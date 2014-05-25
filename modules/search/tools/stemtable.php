<?
IncludeModuleLangFile(__FILE__);

class CSearchStemTable extends CSearchFullText
{
	static public function connect($connectionIndex, $indexName = "")
	{
	}

	static public function truncate()
	{
		$DB = CDatabase::GetModuleConnection('search');
		if (BX_SEARCH_VERSION > 1)
		{
			$DB->Query("TRUNCATE TABLE b_search_stem", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$DB->Query($s="TRUNCATE TABLE b_search_content_text", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		$DB->Query($s="TRUNCATE TABLE b_search_content_stem", false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	static public function deleteById($ID)
	{
		$DB = CDatabase::GetModuleConnection('search');
		if (BX_SEARCH_VERSION > 1)
		{
			$DB->Query("DELETE FROM b_search_content_text WHERE SEARCH_CONTENT_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		$DB->Query($s="DELETE FROM b_search_content_stem WHERE SEARCH_CONTENT_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	static public function replace($ID, $arFields)
	{
		$DB = CDatabase::GetModuleConnection('search');

		if(array_key_exists("SEARCHABLE_CONTENT", $arFields))
		{
			if(BX_SEARCH_VERSION > 1)
			{
				$text_md5 = md5($arFields["SEARCHABLE_CONTENT"]);
				$rsText = $DB->Query("SELECT SEARCH_CONTENT_MD5 FROM b_search_content_text WHERE SEARCH_CONTENT_ID = ".$ID);
				$arText = $rsText->Fetch();
				if(!$arText || $arText["SEARCH_CONTENT_MD5"] !== $text_md5)
				{
					CSearch::CleanFreqCache($ID);
					$DB->Query("DELETE FROM b_search_content_stem WHERE SEARCH_CONTENT_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					if (COption::GetOptionString("search", "agent_stemming") === "Y")
						CSearchStemTable::DelayStemIndex($ID);
					else
						CSearch::StemIndex($arFields["SITE_ID"], $ID, $arFields["SEARCHABLE_CONTENT"]);
					$DB->Query("DELETE FROM b_search_content_text WHERE SEARCH_CONTENT_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					$arText = array(
						"ID" => 1,
						"SEARCH_CONTENT_ID" => $ID,
						"SEARCH_CONTENT_MD5" => $text_md5,
						"SEARCHABLE_CONTENT" => $arFields["SEARCHABLE_CONTENT"]
					);
					$DB->Add("b_search_content_text", $arText, Array("SEARCHABLE_CONTENT"));
				}
			}
			else
			{
				CSearch::CleanFreqCache($ID);
				$DB->Query("DELETE FROM b_search_content_stem WHERE SEARCH_CONTENT_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if (COption::GetOptionString("search", "agent_stemming") === "Y")
					CSearchStemTable::DelayStemIndex($ID);
				else
					CSearch::StemIndex($arFields["SITE_ID"], $ID, $arFields["SEARCHABLE_CONTENT"]);
			}
		}
	}

	public static function DelayStemIndex($ID)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$ID = intval($ID);

		$DB->Query("
			delete from b_search_content_stem
			where SEARCH_CONTENT_ID = -$ID
		");
		$DB->Query("
			insert into b_search_content_stem
			(SEARCH_CONTENT_ID, LANGUAGE_ID, STEM, TF".(BX_SEARCH_VERSION > 1? ",PS": "").")
			values
			(-$ID, 'en', 0, 0".(BX_SEARCH_VERSION > 1? ",0": "").")
		");

		CSearchStemTable::_addAgent();
	}

	private static function _addAgent()
	{
		global $APPLICATION;

		static $bAgentAdded = false;
		if(!$bAgentAdded)
		{
			$bAgentAdded = true;
			$rsAgents = CAgent::GetList(array("ID"=>"DESC"), array("NAME" => "CSearchStemTable::DelayedStemIndex(%"));
			if(!$rsAgents->Fetch())
			{
				$res = CAgent::AddAgent(
					"CSearchStemTable::DelayedStemIndex();",
					"search", //module
					"N", //period
					1 //interval
				);

				if(!$res)
					$APPLICATION->ResetException();
			}
		}
	}

	public static function DelayedStemIndex()
	{
		$DB = CDatabase::GetModuleConnection('search');
		$etime = time() + intval(COption::GetOptionString("search", "agent_duration"));
		do {
			$stemQueue = $DB->Query($DB->TopSql("
				SELECT SEARCH_CONTENT_ID ID
				FROM b_search_content_stem
				WHERE SEARCH_CONTENT_ID < 0
			", 1));
			if($stemTask = $stemQueue->Fetch())
			{
				$ID = -$stemTask["ID"];

				$sites = array();
				$rsSite = $DB->Query("
					SELECT SITE_ID, URL
					FROM b_search_content_site
					WHERE SEARCH_CONTENT_ID = ".$ID."
				");
				while($arSite = $rsSite->Fetch())
					$sites[$arSite["SITE_ID"]] = $arSite["URL"];

				if(BX_SEARCH_VERSION > 1)
					$sql = "SELECT SEARCHABLE_CONTENT from b_search_content_text WHERE SEARCH_CONTENT_ID = $ID";
				else
					$sql = "SELECT SEARCHABLE_CONTENT from b_search_content WHERE ID = $ID";
				$rsContent = $DB->Query($sql);
				if ($arContent = $rsContent->Fetch())
				{
					$DB->Query("DELETE FROM b_search_content_stem WHERE SEARCH_CONTENT_ID = ".$ID);
					CSearch::StemIndex($sites, $ID, $arContent["SEARCHABLE_CONTENT"]);
				}
				$DB->Query("DELETE FROM b_search_content_stem WHERE SEARCH_CONTENT_ID = ".$stemTask["ID"]);
			}
			else
			{
				//Cancel the agent
				return "";
			}

		} while ($etime >= time());
		return "CSearchStemTable::DelayedStemIndex();";
	}
}
?>