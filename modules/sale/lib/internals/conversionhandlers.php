<?php

namespace Bitrix\Sale\Internals;

use Bitrix\Conversion\Utils;
use Bitrix\Conversion\DayContext;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;

Loc::loadMessages(__FILE__);

/** @internal */
final class ConversionHandlers
{
	static public function onGetCounterTypes()
	{
		return array(
			'sale_cart_add_day' => array('MODULE' => 'sale', 'NAME' => 'Added to cart goals', 'GROUP' => 'day'),
			'sale_cart_add'     => array('MODULE' => 'sale', 'NAME' => 'Added to cart total'),
			'sale_cart_sum_add' => array('MODULE' => 'sale', 'NAME' => 'Sum added to cart'),

			'sale_order_add_day' => array('MODULE' => 'sale', 'NAME' => 'Placed orders goals', 'GROUP' => 'day'),
			'sale_order_add'     => array('MODULE' => 'sale', 'NAME' => 'Placed orders total'),
			'sale_order_sum_add' => array('MODULE' => 'sale', 'NAME' => 'Sum placed orders'),

			'sale_payment_add_day' => array('MODULE' => 'sale', 'NAME' => 'Payments a day goals', 'GROUP' => 'day'),
			'sale_payment_add'     => array('MODULE' => 'sale', 'NAME' => 'Payments a day total'),
			'sale_payment_sum_add' => array('MODULE' => 'sale', 'NAME' => 'Added payment sum'),
		);
	}

	static public function onGetRateTypes()
	{
		$scale = array(0.5, 1, 1.5, 2, 5);

		$format = array(
			'SUM' => function ($value, $format = null)
			{
				return Utils::formatToBaseCurrency($value, $format);
			},
		);

		$units = array('SUM' => Utils::getBaseCurrencyUnit()); // TODO deprecated

		return array(
			'sale_payment' => array(
				'NAME'      => Loc::getMessage('SALE_CONVERSION_RATE_PAYMENT_NAME'),
				'SCALE'     => $scale,
				'FORMAT'    => $format,
				'UNITS'     => $units,
				'MODULE'    => 'sale',
				'SORT'      => 1100,
				'COUNTERS'  => array('conversion_visit_day', 'sale_payment_add_day', 'sale_payment_add', 'sale_payment_sum_add'),
				'CALCULATE' => function (array $counters)
				{
					$denominator = $counters['conversion_visit_day'] ?: 0;
					$numerator   = $counters['sale_payment_add_day'] ?: 0;
					$quantity    = $counters['sale_payment_add'    ] ?: 0;
					$sum         = $counters['sale_payment_sum_add'] ?: 0;

					return array(
						'DENOMINATOR' => $denominator,
						'NUMERATOR'   => $numerator,
						'QUANTITY'    => $quantity,
						'RATE'        => $denominator ? $numerator / $denominator : 0,
						'SUM'         => $sum,
					);
				},
			),

			'sale_order' => array(
				'NAME'      => Loc::getMessage('SALE_CONVERSION_RATE_ORDER_NAME'),
				'SCALE'     => $scale,
				'FORMAT'    => $format,
				'UNITS'     => $units,
				'MODULE'    => 'sale',
				'SORT'      => 1200,
				'COUNTERS'  => array('conversion_visit_day', 'sale_order_add_day', 'sale_order_add', 'sale_order_sum_add'),
				'CALCULATE' => function (array $counters)
				{
					$denominator = $counters['conversion_visit_day'] ?: 0;
					$numerator   = $counters['sale_order_add_day'  ] ?: 0;
					$quantity    = $counters['sale_order_add'      ] ?: 0;
					$sum         = $counters['sale_order_sum_add'  ] ?: 0;

					return array(
						'DENOMINATOR' => $denominator,
						'NUMERATOR'   => $numerator,
						'QUANTITY'    => $quantity,
						'RATE'        => $denominator ? $numerator / $denominator : 0,
						'SUM'         => $sum,
					);
				},
			),

			'sale_cart' => array(
				'NAME'      => Loc::getMessage('SALE_CONVERSION_RATE_CART_NAME'),
				'SCALE'     => $scale,
				'FORMAT'    => $format,
				'UNITS'     => $units,
				'MODULE'    => 'sale',
				'SORT'      => 1300,
				'COUNTERS'  => array('conversion_visit_day', 'sale_cart_add_day', 'sale_cart_add', 'sale_cart_sum_add'),
				'CALCULATE' => function (array $counters)
				{
					$denominator = $counters['conversion_visit_day'] ?: 0;
					$numerator   = $counters['sale_cart_add_day'   ] ?: 0;
					$quantity    = $counters['sale_cart_add'       ] ?: 0;
					$sum         = $counters['sale_cart_sum_add'   ] ?: 0;

					return array(
						'DENOMINATOR' => $denominator,
						'NUMERATOR'   => $numerator,
						'QUANTITY'    => $quantity,
						'RATE'        => $denominator ? $numerator / $denominator : 0,
						'SUM'         => $sum,
					);
				},
			),
		);
	}

