<?
IncludeModuleLangFile(__FILE__);


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
	const SEP_EMPTY = 'N';
	const SEP_DOT = 'D';
	const SEP_COMMA = 'C';
	const SEP_SPACE = 'S';
	const SEP_NBSPACE = 'B';

	static protected $arSeparators = array(
		SEP_EMPTY => '',
		SEP_DOT => '.',
		SEP_COMMA => ',',
		SEP_SPACE => ' ',
		SEP_NBSPACE => ' '
	);

	static protected $arDefaultValues = array(
		'FORMAT_STRING' => '#',
		'DEC_POINT' => '.',
		'THOUSANDS_SEP' => ' ',
		'DECIMALS' => 2,
		'THOUSANDS_VARIANT' => '',
		'HIDE_ZERO' => 'N'
	);

	static protected $arCurrencyFormat = array();

	
	/**
	* <p>Функция добавляет новые языкозависимые параметры валюты.</p>
	*
	*
	*
	*
	* @param array $arFields  <p>Ассоциативный массив параметров валюты, ключами которого
	* являются названия параметров, а значениями - значения
	* параметров.</p> <p>Допустимые ключи: <br> CURRENCY - код валюты, для которой
	* добавляются языкозависимые параметры (обязательно должен
	* присутствовать); <br> LID - код языка (обязательный); <br> FORMAT_STRING -
	* строка формата, в соответствии с которой выводится суммы в этой
	* валюте на этом языке (обязательный); <br> FULL_NAME - полное название
	* валюты; <br> DEC_POINT - символ, являющийся десятичной точкой при выводе
	* сумм (обязательный); <br> THOUSANDS_SEP - разделитель тысяч при выводе; <br>
	* DECIMALS - количество знаков после запятой при выводе (обязательный);
	* <br> HIDE_ZERO - (Y|N) определяет скрывать или показывать незначащие нули
	* в дробной части (результат будет виден только в публичной
	* части).</p>
	*
	*
	*
	* @return bool <p>Возвращает значение True в случае успешного добавления и False - в
	* противном случае </p> <a name="examples"></a>
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
	static public function Add($arFields)
	{
		global $DB;
		global $stackCacheManager;
		global $CACHE_MANAGER;

		$arInsert = $DB->PrepareInsert("b_catalog_currency_lang", $arFields);

		$strSql = "insert into b_catalog_currency_lang(".$arInsert[0].") values(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$stackCacheManager->Clear("currency_currency_lang");
		$CACHE_MANAGER->Clean("currency_currency_list");
		$CACHE_MANAGER->Clean("currency_currency_list_".substr($arFields['LID'], 0, 2));

		return true;
	}

	
	/**
	* <p>Функция обновляет языкозависимые параметры валюты currency для языка lang.</p>
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
	* параметров.</p> <p>Допустимые ключи: <br> CURRENCY - код валюты; <br> LID - код
	* языка; <br> FORMAT_STRING - строка формата, в соответствии с которой
	* выводится суммы в этой валюте на этом языке; <br> FULL_NAME - полное
	* название валюты; <br> DEC_POINT - символ, являющийся десятичной точкой
	* при выводе сумм; <br> THOUSANDS_SEP - разделитель тысяч при выводе; <br> DECIMALS
	* - количество знаков после запятой при выводе; <br> HIDE_ZERO - (Y|N)
	* определяет скрывать или показывать незначащие нули в дробной
	* части (результат будет виден только в публичной части). </p>
	*
	*
	*
	* @return bool <p>Возвращает значение True в случае успешного добавления и False - в
	* противном случае</p> <a name="examples"></a>
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
	static public function Update($currency, $lang, $arFields)
	{
		global $DB;
		global $stackCacheManager;
		global $CACHE_MANAGER;

		$strUpdate = $DB->PrepareUpdate("b_catalog_currency_lang", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "update b_catalog_currency_lang set ".$strUpdate." where CURRENCY = '".$DB->ForSql($currency, 3)."' and LID='".$DB->ForSql($lang, 2)."'";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$stackCacheManager->Clear("currency_currency_lang");
			$CACHE_MANAGER->Clean("currency_currency_list");
			$CACHE_MANAGER->Clean("currency_currency_list_".substr($lang, 0, 2));
			if (isset($arFields['LID']))
				$CACHE_MANAGER->Clean("currency_currency_list_".substr($arFields['LID'], 0, 2));
		}

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
	* противном случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/ccurrencylang__delete.819c044d.php
	* @author Bitrix
	*/
	static public function Delete($currency, $lang)
	{
		global $DB;
		global $stackCacheManager;
		global $CACHE_MANAGER;

		$stackCacheManager->Clear("currency_currency_lang");
		$CACHE_MANAGER->Clean("currency_currency_list");
		$CACHE_MANAGER->Clean("currency_currency_list_".substr($lang, 0, 2));

		$strSql = "delete from b_catalog_currency_lang where CURRENCY = '".$DB->ForSql($currency, 3)."' and LID = '".$DB->ForSql($lang, 2)."'";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	
	/**
	* <p>Функция возвращает массив языкозависимых параметров валюты currency для языка lang.</p>
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
	* @return array <p>Ассоциативный массив с ключами</p> <table width="100%" class="tnormal"><tbody> <tr> <th
	* width="20%">Ключ</th> <th>Описание</th> </tr> <tr> <td>CURRENCY</td> <td>Код валюты
	* (трехсимвольный).</td> </tr> <tr> <td>LID</td> <td>Код языка.</td> </tr> <tr>
	* <td>FORMAT_STRING</td> <td>Строка формата, в соответствии с которой выводится
	* суммы в этой валюте на этом языке.</td> </tr> <tr> <td>FULL_NAME</td> <td>Полное
	* название валюты.</td> </tr> <tr> <td>DEC_POINT</td> <td>Символ, являющийся
	* десятичной точкой при выводе сумм.</td> </tr> <tr> <td>THOUSANDS_SEP</td>
	* <td>Разделитель тысяч при выводе.</td> </tr> <tr> <td>DECIMALS</td> <td>Количество
	* знаков после запятой при выводе.</td> </tr> <tr> <td>HIDE_ZERO</td> <td>(Y|N)
	* Определяет скрывать или показывать незначащие нули в дробной
	* части (результат будет виден только в публичной части).</td> </tr>
	* </tbody></table> <p></p><a name="examples"></a>
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
	static public function GetByID($currency, $lang)
	{
		global $DB;

		$strSql = "select * from b_catalog_currency_lang where CURRENCY = '".$DB->ForSql($currency, 3)."' and LID = '".$DB->ForSql($lang, 2)."'";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	
	/**
	* <p>Функция возвращает массив языкозависимых параметров валюты currency для языка lang.</p> <p>Функция аналогична функции <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/ccurrencylang__getbyid.9828270a.php">CCurrencyLang::GetByID</a>, за исключение того, что возвращаемый результат в функции CCurrencyLang::GetCurrencyFormat кешируется. Поэтому повторный вызов функции с теми же кодами валюты и языка в рамках одной страницы не приводит к дополнительному запросу к базе данных. </p>
	*
	*
	*
	*
	* @param string $currency  Код валюты, языкозависимые параметры которой нужны.
	*
	*
	*
	* @param string $lang = LANGUAGE_ID Код языка. Необязательный параметр.
	*
	*
	*
	* @return array <p>Ассоциативный массив с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="20%">Ключ</th> <th>Описание</th> </tr> <tr> <td>CURRENCY</td> <td>Код валюты
	* (трехсимвольный)</td> </tr> <tr> <td>LID</td> <td>Код языка.</td> </tr> <tr>
	* <td>FORMAT_STRING</td> <td>Строка формата, в соответствии с которой выводится
	* суммы в этой валюте на этом языке.</td> </tr> <tr> <td>FULL_NAME</td> <td>Полное
	* название валюты.</td> </tr> <tr> <td>DEC_POINT</td> <td>Символ, являющийся
	* десятичной точкой при выводе сумм.</td> </tr> <tr> <td>THOUSANDS_SEP</td>
	* <td>Разделитель тысяч при выводе.</td> </tr> <tr> <td>DECIMALS</td> <td>Количество
	* знаков после запятой при выводе.</td> </tr> <tr> <td>HIDE_ZERO</td> <td>(Y|N)
	* Определяет скрывать или показывать незначащие нули в дробной
	* части (результат будет виден только в публичной части).</td> </tr> </table>
	* <p></p><a name="examples"></a>
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
	static public function GetCurrencyFormat($currency, $lang = LANGUAGE_ID)
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

	static public function GetList(&$by, &$order, $currency = "")
	{
		global $DB;

		$strSql = "select CURL.* from b_catalog_currency_lang CURL ";

		if ('' != $currency)
		{
			$strSql .= "where CURL.CURRENCY = '".$DB->ForSql($currency, 3)."' ";
		}

		if (strtolower($by) == "currency") $strSqlOrder = " order by CURL.CURRENCY ";
		elseif (strtolower($by) == "name") $strSqlOrder = " order by CURL.FULL_NAME ";
		else
		{
			$strSqlOrder = " order BY CURL.LID ";
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

	public static function GetDefaultValues()
	{
		return self::$arDefaultValues;
	}

	public static function GetSeparatorTypes($boolFull = false)
	{
		$boolFull = (true == $boolFull);
		if ($boolFull)
		{
			return array(
				SEP_EMPTY => GetMessage('BT_CUR_LANG_SEP_VARIANT_EMPTY'),
				SEP_DOT => GetMessage('BT_CUR_LANG_SEP_VARIANT_DOT'),
				SEP_COMMA => GetMessage('BT_CUR_LANG_SEP_VARIANT_COMMA'),
				SEP_SPACE => GetMessage('BT_CUR_LANG_SEP_VARIANT_SPACE'),
				SEP_NBSPACE => GetMessage('BT_CUR_LANG_SEP_VARIANT_NBSPACE')
			);
		}
		return array(
			SEP_EMPTY,
			SEP_DOT,
			SEP_COMMA,
			SEP_SPACE,
			SEP_NBSPACE
		);
	}

	
	/**
	* <p>Форматирует цену в соответствии с настройками валюты. В случае вызова в административной части дополнительно выполняет очистку формата от тегов и скриптов. Если метод вызывается в публичной части, то будет задействован параметр HIDE_ZERO, который отвечает за скрытие незначащих нулей в дробной части.</p> <p><b>Примечание:</b> используется взамен функций <a href="http://dev.1c-bitrix.ru/api_help/currency/functions/currencyformat.php">CurrencyFormat</a> и <a href="http://dev.1c-bitrix.ru/api_help/currency/functions/currencyformatnumber.php">CurrencyFormatNumber</a>, которые считаются устаревшими с версии модуля <b>14.0.0</b>. </p>
	*
	*
	*
	*
	* @param float $price  Цена (денежная сумма), которую нужно сконвертировать.
	*
	*
	*
	* @param string $currency  Код валюты.
	*
	*
	*
	* @param bool $useTemplate  Если указано <i>true</i>, то работает как <a
	* href="http://dev.1c-bitrix.ru/api_help/currency/functions/currencyformat.php">CurrencyFormat</a> и вызывается
	* событие <a href="http://dev.1c-bitrix.ru/api_help/currency/events/currencyformat.php">CurrencyFormat</a>.
	* Если задано <i>false</i>, то работает как <a
	* href="http://dev.1c-bitrix.ru/api_help/currency/functions/currencyformatnumber.php">CurrencyFormatNumber</a>.
	*
	*
	*
	* @return string <p>Возвращает отформатированную строку.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/currencyformat.php
	* @author Bitrix
	*/
	public static function CurrencyFormat($price, $currency, $useTemplate)
	{
		$boolAdminSection = (defined('ADMIN_SECTION') && true === ADMIN_SECTION);
		$result = '';
		$useTemplate = !!$useTemplate;
		if ($useTemplate)
		{
			foreach(GetModuleEvents('currency', 'CurrencyFormat', true) as $arEvent)
			{
				$result = ExecuteModuleEventEx($arEvent, array($price, $currency));
			}
		}
		if ('' != $result)
			return $result;

		if (!isset($price) || '' === $price)
			return '';

		$currency = (string)$currency;

		if (!isset(self::$arCurrencyFormat[$currency]))
		{
			$arCurFormat = CCurrencyLang::GetCurrencyFormat($currency);
			if (false === $arCurFormat)
			{
				$arCurFormat = self::$arDefaultValues;
			}
			else
			{
				if (!isset($arCurFormat['DECIMALS']))
					$arCurFormat['DECIMALS'] = self::$arDefaultValues['DECIMALS'];
				$arCurFormat['DECIMALS'] = intval($arCurFormat['DECIMALS']);
				if (!isset($arCurFormat['DEC_POINT']))
					$arCurFormat['DEC_POINT'] = self::$arDefaultValues['DEC_POINT'];
				if (!empty($arCurFormat['THOUSANDS_VARIANT']) && isset(self::$arSeparators[$arCurFormat['THOUSANDS_VARIANT']]))
				{
					$arCurFormat['THOUSANDS_SEP'] = self::$arSeparators[$arCurFormat['THOUSANDS_VARIANT']];
				}
				elseif (!isset($arCurFormat['THOUSANDS_SEP']))
				{
					$arCurFormat['THOUSANDS_SEP'] = self::$arDefaultValues['THOUSANDS_SEP'];
				}
				if (!isset($arCurFormat['FORMAT_STRING']))
				{
					$arCurFormat['FORMAT_STRING'] = self::$arDefaultValues['FORMAT_STRING'];
				}
				elseif ($boolAdminSection)
				{
					$arCurFormat["FORMAT_STRING"] = strip_tags(preg_replace(
						'#<script[^>]*?>.*?</script[^>]*?>#is',
						'',
						$arCurFormat["FORMAT_STRING"]
					));
				}
				if (!isset($arCurFormat['HIDE_ZERO']) || empty($arCurFormat['HIDE_ZERO']))
					$arCurFormat['HIDE_ZERO'] = self::$arDefaultValues['HIDE_ZERO'];
			}
			self::$arCurrencyFormat[$currency] = $arCurFormat;
		}
		else
		{
			$arCurFormat = self::$arCurrencyFormat[$currency];
		}
		$intDecimals = $arCurFormat['DECIMALS'];
		if (!$boolAdminSection && 'Y' == $arCurFormat['HIDE_ZERO'])
		{
			if (round($price, $arCurFormat["DECIMALS"]) == round($price, 0))
				$intDecimals = 0;
		}
		$price = number_format($price, $intDecimals, $arCurFormat['DEC_POINT'], $arCurFormat['THOUSANDS_SEP']);
		if (self::SEP_NBSPACE == $arCurFormat['THOUSANDS_VARIANT'])
			$price = str_replace(' ', '&nbsp;', $price);

		return (
			$useTemplate
			? str_replace('#', $price, $arCurFormat['FORMAT_STRING'])
			: str_replace(',', '.', $price)
		);
	}
}
?>