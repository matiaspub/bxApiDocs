<?php

IncludeModuleLangFile(__FILE__);

class CUserTypeCrmStatus extends CUserTypeString
{
	function GetUserTypeDescription()
	{
		return array(
			'USER_TYPE_ID' => 'crm_status',
			'CLASS_NAME' => 'CUserTypeCrmStatus',
			'DESCRIPTION' => GetMessage('USER_TYPE_CRM_STATUS_DESCRIPTION'),
			'BASE_TYPE' => 'string',
		);
	}

	function PrepareSettings($arUserField)
	{
		CModule::IncludeModule('crm');

		$arEntityTypes = CCrmStatus::GetEntityTypes();
		$entityType = $arUserField['SETTINGS']['ENTITY_TYPE'];

		return array(
			'ENTITY_TYPE' =>  (isset($arEntityTypes[$entityType])? $entityType: array_shift($arEntityTypes)),
		);
	}

	function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm)
	{
		$result = '';

		if($bVarsFromForm)
			$value = htmlspecialcharsbx($GLOBALS[$arHtmlControl['NAME']]['ENTITY_TYPE']);
		elseif(is_array($arUserField))
			$value = htmlspecialcharsbx($arUserField['SETTINGS']['ENTITY_TYPE']);
		else
			$value = '';

		$ar = CCrmStatus::GetEntityTypes();
		foreach ($ar as $entityType)
		{
			$arr['reference'][] = $entityType['NAME'];
			$arr['reference_id'][] = $entityType['ID'];
		}

		$result .= '
		<tr>
			<td>'.GetMessage('USER_TYPE_CRM_ENTITY_TYPE').':</td>
			<td>
				'.SelectBoxFromArray($arHtmlControl["NAME"].'[ENTITY_TYPE]', $arr, $value).'
			</td>
		</tr>
		';
		return $result;
	}

	function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		$ar = CCrmStatus::GetStatusList($arUserField['SETTINGS']['ENTITY_TYPE']);
		$arr = $arUserField['MANDATORY'] == 'N'? Array('reference' => array(''), 'reference_id' => array('')): Array();
		foreach ($ar as $key => $name)
		{
			$arr['reference'][] = $name;
			$arr['reference_id'][] = $key;
		}
		return SelectBoxFromArray($arHtmlControl['NAME'], $arr, $arHtmlControl['VALUE']);
	}

	function GetFilterHTML($arUserField, $arHtmlControl)
	{
		$ar = CCrmStatus::GetStatusList($arUserField['SETTINGS']['ENTITY_TYPE']);
		foreach ($ar as $key => $name)
		{
			$arr['reference'][] = $name;
			$arr['reference_id'][] = $key;
		}
		return SelectBoxFromArray($arHtmlControl['NAME'], $arr, $arHtmlControl['VALUE']);
	}

	function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		$ar = CCrmStatus::GetStatusList($arUserField['SETTINGS']['ENTITY_TYPE']);
		return isset($ar[$arHtmlControl['VALUE']])? $ar[$arHtmlControl['VALUE']]: '&nbsp;';
	}

	function GetAdminListEditHTML($arUserField, $arHtmlControl)
	{
		$ar = CCrmStatus::GetStatusList($arUserField['SETTINGS']['ENTITY_TYPE']);
		foreach ($ar as $key => $name)
		{
			$arr['reference'][] = $name;
			$arr['reference_id'][] = $key;
		}
		return SelectBoxFromArray($arHtmlControl['NAME'], $arr, $arHtmlControl['VALUE']);
	}

	function CheckFields($arUserField, $value)
	{
		$aMsg = array();
		return $aMsg;
	}

	function GetList($arUserField)
	{
		$rsStatus = false;
		if(CModule::IncludeModule('crm'))
		{
			$arList = Array();
			$arStatuses = CCrmStatus::GetStatus($arUserField['SETTINGS']['ENTITY_TYPE']);
			foreach($arStatuses as $arStatus)
			{
				$arList[] = array('ID' => $arStatus['STATUS_ID'], 'VALUE' => $arStatus['NAME']);
			}
			$rsStatus = new CDBResult();
			$rsStatus->InitFromArray($arList);
		}
		return $rsStatus;
	}

	function OnSearchIndex($arUserField)
	{
		if(is_array($arUserField['VALUE']))
			return implode("\r\n", $arUserField['VALUE']);
		else
			return $arUserField['VALUE'];
	}
}
?>