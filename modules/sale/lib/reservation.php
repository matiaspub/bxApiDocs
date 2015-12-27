<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Sale;

use Bitrix\Main\Localization\Loc;


Loc::loadMessages(__FILE__);


class Reservation extends ReservationBase
{

	/**
	 * @param Basket $basketCollection
	 * @param array $productList
	 * @return array
	 */
	public static function getProductList(Basket $basketCollection, array $productList = array())
	{
		$productBasketIndex = array();
		$result = array();

		foreach ($basketCollection as $basketKey => $basketItem)
		{
			$productId = intval($basketItem->getProductId());
			if (intval($productId < 0) || (sizeof($productList) > 0 && in_array($productId, $productList)) )
			{
				continue;
			}

			$productBasketIndex[$basketKey] = $productId;
		}

		$rsProducts = \CCatalogProduct::GetList(
			array(),
			array('ID' => $productBasketIndex),
				false,
				false,
				array('ID', 'CAN_BUY_ZERO', 'NEGATIVE_AMOUNT_TRACE', 'QUANTITY_TRACE', 'QUANTITY', 'QUANTITY_RESERVED')
			);
		while ($arProduct = $rsProducts->Fetch())
		{
			$result[$arProduct['ID']] = $arProduct;
		}

		return $result;
	}
}