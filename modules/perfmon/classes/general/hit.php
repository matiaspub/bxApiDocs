<?

class CPerfomanceHit
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
				"ID" => "DESC",
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
			case "IS_ADMIN":
			case "REQUEST_METHOD":
			case "SERVER_NAME":
			case "SERVER_PORT":
			case "SCRIPT_NAME":
			case "REQUEST_URI":
				if ($strGroupFunc == "")
				{
					$arSelect[] = $strColumn;
					$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
				}
				break;
			case "INCLUDED_FILES":
			case "MEMORY_PEAK_USAGE":
			case "CACHE_SIZE":
			case "CACHE_COUNT":
			case "CACHE_COUNT_R":
			case "CACHE_COUNT_W":
			case "CACHE_COUNT_C":
			case "QUERIES":
			case "QUERIES_TIME":
			case "PAGE_TIME":
			case "PROLOG_TIME":
			case "PROLOG_BEFORE_TIME":
			case "AGENTS_TIME":
			case "PROLOG_AFTER_TIME":
			case "WORK_AREA_TIME":
			case "EPILOG_TIME":
			case "EPILOG_BEFORE_TIME":
			case "EVENTS_TIME":
			case "EPILOG_AFTER_TIME":
			case "COMPONENTS":
			case "COMPONENTS_TIME":
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
						$arSelect[] = $strGroupFunc."_".$strColumn;
						$arQueryOrder[$strGroupFunc."_".$strColumn] = $strGroupFunc."_".$strColumn." ".$strDirection;
					}
				}
				break;
			case "DATE_HIT":
				if ($strGroupFunc == "" && !$bGroup)
				{
					$arSelect[] = $strColumn;
					$arQueryOrder[$strColumn] = "TMP_DH ".$strDirection;
				}
				break;
			case "COUNT":
				if ($strGroupFunc == "" && $bGroup)
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
			case "IS_ADMIN":
			case "REQUEST_METHOD":
			case "SERVER_NAME":
			case "SERVER_PORT":
			case "SCRIPT_NAME":
			case "REQUEST_URI":
			case "SQL_LOG":
				if ($strGroupFunc == "")
				{
					if ($bGroup)
						$arQueryGroup[$strColumn] = "h.".$strColumn;
					$arQuerySelect[$strColumn] = "h.".$strColumn;
				}
				break;
			case "INCLUDED_FILES":
			case "MEMORY_PEAK_USAGE":
			case "CACHE_SIZE":
			case "CACHE_COUNT_R":
			case "CACHE_COUNT_W":
			case "CACHE_COUNT_C":
			case "QUERIES":
			case "QUERIES_TIME":
			case "PAGE_TIME":
			case "PROLOG_TIME":
			case "PROLOG_BEFORE_TIME":
			case "AGENTS_TIME":
			case "PROLOG_AFTER_TIME":
			case "WORK_AREA_TIME":
			case "EPILOG_TIME":
			case "EPILOG_BEFORE_TIME":
			case "EVENTS_TIME":
			case "EPILOG_AFTER_TIME":
			case "COMPONENTS":
			case "COMPONENTS_TIME":
				if ($strGroupFunc == "")
				{
					if (!$bGroup)
						$arQuerySelect[$strColumn] = "h.".$strColumn;
				}
				else
				{
					if ($bGroup)
						$arQuerySelect[$strGroupFunc."_".$strColumn] = $strGroupFunc."(h.".$strColumn.") ".$strGroupFunc."_".$strColumn;
				}
				break;
			case "CACHE_COUNT":
				if ($strGroupFunc == "")
				{
					if (!$bGroup)
						$arQuerySelect[$strColumn] = "(h.CACHE_COUNT_R + h.CACHE_COUNT_W + h.CACHE_COUNT_C) CACHE_COUNT";
				}
				else
				{
					if ($bGroup)
						$arQuerySelect[$strGroupFunc."_".$strColumn] = $strGroupFunc."(h.CACHE_COUNT_R + h.CACHE_COUNT_W + h.CACHE_COUNT_C) ".$strGroupFunc."_".$strColumn;
				}
				break;
			case "DATE_HIT":
				if ($strGroupFunc == "" && !$bGroup)
				{
					$arQuerySelect["TMP_DH"] = "h.".$strColumn." TMP_DH";
					$arQuerySelect[$strColumn] = $DB->DateToCharFunction("h.".$strColumn, "SHORT")." ".$strColumn;
					$arQuerySelect["FULL_".$strColumn] = $DB->DateToCharFunction("h.".$strColumn, "FULL")." FULL_".$strColumn;
				}
				break;
			case "COUNT":
				if ($strGroupFunc == "" && $bGroup)
				{
					$arQuerySelect[$strColumn] = "COUNT(h.ID) ".$strColumn;
				}
				break;
			}
		}

		static $arWhereFields = array(
			"SCRIPT_NAME" => array(
				"TABLE_ALIAS" => "h",
				"FIELD_NAME" => "h.SCRIPT_NAME",
				"FIELD_TYPE" => "string", //int, double, file, enum, int, string, date, datetime
				"JOIN" => false,
				//"LEFT_JOIN" => "lt",
			),
			"IS_ADMIN" => array(
				"TABLE_ALIAS" => "h",
				"FIELD_NAME" => "h.IS_ADMIN",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"REQUEST_METHOD" => array(
				"TABLE_ALIAS" => "h",
				"FIELD_NAME" => "h.REQUEST_METHOD",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"ID" => array(
				"TABLE_ALIAS" => "h",
				"FIELD_NAME" => "ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"CACHE_TYPE" => array(
				"TABLE_ALIAS" => "h",
				"FIELD_NAME" => "h.CACHE_TYPE",
				"FIELD_TYPE" => "string",
				"JOIN" => false,
			),
			"MENU_RECALC" => array(
				"TABLE_ALIAS" => "h",
				"FIELD_NAME" => "h.MENU_RECALC",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
		);

		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields($arWhereFields);

		if (count($arQuerySelect) < 1)
			$arQuerySelect = array("ID" => "h.ID");

		$strQueryWhere = $obQueryWhere->GetQuery($arFilter);
		$strHaving = "";
		if ($bGroup && count($arQueryGroup) > 0)
		{
			$obQueryHaving = new CSQLWhere;
			$obQueryHaving->SetFields(array(
				"COUNT" => array(
					"TABLE_ALIAS" => "",
					"FIELD_NAME" => "COUNT(h.ID)",
					"FIELD_TYPE" => "int",
					"JOIN" => false,
				),
			));
			$strHaving = $obQueryHaving->GetQuery($arFilter);
			if ($strHaving)
			{
				$strHaving = "HAVING ".$strHaving." ";
			}
		}

		if (is_array($arNavStartParams) && $arNavStartParams["nTopCount"] > 0)
		{
			$strSql = $DB->TopSQL("
				SELECT ".implode(", ", $arQuerySelect)."
				FROM b_perf_hit h
				".$obQueryWhere->GetJoins()."
				".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
				".($bGroup && count($arQueryGroup) > 0? "GROUP BY ".implode(", ", $arQueryGroup): "")."
				".$strHaving."
				".(count($arQueryOrder)? "ORDER BY ".implode(", ", $arQueryOrder): "")."
			", $arNavStartParams["nTopCount"]);
			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		elseif (is_array($arNavStartParams))
		{
			$strSql = "
				SELECT count('x') CNT
				FROM b_perf_hit h
				".$obQueryWhere->GetJoins()."
				".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
				".($bGroup && count($arQueryGroup) > 0? "GROUP BY ".implode(", ", $arQueryGroup): "")."
				".$strHaving."
			";
			$res_cnt = $DB->Query($strSql);
			$ar_cnt = $res_cnt->Fetch();

			$strSql = "
				SELECT ".implode(", ", $arQuerySelect)."
				FROM b_perf_hit h
				".$obQueryWhere->GetJoins()."
				".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
				".($bGroup && count($arQueryGroup) > 0? "GROUP BY ".implode(", ", $arQueryGroup): "")."
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
				FROM b_perf_hit h
				".$obQueryWhere->GetJoins()."
				".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
				".($bGroup && count($arQueryGroup) > 0? "GROUP BY ".implode(", ", $arQueryGroup): "")."
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
		return $DB->Query("TRUNCATE TABLE b_perf_hit");
	}
}

?>