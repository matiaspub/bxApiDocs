<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */

interface IShipmentOrder
{
	public function getShipmentCollection();
	static public function loadShipmentCollection();
}