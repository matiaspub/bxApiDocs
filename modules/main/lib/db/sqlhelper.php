<?php
namespace Bitrix\Main\DB;

abstract class SqlHelper
{
	protected $dbConnection;

	public function __construct(Connection $dbConnection)
	{
		$this->dbConnection = $dbConnection;
	}

	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	static public function getLeftQuote()
	{
		return '';
	}

	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	static public function getRightQuote()
	{
		return '';
	}

	/**
	 * Returns maximum length of an alias in a select statement
	 * @return int
	 */
	static public function getAliasLength()
	{
		return 30;
	}

	/**
	 * @param $identifier string Table or Column name
	 * @return string Quoted identifier, e.g. `TITLE` for MySQL
	 */
	public function quote($identifier)
	{
		// security unshielding
		$identifier = str_replace(array($this->getLeftQuote(), $this->getRightQuote()), '', $identifier);

		// shield [[database.]tablename.]columnname
		if (strpos($identifier, '.') !== false)
		{
			$identifier = str_replace('.', $this->getRightQuote() . '.' . $this->getLeftQuote(), $identifier);
		}

		// shield general borders
		return $this->getLeftQuote() . $identifier . $this->getRightQuote();
	}

	abstract public function getQueryDelimiter();
	abstract public function forSql($value, $maxLength = 0);
	abstract public function getCharToDateFunction($value);
	abstract public function getDateToCharFunction($fieldName);
	abstract public function getCurrentDateTimeFunction();
	abstract public function getCurrentDateFunction();
	abstract public function addSecondsToDateTime($seconds, $from = null);
	abstract public function getDatetimeToDateFunction($value);
	abstract public function formatDate($format, $field = null);
	abstract public function getConcatFunction();
	abstract public function getIsNullFunction($expression, $result);
	abstract public function getLengthFunction($field);
	abstract public function getTopSql($sql, $limit, $offset = 0);

	static public function getSubstrFunction($str, $from, $length = null)
	{
		$sql = 'SUBSTR('.$str.', '.$from;

		if (!is_null($length))
			$sql .= ', '.$length;

		return $sql.')';
	}

	/**
	 * @param $tableName
	 * @param $arFields
	 *
	 * @return array (columnlist, valuelist, binds)
	 */
	abstract function prepareInsert($tableName, $arFields);

	/**
	 * @param $tableName
	 * @param $fields
	 *
	 * @return array (data, binds)
	 */
	public function prepareUpdate($tableName, $fields)
	{
		$update = array();

		$columns = $this->dbConnection->getTableFields($tableName);

		foreach ($columns as $columnName => $columnInfo)
		{
			if (array_key_exists($columnName, $fields))
			{
				$update[] = $this->prepareAssignment($tableName, $columnName, $fields[$columnName]);
			}
		}

		$update = join(', ', $update);
		$binds = $this->prepareBinds($tableName, $fields);

		return array($update, $binds);
	}

	static public function prepareBinds($tableName, $fields)
	{
		return array();
	}

	public function prepareAssignment($tableName, $columnName, $value)
	{
		$columnInfo = $this->dbConnection->getTableField($tableName, $columnName);

		return $this->quote($columnName) . ' = ' . $this->convertValueToDb($value, $columnInfo);
	}

	abstract protected function convertValueToDb($value, array $columnInfo);

	/**
	 * Returns ascending order specifier for ORDER BY clause
	 * @return string
	 */
	static public function getAscendingOrder()
	{
		return 'ASC';
	}

	/**
	 * Returns descending order specifier for ORDER BY clause
	 * @return string
	 */
	static public function getDescendingOrder()
	{
		return 'DESC';
	}
}
