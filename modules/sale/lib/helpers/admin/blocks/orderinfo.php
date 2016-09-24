<?php

namespace Bitrix\Sale\Helpers\Admin\Blocks;

use Bitrix\Main\Event;
use Bitrix\Sale\Order;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\Helpers\Admin\OrderEdit;

Loc::loadMessages(__FILE__);

class OrderInfo
{
	/**
	 * @return string
	 */
	public static function getScripts()
	{
		\Bitrix\Main\Page\Asset::getInstance()->addJs("/bitrix/js/sale/admin/order_info.js");

		return '
			<script type="text/javascript">
				BX.ready(function(){
					BX.Sale.Admin.OrderEditPage.registerFieldsUpdaters( BX.Sale.Admin.OrderInfo.getFieldsUpdaters() );
				});
			</script>
		';
	}

	protected static function getPaymentsInfo(Order $order)
	{
		$payments = $order->getPaymentCollection();

		if(!$payments)
			return array();

		$result = array();

		/** @var $payment \Bitrix\Sale\Payment  */
		foreach ($payments as $payment)
		{
			$id = $payment->getField("PAY_SYSTEM_ID");
			$ptId = $order->getPersonTypeId();

			if(!$id || !$ptId)
				continue;

			$params = OrderPayment::getPaySystemParams($id);

			if(!$params)
				continue;

			$result[] = array(
				"ID" => $payment->getId(),
				"NAME" => $params["NAME"],
				"LOGOTIP_PATH" => $params["LOGOTIP_PATH"],
				"IS_PAYED" => $payment->isPaid()
			);
		}
		return $result;
	}

	protected static function getShipmentsInfo(Order $order)
	{
		$result = array();
		$shipments = $order->getShipmentCollection();

		/** @var \Bitrix\Sale\Shipment $shipment */
		foreach($shipments as $shipment)
		{
			if($shipment->isSystem())
				continue;

			/** @var \Bitrix\Sale\Delivery\Services\Base  $deliveryService */
			$deliveryService = $shipment->getDelivery();

			if(!$deliveryService)
				continue;

			$result[] = array(
				"ID" => $shipment->getId(),
				"NAME" => $deliveryService->getName(),
				"LOGOTIP_PATH" => $deliveryService->getLogotipPath(),
				"IS_SHIPPED" => $shipment->isShipped(),
				"DEDUCTED" => $shipment->getField('DEDUCTED')
			);
		}

		return $result;
	}

