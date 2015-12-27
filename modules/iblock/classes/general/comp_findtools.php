<?
class CIBlockFindTools
{
	public static function GetElementID($element_id, $element_code, $section_id, $section_code, $arFilter)
	{
		$element_id = (int)$element_id;
		$element_code = (string)$element_code;
		if ($element_id > 0)
		{
			return $element_id;
		}
		elseif ($element_code != '')
		{
			if (!is_array($arFilter))
				$arFilter = array();
			$arFilter['=CODE'] = $element_code;

			$section_id = (int)$section_id;
			$section_code = (string)$section_code;
			if ($section_id > 0)
				$arFilter['SECTION_ID'] = $section_id;
			elseif ($section_code != '')
				$arFilter["SECTION_CODE"] = $section_code;

			$rsElement = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID"));
			if ($arElement = $rsElement->Fetch())
				return (int)$arElement["ID"];
		}
		return 0;
	}

	public static function GetSectionID($section_id, $section_code, $arFilter)
	{
		$section_id = (int)$section_id;
		$section_code = (string)$section_code;
		if ($section_id > 0)
		{
			return $section_id;
		}
		elseif ($section_code != '')
		{
			if (!is_array($arFilter))
				$arFilter = array();
			$arFilter['=CODE'] = $section_code;

			$rsSection = CIBlockSection::GetList(array(), $arFilter, false, array("ID"));
			if ($arSection = $rsSection->Fetch())
				return (int)$arSection["ID"];
		}
		return 0;
	}

	public static function GetSectionIDByCodePath($iblock_id, $section_code_path)
	{
		$arVariables = array(
			"SECTION_CODE_PATH" => $section_code_path,
		);
		return (self::checkSection($iblock_id, $arVariables) ? $arVariables["SECTION_ID"] : 0);
	}

	public static function resolveComponentEngine(CComponentEngine $engine, $pageCandidates, &$arVariables)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $CACHE_MANAGER;
		static $aSearch = array("&lt;", "&gt;", "&quot;", "&#039;");
		static $aReplace = array("<", ">", "\"", "'");

		$component = $engine->GetComponent();
		if ($component)
			$iblock_id = intval($component->arParams["IBLOCK_ID"]);
		else
			$iblock_id = 0;

		//To fix GetPagePath security hack for SMART_FILTER_PATH
		foreach ($pageCandidates as $pageID => $arVariablesTmp)
		{
			foreach ($arVariablesTmp as $variableName => $variableValue)
			{
				if ($variableName === "SMART_FILTER_PATH")
					$pageCandidates[$pageID][$variableName] = str_replace($aSearch, $aReplace, $variableValue);
			}
		}

		$requestURL = $APPLICATION->GetCurPage(true);

		$cacheId = $requestURL.implode("|", array_keys($pageCandidates))."|".SITE_ID;
		$cache = new CPHPCache;
		if ($cache->startDataCache(3600, $cacheId, "iblock_find"))
		{
			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->StartTagCache("iblock_find");
				CIBlock::registerWithTagCache($iblock_id);
			}

			foreach ($pageCandidates as $pageID => $arVariablesTmp)
			{
				if (
					$arVariablesTmp["SECTION_CODE_PATH"] != ""
					&& (isset($arVariablesTmp["ELEMENT_ID"]) || isset($arVariablesTmp["ELEMENT_CODE"]))
				)
				{
					if (CIBlockFindTools::checkElement($iblock_id, $arVariablesTmp))
					{
						$arVariables = $arVariablesTmp;
						if (defined("BX_COMP_MANAGED_CACHE"))
							$CACHE_MANAGER->EndTagCache();
						$cache->endDataCache(array($pageID, $arVariablesTmp));
						return $pageID;
					}
				}
			}

			foreach ($pageCandidates as $pageID => $arVariablesTmp)
			{
				if (
					$arVariablesTmp["SECTION_CODE_PATH"] != ""
					&& (!isset($arVariablesTmp["ELEMENT_ID"]) && !isset($arVariablesTmp["ELEMENT_CODE"]))
				)
				{
					if (CIBlockFindTools::checkSection($iblock_id, $arVariablesTmp))
					{
						$arVariables = $arVariablesTmp;
						if (defined("BX_COMP_MANAGED_CACHE"))
							$CACHE_MANAGER->EndTagCache();
						$cache->endDataCache(array($pageID, $arVariablesTmp));
						return $pageID;
					}
				}
			}

