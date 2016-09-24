<?php
namespace Bitrix\Sale\Services\Company\Restrictions;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Restrictions;
use Bitrix\Sale\Internals;
use Bitrix\Sale;
use Bitrix\Sale\PaymentCollection;
use Bitrix\Sale\ShipmentCollection;
use Bitrix\Sale\Services\Base;

Loc::loadMessages(__FILE__);

/**
 * Class Currency
 * @package Bitrix\Sale\Services\Company\Restrictions
 */
class Currency extends Base\Restriction
{
	/**
	 * @param Internals\CollectableEntity $entity
	 * @return string
	 */
	protected static function extractParams(Internals\CollectableEntity $entity)
	{
		/** @var PaymentCollection|ShipmentCollection $collection */
		$collection = $entity->getCollection();

		/** @var \Bitrix\Sale\Order $order */
		$order = $collection->getOrder();

		return $order->getCurrency();
	}

	/**
	 * @return string
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage('SALE_COMPANY_RULES_BY_CURRENCY_TITLE');
	}

	/**
	 * @return string
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage('SALE_COMPANY_RULES_BY_CURRENCY_DESC');
	}

	/**
	 * @param int $entityId
	 * @return array
	 */
	public static function getParamsStructure($entityId = 0)
	{
		return array(
			"CURRENCY" => array(
				"TYPE" => "ENUM",
				'MULTIPLE' => 'Y',
				"LABEL" => Loc::getMessage("SALE_COMPANY_RULES_BY_CURRENCY"),
				"OPTIONS" => CurrencyManager::getCurrencyList()
			)
		);
	}

	/**
	 * @param $params
	 * @param array $restrictionParams
	 * @param int $serviceId
	 * @return bool
	 */
	protected static function check($params, array $restrictionParams, $serviceId = 0)
	{
		if (isset($restrictionParams) && is_array($restrictionParams['CURRENCY']))
			return in_array($params, $restrictionParams['CURRENCY']);

		return true;
	}
}