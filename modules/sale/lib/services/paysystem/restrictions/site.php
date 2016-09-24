<?php
namespace Bitrix\Sale\Services\PaySystem\Restrictions;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Restrictions;
use Bitrix\Sale\Internals;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaymentCollection;

Loc::loadMessages(__FILE__);

/**
 * Class Site
 * @package Bitrix\Sale\Services\PaySystem\Restrictions
 */
class Site extends Restrictions\BySite
{
	/**
	 * @param Internals\CollectableEntity $entity
	 * @return null|string
	 */
	protected static function extractParams(Internals\CollectableEntity $entity)
	{
		if (!($entity instanceof Payment))
			return false;

		/** @var PaymentCollection $collection */
		$collection = $entity->getCollection();

		/** @var Order $order */
		$order = $collection->getOrder();

		return $order->getSiteId();
	}
} 