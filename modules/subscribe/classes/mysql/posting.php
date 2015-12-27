<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/subscribe/classes/general/posting.php");


/**
 * <b>CPosting</b> - класс для работы с выпусками новостей подписки. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cposting/index.php
 * @author Bitrix
 */
class CPosting extends CPostingGeneral
{
	
	/**
	* <p>Метод возвращает список выпусков по фильтру.</p>
	*
	*
	* @param array $arrayaSort = Array() Массив, содержащий признак сортировки в виде наборов "название
	* поля"=&gt;"направление". <br><br> Название поля может принимать
	* значение: <ul> <li> <b>ID</b> - идентификатор выпуска;</li> <li> <b>TIMESTAMP</b> - дата
	* изменения;</li> <li> <b>SUBJECT</b> - тема письма;</li> <li> <b>BODY_TYPE</b> - тип
	* текста;</li> <li> <b>STATUS</b> - статус выпуска;</li> <li> <b>DATE_SENT</b> - дата
	* отправки выпуска;</li> <li> <b>AUTO_SEND_TIME</b> - время автоматической
	* отправки выпуска;</li> </ul> Направление сортировки может принимать
	* значение: <ul> <li> <b>ASC</b> - по возрастанию;</li> <li> <b>DESC</b> - по
	* убыванию.</li> </ul> Пример: <pre class="syntax"><code>array("STATUS"=&gt;"ASC", <br>
	* "DATE_SENT"=&gt;"DESC")</code></pre>
	*
	* @param array $arrayaFilter = Array() Массив, содержащий фильтр в виде наборов "название
	* поля"=&gt;"значение фильтра". <br><br> Название поля может принимать
	* значение: <ul> <li> <b>ID</b> - идентификатор выпуска (возможны сложные
	* условия);</li> <li> <b>TIMESTAMP_1</b> - дата изменения (начало периода);</li> <li>
	* <b>TIMESTAMP_2</b> - дата изменения (конец периода);</li> <li> <b>DATE_SENT_1</b> - дата
	* отправки (начало периода);</li> <li> <b>DATE_SENT_2</b> - дата отправки (конец
	* периода);</li> <li> <b>AUTO_SEND_TIME_1</b> - дата или время автоматической
	* отправки (начало периода);</li> <li> <b>AUTO_SEND_TIME_2</b> - дата или время
	* автоматической отправки (конец периода);</li> <li> <b>STATUS</b> - статус
	* выпуска строкой (возможны сложные условия);</li> <li> <b>STATUS_ID</b> -
	* статус выпуска символом (возможны сложные условия);</li> <li> <b>SUBJECT</b>
	* - тема письма (возможны сложные условия);</li> <li> <b>FROM</b> - поле "от
	* кого" письма (возможны сложные условия);</li> <li> <b>TO</b> - кому
	* отправлен выпуск (возможны сложные условия);</li> <li> <b>BODY_TYPE</b> - тип
	* текста письма;</li> <li> <b>BODY</b> - текст письма (возможны сложные
	* условия);</li> <li> <b>RUB_ID</b> - масив идентификаторов рассылок с
	* которыми связан выпуск; <br> </li> <li> <b>MSG_CHARSET</b> - кодировка в которой
	* был составлен выпуск (точное совпадение).</li> </ul> Пример: <pre
	* class="syntax"><code>array("SUBJECT"=&gt;"test | тест", <br> "TO"=&gt;"@bitrixsoft.ru")</code></pre>
	*
	* @return CDBResult <p>Возвращается результат запроса типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. При выборке из
	* результата методами класса CDBResult становятся доступны <a
	* href="http://dev.1c-bitrix.ru/api_help/subscribe/classes/cposting/cpostingfields.php">поля объекта
	* "Выпуск"</a>, за исключением полей типа text. <br><br> Если поля фильтра
	* содержат ошибку, то переменная LAST_ERROR класса содержит сообщение
	* об ошибке. </p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* $cPosting = new CPosting;<br>$arFilter = Array(<br>    "ID" =&gt; $find_id,<br>    "TIMESTAMP_1" =&gt; $find_timestamp_1,<br>    "TIMESTAMP_2" =&gt; $find_timestamp_2,<br>    "DATE_SENT_1" =&gt; $find_date_sent_1,<br>    "DATE_SENT_2" =&gt; $find_date_sent_2,<br>    "STATUS" =&gt; $find_status,<br>    "STATUS_ID" =&gt; $find_status_id,<br>    "SUBJECT" =&gt; $find_subject,<br>    "FROM" =&gt; $find_from,<br>    "TO" =&gt; $find_to,<br>    "BODY" =&gt; $find_body,<br>    "BODY_TYPE" =&gt; $find_body_type<br>);<br>$rsPosting = <b>$cPosting-&gt;GetList</b>(array($by=&gt;$order), $arFilter);<br>$strError .= $cPosting-&gt;LAST_ERROR;<br><br>$rsPosting-&gt;NavStart(50);<br>echo $rsPosting-&gt;NavPrint("Issues");<br>while($rsPosting-&gt;NavNext(true, "f_"))<br>{<br>    //...<br>}<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cposting/cpostinggetlist.php
	* @author Bitrix
	*/
	public function GetList($aSort=Array(), $arFilter=Array())
	{
		global $DB;
		$this->LAST_ERROR = "";
		$arSqlSearch = Array();
		$arSqlSearch_h = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			foreach($arFilter as $key=>$val)
			{
				if (!is_array($val) && (strlen($val)<=0 || $val=="NOT_REF"))
					continue;

				switch(strtoupper($key))
				{
				case "MSG_CHARSET":
					$arSqlSearch[] = "P.MSG_CHARSET = '".$DB->ForSql($val)."'";
					break;
				case "ID":
					$arSqlSearch[] = GetFilterQuery("P.ID",$val,"N");
					break;
				case "TIMESTAMP_1":
					if($DB->IsDate($val))
						$arSqlSearch[] = "P.TIMESTAMP_X>=".$DB->CharToDateFunction($val, "SHORT");
					else
						$this->LAST_ERROR .= GetMessage("POST_WRONG_TIMESTAMP_FROM")."<br>";
					break;
				case "TIMESTAMP_2":
					if($DB->IsDate($val))
						$arSqlSearch[] = "P.TIMESTAMP_X<DATE_ADD(".$DB->CharToDateFunction($val, "SHORT").",INTERVAL 1 DAY)";
					else
						$this->LAST_ERROR .= GetMessage("POST_WRONG_TIMESTAMP_TILL")."<br>";
					break;
				case "DATE_SENT_1":
					if($DB->IsDate($val))
						$arSqlSearch[] = "P.DATE_SENT>=".$DB->CharToDateFunction($val, "SHORT");
					else
						$this->LAST_ERROR .= GetMessage("POST_WRONG_DATE_SENT_FROM")."<br>";
					break;
				case "DATE_SENT_2":
					if($DB->IsDate($val))
						$arSqlSearch[] = "P.DATE_SENT<DATE_ADD(".$DB->CharToDateFunction($val, "SHORT").",INTERVAL 1 DAY)";
					else
						$this->LAST_ERROR .= GetMessage("POST_WRONG_DATE_SENT_TILL")."<br>";
					break;
				case "STATUS":
					$arSqlSearch_h[] = GetFilterQuery("STATUS_TITLE, P.STATUS",$val);
					break;
				case "STATUS_ID":
					$arSqlSearch[] = GetFilterQuery("P.STATUS",$val,"N");
					break;
				case "SUBJECT":
					$arSqlSearch[] = GetFilterQuery("P.SUBJECT",$val);
					break;
				case "FROM":
					$arSqlSearch[] = GetFilterQuery("P.FROM_FIELD",$val,"Y",array("@","_","."));
					break;
				case "TO":
					$r = GetFilterQuery("PE.EMAIL",$val,"Y",array("@","_","."));
					if(strlen($r) > 0)
						$arSqlSearch[] = "EXISTS (SELECT * FROM b_posting_email PE WHERE PE.POSTING_ID=P.ID AND PE.STATUS='N' AND ".$r.")";
					break;
				case "BODY_TYPE":
					$arSqlSearch[] = ($val=="html") ? "P.BODY_TYPE='html'" : "P.BODY_TYPE='text'";
					break;
				case "RUB_ID":
					if(is_array($val) && count($val) > 0)
					{
						$rub_id = array();
						foreach($val as $i => $v)
						{
							$v = intval($v);
							if($v > 0)
								$rub_id[$v] = $v;
						}
						if(count($rub_id))
							$arSqlSearch[] = "EXISTS (SELECT * from b_posting_rubric PR WHERE PR.POSTING_ID = P.ID AND PR.LIST_RUBRIC_ID in (".implode(", ", $rub_id)."))";
					}
					break;
				case "BODY":
					$arSqlSearch[] = GetFilterQuery("P.BODY",$val);
					break;
				case "AUTO_SEND_TIME_1":
					if($DB->IsDate($val, false, false, "FULL"))
						$arSqlSearch[] = "(P.AUTO_SEND_TIME is not null and P.AUTO_SEND_TIME>=".$DB->CharToDateFunction($val, "FULL")." )";
					elseif($DB->IsDate($val, false, false, "SHORT"))
						$arSqlSearch[] = "(P.AUTO_SEND_TIME is not null and P.AUTO_SEND_TIME>=".$DB->CharToDateFunction($val, "SHORT")." )";
					else
						$this->LAST_ERROR .= GetMessage("POST_WRONG_AUTO_FROM")."<br>";
					break;
				case "AUTO_SEND_TIME_2":
					if($DB->IsDate($val, false, false, "FULL"))
						$arSqlSearch[] = "(P.AUTO_SEND_TIME is not null and P.AUTO_SEND_TIME<=".$DB->CharToDateFunction($val, "FULL")." )";
					elseif($DB->IsDate($val, false, false, "SHORT"))
						$arSqlSearch[] = "(P.AUTO_SEND_TIME is not null and P.AUTO_SEND_TIME<=".$DB->CharToDateFunction($val, "SHORT")." )";
					else
						$this->LAST_ERROR .= GetMessage("POST_WRONG_AUTO_TILL")."<br>";
					break;
				}
			}
		}

