<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Currency;

Loc::loadMessages(__FILE__);

class CAllCurrencyLang
{
	const SEP_EMPTY = 'N';
	const SEP_DOT = 'D';
	const SEP_COMMA = 'C';
	const SEP_SPACE = 'S';
	const SEP_NBSPACE = 'B';

	static protected $arSeparators = array(
		self::SEP_EMPTY => '',
		self::SEP_DOT => '.',
		self::SEP_COMMA => ',',
		self::SEP_SPACE => ' ',
		self::SEP_NBSPACE => ' '
	);

	static protected $arDefaultValues = array(
		'FORMAT_STRING' => '#',
		'DEC_POINT' => '.',
		'THOUSANDS_SEP' => ' ',
		'DECIMALS' => 2,
		'THOUSANDS_VARIANT' => self::SEP_SPACE,
		'HIDE_ZERO' => 'N'
	);

	static protected $arCurrencyFormat = array();

	static protected $useHideZero = 0;

	
	/**
	* <p>Метод служит для возвращения после вызова метода <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/disableusehidezero.php">CurrencyLang::disableUseHideZero</a> в исходное состояние использования настройки <b>В публичной части не показывать незначащие нули в дробной части цены</b>. Метод статический.</p>
	*
	*
	* @return mixed <p>Нет.</p></bo<br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/enableusehidezero.php
	* @author Bitrix
	*/
	public static function enableUseHideZero()
	{
		if (defined('ADMIN_SECTION') && ADMIN_SECTION === true)
			return;
		self::$useHideZero++;
	}

	
	/**
	* <p>Метод служит для временного отключения использования настройки <b>В публичной части не показывать незначащие нули в дробной части цены</b>. Для возвращения в исходное состояние необходимо вызвать метод <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/enableusehidezero.php">CCurrencyLang::enableUseHideZero</a>. Метод статический.</p>
	*
	*
	* @return mixed <p>Нет.</p></bo<br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/disableusehidezero.php
	* @author Bitrix
	*/
	public static function disableUseHideZero()
	{
		if (defined('ADMIN_SECTION') && ADMIN_SECTION === true)
			return;
		self::$useHideZero--;
	}

	
	/**
	* <p>Метод проверяет снят ли флаг, запрещающий использование настройки <b>В публичной части не показывать незначащие нули в дробной части цены</b>. Метод статический.</p>
	*
	*
	* @return bool <p>Если флаг снят, то метод возвращает <i>true</i>, в противном случае -
	* <i>false</i>.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/isallowusehidezero.php
	* @author Bitrix
	*/
	public static function isAllowUseHideZero()
	{
		return (!(defined('ADMIN_SECTION') && ADMIN_SECTION === true) && self::$useHideZero >= 0);
	}

