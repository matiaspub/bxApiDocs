<?php
namespace Bitrix\Main;

final class ModuleManager
{
	private static $installedModules = array();

	public static function getInstalledModules()
	{
		if (empty(self::$installedModules))
		{
			$cacheManager = Application::getInstance()->getManagedCache();
			if ($cacheManager->read(3600, "b_module"))
				self::$installedModules = $cacheManager->get("b_module");

			if (empty(self::$installedModules))
			{
				self::$installedModules = array();
				$con = Application::getConnection();
				$rs = $con->query("SELECT ID FROM b_module ORDER BY ID");
				while ($ar = $rs->fetch())
					self::$installedModules[$ar['ID']] = $ar;
				$cacheManager->set("b_module", self::$installedModules);
			}
		}

		return self::$installedModules;
	}

	public static function getVersion($moduleName)
	{
		$moduleName = preg_replace("/[^a-zA-Z0-9_.]+/i", "", trim($moduleName));
		if ($moduleName == '')
			return false;

		if (!self::isModuleInstalled($moduleName))
			return false;

		$version = false;

		if ($moduleName == 'main')
		{
			if (!defined("SM_VERSION"))
			{
				include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/version.php");
			}
			$version = SM_VERSION;
		}
		else
		{
			$modulePath = getLocalPath("modules/".$moduleName."/install/version.php");
			if ($modulePath === false)
				return false;

			include($_SERVER["DOCUMENT_ROOT"].$modulePath);
			$version = array_key_exists("VERSION", $arModuleVersion)? $arModuleVersion["VERSION"]: false;
		}

		return $version;
	}

	public static function isModuleInstalled($moduleName)
	{
		$arInstalledModules = self::getInstalledModules();
		return isset($arInstalledModules[$moduleName]);
	}

	public static function delete($moduleName)
	{
		$con = Application::getConnection();
		$con->queryExecute("DELETE FROM b_module WHERE ID = '".$con->getSqlHelper()->forSql($moduleName)."'");

		self::$installedModules = array();
		Loader::clearModuleCache($moduleName);

		$cacheManager = Application::getInstance()->getManagedCache();
		$cacheManager->clean("b_module");
	}

	public static function add($moduleName)
	{
		$con = Application::getConnection();
		$con->queryExecute("INSERT INTO b_module(ID) VALUES('".$con->getSqlHelper()->forSql($moduleName)."')");

		self::$installedModules = array();
		Loader::clearModuleCache($moduleName);

		$cacheManager = Application::getInstance()->getManagedCache();
		$cacheManager->clean("b_module");
	}

	public static function registerModule($moduleName)
	{
		static::add($moduleName);

		$event = new Event("main", "OnAfterRegisterModule", array($moduleName));
		$event->send();
	}

	public static function unRegisterModule($moduleName)
	{
		$con = Application::getInstance()->getConnection();

		$con->queryExecute("DELETE FROM b_agent WHERE MODULE_ID='".$con->getSqlHelper()->forSql($moduleName)."'");
		\CMain::DelGroupRight($moduleName);

		static::delete($moduleName);

		$event = new Event("main", "OnAfterUnRegisterModule", array($moduleName));
		$event->send();
	}
}
