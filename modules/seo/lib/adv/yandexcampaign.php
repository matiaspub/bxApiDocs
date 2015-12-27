<?php
namespace Bitrix\Seo\Adv;

use Bitrix\Main\Entity;
use Bitrix\Main;
use Bitrix\Seo\AdvEntity;
use Bitrix\Seo\Engine;
use Bitrix\Main\Localization\Loc;
use Bitrix\Seo\Service;

Loc::loadMessages(__FILE__);

/**
 * Class YandexCampaignTable
 *
 * Local mirror for Yandex.Direct campaigns
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ENGINE_ID int mandatory
 * <li> XML_ID string(255) mandatory
 * <li> LAST_UPDATE datetime optional
 * <li> SETTINGS string optional
 * </ul>
 *
 * @package Bitrix\Seo
 **/

class YandexCampaignTable extends AdvEntity
{
	const ENGINE = 'yandex_direct';

	const STRATEGY_WEEKLY_BUDGET = 'WeeklyBudget';
	const STRATEGY_WEEKLY_PACKET_OF_CLICKS = 'WeeklyPacketOfClicks';
	const STRATEGY_AVERAGE_CLICK_PRICE = 'AverageClickPrice';

	const MONEY_WARNING_VALUE_DEFAULT = 20;
	const MONEY_WARN_PLACE_INTERVAL_DEFAULT = 30;

	const CACHE_LIFETIME = 3600;

	public static $allowedWarnPlaceIntervalValues = array(15, 30, 60);
	public static $allowedMoneyWarningInterval = array(1, 50);
	public static $supportedStrategy = array(
		"WEEKLY_BUDGET" => self::STRATEGY_WEEKLY_BUDGET,
		"WEEKLY_PACKET_OF_CLICKS" => self::STRATEGY_WEEKLY_PACKET_OF_CLICKS,
		"AVERAGE_CLICK_PRICE" => self::STRATEGY_AVERAGE_CLICK_PRICE,
	);

	public static $strategyConfig = array(
		self::STRATEGY_WEEKLY_BUDGET => array(
			'WeeklySumLimit' => array(
				'type' => 'float',
				'mandatory' => true,
			),
			'MaxPrice' => array(
				'type' => 'float',
				'mandatory' => false,
			),
		),
		self::STRATEGY_WEEKLY_PACKET_OF_CLICKS => array(
			'ClicksPerWeek' => array(
				'type' => 'int',
				'mandatory' => true,
			),
			'MaxPrice' => array(
				'type' => 'float',
				'mandatory' => false,
			),
			'AveragePrice' => array(
				'type' => 'float',
				'mandatory' => false,
			),
		),
		self::STRATEGY_AVERAGE_CLICK_PRICE => array(
			'AveragePrice' => array(
				'type' => 'float',
				'mandatory' => true,
			),
			'WeeklySumLimit' => array(
				'type' => 'float',
				'mandatory' => false,
			),
		),
	);

	private static $engine = null;

