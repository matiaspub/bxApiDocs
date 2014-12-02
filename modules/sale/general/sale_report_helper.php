<?php
if (!CModule::IncludeModule('report'))
	return;
if (!CModule::IncludeModule('currency'))
	return;

use Bitrix\Main\Entity;
use Bitrix\Main\Text\String;

abstract class CBaseSaleReportHelper extends CReportHelper
{
	// names of base entities for report construction (name with 0 index is default)
	private static $owners = array();

	private static $fInit = false;
	private static $fOwnersInit = false;
	protected static $fDecimalQuant = false;

	protected static $iblockSite = array();
	protected static $sitelist = array();
	protected static $defaultSiteId = '';
	protected static $siteCookieId = '';

	protected static $statuslist = array();
	protected static $paySystemList = array();
	protected static $deliveryList = array();
	protected static $catalogSections = array();
	protected static $catalogs = array();
	protected static $productStores = array();
	protected static $genders = array();
	protected static $personTypes = array();
	protected static $weightOptions = array();
	protected static $priceTypes = array();
	protected static $bUsePriceTypesColumns = false;
	protected static $currencies = array();
	protected static $reportCurrencyId = null;
	protected static $siteCurrencyId = '';

	public static function init()
	{
		IncludeModuleLangFile(__FILE__);

		if (!self::$fInit)
		{
			self::$fInit = true;

			self::$siteCookieId = md5('SALE_REPORT_SITE_ID');

			// Initializing list of sites.
			$result = Bitrix\Main\SiteTable::getList(array('select' => array('LID', 'DEF', 'NAME')));
			$i = 0;
			while ($row = $result->fetch())
			{
				self::$sitelist[$row['LID']] = $row['NAME'];
				if (++$i === 1) self::$defaultSiteId = $row['LID'];
				else if ($row['DEF'] === 'Y') self::$defaultSiteId = $row['LID'];
				self::$weightOptions[$row['LID']] = array(
					'unit' => COption::GetOptionString('sale', 'weight_unit', null, $row['LID']),
					'koef' => COption::GetOptionInt('sale', 'weight_koef', null, $row['LID'])
				);
			}
			unset($i, $row, $result);

			// Initializing list of statuses of orders.
			$result = Bitrix\Sale\StatusLangTable::getList(array(
				'select' => array('STATUS_ID', 'NAME'),
				'filter' => array('=LID' => LANGUAGE_ID)
			));
			while ($row = $result->fetch())
			{
				self::$statuslist[$row['STATUS_ID']] = $row['NAME'];
			}
			unset($row, $result);

			self::$genders = array('M' => GetMessage('USER_MALE'), 'F' => GetMessage('USER_FEMALE'));

			// Initializing list of person types.
			$result = Bitrix\Sale\PersonTypeTable::getList(array(
				'select' => array('ID', 'LID', 'NAME')/*,
				'filter' => array('=ACTIVE', 'Y')*/
			));
			while ($row = $result->fetch())
			{
				self::$personTypes[$row['ID']] = array('LID' => $row['LID'], 'NAME' => $row['NAME']);
			}
			unset($row, $result);

			// Initializing list of pay systems of orders.
			$result = Bitrix\Sale\PaySystemTable::getList(array(
				'select' => array('ID', 'LID', 'NAME')/*,
				'filter' => array('=ACTIVE', 'Y')*/
			));
			while ($row = $result->fetch())
			{
				self::$paySystemList[$row['ID']] = array('value' => $row['NAME'], 'site_id' => $row['LID']);
			}
			unset($row, $result);

			// Initializing list of services and methods of delivery.
			$result = Bitrix\Sale\DeliveryTable::getList(array(
				'select' => array('ID', 'NAME', 'LID')/*,
				'filter' => array('=ACTIVE', 'Y')*/
			));
			while ($row = $result->fetch())
			{
				self::$deliveryList[$row['ID']] = array('value' => $row['NAME'], 'site_id' => $row['LID']);
			}
			unset($row, $result);
			$result = Bitrix\Sale\DeliveryHandlerTable::getList(array(
				'select' => array('HID', 'NAME', 'LID')
			));
			while ($row = $result->fetch())
			{
				self::$deliveryList[$row['HID']] = array('value' => $row['NAME'], 'site_id' => $row['LID']);
			}
			unset($row, $result);

			// Obtaining table of correspondences of iblocks to sites.
			$result = Bitrix\Iblock\SiteTable::getList();
			while ($row = $result->fetch()) self::$iblockSite[$row['SITE_ID']][] = $row['IBLOCK_ID'];
			unset($row, $result);

			// Obtaining the list of iblocks which are directories and filling
			// a property $catalogSections with sections of these units.
			$ent = new CCatalog();
			$result = $ent->GetList();
			while ($ibRow = $result->Fetch())
			{
				// Obtaining list of sections of the catalog.
				self::$catalogs[] = $ibRow;
				$path = array();
				$curLevel = $prevLevel = 0;
				$sections = CIBlockSection::GetTreeList(array('=IBLOCK_ID'=>$ibRow['IBLOCK_ID']));
				$row = null;
				while($row = $sections->GetNext())
				{
					// Formation of an array of identifiers of current and parent sections.
					$curLevel = $row['DEPTH_LEVEL'];
					for ($i = 0; $i <= $prevLevel - $curLevel; $i++) array_pop($path);
					array_push($path, $row['ID']);
					$prevLevel = $curLevel;

					self::$catalogSections[$row['ID']] = array(
						'name' => ltrim(str_repeat(' . ', $curLevel).$row['NAME']),
						'path' => $path,
						'catalog' => array(
							'ID' => $ibRow['ID'],
							'NAME' => $ibRow['NAME']
						)
					);
				}
			}
			unset($ent, $ibRow, $row, $sections, $result);

			// Initialization of the list of warehouses.
			$result = Bitrix\Catalog\StoreTable::getList(array('select' => array('ID', 'TITLE')));
			while ($row = $result->fetch())
			{
				self::$productStores[$row['ID']] = $row['TITLE'];
			}
			unset($row, $result);

			// Getting currencies
			$obj = new CCurrency();
			$by = ''; $order = '';
			$result = $obj->GetList($by, $order, LANGUAGE_ID);
			while($row = $result->Fetch())
			{
				self::$currencies[$row['CURRENCY']] = array(
					'name' => $row['FULL_NAME']
				);
			}
			unset($row, $result, $obj, $by, $order);

			// Getting types of prices
			$obj = new CCatalogGroup();
			$result = $obj->GetListEx(array('SORT'), array(), false, false, array('ID', 'NAME', 'BASE', 'NAME_LANG'));
			while($row = $result->Fetch())
			{
				self::$priceTypes[$row['ID']] = array(
					'name' => (empty($row['NAME_LANG'])) ? $row['NAME'] : $row['NAME_LANG'],
					'base' => ($row['BASE'] === 'Y') ? true : false
				);
			}
			unset($row, $result, $obj);

			// Getting option, which means, it is necessary to display a fractional quantity of goods of no.
			self::$fDecimalQuant = COption::GetOptionString('sale', 'QUANTITY_FACTORIAL') == 'Y';

			self::initOwners();
		}
	}

	public static function isInitialized()
	{
		return self::$fInit;
	}

	public static function initOwners()
	{
		if (!self::$fOwnersInit)
		{
			self::$fOwnersInit = true;
			self::addOwner(CSaleReportSaleOrderHelper::getOwnerId());
			self::addOwner(CSaleReportUserHelper::getOwnerId());
			self::addOwner(CSaleReportSaleBasketHelper::getOwnerId());
			self::addOwner(CSaleReportSaleProductHelper::getOwnerId());
		}

	}

	public static function getSiteList()
	{
		return self::$sitelist;
	}

	public static function getWeightOptions()
	{
		return self::$weightOptions;
	}

	public static function getPriceTypes()
	{
		return self::$priceTypes;
	}

	public static function setSelectedPriceTypes($arSelected)
	{
		$bSelected = false;
		reset(self::$priceTypes);
		$basePriceTypeKey = key(self::$priceTypes);
		foreach (self::$priceTypes as $priceTypeKey => $priceTypeInfo)
		{
			if ($priceTypeInfo['base'] === true) $basePriceTypeKey = $priceTypeKey;
			if (in_array($priceTypeKey, $arSelected, true))
			{
				self::$priceTypes[$priceTypeKey]['selected'] = true;
				$bSelected = true;
			}
		}
		if (!$bSelected) self::$priceTypes[$basePriceTypeKey]['selected'] = true;
	}

	public static function getCurrencies()
	{
		return self::$currencies;
	}

	public static function setSelectedCurrency($currencyId)
	{
		$currenciesIds = array_keys(self::$currencies);
		if (in_array($currencyId, $currenciesIds, true))
		{
			self::$currencies[$currencyId]['selected'] = true;
			self::$reportCurrencyId = $currencyId;
		}
		else
		{
			self::$currencies[$currenciesIds[0]]['selected'] = true;
			self::$reportCurrencyId = $currenciesIds[0];
		}
	}

	public static function setSiteCurrencyId($currencyId)
	{
		self::$siteCurrencyId = $currencyId;
	}

	public static function getSiteCurrencyId()
	{
		return self::$siteCurrencyId;
	}

	public static function getReportCurrencyId()
	{
		return self::$reportCurrencyId;
	}

	public static function getSiteCookieId()
	{
		return self::$siteCookieId;
	}

	public static function getStatusList()
	{
		return self::$statuslist;
	}

	public static function getGenders()
	{
		return self::$genders;
	}

	public static function getPersonTypes()
	{
		return self::$personTypes;
	}

	public static function getDefaultSiteId()
	{
		return self::$defaultSiteId;
	}

	public static function isCatalogCorrespondsDefSite($catalogId)
	{
		$result = false;
		$siteId = self::getDefaultSiteId();
		if (in_array($catalogId, self::$iblockSite[$siteId])) $result = true;
		return $result;
	}

	public static function getDefaultSiteWeightUnits()
	{
		return self::$weightOptions[self::$defaultSiteId]['unit'];
	}

	public static function getDefaultSiteWeightDivider()
	{
		return self::$weightOptions[self::$defaultSiteId]['koef'];
	}

	public static function setDefaultSiteId($siteId)
	{
		self::$defaultSiteId = $siteId;
	}

	public static function getPaySystemList()
	{
		return self::$paySystemList;
	}

	public static function getDeliveryList()
	{
		return self::$deliveryList;
	}

	public static function prepareDeliveryId($delivery_id)
	{
		$id = null;
		if (is_numeric($delivery_id)) $id = $delivery_id;
		else
		{
			if (is_string($delivery_id))
			{
				$pos = strpos($delivery_id, ':');
				if ($pos) $id = substr($delivery_id, 0, $pos);
			}
		}
		return $id;
	}

	public static function getDeliveryByExtId($delivery_id)
	{
		$val = null;
		$id = self::prepareDeliveryId($delivery_id);
		if (!is_null($id))
		{
			$arVal = self::$deliveryList[$id];
			if ($arVal['site_id'] === '' || $arVal['site_id'] === self::$defaultSiteId)
			{
				$val = self::$deliveryList[$id]['value'];
			}
		}
		return $val;
	}

	protected static function addOwner($ownerName)
	{
		if (!in_array($ownerName, self::$owners)) self::$owners[] = $ownerName;
	}

	public static function getOwners()
	{
		IncludeModuleLangFile(__FILE__);

		return self::$owners;
	}

	public static function getCatalogSections()
	{
		return self::$catalogSections;
	}

	public static function getProductStores()
	{
		return self::$productStores;
	}

	public static function getHelperByOwner($ownerId)
	{
		return 'CSaleReport'.substr($ownerId,strlen(SALE_REPORT_OWNER_ID)+1).'Helper';
	}

