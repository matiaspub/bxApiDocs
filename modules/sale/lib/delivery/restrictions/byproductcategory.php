<?php
namespace Bitrix\Sale\Delivery\Restrictions;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\CollectableEntity;

Loc::loadMessages(__FILE__);

/**
 * Class ByProductCategory
 * Restricts delivery by product category
 * @package Bitrix\Sale\Delivery\Restrictions
 */
class ByProductCategory extends Base
{
	public static $easeSort = 400;

	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_PC_NAME");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_PC_DESCRIPT");
	}

	public static function check($categoriesList, array $restrictionParams, $deliveryId = 0)
	{
		if(
			empty($categoriesList)
			|| !is_array($categoriesList)
			|| empty($restrictionParams["CATEGORIES"])
			|| !is_array($restrictionParams["CATEGORIES"]))
		{
			return true;
		}

		foreach($categoriesList as $category)
		{
			$categoryPath = self::getCategoriesPath($category);

			if(!array_intersect($categoryPath, $restrictionParams["CATEGORIES"]))
				return false;
		}

		return true;
	}

    protected static function getCategoriesPath($categoryId)
	{
		$result = array($categoryId);

		$nav = \CIBlockSection::GetNavChain(false, $categoryId);

		while($arSectionPath = $nav->GetNext())
			if(!in_array($arSectionPath['ID'], $result))
				$result[] = $arSectionPath['ID'];

		return $result;
	}

	public static function extractParams(CollectableEntity $shipment)
	{
		if(!\Bitrix\Main\Loader::includeModule('iblock'))
			return array();

		if(!\Bitrix\Main\Loader::includeModule('catalog'))
			return array();

		$productIds = array();

		/** @var \Bitrix\Sale\ShipmentItem $shipmentItem */
		foreach($shipment->getShipmentItemCollection() as $shipmentItem)
		{
			/** @var \Bitrix\Sale\BasketItem $basketItem */
			$basketItem = $shipmentItem->getBasketItem();

			if($basketItem->getField('MODULE') != 'catalog')
				continue;

			$productId = intval($basketItem->getField('PRODUCT_ID'));
			$iblockId = (int)\CIBlockElement::getIBlockByID($productId);
			$info = \CCatalogSKU::getProductInfo($productId, $iblockId);

			if(!empty($info['ID']))
				$candidate = $info['ID'];
			else
				$candidate = $productId;

			if(!in_array($candidate, $productIds))
				$productIds[] = $candidate;
		}

		return  self::getGroupsIds($productIds);
	}

	protected static function getGroupsIds(array $productIds)
	{
		$groupsIds = array();

		$res = \CIBlockElement::GetElementGroups($productIds, true, array('ID'));

		while($group = $res->Fetch())
			if(!in_array($group['ID'], $groupsIds))
				$groupsIds[] = $group['ID'];

		return $groupsIds;
	}

	public static function getParamsStructure($deliveryId = 0)
	{
		$result =  array(
			"CATEGORIES" => array(
				"TYPE" => "DELIVERY_PRODUCT_CATEGORIES",
				"URL" => "/bitrix/admin/cat_section_search.php?lang=ru&m=y&n=SECTIONS_IDS",
				"SCRIPT" => "window.InS".md5('SECTIONS_IDS')."=function(id, name){BX.Sale.Delivery.addRestrictionProductSection(id, name, this);};",
				"LABEL" => Loc::getMessage("SALE_DLVR_RSTR_BY_PC_CATEGORIES"),
				"ID" => 'sale-admin-delivery-restriction-cat-add'
			)
		);

		return $result;
	}
}