<?
use Bitrix\Currency;

IncludeModuleLangFile(__FILE__);


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
class CAllCurrencyRates
{
	protected static $currentCache = array();

	
	/**
	* <p>Выполняет проверку полей курса при добавлении или изменении. Метод статический.</p>
	*
	*
	* @param arFields $arFieldsID = 0 Равно <b>ADD</b> или <b>UPDATE</b> с учетом регистра. Если значение в другом
	* регистре или другое значение, то возвращает <i>false</i> без текста
	* ошибки (exception). Если значение равно <b>UPDATE</b>, то проверяется ID. Если
	* ID &lt;= 0, то возвращается ошибка. В случае наличия в <i>arFields</i> ключа ID
	* удалит его.
	*
	* @return boolean <p>В случае успеха возвращает <i>true</i>. В случае ошибки - <i>false</i>.
	* Текст ошибки можно получить через <code>$APPLICATION-&gt;GetException()</code>.</p><a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $ID = 7;
	* 	$arFields = array(
	* 		'CURRENCY' =&gt; 'RUB',
	* 		'DATE_RATE' =&gt; '21.02.2012',
	* 		'RATE_CNT' =&gt; 7
	* 	);
	* 	
	* 	$mxRes = CCurrencyRates::CheckFields('ADD', $arFields); // вернет ошибку, т.к. нет курса (RATE)
	* 	$mxRes = CCurrencyRates::CheckFields('UPDATE', $arFields, $ID); // ошибки не будет, т.к. при обновлении RATE не является обязательным;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/checkfields.php
	* @author Bitrix
	*/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION, $DB;

		$arMsg = array();

		if ('UPDATE' != $ACTION && 'ADD' != $ACTION)
			return false;
		if (array_key_exists('ID', $arFields))
			unset($arFields['ID']);

		if ('UPDATE' == $ACTION && 0 >= intval($ID))
			$arMsg[] = array('id' => 'ID','text' => GetMessage('BT_MOD_CURR_ERR_RATE_ID_BAD'));

		if (!isset($arFields["CURRENCY"]))
			$arMsg[] = array('id' => 'CURRENCY','text' => GetMessage('BT_MOD_CURR_ERR_RATE_CURRENCY_ABSENT'));
		else
			$arFields["CURRENCY"] = substr($arFields["CURRENCY"],0,3);

		if (empty($arFields['DATE_RATE']))
			$arMsg[] = array('id' => 'DATE_RATE','text' => GetMessage('BT_MOD_CURR_ERR_RATE_DATE_ABSENT'));
		elseif (!$DB->IsDate($arFields['DATE_RATE']))
			$arMsg[] = array('id' => 'DATE_RATE','text' => GetMessage('BT_MOD_CURR_ERR_RATE_DATE_FORMAT_BAD'));

