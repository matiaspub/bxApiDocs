<?
IncludeModuleLangFile(__FILE__);


/**
 * 
 *
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
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION;
		global $CATALOG_TIME_PERIOD_TYPES;

		$arMsg = array();
		$boolResult = true;

		if ($ACTION == "ADD" && (!is_set($arFields, "ID") || intval($arFields["ID"])<=0))
		{
			$arMsg[] = array('id' => 'ID','text' => GetMessage('KGP_EMPTY_ID'));
			$boolResult = false;
		}
		if ($ACTION != "ADD" && intval($ID) <= 0)
		{
			$arMsg[] = array('id' => 'ID','text' => GetMessage('KGP_EMPTY_ID'));
			$boolResult = false;
		}

		if ($ACTION != "ADD" && is_set($arFields, 'ID'))
			unset($arFields["ID"]);

		if (is_set($arFields, "ID") || $ACTION=="ADD")
			$arFields["ID"] = intval($arFields["ID"]);
		if (is_set($arFields, "QUANTITY") || $ACTION=="ADD")
			$arFields["QUANTITY"] = doubleval($arFields["QUANTITY"]);
		if (is_set($arFields, "QUANTITY_RESERVED") || $ACTION=="ADD")
			$arFields["QUANTITY_RESERVED"] = doubleval($arFields["QUANTITY_RESERVED"]);
		if (is_set($arFields["OLD_QUANTITY"]))
			$arFields["OLD_QUANTITY"] = doubleval($arFields["OLD_QUANTITY"]);
		if (is_set($arFields, "WEIGHT") || $ACTION=="ADD")
			$arFields["WEIGHT"] = doubleval($arFields["WEIGHT"]);

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

		if (isset($CATALOG_TIME_PERIOD_TYPES) && is_array($CATALOG_TIME_PERIOD_TYPES))
		{
			if ((is_set($arFields, "RECUR_SCHEME_TYPE") || $ACTION=="ADD") && (StrLen($arFields["RECUR_SCHEME_TYPE"]) <= 0 || !array_key_exists($arFields["RECUR_SCHEME_TYPE"], $CATALOG_TIME_PERIOD_TYPES)))
			{
				$arFields["RECUR_SCHEME_TYPE"] = 'D';
			}
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
			$arFields['PURCHASING_PRICE'] = str_replace(',', '.', $arFields['PURCHASING_PRICE']);
			$arFields['PURCHASING_PRICE'] = doubleval($arFields['PURCHASING_PRICE']);
		}
		if (is_set($arFields, 'PURCHASING_CURRENCY'))
		{
			if (empty($arFields['PURCHASING_CURRENCY']))
			{
				$arMsg[] = array('id' => 'PURCHASING_CURRENCY','text' => GetMessage('BT_MOD_CATALOG_PROD_ERR_COST_CURRENCY'));
				$boolResult = false;
			}
			else
			{
				$arFields['PURCHASING_CURRENCY'] = strtoupper($arFields['PURCHASING_CURRENCY']);
			}
		}
		if ((is_set($arFields, 'BARCODE_MULTI') || 'ADD' == $ACTION) && 'Y' != $arFields['BARCODE_MULTI'])
			$arFields['BARCODE_MULTI'] = 'N';

		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ThrowException($obError);
		}
		return $boolResult;
	}

	
	/**
	 * <p>Функция по коду товара ID возвращает все параметры этого товара, включая относящиеся к товару как к элементу информационного блока. </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код товара.
	 *
	 *
	 *
	 * @return array <p>Возвращает ассоциативный массив параметров товара или <i>false</i> в
	 * случае ошибки. Для просмотра всех доступных параметров
	 * воспользуйтесь кодом из приведенного ниже примера. </p><a
	 * name="examples"></a>
	 *
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
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/ccatalogproduct__getbyidex.c95826cc.php
	 * @author Bitrix
	 */
	public static function GetByIDEx($ID, $boolAllValues = false)
	{
		global $DB, $USER;

		$boolAllValues = (true == $boolAllValues ? true : false);
		$ID = intval($ID);
		if (0 >= $ID)
			return false;
		$arFilter = Array("ID" => $ID, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y");

		$dbIBlockElement = CIBlockElement::GetList(array(), $arFilter);
		if ($arIBlockElement = $dbIBlockElement->GetNext())
		{
			if ($arIBlock = CIBlock::GetArrayByID($arIBlockElement["IBLOCK_ID"]))
			{
				$arIBlockElement["IBLOCK_ID"] = $arIBlock["ID"];
				$arIBlockElement["IBLOCK_NAME"] = htmlspecialcharsbx($arIBlock["NAME"]);
				$arIBlockElement["~IBLOCK_NAME"] = $arIBlock["NAME"];
				$arIBlockElement["PROPERTIES"] = false;
				$dbProps = CIBlockElement::GetProperty($arIBlock["ID"], $ID, "sort", "asc", Array("ACTIVE"=>"Y", "NON_EMPTY"=>"Y"));
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
					$arAllPrices = Array();
					do
					{
						$arAllPrices[$arPrices["CATALOG_GROUP_ID"]] = Array("EXTRA_ID"=>intval($arPrices["EXTRA_ID"]), "PRICE"=>doubleval($arPrices["PRICE"]), "CURRENCY"=>htmlspecialcharsbx($arPrices["CURRENCY"]));
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
	 * <p>Уменьшает количество на складе товара с кодом PRODUCT_ID на величину DELTA_QUANTITY, если указанное количество есть на складе и у товара установлен флаг "уменьшать количество товара при заказе" </p>
	 *
	 *
	 *
	 *
	 * @param int $PRODUCT_ID  Код товара.
	 *
	 *
	 *
	 * @param int $DELTA_QUANTITY  Число, на которое нужно уменьшить количество товара на складе.
	 *
	 *
	 *
	 * @return bool <p>Возвращает <i>true</i> в случае успешного уменьшения и <i>false</i> - в
	 * противном случае </p><a name="examples"></a>
	 *
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
	public static function QuantityTracer($ProductID, $DeltaQuantity)
	{
		global $DB;
		global $CACHE_MANAGER;

		$boolClearCache = false;

		$ProductID = intval($ProductID);
		if (0 >= $ProductID)
			return false;
		$DeltaQuantity = doubleval($DeltaQuantity);
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
			$arFields["QUANTITY"] = doubleval($arProduct["QUANTITY"]) - $DeltaQuantity;

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
	 * <p>Функция по коду товара ID возвращает количество товара, доступное для покупки.</p>
	 *
	 *
	 *
	 *
	 * @param int $productID  Код товара.
	 *
	 *
	 *
	 * @param  $int  Количество, ближайшее продаваемое количество к которому
	 * необходимо найти.
	 *
	 *
	 *
	 * @param quantit $y = 1[ массив групп, которым принадлежит пользователь. Для текущего
	 * пользователя он возвращается методом $USER-&gt;GetUserGroupArray() 
	 *
	 *
	 *
	 * @param array $arUserGroups = array()]] 
	 *
	 *
	 *
	 * @return int <p>Количество товара, доступное для покупки.</p><a name="examples"></a>
	 *
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
	public static function GetNearestQuantityPrice($productID, $quantity = 1, $arUserGroups = array())
	{
		global $APPLICATION;

		$mxResult = true;
		foreach (GetModuleEvents("catalog", "OnGetNearestQuantityPrice", true) as $arEvent)
		{
			$mxResult = ExecuteModuleEventEx($arEvent, array($productID, $quantity, $arUserGroups));
			if (true !== $mxResult)
				return $mxResult;
		}

		// Check input params
		$productID = intval($productID);
		if ($productID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("BT_MOD_CATALOG_PROD_ERR_PRODUCT_ID_ABSENT"), "NO_PRODUCT_ID");
			return false;
		}

		$quantity = doubleval($quantity);
		if ($quantity <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("BT_MOD_CATALOG_PROD_ERR_QUANTITY_ABSENT"), "NO_QUANTITY");
			return false;
		}

		if (!is_array($arUserGroups) && intval($arUserGroups)."|" == $arUserGroups."|")
			$arUserGroups = array(intval($arUserGroups));

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
			if ($quantity >= doubleval($arPriceList["QUANTITY_FROM"])
				&& ($quantity <= doubleval($arPriceList["QUANTITY_TO"]) || doubleval($arPriceList["QUANTITY_TO"]) == 0))
			{
				$nearestQuantity = $quantity;
				break;
			}

			if ($quantity < doubleval($arPriceList["QUANTITY_FROM"]))
			{
				$nearestQuantity_tmp = doubleval($arPriceList["QUANTITY_FROM"]);
				$quantityDifference_tmp = doubleval($arPriceList["QUANTITY_FROM"]) - $quantity;
			}
			else
			{
				$nearestQuantity_tmp = doubleval($arPriceList["QUANTITY_TO"]);
				$quantityDifference_tmp = $quantity - doubleval($arPriceList["QUANTITY_TO"]);
			}

			if ($quantityDifference < 0 || $quantityDifference_tmp < $quantityDifference)
			{
				$quantityDifference = $quantityDifference_tmp;
				$nearestQuantity = $nearestQuantity_tmp;
			}
		}

		foreach (GetModuleEvents("catalog", "OnGetNearestQuantityPriceResult", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$nearestQuantity))===false)
				return false;
		}

		return ($nearestQuantity > 0 ? $nearestQuantity : false);
	}

	
	/**
	 * <p>Метод возвращает параметры наименьшей цены товара productID в количестве quantity для посетителя, входящего в группы пользователей arUserGroups.</p>
	 *
	 *
	 *
	 *
	 * @param int $productID  Код товара.
	 *
	 *
	 *
	 * @param  $int  Приобретаемое количество.
	 *
	 *
	 *
	 * @param quantit $y = 1[ массив групп, которым принадлежит пользователь. Для текущего
	 * пользователя он возвращается методом $USER-&gt;GetUserGroupArray() 
	 *
	 *
	 *
	 * @param array $arUserGroups = array()[ "Y", если вычисляется для продления товара (продажа контента), "N" в
	 * остальных случаях.
	 *
	 *
	 *
	 * @param string $renewal = "N"[ Массив цен. Если он установлен, то пересчет идет не по ценам из
	 * базы данных, а по ценам из этого массива. Представляет собой
	 * массив ассоциативных массивов с ключами: <ul> <li> <b>ID</b> - код цены;</li>
	 * <li> <b>PRICE</b> - цена;</li> <li> <b>CURRENCY</b> - валюта;</li> <li> <b>CATALOG_GROUP_ID</b> - код
	 * типа цен.</li> </ul>
	 *
	 *
	 *
	 * @param array $arPrices = array()[ Сайт, для которого производится вычисление. Если парамерт равен
	 * <i>false</i>, то берется текущий сайт.
	 *
	 *
	 *
	 * @param string $siteID = false]]]]] 
	 *
	 *
	 *
	 * @return array <p>Массив вида:</p><pre class="syntax">array( "PRICE" =&gt;
	 * массив_параметров_минимальной_цены, "DISCOUNT_PRICE" =&gt;
	 * минимальная_цена_в_базовой_валюте, "DISCOUNT" =&gt;
	 * массив_параметров_максимальной_доступной_скидки )</pre><a
	 * name="examples"></a>
	 *
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
	public static function GetOptimalPrice($intProductID, $quantity = 1, $arUserGroups = array(), $renewal = "N", $arPrices = array(), $siteID = false, $arDiscountCoupons = false)
	{
		global $APPLICATION;

		$mxResult = true;
		foreach (GetModuleEvents("catalog", "OnGetOptimalPrice", true) as $arEvent)
		{
			$mxResult = ExecuteModuleEventEx($arEvent, array($intProductID, $quantity, $arUserGroups, $renewal, $arPrices, $siteID, $arDiscountCoupons));
			if (true !== $mxResult)
				return $mxResult;
		}

		$intProductID = intval($intProductID);
		if (0 >= $intProductID)
		{
			$APPLICATION->ThrowException(GetMessage("BT_MOD_CATALOG_PROD_ERR_PRODUCT_ID_ABSENT"), "NO_PRODUCT_ID");
			return false;
		}

		$quantity = doubleval($quantity);
		if (0 >= $quantity)
		{
			$APPLICATION->ThrowException(GetMessage("BT_MOD_CATALOG_PROD_ERR_QUANTITY_ABSENT"), "NO_QUANTITY");
			return false;
		}

		if (!is_array($arUserGroups) && intval($arUserGroups)."|" == $arUserGroups."|")
			$arUserGroups = array(intval($arUserGroups));

		if (!is_array($arUserGroups))
			$arUserGroups = array();

		if (!in_array(2, $arUserGroups))
			$arUserGroups[] = 2;

		$rsVAT = CCatalogProduct::GetVATInfo($intProductID);
		if ($arVAT = $rsVAT->Fetch())
		{
			$arVAT['RATE'] = doubleval($arVAT['RATE'] * 0.01);
		}
		else
		{
			$arVAT = array('RATE' => 0.0, 'VAT_INCLUDED' => 'N');
		}

		$renewal = (($renewal == "N") ? "N" : "Y");

		if (false === $siteID)
			$siteID = SITE_ID;

		if (false === $arDiscountCoupons)
			$arDiscountCoupons = CCatalogDiscountCoupon::GetCoupons();

		$strBaseCurrency = CCurrency::GetBaseCurrency();
		if (empty($strBaseCurrency))
		{
			$APPLICATION->ThrowException(GetMessage("BT_MOD_CATALOG_PROD_ERR_NO_BASE_CURRENCY"), "NO_BASE_CURRENCY");
			return false;
		}

		$rsElements = CIBlockElement::GetList(
			array(),
			array('ID' => $intProductID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'IBLOCK_ID')
		);
		if (!($arElement = $rsElements->Fetch()))
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $intProductID, GetMessage('BT_MOD_CATALOG_PROD_ERR_ELEMENT_ID_NOT_FOUND')), "NO_ELEMENT");
			return false;
		}
		$intIBlockID = intval($arElement["IBLOCK_ID"]);

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
		}
		else
		{
			foreach ($arPrices as &$arOnePrice)
			{
				$arOnePrice['ELEMENT_IBLOCK_ID'] = $intIBlockID;
			}
			if (isset($arOnePrice))
				unset($arOnePrice);
		}

		if (empty($arPrices))
			return false;

//		$boolDiscountVat = ('N' != COption::GetOptionString('catalog', 'discount_vat', 'Y'));
		$boolDiscountVat = true;
		$strDiscSaveApply = COption::GetOptionString('catalog', 'discsave_apply', 'R');

		$dblMinPrice = -1;
		$arMinPrice = array();
		$arMinDiscounts = array();

		foreach ($arPrices as &$arPriceList)
		{
			$arPriceList['VAT_RATE'] = $arVAT['RATE'];
			$arPriceList['VAT_INCLUDED'] = $arVAT['VAT_INCLUDED'];
			$arPriceList['ORIG_VAT_INCLUDED'] = $arPriceList['VAT_INCLUDED'];

			if ($boolDiscountVat)
			{
				if ('N' == $arPriceList['VAT_INCLUDED'])
				{
					$arPriceList['PRICE'] *= (1 + $arPriceList['VAT_RATE']);
					$arPriceList['VAT_INCLUDED'] = 'Y';
				}
			}
			else
			{
				if ('Y' == $arPriceList['VAT_INCLUDED'])
				{
					$arPriceList['PRICE'] /= (1 + $arPriceList['VAT_RATE']);
					$arPriceList['VAT_INCLUDED'] = 'N';
				}
			}

			if ($arPriceList["CURRENCY"] == $strBaseCurrency)
				$dblCurrentPrice = $arPriceList["PRICE"];
			else
				$dblCurrentPrice = CCurrencyRates::ConvertCurrency($arPriceList["PRICE"], $arPriceList["CURRENCY"], $strBaseCurrency);

			$arDiscounts = CCatalogDiscount::GetDiscount($intProductID, $intIBlockID, $arPriceList["CATALOG_GROUP_ID"], $arUserGroups, $renewal, $siteID, $arDiscountCoupons);

			$arDiscSave = array();
			$arPriceDiscount = array();

			$arResultPrice = array(
				'PRICE' => $dblCurrentPrice,
				'CURRENCY' => $strBaseCurrency,
			);
			$arDiscountApply = array();

			if (!empty($arDiscounts))
			{
				CCatalogProduct::__PrimaryDiscountFilter($arDiscounts, $arPriceDiscount, $arDiscSave, $arResultPrice);

				if (!empty($arPriceDiscount))
				{
					foreach ($arPriceDiscount as &$arOnePriority)
					{
						$boolResultPriority = CCatalogProduct::__CalcOnePriority($arOnePriority, $arDiscountApply, $arResultPrice);
						if (!$boolResultPriority)
						{
							return false;
						}
						else
						{
							if (isset($arResultPrice['LAST_DISCOUNT']) && 'Y' == $arResultPrice['LAST_DISCOUNT'])
								break;
						}
					}
					if (isset($arOnePriority))
						unset($arOnePriority);
				}

				if (!empty($arDiscSave))
				{
					switch($strDiscSaveApply)
					{
					case 'R':
						$arDiscSaveResult = array(
							'PRICE' => $dblCurrentPrice,
							'CURRENCY' => $strBaseCurrency,
						);
						$arDiscSaveApply = array();
						$boolResultDiscSave = CCatalogProduct::__CalcDiscSave($arDiscSave, $arDiscSaveApply, $arDiscSaveResult);
						if (!$boolResultDiscSave)
						{
							return false;
						}
						else
						{
							if ($arDiscSaveResult['PRICE'] < $arResultPrice['PRICE'])
							{
								$arResultPrice = $arDiscSaveResult;
								$arDiscountApply = $arDiscSaveApply;
							}
						}
						break;
					case 'A':
						$boolResultDiscSave = CCatalogProduct::__CalcDiscSave($arDiscSave, $arDiscountApply, $arResultPrice);
						if (!$boolResultDiscSave)
						{
							return false;
						}
						break;
					case 'D':
						if (empty($arDiscountApply))
						{
							$boolResultDiscSave = CCatalogProduct::__CalcDiscSave($arDiscSave, $arDiscountApply, $arResultPrice);
							if (!$boolResultDiscSave)
							{
								return false;
							}
						}
						break;
					}
				}
			}

			if (-1 == $dblMinPrice || $dblMinPrice > $arResultPrice['PRICE'])
			{
				$dblMinPrice = $arResultPrice['PRICE'];
				$arMinPrice = $arPriceList;
				$arMinDiscounts = $arDiscountApply;
			}
		}
		if (isset($arPriceList))
			unset($arPriceList);

		if ($boolDiscountVat)
		{
			if ('N' == $arMinPrice['ORIG_VAT_INCLUDED'])
			{
				$arMinPrice['PRICE'] /= (1 + $arMinPrice['VAT_RATE']);
				$arMinPrice['VAT_INCLUDED'] = $arMinPrice['ORIG_VAT_INCLUDED'];
			}
		}
		else
		{
			if ('Y' == $arMinPrice['ORIG_VAT_INCLUDED'])
			{
				$arMinPrice['PRICE'] *= (1 + $arMinPrice['VAT_RATE']);
				$arMinPrice['VAT_INCLUDED'] = $arMinPrice['ORIG_VAT_INCLUDED'];
			}
		}
		unset($arMinPrice['ORIG_VAT_INCLUDED']);

		$dblMinPrice = roundEx($dblMinPrice, CATALOG_VALUE_PRECISION);

		$arResult = array(
			'PRICE' => $arMinPrice,
			'DISCOUNT_PRICE' => $dblMinPrice,
			'DISCOUNT' => array(),
			'DISCOUNT_LIST' => array(),
		);
		if (!empty($arMinDiscounts))
		{
			reset($arMinDiscounts);
			$arResult['DISCOUNT'] = current($arMinDiscounts);
			$arResult['DISCOUNT_LIST'] = $arMinDiscounts;
		}

		foreach (GetModuleEvents("catalog", "OnGetOptimalPriceResult", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arResult))===false)
				return false;
		}

		return $arResult;
	}

	
	/**
	 * <p>Метод приминяет к цене price в валюте currency наибольшую скидку из массива arDiscounts.</p>
	 *
	 *
	 *
	 *
	 * @param double $price  Цена.
	 *
	 *
	 *
	 * @param string $currency  Валюта цены.
	 *
	 *
	 *
	 * @param array $arDiscounts  массив ассоциативных массивов скидок. Имеет вид: <pre class="syntax">array(
	 * array( "VALUE_TYPE" =&gt; "тип скидки (P - в процентах, F - фиксированная сумма)",
	 * "VALUE" =&gt; "величина скидки", "CURRENCY" =&gt; "валюта", "MAX_DISCOUNT" =&gt;
	 * "максимальный размер скидки" ), array( "VALUE_TYPE" =&gt; "тип скидки (P - в
	 * процентах, F - фиксированная сумма)", "VALUE" =&gt; "величина скидки",
	 * "CURRENCY" =&gt; "валюта", "MAX_DISCOUNT" =&gt; "максимальный размер скидки" ), * * * )
	 * </pre>
	 *
	 *
	 *
	 * @return array <p>Метод возвращает наименьшую цену, которую можно получить с
	 * помощью наибольшей скидки.</p><a name="examples"></a>
	 *
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
	public static function CountPriceWithDiscount($price, $currency, $arDiscounts)
	{
		$mxResult = true;
		foreach (GetModuleEvents("catalog", "OnCountPriceWithDiscount", true) as $arEvent)
		{
			$mxResult = ExecuteModuleEventEx($arEvent, array($price, $currency, $arDiscounts));
			if (true !== $mxResult)
				return $mxResult;
		}

		if (strlen($currency) <= 0)
			return false;

		$price = doubleval($price);
		if ($price <= 0)
			return 0.0;

		if (!is_array($arDiscounts) || empty($arDiscounts))
			return $price;

		$arDiscSave = array();
		$arPriceDiscount = array();

		$arResultPrice = array(
			'PRICE' => $price,
			'CURRENCY' => $currency,
		);

		CCatalogProduct::__PrimaryDiscountFilter($arDiscounts, $arPriceDiscount, $arDiscSave, $arResultPrice);

		if (!empty($arPriceDiscount))
		{
			foreach ($arPriceDiscount as &$arOnePriority)
			{
				$boolResultPriority = CCatalogProduct::__CalcOnePriority($arOnePriority, $arDiscountApply, $arResultPrice);
				if (!$boolResultPriority)
				{
					return $price;
				}
				else
				{
					if (isset($arResultPrice['LAST_DISCOUNT']) && 'Y' == $arResultPrice['LAST_DISCOUNT'])
						break;
				}
			}
			if (isset($arOnePriority))
				unset($arOnePriority);
		}

		if (!empty($arDiscSave))
		{
			switch($strDiscSaveApply)
			{
			case 'R':
				$arDiscSaveResult = array(
					'PRICE' => $price,
					'CURRENCY' => $currency,
				);
				$arDiscSaveApply = array();
				$boolResultDiscSave = CCatalogProduct::__CalcDiscSave($arDiscSave, $arDiscSaveApply, $arDiscSaveResult);
				if (!$boolResultDiscSave)
				{
					return $price;
				}
				else
				{
					if ($arDiscSaveResult['PRICE'] < $arResultPrice['PRICE'])
					{
						$arResultPrice = $arDiscSaveResult;
						$arDiscountApply = $arDiscSaveApply;
					}
				}
				break;
			case 'A':
				$boolResultDiscSave = CCatalogProduct::__CalcDiscSave($arDiscSave, $arDiscountApply, $arResultPrice);
				if (!$boolResultDiscSave)
				{
					return $price;
				}
				break;
			case 'D':
				if (empty($arDiscountApply))
				{
					$boolResultDiscSave = CCatalogProduct::__CalcDiscSave($arDiscSave, $arDiscountApply, $arResultPrice);
					if (!$boolResultDiscSave)
					{
						return $price;
					}
				}
				break;
			}
		}

		$currentPrice_min = $arResultPrice['PRICE'];

		foreach (GetModuleEvents("catalog", "OnCountPriceWithDiscountResult", true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array(&$currentPrice_min))===false)
				return false;
		}

		return $currentPrice_min;
	}

	public static function GetProductSections($ID)
	{
		global $stackCacheManager;

		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		$cacheTime = CATALOG_CACHE_DEFAULT_TIME;
		if (defined("CATALOG_CACHE_TIME"))
			$cacheTime = intval(CATALOG_CACHE_TIME);

		$arProductSections = array();

		$dbElementSections = CIBlockElement::GetElementGroups($ID);
		while ($arElementSections = $dbElementSections->Fetch())
		{
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
				$dbSection = CIBlockSection::GetByID($arElementSections["ID"]);
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
						)
					);
					while ($arSectionTree = $dbSectionTree->Fetch())
					{
						if (!in_array($arSectionTree["ID"], $arProductSections))
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

	public static function OnIBlockElementDelete($ProductID)
	{
		global $DB;
		$ProductID = intval($ProductID);

		return CCatalogProduct::Delete($ProductID);
	}

	public static function OnAfterIBlockElementUpdate($arFields)
	{
		global $stackCacheManager;
		if (is_set($arFields, "IBLOCK_SECTION"))
			$stackCacheManager->Clear("catalog_element_groups");
	}

	protected function __PrimaryDiscountFilter(&$arDiscount, &$arPriceDiscount, &$arDiscSave, &$arParams)
	{
		if (isset($arParams['PRICE']) && isset($arParams['CURRENCY']))
		{
			$arParams['PRICE'] = doubleval($arParams['PRICE']);
			if (0 < $arParams['PRICE'])
			{
				$arPriceDiscount = array();
				$arDiscSave = array();

				foreach ($arDiscount as $arOneDiscount)
				{
					$dblDiscountValue = 0.0;
					if ('F' == $arOneDiscount['VALUE_TYPE'])
					{
						if ($arParams['CURRENCY'] == $arOneDiscount["CURRENCY"])
							$dblDiscountValue = $arOneDiscount["VALUE"];
						else
							$dblDiscountValue = CCurrencyRates::ConvertCurrency($arOneDiscount["VALUE"], $arOneDiscount["CURRENCY"], $arParams['CURRENCY']);
						if ($arParams['PRICE'] < $dblDiscountValue)
							continue;
						$arOneDiscount['DISCOUNT_CONVERT'] = $dblDiscountValue;
					}
					elseif ('S' == $arOneDiscount['VALUE_TYPE'])
					{
						if ($arParams['CURRENCY'] == $arOneDiscount["CURRENCY"])
							$dblDiscountValue = $arOneDiscount["VALUE"];
						else
							$dblDiscountValue = CCurrencyRates::ConvertCurrency($arOneDiscount["VALUE"], $arOneDiscount["CURRENCY"], $arParams['CURRENCY']);
						if ($arParams['PRICE'] <= $dblDiscountValue)
							continue;
						$arOneDiscount['DISCOUNT_CONVERT'] = $dblDiscountValue;
					}
					elseif ('P' == $arOneDiscount['VALUE_TYPE'])
					{
						if (100 < $arOneDiscount["VALUE"])
							continue;
						if (DISCOUNT_TYPE_STANDART == $arOneDiscount['TYPE'] && 0 < $arOneDiscount["MAX_DISCOUNT"])
						{
							if ($arParams['CURRENCY'] == $arOneDiscount["CURRENCY"])
								$dblDiscountValue = $arOneDiscount["MAX_DISCOUNT"];
							else
								$dblDiscountValue = CCurrencyRates::ConvertCurrency($arOneDiscount["MAX_DISCOUNT"], $arOneDiscount["CURRENCY"], $arParams['CURRENCY']);
							$arOneDiscount['DISCOUNT_CONVERT'] = $dblDiscountValue;
						}
					}
					if (DISCOUNT_TYPE_SAVE == $arOneDiscount['TYPE'])
					{
						$arDiscSave[] = $arOneDiscount;
					}
					else
					{
						$arPriceDiscount[intval($arOneDiscount['PRIORITY'])][] = $arOneDiscount;
					}
				}

				if (!empty($arPriceDiscount))
					krsort($arPriceDiscount);
			}
		}
	}

	protected function __CalcOnePriority(&$arDiscounts, &$arResultDiscount, &$arParams)
	{
		$boolResult = false;
		if (isset($arParams['PRICE']) && isset($arParams['CURRENCY']))
		{
			$arParams['PRICE'] = doubleval($arParams['PRICE']);
			if (0 < $arParams['PRICE'])
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
						case 'P':
							$dblTempo = $dblCurrentPrice*$arOneDiscount['VALUE']/100.0;
							if (isset($arOneDiscount['DISCOUNT_CONVERT']))
							{
								if ($dblTempo > $arOneDiscount['DISCOUNT_CONVERT'])
									$dblTempo = $arOneDiscount['DISCOUNT_CONVERT'];
							}
							$dblPriceTmp = $dblCurrentPrice - $dblTempo;
							break;
						case 'F':
							if ($arOneDiscount['DISCOUNT_CONVERT'] > $dblCurrentPrice)
							{
								$boolDelete = true;
							}
							else
							{
								$dblPriceTmp = $dblCurrentPrice - $arOneDiscount['DISCOUNT_CONVERT'];
							}
							break;
						case 'S':
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

	protected function __CalcDiscSave(&$arDiscSave, &$arResultDiscount, &$arParams)
	{
		$boolResult = false;
		if (isset($arParams['PRICE']) && isset($arParams['CURRENCY']))
		{
			$arParams['PRICE'] = doubleval($arParams['PRICE']);
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
					case 'P':
						$dblPriceTmp = $dblCurrentPrice*(1 - $arOneDiscount['VALUE']/100.0);
						break;
					case 'F':
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
?>