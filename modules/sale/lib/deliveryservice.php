<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Sale\Internals\DeliveryServiceTable;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DeliveryService
{
	protected $attributes = array();

	function __construct()
	{

	}


	public static function load($id)
	{
		if ($deliveryDat = DeliveryServiceTable::getByPrimary($id)->fetch())
		{
			$delivery = new static();
			$delivery->setAttributesFromArray($deliveryDat);

			return $delivery;
		}

		return false;
	}

	/**
	 * @param array $parameters
	 * @return \Bitrix\Main\DB\Result
	 */
	public static function getList(array $parameters)
	{
		return DeliveryServiceTable::getList($parameters);
	}

	public static function getAvailableList()
	{

	}

	static public function applicableForOrder($order, $basket)
	{
		return true;
	}

	public function setAttributesFromArray($attributes)
	{
		$this->attributes = $attributes;
	}

	public function getAttribute($name)
	{
		if (isset($this->attributes[$name]) || array_key_exists($name, $this->attributes))
		{
			return $this->attributes[$name];
		}
		return null;
	}

	public function getId()
	{
		return $this->getAttribute('ID');
	}

	public function getName()
	{
		return $this->getAttribute('NAME');
	}

	public function getPrice()
	{
		return $this->getAttribute('PRICE');
	}

	public function getCurrency()
	{
		return $this->getAttribute('CURRENCY');
	}

	/**
	 * @param OrderBase $order
	 * @return bool
	 */
	public static function process(OrderBase $order)
	{
		\CSaleDelivery::DoProcessOrder($order);

		return true;
	}


}
