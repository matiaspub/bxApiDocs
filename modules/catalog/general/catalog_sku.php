<?
use Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Type\Collection,
	Bitrix\Iblock,
	Bitrix\Catalog;

Loc::loadMessages(__FILE__);

class CAllCatalogSku
{
	const TYPE_CATALOG = 'D';
	const TYPE_PRODUCT = 'P';
	const TYPE_OFFERS = 'O';
	const TYPE_FULL = 'X';

	static protected $arOfferCache = array();
	static protected $arProductCache = array();
	static protected $arPropertyCache = array();
	static protected $arIBlockCache = array();
	static protected $parentCache = array();

	public static function GetCatalogTypes($boolFull = false)
	{
		$boolFull = ($boolFull === true);
		if ($boolFull)
		{
			return array(
				self::TYPE_CATALOG => Loc::getMessage('BT_CAT_SKU_TYPE_CATALOG'),
				self::TYPE_PRODUCT => Loc::getMessage('BT_CAT_SKU_TYPE_PRODUCT'),
				self::TYPE_OFFERS => Loc::getMessage('BT_CAT_SKU_TYPE_OFFERS'),
				self::TYPE_FULL => Loc::getMessage('BT_CAT_SKU_TYPE_FULL')
			);
		}
		return array(
			self::TYPE_CATALOG,
			self::TYPE_PRODUCT,
			self::TYPE_OFFERS,
			self::TYPE_FULL
		);
	}

	public static function GetProductInfo($intOfferID, $intIBlockID = 0)
	{
		$intOfferID = (int)$intOfferID;
		if ($intOfferID <= 0)
			return false;

		if (!isset(self::$parentCache[$intOfferID]))
		{
			self::$parentCache[$intOfferID] = false;
			$intIBlockID = (int)$intIBlockID;
			if ($intIBlockID <= 0)
				$intIBlockID = (int)CIBlockElement::GetIBlockByID($intOfferID);

			if ($intIBlockID <= 0)
				return self::$parentCache[$intOfferID];

			if (!isset(self::$arOfferCache[$intIBlockID]))
				$skuInfo = static::GetInfoByOfferIBlock($intIBlockID);
			else
				$skuInfo = self::$arOfferCache[$intIBlockID];

			if (empty($skuInfo) || empty($skuInfo['SKU_PROPERTY_ID']))
				return self::$parentCache[$intOfferID];

			$conn = Application::getConnection();
			$helper = $conn->getSqlHelper();

			if ($skuInfo['VERSION'] == 2)
			{
				$productField = $helper->quote('PROPERTY_'.$skuInfo['SKU_PROPERTY_ID']);
				$sqlQuery = 'select '.$productField.' as ID from '.$helper->quote('b_iblock_element_prop_s'.$skuInfo['IBLOCK_ID']).
					' where '.$helper->quote('IBLOCK_ELEMENT_ID').' = '.$intOfferID;
			}
			else
			{
				$productField = $helper->quote('VALUE_NUM');
				$sqlQuery = 'select '.$productField.' as ID from '.$helper->quote('b_iblock_element_property').
					' where '.$helper->quote('IBLOCK_PROPERTY_ID').' = '.$skuInfo['SKU_PROPERTY_ID'].
					' and '.$helper->quote('IBLOCK_ELEMENT_ID').' = '.$intOfferID;
			}
			unset($productField);
			$parentIterator = $conn->query($sqlQuery);
			if ($parent = $parentIterator->fetch())
			{
				$parent['ID'] = (int)$parent['ID'];
				if ($parent['ID'] > 0)
				{
					self::$parentCache[$intOfferID] = array(
						'ID' => $parent['ID'],
						'IBLOCK_ID' => $skuInfo['PRODUCT_IBLOCK_ID'],
						'OFFER_IBLOCK_ID' => $intIBlockID,
						'SKU_PROPERTY_ID' => $skuInfo['SKU_PROPERTY_ID']
					);
				}
			}
			unset($parent, $parentIterator, $sqlQuery, $helper, $conn, $skuInfo);
		}
		return self::$parentCache[$intOfferID];
	}