		if (is_set($arFields, 'RATE_CNT') || 'ADD' == $ACTION)
		{
			if (!isset($arFields['RATE_CNT']))
			{
				$arMsg[] = array('id' => 'RATE_CNT', 'text' => GetMessage('BT_MOD_CURR_ERR_RATE_RATE_CNT_ABSENT'));
			}
			else
			{
				$arFields['RATE_CNT'] = (int)$arFields['RATE_CNT'];
				if ($arFields['RATE_CNT'] <= 0)
					$arMsg[] = array('id' => 'RATE_CNT', 'text' => GetMessage('BT_MOD_CURR_ERR_RATE_RATE_CNT_BAD'));
			}
		}
		if (is_set($arFields['RATE']) || 'ADD' == $ACTION)
		{
			if (!isset($arFields['RATE']))
			{
				$arMsg[] = array('id' => 'RATE','text' => GetMessage('BT_MOD_CURR_ERR_RATE_RATE_ABSENT'));
			}
			else
			{
				$arFields['RATE'] = (float)$arFields['RATE'];
				if (!($arFields['RATE'] > 0))
				{
					$arMsg[] = array('id' => 'RATE','text' => GetMessage('BT_MOD_CURR_ERR_RATE_RATE_BAD'));
				}
			}
		}
		if ($ACTION == 'ADD')
		{
			if ($arFields['CURRENCY'] == Currency\CurrencyManager::getBaseCurrency())
			{
				$arMsg[] = array('id' => 'CURRENCY', 'text' => GetMessage('BT_MOD_CURR_ERR_RATE_FOR_BASE_CURRENCY'));
			} else
			{
				if (!isset($arFields['BASE_CURRENCY']) || !Currency\CurrencyManager::checkCurrencyID($arFields['BASE_CURRENCY']))
					$arFields['BASE_CURRENCY'] = Currency\CurrencyManager::getBaseCurrency();
			}
			if ($arFields['CURRENCY'] == $arFields['BASE_CURRENCY'])
			{
				$arMsg[] = array('id' => 'CURRENCY', 'text' => GetMessage('BT_MOD_CURR_ERR_RATE_FOR_SELF_CURRENCY'));
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
	* <p>Метод создает новый курс на заданную дату. Перед добавлением проверяет, нет ли курса этой валюты на этот день. Метод статический.</p> <p>Сбрасывает кеш <b>currency_rate</b> в случае успешного добавления, сбрасывает тэгированный кеш <b>currency_id_КОД_ВАЛЮТЫ</b>.</p>
	*
	*
	* @param array $arFields  <p>Ассоциативный массив параметров курса валюты, ключами которого
	* являются названия параметров, а значениями - значения
	* параметров.</p> 	  <p>Допустимые ключи (Все поля обязательны):</p> <ul>
	* <li>CURRENCY - код валюты, для которой добавляется курс;</li> <li>DATE_RATE - дата
	* БЕЗ ВРЕМЕНИ, на которую устанавливается курс;</li>  <li>RATE_CNT -
	* количество единиц валюты, которое участвует в задании курса
	* валюты (например, если 10 Датских крон стоят 48.7 рублей, то 10 - это
	* количество единиц);</li>  <li>RATE - курс валюты.</li>   </ul>
	*
	* @return bool <p>Возвращает значение ID курса валют в случае успешного
	* добавления и <i>False</i> - в противном случае. Текст ошибки выводится с
	* помощью <code>$APPLICATION-&gt;GetException()</code>.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // Считаем, что RUR - базовая валюта
	* // Курс ирландских крон (ISK) на 21.02.2005:  10 ISK = 48.1756 RUR
	* 
	* $arFields = array(
	*     "RATE" =&gt; 48.1756,
	*     "RATE_CNT" =&gt; 10,
	*     "CURRENCY" =&gt; "ISK",
	*     "DATE_RATE" =&gt; "21.02.2005"
	*     );
	* 
	* if (!CCurrencyRates::Add($arFields))
	*     echo "Ошибка добавления курса";<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__add.a9ea23d5.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;
		global $APPLICATION;
		/** @global CStackCacheManager $stackCacheManager */
		global $stackCacheManager;

		$arMsg = array();

		foreach (GetModuleEvents("currency", "OnBeforeCurrencyRateAdd", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields))===false)
				return false;
		}

		if (!CCurrencyRates::CheckFields("ADD", $arFields))
			return false;

		$db_result = $DB->Query("SELECT 'x' FROM b_catalog_currency_rate WHERE CURRENCY = '".$DB->ForSql($arFields["CURRENCY"])."' ".
			"	AND DATE_RATE = ".$DB->CharToDateFunction($DB->ForSql($arFields["DATE_RATE"]), "SHORT"));
		if ($db_result->Fetch())
		{
			$arMsg[] = array("id"=>"DATE_RATE", "text"=> GetMessage("ERROR_ADD_REC2"));
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		else
		{
			$stackCacheManager->Clear("currency_rate");

			$isMsSql = strtolower($DB->type) == 'mssql';
			if ($isMsSql)
				CTimeZone::Disable();
			$ID = $DB->Add("b_catalog_currency_rate", $arFields);
			if ($isMsSql)
				CTimeZone::Enable();
			unset($isMsSql);

			Currency\CurrencyManager::updateBaseRates($arFields['CURRENCY']);
			Currency\CurrencyManager::clearTagCache($arFields['CURRENCY']);
			self::$currentCache = array();

			foreach (GetModuleEvents("currency", "OnCurrencyRateAdd", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			return $ID;
		}
	}

	
	/**
	* <p>Метод обновляет параметры записи в таблице курсов валют на значения из массива <i>arFields</i>. Перед обновлением выполняется проверка, нет ли курса этой валюты на эту дату с другим ID. Если есть - то произойдет ошибка. Метод статический.</p> <p>В случае успешного обновления сбрасываются кеш <b>currency_rate</b> и тэгированный кеш <b>currency_id_КОД_ВАЛЮТЫ</b>.</p>
	*
	*
	* @param mixed $intID  Код записи.
	*
	* @param array $arFields  <p>Ассоциативный массив новых параметров курса валюты, ключами
	* которого являются названия параметров, а значениями - значения
	* параметров.</p> 	  <p>Допустимые ключи:</p> <ul> <li>CURRENCY - код валюты
	* (обязательный);</li> <li>DATE_RATE - дата БЕЗ ВРЕМЕНИ, за которую
	* устанавливается курс (обязательный);</li>  <li>RATE_CNT - количество
	* единиц валюты, которое участвует в задании курса валюты
	* (например, если 10 Датских крон стоят 48.7 рублей, то 10 - это
	* количество единиц);</li>  <li>RATE - курс валюты.</li>   </ul>
	*
	* @return bool <p>В случае успеха возвращает ID изменённого курса, иначе <i>false</i>.
	* Текст ошибки выводится через <code>$APPLICATION-&gt;GetException()</code>.</p><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__update.1f36666f.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;
		global $APPLICATION;
		/** @global CStackCacheManager $stackCacheManager */
		global $stackCacheManager;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;
		$arMsg = array();

		foreach (GetModuleEvents("currency", "OnBeforeCurrencyRateUpdate", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields))===false)
				return false;
		}

