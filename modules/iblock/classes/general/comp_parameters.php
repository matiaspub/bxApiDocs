<?
IncludeModuleLangFile(__FILE__);

class CIBlockParameters
{
	public static function GetFieldCode($name, $parent, $options = array())
	{
		//Common use in components
		$result = array(
			"PARENT" => $parent,
			"NAME" => $name,
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"ADDITIONAL_VALUES" => "Y",
			"SIZE" => 8,
			"VALUES" => array(
				"ID" => GetMessage("IBLOCK_FIELD_ID"),
				"CODE" => GetMessage("IBLOCK_FIELD_CODE"),
				"XML_ID" => GetMessage("IBLOCK_FIELD_XML_ID"),
				"NAME" => GetMessage("IBLOCK_FIELD_NAME"),
				"TAGS" => GetMessage("IBLOCK_FIELD_TAGS"),
				"SORT"=> GetMessage("IBLOCK_FIELD_SORT"),
				"PREVIEW_TEXT" => GetMessage("IBLOCK_FIELD_PREVIEW_TEXT"),
				"PREVIEW_PICTURE" => GetMessage("IBLOCK_FIELD_PREVIEW_PICTURE"),
				"DETAIL_TEXT" => GetMessage("IBLOCK_FIELD_DETAIL_TEXT"),
				"DETAIL_PICTURE" => GetMessage("IBLOCK_FIELD_DETAIL_PICTURE"),
				"DATE_ACTIVE_FROM" => GetMessage("IBLOCK_FIELD_DATE_ACTIVE_FROM"),
				"ACTIVE_FROM" => GetMessage("IBLOCK_FIELD_ACTIVE_FROM"),
				"DATE_ACTIVE_TO" => GetMessage("IBLOCK_FIELD_DATE_ACTIVE_TO"),
				"ACTIVE_TO" => GetMessage("IBLOCK_FIELD_ACTIVE_TO"),
				"SHOW_COUNTER" => GetMessage("IBLOCK_FIELD_SHOW_COUNTER"),
				"SHOW_COUNTER_START" => GetMessage("IBLOCK_FIELD_SHOW_COUNTER_START"),
				"IBLOCK_TYPE_ID" => GetMessage("IBLOCK_FIELD_IBLOCK_TYPE_ID"),
				"IBLOCK_ID" => GetMessage("IBLOCK_FIELD_IBLOCK_ID"),
				"IBLOCK_CODE" => GetMessage("IBLOCK_FIELD_IBLOCK_CODE"),
				"IBLOCK_NAME" => GetMessage("IBLOCK_FIELD_IBLOCK_NAME"),
				"IBLOCK_EXTERNAL_ID" => GetMessage("IBLOCK_FIELD_IBLOCK_EXTERNAL_ID"),
				"DATE_CREATE" => GetMessage("IBLOCK_FIELD_DATE_CREATE"),
				"CREATED_BY" => GetMessage("IBLOCK_FIELD_CREATED_BY"),
				"CREATED_USER_NAME" => GetMessage("IBLOCK_FIELD_CREATED_USER_NAME"),
				"TIMESTAMP_X" => GetMessage("IBLOCK_FIELD_TIMESTAMP_X"),
				"MODIFIED_BY" => GetMessage("IBLOCK_FIELD_MODIFIED_BY"),
				"USER_NAME" => GetMessage("IBLOCK_FIELD_USER_NAME"),
			),
		);

		//Check for any additional fields
		if(isset($options["SECTION_ID"]) && $options["SECTION_ID"])
			$result["VALUES"]["SECTION_ID"] = GetMessage("IBLOCK_FIELD_SECTION_ID");

		return $result;
	}

	public static function GetSectionFieldCode($name, $parent, $options = array())
	{
		//Common use in components
		$result = array(
			"PARENT" => $parent,
			"NAME" => $name,
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"ADDITIONAL_VALUES" => "Y",
			"SIZE" => 8,
			"VALUES" => array(
				"ID" => GetMessage("IBLOCK_FIELD_ID"),
				"CODE" => GetMessage("IBLOCK_FIELD_CODE"),
				"XML_ID" => GetMessage("IBLOCK_FIELD_XML_ID"),
				"NAME" => GetMessage("IBLOCK_FIELD_NAME"),
				"SORT"=> GetMessage("IBLOCK_FIELD_SORT"),
				"DESCRIPTION" => GetMessage("IBLOCK_FIELD_DESCRIPTION"),
				"PICTURE" => GetMessage("IBLOCK_FIELD_PICTURE"),
				"DETAIL_PICTURE" => GetMessage("IBLOCK_FIELD_DETAIL_PICTURE"),
				"IBLOCK_TYPE_ID" => GetMessage("IBLOCK_FIELD_IBLOCK_TYPE_ID"),
				"IBLOCK_ID" => GetMessage("IBLOCK_FIELD_IBLOCK_ID"),
				"IBLOCK_CODE" => GetMessage("IBLOCK_FIELD_IBLOCK_CODE"),
				"IBLOCK_EXTERNAL_ID" => GetMessage("IBLOCK_FIELD_IBLOCK_EXTERNAL_ID"),
				"DATE_CREATE" => GetMessage("IBLOCK_FIELD_DATE_CREATE"),
				"CREATED_BY" => GetMessage("IBLOCK_FIELD_CREATED_BY"),
				"TIMESTAMP_X" => GetMessage("IBLOCK_FIELD_TIMESTAMP_X"),
				"MODIFIED_BY" => GetMessage("IBLOCK_FIELD_MODIFIED_BY"),
			),
		);
		return $result;
	}

