<?
global $DBType;

CModule::AddAutoloadClasses(
	"currency",
	array(
		"CCurrency" => $DBType."/currency.php",
		"CCurrencyLang" => $DBType."/currency_lang.php",
		"CCurrencyRates" => $DBType."/currency_rate.php",
	)
);

// define("CURRENCY_CACHE_DEFAULT_TIME", 10800);


/**
 * <p>Функция форматирует сумму fSum в соответствии с правилами форматирования для валюты strCurrency на текущем языке </p>
 *
 *
 *
 *
 * @param float $fSum  Денежная сумма, которую нужно сформатировать.
 *
 *
 *
 * @param string $strCurrency  Валюта, по правилам которой нужно производить форматирование.
 *
 *
 *
 * @return string <p>Возвращает сформатированую строку </p><a name="examples"></a>
 *
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * echo CurrencyFormat(11800.95, "USD");
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/functions/currencyformat.php
 * @author Bitrix
 */
function CurrencyFormat($fSum, $strCurrency)
{
	$result = "";
	$db_events = GetModuleEvents("currency", "CurrencyFormat");
	while ($arEvent = $db_events->Fetch())
		$result = ExecuteModuleEventEx($arEvent, Array($fSum, $strCurrency));

	if(strlen($result) > 0)
		return $result;

	if (!isset($fSum) || strlen($fSum)<=0)
		return "";

	$arCurFormat = CCurrencyLang::GetCurrencyFormat($strCurrency);

	if (!isset($arCurFormat["DECIMALS"]))
		$arCurFormat["DECIMALS"] = 2;
	$arCurFormat["DECIMALS"] = IntVal($arCurFormat["DECIMALS"]);

	if (!isset($arCurFormat["DEC_POINT"]))
		$arCurFormat["DEC_POINT"] = ".";
	if(!empty($arCurFormat["THOUSANDS_VARIANT"]))
	{
		if($arCurFormat["THOUSANDS_VARIANT"] == "N")
			$arCurFormat["THOUSANDS_SEP"] = "";
		elseif($arCurFormat["THOUSANDS_VARIANT"] == "D")
			$arCurFormat["THOUSANDS_SEP"] = ".";
		elseif($arCurFormat["THOUSANDS_VARIANT"] == "C")
			$arCurFormat["THOUSANDS_SEP"] = ",";
		elseif($arCurFormat["THOUSANDS_VARIANT"] == "S")
			$arCurFormat["THOUSANDS_SEP"] = chr(32);
		elseif($arCurFormat["THOUSANDS_VARIANT"] == "B")
			$arCurFormat["THOUSANDS_SEP"] = chr(32);
	}

	if (!isset($arCurFormat["FORMAT_STRING"]))
		$arCurFormat["FORMAT_STRING"] = "#";

	$num = number_format($fSum, $arCurFormat["DECIMALS"], $arCurFormat["DEC_POINT"], $arCurFormat["THOUSANDS_SEP"]);
	if($arCurFormat["THOUSANDS_VARIANT"] == "B")
		$num = str_replace(" ", "&nbsp;", $num);

	return str_replace("#", $num, $arCurFormat["FORMAT_STRING"]);
}

function CurrencyFormatNumber($price, $currency)
{
	$arCurFormat = CCurrencyLang::GetCurrencyFormat($currency);
	
	if (!isset($arCurFormat["DECIMALS"]))
		$arCurFormat["DECIMALS"] = 2;
	$arCurFormat["DECIMALS"] = IntVal($arCurFormat["DECIMALS"]);

	if (!isset($arCurFormat["DEC_POINT"]))
		$arCurFormat["DEC_POINT"] = ".";
	
	if(!empty($arCurFormat["THOUSANDS_VARIANT"]))
	{
		if($arCurFormat["THOUSANDS_VARIANT"] == "N")
			$arCurFormat["THOUSANDS_SEP"] = "";
		elseif($arCurFormat["THOUSANDS_VARIANT"] == "D")
			$arCurFormat["THOUSANDS_SEP"] = ".";
		elseif($arCurFormat["THOUSANDS_VARIANT"] == "C")
			$arCurFormat["THOUSANDS_SEP"] = ",";
		elseif($arCurFormat["THOUSANDS_VARIANT"] == "S")
			$arCurFormat["THOUSANDS_SEP"] = chr(32);
		elseif($arCurFormat["THOUSANDS_VARIANT"] == "B")
			$arCurFormat["THOUSANDS_SEP"] = chr(32);
	}
	
	if (!isset($arCurFormat["FORMAT_STRING"]))
		 $arCurFormat["FORMAT_STRING"] = "#";
	
	$price = number_format($price, $arCurFormat["DECIMALS"], $arCurFormat["DEC_POINT"], $arCurFormat["THOUSANDS_SEP"]);
	if($arCurFormat["THOUSANDS_VARIANT"] == "B")
		$num = str_replace(" ", "&nbsp;", $num);
	
	$price = str_replace(',', '.', $price);
	
	return $price;
}
?>