<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */
IncludeModuleLangFile(__FILE__);

class CUserTypeEnum
{
	public static function GetUserTypeDescription()
	{
		return array(
			"USER_TYPE_ID" => "enumeration",
			"CLASS_NAME" => "CUserTypeEnum",
			"DESCRIPTION" => GetMessage("USER_TYPE_ENUM_DESCRIPTION"),
			"BASE_TYPE" => "enum",
		);
	}

	public static function GetDBColumnType($arUserField)
	{
		global $DB;
		switch(strtolower($DB->type))
		{
			case "mysql":
				return "int(18)";
			case "oracle":
				return "number(18)";
			case "mssql":
				return "int";
		}
		return "int";
	}

	public static function PrepareSettings($arUserField)
	{
		$height = intval($arUserField["SETTINGS"]["LIST_HEIGHT"]);
		$disp = $arUserField["SETTINGS"]["DISPLAY"];
		$caption_no_value = trim($arUserField["SETTINGS"]["CAPTION_NO_VALUE"]);

		if($disp!="CHECKBOX" && $disp!="LIST")
			$disp = "LIST";
		return array(
			"DISPLAY" => $disp,
			"LIST_HEIGHT" => ($height < 1? 1: $height),
			"CAPTION_NO_VALUE" => $caption_no_value // no default value - only in output
		);
	}

