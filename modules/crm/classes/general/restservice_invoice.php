<?php
if(!CModule::IncludeModule('rest'))
{
	return;
}

use \Bitrix\Rest\RestException;

class CCrmInvoiceRestUtil
{
	public static function convertValue($method, $valueType, $value)
	{
		$result = null;
		$convert = 'no';
		if ($valueType === 'datetime' || $valueType === 'date')
		{
			$postfix = '';
			switch ($valueType)
			{
				case 'datetime':
					$postfix = '_dt';
					break;
				case 'date':
					$postfix = '_d';
					break;
			}
			switch ($method)
			{
				case 'add':
				case 'update':
					$convert = 'in'.$postfix;
					break;
				case 'list':
				case 'get':
					$convert = 'out'.$postfix;
					break;
			}
		}
		switch ($convert)
		{
			case 'no':
				$result = $value;
				break;
			case 'in_dt':
				$result = CRestUtil::unConvertDateTime($value);
				break;
			case 'in_d':
				$result = CRestUtil::unConvertDate($value);
				break;
			case 'out_dt':
				$result = CRestUtil::ConvertDateTime($value);
				break;
			case 'out_d':
				$result = CRestUtil::ConvertDate($value);
				break;
		}

		return $result;
	}

	public static function getParamScalar(&$params, $name, $defaultValue = null)
	{
		$result = $defaultValue;

		if (is_array($params))
		{
			$index = strtolower($name);
			if (array_key_exists($index, $params))
			{
				$result = $params[$index];
			}
			else
			{
				$index = strtoupper($index);
				if (array_key_exists($index, $params))
					$result = $params[$index];
			}
		}

		return $result;
	}

	public static function getParamArray(&$params, $name, $defaultValue = array())
	{
		$result = $defaultValue;

		if (is_array($params))
		{
			$index = strtolower($name);
			if (is_array($params[$index]) && count($params[$index]) > 0)
			{
				$result = $params[$index];
			}
			else
			{
				$index = strtoupper($index);
				if (is_array($params[$index]) && count($params[$index]) > 0)
					$result = $params[$index];
			}
		}

		return $result;
	}
}

class CCrmInvoiceRestService extends IRestService
{
	private static $arAllowedFilterOperations =
		array('', '!', '+', '>=', '>', '<=', '<', '@', '~', '%');

	public static function OnRestServiceBuildDescription()
	{
		return array(
			'crm' => array(
				'crm.invoice.fields' => array('CCrmInvoiceRestService', 'fields'),
				'crm.invoice.list' => array('CCrmInvoiceRestService', 'getList'),
				'crm.invoice.get' => array('CCrmInvoiceRestService', 'get'),
				'crm.invoice.add' => array('CCrmInvoiceRestService', 'add'),
				'crm.invoice.update' => array('CCrmInvoiceRestService', 'update'),
				'crm.invoice.delete' => array('CCrmInvoiceRestService', 'delete'),
				'crm.vat.fields' => array('CCrmRestVat', 'fields'),
				'crm.vat.list' => array('CCrmRestVat', 'getList'),
				'crm.vat.get' => array('CCrmRestVat', 'get'),
				'crm.vat.add' => array('CCrmRestVat', 'add'),
				'crm.vat.update' => array('CCrmRestVat', 'update'),
				'crm.vat.delete' => array('CCrmRestVat', 'delete')
			)
		);
	}

	public static function getList($params, $nav = 0)
	{
		if(!CCrmInvoice::CheckReadPermission(0))
			throw new RestException('Access denied.');

		$order = CCrmInvoiceRestUtil::getParamArray($params, 'order', array('ID' => 'DESC'));
		$filter = CCrmInvoiceRestUtil::getParamArray($params, 'filter');
		$select = CCrmInvoiceRestUtil::getParamArray($params, 'select');

		$filter = self::prepareFilter($filter);
		$select = self::prepareSelect($select);
		$order = self::prepareOrder($order);

		if (!is_array($select) || count($select) === 0)
			throw new RestException('Inadmissible fields for selection');

		$dbResult = CCrmInvoice::GetList($order, $filter, false, self::getNavData($nav), $select);

		$result = array();
		while($arRow = $dbResult->NavNext(false))
			$result[] = self::filterFields($arRow, 'list');

		return self::setNavData($result, $dbResult);
	}

