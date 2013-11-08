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
	 * @param array $arValues
	 */
	public function __construct(array $arValues, $name = null)
	{
		$this->arValues = $this->arRawValues = $arValues;
		$this->name = $name;
	}

	public function addFilter(IDictionaryFilter $filter)
	{
		$this->arValues = $filter->filterArray($this->arValues, $this->name);
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
		$this->arValues[$offset] = $this->arRawValues[$offset] = $value;
		foreach ($this->arFilters as $filter)
			$this->arValues[$offset] = $filter->filter($this->arValues[$offset], $this->name."[".$offset."]", $this->arValues);
	}

	/**
	 * Offset to unset
	 */
	public function offsetUnset($offset)
	{
		unset($this->arValues[$offset]);
		unset($this->arRawValues[$offset]);
	}
}