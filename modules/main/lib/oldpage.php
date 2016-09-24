<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

class OldPage extends \Bitrix\Main\Page
{
	static public function startRequest()
	{
		\CMain::prologActions();
	}

	static public function preInit()
	{
		//! вызывается только для публичных страниц только если prolog.php подключался (нет, если prolog_before.php)
			if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.enabled"))
			{
				// define("BITRIX_STATIC_PAGES", true);
				require_once(dirname(__FILE__)."/../classes/general/cache_html.php");
				\CHTMLPagesCache::startCaching();
			}
		//!

		// define("START_EXEC_PROLOG_BEFORE_1", microtime());
		$GLOBALS["BX_STATE"] = "PB";

		if(isset($_REQUEST["BX_STATE"])) unset($_REQUEST["BX_STATE"]);
		if(isset($_GET["BX_STATE"])) unset($_GET["BX_STATE"]);
		if(isset($_POST["BX_STATE"])) unset($_POST["BX_STATE"]);
		if(isset($_COOKIE["BX_STATE"])) unset($_COOKIE["BX_STATE"]);
		if(isset($_FILES["BX_STATE"])) unset($_FILES["BX_STATE"]);

		// вызывается только для админских страниц
		if(defined("ADMIN_SECTION") && ADMIN_SECTION===true)
		{
			// define("NEED_AUTH", true);

			if (isset($_REQUEST['bxpublic']) && $_REQUEST['bxpublic'] == 'Y' && !defined('BX_PUBLIC_MODE'))
				// define('BX_PUBLIC_MODE', 1);
		}
		//

		// <start.php>
		if(!isset($USER)) {global $USER;}
		if(!isset($APPLICATION)) {global $APPLICATION;}
		if(!isset($DB)) {global $DB;}

		error_reporting(E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE);

		// define("START_EXEC_TIME", microtime(true));
		// define("B_PROLOG_INCLUDED", true);

		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/version.php");
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/tools.php");

		if(version_compare(PHP_VERSION, "5.0.0")>=0 && @ini_get_bool("register_long_arrays") != true)
		{
			$GLOBALS["HTTP_POST_FILES"] = $_FILES;
			$GLOBALS["HTTP_SERVER_VARS"] = $_SERVER;
			$GLOBALS["HTTP_GET_VARS"] = $_GET;
			$GLOBALS["HTTP_POST_VARS"] = $_POST;
			$GLOBALS["HTTP_COOKIE_VARS"] = $_COOKIE;
			$GLOBALS["HTTP_ENV_VARS"] = $_ENV;
		}

		UnQuoteAll();
		FormDecode();
	}


