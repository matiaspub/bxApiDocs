<?
IncludeModuleLangFile(__FILE__);

class CCRMLeadRest
{
	private static $bReturnObject = false;
	private static $authHash = null;

	/* public section */

	public static function CreateAuthHash($arData)
	{
		global $USER, $APPLICATION;
		self::$authHash = $USER->AddHitAuthHash($APPLICATION->GetCurPage());
	}

	public static function CheckAuthHash($arData)
	{
		global $USER;

		if (strlen($arData['AUTH']) > 0)
		{
			$_REQUEST['bx_hit_hash'] = $arData['AUTH'];
			return $USER->LoginHitByHash();
		}

		return false;
	}

	public static function AddLead($arData, $CCrmLead)
	{
		global $DB, $USER_FIELD_MANAGER;

		$CCrmBizProc = new CCrmBizProc('LEAD');

		$arData['CURRENCY_ID'] = trim($arData['CURRENCY_ID']);
		if (strlen($arData['CURRENCY_ID']) <= 0)
			$arData['CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();

		$arFields = array(
			'TITLE' => trim($arData['TITLE']),
			'COMPANY_TITLE' => trim($arData['COMPANY_TITLE']),
			'NAME' => trim($arData['NAME']),
			'LAST_NAME' => trim($arData['LAST_NAME']),
			'SECOND_NAME' => trim($arData['SECOND_NAME']),
			'POST' => trim($arData['POST']),
			'ADDRESS' => trim($arData['ADDRESS']),
			'COMMENTS' => trim($arData['COMMENTS']),
			'SOURCE_DESCRIPTION' => trim($arData['SOURCE_DESCRIPTION']),
			'STATUS_DESCRIPTION' => trim($arData['STATUS_DESCRIPTION']),
			'OPPORTUNITY' => trim($arData['OPPORTUNITY']),
			'CURRENCY_ID' => trim($arData['CURRENCY_ID']),
			'ASSIGNED_BY_ID' => (int)(is_array($arData['ASSIGNED_BY_ID']) ? $arData['ASSIGNED_BY_ID'][0] : $arData['ASSIGNED_BY_ID']),
			'OPENED' => 'Y',
		);

		$arData['SOURCE_ID'] = trim($arData['SOURCE_ID']);
		$arData['STATUS_ID'] = trim($arData['STATUS_ID']);

		if (strlen($arData['STATUS_ID']) > 0)
			$arFields['STATUS_ID'] = $arData['STATUS_ID'];
		if (strlen($arData['SOURCE_ID']) > 0)
			$arFields['SOURCE_ID'] = $arData['SOURCE_ID'];

		$USER_FIELD_MANAGER->EditFormAddFields(CCrmLead::$sUFEntityID, $arFields);
		$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);
		$CCrmUserType->PrepareImport($arFields, ',');

		$arFields['FM'] = CCrmFieldMulti::PrepareFields($arData);

		$DB->StartTransaction();

		$ID = $CCrmLead->Add($arFields);


		if ($ID === false)
		{
			$DB->Rollback();
			if (!empty($arFields['RESULT_MESSAGE']))
				$sErrorMessage = $arFields['RESULT_MESSAGE'];
			else
				$sErrorMessage = GetMessage('UNKNOWN_ERROR');

			$res =  array('error' => 400, 'error_message' => strip_tags(nl2br($sErrorMessage)));
		}
		else
		{
			$DB->Commit();

			// Ignore all BizProc errors
			try
			{
				$arErrors = array();
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Lead,
					$ID,
					CCrmBizProcEventType::Create,
					$arErrors
				);
			}
			catch(Exception $e)
			{
			}

			$res = array('error' => 201, 'ID' => $ID, 'error_message' => GetMessage('CRM_REST_OK'));
		}

