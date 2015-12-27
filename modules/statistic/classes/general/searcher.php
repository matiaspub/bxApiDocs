<?

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
class CAllSearcher
{
	public static function DynamicDays($SEARCHER_ID, $date1="", $date2="")
	{
		$arFilter = array("DATE1"=>$date1, "DATE2"=>$date2);
		$z = CSearcher::GetDynamicList($SEARCHER_ID, $by, $order, $arMaxMin, $arFilter);
		$d = 0;
		while($zr = $z->Fetch())
			if(intval($zr["TOTAL_HITS"]) > 0)
				$d++;
		return $d;
	}

	// returns arrays needed to plot site indexing graph
	public static function GetGraphArray($arFilter, &$arrLegend)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array("D.SEARCHER_ID <> 1");
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
					case "SEARCHER_ID":
						$arSqlSearch[] = GetFilterQuery("D.SEARCHER_ID",$val,"N");
						break;
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT<=".$DB->CharToDateFunction($val." 23:59:59", "FULL");
						break;
				}
			}
		}
		$arrDays = array();
		$arrLegend = array();
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$summa = $arFilter["SUMMA"]=="Y" ? "Y" : "N";
		$strSql = CSearcher::GetGraphArray_SQL($strSqlSearch);

		$rsD = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($arD = $rsD->Fetch())
		{
			$arrDays[$arD["DATE_STAT"]]["D"] = $arD["DAY"];
			$arrDays[$arD["DATE_STAT"]]["M"] = $arD["MONTH"];
			$arrDays[$arD["DATE_STAT"]]["Y"] = $arD["YEAR"];
			if ($summa=="N")
			{
				$arrDays[$arD["DATE_STAT"]][$arD["SEARCHER_ID"]]["TOTAL_HITS"] = $arD["TOTAL_HITS"];
				$arrLegend[$arD["SEARCHER_ID"]]["COUNTER_TYPE"] = "DETAIL";
				$arrLegend[$arD["SEARCHER_ID"]]["NAME"] = $arD["NAME"];
			}
			elseif ($summa=="Y")
			{
				$arrDays[$arD["DATE_STAT"]]["TOTAL_HITS"] += $arD["TOTAL_HITS"];
				$arrLegend[0]["COUNTER_TYPE"] = "TOTAL";
			}
		}
		reset($arrLegend);
		$total = sizeof($arrLegend);
		while (list($key, $arr) = each($arrLegend))
		{
			$color = GetNextRGB($color, $total);
			$arr["COLOR"] = $color;
			$arrLegend[$key] = $arr;
		}

		reset($arrDays);
		reset($arrLegend);
		return $arrDays;
	}

	
	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search_domain">доменов поисковых систем</a>.</p>
	*
	*
	* @param string &$by = "s_id" Поле для сортировки. Возможные значения: <ul> <li> <b>s_id</b> - ID домена;
	* </li> <li> <b>s_domain</b> - домен; </li> <li> <b>s_variable</b> - имя переменной (или группа
	* имен переменных разделенных запятой) в которых хранится
	* поисковая фраза. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID домена; </li> <li>
	* <b>ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по <b>ID</b>
	* будет искаться вхождение; </li> <li> <b>SEARCHER_ID</b>* - ID поисковой системы;
	* </li> <li> <b>SEARCHER_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации
	* по <b>SEARCHER_ID</b> будет искаться вхождение; </li> <li> <b>DOMAIN</b>* - домен; </li>
	* <li> <b>DOMAIN_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>DOMAIN</b> будет искаться точное совпадение; </li> <li> <b>VARIABLE</b>* - имя
	* переменной (или группа имен переменных разделенных запятой) в
	* которых хранится поисковая фраза; </li> <li> <b>VARIABLE_EXACT_MATCH</b> - если
	* значение равно "Y", то при фильтрации по <b>VARIABLE</b> будет искаться
	* точное совпадение. </li> </ul> * - допускается <a
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
	* // выберем домены поисковой системы #20
	* $arFilter = array(
	*     "SEARCHER_ID" =&gt; 20
	*     );
	* 
	* // получим список записей
	* $rs = <b>CSearcher::GetDomainList</b>(
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/classes/csearcher/getbyid.php">CSearcher::GetByID</a>
	* </li> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search_domain">Термин "Домен
	* поисковой системы"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/csearcher/getdomainlist.php
	* @author Bitrix
	*/
	public static function GetDomainList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array("P.SEARCHER_ID <> 1");
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
						$arSqlSearch[] = GetFilterQuery("P.".$key,$val,$match);
						break;
					case "DOMAIN":
					case "VARIABLE":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("P.".$key, $val, $match);
						break;
				}
			}
		}
		$strSqlOrder = "";
		if ($by == "s_id")				$strSqlOrder = "ORDER BY P.ID";
		elseif ($by == "s_domain")		$strSqlOrder = "ORDER BY P.DOMAIN";
		elseif ($by == "s_variable")	$strSqlOrder = "ORDER BY P.VARIABLE";
		else
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY P.ID";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				P.ID,
				P.DOMAIN,
				P.VARIABLE,
				P.CHAR_SET
			FROM
				b_stat_searcher_params P
			WHERE
			$strSqlSearch
			$strSqlOrder
			";

		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $rs;
	}

	
	/**
	* <p>Возвращает данные по указанной <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search">поисковой системе</a>.</p>
	*
	*
	* @param int $searcher_id  ID поисковой системы. </ht
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $searcher_id = 1;
	* if ($rs = <b>CSearcher::GetByID</b>($searcher_id))
	* {
	*     $ar = $rs-&gt;Fetch();
	*     // выведем параметры поисковой системы
	*     echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/csearcher/getdomainlist.php">CSearcher::GetDomainList</a> </li>
	* <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search">Термин "Поисковая
	* система"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/csearcher/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$ID = intval($ID);
		$strSql = "SELECT S.* FROM b_stat_searcher S WHERE S.ID = '$ID'";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}
}
?>