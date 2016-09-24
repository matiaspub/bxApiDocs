<?php

namespace Bitrix\Sale\Helpers\Admin\Blocks;

use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Sale\Order;
use Bitrix\Sale\Provider;
use Bitrix\Main\Config\Option;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Sale\Helpers\Admin\OrderEdit;

Loc::loadMessages(__FILE__);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/admin_tool.php");

class OrderBasket
{
	protected static $jsInited = false;
	protected static $arSkuProps = array();
	protected static $offersCatalog = array();
	protected static $iblockPropsParams = array();
	protected static $iblockPropsParamsOrder = array();
	protected static $productsOffersSkuParams = array();

	public $settingsDialog = null;

	/** @var Order $order  */
	protected $order = null;
	protected $data = null;
	protected $dataLight = null;
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
		if (!$this->visibleColumns)
			$this->visibleColumns = $this->getVisibleColumns($idPrefix);
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
				"ALL_COLUMNS" => array_merge(
					static::getDefaultVisibleColumns(),
					getAdditionalColumns(),
					array('NUMBER' => Loc::getMessage('SALE_ORDER_BASKET_SETTINGS_COL_NUMBER'))
				),
				"VISIBLE_COLUMNS" => $this->visibleColumns
			));
		}
	}

	/**
	 * @param bool|false $defTails
	 * @return string
	 * @throws Main\ArgumentNullException
	 */

	public function getEdit($defTails = false)
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
			$result .= "<td>".htmlspecialcharsbx($name)."</td>";

		$result .= '</tr>
					</thead>';

		if($defTails)
		{
			$result .='
					<tbody style="border: 1px solid rgb(221, 221, 221);" id="'.$this->idPrefix.'sale-adm-order-basket-loading-row">
						<tr>
							<td colspan="'.(count($this->visibleColumns)+1).'" style="padding: 20px;">
								<img src="/bitrix/images/sale/admin-loader.gif"/>
							</td>
						</tr>
					</tbody>';
		}

		$result .='
			<tbody style="border: 1px solid rgb(221, 221, 221);'.($defTails ? ' display:none;' : '').'" id="'.$this->idPrefix.'sale-adm-order-edit-basket-empty-row">
				<tr>
					<td colspan="'.(count($this->visibleColumns)+1).'" style="padding: 20px;">
						'.Loc::getMessage("SALE_ORDER_BASKET_EMPTY_ROW").'.
					</td>
				</tr>
			</tbody>';

		$result .= '
				</table>
			</div>
			<div class="adm-s-gray-title" style="padding-right: 2px;">
				<div class="adm-s-gray-title-btn-container">
					<!--<span
						class="adm-btn adm-btn-green"
						onClick="BX.Sale.Admin.OrderAjaxer.sendRequest(BX.Sale.Admin.OrderEditPage.ajaxRequests.refreshOrderData({operation: \'DATA_ACTUALIZE\'}));"
					>'.Loc::getMessage('SALE_ORDER_BASKET_ACTUALIZE_DATA').'</span>&nbsp;-->';

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

		$result .= $this->getCouponsHtml(self::EDIT_MODE, false);
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
			$couponMessage = '';
			if ($this->order->getId() > 0)
				$couponMessage = '<div class="bx-adm-pc-section">'.Loc::getMessage('SALE_ORDER_BASKET_COUPONS_NOTE').'</div>';

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
						</div>'.$couponMessage.'						
					</div>
				</div>';
			unset($couponMessage);
		}
		else
		{
			$result = '';
		}

		return $result;
	}

	/**
	 * @param int $index
	 * @return string
	 */
	public function getView($index = 0)
	{
		$result = '
			<div class="adm-s-order-table-ddi">
				<table class="adm-s-order-table-ddi-table" style="width: 100%;" id="'.$this->idPrefix.'sale_order_view_product_table">
					<thead style="text-align: left;">
					<tr>';

		foreach($this->visibleColumns as $colId => $name)
		{
			$result .= "<td>".htmlspecialcharsbx($name)."</td>";
		}

		$result .= '</tr>
					</thead>
					<tbody style="border: 1px solid rgb(221, 221, 221);" id="'.$this->idPrefix.'sale-adm-order-basket-loading-row">
						<tr>
							<td colspan="'.(count($this->visibleColumns)+1).'" style="padding: 20px;">
								<img src="/bitrix/images/sale/admin-loader.gif"/>
							</td>
						</tr>
					</tbody>
					<tbody style="border: 1px solid rgb(221, 221, 221); display:none;" id="'.$this->idPrefix.'sale-adm-order-edit-basket-empty-row">
						<tr>
							<td colspan="'.(count($this->visibleColumns)+1).'" style="padding: 20px;">
								'.Loc::getMessage("SALE_ORDER_BASKET_EMPTY_ROW").'.
							</td>
						</tr>
					</tbody>
				</table>
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
	 * @param bool|false $defTails deferred or not loading of tails.
	 * @return string
	 * @throws Main\ArgumentNullException
	 */

	public function getScripts($defTails = false)
	{
		if(!static::$jsInited)
		{
			\Bitrix\Main\Page\Asset::getInstance()->addJs("/bitrix/js/sale/admin/order_basket.js");
			static::$jsInited = true;
		}

		$langPhrases = array("SALE_ORDER_BASKET_TURN", "SALE_ORDER_BASKET_EXPAND", "SALE_ORDER_BASKET_UP_RATIO",
			"SALE_ORDER_BASKET_PROD_EDIT", "SALE_ORDER_BASKET_DOWN_RATIO", "SALE_ORDER_BASKET_PROD_COUNT",
			"SALE_ORDER_BASKET_NO_PICTURE", "SALE_ORDER_BASKET_PROD_CREATE", "SALE_ORDER_BASKET_ROW_SETTINGS",
			"SALE_ORDER_BASKET_PROD_MENU_EDIT", "SALE_ORDER_BASKET_PROD_MENU_DELETE", "SALE_ORDER_BASKET_BASE_CATALOG_PRICE",
			"SALE_ORDER_BASKET_PROD_EDIT_ITEM_SAVE", "SALE_ORDER_BASKET_KG", "SALE_ORDER_BASKET_COUPON",
			"SALE_ORDER_BASKET_COUPON_STATUS", "SALE_ORDER_BASKET_COUPON_APPLY", "SALE_ORDER_BASKET_COUPON_DELETE",
			"SALE_ORDER_BASKET_POSITION_EXISTS", "SALE_ORDER_BASKET_ADD_COUPON_ERROR"
		);
		$result = '<script type="text/javascript">';

		foreach($langPhrases as $phrase)
			$result .= ' BX.message({'.$phrase.': "'.\CUtil::jsEscape(Loc::getMessage($phrase)).'"});';

		if(!$defTails)
			$data = static::prepareData();

		$totalPrices = OrderEdit::getTotalPrices($this->order, $this, false);

		if($this->mode == self::EDIT_MODE)
		{
			$result .= '
				BX.ready(function(){
					var obParams = {
						tableId: "'.$this->idPrefix.'sale_order_edit_product_table",
						idPrefix: "'.$this->idPrefix.'",
						visibleColumns: '.\CUtil::phpToJSObject($this->visibleColumns).',
						objName: "'.$this->jsObjName.'",
						createProductBasement: '.($this->createProductBasement ? 'true' : 'false').',
						columnsCount: '.count($this->visibleColumns).',
						createBasketBottom: true,
						isShowXmlId: '.($this->isShowXmlId ? 'true' : 'false').',
						discounts: '.\CUtil::phpToJSObject(OrderEdit::getOrderedDiscounts($this->order, false)).',
						mode: "edit",
						unRemovableFields: ["PRICE", "QUANTITY"],
						formatQuantity: "'.Option::get('sale', 'format_quantity', 'AUTO').'",
						weightUnit: "'.$this->weightUnit.'",
						'.$this->getTotalBlockFieldsJs($totalPrices, $data).'
					};';

			if(!$defTails)
			{
				$result .= '
					obParams.productsOrder = '.\CUtil::phpToJSObject($data["ITEMS_ORDER"]).';
					obParams.products = '.\CUtil::phpToJSObject($data["ITEMS"]).';
					obParams.iblocksSkuParams = '.\CUtil::phpToJSObject($data["IBLOCKS_SKU_PARAMS"]).';
					obParams.iblocksSkuParamsOrder = '.\CUtil::phpToJSObject($data["IBLOCKS_SKU_PARAMS_ORDER"]).';';
			}

			$result .=
					$this->jsObjName.'= new BX.Sale.Admin.OrderBasketEdit(obParams);
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
					var obParams = {
						tableId: "'.$this->idPrefix.'sale_order_view_product_table",
						idPrefix: "'.$this->idPrefix.'",
						visibleColumns: '.\CUtil::phpToJSObject($this->visibleColumns).',
						objName: "'.$this->jsObjName.'",
						createProductBasement: '.($this->createProductBasement ? 'true' : 'false').',
						columnsCount: '.count($this->visibleColumns).',
						isShowXmlId: '.($this->isShowXmlId ? 'true' : 'false').',
						discounts: '.\CUtil::phpToJSObject(OrderEdit::getOrderedDiscounts($this->order, false)).',
						createBasketBottom: true,
						mode: "view",
						formatQuantity: "'.Option::get('sale', 'format_quantity', 'AUTO').'",
						weightUnit: "'.$this->weightUnit.'",
						'.$this->getTotalBlockFieldsJs($totalPrices, array("WEIGHT" => $this->order->getBasket()->getWeight())).'
					};';

			if(!$defTails)
			{
				$result .= '
					obParams.productsOrder = '.\CUtil::phpToJSObject($data["ITEMS_ORDER"]).';
					obParams.products = '.\CUtil::phpToJSObject($data["ITEMS"]).';
					obParams.iblocksSkuParams = '.\CUtil::phpToJSObject($data["IBLOCKS_SKU_PARAMS"]).';
					obParams.iblocksSkuParamsOrder = '.\CUtil::phpToJSObject($data["IBLOCKS_SKU_PARAMS_ORDER"]).';';
			}

			$result .=
					$this->jsObjName.'= new BX.Sale.Admin.OrderBasket(obParams);
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
					value: "'.Sale\PriceMaths::roundByFormatCurrency($totalPrices["PRICE_BASKET"], $this->order->getCurrency()).'",
					type: "currency"
				},
			PRICE_BASKET_DISCOUNTED: {
				id: "'.$this->idPrefix.'sale_order_edit_basket_price_discount",
					value: "'.Sale\PriceMaths::roundByFormatCurrency($totalPrices["PRICE_BASKET_DISCOUNTED"], $this->order->getCurrency()).'",
					type: "currency"
				},
			PRICE_DELIVERY: {
				id: "'.$this->idPrefix.'sale_order_edit_basket_price_delivery",
					value: "'.Sale\PriceMaths::roundByFormatCurrency($totalPrices["PRICE_DELIVERY"], $this->order->getCurrency()).'",
					type: "currency"
				},
			PRICE_DELIVERY_DISCOUNTED: {
				id: "'.$this->idPrefix.'sale_order_edit_basket_price_delivery_discount",
					value: "'.Sale\PriceMaths::roundByFormatCurrency($totalPrices["PRICE_DELIVERY_DISCOUNTED"], $this->order->getCurrency()).'",
					type: "currency"
				},
			TAX_VALUE: {
				id: "'.$this->idPrefix.'sale_order_edit_basket_tax",
					value: "'.Sale\PriceMaths::roundByFormatCurrency($totalPrices["TAX_VALUE"], $this->order->getCurrency()).'",
					type: "currency"
				},
			WEIGHT: {
				id: "'.$this->idPrefix.'sale_order_edit_basket_weight",
					value: "'.roundEx(floatval($data["WEIGHT"]), SALE_WEIGHT_PRECISION).'",
					type: "weight"
				},
			SUM_PAID: {
				id: "'.$this->idPrefix.'sale_order_edit_summ_paid",
					value: "'.Sale\PriceMaths::roundByFormatCurrency($totalPrices["SUM_PAID"], $this->order->getCurrency()).'",
					//edit: true,
					type: "currency"
				},
			SUM_UNPAID: {
				id: "'.$this->idPrefix.'sale_order_edit_basket_price_total",
					value: "'.Sale\PriceMaths::roundByFormatCurrency($totalPrices["SUM_UNPAID"], $this->order->getCurrency()).'",
					type: "currency"
				}
		}';
	}

	/**
	 * @param int $productId
	 * @param array $products
	 * @return array
	 */
	protected function getOffersIds($productId, array $products)
	{
		$result = array();

		foreach($products as $product)
			if($product['PRODUCT_ID'] == $productId && intval($product['OFFER_ID']) > 0)
				$result[] = $product['OFFER_ID'];

		return $result;
	}

	/**
	 * @param array $productsParams
	 * @param array $visibleColumns
	 * @return array
	 * @throws Main\LoaderException
	 */
	public function getOffersSkuParams(array $productsParams, array $visibleColumns = array())
	{
		if(!empty($productsParams["ITEMS"]) && is_array($productsParams["ITEMS"]))
		{
			if (self::$catalogIncluded === null)
				self::$catalogIncluded = Main\Loader::includeModule('catalog');
			if (!self::$catalogIncluded)
				return $productsParams;

			$iblockPropsUsed = array();
			$productIds = array();
			$existOffers = array();
			$skuFilter = array('ID' => array());
			$propFilter = array('ID' => array());
			$ibs = array();

			foreach($productsParams["ITEMS"] as $params)
			{
				if($params['MODULE'] != 'catalog')
					continue;

				$productIds[] = $params['PRODUCT_ID'];

				if($this->mode == self::VIEW_MODE)
					$skuFilter['ID'] = array_merge($skuFilter['ID'], $this->getOffersIds($params["PRODUCT_ID"], $productsParams["ITEMS"]));

				if(!in_array($params["IBLOCK_ID"], $ibs))
				{
					$props = static::getSkuProps(true, $params["IBLOCK_ID"]);

					if($props)
						foreach($props as $prop)
							$propFilter['ID'][] = $prop['ID'];

					$ibs[] = $params["IBLOCK_ID"];
				}

				if($params["PRODUCT_ID"] != $params["OFFER_ID"])
					$existOffers[] = $params["OFFER_ID"];
			}

			$skuFilter['ID'] = array_unique($skuFilter['ID']);
			$productIds = array_unique($productIds);
			$propFilter['ID'] = array_unique($propFilter['ID']);

			$allOffers = \CCatalogSKU::getOffersList(
				$productIds,
				0,
				$skuFilter,
				array('NAME',  "ACTIVE", 'CATALOG_QUANTITY'),
				$propFilter
			);

			$tmpPropsOff = array();

			if(!empty($allOffers))
				$tmpPropsOff = static::getPropsFromOffers2($allOffers, $existOffers);

			unset($skuFilter, $propFilter, $existOffers, $productIds);

			foreach($productsParams["ITEMS"] as &$params)
			{
				if($params['MODULE'] != 'catalog')
					continue;

				if(!isset(self::$productsOffersSkuParams[$params["PRODUCT_ID"]]) || !isset(self::$productsOffersSkuParams[$params["PRODUCT_ID"]]))
				{
					if(isset($tmpPropsOff[$params["PRODUCT_ID"]]))
					{
						self::$productsOffersSkuParams[$params["PRODUCT_ID"]] = $tmpPropsOff[$params["PRODUCT_ID"]];

						foreach($tmpPropsOff[$params["PRODUCT_ID"]] as $offerId => $propsList)
						{
							foreach($propsList as $propId => $propValue)
							{
								if(!isset($iblockPropsUsed[$propId]))
									$iblockPropsUsed[$propId] = array();

								if(is_array($propValue))
								{
									$iblockPropsUsed[$propId] = array_merge(
										$iblockPropsUsed[$propId],
										array_diff(
											$propValue,
											$iblockPropsUsed[$propId]
										)
									);
								}
								else
								{
									if(!in_array($propValue, $iblockPropsUsed[$propId]))
										$iblockPropsUsed[$propId][] = $propValue;
								}
							}
						}
					}
					else
					{
						$res = \CIBlockElement::GetPropertyValues($params["IBLOCK_ID"], array('ID' => $params['PRODUCT_ID']));
						$tmpProps = $res->Fetch();

						if(is_array($tmpProps))
						{
							foreach($tmpProps as $id => $val)
							{
								if(!empty($val))
								{
									if(!isset($iblockPropsUsed[$id]))
										$iblockPropsUsed[$id] = array();

									if(is_array($val))
									{
										$iblockPropsUsed[$id] = array_merge(
											$iblockPropsUsed[$id],
											array_diff(
												$val,
												$iblockPropsUsed[$id]
											)
										);
									}
									else
									{
										if(!in_array($val, $iblockPropsUsed[$id]))
											$iblockPropsUsed[$id][] = $val;
									}
								}
							}
						}
					}
				}
			}

			unset($allOffers, $tmpPropsOff);
			$possibleSkuParams = array();

			foreach($productsParams["ITEMS"] as &$params)
			{
				if($params['MODULE'] != 'catalog')
					continue;

				$possibleSku = array();

				if(intval($params["OFFERS_IBLOCK_ID"]) > 0 && !isset(self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]]))
				{
					self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]] = static::getPropsParams(
						$params["OFFERS_IBLOCK_ID"],
						array(),
						$iblockPropsUsed
					);
				}

				if(intval($params["IBLOCK_ID"]) > 0 && !isset(self::$iblockPropsParams[$params["IBLOCK_ID"]]))
				{
					self::$iblockPropsParams[$params["IBLOCK_ID"]] = static::getPropsParams(
						$params["IBLOCK_ID"],
						$visibleColumns,
						$iblockPropsUsed
					);
				}

				if(self::$productsOffersSkuParams[$params["PRODUCT_ID"]][$params["OFFER_ID"]])
					$params["SKU_PROPS"] = self::$productsOffersSkuParams[$params["PRODUCT_ID"]][$params["OFFER_ID"]];

				if (is_array($params["SKU_PROPS"]))
				{
					foreach ($params["SKU_PROPS"] as $id => $skuProps)
					{
						if(is_array($skuProps))
						{
							unset($params["SKU_PROPS"][$id]);
							continue;
						}

						$params["SKU_PROPS"][$id] = array(
							'ID' => $skuProps,
							'NAME' => self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]][$id]['NAME'],
							'VALUE' => self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]][$id]['VALUES'][$skuProps],
							'CODE' => self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]][$id]['CODE'],
							'SORT' => self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]][$id]['SORT']
						);

						$possibleSku[$id] = self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]][$id]['VALUES'][$skuProps]['ID'];
					}
				}

				if($this->mode == self::EDIT_MODE && $params['PRODUCT_ID'] != $params['OFFER_ID'])
				{
					if(is_array(self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]]))
						$skuOrder = array_keys(self::$iblockPropsParams[$params["OFFERS_IBLOCK_ID"]]);
					else
						$skuOrder = array_keys($possibleSku);

					$possibleSkuParams[$params['OFFER_ID']] = array(
						'PRODUCT_ID' => $params['PRODUCT_ID'],
						'OFFER_ID' => $params['OFFER_ID'],
						'SKU_PROPS' => $possibleSku,
						'SKU_ORDER' => $skuOrder
					);
				}
			}

			unset($params, $iblockPropsUsed);

			if($this->mode == self::EDIT_MODE && !empty($possibleSkuParams))
			{
				$possibleSkuProps = Sale\Helpers\Admin\SkuProps::getPossibleSkuPropsValues($possibleSkuParams);

				if(!empty($possibleSkuProps))
				{
					foreach($productsParams["ITEMS"] as $key => $params)
					{
						if($params['MODULE'] != 'catalog')
							continue;

						$productsParams["ITEMS"][$key]["SKU_PROPS_POSSIBLE_VALUES"] = $possibleSkuProps[$params['OFFER_ID']];
					}

					unset($possibleSkuParams);
				}
			}

			$productsParams["IBLOCKS_SKU_PARAMS"] = self::$iblockPropsParams;

			foreach(self::$iblockPropsParams as $iBlockId => $props)
				self::$iblockPropsParamsOrder[$iBlockId] = array_keys($props);
		}

		$productsParams["IBLOCKS_SKU_PARAMS_ORDER"] = self::$iblockPropsParamsOrder;
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
	 * @deprecated use OrderBasket::getProductsData instead.
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
					$result['DISCOUNT_PRICE'] = Sale\PriceMaths::roundByFormatCurrency($result['DISCOUNT_PRICE'], $result['CURRENCY']);
				}

				if (isset($result['PRICE']))
				{
					$result['PRICE'] = Sale\PriceMaths::roundByFormatCurrency($result['PRICE'], $result['CURRENCY']);
				}

				if (isset($result['PRICE_BASE']))
				{
					$result['PRICE_BASE'] = Sale\PriceMaths::roundByFormatCurrency($result['PRICE_BASE'], $result['CURRENCY']);
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

	public static function getProductsData(array $productsIds, $siteId, array $fields = array())
	{
		if(empty($productsIds))
			return array();

		global $USER;
		$result = array();

		foreach($productsIds as  $id)
		{
			$details = OrderEdit::getProductDetails($id, $USER->GetID(), $siteId);

			if($details !== false)
				$result[$id] = $details;
		}

		$noCachedProductIds = array_diff($productsIds, array_keys($result));

		if(!empty($noCachedProductIds))
		{
			$noCachedData = \Bitrix\Sale\Helpers\Admin\Product::getData($noCachedProductIds, $siteId, array_keys($fields));

			foreach($noCachedData as $productId => $productData)
			{
				$result[$productId] = $productData;
				OrderEdit::setProductDetails($productId, $USER->GetID(), $siteId, $result[$productId]);
			}

			$emptyData = array_diff($productsIds, array_keys($result));

			foreach($emptyData as $productId)
				$result[$productId] = array();
		}

		return $result;
	}

	protected static function getPropsParams($iblockId, array $visibleColumns = array(), array $iblockPropsUsed = array())
	{
		$arRes = array();
		$bUseHLIblock = \Bitrix\Main\Loader::includeModule('highloadblock');
		$filter = array(
			'=IBLOCK_ID' => intval($iblockId),
			'=ACTIVE' => 'Y'
		);

		if(!empty($iblockPropsUsed))
		{
			$filter['ID'] = array_keys($iblockPropsUsed);
		}

		if(!empty($visibleColumns))
		{
			$iBlockProps = array();
			$iBlockPropsInt = array();

			foreach ($visibleColumns as $id => $name)
			{
				if (substr($id, 0, 9) == "PROPERTY_")
				{
					$iblockPropCode = substr($id, 9);

					if(strlen($iblockPropCode) > 0)
					{
						$iBlockProps[] = $iblockPropCode;

						if(intval($iblockPropCode) > 0)
							$iBlockPropsInt[] = intval($iblockPropCode);
					}
				}
			}

			if(!empty($iBlockProps) || !empty($iBlockPropsInt))
			{
				$codes = array('LOGIC' => 'OR');

				if(!empty($iBlockProps))
					$codes['=CODE'] = $iBlockProps;

				if(!empty($iBlockPropsInt))
					$codes['=ID'] = $iBlockPropsInt;

				$filter[] = $codes;
			}
		}

		$rsProps = \Bitrix\Iblock\PropertyTable::getList(array(
				'filter' => $filter,
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
		));

		while ($arProp = $rsProps->fetch())
		{
			if(!empty($iblockPropsUsed) && !array_key_exists($arProp['ID'], $iblockPropsUsed))
				continue;

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
					$eFilter = array('IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'], 'ACTIVE' => 'Y');

					if(!empty($iblockPropsUsed[$arProp['ID']]))
						$eFilter['ID'] = $iblockPropsUsed[$arProp['ID']];

					$rsPropEnums = \CIBlockElement::getList(
						array('SORT' => 'ASC'),
						$eFilter,
						false,
						false,
						array('ID', 'NAME', 'PREVIEW_PICTURE', 'SORT')
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
							$arEnum['PREVIEW_PICTURE'] = $productImg['src'];
						}

						$arValues[$arEnum['ID']] = array(
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
						if(!is_array($arProp["USER_TYPE_SETTINGS"]))
							$arProp["USER_TYPE_SETTINGS"] = unserialize($arProp["USER_TYPE_SETTINGS"]);

						$hlblock = HL\HighloadBlockTable::getList(array("filter" => array("TABLE_NAME" => $arProp["USER_TYPE_SETTINGS"]["TABLE_NAME"])))->fetch();
						if ($hlblock)
						{
							$entity = HL\HighloadBlockTable::compileEntity($hlblock);
							$entity_data_class = $entity->getDataClass();
							$rsData = $entity_data_class::getList();

							while ($arData = $rsData->fetch())
							{
								$arValues[$arData['UF_XML_ID']] = array(
									'ID' => $arData['UF_XML_ID'],
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


				if (is_array($iblockPropsUsed[$arProp['ID']]) && !empty($arValues) && is_array($arValues))
				{
					//if property value deleted or inactive
					$notFound = array_diff($iblockPropsUsed[$arProp['ID']], array_keys($arValues));

					//if Empty values exist
					if((is_array($iblockPropsUsed[$arProp['ID']]) && in_array("-", $iblockPropsUsed[$arProp['ID']])) || (is_array($notFound) && !empty($notFound)))
					{
						$arValues["-"] = array(
							"ID" => "-",
							"NAME" => "-"
						);
					}

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

	protected static function getPropsFromOffers2(array $items, array $existOffers)
	{
		$props = array();
		$notEmptyProps = array();
		$allProps = array();

		foreach($items as $id => $item)
		{
			if(!isset($props[$id]))
				$props[$id] = array();

			foreach($item as $offerId => $offer)
			{
				if(!isset($offer["PROPERTIES"]))
					continue;

				if(!in_array($offerId, $existOffers) && $offer["ACTIVE"] != "Y")
					continue;

				if(!isset($props[$id][$offerId]))
					$props[$id][$offerId] = array();

				foreach($offer["PROPERTIES"] as $propCode => $propParams)
				{
					if($propParams["MULTIPLE"] == "Y")
						continue;

					if($propParams["ACTIVE"] != "Y") //!$propParams["~VALUE"] ||
						continue;

					if($propParams['PROPERTY_TYPE'] == 'L'
						|| $propParams['PROPERTY_TYPE'] == 'E'
						|| (
							$propParams['PROPERTY_TYPE'] == 'S'
							&& $propParams['USER_TYPE'] == 'directory'
							)
						)
					{
						if(!in_array($propParams["ID"], $allProps))
							$allProps[] = $propParams["ID"];

						if(strlen($propParams["~VALUE"]) > 0)
						{
							$props[$id][$offerId][$propParams["ID"]] = $propParams["~VALUE"];

							if(!in_array($propParams["ID"], $notEmptyProps))
								$notEmptyProps[] = $propParams["ID"];
						}
						else
						{
							$props[$id][$offerId][$propParams["ID"]] = "-";
						}
					}
				}
			}
		}

		$emptyProps = array_diff($allProps, $notEmptyProps);

		if(!empty($emptyProps))
		{
			foreach($emptyProps as $propId)
			{
				foreach($props as $prodId => $offers)
				{
					foreach($offers as $offerId => $offerProps)
					{
						unset($props[$prodId][$offerId][$propId]);
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

	protected function getVisibleColumns($idPrefix)
	{
		if (self::$iblockIncluded === null)
			self::$iblockIncluded = Main\Loader::includeModule('iblock');
		if (!self::$iblockIncluded)
			return array();

		$result = array();
		$arTmpColumns = array();
		$arColumnsOptions = static::loadVisibleColumns($idPrefix);

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
							array(
								'id' => 'FORM_PROD_BASKET_MEASURE_CODE',
								'onchange' => $objName.'.productEditDialog.setMeasureText();'
							)
						).'
						<input name="FORM_PROD_BASKET_MEASURE_TEXT" id="FORM_PROD_BASKET_MEASURE_TEXT" type="hidden" value="">
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
	 * @deprecated use \Bitrix\Sale\Helpers\Admin\Product::getData() instead it.
	 */
	public static function getProductDataToFillBasket($productId, $quantity, $userId, $LID, $userColumns, $tmpId = "")
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
			$column = strtoupper($column);
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
					$dbres = \CIBlockProperty::getList(array(), array("CODE" => $propertyCode));
					if ($arPropData = $dbres->getNext())
					{
						$arPropertyInfo[$column] = $arPropData;
						$proxyIblockProperty[$propertyCode] = $arPropData;
					}
				}

			}
		}

		$arSelect = array_merge(
				array("ID", "NAME", "IBLOCK_ID", "IBLOCK_SECTION_ID", "DETAIL_PICTURE", "PREVIEW_PICTURE", "XML_ID", "IBLOCK_EXTERNAL_ID"),
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
						if (!isset($arPropertyInfo[$columnCode]))
							continue;
						$keyResult = 'PROPERTY_'.$arPropertyInfo[$columnCode]['CODE'].'_VALUE';
						$arElement[$key] = getIblockPropInfo($value, $arPropertyInfo[$columnCode], array("WIDTH" => 90, "HEIGHT" => 90));
						if ($keyResult != $key)
							$arElement[$keyResult] = $arElement[$key];
						unset($keyResult);
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
			$priceType = GetCatalogGroup($arPrice["PRICE"]["CATALOG_GROUP_ID"]);
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
						array('ID', 'QUANTITY', 'WEIGHT', 'MEASURE', 'TYPE', 'BARCODE_MULTI', 'WIDTH', 'LENGTH', 'HEIGHT')
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
							'NAME' => htmlspecialcharsback($val['NAME']),
							'VALUE' => htmlspecialcharsback($val['VALUE']),
							'CODE' => htmlspecialcharsback($val['CODE'])
					);
				}
				unset($val);
			}

			if(strlen($arElementInfo["~IBLOCK_EXTERNAL_ID"]) > 0)
			{
				$arSkuData[] = array(
						"NAME" => "Catalog XML_ID",
						"CODE" => "CATALOG.XML_ID",
						"VALUE" => $arElementInfo['~IBLOCK_EXTERNAL_ID']
				);
			}

			if(strlen($arElementInfo["~XML_ID"]) > 0)
			{
				$arSkuData[] = array(
						"NAME" => "Product XML_ID",
						"CODE" => "PRODUCT.XML_ID",
						"VALUE" => $arElementInfo["~XML_ID"]
				);
			}

			$arElementInfo["WEIGHT"] = $arProduct["WEIGHT"];
			$arElementInfo["WIDTH"] = $arProduct["WIDTH"];
			$arElementInfo["LENGTH"] = $arProduct["LENGTH"];
			$arElementInfo["HEIGHT"] = $arProduct["HEIGHT"];

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
			$arParams["NAME"] = $arElementInfo["~NAME"]; //'
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
			$arParams["PRICE_TYPE"] = $priceType["NAME_LANG"];
			$arParams["AVAILABLE"] = $balance;
			$arParams["NOTES"] = (!empty($priceType["NAME_LANG"]) ? $priceType["NAME_LANG"] : "");
			$arParams["CATALOG_XML_ID"] = $arElementInfo["~IBLOCK_EXTERNAL_ID"];
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


	/**
	 * @param array $inParams
	 * @return array
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
			$discounts = !empty($inParams["DISCOUNTS"]) ? $inParams["DISCOUNTS"] : OrderEdit::getDiscountsApplyResult($this->order, true);

			if(!$basket)
			{
				$result["WEIGHT_FOR_HUMAN"] = roundEx(floatval(0), SALE_WEIGHT_PRECISION);
				$this->data = $result;
				return $result;
			}

			$result["WEIGHT"] = $basket->getWeight();
			$result["WEIGHT_FOR_HUMAN"] = roundEx(floatval($result["WEIGHT"]/$this->weightKoef), SALE_WEIGHT_PRECISION);
			$items = $basket->getBasketItems();

			if (self::$catalogIncluded === null)
				self::$catalogIncluded = Main\Loader::includeModule('catalog');

			if(!empty($inParams["ADDED_PRODUCTS"]))
				$result["ADDED_PRODUCTS"] = array();

			$catalogProductIds = array();

			/** @var \Bitrix\Sale\BasketItem $item */
			foreach($items as $item)
				if($item->getField('MODULE') == 'catalog')
					$catalogProductIds[] = $item->getProductId();

			$catalogPreparedData = static::getProductsData($catalogProductIds, $this->order->getSiteId(), $this->visibleColumns);
			$providerData = Provider::getProductData($basket, array("PRICE"));

			/** @var \Bitrix\Sale\BasketItem $item */
			foreach($items as $item)
			{
				$params = array();
				$productId = $item->getProductId();

				$params["BASKET_CODE"] = $basketCode = $item->getBasketCode();
				$params["QUANTITY"] = Sale\BasketItem::formatQuantity($item->getField('QUANTITY'));
				$params["NOTES"] = $item->getField("NOTES");

				if(!isset($params["OFFER_ID"]))
					$params["OFFER_ID"] = $productId;

				if(isset($discounts["RESULT"]["BASKET"][$basketCode]) && is_array($discounts["RESULT"]["BASKET"][$basketCode]))
					foreach($discounts["RESULT"]["BASKET"][$basketCode] as $discount)
						$params["DISCOUNTS"][$discount["DISCOUNT_ID"]] = $discount;

				if(isset($discounts["PRICES"]["BASKET"][$basketCode]))
				{
					$params["PRICE_BASE"] = Sale\PriceMaths::roundByFormatCurrency($discounts["PRICES"]["BASKET"][$basketCode]["BASE_PRICE"], $this->order->getCurrency());
					$params["PRICE"] = Sale\PriceMaths::roundByFormatCurrency($discounts["PRICES"]["BASKET"][$basketCode]["PRICE"], $this->order->getCurrency());
				}

				$params["CUSTOM_PRICE"] = $item->isCustomPrice() ? "Y" : "N";

				if(
					!is_array($inParams["ADDED_PRODUCTS"])
					||
					(
						!empty($inParams["ADDED_PRODUCTS"])
						&& is_array($inParams["ADDED_PRODUCTS"])
						&& in_array($productId, $inParams["ADDED_PRODUCTS"])
					)
				)
				{
					$params = $params + self::getPropsForBasketItem($item, $catalogPreparedData);

					if(is_array($inParams["ADDED_PRODUCTS"]) && in_array($productId, $inParams["ADDED_PRODUCTS"]))
					{
						$result["ADDED_PRODUCTS"][] = $basketCode;
					}

					//Let's cache provider product data into form field
					if(!Provider::isExistsTrustData($this->order->getSiteId(), 'sale', $item->getProductId()))
					{
						if(isset($providerData[$basketCode]))
							$data = $providerData[$basketCode];
						else
							$data = Provider::getProductData($basket, array("PRICE"), $item);

						if(is_array($data[$basketCode]) && !empty($data[$basketCode]))
						{
							\Bitrix\Sale\Helpers\Admin\OrderEdit::setProviderTrustData($item, $this->order, $data[$basketCode]);
							$params["PROVIDER_DATA"] = serialize($data[$basketCode]);
						}
					}
					else
					{
						$providerData = Provider::getTrustData($this->order->getSiteId(), 'sale', $item->getProductId());

						if(is_array($providerData) && !empty($providerData))
							$params["PROVIDER_DATA"] = serialize($providerData);
					}
				}

				if(is_array($params["SET_ITEMS"]) && !empty($params["SET_ITEMS"]))
				{
					$offerToIdx = array();

					foreach($params["SET_ITEMS"] as $idx => $childFields)
						$offerToIdx[$childFields["OFFER_ID"]] = $idx;

					if($children = $item->getBundleCollection())
					{
						foreach($children->getBasketItems() as $child)
						{
							$fields = $child->getFieldValues();

							if(isset($offerToIdx[$fields['PRODUCT_ID']]))
							{
								$params["SET_ITEMS"][$offerToIdx[$fields['PRODUCT_ID']]] = $fields + $params["SET_ITEMS"][$offerToIdx[$fields['PRODUCT_ID']]];
							}
							else
							{
								$fields["OFFER_ID"] = $fields["PRODUCT_ID"];
								//unset($fields["PRODUCT_ID"]);
								$params["SET_ITEMS"][] = $fields;
							}
						}
					}

					$params["SET_ITEMS_DATA"] = serialize($params["SET_ITEMS"]);
				}
				elseif($item->isBundleParent())
				{
					$params["SET_ITEMS"] = array();

					if($children = $item->getBundleCollection())
					{
						/** @var \Bitrix\Sale\BasketItem $child */
						foreach($children->getBasketItems() as $child)
						{
							$childFields = $child->getFieldValues();
							$childFields["OFFER_ID"] = $child->getProductId();
							$childFields["IS_SET_ITEM"] = "Y";
							$childFields["IS_SET_PARENT"] = "N";
							$params["SET_ITEMS"][] = $childFields;
						}
					}

					if(!empty($params["SET_ITEMS"]))
					{
						$params["IS_SET_PARENT"] = "Y";
					}
				}

				$result["ITEMS"][$basketCode] = $params;
			}

			$result = array_merge(
				$result, $this->getPrices($discounts)
			);

			$result["ITEMS_ORDER"] = array_keys($result["ITEMS"]);

			if(!$inParams["SKIP_SKU_INFO"])
			{
				$result = $this->getOffersSkuParams($result, $this->visibleColumns);
			}

			$this->data = $result;
		}

		return $this->data;
	}

	public function getPrices($discounts = null)
	{
		static $result = null;

		if($result === null)
		{
			$basketPrice = 0;
			$basketDiscount = 0;
			$basketPriceBase = 0;
			$basket = $this->order->getBasket();

			if($basket)
			{
				$items = $basket->getBasketItems();

				if (!$discounts)
					$discounts = OrderEdit::getDiscountsApplyResult($this->order, true);

				/** @var \Bitrix\Sale\BasketItem $item */
				foreach($items as $item)
				{
					$basketCode = $item->getBasketCode();

					if(isset($discounts["PRICES"]["BASKET"][$basketCode]))
					{
						$priceBase = Sale\PriceMaths::roundByFormatCurrency($discounts["PRICES"]["BASKET"][$basketCode]["BASE_PRICE"], $this->order->getCurrency());
						$price = Sale\PriceMaths::roundByFormatCurrency($discounts["PRICES"]["BASKET"][$basketCode]["PRICE"], $this->order->getCurrency());
						$basketPriceBase += $priceBase * $item->getQuantity();
						$basketPrice += $price * $item->getQuantity();

						if (!$item->isCustomPrice())
							$basketDiscount += $discounts["PRICES"]["BASKET"][$basketCode]["DISCOUNT"] * $item->getQuantity();
					}
				}
			}

			$result = array(
				"BASKET_PRICE_BASE" => Sale\PriceMaths::roundByFormatCurrency($basketPriceBase, $this->order->getCurrency()),
				"BASKET_PRICE" => Sale\PriceMaths::roundByFormatCurrency($basketPrice, $this->order->getCurrency()),
				"DISCOUNT_VALUE" => Sale\PriceMaths::roundByFormatCurrency($basketDiscount, $this->order->getCurrency())
			);
		}

		return $result;
	}

	protected function getPropsForBasketItem($item, array $preparedData = array())
	{
		$params = array();

		/** @var \Bitrix\Sale\BasketItem $item */
		if (self::$catalogIncluded === null)
			self::$catalogIncluded = Main\Loader::includeModule('catalog');

		$productId = $item->getProductId();

		if($item->getField("MODULE") == "catalog")
		{
			if(!empty($preparedData[$item->getProductId()]))
			{
				$params = $preparedData[$item->getProductId()];
			}
			else
			{
				$res = static::getProductsData(array($productId),$this->order->getSiteId(), $this->visibleColumns);
				$params = $res[$productId];
			}
		}

		if(intval($item->getField("MEASURE_CODE")) > 0)
			$params["MEASURE_CODE"] = intval($item->getField("MEASURE_CODE"));
		elseif(!isset($params["MEASURE_CODE"]))
			$params["MEASURE_CODE"] = 0;

		if(strlen($item->getField("MEASURE_NAME")) > 0)
			$params["MEASURE_TEXT"] = $item->getField("MEASURE_NAME");
		elseif(!isset($params["MEASURE_TEXT"]))
			$params["MEASURE_TEXT"] = "";

		if(!isset($params["OFFER_ID"]))
			$params["OFFER_ID"] = $productId;

		$params["PRODUCT_PROVIDER_CLASS"] = $item->getProvider();
		$id = $params["PRODUCT_ID"];
		$params = array_merge($params, $item->getFieldValues(), array("PRODUCT_ID" => $id));
		$params["PROPS"] = array();

		/** @var \Bitrix\Sale\BasketPropertyItem $property */
		foreach($item->getPropertyCollection() as  $property)
		{
			$params["PROPS"][] = array(
				"VALUE" => $property->getField("VALUE"),
				"NAME" => $property->getField("NAME"),
				"CODE" => $property->getField("CODE"),
				"SORT" => $property->getField("SORT"),
				"ID" => $property->getField("ID")
			);
		}

		//Let's cache provider product data into form field
		if(Provider::isExistsTrustData($this->order->getSiteId(), 'sale', $item->getProductId()))
		{
			$providerData = Provider::getTrustData($this->order->getSiteId(), 'sale', $item->getProductId());

			if(is_array($providerData) && !empty($providerData))
				$params["PROVIDER_DATA"] = serialize($providerData);
		}

		if(is_array($params["SET_ITEMS"]) && !empty($params["SET_ITEMS"]))
		{
			$offerToIdx = array();

			foreach($params["SET_ITEMS"] as $idx => $childFields)
				$offerToIdx[$childFields["OFFER_ID"]] = $idx;

			if($children = $item->getBundleCollection())
			{
				foreach($children->getBasketItems() as $child)
				{
					$fields = $child->getFieldValues();

					if(isset($offerToIdx[$fields['PRODUCT_ID']]))
					{
						$params["SET_ITEMS"][$offerToIdx[$fields['PRODUCT_ID']]] = $fields + $params["SET_ITEMS"][$offerToIdx[$fields['PRODUCT_ID']]];
					}
					else
					{
						$fields["OFFER_ID"] = $fields["PRODUCT_ID"];
						$fields["IS_SET_ITEM"] = "Y";
						$fields["IS_SET_PARENT"] = "N";
						$fields["OLD_PARENT_ID"] = $params["OLD_PARENT_ID"];
						//unset($fields["PRODUCT_ID"]);
						$params["SET_ITEMS"][] = $fields;
					}
				}
			}

			$params["SET_ITEMS_DATA"] = serialize($params["SET_ITEMS"]);
		}

		return $params;
	}
}