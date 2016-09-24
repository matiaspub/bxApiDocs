<?php

namespace Bitrix\Sale\PaySystem\ExtraService;

use Bitrix\Main\Entity\EntityError;
use Bitrix\Sale\Internals\PaySystemExtraServiceTable;
use Bitrix\Sale\Result;

class Manager extends BaseManager
{
	/**
	 * @return string
	 */
	public static function getEventName()
	{
		return 'onSalePaySystemExtraServiceInit';
	}

	/**
	 * @return array
	 */
	public static function getBuildInExtraServices()
	{
		return array(
			'\Bitrix\Sale\PaySystem\ExtraService\Checkbox' => 'lib/paysystem/extra_service/checkbox.php',
		);
	}

	/**
	 * @param $serviceId
	 * @return array
	 */
	public static function getExtraServiceBySystem($serviceId)
	{
		static::initClassesList();

		$result = array();
		$params = array(
			'filter' => array('PAY_SYSTEM_ID' => $serviceId)
		);
		$dbResult = PaySystemExtraServiceTable::getList($params);

		while ($psExtraService = $dbResult->fetch())
			$result[$psExtraService['ID']] = $psExtraService;

		return $result;
	}

	/**
	 * @param $entityId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getExtraServiceByEntity($entityId)
	{
		static::initClassesList();

		$result = array();
		$params = array(
			'select' => array(
				'*',
				'VALUE' => 'PAYMENT.VALUE'
			),
			'filter' => array('PAY_SYSTEM_ID' => $entityId)
		);
		$dbResult = PaySystemExtraServiceTable::getList($params);

		while ($psExtraService = $dbResult->fetch())
			$result[$psExtraService['ID']] = $psExtraService;

		return $result;
	}

	/**
	 * @param array $extraServices
	 * @param int $entityId
	 * @return Result
	 * @throws \Exception
	 */
	public static function saveByEntity(array $extraServices, $entityId)
	{
		$result = new Result();

		foreach ($extraServices as $id => $value)
		{
			if ((int)$id > 0)
			{
				PaySystemExtraServiceTable::update($id, array('VALUE' => $value));
			}
			else
			{
				if ($entityId > 0)
					PaySystemExtraServiceTable::add(array('VALUE' => $value, 'PAYMENT_ID' => $entityId));
				else
					$result->addError(new EntityError('ERROR'));
			}
		}

		return $result;
	}
}