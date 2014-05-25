<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
IncludeModuleLangFile(__FILE__);

if($_GET["back_url_pub"] <> "" && !is_array($_GET["back_url_pub"]) && strpos($_GET["back_url_pub"], "/") === 0)
	$_SESSION["BACK_URL_PUB"] = $_GET["back_url_pub"];

$params = DeleteParam(array("logout", "back_url_pub"));

$arPanelButtons = array();

function _showTopPanelButtonsSection($arPanelButtons, $hkInstance, $section = null)
{
	global $USER;

	foreach ($arPanelButtons as $item):
		if($item["SEPARATOR"] == true)
			continue;
		if ($section == null && isset($item['SECTION']))
			continue;
		if ($section != null && $item['SECTION'] != $section)
			continue;

		$id = isset($item['ID']) ? $item['ID'] : 'bx_top_panel_button_'.RandString();
		$bHasMenu = (is_array($item["MENU"]) && !empty($item["MENU"]));

		if($USER->IsAuthorized())
			echo $hkInstance->PrintTPButton($item);

		if ($item['LINK']):

?><a id="<?=htmlspecialcharsEx($id)?>" href="<?=htmlspecialcharsEx($item['LINK'])?>" class="<?=$item['ICON']?>"<?=isset($item["TITLE"])?' title="'.htmlspecialcharsEx($item["TITLE"]).'"':''?><?=isset($item["TARGET"])?' target="'.htmlspecialcharsEx($item["TARGET"]).'"':''?> hidefocus="true" onfocus="this.blur();"><?=htmlspecialcharsbx($item["TEXT"])?></a><?

		else:

?><span id="<?=htmlspecialcharsEx($id)?>" class="<?=$item['ICON']?>"<?=isset($item["TITLE"])?'title="'.htmlspecialcharsEx($item["TITLE"]).'"':''?>><?=htmlspecialcharsbx($item["TEXT"])?></span><?

		endif;

		if ($bHasMenu || $item['TOOLTIP'] && $item['TOOLTIP_ID']):
?><script type="text/javascript"><?
			if ($item['TOOLTIP']):
				if ($item['TOOLTIP_ID']):

?>
BX.ready(function() {BX.hint(BX('<?=CUtil::JSEscape($id)?>'), '<?=CUtil::JSEscape($item["TITLE"])?>', '<?=CUtil::JSEscape($item['TOOLTIP'])?>', '<?=CUtil::JSEscape($item['TOOLTIP_ID'])?>')});
<?

				endif;
			endif;
			if ($bHasMenu):

?>
BX.adminPanel.registerButton('<?=CUtil::JSEscape($id)?>', {MENU: <?=CUtil::PhpToJsObject($item['MENU'])?>});
<?

			endif;

?></script><?

		endif;
	endforeach;
}

