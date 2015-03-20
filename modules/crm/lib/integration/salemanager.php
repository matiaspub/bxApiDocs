<?php
namespace Bitrix\Crm\Integration;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
//use Bitrix\Main;
class SaleManager
{
	public static function ensureQuotePaySystemsCreated()
	{
		if(!Loader::includeModule('sale'))
		{
			return;
		}

		$siteID = '';
		$languageID = '';

		$dbSites = \CSite::GetList($by = 'sort', $order = 'desc', array('DEFAULT' => 'Y', 'ACTIVE' => 'Y'));
		$defaultSite = is_object($dbSites) ? $dbSites->Fetch() : null;
		if(is_array($defaultSite))
		{
			$siteID = $defaultSite['LID'];
			$languageID = $defaultSite['LANGUAGE_ID'];
		}

		if($siteID === '')
		{
			$siteID = 's1';
		}

		if($languageID === '')
		{
			$languageID = 'ru';
		}

		$paySysName = "quote_{$languageID}";
		$paySystems = array();

		$customPaySystemPath = \COption::GetOptionString('sale', 'path2user_ps_files', '');
		if($customPaySystemPath === '')
		{
			$customPaySystemPath = BX_ROOT.'/php_interface/include/sale_payment/';
		}

		$personTypeIDs = \CCrmPaySystem::getPersonTypeIDs();
		if(isset($personTypeIDs['COMPANY']))
		{
			$paySystems[] = array(
				'NAME' => Loc::getMessage('CRM_PS_QUOTE_COMPANY', null, $languageID),
				'SORT' => 200,
				'DESCRIPTION' => '',
				'CODE_TEMP' => $paySysName,
				'ACTION' => array(
					array(
						'PERSON_TYPE_ID' => $personTypeIDs['COMPANY'],
						'NAME' => Loc::getMessage('CRM_PS_QUOTE_COMPANY', null, $languageID),
						'ACTION_FILE' => "$customPaySystemPath{$paySysName}",
						'RESULT_FILE' => '',
						'NEW_WINDOW' => 'Y',
						'PARAMS' =>
							serialize(
								array(
									'DATE_INSERT' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_BILL_DATE'),
									'DATE_PAY_BEFORE' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_PAY_BEFORE'),
									'BUYER_NAME' => array('TYPE' => 'PROPERTY', 'VALUE' => 'COMPANY'),
									'BUYER_INN' => array('TYPE' => 'PROPERTY', 'VALUE' => 'INN'),
									'BUYER_ADDRESS' => array('TYPE' => 'PROPERTY', 'VALUE' => 'COMPANY_ADR'),
									'BUYER_PHONE' => array('TYPE' => 'PROPERTY', 'VALUE' => 'PHONE'),
									'BUYER_FAX' => array('TYPE' => 'PROPERTY', 'VALUE' => 'FAX'),
									'BUYER_PAYER_NAME' => array('TYPE' => 'PROPERTY', 'VALUE' => 'CONTACT_PERSON'),
									'COMMENT1' => array('TYPE' => 'ORDER', 'VALUE' => 'USER_DESCRIPTION')
								)
							),
						'HAVE_PAYMENT' => 'Y',
						'HAVE_ACTION' => 'N',
						'HAVE_RESULT' => 'N',
						'HAVE_PREPAY' => 'N',
						'HAVE_RESULT_RECEIVE' => 'N'
					)
				)
			);
		}

		if(isset($personTypeIDs['CONTACT']))
		{
			$paySystems[] = array(
				'NAME' => Loc::getMessage('CRM_PS_QUOTE_CONTACT', null, $languageID),
				'SORT' => 300,
				'DESCRIPTION' => '',
				'CODE_TEMP' => $paySysName,
				'ACTION' => array(
					array(
						'PERSON_TYPE_ID' => $personTypeIDs['CONTACT'],
						'NAME' => Loc::getMessage('CRM_PS_QUOTE_CONTACT', null, $languageID),
						'ACTION_FILE' => "$customPaySystemPath{$paySysName}",
						'RESULT_FILE' => '',
						'NEW_WINDOW' => 'Y',
						'PARAMS' => serialize(
							array(
								'DATE_INSERT' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_BILL_DATE'),
								'DATE_PAY_BEFORE' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_PAY_BEFORE'),
								'BUYER_NAME' => array('TYPE' => 'PROPERTY', 'VALUE' => 'FIO'),
								'BUYER_INN' => array('TYPE' => 'PROPERTY', 'VALUE' => 'INN'),
								'BUYER_ADDRESS' => array('TYPE' => 'PROPERTY', 'VALUE' => 'ADDRESS'),
								'BUYER_PHONE' => array('TYPE' => 'PROPERTY', 'VALUE' => 'PHONE'),
								'BUYER_FAX' => array('TYPE' => '', 'VALUE' => ''),
								'BUYER_PAYER_NAME' => array('TYPE' => 'PROPERTY', 'VALUE' => 'FIO'),
								'COMMENT1' => array('TYPE' => 'ORDER', 'VALUE' => 'USER_DESCRIPTION')
							)
						),
						'HAVE_PAYMENT' => 'Y',
						'HAVE_ACTION' => 'N',
						'HAVE_RESULT' => 'N',
						'HAVE_PREPAY' => 'N',
						'HAVE_RESULT_RECEIVE' => 'N'
					)
				)
			);
		}

		$currencyID = \CCrmCurrency::GetBaseCurrencyID();
		foreach($paySystems as $paySystem)
		{
			$dbSalePaySystem = \CSalePaySystem::GetList(
				array(),
				array('LID' => $siteID, 'NAME' => $paySystem['NAME']),
				false,
				false,
				array('ID')
			);

			if(!$dbSalePaySystem->Fetch())
			{
				$paySystemID = \CSalePaySystem::Add(
					array(
						'NAME' => $paySystem['NAME'],
						'DESCRIPTION' => $paySystem['DESCRIPTION'],
						'SORT' => $paySystem['SORT'],
						'LID' => $siteID,
						'CURRENCY' => $currencyID,
						'ACTIVE' => 'Y'
					)
				);

				if($paySystemID > 0)
				{
					foreach($paySystem['ACTION'] as &$action)
					{
						$action['PAY_SYSTEM_ID'] = $paySystemID;
						\CSalePaySystemAction::Add($action);
					}
					unset($action);
				}
			}
		}
		unset($paySystem);
	}

