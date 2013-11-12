<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

IncludeModuleLangFile(__FILE__);

// define("ADMIN_THEMES_PATH", "/bitrix/themes");

class CAdminPage
{
	var $aModules = array();
	var $bInit = false;

	public static function CAdminPage()
	{
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminpage/init.php
	 * @author Bitrix
	 */
	public function Init()
	{
		if($this->bInit)
			return;
		$this->bInit = true;

		$module_list = CModule::GetList();
		while($module = $module_list->Fetch())
			$this->aModules[] = $module["ID"];
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminpage/showpopupcss.php
	 * @author Bitrix
	 */
	public function ShowPopupCSS()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$this->Init();

		$arCSS = array_merge(
			$this->GetModulesCSS($_REQUEST['from_module']),
			$APPLICATION->GetCSSArray()
		);

		$s = '<script type="text/javascript" bxrunfirst>'."\n";
		for ($i = 0, $cnt = count($arCSS); $i < $cnt; $i++)
		{
			$bExternalLink = (strncmp($arCSS[$i], 'http://', 7) == 0 || strncmp($arCSS[$i], 'https://', 8) == 0);
			if($bExternalLink || file_exists($_SERVER['DOCUMENT_ROOT'].$arCSS[$i]))
				$s .= 'top.BX.loadCSS(\''.CUtil::JSEscape($arCSS[$i]).'\');'."\n";
		}
		$s .= '</script>';
		return $s;
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminpage/showcss.php
	 * @author Bitrix
	 */
	public function ShowCSS()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$this->Init();

		$arCSS = array_merge(array(
				ADMIN_THEMES_PATH.'/'.ADMIN_THEME_ID.'/compatible.css',
				//ADMIN_THEMES_PATH.'/'.ADMIN_THEME_ID.'/adminstyles.css',
				'/bitrix/panel/main/adminstyles_fixed.css',
				'/bitrix/panel/main/admin.css',
			),
			$this->GetModulesCSS(),
			$APPLICATION->GetCSSArray()
		);

		$s = '';
		foreach($arCSS as $css)
		{
			$bExternalLink = (strncmp($css, 'http://', 7) == 0 || strncmp($css, 'https://', 8) == 0);
			if($bExternalLink || file_exists($_SERVER['DOCUMENT_ROOT'].$css))
				$s .= '<link rel="stylesheet" type="text/css" href="'.($bExternalLink? $css : CUtil::GetAdditionalFileURL($css, true)).'">'."\n";
		}
		return $s;
	}

	public function GetModulesCSS($module_id='')
	{
		global $CACHE_MANAGER;
		$rel_theme_path = ADMIN_THEMES_PATH."/".ADMIN_THEME_ID."/";
		$abs_theme_path = $_SERVER["DOCUMENT_ROOT"].$rel_theme_path;

		if($module_id <> '' && $this->aModules[$module_id] <> '')
		{
			if(file_exists($abs_theme_path.$module_id.".css"))
				return array($rel_theme_path.$module_id.'.css');
		}

		if($CACHE_MANAGER->Read(36000000, ADMIN_THEME_ID, "modules_css"))
			$time_cached = $CACHE_MANAGER->Get(ADMIN_THEME_ID);
		else
			$time_cached = '';

		//check modification time
		$time_fact = '';
		foreach($this->aModules as $module)
		{
			$fname = $abs_theme_path.$module.".css";
			if(file_exists($fname))
				$time_fact .= filemtime($fname);
		}

		$css_file = $abs_theme_path."modules.css";

		if($time_fact !== $time_cached)
		{
			//parse css files to create summary modules css
			$sCss = '';
			foreach($this->aModules as $module)
			{
				$fname = $abs_theme_path.$module.".css";
				if(file_exists($fname))
					$sCss .= file_get_contents($fname)."\n";
			}

			//create summary modules css
			file_put_contents($css_file, $sCss);

			if($time_cached !== '')
			{
				$CACHE_MANAGER->Clean(ADMIN_THEME_ID, "modules_css");
				$CACHE_MANAGER->Read(36000000, ADMIN_THEME_ID, "modules_css");
			}

			$CACHE_MANAGER->Set(ADMIN_THEME_ID, $time_fact);
		}

		if(file_exists($css_file))
			return array($rel_theme_path.'modules.css');
		else
			return array();
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminpage/showscript.php
	 * @author Bitrix
	 */
	public static function ShowScript()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		//PHP-depended variables
		$aUserOpt = CUserOptions::GetOption("global", "settings");
		$s = "
<script type=\"text/javascript\">
var phpVars = {
	'ADMIN_THEME_ID': '".CUtil::JSEscape(ADMIN_THEME_ID)."',
	'LANGUAGE_ID': '".CUtil::JSEscape(LANGUAGE_ID)."',
	'FORMAT_DATE': '".CUtil::JSEscape(FORMAT_DATE)."',
	'FORMAT_DATETIME': '".CUtil::JSEscape(FORMAT_DATETIME)."',
	'opt_context_ctrl': ".($aUserOpt["context_ctrl"] == "Y"? "true":"false").",
	'cookiePrefix': '".CUtil::JSEscape(COption::GetOptionString("main", "cookie_name", "BITRIX_SM"))."',
	'titlePrefix': '".CUtil::JSEscape(COption::GetOptionString("main", "site_name", $_SERVER["SERVER_NAME"]))." - ',
	'bitrix_sessid': '".bitrix_sessid()."',
	'messHideMenu': '".CUtil::JSEscape(GetMessage("admin_lib_hide_menu"))."',
	'messShowMenu': '".CUtil::JSEscape(GetMessage("admin_lib_show_menu"))."',
	'messHideButtons': '".CUtil::JSEscape(GetMessage("admin_lib_less_buttons"))."',
	'messShowButtons': '".CUtil::JSEscape(GetMessage("admin_lib_more_buttons"))."',
	'messFilterInactive': '".CUtil::JSEscape(GetMessage("admin_lib_filter_clear"))."',
	'messFilterActive': '".CUtil::JSEscape(GetMessage("admin_lib_filter_set"))."',
	'messFilterLess': '".CUtil::JSEscape(GetMessage("admin_lib_filter_less"))."',
	'messLoading': '".CUtil::JSEscape(GetMessage("admin_lib_loading"))."',
	'messMenuLoading': '".CUtil::JSEscape(GetMessage("admin_lib_menu_loading"))."',
	'messMenuLoadingTitle': '".CUtil::JSEscape(GetMessage("admin_lib_loading_title"))."',
	'messNoData': '".CUtil::JSEscape(GetMessage("admin_lib_no_data"))."',
	'messExpandTabs': '".CUtil::JSEscape(GetMessage("admin_lib_expand_tabs"))."',
	'messCollapseTabs': '".CUtil::JSEscape(GetMessage("admin_lib_collapse_tabs"))."',
	'messPanelFixOn': '".CUtil::JSEscape(GetMessage("admin_lib_panel_fix_on"))."',
	'messPanelFixOff': '".CUtil::JSEscape(GetMessage("admin_lib_panel_fix_off"))."',
	'messPanelCollapse': '".CUtil::JSEscape(GetMessage("admin_lib_panel_hide"))."',
	'messPanelExpand': '".CUtil::JSEscape(GetMessage("admin_lib_panel_show"))."'
};
</script>
";

		$APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');
		$APPLICATION->AddHeadScript('/bitrix/js/main/admin_tools.js');
		$APPLICATION->AddHeadScript('/bitrix/js/main/popup_menu.js');
		$APPLICATION->AddHeadScript('/bitrix/js/main/admin_search.js');
		$APPLICATION->AddHeadScript('/bitrix/js/main/hot_keys.js');

		return $s;
	}

	
	/**
	 * <p>Функция выводит страницу админки со списком подразделов.</p>
	 *
	 *
	 *
	 *
	 * @param $menu_i $d  ID пункта меню, подразделы которого будут в списке (т.е. то, что
	 * указано в items_id)
	 *
	 *
	 *
	 * @param $module_i $d = false ID модуля, для которого строится список
	 *
	 *
	 *
	 * @param $mod $e = false Режим: <ul> <li> <b>icon</b> - значки,</li> <li> <b>list</b> - список,</li> <li> <b>table</b> -
	 * таблица</li> </ul> Если не указан режим, отображаются значки или то,
	 * что было выбрано пользователем при предыдущем открытии страницы.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");define("HELP_FILE", "subscribe/index.php");IncludeModuleLangFile(__FILE__);$POST_RIGHT = $APPLICATION-&gt;GetGroupRight("subscribe");if($POST_RIGHTAuthForm(GetMessage("ACCESS_DENIED"));$APPLICATION-&gt;SetTitle(GetMessage("subscr_index_title"));if($_REQUEST["mode"] == "list")    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");else    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");$adminPage-&gt;ShowSectionIndex("menu_subscribe", "subscribe");if($_REQUEST["mode"] == "list")    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");else    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?&gt;
	<b>Примечание</b> В приведённом примере указаны страницы, которых нет в админке с версии 12.0.
	 * </pre>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminpage/showsectionindex.php
	 * @author Bitrix
	 */
	public function ShowSectionIndex($menu_id, $module_id=false, $mode=false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		echo '<div id="index_page_result_div">';

		$sTableID = "module_index_table";
		$page = $APPLICATION->GetCurPage();
		$param = DeleteParam(array("show_mode", "mode"));
		echo '
			<script>
			var '.$sTableID.' = new JCAdminList("'.$sTableID.'");
			jsUtils.addEvent(window, "unload", function(){'.$sTableID.'.Destroy(true);});

			function LoadIndex(mode)
			{
				'.$sTableID.'.Destroy(false);
				jsUtils.LoadPageToDiv("'.$page.'?show_mode="+mode+"&mode=list'.($param<>""? "&".$param:"").'", "index_page_result_div");
			}
			</script>
			';

		if($module_id === false)
			$this->Init();

		/** @global CAdminMenu() $adminMenu */
		global $adminMenu;

		$adminMenu->Init(($module_id !== false? array($module_id) : $this->aModules));
		$adminMenu->ShowSubmenu($menu_id, "table");

		echo '</div>';
	}

	
	/**
	 * 
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminpage/showsound.php
	 * @author Bitrix
	 */
	public static function ShowSound()
	{
		/** @global CMain $APPLICATION */
		global $USER, $APPLICATION;

		$res = '';
		if($USER->IsAuthorized() && !isset($_COOKIE[COption::GetOptionString("main", "cookie_name", "BITRIX_SM").'_SOUND_LOGIN_PLAYED']))
		{
			$aUserOptGlobal = CUserOptions::GetOption("global", "settings");
			if($aUserOptGlobal["sound"] == 'Y')
			{
				if($aUserOptGlobal["sound_login"] == '')
					$aUserOptGlobal["sound_login"] = "/bitrix/sounds/main/bitrix_tune.mp3";

				ob_start();
				$APPLICATION->IncludeComponent("bitrix:player",	"",
					Array(
						"PLAYER_TYPE" => "flv",
						"PATH" => htmlspecialcharsbx($aUserOptGlobal["sound_login"]),
						"WIDTH" => "1",
						"HEIGHT" => "1",
						"CONTROLBAR" => "none",
						"AUTOSTART" => "Y",
						"REPEAT" => "N",
						"VOLUME" => "90",
						"MUTE" => "N",
						"HIGH_QUALITY" => "Y",
						"BUFFER_LENGTH" => "2",
						"PROVIDER"=>"sound",
					),
					null, array("HIDE_ICONS"=>"Y")
				);
				$res = ob_get_contents();
				ob_end_clean();

				$res = '
<script type="text/javascript" src="'.CUtil::GetAdditionalFileURL('/bitrix/components/bitrix/player/mediaplayer/flvscript.js', true).'"></script>
<div style="position:absolute; top:-1000px; left:-1000px;">
'.$res.'
</div>
';
			}
		}
		return $res;
	}
}

/* Left tree-view menu */
class CAdminMenu
{
	var $aGlobalMenu, $aActiveSections=array(), $aOpenedSections=array();
	var $bInit = false;

	static function CAdminMenu()
	{
	}

	public function Init($modules)
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		global $APPLICATION, $USER, $DB, $MESS;

		if($this->bInit)
			return;
		$this->bInit = true;

		$aOptMenu = CUserOptions::GetOption("admin_menu", "pos", array());
		$this->AddOpenedSections($aOptMenu["sections"]);

		$aModuleMenu = array();
		if(is_array($modules))
		{
			foreach($modules as $module)
			{
				$module = _normalizePath($module);

				//trying to include file menu.php in the /admin/ folder of the current module
				$fname = getLocalPath("modules/".$module."/admin/menu.php");
				if($fname !== false)
				{
					$menu = CAdminMenu::_IncludeMenu($_SERVER["DOCUMENT_ROOT"].$fname);
					if(is_array($menu) && !empty($menu))
					{
						if(isset($menu["parent_menu"]) && $menu["parent_menu"] <> "")
						{
							//one section
							$aModuleMenu[] = $menu;
						}
						else
						{
							//multiple sections
							foreach($menu as $submenu)
								$aModuleMenu[] = $submenu;
						}
					}
				}
			}
		}

		//additional user menu
		$aMenuLinks = array();
		if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/admin/.left.menu.php"))
			include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/admin/.left.menu.php");
		if(!empty($aMenuLinks))
		{
			$bWasSeparator = false;
			$menu = array();
			foreach($aMenuLinks as $module_menu)
			{
				if($module_menu[3]["SEPARATOR"] == "Y")
				{
					//first level
					if(!empty($menu))
						$aModuleMenu[] = $menu;

					$menu = array(
						"parent_menu" => "global_menu_services",
						"icon" => "default_menu_icon",
						"page_icon" => "default_page_icon",
						"items_id"=>$module_menu[3]["SECTION_ID"],
						"items"=>array(),
						"sort"=>$module_menu[3]["SORT"],
						"text" => $module_menu[0],
					);
					$bWasSeparator = true;
				}
				elseif($bWasSeparator && $module_menu[3]["SECTION_ID"] == "")
				{
					//section items
					$menu["items"][] = array(
						"text" => $module_menu[0],
						"title"=>$module_menu[3]["ALT"],
						"url" => $module_menu[1],
						"more_url"=>$module_menu[2],
					);
				}
				elseif($module_menu[3]["SECTION_ID"] == "" || $module_menu[3]["SECTION_ID"] == "statistic" || $module_menu[3]["SECTION_ID"] == "sale")
				{
					//item in root
					$aModuleMenu[] = array(
						"parent_menu" => ($module_menu[3]["SECTION_ID"] == "statistic"? "global_menu_statistics" : ($module_menu[3]["SECTION_ID"] == "sale"? "global_menu_store":"global_menu_services")),
						"icon" => "default_menu_icon",
						"page_icon" => "default_page_icon",
						"sort"=>$module_menu[3]["SORT"],
						"text" => $module_menu[0],
						"title"=>$module_menu[3]["ALT"],
						"url" => $module_menu[1],
						"more_url"=>$module_menu[2],
					);
				}
				else
				{
					//item in section
					foreach($aModuleMenu as $i=>$section)
					{
						if($section["section"] == $module_menu[3]["SECTION_ID"])
						{
							if(!is_array($aModuleMenu[$i]["items"]))
								$aModuleMenu[$i]["items"] = array();

							$aModuleMenu[$i]["items"][] = array(
								"text" => $module_menu[0],
								"title"=>$module_menu[3]["ALT"],
								"url" => $module_menu[1],
								"more_url"=>$module_menu[2],
							);
							break;
						}
					}
				}
			}
			if(!empty($menu))
				$aModuleMenu[] = $menu;
		}

		$this->aGlobalMenu = array(
			"global_menu_desktop" => array(
				"menu_id" => "desktop",
				"page_icon" => "content_title_icon",
				"index_icon" => "content_page_icon",
				"text" => GetMessage('admin_lib_desktop'),
				"title" => GetMessage('admin_lib_desktop_title'),
				"url" => "index.php?lang=".LANGUAGE_ID,
				"sort" => 50,
				"items_id" => "global_menu_desktop",
				"help_section" => "desktop",
				"items" => array()
			),
			"global_menu_content" => array(
				"menu_id" => "content",
				//"icon" => "button_content",
				"page_icon" => "content_title_icon",
				"index_icon" => "content_page_icon",
				"text" => GetMessage("admin_lib_menu_content"),
				"title" => GetMessage("admin_lib_menu_content_title"),
				"sort" => 100,
				"items_id" => "global_menu_content",
				"help_section" => "content",
				"items" => array()
			),
			"global_menu_services" => array(
				"menu_id" => "services",
				//"icon" => "button_services",
				"page_icon" => "services_title_icon",
				"index_icon" => "services_page_icon",
				"text" => GetMessage("admin_lib_menu_services"),
				"title" => GetMessage("admin_lib_menu_service_title"),
				"sort" => 200,
				"items_id" => "global_menu_services",
				"help_section" => "service",
				"items" => array()
			),
			"global_menu_store" => array(
				"menu_id" => "store",
				//"icon" => "button_store",
				"page_icon" => "store_title_icon",
				"index_icon" => "store_page_icon",
				"text" => GetMessage("admin_lib_menu_store"),
				"title" => GetMessage("admin_lib_menu_store_title"),
				"sort" => 300,
				"items_id" => "global_menu_store",
				"help_section" => "store",
				"items" => array()
			),
			"global_menu_statistics" => array(
				"menu_id" => "analytics",
				//"icon" => "button_statistics",
				"page_icon" => "statistics_title_icon",
				"index_icon" => "statistics_page_icon",
				"text" => GetMessage("admin_lib_menu_stat"),
				"title" => GetMessage("admin_lib_menu_stat_title"),
				"sort" => 400,
				"items_id" => "global_menu_statistics",
				"help_section" => "statistic",
				"items" => array()
			),
			"global_menu_marketplace" => array(
				"menu_id" => "marketPlace",
				//"icon" => "button_marketplace",
				"page_icon" => "marketplace_title_icon",
				"index_icon" => "marketplace_page_icon",
				"text" => GetMessage("admin_lib_menu_marketplace"),
				"title" => GetMessage("admin_lib_menu_marketplace_title"),
				"url" => "update_system_market.php?lang=".LANGUAGE_ID,
				"sort" => 400,
				"items_id" => "global_menu_marketplace",
				"help_section" => "marketplace",
				"items" => array()
			),
			"global_menu_settings" => array(
				"menu_id" => "settings",
				//"icon" => "button_settings",
				"page_icon" => "settings_title_icon",
				"index_icon" => "settings_page_icon",
				"text" => GetMessage("admin_lib_menu_settings"),
				"title" => GetMessage("admin_lib_menu_settings_title"),
				"sort" => 500,
				"items_id" => "global_menu_settings",
				"help_section" => "settings",
				"items" => array()
			),
		);

		//User defined global sections
		$bSort = false;
		foreach(GetModuleEvents("main", "OnBuildGlobalMenu", true) as $arEvent)
		{
			$bSort = true;
			$arRes = ExecuteModuleEventEx($arEvent, array(&$this->aGlobalMenu, &$aModuleMenu));
			if(is_array($arRes))
				$this->aGlobalMenu = array_merge($this->aGlobalMenu, $arRes);
		}
		if($bSort)
			uasort($this->aGlobalMenu, array($this, '_sort'));

		foreach($aModuleMenu as $menu)
			$this->aGlobalMenu[$menu["parent_menu"]]["items"][] = $menu;

		$sort_func = array($this, '_sort');
		foreach($this->aGlobalMenu as $key => $menu)
		{
			if(empty($menu["items"]) && $key != "global_menu_desktop")
			{
				unset($this->aGlobalMenu[$key]);
			}
			else
			{
				usort($this->aGlobalMenu[$key]["items"], $sort_func);
			}
		}

		foreach($this->aGlobalMenu as $key=>$menu)
			if($this->_SetActiveItems($this->aGlobalMenu[$key]))
				break;
	}

	public static function _sort($a, $b)
	{
		if($a["sort"] == $b["sort"])
			return 0;
		return ($a["sort"] < $b["sort"]? -1 : 1);
	}

	public static function _IncludeMenu($fname)
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		global $APPLICATION, $USER, $DB, $MESS;

		$aModuleMenuLinks = array();
		$menu =  include($fname);

		if(is_array($menu) && !empty($menu))
			return $menu;

