<?php
namespace Bitrix\Main\DB;

abstract class SqlHelper
{
	protected $dbConnection;

	public function __construct(DbConnection $dbConnection)
	{
		$this->dbConnection = $dbConnection;
	}

	abstract public function getQueryDelimiter();

	abstract function forSql($value, $maxLength = 0);

	abstract public function getDateTimeToDbFunction(\Bitrix\Main\Type\DateTime $value, $type = \Bitrix\Main\Type\DateTime::DATE_WITH_TIME);
	abstract public function getDateTimeFromDbFunction($fieldName);

	abstract public function getCurrentDateTimeFunction();
	abstract public function getCurrentDateFunction();
	abstract public function getDatetimeToDateFunction($value);
	abstract public function getConcatFunction();
	abstract public function getIsNullFunction($expression, $result);
	abstract public function getLengthFunction($field);
	abstract public function getToCharFunction($expr, $length = 0);

	static public function getSubstrFunction($str, $from, $length = null)
	{
		$sql = 'SUBSTR('.$str.', '.$from;

		if (!is_null($length))
			$sql .= ', '.$length;

		return $sql.')';
	}

	abstract function prepareInsert($tableName, $arFields);
	abstract function prepareUpdate($tableName, $arFields);
}
