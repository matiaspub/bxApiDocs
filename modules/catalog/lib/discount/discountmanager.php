<?php
namespace Bitrix\Catalog\Discount;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Currency;
use Bitrix\Catalog;
use Bitrix\Iblock;
use Bitrix\Sale;

Loc::loadMessages(__FILE__);

class DiscountManager
{
	protected static $discountCache = array();
	protected static $typeCache = array();
	protected static $editUrlTemplate = array();

	/**
	 * Return methods for prepare discount.
	 *
	 * @param Main\Event $event					Event data from discount manager.
	 * @return Main\EventResult
	 */
	public static function catalogDiscountManager(Main\Event $event)
	{
		$result = new Main\EventResult(
			Main\EventResult::SUCCESS,
			array(
				'prepareData' => array('\Bitrix\Catalog\Discount\DiscountManager', 'prepareData'),
				'getEditUrl' => array('\Bitrix\Catalog\Discount\DiscountManager', 'getEditUrl')
			),
			'catalog'
		);
		return $result;
	}

	/**
	 * Prepare discount before saving.
	 *
	 * @param array $discount				Discount data.
	 * @param array $params					Params.
	 * @return array|bool
	 */
	public static function prepareData($discount, $params = array())
	{
		if (empty($discount) || empty($discount['ID']))
			return false;

		$discountId = (int)$discount['ID'];
		if ($discountId <= 0)
			return false;
		if (!isset(self::$discountCache[$discountId]))
		{
			self::$discountCache[$discountId] = false;

			$loadData = self::loadFromDatabase($discountId, $discount);
			if (!empty($loadData))
			{
				if ($loadData['CURRENCY'] != $params['CURRENCY'])
					Catalog\DiscountTable::convertCurrency($loadData, $params['CURRENCY']);
				self::createSaleAction($loadData, $params);
				$loadData['EDIT_PAGE_URL'] = self::getEditUrl(array('ID' => $discountId, 'TYPE' => $loadData['TYPE']));
				self::$discountCache[$discountId] = $loadData;
			}
		}
		$result = self::$discountCache[$discountId];
		if (empty($result))
			return $result;
		if ($result['USE_COUPONS'] == 'Y')
		{
			if (isset($discount['COUPON']))
				$result['COUPON'] = $discount['COUPON'];
		}

		return $result;
	}

	/**
	 * Return url for edit discount.
	 *
	 * @param array $discount			Discount data.
	 * @return string
	 */
	public static function getEditUrl($discount)
	{
		if (empty(self::$editUrlTemplate))
		{
			self::$editUrlTemplate = array(
				Catalog\DiscountTable::TYPE_DISCOUNT => '/bitrix/admin/cat_discount_edit.php?lang='.LANGUAGE_ID.'&ID=',
				Catalog\DiscountTable::TYPE_DISCOUNT_SAVE => '/bitrix/admin/cat_discsave_edit.php?lang='.LANGUAGE_ID.'&ID='
			);
		}
		$result = '';
		if (empty($discount['ID']) || (int)$discount['ID'] <= 0)
			return $result;

		$id = (int)$discount['ID'];
		$type = -1;
		if (isset($discount['TYPE']))
			$type = (int)$discount['TYPE'];

		if ($type != Catalog\DiscountTable::TYPE_DISCOUNT && $type != Catalog\DiscountTable::TYPE_DISCOUNT_SAVE)
		{
			if (isset(self::$typeCache[$id]))
			{
				$type = self::$typeCache[$id];
			}
			else
			{
				$discountIterator = Catalog\DiscountTable::getList(array(
					'select' => array('ID', 'TYPE'),
					'filter' => array('=ID' => $id)
				));
				$data = $discountIterator->fetch();
				if (!empty($data))
				{
					$type = (int)$data['TYPE'];
					self::$typeCache[$id] = $type;
				}
				unset($data, $discountIterator);
			}
		}
		if (isset(self::$editUrlTemplate[$type]))
			$result = self::$editUrlTemplate[$type].$id;
		unset($type, $id);
		return $result;
	}

