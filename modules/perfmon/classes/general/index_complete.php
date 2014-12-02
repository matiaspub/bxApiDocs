<?php

class CPerfomanceIndexComplete
{
	public static function GetList($arFilter = array(), $arOrder = array())
	{
		global $DB;

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
				$arSelect[] = $strColumn;
				$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
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
		));

		if (!is_array($arFilter))
			$arFilter = array();
		$strQueryWhere = $obQueryWhere->GetQuery($arFilter);

		$strSql = "
			SELECT *
			FROM b_perf_index_complete s
			".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
			".(count($arQueryOrder)? "ORDER BY ".implode(", ", $arQueryOrder): "")."
		";
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;
	}

	public static function Add($arFields)
	{
		global $DB;
		$ID = $DB->Add("b_perf_index_complete", $arFields);
		return $ID;
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);
		$DB->Query("DELETE FROM b_perf_index_complete WHERE ID = ".$ID);
	}

	public static function DeleteByTableName($table, $columns)
	{
		global $DB;
		$DB->Query("
			delete
			from b_perf_index_complete
			where TABLE_NAME = '".$DB->ForSQL($table)."'
			AND COLUMN_NAMES = '".$DB->ForSQL($columns)."'
		");
	}

	public static function IsBanned($table, $columns)
	{
		global $DB;
		$rs = $DB->Query("
			select *
			from b_perf_index_complete
			where TABLE_NAME = '".$DB->ForSQL($table)."'
			AND COLUMN_NAMES = '".$DB->ForSQL($columns)."'
			AND BANNED = 'Y'
		");
		return is_array($rs->Fetch());
	}
}
