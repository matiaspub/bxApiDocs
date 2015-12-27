<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Currency;
use Bitrix\Catalog;

Loc::loadMessages(__FILE__);


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/index.php
 * @author Bitrix
 */
class CAllCatalogProduct
{
	const TYPE_PRODUCT = 1;
	const TYPE_SET = 2;
	const TYPE_SKU = 3;
	const TYPE_OFFER = 4;

	const TIME_PERIOD_HOUR = 'H';
	const TIME_PERIOD_DAY = 'D';
	const TIME_PERIOD_WEEK = 'W';
	const TIME_PERIOD_MONTH = 'M';
	const TIME_PERIOD_QUART = 'Q';
	const TIME_PERIOD_SEMIYEAR = 'S';
	const TIME_PERIOD_YEAR = 'Y';
	const TIME_PERIOD_DOUBLE_YEAR = 'T';

	protected static $arProductCache = array();

	protected static $usedCurrency = null;
	protected static $optimalPriceWithVat = true;
	protected static $useDiscount = true;

	protected static $saleIncluded = null;

	
	/**
	* <p>Метод позволяет указать, в какой валюте считать наименьшую цену в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a>. По умолчанию считается в базовой валюте. Если указана несуществующая валюта - она будет проигнорирована. Используется в штатном провайдере (<b>CCatalogProvider</b>) в паре с <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/getusedcurrency.php">CCatalogProduct::getUsedCurrency</a> для проведения всех расчетов в валюте магазина. Метод статический.</p> <p></p> <div class="note"> <b>Примечание:</b> метод действует в пределах одного хита.</div>
	*
	*
	* @param string $currency  Код валюты.
	*
	* @return mixed <p>Нет.</p></bo<p>Внутри метода выполняется проверка на существование
	* указанной валюты.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/setusedcurrency.php
	* @author Bitrix
	*/
	public static function setUsedCurrency($currency)
	{
		/** @var $oldCurrency string */
		static $oldCurrency = null;
		if ($oldCurrency !== null && $oldCurrency === $currency)
		{
			self::$usedCurrency = $currency;
			return;
		}
		$currency = CCurrency::checkCurrencyID($currency);
		if ($currency === false)
			return;
		$currencyIterator = Currency\CurrencyTable::getList(array(
			'select' => array('CURRENCY'),
			'filter' => array('=CURRENCY' => $currency)
		));
		if ($result = $currencyIterator->fetch())
		{
			self::$usedCurrency = $currency;
			$oldCurrency = $currency;
		}
		unset($result, $currencyIterator);
	}

	
	/**
	* <p>Метод возвращает текущую валюту, в которой считается наименьшая цена в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a>. Используется в штатном провайдере (<b>CCatalogProvider</b>) в паре с <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/setusedcurrency.php">CCatalogProduct::setUsedCurrency</a> для проведения всех расчетов в валюте магазина. Метод статический.</p> <p></p> <div class="note"> <b>Примечание:</b> метод действует в пределах одного хита.</div>
	*
	*
	* @return string <p>Код валюты либо <i>null</i>, тогда в этом случае для расчетов берется
	* базовая валюта сайта.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/getusedcurrency.php
	* @author Bitrix
	*/
	public static function getUsedCurrency()
	{
		return self::$usedCurrency;
	}

	
	/**
	* <p>Для расчета наименьшей цены в методе <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a> необходимо использовать базовую валюту. Метод сбрасывает заданную валюту (т.е. после его вызова все расчеты ведутся в базовой валюте). Метод статический.</p> <p></p> <div class="note"> <b>Примечание:</b> метод действует в пределах одного хита.</div>
	*
	*
	* @return mixed <p>Нет.</p></bo<br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/clearusedcurrency.php
	* @author Bitrix
	*/
	public static function clearUsedCurrency()
	{
		self::$usedCurrency = null;
	}

	
	/**
	* <p>Позволяет указать включать или нет НДС в значения полей ключа RESULT_PRICE метода <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a>. Метод статический.</p> <p></p> <div class="note"> <b>Примечание:</b> метод действует в пределах одного хита.</div>
	*
	*
	* @param mixed $mode  <i>true</i> - включать, <i>false</i> - выключать. По умолчанию - <i>true</i> (НДС
	* включен в результат).
	*
	* @return result_type <p>Нет.</p></bo<br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/setpricevatincludemode.php
	* @author Bitrix
	*/
	public static function setPriceVatIncludeMode($mode)
	{
		if ($mode !== true && $mode !== false)
			return;
		self::$optimalPriceWithVat = $mode;
	}

	
	/**
	* <p>Метод возвращает в текущее состояние включение (или нет) НДС в значения полей ключа RESULT_PRICE метода <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a> после изменения этого значения методом <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/setpricevatincludemode.php">CCatalogProduct::setUsedCurrency</a>. Метод статический.</p> <p></p> <div class="note"> <b>Примечание:</b> метод действует в пределах одного хита.</div>
	*
	*
	* @return result_type <p>нет</p><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/getpricevatincludemode.php
	* @author Bitrix
	*/
	public static function getPriceVatIncludeMode()
	{
		return self::$optimalPriceWithVat;
	}

	
	/**
	* <p>Метод позволяет запретить или разрешить использование скидок торгового каталога при вычислении минимальной цены товара. Действует на все данные, возвращаемые <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a>. Метод статический.</p> <p></p> <div class="note"> <b>Примечание:</b> метод действует в пределах одного хита.</div>
	*
	*
	* @param mixed $mode  <i>true</i> - разрешить, <i>false</i> - запретить. По умолчанию - разрешено.
	*
	* @return mixed <p>Нет.</p></bo<br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/setusediscount.php
	* @author Bitrix
	*/
	public static function setUseDiscount($use)
	{
		if ($use !== true && $use !== false)
			return;
		self::$useDiscount = $use;
	}

	
	/**
	* <p>Метод возвращает в текущее разрешение на использование скидок торгового каталога при вычислении минимальной цены товара методом <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php">CCatalogProduct::GetOptimalPrice</a> после изменения этого значения методом <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/setusediscount.php">CCatalogProduct::setUseDiscount</a>. Метод статический.</p> <p></p> <div class="note"> <b>Примечание:</b> метод действует в пределах одного хита.</div>
	*
	*
	* @return mixed <p>Нет.</p></bo<br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/getusediscount.php
	* @author Bitrix
	*/
	public static function getUseDiscount()
	{
		return self::$useDiscount;
	}

	public static function ClearCache()
	{
		self::$arProductCache = array();
	}

	/**
	 * @param array $product
	 * @return bool
	 */
	public static function isAvailable($product)
	{
		$result = true;
		if (!empty($product) && is_array($product))
		{
			if (isset($product['QUANTITY']) && isset($product['QUANTITY_TRACE']) && isset($product['CAN_BUY_ZERO']))
			{
				$result = !((float)$product['QUANTITY'] <= 0 && $product['QUANTITY_TRACE'] == 'Y' && $product['CAN_BUY_ZERO'] == 'N');
			}
		}
		return $result;
	}

	/**
	 * @deprecated deprecated since catalog 15.5.2
	 * @see \Bitrix\Catalog\ProductTable::isExistProduct()
	 * @param int $intID
	 * @return bool
	 */
	
	/**
	* <p>Метод проверяет наличие записи в таблице <i>b_catalog_product</i> с информацией (доступное количество, разрешена ли покупка при отсутствии товара и т.д.) для товара с кодом <i>intID</i>. Метод статический.</p>
	*
	*
	* @param int $intID  Код товара.
	*
	* @return bool <p> В случае наличия информации о товаре возвращает true, иначе -
	* false.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/isexistproduct.php
	* @author Bitrix
	* @deprecated deprecated since catalog 15.5.2  ->  \Bitrix\Catalog\ProductTable::isExistProduct()
	*/
	public static function IsExistProduct($intID)
	{
		return Catalog\ProductTable::isExistProduct($intID);
	}

	static public function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION;

		$arMsg = array();
		$boolResult = true;

		$ACTION = strtoupper($ACTION);
		$ID = (int)$ID;
		if ($ACTION == "ADD" && (!is_set($arFields, "ID") || (int)$arFields["ID"]<=0))
		{
			$arMsg[] = array('id' => 'ID','text' => Loc::getMessage('KGP_EMPTY_ID'));
			$boolResult = false;
		}
		if ($ACTION != "ADD" && $ID <= 0)
		{
			$arMsg[] = array('id' => 'ID','text' => Loc::getMessage('KGP_EMPTY_ID'));
			$boolResult = false;
		}

		if ($ACTION != "ADD" && array_key_exists('ID', $arFields))
			unset($arFields["ID"]);
		if (array_key_exists('TYPE', $arFields))
			unset($arFields['TYPE']);
		if ('ADD' == $ACTION)
		{
			if (!array_key_exists('SUBSCRIBE', $arFields))
				$arFields['SUBSCRIBE'] = '';
			$arFields['TYPE'] = self::TYPE_PRODUCT;
		}

