<?php
IncludeModuleLangFile(__FILE__);
use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\Integration\StorageType;
class CAllCrmQuote
{
	public static $sUFEntityID = 'CRM_QUOTE';
	protected static $TYPE_NAME = 'QUOTE';
	private static $QUOTE_STATUSES = null;
	private static $STORAGE_TYPE_ID = CCrmQuoteStorageType::Undefined;
	private static $clientFields = array(
		'CLIENT_TITLE', 'CLIENT_ADDR', 'CLIENT_TP_ID', 'CLIENT_TPA_ID', 'CLIENT_CONTACT', 'CLIENT_EMAIL', 'CLIENT_PHONE'
	);

	public $LAST_ERROR = '';
	public $cPerms = null;
	protected $bCheckPermission = true;

	const TABLE_ALIAS = 'Q';
	const OWNER_TYPE = self::TABLE_ALIAS;

	public function __construct($bCheckPermission = true)
	{
		$this->bCheckPermission = $bCheckPermission;
		$this->cPerms = CCrmPerms::GetCurrentUserPermissions();
	}

	public function Add(&$arFields, $bUpdateSearch = true, $options = array())
	{
		global $DB;

		if(!is_array($options))
		{
			$options = array();
		}

		$this->LAST_ERROR = '';
		$iUserId = CCrmSecurityHelper::GetCurrentUserID();

		if (isset($arFields['ID']))
			unset($arFields['ID']);

		if (isset($arFields['DATE_CREATE']))
			unset($arFields['DATE_CREATE']);
		$arFields['~DATE_CREATE'] = $DB->CurrentTimeFunction();
		$arFields['~DATE_MODIFY'] = $DB->CurrentTimeFunction();

		if (!isset($arFields['CREATED_BY_ID']) || (int)$arFields['CREATED_BY_ID'] <= 0)
			$arFields['CREATED_BY_ID'] = $iUserId;
		if (!isset($arFields['MODIFY_BY_ID']) || (int)$arFields['MODIFY_BY_ID'] <= 0)
			$arFields['MODIFY_BY_ID'] = $iUserId;

		if(isset($arFields['ASSIGNED_BY_ID']) && is_array($arFields['ASSIGNED_BY_ID']))
		{
			$arFields['ASSIGNED_BY_ID'] = count($arFields['ASSIGNED_BY_ID']) > 0 ? intval($arFields['ASSIGNED_BY_ID'][0]) : $iUserId;
		}

		if (!isset($arFields['ASSIGNED_BY_ID']) || (int)$arFields['ASSIGNED_BY_ID'] <= 0)
			$arFields['ASSIGNED_BY_ID'] = $iUserId;

		// person type
		if (!isset($arFields['PERSON_TYPE_ID']) || intval($arFields['PERSON_TYPE_ID']) <= 0)
		{
			$arFields['PERSON_TYPE_ID'] = 0;
			$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
			if (isset($arPersonTypes['CONTACT']) && (!isset($arFields['COMPANY_ID']) || intval($arFields['COMPANY_ID']) <= 0))
				$arFields['PERSON_TYPE_ID'] = intval($arPersonTypes['CONTACT']);
			else if (isset($arPersonTypes['COMPANY']) && isset($arFields['COMPANY_ID']) && intval($arFields['COMPANY_ID']) > 0)
				$arFields['PERSON_TYPE_ID'] = intval($arPersonTypes['COMPANY']);
		}

		// storage type
		$storageTypeID = isset($arFields['STORAGE_TYPE_ID'])
			? intval($arFields['STORAGE_TYPE_ID']) : CCrmQuoteStorageType::Undefined;
		if($storageTypeID === CCrmQuoteStorageType::Undefined
			|| !CCrmQuoteStorageType::IsDefined($storageTypeID))
		{
			$storageTypeID = self::GetDefaultStorageTypeID();
		}
		$arFields['STORAGE_TYPE_ID'] = $storageTypeID;


		// storage elements
		$storageElementIDs = (isset($arFields['STORAGE_ELEMENT_IDS']) && is_array($arFields['STORAGE_ELEMENT_IDS']))
			? $arFields['STORAGE_ELEMENT_IDS'] : null;
		$arFields['STORAGE_ELEMENT_IDS'] = null;
		if ($storageElementIDs !== null)
		{
			$storageElementIDs = self::NormalizeStorageElementIDs($storageElementIDs);
			$arFields['STORAGE_ELEMENT_IDS'] = serialize($storageElementIDs);
		}

		if (!$this->CheckFields($arFields, false, $options))
		{
			$result = false;
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		}
		else
		{
			if (!isset($arFields['STATUS_ID']))
				$arFields['STATUS_ID'] = 'DRAFT';
			$arAttr = array();
			if (!empty($arFields['STATUS_ID']))
				$arAttr['STATUS_ID'] = $arFields['STATUS_ID'];
			if (!empty($arFields['OPENED']))
				$arAttr['OPENED'] = $arFields['OPENED'];

			$sPermission = 'ADD';
			if (isset($arFields['PERMISSION']))
			{
				if ($arFields['PERMISSION'] == 'IMPORT')
					$sPermission = 'IMPORT';
				unset($arFields['PERMISSION']);
			}

			if($this->bCheckPermission)
			{
				$arEntityAttr = self::BuildEntityAttr($iUserId, $arAttr);
				$userPerms =  $iUserId == CCrmPerms::GetCurrentUserID() ? $this->cPerms : CCrmPerms::GetUserPermissions($iUserId);
				$sEntityPerm = $userPerms->GetPermType('QUOTE', $sPermission, $arEntityAttr);
				if ($sEntityPerm == BX_CRM_PERM_NONE)
				{
					$this->LAST_ERROR = GetMessage('CRM_PERMISSION_DENIED');
					$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					return false;
				}

				$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
				if ($sEntityPerm == BX_CRM_PERM_SELF && $assignedByID != $iUserId)
				{
					$arFields['ASSIGNED_BY_ID'] = $iUserId;
				}
				if ($sEntityPerm == BX_CRM_PERM_OPEN && $iUserId == $assignedByID)
				{
					$arFields['OPENED'] = 'Y';
				}
			}

			$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
			$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
			$userPerms =  $assignedByID == CCrmPerms::GetCurrentUserID() ? $this->cPerms : CCrmPerms::GetUserPermissions($assignedByID);
			$sEntityPerm = $userPerms->GetPermType('QUOTE', $sPermission, $arEntityAttr);
			$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);

			// Calculation of Account Data
			$accData = CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : null,
					'SUM' => isset($arFields['OPPORTUNITY']) ? $arFields['OPPORTUNITY'] : null,
					'EXCH_RATE' => isset($arFields['EXCH_RATE']) ? $arFields['EXCH_RATE'] : null
				)
			);

			if(is_array($accData))
			{
				$arFields['ACCOUNT_CURRENCY_ID'] = $accData['ACCOUNT_CURRENCY_ID'];
				$arFields['OPPORTUNITY_ACCOUNT'] = $accData['ACCOUNT_SUM'];
			}

			$accData = CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : null,
					'SUM' => isset($arFields['TAX_VALUE']) ? $arFields['TAX_VALUE'] : null,
					'EXCH_RATE' => isset($arFields['EXCH_RATE']) ? $arFields['EXCH_RATE'] : null
				)
			);

			if(is_array($accData))
				$arFields['TAX_VALUE_ACCOUNT'] = $accData['ACCOUNT_SUM'];

			$arFields['CLOSED'] = self::GetStatusSemantics($arFields['STATUS_ID']) === 'process' ? 'N' : 'Y';

			$now = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', SITE_ID);
			if (!isset($arFields['BEGINDATE'][0]))
			{
				$arFields['BEGINDATE'] = $now;
			}

			if($arFields['CLOSED'] === 'Y'
				&& (!isset($arFields['CLOSEDATE']) || $arFields['CLOSEDATE'] === ''))
			{
				$arFields['CLOSEDATE'] = $now;
			}

			foreach (GetModuleEvents('crm', 'OnBeforeCrmQuoteAdd', true) as $arEvent)
			{
				if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				{
					if(isset($arFields['RESULT_MESSAGE']))
					{
						$this->LAST_ERROR = $arFields['RESULT_MESSAGE'];
					}
					else
					{
						$this->LAST_ERROR = GetMessage('CRM_QUOTE_CREATION_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					}
					return false;
				}
			}

			$clobFieldNames = array('COMMENTS', 'CONTENT', 'STORAGE_ELEMENT_IDS');
			$clobFields = array();
			foreach ($clobFieldNames as $fieldName)
			{
				if (array_key_exists($fieldName, $arFields))
					$clobFields[] = $fieldName;
			}

			$ID = intval($DB->Add('b_crm_quote', $arFields, $clobFields, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__));

			if (!self::SetQuoteNumber($ID))
			{
				$this->LAST_ERROR = GetMessage('CRM_ERROR_QUOTE_NUMBER_IS_NOT_SET');
				$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
				return false;
			}

			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => true));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);
			CCrmPerms::UpdateEntityAttr('QUOTE', $ID, $arEntityAttr);

			if(is_array($storageElementIDs))
			{
				CCrmQuote::DoSaveElementIDs($ID, $storageTypeID, $storageElementIDs);
			}
			unset($storageTypeID, $storageElementIDs);

			if($bUpdateSearch)
			{
				$arFilterTmp = Array('ID' => $ID);
				if (!$this->bCheckPermission)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";
				CCrmSearch::UpdateSearch($arFilterTmp, 'QUOTE', true);
			}

			$result = $arFields['ID'] = $ID;

			if (isset($GLOBALS["USER"]) && isset($arFields['COMPANY_ID']) && intval($arFields['COMPANY_ID']) > 0)
			{
				if (!class_exists('CUserOptions'))
					include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/'.$GLOBALS['DBType'].'/favorites.php');

				CUserOptions::SetOption('crm', 'crm_company_search', array('last_selected' => $arFields['COMPANY_ID']));
			}

			if (isset($GLOBALS["USER"]) && isset($arFields['CONTACT_ID']) && intval($arFields['CONTACT_ID']) > 0)
			{
				if (!class_exists('CUserOptions'))
					include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/classes/".$GLOBALS['DBType']."/favorites.php");

				CUserOptions::SetOption("crm", "crm_contact_search", array('last_selected' => $arFields['CONTACT_ID']));
			}

			if (isset($arFields['FM']) && is_array($arFields['FM']))
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->SetFields('QUOTE', $ID, $arFields['FM']);
			}

			if(isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true)
			{
				$opportunity = round((isset($arFields['OPPORTUNITY']) ? doubleval($arFields['OPPORTUNITY']) : 0.0), 2);
				$currencyID = isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : '';
				if($currencyID === '')
				{
					$currencyID = CCrmCurrency::GetBaseCurrencyID();
				}
				$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
				$createdByID = intval($arFields['CREATED_BY_ID']);

				$liveFeedFields = array(
					'USER_ID' => $createdByID,
					'ENTITY_TYPE_ID' => CCrmOwnerType::Quote,
					'ENTITY_ID' => $ID,
					'TITLE' => GetMessage('CRM_QUOTE_EVENT_ADD'),
					'MESSAGE' => '',
					'PARAMS' => array(
						'TITLE' => isset($arFields['TITLE']) ? $arFields['TITLE'] : '',
						'STATUS_ID' => isset($arFields['STATUS_ID']) ? $arFields['STATUS_ID'] : '',
						'OPPORTUNITY' => strval($opportunity),
						'CURRENCY_ID' => $currencyID,
						'COMPANY_ID' => isset($arFields['COMPANY_ID']) ? intval($arFields['COMPANY_ID']) : 0,
						'CONTACT_ID' => isset($arFields['CONTACT_ID']) ? intval($arFields['CONTACT_ID']) : 0,
						'AUTHOR_ID' => intval($arFields['CREATED_BY_ID']),
						'RESPONSIBLE_ID' => $assignedByID
					)
				);

				//Register contact & company relations
				$contactID = isset($arFields['CONTACT_ID']) ? intval($arFields['CONTACT_ID']) : 0;
				$companyID = isset($arFields['COMPANY_ID']) ? intval($arFields['COMPANY_ID']) : 0;
				if($contactID > 0 || $companyID > 0)
				{
					$liveFeedFields['PARENTS'] = array();
					if($contactID > 0)
					{
						$liveFeedFields['PARENTS'][] = array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
							'ENTITY_ID' => $contactID
						);
					}

					if($companyID > 0)
					{
						$liveFeedFields['PARENTS'][] = array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
							'ENTITY_ID' => $companyID
						);
					}
				}

				CCrmSonetSubscription::RegisterSubscription(
					CCrmOwnerType::Quote,
					$ID,
					CCrmSonetSubscriptionType::Responsibility,
					$assignedByID
				);
				$logEventID = CCrmLiveFeed::CreateLogEvent($liveFeedFields, CCrmLiveFeedEvent::Add);

				if (
					$logEventID
					&& $assignedByID != $createdByID
					&& CModule::IncludeModule("im")
				)
				{
					$url = CCrmOwnerType::GetShowUrl(CCrmOwnerType::Quote, $ID);
					$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"TO_USER_ID" => $assignedByID,
						"FROM_USER_ID" => $createdByID,
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "crm",
						"LOG_ID" => $logEventID,
						"NOTIFY_EVENT" => "quote_add",
						"NOTIFY_TAG" => "CRM|QUOTE_RESPONSIBLE|".$ID,
						"NOTIFY_MESSAGE" => GetMessage("CRM_QUOTE_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>")),
						"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_QUOTE_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['TITLE'])))." (".$serverName.$url.")"
					);
					CIMNotify::Add($arMessageFields);
				}
			}

			foreach (GetModuleEvents('crm', 'OnAfterCrmQuoteAdd', true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array(&$arFields));
		}

		return $result;
	}

	public function CheckFields(&$arFields, $ID = false, $options = array())
	{
		global $APPLICATION, $USER_FIELD_MANAGER, $DB;
		$this->LAST_ERROR = '';

		/*if (($ID == false || isset($arFields['TITLE'])) && empty($arFields['TITLE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_TITLE')))."<br />\n";*/
		if (isset($arFields['TITLE']) && strlen($arFields['TITLE']) > 255)
		{
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_TITLE')))."<br />\n";
		}

		if ($ID !== false && isset($arFields['QUOTE_NUMBER']))
		{
			/*if (strlen($arFields['QUOTE_NUMBER']) <= 0)
			{
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_QUOTE_NUMBER')))."<br />\n";
			}
			else*/ if (strlen($arFields['QUOTE_NUMBER']) > 100)
			{
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_QUOTE_NUMBER')))."<br />\n";
			}
			else
			{
				$dbres = $DB->Query("SELECT ID, QUOTE_NUMBER FROM b_crm_quote WHERE QUOTE_NUMBER = '".$DB->ForSql($arFields["QUOTE_NUMBER"])."'", true);
				if ($arRes = $dbres->GetNext())
				{
					if (is_array($arRes) && $arRes["ID"] != $ID)
					{
						$this->LAST_ERROR .= GetMessage('CRM_ERROR_QUOTE_NUMBER_EXISTS')."<br />\n";
					}
				}
				unset($arRes, $dbres);
			}
		}

		if (!empty($arFields['BEGINDATE']) && !CheckDateTime($arFields['BEGINDATE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_BEGINDATE')))."<br />\n";

		if (!empty($arFields['CLOSEDATE']) && !CheckDateTime($arFields['CLOSEDATE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_CLOSEDATE')))."<br />\n";

		/*if (!empty($arFields['EVENT_DATE']) && !CheckDateTime($arFields['EVENT_DATE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_EVENT_DATE')))."<br />\n";*/

		if(is_string($arFields['OPPORTUNITY']) && $arFields['OPPORTUNITY'] !== '')
		{
			$arFields['OPPORTUNITY'] = str_replace(array(',', ' '), array('.', ''), $arFields['OPPORTUNITY']);
			//HACK: MSSQL returns '.00' for zero value
			if(strpos($arFields['OPPORTUNITY'], '.') === 0)
			{
				$arFields['OPPORTUNITY'] = '0'.$arFields['OPPORTUNITY'];
			}

			if (!preg_match('/^\d{1,}(\.\d{1,})?$/', $arFields['OPPORTUNITY']))
			{
				$this->LAST_ERROR .= GetMessage('CRM_QUOTE_FIELD_OPPORTUNITY_INVALID')."<br />\n";
			}
		}

		// storage type id
		if(!isset($arFields['STORAGE_TYPE_ID'])
			|| $arFields['STORAGE_TYPE_ID'] === CCrmQuoteStorageType::Undefined
			|| !CCrmQuoteStorageType::IsDefined($arFields['STORAGE_TYPE_ID']))
		{
			$arFields['STORAGE_TYPE_ID'] = self::GetDefaultStorageTypeID();
		}

		foreach (self::$clientFields as $fieldName)
		{
			if (isset($arFields[$fieldName]) && strlen($arFields[$fieldName]) > 255)
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_'.$fieldName)))."<br />\n";
		}
		unset($fieldName);

		/*if (!empty($arFields['PROBABILITY']))
		{
			$arFields['PROBABILITY'] = intval($arFields['PROBABILITY']);
			if ($arFields['PROBABILITY'] > 100)
				$arFields['PROBABILITY'] = 100;
		}*/

		// check person type
		$personTypeId = 0;
		if (isset($arFields['PERSON_TYPE_ID']))
			$personTypeId = intval($arFields['PERSON_TYPE_ID']);
		$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
		$arPersonTypeEnum = array();
		if (isset($arPersonTypes['CONTACT']))
			$arPersonTypeEnum[] = intval($arPersonTypes['CONTACT']);
		if (isset($arPersonTypes['COMPANY']))
			$arPersonTypeEnum[] = intval($arPersonTypes['COMPANY']);
		if ($personTypeId <= 0 || !in_array($personTypeId, $arPersonTypeEnum, true))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_PERSON_TYPE_ID')))."<br />\n";
		unset($personTypeId, $arPersonTypes, $arPersonTypeEnum);

		$enableUserFildCheck = !(is_array($options) && isset($options['DISABLE_USER_FIELD_CHECK']) && $options['DISABLE_USER_FIELD_CHECK'] === true);
		if ($enableUserFildCheck)
		{
			// We have to prepare field data before check (issue #22966)
			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $USER_FIELD_MANAGER, array('IS_NEW' => ($ID == false)));
			if(!$USER_FIELD_MANAGER->CheckFields(self::$sUFEntityID, $ID, $arFields))
			{
				$e = $APPLICATION->GetException();
				$this->LAST_ERROR .= $e->GetString();
			}
		}

		if (strlen($this->LAST_ERROR) > 0)
			return false;

		return true;
	}

	static public function BuildEntityAttr($userID, $arAttr = array())
	{
		$userID = (int)$userID;
		$arResult = array("U{$userID}");
		if(isset($arAttr['OPENED']) && $arAttr['OPENED'] == 'Y')
		{
			$arResult[] = 'O';
		}

		$statusID = isset($arAttr['STATUS_ID']) ? $arAttr['STATUS_ID'] : '';
		if($statusID !== '')
		{
			$arResult[] = "STATUS_ID{$statusID}";
		}

		$arUserAttr = CCrmPerms::BuildUserEntityAttr($userID);
		return array_merge($arResult, $arUserAttr['INTRANET']);
	}
	static public function RebuildEntityAccessAttrs($IDs)
	{
		if(!is_array($IDs))
		{
			$IDs = array($IDs);
		}

		$dbResult = self::GetList(
			array(),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'ASSIGNED_BY_ID', 'OPENED', 'STATUS_ID')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		while($fields = $dbResult->Fetch())
		{
			$ID = intval($fields['ID']);
			$assignedByID = isset($fields['ASSIGNED_BY_ID']) ? intval($fields['ASSIGNED_BY_ID']) : 0;
			if($assignedByID <= 0)
			{
				continue;
			}

			$attrs = array();
			if(isset($fields['OPENED']))
			{
				$attrs['OPENED'] = $fields['OPENED'];
			}

			if(isset($fields['STATUS_ID']))
			{
				$attrs['STATUS_ID'] = $fields['STATUS_ID'];
			}

			$entityAttrs = self::BuildEntityAttr($assignedByID, $attrs);
			CCrmPerms::UpdateEntityAttr('QUOTE', $ID, $entityAttrs);
		}
	}
	private function PrepareEntityAttrs(&$arEntityAttr, $entityPermType)
	{
		// Ensure that entity accessable for user restricted by BX_CRM_PERM_OPEN
		if($entityPermType === BX_CRM_PERM_OPEN && !in_array('O', $arEntityAttr, true))
		{
			$arEntityAttr[] = 'O';
		}
	}

	public function Update($ID, &$arFields, $bCompare = true, $bUpdateSearch = true, $options = array())
	{
		global $DB;

		$this->LAST_ERROR = '';
		$ID = (int) $ID;
		if(!is_array($options))
		{
			$options = array();
		}

		$arFilterTmp = array('ID' => $ID);
		if (!$this->bCheckPermission)
			$arFilterTmp['CHECK_PERMISSIONS'] = 'N';

		$obRes = self::GetList(array(), $arFilterTmp);
		if (!($arRow = $obRes->Fetch()))
			return false;

		$iUserId = CCrmSecurityHelper::GetCurrentUserID();

		if (isset($arFields['DATE_CREATE']))
			unset($arFields['DATE_CREATE']);
		if (isset($arFields['DATE_MODIFY']))
			unset($arFields['DATE_MODIFY']);
		$arFields['~DATE_MODIFY'] = $DB->CurrentTimeFunction();

		if (!isset($arFields['MODIFY_BY_ID']) || $arFields['MODIFY_BY_ID'] <= 0)
			$arFields['MODIFY_BY_ID'] = $iUserId;
		if (isset($arFields['ASSIGNED_BY_ID']) && $arFields['ASSIGNED_BY_ID'] <= 0)
			unset($arFields['ASSIGNED_BY_ID']);

		// number
		if (!isset($arFields['QUOTE_NUMBER']) || empty($arFields['QUOTE_NUMBER']))
		{
			$arFields['QUOTE_NUMBER'] = isset($arRow['QUOTE_NUMBER']) ? $arRow['QUOTE_NUMBER'] : '';
			if (empty($arFields['QUOTE_NUMBER']))
				$arFields['QUOTE_NUMBER'] = strval($ID);
		}

		// person type
		if (!isset($arFields['PERSON_TYPE_ID']) || intval($arFields['PERSON_TYPE_ID']) <= 0)
		{
			$companyId = isset($arFields['COMPANY_ID']) ? intval($arFields['COMPANY_ID']) : (isset($arRow['COMPANY_ID']) ? intval($arRow['COMPANY_ID']) : 0);
			$arFields['PERSON_TYPE_ID'] = intval($arRow['PERSON_TYPE_ID']);
			$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
			if (isset($arPersonTypes['CONTACT']) && isset($arPersonTypes['COMPANY']))
			{
				if ($companyId <= 0)
					$arFields['PERSON_TYPE_ID'] = intval($arPersonTypes['CONTACT']);
				else
					$arFields['PERSON_TYPE_ID'] = intval($arPersonTypes['COMPANY']);
			}
			unset($companyId, $arPersonTypes);
		}

		// storage type id
		$storageTypeID = isset($arFields['STORAGE_TYPE_ID'])
			? intval($arFields['STORAGE_TYPE_ID']) : CCrmQuoteStorageType::Undefined;
		if($storageTypeID === CCrmQuoteStorageType::Undefined
			|| !CCrmQuoteStorageType::IsDefined($storageTypeID))
		{
			$storageTypeID = isset($arRow['STORAGE_TYPE_ID'])
				? $arRow['STORAGE_TYPE_ID'] : CCrmQuoteStorageType::Undefined;
			if($storageTypeID === CCrmQuoteStorageType::Undefined
				|| !CCrmQuoteStorageType::IsDefined($storageTypeID))
			{
				$storageTypeID = CCrmQuote::GetDefaultStorageTypeID();
			}
		}
		$arFields['STORAGE_TYPE_ID'] = $storageTypeID;

		// storage elements
		$storageElementIDs = (isset($arFields['STORAGE_ELEMENT_IDS']) && is_array($arFields['STORAGE_ELEMENT_IDS']))
			? $arFields['STORAGE_ELEMENT_IDS'] : null;
		$arFields['STORAGE_ELEMENT_IDS'] = null;
		if ($storageElementIDs !== null)
		{
			$storageElementIDs = self::NormalizeStorageElementIDs($storageElementIDs);
			$arFields['STORAGE_ELEMENT_IDS'] = serialize($storageElementIDs);
		}

		$assignedByID = (int)(isset($arFields['ASSIGNED_BY_ID']) ? $arFields['ASSIGNED_BY_ID'] : $arRow['ASSIGNED_BY_ID']);

		$bResult = false;
		if (!$this->CheckFields($arFields, $ID, $options))
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		else
		{
			if($this->bCheckPermission && !CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $this->cPerms))
			{
				$this->LAST_ERROR = GetMessage('CRM_PERMISSION_DENIED');
				$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
				return false;
			}

			$arAttr = array();
			$arAttr['STATUS_ID'] = !empty($arFields['STATUS_ID']) ? $arFields['STATUS_ID'] : $arRow['STATUS_ID'];
			$arAttr['OPENED'] = !empty($arFields['OPENED']) ? $arFields['OPENED'] : $arRow['OPENED'];
			$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
			$sEntityPerm = $this->cPerms->GetPermType('QUOTE', 'WRITE', $arEntityAttr);
			$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);
			//Prevent 'OPENED' field change by user restricted by BX_CRM_PERM_OPEN permission
			if($sEntityPerm === BX_CRM_PERM_OPEN && isset($arFields['OPENED']) && $arFields['OPENED'] !== 'Y' && $assignedByID !== $iUserId)
			{
				$arFields['OPENED'] = 'Y';
			}

			if (isset($arFields['ASSIGNED_BY_ID']) && $arRow['ASSIGNED_BY_ID'] != $arFields['ASSIGNED_BY_ID'])
				CCrmEvent::SetAssignedByElement($arFields['ASSIGNED_BY_ID'], 'QUOTE', $ID);

			$sonetEventData = array();
			if ($bCompare)
			{
				$arEvents = self::CompareFields($arRow, $arFields, $this->bCheckPermission);
				foreach($arEvents as $arEvent)
				{
					$arEvent['ENTITY_TYPE'] = 'QUOTE';
					$arEvent['ENTITY_ID'] = $ID;
					$arEvent['EVENT_TYPE'] = 1;
					if (!isset($arEvent['USER_ID']))
						$arEvent['USER_ID'] = $iUserId;

					$CCrmEvent = new CCrmEvent();
					$eventID = $CCrmEvent->Add($arEvent, $this->bCheckPermission);
					if(is_int($eventID) && $eventID > 0)
					{
						$fieldID = isset($arEvent['ENTITY_FIELD']) ? $arEvent['ENTITY_FIELD'] : '';
						if($fieldID === '')
						{
							continue;
						}

						switch($fieldID)
						{
							case 'STATUS_ID':
							{
								$sonetEventData[CCrmLiveFeedEvent::Progress] = array(
									'TYPE' => CCrmLiveFeedEvent::Progress,
									'FIELDS' => array(
										//'EVENT_ID' => $eventID,
										'TITLE' => GetMessage('CRM_QUOTE_EVENT_UPDATE_STATUS'),
										'MESSAGE' => '',
										'PARAMS' => array(
											'START_STATUS_ID' => $arRow['STATUS_ID'],
											'FINAL_STATUS_ID' => $arFields['STATUS_ID']
										)
									)
								);
							}
								break;
							case 'ASSIGNED_BY_ID':
							{
								$sonetEventData[CCrmLiveFeedEvent::Responsible] = array(
									'TYPE' => CCrmLiveFeedEvent::Responsible,
									'FIELDS' => array(
										//'EVENT_ID' => $eventID,
										'TITLE' => GetMessage('CRM_QUOTE_EVENT_UPDATE_ASSIGNED_BY'),
										'MESSAGE' => '',
										'PARAMS' => array(
											'START_RESPONSIBLE_ID' => $arRow['ASSIGNED_BY_ID'],
											'FINAL_RESPONSIBLE_ID' => $arFields['ASSIGNED_BY_ID']
										)
									)
								);
							}
								break;
							case 'CONTACT_ID':
							case 'COMPANY_ID':
							{
								if(!isset($sonetEventData[CCrmLiveFeedEvent::Client]))
								{
									$oldContactID = isset($arRow['CONTACT_ID']) ? intval($arRow['CONTACT_ID']) : 0;
									$oldCompanyID = isset($arRow['COMPANY_ID']) ? intval($arRow['COMPANY_ID']) : 0;

									$sonetEventData[CCrmLiveFeedEvent::Client] = array(
										'CODE'=> 'CLIENT',
										'TYPE' => CCrmLiveFeedEvent::Client,
										'FIELDS' => array(
											//'EVENT_ID' => $eventID,
											'TITLE' => GetMessage('CRM_QUOTE_EVENT_UPDATE_CLIENT'),
											'MESSAGE' => '',
											'PARAMS' => array(
												'START_CLIENT_CONTACT_ID' => $oldContactID,
												'FINAL_CLIENT_CONTACT_ID' => isset($arFields['CONTACT_ID']) ? intval($arFields['CONTACT_ID']) : $oldContactID,
												'START_CLIENT_COMPANY_ID' => $oldCompanyID,
												'FINAL_CLIENT_COMPANY_ID' => isset($arFields['COMPANY_ID']) ? intval($arFields['COMPANY_ID']) : $oldCompanyID
											)
										)
									);
								}
							}
								break;
							case 'TITLE':
							{
								$sonetEventData[CCrmLiveFeedEvent::Denomination] = array(
									'TYPE' => CCrmLiveFeedEvent::Denomination,
									'FIELDS' => array(
										//'EVENT_ID' => $eventID,
										'TITLE' => GetMessage('CRM_QUOTE_EVENT_UPDATE_TITLE'),
										'MESSAGE' => '',
										'PARAMS' => array(
											'START_TITLE' => $arRow['TITLE'],
											'FINAL_TITLE' => $arFields['TITLE']
										)
									)
								);
							}
								break;
						}
					}
				}

				// CHECK IF COMPANY/CONTACT WAS ADDED/REMOVED
				if(!isset($sonetEventData[CCrmLiveFeedEvent::Client])
					&& ((!isset($arRow['COMPANY_ID']) && isset($arFields['COMPANY_ID']))
						|| (isset($arRow['COMPANY_ID']) && !isset($arFields['COMPANY_ID']))
						|| (!isset($arRow['CONTACT_ID']) && isset($arFields['CONTACT_ID']))
						|| (isset($arRow['CONTACT_ID']) && !isset($arFields['CONTACT_ID']))))
				{
					$sonetEventData[CCrmLiveFeedEvent::Client] = array(
						'CODE'=> 'CLIENT',
						'TYPE' => CCrmLiveFeedEvent::Client,
						'FIELDS' => array(
							//'EVENT_ID' => $eventID,
							'TITLE' => GetMessage('CRM_QUOTE_EVENT_UPDATE_CLIENT'),
							'MESSAGE' => '',
							'PARAMS' => array(
								'START_CLIENT_CONTACT_ID' => isset($arRow['CONTACT_ID']) ? intval($arRow['CONTACT_ID']) : 0,
								'FINAL_CLIENT_CONTACT_ID' => isset($arFields['CONTACT_ID']) ? intval($arFields['CONTACT_ID']) : 0,
								'START_CLIENT_COMPANY_ID' => isset($arRow['COMPANY_ID']) ? intval($arRow['COMPANY_ID']) : 0,
								'FINAL_CLIENT_COMPANY_ID' => isset($arFields['COMPANY_ID']) ? intval($arFields['COMPANY_ID']) : 0
							)
						)
					);
				}
			}

			// Calculation of Account Data
			$accData = CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : (isset($arRow['CURRENCY_ID']) ? $arRow['CURRENCY_ID'] : null),
					'SUM' => isset($arFields['OPPORTUNITY']) ? $arFields['OPPORTUNITY'] : (isset($arRow['OPPORTUNITY']) ? $arRow['OPPORTUNITY'] : null),
					'EXCH_RATE' => isset($arFields['EXCH_RATE']) ? $arFields['EXCH_RATE'] : (isset($arRow['EXCH_RATE']) ? $arRow['EXCH_RATE'] : null)
				)
			);

			if(is_array($accData))
			{
				$arFields['ACCOUNT_CURRENCY_ID'] = $accData['ACCOUNT_CURRENCY_ID'];
				$arFields['OPPORTUNITY_ACCOUNT'] = $accData['ACCOUNT_SUM'];
			}

			$accData = CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : (isset($arRow['CURRENCY_ID']) ? $arRow['CURRENCY_ID'] : null),
					'SUM' => isset($arFields['TAX_VALUE']) ? $arFields['TAX_VALUE'] : (isset($arRow['TAX_VALUE']) ? $arRow['TAX_VALUE'] : null),
					'EXCH_RATE' => isset($arFields['EXCH_RATE']) ? $arFields['EXCH_RATE'] : (isset($arRow['EXCH_RATE']) ? $arRow['EXCH_RATE'] : null)
				)
			);

			if(is_array($accData))
				$arFields['TAX_VALUE_ACCOUNT'] = $accData['ACCOUNT_SUM'];

			if(isset($arFields['STATUS_ID']))
			{
				$arFields['CLOSED'] = self::GetStatusSemantics($arFields['STATUS_ID']) === 'process' ? 'N' : 'Y';
			}

			if (isset($arFields['BEGINDATE']) && !isset($arFields['BEGINDATE'][0]))
			{
				unset($arFields['BEGINDATE']);
			}

			if(isset($arFields['CLOSED'])
				&& $arFields['CLOSED'] === 'Y'
				&& (!isset($arFields['CLOSEDATE'])
					|| $arFields['CLOSEDATE'] === ''))
			{
				$arFields['CLOSEDATE'] = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', SITE_ID);
			}

			unset($arFields['ID']);

			foreach (GetModuleEvents('crm', 'OnBeforeCrmQuoteUpdate', true) as $arEvent)
			{
				if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				{
					if(isset($arFields['RESULT_MESSAGE']))
					{
						$this->LAST_ERROR = $arFields['RESULT_MESSAGE'];
					}
					else
					{
						$this->LAST_ERROR = GetMessage('CRM_QUOTE_UPDATE_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					}
					return false;
				}
			}

			$sUpdate = $DB->PrepareUpdate('b_crm_quote', $arFields);

			if (strlen($sUpdate) > 0)
			{
				$clobFieldNames = array('COMMENTS', 'CONTENT', 'STORAGE_ELEMENT_IDS');
				$arBinds = array();
				foreach ($clobFieldNames as $fieldName)
				{
					if (array_key_exists($fieldName, $arFields))
						$arBinds[$fieldName] = $arFields[$fieldName];
				}
				unset($fieldName);

				$sql = "UPDATE b_crm_quote SET {$sUpdate} WHERE ID = {$ID}";
				if(!empty($arBinds))
				{
					$DB->QueryBind($sql, $arBinds, false);
				}
				else
				{
					$DB->Query($sql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
				}
				$bResult = true;
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				static $arNameFields = array("TITLE");
				$bClear = false;
				foreach($arNameFields as $val)
				{
					if(isset($arFields[$val]))
					{
						$bClear = true;
						break;
					}
				}
				if ($bClear)
				{
					$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Quote."_".$ID);
				}
			}

			CCrmPerms::UpdateEntityAttr('QUOTE', $ID, $arEntityAttr);

			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => false));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);

			if(is_array($storageElementIDs))
			{
				CCrmQuote::DoSaveElementIDs($ID, $storageTypeID, $storageElementIDs);
			}
			unset($storageTypeID, $storageElementIDs);


			if($bUpdateSearch)
			{
				$arFilterTmp = Array('ID' => $ID);
				if (!$this->bCheckPermission)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";
				CCrmSearch::UpdateSearch($arFilterTmp, 'QUOTE', true);
			}

			$arFields['ID'] = $ID;

			if (isset($arFields['FM']) && is_array($arFields['FM']))
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->SetFields('QUOTE', $ID, $arFields['FM']);
			}

			// Responsible user sync
			//CCrmActivity::Synchronize(CCrmOwnerType::Quote, $ID);

			$registerSonetEvent = isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true;

			if($bResult && isset($arFields['ASSIGNED_BY_ID']))
			{
				CCrmSonetSubscription::ReplaceSubscriptionByEntity(
					CCrmOwnerType::Quote,
					$ID,
					CCrmSonetSubscriptionType::Responsibility,
					$arFields['ASSIGNED_BY_ID'],
					$arRow['ASSIGNED_BY_ID'],
					$registerSonetEvent
				);
			}

			if($bResult && $bCompare && $registerSonetEvent && !empty($sonetEventData))
			{
				//CONTACT
				$newContactID = isset($arFields['CONTACT_ID']) ? intval($arFields['CONTACT_ID']) : 0;
				$oldContactID = isset($arRow['CONTACT_ID']) ? intval($arRow['CONTACT_ID']) : 0;
				$contactID = $newContactID > 0 ? $newContactID : $oldContactID;

				//COMPANY
				$newCompanyID = isset($arFields['COMPANY_ID']) ? intval($arFields['COMPANY_ID']) : 0;
				$oldCompanyID = isset($arRow['COMPANY_ID']) ? intval($arRow['COMPANY_ID']) : 0;
				$companyID = $newCompanyID > 0 ? $newCompanyID : $oldCompanyID;

				$modifiedByID = intval($arFields['MODIFY_BY_ID']);
				foreach($sonetEventData as &$sonetEvent)
				{
					$sonetEventType = $sonetEvent['TYPE'];
					$sonetEventCode = isset($sonetEvent['CODE']) ? $sonetEvent['CODE'] : '';

					$sonetEventFields = &$sonetEvent['FIELDS'];
					$sonetEventFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Quote;
					$sonetEventFields['ENTITY_ID'] = $ID;
					$sonetEventFields['USER_ID'] = $modifiedByID;

					//Register contact & company relations
					if($sonetEventCode === 'CLIENT')
					{
						$sonetEventFields['PARENTS'] = array();
						//If contact changed bind events to old and new contacts
						if($oldContactID !== $newContactID)
						{
							if($oldContactID > 0)
							{
								$sonetEventFields['PARENTS'][] = array(
									'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
									'ENTITY_ID' => $oldContactID
								);
							}

							if($newContactID > 0)
							{
								$sonetEventFields['PARENTS'][] = array(
									'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
									'ENTITY_ID' => $newContactID
								);
							}
						}

						//If company changed bind events to old and new companies
						if($oldCompanyID !== $newCompanyID)
						{
							if($oldCompanyID > 0)
							{
								$sonetEventFields['PARENTS'][] = array(
									'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
									'ENTITY_ID' => $oldCompanyID
								);
							}

							if($newCompanyID > 0)
							{
								$sonetEventFields['PARENTS'][] = array(
									'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
									'ENTITY_ID' => $newCompanyID
								);
							}
						}
					}
					elseif($contactID > 0 || $companyID > 0)
					{
						$sonetEventFields['PARENTS'] = array();
						if($contactID > 0)
						{
							$sonetEventFields['PARENTS'][] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
								'ENTITY_ID' => $contactID
							);
						}

						if($companyID > 0)
						{
							$sonetEventFields['PARENTS'][] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
								'ENTITY_ID' => $companyID
							);
						}
					}

					$logEventID = CCrmLiveFeed::CreateLogEvent($sonetEventFields, $sonetEventType);

					if (
						$logEventID
						&& CModule::IncludeModule("im")
					)
					{
						$url = CCrmOwnerType::GetShowUrl(CCrmOwnerType::Quote, $ID);
						$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

						if (
							$sonetEvent['TYPE'] == CCrmLiveFeedEvent::Responsible
							&& $sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'] != $modifiedByID
						)
						{
							$arMessageFields = array(
								"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
								"TO_USER_ID" => $sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'],
								"FROM_USER_ID" => $modifiedByID,
								"NOTIFY_TYPE" => IM_NOTIFY_FROM,
								"NOTIFY_MODULE" => "crm",
								"LOG_ID" => $logEventID,
								"NOTIFY_EVENT" => "quote_update",
								"NOTIFY_TAG" => "CRM|QUOTE_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_QUOTE_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_QUOTE_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['TITLE'])))." (".$serverName.$url.")"
							);

							CIMNotify::Add($arMessageFields);
						}

						if (
							$sonetEvent['TYPE'] == CCrmLiveFeedEvent::Responsible
							&& $sonetEventFields['PARAMS']['START_RESPONSIBLE_ID'] != $modifiedByID
						)
						{
							$arMessageFields = array(
								"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
								"TO_USER_ID" => $sonetEventFields['PARAMS']['START_RESPONSIBLE_ID'],
								"FROM_USER_ID" => $modifiedByID,
								"NOTIFY_TYPE" => IM_NOTIFY_FROM,
								"NOTIFY_MODULE" => "crm",
								"LOG_ID" => $logEventID,
								"NOTIFY_EVENT" => "quote_update",
								"NOTIFY_TAG" => "CRM|QUOTE_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_QUOTE_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_QUOTE_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['TITLE'])))." (".$serverName.$url.")"
							);

							CIMNotify::Add($arMessageFields);
						}

						if (
							$sonetEvent['TYPE'] == CCrmLiveFeedEvent::Progress
							&& $sonetEventFields['PARAMS']['START_STATUS_ID']
							&& $sonetEventFields['PARAMS']['FINAL_STATUS_ID']

						)
						{
							$assignedByID = (isset($arFields['ASSIGNED_BY_ID']) ? $arFields['ASSIGNED_BY_ID'] : $arRow['ASSIGNED_BY_ID']);
							$infos = CCrmStatus::GetStatus('QUOTE_STATUS');

							if (
								$assignedByID != $modifiedByID
								&& array_key_exists($sonetEventFields['PARAMS']['START_STATUS_ID'], $infos)
								&& array_key_exists($sonetEventFields['PARAMS']['FINAL_STATUS_ID'], $infos)
							)
							{
								$arMessageFields = array(
									"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
									"TO_USER_ID" => $assignedByID,
									"FROM_USER_ID" => $modifiedByID,
									"NOTIFY_TYPE" => IM_NOTIFY_FROM,
									"NOTIFY_MODULE" => "crm",
									"LOG_ID" => $logEventID,
									"NOTIFY_EVENT" => "quote_update",
									"NOTIFY_TAG" => "CRM|QUOTE_PROGRESS|".$ID,
									"NOTIFY_MESSAGE" => GetMessage("CRM_QUOTE_PROGRESS_IM_NOTIFY", Array(
										"#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>",
										"#start_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['START_STATUS_ID']]['NAME']),
										"#final_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['FINAL_STATUS_ID']]['NAME'])
									)),
									"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_QUOTE_PROGRESS_IM_NOTIFY", Array(
											"#title#" => htmlspecialcharsbx($arFields['TITLE']),
											"#start_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['START_STATUS_ID']]['NAME']),
											"#final_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['FINAL_STATUS_ID']]['NAME'])
										))." (".$serverName.$url.")"
								);

								CIMNotify::Add($arMessageFields);
							}
						}

					}

					unset($sonetEventFields);
				}
				unset($sonetEvent);
			}

			if($bResult)
			{
				foreach (GetModuleEvents('crm', 'OnAfterCrmQuoteUpdate', true) as $arEvent)
					ExecuteModuleEventEx($arEvent, array(&$arFields));
			}

			self::PullChange('UPDATE', array('ID' => $ID));
		}
		return $bResult;
	}

	public function Delete($ID, $options = array())
	{
		global $DB, $APPLICATION;

		$ID = intval($ID);
		$iUserId = CCrmSecurityHelper::GetCurrentUserID();

		$sWherePerm = '';
		if ($this->bCheckPermission)
		{
			$arEntityAttr = $this->cPerms->GetEntityAttr('QUOTE', $ID);
			$sEntityPerm = $this->cPerms->GetPermType('QUOTE', 'DELETE', $arEntityAttr[$ID]);
			if ($sEntityPerm == BX_CRM_PERM_NONE)
				return false;
			else if ($sEntityPerm == BX_CRM_PERM_SELF)
				$sWherePerm = " AND ASSIGNED_BY_ID = {$iUserId}";
			else if ($sEntityPerm == BX_CRM_PERM_OPEN)
				$sWherePerm = " AND (OPENED = 'Y' OR ASSIGNED_BY_ID = {$iUserId})";
		}

		$APPLICATION->ResetException();
		foreach (GetModuleEvents('crm', 'OnBeforeCrmQuoteDelete', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if ($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				return false;
			}
		}

		if (!(isset($options['SKIP_FILES']) && $options['SKIP_FILES']))
		{
			if(!self::DeleteStorageElements($ID))
				return false;

			if(!$DB->Query(
				'DELETE FROM '.CCrmQuote::ELEMENT_TABLE_NAME.' WHERE QUOTE_ID = '.$ID,
				false, 'File: '.__FILE__.'<br/>Line: '.__LINE__))
			{
				$APPLICATION->throwException(GetMessage('CRM_QUOTE_ERR_DELETE_STORAGE_ELEMENTS_QUERY'));
				return false;
			}
		}

		$dbRes = $DB->Query("DELETE FROM b_crm_quote WHERE ID = {$ID}{$sWherePerm}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		if (is_object($dbRes) && $dbRes->AffectedRowsCount() > 0)
		{
			$DB->Query("DELETE FROM b_crm_entity_perms WHERE ENTITY='QUOTE' AND ENTITY_ID = $ID", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			$GLOBALS['USER_FIELD_MANAGER']->Delete(self::$sUFEntityID, $ID);
			$CCrmFieldMulti = new CCrmFieldMulti();
			$CCrmFieldMulti->DeleteByElement('QUOTE', $ID);
			$CCrmEvent = new CCrmEvent();
			$CCrmEvent->DeleteByElement('QUOTE', $ID);

			CCrmSearch::DeleteSearch('QUOTE', $ID);

			// Deletion of quote details
			CCrmProductRow::DeleteByOwner(self::OWNER_TYPE, $ID);
			CCrmProductRow::DeleteSettings(self::OWNER_TYPE, $ID);
			/*CCrmActivity::DeleteByOwner(CCrmOwnerType::Quote, $ID);*/

			CCrmSonetSubscription::UnRegisterSubscriptionByEntity(CCrmOwnerType::Quote, $ID);
			CCrmLiveFeed::DeleteLogEvents(
				array(
					'ENTITY_TYPE_ID' => CCrmOwnerType::Quote,
					'ENTITY_ID' => $ID
				)
			);

			self::PullChange('DELETE', array('ID' => $ID));

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Quote."_".$ID);
			}
		}
		return true;
	}

	/**
	 * Generates next quote number according to the scheme selected in the module options (quote_number_...)
	 *
	 * @param int $ID - quote ID
	 * @param string $templateType - quote number template type code
	 * @param string $param - quote number template param
	 * @return mixed - generated number or false
	 */
	public static function GetNextQuoteNumber($ID, $templateType, $param)
	{
		global $DB;
		$value = false;

		switch ($templateType)
		{
			case 'NUMBER':

				$param = intval($param);
				$maxLastID = 0;
				$strSql = '';

				switch(strtolower($DB->type))
				{
					case "mysql":
						$strSql = "SELECT ID, QUOTE_NUMBER FROM b_crm_quote WHERE QUOTE_NUMBER IS NOT NULL ORDER BY ID DESC LIMIT 1";
						break;
					case "oracle":
						$strSql = "SELECT ID, QUOTE_NUMBER FROM b_crm_quote WHERE QUOTE_NUMBER IS NOT NULL AND ROWNUM <= 1 ORDER BY ID DESC";
						break;
					case "mssql":
						$strSql = "SELECT TOP 1 ID, QUOTE_NUMBER FROM b_crm_quote WHERE QUOTE_NUMBER IS NOT NULL ORDER BY ID DESC";
						break;
				}

				$dbres = $DB->Query($strSql, true);
				if ($arRes = $dbres->GetNext())
				{
					if (strlen($arRes["QUOTE_NUMBER"]) === strlen(intval($arRes["QUOTE_NUMBER"])))
						$maxLastID = intval($arRes["QUOTE_NUMBER"]);
				}

				$value = ($maxLastID >= $param) ? $maxLastID + 1 : $param;
				break;

			case 'PREFIX':

				$value = $param.$ID;
				break;

			case 'RANDOM':

				$rand = randString(intval($param), array("ABCDEFGHIJKLNMOPQRSTUVWXYZ", "0123456789"));
				$dbres = $DB->Query("SELECT ID, QUOTE_NUMBER FROM b_crm_quote WHERE QUOTE_NUMBER = '".$rand."'", true);
				$value = ($arRes = $dbres->GetNext()) ? false : $rand;
				break;

			case 'USER':

				$dbres = $DB->Query("SELECT ASSIGNED_BY_ID FROM b_crm_quote WHERE ID = '".$ID."'", true);

				if ($arRes = $dbres->GetNext())
				{
					$userID = intval($arRes["ASSIGNED_BY_ID"]);
					$strSql = '';

					switch (strtolower($DB->type))
					{
						case "mysql":
							$strSql = "SELECT MAX(CAST(SUBSTRING(QUOTE_NUMBER, LENGTH('".$userID."_') + 1) as UNSIGNED)) as NUM_ID FROM b_crm_quote WHERE QUOTE_NUMBER LIKE '".$userID."\_%'";
							break;
						case "oracle":
							$strSql = "SELECT MAX(CAST(SUBSTR(QUOTE_NUMBER, LENGTH('".$userID."_') + 1) as NUMBER)) as NUM_ID FROM b_crm_quote WHERE QUOTE_NUMBER LIKE '".$userID."_%'";
							break;
						case "mssql":
							$strSql = "SELECT MAX(CAST(SUBSTRING(QUOTE_NUMBER, LEN('".$userID."_') + 1, LEN(QUOTE_NUMBER)) as INT)) as NUM_ID FROM b_crm_quote WHERE QUOTE_NUMBER LIKE '".$userID."_%'";
							break;
					}

					$dbres = $DB->Query($strSql, true);
					if ($arRes = $dbres->GetNext())
					{
						$numID = (intval($arRes["NUM_ID"]) > 0) ? $arRes["NUM_ID"] + 1 : 1;
						$value = $userID."_".$numID;
					}
					else
						$value = $userID."_1";
				}
				else
					$value = false;

				break;

			case 'DATE':
				$date = '';
				switch ($param)
				{
					// date in the site format but without delimeters
					case 'day':
						$date = date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")), mktime(0, 0, 0, date("m"), date("d"), date("Y")));
						$date = preg_replace("/[^0-9]/", "", $date);
						break;
					case 'month':
						$date = date($DB->DateFormatToPHP(str_replace("DD", "", CSite::GetDateFormat("SHORT"))), mktime(0, 0, 0, date("m"), date("d"), date("Y")));
						$date = preg_replace("/[^0-9]/", "", $date);
						break;
					case 'year':
						$date = date('Y');
						break;
				}

				$strSql = '';
				switch (strtolower($DB->type))
				{
					case "mysql":
						$strSql = "SELECT MAX(CAST(SUBSTRING(QUOTE_NUMBER, LENGTH('".$date." / ') + 1) as UNSIGNED)) as NUM_ID FROM b_crm_quote WHERE QUOTE_NUMBER LIKE '".$date." / %'";
						break;
					case "oracle":
						$strSql = "SELECT MAX(CAST(SUBSTR(QUOTE_NUMBER, LENGTH('".$date." / ') + 1) as NUMBER)) as NUM_ID FROM b_crm_quote WHERE QUOTE_NUMBER LIKE '".$date." / %'";
						break;
					case "mssql":
						$strSql = "SELECT MAX(CAST(SUBSTRING(QUOTE_NUMBER, LEN('".$date." / ') + 1, LEN(QUOTE_NUMBER)) as INT)) as NUM_ID FROM b_crm_quote WHERE QUOTE_NUMBER LIKE '".$date." / %'";
						break;
				}

				$dbres = $DB->Query($strSql, true);
				if ($arRes = $dbres->GetNext())
				{
					$numID = (intval($arRes["NUM_ID"]) > 0) ? $arRes["NUM_ID"] + 1 : 1;
					$value = $date." / ".$numID;
				}
				else
					$value = $date." / 1";

				break;

			default: // if unknown template

				$value = false;
				break;
		}

		return $value;
	}

	/**
	 * Sets quote number
	 * Use OnBeforeQuoteNumberSet event to generate custom quote number.
	 * Quote number value must be unique! By default quote ID is used if generated value is incorrect
	 *
	 * @param int $ID - quote ID
	 * @return bool - true if quote number is set successfully
	 */
	private static function SetQuoteNumber($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		$type = COption::GetOptionString("crm", "quote_number_template", "");
		$param = COption::GetOptionString("crm", "quote_number_data", "");

		$bCustomAlgorithm = false;
		$value = $ID;
		foreach(GetModuleEvents("crm", "OnBeforeCrmQuoteNumberSet", true) as $arEvent)
		{
			$tmpRes = ExecuteModuleEventEx($arEvent, Array($ID, $type));
			if ($tmpRes !== false)
			{
				$bCustomAlgorithm = true;
				$value = $tmpRes;
			}
		}

		if ($bCustomAlgorithm)
		{
			$arFields = array("QUOTE_NUMBER" => $value);
			$sUpdate = $DB->PrepareUpdate('b_crm_quote', $arFields);
			$sql = "UPDATE b_crm_quote SET $sUpdate WHERE ID = $ID";
			$res = $DB->Query($sql, true);
		}
		else
		{
			$res = false;
			if ($type != "") // if special template is selected
			{
				for ($i = 0; $i < 10; $i++)
				{
					$value = CCrmQuote::GetNextQuoteNumber($ID, $type, $param);

					if ($value !== false)
					{
						$arFields = array("QUOTE_NUMBER" => $value);
						$sUpdate = $DB->PrepareUpdate('b_crm_quote', $arFields);
						$sql = "UPDATE b_crm_quote SET $sUpdate WHERE ID = $ID";
						$res = $DB->Query($sql, true);
						if ($res)
							break;
					}
				}
			}
		}

		if ($type == "" || !$res) // if no special template is used or error occured
		{
			$arFields = array("QUOTE_NUMBER" => $ID);
			$sUpdate = $DB->PrepareUpdate('b_crm_quote', $arFields);
			$sql = "UPDATE b_crm_quote SET $sUpdate WHERE ID = $ID";
			$res = $DB->Query($sql, true);
		}

		return $res;
	}

	public static function CompareFields($arFieldsOrig, $arFieldsModif, $bCheckPerms = true)
	{
		$arMsg = Array();

		if (array_key_exists('QUOTE_NUMBER', $arFieldsModif))
		{
			$origQuoteNumber = isset($arFieldsOrig['QUOTE_NUMBER']) ? $arFieldsOrig['QUOTE_NUMBER'] : '';
			$modifQuoteNumber = isset($arFieldsModif['QUOTE_NUMBER']) ? $arFieldsModif['QUOTE_NUMBER'] : '';
			if ($origQuoteNumber != $modifQuoteNumber)
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'QUOTE_NUMBER',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_QUOTE_NUMBER'),
					'EVENT_TEXT_1' => !empty($origQuoteNumber) ? $origQuoteNumber : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
					'EVENT_TEXT_2' => !empty($modifQuoteNumber) ? $modifQuoteNumber : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY')
				);
			unset($origQuoteNumber, $modifQuoteNumber);
		}

		if (array_key_exists('TITLE', $arFieldsModif))
		{
			$origTitle = isset($arFieldsOrig['TITLE']) ? $arFieldsOrig['TITLE'] : '';
			$modifTitle = isset($arFieldsModif['TITLE']) ? $arFieldsModif['TITLE'] : '';
			if ($origTitle != $modifTitle)
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'TITLE',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_TITLE'),
					'EVENT_TEXT_1' => !empty($origTitle) ? $origTitle : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
					'EVENT_TEXT_2' => !empty($modifTitle) ? $modifTitle : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY')
				);
			unset($origTitle, $modifTitle);
		}

		if (array_key_exists('DEAL_ID', $arFieldsModif))
		{
			$origDealId = isset($arFieldsOrig['DEAL_ID']) ? intval($arFieldsOrig['DEAL_ID']) : 0;
			$modifDealId = isset($arFieldsModif['DEAL_ID']) ? intval($arFieldsModif['DEAL_ID']) : 0;
			if ($origDealId != $modifDealId)
			{
				$arDeal = Array();

				$arFilterTmp = array('ID' => array($origDealId, $modifDealId));
				if (!$bCheckPerms)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";

				$dbRes = CCrmDeal::GetList(Array('TITLE'=>'ASC'), $arFilterTmp);
				while ($arRes = $dbRes->Fetch())
					$arDeal[$arRes['ID']] = $arRes['TITLE'];

				$arMsg[] = Array(
					'ENTITY_FIELD' => 'DEAL_ID',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_DEAL_ID'),
					'EVENT_TEXT_1' => CrmCompareFieldsList($arDeal, $origDealId),
					'EVENT_TEXT_2' => CrmCompareFieldsList($arDeal, $modifDealId)
				);
			}
			unset($origDealId, $modifDealId);
		}

		if (array_key_exists('COMPANY_ID', $arFieldsModif))
		{
			$origCompanyId = isset($arFieldsOrig['COMPANY_ID']) ? intval($arFieldsOrig['COMPANY_ID']) : 0;
			$modifCompanyId = isset($arFieldsModif['COMPANY_ID']) ? intval($arFieldsModif['COMPANY_ID']) : 0;
			if ($origCompanyId != $modifCompanyId)
			{
				$arCompany = Array();

				$arFilterTmp = array('ID' => array($origCompanyId, $modifCompanyId));
				if (!$bCheckPerms)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";

				$dbRes = CCrmCompany::GetList(Array('TITLE'=>'ASC'), $arFilterTmp);
				while ($arRes = $dbRes->Fetch())
					$arCompany[$arRes['ID']] = $arRes['TITLE'];

				$arMsg[] = Array(
					'ENTITY_FIELD' => 'COMPANY_ID',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_COMPANY_ID'),
					'EVENT_TEXT_1' => CrmCompareFieldsList($arCompany, $origCompanyId),
					'EVENT_TEXT_2' => CrmCompareFieldsList($arCompany, $modifCompanyId)
				);
			}
			unset($origCompanyId, $modifCompanyId);
		}

		if (array_key_exists('CONTACT_ID', $arFieldsModif))
		{
			$origContactId = isset($arFieldsOrig['CONTACT_ID']) ? intval($arFieldsOrig['CONTACT_ID']) : 0;
			$modifContactId = isset($arFieldsModif['CONTACT_ID']) ? intval($arFieldsModif['CONTACT_ID']) : 0;
			if ($origContactId != $modifContactId)
			{
				$arContact = Array();

				$arFilterTmp = array('ID' => array($origContactId, $modifContactId));
				if (!$bCheckPerms)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";

				$dbRes = CCrmContact::GetList(Array('LAST_NAME'=>'ASC', 'NAME' => 'ASC'), $arFilterTmp);
				while ($arRes = $dbRes->Fetch())
					$arContact[$arRes['ID']] = $arRes['LAST_NAME'].' '.$arRes['NAME'];

				$arMsg[] = Array(
					'ENTITY_FIELD' => 'CONTACT_ID',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_CONTACT_ID'),
					'EVENT_TEXT_1' => CrmCompareFieldsList($arContact, $origContactId),
					'EVENT_TEXT_2' => CrmCompareFieldsList($arContact, $modifContactId)
				);
			}
			unset($origContactId, $modifContactId);
		}

		if (array_key_exists('ASSIGNED_BY_ID', $arFieldsModif))
		{
			$origAssignedById = isset($arFieldsOrig['ASSIGNED_BY_ID']) ? intval($arFieldsOrig['ASSIGNED_BY_ID']) : 0;
			$modifAssignedById = isset($arFieldsModif['ASSIGNED_BY_ID']) ? intval($arFieldsModif['ASSIGNED_BY_ID']) : 0;
			if ($origAssignedById != $modifAssignedById)
			{
				$arUser = Array();
				$dbUsers = CUser::GetList(
					($sort_by = 'last_name'), ($sort_dir = 'asc'),
					array('ID' => implode('|', array(intval($origAssignedById), intval($modifAssignedById)))),
					array('SELECT' => array('NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'EMAIL'))
				);
				while ($arRes = $dbUsers->Fetch())
					$arUser[$arRes['ID']] = CUser::FormatName(CSite::GetNameFormat(false), $arRes);

				$arMsg[] = Array(
					'ENTITY_FIELD' => 'ASSIGNED_BY_ID',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_ASSIGNED_BY_ID'),
					'EVENT_TEXT_1' => CrmCompareFieldsList($arUser, $origAssignedById),
					'EVENT_TEXT_2' => CrmCompareFieldsList($arUser, $modifAssignedById)
				);
			}
			unset($origAssignedById, $modifAssignedById);
		}

		if (array_key_exists('STATUS_ID', $arFieldsModif))
		{
			$origStatusId = isset($arFieldsOrig['STATUS_ID']) ? $arFieldsOrig['STATUS_ID'] : '';
			$modifStatusId = isset($arFieldsModif['STATUS_ID']) ? $arFieldsModif['STATUS_ID'] : '';
			if ($origStatusId != $modifStatusId)
			{
				$arStatus = CCrmStatus::GetStatusList('QUOTE_STATUS');
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'STATUS_ID',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_STATUS_ID'),
					'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $origStatusId)),
					'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $modifStatusId))
				);
			}
			unset($origStatusId, $modifStatusId);
		}

		if (array_key_exists('COMMENTS', $arFieldsModif))
		{
			$origComments = isset($arFieldsOrig['COMMENTS']) ? $arFieldsOrig['COMMENTS'] : '';
			$modifComments = isset($arFieldsModif['COMMENTS']) ? $arFieldsModif['COMMENTS'] : '';
			if ($origComments != $modifComments)
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'COMMENTS',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_COMMENTS'),
					'EVENT_TEXT_1' => !empty($origComments) ? $origComments : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
					'EVENT_TEXT_2' => !empty($modifComments) ? $modifComments : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY')
				);
			unset($origComments, $modifComments);
		}

		if (array_key_exists('CONTENT', $arFieldsModif))
		{
			$origContent = isset($arFieldsOrig['CONTENT']) ? $arFieldsOrig['CONTENT'] : '';
			$modifContent = isset($arFieldsModif['CONTENT']) ? $arFieldsModif['CONTENT'] : '';
			if ($origContent != $modifContent)
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'CONTENT',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_CONTENT'),
					'EVENT_TEXT_1' => !empty($origContent)? $origContent : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
					'EVENT_TEXT_2' => !empty($modifContent)? $modifContent : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY')
				);
			unset($origContent, $modifContent);
		}

		if (array_key_exists('TERMS', $arFieldsModif))
		{
			$origTerms = isset($arFieldsOrig['TERMS']) ? $arFieldsOrig['TERMS'] : '';
			$modifTerms = isset($arFieldsModif['TERMS']) ? $arFieldsModif['TERMS'] : '';
			if ($origTerms != $modifTerms)
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'TERMS',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_TERMS'),
					'EVENT_TEXT_1' => !empty($origTerms)? $origTerms : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
					'EVENT_TEXT_2' => !empty($modifTerms)? $modifTerms : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
				);
			unset($origTerms, $modifTerms);
		}

		if (array_key_exists('OPPORTUNITY', $arFieldsModif) || array_key_exists('CURRENCY_ID', $arFieldsModif))
		{
			$origOpportunity = isset($arFieldsOrig['OPPORTUNITY']) ? round(doubleval($arFieldsOrig['OPPORTUNITY']), 2) : 0.0;
			$modifOpportunity = isset($arFieldsModif['OPPORTUNITY']) ? round(doubleval($arFieldsModif['OPPORTUNITY']), 2) : $origOpportunity;
			$origCurrencyId = isset($arFieldsOrig['CURRENCY_ID']) ? $arFieldsOrig['CURRENCY_ID'] : '';
			$modifCurrencyId = isset($arFieldsModif['CURRENCY_ID']) ? $arFieldsModif['CURRENCY_ID'] : $origCurrencyId;
			if ($origOpportunity != $modifOpportunity || $origCurrencyId != $modifCurrencyId)
			{
				$arStatus = CCrmCurrencyHelper::PrepareListItems();
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'OPPORTUNITY',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_OPPORTUNITY'),
					'EVENT_TEXT_1' => floatval($arFieldsOrig['OPPORTUNITY']).(($val = CrmCompareFieldsList($arStatus, $origCurrencyId, '')) != '' ? ' ('.$val.')' : ''),
					'EVENT_TEXT_2' => floatval($arFieldsModif['OPPORTUNITY']).(($val = CrmCompareFieldsList($arStatus, $modifCurrencyId, '')) != '' ? ' ('.$val.')' : '')
				);
			}
			unset($origOpportunity, $modifOpportunity, $origCurrencyId, $modifCurrencyId);
		}

		if (array_key_exists('TAX_VALUE', $arFieldsModif) || array_key_exists('CURRENCY_ID', $arFieldsModif))
		{
			if ((isset($arFieldsOrig['TAX_VALUE']) && isset($arFieldsModif['TAX_VALUE']) && $arFieldsOrig['TAX_VALUE'] != $arFieldsModif['TAX_VALUE'])
				|| (isset($arFieldsOrig['CURRENCY_ID']) && isset($arFieldsModif['CURRENCY_ID']) && $arFieldsOrig['CURRENCY_ID'] != $arFieldsModif['CURRENCY_ID']))
			{
				$arStatus = CCrmCurrencyHelper::PrepareListItems();
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'TAX_VALUE',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_TAX_VALUE'),
					'EVENT_TEXT_1' => floatval($arFieldsOrig['TAX_VALUE']).(($val = CrmCompareFieldsList($arStatus, $arFieldsOrig['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : ''),
					'EVENT_TEXT_2' => floatval($arFieldsModif['TAX_VALUE']).(($val = CrmCompareFieldsList($arStatus, $arFieldsModif['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : '')
				);
			}
		}

		if (array_key_exists('BEGINDATE', $arFieldsOrig) && array_key_exists('BEGINDATE', $arFieldsModif) &&
			ConvertTimeStamp(strtotime($arFieldsOrig['BEGINDATE'])) != $arFieldsModif['BEGINDATE'] && $arFieldsOrig['BEGINDATE'] != $arFieldsModif['BEGINDATE'])
		{
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'BEGINDATE',
				'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_BEGINDATE'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['BEGINDATE'])? ConvertTimeStamp(strtotime($arFieldsOrig['BEGINDATE'])): GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['BEGINDATE'])? $arFieldsModif['BEGINDATE']: GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
			);
		}
		if (array_key_exists('CLOSEDATE', $arFieldsOrig) && array_key_exists('CLOSEDATE', $arFieldsModif) &&
			ConvertTimeStamp(strtotime($arFieldsOrig['CLOSEDATE'])) != $arFieldsModif['CLOSEDATE'] && $arFieldsOrig['CLOSEDATE'] != $arFieldsModif['CLOSEDATE'])
		{
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'CLOSEDATE',
				'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_CLOSEDATE'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['CLOSEDATE'])? ConvertTimeStamp(strtotime($arFieldsOrig['CLOSEDATE'])): GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['CLOSEDATE'])? $arFieldsModif['CLOSEDATE']: GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
			);
		}

		if (array_key_exists('OPENED', $arFieldsModif))
		{
			if (isset($arFieldsOrig['OPENED']) && isset($arFieldsModif['OPENED'])
				&& $arFieldsOrig['OPENED'] != $arFieldsModif['OPENED'])
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'OPENED',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_OPENED'),
					'EVENT_TEXT_1' => $arFieldsOrig['OPENED'] == 'Y'? GetMessage('MAIN_YES'): GetMessage('MAIN_NO'),
					'EVENT_TEXT_2' => $arFieldsModif['OPENED'] == 'Y'? GetMessage('MAIN_YES'): GetMessage('MAIN_NO'),
				);
		}

		if (array_key_exists('CLOSED', $arFieldsModif))
		{
			if (isset($arFieldsOrig['CLOSED']) && isset($arFieldsModif['CLOSED'])
				&& $arFieldsOrig['CLOSED'] != $arFieldsModif['CLOSED'])
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'CLOSED',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_CLOSED'),
					'EVENT_TEXT_1' => $arFieldsOrig['CLOSED'] == 'Y'? GetMessage('MAIN_YES'): GetMessage('MAIN_NO'),
					'EVENT_TEXT_2' => $arFieldsModif['CLOSED'] == 'Y'? GetMessage('MAIN_YES'): GetMessage('MAIN_NO'),
				);
		}

		// person type
		if (array_key_exists('PERSON_TYPE_ID', $arFieldsModif))
		{
			$bPersonTypeChanged = (isset($arFieldsOrig['PERSON_TYPE_ID']) && isset($arFieldsModif['PERSON_TYPE_ID'])
				&& intval($arFieldsOrig['PERSON_TYPE_ID']) !== intval($arFieldsModif['PERSON_TYPE_ID']));
			if ($bPersonTypeChanged)
			{
				$arPersonTypes = CCrmPaySystem::getPersonTypesList();

				if ($bPersonTypeChanged)
				{
					$arMsg[] = Array(
						'ENTITY_FIELD' => 'PERSON_TYPE_ID',
						'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_PERSON_TYPE_ID'),
						'EVENT_TEXT_1' => CrmCompareFieldsList($arPersonTypes, $arFieldsOrig['PERSON_TYPE_ID']),
						'EVENT_TEXT_2' => CrmCompareFieldsList($arPersonTypes, $arFieldsModif['PERSON_TYPE_ID'])
					);
				}
			}
		}

		if (array_key_exists('LOCATION_ID', $arFieldsModif))
		{
			$origLocationId = isset($arFieldsOrig['LOCATION_ID']) ? intval($arFieldsOrig['LOCATION_ID']) : 0;
			$modifLocationId = isset($arFieldsModif['LOCATION_ID']) ? intval($arFieldsModif['LOCATION_ID']) : 0;
			if ($origLocationId != $modifLocationId)
			{
				$origLocationString = $modifLocationString = '';
				if (IsModuleInstalled('sale') && CModule::IncludeModule('sale'))
				{
					$location = new CSaleLocation();
					$origLocationString = ($origLocationId > 0) ? $location->GetLocationString($origLocationId) : '';
					$modifLocationString = ($modifLocationId > 0) ? $location->GetLocationString($modifLocationId) : '';
				}
				if (empty($origLocationString) && intval($origLocationId) > 0)
					$origLocationString = '['.$origLocationId.']';
				if (empty($modifLocationString) && intval($modifLocationId) > 0)
					$modifLocationString = '['.$modifLocationId.']';
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'LOCATION_ID',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_LOCATION_ID'),
					'EVENT_TEXT_1' => $origLocationString,
					'EVENT_TEXT_2' => $modifLocationString,
				);
				unset($origLocationString, $modifLocationString);
			}
			unset($origLocationId, $modifLocationId);
		}

		$origClientFieldValue = $modifClientFieldValue = '';
		foreach (self::$clientFields as $fieldName)
		{
			if (array_key_exists($fieldName, $arFieldsModif))
			{
				$origClientFieldValue = isset($arFieldsOrig[$fieldName]) ? $arFieldsOrig[$fieldName] : '';
				$modifClientFieldValue = isset($arFieldsModif[$fieldName]) ? $arFieldsModif[$fieldName] : '';
				if ($origClientFieldValue != $modifClientFieldValue)
					$arMsg[] = Array(
						'ENTITY_FIELD' => $fieldName,
						'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_'.$fieldName),
						'EVENT_TEXT_1' => !empty($origClientFieldValue)? $origClientFieldValue: GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
						'EVENT_TEXT_2' => !empty($modifClientFieldValue)? $modifClientFieldValue: GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
					);
			}
		}
		unset($fieldName, $origClientFieldValue, $modifClientFieldValue);

		// Processing of the files
		if (array_key_exists('STORAGE_TYPE_ID', $arFieldsModif)
			&& array_key_exists('STORAGE_ELEMENT_IDS', $arFieldsModif) && strlen($arFieldsModif['STORAGE_ELEMENT_IDS']) > 0)
		{
			$newStorageTypeID = isset($arFieldsModif['STORAGE_TYPE_ID']) ? intval($arFieldsModif['STORAGE_TYPE_ID']) : CCrmQuoteStorageType::Undefined;
			$oldStorageTypeID = isset($arFieldsOrig['STORAGE_TYPE_ID']) ? intval($arFieldsOrig['STORAGE_TYPE_ID']) : CCrmQuoteStorageType::Undefined;

			self::PrepareStorageElementIDs($arFieldsModif);
			$newElementIDs = $arFieldsModif['STORAGE_ELEMENT_IDS'];

			self::PrepareStorageElementIDs($arFieldsOrig);
			$oldElementIDs = $arFieldsOrig['STORAGE_ELEMENT_IDS'];

			if($newStorageTypeID === $oldStorageTypeID && is_array($newElementIDs) && is_array($oldElementIDs))
			{
				$arRemovedElementIDs = array_values(array_diff($oldElementIDs, $newElementIDs));
				if(!empty($arRemovedElementIDs))
				{
					foreach($arRemovedElementIDs as $elementID)
					{
						self::PrepareFileEvent($oldStorageTypeID, $elementID, 'REMOVE', $arFieldsModif, $arMsg);
					}
					unset($elementID);
				}
				unset($arRemovedElementIDs);

				$arAddedElementIDs = array_values(array_diff($newElementIDs, $oldElementIDs));
				if(!empty($arAddedElementIDs))
				{
					foreach($arAddedElementIDs as $elementID)
					{
						self::PrepareFileEvent($newStorageTypeID, $elementID, 'ADD', $arFieldsModif, $arMsg);
					}
					unset($elementID);
				}
				unset($arAddedElementIDs);
			}
			else if ($newStorageTypeID !== $oldStorageTypeID && is_array($newElementIDs) && is_array($oldElementIDs))
			{
				foreach($oldElementIDs as $elementID)
				{
					self::PrepareFileEvent($oldStorageTypeID, $elementID, 'REMOVE', $arFieldsModif, $arMsg);
				}
				unset($elementID);

				foreach($newElementIDs as $elementID)
				{
					self::PrepareFileEvent($newStorageTypeID, $elementID, 'ADD', $arFieldsModif, $arMsg);
				}
				unset($elementID);
			}
			unset($newStorageTypeID, $oldStorageTypeID, $newElementIDs, $oldElementIDs);
		}

		return $arMsg;
	}

	// Service -->
	public static function GetFieldCaption($fieldName)
	{
		$result = GetMessage("CRM_QUOTE_FIELD_{$fieldName}");
		return is_string($result) ? $result : '';
	}
	// Get Fields Metadata
	public static function GetFields($arOptions = null)
	{
		$dealJoin = 'LEFT JOIN b_crm_deal D ON '.self::TABLE_ALIAS.'.DEAL_ID = D.ID';
		$companyJoin = 'LEFT JOIN b_crm_company CO ON '.self::TABLE_ALIAS.'.COMPANY_ID = CO.ID';
		$contactJoin = 'LEFT JOIN b_crm_contact C ON '.self::TABLE_ALIAS.'.CONTACT_ID = C.ID';
		$assignedByJoin = 'LEFT JOIN b_user U ON '.self::TABLE_ALIAS.'.ASSIGNED_BY_ID = U.ID';
		$createdByJoin = 'LEFT JOIN b_user U2 ON '.self::TABLE_ALIAS.'.CREATED_BY_ID = U2.ID';
		$modifyByJoin = 'LEFT JOIN b_user U3 ON '.self::TABLE_ALIAS.'.MODIFY_BY_ID = U3.ID';

		$result = array(
			'ID' => array('FIELD' => self::TABLE_ALIAS.'.ID', 'TYPE' => 'int'),
			'TITLE' => array('FIELD' => self::TABLE_ALIAS.'.TITLE', 'TYPE' => 'string'),
			/*'TYPE_ID' => array('FIELD' => self::TABLE_ALIAS.'.TYPE_ID', 'TYPE' => 'string'),*/
			'STATUS_ID' => array('FIELD' => self::TABLE_ALIAS.'.STATUS_ID', 'TYPE' => 'string'),
			/*'PROBABILITY' => array('FIELD' => self::TABLE_ALIAS.'.PROBABILITY', 'TYPE' => 'int'),*/
			'CURRENCY_ID' => array('FIELD' => self::TABLE_ALIAS.'.CURRENCY_ID', 'TYPE' => 'string'),
			'EXCH_RATE' => array('FIELD' => self::TABLE_ALIAS.'.EXCH_RATE', 'TYPE' => 'double'),
			'OPPORTUNITY' => array('FIELD' => self::TABLE_ALIAS.'.OPPORTUNITY', 'TYPE' => 'double'),
			'TAX_VALUE' => array('FIELD' => self::TABLE_ALIAS.'.TAX_VALUE', 'TYPE' => 'double'),
			'ACCOUNT_CURRENCY_ID' => array('FIELD' => self::TABLE_ALIAS.'.ACCOUNT_CURRENCY_ID', 'TYPE' => 'string'),
			'OPPORTUNITY_ACCOUNT' => array('FIELD' => self::TABLE_ALIAS.'.OPPORTUNITY_ACCOUNT', 'TYPE' => 'double'),
			'TAX_VALUE_ACCOUNT' => array('FIELD' => self::TABLE_ALIAS.'.TAX_VALUE_ACCOUNT', 'TYPE' => 'double'),

			/*'LEAD_ID' => array('FIELD' => self::TABLE_ALIAS.'.LEAD_ID', 'TYPE' => 'int'),*/
			'COMPANY_ID' => array('FIELD' => self::TABLE_ALIAS.'.COMPANY_ID', 'TYPE' => 'int'),
			'COMPANY_TITLE' => array('FIELD' => 'CO.TITLE', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_INDUSTRY' => array('FIELD' => 'CO.INDUSTRY', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_EMPLOYEES' => array('FIELD' => 'CO.EMPLOYEES', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_REVENUE' => array('FIELD' => 'CO.REVENUE', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_CURRENCY_ID' => array('FIELD' => 'CO.CURRENCY_ID', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_TYPE' => array('FIELD' => 'CO.COMPANY_TYPE', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_ADDRESS' => array('FIELD' => 'CO.ADDRESS', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_ADDRESS_LEGAL' => array('FIELD' => 'CO.ADDRESS_LEGAL', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_BANKING_DETAILS' => array('FIELD' => 'CO.BANKING_DETAILS', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_LOGO' => array('FIELD' => 'CO.LOGO', 'TYPE' => 'string', 'FROM' => $companyJoin),

			'CONTACT_ID' => array('FIELD' => self::TABLE_ALIAS.'.CONTACT_ID', 'TYPE' => 'int'),
			'CONTACT_TYPE_ID' => array('FIELD' => 'C.TYPE_ID', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_NAME' => array('FIELD' => 'C.NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_SECOND_NAME' => array('FIELD' => 'C.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_LAST_NAME' => array('FIELD' => 'C.LAST_NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_FULL_NAME' => array('FIELD' => 'C.FULL_NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),

			'CONTACT_POST' => array('FIELD' => 'C.POST', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_ADDRESS' => array('FIELD' => 'C.ADDRESS', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_SOURCE_ID' => array('FIELD' => 'C.SOURCE_ID', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_PHOTO' => array('FIELD' => 'C.PHOTO', 'TYPE' => 'string', 'FROM' => $contactJoin),

			'BEGINDATE' => array('FIELD' => self::TABLE_ALIAS.'.BEGINDATE', 'TYPE' => 'datetime'),
			'CLOSEDATE' => array('FIELD' => self::TABLE_ALIAS.'.CLOSEDATE', 'TYPE' => 'datetime'),

			'ASSIGNED_BY_ID' => array('FIELD' => self::TABLE_ALIAS.'.ASSIGNED_BY_ID', 'TYPE' => 'int'),
			'ASSIGNED_BY_LOGIN' => array('FIELD' => 'U.LOGIN', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_NAME' => array('FIELD' => 'U.NAME', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_LAST_NAME' => array('FIELD' => 'U.LAST_NAME', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_SECOND_NAME' => array('FIELD' => 'U.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_WORK_POSITION' => array('FIELD' => 'U.WORK_POSITION', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_PERSONAL_PHOTO' => array('FIELD' => 'U.PERSONAL_PHOTO', 'TYPE' => 'string', 'FROM' => $assignedByJoin),

			'CREATED_BY_ID' => array('FIELD' => self::TABLE_ALIAS.'.CREATED_BY_ID', 'TYPE' => 'int'),
			'CREATED_BY_LOGIN' => array('FIELD' => 'U2.LOGIN', 'TYPE' => 'string', 'FROM' => $createdByJoin),
			'CREATED_BY_NAME' => array('FIELD' => 'U2.NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),
			'CREATED_BY_LAST_NAME' => array('FIELD' => 'U2.LAST_NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),
			'CREATED_BY_SECOND_NAME' => array('FIELD' => 'U2.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),

			'MODIFY_BY_ID' => array('FIELD' => self::TABLE_ALIAS.'.MODIFY_BY_ID', 'TYPE' => 'int'),
			'MODIFY_BY_LOGIN' => array('FIELD' => 'U3.LOGIN', 'TYPE' => 'string', 'FROM' => $modifyByJoin),
			'MODIFY_BY_NAME' => array('FIELD' => 'U3.NAME', 'TYPE' => 'string', 'FROM' => $modifyByJoin),
			'MODIFY_BY_LAST_NAME' => array('FIELD' => 'U3.LAST_NAME', 'TYPE' => 'string', 'FROM' => $modifyByJoin),
			'MODIFY_BY_SECOND_NAME' => array('FIELD' => 'U3.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $modifyByJoin),

			'DATE_CREATE' => array('FIELD' => self::TABLE_ALIAS.'.DATE_CREATE', 'TYPE' => 'datetime'),
			'DATE_MODIFY' => array('FIELD' => self::TABLE_ALIAS.'.DATE_MODIFY', 'TYPE' => 'datetime'),

			'OPENED' => array('FIELD' => self::TABLE_ALIAS.'.OPENED', 'TYPE' => 'char'),
			'CLOSED' => array('FIELD' => self::TABLE_ALIAS.'.CLOSED', 'TYPE' => 'char'),
			'COMMENTS' => array('FIELD' => self::TABLE_ALIAS.'.COMMENTS', 'TYPE' => 'string'),
			'COMMENTS_TYPE' => array('FIELD' => self::TABLE_ALIAS.'.COMMENTS_TYPE', 'TYPE' => 'int'),
			/*'ADDITIONAL_INFO' => array('FIELD' => self::TABLE_ALIAS.'.ADDITIONAL_INFO', 'TYPE' => 'string'),*/

			/*'ORIGINATOR_ID' => array('FIELD' => self::TABLE_ALIAS.'.ORIGINATOR_ID', 'TYPE' => 'string'),*/ //EXTERNAL SYSTEM THAT OWNS THIS ITEM
			/*'ORIGIN_ID' => array('FIELD' => self::TABLE_ALIAS.'.ORIGIN_ID', 'TYPE' => 'string'),*/ //ITEM ID IN EXTERNAL SYSTEM

			// For compatibility only
			/*'PRODUCT_ID' => array('FIELD' => self::TABLE_ALIAS.'.PRODUCT_ID', 'TYPE' => 'string'),*/
			// Obsolete
			/*'EVENT_ID' => array('FIELD' => self::TABLE_ALIAS.'.EVENT_ID', 'TYPE' => 'string'),*/
			/*'EVENT_DATE' => array('FIELD' => self::TABLE_ALIAS.'.EVENT_DATE', 'TYPE' => 'datetime'),*/
			/*'EVENT_DESCRIPTION' => array('FIELD' => self::TABLE_ALIAS.'.EVENT_DESCRIPTION', 'TYPE' => 'string'),*/

			'DEAL_ID' => array('FIELD' => self::TABLE_ALIAS.'.DEAL_ID', 'TYPE' => 'int'),
			'DEAL_TITLE' => array('FIELD' => 'D.TITLE', 'TYPE' => 'string', 'FROM' => $dealJoin),
			'QUOTE_NUMBER' => array('FIELD' => self::TABLE_ALIAS.'.QUOTE_NUMBER', 'TYPE' => 'string'),
			'CONTENT' => array('FIELD' => self::TABLE_ALIAS.'.CONTENT', 'TYPE' => 'string'),
			'CONTENT_TYPE' => array('FIELD' => self::TABLE_ALIAS.'.CONTENT_TYPE', 'TYPE' => 'int'),
			'TERMS' => array('FIELD' => self::TABLE_ALIAS.'.TERMS', 'TYPE' => 'string'),
			'TERMS_TYPE' => array('FIELD' => self::TABLE_ALIAS.'.TERMS_TYPE', 'TYPE' => 'int'),
			'STORAGE_TYPE_ID' => array('FIELD' => self::TABLE_ALIAS.'.STORAGE_TYPE_ID', 'TYPE' => 'int'),
			'STORAGE_ELEMENT_IDS' => array('FIELD' => self::TABLE_ALIAS.'.STORAGE_ELEMENT_IDS', 'TYPE' => 'string'),
			'PERSON_TYPE_ID' => array('FIELD' => self::TABLE_ALIAS.'.PERSON_TYPE_ID', 'TYPE' => 'int'),
			'LOCATION_ID' => array('FIELD' => self::TABLE_ALIAS.'.LOCATION_ID', 'TYPE' => 'int'),
			'CLIENT_TITLE' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_TITLE', 'TYPE' => 'string'),
			'CLIENT_ADDR' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_ADDR', 'TYPE' => 'string'),
			'CLIENT_CONTACT' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_CONTACT', 'TYPE' => 'string'),
			'CLIENT_EMAIL' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_EMAIL', 'TYPE' => 'string'),
			'CLIENT_PHONE' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_PHONE', 'TYPE' => 'string'),
			'CLIENT_TP_ID' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_TP_ID', 'TYPE' => 'string'),
			'CLIENT_TPA_ID' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_TPA_ID', 'TYPE' => 'string')
		);

		// Creation of field aliases
		$result['ASSIGNED_BY'] = $result['ASSIGNED_BY_ID'];
		$result['CREATED_BY'] = $result['CREATED_BY_ID'];
		$result['MODIFY_BY'] = $result['MODIFY_BY_ID'];

		$additionalFields = is_array($arOptions) && isset($arOptions['ADDITIONAL_FIELDS'])
			? $arOptions['ADDITIONAL_FIELDS'] : null;

		if(is_array($additionalFields))
		{
			if(in_array('STATUS_SORT', $additionalFields, true))
			{
				$statusJoin = "LEFT JOIN b_crm_status ST ON ST.ENTITY_ID = 'QUOTE_STATUS' AND ".self::TABLE_ALIAS.".STATUS_ID = ST.STATUS_ID";
				$result['STATUS_SORT'] = array('FIELD' => 'ST.SORT', 'TYPE' => 'int', 'FROM' => $statusJoin);
			}

			/*if(in_array('ACTIVITY', $additionalFields, true))
			{
				$commonActivityJoin = CCrmActivity::PrepareJoin(0, CCrmOwnerType::Quote, self::TABLE_ALIAS, 'AC', 'UAC', 'ACUSR');

				$result['C_ACTIVITY_ID'] = array('FIELD' => 'UAC.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_TIME'] = array('FIELD' => 'UAC.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_SUBJECT'] = array('FIELD' => 'AC.SUBJECT', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_ID'] = array('FIELD' => 'AC.RESPONSIBLE_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_LOGIN'] = array('FIELD' => 'ACUSR.LOGIN', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_NAME'] = array('FIELD' => 'ACUSR.NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_LAST_NAME'] = array('FIELD' => 'ACUSR.LAST_NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_SECOND_NAME'] = array('FIELD' => 'ACUSR.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);

				$userID = CCrmPerms::GetCurrentUserID();
				if($userID > 0)
				{
					$activityJoin = CCrmActivity::PrepareJoin($userID, CCrmOwnerType::Quote, self::TABLE_ALIAS, 'A', 'UA', '');

					$result['ACTIVITY_ID'] = array('FIELD' => 'UA.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $activityJoin);
					$result['ACTIVITY_TIME'] = array('FIELD' => 'UA.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $activityJoin);
					$result['ACTIVITY_SUBJECT'] = array('FIELD' => 'A.SUBJECT', 'TYPE' => 'string', 'FROM' => $activityJoin);
				}
			}*/
		}

		return $result;
	}

	public static function __AfterPrepareSql(/*CCrmEntityListBuilder*/ $sender, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
	{
		// Applying filter by PRODUCT_ID
		$prodID = isset($arFilter['PRODUCT_ROW_PRODUCT_ID']) ? intval($arFilter['PRODUCT_ROW_PRODUCT_ID']) : 0;
		if($prodID <= 0)
		{
			return false;
		}

		$a = $sender->GetTableAlias();
		return array(
			'WHERE' => "$a.ID IN (SELECT DP.OWNER_ID from b_crm_product_row DP where DP.OWNER_TYPE = 'Q' and DP.OWNER_ID = $a.ID and DP.PRODUCT_ID = $prodID)"
		);
	}
	// <-- Service

	public static function GetUserFieldEntityID()
	{
		return self::$sUFEntityID;
	}

	public static function GetByID($ID, $bCheckPerms = true)
	{
		$arFilter = array('=ID' => intval($ID));
		if (!$bCheckPerms)
		{
			$arFilter['CHECK_PERMISSIONS'] = 'N';
		}

		$dbRes = CCrmQuote::GetList(array(), $arFilter);
		return $dbRes->Fetch();
	}

	// GetList with navigation support
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		if(!isset($arOptions['PERMISSION_SQL_TYPE']))
		{
			$arOptions['PERMISSION_SQL_TYPE'] = 'FROM';
			$arOptions['PERMISSION_SQL_UNION'] = 'DISTINCT';
		}

		$lb = new CCrmEntityListBuilder(
			CCrmQuote::DB_TYPE,
			CCrmQuote::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(isset($arOptions['FIELD_OPTIONS']) ? $arOptions['FIELD_OPTIONS'] : null),
			self::$sUFEntityID,
			'QUOTE',
			array('CCrmQuote', 'BuildPermSql'),
			array('CCrmQuote', '__AfterPrepareSql')
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	static public function BuildPermSql($sAliasPrefix = self::TABLE_ALIAS, $mPermType = 'READ', $arOptions = array())
	{
		return CCrmPerms::BuildSql('QUOTE', $sAliasPrefix, $mPermType, $arOptions);
	}

	public static function LocalComponentCausedUpdater()
	{
		global $stackCacheManager;

		$bResult = true;
		$errMsg = array();

		// at first, check last update version
		if (COption::GetOptionString('crm', '~CRM_QUOTE_14_1_11', 'N') === 'Y')
			return $bResult;

		try
		{
			// Copy perms from deals to quotes
			$CCrmRole = new CCrmRole();
			$dbRoles = $CCrmRole->GetList();

			while($arRole = $dbRoles->Fetch())
			{
				$arPerms = $CCrmRole->GetRolePerms($arRole['ID']);

				if(!isset($arPerms['QUOTE']) && is_array($arPerms['DEAL']))
				{
					foreach ($arPerms['DEAL'] as $key => $value)
					{
						if(isset($value['-']))
							$arPerms['QUOTE'][$key]['-'] = $value['-'];
						else
							$arPerms['QUOTE'][$key]['-'] = null;
					}
				}

				$arFields = array('RELATION' => $arPerms);
				$CCrmRole->Update($arRole['ID'], $arFields);
			}

			// Create default quote status list (if not exists)
			$arStatus = CCrmStatus::GetStatus('QUOTE_STATUS');
			if (empty($arStatus))
			{
				$CCrmStatus = new CCrmStatus('QUOTE_STATUS');
				$arAdd = Array(
					Array(
						'NAME' => GetMessage('CRM_QUOTE_STATUS_DRAFT'),
						'STATUS_ID' => 'DRAFT',
						'SORT' => 10,
						'SYSTEM' => 'Y'
					),
					Array(
						'NAME' => GetMessage('CRM_QUOTE_STATUS_SENT'),
						'STATUS_ID' => 'SENT',
						'SORT' => 20,
						'SYSTEM' => 'N'
					),
					Array(
						'NAME' => GetMessage('CRM_QUOTE_STATUS_RECEIVED'),
						'STATUS_ID' => 'RECEIVED',
						'SORT' => 30,
						'SYSTEM' => 'N'
					),
					Array(
						'NAME' => GetMessage('CRM_QUOTE_STATUS_APPROVED'),
						'STATUS_ID' => 'APPROVED',
						'SORT' => 40,
						'SYSTEM' => 'Y'
					),
					Array(
						'NAME' => GetMessage('CRM_QUOTE_STATUS_UNANSWERED'),
						'STATUS_ID' => 'UNANSWERED',
						'SORT' => 50,
						'SYSTEM' => 'N'
					),
					Array(
						'NAME' => GetMessage('CRM_QUOTE_STATUS_DECLAINED'),
						'STATUS_ID' => 'DECLAINED',
						'SORT' => 60,
						'SYSTEM' => 'Y'
					)
				);
				foreach($arAdd as $ar)
					$CCrmStatus->Add($ar);
				$stackCacheManager->Clear('b_crm_status', 'QUOTE_STATUS');
			}
			unset($arStatus);
		}
		catch (Exception $e)
		{
			$errMsg[] = $e->getMessage();
		}

		if (empty($errMsg))
		{
			COption::SetOptionString('crm', '~CRM_QUOTE_14_1_11', 'Y');
		}
		else
		{
			$errString = implode('<br>', $errMsg);
			ShowError($errString);
			$bResult = false;
		}

		return $bResult;
	}

	public static function LoadProductRows($ID)
	{
		return CCrmProductRow::LoadRows(self::OWNER_TYPE, $ID);
	}

	public static function SaveProductRows($ID, $arRows, $checkPerms = true, $regEvent = true, $syncOwner = true)
	{
		$context = array();
		$arParams = self::GetByID($ID);
		if(is_array($arParams))
		{
			if(isset($arParams['CURRENCY_ID']))
			{
				$context['CURRENCY_ID'] = $arParams['CURRENCY_ID'];
			}

			if(isset($arParams['EXCH_RATE']))
			{
				$context['EXCH_RATE'] = $arParams['EXCH_RATE'];
			}
		}

		return CCrmProductRow::SaveRows(self::OWNER_TYPE, $ID, $arRows, $context, $checkPerms, $regEvent, $syncOwner);
	}

	public function SynchronizeProductRows($ID)
	{

		$arTotalInfo = CCrmProductRow::CalculateTotalInfo(CCrmQuote::OWNER_TYPE, $ID);

		if (is_array($arTotalInfo))
		{
			$arFields = array(
				'OPPORTUNITY' => isset($arTotalInfo['OPPORTUNITY']) ? $arTotalInfo['OPPORTUNITY'] : 0.0,
				'TAX_VALUE' => isset($arTotalInfo['TAX_VALUE']) ? $arTotalInfo['TAX_VALUE'] : 0.0
			);

			$entity = new CCrmQuote();
			$entity->Update($ID, $arFields);
		}
	}

	public static function LoadElementIDs($ID)
	{
		$ID = (int)$ID;
		if($ID <= 0)
		{
			return array();
		}

		global $DB;
		$result = array();
		$table = CCrmQuote::ELEMENT_TABLE_NAME;
		$dbResult = $DB->Query("SELECT ELEMENT_ID FROM {$table} WHERE QUOTE_ID = {$ID}", false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		while($arResult = $dbResult->Fetch())
		{
			$elementID = isset($arResult['ELEMENT_ID']) ? (int)$arResult['ELEMENT_ID'] : 0;
			if($elementID > 0)
			{
				$result[] = $elementID;
			}
		}
		return $result;
	}

	public static function CheckCreatePermission($userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckCreatePermission(self::$TYPE_NAME, $userPermissions);
	}

	public static function CheckUpdatePermission($ID, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $userPermissions);
	}

	public static function CheckDeletePermission($ID, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckDeletePermission(self::$TYPE_NAME, $ID, $userPermissions);
	}

	public static function CheckReadPermission($ID = 0, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckReadPermission(self::$TYPE_NAME, $ID, $userPermissions);
	}

	public static function GetFinalStatusSort()
	{
		return self::GetStatusSort('APPROVED');
	}

	public static function GetStatusSort($statusID)
	{
		$statusID = strval($statusID);

		if($statusID === '')
		{
			return -1;
		}

		if(!self::$QUOTE_STATUSES)
		{
			self::$QUOTE_STATUSES = CCrmStatus::GetStatus('QUOTE_STATUS');
		}

		$info = isset(self::$QUOTE_STATUSES[$statusID]) ? self::$QUOTE_STATUSES[$statusID] : null;
		return is_array($info) && isset($info['SORT']) ? intval($info['SORT']) : -1;
	}

	public static function GetStatusSemantics($statusID)
	{
		if($statusID === 'APPROVED')
		{
			return 'success';
		}

		if($statusID === 'DECLAINED')
		{
			return 'failure';
		}

		return (self::GetStatusSort($statusID) > self::GetFinalStatusSort()) ? 'apology' : 'process';
	}

	public static function PullChange($type, $arParams)
	{
		if(!CModule::IncludeModule('pull'))
		{
			return;
		}

		$type = strval($type);
		if($type === '')
		{
			$type = 'update';
		}
		else
		{
			$type = strtolower($type);
		}

		CPullWatch::AddToStack(
			'CRM_QUOTE_CHANGE',
			array(
				'module_id'  => 'crm',
				'command'    => "crm_quote_{$type}",
				'params'     => $arParams
			)
		);
	}

	public static function GetCount($arFilter)
	{
		$fields = self::GetFields();
		return CSqlUtil::GetCount(CCrmDeal::TABLE_NAME, self::TABLE_ALIAS, $fields, $arFilter);
	}

	public static function GetClientFields()
	{
		return self::$clientFields;
	}

	public static function RewriteClientFields(&$arFields, $bDualFields = true)
	{
		$arCompany = $companyEMail = $companyPhone = null;
		$arContact = $contactEMail = $contactPhone = null;

		$companyId = isset($arFields['COMPANY_ID']) ? intval($arFields['COMPANY_ID']) : 0;
		$contactId = isset($arFields['CONTACT_ID']) ? intval($arFields['CONTACT_ID']) : 0;

		if ($companyId > 0)
		{
			$arCompany = CCrmCompany::GetByID($companyId);

			// Get multifields values (EMAIL and PHONE)
			$arFieldsMulti = CCrmFieldMulti::GetEntityFields('COMPANY', $companyId, 'EMAIL', true, false);
			if (is_array($arFieldsMulti) && isset($arFieldsMulti[0]['VALUE']))
				$companyEMail = $arFieldsMulti[0]['VALUE'];
			$arFieldsMulti = CCrmFieldMulti::GetEntityFields('COMPANY', $companyId, 'PHONE', true, false);
			if (is_array($arFieldsMulti) && isset($arFieldsMulti[0]['VALUE']))
				$companyPhone = $arFieldsMulti[0]['VALUE'];
			unset($arFieldsMulti);
		}

		if ($contactId > 0)
		{
			$arContact = CCrmContact::GetByID($contactId);

			// Get multifields values (EMAIL and PHONE)
			$arFieldsMulti = CCrmFieldMulti::GetEntityFields('CONTACT', $contactId, 'EMAIL', true, false);
			if (is_array($arFieldsMulti) && isset($arFieldsMulti[0]['VALUE']))
				$contactEMail = $arFieldsMulti[0]['VALUE'];
			$arFieldsMulti = CCrmFieldMulti::GetEntityFields('CONTACT', $contactId, 'PHONE', true, false);
			if (is_array($arFieldsMulti) && isset($arFieldsMulti[0]['VALUE']))
				$contactPhone = $arFieldsMulti[0]['VALUE'];
			unset($arFieldsMulti);
		}

		if ($companyId > 0)
		{
			if (is_array($arCompany) && count($arCompany) >0)
			{
				foreach (self::$clientFields as $k)
				{
					$v = '';
					if ($k === 'CLIENT_TITLE')
					{
						if (isset($arCompany['TITLE']))
							$v = $arCompany['TITLE'];
					}
					elseif ($k === 'CLIENT_CONTACT' && $contactId > 0)
					{
						if (isset($arContact['FULL_NAME']))
							$v = $arContact['FULL_NAME'];
					}
					elseif ($k === 'CLIENT_ADDR')
					{
						if (isset($arCompany['ADDRESS_LEGAL']))
							$v = $arCompany['ADDRESS_LEGAL'];
					}
					elseif ($k === 'CLIENT_EMAIL')
					{
						$v = ($contactEMail != '') ? $contactEMail : $companyEMail;
					}
					elseif ($k === 'CLIENT_PHONE')
					{
						$v = ($contactPhone != '') ? $contactPhone : $companyPhone;
					}
					if ($bDualFields)
						$arFields['~'.$k] = $v;
					$arFields[$k] = $bDualFields ? htmlspecialcharsbx($v) : $v;
				}
			}
		}
		elseif ($contactId > 0)
		{
			if (is_array($arContact) && count($arContact) >0)
			{
				foreach (self::$clientFields as $k)
				{
					$v = '';
					if ($k === 'CLIENT_TITLE')
					{
						if (isset($arContact['FULL_NAME']))
							$v = $arContact['FULL_NAME'];
					}
					elseif ($k === 'CLIENT_CONTACT' && $contactId > 0)
					{
						$v = '';
					}
					elseif ($k === 'CLIENT_ADDR')
					{
						if (isset($arContact['ADDRESS']))
							$v = $arContact['ADDRESS'];
					}
					elseif ($k === 'CLIENT_EMAIL')
					{
						$v = $contactEMail;
					}
					elseif ($k === 'CLIENT_PHONE')
					{
						$v = $contactPhone;
					}
					if ($bDualFields)
						$arFields['~'.$k] = $v;
					$arFields[$k] = $bDualFields ? htmlspecialcharsbx($v) : $v;
				}
			}
		}
	}

	public static function MakeClientInfoString($arQuote, $bDualFields = true)
	{
		$strClientInfo = '';

		$i = 0;
		foreach (self::$clientFields as $k)
		{
			$index = $bDualFields === true ? '~'.$k : $k;
			if (isset($arQuote[$index]) && strlen(trim($arQuote[$index])) > 0)
				$strClientInfo .= (($i++ > 0) ? ', ' : '').$arQuote[$index];
		}

		return $strClientInfo;
	}

	public static function BuildSearchCard($arQuote, $bReindex = false)
	{
		$arStatuses = array();
		$arSite = array();
		$sEntityType = 'QUOTE';
		$sTitle = 'TITLE';
		$sNumber = 'QUOTE_NUMBER';
		$arSearchableFields = array(
			'DATE_CREATE' => GetMessage('CRM_QUOTE_SEARCH_FIELD_DATE_CREATE'),
			'STATUS_ID' => GetMessage('CRM_QUOTE_SEARCH_FIELD_STATUS_ID'),
			'BEGINDATE' => GetMessage('CRM_QUOTE_SEARCH_FIELD_BEGINDATE'),
			'CLOSEDATE' => GetMessage('CRM_QUOTE_SEARCH_FIELD_CLOSEDATE'),
			'OPPORTUNITY' => GetMessage('CRM_QUOTE_SEARCH_FIELD_OPPORTUNITY'),
			'COMMENTS' => GetMessage('CRM_QUOTE_SEARCH_FIELD_COMMENTS'),
			'CLIENT_TITLE' => GetMessage('CRM_QUOTE_SEARCH_FIELD_CLIENT_TITLE'),
			'CLIENT_ADDR' => GetMessage('CRM_QUOTE_SEARCH_FIELD_CLIENT_ADDR'),
			'CLIENT_CONTACT' => GetMessage('CRM_QUOTE_SEARCH_FIELD_CLIENT_CONTACT'),
			'CLIENT_EMAIL' => GetMessage('CRM_QUOTE_SEARCH_FIELD_CLIENT_EMAIL'),
			'CLIENT_PHONE' => GetMessage('CRM_QUOTE_SEARCH_FIELD_CLIENT_PHONE'),
			'CLIENT_TP_ID' => GetMessage('CRM_QUOTE_SEARCH_FIELD_TP_ID'),
			'CLIENT_TPA_ID' => GetMessage('CRM_QUOTE_SEARCH_FIELD_TPA_ID')
		);

		$sBody = $arQuote[$sNumber].', '.$arQuote[$sTitle]."\n";
		$arField2status = array(
			'STATUS_ID' => 'QUOTE_STATUS'
		);
		$site = new CSite();

		foreach (array_keys($arSearchableFields) as $k)
		{
			if (!isset($arQuote[$k]))
				continue;

			$v = $arQuote[$k];

			if($k === 'COMMENTS')
			{
				$v = CSearch::KillTags($v);
			}

			$v = trim($v);

			if ($k === 'DATE_CREATE' || $k === 'BEGINDATE' || $k === 'CLOSEDATE')
			{
				$dateFormatShort = $site->GetDateFormat('SHORT');
				if (!CheckDateTime($v, $dateFormatShort))
				{
					$v = ConvertTimeStamp(strtotime($v), 'SHORT');
				}
				if (CheckDateTime($v, $dateFormatShort))
				{
					$v = FormatDate('SHORT', MakeTimeStamp($v, $dateFormatShort));
				}
				else
				{
					$v = null;
				}
			}

			if (isset($arField2status[$k]))
			{
				if (!isset($arStatuses[$k]))
					$arStatuses[$k] = CCrmStatus::GetStatusList($arField2status[$k]);
				$v = $arStatuses[$k][$v];
			}

			if ($k === 'OPPORTUNITY')
				$v = number_format(doubleval($v), 2, '.', '');
			if (!empty($v) && (!is_numeric($v) || $k === 'OPPORTUNITY') && $v != 'N' && $v != 'Y')
				$sBody .= $arSearchableFields[$k].": $v\n";
		}

		if ((isset($arQuote['ASSIGNED_BY_NAME']) && !empty($arQuote['ASSIGNED_BY_NAME']))
			|| (isset($arQuote['ASSIGNED_BY_LAST_NAME']) && !empty($arQuote['ASSIGNED_BY_LAST_NAME']))
			|| (isset($arQuote['ASSIGNED_BY_SECOND_NAME']) && !empty($arQuote['ASSIGNED_BY_SECOND_NAME'])))
		{
			$responsibleInfo = CUser::FormatName(
				$site->GetNameFormat(null, $arQuote['LID']),
				array(
					'LOGIN' => '',
					'NAME' => isset($arQuote['ASSIGNED_BY_NAME']) ? $arQuote['ASSIGNED_BY_NAME'] : '',
					'LAST_NAME' => isset($arQuote['ASSIGNED_BY_LAST_NAME']) ? $arQuote['ASSIGNED_BY_LAST_NAME'] : '',
					'SECOND_NAME' => isset($arQuote['ASSIGNED_BY_SECOND_NAME']) ? $arQuote['ASSIGNED_BY_SECOND_NAME'] : ''
				),
				false, false
			);
			if (isset($arQuote['ASSIGNED_BY_EMAIL']) && !empty($arQuote['ASSIGNED_BY_EMAIL']))
				$responsibleInfo .= ', '.$arQuote['ASSIGNED_BY_EMAIL'];
			if (isset($arQuote['ASSIGNED_BY_WORK_POSITION']) && !empty($arQuote['ASSIGNED_BY_WORK_POSITION']))
				$responsibleInfo .= ', '.$arQuote['ASSIGNED_BY_WORK_POSITION'];
			if (!empty($responsibleInfo) && !is_numeric($responsibleInfo) && $responsibleInfo != 'N' && $responsibleInfo != 'Y')
				$sBody .= GetMessage('CRM_QUOTE_SEARCH_FIELD_ASSIGNED_BY_INFO').": $responsibleInfo\n";
		}

		$sDetailURL = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_'.strtolower($sEntityType).'_show'),
			array(
				strtolower($sEntityType).'_id' => $arQuote['ID']
			)
		);

		$_arAttr = CCrmPerms::GetEntityAttr($sEntityType, $arQuote['ID']);

		if (empty($arSite))
		{
			$by="sort";
			$order="asc";
			$rsSite = $site->GetList($by, $order);
			while ($_arSite = $rsSite->Fetch())
				$arSite[] = $_arSite['ID'];
		}
		unset($site);

		$sattr_d = '';
		$sattr_s = '';
		$sattr_u = '';
		$sattr_o = '';
		$sattr2 = '';
		$arAttr = array();
		if (!isset($_arAttr[$arQuote['ID']]))
			$_arAttr[$arQuote['ID']] = array();

		$arAttr[] = $sEntityType; // for perm X
		foreach ($_arAttr[$arQuote['ID']] as $_s)
		{
			if ($_s[0] == 'U')
				$sattr_u = $_s;
			else if ($_s[0] == 'D')
				$sattr_d = $_s;
			else if ($_s[0] == 'S')
				$sattr_s = $_s;
			else if ($_s[0] == 'O')
				$sattr_o = $_s;
			$arAttr[] = $sEntityType.'_'.$_s;
		}
		$sattr = $sEntityType.'_'.$sattr_u;
		if (!empty($sattr_d))
		{
			$sattr .= '_'.$sattr_d;
			$arAttr[] = $sattr;
		}
		if (!empty($sattr_s))
		{
			$sattr2 = $sattr.'_'.$sattr_s;
			$arAttr[] = $sattr2;
			$arAttr[] = $sEntityType.'_'.$sattr_s;  // for perm X in status
		}
		if (!empty($sattr_o))
		{
			$sattr  .= '_'.$sattr_o;
			$sattr3 = $sattr2.'_'.$sattr_o;
			$arAttr[] = $sattr3;
			$arAttr[] = $sattr;
		}

		$arSitePath = array();
		foreach ($arSite as $sSite)
			$arSitePath[$sSite] = $sDetailURL;

		$arResult = Array(
			'LAST_MODIFIED' => $arQuote['DATE_MODIFY'],
			'DATE_FROM' => $arQuote['DATE_CREATE'],
			'TITLE' => GetMessage('CRM_'.$sEntityType).': '.$arQuote[$sNumber].', '.$arQuote[$sTitle],
			'PARAM1' => $sEntityType,
			'PARAM2' => $arQuote['ID'],
			'SITE_ID' => $arSitePath,
			'PERMISSIONS' => $arAttr,
			'BODY' => $sBody,
			'TAGS' => 'crm,'.strtolower($sEntityType).','.GetMessage('CRM_'.$sEntityType)
		);

		if ($bReindex)
			$arResult['ID'] = $sEntityType.'.'.$arQuote['ID'];

		return $arResult;
	}

	public static function GetDefaultStorageTypeID()
	{
		if(self::$STORAGE_TYPE_ID === StorageType::Undefined)
		{
			self::$STORAGE_TYPE_ID = intval(CUserOptions::GetOption('crm', 'quote_storage_type_id', StorageType::Undefined));
			if(self::$STORAGE_TYPE_ID === StorageType::Undefined
				|| !StorageType::isDefined(self::$STORAGE_TYPE_ID))
			{
				self::$STORAGE_TYPE_ID = StorageType::getDefaultTypeID();
			}
		}

		return self::$STORAGE_TYPE_ID;
	}

	public static function PrepareStorageElementIDs(&$arFields)
	{
		if(isset($arFields['STORAGE_ELEMENT_IDS']) && is_array($arFields['STORAGE_ELEMENT_IDS']))
		{
			return;
		}

		if(isset($arFields['~STORAGE_ELEMENT_IDS']))
		{
			$field = $arFields['~STORAGE_ELEMENT_IDS'];
		}
		elseif(isset($arFields['STORAGE_ELEMENT_IDS']))
		{
			$field = $arFields['STORAGE_ELEMENT_IDS'];
		}
		else
		{
			$field = '';
		}

		if(is_array($field))
		{
			$result = $field;
		}
		elseif(is_numeric($field))
		{
			$ID = (int)$field;
			if($ID <= 0)
			{
				$ID = isset($arFields['ID']) ? (int)$arFields['ID'] : (isset($arFields['~ID']) ? (int)$arFields['~ID'] : 0);
			}

			if($ID <= 0)
			{
				$result = array();
			}
			else
			{
				$result = self::LoadElementIDs($ID);
				$arUpdateFields = array('STORAGE_ELEMENT_IDS' => serialize($result));
				$table = CCrmActivity::TABLE_NAME;
				global $DB;
				$DB->QueryBind(
					'UPDATE '.$table.' SET '.$DB->PrepareUpdate($table, $arUpdateFields).' WHERE ID = '.$ID,
					$arUpdateFields,
					false
				);
			}
		}
		elseif(is_string($field) && $field !== '')
		{
			$result = unserialize($field);
		}
		else
		{
			$result = array();
		}

		$arFields['~STORAGE_ELEMENT_IDS'] = $arFields['STORAGE_ELEMENT_IDS'] = &$result;
		unset($result);
	}

	protected static function NormalizeStorageElementIDs(&$arElementIDs)
	{
		$result = array();
		foreach($arElementIDs as $elementID)
		{
			$result[] = intval($elementID);
		}

		return array_unique($result, SORT_NUMERIC);
	}

	private static function PrepareFileEvent($storageTypeID, $elementID, $action, &$arRow, &$arEvents)
	{
		$storageTypeID = intval($storageTypeID);
		$elementID = intval($elementID);
		$action = strtoupper(strval($action));

		$name = isset($arRow['SUBJECT']) ? strval($arRow['SUBJECT']) : '';
		if($name === '')
		{
			$name = "[{$arRow['ID']}]";
		}

		$arEventFiles = array();
		if($action === 'ADD' && $storageTypeID !== CCrmQuoteStorageType::Undefined)
		{
			$arEventFiles = self::MakeRawFiles($storageTypeID, array($elementID));
		}

		$arEvents[] = array(
			'EVENT_NAME' => GetMessage("CRM_QUOTE_FILE_{$action}"),
			'EVENT_TEXT_1' => $action !== 'ADD' ? self::ResolveStorageElementName($storageTypeID, $elementID) : '',
			'EVENT_TEXT_2' => '',
			'FILES' => $arEventFiles
		);
	}

	public static function MakeRawFiles($storageTypeID, array $arElementIDs)
	{
		return \Bitrix\Crm\Integration\StorageManager::makeFileArray($arElementIDs, $storageTypeID);
	}

	private static function ResolveStorageElementName($storageTypeID, $elementID)
	{
		return \Bitrix\Crm\Integration\StorageManager::getFileName($elementID, $storageTypeID);
	}

	public static function HandleStorageElementDeletion($storageTypeID, $elementID)
	{
		global $DB;

		$storageTypeID = (int)$storageTypeID;
		$elementID = (int)$elementID;

		$dbResult = $DB->Query(
			'SELECT QUOTE_ID FROM '.CCrmQuote::ELEMENT_TABLE_NAME.' WHERE STORAGE_TYPE_ID = '.$storageTypeID.' AND ELEMENT_ID = '.$elementID,
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);

		while($arResult = $dbResult->Fetch())
		{
			$entityID = isset($arResult['QUOTE_ID']) ? (int)$arResult['QUOTE_ID'] : 0;
			if($entityID <= 0)
			{
				continue;
			}

			$dbEntity = self::GetList(array(), array('ID' => $entityID), false, array('nTopCount' => 1), array('STORAGE_TYPE_ID', 'STORAGE_ELEMENT_IDS'));

			$arEntity = $dbEntity->Fetch();
			if(!is_array($arEntity))
			{
				continue;
			}

			$arEntity['STORAGE_TYPE_ID'] = isset($arEntity['STORAGE_TYPE_ID'])
				? (int)$arEntity['STORAGE_TYPE_ID'] : $storageTypeID;
			self::PrepareStorageElementIDs($arEntity);
			if(!empty($arEntity['STORAGE_ELEMENT_IDS']))
			{
				$arEntity['STORAGE_ELEMENT_IDS'] = array_diff($arEntity['STORAGE_ELEMENT_IDS'], array($elementID));
			}

			$quote = new CCrmQuote(false);
			$quote->Update($entityID, $arEntity, true, false);
		}
	}
	protected static function DeleteStorageElements($ID)
	{
		global $APPLICATION;

		$ID = intval($ID);
		if($ID <= 0)
		{
			$APPLICATION->throwException(GetMessage('CRM_QUOTE_ERR_INCORRECT_QUOTE_ID'));
			return false;
		}

		$dbRes = self::GetList(array(), array('=ID' => $ID), false, array('nTopCount' => 1), array('STORAGE_TYPE_ID', 'STORAGE_ELEMENT_IDS'));

		$arRes = $dbRes->Fetch();
		if(!is_array($arRes))
		{
			$APPLICATION->throwException(GetMessage('CRM_QUOTE_ERR_QUOTE_NOT_FOUND', array('#QUOTE_ID#' => $ID)));
			return false;
		}

		$storageTypeID = isset($arRes['STORAGE_TYPE_ID'])
			? intval($arRes['STORAGE_TYPE_ID']) : CCrmQuoteStorageType::Undefined;

		if($storageTypeID === CCrmQuoteStorageType::File)
		{
			self::PrepareStorageElementIDs($arRes);
			$arFileIDs = isset($arRes['STORAGE_ELEMENT_IDS']) ? $arRes['STORAGE_ELEMENT_IDS'] : array();
			foreach($arFileIDs as $fileID)
			{
				CFile::Delete($fileID);
			}
		}

		return true;
	}
	protected static function GetSaleOrderMap()
	{
		return array(
			'ID' => 'ID',
			'ACCOUNT_NUMBER' => 'QUOTE_NUMBER',
			'ORDER_TOPIC' => 'TITLE',
			'DATE_INSERT' => 'DATE_CREATE',
			'DATE_BILL' => 'BEGINDATE',
			'DATE_PAY_BEFORE' => 'CLOSEDATE',
			'PRICE' => 'OPPORTUNITY',
			'SHOULD_PAY' => 'OPPORTUNITY',
			'CURRENCY' => 'CURRENCY_ID',
			'PAY_SYSTEM_ID' => '',
			'TAX_VALUE' => 'TAX_VALUE',
			'USER_DESCRIPTION' => array('CONTENT', 'TERMS'),
			'PRICE_DELIVERY' => '',
			'DISCOUNT_VALUE' => '',
			'USER_ID' => '',
			'DELIVERY_ID' => ''
		);
	}
	protected static function GetCompanyPersonTypeMap()
	{
		return array(
			'COMPANY' => 'CLIENT_TITLE',
			'COMPANY_ADR' => 'CLIENT_ADDR',
			'CONTACT_PERSON' => 'CLIENT_CONTACT',
			'EMAIL' => 'CLIENT_EMAIL',
			'PHONE' => 'CLIENT_PHONE'
		);
	}
	protected static function GetContactPersonTypeMap()
	{
		return array(
			'FIO' => 'CLIENT_TITLE',
			'EMAIL' => 'CLIENT_EMAIL',
			'PHONE' => 'CLIENT_PHONE'
		);
	}
	public static function PrepareSalePaymentData(array &$arQuote)
	{
		$ID = isset($arQuote['ID']) ? intval($arQuote['ID']) : 0;
		if($ID <= 0)
		{
			return null;
		}

		$fieldMap = self::GetSaleOrderMap();
		$order = array();
		foreach($fieldMap as $orderFileldID => $fileldID)
		{
			if(!is_array($fileldID))
			{
				$order[$orderFileldID] = isset($arQuote[$fileldID]) ? $arQuote[$fileldID] : '';
			}
			else
			{
				$v = '';
				foreach($fileldID as $item)
				{
					$s = isset($arQuote[$item]) ? trim($arQuote[$item]) : '';
					if($s === '')
					{
						continue;
					}

					if(preg_match('/<br(\/)?>$/i', $v) !== 1)
					{
						$v .= '<br/>';
					}
					$v .= $s;
				}
				$order[$orderFileldID] = $v;
			}
		}

		$personTypeIDs = CCrmPaySystem::getPersonTypeIDs();
		$personTypeID = isset($arQuote['PERSON_TYPE_ID']) ? intval($arQuote['PERSON_TYPE_ID']) : 0;

		$propertyMap = isset($personTypeIDs['COMPANY']) && intval($personTypeIDs['COMPANY']) === $personTypeID
			? self::GetCompanyPersonTypeMap()
			: self::GetContactPersonTypeMap();

		$properties = array();
		foreach($propertyMap as $propertyFileldID => $fileldID)
		{
			$properties[$propertyFileldID] = isset($arQuote[$fileldID]) ? $arQuote[$fileldID] : '';
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(self::$sUFEntityID, 0, LANGUAGE_ID);
		$supportedUserTypeIDs = array('string', 'double', 'integer', 'boolean', 'datetime');
		foreach($userFields as $name => &$userField)
		{
			$fieldType = $userField['USER_TYPE_ID'];
			if(!isset($arQuote[$name]) || !in_array($fieldType, $supportedUserTypeIDs, true))
			{
				continue;
			}

			$fieldValue = $arQuote[$name];
			if($fieldType === 'boolean')
			{
				$fieldValue = GetMessage(intval($fieldValue) > 0 ? 'MAIN_YES' : 'MAIN_NO');
			}
			$properties[$name] = $userField['EDIT_FORM_LABEL'].': '.$fieldValue;
		}
		unset($userField);

		$productRows = self::LoadProductRows($ID);
		$currencyID = isset($arQuote['CURRENCY_ID']) ? $arQuote['CURRENCY_ID'] : '';

		$calculatedOrder = CCrmSaleHelper::Calculate(
			$productRows,
			$currencyID,
			$personTypeID,
			false,
			SITE_ID,
			array('LOCATION_ID' => isset($arQuote['LOCATION_ID']) ? intval($arQuote['LOCATION_ID']) : 0)
		);

		$taxList = isset($calculatedOrder['TAX_LIST']) ? $calculatedOrder['TAX_LIST'] : array();
		foreach($taxList as &$taxInfo)
		{
			$taxInfo['TAX_NAME'] = isset($taxInfo['NAME']) ? $taxInfo['NAME'] : '';
		}
		unset($taxInfo);

		return array(
			'ORDER' => $order,
			'PROPERTIES' => $properties,
			'CART_ITEMS' => $calculatedOrder['BASKET_ITEMS'],
			'TAX_LIST' => $taxList
		);
	}
	public static function Rebind($ownerTypeID, $oldID, $newID)
	{
		global $DB;

		$ownerTypeID = intval($ownerTypeID);
		$oldID = intval($oldID);
		$newID = intval($newID);
		$tableName = CCrmQuote::TABLE_NAME;

		if($ownerTypeID === CCrmOwnerType::Contact)
		{
			$DB->Query(
				"UPDATE {$tableName} SET CONTACT_ID = {$newID} WHERE CONTACT_ID = {$oldID}",
				false,
				'File: '.__FILE__.'<br>Line: '.__LINE__
			);
		}
		elseif($ownerTypeID === CCrmOwnerType::Company)
		{
			$DB->Query(
				"UPDATE {$tableName} SET COMPANY_ID = {$newID} WHERE COMPANY_ID = {$oldID}",
				false,
				'File: '.__FILE__.'<br>Line: '.__LINE__
			);
		}
	}
	public static function PrepareStorageElementInfo(&$arFields)
	{
		$storageTypeID = isset($arFields['STORAGE_TYPE_ID']) ? (int)$arFields['STORAGE_TYPE_ID'] : StorageType::Undefined;
		if(!StorageType::IsDefined($storageTypeID))
		{
			$storageTypeID = self::GetDefaultStorageTypeID();
		}

		$storageElementIDs = isset($arFields['STORAGE_ELEMENT_IDS']) && is_array($arFields['STORAGE_ELEMENT_IDS'])
			? $arFields['STORAGE_ELEMENT_IDS'] : array();

		if($storageTypeID === StorageType::File)
		{
			$arFields['FILES'] = array();
			foreach($storageElementIDs as $fileID)
			{
				$arData = CFile::GetFileArray($fileID);
				if(is_array($arData))
				{
					$arFields['FILES'][] = array(
						'fileID' => $arData['ID'],
						'fileName' => $arData['FILE_NAME'],
						'fileURL' =>  CCrmUrlUtil::UrnEncode($arData['SRC']),
						'fileSize' => $arData['FILE_SIZE']
					);
				}
			}
		}
		elseif($storageTypeID === StorageType::WebDav || $storageTypeID === StorageType::Disk)
		{
			$infos = array();
			foreach($storageElementIDs as $elementID)
			{
				$infos[] = StorageManager::getFileInfo($elementID, $storageTypeID);
			}
			$arFields[$storageTypeID === StorageType::Disk ? 'DISK_FILES' : 'WEBDAV_ELEMENTS'] = &$infos;
			unset($infos);
		}
	}
}

/**
 * @deprecated Please use \Bitrix\Crm\Integration\StorageType
 */
class CCrmQuoteStorageType
{
	const Undefined = 0;
	const File = 1;
	const WebDav = 2;
	const Disk = 3;
	const Last = self::Disk;

	public static function IsDefined($typeID)
	{
		$typeID = intval($typeID);
		return $typeID > self::Undefined && $typeID <= self::Last;
	}
}