	public static function getDefaultReports()
	{
		IncludeModuleLangFile(__FILE__);

		$reports = array(
			'12.0.0' => array(
				array(
					'owner' => 'sale_SaleBasket',
					'title' => GetMessage('SALE_REPORT_DEFAULT_LEFT_BASKETS'),
					'description' => GetMessage('SALE_REPORT_DEFAULT_LEFT_BASKETS_DESCR'),
					'mark_default' => 1,
					'settings' => unserialize('a:7:{s:6:"entity";s:10:"SaleBasket";s:6:"period";a:2:{s:4:"type";'.
						's:5:"month";s:5:"value";N;}s:6:"select";a:7:{i:10;a:1:{s:4:"name";s:8:"FUSER_ID";}i:1;'.
						'a:1:{s:4:"name";s:21:"FUSER.USER.SHORT_NAME";}i:2;a:1:{s:4:"name";s:16:"FUSER.USER.EMAIL";'.
						'}i:6;a:3:{s:4:"name";s:8:"QUANTITY";s:5:"alias";s:14:"xxxxxxxxxxxxxx";s:4:"aggr";s:3:"SUM";'.
						'}i:7;a:3:{s:4:"name";s:13:"SUMMARY_PRICE";s:5:"alias";s:17:"xxxxxxxxxxxxxxxxx";s:4:"aggr";'.
						's:3:"SUM";}i:4;a:3:{s:4:"name";s:8:"DATE_INS";s:5:"alias";s:13:"xxxxxxxxxxxxx";s:4:"aggr";'.
						's:3:"MIN";}i:5;a:3:{s:4:"name";s:8:"DATE_UPD";s:5:"alias";s:14:"xxxxxxxxxxxxxx";s:4:"aggr";'.
						's:3:"MAX";}}s:6:"filter";a:1:{i:0;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";'.
						's:8:"ORDER_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:1:"0";s:10:"changeable";s:1:"0";}i:1;'.
						'a:5:{s:4:"type";s:5:"field";s:4:"name";s:31:"FUSER.USER.UserGroup:USER.GROUP";s:7:"compare";'.
						's:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";'.
						'i:7;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;}'
					)
				),
				array(
					'owner' => 'sale_SaleBasket',
					'title' => GetMessage('SALE_REPORT_DEFAULT_GOODS_PROFIT'),
					'description' => GetMessage('SALE_REPORT_DEFAULT_GOODS_PROFIT_DESCR'),
					'mark_default' => 2,
					'settings' => unserialize('a:7:{s:6:"entity";s:10:"SaleBasket";s:6:"period";a:2:{s:4:"type";'.
						's:3:"all";s:5:"value";N;}s:6:"select";a:7:{i:1;a:1:{s:4:"name";s:10:"PRODUCT_ID";}i:2;'.
						'a:1:{s:4:"name";s:4:"NAME";}i:14;a:2:{s:4:"name";s:16:"PRODUCT.QUANTITY";s:5:"alias";'.
						's:14:"xxxxxxxxxxxxxx";}i:3;a:3:{s:4:"name";s:8:"ORDER_ID";s:5:"alias";'.
						's:14:"xxxxxxxxxxxxxx";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:5;a:3:{s:4:"name";s:8:"QUANTITY";'.
						's:5:"alias";s:20:"xxxxxxxxxxxxxxxxxxxx";s:4:"aggr";s:3:"SUM";}i:4;a:3:{s:4:"name";'.
						's:5:"PRICE";s:5:"alias";s:19:"xxxxxxxxxxxxxxxxxxx";s:4:"aggr";s:3:"AVG";}i:6;a:3:{s:4:"name";'.
						's:13:"SUMMARY_PRICE";s:5:"alias";s:5:"xxxxx";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;'.
						'a:6:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ORDER.PAYED";s:7:"compare";s:5:"EQUAL";'.
						's:5:"value";s:4:"true";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";'.
						's:14:"ORDER.DATE_INS";s:7:"compare";s:16:"GREATER_OR_EQUAL";s:5:"value";s:8:"-29 days";'.
						's:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";'.
						's:14:"ORDER.DATE_INS";s:7:"compare";s:13:"LESS_OR_EQUAL";s:5:"value";s:5:"1 day";'.
						's:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:4:"NAME";'.
						's:7:"compare";s:8:"CONTAINS";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:4;'.
						'a:5:{s:4:"type";s:5:"field";s:4:"name";s:33:"PRODUCT.GoodsSection:PRODUCT.SECT";'.
						's:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";'.
						's:3:"AND";}}s:4:"sort";i:6;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;}'
					)
				),
				array(
					'owner' => 'sale_SaleProduct',
					'title' => GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES'),
					'description' => GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES_DESCR'),
					'mark_default' => 3,
					'settings' => unserialize('a:7:{s:6:"entity";s:11:"SaleProduct";s:6:"period";a:2:{s:4:"type";'.
						's:3:"all";s:5:"value";N;}s:6:"select";a:5:{i:0;a:1:{s:4:"name";s:2:"ID";}i:2;a:1:{s:4:"name";'.
						's:4:"NAME";}i:1;a:2:{s:4:"name";s:8:"QUANTITY";s:5:"alias";s:7:"xxxxxxx";}i:3;'.
						'a:2:{s:4:"name";s:22:"PRICE_IN_SITE_CURRENCY";s:5:"alias";s:4:"xxxx";}i:5;a:2:{s:4:"name";'.
						's:30:"SUMMARY_PRICE_IN_SITE_CURRENCY";s:5:"alias";s:15:"xxxxxxxxxxxxxxx";}}s:6:"filter";'.
						'a:1:{i:0;a:10:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:4:"NAME";s:7:"compare";'.
						's:8:"CONTAINS";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";'.
						's:4:"name";s:51:"IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION";s:7:"compare";'.
						's:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";'.
						's:4:"name";s:8:"QUANTITY";s:7:"compare";s:16:"GREATER_OR_EQUAL";s:5:"value";s:0:"";'.
						's:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"QUANTITY";'.
						's:7:"compare";s:13:"LESS_OR_EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:4;'.
						'a:5:{s:4:"type";s:5:"field";s:4:"name";s:22:"PRICE_IN_SITE_CURRENCY";s:7:"compare";'.
						's:16:"GREATER_OR_EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:5;a:5:{s:4:"type";'.
						's:5:"field";s:4:"name";s:22:"PRICE_IN_SITE_CURRENCY";s:7:"compare";s:13:"LESS_OR_EQUAL";'.
						's:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:6;a:5:{s:4:"type";s:5:"field";s:4:"name";'.
						's:30:"SUMMARY_PRICE_IN_SITE_CURRENCY";s:7:"compare";s:16:"GREATER_OR_EQUAL";s:5:"value";'.
						's:0:"";s:10:"changeable";s:1:"1";}i:7;a:5:{s:4:"type";s:5:"field";s:4:"name";'.
						's:30:"SUMMARY_PRICE_IN_SITE_CURRENCY";s:7:"compare";s:13:"LESS_OR_EQUAL";s:5:"value";s:0:"";'.
						's:10:"changeable";s:1:"1";}i:8;a:5:{s:4:"type";s:5:"field";s:4:"name";s:6:"ACTIVE";'.
						's:7:"compare";s:5:"EQUAL";s:5:"value";s:4:"true";s:10:"changeable";s:1:"1";}s:5:"LOGIC";'.
						's:3:"AND";}}s:4:"sort";i:1;s:9:"sort_type";s:3:"ASC";s:5:"limit";N;}'
					)
				),
				array(
					'owner' => 'sale_SaleOrder',
					'title' => GetMessage('SALE_REPORT_DEFAULT_BEST_CLIENTS'),
					'description' => GetMessage('SALE_REPORT_DEFAULT_BEST_CLIENTS_DESCR'),
					'mark_default' => 4,
					'settings' => unserialize(
						'a:7:{s:6:"entity";s:9:"SaleOrder";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}'.
						's:6:"select";a:7:{i:7;a:1:{s:4:"name";s:8:"BUYER.ID";}i:6;a:1:{s:4:"name";'.
						's:16:"BUYER.SHORT_NAME";}i:0;a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:14:"xxxxxxxxxxxxxx";'.
						's:4:"aggr";s:14:"COUNT_DISTINCT";}i:2;a:3:{s:4:"name";s:14:"PRODUCTS_QUANT";s:5:"alias";'.
						's:14:"xxxxxxxxxxxxxx";s:4:"aggr";s:3:"SUM";}i:3;a:3:{s:4:"name";s:12:"DISCOUNT_ALL";'.
						's:5:"alias";s:15:"xxxxxxxxxxxxxxx";s:4:"aggr";s:3:"SUM";}i:14;a:3:{s:4:"name";'.
						's:15:"SUM_PAID_FORREP";s:5:"alias";s:13:"xxxxxxxxxxxxx";s:4:"aggr";s:3:"AVG";}i:5;'.
						'a:3:{s:4:"name";s:15:"SUM_PAID_FORREP";s:5:"alias";s:16:"xxxxxxxxxxxxxxxx";s:4:"aggr";'.
						's:3:"SUM";}}s:6:"filter";a:1:{i:0;a:4:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";'.
						's:5:"BUYER";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;'.
						'a:5:{s:4:"type";s:5:"field";s:4:"name";s:26:"BUYER.UserGroup:USER.GROUP";s:7:"compare";'.
						's:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";'.
						's:5:"field";s:4:"name";s:5:"PAYED";s:7:"compare";s:5:"EQUAL";s:5:"value";s:4:"true";'.
						's:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:5;s:9:"sort_type";'.
						's:4:"DESC";s:5:"limit";N;}'
					)
				),
				array(
					'owner' => 'sale_User',
					'title' => GetMessage('SALE_REPORT_DEFAULT_NEW_CLIENTS'),
					'description' => GetMessage('SALE_REPORT_DEFAULT_NEW_CLIENTS_DESCR'),
					'mark_default' => 5,
					'settings' => unserialize('a:7:{s:6:"entity";s:4:"User";s:6:"period";a:2:{s:4:"type";s:5:"month";'.
							's:5:"value";N;}s:6:"select";a:7:{i:11;a:1:{s:4:"name";s:14:"DATE_REG_SHORT";}i:0;'.
							'a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:18:"xxxxxxxxxxxxxxxxxx";s:4:"aggr";'.
							's:14:"COUNT_DISTINCT";}i:3;a:3:{s:4:"name";s:26:"Bitrix\Sale\Order:BUYER.ID";s:5:"alias";'.
							's:14:"xxxxxxxxxxxxxx";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:4;a:3:{s:4:"name";'.
							's:36:"Bitrix\Sale\Order:BUYER.DISCOUNT_ALL";s:5:"alias";s:15:"xxxxxxxxxxxxxxx";'.
							's:4:"aggr";s:3:"SUM";}i:9;a:3:{s:4:"name";s:29:"Bitrix\Sale\Order:BUYER.PRICE";'.
							's:5:"alias";s:13:"xxxxxxxxxxxxx";s:4:"aggr";s:3:"AVG";}i:5;a:3:{s:4:"name";'.
							's:29:"Bitrix\Sale\Order:BUYER.PRICE";s:5:"alias";s:16:"xxxxxxxxxxxxxxxx";s:4:"aggr";'.
							's:3:"SUM";}i:6;a:3:{s:4:"name";s:39:"Bitrix\Sale\Order:BUYER.SUM_PAID_FORREP";'.
							's:5:"alias";s:8:"xxxxxxxx";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:2:{i:0;'.
							'a:5:{s:4:"type";s:5:"field";s:4:"name";s:20:"UserGroup:USER.GROUP";s:7:"compare";'.
							's:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}'.
							's:4:"sort";i:11;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;}'
					)
				),
				array(
					'owner' => 'sale_SaleOrder',
					'title' => GetMessage('SALE_REPORT_DEFAULT_SALES'),
					'description' => GetMessage('SALE_REPORT_DEFAULT_SALES_DESCR'),
					'mark_default' => 6,
					'settings' => unserialize('a:7:{s:6:"entity";s:9:"SaleOrder";s:6:"period";a:2:{s:4:"type";'.
							's:5:"month";s:5:"value";N;}s:6:"select";a:8:{i:1;a:2:{s:4:"name";s:8:"DATE_INS";'.
							's:5:"alias";s:4:"xxxx";}i:0;a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:14:"xxxxxxxxxxxxxx";'.
							's:4:"aggr";s:14:"COUNT_DISTINCT";}i:13;a:3:{s:4:"name";s:14:"PRODUCTS_QUANT";s:5:"alias";'.
							's:13:"xxxxxxxxxxxxx";s:4:"aggr";s:3:"SUM";}i:2;a:3:{s:4:"name";s:9:"TAX_VALUE";'.
							's:5:"alias";s:6:"xxxxxx";s:4:"aggr";s:3:"SUM";}i:3;a:3:{s:4:"name";s:14:"PRICE_DELIVERY";'.
							's:5:"alias";s:8:"xxxxxxxx";s:4:"aggr";s:3:"SUM";}i:4;a:3:{s:4:"name";s:12:"DISCOUNT_ALL";'.
							's:5:"alias";s:6:"xxxxxx";s:4:"aggr";s:3:"SUM";}i:5;a:3:{s:4:"name";s:5:"PRICE";'.
							's:5:"alias";s:16:"xxxxxxxxxxxxxxxx";s:4:"aggr";s:3:"SUM";}i:6;a:3:{s:4:"name";'.
							's:15:"SUM_PAID_FORREP";s:5:"alias";s:8:"xxxxxxxx";s:4:"aggr";s:3:"SUM";}}s:6:"filter";'.
							'a:1:{i:0;a:2:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";'.
							's:26:"BUYER.UserGroup:USER.GROUP";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";'.
							's:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:1;s:9:"sort_type";'.
							's:4:"DESC";s:5:"limit";N;}'
					)
				),
				array(
					'owner' => 'sale_SaleBasket',
					'title' => GetMessage('SALE_REPORT_DEFAULT_MOST_EXPECTED_GOODS'),
					'description' => GetMessage('SALE_REPORT_DEFAULT_MOST_EXPECTED_GOODS_DESCR'),
					'mark_default' => 7,
					'settings' => unserialize('a:7:{s:6:"entity";s:10:"SaleBasket";s:6:"period";a:2:{s:4:"type";'.
						's:5:"month";s:5:"value";N;}s:6:"select";a:4:{i:1;a:1:{s:4:"name";s:10:"PRODUCT_ID";}i:2;'.
						'a:1:{s:4:"name";s:4:"NAME";}i:5;a:3:{s:4:"name";s:11:"N_SUBSCRIBE";s:5:"alias";'.
						's:15:"xxxxxxxxxxxxxxx";s:4:"aggr";s:3:"SUM";}i:8;a:3:{s:4:"name";s:5:"PRICE";s:5:"alias";'.
						's:15:"xxxxxxxxxxxxxxx";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:8:{i:0;a:5:{s:4:"type";'.
						's:5:"field";s:4:"name";s:9:"SUBSCRIBE";s:7:"compare";s:5:"EQUAL";s:5:"value";s:4:"true";'.
						's:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:4:"NAME";'.
						's:7:"compare";s:8:"CONTAINS";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;'.
						'a:5:{s:4:"type";s:5:"field";s:4:"name";s:33:"PRODUCT.GoodsSection:PRODUCT.SECT";'.
						's:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";'.
						's:5:"field";s:4:"name";s:8:"DATE_UPD";s:7:"compare";s:16:"GREATER_OR_EQUAL";s:5:"value";'.
						's:0:"";s:10:"changeable";s:1:"1";}i:4;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"DATE_UPD";'.
						's:7:"compare";s:13:"LESS_OR_EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:5;'.
						'a:5:{s:4:"type";s:5:"field";s:4:"name";s:5:"PRICE";s:7:"compare";s:16:"GREATER_OR_EQUAL";'.
						's:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:6;a:5:{s:4:"type";s:5:"field";s:4:"name";'.
						's:5:"PRICE";s:7:"compare";s:13:"LESS_OR_EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";'.
						'}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:5;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;}'
					)
				),
				array(
					'owner' => 'sale_SaleProduct',
					'title' => GetMessage('SALE_REPORT_DEFAULT_MOST_VIEWED_GOODS'),
					'description' => GetMessage('SALE_REPORT_DEFAULT_MOST_VIEWED_GOODS_DESCR'),
					'mark_default' => 8,
					'settings' => unserialize('a:7:{s:6:"entity";s:11:"SaleProduct";s:6:"period";a:2:{s:4:"type";'.
						's:5:"month";s:5:"value";N;}s:6:"select";a:7:{i:0;a:1:{s:4:"name";s:2:"ID";}i:1;'.
						'a:1:{s:4:"name";s:4:"NAME";}i:7;a:2:{s:4:"name";s:23:"VIEWS_IN_PERIOD_BY_SHOP";s:5:"alias";'.
						's:10:"xxxxxxxxxx";}i:8;a:2:{s:4:"name";s:24:"ORDERS_IN_PERIOD_BY_SHOP";s:5:"alias";'.
						's:18:"xxxxxxxxxxxxxxxxxx";}i:14;a:1:{s:4:"name";s:10:"CONVERSION";}i:12;a:2:{s:4:"name";'.
						's:32:"SALED_PRODUCTS_IN_PERIOD_BY_SHOP";s:5:"alias";s:7:"xxxxxxx";}i:5;a:2:{s:4:"name";'.
						's:22:"PRICE_IN_SITE_CURRENCY";s:5:"alias";s:15:"xxxxxxxxxxxxxxx";}}s:6:"filter";a:1:{i:0;'.
						'a:7:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:4:"NAME";s:7:"compare";'.
						's:8:"CONTAINS";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";'.
						's:4:"name";s:51:"IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION";s:7:"compare";'.
						's:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";'.
						's:4:"name";s:10:"CONVERSION";s:7:"compare";s:16:"GREATER_OR_EQUAL";s:5:"value";s:0:"";'.
						's:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:23:"VIEWS_IN_PERIOD_BY_SHOP";'.
						's:7:"compare";s:16:"GREATER_OR_EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:4;'.
						'a:5:{s:4:"type";s:5:"field";s:4:"name";s:22:"PRICE_IN_SITE_CURRENCY";s:7:"compare";'.
						's:16:"GREATER_OR_EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:5;a:5:{s:4:"type";'.
						's:5:"field";s:4:"name";s:6:"ACTIVE";s:7:"compare";s:5:"EQUAL";s:5:"value";s:4:"true";'.
						's:10:"changeable";s:1:"0";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:7;s:9:"sort_type";s:4:"DESC";'.
						's:5:"limit";N;}'
					)
				)
			),
			'12.5.0' => array(
				array(
					'owner' => 'sale_SaleProduct',
					'title' => GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES__12_5'),
					'description' => '',
					'mark_default' => 9,
					'settings' => unserialize('a:11:{s:6:"entity";s:19:"Bitrix\Sale\Product";s:6:"period";'.
						'a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:6:{i:2;a:4:{s:4:"name";'.
						's:56:"IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION.NAME";'.
						's:5:"alias";s:9:"xxxxxxxxx";s:4:"aggr";s:12:"GROUP_CONCAT";s:8:"grouping";'.
						'b:1;}i:8;a:2:{s:4:"name";s:15:"NAME_WITH_IDENT";s:8:"grouping";b:1;}i:12;'.
						'a:3:{s:4:"name";s:34:"ARRIVED_PRODUCTS_IN_PERIOD_BY_SHOP";s:5:"alias";s:6:"xxxxxx";'.
						's:17:"grouping_subtotal";b:1;}i:6;a:3:{s:4:"name";s:32:"SALED_PRODUCTS_IN_PERIOD_BY_SHOP";'.
						's:5:"alias";s:6:"xxxxxx";s:17:"grouping_subtotal";b:1;}i:3;a:3:{s:4:"name";'.
						's:8:"QUANTITY";s:5:"alias";s:16:"xxxxxxxx xxxxxxx";s:17:"grouping_subtotal";'.
						'b:1;}i:11;a:1:{s:4:"name";s:22:"PRICE_IN_SITE_CURRENCY";}}s:6:"filter";'.
						'a:1:{i:0;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:6:"ACTIVE";s:7:"compare";'.
						's:5:"EQUAL";s:5:"value";s:4:"true";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";'.
						's:5:"field";s:4:"name";s:51:"IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION";'.
						's:7:"compare";s:5:"EQUAL";s:5:"value";a:1:{i:0;s:0:"";}s:10:"changeable";'.
						's:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:8;s:9:"sort_type";s:3:"ASC";'.
						's:5:"limit";N;s:12:"red_neg_vals";b:1;s:13:"grouping_mode";b:1;s:5:"chart";'.
						'N;s:6:"mobile";a:1:{s:7:"enabled";b:1;}}')
				),
				array(
					'owner' => 'sale_SaleProduct',
					'title' => GetMessage('SALE_REPORT_DEFAULT_PRICE_LIST__12_5'),
					'description' => '',
					'mark_default' => 10,
					'settings' => unserialize('a:12:{s:6:"entity";s:19:"Bitrix\Sale\Product";s:6:"period";'.
						'a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:2:{i:4;a:4:{s:4:"name";'.
						's:56:"IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION.NAME";'.
						's:5:"alias";s:9:"xxxxxxxxx";s:4:"aggr";s:12:"GROUP_CONCAT";s:8:"grouping";'.
						'b:1;}i:12;a:2:{s:4:"name";s:15:"NAME_WITH_IDENT";s:8:"grouping";b:1;}}s:6:"filter";'.
						'a:1:{i:0;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:6:"ACTIVE";s:7:"compare";'.
						's:5:"EQUAL";s:5:"value";s:4:"true";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";'.
						's:5:"field";s:4:"name";s:51:"IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION";'.
						's:7:"compare";s:5:"EQUAL";s:5:"value";a:1:{i:0;s:0:"";}s:10:"changeable";'.
						's:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:12;s:9:"sort_type";s:3:"ASC";'.
						's:5:"limit";N;s:12:"red_neg_vals";b:1;s:13:"grouping_mode";b:1;s:11:"helper_spec";'.
						'a:1:{s:5:"ucspt";b:1;}s:5:"chart";N;s:6:"mobile";a:1:{s:7:"enabled";b:1;}}')
				),
				array(
					'owner' => 'sale_SaleBasket',
					'title' => GetMessage('SALE_REPORT_DEFAULT_GOODS_PROFIT__12_5'),
					'description' => '',
					'mark_default' => 11,
					'settings' => unserialize('a:11:{s:6:"entity";s:18:"Bitrix\Sale\Basket";s:6:"period";'.
						'a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:6:{i:6;a:2:{s:4:"name";'.
						's:15:"NAME_WITH_IDENT";s:8:"grouping";b:1;}i:7;a:4:{s:4:"name";s:8:"QUANTITY";'.
						's:5:"alias";s:10:"xxxxxxxxxx";s:4:"aggr";s:3:"SUM";s:17:"grouping_subtotal";'.
						'b:1;}i:5;a:4:{s:4:"name";s:13:"SUMMARY_PRICE";s:5:"alias";s:16:"xxxxxxxxx xxxxxx";'.
						's:4:"aggr";s:3:"SUM";s:17:"grouping_subtotal";b:1;}i:9;a:4:{s:4:"name";'.
						's:24:"SUMMARY_PURCHASING_PRICE";s:5:"alias";s:13:"xxxxxxxxxxxxx";s:4:"aggr";s:3:"SUM";'.
						's:17:"grouping_subtotal";b:1;}i:10;a:4:{s:4:"name";s:12:"GROSS_PROFIT";s:5:"alias";'.
						's:15:"xxxxxxx xxxxxxx";s:4:"aggr";s:3:"SUM";s:17:"grouping_subtotal";b:1;}i:11;'.
						'a:4:{s:4:"name";s:13:"PROFITABILITY";s:5:"alias";s:18:"xxxxxxxxxxxxxx (%)";s:4:"aggr";'.
						's:3:"AVG";s:17:"grouping_subtotal";b:1;}}s:6:"filter";a:1:{i:0;a:4:{i:0;a:5:{s:4:"type";'.
						's:5:"field";s:4:"name";s:11:"ORDER.PAYED";s:7:"compare";s:5:"EQUAL";s:5:"value";'.
						's:4:"true";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";'.
						's:4:"NAME";s:7:"compare";s:8:"CONTAINS";s:5:"value";s:0:"";s:10:"changeable";'.
						's:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:33:"PRODUCT.GoodsSection:PRODUCT.SECT";'.
						's:7:"compare";s:5:"EQUAL";s:5:"value";a:1:{i:0;s:0:"";}s:10:"changeable";'.
						's:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:6;s:9:"sort_type";s:3:"ASC";'.
						's:5:"limit";N;s:12:"red_neg_vals";b:1;s:13:"grouping_mode";b:1;s:5:"chart";'.
						'a:4:{s:7:"display";b:1;s:4:"type";s:3:"pie";s:8:"x_column";i:6;s:9:"y_columns";'.
						'a:1:{i:0;i:10;}}s:6:"mobile";a:1:{s:7:"enabled";b:1;}}')
				),
				array(
					'owner' => 'sale_SaleBasket',
					'title' => GetMessage('SALE_REPORT_DEFAULT_CLIENTS_PROFIT__12_5'),
					'description' => '',
					'mark_default' => 12,
					'settings' => unserialize('a:11:{s:6:"entity";s:18:"Bitrix\Sale\Basket";s:6:"period";'.
						'a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:7:{i:9;a:2:{s:4:"name";'.
						's:21:"FUSER.USER.SHORT_NAME";s:8:"grouping";b:1;}i:6;a:2:{s:4:"name";s:15:"NAME_WITH_IDENT";'.
						's:8:"grouping";b:1;}i:7;a:4:{s:4:"name";s:8:"QUANTITY";s:5:"alias";s:10:"xxxxxxxxxx";'.
						's:4:"aggr";s:3:"SUM";s:17:"grouping_subtotal";b:1;}i:5;a:4:{s:4:"name";s:13:"SUMMARY_PRICE";'.
						's:5:"alias";s:16:"xxxxxxxxx xxxxxx";s:4:"aggr";s:3:"SUM";s:17:"grouping_subtotal";'.
						'b:1;}i:15;a:4:{s:4:"name";s:24:"SUMMARY_PURCHASING_PRICE";s:5:"alias";s:13:"xxxxxxxxxxxxx";'.
						's:4:"aggr";s:3:"SUM";s:17:"grouping_subtotal";b:1;}i:13;a:4:{s:4:"name";'.
						's:12:"GROSS_PROFIT";s:5:"alias";s:15:"xxxxxxx xxxxxxx";s:4:"aggr";s:3:"SUM";'.
						's:17:"grouping_subtotal";b:1;}i:14;a:4:{s:4:"name";s:13:"PROFITABILITY";'.
						's:5:"alias";s:18:"xxxxxxxxxxxxxx (%)";s:4:"aggr";s:3:"AVG";s:17:"grouping_subtotal";'.
						'b:1;}}s:6:"filter";a:1:{i:0;a:6:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";'.
						's:11:"ORDER.PAYED";s:7:"compare";s:5:"EQUAL";s:5:"value";s:4:"true";s:10:"changeable";'.
						's:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:4:"NAME";s:7:"compare";'.
						's:8:"CONTAINS";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";'.
						's:5:"field";s:4:"name";s:33:"PRODUCT.GoodsSection:PRODUCT.SECT";s:7:"compare";'.
						's:5:"EQUAL";s:5:"value";a:1:{i:0;s:0:"";}s:10:"changeable";s:1:"1";}i:3;'.
						'a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"FUSER.USER";s:7:"compare";'.
						's:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:4;a:5:{s:4:"type";'.
						's:5:"field";s:4:"name";s:31:"FUSER.USER.UserGroup:USER.GROUP";s:7:"compare";'.
						's:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";'.
						'}}s:4:"sort";i:6;s:9:"sort_type";s:3:"ASC";s:5:"limit";N;s:12:"red_neg_vals";'.
						'b:1;s:13:"grouping_mode";b:1;s:5:"chart";N;s:6:"mobile";a:1:{s:7:"enabled";b:1;}}')
				),
				array(
					'owner' => 'sale_SaleProduct',
					'title' => GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES_MOB__12_5'),
					'description' => '',
					'mark_default' => 13,
					'settings' => unserialize('a:11:{s:6:"entity";s:19:"Bitrix\Sale\Product";s:6:"period";'.
						'a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:3:{i:2;a:4:{s:4:"name";'.
						's:56:"IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION.NAME";s:5:"alias";'.
						's:9:"xxxxxxxxx";s:4:"aggr";s:12:"GROUP_CONCAT";s:8:"grouping";b:1;}i:8;a:2:{s:4:"name";'.
						's:15:"NAME_WITH_IDENT";s:8:"grouping";b:1;}i:3;a:3:{s:4:"name";s:8:"QUANTITY";'.
						's:5:"alias";s:16:"xxxxxxxx xxxxxxx";s:17:"grouping_subtotal";b:1;}}s:6:"filter";'.
						'a:1:{i:0;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:6:"ACTIVE";s:7:"compare";'.
						's:5:"EQUAL";s:5:"value";s:4:"true";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";'.
						's:5:"field";s:4:"name";s:51:"IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION";'.
						's:7:"compare";s:5:"EQUAL";s:5:"value";a:1:{i:0;s:0:"";}s:10:"changeable";'.
						's:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:8;s:9:"sort_type";s:3:"ASC";'.
						's:5:"limit";N;s:12:"red_neg_vals";b:1;s:13:"grouping_mode";b:1;s:5:"chart";'.
						'N;s:6:"mobile";a:1:{s:7:"enabled";b:1;}}')
				),
				array(
					'owner' => 'sale_SaleProduct',
					'title' => GetMessage('SALE_REPORT_DEFAULT_PRICE_LIST_MOB__12_5'),
					'description' => '',
					'mark_default' => 14,
					'settings' => unserialize('a:11:{s:6:"entity";s:19:"Bitrix\Sale\Product";s:6:"period";'.
						'a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:3:{i:4;a:4:{s:4:"name";'.
						's:56:"IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION.NAME";s:5:"alias";'.
						's:9:"xxxxxxxxx";s:4:"aggr";s:12:"GROUP_CONCAT";s:8:"grouping";b:1;}i:12;'.
						'a:2:{s:4:"name";s:15:"NAME_WITH_IDENT";s:8:"grouping";b:1;}i:13;a:1:{s:4:"name";'.
						's:22:"PRICE_IN_SITE_CURRENCY";}}s:6:"filter";a:1:{i:0;a:3:{i:0;a:5:{s:4:"type";'.
						's:5:"field";s:4:"name";s:6:"ACTIVE";s:7:"compare";s:5:"EQUAL";s:5:"value";'.
						's:4:"true";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";'.
						's:51:"IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION";s:7:"compare";'.
						's:5:"EQUAL";s:5:"value";a:1:{i:0;s:0:"";}s:10:"changeable";s:1:"1";}s:5:"LOGIC";'.
						's:3:"AND";}}s:4:"sort";i:12;s:9:"sort_type";s:3:"ASC";s:5:"limit";N;s:12:"red_neg_vals";'.
						'b:1;s:13:"grouping_mode";b:1;s:5:"chart";N;s:6:"mobile";a:1:{s:7:"enabled";b:1;}}')
				)
			),
			'12.5.1' => array(
				array(
					'owner' => 'sale_SaleProduct',
					'title' => GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES_BY_STORE__12_5_1'),
					'description' => '',
					'mark_default' => 15,
					'settings' => unserialize('a:11:{s:6:"entity";s:19:"Bitrix\Sale\Product";s:6:"period";'.
						'a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:7:{i:16;a:2:{s:4:"name";'.
						's:37:"StoreProduct:SALE_PRODUCT.STORE.TITLE";s:8:"grouping";b:1;}i:2;a:4:{s:4:"name";'.
						's:56:"IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION.NAME";s:5:"alias";'.
						's:9:"xxxxxxxxx";s:4:"aggr";s:12:"GROUP_CONCAT";s:8:"grouping";b:1;}i:8;'.
						'a:2:{s:4:"name";s:15:"NAME_WITH_IDENT";s:8:"grouping";b:1;}i:25;a:3:{s:4:"name";'.
						's:35:"ARRIVED_PRODUCTS_IN_PERIOD_BY_STORE";s:5:"alias";'.
						's:6:"xxxxxx";s:17:"grouping_subtotal";b:1;}i:26;a:3:{s:4:"name";'.
						's:35:"EXPENSE_PRODUCTS_IN_PERIOD_BY_STORE";'.
						's:5:"alias";s:6:"xxxxxx";s:17:"grouping_subtotal";b:1;}i:23;a:3:{s:4:"name";'.
						's:32:"StoreProduct:SALE_PRODUCT.AMOUNT";s:5:"alias";s:16:"xxxxxxxx xxxxxxx";'.
						's:17:"grouping_subtotal";b:1;}i:11;a:1:{s:4:"name";s:22:"PRICE_IN_SITE_CURRENCY";'.
						'}}s:6:"filter";a:1:{i:0;a:4:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";'.
						's:6:"ACTIVE";s:7:"compare";s:5:"EQUAL";s:5:"value";s:4:"true";s:10:"changeable";'.
						's:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:25:"StoreProduct:SALE_PRODUCT";'.
						's:7:"compare";s:5:"EQUAL";s:5:"value";a:1:{i:0;s:0:"";}s:10:"changeable";'.
						's:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";'.
						's:51:"IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION";'.
						's:7:"compare";s:5:"EQUAL";s:5:"value";a:1:{i:0;s:0:"";}s:10:"changeable";'.
						's:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:8;s:9:"sort_type";s:3:"ASC";'.
						's:5:"limit";N;s:12:"red_neg_vals";b:1;s:13:"grouping_mode";b:1;s:5:"chart";'.
						'N;s:6:"mobile";a:1:{s:7:"enabled";b:1;}}')
				)
			)
		);

		foreach ($reports as $version => &$vreports)
		{
			foreach ($vreports as $num => &$report)
			{
				if ($version === '12.0.0' && $report['mark_default'] === 1)
				{
					$report['settings']['select'][4]['alias'] = GetMessage('SALE_REPORT_DEFAULT_LEFT_BASKETS_ALIAS_4');
					$report['settings']['select'][5]['alias'] = GetMessage('SALE_REPORT_DEFAULT_LEFT_BASKETS_ALIAS_5');
					$report['settings']['select'][6]['alias'] = GetMessage('SALE_REPORT_DEFAULT_LEFT_BASKETS_ALIAS_6');
					$report['settings']['select'][7]['alias'] = GetMessage('SALE_REPORT_DEFAULT_LEFT_BASKETS_ALIAS_7');
				}
				if ($version === '12.0.0' && $report['mark_default'] === 2)
				{
					$report['settings']['select'][3]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_PROFIT_ALIAS_3');
					$report['settings']['select'][4]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_PROFIT_ALIAS_4');
					$report['settings']['select'][5]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_PROFIT_ALIAS_5');
					$report['settings']['select'][6]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_PROFIT_ALIAS_6');
					$report['settings']['select'][14]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_PROFIT_ALIAS_14');
				}
				if ($version === '12.0.0' && $report['mark_default'] === 3)
				{
					$report['settings']['select'][1]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES_ALIAS_1');
					$report['settings']['select'][3]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES_ALIAS_3');
					$report['settings']['select'][5]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES_ALIAS_5');
				}
				if ($version === '12.0.0' && $report['mark_default'] === 4)
				{
					$report['settings']['select'][0]['alias'] = GetMessage('SALE_REPORT_DEFAULT_BEST_CLIENTS_ALIAS_0');
					$report['settings']['select'][2]['alias'] = GetMessage('SALE_REPORT_DEFAULT_BEST_CLIENTS_ALIAS_2');
					$report['settings']['select'][3]['alias'] = GetMessage('SALE_REPORT_DEFAULT_BEST_CLIENTS_ALIAS_3');
					$report['settings']['select'][5]['alias'] = GetMessage('SALE_REPORT_DEFAULT_BEST_CLIENTS_ALIAS_5');
					$report['settings']['select'][14]['alias'] = GetMessage('SALE_REPORT_DEFAULT_BEST_CLIENTS_ALIAS_14');
				}
				if ($version === '12.0.0' && $report['mark_default'] === 5)
				{
					$report['settings']['select'][0]['alias'] = GetMessage('SALE_REPORT_DEFAULT_NEW_CLIENTS_ALIAS_0');
					$report['settings']['select'][3]['alias'] = GetMessage('SALE_REPORT_DEFAULT_NEW_CLIENTS_ALIAS_3');
					$report['settings']['select'][4]['alias'] = GetMessage('SALE_REPORT_DEFAULT_NEW_CLIENTS_ALIAS_4');
					$report['settings']['select'][5]['alias'] = GetMessage('SALE_REPORT_DEFAULT_NEW_CLIENTS_ALIAS_5');
					$report['settings']['select'][6]['alias'] = GetMessage('SALE_REPORT_DEFAULT_NEW_CLIENTS_ALIAS_6');
					$report['settings']['select'][9]['alias'] = GetMessage('SALE_REPORT_DEFAULT_NEW_CLIENTS_ALIAS_9');
				}
				if ($version === '12.0.0' && $report['mark_default'] === 6)
				{
					$report['settings']['select'][0]['alias'] = GetMessage('SALE_REPORT_DEFAULT_SALES_ALIAS_0');
					$report['settings']['select'][1]['alias'] = GetMessage('SALE_REPORT_DEFAULT_SALES_ALIAS_1');
					$report['settings']['select'][2]['alias'] = GetMessage('SALE_REPORT_DEFAULT_SALES_ALIAS_2');
					$report['settings']['select'][3]['alias'] = GetMessage('SALE_REPORT_DEFAULT_SALES_ALIAS_3');
					$report['settings']['select'][4]['alias'] = GetMessage('SALE_REPORT_DEFAULT_SALES_ALIAS_4');
					$report['settings']['select'][5]['alias'] = GetMessage('SALE_REPORT_DEFAULT_SALES_ALIAS_5');
					$report['settings']['select'][6]['alias'] = GetMessage('SALE_REPORT_DEFAULT_SALES_ALIAS_6');
					$report['settings']['select'][13]['alias'] = GetMessage('SALE_REPORT_DEFAULT_SALES_ALIAS_13');
				}
				if ($version === '12.0.0' && $report['mark_default'] === 7)
				{
					$report['settings']['select'][5]['alias'] = GetMessage('SALE_REPORT_DEFAULT_MOST_EXPECTED_GOODS_ALIAS_5');
					$report['settings']['select'][8]['alias'] = GetMessage('SALE_REPORT_DEFAULT_MOST_EXPECTED_GOODS_ALIAS_8');
				}
				if ($version === '12.0.0' && $report['mark_default'] === 8)
				{
					$report['settings']['select'][5]['alias'] = GetMessage('SALE_REPORT_DEFAULT_MOST_VIEWED_GOODS_ALIAS_5');
					$report['settings']['select'][7]['alias'] = GetMessage('SALE_REPORT_DEFAULT_MOST_VIEWED_GOODS_ALIAS_7');
					$report['settings']['select'][8]['alias'] = GetMessage('SALE_REPORT_DEFAULT_MOST_VIEWED_GOODS_ALIAS_8');
					$report['settings']['select'][12]['alias'] = GetMessage('SALE_REPORT_DEFAULT_MOST_VIEWED_GOODS_ALIAS_12');
				}
				if ($version === '12.5.0' && $report['mark_default'] === 9)
				{
					$report['settings']['select'][2]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES__12_5_ALIAS_2');
					$report['settings']['select'][12]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES__12_5_ALIAS_12');
					$report['settings']['select'][6]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES__12_5_ALIAS_6');
					$report['settings']['select'][3]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES__12_5_ALIAS_3');
				}
				if ($version === '12.5.0' && $report['mark_default'] === 10)
				{
					$report['settings']['select'][4]['alias'] = GetMessage('SALE_REPORT_DEFAULT_PRICE_LIST__12_5_ALIAS_4');
				}
				if ($version === '12.5.0' && $report['mark_default'] === 11)
				{
					$report['settings']['select'][7]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_PROFIT__12_5_ALIAS_7');
					$report['settings']['select'][5]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_PROFIT__12_5_ALIAS_5');
					$report['settings']['select'][9]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_PROFIT__12_5_ALIAS_9');
					$report['settings']['select'][10]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_PROFIT__12_5_ALIAS_10');
					$report['settings']['select'][11]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_PROFIT__12_5_ALIAS_11');
				}
				if ($version === '12.5.0' && $report['mark_default'] === 12)
				{
					$report['settings']['select'][7]['alias'] = GetMessage('SALE_REPORT_DEFAULT_CLIENTS_PROFIT__12_5_ALIAS_7');
					$report['settings']['select'][5]['alias'] = GetMessage('SALE_REPORT_DEFAULT_CLIENTS_PROFIT__12_5_ALIAS_5');
					$report['settings']['select'][15]['alias'] = GetMessage('SALE_REPORT_DEFAULT_CLIENTS_PROFIT__12_5_ALIAS_15');
					$report['settings']['select'][13]['alias'] = GetMessage('SALE_REPORT_DEFAULT_CLIENTS_PROFIT__12_5_ALIAS_13');
					$report['settings']['select'][14]['alias'] = GetMessage('SALE_REPORT_DEFAULT_CLIENTS_PROFIT__12_5_ALIAS_14');
				}
				if ($version === '12.5.0' && $report['mark_default'] === 13)
				{
					$report['settings']['select'][2]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES_MOB__12_5_ALIAS_2');
					$report['settings']['select'][3]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES_MOB__12_5_ALIAS_3');
				}
				if ($version === '12.5.0' && $report['mark_default'] === 14)
				{
					$report['settings']['select'][4]['alias'] = GetMessage('SALE_REPORT_DEFAULT_PRICE_LIST__12_5_ALIAS_4');
				}
				if ($version === '12.5.1' && $report['mark_default'] === 15)
				{
					$report['settings']['select'][2]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES_BY_STORE__12_5_1_ALIAS_2');
					$report['settings']['select'][23]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES_BY_STORE__12_5_1_ALIAS_23');
					$report['settings']['select'][25]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES_BY_STORE__12_5_1_ALIAS_25');
					$report['settings']['select'][26]['alias'] = GetMessage('SALE_REPORT_DEFAULT_GOODS_INVENTORIES_BY_STORE__12_5_1_ALIAS_26');
				}

				// remove reports, which not work in MSSQL
				global $DBType;
				if (ToUpper($DBType) === 'MSSQL')
				{
					if (
						($version === '12.0.0' && in_array($report['mark_default'], array(4, 5, 6, 7, 11, 12)))
						||
						($version === '12.5.0' && in_array($report['mark_default'], array(11, 12)))
					)
					{
						unset($vreports[$num]);
					}
				}

				// remove old reports
				if (
					(ToUpper($DBType) !== 'MSSQL' && $version === '12.0.0' && in_array($report['mark_default'], array(2, 3)))
					||
					(ToUpper($DBType) === 'MSSQL' && $version === '12.0.0' && in_array($report['mark_default'], array(3)))
				)
				{
					unset($vreports[$num]);
				}

			}
		}

		return $reports;
	}

