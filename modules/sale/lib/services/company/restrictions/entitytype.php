<?php
namespace Bitrix\Sale\Services\Company\Restrictions;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaymentCollection;
use Bitrix\Sale\Services\Base\Restriction;
use Bitrix\Sale\Internals;
use Bitrix\Sale\Services\Company;
use Bitrix\Sale\Shipment;
use Bitrix\Sale\ShipmentCollection;

Loc::loadMessages(__FILE__);

class EntityType extends Restriction
{
	const ENTITY_NONE = 'N';
	const ENTITY_PAYMENT = 'P';
	const ENTITY_SHIPMENT = 'S';

	/**
	 * @return string
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage('SALE_COMPANY_RULES_BY_ENTITY_TITLE');
	}

	/**
	 * @return string
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage('SALE_COMPANY_RULES_BY_ENTITY_DESC');
	}


	/**
	 * @param int $entityId
	 * @return array
	 */
	public static function getParamsStructure($entityId = 0)
	{
		return array(
			"ENTITY_TYPE" => array(
				"TYPE" => "ENUM",
				"LABEL" => Loc::getMessage("SALE_COMPANY_RULES_BY_ENTITY"),
				"OPTIONS" => array(
					self::ENTITY_NONE => Loc::getMessage('SALE_COMPANY_RULES_BY_ENTITY_NONE'),
					self::ENTITY_PAYMENT => Loc::getMessage('SALE_COMPANY_RULES_BY_ENTITY_PAYMENT'),
					self::ENTITY_SHIPMENT => Loc::getMessage('SALE_COMPANY_RULES_BY_ENTITY_SHIPMENT')
				)
			)
		);
	}


	/**
	 * @param Internals\CollectableEntity $entity
	 * @return string
	 */
	protected static function extractParams(Internals\CollectableEntity $entity)
	{
		/** @var PaymentCollection|ShipmentCollection $collection */
		if ($entity instanceof Payment)
			return self::ENTITY_PAYMENT;

		if ($entity instanceof Shipment)
			return self::ENTITY_SHIPMENT;

		return self::ENTITY_NONE;
	}

	/**
	 * @param $params
	 * @param array $restrictionParams
	 * @param int $serviceId
	 * @return bool
	 */
	protected static function check($params, array $restrictionParams, $serviceId = 0)
	{
		return $params == $restrictionParams['ENTITY_TYPE'];
	}

	/**
	 * @param $mode
	 * @return int
	 */
	public static function getSeverity($mode)
	{
		return Company\Restrictions\Manager::SEVERITY_STRICT;
	}
}