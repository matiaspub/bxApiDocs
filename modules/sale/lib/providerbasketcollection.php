<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Sale;

use Bitrix\Sale\Internals\EntityCollection;

class ProviderBasketCollection extends EntityCollection
{

	public function addBasketItem($provider, ProviderBasketItem $basketItem)
	{
		$this->collection[$provider->getId()][$basketItem->getField('BASKET_ID')] = $basketItem;
	}

}
