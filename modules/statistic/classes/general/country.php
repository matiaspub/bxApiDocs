<?

/**
 * <b>CCountry</b> - класс для получения данных по трафику в разрезе по странами. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/ccountry/index.php
 * @author Bitrix
 */
class CCountry
{
	
	/**
	* <p>Возвращает список стран, определённых в модуле "Статистика". Загрузка списка стран осуществляется при переиндексации базы IP адресов в настройках модуля "Статистика".</p>
	*
	*
	* @param string &$by = "s_name" Поле для сортировки. Возможные значения: <ul> <li> <b>s_id</b> -
	* двухсимвольный идентификатор страны; </li> <li> <b>s_short_name</b> -
	* трехсимвольный идентификатор страны; </li> <li> <b>s_name</b> -
	* наименование страны; </li> <li> <b>s_sessions</b> - суммарное кол-во <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессий</a> по данной стране;
	* </li> <li> <b>s_new_guests</b> - суммарное кол-во <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#new_guest">новых посетителей</a> по
	* данной стране; </li> <li> <b>s_hits</b> - суммарное кол-во <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хитов</a> по данной стране; </li>
	* <li> <b>s_events</b> - суммарное кол-во <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событий</a> по данной стране.
	* </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - двухсимвольный
	* идентификатор страны; </li> <li> <b>ID_EXACT_MATCH</b> - если значение равно "N",
	* то при фильтрации по <b>ID</b> будет искаться вхождение; </li> <li>
	* <b>SHORT_NAME</b>* - трехсимвольный идентификатор страны; </li> <li>
	* <b>SHORT_NAME_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>SHORT_NAME</b> будет искаться точное совпадение; </li> <li> <b>NAME</b>* -
	* наименование страны; </li> <li> <b>NAME_EXACT_MATCH</b> - если значение равно "Y",
	* то при фильтрации по <b>NAME_EXACT_MATCH</b> будет искаться точное
	* совпадение; </li> <li> <b>SESSIONS1</b> - начальное значение интервала для
	* поля "кол-во сессий"; </li> <li> <b>SESSIONS2</b> - конечное значение интервала
	* для поля "кол-во сессий"; </li> <li> <b>NEW_GUESTS1</b> - начальное значение
	* интервала для поля "кол-во новых посетителей"; </li> <li> <b>NEW_GUESTS2</b> -
	* конечное значение интервала для поля "кол-во новых посетителей";
	* </li> <li> <b>HITS1</b> - начальное значение интервала для поля "кол-во
	* хитов"; </li> <li> <b>HITS2</b> - конечное значение интервала для поля "кол-во
	* хитов"; </li> <li> <b>EVENTS1</b> - начальное значение интервала для поля
	* "кол-во событий"; </li> <li> <b>EVENTS2</b> - конечное значение интервала для
	* поля "кол-во событий". <br><br> * - допускается <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a> </li> </ul>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности списка UserAgent'ов. Если значение равно "true",
	* то список был отфильтрован.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // выберем только те страны из которых было не менее 100 заходов на сайт
	* $arFilter = array(
	*     "SESSIONS1" =&gt; 100
	*     );
	* 
	* // получим список записей
	* $rs = <b>CCountry::GetList</b>(
	*     ($by = "s_name"), 
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
	* 
	* 
	* &lt;?
	* // выпадающий список с одиночным выбором
	* echo SelectBox("COUNTRY_ID", <b>CCountry::GetList</b>(), "", intval($COUNTRY_ID));
	* 
	* // список из 20 видимых элементов с возможностью множественного выбора
	* echo SelectBoxM("arCOUNTRY_ID[]", <b>CCountry::GetList</b>(), $arCOUNTRY_ID, "", false, 20);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li>Пользовательскую документацию, <a
	* href="http://www.1c-bitrix.ru/user_help/statistic/settings.php">настройки модуля
	* "Статистика"</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/functions/html/selectbox.php">SelectBox</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/main/functions/html/selectboxm.php">SelectBoxM</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/ccountry/getlist.php
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
						if ($val!="ALL")
						{
							$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
							$arSqlSearch[] = GetFilterQuery("C.ID",$val,$match);
						}
						break;
					case "SHORT_NAME":
					case "NAME":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("C.".$key,$val,$match);
						break;
					case "SESSIONS1":
						$arSqlSearch[] = "C.SESSIONS>='".intval($val)."'";
						break;
					case "SESSIONS2":
						$arSqlSearch[] = "C.SESSIONS<='".intval($val)."'";
						break;
					case "NEW_GUESTS1":
						$arSqlSearch[] = "C.NEW_GUESTS>='".intval($val)."'";
						break;
					case "NEW_GUESTS2":
						$arSqlSearch[] = "C.NEW_GUESTS<='".intval($val)."'";
						break;
					case "HITS1":
						$arSqlSearch[] = "C.HITS>='".intval($val)."'";
						break;
					case "HITS2":
						$arSqlSearch[] = "C.HITS<='".intval($val)."'";
						break;
					case "EVENTS1":
						$arSqlSearch[] = "C.C_EVENTS>='".intval($val)."'";
						break;
					case "EVENTS2":
						$arSqlSearch[] = "C.C_EVENTS<='".intval($val)."'";
						break;
				}
			}
		}

		if ($by == "s_id")				$strSqlOrder = "ORDER BY C.ID";
		elseif ($by == "s_short_name")	$strSqlOrder = "ORDER BY C.SHORT_NAME";
		elseif ($by == "s_name")		$strSqlOrder = "ORDER BY C.NAME";
		elseif ($by == "s_sessions")	$strSqlOrder = "ORDER BY C.SESSIONS";
		elseif ($by == "s_dropdown")	$strSqlOrder = "ORDER BY C.NEW_GUESTS desc, C.NAME";
		elseif ($by == "s_new_guests")	$strSqlOrder = "ORDER BY C.NEW_GUESTS";
		elseif ($by == "s_hits")		$strSqlOrder = "ORDER BY C.HITS ";
		elseif ($by == "s_events")		$strSqlOrder = "ORDER BY C.C_EVENTS ";
		else
		{
			$by = "s_name";
			$strSqlOrder = "ORDER BY C.NAME";
		}
		if ($order=="desc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		else
		{
			$strSqlOrder .= " asc ";
			$order="asc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				C.*,
				C.ID as REFERENCE_ID,
				".$DB->Concat("'['", "C.ID", "'] '", $DB->IsNull("C.NAME","''"))." as REFERENCE
			FROM
				b_stat_country C
			WHERE
			$strSqlSearch
			$strSqlOrder
			";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}

	// returns arrays needed to plot graph and diagram
	
	/**
	* <p>Возвращает данные необходимые для построения графика и круговой диаграммы посещаемости в разрезе по странам.</p>
	*
	*
	* @param array $filter  Массив для фильтрации стран. В массиве допустимы следующие ключи:
	* <ul> <li> <b>COUNTRY_ID</b> - двухсимвольный идентификатор страны; </li> <li>
	* <b>DATE1</b> - начальное значение <i>интервала времени</i>; </li> <li> <b>DATE2</b> -
	* конечное значение <i>интервала времени</i>. </li> </ul>
	*
	* @param array &$legend  Массив содержащий суммарные показатели по каждой стране, а также
	* цвет линии графика и сектора круговой диаграммы для каждой
	* страны. Структура данного массива: <pre>Array<br>(<br> [<i>ID страны</i>] =&gt;
	* Array<br> (<br> [NAME] =&gt; название страны<br> [SESSIONS] =&gt; кол-во <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессий</a> за <i>интервал
	* времени</i><br> [NEW_GUESTS] =&gt; кол-во <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#new_guest">новых посетителей</a> за
	* <i>интервал времени</i><br> [HITS] =&gt; кол-во <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хитов</a> за <i>интервал
	* времени</i><br> [C_EVENTS] =&gt; кол-во <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событий</a> за <i>интервал
	* времени</i><br> [TOTAL_SESSIONS] =&gt; суммарное кол-во сессий<br> [TOTAL_NEW_GUESTS] =&gt;
	* суммарное кол-во новых посетителей<br> [TOTAL_HITS] =&gt; суммарное кол-во
	* хитов<br> [TOTAL_C_EVENTS] =&gt; суммарное кол-во событий<br> [COLOR] =&gt; цвет
	* линии графика и сектора круговой диаграммы<br> )<br> ...<br>)<br></pre>
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/colors.php");<br>// отфильтруем данные только по России на декабрь 2007 года<br>$arFilter = Array(<br>    "COUNTRY_ID" =&gt; "ru",<br>    "DATE1"      =&gt; "01.12.2007",<br>    "DATE2"      =&gt; "31.12.2007"<br>    );<br><br>// получим массив данных в разрезе по дням<br>$arDays = <b>CCountry::GetGraphArray</b>($arFilter, $arLegend);<br><br>// выведем полученные данные по России за декабрь 2007 года<br>while (list($date, $arr) = each($arDays))<br>{<br>    echo "Дата: ".$date."&lt;br&gt;";<br>    echo "Данные на эту дату: &lt;pre&gt;"; print_r($arr); echo "&lt;/pre&gt;";    <br>}<br>?&gt;<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li>Пользовательскую документацию, <a
	* href="http://www.1c-bitrix.ru/user_help/statistic/site_traffic/country_list.php">"География по
	* странам"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/ccountry/getgrapharray.php
	* @author Bitrix
	*/
	public static function GetGraphArray($arFilter, &$arLegend)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		global $arCountryColor;
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
					case "COUNTRY_ID":
						if ($val!="NOT_REF")
							$arSqlSearch[] = GetFilterQuery("D.COUNTRY_ID",$val,"N");
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
		$arLegend = array();
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				".$DB->DateToCharFunction("D.DATE_STAT","SHORT")." DATE_STAT,
				".$DB->DateFormatToDB("DD", "D.DATE_STAT")." DAY,
				".$DB->DateFormatToDB("MM", "D.DATE_STAT")." MONTH,
				".$DB->DateFormatToDB("YYYY", "D.DATE_STAT")." YEAR,
				D.COUNTRY_ID,
				D.SESSIONS,
				D.NEW_GUESTS,
				D.HITS,
				D.C_EVENTS,
				C.NAME,
				C.SESSIONS TOTAL_SESSIONS,
				C.NEW_GUESTS TOTAL_NEW_GUESTS,
				C.HITS TOTAL_HITS,
				C.C_EVENTS TOTAL_C_EVENTS
			FROM
				b_stat_country_day D
				INNER JOIN b_stat_country C ON (C.ID = D.COUNTRY_ID)
			WHERE
				".$strSqlSearch."
			ORDER BY
				D.DATE_STAT, D.COUNTRY_ID
		";

