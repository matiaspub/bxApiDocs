<?php
namespace Bitrix\Main\DB;
/**
 * Class ArrayResult is for presenting an array as database result
 * with fetch() navigation.
 *
 * @package Bitrix\Main\DB
 */
class ArrayResult extends Result
{
	/** @var array */
	protected $resource;

	/**
	 * @param array $result Array of arrays.
	 */
	static public function __construct($result)
	{
		parent::__construct($result);
	}

	/**
	 * Returns the number of rows in the result.
	 *
	 * @return integer
	 */
	
	/**
	* <p>Нестатический метод возвращает число строк в результате запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/arrayresult/getselectedrowscount.php
	* @author Bitrix
	*/
	public function getSelectedRowsCount()
	{
		return count($this->resource);
	}

	/**
	 * Returns null because there is no way to know the fields.
	 *
	 * @return null
	 */
	
	/**
	* <p>Нестатический метод возвращает null так как нет ни какого способа узнать поля.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return null 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/arrayresult/getfields.php
	* @author Bitrix
	*/
	static public function getFields()
	{
		return null;
	}

	/**
	 * Returns next result row or false.
	 *
	 * @return array|false
	 */
	protected function fetchRowInternal()
	{
		$val = current($this->resource);
		next($this->resource);
		return $val;
	}
}
