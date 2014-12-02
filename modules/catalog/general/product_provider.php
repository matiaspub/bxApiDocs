<?
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!Loader::includeModule('sale'))
	return false;

Loc::loadMessages(__FILE__);


/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductprovider/index.php
 * @author Bitrix
 */
class CCatalogProductProvider implements IBXSaleProductProvider
{
	protected static $arOneTimeCoupons = array();
	protected static $arCouponsType = array();
	protected static $clearAutoCache = array();

	
	/**
	* <p>Метод возвращает информацию о товаре из каталога.</p>
	*
	*
	*
	*
	* @param array $arParams  Ассоциативный массив параметров товара, ключами в котором
	* являются названия параметров, а значениями - соответствующие
	* значения: <ul> <li> <b>PRODUCT_ID</b> - идентификатор товара;</li> <li> <b>QUANTITY</b> -
	* количество товара;</li> <li> <b>USER_ID</b> - идентификатор пользователя;</li>
	* <li> <b>SITE_ID</b> - код сайта;</li> <li> <b>CHECK_QUANTITY</b> - (Y|N) флаг выполнения
	* проверки доступности для покупки указанного количества
	* товара;</li> <li> <b>CHECK_PRICE</b> - (Y|N) флаг возвращения информации о цене
	* товара (флаг использовать не нужно, если для товара установлена
	* особая цена);</li> <li> <b>CHECK_COUPONS</b> - (Y|N) флаг обработки купонов;</li> <li>
	* <b>RENEWAL</b> - (Y|N) флаг продления подписки на товар.</li> </ul>
	*
	*
	*
	* @return array <p>Метод возвращает ассоциативный массив параметров товара с
	* ключами:</p> <ul> <li> <b>NAME</b> - название товара;</li> <li> <b>CAN_BUY</b> - (Y|N) флаг
	* возможности купить товар;</li> <li> <b>DETAIL_PAGE_URL</b> - ссылка на страницу с
	* информацией о товаре;</li> <li> <b>BARCODE_MULTI</b> - Y|N) определяет каждый ли
	* экземпляр товара имеет собственный штрихкод;</li> <li> <b>WEIGHT</b> - вес
	* товара;</li> <li> <b>DIMENSIONS</b> - размеры товара (ширина, высота и длина);</li>
	* <li> <b>TYPE</b> - код, обозначающий принадлежность товара к комплектам.
	* Может быть пустым;</li> <li> <b>QUANTITY</b> - количество товара.</li> </ul> <p>Если
	* в массиве <i>arParams</i> указан <i>"CHECK_PRICE" =&gt; "Y"</i>, то дополнительно
	* будут возвращены следующие ключи:</p> <ul> <li> <b>PRICE</b> - цена;</li> <li>
	* <b>PRODUCT_PRICE_ID</b> - идентификатор цены товара;</li> <li> <b>CURRENCY</b> - валюта, в
	* которой задана цена;</li> <li> <b>DISCOUNT_PRICE</b> - величина скидки;</li> <li>
	* <b>NOTES</b> - особые заметки, например, тип цены;</li> <li> <b>VAT_RATE</b> -
	* величина налога на товар.</li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductprovider/getproductdata.php
	* @author Bitrix
	*/
	public static function GetProductData($arParams)
	{
		if (!is_set($arParams, "QUANTITY") || doubleval($arParams["QUANTITY"]) <= 0)
			$arParams["QUANTITY"] = 0;

		if (!is_set($arParams, "RENEWAL") || $arParams["RENEWAL"] != "Y")
			$arParams["RENEWAL"] = "N";

		if (!is_set($arParams, "USER_ID") || intval($arParams["USER_ID"]) <= 0)
			$arParams["USER_ID"] = 0;

		if (!is_set($arParams["SITE_ID"]))
			$arParams["SITE_ID"] = false;

		if (!is_set($arParams["CHECK_QUANTITY"]) || $arParams["CHECK_QUANTITY"] != "N")
			$arParams["CHECK_QUANTITY"] = "Y";

		if (!is_set($arParams["CHECK_PRICE"]) || $arParams["CHECK_PRICE"] != "N")
			$arParams["CHECK_PRICE"] = "Y";

		if (!array_key_exists('CHECK_COUPONS', $arParams) || 'N' != $arParams['CHECK_COUPONS'])
			$arParams['CHECK_COUPONS'] = 'Y';

		$arParams['BASKET_ID'] = (string)(isset($arParams['BASKET_ID']) ? $arParams['BASKET_ID'] : '0');

		$productID = (int)$arParams["PRODUCT_ID"];
		$quantity  = doubleval($arParams["QUANTITY"]);
		$renewal   = (($arParams["RENEWAL"] == "Y") ? "Y" : "N");
		$intUserID = (int)$arParams["USER_ID"];
		if (0 > $intUserID)
			$intUserID = 0;
		$strSiteID = $arParams["SITE_ID"];

		global $USER;
		global $APPLICATION;

		$arResult = array();

		static $arUserCache = array();
		if (0 < $intUserID)
		{
			if (!isset($arUserCache[$intUserID]))
			{
				$by = 'ID';
				$order = 'DESC';
				$rsUsers = CUser::GetList($by, $order, array("ID_EQUAL_EXACT"=>$intUserID),array('FIELDS' => array('ID')));
				if ($arUser = $rsUsers->Fetch())
				{
					$arUser['ID'] = intval($arUser['ID']);
					$arUserCache[$arUser['ID']] = CUser::GetUserGroup($arUser['ID']);
				}
				else
				{
					$intUserID = 0;
					return $arResult;
				}
			}

			$dbIBlockElement = CIBlockElement::GetList(
				array(),
				array(
					"ID" => $productID,
					"ACTIVE" => "Y",
					"ACTIVE_DATE" => "Y",
					"CHECK_PERMISSIONS" => "N",
					),
				false,
				false,
				array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL')
				);
			if(!($arProduct = $dbIBlockElement->GetNext()))
				return $arResult;
			if ('E' == CIBlock::GetArrayByID($arProduct['IBLOCK_ID'], "RIGHTS_MODE"))
			{
				$arUserRights = CIBlockElementRights::GetUserOperations($productID, $intUserID);
				if (empty($arUserRights))
				{
					return $arResult;
				}
				elseif (!is_array($arUserRights) || !array_key_exists('element_read', $arUserRights))
				{
					return $arResult;
				}
			}
			else
			{
				if ('R' > CIBlock::GetPermission($arProduct['IBLOCK_ID'], $intUserID))
				{
					return $arResult;
				}
			}
		}
		else
		{
			$dbIBlockElement = CIBlockElement::GetList(
				array(),
				array(
					"ID" => $productID,
					"ACTIVE" => "Y",
					"ACTIVE_DATE" => "Y",
					"CHECK_PERMISSIONS" => "Y",
					"MIN_PERMISSION" => "R",
					),
				false,
				false,
				array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL')
				);
			if(!($arProduct = $dbIBlockElement->GetNext()))
				return $arResult;
		}

		$rsCatalogs = CCatalog::GetList(
			array(),
			array('IBLOCK_ID' => $arProduct["IBLOCK_ID"]),
			false,
			false,
			array(
				'IBLOCK_ID',
				'SUBSCRIPTION'
			)
		);
		if (!($arCatalog = $rsCatalogs->Fetch()))
		{
			return $arResult;
		}
		if ('Y' == $arCatalog["SUBSCRIPTION"])
		{
			$quantity = 1;
		}

		$dblQuantity = 0;
		$boolQuantity = false;

		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('ID' => $productID),
			false,
			false,
			array(
				'ID',
				'CAN_BUY_ZERO',
				'QUANTITY_TRACE',
				'QUANTITY',
				'WEIGHT',
				'WIDTH',
				'HEIGHT',
				'LENGTH',
				'BARCODE_MULTI',
				'TYPE'
			)
		);

		if ($arCatalogProduct = $rsProducts->Fetch())
		{
			$dblQuantity = doubleval($arCatalogProduct["QUANTITY"]);
			$boolQuantity = ('Y' != $arCatalogProduct["CAN_BUY_ZERO"] && 'Y' == $arCatalogProduct["QUANTITY_TRACE"]);

			if ($arParams["CHECK_QUANTITY"] == "Y" && $boolQuantity && 0 >= $dblQuantity)
			{
				$APPLICATION->ThrowException(Loc::getMessage("CATALOG_NO_QUANTITY_PRODUCT", array("#NAME#" => htmlspecialcharsbx($arProduct["~NAME"]))), "CATALOG_NO_QUANTITY_PRODUCT");
				return $arResult;
			}
		}
		else
		{
			$APPLICATION->ThrowException(Loc::getMessage("CATALOG_ERR_NO_PRODUCT"), "CATALOG_NO_QUANTITY_PRODUCT");
			return $arResult;
		}

		if ($arParams["CHECK_PRICE"] == "Y")
		{
			$strBasketProductID = $productID.'_'.$arParams['BASKET_ID'];
			$arCoupons = array();
			if (0 < $intUserID)
			{
				if ('Y' == $arParams['CHECK_COUPONS'])
					$arCoupons = CCatalogDiscountCoupon::GetCouponsByManage($intUserID);

				CCatalogDiscountSave::SetDiscountUserID($intUserID);
			}
			else
			{
				if ('Y' == $arParams['CHECK_COUPONS'])
					$arCoupons = CCatalogDiscountCoupon::GetCoupons();
			}


			$boolChangeCoupons = false;
			$arNewCoupons = array();
			if (!empty($arCoupons) && is_array($arCoupons) && !empty(self::$arOneTimeCoupons))
			{
				foreach ($arCoupons as $key => $coupon)
				{
					if (isset(self::$arOneTimeCoupons[$coupon]))
					{
						if (self::$arOneTimeCoupons[$coupon] == $strBasketProductID)
						{
							$boolChangeCoupons = true;
							$arNewCoupons[] = $coupon;
						}
						else
						{
							unset($arCoupons[$key]);
						}
					}
				}
			}
			if ($boolChangeCoupons)
				$arCoupons = $arNewCoupons;

			$arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, (0 < $intUserID ? $arUserCache[$intUserID] : $USER->GetUserGroupArray()), $renewal, array(), (0 < $intUserID ? $strSiteID : false), $arCoupons);