		if (!CCurrencyRates::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$db_result = $DB->Query("SELECT 'x' FROM b_catalog_currency_rate WHERE CURRENCY = '".$DB->ForSql($arFields["CURRENCY"])."' ".
			"	AND DATE_RATE = ".$DB->CharToDateFunction($DB->ForSql($arFields["DATE_RATE"]), "SHORT")." AND ID<>".$ID." ");
		if ($db_result->Fetch())
		{
			$arMsg[] = array("id"=>"DATE_RATE", "text"=> GetMessage("ERROR_ADD_REC2"));
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		else
		{
			$isMsSql = strtolower($DB->type) == 'mssql';
			if ($isMsSql)
				CTimeZone::Disable();
			$strUpdate = $DB->PrepareUpdate("b_catalog_currency_rate", $arFields);
			if ($isMsSql)
				CTimeZone::Enable();
			unset($isMsql);

			if (!empty($strUpdate))
			{
				$strSql = "UPDATE b_catalog_currency_rate SET ".$strUpdate." WHERE ID = ".$ID;
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

				$stackCacheManager->Clear("currency_rate");
				Currency\CurrencyManager::updateBaseRates($arFields['CURRENCY']);
				Currency\CurrencyManager::clearTagCache($arFields['CURRENCY']);
				self::$currentCache = array();
			}
			foreach (GetModuleEvents("currency", "OnCurrencyRateUpdate", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));
		}
		return true;
	}

	
	/**
	* <p>Удаляет запись с кодом ID из таблицы курсов валют. Если удаляется несуществующий курс (нет курса с таким ID) - будет ошибка. Метод статический.</p> <p>В случае успеха сбросится кеш <b>currency_rate</b> и тэгированный <b>currency_id_КОД_ВАЛЮТЫ</b>.</p>
	*
	*
	* @param mixed $intID  Код записи для удаления.
	*
	* @return bool <p>Возвращает значение <i>True</i> в случае успешного добавления и
	* <i>False</i> - в противном случае.  Текст ошибки выводится с помощью
	* <code>$APPLICATION-&gt;GetException</code>.</p><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__delete.28de3643.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		global $APPLICATION;
		/** @global CStackCacheManager $stackCacheManager */
		global $stackCacheManager;

		$ID = (int)$ID;

		if ($ID <= 0)
			return false;

		foreach(GetModuleEvents("currency", "OnBeforeCurrencyRateDelete", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array($ID))===false)
				return false;
		}

