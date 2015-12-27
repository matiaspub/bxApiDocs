<?

/**
 * <b>CStatEvent</b> - класс для работы с <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событиями</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/index.php
 * @author Bitrix
 */
class CAllStatEvent
{
	///////////////////////////////////////////////////////////////////
	// Returns string formatted as follows:
	// [sites group ID].<session ID>.<guest ID>.<country ID>.<adv compaign ID>.<adv compaign return Y|N>.<site ID>
	///////////////////////////////////////////////////////////////////
	
	/**
	* <p>Возвращает <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#gid">специальный параметр</a> используемый при создании <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">события</a> в методах <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/add.php">CStatEvent::Add</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/addbyevents.php">CStatEvent::AddByEvents</a>.</p> <p>Значение данного параметра формируется на основе нижеследующих данных текущего <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителя</a>: </p> <ul> <li>краткий идентификатор портала (из настроек модуля "Статистика"); </li> <li>ID <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессии</a>; </li> <li>ID посетителя; </li> <li>двухсимвольный идентификатор страны; </li> <li>ID <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">рекламной кампании</a>; </li> <li>флаг <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_first">прямого захода</a>, либо <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_back">возврата</a> по рекламной кампании; </li> <li>двухсимвольный идентификатор сайта. </li> </ul> В зависимости от значения настройки "Кодировать дополнительный параметр #EVENT_GID# для событий" модуля "Статистика" возвращаемый параметр может быть как в открытом виде, так и в base64-кодированном виде.
	*
	*
	* @param mixed $site_id = false ID сайта.</bod
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // страница, на которую посетитель будет перенаправлен
	* // после фиксации события.
	* // например: платежная система или регистратор
	* $goto = "http://www.softkey.ru/catalog/basket.php?prodid=902&amp;"
	*         "quantity=1&amp;referer1=ritlabs_site&amp;referer2=#EVENT_GID#";
	* 
	* // фиксируем событие
	* $goto = eregi_replace("#EVENT_GID#",
	*                       urlencode(<b>CStatEvent::GetGID</b>()), 
	*                       $goto);
	* CStatEvent::AddCurrent("softkey", "out", "", "", "", $goto);
	* 
	* // перенаправляем посетителя
	* LocalRedirect($goto);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/decodegid.php">CStatEvent::DecodeGID</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#gid">Термин "Специальный параметр
	* события"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/getgid.php
	* @author Bitrix
	*/
	public static function GetGID($site_id=false)
	{
		$s = "";

		$COUNTRY_ID = $_SESSION["SESS_COUNTRY_ID"];
		if (strlen($_SESSION["SESS_COUNTRY_ID"])<=0) $COUNTRY_ID = "N0";

		$s .= $_SESSION["SESS_SESSION_ID"].".".$_SESSION["SESS_GUEST_ID"].".".$COUNTRY_ID;

		if (intval($_SESSION["SESS_ADV_ID"])>0) $s .= ".".$_SESSION["SESS_ADV_ID"].".N";
		elseif (intval($_SESSION["SESS_LAST_ADV_ID"])>0) $s .= ".".$_SESSION["SESS_LAST_ADV_ID"].".Y";
		else $s .= "..";

		if ($site_id===false)
		{
			if (defined("ADMIN_SECTION") && ADMIN_SECTION===true) $site_id = "";
			elseif (defined("SITE_ID")) $site_id = SITE_ID;
		}
		if (strlen($site_id)>0) $s .= ".".$site_id;
		else $s .= ".";

		$encode = COption::GetOptionString("statistic","EVENT_GID_BASE64_ENCODE");
		if ($encode=="Y") $s = base64_encode($s);

		return GetStatGroupSiteID().".".$s;
	}

	///////////////////////////////////////////////////////////////////
	// Event creation
	///////////////////////////////////////////////////////////////////
	