	/**
	 * Apply catalog discount by basket item.
	 *
	 * @param array &$product			Product data.
	 * @param array $discount			Discount data.
	 * @return void
	 */
	public static function applyDiscount(&$product, $discount)
	{
		if (empty($product) || !is_array($product))
			return;
		if (empty($discount) || empty($discount['TYPE']))
			return;
		if (isset($discount['CURRENCY']) && $discount['CURRENCY'] != $product['CURRENCY'])
			return;
		if (!isset($product['DISCOUNT_PRICE']))
			$product['DISCOUNT_PRICE'] = 0;
		$getPercentFromBasePrice = (isset($discount['USE_BASE_PRICE']) && $discount['USE_BASE_PRICE'] == 'Y');
		$basePrice = (float)(
			isset($product['BASE_PRICE'])
			? $product['BASE_PRICE']
			: $product['PRICE'] + $product['DISCOUNT_PRICE']
		);

		switch ($discount['TYPE'])
		{
			case Catalog\DiscountTable::VALUE_TYPE_PERCENT:
				$discountValue = roundEx(((
					$getPercentFromBasePrice
					? $basePrice
					: $product['PRICE']
					)*$discount['VALUE'])/100,
					CATALOG_VALUE_PRECISION
				);
				if (isset($discount['MAX_VALUE']) && $discount['MAX_VALUE'] > 0)
				{
					if ($discountValue > $discount['MAX_VALUE'])
						$discountValue = $discount['MAX_VALUE'];
				}
				$discountValue = roundEx($discountValue, CATALOG_VALUE_PRECISION);
				$product['PRICE'] -= $discountValue;
				$product['DISCOUNT_PRICE'] += $discountValue;
				if (!empty($product['DISCOUNT_RESULT']))
				{
					$product['DISCOUNT_RESULT']['BASKET'][0]['RESULT_VALUE'] = (string)abs($discountValue);
					$product['DISCOUNT_RESULT']['BASKET'][0]['RESULT_UNIT'] = $product['CURRENCY'];
				}
				unset($discountValue);
				break;
			case Catalog\DiscountTable::VALUE_TYPE_FIX:
				$discount['VALUE'] = roundEx($discount['VALUE'], CATALOG_VALUE_PRECISION);
				$product['PRICE'] -= $discount['VALUE'];
				$product['DISCOUNT_PRICE'] += $discount['VALUE'];
				break;
			case Catalog\DiscountTable::VALUE_TYPE_SALE:
				$discount['VALUE'] = roundEx($discount['VALUE'], CATALOG_VALUE_PRECISION);
				$product['DISCOUNT_PRICE'] += ($product['PRICE'] - $discount['VALUE']);
				$product['PRICE'] = $discount['VALUE'];
				break;
		}
	}

	/**
	 * Extend basket data.
	 *
	 * @param Main\Event $event			Event.
	 * @return Main\EventResult
	 */
	public static function extendOrderData(Main\Event $event)
	{
		$process = true;
		$resultData = array();
		$orderData = $event->getParameter('ORDER');
		$entityList = $event->getParameter('ENTITY');

		if (empty($orderData) || !is_array($orderData))
		{
			$process = false;
		}
		else
		{
			if (!isset($orderData['BASKET_ITEMS']) || !is_array($orderData['BASKET_ITEMS']))
				$process = false;
		}

		$entityData = false;
		$iblockData = false;
		if (
			$process
			&& !empty($orderData['BASKET_ITEMS'])
		)
		{
			$entityData = self::prepareEntity($entityList);
			if (empty($entityData))
				$process = false;
		}
		if ($process)
		{
			$productMap = array();
			$productList = array();
			$productData = array();

			$basket = array_filter($orderData['BASKET_ITEMS'], '\Bitrix\Catalog\Discount\DiscountManager::basketFilter');
			if (!empty($basket))
			{
				foreach ($basket as $basketCode => $basketItem)
				{
					$basketItem['PRODUCT_ID'] = (int)$basketItem['PRODUCT_ID'];
					$productList[] = $basketItem['PRODUCT_ID'];
					if (!isset($productMap[$basketItem['PRODUCT_ID']]))
						$productMap[$basketItem['PRODUCT_ID']] = array();
					$productMap[$basketItem['PRODUCT_ID']][] = &$basket[$basketCode];
				}
				unset($basketItem, $basketCode);

				$productData = array_fill_keys($productList, array());

				$iblockData = self::getProductIblocks($productList);
				self::fillProductPropertyList($entityData, $iblockData);
			}

			if (!empty($iblockData['iblockElement']))
				self::getProductData($productData, $entityData, $iblockData);

			if (!empty($iblockData['iblockElement']))
			{
				foreach ($productData as $product => $data)
				{
					if (empty($productMap[$product]))
						continue;
					foreach ($productMap[$product] as &$basketItem)
						$basketItem['CATALOG'] = $data;
					unset($basketItem);
				}
				unset($product, $data);

				$resultData['BASKET_ITEMS'] = $basket;
			}
			unset($basket, $productData, $productMap, $productList);
		}

		if ($process)
			$result = new Main\EventResult(Main\EventResult::SUCCESS, $resultData, 'catalog');
		else
			$result = new Main\EventResult(Main\EventResult::ERROR, null, 'catalog');
		unset($process, $resultData);

		return $result;
	}

