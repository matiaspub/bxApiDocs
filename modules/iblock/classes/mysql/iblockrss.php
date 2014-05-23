<?
class CIBlockRSS extends CAllIBlockRSS
{
	function GetCache($cacheKey)
	{
		global $DB;

		$db_res = $DB->Query(
			"SELECT CACHE, IF(CACHE_DATE>NOW(), 'Y', 'N') as VALID ".
			"FROM b_iblock_cache ".
			"WHERE CACHE_KEY = '".$DB->ForSql($cacheKey, 0)."' ");
		return $db_res->Fetch();
	}

	function Add($IBLOCK_ID, $NODE, $NODE_VALUE)
	{
		global $DB;
		$IBLOCK_ID = IntVal($IBLOCK_ID);
		$DB->Query(
			"INSERT INTO b_iblock_rss (IBLOCK_ID, NODE, NODE_VALUE) ".
			"VALUES(".$IBLOCK_ID.", '".$DB->ForSql($NODE, 50)."', '".$DB->ForSql($NODE_VALUE, 255)."')");
	}

	function UpdateCache($cacheKey, $CACHE, $HOURS_CACHE, $bCACHED)
	{
		global $DB;

		if(is_array($HOURS_CACHE) && array_key_exists("minutes", $HOURS_CACHE))
			$TTL = intval($HOURS_CACHE["minutes"]) * 60;
		else
			$TTL = intval($HOURS_CACHE) * 3600;

		if ($bCACHED)
		{
			$db_res = $DB->Query(
				"UPDATE b_iblock_cache SET ".
				"	CACHE = '".$DB->ForSql($CACHE, 0)."', ".
				"	CACHE_DATE = date_add(NOW(), interval ".IntVal($TTL)." second) ".
				"WHERE CACHE_KEY = '".$DB->ForSql($cacheKey, 0)."' ");
		}
		else
		{
			$db_res = $DB->Query(
				"INSERT INTO b_iblock_cache (CACHE_KEY, CACHE, CACHE_DATE) ".
				"VALUES('".$DB->ForSql($cacheKey, 0)."', '".$DB->ForSql($CACHE, 0)."', date_add(NOW(), interval ".IntVal($TTL)." second)) ");
		}
		$db_res = $DB->Query("DELETE from b_iblock_cache WHERE CACHE_DATE < NOW()");
	}

