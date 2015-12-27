<?
IncludeModuleLangFile(__FILE__);

class CUserTypeDouble
{
	public static function GetUserTypeDescription()
	{
		return array(
			"USER_TYPE_ID" => "double",
			"CLASS_NAME" => "CUserTypeDouble",
			"DESCRIPTION" => GetMessage("USER_TYPE_DOUBLE_DESCRIPTION"),
			"BASE_TYPE" => "double",
		);
	}

	public static function GetDBColumnType($arUserField)
	{
		global $DB;
		switch(strtolower($DB->type))
		{
			case "mysql":
				return "double";
			case "oracle":
				return "number";
			case "mssql":
				return "float";
		}
		return null;
	}

	public static function PrepareSettings($arUserField)
	{
		$prec = intval($arUserField["SETTINGS"]["PRECISION"]);
		$size = intval($arUserField["SETTINGS"]["SIZE"]);
		$min = doubleval($arUserField["SETTINGS"]["MIN_VALUE"]);
		$max = doubleval($arUserField["SETTINGS"]["MAX_VALUE"]);

		return array(
			"PRECISION" => ($prec < 0? 0: ($prec > 12? 12: $prec)),
			"SIZE" =>  ($size <= 1? 20: ($size > 255? 225: $size)),
			"MIN_VALUE" => $min,
			"MAX_VALUE" => $max,
			"DEFAULT_VALUE" => strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"])>0? doubleval($arUserField["SETTINGS"]["DEFAULT_VALUE"]): "",
		);
	}