	public static function fields()
	{
		$fieldsInfo = self::getFieldsInfo();

		$fields = array();
		foreach ($fieldsInfo as $fName => $fInfo)
		{
			if (substr($fName, 0, 19) === 'INVOICE_PROPERTIES.')
			{
				if (substr($fName, 18) === '.{}')
				{
					$definition = array('key' => self::makeFieldInfo($fInfo));
					$fields['INVOICE_PROPERTIES']['definition'] = $definition;
				}
				elseif (substr($fName, 18) === '.{}.')
					$fields['INVOICE_PROPERTIES']['definition']['value'] = self::makeFieldInfo($fInfo);
			}
			elseif (substr($fName, 0, 13) === 'PRODUCT_ROWS.')
			{
				if (substr($fName, 12) === '.[]')
				{
					$definition = array('row' => array());
					$fields['PRODUCT_ROWS']['definition'] = $definition;
				}
				elseif (substr($fName, 12, 4) === '.[].')
				{
					$subName = substr($fName, 16);
					$fields['PRODUCT_ROWS']['definition']['row'][$subName] = self::makeFieldInfo($fInfo);
				}
			}
			else
				$fields[$fName] = self::makeFieldInfo($fInfo);
		}

		return $fields;
	}
	
	public static function get($params)
	{
		$ID = CCrmInvoiceRestUtil::getParamScalar($params, 'id', 0);
		if(!CCrmInvoice::CheckReadPermission($ID))
			throw new RestException('Access denied.');

		$arResult = self::getInvoiceDataByID($ID);
		$arResult = self::filterFields($arResult, 'get');

		return $arResult;
	}

	public static function add($params)
	{
		global $DB;

		$invoice = new CCrmInvoice();
		if(!CCrmInvoice::CheckCreatePermission())
			throw new RestException('Access denied.');

		$fields = CCrmInvoiceRestUtil::getParamArray($params, 'fields');

		$fields = self::filterFields($fields, 'add');

		if (!is_array($fields) || count($fields) === 0)
			throw new RestException('Invalid parameters.');

		// sanitize
		$comments = isset($fields['COMMENTS']) ? trim($fields['COMMENTS']) : '';
		$userDescription = isset($fields['USER_DESCRIPTION']) ? trim($fields['USER_DESCRIPTION']) : '';
		$bSanitizeComments = ($comments !== '' && strpos($comments, '<'));
		$bSanitizeUserDescription = ($userDescription !== '' && strpos($userDescription, '<'));
		if ($bSanitizeComments || $bSanitizeUserDescription)
		{
			$sanitizer = new CBXSanitizer();
			$sanitizer->ApplyDoubleEncode(false);
			$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
			//Crutch for for Chrome line break behaviour in HTML editor.
			$sanitizer->AddTags(array('div' => array()));
			if ($bSanitizeComments)
				$fields['COMMENTS'] = $sanitizer->SanitizeHtml($fields['COMMENTS']);
			if ($bSanitizeUserDescription)
				$fields['USER_DESCRIPTION'] = $sanitizer->SanitizeHtml($fields['USER_DESCRIPTION']);
			unset($sanitizer);
		}
		unset($bSanitizeComments, $bSanitizeUserDescription);
		$fields['COMMENTS'] = $comments;
		$fields['USER_DESCRIPTION'] = $userDescription;
		unset($comments, $userDescription);

		$bStatusSuccess = CCrmStatusInvoice::isStatusSuccess($fields['STATUS_ID']);
		if ($bStatusSuccess)
			$bStatusFailed = false;
		else
			$bStatusFailed = CCrmStatusInvoice::isStatusFailed($fields['STATUS_ID']);

		if (!$invoice->CheckFields($fields, false, $bStatusSuccess, $bStatusFailed))
		{
			if (!empty($invoice->LAST_ERROR))
				throw new RestException($invoice->LAST_ERROR);
			else
				throw new RestException('Error on check fields.');
		}

		$propsInfo = CCrmInvoice::GetPropertiesInfo($fields['PERSON_TYPE_ID']);
		$propsInfo = is_array($propsInfo[$fields['PERSON_TYPE_ID']]) ? $propsInfo[$fields['PERSON_TYPE_ID']] : array();
		$invoiceProperties = array();
		foreach ($fields['INVOICE_PROPERTIES'] as $code => $value)
		{
			if (array_key_exists($code, $propsInfo))
				$invoiceProperties[$propsInfo[$code]['ID']] = $value;
		}
		$fields['INVOICE_PROPERTIES'] = $invoiceProperties;
		unset($propsInfo, $invoiceProperties, $code, $value);

		$DB->StartTransaction();
		$recalculate = false;
		$ID = $invoice->Add($fields, $recalculate, SITE_ID, array('UPDATE_SEARCH' => true));
		if(!is_int($ID) || $ID <= 0)
		{
			$DB->Rollback();

			if (!empty($invoice->LAST_ERROR))
				throw new RestException($invoice->LAST_ERROR);
			else
				throw new RestException('Error on creating invoice.');
		}
		else
			$DB->Commit();

		return $ID;
	}