		if(!empty($aModuleMenuLinks))
		{
			$menu = array();
			$n = 0;
			foreach($aModuleMenuLinks as $module_menu)
			{
				if($n == 0)
				{
					//first level
					$menu = array(
						"parent_menu" => "global_menu_services",
						"icon" => "default_menu_icon",
						"page_icon" => "default_page_icon",
						"items_id"=>"sect_".md5($fname),
						"items"=>array(),
						"sort"=>$module_menu[3]["SORT"],
						"text" => $module_menu[0],
						"url" => $module_menu[1],
					);
				}
				else
				{
					//section items
					$menu["items"][] = array(
						"text" => $module_menu[0],
						"title"=>$module_menu[3]["ALT"],
						"url" => $module_menu[1],
						"more_url"=>$module_menu[2],
					);
				}
				$n++;
			}
			return $menu;
		}
		return false;
	}

	public function _SetActiveItems(&$aMenu, $aSections=array())
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$bSubmenu = (isset($aMenu["items"]) && is_array($aMenu["items"]) && !empty($aMenu["items"]));
		if($bSubmenu)
			$aSections[$aMenu["items_id"]] = array(
				"menu_id" => $aMenu["menu_id"],
				"items_id"=>$aMenu["items_id"],
				"page_icon"=>isset($aMenu["page_icon"])? $aMenu["page_icon"]: null,
				"text"=>$aMenu["text"],
				"url"=>$aMenu["url"],
				"skip_chain"=>isset($aMenu["skip_chain"])? $aMenu["skip_chain"]: null,
				"help_section"=>isset($aMenu["help_section"])? $aMenu["help_section"]: null,
			);

		$bSelected = false;
		$bMoreUrl = (isset($aMenu["more_url"]) && is_array($aMenu["more_url"]) && !empty($aMenu["more_url"]));
		if($aMenu["url"] <> "" || $bMoreUrl)
		{
			$cur_page = $APPLICATION->GetCurPage();

			$all_links = array();
			if($aMenu["url"] <> "")
				$all_links[] = $aMenu["url"];
			if($bMoreUrl)
				$all_links = array_merge($all_links, $aMenu["more_url"]);

			$n = count($all_links);
			for($j = 0; $j < $n; $j++)
			{
				//"/admin/"
				//"/admin/index.php"
				//"/admin/index.php?module=mail"
				if(empty($all_links[$j]))
					continue;

				if(strpos($all_links[$j], "/bitrix/admin/") !== 0)
					$tested_link = "/bitrix/admin/".$all_links[$j];
				else
					$tested_link = $all_links[$j];

				if(strlen($tested_link) > 0 && strpos($cur_page, $tested_link) === 0)
				{
					$bSelected = true;
					break;
				}

				if(($pos = strpos($tested_link, "?"))!==false)
				{
					if(substr($tested_link, 0, $pos)==$cur_page)
					{
						$right = substr($tested_link, $pos+1);
						$params = explode("&", $right);
						$bOK = true;

						foreach ($params as $paramKeyAndValue)
						{
							$eqpos = strpos($paramKeyAndValue, "=");
							$varvalue = "";
							if($eqpos === false)
							{
								$varname = $paramKeyAndValue;
							}
							elseif($eqpos == 0)
							{
								continue;
							}
							else
							{
								$varname = substr($paramKeyAndValue, 0, $eqpos);
								$varvalue = urldecode(substr($paramKeyAndValue, $eqpos+1));
							}

							$globvarvalue = isset($_REQUEST[$varname]) ? $_REQUEST[$varname] : "";
							if($globvarvalue != $varvalue)
							{
								$bOK = false;
								break;
							}
						} //foreach ($params as $paramKeyAndValue)

						if($bOK)
						{
							$bSelected = true;
							break;
						}
					}//if(substr($tested_link, 0, $pos)==$cur_page)
				} //if(($pos = strpos($tested_link, "?"))!==false)
			} //for($j = 0; $j < $n; $j++)
		}

		$bSelectedInside = false;
		if($bSubmenu)
		{
			foreach($aMenu["items"] as $key=>$submenu)
				if($this->_SetActiveItems($aMenu["items"][$key], $aSections))
				{
					$bSelectedInside = true;
					break;
				}
		}

		if($bSelected && !$bSelectedInside)
		{
			if(!$bSubmenu)
			{
				$aSections["_active"] = array(
					"menu_id"=>$aMenu["menu_id"],
					"page_icon"=>$aMenu["page_icon"],
					"text"=>$aMenu["text"],
					"url"=>$aMenu["url"],
					"skip_chain"=>$aMenu["skip_chain"],
					"help_section"=>$aMenu["help_section"],
				);
			}
			$aMenu["_active"] = true;
			$this->aActiveSections = $aSections;
		}

		return $bSelected || $bSelectedInside;
	}

	private function _get_menu_item_width($level)
	{
		static $START_MAGIC_NUMBER = 30, $STEP_MAGIC_NUMBER = 21;
		return $START_MAGIC_NUMBER + $level*$STEP_MAGIC_NUMBER;
	}

	private function _get_menu_item_padding($level)
	{
		static $ADDED_MAGIC_NUMBER = 8;
		return $this->_get_menu_item_width($level) + $ADDED_MAGIC_NUMBER;
	}

	public function Show($aMenu, $level=0)
	{
		$scripts = '';

		$bSubmenu = (isset($aMenu["items"]) && is_array($aMenu["items"]) && !empty($aMenu["items"])) || isset($aMenu["dynamic"]) && $aMenu["dynamic"] == true;
		$bSectionActive = isset($aMenu["items_id"]) && (in_array($aMenu["items_id"], array_keys($this->aActiveSections)) || $this->IsSectionActive($aMenu["items_id"]));

		$icon = isset($aMenu["icon"]) && $aMenu["icon"] <> ""
			? '<span class="adm-submenu-item-link-icon '.$aMenu["icon"].'"></span>'
//			: ($level < 1 ? '<span class="adm-submenu-item-link-icon" id="default_menu_icon"></span>' : '');
			: '';
		$id = 'menu_item_'.RandString(10);
		?><div class="adm-sub-submenu-block<?=$level > 0 ? ' adm-submenu-level-'.($level+1) : ''?><?=$bSectionActive && isset($aMenu["items"]) && is_array($aMenu["items"]) && count($aMenu['items']) > 0 ? ' adm-sub-submenu-open' : ''?><?=$aMenu["_active"] ? ' adm-submenu-item-active' : ''?>"><?
		?><div class="adm-submenu-item-name<?=!$bSubmenu ? ' adm-submenu-no-children' : ''?>" id="<?=$id?>" data-type="submenu-item"<?=isset($aMenu['fav_id']) ? ' data-fav-id="'.intval($aMenu['fav_id']).'"' : ''?>><?
		$onclick = '';
		if ($bSubmenu)
		{
			if(isset($aMenu["dynamic"]) && $aMenu["dynamic"] == true && (!$aMenu["items"] || count($aMenu["items"]) <= 0))
			{
				$onclick = "BX.adminMenu.toggleDynSection(".$this->_get_menu_item_width($level).", this.parentNode.parentNode, '".htmlspecialcharsbx(CUtil::JSEscape($aMenu["module_id"]))."', '".htmlspecialcharsbx(CUtil::JSEscape($aMenu["items_id"]))."', '".($level+1)."')";
			}
			elseif(!$aMenu["dynamic"] || !$bSectionActive || $aMenu['dynamic'] && $bSectionActive && isset($aMenu["items"]) && count($aMenu["items"]) > 0)
			{
				$onclick = "BX.adminMenu.toggleSection(this.parentNode.parentNode, '".htmlspecialcharsbx(CUtil::JSEscape($aMenu["items_id"]))."', '".($level+1)."')";
			} //endif;
		}

		?><span class="adm-submenu-item-arrow"<?=$level > 0 ? ' style="width:'.$this->_get_menu_item_width($level).'px;"' : ''?><?=$onclick ? ' onclick="'.$onclick.'"' : ''?>><span class="adm-submenu-item-arrow-icon"></span></span><?

		if(isset($aMenu["url"]) && $aMenu["url"] <> ""):
			?><a class="adm-submenu-item-name-link<?=(isset($aMenu["readonly"]) && $aMenu["readonly"] == true? ' menutext-readonly':'')?>"<?=$level > 0 ? ' style="padding-left:'.$this->_get_menu_item_padding($level).'px;"' : ''?> href="<?=$aMenu["url"]?>"><?=$icon?><span class="adm-submenu-item-name-link-text"><?=$aMenu["text"]?></span></a><?
		elseif ($bSubmenu):
			if(isset($aMenu["dynamic"]) && $aMenu["dynamic"] == true && !$bSectionActive && (!$aMenu["items"] || count($aMenu["items"]) <= 0)):
				?><a class="adm-submenu-item-name-link<?=(isset($aMenu["readonly"]) && $aMenu["readonly"] == true? ' menutext-readonly':'')?>"<?=$level > 0 ? ' style="padding-left:'.$this->_get_menu_item_padding($level).'px;"' : ''?> href="javascript:void(0)" onclick="BX.adminMenu.toggleDynSection(<?=$this->_get_menu_item_width($level)?>, this.parentNode.parentNode, '<?=htmlspecialcharsbx(CUtil::JSEscape($aMenu["module_id"]))?>', '<?=htmlspecialcharsbx(CUtil::JSEscape($aMenu["items_id"]))?>', '<?=$level+1?>')"><?=$icon?><span class="adm-submenu-item-name-link-text"><?=$aMenu["text"]?></span></a><?
			elseif(!$aMenu["dynamic"] || !$bSectionActive || $aMenu['dynamic'] && $bSectionActive && isset($aMenu["items"]) && count($aMenu["items"]) > 0):
				?><a class="adm-submenu-item-name-link<?=(isset($aMenu["readonly"]) && $aMenu["readonly"] == true? ' menutext-readonly':'')?>"<?=$level > 0 ? ' style="padding-left:'.$this->_get_menu_item_padding($level).'px;"' : ''?> href="javascript:void(0)" onclick="BX.adminMenu.toggleSection(this.parentNode.parentNode, '<?=htmlspecialcharsbx(CUtil::JSEscape($aMenu["items_id"]))?>', '<?=$level+1?>')"><?=$icon?><span class="adm-submenu-item-name-link-text"><?=$aMenu["text"]?></span></a><?
			endif;
		else:
			?><span class="adm-submenu-item-name-link<?=(isset($aMenu["readonly"]) && $aMenu["readonly"] == true? ' menutext-readonly':'')?>"<?=$level > 0 ? ' style="padding-left:'.$this->_get_menu_item_padding($level).'px"' : ''?>><?=$icon?><span class="adm-submenu-item-name-link-text"><?=$aMenu["text"]?></span></span><?
		endif;
		?></div><?

		if(($bSubmenu || (isset($aMenu["dynamic"]) && $aMenu["dynamic"] == true)) && is_array($aMenu["items"]))
		{
			echo  "<div class=\"adm-sub-submenu-block-children\">";
			foreach($aMenu["items"] as $submenu)
			{
				if($submenu)
				{
					$scripts .= $this->Show($submenu, $level+1);
				}
			}
			echo "</div>";
		}
		else
			echo  "<div class=\"adm-sub-submenu-block-children\"></div>";
?></div><?
		if (isset($aMenu["fav_id"]))
		{
			$scripts .= "BX.adminMenu.registerItem('".$id."', {FAV_ID:'".CUtil::JSEscape($aMenu['fav_id'])."'});";
		}
		elseif (isset($aMenu["items_id"]) && $aMenu['url'])
		{
			$scripts .= "BX.adminMenu.registerItem('".$id."', {ID:'".CUtil::JSEscape($aMenu['items_id'])."', URL:'".CUtil::JSEscape(htmlspecialcharsback($aMenu['url']))."', MODULE_ID:'".$aMenu['module_id']."'});";
		}
		elseif (isset($aMenu["items_id"]))
		{
			$scripts .= "BX.adminMenu.registerItem('".$id."', {ID:'".CUtil::JSEscape($aMenu['items_id'])."', MODULE_ID:'".$aMenu['module_id']."'});";
		}
		elseif ($aMenu['url'])
		{
			$scripts .= "BX.adminMenu.registerItem('".$id."', {URL:'".CUtil::JSEscape(htmlspecialcharsback($aMenu['url']))."'});";
		}

		return $scripts;
	}

	public static function ShowIcons($aMenu)
	{
		foreach($aMenu["items"] as $submenu)
		{
			if(!$submenu)
				continue;
			echo
				'<div class="index-icon-block" align="center">'.
				'<a href="'.$submenu["url"].'" title="'.$submenu["title"].'"><div class="index-icon" id="'.($submenu["page_icon"]<>""? $submenu["page_icon"]:$aMenu["page_icon"]).'"></div>'.
				'<div class="index-label">'.$submenu["text"].'</div></a>'.
				'</div>';
		}
		echo '<br clear="all">';
	}

	public static function ShowList($aMenu)
	{
		foreach($aMenu["items"] as $submenu)
		{
			if(!$submenu)
				continue;
			echo '<div class="index-list" id="'.($submenu["icon"]<>""? $submenu["icon"]:$aMenu["icon"]).'"><a href="'.$submenu["url"].'" title="'.$submenu["title"].'">'.$submenu["text"].'</a></div>';
		}
	}

	public static function ShowTable($aMenu)
	{
		$sTableID = "module_index_table";
		// List init
		$lAdmin = new CAdminList($sTableID);

		// List headers
		$lAdmin->AddHeaders(array(
			array("id"=>"NAME", "content"=>GetMessage("admin_lib_index_name"), "default"=>true),
			array("id"=>"DESCRIPTION", "content"=>GetMessage("admin_lib_index_desc"), "default"=>true),
		));

		$n = 0;
		foreach($aMenu["items"] as $submenu)
		{
			// Populate list with data
			if(!$submenu)
				continue;
			$row = &$lAdmin->AddRow(0, null, $submenu["url"], GetMessage("admin_lib_index_go"));
			$row->AddField("NAME", '<a href="'.$submenu["url"].'" title="'.$submenu["title"].'">'.$submenu["text"].'</a>');
			$row->AddField("DESCRIPTION", $submenu["title"]);
			$n++;
		}

		// List footer
		$lAdmin->AddFooter(
			array(
				array(
					"title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"),
					"value" => $n
				)
			)
		);

		$lAdmin->Display();

		echo '
<script>
'.$sTableID.'.InitTable();
</script>
';
	}

	public function ShowSubmenu($menu_id, $mode="menu")
	{
		foreach($this->aGlobalMenu as $key=>$menu)
			if($this->_ShowSubmenu($this->aGlobalMenu[$key], $menu_id, $mode))
				break;
	}

	public function _ShowSubmenu(&$aMenu, $menu_id, $mode, $level=0)
	{
		$bSubmenu = (is_array($aMenu["items"]) && count($aMenu["items"])>0);
		if($bSubmenu)
		{
			if($aMenu["items_id"] == $menu_id)
			{
				if($mode == "menu")
				{
					$menuScripts = "";
					foreach($aMenu["items"] as $submenu)
					{
						$menuScripts .= $this->Show($submenu, $level);
					}
					if ($menuScripts != "")
						echo '<script type="text/javascript">'.$menuScripts.'</script>';
				}
				elseif($mode == "icon")
					$this->ShowIcons($aMenu);
				elseif($mode == "list")
					$this->ShowList($aMenu);
				elseif($mode == "table")
					$this->ShowTable($aMenu);

				return true;
			}
			else
			{
				foreach($aMenu["items"] as $submenu)
					if($this->_ShowSubmenu($submenu, $menu_id, $mode, $level+1))
						return true;
			}
		}
		return false;
	}

	public function ActiveSection()
	{
		if(!empty($this->aActiveSections))
			foreach($this->aActiveSections as $menu)
				return $menu;

		foreach($this->aGlobalMenu as $menu)
			return $menu;

		return null;
	}

	public function ActiveIcon()
	{
		if(!empty($this->aActiveSections))
		{
			$aSections = array_keys($this->aActiveSections);
			for($i=count($aSections)-1; $i>=0; $i--)
				if($this->aActiveSections[$aSections[$i]]["page_icon"] <> "")
					return $this->aActiveSections[$aSections[$i]]["page_icon"];
		}
		return "default_page_icon";
	}

	public function AddOpenedSections($sections)
	{
		$aSect = explode(",", $sections);
		foreach($aSect as $sect)
			if(trim($sect) <> "")
				$this->aOpenedSections[] = trim($sect);
	}

	public function IsSectionActive($section)
	{
		return in_array($section, $this->aOpenedSections);
	}

	public function GetOpenedSections()
	{
		return implode(",", $this->aOpenedSections);
	}
}

/* Popup menu */
class CAdminPopup
{
	var $name;
	var $id;
	var $items;
	var $params;

	public function CAdminPopup($name, $id, $items=false, $params=false)
	{
		//SEPARATOR, ID, ONCLICK, ICONCLASS, TEXT, DEFAULT=>true|false, DISABLED=>true|false
		$this->name = $name;
		$this->id = $id;
		$this->items = $items;
		$this->params = $params;
	}

	public function Show($bReturnValue=false)
	{
		$s = '';
		if(!isset($_REQUEST["mode"]) || $_REQUEST["mode"] != "frame")
		{
			$s .=
'<script>
window.'.$this->name.' = new PopupMenu("'.$this->id.'"'.
	(is_array($this->params) && isset($this->params['zIndex'])? ', '.$this->params['zIndex']:'').
	(is_array($this->params) && isset($this->params['dxShadow'])? ', '.$this->params['dxShadow']:'').
');
';
			if(is_array($this->items))
			{
				$s .=
'window.'.$this->name.'.SetItems('.CAdminPopup::PhpToJavaScript($this->items).');
';
			}
			$s .=
'</script>
';
		}
		if($bReturnValue)
			return $s;
		else
			echo $s;
		return null;
	}

	public static function GetGlobalIconClass($old_icon)
	{
		switch($old_icon)
		{
			case 'edit':
				return 'adm-menu-edit';

			case 'view':
			case 'btn_fileman_view':
				return 'adm-menu-view';

			case 'copy':
				return 'adm-menu-copy';

			case 'move':
				return 'adm-menu-move';

			case 'rename':
				return 'adm-menu-rename';

			case 'delete':
				return 'adm-menu-delete';

			case 'btn_fileman_html':
				return 'adm-menu-edit-htm';

			case 'btn_fileman_php':
				return 'adm-menu-edit-php';

			case 'btn_fileman_text':
				return 'adm-menu-edit-txt';

			case 'btn_fileman_galka': // so it is
				return 'adm-menu-edit-wf';

			case 'btn_download':
				return 'adm-menu-download';

			case 'pack':
				return 'adm-menu-pack';

			case 'unpack':
				return 'adm-menu-unpack';

			case 'access':
				return 'adm-menu-access';

			case 'btn_fileman_prop':
				return 'adm-menu-folder-props';
		}

		return false;
	}

	public static function PhpToJavaScript($items)
	{
		$sMenuUrl = "[";
		if(is_array($items))
		{
			$i = 0;
			foreach($items as $action)
			{
				if($i > 0)
					$sMenuUrl .= ",\n";

				if(isset($action["SEPARATOR"]) && ($action["SEPARATOR"] === true || $action["SEPARATOR"] == "Y"))
					$sMenuUrl .= "{'SEPARATOR':true}";
				else
				{
					if($action["ONCLICK"] <> "")
						$action["ACTION"] = $action["ONCLICK"];

					if (isset($action["ICON"]) && $action["ICON"]<>""
						&& empty($action["GLOBAL_ICON"]))
					{
						$icon_global_class = CAdminPopup::GetGlobalIconClass($action["ICON"]);
						if ($icon_global_class)
						{
							$action["GLOBAL_ICON"] = $icon_global_class;
							unset($action["ICON"]);
						}
					}

					$sItem =
						(isset($action["LINK"]) && $action["LINK"]<>""? "'LINK':'".CUtil::JSEscape($action["LINK"])."',":"").
						(isset($action["DEFAULT"]) && $action["DEFAULT"] === true? "'DEFAULT':true,":"").
						(isset($action["CHECKED"]) && $action["CHECKED"] === true? "'CHECKED':true,":"").
						(isset($action["ICON"]) && $action["ICON"]<>""? "'ICONCLASS':'".CUtil::JSEscape($action["ICON"])."',":"").
						(isset($action["GLOBAL_ICON"]) && $action["GLOBAL_ICON"]<>""? "'GLOBAL_ICON':'".CUtil::JSEscape($action["GLOBAL_ICON"])."',":"").
						(isset($action["IMAGE"]) && $action["IMAGE"]<>""? "'IMAGE':'".CUtil::JSEscape($action["IMAGE"])."',":"").
						(isset($action["ID"]) && $action["ID"]<>""? "'ID':'".CUtil::JSEscape($action["ID"])."',":"").
						(isset($action["DISABLED"]) && $action["DISABLED"] == true? "'DISABLED':true,":"").
						(isset($action["AUTOHIDE"]) && $action["AUTOHIDE"] == false? "'AUTOHIDE':false,":"").
						(isset($action["DEFAULT"]) && $action["DEFAULT"] == true? "'DEFAULT':true,":"").
						($action["TEXT"]<>""? "'TEXT':'".CUtil::JSEscape($action["TEXT"])."',":"").
						(isset($action["TITLE"]) && $action["TITLE"]<>""? "'TITLE':'".CUtil::JSEscape($action["TITLE"])."',":"").
						(isset($action["SHOW_TITLE"]) && $action["SHOW_TITLE"] == true ? "'SHOW_TITLE':true,":"").
						($action["ACTION"]<>""? "'ONCLICK':'".CUtil::JSEscape(htmlspecialcharsback($action["ACTION"]))."',":"").
						(isset($action["ONMENUPOPUP"]) && $action["ONMENUPOPUP"]<>""? "'ONMENUPOPUP':'".CUtil::JSEscape($action["ONMENUPOPUP"])."',":"").
						(isset($action["MENU"]) && is_array($action["MENU"])? "'MENU':".CAdminPopup::PhpToJavaScript($action["MENU"]).",":"").
						(isset($action["MENU_URL"]) && $action["MENU_URL"]<>''? "'MENU_URL':'".CUtil::JSEscape($action["MENU_URL"])."',":"").
						(isset($action["MENU_PRELOAD"]) && $action["MENU_PRELOAD"] == true? "'MENU_PRELOAD':true,":"").
						(isset($action["CLOSE_ON_CLICK"]) && $action["CLOSE_ON_CLICK"] == false? "'CLOSE_ON_CLICK':false,":"");
					if($sItem <> "")
						$sItem = substr($sItem, 0, -1); //delete last comma
					$sMenuUrl .= "{".$sItem."}";
				}
				$i++;
			}
		}
		$sMenuUrl .= "]";
		return $sMenuUrl;
	}
}

class CAdminPopupEx extends CAdminPopup
{
	protected $element_id;

	public function __construct($element_id, $items=false, $params=false)
	{
//SEPARATOR, ID, ONCLICK|LINK, ICONCLASS, TEXT, DEFAULT=>true|false, MENU
		$this->element_id = $element_id;
		$this->items = $items;
		$this->params = $params;
	}

	public function Show($bReturnValue=false)
	{
		$s = '';
		if((!isset($_REQUEST["mode"]) || $_REQUEST["mode"] != "frame") && is_array($this->items))
		{
			$params = '';
			if (is_array($this->params))
				$params = ', '.CUtil::PhpToJsObject($params);

			$s .=
"<script type=\"text/javascript\">
BX.ready(function(){
	BX.bind(BX('".$this->element_id."'), 'click', function() {
		BX.adminShowMenu(this, ".CAdminPopup::PhpToJavaScript($this->items).$params.");
	});
});
</script>";
		}

		if($bReturnValue)
			return $s;
		else
			echo $s;
		return null;
	}
}

class CAdminFilter
{
	public 	$id;
	private $popup;
	private $arItems = array();
	private $arOptFlt = array();
	private static $defaultSort = 100;
	private static $defaultPresetSort = 50;
	private $url=false;
	private $tableId=false;
	const   SESS_PARAMS_NAME = "main.adminFilter";

	public function __construct($id, $popup=false, $arExtraParams=array())
	{
		global $USER;

		$uid = $USER->GetID();
		$isAdmin = $USER->CanDoOperation('edit_other_settings');

		if(empty($popup) || !is_array($popup))
			$popup = false;

		$this->id = $id;
		$this->popup = $popup;

		if(is_array($arExtraParams))
		{
			if(isset($arExtraParams["url"]) && !empty($arExtraParams["url"]))
				$this->url = $arExtraParams["url"];

			if(isset($arExtraParams["table_id"]) && !empty($arExtraParams["table_id"]))
				$this->tableId = $arExtraParams["table_id"];
		}

		$this->arOptFlt = CUserOptions::GetOption("filter", $this->id, array(
			"rows" => "",
			"styleFolded" => "N",
			"presetsDeleted" => ""
		));

		$presetsDeleted = explode(",", $this->arOptFlt["presetsDeleted"]);

		$this->arOptFlt["presetsDeleted"] = $presetsDeleted ? $presetsDeleted : array();

		$presetsDeletedJS='';

		if(is_array($presetsDeleted))
			foreach($presetsDeleted as $preset)
				if(trim($preset) <> "")
					$presetsDeletedJS .= ($presetsDeletedJS <> "" ? ",":"").'"'.CUtil::JSEscape(trim($preset)).'"';

		$this->arOptFlt["presetsDeletedJS"] = $presetsDeletedJS;

		$dbRes = $this->GetList(array(), array("USER_ID" => $uid, "FILTER_ID" => $this->id), true);
		while($arFilter = $dbRes->Fetch())
		{
			if(!is_null($arFilter["LANGUAGE_ID"]) && $arFilter["LANGUAGE_ID"] != LANG )
				continue;

			$arItem = $arFilter;
			$arItem["FIELDS"] = unserialize($arFilter["FIELDS"]);

			if(!is_null($arFilter["SORT_FIELD"]))
				$arItem["SORT_FIELD"] = unserialize($arFilter["SORT_FIELD"]);

			if($arFilter["PRESET"] == "Y" && is_null($arFilter["LANGUAGE_ID"]))
			{
				$langName = GetMessage($arFilter["NAME"]);

				if($langName)
						$arItem["NAME"] = $langName;

				foreach ($arItem["FIELDS"] as $key => $field)
				{
					$langValue = GetMessage($arItem["FIELDS"][$key]["value"]);

					if($langValue)
						$arItem["FIELDS"][$key]["value"] = $langValue;
				}
			}

			$arItem["EDITABLE"] = ((($isAdmin || $arFilter["USER_ID"] == $uid ) && $arFilter["PRESET"] != "Y") ? true : false );

			$this->AddItem($arItem);
		}
	}

	private function err_mess()
	{
		return "<br>Class: CAdminFilter<br>File: ".__FILE__;
	}

	private function AddItem($arItem, $bInsertFirst = false)
	{
		//if user "deleted" preset http://jabber.bx/view.php?id=34405
		if(!$arItem["EDITABLE"] && !empty($this->arOptFlt["presetsDeleted"]))
			if(in_array($arItem["ID"], $this->arOptFlt["presetsDeleted"]))
				return false;

		$customPresetId = $this->FindItemByPresetId($arItem["ID"]);

		if($customPresetId)
		{
			$this->arItems[$customPresetId]["SORT"] = $arItem["SORT"];
			return false;
		}

		if(isset($arItem["PRESET_ID"]))
		{
			$presetID = $this->FindItemByID($arItem["PRESET_ID"]);

			if($presetID)
			{
				$arItem["SORT"] = $this->arItems[$presetID]["SORT"];
				unset($this->arItems[$presetID]);
			}

		}

		if(!isset($arItem["SORT"]))
			$arItem["SORT"] = self::$defaultSort;

		if($bInsertFirst)
		{
			$arNewItems[$arItem["ID"]] = $arItem;

			foreach ($this->arItems as $key => $item)
				$arNewItems[$key] = $item;

			$this->arItems = $arNewItems;
		}
		else
			$this->arItems[$arItem["ID"]] = $arItem;

		unset($this->arItems[$arItem["ID"]][$arItem["ID"]]);

		return true;
	}

	private function CheckFields($arFields)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$aMsg = array();

		if(!is_set($arFields, "FILTER_ID") || (is_set($arFields, "FILTER_ID") && trim($arFields["FILTER_ID"])==""))
			$aMsg[] = array("id"=>"FILTER_ID", "text"=>GetMessage("filters_error_table_name"));

		if(!is_set($arFields, "NAME") || (is_set($arFields, "NAME") && trim($arFields["NAME"])==""))
			$aMsg[] = array("id"=>"NAME", "text"=>GetMessage("filters_error_name"));

		if(!is_set($arFields, "FIELDS") || (is_set($arFields, "FIELDS") && trim($arFields["FIELDS"])==""))
			$aMsg[] = array("id"=>"FIELDS", "text"=>GetMessage("filters_error_fields"));

		if((!is_set($arFields, "USER_ID") && $arFields["COMMON"] != "Y") || (is_set($arFields, "USER_ID") && trim($arFields["USER_ID"])==""))
			$aMsg[] = array("id"=>"USER_ID", "text"=>GetMessage("filters_error_user"));

		if(is_set($arFields, "USER_ID"))
		{
			if(intval($arFields["USER_ID"]) > 0)
			{
				$res = CUser::GetByID(intval($arFields["USER_ID"]));
				if(!$res->Fetch())
					$aMsg[] = array("id"=>"USER_ID", "text"=>GetMessage("filters_error_user"));
			}
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		return true;
	}

	private function FieldsExcess($arFields)
	{
		$arResult = array();

		if(is_array($arFields))
			foreach ($arFields as $key => $field)
					$arResult[$key] = array(
										"value" => $field,
										"hidden" => "false",
										);
		return $arResult;
	}

	private function FieldsDelHiddenEmpty($arFields)
	{
		$arResult = array();

		if(!is_array($arFields))
			return false;

		foreach ($arFields as $key => $field)
		{
				if(!empty($field["value"]) || $field["hidden"] == "false")
					$arResult[$key] = array(
										"value" => $field["value"],
										"hidden" => $field["hidden"],
										);
		}
		return $arResult;
	}

	/**
	 * Sets default rows, witch will be shown to user when he comes to page for the first time/
	 * This function must be called on admin page with the filter initialization.
	 * For example: $oFilter->SetDefaultRows("find_created, find_menu_id");
	 *
	 * @param str rows - rows identificators separated by commas ("rowid_1, rowid_2, ...")
	 * @return bool
	 */
	public function SetDefaultRows($rows)
	{
		if(is_array($rows))
			$outRows = implode(",",$rows);
		else
			$outRows = $rows;

		if(!$outRows)
			return false;

		if(!empty($this->arOptFlt["rows"]))
			return true;

		$this->arOptFlt["rows"] = $outRows;

		return true;
	}

	public static function SetDefaultRowsOption($filterId, $rows)
	{
		if(!$filterId)
			return false;

		if(is_array($rows))
			$outRows = implode(",",$rows);
		else
			$outRows = $rows;

		if(!$outRows)
			return false;

		return CUserOptions::SetOption("filter", $filterId, array("rows" => $outRows),true);
	}

