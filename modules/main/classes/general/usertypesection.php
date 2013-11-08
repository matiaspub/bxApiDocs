<?
IncludeModuleLangFile(__FILE__);

class CUserTypeIBlockSection extends CUserTypeEnum
{
	public static function GetUserTypeDescription()
	{
		return array(
			"USER_TYPE_ID" => "iblock_section",
			"CLASS_NAME" => "CUserTypeIBlockSection",
			"DESCRIPTION" => GetMessage("USER_TYPE_IBSEC_DESCRIPTION"),
			"BASE_TYPE" => "int",
		);
	}

	public static function PrepareSettings($arUserField)
	{
		$height = intval($arUserField["SETTINGS"]["LIST_HEIGHT"]);
		$disp = $arUserField["SETTINGS"]["DISPLAY"];
		if($disp!="CHECKBOX" && $disp!="LIST")
			$disp = "LIST";
		$iblock_id = intval($arUserField["SETTINGS"]["IBLOCK_ID"]);
		if($iblock_id <= 0)
			$iblock_id = "";
		$section_id = intval($arUserField["SETTINGS"]["DEFAULT_VALUE"]);
		if($section_id <= 0)
			$section_id = "";

		$active_filter = $arUserField["SETTINGS"]["ACTIVE_FILTER"] === "Y"? "Y": "N";

		return array(
			"DISPLAY" => $disp,
			"LIST_HEIGHT" => ($height < 1? 1: $height),
			"IBLOCK_ID" => $iblock_id,
			"DEFAULT_VALUE" => $section_id,
			"ACTIVE_FILTER" => $active_filter,
		);
	}

