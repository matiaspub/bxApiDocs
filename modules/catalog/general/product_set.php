<?
IncludeModuleLangFile(__FILE__);

class CCatalogProductSetAll
{
	const TYPE_SET = 1;
	const TYPE_GROUP = 2;

	protected static $arErrors = array();
	protected static $disableShowErrors = 0;
	protected static $disableCheckProduct = false;
	protected static $recalculateSet = 0;

	public static function enableShowErrors()
	{
		self::$disableShowErrors++;
	}

	public static function disableShowErrors()
	{
		self::$disableShowErrors--;
	}

	public static function isEnabledShowErrors()
	{
		return (self::$disableShowErrors >= 0);
	}

	public static function enableRecalculateSet()
	{
		self::$recalculateSet++;
	}

	public static function disableRecalculateSet()
	{
		self::$recalculateSet--;
	}

	public static function isEnabledRecalculateSet()
	{
		return (self::$recalculateSet >= 0);
	}

	public static function getErrors()
	{
		return self::$arErrors;
	}

	public static function setCheckParams($params)
	{
		if (!empty($params) && is_array($params))
		{
			if (isset($params['CHECK_PRODUCT']))
			{
				self::$disableCheckProduct = ('N' == $params['CHECK_PRODUCT']);
			}
		}
	}

	public static function clearCheckParams()
	{
		self::$disableCheckProduct = false;
	}

	public static function checkFields($strAction, &$arFields, $intID = 0)
	{
		self::$arErrors = array();

		$strAction = strtoupper($strAction);
		if ('ADD' != $strAction && 'UPDATE' != $strAction && 'TEST' != $strAction)
			return false;
		if (empty($arFields) || !is_array($arFields))
			return false;
		if (array_key_exists('ID', $arFields))
			unset($arFields['ID']);

		if (array_key_exists('DATE_CREATE', $arFields))
			unset($arFields['DATE_CREATE']);
		if (array_key_exists('TIMESTAMP_X', $arFields))
			unset($arFields['TIMESTAMP_X']);

		$boolCheck = ('UPDATE' != $strAction ? self::checkFieldsToAdd($arFields, 'TEST' == $strAction) : self::checkFieldsToUpdate($intID, $arFields));
		if (!$boolCheck || !empty(self::$arErrors))
		{
			if (self::$disableShowErrors >= 0)
			{
				global $APPLICATION;
				$obError = new CAdminException(self::$arErrors);
				$APPLICATION->ResetException();
				$APPLICATION->ThrowException($obError);
			}
			return false;
		}
		return true;
	}

	static public function add($arFields)
	{
		return false;
	}

	static public function update($intID, $arFields)
	{
		return false;
	}

	static public function delete($intID)
	{
		return false;
	}

	static public function isProductInSet($intProductID, $intSetType = 0)
	{
		return false;
	}

	static public function isProductHaveSet($arProductID, $intSetType = 0)
	{
		return false;
	}

	static public function canCreateSetByProduct($intProductID, $intSetType)
	{
		$intProductID = (int)$intProductID;
		if ($intProductID <= 0)
			return false;

		if (self::isProductInSet($intProductID, $intSetType))
			return false;

		if (CCatalogSku::IsExistOffers($intProductID))
			return false;

		return true;
	}

	static public function getAllSetsByProduct($intProductID, $intSetType)
	{
		return false;
	}

	static public function getSetByID($intID)
	{
		return false;
	}

	static public function deleteAllSetsByProduct($intProductID, $intSetType)
	{
		return true;
	}

	static public function recalculateSetsByProduct($product)
	{

	}

	static public function recalculateSet($setID, $productID = 0)
	{
		$setID = (int)$setID;
		$productID = (int)$productID;
		if ($setID > 0)
		{
			if ($productID > 0)
			{
				$setParams = self::createSetItemsParamsFromUpdate($setID, false);
			}
			else
			{
				$extSetParams = self::createSetItemsParamsFromUpdate($setID, true);
				$productID = $extSetParams['ITEM_ID'];
				$setParams = $extSetParams['ITEMS'];
				unset($extSetParams);
			}
			self::fillSetItemsParams($setParams);
			self::calculateSetParams($productID, $setParams);
		}
	}

