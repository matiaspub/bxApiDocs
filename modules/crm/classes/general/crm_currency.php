<?php
IncludeModuleLangFile(__FILE__);

class CCrmCurrency
{
	private static $BASE_CURRENCY_ID = null;
	private static $ACCOUNT_CURRENCY_ID = null;
	private static $CURRENCY_BY_LANG = array();
	private static $CURRENCY_FORMAT_BY_LANG = array();
	protected static $LAST_ERROR = '';
	// Default currency is stub that used only when 'currency' module is not installed
	protected static $DEFAULT_CURRENCY_ID = '';
	private static $FIELD_INFOS = null;
	private static $LANGS_ID = null;

	// Get Fields Metadata
	public static function GetFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'CURRENCY' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::UserPKey)
				),
				'AMOUNT_CNT' => array(
					'TYPE' => 'int'
				),
				'AMOUNT' => array(
					'TYPE' => 'double'
				),
				'SORT' => array(
					'TYPE' => 'int'
				),
				'DATE_UPDATE' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'LID' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'FORMAT_STRING' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'FULL_NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DEC_POINT' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'THOUSANDS_SEP' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DECIMALS' => array(
					'TYPE' => 'int',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				)
			);
		}

		return self::$FIELD_INFOS;
	}

	public static function GetDefaultCurrencyID()
	{
		if(self::$DEFAULT_CURRENCY_ID !== '')
		{
			return self::$DEFAULT_CURRENCY_ID;
		}

		self::$DEFAULT_CURRENCY_ID = 'USD';

		$rsLang = CLanguage::GetByID('ru');
		if($arLang = $rsLang->Fetch())
		{
			self::$DEFAULT_CURRENCY_ID = 'RUB';
		}
		else
		{
			$rsLang = CLanguage::GetByID('de');
			if($arLang = $rsLang->Fetch())
			{
				self::$DEFAULT_CURRENCY_ID = 'EUR';
			}
		}

		return self::$DEFAULT_CURRENCY_ID;
	}

	public static function NormalizeCurrencyID($currencyID)
	{
		return strtoupper(trim(strval($currencyID)));
	}

	public static function GetBaseCurrencyID()
	{
		if (!CModule::IncludeModule('currency'))
		{
			return self::GetDefaultCurrencyID();
		}

		if(!self::$BASE_CURRENCY_ID)
		{
			self::$BASE_CURRENCY_ID = CCurrency::GetBaseCurrency();
		}
		return self::$BASE_CURRENCY_ID;
	}
	public static function SetBaseCurrencyID($currencyID)
	{
		if (!CModule::IncludeModule('currency'))
		{
			return false;
		}

		return CCurrency::SetBaseCurrency($currencyID);
	}
	// Is used in reports only
	public static function GetAccountCurrencyID()
	{
		if(!self::$ACCOUNT_CURRENCY_ID)
		{
			self::$ACCOUNT_CURRENCY_ID = COption::GetOptionString('crm', 'account_currency_id', '');
			if(!isset(self::$ACCOUNT_CURRENCY_ID[0]))
			{
				self::$ACCOUNT_CURRENCY_ID = self::GetBaseCurrencyID();
			}
		}

		return self::$ACCOUNT_CURRENCY_ID;
	}

	public static function SetAccountCurrencyID($currencyID)
	{
		$currencyID = self::NormalizeCurrencyID($currencyID);
		if($currencyID === self::$ACCOUNT_CURRENCY_ID)
		{
			return;
		}

		self::$ACCOUNT_CURRENCY_ID = $currencyID;
		COption::SetOptionString('crm', 'account_currency_id', self::$ACCOUNT_CURRENCY_ID);

		CCrmDeal::OnAccountCurrencyChange();
		CCrmLead::OnAccountCurrencyChange();
	}

	public static function GetAccountCurrency()
	{
		return self::GetByID(self::GetAccountCurrencyID());
	}

	public static function GetBaseCurrency()
	{
		if (!CModule::IncludeModule('currency'))
		{
			return false;
		}

		$baseCurrencyID = CCurrency::GetBaseCurrency();
		if(!isset($baseCurrencyID[0]))
		{
			return false;
		}
		return self::GetByID($baseCurrencyID);
	}

	public static function EnsureReady()
	{
		if(!CModule::IncludeModule('currency'))
		{
			self::$LAST_ERROR = GetMessage('CRM_CURRERCY_MODULE_WARNING');
			return false;
		}

		return true;
	}

	public static function IsExists($currencyID)
	{
		return is_array(self::GetByID($currencyID));
	}

	public static function GetByID($currencyID, $langID = '')
	{
		$currencyID = self::NormalizeCurrencyID($currencyID);

		if(!isset($currencyID[0]))
		{
			return false;
		}

		$currencies = self::GetAll($langID);
		return isset($currencies[$currencyID]) ? $currencies[$currencyID] : false;
	}

	public static function GetByName($name, $langID = '')
	{
		$name = strval($name);
		$currencies = self::GetAll($langID);
		foreach($currencies as $currency)
		{
			if(isset($currency['FULL_NAME']) && $currency['FULL_NAME'] === $name)
				return $currency;
		}
		return false;
	}

	public static function GetAll($langID = '')
	{
		if (!CModule::IncludeModule('currency'))
		{
			return array();
		}

		$langID = strval($langID);
		if(!isset($langID[0]))
		{
			$langID = LANGUAGE_ID;
		}

		$currencies = isset(self::$CURRENCY_BY_LANG[$langID]) ? self::$CURRENCY_BY_LANG[$langID] : null;
		if(!$currencies)
		{
			$currencies = array();
			$resCurrency = CCurrency::GetList(($by1 = 'sort'), ($order1 = 'asc'), $langID);
			while ($arCurrency = $resCurrency->Fetch())
			{
				$currencies[$arCurrency['CURRENCY']] = $arCurrency;
			}
			self::$CURRENCY_BY_LANG[$langID] = $currencies;
		}

		return $currencies;
	}

	public static function GetList($arOrder, $langID = '')
	{
		if (!CModule::IncludeModule('currency'))
		{
			return false;
		}

		if(!is_array($arOrder))
		{
			$arOrder = array();
		}

		$arOrderFields = array_keys($arOrder);
		if(count($arOrderFields) > 0)
		{
			$by = $arOrderFields[0];
			$order = $arOrder[$by];
		}
		else
		{
			$by = 'sort';
			$order = 'asc';
		}

		$langID = strval($langID);
		if($langID === '')
		{
			$langID = LANGUAGE_ID;
		}

		return CCurrency::GetList($by, $order, $langID);
	}

	public static function GetCurrencyLocalizations($currencyID)
	{
		if (!CModule::IncludeModule('currency'))
		{
			return array();
		}

		$currencyID = strval($currencyID);
		if($currencyID === '')
		{
			return array();
		}

		$result = array();

		$by = '';
		$order = '';
		$dbResult = CCurrencyLang::GetList($by, $order, self::NormalizeCurrencyID($currencyID));
		if($dbResult)
		{
			while($item = $dbResult->Fetch())
			{
				$result[$item['LID']] = $item;
			}
		}

		return $result;
	}

	private static function GetLanguagesID()
	{
		if(self::$LANGS_ID)
		{
			return self::$LANGS_ID;
		}


		self::$LANGS_ID = array();

		$by = 'sort';
		$order = 'asc';
		$dbResult = CLangAdmin::GetList($by, $order);
		while ($arResult = $dbResult->Fetch())
		{
			self::$LANGS_ID[] = $arResult['LID'];
		}

		return self::$LANGS_ID;
	}

	public static function SetCurrencyLocalizations($currencyID, $arItems)
	{
		if (!CModule::IncludeModule('currency'))
		{
			return false;
		}

		$currencyID = self::NormalizeCurrencyID($currencyID);
		$langsID = self::GetLanguagesID();

		$allowedKeys = array(
			'FULL_NAME', 'FORMAT_STRING',
			'DEC_POINT', 'THOUSANDS_VARIANT',
			'THOUSANDS_SEP'
		);

		$processed = 0;
		foreach($langsID as $langID)
		{
			$item = isset($arItems[$langID]) ? $arItems[$langID] : null;


			if(!is_array($item))
			{
				continue;
			}

			$fields = array();
			foreach($allowedKeys as $key)
			{
				if(isset($item[$key]))
				{
					$fields[$key] = $item[$key];
				}
			}

			if(empty($fields))
			{
				continue;
			}

			$fields['CURRENCY'] = $currencyID;
			$fields['LID'] = $langID;

			if(is_array(CCurrencyLang::GetByID($currencyID, $langID)))
			{
				CCurrencyLang::Update($currencyID, $langID, $fields);
				$processed++;
			}
			else
			{
				if(!isset($fields['DECIMALS']))
				{
					$fields['DECIMALS'] = 2;
				}
				CCurrencyLang::Add($fields);

				$processed++;
			}
		}

		return $processed > 0;
	}

	public static function DeleteCurrencyLocalizations($currencyID, $arLangs)
	{
		if (!CModule::IncludeModule('currency'))
		{
			return false;
		}

		if(!is_array($arLangs) || empty($arLangs))
		{
			return false;
		}

		$langsID = self::GetLanguagesID();

		$processed = 0;
		foreach($langsID as $langID)
		{
			if(!in_array($langID, $arLangs, true)
				|| !is_array(CCurrencyLang::GetByID($currencyID, $langID)))
			{
				continue;
			}

			if(CCurrencyLang::Delete($currencyID, $langID))
			{
				$processed++;
			}
		}
		return $processed > 0;
	}

	public static function GetEncodedCurrencyName($currencyID, $langID = '')
	{
		return htmlspecialcharsbx(self::GetCurrencyName($currencyID, $langID));
	}
	public static function GetCurrencyName($currencyID, $langID = '')
	{
		$currencyID = strval($currencyID);
		if($currencyID === '')
		{
			return '';
		}

		$ID = self::NormalizeCurrencyID($currencyID);
		$currencies = self::GetAll($langID);
		return isset($currencies[$ID]) && isset($currencies[$ID]['FULL_NAME']) ? $currencies[$ID]['FULL_NAME'] : $currencyID;
	}

	public static function GetCurrencyFormatString($currencyID, $langID = '')
	{
		$currencyID = strval($currencyID);
		$langID = strval($langID);
		if($langID === '')
		{
			$langID = LANGUAGE_ID;
		}

		$formatStr = '';
		if(isset(self::$CURRENCY_FORMAT_BY_LANG[$langID]) && isset(self::$CURRENCY_FORMAT_BY_LANG[$langID][$currencyID]))
		{
			$formatStr = self::$CURRENCY_FORMAT_BY_LANG[$langID][$currencyID];
		}
		elseif(CModule::IncludeModule('currency'))
		{
			$formatInfo = CCurrencyLang::GetCurrencyFormat($currencyID, $langID);
			$formatStr = isset($formatInfo['FORMAT_STRING'])
				? $formatInfo['FORMAT_STRING'] : '#';

			if($formatStr !== '')
			{
				$formatStr = strip_tags($formatStr);
			}

			if($formatStr === '')
			{
				$formatStr = '#';
			}

			if(!isset(self::$CURRENCY_FORMAT_BY_LANG[$langID]))
			{
				self::$CURRENCY_FORMAT_BY_LANG[$langID] = array();
			}
			self::$CURRENCY_FORMAT_BY_LANG[$langID][$currencyID] = $formatStr;
		}

		return $formatStr;
	}

	public static function MoneyToString($sum, $currencyID, $formatStr = '')
	{
		if(!CModule::IncludeModule('currency'))
		{
			return number_format($sum, 2, '.', '');
		}

		$formatInfo = CCurrencyLang::GetCurrencyFormat($currencyID);
		$formatInfo['DECIMALS'] = isset($formatInfo['DECIMALS']) ?  intval($formatInfo['DECIMALS']) : 2;

		if(!isset($formatInfo['DEC_POINT']))
		{
			$formatInfo['DEC_POINT'] = '.';
		}

		if(!empty($formatInfo['THOUSANDS_VARIANT']))
		{
			$thousands = $formatInfo['THOUSANDS_VARIANT'];

			if($thousands === 'N')
			{
				$formatInfo['THOUSANDS_SEP'] = '';
			}
			elseif($thousands === 'D')
			{
				$formatInfo['THOUSANDS_SEP'] = '.';
			}
			elseif($thousands === 'C')
			{
				$formatInfo['THOUSANDS_SEP'] = ',';
			}
			elseif($thousands === 'S' || $thousands === 'B')
			{
				$formatInfo['THOUSANDS_SEP'] = chr(32);
			}
		}

		if(!isset($formatInfo['THOUSANDS_SEP']))
		{
			$formatInfo['THOUSANDS_SEP'] = '';
		}

		if(is_integer($sum) || is_float($sum))
		{
			// Stansard format for float
			$s = number_format($sum, $formatInfo['DECIMALS'], $formatInfo['DEC_POINT'], $formatInfo['THOUSANDS_SEP']);
		}
		else
		{
			// Do not convert to float to avoid data lost caused by overflow (9 999 999 999 999 999.99 ->10 000 000 000 000 000.00)
			$triadSep = strval($formatInfo['THOUSANDS_SEP']);
			$decPoint = strval($formatInfo['DEC_POINT']);
			$dec = intval($formatInfo['DECIMALS']);

			$sum = str_replace(',', '.', strval($sum));
			list($i, $d) = explode('.', $sum, 2);

			$len = strlen($i);
			$leadLen = $len % 3;
			if($leadLen === 0)
			{
				$leadLen = 3; //take a first triad
			}
			$lead = substr($i, 0, $leadLen);
			if(!is_string($lead))
			{
				$lead = '';
			}
			$triads = substr($i, $leadLen);
			if(!is_string($triads))
			{
				$triads = '';
			}
			$s = $triads !== '' ? $lead.preg_replace('/(\\d{3})/', $triadSep.'\\1', $triads) : ($lead !== '' ? $lead : '0');
			$s .= $decPoint.str_pad(substr($d, 0, $dec), $dec, '0', STR_PAD_RIGHT);
		}

		if(!empty($formatInfo['THOUSANDS_VARIANT']) && $formatInfo['THOUSANDS_VARIANT'] === 'B')
		{
			$s = str_replace(' ', '&nbsp;', $s);
		}

		$formatStr = strval($formatStr);
		if($formatStr === '' && $formatInfo['FORMAT_STRING'] !== '')
		{
			$formatStr = $formatInfo['FORMAT_STRING'];
		}

		if($formatStr === '' || $formatStr === '#')
		{
			return strip_tags($s);
		}

		//Skip HTML entities
		return strip_tags(
			preg_replace('/(^|[^&])#/', '${1}'.$s, $formatStr)
		);
	}

	public static function ConvertMoney($sum, $srcCurrencyID, $dstCurrencyID, $srcExchRate = -1)
	{
		$sum = doubleval($sum);

		if (!CModule::IncludeModule('currency'))
		{
			return $sum;
		}

		$srcCurrencyID = self::NormalizeCurrencyID($srcCurrencyID);
		$dstCurrencyID = self::NormalizeCurrencyID($dstCurrencyID);
		$srcExchRate = doubleval($srcExchRate);

		if($sum === 0.0 || $srcCurrencyID === $dstCurrencyID)
		{
			return $sum;
		}

		$result = 0;
		if($srcExchRate < 0)
		{
			// Use default exchenge rate
			$result = CCurrencyRates::ConvertCurrency($sum, $srcCurrencyID, $dstCurrencyID);
		}
		else
		{
			// Convert source currency to base and convert base currency to destination
			$result = CCurrencyRates::ConvertCurrency(
				doubleval($sum * $srcExchRate),
				self::GetBaseCurrencyID(),
				$dstCurrencyID
			);
		}

		$decimals = 2;
		$formatInfo = CCurrencyLang::GetCurrencyFormat($dstCurrencyID);
		if(isset($formatInfo['DECIMALS']))
		{
			$decimals = intval($formatInfo['DECIMALS']);
		}

		$result = round($result, $decimals);
		return $result;
	}

	public static function GetExchangeRate($currencyID)
	{
		if (!CModule::IncludeModule('currency'))
		{
			return 1;
		}

		$rates = new CCurrencyRates();
		if(!($rs = $rates->_get_last_rates(date('Y-m-d'), $currencyID)))
		{
			return 1.0;
		}

		$exchRate = doubleval($rs['RATE']);
		$cnt = intval($rs['RATE_CNT']);
		if ($exchRate <= 0)
		{
			$exchRate = doubleval($rs["AMOUNT"]);
			$cnt = intval($rs['AMOUNT_CNT']);
		}
		return $cnt != 1 ? ($exchRate / $cnt) : $exchRate;
	}

	private static function ClearCache()
	{
		self::$CURRENCY_BY_LANG = array();
	}

	public static function GetLastError()
	{
		return self::$LAST_ERROR;
	}

	private static function CheckFields($action, &$arFields, $ID)
	{
		if(isset($arFields['AMOUNT_CNT']))
		{
			$arFields['AMOUNT_CNT'] = intval($arFields['AMOUNT_CNT']);
		}

		if(isset($arFields['AMOUNT']))
		{
			$arFields['AMOUNT'] = doubleval($arFields['AMOUNT']);
		}

//		if(isset($arFields['SORT']))
//		{
//			$SORT = intval($arFields['SORT']);
//			$arFields['SORT'] = ($SORT > 255 || $SORT < 0 ? 0 : $SORT);
//		}

		return true;
	}

	public static function Add($arFields)
	{
		if (!CModule::IncludeModule('currency'))
		{
			self::$LAST_ERROR = GetMessage('CRM_CURRERCY_MODULE_IS_NOT_INSTALLED');
			return false;
		}

		global $APPLICATION;

		$ID = isset($arFields['CURRENCY']) ? $arFields['CURRENCY'] : '';
		if(!self::CheckFields('ADD', $arFields, $ID))
		{
			return false;
		}

		$ID = CCurrency::Add($arFields);
		if(!$ID)
		{
			$ex = $APPLICATION->GetException();
			if ($ex)
			{
				self::$LAST_ERROR = $ex->GetString();
			}

			return false;
		}

		self::ClearCache();
		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		if (!CModule::IncludeModule('currency'))
		{
			self::$LAST_ERROR = GetMessage('CRM_CURRERCY_MODULE_IS_NOT_INSTALLED');
			return false;
		}

		global $APPLICATION;

		$arFields['CURRENCY'] = $ID;

		if(!self::CheckFields('UPDATE', $arFields, $ID))
		{
			return false;
		}

		if(!CCurrency::Update($ID, $arFields))
		{
			$ex = $APPLICATION->GetException();
			if ($ex)
			{
				self::$LAST_ERROR = $ex->GetString();
			}

			return false;
		}

		self::ClearCache();
		return true;
	}

	public static function Delete($ID)
	{
		if (!CModule::IncludeModule('currency'))
		{
			self::$LAST_ERROR = GetMessage('CRM_CURRERCY_MODULE_IS_NOT_INSTALLED');
			return false;
		}

		IncludeModuleLangFile(__FILE__);

		global $APPLICATION;

		$ID = strval($ID);
		if(strlen($ID) !== 3)
		{
			//Invalid ID is supplied. Are you A.Krasichkov?
			//self::$LAST_ERROR = GetMessage('CRM_CURRERCY_MODULE_INVALID_ID');
			return false;
		}

		if($ID === self::GetBaseCurrencyID())
		{
			self::$LAST_ERROR = GetMessage('CRM_CURRERCY_ERR_DELETION_OF_BASE_CURRENCY');
			return false;
		}

		if($ID === self::GetAccountCurrencyID())
		{
			self::$LAST_ERROR = GetMessage('CRM_CURRERCY_ERR_DELETION_OF_ACCOUNTING_CURRENCY');
			return false;
		}

		if (!CCurrency::Delete($ID))
		{
			$ex = $APPLICATION->GetException();
			if ($ex)
			{
				self::$LAST_ERROR = $ex->GetString();
			}

			return false;
		}

		self::ClearCache();
		return true;
	}

	public static function CheckCreatePermission()
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	public static function CheckUpdatePermission($ID)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	public static function CheckDeletePermission($ID)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	public static function CheckReadPermission($ID = 0)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');
	}

	public static function getInvoiceDefault()
	{
		return COption::GetOptionString("sale", "default_currency", "RUB");
	}

	public static function setInvoiceDefault($currencyId)
	{
		return COption::SetOptionString("sale", "default_currency", $currencyId);
	}	
}
