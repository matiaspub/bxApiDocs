<?php

namespace Bitrix\Sale\TradingPlatform\Ebay;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\TradingPlatform\Timer;
use Bitrix\Sale\TradingPlatform\Logger;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Sale\TradingPlatform\Ebay\Feed\Manager;
use Bitrix\Sale\TradingPlatform\TimeIsOverException;

/**
 * Class Agent
 * For periodically exchange data with sftp.
 * @package Bitrix\Sale\TradingPlatform\Ebay
 */
class Agent
{
	/**
	 * Starts data exchange.
	 * @param $feedType
	 * @param $siteId
	 * @param string $startPosition
	 * @param bool|false $once
	 * @return string
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */

	public static function start($feedType, $siteId, $startPosition="", $once = false)
	{
		if(empty($siteId))
			throw new ArgumentNullException('siteId');

		$siteId = \EscapePHPString($siteId);

		if(!in_array($feedType, array("ORDER", "PRODUCT", "INVENTORY", "IMAGE")))
			throw new ArgumentOutOfRangeException('feedType');

		$result = "";
		$timeLimit = 300;
		Ebay::log(Logger::LOG_LEVEL_DEBUG, "EBAY_AGENT_FEED_STARTED", $feedType, "Feed: ".$feedType.", site: ".$siteId.", start position: ".$startPosition, $siteId);

		try
		{
			if(in_array($feedType, array("ORDER", "PROCESS_RESULT", "RESULTS")))
			{
				$ebayFeed = Manager::createFeed($feedType, $siteId, $timeLimit);
				$ebayFeed->processData($startPosition);
			}
			else
			{
				$timer = new Timer($timeLimit);
				$queue = Manager::createSftpQueue($feedType, $siteId, $timer);
				$queue->sendData();
			}
		}
		catch(TimeIsOverException $e)
		{
			$result = 'Bitrix\Sale\TradingPlatform\Ebay\Agent::start("'.$feedType.'","'.$siteId.'","'.$e->getEndPosition().'",, '.($once ? 'true' : 'false').');';
		}
		catch(\Exception $e)
		{
			Ebay::log(Logger::LOG_LEVEL_ERROR, "EBAY_FEED_ERROR", $feedType, $e->getMessage(), $siteId);
		}

		if(strlen($result) <=0 && !$once)
			$result = 'Bitrix\Sale\TradingPlatform\Ebay\Agent::start("'.$feedType.'","'.$siteId.'");';

		return $result;
	}

	/**
	 * @param $feedType
	 * @param $siteId
	 * @param $interval
	 * @param bool|false $once
	 * @return bool|int
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	public static function add($feedType, $siteId, $interval, $once = false)
	{
		if($interval <= 0)
			return 0;

		if(empty($siteId))
			throw new ArgumentNullException('siteId');

		$siteId = \EscapePHPString($siteId);

		if($feedType == "ORDER")
			$sort = 50;
		elseif($feedType == "PRODUCT")
			$sort = 100;
		elseif($feedType == "INVENTORY" || $feedType == "IMAGE")
			$sort = 150;
		else
			throw new ArgumentOutOfRangeException('feedType');

		$intervalSeconds = $interval*60;
		$timeToStart = ConvertTimeStamp(strtotime(date('Y-m-d H:i:s', time() + $intervalSeconds)), 'FULL');

		$result =  \CAgent::AddAgent(
			self::createAgentNameForAdd($feedType, $siteId, $once),
			'sale',
			"N",
			$interval*60,
			$timeToStart,
			"Y",
			$timeToStart,
			$sort);

		Ebay::log(Logger::LOG_LEVEL_DEBUG, "EBAY_AGENT_ADDING_RESULT", $feedType, "Feed: ".$feedType.", site: ".$siteId.", interval: ".$interval." once: ".($once ? 'true' : 'false')." agentId: '".$result."'", $siteId);

		return $result;
	}

	protected static function createAgentNameForAdd($feedType, $siteId, $once = false)
	{
		return 'Bitrix\Sale\TradingPlatform\Ebay\Agent::start("'.$feedType.'","'.$siteId.'", "", '.($once ? 'true' : 'false').');';
	}


	/**
	 * Update agent's params.
	 * @param string $siteId Site id.
	 * @param array $feedSettings Feed settings.
	 * @return array Feed settings with renew agents ids.
	 */
	public static function update($siteId, array $feedSettings)
	{
		foreach($feedSettings as $feedType => $feedParams)
		{
			$interval = intval($feedParams["INTERVAL"]);

			$dbRes = \CAgent::GetList(
				array(),
				array(
					'NAME' => self::createAgentNameForAdd($feedType, $siteId)
				)
			);

			if($agent = $dbRes->Fetch())
			{
				if($interval <= 0)
				{
					\CAgent::Delete($agent["ID"]);
					$feedSettings[$feedType]["AGENT_ID"] = 0;
					continue;
				}

				\CAgent::Update(
					$agent["ID"],
					array('AGENT_INTERVAL' => $interval*60)
				);

				$feedSettings[$feedType]["AGENT_ID"] = $agent["ID"];
			}
			else
			{
				$feedSettings[$feedType]["AGENT_ID"] = self::add($feedType, $siteId, $feedParams["INTERVAL"]);
			}
		}

		return $feedSettings;
	}
}