if($USER->IsAuthorized())
{
	/*
	//Favorites
	if(is_callable(array($USER,'CanDoOperation')) && ($USER->CanDoOperation('edit_own_profile') || $USER->CanDoOperation('edit_other_settings') || $USER->CanDoOperation('view_other_settings')))
	{
		$aFav = array();
		if(is_callable(array('CUtil', 'addslashes')))
		{
			$aFav = array(
				array(
					"HK_ID"=>"top_panel_add_fav",
					"TEXT"=>GetMessage("top_panel_add_fav"),
					"TITLE"=>GetMessage("MAIN_ADD_PAGE_TO_FAVORITES"),
//					"ACTION"=>"jsUtils.Redirect([], '".CUtil::addslashes(BX_ROOT."/admin/favorite_act.php?lang=".LANG."&act=add&name=".urlencode($APPLICATION->GetTitle()))."&".bitrix_sessid_get()."&addurl='+encodeURIComponent(document.getElementById('navchain-link').getAttribute('href', 2)));"
					"ACTION"=>"BXFavoriteAdd('".$APPLICATION->GetTitle()."',document.getElementById('navchain-link').getAttribute('href', 2));"
				),
				array(
					"TEXT"=>"TEST-GETLIST-FUNC",
					"TITLE"=>"TEST-GETLIST-FUNC",
					"ACTION"=>"BXFavoriteGetList();"
				),

				array(
					"HK_ID"=>"top_panel_org_fav",
					"TEXT"=>GetMessage("top_panel_org_fav"),
					"TITLE"=>GetMessage("top_panel_org_fav_title"),
					"ACTION"=>"jsUtils.Redirect([], '".CUtil::addslashes(BX_ROOT."/admin/favorite_list.php?lang=".LANG)."');"
				),
			);
			if(is_callable(array('CFavorites','Delete')))
			{
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

					$sTitle = $db_fav_arr["COMMENTS"];
					$sTitle = (strlen($sTitle)>100? substr($sTitle, 0, 100)."..." : $sTitle);
					$sTitle = str_replace("\r\n", "\n", $sTitle);
					$sTitle = str_replace("\r", "\n", $sTitle);
					$sTitle = str_replace("\n", " ", $sTitle);

					$aFav[] = array(
						"TEXT"=>htmlspecialcharsbx($db_fav_arr["NAME"]),
						"TITLE"=>htmlspecialcharsbx($sTitle),
						"ICON"=>"favorites",
						"HK_ID"=>"FAV-".$db_fav_arr["CODE_ID"],
						"ACTION"=>"jsUtils.Redirect([], '".CUtil::addslashes($db_fav_arr["URL"])."');",
					);
				}
			}
		}
		$arPanelButtons[] = array(
			"TEXT"=>GetMessage("top_panel_favorites"),
			"TITLE"=>GetMessage("top_panel_fav"),
			"ICON"=>"bx-panel-admin-button-favorites-icon",
			"HK_ID"=>"top_panel_fav",
			"MENU"=>$aFav,
		);
	}
	*/

	$bCanViewSettings = (is_callable(array($USER,'CanDoOperation')) && ($USER->CanDoOperation('view_other_settings') || $USER->CanDoOperation('edit_other_settings')));
	if($bCanViewSettings)
	{
		//Settings
		$settingsUrl = BX_ROOT."/admin/settings.php?lang=".LANG."&mid=".(defined("ADMIN_MODULE_NAME")? ADMIN_MODULE_NAME:"main").($APPLICATION->GetCurPage() <> BX_ROOT."/admin/settings.php"? "&back_url_settings=".urlencode($_SERVER["REQUEST_URI"]):"");
		$arPanelButtons[] = array(
			"TEXT"=>GetMessage("top_panel_settings"),
			"TITLE"=>GetMessage("button_settings"),
			"LINK"=>$settingsUrl,
			"ICON"=>"adm-header-setting-btn",
			"HK_ID"=>"top_panel_settings",
/*			"MENU"=> array(
				array(
					"HK_ID"=>"top_panel_module_settings",
					"TEXT"=>GetMessage("top_panel_module_settings"),
					"TITLE"=>GetMessage("button_settings"),
					"LINK"=>$settingsUrl,
					"ICON"=>"bx-panel-admin-button-settings-icon",
					"DEFAULT"=>true,
				),
				array(
					"HK_ID"=>"top_panel_interface_settings",
					"TEXT" => GetMessage("top_panel_interface_settings"),
					"LINK" => "/bitrix/admin/user_settings.php?lang=".LANGUAGE_ID,
					"TITLE" => GetMessage("MAIN_MENU_INTERFACE_TITLE"),
				),
			),*/
		);
	}

	//Help
	$module = (defined("ADMIN_MODULE_NAME")? ADMIN_MODULE_NAME: "main");
	$page = (defined("HELP_FILE") && strpos(HELP_FILE, '/') === false? HELP_FILE : basename($APPLICATION->GetCurPage()));

	$aActiveSection = $adminMenu->ActiveSection();
	$section = $aActiveSection["help_section"]."/";
	if (defined("HELP_FILE") && strpos(HELP_FILE, $section) === 0)
		$section = "";

	if(LANGUAGE_ID == "ru")
		$help_link = "http://dev.1c-bitrix.ru/user_help/".$section.(defined("HELP_FILE") && strpos(HELP_FILE, '/') !== false?  HELP_FILE : $module."/".$page);
	else
		$help_link = "http://www.bitrixsoft.com/help/index.html?page=".urlencode("source/".$module."/help/en/".$page.".html");

	$arPanelButtons[] = array(
		"TEXT"=>GetMessage("top_panel_help"),
		"TITLE"=>GetMessage("MAIN_HELP"),
		"LINK"=>$help_link,
		"TARGET"=>"_blank",
		"ICON"=>"adm-header-help-btn",
		"HK_ID"=>"top_panel_help",
	);
}