	public static function getCurrentVersion()
	{
		$arModuleVersion = array();
		include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/install/version.php");
		return $arModuleVersion['VERSION'];
	}

	public static function getFirstVersion()
	{
		return '12.0.0';
	}

	public static function getAjaxResponse($arRequestData)
	{
		$siteId = self::getDefaultSiteId();
		$arResponseData = array();
		$arbFilterTypeFill = array();
		foreach (array_unique($arRequestData) as $filterType)
		{
			if (empty($arbFilterTypeFill[$filterType]))
			{
				switch ($filterType)
				{
					case 'PersonType':
						$arResponseData[$filterType][] = array(
							'value' => '',
							'text' => GetMessage('REPORT_IGNORE_FILTER_VALUE')
						);
						foreach(self::getPersonTypes() as $kID => $v)
						{
							if ($v['LID'] === $siteId)
							{
								$arResponseData[$filterType][] = array('value' => $kID, 'text' => $v['NAME']);
							}
						}
						break;
					case 'PaySystem':
						$arResponseData[$filterType][] = array(
							'value' => '',
							'text' => GetMessage('REPORT_IGNORE_FILTER_VALUE')
						);
						foreach(self::getPaySystemList() as $kID => $val)
						{
							if ($val['site_id'] === '' || $val['site_id'] === $siteId)
							{
								$arResponseData[$filterType][] = array('value' => $kID, 'text' => $val['value']);
							}
						}
						break;
					case 'Delivery':
						$arResponseData[$filterType][] = array(
							'value' => '',
							'text' => GetMessage('REPORT_IGNORE_FILTER_VALUE')
						);
						foreach(self::getDeliveryList() as $kID => $val)
						{
							if ($val['site_id'] === '' || $val['site_id'] === $siteId)
							{
								$arResponseData[$filterType][] = array('value' => $kID, 'text' => $val['value']);
							}
						}
						break;
					case 'Section':
						$arResponseData[$filterType][] = array(
							'value' => '',
							'text' => GetMessage('REPORT_IGNORE_FILTER_VALUE')
						);
						$prevCatalog = -1;
						foreach(self::getCatalogSections() as $kSectionID => $vSection)
						{
							if (self::isCatalogCorrespondsDefSite($vSection['catalog']['ID']))
							{
								// Inserting catalogs headers in list of sections of goods.
								if ($vSection['catalog']['ID'] != $prevCatalog)
								{
									$arResponseData[$filterType][] = array(
										'value' => 'c'.$vSection['catalog']['ID'],
										'text' => '- '.GetMessage('SALE_REPORT_CONSTRUCT_CATALOG_NAME_LABEL').
											': '.$vSection['catalog']['NAME']
									);
								}
								$prevCatalog = $vSection['catalog']['ID'];
								$arResponseData[$filterType][] = array('value' => $kSectionID, 'text' => '&nbsp;'.$vSection['name']);
							}
						}
						break;
				}
			}
			$arbFilterTypeFill[$filterType] = true;
		}
		return $arResponseData;
	}

