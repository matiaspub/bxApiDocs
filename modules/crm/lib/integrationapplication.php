<?php

namespace Bitrix\Crm;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class IntegrationApplication extends \Bitrix\Main\Authentication\Application
{
	protected $validUrls = array(
		"/crm/1c_exchange.php",
	);

	public static function OnApplicationsBuildList()
	{
		$result = null;
		if ('ru' === LANGUAGE_ID)
		{
			$result = array(
				"ID" => "ws_crmintegration",
				"NAME" => Loc::getMessage("WS_CRMINTEGRATION_APP_TITLE"),
				"DESCRIPTION" => Loc::getMessage("WS_CRMINTEGRATION_APP_DESC"),
				"SORT" => 150,
				"CLASS" => '\Bitrix\Crm\IntegrationApplication',
				"OPTIONS_CAPTION" => Loc::getMessage('WS_CRMINTEGRATION_APP_OPTIONS_CAPTION'),
				"OPTIONS" => array(
					Loc::getMessage("WS_CRMINTEGRATION_APP_OPTIONS_TITLE_1C"),
				)
			);
		}

		return $result;
	}
}
