<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Sale;

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Sale,
	Bitrix\Currency,
	Bitrix\Catalog;

if (!Loader::includeModule('sale'))
	return false;

/**
 * Class ProviderAccountPay
 * @package Bitrix\Sale
 */
class ProviderAccountPay implements \IBXSaleProductProvider
{
	/**
	 * @param array $fields
	 * @return array
	 */
	public static function GetProductData($fields)
	{
		$fields["CAN_BUY"] = 'Y';
		$fields["AVAILABLE_QUANTITY"] = 100000000;
		return $fields;

	}

	public static function OrderProduct($fields)
	{
		$fields["AVAILABLE_QUANTITY"] = 'Y';
		return $fields;
	}

	public static function CancelProduct($fields)
	{
	}

	public static function DeliverProduct($fields)
	{
	}

	public static function ViewProduct($fields)
	{
	}

	public static function RecurringOrderProduct($fields)
	{
	}

	public static function GetStoresCount($arParams = array())
	{
	}

	public static function GetProductStores($fields)
	{
	}

	public static function ReserveProduct($fields)
	{
		$fields['QUANTITY_RESERVED'] = $fields['QUANTITY_ADD'];
		$fields['RESULT'] = true;
		return $fields;
	}

	public static function CheckProductBarcode($fields)
	{
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	public static function DeductProduct($fields)
	{
		/** @var Sale\BasketItem $basketItem*/
		$basketItem = $fields['BASKET_ITEM'];
		$orderId = (int)$basketItem->getField('ORDER_ID');
		$currency = $basketItem->getField('CURRENCY');

		$propertyCollection = $basketItem->getPropertyCollection();
		
		$item = $propertyCollection->getPropertyValues();
		$sum = (float)($item['SUM_OF_CHARGE']['VALUE']) * (float)($basketItem->getQuantity());

		/** @var Basket $basket */
		$basket = $basketItem->getCollection();
		$order = $basket->getOrder();
		$userId = $order->getUserId();

		$resultUpdateUserAccount = \CSaleUserAccount::UpdateAccount($userId, ($fields["UNDO_DEDUCTION"]==='N'?$sum:-$sum), $currency, "MANUAL", $orderId, "Payment to user account");

		if ($resultUpdateUserAccount)
		{
			$fields['RESULT'] = true;
		}
		else
		{
			return false;
		}

		return $fields;
	}
}