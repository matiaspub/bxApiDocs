<?
IncludeModuleLangFile(__FILE__);
class CEShop
{
	public static function ShowPanel()
	{
		if ($GLOBALS["USER"]->IsAdmin() && COption::GetOptionString("main", "wizard_solution", "", SITE_ID) == "eshop")
		{
			$GLOBALS["APPLICATION"]->SetAdditionalCSS("/bitrix/wizards/bitrix/eshop/css/panel.css"); 

			$arMenu = Array(
				Array(		
					"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape("/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&wizardSiteID=".SITE_ID."&wizardName=bitrix:eshop&".bitrix_sessid_get())."')",
					"ICON" => "bx-popup-item-wizard-icon",
					"TITLE" => GetMessage("STOM_BUTTON_TITLE_W1"),
					"TEXT" => GetMessage("STOM_BUTTON_NAME_W1"),
				),
				Array(			
					"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape("/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&site_id=".SITE_ID."&wizardName=bitrix:eshop.mobile&".bitrix_sessid_get())."')",
					"ICON" => "bx-popup-item-wizard-icon",
					"TITLE" => GetMessage("STOM_BUTTON_TITLE_W5"),
					"TEXT" => GetMessage("STOM_BUTTON_NAME_W5"),
				),
			/*	Array(			
					"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape("/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&site_id=".SITE_ID."&wizardName=bitrix:store.catalog&".bitrix_sessid_get())."')",
					"ICON" => "bx-popup-item-wizard-icon",
					"TITLE" => GetMessage("STOM_BUTTON_TITLE_W2"),
					"TEXT" => GetMessage("STOM_BUTTON_NAME_W2"),
				),
				Array(			
					"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape("/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&site_id=".SITE_ID."&wizardName=bitrix:store.catalog&editCBlock=Y&".bitrix_sessid_get())."')",
					"ICON" => "bx-popup-item-wizard-icon",
					"TITLE" => GetMessage("STOM_BUTTON_TITLE_W4"),
					"TEXT" => GetMessage("STOM_BUTTON_NAME_W4"),
				),    */
			);
			/*if(COption::GetOptionString("bitrix.eshop", "demo_deleted", "", SITE_ID) != 'Y')
			{
				CModule::IncludeModule("iblock");
				$dbr = CIBlock::GetList(Array(), Array("XML_ID"=>"furniture_".SITE_ID));
 				if($arR = $dbr->Fetch())
				{
					if($_REQUEST['delete_demo']=='eshop' && check_bitrix_sessid())
					{
				    		if(CIBlock::Delete($arR['ID']))
				    		{
				    			DeleteDirFilesEx(SITE_DIR.'catalog/furniture');
								COption::GetOptionString("bitrix.eshop", "demo_deleted", "Y", SITE_ID);
				    		}
							unset($_SESSION["SALE_BASKET_NUM_PRODUCTS"][SITE_ID]);
					}
					else
				 		$arMenu[] = Array(		
							"ACTION" => "if(confirm('".GetMessage("STOM_BUTTON_CONFIRM_W2")."')) jsUtils.Redirect([], '".CUtil::JSEscape(SITE_DIR)."catalog/?delete_demo=eshop&".bitrix_sessid_get()."');",
							"ICON" => "bx-popup-item-delete-icon",
							"TITLE" => GetMessage("STOM_BUTTON_TITLE_W3"),
							"TEXT" => GetMessage("STOM_BUTTON_NAME_W3"),
						);
				}
				else
					COption::SetOptionString("bitrix.eshop", "demo_deleted", "Y", "", SITE_ID);

			}  */

			$GLOBALS["APPLICATION"]->AddPanelButton(array(
				"HREF" => "/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&wizardName=bitrix:eshop&wizardSiteID=".SITE_ID."&".bitrix_sessid_get(),
				"ID" => "eshop_wizard",
				"ICON" => "bx-panel-site-wizard-icon",
				"MAIN_SORT" => 2500,
				"TYPE" => "BIG",
				"SORT" => 10,	
				"ALT" => GetMessage("SCOM_BUTTON_DESCRIPTION"),
				"TEXT" => GetMessage("SCOM_BUTTON_NAME"),
				"MENU" => $arMenu,
			));
		}
	}
}
?>