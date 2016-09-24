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
	
	/**
	* <p>Метод возвращает идентификатор инфоблока. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/getiblockid.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает название таблицы базы данных для хранения индекса свойств. Нестатический внутренний метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/gettablename.php
	* @author Bitrix
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
	
	/**
	* <p>Метод проверяет существование в базе данных индекса для свойства. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/isexists.php
	* @author Bitrix
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
	
	/**
	* <p>Метод создает новый индекс значений свойства информационного блока. Перед вызовом метода необходимо убедиться, что такой индекс еще не существует. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/create.php
	* @author Bitrix
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
	
	/**
	* <p>Метод удаляет существующий индекс из базы данных. Перед вызовом метода необходимо убедиться в том, что индекс существует. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/drop.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает максимальный идентификатор хранящегося элемента. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/getlaststoredelementid.php
	* @author Bitrix
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
	
	/**
	* <p>Метод добавляет новую запись индекса. Нестатический метод.</p>
	*
	*
	* @param integer $sectionId  Идентификатор секции элемента.
	*
	* @param integer $elementId  Идентификатор элемента.
	*
	* @param integer $facetId  Идентификатор свойства/цены.
	*
	* @param integer $value  Значение словаря или 0.
	*
	* @param float $valueNum  Значение числового свойства или цены.
	*
	* @param boolean $includeSubsections  Параметр принимает <i>true</i>, если секция имеет родителя, в
	* противном случае указывается <i>false</i>.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/addindexentry.php
	* @author Bitrix
	*/
	public function addIndexEntry($sectionId, $elementId, $facetId, $value, $valueNum, $includeSubsections)
	{
		$connection = \Bitrix\Main\Application::getConnection();

		try
		{
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
		}
		catch (\Bitrix\Main\DB\SqlException $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * Deletes all element entries from the index.
	 *
	 * @param integer $elementId Identifier of the element to be deleted.
	 *
	 * @return boolean
	 */
	
	/**
	* <p>Метод удаляет все записи для элемента из индекса. Нестатический метод.</p>
	*
	*
	* @param integer $elementId  Идентификатор элемента, записи которого необходимо удалить.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/deleteindexelement.php
	* @author Bitrix
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
	
	/**
	* <p>Метод преобразует идентификатор свойства инфоблока во внутренний идентификатор фасеты. Метод статический.</p>
	*
	*
	* @param integer $propertyId  Идентификатор свойства инфоблока.
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/propertyidtofacetid.php
	* @author Bitrix
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
	
	/**
	* <p>Метод преобразует идентификатор цены во внутренний идентификатор фасеты. Метод статический.</p>
	*
	*
	* @param integer $priceId  Идентификатор цены.
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/priceidtofacetid.php
	* @author Bitrix
	*/
	public static function priceIdToFacetId($priceId)
	{
		return intval($priceId * 2 + 1);
	}

	/**
	 * Returns true if given identifier is catalog price one.
	 *
	 * @param integer $facetId Internal storage facet identifier.
	 *
	 * @return boolean
	 */
	
	/**
	* <p>Метод возвращает <i>true</i>, если заданный идентификатор является идентификатором цены каталога. Метод статический.</p>
	*
	*
	* @param integer $facetId  Внутренний идентификатор фасеты.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/ispriceid.php
	* @author Bitrix
	*/
	public static function isPriceId($facetId)
	{
		return ($facetId % 2) != 0;
	}

	/**
	 * Returns true if given identifier is iblock property one.
	 *
	 * @param integer $facetId Internal storage facet identifier.
	 *
	 * @return boolean
	 */
	
	/**
	* <p>Метод возвращает <i>true</i>, если заданный идентификатор является идентификатором свойства инфоблока. Метод статический.</p>
	*
	*
	* @param integer $facetId  Внутренний идентификатор фасеты.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/ispropertyid.php
	* @author Bitrix
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
	
	/**
	* <p>Метод преобразует внутренний идентификатор фасеты в идентификатор свойства инфоблока. Метод статический.</p>
	*
	*
	* @param integer $facetId  Внутренний идентификатор фасеты.
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/facetidtopropertyid.php
	* @author Bitrix
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
	
	/**
	* <p>Метод преобразует внутренний идентификатор фасеты в идентификатор цены каталога. Метод статический.</p>
	*
	*
	* @param integer $facetId  Внутренний идентификатор фасеты.
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/storage/facetidtopriceid.php
	* @author Bitrix
	*/
	public static function facetIdToPriceId($facetId)
	{
		return intval(($facetId - 1) / 2);
	}
}