			if (empty($arPrice))
			{
				if ($nearestQuantity = CCatalogProduct::GetNearestQuantityPrice($productID, $quantity, (0 < $intUserID ? $arUserCache[$intUserID] : $USER->GetUserGroupArray())))
				{
					$quantity = $nearestQuantity;
					$arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, (0 < $intUserID ? $arUserCache[$intUserID] : $USER->GetUserGroupArray()), $renewal, array(), (0 < $intUserID ? $strSiteID : false), $arCoupons);
				}
			}

			if (empty($arPrice))
			{
				if (0 < $intUserID)
				{
					CCatalogDiscountSave::ClearDiscountUserID();
				}
				return $arResult;
			}

			$boolDiscountVat = ('N' != COption::GetOptionString('catalog', 'discount_vat', 'Y'));

			$currentPrice = $arPrice["PRICE"]["PRICE"];
			$currentDiscount = 0.0;

			$arPrice['PRICE']['ORIG_VAT_INCLUDED'] = $arPrice['PRICE']['VAT_INCLUDED'];

			if ($boolDiscountVat)
			{
				if ('N' == $arPrice['PRICE']['VAT_INCLUDED'])
				{
					$currentPrice *= (1 + $arPrice['PRICE']['VAT_RATE']);
					$arPrice['PRICE']['VAT_INCLUDED'] = 'Y';
				}
			}
			else
			{
				if ('Y' == $arPrice['PRICE']['VAT_INCLUDED'])
				{
					$currentPrice /= (1 + $arPrice['PRICE']['VAT_RATE']);
					$arPrice['PRICE']['VAT_INCLUDED'] = 'N';
				}
			}

			$arDiscountList = array();

			if (!empty($arPrice["DISCOUNT_LIST"]))
			{
				$dblStartPrice = $currentPrice;

				foreach ($arPrice["DISCOUNT_LIST"] as &$arOneDiscount)
				{
					switch ($arOneDiscount['VALUE_TYPE'])
					{
					case CCatalogDiscount::TYPE_FIX:
						if ($arOneDiscount['CURRENCY'] == $arPrice["PRICE"]["CURRENCY"])
							$currentDiscount = $arOneDiscount['VALUE'];
						else
							$currentDiscount = CCurrencyRates::ConvertCurrency($arOneDiscount["VALUE"], $arOneDiscount["CURRENCY"], $arPrice["PRICE"]["CURRENCY"]);
						$currentPrice = $currentPrice - $currentDiscount;
						break;
					case CCatalogDiscount::TYPE_PERCENT:
						$currentDiscount = $currentPrice*$arOneDiscount["VALUE"]/100.0;
						if (0 < $arOneDiscount['MAX_DISCOUNT'])
						{
							if ($arOneDiscount['CURRENCY'] == $arPrice["PRICE"]["CURRENCY"])
								$dblMaxDiscount = $arOneDiscount['MAX_DISCOUNT'];
							else
								$dblMaxDiscount = CCurrencyRates::ConvertCurrency($arOneDiscount['MAX_DISCOUNT'], $arOneDiscount["CURRENCY"], $arPrice["PRICE"]["CURRENCY"]);;
							if ($currentDiscount > $dblMaxDiscount)
								$currentDiscount = $dblMaxDiscount;
						}
						$currentPrice = $currentPrice - $currentDiscount;
						break;
					case CCatalogDiscount::TYPE_SALE:
						if ($arOneDiscount['CURRENCY'] == $arPrice["PRICE"]["CURRENCY"])
							$currentPrice = $arOneDiscount['VALUE'];
						else
							$currentPrice = CCurrencyRates::ConvertCurrency($arOneDiscount['VALUE'], $arOneDiscount["CURRENCY"], $arPrice["PRICE"]["CURRENCY"]);
						break;
					}

					$arOneList = array(
						'ID' => $arOneDiscount['ID'],
						'NAME' => $arOneDiscount['NAME'],
						'COUPON' => '',
						'COUPON_TYPE' => '',
						'MODULE_ID' => 'catalog',
						'TYPE' => $arOneDiscount['TYPE'],
						'VALUE' => $arOneDiscount['VALUE'],
						'VALUE_TYPE' => $arOneDiscount['VALUE_TYPE'],
						'CURRENCY' => $arOneDiscount['CURRENCY'],
						'HANDLERS' => (isset($arOneDiscount['HANDLERS']) ? $arOneDiscount['HANDLERS'] : array())
					);

					if ($arOneDiscount['COUPON'])
					{
						$arOneList['COUPON'] = $arOneDiscount['COUPON'];
						$arOneList['COUPON_TYPE'] = $arOneDiscount['COUPON_ONE_TIME'];
						if ($arOneDiscount['COUPON_ONE_TIME'] == CCatalogDiscountCoupon::TYPE_ONE_TIME)
						{
							self::$arOneTimeCoupons[$arOneDiscount['COUPON']] = $strBasketProductID;
						}
					}
					$arDiscountList[] = $arOneList;
				}
				if (isset($arOneDiscount))
					unset($arOneDiscount);

				$currentDiscount = $dblStartPrice - $currentPrice;
			}

			if (empty($arPrice["PRICE"]["CATALOG_GROUP_NAME"]))
			{
				if (!empty($arPrice["PRICE"]["CATALOG_GROUP_ID"]))
				{
					$rsCatGroups = CCatalogGroup::GetListEx(array(),array('ID' => $arPrice["PRICE"]["CATALOG_GROUP_ID"]),false,false,array('ID','NAME','NAME_LANG'));
					if ($arCatGroup = $rsCatGroups->Fetch())
					{
						$arPrice["PRICE"]["CATALOG_GROUP_NAME"] = (!empty($arCatGroup['NAME_LANG']) ? $arCatGroup['NAME_LANG'] : $arCatGroup['NAME']);
					}
				}
			}

			if (!$boolDiscountVat)
			{
				$currentPrice *= (1 + $arPrice['PRICE']['VAT_RATE']);
				$currentDiscount *= (1 + $arPrice['PRICE']['VAT_RATE']);
				$arPrice['PRICE']['VAT_INCLUDED'] = 'Y';
			}
		}
		else
		{
			$rsVAT = CCatalogProduct::GetVATInfo($productID);
			if ($arVAT = $rsVAT->Fetch())
			{
				$vatRate = doubleval($arVAT['RATE'] * 0.01);
			}
			else
			{
				$vatRate = 0.0;
			}
		}

		$arResult = array(
			"NAME" => $arProduct["~NAME"],
			"CAN_BUY" => "Y",
			"DETAIL_PAGE_URL" => $arProduct['~DETAIL_PAGE_URL'],
			"BARCODE_MULTI" => $arCatalogProduct["BARCODE_MULTI"],
			"WEIGHT" => floatval($arCatalogProduct['WEIGHT']),
			"DIMENSIONS" => serialize(array(
				"WIDTH" => $arCatalogProduct["WIDTH"],
				"HEIGHT" => $arCatalogProduct["HEIGHT"],
				"LENGTH" => $arCatalogProduct["LENGTH"]
			)),
			"TYPE" => ($arCatalogProduct["TYPE"] == CCatalogProduct::TYPE_SET) ? CCatalogProductSet::TYPE_SET : NULL
		);

		if ($arParams["CHECK_QUANTITY"] == "Y")
			$arResult["QUANTITY"] = ($boolQuantity && $dblQuantity < $quantity) ? $dblQuantity : $quantity;
		else
			$arResult["QUANTITY"] = $arParams["QUANTITY"];

		if ($arParams["CHECK_QUANTITY"] == "Y" && $boolQuantity && $dblQuantity < $quantity)
			$APPLICATION->ThrowException(Loc::getMessage("CATALOG_QUANTITY_NOT_ENOGH", array("#NAME#" => htmlspecialcharsbx($arProduct["~NAME"]), "#CATALOG_QUANTITY#" => $arCatalogProduct["QUANTITY"], "#QUANTITY#" => $quantity)), "CATALOG_QUANTITY_NOT_ENOGH");

		if (0 < $intUserID)
		{
			CCatalogDiscountSave::ClearDiscountUserID();
		}

		if ($arParams["CHECK_PRICE"] == "Y")
		{
			$arResult = array_merge($arResult, array(
				"PRODUCT_PRICE_ID" => $arPrice["PRICE"]["ID"],
				"PRICE" => $currentPrice,
				"CURRENCY" => $arPrice["PRICE"]["CURRENCY"],
				"DISCOUNT_PRICE" => $currentDiscount,
				"NOTES" => $arPrice["PRICE"]["CATALOG_GROUP_NAME"],
				"VAT_RATE" => $arPrice["PRICE"]["VAT_RATE"],
				"DISCOUNT_VALUE" => 0,
				"DISCOUNT_NAME" => "",
				"DISCOUNT_COUPON" => "",
				"DISCOUNT_LIST" => array()
			));

			if (!empty($arDiscountList))
			{
				$arResult['DISCOUNT_LIST'] = $arDiscountList;
				$arResult["DISCOUNT_VALUE"] = (100*$currentDiscount/($currentDiscount+$currentPrice))."%";
				$arResult["DISCOUNT_NAME"] = "[".$arPrice["DISCOUNT"]["ID"]."] ".$arPrice["DISCOUNT"]["NAME"];
				if (!empty($arPrice["DISCOUNT"]["COUPON"]))
				{
					$arResult["DISCOUNT_COUPON"] = $arPrice["DISCOUNT"]["COUPON"];
				}
			}
		}
		else
		{
			$arResult["VAT_RATE"] = $vatRate;
		}

		return $arResult;
	}

	
	/**
	* <p>Метод метод возвращает информацию о товаре из каталоге, вызывается после создания заказа. В отличие от <a href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductprovider/getproductdata.php">GetProductData</a> по-другому обрабатывает информацию о купонах.</p>
	*
	*
	*
	*
	* @param array $arParams  Ассоциативный массив параметров товара, ключами в котором
	* являются названия параметров, а значениями - соответствующие
	* значения: <ul> <li> <b>PRODUCT_ID</b> - идентификатор товара;</li> <li> <b>QUANTITY</b> -
	* количество товара;</li> <li> <b>USER_ID</b> - идентификатор пользователя;</li>
	* <li> <b>SITE_ID</b> - код сайта;</li> <li> <b>CHECK_QUANTITY</b> - (Y|N) флаг выполнения
	* проверки доступности для покупки указанного количества
	* товара;</li> <li> <b>RENEWAL</b> - (Y|N) флаг продления подписки на товар.</li> </ul>
	*
	*
	*
	* @return array <p>Метод возвращает ассоциативный массив параметров товара с
	* ключами:</p> <ul> <li> <b>NAME</b> - название товара;</li> <li> <b>CAN_BUY</b> - (Y|N) флаг
	* возможности купить товар;</li> <li> <b>DETAIL_PAGE_URL</b> - ссылка на страницу с
	* информацией о товаре;</li> <li> <b>BARCODE_MULTI</b> - Y|N) определяет каждый ли
	* экземпляр товара имеет собственный штрихкод;</li> <li> <b>WEIGHT</b> - вес
	* товара;</li> <li> <b>DIMENSIONS</b> - размеры товара (ширина, высота и длина);</li>
	* <li> <b>TYPE</b> - код, обозначающий принадлежность товара к комплектам.
	* Может быть пустым;;</li> <li> <b>QUANTITY</b> - количество товара;</li> <li> <b>PRICE</b>
	* - цена;</li> <li> <b>PRODUCT_PRICE_ID</b> - идентификатор цены товара;</li> <li>
	* <b>CURRENCY</b> - валюта, в которой задана цена;</li> <li> <b>DISCOUNT_PRICE</b> -
	* величина скидки;</li> <li> <b>NOTES</b> - особые заметки, например, тип
	* цены;</li> <li> <b>VAT_RATE</b> - величина налога на товар.</li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductprovider/orderproduct.php
	* @author Bitrix
	*/
	public static function OrderProduct($arParams)
	{
		if (!is_set($arParams, "RENEWAL") || $arParams["RENEWAL"] != "Y")
			$arParams["RENEWAL"] = "N";

		if (!is_set($arParams, "USER_ID") || IntVal($arParams["USER_ID"]) <= 0)
			$arParams["USER_ID"] = 0;

		if (!is_set($arParams["SITE_ID"]))
			$arParams["SITE_ID"] = false;

		if (!is_set($arParams["CHECK_QUANTITY"]) || $arParams["CHECK_QUANTITY"] != "N")
			$arParams["CHECK_QUANTITY"] = "Y";

		global $USER;

		$productID = intval($arParams["PRODUCT_ID"]);
		$quantity = doubleval($arParams["QUANTITY"]);
		$renewal = (($arParams["RENEWAL"] == "Y") ? "Y" : "N");
		$strSiteID = $arParams["SITE_ID"];

		$intUserID = intval($arParams["USER_ID"]);
		if (0 > $intUserID)
			$intUserID = 0;

		$arResult = array();

		static $arUserCache = array();
		if (0 < $intUserID)
		{
			if (!isset($arUserCache[$intUserID]))
			{
				$by = 'ID';
				$order = 'DESC';
				$rsUsers = CUser::GetList($by, $order, array("ID_EQUAL_EXACT"=>$intUserID),array('FIELDS' => array('ID')));
				if ($arUser = $rsUsers->Fetch())
				{
					$arUser['ID'] = intval($arUser['ID']);
					$arUserCache[$arUser['ID']] = CUser::GetUserGroup($arUser['ID']);
				}
				else
				{
					$intUserID = 0;
					return $arResult;
				}
			}

			$dbIBlockElement = CIBlockElement::GetList(
				array(),
				array(
						"ID" => $productID,
						"ACTIVE" => "Y",
						"ACTIVE_DATE" => "Y",
						"CHECK_PERMISSION" => "N",
					),
				false,
				false,
				array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL')
				);
			if(!($arProduct = $dbIBlockElement->GetNext()))
				return $arResult;

			if ('E' == CIBlock::GetArrayByID($arProduct['IBLOCK_ID'], "RIGHTS_MODE"))
			{
				$arUserRights = CIBlockElementRights::GetUserOperations($productID,$intUserID);
				if (empty($arUserRights))
				{
					return $arResult;
				}
				elseif (!is_array($arUserRights) || !array_key_exists('element_read',$arUserRights))
				{
					return $arResult;
				}
			}
			else
			{
				if ('R' > CIBlock::GetPermission($arProduct['IBLOCK_ID'], $intUserID))
				{
					return $arResult;
				}
			}
		}
		else
		{
			$dbIBlockElement = CIBlockElement::GetList(
				array(),
				array(
					"ID" => $productID,
					"ACTIVE" => "Y",
					"ACTIVE_DATE" => "Y",
					"CHECK_PERMISSIONS" => "Y",
					"MIN_PERMISSION" => "R",
				),
				false,
				false,
				array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL')
			);
			if(!($arProduct = $dbIBlockElement->GetNext()))
				return $arResult;
		}

		$rsProducts = CCatalogProduct::GetList(
		array(),
		array('ID' => $productID),
		false,
		false,
		array(
			'ID',
			'CAN_BUY_ZERO',
			'QUANTITY_TRACE',
			'QUANTITY',
			'WEIGHT',
			'WIDTH',
			'HEIGHT',
			'LENGTH',
			'BARCODE_MULTI',
			'TYPE'
			)
		);

		if ($arCatalogProduct = $rsProducts->Fetch())
		{
			$arCatalogProduct["QUANTITY"] = (double)$arCatalogProduct["QUANTITY"];
			if ($arParams["CHECK_QUANTITY"] == "Y")
			{
				if (
					'Y' != $arCatalogProduct["CAN_BUY_ZERO"]
					&& 'Y' == $arCatalogProduct["QUANTITY_TRACE"]
					&& ($arCatalogProduct["QUANTITY"] <= 0 || $quantity > $arCatalogProduct["QUANTITY"])
				)
				{
					return $arResult;
				}
			}
		}
		else
		{
			return $arResult;
		}

		if (0 < $intUserID)
		{
			$arCoupons = CCatalogDiscountCoupon::GetCouponsByManage($intUserID);
			CCatalogDiscountSave::SetDiscountUserID($intUserID);
		}
		else
		{
			$arCoupons = CCatalogDiscountCoupon::GetCoupons();
		}

		$arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, (0 < $intUserID ? $arUserCache[$intUserID] : $USER->GetUserGroupArray()), $renewal, array(), (0 < $intUserID ? $strSiteID : false), $arCoupons);

		if (empty($arPrice))
		{
			if ($nearestQuantity = CCatalogProduct::GetNearestQuantityPrice($productID, $quantity, (0 < $intUserID ? $arUserCache[$intUserID] : $USER->GetUserGroupArray())))
			{
				$quantity = $nearestQuantity;
				$arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, (0 < $intUserID ? $arUserCache[$intUserID] : $USER->GetUserGroupArray()), $renewal, array(), (0 < $intUserID ? $strSiteID : false), $arCoupons);
			}
		}

		if (empty($arPrice))
		{
			if (0 < $intUserID)
			{
				CCatalogDiscountSave::ClearDiscountUserID();
			}
			return $arResult;
		}

		$boolDiscountVat = ('N' != COption::GetOptionString('catalog', 'discount_vat', 'Y'));

		$currentPrice = $arPrice["PRICE"]["PRICE"];
		$currentDiscount = 0.0;

		if ($boolDiscountVat)
		{
			if ('N' == $arPrice['PRICE']['VAT_INCLUDED'])
			{
				$currentPrice *= (1 + $arPrice['PRICE']['VAT_RATE']);
				$arPrice['PRICE']['VAT_INCLUDED'] = 'Y';
			}
		}
		else
		{
			if ('Y' == $arPrice['PRICE']['VAT_INCLUDED'])
			{
				$currentPrice /= (1 + $arPrice['PRICE']['VAT_RATE']);
				$arPrice['PRICE']['VAT_INCLUDED'] = 'N';
			}
		}

		$arDiscountList = array();
		$arCouponList = array();

		if (!empty($arPrice["DISCOUNT_LIST"]))
		{
			$dblStartPrice = $currentPrice;

			foreach ($arPrice["DISCOUNT_LIST"] as &$arOneDiscount)
			{
				switch ($arOneDiscount['VALUE_TYPE'])
				{
				case CCatalogDiscount::TYPE_FIX:
					if ($arOneDiscount['CURRENCY'] == $arPrice["PRICE"]["CURRENCY"])
						$currentDiscount = $arOneDiscount['VALUE'];
					else
						$currentDiscount = CCurrencyRates::ConvertCurrency($arOneDiscount["VALUE"], $arOneDiscount["CURRENCY"], $arPrice["PRICE"]["CURRENCY"]);
					$currentPrice = $currentPrice - $currentDiscount;
					break;
				case CCatalogDiscount::TYPE_PERCENT:
					$currentDiscount = $currentPrice*$arOneDiscount["VALUE"]/100.0;
					if (0 < $arOneDiscount['MAX_DISCOUNT'])
					{
						if ($arOneDiscount['CURRENCY'] == $arPrice["PRICE"]["CURRENCY"])
							$dblMaxDiscount = $arOneDiscount['MAX_DISCOUNT'];
						else
							$dblMaxDiscount = CCurrencyRates::ConvertCurrency($arOneDiscount['MAX_DISCOUNT'], $arOneDiscount["CURRENCY"], $arPrice["PRICE"]["CURRENCY"]);;
						if ($currentDiscount > $dblMaxDiscount)
							$currentDiscount = $dblMaxDiscount;
					}
					$currentPrice = $currentPrice - $currentDiscount;
					break;
				case CCatalogDiscount::TYPE_SALE:
					if ($arOneDiscount['CURRENCY'] == $arPrice["PRICE"]["CURRENCY"])
						$currentPrice = $arOneDiscount['VALUE'];
					else
						$currentPrice = CCurrencyRates::ConvertCurrency($arOneDiscount['VALUE'], $arOneDiscount["CURRENCY"], $arPrice["PRICE"]["CURRENCY"]);
					break;
				}

				$arOneList = array(
					'ID' => $arOneDiscount['ID'],
					'NAME' => $arOneDiscount['NAME'],
					'COUPON' => '',
					'COUPON_TYPE' => '',
					'MODULE_ID' => 'catalog',
					'TYPE' => $arOneDiscount['TYPE'],
					'VALUE' => $arOneDiscount['VALUE'],
					'VALUE_TYPE' => $arOneDiscount['VALUE_TYPE'],
					'CURRENCY' => $arOneDiscount['CURRENCY'],
					'HANDLERS' => (isset($arOneDiscount['HANDLERS']) ? $arOneDiscount['HANDLERS'] : array())
				);

				if ($arOneDiscount['COUPON'])
				{
					$arOneList['COUPON'] = $arOneDiscount['COUPON'];
					$arOneList['COUPON_TYPE'] = $arOneDiscount['COUPON_ONE_TIME'];
					$arCouponList[] = $arOneDiscount['COUPON'];
				}

				$arDiscountList[] = $arOneList;
			}
			if (isset($arOneDiscount))
				unset($arOneDiscount);

			$currentDiscount = $dblStartPrice - $currentPrice;
		}

		if (empty($arPrice["PRICE"]["CATALOG_GROUP_NAME"]))
		{
			if (!empty($arPrice["PRICE"]["CATALOG_GROUP_ID"]))
			{
				$rsCatGroups = CCatalogGroup::GetListEx(array(),array('ID' => $arPrice["PRICE"]["CATALOG_GROUP_ID"]),false,false,array('ID','NAME','NAME_LANG'));
				if ($arCatGroup = $rsCatGroups->Fetch())
				{
					$arPrice["PRICE"]["CATALOG_GROUP_NAME"] = (!empty($arCatGroup['NAME_LANG']) ? $arCatGroup['NAME_LANG'] : $arCatGroup['NAME']);
				}
			}
		}

		if (!$boolDiscountVat)
		{
			$currentPrice *= (1 + $arPrice['PRICE']['VAT_RATE']);
			$currentDiscount *= (1 + $arPrice['PRICE']['VAT_RATE']);
			$arPrice['PRICE']['VAT_INCLUDED'] = 'Y';
		}


		$arResult = array(
			"PRODUCT_PRICE_ID" => $arPrice["PRICE"]["ID"],
			"PRICE" => $currentPrice,
			"VAT_RATE" => $arPrice['PRICE']['VAT_RATE'],
			"CURRENCY" => $arPrice["PRICE"]["CURRENCY"],
			"WEIGHT" => floatval($arCatalogProduct["WEIGHT"]),
			"DIMENSIONS" => serialize(array(
				"WIDTH" => $arCatalogProduct["WIDTH"],
				"HEIGHT" => $arCatalogProduct["HEIGHT"],
				"LENGTH" => $arCatalogProduct["LENGTH"]
			)),
			"NAME" => $arProduct["~NAME"],
			"CAN_BUY" => "Y",
			"DETAIL_PAGE_URL" => $arProduct['~DETAIL_PAGE_URL'],
			"NOTES" => $arPrice["PRICE"]["CATALOG_GROUP_NAME"],
			"DISCOUNT_PRICE" => $currentDiscount,
			"TYPE" => ($arCatalogProduct["TYPE"] == CCatalogProduct::TYPE_SET) ? CCatalogProductSet::TYPE_SET : NULL,
			"DISCOUNT_VALUE" => 0,
			"DISCOUNT_NAME" => "",
			"DISCOUNT_COUPON" => "",
			"DISCOUNT_LIST" => array()
		);

		if ($arParams["CHECK_QUANTITY"] == "Y")
			$arResult["QUANTITY"] = $quantity;
		else
			$arResult["QUANTITY"] = $arParams["QUANTITY"];

		if (!empty($arPrice["DISCOUNT_LIST"]))
		{
			$arResult['DISCOUNT_LIST'] = $arDiscountList;
			$arResult["DISCOUNT_VALUE"] = (100*$currentDiscount/($currentDiscount+$currentPrice))."%";
			$arResult["DISCOUNT_NAME"] = "[".$arPrice["DISCOUNT"]["ID"]."] ".$arPrice["DISCOUNT"]["NAME"];

			if (!empty($arPrice["DISCOUNT"]["COUPON"]))
			{
				$arResult["DISCOUNT_COUPON"] = $arPrice["DISCOUNT"]["COUPON"];
			}

			if (!empty($arCouponList))
			{
				$mxApply = CCatalogDiscountCoupon::CouponApply($intUserID, $arCouponList);
			}
		}

		if (0 < $intUserID)
		{
			CCatalogDiscountSave::ClearDiscountUserID();
		}
		return $arResult;
	}

	// in case product provider class is used,
	// instead of this method quantity is changed with ReserveProduct and DeductProduct methods
	public static function CancelProduct($arParams)
	{
		return true;
	}

	
	/**
	* <p>Метод возвращает массив информации о товаре для создания/продления подписки. Вызывается после разрешения доставки.</p>
	*
	*
	*
	*
	* @param array $arParams  Ассоциативный массив параметров с ключами: <ul> <li> <b>PRODUCT_ID</b> -
	* идентификатор товара;</li> <li> <b>USER_ID</b> - идентификатор
	* пользователя;</li> <li> <b>PAID</b> - (Y|N) флаг оплаты заказа;</li> <li> <b>ORDER_ID</b> -
	* идентификатор заказа.</li> </ul>
	*
	*
	*
	* @return mixed <p>Возвращается ассоциативный массив с ключами:</p> <ul> <li> <b>USER_ID</b> -
	* идентификатор пользователя;</li> <li> <b>MODULE</b> - название модуля, право
	* на доступ к контенту которого продлевается;</li> <li> <b>PRODUCT_ID</b> -
	* идентификатор товара;</li> <li> <b>PRODUCT_NAME</b> - название товара;</li> <li>
	* <b>PRODUCT_URL</b> - ссылка на страницу с информацией о товаре;</li> <li>
	* <b>PRODUCT_PRICE_ID</b> - идентификатор цены товара;</li> <li> <b>PRICE_TYPE</b> - тип
	* цены;</li> <li> <b>RECUR_SCHEME_TYPE</b> - тип периода подписки ("H" - час, "D" - сутки,
	* "W" - неделя, "M" - месяц, "Q" - квартал, "S" - полугодие, "Y" - год);</li> <li>
	* <b>RECUR_SCHEME_LENGTH</b> - длина периода подписки;</li> <li> <b>PRICE</b> - цена;</li> <li>
	* <b>CURRENCY</b> - валюта, в которой задана цена;</li> <li> <b>CANCELED</b> - (Y|N) флаг
	* отмены продления подписки;</li> <li> <b>CANCELED_REASON</b> - причина отмены
	* продления подписки;</li> <li> <b>PRODUCT_PROVIDER_CLASS</b> - класс-провайдер
	* модуля, предоставляющего товар;</li> <li> <b>DESCRIPTION</b> - описание
	* подписки;</li> <li> <b>PRIOR_DATE</b> - дата последнего продления;</li> <li>
	* <b>NEXT_DATE</b> - дата очередного продления.</li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductprovider/deliverproduct.php
	* @author Bitrix
	*/
	public static function DeliverProduct($arParams)
	{
		return CatalogPayOrderCallback(
			$arParams["PRODUCT_ID"],
			$arParams["USER_ID"],
			$arParams["PAID"],
			$arParams["ORDER_ID"]
		);
	}

	
	/**
	* <p>Метод возвращает информацию о просмотренном товаре для сохранения ее в магазине.</p>
	*
	*
	*
	*
	* @param array $arParams  Ассоциативный массив параметров товара, ключами в котором
	* являются названия параметров, а значениями - соответствующие
	* значения: <ul> <li> <b>PRODUCT_ID</b> - идентификатор товара;</li> <li> <b>USER_ID</b> -
	* идентификатор пользователя;</li> <li> <b>SITE_ID</b> - код сайта.</li> </ul>
	*
	*
	*
	* @return array <p>Метод возвращает ассоциативный массив параметров товара с
	* ключами:</p> <ul> <li> <b>PREVIEW_PICTURE</b> - ID файла картинки анонса товара;</li>
	* <li> <b>DETAIL_PICTURE</b> - ID файла с детальной картинкой товара;</li> <li>
	* <b>PRODUCT_PRICE_ID</b> - идентификатор цены товара;</li> <li> <b>PRICE</b> - величина
	* цены товара;</li> <li> <b>VAT_RATE</b> - величина налога на товар;</li> <li>
	* <b>CURRENCY</b> - валюта, в которой задана цена товара;</li> <li> <b>DISCOUNT_PRICE</b> -
	* величина скидки на товар;</li> <li> <b>NAME</b> - название товара;</li> <li>
	* <b>DETAIL_PAGE_URL</b> - путь к странице с детальной информацией о товаре;</li>
	* <li> <b>NOTES</b> - особые заметки, например, тип цены.</li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductprovider/viewproduct.php
	* @author Bitrix
	*/
	public static function ViewProduct($arParams)
	{
		if (!is_set($arParams["SITE_ID"]))
			$arParams["SITE_ID"] = SITE_ID;

		return CatalogViewedProductCallback(
			$arParams["PRODUCT_ID"],
			$arParams["USER_ID"],
			$arParams["SITE_ID"]
		);
	}

	
	/**
	* <p>Метод получает данные товара для продления подписки.</p>
	*
	*
	*
	*
	* @param array $arParams  Ассоциативный массив параметров с ключами: <ul> <li> <b>PRODUCT_ID</b> -
	* идентификатор товара;</li> <li> <b>USER_ID</b> - идентификатор
	* пользователя.</li> </ul>
	*
	*
	*
	* @return mixed <p>Возвращается ассоциативный массив с ключами:</p> <ul> <li> <b>WEIGHT</b> -
	* вес товара;</li> <li> <b>DIMENSIONS</b> - размеры товара (ширина, высота и
	* длина);</li> <li> <b>VAT_RATE</b> - величина налога на товар;</li> <li> <b>QUANTITY</b> -
	* количество товара;</li> <li> <b>PRICE</b> - цена;</li> <li> <b>WITHOUT_ORDER</b> - (Y|N) флаг
	* продления подписки без оформления заказа;</li> <li> <b>PRODUCT_ID</b> -
	* идентификатор товара;</li> <li> <b>PRODUCT_NAME</b> - название товара;</li> <li>
	* <b>PRODUCT_URL</b> - ссылка на страницу с информацией о товаре;</li> <li>
	* <b>PRODUCT_PRICE_ID</b> - идентификатор цены товара;</li> <li> <b>CURRENCY</b> - валюта, в
	* которой задана цена;</li> <li> <b>NAME</b> - название товара;</li> <li> <b>MODULE</b> -
	* название модуля, право на доступ к контенту которого
	* продлевается;</li> <li> <b>PRODUCT_PROVIDER_CLASS</b> - класс-провайдер модуля,
	* предоставляющего товар;</li> <li> <b>CATALOG_GROUP_NAME</b> - название группы цен
	* на текущем языке;</li> <li> <b>DETAIL_PAGE_URL</b> - ссылка на страницу с
	* информацией о товаре;</li> <li> <b>PRICE_TYPE</b> - тип цены;</li> <li>
	* <b>RECUR_SCHEME_TYPE</b> - тип периода подписки ("H" - час, "D" - сутки, "W" - неделя,
	* "M" - месяц, "Q" - квартал, "S" - полугодие, "Y" - год);</li> <li> <b>RECUR_SCHEME_LENGTH</b> -
	* длина периода подписки;</li> <li> <b>PRODUCT_XML_ID</b> - внешний код товара;</li>
	* <li> <b>TYPE</b> - код, обозначающий принадлежность товара к комплектам.
	* Может быть пустым;</li> <li> <b>NEXT_DATE</b> - дата очередного продления.</li>
	* </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductprovider/recurringorderproduct.php
	* @author Bitrix
	*/
	public static function RecurringOrderProduct($arParams)
	{
		return CatalogRecurringCallback(
			$arParams["PRODUCT_ID"],
			$arParams["USER_ID"]
		);
	}

	
	/**
	* <p>Метод осуществляет резервирование или отмену резервирования товара.</p>
	*
	*
	*
	*
	* @param array $arParams  Ассоциативный массив параметров с ключами: <ul> <li> <b>PRODUCT_ID</b> -
	* идентификатор товара;</li> <li> <b>QUANTITY_ADD</b> - резервируемое
	* количество;</li> <li> <b>UNDO_RESERVATION</b> - (Y|N) флаг определяет выполнять
	* резервирование или снимать товар с резервов.</li> </ul>
	*
	*
	*
	* @return array <p>Метод возвращает ассоциативный массив с ключами:</p> <ul> <li>
	* <b>RESULT</b> - успешно (true) или неуспешно (false) завершилась операция;</li>
	* <li> <b>QUANTITY_RESERVED</b> - зарезервированное количество.</li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductprovider/reserveproduct.php
	* @author Bitrix
	*/
	public static function ReserveProduct($arParams)
	{
		global $APPLICATION;

		$arRes = array();
		$arFields = array();

		if ((int)$arParams["PRODUCT_ID"] <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("RSRV_INCORRECT_ID"), "NO_ORDER_ID");
			$arRes["RESULT"] = false;
			return $arRes;
		}

		$disableReservation = (COption::GetOptionString("catalog", "enable_reservation") == "N"
			&& COption::GetOptionString("sale", "product_reserve_condition", "O") != "S"
			&& COption::GetOptionString('catalog','default_use_store_control') != "Y");

		if ((string)$arParams["UNDO_RESERVATION"] != "Y")
			$arParams["UNDO_RESERVATION"] = "N";

		$arParams["QUANTITY_ADD"] = doubleval($arParams["QUANTITY_ADD"]);

		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('ID' => $arParams["PRODUCT_ID"]),
			false,
			false,
			array('ID', 'CAN_BUY_ZERO', 'NEGATIVE_AMOUNT_TRACE', 'QUANTITY_TRACE', 'QUANTITY', 'QUANTITY_RESERVED')
		);

		if ($arProduct = $rsProducts->Fetch())
		{
			if ($disableReservation)
			{
				$startReservedQuantity = 0;

				if ($arParams["UNDO_RESERVATION"] != "Y")
					$arFields = array("QUANTITY" => $arProduct["QUANTITY"] - $arParams["QUANTITY_ADD"]);
				else
					$arFields = array("QUANTITY" => $arProduct["QUANTITY"] + $arParams["QUANTITY_ADD"]);

				$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
				$arFields["QUANTITY_RESERVED"] = 0;
				if (self::isNeedClearPublicCache(
					$arProduct['QUANTITY'],
					$arFields['QUANTITY'],
					$arProduct['QUANTITY_TRACE'],
					$arProduct['CAN_BUY_ZERO']
				))
				{
					$productInfo = array(
						'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
						'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
						'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
						'OLD_QUANTITY' => $arProduct['QUANTITY'],
						'QUANTITY' => $arFields['QUANTITY'],
						'DELTA' => $arFields['QUANTITY'] - $arProduct['QUANTITY']
					);
					self::clearPublicCache($arProduct['ID'], $productInfo);
				}
			}
			else
			{
				if ($arProduct["QUANTITY_TRACE"] == "N" || (isset($arParams["ORDER_DEDUCTED"]) && $arParams["ORDER_DEDUCTED"] == "Y"))
				{
					$arRes["RESULT"] = true;
					$arFields["QUANTITY_RESERVED"] = 0;
					$startReservedQuantity = 0;
				}
				else
				{
					$startReservedQuantity = $arProduct["QUANTITY_RESERVED"];

					if ($arParams["UNDO_RESERVATION"] == "N")
					{
						if ($arProduct["CAN_BUY_ZERO"] == "Y")
						{
							$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $arParams["QUANTITY_ADD"];

							if ($arProduct["QUANTITY"] >= $arParams["QUANTITY_ADD"])
							{
								$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY_ADD"];
							}
							elseif ($arProduct["QUANTITY"] < $arParams["QUANTITY_ADD"])
							{
								if ($arProduct["NEGATIVE_AMOUNT_TRACE"] == "Y")
								{
									//reserve value, quantity will be negative
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY_ADD"];
								}
								else
								{
									$arFields["QUANTITY"] = 0;
								}
							}

							$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
						}
						else //CAN_BUY_ZERO = N
						{
							if ($arProduct["QUANTITY"] >= $arParams["QUANTITY_ADD"])
							{
								$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY_ADD"];
								$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $arParams["QUANTITY_ADD"];
							}
							elseif ($arProduct["QUANTITY"] < $arParams["QUANTITY_ADD"])
							{
								//reserve only possible value, quantity = 0

								$arRes["QUANTITY_NOT_RESERVED"] = $arParams["QUANTITY_ADD"] - $arProduct["QUANTITY"];

								$arFields["QUANTITY"] = 0;
								$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $arProduct["QUANTITY"];

								$APPLICATION->ThrowException(Loc::getMessage("RSRV_QUANTITY_NOT_ENOUGH_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "ERROR_NOT_ENOUGH_QUANTITY");
							}
							if (self::isNeedClearPublicCache(
								$arProduct['QUANTITY'],
								$arFields['QUANTITY'],
								$arProduct['QUANTITY_TRACE'],
								$arProduct['CAN_BUY_ZERO']
							))
							{
								$productInfo = array(
									'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
									'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
									'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
									'OLD_QUANTITY' => $arProduct['QUANTITY'],
									'QUANTITY' => $arFields['QUANTITY'],
									'DELTA' => $arFields['QUANTITY'] - $arProduct['QUANTITY']
								);
								self::clearPublicCache($arProduct['ID'], $productInfo);
							}
							$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
						}
					}
					else //undo reservation
					{
						$arFields["QUANTITY"] = $arProduct["QUANTITY"] + $arParams["QUANTITY_ADD"];
						$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - $arParams["QUANTITY_ADD"];
						$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
						if (self::isNeedClearPublicCache(
							$arProduct['QUANTITY'],
							$arFields['QUANTITY'],
							$arProduct['QUANTITY_TRACE'],
							$arProduct['CAN_BUY_ZERO']
						))
						{
							$productInfo = array(
								'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
								'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
								'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
								'OLD_QUANTITY' => $arProduct['QUANTITY'],
								'QUANTITY' => $arFields['QUANTITY'],
								'DELTA' => $arFields['QUANTITY'] - $arProduct['QUANTITY']
							);
							self::clearPublicCache($arProduct['ID'], $productInfo);
						}
					}
				} //quantity trace
			}
		} //product found
		else
		{
			$APPLICATION->ThrowException(Loc::getMessage("RSRV_ID_NOT_FOUND", array("#PRODUCT_ID#" => $arParams["PRODUCT_ID"])), "ID_NOT_FOUND");
			$arRes["RESULT"] = false;
			return $arRes;
		}

		if ($arRes["RESULT"])
		{
			$arRes["QUANTITY_RESERVED"] = $arFields["QUANTITY_RESERVED"] - $startReservedQuantity;
		}
		else
		{
			$APPLICATION->ThrowException(Loc::getMessage("RSRV_UNKNOWN_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "UNKNOWN_RESERVATION_ERROR");
		}

		return $arRes;
	}

	
	/**
	* <p>Метод осуществляет отгрузку или отмену отгрузки товара.</p>
	*
	*
	*
	*
	* @param array $arParams  Ассоциативный массив параметров с ключами: <ul> <li> <b>PRODUCT_ID</b> -
	* идентификатор товара;</li> <li> <b>QUANTITY</b> - количество товара;</li> <li>
	* <b>EMULATE</b> - (Y|N) флаг вызова метода в режиме эмуляции (проверяется
	* только наличие товара, без проверок количества, складов и т.п.).
	* Используется перед действительной отгрузкой;</li> <li> <b>UNDO_DEDUCTION</b> -
	* (Y|N) указывает осуществлять отгрузку или отмену отгрузки
	* товара;</li> <li> <b>PRODUCT_RESERVED</b> - (Y|N) признак резервирования
	* отгружаемого товара;</li> <li> <b>STORE_DATA</b> - массив с данными о складах
	* (может быть один или несколько), также содержит информацию об
	* отгружаемом с каждого склада количестве и штрихкодах для
	* отгружаемых экземпляров товара (если каждый экземпляр товара
	* имеет собственный штрихкод).</li> </ul>
	*
	*
	*
	* @return mixed <p>Возвращается ассоциативный массив с ключами:</p> <ul> <li> <b>RESULT</b> -
	* успешное (true) или неуспешное (false) завершение операции;</li> <li>
	* <b>STORES</b> - массив с указанием идентификаторов складов и сколько
	* товаров с каждого из них было отгружено (например, <i>array("1" =&gt; 5, "2"
	* =&gt; 3)</i>). Данный массив присутствует только при отгрузке со
	* складов.</li> </ul> <p>В случае ошибки срабатывает исключение и
	* выводится его причина.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductprovider/deductproduct.php
	* @author Bitrix
	*/
	public static function DeductProduct($arParams)
	{
		global $APPLICATION;

		$arRes = array();
		$arFields = array();

		$strUseStoreControl = COption::GetOptionString('catalog','default_use_store_control');

		$disableReservation = (COption::GetOptionString("catalog", "enable_reservation") == "N"
			&& COption::GetOptionString("sale", "product_reserve_condition", "O") != "S"
			&& $strUseStoreControl != "Y");

		if ($disableReservation)
		{
			$arRes["RESULT"] = true;
			return $arRes;
		}

		if ((int)$arParams["PRODUCT_ID"] <= 0)
		{
			$APPLICATION->ThrowException(Loc::getMessage("RSRV_INCORRECT_ID"), "NO_ORDER_ID");
			$arRes["RESULT"] = false;
			return $arRes;
		}

		$arParams["QUANTITY"] = doubleval($arParams["QUANTITY"]);

		if ((string)$arParams["UNDO_DEDUCTION"] != "Y")
			$arParams["UNDO_DEDUCTION"] = "N";

		if ((string)$arParams["EMULATE"] != "Y")
			$arParams["EMULATE"] = "N";

		if ((string)$arParams["PRODUCT_RESERVED"] != "Y")
			$arParams["PRODUCT_RESERVED"] = "N";

		if (!isset($arParams["STORE_DATA"]))
			$arParams["STORE_DATA"] = array();

		if (!is_array($arParams["STORE_DATA"]))
			$arParams["STORE_DATA"] = array($arParams["STORE_DATA"]);

		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('ID' => $arParams["PRODUCT_ID"]),
			false,
			false,
			array('ID', 'QUANTITY', 'QUANTITY_RESERVED', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'NEGATIVE_AMOUNT_TRACE')
		);

		if ($arProduct = $rsProducts->Fetch())
		{
			if ($arParams["UNDO_DEDUCTION"] == "N")
			{
				if ($arParams["EMULATE"] == "Y" || $arProduct["QUANTITY_TRACE"] == "N")
				{
					$arRes["RESULT"] = true;
				}
				else
				{
					if ($strUseStoreControl == "Y")
					{
						if (!empty($arParams["STORE_DATA"]))
						{
							$totalAmount = 0;
							foreach ($arParams["STORE_DATA"] as $id => $arRecord)
							{
								if (!isset($arRecord["STORE_ID"]) || intval($arRecord["STORE_ID"]) < 0 || !isset($arRecord["QUANTITY"]) || intval($arRecord["QUANTITY"]) < 0)
								{
									$APPLICATION->ThrowException(Loc::getMessage("DDCT_DEDUCTION_STORE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DDCT_DEDUCTION_STORE_ERROR");
									$arRes["RESULT"] = false;
									return $arRes;
								}

								$rsProps = CCatalogStoreProduct::GetList(
									array(),
									array(
										"PRODUCT_ID" => $arParams["PRODUCT_ID"],
										"STORE_ID" => $arRecord["STORE_ID"]
									),
									false,
									false,
									array('ID', 'AMOUNT')
								);
								if ($arProp = $rsProps->Fetch())
								{
									if ($arProp["AMOUNT"] < $arRecord["QUANTITY"])
									{
										$APPLICATION->ThrowException(
											Loc::getMessage(
												"DDCT_DEDUCTION_QUANTITY_STORE_ERROR",
												array_merge(self::GetProductCatalogInfo($arParams["PRODUCT_ID"]), array("#STORE_ID#" => $arRecord["STORE_ID"]))
											),
											"DDCT_DEDUCTION_QUANTITY_STORE_ERROR"
										);
										$arRes["RESULT"] = false;
										return $arRes;
									}
									else
									{
										$res = CCatalogStoreProduct::Update($arProp["ID"], array("AMOUNT" => $arProp["AMOUNT"] - $arRecord["QUANTITY"]));

										if ($res)
										{
											$arRes["STORES"][$arRecord["STORE_ID"]] = $arRecord["QUANTITY"];
											$totalAmount += $arRecord["QUANTITY"];

											//deleting barcodes
											if (isset($arRecord["BARCODE"]) && is_array($arRecord["BARCODE"]) && count($arRecord["BARCODE"]) > 0)
											{
												foreach ($arRecord["BARCODE"] as $barcodeId => $barcodeValue)
												{
													$arFields = array(
														"STORE_ID" => $arRecord["STORE_ID"],
														"BARCODE" => $barcodeValue,
														"PRODUCT_ID" => $arParams["PRODUCT_ID"]
													);

													$dbres = CCatalogStoreBarcode::GetList(
														array(),
														$arFields,
														false,
														false,
														array("ID", "STORE_ID", "BARCODE", "PRODUCT_ID")
													);

													if ($arRes = $dbres->Fetch())
													{
														CCatalogStoreBarcode::Delete($arRes["ID"]);
													}
													else
													{
														$APPLICATION->ThrowException(
															Loc::getMessage(
																"DDCT_DEDUCTION_BARCODE_ERROR",
																array_merge(self::GetProductCatalogInfo($arParams["PRODUCT_ID"]), array("#BARCODE#" => $barcodeValue))
															),
															"DDCT_DEDUCTION_BARCODE_ERROR"
														);
														$arRes["RESULT"] = false;
														return $arRes;
													}
												}
											}
										}
										else
										{
											$APPLICATION->ThrowException(Loc::getMessage("DDCT_DEDUCTION_SAVE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DDCT_DEDUCTION_SAVE_ERROR");
											$arRes["RESULT"] = false;
											return $arRes;
										}
									}
								}
							}

							//updating total sum
							if ($arParams["PRODUCT_RESERVED"] == "Y")
							{
								if ($totalAmount <= $arProduct["QUANTITY_RESERVED"])
								{
									$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - $totalAmount;
								}
								else if ($totalAmount <= $arProduct["QUANTITY_RESERVED"] + $arProduct["QUANTITY"])
								{
									$arFields["QUANTITY_RESERVED"] = 0;
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - ($totalAmount - $arProduct["QUANTITY_RESERVED"]);
								}
								else //not enough products - don't deduct anything
								{
									$arRes["RESULT"] = false;
									return $arRes;
								}
							}
							else //product not reserved, use main quantity field to deduct from, quantity_reserved only if there is shortage in the main field
							{
								if ($totalAmount <= $arProduct["QUANTITY"])
								{
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $totalAmount;
								}
								else if ($totalAmount <= $arProduct["QUANTITY_RESERVED"] + $arProduct["QUANTITY"])
								{
									$arFields["QUANTITY"] = 0;
									$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - ($totalAmount - $arProduct["QUANTITY"]);
								}
								else //not enough products - don't deduct anything
								{
									$arRes["RESULT"] = false;
									return $arRes;
								}
							}

							CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
							if (isset($arFields['QUANTITY']) && self::isNeedClearPublicCache(
								$arProduct['QUANTITY'],
								$arFields['QUANTITY'],
								$arProduct['QUANTITY_TRACE'],
								$arProduct['CAN_BUY_ZERO']
							))
							{
								$productInfo = array(
									'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
									'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
									'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
									'OLD_QUANTITY' => $arProduct['QUANTITY'],
									'QUANTITY' => $arFields['QUANTITY'],
									'DELTA' => $arFields['QUANTITY'] - $arProduct['QUANTITY']
								);
								self::clearPublicCache($arProduct['ID'], $productInfo);
							}

							$arRes["RESULT"] = true;
						}
						else
						{
							$APPLICATION->ThrowException(Loc::getMessage("DDCT_DEDUCTION_STORE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DEDUCTION_STORE_ERROR1");
							$arRes["RESULT"] = false;
							return $arRes;
						}
					}
					else // store control not used
					{
						if ($arParams["QUANTITY"] <= $arProduct["QUANTITY_RESERVED"] + $arProduct["QUANTITY"])
						{
							if ($arParams["PRODUCT_RESERVED"] == "Y")
							{
								if ($arParams["QUANTITY"] <= $arProduct["QUANTITY_RESERVED"])
								{
									$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - $arParams["QUANTITY"];
								}
								else
								{
									$arFields["QUANTITY_RESERVED"] = 0;
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - ($arParams["QUANTITY"] - $arProduct["QUANTITY_RESERVED"]);
								}
							}
							else //product not reserved, use main quantity field to deduct from, quantity_reserved only if there is shortage in the main field
							{
								if ($arParams["QUANTITY"] <= $arProduct["QUANTITY"])
								{
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY"];
								}
								else
								{
									$arFields["QUANTITY"] = 0;
									$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - ($arParams["QUANTITY"] - $arProduct["QUANTITY"]);
								}
							}

							$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
							if (isset($arFields['QUANTITY']) && self::isNeedClearPublicCache(
									$arProduct['QUANTITY'],
									$arFields['QUANTITY'],
									$arProduct['QUANTITY_TRACE'],
									$arProduct['CAN_BUY_ZERO']
								))
							{
								$productInfo = array(
									'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
									'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
									'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
									'OLD_QUANTITY' => $arProduct['QUANTITY'],
									'QUANTITY' => $arFields['QUANTITY'],
									'DELTA' => $arFields['QUANTITY'] - $arProduct['QUANTITY']
								);
								self::clearPublicCache($arProduct['ID'], $productInfo);
							}
						}
						else //not enough products - don't deduct anything
						{
							$APPLICATION->ThrowException(Loc::getMessage("DDCT_DEDUCTION_QUANTITY_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DDCT_DEDUCTION_QUANTITY_ERROR");
							$arRes["RESULT"] = false;
							return $arRes;
						}

					} //store control
				} //emulate /quantity trace
			}
			else //undo deduction
			{
				if ($arParams["EMULATE"] == "Y" || $arProduct["QUANTITY_TRACE"] == "N")
				{
					$arRes["RESULT"] = true;
				}
				else
				{
					if ($strUseStoreControl == "Y")
					{
						if (!empty($arParams["STORE_DATA"]))
						{
							$totalAddedAmount = 0;
							foreach ($arParams["STORE_DATA"] as $id => $arRecord)
							{
								$rsProps = CCatalogStoreProduct::GetList(
									array(),
									array(
										"PRODUCT_ID" => $arParams["PRODUCT_ID"],
										"STORE_ID" => $arRecord["STORE_ID"]
									),
									false,
									false,
									array('ID', 'AMOUNT')
								);

								if ($arProp = $rsProps->Fetch())
								{
									$res = CCatalogStoreProduct::Update(
										$arProp["ID"],
										array("AMOUNT" => $arProp["AMOUNT"] + $arRecord["QUANTITY"])
									);

									if ($res)
									{
										$arRes["STORES"][$arRecord["STORE_ID"]] = $arRecord["QUANTITY"];
										$totalAddedAmount += $arRecord["QUANTITY"];

										//adding barcodes
										if (isset($arRecord["BARCODE"]) && strlen($arRecord["BARCODE"]) > 0)
										{
											$arFields = array(
												"STORE_ID" => $arRecord["STORE_ID"],
												"BARCODE" => $arRecord["BARCODE"],
												"PRODUCT_ID" => $arParams["PRODUCT_ID"]
											);

											CCatalogStoreBarcode::Add($arFields);
										}
									}
									else
									{
										$APPLICATION->ThrowException(Loc::getMessage("DDCT_DEDUCTION_SAVE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DDCT_DEDUCTION_SAVE_ERROR");
										$arRes["RESULT"] = false;
										return $arRes;
									}
								}
							}

							// $dbAmount = $DB->Query("SELECT SUM(AMOUNT) as AMOUNT FROM b_catalog_store_product WHERE PRODUCT_ID = ".$arParams["PRODUCT_ID"]." ", true);
							// if ($totalAddedAmount = $dbAmount->Fetch())
							// {
							// }
							if ($arParams["PRODUCT_RESERVED"] == "Y")
							{
								$arUpdateFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $totalAddedAmount;
							}
							else
							{
								$arUpdateFields["QUANTITY"] = $arProduct["QUANTITY"] + $totalAddedAmount;
							}

							CCatalogProduct::Update($arParams["PRODUCT_ID"], $arUpdateFields);
							if (isset($arUpdateFields['QUANTITY']) && self::isNeedClearPublicCache(
									$arProduct['QUANTITY'],
									$arUpdateFields['QUANTITY'],
									$arProduct['QUANTITY_TRACE'],
									$arProduct['CAN_BUY_ZERO']
								))
							{
								$productInfo = array(
									'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
									'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
									'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
									'OLD_QUANTITY' => $arProduct['QUANTITY'],
									'QUANTITY' => $arUpdateFields['QUANTITY'],
									'DELTA' => $arUpdateFields['QUANTITY'] - $arProduct['QUANTITY']
								);
								self::clearPublicCache($arProduct['ID'], $productInfo);
							}

							$arRes["RESULT"] = true;
						}
						else
						{
							$APPLICATION->ThrowException(Loc::getMessage("DDCT_DEDUCTION_STORE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DEDUCTION_STORE_ERROR2");
							$arRes["RESULT"] = false;
							return $arRes;
						}
					}
					else //store control not used
					{
						if ($arParams["PRODUCT_RESERVED"] == "Y")
						{
							$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $arParams["QUANTITY"];
							// $arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY_RESERVED"];
						}
						else
						{
							$arFields["QUANTITY"] = $arProduct["QUANTITY"] + $arParams["QUANTITY"];
							// $arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - $arParams["QUANTITY_RESERVED"];
						}

						$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
						if (isset($arFields['QUANTITY']) && self::isNeedClearPublicCache(
								$arProduct['QUANTITY'],
								$arFields['QUANTITY'],
								$arProduct['QUANTITY_TRACE'],
								$arProduct['CAN_BUY_ZERO']
							))
						{
							$productInfo = array(
								'CAN_BUY_ZERO' => $arProduct['CAN_BUY_ZERO'],
								'NEGATIVE_AMOUNT_TRACE' => $arProduct['NEGATIVE_AMOUNT_TRACE'],
								'QUANTITY_TRACE' => $arProduct['QUANTITY_TRACE'],
								'OLD_QUANTITY' => $arProduct['QUANTITY'],
								'QUANTITY' => $arFields['QUANTITY'],
								'DELTA' => $arFields['QUANTITY'] - $arProduct['QUANTITY']
							);
							self::clearPublicCache($arProduct['ID'], $productInfo);
						}
					}
				} //emulate or quantity trace
			}
		}
		else
		{
			$arRes["RESULT"] = false;
		}

		if (!$arRes["RESULT"])
		{
			$APPLICATION->ThrowException(Loc::getMessage("DDCT_UNKNOWN_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "UNKNOWN_DEDUCTION_ERROR");
		}

		return $arRes;
	}

	
	/**
	* <p>Метод возвращает количество складов, если в провайдере товаров поддерживается складской учет, или же <b>-1</b>, если складской учет не используется.</p>
	*
	*
	*
	*
	* @param array $arrayarParams = array() Ассоциативный массив параметров складов с ключом: <ul> <li> <b>SITE_ID</b> -
	* код сайта (необязательный).</li> </ul>
	*
	*
	*
	* @return mixed <p>Возвращает количество складов, если в провайдере товаров
	* поддерживается складской учет, или же <b>-1</b>, если складской учет
	* не используется.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductprovider/getstorescount.php
	* @author Bitrix
	*/
	public static function GetStoresCount($arParams = array())
	{
		$strUseStoreControl = COption::GetOptionString('catalog','default_use_store_control');

		//without store control stores are used for information purposes only
		if ($strUseStoreControl == "N")
			return -1;

		$arFilter = array("ACTIVE" => "Y", "SHIPPING_CENTER" => "Y");

		if (isset($arParams["SITE_ID"]) && strlen($arParams["SITE_ID"]) > 0)
			$arFilter["+SITE_ID"] = $arParams["SITE_ID"];

		$arStoreID = array();
		$dbStoreRes = CCatalogStore::GetList(
			array("SORT" => "DESC"),
			$arFilter,
			false,
			false,
			array("ID", "ACTIVE")
		);

		while ($arStoreRes = $dbStoreRes->GetNext())
			$arStoreID[] = $arStoreRes["ID"];

		return count($arStoreID);
	}

	
	/**
	* <p>Метод возвращает информацию о количестве складов, с которых возможно списание товара с кодом <i>PRODUCT_ID</i>.</p>
	*
	*
	*
	*
	* @param array $arParams  Ассоциативный массив параметров товара с ключами: <ul> <li> <b>PRODUCT_ID</b>
	* - идентификатор товара;</li> <li> <b>SITE_ID</b> - код сайта
	* (необязательный).</li> </ul>
	*
	*
	*
	* @return array <p>Метод возвращает ассоциативный массив с информацией о
	* складах:</p> <ul> <li> <b>STORE_NAME</b> - название склада;</li> <li> <b>STORE_ID</b> -
	* идентификатор склада;</li> <li> <b>AMOUNT</b> - доступное количество на
	* складе товара.</li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductprovider/getproductstores.php
	* @author Bitrix
	*/
	public static function GetProductStores($arParams)
	{
		$strUseStoreControl = COption::GetOptionString('catalog','default_use_store_control');

		//without store control stores are used for information purposes only and manual deduction won't work
		if ($strUseStoreControl == "N")
			return false;

		$arResult = array();
		$arStoreID = array();

		if (intval($arParams["PRODUCT_ID"] < 0))
			return false;

		$arFilter = array("ACTIVE" => "Y", "SHIPPING_CENTER" => "Y");

		if (isset($arParams["SITE_ID"]) && strlen($arParams["SITE_ID"]) > 0)
			$arFilter["+SITE_ID"] = $arParams["SITE_ID"];

		$dbStoreRes = CCatalogStore::GetList(
			array("SORT" => "DESC"),
			$arFilter,
			false,
			false,
			array("ID", "ACTIVE")
		);
		while ($arStoreRes = $dbStoreRes->Fetch())
			$arStoreID[] = $arStoreRes["ID"];

		$dbRes = CCatalogStoreProduct::GetList(
			array(),
			array(
				"PRODUCT_ID" => $arParams["PRODUCT_ID"],
				// ">AMOUNT" => "0"
			),
			false,
			false,
			array("STORE_NAME", "STORE_ID", "AMOUNT", "PRODUCT_ID")
		);
		while ($arRes = $dbRes->Fetch())
		{
			if (in_array($arRes["STORE_ID"], $arStoreID))
			{
				if(isset($arParams["ENUM_BY_ID"]) && $arParams["ENUM_BY_ID"] == true)
					$arResult[$arRes["STORE_ID"]] = $arRes;
				else
					$arResult[] = $arRes;
			}
		}

		return $arResult;
	}

	
	/**
	* <p>Метод осуществляет проверку валидности штрихкода для отгрузки товара.</p>
	*
	*
	*
	*
	* @param array $arParams  Ассоциативный массив параметров с ключами: <ul> <li> <b>PRODUCT_ID</b> -
	* идентификатор товара;</li> <li> <b>BARCODE</b> - штрихкод;</li> <li> <b>STORE_ID</b> -
	* идентификатор склада. Необязательный, нужен только для товаров с
	* множественными штрихкодами.</li> </ul>
	*
	*
	*
	* @return bool <p>В случае успешной проверки штрихкода метод возвращает <i>true</i>,
	* иначе - <i>false</i>.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproductprovider/checkproductbarcode.php
	* @author Bitrix
	*/
	public static function CheckProductBarcode($arParams)
	{
		$result = false;

		$arFilter = array(
			"PRODUCT_ID" => $arParams["PRODUCT_ID"],
			"BARCODE"	 => $arParams["BARCODE"]
		);

		if (isset($arParams["STORE_ID"]))
			$arFilter["STORE_ID"] = intval($arParams["STORE_ID"]);

		$dbres = CCatalogStoreBarcode::GetList(
			array(),
			$arFilter
		);
		if ($res = $dbres->GetNext())
			$result = true;

		return $result;
	}

	private static function GetProductCatalogInfo($productID)
	{
		$productID = intval($productID);
		$result = "";
		if ($productID <= 0)
			return array();

		$dbProduct = CIBlockElement::GetList(array(), array("ID" => $productID), false, false, array('ID', 'IBLOCK_ID', 'NAME'));
		if ($arProduct = $dbProduct->Fetch())
		{
			if ($arProduct["IBLOCK_ID"] > 0)
				$arProduct["EDIT_PAGE_URL"] = CIBlock::GetAdminElementEditLink($arProduct["IBLOCK_ID"], $productID);
		}

		return array(
			"#PRODUCT_ID#"   => $arProduct["ID"],
			"#PRODUCT_NAME#" => $arProduct["NAME"],
		);
	}

	public static function GetSetItems($productID, $intType)
	{
		$arProductId = array();
		$arSets = CCatalogProductSet::getAllSetsByProduct($productID, $intType);

		if (is_array($arSets))
		{
			foreach ($arSets as $k => $arSet)
			{
				foreach ($arSet["ITEMS"] as $k1 => $item)
				{
					$arItem = self::GetProductData(array("PRODUCT_ID" => $item["ITEM_ID"], "QUANTITY" => $item["QUANTITY"], "CHECK_QUANTITY" => "N", "CHECK_PRICE" => "N"));

					$arItem["PRODUCT_ID"] = $item["ITEM_ID"];
					$arItem["MODULE"] = "catalog";
					$arItem["PRODUCT_PROVIDER_CLASS"] = "CCatalogProductProvider";

					$arProductId[] = $item["ITEM_ID"];

					$arItem["PROPS"] = array();
					$arParentSku = CCatalogSku::GetProductInfo($item["ITEM_ID"]);
					if (!empty($arParentSku))
					{
						$arPropsSku = array();

						$dbProduct = CIBlockElement::GetList(array(), array("ID" => $item["ITEM_ID"]), false, false, array('IBLOCK_ID', 'IBLOCK_SECTION_ID'));
						$arProduct = $dbProduct->Fetch();

						$dbOfferProperties = CIBlock::GetProperties($arProduct["IBLOCK_ID"], array(), array("!XML_ID" => "CML2_LINK"));
						while($arOfferProperties = $dbOfferProperties->Fetch())
							$arPropsSku[] = $arOfferProperties["CODE"];

						$product_properties = CIBlockPriceTools::GetOfferProperties(
							$item["ITEM_ID"],
							$arParentSku["IBLOCK_ID"],
							$arPropsSku
						);

						foreach ($product_properties as $propData)
						{
							$arItem["PROPS"][] = array(
								"NAME" => $propData["NAME"],
								"CODE" => $propData["CODE"],
								"VALUE" => $propData["VALUE"],
								"SORT" => $propData["SORT"]
							);
						}
					}

					$arSets[$k]["ITEMS"][$k1] = array_merge($item, $arItem);
				}
			}

			$rsProducts = CIBlockElement::GetList(
				array(),
				array('ID' => $arProductId),
				false,
				false,
				array("ID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "PREVIEW_PICTURE", "DETAIL_PICTURE", "IBLOCK_TYPE_ID", "XML_ID")
			);
			while ($arProduct = $rsProducts->GetNext())
			{
				foreach ($arSets as $k => $arSet)
				{
					foreach ($arSet["ITEMS"] as $k1 => $item)
					{
						if ($item["ITEM_ID"] == $arProduct["ID"])
						{
							$arProps = array();
							$strIBlockXmlID = strval(CIBlock::GetArrayByID($arProduct['IBLOCK_ID'], 'XML_ID'));
							if ($strIBlockXmlID != "")
							{
								$arProps[] = array(
									"NAME" => "Catalog XML_ID",
									"CODE" => "CATALOG.XML_ID",
									"VALUE" => $strIBlockXmlID
								);
							}

							$arProps[] = array(
								"NAME" => "Product XML_ID",
								"CODE" => "PRODUCT.XML_ID",
								"VALUE" => $arProduct["XML_ID"]
							);

							$arSets["$k"]["ITEMS"][$k1]["IBLOCK_ID"] = $arProduct["IBLOCK_ID"];
							$arSets["$k"]["ITEMS"][$k1]["IBLOCK_SECTION_ID"] = $arProduct["IBLOCK_SECTION_ID"];
							$arSets["$k"]["ITEMS"][$k1]["PREVIEW_PICTURE"] = $arProduct["PREVIEW_PICTURE"];
							$arSets["$k"]["ITEMS"][$k1]["DETAIL_PICTURE"] = $arProduct["DETAIL_PICTURE"];
							$arSets["$k"]["ITEMS"][$k1]["PROPS"] = array_merge($arSets["$k"]["ITEMS"][$k1]["PROPS"], $arProps);
						}
					}
				}
			}
		}

		foreach(GetModuleEvents("sale", "OnGetSetItems", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arSets));

		return $arSets;
	}

	protected static function isNeedClearPublicCache($currentQuantity, $newQuantity, $quantityTrace, $canBuyZero, $ratio = 1)
	{
		if (!defined('BX_COMP_MANAGED_CACHE'))
			return false;
		if ($canBuyZero == 'Y' || $quantityTrace == 'N')
			return false;
		if ($currentQuantity * $newQuantity > 0)
			return false;
		return true;
	}

	protected static function clearPublicCache($productID, $productInfo = array())
	{
		global $CACHE_MANAGER;

		$productID = (int)$productID;
		if ($productID <= 0)
			return;
		$iblockID = (int)(isset($productInfo['IBLOCK_ID']) ? $productInfo['IBLOCK_ID'] : CIBlockElement::GetIBlockByID($productID));
		if ($iblockID <= 0)
			return;
		if (defined('BX_COMP_MANAGED_CACHE') && !isset(self::$clearAutoCache[$iblockID]))
		{
			$CACHE_MANAGER->ClearByTag('iblock_id_'.$iblockID);
			self::$clearAutoCache[$iblockID] = true;
		}

		$productInfo['ID'] = $productID;
		$productInfo['ELEMENT_IBLOCK_ID'] = $iblockID;
		$productInfo['IBLOCK_ID'] = $iblockID;
		foreach (GetModuleEvents('catalog', 'OnProductQuantityTrace', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($productID, $productInfo));
		}
	}
}
?>