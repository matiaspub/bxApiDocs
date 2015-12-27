<?

/**
 * <b>CPath</b> - класс для получения данных о <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#path">путях</a> по сайту. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cpath/index.php
 * @author Bitrix
 */
class CPath
{
	
	/**
	* <p>Возвращает данные из таблицы, хранящей статистическую информацию как по <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#path">полным путям</a>, так и по <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#path_step">отрезкам путей</a> в разрезе по дням.</p>
	*
	*
	* @param int $parent_id = "" ID "родительского" отрезка пути (предшествовавшему текущему).
	*
	* @param string $counter_type = "COUNTER_FULL_PATH" Тип счетчика, возможные значения: <ul> <li> <b>COUNTER_FULL_PATH</b> - количество
	* переходов по полному пути; </li> <li> <b>COUNTER</b> - количество переходов
	* по отрезку пути. </li> </ul>
	*
	* @param string &$by = "s_counter" Поле для сортировки. Возможные значения: <ul> <li> <b>s_counter</b> - значение
	* счетчика тип которого задается в <i>counter_type</i>; </li> <li> <b>s_last_page</b> -
	* последняя страница отрезка пути (используется только если
	* <i>counter_type</i>=<b>COUNTER</b>); </li> <li> <b>s_pages</b> - набор всех страниц полного
	* пути (используется только если <i>counter_type</i>=<b>COUNTER_FULL_PATH</b>). </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>PATH_ID</b>* - ID отрезка пути; </li> <li>
	* <b>PATH_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>PATH_ID</b> будет искаться вхождение; </li> <li> <b>DATE1</b> - начальное
	* значение для интервала даты; </li> <li> <b>DATE2</b> - конечное значение для
	* интервала даты; </li> <li> <b>FIRST_PAGE</b>* - первая страница пути; </li> <li>
	* <b>FIRST_PAGE_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>FIRST_PAGE</b> будет искаться точное совпадение; </li> <li> <b>FIRST_PAGE_SITE_ID</b> -
	* ID сайта первой страницы пути; </li> <li> <b>FIRST_PAGE_SITE_ID_EXACT_MATCH</b> - если
	* значение равно "N", то при фильтрации по <b>FIRST_PAGE_SITE_ID</b> будет
	* искаться вхождение; </li> <li> <b>FIRST_PAGE_404</b> - была ли <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#404">404 ошибка</a> на первой странице
	* пути, возможные значения: <ul> <li> <b>Y</b> - была; </li> <li> <b>N</b> - не была. </li>
	* </ul> </li> <li> <b>LAST_PAGE</b>* - последняя страница пути; </li> <li>
	* <b>LAST_PAGE_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>LAST_PAGE</b> будет искаться точное совпадение; </li> <li> <b>LAST_PAGE_SITE_ID</b>* -
	* ID сайта последней страницы пути; </li> <li> <b>LAST_PAGE_SITE_ID_EXACT_MATCH</b> - если
	* значение равно "N", то при фильтрации по <b>LAST_PAGE_SITE_ID</b> будет
	* искаться вхождение; </li> <li> <b>LAST_PAGE_404</b> - была ли <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#404">404 ошибка</a> на последней
	* странице пути, возможные значения: <ul> <li> <b>Y</b> - была; </li> <li> <b>N</b> -
	* не была. </li> </ul> </li> <li> <b>PAGE</b>* - произвольная страница пути </li> <li>
	* <b>PAGE_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по <b>PAGE</b>
	* будет искаться вхождение </li> <li> <b>PAGE_SITE_ID</b> - ID сайта произвольной
	* страницы пути </li> <li> <b>PAGE_404</b> - была ли <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#404">404 ошибка</a> на произвольной
	* странице пути, возможные значения: <ul> <li> <b>Y</b> - была </li> <li> <b>N</b> - не
	* была. </li> </ul> </li> <li> <b>ADV</b>* - ID рекламной кампании, по посетителям
	* которой надо получить данные; </li> <li> <b>ADV_EXACT_MATCH</b> - если значение
	* равно "N", то при фильтрации по <b>ADV</b> будет искаться вхождение; </li>
	* <li> <b>ADV_DATA_TYPE</b> - флаг типа данных для рекламной кампании,
	* возможные значения: <ul> <li> <b>P</b> - только по <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_first">прямым заходам</a> по
	* рекламной кампании; </li> <li> <b>B</b> - только по <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_back">возвратам</a> по рекламной
	* кампании; </li> <li> <b>S</b> - сумма по прямым заходам и возвратам. </li> </ul>
	* </li> <li> <b>STEPS1</b> - начальное значение интервала для поля "количество
	* страниц в пути"; </li> <li> <b>STEPS2</b> - конечное значение интервала для
	* поля "количество страниц в пути". </li> </ul> * - допускается <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности списка записей. Если значение равно "true",
	* то список был отфильтрован.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // выберем данные по полным путям пройденных посетителями
	* // рекламной кампании #1 или #2
	* $arFilter = array(
	*     "ADV" =&gt; "1 | 2"
	*     );
	* 
	* // получим список записей
	* $rs = <b>CPath::GetList</b>(
	*     "",
	*     "COUNTER_FULL_PATH",
	*     ($by = "s_counter"), 
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#path">Термин "Полный путь"</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#path_step">Термин "Отрезок
	* пути"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cpath/getlist.php
	* @author Bitrix
	*/
	public static function GetList($PARENT_ID="", $COUNTER_TYPE="COUNTER_FULL_PATH", &$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		if ($COUNTER_TYPE!="COUNTER_FULL_PATH") $COUNTER_TYPE = "COUNTER";
		$arSqlSearch = Array();
		$strSqlSearch = "";
		$counter = "P.".$COUNTER_TYPE;
		$where_counter = "and P.".$COUNTER_TYPE.">0";
		if (strlen($PARENT_ID)<=0 && $COUNTER_TYPE=="COUNTER")
		{
			$where_parent = "and (P.PARENT_PATH_ID is null or ".$DB->Length("P.PARENT_PATH_ID")."<=0)";
		}
		elseif ($COUNTER_TYPE=="COUNTER")
		{
			$where_parent = "and P.PARENT_PATH_ID = '".$DB->ForSql($PARENT_ID)."'";
		}
		if (is_array($arFilter))
		{
			if (strlen($arFilter["ADV"])>0)
			{
				$from_adv = " , b_stat_path_adv A ";
				$where_adv = "and A.PATH_ID = P.PATH_ID and A.DATE_STAT = P.DATE_STAT ";
				$ADV_EXIST = "Y";
				if ($arFilter["ADV_DATA_TYPE"]=="B")
				{
					$counter = $DB->IsNull("A.".$COUNTER_TYPE."_BACK","0");
					$where_counter = "and ".$counter.">0";
				}
				elseif ($arFilter["ADV_DATA_TYPE"]=="P")
				{
					$counter = $DB->IsNull("A.".$COUNTER_TYPE,"0");
					$where_counter = "and ".$counter.">0";
				}
				elseif ($arFilter["ADV_DATA_TYPE"]=="S")
				{
					$counter = $DB->IsNull("A.".$COUNTER_TYPE,"0")." + ".$DB->IsNull("A.".$COUNTER_TYPE."_BACK","0");
					$where_counter = "and (".$counter.")>0";
				}
			}
			else
			{
				$from_adv = "";
				$where_adv = "";
				$ADV_EXIST = "N";
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
					case "PATH_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("P.PATH_ID", $val, $match);
						break;
					case "DATE1":
						if (CheckDateTime($val))
						{
							$arSqlSearch[] = "P.DATE_STAT >= ".$DB->CharToDateFunction($val, "SHORT");
							if ($ADV_EXIST=="Y")
								$arSqlSearch[] = "A.DATE_STAT >= ".$DB->CharToDateFunction($val, "SHORT");
						}
						break;
					case "DATE2":
						if (CheckDateTime($val))
						{
							$arSqlSearch[] = "P.DATE_STAT < ".CStatistics::DBDateAdd($DB->CharToDateFunction($val, "SHORT"), 1);
							if ($ADV_EXIST=="Y")
								$arSqlSearch[] = "A.DATE_STAT < ".CStatistics::DBDateAdd($DB->CharToDateFunction($val, "SHORT"), 1);
						}
						break;
					case "FIRST_PAGE":
					case "LAST_PAGE":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("P.".$key,$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "FIRST_PAGE_SITE_ID":
					case "LAST_PAGE_SITE_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("P.".$key, $val, $match);
						break;
					case "FIRST_PAGE_404":
					case "LAST_PAGE_404":
						$arSqlSearch[] = ($val=="Y") ? "P.".$key."='Y'" : "P.".$key."='N'";
						break;
					case "PAGE":
						$arSqlSearch[] = GetFilterQuery("P.PAGES", $val, "Y", array("/","\\",".","?","#",":"));
						break;
					case "PAGE_SITE_ID":
						$arSqlSearch[] = GetFilterQuery("P.PAGES", "[".$val."]", "Y", array("[","]"));
						break;
					case "PAGE_404":
						$arSqlSearch[] = ($val=="Y") ? "P.PAGES like '%ERROR_404:%'" : "P.PAGES not like '%ERROR_404:%'";
						break;
					case "ADV":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("A.ADV_ID",$val,$match);
						break;
					case "STEPS1":
						$arSqlSearch[] = "P.STEPS>='".intval($val)."'";
						break;
					case "STEPS2":
						$arSqlSearch[] = "P.STEPS<='".intval($val)."'";
						break;
				}
			}
		}
		if ($COUNTER_TYPE=="COUNTER")
		{
			$select1 = "P.LAST_PAGE, P.LAST_PAGE_404, P.LAST_PAGE_SITE_ID";
		}
		elseif($COUNTER_TYPE=="COUNTER_FULL_PATH")
		{
			$select1 = "P.PAGES";
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		if ($by == "s_last_page" && $COUNTER_TYPE=="COUNTER")				$strSqlOrder = "ORDER BY P.LAST_PAGE";
		elseif ($by == "s_pages" && $COUNTER_TYPE=="COUNTER_FULL_PATH")		$strSqlOrder = "ORDER BY P.PAGES";
		elseif ($by == "s_counter")	$strSqlOrder = "ORDER BY COUNTER";
		else
		{
			$by = "s_counter";
			$strSqlOrder = "ORDER BY COUNTER desc, ".$select1;
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$strSql = "
			SELECT /*TOP*/
				P.PATH_ID,
				$select1,
				sum($counter) as COUNTER
			FROM
				b_stat_path P
				$from_adv
			WHERE
			$strSqlSearch
			$where_parent
			$where_adv
			$where_counter
			GROUP BY P.PATH_ID, $select1
			$strSqlOrder
		";

		$res = $DB->Query(CStatistics::DBTopSql($strSql), false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}

	
	/**
	* <p>По указанному ID записи, метод возвращает данные из таблицы, хранящей статистическую информацию как по <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#path">полным путям</a>, так и по <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#path_step">отрезкам путей</a> в разрезе по дням.</p>
	*
	*
	* @param int $id  ID записи из таблицы. </h
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $path_id = 1;
	* if ($rs = <b>CPath::GetByID</b>($path_id))
	* {
	*     $ar = $rs-&gt;Fetch();
	*     // выведем параметры отрезка пути
	*     echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#path">Термин "Полный путь"</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#path_step">Термин "Отрезок
	* пути"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cpath/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$strSql = "SELECT /*TOP*/ * FROM b_stat_path WHERE PATH_ID = '".$DB->ForSql($ID)."'";
		return $DB->Query(CStatistics::DBTopSql($strSql, 1), false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}
}
?>
