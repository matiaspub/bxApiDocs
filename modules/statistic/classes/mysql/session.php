<?

/**
 * <b>CSession</b> - класс для получения данных о <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессиях</a> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителей</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/csession/index.php
 * @author Bitrix
 */
class CSession
{
	public static function GetAttentiveness($DATE_STAT, $SITE_ID=false)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		if ($SITE_ID!==false)
			$str = " and S.FIRST_SITE_ID = '".$DB->ForSql($SITE_ID,2)."' ";
		else
			$str = "";

		$strSql = "
			SELECT
				sum(UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST))/count(S.ID)		AM_AVERAGE_TIME,
				sum(if(UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)<60,1,0))		AM_1,
				sum(if(UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)>=60
				and UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)<180,1,0))		AM_1_3,
				sum(if(UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)>=180
				and UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)<360,1,0))		AM_3_6,
				sum(if(UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)>=360
				and UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)<540,1,0))		AM_6_9,
				sum(if(UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)>=540
				and UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)<720,1,0))		AM_9_12,
				sum(if(UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)>=720
				and UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)<900,1,0))		AM_12_15,
				sum(if(UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)>=900
				and UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)<1080,1,0))		AM_15_18,
				sum(if(UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)>=1080
				and UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)<1260,1,0))		AM_18_21,
				sum(if(UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)>=1260
				and UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)<1440,1,0))		AM_21_24,
				sum(if(UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST)>=1440,1,0))	AM_24,

				sum(S.HITS)/count(S.ID)						AH_AVERAGE_HITS,
				sum(if(S.HITS<=1, 1, 0))					AH_1,
				sum(if(S.HITS>=2 and S.HITS<=5, 1, 0))		AH_2_5,
				sum(if(S.HITS>=6 and S.HITS<=9, 1, 0))		AH_6_9,
				sum(if(S.HITS>=10 and S.HITS<=13, 1, 0))	AH_10_13,
				sum(if(S.HITS>=14 and S.HITS<=17, 1, 0))	AH_14_17,
				sum(if(S.HITS>=18 and S.HITS<=21, 1, 0))	AH_18_21,
				sum(if(S.HITS>=22 and S.HITS<=25, 1, 0))	AH_22_25,
				sum(if(S.HITS>=26 and S.HITS<=29, 1, 0))	AH_26_29,
				sum(if(S.HITS>=30 and S.HITS<=33, 1, 0))	AH_30_33,
				sum(if(S.HITS>=34, 1, 0))					AH_34
			FROM
				b_stat_session S
			WHERE
				S.DATE_STAT = cast(".$DB->CharToDateFunction($DATE_STAT, "SHORT")." as date)
			$str
			";

		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		$ar = $rs->Fetch();
		$arKeys = array_keys($ar);
		foreach($arKeys as $key)
		{
			if ($key=="AM_AVERAGE_TIME" || $key=="AH_AVERAGE_HITS")
			{
				$ar[$key] = (float) $ar[$key];
				$ar[$key] = round($ar[$key],2);
			}
			else
			{
				$ar[$key] = intval($ar[$key]);
			}
		}
		return $ar;
	}

	
	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессий</a>.</p>
	*
	*
	* @param string &$by = "s_id" Поле для сортировки. Возможные значения: <ul> <li> <b>s_id</b> - ID сессии;
	* </li> <li> <b>s_last_site_id</b> - ID сайта последнего <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хита</a> сессии; </li> <li>
	* <b>s_first_site_id</b> - ID сайта первого хита сессии; </li> <li> <b>s_date_first</b> - время
	* первого хита сессии; </li> <li> <b>s_date_last</b> - время последнего хита
	* сессии; </li> <li> <b>s_user_id</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#user">пользователя</a> под которым
	* последний раз был авторизован <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетитель</a>; </li> <li> <b>s_guest_id</b>
	* - ID посетителя; </li> <li> <b>s_ip</b> - <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#ip">IP
	* адрес</a> посетителя на последнем хите сессии; </li> <li> <b>s_hits</b> -
	* количество хитов в данной сессии; </li> <li> <b>s_events</b> - количество <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событий</a> в данной сессии; </li>
	* <li> <b>s_adv_id</b> - ID рекламной кампании по которой пришел посетитель;
	* </li> <li> <b>s_country_id</b> - ID страны посетителя; </li> <li> <b>s_url_last</b> - страница
	* первого хита сессии; </li> <li> <b>s_url_to</b> - страница последнего хита
	* сессии. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID сессии; </li> <li>
	* <b>ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по <b>ID</b>
	* будет искаться вхождение; </li> <li> <b>GUEST_ID</b>* - ID посетителя; </li> <li>
	* <b>GUEST_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>GUEST_ID</b> будет искаться вхождение; </li> <li> <b>NEW_GUEST</b> - флаг "новый
	* посетитель", возможные значения: <ul> <li> <b>Y</b> - посетитель впервые
	* на портале; </li> <li> <b>N</b> - посетитель уже посещал ранее портал. </li>
	* </ul> </li> <li> <b>USER_ID</b>* - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#user">пользователя</a> под которым
	* последний раз был авторизован посетитель; </li> <li> <b>USER_ID_EXACT_MATCH</b> -
	* если значение равно "N", то при фильтрации по <b>USER_ID</b> будет
	* искаться вхождение; </li> <li> <b>USER_AUTH</b> - флаг "был ли посетитель
	* авторизован в данной сессии", возможные значения: <ul> <li> <b>Y</b> - да;
	* </li> <li> <b>N</b> - нет. </li> </ul> </li> <li> <b>USER</b>* - ID, логин, имя, фамилия
	* пользователя под которым последний раз был авторизован
	* посетитель; </li> <li> <b>USER_EXACT_MATCH</b> - если значение равно "Y", то при
	* фильтрации по <b>USER</b> будет искаться точное совпадение; </li> <li>
	* <b>REGISTERED</b> - флаг "был ли авторизован посетитель в данной сессии
	* или до этого", возможные значения: <ul> <li> <b>Y</b> - был; </li> <li> <b>N</b> - не
	* был. </li> </ul> </li> <li> <b>FAVORITES</b> - флаг "добавлял ли посетитель сайт в
	* "Избранное"", возможные значения: <ul> <li> <b>Y</b> - да; </li> <li> <b>N</b> - нет.
	* </li> </ul> </li> <li> <b>EVENTS1</b> - начальное значение интервала для поля
	* "количество событий данной сессии"; </li> <li> <b>EVENTS2</b> - конечное
	* значение интервала для поля "количество событий данной сессии";
	* </li> <li> <b>HITS1</b> - начальное значение интервала для поля "количество
	* хитов данной сессии"; </li> <li> <b>HITS2</b> - конечное значение интервала
	* для поля "количество хитов данной сессии"; </li> <li> <b>ADV</b> - флаг
	* "приходил ли посетитель в данной сессии по какой-либо рекламной
	* кампании", возможные значения: <ul> <li> <b>Y</b> - да; </li> <li> <b>N</b> - нет. </li>
	* </ul> </li> <li> <b>ADV_ID</b>* - ID рекламной кампании; </li> <li> <b>ADV_ID_EXACT_MATCH</b> -
	* если значение равно "N", то при фильтрации по <b>ADV_ID</b> будет
	* искаться вхождение; </li> <li> <b>ADV_BACK</b> - флаг "возврат по рекламной
	* кампании", возможные значения: <ul> <li> <b>Y</b> - был <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_back">возврат</a>; </li> <li> <b>N</b> - был <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_first">прямой заход</a>. </li> </ul> </li>
	* <li> <b>REFERER1</b>* - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_id">идентификатор</a> referer1
	* рекламной кампании; </li> <li> <b>REFERER1_EXACT_MATCH</b> - если значение равно "Y",
	* то при фильтрации по <b>REFERER1</b> будет искаться точное совпадение;
	* </li> <li> <b>REFERER2</b>* - идентификатор referer2 рекламной кампании; </li> <li>
	* <b>REFERER2_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>REFERER2</b> будет искаться точное совпадение; </li> <li> <b>REFERER3</b>* - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_referer3">дополнительный
	* параметр</a> referer3 рекламной кампании; </li> <li> <b>REFERER3_EXACT_MATCH</b> - если
	* значение равно "Y", то при фильтрации по <b>REFERER3</b> будет искаться
	* точное совпадение; </li> <li> <b>STOP</b> - флаг "попал ли посетитель под
	* какую либо запись <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#stop_list">стоп-листа</a>", возможные
	* значения: <ul> <li> <b>Y</b> - да; </li> <li> <b>N</b> - нет. </li> </ul> </li> <li> <b>STOP_LIST_ID</b>* -
	* ID записи стоп-листа под которую попал посетитель, если это имело
	* место быть; </li> <li> <b>STOP_LIST_ID_EXACT_MATCH</b> - если значение равно "N", то при
	* фильтрации по <b>STOP_LIST_ID</b> будет искаться вхождение; </li> <li>
	* <b>COUNTRY_ID</b>* - ID страны посетителя; </li> <li> <b>COUNTRY_ID_EXACT_MATCH</b> - если
	* значение равно "N", то при фильтрации по <b>COUNTRY_ID</b> будет искаться
	* вхождение; </li> <li> <b>COUNTRY</b>* - наименование страны посетителя; </li> <li>
	* <b>COUNTRY_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>COUNTRY</b> будет искаться точное совпадение; </li> <li> <b>IP</b>* - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#ip">IP адрес</a> посетителя на
	* последнем хите сессии; </li> <li> <b>IP_EXACT_MATCH</b> - если значение равно "Y",
	* то при фильтрации по <b>IP</b> будет искаться точное совпадение; </li>
	* <li> <b>USER_AGENT</b>* - <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#user_agent">UserAgent</a>
	* посетителя; </li> <li> <b>USER_AGENT_EXACT_MATCH</b> - если значение равно "Y", то при
	* фильтрации по <b>USER_AGENT</b> будет искаться точное совпадение; </li> <li>
	* <b>DATE_START_1</b> - начальное значение интервала для поля "время первого
	* хита сессии"; </li> <li> <b>DATE_START_2</b> - конечное значение интервала для
	* поля "время первого хита сессии"; </li> <li> <b>DATE_END_1</b> - начальное
	* значение интервала для поля "время последнего хита сессии"; </li> <li>
	* <b>DATE_END_2</b> - конечное значение интервала для поля "время
	* последнего хита сессии"; </li> <li> <b>URL_TO</b>* - первая страница сессии;
	* </li> <li> <b>URL_TO_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>URL_TO</b> будет искаться точное совпадение; </li> <li> <b>URL_TO_404</b> - была
	* ли <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#404">404 ошибка</a> на первой
	* страницы сессии <ul> <li> <b>Y</b> - была; </li> <li> <b>N</b> - не было. </li> </ul> </li> <li>
	* <b>FIRST_SITE_ID</b>* - ID сайта на первом хите сессии; </li> <li>
	* <b>FIRST_SITE_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>FIRST_SITE_ID</b> будет искаться вхождение; </li> <li> <b>URL_LAST</b>* - последняя
	* страница сессии; </li> <li> <b>URL_LAST_EXACT_MATCH</b> - если значение равно "Y", то
	* при фильтрации по <b>URL_LAST</b> будет искаться точное совпадение; </li>
	* <li> <b>URL_LAST_404</b> - была ли <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#404">404
	* ошибка</a> на последней страницы сессии <ul> <li> <b>Y</b> - была; </li> <li>
	* <b>N</b> - не было. </li> </ul> </li> <li> <b>LAST_SITE_ID</b>* - ID сайта на последнем хите
	* сессии; </li> <li> <b>LAST_SITE_ID_EXACT_MATCH</b> - если значение равно "N", то при
	* фильтрации по <b>LAST_SITE_ID</b> будет искаться вхождение. </li> </ul> * -
	* допускается <a href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная
	* логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности результирующего списка. Если значение
	* равно "true", то список был отфильтрован.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // выберем все неудаленные сессии посетителя #1025
	* $arFilter = array(
	*     "GUEST_ID" =&gt; "1025"
	*     );
	* 
	* // получим список записей
	* $rs = <b>CSession::GetList</b>(
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
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">Термин "Сессия"</a> </li>
	* </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/csession/getlist.php
	* @author Bitrix
	*/
	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();
		$select = "";
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
					case "CITY_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("S.CITY_ID",$val,$match);
						break;
					case "DATE_START_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "S.DATE_FIRST>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_START_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "S.DATE_FIRST<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "DATE_END_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "S.DATE_LAST>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_END_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "S.DATE_LAST<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "IP":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.IP_LAST",$val,$match,array("."));
						break;
					case "REGISTERED":
						$arSqlSearch[] = ($val=="Y") ? "S.USER_ID>0" : "(S.USER_ID<=0 or S.USER_ID is null)";
						break;
					case "EVENTS1":
						$arSqlSearch[] = "S.C_EVENTS>='".intval($val)."'";
						break;
					case "EVENTS2":
						$arSqlSearch[] = "S.C_EVENTS<='".intval($val)."'";
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
					case "USER_AGENT":
						$val = preg_replace("/[\n\r]+/", " ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.USER_AGENT", $val, $match);
						break;
					case "STOP":
						$arSqlSearch[] = ($val=="Y") ? "S.STOP_LIST_ID>0" : "(S.STOP_LIST_ID<=0 or S.STOP_LIST_ID is null)";
						break;
					case "COUNTRY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("C.NAME", $val, $match);
						$from2 = "INNER JOIN b_stat_country C ON (C.ID = S.COUNTRY_ID)";
						break;
					case "REGION":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("CITY.REGION", $val, $match);
						break;
					case "CITY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("CITY.NAME", $val, $match);
						break;
					case "URL_TO":
					case "URL_LAST":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.".$key,$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "ADV_BACK":
					case "NEW_GUEST":
					case "FAVORITES":
					case "URL_LAST_404":
					case "URL_TO_404":
					case "USER_AUTH":
						$arSqlSearch[] = ($val=="Y") ? "S.".$key."='Y'" : "S.".$key."='N'";
						break;
					case "USER":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = "ifnull(S.USER_ID,0)>0";
						$arSqlSearch[] = GetFilterQuery("S.USER_ID,A.LOGIN,A.LAST_NAME,A.NAME", $val, $match);
						$from1 = "LEFT JOIN b_user A ON (A.ID = S.USER_ID)";
						$select = " , A.LOGIN, concat(ifnull(A.NAME,''),' ',ifnull(A.LAST_NAME,'')) USER_NAME";
						break;
					case "LAST_SITE_ID":
					case "FIRST_SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("S.".$key, $val, $match);
						break;
				}
			}
		}

		if ($by == "s_id")					$strSqlOrder = "ORDER BY S.ID";
		elseif ($by == "s_last_site_id")	$strSqlOrder = "ORDER BY S.LAST_SITE_ID";
		elseif ($by == "s_first_site_id")	$strSqlOrder = "ORDER BY S.FIRST_SITE_ID";
		elseif ($by == "s_date_first")		$strSqlOrder = "ORDER BY S.DATE_FIRST";
		elseif ($by == "s_date_last")		$strSqlOrder = "ORDER BY S.DATE_LAST";
		elseif ($by == "s_user_id")			$strSqlOrder = "ORDER BY S.USER_ID";
		elseif ($by == "s_guest_id")		$strSqlOrder = "ORDER BY S.GUEST_ID";
		elseif ($by == "s_ip")				$strSqlOrder = "ORDER BY S.IP_LAST";
		elseif ($by == "s_hits")			$strSqlOrder = "ORDER BY S.HITS ";
		elseif ($by == "s_events")			$strSqlOrder = "ORDER BY S.C_EVENTS ";
		elseif ($by == "s_adv_id")			$strSqlOrder = "ORDER BY S.ADV_ID ";
		elseif ($by == "s_country_id")		$strSqlOrder = "ORDER BY S.COUNTRY_ID ";
		elseif ($by == "s_region_name")		$strSqlOrder = "ORDER BY CITY.REGION ";
		elseif ($by == "s_city_id")		$strSqlOrder = "ORDER BY S.CITY_ID ";
		elseif ($by == "s_url_last")		$strSqlOrder = "ORDER BY S.URL_LAST ";
		elseif ($by == "s_url_to")			$strSqlOrder = "ORDER BY S.URL_TO ";
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
				S.GUEST_ID,
				S.NEW_GUEST,
				S.USER_ID,
				S.USER_AUTH,
				S.C_EVENTS,
				S.HITS,
				S.FAVORITES,
				S.URL_FROM,
				S.URL_TO,
				S.URL_TO_404,
				S.URL_LAST,
				S.URL_LAST_404,
				S.USER_AGENT,
				S.IP_FIRST,
				S.IP_LAST,
				S.FIRST_HIT_ID,
				S.LAST_HIT_ID,
				S.PHPSESSID,
				S.ADV_ID,
				S.ADV_BACK,
				S.REFERER1,
				S.REFERER2,
				S.REFERER3,
				S.STOP_LIST_ID,
				S.COUNTRY_ID,
				CITY.REGION REGION_NAME,
				S.CITY_ID,
				CITY.NAME CITY_NAME,
				S.FIRST_SITE_ID,
				S.LAST_SITE_ID,
				UNIX_TIMESTAMP(S.DATE_LAST)-UNIX_TIMESTAMP(S.DATE_FIRST) SESSION_TIME,
				".$DB->DateToCharFunction("S.DATE_FIRST")." DATE_FIRST,
				".$DB->DateToCharFunction("S.DATE_LAST")." DATE_LAST
				$select
			FROM
				b_stat_session S
			$from1
			$from2
				LEFT JOIN b_stat_city CITY ON (CITY.ID = S.CITY_ID)
			WHERE
			$strSqlSearch
			$strSqlOrder
			LIMIT ".intval(COption::GetOptionString('statistic','RECORDS_LIMIT'))."
			";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}

	
	/**
	* <p>Возвращает данные по указанной <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессии</a>.</p>
	*
	*
	* @param int $session_id  ID сессии.</bod
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $session_id = 1;
	* if ($rs = <b>CSession::GetByID</b>($session_id))
	* {
	*     $ar = $rs-&gt;Fetch();
	*     // выведем параметры сессии
	*     echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">Термин "Сессия"</a>
	* </li></ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/csession/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		$statDB = CDatabase::GetModuleConnection('statistic');
		$ID = intval($ID);

		$res = $statDB->Query("
			SELECT
				S.*,
				UNIX_TIMESTAMP(S.DATE_LAST) - UNIX_TIMESTAMP(S.DATE_FIRST) SESSION_TIME,
				".$statDB->DateToCharFunction("S.DATE_FIRST")." DATE_FIRST,
				".$statDB->DateToCharFunction("S.DATE_LAST")." DATE_LAST,
				C.NAME COUNTRY_NAME,
				CITY.REGION REGION_NAME,
				CITY.NAME CITY_NAME
			FROM
				b_stat_session S
				INNER JOIN b_stat_country C ON (C.ID = S.COUNTRY_ID)
				LEFT JOIN b_stat_city CITY ON (CITY.ID = S.CITY_ID)
			WHERE
				S.ID = ".$ID."
		");

		$res = new CStatResult($res);
		return $res;
	}
}
?>