	static public function onGenerateInitialData(Date $from, Date $to)
	{
		$data = array();

		// 1. Payments

		$result = \CSaleOrder::GetList(
			array(),
			array(
				'PAYED'        => 'Y',
				'CANCELED'     => 'N',
				'>=DATE_PAYED' => $from,
				'<=DATE_PAYED' => $to,
			),
			false,
			false,
			array('LID', 'DATE_PAYED', 'PRICE', 'CURRENCY')
		);

		while ($row = $result->Fetch())
		{
			$day = new DateTime($row['DATE_PAYED']);
			$sum = Utils::convertToBaseCurrency($row['PRICE'], $row['CURRENCY']);

			if ($counters =& $data[$row['LID']][$day->format('Y-m-d')])
			{
				$counters['sale_payment_add_day'] += 1;
				$counters['sale_payment_sum_add'] += $sum;
			}
			else
			{
				$counters = array(
					'sale_payment_add_day' => 1,
					'sale_payment_sum_add' => $sum,
				);
			}
		}

		// 2. Orders

		$result = \CSaleOrder::GetList(
			array(),
			array(
				'CANCELED'      => 'N',
				'>=DATE_INSERT' => $from,
				'<=DATE_INSERT' => $to,
			),
			false,
			false,
			array('LID', 'DATE_INSERT', 'PRICE', 'CURRENCY')
		);

		while ($row = $result->Fetch())
		{
			$day = new DateTime($row['DATE_INSERT']);
			$sum = Utils::convertToBaseCurrency($row['PRICE'], $row['CURRENCY']);

			if ($counters =& $data[$row['LID']][$day->format('Y-m-d')])
			{
				$counters['sale_order_add_day'] += 1;
				$counters['sale_order_sum_add'] += $sum;
			}
			else
			{
				$counters = array(
					'sale_order_add_day' => 1,
					'sale_order_sum_add' => $sum,
				);
			}
		}

		// 3. Cart

		$result = \CSaleBasket::GetList(
			array(),
			array(
				'>=DATE_INSERT' => $from,
				'<=DATE_INSERT' => $to,
			),
			false,
			false,
			array('LID', 'DATE_INSERT', 'PRICE', 'CURRENCY', 'QUANTITY')
		);

		while ($row = $result->Fetch())
		{
			$day = new DateTime($row['DATE_INSERT']);
			$sum = Utils::convertToBaseCurrency($row['PRICE'] * $row['QUANTITY'], $row['CURRENCY']);

			if ($counters =& $data[$row['LID']][$day->format('Y-m-d')])
			{
				$counters['sale_cart_add_day'] += 1;
				$counters['sale_cart_sum_add'] += $sum;
			}
			else
			{
				$counters = array(
					'sale_cart_add_day' => 1,
					'sale_cart_sum_add' => $sum,
				);
			}
		}

		// Result

		unset($counters);

		$result = array();

		foreach ($data as $siteId => $dayCounters)
		{
			$result []= array(
				'ATTRIBUTES'   => array('conversion_site' => $siteId),
				'DAY_COUNTERS' => $dayCounters,
			);
		}

		return $result;
	}