	/**
	* <p>Добавляет <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событие</a> используя текущие параметры <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителя</a>. Если <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">типа события</a> с <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type_id">идентификаторами</a> <i>event1</i>, <i>event2</i> не существует, то он будет автоматически создан с указанными идентификаторами.</p>
	*
	*
	* @param string $event1  Идентификатор типа события event1.
	*
	* @param string $event2 = "" Идентификатор типа события event2.
	*
	* @param string $event3 = "" <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event3">Дополнительный параметр
	* event3</a> события.
	*
	* @param mixed $money = "" Денежная сумма.
	*
	* @param string $currency = "" Трехсимвольный идентификатор валюты. Идентификаторы валют
	* задаются в модуле "Валюты".
	*
	* @param string $goto = "" Адрес страницы куда перешел посетитель. Как правило используется
	* в скриптах вида /bitrix/redirect.php, перенаправляющих посетителей на
	* другие страницы с одновременной фиксацией события.
	*
	* @param string $chargeback = "N" Флаг отрицательной суммы. Используется когда необходимо
	* зафиксировать событие о возврате денег (chargeback). Возможные
	* значения: <ul> <li> <b>Y</b> - денежная сумма отрицательная; </li> <li> <b>N</b> -
	* денежная сумма положительная. </li> </ul>
	*
	* @param mixed $site_id = false ID сайта к которому будет привязано будущее событие.
	*
	* @return array <p>Метод возвращает массив вида: <br><br></p> <pre class="syntax">Array ( [TYPE_ID] =&gt; ID
	* типа события [EID] =&gt; ID добавленного события )</pre> <p></p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // зафиксируем событие типа "Просмотр спец. страницы" (view/special_page)
	* <b>CStatEvent::AddCurrent</b>("softkey", "out", $GLOBALS["APPLICATION"]-&gt;GetCurPage());
	* ?&gt;
	* 
	* &lt;?
	* // зафиксируем событие типа "Уход на оплату заказа на Софткее" (softkey/out)
	* // если такого типа не существует, то он будет автоматически создан
	* // событие будет фиксироваться по параметрам текущего посетителя сайта
	* 
	* // в данной переменной может быть задана страница на которую осуществляется переход
	* $goto = "http://www.softkey.ru/catalog/basket.php?prodid=902&amp;quantity=1&amp;referer1=ritlabs_site&amp;referer2=BITRIX_SM.OTk1LjgyLk4wLjI1Lk4ucnU%3D";
	* 
	* <b>CStatEvent::AddCurrent</b>("softkey", "out", "", "", "", $goto);
	* ?&gt;
	* 
	* &lt;?
	* // зафиксируем событие типа "Скачивание файла manual.chm" (download/manual)
	* // если такого типа не существует, то он будет автоматически создан
	* // событие будет фиксироваться по параметрам текущего посетителя сайта
	* 
	* // сначала проверим не скачивал ли уже текущий посетитель этот файл
	* // в течение последнего часа
	* 
	* // получим ID типа события
	* $rs = CStatEventType::GetByEvents($event1, $event2);
	* if ($ar = $rs-&gt;Fetch())
	* {
	*     // теперь получим все события данного типа для текущего посетителя сайта
	*     // произошедшие за последний час (3600 секунд)
	*     $rs = CStatEvent::GetListByGuest($_SESSION["SESS_GUEST_ID"], $ar["TYPE_ID"], "", 3600);
	*     
	*     // если таких событий не было то
	*     if (!($ar=$rs-&gt;Fetch()))
	*     {
	*         // добавляем данное событие
	*         <b>CStatEvent::AddCurrent</b>("download", "manual");
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/add.php">CStatEvent::Add</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/addbyevents.php">CStatEvent::AddByEvents</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">Термин "Событие"</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event3">Термин "Дополнительный
	* параметр события (event3)"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/addcurrent.php
	* @author Bitrix
	*/
	public static function AddCurrent($event1, $event2="", $event3="", $money="", $currency="", $goto="", $chargeback="N", $site_id=false)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		global $APPLICATION;
		$DB = CDatabase::GetModuleConnection('statistic');

		$event1 = trim($event1);
		$event2 = trim($event2);
		$event3 = trim($event3);

		if(strlen($event1)<=0 && strlen($event2)<=0)
			return array("EVENT_ID"=>0, "TYPE_ID"=>0, "EID"=>0);

		//Check if register event for searcher
		if(intval($_SESSION["SESS_SEARCHER_ID"]) > 0 && COption::GetOptionString("statistic", "SEARCHER_EVENTS")!="Y")
			return array("EVENT_ID"=>0, "TYPE_ID"=>0, "EID"=>0);

		// lookup event type ID
		$EVENT_ID = CStatEvent::SetEventType($event1, $event2, $arEventType);
		// return if it's unknown
		if($EVENT_ID <= 0)
			return array("EVENT_ID"=>0, "TYPE_ID"=>0, "EID"=>0);

