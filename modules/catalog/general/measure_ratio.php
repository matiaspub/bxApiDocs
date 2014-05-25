<?
IncludeModuleLangFile(__FILE__);

class CCatalogMeasureRatioAll
{
	protected static function checkFields($action, &$arFields)
	{
		$action = strtoupper($action);
		if ('UPDATE' != $action && 'ADD' != $action)
			return false;
		if (is_set($arFields, "RATIO") || 'ADD' == $arFields["RATIO"])
		{
			$arFields["RATIO"] = str_replace(',', '.', $arFields["RATIO"]);
			$arFields["RATIO"] = doubleval($arFields["RATIO"]);
			if (CATALOG_VALUE_EPSILON > abs($arFields["RATIO"]))
				$arFields["RATIO"] = 1;
			elseif (0 > $arFields["RATIO"])
				$arFields["RATIO"] = 1;
		}
		return true;
	}

	public static function update($id, $arFields)
	{
		$id = intval($id);
		if($id < 0 || !self::checkFields('UPDATE', $arFields))
			return false;
		global $DB;
		$strUpdate = $DB->PrepareUpdate("b_catalog_measure_ratio", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "UPDATE b_catalog_measure_ratio SET ".$strUpdate." WHERE ID = ".$id;
			if(!$DB->Query($strSql, true, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;
		}
		return $id;
	}

	public static function delete($id)
	{
		global $DB;
		$id = intval($id);
		if($id > 0)
		{
			if($DB->Query("DELETE FROM b_catalog_measure_ratio WHERE ID = ".$id, true))
				return true;
		}
		return false;
	}
}