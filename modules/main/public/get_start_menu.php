<?
// define("NO_KEEP_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");

if(!check_bitrix_sessid())
	die();

IncludeModuleLangFile(__FILE__);

$aUserOpt = CUserOptions::GetOption("global", "settings", array());
$bSkipRecent = isset($_REQUEST['skip_recent']);

function __GetSubmenu($menu)
{
	global $aUserOpt, $bSkipRecent;

	$aPopup = array();
	if (is_array($menu))
	{
		foreach($menu as $item)
		{
			if(!is_array($item))
				continue;

			$aItem = array(
				"TEXT"=>$item["text"],
				"TITLE"=>($aUserOpt['start_menu_title'] <> 'N'? $item["title"] : ''),
				"ICON"=>$item["icon"],
			);
			if($item["url"] <> "")
			{
				$link = htmlspecialcharsback($item["url"]);
				if(strpos($link, "/bitrix/admin/") !== 0)
					$link = "/bitrix/admin/".$link;

				if ($_REQUEST['back_url_pub'])
					$link .= (strpos($link, '?') > 0 ? '&' : '?')."back_url_pub=".urlencode($_REQUEST["back_url_pub"]);

				$aItem['LINK'] = $link;

				if (!$bSkipRecent)
					$aItem['ONCLICK'] = 'BX.admin.startMenuRecent('.CUtil::PhpToJsObject($aItem).')';
			}

			if(is_array($item["items"]) && count($item["items"])>0)
			{
				$aItem["MENU"] = __GetSubmenu($item["items"]);
				if($item["url"] <> "" && $aUserOpt['start_menu_title'] <> 'N')
					$aItem["TITLE"] .= ' '.GetMessage("get_start_menu_dbl");
			}
			elseif($item["dynamic"] == true)
			{
				$aItem["MENU_URL"] = '/bitrix/admin/get_start_menu.php?mode=dynamic&lang='.LANGUAGE_ID.'&admin_mnu_module_id='.urlencode($item['module_id']).'&admin_mnu_menu_id='.urlencode($item['items_id']).($bSkipRecent?'&skip_recent=Y':'').($_REQUEST["back_url_pub"]<>''? '&back_url_pub='.urlencode($_REQUEST["back_url_pub"]):'').'&'.bitrix_sessid_get();
				$aItem['MENU_PRELOAD'] = false;

				if($item["url"] <> "" && $aUserOpt['start_menu_title'] <> 'N')
					$aItem["TITLE"] .= ' '.GetMessage("get_start_menu_dbl");
			}

			$aPopup[] = $aItem;
		}
	}

	return $aPopup;
}

function __FindSubmenu($menu, $items_id)
{
	foreach($menu as $item)
	{
		if(is_array($item["items"]) && count($item["items"])>0)
		{
			if($item["items_id"] == $items_id)
				return $item["items"];
			elseif(($m = __FindSubmenu($item["items"], $items_id)) !== false)
				return $m;
		}
	}
	return false;
}

