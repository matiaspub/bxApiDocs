<?php

namespace Bitrix\Sale\Conversion;

use Bitrix\Conversion\Config;
use Bitrix\Conversion\DayContext;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Handlers
{
//	public static function OnGetCountersInfo()
//	{
//		return array(
//			'sale_cart_add_day' => array('MODULE' => 'sale', 'GROUP' => 'day', 'NAME' => ''),
//			'sale_cart_sum_add' => array('MODULE' => 'sale', 'GROUP' => 'sale_cart_sum', 'TYPE' => 'currency', 'NAME' => ''),
////			'sale_cart_sum_rem' => array('MODULE' => 'sale', 'GROUP' => 'sale_cart_sum', 'TYPE' => 'currency', 'NAME' => ''),
//
//			'sale_order_add_day' => array('MODULE' => 'sale', 'GROUP' => 'day', 'NAME' => ''),
//			'sale_order_sum_add' => array('MODULE' => 'sale', 'GROUP' => 'sale_order_sum', 'NAME' => ''),
////			'sale_order_sum_rem' => array('MODULE' => 'sale', 'GROUP' => 'sale_order_sum', 'NAME' => ''),
//
//			'sale_payment_add_day' => array('MODULE' => 'sale', 'GROUP' => 'day', 'NAME' => ''),
//			'sale_payment_sum_add' => array('MODULE' => 'sale', 'GROUP' => 'sale_payment_sum', 'NAME' => ''),
////			'sale_payment_sum_rem' => array('MODULE' => 'sale', 'GROUP' => 'sale_payment_sum', 'NAME' => ''),
//		);
//	}

	static public function OnGetRateClasses()
	{
		$scale = array(0.5, 1, 1.5, 2, 5);
		$units = array('SUM' => Config::getCurrencyUnit());

		return array(
			'sale_payment' => array(
				'TITLE'     => Loc::getMessage('SALE_CONVERSION_RATE_PAYMENT_TITLE'),
				'SCALE'     => $scale,
				'UNITS'     => $units,
				'MODULE'    => 'sale',
				'COUNTERS'  => array('conversion_visit_day', 'sale_payment_add_day', 'sale_payment_sum_add'),
				'CALCULATE' => function (array $counters)
				{
					$denominator = $counters['conversion_visit_day'] ?: 0;
					$numerator   = $counters['sale_payment_add_day'] ?: 0;
					$sum         = $counters['sale_payment_sum_add'] ?: 0;

					return array(
						'DENOMINATOR'   => $denominator,
						'NUMERATOR'     => $numerator,
						'RATE'          => $denominator ? $numerator / $denominator : 0,
						'SUM'           => $sum,
					);
				},
			),

			'sale_order' => array(
				'TITLE'     => Loc::getMessage('SALE_CONVERSION_RATE_ORDER_TITLE'),
				'SCALE'     => $scale,
				'UNITS'     => $units,
				'MODULE'    => 'sale',
				'COUNTERS'  => array('conversion_visit_day', 'sale_order_add_day', 'sale_order_sum_add'),
				'CALCULATE' => function (array $counters)
				{
					$denominator = $counters['conversion_visit_day'] ?: 0;
					$numerator   = $counters['sale_order_add_day'  ] ?: 0;
					$sum         = $counters['sale_order_sum_add'  ] ?: 0;

					return array(
						'DENOMINATOR'   => $denominator,
						'NUMERATOR'     => $numerator,
						'RATE'          => $denominator ? $numerator / $denominator : 0,
						'SUM'           => $sum,
					);
				},
			),

			'sale_cart' => array(
				'TITLE'     => Loc::getMessage('SALE_CONVERSION_RATE_CART_TITLE'),
				'SCALE'     => $scale,
				'UNITS'     => $units,
				'MODULE'    => 'sale',
				'COUNTERS'  => array('conversion_visit_day', 'sale_cart_add_day', 'sale_cart_sum_add'),
				'CALCULATE' => function (array $counters)
				{
					$denominator = $counters['conversion_visit_day'] ?: 0;
					$numerator   = $counters['sale_cart_add_day'   ] ?: 0;
					$sum         = $counters['sale_cart_sum_add'   ] ?: 0;

					return array(
						'DENOMINATOR'   => $denominator,
						'NUMERATOR'     => $numerator,
						'RATE'          => $denominator ? $numerator / $denominator : 0,
						'SUM'           => $sum,
					);
				},
			),
		);
	}

	// Cart

	private static $cartSum;

	public static function OnBeforeBasketAdd(array $fields)
	{
		if (Loader::includeModule('conversion'))
		{
			if ($row = \CSaleBasket::GetList(
				array(),
				array(
					'LID'        => $fields['LID'],
					'FUSER_ID'   => $fields['FUSER_ID'],
					'PRODUCT_ID' => $fields['PRODUCT_ID'],
					'ORDER_ID'   => 'NULL',
				),
				false,
				false,
				array('PRICE', 'QUANTITY')
			)->Fetch())
			{
				self::$cartSum = $row['PRICE'] * $row['QUANTITY'];
			}
			else
			{
				self::$cartSum = 0;
			}
		}
	}

	public static function OnBasketAdd($id, array $fields)
	{
		if (Loader::includeModule('conversion'))
		{
			$sum = $fields['PRICE'] * $fields['QUANTITY'];

			if ($sum > self::$cartSum)
			{
				$context = DayContext::getInstance();
				$context->addDayCounter     ('sale_cart_add_day', 1);
				$context->addCurrencyCounter('sale_cart_sum_add', $sum - self::$cartSum, $fields['CURRENCY']);
			}
		}
	}

	// Order

	public static function OnOrderAdd($id, array $fields)
	{
		if (Loader::includeModule('conversion'))
		{
			$context = DayContext::getInstance();
			$context->addDayCounter     ('sale_order_add_day', 1);
			$context->addCurrencyCounter('sale_order_sum_add', $fields['PRICE'], $fields['CURRENCY']);
			$context->attachEntityItem  ('sale_order', $id);
		}
	}

	// Payment

	public static function OnSalePayOrder($id, $paid)
	{
		if (Loader::includeModule('conversion') && ($row = \CSaleOrder::GetById($id)))
		{
			if ($paid == 'Y')
			{
				$context = DayContext::getEntityItemInstance('sale_order', $id);
				$context->addCurrencyCounter('sale_payment_sum_add', $row['PRICE'], $row['CURRENCY']);

				if (defined('ADMIN_SECTION') && ADMIN_SECTION === true)
				{
					$context->addCounter    ('sale_payment_add_day', 1, new \Bitrix\Main\Type\Date());
				}
				else
				{
					$context->addDayCounter ('sale_payment_add_day', 1);
				}
			}
		}
	}
}
