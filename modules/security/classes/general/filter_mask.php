<?php
/**
* Bitrix Framework
* @package bitrix
* @subpackage security
* @copyright 2001-2013 Bitrix
*/

class CSecurityFilterMask
{
	public static function Update($arMasks)
	{
		global $DB, $CACHE_MANAGER;

		if(is_array($arMasks))
		{
			$res = $DB->Query("DELETE FROM b_sec_filter_mask", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($res)
			{
				$arLikeSearch = array("?", "*", ".");
				$arLikeReplace = array("_",  "%", "\\.");
				$arPregSearch = array("\\", ".",  "?", "*",   "'");
				$arPregReplace = array("/",  "\.", ".", ".*?", "\'");

				$added = array();
				$i = 10;
				foreach($arMasks as $arMask)
				{
					$site_id = trim($arMask["SITE_ID"]);
					if($site_id == "NOT_REF")
						$site_id = "";

					$mask = trim($arMask["MASK"]);
					if($mask && !array_key_exists($mask, $added))
					{
						$arMask = array(
							"SORT" => $i,
							"FILTER_MASK" => $mask,
							"LIKE_MASK" => str_replace($arLikeSearch, $arLikeReplace, $mask),
							"PREG_MASK" => str_replace($arPregSearch, $arPregReplace, $mask),
						);
						if($site_id)
							$arMask["SITE_ID"] = $site_id;

						$DB->Add("b_sec_filter_mask", $arMask);
						$i += 10;
						$added[$mask] = true;
					}
				}

				if(CACHED_b_sec_filter_mask !== false)
					$CACHE_MANAGER->CleanDir("b_sec_filter_mask");

			}
		}

		return true;
	}

	public static function GetList()
	{
		global $DB;
		$res = $DB->Query("SELECT SITE_ID,FILTER_MASK from b_sec_filter_mask ORDER BY SORT");
		return $res;
	}

	public static function Check($siteId, $uri)
	{
		global $DB, $CACHE_MANAGER;
		$bFound = false;

		//Hardcoded white list (to be continue)
		if (preg_match("#^/bitrix/tools/mail_entry.php#", $uri))
		{
			return true;
		}

		if(CACHED_b_sec_filter_mask !== false)
		{
			$cache_id = "b_sec_filter_mask";
			if($CACHE_MANAGER->Read(CACHED_b_sec_filter_mask, $cache_id, "b_sec_filter_mask"))
			{
				$arMasks = $CACHE_MANAGER->Get($cache_id);
			}
			else
			{
				$arMasks = array();

				$rs = $DB->Query("SELECT * FROM b_sec_filter_mask ORDER BY SORT");
				while($ar = $rs->Fetch())
				{
					$site_id = $ar["SITE_ID"]? $ar["SITE_ID"]: "-";
					$arMasks[$site_id][$ar["SORT"]] = $ar["PREG_MASK"];
				}

				$CACHE_MANAGER->Set($cache_id, $arMasks);
			}

			if(isset($arMasks["-"]) && is_array($arMasks["-"]))
			{
				foreach($arMasks["-"] as $mask)
				{
					if(preg_match("#^".$mask."$#", $uri))
					{
						$bFound = true;
						break;
					}
				}
			}

			if (
				!$bFound
				&& $siteId
				&& isset($arMasks[$siteId])
			)
			{
				foreach($arMasks[$siteId] as $mask)
				{
					if(preg_match("#^".$mask."$#", $uri))
					{
						$bFound = true;
						break;
					}
				}
			}

		}
		else
		{
			$sql = "
				SELECT m.*
				FROM
					b_sec_filter_mask m
				WHERE
					(m.SITE_ID IS NULL AND '".$DB->ForSQL($uri)."' like m.LIKE_MASK)
			";
			if ($siteId)
			{
				$sql .= "
				OR (m.SITE_ID = '".$DB->ForSQL($siteId)."' AND '".$DB->ForSQL($uri)."' like m.LIKE_MASK)
				";
			}

			$rs = $DB->Query($sql);
			if($rs->Fetch())
				$bFound = true;
		}

		return $bFound;
	}
}
