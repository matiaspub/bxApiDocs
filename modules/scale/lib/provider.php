<?php

namespace Bitrix\Scale;

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

use \Bitrix\Main\ArgumentNullException;

/**
 * Class Provider
 * @package Bitrix\Scale
 */
class Provider {

	/**
	 * @param array $params Params for selection.
	 * @return array List of available providers.
	 */
	public static function getList($params = array())
	{
		$result = array();
		$shellAdapter = new ShellAdapter();
		$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/bx-provider -a list -o json");
		$jsonData = $shellAdapter->getLastOutput();

		if($execRes)
		{
			$arData = json_decode($jsonData, true);

			if(isset($arData["params"]) && isset($arData["params"]["providers"]) && is_array($arData["params"]["providers"]))
				$result = $arData["params"]["providers"];

			if(isset($params["filter"]) && is_array($params["filter"]))
			{
				foreach($params["filter"] as $filterKey => $filterValue)
				{
					foreach($result as $providerId => $providerParams)
					{
						if(!array_key_exists($filterKey, $providerParams) || $providerParams[$filterKey] != $filterValue)
						{
							unset($result[$providerId]);
						}
					}
				}
			}

		}

		return $result;
	}

	/**
	 * @param string $providerId Identifier.
	 * @return array Status information.
	 * @throws ArgumentNullException
	 */
	public static function getStatus($providerId)
	{
		if(strlen($providerId) <= 0 )
			throw new ArgumentNullException("providerId");

		$result = array();

		$shellAdapter = new ShellAdapter();
		$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/bx-provider -a status --provider ".$providerId." -o json");
		$jsonData = $shellAdapter->getLastOutput();

		if($execRes)
		{
			$arData = json_decode($jsonData, true);

			if(isset($arData["params"]["provider_options"][$providerId]) && is_array($arData["params"]["provider_options"][$providerId]))
				$result = $arData["params"]["provider_options"][$providerId];
		}

		return $result;
	}

	/**
	 * @param string $providerId Identifier.
	 * @return array Avilable configurations.
	 * @throws ArgumentNullException
	 */
	public static function getConfigs($providerId)
	{
		if(strlen($providerId) <= 0 )
			throw new ArgumentNullException("providerId");

		$result = array();
		$shellAdapter = new ShellAdapter();
		$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/bx-provider -a configs --provider ".$providerId." -o json");
		$jsonData = $shellAdapter->getLastOutput();

		if($execRes)
		{
			$arData = json_decode($jsonData, true);

			if(isset($arData["params"]["provider_configs"][$providerId]["configurations"]) && is_array($arData["params"]["provider_configs"][$providerId]["configurations"]))
				$result = $arData["params"]["provider_configs"][$providerId]["configurations"];
		}

		return $result;
	}

	/**
	 * @param string $providerId Provider identifier.
	 * @param string $configId Config idenifier.
	 * @return int Task identifier.
	 * @throws ArgumentNullException
	 */
	public static function sendOrder($providerId, $configId)
	{
		if(strlen($providerId) <= 0 )
			throw new ArgumentNullException("providerId");

		if(strlen($configId) <= 0 )
			throw new ArgumentNullException("configId");

		$result = "";
		$shellAdapter = new ShellAdapter();
		$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/bx-provider -a order --provider ".$providerId." --config_id ".$configId." -o json");
		$jsonData = $shellAdapter->getLastOutput();

		if($execRes)
		{
			$arData = json_decode($jsonData, true);

			if(isset($arData["params"]["provider_order"][$providerId]["task_id"]))
				$result = $arData["params"]["provider_order"][$providerId]["task_id"];
		}

		if(strlen($result) > 0)
		{
			$logLevel = Logger::LOG_LEVEL_INFO;
			$description = Loc::getMessage("SCALE_PROVIDER_SEND_ORDER_SUCCESS");
		}
		else
		{
			$logLevel = Logger::LOG_LEVEL_ERROR;
			$description = Loc::getMessage("SCALE_PROVIDER_SEND_ORDER_ERROR");
		}

		$description = str_replace(
			array("##PROVIDER##", "##CONFIG_ID##", "##ORDER_ID##"),
			array($providerId, $configId, $result),
			$description
		);

		Logger::addRecord(
			$logLevel,
			"SCALE_PROVIDER_SEND_ORDER",
			$providerId."::".$configId,
			$description);

		return $result;
	}


	/**
	 * @param string $providerId Provider identifier.
	 * @param string $taskId Task identifier.
	 * @return array Status params.
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getOrderStatus($providerId, $taskId)
	{
		if(strlen($providerId) <= 0 )
			throw new ArgumentNullException("providerId");

		if(strlen($taskId) <= 0 )
			throw new ArgumentNullException("taskId");

		$result = array();
		$shellAdapter = new ShellAdapter();
		$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/bx-provider -a order_status --provider ".$providerId." --task_id ".$taskId." -o json");
		$jsonData = $shellAdapter->getLastOutput();

		if($execRes)
		{
			$arData = json_decode($jsonData, true);

			if(isset($arData["params"]["provider_order"][$providerId]))
				$result = $arData["params"]["provider_order"][$providerId];
		}

		return $result;
	}

	/**
	 * @param string $providerId Provider identifier.
	 * @return array List of orders.
	 * @throws ArgumentNullException
	 */
	public static function getOrdersList($providerId = "")
	{
		$result = array();
		$shellAdapter = new ShellAdapter();
		$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/bx-provider -a orders_list".(strlen($providerId) > 0 ? " --provider ".$providerId : "")." -o json");
		$jsonData = $shellAdapter->getLastOutput();

		if($execRes)
		{
			$arData = json_decode($jsonData, true);

			if(isset($arData["params"]["provider_order_list"]))
				$result = $arData["params"]["provider_order_list"];
		}

		return $result;
	}

	/**
	 * Add host from order to pull.
	 * @param string $providerId Provider identifier.
	 * @param string $taskId Task identifier.
	 * @return int
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function addToPullFromOrder($providerId, $taskId)
	{
		if(strlen($providerId) <= 0 )
			throw new ArgumentNullException("providerId");

		if(strlen($taskId) <= 0 )
			throw new ArgumentNullException("taskId");

		$result = false;
		$shellAdapter = new ShellAdapter();
		$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/bx-provider -a order_to_host --provider ".$providerId."  --task_id ".$taskId." -o json");
		$jsonData = $shellAdapter->getLastOutput();

		if($execRes)
		{
			$result = json_decode($jsonData, true);
		}

		return $result;
	}
} 