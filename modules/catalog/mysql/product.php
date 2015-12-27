<?
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Catalog;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/product.php");


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
class CCatalogProduct extends CAllCatalogProduct
{
	
	/**
	* <p>Метод добавляет (или обновляет) параметры товара к элементу каталога. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив, ключами которого являются названия
	* параметров товара, а значениями - новые значения параметров.
	* Допустимые ключи: <br><br> ключи, независящие от вида товаров: <ul> <li>
	* <b>ID</b> - код товара (элемента каталога - обязательный);</li> <li> <b>VAT_ID</b>
	* - код НДС;</li> <li> <b>VAT_INCLUDED</b> - флаг (Y/N) включен ли НДС в цену;</li> <li>
	* <b>QUANTITY</b> - количество товара на складе;</li> <li> <b>QUANTITY_RESERVED</b> -
	* зарезервированное количество;</li> <li> <b>QUANTITY_TRACE</b> - флаг (Y/N/D)<b>*</b>
	* "включить количественный учет" (до версии 12.5.0 параметр назывался
	* "уменьшать ли количество при заказе");</li> </ul> <br> ключи для обычных
	* товаров: <ul> <li> <b>CAN_BUY_ZERO</b> - флаг (Y/N/D)<b>*</b> "разрешить покупку при
	* отсутствии товара";</li> <li> <b>NEGATIVE_AMOUNT_TRACE</b> - флаг (Y/N/D)<b>*</b>
	* "разрешить отрицательное количество товара";</li> <li> <b>SUBSCRIBE</b> - флаг
	* (Y/N/D)<b>*</b> "разрешить подписку при отсутствии товара"; <br><br> </li> <li>
	* <b>PURCHASING_PRICE</b> - закупочная цена;</li> <li> <b>PURCHASING_CURRENCY</b> - валюта
	* закупочной цены;<br><br> </li> <li> <b>WEIGHT</b> - вес единицы товара;<br><br> </li> <li>
	* <b>WIDTH</b> - ширина товара (в мм);</li> <li> <b>LENGTH</b> - длина товара (в мм);</li>
	* <li> <b>HEIGHT</b> - высота товара (в мм);</li> <li> <b>MEASURE</b> - ID единицы
	* измерения;<br><br> </li> <li> <b>BARCODE_MULTI</b> - (Y/N) определяет каждый ли
	* экземпляр товара имеет собственный штрихкод;</li> </ul> <br> ключи для
	* продажи контента: <ul> <li> <b>PRICE_TYPE</b> - тип цены (S - одноразовый платеж,
	* R - регулярные платежи, T - пробная подписка);</li> <li> <b>RECUR_SCHEME_TYPE</b> -
	* тип периода подписки ("H" - час, "D" - сутки, "W" - неделя, "M" - месяц, "Q" -
	* квартал, "S" - полугодие, "Y" - год);</li> <li> <b>RECUR_SCHEME_LENGTH</b> - длина
	* периода подписки;</li> <li> <b>TRIAL_PRICE_ID</b> - код товара, для которого
	* данный товар является пробным;</li> <li> <b>WITHOUT_ORDER</b> - флаг "Продление
	* подписки без оформления заказа".</li> </ul>
	*
	* @param boolean $boolCheck = true Параметр, указывающий, проверять ли наличие в базе информации о
	* товаре или нет, перед добавлением.<br>По умолчанию - проверять.
	*
	* @return bool <p>Возвращает <i>true</i> в случае успешного обновления параметров и
	* <i>false</i> в противном случае. </p>
	*
	* <h4>Example</h4> 
	* <pre>
	* $arFields = array(
	*                   "ID" =&gt; $PRODUCT_ID, 
	*                   "VAT_ID" =&gt; 1, //выставляем тип ндс (задается в админке)  
	*                   "VAT_INCLUDED" =&gt; "Y" //НДС входит в стоимость
	*                   );
	* if(CCatalogProduct::Add($arFields))
	*     echo "Добавили параметры товара к элементу каталога ".$PRODUCT_ID.'&lt;br&gt;';
	* else
	*     echo 'Ошибка добавления параметров&lt;br&gt;';
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__add.933e0eb4.php
	* @author Bitrix
	*/
	static public function Add($arFields, $boolCheck = true)
	{
		global $DB;

		$existProduct = false;
		$boolCheck = ($boolCheck !== false);

		if (empty($arFields['ID']))
			return false;
		$arFields['ID'] = (int)$arFields['ID'];
		if ($arFields['ID'] <= 0)
			return false;

		if ($boolCheck)
			$existProduct = Catalog\ProductTable::isExistProduct($arFields['ID']);

		if ($existProduct)
		{
			return CCatalogProduct::Update($arFields['ID'], $arFields);
		}
		else
		{
			foreach (GetModuleEvents("catalog", "OnBeforeProductAdd", true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, array(&$arFields))===false)
					return false;
			}

			if (!CCatalogProduct::CheckFields("ADD", $arFields, 0))
				return false;

			$arInsert = $DB->PrepareInsert("b_catalog_product", $arFields);

			$strSql = "INSERT INTO b_catalog_product(".$arInsert[0].") VALUES(".$arInsert[1].")";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			Catalog\ProductTable::clearProductCache($arFields['ID']);

			foreach (GetModuleEvents("catalog", "OnProductAdd", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($arFields["ID"], $arFields));
			// strange copy-paste bug
			foreach (GetModuleEvents("sale", "OnProductAdd", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($arFields["ID"], $arFields));
		}

		return true;
	}

	
	/**
	* <p>Метод обновляет параметры товара, относящиеся к товару как к таковому. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код товара.
	*
	* @param array $arFields  Ассоциативный массив, ключами которого являются названия
	* параметров товара, а значениями - новые значения параметров.
	* Допустимые ключи: <br><br> ключи, независящие от типа товаров: <ul> <li>
	* <b>QUANTITY</b> - количество товара на складе;</li> <li> <b>QUANTITY_RESERVED</b> -
	* зарезервированное количество;</li> <li> <b>QUANTITY_TRACE</b> - флаг (Y/N/D)<b>*</b>
	* "включить количественный учет" (до версии 12.5.0 параметр назывался
	* "уменьшать ли количество при заказе");</li> </ul> <br> ключи для обычных
	* товаров: <ul> <li> <b>CAN_BUY_ZERO</b> - флаг (Y/N/D)<b>*</b> "разрешить покупку при
	* отсутствии товара";</li> <li> <b>NEGATIVE_AMOUNT_TRACE</b> - флаг (Y/N/D)<b>*</b>
	* "разрешить отрицательное количество товара";</li> <li> <b>SUBSCRIBE</b> - флаг
	* (Y/N/D)<b>*</b> "разрешить подписку при отсутствии товара"; <br><br> </li> <li>
	* <b>PURCHASING_PRICE</b> - закупочная цена;</li> <li> <b>PURCHASING_CURRENCY</b> - валюта
	* закупочной цены;<br><br> </li> <li> <b>WEIGHT</b> - вес единицы товара;<br><br> </li> <li>
	* <b>WIDTH</b> - ширина товара (в мм);</li> <li> <b>LENGTH</b> - длина товара (в мм);</li>
	* <li> <b>HEIGHT</b> - высота товара (в мм);</li> <li> <b>MEASURE</b> - ID единицы
	* измерения;<br><br> </li> <li> <b>BARCODE_MULTI</b> - (Y/N) определяет каждый ли
	* экземпляр товара имеет собственный штрихкод;</li> </ul> <br> ключи для
	* продажи контента: <ul> <li> <b>PRICE_TYPE</b> - тип цены (S - одноразовый платеж,
	* R - регулярные платежи, T - пробная подписка);</li> <li> <b>RECUR_SCHEME_TYPE</b> -
	* тип периода подписки ("H" - час, "D" - сутки, "W" - неделя, "M" - месяц, "Q" -
	* квартал, "S" - полугодие, "Y" - год);</li> <li> <b>RECUR_SCHEME_LENGTH</b> - длина
	* периода подписки;</li> <li> <b>TRIAL_PRICE_ID</b> - код товара, для которого
	* данный товар является пробным;</li> <li> <b>WITHOUT_ORDER</b> - флаг "Продление
	* подписки без оформления заказа".</li> </ul>
	*
	* @return bool <p>Возвращает <i>true</i> в случае успешного обновления параметров и
	* <i>false</i> в противном случае.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* Обновление зарезервированного количества товара
	* 
	* 
	* Cmodule::IncludeModule('catalog');
	* $PRODUCT_ID = 51; // id товара
	* $arFields = array('QUANTITY_RESERVED' =&gt; 11);// зарезервированное количество
	* CCatalogProduct::Update($PRODUCT_ID, $arFields);
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__update.bc9a623b.php
	* @author Bitrix
	*/
	static public function Update($ID, $arFields)
	{
		global $DB;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		foreach (GetModuleEvents("catalog", "OnBeforeProductUpdate", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields))===false)
				return false;
		}

