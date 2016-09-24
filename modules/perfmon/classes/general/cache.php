<?php

class CPerfomanceCache
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
			case "COMPONENT_ID":
			case "NN":
			case "OP_MODE":
			case "MODULE_NAME":
			case "COMPONENT_NAME":
			case "BASE_DIR":
			case "INIT_DIR":
			case "FILE_NAME":
			case "FILE_PATH":
				if ($strGroupFunc == "")
				{
					if ($bGroup)
						$arQueryGroup[$strColumn] = "c.".$strColumn;
					$arQuerySelect[$strColumn] = "c.".$strColumn;
				}
				break;
			case "HIT_RATIO":
				if ($strGroupFunc == "" && $bGroup)
				{
					$sql = "case when sum((case when c.OP_MODE='W' then 1 else 0 end)+(case when c.OP_MODE='R' then 1 else 0 end)) > 0 then sum(case when c.OP_MODE='R' then 1 else 0 end)/sum((case when c.OP_MODE='W' then 1 else 0 end)+(case when c.OP_MODE='R' then 1 else 0 end)) else null end";
					$arQuerySelect[$strColumn] = $sql." ".$strColumn;
				}
				break;
			case "CACHE_PATH":
				if ($strGroupFunc == "")
				{
					if ($bGroup)
						$arQueryGroup[$strColumn] = $DB->Concat("c.BASE_DIR", "c.INIT_DIR", "c.FILE_NAME");
					$arQuerySelect[$strColumn] = $DB->Concat("c.BASE_DIR", "','", "c.INIT_DIR", "','", "c.FILE_NAME")." ".$strColumn;
				}
				break;
			case "CACHE_SIZE":
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
			case "COUNT":
				if ($strGroupFunc == "" && $bGroup)
				{
					$arQuerySelect[$strColumn] = "COUNT(c.ID) ".$strColumn;
				}
				break;
			case "COUNT_R":
				if ($strGroupFunc == "" && $bGroup)
				{
					$arQuerySelect[$strColumn] = "SUM(case when c.OP_MODE='R' then 1 else 0 end) ".$strColumn;
				}
				break;
			case "COUNT_W":
				if ($strGroupFunc == "" && $bGroup)
				{
					$arQuerySelect[$strColumn] = "SUM(case when c.OP_MODE='W' then 1 else 0 end) ".$strColumn;
				}
				break;
			case "COUNT_C":
				if ($strGroupFunc == "" && $bGroup)
				{
					$arQuerySelect[$strColumn] = "SUM(case when c.OP_MODE='C' then 1 else 0 end) ".$strColumn;
				}
				break;
			}
		}

		if (!is_array($arOrder))
			$arOrder = array();

		$arQueryOrder = array();
		foreach ($arOrder as $strColumn => $strDirection)
		{
			$strColumn = strtoupper($strColumn);
			if (!array_key_exists($strColumn, $arQuerySelect))
				continue;

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
			case "COMPONENT_ID":
			case "NN":
			case "OP_MODE":
			case "MODULE_NAME":
			case "COMPONENT_NAME":
			case "BASE_DIR":
			case "FILE_NAME":
				if ($strGroupFunc == "")
				{
					$arSelect[] = $strColumn;
					$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
				}
				break;
			case "HIT_RATIO":
				if ($strGroupFunc == "" && $bGroup)
				{
					$arSelect[] = $strColumn;
					$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
				}
				break;
			case "FILE_PATH":
				if (
					!isset($arQueryOrder["BASE_DIR"])
					&& !isset($arQueryOrder["INIT_DIR"])
					&& !isset($arQueryOrder["FILE_NAME"])
					&& $strGroupFunc == ""
				)
				{
					$arSelect[] = "BASE_DIR";
					$arQueryOrder["BASE_DIR"] = "BASE_DIR ".$strDirection;
					$arSelect[] = "INIT_DIR";
					$arQueryOrder["INIT_DIR"] = "INIT_DIR ".$strDirection;
					$arSelect[] = "FILE_NAME";
					$arQueryOrder["FILE_NAME"] = "FILE_NAME ".$strDirection;
				}
				break;
			case "INIT_DIR":
				if (
					!isset($arQueryOrder["BASE_DIR"])
					&& !isset($arQueryOrder["INIT_DIR"])
					&& $strGroupFunc == ""
				)
				{
					$arSelect[] = "BASE_DIR";
					$arQueryOrder["BASE_DIR"] = "BASE_DIR ".$strDirection;
					$arSelect[] = "INIT_DIR";
					$arQueryOrder["INIT_DIR"] = "INIT_DIR ".$strDirection;
				}
				break;
			case "CACHE_SIZE":
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
			case "COUNT_R":
			case "COUNT_W":
			case "COUNT_C":
				if ($strGroupFunc == "" && $bGroup)
				{
					$arSelect[] = $strColumn;
					$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
				}
				break;
			}
		}

		$obQueryWhere = new CSQLWhere;
		static $arWhereFields = array(
			"ID" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"HIT_ID" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.HIT_ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"COMPONENT_ID" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.COMPONENT_ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"OP_MODE" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.OP_MODE",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"MODULE_NAME" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.MODULE_NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"COMPONENT_NAME" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.COMPONENT_NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"BASE_DIR" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.BASE_DIR",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"INIT_DIR" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.INIT_DIR",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"FILE_NAME" => array(
				"TABLE_ALIAS" => "c",
				"FIELD_NAME" => "c.FILE_NAME",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
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
				FROM b_perf_cache c
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
				FROM b_perf_cache c
				".$obQueryWhere->GetJoins()."
				".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
				".($bGroup? "GROUP BY ".implode(", ", $arQueryGroup): "")."
				".$strHaving."
			";
			$res_cnt = $DB->Query($strSql);
			$ar_cnt = $res_cnt->Fetch();

			$strSql = "
				SELECT ".implode(", ", $arQuerySelect)."
				FROM b_perf_cache c
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
				FROM b_perf_cache c
				".$obQueryWhere->GetJoins()."
				".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
				".($bGroup? "GROUP BY ".implode(", ", $arQueryGroup): "")."
				".(count($arQueryOrder)? "ORDER BY ".implode(", ", $arQueryOrder): "")."
			";
			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $res;
	}

	public static function Clear()
	{
		global $DB;
		return $DB->Query("TRUNCATE TABLE b_perf_cache");
	}
}
