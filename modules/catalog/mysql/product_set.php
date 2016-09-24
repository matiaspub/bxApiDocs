<?
use Bitrix\Main,
	Bitrix\Catalog;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/product_set.php");

class CCatalogProductSet extends CCatalogProductSetAll
{
	public static function add($arFields)
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
		$DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		$intID = (int)$DB->LastID();
		if (0 < $intID)
		{
			foreach ($arFields['ITEMS'] as &$arOneItem)
			{
				$arOneItem['SET_ID'] = $intID;
				$arInsert = $DB->PrepareInsert("b_catalog_product_sets", $arOneItem);
				$strSql = "insert into b_catalog_product_sets(".$arInsert[0].") values(".$arInsert[1].")";
				$DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			}
			unset($arOneItem);
			switch ($arSet['TYPE'])
			{
				case self::TYPE_SET:
					$setParams = self::createSetItemsParamsFromAdd($arFields['ITEMS']);
					self::fillSetItemsParams($setParams);
					self::calculateSetParams($arSet['ITEM_ID'], $setParams);
					break;
				case self::TYPE_GROUP:
					CCatalogProduct::Update($arSet['ITEM_ID'], array('BUNDLE' => Catalog\ProductTable::STATUS_YES));
					break;
			}

			foreach (GetModuleEvents("catalog", "OnProductSetAdd", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($intID, $arFields));
			}
			return $intID;
		}
		return false;
	}

	public static function update($intID, $arFields)
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
				$DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			}
			if ($boolItems)
			{
				$arIDs = array();
				foreach ($arFields['ITEMS'] as &$arOneItem)
				{
					if (isset($arOneItem['ID']))
					{
						$intSubID = $arOneItem['ID'];
						unset($arOneItem['ID']);
						$strUpdate = $DB->PrepareUpdate("b_catalog_product_sets", $arOneItem);
						if (!empty($strUpdate))
						{
							$strSql = "update b_catalog_product_sets set ".$strUpdate." where ID = ".$intSubID;
							$DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
						}
					}
					else
					{
						$arInsert = $DB->PrepareInsert("b_catalog_product_sets", $arOneItem);
						$strSql = "insert into b_catalog_product_sets(".$arInsert[0].") values(".$arInsert[1].")";
						$DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
						$intSubID = (int)$DB->LastID();
					}
					$arOneItem['ID'] = $intSubID;
					$arIDs[] = $intSubID;
				}
				unset($arOneItem);
				if (!empty($arIDs))
					self::deleteFromSet($intID, $arIDs);
			}
			$strSql = "select ID, ITEM_ID, TYPE from b_catalog_product_sets where ID = ".$intID;
			$rsSets = $DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			if ($arSet = $rsSets->Fetch())
			{
				switch ($arSet['TYPE'])
				{
					case self::TYPE_SET:
						$setParams = self::createSetItemsParamsFromUpdate($intID);
						self::fillSetItemsParams($setParams);
						self::calculateSetParams($arSet['ITEM_ID'], $setParams);
						break;
					case self::TYPE_GROUP:
						CCatalogProduct::Update($arSet['ITEM_ID'], array('BUNDLE' => Catalog\ProductTable::STATUS_YES));
				}
			}

			foreach (GetModuleEvents("catalog", "OnProductSetUpdate", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($intID, $arFields));
			}
			return true;
		}
		return false;
	}

	public static function delete($intID)
	{
		global $DB;

		$intID = (int)$intID;
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
			$DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			$strSql = "delete from b_catalog_product_sets where ID=".$arItem['ID'];
			$DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			switch ($arItem['TYPE'])
			{
				case self::TYPE_SET:
					CCatalogProduct::SetProductType($arItem['ITEM_ID'], CCatalogProduct::TYPE_PRODUCT);
					break;
				case self::TYPE_GROUP:
					if (!static::isProductHaveSet($arItem['ITEM_ID'], self::TYPE_GROUP))
						CCatalogProduct::Update($arItem['ITEM_ID'], array('BUNDLE' => Catalog\ProductTable::STATUS_NO));
					break;
			}

			foreach (GetModuleEvents("catalog", "OnProductSetDelete", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($intID));
			}
			return true;
		}

		return false;
	}

	public static function getList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelect = array())
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

			$dbRes = $DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
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
		if ($boolNavStartParams && isset($arNavStartParams['nTopCount']))
		{
			$intTopCount = (int)$arNavStartParams["nTopCount"];
		}
		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "select COUNT('x') as CNT from b_catalog_product_sets CPS ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " where ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " group by ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql_tmp, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
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
			$dbRes = $DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		}

		return $dbRes;
	}

	public static function isProductInSet($intProductID, $intSetType = 0)
	{
		global $DB;

		$intProductID = (int)$intProductID;
		if (0 >= $intProductID)
			return false;
		$intSetType = (int)$intSetType;
		$strSql = 'select ID from b_catalog_product_sets where ITEM_ID='.$intProductID;
		if (self::TYPE_SET == $intSetType || self::TYPE_GROUP == $intSetType)
			$strSql .= ' and TYPE='.$intSetType;
		$strSql .= ' limit 1';
		$rsRes = $DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		if ($arRes = $rsRes->Fetch())
		{
			return true;
		}
		return false;

	}

	public static function isProductHaveSet($arProductID, $intSetType = 0)
	{
		global $DB;

		if (!is_array($arProductID))
			$arProductID = array($arProductID);
		CatalogClearArray($arProductID, false);
		if (empty($arProductID))
			return false;
		$intSetType = (int)$intSetType;

		$strSql = 'select ID from b_catalog_product_sets where OWNER_ID in('.implode(', ', $arProductID).')';
		if (self::TYPE_SET == $intSetType || self::TYPE_GROUP == $intSetType)
			$strSql .= ' and TYPE='.$intSetType;
		$strSql .= ' limit 1';
		$rsRes = $DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		if ($arRes = $rsRes->Fetch())
		{
			return true;
		}
		return false;
	}

	public static function getAllSetsByProduct($intProductID, $intSetType)
	{
		global $DB;

		$intProductID = (int)$intProductID;
		if (0 >= $intProductID)
			return false;
		$intSetType = (int)$intSetType;
		if (self::TYPE_SET != $intSetType && self::TYPE_GROUP != $intSetType)
			return false;

		$arEmptySet = self::getEmptySet($intSetType);

		$boolSet = self::TYPE_SET == $intSetType;

		$arResult = array();
		$strSql = "select ID, SET_ID, ACTIVE, OWNER_ID, ITEM_ID, SORT, QUANTITY, MEASURE";
		if ($boolSet)
			$strSql .= ", DISCOUNT_PERCENT";
		$strSql .= " from b_catalog_product_sets where OWNER_ID=".$intProductID." AND TYPE=".$intSetType;
		$rsItems = $DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		while ($arItem = $rsItems->Fetch())
		{
			$arItem['ID'] = (int)$arItem['ID'];
			$arItem['SET_ID'] = (int)$arItem['SET_ID'];
			$arItem['OWNER_ID'] = (int)$arItem['OWNER_ID'];
			$arItem['ITEM_ID'] = (int)$arItem['ITEM_ID'];
			$arItem['SORT'] = (int)$arItem['SORT'];

			$boolProduct = $arItem['ITEM_ID'] == $arItem['OWNER_ID'];
			$intSetID = ($boolProduct ? $arItem['ID'] : $arItem['SET_ID']);
			if ($boolSet)
			{
				$arItem['QUANTITY'] = (is_null($arItem['QUANTITY']) ? false : doubleval($arItem['QUANTITY']));
				$arItem['MEASURE'] = (is_null($arItem['MEASURE']) ? false : (int)$arItem['MEASURE']);
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
			if (!isset($arResult[$intSetID]))
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

	public static function getSetByID($intID)
	{
		global $DB;

		$intID = (int)$intID;
		if ($intID <= 0)
			return false;

		$arResult = array();
		$arItemList = array();
		$arOwner = array();
		$strSql = "select * from b_catalog_product_sets where ID=".$intID;
		$rsItems = $DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		if ($arItem = $rsItems->Fetch())
		{
			$arItem['ID'] = (int)$arItem['ID'];
			$arItem['SET_ID'] = (int)$arItem['SET_ID'];
			$arItem['OWNER_ID'] = (int)$arItem['OWNER_ID'];
			$arItem['ITEM_ID'] = (int)$arItem['ITEM_ID'];
			$arItem['SORT'] = (int)$arItem['SORT'];
			$arItem['QUANTITY'] = (is_null($arItem['QUANTITY']) ? false : doubleval($arItem['QUANTITY']));
			$arItem['MEASURE'] =  (is_null($arItem['MEASURE']) ? false : (int)$arItem['MEASURE']);
			$arItem['DISCOUNT_PERCENT'] =  (is_null($arItem['DISCOUNT_PERCENT']) ? false : doubleval($arItem['DISCOUNT_PERCENT']));

			$arResult = $arItem;
			$arResult['ITEMS'] = array();
			$strSql = "select * from b_catalog_product_sets where SET_ID=".$intID;
			$rsSubs = $DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			while ($arSub = $rsSubs->Fetch())
			{
				$arSub['ID'] = (int)$arSub['ID'];
				$arSub['SET_ID'] = (int)$arSub['SET_ID'];
				$arSub['OWNER_ID'] = (int)$arSub['OWNER_ID'];
				$arSub['ITEM_ID'] = (int)$arSub['ITEM_ID'];
				$arSub['SORT'] = (int)$arSub['SORT'];
				$arSub['QUANTITY'] = (is_null($arSub['QUANTITY']) ? false: doubleval($arSub['QUANTITY']));
				$arSub['MEASURE'] = (is_null($arSub['MEASURE']) ? false: (int)$arSub['MEASURE']);
				$arSub['DISCOUNT_PERCENT'] = (is_null($arSub['DISCOUNT_PERCENT']) ? false : doubleval($arSub['DISCOUNT_PERCENT']));

				$arResult['ITEMS'][$arSub['ID']] = $arSub;
			}
		}
		return (!empty($arResult) ? $arResult : false);
	}

	public static function recalculateSetsByProduct($product)
	{
		global $DB;

		if (self::$recalculateSet < 0)
			return;

		$setsList = array();
		$setsID = array();
		$product = (int)$product;
		$query = 'select SET_ID, OWNER_ID, ITEM_ID from b_catalog_product_sets where ITEM_ID='.$product.' and TYPE='.self::TYPE_SET;
		$setIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		while ($setItem = $setIterator->Fetch())
		{
			$setItem['SET_ID'] = (int)$setItem['SET_ID'];
			$setItem['OWNER_ID'] = (int)$setItem['OWNER_ID'];
			$setItem['ITEM_ID'] = (int)$setItem['ITEM_ID'];
			if ($setItem['ITEM_ID'] === $setItem['OWNER_ID'])
				continue;
			$setsList[$setItem['OWNER_ID']] = array();
			$setsID[] = $setItem['SET_ID'];
		}
		unset($setItem, $setIterator, $query);

		if (!empty($setsID))
		{
			$productMap = array();
			$query = 'select SET_ID, OWNER_ID, ITEM_ID, QUANTITY as QUANTITY_IN_SET from b_catalog_product_sets where SET_ID IN('.implode(',', $setsID).')';
			$setIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			while ($setItem = $setIterator->Fetch())
			{
				$setItem['SET_ID'] = (int)$setItem['SET_ID'];
				$setItem['OWNER_ID'] = (int)$setItem['OWNER_ID'];
				$setItem['ITEM_ID'] = (int)$setItem['ITEM_ID'];
				if ($setItem['ITEM_ID'] === $setItem['OWNER_ID'])
					continue;
				if (!isset($setsList[$setItem['OWNER_ID']]))
					$setsList[$setItem['OWNER_ID']] = array();
				$setsList[$setItem['OWNER_ID']][$setItem['ITEM_ID']] = $setItem;
				if (!isset($productMap[$setItem['ITEM_ID']]))
					$productMap[$setItem['ITEM_ID']] = array();
				$productMap[$setItem['ITEM_ID']][] = $setItem['OWNER_ID'];
			}
			unset($setItem, $setIterator, $query);

			$productIterator = CCatalogProduct::GetList(
				array(),
				array('=ID' => array_keys($productMap)),
				false,
				false,
				array('ID', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'WEIGHT')
			);
			while ($item = $productIterator->Fetch())
			{
				$item['ID'] = (int)$item['ID'];
				if (!isset($productMap[$item['ID']]))
					continue;
				foreach ($productMap[$item['ID']] as &$setKey)
					$setsList[$setKey][$item['ID']] = array_merge($setsList[$setKey][$item['ID']], $item);
				unset($setKey);
			}
			unset($item, $productIterator);

			$setsList = array_filter($setsList);
			if (!empty($setsList))
			{
				foreach ($setsList as $setKey => $oneSet)
					static::calculateSetParams($setKey, $oneSet);
				unset($setKey, $oneSet);
			}
		}
	}

	protected static function getSetID($intID)
	{
		global $DB;

		$intID = (int)$intID;
		if (0 >= $intID)
			return false;

		$strSql = "select ID, TYPE, SET_ID, OWNER_ID, ITEM_ID from b_catalog_product_sets where ID=".$intID;
		$rsItems = $DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		if ($arItem = $rsItems->Fetch())
		{
			$arItem['ID'] = (int)$arItem['ID'];
			$arItem['SET_ID'] = (int)$arItem['SET_ID'];
			$arItem['OWNER_ID'] = (int)$arItem['OWNER_ID'];
			$arItem['ITEM_ID'] = (int)$arItem['ITEM_ID'];
			$arItem['TYPE'] = (int)$arItem['TYPE'];
			if ($arItem['OWNER_ID'] == $arItem['ITEM_ID'] && 0 == $arItem['SET_ID'])
			{
				return $arItem;
			}
		}
		return false;
	}

	protected static function deleteFromSet($intID, $arEx)
	{
		global $DB;

		$intID = (int)$intID;
		if (0 >= $intID)
			return false;
		Main\Type\Collection::normalizeArrayValuesByInt($arEx, false);
		if (empty($arEx))
			return false;

		$strSql = "delete from b_catalog_product_sets where SET_ID=".$intID." and ID NOT IN(".implode(', ', $arEx).")";
		$DB->Query($strSql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		return true;
	}

	protected static function calculateSetParams($productID, $items)
	{
		global $DB;

		if (empty($items) || !is_array($items))
			return false;

		$quantityTrace = 'N';
		$canBuyZero = 'Y';
		$quantity = null;
		$weight = 0;

		$allItems = true;
		$tracedItems = array_filter($items, 'CCatalogProductSet::isTracedItem');
		if (empty($tracedItems))
		{
			$tracedItems = $items;
		}
		else
		{
			$allItems = false;
			$quantityTrace = 'Y';
			$canBuyZero = 'N';
			foreach ($items as &$oneItem)
				$weight += $oneItem['WEIGHT']*$oneItem['QUANTITY_IN_SET'];
			unset($oneItem);
		}
		foreach ($tracedItems as &$oneItem)
		{
			if ($oneItem['QUANTITY'] <= 0)
				$itemQuantity = 0;
			else
				$itemQuantity = (int)floor($oneItem['QUANTITY']/$oneItem['QUANTITY_IN_SET']);

			if ($quantity === null || $quantity > $itemQuantity)
				$quantity = $itemQuantity;
			if ($allItems)
				$weight += $oneItem['WEIGHT']*$oneItem['QUANTITY_IN_SET'];
		}
		unset($oneItem);

		$measure = CCatalogMeasure::getDefaultMeasure(true, false);

		$fields = array(
			'WEIGHT' => $weight,
			'QUANTITY' => $quantity,
			'QUANTITY_TRACE' => $quantityTrace,
			'CAN_BUY_ZERO' => $canBuyZero,
			'MEASURE' => $measure['ID'],
			'TYPE' => CCatalogProduct::TYPE_SET
		);
		$fields['AVAILABLE'] = (CCatalogProduct::isAvailable($fields) ? 'Y' : 'N');

		if($productData = Catalog\ProductTable::getRowById($productID))
		{
			$fields['SUBSCRIBE'] = $productData['SUBSCRIBE'];
			if(Catalog\SubscribeTable::checkPermissionSubscribe($productData['SUBSCRIBE']))
				Catalog\SubscribeTable::setOldProductAvailable($productID, $productData['AVAILABLE']);
		}

		foreach(GetModuleEvents('catalog', 'OnBeforeProductSetAvailableUpdate', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($productID, &$fields));

		$update = $DB->PrepareUpdate('b_catalog_product', $fields);

		$query = "update b_catalog_product set ".$update." where ID = ".$productID;
		$DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);

		foreach (GetModuleEvents('catalog', 'OnProductSetAvailableUpdate', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($productID, $fields));

		$query = "delete from b_catalog_measure_ratio where PRODUCT_ID = ".$productID;
		$DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		$fields = array(
			'PRODUCT_ID' => $productID,
			'RATIO' => 1
		);
		$insert = $DB->PrepareInsert('b_catalog_measure_ratio', $fields);
		$query = "insert into b_catalog_measure_ratio (".$insert[0].") values(".$insert[1].")";
		$DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);

		return true;
	}

	protected static function createSetItemsParamsFromUpdate($setID, $getProductID = false)
	{
		global $DB;
		$result = array();

		$getProductID = ($getProductID === true);
		if ($getProductID)
		{
			$result = array(
				'ITEM_ID' => 0,
				'ITEMS' => array()
			);
			$query = 'select ID, ITEM_ID, QUANTITY from b_catalog_product_sets where SET_ID = '.$setID.' or ID = '.$setID;
			$setIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			while ($item = $setIterator->Fetch())
			{
				$item['ID'] = (int)$item['ID'];
				if ($item['ID'] == $setID)
				{
					$result['ITEM_ID'] = $item['ID'];
				}
				else
				{
					$item['ITEM_ID'] = (int)$item['ITEM_ID'];
					$result['ITEMS'][$item['ITEM_ID']] = array(
						'QUANTITY_IN_SET' => $item['QUANTITY']
					);
				}
			}
		}
		else
		{
			$query = 'select ITEM_ID, QUANTITY from b_catalog_product_sets where SET_ID = '.$setID;
			$setIterator = $DB->Query($query, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
			while ($item = $setIterator->Fetch())
			{
				$item['ITEM_ID'] = (int)$item['ITEM_ID'];
				$result[$item['ITEM_ID']] = array(
					'QUANTITY_IN_SET' => $item['QUANTITY']
				);
			}
		}
		return $result;
	}
}