		if ($site_id===false)
		{
			if (defined("ADMIN_SECTION") && ADMIN_SECTION===true)
			{
				$sql_site = "null";
				$site_id = false;
			}
			elseif (defined("SITE_ID"))
			{
				$sql_site = "'".$DB->ForSql(SITE_ID,2)."'";
				$site_id = SITE_ID;
			}
			else
			{
				$sql_site = "null";
				$site_id = false;
			}
		}
		else
		{
			if (strlen(trim($site_id))>0)
			{
				$sql_site = "'".$DB->ForSql($site_id,2)."'";
			}
			else
			{
				$sql_site = "null";
				$site_id = false;
			}
		}

		$money = doubleval($money);
		// convert when currency specified
		if (strlen(trim($currency))>0)
		{
			$base_currency = GetStatisticBaseCurrency();
			if (strlen($base_currency)>0)
			{
				if ($currency!=$base_currency)
				{
					if (CModule::IncludeModule("currency"))
					{
						$rate = CCurrencyRates::GetConvertFactor($currency, $base_currency);
						if ($rate>0 && $rate!=1)
							$money = $money * $rate;
					}
				}
			}
		}
		$money = round($money,2);
		$chargeback = ($chargeback=="Y") ? "Y" : "N";

		$goto = preg_replace("/#EVENT_GID#/i", CStatEvent::GetGID($site_id), $goto);
		$sql_KEEP_DAYS = (intval($arEventType["KEEP_DAYS"])>0) ? intval($arEventType["KEEP_DAYS"]) : "null";

		$arr = false;
		$referer_url = strlen($_SERVER["HTTP_REFERER"])<=0 ? $_SESSION["SESS_HTTP_REFERER"] : $_SERVER["HTTP_REFERER"];
		if (strlen($referer_url)>0)
		{
			if($url = @parse_url($referer_url))
			{
				$rs = CSite::GetList($v1="LENDIR", $v2="DESC", Array("ACTIVE"=>"Y", "DOMAIN"=>"%".$url["host"], "IN_DIR"=>$url["path"]));
				$arr = $rs->Fetch();
			}
		}
		$sql_referer_site_id = is_array($arr) && (strlen($arr["ID"]) > 0)? "'".$DB->ForSql($arr["ID"],2)."'": "null";
		$HIT_ID = CKeepStatistics::GetCuurentHitID();

		$arFields = Array(
			"EVENT_ID" => "'".$EVENT_ID."'",
			"EVENT3" => "'".$DB->ForSql($event3,255)."'",
			"MONEY" => $money,
			"DATE_ENTER" => $DB->GetNowFunction(),
			"REFERER_URL" => "'".$DB->ForSql($referer_url,2000)."'",
			"URL" => "'".$DB->ForSql(__GetFullRequestUri(),2000)."'",
			"REDIRECT_URL" => "'".$DB->ForSql($goto,2000)."'",
			"SESSION_ID" => (intval($_SESSION["SESS_SESSION_ID"])>0) ? intval($_SESSION["SESS_SESSION_ID"]) : "null",
			"GUEST_ID" => (intval($_SESSION["SESS_GUEST_ID"])>0) ? intval($_SESSION["SESS_GUEST_ID"]) : "null",
			"ADV_ID" => ($_SESSION["SESS_LAST_ADV_ID"]>0) ? $_SESSION["SESS_LAST_ADV_ID"] : "null",
			"HIT_ID" => ($HIT_ID > 0 ? $HIT_ID : "NULL"),
			"COUNTRY_ID" => "'".$DB->ForSql($_SESSION["SESS_COUNTRY_ID"],2)."'",
			"KEEP_DAYS" => $sql_KEEP_DAYS,
			"CHARGEBACK" => "'".$chargeback."'",
			"SITE_ID" => $sql_site,
			"REFERER_SITE_ID" => $sql_referer_site_id,
		);

		if (intval($_SESSION["SESS_LAST_ADV_ID"])>0 && intval($_SESSION["SESS_ADV_ID"])<=0)
			$arFields["ADV_BACK"]="'Y'";

		$eid = $DB->Insert("b_stat_event_list", $arFields, $err_mess.__LINE__);

		// in case of first occurence
		if (strlen($arEventType["DATE_ENTER"])<=0)
		{
			// set date of the first event
			$arFields =  Array("DATE_ENTER"=>$DB->GetNowFunction());
			$DB->Update("b_stat_event",$arFields,"WHERE ID='".$EVENT_ID."'",$err_mess.__LINE__);
		}

