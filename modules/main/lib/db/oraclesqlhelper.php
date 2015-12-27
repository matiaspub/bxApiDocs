<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\Entity;

class OracleSqlHelper extends SqlHelper
{
	/**
	 * Identificator escaping - left char
	 *
	 * @return string
	 */
	static public function getLeftQuote()
	{
		return '"';
	}

	/**
	 * Identificator escaping - left char
	 *
	 * @return string
	 */
	static public function getRightQuote()
	{
		return '"';
	}

	/**
	 * Returns maximum length of an alias in a select statement
	 *
	 * @return integer
	 */
	static public function getAliasLength()
	{
		return 30;
	}

	/**
	 * Returns quoted identifier.
	 *
	 * @param string $identifier Table or Column name.
	 *
	 * @return string
	 * @see \Bitrix\Main\DB\SqlHelper::quote
	 */
	static public function quote($identifier)
	{
		return parent::quote(strtoupper($identifier));
	}

	/**
	 * Returns database specific query delimiter for batch processing.
	 *
	 * @return string
	 */
	static public function getQueryDelimiter()
	{
		return "(?<!\\*)/(?!\\*)";
	}

	/**
	 * Escapes special characters in a string for use in an SQL statement.
	 *
	 * @param string $value Value to be escaped.
	 * @param integer $maxLength Limits string length if set.
	 *
	 * @return string
	 */
	public static function forSql($value, $maxLength = 0)
	{
		if ($maxLength <= 0 || $maxLength > 2000)
			$maxLength = 2000;

		$value = substr($value, 0, $maxLength);

		if (\Bitrix\Main\Application::isUtfMode())
		{
			// From http://w3.org/International/questions/qa-forms-utf-8.html
			// This one can crash php with segmentation fault on large input data (over 20K)
			// https://bugs.php.net/bug.php?id=60423
			if (preg_match_all('%(
				[\x00-\x7E]                        # ASCII
				|[\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
				|\xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
				|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
				|\xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
				|\xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
				|[\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
				|\xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
			)+%x', $value, $match))
				$value = implode(' ', $match[0]);
			else
				return ''; //There is no valid utf at all
		}

		return str_replace("'", "''", $value);
	}

	/**
	 * Returns function for getting current time.
	 *
	 * @return string
	 */
	static public function getCurrentDateTimeFunction()
	{
		return "SYSDATE";
	}

	/**
	 * Returns function for getting current date without time part.
	 *
	 * @return string
	 */
	static public function getCurrentDateFunction()
	{
		return "TRUNC(SYSDATE)";
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

		return '('.$from.'+'.$seconds.'/86400)';
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
		return 'TRUNC('.$value.')';
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
		$format = str_replace("HH", "HH24", $format);
		$format = str_replace("GG", "HH24", $format);

		if (strpos($format, 'HH24') === false)
		{
			$format = str_replace("H", "HH", $format);
		}

		$format = str_replace("G", "HH", $format);

		$format = str_replace("MI", "II", $format);

		if (strpos($format, 'MMMM') !== false)
		{
			$format = str_replace("MMMM", "MONTH", $format);
		}
		elseif (strpos($format, 'MM') === false)
		{
			$format = str_replace("M", "MON", $format);
		}

		$format = str_replace("II", "MI", $format);

		$format = str_replace("TT", "AM", $format);
		$format = str_replace("T", "AM", $format);

		if ($field === null)
		{
			return $format;
		}
		else
		{
			return "TO_CHAR(".$field.", '".$format."')";
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
			$str .= implode(" || ", $ar);
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
		return "NVL(".$expression.", ".$result.")";
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
		return "TO_DATE('".$value."', 'YYYY-MM-DD HH24:MI:SS')";
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
		return "TO_CHAR(".$fieldName.", 'YYYY-MM-DD HH24:MI:SS')";
	}

	/**
	 * Performs additional processing of CLOB fields.
	 *
	 * @param Entity\ScalarField[] $tableFields Table fields.
	 * @param array $fields Data fields.
	 *
	 * @return array
	 */
	protected function prepareBinds(array $tableFields, array $fields)
	{
		$binds = array();

		foreach ($tableFields as $columnName => $tableField)
		{
			if (isset($fields[$columnName]) && !($fields[$columnName] instanceof SqlExpression))
			{
				if ($tableField instanceof Entity\TextField && $fields[$columnName] <> '')
				{
					$binds[$columnName] = $fields[$columnName];
				}
			}
		}

		return $binds;
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
		if ($field instanceof Entity\DatetimeField)
		{
			return array($this, "convertDatetimeField");
		}
		elseif ($field instanceof Entity\TextField)
		{
			return array($this, "convertTextField");
		}
		elseif ($field instanceof Entity\StringField)
		{
			return array($this, "convertStringField");
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
	 * @see \Bitrix\Main\Db\OracleSqlHelper::getConverter
	 */
	static public function convertDatetimeField($value)
	{
		if ($value !== null)
		{
			if (strlen($value) == 19)
			{
				//preferable format: NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'
				$value = new Type\DateTime($value, "Y-m-d H:i:s");
			}
			else
			{
				//default Oracle date format: 03-MAR-14
				$value = new Type\DateTime($value." 00:00:00", "d-M-y H:i:s");
			}
		}

		return $value;
	}

	/**
	 * Converts lob object into string.
	 * <p>
	 * Helper function.
	 *
	 * @param string $value Value fetched.
	 *
	 * @return null|string
	 * @see \Bitrix\Main\Db\OracleSqlHelper::getConverter
	 */
	static public function convertTextField($value)
	{
		if ($value !== null)
		{
			if (is_object($value))
			{
				/** @var \OCI_Lob $value */
				$value = $value->load();
			}
		}

		return $value;
	}

	/**
	 * Converts string into \Bitrix\Main\Type\Date object if string has datetime specific format..
	 * <p>
	 * Helper function.
	 *
	 * @param string $value Value fetched.
	 *
	 * @return null|\Bitrix\Main\Type\DateTime
	 * @see \Bitrix\Main\Db\OracleSqlHelper::getConverter
	 */
	static public function convertStringField($value)
	{
		if ($value !== null)
		{
			if ((strlen($value) == 19) && preg_match("#^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$#", $value))
			{
				$value = new Type\DateTime($value, "Y-m-d H:i:s");
			}
		}

		return $value;
	}

	/**
	 * Converts values to the string according to the column type to use it in a SQL query.
	 *
	 * @param mixed $value Value to be converted.
	 * @param Entity\ScalarField $field Type "source".
	 *
	 * @return string Value to write to column.
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function convertToDb($value, Entity\ScalarField $field)
	{
		if ($value === null)
		{
			return "NULL";
		}

		if ($value instanceof SqlExpression)
		{
			return $value->compile();
		}

		if ($field instanceof Entity\DatetimeField)
		{
			if (empty($value))
			{
				$result = "NULL";
			}
			elseif ($value instanceof Type\Date)
			{
				if ($value instanceof Type\DateTime)
				{
					$value = clone($value);
					$value->setDefaultTimeZone();
				}
				$result = $this->getCharToDateFunction($value->format("Y-m-d H:i:s"));
			}
			else
			{
				throw new Main\ArgumentTypeException('value', '\Bitrix\Main\Type\Date');
			}
		}
		elseif ($field instanceof Entity\TextField)
		{
			if (empty($value))
			{
				$result = "NULL";
			}
			else
			{
				$result = "EMPTY_CLOB()";
			}
		}
		elseif ($field instanceof Entity\IntegerField)
		{
			$result = "'".intval($value)."'";
		}
		elseif ($field instanceof Entity\FloatField)
		{
			if (($scale = $field->getScale()) !== null)
			{
				$result = "'".round(doubleval($value), $scale)."'";
			}
			else
			{
				$result = "'".doubleval($value)."'";
			}
		}
		elseif ($field instanceof Entity\StringField)
		{
			$result = "'".$this->forSql($value, $field->getSize())."'";
		}
		else
		{
			$result = "'".$this->forSql($value)."'";
		}

		return $result;
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
			return 'number(18)';
		}
		elseif ($field instanceof Entity\FloatField)
		{
			$scale = $field->getScale();
			return 'number'.($scale !== null? "(*,".$scale.")": "");
		}
		elseif ($field instanceof Entity\DatetimeField)
		{
			return 'date';
		}
		elseif ($field instanceof Entity\DateField)
		{
			return 'date';
		}
		elseif ($field instanceof Entity\TextField)
		{
			return 'clob';
		}
		elseif ($field instanceof Entity\BooleanField)
		{
			$values = $field->getValues();

			if (preg_match('/^[0-9]+$/', $values[0]) && preg_match('/^[0-9]+$/', $values[1]))
			{
				return 'number(1)';
			}
			else
			{
				return 'varchar2('.max(strlen($values[0]), strlen($values[1])).' char)';
			}
		}
		elseif ($field instanceof Entity\EnumField)
		{
			return 'varchar2('.max(array_map('strlen', $field->getValues())).' char)';
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
			return 'varchar2('.($defaultLength > 0? $defaultLength: 255).' char)';
		}
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
		switch ($type)
		{
		case "DATE":
			return new Entity\DatetimeField($name);

		case "NCLOB":
		case "CLOB":
		case "BLOB":
			return new Entity\TextField($name);

		case "FLOAT":
		case "BINARY_FLOAT":
		case "BINARY_DOUBLE":
			return new Entity\FloatField($name);

		case "NUMBER":
			if ($parameters["precision"] == '' && $parameters["scale"] == '')
			{
				//NUMBER
				return new Entity\FloatField($name);
			}
			if (intval($parameters["scale"]) <= 0)
			{
				//NUMBER(18)
				//NUMBER(18,-2)
				return new Entity\IntegerField($name);
			}
			//NUMBER(*,2)
			return new Entity\FloatField($name, array("scale" => $parameters["scale"]));
		}
		//LONG
		//VARCHAR2(size [BYTE | CHAR])
		//NVARCHAR2(size)
		//TIMESTAMP [(fractional_seconds_precision)]
		//TIMESTAMP [(fractional_seconds)] WITH TIME ZONE
		//TIMESTAMP [(fractional_seconds)] WITH LOCAL TIME ZONE
		//INTERVAL YEAR [(year_precision)] TO MONTH
		//INTERVAL DAY [(day_precision)] TO SECOND [(fractional_seconds)]
		//RAW(size)
		//LONG RAW
		//ROWID
		//UROWID [(size)]
		//CHAR [(size [BYTE | CHAR])]
		//NCHAR[(size)]
		//BFILE
		return new Entity\StringField($name, array("size" => $parameters["size"]));
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
			if ($offset <= 0)
			{
				$sql =
					"SELECT * ".
					"FROM (".$sql.") ".
					"WHERE ROWNUM <= ".$limit;
			}
			else
			{
				$sql =
					"SELECT * ".
					"FROM (".
					"   SELECT rownum_query_alias.*, ROWNUM rownum_alias ".
					"   FROM (".$sql.") rownum_query_alias ".
					"   WHERE ROWNUM <= ".($offset + $limit - 1)." ".
					") ".
					"WHERE rownum_alias >= ".$offset;
			}
		}
		return $sql;
	}

	/**
	 * Returns ascending order specifier for ORDER BY clause.
	 *
	 * @return string
	 */
	static public function getAscendingOrder()
	{
		return 'ASC NULLS FIRST';
	}

	/**
	 * Returns descending order specifier for ORDER BY clause.
	 *
	 * @return string
	 */
	static public function getDescendingOrder()
	{
		return 'DESC NULLS LAST';
	}

	/**
	 * Builds the strings for the SQL MERGE command for the given table.
	 *
	 * @param string $tableName A table name.
	 * @param array $primaryFields Array("column")[] Primary key columns list.
	 * @param array $insertFields Array("column" => $value)[] What to insert.
	 * @param array $updateFields Array("column" => $value)[] How to update.
	 *
	 * @return array (merge)
	 */
	public function prepareMerge($tableName, array $primaryFields, array $insertFields, array $updateFields)
	{
		$insert = $this->prepareInsert($tableName, $insertFields);

		$updateColumns = array();
		$sourceSelectColumns = array();
		$targetConnectColumns = array();
		$tableFields = $this->connection->getTableFields($tableName);
		foreach($tableFields as $columnName => $tableField)
		{
			$quotedName = $this->quote($columnName);
			if (in_array($columnName, $primaryFields))
			{
				$sourceSelectColumns[] = $this->convertToDb($insertFields[$columnName], $tableField)." AS ".$quotedName;
				$targetConnectColumns[] = "source.".$quotedName." = target.".$quotedName;
			}

			if (isset($updateFields[$columnName]) || array_key_exists($columnName, $updateFields))
			{
				$updateColumns[] = "target.".$quotedName.' = '.$this->convertToDb($updateFields[$columnName], $tableField);
			}
		}

		if (
			$insert && $insert[0] != "" && $insert[1] != ""
			&& $updateColumns
			&& $sourceSelectColumns && $targetConnectColumns
		)
		{
			$sql = "
				MERGE INTO ".$this->quote($tableName)." target USING (
					SELECT ".implode(", ", $sourceSelectColumns)." FROM dual
				)
				source ON
				(
					".implode(" AND ", $targetConnectColumns)."
				)
				WHEN MATCHED THEN
					UPDATE SET ".implode(", ", $updateColumns)."
				WHEN NOT MATCHED THEN
					INSERT (".$insert[0].")
					VALUES (".$insert[1].")
			";
		}
		else
		{
			$sql = "";
		}

		return array(
			$sql
		);
	}
}
