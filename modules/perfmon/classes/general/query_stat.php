<?php

class CPerfQueryStat
{
	public static function IsBanned($table, $columns)
	{
		global $DB;
		$rs = $DB->Query("
			select *
			from b_perf_index_ban
			where TABLE_NAME = '".$DB->ForSQL($table)."'
			AND COLUMN_NAMES = '".$DB->ForSQL($columns)."'
		");
		return is_array($rs->Fetch());
	}

	public static function Ban($table, $columns)
	{
		global $DB;
		$DB->Add("b_perf_index_ban", array(
			"BAN_TYPE" => "A",
			"TABLE_NAME" => $table,
			"COLUMN_NAMES" => $columns,
		));
	}

	public static function GetTableColumns($table)
	{
		global $DB;
		static $cache = array();
		$table = trim($table, "`");

		if (!array_key_exists($table, $cache))
		{
			$strSql = "SHOW COLUMNS FROM `".$DB->ForSQL($table)."`";
			$rs = $DB->Query($strSql);

			$arResult = array();
			while ($ar = $rs->Fetch())
				$arResult[$ar["Field"]] = $ar;

			$cache[$table] = $arResult;
		}
		return $cache[$table];
	}

	/**
	 * @param string $table
	 * @param array $columns
	 * @param CPerfQuery $q
	 * @return boolean
	 */
	public static function GatherExpressStat($table, $columns, $q)
	{
		$arColumns = explode(",", $columns);
		if (count($arColumns) != 1)
			return false;
		
		$column = trim($arColumns[0], "`");
		$value = trim($q->find_value($table, $arColumns[0]), "'");

		if ($value == "")
			return false;

		$tab = new CPerfomanceTable;
		$tab->Init($table);
		if ($tab->IsExists())
		{
			$arTableColumns = CPerfQueryStat::GetTableColumns($table);
			if (!array_key_exists($column, $arTableColumns))
				return false; //May be it is worth to ban

			if ($arTableColumns[$column]["Type"] === "char(1)")
			{
				if (is_array(CPerfQueryStat::_get_stat($table, $arColumns[0])))
					return true;

				if (CPerfQueryStat::_gather_stat($table, $arColumns[0], $value, 10 * 1024 * 1024))
					return true;
			}

			return false;
		}
		else
		{
			return false;
		}
	}

	public static function GatherColumnStatByValue($table, $column, $value)
	{
		$tab = new CPerfomanceTable;
		$tab->Init($table);
		if ($tab->IsExists())
		{
			$arStat = CPerfQueryStat::_get_stat($table, $column, $value);
			if (!is_array($arStat))
			{
				CPerfQueryStat::_gather_stat($table, $column, $value, -1);
				$arStat = CPerfQueryStat::_get_stat($table, $column, $value);
			}

			return $arStat;
		}
		else
		{
			return false;
		}
	}

	public static function GatherColumnStatOverall($table, $column)
	{
		$tab = new CPerfomanceTable;
		$tab->Init($table);
		if ($tab->IsExists())
		{
			$arStat = CPerfQueryStat::_get_stat($table, $column, null);
			if (!is_array($arStat))
			{
				CPerfQueryStat::_gather_stat($table, $column, null, -1);
				$arStat = CPerfQueryStat::_get_stat($table, $column, null);
			}

			return $arStat;
		}
		else
		{
			return false;
		}
	}

	public static function GatherTableStat($table)
	{
		global $DB;
		$table = trim($table, "`");

		$arStat = CPerfQueryStat::_get_stat($table);
		if (!$arStat)
		{
			$rs = $DB->Query("show table status like '".$DB->ForSQL($table)."'");
			$arDBStat = $rs->Fetch();
			$DB->Add("b_perf_tab_stat", $arStat = array(
				"TABLE_NAME" => $table,
				"TABLE_SIZE" => $arDBStat["Data_length"],
				"TABLE_ROWS" => $arDBStat["Rows"],
			));
		}
		return $arStat;
	}

	protected static function _gather_stat($table, $column, $value, $max_size = -1)
	{
		global $DB;
		$table = trim($table, "`");
		$column = trim($column, "`");

		$arStat = CPerfQueryStat::GatherTableStat($table);
		if ($max_size < 0 || $arStat["TABLE_SIZE"] < $max_size)
		{
			$table = preg_replace("/[^A-Za-z0-9%_]+/i", "", $table);
			$column = preg_replace("/[^A-Za-z0-9%_]+/i", "", $column);

			if (isset($value))
				$rs = $DB->Query($d="
					select count(1) CNT
					from ".$DB->ForSQL($table)."
					where ".$DB->ForSQL($column)." = '".$DB->ForSQL($value)."'
				");
			else
				$rs = $DB->Query($d="
					select count(distinct ".$DB->ForSQL($column).") CNT
					from ".$DB->ForSQL($table)."
				");

			if ($ar = $rs->Fetch())
			{
				$DB->Add("b_perf_tab_column_stat", array(
					"TABLE_NAME" => $table,
					"COLUMN_NAME" => $column,
					"TABLE_ROWS" => $arStat["TABLE_ROWS"],
					"COLUMN_ROWS" => $ar["CNT"],
					"VALUE" => isset($value)? $value: false,
				));
			}
			return true;
		}
		else
			return false;
	}

	protected static function _get_stat($table, $column = "", $value = "")
	{
		global $DB;
		$table = trim($table, "`");
		$column = trim($column, "`");

		if ($column == "")
		{
			$rs = $DB->Query("
				select *
				from b_perf_tab_stat
				where TABLE_NAME = '".$DB->ForSQL($table)."'
			");
		}
		else
		{
			if (isset($value))
				$where = ($value == ""? "": "AND VALUE = '".$DB->ForSQL($value, 100)."'");
			else
				$where = "AND VALUE IS NULL";

			$rs = $DB->Query("
				select *
				from b_perf_tab_column_stat
				where TABLE_NAME = '".$DB->ForSQL($table)."'
				AND COLUMN_NAME = '".$DB->ForSQL($column)."'
				".$where."
			");
		}

		return $rs->Fetch();
	}

	public static function IsSelective($table, $columns)
	{
		global $DB;

		$arColumns = explode(",", $columns);
		if (count($arColumns) != 1)
			return false;

		$arColumns = array_map(array($DB, 'ForSQL'), $arColumns);
		$rs = $DB->Query("
			select max(TABLE_ROWS) TABLE_ROWS, max(COLUMN_ROWS) COLUMN_ROWS
			from b_perf_tab_column_stat
			where TABLE_NAME = '".$DB->ForSQL($table)."'
			AND COLUMN_NAME in ('".implode("','", $arColumns)."')
		");
		$ar = $rs->Fetch();
		if ($ar && $ar["TABLE_ROWS"] > 0)
			return $ar["COLUMN_ROWS"] / $ar["TABLE_ROWS"] > 0.05;
		else
			return false;
	}
}
