<?php

namespace Bitrix\Sale\Delivery\Services;

use Bitrix\Main\Entity\EntityError;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Shipment;

Loc::loadMessages(__FILE__);

class Group extends Base
{
	static public function __construct(array $initParams)
	{
		if(!isset($initParams["ACTIVE"]))
			$initParams["ACTIVE"] = "Y";

		$initParams["CONFIG"] = array();

		parent::__construct($initParams);
	}

	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVR_HANDL_GROUP_NAME");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVR_HANDL_GROUP_DESCRIPTION");
	}

	protected function calculateConcrete(Shipment $shipment)
	{
		$result = new CalculationResult();

		$result->addError(new EntityError(
			Loc::getMessage("SALE_DLVR_HANDL_GROUP_ERROR_CALCULATION"),
			'DELIVERY_CALCULATION'
		));

		return $result;
	}

	protected function getConfigStructure()
	{
		return array();
	}

	public static function getAdminFieldsList()
	{
		return array(
			"ID" => true,
			"NAME" => true,
			"ACTIVE" => true,
			"DESCRIPTION" => true,
		);
	}

	public static function whetherAdminRestrictionsShow()
	{
		return false;
	}

	public static function canHasChildren()
	{
		return true;
	}
} 