<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Sale;

use	Bitrix\Main\Entity,
	Bitrix\Main\Application;

class SiteCurrencyTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_lang';
	}

	public static function getMap()
	{
		return array(
			'LID' => array(
				'data_type' => 'string',
				'primary' => true,
				'format' => '/^[A-Za-z0-9_]{2}$/'
			),
			'CURRENCY' => array(
				'data_type' => 'string',
				'format' => '/^[A-Z]{3}$/'
			)
		);
	}

	private static $cache = array();

	/**
	 * @param string $siteId - LID
	 * @return array - LID, CURRENCY
	 */
	public static function getCurrency($siteId)
	{
		if (! self::$cache)
		{
			$managed = Application::getInstance()->getManagedCache();
			$key = self::getTableName();

			if ($managed->read(3600, $key))
				self::$cache = $managed->get($key);
			else
			{
				$result = self::getList(array(
					'select' => array('*')
				));
				while ($row = $result->fetch())
					self::$cache[$row['LID']] = $row;
				$managed->set($key, self::$cache);
			}
		}
		return self::$cache[$siteId];
	}

	public static function onAfterAdd(Entity\Event $event)
	{
		Application::getInstance()->getManagedCache()->clean(self::getTableName());
		self::$cache = array();
	}

	public static function onAfterUpdate(Entity\Event $event)
	{
		Application::getInstance()->getManagedCache()->clean(self::getTableName());
		self::$cache = array();
	}

	public static function onAfterDelete(Entity\Event $event)
	{
		Application::getInstance()->getManagedCache()->clean(self::getTableName());
		self::$cache = array();
	}
}
