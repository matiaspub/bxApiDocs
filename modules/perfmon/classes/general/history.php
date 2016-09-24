<?php

class CPerfomanceHistory
{
	public static function GetList($arOrder, $arFilter = array())
	{
		global $DB;

		if (!is_array($arOrder))
			$arOrder = array();
		if (count($arOrder) < 1)
			$arOrder = array(
				"ID" => "DESC",
			);

		$arQueryOrder = array();
		foreach ($arOrder as $strColumn => $strDirection)
		{
			$strColumn = strtoupper($strColumn);
			$strDirection = strtoupper($strDirection) == "ASC"? "ASC": "DESC";
			switch ($strColumn)
			{
			case "ID":
				$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
				break;
			}
		}

		static $arWhereFields = array(
			"ID" => array(
				"TABLE_ALIAS" => "h",
				"FIELD_NAME" => "ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
		);

		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields($arWhereFields);

		$strSql = "
			SELECT
				h.*
				,".$DB->DateToCharFunction("h.TIMESTAMP_X")." TIMESTAMP_X
			FROM
				b_perf_history h
		";
		if (!is_array($arFilter))
			$arFilter = array();
		if ($strQueryWhere = $obQueryWhere->GetQuery($arFilter))
		{
			$strSql .= "
				WHERE
				".$strQueryWhere."
			";
		}
		if (count($arQueryOrder) > 0)
		{
			$strSql .= "
				ORDER BY
				".implode(", ", $arQueryOrder)."
			";
		}

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);
		return $DB->Query("DELETE FROM b_perf_history WHERE ID = ".$ID);
	}

	public static function Add($arFields)
	{
		global $DB;

		if ($arFields["TOTAL_MARK"] > 0)
		{
			$arFields["ACCELERATOR_ENABLED"] = $arFields["ACCELERATOR_ENABLED"] === "Y"? "Y": "N";
			$arFields["~TIMESTAMP_X"] = $DB->CurrentTimeFunction();
			return $DB->Add("b_perf_history", $arFields);
		}
		else
		{
			return false;
		}
	}
}
