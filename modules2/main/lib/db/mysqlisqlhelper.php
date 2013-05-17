<?php
namespace Bitrix\Main\DB;

class MysqliSqlHelper
	extends SqlHelper
{
	static public function getQueryDelimiter()
	{
		return ';';
	}

	static public function forSql($value, $maxLength = 0)
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

	static public function getDatetimeToDbFunction(\Bitrix\Main\Type\DateTime $value, $type = \Bitrix\Main\Type\DateTime::DATE_WITH_TIME)
	{
		$customOffset = $value->getOffset();

		$serverTime = new \Bitrix\Main\Type\DateTime();
		$serverOffset = $serverTime->getOffset();

		$diff = $customOffset - $serverOffset;
		$valueTmp = clone $value;

		$valueTmp->sub(new \DateInterval(sprintf("PT%sS", $diff)));

		$format = ($type == \Bitrix\Main\Type\DateTime::DATE_WITHOUT_TIME ? "Y-m-d" : "Y-m-d H:i:s");
		$date = "'".$valueTmp->format($format)."'";

		return $date;
	}

	static public function getDateTimeFromDbFunction($fieldName)
	{
		return $fieldName;
	}

	static public function getDatetimeToDateFunction($value)
	{
		return 'DATE('.$value.')';
	}

	static public function getToCharFunction($expr, $length = 0)
	{
		return $expr;
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
				$strInsert1 .= ", `".$columnName."`";
				$strInsert2 .= ", ".$this->convertValueToDb($arFields[$columnName], $columnInfo["TYPE"]);
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

	static public function prepareUpdate($tableName, $arFields)
	{
		$strUpdate = "";

		$arColumns = $this->dbConnection->getTableFields($tableName);
		foreach ($arColumns as $columnName => $columnInfo)
		{
			if (array_key_exists($columnName, $arFields))
				$strUpdate .= ", `".$columnName."` = ".$this->convertValueToDb($arFields[$columnName], $columnInfo["TYPE"]);
			elseif (array_key_exists("~".$columnName, $arFields))
				$strUpdate .= ", `".$columnName."` = ".$arFields["~".$columnName];
		}

		if ($strUpdate != "")
			$strUpdate = " ".substr($strUpdate, 2)." ";

		return array($strUpdate, array());
	}

	protected function convertValueToDb($value, $type)
	{
		if ($value === null)
			return "NULL";

		switch ($type)
		{
			case "datetime":
				if (empty($value))
					$result = "NULL";
				else
					$result = $this->getDatetimeToDbFunction($value, \Bitrix\Main\Type\DateTime::DATE_WITH_TIME);
				break;
			case "date":
				if (empty($value))
					$result = "NULL";
				else
					$result = $this->getDatetimeToDbFunction($value, \Bitrix\Main\Type\DateTime::DATE_WITHOUT_TIME);
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
}
