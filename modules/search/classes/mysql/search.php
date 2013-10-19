<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/search/classes/general/search.php");


/**
 * Класс для индексирования сайта и осуществления поиска по индексу
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/index.php
 * @author Bitrix
 */
class CSearch extends CAllSearch
{
	var $arForumTopics = array();

	public function DBNavStart()
	{
		//total rows count
		$this->NavRecordCount = mysql_num_rows($this->result);
		if($this->NavRecordCount < 1)
			return;

		if($this->NavShowAll)
			$this->NavPageSize = $this->NavRecordCount;

		//calculate total pages depend on rows count. start with 1
		$this->NavPageCount = floor($this->NavRecordCount/$this->NavPageSize);
		if($this->NavRecordCount % $this->NavPageSize > 0)
			$this->NavPageCount++;

		//page number to display. start with 1
		$this->NavPageNomer = ($this->PAGEN < 1 || $this->PAGEN > $this->NavPageCount? ($_SESSION[$this->SESS_PAGEN] < 1 || $_SESSION[$this->SESS_PAGEN] > $this->NavPageCount? 1:$_SESSION[$this->SESS_PAGEN]):$this->PAGEN);

		//rows to skip
		$NavFirstRecordShow = $this->NavPageSize * ($this->NavPageNomer-1);
		$NavLastRecordShow = $this->NavPageSize;

		if($this->SqlTraceIndex)
		{
			list($usec, $sec) = explode(" ", microtime());
			$start_time = ((float)$usec + (float)$sec);
		}

		while($NavFirstRecordShow > 0)
		{
			if(($res = mysql_fetch_array($this->result, MYSQL_ASSOC)))
			{
				if(
					$res["MODULE_ID"] == "forum"
					&& array_key_exists($res["PARAM2"], $this->arForumTopics)
				)
					$this->NavRecordCount--; //eat forum topic duplicates
				else
					$NavFirstRecordShow--;

				if($res["MODULE_ID"] == "forum")
					$this->arForumTopics[$res["PARAM2"]] = true;
			}
			else
			{
				break;
			}
		}

		$temp_arrray = array();
		while($NavLastRecordShow > 0)
		{
			if(($res = mysql_fetch_array($this->result, MYSQL_ASSOC)))
			{
				if(
					$res["MODULE_ID"] == "forum"
					&& array_key_exists($res["PARAM2"], $this->arForumTopics)
				)
					$this->NavRecordCount--; //eat forum topic duplicates
				else
				{
					if($this->arUserMultyFields)
						foreach($this->arUserMultyFields as $FIELD_NAME=>$flag)
							if($res[$FIELD_NAME])
								$res[$FIELD_NAME] = unserialize($res[$FIELD_NAME]);
					$temp_arrray[] = $res;
					$NavLastRecordShow--;
				}

				if($res["MODULE_ID"] == "forum")
					$this->arForumTopics[$res["PARAM2"]] = true;
			}
			else
			{
				break;
			}
		}

		//Adjust total pages depend on rows count. start with 1
		$this->NavPageCount = floor($this->NavRecordCount/$this->NavPageSize);
		if($this->NavRecordCount % $this->NavPageSize > 0)
			$this->NavPageCount++;

		if($this->SqlTraceIndex)
		{
			list($usec, $sec) = explode(" ", microtime());
			$end_time = ((float)$usec + (float)$sec);
			$exec_time = round($end_time-$start_time, 10);
			$GLOBALS["DB"]->arQueryDebug[$this->SqlTraceIndex - 1]["TIME"] += $exec_time;
			$GLOBALS["DB"]->timeQuery += $exec_time;
		}

		$this->nSelectedCount = $this->NavRecordCount;
		$this->arResult = $temp_arrray;
	}

