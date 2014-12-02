<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Main\Analytics;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;

/**
 * @package bitrix
 * @subpackage main
 */ 
class Catalog
{
	// basket (catalog:OnBasketAdd)
	public static function catchCatalogBasket($id, $arFields)
	{
		if (!static::isOn())
		{
			return;
		}

		// select site user id
		$siteUserId = 0;

		if(\COption::GetOptionString("sale", "encode_fuser_id", "N") == "Y")
		{
			$filter = array('CODE' => $arFields['FUSER_ID']);
		}
		else
		{
			$filter = array('ID' => $arFields['FUSER_ID']);
		}

		$result = \CSaleUser::getList($filter);

		if (!empty($result))
		{
			$siteUserId = $result['USER_ID'];
		}

		// prepare data
		$data = array(
			'product_id' => $arFields['PRODUCT_ID'],
			'user_id' => $siteUserId,
			'bx_user_id' => static::getBxUserId(),
			'domain' => Context::getCurrent()->getServer()->getHttpHost(),
			'recommendation' => '0',
			'date' => date(DATE_ISO8601)
		);

		CounterDataTable::add(array(
			'TYPE' => 'basket',
			'DATA' => $data
		));
	}

	// order detailed info (OnOrderSave)
	public static function catchCatalogOrder($orderId, $arFields, $arOrder, $isNew)
	{
		if (!static::isOn())
		{
			return;
		}

		if (!$isNew)
		{
			// only new orders
			return;
		}

		$data = static::getOrderInfo($orderId);

		$data['paid'] = '0';
		$data['bx_user_id'] = static::getBxUserId();
		$data['domain'] = Context::getCurrent()->getServer()->getHttpHost();
		$data['date'] = date(DATE_ISO8601);

		CounterDataTable::add(array(
			'TYPE' => 'order',
			'DATA' => $data
		));
	}

	// order payment (OnSalePayOrder)
	public static function catchCatalogOrderPayment($orderId, $value)
	{
		if (!static::isOn())
		{
			return;
		}

		if ($value == 'Y')
		{
			$data = static::getOrderInfo($orderId);

			$data['paid'] = '1';
			$data['bx_user_id'] = static::getBxUserId();
			$data['domain'] = Context::getCurrent()->getServer()->getHttpHost();
			$data['date'] = date(DATE_ISO8601);

			CounterDataTable::add(array(
				'TYPE' => 'order_pay',
				'DATA' => $data
			));
		}
	}

	protected static function getOrderInfo($orderId)
	{
		// order itself
		$order = \CSaleOrder::getById($orderId);

		// buyer info
		$siteUserId = $order['USER_ID'];

		$phone = '';
		$email = '';

		$result = \CSaleOrderPropsValue::GetList(array(), array("ORDER_ID" => $orderId));
		while ($row = $result->fetch())
		{
			if (empty($phone) && stripos($row['CODE'], 'PHONE') !== false)
			{
				$stPhone = static::normalizePhoneNumber($row['VALUE']);

				if (!empty($stPhone))
				{
					$phone = sha1($stPhone);
				}
			}

			if (empty($email) && stripos($row['CODE'], 'EMAIL') !== false)
			{
				if (!empty($row['VALUE']))
				{
					$email = sha1($row['VALUE']);
				}
			}
		}

		// products info
		$products = array();

		$result = \CSaleBasket::getList(
			array(), $arFilter = array('ORDER_ID' => $orderId), false, false, array('PRODUCT_ID')
		);

		while ($row = $result->fetch())
		{
			$products[] = array('product_id' => $row['PRODUCT_ID'], 'recommendation' => '0');
		}

		// all together
		$data = array(
			'order_id' => $orderId,
			'user_id' => $siteUserId,
			'phone' => $phone,
			'email' => $email,
			'products' => $products
		);

		return $data;
	}

	protected function getBxUserId()
	{
		if (defined('ADMIN_SECTION') && ADMIN_SECTION)
		{
			return '';
		}
		else
		{
			return $_COOKIE['BX_USER_ID'];
		}
	}

	public static function normalizePhoneNumber($phone)
	{
		$phone = preg_replace('/[^\d]/', '', $phone);

		$cleanPhone = \NormalizePhone($phone, 6);

		if (strlen($cleanPhone) == 10)
		{
			$cleanPhone = '7'.$cleanPhone;
		}

		return $cleanPhone;
	}

	public static function isOn()
	{
		return SiteSpeed::isLicenseAccepted()
			&& Option::get("main", "gather_catalog_stat", "Y") === "Y"
			&& defined("LICENSE_KEY") && LICENSE_KEY !== "DEMO"
		;
	}
}
