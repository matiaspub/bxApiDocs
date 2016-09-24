<?php
namespace Bitrix\Sale\Services\Company\Restrictions;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\CollectableEntity;
use Bitrix\Sale\Internals\CompanyServiceTable;
use Bitrix\Sale;
use Bitrix\Sale\Services\Base;

Loc::loadMessages(__FILE__);

class PaySystem extends Base\Restriction
{
	public static $easeSort = 200;

	/**
	 * @return string
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_COMPANY_RULES_BY_PS_TITLE");
	}

	/**
	 * @return string
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_COMPANY_RULES_BY_PS_DESC");
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

		$paySystemIds = self::getPaySystemsByCompanyId($serviceId);

		if (empty($paySystemIds))
			return true;

		$diff = array_diff($params, $paySystemIds);

		return empty($diff);
	}

	/**
	 * @param CollectableEntity $entity
	 * @return array
	 */
	protected static function extractParams(CollectableEntity $entity)
	{
		$result = array();

		/** @var Sale\PaymentCollection|null $paymentCollection */
		$paymentCollection = null;

		if ($entity instanceof Sale\Payment)
		{
			$paymentCollection = $entity->getCollection();
		}
		elseif ($entity instanceof Sale\Shipment)
		{
			/** @var \Bitrix\Sale\ShipmentCollection $shipmentCollection */
			$shipmentCollection = $entity->getCollection();
			if ($shipmentCollection)
			{
				/** @var \Bitrix\Sale\Order $order */
				$order = $shipmentCollection->getOrder();
				if ($order)
					$paymentCollection = $order->getPaymentCollection();
			}
		}

		if ($paymentCollection !== null)
		{
			/** @var \Bitrix\Sale\Payment $payment */
			foreach ($paymentCollection as $payment)
			{
				$paySystemId = $payment->getPaymentSystemId();
				if ($paySystemId)
					$result[] = $paySystemId;
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected static function getPaySystemList()
	{
		$result = array();

		$dbRes = Sale\PaySystem\Manager::getList(array('select' => array('ID', 'NAME')));
		while ($paySystem = $dbRes->fetch())
			$result[$paySystem['ID']] = $paySystem['NAME'].' ['.$paySystem['ID'].']';

		return $result;
	}

	/**
	 * @param int $entityId
	 * @return array
	 */
	public static function getParamsStructure($entityId = 0)
	{
		$result =  array(
			"PAYSYSTEM" => array(
				"TYPE" => "ENUM",
				'MULTIPLE' => 'Y',
				"LABEL" => Loc::getMessage("SALE_COMPANY_RULES_BY_PS"),
				"OPTIONS" => self::getPaySystemList()
			)
		);

		return $result;
	}

	/**
	 * @param int $companyId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	protected static function getPaySystemsByCompanyId($companyId = 0)
	{
		$result = array();
		if ($companyId == 0)
			return $result;

		$dbRes = CompanyServiceTable::getList(
			array(
				'select' => array('SERVICE_ID'),
				'filter' => array(
					'COMPANY_ID' => $companyId,
					'SERVICE_TYPE' => Sale\Services\Company\Restrictions\Manager::SERVICE_TYPE_PAYMENT)
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
						'SERVICE_TYPE' => Sale\Services\Company\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
						'COMPANY_ID' => $fields['SERVICE_ID']
					)
				)
			);

			while($data = $dbRes->fetch())
			{
				$key = array_search($data['SERVICE_ID'], $serviceIds['PAYSYSTEM']);
				if (!$key)
				{
					CompanyServiceTable::delete(array('COMPANY_ID' => $fields['SERVICE_ID'], 'SERVICE_ID' => $data['SERVICE_ID'], 'SERVICE_TYPE' => Sale\Services\Company\Restrictions\Manager::SERVICE_TYPE_PAYMENT));
				}
				else
				{
					unset($serviceIds['PAYSYSTEM'][$key]);
				}
			}
		}

		$result = parent::save($fields, $restrictionId);

		$addFields = array('COMPANY_ID' => $fields['SERVICE_ID'], 'SERVICE_TYPE' => Sale\Services\Company\Restrictions\Manager::SERVICE_TYPE_PAYMENT);
		foreach ($serviceIds['PAYSYSTEM'] as $id)
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
		return array("PAYSYSTEM" => self::getPaySystemsByCompanyId($entityId));
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
					'SERVICE_TYPE' => Sale\Services\Company\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
					'COMPANY_ID' => $entityId
				)
			)
		);

		while ($data = $dbRes->fetch())
		{
			CompanyServiceTable::delete(array('COMPANY_ID' => $entityId, 'SERVICE_ID' => $data['SERVICE_ID'], 'SERVICE_TYPE' => Sale\Services\Company\Restrictions\Manager::SERVICE_TYPE_PAYMENT));
		}

		return parent::delete($restrictionId);
	}
}