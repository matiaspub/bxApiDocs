<?php
class CCrmMobileHelper
{
	private static $LEAD_STATUSES = null;
	private static $DEAL_STAGES = null;
	private static $INVOICE_STATUSES = null;
	private static $INVOICE_PROPERTY_INFOS = null;
	private static $STATUS_LISTS = array();

	private static function GetStatusList($entityId)
	{
		if(!isset(self::$STATUS_LISTS[$entityId]))
		{
			self::$STATUS_LISTS[$entityId] = CCrmStatus::GetStatusList($entityId);
			if(!is_array(self::$STATUS_LISTS[$entityId]))
			{
				self::$STATUS_LISTS[$entityId] = array();
			}
		}

		return self::$STATUS_LISTS[$entityId];
	}
	public static function PrepareInvoiceItem(&$item, &$params, $enums = array(), $options = array())
	{
		$itemID = intval($item['~ID']);

		if(isset($params['INVOICE_SHOW_URL_TEMPLATE']))
		{
			$item['SHOW_URL'] = CComponentEngine::makePathFromTemplate(
				$params['INVOICE_SHOW_URL_TEMPLATE'],
				array('invoice_id' => $itemID)
			);
		}

		if(isset($params['INVOICE_EDIT_URL_TEMPLATE']))
		{
			$item['EDIT_URL'] = CComponentEngine::makePathFromTemplate(
				$params['INVOICE_EDIT_URL_TEMPLATE'],
				array('invoice_id' => $itemID)
			);
		}

		if(!isset($item['~ACCOUNT_NUMBER']))
		{
			$item['~ACCOUNT_NUMBER'] = $item['ACCOUNT_NUMBER'] = '';
		}

		if(!isset($item['~DATE_BILL']))
		{
			$item['~DATE_BILL'] = $item['DATE_BILL'] = '';
		}
		else
		{
			$item['~DATE_BILL'] = ConvertTimeStamp(MakeTimeStamp($item['~DATE_BILL']), 'SHORT', SITE_ID);
			$item['DATE_BILL'] = htmlspecialcharsbx($item['~DATE_BILL']);
		}

		if(!isset($item['~DATE_PAY_BEFORE']))
		{
			$item['~DATE_PAY_BEFORE'] = $item['DATE_PAY_BEFORE'] = '';
			$item['DATE_PAY_BEFORE_STAMP'] = 0;
		}

		if($item['~DATE_PAY_BEFORE'] !== '')
		{
			$item['~DATE_PAY_BEFORE'] = ConvertTimeStamp(MakeTimeStamp($item['~DATE_PAY_BEFORE']), 'SHORT', SITE_ID);
			$item['DATE_PAY_BEFORE'] = htmlspecialcharsbx($item['~DATE_PAY_BEFORE']);
		}

		if(!isset($item['~ORDER_TOPIC']))
		{
			$item['~ORDER_TOPIC'] = $item['ORDER_TOPIC'] = '';
		}

		// COMMENTS -->
		if(!isset($item['~COMMENTS']))
		{
			$item['~COMMENTS'] = $item['COMMENTS'] = '';
		}

		if(!isset($item['~USER_DESCRIPTION']))
		{
			$item['~USER_DESCRIPTION'] = $item['USER_DESCRIPTION'] = '';
		}
		//<-- COMMENTS

		// STATUS -->
		if(!isset($item['~STATUS_ID']))
		{
			$item['~STATUS_ID'] = $item['STATUS_ID'] = '';
		}

		$statusID = $item['~STATUS_ID'];
		if($statusID !== '')
		{
			$statuses = self::GetStatusList('INVOICE_STATUS');
			if(!isset($statuses[$statusID]))
			{
				$item['~STATUS_TEXT'] = $item['STATUS_TEXT'];
			}
			else
			{
				$item['~STATUS_TEXT'] = $statuses[$statusID];
				$item['STATUS_TEXT'] = htmlspecialcharsbx($item['~STATUS_TEXT']);
			}
		}
		//<-- STATUS

		//PRICE, CURRENCY -->
		$price = isset($item['~PRICE']) ? doubleval($item['~PRICE']) : 0.0;
		$item['~PRICE'] = $item['PRICE'] = $price;

		$currencyID = isset($item['~CURRENCY']) ? $item['~CURRENCY'] : '';
		if($currencyID === '')
		{
			$currencyID = $item['~CURRENCY'] = CCrmCurrency::GetBaseCurrencyID();
			$item['CURRENCY'] = htmlspecialcharsbx($currencyID);
		}

		$item['~CURRENCY_NAME'] = CCrmCurrency::GetCurrencyName($currencyID);
		$item['CURRENCY_NAME'] = htmlspecialcharsbx($item['~CURRENCY_NAME']);

		$item['~FORMATTED_PRICE'] = CCrmCurrency::MoneyToString($price, $currencyID);
		$item['FORMATTED_PRICE'] = strip_tags($item['~FORMATTED_PRICE']);
		//<-- PRICE, CURRENCY

		//DEAL -->
		$dealID = isset($item['~UF_DEAL_ID']) ? intval($item['~UF_DEAL_ID']) : 0;
		$item['~DEAL_ID'] = $item['DEAL_ID'] = $dealID;
		if($dealID <= 0)
		{
			$item['~DEAL_TITLE'] = $item['DEAL_TITLE'] = '';
		}
		else
		{
			$item['~DEAL_TITLE'] = CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $dealID);
			$item['DEAL_TITLE'] = htmlspecialcharsbx($item['~DEAL_TITLE']);
		}
		//<-- DEAL

		// LOCATION -->
		if(is_array($options) && isset($options['ENABLE_LOCATION']) && $options['ENABLE_LOCATION'])
		{
			$properties = is_array($enums) && isset($enums['INVOICE_PROPERTIES']) && is_array($enums['INVOICE_PROPERTIES'])
				? $enums['INVOICE_PROPERTIES'] : null;

			$locationID = is_array($properties) && isset($properties['PR_LOCATION']) ? intval($properties['PR_LOCATION']['VALUE']) : 0;
			$item['~LOCATION_ID'] = $item['LOCATION_ID'] = $locationID;

			$item['~LOCATION_NAME'] = $locationID > 0 ? CCrmInvoice::ResolveLocationName($locationID) : '';
			$item['LOCATION_NAME'] = htmlspecialcharsbx($item['~LOCATION_NAME']);
		}
		//<-- LOCATION

		$enableMultiFields = is_array($options) && isset($options['ENABLE_MULTI_FIELDS']) && $options['ENABLE_MULTI_FIELDS'];

		//CONTACT -->
		$contactID = isset($item['UF_CONTACT_ID']) ? intval($item['UF_CONTACT_ID']) : 0;
		$item['~CONTACT_ID'] = $item['CONTACT_ID'] = $contactID;
		$contact = null;
		if($contactID > 0)
		{
			$dbContact = CCrmContact::GetListEx(array(), array('=ID' => $contactID), false, false, array('NAME', 'SECOND_NAME', 'LAST_NAME', 'POST', 'PHOTO'));
			$contact = $dbContact ? $dbContact->Fetch() : null;
		}

		if(!$contact)
		{
			$item['~CONTACT_FULL_NAME'] = $item['CONTACT_FULL_NAME'] = $item['~CONTACT_POST'] = $item['CONTACT_POST'] = '';
			$item['~CONTACT_PHOTO'] = $item['CONTACT_PHOTO'] = 0;

			if($enableMultiFields)
			{
				$item['CONTACT_FM'] = array();
			}
		}
		else
		{
			$item['~CONTACT_FULL_NAME'] = CUser::FormatName(
				\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
				array(
					'LOGIN' => '',
					'NAME' => isset($contact['NAME']) ? $contact['NAME'] : '',
					'SECOND_NAME' => isset($contact['SECOND_NAME']) ? $contact['SECOND_NAME'] : '',
					'LAST_NAME' => isset($contact['LAST_NAME']) ? $contact['LAST_NAME'] : ''
				),
				false,
				false
			);
			$item['CONTACT_FULL_NAME'] = htmlspecialcharsbx($item['~CONTACT_FULL_NAME']);

			$item['~CONTACT_POST'] = isset($contact['POST']) ? $contact['POST'] : '';
			$item['CONTACT_POST'] = htmlspecialcharsbx($item['~CONTACT_POST']);

			$item['~CONTACT_PHOTO'] = $item['CONTACT_PHOTO'] = isset($contact['PHOTO']) ? intval($contact['PHOTO']) : 0;

			if($enableMultiFields)
			{
				$item['CONTACT_FM'] = array();
				$dbMultiFields = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $contactID)
				);