	public static function update($params)
	{
		global $DB;

		$ID = CCrmInvoiceRestUtil::getParamScalar($params, 'id', 0);
		if($ID <= 0)
			throw new RestException('Invalid identifier.');

		$invoice = new CCrmInvoice();
		if(!CCrmInvoice::CheckUpdatePermission($ID))
			throw new RestException('Access denied.');

		$fields = CCrmInvoiceRestUtil::getParamArray($params, 'fields');
		$fields = self::filterFields($fields, 'update');

		// sanitize
		$comments = isset($fields['COMMENTS']) ? trim($fields['COMMENTS']) : '';
		$userDescription = isset($fields['USER_DESCRIPTION']) ? trim($fields['USER_DESCRIPTION']) : '';
		$bSanitizeComments = ($comments !== '' && strpos($comments, '<'));
		$bSanitizeUserDescription = ($userDescription !== '' && strpos($userDescription, '<'));
		if ($bSanitizeComments || $bSanitizeUserDescription)
		{
			$sanitizer = new CBXSanitizer();
			$sanitizer->ApplyDoubleEncode(false);
			$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
			//Crutch for for Chrome line break behaviour in HTML editor.
			$sanitizer->AddTags(array('div' => array()));
			if ($bSanitizeComments)
				$fields['COMMENTS'] = $sanitizer->SanitizeHtml($fields['COMMENTS']);
			if ($bSanitizeUserDescription)
				$fields['USER_DESCRIPTION'] = $sanitizer->SanitizeHtml($fields['USER_DESCRIPTION']);
			unset($sanitizer);
		}
		unset($bSanitizeComments, $bSanitizeUserDescription);
		$fields['COMMENTS'] = $comments;
		$fields['USER_DESCRIPTION'] = $userDescription;
		unset($comments, $userDescription);

		if (!is_array($fields) || count($fields) === 0)
			throw new RestException('Invalid parameters.');

		$origFields = self::getInvoiceDataByID($ID);
		$origFields = self::filterFields($origFields, 'update');
		foreach ($origFields as $fName => $fValue)
		{
			if (!array_key_exists($fName, $fields))
				$fields[$fName] = $fValue;
		}

		$bStatusSuccess = CCrmStatusInvoice::isStatusSuccess($fields['STATUS_ID']);
		if ($bStatusSuccess)
			$bStatusFailed = false;
		else
			$bStatusFailed = CCrmStatusInvoice::isStatusFailed($fields['STATUS_ID']);

		if (!$invoice->CheckFields($fields, false, $bStatusSuccess, $bStatusFailed))
		{
			if (!empty($invoice->LAST_ERROR))
				throw new RestException($invoice->LAST_ERROR);
			else
				throw new RestException('Error on check fields.');
		}

		$propsInfo = CCrmInvoice::GetPropertiesInfo($fields['PERSON_TYPE_ID']);
		$propsInfo = is_array($propsInfo[$fields['PERSON_TYPE_ID']]) ? $propsInfo[$fields['PERSON_TYPE_ID']] : array();
		$invoiceProperties = array();
		foreach ($propsInfo as $propCode => $arProp)
		{
			if (array_key_exists($propCode, $fields['INVOICE_PROPERTIES']))
				$invoiceProperties[$arProp['ID']] = $fields['INVOICE_PROPERTIES'][$propCode];
			else if (is_array($origFields['INVOICE_PROPERTIES']) && array_key_exists($propCode, $origFields['INVOICE_PROPERTIES']))
				$invoiceProperties[$arProp['ID']] = $origFields['INVOICE_PROPERTIES'][$propCode];
		}
		$fields['INVOICE_PROPERTIES'] = $invoiceProperties;
		unset($propsInfo, $invoiceProperties, $propCode, $arProp);

		$DB->StartTransaction();
		$ID = $invoice->Update($ID, $fields, array('UPDATE_SEARCH' => true));
		if(!is_int($ID) || $ID <= 0)
		{
			$DB->Rollback();

			if (!empty($invoice->LAST_ERROR))
				throw new RestException($invoice->LAST_ERROR);
			else
				throw new RestException('Error on updating invoice.');
		}
		else
			$DB->Commit();

		return $ID;
	}

