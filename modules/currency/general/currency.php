<?
IncludeModuleLangFile(__FILE__);


/**
 * <b>CCurrency</b> - класс для управления валютами: добавление, удаление, перечисление.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/index.php
 * @author Bitrix
 */
class CAllCurrency
{
	
	/**
	 * <p>Функция возвращает массив языконезависимых параметров валюты по ее коду <b>currency</b>. Функция является оболочкой функции <a href="http://dev.1c-bitrix.ruapi_help/currency/developer/ccurrency/ccurrency__getbyid.a0947d8b.php">CCurrency::GetByID</a>.</p>
	 *
	 *
	 *
	 *
	 * @param string $currency  Код валюты.
	 *
	 *
	 *
	 * @return array <p>Ассоциативный массив с ключами</p><table class="tnormal" width="100%"> <tr> <th
	 * width="20%">Ключ</th> <th>Описание</th> </tr> <tr> <td>CURRENCY</td> <td>Код валюты
	 * (трехсимвольный)</td> </tr> <tr> <td>AMOUNT_CNT</td> <td>Количество единиц валюты
	 * по-умолчанию, которое учавствует в задании курса валюты
	 * (например, если 10 Датских крон стоят 48.7 рублей, то 10 - это
	 * количество единиц)</td> </tr> <tr> <td>AMOUNT</td> <td>Курс валюты по-умолчанию
	 * (одна из валют сайта должна иметь курс 1, она называется базовой,
	 * остальные валюты имеют курс относительно базовой валюты)</td> </tr>
	 * <tr> <td>SORT</td> <td>Порядок сортировки.</td> </tr> <tr> <td>DATE_UPDATE</td> <td>Дата
	 * последнего изменения записи.</td> </tr> </table>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__getcurrency.205e6985.php
	 * @author Bitrix
	 */
	public static function GetCurrency($currency)
	{
		$arRes = CCurrency::GetByID($currency);
		return $arRes;
	}

	
	/**
	 * <p>Выполняет проверку полей валюты при добавлении или изменении.</p> <p><b>Примечание</b>: возможное примечание.</p>
	 *
	 *
	 *
	 *
	 * @param $ACTIO $N  Равен <b>ADD</b> или <b>UPDATE</b> с учетом регистра. Если значение в другом
	 * регистре или другое значение - вернет <i>false</i> без текста ошибки
	 * (exception). Если значение равно <b>UPDATE</b>, то дополнительно проверяется
	 * <b>CurrencyID</b>. Если значение пустое - вернет ошибку, если не пустое, то
	 * обрежет до 3 символов.
	 *
	 *
	 *
	 * @param &$arField $s  Ключи: <ul> <li>CURRENCY - (обязательный), обрезается до 3 символов.
	 * Обязательно будет проверен, если присутствует в массиве (даже
	 * если это обновление). При добавлении будет дополнительно
	 * проверен на формат - 3 латинских символа. (При обновлении такая
	 * проверка не выполняется в целях сохранения совместимости.) Если
	 * формат верен, то будет выполнен поиск - не существует ли уже такая
	 * валюта (БЕЗ УЧЕТА регистра). Если не существует - код валюты будет
	 * приведен к верхнему регистру. При обновлении проверки
	 * существования в методе CheckFields не производится.</li> <li>AMOUNT_CNT -
	 * номинал (обязательный). Может быть только целым числом &gt; 0.</li>
	 * <li>AMOUNT - базовый курс (обязательный). Берется, если нет курсов по
	 * датам. Может быть только вещественным числом &gt; 0. </li> <li>SORT -
	 * сортировка. Целое число. Приводится к типу целого.</li> <li>DATE_UPDATE -
	 * время обновления - задается системой. Если есть такой ключ в
	 * массиве - удаляется.</li> </ul>
	 *
	 *
	 *
	 * @param $strCurrencyI $D = false код обновляемой валюты
	 *
	 *
	 *
	 * @return boolean <p>В случае успеха возвращает <i>true</i>. В случае ошибки - <i>false</i>.
	 * Текст ошибки можно получить через <code>$APPLICATION-&gt;GetException()</code>.</p><a
	 * name="examples"></a>
	 *
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
	 * 	*
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/checkfields.php
	 * @author Bitrix
	 */
	public static function CheckFields($ACTION, &$arFields, $strCurrencyID = false)
	{
		global $APPLICATION;
		global $DB;

		$arMsg = array();

		if ('UPDATE' != $ACTION && 'ADD' != $ACTION)
			return false;

		if ('UPDATE' == $ACTION)
		{
			if (strlen($strCurrencyID) <= 0)
			{
				$arMsg[] = array('id' => 'CURRENCY','text' => GetMessage('BT_MOD_CURR_ERR_CURR_CUR_ID_BAD'));
			}
			else
			{
				$strCurrencyID = substr($strCurrencyID, 0, 3);
			}
		}

		if (is_set($arFields, "CURRENCY") || 'ADD' == $ACTION)
		{
			if (!is_set($arFields, "CURRENCY"))
			{
				$arMsg[] = array('id' => 'CURRENCY','text' => GetMessage('BT_MOD_CURR_ERR_CURR_CUR_ID_ABSENT'));
			}
			else
			{
				$arFields["CURRENCY"] = substr($arFields["CURRENCY"], 0, 3);
			}
		}

		if ('ADD' == $ACTION)
		{
			if (!preg_match("~^[a-z]{3}$~i", $arFields["CURRENCY"]))
			{
				$arMsg[] = array('id' => 'CURRENCY','text' => GetMessage('BT_MOD_CURR_ERR_CURR_CUR_ID_LAT'));
			}
			else
			{
				$db_result = $DB->Query("SELECT 'x' FROM b_catalog_currency WHERE UPPER(CURRENCY) = UPPER('".$DB->ForSql($arFields["CURRENCY"])."')");
				if ($db_result->Fetch())
				{
					$arMsg[] = array('id' => 'CURRENCY','text' => GetMessage('BT_MOD_CURR_ERR_CURR_CUR_ID_EXISTS'));
				}
				else
				{
					$arFields["CURRENCY"] = strtoupper($arFields["CURRENCY"]);
				}
			}
		}

		if (is_set($arFields, 'AMOUNT_CNT') || 'ADD' == $ACTION)
		{
			if (!isset($arFields['AMOUNT_CNT']))
			{
				$arMsg[] = array('id' => 'AMOUNT_CNT','text' => GetMessage('BT_MOD_CURR_ERR_CURR_AMOUNT_CNT_ABSENT'));
			}
			elseif (0 >= intval($arFields['AMOUNT_CNT']))
			{
				$arMsg[] = array('id' => 'AMOUNT_CNT','text' => GetMessage('BT_MOD_CURR_ERR_CURR_AMOUNT_CNT_BAD'));
			}
			else
			{
				$arFields['AMOUNT_CNT'] = intval($arFields['AMOUNT_CNT']);
			}
		}

		if (is_set($arFields, 'AMOUNT') || 'ADD' == $ACTION)
		{
			if (!isset($arFields['AMOUNT']))
			{
				$arMsg[] = array('id' => 'AMOUNT','text' => GetMessage('BT_MOD_CURR_ERR_CURR_AMOUNT_ABSENT'));
			}
			else
			{
				$arFields['AMOUNT'] = doubleval($arFields['AMOUNT']);
				if (!(0 < $arFields['AMOUNT']))
				{
					$arMsg[] = array('id' => 'AMOUNT','text' => GetMessage('BT_MOD_CURR_ERR_CURR_AMOUNT_BAD'));
				}
			}
		}

		if (is_set($arFields,'SORT') || 'ADD' == $ACTION)
		{
			$arFields['SORT'] = intval($arFields['SORT']);
			if (0 >= $arFields['SORT'])
				$arFields['SORT'] = 100;
		}

		if (isset($arFields['DATE_UPDATE']))
			unset($arFields['DATE_UPDATE']);

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
	 * <p>Функция добавляет новую валюту, если ее еще не было. После добавления новой валюты необходимо установить ее языкозависимые параметры в помощью метода <a href="http://dev.1c-bitrix.ruapi_help/currency/developer/ccurrencylang/ccurrencylang__add.7ce2349e.php">CCurrencyLang::Add</a>.</p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  <p>Ассоциативный массив параметров валюты, в котором ключами
	 * являются названия параметров, а значениями - значения
	 * параметров.</p> <p>Допустимые названия параметров:</p> <ul> <li>CURRENCY -
	 * трехсимвольный код валюты (обязательный);</li> <li>AMOUNT_CNT - количество
	 * единиц валюты по-умолчанию, которое учавствует в задании курса
	 * валюты (например, если 10 Датских крон стоят 48.7 рублей, то 10 - это
	 * количество единиц);</li> <li>AMOUNT - курс валюты по-умолчанию (одна из
	 * валют сайта должна иметь курс 1, она называется базовой, остальные
	 * валюты имеют курс относительно базовой валюты);</li> <li>SORT - порядок
	 * сортировки</li> </ul>
	 *
	 *
	 *
	 * @return string <p>Функция возвращает код добавленной валюты (сбрасывает кеш
	 * <b>currency_currency_list</b> и <b>currency_base_currency</b> в случае успешного добавления).
	 * Или <i>false</i> в случае ошибки (текст ошибки берётся через
	 * <code>$APPLICATION-&gt;GetException()</code>).</p><a name="examples"></a>
	 *
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
	public static function Add($arFields)
	{
		global $DB;
		global $CACHE_MANAGER;

		if (!CCurrency::CheckFields('ADD', $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_currency", $arFields);

		$strSql =
			"INSERT INTO b_catalog_currency(".$arInsert[0].", DATE_UPDATE) ".
			"VALUES(".$arInsert[1].", ".$DB->GetNowFunction().")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$CACHE_MANAGER->Clean("currency_currency_list");
		$rsLangs = CLanguage::GetList(($by="lid"), ($order="asc"));
		while ($arLang = $rsLangs->Fetch())
		{
			$CACHE_MANAGER->Clean("currency_currency_list_".$arLang['LID']);
		}
		$CACHE_MANAGER->Clean("currency_base_currency");

		return $arFields["CURRENCY"];
	}

	
	/**
	 * <p>Функция изменяет параметры валюты <b>currency</b> на параметры, указанные в массиве <i>arFields</i>. Языкозависимые параметры (название, формат и прочее) обновляются отдельно, через класс <a href="http://dev.1c-bitrix.ruapi_help/currency/developer/ccurrencylang/ccurrencylang__update.8a1e7a7b.php">CCurrencyLang</a></p> <p>Сбрасывает кеш <b>currency_currency_list</b> и <b>currency_base_currency</b> в случае успешного обновления (только если произошел запрос к базе). Так же сбросит тегированный кеш <b>currency_id_КОД_ВАЛЮТЫ</b>.</p>
	 *
	 *
	 *
	 *
	 * @param string $currency  Код валюты, параметры которой нужно изменить.
	 *
	 *
	 *
	 * @param array $arFields  Массив новых параметров валюты. <ul> <li>CURRENCY - трехсимвольный код
	 * валюты (обязательный). Должно совпадать с кодом <b>currency</b>
	 * изменяемой валюты;</li> <li>AMOUNT_CNT - количество единиц валюты
	 * по-умолчанию, которое участвует в задании курса валюты (например,
	 * если 10 Датских крон стоят 48.7 рублей, то 10 - это количество
	 * единиц);</li> <li>AMOUNT - курс валюты по-умолчанию (одна из валют сайта
	 * должна иметь курс 1, она называется базовой, остальные валюты
	 * имеют курс относительно базовой валюты);</li> <li>SORT - порядок
	 * сортировки;</li> <p>Если в массиве нет ни одного из полей, то
	 * обращения к базе данных не будет, но вернет код валюты.</p> </ul>
	 *
	 *
	 *
	 * @return bool <p>Код валюты, параметры которой изменили, или <i>false</i> в случае
	 * ошибки (текст получается через <code>$APPLICATION-&gt;GetException()</code>).</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__update.16586d51.php
	 * @author Bitrix
	 */
	public static function Update($currency, $arFields)
	{
		global $DB;
		global $CACHE_MANAGER;

		if (!CCurrency::CheckFields('UPDATE', $arFields, $currency))
			return false;

		$strCurrencyID = substr($currency, 0, 3);
		if (is_set($arFields, 'CURRENCY'))
			unset($arFields['CURRENCY']);
		$strUpdate = $DB->PrepareUpdate("b_catalog_currency", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "UPDATE b_catalog_currency SET ".$strUpdate.", DATE_UPDATE = ".$DB->GetNowFunction()." WHERE CURRENCY = '".$DB->ForSql($strCurrencyID)."' ";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$CACHE_MANAGER->Clean("currency_base_currency");
			$CACHE_MANAGER->Clean("currency_currency_list");
			$rsLangs = CLanguage::GetList(($by="lid"), ($order="asc"));
			while ($arLang = $rsLangs->Fetch())
			{
				$CACHE_MANAGER->Clean("currency_currency_list_".$arLang['LID']);
			}

			if (defined("BX_COMP_MANAGED_CACHE"))
				$CACHE_MANAGER->ClearByTag("currency_id_".$strCurrencyID);
		}

		return $strCurrencyID;
	}

	
	/**
	 * <p>Функция удаляет валюту с кодом <b>currency</b>. Удаляются в том числе все введенные курсы для этой валюты, а так же языкозависимые свойства валюты. </p> <p>Перед удалением вызывает событие <b>OnBeforeCurrencyDelete</b>, где можно отменить удаление. Формат обработчика: <code>boolean Handler($currency)</code>.</p> <p>Сбрасывает кеш <b>currency_currency_list</b> и <b>currency_base_currency</b>. Так же сбросит тегированный кеш <b>currency_id_КОД_ВАЛЮТЫ</b>.</p>
	 *
	 *
	 *
	 *
	 * @param string $currency  Код валюты.
	 *
	 *
	 *
	 * @return bool <p>Функция возвращает <i>True</i> в случае успешного удаления и <i>False</i> -
	 * в противном случае </p><a name="examples"></a>
	 *
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
	public static function Delete($currency)
	{
		global $DB;
		global $stackCacheManager;
		global $CACHE_MANAGER;

		$currency = substr($currency, 0, 3);

		$bCanDelete = true;
		$db_events = GetModuleEvents("currency", "OnBeforeCurrencyDelete");
		while($arEvent = $db_events->Fetch())
			if(ExecuteModuleEventEx($arEvent, array($currency))===false)
				return false;

		$events = GetModuleEvents("currency", "OnCurrencyDelete");
		while($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($currency));

		$stackCacheManager->Clear("currency_currency_lang");
		$stackCacheManager->Clear("currency_rate");
		$CACHE_MANAGER->Clean("currency_currency_list");
		$rsLangs = CLanguage::GetList(($by="lid"), ($order="asc"));
		while ($arLang = $rsLangs->Fetch())
		{
			$CACHE_MANAGER->Clean("currency_currency_list_".$arLang['LID']);
		}
		$CACHE_MANAGER->Clean("currency_base_currency");

		$DB->Query("DELETE FROM b_catalog_currency_lang WHERE CURRENCY = '".$DB->ForSQL($currency)."'", true);
		$DB->Query("DELETE FROM b_catalog_currency_rate WHERE CURRENCY = '".$DB->ForSQL($currency)."'", true);

		if (defined("BX_COMP_MANAGED_CACHE"))
			$CACHE_MANAGER->ClearByTag("currency_id_".$currency);

		return $DB->Query("DELETE FROM b_catalog_currency WHERE CURRENCY = '".$DB->ForSQL($currency)."'", true);
	}

	
	/**
	 * <p>Функция возвращает массив языконезависимых параметров валюты по ее коду currency.</p> <p>Смотрите так же функцию <a href="http://dev.1c-bitrix.ruapi_help/currency/developer/ccurrency/ccurrency__getcurrency.205e6985.php">CCurrency::GetCurrency</a></p>
	 *
	 *
	 *
	 *
	 * @param string $currency  Код валюты.
	 *
	 *
	 *
	 * @return array <p>Ассоциативный массив с ключами</p><table class="tnormal" width="100%"> <tr> <th
	 * width="20%">Ключ</th> <th>Описание</th> </tr> <tr> <td>CURRENCY</td> <td>Код валюты
	 * (трехсимвольный)</td> </tr> <tr> <td>AMOUNT_CNT</td> <td>Количество единиц валюты
	 * по-умолчанию, которое учавствует в задании курса валюты
	 * (например, если 10 Датских крон стоят 48.7 рублей, то 10 - это
	 * количество единиц)</td> </tr> <tr> <td>AMOUNT</td> <td>Курс валюты по-умолчанию
	 * (одна из валют сайта должна иметь курс 1, она называется базовой,
	 * остальные валюты имеют курс относительно базовой валюты)</td> </tr>
	 * <tr> <td>SORT</td> <td>Порядок сортировки.</td> </tr> <tr> <td>DATE_UPDATE</td> <td>Дата
	 * последнего изменения записи.</td> </tr> </table><a name="examples"></a>
	 *
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
	public static function GetByID($currency)
	{
		global $DB;

		$strSql =
			"SELECT CUR.* ".
			"FROM b_catalog_currency CUR ".
			"WHERE CUR.CURRENCY = '".$DB->ForSQL($currency, 3)."'";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}


	
	/**
	 * <p>Функция возвращает код базовой валюты.</p> <p>Одна из валют сайта должна иметь курс "по-умолчанию" равный 1. Эта валюта называется базовой. Курс всех валют, кроме базовой, задается относительно базовой валюты.</p>
	 *
	 *
	 *
	 *
	 * @return string 
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/ccurrency__getbasecurrency.98c474fc.php
	 * @author Bitrix
	 */
	public static function GetBaseCurrency()
	{
		global $DB;
		global $CACHE_MANAGER;

		$baseCurrency = "";

		if (defined("CURRENCY_SKIP_CACHE") && CURRENCY_SKIP_CACHE)
		{
			$strSql = "SELECT CURRENCY FROM b_catalog_currency WHERE AMOUNT = 1 ";
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				$baseCurrency = $arRes["CURRENCY"];
		}
		else
		{
			$cacheTime = CURRENCY_CACHE_DEFAULT_TIME;
			if (defined("CURRENCY_CACHE_TIME"))
				$cacheTime = intval(CURRENCY_CACHE_TIME);

			if ($CACHE_MANAGER->Read(CURRENCY_CACHE_TIME, "currency_base_currency"))
			{
				$baseCurrency = $CACHE_MANAGER->Get("currency_base_currency");
			}
			else
			{
				$strSql = "SELECT CURRENCY FROM b_catalog_currency WHERE AMOUNT = 1 ";
				$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if ($arRes = $dbRes->Fetch())
					$baseCurrency = $arRes["CURRENCY"];

				$CACHE_MANAGER->Set("currency_base_currency", $baseCurrency);
			}
		}

		return $baseCurrency;
	}

	
	/**
	 * <p>Функция для формирования готового кода выпадающего списка (select) валют. Список валют при построении списка кэшируется. Поэтому вывод дополнительных выпадающих списков в рамках одной страницы не приводит к дополнительным запросам к базе данных. </p>
	 *
	 *
	 *
	 *
	 * @param string $sFieldName  Название выпадающего списка.
	 *
	 *
	 *
	 * @param string $sValue  Код валюты, которую нужно установить в списке выбранной.
	 *
	 *
	 *
	 * @param string $sDefaultValue = "" Особое значение в списке, которое не соответствует ни одной
	 * валюте. Например, значение "Все" или "Не установлено". В этом случае
	 * код валюты в списке будет пустым. Если параметр пуст, то особое
	 * значение не отображается.
	 *
	 *
	 *
	 * @param bool $bFullName = True Выводить ли полное имя валюты. Если параметр равен False, то в списке
	 * выводятся только коды валют.
	 *
	 *
	 *
	 * @param string $JavaFunc = "" Название JavaScript функции, которая вызывается на событие OnChange
	 * списка. Если значение пустое, то функция не вызывается.
	 *
	 *
	 *
	 * @param string $sAdditionalParams = "" Строка произвольных дополнительных атрибутов тега &lt;select&gt;
	 *
	 *
	 *
	 * @return string <p>Строка, содержащая код для формирования выпадающего списка
	 * валют </p><a name="examples"></a>
	 *
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
	public static function SelectBox($sFieldName, $sValue, $sDefaultValue = "", $bFullName = True, $JavaFunc = "", $sAdditionalParams = "")
	{
		$s = '<select name="'.$sFieldName.'"';
		if (strlen($JavaFunc)>0) $s .= ' OnChange="'.$JavaFunc.'"';
		if (strlen($sAdditionalParams)>0) $s .= ' '.$sAdditionalParams.' ';
		$s .= '>'."\n";
		$found = false;

		$dbCurrencyList = CCurrency::GetList(($by="sort"), ($order="asc"));
		while ($arCurrency = $dbCurrencyList->Fetch())
		{
			$found = ($arCurrency["CURRENCY"] == $sValue);
			$s1 .= '<option value="'.$arCurrency["CURRENCY"].'"'.($found ? ' selected':'').'>'.htmlspecialcharsbx($arCurrency["CURRENCY"]).(($bFullName)?(' ('.htmlspecialcharsbx($arCurrency["FULL_NAME"]).')'):"").'</option>'."\n";
		}
		if (strlen($sDefaultValue)>0)
			$s .= "<option value='' ".($found ? "" : "selected").">".htmlspecialcharsbx($sDefaultValue)."</option>";
		return $s.$s1.'</select>';
	}

	
	/**
	 * <p>Функция возвращает список валют, отсортированный по полю из параметра by в направлении order. Языкозависимые параметры валют берутся для языка, указанного в параметре lang (по умолчанию равен текущему языку). </p>
	 *
	 *
	 *
	 *
	 * @param string &$by  Переменная, содержащая порядок сортировки валют. Допустимые
	 * значения переменной:<br> currency - код валюты<br> name - название валюты на
	 * языке lang<br> sort - индекс сортировки (по-умолчанию)
	 *
	 *
	 *
	 * @param string &$order  Переменная, содержащая направление сортировки. Допустимые
	 * значения:<br> asc - по возрастанию значений (по-умолчанию) <br> desc - по
	 * убыванию значений.
	 *
	 *
	 *
	 * @param string $lang = LANGUAGE_ID Код языка, для которого выбираются языкозависимые параметры
	 * валют.
	 *
	 *
	 *
	 * @return CDBResult <p>Возвращается объект класса CDBResult, каждая запись в котором
	 * представляет собой массив с ключами</p><table class="tnormal" width="100%"> <tr> <th
	 * width="20%">Ключ</th> <th>Описание</th> </tr> <tr> <td>CURRENCY</td> <td>Код валюты
	 * (трехсимвольный)</td> </tr> <tr> <td>AMOUNT_CNT</td> <td>Количество единиц валюты
	 * по-умолчанию, которое учавствует в задании курса валюты
	 * (например, если 10 Датских крон стоят 48.7 рублей, то 10 - это
	 * количество единиц)</td> </tr> <tr> <td>AMOUNT</td> <td>Курс валюты по-умолчанию
	 * (одна из валют сайта должна иметь курс 1, она называется базовой,
	 * остальные валюты имеют курс относительно базовой валюты)</td> </tr>
	 * <tr> <td>SORT</td> <td>Порядок сортировки.</td> </tr> <tr> <td>DATE_UPDATE</td> <td>Дата
	 * последнего изменения записи.</td> </tr> <tr> <td>LID</td> <td>Код языка.</td> </tr>
	 * <tr> <td>FORMAT_STRING</td> <td>Строка формата для показа сумм в этой валюте.</td>
	 * </tr> <tr> <td>FULL_NAME</td> <td>Полное название валюты.</td> </tr> <tr> <td>DEC_POINT</td>
	 * <td>Символ, который используется при показе сумм в этой валюте для
	 * отображения десятичной точки.</td> </tr> <tr> <td>THOUSANDS_SEP</td> <td>Символ,
	 * который используется при показе сумм в этой валюте для
	 * отображения разделителя тысяч.</td> </tr> <tr> <td>DECIMALS</td> <td>Количество
	 * знаков после запятой при показе.</td> </tr> </table><a name="examples"></a>
	 *
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
	public static function GetList(&$by, &$order, $lang = LANGUAGE_ID)
	{
		global $DB;
		global $CACHE_MANAGER;

		if (defined("CURRENCY_SKIP_CACHE") && CURRENCY_SKIP_CACHE
			|| StrToLower($by) == "name"
			|| StrToLower($by) == "currency"
			|| StrToLower($order) == "desc")
		{
			$dbCurrencyList = CCurrency::__GetList($by, $order, $lang);
		}
		else
		{
			$by = "sort";
			$order = "asc";

			$lang = substr($lang, 0, 2);

			$cacheTime = CURRENCY_CACHE_DEFAULT_TIME;
			if (defined("CURRENCY_CACHE_TIME"))
				$cacheTime = intval(CURRENCY_CACHE_TIME);

			if ($CACHE_MANAGER->Read($cacheTime, "currency_currency_list_".$lang))
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
}
?>