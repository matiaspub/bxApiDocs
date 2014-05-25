<?php
namespace Bitrix\Main\Type;

use Bitrix\Main\NotSupportedException;

class ParameterDictionary
	extends Dictionary
{
	/**
	 * @var array
	 */
	protected $arRawValues = null;

	protected function setValuesNoDemand(array $values)
	{
		if ($this->arRawValues === null)
			$this->arRawValues = $this->values;
		$this->values = $values;
	}

	/**
	 * Returns original value of any variable by its name. Null if variable is not set.
	 *
	 * @param string $name
	 * @return string | null
	 */
	public function getRaw($name)
	{
		if ($this->arRawValues === null)
		{
			if (isset($this->values[$name]) || array_key_exists($name, $this->values))
				return $this->values[$name];
		}
		else
		{
			if (isset($this->arRawValues[$name]) || array_key_exists($name, $this->arRawValues))
				return $this->arRawValues[$name];
		}

		return null;
	}

	public function toArrayRaw()
	{
		return $this->arRawValues;
	}

	/**
	 * Offset to set
	 */
	static public function offsetSet($offset, $value)
	{
		throw new NotSupportedException("Can not set readonly value");
	}

	/**
	 * Offset to unset
	 */
	static public function offsetUnset($offset)
	{
		throw new NotSupportedException("Can not unset readonly value");
	}
}