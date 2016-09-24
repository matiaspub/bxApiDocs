<?php
namespace Bitrix\Catalog\Helpers\Admin;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Catalog,
	Bitrix\Iblock;

Loc::loadMessages(__FILE__);

class CatalogEdit
{
	const CATALOG_ACTION_ADD = 'add';
	const CATALOG_ACTION_UPDATE = 'update';
	const CATALOG_ACTION_DELETE = 'delete';

	const IBLOCK_ACTION_FILL_PRODUCT = 0x0001;


	protected $iblockId = 0;
	protected $iblockData = array();
	protected $iblockCatalogData = array();

	protected $simpleIblock = true;
	protected $parentIblock = false;
	protected $offerIblock = false;
	protected $catalogIblock = false;

	protected $enableRecurring = null;

	protected $updateData = array();
	protected $catalogTableActions = array();
	protected $iblockActions = array();

	protected $errors = array();

	protected static $siteListSeparator = '|';

	/**
	 * @param int $iblockId				Iblock ID.
	 */
	public function __construct($iblockId)
	{
		$this->iblockId = (int)$iblockId;
		$this->enableRecurring = \CBXFeatures::isFeatureEnabled('SaleRecurring');
		$this->loadIblock();
		$this->loadCatalog();
		$this->initUpdateData();
	}

	/**
	 * Return current status.
	 *
	 * @return bool
	 */
	public function isSuccess()
	{
		return empty($this->errors);
	}

	/**
	 * Return current errors.
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Clear current errors.
	 *
	 * @return void
	 */
	public function clearErrors()
	{
		$this->errors = array();
	}

	/**
	 * Return sale recurring feature state.
	 *
	 * @return bool
	 */
	public function isEnableRecurring()
	{
		return $this->enableRecurring;
	}

	/**
	 * Load iblock data.
	 *
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public function loadIblock()
	{
		if ($this->iblockId <= 0)
		{
			$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_IBLOCK_ID_ABSENT');
			return;
		}
		$this->iblockData = self::loadIblockFromDatabase($this->iblockId);
		if (empty($this->iblockData))
		{
			$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_IBLOCK_IS_NOT_EXISTS');
			return;
		}
		$siteList = self::loadIblockSitesFromDatabase($this->iblockId);
		if (empty($siteList))
		{
			$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_IBLOCK_SITELIST_IS_EMPTY');
			return;
		}
		$this->iblockData['SITES'] = static::getSiteListString($siteList, true);
		unset($siteList);
	}

	/**
	 * Load catalog data from database.
	 *
	 * @return void
	 */
	public function loadCatalog()
	{
		if (!$this->isSuccess())
			return;
		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		$this->iblockCatalogData = \CCatalogSku::getInfoByIBlock($this->iblockId);
		if ($this->iblockCatalogData === false)
			$this->iblockCatalogData = array();
		$this->simpleIblock = self::isSimpleIblock($this->iblockCatalogData);
		if (!$this->simpleIblock)
		{
			$this->parentIblock = self::isParentIblock($this->iblockCatalogData);
			$this->offerIblock = self::isOfferIblock($this->iblockCatalogData);
			$this->catalogIblock = self::isCatalogIblock($this->iblockCatalogData);
			$this->iblockCatalogData['USE_SKU'] = ($this->parentIblock ? 'Y' : 'N');
		}
	}

	/**
	 * Return iblock data.
	 *
	 * @return array
	 */
	public function getIblock()
	{
		return $this->iblockData;
	}

	/**
	 * Return catalog data.
	 *
	 * @return array
	 */
	public function getCatalog()
	{
		return $this->iblockCatalogData;
	}

