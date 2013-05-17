<?
IncludeModuleLangFile(__FILE__);

class CListsParameters
{
	public static function GetPathTemplateMenuItems($menuType, $action_function, $menuID, $inputID = "")
	{
		$arResult = array();

		switch($menuType)
		{
		case "FILE":
			$arResult[] = array(
				"TEXT" => GetMessage("LISTS_PARAMS_POPUP_FILE_ID"),
				"TITLE" => "#list_id# - ".GetMessage("LISTS_PARAMS_POPUP_FILE_ID"),
				"ONCLICK" => "$action_function('#file_id#', '$menuID', '$inputID')",
			);
			$arResult[] = array(
				"TEXT" => GetMessage("LISTS_PARAMS_POPUP_FIELD_ID"),
				"TITLE" => "#list_id# - ".GetMessage("LISTS_PARAMS_POPUP_FIELD_ID"),
				"ONCLICK" => "$action_function('#field_id#', '$menuID', '$inputID')",
			);
		case "ELEMENT":
			$arResult[] = array(
				"TEXT" => GetMessage("LISTS_PARAMS_POPUP_ELEMENT_ID"),
				"TITLE" => "#list_id# - ".GetMessage("LISTS_PARAMS_POPUP_ELEMENT_ID"),
				"ONCLICK" => "$action_function('#element_id#', '$menuID', '$inputID')",
			);
		case "SECTIONS":
			$arResult[] = array(
				"TEXT" => GetMessage("LISTS_PARAMS_POPUP_SECTION_ID"),
				"TITLE" => "#list_id# - ".GetMessage("LISTS_PARAMS_POPUP_SECTION_ID"),
				"ONCLICK" => "$action_function('#section_id#', '$menuID', '$inputID')",
			);
		case "LIST":
			$arResult[] = array(
				"TEXT" => GetMessage("LISTS_PARAMS_POPUP_LIST_ID"),
				"TITLE" => "#list_id# - ".GetMessage("LISTS_PARAMS_POPUP_LIST_ID"),
				"ONCLICK" => "$action_function('#list_id#', '$menuID', '$inputID')",
			);
		}

		return $arResult;
	}

	public static function GetPathTemplateParam($menuType, $ID, $parameterName, $defaultValue = "", $parentID = "URL_TEMPLATES")
	{
		if($menuType == "LISTS")
			return array(
				"PARENT" => $parentID,
				"NAME" => $parameterName,
				"TYPE" => "STRING",
				"DEFAULT" => $defaultValue,
			);
		else
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
					CListsParameters::GetPathTemplateMenuItems($menuType, "window.IBlockComponentPropertiesObj.Action", "mnu_".$ID), //Menu items
				))),
			);
	}
}

?>