	/**
	 * Sets new filter tab with collection of fields and values
	 * This function must be called on admin page with the filter initialization.
	 * For example: $oFilter->AddPreset(array(
	 *									"ID" => "preset1",
	 *									"NAME" => "Test filter",
	 *									"SORT" => 100,
	 *									"SORT_FIELD" => array ("name" => "asc"),
	 *									"FIELDS" => array(
	 *										"find_name"=>"Smith",
	 *										"find_id"=>"15"
	 *										)
	 *									));
	 *
	 * @param array $arFields = array(
	 *								"ID" =>  filter id,
	 *								"NAME" => filter name,
	 *								"SORT" = > filter sorting order. Default value - 100, for presets - 50;
	 *								"SORT_FIELD" => array("Table column name" => "sort order"),
	 *								"FIELDS" => array(
	 *											"field1_name" => "field1_value",
	 *											"field2_name" => "field2_value",
	 *											...
	 * 												)
	 *							)
	 * @return bool
	 */
	public function AddPreset($arFields)
	{
		if(!isset($arFields["NAME"]) || empty($arFields["NAME"]))
			return false;

		if(!isset($arFields["ID"]) || empty($arFields["ID"]))
			return false;

		$item = array(
			"ID" => "page-".$arFields["ID"],
			"FILTER_ID" => $this->id,
			"NAME" => $arFields["NAME"],
			"EDITABLE" => false,
			"PRESET" => "Y"
			);

		if(isset($arFields["FIELDS"]))
			$item["FIELDS"] = CAdminFilter::FieldsExcess($arFields["FIELDS"]);
		else
			$item["FIELDS"] = array();

		if(isset($arFields["SORT"]) && !empty($arFields["SORT"]))
			$item["SORT"] = intval($arFields["SORT"]);
		else
			$item["SORT"] =self::$defaultPresetSort+count($this->arItems)*10;

		if(isset($arFields["SORT_FIELD"]) && is_array($arFields["SORT_FIELD"]) && !empty($arFields["SORT_FIELD"]))
			$item["SORT_FIELD"] = $arFields["SORT_FIELD"];

		return $this->AddItem($item, false);
	}


	private function FindItemByPresetId($strID)
	{

		if(!is_array($this->arItems))
			return false;

		foreach ($this->arItems as $key => $item)
			if($item["PRESET_ID"] == $strID)
				return $key;

		return false;
	}

	private function FindItemByID($strID)
	{
		if(!is_array($this->arItems))
			return false;

		foreach ($this->arItems as $key => $item)
			if($item["ID"] == $strID)
				return $key;

		return false;
	}

	static public function AddPresetToBase($arFields)
	{
		if(!isset($arFields["NAME"]) || empty($arFields["NAME"]))
			return false;

		$arFields["PRESET"] = "Y";
		$arFields["COMMON"] = "Y";

		if(isset($arFields["FIELDS"]))
			$arFields["FIELDS"] = CAdminFilter::FieldsExcess($arFields["FIELDS"]);
		else
			$item["FIELDS"] = array();


		if(!isset($arFields["SORT"]) || empty($arFields["SORT"]))
			$arFields["SORT"] = self::$defaultPresetSort;

		return CAdminFilter::Add($arFields);
	}

	static public function Add($arFields)
	{
		global $DB;

		$arFields["FIELDS"] = CAdminFilter::FieldsDelHiddenEmpty($arFields["FIELDS"]);

		if(!$arFields["FIELDS"])
			return false;

		$arFields["FIELDS"] = serialize($arFields["FIELDS"]);

		if(isset($arFields["SORT_FIELD"]))
			$arFields["SORT_FIELD"] = serialize($arFields["SORT_FIELD"]);

		if(!CAdminFilter::CheckFields($arFields))
			return false;

		$ID = $DB->Add("b_filters", $arFields, array("FIELDS"));
		return $ID;
	}

	static public function Delete($ID)
	{
		global $DB;

		return ($DB->Query("DELETE FROM b_filters WHERE ID='".intval($ID)."'", false, "File: ".__FILE__."<br>Line: ".__LINE__));
	}

	static public function Update($ID, $arFields)
	{
		global $DB;
		$ID = intval($ID);

		$arFields["FIELDS"] = CAdminFilter::FieldsDelHiddenEmpty($arFields["FIELDS"]);

		if(!$arFields["FIELDS"])
			return false;

		$arFields["FIELDS"] = serialize($arFields["FIELDS"]);

		if(isset($arFields["SORT_FIELD"]))
			$arFields["SORT_FIELD"] = serialize($arFields["SORT_FIELD"]);

		if(!CAdminFilter::CheckFields($arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_filters", $arFields);

		$arBinds=Array();
		if(is_set($arFields, "FIELDS"))
			$arBinds["FIELDS"] = $arFields["FIELDS"];


		if(strlen($strUpdate) > 0)
		{
			$strSql = "UPDATE b_filters SET ".$strUpdate." WHERE ID=".$ID;
			return $DB->QueryBind($strSql, $arBinds);

			//if(!$DB->Query($strSql))
			//	return false;
		}

		return false;
	}

	static public function GetList($aSort=array(), $arFilter=Array(), $getCommon=true)
	{
		global $DB;

		$err_mess = (CAdminFilter::err_mess())."<br>Function: GetList<br>Line: ";
		$arSqlSearch = Array();
		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if (strlen($val)<=0 || $val=="NOT_REF")
					continue;

				switch(strtoupper($key))
				{
				case "ID":
					$arSqlSearch[] = GetFilterQuery("F.ID",$val,"N");
					break;
				case "USER_ID":
					if($getCommon)
						$arSqlSearch[] = "F.USER_ID=".intval($val)." OR F.COMMON='Y'";
					else
						$arSqlSearch[] = "F.USER_ID = ".intval($val);
					break;
				case "FILTER_ID":
					$arSqlSearch[] = GetFilterQuery("F.FILTER_ID", $val);
					break;
				case "NAME":
					$arSqlSearch[] = GetFilterQuery("F.NAME", $val);
					break;
				case "FIELDS":
					$arSqlSearch[] = GetFilterQuery("F.FIELDS", $val);
					break;
				case "COMMON":
					$arSqlSearch[] = "F.COMMON = '".$DB->ForSql($val,1)."'";
					break;
				case "PRESET":
					$arSqlSearch[] = "F.PRESET = '".$DB->ForSql($val,1)."'";
					break;
				case "LANGUAGE_ID":
					$arSqlSearch[] = "F.LANGUAGE_ID = '".$DB->ForSql($val,2)."'";
					break;
				case "PRESET_ID":
					$arSqlSearch[] = GetFilterQuery("F.PRESET_ID", $val);
					break;
				case "SORT":
					$arSqlSearch[] = GetFilterQuery("F.SORT", $val);
					break;
				case "SORT_FIELD":
					$arSqlSearch[] = GetFilterQuery("F.SORT_FIELD", $val);
					break;
				}
			}
		}

		$sOrder = "";
		foreach($aSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> "ASC"? "DESC":"ASC");
			switch (strtoupper($key))
			{
				case "ID":		$sOrder .= ", F.ID ".$ord; break;
				case "USER_ID":	$sOrder .= ", F.USER_ID ".$ord; break;
				case "FILTER_ID":	$sOrder .= ", F.FILTER_ID ".$ord; break;
				case "NAME":	$sOrder .= ", F.NAME ".$ord; break;
				case "FIELDS":	$sOrder .= ", F.FIELDS ".$ord; break;
				case "COMMON":	$sOrder .= ", F.COMMON ".$ord; break;
				case "PRESET":	$sOrder .= ", F.PRESET ".$ord; break;
				case "LANGUAGE_ID":	$sOrder .= ", F.LANGUAGE_ID ".$ord; break;
				case "PRESET_ID":	$sOrder .= ", F.PRESET_ID ".$ord; break;
				case "SORT":	$sOrder .= ", F.SORT ".$ord; break;
				case "SORT_FIELD":	$sOrder .= ", F.SORT_FIELD ".$ord; break;
			}
		}
		if (strlen($sOrder)<=0)
			$sOrder = "F.ID ASC";
		$strSqlOrder = " ORDER BY ".TrimEx($sOrder,",");

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch,"noFilterLogic");
		$strSql = "
			SELECT
				F.ID, F.USER_ID, F.NAME, F.FILTER_ID, F.FIELDS, F.COMMON, F.PRESET, F.LANGUAGE_ID, F.PRESET_ID, F.SORT, F.SORT_FIELD
			FROM
				b_filters F
			WHERE
			".$strSqlSearch."
			".$strSqlOrder;

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	private static function Cmp($a, $b)
	{
		if ($a["SORT"] == $b["SORT"])
			return ($a["ID"] < $b["ID"]) ? -1 : 1;

		return ($a["SORT"] < $b["SORT"]) ? -1 : 1;
	}

	public function Begin()
	{
		uasort($this->arItems, "CAdminFilter::Cmp");

		echo '
<div id="adm-filter-tab-wrap-'.$this->id.'" class="adm-filter-wrap'.($this->arOptFlt["styleFolded"]=="Y" ? " adm-filter-folded" : "").'" style = "display: none;">
	<table class="adm-filter-main-table">
		<tr>
			<td class="adm-filter-main-table-cell">
				<div class="adm-filter-tabs-block" id="filter-tabs-'.$this->id.'">
					<span id="adm-filter-tab-'.$this->id.'-0" class="adm-filter-tab adm-filter-tab-active" onclick="'.$this->id.'.SetActiveTab(this); '.$this->id.'.ApplyFilter(\'0\'); " title="'.GetMessage("admin_lib_filter_goto_dfilter").'">'.GetMessage("admin_lib_filter_filter").'</span>';

		if(is_array($this->arItems) && !empty($this->arItems))
		{
			foreach($this->arItems as $filter_id => $filter)
			{
				$name = ($filter["NAME"] <> '' ? $filter["NAME"] : GetMessage("admin_lib_filter_no_name"));
				echo '<span id="adm-filter-tab-'.$this->id.'-'.$filter_id.'" class="adm-filter-tab" onclick="'.$this->id.'.SetActiveTab(this); '.$this->id.'.ApplyFilter(\''.$filter_id.'\');" title="'.GetMessage("admin_lib_filter_goto_filter").": &quot;".htmlspecialcharsbx($name).'&quot;">'.htmlspecialcharsbx($name).'</span>';
			}
		}

			echo '<span id="adm-filter-add-tab-'.$this->id.'" class="adm-filter-tab adm-filter-add-tab" onclick="'.$this->id.'.SaveAs();" title="'.GetMessage("admin_lib_filter_new").'"></span><span onclick="'.$this->id.'.SetFoldedView();" class="adm-filter-switcher-tab"><span id="adm-filter-switcher-tab" class="adm-filter-switcher-tab-icon"></span></span><span class="adm-filter-tabs-block-underlay"></span>
				</div>
			</td>
		</tr>
		<tr>
			<td class="adm-filter-main-table-cell">
				<div class="adm-filter-content" id="'.$this->id.'_content">
					<div class="adm-filter-content-table-wrap">
						<table cellspacing="0" class="adm-filter-content-table" id="'.$this->id.'">';
	}

	public function Buttons($aParams=false)
	{
		$hkInst = CHotKeys::getInstance();

		echo '

						</table>
					</div>
					<div class="adm-filter-bottom-separate" id="'.$this->id.'_bottom_separator"></div>
					<div class="adm-filter-bottom">';

		if($aParams !== false)
		{
			$url = $aParams["url"];
			if(strpos($url, "?")===false)
				$url .= "?";
			else
				$url .= "&";

			if(strpos($url, "lang=")===false)
				$url .= "lang=".LANG;

			if(!$this->url)
				$this->url = $url;

			if(!$this->tableId)
				$this->tableId = $aParams["table_id"];

			if(isset($aParams['report']) && $aParams['report'])
			{
				echo '
						<input type="submit" class="adm-btn" id="'.$this->id.'set_filter" name="set_filter" title="'.GetMessage("admin_lib_filter_set_rep_title").$hkInst->GetTitle("set_filter").'" onclick="'.htmlspecialcharsbx($this->id.'.OnSet(\''.CUtil::AddSlashes($aParams["table_id"]).'\', \''.CUtil::AddSlashes($url).'\', this); return false;').'" value="'.GetMessage("admin_lib_filter_set_rep").'">
						<input type="submit" class="adm-btn" id="'.$this->id.'del_filter" name="del_filter" title="'.GetMessage("admin_lib_filter_clear_butt_title").$hkInst->GetTitle("del_filter").'" onclick="'.htmlspecialcharsbx($this->id.'.OnClear(\''.CUtil::AddSlashes($aParams["table_id"]).'\', \''.CUtil::AddSlashes($url).'\', this); return false;').'" value="'.GetMessage("admin_lib_filter_clear_butt").'">';
			}
			else
				echo '
						<input type="submit" class="adm-btn" id="'.$this->id.'set_filter" name="set_filter" title="'.GetMessage("admin_lib_filter_set_butt").$hkInst->GetTitle("set_filter").'" onclick="'.htmlspecialcharsbx($this->id.'.OnSet(\''.CUtil::AddSlashes($aParams["table_id"]).'\', \''.CUtil::AddSlashes($url).'\', this); return false;').'" value="'.GetMessage("admin_lib_filter_set_butt").'">
						<input type="submit" class="adm-btn" id="'.$this->id.'del_filter" name="del_filter" title="'.GetMessage("admin_lib_filter_clear_butt").$hkInst->GetTitle("del_filter").'" onclick="'.htmlspecialcharsbx($this->id.'.OnClear(\''.CUtil::AddSlashes($aParams["table_id"]).'\', \''.CUtil::AddSlashes($url).'\', this); return false;').'" value="'.GetMessage("admin_lib_filter_clear_butt").'">';

		}
		if($this->popup)
		{

			echo '
						<div class="adm-filter-setting-block">
							<span class="adm-filter-setting" onClick="this.blur();'.$this->id.'.SaveMenuShow(this);return false;" hidefocus="true" title="'.GetMessage("admin_lib_filter_savedel_title").'"></span>
							<span class="adm-filter-add-button" onClick="this.blur();'.$this->id.'.SettMenuShow(this);return false;" hidefocus="true" title="'.GetMessage("admin_lib_filter_more_title").'"></span>
						</div>';
		}
	}

	public function End()
	{

		echo '
					</div>
				</div>
			</td>
		</tr>
	</table>
</div>';

		$sRowIds = $sVisRowsIds = "";


		if(is_array($this->popup))
		{
			foreach($this->popup as $key=>$item)
				if($item !== null)
					$sRowIds .= ($sRowIds <> ""? ",":"").'"'.CUtil::JSEscape($key).'"';

			$aRows = explode(",", $this->arOptFlt["rows"]);

			if(is_array($aRows))
				foreach($aRows as $row)
					if(trim($row) <> "")
						$sVisRowsIds .= ($sVisRowsIds <> ""? ",":"").'"'.CUtil::JSEscape(trim($row)).'":true';
		}

		$this->PrintSaveOptionsDIV();
		$this->GetParamsFromCookie();

		$openedTabUri = false;
		$openedTabSes = $filteredTab = null;

		if(isset($_REQUEST["adm_filter_applied"]) && !empty($_REQUEST["adm_filter_applied"]))
		{
			$openedTabUri = $_REQUEST["adm_filter_applied"];
		}
		else
		{
			$openedTabSes = $_SESSION[self::SESS_PARAMS_NAME][$this->id]["activeTabId"];
			$filteredTab = $_SESSION[self::SESS_PARAMS_NAME][$this->id]["filteredId"];
		}

		echo '
<script type="text/javascript">
		var '.$this->id.' = {};
		BX.ready(function(){
			'.$this->id.' = new BX.AdminFilter("'.$this->id.'", ['.$sRowIds.']);
			'.$this->id.'.state.init = true;
			'.$this->id.'.state.folded = '.($this->arOptFlt["styleFolded"] == "Y" ? "true" : "false").';
			'.$this->id.'.InitFilter({'.$sVisRowsIds.'});
			'.$this->id.'.oOptions = '.CUtil::PhpToJsObject($this->arItems).';
			'.$this->id.'.popupItems = '.CUtil::PhpToJsObject($this->popup).';
			'.$this->id.'.InitFirst();
			'.$this->id.'.url = "'.CUtil::AddSlashes($this->url).'";
			'.$this->id.'.table_id = "'.CUtil::AddSlashes($this->tableId).'";
			'.$this->id.'.presetsDeleted = ['.$this->arOptFlt["presetsDeletedJS"].'];';

			if($filteredTab != null || $openedTabUri != false)
			{
				$tabToInit = ($openedTabUri ? $openedTabUri : $filteredTab);

				echo '
			'.$this->id.'.InitFilteredTab("'.CUtil::JSEscape(htmlspecialcharsbx($tabToInit)).'");';
			}

			if($openedTabSes != null || $openedTabUri != false)
				echo '
			var openedFTab = '.$this->id.'.InitOpenedTab("'.CUtil::JSEscape(htmlspecialcharsbx($openedTabUri)).'", "'.CUtil::JSEscape(htmlspecialcharsbx($openedTabSes)).'");';

			echo '
			'.$this->id.'.state.init = false;
			BX("adm-filter-tab-wrap-'.$this->id.'").style.display = "block";';

			//making filter tabs draggable
			if($this->url)
			{
				$registerUrl = CHTTP::urlDeleteParams($this->url, array("adm_filter_applied", "adm_filter_preset"));

				foreach($this->arItems as $filter_id => $filter)
				{
					$arParamsAdd = array("adm_filter_applied"=>$filter_id);

					if(isset($filter["PRESET_ID"]))
						$arParamsAdd["adm_filter_preset"] = $filter["PRESET_ID"];

					$filterUrl = CHTTP::urlAddParams($registerUrl, $arParamsAdd, array("encode","skip_empty"));

					echo "
					BX.adminMenu.registerItem('adm-filter-tab-".$this->id.'-'.$filter_id."', {URL:'".$filterUrl."', TITLE: true});";
				}
			}

			echo '
			}
		);
</script>';

		$hkInst = CHotKeys::getInstance();
		$Execs = $hkInst->GetCodeByClassName("CAdminFilter");
		echo $hkInst->PrintJSExecs($Execs);
	}


	//experemental
	//extracting filter params from cookie and transfer them to session
	private function GetParamsFromCookie()
	{
		$cookieName = COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_ADM_FLT_PARAMS";
		if(!isset($_COOKIE[$cookieName]) || $_COOKIE[$cookieName] == "")
			return false;

		$aParams = explode(",",$_COOKIE[$cookieName]);
		SetCookie($cookieName,'');

		if(empty($aParams))
			return false;

		$filterId = "";

		foreach ($aParams as $key => $aValue)
		{
			$aParam = explode(":",$aValue);
			unset($aParams[$key]);

			if(!empty($aParam) && $aParam[0] != "filter_id")
				$aParams[$aParam[0]] = $aParam[1];
			elseif($aParam[0] == "filter_id")
				$filterId = $aParam[1];
		}

		if($filterId == "")
			return false;

		foreach ($aParams as $paramName => $value)
			$_SESSION[self::SESS_PARAMS_NAME][$filterId][$paramName] = $value;

		return true;
	}

	//experemental
	private function IsFiltered()
	{
		$fltTable = $_SESSION["SESS_ADMIN"][$this->tableId];

		if(!isset($fltTable) || !is_array($fltTable))
			return false;

		foreach ($fltTable as $value)
			if(!is_null($value))
				return true;

		return false;
	}

	private function PrintSaveOptionsDIV()
	{
		global $USER;
		$isAdmin = $USER->CanDoOperation('edit_other_settings');
		?>
<div style="display:none">
	<div id="filter_save_opts_<?=$this->id?>">
		<table width="100%">
			<tr>
				<td align="right" width="40%"><?=GetMessage("admin_lib_filter_sett_name")?></td>
				<td><input type="text" name="filter_name" value="" size="30" maxlength="255"></td>
			</tr>
			<?if($isAdmin):?>
				<tr>
					<td align="right" width="40%"><?=GetMessage("admin_lib_filter_sett_common")?></td>
					<td><input type="checkbox" name="common" ></td>
				</tr>
			<?endif;?>
		</table>
	</div>
</div>
		<?
	}

	static public function UnEscape($aFilter)
	{
		if(defined("BX_UTF"))
			return;
		if(!is_array($aFilter))
			return;
		foreach($aFilter as $flt)
			if(is_string($GLOBALS[$flt]) && CUtil::DetectUTF8($GLOBALS[$flt]))
				CUtil::decodeURIComponent($GLOBALS[$flt]);
	}
}

/* Context links menu for edit forms */
class CAdminContextMenu
{
	var $items;
	var $additional_items;
	var $bMenuAdded = false;
	var $bRightBarAdded = false;

	public function CAdminContextMenu($items, $additional_items = array())
	{
		//array(
		//	array("NEWBAR"=>true),
		//	array("SEPARATOR"=>true),
		//	array("HTML"=>""),
		//	array("TEXT", "ICON", "TITLE", "LINK", "LINK_PARAM"),
		//	array("TEXT", "ICON", "TITLE", "MENU"=>array(array("SEPARATOR"=>true, "ICON", "TEXT", "TITLE", "ACTION"), ...)),
		//	array("TEXT", "ICON", "TITLE", "ONCLICK", "LINK_PARAM"),
		//	...
		//)
		$this->items = $items;
		$this->additional_items = $additional_items;
	}

	public function Show()
	{
		if (defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
			return;

		$hkInst = CHotKeys::getInstance();

		foreach(GetModuleEvents("main", "OnAdminContextMenuShow", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array(&$this->items));
		}

		$bFirst = true;
		$bNeedSplitClosing = false;
		foreach($this->items as $item)
		{
			if(!empty($item["NEWBAR"]))
				$this->EndBar();

			if($bFirst || !empty($item["NEWBAR"]))
				$this->BeginBar();

			if(!empty($item["NEWBAR"]) || !empty($item['SEPARATOR']))
				continue;

			if ($item['ICON'] != 'btn_list' && !$bNeedSplitClosing)
			{
				$this->BeginRightBar();
				$bNeedSplitClosing = true;
			}

			$this->Button($item, $hkInst);

			$bFirst = false;
		}

		if (count($this->additional_items) > 0)
		{
			if($bFirst)
			{
				$this->BeginBar();
			}

			$this->Additional();
		}

		if ($bNeedSplitClosing)
			$this->EndRightBar();

		$this->EndBar();
	}

	public static function BeginBar()
	{
?>
<div class="adm-detail-toolbar"><span style="position:absolute;"></span>
<?
	}

	static function EndBar()
	{
?>
</div>
<?
	}

	public static function BeginRightBar()
	{
		$id = 'context_right_'.RandString(8);
?>
<script type="text/javascript">BX.ready(function(){
var right_bar = BX('<?=$id?>');
BX.Fix(right_bar, {type: 'right', limit_node: BX.previousSibling(right_bar)});
})</script>
<div class="adm-detail-toolbar-right" id="<?=$id?>">
<?
	}

	function EndRightBar()
	{
?>
</div>
<?
	}

	function GetClassByID($icon_id)
	{
		switch ($icon_id)
		{
			case 'btn_new':
				return 'adm-btn-add';
			case 'btn_copy':
				return 'adm-btn-copy';
			case 'btn_delete':
				return 'adm-btn-delete';
			case 'btn_desktop_gadgets':
				return 'adm-btn-desktop-gadgets';
			case 'btn_desktop_settings':
				return 'adm-btn-desktop-settings';
			case 'btn_active':
				return 'adm-btn-active';
			case 'btn_green':
				return 'adm-btn-green';
		}

		return '';
	}

	function GetActiveClassByID($icon_id)
	{
		return 'adm-btn-active';
	}

	/**
	 * @param array $item
	 * @param CHotKeys $hkInst
	 */
	function Button($item, $hkInst)
	{
		// $item["ICON"]
		if(isset($item["HTML"]) && $item["HTML"] <> "")
		{
			echo '<span class="adm-list-table-top-wrapper">'.$item['HTML'].'</span>';
		}
		elseif(!empty($item["MENU"]))
		{
			$sMenuUrl = "BX.adminShowMenu(this, ".htmlspecialcharsbx(CAdminPopup::PhpToJavaScript($item["MENU"])).", {active_class: '".$this->GetActiveClassByID($item["ICON"])."'});";
			$sClassName = $this->GetClassByID($item["ICON"]);
?>
	<a href="javascript:void(0)" hidefocus="true" onclick="this.blur();<?=$sMenuUrl?> return false;" class="adm-btn<?=$sClassName != '' ? ' '.$sClassName : ''?> adm-btn-menu" title="<?=$item["TITLE"];?>"><?=$item["TEXT"]?></a>
<?
		}
		else
		{
			if ($item['ICON'] == 'btn_list'/* || $item['ICON'] == 'btn_up'*/):
?>
	<a href="<?=($item["ONCLICK"] <> ''? 'javascript:void(0)' : htmlspecialcharsbx(htmlspecialcharsback($item["LINK"])))?>" <?=$item["LINK_PARAM"]?> class="adm-detail-toolbar-btn" title="<?=$item["TITLE"].$hkInst->GetTitle($item["ICON"])?>"<?=($item["ONCLICK"] <> ''? ' onclick="'.htmlspecialcharsbx($item["ONCLICK"]).'"':'')?><?=(!empty($item["ICON"])? ' id="'.$item["ICON"].'"':'')?>><span class="adm-detail-toolbar-btn-l"></span><span class="adm-detail-toolbar-btn-text"><?=$item["TEXT"]?></span><span class="adm-detail-toolbar-btn-r"></span></a>
<?
			else:
				$sClassName = $this->GetClassByID($item["ICON"]);
?>
	<a href="<?=($item["ONCLICK"] <> ''? 'javascript:void(0)' : htmlspecialcharsbx(htmlspecialcharsback($item["LINK"])))?>" <?=$item["LINK_PARAM"]?> class="adm-btn<?=$sClassName != '' ? ' '.$sClassName : ''?>" title="<?=$item["TITLE"].$hkInst->GetTitle($item["ICON"])?>"<?=($item["ONCLICK"] <> ''? ' onclick="'.htmlspecialcharsbx($item["ONCLICK"]).'"':'')?><?=(!empty($item["ICON"])? ' id="'.$item["ICON"].'"':'')?>><?=$item["TEXT"]?></a>

<?
			endif;

			$arExecs = $hkInst->GetCodeByClassName($item["ICON"]);
			echo $hkInst->PrintJSExecs($arExecs, "", true, true);
		}
	}

	function Additional()
	{
		$sMenuUrl = "BX.adminList.ShowMenu(this, ".htmlspecialcharsbx(CAdminPopup::PhpToJavaScript($this->additional_items)).");";
?>
	<div class="adm-small-button adm-table-setting" title="<?=htmlspecialcharsbx(GetMessage('admin_lib_context_sett_title'))?>" onclick="this.blur();<?=$sMenuUrl?> return false;" ></div>
<?
	}
}

/* Context links menu for lists */
class CAdminContextMenuList extends CAdminContextMenu
{
	public static function BeginBar()
	{
?>
<div class="adm-list-table-top">
<?
	}

