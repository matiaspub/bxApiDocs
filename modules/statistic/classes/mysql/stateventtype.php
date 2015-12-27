<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/classes/general/stateventtype.php");

/**
 * <b>CStatEventType</b> - класс для работы с <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">типами событий</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstateventtype/index.php
 * @author Bitrix
 */
class CStatEventType extends CAllStatEventType
{
	
	/**
	* <p>Возвращает данные по указанному <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">типу события</a>.</p>
	*
	*
	* @param int $type_id  ID типа события.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $type_id = 1;
	* if ($rs = <b>CStatEventType::GetByID</b>($type_id))
	* {
	*     $ar = $rs-&gt;Fetch();
	*     // выведем параметры типа события
	*     echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstateventtype/getbyevents.php">CStatEventType::GetByEvents</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstateventtype/conditionset.php">CStatEventType::ConditionSet</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">Термин "Тип
	* события"</a> </li> </ul> </ht<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstateventtype/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$ID = intval($ID);
		$strSql =	"
			SELECT
				E.*,
				".$DB->DateToCharFunction("E.DATE_ENTER")."		DATE_ENTER,
				if (length(E.NAME)>0, E.NAME,
					concat(ifnull(E.EVENT1,''),' / ',ifnull(E.EVENT2,''))) EVENT
			FROM
				b_stat_event E
			WHERE
				E.ID = '$ID'
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	
	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">типов событий</a>.</p>
	*
	*
	* @param string &$by = "s_today_counter" Поле для сортировки. Возможные значения: <ul> <li> <b>s_id</b> - ID типа
	* события; </li> <li> <b>s_date_last</b> - дата последнего <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">события</a> данного типа; </li> <li>
	* <b>s_date_enter</b> - дата первого события данного типа; </li> <li> <b>s_today_counter</b>
	* - количество событий данного типа за сегодня; </li> <li> <b>s_yesterday_counter</b>
	* - количество событий данного типа за вчера; </li> <li> <b>s_b_yesterday_counter</b> -
	* количество событий данного типа за позавчера; </li> <li> <b>s_total_counter</b> -
	* суммарное количество событий данного типа; </li> <li> <b>s_period_counter</b> -
	* количество событий данного типа за указанный период
	* <nobr>(<i>filter</i>[<b>DATE1_PERIOD</b>], <i>filter</i>[<b>DATE2_PERIOD</b>])</nobr>; </li> <li> <b>s_name</b> -
	* название типа события; </li> <li> <b>s_event1</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type_id">идентификатор event1</a> типа
	* события; </li> <li> <b>s_event2</b> - идентификатор event2 типа события; </li> <li>
	* <b>s_event12</b> - сортировка по "EVENT1, EVENT2"; </li> <li> <b>s_chart</b> - сортировка по
	* "DIAGRAM_DEFAULT desc, TOTAL_COUNTER"; </li> <li> <b>s_stat</b> - сортировка по "TODAY_COUNTER desc,
	* YESTERDAY_COUNTER desc, B_YESTERDAY_COUNTER desc, TOTAL_COUNTER desc, PERIOD_COUNTER". </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID типа события; </li> <li>
	* <b>ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по <b>ID</b>
	* будет искаться вхождение; </li> <li> <b>EVENT1</b>* - идентификатор event1 типа
	* события; </li> <li> <b>EVENT1_EXACT_MATCH</b> - если значение равно "Y", то при
	* фильтрации по <b>EVENT1</b> будет искаться точное совпадение; </li> <li>
	* <b>EVENT2</b>* - идентификатор event2 типа события; </li> <li> <b>EVENT2_EXACT_MATCH</b> -
	* если значение равно "Y", то при фильтрации по <b>EVENT2</b> будет
	* искаться точное совпадение; </li> <li> <b>NAME</b>* - название типа события;
	* </li> <li> <b>NAME_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>NAME</b> будет искаться точное совпадение; </li> <li> <b>DESCRIPTION</b>* -
	* описание типа события; </li> <li> <b>DESCRIPTION_EXACT_MATCH</b> - если значение
	* равно "Y", то при фильтрации по <b>DESCRIPTION</b> будет искаться точное
	* совпадение; </li> <li> <b>DATE_ENTER_1</b> - начальное значение интервала для
	* поля "дата первого события данного типа"; </li> <li> <b>DATE_ENTER_2</b> -
	* конечное значение интервала для поля "дата первого события
	* данного типа"; </li> <li> <b>DATE_LAST_1</b> - начальное значение интервала для
	* поля "дата последнего события данного типа"; </li> <li> <b>DATE_LAST_2</b> -
	* конечное значение интервала для поля "дата последнего события
	* данного типа"; </li> <li> <b>DATE1_PERIOD</b> - начальное значение значение для
	* произвольного периода; </li> <li> <b>DATE2_PERIOD</b> - конечное значение
	* значение для произвольного периода; </li> <li> <b>COUNTER1</b> - начальное
	* значение интервала для поля "суммарное количество событий
	* данного типа"; </li> <li> <b>COUNTER2</b> - конечное значение интервала для
	* поля "суммарное количество событий данного типа"; </li> <li> <b>ADV_VISIBLE</b>
	* - флаг включать ли статистику по данному типу события в отчет по
	* рекламным кампаниям, возможные значения: <ul> <li> <b>Y</b> - включать; </li>
	* <li> <b>N</b> - не включать. </li> </ul> </li> <li> <b>DIAGRAM_DEFAULT</b> - флаг включать ли
	* данный тип события в круговую диаграмму и график по умолчанию,
	* возможные значения: <ul> <li> <b>Y</b> - включать; </li> <li> <b>N</b> - не включать.
	* </li> </ul> </li> <li> <b>KEEP_DAYS1</b> - начальное значение интервала для поля
	* "количество дней отведенное для хранения событий данного типа";
	* </li> <li> <b>KEEP_DAYS2</b> - конечное значение интервала для поля
	* "количество дней отведенное для хранения событий данного типа";
	* </li> <li> <b>DYNAMIC_KEEP_DAYS1</b> - начальное значение интервала для поля
	* "количество дней отведенное для хранения статистики по данному
	* типу события в разрезе по дням"; </li> <li> <b>DYNAMIC_KEEP_DAYS2</b> - конечное
	* значение интервала для поля "количество дней отведенное для
	* хранения статистики по данному типу события в разрезе по дням";
	* </li> <li> <b>MONEY1</b> - начальное значение интервала для поля "суммарная
	* денежная сумма для данного типа событий"; </li> <li> <b>MONEY2</b> - конечное
	* значение интервала для поля "суммарная денежная сумма для
	* данного типа событий"; </li> <li> <b>CURRENCY</b> - трехсимвольный
	* идентификатор валюты для денежной суммы; </li> <li> <b>GROUP</b> -
	* группировка результирующего списка, возможные значения: <ul> <li>
	* <b>event1</b> - группировка по <i>event1</i>; </li> <li> <b>event2</b> - группировка по
	* <i>event2</i>. </li> </ul> </li> </ul> * - допускается <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности результирующего списка. Если значение
	* равно "true", то список был отфильтрован.
	*
	* @param mixed $limit = false Максимальное число типов событий которые будут выбраны в списке.
	* Если значение равно "false", то кол-во РК будет ограничено в
	* соответствии со значением параметра <b>Максимальное кол-во
	* показываемых записей в таблицах</b> из настроек модуля
	* "Статистика".
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // получим данные только по тем типам событий 
	* // у которых event1 = "download"
	* // а также получим дополнительные данные на декабрь 2007 года
	* $arFilter = array(
	*     "DATE1_PERIOD" =&gt; "01.12.2007",
	*     "DATE2_PERIOD" =&gt; "31.12.2007",
	*     "EVENT1"       =&gt; "download"
	*     );
	* 
	* // получим список записей
	* $rs = <b>CStatEventType::GetList</b>(
	*     ($by = "s_today_counter"), 
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
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstateventtype/getdynamiclist.php">CStatEventType::GetDynamicList</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstateventtype/getsimplelist.php">CStatEventType::GetSimpleList</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">Термин "Тип
	* события"</a> </li> </ul> </ht<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstateventtype/getlist.php
	* @author Bitrix
	*/
	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered, $LIMIT=false)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');

		$find_group = $arFilter["GROUP"];
		if($find_group!="event1" && $find_group!="event2" && $find_group!="total")
			$find_group="";

		$arSqlSearch = Array();
		$arSqlSearch_h = Array();
		$strSqlSearch_h = "";
		$filter_period = false;
		$strSqlPeriod = "";
		$strT = "";
		$CURRENCY = "";

		if (is_array($arFilter))
		{
			ResetFilterLogic();
			$date1 = $arFilter["DATE1_PERIOD"];
			$date2 = $arFilter["DATE2_PERIOD"];
			$date_from = MkDateTime(ConvertDateTime($date1,"D.M.Y"),"d.m.Y");
			$date_to = MkDateTime(ConvertDateTime($date2,"D.M.Y")." 23:59","d.m.Y H:i");
			if (strlen($date1)>0)
			{
				$filter_period = true;
				if (strlen($date2)>0)
				{
					$strSqlPeriod = "if(D.DATE_STAT<FROM_UNIXTIME('$date_from'),0, if(D.DATE_STAT>FROM_UNIXTIME('$date_to'),0,";
					$strT="))";
				}
				else
				{
					$strSqlPeriod = "if(D.DATE_STAT<FROM_UNIXTIME('$date_from'),0,";
					$strT=")";
				}
			}
			elseif (strlen($date2)>0)
			{
				$filter_period = true;
				$strSqlPeriod = "if(D.DATE_STAT>FROM_UNIXTIME('$date_to'),0,";
				$strT=")";
			}

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
						$arSqlSearch[] = GetFilterQuery("E.".$key,$val,$match);
						break;
					case "DATE_ENTER_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "E.DATE_ENTER>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_ENTER_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "E.DATE_ENTER<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "DATE_LAST_1":
						if (CheckDateTime($val))
							$arSqlSearch_h[] = "max(D.DATE_LAST)>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_LAST_2":
						if (CheckDateTime($val))
							$arSqlSearch_h[] = "max(D.DATE_LAST)<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "EVENT1":
					case "EVENT2":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.".$key,$val,$match);
						break;
					case "COUNTER1":
						$arSqlSearch_h[] = "TOTAL_COUNTER>='".intval($val)."'";
						break;
					case "COUNTER2":
						$arSqlSearch_h[] = "TOTAL_COUNTER<='".intval($val)."'";
						break;
					case "MONEY1":
						$arSqlSearch_h[] = "TOTAL_MONEY>='".roundDB($val)."'";
						break;
					case "MONEY2":
						$arSqlSearch_h[] = "TOTAL_MONEY<='".roundDB($val)."'";
						break;
					case "ADV_VISIBLE":
					case "DIAGRAM_DEFAULT":
						$arSqlSearch[] = ($val=="Y") ? "E.".$key."='Y'" : "E.".$key."='N'";
						break;
					case "DESCRIPTION":
					case "NAME":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.".$key,$val,$match);
						break;
					case "KEEP_DAYS1":
						$arSqlSearch[] = "E.KEEP_DAYS>='".intval($val)."'";
						break;
					case "KEEP_DAYS2":
						$arSqlSearch[] = "E.KEEP_DAYS<='".intval($val)."'";
						break;
					case "DYNAMIC_KEEP_DAYS1":
						$arSqlSearch[] = "E.DYNAMIC_KEEP_DAYS>='".intval($val)."'";
						break;
					case "DYNAMIC_KEEP_DAYS2":
						$arSqlSearch[] = "E.DYNAMIC_KEEP_DAYS<='".intval($val)."'";
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

		if ($by == "s_id" && $find_group=="")			$strSqlOrder = "ORDER BY E.ID";
		elseif ($by == "s_date_last")				$strSqlOrder = "ORDER BY E_DATE_LAST";
		elseif ($by == "s_date_enter")				$strSqlOrder = "ORDER BY DATE_ENTER";
		elseif ($by == "s_today_counter")			$strSqlOrder = "ORDER BY TODAY_COUNTER";
		elseif ($by == "s_yesterday_counter")			$strSqlOrder = "ORDER BY YESTERDAY_COUNTER";
		elseif ($by == "s_b_yesterday_counter")			$strSqlOrder = "ORDER BY B_YESTERDAY_COUNTER";
		elseif ($by == "s_total_counter")			$strSqlOrder = "ORDER BY TOTAL_COUNTER";
		elseif ($by == "s_period_counter")			$strSqlOrder = "ORDER BY PERIOD_COUNTER";
		elseif ($by == "s_name" && $find_group=="")		$strSqlOrder = "ORDER BY E.NAME";
		elseif ($by == "s_event1" && $find_group=="")		$strSqlOrder = "ORDER BY E.EVENT1";
		elseif ($by == "s_event1" && $find_group=="event1")	$strSqlOrder = "ORDER BY E.EVENT1";
		elseif ($by == "s_event2" && $find_group=="")		$strSqlOrder = "ORDER BY E.EVENT2";
		elseif ($by == "s_event2" && $find_group=="event2")	$strSqlOrder = "ORDER BY E.EVENT2";
		elseif ($by == "s_event12" && $find_group=="")		$strSqlOrder = "ORDER BY E.EVENT1, E.EVENT2";
		elseif ($by == "s_chart" && $find_group=="")		$strSqlOrder = "ORDER BY E.DIAGRAM_DEFAULT desc, TOTAL_COUNTER ";
		elseif ($by == "s_stat")				$strSqlOrder = "ORDER BY TODAY_COUNTER desc, YESTERDAY_COUNTER desc, B_YESTERDAY_COUNTER desc, TOTAL_COUNTER desc, PERIOD_COUNTER";
		else
		{
			$by = "s_today_counter";
			$strSqlOrder = "ORDER BY TODAY_COUNTER desc, YESTERDAY_COUNTER desc, B_YESTERDAY_COUNTER desc, TOTAL_COUNTER desc, PERIOD_COUNTER";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		foreach($arSqlSearch_h as $sqlWhere)
			$strSqlSearch_h .= " and (".$sqlWhere.") ";

		$limit_sql = "LIMIT ".intval(COption::GetOptionString('statistic','RECORDS_LIMIT'));
		if (intval($LIMIT)>0) $limit_sql = "LIMIT ".intval($LIMIT);
		if ($find_group=="") // если группировка не выбрана
		{
			$strSql = "
			SELECT
				E.ID,
				E.EVENT1,
				E.EVENT2,
				E.COUNTER,
				E.DIAGRAM_DEFAULT,
				'".$DB->ForSql($view_currency)."'									CURRENCY,
				".$DB->DateToCharFunction("E.DATE_ENTER")."						DATE_ENTER,
				".$DB->DateToCharFunction("max(D.DATE_LAST)")."						DATE_LAST,
				max(ifnull(D.DATE_LAST,'1980-01-01'))							E_DATE_LAST,
				sum(ifnull(D.COUNTER,0))+ifnull(E.COUNTER,0)						TOTAL_COUNTER,
				sum(round(ifnull(D.MONEY,0)*$rate,2))+round(ifnull(E.MONEY,0)*$rate,2)			TOTAL_MONEY,
				sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.COUNTER,0),0))			TODAY_COUNTER,
				sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.COUNTER,0),0))		YESTERDAY_COUNTER,
				sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.COUNTER,0),0))		B_YESTERDAY_COUNTER,
				sum(".($filter_period ? $strSqlPeriod.'ifnull(D.COUNTER,0)'.$strT : 0).")		PERIOD_COUNTER,
				sum(round(if(to_days(curdate())-to_days(D.DATE_STAT)=0,ifnull(D.MONEY,0),0)*$rate,2))	TODAY_MONEY,
				sum(round(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.MONEY,0),0)*$rate,2))	YESTERDAY_MONEY,
				sum(round(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.MONEY,0),0)*$rate,2))	B_YESTERDAY_MONEY,
				sum(round(".($filter_period ? $strSqlPeriod.'ifnull(D.MONEY,0)'.$strT : 0)."*$rate,2))	PERIOD_MONEY,
				E.NAME,
				E.DESCRIPTION,
				if (length(E.NAME)>0, E.NAME, concat(E.EVENT1,' / ',ifnull(E.EVENT2,'')))		EVENT
			FROM
				b_stat_event E
			LEFT JOIN b_stat_event_day D ON (D.EVENT_ID = E.ID)
			WHERE
			$strSqlSearch
			GROUP BY E.ID
			HAVING
				'1'='1'
				$strSqlSearch_h
			$strSqlOrder
			$limit_sql
			";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		}
		elseif ($find_group=="total")
		{
			$arResult = array(
				"TOTAL_COUNTER"		=>0,
				"TOTAL_MONEY"		=>0,
				"TODAY_COUNTER"		=>0,
				"YESTERDAY_COUNTER" 	=>0,
				"B_YESTERDAY_COUNTER"	=>0,
				"PERIOD_COUNTER"	=>0,
				"TODAY_MONEY"		=>0,
				"YESTERDAY_MONEY" 	=>0,
				"B_YESTERDAY_MONEY"	=>0,
				"PERIOD_MONEY"		=>0,
			);
			$strSql = "
			SELECT
				sum(ifnull(D.COUNTER,0))								TOTAL_COUNTER,
				sum(round(ifnull(D.MONEY,0)*$rate,2))							TOTAL_MONEY,
				sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.COUNTER,0),0))			TODAY_COUNTER,
				sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.COUNTER,0),0))		YESTERDAY_COUNTER,
				sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.COUNTER,0),0))		B_YESTERDAY_COUNTER,
				sum(".($filter_period ? $strSqlPeriod.'ifnull(D.COUNTER,0)'.$strT : 0).")		PERIOD_COUNTER,
				sum(round(if(to_days(curdate())-to_days(D.DATE_STAT)=0,ifnull(D.MONEY,0),0)*$rate,2))	TODAY_MONEY,
				sum(round(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.MONEY,0),0)*$rate,2))	YESTERDAY_MONEY,
				sum(round(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.MONEY,0),0)*$rate,2))	B_YESTERDAY_MONEY,
				sum(round(".($filter_period ? $strSqlPeriod.'ifnull(D.MONEY,0)'.$strT : 0)."*$rate,2))	PERIOD_MONEY
			FROM
				b_stat_event E
				LEFT JOIN b_stat_event_day D ON (D.EVENT_ID = E.ID)
			WHERE
				$strSqlSearch
			HAVING
				'1'='1'
				$strSqlSearch_h
			";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			if($ar = $res->Fetch())
				foreach($ar as $k=>$v)
					$arResult[$k]+=$v;
			$strSql = "
			SELECT
				sum(ifnull(E.COUNTER,0))		TOTAL_COUNTER,
				sum(round(ifnull(E.MONEY,0)*$rate,2))	TOTAL_MONEY
			FROM
				b_stat_event E
			WHERE
				$strSqlSearch
			";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			if($ar = $res->Fetch())
				foreach($ar as $k=>$v)
					$arResult[$k]+=$v;
			$arResult["CURRENCY"]=$view_currency;
			$res = new CDBResult;
			$res->InitFromArray(array($arResult));
		}
		else
		{
			$arResult = array();
			if ($find_group=="event1") $group = "E.EVENT1"; else $group = "E.EVENT2";
			$strSql = "
			SELECT
				$group											GROUPING_KEY,
				$group,
				'".$DB->ForSql($view_currency)."'									CURRENCY,
				".$DB->DateToCharFunction("min(E.DATE_ENTER)")."					DATE_ENTER,
				".$DB->DateToCharFunction("max(D.DATE_LAST)")."						DATE_LAST,
				max(ifnull(D.DATE_LAST,'1980-01-01'))							E_DATE_LAST,
				sum(ifnull(D.COUNTER,0))								TOTAL_COUNTER,
				sum(round(ifnull(D.MONEY,0)*$rate,2))							TOTAL_MONEY,
				sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.COUNTER,0),0))			TODAY_COUNTER,
				sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.COUNTER,0),0))		YESTERDAY_COUNTER,
				sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.COUNTER,0),0))		B_YESTERDAY_COUNTER,
				sum(".($filter_period ? $strSqlPeriod.'ifnull(D.COUNTER,0)'.$strT : 0).")		PERIOD_COUNTER,
				sum(round(if(to_days(curdate())-to_days(D.DATE_STAT)=0,ifnull(D.MONEY,0),0)*$rate,2))	TODAY_MONEY,
				sum(round(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.MONEY,0),0)*$rate,2))	YESTERDAY_MONEY,
				sum(round(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.MONEY,0),0)*$rate,2))	B_YESTERDAY_MONEY,
				sum(round(".($filter_period ? $strSqlPeriod.'ifnull(D.MONEY,0)'.$strT : 0)."*$rate,2))	PERIOD_MONEY
			FROM
				b_stat_event E
			LEFT JOIN b_stat_event_day D ON (D.EVENT_ID = E.ID)
			WHERE
			$strSqlSearch
			GROUP BY $group
			HAVING
				'1'='1'
				$strSqlSearch_h
			$strSqlOrder
			";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			while($ar=$res->Fetch())
				$arResult[$ar["GROUPING_KEY"]] = $ar;
			$strSql = "
			SELECT
				$group							GROUPING_KEY,
				'".$DB->ForSql($view_currency)."'					CURRENCY,
				sum(ifnull(E.COUNTER,0))				COUNTER,
				sum(round(ifnull(E.MONEY,0)*$rate,2))			TOTAL_MONEY,
				".$DB->DateToCharFunction("min(E.DATE_ENTER)")."	DATE_ENTER
			FROM
				b_stat_event E
			WHERE
			$strSqlSearch
			GROUP BY $group
			";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			while($ar=$res->Fetch())
			{
				if(array_key_exists($ar["GROUPING_KEY"], $arResult))
				{
					$arResult[$ar["GROUPING_KEY"]]["TOTAL_COUNTER"] += $ar["COUNTER"];
					$arResult[$ar["GROUPING_KEY"]]["TOTAL_MONEY"] += $ar["MONEY"];
				}
				else
				{
					$arResult[$ar["GROUPING_KEY"]] = array(
						"GROUPING_KEY"			=>$ar["GROUPING_KEY"],
						($find_group=="event1"?"EVENT1":"EVENT2")=>$ar["GROUPING_KEY"],
						"CURRENCY"		=>$ar["CURRENCY"],
						"DATE_ENTER"		=>$ar["DATE_ENTER"],
						"TOTAL_COUNTER"		=>$ar["COUNTER"],
						"TOTAL_MONEY"		=>$ar["MONEY"],
						"TODAY_COUNTER"		=>0,
						"YESTERDAY_COUNTER" 	=>0,
						"B_YESTERDAY_COUNTER"	=>0,
						"PERIOD_COUNTER"	=>0,
						"TODAY_MONEY"		=>0,
						"YESTERDAY_MONEY" 	=>0,
						"B_YESTERDAY_MONEY"	=>0,
						"PERIOD_MONEY"		=>0,
					);/*DATE_LAST,E_DATE_LAST,*/
				}
			}
			$res = new CDBResult;
			$res->InitFromArray($arResult);
		}
		$is_filtered = (IsFiltered($strSqlSearch) || $filter_period || strlen($strSqlSearch_h)>0 || $find_group!="");
		return $res;
	}


	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">типов событий</a> в упрощённом виде.</p>
	*
	*
	* @param string &$by = "s_event1" Поле для сортировки. Возможные значения: <ul> <li> <b>s_id</b> - ID типа
	* события; </li> <li> <b>s_event1</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type_id">идентификатор event1</a> типа
	* события; </li> <li> <b>s_event2</b> - идентификатор event2 типа события; </li> <li>
	* <b>s_name</b> - название типа события; </li> <li> <b>s_description</b> - описание типа
	* события. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID типа события; </li> <li>
	* <b>ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по <b>ID</b>
	* будет искаться вхождение; </li> <li> <b>EVENT1</b>* - идентификатор event1 типа
	* события; </li> <li> <b>EVENT1_EXACT_MATCH</b> - если значение равно "Y", то при
	* фильтрации по <b>EVENT1</b> будет искаться точное совпадение; </li> <li>
	* <b>EVENT2</b>* - идентификатор event2 типа события; </li> <li> <b>EVENT2_EXACT_MATCH</b> -
	* если значение равно "Y", то при фильтрации по <b>EVENT2</b> будет
	* искаться точное совпадение; </li> <li> <b>NAME</b>* - название типа события;
	* </li> <li> <b>NAME_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>NAME</b> будет искаться точное совпадение; </li> <li> <b>DESCRIPTION</b>* -
	* описание типа события; </li> <li> <b>DESCRIPTION_EXACT_MATCH</b> - если значение
	* равно "Y", то при фильтрации по <b>DESCRIPTION</b> будет искаться точное
	* совпадение; </li> <li> <b>KEYWORDS</b> - event1, event2, название и описание типа
	* события; </li> <li> <b>KEYWORDS_EXACT_MATCH</b> - если значение равно "Y", то при
	* фильтрации по <b>KEYWORDS</b> будет искаться точное совпадение. </li> </ul> * -
	* допускается <a href="http://www.1c-bitrix.ru/user_help/general/filter.php">сложная логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности результирующего списка. Если значение
	* равно "true", то список был отфильтрован.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // выберем только те типы событий у которых в event1 входит "download"
	* $arFilter = array(
	*     "EVENT1" =&gt; "download"
	*     );
	* 
	* // получим список записей
	* $rs = <b>CStatEventType::GetSimpleList</b>(
	*     ($by="s_event2"), 
	*     ($order="desc"), 
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
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstateventtype/getdynamiclist.php">CStatEventType::GetDynamicList</a>
	* </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstateventtype/getlist.php">CStatEventType::GetList</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">Термин "Тип события"</a>
	* </li> </ul> </ht<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstateventtype/getsimplelist.php
	* @author Bitrix
	*/
	public static 	function GetSimpleList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();

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
						$arSqlSearch[] = GetFilterQuery("E.".$key, $val, $match);
						break;
					case "EVENT1":
					case "EVENT2":
					case "NAME":
					case "DESCRIPTION":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.".$key, $val, $match);
						break;
					case "KEYWORDS":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.EVENT1, E.EVENT2, E.DESCRIPTION, E.NAME",$val, $match);
						break;
				}
			}
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$order= ($order!="desc") ? "asc" : "desc";

		if ($by == "s_id")
			$strSqlOrder = "ORDER BY E.ID ".$order;
		elseif ($by == "s_event1")
			$strSqlOrder = "ORDER BY E.EVENT1 ".$order.", E.EVENT2";
		elseif ($by == "s_event2")
			$strSqlOrder = "ORDER BY E.EVENT2 ".$order;
		elseif ($by == "s_name")
			$strSqlOrder = "ORDER BY E.NAME ".$order;
		elseif ($by == "s_description")
			$strSqlOrder = "ORDER BY E.DESCRIPTION ".$order;
		else
		{
			$by = "s_event1";
			$strSqlOrder = "ORDER BY E.EVENT1 ".$order.", E.EVENT2";
		}

		$strSql =	"
			SELECT
				E.ID, E.EVENT1, E.EVENT2, E.NAME, E.DESCRIPTION,
				if (length(E.NAME)>0, E.NAME,
					concat(ifnull(E.EVENT1,''),' / ',ifnull(E.EVENT2,'')))		EVENT
			FROM
				b_stat_event E
			WHERE
			$strSqlSearch
			$strSqlOrder
			LIMIT ".intval(COption::GetOptionString('statistic','RECORDS_LIMIT'))."
		";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}

