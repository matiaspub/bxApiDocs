<?php
namespace Bitrix\Sale;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Config;

Loc::loadMessages(__FILE__);

class Configuration
{
	const RESERVE_ON_CREATE = 'O';
	const RESERVE_ON_PAY = 'R';
	const RESERVE_ON_FULL_PAY = 'P';
	const RESERVE_ON_ALLOW_DELIVERY = 'D';
	const RESERVE_ON_SHIP = 'S';

	/**
	 * Returns reservation condition list.
	 *
	 * @param bool $extendedMode			Format mode.
	 * @return array
	 */
	public static function getReservationConditionList($extendedMode = false)
	{
		$extendedMode = ($extendedMode === true);
		if ($extendedMode)
		{
			return array(
				self::RESERVE_ON_CREATE => Loc::getMessage('SALE_CONFIGURATION_RESERVE_ON_CREATE'),
				self::RESERVE_ON_FULL_PAY => Loc::getMessage('SALE_CONFIGURATION_RESERVE_ON_FULL_PAY'),
				self::RESERVE_ON_PAY => Loc::getMessage('SALE_CONFIGURATION_RESERVE_ON_PAY'),
				self::RESERVE_ON_ALLOW_DELIVERY => Loc::getMessage('SALE_CONFIGURATION_RESERVE_ON_ALLOW_DELIVERY'),
				self::RESERVE_ON_SHIP => Loc::getMessage('SALE_CONFIGURATION_RESERVE_ON_SHIP')
			);
		}
		return array(
			self::RESERVE_ON_CREATE,
			self::RESERVE_ON_FULL_PAY,
			self::RESERVE_ON_PAY,
			self::RESERVE_ON_ALLOW_DELIVERY,
			self::RESERVE_ON_SHIP
		);
	}

	/**
	 * Returns current reservation condition.
	 *
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getProductReservationCondition()
	{
		return Config\Option::get('sale', 'product_reserve_condition');
	}

	/**
	 * Returns current clear reserve period.
	 *
	 * @return int
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getProductReserveClearPeriod()
	{
		return (int)Config\Option::get('sale', 'product_reserve_clear_period');
	}

	/**
	 * Check is current reservation with shipment.
	 *
	 * @return bool
	 */
	public static function isReservationDependsOnShipment()
	{
		$condition = static::getProductReservationCondition();
		return in_array($condition, array(static::RESERVE_ON_SHIP, static::RESERVE_ON_ALLOW_DELIVERY));
	}

	/**
	 * Returns true, if current condition - delivery.
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function needShipOnAllowDelivery()
	{
		return ((string)Config\Option::get('sale', 'allow_deduction_on_delivery') == 'Y');
	}

	/**
	 * Returns flag allow delivery on pay.
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function needAllowDeliveryOnPay()
	{
		return ((string)Config\Option::get('sale', 'status_on_payed_2_allow_delivery') == 'Y');
	}

	/**
	 * Returns flag enable use stores.
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function useStoreControl()
	{
		return ((string)Config\Option::get('catalog', 'default_use_store_control') == 'Y');
	}

	/**
	 * Returns flag use reservations.
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function isEnabledReservation()
	{
		return ((string)Config\Option::get('catalog', 'enable_reservation') == 'Y');
	}
}