	public static function GetClassByID($icon_id)
	{
		if (substr($icon_id, 0, 7) == 'btn_new')
			return 'adm-btn-save adm-btn-add';
		else
			return parent::GetClassByID($icon_id);
	}

	public static function GetActiveClassByID($icon_id)
	{
		if (substr($icon_id, 0, 7) == 'btn_new')
			return 'adm-btn-save-active';
		else
			return parent::GetActiveClassByID($icon_id);
	}

	public static function Button($item, $hkInst)
	{
		if (isset($item['ICON']) && $item['ICON'] == 'btn_list')
			$item['ICON'] = '';

		parent::Button($item, $hkInst);
	}

	function BeginRightBar() {}
	public static function EndRightBar() {}
}

/* Sorting in lists */
class CAdminSorting
{
	var $by_name;
	var $ord_name;
	var $table_id;
	var $by_initial;
	var $order_initial;

	public function CAdminSorting($table_id, $by_initial=false, $order_initial=false, $by_name="by", $ord_name="order")
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$this->by_name = $by_name;
		$this->ord_name = $ord_name;
		$this->table_id = $table_id;
		$this->by_initial = $by_initial;
		$this->order_initial = $order_initial;

		$uniq = md5($APPLICATION->GetCurPage());

		$aOptSort = array();
		if(isset($GLOBALS[$this->by_name]))
			$_SESSION["SESS_SORT_BY"][$uniq] = $GLOBALS[$this->by_name];
		elseif(isset($_SESSION["SESS_SORT_BY"][$uniq]))
			$GLOBALS[$this->by_name] = $_SESSION["SESS_SORT_BY"][$uniq];
		else
		{
			$aOptSort = CUserOptions::GetOption("list", $this->table_id, array("by"=>$by_initial, "order"=>$order_initial));
			if(!empty($aOptSort["by"]))
				$GLOBALS[$this->by_name] = $aOptSort["by"];
			elseif($by_initial !== false)
				$GLOBALS[$this->by_name] = $by_initial;
		}

		if(isset($GLOBALS[$this->ord_name]))
			$_SESSION["SESS_SORT_ORDER"][$uniq] = $GLOBALS[$this->ord_name];
		elseif(isset($_SESSION["SESS_SORT_ORDER"][$uniq]))
			$GLOBALS[$this->ord_name] = $_SESSION["SESS_SORT_ORDER"][$uniq];
		else
		{
			if(empty($aOptSort["order"]))
				$aOptSort = CUserOptions::GetOption("list", $this->table_id, array("order"=>$order_initial));
			if(!empty($aOptSort["order"]))
				$GLOBALS[$this->ord_name] = $aOptSort["order"];
			elseif($order_initial !== false)
				$GLOBALS[$this->ord_name] = $order_initial;
		}
	}

	public function Show($text, $sort_by, $alt_title = false, $baseCssClass = "")
	{
		$ord = "asc";
		$class = "";
		$title = GetMessage("admin_lib_sort_title")." ".($alt_title?$alt_title:$text);
		//echo $this->by_name.'/'.strtolower($GLOBALS[$this->by_name]) .'=='. strtolower($sort_by).'<br>';
		if(strtolower($GLOBALS[$this->by_name]) == strtolower($sort_by))
		{
			if(strtolower($GLOBALS[$this->ord_name]) == "desc")
			{
				$class = "-down";
				$title .= " ".GetMessage("admin_lib_sort_down");
			}
			else
			{
				$class = "-up";
				$title .= " ".GetMessage("admin_lib_sort_up");
				$ord = "desc";
			}
		}

		$path = $_SERVER["REQUEST_URI"];
		$sep = "?";
		if($_SERVER["QUERY_STRING"] <> "")
		{
			$path = preg_replace("/([?&])".$this->by_name."=[^&]*[&]*/i", "\\1", $path);
			$path = preg_replace("/([?&])".$this->ord_name."=[^&]*[&]*/i", "\\1", $path);
			$path = preg_replace("/([?&])mode=[^&]*[&]*/i", "\\1", $path);
			$path = preg_replace("/([?&])table_id=[^&]*[&]*/i", "\\1", $path);
			$path = preg_replace("/([?&])action=[^&]*[&]*/i", "\\1", $path);
			$sep = "&";
		}
		if(($last = substr($path, -1, 1)) == "?" || $last == "&")
			$sep = "";

		$url = $path.$sep.$this->by_name."=".$sort_by."&".$this->ord_name."=".($class <> ""? $ord:"");

		return 'class="'.$baseCssClass.' adm-list-table-cell-sort'.$class.'" onclick="'.$this->table_id.'.Sort(\''.htmlspecialcharsbx(CUtil::addslashes($url)).'\', '.($class <> ""? "false" : "true").', arguments);" title="'.$title.'"';
	}
}

/* Navigation */
/*
Important Notice:
	CIBlockResult has copy of the methods of this class
	because we need CIBlockResult::Fetch method to be called on iblock_element_admin.php page.
	So this page based on CIBlockResult not on CAdminResult!
*/
class CAdminResult extends CDBResult
{
	var $nInitialSize;
	var $table_id;

	public function CAdminResult($res, $table_id)
	{
		parent::CDBResult($res);
		$this->table_id = $table_id;
	}

	public function NavStart($nPageSize=20, $bShowAll=true, $iNumPage=false)
	{
		$nSize = CAdminResult::GetNavSize($this->table_id, $nPageSize);

		if(!is_array($nPageSize))
			$nPageSize = array();

		$nPageSize["nPageSize"] = $nSize;
		if($_REQUEST["mode"] == "excel")
			$nPageSize["NavShowAll"] = true;

		$this->nInitialSize = $nPageSize["nPageSize"];

		parent::NavStart($nPageSize, $bShowAll, $iNumPage);
	}

	public static function GetNavSize($table_id=false, $nPageSize=20)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$bSess = (CPageOption::GetOptionString("main", "nav_page_in_session", "Y")=="Y");
		if(is_array($nPageSize))
			$sNavID = $nPageSize["sNavID"];
		$unique = md5((isset($sNavID)? $sNavID : $APPLICATION->GetCurPage()));

		if(isset($_REQUEST["SIZEN_".($GLOBALS["NavNum"]+1)]))
		{
			$nSize = intval($_REQUEST["SIZEN_".($GLOBALS["NavNum"]+1)]);
			if($bSess)
				$_SESSION["NAV_PAGE_SIZE"][$unique] = $nSize;
		}
		elseif($bSess && isset($_SESSION["NAV_PAGE_SIZE"][$unique]))
		{
			$nSize = $_SESSION["NAV_PAGE_SIZE"][$unique];
		}
		else
		{
			$aOptions = array();
			if($table_id)
				$aOptions = CUserOptions::GetOption("list", $table_id);
			if(intval($aOptions["page_size"]) > 0)
				$nSize = intval($aOptions["page_size"]);
			else
				$nSize = (is_array($nPageSize)? $nPageSize["nPageSize"]:$nPageSize);
		}
		return $nSize;
	}

	public static function GetNavPrint($title, $show_allways=true, $StyleText="", $template_path=false, $arDeleteParam=false)
	{
		if($template_path === false)
			$template_path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/interface/navigation.php";
		return parent::GetNavPrint($title, $show_allways, $StyleText, $template_path, array('action', 'sessid'));
	}
}

/*Tab Control*/
class CAdminTabControl
{
	var $name, $unique_name;
	var $tabs;
	var $selectedTab;
	var $tabIndex = 0;
	var $bButtons = false;
	var $bCanExpand;
	var $bPublicModeBuffer = false;
	var $bShowSettings = false;
	var $publicModeBuffer_id;

	/** @var CAdminTabEngine */
	var $customTabber;

	var $bPublicMode = false;
	var $publicObject = 'BX.WindowManager.Get()';

	var $AUTOSAVE = null;

	public function CAdminTabControl($name, $tabs, $bCanExpand = true, $bDenyAutoSave = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		//array(array("DIV"=>"", "TAB"=>"", "ICON"=>, "TITLE"=>"", "ONSELECT"=>"javascript"), ...)
		$this->tabs = $tabs;
		$this->name = $name;
		$this->unique_name = $name."_".md5($APPLICATION->GetCurPage());

		$this->bPublicMode = defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1;
		$this->bCanExpand = !$this->bPublicMode && (bool)$bCanExpand;

		$this->SetSelectedTab();

		if (!$bDenyAutoSave && CAutoSave::Allowed())
		{
			$this->AUTOSAVE = new CAutoSave();
		}
	}

	public function SetPublicMode($jsObject = false)
	{
		$this->bPublicMode = true;
		$this->bShowSettings = false;
		$this->bCanExpand = false;
		$this->bPublicModeBuffer = true;

		if ($jsObject)
			$this->publicObject = $jsObject;
	}

	/**
	 * @param CAdminTabEngine $customTabber
	 */
	public function AddTabs(&$customTabber)
	{
		$this->customTabber = $customTabber;

		$arCustomTabs = $this->customTabber->GetTabs();
		if ($arCustomTabs && is_array($arCustomTabs))
		{
			$arTabs = array();
			$i = 0;
			foreach ($this->tabs as $value)
			{
				foreach ($arCustomTabs as $key1 => $value1)
				{
					if (array_key_exists("SORT", $value1) && IntVal($value1["SORT"]) == $i)
					{
						$arTabs[] = array_merge($value1, array("CUSTOM" => "Y"));
						unset($arCustomTabs[$key1]);
					}
				}

				$arTabs[] = $value;
				$i++;
			}

			foreach ($arCustomTabs as $value1)
				$arTabs[] = array_merge($value1, array("CUSTOM" => "Y"));

			$this->tabs = $arTabs;
		}
	}

	public static function OnAdminTabControlBegin()
	{
		foreach(GetModuleEvents("main", "OnAdminTabControlBegin", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$this));
	}

	public function SetSelectedTab()
	{
		$this->selectedTab = $this->tabs[0]["DIV"];
		if(isset($_REQUEST[$this->name."_active_tab"]))
		{
			foreach($this->tabs as $i => $tab)
			{
				if($tab["DIV"] == $_REQUEST[$this->name."_active_tab"])
				{
					$this->selectedTab = $_REQUEST[$this->name."_active_tab"];
					break;
				}
			}
		}
	}

