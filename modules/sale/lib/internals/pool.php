<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Sale;

class Pool
{
	/** @var array */
	protected $quantities = array();

	/** @var array */
	protected $items = array();

	static public function __construct()
	{
	}

	/**
	 * Returns any variable by its name. Null if variable is not set.
	 *
	 * @param $code
	 * @return float | null
	 */
	public function get($code)
	{
		if (isset($this->quantities[$code]) || array_key_exists($code, $this->quantities))
			return $this->quantities[$code];

		return null;
	}

	/**
	 * @param $code
	 * @param $quantity
	 */
	public function set($code, $quantity)
	{
		$this->quantities[$code] = $quantity;

	}

	/**
	 * @param $code
	 * @param $item
	 */
	public function addItem($code, $item)
	{
		if (!array_key_exists($code, $this->items))
			$this->items[$code] = $item;
	}
	/**
	 * @return array
	 */
	public function getQuantities()
	{
		return $this->quantities;
	}

	/**
	 * @return array
	 */
	public function getItems()
	{
		return $this->items;
	}
}