	function GetRSSText($arIBLOCK, $LIMIT_NUM = false, $LIMIT_DAY = false, $yandex = false)
	{
		global $DB;

		$strRes = "";

		$serverName = "";

		if (isset($arIBLOCK["SERVER_NAME"]) && strlen($arIBLOCK["SERVER_NAME"]) > 0)
			$serverName = $arIBLOCK["SERVER_NAME"];

		if (strlen($serverName) <=0 && !isset($arIBLOCK["SERVER_NAME"]))
		{
			$dbSite = CSite::GetList(($b="sort"), ($o="asc"), array("LID" => $arIBLOCK["LID"]));
			if ($arSite = $dbSite->Fetch())
				$serverName = $arSite["SERVER_NAME"];
		}

		if (strlen($serverName) <=0)
		{
			if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME)>0)
				$serverName = SITE_SERVER_NAME;
			else
				$serverName = COption::GetOptionString("main", "server_name", "www.bitrixsoft.com");
		}

		$strRes .= "<channel>\n";
		$strRes .= "<title>".htmlspecialcharsbx($arIBLOCK["NAME"])."</title>\n";
		$strRes .= "<link>http://".$serverName."</link>\n";
		$strRes .= "<description>".htmlspecialcharsbx($arIBLOCK["DESCRIPTION"])."</description>\n";
		$strRes .= "<lastBuildDate>".date("r")."</lastBuildDate>\n";
		$strRes .= "<ttl>".$arIBLOCK["RSS_TTL"]."</ttl>\n";

		$db_img_arr = CFile::GetFileArray($arIBLOCK["PICTURE"]);
		if ($db_img_arr)
		{
			if(substr($db_img_arr["SRC"], 0, 1) == "/")
				$strImage = "http://".$serverName.$db_img_arr["SRC"];
			else
				$strImage = $db_img_arr["SRC"];

			$strRes .= "<image>\n";
			$strRes .= "<title>".htmlspecialcharsbx($arIBLOCK["NAME"])."</title>\n";
			$strRes .= "<url>".$strImage."</url>\n";
			$strRes .= "<link>http://".$serverName."</link>\n";
			$strRes .= "<width>".$db_img_arr["WIDTH"]."</width>\n";
			$strRes .= "<height>".$db_img_arr["HEIGHT"]."</height>\n";
			$strRes .= "</image>\n";
		}

		$arNodes = array();
		$db_res = $DB->Query("SELECT NODE, NODE_VALUE FROM b_iblock_rss WHERE IBLOCK_ID = ".IntVal($arIBLOCK["ID"]));
		while ($db_res_arr = $db_res->Fetch())
		{
			$arNodes[$db_res_arr["NODE"]] = $db_res_arr["NODE_VALUE"];
		}

		CTimeZone::Disable();

		$strSql =
			"SELECT DISTINCT BE.*, ".
			"	".$DB->DateToCharFunction("BE.TIMESTAMP_X")." as TIMESTAMP_X, ".
			"	".$DB->DateToCharFunction("BE.ACTIVE_FROM", "FULL")." as ACTIVE_FROM, ".
			"	".$DB->DateToCharFunction("BE.ACTIVE_TO", "FULL")." as ACTIVE_TO, ".
			"	L.DIR as LANG_DIR, B.DETAIL_PAGE_URL, B.LIST_PAGE_URL, B.LID, L.SERVER_NAME ".
			"FROM b_iblock_element BE, b_lang L, b_iblock B ".
			"	LEFT JOIN b_iblock_group IBG ON IBG.IBLOCK_ID=B.ID ".
			"WHERE BE.IBLOCK_ID = B.ID ".
			"	AND B.LID = L.LID ".
			"	AND IBG.GROUP_ID IN (2) ".
			"	AND BE.WF_STATUS_ID = 1 AND BE.WF_PARENT_ELEMENT_ID is null ".
			"	AND IBG.PERMISSION>='R' ".
			"	AND (IBG.PERMISSION='X' OR B.ACTIVE='Y') ".
			"	AND (BE.IBLOCK_ID = ".IntVal($arIBLOCK["ID"]).") ".
			"	AND ((BE.ACTIVE_TO >= ".$DB->GetNowFunction()." OR BE.ACTIVE_TO IS NULL) AND (BE.ACTIVE_FROM <= ".$DB->GetNowFunction()." OR BE.ACTIVE_FROM IS NULL)) ".
			"	AND (BE.ACTIVE = 'Y') ";
		if ($LIMIT_DAY!==false)
			$strSql .= " AND (BE.ACTIVE_FROM>=".$DB->CharToDateFunction($DB->ForSql(date($DB->DateFormatToPHP(CLang::GetDateFormat("FULL")), mktime(date("H"), date("i"), date("s"), date("m"), date("d")-IntVal($LIMIT_DAY), date("Y")))), "FULL")." OR BE.ACTIVE_FROM IS NULL) ";
		$strSql .= "ORDER BY BE.ACTIVE_FROM DESC, BE.SORT ASC ";

		$res = $DB->Query($strSql);

		CTimeZone::Enable();

		$items = new CIBlockResult($res->result);
		if ($LIMIT_NUM!==False && IntVal($LIMIT_NUM)>0)
			$items->NavStart($LIMIT_NUM);

		while ($arItem = $items->GetNext())
		{
			$props = CIBlockElement::GetProperty($arIBLOCK["ID"], $arItem["ID"], "sort", "asc", Array("ACTIVE"=>"Y", "NON_EMPTY"=>"Y"));
			$arProps = Array();
			while ($arProp = $props->Fetch())
			{
				if (strlen($arProp["CODE"])>0)
					$arProps[$arProp["CODE"]] = Array("NAME"=>htmlspecialcharsbx($arProp["NAME"]), "VALUE"=>htmlspecialcharsex($arProp["VALUE"]));
				else
					$arProps[$arProp["ID"]] = Array("NAME"=>htmlspecialcharsbx($arProp["NAME"]), "VALUE"=>htmlspecialcharsex($arProp["VALUE"]));
			}

			$arLinkProp = $arProps["DOC_LINK"];

			$strRes .= "<item>\n";
			if (strlen($arNodes["title"])>0)
			{
				$strRes .= "<title>".htmlspecialcharsbx(CIBlockRSS::ExtractProperties($arNodes["title"], $arProps, $arItem))."</title>\n";
			}
			else
			{
				$strRes .= "<title>".htmlspecialcharsbx($arItem["~NAME"])."</title>\n";
			}
			if (strlen($arNodes["link"])>0)
			{
				$strRes .= "<link>".CIBlockRSS::ExtractProperties($arNodes["link"], $arProps, $arItem)."</link>\n";
			}
			else
			{
				$strRes .= "<link>http://".$serverName.(($arLinkProp["VALUE"]) ? $arLinkProp["VALUE"] : $arItem["DETAIL_PAGE_URL"])."</link>\n";
			}
			if (strlen($arNodes["description"])>0)
			{
				$strRes .= "<description>".htmlspecialcharsbx(CIBlockRSS::ExtractProperties($arNodes["description"], $arProps, $arItem))."</description>\n";
			}
			else
			{
				$strRes .= "<description>".(($arItem["PREVIEW_TEXT"] || $yandex) ? htmlspecialcharsbx($arItem["PREVIEW_TEXT"]) : htmlspecialcharsbx($arItem["DETAIL_TEXT"]))."</description>\n";
			}
			if (strlen($arNodes["enclosure"])>0)
			{
				$strRes .= "<enclosure url=\"".htmlspecialcharsbx(CIBlockRSS::ExtractProperties($arNodes["enclosure"], $arProps, $arItem))."\" length=\"".htmlspecialcharsbx(CIBlockRSS::ExtractProperties($arNodes["enclosure_length"], $arProps, $arItem))."\" type=\"".htmlspecialcharsbx(CIBlockRSS::ExtractProperties($arNodes["enclosure_type"], $arProps, $arItem))."\"/>\n";
			}
			else
			{
				$db_img_arr = CFile::GetFileArray($arItem["PREVIEW_PICTURE"]);
				if ($db_img_arr)
				{
					if(substr($db_img_arr["SRC"], 0, 1) == "/")
						$strImage = "http://".$serverName.$db_img_arr["SRC"];
					else
						$strImage = $db_img_arr["SRC"];

					$strRes .= "<enclosure url=\"".$strImage."\" length=\"".$db_img_arr["FILE_SIZE"]."\" type=\"".$db_img_arr["CONTENT_TYPE"]."\" width=\"".$db_img_arr["WIDTH"]."\" height=\"".$db_img_arr["HEIGHT"]."\"/>\n";
				}
			}
			if (strlen($arNodes["category"])>0)
			{
				$strRes .= "<category>".htmlspecialcharsbx(CIBlockRSS::ExtractProperties($arNodes["category"], $arProps, $arItem))."</category>\n";
			}
			else
			{
				$strPath = "";
				$nav = CIBlockSection::GetNavChain($arIBLOCK["ID"], $arItem["IBLOCK_SECTION_ID"]);
				while($ar_nav = $nav->GetNext())
				{
					$strPath .= $ar_nav["NAME"]."/";
				}
				if (strlen($strPath)>0)
				{
					$strRes .= "<category>".htmlspecialcharsbx($strPath)."</category>\n";
				}
			}
			if ($yandex)
			{
				$strRes .= "<yandex:full-text>".htmlspecialcharsbx($arItem["DETAIL_TEXT"])."</yandex:full-text>\n";
			}
			if (strlen($arNodes["pubDate"])>0)
			{
				$strRes .= "<pubDate>".htmlspecialcharsbx(CIBlockRSS::ExtractProperties($arNodes["pubDate"], $arProps, $arItem))."</pubDate>\n";
			}
			else
			{
				if (strlen($arItem["ACTIVE_FROM"])>0)
				{
					$strRes .= "<pubDate>".date("r", MkDateTime($DB->FormatDate($arItem["ACTIVE_FROM"], Clang::GetDateFormat("FULL"), "DD.MM.YYYY H:I:S"), "d.m.Y H:i:s"))."</pubDate>\n";
				}
				else
				{
					$strRes .= "<pubDate>".date("r")."</pubDate>\n";
				}
			}
			$strRes .= "</item>\n";
		}
		$strRes .= "</channel>\n";
		return $strRes;
	}

	// Agent
	function PreGenerateRSS($IBLOCK_ID, $yandex = true)
	{
		global $DB;

		$strSql =
			"SELECT DISTINCT B.*, C.CHARSET, S.SERVER_NAME, ".$DB->DateToCharFunction("B.TIMESTAMP_X")." as TIMESTAMP_X ".
			"FROM b_iblock B LEFT JOIN b_iblock_group IBG ON IBG.IBLOCK_ID=B.ID ".
			"	LEFT JOIN b_lang S ON S.LID=B.LID ".
			"	LEFT JOIN b_culture C ON C.ID=S.CULTURE_ID ".
			"WHERE B.ID = ".IntVal($IBLOCK_ID).
			"	AND IBG.GROUP_ID IN (2) ".
			"	AND IBG.PERMISSION>='R'".
			"	AND (IBG.PERMISSION='X' OR B.ACTIVE='Y')";
		$dbr = $DB->Query($strSql);
		$bAccessable = False;
		if (($arIBlock = $dbr->GetNext()) && ($arIBlock["RSS_FILE_ACTIVE"]=="Y" && !$yandex || $arIBlock["RSS_YANDEX_ACTIVE"]=="Y" && $yandex))
			$bAccessable = True;

		if (!$bAccessable) return "";

		$strRes = "";
		$strRes .= "<"."?xml version=\"1.0\" encoding=\"".$arIBlock["CHARSET"]."\"?".">\n";
		$strRes .= "<rss version=\"2.0\"";
//		$strRes .= "<rss version=\"2.0\" xmlns=\"http://backend.userland.com/rss2\"";
		if ($yandex) $strRes .= " xmlns:yandex=\"http://news.yandex.ru\"";
		$strRes .= ">\n";

		$limit_num = false;
		$limit_day = 2;
		if (!$yandex)
		{
			$limit_num = false;
			if (strlen($arIBlock["RSS_FILE_LIMIT"])>0 && IntVal($arIBlock["RSS_FILE_LIMIT"])>0)
				$limit_num = IntVal($arIBlock["RSS_FILE_LIMIT"]);

			$limit_day = false;
			if (strlen($arIBlock["RSS_FILE_DAYS"])>0 && IntVal($arIBlock["RSS_FILE_DAYS"])>0)
				$limit_day = IntVal($arIBlock["RSS_FILE_DAYS"]);
		}
		$strRes .= CIBlockRSS::GetRSSText($arIBlock, $limit_num, $limit_day, $yandex);

		$strRes .= "</rss>\n";

		$rss_file = $_SERVER["DOCUMENT_ROOT"].COption::GetOptionString("iblock", "path2rss", "/upload/");
		if ($yandex)
			$rss_file .= "yandex_rss_".IntVal($arIBlock["ID"]).".xml";
		else
			$rss_file .= "iblock_rss_".IntVal($arIBlock["ID"]).".xml";
		$fp = fopen($rss_file, "w");
		fwrite($fp, $strRes);
		fclose($fp);

		global $pPERIOD;
		$pPERIOD = IntVal($arIBlock["RSS_TTL"])*60*60;
		return "CIBlockRSS::PreGenerateRSS(".$IBLOCK_ID.", ".($yandex?"true":"false").");";
	}

}
?>