	/**
	 * Returns path to the file which contains definition of the class.
	 *
	 * @return string
	 */
	public static function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_seo_adv_campaign';
	}

	/**
	 * Returns link to transport engine object.
	 *
	 * @return Engine\YandexDirect|null
	 */
	public static function getEngine()
	{
		if(!self::$engine)
		{
			self::$engine = new Engine\YandexDirect();
		}
		return self::$engine;
	}

	/**
	 * Makes fields validation and adds new Yandex.Direct campaign.
	 *
	 * @param Entity\Event $event Event data.
	 *
	 * @return Entity\EventResult
	 *
	 * @throws Engine\YandexException
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 */
	public static function onBeforeAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult();

		$data = $event->getParameter("fields");

		$engine = self::getEngine();

		$ownerInfo = $engine->getCurrentUser();

		if(!static::$skipRemoteUpdate)
		{
			$data["SETTINGS"] = self::createParam($engine, $data, $result);
			$data["XML_ID"] = 'Error';
		}
		else
		{
			$data["XML_ID"] = $data["SETTINGS"]["CampaignID"];
		}

		$data["NAME"] = $data["SETTINGS"]["Name"];

		$data["ENGINE_ID"] = $engine->getId();

		$data['OWNER_ID'] = $ownerInfo['id'];
		$data['OWNER_NAME'] = $ownerInfo['login'];

		if(!static::$skipRemoteUpdate && $result->getType() == Entity\EventResult::SUCCESS)
		{
			try
			{
				$data["XML_ID"] = $engine->addCampaign($data["SETTINGS"]);

				$campaignSettings = $engine->getCampaign(array($data['XML_ID']));
				$data['SETTINGS'] = $campaignSettings[0];
			}
			catch(Engine\YandexDirectException $e)
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('ENGINE_ID'),
					$e->getMessage(),
					$e->getCode()
				));
			}
		}

		$data['LAST_UPDATE'] = new Main\Type\DateTime();
		$data['ACTIVE'] = $data['SETTINGS']['StatusArchive'] == Engine\YandexDirect::BOOL_YES
			? static::INACTIVE
			: static::ACTIVE;

		$result->modifyFields($data);

		return $result;
	}

	/**
	 * Makes fields validation and updates Yandex.Direct campaign.
	 *
	 * @param Entity\Event $event Event data.
	 *
	 * @return Entity\EventResult
	 *
	 * @throws Engine\YandexException
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 */
	public static function onBeforeUpdate(Entity\Event $event)
	{
		$result = new Entity\EventResult();

		$primary = $event->getParameter("primary");
		$data = $event->getParameter("fields");

		$currentData = static::getByPrimary($primary);
		$currentData = $currentData->fetch();

		if($currentData)
		{
			$engine = self::getEngine();

			if($currentData['ENGINE_ID'] != $engine->getId())
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('ENGINE_ID'),
					Loc::getMessage("SEO_CAMPAIGN_ERROR_WRONG_ENGINE")
				));
			}

			$ownerInfo = $engine->getCurrentUser();

			if($currentData['OWNER_ID'] != $ownerInfo['id'])
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('OWNER_ID'),
					Loc::getMessage("SEO_CAMPAIGN_ERROR_WRONG_OWNER")
				));
			}

			$data['OWNER_NAME'] = $ownerInfo['login'];
			$data['XML_ID'] = $currentData['XML_ID'];

			if(!static::$skipRemoteUpdate)
			{
				$data["SETTINGS"] = self::createParam($engine, $data, $result);
			}

			$data["NAME"] = $data['SETTINGS']['Name'];

			if(!static::$skipRemoteUpdate && $result->getType() == Entity\EventResult::SUCCESS)
			{
				try
				{
					$engine->updateCampaign($data["SETTINGS"]);

					$campaignSettings = $engine->getCampaign(array($data['XML_ID']));
					$data['SETTINGS'] = $campaignSettings[0];
				}
				catch(Engine\YandexDirectException $e)
				{
					$result->addError(
						new Entity\FieldError(
							static::getEntity()->getField('ENGINE_ID'),
							$e->getMessage(),
							$e->getCode()
						)
					);
				}
			}

			$data['LAST_UPDATE'] = new Main\Type\DateTime();
			$data['ACTIVE'] = $data['SETTINGS']['StatusArchive'] == Engine\YandexDirect::BOOL_YES
				? static::INACTIVE
				: static::ACTIVE;

			$result->modifyFields($data);
		}

		return $result;
	}

	/**
	 * Deletes Yandex.Direct campaign.
	 *
	 * @param Entity\Event $event Event data.
	 *
	 * @return void
	 *
	 * @throws Engine\YandexException
	 * @throws Main\ArgumentException
	 */
	public static function onDelete(Entity\Event $event)
	{
		$primary = $event->getParameter("primary");

		$dbRes = static::getByPrimary($primary);
		$campaign = $dbRes->fetch();

		$engine = self::getEngine();

		if($campaign && $campaign['ENGINE_ID'] == $engine->getId())
		{
			if(!static::$skipRemoteUpdate)
			{
				$engine->deleteCampaign($campaign['XML_ID']);
			}
		}
	}

	/**
	 * Deletes all campaign banners.
	 *
	 * @param Entity\Event $event Event data.
	 *
	 * @return void
	 *
	 * @throws Main\ArgumentException
	 */
	public static function onAfterDelete(Entity\Event $event)
	{
		$primary = $event->getParameter("primary");

		$engine = self::getEngine();

		$dbRes = YandexBannerTable::getList(array(
			'filter' => array(
				'=CAMPAIGN_ID' => $primary,
				'=ENGINE_ID' => $engine->getId(),
			),
			'select' => array('ID')
		));

		YandexBannerTable::setSkipRemoteUpdate(true);
		while($banner = $dbRes->fetch())
		{
			YandexBannerTable::delete($banner['ID']);
		}
		YandexBannerTable::setSkipRemoteUpdate(false);
	}

	/**
	 * Checks campaign data before sending it to Yandex
	 *
	 * $data array format:
	 *
	 * <ul>
	 * <li>ID
	 * <li>XML_ID
	 * <li>NAME
	 * <li>SETTINGS<ul>
	 *    <li>FIO
	 *    <li>StartDate
	 *    <li>Strategy<ul>
	 *        <li>StrategyName
	 *        <li>MaxPrice
	 *        <li>AveragePrice
	 *        <li>WeeklySumLimit
	 *        <li>ClicksPerWeek
	 *    </ul>
	 *    <li>EmailNotification<ul>
	 *        <li>Email
	 *        <li>WarnPlaceInterval
	 *        <li>MoneyWarningValue
	 *    </ul>
	 *  </ul>
	 * </ul>
	 *
	 * @param Engine\YandexDirect $engine Engine object.
	 * @param array $data Campaign data.
	 * @param Entity\EventResult $result Event result object.
	 *
	 * @return array
	 * @see http://api.yandex.ru/direct/doc/reference/CreateOrUpdateCampaign.xml
	 */
	protected static function createParam(Engine\YandexDirect $engine, array $data, Entity\EventResult $result)
	{
		$settings = $engine->getSettings();

		$campaignParam = array(
			"Login" => $settings["AUTH_USER"]["login"],
		);

		$newCampaign = true;

		if(!empty($data["XML_ID"]))
		{
			$newCampaign = false;
			$campaignParam["CampaignID"] = $data["XML_ID"];
		}

		if($newCampaign || isset($data['SETTINGS']["Name"]))
		{
			$campaignParam["Name"] = trim($data['SETTINGS']["Name"]);
			if(strlen($campaignParam["Name"]) <= 0)
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('NAME'),
					Loc::getMessage('SEO_CAMPAIGN_ERROR_NO_NAME')
				));
			}
		}

		if($newCampaign || isset($data["SETTINGS"]["FIO"]))
		{
			$campaignParam["FIO"] = trim($data["SETTINGS"]["FIO"]);

			if(strlen($campaignParam["FIO"]) <= 0)
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('SETTINGS'),
					Loc::getMessage('SEO_CAMPAIGN_ERROR_NO_FIO')
				));
			}
		}

		if(is_array($data["SETTINGS"]) && array_key_exists("StartDate", $data["SETTINGS"]))
		{
			if(is_a($data["SETTINGS"]["StartDate"], "Bitrix\\Main\\Type\\Date"))
			{
				$campaignParam["StartDate"] = $data["SETTINGS"]["StartDate"]->convertFormatToPhp("Y-m-d");
			}
			elseif(is_string($data["SETTINGS"]["StartDate"]))
			{
				if(preg_match("/^\\d{4}-\\d{2}-\\d{2}$/", $data["SETTINGS"]["StartDate"]))
				{
					$campaignParam["StartDate"] = $data["SETTINGS"]["StartDate"];
				}
				else
				{
					$ts = MakeTimeStamp($data["SETTINGS"]["StartDate"], FORMAT_DATE);
					if($ts > 0)
					{
						$campaignParam["StartDate"] = date('Y-m-d', $ts);
					}
				}
			}

			if(!$campaignParam["StartDate"])
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('SETTINGS'),
					Loc::getMessage('SEO_CAMPAIGN_ERROR_NO_START_DATE')
				));
			}
		}

		if($newCampaign || isset($data["SETTINGS"]["Strategy"]))
		{
			if(
				empty($data["SETTINGS"]["Strategy"])
				|| !is_array($data["SETTINGS"]["Strategy"])
				|| empty($data["SETTINGS"]["Strategy"]["StrategyName"])
			)
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('SETTINGS'),
					Loc::getMessage('SEO_CAMPAIGN_ERROR_NO_STRATEGY')
				));
			}

			if(array_key_exists($data["SETTINGS"]["Strategy"]["StrategyName"], self::$strategyConfig))
			{
				$strategy = $data["SETTINGS"]["Strategy"]["StrategyName"];
				$config = self::$strategyConfig[$strategy];

				$campaignParam["Strategy"] = array(
					"StrategyName" => $strategy,
				);

				foreach($data["SETTINGS"]["Strategy"] as $param => $value)
				{
					if($param !== "StrategyName")
					{
						if(array_key_exists($param, $config))
						{
							$campaignParam["Strategy"][$param] = $value;
						}
						else
						{
							$result->addError(new Entity\FieldError(
								static::getEntity()->getField('SETTINGS'),
								Loc::getMessage(
									'SEO_CAMPAIGN_ERROR_STRATEGY_PARAM_NOT_SUPPORTED',
									array(
										'#PARAM#' => $param,
										'#STRATEGY#' => $strategy,
									)
								)
							));
						}
					}
				}

				foreach($config as $key => $def)
				{
					if($def['mandatory'] || isset($campaignParam["Strategy"][$key]))
					{
						switch($def['type'])
						{
							case 'int':
								$campaignParam["Strategy"][$key] = intval($campaignParam["Strategy"][$key]);
							break;
							case 'float':
								$campaignParam["Strategy"][$key] = doubleval($campaignParam["Strategy"][$key]);
							break;
						}

						if(!$def['mandatory'] && empty($campaignParam["Strategy"][$key]))
						{
							unset($campaignParam["Strategy"][$key]);
						}
					}

					if($def['mandatory'] && empty($campaignParam["Strategy"][$key]))
					{
						$result->addError(new Entity\FieldError(
							static::getEntity()->getField('SETTINGS'),
							Loc::getMessage(
								'SEO_CAMPAIGN_ERROR_STRATEGY_PARAM_MANDATORY',
								array(
									'#PARAM#' => Loc::getMessage('SEO_CAMPAIGN_STRATEGY_PARAM_'.ToUpper($key)),
									'#STRATEGY#' => Loc::getMessage('SEO_CAMPAIGN_STRATEGY_'.$strategy),
								)
							)
						));
					}
				}
			}
			else
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('SETTINGS'),
					Loc::getMessage(
						'SEO_CAMPAIGN_ERROR_STRATEGY_NOT_SUPPORTED',
						array(
							'#STRATEGY#' => $data["SETTINGS"]["Strategy"]["StrategyName"],
						)
					)
				));
			}
		}

		if($newCampaign || !empty($data["SETTINGS"]["EmailNotification"]))
		{
			if(
				empty($data["SETTINGS"]["EmailNotification"])
				|| !is_array($data["SETTINGS"]["EmailNotification"])
				|| !check_email($data["SETTINGS"]["EmailNotification"]['Email'])
			)
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('SETTINGS'),
					Loc::getMessage('SEO_CAMPAIGN_ERROR_WRONG_EMAIL')
				));
			}

			$campaignParam["EmailNotification"] = array(
				"Email" => trim($data["SETTINGS"]["EmailNotification"]['Email']),
				"WarnPlaceInterval" => intval($data["SETTINGS"]["EmailNotification"]['WarnPlaceInterval']),
				"MoneyWarningValue" => intval($data["SETTINGS"]["EmailNotification"]['MoneyWarningValue']),
				"SendWarn" => intval($data["SETTINGS"]["EmailNotification"]['SendWarn']),
			);

			if($campaignParam["EmailNotification"]['SendWarn'] === true
				|| $campaignParam["EmailNotification"]['SendWarn'] === 1
				|| $campaignParam["EmailNotification"]['SendWarn'] === 'Y'
			)
			{
				$campaignParam["EmailNotification"]['SendWarn'] = Engine\YandexDirect::BOOL_YES;
			}

			if(
				$campaignParam["EmailNotification"]['SendWarn'] === false
				|| $campaignParam["EmailNotification"]['SendWarn'] === 0
				|| $campaignParam["EmailNotification"]['SendWarn'] === 'N'
			)
			{
				$campaignParam["EmailNotification"]['SendWarn'] = Engine\YandexDirect::BOOL_NO;
			}

			if(!in_array($campaignParam["EmailNotification"]["WarnPlaceInterval"], self::$allowedWarnPlaceIntervalValues))
			{
				if($campaignParam["EmailNotification"]['SendWarn'] == Engine\YandexDirect::BOOL_YES)
				{
					$result->addError(new Entity\FieldError(
						static::getEntity()->getField('SETTINGS'),
						Loc::getMessage(
							'SEO_CAMPAIGN_ERROR_WRONG_INTERVAL',
							array('#VALUES#' => implode(
								',', self::$allowedWarnPlaceIntervalValues
							))
						)
					));
				}
				else
				{
					$campaignParam["EmailNotification"]["WarnPlaceInterval"] = self::MONEY_WARN_PLACE_INTERVAL_DEFAULT;
				}
			}

			if(
				$campaignParam["EmailNotification"]["MoneyWarningValue"] < self::$allowedMoneyWarningInterval[0]
				|| $campaignParam["EmailNotification"]["MoneyWarningValue"] > self::$allowedMoneyWarningInterval[1]
			)
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('SETTINGS'),
					Loc::getMessage(
						'SEO_CAMPAIGN_ERROR_WRONG_WARNING',
						array(
							'#MIN#' => self::$allowedMoneyWarningInterval[0],
							'#MAX#' => self::$allowedMoneyWarningInterval[1],
						)
					)
				));
			}
		}

		if($newCampaign || isset($data["SETTINGS"]["MinusKeywords"]))
		{
			if(!is_array($data["SETTINGS"]["MinusKeywords"]))
			{
				if(strlen($data["SETTINGS"]["MinusKeywords"]) > 0)
				{
					$data["SETTINGS"]["MinusKeywords"] = array();
				}
				else
				{
					$data["SETTINGS"]["MinusKeywords"] = array($data["SETTINGS"]["MinusKeywords"]);
				}
			}

			$campaignParam["MinusKeywords"] = $data["SETTINGS"]["MinusKeywords"];
		}

		if(!$newCampaign && $result->getType() == Entity\EventResult::SUCCESS)
		{
			try
			{
				$yandexCampaignParam = $engine->getCampaign($data["XML_ID"]);

				if(!is_array($yandexCampaignParam) || count($yandexCampaignParam) <= 0)
				{
					$result->addError(new Entity\FieldError(
						static::getEntity()->getField('XML_ID'),
						Loc::getMessage(
							'SEO_CAMPAIGN_ERROR_CAMPAIGN_NOT_FOUND',
							array('#ID#' => $data["XML_ID"])
						)
					));
				}
				else
				{
					$campaignParam = array_replace_recursive($yandexCampaignParam[0], $campaignParam);
				}
			}
			catch(Engine\YandexDirectException $e)
			{
				$result->addError(
					new Entity\FieldError(
						static::getEntity()->getField('ENGINE_ID'),
						$e->getMessage(),
						$e->getCode()
					)
				);
			}
		}

		return $campaignParam;
	}
}
