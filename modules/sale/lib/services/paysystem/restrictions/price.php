<?php

namespace Bitrix\Sale\Services\PaySystem\Restrictions;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\CollectableEntity;
use Bitrix\Sale\Services\Base;
use Bitrix\Sale\Payment;

Loc::loadMessages(__FILE__);

class Price extends Base\Restriction
{
	/**
	 * @param $params
	 * @param array $restrictionParams
	 * @param int $serviceId
	 * @return bool
	 */
	protected static function check($params, array $restrictionParams, $serviceId = 0)
	{
		if ($params['PRICE_PAYMENT'] === null)
			return true;

		$maxValue = static::getPrice($params, $restrictionParams['MAX_VALUE']);
		$minValue = static::getPrice($params, $restrictionParams['MIN_VALUE']);
		$price = (float)$params['PRICE_PAYMENT'];

		if ($maxValue > 0 && $minValue > 0)
			return ($maxValue >= $price) && ($minValue <= $price);

		if ($maxValue > 0)
			return $maxValue >= $price;

		if ($minValue > 0)
			return $minValue <= $price;

		return false;
	}

	/**
	 * @param CollectableEntity $entity
	 * @return array
	 */
	protected static function extractParams(CollectableEntity $entity)
	{
		if ($entity instanceof Payment)
			return array('PRICE_PAYMENT' => $entity->getField('SUM'));
		else
			return array('PRICE_PAYMENT' => null);
	}

	/**
	 * @param $entityParams
	 * @param $paramValue
	 * @return float
	 */
	protected static function getPrice($entityParams, $paramValue)
	{
		return (float)$paramValue;
	}

	/**
	 * @return mixed
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage('SALE_PS_RESTRICTIONS_BY_PRICE');
	}

	/**
	 * @return mixed
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage('SALE_PS_RESTRICTIONS_BY_PRICE_DESC');
	}

	/**
	 * @param $entityId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getParamsStructure($entityId = 0)
	{
		return array(
			"MIN_VALUE" => array(
				'TYPE' => 'NUMBER',
				'DEFAULT' => 0,
				'LABEL' => Loc::getMessage("SALE_PS_RESTRICTIONS_BY_PRICE_TYPE_MORE")
			),
			"MAX_VALUE" => array(
				'TYPE' => 'NUMBER',
				'DEFAULT' => 0,
				'LABEL' => Loc::getMessage("SALE_PS_RESTRICTIONS_BY_PRICE_TYPE_LESS")
			)
		);
	}

	/**
	 * @param Payment $payment
	 * @param $params
	 * @return array
	 * @throws ArgumentTypeException
	 */
	public static function getRange(Payment $payment, $params)
	{
		if ($payment instanceof Payment)
		{
			$p = static::extractParams($payment);
			return array(
				'MAX' => static::getPrice($p, $params['MAX_VALUE']),
				'MIN' => static::getPrice($p, $params['MIN_VALUE']),
			);
		}

		throw new ArgumentTypeException('');
	}

	/**
	 * @param $mode
	 * @return int
	 */
	public static function getSeverity($mode)
	{
		return Manager::SEVERITY_SOFT;
	}
}