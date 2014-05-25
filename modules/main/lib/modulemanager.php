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