	public static function delete($params)
	{
		global $DB;

		$ID = CCrmInvoiceRestUtil::getParamScalar($params, 'id', 0);
		if($ID <= 0)
			throw new RestException('Invalid identifier.');

		$invoice = new CCrmInvoice();
		if(!CCrmInvoice::CheckUpdatePermission($ID))
			throw new RestException('Access denied.');

		$DB->StartTransaction();
		if(!$invoice->Delete($ID))
		{
			$DB->Rollback();

			throw new RestException('Error on deleting invoice.');
		}
		else
		{
			$DB->Commit();
		}

		return $ID;
	}

	private static function getFieldsInfo()
	{
		$fieldsInfo = array(
			"ACCOUNT_NUMBER" => array(
				"type" => "string",
				"size" => "100",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => false,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"COMMENTS" => array(
				"type" => "text",
				"level" => 0,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"CURRENCY" => array(
				"type" => "string",
				"size" => "3",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_BILL" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_INSERT" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_MARKED" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_PAY_BEFORE" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_PAYED" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_STATUS" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"DATE_UPDATE" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"EMP_PAYED_ID" => array(
				"type" => "integer",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"EMP_STATUS_ID" => array(
				"type" => "integer",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"ID" => array(
				"type" => "integer",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"LID" => array(
				"type" => "string",
				"size" => "2",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"ORDER_TOPIC" => array(
				"type" => "string",
				"size" => "255",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PAY_SYSTEM_ID" => array(
				"type" => "integer",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PAY_VOUCHER_DATE" => array(
				"type" => "date",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PAY_VOUCHER_NUM" => array(
				"type" => "string",
				"size" => "20",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PAYED" => array(
				"type" => "string",
				"size" => "1",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PERSON_TYPE_ID" => array(
				"type" => "integer",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PRICE" => array(
				"type" => "double",
				"size" => "18,2",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"REASON_MARKED" => array(
				"type" => "string",
				"size" => "255",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_EMAIL" => array(
				"type" => "string",
				"size" => "255",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_ID" => array(
				"type" => "integer",
				"size" => "18",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_LAST_NAME" => array(
				"type" => "string",
				"size" => "50",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_LOGIN" => array(
				"type" => "string",
				"size" => "50",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_NAME" => array(
				"type" => "string",
				"size" => "50",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_PERSONAL_PHOTO" => array(
				"type" => "integer",
				"size" => "18",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_SECOND_NAME" => array(
				"type" => "string",
				"size" => "50",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RESPONSIBLE_WORK_POSITION" => array(
				"type" => "string",
				"size" => "255",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"STATUS_ID" => array(
				"type" => "string",
				"size" => "1",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"TAX_VALUE" => array(
				"type" => "double",
				"size" => "18,2",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"UF_COMPANY_ID" => array(
				"type" => "integer",
				"size" => "20",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"UF_CONTACT_ID" => array(
				"type" => "integer",
				"size" => "20",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"UF_DEAL_ID" => array(
				"type" => "integer",
				"size" => "20",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"USER_DESCRIPTION" => array(
				"type" => "string",
				"size" => "250",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"PR_LOCATION" => array(
				"type" => "integer",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"INVOICE_PROPERTIES" => array(
				"type" => "aarray",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"INVOICE_PROPERTIES.{}" => array(
				"type" => "integer",
				"level" => 1,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"INVOICE_PROPERTIES.{}." => array(
				"type" => "variable",
				"level" => 2,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS" => array(
				"type" => "iarray",
				"level" => 0,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[]" => array(
				"type" => "integer",
				"level" => 1,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].ID" => array(
				"type" => "integer",
				"level" => 2,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].PRICE" => array(
				"type" => "double",
				"size" => "18,2",
				"level" => 2,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].PRODUCT_ID" => array(
				"type" => "integer",
				"level" => 2,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].PRODUCT_NAME" => array(
				"type" => "string",
				"size" => "255",
				"level" => 2,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].QUANTITY" => array(
				"type" => "double",
				"size" => "18,2",
				"level" => 2,
				"required" => true,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => false,
				"filter" => false,
				"order" => false
			),
			"PRODUCT_ROWS.[].VAT_RATE" => array(
				"type" => "double",
				"size" => "18,2",
				"level" => 2,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => false,
				"filter" => false,
				"order" => false
			)
		);

		return $fieldsInfo;
	}

	private static function makeFieldInfo($fInfo)
	{
		$result = array();
		$result['type'] = $fInfo['type'];
		if (isset($fInfo['size']))
			$result['size'] = $fInfo['size'];
		$result['isRequired'] = $fInfo['required'];
		$result['isReadOnly'] = $fInfo['readonly'];

		return $result;
	}

	private static function filterFields($fields, $method)
	{
		$result = array();

		if (!is_array($fields) || count($fields) === 0)
			return $result;

		if (!in_array($method, array('get', 'add', 'update', 'list'), true))
			return $result;

		$bTaxMode = CCrmTax::isTaxMode();

		$fieldsInfo = self::getFieldsInfo();
		$allowedFields = array();
		foreach ($fieldsInfo as $fName => $fInfo)
		{
			if ($fInfo[$method] === true)
			{
				if ($fName !== 'PR_LOCATION')
				{
					$allowedFields[] = $fName;
				}
				else
				{
					if ($bTaxMode)
						$allowedFields[] = $fName;
				}
			}
		}
		unset($fName, $fInfo);

		foreach ($fields as $fName => $fValue)
		{
			if ($fName !== 'INVOICE_PROPERTIES' && $fName !== 'PRODUCT_ROWS' && in_array($fName, $allowedFields))
				$result[$fName] = CCrmInvoiceRestUtil::convertValue($method, $fieldsInfo[$fName]['type'], $fValue);
		}

		if (isset($fields['INVOICE_PROPERTIES']) && is_array($fields['INVOICE_PROPERTIES'])
			&& in_array('INVOICE_PROPERTIES', $allowedFields, true))
		{
			$props = array();
			foreach ($fields['INVOICE_PROPERTIES'] as $k => $v)
			{
				if (!is_array($v) /*&& preg_match('/^[A-Za-z0-9_\-]+$/',strval($k))*/)
				{
					$props[$k] = $v;
				}
			}
			if (count($props) > 0)
				$result['INVOICE_PROPERTIES'] = $props;
			unset($props, $k, $v);
		}

		if (isset($fields['PRODUCT_ROWS']) && is_array($fields['PRODUCT_ROWS'])
			&& in_array('PRODUCT_ROWS', $allowedFields))
		{
			$products = array();
			foreach ($fields['PRODUCT_ROWS'] as $productRow)
			{
				$row = array();
				foreach ($productRow as $k => $v)
				{
					if (in_array('PRODUCT_ROWS.[].'.$k, $allowedFields, true))
						$row[$k] = CCrmInvoiceRestUtil::convertValue($method, $fieldsInfo['PRODUCT_ROWS.[].'.$k]['type'], $v);
				}
				if (count($row) > 0)
					$products[] = $row;
			}
			if (count($products) > 0)
				$result['PRODUCT_ROWS'] = $products;
			unset($products, $productRow, $k, $v, $row);
		}
		unset($fieldsInfo);

		return $result;
	}

	private static function getInvoiceDataByID($ID)
	{
		$arInvoice = CCrmInvoice::GetByID($ID);
		if(!is_array($arInvoice))
			throw new RestException('Not found.');

		$arProperties = CCrmInvoice::GetProperties($ID, $arInvoice['PERSON_TYPE_ID']);
		$arAllowedProperties = CCrmInvoice::GetPropertiesInfo($arInvoice['PERSON_TYPE_ID'], true);
		$arAllowedProperties = is_array($arAllowedProperties[$arInvoice['PERSON_TYPE_ID']]) ?
			array_keys($arAllowedProperties[$arInvoice['PERSON_TYPE_ID']]) : array();
		$arPropertiesResult = array();
		foreach ($arProperties as $k => $v)
		{
			if ($k !== 'PR_LOCATION')
			{
				if (in_array($v['FIELDS']['CODE'], $arAllowedProperties))
					$arPropertiesResult[$v['FIELDS']['CODE']] = $v['VALUE'];
			}
			else
				$arInvoice['PR_LOCATION'] = $v['VALUE'];
		}

		$arProducts = CCrmInvoice::GetProductRows($ID);

		$result = $arInvoice;
		if (count($arPropertiesResult) > 0)
			$result['INVOICE_PROPERTIES'] = $arPropertiesResult;
		if (count($arProducts) > 0)
			$result['PRODUCT_ROWS'] = $arProducts;

		return $result;
	}

	private static function prepareFilter($arFilter)
	{
		if(!is_array($arFilter))
		{
			$arFilter = array();
		}
		else
		{
			$fieldsInfo = self::getFieldsInfo();
			$arAllowedFilterFields = array();
			foreach ($fieldsInfo as $fieldName => $fieldInfo)
			{
				if ($fieldInfo['filter'] === true)
					$arAllowedFilterFields[] = $fieldName;
			}

			if (count($arFilter) > 0)
			{
				$arFilter = array_change_key_case($arFilter, CASE_UPPER);
				foreach ($arFilter as $key => $value)
				{
					$matches = array();
					if(preg_match('/^([^a-zA-Z]*)(.*)/', $key, $matches))
					{
						$operation = $matches[1];
						$field = $matches[2];

						if(!in_array($field, $arAllowedFilterFields, true)
							|| !in_array($operation, self::$arAllowedFilterOperations, true))
						{
							unset($arFilter[$key]);
						}
						else
						{
							switch ($fieldsInfo[$field]['type'])
							{
								case 'datetime':
									$arFilter[$key] = CRestUtil::unConvertDateTime($value);
									break;

								case 'date':
									$arFilter[$key] = CRestUtil::unConvertDate($value);
									break;

								default:
									break;
							}

							switch($field)
							{
								case 'CHECK_PERMISSIONS':
									unset($arFilter[$key]);
									break;

								default:
									break;
							}
						}
					}
					else
					{
						unset($arFilter[$key]);
					}
				}
			}
		}

		return $arFilter;
	}
	
	private static function prepareSelect($arSelect)
	{
		$arResult = array();

		if (is_array($arSelect))
		{
			$bAllFields = false;
			if (count($arSelect) === 0 || in_array('*', $arSelect, true))
				$bAllFields = true;

			$fieldsInfo = self::getFieldsInfo();
			foreach ($fieldsInfo as $fieldName => $fieldInfo)
			{
				if (isset($fieldInfo['list']) && $fieldInfo['list'] === true)
				{
					if ($bAllFields || in_array($fieldName, $arSelect, true))
						$arResult[] = $fieldName;
				}
			}
		}

		return $arResult;
	}

	private static function prepareOrder($arOrder)
	{
		$arResult = array();

		if (is_array($arOrder))
		{
			$fieldsInfo = self::getFieldsInfo();
			foreach ($arOrder as $fieldName => $sortName)
			{
				if (isset($fieldsInfo[$fieldName])
					&& $fieldsInfo[$fieldName]['order'] === true
					&& ($sortName === 'ASC' || $sortName === 'DESC'))
				{
					$arResult[$fieldName] = $arOrder[$fieldName];
				}
			}
		}

		return $arResult;
	}
}

class CCrmRestVat extends IRestService
{
	private static $arAllowedFilterOperations =
		array('', '=', '!', '@', '~', '%', '!+', '+', '+!', '>=', '<=', '>', '<');

	public static function getList($params, $nav = 0)
	{
		if (!CModule::IncludeModule('catalog'))
			throw new RestException('The Commercial Catalog module is not installed.');

		global $USER;

		$CrmPerms = new CCrmPerms($USER->GetID());
		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
			throw new RestException('Access denied.');

		$order =  CCrmInvoiceRestUtil::getParamArray($params, 'order', array('SORT' => 'ASC'));
		$filter = CCrmInvoiceRestUtil::getParamArray($params, 'filter', array());
		$select = CCrmInvoiceRestUtil::getParamArray($params, 'select', array());

		$result = array();
		$catalogVat = new CCatalogVat();

		$filter = self::prepareFilter($filter);
		$select = self::prepareSelect($select);
		$order = self::prepareOrder($order);

		if (!is_array($select) || count($select) === 0)
			throw new RestException('Inadmissible fields for selection');

		$dbResult = $catalogVat->GetListEx($order, $filter, false, self::getNavData($nav), $select);
		while($arRow = $dbResult->NavNext(false))
			$result[] = self::filterFields($arRow, 'list');

		return self::setNavData($result, $dbResult);
	}

	public static function fields()
	{
		$fieldsInfo = self::getFieldsInfo();

		$fields = array();
		foreach ($fieldsInfo as $fName => $fInfo)
		{
			$fields[$fName] = self::makeFieldInfo($fInfo);
		}

		return $fields;
	}

	public static function get($params)
	{
		if (!CModule::IncludeModule('catalog'))
			throw new RestException('The Commercial Catalog module is not installed.');

		global $USER;

		$CrmPerms = new CCrmPerms($USER->GetID());
		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
			throw new RestException('Access denied.');

		$ID = CCrmInvoiceRestUtil::getParamScalar($params, 'id', 0);
		$arResult = CCrmVat::GetByID($ID);
		if ($arResult === false)
			throw new RestException('VAT rate not found.');
		$arResult = self::filterFields($arResult, 'get');

		return $arResult;
	}

	public static function add($params)
	{
		if (!CModule::IncludeModule('catalog'))
			throw new RestException('The Commercial Catalog module is not installed.');

		global $DB, $USER;

		$CrmPerms = new CCrmPerms($USER->GetID());
		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
			throw new RestException('Access denied.');

		$fields = CCrmInvoiceRestUtil::getParamArray($params, 'fields');

		$fields = self::filterFields($fields, 'add');

		if (!is_array($fields) || count($fields) === 0)
			throw new RestException('Invalid parameters.');

		$DB->StartTransaction();
		$ID = false;
		if (isset($fields['ID']))
			unset($fields['ID']);
		if (count($fields) > 0)
		{
			$catalogVat = new CCatalogVat();
			$ID = $catalogVat->Add($fields);
		}
		if($ID)
		{
			$DB->Commit();
		}
		else
		{
			$DB->Rollback();
			throw new RestException('Error on creating VAT rate.');
		}

		return $ID;
	}

	public static function update($params)
	{
		if (!CModule::IncludeModule('catalog'))
			throw new RestException('The Commercial Catalog module is not installed.');

		global $DB, $USER;

		$CrmPerms = new CCrmPerms($USER->GetID());
		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
			throw new RestException('Access denied.');

		$ID = intval(CCrmInvoiceRestUtil::getParamScalar($params, 'id', 0));
		if($ID <= 0)
			throw new RestException('Invalid identifier.');

		$fields = CCrmInvoiceRestUtil::getParamArray($params, 'fields');

		$fields = self::filterFields($fields, 'update');

		if (!is_array($fields) || count($fields) === 0)
			throw new RestException('Invalid parameters.');

		$DB->StartTransaction();
		$updatedID = false;
		if (count($fields) > 0)
		{
			$catalogVat = new CCatalogVat();
			$updatedID = $catalogVat->Update($ID, $fields);
		}
		if($updatedID)
		{
			$DB->Commit();
		}
		else
		{
			$DB->Rollback();
			throw new RestException('Error on updating VAT rate.');
		}

		return $updatedID;
	}

	public static function delete($params)
	{
		if (!CModule::IncludeModule('catalog'))
			throw new RestException('The Commercial Catalog module is not installed.');

		global $DB, $USER;

		$CrmPerms = new CCrmPerms($USER->GetID());
		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
			throw new RestException('Access denied.');

		$ID = CCrmInvoiceRestUtil::getParamScalar($params, 'id', 0);
		if($ID <= 0)
			throw new RestException('Invalid identifier.');

		$DB->StartTransaction();
		$catalogVat = new CCatalogVat();
		$bDeleted = $catalogVat->Delete($ID);
		if($bDeleted)
		{
			$DB->Commit();
		}
		else
		{
			$DB->Rollback();
			throw new RestException('Error on deleting VAT rate.');
		}

		return $bDeleted;
	}

	private static function getFieldsInfo()
	{
		$fieldsInfo = array(
			"ID" => array(
				"type" => "integer",
				"size" => "11",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"TIMESTAMP_X" => array(
				"type" => "datetime",
				"level" => 0,
				"required" => false,
				"readonly" => true,
				"get" => true,
				"add" => false,
				"update" => false,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"ACTIVE" => array(
				"type" => "string",
				"size" => "1",
				"level" => 0,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"C_SORT" => array(
				"type" => "integer",
				"size" => "18",
				"level" => 0,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"NAME" => array(
				"type" => "string",
				"size" => "50",
				"level" => 0,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
			"RATE" => array(
				"type" => "double",
				"size" => "18,2",
				"level" => 0,
				"required" => false,
				"readonly" => false,
				"get" => true,
				"add" => true,
				"update" => true,
				"list" => true,
				"filter" => true,
				"order" => true
			),
		);

		return $fieldsInfo;
	}

	private static function makeFieldInfo($fInfo)
	{
		$result = array();
		$result['type'] = $fInfo['type'];
		if (isset($fInfo['size']))
			$result['size'] = $fInfo['size'];
		$result['isRequired'] = $fInfo['required'];
		$result['isReadOnly'] = $fInfo['readonly'];

		return $result;
	}

	private static function filterFields($fields, $method)
	{
		$result = array();

		if (!is_array($fields) || count($fields) === 0)
			return $result;

		if (!in_array($method, array('get', 'add', 'update', 'list'), true))
			return $result;

		$fieldsInfo = self::getFieldsInfo();
		$allowedFields = array();
		foreach ($fieldsInfo as $fName => $fInfo)
		{
			if ($fInfo[$method] === true)
				$allowedFields[] = $fName;
		}
		unset($fName, $fInfo);

		foreach ($fields as $fName => $fValue)
		{
			if (in_array($fName, $allowedFields))
				$result[$fName] = CCrmInvoiceRestUtil::convertValue($method, $fieldsInfo[$fName]['type'], $fValue);
		}
		unset($fieldsInfo);

		return $result;
	}

	private static function prepareFilter($arFilter)
	{
		if(!is_array($arFilter))
		{
			$arFilter = array();
		}
		else
		{
			$fieldsInfo = self::getFieldsInfo();
			$arAllowedFilterFields = array();
			foreach ($fieldsInfo as $fieldName => $fieldInfo)
			{
				if ($fieldInfo['filter'] === true)
					$arAllowedFilterFields[] = $fieldName;
			}

			if (count($arFilter) > 0)
			{
				$arFilter = array_change_key_case($arFilter, CASE_UPPER);
				foreach ($arFilter as $key => $value)
				{
					$matches = array();
					if(preg_match('/^([^a-zA-Z]*)(.*)/', $key, $matches))
					{
						$operation = $matches[1];
						$field = $matches[2];

						if(!in_array($field, $arAllowedFilterFields, true)
							|| !in_array($operation, self::$arAllowedFilterOperations, true))
						{
							unset($arFilter[$key]);
						}
						else
						{
							switch ($fieldsInfo[$field]['type'])
							{
								case 'datetime':
									$arFilter[$key] = CRestUtil::unConvertDateTime($value);
									break;

								case 'date':
									$arFilter[$key] = CRestUtil::unConvertDate($value);
									break;

								default:
									break;
							}
						}
					}
					else
					{
						unset($arFilter[$key]);
					}
				}
			}
		}

		return $arFilter;
	}

	private static function prepareSelect($arSelect)
	{
		$arResult = array();

		if (is_array($arSelect))
		{
			$bAllFields = false;
			if (count($arSelect) === 0 || in_array('*', $arSelect, true))
				$bAllFields = true;

			$fieldsInfo = self::getFieldsInfo();
			foreach ($fieldsInfo as $fieldName => $fieldInfo)
			{
				if (isset($fieldInfo['list']) && $fieldInfo['list'] === true)
				{
					if ($bAllFields || in_array($fieldName, $arSelect, true))
						$arResult[] = $fieldName;
				}
			}
		}

		return $arResult;
	}

	private static function prepareOrder($arOrder)
	{
		$arResult = array();

		if (is_array($arOrder))
		{
			$fieldsInfo = self::getFieldsInfo();
			foreach ($arOrder as $fieldName => $sortName)
			{
				if (isset($fieldsInfo[$fieldName])
					&& $fieldsInfo[$fieldName]['order'] === true
					&& ($sortName === 'ASC' || $sortName === 'DESC'))
				{
					$arResult[$fieldName] = $arOrder[$fieldName];
				}
			}
		}

		return $arResult;
	}
}
