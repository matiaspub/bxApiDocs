<?php

if (!CModule::IncludeModule('sale'))
	return;

IncludeModuleLangFile(__FILE__);

class CAllCrmInvoice
{
	static public $sUFEntityID = 'CRM_INVOICE';
	public $LAST_ERROR = '';
	public $cPerms = null;
	protected $bCheckPermission = true;
	protected static $TYPE_NAME = 'INVOICE';
	private static $INVOICE_STATUSES = null;
	private static $INVOICE_PROPERTY_INFOS = null;
	private static $INVOICE_PAY_SYSTEM_TYPES = null;
	private static $arCurrentPermType = null;
	private static $arinvoicePropertiesAllowed = null;

	function __construct($bCheckPermission = true)
	{
		$this->bCheckPermission = $bCheckPermission;
		$this->cPerms = CCrmPerms::GetCurrentUserPermissions();
	}

	public function CheckFields(&$arFields, $ID = false, $bStatusSuccess = true, $bStatusFailed = true)
	{
		$this->LAST_ERROR = '';

		$bTaxMode = CCrmTax::isTaxMode();

		if (!isset($arFields['PRODUCT_ROWS']) || !is_array($arFields['PRODUCT_ROWS']) || count($arFields['PRODUCT_ROWS']) === 0)
		{
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_EMPTY_INVOICE_SPEC')."<br />\n";
		}
		else
		{
			$invalidQuantityExists = false;
			foreach ($arFields['PRODUCT_ROWS'] as $productRow)
			{
				if (!isset($productRow['QUANTITY']) || round(doubleval($productRow['QUANTITY']), 2) <= 0.0)
				{
					$invalidQuantityExists = true;
					break;
				}
			}
			unset($productRow);

			if ($invalidQuantityExists)
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_INVOICE_SPEC_INVALID_QUANTITY')."<br />\n";

			unset($invalidQuantityExists);
		}

		if ($ID !== false && isset($arFields['ACCOUNT_NUMBER']))
		{
			if (strlen($arFields['ACCOUNT_NUMBER']) <= 0)
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_ACCOUNT_NUMBER')))."<br />\n";
		}

		if (($ID == false || isset($arFields['ORDER_TOPIC'])) && strlen($arFields['ORDER_TOPIC']) <= 0)
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_ORDER_TOPIC')))."<br />\n";

		if (!empty($arFields['ORDER_TOPIC']) && strlen($arFields['ORDER_TOPIC']) > 255)
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_ORDER_TOPIC')))."<br />\n";

		if (!empty($arFields['COMMENTS']) && strlen($arFields['COMMENTS']) > 2000)
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_COMMENTS')))
				.' ('.GetMessage('CRM_FIELD_COMMENTS_INCORRECT_INFO').").<br />\n";

		if (!empty($arFields['USER_DESCRIPTION']) && strlen($arFields['USER_DESCRIPTION']) > 250)
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_USER_DESCRIPTION')))
				.' ('.GetMessage('CRM_FIELD_USER_DESCRIPTION_INCORRECT_INFO').").<br />\n";

		if (empty($arFields['STATUS_ID']) || strlen($arFields['STATUS_ID']) !== 1)
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_STATUS_ID')))."<br />\n";

		if ($bStatusSuccess)
		{
			if (!empty($arFields['PAY_VOUCHER_NUM']) && strlen($arFields['PAY_VOUCHER_NUM']) > 20)
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_PAY_VOUCHER_NUM')))."<br />\n";
			if (!empty($arFields['PAY_VOUCHER_DATE']) && !CheckDateTime($arFields['PAY_VOUCHER_DATE']))
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_PAY_VOUCHER_DATE')))."<br />\n";
			if (!empty($arFields['REASON_MARKED']) && strlen($arFields['REASON_MARKED']) > 255)
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_REASON_MARKED_SUCCESS')))."<br />\n";
		}
		elseif ($bStatusFailed)
		{
			if (!empty($arFields['DATE_MARKED']) && !CheckDateTime($arFields['DATE_MARKED']))
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_DATE_MARKED')))."<br />\n";
			if (!empty($arFields['REASON_MARKED']) && strlen($arFields['REASON_MARKED']) > 255)
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_REASON_MARKED')))."<br />\n";
		}

		if (!isset($arFields['PERSON_TYPE_ID']) || intval($arFields['PERSON_TYPE_ID']) <= 0
			|| (intval($arFields['UF_COMPANY_ID']) <= 0 && intval($arFields['UF_CONTACT_ID']) <= 0))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_PAYER_IS_MISSING')."<br />\n";

		if ($bTaxMode)
		{
			if (!isset($arFields['PR_LOCATION']) || intval($arFields['PR_LOCATION']) <= 0)
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_PR_LOCATION')))."<br />\n";
		}

		if (!isset($arFields['PAY_SYSTEM_ID']) || intval($arFields['PAY_SYSTEM_ID']) <= 0)
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_PAY_SYSTEM_ID')))."<br />\n";

		if (!empty($arFields['DATE_INSERT']) && !CheckDateTime($arFields['DATE_INSERT']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_DATE_INSERT')))."<br />\n";

		if (!empty($arFields['DATE_BILL']) && !CheckDateTime($arFields['DATE_BILL']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_DATE_BILL')))."<br />\n";

		if (!empty($arFields['DATE_PAY_BEFORE']) && !CheckDateTime($arFields['DATE_PAY_BEFORE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_DATE_PAY_BEFORE')))."<br />\n";

		if (strlen($this->LAST_ERROR) > 0)
			return false;

		return true;
	}

	public function CheckFieldsUpdate(&$arFields, $ID = false)
	{
		$this->LAST_ERROR = '';

		if (isset($arFields['ORDER_TOPIC']) && empty($arFields['ORDER_TOPIC']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_ORDER_TOPIC')))."<br />\n";

		if (strlen($this->LAST_ERROR) > 0)
			return false;

		return true;
	}

	public static function GetUserFieldEntityID()
	{
		return self::$sUFEntityID;
	}
	public static function GetFieldCaption($fieldName)
	{
		if($fieldName === 'CURRENCY_ID')
		{
			$fieldName = 'CURRENCY';
		}
		elseif($fieldName === 'LOCATION_ID')
		{
			$fieldName = 'PR_LOCATION';
		}

		$result = GetMessage("CRM_INVOICE_FIELD_{$fieldName}");
		return is_string($result) ? $result : '';
	}
	public static function GetList($arOrder = Array("ID"=>"DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		global $USER;
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		if (!is_array($arGroupBy))
		{
			if (is_array($arSelectFields) && (count($arSelectFields) === 0 || in_array('*', $arSelectFields)))
			{
				if (count($arSelectFields) === 0)
					$arSelectFields[] = '*';
				if (!in_array('UF_QUOTE_ID', $arSelectFields))
					$arSelectFields[] = 'UF_QUOTE_ID';
				if (!in_array('UF_DEAL_ID', $arSelectFields))
					$arSelectFields[] = 'UF_DEAL_ID';
				if (!in_array('UF_COMPANY_ID', $arSelectFields))
					$arSelectFields[] = 'UF_COMPANY_ID';
				if (!in_array('UF_CONTACT_ID', $arSelectFields))
					$arSelectFields[] = 'UF_CONTACT_ID';
			}
		}

		// permissions
		if (isset($arFilter['CUSTOM_SUBQUERY']))
			unset($arFilter['CUSTOM_SUBQUERY']);
		if (!(is_object($USER) && $USER->IsAdmin())
			&& (!array_key_exists('CHECK_PERMISSIONS', $arFilter) || $arFilter['CHECK_PERMISSIONS'] !== 'N')
		)
		{
			$arFilter['CUSTOM_SUBQUERY'] = array('CCrmInvoice', '__callbackPermissionsWhereCondition');
			$arPermType = array();
			if (!isset($arFilter['PERMISSION']))
				$arPermType['PERMISSION'] = 'READ';
			else
				$arPermType	= is_array($arFilter['PERMISSION']) ? $arFilter['PERMISSION'] : array($arFilter['PERMISSION']);
			self::$arCurrentPermType = $arPermType;
		}

		$result = CSaleOrder::getList($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
		self::$arCurrentPermType = null;

		return $result;
	}
	static public function BuildEntityAttr($userID, $arAttr = array())
	{
		$userID = (int)$userID;
		$arResult = array("U{$userID}");
		if(isset($arAttr['OPENED']) && $arAttr['OPENED'] == 'Y')
		{
			$arResult[] = 'O';
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
			array('ID', 'RESPONSIBLE_ID')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		while($fields = $dbResult->Fetch())
		{
			$ID = intval($fields['ID']);
			$assignedByID = isset($fields['RESPONSIBLE_ID']) ? intval($fields['RESPONSIBLE_ID']) : 0;
			if($assignedByID <= 0)
			{
				continue;
			}

			$entityAttrs = self::BuildEntityAttr($assignedByID);
			CCrmPerms::UpdateEntityAttr('INVOICE', $ID, $entityAttrs);
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

	public static function BuildPermSql($sAliasPrefix = 'O', $mPermType = 'READ', $arOptions = array())
	{
		$resultSql = CCrmPerms::BuildSql('INVOICE', $sAliasPrefix, $mPermType, $arOptions);

		if ($resultSql === false)
		{
			return '(1=0)';
		}
		else if ($resultSql === '')
		{
			return '(1=1)';
		}

		return '('.$resultSql.')';
	}

	public static function __callbackPermissionsWhereCondition($arFields = array())
	{
		return self::BuildPermSql('O', self::$arCurrentPermType);
	}

	public static function GetStatusList()
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$arStatus = array();

		$res = CSaleStatus::GetList(array('SORT' => 'ASC'), array('LID' => LANGUAGE_ID), false, false, array('ID', 'SORT', 'NAME'));
		$id = 1;
		while ($row = $res->Fetch())
		{
			// Special status, not used in CRM
			if ($row['ID'] === 'F') continue;

			$arStatus[$row['ID']] = array(
				'ID' => $id,
				'ENTITY' => 'INVOICE_STATUS',
				'STATUS_ID' => $row['ID'],
				'NAME' => $row['NAME'],
				'NAME_INIT' => '',
				'SORT' => $id * 10,
				'SYSTEM' => 'N'
			);
			if (in_array($row['ID'], array('P', 'D')))
			{
				if ($row['ID'] === 'P') $arStatus[$row['ID']]['NAME_INIT'] = GetMessage('CRM_INVOICE_STATUS_P');
				elseif ($row['ID'] === 'D') $arStatus[$row['ID']]['NAME_INIT'] = GetMessage('CRM_INVOICE_STATUS_D');

				$arStatus[$row['ID']]['SYSTEM'] = 'Y';
			}
			$id++;
		}

		return $arStatus;
	}

	public static function GetNeutralStatusIds()
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$arResult = array();

		$arStatus = self::GetStatusList();
		$successSort = $arStatus['P']['SORT'];
		foreach ($arStatus as $fields)
		{
			if ($fields['STATUS_ID'] !== 'P' && $fields['SORT'] <= $successSort)
				$arResult[] = $fields['STATUS_ID'];
		}

		return $arResult;
	}

	public static function GetByID($ID, $bCheckPerms = true)
	{
		$arFilter = array('ID' => intval($ID));
		if (!$bCheckPerms)
		{
			$arFilter['CHECK_PERMISSIONS'] = 'N';
		}

		$dbRes = self::GetList(array(/*'ID' => 'ASC'*/), $arFilter);
		return $dbRes->Fetch();
	}

	private static function __fGetUserShoppingCart($arProduct, $LID, $recalcOrder)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$arOrderProductPrice = array();

		foreach($arProduct as $key => $val)
		{
			//$arSortNum[] = $val['PRICE_DEFAULT'];
			$arProduct[$key]["PRODUCT_ID"] = intval($val["PRODUCT_ID"]);
			$arProduct[$key]["TABLE_ROW_ID"] = $key;
		}
		//if (count($arProduct) > 0 && count($arSortNum) > 0)
		//	array_multisort($arSortNum, SORT_DESC, $arProduct);

		$i = 0;
		foreach($arProduct as $key => $val)
		{
			$val["QUANTITY"] = abs(str_replace(",", ".", $val["QUANTITY"]));
			$val["QUANTITY_DEFAULT"] = $val["QUANTITY"];
			$val["PRICE"] = str_replace(",", ".", $val["PRICE"]);

			// Y is used when custom price was set in the admin form
			if ($val["CALLBACK_FUNC"] == "Y")
			{
				$val["CALLBACK_FUNC"] = false;
				$val["CUSTOM_PRICE"] = "Y";

				if (isset($val["BASKET_ID"]) || intval($val["BASKET_ID"]) > 0)
				{
					CSaleBasket::Update($val["BASKET_ID"], array("CUSTOM_PRICE" => "Y"));
				}

				//$val["DISCOUNT_PRICE"] = $val["PRICE_DEFAULT"] - $val["PRICE"];
			}

			$arOrderProductPrice[$i] = $val;
			$arOrderProductPrice[$i]["TABLE_ROW_ID"] = $val["TABLE_ROW_ID"];
			$arOrderProductPrice[$i]["PRODUCT_ID"] = intval($val["PRODUCT_ID"]);
			$arOrderProductPrice[$i]["NAME"] = htmlspecialcharsback($val["NAME"]);
			$arOrderProductPrice[$i]["LID"] = $LID;
			$arOrderProductPrice[$i]["CAN_BUY"] = "Y";

			if (!isset($val["BASKET_ID"]) || $val["BASKET_ID"] == "")
			{
				/*if ($val["CALLBACK_FUNC"] == "Y")
				{
					$arOrderProductPrice[$i]["CALLBACK_FUNC"] = '';
					$arOrderProductPrice[$i]["DISCOUNT_PRICE"] = 0;
				}*/
			}
			else
			{
				$arOrderProductPrice[$i]["ID"] = intval($val["BASKET_ID"]);

				if ($recalcOrder != "Y" && $arOrderProductPrice[$i]["CALLBACK_FUNC"] != false)
					unset($arOrderProductPrice[$i]["CALLBACK_FUNC"]);

				$arNewProps = array();
				if (is_array($val["PROPS"]))
				{
					foreach($val["PROPS"] as $k => $v)
					{
						if ($v["NAME"] != "" AND $v["VALUE"] != "")
							$arNewProps[$k] = $v;
					}
				}
				else
					$arNewProps = array("NAME" => "", "VALUE" => "", "CODE" => "", "SORT" => "");

				$arOrderProductPrice[$i]["PROPS"] = $arNewProps;
			}
			$i++;
		}//endforeach $arProduct

		return $arOrderProductPrice;
	}

	private static function __fGetLocationPropertyId($personTypeId)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$locationPropertyId = null;
		$dbOrderProps = CSaleOrderProps::GetList(
			array("SORT" => "ASC"),
			//array("PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"], "ACTIVE" => "Y", "UTIL" => "N"),
			array("PERSON_TYPE_ID" => $personTypeId, "ACTIVE" => "Y", "TYPE" => "LOCATION", "IS_LOCATION" => "Y", "IS_LOCATION4TAX" => "Y"),
			false,
			false,
			/*array("ID", "NAME", "TYPE", "IS_LOCATION", "IS_LOCATION4TAX", "IS_PROFILE_NAME", "IS_PAYER", "IS_EMAIL",
				"REQUIED", "SORT", "IS_ZIP", "CODE", "DEFAULT_VALUE")*/
			array("ID", "NAME", "TYPE", "IS_LOCATION", "IS_LOCATION4TAX", /*"IS_PROFILE_NAME", "IS_PAYER", "IS_EMAIL",*/
				"REQUIED", "SORT", /*"IS_ZIP", */"CODE", "DEFAULT_VALUE")
		);
		if ($arOrderProp = $dbOrderProps->Fetch())
			$locationPropertyId = $arOrderProp['ID'];
		else
			return false;
		$locationPropertyId = intval($locationPropertyId);
		if ($locationPropertyId <= 0)
			return false;
		return $locationPropertyId;
	}

	public static function QuickRecalculate($arFields, $siteId = SITE_ID)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return array('err'=> '1');
		}

		$tmpOrderId = isset($arFields['ID']) ? intval($arFields['ID']) : 0;
		if($tmpOrderId < 0)
		{
			$tmpOrderId = 0;
		}

		$saleUserId = intval(CSaleUser::GetAnonymousUserID());
		if ($saleUserId <= 0)
		{
			return array('err'=> '2');
		}

		$arProduct = isset($arFields['PRODUCT_ROWS']) && is_array($arFields['PRODUCT_ROWS'])
			? $arFields['PRODUCT_ROWS'] : array();
		if(empty($arProduct))
		{
			return array('err'=> '3');
		}

		$currencyId = CCrmInvoice::GetCurrencyID($siteId);
		foreach ($arProduct as &$productRow)
		{
			if (isset($productRow['PRODUCT_NAME']))
			{
				$productRow['NAME'] = $productRow['PRODUCT_NAME'];
				unset($productRow['PRODUCT_NAME']);
			}
			if (isset($productRow['PRICE']))
			{
				$productRow['PRICE_DEFAULT'] = $productRow['PRICE'];
			}
			if (!isset($productRow['CURRENCY']))
			{
				$productRow['CURRENCY'] = $currencyId;
			}
			$productRow['MODULE'] = 'catalog';
			$productRow['PRODUCT_PROVIDER_CLASS'] = 'CCatalogProductProvider';
			$productRow['CALLBACK_FUNC'] = 'Y';
		}
		unset($productRow);

		$arOrderProductPrice = self::__fGetUserShoppingCart($arProduct, $siteId, 'N');

		foreach ($arOrderProductPrice as &$arItem) // tmp hack not to update basket quantity data from catalog
		{
			$arItem['ID_TMP'] = $arItem['ID'];
			unset($arItem['ID']);
		}
		unset($arItem);

		$arErrors = array();
		$arShoppingCart = CSaleBasket::DoGetUserShoppingCart($siteId, $saleUserId, $arOrderProductPrice, $arErrors, array(), $tmpOrderId);

		foreach ($arShoppingCart as $key => &$arItem)
		{
			$arItem['ID'] = $arItem['ID_TMP'];
			unset($arItem['ID_TMP']);
		}
		unset($arItem);

		$personTypeId = isset($arFields['PERSON_TYPE_ID']) ? intval($arFields['PERSON_TYPE_ID']) : 0;
		if($personTypeId <= 0)
		{
			$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
			if (isset($arPersonTypes['CONTACT']))
				$personTypeId = intval($arPersonTypes['CONTACT']);
		}
		if ($personTypeId <= 0)
		{
			return array('err'=> '4');
		}

		$arOrderPropsValues = array();
		if (isset($arFields['INVOICE_PROPERTIES']) && is_array($arFields['INVOICE_PROPERTIES']) && count($arFields['INVOICE_PROPERTIES']) > 0)
		{
			$arOrderPropsValues = $arFields['INVOICE_PROPERTIES'];
		}
		if (isset($arFields['INVOICE_PROPERTIES']))
		{
			unset($arFields['INVOICE_PROPERTIES']);
		}
		if (count($arOrderPropsValues) <= 0)
		{
			return array('err'=> '5');
		}

		$deliveryId = null;
		$paySystemId = isset($arFields['PAY_SYSTEM_ID']) ? intval($arFields['PAY_SYSTEM_ID']) : 0;
		$arOptions = array();
		$arErrors = array();
		$arWarnings = array();

		return CAllSaleOrder::DoCalculateOrder(
			$siteId,
			$saleUserId,
			$arShoppingCart,
			$personTypeId,
			$arOrderPropsValues,
			$deliveryId,
			$paySystemId,
			$arOptions,
			$arErrors,
			$arWarnings
		);
	}

	public function Add($arFields, &$arRecalculated = false, $siteId = SITE_ID, $options = array())
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$bRecalculate = is_array($arRecalculated);
		$orderID = false;
		$tmpOrderId = (intval($arFields['ID']) <= 0) ? 0 : $arFields['ID'];
		if (isset($arFields['ID']))
		{
			unset($arFields['ID']);
		}

		$arPrevOrder = ($tmpOrderId !== 0) ? CSaleOrder::GetByID($tmpOrderId) : null;

		$userId = CCrmSecurityHelper::GetCurrentUserID();

		if (!isset($arFields['RESPONSIBLE_ID']) || (int)$arFields['RESPONSIBLE_ID'] <= 0)
		{
			if (is_array($arPrevOrder) && isset($arPrevOrder['RESPONSIBLE_ID']) && intval($arPrevOrder['RESPONSIBLE_ID']) > 0)
				$arFields['RESPONSIBLE_ID'] = $arPrevOrder['RESPONSIBLE_ID'];
			else
				$arFields['RESPONSIBLE_ID'] = $userId;
		}

		$orderStatus = '';
		if (isset($arFields['STATUS_ID']))
		{
			$orderStatus = $arFields['STATUS_ID'];
			unset($arFields['STATUS_ID']);
		}

		// prepare entity permissions
		$arAttr = array();
		if (!empty($arFields['OPENED']))
			$arAttr['OPENED'] = $arFields['OPENED'];
		$sPermission = ($tmpOrderId > 0) ? 'WRITE' : 'ADD';
		if($this->bCheckPermission)
		{
			$arEntityAttr = self::BuildEntityAttr($userId, $arAttr);
			$userPerms = ($userId == CCrmPerms::GetCurrentUserID()) ? $this->cPerms : CCrmPerms::GetUserPermissions($userId);
			$sEntityPerm = $userPerms->GetPermType('INVOICE', $sPermission, $arEntityAttr);
			if ($sEntityPerm == BX_CRM_PERM_NONE)
			{
				$this->LAST_ERROR = GetMessage('CRM_PERMISSION_DENIED');
				$GLOBALS['APPLICATION']->ThrowException($this->LAST_ERROR);
				return false;
			}

			$responsibleID = intval($arFields['RESPONSIBLE_ID']);
			if ($sEntityPerm == BX_CRM_PERM_SELF && $responsibleID != $userId)
			{
				$arFields['RESPONSIBLE_ID'] = $userId;
			}
			if ($sEntityPerm == BX_CRM_PERM_OPEN && $userId == $responsibleID)
			{
				$arFields['OPENED'] = 'Y';
			}
		}
		$responsibleID = intval($arFields['RESPONSIBLE_ID']);
		$arEntityAttr = self::BuildEntityAttr($responsibleID, $arAttr);
		$userPerms = ($responsibleID == CCrmPerms::GetCurrentUserID()) ? $this->cPerms : CCrmPerms::GetUserPermissions($responsibleID);
		$sEntityPerm = $userPerms->GetPermType('INVOICE', $sPermission, $arEntityAttr);
		$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);

		if ($tmpOrderId !== 0 && !isset($arFields['PRODUCT_ROWS']) && !isset($arFields['INVOICE_PROPERTIES']))
		{
			if(!is_array($arPrevOrder))
			{
				return false;
			}

			$prevResponsibleID = isset($arPrevOrder['RESPONSIBLE_ID']) ? intval($arPrevOrder['RESPONSIBLE_ID']) : 0;
			$responsibleID = isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0;
			$prevStatusID = isset($arPrevOrder['STATUS_ID']) ? $arPrevOrder['STATUS_ID'] : '';

			// simple update order fields
			$CSaleOrder = new CSaleOrder();
			$orderID = $CSaleOrder->Update($tmpOrderId, $arFields);

			$registerSonetEvent = isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true;

			if(is_int($orderID) && $orderID > 0)
			{
				if($registerSonetEvent)
				{
					$newQuoteID = isset($arFields['UF_QUOTE_ID']) ? intval($arFields['UF_QUOTE_ID']) : 0;
					$oldQuoteID = isset($arPrevOrder['UF_QUOTE_ID']) ? intval($arPrevOrder['UF_QUOTE_ID']) : 0;

					$newDealID = isset($arFields['UF_DEAL_ID']) ? intval($arFields['UF_DEAL_ID']) : 0;
					$oldDealID = isset($arPrevOrder['UF_DEAL_ID']) ? intval($arPrevOrder['UF_DEAL_ID']) : 0;

					$newCompanyID = isset($arFields['UF_COMPANY_ID']) ? intval($arFields['UF_COMPANY_ID']) : 0;
					$oldCompanyID = isset($arPrevOrder['UF_COMPANY_ID']) ? intval($arPrevOrder['UF_COMPANY_ID']) : 0;

					$newContactID = isset($arFields['UF_CONTACT_ID']) ? intval($arFields['UF_CONTACT_ID']) : 0;
					$oldContactID = isset($arPrevOrder['UF_CONTACT_ID']) ? intval($arPrevOrder['UF_CONTACT_ID']) : 0;

					$parents = array();
					$parentsChanged = $newQuoteID !== $oldQuoteID || $newDealID !== $oldDealID
						|| $newCompanyID !== $oldCompanyID || $newContactID !== $oldContactID;
					if($parentsChanged)
					{
						if($newQuoteID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Quote,
								'ENTITY_ID' => $newQuoteID
							);
						}

						if($newDealID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
								'ENTITY_ID' => $newDealID
							);
						}

						if($newCompanyID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
								'ENTITY_ID' => $newCompanyID
							);
						}

						if($newContactID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
								'ENTITY_ID' => $newContactID
							);
						}
					}

					$oldOrderStatus = isset($arPrevOrder['STATUS_ID']) ? $arPrevOrder['STATUS_ID'] : '';
					self::SynchronizeLiveFeedEvent(
						$orderID,
						array(
							'PROCESS_PARENTS' => $parentsChanged,
							'PARENTS' => $parents,
							'REFRESH_DATE' => $orderStatus !== $oldOrderStatus,
							'START_RESPONSIBLE_ID' => $prevResponsibleID,
							'FINAL_RESPONSIBLE_ID' => $responsibleID,
							'TOPIC' => isset($arPrevOrder['ORDER_TOPIC']) ? $arPrevOrder['ORDER_TOPIC'] : $orderID
						)
					);
				}

				if($responsibleID !== $prevResponsibleID)
				{
					CCrmSonetSubscription::ReplaceSubscriptionByEntity(
						CCrmOwnerType::Invoice,
						$orderID,
						CCrmSonetSubscriptionType::Responsibility,
						$responsibleID,
						$prevResponsibleID,
						$registerSonetEvent
					);
				}
			}
		}
		else
		{
			// check product rows
			if (!isset($arFields['PRODUCT_ROWS']) ||
				!is_array($arFields['PRODUCT_ROWS']) ||
				count($arFields['PRODUCT_ROWS']) <= 0)
				return false;
			$arProduct = $arFields['PRODUCT_ROWS'];

			// prepare shopping cart data
			// <editor-fold defaultstate="collapsed" desc="prepare shopping cart data ...">

			// get xml_id fields
			$catalogXmlId = CCrmCatalog::GetDefaultCatalogXmlId();
			$arNewProducts = array();
			$bGetBasketXmlIds = false;
			foreach ($arProduct as &$productRow)
			{
				if (isset($productRow['ID']) && intval($productRow['ID']) === 0 && isset($productRow['PRODUCT_ID']))
					$arNewProducts[] = $productRow['PRODUCT_ID'];
				else
					$bGetBasketXmlIds = true;
			}
			unset($productRow);
			$arXmlIds = array();
			$oldProductRows = null;
			if ($bGetBasketXmlIds && intval($tmpOrderId) > 0)
			{
				$oldProductRows = CCrmInvoice::GetProductRows($tmpOrderId);
				if (count($oldProductRows) > 0)
				{
					foreach ($oldProductRows as $row)
					{
						$arXmlIds[intval($row['ID'])][$row['PRODUCT_ID']] = array(
							'CATALOG_XML_ID' => $row['CATALOG_XML_ID'],
							'PRODUCT_XML_ID' => $row['PRODUCT_XML_ID']
						);
					}
					unset($row);
				}
			}
			unset($bGetBasketXmlIds);
			if (count($arNewProducts) > 0)
			{
				$dbRes = CCrmProduct::GetList(array(), array('ID' => $arNewProducts), array('ID', 'XML_ID'));
				while ($row = $dbRes->Fetch())
				{
					$arXmlIds[0][$row['ID']] = array(
						'CATALOG_XML_ID' => $catalogXmlId,
						'PRODUCT_XML_ID' => $row['XML_ID']
					);
				}
				unset($dbRes, $row);
			}
			unset($arNewProducts, $arOldProducts);

			// products without measures
			$productMeasures = array();
			$productId = 0;
			$productIds = array();
			foreach ($arProduct as $productRow)
			{
				$productId = intval($productRow['PRODUCT_ID']);
				if ($productId > 0
					&& (!array_key_exists('MEASURE_CODE', $productRow) || intval($productRow['MEASURE_CODE']) <= 0))
				{
					$productIds[] = $productId;
				}
			}
			unset($productId, $productRow);
			if (count($productIds) > 0)
				$productMeasures = \Bitrix\Crm\Measure::getProductMeasures($productIds);
			unset($productIds);

			$currencyId = CCrmInvoice::GetCurrencyID($siteId);
			$i = 0;
			$defaultMeasure = null;
			$oldProductRowsById = null;
			foreach ($arProduct as &$productRow)
			{
				$productXmlId = $catalogXmlId = null;
				$rowIndex = intval($productRow['ID']);
				$productId = $productRow['PRODUCT_ID'];
				$isCustomized = (isset($productRow['CUSTOMIZED']) && $productRow['CUSTOMIZED'] === 'Y');
				$productRow['MODULE'] = $productRow['PRODUCT_PROVIDER_CLASS'] = '';
				if($productId > 0)
				{
					if (!$isCustomized)
					{
						$productRow['MODULE'] = 'catalog';
						$productRow['PRODUCT_PROVIDER_CLASS'] = 'CCatalogProductProvider';
					}
					if (is_array($arXmlIds[$rowIndex])
						&& isset($arXmlIds[$rowIndex][$productId]))
					{
						$catalogXmlId = $arXmlIds[$rowIndex][$productId]['CATALOG_XML_ID'];
						$productXmlId = $arXmlIds[$rowIndex][$productId]['PRODUCT_XML_ID'];
					}
					$productRow['CATALOG_XML_ID'] = $catalogXmlId;
					$productRow['PRODUCT_XML_ID'] = $productXmlId;
				}
				else
				{
					$productRow["PRODUCT_XML_ID"] = "CRM-".randString(8);
					$ri = new \Bitrix\Main\Type\RandomSequence($productRow["PRODUCT_XML_ID"]);
					$productRow["PRODUCT_ID"] = $ri->rand(1000000, 9999999);
					$productRow['CATALOG_XML_ID'] = '';
				}
				if($isCustomized)
					$productRow['CUSTOM_PRICE'] = 'Y';
				if (isset($productRow['PRODUCT_NAME']))
				{
					$productRow['NAME'] = $productRow['PRODUCT_NAME'];
					unset($productRow['PRODUCT_NAME']);
				}
				if (isset($productRow['PRICE']))
					$productRow['PRICE_DEFAULT'] = $productRow['PRICE'];
				if (!isset($productRow['CURRENCY']))
					$productRow['CURRENCY'] = $currencyId;

				// measures
				$bRefreshMeasureName = false;
				if (!array_key_exists('MEASURE_CODE', $productRow) || intval($productRow['MEASURE_CODE'] <= 0))
				{
					if ($oldProductRows === null && $tmpOrderId > 0)
						$oldProductRows = CCrmInvoice::GetProductRows($tmpOrderId);
					if (is_array($oldProductRows) && count($oldProductRows) > 0 && $oldProductRowsById === null)
					{
						$oldProductRowsById = array();
						foreach ($oldProductRows as $row)
							$oldProductRowsById[intval($row['ID'])] = $row;
						unset($row);
					}
					if (is_array($oldProductRowsById) && isset($oldProductRowsById[$rowIndex]))
					{
						$row = $oldProductRowsById[$rowIndex];
						if (intval($productId) === intval($row['PRODUCT_ID']))
						{
							if (isset($row['MEASURE_CODE']))
								$productRow['MEASURE_CODE'] = $row['MEASURE_CODE'];
							if (isset($row['MEASURE_NAME']))
								$productRow['MEASURE_NAME'] = $row['MEASURE_NAME'];
							else
								$bRefreshMeasureName = true;
							unset($row);
						}
					}
				}
				if (!isset($productRow['MEASURE_CODE']) || intval($productRow['MEASURE_CODE']) <= 0)
				{
					if ($productId > 0 && isset($productMeasures[$productId]))
					{
						$measure = is_array($productMeasures[$productId][0]) ? $productMeasures[$productId][0] : null;
						if (is_array($measure))
						{
							if (isset($measure['CODE']))
								$productRow['MEASURE_CODE'] = $measure['CODE'];
							if (isset($measure['SYMBOL']))
								$productRow['MEASURE_NAME'] = $measure['SYMBOL'];
						}
						unset($measure);
					}
				}
				if (!isset($productRow['MEASURE_CODE']) || intval($productRow['MEASURE_CODE']) <= 0)
				{
					if ($defaultMeasure === null)
						$defaultMeasure = \Bitrix\Crm\Measure::getDefaultMeasure();

					if (is_array($defaultMeasure))
					{
						$productRow['MEASURE_CODE'] = $defaultMeasure['CODE'];
						$productRow['MEASURE_NAME'] = $defaultMeasure['SYMBOL'];
					}
				}
				if (isset($productRow['MEASURE_CODE'])
					&& intval($productRow['MEASURE_CODE']) > 0
					&& (
						$bRefreshMeasureName ||
						!array_key_exists('MEASURE_NAME', $productRow)
						|| empty($productRow['MEASURE_NAME'])
					)
				)
				{
					$measure = \Bitrix\Crm\Measure::getMeasureByCode($productRow['MEASURE_CODE']);
					if (is_array($measure) && isset($measure['SYMBOL']))
						$productRow['MEASURE_NAME'] = $measure['SYMBOL'];
					unset($measure);
				}

				$i++;
			}
			unset($productRow, $productMeasures, $catalogXmlId, $productXmlId);

			$arOrderProductPrice = self::__fGetUserShoppingCart($arProduct, $siteId, 'N');

			foreach ($arOrderProductPrice as &$arItem) // tmp hack not to update basket quantity data from catalog
			{
				$arItem["ID_TMP"] = $arItem["ID"];
				$arItem["NAME_TMP"] = $arItem["NAME"];
				unset($arItem["ID"]);
			}
			unset($arItem);

			// user id for order
			$saleUserId = intval(CSaleUser::GetAnonymousUserID());
			if ($saleUserId <= 0)
				return false;

			$arErrors = array();

			$arShoppingCart = CSaleBasket::DoGetUserShoppingCart($siteId, $saleUserId, $arOrderProductPrice, $arErrors, array(), $tmpOrderId);
			if (!is_array($arShoppingCart) || count($arShoppingCart) === 0)
			{
				$GLOBALS['APPLICATION']->ThrowException(GetMessage('CRM_ERROR_EMPTY_INVOICE_SPEC'));
				return false;
			}

			foreach ($arShoppingCart as $key => &$arItem)
			{
				$arItem["ID"] = $arItem["ID_TMP"];
				$arItem["NAME"] = $arItem["NAME_TMP"];
				unset($arItem["NAME_TMP"], $arItem["ID_TMP"]);

				//$arShoppingCart[$key]["ID"] = $arItem["ID"];
			}
			unset($key, $arItem);
			// </editor-fold>

			// person type
			$personTypeId = 0;
			if (!isset($arFields['PERSON_TYPE_ID']) || intval($arFields['PERSON_TYPE_ID']) <= 0)
			{
				$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
				if (isset($arPersonTypes['CONTACT']))
					$personTypeId = intval($arPersonTypes['CONTACT']);
			}
			else
				$personTypeId = $arFields['PERSON_TYPE_ID'];
			if ($personTypeId <= 0)
				return false;

			// preparing order to save
			// <editor-fold defaultstate="collapsed" desc="preparing order to save ...">
			$arOrderPropsValues = array();
			if (isset($arFields['INVOICE_PROPERTIES']) && is_array($arFields['INVOICE_PROPERTIES']) && count($arFields['INVOICE_PROPERTIES']) > 0)
				$arOrderPropsValues = $arFields['INVOICE_PROPERTIES'];
			if (isset($arFields['INVOICE_PROPERTIES']))
				unset($arFields['INVOICE_PROPERTIES']);
			if (count($arOrderPropsValues) <= 0)
				return false;
			$deliveryId = null;
			$paySystemId = $arFields['PAY_SYSTEM_ID'];
			$arOptions = array();
			$arErrors = $arWarnings = array();
			$CSaleOrder = new CSaleOrder();

			$arOrder = $CSaleOrder->DoCalculateOrder(
				$siteId, $saleUserId, $arShoppingCart, $personTypeId, $arOrderPropsValues,
				$deliveryId, $paySystemId, $arOptions, $arErrors, $arWarnings
			);
			if (count($arOrder) <= 0)
				return false;
			// </editor-fold>

			if ($bRecalculate)
			{
				foreach ($arOrder as $k => $v)
					$arRecalculated[$k] = $v;
				return true;
			}

			// merge order fields
			$arAdditionalFields = array();
			foreach ($arFields as $k => $v)
			{
				if ($k === 'PRODUCT_ROWS') continue;
				$arAdditionalFields[$k] = $v;
			}

			// saving order
			$arErrors = array();
			$orderID = $CSaleOrder->DoSaveOrder($arOrder, $arAdditionalFields, $tmpOrderId, $arErrors);

			if(is_int($orderID) && $orderID > 0 && isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true)
			{
				$prevResponsibleID = is_array($arPrevOrder) && isset($arPrevOrder['RESPONSIBLE_ID'])
						? intval($arPrevOrder['RESPONSIBLE_ID']) : 0;
				$responsibleID = isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0;

				if($tmpOrderId <= 0)
				{
					self::RegisterLiveFeedEvent($arFields, $orderID, $userId);
					if($responsibleID > 0)
					{
						CCrmSonetSubscription::RegisterSubscription(
							CCrmOwnerType::Invoice,
							$orderID,
							CCrmSonetSubscriptionType::Responsibility,
							$responsibleID
						);
					}
				}
				else
				{
					$newQuoteID = isset($arFields['UF_QUOTE_ID']) ? intval($arFields['UF_QUOTE_ID']) : 0;
					$oldQuoteID = isset($arPrevOrder['UF_QUOTE_ID']) ? intval($arPrevOrder['UF_QUOTE_ID']) : 0;

					$newDealID = isset($arFields['UF_DEAL_ID']) ? intval($arFields['UF_DEAL_ID']) : 0;
					$oldDealID = isset($arPrevOrder['UF_DEAL_ID']) ? intval($arPrevOrder['UF_DEAL_ID']) : 0;

					$newCompanyID = isset($arFields['UF_COMPANY_ID']) ? intval($arFields['UF_COMPANY_ID']) : 0;
					$oldCompanyID = isset($arPrevOrder['UF_COMPANY_ID']) ? intval($arPrevOrder['UF_COMPANY_ID']) : 0;

					$newContactID = isset($arFields['UF_CONTACT_ID']) ? intval($arFields['UF_CONTACT_ID']) : 0;
					$oldContactID = isset($arPrevOrder['UF_CONTACT_ID']) ? intval($arPrevOrder['UF_CONTACT_ID']) : 0;

					$parents = array();
					$parentsChanged = $newQuoteID !== $oldQuoteID || $newDealID !== $oldDealID
						|| $newCompanyID !== $oldCompanyID || $newContactID !== $oldContactID;
					if($parentsChanged)
					{
						if($newQuoteID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Quote,
								'ENTITY_ID' => $newQuoteID
							);
						}

						if($newDealID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
								'ENTITY_ID' => $newDealID
							);
						}

						if($newCompanyID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
								'ENTITY_ID' => $newCompanyID
							);
						}

						if($newContactID > 0)
						{
							$parents[] = array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
								'ENTITY_ID' => $newContactID
							);
						}
					}

					$oldOrderStatus = isset($arPrevOrder['STATUS_ID']) ? $arPrevOrder['STATUS_ID'] : '';
					self::SynchronizeLiveFeedEvent(
						$orderID,
						array(
							'PROCESS_PARENTS' => $parentsChanged,
							'PARENTS' => $parents,
							'REFRESH_DATE' => $orderStatus !== $oldOrderStatus,
							'START_RESPONSIBLE_ID' => $prevResponsibleID,
							'FINAL_RESPONSIBLE_ID' => $responsibleID,
							'TOPIC' => isset($arPrevOrder['ORDER_TOPIC']) ? $arPrevOrder['ORDER_TOPIC'] : $orderID
						)
					);

					if($responsibleID !== $prevResponsibleID)
					{
						CCrmSonetSubscription::ReplaceSubscriptionByEntity(
							CCrmOwnerType::Invoice,
							$orderID,
							CCrmSonetSubscriptionType::Responsibility,
							$responsibleID,
							$prevResponsibleID,
							true
						);
					}
				}
			}
		}

		if (intval($orderID) > 0 && !empty($orderStatus))
		{
			// set status
			$this->SetStatus($orderID, $orderStatus);

			// update entity permissions
			CCrmPerms::UpdateEntityAttr('INVOICE', $orderID, $arEntityAttr);

			if(isset($options['UPDATE_SEARCH']) && $options['UPDATE_SEARCH'] === true)
			{
				$arFilterTmp = Array('ID' => $orderID);
				if (!$this->bCheckPermission)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";
				CCrmSearch::UpdateSearch($arFilterTmp, 'INVOICE', true);
			}
		}

		return $orderID;
	}

	public function Update($ID, $arFields, $arOptions = array())
	{
		$arFields['ID'] = $ID;
		$recalculate = false;
		return $this->Add($arFields, $recalculate, SITE_ID, $arOptions);
	}

	public function Delete($ID)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$CSaleOrder = new CSaleOrder();
		$result = $CSaleOrder->Delete($ID);
		if($result)
		{
			CCrmProductRow::DeleteSettings('I', $ID);
			self::UnregisterLiveFeedEvent($ID);
			CCrmSonetSubscription::UnRegisterSubscriptionByEntity(CCrmOwnerType::Invoice, $ID);
			CCrmSearch::DeleteSearch('INVOICE', $ID);
		}

		return $result;
	}

	public function Recalculate($arFields)
	{
		$result = false;

		$arRecalculated = array();
		if ($this->Add($arFields, $arRecalculated))
			$result = $arRecalculated;

		return $result;
	}

	public function SetStatus($ID, $statusID, $statusParams = false, $options = array())
	{
		global $USER;

		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$currentUserId = 0;
		if(isset($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser)))
		{
			$currentUserId = $USER->GetID();
		}

		$result = true;
		if (!self::$INVOICE_STATUSES)
		{
			self::$INVOICE_STATUSES = CCrmStatus::GetStatus('INVOICE_STATUS');
		}

		if (!is_array(self::$INVOICE_STATUSES) || count(self::$INVOICE_STATUSES) <= 2 ||
			!array_key_exists('P', self::$INVOICE_STATUSES) || !array_key_exists('D', self::$INVOICE_STATUSES) ||
			self::$INVOICE_STATUSES['P']['SORT'] >= self::$INVOICE_STATUSES['D']['SORT']) $result = false;

		if ($result)
		{
			$CSaleOrder = new CSaleOrder();

			// get current state
			if (!($arOrder = CSaleOrder::GetByID($ID))) $result = false;
			if ($result)
			{
				$curPay = $arOrder['PAYED'];
				$curCancel = $arOrder['CANCELED'];
				$curMarked = $arOrder['MARKED'];
				$curStatusID = $arOrder['STATUS_ID'];


				$pay = $cancel = 'N';
				$marked = (isset($statusParams['REASON_MARKED']) || isset($statusParams['DATE_MARKED'])) ? 'Y' : 'N';
				if (self::$INVOICE_STATUSES[$statusID]['SORT'] >= self::$INVOICE_STATUSES['P']['SORT'])
				{
					$pay = 'Y';
				}
				if (self::$INVOICE_STATUSES[$statusID]['SORT'] >= self::$INVOICE_STATUSES['D']['SORT'])
				{
					$pay = 'N';
					$cancel = 'Y';
				}
				if ($curPay != $pay) $result = $CSaleOrder->PayOrder($ID, $pay, true, true, 0, array('NOT_CHANGE_STATUS' => 'Y'));
				if ($result && $curCancel != $cancel) $result = $CSaleOrder->CancelOrder($ID, $cancel);
				if ($result && $marked === 'Y')
				{
					$result = $CSaleOrder->SetMark($ID, isset($statusParams['REASON_MARKED']) ? $statusParams['REASON_MARKED'] : '', $currentUserId);
				}
				if ($result)
				{
					$arUpdate = array();
					if (isset($statusParams['DATE_MARKED']))
						$arUpdate['DATE_MARKED'] = $statusParams['DATE_MARKED'];
					if ($pay === 'Y')
					{
						if (isset($statusParams['PAY_VOUCHER_NUM']))
							$arUpdate['PAY_VOUCHER_NUM'] = $statusParams['PAY_VOUCHER_NUM'];
						if (isset($statusParams['PAY_VOUCHER_DATE']))
							$arUpdate['PAY_VOUCHER_DATE'] = $statusParams['PAY_VOUCHER_DATE'];
					}
					if (count($arUpdate) > 0)
						$result = self::Update($ID, $arUpdate);
					unset($arUpdate);
				}
				if ($result && $curStatusID != $statusID) $result = ($CSaleOrder->StatusOrder($ID, $statusID) === $ID);
			}
		}

		if($result
			&& is_array($options)
			&& isset($options['SYNCHRONIZE_LIVE_FEED'])
			&& $options['SYNCHRONIZE_LIVE_FEED'])
		{
			self::SynchronizeLiveFeedEvent(
				$ID,
				array(
					'PROCESS_PARENTS' => false,
					'REFRESH_DATE' => true
				)
			);
		}

		return $result;
	}

	public static function GetCurrencyID($siteId = SITE_ID)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		return CSaleLang::GetLangCurrency($siteId);
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

	public static function GetProductRows($ID)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$result = array();
		if ($ID > 0)
		{
			$CSaleBasket = new CSaleBasket();
			$dbRes = $CSaleBasket->GetList(
				array('ID' => 'ASC'), array('ORDER_ID' => $ID), false, false,
				array(
					'ID',
					'ORDER_ID',
					'PRODUCT_ID',
					'NAME',
					'QUANTITY',
					'PRICE',
					'CUSTOM_PRICE',
					'DISCOUNT_PRICE',
					'VAT_RATE',
					'MEASURE_CODE',
					'MEASURE_NAME',
					'MODULE',
					'CATALOG_XML_ID',
					'PRODUCT_XML_ID'
				)
			);
			while ($row = $dbRes->Fetch())
			{
				if (isset($row['NAME']))
				{
					$row['PRODUCT_NAME'] = $row['NAME'];
					unset($row['NAME']);
				}
				if (empty($row['MODULE']) && empty($row['CATALOG_XML_ID']))
				{
					$row['PRODUCT_ID'] = 0;
				}
				$result[] = $row;
			}
			unset($row);
		}

		return $result;
	}

	public static function HasProductRows($productID)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$result = false;

		$saleUserId = intval(CSaleUser::GetAnonymousUserID());
		$CSaleBasket = new CSaleBasket();
		$dbRes = $CSaleBasket->GetList(
			array(),
			array('PRODUCT_ID' => $productID,'>ORDER_ID' => 0, 'USER_ID' => $saleUserId),
			false,
			array('nTopCount' => 1),
			array('ID')
		);
		if (is_object($dbRes))
		{
			$arRes = $dbRes->Fetch();
			if (is_array($arRes) && isset($arRes['ID']) && intval($arRes['ID']) > 0)
				$result = true;
		}

		return $result;
	}

	public static function getTaxList($ID)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$arResult = array();

		$dbTaxList = CSaleOrderTax::GetList(
			array("APPLY_ORDER" => "ASC"),
			array("ORDER_ID" => $ID)
		);

		while ($arTaxList = $dbTaxList->Fetch())
		{
			$arResult[] = array(
				'IS_IN_PRICE' => $arTaxList['IS_IN_PRICE'],
				'TAX_NAME' => $arTaxList['TAX_NAME'],
				'IS_PERCENT' => $arTaxList['IS_PERCENT'],
				'VALUE' => $arTaxList['VALUE'],
				'VALUE_MONEY' => $arTaxList['VALUE_MONEY']
			);
		}

		return $arResult;
	}

	private static function _getAllowedPropertiesInfo()
	{
		if (self::$arinvoicePropertiesAllowed !== null)
			return self::$arinvoicePropertiesAllowed;

		$personTypeCompany = $personTypeContact = null;
		$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
		if ($arPersonTypes['COMPANY'] != "" && $arPersonTypes['CONTACT'] != "")
		{
			$personTypeCompany = $arPersonTypes['COMPANY'];
			$personTypeContact = $arPersonTypes['CONTACT'];
		}
		else
			return array();

		self::$arinvoicePropertiesAllowed = array(
			$personTypeCompany => array(
				'COMPANY' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_TITLE'),
				'COMPANY_ADR' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_ADR'),
				'CONTACT_PERSON' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_CONTACT_PERSON'),
				'EMAIL' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_EMAIL'),
				'PHONE' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_PHONE'),
				'INN' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_INN'),
				'KPP' => GetMessage('CRM_INVOICE_PROPERTY_COMPANY_KPP')
			),
			$personTypeContact => array(
				'FIO' => GetMessage('CRM_INVOICE_PROPERTY_CONTACT_FIO'),
				'ADDRESS' => GetMessage('CRM_INVOICE_PROPERTY_CONTACT_ADDRESS'),
				'EMAIL' => GetMessage('CRM_INVOICE_PROPERTY_CONTACT_EMAIL'),
				'PHONE' => GetMessage('CRM_INVOICE_PROPERTY_CONTACT_PHONE')
			)
		);

		return self::$arinvoicePropertiesAllowed;
	}

	public static function GetPropertiesInfo($personTypeId = 0, $onlyEditable = false)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$result = false;

		$bTaxMode = CCrmTax::isTaxMode();

		$personTypeId = intval($personTypeId);

		$allowedProperties = self::_getAllowedPropertiesInfo();
		$arFilter = array("ACTIVE" => "Y");
		if ($personTypeId > 0) $arFilter["PERSON_TYPE_ID"] = $personTypeId;
		$dbProperties = CSaleOrderProps::GetList(
			array("GROUP_SORT" => "ASC", "PROPS_GROUP_ID" => "ASC", "SORT" => "ASC", "NAME" => "ASC"),
			$arFilter,
			false,
			false,
			array("*")
		);

		$arResult = array();
		while ($arProperty = $dbProperties->Fetch())
		{
			if (array_key_exists($arProperty["CODE"], $allowedProperties[$arProperty["PERSON_TYPE_ID"]]))
			{
				$arProperty["NAME"] = $allowedProperties[$arProperty["PERSON_TYPE_ID"]][$arProperty["CODE"]];
				if ($onlyEditable)
					$arResult[$arProperty["PERSON_TYPE_ID"]][$arProperty["CODE"]] = $arProperty;
			}
			if (!$onlyEditable)
				$arResult[$arProperty["PERSON_TYPE_ID"]][$arProperty["CODE"]] = $arProperty;
		}

		if (count($arResult) > 0)
			$result = $arResult;

		return $result;
	}

	public static function GetProperties($ID, $personTypeId)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$result = false;

		$bTaxMode = CCrmTax::isTaxMode();

		$ID = intval($ID);
		$personTypeId = intval($personTypeId);
		// if ($ID <= 0 || $personTypeId <= 0) return false;

		$locationId = null;
		
		$arPropValues = array();

		if ($ID > 0)
		{
			$dbPropValuesList = CSaleOrderPropsValue::GetList(
				array("SORT" => "ASC"),
				array("ORDER_ID" => $ID, "ACTIVE" => "Y"),
				false,
				false,
				array("ID", "ORDER_PROPS_ID", "NAME", "VALUE", "CODE")
			);
			while ($arPropValuesList = $dbPropValuesList->Fetch())
			{
				$arPropValues[intval($arPropValuesList["ORDER_PROPS_ID"])] = $arPropValuesList["VALUE"];
			}
		}

		$arFilter = array("ACTIVE" => "Y");
		if ($personTypeId > 0) $arFilter["PERSON_TYPE_ID"] = $personTypeId;
		$dbProperties = CSaleOrderProps::GetList(
			array("GROUP_SORT" => "ASC", "PROPS_GROUP_ID" => "ASC", "SORT" => "ASC", "NAME" => "ASC"),
			$arFilter,
			false,
			false,
			array("*")
		);
		$propertyGroupId = -1;

		$arResult = array();
		while ($arProperties = $dbProperties->Fetch())
		{
			if (intval($arProperties["PROPS_GROUP_ID"]) != $propertyGroupId)
				$propertyGroupId = intval($arProperties["PROPS_GROUP_ID"]);

			$curVal = $arPropValues[intval($arProperties["ID"])];

			if ($arProperties["CODE"] == "LOCATION" && $bTaxMode)    // required field
			{
				$arResult['PR_LOCATION'] = array(
					'FIELDS' => $arProperties,
					'VALUE' => $curVal
				);
			}

			$arResult['PR_INVOICE_'.$arProperties['ID']] = array(
				'FIELDS' => $arProperties,
				'VALUE' => $curVal
			);
		}
		
		if (count($arResult) > 0)
			$result = $arResult;

		return $result;
	}

	public static function ParsePropertiesValuesFromPost($personTypeId, $post, &$arInvoiceProps)
	{
		if(!is_array($arInvoiceProps) || count($arInvoiceProps) <= 0)
		{
			return false;
		}
		
		$result = false;

		$bTaxMode = CCrmTax::isTaxMode();

		$arPropsValues = array();
		$arPropsIndexes = array();
		$error = 0;
		foreach ($arInvoiceProps as $propertyKey => $property)
		{
			if ((!isset($property['VALUE']) && $property['VALUE'] !== null) ||
				!isset($property['FIELDS']) || !is_array($property['FIELDS']) ||
				count($property['FIELDS']) <= 0)
			{
				$error = 1;
				break;
			}
			$arPropertyFields = &$property['FIELDS'];

			if ($arPropertyFields["CODE"] === "LOCATION" && isset($post['LOC_CITY']) && $bTaxMode)
			{
				// location
				$locationId = intval($post['LOC_CITY']);
				if ($locationId > 0)
					$arInvoiceProps['PR_LOCATION']['VALUE'] = $locationId;
				elseif (isset($arInvoiceProps['PR_LOCATION']))
					$locationId = $arInvoiceProps['PR_LOCATION']['VALUE'];
				if ($locationId > 0 && ($personTypeId === 0 || $arPropertyFields["PERSON_TYPE_ID"] == $personTypeId))
				{
					$arPropsValues[$arPropertyFields["ID"]] = $locationId;
					$arPropsIndexes['PR_LOCATION'] = $arPropertyFields["ID"];
					//rewrite invoice property
					$arInvoiceProps['PR_LOCATION']['VALUE'] = $locationId;
				}
				unset($locationId);
			}

			if(!is_array(${"PR_INVOICE_".$arPropertyFields["ID"]}))
				$curVal = trim($post["PR_INVOICE_".$arPropertyFields["ID"]]);
			else
				$curVal = trim($post["PR_INVOICE_".$arPropertyFields["ID"]]);
			if ($arPropertyFields["TYPE"] == "MULTISELECT")
			{
				$curVal = "";
				$countOrderProp = count($post["PR_INVOICE_".$arPropertyFields["ID"]]);
				for ($i = 0; $i < $countOrderProp; $i++)
				{
					if ($i > 0)
						$curVal .= ",";

					$curVal .= $post["PR_INVOICE_".$arPropertyFields["ID"]][$i];
				}
			}
			if ($arPropertyFields["TYPE"] == "CHECKBOX" && strlen($curVal) <= 0 && $arPropertyFields["REQUIED"] != "Y")
			{
				$curVal = "N";
			}

			if (!isset($arPropsValues[$arPropertyFields["ID"]]) && ($personTypeId === 0 || $arPropertyFields["PERSON_TYPE_ID"] == $personTypeId))
			{
				$arPropsValues[$arPropertyFields["ID"]] = $curVal;
				//rewrite invoice property
				$arInvoiceProps['PR_INVOICE_'.$arPropertyFields["ID"]]['VALUE'] = $curVal;
			}
			if (!isset($arPropsIndexes['PR_INVOICE_'.$arPropertyFields["ID"]]))
				$arPropsIndexes['PR_INVOICE_'.$arPropertyFields["ID"]] = $arPropertyFields["ID"];
		}

		if ($error > 0) return false;

		if (count($arPropsValues) > 0)
			$result = array(
				'PROPS_VALUES' => $arPropsValues,
				'PROPS_INDEXES' => $arPropsIndexes
			);

		return $result;
	}

	public static function __MakePropsHtmlInputs($arInvoiceProperties)
	{
		$htmlInputs = '';
		foreach ($arInvoiceProperties as $propertyKey => $property)
			$htmlInputs .= '<input type="hidden" name="'.htmlspecialcharsbx($propertyKey).'" value="'.htmlspecialcharsbx($arInvoiceProperties[$propertyKey]['VALUE']).'"/>'.PHP_EOL;

		return $htmlInputs;
	}

	public static function __RewritePayerInfo($companyId, $contactId, &$arInvoiceProperties)
	{
		$arCompany = $companyEMail = $companyPhone = null;
		$arContact = $contactEMail = $contactPhone = null;

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
				foreach ($arInvoiceProperties as $propertyKey => $property)
				{
					$curVal = $arInvoiceProperties[$propertyKey]['VALUE'];
					if ($property['FIELDS']['CODE'] === 'COMPANY')
					{
						if (isset($arCompany['TITLE']))
							$curVal = $arCompany['TITLE'];
					}
					elseif ($property['FIELDS']['CODE'] === 'CONTACT_PERSON' && $contactId > 0)
					{
						if (isset($arContact['FULL_NAME']))
							$curVal = $arContact['FULL_NAME'];
					}
					elseif ($property['FIELDS']['CODE'] === 'COMPANY_ADR')
					{
						if (isset($arCompany['ADDRESS_LEGAL']))
							$curVal = $arCompany['ADDRESS_LEGAL'];
					}
					elseif ($property['FIELDS']['CODE'] === 'INN')
					{
						$todo = 'todo'; // TODO:
					}
					elseif ($property['FIELDS']['CODE'] === 'KPP')
					{
						$todo = 'todo'; // TODO:
					}
					elseif ($property['FIELDS']['CODE'] === 'EMAIL')
					{
						$curVal = ($contactEMail != '') ? $contactEMail : $companyEMail;
					}
					elseif ($property['FIELDS']['CODE'] === 'PHONE')
					{
						$curVal = ($contactPhone != '') ? $contactPhone : $companyPhone;
					}

					$arInvoiceProperties[$propertyKey]['VALUE'] = $curVal;
				}
			}
		}
		elseif ($contactId > 0)
		{
			if (is_array($arContact) && count($arContact) >0)
			{
				foreach ($arInvoiceProperties as $propertyKey => $property)
				{
					$curVal = $arInvoiceProperties[$propertyKey]['VALUE'];
					if ($property['FIELDS']['CODE'] === 'FIO')
					{
						if (isset($arContact['FULL_NAME']))
							$curVal = $arContact['FULL_NAME'];
					}
					elseif ($property['FIELDS']['CODE'] === 'EMAIL')
					{
						$curVal = $contactEMail;
					}
					elseif ($property['FIELDS']['CODE'] === 'PHONE')
					{
						$curVal = $contactPhone;
					}
					elseif ($property['FIELDS']['CODE'] === 'ADDRESS')
					{
						if (isset($arContact['ADDRESS']))
							$curVal = $arContact['ADDRESS'];
					}

					$arInvoiceProperties[$propertyKey]['VALUE'] = $curVal;
				}
			}
		}
	}

	public static function __MakePayerInfoString($arInvoiceProperties)
	{
		$strPayerInfo = '';

		if(!self::$INVOICE_PROPERTY_INFOS)
		{
			self::$INVOICE_PROPERTY_INFOS = CCrmInvoice::GetPropertiesInfo(0, true);
		}

		$i = 0;
		foreach (self::$INVOICE_PROPERTY_INFOS as $person => $props)
		{
			$index = 0;
			foreach ($props as $code => $fields)
			{
				if ($fields['TYPE'] === 'TEXT' || $fields['TYPE'] === 'TEXTAREA')
				{
					$value = trim($arInvoiceProperties['PR_INVOICE_'.$fields['ID']]['VALUE']);
					if ($value != '')
					{
						if ($i > 0)
							$strPayerInfo .= ', ';
						$strPayerInfo .= $value;
						$i++;
					}
				}
			}
		}

		return $strPayerInfo;
	}

	public static function __GetCompanyAndContactFromPost(&$post)
	{
		$result = array(
			'COMPANY' => 0,
			'CONTACT' => 0
		);

		if (substr($post['CLIENT_ID'], 0, 2) === 'C_')
		{
			$result['CONTACT'] = intval(substr($post['CLIENT_ID'], 2));
		}
		else if (substr($post['CLIENT_ID'], 0, 3) === 'CO_')
		{
			$result['COMPANY'] = intval(substr($post['CLIENT_ID'], 3));
			$result['CONTACT'] = intval($post['UF_CONTACT_ID']);
		}

		return $result;
	}

	/**
	* <p>
	* CREATE SALE AND CATALOG MODULES ENTITIES FOR INVOICES IN CRM VERSION 12.5.7
	* <br>UPDATE ORDER OPTION IN CRM VERSION 12.5.14
	* <br>CREATE 1C EXCHANGE OPTIONS DEFAULTS AND DEFAULT INVOICE EXPORT PROFILES IN CRM VERSION 12.5.17
	* <br>...
	* </p>
	*/
	public static function installExternalEntities()
	{
		global $DB, $DBType;
		$errMsg = array();

		// at first, check last update version
		if (COption::GetOptionString('crm', '~CRM_INVOICE_UF_QUOTE_ID_14_1_13', 'N') === 'Y')
			return true;

		if (COption::GetOptionString('crm', '~CRM_EXCH1C_BASKET_XML_IDS_14_1_9', 'N') === 'Y')
		{
			$bFieldExists = false;
			$obUserField  = new CUserTypeEntity;
			$dbRes = $obUserField->GetList(array('SORT' => 'DESC'), array('ENTITY_ID' => 'ORDER'));
			$maxUFSort = 0;
			$i = 0;
			while ($arUF = $dbRes->Fetch())
			{
				if ($i++ === 0)
					$maxUFSort = intval($arUF['SORT']);
				if ($arUF['FIELD_NAME'] === 'UF_QUOTE_ID')
				{
					$bFieldExists = true;
					break;
				}
			}
			unset($dbRes, $arUF, $i);
			if (!$bFieldExists)
			{
				$arOrderUserField = array(
					'ENTITY_ID' => 'ORDER',
					'FIELD_NAME' => 'UF_QUOTE_ID',
					'USER_TYPE_ID' => 'integer',
					'XML_ID' => 'uf_quote_id',
					'SORT' => strval($maxUFSort + 10),
					'MULTIPLE' => null,
					'MANDATORY' => null,
					'SHOW_FILTER' => 'N',
					'SHOW_IN_LIST' => 'N',
					'EDIT_IN_LIST' => 'N',
					'IS_SEARCHABLE' => null,
					'SETTINGS' => array(
						'DEFAULT_VALUE' => null,
						'SIZE' => '',
						'ROWS' => '1',
						'MIN_LENGTH' => '0',
						'MAX_LENGTH' => '0',
						'REGEXP' => ''
					),
					'EDIT_FORM_LABEL' => array('ru' => '', 'en' => ''),
					'LIST_COLUMN_LABEL' => array('ru' => '', 'en' => ''),
					'LIST_FILTER_LABEL' => array('ru' => '', 'en' => ''),
					'ERROR_MESSAGE' => array('ru' => '', 'en' => ''),
					'HELP_MESSAGE' => array('ru' => '', 'en' => '')
				);
				$userFieldId = $obUserField->Add($arOrderUserField);
				if ($userFieldId <= 0)
					$errMsg[] = str_replace("#FIELD_NAME#", $arOrderUserField['FIELD_NAME'], GetMessage('CRM_CANT_ADD_USER_FIELD'));
				unset($userFieldId);
			}
			if (empty($errMsg))
			{
				COption::SetOptionString('crm', '~CRM_INVOICE_UF_QUOTE_ID_14_1_13', 'Y');
				return true;
			}
			else
			{
				$errString = implode('<br>', $errMsg);
				ShowError($errString);
				return false;
			}
		}

		if (COption::GetOptionString('crm', '~CRM_EXCH1C_REWRITEDEFCATGRP_12_5_20', 'N') === 'Y')
		{
			// update basket xml_id fields
			if($DB->TableExists('b_sale_order')
				&& $DB->TableExists('b_sale_basket')
				&& $DB->TableExists('b_iblock')
				&& $DB->TableExists('b_iblock_element'))
			{
				if($DB->Query("SELECT RESPONSIBLE_ID FROM b_sale_order WHERE 1=0", true)
					&& $DB->Query("SELECT CATALOG_XML_ID, PRODUCT_XML_ID FROM b_sale_basket WHERE 1=0", true)
					&& $DB->Query("SELECT XML_ID FROM b_iblock WHERE 1=0", true)
					&& $DB->Query("SELECT XML_ID FROM b_iblock_element WHERE 1=0", true))
				{
					$catalogId = 0;
					$tmpCatalogId = intval(COption::GetOptionString('crm', 'default_product_catalog_id', '0'));
					if ($dbRes = $DB->Query("SELECT ID FROM b_iblock I WHERE I.ID = $tmpCatalogId", true))
					{
						if ($arRes = $dbRes->Fetch())
						{
							if ($tmpCatalogId === intval($arRes['ID']))
								$catalogId = $tmpCatalogId;
						}
						unset($arRes);
					}
					unset($tmpCatalogId, $dbRes);
					if ($catalogId > 0)
					{
						$databaseType = strtoupper($DBType);
						$strSql = '';
						switch ($databaseType)
						{
							case 'MYSQL';
								$strSql =
									"UPDATE b_sale_basket B".PHP_EOL.
									"  INNER JOIN b_sale_order O ON B.ORDER_ID = O.ID".PHP_EOL.
									"  INNER JOIN b_iblock_element IE ON B.PRODUCT_ID = IE.ID".PHP_EOL.
									"  INNER JOIN b_iblock I ON IE.IBLOCK_ID = I.ID".PHP_EOL.
									"SET".PHP_EOL.
									"  B.CATALOG_XML_ID = I.XML_ID,".PHP_EOL.
									"  B.PRODUCT_XML_ID = IE.XML_ID".PHP_EOL.
									"WHERE".PHP_EOL.
									"  IE.IBLOCK_ID = $catalogId".PHP_EOL.
									"  AND (".PHP_EOL.
									"    B.PRODUCT_XML_ID IS NULL OR B.PRODUCT_XML_ID = ''".PHP_EOL.
									"    OR B.CATALOG_XML_ID IS NULL OR B.CATALOG_XML_ID = ''".PHP_EOL.
									"  )".PHP_EOL.
									"  AND O.RESPONSIBLE_ID IS NOT NULL";
								break;
							case 'MSSQL';
								$strSql =
									"UPDATE B".PHP_EOL.
									"SET".PHP_EOL.
									"  B.CATALOG_XML_ID = I.XML_ID,".PHP_EOL.
									"  B.PRODUCT_XML_ID = IE.XML_ID".PHP_EOL.
									"FROM B_SALE_BASKET B".PHP_EOL.
									"  INNER JOIN B_SALE_ORDER O ON B.ORDER_ID = O.ID".PHP_EOL.
									"  INNER JOIN B_IBLOCK_ELEMENT IE ON B.PRODUCT_ID = IE.ID".PHP_EOL.
									"  INNER JOIN B_IBLOCK I ON IE.IBLOCK_ID = I.ID".PHP_EOL.
									"WHERE".PHP_EOL.
									"  IE.IBLOCK_ID = $catalogId".PHP_EOL.
									"  AND (".PHP_EOL.
									"    B.PRODUCT_XML_ID IS NULL OR B.PRODUCT_XML_ID = ''".PHP_EOL.
									"    OR B.CATALOG_XML_ID IS NULL OR B.CATALOG_XML_ID = ''".PHP_EOL.
									"  )".PHP_EOL.
									"  AND O.RESPONSIBLE_ID IS NOT NULL";
								break;
							case 'ORACLE';
								$strSql =
									"UPDATE (".PHP_EOL.
									"  SELECT".PHP_EOL.
									"    B.ID,".PHP_EOL.
									"    B.CATALOG_XML_ID,".PHP_EOL.
									"    B.PRODUCT_XML_ID,".PHP_EOL.
									"    I.XML_ID AS C_XML_ID,".PHP_EOL.
									"    IE.XML_ID AS P_XML_ID".PHP_EOL.
									"  FROM B_SALE_BASKET B".PHP_EOL.
									"    INNER JOIN B_SALE_ORDER O ON B.ORDER_ID = O.ID".PHP_EOL.
									"    INNER JOIN B_IBLOCK_ELEMENT IE ON B.PRODUCT_ID = IE.ID".PHP_EOL.
									"    INNER JOIN B_IBLOCK I ON IE.IBLOCK_ID = I.ID".PHP_EOL.
									"  WHERE".PHP_EOL.
									"    IE.IBLOCK_ID = $catalogId".PHP_EOL.
									"    AND (".PHP_EOL.
									"      B.PRODUCT_XML_ID IS NULL OR B.PRODUCT_XML_ID = ''".PHP_EOL.
									"      OR B.CATALOG_XML_ID IS NULL OR B.CATALOG_XML_ID = ''".PHP_EOL.
									"    )".PHP_EOL.
									"    AND O.RESPONSIBLE_ID IS NOT NULL".PHP_EOL.
									") U".PHP_EOL.
									"SET".PHP_EOL.
									"  U.CATALOG_XML_ID = U.C_XML_ID,".PHP_EOL.
									"  U.PRODUCT_XML_ID = U.P_XML_ID";
								break;
						}
						unset($databaseType);
						$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						unset($strSql);
					}
				}
			}
			COption::SetOptionString('crm', '~CRM_EXCH1C_BASKET_XML_IDS_14_1_9', 'Y');

			$bFieldExists = false;
			$obUserField  = new CUserTypeEntity;
			$dbRes = $obUserField->GetList(array('SORT' => 'DESC'), array('ENTITY_ID' => 'ORDER'));
			$maxUFSort = 0;
			$i = 0;
			while ($arUF = $dbRes->Fetch())
			{
				if ($i++ === 0)
					$maxUFSort = intval($arUF['SORT']);
				if ($arUF['FIELD_NAME'] === 'UF_QUOTE_ID')
				{
					$bFieldExists = true;
					break;
				}
			}
			unset($dbRes, $arUF, $i);
			if (!$bFieldExists)
			{
				$arOrderUserField = array(
					'ENTITY_ID' => 'ORDER',
					'FIELD_NAME' => 'UF_QUOTE_ID',
					'USER_TYPE_ID' => 'integer',
					'XML_ID' => 'uf_quote_id',
					'SORT' => strval($maxUFSort + 10),
					'MULTIPLE' => null,
					'MANDATORY' => null,
					'SHOW_FILTER' => 'N',
					'SHOW_IN_LIST' => 'N',
					'EDIT_IN_LIST' => 'N',
					'IS_SEARCHABLE' => null,
					'SETTINGS' => array(
						'DEFAULT_VALUE' => null,
						'SIZE' => '',
						'ROWS' => '1',
						'MIN_LENGTH' => '0',
						'MAX_LENGTH' => '0',
						'REGEXP' => ''
					),
					'EDIT_FORM_LABEL' => array('ru' => '', 'en' => ''),
					'LIST_COLUMN_LABEL' => array('ru' => '', 'en' => ''),
					'LIST_FILTER_LABEL' => array('ru' => '', 'en' => ''),
					'ERROR_MESSAGE' => array('ru' => '', 'en' => ''),
					'HELP_MESSAGE' => array('ru' => '', 'en' => '')
				);
				unset($maxUFSort);
				$userFieldId = $obUserField->Add($arOrderUserField);
				if ($userFieldId <= 0)
					$errMsg[] = str_replace("#FIELD_NAME#", $arOrderUserField['FIELD_NAME'], GetMessage('CRM_CANT_ADD_USER_FIELD'));
				unset($userFieldId, $obUserField, $arOrderUserField);
			}
			if (empty($errMsg))
			{
				COption::SetOptionString('crm', '~CRM_INVOICE_UF_QUOTE_ID_14_1_13', 'Y');
				return true;
			}
			else
			{
				$errString = implode('<br>', $errMsg);
				ShowError($errString);
				return false;
			}
		}

		if (COption::GetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_12_5_19', 'N') === 'Y')
		{
			if (!CModule::IncludeModule('catalog'))
				return false;
			$arBaseCatalogGroup = CCatalogGroup::GetBaseGroup();
			$priceTypeId = intval($arBaseCatalogGroup['ID']);
			COption::SetOptionInt('crm', 'selected_catalog_group_id', $priceTypeId);
			unset($arBaseCatalogGroup, $priceTypeId);
			COption::SetOptionString('crm', '~CRM_EXCH1C_REWRITEDEFCATGRP_12_5_20', 'Y');
			LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
			return true;
		}

		if (COption::GetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_12_5_17', 'N') === 'Y')
		{
			$pref = COption::GetOptionString('sale', '1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX', '');
			if (strlen(strval($pref)) < 1)
				COption::SetOptionString('sale', '1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX', 'CRM_');
			COption::SetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_12_5_19', 'Y');
			self::installDisableSaleEvents();
			COption::SetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_12_5_19', 'Y');
			if (!CModule::IncludeModule('catalog'))
				return false;
			$arBaseCatalogGroup = CCatalogGroup::GetBaseGroup();
			$priceTypeId = intval($arBaseCatalogGroup['ID']);
			COption::SetOptionInt('crm', 'selected_catalog_group_id', $priceTypeId);
			unset($arBaseCatalogGroup, $priceTypeId);
			COption::SetOptionString('crm', '~CRM_EXCH1C_REWRITEDEFCATGRP_12_5_20', 'Y');

			LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
			return true;
		}

		if (COption::GetOptionString('crm', '~CRM_INVOICE_INSTALL_12_5_7', 'N') === 'Y')
		{
			// fix 40279
			if (COption::GetOptionString('crm', '~CRM_INVOICE_UPDATE_12_5_14', 'N') !== 'Y')
			{
				try
				{
					if (CModule::IncludeModule('sale'))
					{
						global $DB;

						if ($DB->TableExists('b_sale_order_props') && class_exists('CSaleOrderProps'))
						{
							$arPropsFilter = array(
								'TYPE' => 'LOCATION',
								'REQUIED' => 'Y',
								'USER_PROPS' => 'Y',
								'IS_LOCATION' => 'Y',
								'IS_EMAIL' => 'N',
								'IS_PROFILE_NAME' => 'N',
								'IS_PAYER' => 'N',
								'CODE' => 'LOCATION'
							);

							// update properties
							$dbOrderProps = CSaleOrderProps::GetList(
								array('SORT' => 'ASC', 'ID' => 'ASC'),
								$arPropsFilter,
								false,
								false,
								array('ID', 'IS_LOCATION4TAX')
							);
							if ($dbOrderProps !== false)
							{
								while ($arOrderProp = $dbOrderProps->Fetch())
								{
									if ($arOrderProp['IS_LOCATION4TAX'] !== 'Y')
									{
										CSaleOrderProps::Update($arOrderProp['ID'], array('IS_LOCATION4TAX' => 'Y'));
									}
								}
								COption::SetOptionString('crm', '~CRM_INVOICE_UPDATE_12_5_14', 'Y');
							}
						}
					}
				}
				catch(Exception $e)
				{}
			}
			if (COption::GetOptionString('crm', '~CRM_INVOICE_UPDATE_12_5_14', 'N') === 'Y')
			{
				if (COption::GetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_12_5_17', 'N') !== 'Y')
				{
					if (CModule::IncludeModule('catalog') && CModule::IncludeModule('sale') && CModule::IncludeModule('iblock'))
					{
						try
						{
							require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/install/exch1c.php");
						}
						catch(Exception $e)
						{
							$errMsg[] = $e->getMessage();
						}

						if (empty($errMsg))
						{
							COption::SetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_12_5_17', 'Y');
							COption::SetOptionString('sale', '1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX', 'CRM_');
							COption::SetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_12_5_19', 'Y');
							self::installDisableSaleEvents();
							COption::SetOptionString('crm', '~CRM_INVOICE_DISABLE_SALE_EVENTS_12_5_19', 'Y');
							if (!CModule::IncludeModule('catalog'))
								return false;
							$arBaseCatalogGroup = CCatalogGroup::GetBaseGroup();
							$priceTypeId = intval($arBaseCatalogGroup['ID']);
							COption::SetOptionInt('crm', 'selected_catalog_group_id', $priceTypeId);
							unset($arBaseCatalogGroup, $priceTypeId);
							COption::SetOptionString('crm', '~CRM_EXCH1C_REWRITEDEFCATGRP_12_5_20', 'Y');
							LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
							return true;
						}
						else
						{
							$errString = implode('<br>', $errMsg);
							ShowError($errString);
							return false;
						}
					}
				}
				else
					return true;
			}
			return false;
		}

		try
		{
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/install/sale_link.php");
		}
		catch(Exception $e)
		{
			$errMsg[] = $e->getMessage();
		}

		if (empty($errMsg))
		{
			COption::SetOptionString('crm', '~CRM_INVOICE_INSTALL_12_5_7', 'Y');
			LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam());
			return true;
		}
		else
		{
			$errString = implode('<br>', $errMsg);
			ShowError($errString);
			return false;
		}
	}

	public static function installDisableSaleEvents()
	{
		$fmodule = new CModule();
		if($module = $fmodule->CreateModuleObject("sale"))
			$module->UnInstallEvents();
	}

	public static function GetCounterValue()
	{
		$result = 0;

		global $USER;
		$userId = is_object($USER) ? intval($USER->GetID()) : 0;
		if ($userId > 0)
		{
			$arNeutralStatuses = self::GetNeutralStatusIds();
			if (!is_array($arNeutralStatuses) || count($arNeutralStatuses) === 0)
				return $result;

			$filter = array(
				"RESPONSIBLE_ID" => $userId,
				"<=DATE_PAY_BEFORE" => FormatDate('FULL', strtotime(date('Y-m-d').' 23:59:59') + CTimeZone::GetOffset()),
				"STATUS_ID" => $arNeutralStatuses
			);
			if ($dbRes = CCrmInvoice::GetList(array(), $filter, false, false, array("ID", "STATUS_ID", "DATE_PAY_BEFORE")))
			{
				$cnt = 0;
				while ($arResult = $dbRes->Fetch())
				//{
					//if (isset($arResult['STATUS_ID']) && CCrmStatusInvoice::isStatusNeutral($arResult['STATUS_ID']))
						$cnt++;
				//}
				$result = $cnt;
			}
		}

		return $result;
	}

	public static function GetPaidSum($filter, $currencyId = '')
	{
		$totalPaidNumber = 0;
		$totalPaidSum = 0;

		if ($currencyId == '')
			$currencyId = CCrmCurrency::GetBaseCurrencyID();

		$dbRes = CCrmInvoice::GetList(array('ID' => 'ASC'), $filter, false, false, array('PRICE', 'CURRENCY', 'STATUS_ID'));
		while ($arValues = $dbRes->Fetch())
		{
			if (CCrmStatusInvoice::isStatusSuccess($arValues['STATUS_ID']))
			{
				$totalPaidNumber++;
				$totalPaidSum += CCrmCurrency::ConvertMoney($arValues['PRICE'], $arValues['CURRENCY'], $currencyId);
			}
		}

		$result = array(
			'num' => $totalPaidNumber,
			'sum' => round($totalPaidSum, 2)
		);

		return $result;
	}

	public static function ResolvePersonTypeID($companyID, $contactID)
	{
		$companyID = intval($companyID);
		$contactID = intval($contactID);

		if(!self::$INVOICE_PAY_SYSTEM_TYPES)
		{
			self::$INVOICE_PAY_SYSTEM_TYPES = CCrmPaySystem::getPersonTypeIDs();
		}

		if($companyID > 0 && isset(self::$INVOICE_PAY_SYSTEM_TYPES['COMPANY']))
		{
			return self::$INVOICE_PAY_SYSTEM_TYPES['COMPANY'];
		}
		elseif($contactID > 0 && isset(self::$INVOICE_PAY_SYSTEM_TYPES['CONTACT']))
		{
			return self::$INVOICE_PAY_SYSTEM_TYPES['CONTACT'];
		}
		return 0;
	}

	public static function ResolveLocationName($ID, $fields = null)
	{
		if(!(is_array($fields) && !empty($fields)))
		{
			$ID = intval($ID);
			if($ID <= 0)
			{
				return '';
			}

			if(!CModule::IncludeModule('sale'))
			{
				return $ID;
			}
			$dbLocations = CSaleLocation::GetList(
				array(),
				array('ID' => $ID, 'LID' => LANGUAGE_ID),
				false,
				false,
				array('ID', 'CITY_ID', 'CITY_NAME', 'COUNTRY_NAME_LANG', 'REGION_NAME_LANG')
			);

			$fields = $dbLocations->Fetch();
			if(!is_array($fields))
			{
				return $ID;
			}
		}

		$name = isset($fields['CITY_NAME']) ? $fields['CITY_NAME'] : '';
		if(isset($fields['REGION_NAME_LANG']))
		{
			if($name !== '')
			{
				$name .= ', ';
			}
			$name .= $fields['REGION_NAME_LANG'];
		}
		if(isset($fields['COUNTRY_NAME_LANG']))
		{
			if($name !== '')
			{
				$name .= ', ';
			}
			$name .= $fields['COUNTRY_NAME_LANG'];
		}

		return $name;
	}

	private static function OnCreate()
	{
	}
	private static function RegisterLiveFeedEvent(&$arFields, $invoiceID, $userID)
	{
		$invoiceID = intval($invoiceID);
		if($invoiceID <= 0)
		{
			$arFields['ERROR'] = 'Could not find invoice invoice ID.';
			return false;
		}

		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		// Params are not assigned - we will use current invoice only.
		$liveFeeedFields = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Invoice,
			'ENTITY_ID' => $invoiceID,
			'USER_ID' => $userID,
			'MESSAGE' => '',
			'TITLE' => ''
			//'PARAMS' => array()
		);

		$quoteID = isset($arFields['UF_QUOTE_ID']) ? intval($arFields['UF_QUOTE_ID']) : 0;
		$dealID = isset($arFields['UF_DEAL_ID']) ? intval($arFields['UF_DEAL_ID']) : 0;
		$companyID = isset($arFields['UF_COMPANY_ID']) ? intval($arFields['UF_COMPANY_ID']) : 0;
		$contactID = isset($arFields['UF_CONTACT_ID']) ? intval($arFields['UF_CONTACT_ID']) : 0;
		$responsibleID = isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0;

		$liveFeeedFields['PARENTS'] = array();
		if($quoteID > 0)
		{
			$liveFeeedFields['PARENTS'][] = array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Quote,
				'ENTITY_ID' => $quoteID
			);
		}

		if($dealID > 0)
		{
			$liveFeeedFields['PARENTS'][] = array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
				'ENTITY_ID' => $dealID
			);
		}

		if($companyID > 0)
		{
			$liveFeeedFields['PARENTS'][] = array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
				'ENTITY_ID' => $companyID
			);
		}

		if($contactID > 0)
		{
			$liveFeeedFields['PARENTS'][] = array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
				'ENTITY_ID' => $contactID
			);
		}

		$eventID = CCrmLiveFeed::CreateLogEvent($liveFeeedFields, CCrmLiveFeedEvent::Add);
		if(!(is_int($eventID) && $eventID > 0))
		{
			if(isset($liveFeeedFields['ERROR']))
			{
				$arFields['ERROR'] = $liveFeeedFields['ERROR'];
			}
		}
		elseif($responsibleID > 0 && $responsibleID !== $userID
			&& IsModuleInstalled('im') && CModule::IncludeModule('im'))
		{
			$eventUrl = CCrmLiveFeed::GetShowUrl($eventID);
			$topic = isset($arFields['ORDER_TOPIC']) ? $arFields['ORDER_TOPIC'] : $invoiceID;

			CIMNotify::Add(
				array(
					'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
					'FROM_USER_ID' => $userID,
					'NOTIFY_TYPE' => IM_NOTIFY_FROM,
					'NOTIFY_MODULE' => 'crm',
					'LOG_ID' => $eventID,
					'NOTIFY_EVENT' => 'invoice_responsible_changed',
					'NOTIFY_TAG' => "CRM|INVOICE|{$invoiceID}",
					'TO_USER_ID' => $responsibleID,
					'NOTIFY_MESSAGE' => GetMessage('CRM_INVOICE_RESPONSIBLE_IM_NOTIFY', array('#title#' => '<a href="'.htmlspecialcharsbx($eventUrl).'">'.htmlspecialcharsbx($topic).'</a>')),
					'NOTIFY_MESSAGE_OUT' => GetMessage('CRM_INVOICE_RESPONSIBLE_IM_NOTIFY', array('#title#' => htmlspecialcharsbx($topic)))." (".CCrmUrlUtil::ToAbsoluteUrl($eventUrl).")"
				)
			);
		}
		return $eventID;
	}
	private static function SynchronizeLiveFeedEvent($invoiceID, $params)
	{
		$invoiceID = intval($invoiceID);
		if($invoiceID <= 0)
		{
			return;
		}

		if(!is_array($params))
		{
			$params = array();
		}

		$processParents = isset($params['PROCESS_PARENTS']) ? (bool)$params['PROCESS_PARENTS'] : false;
		$parents = isset($params['PARENTS']) && is_array($params['PARENTS']) ? $params['PARENTS'] : array();
		$hasParents = !empty($parents);

		if($processParents)
		{
			CCrmSonetRelation::UnRegisterRelationsByEntity(CCrmOwnerType::Invoice, $invoiceID, array('QUICK' => $hasParents));
		}

		$userID = CCrmSecurityHelper::GetCurrentUserID();
		$startResponsibleID = isset($params['START_RESPONSIBLE_ID']) ? intval($params['START_RESPONSIBLE_ID']) : 0;
		$finalResponsibleID = isset($params['FINAL_RESPONSIBLE_ID']) ? intval($params['FINAL_RESPONSIBLE_ID']) : 0;
		$enableMessages = ($startResponsibleID > 0 || $finalResponsibleID > 0)
			&& IsModuleInstalled('im') && CModule::IncludeModule('im');
		$topic = isset($params['TOPIC']) ? $params['TOPIC'] : $invoiceID;

		$slEntities = CCrmLiveFeed::GetLogEvents(
			array(),
			array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Invoice,
				'ENTITY_ID' => $invoiceID
			),
			array('ID', 'EVENT_ID')
		);

		foreach($slEntities as &$slEntity)
		{
			$slID = intval($slEntity['ID']);
			$slEventType = $slEntity['EVENT_ID'];

			if(isset($params['REFRESH_DATE']) ? (bool)$params['REFRESH_DATE'] : false)
			{
				//Update LOG_UPDATE for force event to rise in global feed
				//Update LOG_DATE for force event to rise in entity feed
				global $DB;
				CCrmLiveFeed::UpdateLogEvent(
					$slID,
					array(
						'=LOG_UPDATE' => $DB->CurrentTimeFunction(),
						'=LOG_DATE' => $DB->CurrentTimeFunction()
					)
				);
			}
			else
			{
				//HACK: FAKE UPDATE FOR INVALIDATE CACHE
				CCrmLiveFeed::UpdateLogEvent(
					$slID,
					array(
						'ENTITY_TYPE_ID' => CCrmOwnerType::Invoice,
						'ENTITY_ID' => $invoiceID,
					)
				);
			}

			if($processParents && $hasParents)
			{
				CCrmSonetRelation::RegisterRelationBundle(
					$slID,
					$slEventType,
					CCrmOwnerType::Invoice,
					$invoiceID,
					$parents,
					array('TYPE_ID' => CCrmSonetRelationType::Ownership)
				);
			}

			if($enableMessages)
			{
				$messageFields = array(
					'MESSAGE_TYPE' => IM_MESSAGE_SYSTEM,
					'FROM_USER_ID' => $userID,
					'NOTIFY_TYPE' => IM_NOTIFY_FROM,
					'NOTIFY_MODULE' => 'crm',
					'LOG_ID' => $slID,
					'NOTIFY_EVENT' => 'invoice_responsible_changed',
					'NOTIFY_TAG' => "CRM|INVOICE|{$invoiceID}"
				);

				$eventUrl = CCrmLiveFeed::GetShowUrl($slID);
				if($startResponsibleID > 0 && $startResponsibleID !== $userID)
				{
					$messageFields['TO_USER_ID'] = $startResponsibleID;
					$messageFields['NOTIFY_MESSAGE'] = GetMessage('CRM_INVOICE_NOT_RESPONSIBLE_IM_NOTIFY', array('#title#' => '<a href="'.htmlspecialcharsbx($eventUrl).'">'.htmlspecialcharsbx($topic).'</a>'));
					$messageFields['NOTIFY_MESSAGE_OUT'] = GetMessage('CRM_INVOICE_NOT_RESPONSIBLE_IM_NOTIFY', array('#title#' => htmlspecialcharsbx($topic)))." (".CCrmUrlUtil::ToAbsoluteUrl($eventUrl).")";

					CIMNotify::Add($messageFields);
				}

				if($finalResponsibleID > 0 && $finalResponsibleID !== $userID)
				{
					$messageFields['TO_USER_ID'] = $finalResponsibleID;
					$messageFields['NOTIFY_MESSAGE'] = GetMessage('CRM_INVOICE_RESPONSIBLE_IM_NOTIFY', array('#title#' => '<a href="'.htmlspecialcharsbx($eventUrl).'">'.htmlspecialcharsbx($topic).'</a>'));
					$messageFields['NOTIFY_MESSAGE_OUT'] = GetMessage('CRM_INVOICE_RESPONSIBLE_IM_NOTIFY', array('#title#' => htmlspecialcharsbx($topic)))." (".CCrmUrlUtil::ToAbsoluteUrl($eventUrl).")";

					CIMNotify::Add($messageFields);
				}
			}
		}
		unset($slEntity);
	}
	private static function UnregisterLiveFeedEvent($invoiceID)
	{
		$invoiceID = intval($invoiceID);
		if($invoiceID <= 0)
		{
			return;
		}

		$slEntities = CCrmLiveFeed::GetLogEvents(
			array(),
			array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Invoice,
				'ENTITY_ID' => $invoiceID
			),
			array('ID')
		);

		$options = array('UNREGISTER_RELATION' => false);
		foreach($slEntities as &$slEntity)
		{
			CCrmLiveFeed::DeleteLogEvent($slEntity['ID'], $options);
		}
		unset($slEntity);
		CCrmSonetRelation::UnRegisterRelationsByEntity(CCrmOwnerType::Invoice, $invoiceID);
	}

	public static function BuildSearchCard($arInvoice, $bReindex = false)
	{
		$arStatuses = array();
		$arSite = array();
		$sEntityType = 'INVOICE';
		$sTitle = 'ORDER_TOPIC';
		$sNumber = 'ACCOUNT_NUMBER';
		$arSearchableFields = array(
			/*'ACCOUNT_NUMBER' => GetMessage('CRM_INVOICE_SEARCH_FIELD_ACCOUNT_NUMBER'),*/
			/*'ORDER_TOPIC' => GetMessage('CRM_INVOICE_SEARCH_FIELD_ORDER_TOPIC'),*/
			'STATUS_ID' => GetMessage('CRM_INVOICE_SEARCH_FIELD_STATUS_ID'),
			'DATE_BILL' => GetMessage('CRM_INVOICE_SEARCH_FIELD_DATE_BILL'),
			'DATE_PAY_BEFORE' => GetMessage('CRM_INVOICE_SEARCH_FIELD_DATE_PAY_BEFORE'),
			'PRICE' => GetMessage('CRM_INVOICE_SEARCH_FIELD_PRICE'),
			'PAY_VOUCHER_NUM' => GetMessage('CRM_INVOICE_SEARCH_FIELD_PAY_VOUCHER_NUM'),
			'USER_DESCRIPTION' => GetMessage('CRM_INVOICE_SEARCH_FIELD_USER_DESCRIPTION'),
			'COMMENTS' => GetMessage('CRM_INVOICE_SEARCH_FIELD_COMMENTS'),
			'REASON_MARKED' => GetMessage('CRM_INVOICE_SEARCH_FIELD_REASON_MARKED')
		);

		$sBody = $arInvoice[$sNumber].', '.$arInvoice[$sTitle]."\n";
		$arField2status = array(
			'STATUS_ID' => 'INVOICE_STATUS'
		);
		$site = new CSite();

		foreach (array_keys($arSearchableFields) as $k)
		{
			if (!isset($arInvoice[$k]))
				continue;

			$v = $arInvoice[$k];

			if($k === 'COMMENTS' || $k === 'USER_DESCRIPTION')
			{
				$v = CSearch::KillTags($v);
			}

			$v = trim($v);

			if ($k === 'DATE_BILL' || $k === 'DATE_PAY_BEFORE')
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

			if (!empty($v) && (!is_numeric($v) || $k === 'PRICE') && $v != 'N' && $v != 'Y')
				$sBody .= $arSearchableFields[$k].": $v\n";
		}

		if ((isset($arInvoice['RESPONSIBLE_NAME']) && !empty($arInvoice['RESPONSIBLE_NAME']))
			|| (isset($arInvoice['RESPONSIBLE_LAST_NAME']) && !empty($arInvoice['RESPONSIBLE_LAST_NAME']))
			|| (isset($arInvoice['RESPONSIBLE_SECOND_NAME']) && !empty($arInvoice['RESPONSIBLE_SECOND_NAME'])))
		{
			$responsibleInfo = CUser::FormatName(
				$site->GetNameFormat(null, $arInvoice['LID']),
				array(
					'LOGIN' => '',
					'NAME' => isset($arInvoice['RESPONSIBLE_NAME']) ? $arInvoice['RESPONSIBLE_NAME'] : '',
					'LAST_NAME' => isset($arInvoice['RESPONSIBLE_LAST_NAME']) ? $arInvoice['RESPONSIBLE_LAST_NAME'] : '',
					'SECOND_NAME' => isset($arInvoice['RESPONSIBLE_SECOND_NAME']) ? $arInvoice['RESPONSIBLE_SECOND_NAME'] : ''
				),
				false, false
			);
			if (isset($arInvoice['RESPONSIBLE_EMAIL']) && !empty($arInvoice['RESPONSIBLE_EMAIL']))
				$responsibleInfo .= ', '.$arInvoice['RESPONSIBLE_EMAIL'];
			if (isset($arInvoice['RESPONSIBLE_WORK_POSITION']) && !empty($arInvoice['RESPONSIBLE_WORK_POSITION']))
				$responsibleInfo .= ', '.$arInvoice['RESPONSIBLE_WORK_POSITION'];
			if (!empty($responsibleInfo) && !is_numeric($responsibleInfo) && $responsibleInfo != 'N' && $responsibleInfo != 'Y')
				$sBody .= GetMessage('CRM_INVOICE_SEARCH_FIELD_RESPONSIBLE_INFO').": $responsibleInfo\n";
		}

		if (intval($arInvoice['PERSON_TYPE_ID']) > 0)
		{
			$arSearchableProperties = self::_getAllowedPropertiesInfo();
			$arSearchableProperties = $arSearchableProperties[$arInvoice['PERSON_TYPE_ID']];
			$arInvoiceProps = self::GetProperties($arInvoice['ID'], $arInvoice['PERSON_TYPE_ID']);
			foreach ($arInvoiceProps as $prop)
			{
				$propCode = $prop['FIELDS']['CODE'];
				if (array_key_exists($propCode, $arSearchableProperties))
				{
					$v = $prop['VALUE'];
					if (!empty($v) && !is_numeric($v) && $v != 'N' && $v != 'Y')
						$sBody .= $arSearchableProperties[$propCode].": $v\n";
				}
			}
		}

		$sDetailURL = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_'.strtolower($sEntityType).'_show'),
			array(
				strtolower($sEntityType).'_id' => $arInvoice['ID']
			)
		);

		$_arAttr = CCrmPerms::GetEntityAttr($sEntityType, $arInvoice['ID']);

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
		if (!isset($_arAttr[$arInvoice['ID']]))
			$_arAttr[$arInvoice['ID']] = array();

		$arAttr[] = $sEntityType; // for perm X
		foreach ($_arAttr[$arInvoice['ID']] as $_s)
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
			'LAST_MODIFIED' => $arInvoice['DATE_UPDATE'],
			'DATE_FROM' => $arInvoice['DATE_INSERT'],
			'TITLE' => GetMessage('CRM_'.$sEntityType).': '.$arInvoice[$sNumber].', '.$arInvoice[$sTitle],
			'PARAM1' => $sEntityType,
			'PARAM2' => $arInvoice['ID'],
			'SITE_ID' => $arSitePath,
			'PERMISSIONS' => $arAttr,
			'BODY' => $sBody,
			'TAGS' => 'crm,'.strtolower($sEntityType).','.GetMessage('CRM_'.$sEntityType)
		);

		if ($bReindex)
			$arResult['ID'] = $sEntityType.'.'.$arInvoice['ID'];

		return $arResult;
	}
	
	public static function ProductRows2BasketItems($arProductRows, $srcCurrencyID = '', $dstCurrencyID = '')
	{
		$basketItems = array();
		
		$srcCurrencyID = strval($srcCurrencyID);
		$dstCurrencyID = strval($dstCurrencyID);
		if (strlen($srcCurrencyID) <= 0 || strlen($dstCurrencyID) <= 0)
			$srcCurrencyID = $dstCurrencyID = '';

		foreach ($arProductRows as $row)
		{
			$freshRow = array();
			$freshRow['ID'] = isset($row['ID']) ? intval($row['ID']) : 0;
			$freshRow['PRODUCT_ID'] = isset($row['PRODUCT_ID']) ? intval($row['PRODUCT_ID']) : 0;
			$freshRow['PRODUCT_NAME'] = isset($row['PRODUCT_NAME']) ? strval($row['PRODUCT_NAME']) : '';
			$freshRow['QUANTITY'] = isset($row['QUANTITY']) ? round(doubleval($row['QUANTITY']), 2) : 0.0;
			$freshRow['PRICE'] = isset($row['PRICE']) ? round(doubleval($row['PRICE']), 2) : 0.0;
			if ($dstCurrencyID != $srcCurrencyID)
				$freshRow['PRICE'] = CCrmCurrency::ConvertMoney($freshRow['PRICE'], $srcCurrencyID, $dstCurrencyID);
			$taxRate = isset($row['TAX_RATE']) ? round(doubleval($row['TAX_RATE']), 2) : 0.0;
			$freshRow['VAT_RATE'] = $taxRate / 100;
			$discountTypeID = isset($row['DISCOUNT_TYPE_ID']) ? intval($row['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::UNDEFINED;
			if ($discountTypeID !== \Bitrix\Crm\Discount::PERCENTAGE && $discountTypeID !== \Bitrix\Crm\Discount::MONETARY)
				$discountTypeID = \Bitrix\Crm\Discount::PERCENTAGE;
			if ($discountTypeID === \Bitrix\Crm\Discount::PERCENTAGE)
			{
				$discountRate = isset($row['DISCOUNT_RATE']) ? round(doubleval($row['DISCOUNT_RATE']), 2) : 0.0;
				$exclusivePrice = CCrmProductRow::CalculateExclusivePrice($freshRow['PRICE'], $taxRate);
				$freshRow['DISCOUNT_PRICE'] = round(\Bitrix\Crm\Discount::calculateDiscountSum($exclusivePrice, $discountRate), 2);
			}
			else
			{
				$freshRow['DISCOUNT_PRICE'] = isset($row['DISCOUNT_SUM']) ? round(doubleval($row['DISCOUNT_SUM']), 2) : 0.0;
				if ($dstCurrencyID != $srcCurrencyID)
					$freshRow['DISCOUNT_PRICE'] = CCrmCurrency::ConvertMoney($freshRow['DISCOUNT_PRICE'], $srcCurrencyID, $dstCurrencyID);
			}
			$freshRow['MEASURE_CODE'] = isset($row['MEASURE_CODE']) ? intval($row['MEASURE_CODE']) : 0;
			$freshRow['MEASURE_NAME'] = isset($row['MEASURE_NAME']) ? strval($row['MEASURE_NAME']) : '';
			$freshRow['CUSTOMIZED'] = isset($row['CUSTOMIZED']) ? ($row['CUSTOMIZED'] === 'Y' ? 'Y' : 'N') : 'Y';
			$basketItems[] = $freshRow;
		}

		return $basketItems;
	}
}

?>
