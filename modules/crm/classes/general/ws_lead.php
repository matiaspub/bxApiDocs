<?php

if (!CModule::IncludeModule('webservice'))
	return;

IncludeModuleLangFile(__FILE__);

class CCrmLeadWS extends IWebService
{
	public static function CheckAuth()
	{
		$CCrmPerms = new CCrmPerms($GLOBALS['USER']->GetID());
		if ($CCrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE))
			return new CSOAPFault('Server Error', 'Unable to authorize user.');
		return false;
	}

	public function Add($data)
	{
		global $DB;
		if (($r = self::CheckAuth()) !== false)
			return $r;

		$arFieldsInfo = CCrmLead::GetFields();

		$arFields = array();
		$arEl = $data->elementsByName('Field');
		foreach ($arEl as $child)
		{
			$children = $child->children();
			$sFieldName = $child->getAttribute('id');

			// Fix for issue #40193
			if(!isset($arFieldsInfo[$sFieldName]))
			{
				continue;
			}

			if (!is_null($children))
			{
				$arFields[$sFieldName] = array();
				foreach ($children as $child)
					$arFields[$sFieldName][]  = $child->content;
			}
			else
				$arFields[$sFieldName]  = $child->content;
		}

		CCrmFieldMulti::PrepareFields($arFields);
		$CCrmUserType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], CCrmLead::$sUFEntityID);
		$CCrmUserType->PrepareImport($arFields, ',');

		$CCrmBizProc = new CCrmBizProc('LEAD');
		if (false === $CCrmBizProc->CheckFields(false, true))
			return new CSoapFault('CCrmLead::Add Bizproc', htmlspecialcharsbx(strip_tags(nl2br($CCrmBizProc->LAST_ERROR))));

		$CCrmLead = new CCrmLead();
		$DB->StartTransaction();
		$ID = $CCrmLead->Add($arFields);
		if ($ID !== false && !$CCrmBizProc->StartWorkflow($ID))
		{
			$DB->Rollback();
			return new CSoapFault('CCrmLead::Add Bizproc', htmlspecialcharsbx(strip_tags(nl2br($CCrmBizProc->LAST_ERROR))));
		}

		if ($ID === false)
		{
			$DB->Rollback();
			return new CSoapFault('CCrmLead::Add', htmlspecialcharsbx(strip_tags(nl2br($arFields['RESULT_MESSAGE']))));
		}

		$DB->Commit();
		return 'ok';
	}

	protected static function GetStatusList()
	{
		$ar = CCrmStatus::GetStatusList('STATUS');
		$CXMLCreatorR = new CXMLCreator('CHOISES');

		foreach ($ar as $key => $value)
		{
			$CXMLCreator = new CXMLCreator('CHOISE', true);
			$CXMLCreator->setAttribute('id', $key);
			$CXMLCreator->setData($value);
			$CXMLCreatorR->addChild($CXMLCreator);
		}

		return $CXMLCreatorR;
	}

