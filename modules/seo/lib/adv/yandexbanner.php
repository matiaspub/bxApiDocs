<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Seo\Adv;

use Bitrix\Main\Entity;
use Bitrix\Main;
use Bitrix\Seo\AdvEntity;
use Bitrix\Seo\Engine;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class YandexBannerTable
 *
 * Local mirror for Yandex.Direct banners
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ENGINE_ID int mandatory
 * <li> XML_ID string(255) mandatory
 * <li> LAST_UPDATE datetime optional
 * <li> SETTINGS string optional
 * <li> CAMPAIGN_ID int mandatory
 * <li> GROUP_ID int optional - Yandex.Direct supports groups only in Live version, so we add this entity but won't use it right now
 * <li> AUTO_QUANTITY char(1) optional
 * </ul>
 *
 * @package Bitrix\Seo
 **/

class YandexBannerTable extends AdvEntity
{
	const ENGINE = 'yandex_direct';

	const MAX_TITLE_LENGTH = 33;
	const MAX_TEXT_LENGTH = 75;

	const CACHE_LIFETIME = 3600;

	const MARKED = 'D';

	private static $engine = null;

	protected static $priorityList = array(
		-1 => Engine\YandexDirect::PRIORITY_LOW,
		0 => Engine\YandexDirect::PRIORITY_MEDIUM,
		1 => Engine\YandexDirect::PRIORITY_HIGH,
	);

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
		return 'b_seo_adv_banner';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array_merge(
			parent::getMap(),
			array(
				'CAMPAIGN_ID' => array(
					'data_type' => 'integer',
					'required' => true,
					'title' => Loc::getMessage('ADV_CAMPAIGN_ENTITY_CAMPAIGN_ID_FIELD'),
				),
				'GROUP_ID' => array(
					'data_type' => 'integer',
					'title' => Loc::getMessage('ADV_CAMPAIGN_ENTITY_GROUP_ID_FIELD'),
				),
				'AUTO_QUANTITY_OFF' => array(
					'data_type' => 'enum',
					'values' => array(static::INACTIVE, static::ACTIVE, static::MARKED),
					'title' => Loc::getMessage('ADV_CAMPAIGN_ENTITY_AUTO_QUANTITY_OFF_FIELD'),
				),
				'AUTO_QUANTITY_ON' => array(
					'data_type' => 'enum',
					'values' => array(static::INACTIVE, static::ACTIVE, static::MARKED),
					'title' => Loc::getMessage('ADV_CAMPAIGN_ENTITY_AUTO_QUANTITY_ON_FIELD'),
				),
				'CAMPAIGN' => array(
					'data_type' => 'Bitrix\Seo\Adv\YandexCampaignTable',
					'reference' => array('=this.CAMPAIGN_ID' => 'ref.ID'),
				),
				'GROUP' => array(
					'data_type' => 'Bitrix\Seo\Adv\YandexGroupTable',
					'reference' => array('=this.GROUP_ID' => 'ref.ID'),
				),
			)
		);
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
	 * Makes fields validation and adds new Yandex.Direct banner.
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
			$data["XML_ID"] = $data["SETTINGS"]["BannerID"];
		}

		$data["NAME"] = $data["SETTINGS"]["Title"];
		$data["ENGINE_ID"] = $engine->getId();

		$data['OWNER_ID'] = $ownerInfo['id'];
		$data['OWNER_NAME'] = $ownerInfo['login'];

		if(!static::$skipRemoteUpdate && $result->getType() == Entity\EventResult::SUCCESS)
		{
			try
			{
				$data["XML_ID"] = $engine->addBanner($data["SETTINGS"]);

				$bannerSettings = $engine->getBanners(array($data['XML_ID']));
				$data['SETTINGS'] = $bannerSettings[0];
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
	 * Makes fields validation and updates Yandex.Direct banner.
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
				if(is_array($data["SETTINGS"]['Phrases']))
				{
					$currentPhrases = $currentData["SETTINGS"]["Phrases"];

					foreach($data["SETTINGS"]['Phrases'] as $key => $phraseInfo)
					{
						foreach($currentPhrases as $k => $currentPhrase)
						{
							if($currentPhrase['Phrase'] == $phraseInfo['Phrase'])
							{
								$data["SETTINGS"]['Phrases'][$key]['PhraseID'] = $currentPhrase['PhraseID'];
								unset($currentPhrases[$k]);
							}
						}
					}
				}
			}

			$data["NAME"] = $data["SETTINGS"]["Title"];

			if(!static::$skipRemoteUpdate && $result->getType() == Entity\EventResult::SUCCESS)
			{
				try
				{
					$engine->updateBanner($data["SETTINGS"]);

					$bannerSettings = $engine->getBanners(array($data['XML_ID']));
					$data['SETTINGS'] = $bannerSettings[0];
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
	 * Deletes Yandex.Direct banner.
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
		if(!static::$skipRemoteUpdate)
		{
			$primary = $event->getParameter("primary");

			$engine = self::getEngine();

			$dbRes = static::getList(array(
				'filter' => array(
					'=ID' => $primary,
					'=ENGINE_ID' => $engine->getId(),
				),
				'select' => array(
					'XML_ID', 'CAMPAIGN_XML_ID' => 'CAMPAIGN.XML_ID',
				)
			));
			$banner = $dbRes->fetch();

			if($banner)
			{
				$engine->deleteBanners($banner['CAMPAIGN_XML_ID'], array($banner['XML_ID']));
			}
		}
	}

	/**
	 * Checks banner data before sending it to Yandex.
	 *
	 * $data array format:
	 *
	 * <ul>
	 * <li>ID
	 * <li>XML_ID
	 * <li>NAME
	 * <li>SETTINGS<ul>
	 *    <li>BannerID
	 *    <li>CampaignID *
	 *    <li>Title *
	 *    <li>Text *
	 *    <li>Href *
	 *    <li>Geo - comma-separated list of yandex location IDs
	 *    <li>Phrases *
	 *    <li>MinusKeywords
	 *  </ul>
	 * </ul>
	 *
	 * @param Engine\YandexDirect $engine Engine object.
	 * @param array $data Banner data.
	 * @param Entity\EventResult $result Event result object.
	 *
	 * @return array
	 * @see http://api.yandex.ru/direct/doc/reference/CreateOrUpdateBanner.xml
	 */
	protected static function createParam(Engine\YandexDirect $engine, array $data, Entity\EventResult $result)
	{
		$bannerParam = array();

		$newBanner = true;

		if(!empty($data["XML_ID"]))
		{
			$newBanner = false;
			$bannerParam["BannerID"] = $data["XML_ID"];
		}

		if(!empty($data["CAMPAIGN_ID"]))
		{
			$dbRes = YandexCampaignTable::getByPrimary($data["CAMPAIGN_ID"]);
			$campaign = $dbRes->fetch();
			if($campaign)
			{
				$data['SETTINGS']['CampaignID'] = $campaign['XML_ID'];
			}
			else
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('CAMPAIGN_ID'),
					Loc::getMessage('SEO_BANNER_ERROR_CAMPAIGN_NOT_FOUND')
				));
			}
		}

		if($newBanner || isset($data['SETTINGS']['CampaignID']))
		{
			$bannerParam['CampaignID'] = $data['SETTINGS']['CampaignID'];
		}

		if($newBanner || isset($data['SETTINGS']["Title"]))
		{
			$bannerParam["Title"] = trim($data['SETTINGS']["Title"]);

			if(strlen($bannerParam["Title"]) <= 0)
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('NAME'),
					Loc::getMessage('SEO_BANNER_ERROR_NO_NAME')
				));
			}
			elseif(strlen($bannerParam["Title"]) > static::MAX_TITLE_LENGTH)
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('NAME'),
					Loc::getMessage('SEO_BANNER_ERROR_LONG_NAME', array(
						"#MAX#" => static::MAX_TITLE_LENGTH,
					))
				));
			}
		}

		if($newBanner || isset($data['SETTINGS']["Text"]))
		{
			$bannerParam["Text"] = trim($data['SETTINGS']["Text"]);
			if(strlen($bannerParam["Text"]) <= 0)
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('SETTINGS'),
					Loc::getMessage('SEO_BANNER_ERROR_NO_TEXT')
				));
			}
			elseif(strlen($bannerParam["Text"]) > static::MAX_TEXT_LENGTH)
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('SETTINGS'),
					Loc::getMessage('SEO_BANNER_ERROR_LONG_TEXT', array(
						"#MAX#" => static::MAX_TEXT_LENGTH,
					))
				));
			}
		}

		if($newBanner || isset($data['SETTINGS']["Href"]))
		{
			$bannerParam["Href"] = trim($data['SETTINGS']["Href"]);
			if(strlen($bannerParam["Href"]) <= 0)
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('SETTINGS'),
					Loc::getMessage('SEO_BANNER_ERROR_NO_HREF')
				));
			}
		}

		if($newBanner || isset($data["SETTINGS"]["Geo"]))
		{
			if(is_array($data["SETTINGS"]["Geo"]))
			{
				$data["SETTINGS"]["Geo"] = implode(",", $data["SETTINGS"]["Geo"]);
			}

			$bannerParam["Geo"] = $data["SETTINGS"]["Geo"];
		}

		if($newBanner || isset($data["SETTINGS"]["Phrases"]))
		{
			if(!is_array($data["SETTINGS"]["Phrases"]) || count($data["SETTINGS"]["Phrases"]) <= 0)
			{
				$result->addError(new Entity\FieldError(
					static::getEntity()->getField('SETTINGS'),
					Loc::getMessage('SEO_BANNER_ERROR_NO_PHRASES')
				));
			}
			else
			{
				$bannerParam["Phrases"] = $data["SETTINGS"]["Phrases"];

				foreach($bannerParam["Phrases"] as $key => $phraseInfo)
				{
					$phraseInfo['AutoBudgetPriority'] = static::$priorityList[intval($phraseInfo['AutoBudgetPriority'])];

					$bannerParam["Phrases"][$key] = $phraseInfo;
				}
			}
		}

		if($newBanner || isset($data["SETTINGS"]["MinusKeywords"]))
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

			$bannerParam["MinusKeywords"] = $data["SETTINGS"]["MinusKeywords"];
		}

		if(!$newBanner && $result->getType() == Entity\EventResult::SUCCESS)
		{
			try
			{
				$yandexBannerParam = $engine->getBanners(array($data["XML_ID"]));

				if(!is_array($yandexBannerParam) || count($yandexBannerParam) <= 0)
				{
					$result->addError(new Entity\FieldError(
						static::getEntity()->getField('XML_ID'),
						Loc::getMessage(
							'SEO_CAMPAIGN_ERROR_BANNER_NOT_FOUND',
							array('#ID#' => $data["XML_ID"])
						)
					));
				}
				else
				{
					$bannerParam = array_replace_recursive($yandexBannerParam[0], $bannerParam);
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

		return $bannerParam;
	}

	public static function markStopped(array $idList)
	{
		if(count($idList) > 0)
		{
			$connection = Main\Application::getConnection();
			$sqlHelper = $connection->getSqlHelper();

			$idList = array_map("intval", $idList);

			$update = $sqlHelper->prepareUpdate(static::getTableName(), array(
				"AUTO_QUANTITY_OFF" => static::MARKED,
			));

			$connection->queryExecute(
				"UPDATE ".static::getTableName()." SET ".$update[0]." WHERE ID IN (".implode(",", $idList).")"
			);
		}
	}

	public static function markResumed(array $idList)
	{
		if(count($idList) > 0)
		{
			$connection = Main\Application::getConnection();
			$sqlHelper = $connection->getSqlHelper();

			$idList = array_map("intval", $idList);

			$update = $sqlHelper->prepareUpdate(static::getTableName(), array(
				"AUTO_QUANTITY_ON" => static::MARKED,
			));

			$connection->queryExecute(
				"UPDATE ".static::getTableName()." SET ".$update[0]." WHERE ID IN (".implode(",", $idList).")"
			);
		}
	}

	public static function unMarkStopped(array $idList)
	{
		if(count($idList) > 0)
		{
			$connection = Main\Application::getConnection();
			$sqlHelper = $connection->getSqlHelper();

			$idList = array_map("intval", $idList);

			$update = $sqlHelper->prepareUpdate(static::getTableName(), array(
				"AUTO_QUANTITY_OFF" => static::ACTIVE,
			));

			$connection->queryExecute(
				"UPDATE ".static::getTableName()." SET ".$update[0]." WHERE ID IN (".implode(",", $idList).")"
			);
		}
	}

	public static function unMarkResumed(array $idList)
	{
		if(count($idList) > 0)
		{
			$connection = Main\Application::getConnection();
			$sqlHelper = $connection->getSqlHelper();

			$idList = array_map("intval", $idList);

			$update = $sqlHelper->prepareUpdate(static::getTableName(), array(
				"AUTO_QUANTITY_ON" => static::ACTIVE,
			));

			$connection->queryExecute(
				"UPDATE ".static::getTableName()." SET ".$update[0]." WHERE ID IN (".implode(",", $idList).")"
			);
		}
	}
}