		if (is_set($arFields, "ID") || $ACTION=="ADD")
			$arFields["ID"] = (int)$arFields["ID"];
		if (is_set($arFields, "QUANTITY") || $ACTION=="ADD")
			$arFields["QUANTITY"] = doubleval($arFields["QUANTITY"]);
		if (is_set($arFields, "QUANTITY_RESERVED") || $ACTION=="ADD")
			$arFields["QUANTITY_RESERVED"] = doubleval($arFields["QUANTITY_RESERVED"]);
		if (is_set($arFields, "OLD_QUANTITY"))
			$arFields["OLD_QUANTITY"] = doubleval($arFields["OLD_QUANTITY"]);
		if (is_set($arFields, "WEIGHT") || $ACTION=="ADD")
			$arFields["WEIGHT"] = doubleval($arFields["WEIGHT"]);
		if (is_set($arFields, "WIDTH") || $ACTION=="ADD")
			$arFields["WIDTH"] = doubleval($arFields["WIDTH"]);
		if (is_set($arFields, "LENGTH") || $ACTION=="ADD")
			$arFields["LENGTH"] = doubleval($arFields["LENGTH"]);
		if (is_set($arFields, "HEIGHT") || $ACTION=="ADD")
			$arFields["HEIGHT"] = doubleval($arFields["HEIGHT"]);

		if (is_set($arFields, "VAT_ID") || $ACTION=="ADD")
			$arFields["VAT_ID"] = intval($arFields["VAT_ID"]);
		if ((is_set($arFields, "VAT_INCLUDED") || $ACTION=="ADD") && ($arFields["VAT_INCLUDED"] != "Y"))
			$arFields["VAT_INCLUDED"] = "N";

		if ((is_set($arFields, "QUANTITY_TRACE") || $ACTION=="ADD") && ($arFields["QUANTITY_TRACE"] != "Y" && $arFields["QUANTITY_TRACE"] != "N"))
			$arFields["QUANTITY_TRACE"] = "D";
		if ((is_set($arFields, "CAN_BUY_ZERO") || $ACTION=="ADD") && ($arFields["CAN_BUY_ZERO"] != "Y" && $arFields["CAN_BUY_ZERO"] != "N"))
			$arFields["CAN_BUY_ZERO"] = "D";
		if ((is_set($arFields, "NEGATIVE_AMOUNT_TRACE") || $ACTION=="ADD") && ($arFields["NEGATIVE_AMOUNT_TRACE"] != "Y" && $arFields["NEGATIVE_AMOUNT_TRACE"] != "N"))
			$arFields["NEGATIVE_AMOUNT_TRACE"] = "D";

		if ((is_set($arFields, "PRICE_TYPE") || $ACTION=="ADD") && ($arFields["PRICE_TYPE"] != "R") && ($arFields["PRICE_TYPE"] != "T"))
			$arFields["PRICE_TYPE"] = "S";

		if ((is_set($arFields, "RECUR_SCHEME_TYPE") || $ACTION=="ADD") && (StrLen($arFields["RECUR_SCHEME_TYPE"]) <= 0 || !in_array($arFields["RECUR_SCHEME_TYPE"], CCatalogProduct::GetTimePeriodTypes(false))))
		{
			$arFields["RECUR_SCHEME_TYPE"] = self::TIME_PERIOD_DAY;
		}

		if ((is_set($arFields, "RECUR_SCHEME_LENGTH") || $ACTION=="ADD") && (intval($arFields["RECUR_SCHEME_LENGTH"])<=0))
			$arFields["RECUR_SCHEME_LENGTH"] = 0;

		if ((is_set($arFields, "TRIAL_PRICE_ID") || $ACTION=="ADD") && (intval($arFields["TRIAL_PRICE_ID"])<=0))
			$arFields["TRIAL_PRICE_ID"] = false;

		if ((is_set($arFields, "WITHOUT_ORDER") || $ACTION=="ADD") && ($arFields["WITHOUT_ORDER"] != "Y"))
			$arFields["WITHOUT_ORDER"] = "N";

		if ((is_set($arFields, "SELECT_BEST_PRICE") || $ACTION=="ADD") && ($arFields["SELECT_BEST_PRICE"] != "N"))
			$arFields["SELECT_BEST_PRICE"] = "Y";

