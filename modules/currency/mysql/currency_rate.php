<?
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
	
	/**
	* <p>Метод переводит сумму valSum из валюты curFrom в валюту curTo по курсу, установленному на дату valDate. Метод динамичный. </p>
	*
	*
	* @param float $valSum  Сумма в валюте curFrom, которую нужно перевести в валюту curTo
	*
	* @param string $curFrom  Исходная валюта.
	*
	* @param string $curTo  Конечная валюта.
	*
	* @param string $valDate = "" Дата, по курсу на которую нужно осуществить перевод. Если дата
	* пуста, то перевод идет по текущему курсу. Необязательный
	* параметр.
	*
	* @return float <p>Сумма в новой валюте </p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // предполагаем, что валюты USD и EUR существуют в базе
	* $val = 11.95; // сумма в USD
	* $newval = CCurrencyRates::ConvertCurrency($val, "USD", "EUR");
	* echo $val." USD = ".$newval." EUR";
	* ?&gt;
	* 
	* 
	* &lt;?
	* // способ конвертации валюты для списка
	* if (CModule::IncludeModule('currency')) {
	*    $factor = CCurrencyRates::GetConvertFactor('UEE', 'RUB');
	* } else {
	*    $factor = 1;
	* }
	* 
	* foreach ($arResult['ITEMS'] as $i =&gt; &amp;$arItem) {
	*    $arItem['PROPERTY_PRICE_VALUE'] = number_format($arItem['PROPERTY_PRICE_VALUE'] * $factor, 0, '.', ' ');
	* }
	* ?&amp;gt
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__convertcurrency.930a5544.php
	* @author Bitrix
	*/
	public static function ConvertCurrency($valSum, $curFrom, $curTo, $valDate = "")
	{
		return doubleval(doubleval($valSum) * CCurrencyRates::GetConvertFactor($curFrom, $curTo, $valDate));
	}

	
	/**
	* <p>Метод возвращает коэффициент для перевода сумм из валюты curFrom в валюту curTo по курсу, установленному на дату valDate. Метод динамичный.</p>
	*
	*
	* @param string $curFrom  Исходная валюта.
	*
	* @param string $curTo  Валюта назначения. </h
	*
	* @param string $valDate = "" Дата, по курсу на которую нужно осуществить перевод. Если дата
	* пуста, то перевод идет по текущему курсу. Необязательный
	* параметр.<br><br> Дата должна быть указана в формате <b>YYYY-MM-DD</b>.
	*
	* @return float <p>Коэффициент для перевода. </p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arVals = array(11.95, 18.27, 5.01);
	* $rate_cost = CCurrencyRates::GetConvertFactor("RUR", "USD");
	* for ($i = 0; $i &lt; count($arVals); $i++)
	* {
	*     echo $arVals[$i]." RUR = ".Round($rate_cost*$arVals[$i], 2)." USD";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__getconvertfactor.94622dac.php
	* @author Bitrix
	*/
	public static function GetConvertFactor($curFrom, $curTo, $valDate = "")
	{
		$obRates = new CCurrencyRates;
		return $obRates->GetConvertFactorEx($curFrom, $curTo, $valDate);
	}

	public static function _get_last_rates($valDate, $cur)
	{
		global $DB;

		$strSql = $DB->TopSql("
			SELECT C.AMOUNT, C.AMOUNT_CNT, CR.RATE, CR.RATE_CNT
			FROM
				b_catalog_currency C
				LEFT JOIN b_catalog_currency_rate CR ON (C.CURRENCY = CR.CURRENCY AND CR.DATE_RATE < '".$DB->ForSql($valDate)."')
			WHERE
				C.CURRENCY = '".$DB->ForSql($cur)."'
			ORDER BY
				DATE_RATE DESC
		", 1);
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res->Fetch();
	}
}
?>