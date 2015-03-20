<?

IncludeModuleLangFile(__FILE__);

class CUserTypeCrm extends CUserTypeString
{
	function GetUserTypeDescription()
	{
		return array(
			'USER_TYPE_ID' => 'crm',
			'CLASS_NAME' => 'CUserTypeCrm',
			'DESCRIPTION' => GetMessage('USER_TYPE_CRM_DESCRIPTION'),
			'BASE_TYPE' => 'string',
		);
	}

	function PrepareSettings($arUserField)
	{
		CModule::IncludeModule('crm');

		$entityType['LEAD'] = $arUserField['SETTINGS']['LEAD'] == 'Y'? 'Y': 'N';
		$entityType['CONTACT'] = $arUserField['SETTINGS']['CONTACT'] == 'Y'? 'Y': 'N';
		$entityType['COMPANY'] = $arUserField['SETTINGS']['COMPANY'] == 'Y'? 'Y': 'N';
		$entityType['DEAL'] = $arUserField['SETTINGS']['DEAL'] == 'Y'? 'Y': 'N';

		$iEntityType = 0;
		foreach($entityType as $result)
			if ($result == 'Y') $iEntityType++;

		$entityType['LEAD'] = ($iEntityType == 0)? "Y": $entityType['LEAD'];

		return array(
			'LEAD'	 =>  $entityType['LEAD'],
			'CONTACT'=>  $entityType['CONTACT'],
			'COMPANY'=>  $entityType['COMPANY'],
			'DEAL'	 =>  $entityType['DEAL'],
		);
	}

	function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm)
	{
		$result = '';
		$entityTypeLead = 'Y';
		$entityTypeContact = 'Y';
		$entityTypeCompany = 'Y';
		$entityTypeDeal = 'Y';

		if($bVarsFromForm)
		{
			$entityTypeLead = $GLOBALS[$arHtmlControl['NAME']]['LEAD'] == 'Y'? 'Y': 'N';
			$entityTypeContact = $GLOBALS[$arHtmlControl['NAME']]['CONTACT'] == 'Y'? 'Y': 'N';
			$entityTypeCompany = $GLOBALS[$arHtmlControl['NAME']]['COMPANY'] == 'Y'? 'Y': 'N';
			$entityTypeDeal = $GLOBALS[$arHtmlControl['NAME']]['DEAL'] == 'Y'? 'Y': 'N';
		}
		elseif(is_array($arUserField))
		{
			$entityTypeLead = $arUserField['SETTINGS']['LEAD'] == 'Y'? 'Y': 'N';
			$entityTypeContact = $arUserField['SETTINGS']['CONTACT'] == 'Y'? 'Y': 'N';
			$entityTypeCompany = $arUserField['SETTINGS']['COMPANY'] == 'Y'? 'Y': 'N';
			$entityTypeDeal = $arUserField['SETTINGS']['DEAL'] == 'Y'? 'Y': 'N';
		}

		$result .= '
		<tr valign="top">
			<td>'.GetMessage("USER_TYPE_CRM_ENTITY_TYPE").':</td>
			<td>
				<input type="checkbox" name="'.$arHtmlControl["NAME"].'[LEAD]" value="Y" '.($entityTypeLead=="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_LEAD').' <br/>
				<input type="checkbox" name="'.$arHtmlControl["NAME"].'[CONTACT]" value="Y" '.($entityTypeContact=="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_CONTACT').'<br/>
				<input type="checkbox" name="'.$arHtmlControl["NAME"].'[COMPANY]" value="Y" '.($entityTypeCompany=="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_COMPANY').'<br/>
				<input type="checkbox" name="'.$arHtmlControl["NAME"].'[DEAL]" value="Y" '.($entityTypeDeal=="Y"? 'checked="checked"': '').'> '.GetMessage('USER_TYPE_CRM_ENTITY_TYPE_DEAL').'<br/>
			</td>
		</tr>
		';
		return $result;
	}

	function GetEditFormHTML($arUserField, $arHtmlControl)
	{
		return '<div>'.$arHtmlControl['VALUE'].'</div>';
	}

	function GetFilterHTML($arUserField, $arHtmlControl)
	{
		return '<input type="text" '.
			'name="'.$arHtmlControl["NAME"].'" '.
			'size="'.$arUserField["SETTINGS"]["SIZE"].'" '.
			'value="'.$arHtmlControl["VALUE"].'"'.
			'>';
	}

	function GetAdminListViewHTML($arUserField, $arHtmlControl)
	{
		if (strlen($arHtmlControl['VALUE'])>0)
			return $arHtmlControl['VALUE'];
		else
			return '&nbsp;';
	}

	function GetAdminListEditHTML($arUserField, $arHtmlControl)
	{
		return '<div>'.$arHtmlControl['VALUE'].'</div>';
	}

	function CheckFields($arUserField, $value)
	{
		$aMsg = array();

		return $aMsg;
	}

	function CheckPermission($arUserField, $userID = false)
	{
		//permission check is disabled
		if($userID === false)
		{
			return true;
		}

		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$userID = intval($userID);
		$userPerms = $userID > 0 ?
			CCrmPerms::GetUserPermissions($userID) : CCrmPerms::GetCurrentUserPermissions();

		return CCrmPerms::IsAccessEnabled($userPerms);
	}

	function OnSearchIndex($arUserField)
	{
		if(is_array($arUserField['VALUE']))
			return implode("\r\n", $arUserField['VALUE']);
		else
			return $arUserField['VALUE'];
	}

	static function GetShortEntityType($sEntity)
	{
		$sShortEntityType = '';
		switch ($sEntity)
		{
			case 'DEAL': $sShortEntityType = 'D'; break;
			case 'CONTACT': $sShortEntityType = 'C'; break;
			case 'COMPANY': $sShortEntityType = 'CO'; break;
			case 'LEAD':
			default : $sShortEntityType = 'L'; break;
		}
		return $sShortEntityType;
	}

	static function GetLongEntityType($sEntity)
	{
		$sLongEntityType = '';
		switch ($sEntity)
		{
			case 'D': $sLongEntityType = 'DEAL'; break;
			case 'C': $sLongEntityType = 'CONTACT'; break;
			case 'CO': $sLongEntityType = 'COMPANY'; break;
			case 'L':
			default : $sLongEntityType = 'LEAD'; break;
		}
		return $sLongEntityType;
	}
}

?>