		if (array_key_exists('ID', $arFields))
			unset($arFields['ID']);

		if (!CCatalogProduct::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_catalog_product", $arFields);

		$boolSubscribe = false;
		if (!empty($strUpdate))
		{
			if (isset($arFields["QUANTITY"]) && $arFields["QUANTITY"] > 0)
			{
				if (!isset($arFields["OLD_QUANTITY"]))
				{
					$strQuery = 'select ID, QUANTITY from b_catalog_product where ID = '.$ID;
					$rsProducts = $DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					if ($arProduct = $rsProducts->Fetch())
					{
						$arFields["OLD_QUANTITY"] = doubleval($arProduct['QUANTITY']);
					}
				}
				if (isset($arFields["OLD_QUANTITY"]))
				{
					$boolSubscribe = $arFields["OLD_QUANTITY"] <= 0;
				}
			}

			$strSql = "update b_catalog_product set ".$strUpdate." where ID = ".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if (
				CBXFeatures::IsFeatureEnabled('CatCompleteSet')
				&& (
					isset($arFields['QUANTITY']) || isset($arFields['QUANTITY_TRACE']) || isset($arFields['CAN_BUY_ZERO']) || isset($arFields['WEIGHT'])
				)
			)
			{
				CCatalogProductSet::recalculateSetsByProduct($ID);
			}

			if (isset(self::$arProductCache[$ID]))
			{
				unset(self::$arProductCache[$ID]);
				if (defined('CATALOG_GLOBAL_VARS') && 'Y' == CATALOG_GLOBAL_VARS)
				{
					/** @var array $CATALOG_PRODUCT_CACHE */
					global $CATALOG_PRODUCT_CACHE;
					$CATALOG_PRODUCT_CACHE = self::$arProductCache;
				}
			}
		}

		foreach (GetModuleEvents("catalog", "OnProductUpdate", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
		}

		//call subscribe
		if ($boolSubscribe)
		{
			if (self::$saleIncluded === null)
				self::$saleIncluded = Loader::includeModule('sale');
			if (self::$saleIncluded)
				CSaleBasket::ProductSubscribe($ID, 'catalog');
		}

		return true;
	}

	
	/**
	* <p>Метод удаляет из элемента каталога свойства, относящиеся к товару. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код товара (элемента каталога).
	*
	* @return bool <p>Возвращает <i>true</i> в случае успешного удаления и <i>false</i> в
	* противном случае. </p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__delete.ed301fc8.php
	* @author Bitrix
	*/
	static public function Delete($ID)
	{
		global $DB;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		$DB->Query('delete from b_catalog_price where PRODUCT_ID = '.$ID, true);
		$DB->Query('delete from b_catalog_product2group where PRODUCT_ID = '.$ID, true);
		$DB->Query('delete from b_catalog_product_sets where ITEM_ID = '.$ID.' or OWNER_ID = '.$ID, true);
		$DB->Query('delete from b_catalog_measure_ratio where PRODUCT_ID = '.$ID, true);

		Catalog\ProductTable::clearProductCache($ID);
		if (isset(self::$arProductCache[$ID]))
		{
			unset(self::$arProductCache[$ID]);
			if (defined('CATALOG_GLOBAL_VARS') && CATALOG_GLOBAL_VARS == 'Y')
			{
				/** @var array $CATALOG_PRODUCT_CACHE */
				global $CATALOG_PRODUCT_CACHE;
				$CATALOG_PRODUCT_CACHE = self::$arProductCache;
			}
		}
		return $DB->Query("delete from b_catalog_product where ID = ".$ID, true);
	}

	public static function GetQueryBuildArrays($arOrder, $arFilter, $arSelect)
	{
		global $DB, $USER, $stackCacheManager;

		$strDefQuantityTrace = ((string)Option::get('catalog', 'default_quantity_trace') == 'Y' ? 'Y' : 'N');
		$strDefCanBuyZero = ((string)Option::get('catalog', 'default_can_buy_zero') == 'Y' ? 'Y' : 'N');
		$strDefNegAmount = ((string)Option::get('catalog', 'allow_negative_amount') == 'Y' ? 'Y' : 'N');
		$strSubscribe = ((string)Option::get('catalog', 'default_subscribe') == 'N' ? 'N' : 'Y');

		$sResSelect = '';
		$sResFrom = '';
		$sResWhere = '';
		$arResOrder = array();
		$arJoinGroup = array();
		$arStoreWhere = array();
		$arStore = array();
		$arStoreOrder = array();

		$arSensID = array(
			'PRODUCT_ID' => true,
			'CATALOG_GROUP_ID' => true,
			'CURRENCY' => true,
			'SHOP_QUANTITY' => true,
			'PRICE' => true,
			'STORE_AMOUNT' => true
		);

		$arOrderTmp = array();
		foreach ($arOrder as $key => $val)
		{
			foreach ($val as $by => $order)
			{
				if ($arField = CCatalogProduct::ParseQueryBuildField($by))
				{
					$res = '';
					$join = true;

					$inum = (int)$arField["NUM"];
					$by = (string)$arField["FIELD"];
					if ($by == '' || ($inum <= 0 && isset($arSensID[$by])))
						continue;

					switch ($by)
					{
						case 'PRICE':
							$res = " ".CIBlock::_Order("CAT_P".$inum.".PRICE", $order, "asc")." ";
							break;
						case 'CURRENCY':
							$res = " ".CIBlock::_Order("CAT_P".$inum.".CURRENCY", $order, "asc")." ";
							break;
						case 'QUANTITY':
							$arResOrder[$key] = " ".CIBlock::_Order("CAT_PR.QUANTITY", $order, "asc", false)." ";
							$join = false;
							break;
						case 'WEIGHT':
							$arResOrder[$key] = " ".CIBlock::_Order("CAT_PR.WEIGHT", $order, "asc", false)." ";
							$join = false;
							break;
						case 'AVAILABLE':
							$arResOrder[$key] = " ".CIBlock::_Order("CATALOG_AVAILABLE", $order, "desc", false)." ";
							$join = false;
							break;
						case 'TYPE':
							$arResOrder[$key] = " ".CIBlock::_Order("CAT_PR.TYPE", $order, "asc", false)." ";
							$join = false;
							break;
						case 'PURCHASING_PRICE':
							$arResOrder[$key] = " ".CIBlock::_Order("CAT_PR.PURCHASING_PRICE", $order, "asc")." ";
							$join = false;
							break;
						case 'PURCHASING_CURRENCY':
							$arResOrder[$key] = " ".CIBlock::_Order("CAT_PR.PURCHASING_CURRENCY", $order, "asc")." ";
							$join = false;
							break;
						case 'STORE_AMOUNT':
							$arStore[$inum] = true;
							if (!isset($arStoreOrder[$inum]))
								$arStoreOrder[$inum] = array();
							$arStoreOrder[$inum][$key] = " ".CIBlock::_Order("CAT_SP".$inum.".AMOUNT", $order, "asc")." ";
							$join = false;
							break;
						default:
							$res = " ".CIBlock::_Order("CAT_P".$inum.".ID", $order, "asc", false)." ";
							break;
					}
					if ($join)
					{
						if (!isset($arOrderTmp[$inum]))
							$arOrderTmp[$inum] = array();
						$arOrderTmp[$inum][$key] = $res;
						$arJoinGroup[$inum] = true;
					}
				}
			}
		}

		$productWhere = array();
		$arWhereTmp = array();
		$arAddJoinOn = array();

		$filter_keys = (!is_array($arFilter) ? array() : array_keys($arFilter));

		for ($i = 0, $cnt = count($filter_keys); $i < $cnt; $i++)
		{
			$key = strtoupper($filter_keys[$i]);
			$val = $arFilter[$filter_keys[$i]];

			$res = CIBlock::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			if ($arField = CCatalogProduct::ParseQueryBuildField($key))
			{
				$res = '';
				$join = true;

				$key = (string)$arField["FIELD"];
				$inum = (int)$arField["NUM"];

				if ($key == '' || ($inum <= 0 && isset($arSensID[$key])))
					continue;

				switch($key)
				{
					case "PRODUCT_ID":
						$res = CIBlock::FilterCreate("CAT_P".$inum.".PRODUCT_ID", $val, "number", $cOperationType);
						break;
					case "CATALOG_GROUP_ID":
						$res = CIBlock::FilterCreate("CAT_P".$inum.".CATALOG_GROUP_ID", $val, "number", $cOperationType);
						break;
					case "CURRENCY":
						$res = CIBlock::FilterCreate("CAT_P".$inum.".CURRENCY", $val, "string", $cOperationType);
						break;
					case "SHOP_QUANTITY":
						$val = (int)$val;
						$res = ' 1=1 ';
						$arAddJoinOn[$inum] =
							(($cOperationType=="N") ? " NOT " : " ").
							" ((CAT_P".$inum.".QUANTITY_FROM <= ".$val." OR CAT_P".$inum.".QUANTITY_FROM IS NULL) AND (CAT_P".$inum.".QUANTITY_TO >= ".$val." OR CAT_P".$inum.".QUANTITY_TO IS NULL)) ";
						break;
					case "PRICE":
						$res = CIBlock::FilterCreate("CAT_P".$inum.".PRICE", $val, "number", $cOperationType);
						break;
					case "QUANTITY":
						$res = CIBlock::FilterCreate("CAT_PR.QUANTITY", $val, "number", $cOperationType);
						$join = false;
						break;
					case "AVAILABLE":
						if ('N' !== $val)
							$val = 'Y';
						$res =
							" (IF (
					CAT_PR.QUANTITY > 0 OR
					IF (CAT_PR.QUANTITY_TRACE = 'D', '".$strDefQuantityTrace."', CAT_PR.QUANTITY_TRACE) = 'N' OR
					IF (CAT_PR.CAN_BUY_ZERO = 'D', '".$strDefCanBuyZero."', CAT_PR.CAN_BUY_ZERO) = 'Y',
					'Y', 'N'
					) ".(($cOperationType=="N") ? "<>" : "=")." '".$val."') ";
						$join = false;
						break;
					case "WEIGHT":
						$res = CIBlock::FilterCreate("CAT_PR.WEIGHT", $val, "number", $cOperationType);
						$join = false;
						break;
					case 'TYPE':
						$res = CIBlock::FilterCreate("CAT_PR.TYPE", $val, "number", $cOperationType);
						$join = false;
						break;
					case 'PURCHASING_PRICE':
						$res = CIBlock::FilterCreate("CAT_PR.PURCHASING_PRICE", $val, "number", $cOperationType);
						$join = false;
						break;
					case 'PURCHASING_CURRENCY':
						$res = CIBlock::FilterCreate("CAT_PR.PURCHASING_PRICE", $val, "string", $cOperationType);
						$join = false;
						break;
					case 'STORE_AMOUNT':
						$arStore[$inum] = true;
						if (!isset($arStoreWhere[$inum]))
							$arStoreWhere[$inum] = array();
						$arStoreWhere[$inum][] = CIBlock::FilterCreate("CAT_SP".$inum.".AMOUNT", $val, "number", $cOperationType);
						$join = false;
						break;
				}

				if ($res == '')
					continue;

				if ($join)
				{
					if (!isset($arWhereTmp[$inum]))
						$arWhereTmp[$inum] = array();
					$arWhereTmp[$inum][] = $res;
					$arJoinGroup[$inum] = true;
				}
				else
				{
					$productWhere[] = $res;
				}
			}
		}

