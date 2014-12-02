<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\Entity;

class MysqliSqlHelper extends MysqlCommonSqlHelper
{
	/**
	 * Escapes special characters in a string for use in an SQL statement.
	 *
	 * @param string $value Value to be escaped.
	 * @param integer $maxLength Limits string length if set.
	 *
	 * @return string
	 */
	public function forSql($value, $maxLength = 0)
	{
		if ($maxLength > 0)
			$value = substr($value, 0, $maxLength);

		$con = $this->connection->getResource();
		/** @var $con \mysqli */

		return $con->real_escape_string($value);
	}

	/**
	 * Returns instance of a descendant from Entity\ScalarField
	 * that matches database type.
	 *
	 * @param string $name Database column name.
	 * @param mixed $type Database specific type.
	 * @param array $parameters Additional information.
	 *
	 * @return Entity\ScalarField
	 */
	static public function getFieldByColumnType($name, $type, array $parameters = null)
	{
		switch($type)
		{
			case MYSQLI_TYPE_TINY:
			case MYSQLI_TYPE_SHORT:
			case MYSQLI_TYPE_LONG:
			case MYSQLI_TYPE_INT24:
			case MYSQLI_TYPE_CHAR:
				return new Entity\IntegerField($name);

			case MYSQLI_TYPE_DECIMAL:
			case MYSQLI_TYPE_NEWDECIMAL:
			case MYSQLI_TYPE_FLOAT:
			case MYSQLI_TYPE_DOUBLE:
				return new Entity\FloatField($name);

			case MYSQLI_TYPE_DATETIME:
			case MYSQLI_TYPE_TIMESTAMP:
				return new Entity\DatetimeField($name);

			case MYSQLI_TYPE_DATE:
			case MYSQLI_TYPE_NEWDATE:
				return new Entity\DateField($name);
		}
		//MYSQLI_TYPE_BIT
		//MYSQLI_TYPE_LONGLONG
		//MYSQLI_TYPE_TIME
		//MYSQLI_TYPE_YEAR
		//MYSQLI_TYPE_INTERVAL
		//MYSQLI_TYPE_ENUM
		//MYSQLI_TYPE_SET
		//MYSQLI_TYPE_TINY_BLOB
		//MYSQLI_TYPE_MEDIUM_BLOB
		//MYSQLI_TYPE_LONG_BLOB
		//MYSQLI_TYPE_BLOB
		//MYSQLI_TYPE_VAR_STRING
		//MYSQLI_TYPE_STRING
		//MYSQLI_TYPE_GEOMETRY
		return new Entity\StringField($name);
	}
}
