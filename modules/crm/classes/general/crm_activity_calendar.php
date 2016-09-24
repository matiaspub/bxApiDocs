<?php

IncludeModuleLangFile(__FILE__);

class CCrmActivityCalendar
{
	function __construct()
	{
	}

	static public function GetList($arOrder = Array('CREATED_DATE' => 'DESC'), $arFilter = Array(), $arSelect = Array(), $nPageTop = false)
	{
        // Fix for #27449
        if (!CModule::IncludeModule('calendar'))
        {
            $obRes = new CDBResult();
            $obRes->InitFromArray(array());
            return $obRes;
        }

		global $USER;
		$ENTITY_ID = 'CALENDAR_EVENT';
		$arElement = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields($ENTITY_ID, 0, LANGUAGE_ID);

		if ($arElement == false || !isset($arElement['UF_CRM_CAL_EVENT']))
		{
			$arFields = array();
			$arFields['ENTITY_ID'] = $ENTITY_ID;
			$arFields['FIELD_NAME'] = 'UF_CRM_CAL_EVENT';
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
			$arFilter['UF_CRM_CAL_EVENT'] = $arFilter['ENTITY_TYPE'].'_'.$arFilter['ENTITY_ID'];
			unset($arFilter['ENTITY_TYPE'], $arFilter['ENTITY_ID']);
		}
		else if (!empty($arFilter['ENTITY_TYPE']))
		{
			$arFilter['ENTITY_TYPE'] = CUserTypeCrm::GetShortEntityType($arFilter['ENTITY_TYPE']);
			$arFilter['%UF_CRM_CAL_EVENT'] = $arFilter['ENTITY_TYPE'].'_';
			unset($arFilter['ENTITY_TYPE']);
		}
		else
			$arFilter['!=UF_CRM_CAL_EVENT'] = '';

		$arFilter['CAL_TYPE'] = 'user';
		$arFilter['DELETED'] = 'N';
		if (isset($arFilter['OWNER_ID']) && is_array($arFilter['OWNER_ID']))
			$arFilter['OWNER_ID'] = current($arFilter['OWNER_ID']);

		$arCal = CCalendarEvent::GetList(
			array(
					'arFilter' => $arFilter,
					'parseRecursion' => false,
					'userId' => $USER->GetID(),
					'fetchAttendees' => false,
					'fetchMeetings' => true
				)
		);

		$obRes = new CDBResult();
		$obRes->InitFromArray($arCal);
		return $obRes;
	}

	static public function GetEntityDataByCalRel($sCalRel)
	{
		$sCalRel = trim($sCalRel);
		$_arData = explode('_', $sCalRel);

		$arData = array(
			'ID' => $_arData[1],
			'SHORT_TYPE' => $_arData[0],
			'TYPE' => CUserTypeCrm::GetLongEntityType($_arData[0])
		);

		return $arData;
	}
}

?>