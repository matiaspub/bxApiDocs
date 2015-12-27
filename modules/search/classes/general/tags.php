<?

/**
 * Класс поддержки тегов. </html
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/classes/csearchtags/index.php
 * @author Bitrix
 */
class CSearchTags
{
	
	/**
	* <p>Получение списка тегов элементов поискового индекса. Метод динамичный.</p> <p>Данный метод использует технологию управляемого кеширования в случае соответствующей настройки <a href="http://dev.1c-bitrix.ru/api_help/search/constants.php">констант модуля поиска</a>: CACHED_b_search_tags и CACHED_b_search_tags_len.</p>
	*
	*
	* @param array $arSelect = array() Массив, содержащий поля для выборки. <br><br> Название поля может
	* принимать значение: <ul> <li> <b>NAME</b> - тег;</li> <li> <b>CNT</b> - частота тега,
	* количество элементов поискового индекса содержащих этот тег;</li>
	* <li> <b>DATE_CHANGE</b> - максимальная дата модификации (в полном формате)
	* элементов поискового индекса содержащих этот тег;</li> </ul> Не
	* обязательный параметр. По умолчанию равен: <pre class="syntax"> array(<br>
	* "NAME",<br> "CNT",<br> )<br></pre>
	*
	* @param array $arFilter = array() Массив, содержащий фильтр в виде наборов "название
	* поля"=&gt;"значение фильтра". <br><br> Название поля может принимать
	* значение: <ul> <li> <b>SITE_ID</b> - массив идентификаторов сайтов;</li> <li>
	* <b>TAG</b> - начало тега, будут возвращены все теги начинающиеся с
	* этого значения;</li> <li> <b>MODULE_ID</b> - идентификатор модуля;</li> <li>
	* <b>PARAM1</b> - первый параметр элемента;</li> <li> <b>PARAM2</b> - второй параметр
	* элемента;</li> </ul> Пример: <pre class="syntax"> array(<br> "SITE_ID"=&gt;array("s1"),<br>
	* "TAG"=&gt;"We",<br> "MODULE_ID"=&gt;"iblock",<br> )<br></pre>
	*
	* @param array $arOrder = array() Массив, содержащий признак сортировки в виде наборов "название
	* поля"=&gt;"направление". <br><br> Название поля может принимать
	* значение: <ul> <li> <b>NAME</b> - тег;</li> <li> <b>CNT</b> - частота тега, количество
	* элементов поискового индекса содержащих этот тег;</li> <li>
	* <b>DATE_CHANGE</b> - максимальная дата модификации (в полном формате)
	* элементов поискового индекса содержащих этот тег;</li> </ul>
	* Направление сортировки может принимать значение: <ul> <li> <b>ASC</b> - по
	* возрастанию;</li> <li> <b>DESC</b> - по убыванию.</li> </ul> Не обязательный
	* параметр. По умолчанию равен: <pre class="syntax"> array(<br> "NAME"=&gt;"ASC",<br> )<br></pre>
	*
	* @param int $limit = 100 Ограничение количества тегов в результатах.
	*
	* @return CDBResult <p>Возвращается результат запроса типа <a
	* href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a>. При выборке из
	* результата методами класса CDBResult становятся доступны поля
	* перечисленные в параметре arSelect.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>//подключение модуля поиска<br>if(CModule::IncludeModule('search'))<br>{<br>	$rsTags = CSearchTags::GetList(<br>		array(),<br>		array(<br>			"MODULE_ID" =&gt; "iblock",<br>		),<br>		array(<br>			"CNT" =&gt; "DESC",<br>		),<br>		10<br>	);<br>	while($arTag = $rsTags-&gt;Fetch())<br>		print_r($arTag);<br>}<br>?&gt;<br>
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_help/search/constants.php">Константы модуля
	* поиска</a></li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/search/classes/csearchtags/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arSelect = array(), $arFilter = array(), $arOrder = array(), $limit = 100)
	{
		global $USER;
		$DB = CDatabase::GetModuleConnection('search');
		static $arFilterEvents = false;

		$arQuerySelect = array();
		if(!is_array($arSelect))
			$arSelect = array();
		if(count($arSelect) < 1)
			$arSelect = array(
				"NAME",
				"CNT",
			);
		$bJoinSearchContent = false;
		foreach($arSelect as $key => $value)
		{
			$value = strtoupper($value);
			switch($value)
			{
				case "NAME":
					$arQuerySelect["NAME"] = "stags.NAME";
					break;
				case "CNT":
					$arQuerySelect["CNT"] = "COUNT(DISTINCT stags.SEARCH_CONTENT_ID) as CNT";
					break;
				case "DATE_CHANGE":
					$arQuerySelect["DC_TMP"] = "MAX(sc.DATE_CHANGE) as DC_TMP";
					$arQuerySelect["FULL_DATE_CHANGE"] = $DB->DateToCharFunction("MAX(sc.DATE_CHANGE)", "FULL")." as FULL_DATE_CHANGE";
					$arQuerySelect["DATE_CHANGE"] = $DB->DateToCharFunction("MAX(sc.DATE_CHANGE)", "SHORT")." as DATE_CHANGE";
					$bJoinSearchContent = true;
					break;
			}
		}

		$arQueryWhere = array();
		if(!is_array($arFilter))
			$arFilter = array(
				"TAG" => $arFilter,
				"SITE_ID" => array(SITE_ID),
			);
		if(empty($arFilter["SITE_ID"]) && array_key_exists("TAG", $arFilter))
			$arFilter["SITE_ID"] = array(SITE_ID);
		if(array_key_exists("SITE_ID", $arFilter) && !is_array($arFilter["SITE_ID"]))
			$arFilter["SITE_ID"] = array($arFilter["SITE_ID"]);

		$strTag = "";
		foreach($arFilter as $key => $value)
		{
			$key = strtoupper($key);
			switch($key)
			{
			case "SITE_ID":
				$arSites = array();
				foreach($value as $site_id)
				{
					$arSites[$DB->ForSql($site_id, 2)] = true;
				}
				$arSites = array_keys($arSites);
				if(count($arSites) == 1)
					$arQueryWhere[] = "stags.SITE_ID = '".$arSites[0]."'";
				elseif(count($arSites) > 1)
					$arQueryWhere[] = "stags.SITE_ID in ('".implode("', '", $arSites)."')";
				break;
			case "TAG":
				$arTags = tags_prepare($value, $arFilter["SITE_ID"][0]);
				if(count($arTags) > 0)
				{
					$strTag = array_pop($arTags);
					$arQueryWhere[] = "UPPER(stags.NAME) LIKE '".$DB->ForSql(ToUpper($strTag))."%'";
				}
				break;
			case "MODULE_ID":
			case "PARAM1":
			case "PARAM2":
				$arQueryWhere[] = "sc.".$key." ='".$DB->ForSql($value)."'";
				$bJoinSearchContent = true;
				break;
			case "PARAMS":
				if(is_array($value))
				{
					foreach($value as $p_key => $p_val)
					{
						if(is_array($p_val))
						{
							foreach($p_val as $i=>$val2)
								$p_val[$i] = $DB->ForSQL($val2);
							$p_where = " in ('".implode("', '", $p_val)."')";
						}
						else
						{
							$p_where = " = '".$DB->ForSQL($p_val)."'";
						}
						$arQueryWhere[] = "EXISTS (SELECT * FROM b_search_content_param WHERE SEARCH_CONTENT_ID = stags.SEARCH_CONTENT_ID AND PARAM_NAME = '".$DB->ForSQL($p_key)."' AND PARAM_VALUE ".$p_where.")";
					}
				}
				break;
			default:
				if(!is_array($arFilterEvents))
				{
					$arFilterEvents = GetModuleEvents("search", "OnSearchPrepareFilter", true);
				}
				//Try to get someone to make the filter sql
				foreach($arFilterEvents as $arEvent)
				{
					$sql = ExecuteModuleEventEx($arEvent, array("sc.", $key, $value));
					if(strlen($sql))
					{
						$arQueryWhere[] = "(".$sql.")";
						$bJoinSearchContent = true;
						break;
					}
				}
			}
		}

		$arQueryOrder = array();
		if(!is_array($arOrder))
			$arOrder = array();
		if(count($arOrder) < 1)
			$arOrder = array(
				"NAME" => "ASC",
			);
		foreach($arOrder as $key => $value)
		{
			$key = strtoupper($key);
			$value = strtoupper($value)=="DESC"? "DESC": "ASC";
			switch($key)
			{
				case "NAME":
				case "CNT":
					$arQueryOrder[$key] = $key." ".$value;
					break;
				case "DATE_CHANGE":
					$arQueryOrder[$key] = "DC_TMP ".$value;
					$arQuerySelect["DC_TMP"] = "MAX(sc.DATE_CHANGE) as DC_TMP";
					$arQuerySelect["FULL_DATE_CHANGE"] = $DB->DateToCharFunction("MAX(sc.DATE_CHANGE)", "FULL")." as FULL_DATE_CHANGE";
					$arQuerySelect["DATE_CHANGE"] = $DB->DateToCharFunction("MAX(sc.DATE_CHANGE)", "SHORT")." as DATE_CHANGE";
					$bJoinSearchContent = true;
					break;
			}
		}
		if(count($arQueryOrder) < 1)
			$arQueryOrder = array(
				"NAME" => "NAME ASC",
			);

		$strSql = "
			SELECT /*TOP*/
				".implode("\n,", $arQuerySelect)."
			FROM b_search_tags stags
				".($bJoinSearchContent? "INNER JOIN b_search_content sc ON sc.ID = stags.SEARCH_CONTENT_ID": "")."
			WHERE
				".CSearch::CheckPermissions("stags.SEARCH_CONTENT_ID")."
				".(count($arQueryWhere) > 0? "AND ".implode("\nAND ", $arQueryWhere): "")."
			GROUP BY stags.NAME
			ORDER BY ".implode(", ", $arQueryOrder)."
		";

