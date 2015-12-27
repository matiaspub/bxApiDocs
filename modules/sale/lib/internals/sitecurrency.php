<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Sale\Internals;

use	Bitrix\Main,
	Bitrix\Main\Application;

class SiteCurrencyTable extends Main\Entity\DataManager
{
	private static $cache = array();

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

	/**
	 * Returns site currency data.
	 *
	 * @param string $siteId		Site id.
	 * @return bool|array
	 */
	public static function getCurrency($siteId)
	{
		$siteId = (string)$siteId;
		if ($siteId == '')
			return false;
		if (empty(self::$cache))
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
				unset($row, $result);
				$managed->set($key, self::$cache);
			}
		}
		return (isset(self::$cache[$siteId]) ? self::$cache[$siteId] : false);
	}

	/**
	 * Returns site currency.
	 *
	 * @param string $siteId				Site id.
	 * @return bool|string
	 */
	public static function getSiteCurrency($siteId)
	{
		$siteData = self::getCurrency($siteId);
		return (!empty($siteData['CURRENCY']) ? $siteData['CURRENCY'] : (string)Main\Config\Option::get('sale', 'default_currency'));
	}

	public static function onAfterAdd(Main\Entity\Event $event)
	{
		Application::getInstance()->getManagedCache()->clean(self::getTableName());
		self::$cache = array();
	}

	public static function onAfterUpdate(Main\Entity\Event $event)
	{
		Application::getInstance()->getManagedCache()->clean(self::getTableName());
		self::$cache = array();
	}

	public static function onAfterDelete(Main\Entity\Event $event)
	{
		Application::getInstance()->getManagedCache()->clean(self::getTableName());
		self::$cache = array();
	}
}