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
	
	/**
	* <p>Метод возвращает название таблицы, в которой будут сохранены значения наследуемых вычисляемых свойств. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/sectionvalues/getvaluetablename.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает тип сущности, который будет храниться в базе данных. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/sectionvalues/gettype.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает уникальный идентификатор раздела. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/sectionvalues/getid.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает массив всех родителей секции, где в качестве значений массива выступает родительская секция или инфоблок. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Iblock\InheritedProperty\array[]\Bitrix\Iblock\InheritedProperty\BaseValues 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/sectionvalues/getparents.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает все вычисленные значения наследуемых свойств для секции. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Iblock\InheritedProperty\array[string]string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/sectionvalues/queryvalues.php
	* @author Bitrix
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
				$sqlHelper = $connection->getSqlHelper();
				$result = parent::queryValues();
				foreach ($result as $row)
				{
					$mergeSql = $sqlHelper->prepareMerge(
						"b_iblock_section_iprop",
						array(
							"SECTION_ID",
							"IPROP_ID",
						),
						array(
							"IBLOCK_ID" => $this->iblockId,
							"SECTION_ID" => $this->sectionId,
							"IPROP_ID" => $row["ID"],
							"VALUE" => $row["VALUE"],
						),
						array(
							"IBLOCK_ID" => $this->iblockId,
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
		return $result;
	}

	/**
	 * Clears section values DB cache
	 *
	 * @return void
	 */
	
	/**
	* <p>Метод очищает значения свойств для разделов из кеша базы данных. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/sectionvalues/clearvalues.php
	* @author Bitrix
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
