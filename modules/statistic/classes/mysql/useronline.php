<?

/**
 * <b>CUserOnline</b> - класс для получения данных о недавних <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителях</a> сайта и их <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессиях</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cuseronline/index.php
 * @author Bitrix
 */
class CUserOnline
{
	
	/**
	* <p>Возвращает количество <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителей</a>, проявивших активность на сайте (совершивших <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хит</a>) за определённый интервал времени. Данный интервал времени задается в настройках модуля "Статистика" в параметре "Интервал посетителей в online (сек.)".</p>
	*
	*
	* @return int 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* echo "Сейчас на сайте посетителей: ".<b>CUserOnline::GetGuestCount</b>();
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/classes/ctraffic/getcommonvalues.php">CTraffic::GetCommonValues</a></li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cuseronline/getguestcount.php
	* @author Bitrix
	*/
	public static function GetGuestCount()
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$interval = intval(COption::GetOptionString("statistic", "ONLINE_INTERVAL"));
		$strSql = "
			SELECT
				count(distinct G.ID) CNT
			FROM
				b_stat_session S
				INNER JOIN b_stat_guest G ON (G.ID = S.GUEST_ID)
			WHERE
				S.DATE_STAT = curdate()
				and S.DATE_LAST > DATE_ADD(now(), INTERVAL - $interval SECOND)
		";
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		$ar = $rs->Fetch();
		return intval($ar["CNT"]);
	}

	
	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессий</a> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителей</a>, проявивших активность (совершивших <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хит</a>) на сайте за определённый интервал времени.</p>
	*
	*
	* @param function $GetList  Ссылка на переменную, которая после выполнения метода будет
	* содержать количество <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#online">посетителей в online</a>.
	*
	* @param (&$guest_coun $t  Ссылка на переменную, которая после выполнения метода будет
	* содержать количество сессий посетителей в online.
	*
	* @param &$session_coun $t  
	*
	* @param mixed $arOrder = Array() 
	*
	* @param mixed $arFilter = Array()) 
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // получим список записей
	* $rs = <b>CUserOnline::GetList</b>($guest_counter, $session_counter);
	* 
	* echo "Количество посетителей в онлайн: ".$guest_counter;
	* echo "Количество сессий в онлайн: ".$session_counter;
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
	* <ul> <li> <a href="http://www.1c-bitrix.ru/user_help/statistic/users_online.php">Отчет "Кто на сайте"</a>
	* </li> </ul> </ht<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cuseronline/getlist.php
	* @author Bitrix
	*/
	public static function GetList(&$guest_count, &$session_count, $arOrder=Array(), $arFilter=Array())
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$interval = intval(COption::GetOptionString("statistic", "ONLINE_INTERVAL"));

		$arSqlSearch = Array();
		$from1 = "";
		$from2 = "";
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
					case "INTERVAL":
						$interval = intval($val);
						break;
					case "ID":
					case "GUEST_ID":
					case "ADV_ID":
					case "STOP_LIST_ID":
					case "USER_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("S.".$key,$val,$match);
						break;
					case "COUNTRY_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("S.COUNTRY_ID",$val,$match);
						break;
					case "IP":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.IP_LAST",$val,$match,array("."));
						break;
					case "REGISTERED":
						$arSqlSearch[] = ($val=="Y") ? "S.USER_ID>0" : "(S.USER_ID<=0 or S.USER_ID is null)";
						break;
					case "HITS1":
						$arSqlSearch[] = "S.HITS>='".intval($val)."'";
						break;
					case "HITS2":
						$arSqlSearch[] = "S.HITS<='".intval($val)."'";
						break;
					case "ADV":
						if ($val=="Y")
							$arSqlSearch[] = "(S.ADV_ID>0 and S.ADV_ID is not null)";
						elseif ($val=="N")
							$arSqlSearch[] = "(S.ADV_ID<=0 or S.ADV_ID is null)";
						break;
					case "REFERER1":
					case "REFERER2":
					case "REFERER3":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.".$key, $val, $match);
						break;
					case "STOP":
						$arSqlSearch[] = ($val=="Y") ? "S.STOP_LIST_ID>0" : "(S.STOP_LIST_ID<=0 or S.STOP_LIST_ID is null)";
						break;
					case "COUNTRY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("C.NAME", $val, $match);
						$from2 = "INNER JOIN b_stat_country C ON (C.ID = S.COUNTRY_ID)";
						break;
					case "LAST_SITE_ID":
						$arSqlSearch[] = GetFilterQuery("S.".$key, $val, "N");
						break;
					case "URL_LAST":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.".$key,$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "FIRST_URL_FROM":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("G.".$key,$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "ADV_BACK":
					case "NEW_GUEST":
					case "URL_LAST_404":
					case "URL_TO_404":
					case "USER_AUTH":
						$arSqlSearch[] = ($val=="Y") ? "S.".$key."='Y'" : "S.".$key."='N'";
						break;
					case "FAVORITES":
						$arSqlSearch[] = ($val=="Y") ? "G.".$key."='Y'" : "G.".$key."='N'";
						break;
					case "USER":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = "ifnull(S.USER_ID,0)>0";
						$arSqlSearch[] = GetFilterQuery("S.USER_ID,A.LOGIN,A.LAST_NAME,A.NAME", $val, $match);
						$from1 = "LEFT JOIN b_user A ON (A.ID = S.USER_ID)";
						break;
				}
			}
		}

		$by = "s_id";
		$order = "desc";
		if (is_array($arOrder) && !empty($arOrder))
		{
			foreach($arOrder as $by=>$order)
			{
				$by = strtolower($by);
				$order = strtolower($order);
				if ($order!="asc")
					$order = "desc";
			}
		}

		if ($by == "s_id") $strSqlOrder = "ORDER BY S.ID";
		elseif ($by == "s_session_time") $strSqlOrder = "ORDER BY SESSION_TIME";
		elseif ($by == "s_date_first") $strSqlOrder = "ORDER BY S.DATE_FIRST";
		elseif ($by == "s_date_last") $strSqlOrder = "ORDER BY S.DATE_LAST";
		elseif ($by == "s_user_id") $strSqlOrder = "ORDER BY S.USER_ID";
		elseif ($by == "s_guest_id") $strSqlOrder = "ORDER BY S.GUEST_ID";
		elseif ($by == "s_ip") $strSqlOrder = "ORDER BY S.IP_LAST";
		elseif ($by == "s_hits") $strSqlOrder = "ORDER BY S.HITS ";
		elseif ($by == "s_adv_id") $strSqlOrder = "ORDER BY S.ADV_ID ";
		elseif ($by == "s_country_id") $strSqlOrder = "ORDER BY S.COUNTRY_ID ";
		elseif ($by == "s_url_last") $strSqlOrder = "ORDER BY S.URL_LAST ";
		elseif ($by == "s_url_to") $strSqlOrder = "ORDER BY S.URL_TO ";
		else
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY S.ID";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

		$strSql = "
			SELECT
				S.ID,
				S.ADV_ID, S.REFERER1, S.REFERER2, S.REFERER3, S.ADV_BACK,
				S.LAST_SITE_ID, S.URL_LAST, S.URL_LAST_404, S.IP_LAST, S.HITS, S.USER_AUTH,
				S.STOP_LIST_ID, S.GUEST_ID, G.FAVORITES, G.LAST_USER_ID,
				UNIX_TIMESTAMP(S.DATE_LAST) - UNIX_TIMESTAMP(S.DATE_FIRST) SESSION_TIME,
				".$DB->DateToCharFunction("S.DATE_LAST")." DATE_LAST,
				if(G.SESSIONS<=1,'Y','N') NEW_GUEST,
				G.FIRST_URL_FROM,
				G.FIRST_SITE_ID,
				S.URL_FROM,
				S.COUNTRY_ID,
				C.NAME COUNTRY_NAME,
				CITY.REGION REGION_NAME,
				S.CITY_ID,
				CITY.NAME CITY_NAME
			FROM
				b_stat_session S
				INNER JOIN b_stat_guest G ON (G.ID = S.GUEST_ID)
				INNER JOIN b_stat_country C ON (C.ID = S.COUNTRY_ID)
				".$from1."
				".$from2."
				LEFT JOIN b_stat_city CITY ON (CITY.ID = S.CITY_ID)
			WHERE
				S.DATE_STAT >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
				and S.DATE_LAST > DATE_ADD(now(), INTERVAL - ".$interval." SECOND)
				and ".$strSqlSearch."
			".$strSqlOrder."
		";

		$arr = array();
		$arrG = array();
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		while($ar = $rs->Fetch())
		{
			$arr[] = $ar;
			$arrG[$ar["GUEST_ID"]] = $ar["GUEST_ID"];
		}
		$guest_count = count($arrG);
		$session_count = count($arr);
		$rs = new CDBResult;
		$rs->InitFromArray($arr);
		return $rs;
	}
}
?>