	/**
	 * Save new catalog data.
	 *
	 * @param array $catalogData			Post form params.
	 * @return void
	 */
	public function saveCatalog($catalogData)
	{
		if (!$this->isSuccess())
			return;

		$this->prepareCatalogData($catalogData);
		if (!$this->isSuccess())
			return;

		if (empty($this->catalogTableActions))
			return;

		foreach ($this->catalogTableActions as $iblockId => $action)
		{
			switch ($action)
			{
				case self::CATALOG_ACTION_ADD:
					break;
				case self::CATALOG_ACTION_UPDATE:


					break;
				case self::CATALOG_ACTION_DELETE:
					$result = \CCatalog::delete($iblockId);
					if (!$result)
					{

					}
					break;
			}
			if (!$this->isSuccess())
				break;
		}
		unset($iblockId, $action);
	}

	/**
	 * Check catalog data before update.
	 *
	 * @param array $catalogData			Post form params.
	 * @return void
	 */
	public function prepareCatalogData($catalogData)
	{
		$checkedData = $this->updateData;
		if (!$this->isSuccess())
			return;

		if (empty($catalogData))
		{
			$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_EMPTY_DATA');
			return;
		}
		elseif (!is_array($catalogData))
		{
			$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_BAD_DATA');
			return;
		}

		if (!isset($catalogData['CATALOG']) || ($catalogData['CATALOG'] != 'Y' && $catalogData['CATALOG'] != 'N'))
			$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_FIELD_CATALOG_IS_ABSENT');

		if (!isset($catalogData['USE_SKU']) || ($catalogData['USE_SKU'] != 'Y' && $catalogData['USE_SKU'] != 'N'))
			$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_FIELD_USE_SKU_IS_ABSENT');

		if ($this->isEnableRecurring())
		{
			if (!isset($catalogData['SUBSCRIPTION']) || ($catalogData['SUBSCRIPTION'] != 'Y' && $catalogData['SUBSCRIPTION'] != 'N'))
				$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_FIELD_SUBSCRIPTION_IS_ABSENT');
		}

		if (!$this->isSuccess())
			return;

		if (!$this->simpleIblock)
		{
			if ($this->offerIblock && $catalogData['CATALOG'] == 'N')
				$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_OFFERS_ONLY_CATALOG');
			if ($this->isEnableRecurring() && $this->parentIblock && $catalogData['SUBSCRIPTION'] == 'Y')
				$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_PARENT_IBLOCK_WITH_SUBSCRIPTION');
			if ($this->offerIblock && $catalogData['USE_SKU'] == 'Y')
				$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_SKU_WITH_SKU');
		}

		if (!$this->isSuccess())
			return;

		$skuIblockId = 0;
		$skuCatalog = false;
		if ($catalogData['USE_SKU'] == 'Y')
		{
			if (!isset($catalogData['SKU']) || (int)$catalogData['SKU'] <= 0)
				$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_FIELD_SKU_IS_ABSENT');

			if ($this->isSuccess())
			{
				$skuIblockId = (int)$catalogData['SKU'];
				if ($skuIblockId == $this->iblockId)
					$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_SKU_SELF');

				if ($this->isSuccess())
				{
					$skuIblock = self::loadIblockFromDatabase($skuIblockId);
					if (empty($skuIblock))
						$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_SKU_BAD');
					unset($skuIblock);
				}

				if ($this->isSuccess())
				{
					$skuSiteList = self::loadIblockSitesFromDatabase($skuIblockId);
					if (empty($skuSiteList))
						$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_SKU_SITES_EMPTY');
					elseif ($this->iblockData['SITES'] != self::getSiteListString($skuSiteList, true))
						$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_SKU_SITES_NOT_EQUAL');
					unset($skuSiteList);
				}

				if ($this->isSuccess())
				{
					$skuCatalog = \CCatalogSku::getInfoByIBlock($skuIblockId);
					if (!self::isSimpleIblock($skuCatalog))
					{
						if (self::isParentIblock($skuCatalog))
							$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_SKU_WITH_SKU');
						elseif (self::isOfferIblock($skuCatalog) && $skuCatalog['PRODUCT_IBLOCK_ID'] != $this->iblockId)
							$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_SKU_FROM_OTHER_IBLOCK');
					}
				}
			}
		}
		else
		{
			if (!$this->simpleIblock && $this->offerIblock)
			{
				if (!isset($catalogData['SKU']) || (int)$catalogData['SKU'] <= 0)
					$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_SKU_PARENT_IBLOCK_IS_ABSENT');
				elseif ($this->iblockCatalogData['PRODUCT_IBLOCK_ID'] != $catalogData['SKU'])
					$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_SKU_PARENT_IBLOCK_OTHER');
			}
		}

		if (!$this->isSuccess())
			return;

		if ($catalogData['CATALOG'] == 'Y')
		{
			if (!isset($catalogData['VAT_ID']))
				$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_VAT_ID_IS_ABSENT');
			elseif ((int)$catalogData['VAT_ID'] < 0)
				$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_BAD_VAT_ID');
			if (!isset($catalogData['YANDEX_EXPORT']))
				$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_YANDEX_EXPORT_IS_ABSENT');
			elseif ($catalogData['YANDEX_EXPORT'] != 'Y' && $catalogData['YANDEX_EXPORT'] != 'N')
				$this->errors[] = Loc::getMessage('BX_CAT_HELPER_ADMIN_CATALOGEDIT_ERR_BAD_YANDEX_EXPORT');
		}

		if (!$this->isSuccess())
			return;

		if ($catalogData['CATALOG'] != $this->iblockCatalogData['CATALOG'])
		{
			if ($catalogData['CATALOG'] == 'Y')
			{
				$this->catalogTableActions[$this->iblockId] = self::CATALOG_ACTION_ADD;
				if (!isset($checkedData[$this->iblockId]))
					$checkedData[$this->iblockId] = array();
				$checkedData[$this->iblockId]['IBLOCK_ID'] = $this->iblockId;
			}
			else
			{
				$this->catalogTableActions[$this->iblockId] = self::CATALOG_ACTION_DELETE;
				// TODO: set clear iblock
			}
		}

		if ($catalogData['USE_SKU'] != $this->iblockCatalogData['USE_SKU'])
		{
			if ($catalogData['USE_SKU'] == 'Y')
			{
				$newOffersIBlock = self::isSimpleIblock($skuCatalog);
				if ($newOffersIBlock || $skuCatalog['PRODUCT_IBLOCK_ID'] == 0)
				{
					if (!isset($checkedData[$skuIblockId]))
						$checkedData[$skuIblockId] = array();
					$checkedData[$skuIblockId]['PRODUCT_IBLOCK_ID'] = $this->iblockId;
					$checkedData[$skuIblockId]['SKU_PROPERTY_ID'] = 0;
					if ($newOffersIBlock)
						$checkedData[$skuIblockId]['IBLOCK_ID'] = $skuIblockId;
				}
				unset($newOffersIBlock);
			}
			else
			{

			}
		}

		$this->updateData = $checkedData;
	}