		if (is_set($arFields, 'PURCHASING_PRICE'))
		{
			if ($ACTION != 'ADD')
			{
				if ($arFields['PURCHASING_PRICE'] === null || trim($arFields['PURCHASING_PRICE']) == '')
					unset($arFields['PURCHASING_PRICE']);
			}
		}
		if (is_set($arFields, 'PURCHASING_PRICE'))
		{
			$arFields['PURCHASING_PRICE'] = str_replace(',', '.', $arFields['PURCHASING_PRICE']);
			$arFields['PURCHASING_PRICE'] = (float)$arFields['PURCHASING_PRICE'];
		}
		if (is_set($arFields, 'PURCHASING_CURRENCY') || ($ACTION=="ADD" && is_set($arFields, 'PURCHASING_PRICE')))
		{
			if (empty($arFields['PURCHASING_CURRENCY']))
			{
				$arMsg[] = array('id' => 'PURCHASING_CURRENCY','text' => Loc::getMessage('BT_MOD_CATALOG_PROD_ERR_COST_CURRENCY'));
				$boolResult = false;
			}
			else
			{
				$arFields['PURCHASING_CURRENCY'] = strtoupper($arFields['PURCHASING_CURRENCY']);
			}
		}
		if ((is_set($arFields, 'BARCODE_MULTI') || 'ADD' == $ACTION) && 'Y' != $arFields['BARCODE_MULTI'])
			$arFields['BARCODE_MULTI'] = 'N';
		if (array_key_exists('SUBSCRIBE', $arFields))
		{
			if ('Y' != $arFields['SUBSCRIBE'] && 'N' != $arFields['SUBSCRIBE'])
				$arFields['SUBSCRIBE'] = 'D';
		}

		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ThrowException($obError);
		}
		return $boolResult;
	}

	public static function ParseQueryBuildField($field)
	{
		$field = (string)$field;
		if ($field == '')
			return false;
		$field = strtoupper($field);
		if (strncmp($field, 'CATALOG_', 8) != 0)
			return false;

		$iNum = 0;
		$field = substr($field, 8);
		$p = strrpos($field, '_');
		if ($p !== false && $p > 0)
		{
			$iNum = (int)substr($field, $p+1);
			if ($iNum > 0)
				$field = substr($field, 0, $p);
		}
		return array(
			'FIELD' => $field,
			'NUM' => $iNum
		);
	}

	
	/**
	* <p>Метод по коду товара ID возвращает массив параметров товара (которые относятся к товару как к таковому) или <i>false</i>, если код товара задан некорректно или отсутствует запись базе. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код товара.
	*
	* @return array <p>Ассоциативный массив параметров товара с ключами</p> <table
	* class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> <th width="10%">С
	* версии</th> </tr> <tr> <td>ID</td> <td>Код товара.</td> <td></td> </tr> <tr> <td>QUANTITY</td>
	* <td>Количество на складе.</td> <td></td> </tr> <tr> <td>QUANTITY_RESERVED</td>
	* <td>Зарезервированное количество.</td> <td>12.5.0</td> </tr> <tr> <td>QUANTITY_TRACE</td>
	* <td>Определяет ведется ли количественный учет (Y/N). До версии 12.5.0
	* параметр назывался "уменьшать количество при оформлении заказа".
	* Оригинальное значение доступно в ключе QUANTITY_TRACE_ORIG.</td> <td></td> </tr> <tr>
	* <td>QUANTITY_TRACE_ORIG</td> <td>Флаг (Y/N/D<b>*</b>) "включить количественный учет".</td>
	* <td>12.0.0</td> </tr> <tr> <td>WEIGHT</td> <td>Вес единицы товара в граммах.</td> <td></td>
	* </tr> <tr> <td>WIDTH</td> <td>Ширина товара (в мм).</td> <td>14.0.0</td> </tr> <tr> <td>LENGTH</td>
	* <td>Длина товара (в мм).</td> <td>14.0.0</td> </tr> <tr> <td>HEIGHT</td> <td>Высота товара (в
	* мм).</td> <td>14.0.0</td> </tr> <tr> <td>MEASURE</td> <td>ID единицы измерения.</td> <td>14.0.0</td>
	* </tr> <tr> <td>PRICE_TYPE</td> <td>Тип цены (S - одноразовый платеж, R - регулярные
	* платежи, T - пробная подписка).</td> <td></td> </tr> <tr> <td>RECUR_SCHEME_TYPE</td> <td>Тип
	* периода подписки ("H" - час, "D" - сутки, "W" - неделя, "M" - месяц, "Q" -
	* квартал, "S" - полугодие, "Y" - год).</td> <td></td> </tr> <tr> <td>RECUR_SCHEME_LENGTH</td>
	* <td>Длина периода подписки.</td> <td></td> </tr> <tr> <td>VAT_ID</td> <td>Идентификатор
	* ставки НДС, привязанной к товару.</td> <td></td> </tr> <tr> <td>VAT_INCLUDED</td>
	* <td>Включен ли НДС в цену или нет.</td> <td></td> </tr> <tr> <td>TRIAL_PRICE_ID</td> <td>Код
	* товара, для которого данный товар является пробным.</td> <td></td> </tr> <tr>
	* <td>WITHOUT_ORDER</td> <td>Флаг "Продление подписки без оформления заказа".</td>
	* <td></td> </tr> <tr> <td>TIMESTAMP_X</td> <td>Дата последнего изменения записи.
	* Задается в формате сайта.</td> <td></td> </tr> <tr> <td>CAN_BUY_ZERO</td> <td>Разрешена
	* ли покупка при отсутствии товара (Y/N). Оригинальное значение
	* доступно в ключе CAN_BUY_ZERO_ORIG.</td> <td>12.0.0</td> </tr> <tr> <td>CAN_BUY_ZERO_ORIG</td>
	* <td>Флаг (Y/N/D<b>*</b>) "разрешить покупку при отсутствии товара".</td>
	* <td>12.0.0</td> </tr> <tr> <td>NEGATIVE_AMOUNT_TRACE</td> <td>Разрешено ли отрицательное
	* количество товара (Y/N). Оригинальное значение доступно в ключе
	* NEGATIVE_AMOUNT_TRACE_ORIG.</td> <td>12.0.0</td> </tr> <tr> <td>NEGATIVE_AMOUNT_TRACE_ORIG</td> <td>Флаг
	* (Y/N/D<b>*</b>) "разрешить отрицательное количество товара".</td> <td>12.0.0</td>
	* </tr> <tr> <td>PURCHASING_PRICE</td> <td>Величина закупочной цены.</td> <td>12.5.0</td> </tr> <tr>
	* <td>PURCHASING_CURRENCY</td> <td>Валюта закупочной цены.</td> <td>12.5.0</td> </tr> <tr>
	* <td>SUBSCRIBE</td> <td>Разрешение/запрет подписки при отсутствии товара
	* (Y/N/D<b>*</b>).</td> <td>14.0.0</td> </tr> <tr> <td>TYPE</td> <td>Тип товара (для типа товара
	* "комплект" значение равно "2", во всех других случаях - "1").</td>
	* <td>14.0.0</td> </tr> <tr> <td>TMP_ID</td> <td>Временный строковый идентификатор,
	* используемый для служебных целей.</td> <td></td> </tr> <tr> <td>BARCODE_MULTI</td>
	* <td>(Y/N) Определяет каждый ли экземпляр товара имеет собственный
	* штрихкод.</td> <td>12.5.0</td> </tr> <tr><td colspan="3"> <b>*</b> - значение берется из
	* настроек модуля.</td></tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $ID = 5;
	* $ar_res = CCatalogProduct::GetByID($ID);
	* echo "&lt;br&gt;Товар с кодом ".$ID." имеет следующие параметры:&lt;pre&gt;";
	* print_r($ar_res);
	* echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getbyid.cc16046d.php
	* @author Bitrix
	*/
	static public function GetByID($ID)
	{
		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		if (isset(self::$arProductCache[$ID]))
		{
			return self::$arProductCache[$ID];
		}
		else
		{
			$rsProducts = CCatalogProduct::GetList(
				array(),
				array('ID' => $ID),
				false,
				false,
				array(
					'ID', 'QUANTITY', 'QUANTITY_RESERVED', 'QUANTITY_TRACE', 'QUANTITY_TRACE_ORIG', 'WEIGHT', 'WIDTH', 'LENGTH', 'HEIGHT', 'MEASURE',
					'VAT_ID', 'VAT_INCLUDED', 'CAN_BUY_ZERO', 'CAN_BUY_ZERO_ORIG', 'NEGATIVE_AMOUNT_TRACE', 'NEGATIVE_AMOUNT_TRACE_ORIG',
					'PRICE_TYPE', 'RECUR_SCHEME_TYPE', 'RECUR_SCHEME_LENGTH', 'TRIAL_PRICE_ID', 'WITHOUT_ORDER', 'SELECT_BEST_PRICE',
					'TMP_ID', 'PURCHASING_PRICE', 'PURCHASING_CURRENCY', 'BARCODE_MULTI', 'TIMESTAMP_X', 'SUBSCRIBE', 'SUBSCRIBE_ORIG', 'TYPE'
				)
			);
			if ($arProduct = $rsProducts->Fetch())
			{
				$arProduct['ID'] = (int)$arProduct['ID'];
				self::$arProductCache[$ID] = $arProduct;
				if (defined('CATALOG_GLOBAL_VARS') && CATALOG_GLOBAL_VARS == 'Y')
				{
					/** @var array $CATALOG_PRODUCT_CACHE */
					global $CATALOG_PRODUCT_CACHE;
					$CATALOG_PRODUCT_CACHE = self::$arProductCache;
				}
				return $arProduct;
			}
		}
		return false;
	}

	
	/**
	* <p>Метод по коду товара ID возвращает все параметры этого товара, включая относящиеся к товару как к элементу информационного блока. Метод динамичный.</p> <p></p> <div class="note"> <b>Важно!</b> Метод нежелателен к использованию в связи с избыточностью данных и низкой производительностью.</div>
	*
	*
	* @param int $ID  Код товара.
	*
	* @param boolean $boolAllValues = false Параметр определяет возвращать ли все значения множественных
	* свойств (<i>true</i>) или нет (<i>false</i>). Необязательный параметр.
	*
	* @return array <p>Возвращает ассоциативный массив параметров товара или <i>false</i> в
	* случае ошибки. Для просмотра всех доступных параметров
	* воспользуйтесь кодом из приведенного ниже примера. </p> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $ID = 5;
	* $ar_res = CCatalogProduct::GetByIDEx($ID);
	* echo "&lt;br&gt;Товар с кодом ".$ID." имеет следующие параметры:&lt;br&gt;&lt;pre&gt;";
	* print_r($ar_res);
	* echo "&lt;/pre&gt;";
	* ?&gt;
	* 
	* 
	* 
	* <b>Примечание:</b> до версии модуля <b>12.5.6</b> на агенте этот метод выдает ошибку, т.к. использует объект $USER, который еще не определен (см. <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3436" target="_blank">учебный курс</a>).
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getbyidex.c95826cc.php
	* @author Bitrix
	*/
	static public function GetByIDEx($ID, $boolAllValues = false)
	{
		$boolAllValues = ($boolAllValues === true);
		$ID = (int)$ID;
		if ($ID <= 0)
			return false;
		$arFilter = array("ID" => $ID, "ACTIVE" => "Y", "ACTIVE_DATE" => "Y");

		$dbIBlockElement = CIBlockElement::GetList(array(), $arFilter);
		if ($arIBlockElement = $dbIBlockElement->GetNext())
		{
			if ($arIBlock = CIBlock::GetArrayByID($arIBlockElement["IBLOCK_ID"]))
			{
				$arIBlockElement["IBLOCK_ID"] = $arIBlock["ID"];
				$arIBlockElement["IBLOCK_NAME"] = htmlspecialcharsbx($arIBlock["NAME"]);
				$arIBlockElement["~IBLOCK_NAME"] = $arIBlock["NAME"];
				$arIBlockElement["PROPERTIES"] = false;
				$dbProps = CIBlockElement::GetProperty($arIBlock["ID"], $ID, "sort", "asc", array("ACTIVE"=>"Y", "NON_EMPTY"=>"Y"));
				if ($arProp = $dbProps->Fetch())
				{
					$arAllProps = array();
					do
					{
						$strID = (strlen($arProp["CODE"])>0 ? $arProp["CODE"] : $arProp["ID"]);
						if (is_array($arProp["VALUE"]))
						{
							foreach ($arProp["VALUE"] as &$strOneValue)
							{
								$strOneValue = htmlspecialcharsbx($strOneValue);
							}
							if (isset($strOneValue))
								unset($strOneValue);
						}
						else
						{
							$arProp["VALUE"] = htmlspecialcharsbx($arProp["VALUE"]);
						}

						if ($boolAllValues && 'Y' == $arProp['MULTIPLE'])
						{
							if (!isset($arAllProps[$strID]))
							{
								$arAllProps[$strID] = array(
									"NAME" => htmlspecialcharsbx($arProp["NAME"]),
									"VALUE" => array($arProp["VALUE"]),
									"VALUE_ENUM" => array(htmlspecialcharsbx($arProp["VALUE_ENUM"])),
									"VALUE_XML_ID" => array(htmlspecialcharsbx($arProp["VALUE_XML_ID"])),
									"DEFAULT_VALUE" => htmlspecialcharsbx($arProp["DEFAULT_VALUE"]),
									"SORT" => htmlspecialcharsbx($arProp["SORT"]),
									"MULTIPLE" => $arProp['MULTIPLE'],
								);
							}
							else
							{
								$arAllProps[$strID]['VALUE'][] = $arProp["VALUE"];
								$arAllProps[$strID]['VALUE_ENUM'][] = htmlspecialcharsbx($arProp["VALUE_ENUM"]);
								$arAllProps[$strID]['VALUE_XML_ID'][] = htmlspecialcharsbx($arProp["VALUE_XML_ID"]);
							}
						}
						else
						{
							$arAllProps[$strID] = array(
								"NAME" => htmlspecialcharsbx($arProp["NAME"]),
								"VALUE" => $arProp["VALUE"],
								"VALUE_ENUM" => htmlspecialcharsbx($arProp["VALUE_ENUM"]),
								"VALUE_XML_ID" => htmlspecialcharsbx($arProp["VALUE_XML_ID"]),
								"DEFAULT_VALUE" => htmlspecialcharsbx($arProp["DEFAULT_VALUE"]),
								"SORT" => htmlspecialcharsbx($arProp["SORT"]),
								"MULTIPLE" => $arProp['MULTIPLE'],
							);
						}
					}
					while($arProp = $dbProps->Fetch());

					$arIBlockElement["PROPERTIES"] = $arAllProps;
				}

				// bugfix: 2007-07-31 by Sigurd
				$arIBlockElement["PRODUCT"] = CCatalogProduct::GetByID($ID);

				$dbPrices = CPrice::GetList(array("SORT" => "ASC"), array("PRODUCT_ID" => $ID));
				if ($arPrices = $dbPrices->Fetch())
				{
					$arAllPrices = array();
					do
					{
						$arAllPrices[$arPrices["CATALOG_GROUP_ID"]] = array(
							"EXTRA_ID" => intval($arPrices["EXTRA_ID"]),
							"PRICE" => doubleval($arPrices["PRICE"]),
							"CURRENCY" => htmlspecialcharsbx($arPrices["CURRENCY"])
						);
					}
					while($arPrices = $dbPrices->Fetch());

					$arIBlockElement["PRICES"] = $arAllPrices;
				}

				return $arIBlockElement;
			}
		}

		return false;
	}

	
	/**
	* <p>Уменьшает количество на складе товара с кодом <i>ProductID</i> на величину <i>DeltaQuantity</i>, если указанное количество есть на складе и у товара установлен флаг "уменьшать количество товара при заказе". Метод динамичный.</p>
	*
	*
	* @param int $ProductID  Код товара.
	*
	* @param int $DeltaQuantity  Число, на которое нужно уменьшить количество товара на складе.
	*
	* @return bool <p>Возвращает <i>true</i> в случае успешного уменьшения и <i>false</i> - в
	* противном случае.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Уменьшим количество на складе товара 5 на 12 штук
	* CCatalogProduct::QuantityTracer(5, 12);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__quantitytracer.8cc817fa.php
	* @author Bitrix
	*/
	static public function QuantityTracer($ProductID, $DeltaQuantity)
	{
		global $CACHE_MANAGER;

		$boolClearCache = false;

		$ProductID = (int)$ProductID;
		if ($ProductID <= 0)
			return false;
		$DeltaQuantity = (float)$DeltaQuantity;
		if ($DeltaQuantity==0)
			return false;

		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('ID' => $ProductID),
			false,
			false,
			array('ID', 'CAN_BUY_ZERO', 'NEGATIVE_AMOUNT_TRACE', 'QUANTITY_TRACE', 'QUANTITY', 'ELEMENT_IBLOCK_ID')
		);
		if (($arProduct = $rsProducts->Fetch())
			&& ($arProduct["QUANTITY_TRACE"]=="Y"))
		{
			$strAllowNegativeAmount = $arProduct["NEGATIVE_AMOUNT_TRACE"];

			$arFields = array();
			$arFields["QUANTITY"] = (float)$arProduct["QUANTITY"] - $DeltaQuantity;

			if ('Y' != $arProduct['CAN_BUY_ZERO'])
			{
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$boolClearCache = (0 >= $arFields["QUANTITY"]*$arProduct["QUANTITY"]);
				}
			}

			if ('Y' != $arProduct['CAN_BUY_ZERO'] || 'Y' != $strAllowNegativeAmount)
			{
				if (0 >= $arFields["QUANTITY"])
					$arFields["QUANTITY"] = 0;
			}

			$arFields['OLD_QUANTITY'] = $arProduct["QUANTITY"];
			CCatalogProduct::Update($arProduct["ID"], $arFields);

			if ($boolClearCache)
				$CACHE_MANAGER->ClearByTag('iblock_id_'.$arProduct['ELEMENT_IBLOCK_ID']);

			$arProduct['OLD_QUANTITY'] = $arFields['OLD_QUANTITY'];
			$arProduct['QUANTITY'] = $arFields['QUANTITY'];
			$arProduct['ALLOW_NEGATIVE_AMOUNT'] = $strAllowNegativeAmount;
			$arProduct['DELTA'] = $DeltaQuantity;
			foreach (GetModuleEvents("catalog", "OnProductQuantityTrace", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($arProduct["ID"], $arProduct));
			}

			return true;
		}

		return false;
	}

	/**
	 * @param int $productID
	 * @param int|float $quantity
	 * @param array $arUserGroups
	 * @return bool|float|int
	 */
	
	/**
	* <p>Метод по коду товара ID возвращает количество товара, доступное для покупки. Метод динамичный.</p>
	*
	*
	* @param int $productID  Код товара.
	*
	* @param  $int  Количество, ближайшее продаваемое количество к которому
	* необходимо найти.
	*
	* @param quantit $y = 1[ массив групп, которым принадлежит пользователь. Для текущего
	* пользователя он возвращается методом $USER-&gt;GetUserGroupArray() 
	*
	* @param array $arUserGroups = array()]] 
	*
	* @return int <p>Метод возвращает <i>false</i> или ближайшее к заданному количество
	* товара, которое можно положить в корзину. Метод имеет смысл при
	* работе с диапазонами цен.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, $USER-&gt;GetUserGroupArray(), $renewal);
	* if (!$arPrice || count($arPrice) &lt;= 0)
	* {
	*     if ($nearestQuantity = CCatalogProduct::GetNearestQuantityPrice($productID, $quantity, $USER-&gt;GetUserGroupArray()))
	*     {
	*         $quantity = $nearestQuantity;
	*         $arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, $USER-&gt;GetUserGroupArray(), $renewal);
	*     }
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getnearestquantityprice.3c16046d.php
	* @author Bitrix
	*/
	static public function GetNearestQuantityPrice($productID, $quantity = 1, $arUserGroups = array())
	{
		static $eventOnGetExists = null;
		static $eventOnResultExists = null;

		global $APPLICATION;

		if ($eventOnGetExists === true || $eventOnGetExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnGetNearestQuantityPrice', true) as $arEvent)
			{
				$eventOnGetExists = true;
				$mxResult = ExecuteModuleEventEx($arEvent, array($productID, $quantity, $arUserGroups));
				if ($mxResult !== true)
					return $mxResult;
			}
			if ($eventOnGetExists === null)
				$eventOnGetExists = false;
		}

		// Check input params
		$productID = (int)$productID;
		if ($productID <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("BT_MOD_CATALOG_PROD_ERR_PRODUCT_ID_ABSENT"), "NO_PRODUCT_ID");
			return false;
		}

		$quantity = (float)$quantity;
		if ($quantity <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("BT_MOD_CATALOG_PROD_ERR_QUANTITY_ABSENT"), "NO_QUANTITY");
			return false;
		}

		if (!is_array($arUserGroups) && (int)$arUserGroups.'|' == (string)$arUserGroups.'|')
			$arUserGroups = array((int)$arUserGroups);

		if (!is_array($arUserGroups))
			$arUserGroups = array();

		if (!in_array(2, $arUserGroups))
			$arUserGroups[] = 2;

		$quantityDifference = -1;
		$nearestQuantity = -1;

		// Find nearest quantity
		$dbPriceList = CPrice::GetListEx(
			array(),
			array(
				"PRODUCT_ID" => $productID,
				"GROUP_GROUP_ID" => $arUserGroups,
				"GROUP_BUY" => "Y"
			),
			false,
			false,
			array("ID", "QUANTITY_FROM", "QUANTITY_TO")
		);
		while ($arPriceList = $dbPriceList->Fetch())
		{
			$arPriceList['QUANTITY_FROM'] = (float)$arPriceList['QUANTITY_FROM'];
			$arPriceList['QUANTITY_TO'] = (float)$arPriceList['QUANTITY_TO'];
			if ($quantity >= $arPriceList["QUANTITY_FROM"]
				&& ($quantity <= $arPriceList["QUANTITY_TO"] || $arPriceList["QUANTITY_TO"] == 0))
			{
				$nearestQuantity = $quantity;
				break;
			}

			if ($quantity < $arPriceList["QUANTITY_FROM"])
			{
				$nearestQuantity_tmp = $arPriceList["QUANTITY_FROM"];
				$quantityDifference_tmp = $arPriceList["QUANTITY_FROM"] - $quantity;
			}
			else
			{
				$nearestQuantity_tmp = $arPriceList["QUANTITY_TO"];
				$quantityDifference_tmp = $quantity - $arPriceList["QUANTITY_TO"];
			}

			if ($quantityDifference < 0 || $quantityDifference_tmp < $quantityDifference)
			{
				$quantityDifference = $quantityDifference_tmp;
				$nearestQuantity = $nearestQuantity_tmp;
			}
		}

		if ($eventOnResultExists === true || $eventOnResultExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnGetNearestQuantityPriceResult', true) as $arEvent)
			{
				$eventOnResultExists = true;
				if (ExecuteModuleEventEx($arEvent, array(&$nearestQuantity)) === false)
					return false;
			}
			if ($eventOnResultExists === null)
				$eventOnResultExists = false;
		}

		return ($nearestQuantity > 0 ? $nearestQuantity : false);
	}

	/**
	 * @param int $intProductID
	 * @param int|float $quantity
	 * @param array $arUserGroups
	 * @param string $renewal
	 * @param array $arPrices
	 * @param bool|string $siteID
	 * @param bool|array $arDiscountCoupons
	 * @return array|bool
	 */
	
	/**
	* <p>Метод возвращает параметры наименьшей цены товара productID в количестве quantity для посетителя, входящего в группы пользователей arUserGroups. Метод динамичный.</p>
	*
	*
	* @param int $intProductID  Код товара.<br><br> До версии <b>12.0.0</b> параметр назывался <b>productID</b>.
	*
	* @param  $int  Приобретаемое количество.
	*
	* @param quantit $y = 1[ массив групп, которым принадлежит пользователь. Для текущего
	* пользователя он возвращается методом $USER-&gt;GetUserGroupArray() 
	*
	* @param array $arUserGroups = array()[ "Y", если вычисляется для продления товара (продажа контента), "N" в
	* остальных случаях.
	*
	* @param string $renewal = "N"[ Массив цен. Если он установлен, то пересчет идет не по ценам из
	* базы данных, а по ценам из этого массива. Представляет собой
	* массив ассоциативных массивов с ключами: <ul> <li> <b>ID</b> - код цены;</li>
	* <li> <b>PRICE</b> - цена;</li> <li> <b>CURRENCY</b> - валюта;</li> <li> <b>CATALOG_GROUP_ID</b> - код
	* типа цен.</li> </ul>
	*
	* @param array $arPrices = array()[ Сайт, для которого производится вычисление. Если парамерт равен
	* <i>false</i>, то берется текущий сайт.
	*
	* @param string $siteID = false[ Массив купонов, которые влияют на выборку скидок. Если задано
	* значение <i>false</i>, то массив купонов будет взят из
	* <b>CCatalogDiscountCoupon::GetCoupons</b>
	*
	* @param array $arDiscountCoupons = false]]]]]] 
	*
	* @return array <p>Массив вида:</p> <pre class="syntax">array( "PRICE" =&gt;
	* массив_параметров_минимальной_цены, "DISCOUNT_PRICE" =&gt;
	* минимальная_цена_в_базовой_валюте, "DISCOUNT" =&gt;
	* массив_параметров_первой_из_примененных_скидок_торгового_каталога,
	* "DISCOUNT_LIST" =&gt;
	* массив_скидок_действующих_на_товар_в_порядке_применения, "RESULT_PRICE"
	* =&gt; array( "BASE_PRICE" =&gt; полная (без скидок) цена товара, "DISCOUNT_PRICE" =&gt;
	* цена со скидками, "DISCOUNT" =&gt; итоговая скидка (разница между BASE_PRICE и
	* DISCOUNT_PRICE) "PERCENT" =&gt; итоговая скидка в процентах "CURRENCY" =&gt; валюта
	* результата ) )</pre> <p></p><div class="note"> <b>Примечание</b>. С версии 15.0.6 ключ
	* RESULT_PRICE заполняется данными и в том случае, когда использованы
	* обработчики события <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/events/ongetoptimalprice.php">OnGetOptimalPrice</a>. Параметры
	* заданные методами <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/setusedcurrency.php">CCatalogProduct::setUsedCurrency</a>
	* и <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/setpricevatincludemode.php">CCatalogProduct::setPriceVatIncludeMode</a>
	* при этом учитываются, параметры заданные методом <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/setusediscount.php">CCatalogProduct::setUseDiscount</a>
	* - нет. </div> <p></p><div class="note"> <b>Обратите внимание, что</b> <i>DISCOUNT_PRICE</i> это
	* минимальная цена в <b>базовой валюте</b>. Чтобы перевести эту цену в
	* валюту товара, необходимо: <pre class="syntax">array( $baseCurrency =
	* CCurrency::GetBaseCurrency(); $arPrice["DISCOUNT_PRICE"] = CCurrencyRates::ConvertCurrency($arPrice['DISCOUNT_PRICE'],
	* $baseCurrency, $arPrice["PRICE"]["CURRENCY"]); )</pre> </div> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, $USER-&gt;GetUserGroupArray(), $renewal);
	* if (!$arPrice || count($arPrice) &lt;= 0)
	* {
	*     if ($nearestQuantity = CCatalogProduct::GetNearestQuantityPrice($productID, $quantity, $USER-&gt;GetUserGroupArray()))
	*     {
	*         $quantity = $nearestQuantity;
	*         $arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, $USER-&gt;GetUserGroupArray(), $renewal);
	*     }
	* }
	* echo "&lt;pre&gt;";
	* print_r($arPrice);
	* echo "&lt;/pre&gt;";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getoptimalprice.7c16046d.php
	* @author Bitrix
	*/
	static public function GetOptimalPrice($intProductID, $quantity = 1, $arUserGroups = array(), $renewal = "N", $arPrices = array(), $siteID = false, $arDiscountCoupons = false)
	{
		static $eventOnGetExists = null;
		static $eventOnResultExists = null;

		global $APPLICATION;

		if ($eventOnGetExists === true || $eventOnGetExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnGetOptimalPrice', true) as $arEvent)
			{
				$eventOnGetExists = true;
				$mxResult = ExecuteModuleEventEx($arEvent, array($intProductID, $quantity, $arUserGroups, $renewal, $arPrices, $siteID, $arDiscountCoupons));
				if ($mxResult !== true)
				{
					self::updateUserHandlerOptimalPrice($mxResult);
					return $mxResult;
				}
			}
			if ($eventOnGetExists === null)
				$eventOnGetExists = false;
		}

		$intProductID = (int)$intProductID;
		if ($intProductID <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("BT_MOD_CATALOG_PROD_ERR_PRODUCT_ID_ABSENT"), "NO_PRODUCT_ID");
			return false;
		}

		$quantity = (float)$quantity;
		if ($quantity <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("BT_MOD_CATALOG_PROD_ERR_QUANTITY_ABSENT"), "NO_QUANTITY");
			return false;
		}

		if (!is_array($arUserGroups) && (int)$arUserGroups.'|' == (string)$arUserGroups.'|')
			$arUserGroups = array((int)$arUserGroups);

		if (!is_array($arUserGroups))
			$arUserGroups = array();

		if (!in_array(2, $arUserGroups))
			$arUserGroups[] = 2;

		$renewal = ($renewal == 'Y' ? 'Y' : 'N');

		if ($siteID === false)
			$siteID = SITE_ID;

		$resultCurrency = CCurrency::GetBaseCurrency();
		if (empty($resultCurrency))
		{
			$APPLICATION->ThrowException(Loc::getMessage("BT_MOD_CATALOG_PROD_ERR_NO_BASE_CURRENCY"), "NO_BASE_CURRENCY");
			return false;
		}
		if (self::$usedCurrency !== null)
			$resultCurrency = self::$usedCurrency;

		$intIBlockID = (int)CIBlockElement::GetIBlockByID($intProductID);
		if ($intIBlockID <= 0)
		{
			$APPLICATION->ThrowException(
				Loc::getMessage(
					'BT_MOD_CATALOG_PROD_ERR_ELEMENT_ID_NOT_FOUND',
					array('#ID#' => $intProductID)
				),
				'NO_ELEMENT'
			);
			return false;
		}

		if (!isset($arPrices) || !is_array($arPrices))
			$arPrices = array();

		if (empty($arPrices))
		{
			$arPrices = array();
			$dbPriceList = CPrice::GetListEx(
				array(),
				array(
						"PRODUCT_ID" => $intProductID,
						"GROUP_GROUP_ID" => $arUserGroups,
						"GROUP_BUY" => "Y",
						"+<=QUANTITY_FROM" => $quantity,
						"+>=QUANTITY_TO" => $quantity
					),
				false,
				false,
				array("ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY")
			);
			while ($arPriceList = $dbPriceList->Fetch())
			{
				$arPriceList['ELEMENT_IBLOCK_ID'] = $intIBlockID;
				$arPrices[] = $arPriceList;
			}
			unset($arPriceList, $dbPriceList);
		}
		else
		{
			foreach ($arPrices as &$arOnePrice)
				$arOnePrice['ELEMENT_IBLOCK_ID'] = $intIBlockID;
			unset($arOnePrice);
		}

		if (empty($arPrices))
			return false;

		$rsVAT = CCatalogProduct::GetVATInfo($intProductID);
		if ($arVAT = $rsVAT->Fetch())
			$arVAT['RATE'] = (float)$arVAT['RATE'] * 0.01;
		else
			$arVAT = array('RATE' => 0.0, 'VAT_INCLUDED' => 'N');
		unset($rsVAT);

		if (self::getUseDiscount())
		{
			if ($arDiscountCoupons === false)
				$arDiscountCoupons = CCatalogDiscountCoupon::GetCoupons();
		}