	/**
	 * Filter for catalog basket items.
	 *
	 * @param array $basketItem			Basket item data.
	 * @return bool
	 */
	protected static function basketFilter($basketItem)
	{
		return (
			(
				(isset($basketItem['MODULE']) && $basketItem['MODULE'] == 'catalog')
				|| (isset($basketItem['MODULE_ID']) && $basketItem['MODULE_ID'] == 'catalog')
			)
			&& (isset($basketItem['PRODUCT_ID']) && (int)$basketItem['PRODUCT_ID'] > 0)
		);
	}

	/**
	 * Load discount data from db.
	 * @param int $id					Discount id.
	 * @param array $discount			Exist discount data.
	 * @return bool|array
	 */
	protected static function loadFromDatabase($id, $discount)
	{
		$select = array();
		if (!isset($discount['NAME']))
			$select['NAME'] = true;
		if (empty($discount['CONDITIONS']))
			$select['CONDITIONS_LIST'] = true;
		if (empty($discount['UNPACK']))
			$select['UNPACK'] = true;
		if (empty($discount['USE_COUPONS']))
			$discount['USE_COUPONS'] = (!empty($discount['COUPON']) ? 'Y' : 'N');
		if (!isset($discount['SORT']))
			$select['SORT'] = true;
		if (!isset($discount['PRIORITY']))
			$select['PRIORITY'] = true;
		if (!isset($discount['LAST_DISCOUNT']))
			$select['LAST_DISCOUNT'] = true;

		if (
			!isset($discount['TYPE'])
			|| ($discount['TYPE'] != Catalog\DiscountTable::TYPE_DISCOUNT && $discount['TYPE'] != Catalog\DiscountTable::TYPE_DISCOUNT_SAVE)
		)
			$select['TYPE'] = true;
		if (!isset($discount['VALUE_TYPE']))
		{
			$select['VALUE_TYPE'] = true;
			$select['VALUE'] = true;
			$select['MAX_DISCOUNT'] = true;
			$select['CURRENCY'] = true;
		}
		else
		{
			if (!isset($discount['VALUE']))
				$select['VALUE'] = true;
			if (!isset($discount['CURRENCY']))
				$select['CURRENCY'] = true;
			if ($discount['VALUE_TYPE'] == Catalog\DiscountTable::VALUE_TYPE_PERCENT && !isset($discount['MAX_VALUE']))
				$select['MAX_DISCOUNT'] = true;
		}
		$selectKeys = array_keys($select);

		if (!empty($select))
		{
			$discountIterator = Catalog\DiscountTable::getList(array(
				'select' => $selectKeys,
				'filter' => array('=ID' => $id)
			));
			$loadData = $discountIterator->fetch();
			if (empty($loadData))
				return false;
			$discount = array_merge($loadData, $discount);
			if (isset($discount['CONDITIONS_LIST']))
			{
				$discount['CONDITIONS'] = $discount['CONDITIONS_LIST'];
				unset($discount['CONDITIONS_LIST']);
			}
			if (isset($discount['MAX_DISCOUNT']))
			{
				$discount['MAX_VALUE'] = $discount['MAX_DISCOUNT'];
				unset($discount['MAX_DISCOUNT']);
			}
			unset($loadData, $discountIterator);
		}
		$discount['DISCOUNT_ID'] = $id;
		if (empty($discount['MODULE_ID']))
			$discount['MODULE_ID'] = 'catalog';
		if (array_key_exists('HANDLERS', $discount))
		{
			if (!empty($discount['HANDLERS']['MODULES']) && empty($discount['MODULES']))
				$discount['MODULES'] = $discount['HANDLERS']['MODULES'];
			unset($discount['HANDLERS']);
		}
		if (empty($discount['MODULES']))
		{
			$discount['MODULES'] = array();

			$conn = Main\Application::getConnection();
			$helper = $conn->getSqlHelper();
			$moduleIterator = $conn->query(
				'select MODULE_ID from '.$helper->quote('b_catalog_discount_module').' where '.$helper->quote('DISCOUNT_ID').' = '.$id
			);
			while ($module = $moduleIterator->fetch())
				$discount['MODULES'][] = $module['MODULE_ID'];
			unset($module, $moduleIterator, $helper, $conn);
			if (!in_array('catalog', $discount['MODULES']))
				$discount['MODULES'][] = 'catalog';
		}
		self::$typeCache[$id] = $discount['TYPE'];

		return $discount;
	}