//	protected static function GetProductList()
//	{
//		$ar = CCrmStatus::GetStatusList('PRODUCT');
//		$CXMLCreatorR = new CXMLCreator('CHOISES');
//
//		foreach ($ar as $key => $value)
//		{
//			$CXMLCreator = new CXMLCreator('CHOISE', true);
//			$CXMLCreator->setAttribute('id', $key);
//			$CXMLCreator->setData($value);
//			$CXMLCreatorR->addChild($CXMLCreator);
//		}
//
//		return $CXMLCreatorR;
//	}

	protected static function GetSourceList()
	{
		$ar = CCrmStatus::GetStatusListEx('SOURCE');
		$CXMLCreatorR = new CXMLCreator('CHOISES');

		foreach ($ar as $key => $value)
		{
			$CXMLCreator = new CXMLCreator('CHOISE', true);
			$CXMLCreator->setAttribute('id', $key);
			$CXMLCreator->setData($value);
			$CXMLCreatorR->addChild($CXMLCreator);
		}

		return $CXMLCreatorR;
	}

	protected static function GetCurrencyList()
	{
		$ar = CCrmCurrencyHelper::PrepareListItems();
		$CXMLCreatorR = new CXMLCreator('CHOISES');

		foreach ($ar as $key => $value)
		{
			$CXMLCreator = new CXMLCreator('CHOISE', true);
			$CXMLCreator->setAttribute('id', $key);
			$CXMLCreator->setData($value);
			$CXMLCreatorR->addChild($CXMLCreator);
		}

		return $CXMLCreatorR;
	}

	function GetFieldsList()
	{
		$fields = new CXMLCreator('Fields');
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="TITLE" name="'.GetMessage('CRM_FIELD_TITLE').'" type="string" require="true" default=""', ''));
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="NAME" name="'.GetMessage('CRM_FIELD_NAME').'" type="string" require="false" default=""', ''));
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="LAST_NAME" name="'.GetMessage('CRM_FIELD_LAST_NAME').'" type="string" require="false" default=""', ''));
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="SECOND_NAME" name="'.GetMessage('CRM_FIELD_SECOND_NAME').'" type="string" require="false" default=""', ''));
		$ar = CCrmFieldMulti::GetEntityComplexList();
		foreach($ar as $fieldId => $fieldName)
			$fields->addChild(CXMLCreator::createTagAttributed('Field id="'.$fieldId.'" name="'.$fieldName.'" type="string" require="false" default=""', ''));

		$fields->addChild(CXMLCreator::createTagAttributed('Field id="COMPANY_TITLE" name="'.GetMessage('CRM_FIELD_COMPANY_TITLE').'" type="string" require="false" default=""', ''));
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="POST" name="'.GetMessage('CRM_FIELD_POST').'" type="string" require="false" default=""', ''));
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="ADDRESS" name="'.GetMessage('CRM_FIELD_ADDRESS').'" type="string" require="false" default=""', ''));
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="COMMENTS" name="'.GetMessage('CRM_FIELD_COMMENTS').'" type="string" require="false" default=""', ''));
		//$fieldList = CXMLCreator::createTagAttributed('Field id="PRODUCT_ID" name="'.GetMessage('CRM_FIELD_PRODUCT_ID').'" type="string" require="false" default=""', '');
		//	$fieldList->addChild(self::GetProductList());
		//$fields->addChild($fieldList);
		$fieldList = CXMLCreator::createTagAttributed('Field id="STATUS_ID" name="'.GetMessage('CRM_FIELD_STATUS_ID').'" type="string" require="false" default=""', '');
			$fieldList->addChild(self::GetStatusList());
		$fields->addChild($fieldList);
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="OPPORTUNITY" name="'.GetMessage('CRM_FIELD_OPPORTUNITY').'" type="double" require="false" default=""', ''));
		$fieldList = CXMLCreator::createTagAttributed('Field id="CURRENCY_ID" name="'.GetMessage('CRM_FIELD_CURRENCY_ID').'" type="string" default=""', '');
			$fieldList->addChild(self::GetCurrencyList());
		$fields->addChild($fieldList);
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="STATUS_DESCRIPTION" name="'.GetMessage('CRM_FIELD_STATUS_DESCRIPTION').'" type="text" default=""', ''));
		$fieldList = CXMLCreator::createTagAttributed('Field id="SOURCE_ID" name="'.GetMessage('CRM_FIELD_SOURCE_ID').'" type="string" default=""', '');
			$fieldList->addChild(self::GetSourceList());
		$fields->addChild($fieldList);
		$fields->addChild(CXMLCreator::createTagAttributed('Field id="SOURCE_DESCRIPTION" name="'.GetMessage('CRM_FIELD_SOURCE_DESCRIPTION').'" type="text" default=""', ''));

		$CCrmUserType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], CCrmLead::$sUFEntityID);
		$CCrmUserType->AddWebserviceFields($fields);

		return array('GetFieldsListResult' => $fields);
	}

	public function GetWebServiceDesc()
	{
		$wsdesc = new CWebServiceDesc();
		$wsdesc->wsname = 'bitrix.crm.lead.webservice';
		$wsdesc->wsclassname = 'CCrmLeadWS';
		$wsdesc->wsdlauto = true;
		$wsdesc->wsendpoint = CWebService::GetDefaultEndpoint();
		$wsdesc->wstargetns = CWebService::GetDefaultTargetNS();

		$wsdesc->classTypes = array();
		$wsdesc->structTypes = array();


		$wsdesc->classes = array(
			'CCrmLeadWS' => array(
				'GetFieldsList' => array(
					'type' => 'public',
					'name' => 'GetFieldsList',
					'input' => array(),
					'output' => array(
						'GetFieldsListResult' => array('varType' => 'any')
					),
					'httpauth' => 'Y'
				),
				'Add' => array(
					'type'		=> 'public',
					'name'		=> 'Add',
					'input'		=> array(
						'data' => array('varType' => 'any')
					),
					'output'	=> array(
						'result' => array('varType' => 'string')
					),
					'httpauth' => 'Y'
				)
			)
		);

		return $wsdesc;
	}
}

?>
