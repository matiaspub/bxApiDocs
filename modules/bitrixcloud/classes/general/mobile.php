<?php
IncludeModuleLangFile(__FILE__);

class CBitrixCloudMobile
{
	/**
	 * Builds menu
	 *
	 * @return void
	 *
	 * RegisterModuleDependences(
	 * 								"mobileapp",
	 * 								"OnBeforeAdminMobileMenuBuild",
	 * 								"bitrixcloud",
	 * 								"CBitrixCloudMobile",
	 * 								"OnBeforeAdminMobileMenuBuild"
	 * 							);
	 */
	static public function OnBeforeAdminMobileMenuBuild()
	{
		$arMenu = array(
					array(
						"text" => GetMessage("BCL_MON_MOB_MENU_TITLE"),
						"type" => "section",
						"items" => array(
										array(
											"text" => GetMessage("BCL_MON_MOB_MENU_IPAGE"),
											"data-url" => "/bitrix/admin/mobile/bitrixcloud_monitoring_ipage.php",
											"data-pageid" => "info_page",
											)
										)
						)
					);

		$startSortMenuPosition = 300;

		foreach ($arMenu as $key => $item)
		{
			$item["sort"] = $key+$startSortMenuPosition;
			CAdminMobileMenu::addItem($item);
		}

		return true;
	}
}
