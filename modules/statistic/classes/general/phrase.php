<?

/**
 * <b>CPhrase</b> - класс для получения данных по <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#phrase">поисковым фразам</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cphrase/index.php
 * @author Bitrix
 */
class CPhrase
{
	
	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#phrase">поисковых фраз</a> с возможностью группировки по поисковое фразе или <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search">поисковой системе</a>.</p>
	*
	*
	* @param string &$by = "s_id" Поле для сортировки. В зависимости от группировки списка, набор
	* доступных значений данной переменной может быть различным. <ul>
	* <li>при группировке по поисковой фразе (<i>filter</i>["<b>GROUP</b>"]="P"): <ul> <li>
	* <b>s_phrase</b> - поисковая фраза; </li> <li> <b>s_quantity</b> - количество заходов с
	* той или иной поисковой фразой. </li> </ul> </li> <li>при группировке по
	* поисковой системе (<i>filter</i>["<b>GROUP</b>"]="S"): <ul> <li> <b>s_name</b> - поисковая
	* система; </li> <li> <b>s_quantity</b> - количество заходов с данной поисковой
	* системы; </li> <li> <b>s_average_hits</b> - среднее количество <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хитов</a> производимое
	* посетителями заходящим с той или иной поисковой системы. </li> </ul>
	* </li> <li>когда группировка не установлена: <ul> <li> <b>s_id</b> - ID записи; </li>
	* <li> <b>s_counter</b> - счетчик; </li> <li> <b>s_site_id</b> - ID сайта на который пришли;
	* </li> <li> <b>s_phrase</b> - поисковая фраза; </li> <li> <b>s_searcher_id</b> - ID поисковой
	* системы; </li> <li> <b>s_referer_id</b> - ID записи из таблицы <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#referer">ссылающихся сайтов
	* (страниц)</a>; </li> <li> <b>s_date_hit</b> - дата захода; </li> <li> <b>s_url_to</b> -
	* страница на которую пришли; </li> <li> <b>s_session_id</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессии</a>. </li> </ul> </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b> - ID записи; </li> <li>
	* <b>ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по <b>ID</b>
	* будет искаться вхождение; </li> <li> <b>SESSION_ID</b> - ID сессии; </li> <li>
	* <b>SESSION_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>SESSION_ID</b> будет искаться вхождение; </li> <li> <b>SEARCHER_ID</b> - ID поисковой
	* системы; </li> <li> <b>SEARCHER_ID_EXACT_MATCH</b> - если значение равно "N", то при
	* фильтрации по <b>SEARCHER_ID</b> будет искаться вхождение; </li> <li>
	* <b>REFERER_ID</b> - ID записи из таблицы ссылающихся сайтов (страниц); </li> <li>
	* <b>REFERER_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>REFERER_ID</b> будет искаться вхождение; </li> <li> <b>SEARCHER</b>* - название
	* поисковой системы; </li> <li> <b>SEARCHER_EXACT_MATCH</b> - если значение равно "Y",
	* то при фильтрации по <b>SEARCHER</b> будет искаться точное совпадение;
	* </li> <li> <b>DATE1</b> - начальное значение интервала для поля "дата"; </li> <li>
	* <b>DATE2</b> - конечно значение интервала для поля "дата"; </li> <li> <b>PHRASE</b>*
	* - поисковая фраза; </li> <li> <b>PHRASE_EXACT_MATCH</b> - если значение равно "Y", то
	* при фильтрации по <b>PHRASE</b> будет искаться точное совпадение; </li> <li>
	* <b>TO</b>* - страница на которую пришли; </li> <li> <b>TO_EXACT_MATCH</b> - если
	* значение равно "Y", то при фильтрации по <b>TO</b> будет искаться
	* точное совпадение; </li> <li> <b>TO_404</b> - была ли <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#404">404 ошибка</a> на странице на
	* которую пришли, возможные значения: <ul> <li> <b>Y</b> - была; </li> <li> <b>N</b> -
	* не была. </li> </ul> </li> <li> <b>SITE_ID</b> - ID сайта, на который пришли; </li> <li>
	* <b>GROUP</b> - группировка результирующего списка, возможные значения:
	* <ul> <li> <b>P</b> - группировка по поисковой фразе; </li> <li> <b>S</b> -
	* группировка по поисковой системе. </li> </ul> </li> </ul> * - допускается <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности результирующего списка. Если значение
	* равно "true", то список был отфильтрован.
	*
	* @param int &$total  Суммарное количество поисковых фраз. Принимает значение только
	* при установленной группировке.
	*
	* @param string &$group_by  Группировка списка поисковых фраз. Возможные значения: <ul> <li> <b>P</b>
	* - группировка по поисковой фразе; </li> <li> <b>S</b> - группировка по
	* поисковой системе. </li> </ul>
	*
	* @param int &$max  Количество заходов по самой популярной поисковой фразе.
	* Принимает значение только при установленной группировке.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>// отфильтруем только те поисковые фразы<br>// которые искали на сайте с помощью внутреннего поиска<br>$arFilter = array(<br>    "SEARCHER_ID"  =&gt; 1<br>    );<br><br>// получим список записей<br>$rs = <b>CPhrase::GetList</b>(<br>    ($by = "s_id"), <br>    ($order = "desc"), <br>    $arFilter, <br>    $is_filtered,<br>    $total,<br>    $group_by,<br>    $max<br>    );<br><br>// выведем все записи<br>while ($ar = $rs-&gt;Fetch())<br>{<br>    echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";    <br>}<br>?&gt;<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#phrase">Термин "Поисковая
	* фраза"</a> </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search">Термин
	* "Поисковая система"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cphrase/getlist.php
	* @author Bitrix
	*/
	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered, &$total, &$grby, &$max)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$group = false;
		$s = "S.NAME as SEARCHER_NAME, S.ID as SEARCHER_ID";
		$strSqlGroup =  "GROUP BY S.ID, S.NAME, S.PHRASES_HITS, S.PHRASES";
		$arSqlSearch = Array("PH.SEARCHER_ID <> 1");
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
					case "SEARCHER_ID":
					case "REFERER_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("PH.".$key,$val,$match);
						break;
					case "SEARCHER_ID_STR":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("S.ID",$val,$match);
						break;
					case "SEARCHER":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.NAME", $val, $match);
						break;
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "PH.DATE_HIT >= ".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "PH.DATE_HIT < ".CStatistics::DBDateAdd($DB->CharToDateFunction($val, "SHORT"), 1);
						break;
					case "PHRASE":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						if($match == "N")
							$val = '"'.trim($val, '"').'"';
						$arSqlSearch[] = GetFilterQuery("PH.PHRASE", $val, $match);
						break;
					case "TO":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("PH.URL_TO",$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "TO_404":
						$arSqlSearch[] = ($val=="Y") ? "PH.URL_TO_404='Y'" : "PH.URL_TO_404='N'";
						break;
					case "GROUP":
						$group = true;
						if ($val=="P")
						{
							$find_group="P";
							$strSqlGroup =  " GROUP BY PH.PHRASE ";
							$s = " PH.PHRASE ";
						}
						else $find_group="S";
						break;
					case "SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("PH.SITE_ID", $val, $match);
						break;
				}
			}
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$grby = ($find_group=="P" || $find_group=="S") ? $find_group : "";
		$strSqlOrder = "";
		if (strlen($grby)<=0)
		{
			if ($by == "s_id")					$strSqlOrder = "ORDER BY PH.ID";
			elseif ($by == "s_site_id")			$strSqlOrder = "ORDER BY PH.SITE_ID";
			elseif ($by == "s_phrase")			$strSqlOrder = "ORDER BY PH.PHRASE";
			elseif ($by == "s_searcher_id")		$strSqlOrder = "ORDER BY PH.SEARCHER_ID";
			elseif ($by == "s_referer_id")		$strSqlOrder = "ORDER BY PH.REFERER_ID";
			elseif ($by == "s_date_hit")		$strSqlOrder = "ORDER BY PH.DATE_HIT";
			elseif ($by == "s_url_to")			$strSqlOrder = "ORDER BY PH.URL_TO";
			elseif ($by == "s_session_id")		$strSqlOrder = "ORDER BY PH.SESSION_ID";
			else
			{
				$by = "s_id";
				$strSqlOrder = "ORDER BY PH.ID";
			}
			if ($order!="asc")
			{
				$strSqlOrder .= " desc ";
				$order="desc";
			}
			$strSql = "
				SELECT /*TOP*/
					PH.ID,
					PH.PHRASE,
					PH.SESSION_ID,
					PH.SEARCHER_ID,
					PH.URL_TO,
					PH.URL_TO_404,
					PH.REFERER_ID,
					PH.SITE_ID,
					".$DB->DateToCharFunction("PH.DATE_HIT")." DATE_HIT,
					S.NAME SEARCHER_NAME
				FROM
					b_stat_phrase_list PH
				INNER JOIN b_stat_searcher S ON (S.ID = PH.SEARCHER_ID)
				WHERE
				".$strSqlSearch."
				".$strSqlOrder."
			";
		}
		elseif (IsFiltered($strSqlSearch) || $grby=="P")
		{
			if ($by == "s_phrase" && $grby=="P")			$strSqlOrder = "ORDER BY PH.PHRASE";
			elseif ($by == "s_searcher_id" && $grby=="S")	$strSqlOrder = "ORDER BY PH.SEARCHER_ID";
			elseif ($by == "s_quantity")					$strSqlOrder = "ORDER BY QUANTITY";
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
					count(PH.ID) as COUNTER
				FROM
					b_stat_phrase_list PH,
					b_stat_searcher S
				WHERE
				".$strSqlSearch."
				and S.ID = PH.SEARCHER_ID
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
			$max = (is_array($arrCount) && count($arrCount)>0) ? max($arrCount) : 0;
			if ($grby=="P")
			{
				$strSql = "
					SELECT /*TOP*/
						$s,
						count(PH.ID) QUANTITY,
						(count(PH.ID)*100)/$total C_PERCENT
					FROM
						b_stat_phrase_list PH,
						b_stat_searcher S
					WHERE
					".$strSqlSearch."
					and S.ID = PH.SEARCHER_ID
					".$strSqlGroup."
					".$strSqlOrder."
				";
			}
			else
			{
				$strSql = "
					SELECT /*TOP*/
						$s,
						count(PH.ID) QUANTITY,
						(count(PH.ID)*100)/$total C_PERCENT,
						S.PHRASES_HITS/S.PHRASES AVERAGE_HITS
					FROM
						b_stat_phrase_list PH,
						b_stat_searcher S
					WHERE
					".$strSqlSearch."
					and S.ID = PH.SEARCHER_ID
					".$strSqlGroup."
					".$strSqlOrder."
				";
			}
		}
		elseif ($grby=="S")
		{
			if ($by == "s_name")				$strSqlOrder = "ORDER BY S.ID";
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
			$strSql = "SELECT sum(S.PHRASES) TOTAL, max(S.PHRASES) MAX FROM b_stat_searcher S";
			$c = $DB->Query($strSql, false, $err_mess.__LINE__);
			$cr = $c->Fetch();
			$total = intval($cr["TOTAL"]);
			$max = intval($cr["MAX"]);
			$strSql = "
				SELECT /*TOP*/
					S.ID SEARCHER_ID,
					S.NAME SEARCHER_NAME,
					S.PHRASES QUANTITY,
					S.PHRASES*100/$total C_PERCENT,
					S.PHRASES_HITS/S.PHRASES AVERAGE_HITS
				FROM
					b_stat_searcher S
				WHERE
					".$DB->IsNull("S.PHRASES","0")." > 0
				".$strSqlOrder."
			";
		}

		$res = $DB->Query(CStatistics::DBTopSql($strSql), false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch) || $group);
		return $res;
	}
}
?>
