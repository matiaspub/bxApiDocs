<?php

class CPerfomanceTableList extends CDBResult
{
	public static function GetList($bFull = true)
	{
		global $DB;
		if ($bFull)
			$rsTables = $DB->Query("show table status");
		else
			$rsTables = $DB->Query("show tables from ".CPerfomanceTable::escapeTable($DB->DBName));
		return new CPerfomanceTableList($rsTables);
	}

	public static function Fetch()
	{
		global $DB;
		$ar = parent::Fetch();
		if ($ar)
		{
			if (isset($ar["Tables_in_".$DB->DBName]))
				$ar = array(
					"TABLE_NAME" => $ar["Tables_in_".$DB->DBName],
					"ENGINE_TYPE" => "",
					"NUM_ROWS" => "",
					"BYTES" => "",
				);
			else
				$ar = array(
					"TABLE_NAME" => $ar["Name"],
					"ENGINE_TYPE" => $ar["Comment"] === "VIEW"? "VIEW": $ar["Engine"],
					"NUM_ROWS" => $ar["Rows"],
					"BYTES" => $ar["Data_length"],
				);
		}
		return $ar;
	}
}

class CPerfomanceTable extends CAllPerfomanceTable
{
	public function Init($TABLE_NAME)
	{
		$TABLE_NAME = trim($TABLE_NAME, "`");
		$this->TABLE_NAME = $TABLE_NAME;
	}

	public function IsExists($TABLE_NAME = false)
	{
		global $DB;

		if ($TABLE_NAME === false)
			$TABLE_NAME = $this->TABLE_NAME;
		if (strlen($TABLE_NAME) <= 0)
			return false;

		$TABLE_NAME = trim($TABLE_NAME, "`");

		$strSql = "
			SHOW TABLES LIKE '".$DB->ForSQL($TABLE_NAME)."'
		";
		$rs = $DB->Query($strSql);
		if ($rs->Fetch())
			return true;
		else
			return false;
	}

	public function GetIndexes($TABLE_NAME = false)
	{
		global $DB;
		static $cache = array();

		if ($TABLE_NAME === false)
			$TABLE_NAME = $this->TABLE_NAME;
		if (strlen($TABLE_NAME) <= 0)
			return array();

		$TABLE_NAME = trim($TABLE_NAME, "`");

		if (!array_key_exists($TABLE_NAME, $cache))
		{
			$strSql = "SHOW INDEXES FROM ".$this->escapeTable($TABLE_NAME);
			$arResult = array();
			$rsInd = $DB->Query($strSql, true);
			if ($rsInd)
			{
				while ($arInd = $rsInd->Fetch())
				{
					$arResult[$arInd["Key_name"]][$arInd["Seq_in_index"]] = $arInd["Column_name"];
				}
			}
			$cache[$TABLE_NAME] = $arResult;
		}

		return $cache[$TABLE_NAME];
	}

	public function GetUniqueIndexes($TABLE_NAME = false)
	{
		global $DB;
		static $cache = array();

		if ($TABLE_NAME === false)
			$TABLE_NAME = $this->TABLE_NAME;
		if (strlen($TABLE_NAME) <= 0)
			return array();

		$TABLE_NAME = trim($TABLE_NAME, "`");

		if (!array_key_exists($TABLE_NAME, $cache))
		{
			$strSql = "SHOW INDEXES FROM ".$this->escapeTable($TABLE_NAME);
			$arResult = array();
			$rsInd = $DB->Query($strSql, true);
			if ($rsInd)
			{
				while ($arInd = $rsInd->Fetch())
				{
					if (!$arInd["Non_unique"])
						$arResult[$arInd["Key_name"]][$arInd["Seq_in_index"]] = $arInd["Column_name"];
				}
			}
			$cache[$TABLE_NAME] = $arResult;
		}

		return $cache[$TABLE_NAME];
	}