		// day counter update
		$arFields = Array(
			"DATE_LAST" => $DB->GetNowFunction(),
			"COUNTER" => "COUNTER + 1",
			"MONEY" => "MONEY + ".$money
		);
		$rows = $DB->Update("b_stat_event_day",$arFields,"WHERE EVENT_ID='".$EVENT_ID."' and ".CStatistics::DBDateCompare("DATE_STAT"),$err_mess.__LINE__);
		// there was no records updated
		if (intval($rows)<=0)
		{
			// so add one
			$arFields_i = Array(
				"DATE_STAT" => $DB->GetNowDate(),
				"DATE_LAST" => $DB->GetNowFunction(),
				"EVENT_ID" => $EVENT_ID,
				"COUNTER" => 1,
				"MONEY" => $money
			);
			$DB->Insert("b_stat_event_day",$arFields_i, $err_mess.__LINE__);
		}
		elseif (intval($rows)>1) // more than one record for event
		{
			// delete
			$strSql = "SELECT ID FROM b_stat_event_day WHERE EVENT_ID='".$EVENT_ID."' and  ".CStatistics::DBDateCompare("DATE_STAT")." ORDER BY ID";
			$i = 0;
			$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
			while ($ar = $rs->Fetch())
			{
				$i++;
				if ($i > 1)
				{
					$strSql = "DELETE FROM b_stat_event_day WHERE ID = ".$ar["ID"];
					$DB->Query($strSql, false, $err_mess.__LINE__);
				}
			}
		}

		// guest counter
		$arFields = Array("C_EVENTS" => "C_EVENTS+1");
		$DB->Update("b_stat_guest", $arFields, "WHERE ID=".intval($_SESSION["SESS_GUEST_ID"]), $err_mess.__LINE__,false,false,false);

		// session counter
		$arFields = Array("C_EVENTS" => "C_EVENTS+1");
		$DB->Update("b_stat_session", $arFields, "WHERE ID=".intval($_SESSION["SESS_SESSION_ID"]), $err_mess.__LINE__,false,false,false);

		// events counter
		$arFields = Array("C_EVENTS" => "C_EVENTS+1");
		$DB->Update("b_stat_day", $arFields, "WHERE ".CStatistics::DBDateCompare("DATE_STAT"), $err_mess.__LINE__,false,false,false);

		// when site defined
		if (strlen($site_id)>0)
		{
			// update site
			$arFields = Array("C_EVENTS" => "C_EVENTS+1");
			$DB->Update("b_stat_day_site", $arFields, "WHERE SITE_ID='".$DB->ForSql($site_id,2)."' and ".CStatistics::DBDateCompare("DATE_STAT"), $err_mess.__LINE__);
		}

