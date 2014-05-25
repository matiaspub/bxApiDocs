<?
class CAllSearchTitle extends CDBResult
{
	var $_arPhrase = array();
	var $_arStemFunc;
	var $minLength = 1;

	public function __construct()
	{
		$this->_arStemFunc = stemming_init(LANGUAGE_ID);
	}

	public function Search($phrase = "", $nTopCount = 5, $arParams = array(), $bNotFilter = false, $order = "")
	{
		$DB = CDatabase::GetModuleConnection('search');
		$this->_arPhrase = stemming_split($phrase, LANGUAGE_ID);
		if(!empty($this->_arPhrase))
		{
			$nTopCount = intval($nTopCount);
			if($nTopCount <= 0)
				$nTopCount = 5;

			$arId = CSearchFullText::GetInstance()->searchTitle($phrase, $this->_arPhrase, $nTopCount, $arParams, $bNotFilter, $order);
			if (!is_array($arId))
			{
				return $this->searchTitle($phrase, $nTopCount, $arParams, $bNotFilter, $order);
			}
			elseif (!empty($arId))
			{
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
						,".$this->getRankFunction($phrase)." RANK1
					FROM
						b_search_content sc
						INNER JOIN b_search_content_site scsite ON sc.ID = scsite.SEARCH_CONTENT_ID
						INNER JOIN b_lang L ON scsite.SITE_ID = L.LID
					WHERE
						sc.ID in (".implode(",", $arId).")
						and scsite.SITE_ID = '".SITE_ID."'
					ORDER BY ".$this->getSqlOrder($bOrderByRank)."
				";

				$r = $DB->Query($DB->TopSql($strSql, $nTopCount+1));
				parent::CDBResult($r);
				return true;
			}
		}
		else
		{
			return false;
		}
	}

	public static function getRankFunction($phrase)
	{
		return "0";
	}

	public function setMinWordLength($minLength)
	{
		$minLength = intval($minLength);
		if ($minLength > 0)
			$this->minLength = $minLength;
	}

	public function Fetch()
	{
		$r = parent::Fetch();

		if($r)
		{
			if(strlen($r["SITE_URL"])>0)
				$r["URL"] = $r["SITE_URL"];

			if(substr($r["URL"], 0, 1)=="=")
			{
				foreach (GetModuleEvents("search", "OnSearchGetURL", true) as $arEvent)
					$r["URL"] = ExecuteModuleEventEx($arEvent, array($r));
			}

			$r["URL"] = str_replace(
				array("#LANG#", "#SITE_DIR#", "#SERVER_NAME#"),
				array($r["DIR"], $r["DIR"], $r["SERVER_NAME"]),
				$r["URL"]
			);
			$r["URL"] = preg_replace("'(?<!:)/+'s", "/", $r["URL"]);

			$r["NAME"] = htmlspecialcharsex($r["TITLE"]);

			$preg_template = "/(^|[^".$this->_arStemFunc["pcre_letters"]."])(".str_replace("/", "\\/", implode("|", array_map('preg_quote', array_keys($this->_arPhrase)))).")/i".BX_UTF_PCRE_MODIFIER;
			if(preg_match_all($preg_template, ToUpper($r["NAME"]), $arMatches, PREG_OFFSET_CAPTURE))
			{
				$c = count($arMatches[2]);
				if(defined("BX_UTF"))
				{
					for($j = $c-1; $j >= 0; $j--)
					{
						$prefix = mb_substr($r["NAME"], 0, $arMatches[2][$j][1], 'latin1');
						$instr  = mb_substr($r["NAME"], $arMatches[2][$j][1], mb_strlen($arMatches[2][$j][0], 'latin1'), 'latin1');
						$suffix = mb_substr($r["NAME"], $arMatches[2][$j][1] + mb_strlen($arMatches[2][$j][0], 'latin1'), mb_strlen($r["NAME"], 'latin1'), 'latin1');
						$r["NAME"] = $prefix."<b>".$instr."</b>".$suffix;
					}
				}
				else
				{
					for($j = $c-1; $j >= 0; $j--)
					{
						$prefix = substr($r["NAME"], 0, $arMatches[2][$j][1]);
						$instr  = substr($r["NAME"], $arMatches[2][$j][1], strlen($arMatches[2][$j][0]));
						$suffix = substr($r["NAME"], $arMatches[2][$j][1]+strlen($arMatches[2][$j][0]));
						$r["NAME"] = $prefix."<b>".$instr."</b>".$suffix;
					}
				}
			}
		}

		return $r;
	}

	public static function MakeFilterUrl($prefix, $arFilter)
	{
		if(!is_array($arFilter))
		{
			return "&".urlencode($prefix)."=".urlencode($arFilter);
		}
		else
		{
			$url = "";
			foreach($arFilter as $key => $value)
			{
				$url .= CSearchTitle::MakeFilterUrl($prefix."[".$key."]", $value);
			}
			return $url;
		}
	}
}
?>