	protected function checkFieldsToAdd(&$arFields, $boolCheckNew = false)
	{
		global $DB;
		global $USER;

		$boolCheckNew = !!$boolCheckNew;

		$intCurrentUser = 0;
		if (CCatalog::IsUserExists())
			$intCurrentUser = (int)$USER->GetID();
		if ($intCurrentUser <= 0)
			$intCurrentUser = false;

		$strTimeFunc = $DB->GetNowFunction();

		$arProductInSet = array();
		$dblDiscountPercent = 0;

		$arDefItem = self::getEmptyItemFields();

		$arFields = array_merge(self::getEmptyFields(), $arFields);

		if (empty(self::$arErrors))
		{
			$arFields['ITEM_ID'] = (int)$arFields['ITEM_ID'];
			if (!$boolCheckNew)
			{
				if (0 >= $arFields['ITEM_ID'])
					self::$arErrors[] = array('id' => 'ITEM_ID', 'text' => GetMessage('BT_CAT_SET_ERR_PRODUCT_ID_IS_BAD'));
			}
			else
			{
				if (0 > $arFields['ITEM_ID'])
					self::$arErrors[] = array('id' => 'ITEM_ID', 'text' => GetMessage('BT_CAT_SET_ERR_PRODUCT_ID_IS_BAD'));
			}

			$arFields['TYPE'] = (int)$arFields['TYPE'];
			if (self::TYPE_SET != $arFields['TYPE'] && self::TYPE_GROUP != $arFields['TYPE'])
				self::$arErrors[] = array('id' => 'TYPE', 'text' => GetMessage('BT_CAT_SET_ERR_TYPE_IS_BAD'));

			if (empty($arFields['ITEMS']) || !is_array($arFields['ITEMS']))
				self::$arErrors[] = array('id' => 'ITEMS', 'text' => GetMessage('BT_CAT_SET_ERR_ITEMS_IS_ABSENT'));
		}

		if (empty(self::$arErrors))
		{
			$arFields['QUANTITY'] = false;
			$arFields['MEASURE'] = false;
			$arFields['DISCOUNT_PERCENT'] = false;
		}
		if (empty(self::$arErrors))
		{
			if (0 < $arFields['ITEM_ID'])
				$arProductInSet[$arFields['ITEM_ID']] = true;

			$arValidItems = array();
			foreach ($arFields['ITEMS'] as &$arOneItem)
			{
				if (empty($arOneItem) || !is_array($arOneItem))
					continue;
				$arOneItem = array_merge($arDefItem, $arOneItem);
				$arOneItem['ITEM_ID'] = (int)$arOneItem['ITEM_ID'];
				if (0 >= $arOneItem['ITEM_ID'])
					continue;
				if (isset($arProductInSet[$arOneItem['ITEM_ID']]))
				{
					self::$arErrors[] = array('id' => 'ITEM_ID', 'text' => GetMessage('BT_CAT_PRODUCT_SET_ERR_ITEM_ID_DUBLICATE'));
					continue;
				}
				$arProductInSet[$arOneItem['ITEM_ID']] = true;
				$arOneItem['QUANTITY'] = doubleval($arOneItem['QUANTITY']);
				if (0 >= $arOneItem['QUANTITY'])
				{
					self::$arErrors[] = array(
						'id' => 'QUANTITY',
						'text' => (
							self::TYPE_SET == $arFields['TYPE']
							? GetMessage('BT_CAT_PRODUCT_SET_ERR_QUANTITY_IS_BAD')
							: GetMessage('BT_CAT_PRODUCT_SET_ERR_QUANTITY_GROUP_IS_BAD')
						)
					);
					continue;
				}
				if (self::TYPE_SET == $arFields['TYPE'])
				{
					$arOneItem['MEASURE'] = (int)$arOneItem['MEASURE'];
					if (0 > $arOneItem['MEASURE'])
						$arOneItem['MEASURE'] = 0;

					if (false !== $arOneItem['DISCOUNT_PERCENT'])
					{
						$arOneItem['DISCOUNT_PERCENT'] = doubleval($arOneItem['DISCOUNT_PERCENT']);
						if (0 > $arOneItem['DISCOUNT_PERCENT'] || 100 < $arOneItem['DISCOUNT_PERCENT'])
						{
							self::$arErrors[] = array('id' => 'DISCOUNT_PERCENT', 'text' => GetMessage('BT_CAT_PRODUCT_SET_ERR_DISCOUNT_PERCENT_IS_BAD'));
							continue;
						}
						$dblDiscountPercent += $arOneItem['DISCOUNT_PERCENT'];
					}
				}
				else
				{
					$arOneItem['MEASURE'] = false;
					$arOneItem['DISCOUNT_PERCENT'] = false;
				}
				$arValidItems[] = $arOneItem;
			}
			unset($arOneItem);
			if (empty($arValidItems))
				self::$arErrors[] = array('id' => 'ITEMS', 'text' => GetMessage('BT_CAT_SET_ERR_EMPTY_VALID_ITEMS'));
			else
				$arFields['ITEMS'] = $arValidItems;
			unset($arValidItems);
			if (100 < $dblDiscountPercent)
				self::$arErrors[] = array('id' => 'DISCOUNT_PERCENT', 'text' => GetMessage('BT_CAT_PRODUCT_SET_ERR_ALL_DISCOUNT_PERCENT_IS_BAD'));
		}

		if (empty(self::$arErrors))
		{
			$arProductList = array_keys($arProductInSet);
			if (!self::$disableCheckProduct)
			{
				if ($arFields['TYPE'] == self::TYPE_GROUP)
				{
					$checkProductList = $arProductInSet;
					if ($arFields['ITEM_ID'] > 0)
						unset($checkProductList[$arFields['ITEM_ID']]);
					$checkProductList = array_keys($checkProductList);
				}
				else
				{
					$checkProductList = $arProductList;
				}
				if (!CCatalogProduct::CheckProducts($checkProductList))
				{
					self::$arErrors[] = array('id' => 'ITEMS', 'text' => GetMessage('BT_CAT_PRODUCT_SET_ERR_ITEMS_IS_NOT_PRODUCT'));
				}
				unset($checkProductList);
			}
			if (empty(self::$arErrors) && self::TYPE_SET == $arFields['TYPE'])
			{
				if (CCatalogProductSet::isProductHaveSet($arProductList, self::TYPE_SET))
				{
					self::$arErrors[] = array('id' => 'ITEMS', 'text' => GetMessage('BT_CAT_PRODUCT_SET_ERR_ITEMS_IS_SET'));
				}
				$existSKU = array_filter(CCatalogSKU::getExistOffers($arProductList));
				if (!empty($existSKU))
				{
					self::$arErrors[] = array('id' => 'ITEMS', 'text' => GetMessage('BT_CAT_PRODUCT_SET_ERR_ITEMS_IS_SKU'));
				}
				unset($existSKU);
			}
		}

		if (empty(self::$arErrors))
		{
			$arFields['ACTIVE'] = ('N' != $arFields['ACTIVE'] ? 'Y' : 'N');
			$arFields['SORT'] = (int)$arFields['SORT'];
			if (0 >= $arFields['SORT'])
					$arFields['SORT'] = 100;

			$arFields['SET_ID'] = 0;
			$arFields['OWNER_ID'] = $arFields['ITEM_ID'];
			$arFields['~DATE_CREATE'] = $strTimeFunc;
			$arFields['~TIMESTAMP_X'] = $strTimeFunc;
			$arFields['CREATED_BY'] = (!array_key_exists('CREATED_BY', $arFields) ? 0 : (int)$arFields['CREATED_BY']);
			if (0 >= $arFields['CREATED_BY'])
				$arFields['CREATED_BY'] = $intCurrentUser;
			$arFields['MODIFIED_BY'] = (!array_key_exists('MODIFIED_BY', $arFields) ? 0 : (int)$arFields['MODIFIED_BY']);
			if (0 >= $arFields['MODIFIED_BY'])
				$arFields['MODIFIED_BY'] = $intCurrentUser;

			self::setItemFieldsForAdd($arFields);
		}
		return empty(self::$arErrors);
	}