public static 	function GetDropDownList($strSqlOrder="ORDER BY EVENT1, EVENT2")
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$strSql = "
			SELECT
				ID as REFERENCE_ID,
				concat('(',ifnull(EVENT1,''),' / ',ifnull(EVENT2,''),')',ifnull(NAME,''),' [',ID,']') as REFERENCE
			FROM
				b_stat_event
			$strSqlOrder
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}


	/**
	* <p>Возвращает количество <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событий</a> указанного <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event_type">типа</a> в разрезе по дням.</p>
	*
	*
	* @param int $type_id  ID типа события.
	*
	* @param string &$by = "s_date" Поле для сортировки. Возможные значения: <ul><li> <b>s_date</b> - дата. </li></ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $max_min  Ссылка на массив содержащий максимальную и минимальную даты
	* результирующего списка. Структура данного массива: <pre
	* style="font-size:95%"> Array ( [DATE_FIRST] =&gt; минимальная дата [MIN_DAY] =&gt; день
	* минимальной даты (1-31) [MIN_MONTH] =&gt; месяц минимальной даты (1-12) [MIN_YEAR]
	* =&gt; год минимальной даты [DATE_LAST] =&gt; максимальная дата [MAX_DAY] =&gt;
	* день максимальной даты (1-31) [MAX_MONTH] =&gt; месяц максимальной даты (1-12)
	* [MAX_YEAR] =&gt; год максимальной даты )</pre>
	*
	* @param array $filter  Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>DATE1</b> - начальное значение
	* интервала для поля "дата"; </li> <li> <b>DATE2</b> - конечное значение
	* интервала для поля "дата". </li> </ul>
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $type_id = 1;
	* 
	* // установим фильтр на декабрь 2007 года
	* $arFilter = array(
	*     "DATE1" =&gt; "01.12.2007",
	*     "DATE2" =&gt; "31.12.2007"
	*     );
	* 
	* // получим набор записей
	* $rs = <b>CStatEventType::GetDynamicList</b>(
	*     $type_id, 
	*     ($by="s_date"), 
	*     ($order="desc"), 
	*     $arMaxMin, 
	*     $arFilter
	*     );
	* 
	* // выведем массив с максимальной и минимальной датами
	* echo "&lt;pre&gt;"; print_r($arMaxMin); echo "&lt;/pre&gt;";    
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
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstateventtype/getlist.php">CStatEventType::GetList</a> </li>
	* <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cstateventtype/getsimplelist.php">CStatEventType::GetSimpleList</a>
	* </li> </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstateventtype/getdynamiclist.php
	* @author Bitrix
	*/
	public static 	function GetDynamicList($EVENT_ID, &$by, &$order, &$arMaxMin, $arFilter=Array())
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$EVENT_ID = intval($EVENT_ID);
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

				$key = strtoupper($key);
				switch($key)
				{
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
				}
			}
		}

		foreach($arSqlSearch as $sqlWhere)
			$strSqlSearch .= " and (".$sqlWhere.") ";

		if ($by == "s_date")
			$strSqlOrder = "ORDER BY D.DATE_STAT";
		else
		{
			$by = "s_date";
			$strSqlOrder = "ORDER BY D.DATE_STAT";
		}

		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order = "desc";
		}

		$strSql = "
			SELECT
				".$DB->DateToCharFunction("D.DATE_STAT","SHORT")." DATE_STAT,
				DAYOFMONTH(D.DATE_STAT) DAY,
				MONTH(D.DATE_STAT) MONTH,
				YEAR(D.DATE_STAT) YEAR,
				D.COUNTER
			FROM
				b_stat_event_day D
			WHERE
				D.EVENT_ID = $EVENT_ID
			$strSqlSearch
			$strSqlOrder
		";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		$strSql = "
			SELECT
				max(D.DATE_STAT) DATE_LAST,
				min(D.DATE_STAT) DATE_FIRST,
				DAYOFMONTH(max(D.DATE_STAT)) MAX_DAY,
				MONTH(max(D.DATE_STAT)) MAX_MONTH,
				YEAR(max(D.DATE_STAT)) MAX_YEAR,
				DAYOFMONTH(min(D.DATE_STAT)) MIN_DAY,
				MONTH(min(D.DATE_STAT)) MIN_MONTH,
				YEAR(min(D.DATE_STAT)) MIN_YEAR
			FROM
				b_stat_event_day D
			WHERE
				D.EVENT_ID = $EVENT_ID
			$strSqlSearch
			";
		$a = $DB->Query($strSql, false, $err_mess.__LINE__);
		$ar = $a->Fetch();
		$arMaxMin["MAX_DAY"]	= $ar["MAX_DAY"];
		$arMaxMin["MAX_MONTH"]	= $ar["MAX_MONTH"];
		$arMaxMin["MAX_YEAR"]	= $ar["MAX_YEAR"];
		$arMaxMin["MIN_DAY"]	= $ar["MIN_DAY"];
		$arMaxMin["MIN_MONTH"]	= $ar["MIN_MONTH"];
		$arMaxMin["MIN_YEAR"]	= $ar["MIN_YEAR"];
		return $res;
	}

public static 	function GetGraphArray_SQL($strSqlSearch)
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$strSql = "
			SELECT
				".$DB->DateToCharFunction("D.DATE_STAT","SHORT")." DATE_STAT,
				DAYOFMONTH(D.DATE_STAT) DAY,
				MONTH(D.DATE_STAT) MONTH,
				YEAR(D.DATE_STAT) YEAR,
				D.COUNTER,
				D.MONEY,
				D.EVENT_ID,
				E.NAME,
				E.EVENT1,
				E.EVENT2
			FROM
				b_stat_event_day D
			INNER JOIN b_stat_event E ON (E.ID = D.EVENT_ID)
			WHERE
				$strSqlSearch
			ORDER BY
				D.DATE_STAT, D.EVENT_ID
			";
		return $strSql;
	}
}
?>
