<?
use Bitrix\Main\Loader;

global $DB;
$strDBType = strtolower($DB->type);

Loader::registerAutoLoadClasses(
	'currency',
	array(
		'CCurrency' => 'general/currency.php',
		'CCurrencyLang' => 'general/currency_lang.php',
		'CCurrencyRates' => $strDBType.'/currency_rate.php',
		'\Bitrix\Currency\CurrencyTable' => 'lib/currency.php',
		'\Bitrix\Currency\CurrencyLangTable' => 'lib/currencylang.php',
		'\Bitrix\Currency\CurrencyRateTable' => 'lib/currencyrate.php',
		'\Bitrix\Currency\CurrencyManager' => 'lib/currencymanager.php'
	)
);
unset($strDBType);

$jsCurrencyDescr = array(
	'js' => '/bitrix/js/currency/core_currency.js',
	'rel' => array('core')
);
CJSCore::RegisterExt('currency', $jsCurrencyDescr);
unset($jsCurrencyDescr);

// define('CURRENCY_CACHE_DEFAULT_TIME', 10800);
// define('CURRENCY_ISO_STANDART_URL', 'http://www.iso.org/iso/home/standards/currency_codes.htm');

/*
* @deprecated deprecated since currency 14.0.0
* @see CCurrencyLang::CurrencyFormat()
*/

/**
 * <p>Функция форматирует цену <i>price</i> в соответствии с правилами форматирования для валюты <i>currency</i> на текущем языке. Причем, если функция вызывается в административном разделе, то дополнительно будет проведена очистка шаблона от тегов и скриптов. Если же функция вызывается в публичной части, то будет задействован параметр <i>HIDE_ZERO</i>, который отвечает за скрытие незначащих нулей в дробной части.</p> <p></p> <div class="note"> <b>Примечание:</b> вместо этой устаревшей функции рекомендуется использовать новую функцию <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/currencyformat.php">CurrencyFormat</a> класса <b>CCurrencyLang</b>.</div>
 *
 *
 * @param float $price  Цена (денежная сумма), которую нужно сформатировать.
 *
 * @param string $currency  Валюта, по правилам которой нужно производить форматирование.
 *
 * @return string <p>Возвращает сформатированую строку.</p> <a name="examples"></a>
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * echo CurrencyFormat(11800.95, "USD");
 * ?&gt;
 * &lt;?
 * // Задать свой формат вывода цены можно следующим образом
 * 
 * AddEventHandler("currency", "CurrencyFormat", "myFormat");
 * 
 * function myFormat($fSum, $strCurrency)
 * {
 *    return number_format ( $fSum, 2, '.', ' ' ).' <b style="color:red;">Р</b>ублей.';
 * }
 * 
 * echo CurrencyFormat(1234.5678, 'RUB');
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/functions/currencyformat.php
 * @author Bitrix
 * @deprecated deprecated since currency 14.0.0  ->  CCurrencyLang::CurrencyFormat()
 */
function CurrencyFormat($price, $currency)
{
	return CCurrencyLang::CurrencyFormat($price, $currency, true);
}

/*
* @deprecated deprecated since currency 14.0.0
* @see CCurrencyLang::CurrencyFormat()
*/

/**
 * <p>Функция форматирует цену <i>price</i> в соответствии с настройками валюты <i>currency</i> для текущего языка без использования шаблона.</p> <p></p> <div class="note"> <b>Примечание:</b> вместо этой устаревшей функции рекомендуется использовать новую функцию <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/currencyformat.php">CurrencyFormat</a> класса <b>CCurrencyLang</b>.</div>
 *
 *
 * @param float $price  Цена (денежная сумма), которую нужно сформатировать.
 *
 * @param string $currency  Валюта, по правилам которой нужно производить форматирование.
 *
 * @return string <p>Возвращает строку с величиной суммы, отформатированной
 * согласно настройкам без шаблона.</p> <br><br>
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/functions/currencyformatnumber.php
 * @author Bitrix
 * @deprecated deprecated since currency 14.0.0  ->  CCurrencyLang::CurrencyFormat()
 */
function CurrencyFormatNumber($price, $currency)
{
	return CCurrencyLang::CurrencyFormat($price, $currency, false);
}