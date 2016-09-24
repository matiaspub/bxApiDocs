<?php

namespace Bitrix\Sale\PaySystem;

/**
 * Interface IRefundExtended
 * @package Bitrix\Sale\PaySystem
 */
interface IRefundExtended extends IRefund
{
	/**
	 * @return bool
	 */
	static public function isRefundableExtended();
}
