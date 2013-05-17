<?
$GLOBALS["UF_GALLERY_SIZE"] = array(); 
if (!is_array($GLOBALS["PHOTOGALLERY_VARS"]))
{
	$GLOBALS["PHOTOGALLERY_VARS"] = array(
		"arSections" => array(), 
		"arGalleriesID" => array(), 
		"arGalleries" => array(), 
		"arIBlock" => array());
}

class CPhotogalleryElement
{
//	static $arSections = array();
//	static $arGalleries = array(); 
//	static $arIBlock = array(); 
	
	public static function CheckElement($ID, &$arElement, &$arSection, &$arGallery)
	{
		$ID = doubleval($ID); 
		
		if ($ID <= 0)
			return false; 
		$arSelect = array(
			"ID",
			"IBLOCK_ID",
			"IBLOCK_SECTION_ID");
		$db_res = CIBlockElement::GetList(array(), array("ID" => $ID), false, false, $arSelect); 
		if (!($db_res && $arElement = $db_res->Fetch()))
		{
			return false;
		}
		elseif (doubleval($arElement["IBLOCK_SECTION_ID"]) <= 0)
		{
			return false;
		}
		$tmp_db_res = CIBlockElement::GetProperty($arElement["IBLOCK_ID"], $ID, $by = "sort", $order = "asc", array("CODE" => "REAL_PICTURE")); 
		if ($tmp_db_res && $tmp_res = $tmp_db_res->Fetch())
		{
			$arElement["PROPERTY_REAL_PICTURE_VALUE"] = $tmp_res["VALUE"]; 
			$arElement["PROPERTY_REAL_PICTURE_VALUE_ID"] = $tmp_res["PROPERTY_VALUE_ID"]; 
		}
		
		if (!is_set($GLOBALS["PHOTOGALLERY_VARS"]["arIBlock"], $arElement["IBLOCK_ID"]))
		{
			$GLOBALS["PHOTOGALLERY_VARS"]["arIBlock"][$arElement["IBLOCK_ID"]] = false;
			$db_res = CUserTypeEntity::GetList(array($by=>$order), array("ENTITY_ID" => "IBLOCK_".$arElement["IBLOCK_ID"]."_SECTION", "FIELD_NAME" => "UF_GALLERY_SIZE"));
			if ($db_res && $res = $db_res->Fetch())
				$GLOBALS["PHOTOGALLERY_VARS"]["arIBlock"][$arElement["IBLOCK_ID"]] = true;
		}
		if ($GLOBALS["PHOTOGALLERY_VARS"]["arIBlock"][$arElement["IBLOCK_ID"]] === false)
		{
			return false; 
		}
		$arElement["FILE"] = CFile::GetFileArray($arElement["PROPERTY_REAL_PICTURE_VALUE"]);
		if ($arElement["FILE"])
			$arElement["FILE"]["FILE_SIZE"] = doubleval($arElement["FILE"]["FILE_SIZE"]); 
		else
			return false;

		if (empty($GLOBALS["PHOTOGALLERY_VARS"]["arSections"][$arElement["IBLOCK_SECTION_ID"]]))
		{
			$db_res = CIBlockSection::GetList(
				array(), 
				array("ID" => $arElement["IBLOCK_SECTION_ID"]), 
				false, 
				array("ID", "NAME", "CREATED_BY", "IBLOCK_SECTION_ID", "RIGHT_MARGIN", "LEFT_MARGIN"));
			$GLOBALS["PHOTOGALLERY_VARS"]["arSections"][$arElement["IBLOCK_SECTION_ID"]] = $db_res->Fetch();
		}
		$arSection = $GLOBALS["PHOTOGALLERY_VARS"]["arSections"][$arElement["IBLOCK_SECTION_ID"]]; 
		
		$iGalleryID = 0; 
		if (!empty($GLOBALS["PHOTOGALLERY_VARS"]["arGalleriesID"][$arSection["ID"]]))
		{
			$iGalleryID = $GLOBALS["PHOTOGALLERY_VARS"]["arGalleriesID"][$arSection["ID"]]; 
		}
		elseif (!empty($GLOBALS["PHOTOGALLERY_VARS"]["arGalleries"]))
		{
			foreach ($GLOBALS["PHOTOGALLERY_VARS"]["arGalleries"] as $id => $val)
			{
				if (doubleval($val["RIGHT_MARGIN"]) > doubleval($arSection["RIGHT_MARGIN"]) && 
					doubleval($val["LEFT_MARGIN"]) < doubleval($arSection["LEFT_MARGIN"]))
				{
					$iGalleryID = $GLOBALS["PHOTOGALLERY_VARS"]["arGalleriesID"][$arSection["ID"]] = $id; 
					break; 
				}
			}
		}
		if ($iGalleryID <= 0)
		{
			$arFilter = array(
				"IBLOCK_ID" => $arElement["IBLOCK_ID"], 
				"SECTION_ID" => 0); 
			if (doubleval($arSection["IBLOCK_SECTION_ID"]) > 0)
			{
				$arFilter += array(
					"!LEFT_MARGIN" => $arSection["LEFT_MARGIN"], 
					"!RIGHT_MARGIN" => $arSection["RIGHT_MARGIN"], 
					"!ID" => $arSection["ID"]); 
			}
			else
			{
				$arFilter["ID"] = $arSection["ID"]; 
			}
			
			$db_res = CIBlockSection::GetList(
				array(), 
				$arFilter, 
				false, 
				array("ID", "NAME", "CREATED_BY", "RIGHT_MARGIN", "LEFT_MARGIN", "UF_GALLERY_SIZE", "UF_GALLERY_RECALC"));
			if ($db_res && $res = $db_res->Fetch())
			{
				$GLOBALS["PHOTOGALLERY_VARS"]["arGalleries"][$res["ID"]] = $res; 
				$iGalleryID = $GLOBALS["PHOTOGALLERY_VARS"]["arGalleriesID"][$arSection["ID"]] = intVal($res["ID"]); 
			}
		}
		$arGallery = array(); 
		$iGalleryID = intVal($iGalleryID); 
		if ($iGalleryID > 0)
		{
			if (empty($GLOBALS["PHOTOGALLERY_VARS"]["arGalleries"][$iGalleryID]))
			{
				$db_res = CIBlockSection::GetList(array(), array("ID" => $iGalleryID), false, 
					array("ID", "NAME", "CREATED_BY", "RIGHT_MARGIN", "LEFT_MARGIN", "UF_GALLERY_SIZE", "UF_GALLERY_RECALC"));
				$GLOBALS["PHOTOGALLERY_VARS"]["arGalleries"][$iGalleryID] = $db_res->Fetch(); 
			}
			$arGallery = $GLOBALS["PHOTOGALLERY_VARS"]["arGalleries"][$iGalleryID]; 
		}
		return true; 
	}
	
