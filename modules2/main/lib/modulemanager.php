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
				$con = Application::getDbConnection();
				$rs = $con->query("SELECT m.* FROM b_module m ORDER BY m.ID");
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
		return array_key_exists($moduleName, $arInstalledModules);
	}

	public static function remove($moduleName)
	{
		$con = Application::getDbConnection();
		$con->queryExecute("DELETE FROM b_module WHERE ID = '".$con->getSqlHelper()->forSql($moduleName)."'");

		self::$installedModules = array();

		$cacheManager = Application::getInstance()->getManagedCache();
		$cacheManager->clean("b_module");
	}

	public static function add($moduleName)
	{
		$con = Application::getDbConnection();
		$con->queryExecute("INSERT INTO b_module(ID) VALUES('".$con->getSqlHelper()->forSql($moduleName)."')");

		self::$installedModules = array();

		$cacheManager = Application::getInstance()->getManagedCache();
		$cacheManager->clean("b_module");
	}
}