	public static function createVatZero()
	{
		\Bitrix\Main\Config\Option::set('crm', 'check_vat_zero', '0', '');
		if(!Loader::includeModule('catalog'))
		{
			return;
		}

		$siteID = '';
		$languageID = '';

		$dbSites = \CSite::GetList($by = 'sort', $order = 'desc', array('DEFAULT' => 'Y', 'ACTIVE' => 'Y'));
		$defaultSite = is_object($dbSites) ? $dbSites->Fetch() : null;
		if(is_array($defaultSite))
		{
			$siteID = $defaultSite['LID'];
			$languageID = $defaultSite['LANGUAGE_ID'];
		}

		if($siteID === '')
		{
			$siteID = 's1';
		}

		if($languageID === '')
		{
			$languageID = 'ru';
		}

		\Bitrix\Main\Config\Option::set('crm', 'check_vat_zero', '-1', '');
		if ($languageID == 'ru')
		{
			\Bitrix\Main\Config\Option::set('crm', 'check_vat_zero', '-2', '');
			$resVats = \CCatalogVat::GetListEx(
				array(),
				array('RATE' => 0),
				false,
				false,
				array('ID')
			);
			if (!($vatInfo = $resVats->Fetch()))
			{
				\Bitrix\Main\Config\Option::set('crm', 'check_vat_zero', '-3', '');
				$fields = array(
					'ACTIVE' => 'Y',
					'SORT' => '100',
					'NAME' => Loc::getMessage('CRM_VAT_ZERO', null, $languageID),
					'RATE' => '0.0'
				);
				$vatID = \CCatalogVat::Add($fields);
				if ($vatID)
				{
					$vatID = (int)$vatID;
				}
				else
				{
					$vatID = -4;
				}
				\Bitrix\Main\Config\Option::set('crm', 'check_vat_zero', $vatID, '');
			}
		}
	}
}