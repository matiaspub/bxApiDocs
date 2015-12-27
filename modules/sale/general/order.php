<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;

Loc::loadMessages(__FILE__);

$GLOBALS["SALE_ORDER"] = array();


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/index.php
 * @author Bitrix
 */
class CAllSaleOrder
{
	/**
	 * @param $siteId
	 * @param $userId
	 * @param $arShoppingCart
	 * @param $personTypeId
	 * @param $arOrderPropsValues
	 * @param $deliveryId
	 * @param $paySystemId
	 * @param $arOptions
	 * @param $arErrors
	 * @param $arWarnings
	 * @return array|null
	 */
	static function DoCalculateOrder($siteId, $userId, $arShoppingCart, $personTypeId, $arOrderPropsValues,
		$deliveryId, $paySystemId, $arOptions, &$arErrors, &$arWarnings)
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$siteId = trim($siteId);
		if (empty($siteId))
		{
			$arErrors[] = array("CODE" => "PARAM", "TEXT" => Loc::getMessage('SKGO_CALC_PARAM_ERROR'));
			return null;
		}

		$userId = intval($userId);

		if (!is_array($arShoppingCart) || (count($arShoppingCart) <= 0))
		{
			$arErrors[] = array("CODE" => "PARAM", "TEXT" => Loc::getMessage('SKGO_SHOPPING_CART_EMPTY'));
			return null;
		}

		$arOrder = static::makeOrderArray($siteId, $userId, $arShoppingCart, $arOptions);

		foreach(GetModuleEvents("sale", "OnSaleCalculateOrderShoppingCart", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arOrder));

		CSalePersonType::DoProcessOrder($arOrder, $personTypeId, $arErrors);
		if (count($arErrors) > 0)
			return null;

		foreach(GetModuleEvents("sale", "OnSaleCalculateOrderPersonType", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arOrder));

		CSaleOrderProps::DoProcessOrder($arOrder, $arOrderPropsValues, $arErrors, $arWarnings, $paySystemId, $deliveryId, $arOptions);
		if (count($arErrors) > 0)
			return null;

		foreach(GetModuleEvents("sale", "OnSaleCalculateOrderProps", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arOrder));

		CSaleDelivery::DoProcessOrder($arOrder, $deliveryId, $arErrors);
		if (count($arErrors) > 0)
			return null;

		$arOrder["PRICE_DELIVERY"] = $arOrder["DELIVERY_PRICE"];

		foreach(GetModuleEvents("sale", "OnSaleCalculateOrderDelivery", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arOrder));

		CSalePaySystem::DoProcessOrder($arOrder, $paySystemId, $arErrors);
		if (count($arErrors) > 0)
			return null;

		foreach(GetModuleEvents("sale", "OnSaleCalculateOrderPaySystem", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arOrder));

		if (!array_key_exists('CART_FIX', $arOptions) || 'Y' != $arOptions['CART_FIX'])
		{
			CSaleDiscount::DoProcessOrder($arOrder, $arOptions, $arErrors);
			if (count($arErrors) > 0)
				return null;

			foreach(GetModuleEvents("sale", "OnSaleCalculateOrderDiscount", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array(&$arOrder));

			if (isset($arOrder['ORDER_PRICE']))
			{
				$roundOrderFields = static::getRoundFields();
				foreach ($arOrder as $fieldName => $fieldValue)
				{
					if (in_array($fieldName, $roundOrderFields))
					{
						$arOrder[$fieldName] = roundEx($arOrder[$fieldName], SALE_VALUE_PRECISION);
					}
				}
			}

			if (!empty($arOrder['BASKET_ITEMS']) && is_array($arOrder['BASKET_ITEMS']))
			{
				$arOrder['ORDER_PRICE'] = 0;
				$roundBasketFields = CSaleBasket::getRoundFields();
				foreach ($arOrder['BASKET_ITEMS'] as &$basketItem)
				{
					foreach($basketItem as $fieldName => $fieldValue)
					{
						if (in_array($fieldName, $roundBasketFields))
						{
							if (isset($basketItem[$fieldName]))
							{
								$basketItem[$fieldName] = roundEx($basketItem[$fieldName], SALE_VALUE_PRECISION);
							}
						}
					}
					if (CSaleBasketHelper::isSetItem($basketItem))
						continue;

					$arOrder['ORDER_PRICE'] += CSaleBasketHelper::getFinalPrice($basketItem);
				}
			}

		}

		CSaleTax::DoProcessOrderBasket($arOrder, $arOptions, $arErrors);
		if (count($arErrors) > 0)
			return null;

		foreach(GetModuleEvents("sale", "OnSaleCalculateOrderShoppingCartTax", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arOrder));

		CSaleTax::DoProcessOrderDelivery($arOrder, $arOptions, $arErrors);
		if (count($arErrors) > 0)
			return null;

		foreach(GetModuleEvents("sale", "OnSaleCalculateOrderDeliveryTax", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arOrder));

		$arOrder["PRICE"] = $arOrder["ORDER_PRICE"] + $arOrder["DELIVERY_PRICE"] + $arOrder["TAX_PRICE"] - $arOrder["DISCOUNT_PRICE"];
		$arOrder["TAX_VALUE"] = ($arOrder["USE_VAT"] ? $arOrder["VAT_SUM"] : $arOrder["TAX_PRICE"]);

		foreach(GetModuleEvents("sale", "OnSaleCalculateOrder", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arOrder));

		$arOrder["PRICE"] = roundEx($arOrder["PRICE"], SALE_VALUE_PRECISION);
		$arOrder["TAX_VALUE"] = roundEx($arOrder["TAX_VALUE"], SALE_VALUE_PRECISION);
		return $arOrder;
	}

	/**
	 * @param $siteId
	 * @param null $userId
	 * @param $shoppingCart
	 * @param array $options
	 *
	 * @return array
	 */
	static function makeOrderArray($siteId, $userId = null, array $shoppingCart, array $options = array())
	{
		// calculate weight for set parent
		$parentWeight = array();
		foreach ($shoppingCart as $item)
		{
			if (CSaleBasketHelper::isSetItem($item))
				$parentWeight[$item["SET_PARENT_ID"]]["WEIGHT"] += $item["WEIGHT"] * $item["QUANTITY"];
		}

		foreach ($shoppingCart as &$item)
		{
			if (CSaleBasketHelper::isSetParent($item) && isset($parentWeight[$item["SET_PARENT_ID"]]))
				$item["WEIGHT"] = $parentWeight[$item["SET_PARENT_ID"]]["WEIGHT"];
		}
		unset($item);

		$currency = isset($options['CURRENCY']) && is_string($options['CURRENCY']) ? $options['CURRENCY'] : '';
		if($currency === '')
		{
			$currency = CSaleLang::GetLangCurrency($siteId);
		}

		$arOrder = array(
			"ORDER_PRICE" => 0,
			"ORDER_WEIGHT" => 0,
			"CURRENCY" => $currency,
			"WEIGHT_UNIT" => htmlspecialcharsbx(COption::GetOptionString('sale', 'weight_unit', false, $siteId)),
			"WEIGHT_KOEF" => htmlspecialcharsbx(COption::GetOptionString('sale', 'weight_koef', 1, $siteId)),
			"BASKET_ITEMS" => $shoppingCart,
			"SITE_ID" => $siteId,
			"LID" => $siteId,
			"USER_ID" => $userId,
			"USE_VAT" => false,
			"VAT_RATE" => 0,
			"VAT_SUM" => 0,
			"DELIVERY_ID" => false,
		);

		if(isset($arOptions["DELIVERY_EXTRA_SERVICES"]))
			$arOrder["DELIVERY_EXTRA_SERVICES"] = $arOptions["DELIVERY_EXTRA_SERVICES"];

		$orderPrices = CSaleOrder::CalculateOrderPrices($shoppingCart);

		$arOrder['ORDER_PRICE'] = $orderPrices['ORDER_PRICE'];
		$arOrder['ORDER_WEIGHT'] = $orderPrices['ORDER_WEIGHT'];
		$arOrder['VAT_RATE'] = $orderPrices['VAT_RATE'];
		$arOrder['VAT_SUM'] = $orderPrices['VAT_SUM'];
		$arOrder["USE_VAT"] = ($orderPrices['USE_VAT'] == "Y");

		return $arOrder;
	}
	/**
	 * calculate the cost according to the order basket
	 * @param array $arBasketItems
	 * @return array|bool
	 */
	public static function CalculateOrderPrices($arBasketItems)
	{
		if (!isset($arBasketItems) || (isset($arBasketItems) && sizeof($arBasketItems) <= 0))
			return false;

		$arResult = array(
			"ORDER_PRICE" => 0,
			"ORDER_WEIGHT" => 0,
			"VAT_RATE" => 0,
			"VAT_SUM" => 0,
			"USE_VAT" => 'N',
			"BASKET_ITEMS" => $arBasketItems,
		);

		foreach ($arResult['BASKET_ITEMS'] as &$arItem)
		{
			if (!CSaleBasketHelper::isSetItem($arItem))
			{
				if (array_key_exists('CUSTOM_PRICE', $arItem) && $arItem['CUSTOM_PRICE'] == 'Y')
				{
					$arItem['DISCOUNT_PRICE'] = $arItem['DEFAULT_PRICE'] - $arItem['PRICE'];

					if ($arItem['DISCOUNT_PRICE'] < 0)
						$arItem['DISCOUNT_PRICE'] = 0;

					if (doubleval($arItem['DEFAULT_PRICE']) > 0)
						$arItem['DISCOUNT_PRICE_PERCENT'] = $arItem['DISCOUNT_PRICE'] * 100 / $arItem['DEFAULT_PRICE'];
					else
						$arItem['DISCOUNT_PRICE_PERCENT'] = 0;

					$arItem["DISCOUNT_PRICE_PERCENT_FORMATED"] = roundEx($arItem["DISCOUNT_PRICE_PERCENT"], SALE_VALUE_PRECISION)."%";

				}

				if (isset($arItem['CURRENCY']) && strlen($arItem['CURRENCY']) > 0 )
					$arItem["PRICE_FORMATED"] = SaleFormatCurrency($arItem["PRICE"], $arItem["CURRENCY"]);

				$arResult['ORDER_PRICE'] += CSaleBasketHelper::getFinalPrice($arItem);
				$arResult['ORDER_WEIGHT'] += $arItem["WEIGHT"] * $arItem["QUANTITY"];

				if ($arItem["VAT_RATE"] > 0)
				{
					$arResult['USE_VAT'] = 'Y';

					if ($arItem["VAT_RATE"] > $arResult['VAT_RATE'])
						$arResult['VAT_RATE'] = $arItem["VAT_RATE"];

					$v = CSaleBasketHelper::getVat($arItem);
					$arItem["VAT_VALUE"] = roundEx($v / $arItem["QUANTITY"], SALE_VALUE_PRECISION);
					$arResult["VAT_SUM"] += $v;

				}
			}
		}

		$arResult['ORDER_PRICE'] = roundEx($arResult['ORDER_PRICE'], SALE_VALUE_PRECISION);
		$arResult['VAT_SUM'] = roundEx($arResult['VAT_SUM'], SALE_VALUE_PRECISION);
		unset($arItem);

		return $arResult;
	}

	// $direct == true => ID => CODE
	// $direct == false => CODE => ID
	public static function TranslateLocationPropertyValues($personTypeId, &$orderProps, $direct = true)
	{
		if(CSaleLocation::isLocationProMigrated())
		{
			// location ID to CODE
			$dbOrderProps = CSaleOrderProps::GetList(
				array("SORT" => "ASC"),
				array(
					'PERSON_TYPE_ID' => $personTypeId
				),
				false,
				false,
				array("ID", "NAME", "TYPE", "IS_LOCATION", "IS_LOCATION4TAX", "IS_PROFILE_NAME", "IS_PAYER", "IS_EMAIL", "REQUIED", "SORT", "IS_ZIP", "CODE", "MULTIPLE")
			);
			while($item = $dbOrderProps->fetch())
			{
				if($item['TYPE'] == 'LOCATION' && strlen($orderProps[$item['ID']]))
				{
					$source = $orderProps[$item['ID']];
					$replace = $direct ? CSaleLocation::getLocationCODEbyID($source) : CSaleLocation::getLocationIDbyCODE($source);

					$orderProps[$item['ID']] = $replace;
				}
			}
		}
	}

	/**
	*
	*
	*/
	
	/**
	* <p>Метод выполняет сохранение данных заказа: данных по уменьшению/увеличению количества, штрих-кодов, свойств заказа и данных по налогам. Метод статический.</p>
	*
	*
	* @param array &$arOrder  Массив данных по заказу (все параметры, атрибуты, свойства,
	* корзина). Является ссылкой на исходные параметры.
	*
	* @param array $arAdditionalFields  Массив с дополнительными параметрами заказа. Это данные, которых
	* либо нет в <b>arOrder</b>, либо их требуется заменить для изменения
	* логики работы метода.
	*
	* @param int $orderId  Идентификатор заказа (если заказ уже был создан ранее). Если не
	* задан, то метод выставляет: <pre class="syntax">"PAYED" = "N"; "CANCELED" = "N"; "STATUS_ID" =
	* "N";</pre>
	*
	* @param array &$arErrors  Массив с текстами ошибок (общий массив на весь цикл заказа).
	* Является ссылкой на исходные параметры.
	*
	* @param array $arCoupons  Массив скидочных купонов.
	*
	* @param int $arStoreBarcodeOrderFormData  Массив штрих-кодов. </h
	*
	* @param bool $bSaveBarcodes  Флаг (<i>true/false</i>) добавления новых штрих-кодов.
	*
	* @return int <p>Возвращается идентификатор заказа или <i>null</i> в случае ошибки.</p>
	* <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/dosaveorder.php
	* @author Bitrix
	*/
	public static function DoSaveOrder(&$arOrder, $arAdditionalFields, $orderId, &$arErrors, $arCoupons = array(), $arStoreBarcodeOrderFormData = array(), $bSaveBarcodes = false)
	{
		global $APPLICATION;

		$orderId = (int)$orderId;
		$isNew = ($orderId <= 0);

		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');

		$arFields = array(
			"ID" => $arOrder["ID"],
			"LID" => $arOrder["SITE_ID"],
			"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"],
			"PRICE" => $arOrder["PRICE"],
			"CURRENCY" => $arOrder["CURRENCY"],
			"USER_ID" => $arOrder["USER_ID"],
			"PAY_SYSTEM_ID" => $arOrder["PAY_SYSTEM_ID"],
			"PRICE_DELIVERY" => $arOrder["DELIVERY_PRICE"],
			"DELIVERY_ID" => (strlen($arOrder["DELIVERY_ID"]) > 0 ? $arOrder["DELIVERY_ID"] : false),
			"DISCOUNT_VALUE" => $arOrder["DISCOUNT_PRICE"],
			"TAX_VALUE" => $arOrder["TAX_VALUE"],
			"TRACKING_NUMBER" => $arOrder["TRACKING_NUMBER"]
		);

		if ($arOrder["DELIVERY_PRICE"] == $arOrder["PRICE_DELIVERY"]
			&& isset($arOrder['PRICE_DELIVERY_DIFF']) && floatval($arOrder['PRICE_DELIVERY_DIFF']) > 0)
		{
			$arFields["DELIVERY_PRICE"] = $arOrder['PRICE_DELIVERY_DIFF'] + $arOrder["PRICE_DELIVERY"];
		}

		if ($orderId <= 0)
		{
			$arFields["PAYED"] = "N";
			$arFields["CANCELED"] = "N";
			$arFields["STATUS_ID"] = "N";
		}
		$arFields = array_merge($arFields, $arAdditionalFields);

		if(!$arOrder['LOCATION_IN_CODES']) // it comes from places like crm_invoice`s Add() and tells us if we need to convert location props from ID to CODE
			static::TranslateLocationPropertyValues($arOrder["PERSON_TYPE_ID"], $arOrder["ORDER_PROP"]);
		unset($arOrder['LOCATION_IN_CODES']);