	public static function GetInfoByOfferIBlock($intIBlockID)
	{
		$intIBlockID = (int)$intIBlockID;
		if ($intIBlockID <= 0)
			return false;
		if (!isset(self::$arOfferCache[$intIBlockID]))
		{
			self::$arOfferCache[$intIBlockID] = false;
			$iblockIterator = Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
				'filter' => array('=IBLOCK_ID' => $intIBlockID, '!=PRODUCT_IBLOCK_ID' => 0)
			));
			$arResult = $iblockIterator->fetch();
			if (!empty($arResult))
			{
				$arResult['IBLOCK_ID'] = (int)$arResult['IBLOCK_ID'];
				$arResult['PRODUCT_IBLOCK_ID'] = (int)$arResult['PRODUCT_IBLOCK_ID'];
				$arResult['SKU_PROPERTY_ID'] = (int)$arResult['SKU_PROPERTY_ID'];
				$arResult['VERSION'] = (int)$arResult['VERSION'];
				self::$arOfferCache[$arResult['IBLOCK_ID']] = $arResult;
				self::$arOfferCache[$arResult['PRODUCT_IBLOCK_ID']] = false;
				self::$arProductCache[$arResult['PRODUCT_IBLOCK_ID']] = $arResult;
				self::$arProductCache[$arResult['IBLOCK_ID']] = false;
				self::$arPropertyCache[$arResult['SKU_PROPERTY_ID']] = $arResult;
			}
			unset($arResult, $iblockIterator);
		}
		return self::$arOfferCache[$intIBlockID];
	}

	public static function GetInfoByProductIBlock($intIBlockID)
	{
		$intIBlockID = (int)$intIBlockID;
		if ($intIBlockID <= 0)
			return false;
		if (!isset(self::$arProductCache[$intIBlockID]))
		{
			self::$arProductCache[$intIBlockID] = false;
			$iblockIterator = Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
				'filter' => array('=PRODUCT_IBLOCK_ID' => $intIBlockID)
			));
			$arResult = $iblockIterator->fetch();
			if (!empty($arResult))
			{
				$arResult['IBLOCK_ID'] = (int)$arResult['IBLOCK_ID'];
				$arResult['PRODUCT_IBLOCK_ID'] = (int)$arResult['PRODUCT_IBLOCK_ID'];
				$arResult['SKU_PROPERTY_ID'] = (int)$arResult['SKU_PROPERTY_ID'];
				$arResult['VERSION'] = (int)$arResult['VERSION'];
				self::$arProductCache[$arResult['PRODUCT_IBLOCK_ID']] = $arResult;
				self::$arProductCache[$arResult['IBLOCK_ID']] = false;
				self::$arOfferCache[$arResult['IBLOCK_ID']] = $arResult;
				self::$arOfferCache[$arResult['PRODUCT_IBLOCK_ID']] = false;
				self::$arPropertyCache[$arResult['SKU_PROPERTY_ID']] = $arResult;
			}
			unset($arResult, $iblockIterator);
		}
		return self::$arProductCache[$intIBlockID];
	}

	public static function GetInfoByLinkProperty($intPropertyID)
	{
		$intPropertyID = (int)$intPropertyID;
		if ($intPropertyID <= 0)
			return false;
		if (!isset(self::$arPropertyCache[$intPropertyID]))
		{
			self::$arPropertyCache[$intPropertyID] = false;
			$iblockIterator = Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
				'filter' => array('=SKU_PROPERTY_ID' => $intPropertyID)
			));
			$arResult = $iblockIterator->fetch();
			if (!empty($arResult))
			{
				$arResult['IBLOCK_ID'] = (int)$arResult['IBLOCK_ID'];
				$arResult['PRODUCT_IBLOCK_ID'] = (int)$arResult['PRODUCT_IBLOCK_ID'];
				$arResult['SKU_PROPERTY_ID'] = (int)$arResult['SKU_PROPERTY_ID'];
				$arResult['VERSION'] = (int)$arResult['VERSION'];
				self::$arPropertyCache[$arResult['SKU_PROPERTY_ID']] = $arResult;
				self::$arProductCache[$arResult['PRODUCT_IBLOCK_ID']] = $arResult;
				self::$arProductCache[$arResult['IBLOCK_ID']] = false;
				self::$arOfferCache[$arResult['IBLOCK_ID']] = $arResult;
				self::$arOfferCache[$arResult['PRODUCT_IBLOCK_ID']] = false;
			}
			unset($arResult, $iblockIterator);
		}
		return self::$arPropertyCache[$intPropertyID];
	}

	public static function GetInfoByIBlock($intIBlockID)
	{
		$intIBlockID = (int)$intIBlockID;
		if ($intIBlockID <= 0)
			return false;

		if (!isset(self::$arIBlockCache[$intIBlockID]))
		{
			$result = false;
			$arIBlock = array();
			$arProductIBlock = array();
			$boolExists = false;
			$boolIBlock = false;
			$boolProductIBlock = false;
			$catalogIterator = Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VAT_ID', 'YANDEX_EXPORT', 'SUBSCRIPTION'),
				'filter' => array(array(
					'LOGIC' => 'OR',
					'=IBLOCK_ID' => $intIBlockID,
					'=PRODUCT_IBLOCK_ID' => $intIBlockID
				))
			));
			while ($catalog = $catalogIterator->fetch())
			{
				$catalog['IBLOCK_ID'] = (int)$catalog['IBLOCK_ID'];
				$catalog['PRODUCT_IBLOCK_ID'] = (int)$catalog['PRODUCT_IBLOCK_ID'];
				$catalog['SKU_PROPERTY_ID'] = (int)$catalog['SKU_PROPERTY_ID'];
				$catalog['VAT_ID'] = (int)$catalog['VAT_ID'];
				$boolExists = true;
				if ($catalog['IBLOCK_ID'] == $intIBlockID)
				{
					$boolIBlock = true;
					$arIBlock = $catalog;
				}
				elseif ($catalog['PRODUCT_IBLOCK_ID'] == $intIBlockID)
				{
					$boolProductIBlock = true;
					$arProductIBlock = $catalog;
				}
			}
			unset($catalog, $catalogIterator);
			if ($boolExists)
			{
				if ($boolProductIBlock && $boolIBlock)
				{
					$result = $arProductIBlock;
					$result['VAT_ID'] = $arIBlock['VAT_ID'];
					$result['YANDEX_EXPORT'] = $arIBlock['YANDEX_EXPORT'];
					$result['SUBSCRIPTION'] = $arIBlock['SUBSCRIPTION'];
					$result['CATALOG_TYPE'] = self::TYPE_FULL;
				}
				elseif ($boolIBlock)
				{
					$result = $arIBlock;
					$result['CATALOG_TYPE'] = (0 < $result['PRODUCT_IBLOCK_ID'] ? self::TYPE_OFFERS : self::TYPE_CATALOG);
				}
				else
				{
					$result = $arProductIBlock;
					unset($result['VAT_ID'], $result['YANDEX_EXPORT'], $result['SUBSCRIPTION']);
					$result['CATALOG_TYPE'] = self::TYPE_PRODUCT;
				}
				$result['CATALOG'] = ($boolIBlock ? 'Y' : 'N');
			}
			self::$arIBlockCache[$intIBlockID] = $result;
			unset($boolProductIBlock, $boolIBlock, $boolExists);
			unset($arProductIBlock, $arIBlock);
			unset($result);
		}
		return self::$arIBlockCache[$intIBlockID];
	}

	/*
	* @deprecated deprecated since catalog 15.0.1
	* @see CCatalogSKU::getExistOffers()
	*/
	public static function IsExistOffers($intProductID, $intIBlockID = 0)
	{
		$result = static::getExistOffers($intProductID, $intIBlockID);
		return !empty($result[$intProductID]);
	}

	public static function getExistOffers($productID, $iblockID = 0)
	{
		$iblockID = (int)$iblockID;
		if (!is_array($productID))
			$productID = array($productID);
		Collection::normalizeArrayValuesByInt($productID);
		if (empty($productID))
			return false;
		$result = array_fill_keys($productID, false);
		$iblockProduct = array();
		$iblockSku = array();
		if ($iblockID == 0)
		{
			$iblockList = array();
			$elementIterator = Iblock\ElementTable::getList(array(
				'select' => array('ID', 'IBLOCK_ID'),
				'filter' => array('@ID' => $productID)
			));
			while ($element = $elementIterator->fetch())
			{
				$element['ID'] = (int)$element['ID'];
				$element['IBLOCK_ID'] = (int)$element['IBLOCK_ID'];
				if (!isset($iblockList[$element['IBLOCK_ID']]))
					$iblockList[$element['IBLOCK_ID']] = array();
				$iblockList[$element['IBLOCK_ID']][] = $element['ID'];
			}
			unset($element, $elementIterator);
			if (!empty($iblockList))
			{
				$iblockListIds = array_keys($iblockList);
				foreach ($iblockListIds as &$oneIblock)
				{
					if (!empty(self::$arOfferCache[$oneIblock]))
					{
						unset($iblockList[$oneIblock]);
						continue;
					}
					if (isset(self::$arProductCache[$oneIblock]))
					{
						if (!empty(self::$arProductCache[$oneIblock]))
						{
							$iblockSku[$oneIblock] = self::$arProductCache[$oneIblock];
							$iblockProduct[$oneIblock] = $iblockList[$oneIblock];
						}
						unset($iblockList[$oneIblock]);
					}
				}
				unset($oneIblock, $iblockListIds);
				if (!empty($iblockList))
				{
					$iblockIterator = Catalog\CatalogIblockTable::getList(array(
						'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
						'filter' => array('@PRODUCT_IBLOCK_ID' => array_keys($iblockList))
					));
					while ($iblock = $iblockIterator->fetch())
					{
						$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
						$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
						$iblock['SKU_PROPERTY_ID'] = (int)$iblock['SKU_PROPERTY_ID'];
						$iblock['VERSION'] = (int)$iblock['VERSION'];
						$iblockSku[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
						self::$arProductCache[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
						self::$arProductCache[$iblock['IBLOCK_ID']] = false;
						self::$arOfferCache[$iblock['IBLOCK_ID']] = $iblock;
						self::$arOfferCache[$iblock['PRODUCT_IBLOCK_ID']] = false;
						self::$arPropertyCache[$iblock['SKU_PROPERTY_ID']] = $iblock;
						$iblockProduct[$iblock['PRODUCT_IBLOCK_ID']] = $iblockList[$iblock['PRODUCT_IBLOCK_ID']];
					}
					unset($iblock, $iblockIterator);
				}
			}
			unset($iblockList);
		}
		else
		{
			if (empty(self::$arOfferCache[$iblockID]))
			{
				if (isset(self::$arProductCache[$iblockID]))
				{
					if (!empty(self::$arProductCache[$iblockID]))
					{
						$iblockSku[$iblockID] = self::$arProductCache[$iblockID];
						$iblockProduct[$iblockID] = $productID;
					}
				}
				else
				{
					$iblockIterator = Catalog\CatalogIblockTable::getList(array(
						'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
						'filter' => array('=PRODUCT_IBLOCK_ID' => $iblockID)
					));
					if ($iblock = $iblockIterator->fetch())
					{
						$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
						$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
						$iblock['SKU_PROPERTY_ID'] = (int)$iblock['SKU_PROPERTY_ID'];
						$iblock['VERSION'] = (int)$iblock['VERSION'];
						$iblockSku[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
						self::$arProductCache[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
						self::$arProductCache[$iblock['IBLOCK_ID']] = false;
						self::$arOfferCache[$iblock['IBLOCK_ID']] = $iblock;
						self::$arOfferCache[$iblock['PRODUCT_IBLOCK_ID']] = false;
						self::$arPropertyCache[$iblock['SKU_PROPERTY_ID']] = $iblock;
						$iblockProduct[$iblockID] = $productID;
					}
					unset($iblock, $iblockIterator);
				}
			}
		}
		if (empty($iblockProduct))
			return $result;

		$conn = Application::getConnection();
		$helper = $conn->getSqlHelper();
		$propertyIdField = $helper->quote('IBLOCK_PROPERTY_ID');

		foreach ($iblockProduct as $iblockID => $iblockProductID)
		{
			$sku = $iblockSku[$iblockID];
			if ($sku['VERSION'] == 2)
			{
				$productField = $helper->quote('PROPERTY_'.$sku['SKU_PROPERTY_ID']);
				$sqlQuery = 'select '.$productField.' as PRODUCT_ID, COUNT(*) as CNT from '.$helper->quote('b_iblock_element_prop_s'.$sku['IBLOCK_ID']).
				' where '.$productField.' IN ('.implode(',', $iblockProductID).')'.
				' group by '.$productField;
			}
			else
			{
				$productField = $helper->quote('VALUE_NUM');
				$sqlQuery = 'select '.$productField.' as PRODUCT_ID, COUNT(*) as CNT from '.$helper->quote('b_iblock_element_property').
				' where '.$propertyIdField.' = '.$sku['SKU_PROPERTY_ID'].
				' and '.$productField.' IN ('.implode(',', $iblockProductID).')'.
				' group by '.$productField;
			}
			unset($productField);
			$productIterator = $conn->query($sqlQuery);
			while ($product = $productIterator->fetch())
			{
				$product['CNT'] = (int)$product['CNT'];
				if ($product['CNT'] <= 0)
					continue;

				$product['PRODUCT_ID'] = (int)$product['PRODUCT_ID'];
				$result[$product['PRODUCT_ID']] = true;
			}
			unset($product, $productIterator);
		}
		unset($sku, $iblockProductID, $iblockID, $iblockProduct);
		unset($propertyIdField, $helper, $conn);

		return $result;
	}

	public static function getOffersList($productID, $iblockID = 0, $skuFilter = array(), $fields = array(), $propertyFilter = array())
	{
		static $propertyCache = array();

		$iblockID = (int)$iblockID;
		if (!is_array($productID))
			$productID = array($productID);
		Collection::normalizeArrayValuesByInt($productID);
		if (empty($productID))
			return false;
		if (!is_array($skuFilter))
			$skuFilter = array();
		if (!is_array($fields))
			$fields = array($fields);
		$fields = array_merge($fields, array('ID', 'IBLOCK_ID'));
		if (!is_array($propertyFilter))
			$propertyFilter = array();

		$iblockProduct = array();
		$iblockSku = array();
		$offersIblock = array();
		if ($iblockID == 0)
		{
			$iblockList = array();
			$elementIterator = Iblock\ElementTable::getList(array(
				'select' => array('ID', 'IBLOCK_ID'),
				'filter' => array('@ID' => $productID)
			));
			while ($element = $elementIterator->fetch())
			{
				$element['ID'] = (int)$element['ID'];
				$element['IBLOCK_ID'] = (int)$element['IBLOCK_ID'];
				if (!isset($iblockList[$element['IBLOCK_ID']]))
					$iblockList[$element['IBLOCK_ID']] = array();
				$iblockList[$element['IBLOCK_ID']][] = $element['ID'];
			}
			unset($element, $elementIterator);
			if (!empty($iblockList))
			{
				$iblockListIds = array_keys($iblockList);
				foreach ($iblockListIds as &$oneIblock)
				{
					if (isset(self::$arProductCache[$oneIblock]))
					{
						if (!empty(self::$arProductCache[$oneIblock]))
						{
							$iblockSku[$oneIblock] = self::$arProductCache[$oneIblock];
							$offersIblock[] = self::$arProductCache[$oneIblock]['IBLOCK_ID'];
							$iblockProduct[$oneIblock] = $iblockList[$oneIblock];
						}
						unset($iblockList[$oneIblock]);
					}
				}
				unset($oneIblock, $iblockListIds);
				if (!empty($iblockList))
				{
					$iblockIterator = Catalog\CatalogIblockTable::getList(array(
						'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
						'filter' => array('@PRODUCT_IBLOCK_ID' => array_keys($iblockList))
					));
					while ($iblock = $iblockIterator->fetch())
					{
						$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
						$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
						$iblock['SKU_PROPERTY_ID'] = (int)$iblock['SKU_PROPERTY_ID'];
						$iblock['VERSION'] = (int)$iblock['VERSION'];
						$iblockSku[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
						self::$arProductCache[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
						self::$arProductCache[$iblock['IBLOCK_ID']] = false;
						self::$arOfferCache[$iblock['IBLOCK_ID']] = $iblock;
						self::$arOfferCache[$iblock['PRODUCT_IBLOCK_ID']] = false;
						self::$arPropertyCache[$iblock['SKU_PROPERTY_ID']] = $iblock;
						$offersIblock[] = $iblock['IBLOCK_ID'];
						$iblockProduct[$iblock['PRODUCT_IBLOCK_ID']] = $iblockList[$iblock['PRODUCT_IBLOCK_ID']];
					}
					unset($iblock, $iblockIterator);
				}
			}
			unset($iblockList);
		}
		else
		{
			if (empty(self::$arOfferCache[$iblockID]))
			{
				if (isset(self::$arProductCache[$iblockID]))
				{
					if (!empty(self::$arProductCache[$iblockID]))
					{
						$iblockSku[$iblockID] = self::$arProductCache[$iblockID];
						$offersIblock[] = self::$arProductCache[$iblockID]['IBLOCK_ID'];
						$iblockProduct[$iblockID] = $productID;
					}
				}
				else
				{
					$iblockIterator = Catalog\CatalogIblockTable::getList(array(
						'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
						'filter' => array('=PRODUCT_IBLOCK_ID' => $iblockID)
					));
					if ($iblock = $iblockIterator->fetch())
					{
						$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
						$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
						$iblock['SKU_PROPERTY_ID'] = (int)$iblock['SKU_PROPERTY_ID'];
						$iblock['VERSION'] = (int)$iblock['VERSION'];
						$iblockSku[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
						self::$arProductCache[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
						self::$arProductCache[$iblock['IBLOCK_ID']] = false;
						self::$arOfferCache[$iblock['IBLOCK_ID']] = $iblock;
						self::$arOfferCache[$iblock['PRODUCT_IBLOCK_ID']] = false;
						self::$arPropertyCache[$iblock['SKU_PROPERTY_ID']] = $iblock;
						$offersIblock[] = $iblock['IBLOCK_ID'];
						$iblockProduct[$iblockID] = $productID;
					}
					unset($iblock, $iblockIterator);
				}
			}
		}
		if (empty($iblockProduct))
			return array();

		$propertyFilter = array_filter($propertyFilter);
		if (isset($propertyFilter['ID']))
		{
			$propertyFilter['ID'] = array_filter($propertyFilter['ID']);
			if (empty($propertyFilter['ID']))
				unset($propertyFilter['ID']);
		}
		if (isset($propertyFilter['CODE']))
		{
			$propertyFilter['CODE'] = array_filter($propertyFilter['CODE']);
			if (empty($propertyFilter['CODE']))
				unset($propertyFilter['CODE']);
		}

		$iblockProperties = array();
		if (!empty($propertyFilter['ID']) || !empty($propertyFilter['CODE']))
		{
			$propertyIblock = array('@IBLOCK_ID' => $offersIblock);
			if (!empty($propertyFilter['ID']))
			{
				sort($propertyFilter['ID']);
				$propertyKey = md5(implode('|', $propertyFilter['ID']));
				$propertyIblock['@ID'] = $propertyFilter['ID'];
			}
			else
			{
				sort($propertyFilter['CODE']);
				$propertyKey = md5(implode('|', $propertyFilter['CODE']));
				$propertyIblock['@CODE'] = $propertyFilter['CODE'];
			}
			if (!isset($propertyCache[$propertyKey]))
			{
				$propertyCache[$propertyKey] = array_fill_keys($offersIblock, array());
				$propertyIterator = Iblock\PropertyTable::getList(array(
					'select' => array('ID', 'IBLOCK_ID'),
					'filter' => $propertyIblock
				));
				while ($property = $propertyIterator->fetch())
				{
					$property['IBLOCK_ID'] = (int)$property['IBLOCK_ID'];
					$propertyCache[$propertyKey][$property['IBLOCK_ID']][] = (int)$property['ID'];
				}
				unset($property, $propertyIterator, $propertyIblock);
			}
			$iblockProperties = $propertyCache[$propertyKey];
		}
		unset($offersIblock);

		$result = array_fill_keys($productID, array());

		foreach ($iblockProduct as $iblockID => $productList)
		{
			$skuProperty = 'PROPERTY_'.$iblockSku[$iblockID]['SKU_PROPERTY_ID'];
			$iblockFilter = $skuFilter;
			$iblockFilter['IBLOCK_ID'] = $iblockSku[$iblockID]['IBLOCK_ID'];
			$iblockFilter['='.$skuProperty] = $productList;
			$iblockFields = $fields;
			$iblockFields[] = $skuProperty;
			$skuProperty .= '_VALUE';
			$skuPropertyId = $skuProperty.'_ID';
			$offersLinks = array();

			$offersIterator = CIBlockElement::GetList(
				array('ID' => 'ASC'),
				$iblockFilter,
				false,
				false,
				$iblockFields
			);
			while ($offer = $offersIterator->Fetch())
			{
				$offerProduct = (int)$offer[$skuProperty];
				unset($offer[$skuProperty]);
				if (isset($offer[$skuPropertyId]))
					unset($offer[$skuPropertyId]);
				if (!isset($result[$offerProduct]))
					continue;
				$offer['ID'] = (int)$offer['ID'];
				$offer['IBLOCK_ID'] = (int)$offer['IBLOCK_ID'];
				$offer['PROPERTIES'] = array();
				$result[$offerProduct][$offer['ID']] = $offer;
				$offersLinks[$offer['ID']] = &$result[$offerProduct][$offer['ID']];
			}
			unset($offerProduct, $offer, $offersIterator, $skuProperty);
			if (!empty($iblockProperties[$iblockSku[$iblockID]['IBLOCK_ID']]))
			{
				CIBlockElement::GetPropertyValuesArray(
					$offersLinks,
					$iblockSku[$iblockID]['IBLOCK_ID'],
					$iblockFilter,
					array('ID' => $iblockProperties[$iblockSku[$iblockID]['IBLOCK_ID']])
				);
			}
			unset($offersLinks);
		}
		unset($productList, $iblockID, $iblockProduct);

		return array_filter($result);
	}

	public static function getProductList($offerID, $iblockID = 0)
	{
		$iblockID = (int)$iblockID;
		if (!is_array($offerID))
			$offerID = array($offerID);
		Collection::normalizeArrayValuesByInt($offerID);
		if (empty($offerID))
			return false;

		$iblockSku = array();
		$iblockOffers = array();
		if ($iblockID == 0)
		{
			$iblockList = array();
			$elementIterator = Iblock\ElementTable::getList(array(
				'select' => array('ID', 'IBLOCK_ID'),
				'filter' => array('@ID' => $offerID)
			));
			while ($element = $elementIterator->fetch())
			{
				$element['ID'] = (int)$element['ID'];
				$element['IBLOCK_ID'] = (int)$element['IBLOCK_ID'];
				if (!isset($iblockList[$element['IBLOCK_ID']]))
					$iblockList[$element['IBLOCK_ID']] = array();
				$iblockList[$element['IBLOCK_ID']][] = $element['ID'];
			}
			unset($element, $elementIterator);
			if (!empty($iblockList))
			{
				$iblockListIds = array_keys($iblockList);
				foreach ($iblockListIds as &$oneIblock)
				{
					if (!empty(self::$arProductCache[$oneIblock]))
					{
						unset($iblockList[$oneIblock]);
						continue;
					}
					if (isset(self::$arOfferCache[$oneIblock]))
					{
						if (!empty(self::$arOfferCache[$oneIblock]))
						{
							$iblockSku[$oneIblock] = self::$arOfferCache[$oneIblock];
							$iblockOffers[$oneIblock] = $iblockList[$oneIblock];
						}
						unset($iblockList[$oneIblock]);
					}
				}
				unset($oneIblock, $iblockListIds);
				if (!empty($iblockList))
				{
					$iblockIterator = Catalog\CatalogIblockTable::getList(array(
						'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
						'filter' => array('@IBLOCK_ID' => array_keys($iblockList), '!=PRODUCT_IBLOCK_ID' => 0)
					));
					while ($iblock = $iblockIterator->fetch())
					{
						$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
						$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
						$iblock['SKU_PROPERTY_ID'] = (int)$iblock['SKU_PROPERTY_ID'];
						$iblock['VERSION'] = (int)$iblock['VERSION'];
						$iblockSku[$iblock['IBLOCK_ID']] = $iblock;
						self::$arProductCache[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
						self::$arProductCache[$iblock['IBLOCK_ID']] = false;
						self::$arOfferCache[$iblock['IBLOCK_ID']] = $iblock;
						self::$arOfferCache[$iblock['PRODUCT_IBLOCK_ID']] = false;
						self::$arPropertyCache[$iblock['SKU_PROPERTY_ID']] = $iblock;
						$iblockOffers[$iblock['IBLOCK_ID']] = $iblockList[$iblock['IBLOCK_ID']];
					}
					unset($iblock, $iblockIterator);
				}
			}
			unset($iblockList);
		}
		else
		{
			if (empty(self::$arProductCache[$iblockID]))
			{
				if (isset(self::$arOfferCache[$iblockID]))
				{
					if (!empty(self::$arOfferCache[$iblockID]))
					{
						$iblockSku[$iblockID] = self::$arOfferCache[$iblockID];
						$iblockOffers[$iblockID] = $offerID;
					}
				}
				else
				{
					$iblockIterator = Catalog\CatalogIblockTable::getList(array(
						'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
						'filter' => array('=IBLOCK_ID' => $iblockID, '!=PRODUCT_IBLOCK_ID' => 0)
					));
					if ($iblock = $iblockIterator->fetch())
					{
						$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
						$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
						$iblock['SKU_PROPERTY_ID'] = (int)$iblock['SKU_PROPERTY_ID'];
						$iblock['VERSION'] = (int)$iblock['VERSION'];
						$iblockSku[$iblock['IBLOCK_ID']] = $iblock;
						self::$arProductCache[$iblock['PRODUCT_IBLOCK_ID']] = $iblock;
						self::$arProductCache[$iblock['IBLOCK_ID']] = false;
						self::$arOfferCache[$iblock['IBLOCK_ID']] = $iblock;
						self::$arOfferCache[$iblock['PRODUCT_IBLOCK_ID']] = false;
						self::$arPropertyCache[$iblock['SKU_PROPERTY_ID']] = $iblock;
						$iblockOffers[$iblockID] = $offerID;
					}
					unset($iblock, $iblockIterator);
				}
			}
		}
		if (empty($iblockOffers))
			return array();

		$result = array_fill_keys($offerID, array());

		$conn = Application::getConnection();
		$helper = $conn->getSqlHelper();
		$offerField = $helper->quote('IBLOCK_ELEMENT_ID');
		$propertyIdField = $helper->quote('IBLOCK_PROPERTY_ID');

		foreach ($iblockOffers as $iblockID => $offerList)
		{
			$sku = $iblockSku[$iblockID];
			if ($sku['VERSION'] == 2)
			{
				$productField = $helper->quote('PROPERTY_'.$sku['SKU_PROPERTY_ID']);
				$sqlQuery = 'select '.$productField.' as PRODUCT_ID, '.$offerField.' as ID from '.$helper->quote('b_iblock_element_prop_s'.$sku['IBLOCK_ID']).
					' where '.$offerField.' IN ('.implode(',', $offerList).')';
			}
			else
			{
				$productField = $helper->quote('VALUE_NUM');
				$sqlQuery = 'select '.$productField.' as PRODUCT_ID, '.$offerField.' as ID from '.$helper->quote('b_iblock_element_property').
					' where '.$propertyIdField.' = '.$sku['SKU_PROPERTY_ID'].
					' and '.$offerField.' IN ('.implode(',', $offerList).')';
			}
			unset($productField);
			$offersIterator = $conn->query($sqlQuery);
			while ($offer = $offersIterator->fetch())
			{
				$currentOffer = (int)$offer['ID'];
				$productID = (int)$offer['PRODUCT_ID'];
				if (!isset($result[$currentOffer]) || $productID <= 0)
					continue;

				$result[$currentOffer] = array(
					'ID' => $productID,
					'IBLOCK_ID' => $sku['PRODUCT_IBLOCK_ID'],
					'OFFER_IBLOCK_ID' => $iblockID,
					'SKU_PROPERTY_ID' => $sku['SKU_PROPERTY_ID']
				);
			}
			unset($sku);
		}
		unset($iblockID, $iblockOffers);
		unset($helper, $conn);

		return array_filter($result);
	}

	public static function ClearCache()
	{
		self::$arOfferCache = array();
		self::$arProductCache = array();
		self::$arPropertyCache = array();
		self::$arIBlockCache = array();
		self::$parentCache = array();
	}
}

class CCatalogSku extends CAllCatalogSku
{

}