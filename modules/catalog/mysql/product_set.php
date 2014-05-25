<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/product_set.php");

class CCatalogProductSet extends CCatalogProductSetAll
{
	static public function add($arFields)
	{
		global $DB;

		foreach (GetModuleEvents("catalog", "OnBeforeProductSetAdd", true) as $arEvent)
		{
			if (false === ExecuteModuleEventEx($arEvent, array(&$arFields)))
				return false;
		}

		if (!self::checkFields('ADD', $arFields, 0))
			return false;

		$arSet = $arFields;
		unset($arSet['ITEMS']);
		$arInsert = $DB->PrepareInsert("b_catalog_product_sets", $arFields);
		$strSql = "insert into b_catalog_product_sets(".$arInsert[0].") values(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$intID = intval($DB->LastID());
		if (0 < $intID)
		{
			foreach ($arFields['ITEMS'] as &$arOneItem)
			{
				$arOneItem['SET_ID'] = $intID;
				$arInsert = $DB->PrepareInsert("b_catalog_product_sets", $arOneItem);
				$strSql = "insert into b_catalog_product_sets(".$arInsert[0].") values(".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			unset($arOneItem);
			if (self::TYPE_SET == $arSet['TYPE'])
				CCatalogProduct::SetProductType($arSet['ITEM_ID'], CCatalogProduct::TYPE_SET);

			foreach (GetModuleEvents("catalog", "OnProductSetAdd", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($intID, $arFields));
			}
			return $intID;
		}
		return false;
	}

	static public function update($intID, $arFields)
	{
		global $DB;

		foreach (GetModuleEvents("catalog", "OnBeforeProductSetUpdate", true) as $arEvent)
		{
			if (false === ExecuteModuleEventEx($arEvent, array($intID, &$arFields)))
				return false;
		}

		if (!self::checkFields('UPDATE', $arFields, $intID))
			return false;

		if (!empty($arFields))
		{
			$arSet = $arFields;
			$boolItems = array_key_exists('ITEMS', $arFields);
			if ($boolItems)
				unset($arSet['ITEMS']);
			$strUpdate = $DB->PrepareUpdate("b_catalog_product_sets", $arSet);
			if (!empty($strUpdate))
			{
				$strSql = "update b_catalog_product_sets set ".$strUpdate." where ID = ".$intID;
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			if ($boolItems)
			{
				$arIDs = array();
				foreach ($arFields['ITEMS'] as &$arOneItem)
				{
					if (array_key_exists('ID', $arOneItem))
					{
						$intSubID = $arOneItem['ID'];
						unset($arOneItem['ID']);
						$strUpdate = $DB->PrepareUpdate("b_catalog_product_sets", $arOneItem);
						if (!empty($strUpdate))
						{
							$strSql = "update b_catalog_product_sets set ".$strUpdate." where ID = ".$intSubID;
							$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						}
					}
					else
					{
						$arInsert = $DB->PrepareInsert("b_catalog_product_sets", $arOneItem);
						$strSql = "insert into b_catalog_product_sets(".$arInsert[0].") values(".$arInsert[1].")";
						$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						$intSubID = intval($DB->LastID());
					}
					$arOneItem['ID'] = $intSubID;
					$arIDs[] = $intSubID;
				}
				unset($arOneItem);
				if (!empty($arIDs))
					self::deleteFromSet($intID, $arIDs);
			}
			$strSql = "select ID, ITEM_ID, TYPE from b_catalog_product_sets where ID = ".$intID;
			$rsSets = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arSet = $rsSets->Fetch())
			{
				if (self::TYPE_SET == $arSet['TYPE'])
					CCatalogProduct::SetProductType($arSet['ITEM_ID'], CCatalogProduct::TYPE_SET);
			}

			foreach (GetModuleEvents("catalog", "OnProductSetUpdate", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($intID, $arFields));
			}
			return true;
		}
		return false;
	}

	static public function delete($intID)
	{
		global $DB;

		$intID = intval($intID);
		if (0 >= $intID)
			return false;

		foreach (GetModuleEvents("catalog", "OnBeforeProductSetDelete", true) as $arEvent)
		{
			if (false === ExecuteModuleEventEx($arEvent, array($intID)))
				return false;
		}

		$arItem = self::getSetID($intID);
		if (!empty($arItem) && is_array($arItem))
		{
			$strSql = "delete from b_catalog_product_sets where SET_ID=".$arItem['ID'];
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$strSql = "delete from b_catalog_product_sets where ID=".$arItem['ID'];
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if (self::TYPE_SET == $arItem['TYPE'])
				CCatalogProduct::SetProductType($arItem['ITEM_ID'], CCatalogProduct::TYPE_PRODUCT);

			foreach (GetModuleEvents("catalog", "OnProductSetDelete", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($intID));
			}
			return true;
		}

		return false;
	}

	static public function getList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelect = array())
	{
		global $DB;

		$arFields = array(
			'ID' => array('FIELD' => 'CPS.ID', 'TYPE' => 'int'),
			'TYPE' => array('FIELD' => 'CPS.TYPE', 'TYPE' => 'int'),
			'SET_ID' => array('FIELD' => 'CPS.SET_ID', 'TYPE' => 'int'),
			'ACTIVE' => array('FIELD' => 'CPS.ACTIVE', 'TYPE' => 'char'),
			'OWNER_ID' => array('FIELD' => 'CPS.OWNER_ID', 'TYPE' => 'int'),
			'ITEM_ID' => array('FIELD' => 'CPS.ITEM_ID', 'TYPE' => 'int'),
			'QUANTITY' => array('FIELD' => 'CPS.QUANTITY', 'TYPE' => 'double'),
			'MEASURE' => array('FIELD' => 'CPS.MEASURE', 'TYPE' => 'int'),
			'DISCOUNT_PERCENT' => array('FIELD' => 'CPS.DISCOUNT_PERCENT', 'TYPE' => 'double'),
			'SORT' => array('FIELD' => 'CPS.SORT', 'TYPE' => 'int'),
			'CREATED_BY' => array('FIELD' => 'CPS.CREATED_BY', 'TYPE' => 'int'),
			'DATE_CREATE' => array('FIELD' => 'CPS.DATE_CREATE', 'TYPE' => 'datetime'),
			'MODIFIED_BY' => array('FIELD' => 'CPS.MODIFIED_BY', 'TYPE' => 'int'),
			'TIMESTAMP_X' => array('FIELD' => 'CPS.TIMESTAMP_X', 'TYPE' => 'datetime'),
			'XML_ID' => array('FIELD' => 'CPS.XML_ID', 'TYPE' => 'string')
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelect);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "select ".$arSqls["SELECT"]." from b_catalog_product_sets CPS ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " where ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " group by ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql = "select ".$arSqls["SELECT"]." from b_catalog_product_sets CPS ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " where ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " group by ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " order by ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && array_key_exists('nTopCount', $arNavStartParams))
		{
			$intTopCount = intval($arNavStartParams["nTopCount"]);
		}
		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "select COUNT('x') as CNT from b_catalog_product_sets CPS ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " where ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " group by ".$arSqls["GROUPBY"];

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
				$strSql .= " limit ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	static public function isProductInSet($intProductID, $intSetType = 0)
	{
		global $DB;

		$intProductID = intval($intProductID);
		if (0 >= $intProductID)
			return false;
		$intSetType = intval($intSetType);
		$strSql = 'select ID from b_catalog_product_sets where ITEM_ID='.$intProductID;
		if (self::TYPE_SET == $intSetType || self::TYPE_GROUP == $intSetType)
			$strSql .= ' and TYPE='.$intSetType;
		$strSql .= ' limit 1';
		$rsRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $rsRes->Fetch())
		{
			return true;
		}
		return false;

	}