		$arOrder = array();
		foreach($aSort as $key => $ord)
		{
			$key = strtoupper($key);
			$ord = (strtoupper($ord) <> "ASC"? "DESC": "ASC");
			switch($key)
			{
				case "ID":		$arOrder[$key] = "P.ID ".$ord; break;
				case "TIMESTAMP":	$arOrder[$key] = "P.TIMESTAMP_X ".$ord; break;
				case "SUBJECT":		$arOrder[$key] = "P.SUBJECT ".$ord; break;
				case "BODY_TYPE":	$arOrder[$key] = "P.BODY_TYPE ".$ord; break;
				case "STATUS":		$arOrder[$key] = "P.STATUS ".$ord; break;
				case "DATE_SENT":	$arOrder[$key] = "P.DATE_SENT ".$ord; break;
				case "AUTO_SEND_TIME":	$arOrder[$key] = "P.AUTO_SEND_TIME ".$ord; break;
				case "FROM_FIELD":	$arOrder[$key] = "P.FROM_FIELD ".$ord; break;
				case "TO_FIELD":	$arOrder[$key] = "P.TO_FIELD ".$ord; break;
			}
		}
		if(count($arOrder) <= 0)
		{
			$arOrder["ID"] = "P.ID DESC";
		}
		$strSqlOrder = " ORDER BY ".implode(", ", $arOrder);

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				if(P.STATUS='S','".$DB->ForSql(GetMessage("POST_STATUS_SENT"))."',
				if(P.STATUS='P','".$DB->ForSql(GetMessage("POST_STATUS_PART"))."',
				if(P.STATUS='E','".$DB->ForSql(GetMessage("POST_STATUS_ERROR"))."',
				if(P.STATUS='W','".$DB->ForSql(GetMessage("POST_STATUS_WAIT"))."',
				'".$DB->ForSql(GetMessage("POST_STATUS_DRAFT"))."')))) as STATUS_TITLE
				,P.ID
				,P.STATUS
				,P.FROM_FIELD
				,P.TO_FIELD
				,P.EMAIL_FILTER
				,P.SUBJECT
				,P.BODY_TYPE
				,P.DIRECT_SEND
				,P.CHARSET
				,P.MSG_CHARSET
				,P.SUBSCR_FORMAT
				,".$DB->DateToCharFunction("P.TIMESTAMP_X")." TIMESTAMP_X
				,".$DB->DateToCharFunction("P.DATE_SENT")." DATE_SENT
			FROM b_posting P
			WHERE
			".$strSqlSearch."
		";
		if(count($arSqlSearch_h)>0)
		{
			$strSqlSearch_h = GetFilterSqlSearch($arSqlSearch_h);
			$strSql = $strSql." HAVING ".$strSqlSearch_h;
		}
		$strSql.=$strSqlOrder;
//		echo htmlspecialcharsbx($strSql);
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$res->is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}

	
	/**
	* <p>Метод возвращает true при успешной блокировке выпуска и false при не успешной. Используется при автоматической отправке выпусков.</p>
	*
	*
	* @param int $ID  Идентификатор выпуска.
	*
	* @return bool <p>В случае успешной блокировки возвращается true. В противном
	* случае возвращается false. Если блокировку не удалось получить
	* из-за ошибки базы данных, то возвращается false и возбуждает
	* исключение (<a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cmain/throwexception.php">CMain::ThrowException</a>).</p> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* if(<b>CPosting::Lock</b>($ID)===false)<br>{<br>	if($e = $APPLICATION-&gt;GetException())<br>		echo "Произошла ошибка БД: ".$e-&gt;GetString();<br>	else<br>		return;<br>}<br>else<br>{<br>	//Выпуск успешно заблокирован<br>	//можно продолжать обработку<br>}<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cposting/cpostinglock.php
	* @author Bitrix
	*/
	public static function Lock($ID=0)
	{
		global $DB, $APPLICATION;
		$ID = intval($ID);
		$uniq = $APPLICATION->GetServerUniqID();
		$db_lock = $DB->Query("SELECT GET_LOCK('".$uniq."_post_".$ID."', 0) as L", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar_lock = $db_lock->Fetch();
		if($ar_lock["L"]=="1")
			return true;
		else
			return false;
	}
	
	/**
	* <p>Метод возвращает true при успешном снятии блокировки выпуска и false при неуспешном. Используется при отправке выпусков.</p>
	*
	*
	* @param int $ID  Идентификатор выпуска.
	*
	* @return bool <p>В случае успешного снятия блокировки возвращается true. В
	* противном случае возвращается false.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* <b>CPosting::UnLock</b>($ID);
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/subscribe/classes/cposting/cpostingunlock.php
	* @author Bitrix
	*/
	public static function UnLock($ID=0)
	{
		global $DB;
		$ID = intval($ID);
		$uniq = COption::GetOptionString("main", "server_uniq_id", "");
		if(strlen($uniq)>0)
		{
			$db_lock = $DB->Query("SELECT RELEASE_LOCK('".$uniq."_post_".$ID."') as L", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$ar_lock = $db_lock->Fetch();
			if($ar_lock["L"]=="0")
				return false;
			else
				return true;
		}
	}
}
?>