	public static function OnBeforeIBlockElementDelete($ID)
	{
		$ID = doubleval($ID);
		if (CPhotogalleryElement::CheckElement($ID, $arElement, $arSection, $arGallery))
		{
			$arGallery["UF_GALLERY_SIZE"] = (doubleval($arGallery["UF_GALLERY_SIZE"]) - $arElement["FILE"]["FILE_SIZE"]); 
			$GLOBALS["UF_GALLERY_SIZE"] = ($arGallery["UF_GALLERY_SIZE"] <= 0 ? 0 : $arGallery["UF_GALLERY_SIZE"]); 
			$GLOBALS["PHOTOGALLERY_VARS"]["arGalleries"][$arGallery["ID"]]["UF_GALLERY_SIZE"] = $GLOBALS["UF_GALLERY_SIZE"]; 
			$arFields = array(
				"IBLOCK_ID" => $arElement["IBLOCK_ID"], 
				"UF_GALLERY_SIZE" => $GLOBALS["UF_GALLERY_SIZE"]);
			$bs = new CIBlockSection;
			$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("IBLOCK_".$arElement["IBLOCK_ID"]."_SECTION", $arFields);
			$res = $bs->Update($arGallery["ID"], $arFields, false, false);
		}
		return true;
	}
	
	public static function OnRecalcGalleries($ID, $INDEX)
	{
		$ID = doubleval($ID); 
		
		if (CPhotogalleryElement::CheckElement($ID, $arElement, $arSection, $arGallery))
		{
			$arFields = array(
				"IBLOCK_ID" => $arElement["IBLOCK_ID"], 
				"UF_GALLERY_SIZE" => $arGallery["UF_GALLERY_SIZE"]);
			if (doubleval($arGallery["UF_GALLERY_SIZE"]) > 0 && $arGallery["UF_GALLERY_RECALC"] != $INDEX)
			{
				$arGallery["UF_GALLERY_SIZE"] = 0; 
				$GLOBALS["UF_GALLERY_RECALC"] = $arFields["UF_GALLERY_RECALC"] = $INDEX; 
			}
			$arFields["UF_GALLERY_SIZE"] = $GLOBALS["UF_GALLERY_SIZE"] = $GLOBALS["PHOTOGALLERY_VARS"]["arGalleries"][$arGallery["ID"]]["UF_GALLERY_SIZE"] =(doubleval($arGallery["UF_GALLERY_SIZE"]) + $arElement["FILE"]["FILE_SIZE"]); 
			$bs = new CIBlockSection;
			$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("IBLOCK_".$arElement["IBLOCK_ID"]."_SECTION", $arFields);
			$res = $bs->Update($arGallery["ID"], $arFields, false, false);
		}
		return true;
	}
	
