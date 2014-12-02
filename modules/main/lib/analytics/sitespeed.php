<?php
namespace Bitrix\Main\Analytics;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

class SiteSpeed
{
	public static function onBuildGlobalMenu(&$arGlobalMenu, &$arModuleMenu)
	{
		$siteSpeedItem = array(
			"text" => Loc::getMessage("MAIN_ANALYTICS_MENU_SITE_SPEED"),
			"url" => "site_speed.php?lang=".LANGUAGE_ID,
			"more_url" => array("site_speed.php"),
			"title" => Loc::getMessage("MAIN_ANALYTICS_MENU_SITE_SPEED_ALT"),
		);

		$found = false;
		foreach ($arModuleMenu as &$arMenuItem)
		{
			if (!isset($arMenuItem["items_id"]) || $arMenuItem["items_id"] !== "menu_perfmon")
			{
				continue;
			}

			if (isset($arMenuItem["items"]) && is_array($arMenuItem["items"]))
			{
				array_unshift($arMenuItem["items"], $siteSpeedItem);
			}
			else
			{
				$arMenuItem["items"] = array($siteSpeedItem);
			}

			$found = true;
			break;
		}

		if (!$found)
		{
			$arModuleMenu[] = array(
				"parent_menu" => "global_menu_settings",
				"section" => "perfmon",
				"sort" => 1850,
				"text" => Loc::getMessage("MAIN_ANALYTICS_MENU_PERFORMANCE"),
				"title" => Loc::getMessage("MAIN_ANALYTICS_MENU_PERFORMANCE"),
				"icon" => "perfmon_menu_icon",
				"page_icon" => "perfmon_page_icon",
				"items_id" => "menu_perfmon",
				"items" => array($siteSpeedItem),
			);
		}
	}

	public static function isLicenseAccepted()
	{
		return Option::get("main", "~new_license14_9_sign", "") === "Y";
	}

	public static function canGatherStat()
	{
		return Option::get("main", "gather_user_stat", "Y") === "Y" && defined("LICENSE_KEY") && LICENSE_KEY !== "DEMO";
	}

	public static function isOn()
	{
		return self::isLicenseAccepted() && self::canGatherStat();
	}
} 