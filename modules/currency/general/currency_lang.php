<?
//IncludeModuleLangFile(__FILE__);


/**
 * <b>CCurrencyLang</b> - класс для работы с языкозависимыми параметрами валют (название, формат и пр.)
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/index.php
 * @author Bitrix
 */
class CAllCurrencyLang
{
	
	/**
	 * <p>Функция добавляет новые языкозависимые параметры валюты </p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  <p>Ассоциативный массив параметров валюты, ключами которого
	 * являются названия параметров, а значениями - значения
	 * параметров.</p> <p>Допустимые ключи: <br> CURRENCY - код валюты, для которой
	 * добавляются языкозависимые параметры (обязательно должен
	 * присутствовать) <br> LID - код языка (обязательный) <br> FORMAT_STRING - строка
	 * формата, в соответствии с которой выводится суммы в этой валюте
	 * на этом языке (обязательный) <br> FULL_NAME - полное название валюты <br>
	 * DEC_POINT - символ, являющийся десятичной точкой при выводе сумм
	 * (обязательный) <br> THOUSANDS_SEP - разделитель тысяч при выводе <br> DECIMALS -
	 * количество знаков после запятой при выводе (обязательный)</p>
	 *
	 *
	 *
	 * @return bool <p>Возвращает значение True в случае успешного добавления и False - в
	 * противном случае </p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$arFields = array(<br>    "FORMAT_STRING" =&gt; "# руб", // символ # будет заменен<br>                                // реальной суммой при выводе<br>    "FULL_NAME" =&gt; "Рубль",<br>    "DEC_POINT" =&gt; ".",<br>    "THOUSANDS_SEP" =&gt; "\xA0",  // неразрывный пробел<br>    "DECIMALS" =&gt; 2,<br>    "CURRENCY" =&gt; "RUB",<br>    "LID" =&gt; "ru"<br>);<br><br>// Если запись существует, обновляем,<br>// иначе добавляем новую<br>$db_result_lang = CCurrencyLang::GetByID("RUB", "ru");<br>if ($db_result_lang)<br>    CCurrencyLang::Update("RUB", "ru", $arFields);<br>else<br>    CCurrencyLang::Add($arFields);<br>?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/ccurrencylang__add.7ce2349e.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;
		global $stackCacheManager;
		global $CACHE_MANAGER;

		$arInsert = $DB->PrepareInsert("b_catalog_currency_lang", $arFields);

		$strSql =
			"INSERT INTO b_catalog_currency_lang(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$stackCacheManager->Clear("currency_currency_lang");
		$CACHE_MANAGER->Clean("currency_currency_list");
		$CACHE_MANAGER->Clean("currency_currency_list_".substr($arFields['LID'], 0, 2));

		return true;
	}

	
	/**
	 * <p>Функция обновляет языкозависимые параметры валюты currency для языка lang </p>
	 *
	 *
	 *
	 *
	 * @param string $currency  Код валюты, языкозависимые параметры которой нужно обновить.
	 *
	 *
	 *
	 * @param string $lang  Код языка, для которого языкозависимые параметры валюты нужно
	 * обновить.
	 *
	 *
	 *
	 * @param array $arFields  <p>Ассоциативный массив новых параметров валюты, ключами которого
	 * являются названия параметров, а значениями - значения
	 * параметров.</p> <p>Допустимые ключи: <br> CURRENCY - код валюты <br> LID - код
	 * языка <br> FORMAT_STRING - строка формата, в соответствии с которой
	 * выводится суммы в этой валюте на этом языке <br> FULL_NAME - полное
	 * название валюты <br> DEC_POINT - символ, являющийся десятичной точкой
	 * при выводе сумм <br> THOUSANDS_SEP - разделитель тысяч при выводе <br> DECIMALS -
	 * количество знаков после запятой при выводе</p>
	 *
	 *
	 *
	 * @return bool <p>Возвращает значение True в случае успешного добавления и False - в
	 * противном случае</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$arFields = array(<br>    "FORMAT_STRING" =&gt; "# руб", // символ # будет заменен<br>                                // реальной суммой при выводе<br>    "FULL_NAME" =&gt; "Рубль",<br>    "DEC_POINT" =&gt; ".",<br>    "THOUSANDS_SEP" =&gt; "\xA0",  // неразрывный пробел<br>    "DECIMALS" =&gt; 2,<br>    "CURRENCY" =&gt; "RUB",<br>    "LID" =&gt; "ru"<br>);<br><br>// Если запись существует, то обновляем, иначе добавляем новую<br>$db_result_lang = CCurrencyLang::GetByID("RUB", "ru");<br>if ($db_result_lang)<br>    CCurrencyLang::Update("RUB", "ru", $arFields);<br>else<br>    CCurrencyLang::Add($arFields);<br>?&gt;<br>
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/ccurrencylang__update.8a1e7a7b.php
	 * @author Bitrix
	 */
	public static function Update($currency, $lang, $arFields)
	{
		global $DB;
		global $stackCacheManager;
		global $CACHE_MANAGER;

		$strUpdate = $DB->PrepareUpdate("b_catalog_currency_lang", $arFields);
		$strSql = "UPDATE b_catalog_currency_lang SET ".$strUpdate." WHERE CURRENCY = '".$DB->ForSql($currency, 3)."' AND LID='".$DB->ForSql($lang, 2)."'";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$stackCacheManager->Clear("currency_currency_lang");
		$CACHE_MANAGER->Clean("currency_currency_list");
		$CACHE_MANAGER->Clean("currency_currency_list_".substr($lang, 0, 2));
		if (isset($arFields['LID']))
			$CACHE_MANAGER->Clean("currency_currency_list_".substr($arFields['LID'], 0, 2));

		return true;
	}

	
	/**
	 * <p>Функция удаляет языкозависимые свойства валюты currency для языка lang</p>
	 *
	 *
	 *
	 *
	 * @param string $currency  Код валюты для удаления.
	 *
	 *
	 *
	 * @param string $lang  Код языка.
	 *
	 *
	 *
	 * @return bool <p>Функция возвращает True в случае успешного удаления и False - в
	 * противном случае.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/ccurrencylang__delete.819c044d.php
	 * @author Bitrix
	 */
	public static function Delete($currency, $lang)
	{
		global $DB;
		global $stackCacheManager;
		global $CACHE_MANAGER;

		$stackCacheManager->Clear("currency_currency_lang");
		$CACHE_MANAGER->Clean("currency_currency_list");
		$CACHE_MANAGER->Clean("currency_currency_list_".substr($lang, 0, 2));

		$strSql = "DELETE FROM b_catalog_currency_lang ".
			"WHERE CURRENCY = '".$DB->ForSql($currency, 3)."' ".
			"	AND LID = '".$DB->ForSql($lang, 2)."' ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	
	/**
	 * <p>Функция возвращает массив языкозависимых параметров валюты currency для языка lang </p>
	 *
	 *
	 *
	 *
	 * @param string $currency  Код валюты, языкозависимые параметры которой нужны.
	 *
	 *
	 *
	 * @param string $lang  Код языка.
	 *
	 *
	 *
	 * @return array <p>Ассоциативный массив с ключами</p><table width="100%" class="tnormal"><tbody> <tr> <th
	 * width="20%">Ключ</th> <th>Описание</th> </tr> <tr> <td>CURRENCY</td> <td>Код валюты
	 * (трехсимвольный)</td> </tr> <tr> <td>LID</td> <td>Код языка.</td> </tr> <tr>
	 * <td>FORMAT_STRING</td> <td>Строка формата, в соответствии с которой выводится
	 * суммы в этой валюте на этом языке.</td> </tr> <tr> <td>FULL_NAME</td> <td>Полное
	 * название валюты.</td> </tr> <tr> <td>DEC_POINT</td> <td>Символ, являющийся
	 * десятичной точкой при выводе сумм.</td> </tr> <tr> <td>THOUSANDS_SEP</td>
	 * <td>Разделитель тысяч при выводе.</td> </tr> <tr> <td>DECIMALS</td> <td>Количество
	 * знаков после запятой при выводе.</td> </tr> </tbody></table><p></p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$arFields = array(<br>    "FORMAT_STRING" =&gt; "# руб", // символ # будет заменен<br>                                // реальной суммой при выводе<br>    "FULL_NAME" =&gt; "Рубль",<br>    "DEC_POINT" =&gt; ".",<br>    "THOUSANDS_SEP" =&gt; "\xA0",  // неразрывный пробел<br>    "DECIMALS" =&gt; 2,<br>    "CURRENCY" =&gt; "RUB",<br>    "LID" =&gt; "ru"<br>);<br><br>// Если запись существует, то обновляем, иначе добавляем новую<br>$db_result_lang = CCurrencyLang::GetByID("RUB", "ru");<br>if ($db_result_lang)<br>    CCurrencyLang::Update("RUB", "ru", $arFields);<br>else<br>    CCurrencyLang::Add($arFields);<br>?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/ccurrencylang__getbyid.9828270a.php
	 * @author Bitrix
	 */
	public static function GetByID($currency, $lang)
	{
		global $DB;

		$strSql =
			"SELECT * ".
			"FROM b_catalog_currency_lang ".
			"WHERE CURRENCY = '".$DB->ForSql($currency, 3)."' ".
			"	AND LID = '".$DB->ForSql($lang, 2)."' ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	
	/**
	 * <p>Функция возвращает массив языкозависимых параметров валюты currency для языка lang.</p> <p>Функция аналогична функции <a href="http://dev.1c-bitrix.ruapi_help/currency/developer/ccurrencylang/ccurrencylang__getbyid.9828270a.php">CCurrencyLang::GetByID</a>, за исключение того, что возвращаемый результат в функции CCurrencyLang::GetCurrencyFormat кешируется. Поэтому повторный вызов функции с теми же кодами валюты и языка в рамках одной страницы не приводит к дополнительному запросу к базе данных. </p>
	 *
	 *
	 *
	 *
	 * @param string $currency  Код валюты, языкозависимые параметры которой нужны.
	 *
	 *
	 *
	 * @param string $lang = LANGUAGE_ID Код языка.
	 *
	 *
	 *
	 * @return array <p>Ассоциативный массив с ключами</p><table class="tnormal" width="100%"> <tr> <th
	 * width="20%">Ключ</th> <th>Описание</th> </tr> <tr> <td>CURRENCY</td> <td>Код валюты
	 * (трехсимвольный)</td> </tr> <tr> <td>LID</td> <td>Код языка.</td> </tr> <tr>
	 * <td>FORMAT_STRING</td> <td>Строка формата, в соответствии с которой выводится
	 * суммы в этой валюте на этом языке.</td> </tr> <tr> <td>FULL_NAME</td> <td>Полное
	 * название валюты.</td> </tr> <tr> <td>DEC_POINT</td> <td>Символ, являющийся
	 * десятичной точкой при выводе сумм.</td> </tr> <tr> <td>THOUSANDS_SEP</td>
	 * <td>Разделитель тысяч при выводе.</td> </tr> <tr> <td>DECIMALS</td> <td>Количество
	 * знаков после запятой при выводе.</td> </tr> </table><p></p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * function MyFormatCurrency($fSum, $strCurrency)
	 * {
	 *     if (!isset($fSum) || strlen($fSum)&lt;=0)
	 *         return "";
	 * 
	 *     $arCurFormat = CCurrencyLang::GetCurrencyFormat($strCurrency);
	 * 
	 *     if (!isset($arCurFormat["DECIMALS"]))
	 *         $arCurFormat["DECIMALS"] = 2;
	 * 
	 *     $arCurFormat["DECIMALS"] = IntVal($arCurFormat["DECIMALS"]);
	 * 
	 *     if (!isset($arCurFormat["DEC_POINT"]))
	 *         $arCurFormat["DEC_POINT"] = ".";
	 * 
	 *     if (!isset($arCurFormat["THOUSANDS_SEP"]))
	 *         $arCurFormat["THOUSANDS_SEP"] = "\\"."xA0";
	 * 
	 *     $tmpTHOUSANDS_SEP = $arCurFormat["THOUSANDS_SEP"];
	 *     eval("\$tmpTHOUSANDS_SEP = \"$tmpTHOUSANDS_SEP\";");
	 *     $arCurFormat["THOUSANDS_SEP"] = $tmpTHOUSANDS_SEP;
	 * 
	 *     if (!isset($arCurFormat["FORMAT_STRING"]))
	 *         $arCurFormat["FORMAT_STRING"] = "#";
	 * 
	 *     $num = number_format($fSum,
	 *                          $arCurFormat["DECIMALS"],
	 *                          $arCurFormat["DEC_POINT"],
	 *                          $arCurFormat["THOUSANDS_SEP"]);
	 * 
	 *     return str_replace("#",
	 *                        $num,
	 *                        $arCurFormat["FORMAT_STRING"]);
	 * }
	 * 
	 * echo "Сумма 11800.95 руб на текущем языке будет выглядеть так: ";
	 * echo MyFormatCurrency(11800.95, "RUR");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/ccurrencylang__getcurrencyformat.2a96359c.php
	 * @author Bitrix
	 */
	public static function GetCurrencyFormat($currency, $lang = LANGUAGE_ID)
	{
		global $DB;
		global $stackCacheManager;

		if (defined("CURRENCY_SKIP_CACHE") && CURRENCY_SKIP_CACHE)
		{
			$arCurrencyLang = CCurrencyLang::GetByID($currency, $lang);
		}
		else
		{
			$cacheTime = CURRENCY_CACHE_DEFAULT_TIME;
			if (defined("CURRENCY_CACHE_TIME"))
				$cacheTime = intval(CURRENCY_CACHE_TIME);

			$strCacheKey = $currency."_".$lang;

			$stackCacheManager->SetLength("currency_currency_lang", 20);
			$stackCacheManager->SetTTL("currency_currency_lang", $cacheTime);
			if ($stackCacheManager->Exist("currency_currency_lang", $strCacheKey))
			{
				$arCurrencyLang = $stackCacheManager->Get("currency_currency_lang", $strCacheKey);
			}
			else
			{
				$arCurrencyLang = CCurrencyLang::GetByID($currency, $lang);
				$stackCacheManager->Set("currency_currency_lang", $strCacheKey, $arCurrencyLang);
			}
		}

		return $arCurrencyLang;
	}

	public static function GetList(&$by, &$order, $currency = "")
	{
		global $DB;

		$strSql =
			"SELECT CURL.CURRENCY, CURL.LID, CURL.FORMAT_STRING, CURL.FULL_NAME, CURL.DEC_POINT, CURL.THOUSANDS_SEP, CURL.DECIMALS, CURL.THOUSANDS_VARIANT ".
			"FROM b_catalog_currency_lang CURL ";

		if (strlen($currency)>0)
		{
			$strSql .= "WHERE CURL.CURRENCY = '".$DB->ForSql($currency, 3)."' ";
		}

		if (strtolower($by) == "currency") $strSqlOrder = " ORDER BY CURL.CURRENCY ";
		elseif (strtolower($by) == "name") $strSqlOrder = " ORDER BY CURL.FULL_NAME ";
		else
		{
			$strSqlOrder = " ORDER BY CURL.LID ";
			$by = "lang";
		}

		if ($order=="desc")
			$strSqlOrder .= " desc ";
		else
			$order = "asc";

		$strSql .= $strSqlOrder;
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;
	}
}
?>