		if($limit!==false)
		{
			$limit = intVal($limit);
			if($limit <= 0 || ($limit > COption::GetOptionInt("search", "max_result_size")))
				$limit = COption::GetOptionInt("search", "max_result_size");
			if($limit < 1)
				$limit = 100;

			$strSql = CSearch::FormatLimit($strSql, $limit);
		}
		else
		{
			$strSql = str_replace("/*TOP*/", "", $strSql);
		}

		if((CACHED_b_search_tags!==false) && ($limit!==false) && (strlen($strTag)<=CACHED_b_search_tags_len))
		{
			global $CACHE_MANAGER;
			$path = "b_search_tags";
			while(strlen($strTag) > 0)
			{
				$path .= "/_".ord(substr($strTag, 0, 1));
				$strTag = substr($strTag, 1);
			}
			$cache_id = "search_tags:".md5($strSql);
			if($CACHE_MANAGER->Read(CACHED_b_search_tags, $cache_id, $path))
			{
				$arTags = $CACHE_MANAGER->Get($cache_id);
			}
			else
			{
				$arTags = array();
				$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				while($ar = $res->Fetch())
				{
					$arTags[]=$ar;
				}
				$CACHE_MANAGER->Set($cache_id, $arTags);
			}
			$res = new CDBResult;
			$res->InitFromArray($arTags);
			return $res;
		}
		else
		{
			return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}

	public static function CleanCache($arTags = "", $content_id = false)
	{
		if(CACHED_b_search_tags !== false)
		{
			if($content_id !== false)
			{
				$DB = CDatabase::GetModuleConnection('search');
				$rs = $DB->Query("SELECT NAME FROM b_search_tags WHERE SEARCH_CONTENT_ID = ".intval($content_id), false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$arTags = array();
				while($ar = $rs->Fetch())
				{
					if($ar["NAME"])
						$arTags[] = $ar["NAME"];
				}
				CSearchTags::CleanCache($arTags);
			}
			else
			{
				if(!is_array($arTags))
					$arTags = array($arTags);
				$arPath = array();
				foreach($arTags as $tag)
				{
					if(strlen($tag) > 0)
						$path = "b_search_tags/_".ord(substr($tag, 0, 1));
					else
						$path = "b_search_tags";
					$arPath[$path] = true;
				}
				global $CACHE_MANAGER;
				foreach($arPath as $path=>$value)
					$CACHE_MANAGER->CleanDir($path);
			}
		}
	}
}
?>