	/**
	 * Prepare entity to iblock and catalog fields.
	 *
	 * @param array $entityList			Entity list.
	 * @return array|bool
	 */
	protected static function prepareEntity($entityList)
	{
		$result = array(
			'iblockFields' => array(),
			'sections' => false,
			'iblockProperties' => array(),
			'iblockPropertiesMap' => array(),
			'catalogFields' => array()
		);

		if (!is_array($entityList))
			return false;

		if (empty($entityList['catalog']))
			return $result;

		if (!empty($entityList['catalog']))
		{
			if (!empty($entityList['catalog']['ELEMENT']) && is_array($entityList['catalog']['ELEMENT']))
			{
				foreach ($entityList['catalog']['ELEMENT'] as $entity)
				{
					if ($entity['FIELD_ENTITY'] == 'SECTION_ID')
					{
						$result['sections'] = true;
						continue;
					}
					$result['iblockFields'][$entity['FIELD_TABLE']] = $entity['FIELD_ENTITY'];
				}
				unset($entity);
			}
			if (!empty($entityList['catalog']['ELEMENT_PROPERTY']) && is_array($entityList['catalog']['ELEMENT_PROPERTY']))
			{
				foreach ($entityList['catalog']['ELEMENT_PROPERTY'] as $entity)
				{
					$propertyData = explode(':', $entity['FIELD_TABLE']);
					if (!is_array($propertyData) || count($propertyData) != 2)
						continue;
					$iblock = (int)$propertyData[0];
					$property = (int)$propertyData[1];
					unset($propertyData);
					if (!isset($result['iblockProperties'][$iblock]))
						$result['iblockProperties'][$iblock] = array();
					$result['iblockProperties'][$iblock][] = $property;
					if (!isset($result['iblockPropertiesMap'][$iblock]))
						$result['iblockPropertiesMap'][$iblock] = array();
					$result['iblockPropertiesMap'][$iblock][$property] = $entity['FIELD_ENTITY'];
				}
				unset($iblock, $property, $entity);
			}

			if (!empty($entityList['catalog']['PRODUCT']) && is_array($entityList['catalog']['PRODUCT']))
			{
				foreach ($entityList['catalog']['PRODUCT'] as $entity)
					$result['catalogFields'][$entity['FIELD_TABLE']] = $entity['FIELD_ENTITY'];
				unset($entity);
			}
		}

		return $result;
	}

