<?
IncludeModuleLangFile(__FILE__);

class CSaleDiscountConvert
{
	public static $intConvertPerStep = 0;
	public static $intNextConvertPerStep = 0;
	public static $intConverted = 0;
	public static $intLastConvertID = 0;
	public static $boolEmptyList = false;
	public static $intErrors = 0;
	public static $arErrors = array();
	public static $strSessID = '';

	static public function __construct()
	{

	}

	public static function InitStep()
	{
		if ('' == self::$strSessID)
			self::$strSessID = 'DC'.time();
		if (array_key_exists(self::$strSessID, $_SESSION) && is_array($_SESSION[self::$strSessID]))
		{
			if (isset($_SESSION[self::$strSessID]['ERRORS_COUNT']) && 0 < intval($_SESSION[self::$strSessID]['ERRORS_COUNT']))
				self::$intErrors = intval($_SESSION[self::$strSessID]['ERRORS_COUNT']);
			if (isset($_SESSION[self::$strSessID]['ERRORS']) && is_array($_SESSION[self::$strSessID]['ERRORS']))
				self::$arErrors = $_SESSION[self::$strSessID]['ERRORS'];
		}
	}

	public static function SaveStep()
	{
		if ('' == self::$strSessID)
			self::$strSessID = 'DC'.time();
		if (!array_key_exists(self::$strSessID, $_SESSION) || !is_array($_SESSION[self::$strSessID]))
			$_SESSION[self::$strSessID] = array();
		if (0 < self::$intErrors)
		{
			$_SESSION[self::$strSessID]['ERRORS_COUNT'] = self::$intErrors;
		}
		if (!empty(self::$arErrors))
		{
			$_SESSION[self::$strSessID]['ERRORS'] = self::$arErrors;
		}
	}

	public static function GetErrors()
	{
		return self::$arErrors;
	}

	public static function GetCountOld()
	{
		global $DBType;
		global $DB;

		$strSql = '';
		switch(ToUpper($DBType))
		{
			case 'MYSQL':
				$strSql = "SELECT COUNT(*) CNT FROM b_sale_discount WHERE VERSION=".CSaleDiscount::VERSION_OLD;
				break;
			case 'MSSQL':
				$strSql = "SELECT COUNT(*) CNT FROM B_SALE_DISCOUNT WHERE VERSION=".CSaleDiscount::VERSION_OLD;
				break;
			case 'ORACLE':
				$strSql = "SELECT COUNT(*) CNT FROM B_SALE_DISCOUNT WHERE VERSION=".CSaleDiscount::VERSION_OLD;
				break;
		}
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!$res)
			return 0;

