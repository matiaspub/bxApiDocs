<?
IncludeModuleLangFile(__FILE__);

class CIBlockPropertySequence
{
	function AddFilterFields($arProperty, $strHTMLControlName, &$arFilter, &$filtered)
	{
		$from_name = $strHTMLControlName["VALUE"].'_from';
		$from = isset($_REQUEST[$from_name])? $_REQUEST[$from_name]: "";
		if($from)
		{
			$arFilter[">=PROPERTY_".$arProperty["ID"]] = $from;
			$filtered = true;
		}

		$to_name = $strHTMLControlName["VALUE"].'_to';
		$to = isset($_REQUEST[$to_name])? $_REQUEST[$to_name]: "";
		if($to)
		{
			$arFilter["<=PROPERTY_".$arProperty["ID"]] = $to;
			$filtered = true;
		}
	}

	function GetPublicFilterHTML($arProperty, $strHTMLControlName)
	{
		$from_name = $strHTMLControlName["VALUE"].'_from';
		$to_name = $strHTMLControlName["VALUE"].'_to';
		$from = isset($_REQUEST[$from_name])? $_REQUEST[$from_name]: "";
		$to = isset($_REQUEST[$to_name])? $_REQUEST[$to_name]: "";

		return '
			<input name="'.htmlspecialcharsbx($from_name).'" value="'.htmlspecialcharsbx($from).'" size="8" type="text"> ...
			<input name="'.htmlspecialcharsbx($to_name).'" value="'.htmlspecialcharsbx($to).'" size="8" type="text">
		';
	}

	function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		if($value["VALUE"] > 0 && !$strHTMLControlName["COPY"])
		{
			$current_value = intval($value["VALUE"]);
		}
		else
		{
			$seq = new CIBlockSequence($arProperty["IBLOCK_ID"], $arProperty["ID"]);
			$current_value = $seq->GetNext();
		}

		if(is_array($arProperty["USER_TYPE_SETTINGS"]) && $arProperty["USER_TYPE_SETTINGS"]["write"]==="Y")
			return '<input type="text" size="5" name="'.$strHTMLControlName["VALUE"].'" value="'.$current_value.'">';
		else
			return '<input disabled type="text" size="5" name="'.$strHTMLControlName["VALUE"].'" value="'.$current_value.'">'.
				'<input type="hidden" size="5" name="'.$strHTMLControlName["VALUE"].'" value="'.$current_value.'">';
	}

	function PrepareSettings($arProperty)
	{
		//This method not for storing sequence value in the database
		//but it just sets starting value for it
		if(
			is_array($arProperty["USER_TYPE_SETTINGS"])
			&& isset($arProperty["USER_TYPE_SETTINGS"]["current_value"])
			&& intval($arProperty["USER_TYPE_SETTINGS"]["current_value"]) > 0
		)
		{
			$seq = new CIBlockSequence($arProperty["IBLOCK_ID"], $arProperty["ID"]);
			$seq->SetNext($arProperty["USER_TYPE_SETTINGS"]["current_value"]);
		}

		if(is_array($arProperty["USER_TYPE_SETTINGS"]) && $arProperty["USER_TYPE_SETTINGS"]["write"]==="Y")
			$strWritable = "Y";
		else
			$strWritable = "N";

		return array(
			"write" => $strWritable,
		);
	}

	function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$arPropertyFields = array(
			"HIDE" => array("SEARCHABLE", "WITH_DESCRIPTION", "ROW_COUNT", "COL_COUNT", "DEFAULT_VALUE"),
		);

		if(is_array($arProperty["USER_TYPE_SETTINGS"]) && $arProperty["USER_TYPE_SETTINGS"]["write"]==="Y")
			$bWritable = true;
		else
			$bWritable = false;

		$html = '
			<tr valign="top">
				<td>'.GetMessage("IBLOCK_PROP_SEQ_SETTING_WRITABLE").':</td>
				<td><input type="checkbox" name="'.$strHTMLControlName["NAME"].'[write]" value="Y" '.($bWritable? 'checked="checked"': '').'></td>
			</tr>
		';

		if($arProperty["ID"] > 0)
		{
			$seq = new CIBlockSequence($arProperty["IBLOCK_ID"], $arProperty["ID"]);
			$current_value = $seq->GetCurrent();
			return $html.'
			<tr valign="top">
				<td>'.GetMessage("IBLOCK_PROP_SEQ_SETTING_CURRENT_VALUE").':</td>
				<td><input type="text" size="5" name="'.$strHTMLControlName["NAME"].'[current_value]" value="'.$current_value.'"></td>
			</tr>
			';
		}
		else
		{
			$current_value = 1;
			return $html.'
			<tr valign="top">
				<td>'.GetMessage("IBLOCK_PROP_SEQ_SETTING_CURRENT_VALUE").':</td>
				<td><input disabled type="text" size="5" name="'.$strHTMLControlName["NAME"].'[current_value]" value="'.$current_value.'"></td>
			</tr>
			';
		}
	}

}
?>
