<?php
namespace Bitrix\Sale\Delivery\Restrictions;

use Bitrix\Sale\Delivery;

/**
 * Class Base.
 * Base class for delivery services restrictions.
 * @package Bitrix\Sale\Delivery
 */
abstract class Base {

	/** @var int
	 * 100 - lightweight - just compare with params
	 * 200 - middleweight - may be use base queries
	 * 300 - hardweight - use base, and/or hard calculations
	 * */
	public static $easeSort = 100;


	public static function getClassTitle()
	{
		return "";
	}

	public static function getClassDescription()
	{
		return "";
	}

	/**
	 * @param $params
	 * @param array $restrictionParams
	 * @param int $deliveryId
	 * @return mixed
	 */
	abstract public function check($params, array $restrictionParams, $deliveryId = 0);

	/**
	 * Checks if order params satisfy this restriction conditions
	 * @param \Bitrix\Sale\Shipment $shipment Order params.
	 * @param array $restrictionDeliveryParams Base params.
	 * @param int $deliveryId.
	 * @return bool
	 */
	abstract public function checkByShipment(\Bitrix\Sale\Shipment $shipment, array $restrictionParams, $deliveryId = 0);

	/**
	 * Returns params structure to show it to user
	 * @return array
	 */
	public static function getParamsStructure($deliveryId = 0)
	{
		return array();
	}

	public static function prepareParamsValues(array $paramsValues, $deliveryId = 0)
	{
		return $paramsValues;
	}

	static public function save(array $fields, $restrictionId = 0)
	{
		$fields["CLASS_NAME"] = '\\'.get_class($this);

		if($restrictionId > 0)
			$res = \Bitrix\Sale\Delivery\Restrictions\Table::update($restrictionId, $fields);
		else
			$res = \Bitrix\Sale\Delivery\Restrictions\Table::add($fields);

		return $res;
	}

	static public function delete($restrictionId, $deliveryId = 0)
	{
		return \Bitrix\Sale\Delivery\Restrictions\Table::delete($restrictionId);
	}
}