if($_REQUEST["mode"] == "save_recent")
{
	if($_REQUEST["url"] <> "")
	{
		$nLinks = 5;
		if($aUserOpt["start_menu_links"] <> "")
			$nLinks = intval($aUserOpt["start_menu_links"]);

		$aRecent = CUserOptions::GetOption("start_menu", "recent", array());

		CUtil::decodeURIComponent($_REQUEST["text"]);
		CUtil::decodeURIComponent($_REQUEST["title"]);
		$aLink = array("url"=>$_REQUEST["url"], "text"=>$_REQUEST["text"], "title"=>$_REQUEST["title"], "icon"=>$_REQUEST["icon"]);

		if(($pos = array_search($aLink, $aRecent)) !== false)
			unset($aRecent[$pos]);
		array_unshift($aRecent, $aLink);
		$aRecent = array_slice($aRecent, 0, $nLinks);

		CUserOptions::SetOption("start_menu", "recent", $aRecent);
	}
	echo "OK";
}
elseif($_REQUEST["mode"] == "dynamic")
{
	//admin menu - dynamic sections
	$adminMenu->AddOpenedSections($_REQUEST["admin_mnu_menu_id"]);
	$adminMenu->Init(array($_REQUEST["admin_mnu_module_id"]));

	$aSubmenu = __FindSubmenu($adminMenu->aGlobalMenu, $_REQUEST["admin_mnu_menu_id"]);

	if(!is_array($aSubmenu) || empty($aSubmenu))
		$aSubmenu = array(array("text"=>GetMessage("get_start_menu_no_data")));

	//generate JavaScript array for popup menu
	echo CAdminPopup::PhpToJavaScript(__GetSubmenu($aSubmenu));
}
elseif($_REQUEST["mode"] == "chain")
{
	$adminMenu->AddOpenedSections($_REQUEST["admin_mnu_menu_id"]);
	$adminPage->Init();
	$adminMenu->Init($adminPage->aModules);

	$aSubmenu = __FindSubmenu($adminMenu->aGlobalMenu, $_REQUEST["admin_mnu_menu_id"]);

	if(!is_array($aSubmenu) || empty($aSubmenu))
		$aSubmenu = array(array("text"=>GetMessage("get_start_menu_no_data")));

	$bSkipRecent = true;

	//generate JavaScript array for popup menu
	echo CAdminPopup::PhpToJavaScript(__GetSubmenu($aSubmenu));
}
else
{
	//admin menu - all static sections
	$adminPage->Init();
	$adminMenu->Init($adminPage->aModules);

	$aPopup = array();
	foreach($adminMenu->aGlobalMenu as $menu)
	{
		$aPopup[] = array(
			"TEXT"=>$menu["text"],
			"TITLE"=>($aUserOpt['start_menu_title'] <> 'N'? $menu["title"].' '.GetMessage("get_start_menu_dbl"):''),
			"GLOBAL_ICON"=>'adm-menu-'.$menu["menu_id"],
			"LINK"=>$menu['url'] ? '/bitrix/admin/'.$menu['url'] : '',
			"MENU"=>__GetSubmenu($menu["items"])
		);
	}

	//favorites
	if($USER->CanDoOperation('edit_own_profile') || $USER->CanDoOperation('edit_other_settings') || $USER->CanDoOperation('view_other_settings'))
	{
		$aFav = array(
			array(
				"TEXT"=>GetMessage("get_start_menu_add_fav"),
				"TITLE"=>($aUserOpt['start_menu_title'] <> 'N'? GetMessage("get_start_menu_add_fav_title"):''),
				"ACTION"=>"BX.admin.startMenuFavAdd(".($_REQUEST["back_url_pub"]<>''? "'".CUtil::JSEscape($_REQUEST["back_url_pub"])."'":"").");"
			),
			array(
				"TEXT"=>GetMessage("get_start_menu_org_fav"),
				"TITLE"=>($aUserOpt['start_menu_title'] <> 'N'? GetMessage("get_start_menu_org_fav_title"):''),
				"LINK"=> BX_ROOT."/admin/favorite_list.php?lang=".LANGUAGE_ID."&back_url_pub=".urlencode($_REQUEST["back_url_pub"])
			),
		);

		$aFav[1]["ONCLICK"] = 'BX.admin.startMenuRecent('.CUtil::PhpToJsObject($aFav[1]).')';

		$db_fav = CFavorites::GetList(array("COMMON"=>"ASC", "SORT"=>"ASC", "NAME"=>"ASC"), array("MENU_FOR_USER"=>$USER->GetID(), "LANGUAGE_ID"=>LANGUAGE_ID));
		$prevCommon = "";
		while($db_fav_arr = $db_fav->Fetch())
		{
			if($db_fav_arr["COMMON"] == "Y" && $db_fav_arr["MODULE_ID"] <> "" && $APPLICATION->GetGroupRight($db_fav_arr["MODULE_ID"]) < "R")
				continue;
			if($db_fav_arr["COMMON"] <> $prevCommon)
			{
				$aFav[] = array("SEPARATOR"=>true);
				$prevCommon = $db_fav_arr["COMMON"];
			}

			$sTitle = '';
			if($aUserOpt['start_menu_title'] <> 'N')
			{
				$sTitle = $db_fav_arr["COMMENTS"];
				$sTitle = (strlen($sTitle)>100? substr($sTitle, 0, 100)."..." : $sTitle);
				$sTitle = str_replace("\r\n", "\n", $sTitle);
				$sTitle = str_replace("\r", "\n", $sTitle);
				$sTitle = str_replace("\n", " ", $sTitle);
			}

			$aItem = array(
				"TEXT"=>htmlspecialcharsbx($db_fav_arr["NAME"]),
				"TITLE"=>htmlspecialcharsbx($sTitle),
			);

			if ($db_fav_arr["URL"])
			{
				$aItem["LINK"] = $db_fav_arr["URL"];

				if (!preg_match('/^(http:|https:|\/)/i', $aItem["LINK"]))
				{
					$aItem["LINK"] = '/bitrix/admin/'.$aItem["LINK"];
				}

				$aItem["ONCLICK"] = 'BX.admin.startMenuRecent('.CUtil::PhpToJsObject($aItem).')';
			}

			if ($db_fav_arr['MENU_ID'])
			{
				$aSubmenu = __FindSubmenu($adminMenu->aGlobalMenu, $db_fav_arr['MENU_ID']);

				if(!is_array($aSubmenu) || empty($aSubmenu))
				{
					$aItem["MENU_URL"] = '/bitrix/admin/get_start_menu.php?mode=dynamic&lang='.LANGUAGE_ID.'&admin_mnu_module_id='.urlencode($db_fav_arr['MODULE_ID']).'&admin_mnu_menu_id='.urlencode($db_fav_arr['MENU_ID']).($_REQUEST["back_url_pub"]<>''? '&back_url_pub='.urlencode($_REQUEST["back_url_pub"]):'').'&'.bitrix_sessid_get();
					$aItem['MENU_PRELOAD'] = false;
				}

				$aItem["MENU"] = __GetSubmenu($aSubmenu);
			}

			$aFav[] = $aItem;
		}

		$aPopup[] = array("SEPARATOR"=>true);
		$aPopup[] = array(
			"TEXT"=>GetMessage("get_start_menu_fav"),
			"TITLE"=>($aUserOpt['start_menu_title'] <> 'N'? GetMessage("get_start_menu_fav_title"):''),
			"GLOBAL_ICON" => 'adm-menu-favorites',
			"MENU"=>$aFav,
		);
	}

	//recent urls
	if (!$bSkipRecent)
	{
		$aRecent = CUserOptions::GetOption("start_menu", "recent", array());
		if(!empty($aRecent))
		{
			$aPopup[] = array("SEPARATOR"=>true);

			$nLinks = 5;
			if($aUserOpt["start_menu_links"] <> "")
				$nLinks = intval($aUserOpt["start_menu_links"]);

			$i = 0;
			foreach($aRecent as $recent)
			{
				$i++;
				if($i > $nLinks)
					break;

				$aItem = array(
					"TEXT"=>htmlspecialcharsbx($recent["text"]),
					"TITLE"=>($aUserOpt['start_menu_title'] <> 'N'? htmlspecialcharsbx($recent["title"]):''),
					"GLOBAL_ICON"=>htmlspecialcharsbx($recent["icon"]),
					"LINK"=>$recent["url"],
				);

				$aItem["ONCLICK"] = 'BX.admin.startMenuRecent('.CUtil::PhpToJsObject($aItem).')';

				$aPopup[] = $aItem;
			}
		}
	}

	if(empty($aPopup))
		$aPopup[] = array("TEXT"=>GetMessage("get_start_menu_no_data"));

	//generate JavaScript array for popup menu
	echo CAdminPopup::PhpToJavaScript($aPopup);

} //$_REQUEST["mode"]

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
?>