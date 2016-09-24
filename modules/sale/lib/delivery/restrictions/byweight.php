<?php
namespace Bitrix\Sale\Delivery\Restrictions;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\CollectableEntity;

Loc::loadMessages(__FILE__);

/**
 * Class ByWeight
 * Restricts delivery by weight
 * @package Bitrix\Sale\Delivery\Restrictions
 */
class ByWeight extends Base
{
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_WEIGHT_NAME");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_WEIGHT_DESCRIPT");
	}

	public static function check($weight, array $restrictionParams, $deliveryId = 0)
	{
		if(empty($restrictionParams))
			return true;

		$weight = floatval($weight);

		if(isset($restrictionParams["MIN_WEIGHT"]) && floatval($restrictionParams["MIN_WEIGHT"]) > 0  && $weight < floatval($restrictionParams["MIN_WEIGHT"]))
			return false;

		if(isset($restrictionParams["MAX_WEIGHT"]) && floatval($restrictionParams["MAX_WEIGHT"]) > 0 && $weight > floatval($restrictionParams["MAX_WEIGHT"]))
			return false;

		return true;
	}

	protected static function extractParams(CollectableEntity $shipment)
	{
		return $shipment->getWeight();
	}

	public static function getParamsStructure($entityId = 0)
	{
		return array(
			"MIN_WEIGHT" => array(
				'TYPE' => 'NUMBER',
				'DEFAULT' => "0",
				'MIN' => 0,
				'LABEL' => Loc::getMessage("SALE_DLVR_RSTR_BY_WEIGHT_MIN_WEIGHT")
			),
			"MAX_WEIGHT" => array(
				'TYPE' => 'NUMBER',
				'DEFAULT' => "0",
				'MIN' => 0,
				'LABEL' => Loc::getMessage("SALE_DLVR_RSTR_BY_WEIGHT_MAX_WEIGHT")
			)
		);
	}
} 