<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class ElementSkuProperty extends Base
{
	protected $ids = null;
	protected $iblockId = 0;
	protected $properties = array();

	/**
	 * @param array|mixed $ids Array of iblock element identifiers.
	 */
	public function __construct($ids)
	{
		parent::__construct(0);
		$this->ids = $ids;
	}

	/**
	 * Set the iblock of the elements.
	 *
	 * @param integer $iblockId Iblock identifier.
	 *
	 * @return void
	 */
	public function setIblockId($iblockId)
	{
		$this->iblockId = intval($iblockId);
	}

	/**
	 * Loads values from database.
	 * Returns true on success.
	 *
	 * @return boolean
	 */
	protected function loadFromDatabase()
	{
		if (!isset($this->fields) && $this->iblockId > 0 && is_array($this->ids))
		{
			$this->fields = array();
			foreach($this->ids as $id)
			{
				if ($id > 0)
				{
					$propertyList = \CIBlockElement::getProperty(
						$this->iblockId,
						$id,
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
							$value = new ElementPropertyElement($property["VALUE"]);
						}
						elseif ($property["PROPERTY_TYPE"] === "G")
						{
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

						$this->fields[$property["ID"]][] = $value;
						$this->fieldMap[$property["ID"]] = $property["ID"];
						if ($property["CODE"] != "")
							$this->fieldMap[strtolower($property["CODE"])] = $property["ID"];
					}
				}
			}
		}
		return is_array($this->fields);
	}
}
