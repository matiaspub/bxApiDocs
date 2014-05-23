<?
IncludeModuleLangFile(__FILE__);

class CIBlockPropertyElementList
{
	function PrepareSettings($arProperty)
	{
		$size = 0;
		if(is_array($arProperty["USER_TYPE_SETTINGS"]))
			$size = intval($arProperty["USER_TYPE_SETTINGS"]["size"]);
		if($size <= 0)
			$size = 1;

		$width = 0;
		if(is_array($arProperty["USER_TYPE_SETTINGS"]))
			$width = intval($arProperty["USER_TYPE_SETTINGS"]["width"]);
		if($width <= 0)
			$width = 0;

		if(is_array($arProperty["USER_TYPE_SETTINGS"]) && $arProperty["USER_TYPE_SETTINGS"]["group"] === "Y")
			$group = "Y";
		else
			$group = "N";

		if(is_array($arProperty["USER_TYPE_SETTINGS"]) && $arProperty["USER_TYPE_SETTINGS"]["multiple"] === "Y")
			$multiple = "Y";
		else
			$multiple = "N";

		return array(
			"size" =>  $size,
			"width" => $width,
			"group" => $group,
			"multiple" => $multiple,
		);
	}

	function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$settings = CIBlockPropertyElementList::PrepareSettings($arProperty);

		$arPropertyFields = array(
			"HIDE" => array("ROW_COUNT", "COL_COUNT", "MULTIPLE_CNT"),
		);

