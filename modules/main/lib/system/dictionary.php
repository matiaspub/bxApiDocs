<?php
namespace Bitrix\Main\System;

class Dictionary
	implements \ArrayAccess, \Iterator, \Countable
{
	/**
	 * @var array
	 */
	protected $arValues = array();

	/**
	 * Creates object.
	 *
	 * @param array $arValues
	 */
	public function __construct(array $arValues)
	{
		$this->arValues = $arValues;
	}

	/**
	 * Returns any variable by its name. Null if variable is not set.
	 *
	 * @param string $name
	 * @return string | null
	 */
	public function get($name)
	{
		if (array_key_exists($name, $this->arValues))
			return $this->arValues[$name];

		return null;
	}

	/**
	 * Return the current element
	 */
	public function current()
	{
		return current($this->arValues);
	}

	/**
	 * Move forward to next element
	 */
	public function next()
	{
		return next($this->arValues);
	}

	/**
	 * Return the key of the current element
	 */
	public function key()
	{
		return key($this->arValues);
	}

	/**
	 * Checks if current position is valid
	 */
	public function valid()
	{
		$key = $this->key();
		return ($key != null);
	}

	/**
	 * Rewind the Iterator to the first element
	 */
	public function rewind()
	{
		return reset($this->arValues);
	}

	/**
	 * Whether a offset exists
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->arValues);
	}

	/**
	 * Offset to retrieve
	 */
	public function offsetGet($offset)
	{
		if (array_key_exists($offset, $this->arValues))
			return $this->arValues[$offset];

		return false;
	}

	/**
	 * Offset to set
	 */
	public function offsetSet($offset, $value)
	{
		$this->arValues[$offset] = $value;
	}

	/**
	 * Offset to unset
	 */
	public function offsetUnset($offset)
	{
		unset($this->arValues[$offset]);
	}

	/**
	 * Count elements of an object
	 */
	public function count()
	{
		return count($this->arValues);
	}

	/**
	 * Returns values as an array
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->arValues;
	}
}