<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/classes/general/statevent.php");

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
class CStatEvent extends CAllStatEvent
{
	
	/**
	* <p>Возвращает список идентификаторов <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событий</a> по указанному ID <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителя</a> сайта.</p>
	*
	*
	* @param int $guest_id  ID посетителя.
	*
	* @param mixed $type_id = false ID типа события. Если значение равно "false", то фильтрации по типу
	* события не будет.
	*
	* @param mixed $event3 = false <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event3">Дополнительный параметр
	* event3</a> события. Если значение равно "false", то фильтрации по event3 не
	* будет.
	*
	* @param mixed $time = false Количество секунд, прошедших с текущего момента. Если значение
	* равно "false", то фильтрации по времени не будет.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // зафиксируем событие типа
	* // "Скачивание файла manual.chm" (download/manual)
	* // если такого типа не существует, он будет автоматически создан
	* // событие будет фиксироваться по параметрам
	* // текущего посетителя сайта
	* 
	* // сначала проверим - не скачивал ли уже текущий посетитель
	* // этот файл в течение последнего часа
	* 
	* // получим ID типа события
	* $rs = CStatEventType::GetByEvents($event1, $event2);
	* if ($ar = $rs-&gt;Fetch())
	* {
	*     // теперь получим все события данного типа
	*     // для текущего посетителя сайта,
	*     // произошедшие за последний час (3600 секунд)
	*     $rs = <b>CStatEvent::GetListByGuest</b>($_SESSION["SESS_GUEST_ID"], 
	*                                      $ar["TYPE_ID"], "", 3600);
	*     
	*     // если таких событий не было...
	*     if (!($ar=$rs-&gt;Fetch()))
	*     {
	*         // ...добавляем данное событие
	*         CStatEvent::AddCurrent("download", "manual");
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/getlist.php">CStatEvent::GetList</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">Термин "Событие"</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event3">Термин "Дополнительный
	* параметр события (event3)"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/getlistbyguest.php
	* @author Bitrix
	*/
	public static function GetListByGuest($GUEST_ID, $EVENT_ID=false, $EVENT3=false, $SEC=false)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');

		$strSqlSearch = "";
		if ($EVENT_ID!==false)
			$strSqlSearch .= " and E.EVENT_ID='".intval($EVENT_ID)."' ";
		if ($EVENT3!==false)
			$strSqlSearch .= " and E.EVENT3='".$DB->ForSql($EVENT3,255)."' ";
		if ($SEC!==false)
			$strSqlSearch .= " and E.DATE_ENTER > DATE_ADD(now(),INTERVAL - ".intval($SEC)." SECOND) ";

