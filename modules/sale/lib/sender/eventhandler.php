<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Sale\Sender;


class EventHandler
{
	/**
	 * @param $data
	 * @return mixed
	 */
	public static function onConnectorListBuyer($data)
	{
		$data['CONNECTOR'] = '\Bitrix\Sale\Sender\ConnectorBuyer';

		return $data;
	}

	public static function onTriggerList($data)
	{
		$data['TRIGGER'] = array(
			'\Bitrix\Sale\Sender\TriggerOrderNew',
			'\Bitrix\Sale\Sender\TriggerOrderCancel',
			'\Bitrix\Sale\Sender\TriggerOrderPaid',
			'\Bitrix\Sale\Sender\TriggerBasketForgotten',
			'\Bitrix\Sale\Sender\TriggerDontBuy',
		);

		return $data;
	}

	public static function onConnectorOrder($data)
	{
		$data['CONNECTOR'] = '\Bitrix\Sale\Sender\ConnectorOrder';

		return $data;
	}

	public static function onPresetMailingList()
	{
		$resultList = array();
		$resultList[] = \Bitrix\Sale\Sender\PresetMailing::getForgottenCart(1);
		$resultList[] = \Bitrix\Sale\Sender\PresetMailing::getCanceledOrder();
		$resultList[] = \Bitrix\Sale\Sender\PresetMailing::getPaidOrder();
		$resultList[] = \Bitrix\Sale\Sender\PresetMailing::getDontBuy(90);
		$resultList[] = \Bitrix\Sale\Sender\PresetMailing::getDontAuth(111);
		$resultList[] = \Bitrix\Sale\Sender\PresetMailing::getDontBuy(180);
		$resultList[] = \Bitrix\Sale\Sender\PresetMailing::getDontBuy(360);

		return $resultList;
	}

	public static function onPresetTemplateList()
	{
		$resultList = array();

		return $resultList;
	}
}