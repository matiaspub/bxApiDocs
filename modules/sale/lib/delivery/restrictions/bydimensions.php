<?php
namespace Bitrix\Sale\Delivery\Restrictions;

use Bitrix\Sale\Delivery\Restrictions;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class ByDimensions
 * Restricts delivery by order dimensions.
 * @package Bitrix\Sale\Delivery\Restrictions
 */
class ByDimensions extends Restrictions\Base
{
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_DIMENSIONS_NAME");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_DIMENSIONS_DESCRIPT");
	}

	/**
	 * @param array $dimensions keys:(LENGTH, WIDTH, HEIGHT)
	 * @param array $restrictionParams
	 * @param int $deliveryId
	 * @return bool
	 */
	static public function check($dimensions, array $restrictionParams, $deliveryId = 0)
	{
		foreach($restrictionParams as $name => $value) //LENGTH, WIDTH, HEIGHT
		{
			if($value <=0)
				continue;

			if(!isset($dimensions[$name]))
				continue;

			if(intval($dimensions[$name]) <= 0)
				continue;

			if(intval($dimensions[$name]) > intval($value))
				return false;
		}

		return true;
	}

	public function checkByShipment(\Bitrix\Sale\Shipment $shipment, array $restrictionParams, $deliveryId = 0)
	{
		if(empty($restrictionParams))
			return true;

		foreach($shipment->getShipmentItemCollection() as $shipmentItem)
		{
			$basketItem = $shipmentItem->getBasketItem();
			$dimensions = $basketItem->getField("DIMENSIONS");

			if(is_string($dimensions))
				$dimensions = unserialize($dimensions);

			if(!is_array($dimensions) || empty($dimensions))
				return true;

			if(!$this->check($dimensions, $restrictionParams, $deliveryId))
				return false;
		}

		return true;
	}

	public static function getParamsStructure()
	{
		return array(
			"LENGTH" => array(
				'TYPE' => 'NUMBER',
				'DEFAULT' => "0",
				'MIN' => 0,
				'LABEL' => Loc::getMessage("SALE_DLVR_RSTR_BY_DIMENSIONS_LENGTH")
			),
			"WIDTH" => array(
				'TYPE' => 'NUMBER',
				'DEFAULT' => "0",
				'MIN' => 0,
				'LABEL' => Loc::getMessage("SALE_DLVR_RSTR_BY_DIMENSIONS_WIDTH")
			),
			"HEIGHT" => array(
				'TYPE' => 'NUMBER',
				'DEFAULT' => "0",
				'MIN' => 0,
				'LABEL' => Loc::getMessage("SALE_DLVR_RSTR_BY_DIMENSIONS_HEIGHT")
			)
		);
	}
} 