<?

/**
 * <b>CPage</b> - класс для получения данных о посещенных страницах сайта. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cpage/index.php
 * @author Bitrix
 */
class CPage
{
	
	/**
	* <p>Возвращает данные по посещаемости указанной страницы (каталогу) в разрезе по дням.</p>
	*
	*
	* @param string $url  Полный путь к странице (каталогу) по которой необходимо получить
	* данные.
	*
	* @param string &$by = "s_date" Порядок сортировки. Возможные значения: <ul> <li> <b>s_date</b> - дата. </li>
	* </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>DATE1</b> - начальное значение
	* интервала даты; </li> <li> <b>DATE2</b> - конечное значение интервала даты;
	* </li> <li> <b>ADV</b>* - ID <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">рекламной
	* кампании</a> (РК), данное поле позволяет отфильтровать только те
	* страницы (каталоги) которые были открыты только посетителями по
	* данной РК и соответственно получить данные по посещаемости
	* страницы (каталога) <i>url</i> только этих посетителей; </li> <li>
	* <b>ADV_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по <b>ADV</b>
	* будет искаться вхождение; </li> <li> <b>ADV_DATA_TYPE</b> - флаг "<a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_back">возврат</a> или <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_first">прямой заход</a> по
	* рекламной кампании" (используется только если указано
	* <i>filter</i>["<b>ADV</b>"]), возможные значения: <ul> <li> <b>B</b> - показывать
	* данные по посетителям только на возврате по РК; </li> <li> <b>P</b> -
	* показывать данные по посетителям только на прямом заходе по РК.
	* </li> </ul> </li> <li> <b>IS_DIR</b> - показывать данные по разделам или
	* страницам. Для фильтрации разделов требуется указать значение Y.
	* Для страниц - N; </li> <br> Если не указать ни одно из вышеперечисленных
	* значений, то данные будут показываться в сумме как по прямому
	* заходу так и по возврату. <br> </ul> * - допускается <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a>
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $url = "http://www.bitrixsoft.ru/about/index.php";
	* 
	* // установим фильтр на декабрь 2007 года 
	* // по прямым заходам с рекламной кампании 1 либо 2
	* $arFilter = array(
	*     "DATE1" =&gt; "01.12.2007",
	*     "DATE2" =&gt; "31.12.2007",
	*     "ADV"   =&gt; "1 | 2",
	*     "ADV_DATA_TYPE" =&gt; "P"
	*     );
	* 
	* // получим набор записей
	* $rs = <b>CPage::GetDynamicList</b>(
	*     $url, 
	*     ($by="s_date"), 
	*     ($order="desc"), 
	*     $arFilter, 
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#enter">Термин "Точка входа"</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#exit">Термин "Точка
	* выхода"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cpage/getdynamiclist.php
	* @author Bitrix
	*/
	public static function GetDynamicList($URL, &$by, &$order, $arFilter=Array())
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();
		$from_adv = "";
		$where_adv = "";
		$counter = "SUM(D.COUNTER)";
		$enter_counter = "SUM(D.ENTER_COUNTER)";
		$exit_counter = "SUM(if(D.EXIT_COUNTER>0,D.EXIT_COUNTER,0))";
		if (is_array($arFilter))
		{
			if (strlen($arFilter["ADV"])>0)
			{
				$from_adv = " , b_stat_page_adv A ";
				$where_adv = "and A.PAGE_ID = D.ID";

				if ($arFilter["ADV_DATA_TYPE"]=="B")
				{
					$counter = "SUM(A.COUNTER_BACK)";
					$enter_counter = "SUM(A.ENTER_COUNTER_BACK)";
					$exit_counter = "SUM(if(A.EXIT_COUNTER_BACK>0,A.EXIT_COUNTER_BACK,0))";
				}
				elseif ($arFilter["ADV_DATA_TYPE"]=="P")
				{
					$counter = "SUM(A.COUNTER)";
					$enter_counter = "SUM(A.ENTER_COUNTER)";
					$exit_counter = "SUM(if(A.EXIT_COUNTER>0,A.EXIT_COUNTER,0))";
				}
				else
				{
					$counter = "SUM(A.COUNTER + A.COUNTER_BACK)";
					$enter_counter = "SUM(A.ENTER_COUNTER + A.ENTER_COUNTER_BACK)";
					$exit_counter = "SUM(if(A.EXIT_COUNTER>0,A.EXIT_COUNTER,0) + if(A.EXIT_COUNTER_BACK>0,A.EXIT_COUNTER_BACK,0))";
				}
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
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "ADV":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("A.ADV_ID",$val,$match);
						break;
					case "IS_DIR":
						$arSqlSearch[] = ($val=="Y") ? "D.DIR = 'Y'" : "D.DIR = 'N'";
						break;
				}
			}
		}

		if ($by == "s_date")
			$strSqlOrder = "ORDER BY D.DATE_STAT";
		else
		{
			$by = "s_date";
			$strSqlOrder = "ORDER BY D.DATE_STAT";
		}

		if ($order != "asc")
		{
			$strSqlOrder .= " desc ";
			$order = "desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				".$DB->DateToCharFunction("D.DATE_STAT","SHORT")." DATE_STAT,
				DAYOFMONTH(D.DATE_STAT) DAY,
				MONTH(D.DATE_STAT) MONTH,
				YEAR(D.DATE_STAT) YEAR,
				$counter COUNTER,
				$enter_counter ENTER_COUNTER,
				$exit_counter EXIT_COUNTER
			FROM
				b_stat_page D
				$from_adv
			WHERE
			$strSqlSearch
			and D.URL_HASH = '".crc32ex($URL)."'
			$where_adv
			GROUP BY D.DATE_STAT
			$strSqlOrder
		";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	
	/**
	* <p>Возвращает список посещенных на сайте страниц (каталогов) и данные по их посещаемости.</p>
	*
	*
	* @param string $counter_type = "" Тип счетчика. Возможные значения: <ul> <li> <b>ENTER_COUNTER</b> - кол-во раз
	* когда данная страница (каталог) была <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#enter">точкой входа</a>; </li> <li>
	* <b>EXIT_COUNTER</b> - кол-во раз когда данная страница (каталог) была <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#exit">точкой выхода</a>. </li> </ul> По
	* умолчанию в счетчике хранится общее число хитов по странице
	* (каталогу) (включая и точки входа и точки выхода).
	*
	* @param string &$by = "s_last_date" Порядок сортировки. Возможные значения: <ul> <li> <b>s_url</b> - страница
	* (каталог); </li> <li> <b>s_counter</b> - счетчик. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>DATE1</b> - начальное значение для
	* интервала даты за которую необходимо получить данные; </li> <li>
	* <b>DATE2</b> - конечное значение для интервала даты за которую
	* необходимо получить данные; </li> <li> <b>DIR</b> - флаг "показывать только
	* каталоги или только страницы", возможные значения: <ul> <li> <b>Y</b> - в
	* результирующем списке должны быть только каталоги; </li> <li> <b>N</b> - в
	* результирующем списке должны быть только страницы. </li> </ul> </li> <li>
	* <b>URL</b>* - Полный путь к странице (каталогу) для которой необходимо
	* вывести данные; </li> <li> <b>URL_EXACT_MATCH</b> - если значение равно "Y", то при
	* фильтрации по <b>URL</b> будет искаться точное совпадение; </li> <li>
	* <b>URL_404</b> - была ли <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#404">404 ошибка</a>
	* на странице, возможные значения: <ul> <li> <b>Y</b> - была; </li> <li> <b>N</b> - не
	* было. </li> </ul> Для фильтрации каталогов данное поле не может
	* использоваться. </li> <li> <b>ADV</b>* - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">рекламной кампании</a> (РК),
	* данное поле позволяет отфильтровать только те страницы
	* (каталоги) которые были открыты только посетителями по данной РК
	* и соответственно получить данные по посещаемости страницы
	* (каталога) <i>url</i> только этих посетителей; </li> <li> <b>ADV_EXACT_MATCH</b> - если
	* значение равно "N", то при фильтрации по <b>ADV</b> будет искаться
	* вхождение; </li> <li> <b>ADV_DATA_TYPE</b> - флаг типа данных для рекламной
	* кампании, возможные значения: <ul> <li> <b>P</b> - только по <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_first">прямым заходам</a> по
	* рекламной кампании; </li> <li> <b>B</b> - только по <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_back">возвратам</a> по рекламной
	* кампании; </li> <li> <b>S</b> - сумма по прямым заходам и возвратам. </li> </ul>
	* </li> <li> <b>SITE_ID</b>* - ID сайта; </li> <li> <b>SITE_ID_EXACT_MATCH</b> - если значение равно
	* "N", то при фильтрации по <b>SITE_ID</b> будет искаться вхождение. </li> </ul> *
	* - допускается <a href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная
	* логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности списка страниц (каталогов). Если значение
	* равно "true", то список был отфильтрован.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // получим данные по заданной странице
	* $arFilter = array(
	*     "URL" =&gt; "http://www.bitrixsoft.ru/about/index.php",
	*     "URL_EXACT_MATCH" =&gt; "Y"
	*     );
	* 
	* // получим список записей
	* $rs = <b>CPage::GetList</b>(
	*     "",
	*     ($by = "s_last_date"), 
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#enter">Термин "Точка входа"</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#exit">Термин "Точка
	* выхода"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cpage/getlist.php
	* @author Bitrix
	*/
	public static function GetList($COUNTER_TYPE, &$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		if ($COUNTER_TYPE!="ENTER_COUNTER" && $COUNTER_TYPE!="EXIT_COUNTER")
			$COUNTER_TYPE = "COUNTER";
		$counter = "V.".$COUNTER_TYPE;
		$where_counter = "and V.".$COUNTER_TYPE.">0";
		$arSqlSearch = Array();
		$arSqlSearch_h = Array();
		$strSqlSearch_h = "";
		$from_adv = "";
		$where_adv = "";
		if (is_array($arFilter))
		{
			if (strlen($arFilter["ADV"])>0)
			{
				$from_adv = " , b_stat_page_adv A ";
				$where_adv = "and A.PAGE_ID = V.ID";

				if ($arFilter["ADV_DATA_TYPE"]=="B")
				{
					$counter = "A.".$COUNTER_TYPE."_BACK";
					$where_counter = "and A.".$COUNTER_TYPE."_BACK>0";
				}
				elseif ($arFilter["ADV_DATA_TYPE"]=="S")
				{
					$counter = "if(A.".$COUNTER_TYPE.">0, A.".$COUNTER_TYPE.", 0) + if(A.".$COUNTER_TYPE."_BACK>0, A.".$COUNTER_TYPE."_BACK, 0)";
					$where_counter = "and (A.".$COUNTER_TYPE."_BACK>0 or A.".$COUNTER_TYPE.">0)";
				}
				elseif ($arFilter["ADV_DATA_TYPE"]=="P")
				{
					$counter = "A.".$COUNTER_TYPE;
					$where_counter = "and A.".$COUNTER_TYPE.">0";
				}
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
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "V.DATE_STAT>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "V.DATE_STAT<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "SHOW":
					case "DIR":
						$arSqlSearch[] = ($val=="D") ? "V.DIR='Y'" : "V.DIR='N'";
						break;
					case "SECTION":
					case "URL":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("V.URL",$val,$match,array("/","\\","_",".",":"));
						break;
					case "SECTION_ID":
					case "URL_ID":
						$arSqlSearch[] = "(V.URL like '".$DB->ForSql($val)."' and V.URL<>'".$DB->ForSql($val)."')";
						break;
					case "PAGE_404":
					case "URL_404":
						$arSqlSearch_h[] = ($val=="Y") ? "max(V.URL_404)='Y'" : "max(V.URL_404)='N'";
						break;
					case "ADV":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("A.ADV_ID",$val,$match);
						break;
					case "SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("V.SITE_ID", $val, $match);
						break;
				}
			}
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		foreach($arSqlSearch_h as $sqlWhere)
			$strSqlSearch_h .= " and (".$sqlWhere.") ";

		if ($by == "s_url")
			$strSqlOrder = "ORDER BY V.URL";
		elseif ($by == "s_counter")
			$strSqlOrder = "ORDER BY COUNTER";
		else
		{
			$by = "s_counter";
			$strSqlOrder = "ORDER BY COUNTER desc, V.URL";
		}

		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSql = "
			SELECT
				V.URL,
				V.DIR,
				V.SITE_ID,
				max(V.URL_404) as URL_404,
				sum($counter) as COUNTER
			FROM
				b_stat_page V
				$from_adv
			WHERE
			$strSqlSearch
			$where_adv
			$where_counter
			GROUP BY V.URL, V.DIR
			HAVING
				'1'='1'
				$strSqlSearch_h
			$strSqlOrder
			LIMIT ".intval(COption::GetOptionString('statistic','RECORDS_LIMIT'))."
			";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch) || strlen($strSqlSearch_h)>0);
		return $res;
	}
}
?>