	public static function OnAfterRecalcGalleries($IBLOCK_ID, $INDEX)
	{
		if ($IBLOCK_ID <= 0)
			return false; 
		$arFilters = array(
			array("IBLOCK_ID" => $IBLOCK_ID, "SECTION_ID" => 0, ">UF_GALLERY_SIZE" => 0, "!UF_GALLERY_RECALC" => $INDEX.""), 
			array("IBLOCK_ID" => $IBLOCK_ID, "SECTION_ID" => 0, ">UF_GALLERY_SIZE" => 0, "UF_GALLERY_RECALC" => false)); 
		$bs = new CIBlockSection;
		foreach ($arFilters as $arFilter)
		{
			$db_res = CIBlockSection::GetList(array("ID" => "ASC"), $arFilter); 
			if ($db_res && $res = $db_res->Fetch())
			{
				
				do 
				{
					$arFields = array(
						"IBLOCK_ID" => $IBLOCK_ID, 
						"UF_GALLERY_SIZE" => 0);
					$GLOBALS["UF_GALLERY_SIZE"] = 0; 
					$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("IBLOCK_".$IBLOCK_ID."_SECTION", $arFields);
					$bs->Update($res["ID"], $arFields, false, false);
				} while ($res = $db_res->Fetch()); 
			}
		}
	}

	
	
	public static function OnAfterIBlockElementAdd($res)
	{
		$ID = doubleval($res["ID"]); 
		if (CPhotogalleryElement::CheckElement($ID, $arElement, $arSection, $arGallery))
		{
			$GLOBALS["UF_GALLERY_SIZE"] = $GLOBALS["PHOTOGALLERY_VARS"]["arGalleries"][$arGallery["ID"]]["UF_GALLERY_SIZE"] = (doubleval($arGallery["UF_GALLERY_SIZE"]) + $arElement["FILE"]["FILE_SIZE"]); 
			$arFields = array(
				"IBLOCK_ID" => $arElement["IBLOCK_ID"], 
				"UF_GALLERY_SIZE" => $GLOBALS["UF_GALLERY_SIZE"]);
			$bs = new CIBlockSection;
			$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("IBLOCK_".$arElement["IBLOCK_ID"]."_SECTION", $arFields);
			$res = $bs->Update($arGallery["ID"], $arFields, false, false);
		}
		return true;
	}
}
?>