		// there is advertising compaign defined
		if (intval($_SESSION["SESS_ADV_ID"])>0 || intval($_SESSION["SESS_LAST_ADV_ID"])>0)
		{
			// increase revenue
			if ($money!=0)
			{
				$sign = ($chargeback=="Y") ? "-" : "+";
				$arFields = array(
					"REVENUE" => "REVENUE ".$sign." ".$money,
				);
				$DB->Update("b_stat_adv", $arFields, "WHERE ID='".intval($_SESSION["SESS_LAST_ADV_ID"])."'",$err_mess.__LINE__,false,false,false);
			}

			if (intval($_SESSION["SESS_ADV_ID"])>0)
			{
				$arFields = Array(
					"COUNTER" => "COUNTER + 1",
					"MONEY" => "MONEY + ".$money
				);
			}
			else
			{
				$arFields = Array(
					"COUNTER_BACK" => "COUNTER_BACK + 1",
					"MONEY_BACK" => "MONEY_BACK + ".$money
				);
			}

			$rows = $DB->Update("b_stat_adv_event",$arFields,"WHERE ADV_ID='".intval($_SESSION["SESS_LAST_ADV_ID"])."' and EVENT_ID='".$EVENT_ID."'",$err_mess.__LINE__);
			if(intval($rows) <= 0)
			{
				$arFields = Array(
					"ADV_ID" => "'".$_SESSION["SESS_LAST_ADV_ID"]."'",
					"EVENT_ID" => "'".$EVENT_ID."'",
				);
				if(intval($_SESSION["SESS_ADV_ID"]) > 0)
				{
					$arFields["COUNTER"] = "1";
					$arFields["MONEY"] = $money;
				}
				else
				{
					$arFields["COUNTER_BACK"] = "1";
					$arFields["MONEY_BACK"] = $money;
				}
				$DB->Insert("b_stat_adv_event", $arFields, $err_mess.__LINE__);
			}

			if (intval($_SESSION["SESS_ADV_ID"])>0)
			{
				$arFields = Array(
					"COUNTER" => "COUNTER + 1",
					"MONEY" => "MONEY + ".$money,
				);
			}
			else
			{
				$arFields = Array(
					"COUNTER_BACK" => "COUNTER_BACK + 1",
					"MONEY_BACK" => "MONEY_BACK + ".$money,
				);
			}

			$rows = $DB->Update("b_stat_adv_event_day",$arFields,"WHERE ADV_ID='".intval($_SESSION["SESS_LAST_ADV_ID"])."' and EVENT_ID='".$EVENT_ID."' and ".CStatistics::DBDateCompare("DATE_STAT"),$err_mess.__LINE__,false,false,false);
			if(intval($rows) <= 0)
			{
				$arFields = Array(
					"ADV_ID" => ($_SESSION["SESS_LAST_ADV_ID"]>0) ? $_SESSION["SESS_LAST_ADV_ID"] : "null",
					"EVENT_ID" => "'".$EVENT_ID."'",
					"DATE_STAT" => $DB->GetNowDate(),
				);
				if(intval($_SESSION["SESS_ADV_ID"]) > 0)
				{
					$arFields["COUNTER"] = "1";
					$arFields["MONEY"] = $money;
				}
				else
				{
					$arFields["COUNTER_BACK"] = "1";
					$arFields["MONEY_BACK"] = $money;
				}
				$DB->Insert("b_stat_adv_event_day", $arFields, $err_mess.__LINE__);
			}
			elseif(intval($rows) > 1)
			{
				$strSql = "SELECT ID FROM b_stat_adv_event_day WHERE ADV_ID='".intval($_SESSION["SESS_LAST_ADV_ID"])."' and EVENT_ID='".$EVENT_ID."' and ".CStatistics::DBDateCompare("DATE_STAT")." ORDER BY ID";
				$i = 0;
				$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($ar = $rs->Fetch())
				{
					$i++;
					if ($i>1)
					{
						$strSql = "DELETE FROM b_stat_adv_event_day WHERE ID = ".$ar["ID"];
						$DB->Query($strSql, false, $err_mess.__LINE__);
					}
				}
			}
		}

		// todays traffic counters
		CTraffic::IncParam(array("EVENT" => 1), array("EVENT" => 1));

		if (strlen($_SESSION["SESS_COUNTRY_ID"])>0)
			CStatistics::UpdateCountry($_SESSION["SESS_COUNTRY_ID"], Array("C_EVENTS" => 1));
		if ($_SESSION["SESS_CITY_ID"] > 0)
			CStatistics::UpdateCity($_SESSION["SESS_CITY_ID"], Array("C_EVENTS" => 1));

