<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\PropertyIndex;

class Storage
{
	protected $iblockId = 0;
	protected static $exists = array();

	const PRICE = 1;
	const DICTIONARY = 2;
	const STRING = 3;
	const NUMERIC = 4;
	const DATETIME = 5;

	/**
	 * @param integer $iblockId Information block identifier.
	 */
	public function __construct($iblockId)
	{
		$this->iblockId = intval($iblockId);
	}

	/**
	 * Returns information block identifier.
	 *
	 * @return integer
	 */
	public function getIblockId()
	{
		return $this->iblockId;
	}

	/**
	 * Internal method to get database table name for storing property index.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return "b_iblock_".$this->iblockId."_index";
	}

	/**
	 * Checks if property index exists in the database.
	 * Returns true on success.
	 *
	 * @return boolean
	 */
	public function isExists()
	{
		if (!array_key_exists($this->iblockId, self::$exists))
		{
			$connection = \Bitrix\Main\Application::getConnection();
			self::$exists[$this->iblockId] = $connection->isTableExists($this->getTableName());
		}

		return self::$exists[$this->iblockId];
	}

	/**
	 * Creates new property values index for information block.
	 * You have to be sure that index does not exists.
	 *
	 * @return void
	 */
	public function create()
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$connection->createTable($this->getTableName(), array(
			"SECTION_ID" => new \Bitrix\Main\Entity\IntegerField("SECTION_ID", array(
				'required' => true,
			)),
			"ELEMENT_ID" => new \Bitrix\Main\Entity\IntegerField("ELEMENT_ID", array(
				'required' => true,
			)),
			"FACET_ID" => new \Bitrix\Main\Entity\IntegerField("FACET_ID", array(
				'required' => true,
			)),
			"VALUE" => new \Bitrix\Main\Entity\IntegerField("VALUE", array(
				'required' => true,
			)),
			"VALUE_NUM" => new \Bitrix\Main\Entity\FloatField("VALUE_NUM", array(
				'required' => true,
			)),
			"INCLUDE_SUBSECTIONS" => new \Bitrix\Main\Entity\BooleanField("INCLUDE_SUBSECTIONS", array(
				'required' => true,
				'values' => array(0, 1),
			)),
		), array("SECTION_ID", "FACET_ID", "VALUE", "VALUE_NUM", "ELEMENT_ID"));

		$connection->createIndex($this->getTableName(), 'IX_'.$this->getTableName().'_0', array("SECTION_ID", "FACET_ID", "VALUE_NUM", "VALUE", "ELEMENT_ID"));
		$connection->createIndex($this->getTableName(), 'IX_'.$this->getTableName().'_1', array("ELEMENT_ID", "SECTION_ID", "FACET_ID"));

		self::$exists[$this->iblockId] = true;
	}

	/**
	 * Deletes existing index from the database.
	 * You have to check that index exists before calling this method.
	 *
	 * @return void
	 */
	public function drop()
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$connection->dropTable($this->getTableName());

		self::$exists[$this->iblockId] = false;
	}

	/**
	 * Returns maximum stored element identifier.
	 *
	 * @return int
	 */
	public function getLastStoredElementId()
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$max =  $connection->queryScalar("select max(ELEMENT_ID) ELEMENT_MAX from ".$this->getTableName());

		return $max > 0? $max: 0;
	}

	/**
	 * Adds new index entry.
	 *
	 * @param integer $sectionId Identifier of the element section.
	 * @param integer $elementId Identifier of the element.
	 * @param integer $facetId   Identifier of the property/price.
	 * @param integer $value     Dictionary value or 0.
	 * @param float   $valueNum  Value of an numeric property or price.
	 * @param boolean $includeSubsections If section has parent or direct element connection.
	 *
	 * @return boolean
	 */
	public function addIndexEntry($sectionId, $elementId, $facetId, $value, $valueNum, $includeSubsections)
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$connection->query("
			INSERT INTO ".$this->getTableName()." (
				SECTION_ID
				,ELEMENT_ID
				,FACET_ID
				,VALUE
				,VALUE_NUM
				,INCLUDE_SUBSECTIONS
			) VALUES (
				".intval($sectionId)."
				,".intval($elementId)."
				,".intval($facetId)."
				,".intval($value)."
				,".doubleval($valueNum)."
				,".($includeSubsections > 0? 1: 0)."
			)
		");

		return true;
	}

	/**
	 * Deletes all element entries from the index.
	 *
	 * @param integer $elementId Identifier of the element to be deleted.
	 *
	 * @return boolean
	 */
	public function deleteIndexElement($elementId)
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$connection->query("DELETE from ".$this->getTableName()." WHERE ELEMENT_ID = ".intval($elementId));

		return true;
	}

	/**
	 * Converts iblock property identifier into internal storage facet identifier.
	 *
	 * @param integer $propertyId Property identifier.
	 * @return integer
	 */
	public static function propertyIdToFacetId($propertyId)
	{
		return intval($propertyId * 2);
	}

	/**
	 * Converts catalog price identifier into internal storage facet identifier.
	 *
	 * @param integer $priceId Price identifier.
	 * @return integer
	 */
	public static function priceIdToFacetId($priceId)
	{
		return intval($priceId * 2 + 1);
	}

	/**
	 * Returns true if given identifier is iblock property one.
	 *
	 * @param integer $facetId Internal storage facet identifier.
	 *
	 * @return boolean
	 */
	public static function isPriceId($facetId)
	{
		return ($facetId % 2) != 0;
	}

	/**
	 * Returns true if given identifier is catalog price one.
	 *
	 * @param integer $facetId Internal storage facet identifier.
	 *
	 * @return boolean
	 */
	public static function isPropertyId($facetId)
	{
		return ($facetId % 2) == 0;
	}

	/**
	 * Converts internal storage facet identifier into iblock property identifier.
	 *
	 * @param integer $facetId Internal storage facet identifier.
	 *
	 * @return integer
	 */
	public static function facetIdToPropertyId($facetId)
	{
		return intval($facetId / 2);
	}

	/**
	 * Converts internal storage facet identifier into catalog price identifier.
	 *
	 * @param integer $facetId Internal storage facet identifier.
	 *
	 * @return integer
	 */
	public static function facetIdToPriceId($facetId)
	{
		return intval(($facetId - 1) / 2);
	}
}