	/**
	 * Returns product separate by iblocks.
	 *
	 * @param array $productList		Product id list.
	 * @return array
	 */
	protected static function getProductIblocks($productList)
	{
		$result = array(
			'iblockElement' => array(),
			'iblockList' => array(),
			'skuIblockList' => array()
		);

		if (empty($productList))
			return $result;

		$elementIterator = Iblock\ElementTable::getList(array(
			'select' => array('ID', 'IBLOCK_ID'),
			'filter' => array('@ID' => $productList)
		));
		while ($element = $elementIterator->fetch())
		{
			$element['ID'] = (int)$element['ID'];
			$element['IBLOCK_ID'] = (int)$element['IBLOCK_ID'];
			if (!isset($result['iblockElement'][$element['IBLOCK_ID']]))
				$result['iblockElement'][$element['IBLOCK_ID']] = array();
			$result['iblockElement'][$element['IBLOCK_ID']][] = $element['ID'];
		}
		unset($element, $elementIterator);
		if (!empty($result['iblockElement']))
		{
			$result['iblockList'] = array_keys($result['iblockElement']);

			$skuIterator = Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID'),
				'filter' => array('@IBLOCK_ID' => $result['iblockList'], '!=PRODUCT_IBLOCK_ID' => 0)
			));
			while ($sku = $skuIterator->fetch())
			{
				$sku['IBLOCK_ID'] = (int)$sku['IBLOCK_ID'];
				$sku['PRODUCT_IBLOCK_ID'] = (int)$sku['PRODUCT_IBLOCK_ID'];
				$sku['SKU_PROPERTY_ID'] = (int)$sku['SKU_PROPERTY_ID'];
				$result['skuIblockList'][$sku['IBLOCK_ID']] = $sku;
			}
			unset($sku, $skuIterator);
		}

		return $result;
	}

	/**
	 * Create property list for discounts.
	 *
	 * @param array &$entityData			Entity data.
	 * @param array $iblockData				Iblock data.
	 * @return void
	 */
	protected static function fillProductPropertyList(&$entityData, $iblockData)
	{
		$entityData['needProperties'] = array();
		if (!empty($entityData['iblockProperties']) && !empty($iblockData['iblockList']))
		{
			foreach ($iblockData['iblockList'] as $iblock)
			{
				if (!empty($entityData['iblockProperties'][$iblock]))
					$entityData['needProperties'][$iblock] = $entityData['iblockProperties'][$iblock];
			}
			unset($iblock);
		}
		if (!empty($iblockData['skuIblockList']))
		{
			foreach ($iblockData['skuIblockList'] as $skuData)
			{
				if (!isset($entityData['needProperties'][$skuData['IBLOCK_ID']]))
					$entityData['needProperties'][$skuData['IBLOCK_ID']] = array();
				$entityData['needProperties'][$skuData['IBLOCK_ID']][] = $skuData['SKU_PROPERTY_ID'];
				$entityData['iblockPropertiesMap'][$skuData['IBLOCK_ID']][$skuData['SKU_PROPERTY_ID']] = 'PARENT_ID';
				if (!empty($entityData['iblockProperties'][$skuData['PRODUCT_IBLOCK_ID']]))
					$entityData['needProperties'][$skuData['PRODUCT_IBLOCK_ID']] = $entityData['iblockProperties'][$skuData['PRODUCT_IBLOCK_ID']];
			}
			unset($skuData);
		}
	}

	/**
	 * Convert properties values to discount format.
	 *
	 * @param array &$productData			Product data.
	 * @param array $propertyValues			Product properties.
	 * @param array $entityData				Entity data.
	 * @param array $iblockData				Iblock data.
	 * @return void
	 */
	protected static function convertProperties(&$productData, $propertyValues, $entityData, $iblockData)
	{
		if (empty($productData) || !is_array($productData))
			return;
		if (empty($propertyValues) || !is_array($propertyValues))
			return;
		if (empty($entityData) || !is_array($entityData))
			return;
		if (empty($iblockData) || !is_array($iblockData))
			return;

		if (empty($entityData['needProperties']) || !is_array($entityData['needProperties']))
			return;
		$propertyIblocks = array_keys($entityData['needProperties']);
		foreach ($propertyIblocks as &$iblock)
		{
			if (empty($iblockData['iblockElement'][$iblock]))
				continue;
			$propertyMap = $entityData['iblockPropertiesMap'][$iblock];
			foreach ($iblockData['iblockElement'][$iblock] as $element)
			{
				if (empty($propertyValues[$element]))
					continue;
				foreach ($propertyValues[$element] as $property)
				{
					if (empty($property) || empty($property['ID']))
						continue;
					if ($property['PROPERTY_TYPE'] == Iblock\PropertyTable::TYPE_FILE)
						continue;
					$property['ID'] = (int)$property['ID'];
					if (empty($propertyMap[$property['ID']]))
						continue;
					$propertyKey = $propertyMap[$property['ID']];
					$value = '';

					$check = false;
					if ($property['MULTIPLE'] == 'N')
					{
						if (!empty($property['USER_TYPE']))
						{
							switch($property['USER_TYPE'])
							{
								case 'DateTime':
								case 'Date':
									$property['VALUE'] = (string)$property['VALUE'];
									if ($property['VALUE'] != '')
									{
										$propertyFormat = false;
										if ($property['USER_TYPE'] == 'DateTime')
										{
											if (defined('FORMAT_DATETIME'))
												$propertyFormat = FORMAT_DATETIME;
										}
										else
										{
											if (defined('FORMAT_DATE'))
												$propertyFormat = FORMAT_DATE;
										}
										$intStackTimestamp = (int)$property['VALUE'];
										$property['VALUE'] = (
										$intStackTimestamp.'!' != $property['VALUE'].'!'
											? (int)MakeTimeStamp($property['VALUE'], $propertyFormat)
											: $intStackTimestamp
										);
									}
									$value = $property['VALUE'];
									$check = true;
									break;
							}
						}
						if (!$check)
						{
							switch ($property['PROPERTY_TYPE'])
							{
								case Iblock\PropertyTable::TYPE_LIST:
									$property['VALUE_ENUM_ID'] = (int)$property['VALUE_ENUM_ID'];
									$value = ($property['VALUE_ENUM_ID'] > 0 ? $property['VALUE_ENUM_ID'] : -1);
									break;
								case Iblock\PropertyTable::TYPE_ELEMENT:
								case Iblock\PropertyTable::TYPE_SECTION:
									$property['VALUE'] = (int)$property['VALUE'];
									$value = ($property['VALUE'] > 0 ? $property['VALUE'] : -1);
									break;
								default:
									$value = $property['VALUE'];
									break;
							}
						}
					}
					else
					{
						$value = array();
						if (!empty($property['USER_TYPE']))
						{
							switch($property['USER_TYPE'])
							{
								case 'DateTime':
								case 'Date':
									if (!empty($property['VALUE']) && is_array($property['VALUE']))
									{
										$propertyFormat = false;
										if ($property['USER_TYPE'] == 'DateTime')
										{
											if (defined('FORMAT_DATETIME'))
												$propertyFormat = FORMAT_DATETIME;
										}
										else
										{
											if (defined('FORMAT_DATE'))
												$propertyFormat = FORMAT_DATE;
										}
										foreach ($property['VALUE'] as &$oneValue)
										{
											$oneValue = (string)$oneValue;
											if ('' != $oneValue)
											{
												$intStackTimestamp = (int)$oneValue;
												if ($intStackTimestamp.'!' != $oneValue.'!')
													$oneValue = (int)MakeTimeStamp($oneValue, $propertyFormat);
												else
													$oneValue = $intStackTimestamp;
											}
											$value[] = $oneValue;
										}
										unset($oneValue, $propertyFormat);
									}
									$check = true;
									break;
							}
						}
						if (!$check)
						{
							switch ($property['PROPERTY_TYPE'])
							{
								case Iblock\PropertyTable::TYPE_LIST:
									if (!empty($property['VALUE_ENUM_ID']) && is_array($property['VALUE_ENUM_ID']))
									{
										foreach ($property['VALUE_ENUM_ID'] as &$oneValue)
										{
											$oneValue = (int)$oneValue;
											if ($oneValue > 0)
												$value[] = $oneValue;
										}
										unset($oneValue);
									}
									if (empty($value))
										$value = array(-1);
									break;
								case Iblock\PropertyTable::TYPE_ELEMENT:
								case Iblock\PropertyTable::TYPE_SECTION:
									if (!empty($property['VALUE']) && is_array($property['VALUE']))
									{
										foreach ($property['VALUE'] as &$oneValue)
										{
											$oneValue = (int)$oneValue;
											if ($oneValue > 0)
												$value[] = $oneValue;
										}
										unset($oneValue);
									}
									if (empty($value))
										$value = array(-1);
									break;
								default:
									$value = $property['VALUE'];
									break;
							}
						}
					}
					$productData[$element][$propertyKey] = (is_array($value) ? $value : array($value));
				}
			}
			unset($element);
		}
		unset($iblock);
	}

	/**
	 * Returns parent product data.
	 *
	 * @param array &$productData			Product data.
	 * @param array $entityData				Entity data.
	 * @param array $iblockData				Iblock data.
	 * @return void
	 */
	protected static function getParentProducts(&$productData, $entityData, $iblockData)
	{
		if (empty($iblockData['skuIblockList']))
			return;
		if (empty($productData) || !is_array($productData))
			return;
		$parentMap = array();
		$parentData = array();
		$parentIblockData = array(
			'iblockElement' => array(),
			'iblockList' => array()
		);
		if (!empty($entityData['iblockFields']))
		{
			foreach ($entityData['iblockFields'] as &$value)
				$value = 'PARENT_'.$value;
		}
		foreach ($iblockData['skuIblockList'] as $skuData)
		{
			if (empty($iblockData['iblockElement'][$skuData['IBLOCK_ID']]))
				continue;
			foreach ($iblockData['iblockElement'][$skuData['IBLOCK_ID']] as $element)
			{
				if (empty($productData[$element]['PARENT_ID']))
					continue;
				$parentId = (int)(
				is_array($productData[$element]['PARENT_ID'])
					? current($productData[$element]['PARENT_ID'])
					: $productData[$element]['PARENT_ID']
				);
				if ($parentId <= 0)
					continue;
				if (!isset($parentMap[$parentId]))
					$parentMap[$parentId] = array();
				$parentMap[$parentId][] = $element;
				$parentData[$parentId] = array();
				if (!isset($parentIblockData['iblockElement'][$skuData['PRODUCT_IBLOCK_ID']]))
					$parentIblockData['iblockElement'][$skuData['PRODUCT_IBLOCK_ID']] = array();
				$parentIblockData['iblockElement'][$skuData['PRODUCT_IBLOCK_ID']][] = $parentId;
			}
			unset($parentId, $element);
		}
		unset($skuData);
		if (empty($parentIblockData['iblockElement']))
			return;
		$parentIblockData['iblockList'] = array_keys($parentIblockData['iblockElement']);

		self::getProductData($parentData, $entityData, $parentIblockData);

		foreach ($parentData as $parentId => $data)
		{
			$parentSections = array();
			if ($entityData['sections'])
			{
				$parentSections = $data['SECTION_ID'];
				unset($data['SECTION_ID']);
			}
			foreach ($parentMap[$parentId] as $element)
			{
				$productData[$element] = array_merge($productData[$element], $data);
				if ($entityData['sections'])
				{
					$productData[$element]['SECTION_ID'] = (
						empty($productData['SECTION_ID'])
						? $parentSections
						: array_merge($productData[$element]['SECTION_ID'], $parentSections)
					);
				}
			}
			unset($element, $parentSections);
		}
		unset($parentId, $data);
	}

	/**
	 * Returns product data.
	 *
	 * @param array &$productData			Product data.
	 * @param array $entityData				Entity data.
	 * @param array $iblockData				Iblock list data.
	 * @return void
	 */
	protected static function getProductData(&$productData, $entityData, $iblockData)
	{
		if (!empty($iblockData['iblockElement']))
		{
			$productList = array_keys($productData);
			if (!empty($entityData['iblockFields']))
			{
				$elementIterator = Iblock\ElementTable::getList(array(
					'select' => array_merge(array('ID'), array_keys($entityData['iblockFields'])),
					'filter' => array('@ID' => $productList)
				));
				while ($element = $elementIterator->fetch())
				{
					$element['ID'] = (int)$element['ID'];
					$fields = array();
					foreach ($entityData['iblockFields'] as $key => $alias)
						$fields[$alias] = $element[$key];
					unset($key, $alias);
					$productData[$element['ID']] = (
						empty($productData[$element['ID']])
						? $fields
						: array_merge($productData[$element['ID']], $fields)
					);
					unset($fields);
				}
			}
			if ($entityData['sections'])
			{
				$productSection = array_fill_keys($productList, array());
				$elementSectionIterator = Iblock\SectionElementTable::getList(array(
					'select' => array('*'),
					'filter' => array('@IBLOCK_ELEMENT_ID' => $productList)
				));
				while ($elementSection = $elementSectionIterator->fetch())
				{
					$elementSection['IBLOCK_ELEMENT_ID'] = (int)$elementSection['IBLOCK_ELEMENT_ID'];
					$elementSection['IBLOCK_SECTION_ID'] = (int)$elementSection['IBLOCK_SECTION_ID'];
					$elementSection['ADDITIONAL_PROPERTY_ID'] = (int)$elementSection['ADDITIONAL_PROPERTY_ID'];
					if ($elementSection['ADDITIONAL_PROPERTY_ID'] > 0)
						continue;
					$productSection[$elementSection['IBLOCK_ELEMENT_ID']][$elementSection['IBLOCK_SECTION_ID']] = true;
					$parentSectionIterator = \CIBlockSection::getNavChain(0, $elementSection['IBLOCK_SECTION_ID'], array('ID'));
					while ($parentSection = $parentSectionIterator->fetch())
					{
						$parentSection['ID'] = (int)$parentSection['ID'];
						$productSection[$elementSection['IBLOCK_ELEMENT_ID']][$parentSection['ID']] = true;
					}
					unset($parentSection, $parentSectionIterator);
				}
				unset($elementSection, $elementSectionIterator);
				foreach ($productSection as $element => $sections)
					$productData[$element]['SECTION_ID'] = array_keys($sections);
				unset($element, $sections, $productSection);
			}
			if (!empty($entityData['needProperties']))
			{
				$propertyValues = array_fill_keys($productList, array());
				foreach ($entityData['needProperties'] as $iblock => $propertyList)
				{
					if (empty($iblockData['iblockElement'][$iblock]))
						continue;
					$filter = array(
						'ID' => $iblockData['iblockElement'][$iblock],
						'IBLOCK_ID' => $iblock
					);
					\CTimeZone::disable();
					\CIBlockElement::getPropertyValuesArray($propertyValues, $iblock, $filter, array('ID' => $propertyList));
					\CTimeZone::enable();
				}
				unset($filter, $iblock, $propertyList);
				self::convertProperties($productData, $propertyValues, $entityData, $iblockData);
			}
			if (!empty($entityData['catalogFields']))
			{
				$productIterator = Catalog\ProductTable::getList(array(
					'select' => array_merge(array('ID'), array_keys($entityData['catalogFields'])),
					'filter' => array('@ID' => $productList)
				));
				while ($product = $productIterator->fetch())
				{
					$product['ID'] = (int)$product['ID'];
					$fields = array();
					foreach ($entityData['catalogFields'] as $key => $alias)
						$fields[$alias] = $product[$key];
					unset($key, $alias);
					$productData[$product['ID']] = (
						empty($productData[$product['ID']])
						? $fields
						: array_merge($productData[$product['ID']], $fields)
					);
					unset($fields);
				}
				unset($product, $productIterator);
			}

			if (!empty($iblockData['skuIblockList']))
				self::getParentProducts($productData, $entityData, $iblockData);
		}
	}

	/**
	 * Create sale action.
	 *
	 * @param array &$discount			Discount data.
	 * @param array $params				Manager parameters.
	 * @return void
	 */
	protected static function createSaleAction(&$discount, $params)
	{
		$data = array(
			'TYPE' => $discount['VALUE_TYPE'],
			'VALUE' => $discount['VALUE'],
			'CURRENCY' => $discount['CURRENCY'],
			'USE_BASE_PRICE' => $params['USE_BASE_PRICE']
		);
		if ($discount['TYPE'] == Catalog\DiscountTable::VALUE_TYPE_PERCENT)
			$data['MAX_VALUE'] = $discount['MAX_VALUE'];

		$action = '\Bitrix\Catalog\Discount\DiscountManager::applyDiscount('.$params['BASKET_ITEM'].', '.var_export($data, true).');';
		$discount['APPLICATION'] = 'function (&'.$params['BASKET_ITEM'].'){'.$action.'};';
		$discount['ACTIONS'] = $data;

		if (Loader::includeModule('sale'))
		{
			$type = '';
			$descr = array(
				'VALUE_ACTION' => (
					$discount['TYPE'] == Catalog\DiscountTable::TYPE_DISCOUNT_SAVE
					? Sale\OrderDiscountManager::DESCR_VALUE_ACTION_ACCUMULATE
					: Sale\OrderDiscountManager::DESCR_VALUE_ACTION_DISCOUNT
				),
				'VALUE' => $discount['VALUE']
			);
			switch ($discount['VALUE_TYPE'])
			{
				case Catalog\DiscountTable::VALUE_TYPE_PERCENT:
					$type = (
						$discount['MAX_VALUE'] > 0
						? Sale\OrderDiscountManager::DESCR_TYPE_LIMIT_VALUE
						: Sale\OrderDiscountManager::DESCR_TYPE_VALUE
					);
					$descr['VALUE_TYPE'] = Sale\OrderDiscountManager::DESCR_VALUE_TYPE_PERCENT;
					if ($discount['MAX_VALUE'] > 0)
					{
						$descr['LIMIT_TYPE'] = Sale\OrderDiscountManager::DESCR_LIMIT_MAX;
						$descr['LIMIT_UNIT'] = $discount['CURRENCY'];
						$descr['LIMIT_VALUE'] = $discount['MAX_VALUE'];
					}
					break;
				case Catalog\DiscountTable::VALUE_TYPE_FIX:
					$type = Sale\OrderDiscountManager::DESCR_TYPE_VALUE;
					$descr['VALUE_TYPE'] = Sale\OrderDiscountManager::DESCR_VALUE_TYPE_CURRENCY;
					$descr['VALUE_UNIT'] = $discount['CURRENCY'];
					break;
				case Catalog\DiscountTable::VALUE_TYPE_SALE:
					$type = Sale\OrderDiscountManager::DESCR_TYPE_FIXED;
					$descr['VALUE_UNIT'] = $discount['CURRENCY'];
					break;
			}
			$descrResult = Sale\OrderDiscountManager::prepareDiscountDescription($type, $descr);
			if ($descrResult->isSuccess())
			{
				$discount['ACTIONS_DESCR'] = array(
					'BASKET' => array(
						0 => $descrResult->getData()
					)
				);
			}
			unset($descrResult, $descr, $type);
		}

		unset($action, $data);
	}
}