		if ($row = $res->Fetch())
			return intval($row['CNT']);
	}

	public static function GetCount()
	{
		global $DBType;
		global $DB;

		$strSql = '';
		switch(ToUpper($DBType))
		{
			case 'MYSQL':
				$strSql = "SELECT COUNT(*) CNT FROM b_sale_discount WHERE 1=1";
				break;
			case 'MSSQL':
				$strSql = "SELECT COUNT(*) CNT FROM B_SALE_DISCOUNT WHERE 1=1";
				break;
			case 'ORACLE':
				$strSql = "SELECT COUNT(*) CNT FROM B_SALE_DISCOUNT WHERE 1=1";
				break;
		}
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (!$res)
			return 0;

		if ($row = $res->Fetch())
			return intval($row['CNT']);
	}

	public static function ConvertDiscount($intStep = 100, $intMaxExecutionTime = 15)
	{
		global $DBType;
		global $DB;
		global $APPLICATION;

		self::InitStep();

		$intStep = intval($intStep);
		if (0 >= $intStep)
			$intStep = 100;
		$startConvertTime = getmicrotime();

		$obDiscount = new CSaleDiscount();

		$strTableName = '';
		switch (ToUpper($DBType))
		{
			case 'MYSQL':
				$strTableName = 'b_catalog_discount';
				break;
			case 'MSSQL':
				$strTableName = 'B_CATALOG_DISCOUNT';
				break;
			case 'ORACLE':
				$strTableName = 'B_CATALOG_DISCOUNT';
				break;
		}

		$intCount = CSaleDiscountConvert::GetCount();
		if (0 == $intCount)
		{

		}
		$strStatus = (1 < $intCount ? 'N' : 'Y');

		$arBaseCurrencies = array();
		$rsSites = CSite::GetList($b="id", $o="asc");
		while ($arSite = $rsSites->Fetch())
		{
			$arBaseCurrencies[$arSite['ID']] = CSaleLang::GetLangCurrency($arSite['ID']);
		}
		CTimeZone::Disable();

		$rsDiscounts = CSaleDiscount::GetList(
			array('ID' => 'ASC'),
			array(
				'VERSION' => CSaleDiscount::VERSION_OLD,
			),
			false,
			array('nTopCount' => $intStep),
			array(
				'ID', 'SITE_ID', 'MODIFIED_BY', 'TIMESTAMP_X',
				'PRICE_FROM', 'PRICE_TO', 'CURRENCY',
				'DISCOUNT_VALUE', 'DISCOUNT_TYPE'
			)
		);
		while ($arDiscount = $rsDiscounts->Fetch())
		{
			$arFields = array();
			$arFields['MODIFIED_BY'] = $arDiscount['MODIFIED_BY'];

			$arConditions = array(
				'CLASS_ID' => 'CondGroup',
				'DATA' => array(
					'All' => 'AND',
					'True' => 'True',
				),
				'CHILDREN' => array(),
			);
			$arActions = array(
				'CLASS_ID' => 'CondGroup',
				'DATA' => array(
					'All' => 'AND',
					'True' => 'True',
				),
				'CHILDREN' => array(),
			);

			$boolCurrency = ($arDiscount['CURRENCY'] == $arBaseCurrencies[$arDiscount['SITE_ID']]);

			$strFrom = '';
			$strTo = '';
			$strValue = '';
			$arDiscount['PRICE_FROM'] = doubleval($arDiscount['PRICE_FROM']);
			$arDiscount['PRICE_TO'] = doubleval($arDiscount['PRICE_TO']);
			$arDiscount['DISCOUNT_VALUE'] = doubleval($arDiscount['DISCOUNT_VALUE']);
			if (0 < $arDiscount['PRICE_FROM'])
			{
				$dblValue = roundEx(($boolCurrency ? $arDiscount['PRICE_FROM'] : CCurrencyRates::ConvertCurrency($arDiscount['PRICE_FROM'], $arDiscount['CURRENCY'], $arBaseCurrencies[$arDiscount['SITE_ID']])), SALE_VALUE_PRECISION);
				$arConditions['CHILDREN'][] = array(
					'CLASS_ID' => 'CondBsktAmtGroup',
					'DATA' => array(
						'logic' => 'EqGr',
						'Value' => (string)$dblValue,
						'All' => 'AND',
					),
					'CHILDREN' => array(
					),
				);
				if (!$boolCurrency)
				{
					$arFields['PRICE_FROM'] = $dblValue;
				}
				$strFrom = str_replace('#VALUE#', $dblValue.' '.$arBaseCurrencies[$arDiscount['SITE_ID']], GetMessage('BT_MOD_SALE_DSC_FORMAT_NAME_FROM'));
			}
			if (0 < $arDiscount['PRICE_TO'])
			{
				$dblValue = roundEx($boolCurrency ? $arDiscount['PRICE_TO'] : CCurrencyRates::ConvertCurrency($arDiscount['PRICE_TO'], $arDiscount['CURRENCY'], $arBaseCurrencies[$arDiscount['SITE_ID']]), SALE_VALUE_PRECISION);
				$arConditions['CHILDREN'][] = array(
					'CLASS_ID' => 'CondBsktAmtGroup',
					'DATA' => array(
						'logic' => 'EqLs',
						'Value' => (string)$dblValue,
						'All' => 'AND',
					),
					'CHILDREN' => array(
					),
				);
				if (!$boolCurrency)
				{
					$arFields['PRICE_TO'] = $dblValue;
				}
				$strTo = str_replace('#VALUE#', $dblValue.' '.$arBaseCurrencies[$arDiscount['SITE_ID']], GetMessage('BT_MOD_SALE_DSC_FORMAT_NAME_TO'));
			}
			if (CSaleDiscount::OLD_DSC_TYPE_PERCENT == $arDiscount['DISCOUNT_TYPE'])
			{
				$arActions['CHILDREN'][] = array(
					'CLASS_ID' => 'ActSaleBsktGrp',
					'DATA' => array(
						'Type' => 'Discount',
						'Value' => (string)roundEx($arDiscount['DISCOUNT_VALUE'], SALE_VALUE_PRECISION),
						'Unit' => 'Perc',
						'All' => 'AND',
					),
					'CHILDREN' => array(
					),
				);
				$strValue = $arDiscount['DISCOUNT_VALUE'].' %';
			}
			else
			{
				$dblValue = roundEx(($boolCurrency ? $arDiscount['DISCOUNT_VALUE'] : CCurrencyRates::ConvertCurrency($arDiscount['DISCOUNT_VALUE'], $arDiscount['CURRENCY'], $arBaseCurrencies[$arDiscount['SITE_ID']])), SALE_VALUE_PRECISION);
				$arActions['CHILDREN'][] = array(
					'CLASS_ID' => 'ActSaleBsktGrp',
					'DATA' => array(
						'Type' => 'Discount',
						'Value' => (string)$dblValue,
						'Unit' => 'CurAll',
						'All' => 'AND',
					),
					'CHILDREN' => array(
					),
				);
				if (!$boolCurrency)
				{
					$arFields['DISCOUNT_VALUE'] = $dblValue;
				}
				$strValue = $dblValue.' '.$arBaseCurrencies[$arDiscount['SITE_ID']];
			}

			if ('' != $strFrom || '' != $strTo)
			{
				$strName = str_replace(array('#VALUE#','#FROM#', '#TO#'), array($strValue, $strFrom, $strTo), GetMessage('BT_MOD_SALE_DSC_FORMAT_NAME'));
			}
			else
			{
				$strName = str_replace('#VALUE#', $strValue, GetMessage('BT_MOD_SALE_DSC_FORMAT_SHORT_NAME'));
			}

			$arFields['CONDITIONS'] = $arConditions;
			$arFields['ACTIONS'] = $arActions;
			$arFields['NAME'] = $strName;
			if (!$boolCurrency)
			{
				$arFields['CURRENCY'] = $arBaseCurrencies[$arDiscount['SITE_ID']];
			}

			if ('N' == $strStatus)
			{
				$arFields['ACTIVE'] = 'N';
			}

			$mxRes = $obDiscount->Update($arDiscount['ID'], $arFields);
			if (!$mxRes)
			{
				self::$intErrors++;
				$strError = '';
				if ($ex = $APPLICATION->GetException())
				{
					$strError = $ex->GetString();
				}
				if (empty($strError))
					$strError = GetMessage('');
				self::$arErrors[] = array(
					'ID' => $arDiscount['ID'],
					'NAME' => $strName,
					'ERROR' => $strError,
				);
			}
			else
			{
				$arTimeFields = array('~TIMESTAMP_X' => $DB->CharToDateFunction($arDiscount['TIMESTAMP_X'], "FULL"));
				$strUpdate = $DB->PrepareUpdate($strTableName, $arTimeFields);
				if (!empty($strUpdate))
				{
					$strQuery = "UPDATE ".$strTableName." SET ".$strUpdate." WHERE ID = ".$arDiscount['ID'];
					$DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}

				self::$intConverted++;
				self::$intConvertPerStep++;
			}

			if ($intMaxExecutionTime > 0 && (getmicrotime() - $startConvertTime > $intMaxExecutionTime))
				break;
		}

		CTimeZone::Enable();

		if ($intMaxExecutionTime > (2*(getmicrotime() - $startConvertTime)))
			self::$intNextConvertPerStep = $intStep*2;
		else
			self::$intNextConvertPerStep = $intStep;

		self::SaveStep();
	}
}
?>