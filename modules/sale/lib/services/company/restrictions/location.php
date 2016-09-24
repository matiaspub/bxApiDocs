<?php
namespace Bitrix\Sale\Services\Company\Restrictions;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\CollectableEntity;
use Bitrix\Sale\Internals\CompanyLocationTable;
use Bitrix\Sale\Location\Tree\NodeNotFoundException;
use Bitrix\Sale\Services\Base;

Loc::loadMessages(__FILE__);

/**
 * Class Location
 * @package Bitrix\Sale\Services\Company\Restrictions
 */
class Location extends Base\Restriction
{
	public static $easeSort = 200;

	/**
	 * @return string
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_COMPANY_RULES_BY_LOCATION_TITLE");
	}

	/**
	 * @return string
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_COMPANY_RULES_BY_LOCATION_DESC");
	}

	/**
	 * @param $params
	 * @param array $restrictionParams
	 * @param int $serviceId
	 * @return bool
	 */
	protected static function check($params, array $restrictionParams, $serviceId = 0)
	{
		if ((int)$serviceId <= 0)
			return true;

		if (!$params)
			return false;

		try
		{
			return CompanyLocationTable::checkConnectionExists(
				intval($serviceId),
				$params,
				array(
					'LOCATION_LINK_TYPE' => 'AUTO'
				)
			);
		}
		catch (NodeNotFoundException $e)
		{
			return false;
		}
	}

	/**
	 * @param CollectableEntity $entity
	 * @return null|string
	 */
	protected static function extractParams(CollectableEntity $entity)
	{
		/** @var \Bitrix\Sale\Order $order */
		$order = $entity->getCollection()->getOrder();

		if(!$props = $order->getPropertyCollection())
			return '';

		if(!$locationProp = $props->getDeliveryLocation())
			return '';

		if(!$locationCode = $locationProp->getValue())
			return '';

		return $locationCode;
	}

	/**
	 * @param array $params
	 * @param int $companyId
	 * @return array
	 */
	protected static function prepareParamsForSaving(array $params = array(), $companyId = 0)
	{
		if($companyId > 0)
		{
			$arLocation = array();

			if(!!\CSaleLocation::isLocationProEnabled())
			{
				if(strlen($params["LOCATION"]['L']))
					$LOCATION1 = explode(':', $params["LOCATION"]['L']);

				if(strlen($params["LOCATION"]['G']))
					$LOCATION2 = explode(':', $params["LOCATION"]['G']);
			}

			if (isset($LOCATION1) && is_array($LOCATION1) && count($LOCATION1) > 0)
			{
				$arLocation["L"] = array();
				$locationCount = count($LOCATION1);

				for ($i = 0; $i<$locationCount; $i++)
					if (strlen($LOCATION1[$i]))
						$arLocation["L"][] = $LOCATION1[$i];
			}

			if (isset($LOCATION2) && is_array($LOCATION2) && count($LOCATION2) > 0)
			{
				$arLocation["G"] = array();
				$locationCount = count($LOCATION2);

				for ($i = 0; $i<$locationCount; $i++)
					if (strlen($LOCATION2[$i]))
						$arLocation["G"][] = $LOCATION2[$i];

			}

			CompanyLocationTable::resetMultipleForOwner($companyId, $arLocation);
		}

		return array();
	}

	/**
	 * @param int $entityId
	 * @return array
	 */
	public static function getParamsStructure($entityId = 0)
	{

		$result =  array(
			"LOCATION" => array(
				"TYPE" => "COMPANY_LOCATION_MULTI"
			)
		);

		if($entityId > 0 )
			$result["LOCATION"]["COMPANY_ID"] = $entityId;

		return $result;
	}

	/**
	 * @param array $fields
	 * @param int $restrictionId
	 * @return \Bitrix\Main\Entity\AddResult|\Bitrix\Main\Entity\UpdateResult
	 */
	public static function save(array $fields, $restrictionId = 0)
	{
		$fields["PARAMS"] = self::prepareParamsForSaving($fields["PARAMS"], $fields["SERVICE_ID"]);
		return parent::save($fields, $restrictionId);
	}

	/**
	 * @param $restrictionId
	 * @param int $entityId
	 * @return \Bitrix\Main\Entity\DeleteResult
	 */
	public static function delete($restrictionId, $entityId = 0)
	{
		CompanyLocationTable::resetMultipleForOwner($entityId);
		return parent::delete($restrictionId);
	}
}