	public static function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm)
	{
		$result = '';
		if($bVarsFromForm)
			$value = $GLOBALS[$arHtmlControl["NAME"]]["DISPLAY"];
		elseif(is_array($arUserField))
			$value = $arUserField["SETTINGS"]["DISPLAY"];
		else
			$value = "LIST";
		$result .= '
		<tr>
			<td class="adm-detail-valign-top">'.GetMessage("USER_TYPE_ENUM_DISPLAY").':</td>
			<td>
				<label><input type="radio" name="'.$arHtmlControl["NAME"].'[DISPLAY]" value="LIST" '.("LIST"==$value? 'checked="checked"': '').'>'.GetMessage("USER_TYPE_ENUM_LIST").'</label><br>
				<label><input type="radio" name="'.$arHtmlControl["NAME"].'[DISPLAY]" value="CHECKBOX" '.("CHECKBOX"==$value? 'checked="checked"': '').'>'.GetMessage("USER_TYPE_ENUM_CHECKBOX").'</label><br>
			</td>
		</tr>
		';
		if($bVarsFromForm)
			$value = intval($GLOBALS[$arHtmlControl["NAME"]]["LIST_HEIGHT"]);
		elseif(is_array($arUserField))
			$value = intval($arUserField["SETTINGS"]["LIST_HEIGHT"]);
		else
			$value = 5;
		$result .= '
		<tr>
			<td>'.GetMessage("USER_TYPE_ENUM_LIST_HEIGHT").':</td>
			<td>
				<input type="text" name="'.$arHtmlControl["NAME"].'[LIST_HEIGHT]" size="10" value="'.$value.'">
			</td>
		</tr>
		';

		if($bVarsFromForm)
			$value = trim($GLOBALS[$arHtmlControl["NAME"]]["CAPTION_NO_VALUE"]);
		elseif(is_array($arUserField))
			$value = trim($arUserField["SETTINGS"]["CAPTION_NO_VALUE"]);
		else
			$value = '';
		$result .= '
		<tr>
			<td>'.GetMessage("USER_TYPE_ENUM_CAPTION_NO_VALUE").':</td>
			<td>
				<input type="text" name="'.$arHtmlControl["NAME"].'[CAPTION_NO_VALUE]" size="10" value="'.$value.'">
			</td>
		</tr>
		';

		return $result;
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		if(($arUserField["ENTITY_VALUE_ID"]<1) && strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"])>0)
			$arHtmlControl["VALUE"] = intval($arUserField["SETTINGS"]["DEFAULT_VALUE"]);

		$result = '';
		$rsEnum = call_user_func_array(
			array($arUserField["USER_TYPE"]["CLASS_NAME"], "getlist"),
			array(
				$arUserField,
			)
		);
		if(!$rsEnum)
			return '';

		if($arUserField["SETTINGS"]["DISPLAY"]=="CHECKBOX")
		{
			$bWasSelect = false;
			$result2 = '';
			while($arEnum = $rsEnum->GetNext())
			{
				$bSelected = (
					($arHtmlControl["VALUE"]==$arEnum["ID"]) ||
					($arUserField["ENTITY_VALUE_ID"]<=0 && $arEnum["DEF"]=="Y")
				);
				$bWasSelect = $bWasSelect || $bSelected;
				$result2 .= '<label><input type="radio" value="'.$arEnum["ID"].'" name="'.$arHtmlControl["NAME"].'"'.($bSelected? ' checked': '').($arUserField["EDIT_IN_LIST"]!="Y"? ' disabled="disabled" ': '').'>'.$arEnum["VALUE"].'</label><br>';
			}
			if($arUserField["MANDATORY"]!="Y")
				$result .= '<label><input type="radio" value="" name="'.$arHtmlControl["NAME"].'"'.(!$bWasSelect? ' checked': '').($arUserField["EDIT_IN_LIST"]!="Y"? ' disabled="disabled" ': '').'>'.htmlspecialcharsbx(strlen($arUserField["SETTINGS"]["CAPTION_NO_VALUE"]) > 0 ? $arUserField["SETTINGS"]["CAPTION_NO_VALUE"] : GetMessage('MAIN_NO')).'</label><br>';
			$result .= $result2;
		}
		else
		{
			$bWasSelect = false;
			$result2 = '';
			while($arEnum = $rsEnum->GetNext())
			{
				$bSelected = (
					($arHtmlControl["VALUE"]==$arEnum["ID"]) ||
					($arUserField["ENTITY_VALUE_ID"]<=0 && $arEnum["DEF"]=="Y")
				);
				$bWasSelect = $bWasSelect || $bSelected;
				$result2 .= '<option value="'.$arEnum["ID"].'"'.($bSelected? ' selected': '').'>'.$arEnum["VALUE"].'</option>';
			}

			if($arUserField["SETTINGS"]["LIST_HEIGHT"] > 1)
			{
				$size = ' size="'.$arUserField["SETTINGS"]["LIST_HEIGHT"].'"';
			}
			else
			{
				$arHtmlControl["VALIGN"] = "middle";
				$size = '';
			}

			$result = '<select name="'.$arHtmlControl["NAME"].'"'.$size.($arUserField["EDIT_IN_LIST"]!="Y"? ' disabled="disabled" ': '').'>';
			if($arUserField["MANDATORY"]!="Y")
			{
				$result .= '<option value=""'.(!$bWasSelect? ' selected': '').'>'.htmlspecialcharsbx(strlen($arUserField["SETTINGS"]["CAPTION_NO_VALUE"]) > 0 ? $arUserField["SETTINGS"]["CAPTION_NO_VALUE"] : GetMessage('MAIN_NO')).'</option>';
			}
			$result .= $result2;
			$result .= '</select>';
		}
		return $result;
	}

	public static function GetEditFormHTMLMulty($arUserField, $arHtmlControl)
	{
		if(($arUserField["ENTITY_VALUE_ID"]<1) && strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"])>0)
			$arHtmlControl["VALUE"] = array(intval($arUserField["SETTINGS"]["DEFAULT_VALUE"]));
		elseif(!is_array($arHtmlControl["VALUE"]))
			$arHtmlControl["VALUE"] = array();

		$rsEnum = call_user_func_array(
			array($arUserField["USER_TYPE"]["CLASS_NAME"], "getlist"),
			array(
				$arUserField,
			)
		);
		if(!$rsEnum)
			return '';

		$result = '';

		if($arUserField["SETTINGS"]["DISPLAY"]=="CHECKBOX")
		{
			$result .= '<input type="hidden" value="" name="'.$arHtmlControl["NAME"].'">';
			$bWasSelect = false;
			while($arEnum = $rsEnum->GetNext())
			{
				$bSelected = (
					(in_array($arEnum["ID"], $arHtmlControl["VALUE"])) ||
					($arUserField["ENTITY_VALUE_ID"]<=0 && $arEnum["DEF"]=="Y")
				);
				$bWasSelect = $bWasSelect || $bSelected;
				$result .= '<label><input type="checkbox" value="'.$arEnum["ID"].'" name="'.$arHtmlControl["NAME"].'"'.($bSelected? ' checked': '').($arUserField["EDIT_IN_LIST"]!="Y"? ' disabled="disabled" ': '').'>'.$arEnum["VALUE"].'</label><br>';
			}
		}
		else
		{
			$result = '<select multiple name="'.$arHtmlControl["NAME"].'" size="'.$arUserField["SETTINGS"]["LIST_HEIGHT"].'"'.($arUserField["EDIT_IN_LIST"]!="Y"? ' disabled="disabled" ': ''). '>';

			$result .= '<option value=""'.(!$arHtmlControl["VALUE"]? ' selected': '').'>'.htmlspecialcharsbx(strlen($arUserField["SETTINGS"]["CAPTION_NO_VALUE"]) > 0 ? $arUserField["SETTINGS"]["CAPTION_NO_VALUE"] : GetMessage('MAIN_NO')).'</option>';
			while($arEnum = $rsEnum->GetNext())
			{
				$bSelected = (
					(in_array($arEnum["ID"], $arHtmlControl["VALUE"])) ||
					($arUserField["ENTITY_VALUE_ID"]<=0 && $arEnum["DEF"]=="Y")
				);
				$result .= '<option value="'.$arEnum["ID"].'"'.($bSelected? ' selected': '').'>'.$arEnum["VALUE"].'</option>';
			}
			$result .= '</select>';
		}
		return $result;
	}

	public static function GetFilterHTML($arUserField, $arHtmlControl)
	{
		if(!is_array($arHtmlControl["VALUE"]))
			$arHtmlControl["VALUE"] = array();

		$rsEnum = call_user_func_array(
			array($arUserField["USER_TYPE"]["CLASS_NAME"], "getlist"),
			array(
				$arUserField,
			)
		);
		if(!$rsEnum)
			return '';

		if($arUserField["SETTINGS"]["LIST_HEIGHT"] < 5)
			$size = ' size="5"';
		else
			$size = ' size="'.$arUserField["SETTINGS"]["LIST_HEIGHT"].'"';

		$result = '<select multiple name="'.$arHtmlControl["NAME"].'[]"'.$size.'>';
		$result .= '<option value=""'.(!$arHtmlControl["VALUE"]? ' selected': '').'>'.GetMessage("MAIN_ALL").'</option>';
		while($arEnum = $rsEnum->GetNext())
		{
			$result .= '<option value="'.$arEnum["ID"].'"'.(in_array($arEnum["ID"], $arHtmlControl["VALUE"])? ' selected': '').'>'.$arEnum["VALUE"].'</option>';
		}
		$result .= '</select>';
		return $result;
	}

	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		static $cache = array();
		$empty_caption = '&nbsp;';//strlen($arUserField["SETTINGS"]["CAPTION_NO_VALUE"]) > 0 ? htmlspecialcharsbx($arUserField["SETTINGS"]["CAPTION_NO_VALUE"]) : '&nbsp;';

		if(!array_key_exists($arHtmlControl["VALUE"], $cache))
		{
			$rsEnum = call_user_func_array(
				array($arUserField["USER_TYPE"]["CLASS_NAME"], "getlist"),
				array(
					$arUserField,
				)
			);
			if(!$rsEnum)
				return $empty_caption;
			while($arEnum = $rsEnum->GetNext())
				$cache[$arEnum["ID"]] = $arEnum["VALUE"];
		}
		if(!array_key_exists($arHtmlControl["VALUE"], $cache))
			$cache[$arHtmlControl["VALUE"]] = $empty_caption;
		return $cache[$arHtmlControl["VALUE"]];
	}

	public static function GetAdminListEditHTML($arUserField, $arHtmlControl)
	{
		$rsEnum = call_user_func_array(
			array($arUserField["USER_TYPE"]["CLASS_NAME"], "getlist"),
			array(
				$arUserField,
			)
		);
		if(!$rsEnum)
			return '';

		if($arUserField["SETTINGS"]["LIST_HEIGHT"] > 1)
			$size = ' size="'.$arUserField["SETTINGS"]["LIST_HEIGHT"].'"';
		else
			$size = '';

		$result = '<select name="'.$arHtmlControl["NAME"].'"'.$size.($arUserField["EDIT_IN_LIST"]!="Y"? ' disabled="disabled" ': '').'>';
		if($arUserField["MANDATORY"]!="Y")
		{
			$result .= '<option value=""'.(!$arHtmlControl["VALUE"]? ' selected': '').'>'.htmlspecialcharsbx(strlen($arUserField["SETTINGS"]["CAPTION_NO_VALUE"]) > 0 ? $arUserField["SETTINGS"]["CAPTION_NO_VALUE"] : GetMessage('MAIN_NO')).'</option>';
		}
		while($arEnum = $rsEnum->GetNext())
		{
			$result .= '<option value="'.$arEnum["ID"].'"'.($arHtmlControl["VALUE"]==$arEnum["ID"]? ' selected': '').'>'.$arEnum["VALUE"].'</option>';
		}
		$result .= '</select>';
		return $result;
	}

	public static function GetAdminListEditHTMLMulty($arUserField, $arHtmlControl)
	{
		if(!is_array($arHtmlControl["VALUE"]))
			$arHtmlControl["VALUE"] = array();

		$rsEnum = call_user_func_array(
			array($arUserField["USER_TYPE"]["CLASS_NAME"], "getlist"),
			array(
				$arUserField,
			)
		);
		if(!$rsEnum)
			return '';

		$result = '<select multiple name="'.$arHtmlControl["NAME"].'" size="'.$arUserField["SETTINGS"]["LIST_HEIGHT"].'"'.($arUserField["EDIT_IN_LIST"]!="Y"? ' disabled="disabled" ': '').'>';
		if($arUserField["MANDATORY"]!="Y")
		{
			$result .= '<option value=""'.(!$arHtmlControl["VALUE"]? ' selected': '').'>'.htmlspecialcharsbx(strlen($arUserField["SETTINGS"]["CAPTION_NO_VALUE"]) > 0 ? $arUserField["SETTINGS"]["CAPTION_NO_VALUE"] : GetMessage('MAIN_NO')).'</option>';
		}
		while($arEnum = $rsEnum->GetNext())
		{
			$result .= '<option value="'.$arEnum["ID"].'"'.(in_array($arEnum["ID"], $arHtmlControl["VALUE"])? ' selected': '').'>'.$arEnum["VALUE"].'</option>';
		}
		$result .= '</select>';
		return $result;
	}

	public static function CheckFields($arUserField, $value)
	{
		$aMsg = array();
		return $aMsg;
	}

	public static function GetList($arUserField)
	{
		$obEnum = new CUserFieldEnum;
		$rsEnum = $obEnum->GetList(array(), array("USER_FIELD_ID"=>$arUserField["ID"]));
		return $rsEnum;
	}

	public static function OnSearchIndex($arUserField)
	{
		$res = '';

		if(is_array($arUserField["VALUE"]))
			$val = $arUserField["VALUE"];
		else
			$val = array($arUserField["VALUE"]);

		$val = array_filter($val, "strlen");
		if(count($val))
		{
			$ob = new CUserFieldEnum;
			$rs = $ob->GetList(array(), array(
				"USER_FIELD_ID" => $arUserField["ID"],
				"ID" => $val,
			));

			while($ar = $rs->Fetch())
				$res .= $ar["VALUE"]."\r\n";
		}

		return $res;
	}
}