	public static function GetDateFormat($name, $parent)
	{
		global $DB;

		$timestamp = mktime(7,30,45,2,22,2007);
		return array(
			"PARENT" => $parent,
			"NAME" => $name,
			"TYPE" => "LIST",
			"SIZE" => 8,
			"VALUES" => array(
				"d-m-Y" => CIBlockFormatProperties::DateFormat("d-m-Y", $timestamp),//"22-02-2007",
				"m-d-Y" => CIBlockFormatProperties::DateFormat("m-d-Y", $timestamp),//"02-22-2007",
				"Y-m-d" => CIBlockFormatProperties::DateFormat("Y-m-d", $timestamp),//"2007-02-22",
				"d.m.Y" => CIBlockFormatProperties::DateFormat("d.m.Y", $timestamp),//"22.02.2007",
				"d.M.Y" => CIBlockFormatProperties::DateFormat("d.M.Y", $timestamp),//"22.Feb.2007",
				"m.d.Y" => CIBlockFormatProperties::DateFormat("m.d.Y", $timestamp),//"02.22.2007",
				"j M Y" => CIBlockFormatProperties::DateFormat("j M Y", $timestamp),//"22 Feb 2007",
				"M j, Y" => CIBlockFormatProperties::DateFormat("M j, Y", $timestamp),//"Feb 22, 2007",
				"j F Y" => CIBlockFormatProperties::DateFormat("j F Y", $timestamp),//"22 February 2007",
				"f j, Y" => CIBlockFormatProperties::DateFormat("f j, Y", $timestamp),//"February 22, 2007",
				"d.m.y g:i A" => CIBlockFormatProperties::DateFormat("d.m.y g:i A", $timestamp),//"22.02.07 1:30 PM",
				"d.M.y g:i A" => CIBlockFormatProperties::DateFormat("d.M.y g:i A", $timestamp),//"22.Feb.07 1:30 PM",
				"d.M.Y g:i A" => CIBlockFormatProperties::DateFormat("d.M.Y g:i A", $timestamp),//"22.Febkate.2007 1:30 PM",
				"d.m.y G:i" => CIBlockFormatProperties::DateFormat("d.m.y G:i", $timestamp),//"22.02.07 7:30",
				"d.m.Y H:i" => CIBlockFormatProperties::DateFormat("d.m.Y H:i", $timestamp),//"22.02.2007 07:30",
				"SHORT" => GetMessage('COMP_PARAM_DATE_FORMAT_SITE'),
				"FULL" => GetMessage('COMP_PARAM_DATETIME_FORMAT_SITE')
			),
			"DEFAULT" => $DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")),
			"ADDITIONAL_VALUES" => "Y",
		);
	}

