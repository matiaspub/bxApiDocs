<?php

namespace Bitrix\Sale\Services\PaySystem\Restrictions;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\CollectableEntity;
use Bitrix\Sale\Internals\PersonTypeTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaymentCollection;
use Bitrix\Sale\Services\Base;

Loc::loadMessages(__FILE__);

class PersonType extends Base\Restriction
{
	/**
	 * @param $params
	 * @param array $restrictionParams
	 * @param int $serviceId
	 * @return bool
	 */
	protected static function check($params, array $restrictionParams, $serviceId = 0)
	{
		if (is_array($restrictionParams) && isset($restrictionParams['PERSON_TYPE_ID']))
		{
			return in_array($params, $restrictionParams['PERSON_TYPE_ID']);
		}

		return true;
	}

	/**
	 * @param CollectableEntity $entity
	 * @return int
	 */
	public static function extractParams(CollectableEntity $entity)
	{
		/** @var PaymentCollection $collection */
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
		return Loc::getMessage('SALE_PS_RESTRICTIONS_BY_PERSON_TYPE');
	}

	/**
	 * @return mixed
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage('SALE_PS_RESTRICTIONS_BY_PERSON_TYPE_DESC');
	}

	/**
	 * @param $entityId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getParamsStructure($entityId = 0)
	{
		$personTypeList = array();

		$dbRes = PersonTypeTable::getList();

		while ($personType = $dbRes->fetch())
			$personTypeList[$personType["ID"]] = $personType["NAME"]." (".$personType["ID"].")";

		return array(
			"PERSON_TYPE_ID" => array(
				"TYPE" => "ENUM",
				'MULTIPLE' => 'Y',
				"LABEL" => Loc::getMessage("SALE_SALE_PS_RESTRICTIONS_BY_PERSON_TYPE_NAME"),
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