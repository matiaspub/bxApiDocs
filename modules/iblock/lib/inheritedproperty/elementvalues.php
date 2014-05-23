<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

class ElementValues extends BaseValues
{
	protected $section_id = 0;
	protected $element_id = 0;

	/**
	 * @param integer $iblock_id Iblock identifier.
	 * @param integer $element_id Element identifier.
	 */
	public function __construct($iblock_id, $element_id)
	{
		parent::__construct($iblock_id);
		$this->element_id = intval($element_id);
	}

	/**
	 * Returns the table name where values will be stored.
	 *
	 * @return string
	 */
	public function getValueTableName()
	{
		return "b_iblock_section_iprop";
	}

	/**
	 * Returns type of the entity which will be stored into DB.
	 *
	 * @return string
	 */
	public function getType()
	{
		return "E";
	}

	/**
	 * Returns unique identifier of the element.
	 *
	 * @return integer
	 */
	public function getId()
	{
		return $this->element_id;
	}

	/**
	 * Creates an entity which will be used to process the templates.
	 *
	 * @return \Bitrix\Iblock\Template\Entity\Base
	 */
	public function  createTemplateEntity()
	{
		return new \Bitrix\Iblock\Template\Entity\Element($this->element_id);
	}

	/**
	 * Sets parent to minimal value from array or to $sectionId.
	 *
	 * @param array[]integer|integer $sectionId Section identifier.
	 *
	 * @return void
	 */
	public function setParents($sectionId)
	{
		if (is_array($sectionId))
		{
			if (!empty($sectionId))
			{
				$sectionId = array_map("intval", $sectionId);
				$this->section_id = min($sectionId);
			}
		}
		else
		{
			$this->section_id = intval($sectionId);
		}
	}

	/**
	 * Returns all the parents of the element which is
	 * array with one element: parent section with minimal identifier or iblock.
	 *
	 * @return array[]\Bitrix\Iblock\InheritedProperty\BaseValues
	 */
	public function getParents()
	{
		$parents = array();
		if ($this->element_id > 0)
		{
			$elementList = \Bitrix\Iblock\ElementTable::getList(array(
				"select" => array("IBLOCK_SECTION_ID"),
				"filter" => array("=ID" => $this->element_id),
			));
			$element = $elementList->fetch();
			if ($element && $element["IBLOCK_SECTION_ID"] > 0)
				$parents[] = new SectionValues($this->iblock_id, $element["IBLOCK_SECTION_ID"]);
			else
				$parents[] = new IblockValues($this->iblock_id);
		}
		elseif ($this->section_id > 0)
		{
			$parents[] = new SectionValues($this->iblock_id, $this->section_id);
		}
		else
		{
			$parents[] = new IblockValues($this->iblock_id);
		}
		return $parents;
	}

	/**
	 * Returns all calculated values of inherited properties
	 * for this element.
	 *
	 * @return array[string]string
	 */
	public function queryValues()
	{
		$result = array();
		if ($this->hasTemplates())
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$query = $connection->query("
				SELECT
					P.ID
					,P.CODE
					,P.TEMPLATE
					,P.ENTITY_TYPE
					,P.ENTITY_ID
					,IP.VALUE
				FROM
					b_iblock_element_iprop IP
					INNER JOIN b_iblock_iproperty P ON P.ID = IP.IPROP_ID
				WHERE
					IP.IBLOCK_ID = ".$this->iblock_id."
					AND IP.ELEMENT_ID = ".$this->element_id."
			");

			while ($row = $query->fetch())
			{
				$result[$row["CODE"]] = $row;
			}

			if (empty($result))
			{
				$result = parent::queryValues();
				if (!empty($result))
				{
					$elementList = \Bitrix\Iblock\ElementTable::getList(array(
						"select" => array("IBLOCK_SECTION_ID"),
						"filter" => array("=ID" => $this->element_id),
					));
					$element = $elementList->fetch();

					foreach ($result as $CODE => $row)
					{
						$connection->add("b_iblock_element_iprop", array(
							"IBLOCK_ID" => $this->iblock_id,
							"SECTION_ID" => intval($element["IBLOCK_SECTION_ID"]),
							"ELEMENT_ID" => $this->element_id,
							"IPROP_ID" => $row["ID"],
							"VALUE" => $row["VALUE"],
						));
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Clears element values DB cache
	 *
	 * @return void
	 */
	function clearValues()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$connection->query("
			DELETE FROM b_iblock_element_iprop
			WHERE IBLOCK_ID = ".$this->iblock_id."
			AND ELEMENT_ID = ".$this->element_id."
		");
	}
}
