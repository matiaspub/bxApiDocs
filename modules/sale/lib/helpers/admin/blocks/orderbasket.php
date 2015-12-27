<?php

namespace Bitrix\Sale\Helpers\Admin\Blocks;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\Collection;
use Bitrix\Sale;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Sale\Helpers\Admin\OrderEdit;
use Bitrix\Sale\Order;
use Bitrix\Sale\Provider;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/admin_tool.php");

class OrderBasket
{
	protected static $arSkuProps = array();
	protected static $offersCatalog = array();
	protected static $iblockPropsParams = array();
	protected static $productsOffersSkuParams = array();
	protected static $jsInited = false;

	public $settingsDialog = null;

	/** @var Order $order  */
	protected $order = null;
	protected $data = null;
	protected $jsObjName = "";
	protected $idPrefix = "";
	protected $visibleColumns = array();
	protected $createProductBasement = true;
	protected $mode;
	protected $weightUnit;
	protected $weightKoef;
	protected $isShowXmlId;

	protected static $iblockIncluded = null;
	protected static $catalogIncluded = null;
	protected static $highloadIncluded = null;

	const VIEW_MODE = 0;
	const EDIT_MODE = 1;

	public function __construct(Order $order, $jsObjName = "", $idPrefix = "", $createProductBasement = true, $mode = self::EDIT_MODE)
	{
		$this->order = $order;
		$this->idPrefix = $idPrefix;
		$this->visibleColumns = $this->getVisibleColumns();
		$this->createProductBasement = $createProductBasement;
		$this->mode = $mode;
		$this->weightUnit = htmlspecialcharsbx(Option::get('sale', 'weight_unit', "", $this->order->getSiteId()));
		$this->weightKoef = htmlspecialcharsbx(Option::get('sale', 'weight_koef', 1, $this->order->getSiteId()));
		$this->isShowXmlId = Option::get("sale", "show_order_product_xml_id", "N") == "Y";

		if(strlen($jsObjName)>0 && strlen($idPrefix)>0)
		{
			$this->jsObjName = $jsObjName;

			$this->settingsDialog = new OrderBasketSettings(array(
				"ID_PREFIX" => $this->idPrefix,
				"SETTINGS_DLG_OBJECT_NAME" => $jsObjName.".settingsDialog",
				"ALL_COLUMNS" => array_merge(static::getDefaultVisibleColumns(), getAdditionalColumns()),
				"VISIBLE_COLUMNS" => $this->visibleColumns
			));
		}
	}

	/**
	 * @return string
	 */
	public function getEdit()
	{
		$productAddBool = Option::get('sale', 'SALE_ADMIN_NEW_PRODUCT', 'N');

		$result = '
			<div class="adm-s-gray-title" style="padding-right: 2px;">
				'.Loc::getMessage("SALE_ORDER_BASKET_COMPOSITION").'
				<div class="adm-s-gray-title-btn-container">
					<span
						class="adm-btn adm-btn-green adm-btn-add"
						onClick="'.$this->jsObjName.'.addProductSearch({lang: \''.LANGUAGE_ID.'\', siteId: \''.$this->order->getSiteId().'\', orderId: '.intval($this->order->getId()).'});"
						>'.
							Loc::getMessage("SALE_ORDER_BASKET_PRODUCT_ADD").
					'</span>
				</div>
				<div class="clb"></div>
			</div>';

		$result .= '
			<div class="adm-s-order-table-ddi">
				<table class="adm-s-order-table-ddi-table" style="width: 100%;" id="'.$this->idPrefix.'sale_order_edit_product_table">
					<thead style="text-align: left;">
					<tr>
						<td>
							<span class="adm-s-order-table-title-icon"
								title="'.Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_BUTTON_TITLE").'"
								onclick="'.$this->jsObjName.'.onHeadMenu(this);"
								>
							</span>
						</td>	';

		foreach($this->visibleColumns as $name)
			$result .= "<td>".$name."</td>";

		$result .= '</tr>
					</thead>
					<tbody style="border: 1px solid rgb(221, 221, 221);" id="'.$this->idPrefix.'sale-adm-order-edit-basket-empty-row">
						<tr>
							<td colspan="'.(count($this->visibleColumns)+1).'" style="padding: 20px;">
								'.Loc::getMessage("SALE_ORDER_BASKET_EMPTY_ROW").'.
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="adm-s-gray-title" style="padding-right: 2px;">
				<div class="adm-s-gray-title-btn-container">';

		if($productAddBool == "Y")
		{
			$result .= '<span
				class="adm-btn adm-btn-green adm-btn-add"
				onClick="'.$this->jsObjName.'.productEditDialog.show();"
				>'.
					Loc::getMessage("SALE_ORDER_BASKET_PRODUCT_NEW").
				'</span>&nbsp;';
		}

		$result .= '<span
						class="adm-btn adm-btn-green adm-btn-add"
						onClick="'.$this->jsObjName.'.addProductSearch({lang: \''.LANGUAGE_ID.'\', siteId: \''.$this->order->getSiteId().'\', index: 1, orderId: '.intval($this->order->getId()).'});"
						>'.
						Loc::getMessage("SALE_ORDER_BASKET_PRODUCT_ADD").
					'</span>
				</div>
				<div class="clb"></div>
			</div>
			<input type="hidden" name="BASKET[ID_PREFIX]" value="'.$this->idPrefix.'">
			<div class="adm-s-result-container">';

