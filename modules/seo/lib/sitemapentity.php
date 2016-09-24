<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo;

use Bitrix\Main\Entity;

/**
 * Class SitemapEntityTable
 * @package Bitrix\Seo
 */
class SitemapEntityTable extends Entity\DataManager
{
	const ENTITY_TYPE = 'ENTITY';
	protected static $entityCache = array();

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_seo_sitemap_entity';
	}

	public static function add($sitemapId, $entityId )
	{
		return parent::add(array(
			'ENTITY_TYPE' => static::ENTITY_TYPE,
			'ENTITY_ID' => $entityId,
			'SITEMAP_ID' => $sitemapId,
		));
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'ENTITY_TYPE' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'SITEMAP_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'SITEMAP' => array(
				'data_type' => 'Bitrix\Seo\SitemapTable',
				'reference' => array('=this.SITEMAP_ID' => 'ref.ID'),
			)
		);

		return $fieldsMap;
	}

	public static function getSitemapsByEntityId($entityId)
	{
		if(!isset(self::$entityCache[$entityId.'Sitemaps']))
		{
			self::$entityCache[$entityId] = array();

			$dbRes = self::getList(array(
				'filter' => array(
					'ENTITY_TYPE' => static::ENTITY_TYPE,
					'ENTITY_ID' => $entityId
				),
				'select' => array(
					'SITEMAP_ID',
					'SITE_ID' => 'SITEMAP.SITE_ID',
					'SITEMAP_SETTINGS' => 'SITEMAP.SETTINGS'
				)
			));
			$arSitemaps = array();
			while($arRes = $dbRes->fetch())
			{
				$arRes["SITEMAP_SETTINGS"] = unserialize($arRes['SITEMAP_SETTINGS']);
				self::$entityCache[$entityId][] = $arRes;
				if ($arRes["SITEMAP_SETTINGS"][static::ENTITY_TYPE."_ACTIVE"] &&
					$arRes["SITEMAP_SETTINGS"][static::ENTITY_TYPE."_ACTIVE"][$entityId] == "Y")
				{
					$arSitemaps[] = array(
						'SITEMAP_ID' => $arRes['SITEMAP_ID'],
						'SITE_ID' => $arRes['SITE_ID'],
						'PROTOCOL' => $arRes["SITEMAP_SETTINGS"]['PROTO'] == 1 ? 'https' : 'http',
						'DOMAIN' => $arRes["SITEMAP_SETTINGS"]['DOMAIN'],
						'ROBOTS' => $arRes["SITEMAP_SETTINGS"]['ROBOTS'],
						'SITEMAP_DIR' => $arRes["SITEMAP_SETTINGS"]['DIR'],
						'SITEMAP_FILE' => $arRes["SITEMAP_SETTINGS"]['FILENAME_INDEX'],
						'SITEMAP_FILE_'.static::ENTITY_TYPE => $arRes["SITEMAP_SETTINGS"]['FILENAME_'.static::ENTITY_TYPE],
						'SITEMAP_SETTINGS' => $arRes["SITEMAP_SETTINGS"]
					);
				}
			}
			self::$entityCache[$entityId.'Sitemaps'] = $arSitemaps;
		}

		return self::$entityCache[$entityId.'Sitemaps'];
	}

	public static function clearBySitemap($sitemapId)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$connection->query("
DELETE
FROM ".self::getTableName()."
WHERE SITEMAP_ID=".intval($sitemapId)." AND ENTITY_TYPE='".static::ENTITY_TYPE."'
");
	}
}
