<?php
namespace Bitrix\Seo;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\BasketTable;
use Bitrix\Sale\ProductTable;
use Bitrix\Seo\Adv\LinkTable;
use Bitrix\Seo\Adv\OrderTable;
use Bitrix\Seo\Adv\YandexBannerTable;
use Bitrix\Seo\Engine\YandexDirect;

Loc::loadMessages(__FILE__);

/**
 * Class AdvSession
 *
 * Events handler for managing users came from yandex.direct ad
 *
 * @package Bitrix\Seo
 **/
class AdvSession
{
	const URL_PARAM_CAMPAIGN = 'bxydcampaign';
	const URL_PARAM_CAMPAIGN_VALUE = '{campaign_id}';
	const URL_PARAM_BANNER = 'bxydbanner';
	const URL_PARAM_BANNER_VALUE = '{banner_id}';

	protected static $orderHandlerCalled = array();

	public static function checkSession()
	{
		$request = Main\Context::getCurrent()->getRequest();
		if(
			isset($request[static::URL_PARAM_CAMPAIGN])
			&& isset($request[static::URL_PARAM_BANNER])
		)
		{
			$_SESSION['SEO_ADV'] = array(
				"ENGINE" => YandexDirect::ENGINE_ID,
				"CAMPAIGN_ID" => $request[static::URL_PARAM_CAMPAIGN],
				"BANNER_ID" => $request[static::URL_PARAM_BANNER],
			);
		}
	}

	public static function isSession()
	{
		return isset($_SESSION['SEO_ADV']);
	}

	public static function getSession()
	{
		return $_SESSION['SEO_ADV'];
	}

	public static function onOrderSave($orderId, $orderFields, $orderData, $isNew)
	{
		if($isNew)
		{
			static::checkSessionOrder($orderId);
		}
	}

	public static function onBasketOrder($orderId)
	{
		static::checkSessionOrder($orderId);
	}

	public static function onSalePayOrder($orderId, $val)
	{
		if($val == 'Y')
		{
			static::countSessionOrder($orderId);
		}
	}

	public static function onSaleDeductOrder($orderId, $val)
	{
		if($val == 'Y')
		{
			static::countSessionOrder($orderId);
		}
	}
	public static function onSaleDeliveryOrder($orderId, $val)
	{
		if($val == 'Y')
		{
			static::countSessionOrder($orderId);
		}
	}

	public static function onSaleStatusOrder($orderId, $status)
	{
		if($status == 'F')
		{
			static::countSessionOrder($orderId);
		}
	}

	protected static function checkSessionOrder($orderId)
	{
		if(
			isset($_SESSION['SEO_ADV'])
			&& is_array($_SESSION['SEO_ADV'])
			&& $_SESSION['SEO_ADV']['BANNER_ID'] > 0
			&& !in_array($orderId, static::$orderHandlerCalled)
			&& Main\Loader::includeModule('sale')
			&& Main\Loader::includeModule('catalog')
		)
		{
			static::$orderHandlerCalled[] = $orderId;

			$banner = null;
			switch($_SESSION['SEO_ADV']['ENGINE'])
			{
				case YandexDirect::ENGINE_ID:
					$dbRes = YandexBannerTable::getList(array(
						'filter' => array(
							'=XML_ID' => $_SESSION['SEO_ADV']['BANNER_ID'],
							'=ENGINE.CODE' => YandexDirect::ENGINE_ID,
						),
						'select' => array(
							'ID', 'CAMPAIGN_ID', 'ENGINE_ID',
						)
					));
					$banner = $dbRes->fetch();
					break;
			}

			if($banner)
			{
				$linkedProductsList = static::getBannerLinkedProducts($banner['ID']);

				if(count($linkedProductsList) > 0)
				{
					$basket = BasketTable::getList(array(
						'filter' => array(
							'=ORDER_ID' => $orderId,
						),
						'select' => array('PRODUCT_ID'),
					));

					$addEntry = false;
					while($item = $basket->fetch())
					{
						if(in_array($item['PRODUCT_ID'], $linkedProductsList))
						{
							$addEntry = true;
							break;
						}
						else
						{
							$productInfo = \CCatalogSKU::GetProductInfo($item['PRODUCT_ID']);

							if(is_array($productInfo) && in_array($productInfo['ID'], $linkedProductsList))
							{
								$addEntry = true;
								break;
							}
						}
					}

					if($addEntry)
					{
						$entryData = array(
							'ENGINE_ID' => $banner['ENGINE_ID'],
							'CAMPAIGN_ID' => $banner['CAMPAIGN_ID'],
							'BANNER_ID' => $banner['ID'],
							'ORDER_ID' => $orderId,
							'SUM' => 0,
							'PROCESSED' => OrderTable::NOT_PROCESSED,
						);

						OrderTable::add($entryData);
					}
				}
			}
		}
	}

