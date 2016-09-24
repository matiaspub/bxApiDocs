<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/delivery.php");

/** @deprecated */

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledelivery/index.php
 * @author Bitrix
 * @deprecated
 */
class CSaleDelivery extends CAllSaleDelivery
{
	/** @deprecated  */
	public static function PrepareCurrency4Where($val, $key, $operation, $negative, $field, &$arField, &$arFilter)
	{
		$val = DoubleVal($val);

		$baseSiteCurrency = "";
		if (isset($arFilter["LID"]) && strlen($arFilter["LID"]) > 0)
			$baseSiteCurrency = CSaleLang::GetLangCurrency($arFilter["LID"]);
		elseif (isset($arFilter["CURRENCY"]) && strlen($arFilter["CURRENCY"]) > 0)
			$baseSiteCurrency = $arFilter["CURRENCY"];

		if (strlen($baseSiteCurrency) <= 0)
			return False;

		$strSqlSearch = "";

		$dbCurrency = CCurrency::GetList(($by = "sort"), ($order = "asc"));
		while ($arCurrency = $dbCurrency->Fetch())
		{
			$val1 = roundEx(CCurrencyRates::ConvertCurrency($val, $baseSiteCurrency, $arCurrency["CURRENCY"]), SALE_VALUE_PRECISION);
			if (strlen($strSqlSearch) > 0)
				$strSqlSearch .= " OR ";

			$strSqlSearch .= "(D.ORDER_CURRENCY = '".$arCurrency["CURRENCY"]."' AND ";
			if ($negative == "Y")
				$strSqlSearch .= "NOT";
			$strSqlSearch .= "(".$field." ".$operation." ".$val1." OR ".$field." IS NULL OR ".$field." = 0)";
			$strSqlSearch .= ")";
		}

		return "(".$strSqlSearch.")";
	}

	/** @deprecated */
	public static function PrepareLocation4Where($val, $key, $operation, $negative, $field, &$arField, &$arFilter)
	{
		return "(D2L.LOCATION_ID = ".IntVal($val)." AND D2L.LOCATION_TYPE = 'L' ".
			" OR L2LG.LOCATION_ID = ".IntVal($val)." AND D2L.LOCATION_TYPE = 'G') ";
	}
}
?>