$arLangs = CLanguage::GetLangSwitcherArray();

$arLangButton = array();
$arLangMenu = array();

foreach($arLangs as $adminLang)
{
	if ($adminLang['SELECTED'])
	{
		$arLangButton = array(
			"TEXT"=>ToUpper($adminLang["LID"]),
			"TITLE"=>GetMessage("top_panel_lang")." ".$adminLang["NAME"],
			"LINK"=>htmlspecialcharsback($adminLang["PATH"]),
			"SECTION" => 1,
			"ICON" => "adm-header-language",
		);
	}

	$arLangMenu[] = array(
		"TEXT" => '('.$adminLang["LID"].') '.$adminLang["NAME"],
		"TITLE"=> GetMessage("top_panel_lang")." ".$adminLang["NAME"],
		"LINK"=>htmlspecialcharsback($adminLang["PATH"]),
	);
}

if (count($arLangMenu) > 1)
{
	CJSCore::Init(array('admin_interface'));
	$arLangButton['MENU'] = $arLangMenu;
}

$arPanelButtons[] = $arLangButton;


$sPubUrl = ($_SESSION["BACK_URL_PUB"] <> ""?
	htmlspecialcharsbx($_SESSION["BACK_URL_PUB"]).(strpos($_SESSION["BACK_URL_PUB"], "?") !== false? "&amp;":"?") : '/?').
	'back_url_admin='.urlencode($APPLICATION->GetCurPage().($params<>""? "?".$params:""));

$aUserOptGlobal = CUserOptions::GetOption("global", "settings");

if($USER->IsAuthorized())
{
	$hkInstance = CHotKeys::getInstance();
	$Execs=$hkInstance->GetCodeByClassName("top_panel_menu",GetMessage("admin_panel_menu"));
	echo $hkInstance->PrintJSExecs($Execs);
	$Execs=$hkInstance->GetCodeByClassName("admin_panel_site",GetMessage("admin_panel_site"));
	echo $hkInstance->PrintJSExecs($Execs);
	$Execs=$hkInstance->GetCodeByClassName("admin_panel_admin",GetMessage("admin_panel_admin"));
	echo $hkInstance->PrintJSExecs($Execs);
}
?>
<div id="bx-panel" class="adm-header"><div class="adm-header-left"><a hidefocus="true" href="<?=$sPubUrl?>" class="adm-header-btn adm-header-btn-site" title="<?=GetMessage("adm_top_panel_view_title")?>"><?=GetMessage("admin_panel_site")?></a><a hidefocus="true" href="<?=BX_ROOT."/admin/index.php?lang=".LANGUAGE_ID?>" class="adm-header-btn adm-header-btn-admin"><?echo GetMessage("admin_panel_admin")?></a><?

$informerItemsCount = CAdminInformer::InsertMainItems();

if ($USER->IsAuthorized() && $informerItemsCount>0):

?><span class="adm-header-notif-block" id="adm-header-notif-block" onclick="BX.adminInformer.Toggle(this);" title="<?=GetMessage("admin_panel_notif_block_title")?>"><span class="adm-header-notif-icon"></span><span class="adm-header-notif-counter" id="adm-header-notif-counter"><?=CAdminInformer::$alertCounter?></span></span><?
endif;

