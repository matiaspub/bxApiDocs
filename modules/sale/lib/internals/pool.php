<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Sale;

class Pool
{
	/** @var array */
	protected $quantities = array();

	/** @var Sale\BasketItem[] */
	protected $items = array();

	static public function __construct()
	{
	}

	public function dump()
	{
		$s = '';
		foreach ($this->quantities as $k => $v)
		{
			if ($s != '')
				$s .= ", ";
			$s .= $k."=".$v;
		}
		return $s;
	}

	/**
	 * Returns any variable by its name. Null if variable is not set.
	 *
	 * @param Sale\BasketItem $basketItem
	 * @return float | null
	 */
	public function get(Sale\BasketItem $basketItem)
	{
		$basketCode = $basketItem->getBasketCode();

		if (isset($this->quantities[$basketCode]) || array_key_exists($basketCode, $this->quantities))
			return $this->quantities[$basketCode];

		return null;
	}

	/**
	 * @param Sale\BasketItem $basketItem
	 * @param $quantity
	 */
	public function set(Sale\BasketItem $basketItem, $quantity)
	{
		$this->quantities[$basketItem->getBasketCode()] = $quantity;
		$this->items[$basketItem->getBasketCode()] = $basketItem;
	}

	/**
	 * @return array
	 */
	public function getQuantities()
	{
		return $this->quantities;
	}

	public function getItems()
	{
		return $this->items;
	}
}