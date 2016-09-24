<?php

namespace Bitrix\Sale\Delivery\Restrictions;

use Bitrix\Sale\Internals\CollectableEntity;
use Bitrix\Sale\Internals\PersonTypeTable;
use Bitrix\Sale\ShipmentCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Order;

Loc::loadMessages(__FILE__);

class ByPersonType extends Base
{
	/**
	 * @param $personTypeId
	 * @param array $params
	 * @param int $deliveryId
	 * @return bool
	 */
	protected static function check($personTypeId, array $params, $deliveryId = 0)
	{
		if (is_array($params) && isset($params['PERSON_TYPE_ID']))
		{
			return in_array($personTypeId, $params['PERSON_TYPE_ID']);
		}

		return true;
	}

	/**
	 * @param CollectableEntity $entity
	 * @return int
	 */
	public static function extractParams(CollectableEntity $entity)
	{
		/** @var ShipmentCollection $collection */
		$collection = $entity->getCollection();

		/** @var Order $order */
		$order = $collection->getOrder();

		$personTypeId = $order->getPersonTypeId();
		return $personTypeId;
	}

	/**
	 * @return mixed
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage('SALE_DLVR_RSTR_BY_PERSON_TYPE');
	}

	/**
	 * @return mixed
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage('SALE_DLVR_RSTR_BY_PERSON_TYPE_DESC');
	}

	/**
	 * @param $deliveryId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getParamsStructure($deliveryId = 0)
	{
		$personTypeList = array();

		$dbRes = PersonTypeTable::getList();

		while ($personType = $dbRes->fetch())
			$personTypeList[$personType["ID"]] = $personType["NAME"]." (".$personType["ID"].")";

		return array(
			"PERSON_TYPE_ID" => array(
				"TYPE" => "ENUM",
				'MULTIPLE' => 'Y',
				"LABEL" => Loc::getMessage("SALE_DLVR_RSTR_BY_PERSON_TYPE_NAME"),
				"OPTIONS" => $personTypeList
			)
		);
	}

	/**
	 * @param $mode
	 * @return int
	 */
	public static function getSeverity($mode)
	{
		return Manager::SEVERITY_STRICT;
	}
}

class_alias('Bitrix\Sale\Delivery\Restrictions\ByPersonType', 'Bitrix\Sale\Delivery\Restrictions\PersonType', true);