<?php
namespace Bitrix\Sale\Delivery\Restrictions;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\CollectableEntity;
use Bitrix\Sale\Internals\DeliveryPaySystemTable;
use Bitrix\Sale\Internals\PaySystemInner;
use Bitrix\Sale\PaySystem;

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

	public static function check($paySystemIds, array $restrictionParams, $deliveryId = 0)
	{
		if(intval($deliveryId) <= 0)
			return true;

		if(empty($paySystemIds))
			return true;

		$paySystems = self::getPaySystemsByDeliveryId($deliveryId);

		if(empty($paySystems))
			return true;

		$diff = array_diff($paySystemIds, $paySystems);

		return empty($diff);
	}

	protected static function extractParams(CollectableEntity $shipment)
	{
		$result = array();

		/** @var \Bitrix\Sale\ShipmentCollection $collection */
		$collection = $shipment->getCollection();

		/** @var \Bitrix\Sale\Order $order */
		$order = $collection->getOrder();

		/** @var \Bitrix\Sale\Payment $payment */
		foreach($order->getPaymentCollection() as $payment)
		{
			$paySystemId = $payment->getPaymentSystemId();
			if ($paySystemId)
				$result[] = $paySystemId;
		}

		return $result;
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

	public static function getParamsStructure($entityId = 0)
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
				true
			);

			unset($params["PAY_SYSTEMS"]);
		}

		return $params;
	}

	public static function save(array $fields, $restrictionId = 0)
	{
		$params = $fields["PARAMS"];
		$fields["PARAMS"] = array();

		$result = parent::save($fields, $restrictionId);

		self::prepareParamsForSaving($params, $fields["SERVICE_ID"]);
		return $result;
	}

	public static function prepareParamsValues(array $paramsValues, $deliveryId = 0)
	{
		return array("PAY_SYSTEMS" =>  self::getPaySystemsByDeliveryId($deliveryId));
	}

	public static function delete($restrictionId, $deliveryId = 0)
	{
		DeliveryPaySystemTable::setLinks(
			$deliveryId,
			DeliveryPaySystemTable::ENTITY_TYPE_DELIVERY,
			array(),
			true
		);

		return parent::delete($restrictionId);
	}

	public static function prepareData(array $deliveryIds)
	{
		if(empty($deliveryIds))
			return;

		self::$preparedData = \Bitrix\Sale\Internals\DeliveryPaySystemTable::prepareData($deliveryIds, DeliveryPaySystemTable::ENTITY_TYPE_DELIVERY);
	}
} 