	public static function GetPathTemplateMenuItems($menuType, $action_function, $menuID, $inputID = "")
	{
		switch($menuType)
		{
		case "DETAIL":
			return array(
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SITE_DIR"),
					"TITLE" => "#SITE_DIR# - ".GetMessage("IB_COMPLIB_POPUP_SITE_DIR"),
					"ONCLICK" => "$action_function('#SITE_DIR#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SERVER_NAME"),
					"TITLE" => "#SERVER_NAME# - ".GetMessage("IB_COMPLIB_POPUP_SERVER_NAME"),
					"ONCLICK" => "$action_function('#SERVER_NAME#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_TYPE_ID"),
					"TITLE" => "#IBLOCK_TYPE_ID# - ".GetMessage("IB_COMPLIB_POPUP_IBLOCK_TYPE_ID"),
					"ONCLICK" => "$action_function('#IBLOCK_TYPE_ID#', '$menuID', '$inputID')",
				),
				array("SEPARATOR" => true),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_ID"),
					"TITLE" => "#IBLOCK_ID#".GetMessage("IB_COMPLIB_POPUP_IBLOCK_ID"),
					"ONCLICK" => "$action_function('#IBLOCK_ID#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_CODE"),
					"TITLE" => "#IBLOCK_CODE# - ".GetMessage("IB_COMPLIB_POPUP_IBLOCK_CODE"),
					"ONCLICK" => "$action_function('#IBLOCK_CODE#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_EXTERNAL_ID"),
					"TITLE" => "#IBLOCK_EXTERNAL_ID# - ".GetMessage("IB_COMPLIB_POPUP_IBLOCK_EXTERNAL_ID"),
					"ONCLICK" => "$action_function('#IBLOCK_EXTERNAL_ID#', '$menuID', '$inputID')",
				),
				array("SEPARATOR" => true),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION_ID"),
					"TITLE" => "#SECTION_ID# - ".GetMessage("IB_COMPLIB_POPUP_SECTION_ID"),
					"ONCLICK" => "$action_function('#SECTION_ID#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION_CODE"),
					"TITLE" => "#SECTION_CODE# - ".GetMessage("IB_COMPLIB_POPUP_SECTION_CODE"),
					"ONCLICK" => "$action_function('#SECTION_CODE#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION_CODE_PATH"),
					"TITLE" => "#SECTION_CODE_PATH# - ".GetMessage("IB_COMPLIB_POPUP_SECTION_CODE_PATH"),
					"ONCLICK" => "$action_function('#SECTION_CODE_PATH#', '$menuID', '$inputID')",
				),
				array("SEPARATOR" => true),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_ELEMENT_ID"),
					"TITLE" => "#ID# - ".GetMessage("IB_COMPLIB_POPUP_ELEMENT_ID"),
					"ONCLICK" => "$action_function('#ID#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_ELEMENT_ID")."(2)",
					"TITLE" => "#ELEMENT_ID# - ".GetMessage("IB_COMPLIB_POPUP_ELEMENT_ID"),
					"ONCLICK" => "$action_function('#ELEMENT_ID#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_ELEMENT_CODE"),
					"TITLE" => "#CODE# - ".GetMessage("IB_COMPLIB_POPUP_ELEMENT_CODE"),
					"ONCLICK" => "$action_function('#CODE#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_ELEMENT_CODE")."(2)",
					"TITLE" => "#ELEMENT_CODE# - ".GetMessage("IB_COMPLIB_POPUP_ELEMENT_CODE"),
					"ONCLICK" => "$action_function('#ELEMENT_CODE#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_ELEMENT_EXTERNAL_ID"),
					"TITLE" => "#EXTERNAL_ID# - ".GetMessage("IB_COMPLIB_POPUP_ELEMENT_EXTERNAL_ID"),
					"ONCLICK" => "$action_function('#EXTERNAL_ID#', '$menuID', '$inputID')",
				),
			);
		case "SECTION":
			return array(
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SITE_DIR"),
					"TITLE" => "#SITE_DIR# - ".GetMessage("IB_COMPLIB_POPUP_SITE_DIR"),
					"ONCLICK" => "$action_function('#SITE_DIR#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SERVER_NAME"),
					"TITLE" => "#SERVER_NAME# - ".GetMessage("IB_COMPLIB_POPUP_SERVER_NAME"),
					"ONCLICK" => "$action_function('#SERVER_NAME#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_TYPE_ID"),
					"TITLE" => "#IBLOCK_TYPE_ID# - ".GetMessage("IB_COMPLIB_POPUP_IBLOCK_TYPE_ID"),
					"ONCLICK" => "$action_function('#IBLOCK_TYPE_ID#', '$menuID', '$inputID')",
				),
				array("SEPARATOR" => true),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_ID"),
					"TITLE"=>"#IBLOCK_ID# - ".GetMessage("IB_COMPLIB_POPUP_IBLOCK_ID"),
					"ONCLICK" => "$action_function('#IBLOCK_ID#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_CODE"),
					"TITLE" => "#IBLOCK_CODE# - ".GetMessage("IB_COMPLIB_POPUP_IBLOCK_CODE"),
					"ONCLICK" => "$action_function('#IBLOCK_CODE#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_EXTERNAL_ID"),
					"TITLE" => "#IBLOCK_EXTERNAL_ID# - ".GetMessage("IB_COMPLIB_POPUP_IBLOCK_EXTERNAL_ID"),
					"ONCLICK" => "$action_function('#IBLOCK_EXTERNAL_ID#', '$menuID', '$inputID')",
				),
				array("SEPARATOR" => true),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION_ID"),
					"TITLE" => "#ID# - ".GetMessage("IB_COMPLIB_POPUP_SECTION_ID"),
					"ONCLICK" => "$action_function('#ID#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION_ID")."(2)",
					"TITLE" => "#SECTION_ID# - ".GetMessage("IB_COMPLIB_POPUP_SECTION_ID"),
					"ONCLICK" => "$action_function('#SECTION_ID#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION_CODE"),
					"TITLE" => "#CODE# - ".GetMessage("IB_COMPLIB_POPUP_SECTION_CODE"),
					"ONCLICK" => "$action_function('#CODE#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION_CODE")."(2)",
					"TITLE" => "#SECTION_CODE# - ".GetMessage("IB_COMPLIB_POPUP_SECTION_CODE"),
					"ONCLICK" => "$action_function('#SECTION_CODE#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION_CODE_PATH"),
					"TITLE" => "#SECTION_CODE_PATH# - ".GetMessage("IB_COMPLIB_POPUP_SECTION_CODE_PATH"),
					"ONCLICK" => "$action_function('#SECTION_CODE_PATH#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION_EXTERNAL_ID"),
					"TITLE"=>"#EXTERNAL_ID# - ".GetMessage("IB_COMPLIB_POPUP_SECTION_EXTERNAL_ID"),
					"ONCLICK" => "$action_function('#EXTERNAL_ID#', '$menuID', '$inputID')",
				),
			);
		default:
			return array(
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SITE_DIR"),
					"ONCLICK" => "$action_function('#SITE_DIR#', '$menuID', '$inputID')",
					"TITLE"=> "#SITE_DIR# - ".GetMessage("IB_COMPLIB_POPUP_SITE_DIR"),
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SERVER_NAME"),
					"TITLE" => "#SERVER_NAME# - ".GetMessage("IB_COMPLIB_POPUP_SERVER_NAME"),
					"ONCLICK" => "$action_function('#SERVER_NAME#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_TYPE_ID"),
					"TITLE" => "#IBLOCK_TYPE_ID# - ".GetMessage("IB_COMPLIB_POPUP_IBLOCK_TYPE_ID"),
					"ONCLICK" => "$action_function('#IBLOCK_TYPE_ID#', '$menuID', '$inputID')",
				),
				array("SEPARATOR" => true),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_ID"),
					"TITLE"=>"#IBLOCK_ID# - ".GetMessage("IB_COMPLIB_POPUP_IBLOCK_ID"),
					"ONCLICK" => "$action_function('#IBLOCK_ID#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_CODE"),
					"TITLE" => "#IBLOCK_CODE# - ".GetMessage("IB_COMPLIB_POPUP_IBLOCK_CODE"),
					"ONCLICK" => "$action_function('#IBLOCK_CODE#', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_EXTERNAL_ID"),
					"TITLE" => "#IBLOCK_EXTERNAL_ID# - ".GetMessage("IB_COMPLIB_POPUP_IBLOCK_EXTERNAL_ID"),
					"ONCLICK" => "$action_function('#IBLOCK_EXTERNAL_ID#', '$menuID', '$inputID')",
				),
			);
		}
	}

	public static function GetInheritedPropertyTemplateSectionMenuItems($iblock_id, $action_function, $menuID, $inputID = "")
	{
		global $USER_FIELD_MANAGER;
		$result = array();
		$result["this"] = array(
			"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION"),
			"MENU" => array(
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION_NAME"),
					"ONCLICK" => "$action_function('{=this.Name}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION_LOWER_NAME"),
					"ONCLICK" => "$action_function('{=lower this.Name}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION_CODE"),
					"ONCLICK" => "$action_function('{=this.Code}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTION_PREVIEW_TEXT"),
					"ONCLICK" => "$action_function('{=this.PreviewText}', '$menuID', '$inputID')",
				),
			),
		);
		if ($iblock_id > 0)
		{
			$result["properties"] = array(
				"TEXT" => GetMessage("IB_COMPLIB_POPUP_PROPERTIES"),
				"MENU" => array(
				),
			);
			$arProperty = $USER_FIELD_MANAGER->GetUserFields("IBLOCK_".$iblock_id."_SECTION");
			foreach($arProperty as $property)
			{
				if ($property["PROPERTY_TYPE"] != "F")
				{
					$result["properties"]["MENU"][] = array(
						"TEXT" => $property["FIELD_NAME"],
						"ONCLICK" => "$action_function('{=this.property.".strtolower(substr($property["FIELD_NAME"], 3))."}', '$menuID', '$inputID')",
					);
				}
			}
		}
		$result["parent"] = array(
			"TEXT" => GetMessage("IB_COMPLIB_POPUP_PARENT"),
			"MENU" => array(
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_PARENT_NAME"),
					"ONCLICK" => "$action_function('{=parent.Name}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_PARENT_CODE"),
					"ONCLICK" => "$action_function('{=parent.Code}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_PARENT_TEXT"),
					"ONCLICK" => "$action_function('{=parent.PreviewText}', '$menuID', '$inputID')",
				),
			),
		);
		$result["iblock"] = array(
			"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK"),
			"MENU" => array(
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_NAME"),
					"ONCLICK" => "$action_function('{=iblock.Name}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_CODE"),
					"ONCLICK" => "$action_function('{=iblock.Code}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_TEXT"),
					"ONCLICK" => "$action_function('{=iblock.PreviewText}', '$menuID', '$inputID')",
				),
			),
		);
		if (\Bitrix\Main\Loader::includeModule('catalog'))
		{
			$result["store"] = array(
				"TEXT" => GetMessage("IB_COMPLIB_POPUP_STORE"),
				"MENU" => array(),
			);
			$rsStore = CCatalogStore::GetList();
			while ($store = $rsStore->Fetch())
			{
				$result["store"]["MENU"][] = array(
					"TEXT" => $store["TITLE"],
					"ONCLICK" => "$action_function('{=catalog.store.".$store["ID"].".name}', '$menuID', '$inputID')",
				);
			}
		}
		$result["misc"] = array(
			"TEXT" => GetMessage("IB_COMPLIB_POPUP_MISC"),
			"MENU" => array(),
		);
		$result["misc"]["MENU"][] =  array(
			"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTIONS_PATH"),
			"ONCLICK" => "$action_function('{=concat this.sections.name this.name \" / \"}', '$menuID', '$inputID')",
		);
		if (\Bitrix\Main\Loader::includeModule('catalog'))
		{
			$result["misc"]["MENU"][] =  array(
				"TEXT" => GetMessage("IB_COMPLIB_POPUP_STORE_LIST"),
				"ONCLICK" => "$action_function('{=concat catalog.store \", \"}', '$menuID', '$inputID')",
			);
		}
		$r = array();
		foreach($result as $category)
		{
			if (!empty($category) && !empty($category["MENU"]))
			{
				$r[] = $category;
			}
		}
		return $r;
	}

	public static function GetInheritedPropertyTemplateElementMenuItems($iblock_id, $action_function, $menuID, $inputID = "")
	{
		$result = array();
		$result["this"] = array(
			"TEXT" => GetMessage("IB_COMPLIB_POPUP_ELEMENT"),
			"MENU" => array(
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_ELEMENT_NAME"),
					"ONCLICK" => "$action_function('{=this.Name}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_ELEMENT_LOWER_NAME"),
					"ONCLICK" => "$action_function('{=lower this.Name}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_ELEMENT_CODE"),
					"ONCLICK" => "$action_function('{=this.Code}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_ELEMENT_PREVIEW_TEXT"),
					"ONCLICK" => "$action_function('{=this.PreviewText}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_ELEMENT_DETAIL_TEXT"),
					"ONCLICK" => "$action_function('{=this.DetailText}', '$menuID', '$inputID')",
				),
			),
		);
		if ($iblock_id > 0)
		{
			$result["properties"] = array(
				"TEXT" => GetMessage("IB_COMPLIB_POPUP_PROPERTIES"),
				"MENU" => array(
				),
			);
			$rsProperty = CIBlockProperty::GetList(array(), array("IBLOCK_ID" => $iblock_id));
			while($property = $rsProperty->fetch())
			{
				if ($property["PROPERTY_TYPE"] != "F")
				{
					$result["properties"]["MENU"][] = array(
						"TEXT" => $property["NAME"],
						"ONCLICK" => "$action_function('{=this.property.".($property["CODE"]!=""? $property["CODE"]: $property["ID"])."}', '$menuID', '$inputID')",
					);
				}
			}
		}
		$result["parent"] = array(
			"TEXT" => GetMessage("IB_COMPLIB_POPUP_PARENT"),
			"MENU" => array(
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_PARENT_NAME"),
					"ONCLICK" => "$action_function('{=parent.Name}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_PARENT_CODE"),
					"ONCLICK" => "$action_function('{=parent.Code}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_PARENT_TEXT"),
					"ONCLICK" => "$action_function('{=parent.PreviewText}', '$menuID', '$inputID')",
				),
			),
		);
		$result["iblock"] = array(
			"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK"),
			"MENU" => array(
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_NAME"),
					"ONCLICK" => "$action_function('{=iblock.Name}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_CODE"),
					"ONCLICK" => "$action_function('{=iblock.Code}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_IBLOCK_TEXT"),
					"ONCLICK" => "$action_function('{=iblock.PreviewText}', '$menuID', '$inputID')",
				),
			),
		);
		if (\Bitrix\Main\Loader::includeModule('catalog'))
		{
			$arCatalog = \CCatalogSKU::GetInfoByProductIBlock($iblock_id);
			if (is_array($arCatalog))
			{
				$result["sku_properties"] = array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SKU_PROPERTIES"),
					"MENU" => array(
					),
				);
				$rsProperty = CIBlockProperty::GetList(array(), array("IBLOCK_ID" => $arCatalog["IBLOCK_ID"]));
				while($property = $rsProperty->fetch())
				{
					if ($property["PROPERTY_TYPE"] != "F")
					{
						$result["sku_properties"]["MENU"][] = array(
							"TEXT" => $property["NAME"],
							"ONCLICK" => "$action_function('{=concat {=distinct this.catalog.sku.property.".($property["CODE"]!=""? $property["CODE"]: $property["ID"])." \", \"}}', '$menuID', '$inputID')",
						);
					}
				}
				$result["sku_price"] = array(
					"TEXT" => GetMessage("IB_COMPLIB_POPUP_SKU_PRICE"),
					"MENU" => array(),
				);
				$rsPrice = CCatalogGroup::GetListEx(array("SORT"=>"ASC"), array(), false, false, array("ID", "NAME"));
				while ($price = $rsPrice->Fetch())
				{
					if (preg_match("/^[a-zA-Z0-9]+\$/", $price["NAME"]))
					{
						$result["sku_price"]["MENU"][] = array(
							"TEXT" => GetMessage("IB_COMPLIB_POPUP_MIN_PRICE")." ".$price["NAME"],
							"ONCLICK" => "$action_function('{=min this.catalog.sku.price.".$price["NAME"]."}', '$menuID', '$inputID')",
						);
						$result["sku_price"]["MENU"][] = array(
							"TEXT" => GetMessage("IB_COMPLIB_POPUP_MAX_PRICE")." ".$price["NAME"],
							"ONCLICK" => "$action_function('{=max this.catalog.sku.price.".$price["NAME"]."}', '$menuID', '$inputID')",
						);
					}
					else
					{
						$result["sku_price"]["MENU"][] = array(
							"TEXT" => GetMessage("IB_COMPLIB_POPUP_MIN_PRICE")." ".$price["NAME"],
							"ONCLICK" => "$action_function('{=min this.catalog.sku.price.".$price["ID"]."}', '$menuID', '$inputID')",
						);
						$result["sku_price"]["MENU"][] = array(
							"TEXT" => GetMessage("IB_COMPLIB_POPUP_MAX_PRICE")." ".$price["NAME"],
							"ONCLICK" => "$action_function('{=max this.catalog.sku.price.".$price["ID"]."}', '$menuID', '$inputID')",
						);
					}
				}
			}

			$result["catalog"] = array(
				"TEXT" => GetMessage("IB_COMPLIB_POPUP_CATALOG"),
				"MENU" => array(
					array(
						"TEXT" => GetMessage("IB_COMPLIB_POPUP_CATALOG_WEIGHT"),
						"ONCLICK" => "$action_function('{=this.catalog.weight}', '$menuID', '$inputID')",
					),
					array(
						"TEXT" => GetMessage("IB_COMPLIB_POPUP_CATALOG_MEASURE"),
						"ONCLICK" => "$action_function('{=this.catalog.measure}', '$menuID', '$inputID')",
					),
				),
			);
			$result["price"] = array(
				"TEXT" => GetMessage("IB_COMPLIB_POPUP_PRICE"),
				"MENU" => array(),
			);
			$rsPrice = CCatalogGroup::GetListEx(array("SORT"=>"ASC"), array(), false, false, array("ID", "NAME"));
			while ($price = $rsPrice->Fetch())
			{
				if (preg_match("/^[a-zA-Z0-9]+\$/", $price["NAME"]))
					$result["price"]["MENU"][] = array(
						"TEXT" => $price["NAME"],
						"ONCLICK" => "$action_function('{=this.catalog.price.".$price["NAME"]."}', '$menuID', '$inputID')",
					);
				else
					$result["price"]["MENU"][] = array(
						"TEXT" => $price["NAME"],
						"ONCLICK" => "$action_function('{=this.catalog.price.".$price["ID"]."}', '$menuID', '$inputID')",
					);
			}
			$result["store"] = array(
				"TEXT" => GetMessage("IB_COMPLIB_POPUP_STORE"),
				"MENU" => array(),
			);
			$rsStore = CCatalogStore::GetList();
			while ($store = $rsStore->Fetch())
			{
				$result["store"]["MENU"][] = array(
					"TEXT" => $store["TITLE"],
					"ONCLICK" => "$action_function('{=catalog.store.".$store["ID"].".name}', '$menuID', '$inputID')",
				);
			}
		}
		$result["misc"] = array(
			"TEXT" => GetMessage("IB_COMPLIB_POPUP_MISC"),
			"MENU" => array(),
		);
		$result["misc"]["MENU"][] =  array(
			"TEXT" => GetMessage("IB_COMPLIB_POPUP_SECTIONS_PATH"),
			"ONCLICK" => "$action_function('{=concat this.sections.name \" / \"}', '$menuID', '$inputID')",
		);
		if (\Bitrix\Main\Loader::includeModule('catalog'))
		{
			$result["misc"]["MENU"][] =  array(
				"TEXT" => GetMessage("IB_COMPLIB_POPUP_STORE_LIST"),
				"ONCLICK" => "$action_function('{=concat catalog.store \", \"}', '$menuID', '$inputID')",
			);
		}
		$r = array();
		foreach($result as $category)
		{
			if (!empty($category) && !empty($category["MENU"]))
			{
				$r[] = $category;
			}
		}
		return $r;
	}

	public static function GetPathTemplateParam($menuType, $ID, $parameterName, $defaultValue = "", $parentID = "URL_TEMPLATES")
	{
		return array(
			"PARENT" => $parentID,
			"NAME" => $parameterName,
			"TYPE" => "CUSTOM",
			"DEFAULT" => $defaultValue,
			"JS_FILE" => BX_ROOT."/js/iblock/path_templates.js",
			"JS_EVENT" => "IBlockComponentProperties",
			"JS_DATA" => str_replace("\n", "", CUtil::PhpToJSObject(array(
				"mnu_".$ID, //menu div ID
				5000, //zIndex
				CIBlockParameters::GetPathTemplateMenuItems($menuType, "window.IBlockComponentPropertiesObj.Action", "mnu_".$ID), //Menu items
			))),
		);
	}

	public static function AddPagerSettings(&$arComponentParameters, $pager_title, $bDescNumbering=true, $bShowAllParam=false)
	{
		$arHiddenTemplates = array(
			'js' => true
		);
		if (!isset($arComponentParameters['GROUPS']))
			$arComponentParameters['GROUPS'] = array();
		$arComponentParameters["GROUPS"]["PAGER_SETTINGS"] = array(
			"NAME" => GetMessage("T_IBLOCK_DESC_PAGER_SETTINGS"),
		);

		$arTemplateInfo = CComponentUtil::GetTemplatesList('bitrix:system.pagenavigation');
		if (empty($arTemplateInfo))
		{
			$arComponentParameters["PARAMETERS"]["PAGER_TEMPLATE"] = Array(
				"PARENT" => "PAGER_SETTINGS",
				"NAME" => GetMessage("T_IBLOCK_DESC_PAGER_TEMPLATE"),
				"TYPE" => "STRING",
				"DEFAULT" => "",
			);
		}
		else
		{
			sortByColumn($arTemplateInfo, array('TEMPLATE' => SORT_ASC, 'NAME' => SORT_ASC));
			$arTemplateList = array();
			$arSiteTemplateList = array(
				'.default' => GetMessage('T_IBLOCK_DESC_PAGER_TEMPLATE_SITE_DEFAULT')
			);
			$arTemplateID = array();
			foreach ($arTemplateInfo as &$template)
			{
				if ('' != $template["TEMPLATE"] && '.default' != $template["TEMPLATE"])
					$arTemplateID[] = $template["TEMPLATE"];
				if (!isset($template['TITLE']))
					$template['TITLE'] = $template['NAME'];
			}
			unset($template);

			if (!empty($arTemplateID))
			{
				$rsSiteTemplates = CSiteTemplate::GetList(
					array(),
					array("ID"=>$arTemplateID),
					array()
				);
				while ($arSitetemplate = $rsSiteTemplates->Fetch())
				{
					$arSiteTemplateList[$arSitetemplate['ID']] = $arSitetemplate['NAME'];
				}
			}

			foreach ($arTemplateInfo as &$template)
			{
				if (isset($arHiddenTemplates[$template['NAME']]))
					continue;
				$strDescr = $template["TITLE"].' ('.('' != $template["TEMPLATE"] && '' != $arSiteTemplateList[$template["TEMPLATE"]] ? $arSiteTemplateList[$template["TEMPLATE"]] : GetMessage("T_IBLOCK_DESC_PAGER_TEMPLATE_SYSTEM")).')';
				$arTemplateList[$template['NAME']] = $strDescr;
			}
			unset($template);
			$arComponentParameters["PARAMETERS"]["PAGER_TEMPLATE"] = array(
				"PARENT" => "PAGER_SETTINGS",
				"NAME" => GetMessage("T_IBLOCK_DESC_PAGER_TEMPLATE_EXT"),
				"TYPE" => "LIST",
				"VALUES" => $arTemplateList,
				"DEFAULT" => ".default",
				"ADDITIONAL_VALUES" => "Y"
			);
		}

		$arComponentParameters["PARAMETERS"]["DISPLAY_TOP_PAGER"] = Array(
			"PARENT" => "PAGER_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_TOP_PAGER"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		);
		$arComponentParameters["PARAMETERS"]["DISPLAY_BOTTOM_PAGER"] = Array(
			"PARENT" => "PAGER_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_BOTTOM_PAGER"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		);
		$arComponentParameters["PARAMETERS"]["PAGER_TITLE"] = Array(
			"PARENT" => "PAGER_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_PAGER_TITLE"),
			"TYPE" => "STRING",
			"DEFAULT" => $pager_title,
		);
		$arComponentParameters["PARAMETERS"]["PAGER_SHOW_ALWAYS"] = Array(
			"PARENT" => "PAGER_SETTINGS",
			"NAME" => GetMessage("T_IBLOCK_DESC_PAGER_SHOW_ALWAYS"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		);

		if($bDescNumbering)
		{
			$arComponentParameters["PARAMETERS"]["PAGER_DESC_NUMBERING"] = Array(
				"PARENT" => "PAGER_SETTINGS",
				"NAME" => GetMessage("T_IBLOCK_DESC_PAGER_DESC_NUMBERING"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "N",
			);
			$arComponentParameters["PARAMETERS"]["PAGER_DESC_NUMBERING_CACHE_TIME"] = Array(
				"PARENT" => "PAGER_SETTINGS",
				"NAME" => GetMessage("T_IBLOCK_DESC_PAGER_DESC_NUMBERING_CACHE_TIME"),
				"TYPE" => "STRING",
				"DEFAULT" => "36000",
			);
		}

		if($bShowAllParam)
		{
			$arComponentParameters["PARAMETERS"]["PAGER_SHOW_ALL"] = Array(
				"PARENT" => "PAGER_SETTINGS",
				"NAME" => GetMessage("T_IBLOCK_DESC_SHOW_ALL"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y"
			);
		}
	}

	public static function GetIBlockTypes($arTop = false)
	{
		if(is_array($arTop))
			$arIBlockType = $arTop;
		else
			$arIBlockType = array();
		$rsIBlockType = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
		while($arr=$rsIBlockType->Fetch())
		{
			if($ar=CIBlockType::GetByIDLang($arr["ID"], LANGUAGE_ID))
			{
				$arIBlockType[$arr["ID"]] = "[".$arr["ID"]."] ".$ar["~NAME"];
			}
		}
		return $arIBlockType;
	}

	public static function GetElementSortFields($arFields = array(), $arOptions = array())
	{
		$arResult = array();
		if (!is_array($arFields))
			$arFields = array($arFields);
		if (!is_array($arOptions))
			$arOptions = array();
		$boolLowerCase = (array_key_exists('KEY_LOWERCASE', $arOptions) && 'Y' == $arOptions['KEY_LOWERCASE'] ? true: false);
		$arSortFields = array(
			"SHOWS" => GetMessage("IBLOCK_SORT_FIELD_SHOWS"),
			"SORT" => GetMessage("IBLOCK_SORT_FIELD_SORT"),
			"TIMESTAMP_X" => GetMessage("IBLOCK_SORT_FIELD_TIMESTAMP"),
			"NAME" => GetMessage("IBLOCK_SORT_FIELD_NAME"),
			"ID" => GetMessage("IBLOCK_SORT_FIELD_ID"),
			"ACTIVE_FROM" => GetMessage("IBLOCK_SORT_FIELD_ACTIVE_FROM"),
			"ACTIVE_TO" => GetMessage("IBLOCK_SORT_FIELD_ACTIVE_TO"),
		);
		if (!empty($arFields))
		{
			foreach ($arFields as &$strFieldName)
			{
				if (array_key_exists($strFieldName, $arSortFields))
					$arResult[$strFieldName] = $arSortFields[$strFieldName];
			}
			if (isset($strFieldName))
				unset($strFieldName);
		}
		else
		{
			$arResult = $arSortFields;
		}
		if ($boolLowerCase)
		{
			$arResult = array_change_key_case($arResult, CASE_LOWER);
		}
		return $arResult;
	}

	public static function GetSectionSortFields($arFields = array(), $arOptions = array())
	{
		$arResult = array();
		if (!is_array($arFields))
			$arFields = array($arFields);
		if (!is_array($arOptions))
			$arOptions = array();
		$boolLowerCase = (array_key_exists('KEY_LOWERCASE', $arOptions) && 'Y' == $arOptions['KEY_LOWERCASE'] ? true: false);
		$arSortFields = array(
			"SORT" => GetMessage("IBLOCK_SORT_FIELD_SORT"),
			"TIMESTAMP_X" => GetMessage("IBLOCK_SORT_FIELD_TIMESTAMP"),
			"NAME" => GetMessage("IBLOCK_SORT_FIELD_NAME"),
			"ID" => GetMessage("IBLOCK_SORT_FIELD_ID"),
			"DEPTH_LEVEL" => GetMessage("IBLOCK_SORT_FIELD_DEPTH_LEVEL"),
		);
		if (!empty($arFields))
		{
			foreach ($arFields as &$strFieldName)
			{
				if (array_key_exists($strFieldName, $arSortFields))
					$arResult[$strFieldName] = $arSortFields[$strFieldName];
			}
			if (isset($strFieldName))
				unset($strFieldName);
		}
		else
		{
			$arResult = $arSortFields;
		}
		if ($boolLowerCase)
		{
			$arResult = array_change_key_case($arResult, CASE_LOWER);
		}
		return $arResult;
	}
}

?>