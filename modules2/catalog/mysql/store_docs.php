<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/store_docs.php");

class CCatalogDocs
	extends CAllCatalogDocs
{
	/**
	* @static
	* @param $arFields
	* @return bool|int
	*/
	static function add($arFields)
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

		$arInsert = $DB->PrepareInsert("b_catalog_store_docs", $arFields);

		$strSql =
			"INSERT INTO b_catalog_store_docs (".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";

		$res = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
		if(!$res)
			return false;
		$lastId = intval($DB->LastID());
		return $lastId;
	}

	static function getList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;
		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "DOC_TYPE", "SITE_ID", "CONTRACTOR_ID", "CURRENCY", "STATUS", "DATE_DOCUMENT", "TOTAL");

		$arFields = array(
			"ID" => array("FIELD" => "CD.ID", "TYPE" => "int"),
			"DOC_TYPE" => array("FIELD" => "CD.DOC_TYPE", "TYPE" => "char"),
			"SITE_ID" => array("FIELD" => "CD.SITE_ID", "TYPE" => "string"),
			"CURRENCY" => array("FIELD" => "CD.CURRENCY", "TYPE" => "string"),
			"CONTRACTOR_ID" => array("FIELD" => "CD.CONTRACTOR_ID", "TYPE" => "int"),
			"DATE_CREATE" => array("FIELD" => "CD.DATE_CREATE", "TYPE" => "datetime"),
			"DATE_MODIFY" => array("FIELD" => "CD.DATE_MODIFY", "TYPE" => "datetime"),
			"DATE_DOCUMENT" => array("FIELD" => "CD.DATE_DOCUMENT", "TYPE" => "datetime"),
			"CREATED_BY" => array("FIELD" => "CD.CREATED_BY", "TYPE" => "int"),
			"MODIFIED_BY" => array("FIELD" => "CD.MODIFIED_BY", "TYPE" => "int"),
			"STATUS" => array("FIELD" => "CD.STATUS", "TYPE" => "string"),
			"TOTAL" => array("FIELD" => "CD.TOTAL", "TYPE" => "double"),

			"PRODUCTS_ID" => array("FIELD" => "DE.ID", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_docs_element DE ON (CD.ID = DE.DOC_ID)"),
			"PRODUCTS_DOC_ID" => array("FIELD" => "DE.DOC_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_docs_element DE ON (CD.ID = DE.DOC_ID)"),
			"PRODUCTS_STORE_FROM" => array("FIELD" => "DE.STORE_FROM", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_docs_element DE ON (CD.ID = DE.DOC_ID)"),
			"PRODUCTS_STORE_TO" => array("FIELD" => "DE.STORE_TO", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_docs_element DE ON (CD.ID = DE.DOC_ID)"),
			"PRODUCTS_ELEMENT_ID" => array("FIELD" => "DE.ELEMENT_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_catalog_docs_element DE ON (CD.ID = DE.DOC_ID)"),
			"PRODUCTS_AMOUNT" => array("FIELD" => "DE.AMOUNT", "TYPE" => "double", "FROM" => "INNER JOIN b_catalog_docs_element DE ON (CD.ID = DE.DOC_ID)"),
			"PRODUCTS_PURCHASING_PRICE" => array("FIELD" => "DE.PURCHASING_PRICE", "TYPE" => "double", "FROM" => "INNER JOIN b_catalog_docs_element DE ON (CD.ID = DE.DOC_ID)"),
		);
		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);
		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
					"FROM b_catalog_store_docs CD ".
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
				"FROM b_catalog_store_docs CD ".
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
					"FROM b_catalog_store_docs CD ".
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

	public static function synchronizeStockQuantity($storeId)
	{
		global $DB;
		$storeId = intval($storeId);
		if($storeId > 0)
		{
			$strSql = "INSERT INTO b_catalog_store_product (AMOUNT, PRODUCT_ID, STORE_ID) (SELECT cp.QUANTITY + CASE WHEN cp.QUANTITY_RESERVED IS NULL THEN 0 ELSE cp.QUANTITY_RESERVED END, cp.ID, ".$storeId." FROM b_catalog_product cp WHERE 1 = 1)";
			return $DB->Query($strSql, true, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return false;
	}
}

class CCatalogArrivalDocs extends CAllCatalogArrivalDocs
{

}

class CCatalogMovingDocs extends CAllCatalogMovingDocs
{

}

class CCatalogReturnsDocs extends CAllCatalogReturnsDocs
{

}

class CCatalogDeductDocs extends CAllCatalogDeductDocs
{

}

class CCatalogUnReservedDocs extends CAllCatalogUnReservedDocs
{

}