	protected function checkFieldsToUpdate($intID, &$arFields)
	{
		global $DB;
		global $USER;

		$intCurrentUser = 0;
		if (CCatalog::IsUserExists())
			$intCurrentUser = (int)$USER->GetID();
		if ($intCurrentUser <= 0)
			$intCurrentUser = false;

		$strTimeFunc = $DB->GetNowFunction();

		$arDefItem = self::getEmptyItemFields();

		$arProductInSet = array();
		$dblDiscountPercent = 0;
		$boolItems = false;

		$intID = (int)$intID;
		if ($intID <= 0)
			self::$arErrors[] = array('id' => 'ID', 'text' => GetMessage('BT_CAT_SET_ERR_ID_IS_BAD'));

		$arCurrent = array();
		if (empty(self::$arErrors))
		{
			$arCurrent = CCatalogProductSet::getSetByID($intID);
			if (empty($arCurrent))
				self::$arErrors[] = array('id' => 'ID', 'text' => GetMessage('BT_CAT_SET_ERR_ID_IS_BAD'));
		}
		if (empty(self::$arErrors))
		{
			self::clearFieldsForUpdate($arFields, $arCurrent['TYPE']);
			if (array_key_exists('ACTIVE', $arFields))
				$arFields['ACTIVE'] = ('N' != $arFields['ACTIVE'] ? 'Y' : 'N');
			if (array_key_exists('SORT', $arFields))
			{
				$arFields['SORT'] = (int)$arFields['SORT'];
				if ($arFields['SORT'] <= 0)
					$arFields['SORT'] = 100;
			}

			$arFields['MODIFIED_BY'] = (!array_key_exists('MODIFIED_BY', $arFields) ? 0 : (int)$arFields['MODIFIED_BY']);
			if ($arFields['MODIFIED_BY'] <= 0)
				$arFields['MODIFIED_BY'] = $intCurrentUser;

			$arFields['~TIMESTAMP_X'] = $strTimeFunc;
		}

		if (empty(self::$arErrors))
		{
			$arProductInSet[$arCurrent['ITEM_ID']] = true;

			if (array_key_exists('ITEMS', $arFields))
			{
				if (empty($arFields['ITEMS']) || !is_array($arFields['ITEMS']))
				{
					self::$arErrors[] = array('id' => 'ITEMS', 'text' => GetMessage('BT_CAT_SET_ERR_ITEMS_IS_ABSENT'));
				}
				else
				{
					$arValidItems = array();
					foreach ($arFields['ITEMS'] as &$arOneItem)
					{
						if (empty($arOneItem) || !is_array($arOneItem))
							continue;
						if (array_key_exists('ID', $arOneItem))
							unset($arOneItem['ID']);
						if (!array_key_exists('ITEM_ID', $arOneItem))
							continue;
						$arOneItem['ITEM_ID'] = (int)$arOneItem['ITEM_ID'];
						if ($arOneItem['ITEM_ID'] <= 0)
							continue;
						if (isset($arProductInSet[$arOneItem['ITEM_ID']]))
						{
							self::$arErrors[] = array('id' => 'ITEM_ID', 'text' => GetMessage('BT_CAT_PRODUCT_SET_ERR_ITEM_ID_DUBLICATE'));
							continue;
						}
						$arProductInSet[$arOneItem['ITEM_ID']] = true;
						$intRowID = self::searchItem($arOneItem['ITEM_ID'], $arCurrent['ITEMS']);
						if (false === $intRowID)
						{
							$arOneItem = array_merge($arDefItem, $arOneItem);
						}
						else
						{
							$arOneItem['ID'] = $intRowID;
						}
						if (array_key_exists('SORT', $arOneItem))
						{
							$arOneItem['SORT'] = (int)$arOneItem['SORT'];
							if ($arOneItem['SORT'] <= 0)
								$arOneItem['SORT'] = 100;
						}
						if (array_key_exists('QUANTITY', $arOneItem))
						{
							$arOneItem['QUANTITY'] = doubleval($arOneItem['QUANTITY']);
							if (0 >= $arOneItem['QUANTITY'])
							{
								self::$arErrors[] = array(
									'id' => 'QUANTITY',
									'text' => (
										self::TYPE_SET == $arFields['TYPE']
										? GetMessage('BT_CAT_PRODUCT_SET_ERR_QUANTITY_IS_BAD')
										: GetMessage('BT_CAT_PRODUCT_SET_ERR_QUANTITY_GROUP_IS_BAD')
									)
								);
								continue;
							}
						}
						if (self::TYPE_SET == $arCurrent['TYPE'])
						{
							if (array_key_exists('MEASURE', $arOneItem))
							{
								$arOneItem['MEASURE'] = (int)$arOneItem['MEASURE'];
								if ($arOneItem['MEASURE'] < 0)
									$arOneItem['MEASURE'] = 0;
							}
							if (array_key_exists('DISCOUNT_PERCENT', $arOneItem))
							{
								if (false !== $arOneItem['DISCOUNT_PERCENT'])
								{
									$arOneItem['DISCOUNT_PERCENT'] = doubleval($arOneItem['DISCOUNT_PERCENT']);
									if (0 > $arOneItem['DISCOUNT_PERCENT'] || 100 < $arOneItem['DISCOUNT_PERCENT'])
									{
										self::$arErrors[] = array('id' => 'DISCOUNT_PERCENT', 'text' => GetMessage('BT_CAT_PRODUCT_SET_ERR_DISCOUNT_PERCENT_IS_BAD'));
										continue;
									}
									$dblDiscountPercent += $arOneItem['DISCOUNT_PERCENT'];
								}
							}
							else
							{
								if (false !== $intRowID)
								{
									if (false !== $arCurrent['ITEMS'][$intRowID]['DISCOUNT_PERCENT'])
										$dblDiscountPercent += $arCurrent['ITEMS'][$intRowID]['DISCOUNT_PERCENT'];
								}
							}
						}

						$arValidItems[] = $arOneItem;
					}
					unset($arOneItem);
					if (empty($arValidItems))
					{
						self::$arErrors[] = array('id' => 'ITEMS', 'text' => GetMessage('BT_CAT_SET_ERR_EMPTY_VALID_ITEMS'));
					}
					else
					{
						$arFields['ITEMS'] = $arValidItems;
						$boolItems = true;
					}
					unset($arValidItems);
					if (100 < $dblDiscountPercent)
						self::$arErrors[] = array('id' => 'DISCOUNT_PERCENT', 'text' => GetMessage('BT_CAT_PRODUCT_SET_ERR_ALL_DISCOUNT_PERCENT_IS_BAD'));
				}
			}
			if (empty(self::$arErrors))
			{
				$arProductList = array_keys($arProductInSet);
				if (!self::$disableCheckProduct)
				{
					if ($arFields['TYPE'] == self::TYPE_GROUP)
					{
						$checkProductList = $arProductInSet;
						if ($arFields['ITEM_ID'] > 0)
							unset($checkProductList[$arFields['ITEM_ID']]);
						$checkProductList = array_keys($checkProductList);
					}
					else
					{
						$checkProductList = $arProductList;
					}
					if (!CCatalogProduct::CheckProducts($checkProductList))
					{
						self::$arErrors[] = array('id' => 'ITEMS', 'text' => GetMessage('BT_CAT_PRODUCT_SET_ERR_ITEMS_IS_NOT_PRODUCT'));
					}
					unset($checkProductList);
				}
				if (empty(self::$arErrors) && self::TYPE_SET == $arFields['TYPE'])
				{
					if (CCatalogProductSet::isProductHaveSet($arProductList, self::TYPE_SET))
					{
						self::$arErrors[] = array('id' => 'ITEMS', 'text' => GetMessage('BT_CAT_PRODUCT_SET_ERR_ITEMS_IS_SET'));
					}
					$existSKU = array_filter(CCatalogSKU::getExistOffers($arProductList));
					if (!empty($existSKU))
					{
						self::$arErrors[] = array('id' => 'ITEMS', 'text' => GetMessage('BT_CAT_PRODUCT_SET_ERR_ITEMS_IS_SKU'));
					}
					unset($existSKU);
				}
			}
		}

		if (empty(self::$arErrors))
		{
			if ($boolItems)
				self::setItemFieldsForUpdate($arFields, $arCurrent);
		}

		return empty(self::$arErrors);
	}

