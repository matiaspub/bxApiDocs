<?php
namespace Bitrix\Perfmon\Sql;

/**
 * Class BaseObject
 * Base class for all schema objects such as tables, columns, indexes, etc.
 * @package Bitrix\Perfmon\Sql
 */
abstract class BaseObject
{
	/** @var BaseObject|null */
	public $parent = null;
	public $name = '';
	public $body = '';
	protected $ciName = '';

	/**
	 * @param string $name Name of the table.
	 */
	public function __construct($name = '')
	{
		$this->name = (string)$name;
		$this->ciName = $this->getCompareName($this->name);
	}

	/**
	 * Sets source code for object.
	 *
	 * @param string $body The body.
	 *
	 * @return BaseObject
	 */
	
	/**
	* <p>Нестатический метод устанавливает исходный код для объекта.</p>
	*
	*
	* @param string $body  Код для <code>body</code>.
	*
	* @return \Bitrix\Perfmon\Sql\BaseObject 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/baseobject/setbody.php
	* @author Bitrix
	*/
	public function setBody($body)
	{
		$this->body = trim($body);
		return $this;
	}

	/**
	 * Sets parent for object.
	 * <p>
	 * For example Table for Column.
	 *
	 * @param BaseObject $parent Parent object.
	 *
	 * @return BaseObject
	 */
	
	/**
	* <p>Нестатический метод устанавливает "родителя" для объекта. Например: таблицу или колонку таблицы.</p> <br>
	*
	*
	* @param mixed $Bitrix  Родительский объект.
	*
	* @param Bitri $Perfmon  
	*
	* @param Perfmo $Sql  
	*
	* @param BaseObject $parent = null 
	*
	* @return \Bitrix\Perfmon\Sql\BaseObject 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/baseobject/setparent.php
	* @author Bitrix
	*/
	public function setParent(BaseObject $parent = null)
	{
		$this->parent = $parent;
		return $this;
	}

	/**
	 * Returns "lowercased" name of the object.
	 * <p>
	 * If name is not quoted then it made lowercase.
	 *
	 * @return string
	 */
	final public function getLowercasedName()
	{
		if ($this->name[0] == '`')
			return $this->name;
		elseif ($this->name[0] == '"')
			return $this->name;
		elseif ($this->name[0] == '[')
			return $this->name;
		else
			return strtolower($this->name);
	}

	/**
	 * Returns "normalized" name of the table.
	 * <p>
	 * If name is not quoted then it made uppercase.
	 *
	 * @param string $name Table name.
	 * @return string
	 */
	
	/**
	* <p>Статический метод возвращает "нормализованное" название таблицы. Если название не в кавычках, то оно будет передано в верхнем регистре.</p> <br>
	*
	*
	* @param string $name  Название таблицы.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/baseobject/getcomparename.php
	* @author Bitrix
	*/
	final public static function getCompareName($name)
	{
		if ($name[0] == '`')
			return $name;
		elseif ($name[0] == '"')
			return $name;
		elseif ($name[0] == '[')
			return $name;
		else
			return strtoupper($name);
	}

	/**
	 * Compares name of the table with given.
	 * <p>
	 * If name has no quotes when comparison is case insensitive.
	 *
	 * @param string $name Table name to compare.
	 * @return int
	 * @see strcmp
	 */
	
	/**
	* <p>Нестатический метод сравнивает имя таблицы с данным. Если название не взято в кавычки, то регистр не учитывается.</p> <br>
	*
	*
	* @param string $name  Имя сравниваемой таблицы.
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/baseobject/comparename.php
	* @author Bitrix
	*/
	final public function compareName($name)
	{
		return strcmp($this->ciName, $this->getCompareName($name));
	}

	/**
	 * Return DDL or commentary for object creation.
	 *
	 * @param string $dbType Database type.
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает DDL или комментарий для создания объекта.</p>
	*
	*
	* @param string $dbType = '' Тип базы данных.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/baseobject/getcreateddl.php
	* @author Bitrix
	*/
	static public function getCreateDdl($dbType = '')
	{
		return "// ".get_class($this).":getCreateDdl not implemented";
	}

	/**
	 * Return DDL or commentary for object destruction.
	 *
	 * @param string $dbType Database type.
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает DDL или комментарий для удаления объекта.</p>
	*
	*
	* @param string $dbType = '' Тип базы данных.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/baseobject/getdropddl.php
	* @author Bitrix
	*/
	static public function getDropDdl($dbType = '')
	{
		return "// ".get_class($this).":getDropDdl not implemented";
	}

	/**
	 * Return DDL or commentary for object modification.
	 *
	 * @param BaseObject $target Target object.
	 * @param string $dbType Database type.
	 *
	 * @return array|string
	 */
	
	/**
	* <p>Нестатический метод возвращает DDL или комментарий для изменения объекта.</p>
	*
	*
	* @param mixed $Bitrix  Целевой объект.
	*
	* @param Bitri $Perfmon  Тип базы данных.
	*
	* @param Perfmo $Sql  
	*
	* @param BaseObject $target  
	*
	* @param string $dbType = '' 
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/sql/baseobject/getmodifyddl.php
	* @author Bitrix
	*/
	static public function getModifyDdl(BaseObject $target, $dbType = '')
	{
		return "// ".get_class($this).":getModifyDdl not implemented";
	}
}
