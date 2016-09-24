<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

abstract class BaseValues
{
	/** @var integer */
	protected $iblockId = null;

	/** @var array[string][string]string */
	protected $values = false;

	/**
	 * @param integer $iblockId Iblock identifier.
	 */
	public function __construct($iblockId)
	{
		$this->iblockId = intval($iblockId);
	}

	/**
	 * Returns the identifier of the iblock of the entity.
	 *
	 * @return integer
	 */
	
	/**
	* <p>Метод возвращает идентификатор инфоблока для сущности. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/basevalues/getiblockid.php
	* @author Bitrix
	*/
	public function getIblockId()
	{
		return $this->iblockId;
	}

	/**
	 * Returns the table name where values will be stored.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы, в которой будут сохранены значения шаблонов. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/basevalues/getvaluetablename.php
	* @author Bitrix
	*/
	static public function getValueTableName()
	{
		return "";
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/basevalues/gettype.php
	* @author Bitrix
	*/
	abstract public function getType();

	/**
	 * Returns unique identifier of the entity
	 *
	 * @return integer
	 */
	
	/**
	* <p>Метод возвращает уникальный идентификатор сущности. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/basevalues/getid.php
	* @author Bitrix
	*/
	abstract public function getId();

	/**
	 * Creates an entity which will be used to process the templates.
	 *
	 * @return \Bitrix\Iblock\Template\Entity\Base
	 */
	abstract public function  createTemplateEntity();

	/**
	 * Returns all the parents of the entity.
	 *
	 * @return array[]\Bitrix\Iblock\InheritedProperty\BaseValues
	 */
	
	/**
	* <p>Метод возвращает всех родителей для сущности. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Iblock\InheritedProperty\array[]\Bitrix\Iblock\InheritedProperty\BaseValues 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/basevalues/getparents.php
	* @author Bitrix
	*/
	static public function getParents()
	{
		return array();
	}

	/**
	 * Returns it's first parent if exists one.
	 * Otherwise returns null.
	 *
	 * @return \Bitrix\Iblock\InheritedProperty\BaseValues|null
	 */
	
	/**
	* <p>Метод возвращает первого родителя сущности, если таковой существует. В противном случае - пустое значение. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Iblock\InheritedProperty\BaseValues|null 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/basevalues/getparent.php
	* @author Bitrix
	*/
	public function getParent()
	{
		$parents = $this->getParents();
		if (isset($parents[0]))
			return $parents[0];
		else
			return null;
	}

	/**
	 * Returns all calculated values of inherited properties
	 * for this entity.
	 *
	 * @return array[string]string
	 */
	
	/**
	* <p>Метод возвращает все вычисленные значения наследуемых свойств для сущности. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Iblock\InheritedProperty\array[string]string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/basevalues/getvalues.php
	* @author Bitrix
	*/
	public function getValues()
	{
		if ($this->values === false)
			$this->values = $this->queryValues();

		$result = array();
		foreach ($this->values as $CODE => $row)
		{
			$result[$CODE] = htmlspecialcharsEx($row["VALUE"]);
		}
		return $result;
	}

	/**
	 * Returns value of the inherited property.
	 * The result is html encoded string.
	 *
	 * @param string $propertyCode Mnemonic code.
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает значение наследуемого свойства с кодом <code>$propertyCode</code>. Результат - закодированная html-строка. Нестатический метод.</p>
	*
	*
	* @param string $propertyCode  Символьный код свойства.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/basevalues/getvalue.php
	* @author Bitrix
	*/
	public function getValue($propertyCode)
	{
		if ($this->values === false)
			$this->values = $this->queryValues();

		if (isset($this->values[$propertyCode]))
			return htmlspecialcharsEx($this->values[$propertyCode]["VALUE"]);
		else
			return "";
	}

	/**
	 * Queries templates for this entity.
	 * Then processes them in order to get
	 * calculated values.
	 *
	 * @return array[string][string]string
	 */
	
	/**
	* <p>Метод запрашивает шаблоны наследуемых свойств для сущности и затем обрабатывает их, чтобы получить вычисленные значения. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Iblock\InheritedProperty\array[string][string]string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/basevalues/queryvalues.php
	* @author Bitrix
	*/
	public function queryValues()
	{
		$templateInstance = new BaseTemplate($this);
		$templates = $templateInstance->findTemplates();
		foreach ($templates as $CODE => $row)
		{
			$templates[$CODE]["VALUE"] = \Bitrix\Iblock\Template\Engine::process($this->createTemplateEntity(), $row["TEMPLATE"]);
		}
		return $templates;
	}

	/**
	 * Checks if there are some templates exists for this set of values.
	 *
	 * @return boolean
	 */
	
	/**
	* <p>Метод проверяет, имеются ли шаблоны вычисляемых свойств для набора значений. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/basevalues/hastemplates.php
	* @author Bitrix
	*/
	static public function hasTemplates()
	{
		$templateInstance = new BaseTemplate($this);
		return $templateInstance->hasTemplates($this);
	}

	/**
	 * Clears entity values DB cache
	 *
	 * @return void
	 */
	
	/**
	* <p>Метод очищает значения сущности из кеша базы данных. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/basevalues/clearvalues.php
	* @author Bitrix
	*/
	abstract function clearValues();

	/**
	 * Must be called on template delete.
	 *
	 * @param integer $ipropertyId Identifier of the inherited property.
	 *
	 * @return void
	 */
	
	/**
	* <p>Метод удаляет значения наследуемого свойства. Должен вызываться при удалении шаблона наследуемого свойства. Нестатический метод.</p>
	*
	*
	* @param integer $ipropertyId  Идентификатор наследуемого свойства.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/inheritedproperty/basevalues/deletevalues.php
	* @author Bitrix
	*/
	static public function deleteValues($ipropertyId)
	{
		$ipropertyId = intval($ipropertyId);
		$connection = \Bitrix\Main\Application::getConnection();
		$connection->query("
			DELETE FROM b_iblock_iblock_iprop
			WHERE IPROP_ID = ".$ipropertyId."
		");
		$connection->query("
			DELETE FROM b_iblock_section_iprop
			WHERE IPROP_ID = ".$ipropertyId."
		");
		$connection->query("
			DELETE FROM b_iblock_element_iprop
			WHERE IPROP_ID = ".$ipropertyId."
		");
	}
}