		$result .= $this->getCouponsHtml($this->order->getId() > 0 ? self::VIEW_MODE : self::EDIT_MODE, false);
		$result .= $this->getTotalHtml(false);
		$result .= '
			</div>
			<div class="clb"></div>';
		return $result;
	}

	public static function getBasePrice(Order $order)
	{
		return  $order->getPrice() - $order->getTaxValue() - $order->getDiscountPrice() - $order->getDeliveryPrice();
	}

	public function getTotalHtml($needRecalculate = true)
	{
		$currency = $this->order->getCurrency();
		$basket = $this->order->getBasket();
		$totalPrices = OrderEdit::getTotalPrices($this->order, $this, $needRecalculate);

		if($basket)
			$weight = $basket->getWeight();
		else
			$weight = 0;

		return '
				<div class="adm-s-result-container-itog">
					<table class="adm-s-result-container-itog-table">
						<tr>
							<tr>
								<td>'.Loc::getMessage("SALE_ORDER_BASKET_PRICE").'</td>
								<td id="'.$this->idPrefix.'sale_order_edit_basket_price_basket">'.
									\CCurrencyLang::currencyFormat(floatval($totalPrices["PRICE_BASKET"]), $currency, true).
								'</td>
							</tr>
							<tr>
								<td>'.Loc::getMessage("SALE_ORDER_BASKET_PRICE_DISCOUNT").'</td>
								<td id="'.$this->idPrefix.'sale_order_edit_basket_price_discount">'.
									\CCurrencyLang::currencyFormat(floatval($totalPrices["PRICE_BASKET_DISCOUNTED"]), $currency, true).
								'</td>
							</tr>
							<tr>
								<td>'.Loc::getMessage("SALE_ORDER_BASKET_PRICE_DELIVERY").'</td>
								<td id="'.$this->idPrefix.'sale_order_edit_basket_price_delivery">'.
									\CCurrencyLang::currencyFormat(floatval($totalPrices["PRICE_DELIVERY"]), $currency, true).
								'</td>
							</tr>
							<tr>
								<td>'.Loc::getMessage("SALE_ORDER_BASKET_PRICE_DELIVERY_DISCOUNT").'</td>
								<td id="'.$this->idPrefix.'sale_order_edit_basket_price_delivery_discount">'.
									\CCurrencyLang::currencyFormat(floatval($totalPrices["PRICE_DELIVERY_DISCOUNTED"]), $currency, true).
								'</td>
							</tr>
							<tr>
								<td>'.Loc::getMessage("SALE_ORDER_BASKET_TAX").'</td>
								<td id="'.$this->idPrefix.'sale_order_edit_basket_tax">'.
									\CCurrencyLang::currencyFormat(floatval($totalPrices["TAX_VALUE"]), $currency, true).
								'</td>
							</tr>
							<tr>
								<td>'.Loc::getMessage("SALE_ORDER_BASKET_WEIGHT").'</td>
								<td id="'.$this->idPrefix.'sale_order_edit_basket_weight">'.
									roundEx(floatval($weight/$this->weightKoef), SALE_WEIGHT_PRECISION)." ".$this->weightUnit.
								'</td>
							</tr>
							<tr>
								<td>'.Loc::getMessage("SALE_ORDER_BASKET_PAID").'</td>
								<td id="'.$this->idPrefix.'sale_order_edit_summ_paid">'.
										\CCurrencyLang::currencyFormat(
											floatval($totalPrices["SUM_PAID"]),
											$currency,
											true
										).
								'</td>
							</tr>
							<tr class="adm-s-result-container-itog-table-result">
								<td>'.Loc::getMessage("SALE_ORDER_BASKET_PRICE_TOTAL").'</td>
								<td id="'.$this->idPrefix.'sale_order_edit_basket_price_total">'.
									\CCurrencyLang::currencyFormat(
										floatval($totalPrices["SUM_UNPAID"]),
										$currency,
										true
									).
								'</td>
							</tr>
						</tr>
					</table>
				</div>
		';

	}

	public function getCouponsHtml($mode, $needRecalculate = true)
	{
		$showBlock = true;

		if($mode == self::VIEW_MODE)
		{
			$discounts = OrderEdit::getDiscountsApplyResult($this->order, $needRecalculate);
			$showBlock = !empty($discounts["COUPON_LIST"]);
		}

		if($showBlock)
		{
			$result =  '
				<div class="adm-s-result-container-promo">
					<div class="bx-adm-promocode-container">
						<div class="bx-adm-pc-section">
							<div class="bx-adm-pc-title">'.Loc::getMessage("SALE_ORDER_BASKET_PROMO").'</div>'.
							($mode == self::EDIT_MODE ?
								'<div class="bx-adm-pc-inputs-container">
									<input type="text" class="bx-adm-pc-inout-text" id="sale-admin-order-coupons">
									<input  type="submit" class="bx-adm-pc-input-submit" value='.Loc::getMessage("SALE_ORDER_BASKET_ADD").' onclick="BX.Sale.Admin.OrderBasketCoupons.onAddCoupons(); return false;">
								</div>' : '').
						'</div>
						<div class="bx-adm-pc-section">
							<ul class="bx-adm-pc-sale-list" id="sale-admin-order-coupons-container">
							</ul>
						</div>
					</div>
				</div>';
		}
		else
		{
			$result = '';
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function getView()
	{
		$result = '
			<div class="adm-s-order-table-ddi">
				<table class="adm-s-order-table-ddi-table" style="width: 100%;" id="'.$this->idPrefix.'sale_order_view_product_table">
					<thead style="text-align: left;">
					<tr>';

		foreach($this->visibleColumns as $colId => $name)
		{
			if($colId == "PROPS")
				continue;

			$result .= "<td>".$name."</td>";
		}

		$result .= '</tr>
					</thead>
				</table>
			</div>
			</div>
			<div class="adm-s-result-container">';

		$result .= $this->getCouponsHtml(self::VIEW_MODE, false);
		$result .= $this->getTotalHtml(false);
		$result .= '
			</div>
			<div class="clb"></div>';

		return $result;
	}

	/**
	 * @return string
	 */
	public function getScripts()
	{
		if(!static::$jsInited)
		{
			\Bitrix\Main\Page\Asset::getInstance()->addJs("/bitrix/js/sale/admin/order_basket.js");
			static::$jsInited = true;
		}

		$data = static::prepareData();
		$totalPrices = OrderEdit::getTotalPrices($this->order, $this, false);

		$langPhrases = array("SALE_ORDER_BASKET_TURN", "SALE_ORDER_BASKET_EXPAND", "SALE_ORDER_BASKET_UP_RATIO",
			"SALE_ORDER_BASKET_PROD_EDIT", "SALE_ORDER_BASKET_DOWN_RATIO", "SALE_ORDER_BASKET_PROD_COUNT",
			"SALE_ORDER_BASKET_NO_PICTURE", "SALE_ORDER_BASKET_PROD_CREATE", "SALE_ORDER_BASKET_ROW_SETTINGS",
			"SALE_ORDER_BASKET_PROD_MENU_EDIT", "SALE_ORDER_BASKET_PROD_MENU_DELETE", "SALE_ORDER_BASKET_BASE_CATALOG_PRICE",
			"SALE_ORDER_BASKET_PROD_EDIT_ITEM_SAVE", "SALE_ORDER_BASKET_KG", "SALE_ORDER_BASKET_COUPON",
			"SALE_ORDER_BASKET_COUPON_STATUS", "SALE_ORDER_BASKET_COUPON_APPLY", "SALE_ORDER_BASKET_COUPON_DELETE", "SALE_ORDER_BASKET_POSITION_EXISTS"
		);
		$result = '<script type="text/javascript">';

		foreach($langPhrases as $phrase)
			$result .= ' BX.message({'.$phrase.': "'.\CUtil::jsEscape(Loc::getMessage($phrase)).'"});';

		if($this->mode == self::EDIT_MODE)
		{
			$result .= '
				BX.ready(function(){
					'.$this->jsObjName.'= new BX.Sale.Admin.OrderBasketEdit({
						tableId: "'.$this->idPrefix.'sale_order_edit_product_table",
						idPrefix: "'.$this->idPrefix.'",
						productsOrder: '.\CUtil::phpToJSObject($data["ITEMS_ORDER"]).',
						products: '.\CUtil::phpToJSObject($data["ITEMS"]).',
						visibleColumns: '.\CUtil::phpToJSObject($this->visibleColumns).',
						objName: "'.$this->jsObjName.'",
						createProductBasement: '.($this->createProductBasement ? 'true' : 'false').',
						columnsCount: '.count($this->visibleColumns).',
						createBasketBottom: true,
						iblocksSkuParams: '.\CUtil::phpToJSObject($data["IBLOCKS_SKU_PARAMS"]).',
						productsOffersSkuParams: '.\CUtil::phpToJSObject($data["PRODUCTS_OFFERS_SKU"]).',
						isShowXmlId: '.($this->isShowXmlId ? 'true' : 'false').',
						discounts: '.\CUtil::phpToJSObject(OrderEdit::getOrderedDiscounts($this->order, false)).',
						mode: "edit",
						unRemovableFields: ["PRICE", "QUANTITY"],
						'.$this->getTotalBlockFieldsJs($totalPrices, $data).'
					});

					BX.Sale.Admin.OrderEditPage.registerFieldsUpdaters( '.$this->jsObjName.'.getFieldsUpdaters() );
					BX.Sale.Admin.OrderBasketCoupons.statusCouponApplyed = '.DiscountCouponsManager::STATUS_APPLYED.';
					BX.Sale.Admin.OrderBasketCoupons.mode = BX.Sale.Admin.OrderBasketCoupons.MODES_LIST.'.($this->order->getId() > 0 ? 'EDIT' : 'CREATE').';
					BX.Sale.Admin.OrderBasketCoupons.setCoupons('.
						\CUtil::phpToJSObject(
							OrderEdit::getCouponList($this->order, false)
						).
					');
				});';

			$result .= $this->settingsDialog->getScripts();
		}
		else
		{
			$result .= '
				BX.ready(function(){
					'.$this->jsObjName.'= new BX.Sale.Admin.OrderBasket({
						tableId: "'.$this->idPrefix.'sale_order_view_product_table",
						idPrefix: "'.$this->idPrefix.'",
						productsOrder: '.\CUtil::phpToJSObject($data["ITEMS_ORDER"]).',
						products: '.\CUtil::phpToJSObject($data["ITEMS"]).',
						visibleColumns: '.\CUtil::phpToJSObject($this->visibleColumns).',
						objName: "'.$this->jsObjName.'",
						createProductBasement: '.($this->createProductBasement ? 'true' : 'false').',
						iblocksSkuParams: '.\CUtil::phpToJSObject($data["IBLOCKS_SKU_PARAMS"]).',
						columnsCount: '.count($this->visibleColumns).',
						isShowXmlId: '.($this->isShowXmlId ? 'true' : 'false').',
						discounts: '.\CUtil::phpToJSObject(OrderEdit::getOrderedDiscounts($this->order, false)).',
						createBasketBottom: true,
						mode: "view",
						'.$this->getTotalBlockFieldsJs($totalPrices, $data).'
					});

					BX.Sale.Admin.OrderEditPage.registerFieldsUpdaters( '.$this->jsObjName.'.getFieldsUpdaters() );
					BX.Sale.Admin.OrderBasketCoupons.mode = BX.Sale.Admin.OrderBasketCoupons.MODES_LIST.VIEW;
					BX.Sale.Admin.OrderBasketCoupons.setCoupons('.
						\CUtil::phpToJSObject(
							OrderEdit::getCouponList($this->order, false)
						).
					');
				});';

			$result .= $this->settingsDialog->getScripts();
		}

		$result .= '</script>';
		return $result;
	}

	protected function getTotalBlockFieldsJs($totalPrices, $data)
	{
		return '
			totalBlockFields: {
			PRICE_BASKET: {
				id: "'.$this->idPrefix.'sale_order_edit_basket_price_basket",
									value: "'.roundEx(floatval($totalPrices["PRICE_BASKET"]), SALE_VALUE_PRECISION).'",
									type: "currency"
								},
			PRICE_BASKET_DISCOUNTED: {
				id: "'.$this->idPrefix.'sale_order_edit_basket_price_discount",
									value: "'.roundEx(floatval($totalPrices["PRICE_BASKET_DISCOUNTED"]), SALE_VALUE_PRECISION).'",
									type: "currency"
								},
			PRICE_DELIVERY: {
				id: "'.$this->idPrefix.'sale_order_edit_basket_price_delivery",
									value: "'.roundEx(floatval($totalPrices["PRICE_DELIVERY"]), SALE_VALUE_PRECISION).'",
									type: "currency"
								},
			PRICE_DELIVERY_DISCOUNTED: {
				id: "'.$this->idPrefix.'sale_order_edit_basket_price_delivery_discount",
									value: "'.roundEx(floatval($totalPrices["PRICE_DELIVERY_DISCOUNTED"]), SALE_VALUE_PRECISION).'",
									type: "currency"
								},
			TAX_VALUE: {
				id: "'.$this->idPrefix.'sale_order_edit_basket_tax",
									value: "'.roundEx(floatval($totalPrices["TAX_VALUE"]), SALE_VALUE_PRECISION).'",
									type: "currency"
								},
			WEIGHT: {
				id: "'.$this->idPrefix.'sale_order_edit_basket_weight",
									value: "'.roundEx(floatval($data["WEIGHT"]), SALE_WEIGHT_PRECISION).'",
									type: "weight"
								},
			SUM_PAID: {
				id: "'.$this->idPrefix.'sale_order_edit_summ_paid",
									value: "'.roundEx(floatval($totalPrices["SUM_PAID"]), SALE_VALUE_PRECISION).'",
									//edit: true,
									type: "currency"
								},
			SUM_UNPAID: {
				id: "'.$this->idPrefix.'sale_order_edit_basket_price_total",
									value: "'.roundEx(floatval($totalPrices["SUM_UNPAID"]), SALE_VALUE_PRECISION).'",
									type: "currency"
								}
		}';
	}

	/**
	 * @param array $discounts
	 * @return array|null
	 * @throws Main\LoaderException
	 */
	public function prepareData(array $inParams = array())
	{
		if($this->data === null)
		{
			$result = array(
				"ITEMS" => array(),
				"WEIGHT" => 0
			);

			$basket = $this->order->getBasket();

			if (empty($inParams["DISCOUNTS"]))
				$inParams["DISCOUNTS"] = OrderEdit::getDiscountsApplyResult($this->order, true);

			$basketDiscount = 0;
			$basketPrice = 0;
			$basketPriceBase = 0;

			if(!$basket)
			{
				$result["WEIGHT_FOR_HUMAN"] = roundEx(floatval(0), SALE_WEIGHT_PRECISION);
				$this->data = $result;
				return $result;
			}

			$result["WEIGHT"] = $basket->getWeight();
			$result["WEIGHT_FOR_HUMAN"] = roundEx(floatval($result["WEIGHT"]/$this->weightKoef), SALE_WEIGHT_PRECISION);
			$items = $basket->getBasketItems();

			/** @var \Bitrix\Sale\BasketItem $item */
			if (self::$catalogIncluded === null)
				self::$catalogIncluded = Main\Loader::includeModule('catalog');

			foreach($items as $item)
			{
				$productId = $item->getProductId();

				$params = array();

				if($item->getField("MODULE") == "catalog")
				{
					$params = static::getProductDetails($productId, $item->getQuantity(), $this->order->getUserId(), $this->order->getSiteId(), $this->visibleColumns);
				}
				elseif (strval($item->getField("MEASURE_CODE")) != '' && self::$catalogIncluded)
				{
					$measures = OrderBasket::getCatalogMeasures();
					if(isset($measures[$item->getField("MEASURE_CODE")]) && strlen($measures[$item->getField("MEASURE_CODE")]) > 0)
					{
						$params["MEASURE_TEXT"] = $measures[$item->getField("MEASURE_CODE")];
					}


					if (strval($params["MEASURE_TEXT"]) == '')
					{
						$defaultMeasure = static::getDefaultMeasures();
						$params["MEASURE_TEXT"] = ($defaultMeasure["SYMBOL_RUS"] != '' ? $defaultMeasure["SYMBOL_RUS"] : $defaultMeasure["SYMBOL_INTL"]);
					}
				}

				if(!isset($params["OFFER_ID"]))
					$params["OFFER_ID"] = $productId;

				$params["BASKET_CODE"] = $basketCode = $item->getBasketCode();
				$params["PRODUCT_PROVIDER_CLASS"] = $item->getProvider();
				$id = $params["PRODUCT_ID"];
				$params = array_merge($params, $item->getFieldValues(), array("PRODUCT_ID" => $id));
				$params["PROPS"] = array();
				$params["QUANTITY"] = Sale\BasketItem::formatQuantity($params["QUANTITY"]);

				/** @var \Bitrix\Sale\BasketPropertyItem $property */
				foreach($item->getPropertyCollection() as  $property)
				{
					$params["PROPS"][] = array(
						"VALUE" => $property->getField("VALUE"),
						"NAME" => $property->getField("NAME"),
						"CODE" => $property->getField("CODE"),
						"SORT" => $property->getField("SORT")
					);
				}

				if(isset($inParams["DISCOUNTS"]["RESULT"]["BASKET"][$basketCode]) && is_array($inParams["DISCOUNTS"]["RESULT"]["BASKET"][$basketCode]))
					foreach($inParams["DISCOUNTS"]["RESULT"]["BASKET"][$basketCode] as $discount)
						$params["DISCOUNTS"][$discount["DISCOUNT_ID"]] = $discount;

				if(isset($inParams["DISCOUNTS"]["PRICES"]["BASKET"][$basketCode]))
				{
					$params["PRICE_BASE"] = roundEx($inParams["DISCOUNTS"]["PRICES"]["BASKET"][$basketCode]["BASE_PRICE"], SALE_VALUE_PRECISION);
					$params["PRICE"] = roundEx($inParams["DISCOUNTS"]["PRICES"]["BASKET"][$basketCode]["PRICE"], SALE_VALUE_PRECISION);
					$basketPriceBase += $params["PRICE_BASE"] * $params["QUANTITY"];
					$basketPrice += $params["PRICE"] * $params["QUANTITY"];

					if (!$item->isCustomPrice())
						$basketDiscount += $inParams["DISCOUNTS"]["PRICES"]["BASKET"][$basketCode]["DISCOUNT"] * $params["QUANTITY"];
				}
				else
				{
					// TODO: for old orders
				}

				$params["CUSTOM_PRICE"] = $item->isCustomPrice() ? "Y" : "N";

				//Let's cache provider product data into form field
				if(Provider::isExistsTrustData($this->order->getSiteId(), 'sale', $item->getProductId()))
				{
					$providerData = Provider::getTrustData($this->order->getSiteId(), 'sale', $item->getProductId());

					if(is_array($providerData) && !empty($providerData))
						$params["PROVIDER_DATA"] = serialize($providerData);
				}

				if(is_array($params["SET_ITEMS"]) && !empty($params["SET_ITEMS"]))
					$params["SET_ITEMS_DATA"] = serialize($params["SET_ITEMS"]);


				$result["ITEMS"][$basketCode] = $params;
			}

			$result["BASKET_PRICE_BASE"] = roundEx($basketPriceBase, SALE_VALUE_PRECISION);
			$result["BASKET_PRICE"] = roundEx($basketPrice, SALE_VALUE_PRECISION);
			$result["DISCOUNT_VALUE"] = roundEx($basketDiscount, SALE_VALUE_PRECISION);
			$result["ITEMS_ORDER"] = array_keys($result["ITEMS"]);

			if(!isset($inParams["SKIP_SKU_INFO"]) ||  $inParams["SKIP_SKU_INFO"] != true)
				$result = static::getOffersSkuParams($result);

			$this->data = $result;
		}

		return $this->data;
	}

	public static function getOffersSkuParams(array $productsParams)
	{
		if (self::$catalogIncluded === null)
			self::$catalogIncluded = Main\Loader::includeModule('catalog');
		if (!self::$catalogIncluded)
			return $productsParams;

		foreach($productsParams["ITEMS"] as &$params)
		{
			if(!isset(self::$productsOffersSkuParams[$params["PRODUCT_ID"]]))
			{
				$propFilter = array();
				$props = static::getSkuProps(true, $params["IBLOCK_ID"]);

				if ($props)
					foreach($props as $prop)
						$propFilter['ID'][] = $prop['ID'];

				$select = array('NAME',  "ACTIVE", 'CATALOG_QUANTITY');
				$offers = \CCatalogSKU::getOffersList(array($params["PRODUCT_ID"]), 0, array(), $select, $propFilter);

				if($offers)
				{
					$tmpProps = static::getPropsFromOffers2($offers);
					self::$productsOffersSkuParams[$params["PRODUCT_ID"]] = $tmpProps[$params["PRODUCT_ID"]];
				}
			}

			if(intval($params["OFFERS_IBLOCK_ID"]) > 0 && !isset(self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]]))
				self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]] = static::getPropsParams($params["OFFERS_IBLOCK_ID"]);

//			if(intval($params["IBLOCK_ID"]) > 0 && !isset($iblockPropsParams[$params["IBLOCK_ID"]]))
//				$iblockPropsParams[$params["IBLOCK_ID"]] = static::getPropsParams($params["IBLOCK_ID"]);

			$params["SKU_PROPS"] = self::$productsOffersSkuParams[$params["PRODUCT_ID"]][$params["OFFER_ID"]];
			if (is_array($params["SKU_PROPS"]))
			{
				foreach ($params["SKU_PROPS"] as $id => $skuProps)
				{
					$params["SKU_PROPS"][$id] = array(
						'ID' => $skuProps,
						'NAME' => self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]][$id]['NAME'],
						'VALUE' => self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]][$id]['VALUES'][$skuProps],
						'CODE' => self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]][$id]['CODE'],
						'SORT' => self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]][$id]['SORT']
					);
				}
			}
		}

		$productsParams["IBLOCKS_SKU_PARAMS"] = self::$iblockPropsParams;
		$productsParams["PRODUCTS_OFFERS_SKU"] = self::$productsOffersSkuParams;

		return $productsParams;
	}

	/**
	 * @param int $productId
	 * @param int $quantity
	 * @param int $userId
	 * @param string $siteId
	 * @param array $columns
	 * @return array
	 * @throws SystemException
	 */

	public static function getProductDetails($productId, $quantity, $userId, $siteId, array $columns = array())
	{
		$stored = true;
		$result = OrderEdit::getProductDetails($productId, $userId, $siteId);

		if($result === false)
			$stored = false;

		if($result === false)
		{
			if (self::$catalogIncluded === null)
				self::$catalogIncluded = Main\Loader::includeModule('catalog');
			if (!self::$catalogIncluded)
				return array();

			$result = self::getProductDataToFillBasket($productId, $quantity, $userId, $siteId, implode(",",array_keys($columns)));

			if (!empty($result) && is_array($result))
			{
				if (isset($result['DISCOUNT_PRICE']))
				{
					$result['DISCOUNT_PRICE'] = roundEx($result['DISCOUNT_PRICE'], SALE_VALUE_PRECISION);
				}

				if (isset($result['PRICE']))
				{
					$result['PRICE'] = roundEx($result['PRICE'], SALE_VALUE_PRECISION);
				}

				if (isset($result['PRICE_BASE']))
				{
					$result['PRICE_BASE'] = roundEx($result['PRICE_BASE'], SALE_VALUE_PRECISION);
				}
			}
			static $proxyProductData = array();

			if (!empty($proxyProductData[$productId]) && is_array($proxyProductData[$productId]))
			{
				$productInfo = $proxyProductData[$productId];
			}
			else
			{
				$productInfo = \CCatalogSku::getProductInfo($productId);
				$proxyProductData[$productId] = $productInfo;
			}

			if($productInfo != false)
			{
				$result["OFFERS_IBLOCK_ID"] = $productInfo["OFFER_IBLOCK_ID"];
				$result["IBLOCK_ID"] = $productInfo["IBLOCK_ID"];
				$result["PRODUCT_ID"] = $productInfo["ID"];
			}
			else
			{
				$result["OFFERS_IBLOCK_ID"] = 0;
				$result["IBLOCK_ID"] = $result["PRODUCT_PROPS_VALUES"]["IBLOCK_ID"];
				$result["PRODUCT_ID"] = $result["PRODUCT_PROPS_VALUES"]["ID"];
			}
		}

		if(!$stored)
			OrderEdit::setProductDetails($productId, $userId, $siteId, $result);

		return $result;
	}

	protected static function getPropsParams($iblockId)
	{
		$arRes = array();
		$bUseHLIblock = \Bitrix\Main\Loader::includeModule('highloadblock');

		$rsProps = \CIBlockProperty::getList(
			array('SORT' => 'ASC', 'ID' => 'ASC'),
			array('IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y')
		);

		while ($arProp = $rsProps->fetch())
		{
			if ($arProp['PROPERTY_TYPE'] == 'L' || $arProp['PROPERTY_TYPE'] == 'E' || ($arProp['PROPERTY_TYPE'] == 'S' && $arProp['USER_TYPE'] == 'directory'))
			{
				if ($arProp['XML_ID'] == 'CML2_LINK')
					continue;

				$arValues = array();

				if ($arProp['PROPERTY_TYPE'] == 'L')
				{
					$arValues = array();
					$rsPropEnums = \CIBlockProperty::getPropertyEnum($arProp['ID']);
					while ($arEnum = $rsPropEnums->fetch())
					{
						$arValues[$arEnum['VALUE']] = array(
							'ID' => $arEnum['ID'],
							'NAME' => $arEnum['VALUE'],
							'PICT' => false
						);
					}
				}
				elseif ($arProp['PROPERTY_TYPE'] == 'E')
				{
					$rsPropEnums = \CIBlockElement::getList(
						array('SORT' => 'ASC'),
						array('IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'], 'ACTIVE' => 'Y'),
						false,
						false,
						array('ID', 'NAME', 'PREVIEW_PICTURE')
					);
					while ($arEnum = $rsPropEnums->Fetch())
					{
						$arEnum['PREVIEW_PICTURE'] = \CFile::getFileArray($arEnum['PREVIEW_PICTURE']);

						if (!is_array($arEnum['PREVIEW_PICTURE']))
						{
							$arEnum['PREVIEW_PICTURE'] = false;
						}

						if ($arEnum['PREVIEW_PICTURE'] !== false)
						{
							$productImg = \CFile::resizeImageGet($arEnum['PREVIEW_PICTURE'], array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
							$arEnum['PREVIEW_PICTURE']['SRC'] = $productImg['src'];
						}

						$arValues[$arEnum['NAME']] = array(
							'ID' => $arEnum['ID'],
							'NAME' => $arEnum['NAME'],
							'SORT' => $arEnum['SORT'],
							'PICT' => $arEnum['PREVIEW_PICTURE']
						);
					}
				}
				elseif ($arProp['PROPERTY_TYPE'] == 'S' && $arProp['USER_TYPE'] == 'directory')
				{
					if ($bUseHLIblock)
					{
						$hlblock = HL\HighloadBlockTable::getList(array("filter" => array("TABLE_NAME" => $arProp["USER_TYPE_SETTINGS"]["TABLE_NAME"])))->fetch();
						if ($hlblock)
						{
							$entity = HL\HighloadBlockTable::compileEntity($hlblock);
							$entity_data_class = $entity->getDataClass();
							$rsData = $entity_data_class::getList();

							while ($arData = $rsData->fetch())
							{
								$arValues[$arData['UF_XML_ID']] = array(
									'ID' => $arData['ID'],
									'NAME' => $arData['UF_NAME'],
									'SORT' => $arData['UF_SORT'],
									'FILE' => $arData['UF_FILE'],
									'PICT' => '',
									'XML_ID' => $arData['UF_XML_ID']
								);
							}

						}
					}
				}

				if (!empty($arValues) && is_array($arValues))
				{
					$arRes[$arProp['ID']] = array(
						'ID' => $arProp['ID'],
						'CODE' => $arProp['CODE'],
						'NAME' => $arProp['NAME'],
						'TYPE' => $arProp['PROPERTY_TYPE'],
						'ORDER' => array_keys($arValues),
						'VALUES' => $arValues,
						'SORT' =>  $arProp['SORT']
					);
				}
			}

			if ($arProp['PROPERTY_TYPE'] == "S" && is_array($arRes[$arProp['ID']]['VALUES']))
			{
				foreach($arRes[$arProp['ID']]['VALUES'] as $id => $value)
				{
					$arTmpFile = \CFile::getFileArray($value["FILE"]);
					$tmpImg = \CFile::resizeImageGet($arTmpFile, array('width'=>20, 'height'=>20), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
					$arRes[$arProp['ID']]['VALUES'][$id]['PICT'] = $tmpImg['src'];
				}
			}
		}

		return $arRes;
	}

	protected static function getPropsFromOffers2(array $items)
	{
		$props = array();

		foreach($items as $id => $item)
		{
			if(!isset($props[$id]))
				$props[$id] = array();

			foreach($item as $offerId => $offer)
			{
				if(!isset($offer["PROPERTIES"]) || $offer["ACTIVE"] != "Y")
					continue;

				if(!isset($props[$id][$offerId]))
					$props[$id][$offerId] = array();

				foreach($offer["PROPERTIES"] as $propCode => $propParams)
				{
					if($propParams["MULTIPLE"] == "Y")
						continue;

					if(!$propParams["VALUE"] || $propParams["ACTIVE"] != "Y")
						continue;

					if($propParams['PROPERTY_TYPE'] == 'L'
						|| $propParams['PROPERTY_TYPE'] == 'E'
						|| (
							$propParams['PROPERTY_TYPE'] == 'S'
							&& $propParams['USER_TYPE'] == 'directory'
							)
						)
					{

						$props[$id][$offerId][$propParams["ID"]] = $propParams["VALUE"];
					}
				}
			}
		}

		return $props;
	}


	protected static function getSkuProps($flagAll = false, $iblockId)
	{
		if (static::$arSkuProps[$iblockId] === null)
		{
			$arCatalog = static::getOffersCatalog($iblockId);
			static::$arSkuProps[$iblockId] = $arCatalog? static::getPropsList($arCatalog["IBLOCK_ID"], $arCatalog['SKU_PROPERTY_ID']) : array();
		}
		return $flagAll? static::$arSkuProps[$iblockId] : static::filterProps(static::$arSkuProps[$iblockId]);
	}

	protected function getOffersCatalog($iblockId)
	{
		if (self::$catalogIncluded === null)
			self::$catalogIncluded = Main\Loader::includeModule('catalog');
		if (!self::$catalogIncluded)
			return array();

		if (!isset(static::$offersCatalog[$iblockId]))
			static::$offersCatalog[$iblockId] = \CCatalogSKU::getInfoByProductIBlock($iblockId);

		return static::$offersCatalog[$iblockId];
	}

	protected function getPropsList($iblockId, $skuPropertyId = 0)
	{
		$arResult = array();
		$dbrFProps = \CIBlockProperty::getList(
			array(
				"SORT" => "ASC",
				"NAME" => "ASC"
			),
			array(
				"IBLOCK_ID" => $iblockId,
				"ACTIVE" => "Y",
				"!XML_ID" => "CML2_LINK",
				"CHECK_PERMISSIONS" => "N",
			)
		);
		while ($arProp = $dbrFProps->getNext())
		{
			if ($skuPropertyId == $arProp['ID'])
				continue;
			$arProp["PROPERTY_USER_TYPE"] = (!empty($arProp["USER_TYPE"]) ? \CIBlockProperty::getUserType($arProp["USER_TYPE"]) : array());
			$arResult[] = $arProp;
		}
		return $arResult;
	}

	protected function filterProps(&$props)
	{
		$result = array();
		if ($props)
		{
			foreach ($props AS $prop)
			{
				if ($prop['FILTRABLE'] == 'Y' && $prop['PROPERTY_TYPE'] != 'F')
					$result[] = $prop;
			}
		}
		return $result;
	}

	public function getSettingsDialogContent()
	{
		return $this->settingsDialog->getHtml();
	}

	public static function loadVisibleColumns($idPrefix)
	{
		return \CUserOptions::GetOption($idPrefix."order_basket_table", "table_columns");
	}

	protected static function getDefaultVisibleColumns()
	{
		return array(
			"IMAGE" => Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_COL_IMAGE"),
			"NAME" => Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_COL_NAME"),
			"QUANTITY" => Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_COL_QUANTITY"),
			"AVAILABLE" => Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_COL_AVAILABLE"),
			"PROPS" => Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_COL_PROPS"),
			"PRICE" => Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_COL_PRICE"),
			"SUM" => Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_COL_SUM")
		);
	}

	protected function getVisibleColumns()
	{
		if (self::$iblockIncluded === null)
			self::$iblockIncluded = Main\Loader::includeModule('iblock');
		if (!self::$iblockIncluded)
			return array();

		$result = array();
		$arTmpColumns = array();
		$arColumnsOptions = static::loadVisibleColumns($this->idPrefix);

		if (is_array($arColumnsOptions) && isset($arColumnsOptions["columns"]) && strlen($arColumnsOptions["columns"]) > 0)
			$arTmpColumns = explode(",", $arColumnsOptions["columns"]);

		if(is_array($arTmpColumns) && !empty($arTmpColumns))
		{
			$iBlockProps = array();
			$iBlockPropsInt = array();

			foreach ($arTmpColumns as $id => $columnCode)
			{
				if (substr($columnCode, 0, 9) == "PROPERTY_")
				{
					$iblockPropCode = substr($columnCode, 9);

					if(strlen($iblockPropCode) > 0)
					{
						$iBlockProps[] = $iblockPropCode;

						if(intval($iblockPropCode) > 0)
							$iBlockPropsInt[] = intval($iblockPropCode);

						$result[$columnCode] = "";
					}
				}
				else
				{
					$result[$columnCode] = Loc::getMessage("SALE_ORDER_BASKET_SETTINGS_COL_".$columnCode);
				}
			}

			if(!empty($iBlockProps))
			{
				$dbRes = \Bitrix\Iblock\PropertyTable::getList(array(
					'filter' => array(
						'LOGIC' => 'OR',
						'=CODE' => $iBlockProps,
						'=ID' => $iBlockPropsInt
					),
					'select' => array('ID', 'NAME', 'CODE')
				));

				while($arPropData = $dbRes->fetch())
				{
					if(strlen($arPropData['CODE']) > 0)
						$result["PROPERTY_".$arPropData['CODE']] = $arPropData["NAME"];
					else
						$result["PROPERTY_".$arPropData['ID']] = $arPropData["NAME"];
				}
			}
		}
		else
		{
			$result = static::getDefaultVisibleColumns();
		}

		return $result;
	}

	public static function getProductEditDialogHtml($currency, $objName)
	{
		return '
			<input id="FORM_BASKET_PRODUCT_ID" name="BASKET_PRODUCT_ID" value="" type="hidden">
			<input id="FORM_PROD_BASKET_CUSTOM_PRICE" name="BASKET_CUSTOM_PRICE" value="Y" type="hidden">
			<table class="edit-table">
				<tr>
					<td width="40%">&nbsp;</td>
					<td align="left" width="60%">
					<div id="basketError" style="display:none;">
						<table class="message message-error" border="0" cellpadding="0" cellspacing="0" style="border:2px solid #FF0000;color:#FF0000">
							<tr>
								<td>
									<table class="content" border="0" cellpadding="0" cellspacing="0" style="margin:4px;">
										<tr>
											<td valign="top"><div class="icon-error"></div></td>
											<td>
												<span class="message-title" style="font-weight:bold;">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ERROR").'</span><br>
												<div class="empty" style="height: 5px;"></div><div id="basketErrorText"></div>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</div></td>
				</tr>
				<tr id="FORM_NEWPROD_CODE">
					<td class="adm-detail-content-cell-l" width="30%">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_ID").':</td>
					<td  width="70%"><input size="10" id="FORM_PROD_BASKET_OFFER_ID" name="FORM_PROD_BASKET_OFFER_ID" type="text" value="" tabindex="1"></td>
				</tr>
				<tr class="adm-detail-required-field">
					<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_NAME").':</td>
					<td><input size="40" id="FORM_PROD_BASKET_NAME" name="FORM_PROD_BASKET_NAME" type="text" value="" tabindex="2" onkeyup="'.$objName.'.productEditDialog.disableButton();"></td>
				</tr>
				<tr>
					<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_PATH").':</td>
					<td><input id="FORM_PROD_BASKET_DETAIL_PAGE_URL" name="FORM_PROD_BASKET_DETAIL_PAGE_URL" value="" size="40" type="text" tabindex="3"></td>
				</tr>
				<tr>
					<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_CATALOG_XML").':</td>
					<td><input id="FORM_PROD_BASKET_CATALOG_XML_ID" name="FORM_PROD_BASKET_CATALOG_XML_ID" value="" size="40" type="text" tabindex="4"></td>
				</tr>
				<tr>
					<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_PRODUCT_XML").':</td>
					<td><input id="FORM_PROD_BASKET_PRODUCT_XML_ID" name="FORM_PROD_BASKET_PRODUCT_XML_ID" value="" size="40" type="text" tabindex="5"></td>
				</tr>
				<tr>
					<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_NOTES").':</td>
					<td><input name="FORM_PROD_BASKET_NOTES" id="FORM_PROD_BASKET_NOTES" size="40" maxlength="250" value="" type="text" tabindex="6"></td>
				</tr>
				<tr>
					<td class="adm-detail-content-cell-l" valign="top" width="40%">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_PROPS").':</td>
					<td width="60%">
						<table id="BASKET_PROP_TABLE" class="internal" border="0" cellpadding="3" cellspacing="1" style="width: 521px; margin-top: 20px;">
							<tr class="heading" style="border-collapse:collapse;background-color:#E7EAF5;color:#525355;">
								<td align="center">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_IP_NAME").'</td>
								<td align="center">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_IP_VALUE").'</td>
								<td align="center">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_IP_CODE").'</td>
								<td align="center">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_IP_SORT").'</td>
							</tr>
							<tr id="FORM_PROD_BASKET_EMPTY_PROP_ROW">
								<td colspan="4" style="text-align: center;">
									'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_EMPTY_PROP_ROW").'
								</td>
							</tr>
						</table>
						<div width="100%" style="text-align: right;">
							<input value="'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_PROPERTY_MORE").'" onclick="'.$objName.'.productEditDialog.addPropRow();" type="button" style="margin-top: 10px;">
						</div>
					</td>
				</tr>
				<tr class="adm-detail-required-field">
					<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_QUANTITY").':</td>
					<td>
						<input name="FORM_PROD_BASKET_QUANTITY" id="FORM_PROD_BASKET_QUANTITY" size="10" maxlength="20" value="" type="text" tabindex="7" onkeyup="'.$objName.'.productEditDialog.disableButton();">&nbsp;
						'.OrderEdit::makeSelectHtml(
							'FORM_PROD_BASKET_MEASURE_CODE',
							self::getCatalogMeasures(),
							'',
							true,
							array('id' => 'FORM_PROD_BASKET_MEASURE_CODE')
						).'
					</td>
				</tr>
				<tr  class="adm-detail-required-field">
					<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_PRICE").':</td>
					<td><input name="FORM_PROD_BASKET_PRICE" id="FORM_PROD_BASKET_PRICE" size="10" maxlength="20" value="" type="text" tabindex="8" onkeyup="'.$objName.'.productEditDialog.disableButton();"> ('.\CUtil::JSEscape($currency).')</td>
				</tr>
				<tr>
					<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_WEIGHT").':</td>
					<td><input name="FORM_PROD_BASKET_WEIGHT" id="FORM_PROD_BASKET_WEIGHT" size="10" maxlength="20" value="" type="text" tabindex="9"> ('.Loc::getMessage("SALE_ORDER_BASKET_PROD_EDIT_ITEM_GRAMM").')</td>
				</tr>
			</table>';
	}

	public static function getCatalogMeasures()
	{
		static $result = null;

		if(!is_array($result))
		{
			$result = array();

			if (self::$catalogIncluded === null)
				self::$catalogIncluded = Main\Loader::includeModule('catalog');
			if (self::$catalogIncluded)
			{
				$dbList = \CCatalogMeasure::getList();
				while($arList = $dbList->Fetch())
					$result[$arList["CODE"]] = ($arList["SYMBOL_RUS"] != '' ? $arList["SYMBOL_RUS"] : $arList["SYMBOL_INTL"]);
			}

			if (empty($result))
				$result[796] = GetMessage("SALE_ORDER_BASKET_SHTUKA");
		}

		return $result;
	}

	public static function getDefaultMeasures()
	{
		return \CCatalogMeasure::getDefaultMeasure(true, true);
	}

	/**
	 * @param $productId
	 * @param $quantity
	 * @param $userId
	 * @param $LID
	 * @param $userColumns
	 * @param string $tmpId we can suggest that this mean the set_item
	 * @return array
	 * @throws Main\LoaderException
	 */
	protected function getProductDataToFillBasket($productId, $quantity, $userId, $LID, $userColumns, $tmpId = "")
	{
		$isSetItem = $tmpId != "";

		if (self::$catalogIncluded === null)
			self::$catalogIncluded = Main\Loader::includeModule('catalog');
		if (!self::$catalogIncluded)
			return array();

		$arParams = array();

		static $proxyIblockElement = array();
		static $proxyCatalogMeasure = array();
		static $proxyParent = array();
		static $proxyIblockProperty = array();
		static $proxyProductData = array();
		static $proxyCatalogProduct = array();
		static $proxyCatalogMeasureRatio = array();

		$productId = (int)$productId;
		if ($productId <= 0)
		{
			return $arParams;
		}

		if (!empty($proxyIblockElement[$productId]))
		{
			$iblockId = $proxyIblockElement[$productId];
		}
		else
		{
			$iblockId = (int)\CIBlockElement::getIBlockByID($productId);

			if ($iblockId > 0)
				$proxyIblockElement[$productId] = $iblockId;
		}

		if ($iblockId <= 0)
		{
			return $arParams;
		}

		$arSku2Parent = array();
		$arElementId = array();

		$arElementId[] = $productId;

		$proxyParentKey = $productId."|".$iblockId;

		if (!empty($proxyParent[$proxyParentKey]) && is_array($proxyParent[$proxyParentKey]))
		{
			$arParent = $proxyParent[$proxyParentKey];
		}
		else
		{
			$arParent = \CCatalogSku::getProductInfo($productId, $iblockId);
			$proxyParent[$proxyParentKey] = $arParent;
		}


		if ($arParent)
		{
			$arElementId[] = $arParent["ID"];
			$arSku2Parent[$productId] = $arParent["ID"];
		}

		$arPropertyInfo = array();
			$userColumns = (string)$userColumns;
		$arUserColumns = ($userColumns != '') ? explode(",", $userColumns) : array();
		foreach ($arUserColumns as $key => $column)
		{
			if (strncmp($column, 'PROPERTY_', 9) != 0)
			{
				unset($arUserColumns[$key]);
			}
			else
			{
				$propertyCode = substr($column, 9);
				if ($propertyCode == '')
				{
					unset($arUserColumns[$key]);
					continue;
				}

				if (!empty($proxyIblockProperty[$propertyCode]) && is_array($proxyIblockProperty[$propertyCode]))
				{
					$arPropertyInfo[$column] = $proxyIblockProperty[$propertyCode];
				}
				else
				{
					$dbres = \CIBlockProperty::GetList(array(), array("CODE" => $propertyCode));
					if ($arPropData = $dbres->GetNext())
					{
						$arPropertyInfo[$column] = $arPropData;
						$proxyIblockProperty[$propertyCode] = $arPropData;
					}
				}

			}
		}

		$arSelect = array_merge(
			array("ID", "NAME", "IBLOCK_ID", "IBLOCK_SECTION_ID", "DETAIL_PICTURE", "PREVIEW_PICTURE", "XML_ID", "IBLOCK_XML_ID"),
			$arUserColumns
		);


		$proxyProductDataKey = md5(join('|', $arElementId)."_".join('|', $arSelect));
		if (!empty($proxyProductData[$proxyProductDataKey]) && is_array($proxyProductData[$proxyProductDataKey]))
		{
			$arProductData = $proxyProductData[$proxyProductDataKey];
		}
		else
		{
			$arProductData = getProductProps($arElementId, $arSelect);
			$proxyProductData[$proxyProductDataKey] = $arProductData;
		}

		$defaultMeasure = \CCatalogMeasure::getDefaultMeasure(true, true);

		if (!empty($arProductData))
		{
			$arElementInfo = array();
			foreach ($arProductData as $elemId => &$arElement)
			{
				foreach ($arElement as $key => $value)
				{
					if (strncmp($key, 'PROPERTY_', 9) == 0 && substr($key, -6) == "_VALUE")
					{
						$columnCode = str_replace("_VALUE", "", $key);
						$arElement[$key] = getIblockPropInfo($value, $arPropertyInfo[$columnCode], array("WIDTH" => 90, "HEIGHT" => 90));
					}
				}
			}
			unset($arElement);

			if (isset($arProductData[$productId]))
				$arElementInfo = $arProductData[$productId];

			if (isset( $arSku2Parent[$productId]))
				$arParent = $arProductData[$arSku2Parent[$productId]];

			if (!empty($arSku2Parent)) // if sku element doesn't have value of some property - we'll show parent element value instead
			{
				foreach ($arUserColumns as $field)
				{
					$fieldVal = $field."_VALUE";
					$parentId = $arSku2Parent[$productId];

					if ((!isset($arElementInfo[$fieldVal]) || (isset($arElementInfo[$fieldVal]) && strlen($arElementInfo[$fieldVal]) == 0))
						&& (isset($arProductData[$parentId][$fieldVal]) && !empty($arProductData[$parentId][$fieldVal]))) // can be array or string
					{
						$arElementInfo[$fieldVal] = $arProductData[$parentId][$fieldVal];
					}
				}
				if (strpos($arElementInfo["~XML_ID"], '#') === false)
				{
					$arElementInfo["~XML_ID"] = $arParent['~XML_ID'].'#'.$arElementInfo["~XML_ID"];
				}
			}

			$arElementInfo["MODULE"] = "catalog";
			$arElementInfo["PRODUCT_PROVIDER_CLASS"] = "CCatalogProductProvider";

			$arElementInfo["PRODUCT_ID"] = $arElementInfo["ID"];

			if ($arElementInfo["IBLOCK_ID"] > 0)
			{
				$arElementInfo["EDIT_PAGE_URL"] = \CIBlock::GetAdminElementEditLink($arElementInfo["IBLOCK_ID"], $arElementInfo["PRODUCT_ID"], array(
					"find_section_section" => $arElementInfo["IBLOCK_SECTION_ID"],
					'WF' => 'Y',
				));
			}

			static $buyersGroups = array();

			if(empty($buyersGroups[$userId]))
				$buyersGroups[$userId] = \CUser::getUserGroup($userId);

			$arBuyerGroups = $buyersGroups[$userId];

			// price
			$currentVatMode = \CCatalogProduct::getPriceVatIncludeMode();
			$currentUseDiscount = \CCatalogProduct::getUseDiscount();
			\CCatalogProduct::setUseDiscount(!$isSetItem);
			\CCatalogProduct::setPriceVatIncludeMode(true);
			\CCatalogProduct::setUsedCurrency(Sale\Internals\SiteCurrencyTable::getSiteCurrency($LID));
			$arPrice = \CCatalogProduct::getOptimalPrice($arElementInfo["ID"], 1, $arBuyerGroups, "N", array(), $LID);
			\CCatalogProduct::clearUsedCurrency();
			\CCatalogProduct::setPriceVatIncludeMode($currentVatMode);
			\CCatalogProduct::setUseDiscount($currentUseDiscount);
			unset($currentUseDiscount, $currentVatMode);

			$currentPrice = $arPrice['RESULT_PRICE']['DISCOUNT_PRICE'];
			$arElementInfo['PRICE'] = $currentPrice;
			$arElementInfo['CURRENCY'] = $arPrice['RESULT_PRICE']['CURRENCY'];
			$currentTotalPrice = $arPrice['RESULT_PRICE']['BASE_PRICE'];

			$arProduct = array();

			if (!empty($proxyCatalogProduct[$productId]) && is_array($proxyCatalogProduct[$productId]))
			{
				$arProduct = $proxyCatalogProduct[$productId];
			}
			else
			{
				$rsProducts = \CCatalogProduct::getList(
					array(),
					array('ID' => $productId),
					false,
					false,
					array('ID', 'QUANTITY', 'WEIGHT', 'MEASURE', 'TYPE', 'BARCODE_MULTI')
				);
				if ($arProduct = $rsProducts->Fetch())
				{
					$proxyCatalogProduct[$productId] = $arProduct;
				}
			}

			if (empty($arProduct) || !is_array($arProduct))
			{
				return array();
			}

			$balance = floatval($arProduct["QUANTITY"]);

			// sku props
			$arSkuData = array();
			$arProps[] = array(
				"NAME" => "Catalog XML_ID",
				"CODE" => "CATALOG.XML_ID",
				"VALUE" => $arElementInfo['~IBLOCK_XML_ID']
			);

			static $proxySkuProperty = array();

			if (!empty($proxySkuProperty[$productId]) && is_array($proxySkuProperty[$productId]))
			{
				$arSkuProperty = $proxySkuProperty[$productId];
			}
			else
			{
				$arSkuProperty = \CSaleProduct::GetProductSkuProps($productId, '', true);
				$proxySkuProperty[$productId] = $arSkuProperty;
			}

			if (!empty($arSkuProperty))
			{
				foreach ($arSkuProperty as &$val)
				{
					$arSkuData[] = array(
						'NAME' => $val['NAME'],
						'VALUE' => $val['VALUE'],
						'CODE' => $val['CODE']
					);
				}
				unset($val);
			}


			$arSkuData[] = array(
				"NAME" => "Product XML_ID",
				"CODE" => "PRODUCT.XML_ID",
				"VALUE" => $arElementInfo["~XML_ID"]
			);

			$arElementInfo["WEIGHT"] = $arProduct["WEIGHT"];

			// measure
			$arElementInfo["MEASURE_TEXT"] = "";
			$arElementInfo["MEASURE_CODE"] = 0;
			if ((int)$arProduct["MEASURE"] > 0)
			{

				if (!empty($proxyCatalogMeasure[$arProduct["MEASURE"]]) && is_array($proxyCatalogMeasure[$arProduct["MEASURE"]]))
				{
					$arMeasure = $proxyCatalogMeasure[$arProduct["MEASURE"]];
				}
				else
				{
					$dbMeasure = \CCatalogMeasure::GetList(array(), array("ID" => intval($arProduct["MEASURE"])), false, false, array("ID", "SYMBOL_RUS", "SYMBOL_INTL"));
					if ($arMeasure = $dbMeasure->Fetch())
					{
						$proxyCatalogMeasure[$arProduct["MEASURE"]] = $arMeasure;
					}
				}

				if (!empty($arMeasure) && is_array($arMeasure))
				{
					$arElementInfo["MEASURE_TEXT"] = ($arMeasure["SYMBOL_RUS"] != '' ? $arMeasure["SYMBOL_RUS"] : $arMeasure["SYMBOL_INTL"]);
					$arElementInfo["MEASURE_CODE"] = $arMeasure["CODE"];
				}
			}
			if ($arElementInfo["MEASURE_TEXT"] == '')
			{
				$arElementInfo["MEASURE_TEXT"] = ($defaultMeasure["SYMBOL_RUS"] != '' ? $defaultMeasure["SYMBOL_RUS"] : $defaultMeasure["SYMBOL_INTL"]);
			}


			// ratio
			$arElementInfo["RATIO"] = 1;

			if (!empty($proxyCatalogMeasureRatio[$productId]) && is_array($proxyCatalogMeasureRatio[$productId]))
			{
				$arRatio = $proxyCatalogMeasureRatio[$productId];
			}
			else
			{
				$dbratio = \CCatalogMeasureRatio::GetList(array(), array("PRODUCT_ID" => $productId));
				if ($arRatio = $dbratio->Fetch())
				{
					$proxyCatalogMeasureRatio[$productId] = $arRatio;
				}

			}

			if (!empty($arRatio) && is_array($arRatio))
				$arElementInfo["RATIO"] = $arRatio["RATIO"];

			// image
			$imgCode = '';
			$imgUrl = '';
			if ($arElementInfo["PREVIEW_PICTURE"] > 0)
				$imgCode = $arElementInfo["PREVIEW_PICTURE"];
			elseif ($arElementInfo["DETAIL_PICTURE"] > 0)
				$imgCode = $arElementInfo["DETAIL_PICTURE"];

			if ($imgCode == "" && count($arParent) > 0)
			{
				if ($arParent["PREVIEW_PICTURE"] > 0)
					$imgCode = $arParent["PREVIEW_PICTURE"];
				elseif ($arParent["DETAIL_PICTURE"] > 0)
					$imgCode = $arParent["DETAIL_PICTURE"];
			}

			if ($imgCode > 0)
			{
				$arFile = \CFile::GetFileArray($imgCode);
				$arImgProduct = \CFile::ResizeImageGet($arFile, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_PROPORTIONAL, false, false);
				if (is_array($arImgProduct))
					$imgUrl = $arImgProduct["src"];
			}

			$arSetInfo = array();
			$arStores = array();

			/** @var $productProvider IBXSaleProductProvider */
			if ($productProvider = \CSaleBasket::GetProductProvider(array("MODULE" => $arElementInfo["MODULE"], "PRODUCT_PROVIDER_CLASS" => $arElementInfo["PRODUCT_PROVIDER_CLASS"])))
			{
				// get set items if it is set
				if ($arProduct["TYPE"] == \CCatalogProduct::TYPE_SET)
				{
					if (method_exists($productProvider, "GetSetItems"))
					{
						$arSets = $productProvider::GetSetItems($productId, \CSaleBasket::TYPE_SET);

						if ($tmpId == "")
							$tmpId = randString(7);

						if (!empty($arSets))
						{
							foreach ($arSets as $arSetData)
							{
								foreach ($arSetData["ITEMS"] as $setItem)
								{
									$arSetItemParams = self::getProductDataToFillBasket($setItem["PRODUCT_ID"], $setItem["QUANTITY"], $userId, $LID, $userColumns, $tmpId); // recursive call

									// re-define some fields with set data values
									$arSetItemParams["PARENT_OFFER_ID"] = $productId;
									$arSetItemParams["OFFER_ID"] = $setItem["PRODUCT_ID"];
									$arSetItemParams["NAME"] = $setItem["NAME"];
									$arSetItemParams["MODULE"] = $setItem["MODULE"];
									$arSetItemParams["PRODUCT_PROVIDER_CLASS"] = $setItem["PRODUCT_PROVIDER_CLASS"];
									$arSetItemParams["QUANTITY"] = $setItem["QUANTITY"] * $quantity;
									$arSetItemParams["BARCODE_MULTI"] = $setItem["BARCODE_MULTI"];
									$arSetItemParams["PRODUCT_TYPE"] = $setItem["TYPE"];
									$arSetItemParams["WEIGHT"] = $setItem["WEIGHT"];
									$arSetItemParams["VAT_RATE"] = $setItem["VAT_RATE"];
									$arSetItemParams["SET_ITEMS"] = "";
									$arSetItemParams["OLD_PARENT_ID"] = $productId."_tmp".$tmpId;
									$arSetItemParams["IS_SET_ITEM"] = "Y";
									$arSetItemParams["IS_SET_PARENT"] = "N";
									$arSetItemParams["PROVIDER_DATA"] = serialize($setItem);
									$arSetInfo[] = $arSetItemParams;
								}
							}
						}
					}
				}

				// get stores
				$storeCount = $productProvider::GetStoresCount(array("SITE_ID" => $LID)); // with exact SITE_ID or SITE_ID = NULL

				if ($storeCount > 0)
				{
					if ($arProductStore = $productProvider::GetProductStores(array("PRODUCT_ID" => $productId, "SITE_ID" => $LID)))
						$arStores = $arProductStore;
				}
			}

			$currentTotalPrice = (float)$currentTotalPrice;
			// params array
			$arParams["OFFER_ID"] = $productId;
			$arParams["NAME"] = $arElementInfo["~NAME"];
			$arParams["EDIT_PAGE_URL"] = $arElementInfo["EDIT_PAGE_URL"];
			$arParams["DETAIL_PAGE_URL"] = htmlspecialcharsex($arElementInfo["~DETAIL_PAGE_URL"]);
			$arParams["PICTURE_URL"] = $imgUrl;
			$arParams["PRICE"] = floatval($arElementInfo["PRICE"]);
			$arParams["PRICE_BASE"] = $currentTotalPrice;
			$arParams["DIMENSIONS"] = serialize(array("WIDTH" => $arElementInfo["WIDTH"], "HEIGHT" => $arElementInfo["HEIGHT"], "LENGTH" => $arElementInfo["LENGTH"]));
			$arParams["QUANTITY"] = $quantity;
			$arParams["MODULE"] = $arElementInfo["MODULE"];
			$arParams["CURRENCY"] = $arElementInfo["CURRENCY"];
			$arParams["WEIGHT"] = $arElementInfo["WEIGHT"];
			$arParams["VAT_RATE"] = $arPrice["PRICE"]["VAT_RATE"];
			$arParams["PRICE_TYPE"] = $arPrice["PRICE"]["CATALOG_GROUP_NAME"];
			$arParams["AVAILABLE"] = $balance;
			$arParams["NOTES"] = (!empty($arPrice["PRICE"]["CATALOG_GROUP_NAME"]) ? $arPrice["PRICE"]["CATALOG_GROUP_NAME"] : "");
			$arParams["CATALOG_XML_ID"] = $arElementInfo["~IBLOCK_XML_ID"];
			$arParams["PRODUCT_XML_ID"] = $arElementInfo["~XML_ID"];
			$arParams["PRODUCT_PROVIDER_CLASS"] = $arElementInfo["PRODUCT_PROVIDER_CLASS"];
			$arParams["PROPS"] = $arSkuData;
			$arParams["MEASURE_TEXT"] = $arElementInfo["MEASURE_TEXT"];
			$arParams["MEASURE_CODE"] = $arElementInfo["MEASURE_CODE"];
			$arParams["MEASURE_RATIO"] = $arElementInfo["RATIO"];
			$arParams["BARCODE_MULTI"] = $arProduct["BARCODE_MULTI"];

			$arParams["PRODUCT_TYPE"] = empty($arSetInfo) ? "" : \CSaleBasket::TYPE_SET;
			$arParams["OLD_PARENT_ID"] = empty($arSetInfo) ? "" : $productId."_tmp".$tmpId;

			$arParams["SET_ITEMS"] = $arSetInfo;
			$arParams["IS_SET_ITEM"] = "N";
			$arParams["IS_SET_PARENT"] = empty($arSetInfo) ? "N" : "Y";

			$arParams["STORES"] = empty($arSetInfo) ? $arStores : array();
			$arParams["PRODUCT_PROPS_VALUES"] = $arElementInfo; // along with other information also contains values of properties with correct keys (after getProductProps)
		}

		return $arParams;
	}
}