		if ($isOrderConverted == "Y")
		{
			$orderFields = array_merge($arOrder, $arFields, $arAdditionalFields);
			if (isset($orderFields['CUSTOM_DISCOUNT_PRICE']) && $orderFields['CUSTOM_DISCOUNT_PRICE'] === true)
				Sale\Compatible\DiscountCompatibility::reInit(Sale\Compatible\DiscountCompatibility::MODE_DISABLED);

			if (!empty($arStoreBarcodeOrderFormData))
			{
				$orderFields['BARCODE_LIST'] = $arStoreBarcodeOrderFormData;
			}

			$orderFields['BARCODE_SAVE'] = $bSaveBarcodes;

			if ($orderId > 0)
				$orderFields['ID'] = $orderId;

			/** @var Sale\Result $r */
			$r = Sale\Compatible\OrderCompatibility::modifyOrder(Sale\Compatible\OrderCompatibility::ORDER_COMPAT_ACTION_SAVE, $orderFields);
			if ($r->isSuccess())
			{
				$orderId = $r->getId();
			}
			else
			{
				foreach ($r->getErrorMessages() as $error)
				{
					$arErrors[] = $error;
					$APPLICATION->ThrowException($error);
				}
				return false;
			}
		}
		else
		{

			if ($orderId > 0)
			{
				$orderId = CSaleOrder::Update($orderId, $arFields);
			}
			else
			{
				if (COption::GetOptionString("sale", "product_reserve_condition", "O") == "O")
					$arFields["RESERVED"] = "Y";

				$orderId = CSaleOrder::Add($arFields);
			}

			$orderId = (int)$orderId;
			if ($orderId <= 0)
			{
				if ($ex = $APPLICATION->GetException())
					$arErrors[] = $ex->GetString();
				else
					$arErrors[] = Loc::getMessage("SOA_ERROR_ORDER");
			}

			if (!empty($arErrors))
				return null;

			CSaleBasket::DoSaveOrderBasket($orderId, $arOrder["SITE_ID"], $arOrder["USER_ID"], $arOrder["BASKET_ITEMS"], $arErrors, $arCoupons, $arStoreBarcodeOrderFormData, $bSaveBarcodes);

			CSaleTax::DoSaveOrderTax($orderId, $arOrder["TAX_LIST"], $arErrors);
			CSaleOrderProps::DoSaveOrderProps($orderId, $arOrder["PERSON_TYPE_ID"], $arOrder["ORDER_PROP"], $arErrors);
			Sale\DiscountCouponsManager::finalApply();
			Sale\DiscountCouponsManager::saveApplied();

			foreach(GetModuleEvents("sale", "OnOrderSave", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($orderId, $arFields, $arOrder, $isNew));

		}

		return $orderId;
	}

	//*************** USER PERMISSIONS *********************/
	/**
	 * @param int $ID
	 * @param bool|array $arUserGroups
	 * @param int $userID
	 * @return bool
	 */
	public static function CanUserViewOrder($ID, $arUserGroups = false, $userID = 0)
	{
		$ID = IntVal($ID);
		$userID = IntVal($userID);

		$userRights = CMain::GetUserRight("sale", $arUserGroups, "Y", "Y");
		if ($userRights >= "W")
			return True;

		$arOrder = CSaleOrder::GetByID($ID);
		if ($arOrder)
		{
			if (IntVal($arOrder["USER_ID"]) == $userID)
				return True;

			if ($userRights == "U")
			{
				$num = CSaleGroupAccessToSite::GetList(
						array(),
						array(
								"SITE_ID" => $arOrder["LID"],
								"GROUP_ID" => $arUserGroups
							),
						array()
					);

				if (IntVal($num) > 0)
				{
					$dbStatusPerms = CSaleStatus::GetPermissionsList(
						array(),
						array(
							"STATUS_ID" => $arOrder["STATUS_ID"],
							"GROUP_ID" => $arUserGroups
						),
						array("MAX" => "PERM_VIEW")
					);
					if ($arStatusPerms = $dbStatusPerms->Fetch())
					{
						if ($arStatusPerms["PERM_VIEW"] == "Y")
							return True;
					}
				}
			}
		}

		return False;
	}

	/**
	 * @param int $ID
	 * @param bool|array $arUserGroups
	 * @param bool $siteID
	 * @return bool
	 */
	public static function CanUserUpdateOrder($ID, $arUserGroups = false, $siteID = false)
	{
		$ID = IntVal($ID);

		$userRights = CMain::GetUserRight("sale", $arUserGroups, "Y", "Y");

		if ($userRights >= "W")
			return True;

		if ($userRights == "U")
		{
			if ($ID > 0)
			{
				$arOrder = CSaleOrder::GetByID($ID);
				if ($arOrder)
				{
					$num = CSaleGroupAccessToSite::GetList(
							array(),
							array(
									"SITE_ID" => $arOrder["LID"],
									"GROUP_ID" => $arUserGroups
								),
							array()
						);

					if (IntVal($num) > 0)
					{
						$dbStatusPerms = CSaleStatus::GetPermissionsList(
							array(),
							array(
								"STATUS_ID" => $arOrder["STATUS_ID"],
								"GROUP_ID" => $arUserGroups
							),
							array("MAX" => "PERM_UPDATE")
						);
						if ($arStatusPerms = $dbStatusPerms->Fetch())
							if ($arStatusPerms["PERM_UPDATE"] == "Y")
								return True;
					}
				}
			}
			else // order not created yet
			{
				if ($siteID)
				{
					$num = CSaleGroupAccessToSite::GetList(
							array(),
							array(
									"SITE_ID" => $siteID,
									"GROUP_ID" => $arUserGroups
								),
							array()
						);

					if (IntVal($num) > 0)
						return True;
				}
			}
		}

		return False;
	}

	/**
	 * @param int $ID
	 * @param bool|array $arUserGroups
	 * @param int $userID
	 * @return bool
	 */
	public static function CanUserCancelOrder($ID, $arUserGroups = false, $userID = 0)
	{
		$ID = IntVal($ID);
		$userID = IntVal($userID);

		$userRights = CMain::GetUserRight("sale", $arUserGroups, "Y", "Y");
		if ($userRights >= "W")
			return True;

		$arOrder = CSaleOrder::GetByID($ID);
		if ($arOrder)
		{
			if (IntVal($arOrder["USER_ID"]) == $userID)
				return True;

			if ($userRights == "U")
			{
				$num = CSaleGroupAccessToSite::GetList(
						array(),
						array(
								"SITE_ID" => $arOrder["LID"],
								"GROUP_ID" => $arUserGroups
							),
						array()
					);

				if (IntVal($num) > 0)
				{
					$dbStatusPerms = CSaleStatus::GetPermissionsList(
						array(),
						array(
							"STATUS_ID" => $arOrder["STATUS_ID"],
							"GROUP_ID" => $arUserGroups
						),
						array("MAX" => "PERM_CANCEL")
					);
					if ($arStatusPerms = $dbStatusPerms->Fetch())
						if ($arStatusPerms["PERM_CANCEL"] == "Y")
							return True;
				}
			}
		}

		return False;
	}

	/**
	 * @param int $ID
	 * @param bool|array $arUserGroups
	 * @param int $userID
	 * @return bool
	 */
	public static function CanUserMarkOrder($ID, $arUserGroups = false, $userID = 0)
	{
		$ID = IntVal($ID);
		$userID = IntVal($userID);

		$userRights = CMain::GetUserRight("sale", $arUserGroups, "Y", "Y");
		if ($userRights >= "W")
			return True;

		$arOrder = CSaleOrder::GetByID($ID);
		if ($arOrder)
		{
			if (IntVal($arOrder["USER_ID"]) == $userID)
				return True;

			if ($userRights == "U")
			{
				$num = CSaleGroupAccessToSite::GetList(
						array(),
						array(
								"SITE_ID" => $arOrder["LID"],
								"GROUP_ID" => $arUserGroups
							),
						array()
					);

				if (IntVal($num) > 0)
				{
					$dbStatusPerms = CSaleStatus::GetPermissionsList(
						array(),
						array(
							"STATUS_ID" => $arOrder["STATUS_ID"],
							"GROUP_ID" => $arUserGroups
						),
						array("MAX" => "PERM_MARK")
					);
					if ($arStatusPerms = $dbStatusPerms->Fetch())
						if ($arStatusPerms["PERM_MARK"] == "Y")
							return True;
				}
			}
		}

		return False;
	}

	/**
	 * @param int $ID
	 * @param string $flag
	 * @param bool|array $arUserGroups
	 * @return bool
	 */
	public static function CanUserChangeOrderFlag($ID, $flag, $arUserGroups = false)
	{
		$ID = IntVal($ID);
		$flag = trim($flag);

		$userRights = CMain::GetUserRight("sale", $arUserGroups, "Y", "Y");
		if ($userRights >= "W")
			return True;

		if ($userRights == "U")
		{
			$arOrder = CSaleOrder::GetByID($ID);
			if ($arOrder)
			{
				$num = CSaleGroupAccessToSite::GetList(
						array(),
						array(
								"SITE_ID" => $arOrder["LID"],
								"GROUP_ID" => $arUserGroups
							),
						array()
					);

				if (IntVal($num) > 0)
				{
					if ($flag == "P" || $flag == "PERM_PAYMENT")
						$fieldName = "PERM_PAYMENT";
					elseif ($flag == "PERM_DEDUCTION")
						$fieldName = "PERM_DEDUCTION";
					else
						$fieldName = "PERM_DELIVERY";

					$dbStatusPerms = CSaleStatus::GetPermissionsList(
						array(),
						array(
							"STATUS_ID" => $arOrder["STATUS_ID"],
							"GROUP_ID" => $arUserGroups
						),
						array("MAX" => $fieldName)
					);
					if ($arStatusPerms = $dbStatusPerms->Fetch())
						if ($arStatusPerms[$fieldName] == "Y")
							return True;
				}
			}
		}

		return False;
	}

	/**
	 * @param int $ID
	 * @param string $statusID
	 * @param bool|array $arUserGroups
	 * @return bool
	 */
	public static function CanUserChangeOrderStatus($ID, $statusID, $arUserGroups = false)
	{
		$ID = IntVal($ID);
		$statusID = Trim($statusID);

		$userRights = CMain::GetUserRight("sale", $arUserGroups, "Y", "Y");
		if ($userRights >= "W")
			return True;

		if ($userRights == "U")
		{
			$arOrder = CSaleOrder::GetByID($ID);
			if ($arOrder)
			{
				$num = CSaleGroupAccessToSite::GetList(
						array(),
						array(
								"SITE_ID" => $arOrder["LID"],
								"GROUP_ID" => $arUserGroups
							),
						array()
					);

				if (IntVal($num) > 0)
				{
					$dbStatusPerms = CSaleStatus::GetPermissionsList(
						array(),
						array(
							"STATUS_ID" => $arOrder["STATUS_ID"],
							"GROUP_ID" => $arUserGroups
						),
						array("MAX" => "PERM_STATUS_FROM")
					);
					if ($arStatusPerms = $dbStatusPerms->Fetch())
					{
						if ($arStatusPerms["PERM_STATUS_FROM"] == "Y")
						{
							$dbStatusPerms = CSaleStatus::GetPermissionsList(
								array(),
								array(
									"STATUS_ID" => $statusID,
									"GROUP_ID" => $arUserGroups
								),
								array("MAX" => "PERM_STATUS")
							);
							if ($arStatusPerms = $dbStatusPerms->Fetch())
								if ($arStatusPerms["PERM_STATUS"] == "Y")
									return True;
						}
					}
				}
			}
		}

		return False;
	}

	/**
	 * @param int $ID
	 * @param bool|array $arUserGroups
	 * @param int $userID
	 * @return bool
	 */
	public static function CanUserDeleteOrder($ID, $arUserGroups = false, $userID = 0)
	{
		$ID = IntVal($ID);
		$userID = IntVal($userID);

		$userRights = CMain::GetUserRight("sale", $arUserGroups, "Y", "Y");
		if ($userRights >= "W")
			return True;

		if ($userRights == "U")
		{
			$arOrder = CSaleOrder::GetByID($ID);
			if ($arOrder)
			{
				$num = CSaleGroupAccessToSite::GetList(
						array(),
						array(
								"SITE_ID" => $arOrder["LID"],
								"GROUP_ID" => $arUserGroups
							),
						array()
					);

				if (IntVal($num) > 0)
				{
					$dbStatusPerms = CSaleStatus::GetPermissionsList(
						array(),
						array(
							"STATUS_ID" => $arOrder["STATUS_ID"],
							"GROUP_ID" => $arUserGroups
						),
						array("MAX" => "PERM_DELETE")
					);
					if ($arStatusPerms = $dbStatusPerms->Fetch())
						if ($arStatusPerms["PERM_DELETE"] == "Y")
							return True;
				}
			}
		}

		return False;
	}


	//*************** ADD, UPDATE, DELETE *********************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $USER_FIELD_MANAGER, $DB, $APPLICATION;

		if (is_set($arFields, "SITE_ID") && strlen($arFields["SITE_ID"]) > 0)
			$arFields["LID"] = $arFields["SITE_ID"];

		if ((is_set($arFields, "LID") || $ACTION=="ADD") && strlen($arFields["LID"])<=0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGO_EMPTY_SITE"), "EMPTY_SITE_ID");
			return false;
		}
		if ((is_set($arFields, "PERSON_TYPE_ID") || $ACTION=="ADD") && IntVal($arFields["PERSON_TYPE_ID"])<=0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGO_EMPTY_PERS_TYPE"), "EMPTY_PERSON_TYPE_ID");
			return false;
		}
		if ((is_set($arFields, "USER_ID") || $ACTION=="ADD") && IntVal($arFields["USER_ID"])<=0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGO_EMPTY_USER_ID"), "EMPTY_USER_ID");
			return false;
		}

		if (is_set($arFields, "PAYED") && $arFields["PAYED"]!="Y")
			$arFields["PAYED"]="N";
		if (is_set($arFields, "CANCELED") && $arFields["CANCELED"]!="Y")
			$arFields["CANCELED"]="N";
		if (is_set($arFields, "STATUS_ID") && strlen($arFields["STATUS_ID"])<=0)
			$arFields["STATUS_ID"]="N";
		if (is_set($arFields, "ALLOW_DELIVERY") && $arFields["ALLOW_DELIVERY"]!="Y")
			$arFields["ALLOW_DELIVERY"]="N";
		if (is_set($arFields, "EXTERNAL_ORDER") && $arFields["EXTERNAL_ORDER"]!="Y")
			$arFields["EXTERNAL_ORDER"]="N";

		if (is_set($arFields, "PRICE") || $ACTION=="ADD")
		{
			$arFields["PRICE"] = str_replace(",", ".", $arFields["PRICE"]);
			$arFields["PRICE"] = DoubleVal($arFields["PRICE"]);
		}
		if (is_set($arFields, "PRICE_DELIVERY") || $ACTION=="ADD")
		{
			$arFields["PRICE_DELIVERY"] = str_replace(",", ".", $arFields["PRICE_DELIVERY"]);
			$arFields["PRICE_DELIVERY"] = DoubleVal($arFields["PRICE_DELIVERY"]);
		}
		if (is_set($arFields, "SUM_PAID") || $ACTION=="ADD")
		{
			$arFields["SUM_PAID"] = str_replace(",", ".", $arFields["SUM_PAID"]);
			$arFields["SUM_PAID"] = DoubleVal($arFields["SUM_PAID"]);
		}
		if (is_set($arFields, "DISCOUNT_VALUE") || $ACTION=="ADD")
		{
			$arFields["DISCOUNT_VALUE"] = str_replace(",", ".", $arFields["DISCOUNT_VALUE"]);
			$arFields["DISCOUNT_VALUE"] = DoubleVal($arFields["DISCOUNT_VALUE"]);
		}
		if (is_set($arFields, "TAX_VALUE") || $ACTION=="ADD")
		{
			$arFields["TAX_VALUE"] = str_replace(",", ".", $arFields["TAX_VALUE"]);
			$arFields["TAX_VALUE"] = DoubleVal($arFields["TAX_VALUE"]);
		}

		if(!is_set($arFields, "LOCKED_BY") && (!is_set($arFields, "UPDATED_1C") || (is_set($arFields, "UPDATED_1C") && $arFields["UPDATED_1C"] != "Y")))
		{
			$arFields["UPDATED_1C"] = "N";
			$arFields["~VERSION"] = "VERSION+0+1";
		}

		if ((is_set($arFields, "CURRENCY") || $ACTION=="ADD") && strlen($arFields["CURRENCY"])<=0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGO_EMPTY_CURRENCY"), "EMPTY_CURRENCY");
			return false;
		}

		if (is_set($arFields, "CURRENCY"))
		{
			if (!($arCurrency = CCurrency::GetByID($arFields["CURRENCY"])))
			{
				$APPLICATION->ThrowException(str_replace("#ID#", $arFields["CURRENCY"], Loc::getMessage("SKGO_WRONG_CURRENCY")), "ERROR_NO_CURRENCY");
				return false;
			}
		}

		if (is_set($arFields, "LID"))
		{
			$dbSite = CSite::GetByID($arFields["LID"]);
			if (!$dbSite->Fetch())
			{
				$APPLICATION->ThrowException(str_replace("#ID#", $arFields["LID"], Loc::getMessage("SKGO_WRONG_SITE")), "ERROR_NO_SITE");
				return false;
			}
		}

		if (is_set($arFields, "USER_ID"))
		{
			$dbUser = CUser::GetByID($arFields["USER_ID"]);
			if (!$dbUser->Fetch())
			{
				$APPLICATION->ThrowException(str_replace("#ID#", $arFields["USER_ID"], Loc::getMessage("SKGO_WRONG_USER")), "ERROR_NO_USER_ID");
				return false;
			}
		}

		if (is_set($arFields, "PERSON_TYPE_ID"))
		{
			if (!($arPersonType = CSalePersonType::GetByID($arFields["PERSON_TYPE_ID"])))
			{
				$APPLICATION->ThrowException(str_replace("#ID#", $arFields["PERSON_TYPE_ID"], Loc::getMessage("SKGO_WRONG_PERSON_TYPE")), "ERROR_NO_PERSON_TYPE");
				return false;
			}
		}

