<?php
namespace Bitrix\Main\Config;

use Bitrix\Main;
use Bitrix\Main\IO;

class Option
{
	static $options = array();

	public static function get($moduleId, $name, $default = "", $siteId = "")
	{
		if (empty($moduleId))
			throw new Main\ArgumentNullException("moduleId");
		if (empty($name))
			throw new Main\ArgumentNullException("name");

		if ($siteId == "")
		{
			/** @var $app \Bitrix\Main\HttpApplication */
			$app = Main\Application::getInstance();
			/** @var $page \Bitrix\Main\PublicPage */
			$page = $app->getPage();
			if (($page != null) && ($page instanceof Main\PublicPage))
			{
				$site = $page->getSite();
				if ($site)
					$siteId = $site->getId();
			}
		}

		self::load($moduleId, $siteId);

		$siteKey = ($siteId == "") ? "-" : $siteId;

		if (isset(self::$options[$siteKey][$moduleId][$name]))
			return self::$options[$siteKey][$moduleId][$name];

		if (isset(self::$options["-"][$moduleId][$name]))
			return self::$options["-"][$moduleId][$name];

		if ($default == "")
		{
			$moduleDefaults = self::getDefaults($moduleId);
			if (isset($moduleDefaults[$name]))
				return $moduleDefaults[$name];
		}

		return $default;
	}

	public static function getRealValue($moduleId, $name, $siteId = "")
	{
		if (empty($moduleId))
			throw new Main\ArgumentNullException("moduleId");
		if (empty($name))
			throw new Main\ArgumentNullException("name");

		self::load($moduleId, $siteId);

		$siteKey = ($siteId == "") ? "-" : $siteId;

		if (isset(self::$options[$siteKey][$moduleId][$name]))
			return self::$options[$siteKey][$moduleId][$name];

		return null;
	}

	private static function getDefaults($moduleId)
	{
		static $defaultsCache = array();
		if (isset($defaultsCache[$moduleId]))
			return $defaultsCache[$moduleId];

		if (!IO\Path::validateFilename($moduleId))
			throw new Main\ArgumentOutOfRangeException("moduleId");

		$path = IO\Path::convertRelativeToAbsolute("/bitrix/modules/".$moduleId."/default_option.php");
		if (!IO\File::isFileExists($path))
			return $defaultsCache[$moduleId] = array();

		include(IO\Path::convertLogicalToPhysical($path));

		$varName = str_replace(".", "_", $moduleId)."_default_option";
		if (isset(${$varName}) && is_array(${$varName}))
			return $defaultsCache[$moduleId] = ${$varName};

		return $defaultsCache[$moduleId] = array();
	}

	private static function load($moduleId, $siteId)
	{
		$siteKey = ($siteId == "") ? "-" : $siteId;

		$cacheTtl = self::getCacheTtl();
		if ($cacheTtl === false)
		{
			if (!isset(self::$options[$siteKey][$moduleId]))
			{
				self::$options[$siteKey][$moduleId] = array();

				$con = \Bitrix\Main\Application::getDbConnection();
				$sqlHelper = $con->getSqlHelper();

				$res = $con->query(
					"SELECT SITE_ID, NAME, VALUE ".
					"FROM b_option ".
					"WHERE (SITE_ID = '".$sqlHelper->forSql($siteId, 2)."' OR SITE_ID IS NULL) ".
					"	AND MODULE_ID = '". $sqlHelper->forSql($moduleId)."' "
				);
				while ($ar = $res->fetch())
				{
					$s = ($ar["SITE_ID"] == "") ? "-" : $ar["SITE_ID"];
					self::$options[$s][$moduleId][$ar["NAME"]] = $ar["VALUE"];
				}
			}
		}
		else
		{
			if (empty(self::$options))
			{
				$cache = \Bitrix\Main\Application::getInstance()->getManagedCache();
				if ($cache->read($cacheTtl, "b_option"))
				{
					self::$options = $cache->get("b_option");
				}
				else
				{
					$con = \Bitrix\Main\Application::getDbConnection();

					$res = $con->query(
						"SELECT o.SITE_ID, o.MODULE_ID, o.NAME, o.VALUE ".
						"FROM b_option o "
					);
					while ($ar = $res->fetch())
					{
						$s = ($ar["SITE_ID"] == "") ? "-" : $ar["SITE_ID"];
						self::$options[$s][$ar["MODULE_ID"]][$ar["NAME"]] = $ar["VALUE"];
					}
					$cache->set("b_option", self::$options);
				}
			}
		}
	}

