<?
use Bitrix\Main;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Currency;

Loc::loadMessages(__FILE__);

class CAllCurrency
{
	protected static $currencyCache = array();

/*
* @deprecated deprecated since currency 9.0.0
* @see CCurrency::GetByID()
*/
	
	/**
	* <p>Метод возвращает массив языконезависимых параметров валюты по ее коду <b>currency</b>. Метод является оболочкой метода <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__getbyid.a0947d8b.php">CCurrency::GetByID</a>. Метод динамичный.</p> <p></p> <div class="note"> <b>Важно!</b> Метод устарел и не поддерживается с версии 9.0.0.</div>
	*
	*
	* @param string $currency  
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__getcurrency.205e6985.php
	* @author Bitrix
	* @deprecated deprecated since currency 9.0.0  ->  CCurrency::GetByID()
	*/
	static public function GetCurrency($currency)
	{
		return CCurrency::GetByID($currency);
	}

	
	/**
	* <p>Выполняет проверку полей валюты при добавлении или изменении. Метод динамичный.</p>
	*
	*
	* @param mixed $ACTION  Равен <b>ADD</b> или <b>UPDATE</b> с учетом регистра. Если значение в другом
	* регистре или другое значение - вернет <i>false</i> без текста ошибки
	* (exception). Если значение равно <b>UPDATE</b>, то дополнительно проверяется
	* <b>CurrencyID</b>. Если значение пустое - вернет ошибку, если не пустое, то
	* обрежет до 3 символов.
	*
	* @param &$arField $s  Ключи: <ul> <li> <b>CURRENCY</b> - (обязательный), обрезается до 3 символов.
	* Обязательно будет проверен, если присутствует в массиве (даже
	* если это обновление). При добавлении будет дополнительно
	* проверен на формат - 3 латинских символа. (При обновлении такая
	* проверка не выполняется в целях сохранения совместимости.) Если
	* формат верен, то будет выполнен поиск - не существует ли уже такая
	* валюта (БЕЗ УЧЕТА регистра). Если не существует - код валюты будет
	* приведен к верхнему регистру. При обновлении проверки
	* существования в методе CheckFields не производится.</li> <li> <b>AMOUNT_CNT</b> -
	* номинал (обязательный). Может быть только целым числом &gt; 0.</li> <li>
	* <b>AMOUNT</b> - базовый курс (обязательный). Берется, если нет курсов по
	* датам. Может быть только вещественным числом &gt; 0. </li> <li> <b>SORT</b> -
	* сортировка. Целое число. Приводится к типу целого.</li> <li> <b>NUMCODE</b> -
	* трехзначный цифровой код валюты.</li> <li> <b>BASE</b> - флаг (Y/N) является
	* ли валюта базовой.</li> <li> <b>CREATED_BY</b> - ID пользователя, добавившего
	* валюту.</li> <li> <b>MODIFIED_BY</b> - ID последнего пользователя, изменившего
	* валюту.</li> <li> <b>DATE_UPDATE</b> - время обновления - задается системой.
	* Если есть такой ключ в массиве - удаляется.</li> </ul>
	*
	* @param mixed $strCurrencyID = false Код обновляемой валюты. Необязательный параметр.
	*
	* @return boolean <p>В случае успеха возвращает <i>true</i>. В случае ошибки - <i>false</i>.
	* Текст ошибки можно получить через <code>$APPLICATION-&gt;GetException()</code>.</p> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* $arFields = array(
	* 		'CURRENCY' =&gt; 'руб',
	* 		'AMOUNT_CNT' =&gt; 1,
	* 		'AMOUNT' =&gt; 0
	* 	);
	* 	
	* 	$boolRes = CCurrency::CheckFields('ADD', $arFields);
	* 	if (!$boolRes)
	* 	{
	* 		if ($ex = $APPLICATION-&gt;GetException())
	* 		{
	* 			$strError = $ex-&gt;GetString();
	* 			ShowError($strError);
	* 		}
	* 	}
	* 	
	* 	//<
	* 	Вернет ошибки по полям CURRENCY и AMOUNT
	* 	>//
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/checkfields.php
	* @author Bitrix
	*/
	static public function CheckFields($ACTION, &$arFields, $strCurrencyID = false)
	{
		global $APPLICATION, $DB, $USER;

		$arMsg = array();

		$ACTION = strtoupper($ACTION);
		if ($ACTION != 'UPDATE' && $ACTION != 'ADD')
			return false;
		if (!is_array($arFields))
			return false;

		$defaultValues = array(
			'SORT' => 100,
			'BASE' => 'N'
		);

		$clearFields = array(
			'~CURRENCY',
			'~NUMCODE',
			'~AMOUNT_CNT',
			'~AMOUNT',
			'~BASE',
			'DATE_UPDATE',
			'DATE_CREATE',
			'~DATE_CREATE',
			'~MODIFIED_BY',
			'~CREATED_BY',
			'CURRENT_BASE_RATE',
			'~CURRENT_BASE_RATE'
		);
		if ($ACTION == 'UPDATE')
			$clearFields[] = 'CREATED_BY';
		$arFields = array_filter($arFields, 'CCurrency::clearFields');
		foreach ($clearFields as &$fieldName)
		{
			if (isset($arFields[$fieldName]))
				unset($arFields[$fieldName]);
		}
		unset($fieldName, $clearFields);

		if ($ACTION == 'ADD')
		{
			if (!isset($arFields['CURRENCY']))
			{
				$arMsg[] = array('id' => 'CURRENCY', 'text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_CUR_ID_ABSENT'));
			}
			elseif (!preg_match("~^[a-z]{3}$~i", $arFields['CURRENCY']))
			{
				$arMsg[] = array('id' => 'CURRENCY','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_CUR_ID_LAT_EXT'));
			}
			else
			{
				$db_result = $DB->Query("select 'x' FROM b_catalog_currency where UPPER(CURRENCY) = UPPER('".$DB->ForSql($arFields['CURRENCY'])."')");
				if ($db_result->Fetch())
				{
					$arMsg[] = array('id' => 'CURRENCY','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_CUR_ID_EXISTS'));
				}
				else
				{
					$arFields['CURRENCY'] = strtoupper($arFields['CURRENCY']);
				}
			}
			$arFields = array_merge($defaultValues, $arFields);
			if (!isset($arFields['AMOUNT_CNT']))
			{
				$arMsg[] = array('id' => 'AMOUNT_CNT','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_AMOUNT_CNT_ABSENT'));
			}
			if (!isset($arFields['AMOUNT']))
			{
				$arMsg[] = array('id' => 'AMOUNT','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_AMOUNT_ABSENT'));
			}
		}

		if ($ACTION == 'UPDATE')
		{
			$strCurrencyID = Currency\CurrencyManager::checkCurrencyID($strCurrencyID);
			if ($strCurrencyID === false)
			{
				$arMsg[] = array('id' => 'CURRENCY','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_CUR_ID_BAD'));
			}
			if (isset($arFields['CURRENCY']))
				unset($arFields['CURRENCY']);
		}

		if (empty($arMsg))
		{
			if (isset($arFields['AMOUNT_CNT']))
			{
				$arFields['AMOUNT_CNT'] = (int)$arFields['AMOUNT_CNT'];
				if ($arFields['AMOUNT_CNT'] <= 0)
				{
					$arMsg[] = array('id' => 'AMOUNT_CNT','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_AMOUNT_CNT_BAD'));
				}
			}
			if (isset($arFields['AMOUNT']))
			{
				$arFields['AMOUNT'] = (float)$arFields['AMOUNT'];
				if ($arFields['AMOUNT'] <= 0)
				{
					$arMsg[] = array('id' => 'AMOUNT','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_AMOUNT_BAD'));
				}
			}
			if (isset($arFields['SORT']))
			{
				$arFields['SORT'] = (int)$arFields['SORT'];
				if ($arFields['SORT'] <= 0)
				{
					$arFields['SORT'] = 100;
				}
			}
			if (isset($arFields['BASE']))
			{
				$arFields['BASE'] = ((string)$arFields['BASE'] === 'Y' ? 'Y' : 'N');
			}
			if (isset($arFields['NUMCODE']))
			{
				$arFields['NUMCODE'] = (string)$arFields['NUMCODE'];
				if ($arFields['NUMCODE'] === '')
				{
					unset($arFields['NUMCODE']);
				}
				elseif (!preg_match("~^[0-9]{3}$~", $arFields['NUMCODE']))
				{
					$arMsg[] = array('id' => 'NUMCODE','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_NUMCODE_IS_BAD'));
				}
			}
		}

		$intUserID = 0;
		$boolUserExist = self::isUserExists();
		if ($boolUserExist)
			$intUserID = (int)$USER->GetID();
		$strDateFunction = $DB->GetNowFunction();
		$arFields['~DATE_UPDATE'] = $strDateFunction;
		if ($boolUserExist)
		{
			if (!isset($arFields['MODIFIED_BY']))
				$arFields['MODIFIED_BY'] = $intUserID;
			$arFields['MODIFIED_BY'] = (int)$arFields['MODIFIED_BY'];
			if ($arFields['MODIFIED_BY'] <= 0)
				$arFields['MODIFIED_BY'] = $intUserID;
		}
		if ($ACTION == 'ADD')
		{
			$arFields['~DATE_CREATE'] = $strDateFunction;
			if ($boolUserExist)
			{
				if (!isset($arFields['CREATED_BY']))
					$arFields['CREATED_BY'] = $intUserID;
				$arFields['CREATED_BY'] = (int)$arFields['CREATED_BY'];
				if ($arFields['CREATED_BY'] <= 0)
					$arFields['CREATED_BY'] = $intUserID;
			}
		}

		if (isset($arFields['LANG']))
		{
			if (empty($arFields['LANG']) || !is_array($arFields['LANG']))
			{
				$arMsg[] = array('id' => 'LANG','text' => Loc::getMessage('BT_MOD_CURR_ERR_CURR_LANG_BAD'));
			}
			else
			{
				$langSettings = array();
				$currency = ($ACTION == 'ADD' ? $arFields['CURRENCY'] : $strCurrencyID);
				foreach ($arFields['LANG'] as $lang => $settings)
				{
					if (empty($settings) || !is_array($settings))
						continue;
					$langAction = 'ADD';
					if ($ACTION == 'UPDATE')
					{
						$langAction = (CCurrencyLang::isExistCurrencyLanguage($currency, $lang) ? 'UPDATE' : 'ADD');
					}
					$checkLang = CCurrencyLang::checkFields($langAction, $settings, $currency, $lang, true);
					$settings['CURRENCY'] = $currency;
					$settings['LID'] = $lang;
					$settings['IS_EXIST'] = ($langAction == 'ADD' ? 'N' : 'Y');
					$langSettings[$lang] = $settings;
					if (is_array($checkLang))
					{
						$arMsg = array_merge($arMsg, $checkLang);
					}
				}
				$arFields['LANG'] = $langSettings;
				unset($settings, $lang, $currency, $langSettings);
			}
		}

		if (!empty($arMsg))
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
			return false;
		}
		return true;
	}

	
	/**
	* <p>Метод добавляет новую валюту, если ее еще не было. После добавления новой валюты необходимо установить ее языкозависимые параметры в помощью метода <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/ccurrencylang__add.7ce2349e.php">CCurrencyLang::Add</a>. Метод динамичный.</p>
	*
	*
	* @param array $arFields  <p>Ассоциативный массив параметров валюты, в котором ключами
	* являются названия параметров, а значениями - значения
	* параметров.</p> <p>Допустимые названия параметров:</p> <ul> <li> <b>CURRENCY</b> -
	* трехсимвольный код валюты (обязательный);</li> <li> <b>AMOUNT_CNT</b> -
	* количество единиц валюты по-умолчанию, которое учавствует в
	* задании курса валюты (например, если 10 Датских крон стоят 48.7
	* рублей, то 10 - это количество единиц);</li> <li> <b>AMOUNT</b> - курс валюты
	* по-умолчанию (одна из валют сайта должна иметь курс 1, она
	* называется базовой, остальные валюты имеют курс относительно
	* базовой валюты);</li> <li> <b>SORT</b> - порядок сортировки;</li> <li> <b>NUMCODE</b> -
	* трехзначный цифровой код валюты;</li> <li> <b>BASE</b> - флаг (Y/N) является
	* ли валюта базовой (если для добавляемой валюты указано <b>Y</b> и в
	* системе уже есть некоторая базовая валюта, то флаг с существующей
	* валюты будет снят и <b>AMOUNT</b> у базовой валюты станет равен <b>1</b>);</li>
	* <li> <b>CREATED_BY</b> - ID пользователя, добавившего валюту;</li> <li> <b>MODIFIED_BY</b>
	* - ID последнего пользователя, изменившего валюту.</li> </ul>
	*
	* @return string <p>Метод возвращает код добавленной валюты (сбрасывает кеш
	* <b>currency_currency_list</b> и <b>currency_base_currency</b> в случае успешного добавления).
	* Или <i>false</i> в случае ошибки (текст ошибки берётся через
	* <code>$APPLICATION-&gt;GetException()</code>).</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Добавим новую валюту "Исландские кроны"
	* $arFields = array(
	*    "CURRENCY" =&gt; "ISK",
	*    "AMOUNT" =&gt; 44.8378,
	*    "AMOUNT_CNT" =&gt; 100,
	*    "SORT" =&gt; 250
	* );
	* CCurrency::Add($arFields);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__add.17dc7357.php
	* @author Bitrix
	*/
	static public function Add($arFields)
	{
		global $DB;

		foreach (GetModuleEvents("currency", "OnBeforeCurrencyAdd", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields))===false)
				return false;
		}

		if (!CCurrency::CheckFields('ADD', $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_currency", $arFields);

		$strSql = "insert into b_catalog_currency(".$arInsert[0].") values(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if (isset($arFields['LANG']))
		{
			foreach ($arFields['LANG'] as $lang => $settings)
			{
				if ($settings['IS_EXIST'] == 'N')
				{
					CCurrencyLang::Add($settings);
				}
				else
				{
					CCurrencyLang::Update($arFields['CURRENCY'], $lang, $settings);
				}
			}
			unset($settings, $lang);
		}

		self::updateBaseRates('', $arFields['CURRENCY']);
		Currency\CurrencyManager::clearCurrencyCache();

		foreach (GetModuleEvents("currency", "OnCurrencyAdd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($arFields['CURRENCY'], $arFields));
		}
		if (isset(self::$currencyCache[$arFields['CURRENCY']]))
			unset(self::$currencyCache[$arFields['CURRENCY']]);
		return $arFields["CURRENCY"];
	}

	
	/**
	* <p>Метод изменяет параметры валюты <b>currency</b> на параметры, указанные в массиве <i>arFields</i>. Языкозависимые параметры (название, формат и прочее) обновляются отдельно, через класс <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencylang/ccurrencylang__update.8a1e7a7b.php">CCurrencyLang</a>. Метод динамичный.</p> <p>Сбрасывает кеш <b>currency_currency_list</b> и <b>currency_base_currency</b> в случае успешного обновления (только если произошел запрос к базе). Так же сбросит тегированный кеш <b>currency_id_КОД_ВАЛЮТЫ</b>.</p>
	*
	*
	* @param string $currency  Код валюты, параметры которой нужно изменить.
	*
	* @param array $arFields  Массив новых параметров валюты. <ul> <li> <b>CURRENCY</b> - трехсимвольный
	* код валюты (обязательный). Должно совпадать с кодом <b>currency</b>
	* изменяемой валюты;</li> <li> <b>AMOUNT_CNT</b> - количество единиц валюты
	* по-умолчанию, которое участвует в задании курса валюты (например,
	* если 10 Датских крон стоят 48.7 рублей, то 10 - это количество
	* единиц);</li> <li> <b>AMOUNT</b> - курс валюты по-умолчанию (одна из валют
	* сайта должна иметь курс 1, она называется базовой, остальные
	* валюты имеют курс относительно базовой валюты);</li> <li> <b>SORT</b> -
	* порядок сортировки;</li> <li> <b>NUMCODE</b> - трехзначный цифровой код
	* валюты;</li> <li> <b>BASE</b> - флаг (Y/N) является ли валюта базовой;</li> <li>
	* <b>MODIFIED_BY</b> - ID последнего пользователя, изменившего валюту.</li>
	* <p>Если в массиве нет ни одного из полей, то обращения к базе данных
	* не будет, но вернет код валюты.</p> </ul>
	*
	* @return bool <p>Код валюты, параметры которой изменили, или <i>false</i> в случае
	* ошибки (текст получается через <code>$APPLICATION-&gt;GetException()</code>).</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__update.16586d51.php
	* @author Bitrix
	*/
	static public function Update($currency, $arFields)
	{
		global $DB;

		foreach (GetModuleEvents("currency", "OnBeforeCurrencyUpdate", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($currency, &$arFields))===false)
				return false;
		}

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if (!CCurrency::CheckFields('UPDATE', $arFields, $currency))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_currency", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "update b_catalog_currency set ".$strUpdate." where CURRENCY = '".$DB->ForSql($currency)."'";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			self::updateBaseRates('', $currency);
			Currency\CurrencyManager::clearTagCache($currency);
			if (isset(self::$currencyCache[$currency]))
				unset(self::$currencyCache[$currency]);
		}
		if (isset($arFields['LANG']))
		{
			foreach ($arFields['LANG'] as $lang => $settings)
			{
				if ($settings['IS_EXIST'] == 'N')
				{
					CCurrencyLang::Add($settings);
				}
				else
				{
					CCurrencyLang::Update($currency, $lang, $settings);
				}
			}
			unset($settings, $lang);
		}
		if (!empty($strUpdate) || isset($arFields['LANG']))
		{
			Currency\CurrencyManager::clearCurrencyCache();
		}

		foreach (GetModuleEvents("currency", "OnCurrencyUpdate", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($currency, $arFields));
		}

		return $currency;
	}

	
	/**
	* <p>Метод удаляет валюту с кодом <b>currency</b>. Удаляются в том числе все введенные курсы для этой валюты, а так же языкозависимые свойства валюты. Метод динамичный.</p> <p>Перед удалением вызывает событие <b>OnBeforeCurrencyDelete</b>, где можно отменить удаление. Формат обработчика: <code>boolean Handler($currency)</code>.</p> <p>Сбрасывает кеш <b>currency_currency_list</b> и <b>currency_base_currency</b>. Так же сбросит тегированный кеш <b>currency_id_КОД_ВАЛЮТЫ</b>.</p>
	*
	*
	* @param string $currency  Код валюты.
	*
	* @return bool <p>Метод возвращает <i>True</i> в случае успешного удаления и <i>False</i> - в
	* прот
/**
 * <b>CCurrency</b> - класс для управления валютами: добавление, удаление, перечисление. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/index.php
 * @author Bitrix
 */