	public function Begin()
	{
		$hkInst = CHotKeys::getInstance();

		$this->OnAdminTabControlBegin();
		$this->tabIndex = 0;

		$this->SetSelectedTab();

		if (!$this->bPublicMode)
		{
?>
<div class="adm-detail-block" id="<?=$this->name?>_layout">
	<div class="adm-detail-tabs-block<?=$this->bShowSettings?' adm-detail-tabs-block-settings':''?>" id="<?=$this->name?>_tabs">
<?
		}

		$len = count($this->tabs);
		$tabs_html = '';
		foreach($this->tabs as $key => $tab)
		{
			$bSelected = ($tab["DIV"] == $this->selectedTab);
			$tabs_html .= '<span title="'.$tab["TITLE"].$hkInst->GetTitle("tab-container").'" '.
				'id="tab_cont_'.$tab["DIV"].'" '.
				'class="adm-detail-tab'.($bSelected ? ' adm-detail-tab-active':'').($key==$len-1? ' adm-detail-tab-last':'').'" '.
				'onclick="'.$this->name.'.SelectTab(\''.$tab["DIV"].'\');">'.htmlspecialcharsex($tab["TAB"]).'</span>';
		}

		$tabs_html .= $this->ShowTabButtons();

		if (!$this->bPublicMode)
		{
			echo $tabs_html;
?>
	</div>
	<div class="adm-detail-content-wrap">
<?
		}
		else
		{
			echo '
<script type="text/javascript">
'.$this->publicObject.'.SetHead(\''.CUtil::JSEscape($tabs_html).'\');
';
			if ($this->AUTOSAVE)
			{
				echo '
'.$this->publicObject.'.setAutosave();
';
			}
			echo '
</script>
';
			if ($this->bPublicModeBuffer)
			{
				$this->publicModeBuffer_id = 'bx_tab_control_'.RandString(6);
				echo '<div id="'.$this->publicModeBuffer_id.'" style="display: none;">';
			}
		}
	}

	public function ShowTabButtons()
	{
		$s = '';
		if (!$this->bPublicMode)
		{
			if(count($this->tabs) > 1 && $this->bCanExpand/* || $this->AUTOSAVE*/)
			{
				$s .= '<div class="adm-detail-title-setting" onclick="'.$this->name.'.ToggleTabs();" title="'.GetMessage("admin_lib_expand_tabs").'" id="'.$this->name.'_expand_link"><span class="adm-detail-title-setting-btn adm-detail-title-expand"></span></div>';
			}
		}
		return $s;
	}

	public function BeginNextTab()
	{
		if ($this->AUTOSAVE)
			$this->AUTOSAVE->Init();

		//end previous tab
		$this->EndTab();

		if($this->tabIndex >= count($this->tabs))
			return;

		$css = '';
		if ($this->tabs[$this->tabIndex]["DIV"] <> $this->selectedTab)
			$css .= 'display:none; ';

		echo '
<div class="adm-detail-content" id="'.$this->tabs[$this->tabIndex]["DIV"].'"'.($css != '' ? ' style="'.$css.'"' : '').'>';

		/*if($this->tabs[$this->tabIndex]["ICON"] <> "")
			echo '
		<td class="icon"><div id="'.$this->tabs[$this->tabIndex]["ICON"].'"></div></td>
';*/
		echo '
	<div class="adm-detail-title">'.$this->tabs[$this->tabIndex]["TITLE"].'</div>';

echo '
	<div class="adm-detail-content-item-block">
		<table class="adm-detail-content-table edit-table" id="'.$this->tabs[$this->tabIndex]["DIV"].'_edit_table">
			<tbody>
';
		if(array_key_exists("CUSTOM", $this->tabs[$this->tabIndex]) && $this->tabs[$this->tabIndex]["CUSTOM"] == "Y")
		{
			$this->customTabber->ShowTab($this->tabs[$this->tabIndex]["DIV"]);
			$this->tabIndex++;
			$this->BeginNextTab();
		}
		elseif(array_key_exists("CONTENT", $this->tabs[$this->tabIndex]))
		{
			echo $this->tabs[$this->tabIndex]["CONTENT"];
			$this->tabIndex++;
			$this->BeginNextTab();
		}
		else
		{
			$this->tabIndex++;
		}
	}

	public function EndTab()
	{
		if(
			$this->tabIndex < 1
			|| $this->tabIndex > count($this->tabs)
			|| $this->tabs[$this->tabIndex-1]["_closed"] === true
		)
		{
			return;
		}

		echo '
			</tbody>
		</table>
	</div>
</div>
';

		$this->tabs[$this->tabIndex-1]["_closed"] = true;
	}

	public function Buttons($aParams=false)
	{
		$hkInst = CHotKeys::getInstance();

		while($this->tabIndex < count($this->tabs))
			$this->BeginNextTab();

		$this->bButtons = true;

		//end previous tab
		$this->EndTab();

		if (!$this->bPublicMode)
		{
			echo '<div class="adm-detail-content-btns-wrap" id="'.$this->name.'_buttons_div"><div class="adm-detail-content-btns">';
		}

		if ($_REQUEST['subdialog'])
		{
			echo '<input type="hidden" name="suffix" value="'.substr($GLOBALS['obJSPopup']->suffix, 1).'" />';
			echo '<input type="hidden" name="subdialog" value="Y" />';
		}

		if($aParams !== false)
		{
			if ($this->bPublicMode)
			{
				if (strlen($_REQUEST['from_module']))
				{
					echo '<input type="hidden" name="from_module" value="'.htmlspecialcharsbx($_REQUEST['from_module']).'" />';
				}

				if(is_array($aParams['buttons']))
				{
					echo '
<input type="hidden" name="bxpublic" value="Y" />
<script type="text/javascript">'.$this->publicObject.'.SetButtons('.CUtil::PhpToJsObject($aParams['buttons']).');</script>
';
				}
				else
				{
					echo '
<input type="hidden" name="bxpublic" value="Y" /><input type="hidden" name="save" value="Y" />
<script type="text/javascript">'.$this->publicObject.'.SetButtons(['.$this->publicObject.'.btnSave, '.$this->publicObject.'.btnCancel]);</script>
';
				}
			}
			else
			{
				if($aParams["btnSave"] !== false)
				{
					echo '<input'.($aParams["disabled"] === true? " disabled":"").' type="submit" name="save" value="'.GetMessage("admin_lib_edit_save").'" title="'.GetMessage("admin_lib_edit_save_title").$hkInst->GetTitle("Edit_Save_Button").'" class="adm-btn-save" />';
					echo $hkInst->PrintJSExecs($hkInst->GetCodeByClassName("Edit_Save_Button"));
				}
				if($aParams["btnApply"] !== false)
				{
					echo '<input'.($aParams["disabled"] === true? " disabled":"").' type="submit" name="apply" value="'.GetMessage("admin_lib_edit_apply").'" title="'.GetMessage("admin_lib_edit_apply_title").$hkInst->GetTitle("Edit_Apply_Button").'" />';
					echo $hkInst->PrintJSExecs($hkInst->GetCodeByClassName("Edit_Apply_Button"));
				}
				if($aParams["btnCancel"] !== false && $aParams["back_url"] <> '' && !preg_match('/(javascript|data)[\s\0-\13]*:/i', $aParams["back_url"]))
				{
					echo '<input type="button" value="'.GetMessage("admin_lib_edit_cancel").'" name="cancel" onClick="window.location=\''.htmlspecialcharsbx(CUtil::addslashes($aParams["back_url"])).'\'" title="'.GetMessage("admin_lib_edit_cancel_title").$hkInst->GetTitle("Edit_Cancel_Button").'" />';
					echo $hkInst->PrintJSExecs($hkInst->GetCodeByClassName("Edit_Cancel_Button"));
				}
				if($aParams["btnSaveAndAdd"] === true)
				{
					echo '<input'.($aParams["disabled"] === true? " disabled":"").' type="submit" name="save_and_add" value="'.GetMessage("admin_lib_edit_save_and_add").'" title="'.GetMessage("admin_lib_edit_save_and_add_title").$hkInst->GetTitle("Edit_Save_And_Add_Button").'" class="adm-btn-add" />';
					echo $hkInst->PrintJSExecs($hkInst->GetCodeByClassName("Edit_Save_And_Add_Button"));
				}
			}
		}
	}

	public function ButtonsPublic($arJSButtons = false)
	{
		while ($this->tabIndex < count($this->tabs))
			$this->BeginNextTab();

		$this->bButtons = true;
		$this->EndTab();

		if ($this->bPublicMode)
		{
			if (strlen($_REQUEST['from_module']))
				echo '<input type="hidden" name="from_module" value="'.htmlspecialcharsbx($_REQUEST['from_module']).'" />';

			if ($arJSButtons === false)
			{
				echo '
<input type="hidden" name="bxpublic" value="Y" /><input type="hidden" name="save" value="Y" />
<script type="text/javascript">'.$this->publicObject.'.SetButtons(['.$this->publicObject.'.btnSave, '.$this->publicObject.'.btnCancel]);</script>
';
			}
			elseif (is_array($arJSButtons))
			{
				$arJSButtons = array_values($arJSButtons);
				echo '
<input type="hidden" name="bxpublic" value="Y" />
<script type="text/javascript">'.$this->publicObject.'.SetButtons([
';
				foreach ($arJSButtons as $key => $btn)
				{
					if (substr($btn, 0, 1) == '.')
						$btn = $this->publicObject.$btn;
					echo $key ? ',' : '', $btn, "\r\n"; // NO JSESCAPE HERE! string must contain valid js object
				}
				echo '
]);</script>
';
			}
		}
	}

	public function End()
	{
		$hkInst = CHotKeys::getInstance();

		if(!$this->bButtons)
		{
			while ($this->tabIndex < count($this->tabs))
				$this->BeginNextTab();

			//end previous tab
			$this->EndTab();
			if (!$this->bPublicMode)
				echo '<div class="adm-detail-content-btns-wrap"><div class="adm-detail-content-btns adm-detail-content-btns-empty"></div></div>';
		}
		elseif (!$this->bPublicMode)
		{
			echo '</div></div>';
		}

		if (!$this->bPublicMode)
		{
			echo '
</div></div>
';
		}

		$Execs = $hkInst->GetCodeByClassName("CAdminTabControl");
		echo $hkInst->PrintJSExecs($Execs, $this->name);

		echo '

<input type="hidden" id="'.$this->name.'_active_tab" name="'.$this->name.'_active_tab" value="'.htmlspecialcharsbx($this->selectedTab).'">

<script type="text/javascript">';
		$s = "";
		foreach($this->tabs as $tab)
		{
			$s .= ($s <> ""? ", ":"").
			"{".
			"'DIV': '".$tab["DIV"]."' ".
			($tab["ONSELECT"] <> ""? ", 'ONSELECT': '".CUtil::JSEscape($tab["ONSELECT"])."'":"").
			"}";
		}
		echo '
if (!window.'.$this->name.' || !BX.is_subclass_of(window.'.$this->name.', BX.adminTabControl))
	window.'.$this->name.' = new BX.adminTabControl("'.$this->name.'", "'.$this->unique_name.'", ['.$s.']);
else if(!!window.'.$this->name.')
	window.'.$this->name.'.PreInit(true);
';

		if (!$this->bPublicMode)
		{
			$aEditOpt = CUserOptions::GetOption("edit", $this->unique_name, array());
			$aTabOpt = CUserOptions::GetOption("edit", 'admin_tabs', array());

			if($this->bCanExpand && count($this->tabs) > 1)
			{
				if($aEditOpt["expand"] == "on")
				{
					echo '
'.$this->name.'.ToggleTabs();';
				}
			}

			if ($aTabOpt["fix_top"] == "off" && $aEditOpt["expand"] != "on")
			{
				echo '
'.$this->name.'.ToggleFix(\'top\');';
			}

			if ($aTabOpt["fix_bottom"] == "off")
			{
				echo '
'.$this->name.'.ToggleFix(\'bottom\');';
			}
		}
		else
		{
			echo 'window.'.$this->name.'.setPublicMode(true); ';
		}
echo '
</script>
';
		if ($this->bPublicModeBuffer)
		{
			echo '</div>';
			echo '<script type="text/javascript">BX.ready(function() {'.$this->publicObject.'.SwapContent(\''.$this->publicModeBuffer_id.'\');});</script>';
		}
	}

	public function ActiveTabParam()
	{
		return $this->name."_active_tab=".urlencode($this->selectedTab);
	}

	// this method is temporarily disabled!
	//string, CAdminException, array("id"=>"name", ...)
	public function ShowWarnings($form, $messages, $aFields=false)
	{
/*
		if(!$messages)
			return;
		$aMess = $messages->GetMessages();
		if(empty($aMess) || !is_array($aMess))
			return;
		$s = "";
		foreach($aMess as $msg)
		{
			$field_name = (is_array($aFields)? $aFields[$msg["id"]] : $msg["id"]);
			if(empty($field_name))
				continue;
			$s .= ($s <> ""? ", ":"")."{'name':'".CUtil::JSEscape($field_name)."', 'title':'".CUtil::JSEscape(htmlspecialcharsback($msg["text"]))."'}";
		}
		echo '
<script>
'.$this->name.'.ShowWarnings("'.CUtil::JSEscape($form).'", ['.$s.']);
</script>
';
*/
	}
}

/*View Tab Control*/
class CAdminViewTabControl
{
	var $name;
	var $tabs;
	var $selectedTab;
	var $tabIndex = 0;

	public function CAdminViewTabControl($name, $tabs)
	{
		//array(array("DIV"=>"", "TAB"=>"", "ICON"=>, "TITLE"=>"", "ONSELECT"=>"javascript"), ...)
		$this->tabs = $tabs;
		$this->name = $name;
		if(isset($_REQUEST[$this->name."_active_tab"]))
			$this->selectedTab = $_REQUEST[$this->name."_active_tab"];
		else
			$this->selectedTab = $tabs[0]["DIV"];
	}

	public function Begin()
	{
		echo '
<div class="adm-detail-subtabs-block">
';
		$i = 0;
		foreach($this->tabs as $tab)
		{
			$bSelected = ($tab["DIV"] == $this->selectedTab);
			echo '<span class="adm-detail-subtabs'.($bSelected? " adm-detail-subtab-active":"").'" id="view_tab_'.$tab["DIV"].'" onclick="'.$this->name.'.SelectTab(\''.$tab["DIV"].'\');" title="'.$tab["TITLE"].'">'.$tab["TAB"].'</span>'."\n";
			$i++;
		}
echo '</div>';
	}

	public function BeginNextTab()
	{
		//end previous tab
		$this->EndTab();

		if($this->tabIndex >= count($this->tabs))
			return;

		echo '
<div id="'.$this->tabs[$this->tabIndex]["DIV"].'"'.($this->tabs[$this->tabIndex]["DIV"] <> $this->selectedTab? ' style="display:none;"':'').'>
	<div class="adm-detail-content-item-block-view-tab">
	<div class="adm-detail-title-view-tab">'.$this->tabs[$this->tabIndex]["TITLE"].'</div>
';
		$this->tabIndex++;
	}

	public function EndTab()
	{
		if($this->tabIndex < 1 || $this->tabIndex > count($this->tabs))
			return;
		echo '
	</div>
</div>
';
	}

	public function End()
	{
		$this->EndTab();
		echo '
<script type="text/javascript">
';
		$s = "";
		foreach($this->tabs as $tab)
		{
			$s .= ($s <> ""? ", ":"").
			"{".
			"'DIV': '".$tab["DIV"]."' ".
			($tab["ONSELECT"] <> ""? ", 'ONSELECT': '".CUtil::JSEscape($tab["ONSELECT"])."'":"").
			"}";
		}
		echo 'var '.$this->name.' = new BX.adminViewTabControl(['.$s.']); ';

		if(defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1)
		{
			echo 'window.'.$this->name.'.setPublicMode(true); ';
		}

		echo '</script>';
	}
}

class CAdminList
{
	var $table_id;
	/** @var CAdminSorting */
	var $sort;
	var $aHeaders = array();
	var $aVisibleHeaders = array();
	/** @var CAdminListRow[] */
	var $aRows = array();
	var $aHeader = array();
	var $arVisibleColumns = array();
	var $aFooter = array();
	var $sNavText = '';
	var $arFilterErrors = Array();
	var $arUpdateErrors = array();
	var $arUpdateErrorIDs = Array();
	var $arGroupErrors = array();
	var $arGroupErrorIDs = Array();
	var $arActionSuccess = array();
	var $bEditMode = false;
	var $bMultipart = false;
	var $bCanBeEdited = false;
	var $bCanBeDeleted = false;
	var $arActions = Array();
	var $arActionsParams = Array();
	/** @var CAdminContextMenuList */
	var $context = false;
	var $sContent = false, $sPrologContent = '', $sEpilogContent = '';
	var $bShowActions;
	var $onLoadScript;
	var $arEditedRows;

	public function CAdminList($table_id, $sort=false)
	{
		$this->table_id = $table_id;
		$this->sort = $sort;
	}

	//id, name, content, sort, default
	public function AddHeaders($aParams)
	{
		if (isset($_REQUEST['showallcol']) && $_REQUEST['showallcol'])
			$_SESSION['SHALL'] = ($_REQUEST['showallcol'] == 'Y');

		$aOptions = CUserOptions::GetOption("list", $this->table_id, array());

		$aColsTmp = explode(",", $aOptions["columns"]);
		$aCols = array();
		foreach ($aColsTmp as $col)
		{
			$col = trim($col);
			if ($col <> "")
				$aCols[] = $col;
		}

		$bEmptyCols = empty($aCols);
		foreach ($aParams as $param)
		{
			$param["__sort"] = -1;
			$this->aHeaders[$param["id"]] = $param;
			if (
				(isset($_SESSION['SHALL']) && $_SESSION['SHALL'])
				|| ($bEmptyCols && $param["default"] == true)
				|| in_array($param["id"], $aCols)
			)
			{
				$this->arVisibleColumns[] = $param["id"];
			}
		}

		$aAllCols = null;
		if (isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "settings")
			$aAllCols = $this->aHeaders;

		if (!$bEmptyCols)
		{
			foreach ($aCols as $i => $col)
				if (isset($this->aHeaders[$col]))
					$this->aHeaders[$col]["__sort"] = $i;

			uasort($this->aHeaders, create_function('$a, $b', 'if($a["__sort"] == $b["__sort"]) return 0; return ($a["__sort"] < $b["__sort"])? -1 : 1;'));
		}

		foreach($this->aHeaders as $id=>$arHeader)
		{
			if(in_array($id, $this->arVisibleColumns))
				$this->aVisibleHeaders[$id] = $arHeader;
		}

		if (isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "settings")
			$this->ShowSettings($aAllCols, $aCols, $aOptions);
	}

	public static function ShowSettings($aAllCols, $aCols, $aOptions)
	{
		global $USER;

		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
		require($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/interface/settings_admin_list.php");
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
		die();
	}

	public function AddVisibleHeaderColumn($id)
	{
		if (isset($this->aHeaders[$id]) && !isset($this->aVisibleHeaders[$id]))
		{
			$this->arVisibleColumns[] = $id;
			$this->aVisibleHeaders[$id] = $this->aHeaders[$id];
		}
	}

	public function GetVisibleHeaderColumns()
	{
		return $this->arVisibleColumns;
	}

	public function AddAdminContextMenu($aContext=array(), $bShowExcel=true, $bShowSettings=true)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$aAdditionalMenu = array();

		if($bShowSettings)
		{
			$link = DeleteParam(array("mode"));
			$link = $APPLICATION->GetCurPage()."?mode=settings".($link <> ""? "&".$link:"");
			$aAdditionalMenu[] = array(
				"TEXT"=>GetMessage("admin_lib_context_sett"),
				"TITLE"=>GetMessage("admin_lib_context_sett_title"),
				"ONCLICK"=>$this->table_id.".ShowSettings('".CUtil::JSEscape($link)."')",
				"GLOBAL_ICON"=>"adm-menu-setting",
			);
		}

		if($bShowExcel)
		{
			$link = DeleteParam(array("mode"));
			$link = $APPLICATION->GetCurPage()."?mode=excel".($link <> ""? "&".$link:"");
			$aAdditionalMenu[] = array(
				"TEXT"=>"Excel",
				"TITLE"=>GetMessage("admin_lib_excel"),
				//"LINK"=>htmlspecialcharsbx($link),
				"ONCLICK"=>"location.href='".htmlspecialcharsbx($link)."'",
				"GLOBAL_ICON"=>"adm-menu-excel",
			);
		}

		if(count($aContext)>0 || count($aAdditionalMenu) > 0)
			$this->context = new CAdminContextMenuList($aContext, $aAdditionalMenu);
	}

	public static function IsUpdated($ID)
	{
		$f = $_REQUEST['FIELDS'][$ID];
		$f_old = $_REQUEST['FIELDS_OLD'][$ID];

		if(!is_array($f) || !is_array($f_old))
			return true;

		foreach($f as $k=>$v)
		{
			if(is_array($v))
			{
				if(!is_array($f_old[$k]))
					return true;
				else
				{
					foreach($v as $k2 => $v2)
					{
						if($f_old[$k][$k2] != $v2)
							return true;
						unset($f_old[$k][$k2]);
					}
					if(count($f_old[$k]) > 0)
						return true;
				}
			}
			else
			{
				if(is_array($f_old[$k]))
					return true;
				elseif($f_old[$k] != $v)
					return true;
			}
			unset($f_old[$k]);
		}
		if(count($f_old) > 0)
			return true;

		return false;
	}

	public static function EditAction()
	{
		if($_SERVER['REQUEST_METHOD']=='POST' && isset($_REQUEST['save'])  && check_bitrix_sessid())
		{
			$arrays = array(&$_POST, &$_REQUEST, &$GLOBALS);
			foreach($arrays as $i => $array)
			{
				if(is_array($array["FIELDS"]))
				{
					foreach($array["FIELDS"] as $id=>$fields)
					{
						if(is_array($fields))
						{
							$keys = array_keys($fields);
							foreach($keys as $key)
							{
								if(($c = substr($key, 0, 1)) == '~' || $c == '=')
								{
									unset($arrays[$i]["FIELDS"][$id][$key]);
								}
							}
						}
					}
				}
			}
			return true;
		}
		return false;
	}

	public function GroupAction()
	{
		//AddMessage2Log("GroupAction");
		if(!empty($_REQUEST['action_button']))
			$_REQUEST['action'] = $_REQUEST['action_button'];

		if(!isset($_REQUEST['action']) || !check_bitrix_sessid())
			return false;

		//AddMessage2Log("GroupAction = ".$_REQUEST['action']." & ".($this->bCanBeEdited?'bCanBeEdited':'ne'));
		if($_REQUEST['action_button']=="edit")
		{
			if(isset($_REQUEST['ID']))
			{
				if(!is_array($_REQUEST['ID']))
					$arID = Array($_REQUEST['ID']);
				else
					$arID = $_REQUEST['ID'];

				$this->arEditedRows = $arID;
				$this->bEditMode = true;
			}
			return false;
		}

		//AddMessage2Log("GroupAction = X");
		if($_REQUEST['action_target']!='selected')
		{
			if(!is_array($_REQUEST['ID']))
				$arID = Array($_REQUEST['ID']);
			else
				$arID = $_REQUEST['ID'];
		}
		else
			$arID = Array('');

		return $arID;
	}

	public static function ActionRedirect($url)
	{
		if(strpos($url, "lang=")===false)
		{
			if(strpos($url, "?")===false)
				$url .= '?';
			else
				$url .= '&';
			$url .= 'lang='.LANGUAGE_ID;
		}
		return "BX.adminPanel.Redirect([], '".CUtil::AddSlashes($url)."', event);";
	}

	public function ActionAjaxReload($url)
	{
		if(strpos($url, "lang=")===false)
		{
			if(strpos($url, "?")===false)
				$url .= '?';
			else
				$url .= '&';
			$url .= 'lang='.LANGUAGE_ID;
		}
		return $this->table_id.".GetAdminList('".CUtil::AddSlashes($url)."');";
	}

	public function ActionPost($url = false, $action_name = false, $action_value = 'Y')
	{
		$res = '';
		if($url)
		{
			if(strpos($url, "lang=")===false)
			{
				if(strpos($url, "?")===false)
					$url .= '?';
				else
					$url .= '&';
				$url .= 'lang='.LANGUAGE_ID;
			}

			if(strpos($url, "mode=")===false)
				$url .= '&mode=frame';

			$res = 'BX(\'form_'.$this->table_id.'\').action=\''.CUtil::AddSlashes($url).'\';';
		}

		if ($action_name)
			return $res.'; BX.submit(document.forms.form_'.$this->table_id.', \''.CUtil::JSEscape($action_name).'\', \''.CUtil::JSEscape($action_value).'\');';
		else
			return $res.'; BX.submit(document.forms.form_'.$this->table_id.');';
	}

	public function ActionDoGroup($id, $action_id, $add_params='')
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		return $this->table_id.".GetAdminList('".CUtil::AddSlashes($APPLICATION->GetCurPage())."?ID=".CUtil::AddSlashes($id)."&action=".CUtil::AddSlashes($action_id)."&lang=".LANGUAGE_ID."&".bitrix_sessid_get().($add_params<>""?"&".CUtil::AddSlashes($add_params):"")."');";
	}

	public function InitFilter($arFilterFields)
	{
		$sTableID = $this->table_id;
		global $del_filter, $set_filter, $save_filter;
		if($del_filter <> "")
			DelFilterEx($arFilterFields, $sTableID);
		elseif($set_filter <> "")
		{
			CAdminFilter::UnEscape($arFilterFields);
			InitFilterEx($arFilterFields, $sTableID, "set");
		}
		elseif($save_filter <> "")
		{
			CAdminFilter::UnEscape($arFilterFields);
		}
		else
			InitFilterEx($arFilterFields, $sTableID, "get");
	}

	public function IsDefaultFilter()
	{
		global $set_default;
		$sTableID = $this->table_id;
		return $set_default=="Y" && (!isset($_SESSION["SESS_ADMIN"][$sTableID]) || empty($_SESSION["SESS_ADMIN"][$sTableID]));
	}

	function &AddRow($id = false, $arRes = Array(), $link = false, $title = false)
	{
		$row = new CAdminListRow($this->aHeaders, $this->table_id);
		$row->id = $id;
		$row->arRes = $arRes;
		$row->link = $link;
		$row->title = $title;
		$row->pList = &$this;

		if($id)
		{
			if($this->bEditMode && in_array($id, $this->arEditedRows))
				$row->bEditMode = true;
			elseif(in_array($id, $this->arUpdateErrorIDs))
				$row->bEditMode = true;
		}

		$this->aRows[] = &$row;
		return $row;
	}

	public function AddFooter($aFooter)
	{
		$this->aFooter = $aFooter;
	}

	public function NavText($sNavText)
	{
		$this->sNavText = $sNavText;
	}

	public function Display()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		foreach(GetModuleEvents("main", "OnAdminListDisplay", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$this));

		$errmsg = '';
		foreach ($this->arFilterErrors as $err)
			$errmsg .= ($errmsg<>''? '<br>': '').$err;
		foreach ($this->arUpdateErrors as $err)
			$errmsg .= ($errmsg<>''? '<br>': '').$err[0];
		foreach ($this->arGroupErrors as $err)
			$errmsg .= ($errmsg<>''? '<br>': '').$err[0];
		if($errmsg<>'')
			CAdminMessage::ShowMessage(array("MESSAGE"=>GetMessage("admin_lib_error"), "DETAILS"=>$errmsg, "TYPE"=>"ERROR"));

		$successMessage = '';
		for ($i = 0, $cnt = count($this->arActionSuccess); $i < $cnt; $i++)
			$successMessage .= ($successMessage != '' ? '<br>' : '').$this->arActionSuccess[$i];
		if ($successMessage != '')
			CAdminMessage::ShowMessage(array("MESSAGE" => GetMessage("admin_lib_success"), "DETAILS" => $successMessage, "TYPE" => "OK"));

		echo $this->sPrologContent;

		if($this->sContent===false)
		{
?>
<div class="adm-list-table-wrap<?=$this->context ? '' : ' adm-list-table-without-header'?><?=count($this->arActions)<=0 && !$this->bCanBeEdited ? ' adm-list-table-without-footer' : ''?>">
<?
		}

		if($this->context)
			$this->context->Show();

		if(
			(isset($_REQUEST['ajax_debugx']) && $_REQUEST['ajax_debugx']=='Y')
			|| (isset($_SESSION['AJAX_DEBUGX']) && $_SESSION['AJAX_DEBUGX'])
		)
			echo '<form method="POST" '.($this->bMultipart?' enctype="multipart/form-data" ':'').' onsubmit="CheckWin();ShowWaitWindow();" target="frame_debug" id="form_'.$this->table_id.'" name="form_'.$this->table_id.'" action="'.htmlspecialcharsbx($APPLICATION->GetCurPageParam("mode=frame", array("mode"))).'">';
		else
			echo '<form method="POST" '.($this->bMultipart?' enctype="multipart/form-data" ':'').' onsubmit="return BX.ajax.submitComponentForm(this, \''.$this->table_id.'_result_div\', true);" id="form_'.$this->table_id.'" name="form_'.$this->table_id.'" action="'.htmlspecialcharsbx($APPLICATION->GetCurPageParam("mode=frame", array("mode", "action", "action_button"))).'">';

		if($this->bEditMode && !$this->bCanBeEdited)
			$this->bEditMode = false;

		if($this->sContent!==false)
		{
			echo $this->sContent;
			echo '</form>';
			return;
		}

		$bShowSelectAll = (count($this->arActions)>0 || $this->bCanBeEdited);
		$this->bShowActions = false;
		foreach($this->aRows as $row)
		{
			if(!empty($row->aActions))
			{
				$this->bShowActions = true;
				break;
			}
		}

		//!!! insert filter's hiddens
		echo bitrix_sessid_post();
		//echo $this->sNavText;

		$colSpan = 0;
?>
<table class="adm-list-table" id="<?=$this->table_id;?>">
	<thead>
		<tr class="adm-list-table-header">
<?
		if($bShowSelectAll):
?>
			<td class="adm-list-table-cell adm-list-table-checkbox" onclick="this.firstChild.firstChild.click(); return BX.PreventDefault(event);"><div class="adm-list-table-cell-inner"><input class="adm-checkbox adm-designed-checkbox" type="checkbox" id="<?=$this->table_id?>_check_all" onclick="<?=$this->table_id?>.SelectAllRows(this); return BX.eventCancelBubble(event);" title="<?=GetMessage("admin_lib_list_check_all")?>" /><label for="<?=$this->table_id?>_check_all" class="adm-designed-checkbox-label"></label></div></td>
<?
			$colSpan++;
		endif;

		if($this->bShowActions):
?>
			<td class="adm-list-table-cell adm-list-table-popup-block" title="<?=GetMessage("admin_lib_list_act")?>"><div class="adm-list-table-cell-inner"></div></td>
<?
			$colSpan++;
		endif;

		foreach($this->aVisibleHeaders as $header):
			$bSort = $this->sort && !empty($header["sort"]);

			if ($bSort)
				$attrs = $this->sort->Show($header["content"], $header["sort"], $header["title"], "adm-list-table-cell");
			else
				$attrs = 'class="adm-list-table-cell"';

?>
			<td <?=$attrs?>>
				<div class="adm-list-table-cell-inner"><?=$header["content"]?></div>
			</td>
<?
			$colSpan++;
		endforeach;
?>
		</tr>
	</thead>
	<tbody>
<?
		if(!empty($this->aRows)):
			foreach($this->aRows as $row)
			{
				$row->Display();
			}
		elseif(!empty($this->aHeaders)):
?>
		<tr><td colspan="<?=$colSpan?>" class="adm-list-table-cell adm-list-table-empty"><?=GetMessage("admin_lib_no_data")?></td></tr>
<?
		endif;
?>
	</tbody>
</table>
<?
/*		if(!empty($this->aFooter))
		{
			echo '
<table cellpadding="0" cellspacing="0" border="0" class="listfooter">
	<tr>
';
			$n = count($this->aFooter);
			for($i=0; $i<$n; $i++)
				echo '<td'.($i==0? ' class="left"':'').'>'.$this->aFooter[$i]["title"].' <span'.($this->aFooter[$i]["counter"]===true? ' id="'.$this->table_id.'_selected_span"':'').'>'.$this->aFooter[$i]["value"].'</span></td>';
			echo '
		<td class="right">&nbsp;</td>
	</tr>
</table>
';
		}*/
		$this->ShowActionTable();

// close form and div.adm-list-table-wrap

		echo $this->sEpilogContent;
		echo '
	</form>
</div>
';
		echo $this->sNavText;
	}

	public function DisplayExcel()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		echo '
		<html>
		<head>
		<title>'.$APPLICATION->GetTitle().'</title>
		<meta http-equiv="Content-Type" content="text/html; charset='.LANG_CHARSET.'">
		<style>
			td {mso-number-format:\@;}
			.number0 {mso-number-format:0;}
			.number2 {mso-number-format:Fixed;}
		</style>
		</head>
		<body>';

		echo "<table border=\"1\">";
		echo "<tr>";

		foreach($this->aVisibleHeaders as $header)
		{
			echo '<td>';
			echo $header["content"];
			echo '</td>';
		}
		echo "</tr>";


		foreach($this->aRows as $row)
		{
			echo "<tr>";
			foreach($this->aVisibleHeaders as $id=>$header_props)
			{
				$field = $row->aFields[$id];
				if(!is_array($row->arRes[$id]))
					$val = trim($row->arRes[$id]);
				else
					$val = $row->arRes[$id];

				switch($field["view"]["type"])
				{
					case "checkbox":
						if($val=='Y')
							$val = htmlspecialcharsex(GetMessage("admin_lib_list_yes"));
						else
							$val = htmlspecialcharsex(GetMessage("admin_lib_list_no"));
						break;
					case "select":
						if($field["edit"]["values"][$val])
							$val = htmlspecialcharsex($field["edit"]["values"][$val]);
						break;
					case "file":
						$arFile = CFile::GetFileArray($val);
						if(is_array($arFile))
							$val = htmlspecialcharsex(CHTTP::URN2URI($arFile["SRC"]));
						else
							$val = "";
						break;
					case "html":
						$val = $field["view"]['value'];
						break;
					default:
						$val = htmlspecialcharsex($val);
						break;
				}

				echo '<td'.($header_props['align']?' align="'.$header_props['align'].'"':'').($header_props['valign']?' valign="'.$header_props['valign'].'"':'').'>';
				echo ($val<>""? $val:'&nbsp;').'</td>';
			}
			echo "</tr>";
		}

		echo "</table>";

		if(!empty($this->aFooter))
		{
			echo '<table border="1"><tr>';

			$n = count($this->aFooter);
			for($i=0; $i<$n; $i++)
				echo '<td>'.$this->aFooter[$i]["title"].' '.$this->aFooter[$i]["value"].'</td>';

			echo '</tr></table>';
		}

		echo '</body></html>';
	}


	public function AddGroupActionTable($arActions, $arParams=array())
	{
		//array("action"=>"text", ...)
		//OR array(array("action" => "custom JS", "value" => "action", "type" => "button", "title" => "", "name" => ""), ...)
		$this->arActions = $arActions;
		//array("disable_action_target"=>true, "select_onchange"=>"custom JS")
		$this->arActionsParams = $arParams;
	}

	public function ShowActionTable()
	{
		if(count($this->arActions)<=0 && !$this->bCanBeEdited)
			return;

?>
<div class="adm-list-table-footer" id="<?=$this->table_id?>_footer<?=$this->bEditMode || count($this->arUpdateErrorIDs)>0 ? '_edit' : ''?>">
	<input type="hidden" name="action_button" value="" />
<?
		if($this->bEditMode || count($this->arUpdateErrorIDs)>0):
?>
		<input type="hidden" name="save" id="<?=$this->table_id?>_hidden_save" value="Y">
		<input type="submit" class="adm-btn-save" name="save" value="<?=GetMessage("admin_lib_list_edit_save")?>" title="<?=GetMessage("admin_lib_list_edit_save_title")?>" />
		<input type="button" onclick="BX('<?=$this->table_id?>_hidden_save').name='cancel'; <?=htmlspecialcharsbx($this->ActionPost(false, 'action_button', ''))?> " name="cancel" value="<?=GetMessage("admin_lib_list_edit_cancel")?>" title="<?=GetMessage("admin_lib_list_edit_cancel_title")?>" />

<?
		else: //($this->bEditMode || count($this->arUpdateErrorIDs)>0)
			if($this->arActionsParams["disable_action_target"] <> true):
?>
	<span class="adm-selectall-wrap"><input type="checkbox" class="adm-checkbox adm-designed-checkbox" name="action_target" value="selected" id="action_target" onclick="if(this.checked && !confirm('<?=CUtil::JSEscape(GetMessage("admin_lib_list_edit_for_all_warn"))?>')) {this.checked=false;} <?=$this->table_id?>.EnableActions();" title="<?=GetMessage("admin_lib_list_edit_for_all")?>" /><label for="action_target" class="adm-checkbox adm-designed-checkbox-label"></label><label title="<?=GetMessage("admin_lib_list_edit_for_all")?>" for="action_target" class="adm-checkbox-label"><?=GetMessage("admin_lib_list_for_all");?></label></span>
<?
			endif;

			$this->bCanBeDeleted = array_key_exists("delete", $this->arActions);

			if ($this->bCanBeEdited || $this->bCanBeDeleted)
			{
				echo '
	<span class="adm-table-item-edit-wrap'.(!$this->bCanBeEdited || !$this->bCanBeDeleted ? ' adm-table-item-edit-single' : '').'">
';
				if($this->bCanBeEdited)
				{
					echo '<a href="javascript:void(0)" class="adm-table-btn-edit adm-edit-disable" hidefocus="true" onclick="this.blur();if('.$this->table_id.'.IsActionEnabled(\'edit\')){document.forms[\'form_'.$this->table_id.'\'].elements[\'action_button\'].value=\'edit\'; '.
						htmlspecialcharsbx($this->ActionPost(false, 'action_button', 'edit')).'}" title="'.GetMessage("admin_lib_list_edit").'" id="action_edit_button"></a>';
				}
				if($this->bCanBeDeleted)
				{
					echo '<a href="javascript:void(0);" class="adm-table-btn-delete adm-edit-disable" hidefocus="true" onclick="this.blur();if('.$this->table_id.'.IsActionEnabled() && confirm((document.getElementById(\'action_target\') && document.getElementById(\'action_target\').checked? \''.GetMessage("admin_lib_list_del").'\':\''.GetMessage("admin_lib_list_del_sel").'\'))) {document.forms[\'form_'.$this->table_id.'\'].elements[\'action_button\'].value=\'delete\'; '.
						htmlspecialcharsbx($this->ActionPost(false, 'action_button', 'delete')).'}" title="'.GetMessage("admin_lib_list_del_title").'" class="context-button icon action-delete-button-dis" id="action_delete_button"></a>';
				}
				echo '
	</span>
';
			}

			$list = '';
			$html = '';
			$buttons = '';
			foreach($this->arActions as $k=>$v)
			{
				if($k === "delete")
				{
					continue;
				}
				else
				{
					if(is_array($v))
					{
						if($v["type"] == "button")
						{
							$buttons .= '<input type="button" name="" value="'.htmlspecialcharsbx($v['name']).'" onclick="'.(!empty($v["action"])? str_replace("\"", "&quot;", $v['action']) : 'document.forms[\'form_'.$this->table_id.'\'].elements[\'action_button\'].value=\''.htmlspecialcharsbx($v["value"]).'\'; '.htmlspecialcharsbx($this->ActionPost()).'').'" title="'.htmlspecialcharsbx($v["title"]).'" />';
						}
						elseif($v["type"] == "html")
						{
							$html .= '<span class="adm-list-footer-ext">'.$v["value"].'</span>';
						}
						else
						{
							$list .= '<option value="'.htmlspecialcharsbx($v['value']).'"'.($v['action']?' custom_action="'.str_replace("\"", "&quot;", $v['action']).'"':'').'>'.htmlspecialcharsex($v['name']).'</option>';
						}
					}
					else
					{
						$list .= '<option value="'.htmlspecialcharsbx($k).'">'.htmlspecialcharsex($v).'</option>';
					}
				}
			}

			if (strlen($buttons) > 0)
				echo '<span class="adm-list-footer-ext">'.$buttons.'</span>';

			if (strlen($list) > 0):
?>
	<span class="adm-select-wrap">
		<select name="action" class="adm-select"<?=($this->arActionsParams["select_onchange"] <> ""? ' onchange="'.htmlspecialcharsbx($this->arActionsParams["select_onchange"]).'"':'')?>>
			<option value=""><?=GetMessage("admin_lib_list_actions")?></option>
<?=$list?>
		</select>
	</span>
<?
				if (strlen($html) > 0)
					echo $html;
?>
	<input type="submit" name="apply" value="<?=GetMessage("admin_lib_list_apply")?>" onclick="if(this.form.action[this.form.action.selectedIndex].getAttribute('custom_action')){eval(this.form.action[this.form.action.selectedIndex].getAttribute('custom_action'));return false;}" disabled="disabled" class="adm-table-action-button" />
<?
			endif; //(strlen($list) > 0)
?>
	<span class="adm-table-counter" id="<?=$this->table_id?>_selected_count"><?=GetMessage('admin_lib_checked')?>: <span>0</span></span>
<?
		endif; // ($this->bEditMode || count($this->arUpdateErrorIDs)>0):
?>
</div>
<?
	}