	protected function getSetID($intID)
	{
		return false;
	}

	protected function getEmptySet($intSetType)
	{
		if (self::TYPE_SET == $intSetType)
		{
			return array(
				'TYPE' => self::TYPE_SET,
				'SET_ID' => 0,
				'ITEM_ID' => 0,
				'ACTIVE' => '',
				'QUANTITY' => 0,
				'MEASURE' => false,
				'DISCOUNT_PERCENT' => false,
				'SORT' => 0,
				'ITEMS' => array()
			);
		}
		else
		{
			return array(
				'TYPE' => self::TYPE_GROUP,
				'SET_ID' => 0,
				'ITEM_ID' => 0,
				'ACTIVE' => '',
				'QUANTITY' => 0,
				'SORT' => 0,
				'ITEMS' => array()
			);
		}
	}

	protected function deleteFromSet($intID, $arEx)
	{
		return false;
	}

	protected function setItemFieldsForAdd(&$arFields)
	{
		$arClear = array(
			'ID', 'DATE_CREATE', 'TIMESTAMP_X'
		);
		foreach ($arFields['ITEMS'] as &$arOneItem)
		{
			foreach ($arClear as &$strKey)
			{
				if (array_key_exists($strKey, $arOneItem))
					unset($arOneItem[$strKey]);
			}
			unset($strKey);

			$arOneItem['TYPE'] = $arFields['TYPE'];
			$arOneItem['OWNER_ID'] = $arFields['ITEM_ID'];
			$arOneItem['ACTIVE'] = $arFields['ACTIVE'];
			$arOneItem['CREATED_BY'] = $arFields['CREATED_BY'];
			$arOneItem['~DATE_CREATE'] = $arFields['~DATE_CREATE'];
			$arOneItem['MODIFIED_BY'] = $arFields['MODIFIED_BY'];
			$arOneItem['~TIMESTAMP_X'] = $arFields['~TIMESTAMP_X'];
		}
		unset($arOneItem);
	}

