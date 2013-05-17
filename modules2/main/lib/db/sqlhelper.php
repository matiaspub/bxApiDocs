<?php
namespace Bitrix\Main\DB;

abstract class SqlHelper
{
	protected $dbConnection;

	static public function __construct(DbConnection $dbConnection)
	{
		$this->dbConnection = $dbConnection;
	}

	static abstract public function getQueryDelimiter();

	public static abstract function forSql($value, $maxLength = 0);

	static abstract public function getDateTimeToDbFunction(\Bitrix\Main\Type\DateTime $value, $type = \Bitrix\Main\Type\DateTime::DATE_WITH_TIME);
	static abstract public function getDateTimeFromDbFunction($fieldName);

	static abstract public function getCurrentDateTimeFunction();
	static abstract public function getCurrentDateFunction();
	static abstract public function getDatetimeToDateFunction($value);
	static abstract public function getConcatFunction();
	static abstract public function getIsNullFunction($expression, $result);
	static abstract public function getLengthFunction($field);
	static abstract public function getToCharFunction($expr, $length = 0);

	static public function getSubstrFunction($str, $from, $length = null)
	{
		$sql = 'SUBSTR('.$str.', '.$from;

		if (!is_null($length))
			$sql .= ', '.$length;

		return $sql.')';
	}

	public static abstract function prepareInsert($tableName, $arFields);
	public static abstract function prepareUpdate($tableName, $arFields);
}
