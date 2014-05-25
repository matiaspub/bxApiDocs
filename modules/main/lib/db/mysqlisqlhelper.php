<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\Type;

class MysqliSqlHelper extends SqlHelper
{

	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	static public function getLeftQuote()
	{
		return '`';
	}

	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	static public function getRightQuote()
	{
		return '`';
	}

	static public function getQueryDelimiter()
	{
		return ';';
	}

	static public function getAliasLength()
	{
		return 256;
	}

	public function forSql($value, $maxLength = 0)
	{
		if ($maxLength > 0)
			$value = substr($value, 0, $maxLength);

		$con = $this->dbConnection->getResource();
		/** @var $con \mysqli */

		return $con->real_escape_string($value);
	}

	static public function getCurrentDateTimeFunction()
	{
		return "NOW()";
	}

	static public function getCurrentDateFunction()
	{
		return "CURDATE()";
	}

	static public function addSecondsToDateTime($seconds, $from = null)
	{
		if ($from === null)
		{
			$from = static::getCurrentDateTimeFunction();
		}

		return 'DATE_ADD('.$from.', INTERVAL '.$seconds.' SECOND)';
	}

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

	static public function getIsNullFunction($expression, $result)
	{
		return "IFNULL(".$expression.", ".$result.")";
	}

	static public function getLengthFunction($field)
	{
		return "LENGTH(".$field.")";
	}

	static public function getCharToDateFunction($value)
	{
		return "'".$value."'";
	}

	static public function getDateToCharFunction($fieldName)
	{
		return $fieldName;
	}

	static public function getDatetimeToDateFunction($value)
	{
		return 'DATE('.$value.')';
	}

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

		foreach ($search as $k=>$v)
		{
			$format = str_replace($v, $replace[$k], $format);
		}

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

	public function prepareInsert($tableName, $arFields)
	{
		$strInsert1 = "";
		$strInsert2 = "";

		$arColumns = $this->dbConnection->getTableFields($tableName);
		foreach ($arColumns as $columnName => $columnInfo)
		{
			if (array_key_exists($columnName, $arFields))
			{
				$strInsert1 .= ", `".$columnName."`";
				$strInsert2 .= ", ".$this->convertValueToDb($arFields[$columnName], $columnInfo);
			}
			elseif (array_key_exists("~".$columnName, $arFields))
			{
				$strInsert1 .= ", `".$columnName."`";
				$strInsert2 .= ", ".$arFields["~".$columnName];
			}
		}

		if ($strInsert1 != "")
		{
			$strInsert1 = " ".substr($strInsert1, 2)." ";
			$strInsert2 = " ".substr($strInsert2, 2)." ";
		}

		return array($strInsert1, $strInsert2, array());
	}

	protected function convertValueToDb($value, array $columnInfo)
	{
		if ($value === null)
		{
			return "NULL";
		}

		if ($value instanceof SqlExpression)
		{
			return $value->compile();
		}

		switch ($columnInfo["TYPE"])
		{
			case "datetime":
				if (empty($value))
				{
					$result = "NULL";
				}
				elseif($value instanceof Type\Date)
				{
					if($value instanceof Type\DateTime)
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
				break;
			case "date":
				if (empty($value))
				{
					$result = "NULL";
				}
				elseif($value instanceof Type\Date)
				{
					$result = $this->getCharToDateFunction($value->format("Y-m-d"));
				}
				else
				{
					throw new Main\ArgumentTypeException('value', '\Bitrix\Main\Type\Date');
				}
				break;
			case "int":
				$result = "'".intval($value)."'";
				break;
			case "real":
				$result = "'".doubleval($value)."'";
				break;
			default:
				$result = "'".$this->forSql($value)."'";
				break;
		}

		return $result;
	}

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