		$strSql = "
			SELECT
				E.ID
			FROM
				b_stat_event_list E
			WHERE
				E.GUEST_ID = ".intval($GUEST_ID)."
				".$strSqlSearch."
		";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return $res;
	}

	
	/**
	* <p>Добавляет <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событие</a> по заданному <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">типу</a> и <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#gid">специальному параметру</a>.</p> <p><b>Примечание</b>. Метод использует внутреннюю транзакцию. Если у вас используется <b>MySQL</b> и <b>InnoDB</b>, и ранее была открыта транзакция, то ее необходимо закрыть до подключения метода.</p>
	*
	*
	* @param int $type_id  ID типа события.
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
	* @param string $chargeback = "N" Флаг отрицательной суммы. Используется, когда необходимо
	* зафиксировать событие о возврате денег (chargeback). Возможные
	* значения: <ul> <li> <b>Y</b> - денежная сумма отрицательная; </li> <li> <b>N</b> -
	* денежная сумма положительная. </li> </ul>
	*
	* @return int <p>Функция возвращает ID добавленного события в случае успеха и 0
	* если событие не было добавлено по каким либо причинам.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // добавим событие по типу события #1
	* // данный тип должен быть заранее создан
	* 
	* // специальный параметр события в незакодированном виде
	* $gid = "BITRIX_SM.995.82.N0.25.N.ru"; 
	* 
	* // дата должна быть заданы в формате текущего сайта или языка
	* $date = "23.12.2005 18:15:10";
	* 
	* <b>CStatEvent::Add</b>(1, "", $date, $gid, 99, "USD");
	* ?&gt;
	* 
	* &lt;?
	* // добавим событие по типу события #2
	* // данный тип должен быть заранее создан
	* 
	* // специальный параметр события в закодированном виде
	* $gid = "BITRIX_SM.OTk1LjgyLk4wLjI1Lk4ucnU%3D";
	* 
	* // дата должна быть заданы в формате текущего сайта или языка
	* $date = "01.06.2005";
	* 
	* <b>CStatEvent::Add</b>(2, "", $date, $gid, "199", "EUR");
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/addbyevents.php">CStatEvent::AddByEvents</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/addcurrent.php">CStatEvent::AddCurrent</a>
	* </li> <li> <a href="http://www.1c-bitrix.ru/user_help/statistic/events/event_edit.php">Загрузка
	* событий</a> </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">Термин
	* "Событие"</a> </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event3">Термин
	* "Дополнительный параметр события (event3)"</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#gid">Термин "Специальный параметр
	* события"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/add.php
	* @author Bitrix
	*/
	public static function Add($EVENT_ID, $EVENT3, $DATE_ENTER, $PARAM, $MONEY="", $CURRENCY="", $CHARGEBACK="N")
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');

		$EVENT_ID = intval($EVENT_ID);
		$EVENT_LIST_ID = 0;
		$strSql = "SELECT KEEP_DAYS FROM b_stat_event WHERE ID = $EVENT_ID";
		$rsEvent = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($arEvent = $rsEvent->Fetch())
		{
			$MONEY = doubleval($MONEY);

			// если указана валюта то конвертируем
			if (strlen(trim($CURRENCY))>0)
			{
				$base_currency = GetStatisticBaseCurrency();
				if (strlen($base_currency)>0)
				{
					if ($CURRENCY!=$base_currency)
					{
						if (CModule::IncludeModule("currency"))
						{
							$rate = CCurrencyRates::GetConvertFactor($CURRENCY, $base_currency);
							if ($rate>0 && $rate!=1) $MONEY = $MONEY * $rate;
						}
					}
				}
			}
			$MONEY = round($MONEY,2);

			$arr = CStatEvent::DecodeGid($PARAM);
			$SESSION_ID		= intval($arr["SESSION_ID"]);
			$GUEST_ID		= intval($arr["GUEST_ID"]);
			$COUNTRY_ID		= $arr["COUNTRY_ID"];
			$ADV_ID			= intval($arr["ADV_ID"]);
			$ADV_BACK		= ($arr["ADV_BACK"]=="Y") ? "Y" : "N";
			$CHARGEBACK		= ($CHARGEBACK=="Y") ? "Y" : "N";
			$SITE_ID		= $arr["SITE_ID"];

			$DATE_ENTER = strlen(trim($DATE_ENTER))>0 ? $DATE_ENTER : GetTime(time(),"FULL");
			$TIME_ENTER_TMSTMP = MakeTimeStamp($DATE_ENTER);
			if (!$TIME_ENTER_TMSTMP)
			{
				$DATE_ENTER = GetTime(time(),"FULL");
				$TIME_ENTER_TMSTMP = MakeTimeStamp($DATE_ENTER);
			}
			$TIME_ENTER_SQL = "FROM_UNIXTIME('".$TIME_ENTER_TMSTMP."')";
			$DAY_ENTER_TMSTMP = MakeTimeStamp($DATE_ENTER);
			$DAY_ENTER_SQL = "FROM_UNIXTIME('".$DAY_ENTER_TMSTMP."')";

			$DB->StartTransaction();

			$arFields = array(
				"EVENT_ID"		=> $EVENT_ID,
				"EVENT3"		=> "'".$DB->ForSql($EVENT3,255)."'",
				"MONEY"			=> $MONEY,
				"DATE_ENTER"	=> $TIME_ENTER_SQL,
				"SESSION_ID"	=> (intval($SESSION_ID)>0) ? intval($SESSION_ID) : "null",
				"GUEST_ID"		=> (intval($GUEST_ID)>0) ? intval($GUEST_ID) : "null",
				"ADV_ID"		=> (intval($ADV_ID)>0) ? intval($ADV_ID) : "null",
				"ADV_BACK"		=> ($ADV_BACK=="Y") ? "'Y'" : "'N'",
				"COUNTRY_ID"	=> (strlen($COUNTRY_ID)>0) ? "'".$DB->ForSql($COUNTRY_ID,2)."'" : "null",
				"KEEP_DAYS"		=> (intval($arEvent["KEEP_DAYS"])>0) ? intval($arEvent["KEEP_DAYS"]) : "null",
				"CHARGEBACK"	=> "'".$CHARGEBACK."'",
				"SITE_ID"		=> (strlen($SITE_ID)>0) ? "'".$DB->ForSql($SITE_ID,2)."'" : "null"
				);
			$EVENT_LIST_ID = $DB->Insert("b_stat_event_list",$arFields, $err_mess.__LINE__);

			// увеличиваем счетчик для страны
			if (strlen($COUNTRY_ID)>0)
				CStatistics::UpdateCountry($COUNTRY_ID, Array("C_EVENTS" => 1));

			// если нужно обновляем дату первого события для данного типа события
			$arFields = Array("DATE_ENTER" => $DB->GetNowFunction());
			$DB->Update("b_stat_event",$arFields,"WHERE ID='".$EVENT_ID."' and DATE_ENTER is null",$err_mess.__LINE__);
			// обновляем счетчик по дням для данного типа события
			$arFields = Array(
					"DATE_LAST"	=> $DB->GetNowFunction(),
					"COUNTER"	=> "COUNTER + 1",
					"MONEY"		=> "MONEY + ".$MONEY
					);
			$rows = $DB->Update("b_stat_event_day",$arFields,"WHERE EVENT_ID='".$EVENT_ID."' and DATE_STAT = ".$DAY_ENTER_SQL, $err_mess.__LINE__);
			// если обсчета по дням нет то
			if (intval($rows)<=0)
			{
				// добавляем его
				$arFields_i = Array(
					"DATE_STAT"	=> $DAY_ENTER_SQL,
					"DATE_LAST"	=> $TIME_ENTER_SQL,
					"EVENT_ID"	=> $EVENT_ID,
					"COUNTER"	=> 1,
					"MONEY"		=> $MONEY
					);
				$DB->Insert("b_stat_event_day",$arFields_i, $err_mess.__LINE__);
			}
			elseif (intval($rows)>1) // если обновили более одного дня то
			{
				// удалим лишние
				$strSql = "SELECT ID FROM b_stat_event_day WHERE EVENT_ID='".$EVENT_ID."' and DATE_STAT = ".$DAY_ENTER_SQL." ORDER BY ID";
				$i=0;
				$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($ar = $rs->Fetch())
				{
					$i++;
					if ($i>1)
					{
						$strSql = "DELETE FROM b_stat_event_day WHERE ID = ".$ar["ID"];
						$DB->Query($strSql, false, $err_mess.__LINE__);
					}
				}
			}

			// обновляем сессию и гостя
			$arFields = Array("C_EVENTS" => "C_EVENTS+1");
			$DB->Update("b_stat_session",$arFields,"WHERE ID=".$SESSION_ID, $err_mess.__LINE__,false,false,false);
			$DB->Update("b_stat_guest",$arFields,"WHERE ID=".$GUEST_ID, $err_mess.__LINE__,false,false,false);

			// обновляем дневной счетчик
			$arFields = Array("C_EVENTS" => "C_EVENTS + 1");
			$DB->Update("b_stat_day",$arFields,"WHERE DATE_STAT = ".$DAY_ENTER_SQL, $err_mess.__LINE__,false,false,false);

			// увеличиваем счетчик траффика
			CTraffic::IncParam(array("EVENT" => 1), array(), false, $DATE_ENTER);

			// если сайт определен то
			if (strlen($SITE_ID)>0)
			{
				// обновляем дневной счетчик
				$arFields = Array("C_EVENTS" => "C_EVENTS+1");
				$DB->Update("b_stat_day_site", $arFields, "WHERE SITE_ID='".$DB->ForSql($SITE_ID,2)."' and DATE_STAT = ".$DAY_ENTER_SQL, $err_mess.__LINE__);

				// увеличиваем счетчик траффика
				CTraffic::IncParam(array(), array("EVENT" => 1), $SITE_ID, $DATE_ENTER);
			}

			if ($ADV_ID>0)
			{
				$a = $DB->Query("SELECT 'x' FROM b_stat_adv WHERE ID='".$ADV_ID."'", false, $err_mess.__LINE__);
				// если есть такая рекламная кампания то
				if ($ar = $a->Fetch())
				{
					// увеличиваем доход рекламной кампании
					if ($MONEY!=0)
					{
						$sign = ($CHARGEBACK=="Y") ? "-" : "+";
						$arFields = array("REVENUE" => "REVENUE ".$sign." ".$MONEY);
						$DB->Update("b_stat_adv",$arFields,"WHERE ID='$ADV_ID'",$err_mess.__LINE__,false,false,false);
					}
					// обновляем счетчик связки рекламной кампании и типа события
					if ($ADV_BACK=="Y")
					{
						$arFields = array(
							"COUNTER_BACK"	=> "COUNTER_BACK + 1",
							"MONEY_BACK"	=> "MONEY_BACK + ".$MONEY
							);
					}
					else
					{
						$arFields = array(
							"COUNTER"	=> "COUNTER + 1",
							"MONEY"		=> "MONEY + ".$MONEY
							);
					}
					$rows = $DB->Update("b_stat_adv_event",$arFields,"WHERE ADV_ID='$ADV_ID' and EVENT_ID='$EVENT_ID'",$err_mess.__LINE__);
					// если связки нет то
					if (intval($rows)<=0 && intval($ADV_ID)>0 && intval($EVENT_ID)>0)
					{
						// вставляем связку
						$arFields = Array(
							"ADV_ID"	=> "'".intval($ADV_ID)."'",
							"EVENT_ID"	=> "'".intval($EVENT_ID)."'"
							);
						if ($ADV_BACK=="Y")
						{
							$arFields["COUNTER_BACK"] = 1;
							$arFields["MONEY_BACK"] = $MONEY;
						}
						else
						{
							$arFields["COUNTER"] = 1;
							$arFields["MONEY"] = $MONEY;
						}
						$DB->Insert("b_stat_adv_event", $arFields, $err_mess.__LINE__);
					}

					// обновляем счетчик связки по дням
					if ($ADV_BACK=="Y")
					{
						$arFields = array(
							"COUNTER_BACK"	=> "COUNTER_BACK + 1",
							"MONEY_BACK"	=> "MONEY_BACK + ".$MONEY
							);
					}
					else
					{
						$arFields = array(
							"COUNTER"	=> "COUNTER + 1",
							"MONEY"		=> "MONEY + ".$MONEY
							);
					}
					$rows = $DB->Update("b_stat_adv_event_day",$arFields,"WHERE ADV_ID='$ADV_ID' and EVENT_ID='$EVENT_ID' and DATE_STAT = ".$DAY_ENTER_SQL, $err_mess.__LINE__,false,false,false);
					// если нет такой связки то
					if (intval($rows)<=0 && intval($ADV_ID)>0 && intval($EVENT_ID)>0)
					{
						// вставляем ее
						$arFields = Array(
							"DATE_STAT"	=> $DAY_ENTER_SQL,
							"ADV_ID"	=> "'".$ADV_ID."'",
							"EVENT_ID"	=> "'".$EVENT_ID."'"
							);
						if ($ADV_BACK=="Y")
						{
							$arFields["COUNTER_BACK"] = 1;
							$arFields["MONEY_BACK"] = $MONEY;
						}
						else
						{
							$arFields["COUNTER"] = 1;
							$arFields["MONEY"] = $MONEY;
						}
						$DB->Insert("b_stat_adv_event_day", $arFields, $err_mess.__LINE__);
					}
				}
			}
			$DB->Commit();
		}
		return intval($EVENT_LIST_ID);
	}


	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событий</a>.</p>
	*
	*
	* @param string &$by = "s_id" Поле для сортировки. Возможные значения: <ul> <li> <b>s_id</b> - ID события;
	* </li> <li> <b>s_site_id</b> - ID сайта; </li> <li> <b>s_type_id</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">типа события</a>; </li> <li>
	* <b>s_event3</b> - <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event3">дополнительный
	* параметр event3</a> события; </li> <li> <b>s_date_enter</b> - время создания события;
	* </li> <li> <b>s_adv_id</b> - ID <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">рекламной
	* кампании</a>; </li> <li> <b>s_adv_back</b> - флаг <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_back">возврата</a> либо <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_first">прямого захода</a> по
	* рекламной кампании; </li> <li> <b>s_session_id</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессии</a>; </li> <li> <b>s_guest_id</b> - ID
	* <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителя</a>; </li> <li>
	* <b>s_hit_id</b> - ID <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хита</a>; </li> <li>
	* <b>s_url</b> - страница где зафиксированно событие; </li> <li> <b>s_referer_url</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#referer">ссылающаяся страница</a>; </li>
	* <li> <b>s_redirect_url</b> - страница куда был перенаправлен посетитель после
	* фиксации события; </li> <li> <b>s_country_id</b> - ID страны посетителя; </li> <li>
	* <b>s_money</b> - денежная сумма. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID события; </li> <li>
	* <b>ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по <b>ID</b>
	* будет искаться вхождение; </li> <li> <b>EVENT_ID</b>* - ID типа события; </li> <li>
	* <b>EVENT_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>EVENT_ID</b> будет искаться вхождение; </li> <li> <b>EVENT_NAME</b>* - название
	* типа события; </li> <li> <b>EVENT_NAME_EXACT_MATCH</b> - если значение равно "Y", то
	* при фильтрации по <b>EVENT_NAME</b> будет искаться точное совпадение; </li>
	* <li> <b>EVENT1</b>* - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type_id">идентификатор event1</a> типа
	* события; </li> <li> <b>EVENT1_EXACT_MATCH</b> - если значение равно "Y", то при
	* фильтрации по <b>EVENT1</b> будет искаться точное совпадение; </li> <li>
	* <b>EVENT2</b>* - <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type_id">идентификатор
	* event2</a> типа события; </li> <li> <b>EVENT2_EXACT_MATCH</b> - если значение равно "Y",
	* то при фильтрации по <b>EVENT2</b> будет искаться точное совпадение; </li>
	* <li> <b>EVENT3</b>* - дополнительный параметр event3 события; </li> <li>
	* <b>EVENT3_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>EVENT3</b> будет искаться точное совпадение; </li> <li> <b>DATE</b> - время
	* события (точное совпадение); </li> <li> <b>DATE1</b> - начальное значение
	* интервала для поля "дата события"; </li> <li> <b>DATE2</b> - начальное
	* значение интервала для поля "дата события"; </li> <li> <b>MONEY</b> -
	* денежная сумма события (точное совпадение); </li> <li> <b>MONEY1</b> -
	* начальное значение интервала для поля "денежная сумма"; </li> <li>
	* <b>MONEY2</b> - конечное значение интервала для поля "денежная сумма";
	* </li> <li> <b>CURRENCY</b> - трехсимвольный идентификатор валюты для
	* денежной суммы; </li> <li> <b>SESSION_ID</b>* - ID сессии; </li> <li> <b>SESSION_ID_EXACT_MATCH</b> -
	* если значение равно "N", то при фильтрации по <b>SESSION_ID</b> будет
	* искаться вхождение; </li> <li> <b>GUEST_ID</b>* - ID посетителя; </li> <li>
	* <b>GUEST_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>GUEST_ID</b> будет искаться вхождение; </li> <li> <b>ADV_ID</b>* - ID рекламной
	* кампании; </li> <li> <b>ADV_ID_EXACT_MATCH</b> - если значение равно "N", то при
	* фильтрации по <b>ADV_ID</b> будет искаться вхождение; </li> <li> <b>ADV_BACK</b> -
	* флаг "возврат по рекламной кампании", возможные значения: <ul> <li>
	* <b>Y</b> - был возврат; </li> <li> <b>N</b> - был прямой заход. </li> </ul> </li> <li>
	* <b>HIT_ID</b>* - ID хита; </li> <li> <b>HIT_ID_EXACT_MATCH</b> - если значение равно "N", то
	* при фильтрации по <b>HIT_ID</b> будет искаться вхождение; </li> <li>
	* <b>COUNTRY_ID</b>* - ID страны посетителя сгенерировавшего событие; </li> <li>
	* <b>COUNTRY_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>COUNTRY_ID</b> будет искаться вхождение; </li> <li> <b>COUNTRY</b>* - название
	* страны посетителя сгенерировавшего событие; </li> <li> <b>COUNTRY_EXACT_MATCH</b>
	* - если значение равно "Y", то при фильтрации по <b>COUNTRY</b> будет
	* искаться точное совпадение; </li> <li> <b>REFERER_URL</b>* - ссылающаяся
	* страница; </li> <li> <b>REFERER_URL_EXACT_MATCH</b> - если значение равно "Y", то при
	* фильтрации по <b>REFERER_URL</b> будет искаться точное совпадение; </li> <li>
	* <b>REFERER_SITE_ID</b> - ID сайта для ссылающейся страницы; </li> <li> <b>URL</b>* -
	* страница на которой было зафиксировано событие; </li> <li>
	* <b>URL_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по <b>URL</b>
	* будет искаться точное совпадение; </li> <li> <b>SITE_ID</b> - ID сайта для
	* страницы на которой было зафиксировано событие; </li> <li> <b>REDIRECT_URL</b>*
	* - страница куда был перенаправлен посетитель после фиксации
	* события; </li> <li> <b>REDIRECT_URL_EXACT_MATCH</b> - если значение равно "Y", то при
	* фильтрации по <b>REDIRECT_URL</b> будет искаться точное совпадение. </li> </ul>
	* * - допускается <a href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная
	* логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности результирующего списка. Если значение
	* равно "true", то список был отфильтрован.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // выберем все неудаленные события посетителя #1025
	* $arFilter = array(
	*     "GUEST_ID" =&gt; "1025"
	*     );
	* 
	* // получим список записей
	* $rs = <b>CStatEvent::GetList</b>(
	*     ($by = "s_id"), 
	*     ($order = "desc"), 
	*     $arFilter, 
	*     $is_filtered
	*     );
	* 
	* // выведем все записи
	* while ($ar = $rs-&gt;Fetch())
	* {
	*     echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";    
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/getlistbyguest.php">CStatEvent::GetListByGuest</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">Термин "Событие"</a> </li>
	* </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/getlist.php
	* @author Bitrix
	*/
	public static 	function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();
		$arSqlSearch_h = Array();
		$strSqlSearch_h = "";
		$CURRENCY = "";
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
					case "EVENT_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("E.".$key,$val,$match);
						break;
					case "EVENT_NAME":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("V.NAME",$val, $match);
						break;
					case "EVENT1":
					case "EVENT2":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("V.".$key,$val, $match);
						break;
					case "EVENT3":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.EVENT3",$val, $match);
						break;
					case "DATE":
						if (CheckDateTime($val))
							$arSqlSearch[] = "E.DATE_ENTER=".$DB->CharToDateFunction($val);
						break;
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "E.DATE_ENTER>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "E.DATE_ENTER<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "REDIRECT_URL":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.REDIRECT_URL",$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "MONEY":
						$arSqlSearch_h[] = "MONEY='".roundDB($val)."'";
						break;
					case "MONEY1":
						$arSqlSearch_h[] = "MONEY>='".roundDB($val)."'";
						break;
					case "MONEY2":
						$arSqlSearch_h[] = "MONEY<='".roundDB($val)."'";
						break;
					case "SESSION_ID":
					case "GUEST_ID":
					case "ADV_ID":
					case "HIT_ID":
					case "COUNTRY_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("E.".$key,$val,$match);
						break;
					case "ADV_BACK":
						$arSqlSearch[] = ($val=="Y") ? "E.ADV_BACK='Y'" : "E.ADV_BACK='N'";
						break;
					case "REFERER_URL":
					case "URL":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.".$key,$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "COUNTRY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("C.NAME", $val, $match);
						break;
					case "SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("E.SITE_ID", $val, $match);
						break;
					case "REFERER_SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("E.REFERER_SITE_ID", $val, $match);
						break;
					case "CURRENCY":
						$CURRENCY = $val;
						break;
				}
			}
		}

		$rate = 1;
		$base_currency = GetStatisticBaseCurrency();
		$view_currency = $base_currency;
		if (strlen($base_currency)>0)
		{
			if (CModule::IncludeModule("currency"))
			{
				if ($CURRENCY!=$base_currency && strlen($CURRENCY)>0)
				{
					$rate = CCurrencyRates::GetConvertFactor($base_currency, $CURRENCY);
					$view_currency = $CURRENCY;
				}
			}
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		foreach($arSqlSearch_h as $sqlWhere)
			$strSqlSearch_h .= " and (".$sqlWhere.") ";

		if ($by == "s_id")					$strSqlOrder = "ORDER BY E.ID";
		elseif ($by == "s_site_id")			$strSqlOrder = "ORDER BY E.SITE_ID";
		elseif ($by == "s_event_id" || $by == "s_type_id")		$strSqlOrder = "ORDER BY E.EVENT_ID";
		elseif ($by == "s_event3")			$strSqlOrder = "ORDER BY E.EVENT3";
		elseif ($by == "s_date_enter")		$strSqlOrder = "ORDER BY E.DATE_ENTER";
		elseif ($by == "s_adv_id")			$strSqlOrder = "ORDER BY E.ADV_ID";
		elseif ($by == "s_adv_back")		$strSqlOrder = "ORDER BY E.ADV_BACK";
		elseif ($by == "s_session_id")		$strSqlOrder = "ORDER BY E.SESSION_ID";
		elseif ($by == "s_guest_id")		$strSqlOrder = "ORDER BY E.GUEST_ID";
		elseif ($by == "s_hit_id")			$strSqlOrder = "ORDER BY E.HIT_ID";
		elseif ($by == "s_url")				$strSqlOrder = "ORDER BY E.URL";
		elseif ($by == "s_referer_url")		$strSqlOrder = "ORDER BY E.REFERER_URL";
		elseif ($by == "s_redirect_url")	$strSqlOrder = "ORDER BY E.REDIRECT_URL";
		elseif ($by == "s_country_id")		$strSqlOrder = "ORDER BY E.COUNTRY_ID";
		elseif ($by == "s_money")			$strSqlOrder = "ORDER BY MONEY";
		else
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY E.ID";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		if($arFilter["GROUP"]=="total")
		{
			$strSql =	"
				SELECT
					COUNT(1)							COUNTER,
					round(sum(if(E.CHARGEBACK='Y',-E.MONEY,E.MONEY)*$rate),2)	MONEY,
					'".$DB->ForSql($view_currency)."'						CURRENCY
				FROM
					b_stat_event_list E
				INNER JOIN b_stat_event V ON (V.ID=E.EVENT_ID)
				LEFT JOIN b_stat_country C ON (C.ID=E.COUNTRY_ID)
				WHERE
				$strSqlSearch
				HAVING
					1=1
					$strSqlSearch_h
				";
		}
		else
		{
			$strSql =	"
				SELECT
					E.ID, E.EVENT3, E.EVENT_ID, E.ADV_ID, E.ADV_BACK, E.COUNTRY_ID, E.SESSION_ID, E.GUEST_ID, E.HIT_ID, E.REFERER_URL, E.URL, E.REDIRECT_URL, E.CHARGEBACK, E.SITE_ID, E.REFERER_SITE_ID,
					round((E.MONEY*$rate),2)										MONEY,
					'".$DB->ForSql($view_currency)."'												CURRENCY,
					".$DB->DateToCharFunction("E.DATE_ENTER")."						DATE_ENTER,
					V.ID															TYPE_ID,
					V.DESCRIPTION, V.NAME, V.EVENT1, V.EVENT2,
					C.NAME															COUNTRY_NAME,
					if (length(V.NAME)>0, V.NAME,
						concat(ifnull(V.EVENT1,''),' / ',ifnull(V.EVENT2,'')))		EVENT
				FROM
					b_stat_event_list E
				INNER JOIN b_stat_event V ON (V.ID=E.EVENT_ID)
				LEFT JOIN b_stat_country C ON (C.ID=E.COUNTRY_ID)
				WHERE
				$strSqlSearch
				HAVING
					1=1
					$strSqlSearch_h
				$strSqlOrder
				LIMIT ".intval(COption::GetOptionString('statistic','RECORDS_LIMIT'))."
				";
		}

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch) || strlen($strSqlSearch_h)>0);
		return $res;
	}


	/**
	* <p>Удаляет указанное <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событие</a>.</p>
	*
	*
	* @param int $event_id  ID удаляемого события. </htm
	*
	* @return bool <p>Метод возвращает "true" в случае успешного удаления и "false" в случае
	* неудачи.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $event_id = 1;
	* if (<b>CStatEvent::Delete</b>($event_id)) 
	*     echo "Событие #".$event_id." успешно удалено.";
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">Термин "Событие"</a>
	* </li></ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatevent/delete.php
	* @author Bitrix
	*/
	public static 	function Delete($ID)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$ID = intval($ID);
		$strSql = "
			SELECT
				L.EVENT_ID,
				L.MONEY,
				L.SESSION_ID,
				L.GUEST_ID,
				L.ADV_ID,
				L.ADV_BACK,
				L.COUNTRY_ID,
				L.CHARGEBACK,
				L.SITE_ID,
				".$DB->DateToCharFunction("L.DATE_ENTER","SHORT")."	DATE_ENTER,
				".$DB->DateToCharFunction("L.DATE_ENTER","FULL")."	DATE_ENTER_FULL
			FROM
				b_stat_event_list L,
				b_stat_event E
			WHERE
				L.ID = '$ID'
			and E.ID = L.EVENT_ID
			";
		$a = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($ar = $a->Fetch())
		{
			// уменьшаем счетчик у страны
			CStatistics::UpdateCountry($ar["COUNTRY_ID"], Array("C_EVENTS" => 1), $ar["DATE_ENTER"], "SHORT", "-");

			// уменьшаем счетчик по дням
			$arFields = Array(
				"COUNTER"	=> "COUNTER-1",
				"MONEY"		=> "MONEY - ".doubleval($ar["MONEY"])
				);
			$rows = $DB->Update("b_stat_event_day",$arFields,"WHERE EVENT_ID='".intval($ar["EVENT_ID"])."' and DATE_STAT = FROM_UNIXTIME('".MkDateTime(ConvertDateTime($ar["DATE_ENTER"],"D.M.Y"),"d.m.Y")."')",$err_mess.__LINE__);
			// если уже была свертка то
			if (intval($rows)<=0)
			{
				// уменьшим счетчик на типе события
				$arFields = Array(
					"COUNTER"	=> "COUNTER-1",
					"MONEY"		=> "MONEY - ".doubleval($ar["MONEY"])
					);
				$DB->Update("b_stat_event",$arFields,"WHERE ID='".intval($ar["EVENT_ID"])."'",$err_mess.__LINE__);
			}
			// если в связке есть нулевые значения то ее можно удалить
			$strSql = "DELETE FROM b_stat_event_day WHERE COUNTER=0";
			$DB->Query($strSql,false,$err_mess.__LINE__);

			// чистим сессию
			$arFields = Array("C_EVENTS" => "C_EVENTS-1");
			$DB->Update("b_stat_session",$arFields,"WHERE ID='".intval($ar["SESSION_ID"])."'",$err_mess.__LINE__,false,false,false);

			// чистим гостя
			$DB->Update("b_stat_guest",$arFields,"WHERE ID='".intval($ar["GUEST_ID"])."'",$err_mess.__LINE__,false,false,false);

			if (intval($ar["ADV_ID"])>0)
			{
				// изменяем доход рекламной кампании
				if (doubleval($ar["MONEY"])!=0)
				{
					$sign = ($ar["CHARGEBACK"]=="Y") ? "+" : "-";
					$arFields = array("REVENUE" => "REVENUE ".$sign." ".doubleval($ar["MONEY"]));
					$DB->Update("b_stat_adv",$arFields,"WHERE ID='".intval($ar["ADV_ID"])."'", $err_mess.__LINE__,false,false,false);
				}

				// чистим связку с рекламной кампанией
				if ($ar["ADV_BACK"]=="Y")
				{
					$arFields = array(
						"COUNTER_BACK"	=> "COUNTER_BACK - 1",
						"MONEY_BACK"	=> "MONEY_BACK - ".doubleval($ar["MONEY"]),
						);
				}
				else
				{
					$arFields = array(
						"COUNTER"	=> "COUNTER - 1",
						"MONEY"		=> "MONEY - ".doubleval($ar["MONEY"]),
						);
				}
				$DB->Update("b_stat_adv_event",$arFields,"WHERE ADV_ID='".intval($ar["ADV_ID"])."' and EVENT_ID='".$ar["EVENT_ID"]."'",$err_mess.__LINE__);

				// чистим связку с рекламной кампанией по дням
				if ($ar["ADV_BACK"]=="Y")
				{
					$arFields = array(
						"COUNTER_BACK"	=> "COUNTER_BACK - 1",
						"MONEY_BACK"	=> "MONEY_BACK - ".doubleval($ar["MONEY"]),
						);
				}
				else
				{
					$arFields = array(
						"COUNTER"	=> "COUNTER - 1",
						"MONEY"		=> "MONEY - ".doubleval($ar["MONEY"]),
						);
				}
				$DB->Update("b_stat_adv_event_day",$arFields,"WHERE ADV_ID='".intval($ar["ADV_ID"])."' and EVENT_ID='".$ar["EVENT_ID"]."' and DATE_STAT = FROM_UNIXTIME('".MkDateTime(ConvertDateTime($ar["DATE_ENTER"],"D.M.Y"),"d.m.Y")."')",$err_mess.__LINE__,false,false,false);
			}
			// если в связках остались нулевые значения то их можно удалить
			$strSql = "DELETE FROM b_stat_adv_event WHERE COUNTER<=0 and COUNTER_BACK<=0";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			$strSql = "DELETE FROM b_stat_adv_event_day WHERE COUNTER<=0 and COUNTER_BACK<=0";
			$DB->Query($strSql, false, $err_mess.__LINE__);

			// уменьшаем счетчик по дням
			$arFields = Array("C_EVENTS" => "C_EVENTS-1");
			$DB->Update("b_stat_day",$arFields,"WHERE DATE_STAT = FROM_UNIXTIME('".MkDateTime(ConvertDateTime($ar["DATE_ENTER"],"D.M.Y"),"d.m.Y")."')", $err_mess.__LINE__);

			// уменьшаем счетчик траффика
			CTraffic::DecParam(array("EVENT" => 1), array(), false, $ar["DATE_ENTER_FULL"]);

			if (strlen($ar["SITE_ID"])>0)
			{
				$arFields = Array("C_EVENTS" => "C_EVENTS-1");
				$DB->Update("b_stat_day_site",$arFields,"WHERE SITE_ID = '".$DB->ForSql($ar["SITE_ID"], 2)."' and  DATE_STAT = FROM_UNIXTIME('".MkDateTime(ConvertDateTime($ar["DATE_ENTER"],"D.M.Y"),"d.m.Y")."')", $err_mess.__LINE__);

				// уменьшаем счетчик траффика
				CTraffic::DecParam(array(), array("EVENT" => 1), $ar["SITE_ID"], $ar["DATE_ENTER_FULL"]);
			}

			$strSql = "DELETE FROM b_stat_event_list WHERE ID='$ID'";
			$DB->Query($strSql, false, $err_mess.__LINE__);

			return true;
		}
		return false;
	}
}
?>
