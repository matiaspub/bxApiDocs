<?php
namespace Bitrix\Sale\Services\Company;

use Bitrix\Main;
use Bitrix\Sale\Internals;

class Manager
{
	/**
	 * @param $parameters
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 */
	public static function getList($parameters)
	{
		return Internals\CompanyTable::getList($parameters);
	}

	/**
	 * @param $id
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 */
	public static function getById($id)
	{
		return Internals\CompanyTable::getById($id);
	}

	/**
	 * @param Internals\CollectableEntity $entity
	 * @param int $mode
	 * @return array
	 */
	public static function getListWithRestrictions(Internals\CollectableEntity $entity, $mode = Restrictions\Manager::MODE_CLIENT)
	{
		$result = array();

		$dbRes = self::getList(array(
			'filter' => array('ACTIVE' => 'Y')
		));

		while ($company = $dbRes->fetch())
		{
			if ($mode == Restrictions\Manager::MODE_MANAGER)
			{
				$checkServiceResult = Restrictions\Manager::checkService($company['ID'], $entity, $mode);
				if ($checkServiceResult != Restrictions\Manager::SEVERITY_STRICT)
				{
					if ($checkServiceResult == Restrictions\Manager::SEVERITY_SOFT)
						$company['RESTRICTED'] = $checkServiceResult;
					$result[$company['ID']] = $company;
				}
			}
			else if ($mode == Restrictions\Manager::MODE_CLIENT)
			{
				if (Restrictions\Manager::checkService($company['ID'], $entity, $mode) === Restrictions\Manager::SEVERITY_NONE)
					$result[$company['ID']] = $company;
			}
		}

		return $result;
	}

	/**
	 * @param Internals\CollectableEntity $entity
	 * @param int $mode
	 * @return int
	 */
	public static function getAvailableCompanyIdByEntity(Internals\CollectableEntity $entity, $mode = Restrictions\Manager::MODE_CLIENT)
	{
		$dbRes = self::getList(array(
			'select' => array('ID'),
			'filter' => array('=ACTIVE' => 'Y'),
			'order' => array('SORT' => 'ASC')
		));

		while ($company = $dbRes->fetch())
		{
			$result = Restrictions\Manager::checkService($company['ID'], $entity, $mode);
			if ($mode == Restrictions\Manager::MODE_CLIENT)
			{
				if ($result == Restrictions\Manager::SEVERITY_NONE)
					return $company['ID'];
			}
			else
			{
				if ($result != Restrictions\Manager::SEVERITY_STRICT)
					return $company['ID'];
			}
		}

		return 0;
	}

	/**
	 * Returns entity link name for connection with Locations
	 * @return string
	 */
	public static function getLocationConnectorEntityName()
	{
		return	'Bitrix\Sale\Internals\CompanyLocation';
	}
}