			if (defined("BX_COMP_MANAGED_CACHE"))
				$CACHE_MANAGER->AbortTagCache();
			$cache->abortDataCache();
		}
		else
		{
			$vars = $cache->getVars();
			$pageID = $vars[0];
			$arVariables = $vars[1];
			return $pageID;
		}

		reset($pageCandidates);
		list($pageID, $arVariables) = each($pageCandidates);

		return $pageID;
	}

	public static function checkElement($iblock_id, &$arVariables)
	{
		global $DB;

		$strFrom = "
			b_iblock_element BE
		";

		$strWhere = "
			".($arVariables["ELEMENT_ID"] != ""? "AND BE.ID = ".intval($arVariables["ELEMENT_ID"]): "")."
			".($arVariables["ELEMENT_CODE"] != ""? "AND BE.CODE = '".$DB->ForSql($arVariables["ELEMENT_CODE"])."'": "")."
		";

		if ($arVariables["SECTION_CODE_PATH"] != "")
		{
			//The path may be incomplete so we join part of the section tree BS and BSP
			$strFrom .= "
				INNER JOIN b_iblock_section_element BSE ON BSE.IBLOCK_ELEMENT_ID = BE.ID AND BSE.ADDITIONAL_PROPERTY_ID IS NULL
				INNER JOIN b_iblock_section BS ON BS.ID = BSE.IBLOCK_SECTION_ID
				INNER JOIN b_iblock_section BSP ON BS.IBLOCK_ID = BSP.IBLOCK_ID AND BS.LEFT_MARGIN >= BSP.LEFT_MARGIN AND BS.RIGHT_MARGIN <= BSP.RIGHT_MARGIN
			";
			$joinField = "BSP.ID";

			$sectionPath = explode("/", $arVariables["SECTION_CODE_PATH"]);
			foreach (array_reverse($sectionPath) as $i => $SECTION_CODE)
			{
				$strFrom .= "
					INNER JOIN b_iblock_section BS".$i." ON BS".$i.".ID = ".$joinField."
				";
				$joinField = "BS".$i.".IBLOCK_SECTION_ID";
				$strWhere .= "
					AND BS".$i.".CODE = '".$DB->ForSql($SECTION_CODE)."'
				";
			}
		}

		$strSql = "
			select BE.ID
			from ".$strFrom."
			WHERE BE.IBLOCK_ID = ".$iblock_id."
			".$strWhere."
		";
		$rs = $DB->Query($strSql);
		if ($rs->Fetch())
		{
			if (isset($sectionPath))
				$arVariables["SECTION_CODE"] = $sectionPath[count($sectionPath)-1];
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function checkSection($iblock_id, &$arVariables)
	{
		global $DB;

		$sectionPath = explode("/", $arVariables["SECTION_CODE_PATH"]);

		$strFrom = "";
		$joinField = "";
		$strWhere = "";
		$strRoot = "";
		foreach (array_reverse($sectionPath) as $i => $SECTION_CODE)
		{
			if ($i == 0)
			{
				$strFrom .= "
					b_iblock_section BS
				";
				$joinField .= "BS.IBLOCK_SECTION_ID";
				$strWhere .= "
					AND BS.CODE = '".$DB->ForSql($SECTION_CODE)."'
				";
				$strRoot = "AND BS.IBLOCK_SECTION_ID IS NULL";
			}
			else
			{
				$strFrom .= "
					INNER JOIN b_iblock_section BS".$i." ON BS".$i.".ID = ".$joinField."
				";
				$joinField = "BS".$i.".IBLOCK_SECTION_ID";
				$strWhere .= "
					AND BS".$i.".CODE = '".$DB->ForSql($SECTION_CODE)."'
				";
				$strRoot = "AND BS".$i.".IBLOCK_SECTION_ID IS NULL";
			}
		}

		$strSql = "
			select BS.ID
			from ".$strFrom."
			WHERE BS.IBLOCK_ID = ".$iblock_id."
			".$strWhere."
			".$strRoot."
		";
		$rs = $DB->Query($strSql);
		if ($ar = $rs->Fetch())
		{
			$arVariables["SECTION_ID"] = $ar["ID"];
			$arVariables["SECTION_CODE"] = $sectionPath[count($sectionPath)-1];
			return true;
		}
		else
		{
			return false;
		}
	}
}