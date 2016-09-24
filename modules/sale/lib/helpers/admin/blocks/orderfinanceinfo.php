<?php

namespace Bitrix\Sale\Helpers\Admin\Blocks;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Order;

Loc::loadMessages(__FILE__);

class OrderFinanceInfo
{
	public static function getView(Order $order, $useCurrentBudget = true)
	{
		$data = self::prepareData($order);

		$result =
		'<div class="adm-bus-pay">
			<div class="adm-bus-component-content-container">
				<div class="adm-bus-pay-section">
					<div class="adm-bus-pay-section-content posr">
						<input type="hidden" value="'.$data['PRICE'].'" id="sale-order-financeinfo-price">
						<input type="hidden" value="'.$data['SUM_PAID'].'" id="sale-order-financeinfo-sum-paid">
						<input type="hidden" value="'.$data['PAYABLE'].'" id="sale-order-financeinfo-payable">
						<table class="adm-bus-pay-statuspay " '.(isset($data['OVERPAYMENT']) ? 'style="width: 1000px"' : '').'>
							<thead>
								<tr>
									<td>'.Loc::getMessage('SALE_ORDER_FINANCEINFO_FOR_PAYMENT').'</td>
									<td class="separator"></td>
									<td style="color: #749511;">'.Loc::getMessage('SALE_ORDER_FINANCEINFO_SUM_PAID').'</td>
									<td class="separator"></td>
									<td style="color: #f29129;">'.Loc::getMessage('SALE_ORDER_FINANCEINFO_PAYABLE').'</td>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td id="sale-order-financeinfo-price-view">'.SaleFormatCurrency(floatval($data['PRICE']), $data['CURRENCY']).'</td>
									<td class="separator"></td>
									<td id="sale-order-financeinfo-sum-paid-view">'.SaleFormatCurrency(floatval($data['SUM_PAID']), $data['CURRENCY']).'</td>
									<td class="separator"></td>
									<td id="sale-order-financeinfo-payable-view">'.SaleFormatCurrency(floatval($data['PAYABLE']), $data['CURRENCY']).'</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
					<div class="adm-bus-pay-section"'.(floatval($data['BUYER_BUDGET']) <= 0 ? ' style="display:none;"' : '').'>
						<input id="sale-order-financeinfo-user-budget-input" type="hidden" value="'.floatval($data['BUYER_BUDGET']).'">
						<div class="adm-bus-pay-section-content posr">
							<div class="adm-bus-pay-wallet">
								'.Loc::getMessage('SALE_ORDER_FINANCEINFO_BALANCE_INNER_BUDGET').': <strong id="sale-order-financeinfo-user-budget">' . SaleFormatCurrency($data['BUYER_BUDGET'], $data['CURRENCY']) . '</strong>';

		if ($useCurrentBudget)
			$result .= '<span class="adm-bus-pay-use-current-budget" onclick="BX.Sale.Admin.GeneralPayment.useCurrentBudget(this);">'.Loc::getMessage('SALE_ORDER_FINANCEINFO_USE_INNER_BUDGET').'</span>';

		$result .= '</div>
					</div>
				</div>
			</div>
		</div>';

		return $result;
	}

	public static function prepareData(Order $order)
	{
		$currencyBudget = 0;
		if ($order->getUserId() > 0)
		{
			$res = \CSaleUserAccount::getList(
				array(),
				array(
					'USER_ID' => $order->getUserId(),
					'CURRENCY' => $order->getCurrency(),
					'LOCKED' => 'N'
				),
				false,
				false,
				array(
					'CURRENT_BUDGET'
				)
			);
			$userAccount = $res->Fetch();
			$currencyBudget = $userAccount['CURRENT_BUDGET'];
		}
		
		$payable = $order->getPrice() - $order->getSumPaid();
		$price = $order->getPrice();
		$sumPaid = $order->getSumPaid();
		$data = array(
			'PRICE' => ($price) ? $price : 0,
			'SUM_PAID' => ($sumPaid) ? $sumPaid : 0,
			'PAYABLE' => ($payable >= 0) ? $payable : 0,
			'CURRENCY' => $order->getCurrency(),
			'BUYER_BUDGET' => $currencyBudget,
			'STATUS_ID' => $order->getField('STATUS_ID')
		);

		return $data;
	}

	public static function getScripts()
	{
		\Bitrix\Main\Page\Asset::getInstance()->addJs("/bitrix/js/sale/admin/order_financeinfo.js");

		return '
			<script type="text/javascript">
				BX.ready(function(){
					BX.Sale.Admin.OrderEditPage.registerFieldsUpdaters( BX.Sale.Admin.FinanceInfo.getFieldsUpdaters() );
				});
			</script>
		';
	}
}