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
	public function getSelectedRowsCount()
	{
		return count($this->resource);
	}

	/**
	 * Returns null because there is no way to know the fields.
	 *
	 * @return null
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
