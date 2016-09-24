<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main;
use Bitrix\Main\Type;

class CUserTypeDateTime
{
	public static function GetUserTypeDescription()
	{
		return array(
			"USER_TYPE_ID" => "datetime",
			"CLASS_NAME" => "CUserTypeDateTime",
			"DESCRIPTION" => GetMessage("USER_TYPE_DT_DESCRIPTION"),
			"BASE_TYPE" => "datetime",
		);
	}

	public static function GetDBColumnType($arUserField)
	{
		global $DB;
		switch(strtolower($DB->type))
		{
			case "mysql":
				return "datetime";
			case "oracle":
				return "date";
			case "mssql":
				return "datetime";
		}
	}

	public static function PrepareSettings($arUserField)
	{
		$def = $arUserField["SETTINGS"]["DEFAULT_VALUE"];
		if(!is_array($def))
			$def = array("TYPE"=>"NONE","VALUE"=>"");
		else
		{
			if($def["TYPE"]=="FIXED")
				$def["VALUE"] = CDatabase::FormatDate($def["VALUE"], CLang::GetDateFormat("FULL"), "YYYY-MM-DD HH:MI:SS");
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
			$value = str_replace(" 00:00:00", "", CDatabase::FormatDate($arUserField["SETTINGS"]["DEFAULT_VALUE"]["VALUE"], "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL")));
		else
			$value = "";
		$result .= '
		<tr>
			<td class="adm-detail-valign-top">'.GetMessage("USER_TYPE_DT_DEFAULT_VALUE").':</td>
			<td>
				<label><input type="radio" name="'.$arHtmlControl["NAME"].'[DEFAULT_VALUE][TYPE]" value="NONE" '.("NONE"==$type? 'checked="checked"': '').'>'.GetMessage("USER_TYPE_DT_NONE").'</label><br>
				<label><input type="radio" name="'.$arHtmlControl["NAME"].'[DEFAULT_VALUE][TYPE]" value="NOW" '.("NOW"==$type? 'checked="checked"': '').'>'.GetMessage("USER_TYPE_DT_NOW").'</label><br>
				<label><input type="radio" name="'.$arHtmlControl["NAME"].'[DEFAULT_VALUE][TYPE]" value="FIXED" '.("FIXED"==$type? 'checked="checked"': '').'>'.CAdminCalendar::CalendarDate($arHtmlControl["NAME"].'[DEFAULT_VALUE][VALUE]', $value, 20, true).'</label><br>
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
					$arHtmlControl["VALUE"] = ConvertTimeStamp(time()+CTimeZone::GetOffset(), "FULL");
				else
					$arHtmlControl["VALUE"] = str_replace(" 00:00:00", "", CDatabase::FormatDate($arUserField["SETTINGS"]["DEFAULT_VALUE"]["VALUE"], "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL")));
			}
			return CAdminCalendar::CalendarDate($arHtmlControl["NAME"], $arHtmlControl["VALUE"], 20, true);
		}
		elseif(strlen($arHtmlControl["VALUE"])>0)
			return $arHtmlControl["VALUE"];
		else
			return '&nbsp;';
	}

	public static function GetFilterHTML($arUserField, $arHtmlControl)
	{
		return CAdminCalendar::CalendarDate($arHtmlControl["NAME"], $arHtmlControl["VALUE"], 20, true);
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
			return CAdminCalendar::CalendarDate($arHtmlControl["NAME"], $arHtmlControl["VALUE"], 20, true);
		elseif(strlen($arHtmlControl["VALUE"])>0)
			return $arHtmlControl["VALUE"];
		else
			return '&nbsp;';
	}

	public static function CheckFields($arUserField, $value)
	{
		$aMsg = array();
		$value = (string)$value;
		if ($value != "")
		{
			try
			{
				Type\DateTime::createFromUserTime($value);
			}
			catch (Main\ObjectException $e)
			{
				$aMsg[] = array(
					"id" => $arUserField["FIELD_NAME"],
					"text" => GetMessage("USER_TYPE_DT_ERROR",
						array(
							"#FIELD_NAME#"=>($arUserField["EDIT_FORM_LABEL"] <> ''? $arUserField["EDIT_FORM_LABEL"] : $arUserField["FIELD_NAME"]),
						)
					),
				);
			}
		}
		return $aMsg;
	}

	/**
	 * Returns string time in user timezone
	 *
	 * @param $userfield
	 * @param $fetched
	 *
	 * @return string
	 */
	static public function onAfterFetch($userfield, $fetched)
	{
		$value = $fetched['VALUE'];

		if ($userfield['MULTIPLE'] == 'Y' && !($value instanceof Type\DateTime))
		{
			//Invalid value
			if (strlen($value) <= 1)
			{
				//will be ignored by the caller
				return null;
			}

			try
			{
				//try new independent datetime format
				$value = new Type\DateTime($value, \Bitrix\Main\UserFieldTable::MULTIPLE_DATETIME_FORMAT);
			}
			catch (Main\ObjectException $e)
			{
				//try site format
				try
				{
					$value = new Type\DateTime($value);
				}
				catch (Main\ObjectException $e)
				{
					//try short format
					$value = Type\DateTime::createFromUserTime($value);
				}
			}
		}
		elseif (!($value instanceof Type\DateTime))
		{
			// is not necessary, $value is a valid string already
			//$value = Type\DateTime::createFromUserTime($value);
		}

		return (string) $value;
	}


	/**
	 * Returns  time object in server timezone
	 *
	 * @param $userfield
	 * @param $value
	 *
	 * @return Type\DateTime|string
	 */
	static public function onBeforeSave($userfield, $value)
	{
		if (strlen($value) && !($value instanceof Type\DateTime))
		{
			$value = Type\DateTime::createFromUserTime($value);
		}

		return $value;
	}
}
?>