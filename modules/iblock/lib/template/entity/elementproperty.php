<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class ElementProperty extends Base
{
	protected $iblock_id = 0;
	protected $properties = array();
	protected $element_link_properties = array();
	protected $section_link_properties = array();
	/**
	 * @param integer $id Iblock element identifier.
	 */
	public function __construct($id)
	{
		parent::__construct($id);
	}

	/**
	 * Set the iblock of the element.
	 *
	 * @param integer $iblockId Iblock identifier.
	 *
	 * @return void
	 */
	public function setIblockId($iblockId)
	{
		$this->iblock_id = intval($iblockId);
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
		if ($this->loadFromDatabase())
		{
			if (isset($this->element_link_properties[$entity]))
			{
				if (!is_object($this->element_link_properties[$entity]))
					$this->element_link_properties[$entity] = new Element($this->element_link_properties[$entity]);
				return $this->element_link_properties[$entity];
			}
			elseif (isset($this->section_link_properties[$entity]))
			{
				if (!is_object($this->section_link_properties[$entity]))
					$this->section_link_properties[$entity] = new Element($this->section_link_properties[$entity]);
				return $this->section_link_properties[$entity];
			}
		}
		return parent::resolve($entity);
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
		parent::setFields($fields);
		if (
			is_array($this->fields)
			&& $this->iblock_id > 0
		)
		{
			$properties = array();
			$propertyList = \Bitrix\Iblock\PropertyTable::getList(array(
				"select" => array("*"),
				"filter" => array("=IBLOCK_ID" => $this->iblock_id),
			));
			while ($row = $propertyList->fetch())
			{
				if ($row["USER_TYPE_SETTINGS"])
					$row["USER_TYPE_SETTINGS"] = unserialize($row["USER_TYPE_SETTINGS"]);

				$properties[$row["ID"]] = $row;
				if ($row["CODE"] != "")
					$properties[$row["CODE"]] = &$properties[$row["ID"]];
			}

			foreach ($fields as $propertyCode => $propertyValues)
			{
				if (is_array($propertyValues))
				{
					foreach ($propertyValues as $i => $propertyValue)
					{
						if (is_array($propertyValue) && array_key_exists("VALUE", $propertyValue))
						{
							if ($propertyValue["VALUE"] != "")
								$propertyValues[$i] = $propertyValue["VALUE"];
							else
								unset($propertyValues[$i]);
						}
					}
				}

				if (isset($properties[$propertyCode]))
				{
					$property = $properties[$propertyCode];
					$fieldCode = strtolower($propertyCode);

					if ($property["PROPERTY_TYPE"] === "L")
					{
						if (is_numeric($propertyValues))
						{
							$value = new ElementPropertyEnum($propertyValues);
						}
						elseif (is_array($propertyValues))
						{
							$value = array();
							foreach ($propertyValues as $propertyValue)
							{
								if (is_numeric($propertyValue))
									$value[] = new ElementPropertyEnum($propertyValue);
							}
						}
						else
						{
							$value = $propertyValues;
						}
					}
					elseif ($property["PROPERTY_TYPE"] === "E")
					{
						if ($propertyValues instanceof Element)
						{
							$this->element_link_properties[$fieldCode] = $propertyValues;
							$value = $propertyValues->getField("name");
						}
						elseif (is_numeric($propertyValues))
						{
							$this->element_link_properties[$fieldCode] = $propertyValues;
							$value = new ElementPropertyElement($propertyValues);
						}
						else
						{
							$value = $propertyValues;
						}
					}
					elseif ($property["PROPERTY_TYPE"] === "G")
					{
						if ($propertyValues instanceof Section)
						{
							$this->section_link_properties[$fieldCode] = $propertyValues;
							$value = $propertyValues->getField("name");
						}
						elseif (is_numeric($propertyValues))
						{
							$this->section_link_properties[$fieldCode] = $propertyValues;
							$value = new ElementPropertySection($propertyValues);
						}
						else
						{
							$value = $propertyValues;
						}
					}
					else
					{
						if(strlen($property["USER_TYPE"]))
						{
							$value = new ElementPropertyUserField($propertyValues, $property);
						}
						else
						{
							$value = $propertyValues;
						}
					}

					$this->fieldMap[$fieldCode] = $property["ID"];
					$this->fieldMap[$property["ID"]] = $property["ID"];
					if ($property["CODE"] != "")
						$this->fieldMap[strtolower($property["CODE"])] = $property["ID"];

					$this->fields[$property["ID"]] = $value;
				}
			}
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
		if (!isset($this->fields) && $this->iblock_id > 0)
		{
			$this->fields = array();
			$this->fieldMap = array();

			$propertyList = \CIBlockElement::getProperty(
				$this->iblock_id,
				$this->id,
				array("sort" => "asc"),
				array("EMPTY" => "N")
			);
			while ($property = $propertyList->fetch())
			{
				if ($property["VALUE_ENUM"] != "")
				{
					$value = $property["VALUE_ENUM"];
				}
				elseif ($property["PROPERTY_TYPE"] === "E")
				{
					$this->element_link_properties[$property["ID"]] = $property["VALUE"];
					if ($property["CODE"] != "")
						$this->element_link_properties[strtolower($property["CODE"])] = $property["VALUE"];
					$value = new ElementPropertyElement($property["VALUE"]);
				}
				elseif ($property["PROPERTY_TYPE"] === "G")
				{
					$this->section_link_properties[$property["ID"]] = $property["VALUE"];
					if ($property["CODE"] != "")
						$this->section_link_properties[strtolower($property["CODE"])] = $property["VALUE"];
					$value = new ElementPropertySection($property["VALUE"]);
				}
				else
				{
					if(strlen($property["USER_TYPE"]))
					{
						$value = new ElementPropertyUserField($property["VALUE"], $property);
					}
					else
					{
						$value = $property["VALUE"];
					}
				}

				$this->fieldMap[$property["ID"]] = $property["ID"];
				if ($property["CODE"] != "")
					$this->fieldMap[strtolower($property["CODE"])] = $property["ID"];
				
				if ($property["MULTIPLE"] == "Y")
					$this->fields[$property["ID"]][] = $value;
				else
					$this->fields[$property["ID"]] = $value;
			}
		}
		return is_array($this->fields);
	}
}

class ElementPropertyUserField extends LazyValueLoader
{
	/** @var array  */
	private $property = null;
	private $propertyFormatFunction = null;

	/**
	 * @param integer $key  Iblock element identifier.
	 * @param array|mixed $property Iblock property array.
	 */
	function __construct($key, $property)
	{
		parent::__construct($key);
		if (is_array(($property)))
		{
			$this->property = $property;
			if(strlen($property["USER_TYPE"]))
			{
				$propertyUserType = \CIBlockProperty::GetUserType($property["USER_TYPE"]);
				if(
					array_key_exists("GetPublicViewHTML", $propertyUserType)
					&& is_callable($propertyUserType["GetPublicViewHTML"])
				)
				{
					$this->propertyFormatFunction = $propertyUserType["GetPublicViewHTML"];
				}
			}
		}
	}
	/**
	 * Actual work method which have to retrieve data from the DB.
	 *
	 * @return mixed
	 */
	protected function load()
	{
		if ($this->propertyFormatFunction)
		{
			return call_user_func_array($this->propertyFormatFunction,
				array(
					$this->property,
					array("VALUE" => $this->key),
					array("MODE" => "ELEMENT_TEMPLATE"),
				)
			);
		}
		else
		{
			return $this->key;
		}
	}
}

class ElementPropertyEnum extends LazyValueLoader
{
	/**
	 * Actual work method which have to retrieve data from the DB.
	 *
	 * @return mixed
	 */
	protected function load()
	{
		$enumList = \Bitrix\Iblock\PropertyEnumerationTable::getList(array(
			"select" => array("VALUE"),
			"filter" => array("=ID" => $this->key),
		));
		$enum = $enumList->fetch();
		if ($enum)
			return $enum["VALUE"];
		else
			return "";
	}
}

class ElementPropertyElement extends LazyValueLoader
{
	/**
	 * Actual work method which have to retrieve data from the DB.
	 *
	 * @return mixed
	 */
	protected function load()
	{
		$elementList = \Bitrix\Iblock\ElementTable::getList(array(
			"select" => array("NAME"),
			"filter" => array("=ID" => $this->key),
		));
		$element = $elementList->fetch();
		if ($element)
			return $element["NAME"];
		else
			return "";
	}
}

class ElementPropertySection extends LazyValueLoader
{
	/**
	 * Actual work method which have to retrieve data from the DB.
	 *
	 * @return mixed
	 */
	protected function load()
	{
		$sectionList = \Bitrix\Iblock\SectionTable::getList(array(
			"select" => array("NAME"),
			"filter" => array("=ID" => $this->key),
		));
		$section = $sectionList->fetch();
		if ($section)
			return $section["NAME"];
		else
			return "";
	}
}