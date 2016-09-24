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
	const ALLOW_DELIVERY_ON_PAY = 'R';
	const ALLOW_DELIVERY_ON_FULL_PAY = 'P';
	const STATUS_ON_PAY = 'R';
	const STATUS_ON_FULL_PAY = 'P';

	/**
	 * Returns reservation condition list.
	 *
	 * @param bool $extendedMode			Format mode.
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список правил резервирования и списания товаров. Метод статический.</p>
	*
	*
	* @param boolean $extendedMode = false Формат вывода списка: в кратком или расширенном режиме.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/configuration/getreservationconditionlist.php
	* @author Bitrix
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
	
	/**
	* <p>Возвращает текущее состояние резервирования. Метод статический.</p> <p>Без параметров</p>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/configuration/getproductreservationcondition.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает количество дней, через которое надо снимать резервацию у неоплаченного товара. Метод статический.</p> <p>Без параметров</p>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/configuration/getproductreserveclearperiod.php
	* @author Bitrix
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
	
	/**
	* <p>Метод проверяет резервируется ли товар при отгрузке или разрешении отгрузки. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/configuration/isreservationdependsonshipment.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает <i>true</i>, если разрешена отгрузка при разрешении доставки. Метод статический.</p> <p>Без параметров</p>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/configuration/needshiponallowdelivery.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает флаг разрешения доставки при оплате заказа. Метод статический.</p> <p>Без параметров</p>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/configuration/needallowdeliveryonpay.php
	* @author Bitrix
	*/
	public static function needAllowDeliveryOnPay()
	{
		$condition = static::getAllowDeliveryOnPayCondition();
		return in_array($condition, array(static::ALLOW_DELIVERY_ON_PAY, static::RESERVE_ON_ALLOW_DELIVERY));
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getAllowDeliveryOnPayCondition()
	{
		return Config\Option::get('sale', 'status_on_change_allow_delivery_after_paid');
	}

	/**
	 * @param bool $extendedMode
	 *
	 * @return array
	 */
	public static function getAllowDeliveryAfterPaidConditionList($extendedMode = false)
	{
		if ($extendedMode)
		{
			return array(
				self::ALLOW_DELIVERY_ON_PAY => Loc::getMessage('SALE_CONFIGURATION_ON_PAY'),
				self::ALLOW_DELIVERY_ON_FULL_PAY => Loc::getMessage('SALE_CONFIGURATION_ON_FULL_PAY'),
			);
		}
		return array(
			self::ALLOW_DELIVERY_ON_PAY,
			self::ALLOW_DELIVERY_ON_FULL_PAY,
		);
	}

	public static function getStatusPaidCondition()
	{
		return Config\Option::get('sale', 'status_on_paid_condition');
	}

	public static function getStatusAllowDeliveryCondition()
	{
		return Config\Option::get('sale', 'status_on_paid_condition');
	}

	/**
	 * Returns flag enable use stores.
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	
	/**
	* <p>Метод возвращает флаг использования складского учета. Метод статический.</p> <p>Без параметров</p>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/configuration/usestorecontrol.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает флаг использования механизма резервирования товаров. Метод статический.</p> <p>Без параметров</p>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/configuration/isenabledreservation.php
	* @author Bitrix
	*/
	public static function isEnabledReservation()
	{
		return ((string)Config\Option::get('catalog', 'enable_reservation') == 'Y');
	}
}