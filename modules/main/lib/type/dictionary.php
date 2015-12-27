<?php
namespace Bitrix\Main\Type;

class Dictionary
	implements \ArrayAccess, \Iterator, \Countable
{
	/**
	 * @var array
	 */
	protected $values = array();

	/**
	 * Creates object.
	 *
	 * @param array $values
	 */
	public function __construct(array $values = null)
	{
		if($values !== null)
		{
			$this->values = $values;
		}
	}

	/**
	 * Returns any variable by its name. Null if variable is not set.
	 *
	 * @param string $name
	 * @return string | null
	 */
	public function get($name)
	{
		// this condition a bit faster
		// it is possible to omit array_key_exists here, but for uniformity...
		if (isset($this->values[$name]) || array_key_exists($name, $this->values))
		{
			return $this->values[$name];
		}

		return null;
	}

	public function set(array $values)
	{
		$this->values = $values;
	}

	public function clear()
	{
		$this->values = array();
	}

	/**
	 * Return the current element
	 */
	public function current()
	{
		return current($this->values);
	}

	/**
	 * Move forward to next element
	 */
	public function next()
	{
		return next($this->values);
	}

	/**
	 * Return the key of the current element
	 */
	public function key()
	{
		return key($this->values);
	}

	/**
	 * Checks if current position is valid
	 */
	public function valid()
	{
		return ($this->key() !== null);
	}

	/**
	 * Rewind the Iterator to the first element
	 */
	public function rewind()
	{
		return reset($this->values);
	}

	/**
	 * Whether a offset exists
	 */
	public function offsetExists($offset)
	{
		return isset($this->values[$offset]) || array_key_exists($offset, $this->values);
	}

	/**
	 * Offset to retrieve
	 */
	public function offsetGet($offset)
	{
		if (isset($this->values[$offset]) || array_key_exists($offset, $this->values))
		{
			return $this->values[$offset];
		}

		return null;
	}

	/**
	 * Offset to set
	 */
	public function offsetSet($offset, $value)
	{
		if($offset === null)
		{
			$this->values[] = $value;
		}
		else
		{
			$this->values[$offset] = $value;
		}
	}

	/**
	 * Offset to unset
	 */
	public function offsetUnset($offset)
	{
		unset($this->values[$offset]);
	}

	/**
	 * Count elements of an object
	 */
	public function count()
	{
		return count($this->values);
	}

	/**
	 * Returns the values as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->values;
	}

	/**
	 * Returns true if the dictionary is empty.
	 * @return bool
	 */
	public function isEmpty()
	{
		return empty($this->values);
	}
}
