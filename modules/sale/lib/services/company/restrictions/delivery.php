<?php
namespace Bitrix\Sale\Services\Company\Restrictions;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\CollectableEntity;
use Bitrix\Sale\Internals\CompanyServiceTable;
use Bitrix\Sale;
use Bitrix\Sale\Services\Base;

Loc::loadMessages(__FILE__);

class Delivery extends Base\Restriction
{
	public static $easeSort = 200;

	/**
	 * @return string
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_COMPANY_RULES_BY_DLV_TITLE");
	}

	/**
	 * @return string
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_COMPANY_RULES_BY_DLV_DESC");
	}

	/**
	 * @param $params
	 * @param array $restrictionParams
	 * @param int $serviceId
	 * @return bool
	 */
	public static function check($params, array $restrictionParams, $serviceId = 0)
	{
		if ((int)$serviceId <= 0)
			return true;

		if (!$params)
			return true;

		$deliveryIds = self::getDeliveryByCompanyId($serviceId);

		if (empty($deliveryIds))
			return true;

		$diff = array_diff($params, $deliveryIds);

		return empty($diff);
	}

	/**
	 * @param CollectableEntity $entity
	 * @return array
	 */
	protected static function extractParams(CollectableEntity $entity)
	{
		$result = array();

		/** @var Sale\ShipmentCollection|null  $shipmentCollection  */
		$shipmentCollection = null;

		if ($entity instanceof Sale\Shipment)
		{
			$shipmentCollection = $entity->getCollection();
		}
		elseif ($entity instanceof Sale\Payment)
		{
			/** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
			$paymentCollection = $entity->getCollection();
			if ($paymentCollection)
			{
				/** @var \Bitrix\Sale\Order $order */
				$order = $paymentCollection->getOrder();
				if ($order)
					$shipmentCollection = $order->getShipmentCollection();
			}

		}

		if ($shipmentCollection !== null)
		{
			/** @var \Bitrix\Sale\Shipment $shipment */
			foreach ($shipmentCollection as $shipment)
			{
				if ($deliveryId = $shipment->getDeliveryId())
					$result[] = $deliveryId;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected static function getDeliveryList()
	{
		$result = array();

		$serviceList = array();
		$dbRes = Sale\Delivery\Services\Table::getList(array('select' => array('ID', 'NAME', 'PARENT_ID')));
		while ($service = $dbRes->fetch())
			$serviceList[$service['ID']] = $service;

		foreach ($serviceList as $service)
		{
			if ((int)$service['PARENT_ID'] > 0)
				$name = $serviceList[$service['PARENT_ID']]['NAME'].': '.$service['NAME'].' ['.$service['ID'].']';
			else
				$name = $service['NAME'].' ['.$service['ID'].']';

			$result[$service['ID']] = $name;
		}

		return $result;
	}

	/**
	 * @param int $entityId
	 * @return array
	 */
	public static function getParamsStructure($entityId = 0)
	{
		$result =  array(
			"DELIVERY" => array(
				"TYPE" => "ENUM",
				'MULTIPLE' => 'Y',
				"LABEL" => Loc::getMessage("SALE_COMPANY_RULES_BY_DLV"),
				"OPTIONS" => self::getDeliveryList()
			)
		);

		return $result;
	}

	/**
	 * @param int $companyId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	protected static function getDeliveryByCompanyId($companyId = 0)
	{
		$result = array();
		if ($companyId == 0)
			return $result;

		$dbRes = CompanyServiceTable::getList(
			array(
				'select' => array('SERVICE_ID'),
				'filter' => array(
					'COMPANY_ID' => $companyId,
					'SERVICE_TYPE' => Sale\Services\Company\Restrictions\Manager::SERVICE_TYPE_SHIPMENT)
			)
		);

		while ($data = $dbRes->fetch())
			$result[] = $data['SERVICE_ID'];

		return $result;
	}

	/**
	 * @param array $fields
	 * @param int $restrictionId
	 * @return \Bitrix\Main\Entity\AddResult|\Bitrix\Main\Entity\UpdateResult
	 */
	public static function save(array $fields, $restrictionId = 0)
	{
		$serviceIds = $fields["PARAMS"];
		$fields["PARAMS"] = array();

		if ($restrictionId > 0)
		{
			$dbRes = CompanyServiceTable::getList(
				array(
					'select' => array('SERVICE_ID'),
					'filter' => array(
						'SERVICE_TYPE' => Sale\Services\Company\Restrictions\Manager::SERVICE_TYPE_SHIPMENT,
						'COMPANY_ID' => $fields['SERVICE_ID']
					)
				)
			);

			while($data = $dbRes->fetch())
			{
				$key = array_search($data['SERVICE_ID'], $serviceIds['DELIVERY']);
				if (!$key)
				{
					CompanyServiceTable::delete(array('COMPANY_ID' => $fields['SERVICE_ID'], 'SERVICE_ID' => $data['SERVICE_ID'], 'SERVICE_TYPE' => Sale\Services\Company\Restrictions\Manager::SERVICE_TYPE_SHIPMENT));
				}
				else
				{
					unset($serviceIds['DELIVERY'][$key]);
				}
			}
		}

		$result = parent::save($fields, $restrictionId);

		$addFields = array('COMPANY_ID' => $fields['SERVICE_ID'], 'SERVICE_TYPE' => Sale\Services\Company\Restrictions\Manager::SERVICE_TYPE_SHIPMENT);
		foreach ($serviceIds['DELIVERY'] as $id)
		{
			$addFields['SERVICE_ID'] = $id;
			CompanyServiceTable::add($addFields);
		}

		return $result;
	}

	/**
	 * @param array $paramsValues
	 * @param int $entityId
	 * @return array
	 */
	public static function prepareParamsValues(array $paramsValues, $entityId = 0)
	{
		return array("DELIVERY" => self::getDeliveryByCompanyId($entityId));
	}

	/**
	 * @param $restrictionId
	 * @param int $entityId
	 * @return \Bitrix\Main\Entity\DeleteResult
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function delete($restrictionId, $entityId = 0)
	{
		$dbRes = CompanyServiceTable::getList(
			array(
				'select' => array('SERVICE_ID'),
				'filter' => array(
					'SERVICE_TYPE' => Sale\Services\Company\Restrictions\Manager::SERVICE_TYPE_SHIPMENT,
					'COMPANY_ID' => $entityId
				)
			)
		);

		while ($data = $dbRes->fetch())
		{
			CompanyServiceTable::delete(array('COMPANY_ID' => $entityId, 'SERVICE_ID' => $data['SERVICE_ID'], 'SERVICE_TYPE' => Sale\Services\Company\Restrictions\Manager::SERVICE_TYPE_SHIPMENT));
		}

		return parent::delete($restrictionId);
	}
}