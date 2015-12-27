<?php

IncludeModuleLangFile(__FILE__);

class CUserTypeHlblock extends CUserTypeEnum
{
	public static function GetUserTypeDescription()
	{
		return array(
			"USER_TYPE_ID" => "hlblock",
			"CLASS_NAME" => "CUserTypeHlblock",
			"DESCRIPTION" => GetMessage('USER_TYPE_HLEL_DESCRIPTION'),
			"BASE_TYPE" => "int",
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

		if($disp!="CHECKBOX" && $disp!="LIST")
			$disp = "LIST";

		$hlblock_id = intval($arUserField["SETTINGS"]["HLBLOCK_ID"]);

		if($hlblock_id <= 0)
			$hlblock_id = "";

		$hlfield_id = intval($arUserField["SETTINGS"]["HLFIELD_ID"]);

		if($hlfield_id < 0)
			$hlfield_id = "";

		$element_id = intval($arUserField["SETTINGS"]["DEFAULT_VALUE"]);

		return array(
			"DISPLAY" => $disp,
			"LIST_HEIGHT" => ($height < 1? 1: $height),
			"HLBLOCK_ID" => $hlblock_id,
			"HLFIELD_ID" => $hlfield_id,
			"DEFAULT_VALUE" => $element_id,
		);
	}

	public static function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm)
	{
		$result = '';

		if($bVarsFromForm)
			$hlblock_id = $GLOBALS[$arHtmlControl["NAME"]]["HLBLOCK_ID"];
		elseif(is_array($arUserField))
			$hlblock_id = $arUserField["SETTINGS"]["HLBLOCK_ID"];
		else
			$hlblock_id = "";

		if($bVarsFromForm)
			$hlfield_id = $GLOBALS[$arHtmlControl["NAME"]]["HLFIELD_ID"];
		elseif(is_array($arUserField))
			$hlfield_id = $arUserField["SETTINGS"]["HLFIELD_ID"];
		else
			$hlfield_id = "";

		if($bVarsFromForm)
			$value = $GLOBALS[$arHtmlControl["NAME"]]["DEFAULT_VALUE"];
		elseif(is_array($arUserField))
			$value = $arUserField["SETTINGS"]["DEFAULT_VALUE"];
		else
			$value = "";

		if(CModule::IncludeModule('highloadblock'))
		{
			$dropDown = static::getDropDownHtml($hlblock_id, $hlfield_id);

			$result .= '
			<tr>
				<td>'.GetMessage("USER_TYPE_HLEL_DISPLAY").':</td>
				<td>
					'.$dropDown.'
				</td>
			</tr>
			';
		}

		if($hlblock_id > 0 && strlen($hlfield_id) && CModule::IncludeModule('highloadblock'))
		{
			$result .= '
			<tr>
				<td>'.GetMessage("USER_TYPE_HLEL_DEFAULT_VALUE").':</td>
				<td>
					<select name="'.$arHtmlControl["NAME"].'[DEFAULT_VALUE]" size="5">
						<option value="">'.GetMessage("IBLOCK_VALUE_ANY").'</option>
			';

			$rows = static::getHlRows(array('SETTINGS' => array('HLBLOCK_ID' => $hlblock_id, 'HLFIELD_ID' => $hlfield_id)));

			foreach ($rows as $row)
			{
				$result .= '<option value="'.$row["ID"].'" '.($row["ID"]==$value? "selected": "").'>'.htmlspecialcharsbx($row['VALUE']).'</option>';
			}

			$result .= '</select>';
		}
		else
		{
			$result .= '
			<tr>
				<td>'.GetMessage("USER_TYPE_HLEL_DEFAULT_VALUE").':</td>
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
				<label><input type="radio" name="'.$arHtmlControl["NAME"].'[DISPLAY]" value="LIST" '.("LIST"==$value? 'checked="checked"': '').'>'.GetMessage("USER_TYPE_HLEL_LIST").'</label><br>
				<label><input type="radio" name="'.$arHtmlControl["NAME"].'[DISPLAY]" value="CHECKBOX" '.("CHECKBOX"==$value? 'checked="checked"': '').'>'.GetMessage("USER_TYPE_HLEL_CHECKBOX").'</label><br>
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
			<td>'.GetMessage("USER_TYPE_HLEL_LIST_HEIGHT").':</td>
			<td>
				<input type="text" name="'.$arHtmlControl["NAME"].'[LIST_HEIGHT]" size="10" value="'.$value.'">
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
		$rs = false;

		if(CModule::IncludeModule('highloadblock'))
		{
			$rows = static::getHlRows($arUserField);

			$rs = new CDBResult();
			$rs->InitFromArray($rows);

		}

		return $rs;
	}

	public static function getEntityReferences($userfield, \Bitrix\Main\Entity\ScalarField $entityField)
	{
		if ($userfield['SETTINGS']['HLBLOCK_ID'])
		{
			$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($userfield['SETTINGS']['HLBLOCK_ID'])->fetch();

			if ($hlblock)
			{
				$hlentity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);

				return array(
					new \Bitrix\Main\Entity\ReferenceField(
						$entityField->getName().'_REF',
						$hlentity,
						array('=this.'.$entityField->getName() => 'ref.ID')
					)
				);
			}
		}

		return array();
	}

