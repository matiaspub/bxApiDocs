<?php

class CPerfomanceIndexSuggest
{
	public static function GetList($arSelect, $arFilter, $arOrder)
	{
		global $DB;

		if (!is_array($arSelect))
			$arSelect = array();
		if (count($arSelect) < 1)
			$arSelect = array(
				"ID",
			);

		if (!is_array($arOrder))
			$arOrder = array();
		if (count($arOrder) < 1)
			$arOrder = array(
				"TABLE_NAME" => "ASC",
			);

		$arQueryOrder = array();
		foreach ($arOrder as $strColumn => $strDirection)
		{
			$strColumn = strtoupper($strColumn);
			$strDirection = strtoupper($strDirection) == "ASC"? "ASC": "DESC";
			switch ($strColumn)
			{
			case "ID":
			case "TABLE_NAME":
			case "SQL_COUNT":
			case "SQL_TIME":
				$arSelect[] = $strColumn;
				$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
				break;
			}
		}

		$bJoin = false;
		$arQuerySelect = array();
		foreach ($arSelect as $strColumn)
		{
			$strColumn = strtoupper($strColumn);
			switch ($strColumn)
			{
			case "ID":
			case "TABLE_NAME":
			case "TABLE_ALIAS":
			case "COLUMN_NAMES":
			case "SQL_MD5":
			case "SQL_TEXT":
			case "SQL_COUNT":
			case "SQL_TIME":
			case "SQL_EXPLAIN":
				$arQuerySelect[$strColumn] = "s.".$strColumn;
				break;
			case "BANNED":
				$arQuerySelect[$strColumn] = "c.".$strColumn;
				$bJoin = true;
				break;
			}
		}

		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields(array(
			"ID" => array(
				"TABLE_ALIAS" => "s",
				"FIELD_NAME" => "ID",
				"FIELD_TYPE" => "int", //int, double, file, enum, int, string, date, datetime
				"JOIN" => false,
				//"LEFT_JOIN" => "lt",
			),
			"SQL_MD5" => array(
				"TABLE_ALIAS" => "s",
				"FIELD_NAME" => "s.SQL_MD5",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"TABLE_NAME" => array(
				"TABLE_ALIAS" => "s",
				"FIELD_NAME" => "s.TABLE_NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"COLUMN_NAMES" => array(
				"TABLE_ALIAS" => "s",
				"FIELD_NAME" => "s.COLUMN_NAMES",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"BANNED" => array(
				"TABLE_ALIAS" => "c1",
				"FIELD_NAME" => "c1.BANNED",
				"FIELD_TYPE" => "string",
				"JOIN" => "LEFT JOIN b_perf_index_complete c1 on c1.TABLE_NAME = s.TABLE_NAME and c1.COLUMN_NAMES = s.COLUMN_NAMES",
			),
		));

		if (count($arQuerySelect) < 1)
			$arQuerySelect = array("ID" => "s.ID");

		if (!is_array($arFilter))
			$arFilter = array();
		$strQueryWhere = $obQueryWhere->GetQuery($arFilter);

		$strSql = "
			SELECT ".implode(", ", $arQuerySelect)."
			FROM b_perf_index_suggest s
			".$obQueryWhere->GetJoins()."
			".($bJoin? "LEFT JOIN b_perf_index_complete c on c.TABLE_NAME = s.TABLE_NAME and c.COLUMN_NAMES = s.COLUMN_NAMES": "")."
			".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
			".(count($arQueryOrder)? "ORDER BY ".implode(", ", $arQueryOrder): "")."
		";
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;
	}

	public static function Add($arFields)
	{
		global $DB;
		$ID = $DB->Add("b_perf_index_suggest", $arFields);
		return $ID;
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);
		$DB->Query("DELETE FROM b_perf_index_suggest_sql WHERE SUGGEST_ID = ".$ID);
		$DB->Query("DELETE FROM b_perf_index_suggest WHERE ID = ".$ID);
	}

	public static function UpdateStat($sql_md5, $count, $query_time, $sql_id)
	{
		global $DB;
		$res = $DB->Query("
			INSERT INTO b_perf_index_suggest_sql (
				SUGGEST_ID, SQL_ID
			) SELECT iss.ID,s.ID
			FROM b_perf_index_suggest iss
			,b_perf_sql s
			WHERE iss.SQL_MD5 = '".$DB->ForSQL($sql_md5)."'
			AND s.ID = ".intval($sql_id)."
		");
		if (is_object($res))
		{
			$DB->Query("
				UPDATE b_perf_index_suggest
				SET SQL_COUNT = SQL_COUNT + ".intval($count).",
				SQL_TIME = SQL_TIME + ".floatval($query_time)."
				WHERE SQL_MD5 = '".$DB->ForSQL($sql_md5)."'
			");
		}
	}

	public static function Clear()
	{
		global $DB;
		$DB->Query("TRUNCATE TABLE b_perf_tab_stat");
		$DB->Query("TRUNCATE TABLE b_perf_tab_column_stat");
		$DB->Query("TRUNCATE TABLE b_perf_index_suggest");
		$DB->Query("TRUNCATE TABLE b_perf_index_suggest_sql");
	}
}
