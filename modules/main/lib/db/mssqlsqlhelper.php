<?php
namespace Bitrix\Main\DB;

class MssqlSqlHelper
	extends SqlHelper
{
	static public function getQueryDelimiter()
	{
		return "\nGO";
	}

	public static function forSql($value, $maxLength = 0)
	{
		return str_replace("\x00", "", ($maxLength > 0) ? str_replace("'", "''", substr($value, 0, $maxLength)) : str_replace("'", "''", $value));
	}

	static public function getCurrentDateTimeFunction()
	{
		return "GETDATE()";
	}

	static public function getCurrentDateFunction()
	{
		return "convert(datetime, cast(year(getdate()) as varchar(4)) + '-' + cast(month(getdate()) as varchar(2)) + '-' + cast(day(getdate()) as varchar(2)), 120)";
	}

	static public function getDatetimeToDateFunction($value)
	{
		return 'DATEADD(dd, DATEDIFF(dd, 0, '.$value.'), 0)';
	}

	static public function getConcatFunction()
	{
		$str = "";
		$ar = func_get_args();
		if (is_array($ar))
			$str .= implode(" + ", $ar);
		return $str;
	}

	static public function getIsNullFunction($expression, $result)
	{
		return "ISNULL(".$expression.", ".$result.")";
	}

	static public function getLengthFunction($field)
	{
		return "LEN(".$field.")";
	}

	static public function getToCharFunction($expr, $length = 0)
	{
		return "CAST(".$expr." AS CHAR".($length > 0 ? "(".$length.")" : "").")";
	}

	static public function getDateTimeToDbFunction(\Bitrix\Main\Type\DateTime $value, $type = \Bitrix\Main\Type\DateTime::DATE_WITH_TIME)
	{
		$customOffset = $value->getOffset();

		$serverTime = new \Bitrix\Main\Type\DateTime();
		$serverOffset = $serverTime->getOffset();

		$diff = $customOffset - $serverOffset;
		$valueTmp = clone $value;

		$valueTmp->sub(new \DateInterval(sprintf("PT%sS", $diff)));

		$format = ($type == \Bitrix\Main\Type\DateTime::DATE_WITHOUT_TIME ? "Y-m-d" : "Y-m-d H:i:s");
		$date = "CONVERT(datetime, '".$valueTmp->format($format)."', 120)";

		return $date;
	}

	static public function getDateTimeFromDbFunction($fieldName)
	{
		return "CONVERT(varchar(19), ".$fieldName.", 120)";
	}

	static public function prepareInsert($tableName, $arFields)
	{
		$strInsert1 = "";
		$strInsert2 = "";

		$arColumns = $this->dbConnection->getTableFields($tableName);
		foreach ($arColumns as $columnName => $columnInfo)
		{
			if (array_key_exists($columnName, $arFields))
			{
				$strInsert1 .= ", ".$columnName."";
				$strInsert2 .= ", ".$this->convertValueToDb($arFields[$columnName], $columnInfo);
			}
			elseif (array_key_exists("~".$columnName, $arFields))
			{
				$strInsert1 .= ", ".$columnName."";
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

	static public function prepareUpdate($tableName, $arFields)
	{
		$strUpdate = "";

		$arColumns = $this->dbConnection->getTableFields($tableName);
		foreach ($arColumns as $columnName => $columnInfo)
		{
			if (array_key_exists($columnName, $arFields))
				$strUpdate .= ", ".$columnName." = ".$this->convertValueToDb($arFields[$columnName], $columnInfo);
			elseif (array_key_exists("~".$columnName, $arFields))
				$strUpdate .= ", ".$columnName." = ".$arFields["~".$columnName];
		}

		if ($strUpdate != "")
			$strUpdate = " ".substr($strUpdate, 2)." ";

		return array($strUpdate, array());
	}

	protected function convertValueToDb($value, $columnInfo)
	{
		if ($value === null)
			return "NULL";

		switch ($columnInfo["TYPE"])
		{
			case "datetime":
			case "timestamp":
				if (empty($value))
					$result = "NULL";
				else
					$result = $this->getDatetimeToDbFunction($value, \Bitrix\Main\Type\DateTime::DATE_WITH_TIME);
				break;
			case "int":
			case "tinyint":
			case "smallint":
			case "bigint":
				$result = "'".intval($value)."'";
				break;
			case "decimal":
			case "numeric":
				$result = "'".round(doubleval($value), intval($columnInfo["NUMERIC_SCALE"]))."'";
				break;
			case "real":
			case "float":
				$result = "'".doubleval($value)."'";
				break;
			case "image":
				$result = $value;
				break;
			default:
				$result = "'".$this->forSql($value, $columnInfo['CHARACTER_MAXIMUM_LENGTH'])."'";
				break;
		}

		return $result;
	}
}
