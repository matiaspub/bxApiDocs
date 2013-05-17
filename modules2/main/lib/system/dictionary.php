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
	static public function __construct(array $arValues)
	{
		$this->arValues = $arValues;
	}

	/**
	 * Returns any variable by its name. Null if variable is not set.
	 *
	 * @param string $name
	 * @return string | null
	 */
	static public function get($name)
	{
		if (array_key_exists($name, $this->arValues))
			return $this->arValues[$name];

		return null;
	}

	/**
	 * Return the current element
	 */
	static public function current()
	{
		return current($this->arValues);
	}

	/**
	 * Move forward to next element
	 */
	static public function next()
	{
		return next($this->arValues);
	}

	/**
	 * Return the key of the current element
	 */
	static public function key()
	{
		return key($this->arValues);
	}

	/**
	 * Checks if current position is valid
	 */
	static public function valid()
	{
		$key = $this->key();
		return ($key != null);
	}

	/**
	 * Rewind the Iterator to the first element
	 */
	static public function rewind()
	{
		return reset($this->arValues);
	}

	/**
	 * Whether a offset exists
	 */
	static public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->arValues);
	}

	/**
	 * Offset to retrieve
	 */
	static public function offsetGet($offset)
	{
		if (array_key_exists($offset, $this->arValues))
			return $this->arValues[$offset];

		return false;
	}

	/**
	 * Offset to set
	 */
	static public function offsetSet($offset, $value)
	{
		$this->arValues[$offset] = $value;
	}

	/**
	 * Offset to unset
	 */
	static public function offsetUnset($offset)
	{
		unset($this->arValues[$offset]);
	}

	/**
	 * Count elements of an object
	 */
	static public function count()
	{
		return count($this->arValues);
	}

	/**
	 * Returns values as an array
	 *
	 * @return array
	 */
	static public function toArray()
	{
		return $this->arValues;
	}
}