	public static function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm)
	{
		$result = '';
		if($bVarsFromForm)
			$value = intval($GLOBALS[$arHtmlControl["NAME"]]["PRECISION"]);
		elseif(is_array($arUserField))
			$value = intval($arUserField["SETTINGS"]["PRECISION"]);
		else
			$value = 4;
		$result .= '
		<tr>
			<td>'.GetMessage("USER_TYPE_DOUBLE_PRECISION").':</td>
			<td>
				<input type="text" name="'.$arHtmlControl["NAME"].'[PRECISION]" size="20"  maxlength="225" value="'.$value.'">
			</td>
		</tr>
		';
		if($bVarsFromForm)
			$value = doubleval($GLOBALS[$arHtmlControl["NAME"]]["DEFAULT_VALUE"]);
		elseif(is_array($arUserField))
			$value = doubleval($arUserField["SETTINGS"]["DEFAULT_VALUE"]);
		else
			$value = "";
		$result .= '
		<tr>
			<td>'.GetMessage("USER_TYPE_DOUBLE_DEFAULT_VALUE").':</td>
			<td>
				<input type="text" name="'.$arHtmlControl["NAME"].'[DEFAULT_VALUE]" size="20"  maxlength="225" value="'.$value.'">
			</td>
		</tr>
		';
		if($bVarsFromForm)
			$value = intval($GLOBALS[$arHtmlControl["NAME"]]["SIZE"]);
		elseif(is_array($arUserField))
			$value = intval($arUserField["SETTINGS"]["SIZE"]);
		else
			$value = 20;
		$result .= '
		<tr>
			<td>'.GetMessage("USER_TYPE_DOUBLE_SIZE").':</td>
			<td>
				<input type="text" name="'.$arHtmlControl["NAME"].'[SIZE]" size="20"  maxlength="20" value="'.$value.'">
			</td>
		</tr>
		';
		if($bVarsFromForm)
			$value = doubleval($GLOBALS[$arHtmlControl["NAME"]]["MIN_VALUE"]);
		elseif(is_array($arUserField))
			$value = doubleval($arUserField["SETTINGS"]["MIN_VALUE"]);
		else
			$value = 0;
		$result .= '
		<tr>
			<td>'.GetMessage("USER_TYPE_DOUBLE_MIN_VALUE").':</td>
			<td>
				<input type="text" name="'.$arHtmlControl["NAME"].'[MIN_VALUE]" size="20"  maxlength="20" value="'.$value.'">
			</td>
		</tr>
		';
		if($bVarsFromForm)
			$value = doubleval($GLOBALS[$arHtmlControl["NAME"]]["MAX_VALUE"]);
		elseif(is_array($arUserField))
			$value = doubleval($arUserField["SETTINGS"]["MAX_VALUE"]);
		else
			$value = 0;
		$result .= '
		<tr>
			<td>'.GetMessage("USER_TYPE_DOUBLE_MAX_VALUE").':</td>
			<td>
				<input type="text" name="'.$arHtmlControl["NAME"].'[MAX_VALUE]" size="20"  maxlength="20" value="'.$value.'">
			</td>
		</tr>
		';
		return $result;
	}

	public static function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		if($arUserField["ENTITY_VALUE_ID"]<1 && strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"])>0)
			$arHtmlControl["VALUE"] = $arUserField["SETTINGS"]["DEFAULT_VALUE"];
		if(strlen($arHtmlControl["VALUE"])>0)
			$arHtmlControl["VALUE"] = round(doubleval($arHtmlControl["VALUE"]), $arUserField["SETTINGS"]["PRECISION"]);
		$arHtmlControl["VALIGN"] = "middle";
		return '<input type="text" '.
			'name="'.$arHtmlControl["NAME"].'" '.
			'size="'.$arUserField["SETTINGS"]["SIZE"].'" '.
			'value="'.$arHtmlControl["VALUE"].'" '.
			($arUserField["EDIT_IN_LIST"]!="Y"? 'disabled="disabled" ': '').
			'>';
	}

	public static function GetFilterHTML($arUserField, $arHtmlControl)
	{
		if(strlen($arHtmlControl["VALUE"]))
			$value = round(doubleval($arHtmlControl["VALUE"]), $arUserField["SETTINGS"]["PRECISION"]);
		else
			$value = "";

		return '<input type="text" '.
			'name="'.$arHtmlControl["NAME"].'" '.
			'size="'.$arUserField["SETTINGS"]["SIZE"].'" '.
			'value="'.$value.'" '.
			'>';
	}

	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		if(strlen($arHtmlControl["VALUE"])>0)
			return round(doubleval($arHtmlControl["VALUE"]), $arUserField["SETTINGS"]["PRECISION"]);
		else
			return '&nbsp;';
	}

	public static function GetAdminListEditHTML($arUserField, $arHtmlControl)
	{
		return '<input type="text" '.
			'name="'.$arHtmlControl["NAME"].'" '.
			'size="'.$arUserField["SETTINGS"]["SIZE"].'" '.
			'value="'.round(doubleval($arHtmlControl["VALUE"]), $arUserField["SETTINGS"]["PRECISION"]).'" '.
			'>';
	}

	public static function CheckFields($arUserField, $value)
	{
		$aMsg = array();

		$value = str_replace(array(',', ' '), array('.', ''), $value);

		if(strlen($value)>0 && $arUserField["SETTINGS"]["MIN_VALUE"]!=0 && doubleval($value)<$arUserField["SETTINGS"]["MIN_VALUE"])
		{
			$aMsg[] = array(
				"id" => $arUserField["FIELD_NAME"],
				"text" => GetMessage("USER_TYPE_DOUBLE_MIN_VALUE_ERROR",
					array(
						"#FIELD_NAME#"=>$arUserField["EDIT_FORM_LABEL"],
						"#MIN_VALUE#"=>$arUserField["SETTINGS"]["MIN_VALUE"]
					)
				),
			);
		}
		if(strlen($value)>0 && $arUserField["SETTINGS"]["MAX_VALUE"]<>0 && doubleval($value)>$arUserField["SETTINGS"]["MAX_VALUE"])
		{
			$aMsg[] = array(
				"id" => $arUserField["FIELD_NAME"],
				"text" => GetMessage("USER_TYPE_DOUBLE_MAX_VALUE_ERROR",
					array(
						"#FIELD_NAME#"=>$arUserField["EDIT_FORM_LABEL"],
						"#MAX_VALUE#"=>$arUserField["SETTINGS"]["MAX_VALUE"]
					)
				),
			);
		}
		return $aMsg;
	}

	public static function OnSearchIndex($arUserField)
	{
		if(is_array($arUserField["VALUE"]))
			return implode("\r\n", $arUserField["VALUE"]);
		else
			return $arUserField["VALUE"];
	}

	public static function OnBeforeSave($arUserField, $value)
	{
		$value = str_replace(array(',', ' '), array('.', ''), $value);
		if(strlen($value)>0)
		{
			return "".round(doubleval($value), $arUserField["SETTINGS"]["PRECISION"]);
		}
		return null;
	}
}