	public function GetTableFields($TABLE_NAME = false, $bExtended = false)
	{
		static $cache = array();

		if ($TABLE_NAME === false)
			$TABLE_NAME = $this->TABLE_NAME;
		if (strlen($TABLE_NAME) <= 0)
			return false;

		$TABLE_NAME = trim($TABLE_NAME, "`");

		if (!array_key_exists($TABLE_NAME, $cache))
		{
			global $DB;

			$strSql = "SHOW COLUMNS FROM ".$this->escapeTable($TABLE_NAME);
			$rs = $DB->Query($strSql);
			$arResult = array();
			$arResultExt = array();
			while ($ar = $rs->Fetch())
			{
				$canSort = true;
				$match = array();
				if (preg_match("/^(varchar|char|varbinary)\\((\\d+)\\)/", $ar["Type"], $match))
				{
					$ar["DATA_TYPE"] = "string";
					$ar["DATA_LENGTH"] = $match[2];
					if ($match[2] == 1 && ($ar["Default"] === "N" || $ar["Default"] === "Y"))
						$ar["ORM_DATA_TYPE"] = "boolean";
					else
						$ar["ORM_DATA_TYPE"] = "string";
				}
				elseif (preg_match("/^(varchar|char)/", $ar["Type"]))
				{
					$ar["DATA_TYPE"] = "string";
					$ar["ORM_DATA_TYPE"] = "string";
				}
				elseif (preg_match("/^(text|longtext|mediumtext|longblob)/", $ar["Type"]))
				{
					$canSort = false;
					$ar["DATA_TYPE"] = "string";
					$ar["ORM_DATA_TYPE"] = "text";
				}
				elseif (preg_match("/^(datetime|timestamp)/", $ar["Type"]))
				{
					$ar["DATA_TYPE"] = "datetime";
					$ar["ORM_DATA_TYPE"] = "datetime";
				}
				elseif (preg_match("/^(date)/", $ar["Type"]))
				{
					$ar["DATA_TYPE"] = "date";
					$ar["ORM_DATA_TYPE"] = "date";
				}
				elseif (preg_match("/^(int|smallint|bigint|tinyint)/", $ar["Type"]))
				{
					$ar["DATA_TYPE"] = "int";
					$ar["ORM_DATA_TYPE"] = "integer";
				}
				elseif (preg_match("/^(float|double|decimal)/", $ar["Type"]))
				{
					$ar["DATA_TYPE"] = "double";
					$ar["ORM_DATA_TYPE"] = "float";
				}
				else
				{
					$canSort = false;
					$ar["DATA_TYPE"] = "unknown";
					$ar["ORM_DATA_TYPE"] = "UNKNOWN";
				}
				$arResult[$ar["Field"]] = $ar["DATA_TYPE"];
				$arResultExt[$ar["Field"]] = array(
					"type" => $ar["DATA_TYPE"],
					"length" => $ar["DATA_LENGTH"],
					"nullable" => $ar["Null"] !== "NO",
					"default" => $ar["Default"],
					"sortable" => $canSort,
					"orm_type" => $ar["ORM_DATA_TYPE"],
					"increment" => ($ar["Extra"] === "auto_increment"),
					//"info" => $ar,
				);
			}
			$cache[$TABLE_NAME] = array($arResult, $arResultExt);
		}

		if ($bExtended)
			return $cache[$TABLE_NAME][1];
		else
			return $cache[$TABLE_NAME][0];
	}

	public static function escapeColumn($column)
	{
		return "`".str_replace("`", "``", $column)."`";
	}

	public static function escapeTable($tableName)
	{
		return "`".str_replace("`", "``", $tableName)."`";
	}

	public function getCreateIndexDDL($TABLE_NAME, $INDEX_NAME, $INDEX_COLUMNS)
	{
		$tableFields = $this->GetTableFields($TABLE_NAME, true);
		foreach ($INDEX_COLUMNS as $i => $field)
		{
			if ($tableFields[trim($field, '`[]"')]["orm_type"] === "text")
			{
				$INDEX_COLUMNS[$i] = $field."(100)";
			}
		}
		return parent::getCreateIndexDDL($TABLE_NAME, $INDEX_NAME, $INDEX_COLUMNS);
	}
}
