<?php

class CPerfomanceComponent
{
	public static function GetList($arOrder, $arFilter, $bGroup, $arNavStartParams, $arSelect)
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
				"HIT_ID" => "DESC",
				"NN" => "ASC",
			);

		$arQueryOrder = array();
		foreach ($arOrder as $strColumn => $strDirection)
		{
			$strColumn = strtoupper($strColumn);
			if (preg_match("/^(MIN|MAX|AVG|SUM)_(.*)$/", $strColumn, $arMatch))
			{
				$strGroupFunc = $arMatch[1];
				$strColumn = $arMatch[2];
			}
			else
			{
				$strGroupFunc = "";
			}

			$strDirection = strtoupper($strDirection) == "ASC"? "ASC": "DESC";
			switch ($strColumn)
			{
			case "ID":
			case "HIT_ID":
			case "NN":
			case "CACHE_TYPE":
			case "COMPONENT_NAME":
				if ($strGroupFunc == "")
				{
					$arSelect[] = $strColumn;
					$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
				}
				break;
			case "CACHE_SIZE":
			case "CACHE_COUNT_R":
			case "CACHE_COUNT_W":
			case "CACHE_COUNT_C":
			case "COMPONENT_TIME":
			case "QUERIES":
			case "QUERIES_TIME":
				if ($strGroupFunc == "")
				{
					if (!$bGroup)
					{
						$arSelect[] = $strColumn;
						$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
					}
				}
				else
				{
					if ($bGroup)
					{
						$arSelect[] = $strColumn;
						$arQueryOrder[$strGroupFunc."_".$strColumn] = $strGroupFunc."_".$strColumn." ".$strDirection;
					}
				}
				break;
			case "COUNT":
				if ($bGroup)
				{
					$arSelect[] = $strColumn;
					$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
				}
				break;
			}
		}

		$arQueryGroup = array();
		$arQuerySelect = array();
		foreach ($arSelect as $strColumn)
		{
			$strColumn = strtoupper($strColumn);
			if (preg_match("/^(MIN|MAX|AVG|SUM)_(.*)$/", $strColumn, $arMatch))
			{
				$strGroupFunc = $arMatch[1];
				$strColumn = $arMatch[2];
			}
			else
			{
				$strGroupFunc = "";
			}

			switch ($strColumn)
			{
			case "ID":
			case "HIT_ID":
			case "NN":
			case "CACHE_TYPE":
			case "COMPONENT_NAME":
				if ($strGroupFunc == "")
				{
					if ($bGroup)
						$arQueryGroup[$strColumn] = "c.".$strColumn;
					$arQuerySelect[$strColumn] = "c.".$strColumn;
				}
				break;
			case "CACHE_SIZE":
			case "CACHE_COUNT_R":
			case "CACHE_COUNT_W":
			case "CACHE_COUNT_C":
			case "COMPONENT_TIME":
			case "QUERIES":
			case "QUERIES_TIME":
				if ($strGroupFunc == "")
				{
					if (!$bGroup)
						$arQuerySelect[$strColumn] = "c.".$strColumn;
				}
				else
				{
					if ($bGroup)
						$arQuerySelect[$strGroupFunc."_".$strColumn] = $strGroupFunc."(c.".$strColumn.") ".$strGroupFunc."_".$strColumn;
				}
				break;
			case "CACHE_COUNT":
				if ($strGroupFunc == "")
				{
					if (!$bGroup)
						$arQuerySelect[$strColumn] = "(c.CACHE_COUNT_R + c.CACHE_COUNT_W + c.CACHE_COUNT_C) CACHE_COUNT";
				}
				else
				{
					if ($bGroup)
						$arQuerySelect[$strGroupFunc."_".$strColumn] = $strGroupFunc."(c.CACHE_COUNT_R + c.CACHE_COUNT_W + c.CACHE_COUNT_C) ".$strGroupFunc."_".$strColumn;
				}
				break;
			case "COUNT":
				if ($strGroupFunc == "" && $bGroup)
				{
					$arQuerySelect[$strColumn] = "COUNT(c.ID) ".$strColumn;
				}
				break;
			}
		}

		$obQueryWhere = new CSQLWhere;
		static $arWhereFields = array(
			"HIT_ID" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.HIT_ID",
				"FIELD_TYPE" => "int", //int, double, file, enum, int, string, date, datetime
				"JOIN" => false,
				//"LEFT_JOIN" => "lt",
			),
			"COMPONENT_NAME" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.COMPONENT_NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"ID" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"CACHE_TYPE" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.CACHE_TYPE",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"CACHE_SIZE" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.CACHE_SIZE",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"QUERIES" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.QUERIES",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"HIT_SCRIPT_NAME" => array(
				"TABLE_ALIAS" => "h",
				"FIELD_NAME" => "h.SCRIPT_NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => "INNER JOIN b_perf_hit h on h.ID = c.HIT_ID",
			),
			"HIT_IS_ADMIN" => array(
				"TABLE_ALIAS" => "h",
				"FIELD_NAME" => "h.IS_ADMIN",
				"FIELD_TYPE" => "string",
				"JOIN" => "INNER JOIN b_perf_hit h on h.ID = c.HIT_ID",
			),
			"HIT_CACHE_TYPE" => array(
				"TABLE_ALIAS" => "h",
				"FIELD_NAME" => "h.CACHE_TYPE",
				"FIELD_TYPE" => "string",
				"JOIN" => "INNER JOIN b_perf_hit h on h.ID = c.HIT_ID",
			),
			"HIT_MENU_RECALC" => array(
				"TABLE_ALIAS" => "h",
				"FIELD_NAME" => "h.MENU_RECALC",
				"FIELD_TYPE" => "int",
				"JOIN" => "INNER JOIN b_perf_hit h on h.ID = c.HIT_ID",
			),
		);
		$obQueryWhere->SetFields($arWhereFields);

		if (count($arQuerySelect) < 1)
			$arQuerySelect = array("ID" => "c.ID");

		$strQueryWhere = $obQueryWhere->GetQuery($arFilter);
		$strHaving = "";
		if (
			$bGroup
			&& count($arQueryGroup) > 0
			&& array_key_exists(">COUNT", $arFilter)
		)
		{
			$strHaving = "HAVING COUNT(*) > ".intval($arFilter["COUNT"])."";
		}

		if (is_array($arNavStartParams) && $arNavStartParams["nTopCount"] > 0)
		{
			$strSql = $DB->TopSQL("
				SELECT ".implode(", ", $arQuerySelect)."
				FROM b_perf_component c
				".$obQueryWhere->GetJoins()."
				".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
				".($bGroup? "GROUP BY ".implode(", ", $arQueryGroup): "")."
				".$strHaving."
				".(count($arQueryOrder)? "ORDER BY ".implode(", ", $arQueryOrder): "")."
			", $arNavStartParams["nTopCount"]);
			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		elseif (is_array($arNavStartParams))
		{
			$strSql = "
				SELECT count('x') CNT
				FROM (
					SELECT 1 FROM b_perf_component c
					".$obQueryWhere->GetJoins()."
					".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
					".($bGroup? "GROUP BY ".implode(", ", $arQueryGroup): "")."
					".$strHaving."
				) t
			";
			$res_cnt = $DB->Query($strSql);
			$ar_cnt = $res_cnt->Fetch();

			$strSql = "
				SELECT ".implode(", ", $arQuerySelect)."
				FROM b_perf_component c
				".$obQueryWhere->GetJoins()."
				".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
				".($bGroup? "GROUP BY ".implode(", ", $arQueryGroup): "")."
				".$strHaving."
				".(count($arQueryOrder)? "ORDER BY ".implode(", ", $arQueryOrder): "")."
			";
			$res = new CDBResult();
			$res->NavQuery($strSql, $ar_cnt["CNT"], $arNavStartParams);
		}
		else
		{
			$strSql = "
				SELECT ".implode(", ", $arQuerySelect)."
				FROM b_perf_component c
				".$obQueryWhere->GetJoins()."
				".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
				".($bGroup? "GROUP BY ".implode(", ", $arQueryGroup): "")."
				".$strHaving."
				".(count($arQueryOrder)? "ORDER BY ".implode(", ", $arQueryOrder): "")."
			";
			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $res;
	}

	public static function Clear()
	{
		global $DB;
		return $DB->Query("TRUNCATE TABLE b_perf_component");
	}
}
