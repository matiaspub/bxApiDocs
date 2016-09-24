<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

/**
 * Class Base
 *
 * @package Bitrix\Iblock\Template\Entity
 */
class Base
{
	/** @var integer  */
	protected $id = null;
	/** @var array[string]mixed  */
	protected $fields = null;
	/** @var array[string]string  */
	protected $fieldMap = array();

	/**
	 * @param integer $id Entity identifier.
	 */
	public function __construct($id)
	{
		$this->id = $id;
	}

	/**
	 * Returns entity identifier.
	 *
	 * @return integer
	 */
	
	/**
	* <p>Метод возвращает идентификатор сущности. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/template/entity/base/getid.php
	* @author Bitrix
	*/
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Used to find entity for template processing.
	 *
	 * @param string $entity What to find.
	 *
	 * @return \Bitrix\Iblock\Template\Entity\Base
	 */
	
	/**
	* <p>Метод используется для поиска сущности для обработки шаблона. Нестатический метод.</p>
	*
	*
	* @param string $entity  Сущность, которую необходимо найти.
	*
	* @return \Bitrix\Iblock\Template\Entity\Base 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/template/entity/base/resolve.php
	* @author Bitrix
	*/
	static public function resolve($entity)
	{
		if ($entity === "this")
			return $this;
		else
			return new Base(0);
	}

	/**
	 * Used to initialize entity fields from some external source.
	 *
	 * @param array $fields Entity fields.
	 *
	 * @return void
	 */
	
	/**
	* <p>Используется для инициализации полей сущности из некоторого внешнего источника. Нестатический метод.</p>
	*
	*
	* @param array $fields  Массив, содержащий поля сущности.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/template/entity/base/setfields.php
	* @author Bitrix
	*/
	public function setFields(array $fields)
	{
		$this->fields = $fields;
	}

	/**
	 * Returns field value.
	 *
	 * @param string $fieldName Name of the field to retrieve data from.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает значение поля. Нестатический метод.</p>
	*
	*
	* @param string $fieldName  Название поля, значение которого необходимо получить.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/template/entity/base/getfield.php
	* @author Bitrix
	*/
	public function getField($fieldName)
	{
		if (!$this->loadFromDatabase())
			return "";

		if (!isset($this->fieldMap[$fieldName]))
			return "";

		$fieldName = $this->fieldMap[$fieldName];
		if (!isset($this->fields[$fieldName]))
			return "";

		$fieldValue = $this->fields[$fieldName];
		if (is_array($fieldValue))
		{
			$result = array();
			foreach($fieldValue as $key => $value)
			{
				if ($value instanceof LazyValueLoader)
					$result[$key] = $value->getValue();
				else
					$result[$key] = $value;

			}
			return $result;
		}
		else
		{
			if ($fieldValue instanceof LazyValueLoader)
			{
				return $fieldValue->getValue();
			}
			return $this->fields[$fieldName];
		}
	}

	/**
	 * Loads values from database.
	 * Returns true on success.
	 *
	 * @return boolean
	 */
	protected function loadFromDatabase()
	{
		if (!isset($this->fields))
		{
			$this->fields = array();
		}
		return true;
	}

	/**
	 * Sets new field value only when is not set yet.
	 * Adds mapping from field name to it's internal presentation.
	 *
	 * @param string $fieldName The name of the field.
	 * @param string $internalName Internal name of the field.
	 * @param string $value Value to be stored.
	 *
	 * @return void
	 */
	protected function addField($fieldName, $internalName, $value)
	{
		if (!isset($this->fields[$internalName]))
			$this->fields[$internalName] = $value;
		$this->fieldMap[strtolower($fieldName)] = $internalName;
	}
}

/**
 * Class LazyValueLoader
 * Strategy class used for delaying queries to DB.
 *
 * @package Bitrix\Iblock\Template\Entity
 */
class LazyValueLoader
{
	protected $value = null;
	protected $key = null;

	/**
	 * @param string|integer $key Unique identifier.
	 */
	public function __construct($key)
	{
		$this->key = $key;
	}

	/**
	 * Calls load method if value was not loaded yet.
	 *
	 * @return mixed
	 */
	
	/**
	* <p>Вызывает метод загрузки данных из базы данных, если они еще не были получены. Метод вызывается при преобразовании объекта в строку. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/template/entity/lazyvalueloader/__tostring.php
	* @author Bitrix
	*/
	public function __toString()
	{
		if (!isset($this->value))
			$this->value = $this->load();
		return $this->value;
	}

	/**
	 * Calls load method if value was not loaded yet.
	 *
	 * @return mixed
	 */
	
	/**
	* <p>Вызывает метод загрузки данных из базы данных, если они еще не были получены. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/template/entity/lazyvalueloader/getvalue.php
	* @author Bitrix
	*/
	public function getValue()
	{
		if (!isset($this->value))
			$this->value = $this->load();
		return $this->value;
	}

	/**
	 * Actual work method which have to retrieve data from the DB.
	 *
	 * @return mixed
	 */
	protected function load()
	{
		return "";
	}
}