		if (is_set($arFields, "PAY_SYSTEM_ID") && IntVal($arFields["PAY_SYSTEM_ID"]) > 0)
		{
			if (!($arPaySystem = CSalePaySystem::GetByID(IntVal($arFields["PAY_SYSTEM_ID"]))))
			{
				$APPLICATION->ThrowException(str_replace("#ID#", $arFields["PAY_SYSTEM_ID"], Loc::getMessage("SKGO_WRONG_PS")), "ERROR_NO_PAY_SYSTEM");
				return false;
			}
		}

		if (is_set($arFields, "DELIVERY_ID") && IntVal($arFields["DELIVERY_ID"]) > 0)
		{
			if (!($delivery = \Bitrix\Sale\Delivery\Services\Table::getById($arFields["DELIVERY_ID"])))
			{
				$APPLICATION->ThrowException(str_replace("#ID#", $arFields["DELIVERY_ID"], Loc::getMessage("SKGO_WRONG_DELIVERY")), "ERROR_NO_DELIVERY");
				return false;
			}
		}

		if (is_set($arFields, "STATUS_ID"))
		{
			if (!($arStatus = CSaleStatus::GetByID($arFields["STATUS_ID"])))
			{
				$APPLICATION->ThrowException(str_replace("#ID#", $arFields["STATUS_ID"], Loc::getMessage("SKGO_WRONG_STATUS")), "ERROR_NO_STATUS_ID");
				return false;
			}
		}

		if (is_set($arFields, "ACCOUNT_NUMBER") && $ACTION=="UPDATE")
		{
			if (strlen($arFields["ACCOUNT_NUMBER"]) <= 0)
			{
				$APPLICATION->ThrowException(Loc::getMessage("SKGO_EMPTY_ACCOUNT_NUMBER"), "EMPTY_ACCOUNT_NUMBER");
				return false;
			}
			else
			{
				$dbres = $DB->Query("SELECT ID, ACCOUNT_NUMBER FROM b_sale_order WHERE ACCOUNT_NUMBER = '".$DB->ForSql($arFields["ACCOUNT_NUMBER"])."'", true);
				if ($arRes = $dbres->GetNext())
				{
					if (is_array($arRes) && $arRes["ID"] != $ID)
					{
						$APPLICATION->ThrowException(Loc::getMessage("SKGO_EXISTING_ACCOUNT_NUMBER"), "EXISTING_ACCOUNT_NUMBER");
						return false;
					}
				}
			}
		}

		if($ACTION == "ADD")
			$arFields["VERSION"] = 1;

		if (!$USER_FIELD_MANAGER->CheckFields("ORDER", $ID, $arFields))
		{
			return false;
		}

