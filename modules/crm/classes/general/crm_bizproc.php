<?php

IncludeModuleLangFile(__FILE__);

class CCrmBizProc
{

	protected $sEntityType = 'LEAD';
	protected $sDocument = 'CCrmDocumentLead';
	public $arCurrentUserGroups = array();
	public $arDocumentStates = array();
	public $LAST_ERROR = '';

	public function __construct($ENTITY_TYPE = 'LEAD')
	{
		global $USER;
		$this->sEntityType = strtoupper($ENTITY_TYPE);
		switch($this->sEntityType)
		{
			case 'DEAL':
				$this->sDocument = 'CCrmDocumentDeal';
				break;
			case 'CONTACT':
				$this->sDocument = 'CCrmDocumentContact';
				break;
			case 'COMPANY':
				$this->sDocument = 'CCrmDocumentCompany';
				break;
			case 'LEAD':
			default:
				$this->sDocument = 'CCrmDocumentLead';
				$this->sEntityType = 'LEAD';
				break;
		}
		if (is_object($USER))
			$this->arCurrentUserGroups = $USER->GetUserGroupArray();
	}

	public function StartWorkflow($ID, $arBizProcParametersValues = false)
	{
		if(!CModule::IncludeModule('bizproc'))
			return true;

		global $USER;
		$arBizProcWorkflowId = array();
		$bresult = true;
		foreach ($this->arDocumentStates as $arDocumentState)
		{
			if (strlen($arDocumentState['ID']) <= 0)
			{
				$arErrorsTmp = array();

				$arBizProcWorkflowId[$arDocumentState['TEMPLATE_ID']] = CBPDocument::StartWorkflow(
					$arDocumentState['TEMPLATE_ID'],
					array('crm', $this->sDocument, $this->sEntityType.'_'.$ID),
					$arBizProcParametersValues[$arDocumentState['TEMPLATE_ID']],
					$arErrorsTmp
				);

				if (count($arErrorsTmp) > 0)
				{
					$this->LAST_ERROR = '';
					foreach ($arErrorsTmp as $e)
						$this->LAST_ERROR .= $e['message'].'<br />';
					$bresult = false;
				}
			}
		}

		if ($bresult)
		{
			$bizprocIndex = (int) $_REQUEST['bizproc_index'];
			if ($bizprocIndex > 0)
			{
				for ($i = 1; $i <= $bizprocIndex; $i++)
				{
					$bpId = trim($_REQUEST['bizproc_id_'.$i]);
					$bpTemplateId = intval($_REQUEST['bizproc_template_id_'.$i]);
					$bpEvent = trim($_REQUEST['bizproc_event_'.$i]);

					if (strlen($bpEvent) > 0)
					{
						if (strlen($bpId) > 0)
						{
							if (!array_key_exists($bpId, $this->arDocumentStates))
								continue;
						}
						else
						{
							if (!array_key_exists($bpTemplateId, $this->arDocumentStates ))
								continue;
							$bpId = $arBizProcWorkflowId[$bpTemplateId];
						}

						$arErrorTmp = array();
						CBPDocument::SendExternalEvent(
							$bpId,
							$bpEvent,
							array('Groups' => $this->arCurrentUserGroups, 'User' => $USER->GetID()),
							$arErrorTmp
						);

						if (count($arErrorsTmp) > 0)
						{
							foreach ($arErrorsTmp as $e)
								$this->LAST_ERROR .= $e['message'].'<br />';
							$bresult = false;
						}
					}
				}
			}
		}

		return $bresult;
	}

