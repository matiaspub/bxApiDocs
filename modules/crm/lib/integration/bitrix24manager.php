<?php
namespace Bitrix\Crm\Integration;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class Bitrix24Manager
{
	private static $IS_LICENSE_PAID = null;
	private static $IS_PAID_ACCOUNT = null;

	public static function isEnabled()
	{
		return ModuleManager::isModuleInstalled('bitrix24');
	}
	public static function isPaidAccount()
	{
		if(self::$IS_PAID_ACCOUNT !== null)
		{
			return self::$IS_PAID_ACCOUNT;
		}

		if(\COption::GetOptionString('voximplant', 'account_payed', 'N') === 'Y')
		{
			return (self::$IS_PAID_ACCOUNT = true);
		}

		return (self::$IS_PAID_ACCOUNT = self::isPaidLicense());
	}
	public static function isPaidLicense()
	{
		if(self::$IS_LICENSE_PAID !== null)
		{
			return self::$IS_LICENSE_PAID;
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
			&& method_exists('CBitrix24', 'IsLicensePaid'))
		{
			return (self::$IS_LICENSE_PAID = false);
		}


		return (self::$IS_LICENSE_PAID = \CBitrix24::IsLicensePaid());
	}
}