	protected function setItemFieldsForUpdate(&$arFields, $arCurrent)
	{
		$strActive = (isset($arFields['ACTIVE']) ? $arFields['ACTIVE'] : $arCurrent['ACTIVE']);

		if (self::TYPE_GROUP == $arCurrent['TYPE'])
		{
			$arClear = array(
				'CREATED_BY', 'TYPE', 'SET_ID', 'OWNER_ID', 'ITEM_ID', 'MEASURE', 'DISCOUNT_PERCENT'
			);
		}
		else
		{
			$arClear = array(
				'CREATED_BY', 'TYPE', 'SET_ID', 'OWNER_ID', 'ITEM_ID'
			);
		}
		foreach ($arFields['ITEMS'] as &$arOneItem)
		{
			if (array_key_exists('DATE_CREATE', $arOneItem))
				unset($arOneItem['DATE_CREATE']);

			$arOneItem['ACTIVE'] = $strActive;
			$arOneItem['MODIFIED_BY'] = $arFields['MODIFIED_BY'];
			$arOneItem['~TIMESTAMP_X'] = $arFields['~TIMESTAMP_X'];

			if (array_key_exists('ID', $arOneItem))
			{
				foreach ($arClear as &$strKey)
				{
					if (array_key_exists($strKey, $arOneItem))
						unset($arOneItem[$strKey]);
				}
				unset($strKey);
			}
			else
			{
				$arOneItem['TYPE'] = $arCurrent['TYPE'];
				$arOneItem['SET_ID'] = $arCurrent['ID'];
				$arOneItem['OWNER_ID'] = $arCurrent['ITEM_ID'];

				$arOneItem['CREATED_BY'] = $arFields['MODIFIED_BY'];
				$arOneItem['~DATE_CREATE'] = $arFields['~TIMESTAMP_X'];
			}
		}
		unset($arOneItem);
	}

