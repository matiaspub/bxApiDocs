<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

class IblockValues extends BaseValues
{
	/**
	 * @param integer $iblockId Iblock identifier.
	 */
	static public function __construct($iblockId)
	{
		parent::__construct($iblockId);
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/iblockvalues/getvaluetablename.php
	* @author Bitrix
	*/
	static public function getValueTableName()
	{
		return "b_iblock_iblock_iprop";
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/iblockvalues/gettype.php
	* @author Bitrix
	*/
	static public function getType()
	{
		return "B";
	}

	/**
	 * Returns unique identifier of the iblock.
	 *
	 * @return integer
	 */
	
	/**
	* <p>Метод возвращает уникальный идентификатор инфоблока. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/iblockvalues/getid.php
	* @author Bitrix
	*/
	public function getId()
	{
		return $this->iblockId;
	}

	/**
	 * Creates an entity which will be used to process the templates.
	 *
	 * @return \Bitrix\Iblock\Template\Entity\Base
	 */
	public function  createTemplateEntity()
	{
		return new \Bitrix\Iblock\Template\Entity\Iblock($this->iblockId);
	}

	/**
	 * Returns all the parents of the iblock which is empty array.
	 *
	 * @return array[]\Bitrix\Iblock\InheritedProperty\BaseValues
	 */
	
	/**
	* <p>Метод возвращает пустой массив родителей инфоблока. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Iblock\InheritedProperty\array[]\Bitrix\Iblock\InheritedProperty\BaseValues 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/iblockvalues/getparents.php
	* @author Bitrix
	*/
	static public function getParents()
	{
		return array();
	}

	/**
	 * Returns all calculated values of inherited properties
	 * for iblock.
	 *
	 * @return array[string]string
	 */
	
	/**
	* <p>Метод возвращает все вычисленные значения наследуемых свойств для инфоблока. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Iblock\InheritedProperty\array[string]string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/iblockvalues/queryvalues.php
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
					b_iblock_iblock_iprop IP
					INNER JOIN b_iblock_iproperty P ON P.ID = IP.IPROP_ID
				WHERE
					IP.IBLOCK_ID = ".$this->iblockId."
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
					$connection->add("b_iblock_iblock_iprop", array(
						"IBLOCK_ID" => $this->iblockId,
						"IPROP_ID" => $row["ID"],
						"VALUE" => $row["VALUE"],
					), null);
				}
			}
		}
		return $result;
	}

	/**
	 * Clears iblock values DB cache
	 *
	 * @return void
	 */
	
	/**
	* <p>Метод очищает значения свойств для инфоблока из кеша базы данных. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/iblockvalues/clearvalues.php
	* @author Bitrix
	*/
	public function clearValues()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$connection->query("
			DELETE FROM b_iblock_element_iprop
			WHERE IBLOCK_ID = ".$this->iblockId."
		");
		$connection->query("
			DELETE FROM b_iblock_section_iprop
			WHERE IBLOCK_ID = ".$this->iblockId."
		");
		$connection->query("
			DELETE FROM b_iblock_iblock_iprop
			WHERE IBLOCK_ID = ".$this->iblockId."
		");
	}
}
