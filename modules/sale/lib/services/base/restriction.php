<?php
namespace Bitrix\Sale\Services\Base;

use Bitrix\Main\NotImplementedException;
use Bitrix\Sale\Internals\CollectableEntity;
use Bitrix\Sale\Internals\ServiceRestrictionTable;

/**
 * Class RestrictionBase.
 * Base class for payment and delivery services restrictions.
 * @package Bitrix\Sale\Services
 */
abstract class Restriction {

	/** @var int
	 * 100 - lightweight - just compare with params
	 * 200 - middleweight - may be use base queries
	 * 300 - hardweight - use base, and/or hard calculations
	 * */
	public static $easeSort = 100;

	/**
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function getClassTitle()
	{
		throw new NotImplementedException;
	}

	/**
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function getClassDescription()
	{
		throw new NotImplementedException;
	}

	/**
	 * @param $params
	 * @param array $restrictionParams
	 * @param int $serviceId
	 * @return bool
	 * @throws NotImplementedException
	 */
	protected static function check($params, array $restrictionParams, $serviceId = 0)
	{
		throw new NotImplementedException;
	}

	/**
	 * @param CollectableEntity $entity
	 * @param array $restrictionParams
	 * @param int $mode
	 * @param int $serviceId
	 * @return int
	 * @throws NotImplementedException
	 */
	public static function checkByEntity(CollectableEntity $entity, array $restrictionParams, $mode, $serviceId = 0)
	{
		$severity = static::getSeverity($mode);

		if($severity == RestrictionManager::SEVERITY_NONE)
			return RestrictionManager::SEVERITY_NONE;

		$entityRestrictionParams = static::extractParams($entity);
		$res = static::check($entityRestrictionParams, $restrictionParams, $serviceId);
		return $res ? RestrictionManager::SEVERITY_NONE : $severity;
	}

	/**
	 * @param CollectableEntity $entity
	 * @return mixed
	 * @throws NotImplementedException
	 */
	protected static function extractParams(CollectableEntity $entity)
	{
		throw new NotImplementedException;
	}

	/**
	 * Returns params structure to show it to user
	 * @return array
	 */
	
	/**
	* <p>Возвращает структуру параметров для отображения пользователю. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/services/base/restriction/getparamsstructure.php
	* @author Bitrix
	*/
	public static function getParamsStructure($entityId = 0)
	{
		return array();
	}

	/**
	 * @param array $paramsValues
	 * @param int $entityId
	 * @return array
	 */
	
	/**
	* <p>Подготавливает параметры ограничения для отображения данных , например, в административной части для редактирования или просмотра. Статический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/services/base/restriction/prepareparamsvalues.php
	* @author Bitrix
	*/
	public static function prepareParamsValues(array $paramsValues, $entityId = 0)
	{
		return $paramsValues;
	}

	/**
	 * @param array $fields
	 * @param int $restrictionId
	 * @return \Bitrix\Main\Entity\AddResult|\Bitrix\Main\Entity\UpdateResult
	 * @throws \Exception
	 */
	public static function save(array $fields, $restrictionId = 0)
	{
		$fields["CLASS_NAME"] = '\\'.get_called_class();

		if($restrictionId > 0)
			$res = ServiceRestrictionTable::update($restrictionId, $fields);
		else
			$res = ServiceRestrictionTable::add($fields);

		return $res;
	}

	/**
	 * @param $restrictionId
	 * @param int $entityId
	 * @return \Bitrix\Main\Entity\DeleteResult
	 * @throws \Exception
	 */
	public static function delete($restrictionId, $entityId = 0)
	{
		return ServiceRestrictionTable::delete($restrictionId);
	}

	/**
	 * @param int $mode - RestrictionManager::MODE_CLIENT | RestrictionManager::MODE_MANAGER
	 * @return int
	 */
	public static function getSeverity($mode)
	{
		$result = RestrictionManager::SEVERITY_STRICT;

		if($mode == RestrictionManager::MODE_MANAGER)
			return RestrictionManager::SEVERITY_SOFT;

		return $result;
	}

	/**
	 * @param array $servicesIds
	 * @return bool
	 */
	public static function prepareData(array $servicesIds)
	{
		return true;
	}

	/*
	 * Children can have also this method
	 * for performance purposes.
	 *
	 * @return int[]
	 * public static function filterServicesArray(Shipment $shipment, array $restrictionFields)
	 * {
	 *  ...
	 * }
	*/
}