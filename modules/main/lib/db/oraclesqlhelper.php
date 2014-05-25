<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\Type;

class OracleSqlHelper extends SqlHelper
{
	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	static public function getLeftQuote()
	{
		return '"';
	}

	/**
	 * Identificator escaping - left char
	 * @return string
	 */
	static public function getRightQuote()
	{
		return '"';
	}

	static public function quote($identifier)
	{
		return parent::quote(strtoupper($identifier));
	}

	static public function getQueryDelimiter()
	{
		return "(?<!\\*)/(?!\\*)";
	}

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

	static public function getCurrentDateTimeFunction()
	{
		return "SYSDATE";
	}

	static public function getCurrentDateFunction()
	{
		return "TRUNC(SYSDATE)";
	}

	static public function addSecondsToDateTime($seconds, $from = null)
	{
		if ($from === null)
		{
			$from = static::getCurrentDateTimeFunction();
		}

		return '('.$from.'+'.$seconds.'/86400)';
	}

	static public function getDatetimeToDateFunction($value)
	{
		return 'TRUNC('.$value.')';
	}

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

		if($field === false)
		{
			return $format;
		}
		else
		{
			return "TO_CHAR(".$field.", '".$format."')";
		}
	}

	static public function getConcatFunction()
	{
		$str = "";
		$ar = func_get_args();
		if (is_array($ar))
			$str .= implode(" || ", $ar);
		return $str;
	}

	static public function getIsNullFunction($expression, $result)
	{
		return "NVL(".$expression.", ".$result.")";
	}

	static public function getLengthFunction($field)
	{
		return "LENGTH(".$field.")";
	}

	static public function getCharToDateFunction($value)
	{
		return "TO_DATE('".$value."', 'YYYY-MM-DD HH24:MI:SS')";
	}

	static public function getDateToCharFunction($fieldName)
	{
		return "TO_CHAR(".$fieldName.", 'YYYY-MM-DD HH24:MI:SS')";
	}

	public function prepareInsert($tableName, $arFields)
	{
		$strInsert1 = "";
		$strInsert2 = "";
		$arBinds = array();

		$arColumns = $this->dbConnection->getTableFields($tableName);
		foreach ($arColumns as $columnName => $columnInfo)
		{
			if (array_key_exists($columnName, $arFields))
			{
				$val = $arFields[$columnName];
				$strInsert1 .= ", ".$columnName;
				$strInsert2 .= ", ".$this->convertValueToDb($val, $columnInfo);
				if (($columnInfo["TYPE"] == "CLOB") && ($val != null) && (strlen($val) > 0))
					$arBinds[] = $columnName;
			}
			elseif (array_key_exists("~".$columnName, $arFields))
			{
				$strInsert1 .= ", ".$columnName;
				$strInsert2 .= ", ".$arFields["~".$columnName];
			}
		}

		if ($strInsert1 != "")
		{
			$strInsert1 = " ".substr($strInsert1, 2)." ";
			$strInsert2 = " ".substr($strInsert2, 2)." ";
		}

		return array($strInsert1, $strInsert2, $arBinds);
	}

	public function prepareBinds($tableName, $fields)
	{
		$binds = array();
		$columns = $this->dbConnection->getTableFields($tableName);

		foreach ($columns as $columnName => $columnInfo)
		{
			if (array_key_exists($columnName, $fields) && !($fields[$columnName] instanceof SqlExpression))
			{
				if (($columnInfo["TYPE"] == "CLOB") && ($fields[$columnName] !== null) && (strlen($fields[$columnName]) > 0))
				{
					$binds[$columnName] = $fields[$columnName];
				}
			}
		}

		return $binds;
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
			case "DATE":
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
			case "CLOB":
				if (empty($value))
				{
					$result = "NULL";
				}
				else
				{
					$result = "EMPTY_CLOB()";
				}
				break;
			case "NUMBER":
				if (strlen($columnInfo["DATA_SCALE"]) <= 0)
				{
					$result = doubleval($value);
				}
				elseif (intval($columnInfo["DATA_SCALE"]) <= 0)
				{
					$result = intval($value);
				}
				else
				{
					$result = round(doubleval($value), $columnInfo["DATA_SCALE"]);
				}

				if ($columnInfo["DATA_PRECISION"] > 0 && strlen(intval($result)) > intval($columnInfo["DATA_PRECISION"])-intval($columnInfo["DATA_SCALE"]))
				{
					$result = intval(str_repeat('9', $columnInfo["DATA_PRECISION"] - $columnInfo["DATA_SCALE"]));
				}
				break;

			case "VARCHAR2":
			case "CHAR":
				$result = "'".$this->forSql($value, $columnInfo["CHAR_LENGTH"])."'";
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

	static public function getAscendingOrder()
	{
		return 'ASC NULLS FIRST';
	}

	static public function getDescendingOrder()
	{
		return 'DESC NULLS LAST';
	}
}