	public static function getSectionsSelectHTMLOptions()
	{
		$html = '';
		$prevCatalog = -1;
		$html .= '<option value="">'.GetMessage('REPORT_IGNORE_FILTER_VALUE').'</option>';
		foreach(self::getCatalogSections() as $kSectionID => $vSection)
		{
			if (self::isCatalogCorrespondsDefSite($vSection['catalog']['ID']))
			{
				// Inserting catalogs headers in list of sections of goods.
				if ($vSection['catalog']['ID'] != $prevCatalog)
				{
					$html .= '<option value="c'.htmlspecialcharsbx($vSection['catalog']['ID']).'">- '.GetMessage('SALE_REPORT_CONSTRUCT_CATALOG_NAME_LABEL').
						': '.htmlspecialcharsbx($vSection['catalog']['NAME']).'</option>';
				}
				$prevCatalog = $vSection['catalog']['ID'];
			$html .= '<option value="'.htmlspecialcharsbx($kSectionID).'">&nbsp;'.htmlspecialcharsbx($vSection['name']).'</option>';
			}
		}
		return $html;
	}
	
	public static function calculateInReportCurrency($value)
	{
		$res = $value;
		if (self::$reportCurrencyId != self::$siteCurrencyId)
		{
			$res = \CCurrencyRates::ConvertCurrency($value, self::$siteCurrencyId, self::$reportCurrencyId);
		}

		return $res;
	}
}

class CSaleReportSaleOrderHelper extends CBaseSaleReportHelper
{

	private static $monetaryFields = array(
		'TAX_VALUE',
		'PRICE_DELIVERY',
		'DISCOUNT_ALL',
		'PRICE',
		'SUM_PAID_FORREP'
	);

	private static $goodsQuantityFields = array(
		'PRODUCTS_QUANT'
	);

	private static $withoutTotalFields = array(
		'PERSON_TYPE_ID'
	);

	private static $userNotAuthNameFields = array();

	public static function getEntityName()
	{
		return 'Bitrix\Sale\Order';
	}

	public static function getOwnerId()
	{
		return SALE_REPORT_OWNER_ID.'_SaleOrder';
	}

	public static function getColumnList()
	{
		IncludeModuleLangFile(__FILE__);

		return array(
			'ID',
			'DATE_INS',
			'DATE_UPDATE_SHORT',
			'STATUS' => array(
				'STATUS_ID',
				'NAME',
				'DESCRIPTION'
			),
			'EMP_STATUS_BY' => array(
				'ID',
				'NAME',
				'LAST_NAME',
				'SHORT_NAME',
				'EMAIL',
				'PERSONAL_PHONE'
			),
			'DATE_STATUS_SHORT',
			'ALLOW_DELIVERY',
			'EMP_ALLOW_DELIVERY_BY' => array(
				'ID',
				'NAME',
				'LAST_NAME',
				'SHORT_NAME',
				'EMAIL',
				'PERSONAL_PHONE'
			),
			'DATE_ALLOW_DELIVERY_SHORT',
			'DELIVERY_ID',
			'DELIVERY_DOC_NUM',
			'DELIVERY_DOC_DATE_SHORT',
			'PRICE_DELIVERY',
			'PAYED',
			'EMP_PAYED_BY' => array(
				'ID',
				'NAME',
				'LAST_NAME',
				'SHORT_NAME',
				'EMAIL',
				'PERSONAL_PHONE'
			),
			'DEDUCTED',
			'PAY_SYSTEM' => array(
				'ID',
				'NAME',
				'DESCRIPTION',
				'ACTIVE',
				'CURRENCY'
			),
			'PAY_VOUCHER_NUM',
			'PAY_VOUCHER_DATE_SHORT',
			'SUM_PAID_FORREP',
			'CANCELED',
			'EMP_CANCELED_BY' => array(
				'ID',
				'NAME',
				'LAST_NAME',
				'SHORT_NAME',
				'EMAIL',
				'PERSONAL_PHONE'
			),
			'DATE_CANCELED_SHORT',
			'REASON_CANCELED',
			'PRODUCTS_QUANT',
			'TAX_VALUE',
			'PRICE',
			'DISCOUNT_ALL',
			/*'LID',*/
			'PERSON_TYPE_ID',
			'BUYER' => array(
				'ID',
				'NAME',
				'LAST_NAME',
				'SHORT_NAME',
				'EMAIL',
				'PERSONAL_PHONE',
				'DATE_REG_SHORT',
				'UserGroup:USER.GROUP' => array(
					'ID',
					'NAME'
				)
			)
		);
	}

	public static function getDefaultColumns()
	{
		return array(
			array('name' => 'ID'),
			array('name' => 'DATE_INS')
		);
	}