	// Cart Counters

	// Events can be stacked!!!
	// 1) OnBeforeBasketAdd -> OnBasketAdd
	// 2) OnBeforeBasketAdd -> OnBeforeBasketUpdate -> OnBasketUpdate -> OnBasketAdd
	// 3) and other variations with mixed arguments as well, sick!!!

	public static function onSaleBasketItemSaved(Main\Event $event)
	{
		if (!$event->getParameter('IS_NEW'))
			return;

		$basketItem = $event->getParameter('ENTITY');

		if ($basketItem instanceof Sale\BasketItem)
		{
			$price    = $basketItem->getPrice();
			$quantity = $basketItem->getQuantity();
			$currency = $basketItem->getCurrency();

			if ($quantity && Loader::includeModule('conversion'))
			{
				$context = DayContext::getInstance();

				$context->addDayCounter('sale_cart_add_day', 1);
				$context->addCounter('sale_cart_add', 1);

				if ($price*$quantity && $currency)
					$context->addCurrencyCounter('sale_cart_sum_add', $price*$quantity, $currency);
			}
		}
	}

	static $onBeforeBasketAddQuantity = 0;

	static public function onBeforeBasketAdd(/*array*/ $fields)
	{
		self::$onBeforeBasketAddQuantity = (is_array($fields) && isset($fields['QUANTITY'])) ? $fields['QUANTITY'] : 0;
	}

	static public function onBasketAdd($id, /*array*/ $fields)
	{
		if (is_array($fields)
			&& isset($fields['PRICE'], $fields['QUANTITY'], $fields['CURRENCY'])
			&& self::$onBeforeBasketAddQuantity
			&& Loader::includeModule('conversion'))
		{
			$context = DayContext::getInstance();
			$context->addDayCounter     ('sale_cart_add_day', 1);
			$context->addCounter        ('sale_cart_add'    , 1);
			$context->addCurrencyCounter('sale_cart_sum_add', $fields['PRICE'] * self::$onBeforeBasketAddQuantity, $fields['CURRENCY']);
		}

		self::$onBeforeBasketAddQuantity = 0;
	}

	//static private $onBeforeBasketUpdate = 0;

	static public function onBeforeBasketUpdate($id, /*array*/ $fields = null) // null hack/fix 4 sale 15
	{
		/*self::$onBeforeBasketUpdate =

			Loader::includeModule('conversion')
			&& ($intId = (int) $id) > 0
			&& $intId == $id
			&& ($row = \CSaleBasket::GetByID($id))

				? $row['PRICE'] * $row['QUANTITY'] : 0;*/
	}

	static public function onBasketUpdate($id, /*array*/ $fields)
	{
		/*if (Loader::includeModule('conversion')
			&& is_array($fields)
			&& isset($fields['PRICE'], $fields['QUANTITY'], $fields['CURRENCY']))
		{
			$context = DayContext::getInstance();

			$newSum = $fields['PRICE'] * $fields['QUANTITY'];

			// add item to cart
			if ($newSum > self::$onBeforeBasketUpdate)
			{
				$context->addCurrencyCounter('sale_cart_sum_add', $newSum - self::$onBeforeBasketUpdate, $fields['CURRENCY']);
			}
			// remove item from cart
			elseif ($newSum < self::$onBeforeBasketUpdate)
			{
				$context->addCurrencyCounter('sale_cart_sum_rem', self::$onBeforeBasketUpdate - $newSum, $fields['CURRENCY']);
			}
		}

		self::$onBeforeBasketUpdate = 0;*/
	}

	//static private $onBeforeBasketDeleteSum = 0;
	//static private $onBeforeBasketDeleteCurrency; // TODO same to all other

