<?php
namespace Bitrix\Main\Type;

class FilterableDictionary
	extends Dictionary
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $arRawValues = array();

	/**
	 * @var IDictionaryFilter[]
	 */
	protected $arFilters = array();

	/**
	 * Creates object.
	 *
	 * @param array $values
	 */
	public function __construct(array $values, $name = null)
	{
		$this->values = $this->arRawValues = $values;
		$this->name = $name;
	}

	public function addFilter(IDictionaryFilter $filter)
	{
		$this->values = $filter->filterArray($this->values, $this->name);
		$this->arFilters[] = $filter;
	}

	/**
	 * Returns original value of any variable by its name. Null if variable is not set.
	 *
	 * @param string $name
	 * @return string | null
	 */
	public function getRaw($name)
	{
		if (isset($this->arRawValues[$name]) || array_key_exists($name, $this->arRawValues))
			return $this->arRawValues[$name];

		return null;
	}

	/**
	 * Offset to set
	 */
	public function offsetSet($offset, $value)
	{
		$this->values[$offset] = $this->arRawValues[$offset] = $value;
		foreach ($this->arFilters as $filter)
			$this->values[$offset] = $filter->filter($this->values[$offset], $this->name."[".$offset."]", $this->values);
	}

	/**
	 * Offset to unset
	 */
	public function offsetUnset($offset)
	{
		unset($this->values[$offset]);
		unset($this->arRawValues[$offset]);
	}
}