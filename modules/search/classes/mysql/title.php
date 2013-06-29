<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/search/classes/general/title.php");

class CSearchTitle extends CAllSearchTitle
{
	public function Search($phrase = "", $nTopCount = 5, $arParams = array(), $bNotFilter = false, $order = "")
	{
		$DB = CDatabase::GetModuleConnection('search');
		$this->_arPhrase = stemming_split($phrase, LANGUAGE_ID);
		$bOrderByRank = ($order == "rank");

		if(!empty($this->_arPhrase))
		{
			$nTopCount = intval($nTopCount);
			if($nTopCount <= 0)
				$nTopCount = 5;

			$sqlHaving = array();
			$sqlWords = array();
			foreach(array_reverse($this->_arPhrase, true) as $word => $pos)
			{
				if(empty($sqlWords) && !preg_match("/[\\n\\r \\t]$/", $phrase))
					$s = $sqlWords[] = "ct.WORD like '".$DB->ForSQL($word)."%'";
				else
					$s = $sqlWords[] = "ct.WORD = '".$DB->ForSQL($word)."'";
				$sqlHaving[] = "(sum(".$s.") > 0)";
			}

			$bIncSites = false;
			$strSqlWhere = CSearch::__PrepareFilter($arParams, $bIncSites);
			if($bNotFilter)
			{
				if(!empty($strSqlWhere))
					$strSqlWhere = "NOT (".$strSqlWhere.")";
				else
					$strSqlWhere = "1=0";
			}

			$strSql = "
				SELECT
					sc.ID
					,sc.MODULE_ID
					,sc.ITEM_ID
					,sc.TITLE
					,sc.PARAM1
					,sc.PARAM2
					,sc.DATE_CHANGE
					,L.DIR
					,L.SERVER_NAME
					,sc.URL as URL
					,scsite.URL as SITE_URL
					,scsite.SITE_ID
					,if(locate('".$DB->ForSQL(ToUpper($phrase))."', upper(sc.TITLE)) > 0, 1, 0) RANK1
					,count(1) RANK2
					,min(ct.POS) RANK3
				FROM
					b_search_content_title ct
					INNER JOIN b_lang L ON ct.SITE_ID = L.LID
					inner join b_search_content sc on sc.ID = ct.SEARCH_CONTENT_ID
					INNER JOIN b_search_content_site scsite ON sc.ID = scsite.SEARCH_CONTENT_ID and ct.SITE_ID = scsite.SITE_ID
				WHERE
					".CSearch::CheckPermissions("sc.ID")."
					AND ct.SITE_ID = '".SITE_ID."'
					AND (".implode(" OR ", $sqlWords).")
					".(!empty($strSqlWhere)? "AND ".$strSqlWhere: "")."
				GROUP BY
					ID, MODULE_ID, ITEM_ID, TITLE, PARAM1, PARAM2, DATE_CHANGE, DIR, SERVER_NAME, URL, SITE_URL, SITE_ID
				".(count($sqlHaving) > 1? "HAVING ".implode(" AND ", $sqlHaving): "")."
				ORDER BY ".(
					$bOrderByRank?
						"RANK1 DESC, RANK2 DESC, RANK3 ASC, TITLE":
						"DATE_CHANGE DESC, RANK1 DESC, RANK2 DESC, RANK3 ASC, TITLE"
				)."
				LIMIT 0, ".($nTopCount+1)."
			";

			$r = $DB->Query($strSql);
			parent::CDBResult($r);
			return true;
		}
		else
		{

			return false;
		}
	}
}
?>