<?php

namespace Sale\Handlers\Delivery;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Sale\Delivery\CalculationResult;
use \Bitrix\Sale\Location\GroupTable;

Loc::loadMessages(__FILE__);

/*
 * @package Bitrix\Sale\Delivery\Services
 */
class SimpleHandler extends \Bitrix\Sale\Delivery\Services\Base
{
	protected static $isCalculatePriceImmediately = true;
	protected  static $whetherAdminExtraServicesShow = true;

	/**
	 * @param array $initParams
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function __construct(array $initParams)
	{
		parent::__construct($initParams);

		//Default value
		if(!isset($this->config["MAIN"]["0"]))
			$this->config["MAIN"]["0"] = "0";
	}

	/**
	 * @return string Class title.
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVR_HANDL_SMPL_TITLE");
	}

	/**
	 * @return string Class, service description.
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVR_HANDL_SMPL_DESCRIPTION");
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function getLocationGroups()
	{
		$result = array();
		$res = GroupTable::getList(array(
			'select' => array('ID', 'CODE', 'LNAME' => 'NAME.NAME'),
			'filter' => array('NAME.LANGUAGE_ID' => LANGUAGE_ID)
		));

		while($group = $res->fetch())
			$result[$group['ID']] = $group['LNAME'];

		return $result;
	}

	/**
	 * @param \Bitrix\Sale\Shipment|null $shipment
	 * @return CalculationResult
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment = null)
	{
		$result = new CalculationResult;
		$result->setDeliveryPrice(
			roundEx(
				$this->config["MAIN"]["0"],
				SALE_VALUE_PRECISION
			)
		);

		$order = $shipment->getCollection()->getOrder();

		if(!$props = $order->getPropertyCollection())
			return $result;

		if(!$locationProp = $props->getDeliveryLocation())
			return $result;

		if(!$locationCode = $locationProp->getValue())
			return $result;

		$res = \Bitrix\Sale\Location\LocationTable::getList(array(
			'runtime' => array(
				new \Bitrix\Main\Entity\ReferenceField(
					'PARENT_GROUP',
					'\Bitrix\Sale\Location\GroupLocationTable',
					array(
						'=this.PARENTS.ID' => 'ref.LOCATION_ID',
					),
					array(
						"join_type" => 'inner'
					)
				)
			),
			'filter' => array('=CODE' => $locationCode),
			'select' => array('CLOSEST_GROUP_ID'  => 'PARENT_GROUP.LOCATION_GROUP_ID'),
			'order' => array('PARENTS.DEPTH_LEVEL' => 'desc'),
			'limit' => 1
		));

		if($group = $res->fetch())
		{
			if(isset($this->config["MAIN"][$group['CLOSEST_GROUP_ID']]))
			{
				$result->setDeliveryPrice(
					roundEx(
						floatval(
							$this->config["MAIN"][$group['CLOSEST_GROUP_ID']]
						),
						SALE_VALUE_PRECISION
					)
				);
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	protected function getConfigStructure()
	{
		$currency = $this->currency;

		if(Loader::includeModule('currency'))
		{
			$currencyList = CurrencyManager::getCurrencyList();
			if (isset($currencyList[$this->currency]))
				$currency = $currencyList[$this->currency];
			unset($currencyList);
		}

		$result = array(
			"MAIN" => array(
				"TITLE" => Loc::getMessage("SALE_DLVR_HANDL_SMPL_TAB_MAIN"),
				"DESCRIPTION" => Loc::getMessage("SALE_DLVR_HANDL_SMPL_TAB_MAIN_DESCR"),
				"ITEMS" => array(

					"CURRENCY" => array(
						"TYPE" => "DELIVERY_READ_ONLY",
						"NAME" => Loc::getMessage("SALE_DLVR_HANDL_SMPL_CURRENCY"),
						"VALUE" => $this->currency,
						"VALUE_VIEW" => $currency
					),

					0 => array(
						"TYPE" => "NUMBER",
						"MIN" => 0,
						"NAME" => Loc::getMessage("SALE_DLVR_HANDL_SMPL_DEFAULT")
					)
				)
			)
		);

		foreach(self::getLocationGroups() as $groupId => $groupName)
		{
			$result["MAIN"]["ITEMS"][$groupId] = array(
				"TYPE" => "NUMBER",
				"MIN" => 0,
				"NAME" => $groupName
			);
		}

		return $result;
	}

	static public function isCalculatePriceImmediately()
	{
		return self::$isCalculatePriceImmediately;
	}

	public static function whetherAdminExtraServicesShow()
	{
		return self::$whetherAdminExtraServicesShow;
	}
}