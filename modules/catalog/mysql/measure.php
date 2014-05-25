<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/measure.php");

class CCatalogMeasure
	extends CCatalogMeasureAll
{
	public static function add($arFields)
	{
		global $DB;
		if(!self::CheckFields('ADD',$arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_measure", $arFields);
		$strSql = "INSERT INTO b_catalog_measure (".$arInsert[0].") VALUES(".$arInsert[1].")";

		$res = $DB->Query($strSql, true, "File: ".__FILE__."<br>Line: ".__LINE__);
		if(!$res)
			return false;
		$lastId = intval($DB->LastID());
		return $lastId;
	}

	static function getList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (empty($arSelectFields))
		{
			$arSelectFields = array("ID", "CODE", "MEASURE_TITLE", "SYMBOL_RUS", "SYMBOL_INTL", "SYMBOL_LETTER_INTL", "IS_DEFAULT");
		}
		elseif (is_array($arSelectFields))
		{
			if (!in_array('*', $arSelectFields))
			{
				$selectCodes = array_fill_keys($arSelectFields, true);
				if (!isset($selectCodes['CODE']))
					$arSelectFields[] = 'CODE';
				if (
					(isset($selectCodes['MEASURE_TITLE']) || isset($selectCodes['SYMBOL_RUS']))
					&& !isset($selectCodes['SYMBOL_INTL'])
				)
					$arSelectFields[] = 'SYMBOL_INTL';
				unset($selectCodes);
			}
		}

		$arFields = array(
			"ID" => array("FIELD" => "CM.ID", "TYPE" => "int"),
			"CODE" => array("FIELD" => "CM.CODE", "TYPE" => "int"),
			"MEASURE_TITLE" => array("FIELD" => "CM.MEASURE_TITLE", "TYPE" => "string"),
			"SYMBOL_RUS" => array("FIELD" => "CM.SYMBOL_RUS", "TYPE" => "string"),
			"SYMBOL_INTL" => array("FIELD" => "CM.SYMBOL_INTL", "TYPE" => "string"),
			"SYMBOL_LETTER_INTL" => array("FIELD" => "CM.SYMBOL_LETTER_INTL", "TYPE" => "string"),
			"IS_DEFAULT" => array("FIELD" => "CM.IS_DEFAULT", "TYPE" => "char"),
		);
		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);
		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_measure CM ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_measure CM ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && array_key_exists('nTopCount', $arNavStartParams))
		{
			$intTopCount = intval($arNavStartParams["nTopCount"]);
		}
		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_measure CM ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (empty($arSqls["GROUPBY"]))
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if ($boolNavStartParams && 0 < $intTopCount)
			{
				$strSql .= " LIMIT ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		$dbMeasure = new CCatalogMeasureResult($dbRes);
		return $dbMeasure;
	}
}