	public function DisplayList($arParams = array())
	{
		$menu = new CAdminPopup($this->table_id."_menu", $this->table_id."_menu");
		$menu->Show();

		if(
			(isset($_REQUEST['ajax_debugx']) && $_REQUEST['ajax_debugx']=='Y')
			|| (isset($_SESSION['AJAX_DEBUGX']) && $_SESSION['AJAX_DEBUGX'])
		)
		{
			echo '<script>
				function CheckWin()
				{
					window.open("about:blank", "frame_debug");
				}
				</script>';
		}
		else
		{
			echo '<iframe src="javascript:\'\'" id="frame_'.$this->table_id.'" name="frame_'.$this->table_id.'" style="width:1px; height:1px; border:0px; position:absolute; left:-10px; top:-10px; z-index:0;"></iframe>';
		}

		$aUserOpt = CUserOptions::GetOption("global", "settings");

		if (!is_array($arParams))
			$arParams = array();

		if (!isset($arParams['FIX_HEADER']))
			$arParams['FIX_HEADER'] = true;
		if (!isset($arParams['FIX_FOOTER']))
			$arParams['FIX_FOOTER'] = true;
		if (!isset($arParams['context_ctrl']))
			$arParams['context_ctrl'] = ($aUserOpt["context_ctrl"] == "Y");
		if (!isset($arParams['context_menu']))
			$arParams['context_menu'] = ($aUserOpt["context_menu"] <> "N");

		$tbl = CUtil::JSEscape($this->table_id);
?>
<script type="text/javascript">
window['<?=$tbl?>'] = new BX.adminList('<?=$tbl?>', <?=CUtil::PhpToJsObject($arParams)?>);
BX.adminChain.addItems("<?=$tbl?>_navchain_div");
</script>
<?

		echo '<div id="'.$this->table_id.'_result_div" class="adm-list-table-layout">';
		$this->Display();
		echo '</div>';
	}

	public function AddUpdateError($strError, $id = false)
	{
		$this->arUpdateErrors[] = Array($strError, $id);
		$this->arUpdateErrorIDs[] = $id;
	}

	public function AddGroupError($strError, $id = false)
	{
		$this->arGroupErrors[] = Array($strError, $id);
		$this->arGroupErrorIDs[] = $id;
	}

	public function AddActionSuccessMessage($strMessage)
	{
		$this->arActionSuccess[] = $strMessage;
	}

	public function AddFilterError($strError)
	{
		$this->arFilterErrors[] = $strError;
	}

	public static function BeginPrologContent()
	{
		ob_start();
	}

	public function EndPrologContent()
	{
		$this->sPrologContent .= ob_get_contents();
		ob_end_clean();
	}

	public static function BeginEpilogContent()
	{
		ob_start();
	}

	public function EndEpilogContent()
	{
		$this->sEpilogContent = ob_get_contents();
		ob_end_clean();
	}

	public static function BeginCustomContent()
	{
		ob_start();
	}

	public function EndCustomContent()
	{
		$this->sContent = ob_get_contents();
		ob_end_clean();
	}

	public function CreateChain()
	{
		return new CAdminChain($this->table_id."_navchain_div", false);
	}

	/**
	 * @param CAdminChain $chain
	 */
	public function ShowChain($chain)
	{
		$this->BeginPrologContent();
		$chain->Show();
		$this->EndPrologContent();
	}

	public function CheckListMode()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if (!isset($_REQUEST["mode"]))
			return;

		if($_REQUEST["mode"]=='list' || $_REQUEST["mode"]=='frame')
		{
			ob_start();
			$this->Display();
			$string = ob_get_contents();
			ob_end_clean();

			if($_REQUEST["mode"]=='frame')
			{
?>
<html><head></head><body><?=$string?><script type="text/javascript">
top.bxcompajaxframeonload = function() {
	top.BX.adminPanel.closeWait();
	top.<?=$this->table_id?>.Destroy(false);
	top.<?=$this->table_id?>.Init();
<?
				if(isset($this->onLoadScript)):
?>
	top.BX.evalGlobal('<?=CUtil::JSEscape($this->onLoadScript)?>');
<?
				endif;
?>
}
</script></body></html>
<?
			}
			else
			{
				if(isset($this->onLoadScript)):
?>
<script type="text/javascript"><?=$this->onLoadScript?></script>
<?
				endif;

				echo $string;
			}
			// define("ADMIN_AJAX_MODE", true);
			require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_after.php");
			die();
		}
		elseif($_REQUEST["mode"]=='excel')
		{
			$fname = basename($APPLICATION->GetCurPage(), ".php");
			// http response splitting defence
			$fname = str_replace(array("\r", "\n"), "", $fname);

			header("Content-Type: application/vnd.ms-excel");
			header("Content-Disposition: filename=".$fname.".xls");
			$this->DisplayExcel();
			require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_after.php");
			die();
		}
	}
}

class CAdminListRow
{
	var $aHeaders = array();
	var $aHeadersID = array();
	var $aFields = array();
	var $aActions = array();
	var $table_id;
	var $indexFields = 0;
	var $edit = false;
	var $id;
	var $bReadOnly = false;
	var $aFeatures = array();
	var $bEditMode = false;
	var $arRes;
	var $link;
	var $title;
	var $pList;


	public function CAdminListRow(&$aHeaders, $table_id)
	{
		$this->aHeaders = $aHeaders;
		$this->aHeadersID = array_keys($aHeaders);
		$this->table_id = $table_id;
	}

	public function SetFeatures($aFeatures)
	{
		//array("footer"=>true)
		$this->aFeatures = $aFeatures;
	}

	public function AddField($id, $text, $edit=false)
	{
		$this->aFields[$id] = array();
		if($edit !== false)
		{
			$this->aFields[$id]["edit"] = Array("type"=>"input", "value"=>$edit);
			$this->pList->bCanBeEdited = true;
		}
		$this->aFields[$id]["view"] = Array("type"=>"html", "value"=>$text);
	}

	public function AddCheckField($id, $arAttributes = Array())
	{
		if($arAttributes!==false)
		{
			$this->aFields[$id]["edit"] = Array("type"=>"checkbox", "attributes"=>$arAttributes);
			$this->pList->bCanBeEdited = true;
		}
		$this->aFields[$id]["view"] = Array("type"=>"checkbox");
	}

	public function AddSelectField($id, $arValues = Array(), $arAttributes = Array())
	{
		if($arAttributes!==false)
		{
			$this->aFields[$id]["edit"] = Array("type"=>"select", "values"=>$arValues, "attributes"=>$arAttributes);
			$this->pList->bCanBeEdited = true;
		}
		$this->aFields[$id]["view"] = Array("type"=>"select", "values"=>$arValues);
	}

	public function AddInputField($id, $arAttributes = Array())
	{
		if($arAttributes!==false)
		{
			$this->aFields[$id]["edit"] = Array("type"=>"input", "attributes"=>$arAttributes);
			$this->pList->bCanBeEdited = true;
		}
	}

	public function AddCalendarField($id, $arAttributes = Array())
	{
		if($arAttributes!==false)
		{
			$this->aFields[$id]["edit"] = Array("type"=>"calendar", "attributes"=>$arAttributes);
			$this->pList->bCanBeEdited = true;
		}
	}

	public function AddViewField($id, $sHTML)
	{
		$this->aFields[$id]["view"] = Array("type"=>"html", "value"=>$sHTML);
	}

	public function AddEditField($id, $sHTML)
	{
		$this->aFields[$id]["edit"] = Array("type"=>"html", "value"=>$sHTML);
		$this->pList->bCanBeEdited = true;
	}

	public function AddViewFileField($id, $showInfo = false)
	{
		static $fileman = 0;
		if (!($fileman++))
			CModule::IncludeModule('fileman');

		$this->aFields[$id]["view"] = array(
			"type" => "file",
			"showInfo" => $showInfo,
			"inputs" => array(
				'upload' => false,
				'medialib' => false,
				'file_dialog' => false,
				'cloud' => false,
				'del' => false,
				'description' => false,
			),
		);
	}

	public function AddFileField($id, $showInfo = false, $inputs = array())
	{
		$this->aFields[$id]["edit"] = array(
			"type" => "file",
			"showInfo" => $showInfo,
			"inputs" => $inputs,
		);
		$this->pList->bCanBeEdited = true;
		$this->AddViewFileField($id, $showInfo);
	}

	public function AddActions($aActions)
	{
		$this->aActions = $aActions;
	}

	public static function __AttrGen($attr)
	{
		$res = '';
		foreach($attr as $name=>$val)
			$res .= ' '.htmlspecialcharsbx($name).'="'.htmlspecialcharsbx($val).'"';

		return $res;
	}

	public function VarsFromForm()
	{
		return ($this->bEditMode && is_array($this->pList->arUpdateErrorIDs) && in_array($this->id, $this->pList->arUpdateErrorIDs));
	}

	public function Display()
	{
		$sDefAction = $sDefTitle = "";
		if(!$this->bEditMode)
		{
			if(!empty($this->link))
			{
				$sDefAction = "BX.adminPanel.Redirect([], '".CUtil::JSEscape($this->link)."', event);";
				$sDefTitle = $this->title;
			}
			else
			{
				$this->aActions = array_values($this->aActions);
				foreach($this->aActions as $action)
				{
					if($action["DEFAULT"] == true)
					{
						$sDefAction = $action["ACTION"]
							? htmlspecialcharsbx($action["ACTION"])
							: "BX.adminPanel.Redirect([], '".CUtil::JSEscape($action["LINK"])."', event)"
						;
						$sDefTitle = (!empty($action["TITLE"])? $action["TITLE"]:$action["TEXT"]);
						break;
					}
				}
			}
		}

		$sMenuItems = "";
		if(!empty($this->aActions))
			$sMenuItems = htmlspecialcharsbx(CAdminPopup::PhpToJavaScript($this->aActions));
?>
<tr class="adm-list-table-row<?=(isset($this->aFeatures["footer"]) && $this->aFeatures["footer"] == true? ' footer':'')?><?=$this->bEditMode?' adm-table-row-active' : ''?>"<?=($sMenuItems <> ""? ' oncontextmenu="return '.$sMenuItems.';"':'');?><?=($sDefAction <> ""? ' ondblclick="'.$sDefAction.'"'.(!empty($sDefTitle)? ' title="'.GetMessage("admin_lib_list_double_click").' '.$sDefTitle.'"':''):'')?>>
<?

		if(count($this->pList->arActions)>0 || $this->pList->bCanBeEdited):
			$check_id = RandString(5);
?>
	<td class="adm-list-table-cell adm-list-table-checkbox adm-list-table-checkbox-hover<?=$this->bReadOnly? ' adm-list-table-checkbox-disabled':''?>"><input type="checkbox" class="adm-checkbox adm-designed-checkbox" name="ID[]" id="<?=$this->table_id."_".$this->id."_".$check_id;?>" value="<?=$this->id?>" autocomplete="off" title="<?=GetMessage("admin_lib_list_check")?>"<?=$this->bReadOnly? ' disabled="disabled"':''?><?=$this->bEditMode ? ' checked="checked" disabled="disabled"' : ''?> /><label class="adm-designed-checkbox-label adm-checkbox" for="<?=$this->table_id."_".$this->id."_".$check_id;?>"></label></td>
<?
		endif;

		if($this->pList->bShowActions):
			if(!empty($this->aActions)):
?>
	<td class="adm-list-table-cell adm-list-table-popup-block" onclick="BX.adminList.ShowMenu(this.firstChild, this.parentNode.oncontextmenu(), this.parentNode);"><div class="adm-list-table-popup" title="<?=GetMessage("admin_lib_list_actions_title")?>"></div></td>
<?
			else:
?>
	<td class="adm-list-table-cell"></td>
<?
			endif;
		endif;

		end($this->pList->aVisibleHeaders);
		$last_id = key($this->pList->aVisibleHeaders);
		reset($this->pList->aVisibleHeaders);

		$bVarsFromForm = ($this->bEditMode && is_array($this->pList->arUpdateErrorIDs) && in_array($this->id, $this->pList->arUpdateErrorIDs));
		foreach($this->pList->aVisibleHeaders as $id=>$header_props)
		{
			$field = $this->aFields[$id];
			if($this->bEditMode && isset($field["edit"]))
			{
				if($bVarsFromForm && $_REQUEST["FIELDS"])
					$val = $_REQUEST["FIELDS"][$this->id][$id];
				else
					$val = $this->arRes[$id];

				$val_old = $this->arRes[$id];

				echo '<td class="adm-list-table-cell',
					(isset($header_props['align']) && $header_props['align']? ' align-'.$header_props['align']: ''),
					(isset($header_props['valign']) && $header_props['valign']? ' valign-'.$header_props['valign']: ''),
					($id === $last_id? ' adm-list-table-cell-last': ''),
				'">';

				if(is_array($val_old))
				{
					foreach($val_old as $k=>$v)
						echo '<input type="hidden" name="FIELDS_OLD['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']['.htmlspecialcharsbx($k).']" value="'.htmlspecialcharsbx($v).'">';
				}
				else
				{
					echo '<input type="hidden" name="FIELDS_OLD['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']" value="'.htmlspecialcharsbx($val_old).'">';
				}
				switch($field["edit"]["type"])
				{
					case "checkbox":
						echo '<input type="hidden" name="FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']" value="N">';
						echo '<input type="checkbox" name="FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']" value="Y"'.($val=='Y'?' checked':'').'>';
						break;
					case "select":
						echo '<select name="FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']"'.$this->__AttrGen($field["edit"]["attributes"]).'>';
						foreach($field["edit"]["values"] as $k=>$v)
							echo '<option value="'.htmlspecialcharsbx($k).'" '.($k==$val?' selected':'').'>'.htmlspecialcharsbx($v).'</option>';
						echo '</select>';
						break;
					case "input":
						if(!$field["edit"]["attributes"]["size"])
							$field["edit"]["attributes"]["size"] = "10";
						echo '<input type="text" '.$this->__AttrGen($field["edit"]["attributes"]).' name="FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']" value="'.htmlspecialcharsbx($val).'">';
						break;
					case "calendar":
						if(!$field["edit"]["attributes"]["size"])
							$field["edit"]["attributes"]["size"] = "10";
						echo '<span style="white-space:nowrap;"><input type="text" '.$this->__AttrGen($field["edit"]["attributes"]).' name="FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']" value="'.htmlspecialcharsbx($val).'">';
						echo CAdminCalendar::Calendar('FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']').'</span>';
						break;
					case "file":
						echo CFileInput::Show(
							'FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']',
							$val,
							$field["edit"]["showInfo"],
							$field["edit"]["inputs"]
						);
						break;
					default:
						echo $field["edit"]['value'];
				}
				echo '</td>';
			}
			else
			{
				if(!is_array($this->arRes[$id]))
					$val = trim($this->arRes[$id]);
				else
					$val = $this->arRes[$id];

				if(isset($field["view"]))
				{
					switch($field["view"]["type"])
					{
						case "checkbox":
							if($val=='Y')
								$val = htmlspecialcharsex(GetMessage("admin_lib_list_yes"));
							else
								$val = htmlspecialcharsex(GetMessage("admin_lib_list_no"));
							break;
						case "select":
							if($field["edit"]["values"][$val])
								$val = htmlspecialcharsex($field["edit"]["values"][$val]);
							break;
						case "file":
							if ($val > 0)
								$val = CFileInput::Show(
									'NO_FIELDS['.htmlspecialcharsbx($this->id).']['.htmlspecialcharsbx($id).']',
									$val,
									$field["view"]["showInfo"],
									$field["view"]["inputs"]
								);
							else
								$val = '';
							break;
						case "html":
							$val = $field["view"]['value'];
							break;
						default:
							$val = htmlspecialcharsex($val);
							break;
					}
				}
				else
				{
					$val = htmlspecialcharsex($val);
				}

				echo '<td class="adm-list-table-cell',
					(isset($header_props['align']) && $header_props['align']? ' align-'.$header_props['align']: ''),
					(isset($header_props['valign']) && $header_props['valign']? ' valign-'.$header_props['valign']: ''),
					($id === $last_id? ' adm-list-table-cell-last': ''),
				'">';
				echo ((string)$val <> ""? $val: '&nbsp;');
				if(isset($field["edit"]) && $field["edit"]["type"] == "calendar")
					CAdminCalendar::ShowScript();
				echo '</td>';
			}
		}
?>
</tr>
<?
	}
}

class CAdminMessage
{
	/** @var CAdminException */
	var $exception;
	var	$message;

	/**
	 * @param string|array $message
	 * @param CAdminException|bool $exception
	 */
	public function CAdminMessage($message, $exception=false)
	{
		//array("MESSAGE"=>"", "TYPE"=>("ERROR"|"OK"|"PROGRESS"), "DETAILS"=>"", "HTML"=>true)
		if(!is_array($message))
			$message = array("MESSAGE"=>$message, "TYPE"=>"ERROR");
		if(empty($message["DETAILS"]) && $exception !== false)
			$message["DETAILS"] = $exception->GetString();
		$this->message = $message;
		$this->exception = $exception;
	}

	public function Show()
	{
		if (defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1 && $this->message["TYPE"] != "PROGRESS")
		{
			ob_end_clean();
			echo '<script>top.BX.WindowManager.Get().ShowError(\''.CUtil::JSEscape(str_replace(array('<br>', '<br />', '<BR>', '<BR />'), "\r\n", htmlspecialcharsback($this->message['DETAILS']? $this->message['DETAILS'] : $this->message['MESSAGE']))).'\');</script>';
			die();
		}

		if($this->message["MESSAGE"])
			$title = '<div class="adm-info-message-title">'.$this->_formatHTML($this->message["MESSAGE"]).'</div>';
		else
			$title = '';

		if($this->message["DETAILS"])
			$details = $this->_formatHTML($this->message["DETAILS"]);
		else
			$details = '';

		if($this->message["TYPE"] == "OK")
		{
			$s = '
<div class="adm-info-message-wrap adm-info-message-green">
	<div class="adm-info-message">
		'.$title.'
		'.$details.'
		<div class="adm-info-message-icon"></div>
	</div>
</div>
';
		}
		elseif($this->message["TYPE"] == "PROGRESS")
		{
			if ($this->message['PROGRESS_ICON'])
				$title = '<div class="adm-info-message-icon-progress"></div>'.$title;

			$details = str_replace("#PROGRESS_BAR#", $this->_getProgressHtml(), $details);
			$s = '
<div class="adm-info-message-wrap adm-info-message-gray">
	<div class="adm-info-message">
		'.$title.'
		'.$details.'
		<div class="adm-info-message-buttons">'.$this->_getButtonsHtml().'</div>
	</div>
</div>
';
		}
		else
		{
			$s = '
<div class="adm-info-message-wrap adm-info-message-red">
	<div class="adm-info-message">
		'.$title.'
		'.$details.'
		<div class="adm-info-message-icon"></div>
	</div>
</div>
';
		}

		return $s;
	}

	public function _getProgressHtml()
	{
		$w = isset($this->message['PROGRESS_WIDTH']) ? intval($this->message['PROGRESS_WIDTH']) : 500;
		$p = 0;
		if ($this->message['PROGRESS_TOTAL'] > 0)
			$p = $this->message['PROGRESS_VALUE']/$this->message['PROGRESS_TOTAL'];

		if ($p < 0)
			$p = 0;
		elseif ($p > 1)
			$p = 1;

		$innerText = number_format(100*$p, 0) .'%';
		if ($this->message['PROGRESS_TEMPLATE'])
		{
			$innerText = str_replace(
				array('#PROGRESS_TOTAL#', '#PROGRESS_VALUE#', '#PROGRESS_PERCENT#'),
				array($this->message['PROGRESS_TOTAL'], $this->message['PROGRESS_VALUE'], $innerText),
				$this->message['PROGRESS_TEMPLATE']
			);
		}

		$s = '<div class="adm-progress-bar-outer" style="width: '.$w.'px;"><div class="adm-progress-bar-inner" style="width: '.intval($p*($w-4)).'px;"><div class="adm-progress-bar-inner-text" style="width: '.$w.'px;">'.$innerText.'</div></div>'.$innerText.'</div>';

		return $s;
	}

	public function _getButtonsHtml()
	{
		$s = '';
		if(isset($this->message["BUTTONS"]) && is_array($this->message["BUTTONS"]))
		{
			foreach($this->message["BUTTONS"] as $button)
				$s .= '<input type="button" onclick="'.htmlspecialcharsbx($button["ONCLICK"]).'" value="'.htmlspecialcharsbx($button["VALUE"]).'" '.($button["ID"]? 'id="'.htmlspecialcharsbx($button["ID"]).'"': '').'>';
		}
		return $s;
	}

	public function _formatHTML($html)
	{
		if($this->message["HTML"])
			return $html;
		else
			return _ShowHtmlspec($html);
	}

	public function GetMessages()
	{
		if($this->exception && method_exists($this->exception, 'GetMessages'))
			return $this->exception->GetMessages();
		return false;
	}

	public static function ShowOldStyleError($message)
	{
		if(!empty($message))
		{
			$m = new CAdminMessage(array("MESSAGE"=>GetMessage("admin_lib_error"), "DETAILS"=>$message, "TYPE"=>"ERROR"));
			echo $m->Show();
		}
	}

	public static function ShowMessage($message)
	{
		if(!empty($message))
		{
			$m = new CAdminMessage($message);
			echo $m->Show();
		}
	}

	public static function ShowNote($message)
	{
		if(!empty($message))
			CAdminMessage::ShowMessage(array("MESSAGE"=>$message, "TYPE"=>"OK"));
	}
}

class CAdminChain
{
	var $items = array();
	var $id, $bVisible;

	public function CAdminChain($id=false, $bVisible=true)
	{
		$this->id = $id;
		$this->bVisible = $bVisible;
	}

	public function AddItem($item)
	{
		//array("TEXT"=>"", "LINK"=>"", "ONCLICK"=>"", "MENU"=>array(array("TEXT"=>"", "LINK"=>"", "CLASS"=>""), ...))
		$this->items[] = $item;
	}

	public function Show()
	{
		if(empty($this->items))
			return null;

		$chainScripts = '';

?>
<div class="adm-navchain"<?=($this->id ? ' id="'.$this->id.'"':'').($this->bVisible == false? ' style="display:none;"' : '')?>>
<?
		$last_item = null;

		$cnt = count($this->items)-1;
		foreach($this->items as $n => $item)
		{
			$openerUrl = '/bitrix/admin/get_start_menu.php?skip_recent=Y&lang='.LANGUAGE_ID.($item['ID'] ? '&mode=chain&admin_mnu_menu_id='.urlencode($item['ID']) : '');

			$className = !empty($item['CLASS'])?' '.htmlspecialcharsbx($item['CLASS']):'';

			if (!empty($item['LINK']))
			{
				echo '<a class="adm-navchain-item" href="'.$item["LINK"].'"'.(!empty($item["ONCLICK"])? ' onclick="'.$item["ONCLICK"].'"':'').'><span class="adm-navchain-item-text'.$className.'">'.$item["TEXT"].'</span></a>';
			}
			elseif (!empty($item['ID']))
			{
				echo '<a href="javascript:void(0)" class="adm-navchain-item" id="bx_admin_chain_item_'.$item['ID'].'"><span class="adm-navchain-item-text'.$className.'">'.$item["TEXT"].'</span></a>';

				$chainScripts .= 'new BX.COpener('.CUtil::PhpToJsObject(array(
					'DIV' => 'bx_admin_chain_item_'.$item['ID'],
					'ACTIVE_CLASS' => 'adm-navchain-item-active',
					'MENU_URL' => $openerUrl
				)).');';

			}
			else
			{
				echo '<span class="adm-navchain-item adm-navchain-item-empty'.$className.'"><span class="adm-navchain-item-text">'.$item["TEXT"].'</span></span>';
			}

			if ($n < $cnt)
			{
				if($item['ID'] || ($n==0 && $this->id == 'main_navchain'))
				{
					echo '<span class="adm-navchain-item" id="bx_admin_chain_delimiter_'.$item['ID'].'"><span class="adm-navchain-delimiter"></span></span>';

					$chainScripts .= 'new BX.COpener('.CUtil::PhpToJsObject(array(
							'DIV' => 'bx_admin_chain_delimiter_'.$item['ID'],
							'ACTIVE_CLASS' => 'adm-navchain-item-active',
							'MENU_URL' => $openerUrl
						)).');';
				}
				else
				{
					echo '<span class="adm-navchain-delimiter"></span>';
				}

			}

			$last_item = $item;
		}
?>
</div>
<?
		if ($chainScripts != '')
		{
?>
<script type="text/javascript"><?=$chainScripts?></script>
<?
		}

		return $last_item;
	}
}

class CAdminMainChain extends CAdminChain
{
	var $bInit = false;

	public function CAdminMainChain($id=false, $bVisible=true)
	{
		$this->CAdminChain($id, $bVisible);
	}

	public function Init()
	{
		/** @global CAdminPage $adminPage */
		global $adminPage;
		/** @global CAdminMenu $adminMenu */
		global $adminMenu;

		if($this->bInit)
			return;
		$this->bInit = true;
		$adminPage->Init();
		$adminMenu->Init($adminPage->aModules);

		parent::AddItem(array("TEXT"=> GetMessage("admin_lib_navchain_first"), "LINK"=>"/bitrix/admin/index.php?lang=".LANGUAGE_ID, "CLASS" => "adm-navchain-item-desktop"));

		foreach($adminMenu->aActiveSections as $sect)
		{
			if($sect["skip_chain"] !== true)
				parent::AddItem(array("TEXT"=>$sect["text"], "LINK"=>$sect["url"], "ID" => $sect['items_id']));
		}
	}

