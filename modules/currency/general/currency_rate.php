<?
IncludeModuleLangFile(__FILE__);


/**
 * <b>CCurrencyRates</b> - класс для работы с курсами валют: сохранение, конвертация и пр.
 *
 *
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
	
	/**
	 * <p>Выполняет проверку полей курса при добавлении или изменении.</p>
	 *
	 *
	 *
	 *
	 * @param ACTIO $N  Равно <b>ADD</b> или <b>UPDATE</b> с учетом регистра. Если значение в другом
	 * регистре или другое значение, то возвращает <i>false</i> без текста
	 * ошибки (exception). Если значение равно <b>UPDATE</b>, то проверяется ID. Если
	 * ID &lt;= 0, то возвращается ошибка. В случае наличия в <i>arFields</i> ключа ID
	 * удалит его.
	 *
	 *
	 *
	 * @param arField $s  Значения ключей: <ul> <li>CURRENCY - не пустой код валюты, обрезается до 3
	 * символов. Обязательно будет проверен, если присутствует в
	 * массиве (даже если это обновление).</li> <li>DATE_RATE - дата курса БЕЗ
	 * ВРЕМЕНИ. Проверяется на валидность (должна быть в формате
	 * сайта/языка). Обязательно будет проверена, если присутствует в
	 * массиве (даже если обновление).</li> <li>RATE_CNT - номинал. Может быть
	 * только целым числом &gt; 0.</li> <li>RATE - курс. Может быть только
	 * вещественным числом &gt; 0.</li> </ul> <p>При добавлении обязательны все.
	 * При обновлении - RATE_CNT и RATE могут отсутсвовать.</p>
	 *
	 *
	 *
	 * @param I $D = 0 Код обновляемого курса.
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
		global $APPLICATION;
		global $DB;

		$arMsg = array();

		if ('UPDATE' != $ACTION && 'ADD' != $ACTION)
			return false;
		if (array_key_exists('ID', $arFields))
			unset($arFields['ID']);

		if ('UPDATE' == $ACTION && 0 >= intval($ID))
		{
			$arMsg[] = array('id' => 'ID','text' => GetMessage('BT_MOD_CURR_ERR_RATE_ID_BAD'));
		}

		if (!isset($arFields["CURRENCY"]))
		{
			$arMsg[] = array('id' => 'CURRENCY','text' => GetMessage('BT_MOD_CURR_ERR_RATE_CURRENCY_ABSENT'));
		}
		else
		{
			$arFields["CURRENCY"] = substr($arFields["CURRENCY"],0,3);
		}

		if (empty($arFields['DATE_RATE']))
		{
			$arMsg[] = array('id' => 'DATE_RATE','text' => GetMessage('BT_MOD_CURR_ERR_RATE_DATE_ABSENT'));
		}
		elseif (!$DB->IsDate($arFields['DATE_RATE']))
		{
			$arMsg[] = array('id' => 'DATE_RATE','text' => GetMessage('BT_MOD_CURR_ERR_RATE_DATE_FORMAT_BAD'));
		}

		if (is_set($arFields, 'RATE_CNT') || 'ADD' == $ACTION)
		{
			if (!isset($arFields['RATE_CNT']))
			{
				$arMsg[] = array('id' => 'RATE_CNT','text' => GetMessage('BT_MOD_CURR_ERR_RATE_RATE_CNT_ABSENT'));
			}
			elseif (0 >= intval($arFields['RATE_CNT']))
			{
				$arMsg[] = array('id' => 'RATE_CNT','text' => GetMessage('BT_MOD_CURR_ERR_RATE_RATE_CNT_BAD'));
			}
			else
			{
				$arFields['RATE_CNT'] = intval($arFields['RATE_CNT']);
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
				$arFields['RATE'] = doubleval($arFields['RATE']);
				if (!(0 < $arFields['RATE']))
				{
					$arMsg[] = array('id' => 'RATE','text' => GetMessage('BT_MOD_CURR_ERR_RATE_RATE_BAD'));
				}
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
	 * <p>Функция создает новый курс на заданную дату. Перед добавлением проверяет, нет ли курса этой валюты на этот день.</p> <p>Сбрасывает кеш <b>currency_rate</b> в случае успешного добавления, сбрасывает тэгированный кеш <b>currency_id_КОД_ВАЛЮТЫ</b>.</p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  <p>Ассоциативный массив параметров курса валюты, ключами которого
	 * являются названия параметров, а значениями - значения
	 * параметров.</p> <p>Допустимые ключи (Все поля обязательны):</p> <ul>
	 * <li>CURRENCY - код валюты, для которой добавляется курс;</li> <li>DATE_RATE - дата
	 * БЕЗ ВРЕМЕНИ, на которую устанавливается курс;</li> <li>RATE_CNT -
	 * количество единиц валюты, которое участвует в задании курса
	 * валюты (например, если 10 Датских крон стоят 48.7 рублей, то 10 - это
	 * количество единиц);</li> <li>RATE - курс валюты.</li> </ul>
	 *
	 *
	 *
	 * @return bool <p>Возвращает значение ID курса валют в случае успешного
	 * добавления и <i>False</i> - в противном случае. Текст ошибки выводится с
	 * помощью <code>$APPLICATION-&gt;GetException()</code>.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
		global $CACHE_MANAGER;
		global $APPLICATION;
		global $stackCacheManager;

		$arMsg = array();

		if (!CCurrencyRates::CheckFields("ADD", $arFields))
			return false;

		$db_result = $DB->Query("SELECT 'x' ".
			"FROM b_catalog_currency_rate ".
			"WHERE CURRENCY = '".$DB->ForSql($arFields["CURRENCY"])."' ".
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

			$ID = $DB->Add("b_catalog_currency_rate", $arFields);

			if (defined("BX_COMP_MANAGED_CACHE"))
				$CACHE_MANAGER->ClearByTag("currency_id_".$arFields["CURRENCY"]);

			return $ID;
		}
	}

	
	/**
	 * <p>Функция обновляет параметры записи в таблице курсов валют на значения из массива <i>arFields</i>. Перед обновлением выполняется проверка, нет ли курса этой валюты на эту дату с другим ID. Если есть - то произойдет ошибка.</p> <p>В случае успешного обновления сбрасываются кеш <b>currency_rate</b> и тэгированный кеш <b>currency_id_КОД_ВАЛЮТЫ</b>.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код записи.
	 *
	 *
	 *
	 * @param array $arFields  <p>Ассоциативный массив новых параметров курса валюты, ключами
	 * которого являются названия параметров, а значениями - значения
	 * параметров.</p> <p>Допустимые ключи:</p> <ul> <li>CURRENCY - код валюты
	 * (обязательный);</li> <li>DATE_RATE - дата БЕЗ ВРЕМЕНИ, за которую
	 * устанавливается курс (обязательный);</li> <li>RATE_CNT - количество
	 * единиц валюты, которое участвует в задании курса валюты
	 * (например, если 10 Датских крон стоят 48.7 рублей, то 10 - это
	 * количество единиц);</li> <li>RATE - курс валюты.</li> </ul>
	 *
	 *
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
		global $CACHE_MANAGER;
		global $APPLICATION;
		global $stackCacheManager;

		$ID = intval($ID);
		$arMsg = array();

		if (!CCurrencyRates::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$db_result = $DB->Query("SELECT 'x' ".
			"FROM b_catalog_currency_rate ".
			"WHERE CURRENCY = '".$DB->ForSql($arFields["CURRENCY"])."' ".
			"	AND DATE_RATE = ".$DB->CharToDateFunction($DB->ForSql($arFields["DATE_RATE"]), "SHORT")." ".
			"	AND ID<>".$ID." ");
		if ($db_result->Fetch())
		{
			$arMsg[] = array("id"=>"DATE_RATE", "text"=> GetMessage("ERROR_ADD_REC2"));
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		else
		{
			$strUpdate = $DB->PrepareUpdate("b_catalog_currency_rate", $arFields);
			if (!empty($strUpdate))
			{
				$strSql = "UPDATE b_catalog_currency_rate SET ".$strUpdate." WHERE ID = ".$ID;
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

				$stackCacheManager->Clear("currency_rate");

				if (defined("BX_COMP_MANAGED_CACHE"))
					$CACHE_MANAGER->ClearByTag("currency_id_".$arFields["CURRENCY"]);
			}
		}
		return true;
	}

	
	/**
	 * <p>Удаляет запись с кодом ID из таблицы курсов валют. Если удаляется несуществующий курс (нет курса с таким ID) - будет ошибка. </p> <p>В случае успеха сбросится кеш <b>currency_rate</b> и тэгированный <b>currency_id_КОД_ВАЛЮТЫ</b>.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код записи для удаления.
	 *
	 *
	 *
	 * @return bool <p>Возвращает значение <i>True</i> в случае успешного добавления и
	 * <i>False</i> - в противном случае. Текст ошибки выводится с помощью
	 * <code>$APPLICATION-&gt;GetException</code>.</p><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__delete.28de3643.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB;
		global $CACHE_MANAGER;
		global $stackCacheManager;
		global $APPLICATION;

		$arMsg = array();

		$ID = intval($ID);

		if (0 >= $ID)
			return false;

		$arFields = CCurrencyRates::GetByID($ID);
		if (!is_array($arFields))
		{
			$arMsg = array('id' => 'ID', 'text' => GetMessage('BT_MOD_CURR_ERR_RATE_CANT_DELETE_ABSENT_ID'));
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		$stackCacheManager->Clear("currency_rate");

		$strSql = "DELETE FROM b_catalog_currency_rate WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if (defined("BX_COMP_MANAGED_CACHE"))
			$CACHE_MANAGER->ClearByTag("currency_id_".$arFields['CURRENCY']);

		return true;
	}

	
	/**
	 * <p>Возвращает параметры курса валют с кодом ID </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код курса.
	 *
	 *
	 *
	 * @return array <p>Ассоциативный массив с ключами</p><table class="tnormal" width="100%"> <tr> <th
	 * width="20%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код курса.</td> </tr> <tr>
	 * <td>CURRENCY</td> <td>Код валюты.</td> </tr> <tr> <td>DATE_RATE</td> <td>Дата, за которую
	 * установлен курс.</td> </tr> <tr> <td>RATE_CNT</td> <td>количество единиц валюты,
	 * которое участвует в задании курса валюты (например, если 10
	 * Датских крон стоят 48.7 рублей, то 10 - это количество единиц)</td> </tr>
	 * <tr> <td>RATE</td> <td>курс валюты (одна из валют сайта должна иметь курс
	 * "по-умолчанию" 1, она называется базовой, остальные валюты имеют
	 * курс относительно базовой валюты)</td> </tr> </table><br><br>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrencyrates/ccurrencyrates__getbyid.2c90255f.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT C.*, ".$DB->DateToCharFunction("C.DATE_RATE", "SHORT")." as DATE_RATE ".
			"FROM b_catalog_currency_rate C ".
			"WHERE ID = ".$ID." ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return false;
	}


	
	/**
	 * <p>Функция возвращает список курсов валют, удовлетворяющих фильтру arFilter, отсортированный по полю by в направлении order </p>
	 *
	 *
	 *
	 *
	 * @param string &$by  Переменная , содержащая название поля для сортировки. Доступные
	 * названия:<br> date - дата курса (по умолчанию) <br> curr - валюта<br> rate - курс.
	 *
	 *
	 *
	 * @param string &$order  Переменная, содержащая направление сортировки. Допустимы
	 * значения:<br> asc - по возрастанию (по умолчанию)<br> desc - по убыванию.
	 *
	 *
	 *
	 * @param array $arFilter = Array() <p>Фильтр на записи представляет собой ассоциативный массив, в
	 * котором ключами являются названия фильтруемых полей, а
	 * значениями - условия фильтра.</p> <p>Допустимые поля для фильтра:<br>
	 * CURRENCY - код валюты<br> DATE_RATE - дата курса (выбираются записи с датами
	 * больше или равными указанной) <br> !DATE_RATE - дата курса (выбираются
	 * записи с датами меньше указанной) </p>
	 *
	 *
	 *
	 * @return CDBResult <p>Объект класса CDBResult, содержащий записи с ключами </p><table class="tnormal"
	 * width="100%"> <tr> <th width="30%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	 * курса.</td> </tr> <tr> <td>CURRENCY</td> <td>Код валюты.</td> </tr> <tr> <td>DATE_RATE</td> <td>Дата,
	 * за которую установлен курс.</td> </tr> <tr> <td>RATE_CNT</td> <td>количество
	 * единиц валюты, которое участвует в задании курса валюты
	 * (например, если 10 Датских крон стоят 48.7 рублей, то 10 - это
	 * количество единиц)</td> </tr> <tr> <td>RATE</td> <td>курс валюты (одна из валют
	 * сайта должна иметь курс "по-умолчанию" 1, она называется базовой,
	 * остальные валюты имеют курс относительно базовой валюты)</td> </tr>
	 * </table><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
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
	public static function GetList(&$by, &$order, $arFilter=Array())
	{
		global $DB, $DBType;
		$arSqlSearch = Array();

		if(!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		for($i=0; $i<count($filter_keys); $i++)
		{
			$val = $DB->ForSql($arFilter[$filter_keys[$i]]);
			if (strlen($val)<=0) continue;

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
				$arSqlSearch[] = "(C.DATE_RATE ".($bInvert?"<":">=")." ".($DBType == "mysql"?"CAST(":"").$DB->CharToDateFunction($DB->ForSql($val), "SHORT").($DBType == "mysql"?" AS DATE)":"")."".($bInvert?"":" OR C.DATE_RATE IS NULL").")";
				break;
			}
		}

		$strSqlSearch = "";
		for($i=0; $i<count($arSqlSearch); $i++)
		{
			if($i>0)
				$strSqlSearch .= " AND ";
			else
				$strSqlSearch = " WHERE ";

			$strSqlSearch .= " (".$arSqlSearch[$i].") ";
		}

		$strSql =
			"SELECT C.ID, C.CURRENCY, C.RATE_CNT, C.RATE, ".$DB->DateToCharFunction("C.DATE_RATE", "SHORT")." as DATE_RATE ".
			"FROM b_catalog_currency_rate C ".
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

	public function GetConvertFactorEx($curFrom, $curTo, $valDate = "")
	{
		global $stackCacheManager;

		if(strlen($curFrom) <= 0 || strlen($curTo) <= 0)
			return 0;

		if (strlen($valDate) <= 0)
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
			$cacheTime = 0;
			if($res = $this->_get_last_rates($valDate, $curFrom))
			{
				$curFromRate = doubleval($res["RATE"]);
				$curFromRateCnt = intval($res["RATE_CNT"]);
				if ($curFromRate <= 0)
				{
					$curFromRate = doubleval($res["AMOUNT"]);
					$curFromRateCnt = intval($res["AMOUNT_CNT"]);
				}
			}

			if($res = $this->_get_last_rates($valDate, $curTo))
			{
				$curToRate = doubleval($res["RATE"]);
				$curToRateCnt = intval($res["RATE_CNT"]);
				if ($curToRate <= 0)
				{
					$curToRate = doubleval($res["AMOUNT"]);
					$curToRateCnt = intval($res["AMOUNT_CNT"]);
				}
			}
		}
		else
		{
			$cacheTime = CURRENCY_CACHE_DEFAULT_TIME;
			if (defined("CURRENCY_CACHE_TIME"))
				$cacheTime = IntVal(CURRENCY_CACHE_TIME);

			$strCacheKey = "C_R_".$valDate."_".$curFrom."_".$curTo;

			$stackCacheManager->SetLength("currency_rate", 10);
			$stackCacheManager->SetTTL("currency_rate", $cacheTime);
			if ($stackCacheManager->Exist("currency_rate", $strCacheKey))
			{
				$arResult = $stackCacheManager->Get("currency_rate", $strCacheKey);

				$curFromRate = $arResult["curFromRate"];
				$curFromRateCnt = $arResult["curFromRateCnt"];
				$curToRate = $arResult["curToRate"];
				$curToRateCnt = $arResult["curToRateCnt"];
			}
			else
			{
				if($res = $this->_get_last_rates($valDate, $curFrom))
				{
					$curFromRate = doubleval($res["RATE"]);
					$curFromRateCnt = intval($res["RATE_CNT"]);
					if ($curFromRate <= 0)
					{
						$curFromRate = doubleval($res["AMOUNT"]);
						$curFromRateCnt = intval($res["AMOUNT_CNT"]);
					}
				}

				if($res = $this->_get_last_rates($valDate, $curTo))
				{
					$curToRate = doubleval($res["RATE"]);
					$curToRateCnt = intval($res["RATE_CNT"]);
					if ($curToRate <= 0)
					{
						$curToRate = doubleval($res["AMOUNT"]);
						$curToRateCnt = intval($res["AMOUNT_CNT"]);
					}
				}

				$arResult = array(
					"curFromRate" => $curFromRate,
					"curFromRateCnt" => $curFromRateCnt,
					"curToRate" => $curToRate,
					"curToRateCnt" => $curToRateCnt
				);

				$stackCacheManager->Set("currency_rate", $strCacheKey, $arResult);
			}
		}

		if($curFromRate == 0 || $curToRateCnt == 0 || $curToRate == 0 || $curFromRateCnt == 0)
			return 0;

		return DoubleVal($curFromRate*$curToRateCnt/$curToRate/$curFromRateCnt);
	}
}
?>