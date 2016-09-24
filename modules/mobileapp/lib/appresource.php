<?php

namespace Bitrix\MobileApp;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main;


class AppResource
{
	private static $map = array();

	private static function getMap()
	{
		if(empty(self::$map))
		{
			self::$map = include(Application::getDocumentRoot() . "/bitrix/modules/mobileapp/maps/resources.php");
		}
		return self::$map;
	}

	public static function get($platform_id)
	{
		$map = self::getMap();
		return $map[$platform_id];
	}

	public static function getIconsSet($platform_id)
	{
		$map = self::getMap();
		return $map[$platform_id]["icon"];
	}


	public static function getImagesSet($platform_id)
	{
		$map = self::getMap();
		return $map[$platform_id]["launch"];
	}

	public static function getAdditionalSet($platform_id)
	{
		$map = self::getMap();
		return $map[$platform_id]["additional"];
	}
}