	public static function checkFields($action, &$fields, $currency = '', $language = '', $getErrors = false)
	{
		global $DB, $USER, $APPLICATION;

		$getErrors = ($getErrors === true);
		$action = strtoupper($action);
		if ($action != 'ADD' && $action != 'UPDATE')
			return false;
		if (!is_array($fields))
			return false;
		if ($action == 'ADD')
		{
			if (isset($fields['CURRENCY']))
				$currency = $fields['CURRENCY'];
			if (isset($fields['LID']))
				$language = $fields['LID'];
		}
		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		$language = Currency\CurrencyManager::checkLanguage($language);
		if ($currency === false || $language === false)
			return false;

		$errorMessages = array();

		$clearFields = array(
			'~CURRENCY',
			'~LID',
			'TIMESTAMP_X',
			'DATE_CREATE',
			'~DATE_CREATE',
			'~MODIFIED_BY',
			'~CREATED_BY',
			'~FORMAT_STRING',
			'~FULL_NAME',
			'~DEC_POINT',
			'~THOUSANDS_SEP',
			'~DECIMALS',
			'~THOUSANDS_VARIANT',
			'~HIDE_ZERO'
		);
		if ($action == 'UPDATE')
		{
			$clearFields[] = 'CREATED_BY';
			$clearFields[] = 'CURRENCY';
			$clearFields[] = 'LID';
		}
		$fields = array_filter($fields, 'CCurrencyLang::clearFields');
		foreach ($clearFields as &$fieldName)
		{
			if (isset($fields[$fieldName]))
				unset($fields[$fieldName]);
		}
		unset($fieldName, $clearFields);

		if ($action == 'ADD')
		{
			$defaultValues = self::$arDefaultValues;
			unset($defaultValues['FORMAT_STRING']);

			$fields = array_merge($defaultValues, $fields);
			unset($defaultValues);

			if (!isset($fields['FORMAT_STRING']) || empty($fields['FORMAT_STRING']))
			{
				$errorMessages[] = array(
					'id' => 'FORMAT_STRING', 'text' => Loc::getMessage('BT_CUR_LANG_ERR_FORMAT_STRING_IS_EMPTY', array('#LANG#' => $language))
				);
			}

			if (empty($errorMessages))
			{
				$fields['CURRENCY'] = $currency;
				$fields['LID'] = $language;
			}
		}
		if (empty($errorMessages))
		{
			if (isset($fields['FORMAT_STRING']) && empty($fields['FORMAT_STRING']))
			{
				$errorMessages[] = array(
					'id' => 'FORMAT_STRING', 'text' => Loc::getMessage('BT_CUR_LANG_ERR_FORMAT_STRING_IS_EMPTY', array('#LANG#' => $language))
				);
			}
			if (isset($fields['DECIMALS']))
			{
				$fields['DECIMALS'] = (int)$fields['DECIMALS'];
				if ($fields['DECIMALS'] < 0)
					$fields['DECIMALS'] = self::$arDefaultValues['DECIMALS'];
			}
			if (isset($fields['THOUSANDS_VARIANT']))
			{
				if (empty($fields['THOUSANDS_VARIANT']) || !isset(self::$arSeparators[$fields['THOUSANDS_VARIANT']]))
					$fields['THOUSANDS_VARIANT'] = false;
				else
					$fields['THOUSANDS_SEP'] = false;
			}
			if (isset($fields['HIDE_ZERO']))
				$fields['HIDE_ZERO'] = ($fields['HIDE_ZERO'] == 'Y' ? 'Y' : 'N');
		}
		$intUserID = 0;
		$boolUserExist = CCurrency::isUserExists();
		if ($boolUserExist)
			$intUserID = (int)$USER->GetID();
		$strDateFunction = $DB->GetNowFunction();
		$fields['~TIMESTAMP_X'] = $strDateFunction;
		if ($boolUserExist)
		{
			if (!isset($fields['MODIFIED_BY']))
				$fields['MODIFIED_BY'] = $intUserID;
			$fields['MODIFIED_BY'] = (int)$fields['MODIFIED_BY'];
			if ($fields['MODIFIED_BY'] <= 0)
				$fields['MODIFIED_BY'] = $intUserID;
		}
		if ($action == 'ADD')
		{
			$fields['~DATE_CREATE'] = $strDateFunction;
			if ($boolUserExist)
			{
				if (!isset($arFields['CREATED_BY']))
					$fields['CREATED_BY'] = $intUserID;
				$fields['CREATED_BY'] = (int)$fields['CREATED_BY'];
				if ($fields['CREATED_BY'] <= 0)
					$fields['CREATED_BY'] = $intUserID;
			}
		}

		if (!empty($errorMessages))
		{
			if ($getErrors)
				return $errorMessages;

			$obError = new CAdminException($errorMessages);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
			return false;
		}
		return true;
	}

	
	/**
	* <p>Метод добавляет новые языкозависимые параметры валюты. Метод динамичный.</p>
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
	* @return bool <p>Возвращает значение True в случае успешного добавления и False - в
	* противном случае </p> <a name="examples"></a>
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

		if (!self::checkFields('ADD', $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_currency_lang", $arFields);

		$strSql = "insert into b_catalog_currency_lang(".$arInsert[0].") values(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		Currency\CurrencyManager::clearCurrencyCache($arFields['LID']);

		return true;
	}

	
	/**
	* <p>Метод обновляет языкозависимые параметры валюты currency для языка lang. Метод динамичный.</p>
	*
	*
	* @param string $currency  Код валюты, языкозависимые параметры которой нужно обновить.
	*
	* @param string $lang  Код языка, для которого языкозависимые параметры валюты нужно
	* обновить.
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
	* @return bool <p>Возвращает значение True в случае успешного добавления и False - в
	* противном случае</p> <a name="examples"></a>
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

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		$lang = Currency\CurrencyManager::checkLanguage($lang);
		if ($currency === false || $lang === false)
			return false;

		if (!self::checkFields('UPDATE', $arFields, $currency, $lang))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_currency_lang", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "update b_catalog_currency_lang set ".$strUpdate." where CURRENCY = '".$DB->ForSql($currency)."' and LID='".$DB->ForSql($lang)."'";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			Currency\CurrencyManager::clearCurrencyCache($lang);
		}

		return true;
	}

	
	/**
	* <p>Метод удаляет языкозависимые свойства валюты currency для языка lang. Метод динамичный.</p>
	*
	*
	* @param string $currency  Код валюты для удаления.
	*
	* @param string $lang  Код языка.
	*
	* @return bool <p>Метод возвращает True в случае успешного удаления и False - в
	* противном случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/ccurrencylang__delete.819c044d.php
	* @author Bitrix
	*/
	static public function Delete($currency, $lang)
	{
		global $DB;

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		$lang = Currency\CurrencyManager::checkLanguage($lang);
		if ($currency === false || $lang === false)
			return false;

		Currency\CurrencyManager::clearCurrencyCache($lang);

		$strSql = "delete from b_catalog_currency_lang where CURRENCY = '".$DB->ForSql($currency)."' and LID = '".$DB->ForSql($lang)."'";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	
	/**
	* <p>Метод возвращает массив языкозависимых параметров валюты currency для языка lang. Метод динамичный.</p>
	*
	*
	* @param string $currency  Код валюты, языкозависимые параметры которой нужны.
	*
	* @param string $lang  Код языка.
	*
	* @return array <p>Ассоциативный массив с ключами</p> <table width="100%" class="tnormal"><tbody> <tr> <th
	* width="20%">Ключ</th> <th>Описание</th> </tr> <tr> <td>CURRENC
/**
 * <b>CCurrencyLang</b> - класс для работы с языкозависимыми параметрами валют (название, формат и пр.) 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/index.php
 * @author Bitrix
 */
Y</td> <td>Код валюты
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

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		$lang = Currency\CurrencyManager::checkLanguage($lang);
		if ($currency === false || $lang === false)
			return false;

		$strSql = "select * from b_catalog_currency_lang where CURRENCY = '".$DB->ForSql($currency)."' and LID = '".$DB->ForSql($lang)."'";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	
	/**
	* <p>Метод возвращает массив языкозависимых параметров валюты currency для языка lang.</p> <p>Метод аналогичен методу <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/ccurrencylang__getbyid.9828270a.php">CCurrencyLang::GetByID</a>, за исключение того, что возвращаемый результат в методе CCurrencyLang::GetCurrencyFormat кешируется. Поэтому повторный вызов метода с теми же кодами валюты и языка в рамках одной страницы не приводит к дополнительному запросу к базе данных. Метод динамичный.</p>
	*
	*
	* @param string $currency  Код валюты, языкозависимые параметры которой нужны.
	*
	* @param string $lang = LANGUAGE_ID Код языка. Необязательный параметр.
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

	public static function GetSeparators()
	{
		return self::$arSeparators;
	}

	public static function GetSeparatorTypes($boolFull = false)
	{
		$boolFull = (true == $boolFull);
		if ($boolFull)
		{
			return array(
				self::SEP_EMPTY => Loc::getMessage('BT_CUR_LANG_SEP_VARIANT_EMPTY'),
				self::SEP_DOT => Loc::getMessage('BT_CUR_LANG_SEP_VARIANT_DOT'),
				self::SEP_COMMA => Loc::getMessage('BT_CUR_LANG_SEP_VARIANT_COMMA'),
				self::SEP_SPACE => Loc::getMessage('BT_CUR_LANG_SEP_VARIANT_SPACE'),
				self::SEP_NBSPACE => Loc::getMessage('BT_CUR_LANG_SEP_VARIANT_NBSPACE')
			);
		}
		return array(
			self::SEP_EMPTY,
			self::SEP_DOT,
			self::SEP_COMMA,
			self::SEP_SPACE,
			self::SEP_NBSPACE
		);
	}

	public static function GetFormatTemplates()
	{
		$installCurrencies = CCurrency::getInstalledCurrencies();
		$templates = array();
		$templates[] = array(
			'TEXT' => '$1.234,10',
			'FORMAT' => '$#',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_DOT,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '$1 234,10',
			'FORMAT' => '$#',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_SPACE,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '1.234,10 USD',
			'FORMAT' => '# USD',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_DOT,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '1 234,10 USD',
			'FORMAT' => '# USD',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_SPACE,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '&euro;2.345,20',
			'FORMAT' => '&euro;#',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_DOT,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '&euro;2 345,20',
			'FORMAT' => '&euro;#',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_SPACE,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '2.345,20 EUR',
			'FORMAT' => '# EUR',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_DOT,
			'DECIMALS' => '2'
		);
		$templates[] = array(
			'TEXT' => '2 345,20 EUR',
			'FORMAT' => '# EUR',
			'DEC_POINT' => ',',
			'THOUSANDS_VARIANT' => self::SEP_SPACE,
			'DECIMALS' => '2'
		);

		if (in_array('RUB', $installCurrencies))
		{
			$rubTitle = Loc::getMessage('BT_CUR_LANG_CURRENCY_RUBLE');
			$templates[] = array(
				'TEXT' => '3.456,70 '.$rubTitle,
				'FORMAT' => '# '.$rubTitle,
				'DEC_POINT' => ',',
				'THOUSANDS_VARIANT' => self::SEP_DOT,
				'DECIMALS' => '2'
			);
			$templates[] = array(
				'TEXT' => '3 456,70 '.$rubTitle,
				'FORMAT' => '# '.$rubTitle,
				'DEC_POINT' => ',',
				'THOUSANDS_VARIANT' => self::SEP_SPACE,
				'DECIMALS' => '2'
			);
		}
		return $templates;
	}

	public static function GetFormatDescription($currency)
	{
		$boolAdminSection = (defined('ADMIN_SECTION') && ADMIN_SECTION === true);
		$currency = (string)$currency;

		if (!isset(self::$arCurrencyFormat[$currency]))
		{
			$arCurFormat = CCurrencyLang::GetCurrencyFormat($currency);
			if ($arCurFormat === false)
			{
				$arCurFormat = self::$arDefaultValues;
			}
			else
			{
				if (!isset($arCurFormat['DECIMALS']))
					$arCurFormat['DECIMALS'] = self::$arDefaultValues['DECIMALS'];
				$arCurFormat['DECIMALS'] = (int)$arCurFormat['DECIMALS'];
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
		return $arCurFormat;
	}

	
	/**
	* <p>Форматирует цену в соответствии с настройками валюты. В случае вызова в административной части дополнительно выполняет очистку формата от тегов и скриптов. Если метод вызывается в публичной части, то будет задействован параметр HIDE_ZERO, который отвечает за скрытие незначащих нулей в дробной части. Метод статический.</p> <p></p> <div class="note"> <b>Примечание:</b> используется взамен функций <a href="http://dev.1c-bitrix.ru/api_help/currency/functions/currencyformat.php">CurrencyFormat</a> и <a href="http://dev.1c-bitrix.ru/api_help/currency/functions/currencyformatnumber.php">CurrencyFormatNumber</a>, которые считаются устаревшими с версии модуля <b>14.0.0</b>. </div>
	*
	*
	* @param float $price  Цена (денежная сумма), которую нужно сконвертировать.
	*
	* @param string $currency  Код валюты.
	*
	* @param bool $useTemplate  Если указано <i>true</i>, то работает как <a
	* href="http://dev.1c-bitrix.ru/api_help/currency/functions/currencyformat.php">CurrencyFormat</a> и вызывается
	* событие <a href="http://dev.1c-bitrix.ru/api_help/currency/events/currencyformat.php">CurrencyFormat</a>.
	* Если задано <i>false</i>, то работает как <a
	* href="http://dev.1c-bitrix.ru/api_help/currency/functions/currencyformatnumber.php">CurrencyFormatNumber</a>.
	*
	* @return string <p>Возвращает отформатированную строку.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/currencyformat.php
	* @author Bitrix
	*/
	public static function CurrencyFormat($price, $currency, $useTemplate = true)
	{
		static $eventExists = null;

		$result = '';
		$useTemplate = !!$useTemplate;
		if ($useTemplate)
		{
			if ($eventExists === true || $eventExists === null)
			{
				foreach (GetModuleEvents('currency', 'CurrencyFormat', true) as $arEvent)
				{
					$eventExists = true;
					$result = ExecuteModuleEventEx($arEvent, array($price, $currency));
					if ($result != '')
						return $result;
				}
				if ($eventExists === null)
					$eventExists = false;
			}
		}

		if (!isset($price) || $price === '')
			return '';

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if ($currency === false)
			return '';

		$arCurFormat = (isset(self::$arCurrencyFormat[$currency]) ? self::$arCurrencyFormat[$currency] : self::GetFormatDescription($currency));
		$intDecimals = $arCurFormat['DECIMALS'];
		if (self::isAllowUseHideZero() && $arCurFormat['HIDE_ZERO'] == 'Y')
		{
			if (round($price, $arCurFormat["DECIMALS"]) == round($price, 0))
				$intDecimals = 0;
		}
		$price = number_format($price, $intDecimals, $arCurFormat['DEC_POINT'], $arCurFormat['THOUSANDS_SEP']);
		if ($arCurFormat['THOUSANDS_VARIANT'] == self::SEP_NBSPACE)
			$price = str_replace(' ', '&nbsp;', $price);

		return (
			$useTemplate
			? str_replace('#', $price, $arCurFormat['FORMAT_STRING'])
			: $price
		);
	}

	public static function checkLanguage($language)
	{
		return Currency\CurrencyManager::checkLanguage($language);
	}

	public static function isExistCurrencyLanguage($currency, $language)
	{
		global $DB;
		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		$language = Currency\CurrencyManager::checkLanguage($language);
		if ($currency === false || $language === false)
			return false;
		$query = "select LID from b_catalog_currency_lang where CURRENCY = '".$DB->ForSql($currency)."' and LID = '".$DB->ForSql($language)."'";
		$searchIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		if ($result = $searchIterator->Fetch())
		{
			return true;
		}
		return false;
	}

	protected static function clearFields($value)
	{
		return ($value !== null);
	}
}

class CCurrencyLang extends CAllCurrencyLang
{
}
?>