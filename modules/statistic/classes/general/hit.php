<?

/**
 * <b>CHit</b> - класс для получения данных по <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хитами</a> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителей</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/chit/index.php
 * @author Bitrix
 */
class CHit
{
	
	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хитов</a> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителей</a>. Число выводимых строк определяется в поле <b>Максимальное кол-во показываемых записей в таблицах</b> в <a href="http://dev.1c-bitrix.ru/user_help/statistic/settings.php" >настройках модуля</a> Веб-аналитика.</p>
	*
	*
	* @param string &$by = "s_id" Поле для сортировки. Возможные значения: <ul> <li> <b>s_id</b> - ID хита; </li>
	* <li> <b>s_site_id</b> - ID сайта; </li> <li> <b>s_session_id</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессии</a>; </li> <li> <b>s_date_hit</b> -
	* время хита; </li> <li> <b>s_user_id</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#user">пользователя</a> под которым
	* был авторизован посетитель (в момент хита или до того); </li> <li>
	* <b>s_guest_id</b> - ID <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителя</a>;
	* </li> <li> <b>s_ip</b> - <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#ip">IP адрес</a>
	* посетителя; </li> <li> <b>s_url</b> - страница хита; </li> <li> <b>s_country_id</b> - ID
	* страны посетителя. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID хита; </li> <li> <b>ID_EXACT_MATCH</b> -
	* если значение равно "N", то при фильтрации по <b>ID</b> будет искаться
	* вхождение; </li> <li> <b>GUEST_ID</b>* - ID посетителя; </li> <li> <b>GUEST_ID_EXACT_MATCH</b> -
	* если значение равно "N", то при фильтрации по <b>GUEST_ID</b> будет
	* искаться вхождение; </li> <li> <b>NEW_GUEST</b> - флаг "новый посетитель",
	* возможные значения: <ul> <li> <b>Y</b> - посетитель впервые на портале; </li>
	* <li> <b>N</b> - посетитель уже посещал ранее портал. </li> </ul> </li> <li>
	* <b>SESSION_ID</b>* - ID сессии; </li> <li> <b>SESSION_ID_EXACT_MATCH</b> - если значение равно
	* "N", то при фильтрации по <b>SESSION_ID</b> будет искаться вхождение; </li> <li>
	* <b>STOP_LIST_ID</b>* - ID записи <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#stop_list">стоп-листа</a> под которую
	* попал посетитель (если это имело место быть); </li> <li>
	* <b>STOP_LIST_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>STOP_LIST_ID</b> будет искаться вхождение; </li> <li> <b>URL</b>* - страница хита;
	* </li> <li> <b>URL_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>URL</b> будет искаться точное совпадение; </li> <li> <b>URL_404</b> - была ли <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#404">404 ошибка</a> на странице хита <ul>
	* <li> <b>Y</b> - была; </li> <li> <b>N</b> - не было. </li> </ul> </li> <li> <b>USER</b>* - ID, логин,
	* имя, фамилия пользователя под которым был авторизован посетитель
	* в момент хита или до него; </li> <li> <b>USER_EXACT_MATCH</b> - если значение равно
	* "Y", то при фильтрации по <b>USER</b> будет искаться точное совпадение;
	* </li> <li> <b>REGISTERED</b> - флаг "был ли авторизован посетитель в момент
	* хита или до этого", возможные значения: <ul> <li> <b>Y</b> - был; </li> <li> <b>N</b>
	* - не был. </li> </ul> </li> <li> <b>DATE_1</b> - начальное значение интервала даты
	* хита; </li> <li> <b>DATE_2</b> - конечное значение интервала даты хита; </li> <li>
	* <b>IP</b>* - IP адрес посетителя в момент хита; </li> <li> <b>IP_EXACT_MATCH</b> - если
	* значение равно "Y", то при фильтрации по <b>IP</b> будет искаться
	* точное совпадение; </li> <li> <b>USER_AGENT</b>* - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#user_agent">UserAgent</a> посетителя в
	* момент хита; </li> <li> <b>USER_AGENT_EXACT_MATCH</b> - если значение равно "Y", то при
	* фильтрации по <b>USER_AGENT</b> будет искаться точное совпадение; </li> <li>
	* <b>COUNTRY_ID</b>* - ID страны посетителя в момент хита; </li> <li>
	* <b>COUNTRY_ID_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>COUNTRY_ID</b> будет искаться точное совпадение; </li> <li> <b>COUNTRY</b>* -
	* название страны; </li> <li> <b>COUNTRY_EXACT_MATCH</b> - если значение равно "Y", то
	* при фильтрации по <b>COUNTRY</b> будет искаться точное совпадение; </li>
	* <li> <b>COOKIE</b>* - содержимое <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#cookie">Cookie</a> в момент хита; </li> <li>
	* <b>COOKIE_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>COOKIE</b> будет искаться точное совпадение; </li> <li> <b>STOP</b> - <ul> <li> <b>Y</b>
	* - был; </li> <li> <b>N</b> - не был. </li> </ul> </li> <li> <b>SITE_ID</b>* - ID сайта; </li> <li>
	* <b>SITE_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>SITE_ID</b> будет искаться вхождение. </li> </ul> <br> * - допускается <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности списка хитов. Если значение равно "true", то
	* список был отфильтрован.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // выберем хиты сессии #1056
	* $arFilter = array(
	*     "SESSION_ID" =&gt; 1056
	*     );
	* 
	* // получим список записей
	* $rs = <b>CHit::GetList</b>(
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">Термин "Хит"</a> </li> </ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/chit/getlist.php
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
					case "GUEST_ID":
					case "SESSION_ID":
					case "STOP_LIST_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("H.".$key, $val, $match);
						break;
					case "URL":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("H.URL", $val, $match, array("/","\\",".","?","#",":"));
						break;
					case "URL_404":
					case "NEW_GUEST":
						$arSqlSearch[] = ($val=="Y") ? "H.".$key."='Y'" : "H.".$key."='N'";
						break;
					case "REGISTERED":
						$arSqlSearch[] = ($val=="Y") ? "H.USER_ID>0" : "(H.USER_ID<=0 or H.USER_ID is null)";
						break;
					case "DATE_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "H.DATE_HIT >= ".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "H.DATE_HIT < ".CStatistics::DBDateAdd($DB->CharToDateFunction($val, "SHORT"), 1);
						break;
					case "IP":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("H.IP",$val,$match,array("."));
						break;
					case "USER_AGENT":
					case "COUNTRY_ID":
					case "CITY_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("H.".$key, $val, $match);
						break;
					case "COOKIE":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("H.COOKIES",$val,$match);
						break;
					case "USER":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = $DB->IsNull("H.USER_ID","0").">0";
						$arSqlSearch[] = GetFilterQuery("H.USER_ID,A.LOGIN,A.LAST_NAME,A.NAME", $val, $match);
						$select = ", A.LOGIN, ".$DB->Concat($DB->IsNull("A.NAME","''"), "' '", $DB->IsNull("A.LAST_NAME","''"))." USER_NAME";
						$from1 = "LEFT JOIN b_user A ON (A.ID = H.USER_ID)";
						break;
					case "COUNTRY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("C.NAME", $val, $match);
						$from2 = "INNER JOIN b_stat_country C ON (C.ID = H.COUNTRY_ID)";
						break;
					case "REGION":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("CITY.REGION", $val, $match);
						break;
					case "CITY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("CITY.NAME", $val, $match);
						break;
					case "STOP":
						$arSqlSearch[] = ($val=="Y") ? "H.STOP_LIST_ID>0" : "(H.STOP_LIST_ID<=0 or H.STOP_LIST_ID is null)";
						break;
					case "SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("H.SITE_ID", $val, $match);
						break;
				}
			}
		}

		if ($by == "s_id")				$strSqlOrder = "ORDER BY H.ID";
		elseif ($by == "s_site_id")		$strSqlOrder = "ORDER BY H.SITE_ID";
		elseif ($by == "s_session_id")	$strSqlOrder = "ORDER BY H.SESSION_ID";
		elseif ($by == "s_date_hit")	$strSqlOrder = "ORDER BY H.DATE_HIT";
		elseif ($by == "s_user_id")		$strSqlOrder = "ORDER BY H.USER_ID";
		elseif ($by == "s_guest_id")	$strSqlOrder = "ORDER BY H.GUEST_ID";
		elseif ($by == "s_ip")			$strSqlOrder = "ORDER BY H.IP";
		elseif ($by == "s_url")			$strSqlOrder = "ORDER BY H.URL ";
		elseif ($by == "s_country_id")	$strSqlOrder = "ORDER BY H.COUNTRY_ID ";
		elseif ($by == "s_region_name")	$strSqlOrder = "ORDER BY CITY.REGION ";
		elseif ($by == "s_city_id")	$strSqlOrder = "ORDER BY H.CITY_ID ";
		else
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY H.ID";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT /*TOP*/
				H.ID,
				H.SESSION_ID,
				H.GUEST_ID,
				H.NEW_GUEST,
				H.USER_ID,
				H.USER_AUTH,
				H.URL,
				H.URL_404,
				H.URL_FROM,
				H.IP,
				H.METHOD,
				H.COOKIES,
				H.USER_AGENT,
				H.STOP_LIST_ID,
				H.COUNTRY_ID,
				H.CITY_ID,
				CITY.REGION REGION_NAME,
				CITY.NAME CITY_NAME,
				H.SITE_ID,
				".$DB->DateToCharFunction("H.DATE_HIT")." DATE_HIT
				".$select."
			FROM
				b_stat_hit H
				LEFT JOIN b_stat_city CITY ON (CITY.ID = H.CITY_ID)
			".$from1."
			".$from2."
			WHERE
			".$strSqlSearch."
			".$strSqlOrder."
		";

		$res = $DB->Query(CStatistics::DBTopSql($strSql), false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}

	
	/**
	* <p>Возвращает данные по указанному <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хиту</a> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителя</a>.</p>
	*
	*
	* @param int $hit_id  ID хита.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $hit_id = 1;
	* if ($rs = <b>CHit::GetByID</b>($hit_id))
	* {
	*     $ar = $rs-&gt;Fetch();
	*     // выведем параметры хита
	*     echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">Термин "Хит"</a> </li></ul> <a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/chit/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$ID = intval($ID);
		$res = $DB->Query("
			SELECT
				H.*,
				".$DB->DateToCharFunction("H.DATE_HIT")." DATE_HIT,
				C.NAME COUNTRY_NAME,
				CITY.REGION REGION_NAME,
				CITY.NAME CITY_NAME
			FROM
				b_stat_hit H
				INNER JOIN b_stat_country C ON (C.ID = H.COUNTRY_ID)
				LEFT JOIN b_stat_city CITY ON (CITY.ID = H.CITY_ID)
			WHERE
				H.ID = '$ID'
		", false, $err_mess.__LINE__);

		$res = new CStatResult($res);
		return $res;
	}
}
?>
