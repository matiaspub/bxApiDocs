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

		$strSql =
			"INSERT INTO b_catalog_contractor (".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";

		$res = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
		if(!$res)
			return false;
		$lastId = intval($DB->LastID());
		return $lastId;
	}

	public static function getList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;
		if (count($arSelectFields) <= 0)
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
			"COMPANY" => array("FIELD" => "IF (CC.PERSON_NAME IS NOT NULL, if(CC.PERSON_TYPE = ".CONTRACTOR_INDIVIDUAL.", CC.PERSON_NAME, CONCAT(CC.COMPANY,' (',CC.PERSON_NAME,')')), CC.COMPANY)", "TYPE" => "string"),
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

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
					"FROM b_catalog_contractor CC ".
					"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_catalog_contractor CC ".
				"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";
		if (is_array($arNavStartParams) && intval($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
					"FROM b_catalog_contractor CC ".
					"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (strlen($arSqls["GROUPBY"]) <= 0)
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
			if (is_array($arNavStartParams) && intval($arNavStartParams["nTopCount"])>0)
				$strSql .= "LIMIT ".intval($arNavStartParams["nTopCount"]);

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}
}