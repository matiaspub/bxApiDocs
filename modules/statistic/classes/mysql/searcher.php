<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/classes/general/searcher.php");

/**
 * <b>CSearcher</b> - класс для работы с <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search">поисковыми системами</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/csearcher/index.php
 * @author Bitrix
 */
class CSearcher extends CAllSearcher
{
	public static function GetGraphArray_SQL($strSqlSearch)
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$strSql = "
			SELECT
				".$DB->DateToCharFunction("D.DATE_STAT","SHORT")." DATE_STAT,
				DAYOFMONTH(D.DATE_STAT) DAY,
				MONTH(D.DATE_STAT) MONTH,
				YEAR(D.DATE_STAT) YEAR,
				D.SEARCHER_ID,
				D.TOTAL_HITS,
				C.NAME
			FROM
				b_stat_searcher_day D
			INNER JOIN b_stat_searcher C ON (C.ID = D.SEARCHER_ID)
			WHERE
				$strSqlSearch
			ORDER BY
				D.DATE_STAT, D.SEARCHER_ID
			";
		return $strSql;
	}

	
	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search">поисковых систем</a> и количество <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search_hit">хитов</a> (проиндексированных страниц) каждой из них за все время ведения статистики, за последние 3 дня, либо за указанный интервал времени.</p>
	*
	*
	* @param string &$by = "s_today_hits" Поле для сортировки. Возможные значения: <ul> <li> <b>s_id</b> - ID поисковой
	* системы; </li> <li> <b>s_date_last</b> - дата последнего хита; </li> <li> <b>s_today_hits</b> -
	* количество хитов за сегодня; </li> <li> <b>s_yesterday_hits</b> - количество
	* хитов за вчера; </li> <li> <b>s_b_yesterday_hits</b> - количество хитов за
	* позавчера; </li> <li> <b>s_total_hits</b> - суммарное количество хитов; </li> <li>
	* <b>s_period_hits</b> - количество хитов за установленный период времени
	* (<i>filter</i>["<b>DATE1</b>"], <i>filter</i>["<b>DATE2</b>"]); </li> <li> <b>s_name</b> - название
	* поисковой системы; </li> <li> <b>s_user_agent</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search_useragent">UserAgent поисковой
	* системы</a>. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID поисковой системы; </li>
	* <li> <b>ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по <b>ID</b>
	* будет искаться вхождение </li> <li> <b>ACTIVE</b> - флаг активности,
	* возможные значения: <ul> <li> <b>Y</b> - активна; </li> <li> <b>N</b> - не активна.
	* </li> </ul> </li> <li> <b>SAVE_STATISTIC</b> - флаг "сохранять хиты поисковой системы",
	* возможные значения: <ul> <li> <b>Y</b> - да; </li> <li> <b>N</b> - нет. </li> </ul> </li> <li>
	* <b>DIAGRAM_DEFAULT</b> - флаг "включать в круговую диаграмму и график по
	* умолчанию", возможные значения: <ul> <li> <b>Y</b> - да; </li> <li> <b>N</b> - нет. </li>
	* </ul> </li> <li> <b>HITS1</b> - начальное значение интервала для поля
	* "количество хитов"; </li> <li> <b>HITS2</b> - конечное значение интервала для
	* поля "количество хитов"; </li> <li> <b>DATE1_PERIOD</b> - начальное значение
	* значение для произвольного периода; </li> <li> <b>DATE2_PERIOD</b> - конечное
	* значение значение для произвольного периода; </li> <li> <b>DATE1</b> -
	* начальное значение интервала для поля "дата последнего хита
	* поисковой системы"; </li> <li> <b>DATE2</b> - конечное значение интервала
	* для поля "дата последнего хита поисковой системы"; </li> <li> <b>NAME</b>* -
	* наименование поисковой системы; </li> <li> <b>NAME_EXACT_MATCH</b> - если
	* значение равно "Y", то при фильтрации по <b>NAME</b> будет искаться
	* точное совпадение; </li> <li> <b>USER_AGENT</b>* - UserAgent поисковой системы; </li>
	* <li> <b>USER_AGENT_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>USER_AGENT</b> будет искаться точное совпадение. </li> </ul> * - допускается
	* <a href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности списка поисковых систем. Если значение
	* равно "true", то список был отфильтрован.
	*
	* @param mixed $limit = false Максимальное количество поисковых систем которые будут выбраны
	* в списке. Если значение равно false, то кол-во РК будет ограничено в
	* соответствии со значением параметра "Максимальное кол-во
	* показываемых записей в таблицах" из настроек модуля "Статистика".
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // отфильтруем данные только для поисковой системы #20 и #21
	* // а также получим дополнительные данные на декабрь 2005 года
	* $arFilter = array(
	*     "ID"           =&gt; "20 | 21",
	*     "DATE1_PERIOD" =&gt; "01.12.2005",
	*     "DATE2_PERIOD" =&gt; "31.12.2005",
	*     );
	* 
	* // получим список записей
	* $rs = <b>CSearcher::GetList</b>(
	*     ($by = "s_today_hits"), 
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
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/csearcher/getdropdownlist.php">CSearcher::GetDropdownList</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search">Термин "Поисковая
	* система"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/csearcher/getlist.php
	* @author Bitrix
	*/
	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered, $LIMIT=false)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array("S.ID <> 1");
		$arSqlSearch_h = Array();
		$strSqlSearch_h = "";
		$filter_period = false;
		$strSqlPeriod = "";
		$strT = "";
		if (is_array($arFilter))
		{
			ResetFilterLogic();
			$date1 = $arFilter["DATE1_PERIOD"];
			$date2 = $arFilter["DATE2_PERIOD"];
			$date_from = MkDateTime(ConvertDateTime($date1,"D.M.Y"),"d.m.Y");
			$date_to = MkDateTime(ConvertDateTime($date2,"D.M.Y")." 23:59","d.m.Y H:i");
			if (CheckDateTime($date1) && strlen($date1)>0)
			{
				$filter_period = true;
				if (strlen($date2)>0)
				{
					$strSqlPeriod = "sum(if(D.DATE_STAT<FROM_UNIXTIME('$date_from'),0, if(D.DATE_STAT>FROM_UNIXTIME('$date_to'),0,";
					$strT=")))";
				}
				else
				{
					$strSqlPeriod = "sum(if(D.DATE_STAT<FROM_UNIXTIME('$date_from'),0,";
					$strT="))";
				}
			}
			elseif (CheckDateTime($date2) && strlen($date2)>0)
			{
				ResetFilterLogic();
				$filter_period = true;
				$strSqlPeriod = "sum(if(D.DATE_STAT>FROM_UNIXTIME('$date_to'),0,";
				$strT="))";
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
						$arSqlSearch[] = GetFilterQuery("S.ID",$val,$match);
						break;
					case "ACTIVE":
					case "SAVE_STATISTIC":
					case "DIAGRAM_DEFAULT":
						$arSqlSearch[] = ($val=="Y") ? "S.".$key."='Y'" : "S.".$key."='N'";
						break;
					case "HITS1":
						$arSqlSearch_h[] = "(sum(ifnull(D.TOTAL_HITS,0))+ifnull(S.TOTAL_HITS,0))>='".intval($val)."'";
						break;
					case "HITS2":
						$arSqlSearch_h[] = "(sum(ifnull(D.TOTAL_HITS,0))+ifnull(S.TOTAL_HITS,0))<='".intval($val)."'";
						break;
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch_h[] = "max(D.DATE_LAST)>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch_h[] = "max(D.DATE_LAST)<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "NAME":
					case "USER_AGENT":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.".$key, $val, $match);
						break;
				}
			}
		}

		if ($by == "s_id")
			$strSqlOrder = "ORDER BY S.ID";
		elseif ($by == "s_date_last")
			$strSqlOrder = "ORDER BY S_DATE_LAST";
		elseif ($by == "s_today_hits")
			$strSqlOrder = "ORDER BY TODAY_HITS";
		elseif ($by == "s_yesterday_hits")
			$strSqlOrder = "ORDER BY YESTERDAY_HITS";
		elseif ($by == "s_b_yesterday_hits")
			$strSqlOrder = "ORDER BY B_YESTERDAY_HITS";
		elseif ($by == "s_total_hits")
			$strSqlOrder = "ORDER BY TOTAL_HITS";
		elseif ($by == "s_period_hits")
			$strSqlOrder = "ORDER BY PERIOD_HITS";
		elseif ($by == "s_name")
			$strSqlOrder = "ORDER BY S.NAME";
		elseif ($by == "s_user_agent")
			$strSqlOrder = "ORDER BY S.USER_AGENT";
		elseif ($by == "s_chart")
			$strSqlOrder = "ORDER BY S.DIAGRAM_DEFAULT desc, TOTAL_HITS ";
		elseif ($by == "s_stat")
			$strSqlOrder = "ORDER BY TODAY_HITS desc, YESTERDAY_HITS desc, B_YESTERDAY_HITS desc, TOTAL_HITS desc, PERIOD_HITS";
		else
		{
			$by = "s_today_hits";
			$strSqlOrder = "ORDER BY TODAY_HITS desc, YESTERDAY_HITS desc, B_YESTERDAY_HITS desc, TOTAL_HITS desc, PERIOD_HITS";
		}

		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$limit_sql = "LIMIT ".intval(COption::GetOptionString('statistic','RECORDS_LIMIT'));
		if (intval($LIMIT)>0)
			$limit_sql = "LIMIT ".intval($LIMIT);

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		foreach($arSqlSearch_h as $sqlWhere)
			$strSqlSearch_h .= " and (".$sqlWhere.") ";

		$strSql =	"
		SELECT
			S.ID,
			S.TOTAL_HITS,
			S.USER_AGENT,
			S.DIAGRAM_DEFAULT,
			".$DB->DateToCharFunction("max(D.DATE_LAST)")."						DATE_LAST,
			max(ifnull(D.DATE_LAST,'1980-01-01'))								S_DATE_LAST,
			sum(ifnull(D.TOTAL_HITS,0))+ifnull(S.TOTAL_HITS,0)					TOTAL_HITS,
			sum(if(to_days(curdate())=to_days(D.DATE_STAT),ifnull(D.TOTAL_HITS,0),0))	TODAY_HITS,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=1,ifnull(D.TOTAL_HITS,0),0))	YESTERDAY_HITS,
			sum(if(to_days(curdate())-to_days(D.DATE_STAT)=2,ifnull(D.TOTAL_HITS,0),0))	B_YESTERDAY_HITS,
			".($filter_period ? $strSqlPeriod.'ifnull(D.TOTAL_HITS,0)'.$strT.' PERIOD_HITS, '	: '0 PERIOD_HITS,')."
			S.NAME
		FROM
			b_stat_searcher S
		LEFT JOIN b_stat_searcher_day D ON (D.SEARCHER_ID = S.ID)
		WHERE
		$strSqlSearch
		and S.ID<>1
		GROUP BY S.ID
		HAVING
			'1'='1'
			$strSqlSearch_h
		$strSqlOrder
		$limit_sql
		";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch) || $filter_period || strlen($strSqlSearch_h)>0);
		return $res;
	}

	public static function GetDropDownList($strSqlOrder="ORDER BY NAME, ID")
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$strSql = "
			SELECT
				ID as REFERENCE_ID,
				concat(ifnull(NAME,''),' [',ID,']') as REFERENCE
			FROM
				b_stat_searcher
			WHERE
				ID <> 1
			$strSqlOrder
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	
	/**
	* <p>Возвращает количество <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search_hit">хитов</a> (проиндексированных страниц), для указанной <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search">поисковой системы</a> в разрезе по дням.</p>
	*
	*
	* @param int $searcher_id  ID поисковой системы. </ht
	*
	* @param string &$by = "s_date" Поле для сортировки. Возможные значения: <ul><li> <b>s_date</b> - дата. </li></ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array &$max_min  Ссылка на массив содержащий максимальную и минимальную даты
	* результирующего списка. Структура данного массива: <pre> Array (
	* [DATE_FIRST] =&gt; минимальная дата [MIN_DAY] =&gt; номер дня для минимальной
	* даты (1-31) [MIN_MONTH] =&gt; номер месяца для минимальной даты (1-12) [MIN_YEAR] =&gt;
	* номер года для минимальной даты [DATE_LAST] =&gt; максимальная дата [MAX_DAY]
	* =&gt; номер дня для максимальной даты (1-31) [MAX_MONTH] =&gt; номер месяца для
	* максимальной даты (1-12) [MAX_YEAR] =&gt; номер года для максимальной даты
	* )</pre>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>DATE1</b> - начальное значение
	* интервала для поля "дата"; </li> <li> <b>DATE2</b> - конечное значение
	* интервала для поля "дата". </li> </ul>
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $searcher_id = 1;
	* 
	* // установим фильтр на декабрь 2005 года
	* $arFilter = array(
	*     "DATE1" =&gt; "01.12.2005",
	*     "DATE2" =&gt; "31.12.2005"
	*     );
	* 
	* // получим набор записей
	* $rs = <b>CSearcher::GetDynamicList</b>(
	*     $searcher_id, 
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
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search_hit">Термин "Хит
	* поисковой системы"</a> </li></ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/csearcher/getdynamiclist.php
	* @author Bitrix
	*/
	public static function GetDynamicList($SEARCHER_ID, &$by, &$order, &$arMaxMin, $arFilter=Array())
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$SEARCHER_ID = intval($SEARCHER_ID);
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

		if ($by == "s_date") $strSqlOrder = "ORDER BY D.DATE_STAT";
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
		$strSql =	"
			SELECT
				".$DB->DateToCharFunction("D.DATE_STAT","SHORT")."		DATE_STAT,
				DAYOFMONTH(D.DATE_STAT)									DAY,
				MONTH(D.DATE_STAT)										MONTH,
				YEAR(D.DATE_STAT)										YEAR,
				D.TOTAL_HITS
			FROM
				b_stat_searcher_day D
			WHERE
				D.SEARCHER_ID = $SEARCHER_ID
			$strSqlSearch
			$strSqlOrder
			";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		$strSql = "
			SELECT
				max(D.DATE_STAT)				DATE_LAST,
				min(D.DATE_STAT)				DATE_FIRST,
				DAYOFMONTH(max(D.DATE_STAT))	MAX_DAY,
				MONTH(max(D.DATE_STAT))			MAX_MONTH,
				YEAR(max(D.DATE_STAT))			MAX_YEAR,
				DAYOFMONTH(min(D.DATE_STAT))	MIN_DAY,
				MONTH(min(D.DATE_STAT))			MIN_MONTH,
				YEAR(min(D.DATE_STAT))			MIN_YEAR
			FROM
				b_stat_searcher_day D
			WHERE
				D.SEARCHER_ID = $SEARCHER_ID
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
}
?>
