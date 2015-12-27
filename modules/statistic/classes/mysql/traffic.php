<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/classes/general/traffic.php");

/**
 * <b>CTraffic</b> - класс для получения общих данных по посещаемости сайта. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/ctraffic/index.php
 * @author Bitrix
 */
class CTraffic extends CAllTraffic
{
	
	/**
	* <p>Возвращает количество <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хитов</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#host">хостов</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессий</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителей</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событий</a> в разрезе по часам, дням недели и месяцам.</p>
	*
	*
	* @param string $data_type  Тип данных. Возможные значения: <ul> <li> <b>hour</b> - данные в разрезе по
	* часам; </li> <li> <b>weekday</b> - данные в разрезе по дням недели; </li> <li>
	* <b>month</b> - данные в разрезе по месяцам. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>SITE_ID</b> - ID сайта для которого
	* необходимо получить статистику по ссылающимся сайтам; </li> <li>
	* <b>DATE1</b> - начальная дата; </li> <li> <b>DATE2</b> - конечная дата. </li> </ul>
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // получим данные по посещаемости в разрезе по дням недели
	* $rs = <b>CTraffic::GetSumList</b>("weekday");
	* $ar = $rs-&gt;Fetch();
	* for ($i=0; $i&lt;=6; $i++)
	* {
	*     switch($i)
	*     {
	*         case 0: echo "воскресенье"; break;
	*         case 1: echo "понедельник"; break;
	*         case 2: echo "вторник"; break;
	*         case 3: echo "среда"; break;
	*         case 4: echo "четверг"; break;
	*         case 5: echo "пятница"; break;
	*         case 6: echo "суббота"; break;
	*     }
	*     echo "&lt;br&gt;";
	*     echo "Хостов: ".$ar["WEEKDAY_HOST_".$i]."&lt;br&gt;";
	*     echo "Сессий: ".$ar["WEEKDAY_SESSION_".$i]."&lt;br&gt;";
	*     echo "Хитов: ".$ar["WEEKDAY_HIT_".$i]."&lt;br&gt;";
	*     echo "Посетителей: ".$ar["WEEKDAY_GUEST_".$i]."&lt;br&gt;";
	*     echo "Новых посетителей: ".$ar["WEEKDAY_NEW_GUEST_".$i]."&lt;br&gt;";
	*     echo "&lt;br&gt;&lt;br&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://www.1c-bitrix.ru/user_help/statistic/site_traffic/traffic.php">Отчет
	* "Посещаемость"</a> </li> </ul> </ht<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/ctraffic/getsumlist.php
	* @author Bitrix
	*/
	public static function GetSumList($DATA_TYPE, $arFilter=Array())
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = array();
		$site_filtered = false;
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
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "DATE_STAT>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "DATE_STAT<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "SITE_ID":
						$site_filtered = true;
						if (is_array($val))
							$val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("SITE_ID", $val, $match);
						break;
				}
			}
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$table_name = ($site_filtered) ? "b_stat_day_site" : "b_stat_day";
		$arSelect1 = $arSelect2 = array();

		switch ($DATA_TYPE)
		{
			case "hour":
				$arSelect1 = array(
					"HOUR_HOST_0", "HOUR_HOST_1", "HOUR_HOST_2", "HOUR_HOST_3", "HOUR_HOST_4", "HOUR_HOST_5", "HOUR_HOST_6", "HOUR_HOST_7",	"HOUR_HOST_8", "HOUR_HOST_9", "HOUR_HOST_10", "HOUR_HOST_11", "HOUR_HOST_12", "HOUR_HOST_13", "HOUR_HOST_14", "HOUR_HOST_15", "HOUR_HOST_16", "HOUR_HOST_17", "HOUR_HOST_18", "HOUR_HOST_19", "HOUR_HOST_20", "HOUR_HOST_21", "HOUR_HOST_22", "HOUR_HOST_23",

					"HOUR_SESSION_0", "HOUR_SESSION_1", "HOUR_SESSION_2", "HOUR_SESSION_3", "HOUR_SESSION_4", "HOUR_SESSION_5", "HOUR_SESSION_6", "HOUR_SESSION_7", "HOUR_SESSION_8", "HOUR_SESSION_9", "HOUR_SESSION_10", "HOUR_SESSION_11", "HOUR_SESSION_12", "HOUR_SESSION_13", "HOUR_SESSION_14", "HOUR_SESSION_15", "HOUR_SESSION_16", "HOUR_SESSION_17", "HOUR_SESSION_18", "HOUR_SESSION_19", "HOUR_SESSION_20", "HOUR_SESSION_21", "HOUR_SESSION_22", "HOUR_SESSION_23",

					"HOUR_HIT_0", "HOUR_HIT_1", "HOUR_HIT_2", "HOUR_HIT_3", "HOUR_HIT_4", "HOUR_HIT_5", "HOUR_HIT_6", "HOUR_HIT_7", "HOUR_HIT_8", "HOUR_HIT_9", "HOUR_HIT_10", "HOUR_HIT_11", "HOUR_HIT_12", "HOUR_HIT_13", "HOUR_HIT_14", "HOUR_HIT_15", "HOUR_HIT_16", "HOUR_HIT_17", "HOUR_HIT_18", "HOUR_HIT_19", "HOUR_HIT_20", "HOUR_HIT_21", "HOUR_HIT_22", "HOUR_HIT_23",

					"HOUR_EVENT_0", "HOUR_EVENT_1", "HOUR_EVENT_2", "HOUR_EVENT_3", "HOUR_EVENT_4", "HOUR_EVENT_5", "HOUR_EVENT_6", "HOUR_EVENT_7", "HOUR_EVENT_8", "HOUR_EVENT_9", "HOUR_EVENT_10", "HOUR_EVENT_11", "HOUR_EVENT_12", "HOUR_EVENT_13", "HOUR_EVENT_14", "HOUR_EVENT_15", "HOUR_EVENT_16", "HOUR_EVENT_17", "HOUR_EVENT_18", "HOUR_EVENT_19", "HOUR_EVENT_20", "HOUR_EVENT_21", "HOUR_EVENT_22", "HOUR_EVENT_23"
					);
				if ($table_name=="b_stat_day")
				{
					$arSelect2 = array(
						"HOUR_GUEST_0", "HOUR_GUEST_1", "HOUR_GUEST_2", "HOUR_GUEST_3", "HOUR_GUEST_4", "HOUR_GUEST_5", "HOUR_GUEST_6", "HOUR_GUEST_7", "HOUR_GUEST_8", "HOUR_GUEST_9", "HOUR_GUEST_10", "HOUR_GUEST_11", "HOUR_GUEST_12", "HOUR_GUEST_13", "HOUR_GUEST_14", "HOUR_GUEST_15", "HOUR_GUEST_16", "HOUR_GUEST_17", "HOUR_GUEST_18", "HOUR_GUEST_19", "HOUR_GUEST_20", "HOUR_GUEST_21", "HOUR_GUEST_22", "HOUR_GUEST_23",

						"HOUR_NEW_GUEST_0", "HOUR_NEW_GUEST_1", "HOUR_NEW_GUEST_2", "HOUR_NEW_GUEST_3", "HOUR_NEW_GUEST_4", "HOUR_NEW_GUEST_5", "HOUR_NEW_GUEST_6", "HOUR_NEW_GUEST_7", "HOUR_NEW_GUEST_8", "HOUR_NEW_GUEST_9", "HOUR_NEW_GUEST_10", "HOUR_NEW_GUEST_11", "HOUR_NEW_GUEST_12", "HOUR_NEW_GUEST_13", "HOUR_NEW_GUEST_14", "HOUR_NEW_GUEST_15", "HOUR_NEW_GUEST_16", "HOUR_NEW_GUEST_17", "HOUR_NEW_GUEST_18", "HOUR_NEW_GUEST_19", "HOUR_NEW_GUEST_20", "HOUR_NEW_GUEST_21", "HOUR_NEW_GUEST_22", "HOUR_NEW_GUEST_23",

						"HOUR_FAVORITE_0", "HOUR_FAVORITE_1", "HOUR_FAVORITE_2", "HOUR_FAVORITE_3", "HOUR_FAVORITE_4", "HOUR_FAVORITE_5", "HOUR_FAVORITE_6", "HOUR_FAVORITE_7", "HOUR_FAVORITE_8", "HOUR_FAVORITE_9", "HOUR_FAVORITE_10", "HOUR_FAVORITE_11", "HOUR_FAVORITE_12", "HOUR_FAVORITE_13", "HOUR_FAVORITE_14", "HOUR_FAVORITE_15", "HOUR_FAVORITE_16", "HOUR_FAVORITE_17", "HOUR_FAVORITE_18", "HOUR_FAVORITE_19", "HOUR_FAVORITE_20", "HOUR_FAVORITE_21", "HOUR_FAVORITE_22", "HOUR_FAVORITE_23"
						);
				}
				break;
			case "weekday":
				$arSelect1 = array(
					"WEEKDAY_HOST_0", "WEEKDAY_HOST_1", "WEEKDAY_HOST_2", "WEEKDAY_HOST_3", "WEEKDAY_HOST_4", "WEEKDAY_HOST_5", "WEEKDAY_HOST_6",

					"WEEKDAY_SESSION_0", "WEEKDAY_SESSION_1", "WEEKDAY_SESSION_2", "WEEKDAY_SESSION_3", "WEEKDAY_SESSION_4", "WEEKDAY_SESSION_5", "WEEKDAY_SESSION_6",

					"WEEKDAY_HIT_0", "WEEKDAY_HIT_1", "WEEKDAY_HIT_2", "WEEKDAY_HIT_3", "WEEKDAY_HIT_4", "WEEKDAY_HIT_5", "WEEKDAY_HIT_6",

					"WEEKDAY_EVENT_0", "WEEKDAY_EVENT_1", "WEEKDAY_EVENT_2", "WEEKDAY_EVENT_3", "WEEKDAY_EVENT_4", "WEEKDAY_EVENT_5", "WEEKDAY_EVENT_6"
					);

				if ($table_name=="b_stat_day")
				{
					$arSelect2 = array(
						"WEEKDAY_GUEST_0", "WEEKDAY_GUEST_1", "WEEKDAY_GUEST_2", "WEEKDAY_GUEST_3", "WEEKDAY_GUEST_4", "WEEKDAY_GUEST_5", "WEEKDAY_GUEST_6",

						"WEEKDAY_NEW_GUEST_0", "WEEKDAY_NEW_GUEST_1", "WEEKDAY_NEW_GUEST_2", "WEEKDAY_NEW_GUEST_3", "WEEKDAY_NEW_GUEST_4", "WEEKDAY_NEW_GUEST_5", "WEEKDAY_NEW_GUEST_6",

						"WEEKDAY_FAVORITE_0", "WEEKDAY_FAVORITE_1", "WEEKDAY_FAVORITE_2", "WEEKDAY_FAVORITE_3", "WEEKDAY_FAVORITE_4", "WEEKDAY_FAVORITE_5", "WEEKDAY_FAVORITE_6"
					);
				}
				break;
			case "month":
				$arSelect1 = array(
					"MONTH_HOST_1", "MONTH_HOST_2", "MONTH_HOST_3", "MONTH_HOST_4", "MONTH_HOST_5", "MONTH_HOST_6", "MONTH_HOST_7", "MONTH_HOST_8", "MONTH_HOST_9", "MONTH_HOST_10", "MONTH_HOST_11", "MONTH_HOST_12",

					"MONTH_SESSION_1", "MONTH_SESSION_2", "MONTH_SESSION_3", "MONTH_SESSION_4", "MONTH_SESSION_5", "MONTH_SESSION_6", "MONTH_SESSION_7", "MONTH_SESSION_8", "MONTH_SESSION_9", "MONTH_SESSION_10", "MONTH_SESSION_11", "MONTH_SESSION_12",

					"MONTH_HIT_1", "MONTH_HIT_2", "MONTH_HIT_3", "MONTH_HIT_4", "MONTH_HIT_5", "MONTH_HIT_6", "MONTH_HIT_7", "MONTH_HIT_8", "MONTH_HIT_9", "MONTH_HIT_10", "MONTH_HIT_11", "MONTH_HIT_12",

					"MONTH_EVENT_1", "MONTH_EVENT_2", "MONTH_EVENT_3", "MONTH_EVENT_4", "MONTH_EVENT_5", "MONTH_EVENT_6", "MONTH_EVENT_7", "MONTH_EVENT_8", "MONTH_EVENT_9", "MONTH_EVENT_10", "MONTH_EVENT_11", "MONTH_EVENT_12"
					);

				if ($table_name=="b_stat_day")
				{
					$arSelect2 = array(
						"MONTH_GUEST_1", "MONTH_GUEST_2", "MONTH_GUEST_3", "MONTH_GUEST_4", "MONTH_GUEST_5", "MONTH_GUEST_6", "MONTH_GUEST_7", "MONTH_GUEST_8", "MONTH_GUEST_9", "MONTH_GUEST_10", "MONTH_GUEST_11", "MONTH_GUEST_12",

						"MONTH_NEW_GUEST_1", "MONTH_NEW_GUEST_2", "MONTH_NEW_GUEST_3", "MONTH_NEW_GUEST_4", "MONTH_NEW_GUEST_5", "MONTH_NEW_GUEST_6", "MONTH_NEW_GUEST_7", "MONTH_NEW_GUEST_8", "MONTH_NEW_GUEST_9", "MONTH_NEW_GUEST_10", "MONTH_NEW_GUEST_11", "MONTH_NEW_GUEST_12",

						"MONTH_FAVORITE_1", "MONTH_FAVORITE_2", "MONTH_FAVORITE_3", "MONTH_FAVORITE_4", "MONTH_FAVORITE_5", "MONTH_FAVORITE_6", "MONTH_FAVORITE_7", "MONTH_FAVORITE_8", "MONTH_FAVORITE_9", "MONTH_FAVORITE_10", "MONTH_FAVORITE_11", "MONTH_FAVORITE_12"
						);
				}
				break;
		}

		$sql = "";
		$arSelect = array_merge($arSelect1, $arSelect2);
		foreach($arSelect as $name)
			$sql .= "sum($name) $name,\n";

		$strSql = "
			SELECT $sql 1
			FROM
				$table_name
			WHERE
				$strSqlSearch
			";

		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $rs;
	}

	
	/**
	* <p>Возвращает количество <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хитов</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#host">хостов</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессий</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителей</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событий</a>, коэфициенты оценки внимательности посетителей в разрезе по дням.</p>
	*
	*
	* @param string &$by = "s_date" Поле для сортировки. Возможные значения: <ul> <li> <b>s_id</b> - ID записи;
	* </li> <li> <b>s_date</b> - дата; </li> <li> <b>s_hits</b> - количество хитов; </li> <li>
	* <b>s_hosts</b> - количество хостов; </li> <li> <b>s_sessions</b> - количество сессий;
	* </li> <li> <b>s_events</b> - количество событий; </li> <li> <b>s_guests</b> - количество
	* посетителей; </li> <li> <b>s_new_guests</b> - количество <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#new_guest">новых посетителей</a>; </li> <li>
	* <b>s_favorites</b> - количество посетителей, добавивших сайт в "<a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#favorites">Избранное</a>". </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array &$max_min  Ссылка на массив содержащий максимальную и минимальную даты из
	* выбранного результирующего списка. Структура данного массива: <pre
	* style="font-size: 95%;">Array ( [DATE_FIRST] =&gt; минимальная дата [MIN_DAY] =&gt; день
	* минимальной даты (1-31) [MIN_MONTH] =&gt; месяц минимальной даты (1-12) [MIN_YEAR]
	* =&gt; год минимальной даты [DATE_LAST] =&gt; максимальная дата [MAX_DAY] =&gt;
	* день максимальной даты (1-31) [MAX_MONTH] =&gt; месяц максимальной даты (1-12)
	* [MAX_YEAR] =&gt; год максимальной даты )</pre>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID записи; </li> <li>
	* <b>ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по <b>ID</b>
	* будет искаться вхождение; </li> <li> <b>DATE1</b> - начальное значение
	* интервала для поля "дата"; </li> <li> <b>DATE2</b> - конечное значение
	* интервала для поля "дата"; </li> <li> <b>HITS_1</b> - начальное значение
	* интервала для поля "количество хитов"; </li> <li> <b>HITS_2</b> - конечное
	* значение интервала для поля "количество хитов"; </li> <li> <b>HOSTS_1</b> -
	* начальное значение интервала для поля "количество хостов"; </li> <li>
	* <b>HOSTS_2</b> - начальное значение интервала для поля "количество
	* хостов"; </li> <li> <b>SESSIONS_1</b> - начальное значение интервала для поля
	* "количество сессий"; </li> <li> <b>SESSIONS_2</b> - конечное значение интервала
	* для поля "количество сессий"; </li> <li> <b>EVENTS_1</b> - начальное значение
	* интервала для поля "количество событий"; </li> <li> <b>EVENTS_2</b> - конечное
	* значение интервала для поля "количество событий"; </li> <li> <b>GUESTS_1</b> -
	* начальное значение интервала для поля "количество посетителей";
	* </li> <li> <b>GUESTS_2</b> - конечное значение интервала для поля "количество
	* посетителей"; </li> <li> <b>NEW_GUESTS_1</b> - начальное значение интервала для
	* поля "количество новых посетителей"; </li> <li> <b>NEW_GUESTS_2</b> - конечное
	* значение интервала для поля "количество новых посетителей"; </li> <li>
	* <b>FAVORITES_1</b> - начальное значение интервала для поля "количество
	* посетителей добавивших сайт в "Избранное""; </li> <li> <b>FAVORITES_2</b> -
	* конечное значение интервала для поля "количество посетителей
	* добавивших сайт в "Избранное"; </li> <li> <b>SITE_ID</b>* - ID сайта; </li> <li>
	* <b>SITE_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>SITE_ID</b> будет искаться вхождение. </li> </ul> * - допускается <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности результирующего списка. Если значение
	* равно "true", то список был отфильтрован.
	*
	* @param string $get_maxmin = "Y" Если значение данной переменной равно "Y", то в параметр <i>max_min</i>
	* будет возвращен соответствующий массив содержащий максимальную
	* и минимальную даты из выбранного результирующего списка.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // ограничимся данными только за декабрь 2007 года
	* $arFilter = array(
	*     "DATE1" =&gt; "01.12.2007",
	*     "DATE2" =&gt; "31.12.2007"
	*     );
	* 
	* // получим данные по посещаемости
	* $rsDays = <b>CTraffic::GetDailyList</b>(
	*     ($by="s_date"), 
	*     ($order="desc"), 
	*     $arMaxMin, 
	*     $arFilter, 
	*     $is_filtered
	*     );
	* 
	* while ($arDay = $rsDays-&gt;Fetch())
	* {
	*     echo "дата: ".$arDay["DATE_STAT"]."&lt;br&gt;";
	*     echo "количество хитов: ".$arDay["HITS"]."&lt;br&gt;";
	*     echo "количество хостов: ".$arDay["C_HOSTS"]."&lt;br&gt;";
	*     echo "количество сессий: ".$arDay["SESSIONS"]."&lt;br&gt;";
	*     echo "количество событий: ".$arDay["C_EVENTS"]."&lt;br&gt;";
	*     echo "количество посетителей: ".$arDay["GUESTS"]."&lt;br&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/ctraffic/getcommonvalues.php">CTraffic::GetCommonValues</a>
	* </li> <li> <a href="http://www.1c-bitrix.ru/user_help/statistic/site_traffic/traffic.php">Отчет "Динамика
	* посещаемости"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/ctraffic/getdailylist.php
	* @author Bitrix
	*/
	public static function GetDailyList(&$by, &$order, &$arMaxMin, $arFilter=Array(), &$is_filtered, $get_maxmin="Y")
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();
		$strSqlSearch = "";
		$site_filtered = false;
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
						$arSqlSearch[] = GetFilterQuery("D.ID",$val,$match);
						break;
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "HITS_1":
						$arSqlSearch[] = "D.HITS>='".intval($val)."'";
						break;
					case "HITS_2":
						$arSqlSearch[] = "D.HITS<='".intval($val)."'";
						break;
					case "SESSIONS_1":
						$arSqlSearch[] = "D.SESSIONS>='".intval($val)."'";
						break;
					case "SESSIONS_2":
						$arSqlSearch[] = "D.SESSIONS<='".intval($val)."'";
						break;
					case "NEW_GUESTS_1":
						$arSqlSearch[] = "D.NEW_GUESTS>='".intval($val)."'";
						break;
					case "NEW_GUESTS_2":
						$arSqlSearch[] = "D.NEW_GUESTS<='".intval($val)."'";
						break;
					case "FAVORITES_1":
						$arSqlSearch[] = "D.FAVORITES>='".intval($val)."'";
						break;
					case "FAVORITES_2":
						$arSqlSearch[] = "D.FAVORITES<='".intval($val)."'";
						break;
					case "GUESTS_1":
						$arSqlSearch[] = "D.GUESTS>='".intval($val)."'";
						break;
					case "GUESTS_2":
						$arSqlSearch[] = "D.GUESTS<='".intval($val)."'";
						break;
					case "HOSTS_1":
						$arSqlSearch[] = "D.C_HOSTS>='".intval($val)."'";
						break;
					case "HOSTS_2":
						$arSqlSearch[] = "D.C_HOSTS<='".intval($val)."'";
						break;
					case "EVENTS_1":
						$arSqlSearch[] = "D.C_EVENTS>='".intval($val)."'";
						break;
					case "EVENTS_2":
						$arSqlSearch[] = "D.C_EVENTS<='".intval($val)."'";
						break;
					case "SITE_ID":
						$site_filtered = true;
						if (is_array($val))
							$val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("D.SITE_ID", $val, $match);
						break;
				}
			}
		}

		foreach($arSqlSearch as $sqlWhere)
			$strSqlSearch .= " and (".$sqlWhere.") ";

		if ($by == "s_id")
			$strSqlOrder = "ORDER BY D.ID";
		elseif ($by == "s_date")
			$strSqlOrder = "ORDER BY D.DATE_STAT";
		elseif ($by == "s_hits")
			$strSqlOrder = "ORDER BY D.HITS";
		elseif ($by == "s_hosts")
			$strSqlOrder = "ORDER BY D.C_HOSTS";
		elseif ($by == "s_sessions")
			$strSqlOrder = "ORDER BY D.SESSIONS";
		elseif ($by == "s_events")
			$strSqlOrder = "ORDER BY D.C_EVENTS";
		elseif ($by == "s_guests")
			$strSqlOrder = "ORDER BY D.GUESTS";
		elseif ($by == "s_new_guests")
			$strSqlOrder = "ORDER BY D.NEW_GUESTS";
		elseif ($by == "s_favorites")
			$strSqlOrder = "ORDER BY D.FAVORITES";
		else
		{
			$by = "s_date";
			$strSqlOrder = "ORDER BY D.DATE_STAT";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$table_name = ($site_filtered) ? "b_stat_day_site" : "b_stat_day";
		$strSql = "
			SELECT
				D.ID,
				D.HITS,
				D.C_HOSTS,
				D.SESSIONS,
				D.C_EVENTS,
				D.GUESTS,
				D.NEW_GUESTS,
				D.FAVORITES,
				D.AM_AVERAGE_TIME,
				D.AM_1,D.AM_1_3, D.AM_3_6, D.AM_6_9, D.AM_9_12, D.AM_12_15, D.AM_15_18, D.AM_18_21, D.AM_21_24, D.AM_24,
				D.AH_AVERAGE_HITS,
				D.AH_1, D.AH_2_5, D.AH_6_9, D.AH_10_13, D.AH_14_17, D.AH_18_21, D.AH_22_25, D.AH_26_29, D.AH_30_33, D.AH_34,
				".$DB->DateToCharFunction("D.DATE_STAT","SHORT")." DATE_STAT,
				DAYOFMONTH(D.DATE_STAT) DAY,
				MONTH(D.DATE_STAT) MONTH,
				YEAR(D.DATE_STAT) YEAR,
				WEEKDAY(D.DATE_STAT) WDAY
			FROM
				".$table_name." D
			WHERE
				1=1
				".$strSqlSearch."
			$strSqlOrder
			";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));

		if ($get_maxmin=="Y")
		{
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
					".$table_name." D
				WHERE
					1=1
					".$strSqlSearch."
				";
			$a = $DB->Query($strSql, false, $err_mess.__LINE__);
			$ar = $a->Fetch();
			if (!is_array($arMaxMin))
				$arMaxMin = array();
			$arMaxMin["MAX_DAY"]	= $ar["MAX_DAY"];
			$arMaxMin["MAX_MONTH"]	= $ar["MAX_MONTH"];
			$arMaxMin["MAX_YEAR"]	= $ar["MAX_YEAR"];
			$arMaxMin["MIN_DAY"]	= $ar["MIN_DAY"];
			$arMaxMin["MIN_MONTH"]	= $ar["MIN_MONTH"];
			$arMaxMin["MIN_YEAR"]	= $ar["MIN_YEAR"];
		}
		return $res;
	}

	
	/**
	* <p>Возвращает количество <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хитов</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#host">хостов</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессий</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителей</a>, <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событий</a> за весь период ведения статистики, за последние 3 дня, а также за произвольный указанный интервал времени.</p> <p class="note"><b>Примечание</b>: при фильтрации по сайту (<i>filter</i>[<b>SITE_ID</b>]), будут доступны только данные по хитам, хостам, сессиям, событиям. Данные по посетителям будут отсутствовать, т.к. текущая версия модуля предполагает, что посетитель один на весь портал.</p>
	*
	*
	* @param array $arrayfilter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>SITE_ID</b> - ID сайта для которого
	* необходимо получить статистику; </li> <li> <b>DATE1</b> - начальная дата; </li>
	* <li> <b>DATE2</b> - конечная дата. </li> </ul>
	*
	* @return array <p>В случае если не установлена фильтрация по сайту, то структура
	* возвращаемого массива будет следующей:</p> <pre class="syntax">Array ( [TOTAL_HITS]
	* =&gt; суммарное количество хитов за все время ведения статистики
	* [TOTAL_SESSIONS] =&gt; суммарное количество сессий за все время ведения
	* статистики [TOTAL_EVENTS] =&gt; суммарное количество событий за все время
	* ведения статистики [TOTAL_HOSTS] =&gt; суммарное количество хостов за все
	* время ведения статистики [TOTAL_GUESTS] =&gt; суммарное количество
	* посетителей за все время ведения статистики [TOTAL_FAVORITES] =&gt;
	* суммарное количество посетителей, добавивших сайт в "<a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#favorites">Избранное</a>" за все время
	* ведения статистики [TODAY_HITS] =&gt; количество хитов за сегодня
	* [TODAY_SESSIONS] =&gt; количество сессий за сегодня [TODAY_EVENTS] =&gt; количество
	* событий за сегодня [TODAY_HOSTS] =&gt; количество хостов за сегодня
	* [TODAY_GUESTS] =&gt; суммарное количество посетителей за сегодня
	* [TODAY_NEW_GUESTS] =&gt; количество <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#new_guest">новых посетителей</a> за
	* сегодня [TODAY_FAVORITES] =&gt; количество посетителей, добавивших сайт в
	* "Избранное", за сегодня [YESTERDAY_HITS] =&gt; количество хитов за вчера
	* [YESTERDAY_SESSIONS] =&gt; количество сессий за вчера [YESTERDAY_EVENTS] =&gt;
	* количество событий за вчера [YESTERDAY_HOSTS] =&gt; количество хостов за
	* вчера [YESTERDAY_GUESTS] =&gt; суммарное количество посетителей за вчера
	* [YESTERDAY_NEW_GUESTS] =&gt; количество новых посетителей за вчера
	* [YESTERDAY_FAVORITES] =&gt; количество посетителей, добавивших сайт в
	* "Избранное", за вчера [B_YESTERDAY_HITS] =&gt; количество хитов за позавчера
	* [B_YESTERDAY_SESSIONS] =&gt; количество сессий за позавчера [B_YESTERDAY_EVENTS] =&gt;
	* количество событий за позавчера [B_YESTERDAY_HOSTS] =&gt; количество хостов
	* за позавчера [B_YESTERDAY_GUESTS] =&gt; суммарное количество посетителей за
	* позавчера [B_YESTERDAY_NEW_GUESTS] =&gt; количество новых посетителей за
	* позавчера [B_YESTERDAY_FAVORITES] =&gt; количество посетителей, добавивших
	* сайт в "Избранное", за позавчера [PERIOD_HITS] =&gt; количество хитов за
	* установленный период времени (<i>filter</i>[<b>DATE1</b>], <i>filter</i>[<b>DATE2</b>])
	* [PERIOD_SESSIONS] =&gt; количество сессий за установленный период времени
	* [PERIOD_EVENTS] =&gt; количество событий за установленный период времени
	* [PERIOD_NEW_GUESTS] =&gt; количество новых посетителей за установленный
	* период времени [PERIOD_FAVORITES] =&gt; количество посетителей, добавивших
	* сайт в "Избранное", за установленный период времени [ONLINE_GUESTS] =&gt;
	* количество <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#online">посетителей в
	* online</a> )</pre> <p></p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // получим данные по посещаемости сайта
	* $arr = <b>CTraffic::GetCommonValues</b>();
	* 
	* echo "Всего хитов: ".$arr["TOTAL_HITS"];
	* echo "Сегодня хитов: ".$arr["TODAY_HITS"];
	* 
	* echo "Всего хостов: ".$arr["TOTAL_HOSTS"];
	* echo "Сегодня хостов: ".$arr["TODAY_HOSTS"];
	* 
	* echo "Всего посетителей: ".$arr["TOTAL_GUESTS"];
	* echo "Сегодня посетителей: ".$arr["TODAY_GUESTS"];
	* echo "Посетителей в онлайн: ".$arr["ONLINE_GUESTS"];
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cuseronline/getguestcount.php">CUserOnline::GetGuestCount</a>
	* </li> <li> <a href="http://www.1c-bitrix.ru/user_help/statistic/stat_list.php">Отчет "Сводная
	* статистика"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/ctraffic/getcommonvalues.php
	* @author Bitrix
	*/
	public static function GetCommonValues($arFilter=Array(), $bIgnoreErrors=false)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');

		$site_id = $arFilter["SITE_ID"];
		if(strlen($site_id)>0 && $site_id!="NOT_REF")
		{
			$site_filter = true;
			$strSqlSearch = " and SITE_ID = '".$DB->ForSql($site_id, 2)."' ";
		}
		else
		{
			$site_filter = false;
			$strSqlSearch = "";
		}

		$date1 = $arFilter["DATE1"];
		$date2 = $arFilter["DATE2"];
		if(strlen($date1)>0 && CheckDateTime($date1))
		{
			$is_filtered = true;
			$date_from = MkDateTime(ConvertDateTime($date1,"D.M.Y"),"d.m.Y");
			if(strlen($date2)>0 && CheckDateTime($date2))
			{
				$date_to = MkDateTime(ConvertDateTime($date2,"D.M.Y")." 23:59","d.m.Y H:i");
				$strSqlPeriod = "sum(if(DATE_STAT<FROM_UNIXTIME('$date_from'),0, if(DATE_STAT>FROM_UNIXTIME('$date_to'),0,";
				$strT=")))";
			}
			else
			{
				$strSqlPeriod = "sum(if(DATE_STAT<FROM_UNIXTIME('$date_from'),0,";
				$strT="))";
			}
		}
		elseif(strlen($date2)>0 && CheckDateTime($date2))
		{
			$is_filtered = true;
			$date_to = MkDateTime(ConvertDateTime($date2,"D.M.Y")." 23:59","d.m.Y H:i");
			$strSqlPeriod = "sum(if(DATE_STAT>FROM_UNIXTIME('$date_to'),0,";
			$strT="))";
		}
		else
		{
			$is_filtered = false;
			$strSqlPeriod = "";
			$strT="";
		}

		$strSql = "
			SELECT
				sum(HITS)							TOTAL_HITS,
				sum(if(to_days(curdate())=to_days(DATE_STAT),HITS,0))		TODAY_HITS,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=1,HITS,0))		YESTERDAY_HITS,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=2,HITS,0))		B_YESTERDAY_HITS,

				sum(SESSIONS)							TOTAL_SESSIONS,
				sum(if(to_days(curdate())=to_days(DATE_STAT),SESSIONS,0))	TODAY_SESSIONS,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=1,SESSIONS,0))	YESTERDAY_SESSIONS,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=2,SESSIONS,0))	B_YESTERDAY_SESSIONS,

				sum(C_EVENTS)							TOTAL_EVENTS,
				sum(if(to_days(curdate())=to_days(DATE_STAT),C_EVENTS,0))	TODAY_EVENTS,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=1,C_EVENTS,0))	YESTERDAY_EVENTS,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=2,C_EVENTS,0))	B_YESTERDAY_EVENTS,

				sum(C_HOSTS)							TOTAL_HOSTS,
				sum(if(to_days(curdate())=to_days(DATE_STAT),C_HOSTS,0))	TODAY_HOSTS,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=1,C_HOSTS,0))	YESTERDAY_HOSTS,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=2,C_HOSTS,0))	B_YESTERDAY_HOSTS,

				sum(NEW_GUESTS)							TOTAL_GUESTS,
				sum(if(to_days(curdate())=to_days(DATE_STAT),GUESTS,0))		TODAY_GUESTS,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=1,GUESTS,0))	YESTERDAY_GUESTS,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=2,GUESTS,0))	B_YESTERDAY_GUESTS,

				sum(if(to_days(curdate())=to_days(DATE_STAT),NEW_GUESTS,0))	TODAY_NEW_GUESTS,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=1,NEW_GUESTS,0))	YESTERDAY_NEW_GUESTS,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=2,NEW_GUESTS,0))	B_YESTERDAY_NEW_GUESTS,

				sum(FAVORITES)							TOTAL_FAVORITES,
				sum(if(to_days(curdate())=to_days(DATE_STAT),FAVORITES,0))	TODAY_FAVORITES,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=1,FAVORITES,0))	YESTERDAY_FAVORITES,
				sum(if(to_days(curdate())-to_days(DATE_STAT)=2,FAVORITES,0))	B_YESTERDAY_FAVORITES
				".
				($is_filtered ? ','.
					$strSqlPeriod.'HITS'.$strT.' PERIOD_HITS, '.
					$strSqlPeriod.'SESSIONS'.$strT.' PERIOD_SESSIONS, '.
					$strSqlPeriod.'C_EVENTS'.$strT.' PERIOD_EVENTS, '.
					$strSqlPeriod.'FAVORITES'.$strT.' PERIOD_FAVORITES, '.
					$strSqlPeriod.'NEW_GUESTS'.$strT.' PERIOD_NEW_GUESTS '
				: '')
				."
			FROM
				".($site_filter ? "b_stat_day_site" : "b_stat_day")."
			WHERE
				1=1
				".$strSqlSearch."
		";

		$result = false;
		$rs = $DB->Query($strSql, $bIgnoreErrors, $err_mess.__LINE__);
		if($rs)
		{
			if($result = $rs->Fetch())
			{
				foreach($result as $key=>$value)
					$result[$key] = intval($value);
				if(!$site_filter)
					$result["ONLINE_GUESTS"] = CUserOnline::GetGuestCount();
			}
		}
		return $result;
	}


	
	/**
	* <p>Возвращает количество <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#referer">ссылающихся сайтов</a> за весь период ведения статистики, за последние 3 дня, а также за произвольный указанный интервал времени.</p>
	*
	*
	* @param string &$by = "ref_today" Поле для сортировки. Возможные значения: <ul> <li> <b>ref_server</b> -
	* ссылающийся сайт; </li> <li> <b>ref_today</b> - количество заходов с
	* ссылающегося сайта за сегодня; </li> <li> <b>ref_yesterday</b> - количество
	* заходов с ссылающегося сайта за вчера; </li> <li> <b>ref_bef_yesterday</b> -
	* количество заходов с ссылающегося сайта за позавчера; </li> <li>
	* <b>ref_total</b> - суммарное количество заходов с ссылающегося сайта; </li>
	* <li> <b>ref_period</b> - количество заходов с ссылающегося сайта за
	* установленный период времени <nobr>(<i>filter</i>[<b>DATE1</b>],
	* <i>filter</i>[<b>DATE2</b>])</nobr>. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>SITE_ID</b> - ID сайта для которого
	* необходимо получить статистику по ссылающимся сайтам; </li> <li>
	* <b>DATE1</b> - начальная дата; </li> <li> <b>DATE2</b> - конечная дата. </li> </ul>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности списка ссылающихся сайтов. Если значение
	* равно "true", то список был отфильтрован.
	*
	* @param mixed $limit = 10 Максимальное число записей результирующего списка. Если задано
	* число &gt;0, то число записей будет ограничено, иначе ограничений не
	* будет.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // получим дополнительные данные за декабрь 2007 года
	* $arFilter = array(
	*     "DATE1" =&gt; "01.12.2007",
	*     "DATE2" =&gt; "31.12.2007"
	*     );
	* 
	* // получим список поисковых фраз и статистику по ним
	* $rs = <b>CTraffic::GetRefererList</b>(
	*     ($by="ref_today"), 
	*     ($order="desc"), 
	*     $arFilter, 
	*     $is_filtered, 
	*     false
	*     );
	* 
	* while ($ar = $rs-&gt;Fetch())
	* {
	*     echo "ссылающийся сайт: ".$ar["SITE_NAME"]."&lt;br&gt;";
	*     echo "всего заходов с данного ссылающегося сайта: ".
	*          $ar["TOTAL_REFERERS"]."&lt;br&gt;";
	*     echo "сегодня: ".$ar["TODAY_REFERERS"]."&lt;br&gt;";
	*     echo "вчера: ".$ar["YESTERDAY_REFERERS"]."&lt;br&gt;";
	*     echo "позавчера: ".$ar["B_YESTERDAY_REFERERS"]."&lt;br&gt;";
	*     echo "в течение декабря 2005 года: ".
	*          $ar["PERIOD_REFERERS"]."&lt;br&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/creferer/index.php">Класс "CReferer"</a>
	* </li> <li> <a href="http://www.1c-bitrix.ru/user_help/statistic/stat_list.php">Отчет "Сводная
	* статистика"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/ctraffic/getrefererlist.php
	* @author Bitrix
	*/
	public static function GetRefererList(&$by, &$order, $arFilter=Array(), &$is_filtered, $limit=10)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');

		$site_id = $arFilter["SITE_ID"];
		if (strlen($site_id)>0 && $site_id!="NOT_REF")
		{
			$is_filtered = true;
			$strSqlSearch = " and SITE_ID = '".$DB->ForSql($site_id, 2)."' ";
		}
		else
		{
			$is_filtered = false;
			$strSqlSearch = "";
		}

		$date1 = $arFilter["DATE1"];
		$date2 = $arFilter["DATE2"];
		$date_from = MkDateTime(ConvertDateTime($date1,"D.M.Y"),"d.m.Y");
		$date_to = MkDateTime(ConvertDateTime($date2,"D.M.Y")." 23:59","d.m.Y H:i");
		if (strlen($date1)>0)
		{
			$date_filtered = $is_filtered = true;
			if (strlen($date2)>0)
				$strSqlPeriod = " sum(if(DATE_HIT<FROM_UNIXTIME('$date_from'),0, if(date_hit>FROM_UNIXTIME('$date_to'),0,1)))";
			else
				$strSqlPeriod = " sum(if(DATE_HIT<FROM_UNIXTIME('$date_from'),0,1))";
		}
		elseif (strlen($date2)>0)
		{
			$date_filtered = $is_filtered = true;
			$strSqlPeriod = " sum(if(DATE_HIT>FROM_UNIXTIME('$date_to'),0,1))";
		}
		else
		{
			$date_filtered = false;
			$strSqlPeriod = "";
		}

		if ($by == "ref_server")
			$strSqlOrder = " ORDER BY SITE_NAME ";
		elseif($by == "ref_today")
			$strSqlOrder = " ORDER BY TODAY_REFERERS ";
		elseif($by == "ref_yesterday")
			$strSqlOrder = " ORDER BY YESTERDAY_REFERERS ";
		elseif($by == "ref_bef_yesterday")
			$strSqlOrder = " ORDER BY B_YESTERDAY_REFERERS ";
		elseif($by == "ref_total")
			$strSqlOrder = " ORDER BY TOTAL_REFERERS ";
		elseif($by == "ref_period" && $date_filtered)
			$strSqlOrder = " ORDER BY PERIOD_REFERERS";
		else
		{
			$by = "ref_today";
			$strSqlOrder = "ORDER BY TODAY_REFERERS desc, YESTERDAY_REFERERS desc, B_YESTERDAY_REFERERS desc, TOTAL_REFERERS ";
		}

		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$strSql = "
			SELECT
				SITE_NAME,
				count('x') TOTAL_REFERERS,
				sum(if(to_days(curdate())-to_days(DATE_HIT)=0,1,0)) TODAY_REFERERS,
				sum(if(to_days(curdate())-to_days(DATE_HIT)=1,1,0)) YESTERDAY_REFERERS,
				sum(if(to_days(curdate())-to_days(DATE_HIT)=2,1,0)) B_YESTERDAY_REFERERS
				".
				($date_filtered ? ','.$strSqlPeriod.' as PERIOD_REFERERS ' : '')
				."
			FROM
				b_stat_referer_list
			WHERE
				1=1
				".$strSqlSearch."
			GROUP BY
				SITE_NAME
			".$strSqlOrder."
		";
		if(intval($limit)>0)
		{
			$strSql .= " LIMIT ".intval($limit);
		}
		return $DB->Query($strSql, false, $err_mess.__LINE__);
	}

	
	/**
	* <p>Возвращает количество <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search">поисковых фраз</a> за весь период ведения статистики, за последние 3 дня, а также за произвольный указанный интервал времени.</p>
	*
	*
	* @param string &$by = "s_today" Поле для сортировки. Возможные значения: <ul> <li> <b>s_phrase</b> -
	* поисковая фраза; </li> <li> <b>s_today</b> - количество поисковых фраз за
	* сегодня; </li> <li> <b>s_yesterday</b> - количество поисковых фраз за вчера; </li>
	* <li> <b>s_bef_yesterday</b> - количество поисковых фраз за позавчера; </li> <li>
	* <b>s_total</b> - суммарное количество поисковых фраз; </li> <li> <b>s_period</b> -
	* количество поисковых фраз за установленный период времени
	* <nobr>(<i>filter</i>[<b>DATE1</b>], <i>filter</i>[<b>DATE2</b>])</nobr>. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>SITE_ID</b> - ID сайта для которого
	* необходимо получить статистику по поисковым фразам; </li> <li> <b>DATE1</b>
	* - начальная дата; </li> <li> <b>DATE2</b> - конечная дата. </li> </ul>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности списка поисковых фраз. Если значение
	* равно "true", то список был отфильтрован.
	*
	* @param mixed $limit = 10 Максимальное число записей результирующего списка. Если задано
	* число &gt;0, то число записей будет ограничено, иначе ограничений не
	* будет.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // получим дополнительные данные за декабрь 2007 года
	* $arFilter = array(
	*     "DATE1" =&gt; "01.12.2007",
	*     "DATE2" =&gt; "31.12.2007"
	*     );
	* 
	* // получим список поисковых фраз и статистику по ним
	* $rs = CTraffic::GetPhraseList($by="s_today", 
	*                               $order="desc", 
	*                               $arFilter, 
	*                               $is_filtered, 
	*                               false);
	* while ($ar = $rs-&gt;Fetch())
	* {
	*     echo "поисковая фраза: ".$ar["PHRASE"]."&lt;br&gt;";
	*     echo "всего заходов с данной поисковой фразой: ".
	*          $ar["TOTAL_PHRASES"].
	*          "&lt;br&gt;";
	*     echo "сегодня: ".$ar["TODAY_PHRASES"]."&lt;br&gt;";
	*     echo "вчера: ".$ar["YESTERDAY_PHRASES"]."&lt;br&gt;";
	*     echo "позавчера: ".$ar["B_YESTERDAY_PHRASES"]."&lt;br&gt;";
	*     echo "в течение декабря 2005 года: ".
	*          $ar["PERIOD_PHRASES"]."&lt;br&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/cphrase/index.php">Класс "CPhrase"</a> </li>
	* <li> <a href="http://www.1c-bitrix.ru/user_help/statistic/stat_list.php">Отчет "Сводная
	* статистика"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/ctraffic/getphraselist.php
	* @author Bitrix
	*/
	public static function GetPhraseList(&$s_by, &$s_order, $arFilter=Array(), &$is_filtered, $limit=10)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$strSqlSearch = "";

		$site_id = $arFilter["SITE_ID"];
		if (strlen($site_id)>0 && $site_id!="NOT_REF")
		{
			$is_filtered = true;
			$strSqlSearch = " and SITE_ID = '".$DB->ForSql($site_id, 2)."' ";
		}

		$date1 = $arFilter["DATE1"];
		$date2 = $arFilter["DATE2"];
		$date_from = MkDateTime(ConvertDateTime($date1,"D.M.Y"),"d.m.Y");
		$date_to = MkDateTime(ConvertDateTime($date2,"D.M.Y")." 23:59","d.m.Y H:i");
		if (strlen($date1)>0)
		{
			$date_filtered = $is_filtered = true;
			if (strlen($date2)>0)
				$strSqlPeriod = " sum(if(DATE_HIT<FROM_UNIXTIME('$date_from'),0, if(date_hit>FROM_UNIXTIME('$date_to'),0,1)))";
			else
				$strSqlPeriod = " sum(if(DATE_HIT<FROM_UNIXTIME('$date_from'),0,1))";
		}
		elseif (strlen($date2)>0)
		{
			$date_filtered = $is_filtered = true;
			$strSqlPeriod = " sum(if(DATE_HIT>FROM_UNIXTIME('$date_to'),0,1))";
		}
		else
		{
			$date_filtered = false;
			$strSqlPeriod = "";
		}

		if ($s_by == "s_phrase")
			$strSqlOrder = " ORDER BY PHRASE ";
		elseif ($s_by == "s_today")
			$strSqlOrder = " ORDER BY TODAY_PHRASES ";
		elseif ($s_by == "s_yesterday")
			$strSqlOrder = " ORDER BY YESTERDAY_PHRASES ";
		elseif ($s_by == "s_bef_yesterday")
			$strSqlOrder = " ORDER BY B_YESTERDAY_PHRASES ";
		elseif ($s_by == "s_total")
			$strSqlOrder = " ORDER BY TOTAL_PHRASES ";
		elseif($s_by == "s_period" && $date_filtered)
			$strSqlOrder = " ORDER BY PERIOD_PHRASES ";
		else
		{
			$s_by = "s_today";
			$strSqlOrder = " ORDER BY TODAY_PHRASES desc, YESTERDAY_PHRASES desc, B_YESTERDAY_PHRASES desc, TOTAL_PHRASES ";
		}

		if ($s_order != "asc")
		{
			$strSqlOrder .= " desc ";
			$s_order="desc";
		}

		$strSql = "
			SELECT
				PHRASE,
				count('x') TOTAL_PHRASES,
				sum(if(to_days(curdate())-to_days(DATE_HIT)=0,1,0)) TODAY_PHRASES,
				sum(if(to_days(curdate())-to_days(DATE_HIT)=1,1,0)) YESTERDAY_PHRASES,
				sum(if(to_days(curdate())-to_days(DATE_HIT)=2,1,0)) B_YESTERDAY_PHRASES
				".
				($date_filtered ? ','.$strSqlPeriod.' PERIOD_PHRASES ' : '')
				."
			FROM
				b_stat_phrase_list
			WHERE
				1=1
				".$strSqlSearch."
			GROUP BY
				PHRASE
			".$strSqlOrder."
		";
		if(intval($limit)>0)
		{
			$strSql .= " LIMIT ".intval($limit);
		}
		return $DB->Query($strSql, false, $err_mess.__LINE__);
	}
}
?>