	static public function init()
	{
		//language independed classes
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/punycode.php");
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/charset_converter.php");
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$GLOBALS["DBType"]."/main.php");	//main class
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$GLOBALS["DBType"]."/option.php");	//options and settings class
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/cache.php");	//various cache classes
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/cache_html.php");	//html cache class support
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/module.php");

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/virtual_io.php");
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/virtual_file.php");

		//define global application object
		$GLOBALS["APPLICATION"] = new \CMain;

		if(defined("SITE_ID"))
			// define("LANG", SITE_ID);

		if(defined("LANG"))
		{
			if(defined("ADMIN_SECTION") && ADMIN_SECTION===true)
				$db_lang = CLangAdmin::getByID(LANG);
			else
				$db_lang = CLang::getByID(LANG);

			$arLang = $db_lang->fetch();
		}
		else
		{
			$arLang = $GLOBALS["APPLICATION"]->getLang();
			define("LANG", $arLang["LID"]);
		}

		$lang = $arLang["LID"];
		// define("SITE_ID", $arLang["LID"]);
		// define("SITE_DIR", $arLang["DIR"]);
		// define("SITE_SERVER_NAME", $arLang["SERVER_NAME"]);
		// define("SITE_CHARSET", $arLang["CHARSET"]);
		// define("FORMAT_DATE", $arLang["FORMAT_DATE"]);
		// define("FORMAT_DATETIME", $arLang["FORMAT_DATETIME"]);
		// define("LANG_DIR", $arLang["DIR"]);
		// define("LANG_CHARSET", $arLang["CHARSET"]);
		// define("LANG_ADMIN_LID", $arLang["LANGUAGE_ID"]);
		// define("LANGUAGE_ID", $arLang["LANGUAGE_ID"]);

		$GLOBALS["APPLICATION"]->reinitPath();

		//global var, is used somewhere
		$GLOBALS["sDocPath"] = $GLOBALS["APPLICATION"]->getCurPage();

		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/tools.php");
		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/date_format.php");
		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/database.php");
		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/main.php");
		IncludeModuleLangFile(__FILE__);

		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/filter_tools.php");
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/ajax_tools.php");
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/urlrewriter.php");

		\CModule::addAutoloadClasses(
			"main",
			array(
				"CBitrixComponent" => "classes/general/component.php",
				"CComponentEngine" => "classes/general/component_engine.php",
				"CComponentAjax" => "classes/general/component_ajax.php",
				"CBitrixComponentTemplate" => "classes/general/component_template.php",
				"CComponentUtil" => "classes/general/component_util.php",
				"CControllerClient" => "classes/general/controller_member.php",
				"PHPParser" => "classes/general/php_parser.php",
				"CDiskQuota" => "classes/".$GLOBALS["DBType"]."/quota.php",
				"CEventLog" => "classes/general/event_log.php",
				"CEventMain" => "classes/general/event_log.php",
				"CAdminFileDialog" => "classes/general/file_dialog.php",
				"WLL_User" => "classes/general/liveid.php",
				"WLL_ConsentToken" => "classes/general/liveid.php",
				"WindowsLiveLogin" => "classes/general/liveid.php",
				"CAllFile" => "classes/general/file.php",
				"CFile" => "classes/".$GLOBALS["DBType"]."/file.php",
				"CTempFile" => "classes/general/file_temp.php",
				"CFavorites" => "classes/".$GLOBALS["DBType"]."/favorites.php",
				"CUserOptions" => "classes/general/favorites.php",
				"CGridOptions" => "classes/general/grids.php",
				"CUndo" => "/classes/general/undo.php",
				"CAutoSave" => "/classes/general/undo.php",
				"CRatings" => "classes/".$GLOBALS["DBType"]."/ratings.php",
				"CRatingsComponentsMain" => "classes/".$GLOBALS["DBType"]."/ratings_components.php",
				"CRatingRule" => "classes/general/rating_rule.php",
				"CRatingRulesMain" => "classes/".$GLOBALS["DBType"]."/rating_rules.php",
				"CTopPanel" => "public/top_panel.php",
				"CEditArea" => "public/edit_area.php",
				"CComponentPanel" => "public/edit_area.php",
				"CTextParser" => "classes/general/textparser.php",
				"CPHPCacheFiles" => "classes/general/cache_files.php",
				"CTimeZone" => "classes/general/time.php",
				"CDataXML" => "classes/general/xml.php",
				"CRsaProvider" => "classes/general/rsasecurity.php",
				"CRsaSecurity" => "classes/general/rsasecurity.php",
				"CRsaBcmathProvider" => "classes/general/rsabcmath.php",
				"CRsaOpensslProvider" => "classes/general/rsaopenssl.php",
				"CASNReader" => "classes/general/asn.php",
				"CBXShortUri" => "classes/".$GLOBALS["DBType"]."/short_uri.php",
				"CFinder" => "classes/general/finder.php",
				"CAccess" => "classes/general/access.php",
				"CAuthProvider" => "classes/general/authproviders.php",
				"IProviderInterface" => "classes/general/authproviders.php",
				"CGroupAuthProvider" => "classes/general/authproviders.php",
				"CUserAuthProvider" => "classes/general/authproviders.php",
				"Bitrix\\Main\\Entity\\Base" => "lib/entity/base.php",
				"Bitrix\\Main\\Entity\\DataManager" => "lib/entity/base.php",
				"Bitrix\\Main\\Entity\\Field" => "lib/entity/field.php",
				"Bitrix\\Main\\Entity\\ScalarField" => "lib/entity/scalarfield.php",
				"Bitrix\\Main\\Entity\\IntegerField" => "lib/entity/integerfield.php",
				"Bitrix\\Main\\Entity\\FloatField" => "lib/entity/floatfield.php",
				"Bitrix\\Main\\Entity\\StringField" => "lib/entity/stringfield.php",
				"Bitrix\\Main\\Entity\\TextField" => "lib/entity/textfield.php",
				"Bitrix\\Main\\Entity\\BooleanField" => "lib/entity/booleanfield.php",
				"Bitrix\\Main\\Entity\\DateField" => "lib/entity/datefield.php",
				"Bitrix\\Main\\Entity\\DatetimeField" => "lib/entity/datetimefield.php",
				"Bitrix\\Main\\Entity\\EnumField" => "lib/entity/enumfield.php",
				"Bitrix\\Main\\Entity\\ExpressionField" => "lib/entity/expressionfield.php",
				"Bitrix\\Main\\Entity\\UField" => "lib/entity/ufield.php",
				"WorkgroupEntity" => "lib/workgroup.php",
				"Bitrix\\Main\\Entity\\ReferenceField" => "lib/entity/referencefield.php",
				"Bitrix\\Main\\Entity\\Query" => "lib/entity/query.php",
				"Bitrix\\Main\\Entity\\QueryChain" => "lib/entity/querychain.php",
				"Bitrix\\Main\\Entity\\QueryChainElement" => "lib/entity/querychainelement.php",
				"SiteEntity" => "lib/site.php",
				"Site" => "lib/site.php",
				"UserEntity" => "lib/user.php",
				"UtsUserEntity" => "lib/utsuser.php",
				"UtmUserEntity" => "lib/utmuser.php",
				"UserGroupEntity" => "lib/usergroup.php",
				"GroupEntity" => "lib/group.php",
				"CTableSchema" => "classes/general/table_schema.php",
				"CUserCounter" => "classes/".$GLOBALS["DBType"]."/user_counter.php",
				"CHotKeys" => "classes/general/hot_keys.php",
				"CHotKeysCode" => "classes/general/hot_keys.php",
				"CBXSanitizer" => "classes/general/sanitizer.php",
				"CBXArchive" => "classes/general/archive.php",
				"CAdminNotify" => "classes/general/admin_notify.php",
				"CBXFavAdmMenu" => "classes/general/favorites.php",
				"CAdminInformer" => "classes/general/admin_informer.php"
			)
		);
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$GLOBALS["DBType"]."/agent.php");
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$GLOBALS["DBType"]."/user.php");
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$GLOBALS["DBType"]."/event.php");
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/menu.php");
		AddEventHandler("main", "OnAfterEpilog", array("CCacheManager", "_Finalize"));
		require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".$GLOBALS["DBType"]."/usertype.php");

		//component 2.0 template engines
		// нужно до подключения init.php
		$GLOBALS["arCustomTemplateEngines"] = array();
	}

	static public function authorizeUser()
	{
		// раскомментировать когда уберем из CurrentUser вызовы USER
		//$GLOBALS["USER"] = new \CUser;

		// define("BX_STARTED", true); // нужен для инфоблоков чтобы понять - определен ли сайт???

		if(!defined("NOT_CHECK_PERMISSIONS") || NOT_CHECK_PERMISSIONS!==true)
		{
			$bLogout = (strtolower($_REQUEST["logout"]) == "yes");

			if($bLogout && $GLOBALS["USER"]->isAuthorized())
			{
// этот редирект нужно убирать
				$GLOBALS["USER"]->logout();
				LocalRedirect($GLOBALS["APPLICATION"]->getCurPageParam('', array('logout')));
			}

			// authorize by cookie
			$cookie_prefix = \COption::getOptionString('main', 'cookie_name', 'BITRIX_SM');
			$cookie_login = $_COOKIE[$cookie_prefix.'_LOGIN'];
			$cookie_md5pass = $_COOKIE[$cookie_prefix.'_UIDH'];

			if(\COption::getOptionString("main", "store_password", "Y")=="Y"
				&& strlen($cookie_login)>0
				&& strlen($cookie_md5pass)>0
				&& !$GLOBALS["USER"]->isAuthorized()
				&& !$bLogout
				&& $_SESSION["SESS_PWD_HASH_TESTED"] != md5($cookie_login."|".$cookie_md5pass)
			)
			{
				$GLOBALS["USER"]->loginByHash($cookie_login, $cookie_md5pass);
				$_SESSION["SESS_PWD_HASH_TESTED"] = md5($cookie_login."|".$cookie_md5pass);
			}

			$arAuthResult = false;

			//http basic and digest authorization
			if(($httpAuth = $GLOBALS["USER"]->loginByHttpAuth()) !== null)
			{
				$arAuthResult = $httpAuth;
				$GLOBALS["APPLICATION"]->setAuthResult($arAuthResult);
			}

			//Authorize user from authorization html form
			if($_REQUEST["AUTH_FORM"] <> '')
			{
				$bRsaError = false;
				if(\COption::getOptionString('main', 'use_encrypted_auth', 'N') == 'Y')
				{
					//possible encrypted user password
					$sec = new \CRsaSecurity();
					if(($arKeys = $sec->loadKeys()))
					{
						$sec->setKeys($arKeys);
						$errno = $sec->acceptFromForm(array('USER_PASSWORD', 'USER_CONFIRM_PASSWORD'));
						if($errno == \CRsaSecurity::ERROR_SESS_CHECK)
							$arAuthResult = array("MESSAGE"=>GetMessage("main_include_decode_pass_sess"), "TYPE"=>"ERROR");
						elseif($errno < 0)
							$arAuthResult = array("MESSAGE"=>GetMessage("main_include_decode_pass_err", array("#ERRCODE#"=>$errno)), "TYPE"=>"ERROR");

						if($errno < 0)
							$bRsaError = true;
					}
				}

				if($bRsaError == false)
				{
					if(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
						$USER_LID = LANG;
					else
						$USER_LID = false;
					if($_REQUEST["TYPE"] == "AUTH")
					{
						$arAuthResult = $GLOBALS["USER"]->login($_REQUEST["USER_LOGIN"], $_REQUEST["USER_PASSWORD"], $_REQUEST["USER_REMEMBER"]);
						if ($arAuthResult === true && defined('ADMIN_SECTION') && ADMIN_SECTION === true)
						{
							$_SESSION['BX_ADMIN_LOAD_AUTH'] = true;
							echo '<script type="text/javascript">window.onload=function(){top.BX.AUTHAGENT.setAuthResult(false);};</script>';
							die();
						}
					}
					elseif($_REQUEST["TYPE"] == "SEND_PWD")
						$arAuthResult = $GLOBALS["USER"]->sendPassword($_REQUEST["USER_LOGIN"], $_REQUEST["USER_EMAIL"], $USER_LID);
					elseif($_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST["TYPE"] == "CHANGE_PWD")
						$arAuthResult = $GLOBALS["USER"]->changePassword($_REQUEST["USER_LOGIN"], $_REQUEST["USER_CHECKWORD"], $_REQUEST["USER_PASSWORD"], $_REQUEST["USER_CONFIRM_PASSWORD"], $USER_LID);
					elseif(\COption::getOptionString("main", "new_user_registration", "N") == "Y" && $_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST["TYPE"] == "REGISTRATION" && (!defined("ADMIN_SECTION") || ADMIN_SECTION!==true))
						$arAuthResult = $GLOBALS["USER"]->register($_REQUEST["USER_LOGIN"], $_REQUEST["USER_NAME"], $_REQUEST["USER_LAST_NAME"], $_REQUEST["USER_PASSWORD"], $_REQUEST["USER_CONFIRM_PASSWORD"], $_REQUEST["USER_EMAIL"], $USER_LID, $_REQUEST["captcha_word"], $_REQUEST["captcha_sid"]);
				}
				$GLOBALS["APPLICATION"]->setAuthResult($arAuthResult);
			}
			elseif(!$GLOBALS["USER"]->isAuthorized())
			{
				//Authorize by unique URL
				$GLOBALS["USER"]->loginHitByHash();
			}
		}
	}
}