				if($dbMultiFields)
				{
					while($multiFields = $dbMultiFields->Fetch())
					{
						$item['CONTACT_FM'][$multiFields['TYPE_ID']][] = array('VALUE' => $multiFields['VALUE'], 'VALUE_TYPE' => $multiFields['VALUE_TYPE']);
					}
				}
			}
		}
		//<-- CONTACT

		//COMPANY -->
		$companyID = isset($item['UF_COMPANY_ID']) ? intval($item['UF_COMPANY_ID']) : 0;
		$item['~COMPANY_ID'] = $item['COMPANY_ID'] = $companyID;
		$company = null;
		if($companyID > 0)
		{
			$dbCompany = CCrmCompany::GetListEx(array(), array('=ID' => $companyID), false, false, array('TITLE', 'LOGO'));
			$company = $dbCompany ? $dbCompany->Fetch() : null;
		}

		if(!$company)
		{
			$item['~COMPANY_TITLE'] = $item['COMPANY_TITLE'] = '';
			$item['~COMPANY_LOGO'] = $item['COMPANY_LOGO'] = 0;
		}
		else
		{
			$item['~COMPANY_TITLE'] =  isset($company['TITLE']) ? $company['TITLE'] : '';
			$item['COMPANY_TITLE'] =  htmlspecialcharsbx($item['~COMPANY_TITLE']);
			$item['~COMPANY_LOGO'] = $item['COMPANY_LOGO'] = isset($company['LOGO']) ? intval($company['LOGO']) : 0;

			if($enableMultiFields)
			{
				$item['COMPANY_FM'] = array();
				$dbMultiFields = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $companyID)
				);

				if($dbMultiFields)
				{
					while($multiFields = $dbMultiFields->Fetch())
					{
						$item['COMPANY_FM'][$multiFields['TYPE_ID']][] = array('VALUE' => $multiFields['VALUE'], 'VALUE_TYPE' => $multiFields['VALUE_TYPE']);
					}
				}
			}
		}
		//<-- COMPANY

		$personTypeID = CCrmInvoice::ResolvePersonTypeID($companyID, $contactID);

		// PAYER_INFO -->
		if(is_array($options) && isset($options['ENABLE_PAYER_INFO']) && $options['ENABLE_PAYER_INFO'])
		{
			if($companyID <= 0 && $contactID <= 0)
			{
				$item['~PAYER_INFO'] = $item['PAYER_INFO'] = '';
			}
			else
			{
				// Get invoice properties
				$properties = isset($item['INVOICE_PROPERTIES']) ? $item['INVOICE_PROPERTIES'] : null;

				if (!is_array($properties) && $personTypeID > 0)
				{
					$properties = CCrmInvoice::GetProperties($itemID, $personTypeID);
					if($itemID <= 0)
					{
						CCrmInvoice::__RewritePayerInfo($companyID, $contactID, $properties);
					}
				}

				$item['~PAYER_INFO'] = is_array($properties) ? CCrmInvoice::__MakePayerInfoString($properties) : '';
				$item['PAYER_INFO'] = htmlspecialcharsbx($item['~PAYER_INFO']);
			}
		}
		//<-- PAYER_INFO

		// PAY_SYSTEM -->
		if(!isset($item['~PAY_SYSTEM_ID']))
		{
			$item['~PAY_SYSTEM_ID'] = $item['PAY_SYSTEM_ID'] = '';
		}

		$paySystemID = $item['~PAY_SYSTEM_ID'];
		$paySystems = is_array($enums) && isset($enums['PAY_SYSTEMS']) && is_array($enums['PAY_SYSTEMS'])
			? $enums['PAY_SYSTEMS']
			: ($personTypeID > 0 ? CCrmPaySystem::GetPaySystemsListItems($personTypeID) : array());

		if(isset($paySystems[$paySystemID]))
		{
			$item['~PAY_SYSTEM_NAME'] = $paySystems[$paySystemID];
			$item['PAY_SYSTEM_NAME'] = htmlspecialcharsbx($item['~PAY_SYSTEM_NAME']);
		}
		else
		{
			$item['~PAY_SYSTEM_NAME'] = $item['PAY_SYSTEM_NAME'] = '';
		}
		//<-- PAY_SYSTEM

		// RESPONSIBLE -->
		$responsibleID = isset($item['~RESPONSIBLE_ID']) ? intval($item['~RESPONSIBLE_ID']) : 0;
		$item['RESPONSIBLE_SHOW_URL'] = '';
		$item['~RESPONSIBLE_FORMATTED_NAME'] = '';
		if($responsibleID > 0)
		{
			$item['RESPONSIBLE_SHOW_URL'] = $params['USER_PROFILE_URL_TEMPLATE'] !== ''
				? CComponentEngine::makePathFromTemplate(
					$params['USER_PROFILE_URL_TEMPLATE'],
					array('user_id' => $responsibleID)
			) : '';

			$item['~RESPONSIBLE_FORMATTED_NAME'] = CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($item['~RESPONSIBLE_LOGIN']) ? $item['~RESPONSIBLE_LOGIN'] : '',
					'NAME' => isset($item['~RESPONSIBLE_NAME']) ? $item['~RESPONSIBLE_NAME'] : '',
					'LAST_NAME' => isset($item['~RESPONSIBLE_LAST_NAME']) ? $item['~RESPONSIBLE_LAST_NAME'] : '',
					'SECOND_NAME' => isset($item['~RESPONSIBLE_SECOND_NAME']) ? $item['~RESPONSIBLE_SECOND_NAME'] : ''
				),
				true, false
			);
		}

		$item['RESPONSIBLE_FORMATTED_NAME'] = htmlspecialcharsbx($item['~RESPONSIBLE_FORMATTED_NAME']);
		//<-- RESPONSIBLE
	}
	public static function PrepareInvoiceData(&$fields)
	{
		$statusID = $fields['~STATUS_ID'];
		$success = CCrmStatusInvoice::isStatusSuccess($statusID);
		$failed = CCrmStatusInvoice::isStatusFailed($statusID);

		$paymentTimeStamp = 0;
		$paymentDate = '';
		$paymentDoc = '';
		$paymentComment = '';
		$cancelTimeStamp = 0;
		$cancelDate = '';
		$cancelReason = '';
		if($success)
		{
			$paymentTimeStamp = MakeTimeStamp($fields['~PAY_VOUCHER_DATE']);
			$paymentDate = isset($fields['~PAY_VOUCHER_DATE'])
				? ConvertTimeStamp($paymentTimeStamp, 'SHORT', SITE_ID)
				: '';
			$paymentDoc =  isset($fields['~PAY_VOUCHER_NUM']) ? $fields['~PAY_VOUCHER_NUM'] : '';
			$paymentComment =  isset($fields['~REASON_MARKED']) ? $fields['~REASON_MARKED'] : '';
		}
		else
		{
			$cancelTimeStamp = MakeTimeStamp($fields['~DATE_MARKED']);
			$cancelDate = isset($fields['~DATE_MARKED'])
				? ConvertTimeStamp($cancelTimeStamp, 'SHORT', SITE_ID)
				: '';
			$cancelReason =  isset($fields['~REASON_MARKED']) ? $fields['~REASON_MARKED'] : '';
		}

		return array(
			'ID' => $fields['~ID'],
			'SHOW_URL' => $fields['SHOW_URL'],
			'EDIT_URL' => isset($fields['EDIT_URL']) ? $fields['EDIT_URL'] : '',
			'ACCOUNT_NUMBER' => $fields['~ACCOUNT_NUMBER'],
			'ORDER_TOPIC' => $fields['~ORDER_TOPIC'],
			'STATUS_ID' => $statusID,
			'STATUS_TEXT' => $fields['~STATUS_TEXT'],
			'PRICE' => $fields['~PRICE'],
			'CURRENCY' => $fields['~CURRENCY'],
			'FORMATTED_PRICE' => $fields['~FORMATTED_PRICE'],
			'DEAL_ID' => $fields['~DEAL_ID'],
			'DEAL_TITLE' => $fields['~DEAL_TITLE'],
			'CONTACT_ID' => $fields['~CONTACT_ID'],
			'CONTACT_FULL_NAME' => $fields['~CONTACT_FULL_NAME'],
			'COMPANY_ID' => $fields['~COMPANY_ID'],
			'COMPANY_TITLE' => $fields['~COMPANY_TITLE'],
			'PAYMENT_TIME_STAMP' => $paymentTimeStamp,
			'PAYMENT_DATE' => $paymentDate,
			'PAYMENT_DOC' => $paymentDoc,
			'PAYMENT_COMMENT' => $paymentComment,
			'CANCEL_TIME_STAMP' => $cancelTimeStamp,
			'CANCEL_DATE' => $cancelDate,
			'CANCEL_REASON' => $cancelReason,
			'IS_FINISHED' => ($success || $failed),
			'IS_SUCCESSED' => $success
		);
	}
	public static function PrepareInvoiceClientRequisites($personTypeID, &$properties)
	{
		if(!is_int($personTypeID))
		{
			$personTypeID = intval($personTypeID);
		}

		if($personTypeID <= 0)
		{
			return array();
		}

		if(!self::$INVOICE_PROPERTY_INFOS)
		{
			self::$INVOICE_PROPERTY_INFOS = CCrmInvoice::GetPropertiesInfo(0, true);
		}

		$propertyInfos = isset(self::$INVOICE_PROPERTY_INFOS[$personTypeID]) ? self::$INVOICE_PROPERTY_INFOS[$personTypeID] : array();
		$result = array();
		foreach($properties as $alias => &$property)
		{
			$propertyFields = isset($property['FIELDS']) ? $property['FIELDS'] : null;
			if(!is_array($propertyFields) || empty($propertyFields))
			{
				continue;
			}

			$id = isset($propertyFields['ID']) ? $propertyFields['ID'] : 0;
			$code = isset($propertyFields['CODE']) ? $propertyFields['CODE'] : '';

			if(!isset($propertyInfos[$code]))
			{
				// Property is not allowed (or required) in CRM context
				continue;
			}

			$result[] = array(
				'ID' => $id,
				'CODE' => $code,
				'ALIAS' => $alias,
				'TYPE' => isset($propertyFields['TYPE']) ? $propertyFields['TYPE'] : 'TEXT',
				'SORT' => isset($propertyFields['SORT']) ? intval($propertyFields['SORT']) : 0,
				'REQUIRED' => isset($propertyFields['REQUIRED']) && $propertyFields['REQUIRED'] === 'Y',
				'TITLE' => isset($propertyInfos[$code]) && isset($propertyInfos[$code]['NAME']) ? $propertyInfos[$code]['NAME'] : $code,
				'VALUE' => isset($property['VALUE']) ? $property['VALUE'] : ''
			);
		}
		unset($property);
		return $result;
	}
	public static function PrepareInvoiceClientInfoFormat($personTypeID)
	{
		$personTypeID = intval($personTypeID);
		if($personTypeID <= 0)
		{
			return '';
		}

		if(!self::$INVOICE_PROPERTY_INFOS)
		{
			self::$INVOICE_PROPERTY_INFOS = CCrmInvoice::GetPropertiesInfo(0, true);
		}

		$propertyInfos = isset(self::$INVOICE_PROPERTY_INFOS[$personTypeID]) ? self::$INVOICE_PROPERTY_INFOS[$personTypeID] : null;
		if(!is_array($propertyInfos))
		{
			return '';
		}

		$result = array();
		foreach ($propertyInfos as $code => &$fields)
		{
			$type = $fields['TYPE'];
			if($type !== 'TEXT' && $type !== 'TEXTAREA')
			{
				continue;
			}

			$result[] = $code;
		}
		unset($fields);

		return implode(',', $result);
	}
	public static function PrepareInvoiceTaxInfo(&$taxList, $enableTotals = false)
	{
		IncludeModuleLangFile(__FILE__);

		$result = array(
			'SUM_INCUDED_IN_PRICE' => 0.0,
			'SUM_EXCLUDED_FROM_PRICE' => 0.0,
			'SUM_TOTAL' => 0.0,
			'ITEMS' => array()
		);
		foreach($taxList as &$tax)
		{
			$name = isset($tax['TAX_NAME']) ? $tax['TAX_NAME'] : '';
			if($name === '')
			{
				$name = isset($tax['NAME'])
					? $tax['NAME']
					: (isset($tax['CODE']) ? $tax['CODE'] : '');
			}

			$taxSum = isset($tax['VALUE_MONEY']) ? doubleval($tax['VALUE_MONEY']) : 0.0;
			$isInPrice = isset($tax['IS_IN_PRICE']) && $tax['IS_IN_PRICE'] === 'Y';
			$title = $isInPrice
				? GetMessage('CRM_INVOICE_TAX_INCLUDED_TITLE', array('#TAX_NAME#' => $name))
				: $name;

			$taxInfo = array(
				'NAME' => $name,
				'TITLE' => $title,
				'SUM' => $taxSum,
				'IS_IN_PRICE' => $isInPrice
			);

			$taxInfo['FORMATTED_SUM'] = isset($tax['VALUE_MONEY_FORMATED'])
				? $tax['VALUE_MONEY_FORMATED'] : CCrmCurrency::MoneyToString($taxSum, $currencyID);

			$result['ITEMS'][] = &$taxInfo;

			if($enableTotals)
			{
				$result['SUM_TOTAL'] += $taxSum;
				if($isInPrice)
				{
					$result['SUM_INCUDED_IN_PRICE'] += $taxSum;
				}
				else
				{
					$result['SUM_EXCLUDED_FROM_PRICE'] += $taxSum;
				}
			}
			unset($taxInfo);
		}
		unset($tax);
		return $result;
	}
	public static function PrepareDealItem(&$item, &$params, $enums = array())
	{
		$itemID = intval($item['~ID']);

		$item['SHOW_URL'] = CComponentEngine::MakePathFromTemplate(
			$params['DEAL_SHOW_URL_TEMPLATE'],
			array('deal_id' => $itemID)
		);

		if(!isset($item['~TITLE']))
		{
			$item['~TITLE'] = $item['TITLE'] =  '';
		}

		if(!isset($item['~OPPORTUNITY']))
		{
			$item['~OPPORTUNITY'] = $item['OPPORTUNITY'] = 0.0;
		}

		if(!isset($item['~PROBABILITY']))
		{
			$item['~PROBABILITY'] = $item['PROBABILITY'] = 0;
		}

		$currencyID = isset($item['~CURRENCY_ID']) ? $item['~CURRENCY_ID'] : '';
		if($currencyID === '')
		{
			$currencyID = $item['~CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
			$item['CURRENCY_ID'] = htmlspecialcharsbx($currencyID);
		}

		$item['~CURRENCY_NAME'] = CCrmCurrency::GetCurrencyName($currencyID);
		$item['CURRENCY_NAME'] = htmlspecialcharsbx($item['~CURRENCY_NAME']);

		$item['~FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString($item['~OPPORTUNITY'], $currencyID);
		$item['FORMATTED_OPPORTUNITY'] = strip_tags($item['~FORMATTED_OPPORTUNITY']);

		$contactID = isset($item['~CONTACT_ID']) ? intval($item['~CONTACT_ID']) : 0;
		$item['~CONTACT_ID'] = $item['CONTACT_ID'] = $contactID;
		$item['CONTACT_SHOW_URL'] = $contactID > 0 && $params['CONTACT_SHOW_URL_TEMPLATE'] !== ''
			? CComponentEngine::MakePathFromTemplate(
				$params['CONTACT_SHOW_URL_TEMPLATE'], array('contact_id' => $contactID)
			) : '';

		$item['~CONTACT_FORMATTED_NAME'] = $contactID > 0
			? CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => '',
					'NAME' => isset($item['~CONTACT_NAME']) ? $item['~CONTACT_NAME'] : '',
					'LAST_NAME' => isset($item['~CONTACT_LAST_NAME']) ? $item['~CONTACT_LAST_NAME'] : '',
					'SECOND_NAME' => isset($item['~CONTACT_SECOND_NAME']) ? $item['~CONTACT_SECOND_NAME'] : ''
				),
				false, false
			) : '';
		$item['CONTACT_FORMATTED_NAME'] = htmlspecialcharsbx($item['~CONTACT_FORMATTED_NAME']);

		$companyID = isset($item['~COMPANY_ID']) ? intval($item['~COMPANY_ID']) : 0;
		$item['~COMPANY_ID'] = $item['COMPANY_ID'] = $companyID;

		if(!isset($item['~COMPANY_TITLE']))
		{
			$item['~COMPANY_TITLE'] = $item['COMPANY_TITLE'] = '';
		}

		$item['COMPANY_SHOW_URL'] = $companyID > 0
			? CComponentEngine::MakePathFromTemplate(
				$params['COMPANY_SHOW_URL_TEMPLATE'], array('company_id' => $companyID)
			) : '';

		$clientTitle = '';
		if($item['~CONTACT_ID'] > 0)
			$clientTitle = $item['~CONTACT_FORMATTED_NAME'];
		if($item['~COMPANY_ID'] > 0 && $item['COMPANY_TITLE'] !== '')
		{
			if($clientTitle !== '')
				$clientTitle .= ', ';
			$clientTitle .= $item['~COMPANY_TITLE'];
		}

		$item['~CLIENT_TITLE'] = $clientTitle;
		$item['CLIENT_TITLE'] = htmlspecialcharsbx($item['~CLIENT_TITLE']);

		$assignedByID = isset($item['~ASSIGNED_BY_ID']) ? intval($item['~ASSIGNED_BY_ID']) : 0;
		$item['~ASSIGNED_BY_ID'] = $item['ASSIGNED_BY_ID'] = $assignedByID;
		$item['ASSIGNED_BY_SHOW_URL'] = $assignedByID > 0  ?
			CComponentEngine::MakePathFromTemplate(
				$params['USER_PROFILE_URL_TEMPLATE'],
					array('user_id' => $assignedByID)
			) : '';

		$item['~ASSIGNED_BY_FORMATTED_NAME'] = $assignedByID > 0
			? CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($item['~ASSIGNED_BY_LOGIN']) ? $item['~ASSIGNED_BY_LOGIN'] : '',
					'NAME' => isset($item['~ASSIGNED_BY_NAME']) ? $item['~ASSIGNED_BY_NAME'] : '',
					'LAST_NAME' => isset($item['~ASSIGNED_BY_LAST_NAME']) ? $item['~ASSIGNED_BY_LAST_NAME'] : '',
					'SECOND_NAME' => isset($item['~ASSIGNED_BY_SECOND_NAME']) ? $item['~ASSIGNED_BY_SECOND_NAME'] : ''
				),
				true, false
			) : '';
		$item['ASSIGNED_BY_FORMATTED_NAME'] = htmlspecialcharsbx($item['~ASSIGNED_BY_FORMATTED_NAME']);

		$stageList = $enums && isset($enums['STAGE_LIST'])
			? $enums['STAGE_LIST'] : self::GetStatusList('DEAL_STAGE');

		if(!isset($item['~STAGE_ID']))
		{
			$item['~STAGE_ID'] = $item['STAGE_ID'] = '';
		}

		$stageID = $item['~STAGE_ID'];
		if($stageID === '' || !isset($stageList[$stageID]))
		{
			$item['~STAGE_NAME'] = $item['STAGE_NAME'] = '';
		}
		else
		{
			$item['~STAGE_NAME'] = $stageList[$stageID];
			$item['STAGE_NAME'] = htmlspecialcharsbx($item['~STAGE_NAME']);
		}

		$typeList = $enums && isset($enums['TYPE_LIST'])
			? $enums['TYPE_LIST'] : self::GetStatusList('DEAL_TYPE');

		if(!isset($item['~TYPE_ID']))
		{
			$item['~TYPE_ID'] = $item['TYPE_ID'] = '';
		}

		$typeID = $item['~TYPE_ID'];
		if($typeID === '' || !isset($typeList[$typeID]))
		{
			$item['~TYPE_NAME'] = $item['TYPE_NAME'] = '';
		}
		else
		{
			$item['~TYPE_NAME'] = $typeList[$typeID];
			$item['TYPE_NAME'] = htmlspecialcharsbx($item['~TYPE_NAME']);
		}

		if(!isset($item['~COMMENTS']))
		{
			$item['~COMMENTS'] = $item['COMMENTS'] = '';
		}
	}
	public static function PrepareDealData(&$fields)
	{
		$clientImageID = 0;
		$clientTitle = '';
		//$clientLegend = '';
		if($fields['~CONTACT_ID'] > 0)
		{
			$clientImageID = $fields['~CONTACT_PHOTO'];
			$clientTitle = $fields['~CONTACT_FORMATTED_NAME'];
			//$clientLegend = $fields['~CONTACT_POST'];
		}
		if($fields['~COMPANY_ID'] > 0)
		{
			if($clientImageID === 0)
			{
				$clientImageID = $fields['~COMPANY_LOGO'];
			}
			if($clientTitle !== '')
			{
				$clientTitle .= ', ';
			}
			$clientTitle .= $fields['~COMPANY_TITLE'];
		}

		$stageID = $fields['~STAGE_ID'];
		$stageSort = CCrmDeal::GetStageSort($stageID);
		$finalStageSort = CCrmDeal::GetFinalStageSort();

		return array(
			'ID' => $fields['~ID'],
			'TITLE' => $fields['~TITLE'],
			'STAGE_ID' => $fields['~STAGE_ID'],
			'STAGE_NAME' => $fields['~STAGE_NAME'],
			'TYPE_ID' => $fields['~TYPE_ID'],
			'TYPE_NAME' => $fields['~TYPE_NAME'],
			'PROBABILITY' => $fields['~PROBABILITY'],
			'OPPORTUNITY' => $fields['~OPPORTUNITY'],
			'FORMATTED_OPPORTUNITY' => $fields['FORMATTED_OPPORTUNITY'],
			'CURRENCY_ID' => $fields['~CURRENCY_ID'],
			'ASSIGNED_BY_ID' => $fields['~ASSIGNED_BY_ID'],
			'ASSIGNED_BY_FORMATTED_NAME' => $fields['~ASSIGNED_BY_FORMATTED_NAME'],
			'CONTACT_ID' => $fields['~CONTACT_ID'],
			'CONTACT_FORMATTED_NAME' => $fields['~CONTACT_FORMATTED_NAME'],
			'COMPANY_ID' => $fields['~COMPANY_ID'],
			'COMPANY_TITLE' => $fields['~COMPANY_TITLE'],
			'COMMENTS' => $fields['~COMMENTS'],
			'DATE_CREATE' => $fields['~DATE_CREATE'],
			'DATE_MODIFY' => $fields['~DATE_MODIFY'],
			'SHOW_URL' => $fields['SHOW_URL'],
			'CONTACT_SHOW_URL' => $fields['CONTACT_SHOW_URL'],
			'COMPANY_SHOW_URL' => $fields['COMPANY_SHOW_URL'],
			'ASSIGNED_BY_SHOW_URL' => $fields['ASSIGNED_BY_SHOW_URL'],
			'CLIENT_TITLE' => $clientTitle,
			'CLIENT_IMAGE_ID' => $clientImageID,
			'IS_FINISHED' => $stageSort >= $finalStageSort,
			'IS_SUCCESSED' => $stageSort === $finalStageSort
		);
	}
	public static function PrepareContactItem(&$item, &$params, $enums = array())
	{
		$itemID = intval($item['~ID']);

		if(isset($params['CONTACT_SHOW_URL_TEMPLATE']))
		{
			$item['SHOW_URL'] = CComponentEngine::MakePathFromTemplate(
				$params['CONTACT_SHOW_URL_TEMPLATE'],
				array('contact_id' => $itemID)
			);
		}

		if(isset($params['CONTACT_EDIT_URL_TEMPLATE']))
		{
			$item['EDIT_URL'] = CComponentEngine::MakePathFromTemplate(
				$params['CONTACT_EDIT_URL_TEMPLATE'],
				array('contact_id' => $itemID)
			);
		}

		if(!isset($item['~NAME']))
		{
			$item['~NAME'] = $item['NAME'] = '';
		}

		if(!isset($item['~LAST_NAME']))
		{
			$item['~LAST_NAME'] = $item['LAST_NAME'] = '';
		}

		if(!isset($item['~SECOND_NAME']))
		{
			$item['~SECOND_NAME'] = $item['SECOND_NAME'] = '';
		}

		$item['~FORMATTED_NAME'] = CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => '',
					'NAME' => $item['~NAME'],
					'LAST_NAME' => $item['~LAST_NAME'],
					'SECOND_NAME' => $item['~SECOND_NAME']
				),
				false, false
			);
		$item['FORMATTED_NAME'] = htmlspecialcharsbx($item['~FORMATTED_NAME']);

		$lastName = $item['~LAST_NAME'];
		$item['CLASSIFIER'] = $lastName !== '' ? strtoupper(substr($lastName, 0, 1)) : '';

		if(!isset($item['~POST']))
		{
			$item['~POST'] = $item['POST'] = '';
		}

		$companyID = isset($item['~COMPANY_ID']) ? intval($item['~COMPANY_ID']) : 0;
		$item['~COMPANY_ID'] = $item['COMPANY_ID'] = $companyID;

		if(!isset($item['~COMPANY_TITLE']))
		{
			$item['~COMPANY_TITLE'] = $item['COMPANY_TITLE'] = '';
		}

		/*$item['COMPANY_SHOW_URL'] = $companyID > 0
			? CComponentEngine::MakePathFromTemplate(
				$params['COMPANY_SHOW_URL_TEMPLATE'], array('company_id' => $companyID)
			) : '';*/

		$assignedByID = isset($item['~ASSIGNED_BY_ID']) ? intval($item['~ASSIGNED_BY_ID']) : 0;
		$item['~ASSIGNED_BY_ID'] = $item['ASSIGNED_BY_ID'] = $assignedByID;
		$item['ASSIGNED_BY_SHOW_URL'] = $assignedByID > 0  ?
			CComponentEngine::MakePathFromTemplate(
				$params['USER_PROFILE_URL_TEMPLATE'],
					array('user_id' => $assignedByID)
			) : '';

		$item['~ASSIGNED_BY_FORMATTED_NAME'] = $assignedByID > 0
			? CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($item['~ASSIGNED_BY_LOGIN']) ? $item['~ASSIGNED_BY_LOGIN'] : '',
					'NAME' => isset($item['~ASSIGNED_BY_NAME']) ? $item['~ASSIGNED_BY_NAME'] : '',
					'LAST_NAME' => isset($item['~ASSIGNED_BY_LAST_NAME']) ? $item['~ASSIGNED_BY_LAST_NAME'] : '',
					'SECOND_NAME' => isset($item['~ASSIGNED_BY_SECOND_NAME']) ? $item['~ASSIGNED_BY_SECOND_NAME'] : ''
				),
				true, false
			) : '';
		$item['ASSIGNED_BY_FORMATTED_NAME'] = htmlspecialcharsbx($item['~ASSIGNED_BY_FORMATTED_NAME']);

		if(!isset($item['~POST']))
		{
			$item['~POST'] = $item['POST'] = '';
		}

		if(!isset($item['~ADDRESS']))
		{
			$item['~ADDRESS'] = $item['ADDRESS'] = '';
		}

		if(!isset($item['~COMMENTS']))
		{
			$item['~COMMENTS'] = $item['COMMENTS'] = '';
		}

		if(!isset($item['~TYPE_ID']))
		{
			$item['~TYPE_ID'] = $item['TYPE_ID'] = '';
		}

		$typeList = $enums && isset($enums['CONTACT_TYPE'])
			? $enums['CONTACT_TYPE'] : null;

		if(is_array($typeList))
		{
			$typeID = $item['~TYPE_ID'];
			$item['~TYPE_NAME'] = isset($typeList[$typeID]) ? $typeList[$typeID] : $typeID;
			$item['TYPE_NAME'] = htmlspecialcharsbx($item['~TYPE_NAME']);
		}

		if(!isset($item['~PHOTO']))
		{
			$item['~PHOTO'] = $item['PHOTO'] = 0;
		}
	}
	public static function PrepareContactData(&$fields)
	{
		$legend = '';
		$companyTitle = isset($fields['~COMPANY_TITLE']) ? $fields['~COMPANY_TITLE'] : '';
		$post = isset($fields['~POST']) ? $fields['~POST'] : '';

		if($companyTitle !== '' && $post !== '')
		{
			$legend = "{$companyTitle}, {$post}";
		}
		elseif($companyTitle !== '')
		{
			$legend = $companyTitle;
		}
		elseif($post !== '')
		{
			$legend = $post;
		}

		$listImageInfo = null;
		$viewImageInfo = null;
		$photoID = isset($fields['PHOTO']) ? intval($fields['PHOTO']) : 0;
		if($photoID > 0)
		{
			$listImageInfo = CFile::ResizeImageGet(
				$photoID, array('width' => 40, 'height' => 40), BX_RESIZE_IMAGE_EXACT);
			$viewImageInfo = CFile::ResizeImageGet(
				$photoID, array('width' => 55, 'height' => 55), BX_RESIZE_IMAGE_EXACT);
		}
		else
		{
			$listImageInfo = array('src' => SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_contact_small.png?ver=1');
			$viewImageInfo = array('src' => SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_contact_big.png?ver=1');
		}
		return array(
			'ID' => $fields['~ID'],
			'NAME' => isset($fields['~NAME']) ? $fields['~NAME'] : '',
			'LAST_NAME' => isset($fields['~LAST_NAME']) ? $fields['~LAST_NAME'] : '',
			'SECOND_NAME' => isset($fields['~SECOND_NAME']) ? $fields['~SECOND_NAME'] : '',
			'FORMATTED_NAME' => isset($fields['~FORMATTED_NAME']) ? $fields['~FORMATTED_NAME'] : '',
			'COMPANY_ID' => isset($fields['~COMPANY_ID']) ? $fields['~COMPANY_ID'] : '',
			'COMPANY_TITLE' => $companyTitle,
			'POST' => $post,
			'ASSIGNED_BY_ID' => isset($fields['~ASSIGNED_BY_ID']) ? $fields['~ASSIGNED_BY_ID'] : '',
			'ASSIGNED_BY_FORMATTED_NAME' => isset($fields['~ASSIGNED_BY_FORMATTED_NAME']) ? $fields['~ASSIGNED_BY_FORMATTED_NAME'] : '',
			'COMMENTS' => isset($fields['~COMMENTS']) ? $fields['~COMMENTS'] : '',
			'DATE_CREATE' => isset($fields['~DATE_CREATE']) ? $fields['~DATE_CREATE'] : '',
			'DATE_MODIFY' => isset($fields['~DATE_MODIFY']) ? $fields['~DATE_MODIFY'] : '',
			'LEGEND' => $legend,
			'CLASSIFIER' => isset($fields['CLASSIFIER']) ? $fields['CLASSIFIER'] : '',
			//'COMPANY_SHOW_URL' => isset($fields['COMPANY_SHOW_URL']) ? $fields['COMPANY_SHOW_URL'] : '',
			'ASSIGNED_BY_SHOW_URL' => isset($fields['ASSIGNED_BY_SHOW_URL']) ? $fields['ASSIGNED_BY_SHOW_URL'] : '',
			'SHOW_URL' => isset($fields['SHOW_URL']) ? $fields['SHOW_URL'] : '',
			'EDIT_URL' => isset($fields['EDIT_URL']) ? $fields['EDIT_URL'] : '',
			'IMAGE_ID' => $photoID,
			'LIST_IMAGE_URL' => $listImageInfo && isset($listImageInfo['src']) ? $listImageInfo['src'] : '',
			'VIEW_IMAGE_URL' => $viewImageInfo && isset($viewImageInfo['src']) ? $viewImageInfo['src'] : ''
		);
	}
	public static function PrepareCompanyItem(&$item, &$params, $enums = array())
	{
		$itemID = intval($item['~ID']);

		if(!isset($item['~TITLE']))
		{
			$item['~TITLE'] = $item['TITLE'] = '';
		}

		$item['SHOW_URL'] = CComponentEngine::MakePathFromTemplate(
			$params['COMPANY_SHOW_URL_TEMPLATE'],
			array('company_id' => $itemID)
		);

		$typeList = $enums && isset($enums['COMPANY_TYPE'])
			? $enums['COMPANY_TYPE'] : CCrmStatus::GetStatusList('COMPANY_TYPE');

		$type = isset($item['~COMPANY_TYPE']) ? $item['~COMPANY_TYPE'] : '';
		if($type === '' || !isset($typeList[$type]))
		{
			$item['~COMPANY_TYPE_NAME'] = $item['COMPANY_TYPE_NAME'] = '';
		}
		else
		{
			$item['~COMPANY_TYPE_NAME'] = $typeList[$type];
			$item['COMPANY_TYPE_NAME'] = htmlspecialcharsbx($item['~COMPANY_TYPE_NAME']);
		}

		$industryList = $enums && isset($enums['INDUSTRY'])
			? $enums['INDUSTRY'] : CCrmStatus::GetStatusList('INDUSTRY');

		$industry = isset($item['~INDUSTRY']) ? $item['~INDUSTRY'] : '';
		if($industry === '' || !isset($industryList[$industry]))
		{
			$item['~INDUSTRY_NAME'] = $item['INDUSTRY_NAME'] = '';
		}
		else
		{
			$item['~INDUSTRY_NAME'] = $industryList[$industry];
			$item['INDUSTRY_NAME'] = htmlspecialcharsbx($item['~INDUSTRY_NAME']);
		}

		$employeesList = $enums && isset($enums['EMPLOYEES_LIST'])
			? $enums['EMPLOYEES_LIST'] : CCrmStatus::GetStatusList('EMPLOYEES');

		$employees = isset($item['~EMPLOYEES']) ? $item['~EMPLOYEES'] : '';
		if($employees === '' || !isset($employeesList[$employees]))
		{
			$item['~EMPLOYEES_NAME'] = $item['EMPLOYEES_NAME'] = '';
		}
		else
		{
			$item['~EMPLOYEES_NAME'] = $employeesList[$employees];
			$item['EMPLOYEES_NAME'] = htmlspecialcharsbx($item['~EMPLOYEES_NAME']);
		}

		$item['~FORMATTED_REVENUE'] = CCrmCurrency::MoneyToString(
			isset($item['~REVENUE']) ? $item['~REVENUE'] : '',
			isset($item['~CURRENCY_ID']) ? $item['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID()
		);
		$item['FORMATTED_REVENUE'] = strip_tags($item['~FORMATTED_REVENUE']);

		$assignedByID = isset($item['~ASSIGNED_BY_ID']) ? intval($item['~ASSIGNED_BY_ID']) : 0;
		$item['~ASSIGNED_BY_ID'] = $item['ASSIGNED_BY_ID'] = $assignedByID;
		$item['ASSIGNED_BY_SHOW_URL'] = $assignedByID > 0  ?
			CComponentEngine::MakePathFromTemplate(
				$params['USER_PROFILE_URL_TEMPLATE'],
					array('user_id' => $assignedByID)
			) : '';

		$item['~ASSIGNED_BY_FORMATTED_NAME'] = $assignedByID > 0
			? CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($item['~ASSIGNED_BY_LOGIN']) ? $item['~ASSIGNED_BY_LOGIN'] : '',
					'NAME' => isset($item['~ASSIGNED_BY_NAME']) ? $item['~ASSIGNED_BY_NAME'] : '',
					'LAST_NAME' => isset($item['~ASSIGNED_BY_LAST_NAME']) ? $item['~ASSIGNED_BY_LAST_NAME'] : '',
					'SECOND_NAME' => isset($item['~ASSIGNED_BY_SECOND_NAME']) ? $item['~ASSIGNED_BY_SECOND_NAME'] : ''
				),
				true, false
			) : '';
		$item['ASSIGNED_BY_FORMATTED_NAME'] = htmlspecialcharsbx($item['~ASSIGNED_BY_FORMATTED_NAME']);
	}
	public static function PrepareCompanyData(&$fields)
	{
		$listImageInfo = null;
		$viewImageInfo = null;
		$logoID = isset($fields['LOGO']) ? intval($fields['LOGO']) : 0;
		if($logoID > 0)
		{
			$listImageInfo = CFile::ResizeImageGet(
				$logoID, array('width' => 32, 'height' => 32), BX_RESIZE_IMAGE_EXACT);
			$viewImageInfo = CFile::ResizeImageGet(
				$logoID, array('width' => 55, 'height' => 55), BX_RESIZE_IMAGE_EXACT);
		}
		else
		{
			$viewImageInfo = array('src' => SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_company_big.png?ver=1');
			$listImageInfo = array('src' => SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_company_small.png?ver=1');
		}

		return array(
			'ID' => $fields['~ID'],
			'TITLE' => isset($fields['~TITLE']) ? $fields['~TITLE'] : '',
			'COMPANY_TYPE' => isset($fields['~COMPANY_TYPE']) ? $fields['~COMPANY_TYPE'] : '',
			'COMPANY_TYPE_NAME' => isset($fields['~COMPANY_TYPE_NAME']) ? $fields['~COMPANY_TYPE_NAME'] : '',
			'INDUSTRY' => isset($fields['~INDUSTRY']) ? $fields['~INDUSTRY'] : '',
			'INDUSTRY_NAME' => isset($fields['~INDUSTRY_NAME']) ? $fields['~INDUSTRY_NAME'] : '',
			'EMPLOYEES' => isset($fields['~EMPLOYEES']) ? $fields['~EMPLOYEES'] : '',
			'EMPLOYEES_NAME' => isset($fields['~EMPLOYEES_NAME']) ? $fields['~EMPLOYEES_NAME'] : '',
			'REVENUE' => isset($fields['~REVENUE']) ? doubleval($fields['~REVENUE']) : 0.0,
			'ASSIGNED_BY_ID' => isset($fields['~ASSIGNED_BY_ID']) ? $fields['~ASSIGNED_BY_ID'] : '',
			'ASSIGNED_BY_FORMATTED_NAME' => isset($fields['~ASSIGNED_BY_FORMATTED_NAME']) ? $fields['~ASSIGNED_BY_FORMATTED_NAME'] : '',
			'ADDRESS' => isset($fields['~ADDRESS']) ? $fields['~ADDRESS'] : '',
			'ADDRESS_LEGAL' => isset($fields['~ADDRESS_LEGAL']) ? $fields['~ADDRESS_LEGAL'] : '',
			'BANKING_DETAILS' => isset($fields['~BANKING_DETAILS']) ? $fields['~BANKING_DETAILS'] : '',
			'COMMENTS' => isset($fields['~COMMENTS']) ? $fields['~COMMENTS'] : '',
			'DATE_CREATE' => isset($fields['~DATE_CREATE']) ? $fields['~DATE_CREATE'] : '',
			'DATE_MODIFY' => isset($fields['~DATE_MODIFY']) ? $fields['~DATE_MODIFY'] : '',
			'ASSIGNED_BY_SHOW_URL' => isset($fields['ASSIGNED_BY_SHOW_URL']) ? $fields['ASSIGNED_BY_SHOW_URL'] : '',
			'SHOW_URL' => isset($fields['SHOW_URL']) ? $fields['SHOW_URL'] : '',
			'IMAGE_ID' => $logoID,
			'LIST_IMAGE_URL' => $listImageInfo && isset($listImageInfo['src']) ? $listImageInfo['src'] : '',
			'VIEW_IMAGE_URL' => $viewImageInfo && isset($viewImageInfo['src']) ? $viewImageInfo['src'] : ''
		);
	}
	public static function PrepareImageUrl(&$fields, $fieldID, $size)
	{
		$fieldID = strval($fieldID);
		if($fieldID === '')
		{
			return '';
		}

		$width = is_array($size) && isset($size['WIDTH']) ? intval($size['WIDTH']) : 50;
		$height = is_array($size) && isset($size['HEIGHT']) ? intval($size['HEIGHT']) : 50;

		if($fieldID)
		$imageID = isset($fields[$fieldID]) ? intval($fields[$fieldID]) : 0;
		if($imageID > 0)
		{
			$info = CFile::ResizeImageGet(
				$imageID, array('width' => $width, 'height' => $height), BX_RESIZE_IMAGE_EXACT);

			return isset($info['src']) ? $info['src'] : '';
		}

		return '';
	}
	public static function PrepareCompanyImageUrl(&$fields, $size)
	{
		$url = self::PrepareImageUrl($fields, 'LOGO', $size);
		return $url !== ''
			? $url : SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_company_small.png?ver=1';
	}
	public static function PrepareContactImageUrl(&$fields, $size)
	{
		$url = self::PrepareImageUrl($fields, 'PHOTO', $size);
		return $url !== ''
			? $url : SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_contact_small.png?ver=1';
	}
	public static function PrepareLeadItem(&$item, &$params, $enums = array())
	{
		$itemID = intval($item['~ID']);

		$item['SHOW_URL'] = CComponentEngine::MakePathFromTemplate(
			$params['LEAD_SHOW_URL_TEMPLATE'],
			array('lead_id' => $itemID)
		);

		$statusList = $enums && isset($enums['STATUS_LIST'])
			? $enums['STATUS_LIST'] : self::GetStatusList('STATUS');

		$statusID = isset($item['~STATUS_ID']) ? $item['~STATUS_ID'] : '';
		if($statusID === '' || !isset($statusList[$statusID]))
		{
			$item['~STATUS_NAME'] = $item['STATUS_NAME'] = '';
		}
		else
		{
			$item['~STATUS_NAME'] = $statusList[$statusID];
			$item['STATUS_NAME'] = htmlspecialcharsbx($item['~STATUS_NAME']);
		}

		$sourceList = $enums && isset($enums['SOURCE_LIST'])
			? $enums['SOURCE_LIST'] : self::GetStatusList('SOURCE');

		$sourceID = isset($item['~SOURCE_ID']) ? $item['~SOURCE_ID'] : '';
		if($sourceID === '' || !isset($sourceList[$sourceID]))
		{
			$item['~SOURCE_NAME'] = $item['SOURCE_NAME'] = '';
		}
		else
		{
			$item['~SOURCE_NAME'] = $sourceList[$sourceID];
			$item['SOURCE_NAME'] = htmlspecialcharsbx($item['~SOURCE_NAME']);
		}

		$currencyID = isset($item['~CURRENCY_ID']) ? $item['~CURRENCY_ID'] : '';
		if($currencyID === '')
		{
			$currencyID = $item['~CURRENCY_ID'] = CCrmCurrency::GetBaseCurrencyID();
			$item['CURRENCY_ID'] = htmlspecialcharsbx($currencyID);
		}

		$item['~CURRENCY_NAME'] = CCrmCurrency::GetCurrencyName($currencyID);
		$item['CURRENCY_NAME'] = htmlspecialcharsbx($item['~CURRENCY_NAME']);

		$item['~FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString(
			isset($item['~OPPORTUNITY']) ? $item['~OPPORTUNITY'] : '',
			$currencyID
		);
		$item['FORMATTED_OPPORTUNITY'] = strip_tags($item['~FORMATTED_OPPORTUNITY']);

		$item['~FORMATTED_NAME'] = CUser::FormatName(
			$params['NAME_TEMPLATE'],
			array(
				'LOGIN' => '',
				'NAME' => isset($item['~NAME']) ? $item['~NAME'] : '',
				'LAST_NAME' => isset($item['~LAST_NAME']) ? $item['~LAST_NAME'] : '',
				'SECOND_NAME' => isset($item['~SECOND_NAME']) ? $item['~SECOND_NAME'] : ''
			),
			false, false
		);
		$item['FORMATTED_NAME'] = htmlspecialcharsbx($item['~FORMATTED_NAME']);

		$assignedByID = isset($item['~ASSIGNED_BY_ID']) ? intval($item['~ASSIGNED_BY_ID']) : 0;
		$item['~ASSIGNED_BY_ID'] = $item['ASSIGNED_BY_ID'] = $assignedByID;
		$item['ASSIGNED_BY_SHOW_URL'] = $assignedByID > 0  ?
			CComponentEngine::MakePathFromTemplate(
				$params['USER_PROFILE_URL_TEMPLATE'],
					array('user_id' => $assignedByID)
			) : '';

		$item['~ASSIGNED_BY_FORMATTED_NAME'] = $assignedByID > 0
			? CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($item['~ASSIGNED_BY_LOGIN']) ? $item['~ASSIGNED_BY_LOGIN'] : '',
					'NAME' => isset($item['~ASSIGNED_BY_NAME']) ? $item['~ASSIGNED_BY_NAME'] : '',
					'LAST_NAME' => isset($item['~ASSIGNED_BY_LAST_NAME']) ? $item['~ASSIGNED_BY_LAST_NAME'] : '',
					'SECOND_NAME' => isset($item['~ASSIGNED_BY_SECOND_NAME']) ? $item['~ASSIGNED_BY_SECOND_NAME'] : ''
				),
				true, false
			) : '';
		$item['ASSIGNED_BY_FORMATTED_NAME'] = htmlspecialcharsbx($item['~ASSIGNED_BY_FORMATTED_NAME']);
	}
	public static function PrepareLeadData(&$fields)
	{
		return array(
			'ID' => $fields['~ID'],
			'TITLE' => isset($fields['~TITLE']) ? $fields['~TITLE'] : '',
			'STATUS_ID' => isset($fields['~STATUS_ID']) ? $fields['~STATUS_ID'] : '',
			'STATUS_NAME' => isset($fields['~STATUS_NAME']) ? $fields['~STATUS_NAME'] : '',
			'SOURCE_ID' => isset($fields['~SOURCE_ID']) ? $fields['~SOURCE_ID'] : '',
			'SOURCE_NAME' => isset($fields['~SOURCE_NAME']) ? $fields['~SOURCE_NAME'] : '',
			'FORMATTED_NAME' => isset($fields['~FORMATTED_NAME']) ? $fields['~FORMATTED_NAME'] : '',
			'COMPANY_TITLE' => isset($fields['~COMPANY_TITLE']) ? $fields['~COMPANY_TITLE'] : '',
			'POST' => isset($fields['~POST']) ? $fields['~POST'] : '',
			'OPPORTUNITY' => isset($fields['~OPPORTUNITY']) ? $fields['~OPPORTUNITY'] : '',
			'FORMATTED_OPPORTUNITY' => isset($fields['FORMATTED_OPPORTUNITY']) ? $fields['FORMATTED_OPPORTUNITY'] : '',
			'ASSIGNED_BY_ID' => isset($fields['~ASSIGNED_BY_ID']) ? $fields['~ASSIGNED_BY_ID'] : '',
			'ASSIGNED_BY_FORMATTED_NAME' => isset($fields['~ASSIGNED_BY_FORMATTED_NAME']) ? $fields['~ASSIGNED_BY_FORMATTED_NAME'] : '',
			'COMMENTS' => isset($fields['~COMMENTS']) ? $fields['~COMMENTS'] : '',
			'DATE_CREATE' => isset($fields['~DATE_CREATE']) ? $fields['~DATE_CREATE'] : '',
			'DATE_MODIFY' => isset($fields['~DATE_MODIFY']) ? $fields['~DATE_MODIFY'] : '',
			'ASSIGNED_BY_SHOW_URL' => isset($fields['ASSIGNED_BY_SHOW_URL']) ? $fields['ASSIGNED_BY_SHOW_URL'] : '',
			'SHOW_URL' => isset($fields['SHOW_URL']) ? $fields['SHOW_URL'] : '',
			//'LIST_IMAGE_URL' => SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_lead_small.png?ver=1',
			'LIST_IMAGE_URL' => '',
			//'VIEW_IMAGE_URL' => SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_lead_big.png?ver=1'
			'VIEW_IMAGE_URL' => ''
		);
	}
	public static function PrepareActivityItem(&$item, &$params, $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$itemID = intval($item['ID']);

		if(!isset($item['SUBJECT']))
		{
			$item['SUBJECT'] = '';
		}

		if(!isset($item['DESCRIPTION']))
		{
			$item['DESCRIPTION'] = '';
		}

		if(!isset($item['LOCATION']))
		{
			$item['LOCATION'] = '';
		}

		$typeID = isset($item['TYPE_ID']) ? intval($item['TYPE_ID']) : CCrmActivityType::Undefined;
		$item['TYPE_ID'] = $typeID;

		$direction = isset($item['DIRECTION']) ? intval($item['DIRECTION']) : CCrmActivityDirection::Undefined;
		$item['DIRECTION'] = $direction;

		$priority = isset($item['PRIORITY']) ? intval($item['PRIORITY']) : CCrmActivityPriority::None;
		$item['PRIORITY'] = $priority;
		$item['IS_IMPORTANT'] = $priority === CCrmActivityPriority::High;

		$completed = isset($item['COMPLETED']) ? $item['COMPLETED'] === 'Y' : false;
		$item['COMPLETED'] = $completed ? 'Y' : 'N';

		if($typeID === CCrmActivityType::Task)
		{
			$taskID = isset($item['ASSOCIATED_ENTITY_ID']) ? intval($item['ASSOCIATED_ENTITY_ID']) : 0;
			$item['SHOW_URL'] = $taskID > 0 && isset($params['TASK_SHOW_URL_TEMPLATE'])
				? CComponentEngine::MakePathFromTemplate(
					$params['TASK_SHOW_URL_TEMPLATE'],
					array(
						'user_id' => isset($params['USER_ID']) ? $params['USER_ID'] : CCrmSecurityHelper::GetCurrentUserID(),
						'task_id' => $taskID
					)
				) : '';
			$item['DEAD_LINE'] = isset($item['DEADLINE'])
				? $item['DEADLINE'] : (isset($item['END_TIME']) ? $item['END_TIME'] : '');
		}
		else
		{
			if(isset($params['ACTIVITY_SHOW_URL_TEMPLATE']))
			{
				$item['SHOW_URL'] = CComponentEngine::makePathFromTemplate(
					$params['ACTIVITY_SHOW_URL_TEMPLATE'],
					array('activity_id' => $itemID)
				);
			}
			$item['DEAD_LINE'] = isset($item['DEADLINE'])
				? $item['DEADLINE'] : (isset($item['START_TIME']) ? $item['START_TIME'] : '');
		}

		//OWNER_TITLE
		$ownerTitle = '';
		$ownerID = isset($item['OWNER_ID']) ? intval($item['OWNER_ID']) : 0;
		$item['OWNER_ID'] = $ownerID;

		$ownerTypeID = isset($item['OWNER_TYPE_ID']) ? intval($item['OWNER_TYPE_ID']) : 0;
		$item['OWNER_TYPE_ID'] = $ownerTypeID;

		if($ownerID > 0 && $ownerTypeID > 0)
		{
			$ownerTitle = CCrmOwnerType::GetCaption($ownerTypeID, $ownerID);
		}

		$item['OWNER_TITLE'] = $ownerTitle;

		//OWNER_SHOW_URL
		$ownerShowUrl = '';
		if($ownerID > 0)
		{
			if($ownerTypeID === CCrmOwnerType::Lead)
			{
				$ownerShowUrl = isset($params['LEAD_SHOW_URL_TEMPLATE']) ? CComponentEngine::makePathFromTemplate(
					$params['LEAD_SHOW_URL_TEMPLATE'],
					array('lead_id' => $ownerID)
				) : '';
			}
			elseif($ownerTypeID === CCrmOwnerType::Contact)
			{
				$ownerShowUrl = isset($params['CONTACT_SHOW_URL_TEMPLATE']) ? CComponentEngine::makePathFromTemplate(
					$params['CONTACT_SHOW_URL_TEMPLATE'],
					array('contact_id' => $ownerID)
				) : '';
			}
			elseif($ownerTypeID === CCrmOwnerType::Company)
			{
				$ownerShowUrl = isset($params['COMPANY_SHOW_URL_TEMPLATE']) ? CComponentEngine::makePathFromTemplate(
					$params['COMPANY_SHOW_URL_TEMPLATE'],
					array('company_id' => $ownerID)
				) : '';
			}
			elseif($ownerTypeID === CCrmOwnerType::Deal)
			{
				$ownerShowUrl = isset($params['DEAL_SHOW_URL_TEMPLATE']) ? CComponentEngine::makePathFromTemplate(
					$params['DEAL_SHOW_URL_TEMPLATE'],
					array('deal_id' => $ownerID)
				) : '';
			}
		}
		$item['OWNER_SHOW_URL'] = $ownerShowUrl;

		//IS_EXPIRED
		if($item['COMPLETED'] === 'Y')
		{
			$item['IS_EXPIRED'] = false;
		}
		else
		{
			$time = isset($item['DEAD_LINE']) ? MakeTimeStamp($item['DEAD_LINE']) : 0;
			$item['IS_EXPIRED'] = $time !== 0 && $time <= (time() + CTimeZone::GetOffset());
		}

		$responsibleID = isset($item['RESPONSIBLE_ID']) ? intval($item['RESPONSIBLE_ID']) : 0;
		$item['RESPONSIBLE_ID'] = $responsibleID;
		$item['RESPONSIBLE_SHOW_URL'] = $responsibleID > 0  ?
			CComponentEngine::MakePathFromTemplate(
				$params['USER_PROFILE_URL_TEMPLATE'],
					array('user_id' => $responsibleID)
			) : '';

		$item['RESPONSIBLE_FORMATTED_NAME'] = $responsibleID > 0 && isset($params['NAME_TEMPLATE'])
			? CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($item['RESPONSIBLE_LOGIN']) ? $item['RESPONSIBLE_LOGIN'] : '',
					'NAME' => isset($item['RESPONSIBLE_NAME']) ? $item['RESPONSIBLE_NAME'] : '',
					'LAST_NAME' => isset($item['RESPONSIBLE_LAST_NAME']) ? $item['RESPONSIBLE_LAST_NAME'] : '',
					'SECOND_NAME' => isset($item['RESPONSIBLE_SECOND_NAME']) ? $item['RESPONSIBLE_SECOND_NAME'] : ''
				),
				true, false
			) : '';

		//COMMUNICATIONS
		if($itemID > 0 && isset($options['ENABLE_COMMUNICATIONS'])
			&& $options['ENABLE_COMMUNICATIONS']
			&& !isset($item['COMMUNICATIONS']))
		{
			$item['COMMUNICATIONS'] = CCrmActivity::GetCommunications($itemID);
		}

		$storageTypeID = isset($item['STORAGE_TYPE_ID']) ? intval($item['STORAGE_TYPE_ID']) : CCrmActivityStorageType::Undefined;
		if($storageTypeID === CCrmActivityStorageType::Undefined || !CCrmActivityStorageType::IsDefined($storageTypeID))
		{
			$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
		}
		$item['STORAGE_TYPE_ID'] = $storageTypeID;

		$item['FILES'] = array();
		$item['DISK_FILES'] = array();
		$item['WEBDAV_ELEMENTS'] = array();

		if(isset($options['ENABLE_FILES']) && $options['ENABLE_FILES'])
		{
			CCrmActivity::PrepareStorageElementIDs($item);
			CCrmActivity::PrepareStorageElementInfo($item);
		}
	}
	public static function PrepareActivityData(&$fields)
	{
		$typeID = isset($fields['TYPE_ID']) ? intval($fields['TYPE_ID']) : CCrmActivityType::Undefined;
		$direction = isset($fields['DIRECTION']) ? intval($fields['DIRECTION']) : CCrmActivityDirection::Undefined;
		$isCompleted = $fields['COMPLETED'] === 'Y';

		$imageFileName = '';
		if($typeID === CCrmActivityType::Call)
		{
			$imageFileName = $direction === CCrmActivityDirection::Incoming ? 'call_in' : 'call_out';
		}
		elseif($typeID === CCrmActivityType::Email)
		{
			$imageFileName = $direction === CCrmActivityDirection::Incoming ? 'email_in' : 'email_out';
		}
		elseif($typeID === CCrmActivityType::Meeting)
		{
			$imageFileName = 'cont';
		}
		elseif($typeID === CCrmActivityType::Task)
		{
			$imageFileName = 'check';
		}

		if($imageFileName !== '' && $isCompleted)
		{
			$imageFileName .= '_disabled';
		}

		$imageUrl = $imageFileName !== ''
			? SITE_DIR.'bitrix/templates/mobile_app/images/crm/'.$imageFileName.'.png?ver=1'
			: '';

		$data = array(
			'ID' => $fields['ID'],
			'TYPE_ID' => $fields['TYPE_ID'],
			'OWNER_ID' => $fields['OWNER_ID'],
			'OWNER_TYPE' => CCrmOwnerType::ResolveName($fields['OWNER_TYPE_ID']),
			'SUBJECT' => isset($fields['SUBJECT']) ? $fields['SUBJECT'] : '',
			'DESCRIPTION' => isset($fields['DESCRIPTION']) ? $fields['DESCRIPTION'] : '',
			'LOCATION' => isset($fields['LOCATION']) ? $fields['LOCATION'] : '',
			'START_TIME' => isset($fields['START_TIME']) ? CCrmComponentHelper::RemoveSeconds(ConvertTimeStamp(MakeTimeStamp($fields['START_TIME']), 'FULL', SITE_ID)) : '',
			'END_TIME' => isset($fields['END_TIME']) ? CCrmComponentHelper::RemoveSeconds(ConvertTimeStamp(MakeTimeStamp($fields['END_TIME']), 'FULL', SITE_ID)) : '',
			'DEAD_LINE' => isset($fields['DEAD_LINE']) ? CCrmComponentHelper::RemoveSeconds(ConvertTimeStamp(MakeTimeStamp($fields['DEAD_LINE']), 'FULL', SITE_ID)) : '',
			'COMPLETED' => isset($fields['COMPLETED']) ? $fields['COMPLETED'] === 'Y' : false,
			'PRIORITY' => isset($fields['PRIORITY']) ? intval($fields['PRIORITY']) : CCrmActivityPriority::None,
			'IS_IMPORTANT' => isset($fields['IS_IMPORTANT']) ? $fields['IS_IMPORTANT'] : false,
			'IS_EXPIRED' => isset($fields['IS_EXPIRED']) ? $fields['IS_EXPIRED'] : false,
			'OWNER_TITLE' => isset($fields['OWNER_TITLE']) ? $fields['OWNER_TITLE'] : '',
			'SHOW_URL' => isset($fields['SHOW_URL']) ? $fields['SHOW_URL'] : '',
			'LIST_IMAGE_URL' => $imageUrl,
			'VIEW_IMAGE_URL' => $imageUrl,
			'STORAGE_TYPE_ID' => $fields['STORAGE_TYPE_ID'],
			'FILES' => isset($fields['FILES']) ? $fields['FILES'] : array(),
			'WEBDAV_ELEMENTS' => isset($fields['WEBDAV_ELEMENTS']) ? $fields['WEBDAV_ELEMENTS'] : array()
		);

		//COMMUNICATIONS
		if(isset($fields['COMMUNICATIONS']))
		{
			$communications = $fields['COMMUNICATIONS'];
			foreach($communications as &$comm)
			{
				CCrmActivity::PrepareCommunicationInfo($comm);
				$comm['ENTITY_TYPE'] = CCrmOwnerType::ResolveName($comm['ENTITY_TYPE_ID']);
				unset($comm['ENTITY_TYPE_ID']);

				if(isset($comm['ENTITY_SETTINGS']))
				{
					// entity settings is useless for client
					unset($comm['ENTITY_SETTINGS']);
				}
			}
			unset($comm);
			$data['COMMUNICATIONS'] = $communications;
		}

		return $data;
	}
	public static function PrepareEventItem(&$item, &$params)
	{
		if(isset($item['EVENT_TEXT_1']))
		{
			$item['EVENT_TEXT_1'] = strip_tags($item['EVENT_TEXT_1'], '<br>');
		}

		if(isset($item['EVENT_TEXT_2']))
		{
			$item['EVENT_TEXT_2'] = strip_tags($item['EVENT_TEXT_2'], '<br>');
		}

		$authorID = isset($item['CREATED_BY_ID']) ? intval($item['CREATED_BY_ID']) : 0;
		$item['CREATED_BY_ID'] = $authorID;
		$item['CREATED_BY_SHOW_URL'] = $authorID > 0  ?
			CComponentEngine::MakePathFromTemplate(
				$params['USER_PROFILE_URL_TEMPLATE'],
					array('user_id' => $authorID)
			) : '';

		$item['CREATED_BY_FORMATTED_NAME'] = $authorID > 0
			? CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($item['CREATED_BY_LOGIN']) ? $item['CREATED_BY_LOGIN'] : '',
					'NAME' => isset($item['CREATED_BY_NAME']) ? $item['CREATED_BY_NAME'] : '',
					'LAST_NAME' => isset($item['CREATED_BY_LAST_NAME']) ? $item['CREATED_BY_LAST_NAME'] : '',
					'SECOND_NAME' => isset($item['CREATED_BY_SECOND_NAME']) ? $item['CREATED_BY_SECOND_NAME'] : ''
				),
				true, false
			) : '';
	}
	public static function PrepareEventData(&$fields)
	{
		return array(
			'ID' => $fields['ID'],
			'EVENT_NAME' => isset($fields['EVENT_NAME']) ? $fields['EVENT_NAME'] : '',
			'EVENT_TEXT_1' => isset($fields['EVENT_TEXT_1']) ? $fields['EVENT_TEXT_1'] : '',
			'EVENT_TEXT_2' => isset($fields['EVENT_TEXT_2']) ? $fields['EVENT_TEXT_2'] : '',
			'CREATED_BY_ID' => isset($fields['CREATED_BY_ID']) ? $fields['CREATED_BY_ID'] : '',
			'CREATED_BY_FORMATTED_NAME' => isset($fields['CREATED_BY_FORMATTED_NAME']) ? $fields['CREATED_BY_FORMATTED_NAME'] : '',
			'DATE_CREATE' => isset($fields['DATE_CREATE']) ? ConvertTimeStamp(MakeTimeStamp($fields['DATE_CREATE']), 'SHORT', SITE_ID) : ''
		);
	}
	public static function PrepareInvoiceEventItem(&$item, &$params, &$entity, &$enums)
	{
		$types = isset($enums['EVENT_TYPES']) ? $enums['EVENT_TYPES'] : array();

		$ID = isset($item['ID']) ? intval($item['ID']) : 0;
		$item['ID'] = $ID;

		if(!isset($item['DATE_CREATE']))
		{
			$item['DATE_CREATE'] = '';
		}

		$type = isset($item['TYPE']) ? $item['TYPE'] : '';
		$item['NAME'] = isset($types[$type]) ? $types[$type] : $type;

		if(!isset($item['DATA']))
		{
			$item['DESCRIPTION_HTML'] = '';
		}
		else
		{
			$infoData = $entity->GetRecordDescription($type, $item['DATA']);
			$descr = isset($infoData['INFO']) ? strip_tags($infoData['INFO'], '<br>') : '';
			if(strlen($descr) <= 128)
			{
				$item['DESCRIPTION_HTML'] = $descr;
			}
			else
			{
				$cutWrapperID = "invoice_event_descr_cut_{$ID}";
				$fullWrapperID = "invoice_event_descr_full_{$ID}";

				$item['DESCRIPTION_HTML'] = '<div id="'.$cutWrapperID.'">'
					.substr($descr, 0, 128).'...<a href="#more" onclick="BX(\''.$cutWrapperID.'\').style.display=\'none\'; BX(\''.$fullWrapperID.'\').style.display=\'\'; return false;">'
					.GetMessage('CRM_EVENT_DESC_MORE').'</a></div>'
					.'<div id="'.$fullWrapperID.'" style="display:none;">'.$descr.'</div>';
			}
		}

		$authorID = isset($item['USER_ID']) ? intval($item['USER_ID']) : 0;
		$item['USER_ID'] = $authorID;

		$item['USER_FORMATTED_NAME'] = $authorID > 0
			? CUser::FormatName(
				$params['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($item['USER_LOGIN']) ? $item['USER_LOGIN'] : '',
					'NAME' => isset($item['USER_NAME']) ? $item['USER_NAME'] : '',
					'LAST_NAME' => isset($item['USER_LAST_NAME']) ? $item['USER_LAST_NAME'] : '',
					'SECOND_NAME' => isset($item['USER_SECOND_NAME']) ? $item['USER_SECOND_NAME'] : ''
				),
				true, false
			) : '';
	}
	public static function PrepareInvoiceEventData(&$fields)
	{
		return array(
			'ID' => $fields['ID'],
			'TYPE' => $fields['TYPE'],
			'NAME' => $fields['NAME'],
			'DESCRIPTION_HTML' => $fields['DESCRIPTION_HTML'],
			'DATE_CREATE' => ConvertTimeStamp(MakeTimeStamp($fields['DATE_CREATE']), 'SHORT', SITE_ID),
			'USER_ID' => $fields['USER_ID'],
			'USER_FORMATTED_NAME' => $fields['USER_FORMATTED_NAME']
		);
	}
	public static function PrepareProductItem(&$item, &$params)
	{
		$sectionID = $item['~SECTION_ID'] = isset($item['SECTION_ID']) ? intval($item['SECTION_ID']) : 0;
		if($sectionID <= 0)
		{
			$item['~SECTION_NAME'] = $item['SECTION_NAME'] = '';
		}
		else
		{
			$sections = isset($params['SECTIONS']) ? $params['SECTIONS'] : array();
			$item['~SECTION_NAME'] = isset($sections[$sectionID]) ? $sections[$sectionID]['NAME'] : '';
			$item['SECTION_NAME'] = htmlspecialcharsbx($item['~SECTION_NAME']);
		}

		$price = $item['~PRICE'] = isset($item['~PRICE']) ? doubleval($item['~PRICE']) : 0.0;

		$srcCurrencyID = $item['~CURRENCY_ID'] = isset($item['~CURRENCY_ID']) ? $item['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
		$dstCurrencyID = isset($params['CURRENCY_ID']) ? $params['CURRENCY_ID'] : '';
		if($dstCurrencyID === '')
		{
			$dstCurrencyID = $srcCurrencyID;
		}

		if($dstCurrencyID !== $srcCurrencyID)
		{
			$item['~CURRENCY_ID'] = $dstCurrencyID;
			$item['CURRENCY_ID'] = htmlspecialcharsbx($dstCurrencyID);

			$price = CCrmCurrency::ConvertMoney($price, $srcCurrencyID, $dstCurrencyID);
		}
		$item['FORMATTED_PRICE'] = CCrmCurrency::MoneyToString($price, $dstCurrencyID);
	}
	public static function PrepareProductData(&$fields)
	{
		return array(
			'ID' => $fields['~ID'],
			'NAME' => $fields['~NAME'],
			'PRICE' => $fields['~PRICE'],
			'CURRENCY_ID' => $fields['~CURRENCY_ID'],
			'SECTION_ID' => $fields['~SECTION_ID'],
			'SECTION_NAME' => $fields['SECTION_NAME'],
			'FORMATTED_PRICE' => $fields['FORMATTED_PRICE']
		);
	}
	public static function PrepareProductSectionItem(&$item, &$params)
	{
		$item['PRODUCT_SECTION_URL'] = isset($params['PRODUCT_SECTION_URL_TEMPLATE'])
			? CComponentEngine::MakePathFromTemplate(
				$params['PRODUCT_SECTION_URL_TEMPLATE'],
				array('section_id' => $item['~ID']))
			: '';
	}
	public static function PrepareProductSectionData(&$fields)
	{
		return array(
			'ID' => $fields['~ID'],
			'NAME' => $fields['~NAME'],
			'PRODUCT_SECTION_URL' => $fields['PRODUCT_SECTION_URL']
		);
	}
	public static function RenderProgressBar($params)
	{
		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? intval($params['ENTITY_TYPE_ID']) : 0;
		//$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);

		$infos = isset($params['INFOS']) ? $params['INFOS'] : null;
		if(!is_array($infos) || empty($infos))
		{
			if($entityTypeID === CCrmOwnerType::Lead)
			{
				if(!self::$LEAD_STATUSES)
				{
					self::$LEAD_STATUSES = CCrmStatus::GetStatus('STATUS');
				}
				$infos = self::$LEAD_STATUSES;
			}
			elseif($entityTypeID === CCrmOwnerType::Deal)
			{
				if(!self::$DEAL_STAGES)
				{
					self::$DEAL_STAGES = CCrmStatus::GetStatus('DEAL_STAGE');
				}
				$infos = self::$DEAL_STAGES;
			}
			elseif($entityTypeID === CCrmOwnerType::Invoice)
			{
				if(!self::$INVOICE_STATUSES)
				{
					self::$INVOICE_STATUSES = CCrmStatus::GetStatus('INVOICE_STATUS');
				}
				$infos = self::$INVOICE_STATUSES;
			}
		}

		if(!is_array($infos) || empty($infos))
		{
			return;
		}

		$currentInfo = null;
		$currentID = isset($params['CURRENT_ID']) ? $params['CURRENT_ID'] : '';
		if($currentID !== '' && isset($infos[$currentID]))
		{
			$currentInfo = $infos[$currentID];
		}
		$currentSort = is_array($currentInfo) && isset($currentInfo['SORT']) ? intval($currentInfo['SORT']) : -1;

		$finalID = isset($params['FINAL_ID']) ? $params['FINAL_ID'] : '';
		if($finalID === '')
		{
			if($entityTypeID === CCrmOwnerType::Lead)
			{
				$finalID = 'CONVERTED';
			}
			elseif($entityTypeID === CCrmOwnerType::Deal)
			{
				$finalID = 'WON';
			}
			elseif($entityTypeID === CCrmOwnerType::Invoice)
			{
				$finalID = 'P';
			}
		}

		$finalInfo = null;
		if($finalID !== '' && isset($infos[$finalID]))
		{
			$finalInfo = $infos[$finalID];
		}
		$finalSort = is_array($finalInfo) && isset($finalInfo['SORT']) ? intval($finalInfo['SORT']) : -1;

		$layout = isset($params['LAYOUT']) ? strtolower($params['LAYOUT']) : 'small';

		$wrapperClass = "crm-list-stage-bar-{$layout}";
		if($currentSort === $finalSort)
		{
			$wrapperClass .= ' crm-list-stage-end-good';
		}
		elseif($currentSort > $finalSort)
		{
			$wrapperClass .= ' crm-list-stage-end-bad';
		}

		//$prefix = isset($params['PREFIX']) ? $params['PREFIX'] : '';
		//$entityID = isset($params['ENTITY_ID']) ? intval($params['ENTITY_ID']) : 0;

		//$controlID = $entityTypeName !== '' && $entityID > 0
		//	? "{$prefix}{$entityTypeName}_{$entityID}" : uniqid($prefix);
		$wrapperID = isset($params['WRAPPER_ID']) ? $params['WRAPPER_ID'] : '';
		$tableClass = "crm-list-stage-bar-table-{$layout}";

		echo '<div class="', $wrapperClass,'" style="width:89%;"',
			($wrapperID !== '' ? ' id="'.htmlspecialcharsbx($wrapperID).'"' : ''),
			'><table class="', $tableClass, '"><tbody><tr>';

		foreach($infos as &$info)
		{
			$ID = isset($info['STATUS_ID']) ? $info['STATUS_ID'] : '';
			$sort = isset($info['SORT']) ? intval($info['SORT']) : 0;
			if($sort > $finalSort)
			{
				break;
			}

			echo '<td class="crm-list-stage-bar-part',
				($sort <= $currentSort ? ' crm-list-stage-passed' : ''), '">',
				'<div class="crm-list-stage-bar-block" data-progress-step-id="'.htmlspecialcharsbx(strtolower($ID)).'"><div class="crm-list-stage-bar-btn"></div></div>',
				'<input class="crm-list-stage-bar-block-sort" type="hidden" value="', $sort ,'" />',
			'</td>';
		}
		unset($info);

		echo '</tr></tbody></table></div>';
	}
	public static function PrepareCalltoUrl($value)
	{
		return 'tel:'.$value;
	}
	public static function PrepareMailtoUrl($value)
	{
		return 'mailto:'.$value;
	}
	public static function PrepareCalltoParams($params)
	{
		$result = array(
			'URL' => '',
			'SCRIPT' => ''
		);

		$multiFields = isset($params['FM']) ? $params['FM'] : array();
		$c = count($multiFields['PHONE']);
		if($c === 0)
		{
			return $result;
		}


		$commListUrlTemplate = isset($params['COMMUNICATION_LIST_URL_TEMPLATE']) ? $params['COMMUNICATION_LIST_URL_TEMPLATE'] : '';
		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? intval($params['ENTITY_TYPE_ID']) : 0;
		$entityID = isset($params['ENTITY_ID']) ? intval($params['ENTITY_ID']) : 0;

		if($c === 1)
		{
			$result['URL'] = self::PrepareCalltoUrl($multiFields['PHONE'][0]['VALUE']);
		}
		elseif($commListUrlTemplate !== '' && $entityTypeID > 0 && $entityID > 0)
		{
			$url = CComponentEngine::MakePathFromTemplate(
				$commListUrlTemplate,
				array(
					'entity_type_id' => $entityTypeID,
					'entity_id' => $entityID,
					'type_id' => 'PHONE'
				)
			);

			$result['SCRIPT'] = 'BX.CrmMobileContext.redirect({ url: \''.CUtil::JSEscape($url).'\', pageid:\'crm_phone_list_'.$entityTypeID.'_'.$entityID.'\' }); return false;';
		}

		return $result;
	}
	public static function PrepareMailtoParams($params)
	{
		$result = array(
			'URL' => '',
			'SCRIPT' => ''
		);

		$multiFields = isset($params['FM']) ? $params['FM'] : array();
		$c = count($multiFields['EMAIL']);
		if($c === 0)
		{
			return $result;
		}


		$commListUrlTemplate = isset($params['COMMUNICATION_LIST_URL_TEMPLATE']) ? $params['COMMUNICATION_LIST_URL_TEMPLATE'] : '';
		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? intval($params['ENTITY_TYPE_ID']) : 0;
		$entityID = isset($params['ENTITY_ID']) ? intval($params['ENTITY_ID']) : 0;

		if($c === 1)
		{
			$result['URL'] = self::PrepareMailtoUrl($multiFields['EMAIL'][0]['VALUE']);
		}
		elseif($commListUrlTemplate !== '' && $entityTypeID > 0 && $entityID > 0)
		{
			$url = CComponentEngine::MakePathFromTemplate(
				$commListUrlTemplate,
				array(
					'entity_type_id' => $entityTypeID,
					'entity_id' => $entityID,
					'type_id' => 'EMAIL'
				)
			);

			$result['SCRIPT'] = 'BX.CrmMobileContext.redirect({ url: \''.CUtil::JSEscape($url).'\' }); return false;';
		}

		return $result;
	}
	public static function PrepareCut($src, &$text, &$cut)
	{
		$text = '';
		$cut = '';
		if($src === '' || preg_match('/^\s*(\s*<br[^>]*>\s*)+\s*$/i', $src) === 1)
		{
			return false;
		}

		$text = $src;
		if(strlen($text) > 128)
		{
			$cut = substr($text, 128);
			$text = substr($text, 0, 128);
		}

		return true;
	}
	public static function GetContactViewImageStub()
	{
		return SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_contact_big.png?ver=1';
	}
	public static function GetCompanyViewImageStub()
	{
		return SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_company_big.png?ver=1';
	}
	public static function GetLeadViewImageStub()
	{
		return SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_lead_big.png?ver=1';
	}
	public static function GetLeadListImageStub()
	{
		return SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_lead_small.png?ver=1';
	}
	public static function GetUploadedFileIDs($ownerTypeID, $ownerID)
	{
		if(!CCrmOwnerType::IsDefined($ownerTypeID))
		{
			return array();
		}

		$key = 'CRM_MBL_'.CCrmOwnerType::ResolveName($ownerTypeID).'_'.$ownerID.'_FILES';
		return isset($_SESSION[$key]) && is_array($_SESSION[$key]) ? $_SESSION[$key] : array();
	}
	public static function TryUploadFile(&$result, $options = array())
	{
		//Options initialization -->
		$ownerTypeID = isset($options['OWNER_TYPE_ID']) ? intval($options['OWNER_TYPE_ID']) : CCrmOwnerType::Undefined;
		if($ownerTypeID !== CCrmOwnerType::Undefined && !CCrmOwnerType::IsDefined($ownerTypeID))
		{
			$ownerTypeID = CCrmOwnerType::Undefined;
		}
		$ownerID = isset($options['OWNER_ID']) ? max(intval($options['OWNER_ID']), 0) : 0;
		$scope = isset($options['SCOPE']) ? strtoupper($options['SCOPE']) : '';
		if(!in_array($scope, array('I', 'A', 'F'), true))
		{
			$scope = '';
		}
		$extensions = isset($options['EXTENSIONS']) && is_array($options['EXTENSIONS'])
			? $options['EXTENSIONS'] : array();

		$maxFileSize = isset($options['MAX_FILE_SIZE']) ? max(intval($options['MAX_FILE_SIZE']), 0) : 0;
		//<-- Options initialization
		if(!is_array($result))
		{
			$result = array();
		}

		$file = is_array($_FILES) && isset($_FILES['file']) ? $_FILES['file'] : null;
		if(!is_array($file))
		{
			$result['ERROR_MESSAGE'] = 'No files';
			return false;
		}
		$file['MODULE_ID'] = 'crm';

		if ($scope === 'I')
		{
			$error = CFile::CheckImageFile($file, $maxFileSize, 0, 0);
		}
		elseif ($scope === 'F')
		{
			$error = CFile::CheckFile($file, $maxFileSize, false, implode(',', $extensions));
		}
		else
		{
			$error = CFile::CheckFile($file, $maxFileSize, false, false);
		}
		$isValid = !(is_string($error) && $error !== '');

		if(!$isValid)
		{
			$result['ERROR_MESSAGE'] = $error;
			return false;
		}

		$fileID = CFile::SaveFile($file, 'crm');
		if(!is_int($fileID) || $fileID <= 0)
		{
			$result['ERROR_MESSAGE'] = 'General error.';
			return false;
		}

		if($ownerTypeID != CCrmOwnerType::Undefined)
		{
			$key = 'CRM_MBL_'.CCrmOwnerType::ResolveName($ownerTypeID).'_'.$ownerID.'_FILES';
			if (!isset($_SESSION[$key]))
			{
				$_SESSION[$key] = array();
			}

			$_SESSION[$key][] = $fileID;
		}
		$result['FILE_ID'] = $fileID;
		return true;
	}
	public static function SaveRecentlyUsedLocation($locationID, $userID = 0)
	{
		$locationID = intval($locationID);
		if($locationID <= 0)
		{
			return false;
		}

		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$key = strval($locationID);

		$s = CUserOptions::GetOption('m_crm_invoice', 'locations', '', $userID);
		$ary = $s !== '' ? explode(',', $s) : array();
		$qty = count($ary);
		if($qty > 0)
		{
			if(in_array($key, $ary, true))
			{
				return true;
			}

			if($qty >= 10)
			{
				array_shift($ary);
			}
		}
		$ary[] = $key;
		CUserOptions::SetOption('m_crm_invoice', 'locations', implode(',', $ary));
		return true;
	}
	public static function GetRecentlyUsedLocations($userID = 0)
	{
		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$s = CUserOptions::GetOption('m_crm_invoice', 'locations', '', $userID);
		$ary = $s !== '' ? explode(',', $s) : array();
		$qty = count($ary);
		for($i = 0; $i < $qty; $i++)
		{
			$ary[$i] = intval($ary[$i]);
		}
		return $ary;
	}
}