//		$boolDiscountVat = ('N' != COption::GetOptionString('catalog', 'discount_vat', 'Y'));
		$boolDiscountVat = true;

		$minPrice = false;
		$basePrice = false;
		$arMinPrice = array();
		$arMinDiscounts = array();

		foreach ($arPrices as &$arPriceList)
		{
			$arPriceList['VAT_RATE'] = $arVAT['RATE'];
			$arPriceList['VAT_INCLUDED'] = $arVAT['VAT_INCLUDED'];

			$dblCurrentPrice = $arPriceList['PRICE'];
			if ($boolDiscountVat)
			{
				if ('N' == $arPriceList['VAT_INCLUDED'])
					$dblCurrentPrice *= (1 + $arPriceList['VAT_RATE']);
			}
			else
			{
				if ('Y' == $arPriceList['VAT_INCLUDED'])
					$dblCurrentPrice /= (1 + $arPriceList['VAT_RATE']);
			}

			if ($arPriceList['CURRENCY'] != $resultCurrency)
				$dblCurrentPrice = CCurrencyRates::ConvertCurrency($arPriceList['PRICE'], $arPriceList['CURRENCY'], $resultCurrency);
			$dblCurrentPrice = roundEx($dblCurrentPrice, CATALOG_VALUE_PRECISION);

			$arDiscounts = array();
			if (self::getUseDiscount())
				$arDiscounts = CCatalogDiscount::GetDiscount($intProductID, $intIBlockID, $arPriceList["CATALOG_GROUP_ID"], $arUserGroups, $renewal, $siteID, $arDiscountCoupons);

			$result = CCatalogDiscount::applyDiscountList($dblCurrentPrice, $resultCurrency, $arDiscounts);
			if ($result === false)
				return false;

			if ($minPrice === false || $minPrice > $result['PRICE'])
			{
				$basePrice = $dblCurrentPrice;
				$minPrice = $result['PRICE'];
				$arMinPrice = $arPriceList;
				$arMinDiscounts = $result['DISCOUNT_LIST'];
			}
		}
		unset($arPriceList);

		if ($boolDiscountVat)
		{
			if (!self::$optimalPriceWithVat)
			{
				$minPrice /= (1 + $arMinPrice['VAT_RATE']);
				$basePrice /= (1 + $arMinPrice['VAT_RATE']);

				$minPrice = roundEx($minPrice, CATALOG_VALUE_PRECISION);
				$basePrice = roundEx($basePrice, CATALOG_VALUE_PRECISION);
			}
		}
		else
		{
			if (self::$optimalPriceWithVat)
			{
				$minPrice *= (1 + $arMinPrice['VAT_RATE']);
				$basePrice *= (1 + $arMinPrice['VAT_RATE']);

				$minPrice = roundEx($minPrice, CATALOG_VALUE_PRECISION);
				$basePrice = roundEx($basePrice, CATALOG_VALUE_PRECISION);
			}
		}

		$arResult = array(
			'PRICE' => $arMinPrice,
			'RESULT_PRICE' => array(
				'BASE_PRICE' => $basePrice,
				'DISCOUNT_PRICE' => $minPrice,
				'DISCOUNT' => $basePrice - $minPrice,
				'PERCENT' => ($basePrice > 0 ? (100*($basePrice - $minPrice))/$basePrice : 0),
				'CURRENCY' => $resultCurrency,
				'VAT_RATE' => $arMinPrice['VAT_RATE'],
				'VAT_INCLUDED' => (self::$optimalPriceWithVat ? 'Y' : 'N')
			),
			'DISCOUNT_PRICE' => $minPrice,
			'DISCOUNT' => array(),
			'DISCOUNT_LIST' => array()
		);
		if (!empty($arMinDiscounts))
		{
			reset($arMinDiscounts);
			$arResult['DISCOUNT'] = current($arMinDiscounts);
			$arResult['DISCOUNT_LIST'] = $arMinDiscounts;
		}

		if ($eventOnResultExists === true || $eventOnResultExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnGetOptimalPriceResult', true) as $arEvent)
			{
				$eventOnResultExists = true;
				if (ExecuteModuleEventEx($arEvent, array(&$arResult)) === false)
					return false;
			}
			if ($eventOnResultExists === null)
				$eventOnResultExists = false;
		}

		return $arResult;
	}

	/**
	 * @param float $price
	 * @param string $currency
	 * @param array $arDiscounts
	 * @return bool|float
	 */
	
	/**
	* <p>Метод применяет к цене <i>price</i> в валюте <i>currency</i> цепочку скидок из массива <i>arDiscounts</i>. Метод динамичный.</p> <p></p> <div class="note"> <b>Примечание:</b> до версии модуля <b>12.0</b> метод применял к цене <i>price</i> в валюте <i>currency</i> наибольшую скидку из массива <i>arDiscounts</i>.</div>
	*
	*
	* @param double $price  Цена.</b
	*
	* @param string $currency  Валюта цены.
	*
	* @param array $arDiscounts  массив ассоциативных массивов скидок. Имеет вид: <pre class="syntax">array(
	* array( "VALUE_TYPE" =&gt; "тип скидки (P - в процентах, F - фиксированная сумма)",
	* "VALUE" =&gt; "величина скидки", "CURRENCY" =&gt; "валюта", "MAX_DISCOUNT" =&gt;
	* "максимальный размер скидки" ), array( "VALUE_TYPE" =&gt; "тип скидки (P - в
	* процентах, F - фиксированная сумма)", "VALUE" =&gt; "величина скидки",
	* "CURRENCY" =&gt; "валюта", "MAX_DISCOUNT" =&gt; "максимальный размер скидки" ), * * * )
	* </pre>
	*
	* @return array <p>Метод возвращает цену, получившуюся после применения цепочки
	* скидок.</p> <p></p><div class="note"> <b>Примечание:</b> до версии модуля <b>12.0.0</b>
	* метод возвращал наименьшую цену, которую можно было получить с
	* помощью наибольшей скидки.</div> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Для товара с кодом $ID выведем различные цены (по типу и количеству), по
	* // которым данный товар может быть куплен текущим пользователем
	* 
	* $dbPrice = CPrice::GetList(
	*         array("QUANTITY_FROM" =&gt; "ASC", "QUANTITY_TO" =&gt; "ASC", "SORT" =&gt; "ASC"),
	*         array("PRODUCT_ID" =&gt; $ID),
	*         false,
	*         false,
	*         array("ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY", "QUANTITY_FROM", "QUANTITY_TO")
	*     );
	* while ($arPrice = $dbPrice-&gt;Fetch())
	* {
	*     $arDiscounts = CCatalogDiscount::GetDiscountByPrice(
	*             $arPrice["ID"],
	*             $USER-&gt;GetUserGroupArray(),
	*             "N",
	*             SITE_ID
	*         );
	*     $discountPrice = CCatalogProduct::CountPriceWithDiscount(
	*             $arPrice["PRICE"],
	*             $arPrice["CURRENCY"],
	*             $arDiscounts
	*         );
	*     $arPrice["DISCOUNT_PRICE"] = $discountPrice;
	* 
	*     echo "&lt;pre&gt;";
	*     print_r($arPrice);
	*     echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__countpricewithdiscount.9c16046d.php
	* @author Bitrix
	*/
	static public function CountPriceWithDiscount($price, $currency, $arDiscounts)
	{
		static $eventOnGetExists = null;
		static $eventOnResultExists = null;

		if ($eventOnGetExists === true || $eventOnGetExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnCountPriceWithDiscount', true) as $arEvent)
			{
				$eventOnGetExists = true;
				$mxResult = ExecuteModuleEventEx($arEvent, array($price, $currency, $arDiscounts));
				if ($mxResult !== true)
					return $mxResult;
			}
			if ($eventOnGetExists === null)
				$eventOnGetExists = false;
		}

		$currency = CCurrency::checkCurrencyID($currency);
		if ($currency === false)
			return false;

		$price = (float)$price;
		if ($price <= 0)
			return $price;

		if (empty($arDiscounts) || !is_array($arDiscounts))
			return $price;

		$result = CCatalogDiscount::applyDiscountList($price, $currency, $arDiscounts);
		if ($result === false)
			return false;

		$currentMinPrice = $result['PRICE'];

		if ($eventOnResultExists === true || $eventOnResultExists === null)
		{
			foreach (GetModuleEvents('catalog', 'OnCountPriceWithDiscountResult', true) as $arEvent)
			{
				$eventOnResultExists = true;
				if (ExecuteModuleEventEx($arEvent, array(&$currentMinPrice)) === false)
					return false;
			}
			if ($eventOnResultExists === null)
				$eventOnResultExists = false;
		}

		return $currentMinPrice;
	}

	static public function GetProductSections($ID)
	{
		/** @global CStackCacheManager $stackCacheManager */
		global $stackCacheManager;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		$cacheTime = CATALOG_CACHE_DEFAULT_TIME;
		if (defined('CATALOG_CACHE_TIME'))
			$cacheTime = intval(CATALOG_CACHE_TIME);

		$arProductSections = array();

		$dbElementSections = CIBlockElement::GetElementGroups($ID, false, array('ID', 'ADDITIONAL_PROPERTY_ID'));
		while ($arElementSections = $dbElementSections->Fetch())
		{
			if ((int)$arElementSections['ADDITIONAL_PROPERTY_ID'] > 0)
				continue;
			$arSectionsTmp = array();

			$strCacheKey = "p".$arElementSections["ID"];

			$stackCacheManager->SetLength("catalog_group_parents", 50);
			$stackCacheManager->SetTTL("catalog_group_parents", $cacheTime);
			if ($stackCacheManager->Exist("catalog_group_parents", $strCacheKey))
			{
				$arSectionsTmp = $stackCacheManager->Get("catalog_group_parents", $strCacheKey);
			}
			else
			{
				$dbSection = CIBlockSection::GetList(
					array(),
					array('ID' => $arElementSections["ID"]),
					false,
					array(
						'ID',
						'IBLOCK_ID',
						'LEFT_MARGIN',
						'RIGHT_MARGIN',
					)
				);
				if ($arSection = $dbSection->Fetch())
				{
					$dbSectionTree = CIBlockSection::GetList(
						array("LEFT_MARGIN" => "DESC"),
						array(
							"IBLOCK_ID" => $arSection["IBLOCK_ID"],
							"ACTIVE" => "Y",
							"GLOBAL_ACTIVE" => "Y",
							"IBLOCK_ACTIVE" => "Y",
							"<=LEFT_BORDER" => $arSection["LEFT_MARGIN"],
							">=RIGHT_BORDER" => $arSection["RIGHT_MARGIN"]
						),
						false,
						array('ID')
					);
					while ($arSectionTree = $dbSectionTree->Fetch())
					{
						$arSectionTree["ID"] = intval($arSectionTree["ID"]);
						$arSectionsTmp[] = $arSectionTree["ID"];
					}
				}

				$stackCacheManager->Set("catalog_group_parents", $strCacheKey, $arSectionsTmp);
			}

			$arProductSections = array_merge($arProductSections, $arSectionsTmp);
		}

		$arProductSections = array_unique($arProductSections);

		return $arProductSections;
	}

	static public function OnIBlockElementDelete($ProductID)
	{
		return CCatalogProduct::Delete($ProductID);
	}

	static public function OnAfterIBlockElementUpdate($arFields)
	{
		if (isset($arFields["IBLOCK_SECTION"]))
		{
			/** @global CStackCacheManager $stackCacheManager */
			global $stackCacheManager;
			$stackCacheManager->Clear("catalog_element_groups");
		}
	}

	public static function CheckProducts($arItemIDs)
	{
		if (!is_array($arItemIDs))
			$arItemIDs = array($arItemIDs);
		CatalogClearArray($arItemIDs);
		if (empty($arItemIDs))
			return false;
		$arProductList = array();
		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('@ID' => $arItemIDs),
			false,
			false,
			array('ID')
		);
		while ($arProduct = $rsProducts->Fetch())
		{
			$arProduct['ID'] = (int)$arProduct['ID'];
			$arProductList[$arProduct['ID']] = true;
		}
		if (empty($arProductList))
			return false;
		$boolFlag = true;
		foreach ($arItemIDs as &$intItemID)
		{
			if (!isset($arProductList[$intItemID]))
			{
				$boolFlag = false;
				break;
			}
		}
		unset($intItemID);
		return $boolFlag;
	}

	public static function GetTimePeriodTypes($boolFull = false)
	{
		$boolFull = ($boolFull === true);
		if ($boolFull)
		{
			return array(
				self::TIME_PERIOD_HOUR => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_HOUR'),
				self::TIME_PERIOD_DAY => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_DAY'),
				self::TIME_PERIOD_WEEK => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_WEEK'),
				self::TIME_PERIOD_MONTH => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_MONTH'),
				self::TIME_PERIOD_QUART => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_QUART'),
				self::TIME_PERIOD_SEMIYEAR => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_SEMIYEAR'),
				self::TIME_PERIOD_YEAR => Loc::getMessage('BT_MOD_CATALOG_PROD_PERIOD_YEAR')
			);
		}
		return array(
			self::TIME_PERIOD_HOUR,
			self::TIME_PERIOD_DAY,
			self::TIME_PERIOD_WEEK,
			self::TIME_PERIOD_MONTH,
			self::TIME_PERIOD_QUART,
			self::TIME_PERIOD_SEMIYEAR,
			self::TIME_PERIOD_YEAR
		);
	}

	/**
	 * Update result user handlers for event OnGetOptimalPrice.
	 *
	 * @param array &$userResult		Optimal price array.
	 * @return void
	 */
	public static function updateUserHandlerOptimalPrice(&$userResult)
	{
		global $APPLICATION;
		if (empty($userResult) || !is_array($userResult))
		{
			$userResult = false;
			return;
		}
		if (empty($userResult['PRICE']) || !is_array($userResult['PRICE']))
		{
			$userResult = false;
			return;
		}
		if (empty($userResult['RESULT_PRICE']) || !is_array($userResult['RESULT_PRICE']))
		{
			$resultCurrency = CCurrency::GetBaseCurrency();
			if (empty($resultCurrency))
			{
				$APPLICATION->ThrowException(Loc::getMessage('BT_MOD_CATALOG_PROD_ERR_NO_BASE_CURRENCY'), 'NO_BASE_CURRENCY');
				$userResult = false;

				return;
			}
			if (self::$usedCurrency !== null)
				$resultCurrency = self::$usedCurrency;

			$oldDiscountExist = !empty($userResult['DISCOUNT']) && is_array($userResult['DISCOUNT']);
			if ($oldDiscountExist)
			{
				if (empty($userResult['DISCOUNT']['MODULE_ID']))
					$userResult['DISCOUNT']['MODULE_ID'] = 'catalog';
				if ($userResult['DISCOUNT']['CURRENCY'] != $resultCurrency)
					Catalog\DiscountTable::convertCurrency($userResult['DISCOUNT'], $resultCurrency);
			}
			if (!isset($userResult['DISCOUNT_LIST']) || !is_array($userResult['DISCOUNT_LIST']))
			{
				$userResult['DISCOUNT_LIST'] = array();
				if ($oldDiscountExist)
					$userResult['DISCOUNT_LIST'][] = $userResult['DISCOUNT'];
			}
			if (isset($userResult['DISCOUNT_LIST']))
			{
				foreach ($userResult['DISCOUNT_LIST'] as &$discount)
				{
					if (empty($discount['MODULE_ID']))
						$discount['MODULE_ID'] = 'catalog';
					if ($discount['CURRENCY'] != $resultCurrency)
						Catalog\DiscountTable::convertCurrency($discount, $resultCurrency);
				}
				unset($discount);
			}
			$userResult['RESULT_PRICE'] = CCatalogDiscount::calculateDiscountList($userResult['PRICE'], $resultCurrency, $userResult['DISCOUNT_LIST'], self::$optimalPriceWithVat);
		}
		else
		{
			$userResult['RESULT_PRICE']['BASE_PRICE'] = roundEx($userResult['RESULT_PRICE']['BASE_PRICE'], CATALOG_VALUE_PRECISION);
			$userResult['RESULT_PRICE']['DISCOUNT'] = roundEx($userResult['RESULT_PRICE']['DISCOUNT'], CATALOG_VALUE_PRECISION);
			$userResult['RESULT_PRICE']['DISCOUNT_PRICE'] = $userResult['RESULT_PRICE']['BASE_PRICE'] - $userResult['RESULT_PRICE']['DISCOUNT'];
			$userResult['RESULT_PRICE']['VAT_RATE'] = $userResult['PRICE']['VAT_RATE'];
		}
	}

	/**
	* @deprecated deprecated since catalog 15.0.0
	* @see CCatalogDiscount::applyDiscountList()
	* @see CCatalogDiscount::primaryDiscountFilter()
	*/
	protected static function __PrimaryDiscountFilter(&$arDiscount, &$arPriceDiscount, &$arDiscSave, &$arParams)
	{
		if (isset($arParams['PRICE']) && isset($arParams['CURRENCY']))
		{
			$arParams['PRICE'] = (float)$arParams['PRICE'];
			$arParams['BASE_PRICE'] = $arParams['PRICE'];
			if ($arParams['PRICE'] > 0)
			{
				$arPriceDiscount = array();
				$arDiscSave = array();

				foreach ($arDiscount as $arOneDiscount)
				{
					$changeData = ($arParams['CURRENCY'] != $arOneDiscount['CURRENCY']);
					$dblDiscountValue = 0.0;
					$arOneDiscount['PRIORITY'] = (int)$arOneDiscount['PRIORITY'];
					if (CCatalogDiscount::TYPE_FIX == $arOneDiscount['VALUE_TYPE'])
					{
						$dblDiscountValue = (
							!$changeData
							? $arOneDiscount['VALUE']
							: roundEx(
								CCurrencyRates::ConvertCurrency($arOneDiscount['VALUE'], $arOneDiscount['CURRENCY'], $arParams['CURRENCY']),
								CATALOG_VALUE_PRECISION
							)
						);
						if ($arParams['PRICE'] < $dblDiscountValue)
							continue;
						$arOneDiscount['DISCOUNT_CONVERT'] = $dblDiscountValue;
						if ($changeData)
							$arOneDiscount['VALUE'] = $arOneDiscount['DISCOUNT_CONVERT'];
					}
					elseif (CCatalogDiscount::TYPE_SALE == $arOneDiscount['VALUE_TYPE'])
					{
						$dblDiscountValue = (
							!$changeData
							? $arOneDiscount['VALUE']
							: roundEx(
								CCurrencyRates::ConvertCurrency($arOneDiscount['VALUE'], $arOneDiscount['CURRENCY'], $arParams['CURRENCY']),
								CATALOG_VALUE_PRECISION
							)
						);
						if ($arParams['PRICE'] <= $dblDiscountValue)
							continue;
						$arOneDiscount['DISCOUNT_CONVERT'] = $dblDiscountValue;
						if ($changeData)
							$arOneDiscount['VALUE'] = $arOneDiscount['DISCOUNT_CONVERT'];
					}
					elseif (CCatalogDiscount::TYPE_PERCENT == $arOneDiscount['VALUE_TYPE'])
					{
						if (100 < $arOneDiscount["VALUE"])
							continue;
						if ($arOneDiscount['TYPE'] == CCatalogDiscount::ENTITY_ID && $arOneDiscount["MAX_DISCOUNT"] > 0)
						{
							$dblDiscountValue = (
								!$changeData
								? $arOneDiscount['MAX_DISCOUNT']
								: roundEx(
									CCurrencyRates::ConvertCurrency($arOneDiscount['MAX_DISCOUNT'], $arOneDiscount['CURRENCY'], $arParams['CURRENCY']),
									CATALOG_VALUE_PRECISION
								)
							);
							$arOneDiscount['DISCOUNT_CONVERT'] = $dblDiscountValue;
							if ($changeData)
								$arOneDiscount['MAX_DISCOUNT'] = $arOneDiscount['DISCOUNT_CONVERT'];
						}
					}
					if ($changeData)
						$arOneDiscount['CURRENCY'] = $arParams['CURRENCY'];
					if ($arOneDiscount['TYPE'] == CCatalogDiscountSave::ENTITY_ID)
					{
						$arDiscSave[] = $arOneDiscount;
					}
					else
					{
						$arPriceDiscount[$arOneDiscount['PRIORITY']][] = $arOneDiscount;
					}
				}

				if (!empty($arPriceDiscount))
					krsort($arPriceDiscount);
			}
		}
	}

	/**
	* @deprecated deprecated since catalog 15.0.0
	* @see CCatalogDiscount::applyDiscountList()
	* @see CCatalogDiscount::calculatePriorityLevel()
	*/
	protected static function __CalcOnePriority(&$arDiscounts, &$arResultDiscount, &$arParams)
	{
		$boolResult = false;
		if (isset($arParams['PRICE']) && isset($arParams['CURRENCY']))
		{
			$arParams['PRICE'] = (float)$arParams['PRICE'];
			$arParams['BASE_PRICE'] = (float)$arParams['BASE_PRICE'];
			if ($arParams['PRICE'] > 0)
			{
				$dblCurrentPrice = $arParams['PRICE'];
				do
				{
					$dblMinPrice = -1;
					$strMinKey = -1;
					$boolLast = false;
					$boolApply = false;
					foreach ($arDiscounts as $strDiscountKey => $arOneDiscount)
					{
						$boolDelete = false;
						$dblPriceTmp = -1;
						switch($arOneDiscount['VALUE_TYPE'])
						{
						case CCatalogDiscount::TYPE_PERCENT:
							$dblTempo = roundEx((
								CCatalogDiscount::getUseBasePrice()
								? $arParams['BASE_PRICE']
								: $dblCurrentPrice
								)*$arOneDiscount['VALUE']/100,
								CATALOG_VALUE_PRECISION
							);
							if (isset($arOneDiscount['DISCOUNT_CONVERT']))
							{
								if ($dblTempo > $arOneDiscount['DISCOUNT_CONVERT'])
									$dblTempo = $arOneDiscount['DISCOUNT_CONVERT'];
							}
							$dblPriceTmp = $dblCurrentPrice - $dblTempo;
							break;
						case CCatalogDiscount::TYPE_FIX:
							if ($arOneDiscount['DISCOUNT_CONVERT'] > $dblCurrentPrice)
							{
								$boolDelete = true;
							}
							else
							{
								$dblPriceTmp = $dblCurrentPrice - $arOneDiscount['DISCOUNT_CONVERT'];
							}
							break;
						case CCatalogDiscount::TYPE_SALE:
							if (!($arOneDiscount['DISCOUNT_CONVERT'] < $dblCurrentPrice))
							{
								$boolDelete = true;
							}
							else
							{
								$dblPriceTmp = $arOneDiscount['DISCOUNT_CONVERT'];
							}
							break;
						}
						if ($boolDelete)
						{
							unset($arDiscounts[$strDiscountKey]);
						}
						else
						{
							if (-1 == $dblMinPrice || $dblMinPrice > $dblPriceTmp)
							{
								$dblMinPrice = $dblPriceTmp;
								$strMinKey = $strDiscountKey;
								$boolApply = true;
							}
						}
					}
					if ($boolApply)
					{
						$dblCurrentPrice = $dblMinPrice;
						$arResultDiscount[] = $arDiscounts[$strMinKey];
						if ('Y' == $arDiscounts[$strMinKey]['LAST_DISCOUNT'])
						{
							$arDiscounts = array();
							$arParams['LAST_DISCOUNT'] = 'Y';
						}
						unset($arDiscounts[$strMinKey]);
					}
				} while (!empty($arDiscounts));
				if ($boolApply)
				{
					$arParams['PRICE'] = $dblCurrentPrice;
				}
				$boolResult = true;
			}
		}
		return $boolResult;
	}

	/**
	* @deprecated deprecated since catalog 15.0.0
	* @see CCatalogDiscount::applyDiscountList()
	* @see CCatalogDiscount::calculateDiscSave()
	*/
	protected static function __CalcDiscSave(&$arDiscSave, &$arResultDiscount, &$arParams)
	{
		$boolResult = false;
		if (isset($arParams['PRICE']) && isset($arParams['CURRENCY']))
		{
			$arParams['PRICE'] = (float)$arParams['PRICE'];
			if (0 < $arParams['PRICE'])
			{
				$dblCurrentPrice = $arParams['PRICE'];
				$dblMinPrice = -1;
				$strMinKey = -1;
				$boolApply = false;
				foreach ($arDiscSave as $strDiscountKey => $arOneDiscount)
				{
					$dblPriceTmp = -1;
					$boolDelete = false;
					switch($arOneDiscount['VALUE_TYPE'])
					{
					case CCatalogDiscountSave::TYPE_PERCENT:
						$dblPriceTmp = roundEx($dblCurrentPrice*(1 - $arOneDiscount['VALUE']/100.0), CATALOG_VALUE_PRECISION);
						break;
					case CCatalogDiscountSave::TYPE_FIX:
						if ($arOneDiscount['DISCOUNT_CONVERT'] > $dblCurrentPrice)
						{
							$boolDelete = true;
						}
						else
						{
							$dblPriceTmp = $dblCurrentPrice - $arOneDiscount['DISCOUNT_CONVERT'];
						}
						break;
					}
					if (!$boolDelete)
					{
						if (-1 == $dblMinPrice || $dblMinPrice > $dblPriceTmp)
						{
							$dblMinPrice = $dblPriceTmp;
							$strMinKey = $strDiscountKey;
							$boolApply = true;
						}
					}
				}
				if ($boolApply)
				{
					$arParams['PRICE'] = $dblMinPrice;
					$arResultDiscount[] = $arDiscSave[$strMinKey];
				}
				$boolResult = true;
			}
		}
		return $boolResult;
	}
}