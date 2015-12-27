<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

class SectionValues extends BaseValues
{
	protected $sectionId = 0;

	/**
	 * @param integer $iblockId Iblock identifier.
	 * @param integer $sectionId Section identifier.
	 */
	public function __construct($iblockId, $sectionId)
	{
		parent::__construct($iblockId);
		$this->sectionId = intval($sectionId);
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
		return "S";
	}

	/**
	 * Returns unique identifier of the section.
	 *
	 * @return integer
	 */
	public function getId()
	{
		return $this->sectionId;
	}

	/**
	 * Creates an entity which will be used to process the templates.
	 *
	 * @return \Bitrix\Iblock\Template\Entity\Base
	 */
	public function  createTemplateEntity()
	{
		return new \Bitrix\Iblock\Template\Entity\Section($this->sectionId);
	}

	/**
	 * Returns all the parents of the section which is
	 * array with one element: parent section or iblock.
	 *
	 * @return array[]\Bitrix\Iblock\InheritedProperty\BaseValues
	 */
	public function getParents()
	{
		$parents = array();
		$sectionList = \Bitrix\Iblock\SectionTable::getList(array(
			"select" => array("IBLOCK_SECTION_ID"),
			"filter" => array("=ID" => $this->sectionId),
		));
		$section = $sectionList->fetch();
		if ($section && $section["IBLOCK_SECTION_ID"] > 0)
			$parents[] = new SectionValues($this->iblockId, $section["IBLOCK_SECTION_ID"]);
		else
			$parents[] = new IblockValues($this->iblockId);
		return $parents;
	}

	/**
	 * Returns all calculated values of inherited properties
	 * for this section.
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
					b_iblock_section_iprop IP
					INNER JOIN b_iblock_iproperty P ON P.ID = IP.IPROP_ID
				WHERE
					IP.IBLOCK_ID = ".$this->iblockId."
					AND IP.SECTION_ID = ".$this->sectionId."
			");

			while ($row = $query->fetch())
			{
				$result[$row["CODE"]] = $row;
			}

			if (empty($result))
			{
				$result = parent::queryValues();
				foreach ($result as $row)
				{
					$connection->add("b_iblock_section_iprop", array(
						"IBLOCK_ID" => $this->iblockId,
						"SECTION_ID" => $this->sectionId,
						"IPROP_ID" => $row["ID"],
						"VALUE" => $row["VALUE"],
					), null);
				}
			}
		}
		return $result;
	}

	/**
	 * Clears section values DB cache
	 *
	 * @return void
	 */
	public function clearValues()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$sectionList = \Bitrix\Iblock\SectionTable::getList(array(
			"select" => array("LEFT_MARGIN", "RIGHT_MARGIN"),
			"filter" => array("=ID" => $this->sectionId),
		));
		$section = $sectionList->fetch();
		if ($section)
		{
			$connection->query("
				DELETE FROM b_iblock_element_iprop
				WHERE IBLOCK_ID = ".$this->iblockId."
				AND ELEMENT_ID in (
					SELECT BSE.IBLOCK_ELEMENT_ID
					FROM b_iblock_section_element BSE
					INNER JOIN b_iblock_section BS ON BSE.IBLOCK_SECTION_ID = BS.ID AND BSE.ADDITIONAL_PROPERTY_ID IS NULL
					WHERE BS.IBLOCK_ID = ".$this->iblockId."
					AND BS.LEFT_MARGIN <= ".$section["RIGHT_MARGIN"]."
					AND BS.RIGHT_MARGIN >= ".$section["LEFT_MARGIN"]."
				)
			");
			$connection->query("
				DELETE FROM b_iblock_section_iprop
				WHERE IBLOCK_ID = ".$this->iblockId."
				AND SECTION_ID in (
					SELECT BS.ID
					FROM b_iblock_section BS
					WHERE BS.IBLOCK_ID = ".$this->iblockId."
					AND BS.LEFT_MARGIN <= ".$section["RIGHT_MARGIN"]."
					AND BS.RIGHT_MARGIN >= ".$section["LEFT_MARGIN"]."
				)
			");
		}
	}
}