	public function Delete($ID, $arEntityAttr)
	{
		if(!CModule::IncludeModule('bizproc'))
			return true;

		$userID = CCrmSecurityHelper::GetCurrentUserID();
		$bDeleteError = !CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::WriteDocument,
			$userID,
			array('crm', $this->sDocument, $this->sEntityType.'_'.$ID),
			array(
				'UserGroups' => $this->arCurrentUserGroups,
				'UserIsAdmin' => CCrmPerms::IsAdmin($userID),
				'CRMEntityAttr' => $arEntityAttr
			)
		);
		if (!$bDeleteError)
		{
			$arErrorsTmp = array();
			CBPDocument::OnDocumentDelete(array('crm', $this->sDocument, $this->sEntityType.'_'.$ID), $arErrorsTmp);
			if (count($arErrorsTmp) > 0)
			{
				$this->LAST_ERROR = '';
				foreach ($arErrorsTmp as $e)
					$this->LAST_ERROR .= $e['message'].'<br />';
				return false;
			}
		}
		return true;
	}

	public function CheckFields($ID = false, $bAutoExec = false, $CreatedBy = 0, $arEntityAttr = array())
	{
		global $USER;

		$this->LAST_ERROR = '';

		if(!CModule::IncludeModule('bizproc'))
			return true;

		$this->arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', $this->sDocument, $this->sEntityType),
			$ID == false ? null : array('crm', $this->sDocument, $this->sEntityType.'_'.$ID)
		);

		$arCurrentUserGroups = $this->arCurrentUserGroups;

		if (is_object($USER))
		{
			if ($ID == false)
			{
				$arCurrentUserGroups[] = 'Author';
				$bCanWrite = CBPDocument::CanUserOperateDocumentType(
					CBPCanUserOperateOperation::WriteDocument,
					$USER->GetID(),
					array('crm', $this->sDocument, $this->sEntityType),
					array(
						'AllUserGroups' => $arCurrentUserGroups,
						'DocumentStates' => $this->arDocumentStates,
						'UserIsAdmin' => $USER->IsAdmin()
					)
				);
			}
			else
			{
				if ($USER->GetID() == $CreatedBy)
					$arCurrentUserGroups[] = 'Author';

				$bCanWrite = CBPDocument::CanUserOperateDocument(
					CBPCanUserOperateOperation::WriteDocument,
					$USER->GetID(),
					array('crm', $this->sDocument, $this->sEntityType.'_'.$ID),
					array(
						'AllUserGroups' => $arCurrentUserGroups,
						'DocumentStates' =>  $this->arDocumentStates,
						'CreatedBy' => $CreatedBy != 0 ? $CreatedBy : 0,
						'UserIsAdmin' => $USER->IsAdmin(),
						'CRMEntityAttr' => $arEntityAttr
					)
				);
			}
		}
		else
		{
			$bCanWrite = true;
		}

		if (!$bCanWrite)
		{
			$this->LAST_ERROR =  GetMessage('CRM_PERMISSION_DENIED');
			return false;
		}

		$arBizProcParametersValues = array();
		foreach ($this->arDocumentStates as $arDocumentState)
		{
			if (strlen($arDocumentState['ID']) <= 0)
			{
				if ($bAutoExec)
				{
					foreach ($arDocumentState['TEMPLATE_PARAMETERS'] as $parameterKey => $arParam)
					{
						if ($arParam['Required'] && !isset($_REQUEST['bizproc'.$arDocumentState['TEMPLATE_ID'].'_'.$parameterKey]) && strlen($arParam['Default']) > 0)
							$_REQUEST['bizproc'.$arDocumentState['TEMPLATE_ID'].'_'.$parameterKey] = $arParam['Default'];
					}
				}

				$arErrorsTmp = array();
				$arBizProcParametersValues[$arDocumentState['TEMPLATE_ID']] = CBPDocument::StartWorkflowParametersValidate(
					$arDocumentState['TEMPLATE_ID'],
					$arDocumentState['TEMPLATE_PARAMETERS'],
					array('crm', $this->sDocument, $ID == false ? $this->sEntityType : $this->sEntityType.'_'.$ID),
					$arErrorsTmp
				);

				if (count($arErrorsTmp) > 0)
				{
					$this->LAST_ERROR = '';
					foreach ($arErrorsTmp as $e)
						$this->LAST_ERROR .= $e['message'].'<br />';
					return false;
				}
			}
		}
		return $arBizProcParametersValues;
	}
}