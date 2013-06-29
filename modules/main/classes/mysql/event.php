<?
/*
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
*/
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/event.php");

class CEvent extends CAllEvent
{
	public static function CheckEvents()
	{
		if((defined("DisableEventsCheck") && DisableEventsCheck===true) || (defined("BX_CRONTAB_SUPPORT") && BX_CRONTAB_SUPPORT===true && BX_CRONTAB!==true))
			return;

		global $DB, $CACHE_MANAGER;

		if(CACHED_b_event !== false && $CACHE_MANAGER->Read(CACHED_b_event, "events"))
			return "";

		return CEvent::ExecuteEvents();
	}

	public static function ExecuteEvents()
	{
		$err_mess = "<br>Class: CEvent<br>File: ".__FILE__."<br>Function: CheckEvents<br>Line: ";
		global $DB, $CACHE_MANAGER;

		if(defined("BX_FORK_AGENTS_AND_EVENTS_FUNCTION"))
		{
			if(CMain::ForkActions(array("CEvent", "ExecuteEvents")))
				return "";
		}

		$uniq = COption::GetOptionString("main", "server_uniq_id", "");
		if(strlen($uniq)<=0)
		{
			$uniq = md5(uniqid(rand(), true));
			COption::SetOptionString("main", "server_uniq_id", $uniq);
		}

		$bulk = intval(COption::GetOptionString("main", "mail_event_bulk", 5));
		if($bulk <= 0)
			$bulk = 5;

		$strSql=
			"SELECT 'x' ".
			"FROM b_event ".
			"WHERE SUCCESS_EXEC='N' ".
			"LIMIT 1";

		$db_result_event = $DB->Query($strSql);
		if($db_result_event->Fetch())
		{
			$db_lock = $DB->Query("SELECT GET_LOCK('".$uniq."_event', 0) as L");
			$ar_lock = $db_lock->Fetch();
			if($ar_lock["L"]=="0")
				return "";
		}
		else
		{
			if(CACHED_b_event!==false)
				$CACHE_MANAGER->Set("events", true);

			return "";
		}

		$strSql = "
			SELECT ID, C_FIELDS, EVENT_NAME, MESSAGE_ID, LID, DATE_FORMAT(DATE_INSERT, '%d.%m.%Y %H:%i:%s') as DATE_INSERT, DUPLICATE
			FROM b_event
			WHERE SUCCESS_EXEC='N'
			ORDER BY ID
			LIMIT ".$bulk;

		$rsMails = $DB->Query($strSql);

		while($arMail = $rsMails->Fetch())
		{
			$flag = CEvent::HandleEvent($arMail);
			/*
			'0' - нет шаблонов (не нужно было ничего отправлять)
			'Y' - все отправлены
			'F' - все не смогли быть отправлены
			'P' - частично отправлены
			*/
			$strSql = "
				UPDATE b_event SET
					DATE_EXEC = now(),
					SUCCESS_EXEC = '$flag'
				WHERE
					ID = ".$arMail["ID"];
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}

		$DB->Query("SELECT RELEASE_LOCK('".$uniq."_event')");
	}

	public static function CleanUpAgent()
	{
		global $DB;
		$period = abs(intval(COption::GetOptionString("main", "mail_event_period", 14)));
		$strSql = "DELETE FROM b_event WHERE DATE_EXEC <= DATE_ADD(now(), INTERVAL -".$period." DAY)";
		$DB->Query($strSql, true);
		return "CEvent::CleanUpAgent();";
	}
}

///////////////////////////////////////////////////////////////////
// Класс почтовых шаблонов
///////////////////////////////////////////////////////////////////

class CEventMessage extends CAllEventMessage
{
	
