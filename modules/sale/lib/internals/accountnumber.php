<?php


namespace Bitrix\Sale\Internals;


use Bitrix\Main;
use Bitrix\Sale;

class AccountNumberGenerator
{
	const ACCOUNT_NUMBER_SEPARATOR = "/";

	/**
	 * @param CollectableEntity $item
	 *
	 * @return null
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectNotFoundException
	 */
	public static function generate(CollectableEntity $item)
	{
		$accountNumber = null;
		/** @var EntityCollection $collection */
		if (!$collection = $item->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "Collection" not found');
		}

		if (!method_exists($collection, "getOrder"))
		{
			throw new Main\NotSupportedException();
		}

		/** @var Sale\Order $order */
		if (!$order = $collection->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		$accountNumber = $order->getField('ACCOUNT_NUMBER').static::ACCOUNT_NUMBER_SEPARATOR;

		$count = 1;
		/** @var CollectableEntity $itemCollection */
		foreach ($collection as $itemCollection)
		{
			if (strval($itemCollection->getField("ACCOUNT_NUMBER")) != "")
			{
				list($orderAccountNumber, $itemAccountNumber) = explode(static::ACCOUNT_NUMBER_SEPARATOR, $itemCollection->getField("ACCOUNT_NUMBER"));

				if ($count <= $itemAccountNumber)
					$count = $itemAccountNumber + 1;
			}
		}

		return $accountNumber.$count;
	}
}