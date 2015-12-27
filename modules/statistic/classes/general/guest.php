<?

/**
 * <b>CGuest</b> - класс для получения данных по <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителям</a> сайта. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cguest/index.php
 * @author Bitrix
 */
class CAllGuest
{
	
	/**
	* <p>Возвращает список <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителей</a>.</p>
	*
	*
	* @param string &$by = "s_last_date" Поле для сортировки. Возможные значения: <ul> <li> <b>s_id</b> - ID
	* посетителя; </li> <li> <b>s_events</b> - суммарное кол-во <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#event">событий</a> сгенерированных
	* посетителем; </li> <li> <b>s_sessions</b> - суммарное кол-во <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#session">сессий</a> посетителя; </li> <li>
	* <b>s_hits</b> - суммарное кол-во <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#hit">хитов</a> посетителя; </li> <li>
	* <b>s_first_site_id</b> - ID сайта на который впервые пришел посетитель; </li> <li>
	* <b>s_first_date</b> - время первого захода на сайт; </li> <li> <b>s_first_url_from</b> -
	* страница с которой впервые пришел посетитель; </li> <li> <b>s_first_url_to</b> -
	* страница куда впервые пришел посетитель; </li> <li> <b>s_first_adv_id</b> - ID <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv">рекламной кампании</a> первого
	* захода; </li> <li> <b>s_last_site_id</b> - ID сайта последнего захода посетителя;
	* </li> <li> <b>s_last_date</b> - время последнего захода поестителя; </li> <li>
	* <b>s_last_user_id</b> - ID пользователя; </li> <li> <b>s_last_url_last</b> - последняя
	* страница на которую заходил посетитель; </li> <li> <b>s_last_user_agent</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#user_agent">UserAgent</a> посетителя на
	* последнем заходе; </li> <li> <b>s_last_ip</b> - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#ip">IP адрес</a> посетителя на
	* последнем заходе; </li> <li> <b>s_last_adv_id</b> - ID рекламной кампании на
	* последнем заходе; </li> <li> <b>s_last_country_id</b> - ID страны посетителя на
	* последнем заходе. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>ID</b>* - ID посетителя; </li> <li>
	* <b>ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по <b>ID</b>
	* будет искаться вхождение; </li> <li> <b>REGISTERED</b> - был ли посетитель
	* когда либо авторизован на сайте, возможные значения: <ul> <li> <b>Y</b> -
	* был; </li> <li> <b>N</b> - не был. </li> </ul> </li> <li> <b>FIRST_DATE1</b> - начальное
	* значение интервала для поля "дата первого захода на сайт"; </li> <li>
	* <b>FIRST_DATE2</b> - конечное значение интервала для поля "дата первого
	* захода на сайт"; </li> <li> <b>LAST_DATE1</b> - начальное значение интервала
	* для поля "дата последнего захода на сайт"; </li> <li> <b>LAST_DATE2</b> -
	* конечное значение интервала для поля "дата первого захода на
	* сайт"; </li> <li> <b>PERIOD_DATE1</b> - начальное значение интервала для даты
	* посещения посетителем сайта; </li> <li> <b>PERIOD_DATE2</b> - конечно значение
	* интервала для даты посещения посетителем сайта; </li> <li> <b>SITE_ID</b>* - ID
	* сайта первого либо последнего захода; </li> <li> <b>SITE_ID_EXACT_MATCH</b> - если
	* значение равно "N", то при фильтрации по <b>SITE_ID</b> будет искаться
	* вхождение; </li> <li> <b>FIRST_SITE_ID</b>* - ID сайта первого захода; </li> <li>
	* <b>FIRST_SITE_ID_EXACT_MATCH</b> - если значение равно "N", то при фильтрации по
	* <b>FIRST_SITE_ID</b> будет искаться вхождение; </li> <li> <b>LAST_SITE_ID</b>* - ID сайта
	* последнего захода; </li> <li> <b>LAST_SITE_ID_EXACT_MATCH</b> - если значение равно
	* "N", то при фильтрации по <b>LAST_SITE_ID</b> будет искаться вхождение; </li>
	* <li> <b>URL</b>* - страница откуда впервые пришел посетитель, страница на
	* которую впервые пришел посетитель и последняя страница
	* просмотренная посетителем; </li> <li> <b>URL_EXACT_MATCH</b> - если значение
	* равно "Y", то при фильтрации по <b>URL</b> будет искаться точное
	* совпадение; </li> <li> <b>URL_404</b> - была ли <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#404">404 ошибка</a> на первой странице
	* или на последней странице посещенной посетителем, возможные
	* значения: <ul> <li> <b>Y</b> - была; </li> <li> <b>N</b> - не было. </li> </ul> </li> <li>
	* <b>USER_AGENT</b>* - UserAgent посетителя на последнем заходе; </li> <li>
	* <b>USER_AGENT_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>USER_AGENT</b> будет искаться точное совпадение; </li> <li> <b>ADV</b> - флаг
	* "приходил ли посетитель когда либо по рекламной кампании (не
	* равной NA/NA)", возможные значения: <ul> <li> <b>Y</b> - посетитель приходил
	* по какой либо рекламной кампании (не равной NA/NA); </li> <li> <b>N</b> - не
	* приходил никогда ни по одной рекламной кампании (не равной NA/NA).
	* </li> </ul> </li> <li> <b>ADV_ID</b> - ID рекламной кампании первого либо
	* последнего захода посетителя (при этом это мог быть как <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_first">прямой заход</a> так и <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_back">возврат</a> по рекламной
	* кампании); </li> <li> <b>REFERER1</b>* - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_id">идентификатор</a> referer1
	* рекламной кампании первого либо последнего захода посетителя;
	* </li> <li> <b>REFERER1_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>REFERER1</b> будет искаться точное совпадение; </li> <li> <b>REFERER2</b>* -
	* идентификатор referer2 рекламной кампании первого либо последнего
	* захода посетителя; </li> <li> <b>REFERER2_EXACT_MATCH</b> - если значение равно "Y",
	* то при фильтрации по <b>REFERER2</b> будет искаться точное совпадение;
	* </li> <li> <b>REFERER3</b>* - <a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#adv_referer3">дополнительный
	* параметр</a> referer3 рекламной кампании первого либо последнего
	* захода посетителя; </li> <li> <b>REFERER3_EXACT_MATCH</b> - если значение равно "Y",
	* то при фильтрации по <b>REFERER3</b> будет искаться точное совпадение;
	* </li> <li> <b>EVENTS1</b> - начальное значение для интервала кол-ва событий
	* сгенерированных посетителем; </li> <li> <b>EVENTS2</b> - конечное значение
	* для интервала кол-ва событий сгенерированных посетителем; </li> <li>
	* <b>SESS1</b> - начальное значение для интервала кол-ва сессий
	* сгенерированных посетителем; </li> <li> <b>SESS2</b> - конечное значение
	* для интервала кол-ва сессий сгенерированных посетителем; </li> <li>
	* <b>HITS1</b> - начальное значение для интервала кол-ва хитов
	* сгенерированных посетителем; </li> <li> <b>HITS2</b> - конечное значение
	* для интервала кол-ва хитов сгенерированных посетителем; </li> <li>
	* <b>FAVORITES</b> - флаг "добавлял ли посетитель сайт в "<a
	* href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#favorites">Избранное</a>"", возможные
	* значения: <ul> <li> <b>Y</b> - добавлял; </li> <li> <b>N</b> - не добавлял. </li> </ul> </li>
	* <li> <b>IP</b> - IP адрес посетителя сайта в последнем заходе; </li> <li>
	* <b>LANG</b> - <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#browser_lang">языки
	* установленные в настройках браузера</a> посетителя в последнем
	* заходе; </li> <li> <b>COUNTRY_ID</b>* - ID страны (двухсимвольный идентификатор)
	* посетителя в последнем заходе; </li> <li> <b>COUNTRY_ID_EXACT_MATCH</b> - если
	* значение равно "Y", то при фильтрации по <b>COUNTRY_ID</b> будет искаться
	* точное совпадение; </li> <li> <b>COUNTRY</b>* - название страны; </li> <li>
	* <b>COUNTRY_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>COUNTRY</b> будет искаться точное совпадение; </li> <li> <b>USER</b>* - ID, логин,
	* имя, фамилия <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#user">пользователя</a>,
	* под которыми посетитель последний раз был авторизован; </li> <li>
	* <b>USER_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по <b>USER</b>
	* будет искаться точное совпадение; </li> <li> <b>USER_ID</b>* - ID пользователя,
	* под которым посетитель последний раз был авторизован; </li> <li>
	* <b>USER_ID_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>USER_ID</b> будет искаться точное совпадение. </li> </ul> <br> * -
	* допускается <a href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная
	* логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности списка посетителей. Если значение равно
	* "true", то список был отфильтрован.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // выберем только тех посетителей UserAgent которых содержит "Opera"
	* $arFilter = array(
	*     "USER_AGENT" =&gt; "Opera"
	*     );
	* 
	* // получим список записей
	* $rs = <b>CGuest::GetList</b>(
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
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">Термин "Посетитель"</a>
	* </li></ul> </h<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cguest/getlist.php
	* @author Bitrix
	*/
	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();
		$strSqlSearch = "";

