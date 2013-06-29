<?
class CSearchSuggest
{
	var $_filter_md5 = "";
	var $_phrase = "";

	public function __construct($strFilterMD5 = "", $phrase = "")
	{
		return $this->CSearchSuggest($strFilterMD5, $phrase);
	}

	public function CSearchSuggest($strFilterMD5 = "", $phrase = "")
	{
		$strFilterMD5 = strtolower($strFilterMD5);
		if(preg_match("/^[0-9a-f]{32}$/", $strFilterMD5))
			$this->_filter_md5 = $strFilterMD5;

		$phrase = ToLower(trim($phrase, " \t\n\r"));
		if($l = strlen($phrase))
		{
			if($l > 250)
			{
				$p = strrpos($phrase, ' ');
				if($p === false)
					$phrase = substr($phrase, 0, 250);
				else
					$phrase = substr($phrase, 0, $p);
			}
			$this->_phrase = $phrase;
		}
	}

	public function SetResultCount($result_count)
	{
		$DB = CDatabase::GetModuleConnection('search');
		if(strlen($this->_filter_md5) && strlen($this->_phrase))
		{
			$result_count = intval($result_count);
			$filter_md5 = $DB->ForSQL($this->_filter_md5);
			$phrase = $DB->ForSQL($this->_phrase, 250);

			$rsQueryStat = $DB->Query("
				SELECT ID, FILTER_MD5, RATE, DATEDIFF(now(), TIMESTAMP_X) DAYS, RESULT_COUNT
				FROM b_search_suggest
				WHERE SITE_ID = '".SITE_ID."'
				AND FILTER_MD5 = '".$filter_md5."'
				AND PHRASE = '".$phrase."'
			");

			$arQueryStat = $rsQueryStat->Fetch();
			if(!$arQueryStat)
			{
				$DB->Add("b_search_suggest", array(
					"SITE_ID" => SITE_ID,
					"FILTER_MD5" => $this->_filter_md5,
					"PHRASE" => $this->_phrase,
					"RATE" => 1.0,
					"~TIMESTAMP_X" => $DB->CurrentTimeFunction(),
					"RESULT_COUNT" => $result_count,
				));
			}
			else
			{
				$bUpdate = $result_count != $arQueryStat["RESULT_COUNT"];

				$suggest_save_days = COption::GetOptionInt("search", "suggest_save_days");
				if($suggest_save_days <= 0)
					$suggest_save_days = 360;

				if($arQueryStat["DAYS"] <= 0)
				{
					$rate = $arQueryStat["RATE"];
					$bUpdate = $bUpdate || false;
				}
				elseif($arQueryStat["DAYS"] >= $suggest_save_days)
				{
					$rate = 1.0;
					$bUpdate = $bUpdate || true;
				}
				else
				{
					$rate = doubleval($arQueryStat["RATE"]) + ($suggest_save_days - $arQueryStat["DAYS"]) / $suggest_save_days;
					$bUpdate = $bUpdate || true;
				}

				if($bUpdate)
				{
					$DB->Query("
						UPDATE
							b_search_suggest
						SET
							RESULT_COUNT = ".$result_count.",
							RATE = ".$rate.",
							TIMESTAMP_X = ".$DB->CurrentTimeFunction()."
						WHERE
							ID = ".$arQueryStat["ID"]."
					");
				}
			}

			while($arQueryStat = $rsQueryStat->Fetch())
			{
				$DB->Query("DELETE FROM b_search_suggest WHERE ID = ".$arQueryStat["ID"]);
			}
		}
	}

	public function GetList($nTopCount, $site_id = null)
	{
		$DB = CDatabase::GetModuleConnection('search');
		if (!isset($site_id))
			$site_id = SITE_ID;

		if(strlen($this->_phrase))
		{
			$nTopCount = intval($nTopCount);
			if($nTopCount <= 0)
				$nTopCount = 10;

			$phrase = $DB->ForSQL($this->_phrase);
			$site_id = $DB->ForSQL($site_id);

			if(strlen($this->_filter_md5))
			{
				$filter_md5 = $DB->ForSQL($this->_filter_md5, 32);
				return $DB->Query($DB->TopSql("
					SELECT PHRASE, RESULT_COUNT CNT, RATE
					FROM b_search_suggest
					WHERE SITE_ID = '".$site_id."'
					AND FILTER_MD5 = '".$filter_md5."'
					AND PHRASE LIKE '".$phrase."%'
					ORDER BY RATE DESC, PHRASE ASC
				", $nTopCount));
			}
			else
			{
				return $DB->Query($DB->TopSql("
					SELECT PHRASE, max(RESULT_COUNT) CNT, max(RATE) RATE
					FROM b_search_suggest
					WHERE SITE_ID = '".$site_id."'
					AND PHRASE LIKE '".$phrase."%'
					GROUP BY PHRASE
					ORDER BY RATE DESC, PHRASE ASC
				", $nTopCount));
			}
		}
		else
		{

			return false;
		}
	}

	public static function CleanUpAgent()
	{
		$DB = CDatabase::GetModuleConnection('search');
		$cleanup_days = COption::GetOptionInt("search", "suggest_save_days");
		if($cleanup_days > 0)
		{
			$arDate = localtime(time());
			$date = mktime(0, 0, 0, $arDate[4]+1, $arDate[3]-$cleanup_days, 1900+$arDate[5]);
			$DB->Query("DELETE FROM b_search_suggest WHERE TIMESTAMP_X <= ".$DB->CharToDateFunction(ConvertTimeStamp($date, "FULL")));
		}
		return "CSearchSuggest::CleanUpAgent();";
	}
}
?>