	public static function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm)
	{
		$result = '';

		if($bVarsFromForm)
			$iblock_id = $GLOBALS[$arHtmlControl["NAME"]]["IBLOCK_ID"];
		elseif(is_array($arUserField))
			$iblock_id = $arUserField["SETTINGS"]["IBLOCK_ID"];
		else
			$iblock_id = "";
		if(CModule::IncludeModule('iblock'))
		{
			$result .= '
			<tr>
				<td>'.GetMessage("USER_TYPE_IBSEC_DISPLAY").':</td>
				<td>
					'.GetIBlockDropDownList($iblock_id, $arHtmlControl["NAME"].'[IBLOCK_TYPE_ID]', $arHtmlControl["NAME"].'[IBLOCK_ID]', false, 'class="adm-detail-iblock-types"', 'class="adm-detail-iblock-list"').'
				</td>
			</tr>
			';
		}
		else
		{
			$result .= '
			<tr>
				<td>'.GetMessage("USER_TYPE_IBSEC_DISPLAY").':</td>
				<td>
					<input type="text" size="6" name="'.$arHtmlControl["NAME"].'[IBLOCK_ID]" value="'.htmlspecialcharsbx($value).'">
				</td>
			</tr>
			';
		}

		if($bVarsFromForm)
			$ACTIVE_FILTER = $GLOBALS[$arHtmlControl["NAME"]]["ACTIVE_FILTER"] === "Y"? "Y": "N";
		elseif(is_array($arUserField))
			$ACTIVE_FILTER = $arUserField["SETTINGS"]["ACTIVE_FILTER"] === "Y"? "Y": "N";
		else
			$ACTIVE_FILTER = "N";

		if($bVarsFromForm)
			$value = $GLOBALS[$arHtmlControl["NAME"]]["DEFAULT_VALUE"];
		elseif(is_array($arUserField))
			$value = $arUserField["SETTINGS"]["DEFAULT_VALUE"];
		else
			$value = "";
		if(($iblock_id > 0) && CModule::IncludeModule('iblock'))
		{
			$result .= '
			<tr>
				<td>'.GetMessage("USER_TYPE_IBSEC_DEFAULT_VALUE").':</td>
				<td>
					<select name="'.$arHtmlControl["NAME"].'[DEFAULT_VALUE]" size="5">
						<option value="">'.GetMessage("IBLOCK_VALUE_ANY").'</option>
			';

			$arFilter = Array("IBLOCK_ID"=>$iblock_id);
			if($ACTIVE_FILTER === "Y")
				$arFilter["GLOBAL_ACTIVE"] = "Y";

			$rsSections = CIBlockSection::GetList(
				Array("left_margin"=>"asc"),
				$arFilter,
				false,
				array("ID", "DEPTH_LEVEL", "NAME")
			);
			while($arSection = $rsSections->GetNext())
				$result .= '<option value="'.$arSection["ID"].'"'.($arSection["ID"]==$value? " selected": "").'>'.str_repeat("&nbsp;.&nbsp;", $arSection["DEPTH_LEVEL"]).$arSection["NAME"].'</option>';

			$result .= '</select>';
		}
		else
		{
			$result .= '
			<tr>
				<td>'.GetMessage("USER_TYPE_IBSEC_DEFAULT_VALUE").':</td>
				<td>
					<input type="text" size="8" name="'.$arHtmlControl["NAME"].'[DEFAULT_VALUE]" value="'.htmlspecialcharsbx($value).'">
				</td>
			</tr>
			';
		}

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
				<label><input type="radio" name="'.$arHtmlControl["NAME"].'[DISPLAY]" value="LIST" '.("LIST"==$value? 'checked="checked"': '').'>'.GetMessage("USER_TYPE_IBSEC_LIST").'</label><br>
				<label><input type="radio" name="'.$arHtmlControl["NAME"].'[DISPLAY]" value="CHECKBOX" '.("CHECKBOX"==$value? 'checked="checked"': '').'>'.GetMessage("USER_TYPE_IBSEC_CHECKBOX").'</label><br>
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
			<td>'.GetMessage("USER_TYPE_IBSEC_LIST_HEIGHT").':</td>
			<td>
				<input type="text" name="'.$arHtmlControl["NAME"].'[LIST_HEIGHT]" size="10" value="'.$value.'">
			</td>
		</tr>
		';

		$result .= '
		<tr>
			<td>'.GetMessage("USER_TYPE_IBSEC_ACTIVE_FILTER").':</td>
			<td>
				<input type="checkbox" name="'.$arHtmlControl["NAME"].'[ACTIVE_FILTER]" value="Y" '.($ACTIVE_FILTER=="Y"? 'checked="checked"': '').'>
			</td>
		</tr>
		';

		return $result;
	}

	public static function CheckFields($arUserField, $value)
	{
		$aMsg = array();
		return $aMsg;
	}

	public static function GetList($arUserField)
	{
		$rsSection = false;
		if(CModule::IncludeModule('iblock'))
		{
			$obSection = new CIBlockSectionEnum;
			$rsSection = $obSection->GetTreeList($arUserField["SETTINGS"]["IBLOCK_ID"], $arUserField["SETTINGS"]["ACTIVE_FILTER"]);
		}
		return $rsSection;
	}

	public static function OnSearchIndex($arUserField)
	{
		$res = '';

		if(is_array($arUserField["VALUE"]))
			$val = $arUserField["VALUE"];
		else
			$val = array($arUserField["VALUE"]);

		$val = array_filter($val, "strlen");
		if(count($val) && CModule::IncludeModule('iblock'))
		{
			$ob = new CIBlockSection;
			$rs = $ob->GetList(array("left_margin" => "asc"), array(
				"=ID" => $val
			), false, array("NAME"));

			while($ar = $rs->Fetch())
				$res .= $ar["NAME"]."\r\n";
		}

		return $res;
	}
}

class CIBlockSectionEnum extends CDBResult
{
	public static function GetTreeList($IBLOCK_ID, $ACTIVE_FILTER="N")
	{
		$rs = false;
		if(CModule::IncludeModule('iblock'))
		{
			$arFilter = Array("IBLOCK_ID"=>$IBLOCK_ID);
			if($ACTIVE_FILTER === "Y")
				$arFilter["GLOBAL_ACTIVE"] = "Y";

			$rs = CIBlockSection::GetList(
				Array("left_margin"=>"asc"),
				$arFilter,
				false,
				array("ID", "DEPTH_LEVEL", "NAME", "SORT", "XML_ID", "ACTIVE", "IBLOCK_SECTION_ID")
			);
			if($rs)
			{
				$rs = new CIBlockSectionEnum($rs);
			}
		}
		return $rs;
	}

	public static function GetNext($bTextHtmlAuto=true, $use_tilda=true)
	{
		$r = parent::GetNext($bTextHtmlAuto, $use_tilda);
		if($r)
			$r["VALUE"] = str_repeat(" . ", $r["DEPTH_LEVEL"]).$r["NAME"];
		return $r;
	}
}
?>