		$bGroup = false;
		$arrGroup = array(
			"G.ID" => true,
			"G.C_EVENTS" => true,
			"G.FIRST_SITE_ID" => true,
			"G.LAST_SITE_ID" => true,
			"G.SESSIONS" => true,
			"G.HITS" => true,
			"G.FAVORITES" => true,
			"G.FIRST_URL_FROM" => true,
			"G.FIRST_URL_TO" => true,
			"G.FIRST_URL_TO_404" => true,
			"G.FIRST_ADV_ID" => true,
			"G.FIRST_REFERER1" => true,
			"G.FIRST_REFERER2" => true,
			"G.FIRST_REFERER3" => true,
			"G.LAST_ADV_ID" => true,
			"G.LAST_ADV_BACK" => true,
			"G.LAST_REFERER1" => true,
			"G.LAST_REFERER2" => true,
			"G.LAST_REFERER3" => true,
			"G.LAST_USER_ID" => true,
			"G.LAST_USER_AUTH" => true,
			"G.LAST_URL_LAST" => true,
			"G.LAST_URL_LAST_404" => true,
			"G.LAST_USER_AGENT" => true,
			"G.LAST_IP" => true,
			"G.LAST_LANGUAGE" => true,
			"G.LAST_COUNTRY_ID" => true,
			"G.LAST_CITY_ID" => true,
			"G.FIRST_DATE" => true,
			"G.LAST_DATE" => true,
			"G.FIRST_SESSION_ID" => true,
			"G.LAST_SESSION_ID" => true,
			"CITY.REGION" => true,
			"CITY.NAME" => true,
		);

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
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("G.ID",$val,$match);
						break;
					case "REGISTERED":
						if ($val=="Y")
							$arSqlSearch[] = "G.LAST_USER_ID>0 and G.LAST_USER_ID is not null";
						elseif ($val=="N")
							$arSqlSearch[] = "G.LAST_USER_ID<=0 or G.LAST_USER_ID is null";
						break;
					case "FIRST_DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "G.FIRST_DATE >= ".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "FIRST_DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "G.FIRST_DATE < ".CStatistics::DBDateAdd($DB->CharToDateFunction($val, "SHORT"), 1);
						break;
					case "LAST_DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "G.LAST_DATE >= ".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "LAST_DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "G.LAST_DATE < ".CStatistics::DBDateAdd($DB->CharToDateFunction($val, "SHORT"), 1);
						break;
					case "PERIOD_DATE1":
						ResetFilterLogic();
						if (CheckDateTime($val))
						{
							$arSqlSearch[] = "S.DATE_FIRST >= ".$DB->CharToDateFunction($val, "SHORT");
							$from0 = " INNER JOIN b_stat_session S ON (S.GUEST_ID = G.ID) ";
							$select0 = "count(S.ID) as SESS,";
							$bGroup = true;
						}
						break;
					case "PERIOD_DATE2":
						ResetFilterLogic();
						if (CheckDateTime($val))
						{
							$arSqlSearch[] = "S.DATE_LAST < ".CStatistics::DBDateAdd($DB->CharToDateFunction($val, "SHORT"), 1);
							$from0 = " INNER JOIN b_stat_session S ON (S.GUEST_ID = G.ID) ";
							$select0 = "count(S.ID) as SESS,";
							$bGroup = true;
						}
						break;
					case "SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("G.LAST_SITE_ID, G.FIRST_SITE_ID", $val, $match);
						break;
					case "LAST_SITE_ID":
					case "FIRST_SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("G.".$key, $val, $match);
						break;
					case "URL":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("G.FIRST_URL_FROM,G.FIRST_URL_TO,G.LAST_URL_LAST", $val, $match, array("/","\\",".","?","#",":"));
						break;
					case "URL_404":
						if ($val=="Y")
							$arSqlSearch[] = "G.FIRST_URL_TO_404='Y' or	G.LAST_URL_LAST_404='Y'";
						elseif ($val=="N")
							$arSqlSearch[] = "G.FIRST_URL_TO_404='N' and G.LAST_URL_LAST_404='N'";
						break;
					case "USER_AGENT":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("G.LAST_USER_AGENT", $val, $match);
						break;
					case "ADV":
						if ($val=="Y")
						{
							$arSqlSearch[] = "(
									G.FIRST_ADV_ID>0 and
									G.FIRST_ADV_ID is not null and
									G.FIRST_REFERER1<>'NA' and G.FIRST_REFERER2<>'NA'
								or
									G.LAST_ADV_ID>0 and
									G.LAST_ADV_ID is not null and
									G.LAST_REFERER1<>'NA' and G.LAST_REFERER2<>'NA'
								)";
						}
						elseif ($val=="N")
						{
							$arSqlSearch[] = "((
										G.FIRST_ADV_ID<=0 or
										G.FIRST_ADV_ID is null or
										(G.FIRST_REFERER1='NA' and G.FIRST_REFERER2='NA')
									) and (
										G.LAST_ADV_ID<=0 or
										G.LAST_ADV_ID is null or
										(G.LAST_REFERER1='NA' and G.LAST_REFERER2='NA')
									))";
						}
						break;
					case "ADV_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("G.FIRST_ADV_ID,G.LAST_ADV_ID", $val, $match);
						break;
					case "REFERER1":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("G.FIRST_REFERER1,G.LAST_REFERER1", $val, $match);
						break;
					case "REFERER2":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("G.FIRST_REFERER2,G.LAST_REFERER2", $val, $match);
						break;
					case "REFERER3":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("G.FIRST_REFERER3,G.LAST_REFERER3", $val, $match);
						break;
					case "EVENTS1":
						$arSqlSearch[] = "G.C_EVENTS>='".intval($val)."'";
						break;
					case "EVENTS2":
						$arSqlSearch[] = "G.C_EVENTS<='".intval($val)."'";
						break;
					case "SESS1":
						$arSqlSearch[] = "G.SESSIONS>='".intval($val)."'";
						break;
					case "SESS2":
						$arSqlSearch[] = "G.SESSIONS<='".intval($val)."'";
						break;
					case "HITS1":
						$arSqlSearch[] = "G.HITS>='".intval($val)."'";
						break;
					case "HITS2":
						$arSqlSearch[] = "G.HITS<='".intval($val)."'";
						break;
					case "FAVORITES":
						if ($val=="Y")
							$arSqlSearch[] = "G.FAVORITES='Y'";
						elseif ($val=="N")
							$arSqlSearch[] = "G.FAVORITES<>'Y'";
						break;
					case "IP":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("G.LAST_IP",$val,$match,array("."));
						break;
					case "LANG":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("G.LAST_LANGUAGE", $val, $match);
						break;
					case "COUNTRY_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("G.LAST_COUNTRY_ID", $val, $match);
						break;
					case "COUNTRY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("C.NAME", $val, $match);
						$select1 .= " , C.NAME LAST_COUNTRY_NAME ";
						$from2 = " LEFT JOIN b_stat_country C ON (C.ID = G.LAST_COUNTRY_ID) ";
						$arrGroup["C.NAME"] = true;
						$bGroup = true;
						break;
					case "REGION":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("CITY.REGION", $val, $match);
						break;
					case "CITY_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("G.LAST_CITY_ID", $val, $match);
						break;
					case "CITY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("CITY.NAME", $val, $match);
						break;
					case "USER":
						if(COption::GetOptionString("statistic", "dbnode_id") <= 0)
						{
							$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
							$arSqlSearch[] = $DB->IsNull("G.LAST_USER_ID","0").">0";
							$arSqlSearch[] = GetFilterQuery("G.LAST_USER_ID,A.LOGIN,A.LAST_NAME,A.NAME", $val, $match);
							$select1 .= ", ".$DB->Concat($DB->IsNull("A.NAME","''"), "' '", $DB->IsNull("A.LAST_NAME","''"))." USER_NAME, A.LOGIN";
							$from1 = "LEFT JOIN  b_user A ON (A.ID = G.LAST_USER_ID) ";
							$arrGroup["A.NAME"] = true;
							$arrGroup["A.LAST_NAME"] = true;
							$arrGroup["A.LOGIN"] = true;
							$bGroup = true;
						}
						break;
					case "USER_ID":
						if(COption::GetOptionString("statistic", "dbnode_id") <= 0)
						{
							$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
							$arSqlSearch[] = $DB->IsNull("G.LAST_USER_ID","0").">0";
							$arSqlSearch[] = GetFilterQuery("G.LAST_USER_ID", $val, $match);
							$select1 .= ", ".$DB->Concat($DB->IsNull("A.NAME","''"), "' '", $DB->IsNull("A.LAST_NAME","''"))." USER_NAME, A.LOGIN";
							$from1 = "LEFT JOIN  b_user A ON (A.ID = G.LAST_USER_ID) ";
							$arrGroup["A.NAME"] = true;
							$arrGroup["A.LAST_NAME"] = true;
							$arrGroup["A.LOGIN"] = true;
							$bGroup = true;
						}
						break;
				}
			}
		}
		if ($by == "s_id")					$strSqlOrder = "ORDER BY G.ID";
		elseif ($by == "s_first_site_id")	$strSqlOrder = "ORDER BY G.FIRST_SITE_ID";
		elseif ($by == "s_last_site_id")	$strSqlOrder = "ORDER BY G.LAST_SITE_ID";
		elseif ($by == "s_events")			$strSqlOrder = "ORDER BY G.C_EVENTS";
		elseif ($by == "s_sessions")		$strSqlOrder = "ORDER BY G.SESSIONS";
		elseif ($by == "s_hits")			$strSqlOrder = "ORDER BY G.HITS";
		elseif ($by == "s_first_date")		$strSqlOrder = "ORDER BY G.FIRST_DATE";
		elseif ($by == "s_first_url_from")	$strSqlOrder = "ORDER BY G.FIRST_URL_FROM";
		elseif ($by == "s_first_url_to")	$strSqlOrder = "ORDER BY G.FIRST_URL_TO";
		elseif ($by == "s_first_adv_id")	$strSqlOrder = "ORDER BY G.FIRST_ADV_ID";
		elseif ($by == "s_last_date")		$strSqlOrder = "ORDER BY ".CStatistics::DBFirstDate("G.LAST_DATE");
		elseif ($by == "s_last_user_id")	$strSqlOrder = "ORDER BY G.LAST_USER_ID";
		elseif ($by == "s_last_url_last")	$strSqlOrder = "ORDER BY G.LAST_URL_LAST";
		elseif ($by == "s_last_user_agent")	$strSqlOrder = "ORDER BY G.LAST_USER_AGENT";
		elseif ($by == "s_last_ip")			$strSqlOrder = "ORDER BY G.LAST_IP";
		elseif ($by == "s_last_adv_id")		$strSqlOrder = "ORDER BY G.LAST_ADV_ID";
		elseif ($by == "s_last_country_id")	$strSqlOrder = "ORDER BY G.LAST_COUNTRY_ID";
		elseif ($by == "s_last_region_name")	$strSqlOrder = "ORDER BY CITY.REGION";
		elseif ($by == "s_last_city_id")	$strSqlOrder = "ORDER BY G.LAST_CITY_ID";
		else
		{
			$by = "s_last_date";
			$strSqlOrder = "ORDER BY ".CStatistics::DBFirstDate("G.LAST_DATE");
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		if($bGroup)
		{
			$strSqlGroup = "GROUP BY ".implode(", ", array_keys($arrGroup));
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT /*TOP*/
				".$select0."
				G.ID, G.FIRST_SITE_ID, G.FIRST_SESSION_ID,
				G.LAST_SESSION_ID, G.LAST_SITE_ID,
				G.C_EVENTS, G.SESSIONS, G.HITS,  G.FAVORITES,
				G.FIRST_URL_FROM, G.FIRST_URL_TO, G.FIRST_URL_TO_404,
				G.FIRST_ADV_ID, G.FIRST_REFERER1, G.FIRST_REFERER2, G.FIRST_REFERER3,
				G.LAST_ADV_ID, G.LAST_ADV_BACK, G.LAST_REFERER1, G.LAST_REFERER2, G.LAST_REFERER3,
				G.LAST_USER_ID, G.LAST_USER_AUTH, G.LAST_URL_LAST, G.LAST_URL_LAST_404,
				G.LAST_USER_AGENT, G.LAST_IP, G.LAST_LANGUAGE, G.LAST_COUNTRY_ID,
				CITY.REGION as LAST_REGION_NAME,
				G.LAST_CITY_ID, CITY.NAME as LAST_CITY_NAME,
				".$DB->DateToCharFunction("G.FIRST_DATE")." FIRST_DATE,
				".$DB->DateToCharFunction("G.LAST_DATE")." LAST_DATE
				".$select1."
			FROM
				b_stat_guest G
			".$from0."
			".$from1."
			".$from2."
				LEFT JOIN b_stat_city CITY ON (CITY.ID = G.LAST_CITY_ID)
			WHERE
			".$strSqlSearch."
			".$strSqlGroup."
			".$strSqlOrder."
		";

		$res = $DB->Query(CStatistics::DBTopSql($strSql), false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}

	
	/**
	* <p>Возвращает данные по указанному <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителю</a>.</p>
	*
	*
	* @param int $guest_id  ID посетителя.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $guest_id = 1;
	* if ($rs = <b>CGuest::GetByID</b>($guest_id))
	* {
	*     $ar = $rs-&gt;Fetch();
	*     // выведем параметры посетителя
	*     echo "&lt;pre&gt;"; print_r($ar); echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">Термин "Посетитель"</a>
	* </li></ul> </h<a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cguest/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$ID = intval($ID);

		$res = $DB->Query("
			SELECT
				G.*,
				".$DB->DateToCharFunction("G.FIRST_DATE")." FIRST_DATE,
				".$DB->DateToCharFunction("G.LAST_DATE")." LAST_DATE,
				".CStatistics::DBDateDiff("FS.DATE_LAST","FS.DATE_FIRST")." FSESSION_TIME,
				".CStatistics::DBDateDiff("LS.DATE_LAST","LS.DATE_FIRST")." LSESSION_TIME,
				FS.HITS FSESSION_HITS,
				LS.HITS LSESSION_HITS,
				C.NAME COUNTRY_NAME,
				CITY.REGION REGION_NAME,
				CITY.NAME CITY_NAME,
				G.LAST_CITY_INFO
			FROM
				b_stat_guest G
				INNER JOIN b_stat_country C ON (C.ID = G.LAST_COUNTRY_ID)
				LEFT JOIN b_stat_session FS ON (FS.ID = G.FIRST_SESSION_ID)
				LEFT JOIN b_stat_session LS ON (LS.ID = G.LAST_SESSION_ID)
				LEFT JOIN b_stat_city CITY ON (CITY.ID = G.LAST_CITY_ID)
			WHERE
				G.ID = '$ID'
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$res = new CStatResult($res);
		return $res;
	}
}
?>