	/**
	 * Return iblock site list in string format.
	 *
	 * @param array $siteList			Iblock site list.
	 * @param bool $sorted				Site list already sorted.
	 * @return string
	 */
	protected static function getSiteListString($siteList, $sorted = false)
	{
		if (empty($siteList) || !is_array($siteList))
			return '';
		$sorted = ($sorted === true);
		if (!$sorted)
			sort($siteList);
		return implode(self::$siteListSeparator, $siteList);
	}

	/**
	 * Load iblock data from database.
	 *
	 * @param int $iblockId				Iblock id.
	 * @return array|bool|false
	 * @throws Main\ArgumentException
	 */
	protected static function loadIblockFromDatabase($iblockId)
	{
		$iblockId = (int)$iblockId;
		if ($iblockId <= 0)
			return false;
		return Iblock\IblockTable::getList(array(
			'select' => array('ID', 'NAME', 'IBLOCK_TYPE_ID', 'ACTIVE', 'PROPERTY_INDEX'),
			'filter' => array('=ID' => $iblockId)
		))->fetch();
	}

	/**
	 * Load iblock sites from database.
	 *
	 * @param int $iblockId				Iblock id.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	protected static function loadIblockSitesFromDatabase($iblockId)
	{
		$iblockId = (int)$iblockId;
		if ($iblockId <= 0)
			return array();
		$result = array();
		$sitesIterator = Iblock\IblockSiteTable::getList(array(
			'select' => array('SITE_ID'),
			'filter' => array('=IBLOCK_ID' => $iblockId),
			'order' => array('SITE_ID' => 'ASC')
		));
		while ($site = $sitesIterator->fetch())
			$result[] = $site['SITE_ID'];
		unset($site, $sitesIterator);
		return $result;
	}

	/**
	 * Return is iblock not use in catalog module.
	 *
	 * @param bool|array $iblockCatalog		Catalog data.
	 * @return bool
	 */
	protected static function isSimpleIblock($iblockCatalog)
	{
		return (empty($iblockCatalog) || !is_array($iblockCatalog));
	}

