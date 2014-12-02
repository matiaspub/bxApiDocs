<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\Entity;

abstract class MysqlCommonSqlHelper extends SqlHelper
{
	/**
	 * Identificator escaping - left char
	 *
	 * @return string
	 */
	static public function getLeftQuote()
	{
		return '`';
	}

	/**
	 * Identificator escaping - left char
	 *
	 * @return string
	 */
	static public function getRightQuote()
	{
		return '`';
	}

	/**
	 * Returns maximum length of an alias in a select statement
	 *
	 * @return integer
	 */
	static public function getAliasLength()
	{
		return 256;
	}

	/**
	 * Returns database specific query delimiter for batch processing.
	 *
	 * @return string
	 */
	static public function getQueryDelimiter()
	{
		return ';';
	}

	/**
	 * Returns function for getting current time.
	 *
	 * @return string
	 */
	static public function getCurrentDateTimeFunction()
	{
		return "NOW()";
	}

	/**
	 * Returns function for getting current date without time part.
	 *
	 * @return string
	 */
	static public function getCurrentDateFunction()
	{
		return "CURDATE()";
	}

	/**
	 * Returns function for adding seconds time interval to $from.
	 * <p>
	 * If $from is null or omitted, then current time is used.
	 * <p>
	 * $seconds and $from parameters are SQL unsafe.
	 *
	 * @param integer $seconds How many seconds to add.
	 * @param integer $from Datetime database field of expression.
	 *
	 * @return string
	 */
	static public function addSecondsToDateTime($seconds, $from = null)
	{
		if ($from === null)
		{
			$from = static::getCurrentDateTimeFunction();
		}

		return 'DATE_ADD('.$from.', INTERVAL '.$seconds.' SECOND)';
	}

	/**
	 * Returns function cast $value to datetime database type.
	 * <p>
	 * $value parameter is SQL unsafe.
	 *
	 * @param string $value Database field or expression to cast.
	 *
	 * @return string
	 */
	static public function getDatetimeToDateFunction($value)
	{
		return 'DATE('.$value.')';
	}

	/**
	 * Returns database expression for converting $field value according the $format.
	 * <p>
	 * Following format parts converted:
	 * - YYYY   A full numeric representation of a year, 4 digits
	 * - MMMM   A full textual representation of a month, such as January or March
	 * - MM     Numeric representation of a month, with leading zeros
	 * - MI     Minutes with leading zeros
	 * - M      A short textual representation of a month, three letters
	 * - DD     Day of the month, 2 digits with leading zeros
	 * - HH     24-hour format of an hour with leading zeros
	 * - H      12-hour format of an hour with leading zeros
	 * - GG     24-hour format of an hour with leading zeros
	 * - G      12-hour format of an hour with leading zeros
	 * - SS     Minutes with leading zeros
	 * - TT     AM or PM
	 * - T      AM or PM
	 * <p>
	 * $field parameter is SQL unsafe.
	 *
	 * @param string $format Format string.
	 * @param string $field Database field or expression.
	 *
	 * @return string
	 */
	static public function formatDate($format, $field = null)
	{
		static $search  = array(
			"YYYY",
			"MMMM",
			"MM",
			"MI",
			"DD",
			"HH",
			"GG",
			"G",
			"SS",
			"TT",
			"T"
		);
		static $replace = array(
			"%Y",
			"%M",
			"%m",
			"%i",
			"%d",
			"%H",
			"%h",
			"%l",
			"%s",
			"%p",
			"%p"
		);

		$format = str_replace($search, $replace, $format);

		if (strpos($format, '%H') === false)
		{
			$format = str_replace("H", "%h", $format);
		}

		if (strpos($format, '%M') === false)
		{
			$format = str_replace("M", "%b", $format);
		}

		if($field === null)
		{
			return $format;
		}
		else
		{
			return "DATE_FORMAT(".$field.", '".$format."')";
		}
	}

	/**
	 * Returns function for concatenating database fields or expressions.
	 * <p>
	 * All parameters are SQL unsafe.
	 *
	 * @param string $field,... Database fields or expressions.
	 *
	 * @return string
	 */
	static public function getConcatFunction()
	{
		$str = "";
		$ar = func_get_args();
		if (is_array($ar))
			$str .= implode(", ", $ar);
		if (strlen($str) > 0)
			$str = "CONCAT(".$str.")";
		return $str;
	}

	/**
	 * Returns function for testing database field or expressions
	 * against NULL value. When it is NULL then $result will be returned.
	 * <p>
	 * All parameters are SQL unsafe.
	 *
	 * @param string $expression Database field or expression for NULL test.
	 * @param string $result Database field or expression to return when $expression is NULL.
	 *
	 * @return string
	 */
	static public function getIsNullFunction($expression, $result)
	{
		return "IFNULL(".$expression.", ".$result.")";
	}

	/**
	 * Returns function for getting length of database field or expression.
	 * <p>
	 * $field parameter is SQL unsafe.
	 *
	 * @param string $field Database field or expression.
	 *
	 * @return string
	 */
	static public function getLengthFunction($field)
	{
		return "LENGTH(".$field.")";
	}

