<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

/**
 * Class Base
 *
 * @package Bitrix\Iblock\Template\Entity
 */
class Base
{
	/** @var integer  */
	protected $id = null;
	/** @var array[string]mixed  */
	protected $fields = null;
	/** @var array[string]string  */
	protected $fieldMap = array();

	/**
	 * @param integer $id Entity identifier.
	 */
	public function __construct($id)
	{
		$this->id = $id;
	}

	/**
	 * Used to find entity for template processing.
	 *
	 * @param string $entity What to find.
	 *
	 * @return \Bitrix\Iblock\Template\Entity\Base
	 */
	public function resolve($entity)
	{
		if ($entity === "this")
			return $this;
		else
			return new Base(0);
	}

	/**
	 * Used to initialize entity fields from some external source.
	 *
	 * @param array $fields Entity fields.
	 *
	 * @return void
	 */
	public function setFields(array $fields)
	{
		$this->fields = $fields;
	}

	/**
	 * Returns field value.
	 *
	 * @param string $fieldName Name of the field to retrieve data from.
	 *
	 * @return string
	 */
	public function getField($fieldName)
	{
		if (!$this->loadFromDatabase())
			return "";

		if (!isset($this->fieldMap[$fieldName]))
			return "";

		$fieldName = $this->fieldMap[$fieldName];
		if (!isset($this->fields[$fieldName]))
			return "";

		$fieldValue = $this->fields[$fieldName];
		if (is_array($fieldValue))
		{
			$result = array();
			foreach($fieldValue as $key => $value)
			{
				if ($value instanceof LazyValueLoader)
					$result[$key] = $value->getValue();
				else
					$result[$key] = $value;

			}
			return $result;
		}
		else
		{
			if ($fieldValue instanceof LazyValueLoader)
			{
				return $fieldValue->getValue();
			}
			return $this->fields[$fieldName];
		}
	}

	/**
	 * Loads values from database.
	 * Returns true on success.
	 *
	 * @return boolean
	 */
	protected function loadFromDatabase()
	{
		if (!isset($this->fields))
		{
			$this->fields = array();
		}
		return true;
	}

	/**
	 * Sets new field value only when is not set yet.
	 * Adds mapping from field name to it's internal presentation.
	 *
	 * @param string $fieldName The name of the field.
	 * @param string $internalName Internal name of the field.
	 * @param string $value Value to be stored.
	 *
	 * @return void
	 */
	protected function addField($fieldName, $internalName, $value)
	{
		if (!isset($this->fields[$internalName]))
			$this->fields[$internalName] = $value;
		$this->fieldMap[strtolower($fieldName)] = $internalName;
	}
}

/**
 * Class LazyValueLoader
 * Strategy class used for delaying queries to DB.
 *
 * @package Bitrix\Iblock\Template\Entity
 */
class LazyValueLoader
{
	protected $value = null;
	protected $key = null;

	/**
	 * @param string|integer $key Unique identifier.
	 */
	function __construct($key)
	{
		$this->key = $key;
	}

	/**
	 * Calls load method if value was not loaded yet.
	 *
	 * @return mixed
	 */
	public function __toString()
	{
		if (!isset($this->value))
			$this->value = $this->load();
		return $this->value;
	}

	/**
	 * Calls load method if value was not loaded yet.
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		if (!isset($this->value))
			$this->value = $this->load();
		return $this->value;
	}

	/**
	 * Actual work method which have to retrieve data from the DB.
	 *
	 * @return mixed
	 */
	protected function load()
	{
		return "";
	}
}

