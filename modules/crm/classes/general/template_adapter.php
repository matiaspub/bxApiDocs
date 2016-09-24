<?php
abstract class CCrmTemplateAdapterBase
{
	abstract public function GetSupportedTypes();
	abstract public function IsTypeSupported($typeID);
	abstract public function GetTypeMap($typeID);
	abstract public function CreateMapper($typeID, $ID);
}

class CCrmTemplateAdapter extends CCrmTemplateAdapterBase
{
	private static $MAP_BY_LANG = array();

	public function GetSupportedTypes()
	{
		return array(
			CCrmOwnerType::Lead,
			CCrmOwnerType::Contact,
			CCrmOwnerType::Company,
			CCrmOwnerType::Deal
		);
	}
	public function IsTypeSupported($typeID)
	{
		return CCrmOwnerType::IsDefined($typeID);
	}
	public function GetTypeMap($typeID)
	{
		$typeID = intval($typeID);
		if(!CCrmOwnerType::IsDefined($typeID))
		{
			return null;
		}

		if(!isset(self::$MAP_BY_LANG[LANGUAGE_ID]))
		{
			self::$MAP_BY_LANG[LANGUAGE_ID] = array();
		}

		if(isset(self::$MAP_BY_LANG[LANGUAGE_ID][$typeID]))
		{
			return self::$MAP_BY_LANG[LANGUAGE_ID][$typeID];
		}

		IncludeModuleLangFile(__FILE__);

		if($typeID === CCrmOwnerType::Lead)
		{
			return (self::$MAP_BY_LANG[LANGUAGE_ID][CCrmOwnerType::Lead] =
				array(
					'typeId' => CCrmOwnerType::Lead,
					'typeName' => CCrmOwnerType::ResolveName(CCrmOwnerType::Lead),
					'fields' => array(
						array('id' => 'ID', 'name' => 'ID'),
						array('id' => 'TITLE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_TITLE')),
						array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_LAST_NAME')),
						array('id' => 'NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_NAME')),
						array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_SECOND_NAME')),
						array('id' => 'FORMATTED_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_FORMATTED_NAME')),
						array('id' => 'COMPANY_TITLE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMPANY_TITLE')),
						array('id' => 'SOURCE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_SOURCE')),
						array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_SOURCE_DESCRIPTION')),
						array('id' => 'STATUS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_STATUS')),
						array('id' => 'STATUS_DESCRIPTION', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_STATUS_DESCRIPTION')),
						array('id' => 'POST', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_POST')),
						array('id' => 'ADDRESS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ADDRESS')),
						array('id' => 'CURRENCY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CURRENCY')),
						array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_OPPORTUNITY')),
						array('id' => 'OPPORTUNITY_FORMATTED', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_OPPORTUNITY_FORMATTED')),
						array('id' => 'ASSIGNED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_FULL_NAME')),
						array('id' => 'CREATED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CREATED_BY_FULL_NAME')),
						array('id' => 'MODIFY_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_MODIFY_BY_FULL_NAME')),
						array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_CREATE')),
						array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_MODIFY')),
						array('id' => 'COMMENTS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMMENTS'))
					)
				)
			);
		}
		elseif($typeID === CCrmOwnerType::Deal)
		{
			return (self::$MAP_BY_LANG[LANGUAGE_ID][CCrmOwnerType::Deal] =
				array(
					'typeId' => CCrmOwnerType::Deal,
					'typeName' => CCrmOwnerType::ResolveName(CCrmOwnerType::Deal),
					'fields' => array(
						array('id' => 'ID', 'name' => 'ID'),
						array('id' => 'TITLE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_TITLE')),
						array('id' => 'TYPE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_TYPE')),
						array('id' => 'STAGE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_STAGE')),
						array('id' => 'PROBABILITY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_PROBABILITY')),
						array('id' => 'CURRENCY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CURRENCY')),
						array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_OPPORTUNITY')),
						array('id' => 'OPPORTUNITY_FORMATTED', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_OPPORTUNITY_FORMATTED')),
						array('id' => 'COMPANY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMPANY'), 'typeId' => CCrmOwnerType::Company),
						array('id' => 'CONTACT', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CONTACT'), 'typeId' => CCrmOwnerType::Contact),
						array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_BEGINDATE')),
						array('id' => 'CLOSEDATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CLOSEDATE')),
						array('id' => 'ASSIGNED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_FULL_NAME')),
						array('id' => 'CREATED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CREATED_BY_FULL_NAME')),
						array('id' => 'MODIFY_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_MODIFY_BY_FULL_NAME')),
						array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_CREATE')),
						array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_MODIFY')),
						array('id' => 'COMMENTS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMMENTS'))
					)
				)
			);
		}
		elseif($typeID === CCrmOwnerType::Contact)
		{
			return (self::$MAP_BY_LANG[LANGUAGE_ID][CCrmOwnerType::Contact] =
				array(
					'typeId' => CCrmOwnerType::Contact,
					'typeName' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
					'fields' => array(
						array('id' => 'ID', 'name' => 'ID'),
						array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_LAST_NAME')),
						array('id' => 'NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_NAME')),
						array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_SECOND_NAME')),
						array('id' => 'FORMATTED_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_FORMATTED_NAME')),
						array('id' => 'POST', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_POST')),
						array('id' => 'ADDRESS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ADDRESS')),
						array('id' => 'TYPE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_TYPE')),
						array('id' => 'SOURCE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_SOURCE')),
						array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_STATUS_DESCRIPTION')),
						array('id' => 'COMPANY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMPANY'), 'typeId' => CCrmOwnerType::Company),
						array('id' => 'ASSIGNED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_FULL_NAME')),
						array('id' => 'CREATED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CREATED_BY_FULL_NAME')),
						array('id' => 'MODIFY_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_MODIFY_BY_FULL_NAME')),
						array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_CREATE')),
						array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_MODIFY')),
						array('id' => 'COMMENTS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMMENTS'))
					)
				)
			);
		}
		elseif($typeID === CCrmOwnerType::Company)
		{
			return (self::$MAP_BY_LANG[LANGUAGE_ID][CCrmOwnerType::Company] =
				array(
					'typeId' => CCrmOwnerType::Company,
					'typeName' => CCrmOwnerType::ResolveName(CCrmOwnerType::Company),
					'fields' => array(
						array('id' => 'ID', 'name' => 'ID'),
						array('id' => 'TITLE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_TITLE')),
						array('id' => 'TYPE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMPANY_TYPE')),
						array('id' => 'ADDRESS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ACTUAL_ADDRESS')),
						array('id' => 'ADDRESS_LEGAL', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ADDRESS_LEGAL')),
						array('id' => 'BANKING_DETAILS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_BANKING_DETAILS')),
						array('id' => 'INDUSTRY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_INDUSTRY')),
						array('id' => 'EMPLOYEES', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_EMPLOYEES')),
						array('id' => 'CURRENCY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CURRENCY')),
						array('id' => 'REVENUE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_REVENUE')),
						array('id' => 'REVENUE_FORMATTED', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_REVENUE_FORMATTED')),
						array('id' => 'ASSIGNED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_FULL_NAME')),
						array('id' => 'CREATED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CREATED_BY_FULL_NAME')),
						array('id' => 'MODIFY_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_MODIFY_BY_FULL_NAME')),
						array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_CREATE')),
						array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_MODIFY')),
						array('id' => 'COMMENTS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMMENTS'))
					)
				)
			);
		}
		return null;
	}
	public function CreateMapper($typeID, $ID)
	{
		return self::IsTypeSupported($typeID) ? new CCrmTemplateMapper($typeID, $ID) : null;
	}
}