		return array("EVENT_ID"=>intval($EVENT_ID), "TYPE_ID"=>intval($EVENT_ID), "EID"=>intval($eid));
	}

	// creates new event by ID
	public static function AddByID($EVENT_ID, $EVENT3, $DATE_ENTER, $PARAM, $MONEY="", $CURRENCY="", $CHARGEBACK="N")
	{
		return CStatEvent::Add($EVENT_ID, $EVENT3, $DATE_ENTER, $PARAM, $MONEY, $CURRENCY, $CHARGEBACK);
	}

	// creates new event by event1 and event2
	
	/**
	* <p>Добавляет <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событие</a> по заданным <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type_id">идентификаторам</a> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">типа события</a> и <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#gid">специальному параметру</a>. Если <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">типа события</a> с идентификаторами <i>event1</i>, <i>event2</i> не существует, то он будет автоматически создан с указанными идентификаторами.</p>
	*
	*
	* @param string $event1  Идентификатор типа события event1.
	*
	* @param string $event2  Идентификатор типа события event2.
	*
	* @param string $event3  <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event3">Дополнительный параметр
	* event3</a> события.
	*
	* @param string $date  Дата в <a href="http://dev.1c-bitrix.ru/api_help/main/general/constants.php#format_datetime">текущем
	* формате</a>.
	*
	* @param string $gid  <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#gid">Специальный параметр</a> в
	* котором закодированы все необходимые данные для добавления
	* события.
	*
	* @param mixed $money = "" Денежная сумма.
	*
	* @param string $currency = "" Трехсимвольный идентификатор валюты. Идентификаторы валют
	* задаются в модуле "Валюты".
	*
	* @param string $chargeback = "N" Флаг отрицательной суммы. Используется когда необходимо
	* зафиксировать событие о возврате денег (chargeback). Возможные
	* значения: <ul> <li> <b>Y</b> - денежная сумма отрицательная; </li> <li> <b>N</b> -
	* денежная сумма положительная. </li> </ul>
	*
	* @return int <p>Метод возвращает ID добавленного события в случае успеха, и 0,
	* если событие не было добавлено по каким либо причинам.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // добавим событие по типу softkey/buy
	* // если такого типа нет, то он автоматически будет создан
	* 
	* // специальный параметр события в незакодированном виде
	* $gid = "BITRIX_SM.995.82.N0.25.N.ru";
	* 
	* // дата должна быть заданы в формате текущего сайта или языка
	* $date = "23.12.2005 18:15:10";
	* 
	* <b>CStatEvent::AddByEvents</b>("softkey", "buy", "", $date, $gid, "899", "USD");
	* ?&gt;
	* 
	* 
	* &lt;?
	* // добавим событие по типу regnow/buy
	* // если такого типа нет, то он автоматически будет создан
	* 
	* // специальный параметр события в закодированном виде
	* $gid = "BITRIX_SM.OTk1LjgyLk4wLjI1Lk4ucnU%3D";
	* 
	* // дата должна быть заданы в формате текущего сайта или языка
	* $date = "01.06.2005";
	* 
	* <b>CStatEvent::AddByEvents</b>("regnow", "buy", "", $date, $gid, "199", "EUR");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/add.php">CStatEvent::Add</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/addcurrent.php">CStatEvent::AddCurrent</a> </li>
	* <li> <a href="http://www.1c-bitrix.ru/user_help/statistic/events/event_edit.php">Загрузка событий</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">Термин "Событие"</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event3">Термин "Дополнительный
	* параметр события (event3)"</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#gid">Термин "Специальный параметр
	* события"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/addbyevents.php
	* @author Bitrix
	*/
	public static function AddByEvents($EVENT1, $EVENT2, $EVENT3, $DATE_ENTER, $PARAM, $MONEY="", $CURRENCY="", $CHARGEBACK="N")
	{
		$EVENT_ID = CStatEvent::SetEventType($EVENT1, $EVENT2, $arEventType);
		if ($EVENT_ID>0 && strlen($PARAM)>0)
		{
			return CStatEvent::Add($EVENT_ID, $EVENT3, $DATE_ENTER, $PARAM, $MONEY, $CURRENCY, $CHARGEBACK);
		}
	}

	
	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#handler">обработчиков CSV файлов</a> для вывода его в выпадающем списке с помощью функции <a href="http://dev.1c-bitrix.ru/api_help/main/functions/html/selectboxfromarray.php">SelectBoxFromArray</a>, либо в списке множественного выбора с помощью функции <a href="http://dev.1c-bitrix.ru/api_help/main/functions/html/selectboxmfromarray.php">SelectBoxMFromArray</a>.</p> <p>Обработчики CSV файлов могут быть как <i>пользовательские</i> (путь к ним указывается в параметре "Путь к дополнительным обработчикам при ручной загрузке событий" в настройках модуля "Статистика"), так и <i>стандартные</i> - входящие в дистрибутив модуля (хранятся в каталоге <b>/bitrix/modules/statistic/loading/</b>).</p>
	*
	*
	* @param array &$handlers  Данная переменная после отработки метода будет содержать в себе
	* массив путей относительно корня к <i>пользовательским</i>
	* обработчикам. Пример данного массива: <pre class="syntax">Array ( [0] =&gt;
	* /bitrix/php_interface/include/statistic/regnow_alawar.php [1] =&gt;
	* /bitrix/php_interface/include/statistic/regnow_editable.php [2] =&gt;
	* /bitrix/php_interface/include/statistic/regsoft_editable.php [3] =&gt;
	* /bitrix/php_interface/include/statistic/shareit_editable.php [4] =&gt;
	* /bitrix/php_interface/include/statistic/softkey_editable.php )</pre>
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre>
	* Array
	* (
	*     [reference] =&gt; Array
	*         (
	*             [0] =&gt; regnow.php
	*             [1] =&gt; regsoft.php
	*             [2] =&gt; shareit_eur.php
	*             [3] =&gt; shareit_usd.php
	*             [4] =&gt; softkey.php
	*             [5] =&gt; [1] regnow_alawar.php
	*             [6] =&gt; [2] regnow_editable.php
	*             [7] =&gt; [3] regsoft_editable.php
	*             [8] =&gt; [4] shareit_editable.php
	*             [9] =&gt; [5] softkey_editable.php
	*         )
	* 
	*     [reference_id] =&gt; Array
	*         (
	*             [0] =&gt; /bitrix/modules/statistic/loading/regnow.php
	*             [1] =&gt; /bitrix/modules/statistic/loading/regsoft.php
	*             [2] =&gt; /bitrix/modules/statistic/loading/shareit_eur.php
	*             [3] =&gt; /bitrix/modules/statistic/loading/shareit_usd.php
	*             [4] =&gt; /bitrix/modules/statistic/loading/softkey.php
	*             [5] =&gt; /bitrix/php_interface/include/statistic/regnow_alawar.php
	*             [6] =&gt; /bitrix/php_interface/include/statistic/regnow_editable.php
	*             [7] =&gt; /bitrix/php_interface/include/statistic/regsoft_editable.php
	*             [8] =&gt; /bitrix/php_interface/include/statistic/shareit_editable.php
	*             [9] =&gt; /bitrix/php_interface/include/statistic/softkey_editable.php
	*         )
	* 
	* )&lt;?
	* // получим список доступных обработчиков
	* $arrHandlers = <b>CStatEvent::GetHandlerList</b>($arUSER_HANDLERS);
	* 
	* // выведем этот список в виде выпадающего списка
	* echo SelectBoxFromArray("handler", $arrHandlers, $handler, " ");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/add.php">CStatEvent::Add</a> </li> <li>
	* <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/addbyevents.php">CStatEvent::AddByEvents</a>
	* </li> <li> <a href="http://www.1c-bitrix.ru/user_help/statistic/events/event_edit.php">Загрузка
	* событий</a> </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">Термин
	* "Событие"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/gethandlerlist.php
	* @author Bitrix
	*/
	public static function GetHandlerList(&$arUSER_HANDLERS)
	{
		$arr = array();
		$arReferenceId = array();
		$arReference = array();
		$arUSER_HANDLERS = array();
		$i=0;

		// system loaders
		$path = "";
		$path = COption::GetOptionString("statistic", "EVENTS_LOAD_HANDLERS_PATH");
		$handle=@opendir($_SERVER["DOCUMENT_ROOT"].$path);
		if($handle)
		{
			while (false!==($fname = readdir($handle)))
			{
				if (is_file($_SERVER["DOCUMENT_ROOT"].$path.$fname) && $fname!="." && $fname!="..")
				{
					$arReferenceId[] = $path.$fname;
					$arReference[] = $fname;
				}
			}
			closedir($handle);
		}

		// user defined loaders
		$path = "";
		$path = COption::GetOptionString("statistic", "USER_EVENTS_LOAD_HANDLERS_PATH");
		$handle=@opendir($_SERVER["DOCUMENT_ROOT"].$path);
		if($handle)
		{
			while (false!==($fname = readdir($handle)))
			{
				if (is_file($_SERVER["DOCUMENT_ROOT"].$path.$fname) && $fname!="." && $fname!="..")
				{
					$i++;
					$arReferenceId[] = $path.$fname;
					$arUSER_HANDLERS[] = $path.$fname;
					$arReference[] = "[".$i."] ".$fname;
				}
			}
			closedir($handle);
		}

		$arr = array("reference"=>$arReference,"reference_id"=>$arReferenceId);
		return $arr;
	}

	// decodes EVENT_GID into array
	
	/**
	* <p>Декодирует <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#gid">специальный параметр</a> используемый при создании <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">события</a> в методах <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/add.php">CStatEvent::Add</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/addbyevents.php">CStatEvent::AddByEvents</a>. Метод успешно декодирует параметр как в открытом виде, так и в base64-кодированном.</p>
	*
	*
	* @param string $gid  Специальный параметр события.
	*
	* @return array <p>Метод возвращает массив вида: </p> <br><pre class="syntax">Array ( [SESSION_ID] =&gt; ID
	* сессии [GUEST_ID] =&gt; ID посетителя [COUNTRY_ID] =&gt; ID страны [ADV_ID] =&gt; ID
	* рекламной кампании [ADV_BACK] =&gt; Y - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_back">возврат</a> по рекламной
	* кампании; N - <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_first">прямой
	* заход</a> [SITE_ID] =&gt; ID сайта )</pre> <p></p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // раскодируем специальный параметр события
	* $arr = <b>CStatEvent::DecodeGID</b>("BITRIX_SM.OTk1LjgyLk4wLjI1Lk4ucnU%3D");
	* 
	* // распечатаем результирующий массив
	* echo "&lt;pre&gt;"; print_r($arr); echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/getgid.php">CStatEvent::GetGID</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#gid">Термин "Специальный
	* параметр события"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/decodegid.php
	* @author Bitrix
	*/
	public static function DecodeGID($EVENT_GID)
	{
		$ar = explode(".",$EVENT_GID);
		$sid = intval($ar[1]);
		$gid = intval($ar[2]);
		$base64 = "Y";

		if ((count($ar)==6 || count($ar)==7) && $sid>0 && $gid>0 && strlen($ar[1])==strlen($sid) && strlen($ar[2])==strlen($gid)) $base64 = "N";
		if ($base64=="Y")
		{
			$group_site_id = GetStatGroupSiteID();
			$s = substr($EVENT_GID,strlen($group_site_id)+1,strlen($EVENT_GID));
			$EVENT_GID = $group_site_id.".".base64_decode($s);
		}
		$arr = explode(".",$EVENT_GID);
		$SESSION_ID = (intval($arr[1])>0) ? intval($arr[1]) : "";
		$GUEST_ID = (intval($arr[2])>0) ? intval($arr[2]) : "";
		$COUNTRY_ID = (strlen($arr[3])>0) ? $arr[3] : "";
		$ADV_ID = (intval($arr[4])>0) ? intval($arr[4]) : "";
		$ADV_BACK = ($arr[5]=="Y" || $arr[5]=="N") ? $arr[5] : "";
		$SITE_ID = (strlen($arr[6])>0) ? $arr[6] : "";

		$arrRes = array(
			"SESSION_ID" => $SESSION_ID,
			"GUEST_ID" => $GUEST_ID,
			"COUNTRY_ID" => $COUNTRY_ID,
			"ADV_ID" => $ADV_ID,
			"ADV_BACK" => $ADV_BACK,
			"SITE_ID" => $SITE_ID,
		);
		return $arrRes;
	}

	// compatibility
	public static function SetEventType($event1, $event2, &$arEventType) { return CStatEventType::ConditionSet($event1, $event2, $arEventType); }
	public static function GetByEvents($event1, $event2) { return CStatEventType::GetByEvents($event1, $event2); }
	public static function GetEventsByGuest($GUEST_ID, $EVENT_ID=false, $EVENT3=false, $SEC=false) { return CStatEvent::GetListByGuest($GUEST_ID, $EVENT_ID, $EVENT3, $SEC); }

	public static function GetListUniqueCheck($arFilter=Array(), $LIMIT=1)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();
		$strSqlSearch = "";
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
					case "EVENT3":
						$arSqlSearch[] = "E.EVENT3 = '".$DB->ForSql($val,255)."'";
						break;
					case "DATE":
						if (CheckDateTime($val))
							$arSqlSearch[] = "E.DATE_ENTER=".$DB->CharToDateFunction($val);
						break;
					case "EVENT_ID":
					case "SESSION_ID":
					case "GUEST_ID":
					case "ADV_ID":
					case "COUNTRY_ID":
						$arSqlSearch[] = "E.".$key."='".$DB->ForSql($val)."'";
						break;
					case "ADV_BACK":
						$arSqlSearch[] = ($val=="Y") ? "E.ADV_BACK='Y'" : "E.ADV_BACK='N'";
						break;
					case "SITE_ID":
						$arSqlSearch[] = "E.SITE_ID = '".$DB->ForSql($val,2)."'";
						break;
				}
			}
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT /*TOP*/
				E.ID
			FROM
				b_stat_event_list E
			WHERE
				".$strSqlSearch."
		";

		$res = $DB->Query(CStatistics::DBTopSql($strSql, $LIMIT), false, $err_mess.__LINE__);
		return $res;
	}
}
?>