	public function AddItem($item)
	{
		$this->Init();
		parent::AddItem($item);
	}
}

class CAdminCalendar
{
	const PERIOD_EMPTY = "NOT_REF";
	const PERIOD_DAY = "day";
	const PERIOD_WEEK = "week";
	const PERIOD_MONTH = "month";
	const PERIOD_QUARTER = "quarter";
	const PERIOD_YEAR = "year";
	const PERIOD_EXACT = "exact";
	const PERIOD_BEFORE = "before";
	const PERIOD_AFTER = "after";
	const PERIOD_INTERVAL = "interval";

	private function InitPeriodList($arPeriodParams = array())
	{
			$arPeriod = array(
				self::PERIOD_EMPTY => GetMessage("admin_lib_calend_no_period"),
				self::PERIOD_DAY => GetMessage("admin_lib_calend_day"),
				self::PERIOD_WEEK => GetMessage("admin_lib_calend_week"),
				self::PERIOD_MONTH => GetMessage("admin_lib_calend_month"),
				self::PERIOD_QUARTER => GetMessage("admin_lib_calend_quarter"),
				self::PERIOD_YEAR => GetMessage("admin_lib_calend_year"),
				self::PERIOD_EXACT => GetMessage("admin_lib_calend_exact"),
				self::PERIOD_BEFORE => GetMessage("admin_lib_calend_before"),
				self::PERIOD_AFTER => GetMessage("admin_lib_calend_after"),
				self::PERIOD_INTERVAL => GetMessage("admin_lib_calend_interval")
			);

		if(!is_array($arPeriodParams) || empty($arPeriodParams))
			return $arPeriod;

		$arReturnPeriod = array();

		foreach ($arPeriodParams as $periodName => $lPhrase)
		{
			if(array_key_exists($periodName, $arPeriod))
				$arReturnPeriod[$periodName] = $lPhrase;
			elseif(array_key_exists($arPeriodParams[$periodName], $arPeriod))
				$arReturnPeriod[$arPeriodParams[$periodName]] = $arPeriod[$arPeriodParams[$periodName]];
		}



		if(empty($arReturnPeriod))
			$arReturnPeriod = $arPeriod;


		return $arReturnPeriod;

	}

	static public function ShowScript()
	{
		CJSCore::Init(array('date'));
	}

	static public function Calendar($sFieldName, $sFromName="", $sToName="", $bTime=false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		ob_start();
		$APPLICATION->IncludeComponent('bitrix:main.calendar', '', array(
			'RETURN' => 'Y',
			'SHOW_INPUT' => 'N',
			'INPUT_NAME' => $sFieldName,
			'SHOW_TIME' => $bTime ? 'Y' : 'N'
		), null, array('HIDE_ICONS' => 'Y'));
		$res = ob_get_contents();
		ob_end_clean();

		return $res;
	}

	static public function CalendarDate($sFieldName, $sValue="", $size="10", $bTime=false)
	{
		// component can't set 'size' param
		return '
	<div class="adm-input-wrap adm-input-wrap-calendar">
		<input class="adm-input adm-input-calendar" type="text" name="'.$sFieldName.'" size="'.(intval($size)+3).'" value="'.htmlspecialcharsbx($sValue).'">
		<span class="adm-calendar-icon" title="'.GetMessage("admin_lib_calend_title").'" onclick="BX.calendar({node:this, field:\''.$sFieldName.'\', form: \'\', bTime: '.($bTime ? 'true' : 'false').', bHideTime: false});"></span>
	</div>';

	}

	static public function CalendarPeriodCustom($sFromName, $sToName, $sFromVal="", $sToVal="", $bSelectShow=false, $size="10", $bTime=false, $arPeriod = false)
	{
		$arPeriodList = self::InitPeriodList($arPeriod);

		return self::GetPeriodHtml($sFromName, $sToName, $sFromVal, $sToVal, $bSelectShow, $size, $bTime, $arPeriodList);
	}

	static public function CalendarPeriod($sFromName, $sToName, $sFromVal="", $sToVal="", $bSelectShow=false, $size="10", $bTime=false)
	{
		$arPeriodList = self::InitPeriodList();

		return self::GetPeriodHtml($sFromName, $sToName, $sFromVal, $sToVal, $bSelectShow, $size, $bTime, $arPeriodList);
	}

	private function GetPeriodHtml($sFromName, $sToName, $sFromVal="", $sToVal="", $bSelectShow=false, $size="10", $bTime=false, $arPeriod)
	{
		$size = intval($size);

		$s = '
		<div class="adm-calendar-block adm-filter-alignment">
			<div class="adm-filter-box-sizing">';

		if($bSelectShow)
		{
			$sPeriodName = $sFromName."_FILTER_PERIOD";
			$sDirectionName = $sFromName."_FILTER_DIRECTION";

			$arDirection = array(
				"previous"=>GetMessage("admin_lib_calend_previous"),
				"current"=>GetMessage("admin_lib_calend_current"),
				"next"=>GetMessage("admin_lib_calend_next")
			);

			$s .= '<span class="adm-select-wrap adm-calendar-period" ><select class="adm-select adm-calendar-period" id="'.$sFromName.'_calendar_period" name="'.$sPeriodName.'" onchange="BX.CalendarPeriod.OnChangeP(this);" title="'.GetMessage("admin_lib_calend_period_title").'">';

			foreach($arPeriod as $k => $v)
			{
					$k = ($k != "NOT_REF" ? $k : "");
					$s .= '<option value="'.$k.'"'.(($GLOBALS[$sPeriodName] <> "" && $GLOBALS[$sPeriodName] == $k) ? " selected":"").'>'.$v.'</option>';
			}

			$s .='</select></span>';

			$s .= '<span class="adm-select-wrap adm-calendar-direction" style="display: none;"><select class="adm-select adm-calendar-direction" id="'.$sFromName.'_calendar_direct" name="'.$sDirectionName.'" onchange="BX.CalendarPeriod.OnChangeD(this);"  title="'.GetMessage("admin_lib_calend_direct_title").'">';
			foreach($arDirection as $k => $v)
					$s .= '<option value="'.$k.'"'.(($GLOBALS[$sDirectionName] <> "" && $GLOBALS[$sDirectionName] == $k) ? " selected":"").'>'.$v.'</option>';

			$s .='</select></span>';
		}

		$s .=''.
		'<div class="adm-input-wrap adm-calendar-inp adm-calendar-first" style="display: '.($bSelectShow ? 'none' : 'inline-block').';">'.
			'<input type="text" class="adm-input adm-calendar-from" id="'.$sFromName.'_calendar_from" name="'.$sFromName.'" size="'.($size+5).'" value="'.htmlspecialcharsbx($sFromVal).'">'.
			'<span class="adm-calendar-icon" title="'.GetMessage("admin_lib_calend_title").'" onclick="BX.calendar({node:this, field:\''.$sFromName.'\', form: \'\', bTime: '.($bTime ? 'true' : 'false').', bHideTime: false});"></span>'.
		'</div>
		<span class="adm-calendar-separate" style="display: '.($bSelectShow ? 'none' : 'inline-block').'"></span>'.
		'<div class="adm-input-wrap adm-calendar-second" style="display: '.($bSelectShow ? 'none' : 'inline-block').';">'.
			'<input type="text" class="adm-input adm-calendar-to" id="'.$sToName.'_calendar_to" name="'.$sToName.'" size="'.($size+5).'" value="'.htmlspecialcharsbx($sToVal).'">'.
			'<span class="adm-calendar-icon" title="'.GetMessage("admin_lib_calend_title").'" onclick="BX.calendar({node:this, field:\''.$sToName.'\', form: \'\', bTime: '.($bTime ? 'true' : 'false').', bHideTime: false});"></span>'.
		'</div>'.
		'<script type="text/javascript">
			window["'.$sFromName.'_bTime"] = '.($bTime ? "true" : "false").';';

		if($bSelectShow)
			$s .='BX.CalendarPeriod.Init(BX("'.$sFromName.'_calendar_from"), BX("'.$sToName.'_calendar_to"), BX("'.$sFromName.'_calendar_period"));';

		$s .='
		</script>
		</div>
		</div>';

		return $s;
	}
}

class CAdminTheme
{
	public static function GetList()
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		global $MESS;

		$aThemes = array();
		$dir = $_SERVER["DOCUMENT_ROOT"].ADMIN_THEMES_PATH;
		if(is_dir($dir) && ($dh = opendir($dir)))
		{
			while (($file = readdir($dh)) !== false)
			{
				if(is_dir($dir."/".$file) && $file!="." && $file!="..")
				{
					$path = ADMIN_THEMES_PATH."/".$file;

					$sLangFile = $_SERVER["DOCUMENT_ROOT"].$path."/lang/".LANGUAGE_ID."/.description.php";
					if(file_exists($sLangFile))
						include($sLangFile);
					else
					{
						$sLangFile = $_SERVER["DOCUMENT_ROOT"].$path."/lang/en/.description.php";
						if(file_exists($sLangFile))
							include($sLangFile);
					}

					$aTheme = array();
					$sDescFile = $_SERVER["DOCUMENT_ROOT"].$path."/.description.php";
					if(file_exists($sDescFile))
						$aTheme = include($sDescFile);
					$aTheme["ID"] = $file;
					if($aTheme["NAME"] == "")
						$aTheme["NAME"] = $file;

					$aThemes[] = $aTheme;
				}
			}
			closedir($dh);
		}
		usort($aThemes, create_function('$a, $b', 'return strcasecmp($a["ID"], $b["ID"]);'));
		return $aThemes;
	}

	public static function GetCurrentTheme()
	{
		$aUserOpt = CUserOptions::GetOption("global", "settings");
		if($aUserOpt["theme_id"] <> "")
		{
			$theme = preg_replace("/[^a-z0-9_.-]/i", "", $aUserOpt["theme_id"]);
			if($theme <> "")
			{
				return $theme;
			}
		}

		return ".default";
	}
}

/**********************************************************************/

class CAdminTabEngine
{
	var $name;
	var $bInited = False;
	var $arEngines = array();
	var $arArgs = array();
	var $bVarsFromForm = False;

	public function CAdminTabEngine($name, $arArgs = array())
	{
		$this->bInited = False;
		$this->name = $name;
		$this->arEngines = array();
		$this->arArgs = $arArgs;

		foreach (GetModuleEvents("main", $this->name, true) as $arEvent)
		{
			$res = ExecuteModuleEventEx($arEvent, array($this->arArgs));
			$this->arEngines[$res["TABSET"]] = $res;
			$this->bInited = True;
		}
	}

	public function SetErrorState($bVarsFromForm = False)
	{
		$this->bVarsFromForm = $bVarsFromForm;
	}

	public function SetArgs($arArgs = array())
	{
		$this->arArgs = $arArgs;
	}

	public function Check()
	{
		if (!$this->bInited)
			return True;

		$result = True;

		foreach ($this->arEngines as $value)
		{
			if (array_key_exists("Check", $value))
			{
				$resultTmp = call_user_func_array($value["Check"], array($this->arArgs));
				if ($result && !$resultTmp)
					$result = False;
			}
		}

		return $result;
	}

	public function Action()
	{
		if (!$this->bInited)
			return True;

		$result = True;

		foreach ($this->arEngines as $value)
		{
			if (array_key_exists("Action", $value))
			{
				$resultTmp = call_user_func_array($value["Action"], array($this->arArgs));
				if ($result && !$resultTmp)
					$result = False;
			}
		}

		return $result;
	}

	public function GetTabs()
	{
		if (!$this->bInited)
			return False;

		$arTabs = array();
		foreach ($this->arEngines as $key => $value)
		{
			if (array_key_exists("GetTabs", $value))
			{
				$arTabsTmp = call_user_func_array($value["GetTabs"], array($this->arArgs));
				if (is_array($arTabsTmp))
				{
					foreach ($arTabsTmp as $key1 => $value1)
						$arTabsTmp[$key1]["DIV"] = $key."_".$arTabsTmp[$key1]["DIV"];

					$arTabs = array_merge($arTabs, $arTabsTmp);
				}
			}
		}

		return $arTabs;
	}

	public function ShowTab($divName)
	{
		if (!$this->bInited)
			return False;

		foreach ($this->arEngines as $key => $value)
		{
			if (SubStr($divName, 0, StrLen($key."_")) == $key."_")
			{
				if (array_key_exists("ShowTab", $value))
					call_user_func_array($value["ShowTab"], array(SubStr($divName, StrLen($key."_")), $this->arArgs, $this->bVarsFromForm));
			}
		}
		return null;
	}
}

class CJSPopup
{
	var $__form_name = 'bx_popup_form';
	var $post_args;
	var $title = '';
	var $bDescriptionStarted = false;
	var $bContentStarted = false;
	var $bButtonsStarted = false;
	var $suffix = '';
	var $jsPopup = 'BX.WindowManager.Get()';
	var $bContentBuffered;
	var $cont_id;

	var $bInited = false;

	/*
	$arConfig = array(
		'TITLE' => 'Popup window title',
		'ARGS' => 'param1=values1&param2=value2', // additional GET arguments for POST query
	)
	*/
	public function CJSPopup($title = '', $arConfig = array())
	{
		if ($title != '') $this->SetTitle($title);
		if (is_set($arConfig, 'TITLE')) $this->SetTitle($arConfig['TITLE']);
		if (is_set($arConfig, 'ARGS')) $this->SetAdditionalArgs($arConfig['ARGS']);
		if (is_set($arConfig, 'SUFFIX') && strlen($arConfig['SUFFIX']) > 0) $this->SetSuffix($arConfig['SUFFIX']);
	}

	public function InitSystem()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if (!$this->bInited && $_REQUEST['bxsender'] != 'core_window_cauthdialog')
		{
			$this->InitScripts();

			$APPLICATION->AddBufferContent(array($this, "_InitSystem"));

			$APPLICATION->ShowHeadStrings();
			$APPLICATION->ShowHeadScripts();

			$this->bInited = true;
		}
	}

	public static function _InitSystem()
	{
		$adminPage = new CAdminPage();

		echo $adminPage->ShowPopupCSS();
		echo $adminPage->ShowScript();

	}

	public static function InitScripts()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		CJSCore::Init(array('admin_interface'));
	}

	public function SetAdditionalArgs($additional_args = '')
	{
		$this->post_args = $additional_args;
	}

	public function SetTitle($title = '')
	{
		$this->title = trim($title);
	}

	public function GetFormName()
	{
		return $this->__form_name;
	}

	public function SetSuffix($suffix)
	{
		$this->suffix = '_'.trim($suffix);
		//$this->jsPopup .= $this->suffix;
		$this->__form_name .= $this->suffix;
	}

	public function ShowTitlebar($title = '')
	{
		$this->InitSystem();

		if ($title == '')
			$title = $this->title;
?>
<script type="text/javascript">top.<?=$this->jsPopup?>.SetTitle('<?echo CUtil::JSEscape($title)?>');</script>
<?
	}

	public function StartDescription($icon = false, $additional = '')
	{
		$this->InitSystem();

		$this->bDescriptionStarted = true;
?>
<script type="text/javascript"><?if ($icon):?>
	<?if (strpos($icon, '/') === false):?>

		<?=$this->jsPopup?>.SetIcon('<?echo CUtil::JSEscape($icon)?>');
	<?else:?>

		<?=$this->jsPopup?>.SetIconFile('<?echo CUtil::JSEscape($icon)?>');
	<?endif;?>
<?endif;?>
<?
			ob_start();
	}

	function EndDescription()
	{
		if ($this->bDescriptionStarted)
		{
			$descr = ob_get_contents();
			ob_end_clean();
?>

<?=$this->jsPopup?>.SetHead('<?echo CUtil::JSEscape($descr)?>');</script>
<?
			//echo '</div></div>';
			$this->bDescriptionStarted = false;
		}
	}

	function StartContent($arAdditional = array())
	{
		$this->InitSystem();

		$this->EndDescription();
		$this->bContentStarted = true;

		if ($arAdditional['buffer'])
		{
			$this->bContentBuffered = true;
			//ob_start();
			$this->cont_id = RandString(10);
			echo '<div id="'.$this->cont_id.'" style="display: none;">';
		}

		echo '<form name="'.$this->__form_name.'">'."\r\n";
		echo bitrix_sessid_post()."\r\n";

		if (is_set($_REQUEST, 'back_url'))
			echo '<input type="hidden" name="back_url" value="'.htmlspecialcharsbx($_REQUEST['back_url']).'" />'."\r\n";
	}

	function EndContent()
	{
		if ($this->bContentStarted)
		{
			echo '</form>'."\r\n";

			$hkInstance = CHotKeys::getInstance();
			$Execs = $hkInstance->GetCodeByClassName("CDialog");
			echo $hkInstance->PrintJSExecs($Execs, "", true, true);

			if ($this->bContentBuffered)
			{
?></div><script type="text/javascript">BX.ready(function() {<?=$this->jsPopup?>.SwapContent(BX('<?echo $this->cont_id?>'))});</script><?
			}

			if (!defined('BX_PUBLIC_MODE') || BX_PUBLIC_MODE == false)
			{
?><script type="text/javascript">BX.adminFormTools.modifyFormElements(<?=$this->jsPopup?>.DIV);</script><?
			}

			$this->bContentStarted = false;
		}
	}

	function StartButtons($additional = '')
	{
		$this->InitSystem();

		$this->EndDescription();
		$this->EndContent();

		$this->bButtonsStarted = true;

		ob_start();
	}

	function EndButtons()
	{
		if ($this->bButtonsStarted)
		{
			$buttons = ob_get_contents();
			ob_end_clean();
?>
		<script type="text/javascript"><?=$this->jsPopup?>.SetButtons('<?echo CUtil::JSEscape($buttons)?>');</script>
<?
			$this->bButtonsStarted = false;
		}
	}

	function ShowStandardButtons($arButtons = array('save', 'cancel'))
	{
		$this->InitSystem();

		if (!is_array($arButtons)) return;

		if ($this->bButtonsStarted)
		{
			$this->EndButtons();
		}

		$arSB = array('save' => $this->jsPopup.'.btnSave', 'cancel' => $this->jsPopup.'.btnCancel', 'close' => $this->jsPopup.'.btnClose');

		foreach ($arButtons as $key => $value)
			if (!$arSB[$value]) unset($arButtons[$key]);
		$arButtons = array_values($arButtons);

?>
<script type="text/javascript"><?=$this->jsPopup?>.SetButtons([<?foreach ($arButtons as $key => $btn) {echo ($key ? ',' : '').$arSB[$btn];}?>]);</script><?
	}

	function ShowValidationError($errortext)
	{
		$this->EndDescription();
		echo '<script>top.'.$this->jsPopup.'.ShowError(\''.CUtil::JSEscape(str_replace(array('<br>', '<br />', '<BR>', '<BR />'), "\r\n", $errortext)).'\')</script>';
	}


	/**
	 * <p>Выводит сообщение об ошибке.</p>
	 *
	 *
	 *
	 *
	 * @param string $message  Строка сообщения.
	 *
	 *
	 *
	 * @param string $css_class = "errortext" Название CSS класса для оформления сообщения.<br>Необязательный
	 * параметр, по умолчанию равен "errortext" (как правило <font
	 * color="red">красного цвета</font>).
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * <b>ShowError</b>("Ошибка!");
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/functions/other/showerror.php
	 * @author Bitrix
	 */
	function ShowError($errortext, $title = '')
	{
		$this->ShowTitlebar($title != "" ? $title : $this->title);

		if (!$this->bDescriptionStarted)
			$this->StartDescription();

		ShowError($errortext);

		$this->ShowStandardButtons(array("close"));
		echo '<script>'.$this->jsPopup.'.AdjustShadow();</script>';
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");

		exit();
	}

	function Close($bReload = true, $back_url = false)
	{
		if (!$back_url && is_set($_REQUEST, 'back_url'))
			$back_url = $_REQUEST['back_url'];

		echo '<script>';
		echo 'top.'.$this->jsPopup.'.Close(); ';

		if ($bReload)
		{
			echo 'top.BX.showWait(); ';
			echo "top.BX.reload('".CUtil::JSEscape($back_url)."', true);";
		}
		echo '</script>';
		die();
	}
}

class CJSPopupOnPage extends CJSPopup
{
	public static function InitSystem() {} // this SHOULD be empty!
}

class CAdminFormSettings
{
	public static function getTabsArray($formId)
	{
		$arCustomTabs = array();
		$customTabs = CUserOptions::GetOption("form", $formId);
		if($customTabs && $customTabs["tabs"])
		{
			$arTabs = explode("--;--", $customTabs["tabs"]);
			foreach($arTabs as $customFields)
			{
				if ($customFields == "")
					continue;

				$arCustomFields = explode("--,--", $customFields);
				$arCustomTabID = "";
				$arCustomTabName = "";
				foreach($arCustomFields as $customField)
				{
					if($arCustomTabID == "")
					{
						list($arCustomTabID, $arCustomTabName) = explode("--#--", $customField);
						$arCustomTabs[$arCustomTabID] = array(
							"TAB" => $arCustomTabName,
							"FIELDS" => array(),
						);
					}
					else
					{
						list($arCustomFieldID, $arCustomFieldName) = explode("--#--", $customField);
						$arCustomFieldName = ltrim($arCustomFieldName, defined("BX_UTF")? "* -\xa0\xc2": "* -\xa0");
						$arCustomTabs[$arCustomTabID]["FIELDS"][$arCustomFieldID] = $arCustomFieldName;
					}
				}
			}
		}
		return $arCustomTabs;
	}

	public static function setTabsArray($formId, $arCustomTabs, $common = false, $userID = false)
	{
		$option = "";
		if (is_array($arCustomTabs))
		{
			foreach($arCustomTabs as $arCustomTabID => $arTab)
			{
				if (is_array($arTab) && isset($arTab["TAB"]))
				{
					$option .= $arCustomTabID.'--#--'.$arTab["TAB"];
					if (isset($arTab["FIELDS"]) && is_array($arTab["FIELDS"]))
					{
						foreach ($arTab["FIELDS"] as $arCustomFieldID => $arCustomFieldName)
						{
							$option .= '--,--'.$arCustomFieldID.'--#--'.$arCustomFieldName;
						}
					}
				}
				$option .= '--;--';
			}
		}
		$customTabs = CUserOptions::SetOption("form", $formId, array("tabs" => $option), $common, $userID);
	}
}


class CAdminForm extends CAdminTabControl
{
	var $arParams = array();
	var $arFields = array();
	var $group = "";
	var $group_ajax = false;
	var $arFieldValues = array();
	var $sPrologContent = "";
	var $sEpilogContent = "";
	var $arButtonsParams = false;
	var $sButtonsContent = "";

	var $arSavedTabs = array();
	var $arSystemTabs = array();
	var $arSystemFields = array();

	var $arCustomLabels = array();
	var $bCustomFields = false;
	var $sCurrentLabel = "";
	var $bCurrentReq = false;

	var $bShowSettings = true;

	public function CAdminForm($name, $tabs, $bCanExpand = true, $bDenyAutosave = false)
	{
		parent::CAdminTabControl($name, $tabs, $bCanExpand, $bDenyAutosave);
		$this->tabIndex = 0;
		foreach($this->tabs as $i => $arTab)
			$this->tabs[$i]["FIELDS"] = array();

		//Parse customized labels
		$this->arCustomLabels = array();
		foreach (CAdminFormSettings::getTabsArray($this->name) as $tab_id => $arTab)
		{
			foreach ($arTab["FIELDS"] as $customID => $customName)
			{
				$this->arCustomLabels[$customID] = $customName;
			}
		}
		ob_start();
	}

	public function SetShowSettings($v)
	{
		$this->bShowSettings = $v;
	}

	public static function ShowSettings()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $USER;

		$APPLICATION->RestartBuffer();

		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");

		require($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/interface/settings_admin_form.php");

		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");

		die();
	}

	public function SetFieldsValues($bVarsFromForm, $db_record, $default_values)
	{
		foreach($default_values as $key=>$value)
			$this->SetFieldValue($key, $bVarsFromForm, $db_record, $value);
	}

	public function SetFieldValue($field_name, $bVarsFromForm, $db_record, $default_value = false)
	{
		if($bVarsFromForm)
		{
			if(array_key_exists($field_name, $_REQUEST))
				$this->arFieldValues[$field_name] = $_REQUEST[$field_name];
			else
				$this->arFieldValues[$field_name] = $default_value;
		}
		else
		{
			if(is_array($db_record) && array_key_exists($field_name, $db_record) && isset($db_record[$field_name]))
				$this->arFieldValues[$field_name] = $db_record[$field_name];
			else
				$this->arFieldValues[$field_name] = $default_value;
		}
	}

	public function GetFieldValue($field_name)
	{
		return $this->arFieldValues[$field_name];
	}

	public function GetHTMLFieldValue($field_name)
	{
		return htmlspecialcharsbx($this->arFieldValues[$field_name]);
	}

	public function GetHTMLFieldValueEx($field_name)
	{
		return htmlspecialcharsex($this->arFieldValues[$field_name]);
	}

	public function GetFieldLabel($id)
	{
		return $this->arFields[$id]["content"];
	}

