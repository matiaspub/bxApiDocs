<?php

namespace Bitrix\Sale\Services\PaySystem\Restrictions;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\CollectableEntity;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\PaySystem\Service;
use Bitrix\Sale\Services\Base;

Loc::loadMessages(__FILE__);

class Currency extends Base\Restriction
{
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

	/**
	 * @param CollectableEntity $entity
	 * @return string
	 * @throws ArgumentTypeException
	 */
	protected static function extractParams(CollectableEntity $entity)
	{
		if ($entity instanceof Payment)
		{
			/** @var \Bitrix\Sale\PaymentCollection $collection */
			$collection = $entity->getCollection();

			/** @var \Bitrix\Sale\Order $order */
			$order = $collection->getOrder();

			return $order->getCurrency();
		}

		throw new ArgumentTypeException('');
	}

	/**
	 * @return string
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage('SALE_PS_RESTRICTIONS_BY_CURRENCY');
	}

	/**
	 * @return string
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage('SALE_PS_RESTRICTIONS_BY_CURRENCY_DESC');
	}

	public static function getParamsStructure($entityId = 0)
	{
		$data = PaySystem\Manager::getById($entityId);

		$currencyList = CurrencyManager::getCurrencyList();

		if ($data !== false)
		{
			/** @var Service $paySystem */
			$paySystem = new Service($data);
			$psCurrency = $paySystem->getCurrency();

			$options = array();
			foreach ($psCurrency as $code)
				$options[$code] = (isset($currencyList[$code])) ? $currencyList[$code] : $code;

			if ($options)
			{
				return array(
					"CURRENCY" => array(
						"TYPE" => "ENUM",
						'MULTIPLE' => 'Y',
						"LABEL" => Loc::getMessage("SALE_PS_RESTRICTIONS_BY_CURRENCY_NAME"),
						"OPTIONS" => $options
					)
				);
			}
		}

		return array();
	}

	public static function save(array $fields, $restrictionId = 0)
	{
		return parent::save($fields, $restrictionId);
	}


}