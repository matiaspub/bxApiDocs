<?php
namespace Bitrix\Sale\Delivery\Restrictions;

use Bitrix\Main\Application;
use Bitrix\Sale\Delivery\Restrictions;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\CollectableEntity;

Loc::loadMessages(__FILE__);

/**
 * Class ByPublicMode
 * @package Bitrix\Sale\Delivery\Restrictions
 */

class ByPublicMode extends Restrictions\Base
{
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_PUBLIC_MODE_NAME");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVR_RSTR_BY_PUBLIC_MODE_DESCRIPT");
	}

	public static function check($dummy, array $restrictionParams, $deliveryId = 0)
	{
		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();

		if (empty($restrictionParams) || $request->isAdminSection())
			return true;

		return $restrictionParams["PUBLIC_SHOW"] == 'Y';
	}

	protected static function extractParams(CollectableEntity $shipment)
	{
		return null;
	}

	public static function getParamsStructure($entityId = 0)
	{
		return array(
			"PUBLIC_SHOW" => array(
				'TYPE' => 'Y/N',
				'VALUE' => 'Y',
				'LABEL' => Loc::getMessage("SALE_DLVR_RSTR_BY_PUBLIC_MODE_SHOW")
			)
		);
	}
}