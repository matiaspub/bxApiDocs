<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

IncludeModuleLangFile(__FILE__);

use Bitrix\Main;
use Bitrix\Main\Type;

class CUserTypeDate
{
	public static function GetUserTypeDescription()
	{
		return array(
			"USER_TYPE_ID" => "date",
			"CLASS_NAME" => "CUserTypeDate",
			"DESCRIPTION" => GetMessage("USER_TYPE_D_DESCRIPTION"),
			"BASE_TYPE" => "datetime",
		);
	}

	public static function GetDBColumnType()
	{
		return "date";
	}

	public static function PrepareSettings($arUserField)
	{
		$def = $arUserField["SETTINGS"]["DEFAULT_VALUE"];
		if(!is_array($def))
		{
			$def = array("TYPE"=>"NONE","VALUE"=>"");
		}
		else
		{
			if($def["TYPE"]=="FIXED")
				$def["VALUE"] = CDatabase::FormatDate($def["VALUE"], CLang::GetDateFormat("SHORT"), "YYYY-MM-DD");
			elseif($def["TYPE"]=="NOW")
				$def["VALUE"] = "";
			else
				$def = array("TYPE"=>"NONE","VALUE"=>"");
		}
		return array(
			"DEFAULT_VALUE" => array("TYPE"=>$def["TYPE"], "VALUE"=>$def["VALUE"]),
		);
	}

	public static function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm)
	{
		$result = '';
		if($bVarsFromForm)
			$type = $GLOBALS[$arHtmlControl["NAME"]]["DEFAULT_VALUE"]["TYPE"];
		elseif(is_array($arUserField) && is_array($arUserField["SETTINGS"]["DEFAULT_VALUE"]))
			$type = $arUserField["SETTINGS"]["DEFAULT_VALUE"]["TYPE"];
		else
			$type = "NONE";
		if($bVarsFromForm)
			$value = $GLOBALS[$arHtmlControl["NAME"]]["DEFAULT_VALUE"]["VALUE"];
		elseif(is_array($arUserField) && is_array($arUserField["SETTINGS"]["DEFAULT_VALUE"]))
			$value = CDatabase::FormatDate($arUserField["SETTINGS"]["DEFAULT_VALUE"]["VALUE"], "YYYY-MM-DD", CLang::GetDateFormat("SHORT"));
		else
			$value = "";
		$result .= '
		<tr>
			<td class="adm-detail-valign-top">'.GetMessage("USER_TYPE_D_DEFAULT_VALUE").':</td>
			<td>
				<label><input type="radio" name="'.$arHtmlControl["NAME"].'[DEFAULT_VALUE][TYPE]" value="NONE" '.("NONE"==$type? 'checked="checked"': '').'>'.GetMessage("USER_TYPE_D_NONE").'</label><br>
				<label><input type="radio" name="'.$arHtmlControl["NAME"].'[DEFAULT_VALUE][TYPE]" value="NOW" '.("NOW"==$type? 'checked="checked"': '').'>'.GetMessage("USER_TYPE_D_NOW").'</label><br>
				<label><input type="radio" name="'.$arHtmlControl["NAME"].'[DEFAULT_VALUE][TYPE]" value="FIXED" '.("FIXED"==$type? 'checked="checked"': '').'>'.CAdminCalendar::CalendarDate($arHtmlControl["NAME"].'[DEFAULT_VALUE][VALUE]', $value).'</label><br>
			</td>
		</tr>
		';
		return $result;
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		$arHtmlControl["VALIGN"] = "middle";
		if($arUserField["EDIT_IN_LIST"]=="Y")
		{
			if($arUserField["ENTITY_VALUE_ID"]<1 && $arUserField["SETTINGS"]["DEFAULT_VALUE"]["TYPE"]!="NONE")
			{
				if($arUserField["SETTINGS"]["DEFAULT_VALUE"]["TYPE"]=="NOW")
					$arHtmlControl["VALUE"] = ConvertTimeStamp(time(), "SHORT");
				else
					$arHtmlControl["VALUE"] = CDatabase::FormatDate($arUserField["SETTINGS"]["DEFAULT_VALUE"]["VALUE"], "YYYY-MM-DD", CLang::GetDateFormat("SHORT"));
			}
			return CAdminCalendar::CalendarDate($arHtmlControl["NAME"], $arHtmlControl["VALUE"]);
		}
		elseif(strlen($arHtmlControl["VALUE"])>0)
			return $arHtmlControl["VALUE"];
		else
			return '&nbsp;';
	}

	public static function GetFilterHTML($arUserField, $arHtmlControl)
	{
		return CAdminCalendar::CalendarDate($arHtmlControl["NAME"], $arHtmlControl["VALUE"]);
	}

	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		if(strlen($arHtmlControl["VALUE"])>0)
			return $arHtmlControl["VALUE"];
		else
			return '&nbsp;';
	}

	public static function GetAdminListEditHTML($arUserField, $arHtmlControl)
	{
		if($arUserField["EDIT_IN_LIST"]=="Y")
			return CAdminCalendar::CalendarDate($arHtmlControl["NAME"], $arHtmlControl["VALUE"]);
		elseif(strlen($arHtmlControl["VALUE"])>0)
			return $arHtmlControl["VALUE"];
		else
			return '&nbsp;';
	}

	public static function CheckFields($arUserField, $value)
	{
		$aMsg = array();
		if(is_string($value) && !empty($value) && !CheckDateTime($value, FORMAT_DATE))
		{
			$aMsg[] = array(
				"id" => $arUserField["FIELD_NAME"],
				"text" => GetMessage("USER_TYPE_D_ERROR",
					array(
						"#FIELD_NAME#"=>($arUserField["EDIT_FORM_LABEL"] <> ''? $arUserField["EDIT_FORM_LABEL"] : $arUserField["FIELD_NAME"]),
					)
				),
			);
		}
		return $aMsg;
	}

	/**
	 * @param array $userfield
	 * @param array $fetched
	 *
	 * @return string
	 */
	static public function onAfterFetch($userfield, $fetched)
	{
		$value = $fetched['VALUE'];

		if ($userfield['MULTIPLE'] == 'Y' && !($value instanceof Type\Date))
		{
			try
			{
				//try new independent date format
				$value = new Type\Date($value, \Bitrix\Main\UserFieldTable::MULTIPLE_DATE_FORMAT);
			}
			catch (Main\ObjectException $e)
			{
				// try site format (sometimes it can be full site format)
				try
				{
					$value = new Type\Date($value);
				}
				catch (Main\ObjectException $e)
				{
					$value = new Type\Date($value, Type\DateTime::getFormat());
				}
			}
		}

		return (string) $value;
	}

	/**
	 * @param array            $userfield
	 * @param Type\Date|string $value
	 *
	 * @return Type\Date
	 */
	static public function onBeforeSave($userfield, $value)
	{
		if (strlen($value) && !($value instanceof Type\Date))
		{
			// try both site's format - short and full
			try
			{
				$value = new Type\Date($value);
			}
			catch (Main\ObjectException $e)
			{
				$value = new Type\Date($value, Type\DateTime::getFormat());
			}
		}

		return $value;
	}
}