	protected function clearFieldsForUpdate(&$arFields, $intSetType)
	{
		$intSetType = (int)$intSetType;
		$arClear = array(
			'TYPE', 'SET_ID', 'ITEM_ID', 'OWNER_ID', 'CREATED_BY', 'MEASURE', 'DISCOUNT_PERCENT'
		);
		if ($intSetType == self::TYPE_SET)
			$arClear[] = 'QUANTITY';
		foreach ($arClear as &$strKey)
		{
			if (array_key_exists($strKey, $arFields))
				unset($arFields[$strKey]);
		}
		unset($strKey);
	}

	protected function getEmptyFields()
	{
		return array(
			'TYPE' => 0,
			'SET_ID' => 0,
			'ACTIVE' => 'Y',
			'OWNER_ID' => 0,
			'ITEM_ID' => 0,
			'QUANTITY' => false,
			'MEASURE' => false,
			'DISCOUNT_PERCENT' => false,
			'SORT' => 100,
			'XML_ID' => false,
			'ITEMS' => array()
		);
	}

	protected function getEmptyItemFields()
	{
		return array(
			'TYPE' => 0,
			'SET_ID' => 0,
			'ACTIVE' => 'Y',
			'OWNER_ID' => 0,
			'ITEM_ID' => 0,
			'QUANTITY' => false,
			'MEASURE' => false,
			'DISCOUNT_PERCENT' => false,
			'SORT' => 100,
			'XML_ID' => false,
		);
	}

