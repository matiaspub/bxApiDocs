<?
use Bitrix\Main\Loader;
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/product.php");

class CSaleProduct extends CALLSaleProduct
{
	/*
	 * Returns list of products ordered with the specific product
	 *
	 * @param int $ID - product id (sku ID or parent product ID are also supported)
	 * @param int $minCNT - number of times products should have been ordered to be returned in the result
	 * @param int $limit - serialized data saved in the database for the record of this type
	 * @param boolean $getParentOnly - return only parent product ID
	 * @return dbres
	 */
	public static function GetProductList($ID, $minCNT, $limit, $getParentOnly = false)
	{
		global $DB;

		$ID = (int)$ID;
		if ($ID <= 0)
			return false;
		$limit = (int)$limit;
		if ($limit < 0)
			$limit = 0;
		$minCNT = (int)$minCNT;
		if ($minCNT < 0)
			$minCNT = 0;

		$getParentOnly = ($getParentOnly === true);

		$elementInclude = array($ID);
		$elementExclude = array();

		if (Loader::includeModule('catalog'))
		{
			$intIBlockID = (int)CIBlockElement::GetIBlockByID($ID);
			if ($intIBlockID == 0)
				return false;

			$skuInfo = CCatalogSKU::GetInfoByProductIBlock($intIBlockID);
			if (!empty($skuInfo))
			{
				$itemsIterator = CIBlockElement::GetList(
					array(),
					array('IBLOCK_ID' => $skuInfo['IBLOCK_ID'], 'PROPERTY_'.$skuInfo['SKU_PROPERTY_ID'] => $ID),
					false,
					false,
					array('ID', 'IBLOCK_ID', 'PROPERTY_'.$skuInfo['SKU_PROPERTY_ID'])
				);
				while ($item = $itemsIterator->Fetch())
				{
					$item['ID'] = (int)$item['ID'];
					$elementInclude[] = $item['ID'];
					$elementExclude[] = $item['ID'];
				}
			}
		}

		if ($getParentOnly)
		{
			$strSql = "select PARENT_PRODUCT_ID from b_sale_product2product where PRODUCT_ID IN (".implode(',', $elementInclude).")";
			if (!empty($elementExclude))
				$strSql .= " and PARENT_PRODUCT_ID not in (".implode(',', $elementExclude).")";
			if ($minCNT > 0)
				$strSql .= " and CNT >= ".$minCNT;
			$strSql .= ' group by PARENT_PRODUCT_ID';
			if ($limit > 0)
				$strSql .= " limit ".$limit;
		}
		else
		{
			$strSql = "select * from b_sale_product2product where PRODUCT_ID in (".implode(',', $elementInclude).")";
			if (!empty($elementExclude))
				$strSql .= " and PARENT_PRODUCT_ID not in (".implode(',', $elementExclude).")";
			if ($minCNT > 0)
				$strSql .= " and CNT >= ".$minCNT;
			$strSql .= " order by CNT desc, PRODUCT_ID asc";
			if ($limit > 0)
				$strSql .= " limit ".$limit;
		}
		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function GetBestSellerList($by = "AMOUNT", $arFilter = Array(), $arOrderFilter = Array(), $limit = 0)
	{
		global $DB;

		$byQuantity = false;
		if($by == "QUANTITY")
			$byQuantity = true;

		$arJoin = array();
		$arWhere = array();
		$orderFilter = "";
		$i = 1;

		if(is_array($arFilter) && count($arFilter) > 0)
		{
			foreach($arFilter as $key => $value)
			{
				$arJoin[] = "LEFT JOIN b_sale_basket_props p".$i." ON (b.ID = p".$i.".BASKET_ID)";
				$arFilter = CSaleProduct::GetFilterOperation($key, $value);
				$arWhere[] = "   AND p".$i.".CODE = '".$arFilter["field"]."' AND p".$i.".VALUE ".$arFilter["operation"]." ".$arFilter["value"];
				$i++;
			}
		}

		$arFields = array(
			"ID" => array("FIELD_NAME" => "O.ID", "FIELD_TYPE" => "int"),
			"LID" => array("FIELD_NAME" => "O.LID", "FIELD_TYPE" => "string"),
			"PERSON_TYPE_ID" => array("FIELD_NAME" => "O.PERSON_TYPE_ID", "FIELD_TYPE" => "int"),
			"PAYED" => array("FIELD_NAME" => "O.PAYED", "FIELD_TYPE" => "string"),
			"DATE_PAYED" => array("FIELD_NAME" => "O.DATE_PAYED", "FIELD_TYPE" => "datetime"),
			"EMP_PAYED_ID" => array("FIELD_NAME" => "O.EMP_PAYED_ID", "FIELD_TYPE" => "int"),
			"CANCELED" => array("FIELD_NAME" => "O.CANCELED", "FIELD_TYPE" => "string"),
			"DATE_CANCELED" => array("FIELD_NAME" => "O.DATE_CANCELED", "FIELD_TYPE" => "datetime"),
			"EMP_CANCELED_ID" => array("FIELD_NAME" => "O.EMP_CANCELED_ID", "FIELD_TYPE" => "int"),
			"REASON_CANCELED" => array("FIELD_NAME" => "O.REASON_CANCELED", "FIELD_TYPE" => "string"),
			"STATUS_ID" => array("FIELD_NAME" => "O.STATUS_ID", "FIELD_TYPE" => "string"),
			"DATE_STATUS" => array("FIELD_NAME" => "O.DATE_STATUS", "FIELD_TYPE" => "datetime"),
			"PAY_VOUCHER_NUM" => array("FIELD_NAME" => "O.PAY_VOUCHER_NUM", "FIELD_TYPE" => "string"),
			"PAY_VOUCHER_DATE" => array("FIELD_NAME" => "O.PAY_VOUCHER_DATE", "FIELD_TYPE" => "date"),
			"EMP_STATUS_ID" => array("FIELD_NAME" => "O.EMP_STATUS_ID", "FIELD_TYPE" => "int"),
			"PRICE_DELIVERY" => array("FIELD_NAME" => "O.PRICE_DELIVERY", "FIELD_TYPE" => "double"),
			"ALLOW_DELIVERY" => array("FIELD_NAME" => "O.ALLOW_DELIVERY", "FIELD_TYPE" => "string"),
			"DATE_ALLOW_DELIVERY" => array("FIELD_NAME" => "O.DATE_ALLOW_DELIVERY", "FIELD_TYPE" => "datetime"),
			"EMP_ALLOW_DELIVERY_ID" => array("FIELD_NAME" => "O.EMP_ALLOW_DELIVERY_ID", "FIELD_TYPE" => "int"),
			"PRICE" => array("FIELD_NAME" => "O.PRICE", "FIELD_TYPE" => "double"),
			"CURRENCY" => array("FIELD_NAME" => "O.CURRENCY", "FIELD_TYPE" => "string"),
			"DISCOUNT_VALUE" => array("FIELD_NAME" => "O.DISCOUNT_VALUE", "FIELD_TYPE" => "double"),
			"SUM_PAID" => array("FIELD_NAME" => "O.SUM_PAID", "FIELD_TYPE" => "double"),
			"USER_ID" => array("FIELD_NAME" => "O.USER_ID", "FIELD_TYPE" => "int"),
			"PAY_SYSTEM_ID" => array("FIELD_NAME" => "O.PAY_SYSTEM_ID", "FIELD_TYPE" => "int"),
			"DELIVERY_ID" => array("FIELD_NAME" => "O.DELIVERY_ID", "FIELD_TYPE" => "string"),
			"DATE_INSERT" => array("FIELD_NAME" => "O.DATE_INSERT", "FIELD_TYPE" => "datetime"),
			"DATE_INSERT_FORMAT" => array("FIELD_NAME" => "O.DATE_INSERT", "FIELD_TYPE" => "datetime"),
			"DATE_UPDATE" => array("FIELD_NAME" => "O.DATE_UPDATE", "FIELD_TYPE" => "datetime"),
			"USER_DESCRIPTION" => array("FIELD_NAME" => "O.USER_DESCRIPTION", "FIELD_TYPE" => "string"),
			"ADDITIONAL_INFO" => array("FIELD_NAME" => "O.ADDITIONAL_INFO", "FIELD_TYPE" => "string"),
			"PS_STATUS" => array("FIELD_NAME" => "O.PS_STATUS", "FIELD_TYPE" => "string"),
			"PS_STATUS_CODE" => array("FIELD_NAME" => "O.PS_STATUS_CODE", "FIELD_TYPE" => "string"),
			"PS_STATUS_DESCRIPTION" => array("FIELD_NAME" => "O.PS_STATUS_DESCRIPTION", "FIELD_TYPE" => "string"),
			"PS_STATUS_MESSAGE" => array("FIELD_NAME" => "O.PS_STATUS_MESSAGE", "FIELD_TYPE" => "string"),
			"PS_SUM" => array("FIELD_NAME" => "O.PS_SUM", "FIELD_TYPE" => "double"),
			"PS_CURRENCY" => array("FIELD_NAME" => "O.PS_CURRENCY", "FIELD_TYPE" => "string"),
			"PS_RESPONSE_DATE" => array("FIELD_NAME" => "O.PS_RESPONSE_DATE", "FIELD_TYPE" => "datetime"),
			"COMMENTS" => array("FIELD_NAME" => "O.COMMENTS", "FIELD_TYPE" => "string"),
			"TAX_VALUE" => array("FIELD_NAME" => "O.TAX_VALUE", "FIELD_TYPE" => "double"),
			"STAT_GID" => array("FIELD_NAME" => "O.STAT_GID", "FIELD_TYPE" => "string"),
			"RECURRING_ID" => array("FIELD_NAME" => "O.RECURRING_ID", "FIELD_TYPE" => "int"),
			"RECOUNT_FLAG" => array("FIELD_NAME" => "O.RECOUNT_FLAG", "FIELD_TYPE" => "string"),
			"AFFILIATE_ID" => array("FIELD_NAME" => "O.AFFILIATE_ID", "FIELD_TYPE" => "int"),
			"DELIVERY_DOC_NUM" => array("FIELD_NAME" => "O.DELIVERY_DOC_NUM", "FIELD_TYPE" => "string"),
			"DELIVERY_DOC_DATE" => array("FIELD_NAME" => "O.DELIVERY_DOC_DATE", "FIELD_TYPE" => "date"),

			"DEDUCTED" => array("FIELD_NAME" => "O.DEDUCTED", "FIELD_TYPE" => "string"),
			"DATE_DEDUCTED" => array("FIELD_NAME" => "O.DATE_DEDUCTED", "FIELD_TYPE" => "datetime"),
		);
		if (!empty($arOrderFilter) && is_array($arOrderFilter))
		{
			$sqlWhere = new CSQLWhere;
			$sqlWhere->SetFields($arFields);
			$arJ = array();
			$orderFilter = $sqlWhere->GetQueryEx($arOrderFilter, $arJ);
		}

		//if($byQuantity)
		//	$strSql = "SELECT b.PRODUCT_ID, b.CATALOG_XML_ID, b.PRODUCT_XML_ID, SUM(b.QUANTITY) as QUANTITY \n";
		//else
			$strSql = "SELECT b.PRODUCT_ID, b.NAME, ifnull(b.CATALOG_XML_ID, '') CATALOG_XML_ID, b.PRODUCT_XML_ID, SUM(b.PRICE*b.QUANTITY) as PRICE, AVG(b.PRICE) as AVG_PRICE, SUM(b.QUANTITY) as QUANTITY, b.CURRENCY \n";

		$strSql .= "FROM b_sale_basket b \n";

		foreach($arJoin as $v)
			$strSql .= $v."\n";
		if ($orderFilter != '')
			$strSql .= "INNER JOIN b_sale_order O ON (b.ORDER_ID = O.ID) \n";

		$strSql .= "WHERE \n".
			" b.ORDER_ID is not null \n";

		foreach($arWhere as $v)
			$strSql .= $v."\n";

		if ($orderFilter != '')
			$strSql .= " AND ".$orderFilter."\n";

		$strSql .= " GROUP BY b.PRODUCT_ID, ifnull(b.CATALOG_XML_ID, ''), b.PRODUCT_XML_ID, b.CURRENCY \n";
		if($byQuantity)
			$strSql .= " ORDER BY QUANTITY DESC\n";
		else
			$strSql .= " ORDER BY PRICE DESC\n";

		if(IntVal($limit) > 0)
			$strSql .= "LIMIT ".IntVal($limit);
		// echo htmlspecialcharsbx($strSql);

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $dbRes;
	}

	public static function GetFilterOperation($key, $value)
	{
		global $DB;
		$field = "";
		$operation = "";
		$field_val = "";

		if(is_array($value))
		{
			$field_val = "(";
			foreach($value as $val)
			{
				if(strlen($val) > 0)
					$field_val .= "\"".$DB->ForSQL($val)."\", ";
			}
			$field_val = substr($field_val, 0, -2);
			$field_val .= ")";

			if (substr($key, 0, 1) == "!")
			{
				$operation = "NOT IN";
				$field = $DB->ForSQL(substr($key, 1));
			}
			else
			{
				$operation = "IN";
				$field = $key;
			}
		}
		else
		{
			$field_val = "\"".$DB->ForSQL($value)."\"";

			if (substr($key, 0, 1) == "!")
			{
				$operation = "<>";
				$field = $DB->ForSQL(substr($key, 1));

			}
			elseif (substr($key, 0, 1) == "%")
			{
				$operation = "LIKE";
				$field = $DB->ForSQL(substr($key, 1));
			}
			elseif (substr($key, 0, 2) == "<=")
			{
				$operation = "<=";
				$field = $DB->ForSQL(substr($key, 2));
			}
			elseif (substr($key, 0, 2) == ">=")
			{
				$operation = ">=";
				$field = $DB->ForSQL(substr($key, 2));
			}
			elseif (substr($key, 0, 1) == ">")
			{
				$operation = ">";
				$field = $DB->ForSQL(substr($key, 1));
			}
			elseif (substr($key, 0, 1) == "<")
			{
				$operation = "<";
				$field = $DB->ForSQL(substr($key, 1));
			}
			else
			{
				$operation = "=";
				$field = $DB->ForSQL($key);
			}
		}
		return array("field" => $field, "operation" => $operation, "value" => $field_val);
	}
}

/**
 * Class CSaleViewedProduct
 * @deprecated
 */
class CSaleViewedProduct extends CAllSaleViewedProduct
{
	/**
	* The function add viewed product
	*
	* @param array $arFields - params for add
	* @return true false
	*/
	static public function Add($arFields)
	{
		global $DB;

		foreach(GetModuleEvents("sale", "OnBeforeViewedAdd", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array(&$arFields))===false)
				return false;

		if (isset($arFields["ID"]))
			unset($arFields["ID"]);

		$arFields["PRODUCT_ID"] = IntVal($arFields["PRODUCT_ID"]);
		$arFields["USER_ID"] = IntVal($arFields["USER_ID"]);
		$arFields["FUSER_ID"] = IntVal($arFields["FUSER_ID"]);
		$arFields["IBLOCK_ID"] = IntVal($arFields["IBLOCK_ID"]);
		if (strlen($arFields["CALLBACK_FUNC"]) <= 0)
			$arFields["CALLBACK_FUNC"] = "CatalogViewedProductCallback";
		if (strlen($arFields["MODULE"]) <= 0)
			$arFields["MODULE"] = "catalog";
		if (strlen($arFields["PRODUCT_PROVIDER_CLASS"]) <= 0 && $arFields["MODULE"] == 'catalog')
			$arFields["PRODUCT_PROVIDER_CLASS"] = "CCatalogProductProvider";
		if ($arFields["PRODUCT_ID"] <= 0)
			return false;
		if (strlen($arFields["LID"]) <= 0)
			return false;

		if (\Bitrix\Main\Loader::includeModule('statistic') && isset($_SESSION['SESS_SEARCHER_ID']) && (int)$_SESSION['SESS_SEARCHER_ID'] > 0)
			return false;

		if ((string)\Bitrix\Main\Config\Option::get('sale', 'viewed_capability') == 'Y')
		{
			if (\Bitrix\Main\Loader::includeModule('catalog'))
			{
				return \Bitrix\Catalog\CatalogViewedProductTable::refresh($arFields["PRODUCT_ID"], CSaleBasket::GetBasketUserID(), $arFields["LID"]);
			}
		}

		$arFilter = array();
		$arFilter["PRODUCT_ID"] = $arFields["PRODUCT_ID"];

		if ($arFields["USER_ID"] > 0)
		{
			$arFuserItems = CSaleUser::GetList(array("USER_ID" => $arFields["USER_ID"]));
			$FUSER_ID = $arFuserItems["ID"];
		}
		elseif ((int)$arFields["FUSER_ID"] > 0)
			$FUSER_ID = $arFields["FUSER_ID"];
		else
			$FUSER_ID = CSaleBasket::GetBasketUserID();
		$FUSER_ID = (int)$FUSER_ID;

		$arFilter["FUSER_ID"] = $FUSER_ID;
		$arFields["FUSER_ID"] = $FUSER_ID;

		$db_res = CSaleViewedProduct::GetList(
				array(),
				$arFilter,
				false,
				false,
				array('ID')
		);
		if (!$arItems = $db_res->Fetch())//insert
		{
			if (\Bitrix\Main\Loader::includeModule('catalog'))
			{
				/** @var $productProvider IBXSaleProductProvider */
				if ($productProvider = CSaleBasket::GetProductProvider($arFields))
				{
					$arResultTmp = $productProvider::ViewProduct(array(
						"PRODUCT_ID" => $arFields["PRODUCT_ID"],
						"USER_ID"    => $arFields["USER_ID"],
						"SITE_ID"    => $arFields["LID"]
					));
				}
				else
				{
					$arResultTmp = CSaleBasket::ExecuteCallbackFunction(
						$arFields["CALLBACK_FUNC"],
						$arFields["MODULE"],
						$arFields["PRODUCT_ID"],
						$arFields["USER_ID"],
						$arFields["LID"]
					);
				}
				if ($arResultTmp && count($arResultTmp) > 0)
					$arFields = array_merge($arFields, $arResultTmp);

				if (strlen($arFields["NAME"]) <= 0)
					return false;

				$arInsert = $DB->PrepareInsert("b_sale_viewed_product", $arFields);

				//chance deleted
				$rnd = mt_rand(0, 1000);
				if ($rnd < 100)
				{
					$db_res = CSaleViewedProduct::GetList(
							array(),
							array("FUSER_ID" => $FUSER_ID),
							array("COUNT" => "ID"),
							false
					);
					$arCount = $db_res->Fetch();
					$viewedCount = COption::GetOptionString("sale", "viewed_count", "100");

					if ($arCount["ID"] > IntVal($viewedCount))
					{
						$limit = ($arCount["ID"] - $viewedCount) + ($viewedCount * 0.2);
						CSaleViewedProduct::DeleteForUser($FUSER_ID, $limit);
					}
				}

				$strSql = "INSERT INTO b_sale_viewed_product (".$arInsert[0].", DATE_VISIT) VALUES(".$arInsert[1].", ".$DB->GetNowFunction().")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

				$ID = IntVal($DB->LastID());
			}
		}
		else//update
		{
			$ID = IntVal($arItems["ID"]);
			$arFields["~DATE_VISIT"] = $DB->GetNowFunction();
			CSaleViewedProduct::Update($ID, $arFields);
		}

		foreach(GetModuleEvents("sale", "OnViewedAdd", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($arFields));

		return $ID;
	}

	/**
	 * Return viewed products.
	 *
	 * @param array $arOrder				Sorting params.
	 * @param array $arFilter				Filter params.
	 * @param bool|array $arGroupBy			Group params.
	 * @param bool|array $arNavStartParams	Navy params.
	 * @param array $arSelectFields			Select fields.
	 * @return bool|CDBResult
	 */
	static public function GetList($arOrder = array("ID"=>"DESC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (array_key_exists("DATE_FROM", $arFilter))
		{
			$arFilter[">=DATE_VISIT"] = trim($arFilter["DATE_FROM"]);
			unset($arFilter["DATE_FROM"]);
		}
		if (array_key_exists("DATE_TO", $arFilter))
		{
			$arFilter["<=DATE_VISIT"] = trim($arFilter["DATE_TO"]);
			unset($arFilter["DATE_TO"]);
		}

		if (!$arSelectFields || count($arSelectFields) <= 0 || in_array("*", $arSelectFields))
			$arSelectFields = array("ID", "FUSER_ID", "DATE_VISIT", "PRODUCT_ID", "MODULE", "LID", "NAME", "DETAIL_PAGE_URL", "CURRENCY", "PRICE", "NOTES", "PREVIEW_PICTURE", "DETAIL_PICTURE", "CALLBACK_FUNC", "PRODUCT_PROVIDER_CLASS");

		if ((string)\Bitrix\Main\Config\Option::get('sale', 'viewed_capability') == 'Y')
		{
			if(\Bitrix\Main\Loader::includeModule('catalog'))
			{
				foreach($arFilter as $key => $value)
				{
					if($key == "LID")
					{
						$arFilter['SITE_ID']= $value;
						unset($arFilter['LID']);
					}
				}

				$limit = 100;
				if(is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) >= 0)
				{
					$limit = IntVal($arNavStartParams["nTopCount"]);
				}

				$viewedIterator = \Bitrix\Catalog\CatalogViewedProductTable::getList(
					array(
						"filter" => $arFilter,
						"select" => array(
							"ID",
							"PRODUCT_ID",
							"DATE_VISIT",
							"LID" => "SITE_ID",
							"NAME" => "ELEMENT.NAME"
						),
						"order" => array("DATE_VISIT" => "DESC"),
						"limit" => $limit
					)
				);

				$viewed = array();
				while($row = $viewedIterator->fetch())
				{
					$row['MODULE'] = "catalog";
					$row['DATE_VISIT'] = $row['DATE_VISIT']->toString();
					$viewed[$row['PRODUCT_ID']] = $row;
				}

				if(!empty($viewed))
				{
					// Map to parent sku
					$newIds = array();
					$ids = array_keys($viewed);
					$catalogIterator = CCatalog::getList();
					while($catalog = $catalogIterator->fetch())
					{
						if ($catalog['IBLOCK_TYPE_ID'] == "offers")
						{
							$elementIterator = CIBlockElement::getList(
								array(),
								array("ID" => $ids, "IBLOCK_ID" => $catalog['IBLOCK_ID']),
								false,
								false,
								array("ID", "IBLOCK_ID", "PROPERTY_" . $catalog['SKU_PROPERTY_ID'])
							);

							while ($item = $elementIterator->fetch())
							{
								$propertyName = "PROPERTY_" . $catalog['SKU_PROPERTY_ID'] . "_VALUE";
								$parentId = $item[$propertyName];
								if (!empty($parentId))
								{
									$newIds[$item['ID']] = $parentId;
								}
								else
								{
									$newIds[$item['ID']] = $item['ID'];
								}
							}
						}
					}

					// Push missing
					foreach ($ids as $id)
					{
						if (!isset($newIds[$id]))
						{
							$newIds[$id] = $id;
						}
					}

					$filter = array("ID" => array_values($newIds));
					if(!count($filter['ID']))
						$filter = array("ID" => -1);

					$mapped = array();
					if(	in_array("DETAIL_PAGE_URL", $arSelectFields) ||
						in_array("PREVIEW_PICTURE", $arSelectFields) ||
						in_array("DETAIL_PICTURE", $arSelectFields))
					{

						$elementIterator = CIBlockElement::GetList(array(), $filter);
						while ($elementObj = $elementIterator->GetNextElement())
						{
							$fields = $elementObj->GetFields();
							$mapped[$fields['ID']]['PREVIEW_PICTURE'] = $fields['PREVIEW_PICTURE'];
							$mapped[$fields['ID']]['DETAIL_PICTURE'] = $fields['DETAIL_PICTURE'];
						}
					}

					foreach($newIds as $natural => $tr)
					{
						$viewed[$natural]['PREVIEW_PICTURE'] =  $mapped[$tr]['DETAIL_PICTURE'];
						$viewed[$natural]['DETAIL_PICTURE'] =  $mapped[$tr]['PREVIEW_PICTURE'];
						$viewed[$natural]['PRODUCT_ID'] = $tr;
					}

					if(in_array("CURRENCY", $arSelectFields) || in_array("PRICE", $arSelectFields))
					{
						// Prices
						$priceIterator = CPrice::getList(array(), array("PRODUCT_ID" => $ids), false, false, array("PRODUCT_ID", "PRICE", "CURRENCY"));
						while($price = $priceIterator->fetch())
						{
							if(!isset($viewed[$price['PRODUCT_ID']]['PRICE']))
							{
								$viewed[$price['PRODUCT_ID']]['PRICE'] = $price['PRICE'];
								$viewed[$price['PRODUCT_ID']]['CURRENCY'] = $price['CURRENCY'];
							}
						}
					}
				}

				// resort
				$dbresult = new CDBResult();
				$dbresult->InitFromArray(array_values($viewed));

				return $dbresult;
			}
		}

		$arFields = array(
				"ID" => array("FIELD" => "V.ID", "TYPE" => "int"),
				"FUSER_ID" => array("FIELD" => "V.FUSER_ID", "TYPE" => "int"),
				"DATE_VISIT" => array("FIELD" => "V.DATE_VISIT", "TYPE" => "datetime"),
				"PRODUCT_ID" => array("FIELD" => "V.PRODUCT_ID", "TYPE" => "int"),
				"MODULE" => array("FIELD" => "V.MODULE", "TYPE" => "string"),
				"LID" => array("FIELD" => "V.LID", "TYPE" => "string"),
				"NAME" => array("FIELD" => "V.NAME", "TYPE" => "string"),
				"DETAIL_PAGE_URL" => array("FIELD" => "V.DETAIL_PAGE_URL", "TYPE" => "string"),
				"CURRENCY" => array("FIELD" => "V.CURRENCY", "TYPE" => "string"),
				"PRICE" => array("FIELD" => "V.PRICE", "TYPE" => "double"),
				"NOTES" => array("FIELD" => "V.NOTES", "TYPE" => "string"),
				"PREVIEW_PICTURE" => array("FIELD" => "V.PREVIEW_PICTURE", "TYPE" => "string"),
				"DETAIL_PICTURE" => array("FIELD" => "V.DETAIL_PICTURE", "TYPE" => "string"),
				"CALLBACK_FUNC" => array("FIELD" => "V.CALLBACK_FUNC", "TYPE" => "string"),
				"PRODUCT_PROVIDER_CLASS" => array("FIELD" => "V.PRODUCT_PROVIDER_CLASS", "TYPE" => "string")
		);

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_sale_viewed_product V ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arGroupBy) && count($arGroupBy) == 0)
		{
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) <= 0 )
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_sale_viewed_product B ";
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
			$strSql = $DB->TopSql($strSql, $arNavStartParams["nTopCount"]);

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	/**
	* The function delete old viewed
	*
	* @param
	* @return true false
	*/
	static public function _ClearViewed()
	{
		global $DB;

		$viewed_time = COption::GetOptionString("sale", "viewed_time", "90");
		$viewed_time = IntVal($viewed_time);

		$strSql =
			"DELETE ".
			"FROM b_sale_viewed_product ".
			"WHERE TO_DAYS(DATE_VISIT) < (TO_DAYS(NOW()) - ".$viewed_time.") LIMIT 1000";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	/**
	* The function clear viewed product for user
	*
	* @param int $FUSER_ID - inner basket user code
	* @param int $LIMIT - fields count for delete
	* @return true false
	*/
	static public function DeleteForUser($FUSER_ID, $LIMIT = NULL)
	{
		global $DB;

		$FUSER_ID = IntVal($FUSER_ID);
		if ($FUSER_ID <= 0)
			return false;

		foreach(GetModuleEvents("sale", "OnBeforeViewedDelete", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array($FUSER_ID))===false)
				return false;

		$strSqlLimit = "";
		if (!empty($LIMIT) && IntVal($LIMIT) > 0)
			$strSqlLimit = " ORDER BY DATE_VISIT DESC LIMIT ".IntVal($LIMIT);

		$DB->Query("DELETE FROM b_sale_viewed_product WHERE FUSER_ID = '".$FUSER_ID."' ".$strSqlLimit, true);

		foreach(GetModuleEvents("sale", "OnViewedDelete", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array($FUSER_ID))===false)
				return false;

		return true;
	}
}