		$rsD = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($arD = $rsD->Fetch())
		{
			$arrDays[$arD["DATE_STAT"]]["D"] = $arD["DAY"];
			$arrDays[$arD["DATE_STAT"]]["M"] = $arD["MONTH"];
			$arrDays[$arD["DATE_STAT"]]["Y"] = $arD["YEAR"];
			$arrDays[$arD["DATE_STAT"]][$arD["COUNTRY_ID"]]["SESSIONS"]		= $arD["SESSIONS"];
			$arrDays[$arD["DATE_STAT"]][$arD["COUNTRY_ID"]]["NEW_GUESTS"]	= $arD["NEW_GUESTS"];
			$arrDays[$arD["DATE_STAT"]][$arD["COUNTRY_ID"]]["HITS"]			= $arD["HITS"];
			$arrDays[$arD["DATE_STAT"]][$arD["COUNTRY_ID"]]["C_EVENTS"]		= $arD["C_EVENTS"];

			$arLegend[$arD["COUNTRY_ID"]]["NAME"] = $arD["NAME"];
			$arLegend[$arD["COUNTRY_ID"]]["SESSIONS"] += $arD["SESSIONS"];
			$arLegend[$arD["COUNTRY_ID"]]["NEW_GUESTS"] += $arD["NEW_GUESTS"];
			$arLegend[$arD["COUNTRY_ID"]]["HITS"] += $arD["HITS"];
			$arLegend[$arD["COUNTRY_ID"]]["C_EVENTS"] += $arD["C_EVENTS"];

			$arLegend[$arD["COUNTRY_ID"]]["TOTAL_SESSIONS"] = $arD["TOTAL_SESSIONS"];
			$arLegend[$arD["COUNTRY_ID"]]["TOTAL_NEW_GUESTS"] = $arD["TOTAL_NEW_GUESTS"];
			$arLegend[$arD["COUNTRY_ID"]]["TOTAL_HITS"] = $arD["TOTAL_HITS"];
			$arLegend[$arD["COUNTRY_ID"]]["TOTAL_C_EVENTS"] = $arD["TOTAL_C_EVENTS"];
		}
		reset($arLegend);
		$total = sizeof($arLegend);
		while (list($key, $arr) = each($arLegend))
		{
			if (strlen($arCountryColor[$key])>0)
			{
				$color = $arCountryColor[$key];
			}
			else
			{
				$color = GetNextRGB($color_getnext, $total);
				$color_getnext = $color;
			}
			$arr["COLOR"] = $color;
			$arLegend[$key] = $arr;
		}

		reset($arrDays);
		reset($arLegend);
		return $arrDays;
	}
}
?>