	public static function getHlRows($userfield)
	{
		global $USER_FIELD_MANAGER;

		$rows = array();

		$hlblock_id = $userfield['SETTINGS']['HLBLOCK_ID'];
		$hlfield_id = $userfield['SETTINGS']['HLFIELD_ID'];

		if (!empty($hlblock_id))
		{
			$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($hlblock_id)->fetch();
		}

		if (!empty($hlblock))
		{
			$userfield = null;

			if ($hlfield_id == 0)
			{
				$userfield = array('FIELD_NAME' => 'ID');
			}
			else
			{
				$userfields = $USER_FIELD_MANAGER->GetUserFields('HLBLOCK_'.$hlblock['ID'], 0, LANGUAGE_ID);

				foreach ($userfields as $_userfield)
				{
					if ($_userfield['ID'] == $hlfield_id)
					{
						$userfield = $_userfield;
						break;
					}
				}
			}

			if ($userfield)
			{
				// validated successfully. get data
				$hlDataClass = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock)->getDataClass();
				$rows = $hlDataClass::getList(array(
					'select' => array('ID', $userfield['FIELD_NAME']),
					'order' => 'ID'
				))->fetchAll();

				foreach ($rows as &$row)
				{
					if ($userfield['FIELD_NAME'] == 'ID')
					{
						$row['VALUE'] = $row['ID'];
					}
					else
					{
						$row['VALUE'] = $USER_FIELD_MANAGER->getListView($userfield, $row[$userfield['FIELD_NAME']]);
						$row['VALUE'] .= ' ['.$row['ID'].']';
					}
				}
			}
		}

		return $rows;
	}

	public static function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		static $cache = array();
		$empty_caption = '&nbsp;';

		$cacheKey = $arUserField['SETTINGS']['HLBLOCK_ID'].'_v'.$arHtmlControl["VALUE"];

		if(!array_key_exists($cacheKey, $cache) && !empty($arHtmlControl["VALUE"]))
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
				$cache[$arUserField['SETTINGS']['HLBLOCK_ID'].'_v'.$arEnum["ID"]] = $arEnum["VALUE"];
		}
		if(!array_key_exists($cacheKey, $cache))
			$cache[$cacheKey] = $empty_caption;

		return $cache[$cacheKey];
	}

	public static function getDropDownData()
	{
		global $USER_FIELD_MANAGER;

		$hlblocks = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('order' => 'NAME'))->fetchAll();

		$list = array();

		foreach ($hlblocks as $hlblock)
		{
			// add hlblock itself
			$list[$hlblock['ID']] = array(
				'name' => $hlblock['NAME'],
				'fields' => array(
					0 => 'ID'
				)
			);

			$userfields = $USER_FIELD_MANAGER->GetUserFields('HLBLOCK_'.$hlblock['ID'], 0, LANGUAGE_ID);

			foreach ($userfields as $userfield)
			{
				$fieldTitle = strlen($userfield['LIST_COLUMN_LABEL']) ? $userfield['LIST_COLUMN_LABEL'] : $userfield['FIELD_NAME'];
				$list[$hlblock['ID']]['fields'][(int)$userfield['ID']] = $fieldTitle;
			}
		}

		return $list;
	}

	public static function getDropDownHtml($hlblockId = null, $hlfieldId = null)
	{

		$list = static::getDropDownData();

		// hlblock selector
		$html = '<select name="SETTINGS[HLBLOCK_ID]" onchange="hlChangeFieldOnHlblockChanged(this)">';
		$html .= '<option value="">'.htmlspecialcharsbx(GetMessage('USER_TYPE_HLEL_SEL_HLBLOCK')).'</option>';

		foreach ($list as $_hlblockId => $hlblockData)
		{
			$html .= '<option value="'.$_hlblockId.'" '.($_hlblockId == $hlblockId?'selected':'').'>'.htmlspecialcharsbx($hlblockData['name']).'</option>';
		}

		$html .= '</select> &nbsp; ';

		// field selector
		$html .= '<select name="SETTINGS[HLFIELD_ID]" id="hl_ufsett_field_selector">';
		$html .= '<option value="">'.htmlspecialcharsbx(GetMessage('USER_TYPE_HLEL_SEL_HLBLOCK_FIELD')).'</option>';

		if ($hlblockId)
		{
			if (strlen($hlfieldId))
			{
				$hlfieldId = (int) $hlfieldId;
			}

			foreach ($list[$hlblockId]['fields'] as $fieldId => $fieldName)
			{
				$html .= '<option value="'.$fieldId.'" '.($fieldId === $hlfieldId?'selected':'').'>'.htmlspecialcharsbx($fieldName).'</option>';
			}
		}

		$html .= '</select>';

		// js: changing field selector
		$html .= '
			<script type="text/javascript">
				function hlChangeFieldOnHlblockChanged(hlSelect)
				{
					var list = '.CUtil::PhpToJSObject($list).';
					var fieldSelect = BX("hl_ufsett_field_selector");

					for(var i=fieldSelect.length-1; i >= 0; i--)
						fieldSelect.remove(i);

					var newOption = new Option(\''.GetMessageJS('USER_TYPE_HLEL_SEL_HLBLOCK_FIELD').'\', "", false, false);
					fieldSelect.options.add(newOption);

					if (list[hlSelect.value])
					{
						for(var j in list[hlSelect.value]["fields"])
						{
							var newOption = new Option(list[hlSelect.value]["fields"][j], j, false, false);
							fieldSelect.options.add(newOption);
						}
					}
				}
			</script>
		';

		return $html;
	}
}