	public static function set($moduleId, $name, $value = "", $siteId = "")
	{
		$cacheTtl = self::getCacheTtl();
		if ($cacheTtl !== false)
		{
			$cache = \Bitrix\Main\Application::getInstance()->getManagedCache();
			$cache->clean("b_option");
		}

		$con = \Bitrix\Main\Application::getDbConnection();
		$sqlHelper = $con->getSqlHelper();

		$strSqlWhere = sprintf(
			"SITE_ID %s AND MODULE_ID = '%s' AND NAME = '%s'",
			($siteId == "") ? "IS NULL" : "= '".$sqlHelper->forSql($siteId, 2)."'",
			$sqlHelper->forSql($moduleId),
			$sqlHelper->forSql($name)
		);

		$res = $con->queryScalar(
			"SELECT 'x' ".
			"FROM b_option ".
			"WHERE ".$strSqlWhere
		);

		if ($res != null)
		{
			$con->queryExecute(
				"UPDATE b_option SET ".
				"	VALUE = '".$sqlHelper->forSql($value, 2000)."' ".
				"WHERE ".$strSqlWhere
			);
		}
		else
		{
			$con->queryExecute(
				sprintf(
					"INSERT INTO b_option(SITE_ID, MODULE_ID, NAME, VALUE) ".
					"VALUES(%s, '%s', '%s', '%s') ",
					($siteId == "") ? "NULL" : "'".$sqlHelper->forSql($siteId, 2)."'",
					$sqlHelper->forSql($moduleId, 50),
					$sqlHelper->forSql($name, 50),
					$sqlHelper->forSql($value, 2000)
				)
			);
		}

		if ($siteId == "")
			$siteId = '-';

		self::$options[$siteId][$moduleId][$name] = $value;

		self::loadTriggers($moduleId);

		$event = new Main\Event(
			"main",
			"OnAfterSetOption_".$name,
			array("value" => $value)
		);
		$event->send();

		return;
	}

	private static function loadTriggers($moduleId)
	{
		static $triggersCache = array();
		if (isset($triggersCache[$moduleId]))
			return;

		if (!IO\Path::validateFilename($moduleId))
			throw new Main\ArgumentOutOfRangeException("moduleId");

		$triggersCache[$moduleId] = true;

		$path = IO\Path::convertRelativeToAbsolute("/bitrix/modules/".$moduleId."/option_triggers.php");
		if (!IO\File::isFileExists($path))
			return;

		include(IO\Path::convertLogicalToPhysical($path));
	}

	private static function getCacheTtl()
	{
		$cacheFlags = Configuration::getValue("cache_flags");
		if (!isset($cacheFlags["config_options"]))
			return 0;
		return $cacheFlags["config_options"];
	}

	public static function delete($moduleId, $name = "", $siteId = "")
	{
		$cacheTtl = self::getCacheTtl();
		if ($cacheTtl !== false)
		{
			$cache = \Bitrix\Main\Application::getInstance()->getManagedCache();
			$cache->clean("b_option");
		}

		$con = \Bitrix\Main\Application::getDbConnection();
		$sqlHelper = $con->getSqlHelper();

		$strSqlWhere = "";
		if ($name != "")
			$strSqlWhere .= " AND NAME = '".$sqlHelper->forSql($name)."' ";
		if ($siteId != "")
			$strSqlWhere .= " AND SITE_ID = '".$sqlHelper->forSql($siteId)."' ";

		if ($moduleId == "main")
		{
			$con->queryExecute(
				"DELETE FROM b_option ".
				"WHERE MODULE_ID = 'main' ".
				"   AND NAME NOT LIKE '~%' ".
				"   AND NAME <> 'crc_code' ".
				"   AND NAME <> 'admin_passwordh' ".
				"   AND NAME <> 'server_uniq_id' ".
				"   AND NAME <> 'PARAM_MAX_SITES' ".
				"   AND NAME <> 'PARAM_MAX_USERS' ".
				$strSqlWhere
			);
		}
		else
		{
			$con->queryExecute(
				"DELETE FROM b_option ".
				"WHERE MODULE_ID = '".$sqlHelper->forSql($moduleId)."' ".
				"   AND NAME <> '~bsm_stop_date' ".
				$strSqlWhere
			);
		}

		if ($siteId != "")
		{
			if ($name == "")
				unset(self::$options[$siteId][$moduleId]);
			else
				unset(self::$options[$siteId][$moduleId][$name]);
		}
		else
		{
			$arSites = array_keys(self::$options);
			foreach ($arSites as $s)
			{
				if ($name == "")
					unset(self::$options[$s][$moduleId]);
				else
					unset(self::$options[$s][$moduleId][$name]);
			}
		}
	}
}