ивном случае </p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* CCurrency::Delete("USD");
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__delete.140a51ba.php
	* @author Bitrix
	*/
	static public function Delete($currency)
	{
		global $DB;

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if ($currency === false)
			return false;

		foreach(GetModuleEvents("currency", "OnBeforeCurrencyDelete", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array($currency))===false)
				return false;
		}

		$sqlCurrency = $DB->ForSQL($currency);

		$query = "select CURRENCY, BASE from b_catalog_currency where CURRENCY = '".$sqlCurrency."'";
		$currencyIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		if ($existCurrency = $currencyIterator->Fetch())
		{
			if ($existCurrency['BASE'] == 'Y')
				return false;
		}
		else
		{
			return false;
		}

		foreach(GetModuleEvents("currency", "OnCurrencyDelete", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($currency));
		}

		Currency\CurrencyManager::clearCurrencyCache();

		$DB->Query("delete from b_catalog_currency_lang where CURRENCY = '".$sqlCurrency."'", true);
		$DB->Query("delete from b_catalog_currency_rate where CURRENCY = '".$sqlCurrency."'", true);

		Currency\CurrencyManager::clearTagCache($currency);

		if (isset(self::$currencyCache[$currency]))
			unset(self::$currencyCache[$currency]);
		return $DB->Query("delete from b_catalog_currency where CURRENCY = '".$sqlCurrency."'", true);
	}

	
	/**
	* <p>Метод возвращает массив языконезависимых параметров валюты по ее коду <b>currency</b>. Метод динамичный.</p>
	*
	*
	* @param string $currency  Код валюты.
	*
	* @return array <p>Ассоциативный массив с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="20%">Ключ</th> <th>Описание</th> </tr> <tr> <td>CURRENCY</td> <td>Код валюты
	* (трехсимвольный)</td> </tr> <tr> <td>AMOUNT_CNT</td> <td>Количество единиц валюты
	* по-умолчанию, которое учавствует в задании курса валюты
	* (например, если 10 Датских крон стоят 48.7 рублей, то 10 - это
	* количество единиц)</td> </tr> <tr> <td>AMOUNT</td> <td>Курс валюты по-умолчанию
	* (одна из валют сайта должна иметь курс 1, она называется базовой,
	* остальные валюты имеют курс относительно базовой валюты)</td> </tr>
	* <tr> <td>SORT</td> <td>Порядок сортировки.</td> </tr> <tr> <td>BASE</td> <td>Флаг (Y/N)
	* является ли валюта базовой.</td> </tr> <tr> <td>NUMCODE</td> <td>Трехзначный
	* цифровой код валюты.</td> </tr> <tr> <td>CREATED_BY</td> <td>ID пользователя,
	* добавившего валюту.</td> </tr> <tr> <td>MODIFIED_BY</td> <td>ID последнего
	* пользователя, изменившего валюту.</td> </tr> <tr> <td>DATE_UPDATE_FORMAT</td>
	* <td>Отформатированная в соответствии с настройками сайта дата
	* последнего изменения записи.</td> </tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (!($ar_usd_cur = CCurrency::GetByID("USD")))
	* {
	*     echo "Валюта USD не найдена";
	* }
	* else
	* {
	*     echo "Валюта USD имеет параметры:&lt;pre&gt;";
	*     print_r($ar_usd_cur);
	*     echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__getbyid.a0947d8b.php
	* @author Bitrix
	*/
	static public function GetByID($currency)
	{
		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if ($currency === false)
			return false;

		if (!isset(self::$currencyCache[$currency]))
		{
			self::$currencyCache[$currency] = false;
			$currencyIterator = Currency\CurrencyTable::getById($currency);
			if ($currencyData = $currencyIterator->fetch())
			{
				$currencyData['DATE_UPDATE_FORMAT'] = (
					$currencyData['DATE_UPDATE'] instanceof Main\Type\DateTime
					? $currencyData['DATE_UPDATE']->toString()
					: null
				);
				$currencyData['DATE_CREATE_FORMAT'] = (
					$currencyData['DATE_CREATE'] instanceof Main\Type\DateTime
					? $currencyData['DATE_CREATE']->toString()
					: null
				);
				unset($currencyData['DATE_UPDATE'], $currencyData['DATE_CREATE']);
				self::$currencyCache[$currency] = $currencyData;
			}
			unset($currencyData, $currencyIterator);
		}
		return self::$currencyCache[$currency];
	}

	
	/**
	* <p>Метод возвращает код базовой валюты.</p> <p>Одна из валют сайта должна иметь курс "по-умолчанию" равный 1. Эта валюта называется базовой. Курс всех валют, кроме базовой, задается относительно базовой валюты. Метод динамичный.</p>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__getbasecurrency.98c474fc.php
	* @author Bitrix
	*/
	static public function GetBaseCurrency()
	{
		return Currency\CurrencyManager::getBaseCurrency();
	}

	static public function SetBaseCurrency($currency)
	{
		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if ($currency === false)
			return false;

		$currencyIterator = Currency\CurrencyTable::getList(array(
			'select' => array('CURRENCY', 'BASE'),
			'filter' => array('=CURRENCY' => $currency)
		));
		if ($existCurrency = $currencyIterator->fetch())
		{
			if ($existCurrency['BASE'] == 'Y')
				return true;
			$result = self::updateBaseCurrency($currency);
			if ($result)
				Currency\CurrencyManager::clearCurrencyCache();
			return $result;
		}
		return false;
	}

	
	/**
	* <p>Метод для формирования готового кода выпадающего списка (select) валют. Список валют при построении списка кешируется. Поэтому вывод дополнительных выпадающих списков в рамках одной страницы не приводит к дополнительным запросам к базе данных. Метод динамичный.</p>
	*
	*
	* @param string $sFieldName  Название выпадающего списка.
	*
	* @param string $sValue  Код валюты, которую нужно установить в списке выбранной.
	*
	* @param string $sDefaultValue = "" Особое значение в списке, которое не соответствует ни одной
	* валюте. Например, значение "Все" или "Не установлено". В этом случае
	* код валюты в списке будет пустым. Если параметр пуст, то особое
	* значение не отображается. Необязательный параметр.
	*
	* @param bool $bFullName = True Выводить ли полное имя валюты. Если параметр равен False, то в списке
	* выводятся только коды валют. Необязательный параметр.
	*
	* @param string $JavaFunc = "" Название JavaScript функции, которая вызывается на событие OnChange
	* списка. Если значение пустое, то функция не вызывается.
	* Необязательный параметр.
	*
	* @param string $sAdditionalParams = "" Строка произвольных дополнительных атрибутов тега &lt;select&gt;
	* Необязательный параметр.
	*
	* @return string <p>Строка, содержащая код для формирования выпадающего списка
	* валют </p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>// Выведем выпадающий список валют <br>// с именем CURRENCY_DEFAULT, выбранной по умолчанию,<br>// валютой RUB, без особого значения<br><br>echo CCurrency::SelectBox("CURRENCY_DEFAULT",<br>                          "RUB",<br>                          "",<br>                          True, <br>                          "",<br>                          "class='typeselect'");<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__selectbox.a14c0d6e.php
	* @author Bitrix
	*/
	static public function SelectBox($sFieldName, $sValue, $sDefaultValue = '', $bFullName = true, $JavaFunc = '', $sAdditionalParams = '')
	{
		$s = '<select name="'.$sFieldName.'"';
		if ('' != $JavaFunc) $s .= ' onchange="'.$JavaFunc.'"';
		if ('' != $sAdditionalParams) $s .= ' '.$sAdditionalParams.' ';
		$s .= '>';
		$s1 = '';
		$found = false;

		$currencyList = Currency\CurrencyManager::getCurrencyList();
		if (!empty($currencyList) && is_array($currencyList))
		{
			foreach ($currencyList as $currency => $title)
			{
				$found = ($currency == $sValue);
				$s1 .= '<option value="'.$currency.'"'.($found ? ' selected' : '').'>'.($bFullName ? htmlspecialcharsex($title) : $currency).'</option>';
			}
		}
		if ('' != $sDefaultValue)
			$s .= '<option value=""'.($found ? '' : ' selected').'>'.htmlspecialcharsex($sDefaultValue).'</option>';
		return $s.$s1.'</select>';
	}

	
	/**
	* <p>Метод возвращает список валют, отсортированный по полю из параметра by в направлении order. Языкозависимые параметры валют берутся для языка, указанного в параметре lang (по умолчанию равен текущему языку). Метод динамичный.</p>
	*
	*
	* @param string &$by  Переменная, содержащая порядок сортировки валют. Допустимые
	* значения переменной:<br> currency - код валюты<br> name - название валюты на
	* языке lang<br> sort - индекс сортировки (по-умолчанию)
	*
	* @param string &$order  Переменная, содержащая направление сортировки. Допустимые
	* значения:<br> asc - по возрастанию значений (по-умолчанию) <br> desc - по
	* убыванию значений.
	*
	* @param string $lang = LANGUAGE_ID Код языка, для которого выбираются языкозависимые параметры
	* валют. Необязательный параметр.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, каждая запись в котором
	* представляет собой массив с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="20%">Ключ</th> <th>Описание</th> </tr> <tr> <td>CURRENCY</td> <td>Код валюты
	* (трехсимвольный)</td> </tr> <tr> <td>AMOUNT_CNT</td> <td>Количество единиц валюты
	* по-умолчанию, которое учавствует в задании курса валюты
	* (например, если 10 Датских крон стоят 48.7 рублей, то 10 - это
	* количество единиц)</td> </tr> <tr> <td>AMOUNT</td> <td>Курс валюты по-умолчанию
	* (одна из валют сайта должна иметь курс 1, она называется базовой,
	* остальные валюты имеют курс относительно базовой валюты)</td> </tr>
	* <tr> <td>SORT</td> <td>Порядок сортировки.</td> </tr> <tr> <td>DATE_UPDATE</td> <td>Дата
	* последнего изменения записи (в формате базы данных).</td> </tr> <tr>
	* <td>BASE</td> <td>Флаг (Y/N) является ли валюта базовой.</td> </tr> <tr> <td>NUMCODE</td>
	* <td>Трехзначный цифровой код валюты.</td> </tr> <tr> <td>CREATED_BY</td> <td>ID
	* пользователя, добавившего валюту.</td> </tr> <tr> <td>MODIFIED_BY</td> <td>ID
	* последнего пользователя, изменившего валюту.</td> </tr> <tr>
	* <td>DATE_UPDATE_FORMAT</td> <td>Отформатированная в соответствии с настройками
	* сайта дата последнего изменения записи.</td> </tr> <tr> <td>LID</td> <td>Код
	* языка.</td> </tr> <tr> <td>FORMAT_STRING</td> <td>Строка формата для показа сумм в
	* этой валюте.</td> </tr> <tr> <td>FULL_NAME</td> <td>Полное название валюты.</td> </tr>
	* <tr> <td>DEC_POINT</td> <td>Символ, который используется при показе сумм в
	* этой валюте для отображения десятичной точки.</td> </tr> <tr>
	* <td>THOUSANDS_SEP</td> <td>Символ, который используется при показе сумм в
	* этой валюте для отображения разделителя тысяч.</td> </tr> <tr> <td>DECIMALS</td>
	* <td>Количество знаков после запятой при показе.</td> </tr> <tr> <td>HIDE_ZERO</td>
	* <td>Флаг (Y/N) убирает показ в публичной части незначащих нулей у
	* дробной части цены.</td> </tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выведем список валют на текущем языке, отсортированный по названию
	* // Кроме того выведем сумму 11.95 в формате этой валюты на текущем языке
	* $lcur = CCurrency::GetList(($by="name"), ($order1="asc"), LANGUAGE_ID);
	* while($lcur_res = $lcur-&gt;Fetch())
	* {
	*     echo "[".$lcur_res["CURRENCY"]."] ".$lcur_res["FULL_NAME"].": ";
	*     echo CurrencyFormat(11.95, $lcur_res["CURRENCY"])."&lt;br&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__getlist.efde2fe7.php
	* @author Bitrix
	*/
	static public function GetList(&$by, &$order, $lang = LANGUAGE_ID)
	{
		global $CACHE_MANAGER;

		if (defined("CURRENCY_SKIP_CACHE") && CURRENCY_SKIP_CACHE
			|| strtolower($by) == "name"
			|| strtolower($by) == "currency"
			|| strtolower($order) == "desc")
		{
			$dbCurrencyList = CCurrency::__GetList($by, $order, $lang);
		}
		else
		{
			$cacheTime = (int)CURRENCY_CACHE_DEFAULT_TIME;
			if (defined("CURRENCY_CACHE_TIME"))
				$cacheTime = (int)CURRENCY_CACHE_TIME;

			if ($CACHE_MANAGER->Read($cacheTime, "currency_currency_list_".$lang, 'b_catalog_currency'))
			{
				$arCurrencyList = $CACHE_MANAGER->Get("currency_currency_list_".$lang);
				$dbCurrencyList = new CDBResult();
				$dbCurrencyList->InitFromArray($arCurrencyList);
			}
			else
			{
				$arCurrencyList = array();
				$dbCurrencyList = CCurrency::__GetList($by, $order, $lang);
				while ($arCurrency = $dbCurrencyList->Fetch())
					$arCurrencyList[] = $arCurrency;

				$CACHE_MANAGER->Set("currency_currency_list_".$lang, $arCurrencyList);

				$dbCurrencyList = new CDBResult();
				$dbCurrencyList->InitFromArray($arCurrencyList);
			}
		}

		return $dbCurrencyList;
	}

	static public function __GetList(&$by, &$order, $lang = LANGUAGE_ID)
	{
		$lang = substr((string)$lang, 0, 2);
		$normalBy = strtolower($by);
		if ($normalBy != 'currency' && $normalBy != 'name')
		{
			$normalBy = 'sort';
			$by = 'sort';
		}
		$normalOrder = strtoupper($order);
		if ($normalOrder != 'DESC')
		{
			$normalOrder = 'ASC';
			$order = 'asc';
		}
		switch($normalBy)
		{
			case 'currency':
				$currencyOrder = array('CURRENCY' => $normalOrder);
				break;
			case 'name':
				$currencyOrder = array('FULL_NAME' => $normalOrder);
				break;
			case 'sort':
			default:
				$currencyOrder = array('SORT' => $normalOrder);
				break;
		}
		unset($normalOrder, $normalBy);

		$datetimeField = Currency\CurrencyManager::getDatetimeExpressionTemplate();
		$currencyIterator = Currency\CurrencyTable::getList(array(
			'select' => array(
				'CURRENCY', 'AMOUNT_CNT', 'AMOUNT', 'SORT', 'BASE', 'NUMCODE', 'CREATED_BY', 'MODIFIED_BY',
				new Main\Entity\ExpressionField('DATE_UPDATE_FORMAT', $datetimeField,  array('DATE_UPDATE'), array('data_type' => 'datetime')),
				new Main\Entity\ExpressionField('DATE_CREATE_FORMAT', $datetimeField,  array('DATE_CREATE'), array('data_type' => 'datetime')),
				'FULL_NAME' => 'RT_LANG.FULL_NAME', 'LID' => 'RT_LANG.LID', 'FORMAT_STRING' => 'RT_LANG.FORMAT_STRING',
				'DEC_POINT' => 'RT_LANG.DEC_POINT', 'THOUSANDS_SEP' => 'RT_LANG.THOUSANDS_SEP',
				'DECIMALS' => 'RT_LANG.DECIMALS', 'HIDE_ZERO' => 'RT_LANG.HIDE_ZERO'
			),
			'order' => $currencyOrder,
			'runtime' => array(
				'RT_LANG' => array(
					'data_type' => 'Bitrix\Currency\CurrencyLang',
					'reference' => array(
						'=this.CURRENCY' => 'ref.CURRENCY',
						'=ref.LID' => new Main\DB\SqlExpression('?', $lang)
					)
				)
			)
		));
		unset($datetimeField);
		$currencyList = array();
		while ($currency = $currencyIterator->fetch())
		{
			$currency['DATE_UPDATE'] = $currency['DATE_UPDATE_FORMAT'];
			$currency['DATE_CREATE'] = $currency['DATE_CREATE_FORMAT'];
			$currencyList[] = $currency;
		}
		unset($currency, $currencyIterator);
		$result = new CDBResult();
		$result->InitFromArray($currencyList);
		return $result;
	}

	public static function isUserExists()
	{
		global $USER;
		return (isset($USER) && $USER instanceof CUser);
	}

	public static function getInstalledCurrencies()
	{
		return Currency\CurrencyManager::getInstalledCurrencies();
	}

	public static function clearCurrencyCache()
	{
		Currency\CurrencyManager::clearCurrencyCache();
	}

	public static function clearTagCache($currency)
	{
		Currency\CurrencyManager::clearTagCache($currency);
	}

	public static function checkCurrencyID($currency)
	{
		return Currency\CurrencyManager::checkCurrencyID($currency);
	}

	public static function updateCurrencyBaseRate($currency)
	{
		global $DB;

		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if ($currency === false)
			return;
		$query = "select CURRENCY from b_catalog_currency where CURRENCY = '".$currency."'";
		$currencyIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		if ($existCurrency = $currencyIterator->Fetch())
		{
			self::updateBaseRates('', $existCurrency['CURRENCY']);
		}
	}

	public static function updateAllCurrencyBaseRate()
	{
		global $DB;

		$baseCurrency = (string)Currency\CurrencyManager::getBaseCurrency();
		if ($baseCurrency === '')
			return;

		$query = "select CURRENCY from b_catalog_currency where 1=1";
		$currencyIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		while ($existCurrency = $currencyIterator->Fetch())
		{
			self::updateBaseRates($baseCurrency, $existCurrency['CURRENCY']);
		}
	}

	public static function initCurrencyBaseRateAgent()
	{
		if (!ModuleManager::isModuleInstalled('bitrix24'))
		{
			$agentIterator = CAgent::GetList(
				array(),
				array('MODULE_ID' => 'currency','=NAME' => '\Bitrix\Currency\CurrencyTable::currencyBaseRateAgent();')
			);
			if ($agentIterator)
			{
				if (!($currencyAgent = $agentIterator->Fetch()))
				{
					self::updateAllCurrencyBaseRate();
					$checkDate = Main\Type\DateTime::createFromTimestamp(strtotime('tomorrow 00:01:00'));;
					CAgent::AddAgent('\Bitrix\Currency\CurrencyTable::currencyBaseRateAgent();', 'currency', 'Y', 86400, '', 'Y', $checkDate->toString(), 100, false, true);
				}
			}
		}
		return '';
	}

	protected static function updateBaseCurrency($currency)
	{
		global $DB, $USER;
		$currency = Currency\CurrencyManager::checkCurrencyID($currency);
		if ($currency === false)
			return false;
		$userID = (self::isUserExists() ? (int)$USER->GetID() : false);
		$currentDate = $DB->GetNowFunction();
		$fields = array(
			'BASE' => 'N',
			'~DATE_UPDATE' => $currentDate,
			'MODIFIED_BY' => $userID
		);
		$update = $DB->PrepareUpdate('b_catalog_currency', $fields);
		$query = "update b_catalog_currency set ".$update." where CURRENCY <> '".$currency."' and BASE = 'Y'";
		$DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		$fields = array(
			'BASE' => 'Y',
			'~DATE_UPDATE' => $currentDate,
			'MODIFIED_BY' => $userID,
			'AMOUNT' => 1,
			'AMOUNT_CNT' => 1
		);
		$update = $DB->PrepareUpdate('b_catalog_currency', $fields);
		$query = "update b_catalog_currency set ".$update." where CURRENCY = '".$currency."'";
		$DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		self::updateBaseRates($currency);
		return true;
	}

	protected static function updateBaseRates($currency = '', $updateCurrency = '')
	{
		global $DB;
		if ($currency === '')
			$currency = (string)Currency\CurrencyManager::getBaseCurrency();
		if ($currency === '')
			return;

		if ($updateCurrency != '')
		{
			$factor = 1;
			if ($updateCurrency != $currency)
				$factor = CCurrencyRates::GetConvertFactor($updateCurrency, $currency);
			$query = "update b_catalog_currency set CURRENT_BASE_RATE = ".(float)$factor." where CURRENCY = '".$updateCurrency."'";
			$DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		}
		else
		{
			$query = "select CURRENCY from b_catalog_currency";
			$currencyIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			while ($oneCurrency = $currencyIterator->Fetch())
			{
				$factor = 1;
				if ($oneCurrency['CURRENCY'] != $currency)
					$factor = CCurrencyRates::GetConvertFactor($oneCurrency['CURRENCY'], $currency);
				$query = "update b_catalog_currency set CURRENT_BASE_RATE = ".(float)$factor." where CURRENCY = '".$oneCurrency['CURRENCY']."'";
				$DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			}
		}
	}

	protected static function clearFields($value)
	{
		return ($value !== null);
	}
}

class CCurrency extends CAllCurrency
{

}
?>