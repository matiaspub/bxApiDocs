<?php

namespace Bitrix\Sale\TradingPlatform\Ebay;

use Bitrix\Main\SystemException;
use Bitrix\Sale\TradingPlatform\MapTable;
use Bitrix\Sale\TradingPlatform\MapEntityTable;
use Bitrix\Sale\TradingPlatform\Platform;


/**
 * Class MapHelper
 * Useful mapping methods
 * @package Bitrix\Sale\TradingPlatform\Ebay
 */
class MapHelper
{
	/**
	 * @param int $iblockId Iblock id.
	 * @return string Category map entity code.
	 */
	public static function getCategoryEntityCode($iblockId)
	{
		return "CATEGORY_IBLOCK_".$iblockId;
	}

	/**
	 * @param int $iblockId Iblock id.
	 * @param int $ebayCategoryId Category id.
	 * @return string Category variation entity code.
	 */
	public static function getCategoryVariationEntityCode($iblockId, $ebayCategoryId)
	{
		return "CATEGORY_VAR_".$iblockId."_".$ebayCategoryId;
	}

	/**
	 * @param string  $siteId Site id.
	 * @return string Delivery entity code.
	 */
	public static function getDeliveryEntityCode($siteId)
	{
		return "DELIVERY_".$siteId;
	}

	/**
	 * @param string $siteId Site id.
	 * @return int Delivery entity id.
	 */
	public static function getDeliveryEntityId($siteId)
	{
		$deliveryEntCode = self::getDeliveryEntityCode($siteId);
		return self::getMapEntityId($deliveryEntCode);
	}

	/**
	 * @param int $iblockId Iblock id.
	 * @return int Category entity id.
	 */
	public static function getCategoryEntityId($iblockId)
	{
		$catMapEntCode = self::getCategoryEntityCode($iblockId);
		return self::getMapEntityId($catMapEntCode);
	}

	/**
	 * @param int $iblockId Iblock id.
	 * @param int $ebayCategoryId Category id.
	 * @return int Category variation entity id.
	 */
	public static function getCategoryVariationEntityId($iblockId, $ebayCategoryId)
	{
		$mapEntityCode = self::getCategoryVariationEntityCode($iblockId, $ebayCategoryId);
		return self::getMapEntityId($mapEntityCode);
	}

	/**
	 * @param string $mapEntityCode Map entity code
	 * @return int Map entity id.
	 * @throws \Bitrix\Main\SystemException
	 */
	protected static function getMapEntityId($mapEntityCode)
	{
		$result = 0;
		$ebay = Ebay::getInstance();

		$fields = array(
			"TRADING_PLATFORM_ID" => $ebay->getId(),
			"CODE" => $mapEntityCode
		);

		$catMapVarEntRes = MapEntityTable::getList(array(
			"filter" => $fields
		));

		if($arCatVarMapEnt = $catMapVarEntRes->fetch())
		{
			$result = $arCatVarMapEnt["ID"];
		}
		else
		{
			$addRes = MapEntityTable::add($fields);

			if($addRes->isSuccess())
				$result = $addRes->getId();
		}

		if($result <= 0)
			throw new SystemException("Can' t get map entity id for code: ".$mapEntityCode.".");

		return $result;
	}

	/**
	 * @param array $ebayDelivery Ebay deliveries ids.
	 * @param string $siteId Site id.
	 * @return array Bitrix delivery ids.
	 */
	static public function getBitrixDeliveryIds(array $ebayDelivery, $siteId)
	{
		$result = array();
		$deliveryEntId = self::getDeliveryEntityId($siteId);

		$deliveryRes = MapTable::getList(array(
			"filter" => array(
				"ENTITY_ID" => $deliveryEntId,
				"VALUE_EXTERNAL" => $ebayDelivery
			)
		));

		while($arMapRes = $deliveryRes->fetch())
			$result[$arMapRes["VALUE_EXTERNAL"]] =  $arMapRes["VALUE_INTERNAL"];

		return $result;
	}
} 