	/**
	 * @param Order $order
	 * @return string HTML
	 */
	public static function getView(Order $order, $orderBasket)
	{
		$currency = $order->getCurrency();
		$orderProps = $order->getPropertyCollection();

		if($email = $orderProps->getUserEmail())
			$email = $email->getViewHtml();

		if($phone = $orderProps->getPhone())
			$phone = $phone->getViewHtml();

		if($name = $orderProps->getPayerName())
			$name = $name->getViewHtml();

		$totalPrices = OrderEdit::getTotalPrices($order,  $orderBasket, false);

		//Here we can receive custom data
		$event = new Event('sale', 'onSaleAdminOrderInfoBlockShow', array('ORDER' => $order, 'ORDER_BASKET' => $orderBasket));
		$event->send();
		$resultList = $event->getResults();
		$customData = array();

		if (is_array($resultList) && !empty($resultList))
		{
			foreach ($resultList as $eventResult)
			{
				/** @var  EventResult $eventResult*/
				if ($eventResult->getType() != EventResult::SUCCESS)
					continue;

				$params = $eventResult->getParameters();

				if(!empty($params) && is_array($params))
					$customData = $params;
			}
		}
		///

		$result = '
			<div class="adm-bus-orderinfoblock adm-detail-tabs-block-pin" id="sale-order-edit-block-order-info">
				<div class="adm-bus-orderinfoblock-container">
				<div class="adm-bus-orderinfoblock-title">'.
				Loc::getMessage("SALE_ORDER_INFO", array(
					"#ID#" => $order->getId(),
					"#NUM#" => strlen($order->getField("ACCOUNT_NUMBER")) > 0 ? $order->getField("ACCOUNT_NUMBER") : $order->getId(),
					"#DATE#" => $order->getDateInsert()->toString())
				)." [".$order->getSiteId()."]".
				'<div class="adm-bus-orderinfoblock-status success" id="order_info_order_status_name">'.$order->getField('STATUS_ID').'</div> <!-- TODO -->
				</div>
				<div class="adm-bus-orderinfoblock-content">
					<div class="adm-bus-orderinfoblock-content-block-customer">
						<ul class="adm-bus-orderinfoblock-content-customer-info">
							<li>
								<span class="adm-bus-orderinfoblock-content-customer-info-param">'.Loc::getMessage("SALE_ORDER_INFO_FIO").':</span>
								<span class="adm-bus-orderinfoblock-content-customer-info-value" id="order_info_buyer_name">'.$name.'</span>
							</li>
							<li>
								<span class="adm-bus-orderinfoblock-content-customer-info-param">E-Mail:</span>
								<span class="adm-bus-orderinfoblock-content-customer-info-value"  id="order_info_buyer_email">'.$email.'</span>
							</li>
							<li>
								<span class="adm-bus-orderinfoblock-content-customer-info-param">'.Loc::getMessage("SALE_ORDER_INFO_PHONE").':</span>
								<span class="adm-bus-orderinfoblock-content-customer-info-value" id="order_info_buyer_phone">
									<a href="javascript:void(0)" onclick="BX.Sale.Admin.OrderEditPage.desktopMakeCall(\''.$phone.'\');">'.
										htmlspecialcharsbx($phone).
									'</a>
								</span>
							</li>';

		if(!empty($customData))
		{
			foreach($customData as $custom)
			{
				if(empty($custom['TITLE']))
					throw new ArgumentNullException("customData['TITLE']");

				if(empty($custom['VALUE']))
					throw new ArgumentNullException("customData['VALUE']");

				$result .='
					<li>
						<span class="adm-bus-orderinfoblock-content-customer-info-param">'.$custom['TITLE'].'</span>
						<span class="adm-bus-orderinfoblock-content-customer-info-value"'.(!empty($custom['ID']) ? ' id="'.$custom['ID'].'"' : '' ).'>'.$custom['VALUE'].'</span>
					</li>';
			}
		}

		$result .= '
						</ul>
					</div>
					<div class="adm-bus-orderinfoblock-content-block-order">
						<ul class="adm-bus-orderinfoblock-content-order-info">
							<li>
								<span class="adm-bus-orderinfoblock-content-order-info-param">'.Loc::getMessage("SALE_ORDER_INFO_PRICE").'</span>
								<span class="adm-bus-orderinfoblock-content-order-info-value" id="order_info_price_basket">'.
									SaleFormatCurrency(floatval($totalPrices["PRICE_BASKET"]), $currency).
								'</span>
							</li>
							<li class="adm-bus-orderinfoblock-content-redtext">
								<span class="adm-bus-orderinfoblock-content-order-info-param">'.Loc::getMessage("SALE_ORDER_INFO_DISCOUNT_PRICE").'</span>
								<span class="adm-bus-orderinfoblock-content-order-info-value" id="order_info_price_basket_discount">'.
									SaleFormatCurrency(floatval($totalPrices["PRICE_BASKET_DISCOUNTED"]), $currency).
								'</span>
							</li>
							<li>
								<span class="adm-bus-orderinfoblock-content-order-info-param">'.Loc::getMessage("SALE_ORDER_INFO_DELIVERY_PRICE").'</span>
								<span class="adm-bus-orderinfoblock-content-order-info-value" id="order_info_delivery_price">'.
									SaleFormatCurrency(floatval($order->getDeliveryPrice()), $currency).
								'</span>
							</li>
						</ul>
						<ul class="adm-bus-orderinfoblock-content-order-info-result">
							<li>
								<span class="adm-bus-orderinfoblock-content-order-info-param">'.Loc::getMessage("SALE_ORDER_INFO_ALL_PRICE").'</span>
								<span class="adm-bus-orderinfoblock-content-order-info-value" id="order_info_buyer_price">'.
									SaleFormatCurrency(floatval($order->getPrice()), $currency).
								'</span>
							</li>
						</ul>
					</div>
					<div class="adm-bus-orderinfoblock-content-block-last">
						'.Loc::getMessage("SALE_ORDER_INFO_PAYED").': '.SaleFormatCurrency(floatval($order->getSumPaid()), $currency).
						'<ul class="adm-bus-orderinfoblock-content-last">';

		$updatersContent = "";

		foreach(self::getPaymentsInfo($order) as $payment)
		{
			$result .= '<a href="#payment_'.$payment["ID"].'" style="text-decoration: none;">
				<li id="sale-admin-order-icon-payment-'.$payment["ID"].'" class="adm-bus-orderinfoblock-content-last-icon '.
				($payment["IS_PAYED"] ? 'green' : 'red').
				'" ';

			if (strval($payment["LOGOTIP_PATH"]) != '')
			{
				$result .= 'style="background-image: url('.$payment["LOGOTIP_PATH"].');" ';
			}

			$result .= 'title="'.htmlspecialcharsbx($payment["NAME"]).'"'.
				'><span></span></li></a>';

			if(strlen($updatersContent) > 0)
				$updatersContent .=",\n";

			$updatersContent .= "\tPAYMENT_PAID_".$payment["ID"].": function(paid) { BX.Sale.Admin.OrderInfo.setIconLamp('payment', '".$payment["ID"]."', (paid == 'Y' ? 'green' : 'red')); }";
		}
		$result .=		'</ul>
						'.Loc::getMessage("SALE_ORDER_INFO_SHIPMENT").'
						<ul class="adm-bus-orderinfoblock-content-last">';
		foreach(self::getShipmentsInfo($order) as $shipment)
		{
			$result .= '<a href="#shipment_'.$shipment["ID"].'" style="text-decoration: none;">
				<li id="sale-admin-order-icon-shipment-'.$shipment["ID"].'" class="adm-bus-orderinfoblock-content-last-icon '.
				($shipment["DEDUCTED"] == "Y" ? 'green' : 'red').
				'" ';

			if (strval($shipment["LOGOTIP_PATH"]) != '')
			{
				$result .= 'style="background-image: url('.$shipment["LOGOTIP_PATH"].');" ';
			}
			$result .= 'title="'.htmlspecialcharsbx($shipment["NAME"]).'"'.
				'><span></span></li></a>';

			if(strlen($updatersContent) > 0)
				$updatersContent .=",\n";

			$updatersContent .= "\tSHIPMENT_STATUS_".$shipment["ID"].": function(shipmentStatus) { BX.Sale.Admin.OrderInfo.setIconLamp('shipment', '".$shipment["ID"]."', (shipmentStatus == 'DF' ? 'green' : 'red')); }";
		}

		$result .=      '</ul>
					</div>
				</div>
				<div id="sale-order-edit-block-order-info-pin" onclick="BX.Sale.Admin.OrderEditPage.toggleFix(this.id, \'sale-order-edit-block-order-info\');" class="adm-detail-pin-btn-tabs" style="top: 9px;right: 5px;"></div>
				</div>
			</div>';

		$orderEditOpts  = \CUserOptions::GetOption("sale_admin", "sale_order_edit", array());
		$isFixed = isset($orderEditOpts["fix_sale-order-edit-block-order-info"]) && $orderEditOpts["fix_sale-order-edit-block-order-info"] == "Y" ? true : false;
		if($isFixed)
		{
			$result .= '
				<script type="text/javascript">
					BX.ready(function(){
						setTimeout(function(){BX.Sale.Admin.OrderEditPage.toggleFix("sale-order-edit-block-order-info-pin", "sale-order-edit-block-order-info");},1);
					});
				</script>';
		}

		if(strlen($updatersContent) > 0)
		{
			$result .= '
					<script type="text/javascript">
						BX.ready(function(){
							BX.Sale.Admin.OrderEditPage.registerFieldsUpdaters({
							'.$updatersContent.'
							});
						});
					</script>';

		}
		return $result;
	}
}