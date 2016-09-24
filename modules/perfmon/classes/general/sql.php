<?php

class CAllPerfomanceSQL
{
	/**
	 * @param array $arSelect
	 * @param array $arFilter
	 * @param array $arOrder
	 * @param boolean $bGroup
	 * @param boolean|array $arNavStartParams
	 *
	 * @return boolean|CDBResult
	 */
	public static function GetList($arSelect, $arFilter, $arOrder, $bGroup, $arNavStartParams = false)
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
			$strDirection = strtoupper($strDirection) == "ASC"? "ASC": "DESC";
			switch ($strColumn)
			{
			case "ID":
			case "HIT_ID":
			case "NN":
			case "MODULE_NAME":
			case "COMPONENT_NAME":
			case "NODE_ID":
				$arSelect[] = $strColumn;
				$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
				break;
			case "SQL_TEXT":
			case "QUERY_TIME":
				if (!$bGroup)
				{
					$arSelect[] = $strColumn;
					$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
				}
				break;
			case "MAX_QUERY_TIME":
			case "MIN_QUERY_TIME":
			case "AVG_QUERY_TIME":
			case "SUM_QUERY_TIME":
				if ($bGroup)
				{
					$arSelect[] = $strColumn;
					$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
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
			switch ($strColumn)
			{
			case "ID":
			case "HIT_ID":
			case "NN":
			case "MODULE_NAME":
			case "COMPONENT_NAME":
			case "NODE_ID":
				if ($bGroup)
					$arQueryGroup[$strColumn] = "s.".$strColumn;
				$arQuerySelect[$strColumn] = "s.".$strColumn;
				break;
			case "SQL_TEXT":
			case "QUERY_TIME":
				if (!$bGroup)
					$arQuerySelect[$strColumn] = "s.".$strColumn;
				break;
			case "MAX_QUERY_TIME":
			case "MIN_QUERY_TIME":
			case "AVG_QUERY_TIME":
			case "SUM_QUERY_TIME":
				if ($bGroup)
				{
					$arQuerySelect[$strColumn] = substr($strColumn, 0, 3)."(s.".substr($strColumn, 4).") ".$strColumn;
				}
				break;
			case "COUNT":
				if ($bGroup)
				{
					$arQuerySelect[$strColumn] = "COUNT(s.ID) ".$strColumn;
				}
				break;
			}
		}

		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields(array(
			"HIT_ID" => array(
				"TABLE_ALIAS" => "s",
				"FIELD_NAME" => "s.HIT_ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"COMPONENT_ID" => array(
				"TABLE_ALIAS" => "s",
				"FIELD_NAME" => "s.COMPONENT_ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"ID" => array(
				"TABLE_ALIAS" => "s",
				"FIELD_NAME" => "s.ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
			"QUERY_TIME" => array(
				"TABLE_ALIAS" => "s",
				"FIELD_NAME" => "s.QUERY_TIME",
				"FIELD_TYPE" => "double",
				"JOIN" => false,
			),
			"SUGGEST_ID" => array(
				"TABLE_ALIAS" => "iss",
				"FIELD_NAME" => "iss.SUGGEST_ID",
				"FIELD_TYPE" => "int",
				"JOIN" => "INNER JOIN b_perf_index_suggest_sql iss on iss.SQL_ID = s.ID",
				"LEFT_JOIN" => "LEFT JOIN b_perf_index_suggest_sql is on is.SQL_ID = s.ID",
			),
			"NODE_ID" => array(
				"TABLE_ALIAS" => "s",
				"FIELD_NAME" => "s.NODE_ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
		));

		if (count($arQuerySelect) < 1)
			$arQuerySelect = array("ID" => "s.ID");

		if (!is_array($arFilter))
			$arFilter = array();
		$strQueryWhere = $obQueryWhere->GetQuery($arFilter);

		if (is_array($arNavStartParams) && $arNavStartParams["nTopCount"] > 0)
		{
			$strSql = $DB->TopSQL("
				SELECT ".implode(", ", $arQuerySelect)."
				FROM b_perf_sql s
				".$obQueryWhere->GetJoins()."
				".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
				".($bGroup? "GROUP BY ".implode(", ", $arQueryGroup): "")."
				".(count($arQueryOrder)? "ORDER BY ".implode(", ", $arQueryOrder): "")."
			", $arNavStartParams["nTopCount"]);
			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		elseif (is_array($arNavStartParams))
		{
			$strSql = "
				SELECT count('x') CNT
				FROM b_perf_sql s
				".$obQueryWhere->GetJoins()."
				".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
				".($bGroup? "GROUP BY ".implode(", ", $arQueryGroup): "")."
			";
			$res_cnt = $DB->Query($strSql);
			$ar_cnt = $res_cnt->Fetch();

			$strSql = "
				SELECT ".implode(", ", $arQuerySelect)."
				FROM b_perf_sql s
				".$obQueryWhere->GetJoins()."
				".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
				".($bGroup? "GROUP BY ".implode(", ", $arQueryGroup): "")."
				".(count($arQueryOrder)? "ORDER BY ".implode(", ", $arQueryOrder): "")."
			";
			$res = new CDBResult();
			$res->NavQuery($strSql, $ar_cnt["CNT"], $arNavStartParams);
		}
		else
		{
			$strSql = "
				SELECT ".implode(", ", $arQuerySelect)."
				FROM b_perf_sql s
				".$obQueryWhere->GetJoins()."
				".($strQueryWhere? "WHERE ".$strQueryWhere: "")."
				".($bGroup? "GROUP BY ".implode(", ", $arQueryGroup): "")."
				".(count($arQueryOrder)? "ORDER BY ".implode(", ", $arQueryOrder): "")."
			";
			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $res;
	}

	public static function GetBacktraceList($sql_id)
	{
		global $DB;
		return $DB->Query("
			SELECT *
			FROM b_perf_sql_backtrace
			WHERE SQL_ID = ".intval($sql_id)."
			AND NN > 0
			ORDER BY NN
		");
	}

	public static function Format($strSql)
	{
		$strSql = preg_replace("/[\\n\\r\\t\\s ]+/", " ", $strSql);
		$strSql = preg_replace("/^ +/", "", $strSql);
		$strSql = preg_replace("/ (INNER JOIN|OUTER JOIN|LEFT JOIN|SET|LIMIT) /i", "\n\\1 ", $strSql);
		$strSql = preg_replace("/(INSERT INTO [A-Z_0-1]+?)\\s/i", "\\1\n", $strSql);
		$strSql = preg_replace("/(INSERT INTO [A-Z_0-1]+?)([(])/i", "\\1\n\\2", $strSql);
		$strSql = preg_replace("/([\\s)])(VALUES)([\\s(])/i", "\\1\n\\2\n\\3", $strSql);
		$strSql = preg_replace("/ (FROM|WHERE|ORDER BY|GROUP BY|HAVING) /i", "\n\\1\n", $strSql);
		$arMatch = array();

		if (preg_match("/.*WHERE(.+)\\s(ORDER BY|GROUP BY|HAVING|$)/is", $strSql." ", $arMatch))
		{
			$strWhere = $arMatch[1];
			$len = strlen($strWhere);
			$res = "";
			$group = 0;
			for ($i = 0; $i < $len; $i++)
			{
				$char = substr($strWhere, $i, 1);
				if ($char == "(")
					$group++;
				elseif ($char == ")")
					$group--;
				elseif ($group == 0)
				{
					$match = array();
					if (preg_match("/^(\\s)(AND|OR|NOT)([\\s(])/is", substr($strWhere, $i), $match))
					{
						$char = "\n    ".$match[2];
						$i += strlen($match[1].$match[2]) - 1;
					}
				}
				$res .= $char;
			}
			$strSql = str_replace($arMatch[1], $res, $strSql);
		}

		if (preg_match("/.*?SELECT(.+)\\s(FROM)/is", $strSql." ", $arMatch))
		{
			$strWhere = $arMatch[1];
			$len = strlen($strWhere);
			$res = "";
			$group = 0;
			for ($i = 0; $i < $len; $i++)
			{
				$char = substr($strWhere, $i, 1);
				if ($char == "(")
					$group++;
				elseif ($char == ")")
					$group--;
				elseif ($group == 0 && $char == ",")
				{
					$char = "\n    ".$char;
				}
				$res .= $char;
			}
			$strSql = str_replace($arMatch[1], $res, $strSql);
		}

		if (preg_match("/.*?UPDATE\\s.+?\\sSET\\s(.+?)WHERE/is", $strSql." ", $arMatch))
		{
			$strWhere = $arMatch[1];
			$len = strlen($strWhere);
			$res = "";
			$group = 0;
			for ($i = 0; $i < $len; $i++)
			{
				$char = substr($strWhere, $i, 1);
				if ($char == "(")
					$group++;
				elseif ($char == ")")
					$group--;
				elseif ($group == 0 && $char == ",")
				{
					$char = "\n    ".$char;
				}
				$res .= $char;
			}
			$strSql = str_replace($arMatch[1], $res, $strSql);
		}
		return $strSql;
	}
}
