<?php
namespace Bitrix\Sale\Delivery\Restrictions;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\DeliveryPaySystemTable;
use Bitrix\Sale\Internals\PaySystemInner;

Loc::loadMessages(__FILE__);

/**
 * Class ByPaySystem
 * @package Bitrix\Sale\Delivery\Restrictions
 */
class ByPaySystem extends Base
{
	public static $easeSort = 200;
	protected static $preparedData = array();

	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_PAYSYSTEM_NAME");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_PAYSYSTEM_DESCRIPT");
	}

	public function check($paySystemId, array $restrictionParams, $deliveryId = 0)
	{
		$paySystems = $this->getPaySystemsByDeliveryId($deliveryId);
		return empty($paySystems) || in_array($paySystemId, $paySystems);
	}

	public function checkByShipment(\Bitrix\Sale\Shipment $shipment, array $restrictionParams, $deliveryId = 0)
	{
			if(intval($deliveryId) <= 0)
			return true;

		$paymentsCount = 0;
		$paySystemId = 0;

		/** @var \Bitrix\Sale\Payment $payment */
		foreach($shipment->getCollection()->getOrder()->getPaymentCollection() as $payment)
		{
			if($payment->getId() != PaySystemInner::getId())
			{
				$paymentsCount++;
				$paySystemId = $payment->getPaymentSystemId();
			}
		}

		if($paymentsCount <= 0 || $paymentsCount > 1 || $paySystemId <= 0)
			return true;

		return $this->check($paySystemId, $restrictionParams, $deliveryId);
	}

	protected static function getPaySystemsList()
	{
		static $result = null;

		if($result !== null)
			return $result;

		$result = array();

		$dbResultList = \CSalePaySystem::GetList(
			array("SORT"=>"ASC", "NAME"=>"ASC"),
			array("ACTIVE" => "Y"),
			false,
			false,
			array("ID", "NAME", "ACTIVE", "SORT", "LID")
		);

		while ($arPayType = $dbResultList->Fetch())
		{
			$name = (strlen($arPayType["LID"]) > 0) ? htmlspecialcharsbx($arPayType["NAME"]). " (".$arPayType["LID"].")" : htmlspecialcharsbx($arPayType["NAME"]);
			$result[$arPayType["ID"]] = $name;
		}

		return $result;
	}

	public static function getParamsStructure()
	{
		$result =  array(
			"PAY_SYSTEMS" => array(
				"TYPE" => "ENUM",
				'MULTIPLE' => 'Y',
				"LABEL" => Loc::getMessage("SALE_DLVR_RSTR_BY_PAYSYSTEM_PRM_PS"),
				"OPTIONS" => self::getPaySystemsList()
			)
		);

		return $result;
	}

	protected static function getPaySystemsByDeliveryId($deliveryId = 0)
	{
		if($deliveryId == 0)
			return array();

		$result = DeliveryPaySystemTable::getLinks($deliveryId, DeliveryPaySystemTable::ENTITY_TYPE_DELIVERY, self::$preparedData);
		return $result;
	}

	protected static function prepareParamsForSaving(array $params = array(), $deliveryId = 0)
	{
		if(intval($deliveryId) <= 0)
			return $params;

		if(isset($params["PAY_SYSTEMS"]) && is_array($params["PAY_SYSTEMS"]))
		{
			DeliveryPaySystemTable::setLinks(
				$deliveryId,
				DeliveryPaySystemTable::ENTITY_TYPE_DELIVERY,
				$params["PAY_SYSTEMS"],
				false
			);

			unset($params["PAY_SYSTEMS"]);
		}

		return $params;
	}

	public function save(array $fields, $restrictionId = 0)
	{
		$fields["PARAMS"] = $this->prepareParamsForSaving($fields["PARAMS"], $fields["DELIVERY_ID"]);
		return parent::save($fields, $restrictionId);
	}

	public static function prepareParamsValues(array $paramsValues, $deliveryId = 0)
	{
		return array("PAY_SYSTEMS" =>  self::getPaySystemsByDeliveryId($deliveryId));
	}

	static public function delete($restrictionId, $deliveryId)
	{
		DeliveryPaySystemTable::setLinks(
			$deliveryId,
			DeliveryPaySystemTable::ENTITY_TYPE_DELIVERY,
			array(),
			false
		);

		return parent::delete($restrictionId);
	}

	static public function prepareData(array $deliveryIds)
	{
		if(empty($deliveryIds))
			return;

		self::$preparedData = \Bitrix\Sale\Internals\DeliveryPaySystemTable::prepareData($deliveryIds, DeliveryPaySystemTable::ENTITY_TYPE_DELIVERY);
	}
} 