	public function ShowTabButtons()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$s = '';
		if (!$this->bPublicMode)
		{
			if ($this->bShowSettings)
			{
				$link = DeleteParam(array("mode"));
				$link = $APPLICATION->GetCurPage()."?mode=settings".($link <> ""? "&".$link:"");

				$aAdditionalMenu = array();

				$aAdditionalMenu[] = array(
					"TEXT"=>GetMessage("admin_lib_menu_settings"),
					"TITLE"=>GetMessage("admin_lib_context_sett_title"),
					"ONCLICK"=>$this->name.".ShowSettings('".htmlspecialcharsex(CUtil::JSEscape($link))."')",
					"GLOBAL_ICON"=>"adm-menu-setting"
				);

				if($this->bCustomFields)
				{
					if(is_array($_SESSION["ADMIN_CUSTOM_FIELDS"]) && array_key_exists($this->name, $_SESSION["ADMIN_CUSTOM_FIELDS"]))
					{
						$aAdditionalMenu[] = array(
							"TEXT" => GetMessage("admin_lib_sett_sett_enable_text"),
							"TITLE" => GetMessage("admin_lib_sett_sett_enable"),
							"ONCLICK" => $this->name.'.EnableSettings();',
							"ICON" => 'custom-fields-on',
						);
					}
					else
					{
						$aAdditionalMenu[] = array(
							"TEXT" => GetMessage("admin_lib_sett_sett_disable_text"),
							"TITLE" => GetMessage("admin_lib_sett_sett_disable"),
							"ONCLICK" => $this->name.'.DisableSettings();',
							"ICON" => 'custom-fields-off'
						);

					}
				}

				if (count($aAdditionalMenu) > 1)
				{
					$sMenuUrl = "BX.adminShowMenu(this, ".htmlspecialcharsbx(CAdminPopup::PhpToJavaScript($aAdditionalMenu)).", {active_class: 'bx-settings-btn-active'});";
					$bCustomFieldsOff = is_array($_SESSION["ADMIN_CUSTOM_FIELDS"]) && array_key_exists($this->name, $_SESSION["ADMIN_CUSTOM_FIELDS"]);

					$s .= '<span id="'.$this->name.'_settings_btn" class="adm-detail-settings adm-detail-settings-arrow'.($bCustomFieldsOff ? '' : ' adm-detail-settings-active').'" onclick="'.$sMenuUrl.'"></span>';
				}
				else
				{
					$s .= '<a class="adm-detail-settings" href="javascript:void(0)" onclick="'.$aAdditionalMenu[0]['ONCLICK'].'"></a>';

				}
			}
		}

		return $s.parent::ShowTabButtons();
	}

	public function Begin($arParams = array())
	{
		$this->tabIndex = -1;
		if(is_array($arParams))
			$this->arParams = $arParams;
		else
			$this->arParams = array();
	}

	public function BeginNextFormTab()
	{
		if($this->tabIndex >= count($this->tabs))
			return;

		$this->tabIndex++;
		while(
			isset($this->tabs[$this->tabIndex])
			&& array_key_exists("CUSTOM", $this->tabs[$this->tabIndex])
			&& $this->tabs[$this->tabIndex]["CUSTOM"] == "Y"
		)
		{
			$this->tabIndex++;
		}
	}

	public function Show()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		//Save form defined tabs
		$this->arSavedTabs = $this->tabs;
		$this->arSystemTabs = array();
		foreach($this->tabs as $arTab)
		{
			$this->arSystemTabs[$arTab["DIV"]] = $arTab;
			if(is_array($arTab["FIELDS"]))
				foreach($arTab["FIELDS"] as $arField)
					$this->arFields[$arField["id"]] = $arField;
		}
		//Save form defined fields
		$this->arSystemFields = $this->arFields;

		$arCustomTabs = CAdminFormSettings::getTabsArray($this->name);
		if (!empty($arCustomTabs))
		{
			$this->bCustomFields = true;
			$this->tabs = array();
			foreach($arCustomTabs as $tab_id => $arTab)
			{
				if(array_key_exists($tab_id, $this->arSystemTabs))
				{
					$arNewTab = $this->arSystemTabs[$tab_id];
					$arNewTab["TAB"] = $arTab["TAB"];
					$arNewTab["FIELDS"] = array();
				}
				else
				{
					$arNewTab = array(
						"DIV" => $tab_id,
						"TAB" => $arTab["TAB"],
						"ICON" => "main_user_edit",
						"TITLE" => "",
						"FIELDS" => array(),
					);
				}

				foreach($arTab["FIELDS"] as $field_id => $content)
				{
					if(array_key_exists($field_id, $this->arSystemFields))
					{
						$arNewField = $this->arSystemFields[$field_id];
						$arNewField["content"] = $content;
					}
					elseif(strlen($content) > 0)
					{
						$arNewField = array(
							"id" => $field_id,
							"content" => $content,
							"html" => '<td colspan="2">'.htmlspecialcharsex($content).'</td>',
							"delimiter" => true,
						);
					}
					else
					{
						$arNewField = false;
					}

					if(is_array($arNewField))
					{
						$this->arFields[$field_id] = $arNewField;
						$arNewTab["FIELDS"][] = $arNewField;
					}
				}

				$this->tabs[] = $arNewTab;
			}
		}

		if($_REQUEST["mode"] == "settings")
		{
			ob_end_clean();
			$this->ShowSettings($this->arFields);
			die();
		}
		else
		{
			ob_end_flush();
		}

		if(!is_array($_SESSION["ADMIN_CUSTOM_FIELDS"]))
			$_SESSION["ADMIN_CUSTOM_FIELDS"] = array();
		$arDisabled = CUserOptions::GetOption("form", $this->name."_disabled", "N");
		if(is_array($arDisabled) && $arDisabled["disabled"] === "Y")
		{
			$_SESSION["ADMIN_CUSTOM_FIELDS"][$this->name] = true;
			$this->tabs = $this->arSavedTabs;
			$this->arFields = $this->arSystemFields;
		}
		else
		{
			unset($_SESSION["ADMIN_CUSTOM_FIELDS"][$this->name]);
		}

		if(isset($_REQUEST[$this->name."_active_tab"]))
			$this->selectedTab = $_REQUEST[$this->name."_active_tab"];
		else
			$this->selectedTab = $this->tabs[0]["DIV"];

		//To show
		$arHiddens = $this->arFields;
		echo $this->sPrologContent;
		if(array_key_exists("FORM_ACTION", $this->arParams))
			$action = htmlspecialcharsbx($this->arParams["FORM_ACTION"]);
		else
			$action = htmlspecialcharsbx($APPLICATION->GetCurPage());
		echo '<form method="POST" Action="'.$action.'"  ENCTYPE="multipart/form-data" id="'.$this->name.'_form" name="'.$this->name.'_form"'.($this->arParams["FORM_ATTRIBUTES"] <> ''? ' '.$this->arParams["FORM_ATTRIBUTES"]:'').'>';

		$htmlGroup = "";
		if($this->group)
		{
			if (!empty($arCustomTabs))
			{
				foreach($this->tabs as $arTab)
				{
					if(is_array($arTab["FIELDS"]))
					{
						foreach($arTab["FIELDS"] as $arField)
						{
							if(
								(strlen($this->arFields[$arField["id"]]["custom_html"]) > 0)
								|| (strlen($this->arFields[$arField["id"]]["html"]) > 0)
							)
							{
								$p = array_search($arField["id"], $this->arFields[$this->group]["group"]);
								if($p !== false)
									unset($this->arFields[$this->group]["group"][$p]);
							}
						}
					}
				}
			}

			if(!empty($this->arFields[$this->group]["group"]))
			{
				$htmlGroup .= '<tr class="heading" id="tr_'.$this->arFields[$this->group]["id"].'">'
					.$this->arFields[$this->group]["html"].'</tr>'
					."\n";
			}
		}

		$this->OnAdminTabControlBegin();
		$this->tabIndex = 0;
		while($this->tabIndex < count($this->tabs))
		{
			ob_start();//Start of the tab content
			$arTab = $this->tabs[$this->tabIndex];
			if(is_array($arTab["FIELDS"]))
			{
				foreach($arTab["FIELDS"] as $arField)
				{
					if(isset($this->arFields[$arField["id"]]["group"]))
					{
						if(!empty($this->arFields[$arField["id"]]["group"]))
						{
							echo $htmlGroup;
							foreach($this->arFields[$arField["id"]]["group"] as $p)
							{
								if($this->arFields[$p]["custom_html"])
									echo preg_replace("/^\\s*<tr/is", "<tr class=\"bx-in-group\"", $this->arFields[$p]["custom_html"]);
								elseif($this->arFields[$p]["html"] && !$this->arFields[$p]["delimiter"])
									echo '<tr class="bx-in-group" '.($this->arFields[$p]["valign"] <> ''? ' valign="'.$this->arFields[$p]["valign"].'"':'').' id="tr_'.$p.'">', $this->arFields[$p]["html"], "</tr>\n";
								$this->arFields[$p] = array();
								unset($arHiddens[$this->arFields[$p]["id"]]);
							}
						}
					}
					elseif(strlen($this->arFields[$arField["id"]]["custom_html"]) > 0)
					{
						if($this->group_ajax)
							echo preg_replace("#<script[^>]*>.*?</script>#im".BX_UTF_PCRE_MODIFIER, "", $this->arFields[$arField["id"]]["custom_html"]);
						else
							echo $this->arFields[$arField["id"]]["custom_html"];
					}
					elseif(strlen($this->arFields[$arField["id"]]["html"]) > 0)
					{
						$rowClass = (
							array_key_exists("rowClass", $this->arFields[$arField["id"]])
							? ' class="'.$this->arFields[$arField["id"]]["rowClass"].'"'
							: ''
						);

						if($this->arFields[$arField["id"]]["delimiter"])
							echo '<tr class="heading" id="tr_'.$arField["id"].'"'.$rowClass.'>';
						else
							echo '<tr'.($this->arFields[$arField["id"]]["valign"] <> ''? ' valign="'.$this->arFields[$arField["id"]]["valign"].'"':'').' id="tr_'.$arField["id"].'"'.$rowClass.'>';
						echo $this->arFields[$arField["id"]]["html"].'</tr>'."\n";
					}
					unset($arHiddens[$arField["id"]]);
				}
			}
			$tabContent = ob_get_contents();
			ob_end_clean(); //Dispose tab content

			if ($tabContent == "")
			{
				array_splice($this->tabs, $this->tabIndex, 1); // forget about tab
			}
			else
			{

				$this->tabs[$this->tabIndex]["CONTENT"] = $tabContent;
				$this->tabIndex++;
			}
		}

		//sometimes form settings are incorrect but we must show required fields
		$requiredFields = '';
		foreach($arHiddens as $arField)
		{
			if($arField["required"])
			{
				if(strlen($this->arFields[$arField["id"]]["custom_html"]) > 0)
				{
					$requiredFields .= $this->arFields[$arField["id"]]["custom_html"];
				}
				elseif(strlen($this->arFields[$arField["id"]]["html"]) > 0)
				{
					if($this->arFields[$arField["id"]]["delimiter"])
						$requiredFields .= '<tr class="heading">';
					else
						$requiredFields .= '<tr>';
					$requiredFields .= $this->arFields[$arField["id"]]["html"].'</tr>';
				}
				unset($arHiddens[$arField["id"]]);
			}
		}
		if($requiredFields <> '')
		{
			$this->tabs[] = array(
				"CONTENT" => $requiredFields,
				"DIV" => "bx_req",
				"TAB" => GetMessage("admin_lib_required"),
				"TITLE" => GetMessage("admin_lib_required"),
			);
		}

		parent::Begin();

		while($this->tabIndex < count($this->tabs))
		{
			$this->BeginNextTab();
			echo $this->tabs[$this->tabIndex]["CONTENT"];
		}

		parent::Buttons($this->arButtonsParams);
		echo $this->sButtonsContent;

		$this->End();
		echo $this->sEpilogContent;

		echo '<span class="bx-fields-hidden">';
		foreach($arHiddens as $arField)
		{
			echo $arField["hidden"];
		}
		echo '</span>';

		echo '</form>';
	}

	public function GetName()
	{
		return $this->name;
	}

	public function GetFormName()
	{
		return $this->name."_form";
	}

	public function GetCustomLabel($id, $content)
	{
		$bColumnNeeded = substr($content, -1)==":";

		if($id === false)
			return $this->sCurrentLabel;
		elseif(array_key_exists($id, $this->arCustomLabels))
			return $this->arCustomLabels[$id].($bColumnNeeded? ":": "");
		else
			return $content;
	}

	public function GetCustomLabelHTML($id = false, $content = "")
	{
		$bColumnNeeded = substr($content, -1)==":";

		if($id === false)
			return ($this->bCurrentReq? '<span class="adm-required-field">'.htmlspecialcharsex($this->sCurrentLabel).'</span>': htmlspecialcharsex($this->sCurrentLabel));
		elseif(array_key_exists($id, $this->arCustomLabels))
			return ($this->arFields[$id]["required"]? '<span class="adm-required-field">'.htmlspecialcharsex($this->arCustomLabels[$id]).($bColumnNeeded? ":": "").'</span>': htmlspecialcharsex($this->arCustomLabels[$id]).($bColumnNeeded? ":": ""));
		else
			return ($this->tabs[$this->tabIndex]["FIELDS"][$id]["required"]? '<span class="adm-required-field">'.htmlspecialcharsex($content).'</span>': htmlspecialcharsex($content));
	}

	public function ShowWarnings($form, $messages, $aFields=false)
	{
		parent::ShowWarnings($this->name.'_form', $messages, $aFields);
	}

	public static function BeginPrologContent()
	{
		ob_start();
	}

	public function EndPrologContent()
	{
		$this->sPrologContent = ob_get_contents();
		ob_end_clean();
	}

	public static function BeginEpilogContent()
	{
		ob_start();
	}

	public function EndEpilogContent()
	{
		$this->sEpilogContent = ob_get_contents();
		ob_end_clean();
	}

	public function AddFieldGroup($id, $content, $arFields, $bAjax = false)
	{
		$this->group = $id;
		$this->group_ajax = $bAjax;
		$this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
			"id" => $id,
			"content" => $content,
			"group" => $arFields,
			"html" => '<td colspan="2">'.$this->GetCustomLabelHTML($id, $content).'</td>',
		);
	}

	public function AddSection($id, $content, $required = false)
	{
		$this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
			"id" => $id,
			"required" => $required,
			"delimiter" => true,
			"content" => $content,
			"html" => '<td colspan="2">'.($required? '<span class="adm-required-field">'.$this->GetCustomLabelHTML($id, $content).'</span>': $this->GetCustomLabelHTML($id, $content)).'</td>',
		);
	}

	public function AddViewField($id, $content, $html, $required=false)
	{
		$this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
			"id" => $id,
			"required" => $required,
			"content" => $content,
			"html" => ($html <> ''? '<td width="40%">'.$this->GetCustomLabelHTML($id, $content).'</td><td>'.$html.'</td>' : ''),
		);
	}

	public function AddDropDownField($id, $content, $required, $arSelect, $value=false, $arParams=array())
	{
		if($value === false)
			$value = $this->arFieldValues[$id];

		$html = '<select name="'.$id.'"';
		foreach($arParams as $param)
			$html .= ' '.$param;
		$html .= '>';

		foreach($arSelect as $key => $val)
			$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($value == $key? ' selected': '').'>'.htmlspecialcharsex($val).'</option>';
		$html .= '</select>';

		$this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
			"id" => $id,
			"required" => $required,
			"content" => $content,
			"html" => '<td width="40%">'.($required? '<span class="adm-required-field">'.$this->GetCustomLabelHTML($id, $content).'</span>': $this->GetCustomLabelHTML($id, $content)).'</td><td>'.$html.'</td>',
			"hidden" => '<input type="hidden" name="'.$id.'" value="'.htmlspecialcharsbx($value).'">',
		);
	}

	public function AddEditField($id, $content, $required, $arParams = array(), $value = false)
	{
		if($value === false)
			$value = htmlspecialcharsbx($this->arFieldValues[$id]);
		$html = '<input type="text" name="'.$id.'" value="'.$value.'"';
		if(intval($arParams["size"]) > 0)
			$html .= ' size="'.intval($arParams["size"]).'"';
		if(intval($arParams["maxlength"]) > 0)
			$html .= ' maxlength="'.intval($arParams["maxlength"]).'"';
		$html .= '>';

		$this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
			"id" => $id,
			"required" => $required,
			"content" => $content,
			"html" => '<td width="40%">'.($required? '<span class="adm-required-field">'.$this->GetCustomLabelHTML($id, $content).'</span>': $this->GetCustomLabelHTML($id, $content)).'</td><td>'.$html.'</td>',
			"hidden" => '<input type="hidden" name="'.$id.'" value="'.$value.'">',
		);
	}

	public function AddTextField($id, $label, $value, $arParams=array(), $required=false)
	{
		$html = '<textarea name="'.$id.'"';
		if(intval($arParams["cols"]) > 0)
			$html .= ' cols="'.intval($arParams["cols"]).'"';
		if(intval($arParams["rows"]) > 0)
			$html .= ' rows="'.intval($arParams["rows"]).'"';
		$html .= '>'.$value.'</textarea>';

		$this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
			"id" => $id,
			"required" => $required,
			"content" => $label,
			"html" => '<td width="40%">'.($required? '<span class="adm-required-field">'.$this->GetCustomLabelHTML($id, $label).'</span>': $this->GetCustomLabelHTML($id, $label)).'</td><td>'.$html.'</td>',
			"hidden" => '<input type="hidden" name="'.$id.'" value="'.$value.'">',
			"valign" => "top",
		);
	}

	public function AddCalendarField($id, $label, $value, $required=false)
	{
		$html = CalendarDate($id, $value, $this->GetFormName());

		$this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
			"id" => $id,
			"required" => $required,
			"content" => $label,
			"html" => '<td width="40%">'.($required? '<span class="adm-required-field">'.$this->GetCustomLabelHTML($id, $label).'</span>': $this->GetCustomLabelHTML($id, $label)).'</td><td>'.$html.'</td>',
			"hidden" => '<input type="hidden" name="'.$id.'" value="'.$value.'">',
		);
	}

	public function AddCheckBoxField($id, $content, $required, $value, $checked, $arParams=array())
	{
		$html = '<input type="checkbox" name="'.$id.'" value="'.htmlspecialcharsbx($value).'"'.($checked? ' checked': '');
		foreach($arParams as $param)
			$html .= ' '.$param;
		$html .= '>';

		$this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
			"id" => $id,
			"required" => $required,
			"content" => $content,
			"html" => '<td width="40%">'.($required? '<span class="adm-required-field">'.$this->GetCustomLabelHTML($id, $content).'</span>': $this->GetCustomLabelHTML($id, $content)).'</td><td>'.$html.'</td>',
			"hidden" => '<input type="hidden" name="'.$id.'" value="'.htmlspecialcharsbx($value).'">',
		);
	}

	public function AddFileField($id, $label, $value, $arParams=array(), $required=false)
	{
		$arDefParams = array("iMaxW"=>150, "iMaxH"=>150, "sParams"=>"border=0", "strImageUrl"=>"", "bPopup"=>true, "sPopupTitle"=>false);
		foreach($arDefParams as $key=>$val)
			if(!array_key_exists($key, $arParams))
				$arParams[$key] = $val;

		$html = CFile::InputFile($id, 20, $value);
		if($value <> '')
			$html .= '<div class="adm-detail-file-image">'.CFile::ShowImage($value, $arParams["iMaxW"], $arParams["iMaxH"], $arParams["sParams"], $arParams["strImageUrl"], $arParams["bPopup"], $arParams["sPopupTitle"])."</div>";

		$this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
			"id" => $id,
			"required" => $required,
			"content" => $label,
			"html" => '<td width="40%">'.($required? '<span class="adm-required-field">'.$this->GetCustomLabelHTML($id, $label).'</span>': $this->GetCustomLabelHTML($id, $label)).'</td><td>'.$html.'</td>',
			"valign" => "top",
			"rowClass" => "adm-detail-file-row"
		);
	}

	public function BeginCustomField($id, $content, $required = false)
	{
		$this->sCurrentLabel = $this->GetCustomLabel($id, $content);
		$this->bCurrentReq = $required;
		$this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
			"id" => $id,
			"required" => $required,
			"content" => $content,
		);

		ob_start();
	}

	public function EndCustomField($id, $hidden = "")
	{
		$html = ob_get_contents();
		ob_end_clean();

		$this->tabs[$this->tabIndex]["FIELDS"][$id]["custom_html"] = $html;
		$this->tabs[$this->tabIndex]["FIELDS"][$id]["hidden"] = $hidden;
	}

	public function ShowUserFields($PROPERTY_ID, $ID, $bVarsFromForm)
	{
		/**
		 * @global CMain $APPLICATION
		 * @global CUserTypeManager $USER_FIELD_MANAGER
		 */
		global $USER_FIELD_MANAGER, $APPLICATION;

		if($USER_FIELD_MANAGER->GetRights($PROPERTY_ID) >= "W")
		{
			$this->BeginCustomField("USER_FIELDS_ADD", GetMessage("admin_lib_add_user_field"));
			?>
				<tr>
					<td colspan="2" align="left">
						<a href="/bitrix/admin/userfield_edit.php?lang=<?echo LANGUAGE_ID?>&amp;ENTITY_ID=<?echo urlencode($PROPERTY_ID)?>&amp;back_url=<?echo urlencode($APPLICATION->GetCurPageParam()."&tabControl_active_tab=user_fields_tab")?>"><?echo $this->GetCustomLabelHTML()?></a>
					</td>
				</tr>
			<?
			$this->EndCustomField("USER_FIELDS_ADD", '');
		}

		$arUserFields = $USER_FIELD_MANAGER->GetUserFields($PROPERTY_ID, $ID, LANGUAGE_ID);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$arUserField["VALUE_ID"] = intval($ID);
			if(array_key_exists($FIELD_NAME, $this->arCustomLabels))
				$strLabel = $this->arCustomLabels[$FIELD_NAME];
			else
				$strLabel = $arUserField["EDIT_FORM_LABEL"]? $arUserField["EDIT_FORM_LABEL"]: $arUserField["FIELD_NAME"];
			$arUserField["EDIT_FORM_LABEL"] = $strLabel;

			$this->BeginCustomField($FIELD_NAME, $strLabel, $arUserField["MANDATORY"]=="Y");

			if(isset($_REQUEST['def_'.$FIELD_NAME]))
				$arUserField['SETTINGS']['DEFAULT_VALUE'] = $_REQUEST['def_'.$FIELD_NAME];

			echo $USER_FIELD_MANAGER->GetEditFormHTML($bVarsFromForm, $GLOBALS[$FIELD_NAME], $arUserField);

			$form_value = $GLOBALS[$FIELD_NAME];
			if(!$bVarsFromForm)
				$form_value = $arUserField["VALUE"];
			elseif($arUserField["USER_TYPE"]["BASE_TYPE"]=="file")
				$form_value = $GLOBALS[$arUserField["FIELD_NAME"]."_old_id"];

			$hidden = "";
			if(is_array($form_value))
			{
				foreach($form_value as $value)
					$hidden .= '<input type="hidden" name="'.$FIELD_NAME.'[]" value="'.htmlspecialcharsbx($value).'">';
			}
			else
			{
				$hidden .= '<input type="hidden" name="'.$FIELD_NAME.'" value="'.htmlspecialcharsbx($form_value).'">';
			}
			$this->EndCustomField($FIELD_NAME, $hidden);
		}
	}

	public function Buttons($aParams=false, $additional_html="")
	{
		if($aParams === false)
			$this->arButtonsParams = false;
		else
			$this->arButtonsParams = $aParams;
		$this->sButtonsContent = $additional_html;
	}

	public function ButtonsPublic($arJSButtons = false)
	{
		if ($this->bPublicMode)
		{
			if (strlen($_REQUEST['from_module']))
				$this->sButtonsContent .= '<input type="hidden" name="from_module" value="'.htmlspecialcharsbx($_REQUEST['from_module']).'" />';

			ob_start();
			if ($arJSButtons === false)
			{
				echo '
<input type="hidden" name="bxpublic" value="Y" /><input type="hidden" name="save" value="Y" />
<script type="text/javascript">'.$this->publicObject.'.SetButtons(['.$this->publicObject.'.btnSave, '.$this->publicObject.'.btnCancel]);</script>
';
			}
			elseif (is_array($arJSButtons))
			{
				$arJSButtons = array_values($arJSButtons);
				echo '
<input type="hidden" name="bxpublic" value="Y" />
<script type="text/javascript">'.$this->publicObject.'.SetButtons([
';
				foreach ($arJSButtons as $key => $btn)
				{
					if (substr($btn, 0, 1) == '.')
						$btn = $this->publicObject.$btn;
					echo $key ? ',' : '', $btn, "\r\n"; // NO JSESCAPE HERE! string must contain valid js object
				}
				echo '
]);</script>
';
			}
			$this->sButtonsContent .= ob_get_clean();
		}
	}
}

class CAdminUtil
{
	public static function dumpVars($vars, $arExclusions = array())
	{
		$result = "";
		if (is_array($vars))
		{
			foreach ($vars as $varName => $varValue)
			{
				if (in_array($varName, $arExclusions))
					continue;

				$result .= self::dumpVar($varName, $varValue);
			}
		}

		return $result;
	}

	private static function dumpVar($varName, $varValue, $varStack = array())
	{
		$result = "";
		if (is_array($varValue))
		{
			foreach ($varValue as $key => $value)
			{
				$result .= self::dumpVar($key, $value, array_merge($varStack ,array($varName)));
			}
		}
		else
		{
			$htmlName = $varName;
			if (count($varStack) > 0)
			{
				$htmlName = $varStack[0];
				for ($i = 1, $intCount = count($varStack); $i < $intCount; $i++)
					$htmlName .= "[".$varStack[$i]."]";
				$htmlName .= "[".$varName."]";
			}

			return '<input type="hidden" name="'.htmlspecialcharsbx($htmlName).'" value="'.htmlspecialcharsbx($varValue).'">';
		}

		return $result;
	}
}

function ShowJSHint($text, $arParams=false)
{
	if (strlen($text) <= 0)
		return '';

	CJSCore::Init();

	$res = '<img src="/bitrix/images/1.gif" onload="BX.hint_replace(this, \''.htmlspecialcharsbx(CUtil::JSEscape($text)).'\')" />';

	if (isset($arParams['return']) && $arParams['return'])
		return $res;
	echo $res;
	return null;
}
