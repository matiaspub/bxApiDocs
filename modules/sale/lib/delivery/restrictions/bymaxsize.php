<?php
namespace Bitrix\Sale\Delivery\Restrictions;

use Bitrix\Sale\Delivery\Restrictions;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class ByMaxSize
 * Restricts delivery by basket items max size.
 * @package Bitrix\Sale\Delivery\Restrictions
 */
class ByMaxSize extends Restrictions\Base
{
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_MAXSIZE_NAME");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_MAXSIZE_DESCRIPT");
	}

	/**
	 * @param $dimensions
	 * @param array $restrictionParams
	 * @param int $deliveryId
	 * @return bool
	 */
	static public function check($dimensions, array $restrictionParams, $deliveryId = 0)
	{
		$maxSize = intval($restrictionParams["MAX_SIZE"]);

		foreach($dimensions as $dimension)
		{
			if(intval($dimension) <= 0)
				continue;

			if(intval($dimension) > $maxSize)
				return false;
		}

		return true;
	}

	/**
	 * @param \Bitrix\Sale\Shipment $shipment
	 * @param array $restrictionParams
	 * @param int $deliveryId
	 * @return bool
	 */
	public function checkByShipment(\Bitrix\Sale\Shipment $shipment, array $restrictionParams, $deliveryId = 0)
	{
		if(empty($restrictionParams))
			return true;

		$maxSize = intval($restrictionParams["MAX_SIZE"]);

		if($maxSize <= 0)
			return true;

		foreach($shipment->getShipmentItemCollection() as $shipmentItem)
		{
			$basketItem = $shipmentItem->getBasketItem();
			$dimensions = $basketItem->getField("DIMENSIONS");

			if(is_string($dimensions))
				$dimensions = unserialize($dimensions);

			if(!is_array($dimensions))
				return true;

			if(!$this->check($dimensions, $restrictionParams, $deliveryId))
				return false;
		}

		return true;
	}

	public static function getParamsStructure()
	{
		return array(
			"MAX_SIZE" => array(
				'TYPE' => 'NUMBER',
				'DEFAULT' => "0",
				'MIN' => 0,
				'LABEL' => Loc::getMessage("SALE_DLVR_RSTR_BY_MAXSIZE_SIZE")
			)
		);
	}
} 