_showTopPanelButtonsSection($arPanelButtons, $hkInstance)

?></div><div class="adm-header-right"><?
if($USER->IsAuthorized() && IsModuleInstalled("search")):

?><div class="adm-header-search-block" id="bx-search-box"><input class="adm-header-search" id="bx-search-input" onfocus="if (this.value=='<?=GetMessage("top_panel_search_def")?>') {this.value=''; BX.addClass(this.parentNode,'adm-header-search-block-active');}" value="<?=GetMessage("top_panel_search_def")?>" onblur="if (this.value==''){this.value='<?=GetMessage("top_panel_search_def")?>'; BX.removeClass(this.parentNode,'adm-header-search-block-active');}" type="text" autocomplete="off" /><a href="#" onclick="BX('bx-search-input').value=''; BX('bx-search-input').onblur();" class="adm-header-search-block-btn"></a></div><script type="text/javascript">
var jsControl = new JCAdminTitleSearch({
	'AJAX_PAGE' : '/bitrix/admin/get_search.php?lang=<?=LANGUAGE_ID?>',
	'CONTAINER_ID': 'bx-search-box',
	'INPUT_ID': 'bx-search-input',
	'MIN_QUERY_LEN': 1
});
</script><?

	$Execs = $hkInstance->GetCodeByClassName("bx-search-input", GetMessage("top_panel_search_def"));
	echo $hkInstance->PrintJSExecs($Execs);

endif;
?><div class="adm-header-right-block"><?

if ($USER->IsAuthorized()):
	if ($USER->CanDoOperation('view_own_profile') || $USER->CanDoOperation('edit_own_profile')):

		$userName = CUser::FormatName(
			CSite::GetNameFormat(false),
			array(
				"NAME" => $USER->GetFirstName(),
				"LAST_NAME" => $USER->GetLastName(),
				"SECOND_NAME" => $USER->GetSecondName(),
				"LOGIN" => $USER->GetLogin()
			),
			true, false
		);
?><a hidefocus="true" href="/bitrix/admin/user_edit.php?lang=<?=LANGUAGE_ID?>&amp;ID=<?=$USER->GetID()?>" class="adm-header-user-block" id="bx-panel-user"><?=htmlspecialcharsbx($userName);?></a><?

	else:

?><span class="adm-header-user-block" id="bx-panel-user"><?=htmlspecialcharsbx($USER->GetFullName()).' ('.htmlspecialcharsbx($USER->GetLogin());?></span><?

	endif;

?><a hidefocus="true" href="<?=htmlspecialcharsbx((defined('BX_ADMIN_SECTION_404') && BX_ADMIN_SECTION_404 == 'Y' ? '/bitrix/admin/' : $APPLICATION->GetCurPage())).'?logout=yes'.htmlspecialcharsbx(($s=DeleteParam(array("logout"))) == ""? "":"&".$s)?>" class="adm-header-exit" id="bx-panel-logout" title="<?=GetMessage('admin_panel_logout_title')?>"><?=GetMessage("admin_panel_logout")?></a><?

	$Execs = $hkInstance->GetCodeByClassName("bx-panel-logout",GetMessage('admin_panel_logout'));
	echo $hkInstance->PrintJSExecs($Execs);

endif;


_showTopPanelButtonsSection($arPanelButtons, $hkInstance, 1);

if ($USER->IsAuthorized()):
	if($hkInstance->IsActive()):

?><a hidefocus="true" id="bx-panel-hotkeys" href="javascript:void(0)" onclick="BXHotKeys.ShowSettings();" class="header-keyboard" title="<?=GetMessage('admin_panel_hotkeys_title')?>"></a><?

	endif;

	$aUserOpt = CUserOptions::GetOption("admin_panel", "settings");

