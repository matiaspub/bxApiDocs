<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/contractor.php");

class CCatalogContractor
	extends CAllCatalogContractor
{
	/** Add new store in table b_catalog_contractor,
	* @static
	* @param $arFields
	* @return bool|int
	*/
	public static function add($arFields)
	{
		global $DB;

		if(array_key_exists('DATE_CREATE', $arFields))
			unset($arFields['DATE_CREATE']);
		if(array_key_exists('DATE_MODIFY', $arFields))
			unset($arFields['DATE_MODIFY']);

		$arFields['~DATE_MODIFY'] = $DB->GetNowFunction();
		$arFields['~DATE_CREATE'] = $DB->GetNowFunction();

		if(!self::CheckFields('ADD', $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_contractor", $arFields);

		$strSql = "INSERT INTO b_catalog_contractor (".$arInsert[0].") VALUES(".$arInsert[1].")";

		$res = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
		if(!$res)
			return false;
		$lastId = intval($DB->LastID());
		return $lastId;
	}

	public static function getList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;
		if (empty($arSelectFields))
			$arSelectFields = array("ID", "PERSON_TYPE", "PERSON_NAME", "PERSON_LASTNAME", "PERSON_MIDDLENAME", "EMAIL", "PHONE", "POST_INDEX", "COUNTRY", "CITY", "COMPANY", "ADDRESS", "INN", "KPP");

		$arFields = array(
			"ID" => array("FIELD" => "CC.ID", "TYPE" => "int"),
			"PERSON_TYPE" => array("FIELD" => "CC.PERSON_TYPE", "TYPE" => "char"),
			"PERSON_NAME" => array("FIELD" => "CC.PERSON_NAME", "TYPE" => "string"),
			"PERSON_LASTNAME" => array("FIELD" => "CC.PERSON_LASTNAME", "TYPE" => "string"),
			"PERSON_MIDDLENAME" => array("FIELD" => "CC.PERSON_MIDDLENAME", "TYPE" => "string"),
			"EMAIL" => array("FIELD" => "CC.EMAIL", "TYPE" => "string"),
			"PHONE" => array("FIELD" => "CC.PHONE", "TYPE" => "string"),
			"POST_INDEX" => array("FIELD" => "CC.POST_INDEX", "TYPE" => "string"),
			"COUNTRY" => array("FIELD" => "CC.COUNTRY", "TYPE" => "string"),
			"CITY" => array("FIELD" => "CC.CITY", "TYPE" => "string"),
			"COMPANY" => array("FIELD" => "CC.COMPANY", "TYPE" => "string"),
			"ADDRESS" => array("FIELD" => "CC.ADDRESS", "TYPE" => "string"),
			"INN" => array("FIELD" => "CC.INN", "TYPE" => "string"),
			"KPP" => array("FIELD" => "CC.KPP", "TYPE" => "string"),
			"DATE_CREATE" => array("FIELD" => "CC.DATE_CREATE", "TYPE" => "datetime"),
			"DATE_MODIFY" => array("FIELD" => "CC.DATE_MODIFY", "TYPE" => "datetime"),
			"CREATED_BY" => array("FIELD" => "CC.CREATED_BY", "TYPE" => "int"),
			"MODIFIED_BY" => array("FIELD" => "CC.MODIFIED_BY", "TYPE" => "int"),
		);
		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);
		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_contractor CC ".$arSqls["FROM"];
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

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_contractor CC ".$arSqls["FROM"];
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
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_contractor CC ".$arSqls["FROM"];
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

		return $dbRes;
	}
}