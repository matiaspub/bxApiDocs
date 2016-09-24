<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo\Adv;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;
use Bitrix\Seo\AdvEntity;
use Bitrix\Seo\Engine;

Loc::loadMessages(__FILE__);

/**
 * Class YandexGroupTable
 *
 * Local mirror for Yandex.Direct banner groups
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ENGINE_ID int mandatory
 * <li> XML_ID string(255) mandatory
 * <li> LAST_UPDATE datetime optional
 * <li> SETTINGS string optional
 * <li> PARENT_ID int optional
 * </ul>
 *
 * @package Bitrix\Seo
 **/

class YandexRegionTable extends AdvEntity
{
	const ENGINE = 'yandex_direct';
	const CACHE_LIFETIME = 2592000;
	const OPTION_LAST_UPDATE = 'yandex_direct_region_last_update';

	private static $engine = null;

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_seo_adv_region';
	}

	public static function getMap()
	{
		return array_merge(
			parent::getMap(),
			array(
				'PARENT_ID' => array(
					'data_type' => 'integer',
					'title' => Loc::getMessage('ADV_REGION_ENTITY_PARENT_ID_FIELD'),
				),
				'PARENT' => array(
					'data_type' => 'Bitrix\Seo\Adv\YandexRegionTable',
					'reference' => array('=this.PARENT_ID' => 'ref.ID'),
				),
			)
		);
	}

	public static function getEngine()
	{
		if(!self::$engine)
		{
			self::$engine = new Engine\YandexDirect();
		}
		return self::$engine;
	}

	public static function getList($params)
	{
		if(static::needDatabaseUpdate())
		{
			static::updateDatabase();
		}

		return parent::getList($params);
	}

	public static function updateDatabase()
	{
		$engine = static::getEngine();
		$regionList = $engine->getRegions();

		$regionMap = array();
		foreach($regionList as $region)
		{
			$regionMap[$region['RegionID']] = $region;
		}

		static::clearDatabase();

		foreach($regionMap as $regionId => $region)
		{
			static::updateDatabaseItem($regionMap, $regionId);
		}

		static::setLastUpdate();
	}

	protected static function updateDatabaseItem(array &$regionMap, $regionId)
	{
		$region = $regionMap[$regionId];

		if(!$regionMap[$region["RegionID"]]["ID"])
		{
			$engine = static::getEngine();
			$ownerInfo = $engine->getCurrentUser();

			$parentId = 0;
			if($region["ParentID"] !== '')
			{
				if(array_key_exists($region["ParentID"], $regionMap))
				{
					if($regionMap[$region["ParentID"]]["ID"] > 0)
					{
						$parentId = $regionMap[$region["ParentID"]]["ID"];
					}
					else
					{
						$parentId = static::updateDatabaseItem(
							$regionMap,
							$region["ParentID"]
						);
					}
				}
			}

			$regionData = array(
				"ENGINE_ID" => $engine->getId(),
				"OWNER_ID" => $ownerInfo['id'],
				"OWNER_NAME" => $ownerInfo['login'],
				"XML_ID" => $region["RegionID"],
				"NAME" => $region["RegionName"],
				"PARENT_ID" => $parentId
			);

			$result = static::add($regionData);

			if($result->isSuccess())
			{
				$regionMap[$region["RegionID"]]["ID"] = $result->getId();
			}
		}

		return $regionMap[$region["RegionID"]]["ID"];
	}

	protected static function clearDatabase()
	{
		$connection = Application::getConnection();
		$connection->truncateTable(static::getTableName());
	}

	protected static function needDatabaseUpdate()
	{
		return time() - static::getLastUpdate() > static::CACHE_LIFETIME;
	}

	public static function setLastUpdate($v = null)
	{
		if($v === null)
		{
			$v = time();
		}

		Option::set('seo', static::OPTION_LAST_UPDATE, $v);
	}

	public static function getLastUpdate()
	{
		return Option::get('seo', static::OPTION_LAST_UPDATE, 0);
	}

}