?><a hidefocus="true" href="javascript:void(0)" id="bx-panel-pin" class="adm-header-pin" onclick="BX.adminPanel.Fix(this)" title="<?=GetMessage('top_panel_pin_'.($aUserOpt['fix'] == 'on' ? 'off' : 'on'))?>"></a><?

	$Execs = $hkInstance->GetCodeByClassName("bx-panel-pin",GetMessage('top_panel_pin'));
	echo $hkInstance->PrintJSExecs($Execs);

endif;
?></div></div><div class="adm-header-bottom"></div><?

if ($USER->IsAdmin())
	echo CAdminNotify::GetHtml();

?></div><?

echo $GLOBALS["adminPage"]->ShowSound();

return;























/*
	<div id="bx-panel-top"><?


		$userInfo = "";
		if(!defined("BX_AUTH_FORM") && $USER->IsAuthorized())
		{
			$maxQuota = COption::GetOptionInt("main", "disk_space", 0)*1024*1024;
			if ($maxQuota > 0)
			{
				$quota = new CDiskQuota();
				$free = $quota->GetDiskQuota();
				$free = round($free/$maxQuota*100);

				$userInfo .= '<span id="bx-panel-quota"><span id="bx-panel-quota-caption">'.GetMessage("admin_panel_free").' '.$free.'%</span><span id="bx-panel-quota-indicator"><span id="bx-panel-quota-slider" style="width:'.(100 - $free).'%;'.($free <= 10 ? ' background-color: #F55 !important;' : '').'"></span></span></span><span class="bx-panel-userinfo-separator bx-panel-expand-mode-only"></span>';
			}
		}
		if($USER->IsAuthorized())
		{

			$bCanProfile = $USER->CanDoOperation('view_own_profile') || $USER->CanDoOperation('edit_own_profile');
			if ($bCanProfile)
				$userInfo .= '<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='.$USER->GetID().'" id="bx-panel-user">'.htmlspecialcharsbx($USER->GetFullName()).' ('.htmlspecialcharsbx($USER->GetLogin()).')</a><span class="bx-panel-userinfo-separator"></span>';
			else
				$userInfo .= '<a id="bx-panel-user">'.htmlspecialcharsbx($USER->GetFullName()).' ('.htmlspecialcharsbx($USER->GetLogin()).')</a><span class="bx-panel-userinfo-separator"></span>';

			$userInfo .= '<a href="'.htmlspecialcharsbx($APPLICATION->GetCurPage()).'?logout=yes'.htmlspecialcharsbx(($s=DeleteParam(array("logout"))) == ""? "":"&".$s).'" id="bx-panel-logout" title="'.$hkInstance->GetTitle("bx-panel-logout").'">'.GetMessage("admin_panel_logout").'</a>';
			$Execs	=	$hkInstance->GetCodeByClassName("bx-panel-logout",GetMessage('admin_panel_logout'));

			$userInfo .= '<span class="bx-panel-userinfo-separator"></span>';

			if($hkInstance->IsActive())
			{
				$userInfo .= '<a id="bx-panel-hotkeys" href="javascript:void(0)" Onclick="BXHotKeys.ShowSettings();"></a>';

				$userInfo .= '<span class="bx-panel-userinfo-separator"></span>';
			}

			$userInfo .= '<a href="javascript:void(0)" id="bx-panel-pin"'.($aUserOpt['fix'] == 'on' ? ' class="bx-panel-pin-fixed"' : '').'></a>';

			$userInfo .= '<span class="bx-panel-userinfo-separator"></span>';

		}
		*/


		?>

	<div id="bx-panel-userinfo"><?=$userInfo?></div>
	</div>
		<?
		if($USER->IsAuthorized())
			$hkInstance->PrintJSExecs($Execs);
		?>

	<div id="bx-panel-admin-toolbar"<?if($aUserOptGlobal["panel_color"]!=""):?> style="background-color: <?=htmlspecialcharsbx($aUserOptGlobal["panel_color"])?> !important"<?endif?>>
	<div id="bx-panel-admin-toolbar-inner">
		<?
		foreach($arPanelButtons as $item)
		{
			if($USER->IsAuthorized())
				echo $hkInstance->PrintTPButton($item);

			if($item["SEPARATOR"] == true)
			{
				?><span class="bx-panel-admin-button-separator"></span><?
			}
			else
			{
				$id = isset($item["ICON"]) ? $item["ICON"]."-id" : "bx-panel-admin-button-".ToLower(randString(5))."-id";
				$bHasMenu = (is_array($item["MENU"]) && !empty($item["MENU"]));
				$button_text = (!empty($item["ICON"])? '<span class="bx-panel-admin-button-icon '.$item["ICON"].'"></span>':'').'<span class="bx-admin-opacity-text">'.$item["TEXT"].'</span>';

				$result = '<span class="bx-panel-admin-button'.($item["SELECTED"] == true ? " bx-panel-admin-button-selected": "" ).'"><span class="bx-panel-admin-button-inner">';
				if($item["LINK"] <> '')
				{
					$result .= '<a href="'.$item["LINK"].'" id="'.$id.'" title="'.$item["TITLE"].'"><span class="bx-panel-admin-button-lb"></span><span class="bx-panel-admin-button-text">'.$button_text.'</span><span class="bx-panel-admin-button-rb"></span></a>';
					$result .= '<script type="text/javascript">BX.admin.panel.RegisterButton({ID: \''.$id.'\', TYPE: \'SMALL\', HOVER_CSS: \'bx-panel-admin-button-hover\'})</script>';
					if($bHasMenu)
					{
						$result .= '<a href="javascript:void(0)" id="'.$id.'_menu"><span class="bx-panel-admin-button-text" style="padding:0px !important"><span class="bx-panel-admin-button-arrow"></span></span><span class="bx-panel-admin-button-rb"></span></a>';
						$result .= '<script type="text/javascript">BX.admin.panel.RegisterButton({ID: \''.$id.'_menu\', TYPE: \'SMALL\', MENU: '.CUtil::PhpToJsObject($item['MENU']).', HOVER_CSS: \'bx-panel-admin-button-hover\'})</script>';
					}
				}
				elseif($bHasMenu)
				{
					$result .= '<a href="javascript:void(0)" id="'.$id.'" title="'.$item['TITLE'].'"><span class="bx-panel-admin-button-lb"></span><span class="bx-panel-admin-button-text">'.$button_text.'<span class="bx-panel-admin-button-arrow"></span></span><span class="bx-panel-admin-button-rb"></span></a>';
					$result .= '<script type="text/javascript">BX.admin.panel.RegisterButton({ID: \''.$id.'\', TYPE: \'SMALL\', MENU: '.CUtil::PhpToJsObject($item['MENU']).', HOVER_CSS: \'bx-panel-admin-button-hover\'})</script>';
				}
				$result .= '</span></span>';
				echo $result;

				if ($item['TOOLTIP'])
				{
					if ($item['TOOLTIP_ID'])
					{
?>
<script type="text/javascript">BX.ready(function() {BX.hint(BX('<?=CUtil::JSEscape($id)?>'), '<?=CUtil::JSEscape($item["TITLE"])?>', '<?=CUtil::JSEscape($item['TOOLTIP'])?>', '<?=CUtil::JSEscape($item['TOOLTIP_ID'])?>')});</script>
<?
					}
				}
			}
		}


		?>
		</div>
	</div>
	<?
		if ($USER->IsAdmin())
			echo CAdminNotify::GetHtml();
	?>
</div>

<script type="text/javascript">BX.admin.panel.state = {fixed: <?=($aUserOpt["fix"] == "on" ? "true" : "false")?>}</script>

<?
if($USER->IsAuthorized())
{
	//start menu preload
	if($aUserOptGlobal["start_menu_preload"] == 'Y')
		echo '<script type="text/javascript">jsUtils.addEvent(window, "load", function(){jsStartMenu.PreloadMenu();});</script>';
}

echo $GLOBALS["adminPage"]->ShowSound();

?>
