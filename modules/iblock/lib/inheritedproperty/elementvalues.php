<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

class ElementValues extends BaseValues
{
	protected $sectionId = 0;
	protected $elementId = 0;

	/**
	 * @param integer $iblockId Iblock identifier.
	 * @param integer $elementId Element identifier.
	 */
	public function __construct($iblockId, $elementId)
	{
		parent::__construct($iblockId);
		$this->elementId = intval($elementId);
	}

	/**
	 * Returns the table name where values will be stored.
	 *
	 * @return string
	 */
	static public function getValueTableName()
	{
		return "b_iblock_section_iprop";
	}

	/**
	 * Returns type of the entity which will be stored into DB.
	 *
	 * @return string
	 */
	static public function getType()
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
		return $this->elementId;
	}

	/**
	 * Creates an entity which will be used to process the templates.
	 *
	 * @return \Bitrix\Iblock\Template\Entity\Base
	 */
	public function  createTemplateEntity()
	{
		return new \Bitrix\Iblock\Template\Entity\Element($this->elementId);
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
				$this->sectionId = min($sectionId);
			}
		}
		else
		{
			$this->sectionId = intval($sectionId);
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
		if ($this->elementId > 0)
		{
			$elementList = \Bitrix\Iblock\ElementTable::getList(array(
				"select" => array("IBLOCK_SECTION_ID"),
				"filter" => array("=ID" => $this->elementId),
			));
			$element = $elementList->fetch();
			if ($element && $element["IBLOCK_SECTION_ID"] > 0)
				$parents[] = new SectionValues($this->iblockId, $element["IBLOCK_SECTION_ID"]);
			else
				$parents[] = new IblockValues($this->iblockId);
		}
		elseif ($this->sectionId > 0)
		{
			$parents[] = new SectionValues($this->iblockId, $this->sectionId);
		}
		else
		{
			$parents[] = new IblockValues($this->iblockId);
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
					IP.IBLOCK_ID = ".$this->iblockId."
					AND IP.ELEMENT_ID = ".$this->elementId."
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
						"filter" => array("=ID" => $this->elementId),
					));
					$element = $elementList->fetch();
					$element['IBLOCK_SECTION_ID'] = (int)$element['IBLOCK_SECTION_ID'];

					$sqlHelper = $connection->getSqlHelper();
					foreach ($result as $CODE => $row)
					{
						$mergeSql = $sqlHelper->prepareMerge(
							"b_iblock_element_iprop",
							array(
								"ELEMENT_ID",
								"IPROP_ID",
							),
							array(
								"IBLOCK_ID" => $this->iblockId,
								"SECTION_ID" => $element["IBLOCK_SECTION_ID"],
								"ELEMENT_ID" => $this->elementId,
								"IPROP_ID" => $row["ID"],
								"VALUE" => $row["VALUE"],
							),
							array(
								"IBLOCK_ID" => $this->iblockId,
								"SECTION_ID" => $element["IBLOCK_SECTION_ID"],
								"VALUE" => $row["VALUE"],
							)
						);
						foreach ($mergeSql as $sql)
						{
							$connection->query($sql);
						}
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
	public function clearValues()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$connection->query("
			DELETE FROM b_iblock_element_iprop
			WHERE IBLOCK_ID = ".$this->iblockId."
			AND ELEMENT_ID = ".$this->elementId."
		");
	}
}
