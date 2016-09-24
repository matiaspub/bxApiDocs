<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */

interface IPaymentOrder
{
	public function getPaymentCollection();
	static public function loadPaymentCollection();
}