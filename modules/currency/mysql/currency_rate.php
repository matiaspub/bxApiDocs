<?
use Bitrix\Currency;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/general/currency_rate.php");


/**
 * <b>CCurrencyRates</b> - класс для работы с курсами валют: сохранение, конвертация и пр.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/index.php
 * @author Bitrix
 */
class CCurrencyRates extends CAllCurrencyRates
{
	public static function _get_last_rates($valDate, $cur)
	{
		global $DB;

		$baseCurrency = Currency\CurrencyManager::getBaseCurrency();

		$strSql = $DB->TopSql("
			SELECT C.AMOUNT, C.AMOUNT_CNT, CR.RATE, CR.RATE_CNT
			FROM
				b_catalog_currency C
				LEFT JOIN b_catalog_currency_rate CR ON (
					C.CURRENCY = CR.CURRENCY AND CR.DATE_RATE < '".$DB->ForSql($valDate)."' AND CR.BASE_CURRENCY = '".$DB->ForSql($baseCurrency)."'
				)
			WHERE
				C.CURRENCY = '".$DB->ForSql($cur)."'
			ORDER BY
				DATE_RATE DESC
		", 1);
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res->Fetch();
	}
}