		return '
		<tr valign="top">
			<td>'.GetMessage("IBLOCK_PROP_ELEMENT_LIST_SETTING_SIZE").':</td>
			<td><input type="text" size="5" name="'.$strHTMLControlName["NAME"].'[size]" value="'.$settings["size"].'"></td>
		</tr>
		<tr valign="top">
			<td>'.GetMessage("IBLOCK_PROP_ELEMENT_LIST_SETTING_WIDTH").':</td>
			<td><input type="text" size="5" name="'.$strHTMLControlName["NAME"].'[width]" value="'.$settings["width"].'">px</td>
		</tr>
		<tr valign="top">
			<td>'.GetMessage("IBLOCK_PROP_ELEMENT_LIST_SETTING_SECTION_GROUP").':</td>
			<td><input type="checkbox" name="'.$strHTMLControlName["NAME"].'[group]" value="Y" '.($settings["group"]=="Y"? 'checked': '').'></td>
		</tr>
		<tr valign="top">
			<td>'.GetMessage("IBLOCK_PROP_ELEMENT_LIST_SETTING_MULTIPLE").':</td>
			<td><input type="checkbox" name="'.$strHTMLControlName["NAME"].'[multiple]" value="Y" '.($settings["multiple"]=="Y"? 'checked': '').'></td>
		</tr>
		';
	}

	//PARAMETERS:
	//$arProperty - b_iblock_property.*
	//$value - array("VALUE","DESCRIPTION") -- here comes HTML form value
	//strHTMLControlName - array("VALUE","DESCRIPTION")
	//return:
	//safe html
	function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		$settings = CIBlockPropertyElementList::PrepareSettings($arProperty);
		if($settings["size"] > 1)
			$size = ' size="'.$settings["size"].'"';
		else
			$size = '';

		if($settings["width"] > 0)
			$width = ' style="width:'.$settings["width"].'px"';
		else
			$width = '';

		$bWasSelect = false;
		$options = CIBlockPropertyElementList::GetOptionsHtml($arProperty, array($value["VALUE"]), $bWasSelect);

		$html = '<select name="'.$strHTMLControlName["VALUE"].'"'.$size.$width.'>';
		if($arProperty["IS_REQUIRED"] != "Y")
			$html .= '<option value=""'.(!$bWasSelect? ' selected': '').'>'.GetMessage("IBLOCK_PROP_ELEMENT_LIST_NO_VALUE").'</option>';
		$html .= $options;
		$html .= '</select>';
		return  $html;
	}

	function GetPropertyFieldHtmlMulty($arProperty, $value, $strHTMLControlName)
	{
		$max_n = 0;
		$values = array();
		if(is_array($value))
		{
			foreach($value as $property_value_id => $arValue)
			{
				$values[$property_value_id] = $arValue["VALUE"];
				if(preg_match("/^n(\\d+)$/", $property_value_id, $match))
				{
					if($match[1] > $max_n)
						$max_n = intval($match[1]);
				}
			}
		}

		$settings = CIBlockPropertyElementList::PrepareSettings($arProperty);
		if($settings["size"] > 1)
			$size = ' size="'.$settings["size"].'"';
		else
			$size = '';

		if($settings["width"] > 0)
			$width = ' style="width:'.$settings["width"].'px"';
		else
			$width = '';

		if($settings["multiple"]=="Y")
		{
			$bWasSelect = false;
			$options = CIBlockPropertyElementList::GetOptionsHtml($arProperty, $values, $bWasSelect);

			$html = '<input type="hidden" name="'.$strHTMLControlName["VALUE"].'[]" value="">';
			$html .= '<select multiple name="'.$strHTMLControlName["VALUE"].'[]"'.$size.$width.'>';
			if($arProperty["IS_REQUIRED"] != "Y")
				$html .= '<option value=""'.(!$bWasSelect? ' selected': '').'>'.GetMessage("IBLOCK_PROP_ELEMENT_LIST_NO_VALUE").'</option>';
			$html .= $options;
			$html .= '</select>';
		}
		else
		{
			if(end($values) != "" || substr(key($values), 0, 1) != "n")
				$values["n".($max_n+1)] = "";

			$name = $strHTMLControlName["VALUE"]."VALUE";

			$html = '<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tb'.md5($name).'">';
			foreach($values as $property_value_id=>$value)
			{
				$html .= '<tr><td>';

				$bWasSelect = false;
				$options = CIBlockPropertyElementList::GetOptionsHtml($arProperty, array($value), $bWasSelect);

				$html .= '<select name="'.$strHTMLControlName["VALUE"].'['.$property_value_id.'][VALUE]"'.$size.$width.'>';
				$html .= '<option value=""'.(!$bWasSelect? ' selected': '').'>'.GetMessage("IBLOCK_PROP_ELEMENT_LIST_NO_VALUE").'</option>';
				$html .= $options;
				$html .= '</select>';

				$html .= '</td></tr>';
			}
			$html .= '</table>';

			$html .= '<input type="button" value="'.GetMessage("IBLOCK_PROP_ELEMENT_LIST_ADD").'" onClick="if(window.addNewRow){addNewRow(\'tb'.md5($name).'\', -1)}else{addNewTableRow(\'tb'.md5($name).'\', 1, /\[(n)([0-9]*)\]/g, 2)}">';
		}
		return  $html;
	}

	function GetAdminFilterHTML($arProperty, $strHTMLControlName)
	{
		$lAdmin = new CAdminList($strHTMLControlName["TABLE_ID"]);
		$lAdmin->InitFilter(array($strHTMLControlName["VALUE"]));
		$filterValue = $GLOBALS[$strHTMLControlName["VALUE"]];

		if(isset($filterValue) && is_array($filterValue))
			$values = $filterValue;
		else
			$values = array();

		$settings = CIBlockPropertyElementList::PrepareSettings($arProperty);
		if($settings["size"] > 1)
			$size = ' size="'.$settings["size"].'"';
		else
			$size = '';

		if($settings["width"] > 0)
			$width = ' style="width:'.$settings["width"].'px"';
		else
			$width = '';

		$bWasSelect = false;
		$options = CIBlockPropertyElementList::GetOptionsHtml($arProperty, $values, $bWasSelect);

		$html = '<select multiple name="'.$strHTMLControlName["VALUE"].'[]"'.$size.$width.'>';
		$html .= '<option value=""'.(!$bWasSelect? ' selected': '').'>'.GetMessage("IBLOCK_PROP_ELEMENT_LIST_ANY_VALUE").'</option>';
		$html .= $options;
		$html .= '</select>';
		return  $html;
	}

	public function GetPublicViewHTML($arProperty, $arValue, $strHTMLControlName)
	{
		static $cache = array();

		$strResult = '';
		$arValue['VALUE'] = intval($arValue['VALUE']);
		if (0 < $arValue['VALUE'])
		{
			if (!isset($cache[$arValue['VALUE']]))
			{
				$arFilter = array();
				$intIBlockID = intval($arProperty['LINK_IBLOCK_ID']);
				if (0 < $intIBlockID) $arFilter['IBLOCK_ID'] = $intIBlockID;
				$arFilter['ID'] = $arValue['VALUE'];
				$arFilter["ACTIVE"] = "Y";
				$arFilter["ACTIVE_DATE"] = "Y";
				$arFilter["CHECK_PERMISSIONS"] = "Y";
				$rsElements = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID","IBLOCK_ID","NAME","DETAIL_PAGE_URL"));
				$cache[$arValue['VALUE']] = $rsElements->GetNext(true,false);
			}
			if (is_array($cache[$arValue['VALUE']]))
			{
				if (isset($strHTMLControlName['MODE']) && 'CSV_EXPORT' == $strHTMLControlName['MODE'])
				{
					$strResult = $cache[$arValue['VALUE']]['ID'];
				}
				elseif (isset($strHTMLControlName['MODE']) && ('SIMPLE_TEXT' == $strHTMLControlName['MODE'] || 'ELEMENT_TEMPLATE' == $strHTMLControlName['MODE']))
				{
					$strResult = $cache[$arValue['VALUE']]["NAME"];
				}
				else
				{
					$strResult = '<a href="'.$cache[$arValue['VALUE']]["DETAIL_PAGE_URL"].'">'.htmlspecialcharsEx($cache[$arValue['VALUE']]["NAME"]).'</a>';;
				}
			}
		}
		return $strResult;
	}

	function GetOptionsHtml($arProperty, $values, &$bWasSelect)
	{
		$options = "";
		$settings = CIBlockPropertyElementList::PrepareSettings($arProperty);
		$bWasSelect = false;

		if($settings["group"] === "Y")
		{
			$arElements = CIBlockPropertyElementList::GetElements($arProperty["LINK_IBLOCK_ID"]);
			$arTree = CIBlockPropertyElementList::GetSections($arProperty["LINK_IBLOCK_ID"]);
			foreach($arElements as $i => $arElement)
			{
				if(
					$arElement["IN_SECTIONS"] == "Y"
					&& array_key_exists($arElement["IBLOCK_SECTION_ID"], $arTree)
				)
				{
					$arTree[$arElement["IBLOCK_SECTION_ID"]]["E"][] = $arElement;
					unset($arElements[$i]);
				}
			}

			foreach($arTree as $arSection)
			{
				$options .= '<optgroup label="'.str_repeat(" . ", $arSection["DEPTH_LEVEL"]-1).$arSection["NAME"].'">';
				if(isset($arSection["E"]))
				{
					foreach($arSection["E"] as $arItem)
					{
						$options .= '<option value="'.$arItem["ID"].'"';
						if(in_array($arItem["~ID"], $values))
						{
							$options .= ' selected';
							$bWasSelect = true;
						}
						$options .= '>'.$arItem["NAME"].'</option>';
					}
				}
				$options .= '</optgroup>';
			}
			foreach($arElements as $arItem)
			{
				$options .= '<option value="'.$arItem["ID"].'"';
				if(in_array($arItem["~ID"], $values))
				{
					$options .= ' selected';
					$bWasSelect = true;
				}
				$options .= '>'.$arItem["NAME"].'</option>';
			}

		}
		else
		{
			foreach(CIBlockPropertyElementList::GetElements($arProperty["LINK_IBLOCK_ID"]) as $arItem)
			{
				$options .= '<option value="'.$arItem["ID"].'"';
				if(in_array($arItem["~ID"], $values))
				{
					$options .= ' selected';
					$bWasSelect = true;
				}
				$options .= '>'.$arItem["NAME"].'</option>';
			}
		}

		return  $options;
	}

	function GetElements($IBLOCK_ID)
	{
		static $cache = array();
		$IBLOCK_ID = intval($IBLOCK_ID);

		if(!array_key_exists($IBLOCK_ID, $cache))
		{
			$cache[$IBLOCK_ID] = array();
			if($IBLOCK_ID > 0)
			{
				$arSelect = array(
					"ID",
					"NAME",
					"IN_SECTIONS",
					"IBLOCK_SECTION_ID",
				);
				$arFilter = array (
					"IBLOCK_ID"=> $IBLOCK_ID,
					//"ACTIVE" => "Y",
					"CHECK_PERMISSIONS" => "Y",
				);
				$arOrder = array(
					"NAME" => "ASC",
					"ID" => "ASC",
				);
				$rsItems = CIBlockElement::GetList($arOrder, $arFilter, false, false, $arSelect);
				while($arItem = $rsItems->GetNext())
					$cache[$IBLOCK_ID][] = $arItem;
			}
		}
		return $cache[$IBLOCK_ID];
	}

	function GetSections($IBLOCK_ID)
	{
		static $cache = array();
		$IBLOCK_ID = intval($IBLOCK_ID);

		if(!array_key_exists($IBLOCK_ID, $cache))
		{
			$cache[$IBLOCK_ID] = array();
			if($IBLOCK_ID > 0)
			{
				$arSelect = array(
					"ID",
					"NAME",
					"DEPTH_LEVEL",
				);
				$arFilter = array (
					"IBLOCK_ID"=> $IBLOCK_ID,
					//"ACTIVE" => "Y",
					"CHECK_PERMISSIONS" => "Y",
				);
				$arOrder = array(
					"LEFT_MARGIN" => "ASC",
				);
				$rsItems = CIBlockSection::GetList($arOrder, $arFilter, false, $arSelect);
				while($arItem = $rsItems->GetNext())
					$cache[$IBLOCK_ID][$arItem["ID"]] = $arItem;
			}
		}
		return $cache[$IBLOCK_ID];
	}
}
?>