	/**
	 * Returns function for converting string value into datetime.
	 * $value must be in YYYY-MM-DD HH:MI:SS format.
	 * <p>
	 * $value parameter is SQL unsafe.
	 *
	 * @param string $value String in YYYY-MM-DD HH:MI:SS format.
	 *
	 * @return string
	 * @see \Bitrix\Main\DB\MssqlSqlHelper::formatDate
	 */
	static public function getCharToDateFunction($value)
	{
		return "'".$value."'";
	}

	/**
	 * Returns function for converting database field or expression into string.
	 * <p>
	 * Result string will be in YYYY-MM-DD HH:MI:SS format.
	 * <p>
	 * $fieldName parameter is SQL unsafe.
	 *
	 * @param string $fieldName Database field or expression.
	 *
	 * @return string
	 * @see \Bitrix\Main\DB\MssqlSqlHelper::formatDate
	 */
	static public function getDateToCharFunction($fieldName)
	{
		return $fieldName;
	}

	/**
	 * Returns $value converted to an type according to $field type.
	 * <p>
	 * For example if $field is Entity\DatetimeField then returned value will be instance of Type\DateTime.
	 *
	 * @param mixed $value Value to be converted.
	 * @param Entity\ScalarField $field Type "source".
	 *
	 * @return mixed
	 */
	static public function convertFromDb($value, Entity\ScalarField $field)
	{
		if($value !== null)
		{
			if($field instanceof Entity\DatetimeField)
			{
				$value = new Type\DateTime($value, "Y-m-d H:i:s");
			}
			elseif($field instanceof Entity\DateField)
			{
				$value = new Type\Date($value, "Y-m-d");
			}
		}

		return $value;
	}

	/**
	 * Returns callback to be called for a field value on fetch.
	 *
	 * @param Entity\ScalarField $field Type "source".
	 *
	 * @return false|callback
	 */
	static public function getConverter(Entity\ScalarField $field)
	{
		if($field instanceof Entity\DatetimeField)
		{
			return array($this, "convertDatetimeField");
		}
		elseif($field instanceof Entity\DateField)
		{
			return array($this, "convertDateField");
		}
		else
		{
			return parent::getConverter($field);
		}
	}

	/**
	 * Converts string into \Bitrix\Main\Type\DateTime object.
	 * <p>
	 * Helper function.
	 *
	 * @param string $value Value fetched.
	 *
	 * @return null|\Bitrix\Main\Type\DateTime
	 * @see \Bitrix\Main\Db\MysqlCommonSqlHelper::getConverter
	 */
	static public function convertDatetimeField($value)
	{
		if($value !== null)
		{
			$value = new Type\DateTime($value, "Y-m-d H:i:s");
		}

		return $value;
	}

	/**
	 * Converts string into \Bitrix\Main\Type\Date object.
	 * <p>
	 * Helper function.
	 *
	 * @param string $value Value fetched.
	 *
	 * @return null|\Bitrix\Main\Type\DateTime
	 * @see \Bitrix\Main\Db\MysqlCommonSqlHelper::getConverter
	 */
	static public function convertDateField($value)
	{
		if($value !== null)
		{
			$value = new Type\Date($value, "Y-m-d");
		}

		return $value;
	}

	/**
	 * Returns a column type according to ScalarField object.
	 *
	 * @param Entity\ScalarField $field Type "source".
	 *
	 * @return string
	 */
	static public function getColumnTypeByField(Entity\ScalarField $field)
	{
		if ($field instanceof Entity\IntegerField)
		{
			return 'int';
		}
		elseif ($field instanceof Entity\FloatField)
		{
			return 'double';
		}
		elseif ($field instanceof Entity\DatetimeField)
		{
			return 'datetime';
		}
		elseif ($field instanceof Entity\DateField)
		{
			return 'date';
		}
		elseif ($field instanceof Entity\TextField)
		{
			return 'text';
		}
		elseif ($field instanceof Entity\BooleanField)
		{
			$values = $field->getValues();

			if (ctype_digit($values[0]) && ctype_digit($values[1]))
			{
				return 'int';
			}
			else
			{
				return 'varchar('.max(strlen($values[0]), strlen($values[1])).')';
			}
		}
		elseif ($field instanceof Entity\EnumField)
		{
			return 'varchar('.max(array_map('strlen', $field->getValues())).')';
		}
		else
		{
			// string by default
			$defaultLength = false;
			foreach ($field->getValidators() as $validator)
			{
				if ($validator instanceof Entity\Validator\Length)
				{
					if ($defaultLength === false || $defaultLength > $validator->getMax())
					{
						$defaultLength = $validator->getMax();
					}
				}
			}
			return 'varchar('.($defaultLength > 0? $defaultLength: 255).')';
		}
	}

	/**
	 * Transforms Sql according to $limit and $offset limitations.
	 * <p>
	 * You must specify $limit when $offset is set.
	 *
	 * @param string $sql Sql text.
	 * @param integer $limit Maximum number of rows to return.
	 * @param integer $offset Offset of the first row to return.
	 *
	 * @return string
	 * @throws Main\ArgumentException
	 */
	static public function getTopSql($sql, $limit, $offset = 0)
	{
		$offset = intval($offset);
		$limit = intval($limit);

		if ($offset > 0 && $limit <= 0)
			throw new \Bitrix\Main\ArgumentException("Limit must be set if offset is set");

		if ($limit > 0)
		{
			$sql .= "\nLIMIT ".$offset.", ".$limit."\n";
		}

		return $sql;
	}
}
