<?php
class CCrmBizProcHelper
{
	public static function ResolveDocumentName($ownerTypeID)
	{
		$ownerTypeID = intval($ownerTypeID);

		$docName = '';
		if($ownerTypeID === CCrmOwnerType::Contact)
		{
			$docName = 'CCrmDocumentContact';
		}
		elseif($ownerTypeID === CCrmOwnerType::Company)
		{
			$docName = 'CCrmDocumentCompany';
		}
		elseif($ownerTypeID === CCrmOwnerType::Lead)
		{
			$docName = 'CCrmDocumentLead';
		}
		elseif($ownerTypeID === CCrmOwnerType::Deal)
		{
			$docName = 'CCrmDocumentDeal';
		}

		return $docName;
	}
	public static function AutoStartWorkflows($ownerTypeID, $ownerID, $eventType, &$errors)
	{
		if (!(IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc')))
		{
			return false;
		}

		$ownerTypeID = intval($ownerTypeID);
		$ownerID = intval($ownerID);
		$eventType = intval($eventType);

		$docName = self::ResolveDocumentName($ownerTypeID);
		if($docName === '')
		{
			return false;
		}

		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);
		if($ownerTypeName === '')
		{
			return false;
		}

		CBPDocument::AutoStartWorkflows(
			array('crm', $docName, $ownerTypeName),
			$eventType,
			array('crm', $docName, $ownerTypeName.'_'.$ownerID),
			array(),
			$errors
		);

		return true;
	}

	public static function GetDocumentNames($ownerTypeID, $ownerID)
	{
		if (!(IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc')))
		{
			return false;
		}

		$ownerTypeID = intval($ownerTypeID);
		$ownerID = intval($ownerID);

		$docName = self::ResolveDocumentName($ownerTypeID);
		if($docName === '')
		{
			return array();
		}

		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);
		if($ownerTypeName === '')
		{
			return array();
		}

		/*$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', $docName, $ownerTypeName),
			array('crm', $docName, $ownerTypeName.'_'.$ownerID)
		);*/

		$arDocumentStates = CBPStateService::GetDocumentStates(
			array('crm', $docName, $ownerTypeName.'_'.$ownerID)
		);

		$result = array();
		foreach ($arDocumentStates as $arDocumentState)
		{
			if($arDocumentState['ID'] !== '' && $arDocumentState['TEMPLATE_NAME'] !== '')
			{
				$result[] = $arDocumentState['TEMPLATE_NAME'];
			}
		}

		return $result;
	}

	public static function GetUserWorkflowTaskCount($workflowIDs, $userID = 0)
	{
		if(!is_array($workflowIDs))
		{
			return 0;
		}

		if (!(IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc')))
		{
			return 0;
		}

		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$filter = array('USER_ID' => $userID);
		$workflowQty = count($workflowIDs);
		if($workflowQty > 1)
		{
			//IMPORTANT: will produce SQL error due to CBPTaskService::GetList bug
			//$filter['@WORKFLOW_ID'] = $workflowIDs;
			$filter['WORKFLOW_ID'] = $workflowIDs[0];
		}
		/*elseif($workflowQty === 1)
		{
			$filter['WORKFLOW_ID'] = $workflowIDs[0];
		}*/

		$result = CBPTaskService::GetList(array(), $filter, array(), false, array());
		return is_int($result) ? $result : 0;
	}
}

class CCrmBizProcEventType
{
	const Undefined = 0;
	const Create = 1; //CBPDocumentEventType::Create
	const Edit = 2; //CBPDocumentEventType::Edit
}