	public function MakeSQL($query, $strSqlWhere, $strSort, $bIncSites, $bStem)
	{
		global $USER;
		$DB = CDatabase::GetModuleConnection('search');

		$bDistinct = false;
		$arSelect = array(
			"ID" => "sc.ID",
			"MODULE_ID" => "sc.MODULE_ID",
			"ITEM_ID" => "sc.ITEM_ID",
			"TITLE" => "sc.TITLE",
			"TAGS" => "sc.TAGS",
			"BODY" => "sc.BODY",
			"PARAM1" => "sc.PARAM1",
			"PARAM2" => "sc.PARAM2",
			"UPD" => "sc.UPD",
			"DATE_FROM" => "sc.DATE_FROM",
			"DATE_TO" => "sc.DATE_TO",
			"URL" => "sc.URL",
			"CUSTOM_RANK" => "sc.CUSTOM_RANK",
			"FULL_DATE_CHANGE" => $DB->DateToCharFunction("sc.DATE_CHANGE")." as FULL_DATE_CHANGE",
			"DATE_CHANGE" => $DB->DateToCharFunction("sc.DATE_CHANGE", "SHORT")." as DATE_CHANGE",
		);
		if(BX_SEARCH_VERSION > 1)
		{
			if($this->Query->bText)
				$arSelect["SEARCHABLE_CONTENT"] = "sct.SEARCHABLE_CONTENT";
			$arSelect["USER_ID"] = "sc.USER_ID";
		}
		else
		{
			$arSelect["LID"] = "sc.LID";
			$arSelect["SEARCHABLE_CONTENT"] = "sc.SEARCHABLE_CONTENT";
		}

		if(strpos($strSort, "TITLE_RANK") !== false)
		{
			$strSelect = "";
			if($bStem)
			{
				foreach($this->Query->m_stemmed_words as $stem)
				{
					if(strlen($strSelect) > 0)
						$strSelect .= " + ";
					$strSelect .= "if(locate('".$stem."', upper(sc.TITLE)) > 0, 1, 0)";
				}
				$arSelect["TITLE_RANK"] = $strSelect." as TITLE_RANK";
			}
			else
			{
				foreach($this->Query->m_words as $word)
				{
					if(strlen($strSelect) > 0)
						$strSelect .= " + ";
					$strSelect .= "if(locate('".$DB->ForSql(ToUpper($word))."', upper(sc.TITLE)) > 0, 1, 0)";
				}
				$arSelect["TITLE_RANK"] = $strSelect." as TITLE_RANK";
			}
		}

		if($bStem)
		{
			if(BX_SEARCH_VERSION > 1)
				$strStemList = implode(", ", $this->Query->m_stemmed_words_id);
			else
				$strStemList = "'".implode("' ,'", $this->Query->m_stemmed_words)."'";
		}

		$bWordPos = BX_SEARCH_VERSION > 1 && COption::GetOptionString("search", "use_word_distance") == "Y";

		if($bIncSites && $bStem)
		{
			$arSelect["SITE_URL"] = "scsite.URL as SITE_URL";
			$arSelect["SITE_ID"] = "scsite.SITE_ID";

			if(!preg_match("/(sc|sct)./", $query))
			{
				$strSqlWhere = preg_replace('# AND st.TF >= [0-9\.,]+#i', "", $strSqlWhere);

				if(count($this->Query->m_stemmed_words) > 1)
					$arSelect["RANK"] = "stt.RANK as RANK";
				else
					$arSelect["RANK"] = "stt.TF as RANK";

				$strSql = "
				FROM b_search_content sc
					".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
					INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID
					".(count($this->Query->m_stemmed_words) > 1?
						"INNER JOIN  (
							select search_content_id, max(st.TF) TF, ".($bWordPos? "if(STDDEV(st.PS)-".$this->normdev(count($this->Query->m_stemmed_words))." between -0.000001 and 1, 1/STDDEV(st.PS), 0) + ": "")."sum(st.TF/sf.FREQ) as RANK
							from b_search_content_stem st, b_search_content_freq sf
							where st.language_id = '".$this->Query->m_lang."'
							and st.stem = sf.stem
							and sf.language_id = st.language_id
							and st.stem in (".$strStemList.")
							".($this->tf_hwm > 0? "and st.TF >= ".number_format($this->tf_hwm, 2, ".", ""): "")."
							".(strlen($this->tf_hwm_site_id) > 0? "and sf.SITE_ID = '".$DB->ForSQL($this->tf_hwm_site_id, 2)."'": "and sf.SITE_ID IS NULL")."
							group by st.search_content_id
							having (".$query.")
						) stt ON sc.id = stt.search_content_id"
						:"INNER JOIN b_search_content_stem stt ON sc.id = stt.search_content_id"
					)."
				WHERE
				".CSearch::CheckPermissions("sc.ID")."
				".(count($this->Query->m_stemmed_words) > 1? "": "
					and stt.language_id = '".$this->Query->m_lang."'
					and stt.stem in (".$strStemList.")
					".($this->tf_hwm > 0? "and stt.TF >= ".number_format($this->tf_hwm, 2, ".", ""): "")."")."
				".$strSqlWhere."
				";
			}
			else
			{
				if(count($this->Query->m_stemmed_words) > 1)
				{
					if($bWordPos)
						$arSelect["RANK"] = "if(STDDEV(st.PS)-".$this->normdev(count($this->Query->m_stemmed_words))." between -0.000001 and 1, 1/STDDEV(st.PS), 0) + sum(st.TF/sf.FREQ) as RANK";
					else
						$arSelect["RANK"] = "sum(st.TF/sf.FREQ) as RANK";
				}
				else
				{
					$arSelect["RANK"] = "st.TF as RANK";
				}

				$strSql = "
				FROM b_search_content sc
					".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
					INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID
					INNER JOIN b_search_content_stem st ON sc.id = st.search_content_id+0
					".(count($this->Query->m_stemmed_words)>1?
						"INNER JOIN b_search_content_freq sf ON
							st.language_id = sf.language_id
							and st.stem=sf.stem
							".(strlen($this->tf_hwm_site_id) > 0?
								"and sf.SITE_ID = '".$DB->ForSQL($this->tf_hwm_site_id, 2)."'":
								"and sf.SITE_ID IS NULL"
							):
						""
					)."
				WHERE
					".CSearch::CheckPermissions("sc.ID")."
					AND st.STEM in (".$strStemList.")
					".(count($this->Query->m_stemmed_words)>1? "AND sf.STEM in (".$strStemList.")": "")."
					AND st.language_id='".$this->Query->m_lang."'
					".$strSqlWhere."
				GROUP BY
					sc.ID
					,scsite.URL
					,scsite.SITE_ID
				HAVING
					(".$query.")
				";
			}
		}
		elseif($bIncSites && !$bStem)
		{
			$bDistinct = true;

			$arSelect["SITE_URL"] = "scsite.URL as SITE_URL";
			$arSelect["SITE_ID"] = "scsite.SITE_ID";
			$arSelect["RANK"] = "1 as RANK";

			if($this->Query->bTagsSearch)
			{
				$strSql = "
				FROM b_search_content sc
					".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
					INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID
					INNER JOIN b_search_tags stags ON (sc.ID = stags.SEARCH_CONTENT_ID)
				WHERE
					".CSearch::CheckPermissions("sc.ID")."
					".$strSqlWhere."
					".(is_array($this->Query->m_tags_words) && count($this->Query->m_tags_words)>0? "AND stags.NAME in ('".implode("','", $this->Query->m_tags_words)."')": "")."
				GROUP BY
					sc.ID
					,scsite.URL
					,scsite.SITE_ID
				HAVING
					".$query."
				";
			}
			else
			{
				$strSql = "
				FROM
					".($this->Query->bText? "
						b_search_content_text sct
						INNER JOIN b_search_content sc ON sc.ID = sct.SEARCH_CONTENT_ID
						INNER JOIN b_search_content_site scsite ON sc.ID = scsite.SEARCH_CONTENT_ID
					": "
						b_search_content sc
						INNER JOIN b_search_content_site scsite ON sc.ID = scsite.SEARCH_CONTENT_ID
					")."
				WHERE
					".CSearch::CheckPermissions("sc.ID")."
					AND (".$query.")
					".$strSqlWhere."
				";
			}
		}
		elseif(!$bIncSites && $bStem)
		{
			if(BX_SEARCH_VERSION <= 1)
				$arSelect["SITE_ID"] = "sc.LID as SITE_ID";

			if(count($this->Query->m_stemmed_words) > 1)
			{
				if($bWordPos)
					$arSelect["RANK"] = "if(STDDEV(st.PS)-".$this->normdev(count($this->Query->m_stemmed_words))." between -0.000001 and 1, 1/STDDEV(st.PS), 0) + sum(st.TF/sf.FREQ) as RANK";
				else
					$arSelect["RANK"] = "sum(st.TF/sf.FREQ) as RANK";
			}
			else
			{
				$arSelect["RANK"] = "st.TF as RANK";
			}

			$strSql = "
			FROM b_search_content sc
				".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
				INNER JOIN b_search_content_stem st ON sc.id = st.search_content_id
				".(count($this->Query->m_stemmed_words)>1?
					"INNER JOIN b_search_content_freq sf ON
						st.language_id = sf.language_id
						and st.stem=sf.stem
						".(strlen($this->tf_hwm_site_id) > 0?
							"and sf.SITE_ID = '".$DB->ForSQL($this->tf_hwm_site_id, 2)."'":
							"and sf.SITE_ID IS NULL"
						):
					""
				)."
			WHERE
				".CSearch::CheckPermissions("sc.ID")."
				AND st.STEM in (".$strStemList.")
				".(count($this->Query->m_stemmed_words)>1? "AND sf.STEM in (".$strStemList.")": "")."
				AND st.language_id='".$this->Query->m_lang."'
				".$strSqlWhere."
			".(count($this->Query->m_stemmed_words)>1?"
			GROUP BY
				sc.ID
			HAVING
				(".$query.") ": "")."
			";
		}
		else //if(!$bIncSites && !$bStem)
		{
			$bDistinct = true;

			if(BX_SEARCH_VERSION <= 1)
				$arSelect["SITE_ID"] = "sc.LID as SITE_ID";
			$arSelect["RANK"] = "1 as RANK";

			$strSql = "
			FROM b_search_content sc
				".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
				".($this->Query->bTagsSearch? "INNER JOIN b_search_tags stags ON (sc.ID = stags.SEARCH_CONTENT_ID)
			WHERE
				".CSearch::CheckPermissions("sc.ID")."
				".$strSqlWhere."
				".(is_array($this->Query->m_tags_words) && count($this->Query->m_tags_words)>0? "AND stags.NAME in ('".implode("','", $this->Query->m_tags_words)."')": "")."
			GROUP BY
				sc.ID
			HAVING
				(".$query.")" :
			" WHERE
				(".$query.")
				".$strSqlWhere."
			")."
			";
		}

		$limit = COption::GetOptionInt("search", "max_result_size");
		if($limit < 1)
			$limit = 500;

		$strSelect = "SELECT ".($bDistinct? "DISTINCT": "")."\n".implode("\n,", $arSelect);

		$strRatingJoin = "";
		if(
			($this->flagsUseRatingSort & 0x01)
			&& COption::GetOptionString("search", "use_social_rating") == "Y"
			&& BX_SEARCH_VERSION == 2
			&& COption::GetOptionString("search", "dbnode_id") <= 0
		)
		{
			$rsMinMax = $DB->Query("select max(TOTAL_VALUE) RATING_MAX, min(TOTAL_VALUE) RATING_MIN from b_rating_voting");
			$arMinMax = $rsMinMax->Fetch();
			if($arMinMax)
			{
				$RATING_MAX = doubleval($arMinMax["RATING_MAX"]);
				if($RATING_MAX < 0)
					$RATING_MAX = 0;

				$RATING_MIN = doubleval($arMinMax["RATING_MIN"]);
				if($RATING_MIN > 0)
					$RATING_MIN = 0;
			}

			if($RATING_MAX != 0 || $RATING_MIN != 0)
				return "
					SELECT sc.*, sc.RANK +
						if(rv.TOTAL_VALUE > 0, ".($RATING_MAX > 0? "rv.TOTAL_VALUE/".$RATING_MAX: "0").",
						if(rv.TOTAL_VALUE < 0, ".($RATING_MIN < 0? "rv.TOTAL_VALUE/".abs($RATING_MIN): "0").",
						0
					)) SRANK
					,".$DB->IsNull('rvv.VALUE', '0')." RATING_USER_VOTE_VALUE
					,sc.ENTITY_TYPE_ID RATING_TYPE_ID
					,sc.ENTITY_ID RATING_ENTITY_ID
					,rv.TOTAL_VOTES RATING_TOTAL_VOTES
					,rv.TOTAL_POSITIVE_VOTES RATING_TOTAL_POSITIVE_VOTES
					,rv.TOTAL_NEGATIVE_VOTES RATING_TOTAL_NEGATIVE_VOTES
					,rv.TOTAL_VALUE RATING_TOTAL_VALUE
					FROM (
					".$strSelect."
					,sc.ENTITY_TYPE_ID, sc.ENTITY_ID
					".$strSql.$strSort."\nLIMIT ".$limit."
					) sc
					LEFT JOIN b_rating_voting rv ON rv.ENTITY_TYPE_ID = sc.ENTITY_TYPE_ID AND rv.ENTITY_ID = sc.ENTITY_ID
					LEFT JOIN b_rating_vote rvv ON rvv.ENTITY_TYPE_ID = sc.ENTITY_TYPE_ID AND rvv.ENTITY_ID = sc.ENTITY_ID AND rvv.USER_ID = ".intval($USER->GetId())."
				".str_replace(" RANK", " SRANK", $strSort);
		}

		return $strSelect."\n".$strSql.$strSort."\nLIMIT ".$limit;
	}

	public function tagsMakeSQL($query, $strSqlWhere, $strSort, $bIncSites, $bStem, $limit = 100)
	{
		global $USER;
		$DB = CDatabase::GetModuleConnection('search');
		$limit = intVal($limit);
		if($bStem && count($this->Query->m_stemmed_words)>1)
		{//We have to make some magic in case quotes was used in query
		//We have to move (sc.searchable_content LIKE '%".ToUpper($word)."%') from $query to $strSqlWhere
			while(preg_match("/(AND\s+\([sct]+.searchable_content LIKE \'\%.+?\%\'\))/", $query, $arMatches))
			{
				$strSqlWhere .= $arMatches[0];
				$query = str_replace($arMatches[0], "", $query);
			}
		}

		if($bStem)
		{
			if(BX_SEARCH_VERSION > 1)
				$strStemList = implode(", ", $this->Query->m_stemmed_words_id);
			else
				$strStemList = "'".implode("' ,'", $this->Query->m_stemmed_words)."'";
		}

		if($bIncSites && $bStem)
			$strSql = "
				SELECT
					stags.NAME
					,COUNT(DISTINCT stags.SEARCH_CONTENT_ID) as CNT
					,MAX(sc.DATE_CHANGE) DC_TMP
					,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)")." as FULL_DATE_CHANGE
					,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)", "SHORT")." as DATE_CHANGE
					".(count($this->Query->m_stemmed_words)>1 && strpos($query, "searchable_content")!==false
						?(BX_SEARCH_VERSION > 1? ",sct.SEARCHABLE_CONTENT": ",sc.SEARCHABLE_CONTENT")
						: ""
					)."
				FROM b_search_tags stags
					INNER JOIN b_search_content sc ON (stags.SEARCH_CONTENT_ID=sc.ID)
					".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
					INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID
					INNER JOIN b_search_content_stem st ON sc.id = st.search_content_id
					".(count($this->Query->m_stemmed_words)>1?
						"INNER JOIN b_search_content_freq sf ON
							st.language_id = sf.language_id
							and st.stem=sf.stem
							".(strlen($this->tf_hwm_site_id) > 0?
								"and sf.SITE_ID = '".$DB->ForSQL($this->tf_hwm_site_id, 2)."'":
								"and sf.SITE_ID IS NULL"
							):
						""
					)."
				WHERE
					".CSearch::CheckPermissions("sc.ID")."
					AND st.STEM in (".$strStemList.")
					".(count($this->Query->m_stemmed_words)>1? "AND sf.STEM in (".$strStemList.")": "")."
					AND st.language_id='".$this->Query->m_lang."'
					AND stags.SITE_ID = scsite.SITE_ID
					".$strSqlWhere."
				GROUP BY
					stags.NAME
				".((count($this->Query->m_stemmed_words)>1)?"
				HAVING
					(".$query.") ": "")."
				".$strSort."
			";
		elseif($bIncSites && !$bStem)
		{
			if($query == "1=1")
			{
				$strSql = "
					SELECT
						stags2.NAME
						,COUNT(DISTINCT stags2.SEARCH_CONTENT_ID) as CNT
						,MAX(sc.DATE_CHANGE) DC_TMP
						,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)")." as FULL_DATE_CHANGE
						,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)", "SHORT")." as DATE_CHANGE
					FROM b_search_tags stags2
						INNER JOIN b_search_content sc ON (stags2.SEARCH_CONTENT_ID=sc.ID)
						".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
						INNER JOIN b_search_content_site scsite ON (sc.ID=scsite.SEARCH_CONTENT_ID AND stags2.SITE_ID=scsite.SITE_ID)
					WHERE
						".CSearch::CheckPermissions("sc.ID")."
						AND ".($this->Query->bTagsSearch? (
						//Index range scan optimization (make it for other queries ???)
						is_array($this->Query->m_tags_words) && count($this->Query->m_tags_words)?
						"stags.name in ('".implode("', '", $this->Query->m_tags_words)."')":
						"(1=1)"
						) : "(".$query.")")." ".$strSqlWhere."
					GROUP BY
						stags2.NAME
					".$strSort."
				";
			}
			else
			{
				$strSql = "
					SELECT
						stags2.NAME
						,COUNT(DISTINCT stags.SEARCH_CONTENT_ID) as CNT
						,MAX(sc.DATE_CHANGE) DC_TMP
						,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)")." as FULL_DATE_CHANGE
						,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)", "SHORT")." as DATE_CHANGE
					FROM b_search_tags stags2
						INNER JOIN b_search_tags stags ON (stags.SEARCH_CONTENT_ID=stags2.SEARCH_CONTENT_ID and stags.SITE_ID=stags2.SITE_ID)
						INNER JOIN b_search_content sc ON (stags.SEARCH_CONTENT_ID=sc.ID)
						".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
						INNER JOIN b_search_content_site scsite ON (sc.ID=scsite.SEARCH_CONTENT_ID AND stags.SITE_ID=scsite.SITE_ID)
					WHERE
						".CSearch::CheckPermissions("sc.ID")."
						AND ".($this->Query->bTagsSearch? (
						//Index range scan optimization (make it for other queries ???)
						is_array($this->Query->m_tags_words) && count($this->Query->m_tags_words)?
						"stags.name in ('".implode("', '", $this->Query->m_tags_words)."')":
						"(1=1)"
						) : "(".$query.")")." ".$strSqlWhere."
					GROUP BY
						stags2.NAME
						".($this->Query->bTagsSearch? "
					HAVING
						(".$query.")": "")."
					".$strSort."
				";
			}
		}
		elseif(!$bIncSites && $bStem)
			$strSql = "
				SELECT
					stags.NAME
					,COUNT(DISTINCT stags.SEARCH_CONTENT_ID) as CNT
					,MAX(sc.DATE_CHANGE) DC_TMP
					, ".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)")." as FULL_DATE_CHANGE
					, ".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)", "SHORT")." as DATE_CHANGE
					".(count($this->Query->m_stemmed_words)>1 && strpos($query, "searchable_content")!==false
						?(BX_SEARCH_VERSION > 1? ",sct.SEARCHABLE_CONTENT": ",sc.SEARCHABLE_CONTENT")
						: ""
					)."
				FROM b_search_tags stags
					INNER JOIN b_search_content sc ON (stags.SEARCH_CONTENT_ID=sc.ID)
					".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
					INNER JOIN b_search_content_stem st ON sc.id = st.search_content_id
					".(count($this->Query->m_stemmed_words)>1?
						"INNER JOIN b_search_content_freq sf ON
							st.language_id = sf.language_id
							and st.stem=sf.stem
							".(strlen($this->tf_hwm_site_id) > 0?
								"and sf.SITE_ID = '".$DB->ForSQL($this->tf_hwm_site_id, 2)."'":
								"and sf.SITE_ID IS NULL"
							):
						""
					)."
				WHERE
					".CSearch::CheckPermissions("sc.ID")."
					AND st.STEM in (".$strStemList.")
					".(count($this->Query->m_stemmed_words)>1? "AND sf.STEM in (".$strStemList.")": "")."
					AND st.language_id='".$this->Query->m_lang."'
					".$strSqlWhere."
				GROUP BY
					stags.NAME
				".(count($this->Query->m_stemmed_words)>1?"
					,sc.ID
				HAVING
					(".$query.") ": "")."
				".$strSort."
			";
		else //if(!$bIncSites && !$bStem)
			$strSql = "
				SELECT
					stags2.NAME
					,COUNT(DISTINCT stags.SEARCH_CONTENT_ID) as CNT
					,MAX(sc.DATE_CHANGE) DC_TMP
					,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)")." as FULL_DATE_CHANGE
					,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)", "SHORT")." as DATE_CHANGE
				FROM b_search_tags stags2
					INNER JOIN b_search_tags stags ON (stags.SEARCH_CONTENT_ID=stags2.SEARCH_CONTENT_ID and stags.SITE_ID=stags2.SITE_ID)
					INNER JOIN b_search_content sc ON (stags.SEARCH_CONTENT_ID=sc.ID)
					".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
				WHERE
					".CSearch::CheckPermissions("sc.ID")."
					AND ".($this->Query->bTagsSearch? (
					//Index range scan optimization (make it for other queries ???)
					is_array($this->Query->m_tags_words) && count($this->Query->m_tags_words)?
					"stags.name in ('".implode("', '", $this->Query->m_tags_words)."')":
					"(1=1)"
					) : "(".$query.")")." ".$strSqlWhere."
				GROUP BY
					stags2.NAME
					".($this->Query->bTagsSearch? "
				HAVING
					(".$query.")": "")."
				".$strSort."
			";

		if($limit < 1)
			$limit = 150;

		return $strSql."LIMIT ".$limit;
	}

	public static function ReindexLock()
	{
		//do not lock for mysql database
	}

	public static function DeleteOld($SESS_ID, $MODULE_ID="", $SITE_ID="")
	{
		$DB = CDatabase::GetModuleConnection('search');

		$strFilter = "";
		if($MODULE_ID!="")
			$strFilter.=" AND MODULE_ID = '".$DB->ForSql($MODULE_ID)."' ";

		$strJoin = "";
		if($SITE_ID!="")
		{
			$strFilter.=" AND scsite.SITE_ID = '".$DB->ForSql($SITE_ID)."' ";
			$strJoin.=" INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID ";
		}

		if(!is_array($SESS_ID))
			$SESS_ID = array($SESS_ID);

		$strSql = "
			SELECT ID
			FROM b_search_content sc
			".$strJoin."
			WHERE (UPD not in ('".implode("', '", $SESS_ID)."') OR UPD IS NULL)
			".$strFilter."
		";

		$arEvents = GetModuleEvents("search", "OnBeforeIndexDelete", true);

		$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($ar = $rs->Fetch())
		{
			foreach($arEvents as $arEvent)
				ExecuteModuleEventEx($arEvent, array("SEARCH_CONTENT_ID = ".$ar["ID"]));

			$DB->Query("DELETE FROM b_search_content_param WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_right WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_site WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_title WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_stem WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if(BX_SEARCH_VERSION > 1)
				$DB->Query("DELETE FROM b_search_content_text WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_tags WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content WHERE ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		CSearchTags::CleanCache();
	}

	public static function DeleteForReindex($MODULE_ID)
	{
		$DB = CDatabase::GetModuleConnection('search');

		$MODULE_ID = $DB->ForSql($MODULE_ID);
		$strSql = "SELECT ID FROM b_search_content WHERE MODULE_ID = '".$MODULE_ID."'";

		$arEvents = GetModuleEvents("search", "OnBeforeIndexDelete", true);

		$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($ar = $rs->Fetch())
		{
			foreach($arEvents as $arEvent)
				ExecuteModuleEventEx($arEvent, array("SEARCH_CONTENT_ID = ".$ar["ID"]));

			$DB->Query("DELETE FROM b_search_content_param WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_right WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_site WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_title WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_stem WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if(BX_SEARCH_VERSION > 1)
				$DB->Query("DELETE FROM b_search_content_text WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_tags WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content WHERE ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		CSearchTags::CleanCache();
	}

	public static function OnLangDelete($lang)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$DB->Query("
			DELETE FROM b_search_content_site
			WHERE SITE_ID='".$DB->ForSql($lang)."'
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		CSearchTags::CleanCache();
	}

	
	/**
	 * <p>Функция изменяет права на доступ к проиндексированной информации данного модуля.</p>
	 *
	 *
	 *
	 *
	 * @param string $MODULE_ID  Код модуля, права на доступ к информации которого необходимо
	 * поменять.
	 *
	 *
	 *
	 * @param array $arGroups  Массив кодов групп, которые имеют право на чтение
	 * проиндексированной информации.
	 *
	 *
	 *
	 * @param string $ITEM_ID = false Код элемента, права на доступ к которому необходимо поменять.
	 * Если этот параметр равен false, то ограничение по коду элемента не
	 * устанавливается (изменяются права на доступ к элементу с любыми
	 * кодами). Не обязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @param string $PARAM1 = false Первый параметр элемента, ограничивающий набор
	 * проиндексированных элементов, права на доступ к которым
	 * необходимо поменять. Если этот параметр равен false, то ограничение
	 * по первому параметру элемента не устанавливается. Не
	 * обязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @param string $PARAM2 = false Второй параметр элемента, ограничивающий набор
	 * проиндексированных элементов, права на доступ к которым
	 * необходимо поменять. Если этот параметр равен false, то ограничение
	 * по второму параметру элемента не устанавливается. Не
	 * обязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @param string $SITE_ID = false Сайт проиндексированного элемента, ограничивающий набор
	 * элементов, права на доступ к которым необходимо поменять. Если
	 * этот параметр равен false, то ограничение по сайту элемента не
	 * устанавливается. Необязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @return void 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $Forum_ID = 5;
	 * $arGroups = CForum::GetAccessPermissions($Forum_ID);
	 * $arGPerm = Array();
	 * for ($i=0; $i &lt; count($arGroups); $i++)
	 * {
	 * 	if ($arGroups[$i][1]&gt;="E")
	 * 	{
	 * 		$arGPerm[] = $arGroups[$i][0];
	 * 		if($arGroups[$i][0]==2)
	 * 			break;
	 * 	}
	 * }
	 * CSearch::ChangePermission("forum", $arGPerm, false, $Forum_ID);
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/changepermission.php
	 * @author Bitrix
	 */
	public static function ChangePermission($MODULE_ID, $arGroups, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $SITE_ID=false, $PARAMS=false)
	{
		$DB = CDatabase::GetModuleConnection('search');

		$arNewGroups = array();
		foreach($arGroups as $group_id)
		{
			if(is_numeric($group_id))
				$arNewGroups[$group_id] = "G".intval($group_id);
			else
				$arNewGroups[$group_id] = $group_id;
		}

		$strSqlWhere = CSearch::__PrepareFilter(array(
			"MODULE_ID"=>$MODULE_ID,
			"ITEM_ID"=>$ITEM_ID,
			"PARAM1"=>$PARAM1,
			"PARAM2"=>$PARAM2,
			"SITE_ID"=>$SITE_ID,
			"PARAMS"=>$PARAMS,
		), $bIncSites);

		if($strSqlWhere)
		{
			$strSqlJoin1 = "INNER JOIN b_search_content sc ON sc.ID = b_search_content_right.SEARCH_CONTENT_ID";
			//Copy first exists into inner join in hopeless try to defeat MySQL optimizer
			if(preg_match('#^\\s*EXISTS (\\(SELECT \\* FROM b_search_content_param WHERE SEARCH_CONTENT_ID = sc.ID AND PARAM_NAME = \'[^\']+\' AND PARAM_VALUE  = \'[^\']+\'\\))#', $strSqlWhere, $match))
			{
				$subTable = str_replace("SEARCH_CONTENT_ID = sc.ID AND", "", $match[1]);
				$strSqlJoin2 = "INNER JOIN ".$subTable." p1 ON p1.SEARCH_CONTENT_ID = sc.ID";
			}
			else
			{
				$strSqlJoin2 = "";
			}
		}
		else
		{
			$strSqlJoin1 = "";
			$strSqlJoin2 = "";
		}

		$DB->Query("
			DELETE b_search_content_right
			FROM b_search_content_right
			".$strSqlJoin1."
			".$strSqlJoin2."
			".($strSqlWhere
				?"WHERE ".$strSqlWhere
				:""
			)
		, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		foreach($arNewGroups as $group_code)
		{
			$DB->Query("
				INSERT INTO b_search_content_right (SEARCH_CONTENT_ID, GROUP_CODE)
				SELECT sc.ID, '".$DB->ForSQL($group_code, 100)."'
				FROM b_search_content sc
				".$strSqlJoin2."
				".($strSqlWhere?
					"WHERE ".$strSqlWhere:
					""
				)."
			", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}

	
	/**
	 * <p>Функция изменяет привязку проиндексированной информации к сайтам.</p>
	 *
	 *
	 *
	 *
	 * @param string $MODULE_ID  Код модуля, права на доступ к информации которого необходимо
	 * поменять.
	 *
	 *
	 *
	 * @param array $arSite  Ассоциативный массив привязки к сайтам.Ключи - идентификаторы
	 * сайтов, а их значения пути к проиндексированной информации. <br>
	 *
	 *
	 *
	 * @param string $ITEM_ID = false Код элемента, права на доступ к которому необходимо поменять.
	 * Если этот параметр равен false, то ограничение по коду элемента не
	 * устанавливается (изменяются права на доступ к элементу с любыми
	 * кодами). Не обязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @param string $PARAM1 = false Первый параметр элемента, ограничивающий набор
	 * проиндексированных элементов, права на доступ к которым
	 * необходимо поменять. Если этот параметр равен false, то ограничение
	 * по первому параметру элемента не устанавливается. Не
	 * обязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @param string $PARAM2 = false Второй параметр элемента, ограничивающий набор
	 * проиндексированных элементов, права на доступ к которым
	 * необходимо поменять. Если этот параметр равен false, то ограничение
	 * по второму параметру элемента не устанавливается. Не
	 * обязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @param string $SITE_ID = false Сайт проиндексированного элемента, ограничивающий набор
	 * элементов, права на доступ к которым необходимо поменять. Если
	 * этот параметр равен false, то ограничение по сайту элемента не
	 * устанавливается. Необязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @return void 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$IBLOCK_ID = 5;<br>CSearch::ChangeSite("iblock", array("s1" =&gt; "=/new/localation/#ID#/"), false, $IBLOCK_ID);<br>?&gt;<br>
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/changesite.php
	 * @author Bitrix
	 */
	public static function ChangeSite($MODULE_ID, $arSite, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $SITE_ID=false)
	{
		$DB = CDatabase::GetModuleConnection('search');

		$strSqlWhere = CSearch::__PrepareFilter(Array("MODULE_ID"=>$MODULE_ID, "ITEM_ID"=>$ITEM_ID, "PARAM1"=>$PARAM1, "PARAM2"=>$PARAM2, "SITE_ID"=>$SITE_ID), $bIncSites);

		$strSql = "
			SELECT sc.ID
			FROM b_search_content sc
			".($bIncSites? "INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID": "")."
			WHERE
			".$strSqlWhere."
		";

		$r = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($arR = $r->Fetch())
		{
			$DB->Query("DELETE FROM b_search_content_site WHERE SEARCH_CONTENT_ID = ".$arR["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$bFirst = true;
			foreach($arSite as $site => $url)
			{
				if($bFirst)
				{
					if(BX_SEARCH_VERSION > 1)
						$strSql = "
							UPDATE b_search_content
							SET URL = '".$DB->ForSql($url, 2000)."'
							WHERE ID = ".$arR["ID"]."
						";
					else
						$strSql = "
							UPDATE b_search_content
							SET LID = '".$DB->ForSql($site, 2)."',
							URL = '".$DB->ForSql($url, 2000)."'
							WHERE ID = ".$arR["ID"]."
						";
					$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					$bFirst = false;
				}
				$strSql = "
					INSERT INTO b_search_content_site(SEARCH_CONTENT_ID, SITE_ID, URL)
					VALUES(".$arR["ID"].", '".$DB->ForSql($site, 2)."', '".$DB->ForSql($url, 2000)."')";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
	}

	
	/**
	 * <p>Функция удаляет из индекса поиска указанную информацию.</p>
	 *
	 *
	 *
	 *
	 * @param string $MODULE_ID  Код модуля, индексацию информации которого необходимо удалить .
	 *
	 *
	 *
	 * @param string $ITEM_ID = false Код элемента, индексацию информации которого необходимо удалить.
	 * Если этот параметр равен false, то ограничение по коду элемента не
	 * устанавливается (удаляется индексация информации с любыми
	 * кодами). Не обязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @param string $PARAM1 = false Первый параметр элемента, ограничивающий набор
	 * проиндексированных элементов, индексацию информации которых
	 * необходимо удалить. Если этот параметр равен false, то ограничение
	 * по первому параметру элемента не устанавливается. Не
	 * обязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @param string $PARAM2 = false Второй параметр элемента, ограничивающий набор
	 * проиндексированных элементов, индексацию информации которых
	 * необходимо удалить. Если этот параметр равен false, то ограничение
	 * по второму параметру элемента не устанавливается. Не
	 * обязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @param string $SITE_ID = false Сайт проиндексированого элемента, ограничивающий набор
	 * элементов, индексацию информации которых необходимо удалить.
	 * Если этот параметр равен false, то ограничение по сайту элемента не
	 * устанавливается. Необязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @return void 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $path = "/ru/my_files/file.php";
	 * if (unlink($_SERVER["DOCUMENT_ROOT"].$path))
	 * {
	 * 	if (CModule::IncludeModule("search"))
	 * 		CSearch::DeleteIndex("main", $path);
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/deleteindex.php
	 * @author Bitrix
	 */
	public static function DeleteIndex($MODULE_ID, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $SITE_ID=false)
	{
		$DB = CDatabase::GetModuleConnection('search');

		if($PARAM1 !== false && $PARAM2 !== false)
			$strSqlWhere = CSearch::__PrepareFilter(Array("MODULE_ID"=>$MODULE_ID, "ITEM_ID"=>$ITEM_ID, array("=PARAM1"=>$PARAM1, "PARAM2"=>$PARAM2), "SITE_ID"=>$SITE_ID), $bIncSites);
		else
			$strSqlWhere = CSearch::__PrepareFilter(Array("MODULE_ID"=>$MODULE_ID, "ITEM_ID"=>$ITEM_ID, "PARAM1"=>$PARAM1, "PARAM2"=>$PARAM2, "SITE_ID"=>$SITE_ID), $bIncSites);

		$strSql = "
			SELECT sc.ID
			FROM b_search_content sc
				".($bIncSites? "INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID" :"")."
			WHERE
			".$strSqlWhere."
		";

		$arEvents = GetModuleEvents("search", "OnBeforeIndexDelete", true);

		$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($ar = $rs->Fetch())
		{
			foreach($arEvents as $arEvent)
				ExecuteModuleEventEx($arEvent, array("SEARCH_CONTENT_ID = ".$ar["ID"]));

			$DB->Query("DELETE FROM b_search_content_param WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_right WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_site WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_title WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content_stem WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if(BX_SEARCH_VERSION > 1)
				$DB->Query("DELETE FROM b_search_content_text WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_tags WHERE SEARCH_CONTENT_ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query("DELETE FROM b_search_content WHERE ID = ".$ar["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		CSearchTags::CleanCache();
	}

	public static function FormatDateString($strField)
	{
		return "DATE_FORMAT(".$strField.", '%d.%m.%Y %H:%i:%s')";
	}

	public static function FormatLimit($strSql, $limit)
	{
		return str_replace("/*TOP*/", "", $strSql)."LIMIT ".intval($limit);
	}

	public static function Update($ID, $arFields)
	{
		$DB = CDatabase::GetModuleConnection('search');

		if(array_key_exists("~DATE_CHANGE", $arFields))
		{
			$arFields["DATE_CHANGE"] = $arFields["~DATE_CHANGE"];
			unset($arFields["~DATE_CHANGE"]);
		}
		elseif(array_key_exists("LAST_MODIFIED", $arFields))
		{
			$arFields["DATE_CHANGE"] = $arFields["LAST_MODIFIED"];
			unset($arFields["LAST_MODIFIED"]);
		}
		elseif(array_key_exists("DATE_CHANGE", $arFields))
		{
			$arFields["DATE_CHANGE"] = $DB->FormatDate($arFields["DATE_CHANGE"], "DD.MM.YYYY HH.MI.SS", CLang::GetDateFormat());
		}

		if(BX_SEARCH_VERSION > 1)
			unset($arFields["SEARCHABLE_CONTENT"]);

		$strUpdate = $DB->PrepareUpdate("b_search_content", $arFields);
		if(strlen($strUpdate) > 0)
		{
			$DB->Query("UPDATE b_search_content SET ".$strUpdate." WHERE ID=".intval($ID), false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}

	public static function CleanFreqCache($ID)
	{
		$DB = CDatabase::GetModuleConnection('search');

		$DB->Query("
			UPDATE
				b_search_content_freq F,
				b_search_content_stem S
			SET
				F.TF = null
			WHERE
				F.TF is not null
				AND F.LANGUAGE_ID = S.LANGUAGE_ID
				AND F.STEM = S.STEM
				AND S.SEARCH_CONTENT_ID = ".intval($ID)."
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function IndexTitle($arLID, $ID, $sTitle)
	{
		$DB = CDatabase::GetModuleConnection('search');
		static $CACHE_SITE_LANGS = array();
		$ID = intval($ID);

		$arLang=array();
		if(!is_array($arLID))
			$arLID = Array();
		foreach($arLID as $site=>$url)
		{
			$sql_site = $DB->ForSql($site);

			if(!array_key_exists($site, $CACHE_SITE_LANGS))
			{
				$db_site_tmp = CSite::GetByID($site);
				if ($ar_site_tmp = $db_site_tmp->Fetch())
					$CACHE_SITE_LANGS[$site] = array(
						"LANGUAGE_ID" => $ar_site_tmp["LANGUAGE_ID"],
						"CHARSET" => $ar_site_tmp["CHARSET"],
						"SERVER_NAME" => $ar_site_tmp["SERVER_NAME"]
					);
				else
					$CACHE_SITE_LANGS[$site] = false;
			}

			if(is_array($CACHE_SITE_LANGS[$site]))
			{
				$lang = $CACHE_SITE_LANGS[$site]["LANGUAGE_ID"];

				$arTitle = stemming_split($sTitle, $lang);
				if(!empty($arTitle))
				{
					$maxValuesLen = 2048;
					$strSqlPrefix = "
							insert ignore into b_search_content_title
							(SEARCH_CONTENT_ID, SITE_ID, WORD, POS)
							values
					";
					$strSqlValues = "";
					$strSqlSuffix = "";
					foreach($arTitle as $word => $pos)
					{
						$strSqlValues .= ",\n(".$ID.", '".$sql_site."', '".$DB->ForSql($word)."', ".$pos.")";
						if(strlen($strSqlValues) > $maxValuesLen)
						{
							$DB->Query($strSqlPrefix.substr($strSqlValues, 2), false, "File: ".__FILE__."<br>Line: ".__LINE__);
							$strSqlValues = "";
						}
					}
					if(strlen($strSqlValues) > 0)
					{
						$DB->Query($strSqlPrefix.substr($strSqlValues, 2), false, "File: ".__FILE__."<br>Line: ".__LINE__);
						$strSqlValues = "";
					}
				}
			}
		}
	}

	public static function RegisterStem($stem)
	{
		global $DB;
		static $cache = array();

		if(is_array($stem)) //This is batch check of the already exist stems
		{
			ksort($stem);

			$strSqlPrefix = "select * from b_search_stem where stem in (";
			$maxValuesLen = 4096;
			$maxValuesCnt = 1500;
			$strSqlValues = "";
			$i = 0;
			foreach($stem as $word => $count)
			{
				$strSqlValues .= ",'".$DB->ForSQL($word)."'";
				$i++;

				if(strlen($strSqlValues) > $maxValuesLen || $i > $maxValuesCnt)
				{
					$rs = $DB->Query($strSqlPrefix.substr($strSqlValues, 1).")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
					while($ar = $rs->Fetch())
						$cache[$ar["STEM"]] = $ar["ID"];

					$strSqlValues = "";
					$i = 0;
				}
			}

			if(strlen($strSqlValues) > 0)
			{
				$rs = $DB->Query($strSqlPrefix.substr($strSqlValues, 1).")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				while($ar = $rs->Fetch())
					$cache[$ar["STEM"]] = $ar["ID"];
			}

			return;
		}

		if(!isset($cache[$stem]))
		{
			$rs = $DB->Query("insert into b_search_stem (STEM) values ('".$DB->ForSQL($stem)."')", true);
			if($rs === false)
			{
				$rs = $DB->Query("select ID from b_search_stem WHERE STEM = '".$DB->ForSQL($stem)."'");
				$ar = $rs->Fetch();
				$cache[$stem] = $ar["ID"];
			}
			else
			{
				$cache[$stem] = $DB->LastID();
			}
		}

		return $cache[$stem];
	}

	public static function StemIndex($arLID, $ID, $sContent)
	{
		$DB = CDatabase::GetModuleConnection('search');
		static $CACHE_SITE_LANGS = array();
		$ID = intval($ID);

		$arLang = array();
		if(!is_array($arLID))
			$arLID = array();

		foreach($arLID as $site => $url)
		{
			if(!array_key_exists($site, $CACHE_SITE_LANGS))
			{
				$db_site_tmp = CSite::GetByID($site);
				if ($ar_site_tmp = $db_site_tmp->Fetch())
					$CACHE_SITE_LANGS[$site] = array(
						"LANGUAGE_ID" => $ar_site_tmp["LANGUAGE_ID"],
						"CHARSET" => $ar_site_tmp["CHARSET"],
						"SERVER_NAME" => $ar_site_tmp["SERVER_NAME"]
					);
				else
					$CACHE_SITE_LANGS[$site] = false;
			}
			if(is_array($CACHE_SITE_LANGS[$site]))
				$arLang[$CACHE_SITE_LANGS[$site]["LANGUAGE_ID"]] = true;
		}

		foreach($arLang as $lang=>$value)
		{
			$sql_lang = $DB->ForSql($lang);

			$arDoc = stemming($sContent, $lang);
			$docLength = array_sum($arDoc);

			if(BX_SEARCH_VERSION > 1)
			{
				$arPos = stemming($sContent, $lang, /*$bIgnoreStopWords*/false, /*$bReturnPositions*/true);
				CSearch::RegisterStem($arDoc);
			}

			if($docLength > 0)
			{
				$doc = "";
				$logDocLength = log($docLength<20?20:$docLength);
				$strSqlPrefix = "
						insert ignore into b_search_content_stem
						(SEARCH_CONTENT_ID, LANGUAGE_ID, STEM, TF".(BX_SEARCH_VERSION > 1? ",PS": "").")
						values
				";
				$maxValuesLen = 2048;
				$strSqlValues = "";

				if(BX_SEARCH_VERSION > 1)
				{
					foreach($arDoc as $word => $count)
					{
						$stem_id = CSearch::RegisterStem($word);
						//This is almost impossible, but happens
						if($stem_id > 0)
							$strSqlValues .= ",\n("
								.$ID
								.", '".$sql_lang."'"
								.", ".CSearch::RegisterStem($word)
								.", ".number_format(log($count+1)/$logDocLength, 4, ".", "")
								.", ".number_format($arPos[$word]/$count, 4, ".", "")
							.")";

						if(strlen($strSqlValues) > $maxValuesLen)
						{
							$DB->Query($strSqlPrefix.substr($strSqlValues, 2), false, "File: ".__FILE__."<br>Line: ".__LINE__);
							$strSqlValues = "";
						}
					}
				}
				else
				{
					foreach($arDoc as $word => $count)
					{
						$strSqlValues .= ",\n("
							.$ID
							.", '".$sql_lang."'"
							.", '".$DB->ForSQL($word)."'"
							.", ".number_format(log($count+1)/$logDocLength, 4, ".", "")
						.")";

						if(strlen($strSqlValues) > $maxValuesLen)
						{
							$DB->Query($strSqlPrefix.substr($strSqlValues, 2), false, "File: ".__FILE__."<br>Line: ".__LINE__);
							$strSqlValues = "";
						}
					}
				}

				if(strlen($strSqlValues) > 0)
				{
					$DB->Query($strSqlPrefix.substr($strSqlValues, 2), false, "File: ".__FILE__."<br>Line: ".__LINE__);
					$strSqlValues = "";
				}
			}
		}
	}

	public static function TagsIndex($arLID, $ID, $sContent)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$ID = intval($ID);

		if(!is_array($arLID))
			$arLID = Array();
		$sContent = str_replace("\x00", "", $sContent);

		foreach($arLID as $site_id => $url)
		{
			$sql_site_id  = $DB->ForSQL($site_id);

			$arTags = tags_prepare($sContent, $site_id);
			if(!empty($arTags))
			{
				$strSqlPrefix = "
						insert ignore into b_search_tags
						(SEARCH_CONTENT_ID, SITE_ID, NAME)
						values
				";
				$maxValuesLen = 2048;
				$strSqlValues = "";
				CSearchTags::CleanCache($arTags);
				foreach($arTags as $tag)
				{
					$strSqlValues .= ",\n(".$ID.", '".$sql_site_id."', '".$DB->ForSql($tag, 255)."')";
					if(strlen($strSqlValues) > $maxValuesLen)
					{
						$DB->Query($strSqlPrefix.substr($strSqlValues, 2), false, "File: ".__FILE__."<br>Line: ".__LINE__);
						$strSqlValues = "";
					}
				}
				if(strlen($strSqlValues) > 0)
				{
					$DB->Query($strSqlPrefix.substr($strSqlValues, 2), false, "File: ".__FILE__."<br>Line: ".__LINE__);
					$strSqlValues = "";
				}
			}
		}
	}

	
	/**
	 * <p>Функция изменяет проиндексированную информацию данного модуля.</p>
	 *
	 *
	 *
	 *
	 * @param string $MODULE_ID  Код модуля, проиндексированную информацию которого необходимо
	 * поменять.
	 *
	 *
	 *
	 * @param array $arFields  Массив новых значений для поискового индекса. Массив должен
	 * иметь следующую структуру: <ul> <li> <b>"DATE_CHANGE"</b> =&gt; дата изменения
	 * индексируемого элемента,</li> <li> <b>"URL"</b> =&gt; адрес индексируемого
	 * элемента,</li> <li> <b>"TITLE"</b> =&gt; заголовок индексируемого элемента,</li>
	 * <li> <b>"BODY"</b> =&gt; индексируемый текст,</li> <li> <b>"SEARCHABLE_CONTENT"</b> =&gt;
	 * индекс,</li> <li> <b>"PARAM1"</b> =&gt; первый параметр,</li> <li> <b>"PARAM2"</b> =&gt;
	 * второй параметр,</li> <li> <b>"PERMISSIONS"</b> =&gt; массив кодов групп, члены
	 * которых имеют право на чтение индексируемого элемента,</li> <li>
	 * <b>"SITE_ID"</b> =&gt; ассоциативный массив привязки к сайтам. Ключи -
	 * идентификаторы сайтов, а их значения пути к элементу. <br> </li> </ul>
	 * Атрибуты, значения которых не изменились, можно опустить.
	 *
	 *
	 *
	 * @param string $ITEM_ID = false Код элемента, индекс которого необходимо изменить. Если этот
	 * параметр равен false, то ограничение по коду элемента не
	 * устанавливается (изменяются индексы элементов с любыми кодами).
	 * Не обязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @param string $PARAM1 = false Первый параметр элемента, ограничивающий набор
	 * проиндексированных элементов, индексы которых необходимо
	 * поменять. Если этот параметр равен false, то ограничение по первому
	 * параметру элемента не устанавливается. Не обязательный параметр,
	 * по умолчанию равен false.
	 *
	 *
	 *
	 * @param string $PARAM2 = false Второй параметр элемента, ограничивающий набор
	 * проиндексированных элементов, индексы которых необходимо
	 * поменять. Если этот параметр равен false, то ограничение по второму
	 * параметру элемента не устанавливается. Не обязательный параметр,
	 * по умолчанию равен false.
	 *
	 *
	 *
	 * @param string $SITE_ID = false Сайт проиндексированого элемента, ограничивающий набор
	 * элементов, индекс которых необходимо поменять. Если этот
	 * параметр равен false, то ограничение по сайту элемента не
	 * устанавливается. Необязательный параметр, по умолчанию равен false.
	 *
	 *
	 *
	 * @return void 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$new_title = "Новый заголовок темы";<br>$fid = 5;    // Код форума<br>$tid = 128;    // Код темы<br>CSearch::ChangeIndex("forum",<br>	array(<br>		"TITLE" =&gt; $new_title<br>	),<br>	false, $fid, $tid);<br>?&gt;<br>
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/search/classes/csearch/changeindex.php
	 * @author Bitrix
	 */
	public static function ChangeIndex($MODULE_ID, $arFields, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $SITE_ID=false)
	{
		$DB = CDatabase::GetModuleConnection('search');

		if(array_key_exists("TITLE", $arFields))
			$arFields["TITLE"] = Trim($arFields["TITLE"]);

		if(array_key_exists("BODY", $arFields))
			$arFields["BODY"] = Trim($arFields["BODY"]);

		if(array_key_exists("PERMISSIONS", $arFields) && is_array($arFields["PERMISSIONS"]))
			CSearch::ChangePermission($MODULE_ID, $arFields["PERMISSIONS"], $ITEM_ID, $PARAM1, $PARAM2, $SITE_ID);

		if(array_key_exists("SITE_ID", $arFields) && is_array($arFields["SITE_ID"]))
			CSearch::ChangeSite($MODULE_ID, $arFields["SITE_ID"], $ITEM_ID, $PARAM1, $PARAM2, $SITE_ID);

		$strUpdate = $DB->PrepareUpdate("b_search_content", $arFields);
		if(strlen($strUpdate) > 0)
		{
			$strSqlWhere = CSearch::__PrepareFilter(Array("MODULE_ID"=>$MODULE_ID, "ITEM_ID"=>$ITEM_ID, "PARAM1"=>$PARAM1, "PARAM2"=>$PARAM2, "SITE_ID"=>$SITE_ID), $bIncSites);
			$strSql = "
				SELECT sc.ID
				FROM b_search_content sc
				".($bIncSites? "INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID": "")."
				".(strlen($strSqlWhere)>0? "WHERE ".$strSqlWhere: "")."
			";
			$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($ar = $rs->Fetch())
			{
				$strSql = "UPDATE b_search_content SET ".$strUpdate." WHERE ID=".$ar["ID"];
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

				if(array_key_exists("PARAMS", $arFields))
					CSearch::SetContentItemParams($ar["ID"], $arFields["PARAMS"]);
			}
		}
	}
}

class CSearchQuery extends CAllSearchQuery
{
	var $cnt = 0;
	public function BuildWhereClause($word)
	{
		$DB = CDatabase::GetModuleConnection('search');

		$this->cnt++;
		if($this->cnt > 10)
			return "1=1";

		if(isset($this->m_kav[$word]))
		{
			$word = $this->m_kav[$word];
			$bInQuotes = true;
		}
		else
		{
			$bInQuotes = false;
		}
		$this->m_words[] = $word;
		$word = $DB->ForSql($word, 100);

		if($this->bTagsSearch)
		{
			if(strpos($word, "%")===false)
			{
				//We can optimize query by doing range scan
				if(is_array($this->m_tags_words))
					$this->m_tags_words[] = $word;
				$op = "=";
			}
			else
			{
				//Optimization is not possible
				$this->m_tags_words = false;
				$op = "like";
			}
			return "(sum(stags.name ".$op." '".$word."')>0)";
		}
		elseif($this->bStemming && !$bInQuotes)
		{
			$word = ToUpper($word);
			$this->m_stemmed_words[] = $word;

			if(BX_SEARCH_VERSION > 1)
			{
				$rs = $DB->Query("select ID from b_search_stem where STEM='".$DB->ForSQL($word)."'");
				$ar = $rs->Fetch();
				$this->m_stemmed_words_id[] = intval($ar["ID"]);

				return "(sum(st.stem = ".intval($ar["ID"]).")>0)";
			}
			else
			{
				return "(sum(st.stem = '".$word."')>0)";
			}
		}
		else
		{
			if(BX_SEARCH_VERSION > 1)
			{
				$this->bText = true;
				return "(sct.searchable_content LIKE '%".str_replace(array("%", "_"), array("\\%", "\\_") ,ToUpper($word))."%')";
			}
			else
			{
				return "(sc.searchable_content LIKE '%".str_replace(array("%", "_"), array("\\%", "\\_") ,ToUpper($word))."%')";
			}
		}
	}
}
?>