		return True;
	}

	public static function _Delete($ID)
	{
		global $DB, $USER_FIELD_MANAGER;

		$ID = IntVal($ID);
		$bSuccess = True;

		foreach(GetModuleEvents("sale", "OnBeforeOrderDelete", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, Array($ID))===false)
				return false;

		$DB->StartTransaction();

		if ($bSuccess)
		{
			$dbBasket = CSaleBasket::GetList(array(), array("ORDER_ID" => $ID));
			while ($arBasket = $dbBasket->Fetch())
			{
				if (CSaleBasketHelper::isSetItem($arBasket)) // set items are deleted when parent is deleted
					continue;

				$bSuccess = CSaleBasket::Delete($arBasket["ID"]);
				if (!$bSuccess)
					break;
			}
		}

		if ($bSuccess)
		{
			$dbRecurring = CSaleRecurring::GetList(array(), array("ORDER_ID" => $ID));
			while ($arRecurring = $dbRecurring->Fetch())
			{
				$bSuccess = CSaleRecurring::Delete($arRecurring["ID"]);
				if (!$bSuccess)
					break;
			}
		}

		if ($bSuccess)
			$bSuccess = CSaleOrderPropsValue::DeleteByOrder($ID);

		if ($bSuccess)
			$bSuccess = CSaleOrderTax::DeleteEx($ID);

		if($bSuccess)
			$bSuccess = CSaleUserTransact::DeleteByOrder($ID);

		if ($bSuccess)
			unset($GLOBALS["SALE_ORDER"]["SALE_ORDER_CACHE_".$ID]);

		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sale_order WHERE ID = ".$ID."", true);

		if ($bSuccess)
			$USER_FIELD_MANAGER->Delete("ORDER", $ID);

		if ($bSuccess)
			$DB->Commit();
		else
			$DB->Rollback();

		foreach(GetModuleEvents("sale", "OnOrderDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, Array($ID, $bSuccess));


		return $bSuccess;
	}

	
	/**
	* <p>Метод удаляет заказ с кодом <i>ID</i>. При этом заказ отменяется, если не был отменён, снимается флаг оплаты с возвращением денег на счет покупателя, если он был оплачен, и снимается флаг разрешения доставки, если он был установлен. Метод динамичный.</p> <p>Перед удалением заказа вызываются обработчики события <b>OnBeforeOrderDelete</b> модуля магазина, в которых можно отменить удаление вернув значение <i>false</i>. Сразу после удаления вызываются обработчики события <b>OnOrderDelete</b> модуля магазина.</p> <p></p> <div class="note"> <b>Примечание</b>. Метод использует внутреннюю транзакцию. Если у вас используется <b>MySQL</b> и <b>InnoDB</b>, и ранее была открыта транзакция, то ее необходимо закрыть до подключения метода.</div>
	*
	*
	* @param int $ID  Код заказа.
	*
	* @return bool <p>Возвращается <i>true</i> при успешном удалении и <i>false</i> - в противном
	* случае.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* CSaleOrder::Delete(23);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__delete.f9925f50.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $APPLICATION;
		$ID = (int)$ID;
		if ($ID <= 0)
			return false;

		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');

		$arOrder = CSaleOrder::GetByID($ID);
		if ($arOrder)
		{

			if ($isOrderConverted == "Y")
			{
				$errorMessage = "";

				/** @var \Bitrix\Sale\Result $r */
				$r = \Bitrix\Sale\Compatible\OrderCompatibility::delete($ID);

				$orderDeleted = (bool)$r->isSuccess();

				if (!$r->isSuccess())
				{
					foreach($r->getErrorMessages() as $error)
					{
						$errorMessage .= " ".$error;
					}

					$APPLICATION->ThrowException(Loc::getMessage("SKGO_DELETE_ERROR", array("#MESSAGE#" => $errorMessage)), "DELETE_ERROR");
				}

				return $orderDeleted;

			}
			else
			{
				if ($arOrder["CANCELED"] != "Y")
					CSaleBasket::OrderCanceled($ID, "Y"); //used only for old catalog without reservation and deduction

				if ($arOrder["ALLOW_DELIVERY"] == "Y")
					CSaleOrder::DeliverOrder($ID, "N");

				if ($arOrder["DEDUCTED"] == "Y")
				{
					CSaleOrder::DeductOrder($ID, "N");
				}

				if ($arOrder["RESERVED"] == "Y")
				{
					CSaleOrder::ReserveOrder($ID, "N");
				}


				if ($arOrder["PAYED"] != "Y")
				{
					$arOrder["SUM_PAID"] = DoubleVal($arOrder["SUM_PAID"]);
					if ($arOrder["SUM_PAID"] > 0)
					{
						if (!CSaleUserAccount::UpdateAccount($arOrder["USER_ID"], $arOrder["SUM_PAID"], $arOrder["CURRENCY"], "ORDER_CANCEL_PART", $ID))
							return False;
					}

					return CSaleOrder::_Delete($ID);
				}
				else
				{
					if (CSaleOrder::PayOrder($ID, "N", True, True))
						return CSaleOrder::_Delete($ID);
				}
			}
		}

		return false;
	}

	//*************** COMMON UTILS *********************/
	public static function GetFilterOperation($key)
	{
		$strNegative = "N";
		if (substr($key, 0, 1)=="!")
		{
			$key = substr($key, 1);
			$strNegative = "Y";
		}

		$strOrNull = "N";
		if (substr($key, 0, 1)=="+")
		{
			$key = substr($key, 1);
			$strOrNull = "Y";
		}

		if (substr($key, 0, 2)==">=")
		{
			$key = substr($key, 2);
			$strOperation = ">=";
		}
		elseif (substr($key, 0, 1)==">")
		{
			$key = substr($key, 1);
			$strOperation = ">";
		}
		elseif (substr($key, 0, 2)=="<=")
		{
			$key = substr($key, 2);
			$strOperation = "<=";
		}
		elseif (substr($key, 0, 1)=="<")
		{
			$key = substr($key, 1);
			$strOperation = "<";
		}
		elseif (substr($key, 0, 1)=="@")
		{
			$key = substr($key, 1);
			$strOperation = "IN";
		}
		elseif (substr($key, 0, 1)=="~")
		{
			$key = substr($key, 1);
			$strOperation = "LIKE";
		}
		elseif (substr($key, 0, 1)=="%")
		{
			$key = substr($key, 1);
			$strOperation = "QUERY";
		}
		else
		{
			$strOperation = "=";
		}

		return array("FIELD" => $key, "NEGATIVE" => $strNegative, "OPERATION" => $strOperation, "OR_NULL" => $strOrNull);
	}

	public static function PrepareSql(&$arFields, $arOrder, &$arFilter, $arGroupBy, $arSelectFields, $obUserFieldsSql = false, $callback = false, $arOptions = array())
	{
		global $DB;

		$arGroupByFunct = array("COUNT", "AVG", "MIN", "MAX", "SUM");

		$arAlreadyJoined = array();

		$strSqlGroupBy = '';
		$strSqlFrom = '';
		$strSqlSelect = '';
		$strSqlWhere = '';

		// GROUP BY -->
		if (is_array($arGroupBy) && count($arGroupBy) > 0)
		{
			$arSelectFields = $arGroupBy;
			foreach ($arGroupBy as $key => $val)
			{
				$val = ToUpper($val);
				$key = ToUpper($key);
				if (array_key_exists($val, $arFields) && !in_array($key, $arGroupByFunct))
				{
					if ($strSqlGroupBy != '')
						$strSqlGroupBy .= ", ";
					$strSqlGroupBy .= $arFields[$val]["FIELD"];

					if (isset($arFields[$val]["FROM"])
						&& strlen($arFields[$val]["FROM"]) > 0
						&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
					{
						if ($strSqlFrom != '')
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$val]["FROM"];
						$arAlreadyJoined[] = $arFields[$val]["FROM"];
					}
				}
			}
		}
		// <-- GROUP BY

		// SELECT -->
		$arFieldsKeys = array_keys($arFields);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSqlSelect = "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT ";
		}
		else
		{
			if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && strlen($arSelectFields)>0 && array_key_exists($arSelectFields, $arFields))
				$arSelectFields = array($arSelectFields);

			if (!isset($arSelectFields)
				|| !is_array($arSelectFields)
				|| count($arSelectFields)<=0
				|| in_array("*", $arSelectFields))
			{
				$countFieldKey = count($arFieldsKeys);
				for ($i = 0; $i < $countFieldKey; $i++)
				{
					if (isset($arFields[$arFieldsKeys[$i]]["WHERE_ONLY"])
						&& $arFields[$arFieldsKeys[$i]]["WHERE_ONLY"] == "Y")
					{
						continue;
					}

					if ($strSqlSelect != '')
						$strSqlSelect .= ", ";

					if ($arFields[$arFieldsKeys[$i]]["TYPE"] == "datetime")
					{
						if ((ToUpper($DB->type)=="ORACLE" || ToUpper($DB->type)=="MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "FULL")." as ".$arFieldsKeys[$i];
					}
					elseif ($arFields[$arFieldsKeys[$i]]["TYPE"] == "date")
					{
						if ((ToUpper($DB->type)=="ORACLE" || ToUpper($DB->type)=="MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "SHORT")." as ".$arFieldsKeys[$i];
					}
					else
						$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i];

					if (isset($arFields[$arFieldsKeys[$i]]["FROM"])
						&& strlen($arFields[$arFieldsKeys[$i]]["FROM"]) > 0
						&& !in_array($arFields[$arFieldsKeys[$i]]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$arFieldsKeys[$i]]["FROM"];
						$arAlreadyJoined[] = $arFields[$arFieldsKeys[$i]]["FROM"];
					}
				}
			}
			else
			{
				foreach ($arSelectFields as $key => $val)
				{
					$val = ToUpper($val);
					$key = ToUpper($key);
					if (array_key_exists($val, $arFields))
					{
						if (strlen($strSqlSelect) > 0)
							$strSqlSelect .= ", ";

						if (in_array($key, $arGroupByFunct))
						{
							$strSqlSelect .= $key."(".$arFields[$val]["FIELD"].") as ".$val;
						}
						else
						{
							if ($arFields[$val]["TYPE"] == "datetime")
							{
								if ((ToUpper($DB->type)=="ORACLE" || ToUpper($DB->type)=="MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL")." as ".$val;
							}
							elseif ($arFields[$val]["TYPE"] == "date")
							{
								if ((ToUpper($DB->type)=="ORACLE" || ToUpper($DB->type)=="MSSQL") && (array_key_exists($val, $arOrder)))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "SHORT")." as ".$val;
							}
							else
								$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val;
						}

						if (isset($arFields[$val]["FROM"])
							&& strlen($arFields[$val]["FROM"]) > 0
							&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
						{
							if (strlen($strSqlFrom) > 0)
								$strSqlFrom .= " ";
							$strSqlFrom .= $arFields[$val]["FROM"];
							$arAlreadyJoined[] = $arFields[$val]["FROM"];
						}
					}
				}
			}

			if (strlen($strSqlGroupBy) > 0)
			{
				if (strlen($strSqlSelect) > 0)
					$strSqlSelect .= ", ";
				$strSqlSelect .= "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT";
			}
			else
				$strSqlSelect = "%%_DISTINCT_%% ".$strSqlSelect;
		}
		// <-- SELECT

		// WHERE -->
		$arSqlSearch = Array();

		if (!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		$countFilterKey = count($filter_keys);
		for ($i = 0; $i < $countFilterKey; $i++)
		{
			$vals = $arFilter[$filter_keys[$i]];
			if (!is_array($vals))
				$vals = array($vals);
			else
				$vals = array_values($vals);

			$key = $filter_keys[$i];
			$key_res = CSaleOrder::GetFilterOperation($key);
			$key = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strOrNull = $key_res["OR_NULL"];

			if (array_key_exists($key, $arFields))
			{
				$arSqlSearch_tmp = array();
				if (count($vals) > 0)
				{
					if ($strOperation == "IN")
					{
						if (isset($arFields[$key]["WHERE"]))
						{
							$arSqlSearch_tmp1 = call_user_func_array(
									$arFields[$key]["WHERE"],
									array($vals, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], $arFields, $arFilter)
								);
							if ($arSqlSearch_tmp1 !== false)
								$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
						}
						else
						{
							if ($arFields[$key]["TYPE"] == "int")
							{
								array_walk($vals, create_function("&\$item", "\$item=IntVal(\$item);"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." IN (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "double")
							{
								array_walk($vals, create_function("&\$item", "\$item=DoubleVal(\$item);"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->ForSql(\$item).\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "(1 = 2)";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "datetime")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->CharToDateFunction(\$GLOBALS[\"DB\"]->ForSql(\$item), \"FULL\").\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "1 = 2";
								else
									$arSqlSearch_tmp[] = ($strNegative=="Y"?" NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
							elseif ($arFields[$key]["TYPE"] == "date")
							{
								array_walk($vals, create_function("&\$item", "\$item=\"'\".\$GLOBALS[\"DB\"]->CharToDateFunction(\$GLOBALS[\"DB\"]->ForSql(\$item), \"SHORT\").\"'\";"));
								$vals = array_unique($vals);
								$val = implode(",", $vals);

								if (count($vals) <= 0)
									$arSqlSearch_tmp[] = "1 = 2";
								else
									$arSqlSearch_tmp[] = ($strNegative=="Y"?" NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." (".$val."))";
							}
						}
					}
					else
					{
						$countVals = count($vals);
						for ($j = 0; $j < $countVals; $j++)
						{
							$val = $vals[$j];

							if (isset($arFields[$key]["WHERE"]))
							{
								$arSqlSearch_tmp1 = call_user_func_array(
										$arFields[$key]["WHERE"],
										array($val, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], $arFields, $arFilter)
									);
								if ($arSqlSearch_tmp1 !== false)
									$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
							}
							else
							{
								if ($arFields[$key]["TYPE"] == "int")
								{
									if ((IntVal($val) == 0) && (strpos($strOperation, "=") !== False))
										$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
									else
										$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".IntVal($val)." )";
								}
								elseif ($arFields[$key]["TYPE"] == "double")
								{
									$val = str_replace(",", ".", $val);

									if ((DoubleVal($val) == 0) && (strpos($strOperation, "=") !== False))
										$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
									else
										$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".DoubleVal($val)." )";
								}
								elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
								{
									if ($strOperation == "QUERY")
									{
										$arSqlSearch_tmp[] = GetFilterQuery($arFields[$key]["FIELD"], $val, "Y");
									}
									else
									{
										if ((strlen($val) == 0) && (strpos($strOperation, "=") !== False))
											$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$DB->Length($arFields[$key]["FIELD"])." <= 0) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
										else
											$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
									}
								}
								elseif ($arFields[$key]["TYPE"] == "datetime")
								{
									if (strlen($val) <= 0)
										$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
									else
										$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
								}
								elseif ($arFields[$key]["TYPE"] == "date")
								{
									if (strlen($val) <= 0)
										$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
									else
										$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "SHORT").")";
								}
							}
						}
					}
				}

				if (isset($arFields[$key]["FROM"])
					&& strlen($arFields[$key]["FROM"]) > 0
					&& !in_array($arFields[$key]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$key]["FROM"];
					$arAlreadyJoined[] = $arFields[$key]["FROM"];
				}

				$strSqlSearch_tmp = "";
				$countSqlSearch = count($arSqlSearch_tmp);
				for ($j = 0; $j < $countSqlSearch; $j++)
				{
					if ($j > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arSqlSearch_tmp[$j].")";
				}
				if ($strOrNull == "Y")
				{
					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." IS ".($strNegative=="Y" ? "NOT " : "")."NULL)";

					if ($arFields[$key]["TYPE"] == "int" || $arFields[$key]["TYPE"] == "double")
					{
						if (strlen($strSqlSearch_tmp) > 0)
							$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." 0)";
					}
					elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
					{
						if (strlen($strSqlSearch_tmp) > 0)
							$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." '')";
					}
				}

				if ($strSqlSearch_tmp != "")
					$arSqlSearch[] = "(".$strSqlSearch_tmp.")";
			}
		}

		// custom subquery callback
		if (is_callable($callback))
		{
			$arSqlSearch[] = call_user_func_array($callback, array($arFields));
		}

		$countSqlSearch = count($arSqlSearch);
		for ($i = 0; $i < $countSqlSearch; $i++)
		{
			if ($strSqlWhere != '')
				$strSqlWhere .= " AND ";
			$strSqlWhere .= "(".$arSqlSearch[$i].")";
		}

		// <-- WHERE

		// ORDER BY -->
		$arSqlOrder = Array();
		if (!is_array($arOrder))
			$arOrder = array();
		foreach ($arOrder as $by => $order)
		{
			$by = ToUpper($by);
			$order = ToUpper($order);

			if ($order != "ASC")
				$order = "DESC";
			else
				$order = "ASC";

			if (is_array($arGroupBy) && count($arGroupBy) > 0 && in_array($by, $arGroupBy))
			{
				$arSqlOrder[] = " ".$by." ".$order." ";
			}
			elseif (array_key_exists($by, $arFields))
			{
				$arSqlOrder[] = " ".$arFields[$by]["FIELD"]." ".$order." ";

				if (isset($arFields[$by]["FROM"])
					&& strlen($arFields[$by]["FROM"]) > 0
					&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$by]["FROM"];
					$arAlreadyJoined[] = $arFields[$by]["FROM"];
				}
			}
			elseif ($obUserFieldsSql)
			{
				$arSqlOrder[] = " ".$obUserFieldsSql->GetOrder($by)." ".$order." ";
			}
		}

		$nullsLast = isset($arOptions['NULLS_LAST']) ? (bool)$arOptions['NULLS_LAST'] : false;
		$strSqlOrderBy = "";
		DelDuplicateSort($arSqlOrder);
		$countSqlOrder = count($arSqlOrder);
		for ($i=0; $i < $countSqlOrder; $i++)
		{
			if (strlen($strSqlOrderBy) > 0)
				$strSqlOrderBy .= ", ";

			$order = (substr($arSqlOrder[$i], -3)=="ASC") ? "ASC" : "DESC";
			if (!$nullsLast)
			{
				if(ToUpper($DB->type)=="ORACLE")
				{
					if($order === "ASC")
						$strSqlOrderBy .= $arSqlOrder[$i]." NULLS FIRST";
					else
						$strSqlOrderBy .= $arSqlOrder[$i]." NULLS LAST";
				}
				else
					$strSqlOrderBy .= $arSqlOrder[$i];
			}
			else
			{
				$field = substr($arSqlOrder[$i], 0, -strlen($order)-1);
				if(ToUpper($DB->type) === "MYSQL")
				{
					if($order === 'ASC')
						$strSqlOrderBy .= '(CASE WHEN ISNULL('.$field.') THEN 1 ELSE 0 END) '.$order.', '.$field." ".$order;
					else
						$strSqlOrderBy .= $field." ".$order;
				}
				elseif(ToUpper($DB->type) === "MSSQL")
				{
					if($order === 'ASC')
						$strSqlOrderBy .= '(CASE WHEN '.$field.' IS NULL THEN 1 ELSE 0 END) '.$order.', '.$field." ".$order;
					else
						$strSqlOrderBy .= $field." ".$order;

				}
				elseif(ToUpper($DB->type) === "ORACLE")
				{
					if($order === 'DESC')
						$strSqlOrderBy .= $field." ".$order." NULLS LAST";
					else
						$strSqlOrderBy .= $field." ".$order;
				}
			}
		}
		// <-- ORDER BY

		return array(
			"SELECT" => $strSqlSelect,
			"FROM" => $strSqlFrom,
			"WHERE" => $strSqlWhere,
			"GROUPBY" => $strSqlGroupBy,
			"ORDERBY" => $strSqlOrderBy
		);
	}


	//*************** SELECT *********************/
	
	/**
	* <p>Метод возвращает параметры заказа с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код заказа.
	*
	* @return array <p>Возвращается ассоциативный массив с ключами:</p> <table class="tnormal"
	* width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	* заказа.</td> </tr> <tr> <td>LID</td> <td>Код сайта, на котором сделан заказ.</td>
	* </tr> <tr> <td>PERSON_TYPE_ID</td> <td>Тип плательщика, к которому принадлежит
	* посетитель, сделавший заказ (заказчик)</td> </tr> <tr> <td>PAYED</td> <td>Флаг (Y/N)
	* оплачен ли заказ.</td> </tr> <tr> <td>DATE_PAYED</td> <td>Дата оплаты заказа.</td> </tr>
	* <tr> <td>EMP_PAYED_ID</td> <td>Код пользователя (сотрудника магазина), который
	* установил флаг оплаченности.</td> </tr> <tr> <td>CANCELED</td> <td>Флаг (Y/N)
	* отменён ли заказ.</td> </tr> <tr> <td>DATE_CANCELED</td> <td>Дата отмены заказа.</td>
	* </tr> <tr> <td>EMP_CANCELED_ID</td> <td>Код пользователя, который установил флаг
	* отмены заказа.</td> </tr> <tr> <td>RESPONSIBLE_ID</td> <td>ID ответственного лица.</td>
	* </tr> <tr> <td>REASON_CANCELED</td> <td>Текстовое описание причины отмены
	* заказа.</td> </tr> <tr> <td>STATUS_ID</td> <td>Код статуса заказа.</td> </tr> <tr>
	* <td>DATE_STATUS</td> <td>Дата изменения статуса заказа.</td> </tr> <tr> <td>EMP_STATUS_ID</td>
	* <td>Код пользователя (сотрудника магазина), который установил
	* текущий статус заказа.</td> </tr> <tr> <td>PRICE_DELIVERY</td> <td>Стоимость
	* доставки заказа.</td> </tr> <tr> <td>ALLOW_DELIVERY</td> <td>Флаг (Y/N) разрешена ли
	* доставка (отгрузка) заказа.</td> </tr> <tr> <td>DATE_ALLOW_DELIVERY</td> <td>Дата, когда
	* была разрешена доставка заказа.</td> </tr> <tr> <td>EMP_ALLOW_DELIVERY_ID</td> <td>Код
	* пользователя (сотрудника магазина), который разрешил доставку
	* заказа.</td> </tr> <tr> <td>PRICE</td> <td>Общая стоимость заказа.</td> </tr> <tr>
	* <td>CURRENCY</td> <td>Валюта стоимости заказа.</td> </tr> <tr> <td>DISCOUNT_VALUE</td>
	* <td>Общая величина скидки.</td> </tr> <tr> <td>USER_ID</td> <td>Код пользователя
	* заказчика.</td> </tr> <tr> <td>PAY_SYSTEM_ID</td> <td>Платежная система, которой
	* (будет) оплачен заказа.</td> </tr> <tr> <td>DELIVERY_ID</td> <td>Способ (служба)
	* доставки заказа.</td> </tr> <tr> <td>DATE_INSERT</td> <td>Дата добавления заказа.</td>
	* </tr> <tr> <td>DATE_UPDATE</td> <td>Дата последнего изменения заказа.</td> </tr> <tr>
	* <td>USER_DESCRIPTION</td> <td>Описание заказа заказчиком.</td> </tr> <tr>
	* <td>ADDITIONAL_INFO</td> <td>Дополнительная информация по заказу.</td> </tr> <tr>
	* <td>PS_STATUS</td> <td>Флаг (Y/N) статуса платежной системы - успешно ли
	* оплачен заказ (для платежных систем, которые позволяют
	* автоматически получать данные по проведенным через них
	* заказам)</td> </tr> <tr> <td>PS_STATUS_CODE</td> <td>Код статуса платежной системы
	* (значение зависит от системы)</td> </tr> <tr> <td>PS_STATUS_DESCRIPTION</td>
	* <td>Описание результата работы платежной системы.</td> </tr> <tr>
	* <td>PS_STATUS_MESSAGE</td> <td>Сообщение от платежной системы.</td> </tr> <tr>
	* <td>PS_SUM</td> <td>Сумма, которая была реально оплачена через платежную
	* систему.</td> </tr> <tr> <td>PS_CURRENCY</td> <td>Валюта суммы.</td> </tr> <tr>
	* <td>PS_RESPONSE_DATE</td> <td>Дата получения статуса платежной системы.</td> </tr>
	* <tr> <td>COMMENTS</td> <td>Произвольные комментарии.</td> </tr> <tr> <td>TAX_VALUE</td>
	* <td>Общая сумма налогов.</td> </tr> <tr> <td>STAT_GID</td> <td>Параметр события в
	* статистике.</td> </tr> <tr> <td>SUM_PAID</td> <td>Сумма, которая уже была оплачена
	* покупателем по данному счету (например, с внутреннего счета).</td>
	* </tr> <tr> <td>PAY_VOUCHER_NUM</td> <td>Номер платежного поручения.</td> </tr> <tr>
	* <td>PAY_VOUCHER_DATE</td> <td>Дата платежного поручения.</td> </tr> </table> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (!($arOrder = CSaleOrder::GetByID($ORDER_ID)))
	* {
	*    echo "Заказ с кодом ".$ORDER_ID." не найден";
	* }
	* else
	* {
	*    echo "&lt;pre&gt;";
	*    print_r($arOrder);
	*    echo "&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__getbyid.5cbe0078.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		if (isset($GLOBALS["SALE_ORDER"]["SALE_ORDER_CACHE_".$ID]) && is_array($GLOBALS["SALE_ORDER"]["SALE_ORDER_CACHE_".$ID]) && is_set($GLOBALS["SALE_ORDER"]["SALE_ORDER_CACHE_".$ID], "ID"))
		{
			return $GLOBALS["SALE_ORDER"]["SALE_ORDER_CACHE_".$ID];
		}
		else
		{
			$isOrderConverted = (\Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N') == 'Y');

			if ($isOrderConverted == "Y")
			{
				$db_res = \Bitrix\Sale\Compatible\OrderCompatibility::getList(array("ID" => "DESC"), array('ID' => $ID), false, false, array('*'));
				$db_res->addFetchAdapter(new \Bitrix\Sale\Compatible\OrderFetchAdapter());
			}
			else
			{
				$strSql =
					"SELECT O.*, ".
					"	".$DB->DateToCharFunction("O.DATE_STATUS", "FULL")." as DATE_STATUS_FORMAT, ".
					"	".$DB->DateToCharFunction("O.DATE_INSERT", "SHORT")." as DATE_INSERT_FORMAT, ".
					"	".$DB->DateToCharFunction("O.DATE_UPDATE", "FULL")." as DATE_UPDATE_FORMAT, ".
					"	".$DB->DateToCharFunction("O.DATE_LOCK", "FULL")." as DATE_LOCK_FORMAT ".
					"FROM b_sale_order O ".
					"WHERE O.ID = ".$ID."";

				$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}

			if ($res = $db_res->Fetch())
			{
				$dataKeys = array_keys($res);
				foreach ($dataKeys as $key)
				{
					if (strpos($key, '~') === 0)
						continue;

					if (!empty($res['~'.$key])
						&& (($res['~'.$key] instanceof \Bitrix\Main\Type\DateTime)
							|| ($res['~'.$key] instanceof \Bitrix\Main\Type\Date)))
					{
						/** @var \Bitrix\Main\Type\Date|\Bitrix\Main\Type\DateTime $dateObject */
						$dateObject = $res['~'.$key];

						if ($key == "DATE_STATUS")
						{
							$res['DATE_STATUS_FORMAT'] = Sale\Compatible\OrderCompatibility::convertDateFieldToFormat($dateObject, FORMAT_DATETIME);
						}
						elseif ($key == "DATE_UPDATE")
						{
							$res['DATE_UPDATE_FORMAT'] = Sale\Compatible\OrderCompatibility::convertDateFieldToFormat($dateObject, FORMAT_DATETIME);
						}
						elseif ($key == "DATE_LOCK")
						{
							$res['DATE_LOCK_FORMAT'] = Sale\Compatible\OrderCompatibility::convertDateFieldToFormat($dateObject, FORMAT_DATETIME);
						}

						$res[$key] = $dateObject->format('Y-m-d H:i:s');
						unset($res['~'.$key]);

					}
				}
				$GLOBALS["SALE_ORDER"]["SALE_ORDER_CACHE_".$ID] = $res;
				return $res;
			}
		}

		return False;
	}


	//*************** EVENTS *********************/
	public static function OnBeforeCurrencyDelete($currency)
	{
		global $APPLICATION;
		if (strlen($currency)<=0) return false;

		$dbOrders = CSaleOrder::GetList(array(), array("CURRENCY" => $currency), false, array("nTopCount" => 1), array("ID", "CURRENCY"));
		if ($arOrders = $dbOrders->Fetch())
		{
			$APPLICATION->ThrowException(str_replace("#CURRENCY#", $currency, Loc::getMessage("SKGO_ERROR_ORDERS_CURRENCY")), "ERROR_ORDERS_CURRENCY");
			return False;
		}

		return True;
	}

	public static function OnBeforeUserDelete($userID)
	{
		global $APPLICATION;

		$userID = IntVal($userID);
		if ($userID <= 0)
		{
			$APPLICATION->ThrowException("Empty user ID", "EMPTY_USER_ID");
			return false;
		}

		$dbOrders = CSaleOrder::GetList(array(), array("USER_ID" => $userID), false, array("nTopCount" => 1), array("ID", "USER_ID"));
		if ($arOrders = $dbOrders->Fetch())
		{
			$APPLICATION->ThrowException(str_replace("#USER_ID#", $userID, Loc::getMessage("SKGO_ERROR_ORDERS")), "ERROR_ORDERS");
			return False;
		}

		return True;
	}

	//*************** ACTIONS *********************/
	
	/**
	* <p>Метод меняет значение флага "заказ оплачен" (поле <b>PAYED</b>) на значение параметра <i> Val </i> для заказа с кодом <i>ID</i>. Кроме флага оплаты заказа, устанавливаются также поля даты изменения значения флага (<b>DATE_PAYED</b>) и кода пользователя, изменившего значение флага (<b>EMP_PAYED_ID</b>). Метод динамичный.</p> <p>Перед изменением флага вызываются обработчики события OnSaleBeforePayOrder модуля магазина, в которых можно отменить изменение флага вернув значение <i>false</i>. После изменения флага вызываются обработчики события OnSalePayOrder модуля магазина.</p> <p>В случае установки флага генерируется почтовое событие типа <b>SALE_ORDER_PAID</b>.</p>
	*
	*
	* @param int $ID  Код заказа.
	*
	* @param string $val  Новое значение флага оплаты заказа (Y/N).
	*
	* @param  $bool  Значение <i>true </i>отражает изменение флага на внутреннем счете
	* пользователя. Значение <i>false</i> изменяет только флаг, не
	* затрагивая счет.
	*
	* @param bWithdra $w = True[ Если параметр <b> bWithdraw </b> установлен в <i>true</i>, то установка
	* параметра <b> bPay </b> в <i>true</i> приведет к тому, что необходимая сумма
	* денег будет внесена на счет покупателя перед оплатой, а установка
	* в <i>false</i> приведет к тому, что оплата будет происходить целиком с
	* внутреннего счета.<br><br> Если параметр bWithdraw установлен в <i>false</i>,
	* то операции со счетом не производятся и значение параметра bPay не
	* играет роли.
	*
	* @param bool $bPay = True[ Должен быть равен 0. </ht
	*
	* @param int $recurringID = 0[ Массив дополнительно обновляемых параметров (обычно это номер и
	* дата платежного поручения).
	*
	* @param array $arAdditionalFields = array()]]]] 
	*
	* @return int <p>Возвращается код заказа или <i>false</i> в случае ошибки. Повторная
	* оплата заказа невозможна.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (!CSaleOrder::PayOrder($ID, "Y", True, True, 0, array("PAY_VOUCHER_NUM" =&gt; "19210")))
	* {
	*    echo "Ошибка обновления заказа";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__payorder.88101c0f.php
	* @author Bitrix
	*/
	public static function PayOrder($ID, $val, $bWithdraw = True, $bPay = True, $recurringID = 0, $arAdditionalFields = array())
	{
		global $DB, $USER, $APPLICATION;

		$ID = IntVal($ID);
		$val = (($val != "Y") ? "N" : "Y");
		$bWithdraw = ($bWithdraw ? True : False);
		$bPay = ($bPay ? True : False);
		$recurringID = IntVal($recurringID);

		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');


		$NO_CHANGE_STATUS = "N";
		if (is_set($arAdditionalFields["NOT_CHANGE_STATUS"]) && $arAdditionalFields["NOT_CHANGE_STATUS"] == "Y")
		{
			$NO_CHANGE_STATUS = "Y";
			unset($arAdditionalFields["NOT_CHANGE_STATUS"]);
		}

		if ($ID <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGO_NO_ORDER_ID"), "NO_ORDER_ID");
			return False;
		}

		$arOrder = CSaleOrder::GetByID($ID);
		if (!$arOrder)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $ID, Loc::getMessage("SKGO_NO_ORDER")), "NO_ORDER");
			return False;
		}

		if ($arOrder["PAYED"] == $val)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $ID, Loc::getMessage("SKGO_DUB_PAY")), "ALREADY_FLAG");
			return False;
		}

		foreach(GetModuleEvents("sale", "OnSaleBeforePayOrder", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, Array($ID, $val, $bWithdraw, $bPay, $recurringID, $arAdditionalFields))===false)
				return false;

		if ($isOrderConverted != "Y" && $bWithdraw)
		{
			if ($val == "Y")
			{
				$needPaySum = DoubleVal($arOrder["PRICE"]) - DoubleVal($arOrder["SUM_PAID"]);

				if ($bPay)
					if (!CSaleUserAccount::UpdateAccount($arOrder["USER_ID"], $needPaySum, $arOrder["CURRENCY"], "OUT_CHARGE_OFF", $ID))
						return False;

				if ($needPaySum > 0 && !CSaleUserAccount::Pay($arOrder["USER_ID"], $needPaySum, $arOrder["CURRENCY"], $ID, False))
					return False;
			}
			else
			{
				if (!CSaleUserAccount::UpdateAccount($arOrder["USER_ID"], $arOrder["PRICE"], $arOrder["CURRENCY"], "ORDER_UNPAY", $ID))
					return False;
			}
		}


			$arFields = array(
				"PAYED" => $val,
				"=DATE_PAYED" => $DB->GetNowFunction(),
				"EMP_PAYED_ID" => ( IntVal($USER->GetID())>0 ? IntVal($USER->GetID()) : false ),
				"SUM_PAID" => 0
			);
			if (count($arAdditionalFields) > 0)
			{
				foreach ($arAdditionalFields as $addKey => $addValue)
				{
					if (!array_key_exists($addKey, $arFields))
						$arFields[$addKey] = $addValue;
				}
			}

		if ($isOrderConverted == "Y")
		{
			$errorMessage = "";
			/** @var \Bitrix\Sale\Result $r */
			$r = \Bitrix\Sale\Compatible\OrderCompatibility::pay($ID, $arFields, $bWithdraw, $bPay);

			if (!$r->isSuccess(true))
			{
				foreach($r->getErrorMessages() as $error)
				{
					$errorMessage .= " ".$error;
				}

				$APPLICATION->ThrowException(Loc::getMessage("SKGB_PAY_ERROR", array("#MESSAGE#" => $errorMessage)), "RESERVATION_ERROR");
				return false;
			}

			$res = true;
		}
		else
		{
			$res = CSaleOrder::Update($ID, $arFields);
			foreach(GetModuleEvents("sale", "OnSalePayOrder", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array($ID, $val));
		}

		if ($val == "Y")
		{
			CTimeZone::Disable();
			$arOrder = CSaleOrder::GetByID($ID);
			CTimeZone::Enable();

			if ($NO_CHANGE_STATUS != "Y")
			{
				$orderStatus = COption::GetOptionString("sale", "status_on_paid", "");
				if(strlen($orderStatus) > 0 && $orderStatus != $arOrder["STATUS_ID"])
				{
					$dbStatus = CSaleStatus::GetList(Array("SORT" => "ASC"), Array("LID" => LANGUAGE_ID));
					while ($arStatus = $dbStatus->GetNext())
					{
						$arStatuses[$arStatus["ID"]] = $arStatus["SORT"];
					}

					if($arStatuses[$orderStatus] >= $arStatuses[$arOrder["STATUS_ID"]])
						CSaleOrder::StatusOrder($ID, $orderStatus);
				}
			}

			$userEMail = "";
			$dbOrderProp = CSaleOrderPropsValue::GetList(Array(), Array("ORDER_ID" => $arOrder["ID"], "PROP_IS_EMAIL" => "Y"));
			if($arOrderProp = $dbOrderProp->Fetch())
				$userEMail = $arOrderProp["VALUE"];

			if(strlen($userEMail) <= 0)
			{
				$dbUser = CUser::GetByID($arOrder["USER_ID"]);
				if($arUser = $dbUser->Fetch())
					$userEMail = $arUser["EMAIL"];
			}

			$arFields = Array(
				"ORDER_ID" => $arOrder["ACCOUNT_NUMBER"],
				"ORDER_DATE" => $arOrder["DATE_INSERT_FORMAT"],
				"EMAIL" => $userEMail,
				"SALE_EMAIL" => COption::GetOptionString("sale", "order_email", "order@".$_SERVER["SERVER_NAME"])
			);
			$eventName = "SALE_ORDER_PAID";

			$bSend = true;
			foreach(GetModuleEvents("sale", "OnOrderPaySendEmail", true) as $arEvent)
			{
				if (ExecuteModuleEventEx($arEvent, Array($ID, &$eventName, &$arFields))===false)
					$bSend = false;
			}

			if($bSend)
			{
				$event = new CEvent;
				$event->Send($eventName, $arOrder["LID"], $arFields, "N");
			}

			CSaleMobileOrderPush::send("ORDER_PAYED", array("ORDER" => $arOrder));

			if (CModule::IncludeModule("statistic"))
			{
				CStatEvent::AddByEvents("eStore", "order_paid", $ID, "", $arOrder["STAT_GID"], $arOrder["PRICE"], $arOrder["CURRENCY"]);
			}
		}
		else
		{
			if (CModule::IncludeModule("statistic"))
			{
				CStatEvent::AddByEvents("eStore", "order_chargeback", $ID, "", $arOrder["STAT_GID"], $arOrder["PRICE"], $arOrder["CURRENCY"], "Y");
			}
		}

		if ($isOrderConverted != "Y")
		{
			//reservation
			if (COption::GetOptionString("sale", "product_reserve_condition", "O") == "P" && $arOrder["RESERVED"] != $val)
			{
				if (!CSaleOrder::ReserveOrder($ID, $val))
					return false;
			}

			if ($val == "Y")
			{
				$allowDelivery = COption::GetOptionString("sale", "status_on_payed_2_allow_delivery", "");
				if($allowDelivery == "Y" && $arOrder["ALLOW_DELIVERY"] == "N")
				{
					CSaleOrder::DeliverOrder($ID, "Y");
				}
			}
		}

		return $res;
	}

	
	/**
	* <p>Метод меняет значение флага "доставка разрешена" (поле <b>ALLOW_DELIVERY</b>) на значение параметра <i>val</i> для заказа с кодом <i>ID</i>. Метод динамичный.</p> <p>Кроме флага разрешения доставки, устанавливаются также поля даты изменения значения флага (<b>DATE_ALLOW_DELIVERY</b>) и кода пользователя, изменившего значение флага (<b>EMP_ALLOW_DELIVERY_ID</b>).</p> <p>При изменении флага "Доставка разрешена" для каждого элемента заказа (товара) вызывается функция обратного вызова из поля <b> PAY_CALLBACK_FUNC </b> корзины (если она установлена).</p> <p>Перед изменением флага вызываются обработчики события OnSaleBeforeDeliveryOrder модуля магазина, в которых можно отменить изменение флага вернув значение <i>false</i>. После изменения флага вызываются обработчики события OnSaleDeliveryOrder модуля магазина.</p> <p>В случае разрешения доставки генерируется почтовое событие типа <b>SALE_ORDER_DELIVERY</b>.</p>
	*
	*
	* @param int $ID  Код заказа.
	*
	* @param string $val  Новое значение флага р<span class="style1">азрешения доставки </span>(Y/N).
	*
	* @param  $int  Код продления подписки (если он есть).
	*
	* @param recurringI $D = 0[ Массив дополнительно обновляемых параметров (обычно это номер и
	* дата платежного поручения).
	*
	* @param array $arAdditionalFields = array()]] 
	*
	* @return int <p>Возвращается код заказа или <i>false</i> в случае ошибки.</p> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (!CSaleOrder::DeliverOrder(23, "Y"))
	*    echo "Ошибка изменения заказа 23";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__deliverorder.fd0e66d5.php
	* @author Bitrix
	*/
	public static function DeliverOrder($ID, $val, $recurringID = 0, $arAdditionalFields = array())
	{
		global $DB, $USER, $APPLICATION;

		$ID = IntVal($ID);
		$val = (($val != "Y") ? "N" : "Y");
		$recurringID = IntVal($recurringID);

		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');

		$NO_CHANGE_STATUS = "N";
		if (is_set($arAdditionalFields["NOT_CHANGE_STATUS"]) && $arAdditionalFields["NOT_CHANGE_STATUS"] == "Y")
		{
			$NO_CHANGE_STATUS = "Y";
			unset($arAdditionalFields["NOT_CHANGE_STATUS"]);
		}

		if ($ID <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGO_NO_ORDER_ID"), "NO_ORDER_ID");
			return False;
		}

		$arOrder = CSaleOrder::GetByID($ID);
		if (!$arOrder)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $ID, Loc::getMessage("SKGO_NO_ORDER")), "NO_ORDER");
			return False;
		}

		if ($arOrder["ALLOW_DELIVERY"] == $val)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $ID, Loc::getMessage("SKGO_DUB_DELIVERY")), "ALREADY_FLAG");
			return False;
		}

		foreach(GetModuleEvents("sale", "OnSaleBeforeDeliveryOrder", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, Array($ID, $val, $recurringID, $arAdditionalFields))===false)
				return false;

		if ($isOrderConverted != "Y")
		{
			$arFields = array(
				"ALLOW_DELIVERY" => $val,
				"=DATE_ALLOW_DELIVERY" => $DB->GetNowFunction(),
				"EMP_ALLOW_DELIVERY_ID" => ( IntVal($USER->GetID())>0 ? IntVal($USER->GetID()) : false )
			);
			if (count($arAdditionalFields) > 0)
			{
				foreach ($arAdditionalFields as $addKey => $addValue)
				{
					if (!array_key_exists($addKey, $arFields))
						$arFields[$addKey] = $addValue;
				}
			}
			$res = CSaleOrder::Update($ID, $arFields);
		}
		else
		{
			$errorMessage = '';

			/** @var \Bitrix\Sale\Result $r */
			$r = \Bitrix\Sale\Compatible\OrderCompatibility::allowDelivery($ID, ($val == "Y" ? true : false));
			if (!$r->isSuccess(true))
			{
				foreach($r->getErrorMessages() as $error)
				{
					$errorMessage .= " ".$error;
				}

				$APPLICATION->ThrowException(Loc::getMessage("SKGO_DELIVER_ERROR", array("#MESSAGE#" => $errorMessage)), "DELIVER_ERROR");
				return false;
			}

			$res = true;
		}

		unset($GLOBALS["SALE_ORDER"]["SALE_ORDER_CACHE_".$ID]);

		if ($recurringID <= 0)
		{
			if (IntVal($arOrder["RECURRING_ID"]) > 0)
				$recurringID = IntVal($arOrder["RECURRING_ID"]);
		}

		CSaleBasket::OrderDelivery($ID, (($val=="Y") ? True : False), $recurringID);

		if ($isOrderConverted != "Y")
		{
			foreach(GetModuleEvents("sale", "OnSaleDeliveryOrder", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array($ID, $val));
		}

		if ($val == "Y")
		{
			CTimeZone::Disable();
			$arOrder = CSaleOrder::GetByID($ID);
			CTimeZone::Enable();

			if ($NO_CHANGE_STATUS != "Y")
			{
				$orderStatus = COption::GetOptionString("sale", "status_on_allow_delivery", "");
				if(strlen($orderStatus) > 0 && $orderStatus != $arOrder["STATUS_ID"])
				{
					$dbStatus = CSaleStatus::GetList(Array("SORT" => "ASC"), Array("LID" => LANGUAGE_ID), false, false, Array("ID", "SORT"));
					while ($arStatus = $dbStatus->GetNext())
					{
						$arStatuses[$arStatus["ID"]] = $arStatus["SORT"];
					}

					if($arStatuses[$orderStatus] >= $arStatuses[$arOrder["STATUS_ID"]])
						CSaleOrder::StatusOrder($ID, $orderStatus);
				}
			}

			$userEMail = "";
			$dbOrderProp = CSaleOrderPropsValue::GetList(Array(), Array("ORDER_ID" => $arOrder["ID"], "PROP_IS_EMAIL" => "Y"));
			if($arOrderProp = $dbOrderProp->Fetch())
				$userEMail = $arOrderProp["VALUE"];

			if(strlen($userEMail) <= 0)
			{
				$dbUser = CUser::GetByID($arOrder["USER_ID"]);
				if($arUser = $dbUser->Fetch())
					$userEMail = $arUser["EMAIL"];
			}

			$eventName = "SALE_ORDER_DELIVERY";
			$arFields = Array(
				"ORDER_ID" => $arOrder["ACCOUNT_NUMBER"],
				"ORDER_DATE" => $arOrder["DATE_INSERT_FORMAT"],
				"EMAIL" => $userEMail,
				"SALE_EMAIL" => COption::GetOptionString("sale", "order_email", "order@".$_SERVER["SERVER_NAME"])
			);

			$bSend = true;
			foreach(GetModuleEvents("sale", "OnOrderDeliverSendEmail", true) as $arEvent)
				if (ExecuteModuleEventEx($arEvent, Array($ID, &$eventName, &$arFields))===false)
					$bSend = false;

			if($bSend)
			{
				$event = new CEvent;
				$event->Send($eventName, $arOrder["LID"], $arFields, "N");
			}

			CSaleMobileOrderPush::send("ORDER_DELIVERY_ALLOWED", array("ORDER" => $arOrder));
		}

		//reservation
		if (COption::GetOptionString("sale", "product_reserve_condition", "O") == "D" && $arOrder["RESERVED"] != $val)
		{
			if (!CSaleOrder::ReserveOrder($ID, $val))
				return false;
		}

		//proceed to deduction
		if ($val == "Y")
		{
			$allowDeduction = COption::GetOptionString("sale", "allow_deduction_on_delivery", "");
			if($allowDeduction == "Y" && $arOrder["DEDUCTED"] == "N")
			{
				CSaleOrder::DeductOrder($ID, "Y");
			}
		}

		return $res;
	}

	public static function DeductOrder($ID, $val, $description = "", $bAutoDeduction = true, $arStoreBarcodeOrderFormData = array(), $recurringID = 0)
	{
		global $DB, $USER, $APPLICATION;

		$ID = IntVal($ID);
		$val = (($val != "Y") ? "N" : "Y");
		$description = Trim($description);
		$recurringID = IntVal($recurringID);

		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');

		if ($ID <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGO_NO_ORDER_ID"), "NO_ORDER_ID");
			return false;
		}

		$arOrder = CSaleOrder::GetByID($ID);
		if (!$arOrder)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $ID, Loc::getMessage("SKGO_NO_ORDER")), "NO_ORDER");
			return false;
		}

		if ($arOrder["DEDUCTED"] == $val)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $ID, Loc::getMessage("SKGO_DUB_DEDUCTION")), "ALREADY_FLAG");
			return false;
		}

		foreach(GetModuleEvents("sale", "OnSaleBeforeDeductOrder", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, Array($ID, $val, $description, $bAutoDeduction, $arStoreBarcodeOrderFormData, $recurringID))===false)
				return false;

		unset($GLOBALS["SALE_ORDER"]["SALE_ORDER_CACHE_".$ID]);

		if ($recurringID <= 0)
		{
			if (IntVal($arOrder["RECURRING_ID"]) > 0)
				$recurringID = IntVal($arOrder["RECURRING_ID"]);
		}

		$arDeductResult = CSaleBasket::OrderDeduction($ID, (($val == "N") ? true : false), $recurringID, $bAutoDeduction, $arStoreBarcodeOrderFormData);

		if (array_key_exists("ERROR", $arDeductResult))
		{
			if ($isOrderConverted != "Y")
			{
				CSaleOrder::SetMark($ID, Loc::getMessage("SKGB_DEDUCT_ERROR", array("#MESSAGE#" => $arDeductResult["ERROR"]["MESSAGE"])));
			}

			$APPLICATION->ThrowException(Loc::getMessage("SKGB_DEDUCT_ERROR", array("#MESSAGE#" => $arDeductResult["ERROR"]["MESSAGE"])), "DEDUCTION_ERROR");
			return false;
		}
		else
		{
			if ($arOrder["MARKED"] == "Y")
			{
				CSaleOrder::UnsetMark($ID);
			}
		}

		if ($isOrderConverted == "Y")
		{
			if ($arDeductResult["RESULT"])
			{
				if($val == "Y")
					CSaleMobileOrderPush::send("ORDER_DEDUCTED", array("ORDER" => $arOrder));

				return true;
			}
		}
		else
		{

			if ($arDeductResult["RESULT"])
			{
				if ($val == "Y")
				{
					$arFields = array(
						"DEDUCTED" => "Y",
						"EMP_DEDUCTED_ID" => ( IntVal($USER->GetID())>0 ? IntVal($USER->GetID()) : false ),
						"=DATE_DEDUCTED" => $DB->GetNowFunction()
					);
				}
				else
				{
					$arFields = array(
						"DEDUCTED" => "N",
						"EMP_DEDUCTED_ID" => ( IntVal($USER->GetID())>0 ? IntVal($USER->GetID()) : false ),
						"=DATE_DEDUCTED" => $DB->GetNowFunction()
					);

					if (strlen($description) > 0)
						$arFields["REASON_UNDO_DEDUCTED"] = $description;
				}
				$res = CSaleOrder::Update($ID, $arFields, false);

				if($val == "Y" && $res)
					CSaleMobileOrderPush::send("ORDER_DEDUCTED", array("ORDER" => $arOrder));
			}
			else
				$res = false;

			foreach(GetModuleEvents("sale", "OnSaleDeductOrder", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array($ID, $val));

			if ($res)
				return $res;
			else
				return false;

		}


	}

	public static function ReserveOrder($ID, $val)
	{
		global $APPLICATION;

		$ID = IntVal($ID);
		$val = (($val != "Y") ? "N" : "Y");
		$errorMessage = "";

		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');

		if ($ID <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGO_NO_ORDER_ID"), "NO_ORDER_ID");
			return false;
		}

		$arOrder = CSaleOrder::GetByID($ID);
		if (!$arOrder)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $ID, Loc::getMessage("SKGO_NO_ORDER")), "NO_ORDER");
			return false;
		}

		if ($arOrder["RESERVED"] == $val)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $ID, Loc::getMessage("SKGO_DUB_RESERVATION")), "ALREADY_FLAG");
			return false;
		}

		foreach(GetModuleEvents("sale", "OnSaleBeforeReserveOrder", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, Array($ID, $val))===false)
				return false;

		unset($GLOBALS["SALE_ORDER"]["SALE_ORDER_CACHE_".$ID]);

		if ($isOrderConverted == "Y")
		{
			/** @var \Bitrix\Sale\Result $r */
			$r = \Bitrix\Sale\Compatible\OrderCompatibility::reserve($ID, $val);

			if (!$r->isSuccess(true))
			{
				foreach($r->getErrorMessages() as $error)
				{
					$errorMessage .= " ".$error;
				}

				$APPLICATION->ThrowException(Loc::getMessage("SKGB_RESERVE_ERROR", array("#MESSAGE#" => $errorMessage)), "RESERVATION_ERROR");

				return false;
			}

			$res = true;
		}
		else
		{
			$res = CSaleOrder::Update($ID, array("RESERVED" => $val), false);

			$arRes = CSaleBasket::OrderReservation($ID, (($val == "N") ? true : false));
			if (array_key_exists("ERROR", $arRes))
			{
				foreach ($arRes["ERROR"] as $arError)
				{
					$errorMessage .= " ".$arError["MESSAGE"];
				}

				CSaleOrder::SetMark($ID, Loc::getMessage("SKGB_RESERVE_ERROR", array("#MESSAGE#" => $errorMessage)));
				$APPLICATION->ThrowException(Loc::getMessage("SKGB_RESERVE_ERROR", array("#MESSAGE#" => $errorMessage)), "RESERVATION_ERROR");
				return false;
			}
			else
			{
				if ($arOrder["MARKED"] == "Y")
				{
					CSaleOrder::UnsetMark($ID);
				}
			}
		}

		foreach(GetModuleEvents("sale", "OnSaleReserveOrder", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, Array($ID, $val));

		return $res;
	}

	
	/**
	* <p>Метод меняет значение флага "заказ отменён" (поле <b>CANCELED</b>) на значение параметра <i> Val </i> для заказа с кодом <i>ID</i>. Кроме флага отмены заказа, устанавливаются также поля даты изменения значения флага (<b>DATE_CANCELED</b>) и кода пользователя, изменившего значение флага (<b>EMP_CANCELED_ID</b>). Поле с описанием причины отмены заказа (<b>REASON_CANCELED</b>) заполняется значением параметра <i>Descr</i>.</p> <p>Если заказ был оплачен, то при отмене снимается флаг оплаты с возвращением денег на счет покупателя. Если заказ был частично оплачен, то при отмене внесённая сумма возвращается на счет покупателя.</p> <p>Перед отменой заказа вызываются обработчики события OnSaleBeforeCancelOrder модуля магазина, в которых можно отменить отмену заказа, вернув значение <i>false</i>. После отмены вызываются обработчики события OnSaleCancelOrder модуля магазина.</p> <p>В случае отмены заказа генерируется почтовое событие типа <b>SALE_ORDER_CANCEL</b>. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код заказа.
	*
	* @param string $val  Новое значение флага отмены заказа (Y/N).
	*
	* @param string $description = "" Описание причины отмены заказа.
	*
	* @return int <p>Возвращается код заказа или <i>false</i> в случае ошибки.</p> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* CSaleOrder::CancelOrder(25, "Y", "Потому что передумал");
	* ?&gt;
	* </htm
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__cancelorder.fa562b77.php
	* @author Bitrix
	*/
	public static function CancelOrder($ID, $val, $description = "")
	{
		global $DB, $USER, $APPLICATION;

		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');

		$ID = IntVal($ID);
		$val = (($val != "Y") ? "N" : "Y");
		$description = Trim($description);

		if ($ID <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("SKGO_NO_ORDER_ID1"), "NO_ORDER_ID");
			return false;
		}

		$arOrder = CSaleOrder::GetByID($ID);
		if (!$arOrder)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $ID, Loc::getMessage("SKGO_NO_ORDER")), "NO_ORDER");
			return false;
		}

		if ($arOrder["CANCELED"] == $val)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $ID, Loc::getMessage("SKGO_DUB_CANCEL")), "ALREADY_FLAG");
			return false;
		}

		if ($isOrderConverted == "Y")
		{
			$r = \Bitrix\Sale\Compatible\OrderCompatibility::cancel($ID, $val);
			if ($r->isSuccess(true))
			{
				$res = true;
			}
			else
			{
				$errorMessage = "";
				foreach($r->getErrorMessages() as $error)
				{
					$errorMessage .= " ".$error;
				}

				$APPLICATION->ThrowException(Loc::getMessage("SKGO_CANCEL_ERROR", array("#MESSAGE#" => $errorMessage)), "CANCEL_ERROR");
				return false;
			}
		}
		else
		{

			foreach(GetModuleEvents("sale", "OnSaleBeforeCancelOrder", true) as $arEvent)
				if (ExecuteModuleEventEx($arEvent, Array($ID, $val))===false)
					return false;

			if ($val == "Y")
			{
				if ($arOrder["DEDUCTED"] == "Y")
				{
					if (!CSaleOrder::DeductOrder($ID, "N"))
						return false;
				}

				if ($arOrder["RESERVED"] == "Y")
				{
					if (!CSaleOrder::ReserveOrder($ID, "N"))
						return false;
				}

				if ($arOrder["PAYED"] == "Y")
				{
					if (!CSaleOrder::PayOrder($ID, "N", True, True))
						return False;
				}
				else
				{
					$arOrder["SUM_PAID"] = DoubleVal($arOrder["SUM_PAID"]);
					if ($arOrder["SUM_PAID"] > 0)
					{
						if (!CSaleUserAccount::UpdateAccount($arOrder["USER_ID"], $arOrder["SUM_PAID"], $arOrder["CURRENCY"], "ORDER_CANCEL_PART", $ID))
							return False;
						CSaleOrder::Update($arOrder["ID"], array("SUM_PAID" => 0));
					}
				}

				if ($arOrder["ALLOW_DELIVERY"] == "Y")
				{
					if (!CSaleOrder::DeliverOrder($ID, "N"))
						return False;
				}
			}
			else //if undo cancel
			{
				if (COption::GetOptionString("sale", "product_reserve_condition", "O") == "O" && $arOrder["RESERVED"] != "Y")
				{
					if (!CSaleOrder::ReserveOrder($ID, "Y"))
						return false;
				}
			}

			$arFields = array(
				"CANCELED" => $val,
				"=DATE_CANCELED" => $DB->GetNowFunction(),
				"REASON_CANCELED" => ( strlen($description)>0 ? $description : false ),
				"EMP_CANCELED_ID" => ( IntVal($USER->GetID())>0 ? IntVal($USER->GetID()) : false )
			);
			$res = CSaleOrder::Update($ID, $arFields);
		}

		unset($GLOBALS["SALE_ORDER"]["SALE_ORDER_CACHE_".$ID]);

		if ($isOrderConverted != "Y")
		{
			//this method is used only for catalogs without reservation and deduction support
			CSaleBasket::OrderCanceled($ID, (($val=="Y") ? True : False));

			foreach(GetModuleEvents("sale", "OnSaleCancelOrder", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, Array($ID, $val, $description));
		}

		if ($val == "Y")
		{
			CTimeZone::Disable();
			$arOrder = CSaleOrder::GetByID($ID);
			CTimeZone::Enable();

			$userEmail = "";
			$dbOrderProp = CSaleOrderPropsValue::GetList(Array(), Array("ORDER_ID" => $ID, "PROP_IS_EMAIL" => "Y"));
			if($arOrderProp = $dbOrderProp->Fetch())
				$userEmail = $arOrderProp["VALUE"];
			if(strlen($userEmail) <= 0)
			{
				$dbUser = CUser::GetByID($arOrder["USER_ID"]);
				if($arUser = $dbUser->Fetch())
					$userEmail = $arUser["EMAIL"];
			}

			$event = new CEvent;
			$arFields = Array(
				"ORDER_ID" => $arOrder["ACCOUNT_NUMBER"],
				"ORDER_DATE" => $arOrder["DATE_INSERT_FORMAT"],
				"EMAIL" => $userEmail,
				"ORDER_CANCEL_DESCRIPTION" => $description,
				"SALE_EMAIL" => COption::GetOptionString("sale", "order_email", "order@".$_SERVER["SERVER_NAME"])
			);

			$eventName = "SALE_ORDER_CANCEL";

			$bSend = true;
			foreach(GetModuleEvents("sale", "OnOrderCancelSendEmail", true) as $arEvent)
				if (ExecuteModuleEventEx($arEvent, Array($ID, &$eventName, &$arFields))===false)
					$bSend = false;

			if($bSend)
			{
				$event = new CEvent;
				$event->Send($eventName, $arOrder["LID"], $arFields, "N");
			}

			CSaleMobileOrderPush::send("ORDER_CANCELED", array("ORDER" => $arOrder));

			if (CModule::IncludeModule("statistic"))
			{
				CStatEvent::AddByEvents("eStore", "order_cancel", $ID, "", $arOrder["STAT_GID"]);
			}
		}

		return $res;
	}

	
	/**
	* <p>Метод меняет значение статуса заказа (поле <b>STATUS_ID</b>) на значение параметра <i> Val </i> для заказа с кодом <i>ID</i>. Кроме статуса заказа устанавливаются так же поля даты изменения статуса заказа (<b>DATE_STATUS</b>) и кода пользователя, изменившего статус заказа (<b>EMP_STATUS_ID</b>). Метод динамичный.</p> <p>Перед изменением статуса вызываются обработчики события OnSaleBeforeStatusOrder модуля магазина, в которых можно отменить изменение статуса вернув значение <i>false</i>. После изменения флага вызываются обработчики события OnSaleStatusOrder модуля магазина.</p> <p>Генерируется почтовое событие типа <b> SALE_STATUS_CHANGED_&lt;код статуса&gt;</b>, если есть подходящий почтовый шаблон. Иначе генерируется почтовое событие типа <b>SALE_STATUS_CHANGED</b>.</p>
	*
	*
	* @param int $ID  Код заказа.
	*
	* @param string $Val  Код статуса заказа. </htm
	*
	* @return int <p>Возвращается код заказа или <i>false</i> в случае ошибки.</p> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (!CSaleOrder::StatusOrder($ID, "F"))
	*    echo "Ошибка установки нового статуса заказа";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__statusorder.f21c0322.php
	* @author Bitrix
	*/
	public static function StatusOrder($ID, $val)
	{
		global $DB, $USER;

		$ID = IntVal($ID);
		$val = trim($val);

		foreach(GetModuleEvents("sale", "OnSaleBeforeStatusOrder", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, Array($ID, $val))===false)
				return false;

		$arFields = array(
			"STATUS_ID" => $val,
			"=DATE_STATUS" => $DB->GetNowFunction(),
			"EMP_STATUS_ID" => ( IntVal($USER->GetID())>0 ? IntVal($USER->GetID()) : false )
		);
		$res = CSaleOrder::Update($ID, $arFields);

		unset($GLOBALS["SALE_ORDER"]["SALE_ORDER_CACHE_".$ID]);

		foreach(GetModuleEvents("sale", "OnSaleStatusOrder", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, Array($ID, $val));

		CTimeZone::Disable();
		$arOrder = CSaleOrder::GetByID($ID);
		CTimeZone::Enable();

		CSaleMobileOrderPush::send("ORDER_STATUS_CHANGED", array("ORDER" => $arOrder));

		$userEmail = "";
		$dbOrderProp = CSaleOrderPropsValue::GetList(Array(), Array("ORDER_ID" => $ID, "PROP_IS_EMAIL" => "Y"));
		if($arOrderProp = $dbOrderProp->Fetch())
			$userEmail = $arOrderProp["VALUE"];
		if(strlen($userEmail) <= 0)
		{
			$dbUser = CUser::GetByID($arOrder["USER_ID"]);
			if($arUser = $dbUser->Fetch())
				$userEmail = $arUser["EMAIL"];
		}
		$dbSite = CSite::GetByID($arOrder["LID"]);
		$arSite = $dbSite->Fetch();
		$arStatus = CSaleStatus::GetByID($arOrder["STATUS_ID"], $arSite["LANGUAGE_ID"]);

		if ($arStatus['NOTIFY'] == 'Y')
		{
			$arFields = Array(
				"ORDER_ID" => $arOrder["ACCOUNT_NUMBER"],
				"ORDER_DATE" => $arOrder["DATE_INSERT_FORMAT"],
				"ORDER_STATUS" => $arStatus["NAME"],
				"EMAIL" => $userEmail,
				"ORDER_DESCRIPTION" => $arStatus["DESCRIPTION"],
				"TEXT" => "",
				"SALE_EMAIL" => COption::GetOptionString("sale", "order_email", "order@".$_SERVER["SERVER_NAME"])
			);

			foreach(GetModuleEvents("sale", "OnSaleStatusEMail", true) as $arEvent)
				$arFields["TEXT"] = ExecuteModuleEventEx($arEvent, Array($ID, $arStatus["ID"]));

			$eventName = "SALE_STATUS_CHANGED_".$arOrder["STATUS_ID"];

			$bSend = true;
			foreach(GetModuleEvents("sale", "OnOrderStatusSendEmail", true) as $arEvent)
				if (ExecuteModuleEventEx($arEvent, Array($ID, &$eventName, &$arFields, $arOrder["STATUS_ID"]))===false)
					$bSend = false;

			if($bSend)
			{
				$b = '';
				$o = '';
				$eventMessage = new CEventMessage;
				$dbEventMessage = $eventMessage->GetList(
					$b,
					$o,
					array(
						"EVENT_NAME" => $eventName,
						"SITE_ID" => $arOrder["LID"],
						'ACTIVE' => 'Y'
					)
				);
				if (!($arEventMessage = $dbEventMessage->Fetch()))
					$eventName = "SALE_STATUS_CHANGED";
				unset($o, $b);
				$event = new CEvent;
				$event->Send($eventName, $arOrder["LID"], $arFields, "N");
			}
		}

		return $res;
	}

	
	/**
	* <p>Метод устанавливает новое значение <i> Val</i> комментария (поле <b>COMMENTS</b>) к заказу с кодом <i> ID</i>. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код заказа.
	*
	* @param string $Val  Новое значение комментария.
	*
	* @return int <p>Возвращается код заказа или <i>false</i> в случае ошибки.</p> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (!CSaleOrder::CommentsOrder(12, "Не отгружать до оплаты!"))
	*    echo "Ошибка изменения комментария к заказу";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleorder/csaleorder__commentsorder.edec4495.php
	* @author Bitrix
	*/
	public static function CommentsOrder($ID, $val)
	{
		$ID = IntVal($ID);
		$val = Trim($val);

		$arFields = array(
			"COMMENTS" => ( strlen($val)>0 ? $val : false )
		);
		$res = CSaleOrder::Update($ID, $arFields);

		unset($GLOBALS["SALE_ORDER"]["SALE_ORDER_CACHE_".$ID]);

		return $res;
	}


	public static function Lock($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return False;

		$arFields = array(
			"DATE_LOCK" => new \Bitrix\Main\Type\DateTime(),
			"LOCKED_BY" => $GLOBALS["USER"]->GetID()
		);

		return Sale\Internals\OrderTable::update($ID, $arFields);
//		return CSaleOrder::Update($ID, $arFields, false);
	}

	public static function UnLock($ID)
	{
		$ID = IntVal($ID);
		if ($ID <= 0)
			return False;

		$arOrder = CSaleOrder::GetByID($ID);
		if (!$arOrder)
			return False;

		$userRights = CMain::GetUserRight("sale", $GLOBALS["USER"]->GetUserGroupArray(), "Y", "Y");

		if (($userRights >= "W") || ($arOrder["LOCKED_BY"] == $GLOBALS["USER"]->GetID()))
		{
			$arFields = array(
				"DATE_LOCK" => false,
				"LOCKED_BY" => false
			);

			if (!Sale\Internals\OrderTable::update($ID, $arFields))
				return False;
			else
				return True;
		}

		return False;
	}

	public static function IsLocked($ID, &$lockedBY, &$dateLock)
	{
		$ID = IntVal($ID);

		$lockStatus = CSaleOrder::GetLockStatus($ID, $lockedBY, $dateLock);
		if ($lockStatus == "red")
			return true;

		return false;
	}

	public static function RemindPayment()
	{
		$reminder = COption::GetOptionString("sale", "pay_reminder", "");
		$arReminder = unserialize($reminder);

		if(!empty($arReminder))
		{
			$arSites = Array();
			$minDay = mktime();
			foreach($arReminder as $key => $val)
			{
				if($val["use"] == "Y" && $val["frequency"] > 0)
				{
					$arSites[] = $key;
					$days = Array();

					for($i=0; $i <= floor($val["period"] / $val["frequency"]); $i++)
					{
						$day = AddToTimeStamp(Array("DD" => -($val["after"] + $val["period"] - $val["frequency"]*$i)));
						if($day < mktime())
						{
							if($minDay > $day)
								$minDay = $day;

							$day = ConvertTimeStamp($day);

							$days[] = $day;
						}
					}
					$arReminder[$key]["days"] = $days;
				}
			}

			if(!empty($arSites))
			{
				$bTmpUser = False;
				if (!isset($GLOBALS["USER"]) || !is_object($GLOBALS["USER"]))
				{
					$bTmpUser = True;
					$GLOBALS["USER"] = new CUser;
				}

				$arFilter = Array(
						"LID" => $arSites,
						"PAYED" => "N",
						"CANCELED" => "N",
						"ALLOW_DELIVERY" => "N",
						">=DATE_INSERT" => ConvertTimeStamp($minDay),
					);

				$dbOrder = CSaleOrder::GetList(Array("ID" => "DESC"), $arFilter, false, false, Array("ID", "DATE_INSERT", "PAYED", "USER_ID", "LID", "PRICE", "CURRENCY", "ACCOUNT_NUMBER"));
				while($arOrder = $dbOrder -> GetNext())
				{
					$date_insert = ConvertDateTime($arOrder["DATE_INSERT"], CSite::GetDateFormat("SHORT"));

					if(in_array($date_insert, $arReminder[$arOrder["LID"]]["days"]))
					{

						$strOrderList = "";
						$dbBasketTmp = CSaleBasket::GetList(
								array("NAME" => "ASC"),
								array("ORDER_ID" => $arOrder["ID"]),
								false,
								false,
								array("ID", "NAME", "QUANTITY")
							);
						while ($arBasketTmp = $dbBasketTmp->Fetch())
						{
							$strOrderList .= $arBasketTmp["NAME"]." (".$arBasketTmp["QUANTITY"].")";
							$strOrderList .= "\n";
						}

						$payerEMail = "";
						$dbOrderProp = CSaleOrderPropsValue::GetList(Array(), Array("ORDER_ID" => $arOrder["ID"], "PROP_IS_EMAIL" => "Y"));
						if($arOrderProp = $dbOrderProp->Fetch())
							$payerEMail = $arOrderProp["VALUE"];

						$payerName = "";
						$dbUser = CUser::GetByID($arOrder["USER_ID"]);
						if ($arUser = $dbUser->Fetch())
						{
							if (strlen($payerName) <= 0)
								$payerName = $arUser["NAME"].((strlen($arUser["NAME"])<=0 || strlen($arUser["LAST_NAME"])<=0) ? "" : " ").$arUser["LAST_NAME"];
							if (strlen($payerEMail) <= 0)
								$payerEMail = $arUser["EMAIL"];
						}

						$arFields = Array(
							"ORDER_ID" => $arOrder["ACCOUNT_NUMBER"],
							"ORDER_DATE" => $date_insert,
							"ORDER_USER" => $payerName,
							"PRICE" => SaleFormatCurrency($arOrder["PRICE"], $arOrder["CURRENCY"]),
							"BCC" => COption::GetOptionString("sale", "order_email", "order@".$_SERVER["SERVER_NAME"]),
							"EMAIL" => $payerEMail,
							"ORDER_LIST" => $strOrderList,
							"SALE_EMAIL" => COption::GetOptionString("sale", "order_email", "order@".$_SERVER['SERVER_NAME'])
						);

						$eventName = "SALE_ORDER_REMIND_PAYMENT";

						$bSend = true;
						foreach(GetModuleEvents("sale", "OnOrderRemindSendEmail", true) as $arEvent)
							if (ExecuteModuleEventEx($arEvent, Array($arOrder["ID"], &$eventName, &$arFields))===false)
								$bSend = false;

						if($bSend)
						{
							$event = new CEvent;
							$event->Send($eventName, $arOrder["LID"], $arFields, "N");
						}
					}
				}

				if ($bTmpUser)
				{
					unset($GLOBALS["USER"]);
				}

			}
		}
		return "CSaleOrder::RemindPayment();";
	}

	public static function __SaleOrderCount($arFilter, $strCurrency = '')
	{
		$mxResult = false;
		if (is_array($arFilter) && !empty($arFilter))
		{
			$dblPrice = 0;
			$strCurrency = strval($strCurrency);
			$mxLastOrderDate = '';
			$intMaxTimestamp = 0;
			$intTimeStamp = 0;
			$rsSaleOrders = CSaleOrder::GetList(array(),$arFilter,false,false,array('ID','PRICE','CURRENCY','DATE_INSERT'));
			while ($arSaleOrder = $rsSaleOrders->Fetch())
			{
				$intTimeStamp = MakeTimeStamp($arSaleOrder['DATE_INSERT']);
				if ($intMaxTimestamp < $intTimeStamp)
				{
					$intMaxTimestamp = $intTimeStamp;
					$mxLastOrderDate = $arSaleOrder['DATE_INSERT'];
				}
				if (empty($strCurrency))
				{
					$dblPrice += $arSaleOrder['PRICE'];
					$strCurrency = $arSaleOrder['CURRENCY'];
				}
				else
				{
					if ($strCurrency != $arSaleOrder['CURRENCY'])
					{
						$dblPrice += $arSaleOrder['PRICE'];
					}
					else
					{
						$dblPrice += $arSaleOrder['PRICE'];
					}
				}
			}
			$mxResult = array(
				'PRICE' => $dblPrice,
				'CURRENCY' => $strCurrency,
				'LAST_ORDER_DATE' => $mxLastOrderDate,
				'TIMESTAMP' => $intMaxTimestamp,
			);
		}
		return $mxResult;
	}


	/**
	* @deprecated Use CSaleOrderChange::GetList instead
	* The function selects order history
	*
	* @param array $arOrder - array to sort
	* @param array $arFilter - array to filter
	* @param array $arGroupBy - array to group records
	* @param array $arNavStartParams - array to parameters
	* @param array $arSelectFields - array to selectes fields
	* @return object $dbRes - object result
	*/
	static public function GetHistoryList($arOrder = array("ID"=>"DESC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (array_key_exists("H_DATE_INSERT_FROM", $arFilter))
		{
			$val = $arFilter["H_DATE_INSERT_FROM"];
			unset($arFilter["H_DATE_INSERT_FROM"]);
			$arFilter[">=H_DATE_INSERT"] = $val;
		}
		if (array_key_exists("H_DATE_INSERT_TO", $arFilter))
		{
			$val = $arFilter["H_DATE_INSERT_TO"];
			unset($arFilter["H_DATE_INSERT_TO"]);
			$arFilter["<=H_DATE_INSERT"] = $val;
		}

		if (!$arSelectFields || count($arSelectFields) <= 0 || in_array("*", $arSelectFields))
		{
			$arSelectFields = array(
				"ID",
				"H_USER_ID",
				"H_DATE_INSERT",
				"H_ORDER_ID",
				"H_CURRENCY",
				"PERSON_TYPE_ID",
				"PAYED",
				"DATE_PAYED",
				"EMP_PAYED_ID",
				"CANCELED",
				"DATE_CANCELED",
				"REASON_CANCELED",
				"MARKED",
				"DATE_MARKED",
				"REASON_MARKED",
				"DEDUCTED",
				"DATE_DEDUCTED",
				"REASON_UNDO_DEDUCTED",
				"RESERVED",
				"STATUS_ID",
				"DATE_STATUS",
				"PRICE_DELIVERY",
				"ALLOW_DELIVERY",
				"DATE_ALLOW_DELIVERY",
				"PRICE",
				"CURRENCY",
				"DISCOUNT_VALUE",
				"USER_ID",
				"PAY_SYSTEM_ID",
				"DELIVERY_ID",
				"PS_STATUS",
				"PS_STATUS_CODE",
				"PS_STATUS_DESCRIPTION",
				"PS_STATUS_MESSAGE",
				"PS_SUM",
				"PS_CURRENCY",
				"PS_RESPONSE_DATE",
				"TAX_VALUE",
				"STAT_GID",
				"SUM_PAID",
				"PAY_VOUCHER_NUM",
				"PAY_VOUCHER_DATE",
				"AFFILIATE_ID",
				"DELIVERY_DOC_NUM",
				"DELIVERY_DOC_DATE"
			);
		}

		$arFields = array(
				"ID" => array("FIELD" => "V.ID", "TYPE" => "int"),
				"H_ORDER_ID" => array("FIELD" => "V.H_ORDER_ID", "TYPE" => "int"),
				"H_USER_ID" => array("FIELD" => "V.H_USER_ID", "TYPE" => "int"),
				"H_DATE_INSERT" => array("FIELD" => "V.H_DATE_INSERT", "TYPE" => "datetime"),
				"H_CURRENCY" => array("FIELD" => "V.H_CURRENCY", "TYPE" => "string"),
				"PERSON_TYPE_ID" => array("FIELD" => "V.PERSON_TYPE_ID", "TYPE" => "int"),
				"PAYED" => array("FIELD" => "V.PAYED", "TYPE" => "char"),
				"DATE_PAYED" => array("FIELD" => "V.DATE_PAYED", "TYPE" => "datetime"),
				"EMP_PAYED_ID" => array("FIELD" => "V.EMP_PAYED_ID", "TYPE" => "int"),
				"CANCELED" => array("FIELD" => "V.CANCELED", "TYPE" => "char"),
				"DATE_CANCELED" => array("FIELD" => "V.DATE_CANCELED", "TYPE" => "datetime"),
				"REASON_CANCELED" => array("FIELD" => "V.REASON_CANCELED", "TYPE" => "string"),
				"MARKED" => array("FIELD" => "V.MARKED", "TYPE" => "char"),
				"DATE_MARKED" => array("FIELD" => "V.DATE_MARKED", "TYPE" => "datetime"),
				"REASON_MARKED" => array("FIELD" => "V.REASON_MARKED", "TYPE" => "string"),
				"DEDUCTED" => array("FIELD" => "V.DEDUCTED", "TYPE" => "char"),
				"DATE_DEDUCTED" => array("FIELD" => "V.DATE_DEDUCTED", "TYPE" => "datetime"),
				"REASON_DEDUCTED" => array("FIELD" => "V.REASON_UNDO_DEDUCTED", "TYPE" => "string"),
				"RESERVED" => array("FIELD" => "V.RESERVED", "TYPE" => "char"),
				"STATUS_ID" => array("FIELD" => "V.STATUS_ID", "TYPE" => "char"),
				"DATE_STATUS" => array("FIELD" => "V.DATE_STATUS", "TYPE" => "datetime"),
				"PAY_VOUCHER_NUM" => array("FIELD" => "V.PAY_VOUCHER_NUM", "TYPE" => "string"),
				"PAY_VOUCHER_DATE" => array("FIELD" => "V.PAY_VOUCHER_DATE", "TYPE" => "date"),
				"PRICE_DELIVERY" => array("FIELD" => "V.PRICE_DELIVERY", "TYPE" => "double"),
				"ALLOW_DELIVERY" => array("FIELD" => "V.ALLOW_DELIVERY", "TYPE" => "char"),
				"DATE_ALLOW_DELIVERY" => array("FIELD" => "V.DATE_ALLOW_DELIVERY", "TYPE" => "datetime"),
				"PRICE" => array("FIELD" => "V.PRICE", "TYPE" => "double"),
				"CURRENCY" => array("FIELD" => "V.CURRENCY", "TYPE" => "string"),
				"DISCOUNT_VALUE" => array("FIELD" => "V.DISCOUNT_VALUE", "TYPE" => "double"),
				"SUM_PAID" => array("FIELD" => "V.SUM_PAID", "TYPE" => "double"),
				"USER_ID" => array("FIELD" => "V.USER_ID", "TYPE" => "int"),
				"PAY_SYSTEM_ID" => array("FIELD" => "V.PAY_SYSTEM_ID", "TYPE" => "int"),
				"DELIVERY_ID" => array("FIELD" => "V.DELIVERY_ID", "TYPE" => "string"),
				"PS_STATUS" => array("FIELD" => "V.PS_STATUS", "TYPE" => "char"),
				"PS_STATUS_CODE" => array("FIELD" => "V.PS_STATUS_CODE", "TYPE" => "string"),
				"PS_STATUS_DESCRIPTION" => array("FIELD" => "V.PS_STATUS_DESCRIPTION", "TYPE" => "string"),
				"PS_STATUS_MESSAGE" => array("FIELD" => "V.PS_STATUS_MESSAGE", "TYPE" => "string"),
				"PS_SUM" => array("FIELD" => "V.PS_SUM", "TYPE" => "double"),
				"PS_CURRENCY" => array("FIELD" => "V.PS_CURRENCY", "TYPE" => "string"),
				"PS_RESPONSE_DATE" => array("FIELD" => "V.PS_RESPONSE_DATE", "TYPE" => "datetime"),
				"TAX_VALUE" => array("FIELD" => "V.TAX_VALUE", "TYPE" => "double"),
				"AFFILIATE_ID" => array("FIELD" => "V.AFFILIATE_ID", "TYPE" => "int"),
				"DELIVERY_DOC_NUM" => array("FIELD" => "V.DELIVERY_DOC_NUM", "TYPE" => "string"),
				"DELIVERY_DOC_DATE" => array("FIELD" => "V.DELIVERY_DOC_DATE", "TYPE" => "date"),
		);

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);
		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_sale_order_history V ";

		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arGroupBy) && count($arGroupBy) == 0)
		{
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) <= 0 )
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_sale_order_history V ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (strlen($arSqls["GROUPBY"]) <= 0)
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
			$strSql = $DB->TopSql($strSql, $arNavStartParams["nTopCount"]);
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	public static function SetMark($ID, $comment = "", $userID = 0)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID < 0)
			return false;

		$userID = IntVal($userID);

		$arFields = array(
			"MARKED" => "Y",
			"REASON_MARKED" => $comment,
			"EMP_MARKED_ID" => $userID,
			"=DATE_MARKED" => $DB->GetNowFunction()
		);

		CSaleMobileOrderPush::send("ORDER_MARKED", array("ORDER_ID" => $ID));

		return CSaleOrder::Update($ID, $arFields);
	}

	public static function UnsetMark($ID, $userID = 0)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID < 0)
			return false;

		$userID = IntVal($userID);

		$arFields = array(
			"MARKED" => "N",
			"REASON_MARKED" => "",
			"EMP_MARKED_ID" => $userID,
			"=DATE_MARKED" => $DB->GetNowFunction()
		);

		return CSaleOrder::Update($ID, $arFields);
	}

	/**
	* Sets order account number
	* Use OnBeforeOrderAccountNumberSet event to generate custom account number.
	* Account number value must be unique! By default order ID is used if generated value is incorrect
	*
	* @param int $ID - order ID
	* @return bool - true if account number is set successfully
	*/
	public static function SetAccountNumber($ID)
	{
		/** @var Sale\Result $r */
		$r = static::setAccountNumberById($ID);
		return $r->isSuccess();
	}

	/**
	 * @param $id
	 *
	 * @return Sale\Result|bool|int
	 * @throws Exception
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function setAccountNumberById($id)
	{
		$result = new Sale\Result();
		$id = intval($id);
		if ($id <= 0)
		{
			$result->addError(new Sale\ResultError(Loc::getMessage('SALE_ORDER_GENERATE_ACCOUNT_NUMBER_ORDER_NUMBER_WRONG_ID'), 'SALE_ORDER_GENERATE_ACCOUNT_NUMBER_ORDER_NUMBER_WRONG_ID'));
			return $result;
		}

		$type = COption::GetOptionString("sale", "account_number_template", "");
		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');

		$accountNumber = null;
		$isCustomAlgorithm = false;
		$value = null;
		/** @var Sale\Result $r */
		$r = static::generateCustomAccountNumber($id);
		if ($r->isSuccess())
		{
			if ($accountData = $r->getData())
			{
				if (array_key_exists('IS_CUSTOM', $accountData))
				{
					$isCustomAlgorithm = $accountData['IS_CUSTOM'];
				}
				if (array_key_exists('VALUE', $accountData))
				{
					$value = $accountData['VALUE'];
				}
			}

		}

		if ($isCustomAlgorithm)
		{
			if ($isOrderConverted == "Y")
			{
				try
				{
					/** @var \Bitrix\Sale\Result $r */
					$r = \Bitrix\Sale\Internals\OrderTable::update($id, array("ACCOUNT_NUMBER" => $value));
					$res = $r->isSuccess(true);
				}
				catch (\Bitrix\Main\DB\SqlQueryException $exception)
				{
					$res = false;
				}

			}
			else
			{
				$res = CSaleOrder::Update($id, array("ACCOUNT_NUMBER" => $value), false);
			}

			if ($res)
			{
				$accountNumber = $value;
			}
		}
		else
		{
			$r = static::generateAccountNumber($id);
			if ($res = $r->isSuccess())
			{
				if ($accountData = $r->getData())
				{
					if (array_key_exists('VALUE', $accountData))
					{
						$accountNumber = $accountData['VALUE'];
					}
				}
			}
		}

		if ($type == "" || !$res) // if no special template is used or error occured
		{
			$r = static::setIdAsAccountNumber($id);
			$res = $r->isSuccess();

			if ($res)
			{
				if ($accountData = $r->getData())
				{
					if (array_key_exists('ACCOUNT_NUMBER', $accountData))
					{
						$accountNumber = $accountData['ACCOUNT_NUMBER'];
					}
				}
			}
		}

		$result->setData(array(
							'ACCOUNT_NUMBER' => $accountNumber
						 ));
		return $result;
	}

	/**
	 * @param $id
	 *
	 * @return Sale\Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected static function generateCustomAccountNumber($id)
	{
		$result = new Sale\Result();

		$id = intval($id);
		if ($id <= 0)
		{
			$result->addError(new Sale\ResultError(Loc::getMessage('SALE_ORDER_GENERATE_ACCOUNT_NUMBER_ORDER_NUMBER_WRONG_ID'), 'SALE_ORDER_GENERATE_ACCOUNT_NUMBER_ORDER_NUMBER_WRONG_ID'));
			return $result;
		}

		$type = \Bitrix\Main\Config\Option::get("sale", "account_number_template", "");

		$isCustomAlgorithm = false;
		$value = null;
		foreach(GetModuleEvents("sale", "OnBeforeOrderAccountNumberSet", true) as $arEvent)
		{
			$tmpRes = ExecuteModuleEventEx($arEvent, array($id, $type));
			if ($tmpRes !== false)
			{
				$isCustomAlgorithm = true;
				$value = $tmpRes;
			}
		}

		$result->setData(array(
							 'IS_CUSTOM' => $isCustomAlgorithm,
							 'VALUE' => $value
						 ));

		return $result;
	}

	/**
	 * @internal
	 * @param $id
	 *
	 * @return Sale\Result
	 * @throws Exception
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected static function generateAccountNumber($id)
	{
		$result = new Sale\Result();

		$id = intval($id);
		if ($id <= 0)
		{
			$result->addError(new Sale\ResultError(Loc::getMessage('SALE_ORDER_GENERATE_ACCOUNT_NUMBER_ORDER_NUMBER_WRONG_ID'), 'SALE_ORDER_GENERATE_ACCOUNT_NUMBER_ORDER_NUMBER_WRONG_ID'));
			return $result;
		}

		$type = \Bitrix\Main\Config\Option::get("sale", "account_number_template", "");
		$param = \Bitrix\Main\Config\Option::get("sale", "account_number_data", "");
		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');

		$res = false;
		$value = null;
		if ($type != "") // if special template is selected
		{
			for ($i = 0; $i < 10; $i++)
			{
				$value = CSaleOrder::GetNextAccountNumber($id, $type, $param);

				if ($value)
				{
					if ($isOrderConverted == "Y")
					{
						try
						{
							/** @var \Bitrix\Sale\Result $r */
							$r = \Bitrix\Sale\Internals\OrderTable::update($id, array("ACCOUNT_NUMBER" => $value));
							$res = $r->isSuccess();
						}
						catch(\Bitrix\Main\DB\SqlQueryException $exception)
						{
							$res = false;
						}

					}
					else
					{
						$res = CSaleOrder::Update($id, array("ACCOUNT_NUMBER" => $value), false);
					}

					if ($res)
						break;
				}
			}
		}

		if (!$res)
		{
			$result->addError(new Sale\ResultError(Loc::getMessage('SALE_ORDER_GENERATE_ACCOUNT_NUMBER_ORDER_NUMBER_IS_NOT_SET'), 'SALE_ORDER_GENERATE_ACCOUNT_NUMBER_ORDER_NUMBER_IS_NOT_SET'));
			return $result;
		}

		$result->setData(array(
							'VALUE' => (strval($value) != '')? $value : null,
						 ));

		return $result;
	}

	/**
	 * @internal
	 * @param $id
	 *
	 * @return Sale\Result
	 * @throws Exception
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected static function setIdAsAccountNumber($id)
	{
		$result = new Sale\Result();
		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');
		if ($isOrderConverted == "Y")
		{
			try
			{
				/** @var \Bitrix\Sale\Result $r */
				$r = \Bitrix\Sale\Internals\OrderTable::update($id, array("ACCOUNT_NUMBER" => $id));
				$res = $r->isSuccess(true);
			}
			catch (\Bitrix\Main\DB\SqlQueryException $exception)
			{
				$res = false;
			}

		}
		else
		{
			$res = CSaleOrder::Update($id, array("ACCOUNT_NUMBER" => $id), false);
		}

		if (!$res)
		{
			$result->addError(new Sale\ResultError(Loc::getMessage('SALE_ORDER_GENERATE_ACCOUNT_NUMBER_ORDER_NUMBER_IS_NOT_SET_AS_ID'), 'SALE_ORDER_GENERATE_ACCOUNT_NUMBER_ORDER_NUMBER_IS_NOT_SET_AS_ID'));
			return $result;
		}

		$result->setData(array(
							'ACCOUNT_NUMBER' => $id
						 ));

		return $result;
	}

	/**
	* Generates next account number according to the scheme selected in the module options
	*
	* @param int $orderID - order ID
	* @param string $templateType - account number template type code
	* @param string $param - account number template param
	* @return mixed - generated number or false
	*/
	public static function GetNextAccountNumber($orderID, $templateType, $param)
	{
		global $DB;
		$value = false;

		switch ($templateType)
		{
			case 'NUMBER':

				$param = intval($param);
				$maxLastID = 0;

				switch(strtolower($DB->type))
				{
					case "mysql":
						$strSql = "SELECT ID, ACCOUNT_NUMBER FROM b_sale_order WHERE ACCOUNT_NUMBER IS NOT NULL ORDER BY ID DESC LIMIT 1";
						break;
					case "oracle":
						$strSql = "SELECT ID, ACCOUNT_NUMBER FROM b_sale_order WHERE ACCOUNT_NUMBER IS NOT NULL AND ROWNUM <= 1 ORDER BY ID DESC";
						break;
					case "mssql":
						$strSql = "SELECT TOP 1 ID, ACCOUNT_NUMBER FROM b_sale_order WHERE ACCOUNT_NUMBER IS NOT NULL ORDER BY ID DESC";
						break;
				}

				$dbres = $DB->Query($strSql, true);
				if ($arRes = $dbres->GetNext())
				{
					if (strlen($arRes["ACCOUNT_NUMBER"]) === strlen(intval($arRes["ACCOUNT_NUMBER"])))
						$maxLastID = intval($arRes["ACCOUNT_NUMBER"]);
				}

				$value = ($maxLastID >= $param) ? $maxLastID + 1 : $param;
				break;

			case 'PREFIX':

				$value = $param.$orderID;
				break;

			case 'RANDOM':

				$rand = randString(intval($param), array("ABCDEFGHIJKLNMOPQRSTUVWXYZ", "0123456789"));
				$dbres = $DB->Query("SELECT ID, ACCOUNT_NUMBER FROM b_sale_order WHERE ACCOUNT_NUMBER = '".$rand."'", true);
				$value = ($arRes = $dbres->GetNext()) ? false : $rand;
				break;

			case 'USER':

				$dbres = $DB->Query("SELECT USER_ID FROM b_sale_order WHERE ID = '".$orderID."'", true);

				if ($arRes = $dbres->GetNext())
				{
					$userID = $arRes["USER_ID"];

					switch (strtolower($DB->type))
					{
						case "mysql":
							$strSql = "SELECT MAX(CAST(SUBSTRING(ACCOUNT_NUMBER, LENGTH('".$userID."_') + 1) as UNSIGNED)) as NUM_ID FROM b_sale_order WHERE ACCOUNT_NUMBER LIKE '".$userID."\_%'";
							break;
						case "oracle":
							$strSql = "SELECT MAX(CAST(SUBSTR(ACCOUNT_NUMBER, LENGTH('".$userID."_') + 1) as NUMBER)) as NUM_ID FROM b_sale_order WHERE ACCOUNT_NUMBER LIKE '".$userID."_%'";
							break;
						case "mssql":
							$strSql = "SELECT MAX(CAST(SUBSTRING(ACCOUNT_NUMBER, LEN('".$userID."_') + 1, LEN(ACCOUNT_NUMBER)) as INT)) as NUM_ID FROM b_sale_order WHERE ACCOUNT_NUMBER LIKE '".$userID."_%'";
							break;
					}

					$dbres = $DB->Query($strSql, true);
					if ($arRes = $dbres->GetNext())
					{
						$numID = (intval($arRes["NUM_ID"]) > 0) ? $arRes["NUM_ID"] + 1 : 1;
						$value = $userID."_".$numID;
					}
					else
						$value = $userID."_1";
				}
				else
					$value = false;

				break;

			case 'DATE':

				switch ($param)
				{
					// date in the site format but without delimeters
					case 'day':
						$date = date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")), mktime(0, 0, 0, date("m"), date("d"), date("Y")));
						$date = preg_replace("/[^0-9]/", "", $date);
						break;
					case 'month':
						$date = date($DB->DateFormatToPHP(str_replace("DD", "", CSite::GetDateFormat("SHORT"))), mktime(0, 0, 0, date("m"), date("d"), date("Y")));
						$date = preg_replace("/[^0-9]/", "", $date);
						break;
					case 'year':
						$date = date('Y');
						break;
				}

				switch (strtolower($DB->type))
				{
					case "mysql":
						$strSql = "SELECT MAX(CAST(SUBSTRING(ACCOUNT_NUMBER, LENGTH('".$date." / ') + 1) as UNSIGNED)) as NUM_ID FROM b_sale_order WHERE ACCOUNT_NUMBER LIKE '".$date." / %'";
						break;
					case "oracle":
						$strSql = "SELECT MAX(CAST(SUBSTR(ACCOUNT_NUMBER, LENGTH('".$date." / ') + 1) as NUMBER)) as NUM_ID FROM b_sale_order WHERE ACCOUNT_NUMBER LIKE '".$date." / %'";
						break;
					case "mssql":
						$strSql = "SELECT MAX(CAST(SUBSTRING(ACCOUNT_NUMBER, LEN('".$date." / ') + 1, LEN(ACCOUNT_NUMBER)) as INT)) as NUM_ID FROM b_sale_order WHERE ACCOUNT_NUMBER LIKE '".$date." / %'";
						break;
				}

				$dbres = $DB->Query($strSql, true);
				if ($arRes = $dbres->GetNext())
				{
					$numID = (intval($arRes["NUM_ID"]) > 0) ? $arRes["NUM_ID"] + 1 : 1;
					$value = $date." / ".$numID;
				}
				else
					$value = $date." / 1";

				break;

			default: // if unknown template

				$value = false;
				break;
		}

		return $value;
	}

	/**
	* The agent function. Moves reserved quantity back to the quantity field for each product
	* for orders which were placed earlier than specific date
	*
	* @return agent name string
	*/
	public static function ClearProductReservedQuantity()
	{
		global $DB, $USER;

		if (!is_object($USER))
			$USER = new CUser;

		$days_ago = (int)COption::GetOptionString("sale", "product_reserve_clear_period");

		if ($days_ago > 0)
		{
			$date = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), time() - $days_ago*24*60*60);

			$arFilter = array(
				"<=DATE_INSERT" => $date,
				"RESERVED" => "Y",
				"DEDUCTED" => "N",
				"PAYED" => "N",
				"ALLOW_DELIVERY" => "N",
				"CANCELED" => "N"
			);

			$dbRes = CSaleOrder::GetList(
				array(),
				$arFilter,
				false,
				false,
				array("ID", "RESERVED", "DATE_INSERT", "DEDUCTED", "PAYED", "CANCELED", "MARKED")
			);
			while ($arRes = $dbRes->GetNext())
			{
				foreach(GetModuleEvents("sale", "OnSaleBeforeReserveOrder", true) as $arEvent)
						if (ExecuteModuleEventEx($arEvent, array($arRes["ID"], "N", $arRes))===false)
							return false;

				// undoing reservation
				$res = CSaleBasket::OrderReservation($arRes["ID"], true);

				if (array_key_exists("ERROR", $res))
				{
					$errorMessage = '';
					foreach ($res["ERROR"] as $productId => $arError)
						$errorMessage .= " ".$arError["MESSAGE"];

					CSaleOrder::SetMark($arRes["ID"], Loc::getMessage("SKGB_RESERVE_ERROR", array("#MESSAGE#" => $errorMessage)));
				}
				else
				{
					if ($arRes["MARKED"] == "Y")
						CSaleOrder::UnsetMark($arRes["ID"]);
				}

				$res = CSaleOrder::Update($arRes["ID"], array("RESERVED" => "N"), false);

				foreach(GetModuleEvents("sale", "OnSaleReserveOrder", true) as $arEvent)
					ExecuteModuleEventEx($arEvent, Array($arRes["ID"], "N"));
			}
		}

		return "CSaleOrder::ClearProductReservedQuantity();";
	}

	/**
	* Function processes "COMPLETE_ORDERS" key in $arFilter for CSaleOrder::GetList() method
	*
	* @param mixed[]|string $values - next key value in the filter
	* @param string $key - key name
	* @param string $op - key operation modificator
	* @param string $opNegative - key condition is negative or not
	* @param mixed[] $field - field array of the key
	* @param mixed[] $fields - array of all fields
	* @param mixed[] $filter - filter array of the key
	* @return string|false
	*/
	protected static function ProcessCompleteOrdersParam($values, $key, $op, $opNegative, $field, $fields, $filter)
	{
		if($op != '=' && $op != 'IN')
			return false;

		global $DB;

		if(is_array($values) && !empty($values))
		{
			foreach($values as $k => $value)
				$values[$k] = "'".$DB->ForSql($value)."'";

			$values = '('.implode(',', $values).')';
		}
		elseif(!empty($values))
			$values = "'".$DB->ForSql($values)."'";
		else
			return false;

		if($opNegative == 'Y')
			return "( (NOT (".$fields["STATUS_ID"]["FIELD"]." ".$op." ".$values.")) AND (".$fields["CANCELED"]["FIELD"]." = 'N'))";
		else
			return "((".$fields["STATUS_ID"]["FIELD"]." ".$op." ".$values.") OR (".$fields["CANCELED"]["FIELD"]." = 'Y'))";
	}

	// returns reference of all properties of TYPE = LOCATION
	public static function getLocationPropertyInfo()
	{
		static $info;

		if($info === null)
		{
			$info = array();
			if(CSaleLocation::isLocationProMigrated())
			{
				$res = CSaleOrderProps::GetList(array(), array('TYPE' => 'LOCATION'), false, false, array('ID', 'CODE'));
				while($item = $res->fetch())
				{
					$info['ID'][$item['ID']] = $item['CODE'];
					$info['CODE'][$item['CODE']] = $item['ID'];
				}
			}
		}

		return $info;
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function getRoundFields()
	{
		return array(
			'ORDER_PRICE',
			'DISCOUNT_PRICE',
			'VAT_RATE',
			'VAT_SUM',
		);
	}
}
?>