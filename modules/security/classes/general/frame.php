<?
IncludeModuleLangFile(__FILE__);

class CSecurityFrame
{
	public static function SetHeader()
	{
		if((!defined("BX_SECURITY_SKIP_FRAMECHECK") || BX_SECURITY_SKIP_FRAMECHECK!==true) && !CSecurityFrameMask::Check(SITE_ID, $_SERVER["REQUEST_URI"]))
		{
			header("X-Frame-Options: SAMEORIGIN");
		}
	}

	public static function IsActive()
	{
		$bActive = false;
		foreach(GetModuleEvents("main", "OnPageStart", true) as $event)
		{
			if(
				$event["TO_MODULE_ID"] == "security"
				&& $event["TO_CLASS"] == "CSecurityFrame"
			)
			{
				$bActive = true;
				break;
			}
		}
		return $bActive;
	}

	public static function SetActive($bActive = false)
	{
		if($bActive)
		{
			if(!CSecurityFrame::IsActive())
			{
				RegisterModuleDependences("main", "OnPageStart", "security", "CSecurityFrame", "SetHeader", "0");
			}
		}
		else
		{
			if(CSecurityFrame::IsActive())
			{
				UnRegisterModuleDependences("main", "OnPageStart", "security", "CSecurityFrame", "SetHeader");
			}
		}
	}
}

class CSecurityFrameMask
{
	public static function Update($arMasks)
	{
		global $DB, $CACHE_MANAGER;

		if(is_array($arMasks))
		{
			$res = $DB->Query("DELETE FROM b_sec_frame_mask", false, "File: ".__FILE__."<br>Line: ".__LINE__);
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
							"FRAME_MASK" => $mask,
							"LIKE_MASK" => str_replace($arLikeSearch, $arLikeReplace, $mask),
							"PREG_MASK" => str_replace($arPregSearch, $arPregReplace, $mask),
						);
						if($site_id)
							$arMask["SITE_ID"] = $site_id;

						$DB->Add("b_sec_frame_mask", $arMask);
						$i += 10;
						$added[$mask] = true;
					}
				}

				if(CACHED_b_sec_frame_mask !== false)
					$CACHE_MANAGER->CleanDir("b_sec_frame_mask");

			}
		}

		return true;
	}

	public static function GetList()
	{
		global $DB;
		$res = $DB->Query("SELECT SITE_ID,FRAME_MASK from b_sec_frame_mask ORDER BY SORT");
		return $res;
	}

	public static function Check($siteId, $uri)
	{
		global $DB, $CACHE_MANAGER;
		$bFound = false;

		if(CACHED_b_sec_frame_mask !== false)
		{
			$cache_id = "b_sec_frame_mask";
			if($CACHE_MANAGER->Read(CACHED_b_sec_frame_mask, $cache_id, "b_sec_frame_mask"))
			{
				$arMasks = $CACHE_MANAGER->Get($cache_id);
			}
			else
			{
				$arMasks = array();

				$rs = $DB->Query("SELECT * FROM b_sec_frame_mask ORDER BY SORT");
				while($ar = $rs->Fetch())
				{
					$site_id = $ar["SITE_ID"]? $ar["SITE_ID"]: "-";
					$arMasks[$site_id][$ar["SORT"]] = $ar["PREG_MASK"];
				}

				$CACHE_MANAGER->Set($cache_id, $arMasks);
			}

			if(is_array($arMasks["-"]))
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

			if(
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
					b_sec_frame_mask m
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

?>