	/**
	 * Return is iblock - catalog.
	 *
	 * @param array $iblockCatalog		Catalog data.
	 * @return bool
	 */
	protected static function isCatalogIblock($iblockCatalog)
	{
		return (
			is_array($iblockCatalog)
			&& (
				$iblockCatalog['CATALOG_TYPE'] == \CCatalogSku::TYPE_CATALOG
				|| $iblockCatalog['CATALOG_TYPE'] == \CCatalogSku::TYPE_FULL
			)
		);
	}

	/**
	 * Return is iblock use sku.
	 *
	 * @param array $iblockCatalog		Catalog data.
	 * @return bool
	 */
	protected static function isParentIblock($iblockCatalog)
	{
		return (
			is_array($iblockCatalog)
			&& (
				$iblockCatalog['CATALOG_TYPE'] == \CCatalogSku::TYPE_PRODUCT
				|| $iblockCatalog['CATALOG_TYPE'] == \CCatalogSku::TYPE_FULL
			)
		);
	}

	/**
	 * Return is sku iblock.
	 *
	 * @param array $iblockCatalog		Catalog data.
	 * @return bool
	 */
	protected static function isOfferIblock($iblockCatalog)
	{
		return (is_array($iblockCatalog) && $iblockCatalog['CATALOG_TYPE'] == \CCatalogSku::TYPE_OFFERS);
	}

	/**
	 * Init catalog data for update.
	 *
	 * @return void
	 */
	protected function initUpdateData()
	{
		if (!$this->isSuccess())
			return;

		if ($this->simpleIblock)
			return;

		if ($this->offerIblock || $this->catalogIblock)
		{
			$this->updateData[$this->iblockId] = array();
			$this->catalogTableActions[$this->iblockCatalogData['IBLOCK_ID']] = self::CATALOG_ACTION_UPDATE;
		}
		if ($this->parentIblock)
		{
			$this->updateData[$this->iblockCatalogData['IBLOCK_ID']] = array();
			$this->catalogTableActions[$this->iblockCatalogData['IBLOCK_ID']] = self::CATALOG_ACTION_UPDATE;
		}
	}

	/**
	 * Get sku property.
	 *
	 * @param int $parentiblockId		Product iblock Id.
	 * @param int $offerIblockId		Offer iblock id.
	 * @return int
	 */
	protected function createSkuProperty($parentiblockId, $offerIblockId)
	{
		$parentiblockId = (int)$parentiblockId;
		$offerIblockId = (int)$offerIblockId;
		if ($parentiblockId <= 0 || $offerIblockId <= 0)
			return 0;

		$result = \CIBlockPropertyTools::createProperty(
			$offerIblockId,
			\CIBlockPropertyTools::CODE_SKU_LINK,
			array('LINK_IBLOCK_ID' => $parentiblockId)
		);
		if (!$result)
			$this->errors = array_merge($this->errors, \CIBlockPropertyTools::getErrors());

		return $result;
	}
}