	protected function searchItem($intItemID, &$arItems)
	{
		$mxResult = false;
		foreach ($arItems as &$arOneItem)
		{
			if ($intItemID === $arOneItem['ITEM_ID'])
			{
				$mxResult = $arOneItem['ID'];
				break;
			}
		}
		unset($arOneItem);
		return $mxResult;
	}

	protected function calculateSetParams($productID, $items)
	{
		return false;
	}

	protected function fillSetItemsParams(&$items)
	{
		$productIterator = CCatalogProduct::GetList(
			array(),
			array('=ID' => array_keys($items)),
			false,
			false,
			array('ID', 'QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO', 'WEIGHT')
		);
		while ($product = $productIterator->Fetch())
		{
			$product['ID'] = (int)$product['ID'];
			if (isset($items[$product['ID']]))
			{
				$items[$product['ID']] = array_merge($items[$product['ID']], $product);
			}
		}
	}

	protected function createSetItemsParamsFromAdd($items)
	{
		$result = array();
		foreach ($items as &$oneItem)
		{
			$oneItem['ITEM_ID'] = (int)$oneItem['ITEM_ID'];
			$result[$oneItem['ITEM_ID']] = array(
				'QUANTITY_IN_SET' => $oneItem['QUANTITY']
			);
		}
		unset($oneItem);
		return $result;
	}

	protected function createSetItemsParamsFromUpdate($setID, $getProductID = false)
	{
		return array();
	}

	protected static function isTracedItem($item)
	{
		return (
			isset($item['QUANTITY_TRACE']) && $item['QUANTITY_TRACE'] === 'Y'
			&& isset($item['CAN_BUY_ZERO']) && $item['CAN_BUY_ZERO'] === 'N'
		);
	}
}
?>