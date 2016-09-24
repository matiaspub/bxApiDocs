<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Basket;


Loc::loadMessages(__FILE__);


abstract class ReservationBase extends Attributes
{

	protected function __construct()
	{

	}

	/**
	 * @return bool
	 */
	static public function getEnableReservation()
	{
		//default_use_store_control = Y
		return (COption::GetOptionString("catalog", "enable_reservation") == "Y"
//			&& COption::GetOptionString("sale", "product_reserve_condition", "O") != "S"
			&& COption::GetOptionString('catalog','default_use_store_control') == "Y");
	}

	/**
	 * @param Basket $basketCollection
	 * @param array $productList
	 * @throws \Exception
	 */


	/**
	 * @param Basket $basketCollection
	 * @param array $productList
	 * @return array
	 */
	public static function getProductList(Basket $basketCollection, array $productList = array())
	{
		throw new \Exception("Method 'ReservationBase::getProduct' is not overridden");
	}

} 