	/**
	 * <p>Возвращает список почтовых шаблонов в виде объекта класса <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>.</p>
	 *
	 *
	 *
	 *
	 * @param string &$by = "id" Ссылка на переменную с полем для сортировки, может принимать
	 * значения: <ul> <li> <b>site_id</b> - идентификатор сайта;</li> <li> <b>subject</b> -
	 * тема;</li> <li> <b>timestamp_x</b> - дата изменения;</li> <li> <b>event_name</b> - тип
	 * события;</li> <li> <b>id</b> - ID шаблона;</li> <li> <b>active</b> - активность;</li> </ul>
	 *
	 *
	 *
	 * @param string &$order = "desc" Ссылка на переменную с порядком сортировки, может принимать
	 * значения: <ul> <li> <b>asc</b> - по возрастанию;</li> <li> <b>desc</b> - по
	 * убыванию;</li> </ul>
	 *
	 *
	 *
	 * @param array $filter  Массив вида array("фильтруемое поле"=&gt;"значение" [, ...]), может
	 * принимать значения: <ul> <li> <b>ID</b> - ID шаблона;</li> <li> <b>TYPE</b> - код и
	 * заголовок типа события (допустима <a
	 * href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>);</li> <li> <b>TYPE_ID</b> -
	 * код типа события (допустима <a
	 * href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>);</li> <li>
	 * <b>TIMESTAMP_1</b> - левая часть интервала ("c") для поиска по дате
	 * изменения;</li> <li> <b>TIMESTAMP_2</b> - правая часть интервала ("по") для
	 * поиска по дате изменения;</li> <li> <b>SITE_ID</b> - идентификатор сайта
	 * (допустимо задание массива для поиска по логике "или", либо
	 * допустимо использование <a
	 * href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложной логики</a>);</li> <li> <b>ACTIVE</b> -
	 * флаг активности (Y|N);</li> <li> <b>FROM</b> - поле "От кого" (допустима <a
	 * href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>);</li> <li> <b>TO</b> -
	 * поле "Кому" (допустима <a href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная
	 * логика</a>);</li> <li> <b>BCC</b> - поле "Скрытая копия" (допустима <a
	 * href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>);</li> <li> <b>SUBJECT</b> -
	 * по теме сообщения (допустима <a
	 * href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>);</li> <li> <b>BODY_TYPE</b>
	 * - по типу тела сообщения (text|html);</li> <li> <b>BODY</b> - по телу сообщения
	 * (допустима <a href="http://dev.1c-bitrix.ru/user_help/general/filter.php">сложная
	 * логика</a>);</li> </ul>
	 *
	 *
	 *
	 * @return CDBResult 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $arFilter = Array(
	 *     "ID"            =&gt; "12 | 134",
	 *     "TYPE"          =&gt; "контракт &amp; рекл",
	 *     "TYPE_ID"       =&gt; "ADV_BANNER | ADV_CONTRACT",
	 *     "TIMESTAMP_1"   =&gt; "12.11.2001",
	 *     "TIMESTAMP_2"   =&gt; "12.11.2005",
	 *     "SITE_ID"       =&gt; "ru | en",
	 *     "ACTIVE"        =&gt; "Y",
	 *     "FROM"          =&gt; "bitrixsoft.ru",
	 *     "TO"            =&gt; "#TO#",
	 *     "BCC"           =&gt; "admin",
	 *     "SUBJECT"       =&gt; "конктракт",
	 *     "BODY_TYPE"     =&gt; "text",
	 *     "BODY"          =&gt; "auto"
	 *     );
	 * $rsMess = <b>CEventMessage::GetList</b>($by="site_id", $order="desc", $arFilter);
	 * $is_filtered = $rsMess-&gt;is_filtered;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/ceventmessage/index.php">Поля шаблона
	 * почтового сообщения</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/ceventmessage/getbyid.php">CEventMessage::GetByID</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">Класс CDBResult</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/ceventmessage/getlist.php
	 * @author Bitrix
	 */
	public static function GetList(&$by, &$order, $arFilter=Array())
	{
		$err_mess = "<br>Class: CEventMessage<br>File: ".__FILE__."<br>Function: GetList<br>Line: ";
		global $DB, $USER;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		$bIsLang = false;
		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if(is_array($val))
				{
					if(count($val) <= 0)
						continue;
				}
				else
				{
					if( (strlen($val) <= 0) || ($val === "NOT_REF") )
						continue;
				}
				$match_value_set = array_key_exists($key."_EXACT_MATCH", $arFilter);
				$key = strtoupper($key);
				switch($key)
				{
				case "ID":
					$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
					$arSqlSearch[] = GetFilterQuery("M.ID", $val, $match);
					break;
				case "TYPE":
					$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
					$arSqlSearch[] = GetFilterQuery("M.EVENT_NAME, T.NAME", $val, $match);
					break;
				case "EVENT_NAME":
				case "TYPE_ID":
					$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
					$arSqlSearch[] = GetFilterQuery("M.EVENT_NAME", $val, $match);
					break;
				case "TIMESTAMP_1":
					$arSqlSearch[] = "M.TIMESTAMP_X >= FROM_UNIXTIME('".MkDateTime(FmtDate($val,"D.M.Y"),"d.m.Y")."')";
					break;
				case "TIMESTAMP_2":
					$arSqlSearch[] = "M.TIMESTAMP_X <= FROM_UNIXTIME('".MkDateTime(FmtDate($val,"D.M.Y")." 23:59:59","d.m.Y")."')";
					break;
				case "LID":
				case "LANG":
				case "SITE_ID":
					if (is_array($val)) $val = implode(" | ",$val);
					$arSqlSearch[] = GetFilterQuery("MS.SITE_ID",$val,"N");
					$bIsLang = true;
					break;
				case "ACTIVE":
					$arSqlSearch[] = ($val=="Y") ? "M.ACTIVE = 'Y'" : "M.ACTIVE = 'N'";
					break;
				case "FROM":
					$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
					$arSqlSearch[] = GetFilterQuery("M.EMAIL_FROM", $val, $match);
					break;
				case "TO":
					$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
					$arSqlSearch[] = GetFilterQuery("M.EMAIL_TO", $val, $match);
					break;
				case "BCC":
					$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
					$arSqlSearch[] = GetFilterQuery("M.BCC", $val, $match);
					break;
				case "SUBJECT":
					$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
					$arSqlSearch[] = GetFilterQuery("M.SUBJECT", $val, $match);
					break;
				case "BODY_TYPE":
					$arSqlSearch[] = ($val=="text") ? "M.BODY_TYPE = 'text'" : "M.BODY_TYPE = 'html'";
					break;
				case "BODY":
					$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
					$arSqlSearch[] = GetFilterQuery("M.MESSAGE", $val, $match);
					break;
				}
			}
		}

		if ($by == "id") $strSqlOrder = " ORDER BY M.ID ";
		elseif ($by == "active") $strSqlOrder = " ORDER BY M.ACTIVE ";
		elseif ($by == "event_name") $strSqlOrder = " ORDER BY M.EVENT_NAME ";
		elseif ($by == "from") $strSqlOrder = " ORDER BY M.EMAIL_FROM ";
		elseif ($by == "to") $strSqlOrder = " ORDER BY M.EMAIL_TO ";
		elseif ($by == "bcc") $strSqlOrder = " ORDER BY M.BCC ";
		elseif ($by == "body_type") $strSqlOrder = " ORDER BY M.BODY_TYPE ";
		elseif ($by == "subject") $strSqlOrder = " ORDER BY M.SUBJECT ";
		else
		{
			$strSqlOrder = " ORDER BY M.ID ";
			$by = "id";
		}

		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order = "desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql =
			"SELECT M.ID, M.EVENT_NAME, M.ACTIVE, M.LID, ".($bIsLang? "MS.SITE_ID":"M.LID AS SITE_ID").", M.EMAIL_FROM, M.EMAIL_TO, M.SUBJECT, M.MESSAGE, M.BODY_TYPE, M.BCC,
				M.REPLY_TO,
				M.CC,
				M.IN_REPLY_TO,
				M.PRIORITY,
				M.FIELD1_NAME,
				M.FIELD1_VALUE,
				M.FIELD2_NAME,
				M.FIELD2_VALUE,
			".
				$DB->DateToCharFunction("M.TIMESTAMP_X").
			" TIMESTAMP_X,	if(T.ID is null, M.EVENT_NAME, concat('[ ',T.EVENT_NAME,' ] ',ifnull(T.NAME,'')))	EVENT_TYPE ".
			"FROM b_event_message M ".
			($bIsLang?" LEFT JOIN b_event_message_site MS ON (M.ID = MS.EVENT_MESSAGE_ID)":"")." ".
			"	LEFT JOIN b_event_type T ON (T.EVENT_NAME = M.EVENT_NAME and T.LID = '".LANGUAGE_ID."') ".
			"WHERE ".
			$strSqlSearch.
			$strSqlOrder;

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$res->is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}
}
?>