<?php
namespace Bitrix\Sale\Delivery\Restrictions;

use Bitrix\Sale\Delivery\Restrictions;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\CollectableEntity;

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
	public static function check($dimensionsList, array $restrictionParams, $deliveryId = 0)
	{
		if(empty($restrictionParams))
			return true;

		$maxSize = intval($restrictionParams["MAX_SIZE"]);

		if($maxSize <= 0)
			return true;

		foreach($dimensionsList as $dimensions)
		{
			if(!is_array($dimensions))
				continue;

			foreach($dimensions as $dimension)
			{
				if(intval($dimension) <= 0)
					continue;

				if(intval($dimension) > $maxSize)
					return false;
			}
		}

		return true;
	}

	protected static function extractParams(CollectableEntity $shipment)
	{
		$result = array();

		foreach($shipment->getShipmentItemCollection() as $shipmentItem)
		{
			$basketItem = $shipmentItem->getBasketItem();
			$dimensions = $basketItem->getField("DIMENSIONS");

			if(is_string($dimensions))
				$dimensions = unserialize($dimensions);

			$result[] = $dimensions;
		}

		return $result;
	}

	public static function getParamsStructure($entityId = 0)
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