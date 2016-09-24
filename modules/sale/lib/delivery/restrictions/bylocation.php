<?php
namespace Bitrix\Sale\Delivery\Restrictions;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\DeliveryLocationTable;
use Bitrix\Sale\Internals\CollectableEntity;
use Bitrix\Sale\Location\Connector;
use Bitrix\Sale\Location\GroupLocationTable;
use Bitrix\Sale\Location\LocationTable;
use Bitrix\Sale\Shipment;

Loc::loadMessages(__FILE__);

/**
 * Class ByLocation
 * Restricts delivery by location(s)
 * @package Bitrix\Sale\Delivery\Restrictions
 */
class ByLocation extends Base
{
	public static $easeSort = 200;

	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_LOCATION_NAME");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_LOCATION_DESCRIPT");
	}

	/**
	 * This function should accept only location CODE, not ID, being a part of modern API
	 */
	
	/**
	* <p>Метод проверяет, подходит ли служба доставки для данного местоположения. Метод статический.</p>
	*
	*
	* @param integer $locationCode  Код местоположения.
	*
	* @param array $restrictionParams  Параметры ограничения.
	*
	* @param integer $deliveryId = 0 Идентификатор доставки.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/delivery/restrictions/bylocation/check.php
	* @author Bitrix
	*/
	public static function check($locationCode, array $restrictionParams, $deliveryId = 0)
	{
		if(intval($deliveryId) <= 0)
			return true;

		if(strlen($locationCode) <= 0)
			return false;

		try
		{
			return DeliveryLocationTable::checkConnectionExists(
				intval($deliveryId),
				$locationCode,
				array(
					'LOCATION_LINK_TYPE' => 'AUTO'
				)
			);
		}
		catch(\Bitrix\Sale\Location\Tree\NodeNotFoundException $e)
		{
			return false;
		}
	}

	protected static function extractParams(CollectableEntity $shipment)
	{
		/** @var \Bitrix\Sale\Order $order */
		$order = $shipment->getCollection()->getOrder();

		if(!$props = $order->getPropertyCollection())
			return '';

		if(!$locationProp = $props->getDeliveryLocation())
			return '';

		if(!$locationCode = $locationProp->getValue())
			return '';

		return $locationCode;
	}

	protected static function prepareParamsForSaving(array $params = array(), $deliveryId = 0)
	{
		if($deliveryId > 0)
		{
			$arLocation = array();

			if(!!\CSaleLocation::isLocationProEnabled())
			{
				if(strlen($params["LOCATION"]['L']))
					$LOCATION1 = explode(':', $params["LOCATION"]['L']);

				if(strlen($params["LOCATION"]['G']))
					$LOCATION2 = explode(':', $params["LOCATION"]['G']);
			}

			if (isset($LOCATION1) && is_array($LOCATION1) && count($LOCATION1) > 0)
			{
				$arLocation["L"] = array();
				$locationCount = count($LOCATION1);

				for ($i = 0; $i<$locationCount; $i++)
					if (strlen($LOCATION1[$i]))
						$arLocation["L"][] = $LOCATION1[$i];
			}

			if (isset($LOCATION2) && is_array($LOCATION2) && count($LOCATION2) > 0)
			{
				$arLocation["G"] = array();
				$locationCount = count($LOCATION2);

				for ($i = 0; $i<$locationCount; $i++)
					if (strlen($LOCATION2[$i]))
						$arLocation["G"][] = $LOCATION2[$i];

			}

			DeliveryLocationTable::resetMultipleForOwner($deliveryId, $arLocation);
		}

		return array();
	}

	public static function getParamsStructure($deliveryId = 0)
	{

		$result =  array(
			"LOCATION" => array(
				"TYPE" => "LOCATION_MULTI"
				//'LABEL' => Loc::getMessage("SALE_DLVR_RSTR_BY_LOCATION_LOC"),
			)
		);

		if($deliveryId > 0 )
			$result["LOCATION"]["DELIVERY_ID"] = $deliveryId;

		return $result;
	}

	public static function save(array $fields, $restrictionId = 0)
	{
		$fields["PARAMS"] = self::prepareParamsForSaving($fields["PARAMS"], $fields["SERVICE_ID"]);
		return parent::save($fields, $restrictionId);
	}

	public static function delete($restrictionId, $deliveryId = 0)
	{
		DeliveryLocationTable::resetMultipleForOwner($deliveryId);
		return parent::delete($restrictionId);
	}

	/**
	 * @param Shipment $shipment
	 * @param array $restrictionFields
	 * @return array
	 */
	public static function filterServicesArray(Shipment $shipment, array $restrictionFields)
	{
		if(empty($restrictionFields))
			return array();

		$shpLocCode = self::extractParams($shipment);

		//if location not defined in shipment
		if(strlen($shpLocCode) < 0)
			return array_keys($restrictionFields);

		$res = LocationTable::getList(array(
			'filter' => array('=CODE' => $shpLocCode),
			'select' => array('CODE', 'LEFT_MARGIN', 'RIGHT_MARGIN')
		));

		//if location doesn't exists
		if(!$shpLocParams = $res->fetch())
			return array_keys($restrictionFields);

		$result = array();
		$srvLocCodesCompat = self::getLocationsCompat($restrictionFields, $shpLocParams['LEFT_MARGIN'], $shpLocParams['RIGHT_MARGIN']);

		foreach($srvLocCodesCompat as $locCode => $deliveries)
			foreach($deliveries as $deliveryId)
				if(!in_array($deliveryId, $result))
					$result[] = $deliveryId;

		return $result;
	}

	/**
	 * @param array $restrictionFields
	 * @param $leftMargin
	 * @param $rightMargin
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected static function getLocationsCompat(array $restrictionFields, $leftMargin, $rightMargin)
	{
		$result = array();
		$groups = array();

		$res = DeliveryLocationTable::getList(array(
			'filter' => array(
				'=DELIVERY_ID' => array_keys($restrictionFields),
				array(
					'LOGIC' => 'OR',
					array(
						'LOGIC' => 'AND',
						'=LOCATION_TYPE' => Connector::DB_LOCATION_FLAG,
						'<=LOCATION.LEFT_MARGIN' => $leftMargin,
						'>=LOCATION.RIGHT_MARGIN' => $rightMargin
					),
					array(
						'LOGIC' => 'AND',
						'=LOCATION_TYPE' => Connector::DB_GROUP_FLAG
					)
				)
			)
		));

		while($d2l = $res->fetch())
		{
			if($d2l['LOCATION_TYPE'] == Connector::DB_LOCATION_FLAG)
			{
				if(!is_array($result[$d2l['LOCATION_CODE']]))
					$result[$d2l['LOCATION_CODE']] = array();

				if(!in_array($d2l['DELIVERY_ID'] ,$result[$d2l['LOCATION_CODE']]))
					$result[$d2l['LOCATION_CODE']][] = $d2l['DELIVERY_ID'];
			}
			else
			{
				if(!is_array($groups[$d2l['LOCATION_CODE']]))
					$groups[$d2l['LOCATION_CODE']] = array();

				if(!in_array($d2l['DELIVERY_ID'] ,$groups[$d2l['LOCATION_CODE']]))
					$groups[$d2l['LOCATION_CODE']][] = $d2l['DELIVERY_ID'];
			}
		}

		//groups
		if(!empty($groups))
		{
			$res = GroupLocationTable::getList(array(
				'filter' => array(
					'=GROUP.CODE' => array_keys($groups),
					'<=LOCATION.LEFT_MARGIN' => $leftMargin,
					'>=LOCATION.RIGHT_MARGIN' => $rightMargin
				),
				'select' => array(
					'LOCATION_ID', 'LOCATION_GROUP_ID',
					'LOCATION_CODE' => 'LOCATION.CODE',
					'GROUP_CODE' => 'GROUP.CODE'
				)
			));

			while($loc = $res->fetch())
			{
				if(!is_array($result[$loc['LOCATION_CODE']]))
					$result[$loc['LOCATION_CODE']] = array();

				foreach($groups[$loc['GROUP_CODE']] as $srvId)
					if(!in_array($srvId, $result[$loc['LOCATION_CODE']]))
						$result[$loc['LOCATION_CODE']][] = $srvId;
			}
		}

		return $result;
	}
}