	protected static function countSessionOrder($orderId)
	{
		if(
			Main\Loader::includeModule('sale')
			&& Main\Loader::includeModule('catalog')
			&& Main\Loader::includeModule('currency')
		)
		{
			$orderLinks = OrderTable::getList(array(
				'filter' => array(
					'=ORDER_ID' => $orderId,
					'=PROCESSED' => OrderTable::NOT_PROCESSED,
				),
				'select' => array('ID', 'BANNER_ID')
			));
			$orderLink = $orderLinks->fetch();
			if($orderLink)
			{
				$linkedProductsList = static::getBannerLinkedProducts($orderLink['BANNER_ID']);
				if(count($linkedProductsList) > 0)
				{
					$basket = BasketTable::getList(array(
						'filter' => array(
							'=ORDER_ID' => $orderId,
						),
						'select' => array('PRODUCT_ID', 'GROSS_PROFIT', 'SUMMARY_PRICE', 'SUMMARY_PURCHASING_PRICE', 'QUANTITY'),
					));

					$sum = 0;
					while($item = $basket->fetch())
					{
						if(in_array($item['PRODUCT_ID'], $linkedProductsList))
						{
							$sum += static::getProductProfit($item);
						}
						else
						{
							$productInfo = \CCatalogSKU::GetProductInfo($item['PRODUCT_ID']);

							if(is_array($productInfo) && in_array($productInfo['ID'], $linkedProductsList))
							{
								$sum += static::getProductProfit($item);
							}
						}
					}

					OrderTable::update($orderLink['ID'], array(
						'SUM' => $sum,
						'PROCESSED' => OrderTable::PROCESSED,
					));
				}
			}
		}
	}

	protected static function getBannerLinkedProducts($bannerId)
	{
		$linkedProductsList = array();
		$linkedProducts = LinkTable::getList(
			array(
				'filter' => array(
					'=LINK_TYPE' => LinkTable::TYPE_IBLOCK_ELEMENT,
					'=BANNER_ID' => $bannerId,
				),
				'select' => array(
					'LINK_ID'
				)
			)
		);

		while($link = $linkedProducts->fetch())
		{
			$linkedProductsList[] = $link['LINK_ID'];
		}

		return $linkedProductsList;
	}

	// TODO: remove all this math when /sale_refactoring_mince releases
	protected static function getProductProfit($productInfo)
	{
		if(strlen($productInfo['GROSS_PROFIT']) > 0)
		{
			$profit = doubleval($productInfo['GROSS_PROFIT']);
		}
		else
		{
			$purchasingCost = 0;
			if(strlen($productInfo['SUMMARY_PURCHASING_PRICE']) > 0)
			{
				$purchasingCost = doubleval($productInfo['SUMMARY_PURCHASING_PRICE']);
			}
			else
			{
				$dbRes = ProductTable::getList(array(
					'filter' => array(
						'=ID' => $productInfo['PRODUCT_ID'],
					),
					'select' => array(
						'ID', 'PURCHASING_PRICE', 'PURCHASING_CURRENCY'
					)
				));
				$productInfoBase = $dbRes->fetch();
				if($productInfoBase)
				{
					$purchasingCost = $productInfoBase['PURCHASING_PRICE'] * $productInfo['QUANTITY'];

					$baseCurrency = CurrencyManager::getBaseCurrency();
					if($baseCurrency != $productInfoBase['PURCHASING_CURRENCY'])
					{
						$purchasingCost = \CCurrencyRates::convertCurrency(
							$purchasingCost,
							$productInfoBase['PURCHASING_CURRENCY'],
							$baseCurrency
						);
					}
				}
			}

			$profit = doubleval($productInfo['SUMMARY_PRICE'])-$purchasingCost;
		}
		return $profit;
	}
}