	static public function isProductHaveSet($arProductID, $intSetType = 0)
	{
		global $DB;

		if (!is_array($arProductID))
			$arProductID = array($arProductID);
		CatalogClearArray($arProductID, false);
		if (empty($arProductID))
			return false;
		$intSetType = intval($intSetType);

		$strSql = 'select ID from b_catalog_product_sets where OWNER_ID in('.implode(', ', $arProductID).')';
		if (self::TYPE_SET == $intSetType || self::TYPE_GROUP == $intSetType)
			$strSql .= ' and TYPE='.$intSetType;
		$strSql .= ' limit 1';
		$rsRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $rsRes->Fetch())
		{
			return true;
		}
		return false;
	}

	static public function getAllSetsByProduct($intProductID, $intSetType)
	{
		global $DB;

		$intProductID = intval($intProductID);
		if (0 >= $intProductID)
			return false;
		$intSetType = intval($intSetType);
		if (self::TYPE_SET != $intSetType && self::TYPE_GROUP != $intSetType)
			return false;

		$arEmptySet = self::getEmptySet($intSetType);

		$boolSet = self::TYPE_SET == $intSetType;

		$arResult = array();
		$strSql = "select ID, SET_ID, ACTIVE, OWNER_ID, ITEM_ID, SORT";
		if ($boolSet)
			$strSql .= ", QUANTITY, MEASURE, DISCOUNT_PERCENT";
		$strSql .= " from b_catalog_product_sets where OWNER_ID=".$intProductID." AND TYPE=".$intSetType;
		$rsItems = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arItem = $rsItems->Fetch())
		{
			$arItem['ID'] = intval($arItem['ID']);
			$arItem['SET_ID'] = intval($arItem['SET_ID']);
			$arItem['OWNER_ID'] = intval($arItem['OWNER_ID']);
			$arItem['ITEM_ID'] = intval($arItem['ITEM_ID']);
			$arItem['SORT'] = intval($arItem['SORT']);

			$boolProduct = $arItem['ITEM_ID'] == $arItem['OWNER_ID'];
			$intSetID = ($boolProduct ? $arItem['ID'] : $arItem['SET_ID']);
			if ($boolSet)
			{
				$arItem['QUANTITY'] = (is_null($arItem['QUANTITY']) ? false : doubleval($arItem['QUANTITY']));
				$arItem['MEASURE'] = (is_null($arItem['MEASURE']) ? false : intval($arItem['MEASURE']));
				$arItem['DISCOUNT_PERCENT'] = (is_null($arItem['DISCOUNT_PERCENT']) ? false : $arItem['DISCOUNT_PERCENT']);
			}
			if ($boolProduct)
			{
				unset($arItem['OWNER_ID']);
				unset($arItem['SET_ID']);
				unset($arItem['ID']);

			}
			else
			{
				unset($arItem['SET_ID']);
				unset($arItem['OWNER_ID']);
				unset($arItem['ACTIVE']);
			}
			if (!array_key_exists($intSetID, $arResult))
			{
				$arResult[$intSetID] = $arEmptySet;
				$arResult[$intSetID]['SET_ID'] = $intSetID;
			}
			if ($boolProduct)
			{
				$arResult[$intSetID] = array_merge($arResult[$intSetID], $arItem);
			}
			else
			{
				$arResult[$intSetID]['ITEMS'][$arItem['ID']] = $arItem;
			}
		}

		return (!empty($arResult) ? $arResult : false);
	}

	static public function getSetByID($intID)
	{
		global $DB;

		$intID = intval($intID);
		if (0 >= $intID)
			return false;

		$arResult = array();
		$arItemList = array();
		$arOwner = array();
		$strSql = "select * from b_catalog_product_sets where ID=".$intID;
		$rsItems = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arItem = $rsItems->Fetch())
		{
			$arItem['ID'] = intval($arItem['ID']);
			$arItem['SET_ID'] = intval($arItem['SET_ID']);
			$arItem['OWNER_ID'] = intval($arItem['OWNER_ID']);
			$arItem['ITEM_ID'] = intval($arItem['ITEM_ID']);
			$arItem['SORT'] = intval($arItem['SORT']);
			$arItem['QUANTITY'] = (is_null($arItem['QUANTITY']) ? false : doubleval($arItem['QUANTITY']));
			$arItem['MEASURE'] =  (is_null($arItem['MEASURE']) ? false : intval($arItem['MEASURE']));
			$arItem['DISCOUNT_PERCENT'] =  (is_null($arItem['DISCOUNT_PERCENT']) ? false : doubleval($arItem['DISCOUNT_PERCENT']));

			$arResult = $arItem;
			$arResult['ITEMS'] = array();
			$strSql = "select * from b_catalog_product_sets where SET_ID=".$intID;
			$rsSubs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arSub = $rsSubs->Fetch())
			{
				$arSub['ID'] = intval($arSub['ID']);
				$arSub['SET_ID'] = intval($arSub['SET_ID']);
				$arSub['OWNER_ID'] = intval($arSub['OWNER_ID']);
				$arSub['ITEM_ID'] = intval($arSub['ITEM_ID']);
				$arSub['SORT'] = intval($arSub['SORT']);
				$arSub['QUANTITY'] = (is_null($arSub['QUANTITY']) ? false: doubleval($arSub['QUANTITY']));
				$arSub['MEASURE'] = (is_null($arSub['MEASURE']) ? false: intval($arSub['MEASURE']));
				$arSub['DISCOUNT_PERCENT'] = (is_null($arSub['DISCOUNT_PERCENT']) ? false : doubleval($arSub['DISCOUNT_PERCENT']));

				$arResult['ITEMS'][$arSub['ID']] = $arSub;
			}
		}
		return (!empty($arResult) ? $arResult : false);
	}

	static public function deleteAllSetsByProduct($intProductID, $intSetType)
	{
		global $DB;

		$intProductID = intval($intProductID);
		if (0 >= $intProductID)
			return false;
		$intSetType = intval($intSetType);
		if (self::TYPE_SET != $intSetType && self::TYPE_GROUP != $intSetType)
			return false;
		$strSql = 'delete from b_catalog_product_sets where OWNER_ID='.$intProductID.' and TYPE='.$intSetType;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (self::TYPE_SET == $intSetType)
			CCatalogProduct::SetProductType($intProductID, CCatalogProduct::TYPE_PRODUCT);
		return true;
	}

	protected function getSetID($intID)
	{
		global $DB;

		$intID = intval($intID);
		if (0 >= $intID)
			return false;

		$strSql = "select ID, TYPE, SET_ID, OWNER_ID, ITEM_ID from b_catalog_product_sets where ID=".$intID;
		$rsItems = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arItem = $rsItems->Fetch())
		{
			$arItem['ID'] = intval($arItem['ID']);
			$arItem['SET_ID'] = intval($arItem['SET_ID']);
			$arItem['OWNER_ID'] = intval($arItem['OWNER_ID']);
			$arItem['ITEM_ID'] = intval($arItem['ITEM_ID']);
			$arItem['TYPE'] = intval($arItem['TYPE']);
			if ($arItem['OWNER_ID'] == $arItem['ITEM_ID'] && 0 == $arItem['SET_ID'])
			{
				return $arItem;
			}
		}
		return false;
	}

	protected function deleteFromSet($intID, $arEx)
	{
		global $DB;

		$intID = intval($intID);
		if (0 >= $intID)
			return false;
		CatalogClearArray($arEx, false);
		if (empty($arEx))
			return false;

		$strSql = "delete from b_catalog_product_sets where SET_ID=".$intID." and ID NOT IN(".implode(', ', $arEx).")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return true;
	}
}
?>