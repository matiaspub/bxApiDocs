<?php

namespace Bitrix\Sale\PaySystem;

use Bitrix\Main\Request;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Payment;
use Bitrix\Sale\Result;

abstract class ServiceHandler extends BaseServiceHandler
{
	/**
	 * @return array
	 */
	static public function getIndicativeFields()
	{
		return array();
	}

	/**
	 * @param Request $request
	 * @param int $paySystemId
	 * @return bool
	 */
	public static function isMyResponse(Request $request, $paySystemId)
	{
		$fields = static::getIndicativeFields();

		if (!is_array($fields) || empty($fields))
			return false;

		$isAssociate = \CSaleHelper::IsAssociativeArray($fields);

		foreach ($fields as $key => $value)
		{
			if (!$isAssociate && !isset($request[$value]))
				return false;

			if ($isAssociate && (!isset($request[$key]) || is_null($value) || ($value != $request[$key])))
				return false;
		}

		return static::isMyResponseExtended($request, $paySystemId);
	}

	/**
	 * @param Request $request
	 * @param $paySystemId
	 * @return bool
	 */
	protected static function isMyResponseExtended(Request $request, $paySystemId)
	{
		return true;
	}

	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return mixed
	 */
	public abstract function processRequest(Payment $payment, Request $request);

	/**
	 * @param ServiceResult $result
	 * @param Request $request
	 * @return mixed
	 */
	static public function sendResponse(ServiceResult $result, Request $request)
	{
		return '';
	}

	/**
	 * @param Request $request
	 * @return mixed
	 */
	public abstract function getPaymentIdFromRequest(Request $request);
}