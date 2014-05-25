<?
class CAllPerfomanceTable
{
	public function GetList($arSelect, $arFilter, $arOrder = array(), $arNavParams = false)
	{
		global $DB;

		$arFields = $this->GetTableFields();

		if(!is_array($arSelect))
			$arSelect = array();
		if(count($arSelect) < 1)
			$arSelect = array_keys($arFields);

		if(!is_array($arOrder))
			$arOrder = array();

		$arQueryOrder = array();
		foreach($arOrder as $strColumn => $strDirection)
		{
			$strDirection = strtoupper($strDirection)=="ASC"? "ASC": "DESC";
			if(array_key_exists($strColumn, $arFields))
			{
				$arSelect[] = $strColumn;
				if($arFields[$strColumn]=="datetime")
					$arQueryOrder[$strColumn] = $this->escapeColumn("TMP_".$strColumn)." ".$strDirection;
				else
					$arQueryOrder[$strColumn] = $this->escapeColumn($strColumn)." ".$strDirection;
			}
		}

		$arQuerySelect = array();
		foreach($arSelect as $strColumn)
		{
			if(array_key_exists($strColumn, $arFields))
			{
				if($arFields[$strColumn]=="datetime" || $arFields[$strColumn]=="date")
				{
					$arQuerySelect["TMP_".$strColumn] = "t.".$strColumn." TMP_".$strColumn;
					$arQuerySelect[$strColumn] = $DB->DateToCharFunction("t.".$strColumn, "SHORT")." ".$strColumn;
					$arQuerySelect["FULL_".$strColumn] = $DB->DateToCharFunction("t.".$strColumn, "FULL")." FULL_".$strColumn;
					$arQuerySelect["SHORT_".$strColumn] = $DB->DateToCharFunction("t.".$strColumn, "SHORT")." SHORT_".$strColumn;
				}
				else
				{
					$arQuerySelect[$strColumn] = "t.".$strColumn;
				}
			}
		}

		foreach($arFields as $FIELD_NAME => $FIELD_TYPE)
		{
			$arFields[$FIELD_NAME] = array(
				"TABLE_ALIAS" => "t",
				"FIELD_NAME" => $FIELD_NAME,
				"FIELD_TYPE" => $FIELD_TYPE,
				"JOIN" => false,
				//"LEFT_JOIN" => "lt",
			);
		}
		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields($arFields);

		if(count($arQuerySelect) < 1)
			$arQuerySelect = array("*"=>"t.*");

		if(is_array($arNavParams))
		{
			return $this->NavQuery($arNavParams, $arQuerySelect, $this->TABLE_NAME, $obQueryWhere->GetQuery($arFilter), $arQueryOrder);
		}
		else
		{
			$strSql = "
				SELECT
				".implode(", ", $arQuerySelect)."
				FROM
					".$this->TABLE_NAME." t
			";
			if($strQueryWhere = $obQueryWhere->GetQuery($arFilter))
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
			//echo "<pre>",htmlspecialcharsbx($strSql),"</pre><hr>";
			return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}

	public static function escapeColumn($column)
	{
		return $column;
	}
}
?>