		if (!empty($arSelect))
		{
			foreach ($arSelect as &$strOneSelect)
			{
				$val = strtoupper($strOneSelect);
				if (strncmp($val, 'CATALOG_GROUP_', 14) == 0)
				{
					$num = (int)substr($val, 14);
					if ($num > 0)
						$arJoinGroup[$num] = true;
				}
				elseif (strncmp($val, 'CATALOG_STORE_AMOUNT_', 21) == 0)
				{
					$num = (int)substr($val, 21);
					if ($num > 0)
						$arStore[$num] = true;
				}
			}
			unset($strOneSelect);
		}

		if (!empty($arJoinGroup))
		{
			$strSubWhere = implode(',', array_keys($arJoinGroup));

			$strUserGroups = (CCatalog::IsUserExists() ? $USER->GetGroups() : '2');
			$strCacheKey = "P_".$strUserGroups;
			$strCacheKey .= "_".$strSubWhere;
			$strCacheKey .= "_".LANGUAGE_ID;

			$cacheTime = CATALOG_CACHE_DEFAULT_TIME;
			if (defined("CATALOG_CACHE_TIME"))
				$cacheTime = intval(CATALOG_CACHE_TIME);

			$stackCacheManager->SetLength("catalog_GetQueryBuildArrays", 50);
			$stackCacheManager->SetTTL("catalog_GetQueryBuildArrays", $cacheTime);
			if ($stackCacheManager->Exist("catalog_GetQueryBuildArrays", $strCacheKey))
			{
				$arResult = $stackCacheManager->Get("catalog_GetQueryBuildArrays", $strCacheKey);
			}
			else
			{
				$strSql = "SELECT CAT_CG.ID, CAT_CGL.NAME as CATALOG_GROUP_NAME, ".
					"	IF(CAT_CGG.ID IS NULL, 'N', 'Y') as CATALOG_CAN_ACCESS, ".
					"	IF(CAT_CGG1.ID IS NULL, 'N', 'Y') as CATALOG_CAN_BUY ".
					"FROM b_catalog_group CAT_CG ".
					"	LEFT JOIN b_catalog_group2group CAT_CGG ON (CAT_CG.ID = CAT_CGG.CATALOG_GROUP_ID AND CAT_CGG.GROUP_ID IN (".$strUserGroups.") AND CAT_CGG.BUY <> 'Y') ".
					"	LEFT JOIN b_catalog_group2group CAT_CGG1 ON (CAT_CG.ID = CAT_CGG1.CATALOG_GROUP_ID AND CAT_CGG1.GROUP_ID IN (".$strUserGroups.") AND CAT_CGG1.BUY = 'Y') ".
					"	LEFT JOIN b_catalog_group_lang CAT_CGL ON (CAT_CG.ID = CAT_CGL.CATALOG_GROUP_ID AND CAT_CGL.LANG = '".LANGUAGE_ID."') ".
					" WHERE CAT_CG.ID IN (".$strSubWhere.") ".
					" GROUP BY CAT_CG.ID ";
				$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$arResult = array();
				while ($arRes = $dbRes->Fetch())
					$arResult[] = $arRes;

				$stackCacheManager->Set("catalog_GetQueryBuildArrays", $strCacheKey, $arResult);
			}

			foreach ($arResult as &$row)
			{
				$i = (int)$row["ID"];

				if (!empty($arWhereTmp[$i]) && is_array($arWhereTmp[$i]))
				{
					$sResWhere .= ' AND '.implode(' AND ', $arWhereTmp[$i]);
				}

				if (!empty($arOrderTmp[$i]) && is_array($arOrderTmp[$i]))
				{
					foreach($arOrderTmp[$i] as $k=>$v)
						$arResOrder[$k] = $v;
				}

				$sResSelect .= ", CAT_P".$i.".ID as CATALOG_PRICE_ID_".$i.", ".
					" CAT_P".$i.".CATALOG_GROUP_ID as CATALOG_GROUP_ID_".$i.", ".
					" CAT_P".$i.".PRICE as CATALOG_PRICE_".$i.", ".
					" CAT_P".$i.".CURRENCY as CATALOG_CURRENCY_".$i.", ".
					" CAT_P".$i.".QUANTITY_FROM as CATALOG_QUANTITY_FROM_".$i.", ".
					" CAT_P".$i.".QUANTITY_TO as CATALOG_QUANTITY_TO_".$i.", ".
					" '".$DB->ForSql($row["CATALOG_GROUP_NAME"])."' as CATALOG_GROUP_NAME_".$i.", ".
					" '".$DB->ForSql($row["CATALOG_CAN_ACCESS"])."' as CATALOG_CAN_ACCESS_".$i.", ".
					" '".$DB->ForSql($row["CATALOG_CAN_BUY"])."' as CATALOG_CAN_BUY_".$i.", ".
					" CAT_P".$i.".EXTRA_ID as CATALOG_EXTRA_ID_".$i;

				$sResFrom .= ' left join b_catalog_price CAT_P'.$i.' on (CAT_P'.$i.'.PRODUCT_ID = BE.ID AND CAT_P'.$i.'.CATALOG_GROUP_ID = '.$row['ID'].') ';

				if (isset($arAddJoinOn[$i]))
					$sResFrom .= ' and '.$arAddJoinOn[$i];
			}
			if (isset($row))
				unset($row);
		}

