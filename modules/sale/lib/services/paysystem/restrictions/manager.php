<?php

namespace Bitrix\Sale\Services\PaySystem\Restrictions;

use Bitrix\Sale\Internals\ServiceRestrictionTable;
use Bitrix\Sale\Payment;
use Bitrix\Sale\Services\Base;

class Manager extends Base\RestrictionManager
{
	protected static $classNames = null;

	/**
	 * @return string
	 */
	public static function getEventName()
	{
		return 'onSalePaySystemRestrictionsClassNamesBuildList';
	}

	/**
	 * @return array
	 */
	public static function getBuildInRestrictions()
	{
		return array(
			'\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType' => 'lib/services/paysystem/restrictions/persontype.php',
			'\Bitrix\Sale\Services\PaySystem\Restrictions\Price' => 'lib/services/paysystem/restrictions/price.php',
			'\Bitrix\Sale\Services\PaySystem\Restrictions\PercentPrice' => 'lib/services/paysystem/restrictions/percentprice.php',
			'\Bitrix\Sale\Services\PaySystem\Restrictions\Currency' => 'lib/services/paysystem/restrictions/currency.php',
			'\Bitrix\Sale\Services\PaySystem\Restrictions\Delivery' => 'lib/services/paysystem/restrictions/delivery.php',
			'\Bitrix\Sale\Services\PaySystem\Restrictions\Site' => 'lib/services/paysystem/restrictions/site.php'
		);
	}

	/**
	 * @return int
	 */
	protected static function getServiceType()
	{
		return parent::SERVICE_TYPE_PAYMENT;
	}

	public static function getPriceRange(Payment $payment, $paySystemId)
	{
		$result = array();

		$classes = array(
			'\Bitrix\Sale\Services\PaySystem\Restrictions\PercentPrice',
			'\Bitrix\Sale\Services\PaySystem\Restrictions\Price'
		);

		$params = array(
			'select' => array('CLASS_NAME', 'PARAMS'),
			'filter' => array(
				'SERVICE_ID' => $paySystemId,
				'=CLASS_NAME' => $classes
			)
		);

		$dbRes = Manager::getList($params);
		while ($data = $dbRes->fetch())
		{
			$range = $data['CLASS_NAME']::getRange($payment, $data['PARAMS']);

			if (!$result['MAX'] || $range['MAX'] < $result['MAX'])
				$result['MAX'] = $range['MAX'];

			if (!$result['MIN'] || $range['MIN'] > $result['MIN'])
				$result['MIN'] = $range['MIN'];
		}

		return $result;
	}
}