		$arFields = CCurrencyRates::GetByID($ID);
		if (!is_array($arFields))
		{
			$arMsg = array('id' => 'ID', 'text' => GetMessage('BT_MOD_CURR_ERR_RATE_CANT_DELETE_ABSENT_ID'));
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		$stackCacheManager->Clear("currency_rate");

		$strSql = "DELETE FROM b_catalog_currency_rate WHERE ID = ".$ID;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		Currency\CurrencyManager::updateBaseRates($arFields['CURRENCY']);
		Currency\CurrencyManager::clearTagCache($arFields['CURRENCY']);
		self::$currentCache = array();

		foreach(GetModuleEvents("currency", "OnCurrencyRateDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));

		return true;
	}

	
	/**
	* <p>Возвращает параметры курса валют с кодом ID. Метод статический. </p>
	*
	*
	* @param mixed $intID  Код курса.
	*
	* @return array <p>Ассоциативный массив с ключами</p><table class="tnormal" width="100%"> <tr> <th
	* width="20%">Ключ</th>     <th>Описание</th>   </tr> <tr> <td>ID</td>     <td>Код курса.</td> </tr>
	* <tr> <td>CURRENCY</td>     <td>Код валюты.</td> </tr> <tr> <td>DATE_RATE</td>     <td>Дата, за
	* которую установлен курс.</td> </tr> <tr> <td>RATE_CNT</td>     <td>количество
	* единиц валюты, которое участвует в задании курса валюты
	* (например, если 10 Датских крон стоят 48.7 рублей, то 10 - это
	* количество единиц)</td>   </tr> <tr> <td>RATE</td>     <td>курс валюты (одна из
	* валют сайта должна иметь курс "по-умолчанию" 1, она называется
	* базовой, остальные валюты имеют курс относительно базовой
	* валюты)</td>   </tr> </table><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__getbyid.2c90255f.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;
		$strSql = "SELECT C.*, ".$DB->DateToCharFunction("C.DATE_RATE", "SHORT")." as DATE_RATE FROM b_catalog_currency_rate C WHERE ID = ".$ID;
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	
	/**
	* <p>Метод возвращает список курсов валют, удовлетворяющих фильтру arFilter, отсортированный по полю by в направлении order. Метод статический. </p>
	*
	*
	* @param string &$by  Переменная , содержащая название поля для сортировки. Доступные
	* названия:<br>       date - дата курса (по умолчанию) <br>       curr - валюта<br>     
	*  rate - курс.
	*
	* @param string &$order  Переменная, содержащая направление сортировки. Допустимы
	* значения:<br> 	  asc - по возрастанию (по умолчанию)<br> 	  desc - по
	* убыванию.
	*
	* @param array $arFilter = Array() <p>Фильтр на записи представляет собой ассоциативный массив, в
	* котором ключами являются названия фильтруемых полей, а
	* значениями - условия фильтра. Необязательный параметр.</p> 	 
	* <p>Допустимые поля для фильтра:<br>       CURRENCY - код валюты<br>       DATE_RATE -
	* дата курса (выбираются записи с датами больше или равными
	* указанной) <br>       !DATE_RATE - дата курса (выбираются записи с датами
	* меньше указанной)	  </p>
	*
	* @return CDBResult <p>Объект класса CDBResult, содержащий записи с ключами </p><table class="tnormal"
	* width="100%"> <tr> <th width="30%">Ключ</th>     <th>Описание</th>   </tr> <tr> <td>ID</td>     <td>Код
	* курса.</td> </tr> <tr> <td>CURRENCY</td>     <td>Код валюты.</td> </tr> <tr> <td>DATE_RATE</td>    
	* <td>Дата, за которую установлен курс.</td> </tr> <tr> <td>RATE_CNT</td>    
	* <td>количество единиц валюты, которое участвует в задании курса
	* валюты (например, если 10 Датских крон стоят 48.7 рублей, то 10 - это
	* количество единиц)</td>   </tr> <tr> <td>RATE</td>     <td>курс валюты (одна из
	* валют сайта должна иметь курс "по-умолчанию" 1, она называется
	* базовой, остальные валюты имеют курс относительно базовой
	* валюты)</td>   </tr> </table><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* <button hidefocus="true" onclick="copyExample('11523A13')" class="copyx"><span class="copyx"></span></button>
	* &lt;?
	* // Выведем все курсы USD, отсортированные по дате
	* $arFilter = array(
	*     "CURRENCY" =&gt; "USD"
	*     );
	* $by = "date";
	* $order = "desc";
	* 
	* $db_rate = CCurrencyRates::GetList($by, $order, $arFilter);
	* while($ar_rate = $db_rate-&gt;Fetch())
	* {
	*     echo $ar_rate["RATE"]."&lt;br&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__getlist.37245cb2.php
	* @author Bitrix
	*/
	public static function GetList(&$by, &$order, $arFilter=array())
	{
		global $DB;

		$mysqlEdition = strtolower($DB->type) === 'mysql';
		$arSqlSearch = array();

		if(!is_array($arFilter))
			$filter_keys = array();
		else
			$filter_keys = array_keys($arFilter);

		for ($i=0, $intCount = count($filter_keys); $i < $intCount; $i++)
		{
			$val = (string)$DB->ForSql($arFilter[$filter_keys[$i]]);
			if ($val === '')
				continue;

			$key = $filter_keys[$i];
			if ($key[0]=="!")
			{
				$key = substr($key, 1);
				$bInvert = true;
			}
			else
				$bInvert = false;

			switch(strtoupper($key))
			{
			case "CURRENCY":
				$arSqlSearch[] = "C.CURRENCY = '".$val."'";
				break;
			case "DATE_RATE":
				$arSqlSearch[] = "(C.DATE_RATE ".($bInvert?"<":">=")." ".($mysqlEdition ? "CAST(" : "" ).$DB->CharToDateFunction($DB->ForSql($val), "SHORT").($mysqlEdition ? " AS DATE)" : "" ).($bInvert?"":" OR C.DATE_RATE IS NULL").")";
				break;
			}
		}

		$strSqlSearch = "";
		for($i=0, $intCount = count($arSqlSearch); $i < $intCount; $i++)
		{
			if($i>0)
				$strSqlSearch .= " AND ";
			else
				$strSqlSearch = " WHERE ";

			$strSqlSearch .= " (".$arSqlSearch[$i].") ";
		}

		$strSql = "SELECT C.ID, C.CURRENCY, C.RATE_CNT, C.RATE, ".$DB->DateToCharFunction("C.DATE_RATE", "SHORT")." as DATE_RATE FROM b_catalog_currency_rate C ".
			$strSqlSearch;

		if (strtolower($by) == "curr") $strSqlOrder = " ORDER BY C.CURRENCY ";
		elseif (strtolower($by) == "rate") $strSqlOrder = " ORDER BY C.RATE ";
		else
		{
			$strSqlOrder = " ORDER BY C.DATE_RATE ";
			$by = "date";
		}

		if (strtolower($order)=="desc")
			$strSqlOrder .= " desc ";
		else
			$order = "asc";

		$strSql .= $strSqlOrder;
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;
	}

	
	/**
	* <p>Метод переводит сумму valSum из валюты curFrom в валюту curTo по курсу, установленному на дату valDate. Метод статический. </p>
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
	* @return float <p>Сумма в новой валюте </p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // предполагаем, что валюты USD и EUR существуют в базе
	* $val = 11.95; // сумма в USD
	* $newval = CCurrencyRates::ConvertCurrency($val, "USD", "EUR");
	* echo $val." USD = ".$newval." EUR";
	* ?&gt;
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
		return (float)$valSum * static::GetConvertFactorEx($curFrom, $curTo, $valDate);
	}

	/**
	 * @deprecated deprecated since currency 16.0.0
	 * @see CCurrencyRates::GetConvertFactorEx
	 *
	 * @param float|int $curFrom
	 * @param float|int $curTo
	 * @param string $valDate
	 * @return float|int
	 */
	
	/**
	* <p>Метод возвращает коэффициент для перевода сумм из валюты curFrom в валюту curTo по курсу, установленному на дату valDate. Метод статический.</p> <p>С версии 16.0.0 вместо этого метода используйте <a href="http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/getconvertfactorex.php">GetConvertFactorEx</a>.</p>
	*
	*
	* @param string $curFrom  Исходная валюта.
	*
	* @param string $curTo  Валюта назначения.
	*
	* @param string $valDate = "" Дата, по курсу на которую нужно осуществить перевод. Если дата
	* пуста, то перевод идет по текущему курсу. Необязательный
	* параметр.<br><br> Дата должна быть указана в формате <b>YYYY-MM-DD</b>.
	*
	* @return float <p>Коэффициент для перевода. </p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
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
	* @deprecated deprecated since currency 16.0.0  ->  CCurrencyRates::GetConvertFactorEx
	*/
	public static function GetConvertFactor($curFrom, $curTo, $valDate = "")
	{
		return static::GetConvertFactorEx($curFrom, $curTo, $valDate);
	}

	/**
	 * @param float|int $curFrom
	 * @param float|int $curTo
	 * @param string $valDate
	 * @return float|int
	 */
	
	/**
	* <p>Метод возвращает коэффициент для перевода сумм из валюты curFrom в валюту curTo по курсу, установленному на дату valDate. Метод статический.</p>
	*
	*
	* @param string $curFrom  Исходная валюта.
	*
	* @param string $curTo  Валюта назначения.
	*
	* @param string $valDate = "" Дата, по курсу на которую нужно осуществить перевод. Если дата
	* пуста, то перевод идет по текущему курсу. Необязательный
	* параметр.<br><br> Дата должна быть указана в формате <b>YYYY-MM-DD</b>.
	*
	* @return float <p>Коэффициент для перевода. </p><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/getconvertfactorex.php
	* @author Bitrix
	*/
	public static function GetConvertFactorEx($curFrom, $curTo, $valDate = "")
	{
		/** @global CStackCacheManager $stackCacheManager */
		global $stackCacheManager;

		$curFrom = (string)$curFrom;
		$curTo = (string)$curTo;
		if($curFrom === '' || $curTo === '')
			return 0;
		if ($curFrom == $curTo)
			return 1;

		$valDate = (string)$valDate;
		if ($valDate === '')
			$valDate = date("Y-m-d");
		list($dpYear, $dpMonth, $dpDay) = explode("-", $valDate, 3);
		$dpDay += 1;
		if($dpYear < 2038 && $dpYear > 1970)
			$valDate = date("Y-m-d", mktime(0, 0, 0, $dpMonth, $dpDay, $dpYear));
		else
			$valDate = date("Y-m-d");

		$curFromRate = 0;
		$curFromRateCnt = 0;
		$curToRate = 1;
		$curToRateCnt = 1;

		if (defined("CURRENCY_SKIP_CACHE") && CURRENCY_SKIP_CACHE)
		{
			if ($res = static::_get_last_rates($valDate, $curFrom))
			{
				$curFromRate = (float)$res["RATE"];
				$curFromRateCnt = (int)$res["RATE_CNT"];
				if ($curFromRate <= 0)
				{
					$curFromRate = (float)$res["AMOUNT"];
					$curFromRateCnt = (int)$res["AMOUNT_CNT"];
				}
			}

			if ($res = static::_get_last_rates($valDate, $curTo))
			{
				$curToRate = (float)$res["RATE"];
				$curToRateCnt = (int)$res["RATE_CNT"];
				if ($curToRate <= 0)
				{
					$curToRate = (float)$res["AMOUNT"];
					$curToRateCnt = (int)$res["AMOUNT_CNT"];
				}
			}
		}
		else
		{
			$cacheTime = CURRENCY_CACHE_DEFAULT_TIME;
			if (defined("CURRENCY_CACHE_TIME"))
				$cacheTime = (int)CURRENCY_CACHE_TIME;

			$cacheKey = 'C_R_'.$valDate.'_'.$curFrom.'_'.$curTo;

			$stackCacheManager->SetLength("currency_rate", 10);
			$stackCacheManager->SetTTL("currency_rate", $cacheTime);
			if ($stackCacheManager->Exist("currency_rate", $cacheKey))
			{
				$arResult = $stackCacheManager->Get("currency_rate", $cacheKey);
			}
			else
			{
				if (!isset(self::$currentCache[$cacheKey]))
				{
					if ($res = static::_get_last_rates($valDate, $curFrom))
					{
						$curFromRate = (float)$res["RATE"];
						$curFromRateCnt = (int)$res["RATE_CNT"];
						if ($curFromRate <= 0)
						{
							$curFromRate = (float)$res["AMOUNT"];
							$curFromRateCnt = (int)$res["AMOUNT_CNT"];
						}
					}

					if ($res = static::_get_last_rates($valDate, $curTo))
					{
						$curToRate = (float)$res["RATE"];
						$curToRateCnt = (int)$res["RATE_CNT"];
						if ($curToRate <= 0)
						{
							$curToRate = (float)$res["AMOUNT"];
							$curToRateCnt = (int)$res["AMOUNT_CNT"];
						}
					}

					self::$currentCache[$cacheKey] = array(
						"curFromRate" => $curFromRate,
						"curFromRateCnt" => $curFromRateCnt,
						"curToRate" => $curToRate,
						"curToRateCnt" => $curToRateCnt
					);

					$stackCacheManager->Set("currency_rate", $cacheKey, self::$currentCache[$cacheKey]);
				}
				$arResult = self::$currentCache[$cacheKey];
			}
			$curFromRate = $arResult["curFromRate"];
			$curFromRateCnt = $arResult["curFromRateCnt"];
			$curToRate = $arResult["curToRate"];
			$curToRateCnt = $arResult["curToRateCnt"];
		}

		if ($curFromRate == 0 || $curToRateCnt == 0 || $curToRate == 0 || $curFromRateCnt == 0)
			return 0;

		return $curFromRate*$curToRateCnt/$curToRate/$curFromRateCnt;
	}

	public static function _get_last_rates($valDate, $cur)
	{
		return false;
	}
}