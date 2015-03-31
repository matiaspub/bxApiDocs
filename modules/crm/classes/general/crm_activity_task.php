<?php

IncludeModuleLangFile(__FILE__);

class CCrmActivityTask
{      
	function __construct() 
	{	
	}
	
	static public function GetList($arOrder = Array('CREATED_DATE' => 'DESC'), $arFilter = Array(), $arSelect = Array(), $nPageTop = false) 
	{		
		$ENTITY_ID = 'TASKS_TASK';
		$arElement = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields($ENTITY_ID, 0, LANGUAGE_ID);    

		if ($arElement == false || !isset($arElement['UF_CRM_TASK']))
		{		
			$arFields = array();
			$arFields['ENTITY_ID'] = $ENTITY_ID;
			$arFields['FIELD_NAME'] = 'UF_CRM_TASK';
			$arFields['USER_TYPE_ID'] = 'crm';
			$arFields['EDIT_FORM_LABEL'][LANGUAGE_ID] = GetMessage('CRM_UF_NAME');
			$arFields['LIST_COLUMN_LABEL'][LANGUAGE_ID] = GetMessage('CRM_UF_NAME');
			$arFields['LIST_FILTER_LABEL'][LANGUAGE_ID] = GetMessage('CRM_UF_NAME');
			$arFields['SETTINGS']['LEAD'] = 'Y';
			$arFields['SETTINGS']['CONTACT'] = 'Y';
			$arFields['SETTINGS']['COMPANY'] = 'Y';
			$arFields['SETTINGS']['DEAL'] = 'Y';
			$arFields['MULTIPLE'] = 'Y';
			$CAllUserTypeEntity = new CUserTypeEntity();
			$CAllUserTypeEntity->Add($arFields);		
		}		
				
		if (isset($arFilter['ENTITY_TYPE']) && isset($arFilter['ENTITY_ID']))
		{
			$arFilter['ENTITY_TYPE'] = CUserTypeCrm::GetShortEntityType($arFilter['ENTITY_TYPE']);
			$arFilter['UF_CRM_TASK'] = $arFilter['ENTITY_TYPE'].'_'.$arFilter['ENTITY_ID'];
			unset($arFilter['ENTITY_TYPE'], $arFilter['ENTITY_ID']);			
		} 
		else if (isset($arFilter['ENTITY_TYPE']))
		{

			if(!empty($arFilter['ENTITY_TYPE']))
			{
				$arFilter['ENTITY_TYPE'] = CUserTypeCrm::GetShortEntityType($arFilter['ENTITY_TYPE']);
				$arFilter['%UF_CRM_TASK'] = $arFilter['ENTITY_TYPE'].'_';
			}
			else
			{
				$arFilter['!=UF_CRM_TASK'] = '';
			}
			unset($arFilter['ENTITY_TYPE']);			
		}
		else
		{
			$arFilter['!=UF_CRM_TASK'] = '';
		}

		if (isset($arFilter['TITLE'])) 
		{
			$arFilter['%TITLE'] = $arFilter['TITLE'];
			unset($arFilter['TITLE']);
		}
		if (isset($arFilter['REAL_STATUS']))
		{
			$arFilter['STATUS'] = $arFilter['REAL_STATUS'];
			unset($arFilter['REAL_STATUS']);			
		}
		if (isset($arOrder['ID']) || isset($arOrder['id']))
			$arSelect[] = 'ID';
		if (in_array('RESPONSIBLE_ID', $arSelect))
		{
			$arSelect[] = 'RESPONSIBLE_NAME';
			$arSelect[] = 'RESPONSIBLE_LAST_NAME';
			$arSelect[] = 'RESPONSIBLE_SECOND_NAME';
			$arSelect[] = 'RESPONSIBLE_LOGIN';
		}
			
		$obRes = CTasks::GetList($arOrder, $arFilter, $arSelect, $nPageTop);
		return $obRes;
	}
		
	static public function GetEntityDataByTaskRel($sTaskRel)
	{
		$sTaskRel = trim($sTaskRel);
		$_arData = explode('_', $sTaskRel);
		
		$arData = array(
			'ID' => $_arData[1],
			'SHORT_TYPE' => $_arData[0],
			'TYPE' => CUserTypeCrm::GetLongEntityType($_arData[0])
		);
		
		return $arData;
	}		
}

?>
