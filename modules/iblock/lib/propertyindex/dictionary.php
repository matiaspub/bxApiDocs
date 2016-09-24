<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\PropertyIndex;

class Dictionary
{
	protected $iblockId = 0;
	protected $cache = array();
	protected static $exists = array();

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
	* <p>Метод возвращает идентификатор информационного блока. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/dictionary/getiblockid.php
	* @author Bitrix
	*/
	public function getIblockId()
	{
		return $this->iblockId;
	}

	/**
	 * Internal method to get database table name for storing values.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы базы данных для хранения значений свойств. Нестатический внутренний метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/dictionary/gettablename.php
	* @author Bitrix
	*/
	public function getTableName()
	{
		return "b_iblock_".$this->iblockId."_index_val";
	}

	/**
	 * Checks if dictionary exists in the database.
	 * Returns true on success.
	 *
	 * @return boolean
	 */
	
	/**
	* <p>Метод проверяет наличие словаря в базе данных и возвращает <i>true</i> в случае успеха. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/dictionary/isexists.php
	* @author Bitrix
	*/
	public function isExists()
	{
		if (!isset(self::$exists[$this->iblockId]))
		{
			$connection = \Bitrix\Main\Application::getConnection();
			self::$exists[$this->iblockId] = $connection->isTableExists($this->getTableName());
		}

		return self::$exists[$this->iblockId];
	}

	/**
	 * Returns validators for VALUE field.
	 * This is an internal method for eAccelerator compatibility.
	 *
	 * @return array[]\Bitrix\Main\Entity\Validator\Base
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>VALUE</code> (значение свойства). Является внутренним статическим методом для совместимости с eAccelerator.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Iblock\PropertyIndex\array[]\Bitrix\Main\Entity\Validator\Base 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/dictionary/validatevalue.php
	* @author Bitrix
	*/
	public static function validateValue()
	{
		return array(
			new \Bitrix\Main\Entity\Validator\Length(null, 2000),
		);
	}

	/**
	 * Creates new dictionary for information block.
	 * You have to be sure that dictionary does not exists.
	 *
	 * @return void
	 */
	
	/**
	* <p>Метод создает новый словарь для информационного блока. Перед использованием метода следует убедиться, что словаря для данного инфоблока не существует. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/dictionary/create.php
	* @author Bitrix
	*/
	public function create()
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$connection->createTable($this->getTableName(), array(
			"ID" => new \Bitrix\Main\Entity\IntegerField("ID", array(
				'primary' => true,
				'unique' => true,
				'required' => true,
			)),
			"VALUE" => new \Bitrix\Main\Entity\StringField("VALUE", array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateValue'),
			)),
		), array("ID"), array("ID"));

		$connection->createIndex($this->getTableName(), 'IX_'.$this->getTableName().'_0', array("VALUE"), array("VALUE" => 200));

		$this->cache = array();
		self::$exists[$this->iblockId] = true;
	}

	/**
	 * Deletes existing dictionary in the database.
	 * You have to check that dictionary exists before calling this method.
	 *
	 * @return void
	 */
	
	/**
	* <p>Метод удаляет существующий в базе данных словарь. Перед вызовом метода необходимо проверить, что словарь существует. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/dictionary/drop.php
	* @author Bitrix
	*/
	public function drop()
	{
		$connection = \Bitrix\Main\Application::getConnection();

		$connection->dropTable($this->getTableName());

		$this->cache = array();
		self::$exists[$this->iblockId] = false;
	}

	/**
	 * Returns unique number presentation of the string.
	 *
	 * @param string  $value           Value for dictionary lookup.
	 * @param boolean $addWhenNotFound Add new value to the dictionary if none found.
	 *
	 * @return int
	 */
	
	/**
	* <p>Возвращает уникальный номер представления строки. Нестатический метод.</p>
	*
	*
	* @param string $value  Значение для поиска в словаре.
	*
	* @param boolean $addWhenNotFound = true Если параметр принимает значение <i>true</i> и ничего не найдено, то
	* будет добавлено новое значение в словарь.
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/dictionary/getstringid.php
	* @author Bitrix
	*/
	public function getStringId($value, $addWhenNotFound = true)
	{
		if (!isset($this->cache[$value]))
		{
			$connection = \Bitrix\Main\Application::getConnection();

			$sqlHelper  = $connection->getSqlHelper();
			$valueId    = $connection->queryScalar("SELECT ID FROM ".$this->getTableName()." WHERE VALUE = '".$sqlHelper->forSql($value)."'");
			if ($valueId === null)
			{
				if ($addWhenNotFound)
				{
					$valueId = $connection->add($this->getTableName(), array(
						"VALUE" => $value,
					));
				}
				else
				{
					$valueId = 0;
				}
			}

			$this->cache[$value] = intval($valueId);
		}

		return $this->cache[$value];
	}

	/**
	 * Returns string by its identifier in the dictionary.
	 *
	 * @param integer $valueId Value identifier for dictionary lookup.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает строковое значение свойства по его идентификатору в словаре. Нестатический метод.</p>
	*
	*
	* @param integer $valueId  Идентификатор значения в словаре.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertyindex/dictionary/getstringbyid.php
	* @author Bitrix
	*/
	public function getStringById($valueId)
	{
		$connection  = \Bitrix\Main\Application::getConnection();
		$stringValue = $connection->queryScalar("SELECT VALUE FROM ".$this->getTableName()." WHERE ID = ".intval($valueId));
		if ($stringValue === null)
		{
			return "";
		}
		return $stringValue;
	}
}
