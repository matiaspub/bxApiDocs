<?

/**
 * <b>CAutoDetect</b> - класс для поиска неизвестных <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#user_agent">UserAgent'ов</a>. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cautodetect/index.php
 * @author Bitrix
 */
class CAutoDetect
{
	
	/**
	* <p>Возвращает список незнакомых <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#user_agent">UserAgent'ов</a>. Метод анализирует список сессий, и собирает все UserAgent'ы которые не принадлежат ни одной <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#search">поисковой системе</a> и ни одному браузеру (UserAgent'ы браузеров задаются в настройках модуля "Статистика").</p>
	*
	*
	* @param string &$by = "s_counter" Поле для сортировки. Возможные значения: <ul> <li> <b>s_user_agent</b> - UserAgent;
	* </li> <li> <b>s_counter</b> - количество сессий. </li> </ul>
	*
	* @param string &$order = "desc" Порядок сортировки. Возможные значения: <ul> <li> <b>asc</b> - по
	* возрастанию; </li> <li> <b>desc</b> - по убыванию. </li> </ul>
	*
	* @param array $filter = array() Массив для фильтрации результирующего списка. В массиве
	* допустимы следующие ключи: <ul> <li> <b>LAST</b> - флаг определяющий какие
	* сессии буду анализироваться, возможные значения: <ul> <li> <b>Y</b> - за
	* текущий день; </li> <li> <b>N</b> - за предыдущие дни (не включая текущий).
	* </li> </ul> </li> <li> <b>USER_AGENT</b>* - искомый UserAgent (маска, либо его часть); </li>
	* <li> <b>USER_AGENT_EXACT_MATCH</b> - если значение равно "Y", то при фильтрации по
	* <b>USER_AGENT</b> будет искаться точное совпадение; </li> <li> <b>COUNTER1</b> -
	* начальное значение интервала для поля "количество сессий"; </li> <li>
	* <b>COUNTER2</b> - конечное значение интервала для поля "количество
	* сессий". </li> </ul> * - допускается <a
	* href="http://dev.1c-bitrix.ru/api_help/main/general/filter.php">сложная логика</a>
	*
	* @param bool &$is_filtered  Флаг отфильтрованности списка UserAgent'ов. Если значение равно "true",
	* то список был отфильтрован.
	*
	* @return CDBResult 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // выберем данные только за последний день
	* $arFilter = array(
	*     "LAST" =&gt; "Y"
	*     );
	* 
	* // получим список записей
	* $rs = <b>CAutoDetect::GetList</b>(
	*     ($by = "s_counter"),
	*     ($order = "desc"),
	*     $arFilter = array(),
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
	* <ul> <li>Пользовательскую документацию, раздел <em><a
	* href="http://www.1c-bitrix.ru/user_help/statistic/search_engines/autodetect_list.php">Веб-аналитика &gt;
	* Поисковые системы &gt; Автодетект</a></em> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cautodetect/getlist.php
	* @author Bitrix
	*/
	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();
		$arSqlSearch_h = Array();
		$strSqlSearch_h = "";
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
					case "LAST":
						$arSqlSearch[] = ($val=="Y") ? "S.DATE_STAT = curdate()" : "S.DATE_STAT<>curdate()";
						break;
					case "USER_AGENT":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.USER_AGENT",$val,$match);
						break;
					case "COUNTER1":
						$arSqlSearch_h[] = "COUNTER>=".intval($val);
						break;
					case "COUNTER2":
						$arSqlSearch_h[] = "COUNTER<=".intval($val);
						break;
				}
			}
			foreach($arSqlSearch_h as $sqlWhere)
				$strSqlSearch_h .= " and (".$sqlWhere.") ";
		}

		if ($by == "s_user_agent")
			$strSqlOrder = "ORDER BY S.USER_AGENT";
		elseif ($by == "s_counter")
			$strSqlOrder = "ORDER BY COUNTER";
		else
		{
			$by = "s_counter";
			$strSqlOrder = "ORDER BY COUNTER";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

		$strSql = "
			SELECT
				S.USER_AGENT,
				count(S.ID) COUNTER
			FROM
				b_stat_session S
			LEFT JOIN b_stat_browser B ON (
				length(B.USER_AGENT)>0
			and B.USER_AGENT is not null
			and	upper(S.USER_AGENT) like upper(B.USER_AGENT)
			)
			LEFT JOIN b_stat_searcher R ON (
				length(R.USER_AGENT)>0
			and	R.USER_AGENT is not null
			and	upper(S.USER_AGENT) like upper(concat('%',R.USER_AGENT,'%'))
			)
			WHERE
			$strSqlSearch
			and S.USER_AGENT is not null
			and S.USER_AGENT<>''
			and S.NEW_GUEST<>'N'
			and B.ID is null
			and R.ID is null
			GROUP BY S.USER_AGENT
			HAVING '1'='1' $strSqlSearch_h
			$strSqlOrder
		";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch) || strlen($strSqlSearch_h)>0);
		return $res;
	}
}
?>