		return self::_out($res);
	}

	public static function AddLeadBundle($arLeads, $CCrmLead)
	{
		if (is_array($arLeads))
		{
			$res = array();
			self::$bReturnObject = true;
			foreach ($arLeads as $arLeadData)
			{
				$res[] = CCrmLeadRest::AddLead($arLeadData, $CCrmLead, true);
			}
			self::$bReturnObject = false;
			return self::_out(array('RESULTS' => $res));
		}
		else
		{
			return self::_out(array('error' => 400, 'error_message' => GetMessage('CRM_REST_ERROR_BAD_REQUEST')));
		}
	}

	public static function GetFields()
	{
		$fields = array();
		$fields[] = array('ID' => 'TITLE', 'NAME' => GetMessage('CRM_FIELD_TITLE'), 'TYPE' => 'string', 'REQUIRED' => true);
		$fields[] = array('ID' => 'NAME', 'NAME' => GetMessage('CRM_FIELD_REST_NAME'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'LAST_NAME', 'NAME' => GetMessage('CRM_FIELD_LAST_NAME'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'SECOND_NAME', 'NAME' => GetMessage('CRM_FIELD_SECOND_NAME'), 'TYPE' => 'string', 'REQUIRED' => false);

		$ar = CCrmFieldMulti::GetEntityComplexList();
		foreach($ar as $fieldId => $fieldName)
		{
			$fields[] = array('ID' => $fieldId, 'NAME' => $fieldName, 'TYPE' => 'string', 'REQUIRED' => false);
		}

		$fields[] = array('ID' => 'COMPANY_TITLE', 'NAME' => GetMessage('CRM_FIELD_COMPANY_TITLE'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'POST', 'NAME' => GetMessage('CRM_FIELD_POST'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'ADDRESS', 'NAME' => GetMessage('CRM_FIELD_ADDRESS'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'COMMENTS', 'NAME' => GetMessage('CRM_FIELD_COMMENTS'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'STATUS_ID', 'NAME' => GetMessage('CRM_FIELD_STATUS_ID'), 'TYPE' => 'enum', 'VALUES' => self::_GetStatusList(), 'REQUIRED' => false);
		$fields[] = array('ID' => 'CURRENCY_ID', 'NAME' => GetMessage('CRM_FIELD_CURRENCY_ID'), 'TYPE' => 'enum', 'VALUES' => self::_GetCurrencyList(), 'REQUIRED' => false);
		$fields[] = array('ID' => 'SOURCE_ID', 'NAME' => GetMessage('CRM_FIELD_SOURCE_ID'), 'TYPE' => 'enum', 'VALUES' => self::_GetSourceList(), 'REQUIRED' => false);
		$fields[] = array('ID' => 'OPPORTUNITY', 'NAME' => GetMessage('CRM_FIELD_OPPORTUNITY'), 'TYPE' => 'double', 'REQUIRED' => false);
		$fields[] = array('ID' => 'STATUS_DESCRIPTION', 'NAME' => GetMessage('CRM_FIELD_STATUS_DESCRIPTION'), 'TYPE' => 'string', 'REQUIRED' => false);
		$fields[] = array('ID' => 'SOURCE_DESCRIPTION', 'NAME' => GetMessage('CRM_FIELD_SOURCE_DESCRIPTION'), 'TYPE' => 'string', 'REQUIRED' => false);

		$CCrmUserType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], CCrmLead::$sUFEntityID);
		$CCrmUserType->AddRestServiceFields($fields);

		return self::_out(array('error' => 201, 'FIELDS' => $fields));
	}

	/* private section */

	private static function _GetStatusList()
	{
		$ar = CCrmStatus::GetStatusList('STATUS');
		$list = array();

		foreach ($ar as $key => $value)
		{
			$list[] = array('ID' => $key, 'NAME' => $value);
		}

		return $list;
	}

	private static function _GetCurrencyList()
	{
		$ar = CCrmCurrencyHelper::PrepareListItems();
		$list = array();

		foreach ($ar as $key => $value)
		{
			$list[] = array('ID' => $key, 'NAME' => $value);
		}

		return $list;
	}

	private static function _GetSourceList()
	{
		$ar = CCrmStatus::GetStatusListEx('SOURCE');
		$list = array();

		foreach ($ar as $key => $value)
		{
			$list[] = array('ID' => $key, 'NAME' => $value);
		}

		return $list;
	}

	private static function _out($data)
	{
		global $APPLICATION;

		if (self::$authHash)
		{
			$data['AUTH'] = self::$authHash;
		}

		return self::$bReturnObject ? $data : $APPLICATION->ConvertCharset(CUtil::PhpToJsObject($data), LANG_CHARSET, 'UTF-8');
	}
}
?>