	static public function onBeforeBasketDelete($id)
	{
		/*self::$onBeforeBasketDeleteSum =

			Loader::includeModule('conversion')
			&& ($intId = (int) $id) > 0
			&& $intId == $id
			&& ($row = \CSaleBasket::GetByID($id))
			&& (self::$onBeforeBasketDeleteCurrency = $row['CURRENCY'])

				? $row['PRICE'] * $row['QUANTITY'] : 0;*/
	}

	static public function onBasketDelete($id)
	{
		/*if (Loader::includeModule('conversion') && self::$onBeforeBasketDeleteSum > 0)
		{
			$context = DayContext::getInstance();
			$context->addCurrencyCounter('sale_cart_sum_rem', self::$onBeforeBasketDeleteSum, self::$onBeforeBasketDeleteCurrency);
		}

		self::$onBeforeBasketDeleteSum = 0;*/
	}

	// Order Counters

	public static function onSaleOrderSaved(Main\Event $event)
	{
		if (!$event->getParameter('IS_NEW'))
			return;

		$order = $event->getParameter('ENTITY');

		if ($order instanceof Sale\Order)
		{
			$price    = $order->getPrice();
			$currency = $order->getCurrency();

			if (Loader::includeModule('conversion'))
			{
				$context = DayContext::getInstance();

				$context->addDayCounter('sale_order_add_day', 1);
				$context->addCounter('sale_order_add', 1);
				$context->attachEntityItem('sale_order', $order->getId());

				if ($price && $currency)
					$context->addCurrencyCounter('sale_order_sum_add', $price, $currency);
			}
		}
	}

	static public function onOrderAdd($id, array $fields)
	{
		if (Loader::includeModule('conversion'))
		{
			$context = DayContext::getInstance();
			$context->addDayCounter     ('sale_order_add_day', 1);
			$context->addCounter        ('sale_order_add'    , 1);
			$context->addCurrencyCounter('sale_order_sum_add', $fields['PRICE'], $fields['CURRENCY']);
			$context->attachEntityItem  ('sale_order', $id);
		}
	}

	// Payment Counters

	public static function onSaleOrderPaid(Main\Event $event)
	{
		$order = $event->getParameter('ENTITY');

		if (!$order->isPaid())
			return;

		if ($order instanceof Sale\Order)
		{
			$price    = $order->getPrice();
			$currency = $order->getCurrency();

			if (Loader::includeModule('conversion'))
			{
				$context = DayContext::getEntityItemInstance('sale_order', $order->getId());

				$addMethod = defined('ADMIN_SECTION') && ADMIN_SECTION === true ? 'addCounter' : 'addDayCounter';
				$context->$addMethod('sale_payment_add_day', 1);
				$context->addCounter('sale_payment_add', 1);

				if ($price && $currency)
					$context->addCurrencyCounter('sale_payment_sum_add', $price, $currency);
			}
		}
	}

	static public function onSalePayOrder($id, $paid)
	{
		if (Loader::includeModule('conversion') && ($row = \CSaleOrder::GetById($id)))
		{
			$context = DayContext::getEntityItemInstance('sale_order', $id);

			if ($paid == 'Y')
			{
				if (defined('ADMIN_SECTION') && ADMIN_SECTION === true)
				{
					$context->addCounter    ('sale_payment_add_day', 1);
				}
				else
				{
					$context->addDayCounter ('sale_payment_add_day', 1);
				}

				$context->addCounter        ('sale_payment_add'    , 1);
				$context->addCurrencyCounter('sale_payment_sum_add', $row['PRICE'], $row['CURRENCY']);
			}
			/*
			elseif ($paid == 'N')
			{
				if (defined('ADMIN_SECTION') && ADMIN_SECTION === true)
				{
					// TODO what if payment added by user and removed by admin -- conversion is going down!!!
					$context->addCounter    ('sale_payment_rem_day', 1);
				}
				else
				{
					$context->addDayCounter ('sale_payment_rem_day', 1);
				}

				$context->addCounter        ('sale_payment_rem'    , 1);
				$context->addCurrencyCounter('sale_payment_sum_rem', $row['PRICE'], $row['CURRENCY']);
			}
			*/
		}
	}
}
