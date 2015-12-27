<?

/**
 * <b>CSearcherHit</b> - класс для получения данных о <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search_hit">хитах поисковых систем</a> (проиндекированных страниц). 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/csearcherhit/index.php
 * @author Bitrix
 */
class CSearcherHit
{
	
	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search_hit">хитов поисковых систем</a>.</p>
	*
	*
	* @param string &$by = "s_date_hit" Поле для сортировки. Возможные значения: <ul> <li> <b>s_id</b> - ID хита; </li>
	* <li> <b>s_site_id</b> - ID сайта; </li> <li> <b>s_date_hit</b> - дата хита; </li> <li> <b>s_searcher_id</b>
	* - ID <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search">поисковой системы</a>; </li>
	* <li> <b>s_user_agent</b> - <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search_useragent">UserAgent
	* поисковой системы</a>; </li> <li> <b>s_ip</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#ip">IP адрес</a> поисковой системы;
	* </li> <li> <b>s_url</b> - адрес проиндексированной страницы. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID хита; </li> <li> <b>ID_EXACT_MATCH</b> -
	* если значение равно "N", то при фильтрации по <b>ID</b> будет искаться
	* вхождение; </li> <li> <b>SEARCHER_ID</b>* - ID поисковой системы; </li> <li>
	* <b>SEARCHER_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>SEARCHER_ID</b> будет искаться вхождение; </li> <li> <b>URL</b>* - адрес
	* проиндексированной страницы; </li> <li> <b>URL_404</b> - была ли <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#404">404 ошибка</a> на
	* проиндексированной странице: <ul> <li> <b>Y</b> - была; </li> <li> <b>N</b> - не
	* была. </li> </ul> </li> <li> <b>SEARCHER</b>* - название поисковой системы; </li> <li>
	* <b>SEARCHER_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>SEARCHER</b> будет искаться точное совпадение; </li> <li> <b>DATE1</b> -
	* начальное значение интервала для поля "дата хита"; </li> <li> <b>DATE2</b> -
	* конечное значение интервала для поля "дата хита"; </li> <li> <b>IP</b>* - IP
	* адрес поисковой системы; </li> <li> <b>IP_EXACT_MATCH</b> - если значение равно
	* "Y", то при фильтрации по <b>IP</b> будет искаться точное совпадение;
	* </li> <li> <b>USER_AGENT</b>* - UserAgent поисковой системы; </li> <li> <b>USER_AGENT_EXACT_MATCH</b> -
	* если значение равно "Y", то при фильтрации по <b>USER_AGENT</b> будет
	* искаться точное совпадение; </li> <li> <b>SITE_ID</b>* - ID сайта; </li> <li>
	* <b>SITE_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>SITE_ID</b> будет искаться вхождение. </li> </ul> * - допускается <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности результирующего списка. Если значение
	* равно "true", то список был отфильтрован.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // отфильтруем страницы проиндексированные 
	* // поисковой системой #20 и #21
	* $arFilter = array(
	*     "SEARCHER_ID" =&gt; "20 | 21"
	*     );
	* 
	* // получим список записей
	* $rs = <b>CSearcherHit::GetList</b>(
	*     ($by = "s_url"), 
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search_hit">Термин "Хит
	* поисковой системы"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/csearcherhit/getlist.php
	* @author Bitrix
	*/
	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered)
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
					case "ID":
					case "SEARCHER_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("H.".$key, $val, $match);
						break;
					case "URL":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("H.URL", $val, $match, array("/","\\",".","?","#",":"));
						break;
					case "URL_404":
						$arSqlSearch[] = ($val=="Y") ? "H.URL_404='Y'" : "H.URL_404='N'";
						break;
					case "SEARCHER":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.NAME",$val,$match);
						break;
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "H.DATE_HIT >= ".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "H.DATE_HIT < ".CStatistics::DBDateAdd($DB->CharToDateFunction($val, "SHORT"), 1);
						break;
					case "IP":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("H.IP", $val, $match, array("."));
						break;
					case "USER_AGENT":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("H.USER_AGENT", $val, $match);
						break;
					case "SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("H.SITE_ID", $val, $match);
						break;
				}
			}
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		if ($by == "s_id")				$strSqlOrder = "ORDER BY H.ID";
		elseif ($by == "s_site_id")		$strSqlOrder = "ORDER BY H.SITE_ID";
		elseif ($by == "s_date_hit")	$strSqlOrder = "ORDER BY H.DATE_HIT";
		elseif ($by == "s_searcher_id")	$strSqlOrder = "ORDER BY H.SEARCHER_ID";
		elseif ($by == "s_user_agent")	$strSqlOrder = "ORDER BY H.USER_AGENT";
		elseif ($by == "s_ip")			$strSqlOrder = "ORDER BY H.IP";
		elseif ($by == "s_url")			$strSqlOrder = "ORDER BY H.URL ";
		else
		{
			$by = "s_date_hit";
			$strSqlOrder = "ORDER BY H.DATE_HIT";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$strSql = "
			SELECT /*TOP*/
				H.ID, H.SEARCHER_ID, H.URL, H.URL_404, H.IP, H.USER_AGENT, H.HIT_KEEP_DAYS, H.SITE_ID,
				S.NAME SEARCHER_NAME,
				".$DB->DateToCharFunction("H.DATE_HIT")." DATE_HIT
			FROM
				b_stat_searcher_hit H
			INNER JOIN b_stat_searcher S ON (S.ID = H.SEARCHER_ID)
			WHERE
			".$strSqlSearch."
			".$strSqlOrder."
		";

		$res = $DB->Query(CStatistics::DBTopSql($strSql), false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}
}
?>