		$sResSelect .= ", CAT_PR.QUANTITY as CATALOG_QUANTITY, CAT_PR.QUANTITY_RESERVED as CATALOG_QUANTITY_RESERVED, ".
			" IF (CAT_PR.QUANTITY_TRACE = 'D', '".$strDefQuantityTrace."', CAT_PR.QUANTITY_TRACE) as CATALOG_QUANTITY_TRACE, ".
			" CAT_PR.QUANTITY_TRACE as CATALOG_QUANTITY_TRACE_ORIG, ".
			" IF (CAT_PR.CAN_BUY_ZERO = 'D', '".$strDefCanBuyZero."', CAT_PR.CAN_BUY_ZERO) as CATALOG_CAN_BUY_ZERO, ".
			" CAT_PR.CAN_BUY_ZERO as CATALOG_CAN_BUY_ZERO_ORIG, ".
			" IF (CAT_PR.NEGATIVE_AMOUNT_TRACE = 'D', '".$strDefNegAmount."', CAT_PR.NEGATIVE_AMOUNT_TRACE) as CATALOG_NEGATIVE_AMOUNT_TRACE, ".
			" CAT_PR.NEGATIVE_AMOUNT_TRACE as CATALOG_NEGATIVE_AMOUNT_ORIG, ".
			" IF (CAT_PR.SUBSCRIBE = 'D', '".$strSubscribe."', CAT_PR.SUBSCRIBE) as CATALOG_SUBSCRIBE, ".
			" CAT_PR.SUBSCRIBE as CATALOG_SUBSCRIBE_ORIG, ".
			" IF (
				CAT_PR.QUANTITY > 0 OR
				IF (CAT_PR.QUANTITY_TRACE = 'D', '".$strDefQuantityTrace."', CAT_PR.QUANTITY_TRACE) = 'N' OR
				IF (CAT_PR.CAN_BUY_ZERO = 'D', '".$strDefCanBuyZero."', CAT_PR.CAN_BUY_ZERO) = 'Y',
				'Y', 'N'
			) as CATALOG_AVAILABLE, ".
			" CAT_PR.WEIGHT as CATALOG_WEIGHT, CAT_PR.WIDTH as CATALOG_WIDTH, CAT_PR.LENGTH as CATALOG_LENGTH, CAT_PR.HEIGHT as CATALOG_HEIGHT, ".
			" CAT_PR.MEASURE as CATALOG_MEASURE, ".
			" CAT_VAT.RATE as CATALOG_VAT, CAT_PR.VAT_INCLUDED as CATALOG_VAT_INCLUDED, ".
			" CAT_PR.PRICE_TYPE as CATALOG_PRICE_TYPE, CAT_PR.RECUR_SCHEME_TYPE as CATALOG_RECUR_SCHEME_TYPE, ".
			" CAT_PR.RECUR_SCHEME_LENGTH as CATALOG_RECUR_SCHEME_LENGTH, CAT_PR.TRIAL_PRICE_ID as CATALOG_TRIAL_PRICE_ID, ".
			" CAT_PR.WITHOUT_ORDER as CATALOG_WITHOUT_ORDER, CAT_PR.SELECT_BEST_PRICE as CATALOG_SELECT_BEST_PRICE, ".
			" CAT_PR.PURCHASING_PRICE as CATALOG_PURCHASING_PRICE, CAT_PR.PURCHASING_CURRENCY as CATALOG_PURCHASING_CURRENCY, CAT_PR.TYPE as CATALOG_TYPE ";

		$sResFrom .= " left join b_catalog_product CAT_PR on (CAT_PR.ID = BE.ID) ";
		$sResFrom .= " left join b_catalog_iblock CAT_IB on ((CAT_PR.VAT_ID IS NULL OR CAT_PR.VAT_ID = 0) AND CAT_IB.IBLOCK_ID = BE.IBLOCK_ID) ";
		$sResFrom .= " left join b_catalog_vat CAT_VAT on (CAT_VAT.ID = IF((CAT_PR.VAT_ID IS NULL OR CAT_PR.VAT_ID = 0), CAT_IB.VAT_ID, CAT_PR.VAT_ID)) ";

		if (!empty($productWhere))
		{
			$sResWhere .= ' and '.implode(' and ', $productWhere);
		}
		unset($productWhere);

		if (!empty($arStore))
		{
			foreach (array_keys($arStore) as $inum)
			{
				$sResFrom .= " left join b_catalog_store_product CAT_SP".$inum." on (CAT_SP".$inum.".PRODUCT_ID = BE.ID and CAT_SP".$inum.".STORE_ID = ".$inum.") ";
				$sResSelect  .= ", CAT_SP".$inum.".AMOUNT as CATALOG_STORE_AMOUNT_".$inum." ";
			}

			if (!empty($arStoreOrder))
			{
				foreach ($arStoreOrder as $oneStoreOrder)
				{
					if (!empty($oneStoreOrder) && is_array($oneStoreOrder))
					{
						foreach ($oneStoreOrder as $k => $v)
							$arResOrder[$k] = $v;
						unset($k, $v);
					}
				}
				unset($oneStoreOrder);
			}

			if (!empty($arStoreWhere))
			{
				foreach ($arStoreWhere as $where)
					$sResWhere .= ' and '.implode(' and ', $where);
			}
		}

		return array(
			'SELECT' => $sResSelect,
			'FROM' => $sResFrom,
			'WHERE' => $sResWhere,
			'ORDER' => $arResOrder
		);
	}

	
	/**
	* <p>Метод возвращает результат выборки записей товаров в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле товара, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи товара.
	* Массив имеет вид: <pre class="syntax">array(
	* "[модификатор1][оператор1]название_поля1" =&gt; "значение1",
	* "[модификатор2][оператор2]название_поля2" =&gt; "значение2", . . . )</pre>
	* Удовлетворяющие фильтру записи возвращаются в результате, а
	* записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	* <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и пустая строка
	* так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	* являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	* или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	* поля строго больше передаваемой в фильтр величины;</li> <li><b> -
	* значение поля меньше или равно передаваемой в фильтр
	* величины;</b></li> <li><b> - значение поля строго меньше передаваемой в
	* фильтр величины;</b></li> <li> <b>@</b> - оператор может использоваться для
	* целочисленных и вещественных данных при передаче набора
	* значений (массива). В этом случае при генерации sql-запроса будет
	* использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	* <li> <b>~</b> - значение поля проверяется на соответствие
	* передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	* проверяется на соответствие передаваемой в фильтр строке в
	* соответствии с языком запросов.</li> </ul> В качестве "название_поляX"
	* может стоять любое поле товара.<br><br> Пример фильтра: <pre
	* class="syntax">array("QUANTITY_TRACE" =&gt; "Y")</pre> Этот фильтр означает "выбрать все
	* записи, в которых значение в поле QUANTITY_TRACE (т.е. ведется
	* количественный учет) равно Y".<br><br> Значение по умолчанию - пустой
	* массив array() - означает, что результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи типов товара. Массив
	* имеет вид: <pre class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В
	* качестве "название_поля<i>N</i>" может стоять любое поле типов
	* товара. <br><br> Если массив пустой, то метод вернет число записей,
	* удовлетворяющих фильтру.<br><br> Значение по умолчанию - <i>false</i> -
	* означает, что результат группироваться не будет.
	*
	* @param array $arNavStartParams = false Массив параметров выборки. Может содержать следующие ключи: <ul>
	* <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li> <li> любой ключ,
	* принимаемый методом <b> CDBResult::NavQuery</b> в качестве третьего
	* параметра.</li> </ul> Значение по умолчанию - <i>false</i> - означает, что
	* параметров выборки нет.
	*
	* @param array $arSelectFields = array() Массив полей записей, которые будут возвращены методом. Можно
	* указать только те поля, которые необходимы. Если в массиве
	* присутствует значение "*", то будут возвращены все доступные
	* поля.<br><br> Значение по умолчанию - пустой массив array() - означает,
	* что будут возвращены все поля основной таблицы запроса.
	*
	* @return CDBResult <p>Объект класса CDBResult, содержащий записи в виде ассоциативных
	* массивов параметров товара с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> <th width="10%">С версии</th> </tr> <tr> <td>ID</td>
	* <td>Код товара.</td> <td></td> </tr> <tr> <td>QUANTITY</td> <td>Количество на складе.</td>
	* <td></td> </tr> <tr> <td>QUANTITY_RESERVED</td> <td>Зарезервированное количество.</td>
	* <td>12.5.0</td> </tr> <tr> <td>QUANTITY_TRACE</td> <td>Определяет ведется ли
	* количественный учет (Y/N). До версии 12.5.0 параметр назывался
	* "уменьшать количество при оформлении заказа". Оригинальное
	* значение доступно в ключе QUANTITY_TRACE_ORIG.</td> <td></td> </tr> <tr>
	* <td>QUANTITY_TRACE_ORIG</td> <td>Флаг (Y/N/D<b>*</b>) "включить количественный учет".</td>
	* <td>12.0.0</td> </tr> <tr> <td>SUBSCRIBE</td> <td>Разрешение/запрет подписки при
	* отсутствии товара (Y/N/D<b>*</b>).</td> <td>14.0.0</td> </tr> <tr> <td>WEIGHT</td> <td>Вес
	* единицы товара.</td> <td>14.0.0</td> </tr> <tr> <td>WIDTH</td> <td>Ширина товара (в
	* мм).</td> <td>14.0.0</td> </tr> <tr> <td>LENGTH</td> <td>Длина товара (в мм).</td> <td>14.0.0</td> </tr>
	* <tr> <td>HEIGHT</td> <td>Высота товара (в мм).</td> <td>14.0.0</td> </tr> <tr> <td>PRICE_TYPE</td>
	* <td>Тип цены (S - одноразовый платеж, R - регулярные платежи, T -
	* пробная подписка)</td> <td></td> </tr> <tr> <td>RECUR_SCHEME_TYPE</td> <td>Тип периода
	* подписки ("H" - час, "D" - сутки, "W" - неделя, "M" - месяц, "Q" - квартал, "S" -
	* полугодие, "Y" - год)</td> <td></td> </tr> <tr> <td>RECUR_SCHEME_LENGTH</td> <td>Длина периода
	* подписки.</td> <td></td> </tr> <tr> <td>TRIAL_PRICE_ID</td> <td>Код товара, для которого
	* данный товар является пробным.</td> <td></td> </tr> <tr> <td>WITHOUT_ORDER</td> <td>Флаг
	* "Продление подписки без оформления заказа"</td> <td></td> </tr> <tr>
	* <td>TIMESTAMP_X</td> <td>Дата последнего изменения записи. Задается в
	* формате сайта.</td> <td></td> </tr> <tr> <td>VAT_ID</td> <td>Идентификатор ставки
	* НДС.</td> <td></td> </tr> <tr> <td>VAT_INCLUDED</td> <td>Признак включённости НДС в цену
	* (Y/N).</td> <td></td> </tr> <tr> <td>PURCHASING_PRICE</td> <td>Величина закупочной цены.</td>
	* <td>12.5.0</td> </tr> <tr> <td>PURCHASING_CURRENCY</td> <td>Валюта закупочной цены.</td>
	* <td>12.5.0</td> </tr> <tr> <td>CAN_BUY_ZERO</td> <td>Разрешена ли покупка при отсутствии
	* товара (Y/N). Оригинальное значение доступно в ключе CAN_BUY_ZERO_ORIG.</td>
	* <td>12.0.0</td> </tr> <tr> <td>CAN_BUY_ZERO_ORIG</td> <td>Флаг (Y/N/D<b>*</b>) "разрешить покупку
	* при отсутствии товара".</td> <td>12.0.0</td> </tr> <tr> <td>NEGATIVE_AMOUNT_TRACE</td>
	* <td>Разрешено ли отрицательное количество товара (Y/N). Оригинальное
	* значение доступно в ключе NEGATIVE_AMOUNT_TRACE_ORIG.</td> <td>12.0.0</td> </tr> <tr>
	* <td>NEGATIVE_AMOUNT_TRACE_ORIG</td> <td>Флаг (Y/N/D<b>*</b>) "разрешить отрицательное
	* количество товара".</td> <td>12.0.0</td> </tr> <tr> <td>TMP_ID</td> <td>Временный
	* строковый идентификатор, используемый для служебных целей.</td>
	* <td></td> </tr> <tr> <td>BARCODE_MULTI</td> <td>(Y/N) Определяет каждый ли экземпляр
	* товара имеет собственный штрихкод.</td> <td>12.5.0</td> </tr> <tr> <td>MEASURE</td> <td>ID
	* единицы измерения.</td> <td>14.0.0</td> </tr> <tr> <td>TYPE</td> <td>Тип товара (для
	* типа товара "комплект" значение равно "2", во всех других случаях -
	* "1").</td> <td>14.0.0</td> </tr> <tr> <td>ELEMENT_IBLOCK_ID</td> <td>Код инфоблока товара.</td>
	* <td></td> </tr> <tr> <td>ELEMENT_XML_ID</td> <td>Внешний код товара.</td> <td></td> </tr> <tr>
	* <td>ELEMENT_NAME </td> <td>Название товара.</td> <td></td> </tr> <tr><td colspan="3"> <b>*</b> -
	* значение берется из настроек модуля.</td></tr> </table> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выведем коды 10 товаров с самым большим количеством на складе
	* // из тех, количество которых при заказе должно уменьшаться
	* $ind = 0;
	* $db_res = CCatalogProduct::GetList(
	*         array("QUANTITY" =&gt; "DESC"),
	*         array("QUANTITY_TRACE" =&gt; "Y"),
	*         false,
	*         array("nTopCount" =&gt; 10)
	*     );
	* while (($ar_res = $db_res-&gt;Fetch()) &amp;&amp; ($ind &lt; 10))
	* {
	*     echo $ar_res["ID"].", ";
	*     $ind++;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getlist.971a2b70.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (!is_array($arOrder) && !is_array($arFilter))
		{
			$arOrder = (string)$arOrder;
			$arFilter = (string)$arFilter;
			$arOrder = ($arOrder != '' && $arFilter != '' ? array($arOrder => $arFilter) : array());
			$arFilter = (is_array($arGroupBy) ? $arGroupBy : array());
			$arGroupBy = false;
		}

		$defaultQuantityTrace = ((string)Option::get('catalog', 'default_quantity_trace') == 'Y' ? 'Y' : 'N');
		$defaultCanBuyZero = ((string)Option::get('catalog', 'default_can_buy_zero') == 'Y' ? 'Y' : 'N');
		$defaultNegativeAmount = ((string)Option::get('catalog', 'allow_negative_amount') == 'Y' ? 'Y' : 'N');
		$defaultSubscribe = ((string)Option::get('catalog', 'default_subscribe') == 'N' ? 'N' : 'Y');

		$arFields = array(
			"ID" => array("FIELD" => "CP.ID", "TYPE" => "int"),
			"QUANTITY" => array("FIELD" => "CP.QUANTITY", "TYPE" => "double"),
			"QUANTITY_RESERVED" => array("FIELD" => "CP.QUANTITY_RESERVED", "TYPE" => "double"),
			"QUANTITY_TRACE_ORIG" => array("FIELD" => "CP.QUANTITY_TRACE", "TYPE" => "char"),
			"CAN_BUY_ZERO_ORIG" => array("FIELD" => "CP.CAN_BUY_ZERO", "TYPE" => "char"),
			"NEGATIVE_AMOUNT_TRACE_ORIG" => array("FIELD" => "CP.NEGATIVE_AMOUNT_TRACE", "TYPE" => "char"),
			"QUANTITY_TRACE" => array("FIELD" => "IF (CP.QUANTITY_TRACE = 'D', '".$defaultQuantityTrace."', CP.QUANTITY_TRACE)", "TYPE" => "char"),
			"CAN_BUY_ZERO" => array("FIELD" => "IF (CP.CAN_BUY_ZERO = 'D', '".$defaultCanBuyZero."', CP.CAN_BUY_ZERO)", "TYPE" => "char"),
			"NEGATIVE_AMOUNT_TRACE" => array("FIELD" => "IF (CP.NEGATIVE_AMOUNT_TRACE = 'D', '".$defaultNegativeAmount."', CP.NEGATIVE_AMOUNT_TRACE)", "TYPE" => "char"),
			"SUBSCRIBE_ORIG" => array("FIELD" => "CP.SUBSCRIBE", "TYPE" => "char"),
			"SUBSCRIBE" => array("FIELD" => "IF (CP.SUBSCRIBE = 'D', '".$defaultSubscribe."', CP.SUBSCRIBE)", "TYPE" => "char"),
			"AVAILABLE" => array("FIELD" => "IF (
				CP.QUANTITY <= 0 AND
				IF (CP.QUANTITY_TRACE = 'D', '".$defaultQuantityTrace."', CP.QUANTITY_TRACE) = 'Y' AND
				IF (CP.CAN_BUY_ZERO = 'D', '".$defaultCanBuyZero."', CP.CAN_BUY_ZERO) = 'N',
				'N', 'Y'
			)", "TYPE" => "char"),
			"WEIGHT" => array("FIELD" => "CP.WEIGHT", "TYPE" => "double"),
			"WIDTH" => array("FIELD" => "CP.WIDTH", "TYPE" => "double"),
			"LENGTH" => array("FIELD" => "CP.LENGTH", "TYPE" => "double"),
			"HEIGHT" => array("FIELD" => "CP.HEIGHT", "TYPE" => "double"),
			"TIMESTAMP_X" => array("FIELD" => "CP.TIMESTAMP_X", "TYPE" => "datetime"),
			"PRICE_TYPE" => array("FIELD" => "CP.PRICE_TYPE", "TYPE" => "char"),
			"RECUR_SCHEME_TYPE" => array("FIELD" => "CP.RECUR_SCHEME_TYPE", "TYPE" => "char"),
			"RECUR_SCHEME_LENGTH" => array("FIELD" => "CP.RECUR_SCHEME_LENGTH", "TYPE" => "int"),
			"TRIAL_PRICE_ID" => array("FIELD" => "CP.TRIAL_PRICE_ID", "TYPE" => "int"),
			"WITHOUT_ORDER" => array("FIELD" => "CP.WITHOUT_ORDER", "TYPE" => "char"),
			"SELECT_BEST_PRICE" => array("FIELD" => "CP.SELECT_BEST_PRICE", "TYPE" => "char"),
			"VAT_ID" => array("FIELD" => "CP.VAT_ID", "TYPE" => "int"),
			"VAT_INCLUDED" => array("FIELD" => "CP.VAT_INCLUDED", "TYPE" => "char"),
			"TMP_ID" => array("FIELD" => "CP.TMP_ID", "TYPE" => "char"),
			"PURCHASING_PRICE" => array("FIELD" => "CP.PURCHASING_PRICE", "TYPE" => "double"),
			"PURCHASING_CURRENCY" => array("FIELD" => "CP.PURCHASING_CURRENCY", "TYPE" => "string"),
			"BARCODE_MULTI" => array("FIELD" => "CP.BARCODE_MULTI", "TYPE" => "char"),
			"MEASURE" => array("FIELD" => "CP.MEASURE", "TYPE" => "int"),
			"TYPE" => array("FIELD" => "CP.TYPE", "TYPE" => "int"),
			"ELEMENT_IBLOCK_ID" => array("FIELD" => "I.IBLOCK_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_iblock_element I ON (CP.ID = I.ID)"),
			"ELEMENT_XML_ID" => array("FIELD" => "I.XML_ID", "TYPE" => "string", "FROM" => "INNER JOIN b_iblock_element I ON (CP.ID = I.ID)"),
			"ELEMENT_NAME" => array("FIELD" => "I.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_iblock_element I ON (CP.ID = I.ID)")
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_product CP ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_product CP ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && array_key_exists('nTopCount', $arNavStartParams))
		{
			$intTopCount = intval($arNavStartParams["nTopCount"]);
		}
		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_product CP ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (empty($arSqls["GROUPBY"]))
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();
			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if ($boolNavStartParams && 0 < $intTopCount)
			{
				$strSql .= " LIMIT ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

/*
* @deprecated deprecated since catalog 8.5.1
* @see CCatalogProduct::GetList()
*/
	public static function GetListEx($arOrder=array("SORT"=>"ASC"), $arFilter=array())
	{
		return false;
	}

	public static function GetVATInfo($PRODUCT_ID)
	{
		global $DB;

		$query = "
SELECT CAT_VAT.*, CAT_PR.VAT_INCLUDED
FROM b_catalog_product CAT_PR
LEFT JOIN b_iblock_element BE ON (BE.ID = CAT_PR.ID)
LEFT JOIN b_catalog_iblock CAT_IB ON ((CAT_PR.VAT_ID IS NULL OR CAT_PR.VAT_ID = 0) AND CAT_IB.IBLOCK_ID = BE.IBLOCK_ID)
LEFT JOIN b_catalog_vat CAT_VAT ON (CAT_VAT.ID = IF((CAT_PR.VAT_ID IS NULL OR CAT_PR.VAT_ID = 0), CAT_IB.VAT_ID, CAT_PR.VAT_ID))
WHERE CAT_PR.ID = '".intval($PRODUCT_ID)."'
AND CAT_VAT.ACTIVE='Y'
";
		return $DB->Query($query);
	}

	static public function SetProductType($intID, $intTypeID)
	{
		global $DB;
		$intID = intval($intID);
		if (0 >= $intID)
			return false;
		$intTypeID = intval($intTypeID);
		if (self::TYPE_PRODUCT != $intTypeID && self::TYPE_SET != $intTypeID)
			return false;
		$strSql = 'update b_catalog_product set TYPE='.$intTypeID.' where ID='.$intID;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return true;
	}
}