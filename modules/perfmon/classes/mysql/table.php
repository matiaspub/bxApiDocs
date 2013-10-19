<?
class CPerfomanceTableList extends CDBResult
{
	public static function GetList($bFull = true)
	{
		global $DB;
		if($bFull)
			$rsTables = $DB->Query("show table status");
		else
			$rsTables = $DB->Query("show tables from `".$DB->ForSQL($DB->DBName)."`");
		return new CPerfomanceTableList($rsTables);
	}

	public static function Fetch()
	{
		global $DB;
		$ar = parent::Fetch();
		if($ar)
		{
			if(isset($ar["Tables_in_".$DB->DBName]))
				$ar = array(
					"TABLE_NAME" => $ar["Tables_in_".$DB->DBName],
					"ENGINE_TYPE" => "",
					"NUM_ROWS" => "",
					"BYTES" => "",
				);
			else
				$ar = array(
					"TABLE_NAME" => $ar["Name"],
					"ENGINE_TYPE" => $ar["Engine"],
					"NUM_ROWS" => $ar["Rows"],
					"BYTES" => $ar["Data_length"],
				);
		}
		return $ar;
	}
}

class CPerfomanceTable extends CAllPerfomanceTable
{
	var $TABLE_NAME;

	public function Init($TABLE_NAME)
	{
		$this->TABLE_NAME = $TABLE_NAME;
	}

	public function IsExists($TABLE_NAME = false)
	{
		if($TABLE_NAME===false)
			$TABLE_NAME = $this->TABLE_NAME;
		if(strlen($TABLE_NAME) <= 0)
			return false;
		global $DB;
		$strSql = "
			SHOW TABLES LIKE '".$DB->ForSQL($TABLE_NAME)."'
		";
		$rs = $DB->Query($strSql);
		if($rs->Fetch())
			return true;
		else
			return false;
	}

	public function GetIndexes($TABLE_NAME = false)
	{
		static $cache = array();

		if($TABLE_NAME===false)
			$TABLE_NAME = $this->TABLE_NAME;

		if(!array_key_exists($TABLE_NAME, $cache))
		{
			global $DB;

			$strSql = "
				SHOW INDEXES FROM `".$DB->ForSQL($TABLE_NAME)."`
			";
			$rs = $DB->Query($strSql);
			$arResult = array();
			while($ar = $rs->Fetch())
			{
				$arResult[$ar["Key_name"]][$ar["Seq_in_index"]] = $ar["Column_name"];
			}
			$cache[$TABLE_NAME] = $arResult;
		}

		return $cache[$TABLE_NAME];
	}

	public function GetUniqueIndexes($TABLE_NAME = false)
	{
		static $cache = array();

		if($TABLE_NAME===false)
			$TABLE_NAME = $this->TABLE_NAME;

		if(!array_key_exists($TABLE_NAME, $cache))
		{
			global $DB;

			$strSql = "
				SHOW INDEXES FROM `".$DB->ForSQL($TABLE_NAME)."`
			";
			$rs = $DB->Query($strSql);
			$arResult = array();
			while($ar = $rs->Fetch())
			{
				if(!$ar["Non_unique"])
					$arResult[$ar["Key_name"]][$ar["Seq_in_index"]] = $ar["Column_name"];
			}
			$cache[$TABLE_NAME] = $arResult;
		}

		return $cache[$TABLE_NAME];
	}

	public function GetTableFields($TABLE_NAME = false, $bExtended = false)
	{
		static $cache = array();

		if($TABLE_NAME===false)
			$TABLE_NAME = $this->TABLE_NAME;

		if(!array_key_exists($TABLE_NAME, $cache))
		{
			global $DB;

			$strSql = "
				SHOW COLUMNS FROM `".$DB->ForSQL($TABLE_NAME)."`
			";
			$rs = $DB->Query($strSql);
			$arResult = array();
			$arResultExt = array();
			while($ar = $rs->Fetch())
			{
				$canSort = true;
				if(preg_match("/^(varchar|char)\\((\\d+)\\)/", $ar["Type"], $match))
				{
					$ar["DATA_TYPE"] = "string";
					$ar["DATA_LENGTH"] = $match[2];
				}
				elseif(preg_match("/^(varchar|char)/", $ar["Type"]))
				{
					$ar["DATA_TYPE"] = "string";
				}
				elseif(preg_match("/^(text|longtext|mediumtext)/", $ar["Type"]))
				{
					$canSort = false;
					$ar["DATA_TYPE"] = "string";
				}
				elseif(preg_match("/^(datetime|timestamp)/", $ar["Type"]))
				{
					$ar["DATA_TYPE"] = "datetime";
				}
				elseif(preg_match("/^(date)/", $ar["Type"]))
				{
					$ar["DATA_TYPE"] = "date";
				}
				elseif(preg_match("/^(int|smallint|bigint)/", $ar["Type"]))
				{
					$ar["DATA_TYPE"] = "int";
				}
				elseif(preg_match("/^float/", $ar["Type"]))
				{
					$ar["DATA_TYPE"] = "double";
				}
				else
				{
					$canSort = false;
					$ar["DATA_TYPE"] = "unknown";
				}
				$arResult[$ar["Field"]] = $ar["DATA_TYPE"];
				$arResultExt[$ar["Field"]] = array(
					"type" => $ar["DATA_TYPE"],
					"length" => $ar["DATA_LENGTH"],
					"nullable" => $ar["Null"] !== "NO",
					"default" => $ar["Default"],
					"sortable" => $canSort,
					//"info" => $ar,
				);
			}
			$cache[$TABLE_NAME] = array($arResult, $arResultExt);
		}

		if($bExtended)
			return $cache[$TABLE_NAME][1];
		else
			return $cache[$TABLE_NAME][0];
	}

	public static function NavQuery($arNavParams, $arQuerySelect, $strTableName, $strQueryWhere, $arQueryOrder)
	{
		global $DB;
		if(IntVal($arNavParams["nTopCount"]) <= 0)
		{
			$strSql = "
				SELECT
					count(1) C
				FROM
					".$strTableName." t
			";
			if($strQueryWhere)
			{
				$strSql .= "
					WHERE
					".$strQueryWhere."
				";
			}
			$res_cnt = $DB->Query($strSql);
			$res_cnt = $res_cnt->Fetch();
			$cnt = $res_cnt["C"];

			$strSql = "
				SELECT
				".implode(", ", $arQuerySelect)."
				FROM
					".$strTableName." t
			";
			if($strQueryWhere)
			{
				$strSql .= "
					WHERE
					".$strQueryWhere."
				";
			}
			if(count($arQueryOrder) > 0)
			{
				$strSql .= "
					ORDER BY
					".implode(", ", $arQueryOrder)."
				";
			}

			$res = new CDBResult();
			$res->NavQuery($strSql, $cnt, $arNavParams);

			return $res;
		}
		else
		{
			$strSql = "
				SELECT
				".implode(", ", $arQuerySelect)."
				FROM
					".$strTableName." t
			";
			if($strQueryWhere)
			{
				$strSql .= "
					WHERE
					".$strQueryWhere."
				";
			}
			if(count($arQueryOrder) > 0)
			{
				$strSql .= "
					ORDER BY
					".implode(", ", $arQueryOrder)."
				";
			}
			$strSql = $strSql." LIMIT ".IntVal($arNavParams["nTopCount"]);
			return $DB->Query($strSql);
		}
	}


	public static function escapeColumn($column)
	{
		return "`".$column."`";
	}
}
?>