<?

/**
 * <b>CReferer</b> - класс для получения данных о <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#referer">ссылающихся сайтах</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/creferer/index.php
 * @author Bitrix
 */
class CReferer
{
	
	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#referer">ссылающихся сайтов (страниц)</a>.</p>
	*
	*
	* @param string &$by = "s_id" Поле для сортировки. В зависимости от группировки списка, набор
	* доступных значений данной переменной может быть различным. <ul>
	* <li>при группировке по ссылающейся странице (<i>filter</i>["<b>GROUP</b>"]="U"): <ul>
	* <li> <b>s_url_from</b> - ссылающаяся страница; </li> <li> <b>s_quantity</b> - количество
	* заходов с ссылающейся страницы; </li> <li> <b>s_average_hits</b> - среднее
	* количество <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хитов</a>,
	* производимое посетителями заходящими с той или ссылающейся
	* страницы. </li> </ul> </li> <li>при группировке по ссылающемуся домену
	* (<i>filter</i>["<b>GROUP</b>"]="S"): <ul> <li> <b>s_url_from</b> - ссылающийся домен; </li> <li>
	* <b>s_quantity</b> - количество заходов с ссылающегося домена; </li> <li>
	* <b>s_average_hits</b> - среднее количество хитов, производимое
	* посетителями. </li> </ul> </li> <li>когда группировка не установлена: <ul> <li>
	* <b>s_id</b> - ID записи; </li> <li> <b>s_site_id</b> - ID сайта, на который пришли; </li> <li>
	* <b>s_url_from</b> - ссылающаяся страница (с которой пришли); </li> <li> <b>s_url_to</b>
	* - страница на которую пришли; </li> <li> <b>s_date_hit</b> - дата; </li> <li>
	* <b>s_session_id</b> - ID сессии. </li> </ul> </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b> - ID записи; </li> <li>
	* <b>ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по <b>ID</b>
	* будет искаться вхождение; </li> <li> <b>SESSION_ID</b> - ID сессии; </li> <li>
	* <b>SESSION_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>SESSION_ID</b> будет искаться вхождение; </li> <li> <b>DATE1</b> - начальное
	* значение интервала для поля "дата"; </li> <li> <b>DATE2</b> - конечное
	* значение интервала для поля "дата"; </li> <li> <b>FROM_PROTOCOL</b> - протокол
	* ссылающейся страницы; </li> <li> <b>FROM_PROTOCOL_EXACT_MATCH</b> - если значение
	* равно "Y", то при фильтрации по <b>FROM_PROTOCOL</b> будет искаться точное
	* совпадение; </li> <li> <b>FROM_DOMAIN</b> - домен ссылающейся страницы; </li> <li>
	* <b>FROM_DOMAIN_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>FROM_DOMAIN</b> будет искаться точное совпадение; </li> <li> <b>FROM_PAGE</b> -
	* ссылающаяся страница; </li> <li> <b>FROM_PAGE_EXACT_MATCH</b> - если значение равно
	* "Y", то при фильтрации по <b>FROM_PAGE</b> будет искаться точное
	* совпадение; </li> <li> <b>FROM</b> - протокол + домен + ссылающаяся страница;
	* </li> <li> <b>FROM_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>FROM</b> будет искаться точное совпадение; </li> <li> <b>TO</b>* - страница на
	* которую пришли; </li> <li> <b>TO_EXACT_MATCH</b> - если значение равно "Y", то при
	* фильтрации по <b>TO</b> будет искаться точное совпадение; </li> <li>
	* <b>TO_404</b> - была ли <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#404">404 ошибка</a>
	* на странице, на которую пришли, возможные значения: <ul> <li> <b>Y</b> -
	* была; </li> <li> <b>N</b> - не была. </li> </ul> </li> <li> <b>SITE_ID</b> - ID сайта на который
	* пришли; </li> <li> <b>GROUP</b> - группировка результирующего списка;
	* возможные значения: <ul> <li> <b>S</b> - группировка по ссылающемуся
	* домену (сайту); </li> <li> <b>U</b> - группировка по ссылающейся странице.
	* </li> </ul> </li> </ul> * - допускается <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности результирующего списка. Если значение
	* равно "true", то список был отфильтрован.
	*
	* @param int &$total  Суммарные количество заходов с ссылающихся страниц.
	*
	* @param string &$group_by  Группировка результирующего списка. Возможные значения: <ul> <li>
	* <b>U</b> - группировка по ссылающейся странице; </li> <li> <b>S</b> -
	* группировка по ссылающемуся домену. </li> </ul>
	*
	* @param int &$max  Количество заходов с самой популярной ссылающейся страницы.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // отфильтруем только заходы с доменов "google"
	* // сгруппировав по ссылающемуся домену
	* $arFilter = array(
	*     "FROM_DOMAIN"  =&gt; "google",
	*     "GROUP"        =&gt; "S"
	*     );
	* 
	* // получим список записей
	* $rs = <b>CReferer::GetList</b>(
	*     ($by = "s_url_from"), 
	*     ($order = "desc"), 
	*     $arFilter, 
	*     $is_filtered,
	*     $total,
	*     $group_by,
	*     $max
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#referer">Термин "Ссылающийся
	* сайт (страница)"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/creferer/getlist.php
	* @author Bitrix
	*/
	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered, &$total, &$grby, &$max)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		global $grby, $total;
		$DB = CDatabase::GetModuleConnection('statistic');
		$group = false;
		$strSqlGroup =  "GROUP BY L.PROTOCOL, L.SITE_NAME, L.URL_FROM, R.HITS, R.SESSIONS";
		$url_from = $DB->Concat("L.PROTOCOL", "L.SITE_NAME", "L.URL_FROM");
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
					case "SESSION_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("L.".$key,$val,$match);
						break;
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "L.DATE_HIT >= ".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "L.DATE_HIT < ".CStatistics::DBDateAdd($DB->CharToDateFunction($val, "SHORT"), 1);
						break;
					case "FROM_PROTOCOL":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("L.PROTOCOL",$val,$match,array("/","\\",":"));
						break;
					case "FROM_DOMAIN":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("L.SITE_NAME",$val,$match,array("."));
						break;
					case "FROM_PAGE":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("L.URL_FROM",$val,$match,array("/","\\",".","?","#",":",":"));
						break;
					case "FROM":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery($url_from,$val, $match, array("/","\\",".","?","#",":"), "N", "N");
						break;
					case "TO":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("L.URL_TO",$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "TO_404":
						$arSqlSearch[] = ($val=="Y") ? "L.URL_TO_404='Y'" : "L.URL_TO_404='N'";
						break;
					case "GROUP":
						$group = true;
						if ($val=="S")
						{
							$find_group="S";
							$strSqlGroup = "GROUP BY L.SITE_NAME, R.HITS, R.SESSIONS";
							$url_from = "L.SITE_NAME";
						}
						else $find_group="U";
						break;
					case "SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("L.SITE_ID", $val, $match);
						break;
				}
			}
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$grby = ($find_group=="U" || $find_group=="S") ? $find_group : "";
		$strSqlOrder = "";
		if (strlen($grby)<=0)
		{
			if ($by == "s_id")					$strSqlOrder = " ORDER BY L.ID ";
			elseif ($by == "s_site_id")			$strSqlOrder = " ORDER BY L.SITE_ID ";
			elseif ($by == "s_url_from")		$strSqlOrder = " ORDER BY URL_FROM ";
			elseif ($by == "s_url_to")			$strSqlOrder = " ORDER BY L.URL_TO ";
			elseif ($by == "s_date_hit")		$strSqlOrder = " ORDER BY L.DATE_HIT ";
			elseif ($by == "s_session_id")		$strSqlOrder = " ORDER BY L.SESSION_ID ";
			else
			{
				$by = "s_id";
				$strSqlOrder = "ORDER BY L.ID";
			}
			if ($order!="asc")
			{
				$strSqlOrder .= " desc ";
				$order="desc";
			}

			$strSql = "
				SELECT /*TOP*/
					".$url_from." as URL_FROM,
					L.ID,
					L.SESSION_ID,
					L.SITE_ID,
					".$DB->DateToCharFunction("L.DATE_HIT")." DATE_HIT,
					L.URL_TO,
					L.URL_TO_404
				FROM
					b_stat_referer_list L
				WHERE
				".$strSqlSearch."
				".$strSqlOrder."
			";
		}
		elseif (IsFiltered($strSqlSearch) || $grby=="U")
		{
			if ($by == "s_url_from")			$strSqlOrder = "ORDER BY URL_FROM";
			elseif ($by == "s_quantity")		$strSqlOrder = "ORDER BY QUANTITY";
			elseif ($by == "s_average_hits")	$strSqlOrder = "ORDER BY AVERAGE_HITS";
			else
			{
				$by = "s_quantity";
				$strSqlOrder = "ORDER BY QUANTITY";
			}
			if ($order!="asc")
			{
				$strSqlOrder .= " desc ";
				$order="desc";
			}
			$strSql = "
				SELECT
					count(L.ID) as COUNTER
				FROM
					b_stat_referer_list L
					LEFT JOIN b_stat_referer R ON (R.ID = L.REFERER_ID)
				WHERE
				".$strSqlSearch."
				".$strSqlGroup."
			";
			$c = $DB->Query($strSql, false, $err_mess.__LINE__);
			$total = 0;
			$arrCount = array();
			while ($cr = $c->Fetch())
			{
				$total += intval($cr["COUNTER"]);
				$arrCount[] = intval($cr["COUNTER"]);
			}
			if (count($arrCount)>0)
				$max = max($arrCount);
			$strSql = "
				SELECT /*TOP*/
					".$url_from." URL_FROM,
					count(L.ID) QUANTITY,
					(count(L.ID)*100)/$total C_PERCENT,
					R.HITS/R.SESSIONS AVERAGE_HITS
				FROM
					b_stat_referer_list L
					LEFT JOIN b_stat_referer R ON (R.ID = L.REFERER_ID)
				WHERE
				".$strSqlSearch."
				".$strSqlGroup."
				".$strSqlOrder."
			";
		}
		elseif($grby=="S")
		{
			if ($by == "s_url_from")			$strSqlOrder = "ORDER BY URL_FROM";
			elseif ($by == "s_quantity")		$strSqlOrder = "ORDER BY QUANTITY";
			elseif ($by == "s_average_hits")	$strSqlOrder = "ORDER BY AVERAGE_HITS";
			else
			{
				$by = "s_quantity";
				$strSqlOrder = "ORDER BY QUANTITY";
			}
			if ($order!="asc")
			{
				$strSqlOrder .= " desc ";
				$order="desc";
			}
			$strSql = "SELECT sum(R.SESSIONS) TOTAL, max(R.SESSIONS) MAX FROM b_stat_referer R";
			$c = $DB->Query($strSql, false, $err_mess.__LINE__);
			$cr = $c->Fetch();
			$total = intval($cr["TOTAL"]);
			$max = intval($cr["MAX"]);
			$strSql = "
				SELECT /*TOP*/
					R.SITE_NAME URL_FROM,
					sum(R.SESSIONS) QUANTITY,
					(sum(R.SESSIONS)*100)/$total C_PERCENT,
					sum(R.HITS)/sum(R.SESSIONS) AVERAGE_HITS
				FROM
					b_stat_referer R
				GROUP BY R.SITE_NAME
				".$strSqlOrder."
			";
		}

		$res = $DB->Query(CStatistics::DBTopSql($strSql), false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch) || $group);
		return $res;
	}
}
?>
