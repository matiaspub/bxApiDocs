<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Sale\Internals\PaySystemServiceTable;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PaySystemService
{
	protected $attributes = array();

	public static function __construct()
	{

	}

	/**
	 * @param $id
	 * @return bool|static
	 */
	public static function load($id)
	{
		if ($paymentSystemDat = PaySystemServiceTable::getByPrimary($id)->fetch())
		{
			$paymentSystem = new static();
			$paymentSystem->setAttributesFromArray($paymentSystemDat);

			return $paymentSystem;
		}

		return null;
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public static function isExist($id)
	{
		return (bool)(PaySystemServiceTable::getByPrimary($id)->fetch());
	}


	public static function getAvailableList()
	{

	}

	static public function applicableForOrder($order, $sum)
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
	 * @param array $parameters
	 * @return \Bitrix\Main\DB\Result
	 */
	public static function getList(array $parameters)
	{
		return PaySystemServiceTable::getList($parameters);
	}

	/**
	 * @param OrderBase $order
	 * @return bool
	 */
	public static function process(OrderBase $order)
	{
		return true;
	}
}