	public static function getCalcVariations()
	{
		return array_merge(parent::getCalcVariations(), array(
			'BUYER.UserGroup:USER.GROUP.ID' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			),
			'BUYER.UserGroup:USER.GROUP.NAME' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			)
		));
	}

	public static function getCompareVariations()
	{
		return array_merge(parent::getCompareVariations(), array(
			'LID' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'PERSON_TYPE_ID' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'STATUS' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'PAY_SYSTEM' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'DELIVERY_ID' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'BUYER.UserGroup:USER.GROUP' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'\Bitrix\Main\User' => array(
				'EQUAL'
			),
			'\Bitrix\Main\Group' => array(
				'EQUAL'
			)
		));
	}

	public static function getPeriodFilter($date_from, $date_to)
	{
		global $DB;

		$filter = array('LOGIC' => 'AND');

		if(!is_null($date_to))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'<=DATE_INS' => $date_to,
				'=DATE_INS' => null
			);
		}

		if(!is_null($date_from))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'>=DATE_INS' => $date_from,
				'=DATE_INS' => null
			);
		}

		// Site filter
		$filter[] = array(
			'=LID' => $DB->ForSql(self::getDefaultSiteId())
		);

		return $filter;
	}

	private static function rewriteDeliveryFilter(&$filter)
	{
		foreach ($filter as $k => &$v)
		{
			if ($k === 'LOGIC') continue;
			if (is_array($v)) self::rewriteDeliveryFilter($v);
			else if ($k === '=DELIVERY_ID')
			{
				if (!is_numeric($v))
				{
					$filter['=%DELIVERY_ID'] = $v.':%';
					unset($filter[$k]);
				}
			}
			else if ($k === '!DELIVERY_ID')
			{
				if (!is_numeric($v))
				{
					$filter[] = array(
						'LOGIC' => 'OR',
						'!=%DELIVERY_ID' => $v.':%',
						'=DELIVERY_ID' => null
					);
					unset($filter[$k]);
				}
			}
		}
	}

	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime)
	{
		self::rewriteDeliveryFilter($filter);
	}

	public static function fillFilterReferenceColumn(&$filterElement, Entity\ReferenceField $field)
	{
		if ($field->getRefEntityName() == '\Bitrix\Main\User')
		{
			// USER
			if ($filterElement['value'])
			{
				$res = CUser::GetByID($filterElement['value']);
				$user = $res->fetch();

				if ($user)
				{
					$username = CUser::FormatName(CSite::GetNameFormat(null, self::getDefaultSiteId()), $user, true, false);
					$filterElement['value'] = array('id' => $user['ID'], 'name' => $username);
				}
				else
				{
					$filterElement['value'] = array('id' => $filterElement['value'], 'name' => GetMessage('REPORT_USER_NOT_FOUND'));
				}
			}
			else
			{
				$filterElement['value'] = array('id' => '');
			}
		}
		else if ($field->getRefEntityName() == '\Bitrix\Main\Group')
		{
			// GROUP
			if ($filterElement['value'])
			{
				$res = CGroup::GetByID($filterElement['value']);
				$group = $res->fetch();

				if ($group)
				{
					$filterElement['value'] = array('id' => $group['ID'], 'name' => $group['NAME']);
				}
				else
				{
					$filterElement['value'] = array('id' => $filterElement['value'], 'name' => GetMessage('SALE_REPORT_GROUP_NOT_FOUND'));
				}
			}
			else
			{
				$filterElement['value'] = array('id' => '');
			}
		}
	}

	/* remove it when PHP 5.3 available */
	public static function formatResults(&$rows, &$columnInfo, $total)
	{
		foreach ($rows as &$row)
		{
			foreach ($row as $k => &$v)
			{
				if (!array_key_exists($k, $columnInfo))
				{
					continue;
				}

				$cInfo = &$columnInfo[$k];

				if (is_array($v))
				{
					foreach ($v as &$subv)
					{
						self::formatResultValue($k, $subv, $row, $cInfo, $total);
					}
				}
				else
				{
					self::formatResultValue($k, $v, $row, $cInfo, $total);
				}
			}
		}

		unset($row, $v, $subv);
	}
	/* \remove it */

	public static function formatResultValue($k, &$v, &$row, &$cInfo, $total)
	{
		/** @var Bitrix\Main\Entity\Field[] $cInfo */
		if ($cInfo['field']->getDataType() !== 'float' )    // skip base rounding
		{
			parent::formatResultValue($k, $v, $row, $cInfo, $total);
		}

		if ($k === 'PERSON_TYPE_ID')
		{
			$v = self::$personTypes[$v]['NAME'];
		}
		else if ($k === 'LID')
		{
			$v = self::$sitelist[$v];
		}
		else if ($k === 'DELIVERY_ID')
		{
			$id = self::prepareDeliveryId($v);
			if (!is_null($id))
			{
				if (is_numeric($id))
				{
					$row['__HREF_'.$k] = '/bitrix/admin/sale_delivery_edit.php?ID='.$id.'&lang='.LANGUAGE_ID.'&filter=Y&set_filter=Y';
				}
				else
				{
					$row['__HREF_'.$k] = '/bitrix/admin/sale_delivery_handler_edit.php?SID='.$id.'&lang='.LANGUAGE_ID;
				}
			}
			$v = self::getDeliveryByExtId($v);
		}
		// Removing the link from the email field if is empty.
		else if ($k === 'SALE_ORDER_BUYER_EMAIL')
		{
			if (is_null($v) || empty($v) || $v == '&nbsp;') unset($row['__HREF_'.$k]);
		}

		// Inserting zero in numerical fields with null value.
		if (empty($v))
		{
			if (in_array($cInfo['field']->getDataType(), array('integer', 'float')))
			{
				$v = 0;
			}
			//else $v = '&nbsp;';
		}

		// Formatting of monetary fields.
		foreach (self::$monetaryFields as $monField)
		{
			if (preg_match('/'.$monField.'$/', $k))
			{
				$v = self::calculateInReportCurrency($v);
				$v = number_format($v, 2, '.', ' ');
				break;
			}
		}

		// Formatting fields with goods quantity.
		foreach (self::$goodsQuantityFields as $qField)
		{
			if (preg_match('/'.$qField.'$/', $k))
			{
				if (!empty($v) && !is_null($v) && $v != '&nbsp;')
				{
					if (self::$fDecimalQuant) $v = sprintf('%.3f', round($v,3));
					else $v = sprintf('%d', round($v,0));
				}
				break;
			}
		}

		// Formatting short name fields of buyers.
		foreach (self::$userNotAuthNameFields as $uField)
		{
			if (preg_match('/'.$uField.'$/', $k))
			{
				if (empty($v) || is_null($v) || $v == '&nbsp;')
				{
					$v = GetMessage('SALE_REPORT_USER_NOT_AUTH');
				}
				break;
			}
		}
	}

	public static function formatResultGroupingTotal($params)
	{
		$paramTotal = array('TOTAL_'.$params['k'] => &$params['v']);
		$viewColumns = array($params['k'] => &$params['cInfo']);
		static::formatResultsTotal($paramTotal, $viewColumns, true);
	}

	public static function formatResultsTotal(&$total, &$columnInfo, $bFormatOnly = false)
	{
		parent::formatResultsTotal($total, $columnInfo);

		foreach ($total as $k => &$v)
		{
			// Formatting of monetary fields.
			foreach (self::$monetaryFields as $monField)
			{
				if (preg_match('/'.$monField.'$/', $k))
				{
					if (!$bFormatOnly) $v = self::calculateInReportCurrency($v);
					$v = number_format($v, 2, '.', ' ');
					break;
				}
			}

			// Formatting fields with goods quantity.
			foreach (self::$goodsQuantityFields as $qField)
			{
				if (preg_match('/'.$qField.'$/', $k))
				{
					if (!empty($v) && !is_null($v) && $v != '&nbsp;')
					{
						if (self::$fDecimalQuant) $v = sprintf('%.3f', round($v,3));
						else $v = sprintf('%d', round($v,0));
					}
					break;
				}
			}

			// Formatting fields without summary calculation.
			foreach (self::$withoutTotalFields as $wtField)
			{
				if (preg_match('/TOTAL_'.$wtField.'$/', $k))
				{
					unset($total[$k]);
					break;
				}
			}
		}
	}

	public static function getDefaultElemHref($elem, $fList)
	{
		$href = '';
		if (empty($elem['aggr']))
		{
			$field = $fList[$elem['name']];

			if ($field->getEntity()->getName() == 'User')
			{
				if ($elem['name'] == 'BUYER.SHORT_NAME')
				{
					//$href = array('pattern' => '/bitrix/admin/user_edit.php?ID=#BUYER.ID#&lang='.LANG);
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#BUYER.ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'BUYER.EMAIL')
				{
					$href = array('pattern' => 'mailto:#BUYER.EMAIL#');
				}
				else if ($elem['name'] == 'EMP_PAYED_BY.SHORT_NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#EMP_PAYED_BY.ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'EMP_PAYED_BY.EMAIL')
				{
					$href = array('pattern' => 'mailto:#EMP_PAYED_BY.EMAIL#');
				}
				else if ($elem['name'] == 'EMP_CANCELED_BY.SHORT_NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#EMP_CANCELED_BY.ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'EMP_CANCELED_BY.EMAIL')
				{
					$href = array('pattern' => 'mailto:#EMP_CANCELED_BY.EMAIL#');
				}
				else if ($elem['name'] == 'EMP_STATUS_BY.SHORT_NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#EMP_STATUS_BY.ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'EMP_STATUS_BY.EMAIL')
				{
					$href = array('pattern' => 'mailto:#EMP_STATUS_BY.EMAIL#');
				}
				else if ($elem['name'] == 'EMP_ALLOW_DELIVERY_BY.SHORT_NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#EMP_ALLOW_DELIVERY_BY.ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'EMP_ALLOW_DELIVERY_BY.EMAIL')
				{
					$href = array('pattern' => 'mailto:#EMP_ALLOW_DELIVERY_BY.EMAIL#');
				}
			}
			else if ($field->getEntity()->getName() == 'Order')
			{
				if ($elem['name'] === 'ID')
				{
					$href = array('pattern' => '/bitrix/admin/sale_order_detail.php?ID=#ID#&lang='.LANG);
				}
				else if ($elem['name'] === 'DELIVERY_ID')
				{
					$href = array('pattern' => '/404.php?DELIVERY_ID=#DELIVERY_ID#&lang='.LANG);
				}
			}
			else if ($field->getEntity()->getName() == 'PaySystem')
			{
				if ($elem['name'] == 'PAY_SYSTEM.NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_pay_system_edit.php?ID=#PAY_SYSTEM.ID#&lang='.LANG.'&filter=Y&set_filter=Y');
				}
			}
		}

		return $href;
	}
}

class CSaleReportUserHelper extends CBaseSaleReportHelper
{
	private static $monetaryFields = array(
		'DISCOUNT_ALL',
		'PRICE',
		'SUM_PAID_FORREP',
		// Order fields
		'TAX_VALUE',
		'PRICE_DELIVERY',
		'DISCOUNT_ALL',
		'PRICE',
		'SUM_PAID_FORREP'
	);

	private static $goodsQuantityFields = array(
		'SALE_ORDER_BUYER_PRODUCTS_QUANT',
		// Order fields
		'PRODUCTS_QUANT'
	);

	private static $withoutTotalFields = array(
		// Order fields
		'PERSON_TYPE_ID'
	);

	private static $userNotAuthNameFields = array();

	public static function getEntityName()
	{
		return 'Bitrix\Main\User';
	}

	public static function getOwnerId()
	{
		return SALE_REPORT_OWNER_ID.'_User';
	}

	public static function getColumnList()
	{
		IncludeModuleLangFile(__FILE__);

		return array(
			'ID',
			'DATE_REG_SHORT',
			'LAST_LOGIN_SHORT',
			'ACTIVE',
			'LOGIN',
			'NAME',
			'LAST_NAME',
			/*'LID',*/
			'SHORT_NAME',
			'PERSONAL_GENDER',
			'EMAIL',
			'PERSONAL_PHONE',
			'Bitrix\Sale\Order:BUYER' => array(
				'DATE_INS',
				'DATE_UPDATE_SHORT',
				'STATUS' => array(
					'STATUS_ID',
					'NAME',
					'DESCRIPTION'
				),
				'EMP_STATUS_BY' => array(
					'ID',
					'NAME',
					'LAST_NAME',
					'SHORT_NAME',
					'EMAIL',
					'PERSONAL_PHONE'
				),
				'DATE_STATUS_SHORT',
				'ALLOW_DELIVERY',
				'EMP_ALLOW_DELIVERY_BY' => array(
					'ID',
					'NAME',
					'LAST_NAME',
					'SHORT_NAME',
					'EMAIL',
					'PERSONAL_PHONE'
				),
				'DATE_ALLOW_DELIVERY_SHORT',
				'DELIVERY_ID',
				'DELIVERY_DOC_NUM',
				'DELIVERY_DOC_DATE_SHORT',
				'PRICE_DELIVERY',
				'PAYED',
				'EMP_PAYED_BY' => array(
					'ID',
					'NAME',
					'LAST_NAME',
					'SHORT_NAME',
					'EMAIL',
					'PERSONAL_PHONE'
				),
				'DEDUCTED',
				'PAY_SYSTEM' => array(
					'ID',
					'NAME',
					'DESCRIPTION',
					'ACTIVE',
					'CURRENCY'
				),
				'PAY_VOUCHER_NUM',
				'PAY_VOUCHER_DATE_SHORT',
				'PRICE',
				'SUM_PAID_FORREP',
				'CANCELED',
				'EMP_CANCELED_BY' => array(
					'ID',
					'NAME',
					'LAST_NAME',
					'SHORT_NAME',
					'EMAIL',
					'PERSONAL_PHONE'
				),
				'DATE_CANCELED_SHORT',
				'REASON_CANCELED',
				'PRODUCTS_QUANT',
				'TAX_VALUE',
				'DISCOUNT_ALL',
				'PERSON_TYPE_ID'
			),
			'UserGroup:USER.GROUP' => array(
				'ID',
				'NAME'
			)
		);
	}

	public static function getCalcVariations()
	{
		return array_merge(parent::getCalcVariations(), array(
			'UserGroup:USER.GROUP.ID' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			),
			'UserGroup:USER.GROUP.NAME' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			)
		));
	}

	public static function getCompareVariations()
	{
		return array_merge(parent::getCompareVariations(), array(
			// Order
			'\Bitrix\Sale\StatusLang' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'Bitrix\Sale\Order:BUYER.DELIVERY_ID' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'Bitrix\Sale\Order:BUYER.PAY_SYSTEM' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'Bitrix\Sale\Order:BUYER.PERSON_TYPE_ID' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			// User
			'PERSONAL_GENDER' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'UserGroup:USER.GROUP' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'\Bitrix\Main\User' => array(
				'EQUAL'
			),
			'\Bitrix\Main\Group' => array(
				'EQUAL'
			)
		));
	}

	public static function getDefaultColumns()
	{
		return array(
			array('name' => 'ID'),
			array('name' => 'SHORT_NAME')
		);
	}

	public static function getPeriodFilter($date_from, $date_to)
	{
		if(is_null($date_from) && is_null($date_to))
		{
			return array(); // Empty filter for empty time interval.
		}

		$filter = array('LOGIC' => 'AND');
		if(!is_null($date_to))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'<=DATE_REG_SHORT' => $date_to,
				'=DATE_REG_SHORT' => null
			);
		}

		if(!is_null($date_from))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'>=DATE_REG_SHORT' => $date_from,
				'=DATE_REG_SHORT' => null
			);
		}

		return $filter;
	}

	public static function fillFilterReferenceColumn(&$filterElement, Entity\ReferenceField $field)
	{
		if ($field->getRefEntityName() == '\Bitrix\Main\User')
		{
			// USER
			if ($filterElement['value'])
			{
				$res = CUser::GetByID($filterElement['value']);
				$user = $res->fetch();

				if ($user)
				{
					$username = CUser::FormatName(CSite::GetNameFormat(null, self::getDefaultSiteId()), $user, true, false);
					$filterElement['value'] = array('id' => $user['ID'], 'name' => $username);
				}
				else
				{
					$filterElement['value'] = array('id' => $filterElement['value'], 'name' => GetMessage('REPORT_USER_NOT_FOUND'));
				}
			}
			else
			{
				$filterElement['value'] = array('id' => '');
			}
		}
		else if ($field->getRefEntityName() == '\Bitrix\Main\Group')
		{
			// GROUP
			if ($filterElement['value'])
			{
				$res = CGroup::GetByID($filterElement['value']);
				$group = $res->fetch();

				if ($group)
				{
					$filterElement['value'] = array('id' => $group['ID'], 'name' => $group['NAME']);
				}
				else
				{
					$filterElement['value'] = array('id' => $filterElement['value'], 'name' => GetMessage('SALE_REPORT_GROUP_NOT_FOUND'));
				}
			}
			else
			{
				$filterElement['value'] = array('id' => '');
			}
		}
	}

	public static function confirmFilterBackReferenceRewrite($fElem, $chain)
	{
		//if ($chain->getLastElement()->getValue()->getEntity() instanceof \Bitrix\Sale\OrderEntity)
		if (strncasecmp($fElem['name'], 'Bitrix\Sale\Order:BUYER', 23) === 0)
		{
			return false;
		}

		return true;
	}

	public static function confirmSelectBackReferenceRewrite($elem, $chain)
	{
		if (strncasecmp($elem['name'], 'Bitrix\Sale\Order:BUYER', 23) === 0)
		{
			return false;
		}

		return true;
	}

	private static function fieldInFilter(&$filter, $fieldPathStr)
	{
		foreach ($filter as $k => &$v)
		{
			if ($k === 'LOGIC') continue;
			if (is_array($v)) return(self::fieldInFilter($v, $fieldPathStr));
			else if (strpos($k, $fieldPathStr) !== false) return true;
		}
		return false;
	}

	private static function filterBySiteNeeded(&$select, &$filter)
	{
		// The filter by site is necessary, if the field of "Order" entity in the filter or select is used.
		$bResult = false;
		foreach (array_keys($select) as $k)
		{
			if (strpos($k, '_SALE_ORDER_BUYER_') !== false)
			{
				$bResult = true;
				break;
			}
		}
		if (!$bResult)
		{
			if (self::fieldInFilter($filter, 'Bitrix\Sale\Order:BUYER.')) $bResult = true;
		}

		return $bResult;
	}

	private static function rewriteDeliveryFilter(&$filter)
	{
		foreach ($filter as $k => &$v)
		{
			if ($k === 'LOGIC') continue;
			if (is_array($v)) self::rewriteDeliveryFilter($v);
			else if ($k === '=Bitrix\Sale\Order:BUYER.DELIVERY_ID')
			{
				if (!is_numeric($v))
				{
					$filter['=%Bitrix\Sale\Order:BUYER.DELIVERY_ID'] = $v.':%';
					unset($filter[$k]);
				}
			}
			else if ($k === '!Bitrix\Sale\Order:BUYER.DELIVERY_ID')
			{
				if (!is_numeric($v))
				{
					$filter[] = array(
						'LOGIC' => 'OR',
						'!=%Bitrix\Sale\Order:BUYER.DELIVERY_ID' => $v.':%',
						'=Bitrix\Sale\Order:BUYER.DELIVERY_ID' => null
					);
					unset($filter[$k]);
				}
			}
		}
	}

	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime)
	{
		global $DB;

		self::rewriteDeliveryFilter($filter);

		// Rewrite filter by Site.
		if (self::filterBySiteNeeded($select, $filter))
		{
			if ($filter['LOGIC'] === 'AND')
			{
				$filter[] = array(
					'LOGIC' => 'OR',
					array('=Bitrix\Sale\Order:BUYER.LID' => $DB->ForSql(self::getDefaultSiteId())),
					array('=Bitrix\Sale\Order:BUYER.LID' => null)
				);
			}
			else {
				$subFilter = $filter;
				foreach (array_keys($filter) as $k)
				{
					unset($filter[$k]);
				}
				$filter['LOGIC'] = 'AND';
				$filter[] = $subFilter;
				$filter[] = array(
					'LOGIC' => 'OR',
					array('=Bitrix\Sale\Order:BUYER.LID' => $DB->ForSql(self::getDefaultSiteId())),
					array('=Bitrix\Sale\Order:BUYER.LID' => null)
				);
			}
		}
	}


	/* remove it when PHP 5.3 available */
	public static function formatResults(&$rows, &$columnInfo, $total)
	{
		foreach ($rows as &$row)
		{
			foreach ($row as $k => &$v)
			{
				if (!array_key_exists($k, $columnInfo))
				{
					continue;
				}

				$cInfo = &$columnInfo[$k];

				if (is_array($v))
				{
					foreach ($v as &$subv)
					{
						self::formatResultValue($k, $subv, $row, $cInfo, $total);
					}
				}
				else
				{
					self::formatResultValue($k, $v, $row, $cInfo, $total);
				}
			}
		}

		unset($row, $v, $subv);
	}
	/* \remove it */

	public static function formatResultValue($k, &$v, &$row, &$cInfo, $total)
	{
		/** @var Bitrix\Main\Entity\Field[] $cInfo */
		if ($cInfo['field']->getDataType() !== 'float' )    // skip base rounding
		{
			parent::formatResultValue($k, $v, $row, $cInfo, $total);
		}

		if ($k === 'LID')
		{
			$v = self::$sitelist[$v];
		}
		else if ($k === 'PERSONAL_GENDER')
		{
			$v = self::$genders[$v];
		}
		else if ($k === 'MAIN_USER_SALE_ORDER_BUYER_DELIVERY_ID')
		{
			$id = self::prepareDeliveryId($v);
			if (!is_null($id))
			{
				if (is_numeric($id))
				{
					$row['__HREF_'.$k] = '/bitrix/admin/sale_delivery_edit.php?ID='.$id.'&lang='.LANGUAGE_ID.'&filter=Y&set_filter=Y';
				}
				else
				{
					$row['__HREF_'.$k] = '/bitrix/admin/sale_delivery_handler_edit.php?SID='.$id.'&lang='.LANGUAGE_ID;
				}
			}
			$v = self::getDeliveryByExtId($v);
		}
		// Removing the link from the email field if is empty.
		else if ($k === 'EMAIL')
		{
			if (is_null($v) || empty($v) || $v == '&nbsp;') unset($row['__HREF_'.$k]);
		}
		else if ($k === 'MAIN_USER_SALE_ORDER_BUYER_PERSON_TYPE_ID')
		{
			$v = self::$personTypes[$v]['NAME'];
		}

		// Inserting zero in numerical fields with null value.
		if (empty($v))
		{
			if (in_array($cInfo['field']->getDataType(), array('integer', 'float')))
			{
				$v = 0;
			}
			//else $v = '&nbsp;';
		}

		// Formatting of monetary fields.
		foreach (self::$monetaryFields as $monField)
		{
			if ($cInfo['field']->getEntity()->getDataClass() === 'Bitrix\\Sale\\OrderTable'
				&& preg_match('/'.$monField.'$/', $cInfo['field']->getName()))
			{
				$v = self::calculateInReportCurrency($v);
				$v = number_format($v, 2, '.', ' ');
				break;
			}
		}

		// Formatting fields with goods quantity.
		foreach (self::$goodsQuantityFields as $qField)
		{
			if (preg_match('/'.$qField.'$/', $k))
			{
				if (!empty($v) && !is_null($v) && $v != '&nbsp;')
				{
					if (self::$fDecimalQuant) $v = sprintf('%.3f', round($v,3));
					else $v = sprintf('%d', round($v,0));
				}
				break;
			}
		}

		// Formatting short name fields of buyers.
		foreach (self::$userNotAuthNameFields as $uField)
		{
			if (preg_match('/'.$uField.'$/', $k))
			{
				if (empty($v) || is_null($v) || $v == '&nbsp;')
				{
					$v = GetMessage('SALE_REPORT_USER_NOT_AUTH');
				}
				break;
			}
		}
	}

	public static function formatResultGroupingTotal($params)
	{
		$paramTotal = array('TOTAL_'.$params['k'] => &$params['v']);
		$viewColumns = array($params['k'] => &$params['cInfo']);
		static::formatResultsTotal($paramTotal, $viewColumns, true);
	}

	public static function formatResultsTotal(&$total, &$columnInfo, $bFormatOnly = false)
	{
		parent::formatResultsTotal($total, $columnInfo);

		foreach ($total as $k => &$v)
		{
			// Formatting of monetary fields.
			foreach (self::$monetaryFields as $monField)
			{
				if (preg_match('/'.$monField.'$/', $k))
				{
					if (!$bFormatOnly) $v = self::calculateInReportCurrency($v);
					$v = number_format($v, 2, '.', ' ');
					break;
				}
			}

			// Formatting fields with goods quantity.
			foreach (self::$goodsQuantityFields as $qField)
			{
				if (preg_match('/'.$qField.'$/', $k))
				{
					if (!empty($v) && !is_null($v) && $v != '&nbsp;')
					{
						if (self::$fDecimalQuant) $v = sprintf('%.3f', round($v,3));
						else $v = sprintf('%d', round($v,0));
					}
					break;
				}
			}

			// Formatting fields without summary calculation.
			foreach (self::$withoutTotalFields as $wtField)
			{
				if (preg_match('/TOTAL_'.$wtField.'$/', $k))
				{
					unset($total[$k]);
					break;
				}
			}
		}
	}

	public static function getDefaultElemHref($elem, $fList)
	{
		$href = '';
		if (empty($elem['aggr']))
		{
			$field = $fList[$elem['name']];

			if ($field->getEntity()->getName() == 'User')
			{
				if ($elem['name'] == 'SHORT_NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'EMAIL')
				{
					$href = array('pattern' => 'mailto:#EMAIL#');
				}
				else if ($elem['name'] == 'Bitrix\Sale\Order:BUYER.EMP_STATUS_BY.SHORT_NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#Bitrix\Sale\Order:BUYER.EMP_STATUS_BY.ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'Bitrix\Sale\Order:BUYER.EMP_STATUS_BY.EMAIL')
				{
					$href = array('pattern' => 'mailto:#Bitrix\Sale\Order:BUYER.EMP_STATUS_BY.EMAIL#');
				}
				else if ($elem['name'] == 'Bitrix\Sale\Order:BUYER.EMP_ALLOW_DELIVERY_BY.SHORT_NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#Bitrix\Sale\Order:BUYER.EMP_ALLOW_DELIVERY_BY.ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'Bitrix\Sale\Order:BUYER.EMP_ALLOW_DELIVERY_BY.EMAIL')
				{
					$href = array('pattern' => 'mailto:#Bitrix\Sale\Order:BUYER.EMP_ALLOW_DELIVERY_BY.EMAIL#');
				}
				else if ($elem['name'] == 'Bitrix\Sale\Order:BUYER.EMP_PAYED_BY.SHORT_NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#Bitrix\Sale\Order:BUYER.EMP_PAYED_BY.ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'Bitrix\Sale\Order:BUYER.EMP_PAYED_BY.EMAIL')
				{
					$href = array('pattern' => 'mailto:#Bitrix\Sale\Order:BUYER.EMP_PAYED_BY.EMAIL#');
				}
				else if ($elem['name'] == 'Bitrix\Sale\Order:BUYER.EMP_CANCELED_BY.SHORT_NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#Bitrix\Sale\Order:BUYER.EMP_CANCELED_BY.ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'Bitrix\Sale\Order:BUYER.EMP_CANCELED_BY.EMAIL')
				{
					$href = array('pattern' => 'mailto:#Bitrix\Sale\Order:BUYER.EMP_CANCELED_BY.EMAIL#');
				}
			}
			elseif ($field->getEntity()->getName() == 'Order')
			{
				if ($elem['name'] == 'Bitrix\Sale\Order:BUYER.ID')
				{
					$href = array('pattern' => '/bitrix/admin/sale_order_detail.php?ID=#Bitrix\Sale\Order:BUYER.ID#&lang='.LANG);
				}
				else if ($elem['name'] === 'Bitrix\Sale\Order:BUYER.DELIVERY_ID')
				{
					$href = array('pattern' => '/404.php?DELIVERY_ID=#Bitrix\Sale\Order:BUYER.DELIVERY_ID#&lang='.LANG);
				}
			}
			else if ($field->getEntity()->getName() == 'PaySystem')
			{
				if ($elem['name'] == 'Bitrix\Sale\Order:BUYER.PAY_SYSTEM.NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_pay_system_edit.php?ID=#Bitrix\Sale\Order:BUYER.PAY_SYSTEM.ID#&lang='.LANG.'&filter=Y&set_filter=Y');
				}
			}
		}

		return $href;
	}
}

class CSaleReportSaleBasketHelper extends CBaseSaleReportHelper
{
	private static $monetaryFields = array(
		'PRICE',
		'DISCOUNT_PRICE',
		'SUMMARY_PRICE',
		'VAT_RATE_PRC',
		// Order fields
		'TAX_VALUE',
		'PRICE_DELIVERY',
		'DISCOUNT_ALL',
		'PRICE',
		'SUM_PAID_FORREP',
		'PRODUCT_PURCHASING_PRICE_IN_SITE_CURRENCY',
		'GROSS_PROFIT',
		'PROFITABILITY'
	);

	private static $goodsQuantityFields = array(
		'QUANTITY',
		'PRODUCT_QUANTITY',
		// Order fields
		'PRODUCTS_QUANT'
	);

	private static $withoutTotalFields = array(
		'PRODUCT_ID',
		'FUSER_ID',
		'ORDER_ID',
		'PRODUCT_SALE_GOODS_SECTION_PROD_SECT_ID',
		'PRODUCT_SALE_GOODS_SECTION_PROD_SECT_NAME',
		'VAT_RATE_PRC',
		// Order fields
		'PERSON_TYPE_ID'
	);

	private static $avgGroupingAggregationFields = array(
		'PROFITABILITY'
	);

	private static $userNotAuthNameFields = array(
		'FUSER_USER_SHORT_NAME'
	);

	private static $currentIblockFilter = array('value' => null, 'compare' => null);

	public static function getEntityName()
	{
		return 'Bitrix\Sale\Basket';
	}

	public static function getOwnerId()
	{
		return SALE_REPORT_OWNER_ID.'_SaleBasket';
	}

	public static function getColumnList()
	{
		IncludeModuleLangFile(__FILE__);
		return array(
			'ID',
			'FUSER_ID',
			'FUSER.USER' => array(
				'ID',
				'SHORT_NAME',
				'EMAIL',
				'PERSONAL_PHONE',
				'UserGroup:USER.GROUP' => array(
					'ID',
					'NAME'
				)
			),
			'DATE_INS',
			'DATE_UPD',
			'PRODUCT_ID',
			'NAME',
			'NAME_WITH_IDENT',
			'DELAY',
			'ORDER_ID',
			'ORDER' => array(
				'DATE_INS',
				'DATE_UPDATE_SHORT',
				'STATUS' => array(
					'STATUS_ID',
					'NAME',
					'DESCRIPTION'
				),
				'EMP_STATUS_BY' => array(
					'ID',
					'NAME',
					'LAST_NAME',
					'SHORT_NAME',
					'EMAIL',
					'PERSONAL_PHONE'
				),
				'DATE_STATUS_SHORT',
				'ALLOW_DELIVERY',
				'EMP_ALLOW_DELIVERY_BY' => array(
					'ID',
					'NAME',
					'LAST_NAME',
					'SHORT_NAME',
					'EMAIL',
					'PERSONAL_PHONE'
				),
				'DATE_ALLOW_DELIVERY_SHORT',
				'DELIVERY_ID',
				'DELIVERY_DOC_NUM',
				'DELIVERY_DOC_DATE_SHORT',
				'PRICE_DELIVERY',
				'PAYED',
				'EMP_PAYED_BY' => array(
					'ID',
					'NAME',
					'LAST_NAME',
					'SHORT_NAME',
					'EMAIL',
					'PERSONAL_PHONE'
				),
				'DEDUCTED',
				'PAY_SYSTEM' => array(
					'ID',
					'NAME',
					'DESCRIPTION',
					'ACTIVE',
					'CURRENCY'
				),
				'PAY_VOUCHER_NUM',
				'PAY_VOUCHER_DATE_SHORT',
				'PRICE',
				'SUM_PAID_FORREP',
				'CANCELED',
				'EMP_CANCELED_BY' => array(
					'ID',
					'NAME',
					'LAST_NAME',
					'SHORT_NAME',
					'EMAIL',
					'PERSONAL_PHONE'
				),
				'DATE_CANCELED_SHORT',
				'REASON_CANCELED',
				'PRODUCTS_QUANT',
				'TAX_VALUE',
				'DISCOUNT_ALL',
				'PERSON_TYPE_ID'
			),
			'PRICE',
			'DISCOUNT_PRICE',
			'DISCOUNT_NAME',
			'DISCOUNT_VALUE',
			'NOTES',
			'QUANTITY',
			'SUMMARY_PRICE',
			'VAT_RATE_PRC',
			'PRODUCT.ACTIVE',
			'SUBSCRIBE',
			'N_SUBSCRIBE',
			'PRODUCT.QUANTITY',
			'SUMMARY_PURCHASING_PRICE',
			'GROSS_PROFIT',
			'PROFITABILITY',
			'PRODUCT.GoodsSection:PRODUCT.SECT' => array(
				'ID',
				'NAME'
			)
		);
	}

	public static function getDefaultColumns()
	{
		return array(
			array('name' => 'ID'),
			array('name' => 'DATE_UPD'),
			array('name' => 'ORDER_ID'),
			array('name' => 'PRODUCT_ID'),
			array('name' => 'NAME')
		);
	}

	public static function getCalcVariations()
	{
		return array_merge(parent::getCalcVariations(), array(
			'FUSER.USER.UserGroup:USER.GROUP.ID' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			),
			'FUSER.USER.UserGroup:USER.GROUP.NAME' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			),
			'PRODUCT.GoodsSection:PRODUCT.SECT.ID' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			),
			'PRODUCT.GoodsSection:PRODUCT.SECT.NAME' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			)
		));
	}

	public static function getCompareVariations()
	{
		return array_merge(parent::getCompareVariations(), array(
			// Order
			'ORDER.LID' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'ORDER.PERSON_TYPE_ID' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'ORDER.STATUS' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'ORDER.PAY_SYSTEM' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'ORDER.DELIVERY_ID' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			// Basket
			'PRODUCT.GoodsSection:PRODUCT.SECT' => array(
				'EQUAL'/*,
				'NOT_EQUAL'*/
			),
			'FUSER.USER.UserGroup:USER.GROUP' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'\Bitrix\Main\User' => array(
				'EQUAL'
			),
			'\Bitrix\Main\Group' => array(
				'EQUAL'
			)
		));
	}

	public static function getPeriodFilter($date_from, $date_to)
	{
		global $DB;

		$filter = array('LOGIC' => 'AND');

		if(!is_null($date_to))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'<=DATE_UPD' => $date_to,
				'=DATE_UPD' => null
			);
		}

		if(!is_null($date_from))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'>=DATE_UPD' => $date_from,
				'=DATE_UPD' => null
			);
		}

		// Site filter
		$filter[] = array(
			'=LID' => $DB->ForSql(self::getDefaultSiteId())
		);

		return $filter;
	}

	public static function beforeFilterBackReferenceRewrite(&$filter, $viewColumns)
	{
		// Field for filtering category of the goods
		$fieldName = 'PRODUCT.GoodsSection:PRODUCT.SECT.ID';

		// <editor-fold defaultstate="collapsed" desc="Preparation of multiple filters">
		foreach ($filter as $fId => &$fInfo)
		{
			foreach ($fInfo as $k => &$fElem)
			{
				if (is_array($fElem) && $fElem['type'] == 'field')
				{
					$columnName = '';

					if (preg_match('/__COLUMN__(\d+)/', $fElem['name'], $match))
					{
						$num = $match[1];
						$columnName = $viewColumns[$num]['fieldName'];
					}

					if ($fElem['name'] == $fieldName || $columnName == $fieldName)
					{
						$iblockFilterCompare = $fElem['compare'];
						if ($iblockFilterCompare === 'EQUAL'/* || $iblockFilterCompare === 'NOT_EQUAL'*/)
						{
							$iblockFilterValue = array();
							$arFilterValues = array();
							if (is_array($fElem['value'])) $arFilterValues = $fElem['value'];
							else $arFilterValues[] = $fElem['value'];
							foreach ($arFilterValues as $l => $filterValue)
							{
								if (is_string($filterValue) && $filterValue[0] == 'c')
								{
									$iblockFilterValue[] = intval(substr($filterValue, 1));
									// The filter on a section is deleted if the filter only according
									// to the catalog is necessary
									unset($arFilterValues[$l]);
								}
								else{
									$sections = array();
									foreach (parent::$catalogSections as $sectKey => $sect)
									{
										if ($sectKey == $filterValue) $iblockFilterValue[] = intval($sect['catalog']['ID']);
										if (in_array($filterValue, $sect['path'])) $sections[] = $sectKey;
									}
									$arFilterValues[$l] = $sections;
								}
							}
							$l = count($arFilterValues);
							if ($l === 0) unset($fInfo[$k]);
							else if ($l === 1) $fInfo[$k]['value'] = reset($arFilterValues);
							else
							{
								$newFilterValue = array();
								foreach ($arFilterValues as $filterValue)
								{
									$newFilterValue = array_merge($newFilterValue, $filterValue);
								}
								$newFilterValue = array_unique($newFilterValue);
								$fInfo[$k]['value'] = $newFilterValue;
							}
							$l = count($iblockFilterValue);
							if ($l === 0) $iblockFilterValue = null;
							else if ($l === 1) $iblockFilterValue = reset($iblockFilterValue);
							else $iblockFilterValue = array_unique($iblockFilterValue);
							self::$currentIblockFilter['value'] = $iblockFilterValue;
							self::$currentIblockFilter['compare'] = $iblockFilterCompare;
						}
					}
				}
			}
		}
		// </editor-fold>
	}

	private static function rewriteDeliveryFilter(&$filter)
	{
		foreach ($filter as $k => &$v)
		{
			if ($k === 'LOGIC') continue;
			if (is_array($v)) self::rewriteDeliveryFilter($v);
			else if ($k === '=ORDER.DELIVERY_ID')
			{
				if (!is_numeric($v))
				{
					$filter['=%ORDER.DELIVERY_ID'] = $v.':%';
					unset($filter[$k]);
				}
			}
			else if ($k === '!ORDER.DELIVERY_ID')
			{
				if (!is_numeric($v))
				{
					$filter[] = array(
						'LOGIC' => 'OR',
						'!=%ORDER.DELIVERY_ID' => $v.':%',
						'=ORDER.DELIVERY_ID' => null
					);
					unset($filter[$k]);
				}
			}
		}
	}

	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime)
	{
		if (self::$currentIblockFilter['value'])
		{
			$filter[] = array(
				'LOGIC' => 'AND',
				((self::$currentIblockFilter['compare'] === 'EQUAL') ? '=' : '!=').'PRODUCT.IBLOCK.IBLOCK_ID' => self::$currentIblockFilter['value']
			);
		}
		self::$currentIblockFilter['value'] = null;
		self::$currentIblockFilter['compare'] = null;

		self::rewriteDeliveryFilter($filter);
	}

	public static function fillFilterReferenceColumn(&$filterElement, Entity\ReferenceField $field)
	{
		if ($field->getRefEntityName() == '\Bitrix\Main\User')
		{
			// USER
			if ($filterElement['value'])
			{
				$res = CUser::GetByID($filterElement['value']);
				$user = $res->fetch();

				if ($user)
				{
					$username = CUser::FormatName(CSite::GetNameFormat(null, self::getDefaultSiteId()), $user, true, false);
					$filterElement['value'] = array('id' => $user['ID'], 'name' => $username);
				}
				else
				{
					$filterElement['value'] = array('id' => $filterElement['value'], 'name' => GetMessage('REPORT_USER_NOT_FOUND'));
				}
			}
			else
			{
				$filterElement['value'] = array('id' => '');
			}
		}
		else if ($field->getRefEntityName() == '\Bitrix\Main\Group')
		{
			// GROUP
			if ($filterElement['value'])
			{
				$res = CGroup::GetByID($filterElement['value']);
				$group = $res->fetch();

				if ($group)
				{
					$filterElement['value'] = array('id' => $group['ID'], 'name' => $group['NAME']);
				}
				else
				{
					$filterElement['value'] = array('id' => $filterElement['value'], 'name' => GetMessage('SALE_REPORT_GROUP_NOT_FOUND'));
				}
			}
			else
			{
				$filterElement['value'] = array('id' => '');
			}
		}
	}

	/* remove it when PHP 5.3 available */
	public static function formatResults(&$rows, &$columnInfo, $total)
	{
		foreach ($rows as &$row)
		{
			foreach ($row as $k => &$v)
			{
				if (!array_key_exists($k, $columnInfo))
				{
					continue;
				}

				$cInfo = &$columnInfo[$k];

				if (is_array($v))
				{
					foreach ($v as &$subv)
					{
						self::formatResultValue($k, $subv, $row, $cInfo, $total);
					}
				}
				else
				{
					self::formatResultValue($k, $v, $row, $cInfo, $total);
				}
			}
		}

		unset($row, $v, $subv);
	}
	/* \remove it */

	public static function formatResultValue($k, &$v, &$row, &$cInfo, $total)
	{
		/** @var Bitrix\Main\Entity\Field[] $cInfo */
		if ($cInfo['field']->getDataType() !== 'float' )    // skip base rounding
		{
			parent::formatResultValue($k, $v, $row, $cInfo, $total);
		}

		if ($k === 'SALE_BASKET_ORDER_PERSON_TYPE_ID')
		{
			$v = self::$personTypes[$v]['NAME'];
		}
		else if ($k === 'SALE_BASKET_ORDER_DELIVERY_ID')
		{
			$id = self::prepareDeliveryId($v);
			if (!is_null($id))
			{
				if (is_numeric($id))
				{
					$row['__HREF_'.$k] = '/bitrix/admin/sale_delivery_edit.php?ID='.$id.'&lang='.LANGUAGE_ID.'&filter=Y&set_filter=Y';
				}
				else
				{
					$row['__HREF_'.$k] = '/bitrix/admin/sale_delivery_handler_edit.php?SID='.$id.'&lang='.LANGUAGE_ID;
				}
			}
			$v = self::getDeliveryByExtId($v);
		}
		// Removing the link from the email field if is empty.
		else if ($k === 'SALE_BASKET_FUSER_USER_EMAIL')
		{
			if (is_null($v) || empty($v) || $v == '&nbsp;') unset($row['__HREF_'.$k]);
		}
		else if ($k === 'SALE_BASKET_ORDER_PERSON_TYPE_ID')
		{
			$v = self::$personTypes[$v]['NAME'];
		}


		// Inserting zero in numerical fields with null value.
		if (empty($v))
		{
			if (in_array($cInfo['field']->getDataType(), array('integer', 'float')))
			{
				$v = 0;
			}
			//else $v = '&nbsp;';
		}

		// Formatting of monetary fields.
		foreach (self::$monetaryFields as $monField)
		{
			if (preg_match('/'.$monField.'$/', $k))
			{
				$v = self::calculateInReportCurrency($v);
				$v = number_format($v, 2, '.', ' ');
				break;
			}
		}

		// Formatting fields with goods quantity.
		foreach (self::$goodsQuantityFields as $qField)
		{
			if (preg_match('/'.$qField.'$/', $k))
			{
				if (!empty($v) && !is_null($v) && $v != '&nbsp;')
				{
					if (self::$fDecimalQuant) $v = sprintf('%.3f', round($v,3));
					else $v = sprintf('%d', round($v,0));
				}
				break;
			}
		}

		// Formatting short name fields of buyers.
		foreach (self::$userNotAuthNameFields as $uField)
		{
			if (preg_match('/'.$uField.'$/', $k))
			{
				if (empty($v) || is_null($v) || $v == '&nbsp;')
				{
					$v = GetMessage('SALE_REPORT_USER_NOT_AUTH');
					unset($row['__HREF_'.$k]);
				}
				break;
			}
		}

		// fields has average grouping aggregation (default grouping aggregation is sum)
		if (!isset($cInfo['grouping_aggr']))
		{
			foreach (self::$avgGroupingAggregationFields as $aggrField)
			{
				if (preg_match('/'.$aggrField.'$/', $k))
				{
					$cInfo['grouping_aggr'] = 'AVG';
				}
			}
		}
	}

	public static function formatResultGroupingTotal($params)
	{
		$paramTotal = array('TOTAL_'.$params['k'] => &$params['v']);
		$viewColumns = array($params['k'] => &$params['cInfo']);
		static::formatResultsTotal($paramTotal, $viewColumns, true);
	}

	public static function formatResultsTotal(&$total, &$columnInfo, $bFormatOnly = false)
	{
		parent::formatResultsTotal($total, $columnInfo);

		foreach ($total as $k => &$v)
		{
			// Formatting of monetary fields.
			foreach (self::$monetaryFields as $monField)
			{
				if (preg_match('/'.$monField.'$/', $k))
				{
					if (!$bFormatOnly) $v = self::calculateInReportCurrency($v);
					$v = number_format($v, 2, '.', ' ');
					break;
				}
			}

			// Formatting fields with goods quantity.
			foreach (self::$goodsQuantityFields as $qField)
			{
				if (preg_match('/'.$qField.'$/', $k))
				{
					if (!empty($v) && !is_null($v) && $v != '&nbsp;')
					{
						if (self::$fDecimalQuant) $v = sprintf('%.3f', round($v,3));
						else $v = sprintf('%d', round($v,0));
					}
					break;
				}
			}

			// Formatting fields without summary calculation.
			foreach (self::$withoutTotalFields as $wtField)
			{
				if (preg_match('/TOTAL_'.$wtField.'$/', $k))
				{
					unset($total[$k]);
					break;
				}
			}
		}
	}

	public static function getDefaultElemHref($elem, $fList)
	{
		$href = '';
		if (empty($elem['aggr']))
		{
			$field = $fList[$elem['name']];

			if ($field->getEntity()->getName() == 'User')
			{
				if ($elem['name'] == 'FUSER.USER.SHORT_NAME')
				{
					//$href = array('pattern' => '/bitrix/admin/user_edit.php?ID=#FUSER.USER.ID#&lang='.LANG);
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#FUSER.USER.ID#&lang='.LANG);
				}
				elseif ($elem['name'] == 'FUSER.USER.EMAIL')
				{
					$href = array('pattern' => 'mailto:#FUSER.USER.EMAIL#');
				}
				else if ($elem['name'] == 'ORDER.EMP_STATUS_BY.SHORT_NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#ORDER.EMP_STATUS_BY.ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'ORDER.EMP_STATUS_BY.EMAIL')
				{
					$href = array('pattern' => 'mailto:#ORDER.EMP_STATUS_BY.EMAIL#');
				}
				else if ($elem['name'] == 'ORDER.EMP_ALLOW_DELIVERY_BY.SHORT_NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#ORDER.EMP_ALLOW_DELIVERY_BY.ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'ORDER.EMP_ALLOW_DELIVERY_BY.EMAIL')
				{
					$href = array('pattern' => 'mailto:#ORDER.EMP_ALLOW_DELIVERY_BY.EMAIL#');
				}
				else if ($elem['name'] == 'ORDER.EMP_PAYED_BY.SHORT_NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#ORDER.EMP_PAYED_BY.ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'ORDER.EMP_PAYED_BY.EMAIL')
				{
					$href = array('pattern' => 'mailto:#ORDER.EMP_PAYED_BY.EMAIL#');
				}
				else if ($elem['name'] == 'ORDER.EMP_CANCELED_BY.SHORT_NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_buyers_profile.php?USER_ID=#ORDER.EMP_CANCELED_BY.ID#&lang='.LANG);
				}
				else if ($elem['name'] == 'ORDER.EMP_CANCELED_BY.EMAIL')
				{
					$href = array('pattern' => 'mailto:#ORDER.EMP_CANCELED_BY.EMAIL#');
				}
			}
			elseif ($field->getEntity()->getName() == 'Basket')
			{
				if ($elem['name'] == 'NAME' || $elem['name'] == 'NAME_WITH_IDENT')
				{
					$href = array('pattern' => '/bitrix/admin/cat_product_edit.php?IBLOCK_ID='.
						'#PRODUCT.IBLOCK.IBLOCK.ID#&type=#PRODUCT.IBLOCK.IBLOCK.IBLOCK_TYPE_ID#&ID='.
						'#PRODUCT_ID#&lang='.LANG.'&WF=Y');
				}
				elseif ($elem['name'] == 'ORDER_ID')
				{
					$href = array('pattern' => '/bitrix/admin/sale_order_detail.php?ID=#ORDER_ID#&lang='.LANG);
				}
			}
			else if ($field->getEntity()->getName() == 'Order')
			{
				if ($elem['name'] === 'ORDER.ID')
				{
					$href = array('pattern' => '/bitrix/admin/sale_order_detail.php?ID=#ORDER.ID#&lang='.LANG);
				}
				else if ($elem['name'] === 'ORDER.DELIVERY_ID')
				{
					$href = array('pattern' => '/404.php?DELIVERY_ID=#ORDER.DELIVERY_ID#&lang='.LANG);
				}
			}
			else if ($field->getEntity()->getName() == 'PaySystem')
			{
				if ($elem['name'] == 'ORDER.PAY_SYSTEM.NAME')
				{
					$href = array('pattern' => '/bitrix/admin/sale_pay_system_edit.php?ID=#ORDER.PAY_SYSTEM.ID#&lang='.LANG.'&filter=Y&set_filter=Y');
				}
			}
		}

		return $href;
	}
}

class CSaleReportSaleProductHelper extends CBaseSaleReportHelper
{
	private static $customProductFilter = 'all';

	private static $monetaryFields = array(
		'PRICE_IN_SITE_CURRENCY',
		'SUMMARY_PRICE_IN_SITE_CURRENCY',
		'PURCHASING_PRICE_IN_SITE_CURRENCY'
	);

	private static $goodsQuantityFields = array(
		'QUANTITY',
		'SALED_PRODUCTS_IN_PERIOD_BY_SHOP',
		'ARRIVED_PRODUCTS_IN_PERIOD_BY_SHOP',
		'ARRIVED_PRODUCTS_IN_PERIOD_BY_STORE',
		'EXPENSE_PRODUCTS_IN_PERIOD_BY_STORE',
		'STORE_PRODUCT_SALE_PRODUCT_AMOUNT'
	);

	private static $withoutTotalFields = array(
		'CONVERSION',
		'PRODUCT_STORE_ID'
	);

	private static $userNotAuthNameFields = array();

	private static $currentIblockFilter = array('value' => null, 'compare' => null);

	public static function setCustomProductFilter($strFilterValue)
	{
		self::$customProductFilter = $strFilterValue;
	}

	public static function enablePriceTypesColumns($bUsePriceTypesColumns)
	{
		self::$bUsePriceTypesColumns = $bUsePriceTypesColumns;
	}


	public static function getCustomProductFilter()
	{
		return self::$customProductFilter;
	}

	public static function getEntityName()
	{
		return 'Bitrix\Sale\Product';
	}

	public static function getOwnerId()
	{
		return SALE_REPORT_OWNER_ID.'_SaleProduct';
	}

	public static function getColumnList()
	{
		IncludeModuleLangFile(__FILE__);

		return array(
			'ID',
			'QUANTITY',
			'NAME',
			'NAME_WITH_IDENT',
			'ACTIVE',
			'WEIGHT_IN_SITE_UNITS',
			'PRICE_IN_SITE_CURRENCY',
			'PURCHASING_PRICE_IN_SITE_CURRENCY',
			'SUMMARY_PRICE_IN_SITE_CURRENCY',
			'VIEWS_IN_PERIOD_BY_SHOP',
			'ORDERS_IN_PERIOD_BY_SHOP',
			'CONVERSION',
			'SALED_PRODUCTS_IN_PERIOD_BY_SHOP',
			'ARRIVED_PRODUCTS_IN_PERIOD_BY_SHOP',
			'ARRIVED_PRODUCTS_IN_PERIOD_BY_STORE',
			'EXPENSE_PRODUCTS_IN_PERIOD_BY_STORE',
			'IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION' => array(
				'ID',
				'NAME'
			),
			'StoreProduct:SALE_PRODUCT' => array(
				'STORE.ID',
				'STORE.TITLE',
				'STORE.ADDRESS',
				'STORE.DESCRIPTION',
				'STORE.PHONE',
				'STORE.SCHEDULE',
				'AMOUNT'
			)
		);
	}

	public static function setRuntimeFields(\Bitrix\Main\Entity\Base $entity, $sqlTimeInterval)
	{
		global $DB;

		$entity->addField(array(
			'data_type' => 'integer',
			'expression' => array(
				'(SELECT  SUM(1) FROM b_catalog_product, b_sale_viewed_product WHERE %s = b_sale_viewed_product.PRODUCT_ID
				AND b_catalog_product.ID = b_sale_viewed_product.PRODUCT_ID
				AND b_sale_viewed_product.DATE_VISIT '.$sqlTimeInterval.' AND b_sale_viewed_product.LID = \''.$DB->ForSql(self::getDefaultSiteId()).'\')', 'ID'
			)
		), 'VIEWS_IN_PERIOD_BY_SHOP');

		$entity->addField(array(
			'data_type' => 'integer',
			'expression' => array(
				'(SELECT  COUNT(DISTINCT b_sale_order.ID)
				FROM b_catalog_product
					INNER JOIN b_sale_basket ON b_catalog_product.ID = b_sale_basket.PRODUCT_ID
					INNER JOIN b_sale_order ON b_sale_basket.ORDER_ID = b_sale_order.ID
				WHERE
						b_catalog_product.ID = %s
					AND b_sale_order.PAYED = \'Y\'
					AND b_sale_order.DATE_INSERT '.$sqlTimeInterval.'
					AND b_sale_basket.LID = \''.$DB->ForSql(self::getDefaultSiteId()).'\')', 'ID'
			)
		), 'ORDERS_IN_PERIOD_BY_SHOP');

		$entity->addField(array(
			'data_type' => 'integer',
			'expression' => array(
				$DB->isNull('(SELECT  SUM(b_sale_basket.QUANTITY)
							FROM b_sale_basket
								INNER JOIN b_sale_order ON b_sale_basket.ORDER_ID = b_sale_order.ID
							WHERE b_sale_basket.PRODUCT_ID = %s
								AND b_sale_order.PAYED = \'Y\'
								AND b_sale_order.DEDUCTED = \'Y\'
								AND b_sale_order.DATE_INSERT '.$sqlTimeInterval.'
								AND b_sale_basket.LID = \''.$DB->ForSql(self::getDefaultSiteId()).'\')', 0).'+'.
				$DB->isNull('(SELECT  SUM(b_catalog_docs_element.AMOUNT)
							FROM b_catalog_store_docs
								INNER JOIN b_catalog_docs_element on b_catalog_store_docs.ID = b_catalog_docs_element.DOC_ID
							WHERE b_catalog_store_docs.DOC_TYPE = \'D\'
								AND b_catalog_store_docs.STATUS = \'Y\'
								AND b_catalog_store_docs.DATE_DOCUMENT '.$sqlTimeInterval.'
								AND b_catalog_docs_element.ELEMENT_ID = %s)', 0),
				'ID', 'ID'
			)
		), 'SALED_PRODUCTS_IN_PERIOD_BY_SHOP');

		$entity->addField(array(
			'data_type' => 'float',
			'expression' => array(
				'(SELECT  SUM(b_catalog_docs_element.AMOUNT)
					FROM b_catalog_store_docs
					INNER JOIN b_catalog_docs_element on b_catalog_store_docs.ID = b_catalog_docs_element.DOC_ID
					WHERE b_catalog_store_docs.DOC_TYPE in (\'A\', \'R\')
						AND b_catalog_store_docs.STATUS = \'Y\'
						AND b_catalog_store_docs.DATE_DOCUMENT '.$sqlTimeInterval.'
						AND b_catalog_docs_element.ELEMENT_ID = %s)', 'ID'
			)
		), 'ARRIVED_PRODUCTS_IN_PERIOD_BY_SHOP');

		$entity->addField(array(
			'data_type' => 'float',
			'expression' => array(
				'(SELECT  SUM(b_catalog_docs_element.AMOUNT)
					FROM b_catalog_store_docs
					INNER JOIN b_catalog_docs_element on b_catalog_store_docs.ID = b_catalog_docs_element.DOC_ID
					WHERE b_catalog_store_docs.DOC_TYPE in (\'A\', \'M\', \'R\')
						AND b_catalog_store_docs.STATUS = \'Y\'
						AND b_catalog_store_docs.DATE_DOCUMENT '.$sqlTimeInterval.'
						AND b_catalog_docs_element.STORE_TO = %s
						AND b_catalog_docs_element.ELEMENT_ID = %s)', 'StoreProduct:SALE_PRODUCT.STORE_ID', 'ID'
			)
		), 'ARRIVED_PRODUCTS_IN_PERIOD_BY_STORE');

		$entity->addField(array(
			'data_type' => 'integer',
			'expression' => array(
				$DB->isNull('(SELECT  SUM(b_sale_store_barcode.QUANTITY)
							FROM b_sale_store_barcode
								INNER JOIN b_sale_basket ON b_sale_store_barcode.BASKET_ID = b_sale_basket.ID
								INNER JOIN b_sale_order ON b_sale_basket.ORDER_ID = b_sale_order.ID
							WHERE b_sale_store_barcode.STORE_ID = %s
								AND b_sale_basket.PRODUCT_ID = %s
								AND b_sale_order.PAYED = \'Y\'
								AND b_sale_order.DEDUCTED = \'Y\'
								AND b_sale_order.DATE_INSERT '.$sqlTimeInterval.'
								AND b_sale_basket.LID = \''.$DB->ForSql(self::getDefaultSiteId()).'\')', 0).'+'.
				$DB->isNull('(SELECT  SUM(b_catalog_docs_element.AMOUNT)
							FROM b_catalog_store_docs
								INNER JOIN b_catalog_docs_element on b_catalog_store_docs.ID = b_catalog_docs_element.DOC_ID
							WHERE b_catalog_store_docs.DOC_TYPE in (\'M\', \'D\')
								AND b_catalog_store_docs.STATUS = \'Y\'
								AND b_catalog_store_docs.DATE_DOCUMENT '.$sqlTimeInterval.'
								AND b_catalog_docs_element.STORE_FROM = %s
								AND b_catalog_docs_element.ELEMENT_ID = %s)', 0),
				'StoreProduct:SALE_PRODUCT.STORE_ID', 'ID', 'StoreProduct:SALE_PRODUCT.STORE_ID', 'ID'
			)
		), 'EXPENSE_PRODUCTS_IN_PERIOD_BY_STORE');

		$entity->addField(array(
			'data_type' => 'float',
			'expression' => array(
				'100 * CASE WHEN %s IS NULL OR %s = 0 THEN NULL ELSE %s / %s END',
				'VIEWS_IN_PERIOD_BY_SHOP', 'VIEWS_IN_PERIOD_BY_SHOP', 'ORDERS_IN_PERIOD_BY_SHOP', 'VIEWS_IN_PERIOD_BY_SHOP'
			)
		), 'CONVERSION');
	}

	public static function getDefaultColumns()
	{
		return array(
			array('name' => 'ID'),
			array('name' => 'NAME')
		);
	}

	public static function getCompareVariations()
	{
		return array_merge(parent::getCompareVariations(), array(
			'IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION' => array(
				'EQUAL'/*,
				'NOT_EQUAL'*/
			),
			'StoreProduct:SALE_PRODUCT' => array(
				'EQUAL'
			)
		));
	}

	public static function getCalcVariations()
	{
		return array_merge(parent::getCalcVariations(), array(
			'IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION.ID' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			),
			'IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION.NAME' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			),
			'StoreProduct:SALE_PRODUCT.STORE.ID' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			),
			'StoreProduct:SALE_PRODUCT.STORE.TITLE' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			)
		));
	}

	public static function getPeriodFilter($date_from, $date_to)
	{
		global $DB;

		$filter = array('LOGIC' => 'AND');

		// Site filter
		$filter[] = array(
			'=IBLOCK.IBLOCK.Site:IBLOCK.SITE_ID' => $DB->ForSql(self::getDefaultSiteId())
		);

		// Custom Product filter
		$customProductFilter = self::getCustomProductFilter();
		if (!empty($customProductFilter))
		{
			if ($customProductFilter === 'avail') $strOperation = '>';
			else if ($customProductFilter === 'not_avail') $strOperation = '<=';
			else $strOperation = '';
			if (!empty($strOperation)) $filter[] = array($strOperation.'QUANTITY' => '0');
		}

		return $filter;
	}

	public static function getEntityFilterPrimaryFieldName($fElem)
	{
		$res = 'ID';
		if ($fElem['name'] === 'StoreProduct:SALE_PRODUCT')
		{
			$res = 'STORE_ID';
		}
		else
		{
			$res = parent::getEntityFilterPrimaryFieldName($fElem);
		}
		return $res;
	}

	public static function confirmFilterBackReferenceRewrite($fElem, $chain)
	{
		if ($fElem['name'] === 'StoreProduct:SALE_PRODUCT.STORE_ID')
		{
			return false;
		}

		return true;
	}

	public static function confirmSelectBackReferenceRewrite($elem, $chain)
	{
		if ($elem['name'] === 'StoreProduct:SALE_PRODUCT.STORE_ID')
		{
			return false;
		}

		return true;
	}

	public static function beforeFilterBackReferenceRewrite(&$filter, $viewColumns)
	{
		// Field for filtering category of the goods
		$fieldName = 'IBLOCK.SectionElement:IBLOCK_ELEMENT.IBLOCK_SECTION.ID';

		// <editor-fold defaultstate="collapsed" desc="Preparation of multiple filters">
		foreach ($filter as $fId => &$fInfo)
		{
			foreach ($fInfo as $k => &$fElem)
			{
				if (is_array($fElem) && $fElem['type'] == 'field')
				{
					$columnName = '';

					if (preg_match('/__COLUMN__(\d+)/', $fElem['name'], $match))
					{
						$num = $match[1];
						$columnName = $viewColumns[$num]['fieldName'];
					}

					if ($fElem['name'] == $fieldName || $columnName == $fieldName)
					{
						$iblockFilterCompare = $fElem['compare'];
						if ($iblockFilterCompare === 'EQUAL'/* || $iblockFilterCompare === 'NOT_EQUAL'*/)
						{
							$iblockFilterValue = array();
							$arFilterValues = array();
							if (is_array($fElem['value'])) $arFilterValues = $fElem['value'];
							else $arFilterValues[] = $fElem['value'];
							foreach ($arFilterValues as $l => $filterValue)
							{
								if (is_string($filterValue) && $filterValue[0] == 'c')
								{
									$iblockFilterValue[] = intval(substr($filterValue, 1));
									// The filter on a section is deleted if the filter only according
									// to the catalog is necessary
									unset($arFilterValues[$l]);
								}
								else{
									$sections = array();
									foreach (parent::$catalogSections as $sectKey => $sect)
									{
										if ($sectKey == $filterValue) $iblockFilterValue[] = intval($sect['catalog']['ID']);
										if (in_array($filterValue, $sect['path'])) $sections[] = $sectKey;
									}
									$arFilterValues[$l] = $sections;
								}
							}
							$l = count($arFilterValues);
							if ($l === 0) unset($fInfo[$k]);
							else if ($l === 1) $fInfo[$k]['value'] = reset($arFilterValues);
							else
							{
								$newFilterValue = array();
								foreach ($arFilterValues as $filterValue)
								{
									$newFilterValue = array_merge($newFilterValue, $filterValue);
								}
								$newFilterValue = array_unique($newFilterValue);
								$fInfo[$k]['value'] = $newFilterValue;
							}
							$l = count($iblockFilterValue);
							if ($l === 0) $iblockFilterValue = null;
							else if ($l === 1) $iblockFilterValue = reset($iblockFilterValue);
							else $iblockFilterValue = array_unique($iblockFilterValue);
							self::$currentIblockFilter['value'] = $iblockFilterValue;
							self::$currentIblockFilter['compare'] = $iblockFilterCompare;
						}
					}

					// rewrite StoreProduct.STORE_ID
					if ($fElem['name'] == 'StoreProduct:SALE_PRODUCT.STORE_ID')
					{
						$fElem['name'] = 'StoreProduct:SALE_PRODUCT.STORE_ID';
					}
				}
			}
		}
		// </editor-fold>
	}

	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime)
	{
		global $DB, $DBType;

		if (function_exists('___dbCastIntToChar') !== true)
		{
			eval(
				'function ___dbCastIntToChar($dbtype, $param)'.
				'{'.
				'   $result = $param;'.
				'   if (ToLower($dbtype) === "mssql")'.
				'   {'.
				'       $result = "CAST(".$param." AS VARCHAR)";'.
				'   }'.
				'   return $result;'.
				'}'
			);
		}

		// Runtime fields
		if (self::$bUsePriceTypesColumns)
		{
			foreach (self::$priceTypes as $id => $info)
			{
				if ($info['selected'] === true)
				{
					$fieldName = 'PRICE_TYPE_'.$id;
					$runtime[$fieldName] = array(
						'data_type' => 'string',
						'expression' => array('
				(SELECT '.$DB->Concat(___dbCastIntToChar($DBType,'b_catalog_price.PRICE'), '\' \'', 'b_catalog_price.CURRENCY').'
				FROM b_catalog_price
					LEFT JOIN b_catalog_group ON b_catalog_group.ID = b_catalog_price.CATALOG_GROUP_ID
				WHERE   b_catalog_price.PRODUCT_ID = %s
					AND b_catalog_group.ID = '.$id.'
					AND ( b_catalog_price.quantity_from <= 1 OR b_catalog_price.quantity_from IS NULL )
					AND ( b_catalog_price.quantity_to >= 1 OR b_catalog_price.quantity_to IS NULL ))',
							'ID'
						),
						'view_column' => array(
							'humanTitle' => $info['name']
						)
					);
					$select[$fieldName] = $fieldName;
				}
			}
		}

		if (self::$currentIblockFilter['value'])
		{
			$filter[] = array(
				'LOGIC' => 'AND',
				((self::$currentIblockFilter['compare'] === 'EQUAL') ? '=' : '!=').'IBLOCK.IBLOCK_ID' => self::$currentIblockFilter['value']
			);
		}
		self::$currentIblockFilter['value'] = null;
		self::$currentIblockFilter['compare'] = null;
	}

	/* remove it when PHP 5.3 available */
	public static function formatResults(&$rows, &$columnInfo, $total)
	{
		foreach ($rows as &$row)
		{

			foreach ($row as $k => &$v)
			{
				if (!array_key_exists($k, $columnInfo))
				{
					continue;
				}

				$cInfo = &$columnInfo[$k];

				if (is_array($v))
				{
					foreach ($v as &$subv)
					{
						self::formatResultValue($k, $subv, $row, $cInfo, $total);
					}
				}
				else
				{
					self::formatResultValue($k, $v, $row, $cInfo, $total);
				}
			}
		}

		unset($row, $v, $subv);
	}
	/* \remove it */

	// format result value in grouping mode
	/*public static function formatResultGroupingValue($params)
	{
		$k = $params['k'];
		$v = $params['v'];
		$row = $params['row'];
		$cInfo = $params['cInfo'];
		static::formatResultValue($k, $v, $row, $cInfo, array());
	}*/

	public static function formatResultValue($k, &$v, &$row, &$cInfo, $total)
	{
		/** @var Bitrix\Main\Entity\Field[] $cInfo */
		if ($cInfo['field']->getDataType() !== 'float' )    // skip base rounding
		{
			parent::formatResultValue($k, $v, $row, $cInfo, $total);
		}

		// Inserting zero in numerical fields with null value.
		if (empty($v))
		{
			if (in_array($cInfo['field']->getDataType(), array('integer', 'float')))
			{
				$v = 0;
			}
			//else $v = '&nbsp;';
		}

		// Formatting of monetary fields.
		foreach (self::$monetaryFields as $monField)
		{
			if (preg_match('/'.$monField.'$/', $k))
			{
				$v = self::calculateInReportCurrency($v);
				$v = number_format($v, 2, '.', ' ');
				break;
			}
		}

		// Formatting fields with goods quantity.
		foreach (self::$goodsQuantityFields as $qField)
		{
			if (preg_match('/'.$qField.'$/', $k))
			{
				if (!empty($v) && !is_null($v) && $v != '&nbsp;')
				{
					if (self::$fDecimalQuant) $v = sprintf('%.3f', round($v,3));
					else $v = sprintf('%d', round($v,0));
				}
				break;
			}
		}

		// Formatting short name fields of buyers.
		foreach (self::$userNotAuthNameFields as $uField)
		{
			if (preg_match('/'.$uField.'$/', $k))
			{
				if (empty($v) || is_null($v) || $v == '&nbsp;')
				{
					$v = GetMessage('SALE_REPORT_USER_NOT_AUTH');
				}
				break;
			}
		}

		// runtime fields which align right
		if (self::$bUsePriceTypesColumns)
		{
			if (strpos($k, 'PRICE_TYPE_') === 0 && is_numeric(substr($k, 11))) $cInfo['align'] = 'right';
		}

		// Formatting fields of price types
		if (preg_match('/[A-Za-z_]*PRICE_TYPE_[0-9]+$/', $k) && !empty($v) && $v !== '&nbsp;')
		{
			$v = trim($v);
			$spacePos = strpos(trim($v), ' ');
			$v = number_format(floatval(substr($v, 0, $spacePos)), 2, '.', ' ').substr($v, $spacePos);
		}
	}

	public static function formatResultGroupingTotal($params)
	{
		$paramTotal = array('TOTAL_'.$params['k'] => &$params['v']);
		$viewColumns = array($params['k'] => &$params['cInfo']);
		static::formatResultsTotal($paramTotal, $viewColumns, true);
	}

	public static function formatResultsTotal(&$total, &$columnInfo, $bFormatOnly = false)
	{
		parent::formatResultsTotal($total, $columnInfo);

		foreach ($total as $k => &$v)
		{
			// Formatting of monetary fields.
			foreach (self::$monetaryFields as $monField)
			{
				if (preg_match('/'.$monField.'$/', $k))
				{
					if (!$bFormatOnly) $v = self::calculateInReportCurrency($v);
					$v = number_format($v, 2, '.', ' ');
					break;
				}
			}

			// Formatting fields with goods quantity.
			foreach (self::$goodsQuantityFields as $qField)
			{
				if (preg_match('/'.$qField.'$/', $k))
				{
					if (!empty($v) && !is_null($v) && $v != '&nbsp;')
					{
						if (self::$fDecimalQuant) $v = sprintf('%.3f', round($v,3));
						else $v = sprintf('%d', round($v,0));
					}
					break;
				}
			}

			// Formatting fields without summary calculation.
			foreach (self::$withoutTotalFields as $wtField)
			{
				if (preg_match('/TOTAL_'.$wtField.'$/', $k))
				{
					unset($total[$k]);
					break;
				}
			}

			// Formatting fields of price types
			if (!$bFormatOnly)
			{
				if (preg_match('/[A-Za-z_]*PRICE_TYPE_[0-9]+$/', $k) && !empty($v) && $v !== '&nbsp;')
				{
					$v = trim($v);
					$spacePos = strpos($v, ' ');
					$v = number_format(floatval(substr($v, 0, $spacePos)), 2, '.', ' ').substr($v, $spacePos);
				}
			}
		}
	}

	public static function getDefaultElemHref($elem, $fList)
	{
		$href = '';
		if (empty($elem['aggr']))
		{
			$field = $fList[$elem['name']];

			if ($field->getEntity()->getName() == 'Product')
			{
				if ($elem['name'] == 'NAME')
				{
					$href = array('pattern' => '/bitrix/admin/cat_product_edit.php?IBLOCK_ID='.
						'#IBLOCK.IBLOCK.ID#&type=#IBLOCK.IBLOCK.IBLOCK_TYPE_ID#&ID='.
						'#ID#&lang='.LANG.'&WF=Y');
				}
			}
		}

		return $href;
	}
}
