<?
/*
##############################################
# Bitrix: SiteManager                        #
# Copyright (c) 2002-2005 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
*/
global $DOCUMENT_ROOT, $MESS;
IncludeModuleLangFile(__FILE__);
// define("DEBUG_FILE_MAN", false);
if(!defined("CACHED_stickers_count")) // define("CACHED_stickers_count", 36000000);

$GLOBALS['arFilemanPredifinedFileTypesR'] = array();
$GLOBALS['arFilemanPredifinedFileTypes'] = array(
	"css" => array(
		"exts" => array("css"),
		"gtype" => "text",
		"name" => GetMessage("MAIN_BFSD_FTYPE_CSS")
	),
	"htaccess" => array(
		"exts" => array("htaccess"),
		"gtype" => "text",
		"name" => GetMessage("MAIN_BFSD_FTYPE_SYS")
	),
	"html" => array(
		"exts" => array("html", "htm", "shtml", "shtm"),
		"gtype" => "text",
		"name" => GetMessage("MAIN_BFSD_FTYPE_HTML")
	),
	"png" => array(
		"exts" => array("png"),
		"gtype" => "image",
		"name" => GetMessage("MAIN_BFSD_FTYPE_PNG")
	),
	"gif" => array(
		"exts" => array("gif"),
		"gtype" => "image",
		"name" => GetMessage("MAIN_BFSD_FTYPE_GIF")
	),
	"jpeg" => array(
		"exts" => array("jpeg", "jpg", "jpe"),
		"gtype" => "image",
		"name" => GetMessage("MAIN_BFSD_FTYPE_JPG")
	),
	"js" => array(
		"exts" => array("js"),
		"gtype" => "text",
		"name" => "JavaScript"
	),
	"php" => array(
		"exts" => array("php", "php3", "php4", "php5", "php6", "phtml"),
		"gtype" => "text",
		"name" => GetMessage("MAIN_BFSD_FTYPE_PHP")
	),
	"txt" => array(
		"exts" => array("txt", "sql"),
		"gtype" => "text",
		"name" => GetMessage("MAIN_BFSD_FTYPE_TXT")
	),
	"xml" => array(
		"exts" => array("xml", "xsl"),
		"gtype" => "text",
		"name" => GetMessage("MAIN_BFSD_FTYPE_XML")
	),
	"csv" => array(
		"exts" => array("csv"),
		"gtype" => "text",
		"name" => GetMessage("MAIN_BFSD_FTYPE_CSV")
	),
	"flash" => array(
		"exts" => array("fla", "swf"),
		"gtype" => "file",
		"name" => GetMessage("MAIN_BFSD_FTYPE_SWF")
	),
	"archive" => array(
		"exts" => CBXArchive::GetArchiveExtensions(),
		"gtype" => "file",
		"name" => GetMessage("MAIN_BFSD_FTYPE_ARC")
	),
	"file" => array(
		"exts" => array(),
		"gtype" => "file",
		"name" => GetMessage("MAIN_BFSD_FTYPE_NA")
	)
);

// AddEventHandler("fileman", "OnBeforeHTMLEditorScriptsGet", "bitrix_tabs");
// function bitrix_tabs($editorName, $arEditorParams){return array("JS" => array('bitrix_tabs.js'));}

class CFileMan
{
	var $arFILE_TYPES;

	public static function OnPanelCreate()
	{
		global $APPLICATION, $REQUEST_URI;
		if($APPLICATION->GetGroupRight("fileman")<="D")
			return;

		$cur_page = $APPLICATION->GetCurPage(true);
		$cur_dir = $APPLICATION->GetCurDir();

		//access to curent page
		$page_permission = $APPLICATION->GetFileAccessPermission($cur_page);

		// access to current directory
		$sect_permission = $APPLICATION->GetFileAccessPermission($cur_dir);

		$main_sort = 100;

		$full_src = "";
		$default_edit = COption::GetOptionString("fileman", "default_edit", "html");
		if($default_edit == "php")
		{
			$editor_type = "file";
			$full_src = "&full_src=Y";
		}
		elseif($default_edit == "text")
			$editor_type = "file";
		else
			$editor_type = "html";


		$aMenuItems = array(
			'create' => array(),
			'create_section' => array(),
			'edit' => array(),
			'edit_section' => array()
		);

		if ($sect_permission>="W")
		{
			// New page
			$href = "/bitrix/admin/fileman_".$editor_type."_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&path=".UrlEncode($APPLICATION->GetCurDir())."&new=Y&templateID=".urlencode(SITE_TEMPLATE_ID)."&back_url=".UrlEncode($REQUEST_URI);
			$APPLICATION->AddPanelButtonMenu('create', array("SEPARATOR"=>true, "SORT"=>99));
			$APPLICATION->AddPanelButtonMenu('create', array(
				"TEXT" => GetMessage("fileman_panel_admin"),
				"TITLE"=> GetMessage("fileman_panel_new_page_title"),
				"ACTION"	=> "jsUtils.Redirect([], '".CUtil::JSEscape($href)."')",
				"SORT" => 100
			));

			//New folder
			$href = "/bitrix/admin/fileman_newfolder.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&path=". UrlEncode($APPLICATION->GetCurDir())."&back_url=".UrlEncode($REQUEST_URI);
			$APPLICATION->AddPanelButtonMenu('create_section', array("SEPARATOR"=>true, "SORT"=>99));
			$APPLICATION->AddPanelButtonMenu('create_section', array(
				"TEXT" => GetMessage("fileman_panel_admin"),
				"TITLE"=> GetMessage("fileman_panel_new_folder_title"),
				"ACTION"	=> "jsUtils.Redirect([], '".CUtil::JSEscape($href)."')",
			));
		}

		// Edit page
		if ($page_permission>="W")
		{
			$href = "/bitrix/admin/fileman_".$editor_type."_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&templateID=".urlencode(SITE_TEMPLATE_ID).$full_src."&path=".UrlEncode(isset($_SERVER["REAL_FILE_PATH"]) && $_SERVER["REAL_FILE_PATH"]<>""? $_SERVER["REAL_FILE_PATH"] : $cur_page)."&back_url=".UrlEncode($REQUEST_URI);
			$APPLICATION->AddPanelButtonMenu('edit', array("SEPARATOR"=>true, "SORT"=>99));
			$APPLICATION->AddPanelButtonMenu('edit', array(
				"TEXT" => GetMessage("fileman_panel_admin"),
				"TITLE"=> GetMessage("fileman_panel_edit_title"),
				"ACTION"	=> "jsUtils.Redirect([], '".CUtil::JSEscape($href)."')",
				"SORT" => 100
			));
		}

		// Folder properties
		$alt = GetMessage("FILEMAN_FOLDER_PROPS");
		if ($sect_permission>="W")
		{
			$href = "/bitrix/admin/fileman_folder.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&path=".UrlEncode($APPLICATION->GetCurDir())."&back_url=".UrlEncode($REQUEST_URI);
			$APPLICATION->AddPanelButtonMenu('edit_section', array("SEPARATOR"=>true, "SORT"=>99));
			$APPLICATION->AddPanelButtonMenu('edit_section', array(
				"TEXT" => GetMessage("fileman_panel_admin"),
				"TITLE"=> GetMessage("fileman_panel_folder_prop_title"),
				"ACTION"	=> "jsUtils.Redirect([], '".CUtil::JSEscape($href)."')",
				"SORT"=>100,
			));
		}

		//Sticker buttons
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/classes/general/sticker.php");
		if (CSticker::CanDoOperation('sticker_view'))
		{
			$useHotKeys = COption::GetOptionString('fileman', "stickers_use_hotkeys", "Y") == "Y";
			$arMenu = array();
			if (CSticker::CanDoOperation('sticker_new'))
			{
				$arMenu[] = array(
					"TEXT" => '<div style="float: left; margin: 0 50px 0 0;">'.GetMessage("FMST_PANEL_STICKER_ADD").'</div>'.($useHotKeys ? '<div style="float: right;"><nobr>Ctrl+Shift+S</nobr></div>' : ''),
					"TITLE" => GetMessage("FMST_PANEL_STICKER_ADD_TITLE"),
					"ICON" => "",
					"ACTION" => CSticker::GetScriptStr('add'),
					"DEFAULT" => true,
					"HK_ID"=>"FMST_PANEL_STICKER_ADD",
				);
				$arMenu[] = array("SEPARATOR" => true);
			}

			$curPageCount = CSticker::GetCurPageCount();
			$arMenu[] = array(
				"ID" => "bxst-show-sticker-icon",
				"TEXT" => '<div style="float: left; margin: 0 50px 0 0;">'.GetMessage("FMST_PANEL_STICKERS_SHOW", array("#COUNT#" => $curPageCount)).'</div>'.($useHotKeys ? '<div style="float:right;"><nobr>Ctrl+Shift+X</nobr></div>' : ''),
				"TITLE" => GetMessage("FMST_PANEL_STICKERS_SHOW_TITLE"),
				"ICON" => CSticker::GetBShowStickers() ? " checked" : "",
				"ACTION" => CSticker::GetScriptStr('show'),
				"HK_ID"=>"FMST_PANEL_STICKERS_SHOW",
			);
			$arMenu[] = array(
				//"TEXT" => GetMessage("FMST_PANEL_CUR_STICKER_LIST"),
				"TEXT" => '<div style="float: left; margin: 0 50px 0 0;">'.GetMessage("FMST_PANEL_CUR_STICKER_LIST").'</div>'.($useHotKeys ? '<div style="float: right;"><nobr>Ctrl+Shift+L</nobr></div>' : ''),
				"TITLE" => GetMessage("FMST_PANEL_CUR_STICKER_LIST_TITLE"),
				"ICON" => "",
				"ACTION" => CSticker::GetScriptStr('list_cur'),
				"HK_ID"=>"FMST_PANEL_CUR_STICKER_LIST",
			);
			$arMenu[] = array(
				"TEXT" => GetMessage("FMST_PANEL_ALL_STICKER_LIST"),
				"TITLE" => GetMessage("FMST_PANEL_ALL_STICKER_LIST_TITLE"),
				"ICON" => "",
				"ACTION" => CSticker::GetScriptStr('list_all'),
				"HK_ID"=>"FMST_PANEL_ALL_STICKER_LIST",
			);

			$APPLICATION->AddPanelButton(array(
				"HREF" => 'javascript:'.(CSticker::CanDoOperation('sticker_edit') ? CSticker::GetScriptStr('add') : CSticker::GetScriptStr('show')),
				"TYPE" => "SMALL",
				"ICON" => "bx-panel-small-stickers-icon",
				"TEXT" => GetMessage("FMST_PANEL_STICKERS_TOOLTIP_TITLE"),
				"MAIN_SORT" => "1000",
				"SORT" => 100,
				"MENU" => $arMenu,
				"HK_ID"=>"FMST_PANEL_STICKERS",
				"HINT" => array(
					"TITLE" => CSticker::CanDoOperation('sticker_edit') ? GetMessage("FMST_PANEL_STICKERS_TOOLTIP_TITLE") : GetMessage("FMST_PANEL_STICKERS_SHOW_1"),
					"TEXT" => CSticker::CanDoOperation('sticker_edit') ? GetMessage("FMST_PANEL_STICKERS_TOOLTIP").($useHotKeys ? " (Ctrl+Shift+S)" : "") : GetMessage("FMST_PANEL_STICKERS_ALT").($useHotKeys ? " (Ctrl+Shift+X)" : "")
				)
			));
			CSticker::Init(array('curPageCount' => $curPageCount));
		}
	}

	public function CFileMan()
	{
		$this->arFILE_TYPES = Array("SOURCE"=>GetMessage("FILEMAN_FILEMAN_SCRIPT_TEXT"), "IMAGE"=>GetMessage("FILEMAN_FILEMAN_PIC"), "UNKNOWN"=>GetMessage("FILEMAN_FILEMAN_UNK"));
	}

	public static function OnGroupDelete($group_id)
	{
		return "";
	}

	public static function GetVersion()
	{
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/install/version.php");
		if (!isset($arModuleVersion['VERSION']))
			return false;
		return $arModuleVersion['VERSION'];
	}

	public static function SaveMenu($path, $aMenuLinksTmp, $sMenuTemplateTmp)
	{
		global $APPLICATION;
		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);

		$strMenuLinks = "";
		if(strlen($sMenuTemplateTmp)>0)
			$strMenuLinks .= "\$sMenuTemplate = \"".CFileMan::EscapePHPString($sMenuTemplateTmp)."\";\n";

		$strMenuLinks .= "\$aMenuLinks = Array(";
		$i=0;
		foreach($aMenuLinksTmp as $arMenuItem)
		{
			$i++;
			$strMenuLinksTmp = "";

			if($i>1)
				$strMenuLinksTmp .= ",";

			$strMenuLinksTmp .= "\n".
				"	Array(\n".
				"		\"".CFileMan::EscapePHPString($arMenuItem[0])."\", \n".
				"		\"".CFileMan::EscapePHPString($arMenuItem[1])."\", \n".
				"		Array(";

			if(is_array($arMenuItem[2]))
			{
				for($j = 0, $l = count($arMenuItem[2]); $j < $l; $j++)
				{
					if($j>0)
						$strMenuLinksTmp .= ", ";
					$strMenuLinksTmp .= "\"".CFileMan::EscapePHPString($arMenuItem[2][$j])."\"";
				}
			}
			$strMenuLinksTmp .= "), \n";

			$strMenuLinksTmp .= "		Array(";
			if(is_array($arMenuItem[3]))
			{
				$arParams = array_keys($arMenuItem[3]);
				for($j = 0, $l = count($arParams); $j < $l; $j++)
				{
					if($j>0)
						$strMenuLinksTmp .= ", ";
					$strMenuLinksTmp .= "\"".CFileMan::EscapePHPString($arParams[$j])."\"=>"."\"".CFileMan::EscapePHPString($arMenuItem[3][$arParams[$j]])."\"";
				}
			}

			$strMenuLinksTmp .= "), \n".
				"		\"".CFileMan::EscapePHPString($arMenuItem[4])."\" \n".
				"	)";

			$strMenuLinks .= $strMenuLinksTmp;
		}
		$strMenuLinks .= "\n);";
		$APPLICATION->SaveFileContent($DOC_ROOT.$path, "<"."?\n".$strMenuLinks."\n?".">");
		$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
		CBitrixComponent::clearComponentCache("bitrix:menu");
	}

	public static function GetMenuArray($abs_path)
	{
		$aMenuLinks = Array();
		$sMenuTemplate = '';

		$io = CBXVirtualIo::GetInstance();
		if ($io->FileExists($abs_path))
			include($io->GetPhysicalName($abs_path));

		return Array("aMenuLinks"=>$aMenuLinks, "sMenuTemplate" => $sMenuTemplate);
	}

	public static function GetFileName($path)
	{
		return GetFileName($path);
	}

	public static function CreateDir($path)
	{
		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);

		if (DEBUG_FILE_MAN) echo "CreateDir($path);<br>";
		global $APPLICATION, $USER;

		$badDirs=Array();

		$io = CBXVirtualIo::GetInstance();

		$path = $io->CombinePath($path);

		$p=strrpos($path, "/");

		while($p!==false)
		{
			$dir = $io->GetDirectory($DOC_ROOT.$path);
			if($dir->IsExists())
			{
				$dir->MarkWritable();
				break;
			}
			$badDirs[] = substr($path, $p + 1);
			$path = substr($path, 0, $p);
			$p = strrpos($path, "/");
		}

		for($i = count($badDirs) - 1; $i >= 0; $i--)
		{
			$path = $path."/".$badDirs[$i];
			if(!$USER->CanDoFileOperation('fm_create_new_folder', Array($site,$path)))
				return GetMessage("FILEMAN_FILEMAN_CREATE_FOLDER_DENY")." \"".$path."\".\n";

			if (($mess = CFileMan::CheckFileName($badDirs[$i])) !== true)
				return $mess;

			if (!$io->CreateDirectory($DOC_ROOT.$path))
				return GetMessage("FILEMAN_CREATE_FOLDER_ERROR", array('#PATH#' => htmlspecialcharsex($path)));
		}
	}

	//Function check if there are anything exept .access.php and if folder is empty - delete it
	public static function DeleteDir($path)
	{
		if(DEBUG_FILE_MAN)echo "DeleteDir(".$path.");<br>";

		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);

		$io = CBXVirtualIo::GetInstance();

		global $APPLICATION, $USER;
		if(strlen(trim($path))<=0)
			return GetMessage("FILEMAN_FILEMAN_TRYING_ROOT_DELETE")."\n";
		if(!$io->DirectoryExists($DOC_ROOT.$path))
			return GetMessage("FILEMAN_FILEMAN_FOLDER")." \"$path\" ".GetMessage("FILEMAN_FILEMAN_NOT_EXISTS")."\n";

		//check rights to write for this subfolder
		if(!$USER->CanDoFileOperation('fm_delete_folder', Array($site,$path)))
			return GetMessage("FILEMAN_FILEMAN_FOLDER_DEL_DENY")."\" ".$path."\".\n";

		$d = $io->GetDirectory($DOC_ROOT.$path);
		$arChildren = $d->GetChildren();
		foreach ($arChildren as $child)
		{
			if (!$child->IsDirectory() && $child->GetName() != ".access.php")
				return GetMessage("FILEMAN_FILEMAN_FOLDER_DEL_ERROR")." \"".$path."\" ".GetMessage("FILEMAN_FILEMAN_FOLDER_NOT_EMPTY").".\n";
		}

		//it's ok ... BUMP OFF!!!
		if ($io->FileExists($DOC_ROOT.$path."/.access.php"))
		{
			$f = $io->GetFile($DOC_ROOT.$path."/.access.php");
			$f->MarkWritable();
			$io->Delete($DOC_ROOT.$path."/.access.php");
		}

		$d->MarkWritable();
		if(DEBUG_FILE_MAN)echo "rmdir(".$path.");";
		if(!$io->Delete($DOC_ROOT.$path))
			return GetMessage("FILEMAN_FILEMAN_FOLDER_DEL_ERROR")." \"".$path."\".\n";

		$APPLICATION->RemoveFileAccessPermission(Array($site, $path));
	}

	public static function DeleteFile($path)
	{
		global $APPLICATION, $USER;
		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);

		$io = CBXVirtualIo::GetInstance();

		if(!$io->FileExists($DOC_ROOT.$path))
			return GetMessage("FILEMAN_FILEMAN_FILE")." \"$path\" ".GetMessage("FILEMAN_FILEMAN_NOT_EXISTS")."\n";

		if(strlen($path)>=12 && substr($path, strlen($path) - 12)=="/.access.php")
			return;

		//check: can we delete this file
		if(!$USER->CanDoFileOperation('fm_delete_file', Array($site,$path)))
			return GetMessage("FILEMAN_FILEMAN_FILE_DEL_DENY")." \"".$path."\".\n";

		$f = $io->GetFile($DOC_ROOT.$path);
		$f->MarkWritable();

		//deleting file
		if(DEBUG_FILE_MAN)echo "unlink(".$DOC_ROOT.$path.");<br>";
		$file_size = $f->GetFileSize();
		if($io->Delete($DOC_ROOT.$path))
		{
			$APPLICATION->RemoveFileAccessPermission(Array($site, $path));
			if(CModule::IncludeModule("search"))
				CSearch::DeleteIndex("main", $site."|".$path);

			//************************** Quota **************************//
			if(COption::GetOptionInt("main", "disk_space") > 0)
			{
				$quota = new CDiskQuota();
				$quota->updateDiskQuota("file", $file_size, "delete");
			}
			//************************** Quota **************************//
		}
	}

	public static function DeleteEx($path)
	{
		global $APPLICATION, $USER;

		CMain::InitPathVars($site, $path);

		if(strlen($path) <= 0)
			return false;

		$src = $_SERVER["DOCUMENT_ROOT"].$path;
		$src = str_replace("//","/",$src);
		if($src == $_SERVER["DOCUMENT_ROOT"])
			return false;

		$DOC_ROOT = CSite::GetSiteDocRoot($site);

		@set_time_limit(600);

		$io = CBXVirtualIo::GetInstance();

		if(is_link($linkPath = CBXVirtualIoFileSystem::ConvertCharset($DOC_ROOT.$path))) //if delete symb. link 	todo: windows, can't delete links on dirs
		{
			if(@unlink($linkPath))
				return;
			else
				return GetMessage("FILEMAN_FILEMAN_SYMLINK_DEL_ERROR")." \"".$path."\".\n";
		}
		elseif($io->DirectoryExists($DOC_ROOT.$path))//if delete folder
		{
			//check rights
			if(!$USER->CanDoFileOperation('fm_delete_folder', Array($site,$path)))
				return GetMessage("FILEMAN_FILEMAN_FOLDER_DEL_DENY")." \"".$path."\".\n";
		}
		else //if delete file
		{
			return CFileman::DeleteFile(Array($site, $path));
		}

		$strWarning = "";
		//get folder content
		$d = $io->GetDirectory($DOC_ROOT.$path);
		$arChildren = $d->GetChildren();
		foreach ($arChildren as $child)
		{
			$fn = $child->GetName();
			if ($child->IsDirectory())
			{
				$strWarning .= CFileman::DeleteEx(Array($site, $path."/".$fn));
			}
			else
			{
				if($fn == ".access.php") continue;
				$strWarning .= CFileman::DeleteFile(Array($site, $path."/".$fn));
			}
		}

		//delete first folder
		$strWarning .= CFileMan::DeleteDir(Array($site, $path));

		return $strWarning;
	}


	public static function NormalizePath($path)
	{
		$io = CBXVirtualIo::GetInstance();
		return $io->CombinePath("/", $path);
	}

	public static function CopyEx($path_from, $path_to, $bDeleteAfterCopy = false, $bOverride = false)
	{
		global $APPLICATION, $USER;
		CMain::InitPathVars($site_from, $path_from);
		$DOC_ROOT_FROM = CSite::GetSiteDocRoot($site_from);

		CMain::InitPathVars($site_to, $path_to);
		$DOC_ROOT_TO = CSite::GetSiteDocRoot($site_to);
		$strWarning = '';

		//check: if we copy to the same directory
		if(strpos($DOC_ROOT_TO.$path_to."/", $DOC_ROOT_FROM.$path_from."/")===0)
			return GetMessage("FILEMAN_LIB_BAD_FOLDER").": \"".$path_from."\".\n";

		$io = CBXVirtualIo::GetInstance();

		if($io->DirectoryExists($DOC_ROOT_FROM.$path_from))// Copy folder
		{
			// Minimal access - read/listing for copying files
			if(!$USER->CanDoFileOperation('fm_view_listing', Array($site_from, $path_from)))
				return GetMessage("FILEMAN_FILEMAN_FOLDER_READ_DENY")." \"".$path_from."\".\n";

			if ($bDeleteAfterCopy && !$USER->CanDoFileOperation('fm_delete_folder', Array($site_from, $path_from)))
				return GetMessage("FILEMAN_FILEMAN_FOLDER_DEL_DENY")." \"".$path_from."\".\n";

			//Check: folder exist or not
			$strWarTmp = CFileMan::CreateDir(Array($site_to, $path_to));

			if(strlen($strWarTmp ) > 0)
				return $strWarTmp;

			$APPLICATION->CopyFileAccessPermission(Array($site_from, $path_from), Array($site_to, $path_to));
		}
		else // Copy file
		{
			// If we can write this file
			if (!$USER->CanDoFileOperation('fm_create_new_file', Array($site_to, $path_to)))
				return GetMessage("FILEMAN_FILEMAN_FILE_WRITE_DENY")." \"".$path_to."\".\n";

			// If we can't read source-file
			if(!$USER->CanDoFileOperation('fm_view_file', Array($site_from, $path_from)))
				return GetMessage("FILEMAN_FILEMAN_FILE_READ_DENY")." \"".$path_from."\".\n";

			// Copying php or system file without PHP or LPA access
			if (!($USER->CanDoOperation('edit_php') || $USER->CanDoFileOperation('fm_lpa', $arPath) || !(HasScriptExtension($Elem["NAME"]) || substr($Elem["NAME"], 0, 1)==".")))
				return GetMessage("FILEMAN_FILEMAN_FILE_READ_DENY")." \"".$path_from."\".\n";

			// If we can't move source-file
			if($bDeleteAfterCopy &&  !$USER->CanDoFileOperation('fm_delete_file', Array($site_from, $path_from)))
				return GetMessage("FILEMAN_FILEMAN_FILE_DEL_DENY")." \"".$path_from."\".\n";

			//Check if folder already exist and trying to create if not
			$p = strrpos($path_to, "/");
			$path_to_dir = substr($path_to, 0, $p);
			$strWarTmp = CFileMan::CreateDir(Array($site_to, $path_to_dir));
			if(strlen($strWarTmp)>0)
				return $strWarTmp;

			if($io->FileExists($DOC_ROOT_TO.$path_to) || $io->DirectoryExists($DOC_ROOT_TO.$path_to))
			{
				if ($bOverride)
				{
					$strWarn = CFileMan::DeleteEx(Array($site_to, $path_to));
					if ($strWarn != "")
						return $strWarn;
				}
				else
				{
					return GetMessage("FILEMAN_FILEMAN_FILE_WITH_NAME")." \"".$path_to."\" ".GetMessage("FILEMAN_FILEMAN_ALREADY_EXISTS")."!\n";
				}
			}

			$APPLICATION->CopyFileAccessPermission(Array($site_from, $path_from), Array($site_to, $path_to));

			//************************** Quota **************************//
			if(COption::GetOptionInt("main", "disk_space") > 0)
			{
				$f = $io->GetFile($DOC_ROOT_FROM.$path_from);
				$size = $f->GetFileSize();
				$quota = new CDiskQuota();
				if (!$quota->checkDiskQuota(array("FILE_SIZE"=>$size)))
					return $quota->LAST_ERROR;
			}
			//************************** Quota **************************//

			// Copy file
			if(DEBUG_FILE_MAN)
				echo "copy(".$DOC_ROOT_FROM.$path_from.",".$DOC_ROOT_TO.$path_to.");<br>";

			if (!$io->Copy($DOC_ROOT_FROM.$path_from, $DOC_ROOT_TO.$path_to))
				$strWarning .= GetMessage('FILEMAN_COPY_ERROR', array('#PATH_FROM#' => htmlspecialcharsex($path_from), '#PATH_TO#' => htmlspecialcharsex($path_to)));

			//************************** Quota **************************//
			if(COption::GetOptionInt("main", "disk_space") > 0)
				$quota->updateDiskQuota("file", $size, "copy");
			//************************** Quota **************************//

			if(CModule::IncludeModule("search"))
			{
				$site = CSite::GetSiteByFullPath($DOC_ROOT_TO.$path_to);
				CSearch::ReIndexFile(Array($site_to, $path_to), $site);
			}

			if($bDeleteAfterCopy && strlen($strWarning) <=0) // If was command "delete after copy"?
				$strWarning .= CFileMan::DeleteFile(Array($site_from, $path_from));

			return $strWarning;
		}

		// Recursive
		$d = $io->GetDirectory($DOC_ROOT_FROM.$path_from);
		$arChildren = $d->GetChildren();
		foreach ($arChildren as $child)
		{
			$fn = $child->GetName();

			if($child->IsDirectory()) //if this is subfolder
			{
				//go to recursion
				$strWarning .= CFileMan::CopyEx(Array($site_from, $path_from."/".$fn), Array($site_to, $path_to."/".$fn), $bDeleteAfterCopy, $bOverride);
				//back from recursion, in this subfolder all right
				//if($bDeleteAfterCopy) //necessary delete this subfolder
				//	$strWarning .= CFileMan::DeleteDir($path_from."/".$file);
			}
			else //this is "subfile" :-)
			{
				if($fn == ".access.php")
					continue;
				//let's check, if we can to write there
				if(!$USER->CanDoFileOperation('fm_create_new_file', Array($site_to, $path_to."/".$fn)))
					$strWarning .= GetMessage("FILEMAN_FILEMAN_FILE_WRITE_DENY")." \"".$path_to."/".$fn."\".\n";
				//let's check, if we can read from there
				elseif(!$USER->CanDoFileOperation('fm_view_file', Array($site_from, $path_from."/".$fn)))
					$strWarning .= GetMessage("FILEMAN_FILEMAN_FILE_READ_DENY")." \"".$path_from."/".$fn."\".\n";
				elseif (!($USER->CanDoOperation('edit_php') || $USER->CanDoFileOperation('fm_lpa', Array($site_from, $path_from."/".$fn)) || !(HasScriptExtension($fn) || substr($fn, 0, 1) == ".")))
					$strWarning .= GetMessage("FILEMAN_FILEMAN_FILE_READ_DENY")." \"".$path_from."/".$fn."\".\n";
				else
				{
					if($io->FileExists($DOC_ROOT_TO.$path_to."/".$fn))
					{
						if ($bOverride)
						{
							$strWarn = CFileMan::DeleteEx(Array($site_to, $path_to."/".$fn));
							if ($strWarn != "")
								$strWarning .=  $strWarn."\n";
						}
						else
						{
							$strWarning .= GetMessage("FILEMAN_FILEMAN_FILE_WITH_NAME")." \"".$path_to."/".$fn."\" ".GetMessage("FILEMAN_FILEMAN_ALREADY_EXISTS")."!\n";
						}
					}

					if ($strWarning == "")
					{
						//it means we can copy, if we found here
						$APPLICATION->CopyFileAccessPermission(Array($site_from, $path_from."/".$fn), Array($site_to, $path_to."/".$fn));

						if(DEBUG_FILE_MAN)
							echo "copy(".$DOC_ROOT_FROM.$path_from."/".$fn.",".$DOC_ROOT_TO.$path_to."/".$fn.");<br>";

						if (!$io->Copy($DOC_ROOT_FROM.$path_from."/".$fn, $DOC_ROOT_TO.$path_to."/".$fn))
							$strWarning .= GetMessage('FILEMAN_COPY_ERROR', array('#PATH_FROM#' => htmlspecialcharsex($path_from."/".$fn), '#PATH_TO#' => htmlspecialcharsex($path_to."/".$fn)));

						//************************** Quota **************************//
						if(COption::GetOptionInt("main", "disk_space") > 0)
						{
							$f = $io->GetFile($DOC_ROOT_TO.$path_to."/".$fn);
							$quota = new CDiskQuota();
							$quota->updateDiskQuota("file", $f->GetFileSize(), "copy");
						}
						//************************** Quota **************************//

						if(CModule::IncludeModule("search"))
						{
							$site = CSite::GetSiteByFullPath($DOC_ROOT_TO, $path_to."/".$fn);
							CSearch::ReindexFile($path_to."/".$fn, $site);
						}

						if($bDeleteAfterCopy && strlen($strWarning) <=0)
						{
							$strWarning .= CFileMan::DeleteFile(Array($site_from, $path_from."/".$fn));
						}
					}
				}
			}
		}

		//we may be need, to delete our initial folder
		if($bDeleteAfterCopy)
			$strWarning .= CFileMan::DeleteDir(Array($site_from, $path_from));

		return $strWarning;
	}

	public static function GetAllDirList(&$arDirs, $arFilter=Array(), $site=false)
	{
		$arFiles = array();
		CFileMan::DirsRecursive(Array($site, ""), $arDirs, $arFilter, 0);
	}

	public static function DirsRecursive($path, &$arDirs, $arFilter=Array(), $depth=0)
	{
		$depth++;
		CFileMan::GetDirList($path, $arDirsTmp, $arFiles, $arFilter, Array("name"=>"asc"), "D");
		for($i = 0, $l = count($arDirsTmp); $i < $l; $i++)
		{
			$arDir = $arDirsTmp[$i];
			$arDir["DEPTH_LEVEL"] = $depth;
			$arDirs[] = $arDir;
			CFileMan::DirsRecursive($arDir["ABS_PATH"], $arDirs, $arFilter, $depth);
		}
	}

	public static function CompareFiles($f1, $f2, $sort=Array())
	{
		return CompareFiles($f1, $f2, $sort);
	}

	public static function GetDirList($path, &$arDirs, &$arFiles, $arFilter=Array(), $sort=Array(), $type="DF", $bLogical=false, $task_mode=false)
	{
		return GetDirList($path, $arDirs, $arFiles, $arFilter, $sort, $type, $bLogical,$task_mode);
	}

	public static function __CheckSite($site)
	{
		if($site !== false)
		{
			if(strlen($site)>0)
			{
				$res = CSite::GetByID($site);
				if($arSite = $res->Fetch())
					$site = $arSite['ID'];
				else
					$site = false;
			}
			else
				$site = false;
		}
		return $site;
	}

	public static function ParsePath($path, $bLast=false,  $url=false, $param="", $bLogical = false)
	{
		return ParsePath($path, $bLast, $url, $param, $bLogical);
	}

	public static function GetFileExtension($path)
	{
		return GetFileExtension($path);
	}

	public static function GetFileType($path)
	{
		return GetFileType($path);
	}

	public static function GetStrFileSize($size)
	{
		return CFile::FormatSize($size);
	}

	public static function GetFileTypeEx($fileName)
	{
		global $arFilemanPredifinedFileTypesR;
		$fileExt = GetFileExtension(strtolower($fileName));
		if (count($arFilemanPredifinedFileTypesR) <= 0)
		{
			foreach ($GLOBALS['arFilemanPredifinedFileTypes'] as $key => $value)
				foreach ($value["exts"] as $ext)
					$arFilemanPredifinedFileTypesR[$ext] = $key;
		}
		if (isset($arFilemanPredifinedFileTypesR[$fileExt]))
			return $arFilemanPredifinedFileTypesR[$fileExt];
		return "file";
	}

	public static function EscapePHPString($str)
	{
		return EscapePHPString($str);
	}

	public static function UnEscapePHPString($str)
	{
		return UnEscapePHPString($str);
	}

	public static function UndoFileDelete($Params, $type)
	{
		global $APPLICATION;

		$documentRoot = CSite::GetSiteDocRoot($Params['site']);

		// Restore file
		if (strlen($Params['path']) > 0)
			$APPLICATION->SaveFileContent($documentRoot.$Params['path'], $Params['content']);

		// Update disk quota
		if (COption::GetOptionInt("main", "disk_space") > 0)
		{
			$io = CBXVirtualIo::GetInstance();
			$f = $io->GetFile($documentRoot.$Params['path']);
			$file_size = $f->GetFileSize();
			$quota = new CDiskQuota();
			$quota->UpdateDiskQuota("file", $file_size, "delete");
		}

		// Restore file access permissions
		if (is_array($Params['perm']) && count($Params['perm']) > 0)
		{
			for ($i = 0, $l = count($Params['perm']); $i < $l; $i++)
			{
				$permFile = $Params['perm'][$i]['permFile'];

				$permContent = $APPLICATION->GetFileContent($permFile);
				$permContent = substr($permContent, 0, strpos($permContent, "?".">"));
				$permContent .= "\$PERM[\"".EscapePHPString($Params['perm'][$i]['file'])."\"][\"".EscapePHPString($Params['perm'][$i]['group'])."\"]=\"".EscapePHPString($Params['perm'][$i]['perm'])."\";\n";
				$permContent .= "?".">\n";

				$APPLICATION->SaveFileContent($permFile, $permContent);
			}
		}

		// Restore menu
		if (is_array($Params['menu']) && count($Params['menu']) > 0)
		{
			for ($i = 0, $l = count($Params['menu']); $i < $l; $i++)
			{
				$menuFile = $Params['menu'][$i]['menuFile'];
				$menuIndex = $Params['menu'][$i]['menuIndex'];
				$menuItem = $Params['menu'][$i]['menuItem'];

				$arMenu = CFileman::GetMenuArray($documentRoot.$menuFile);

				$arFound = false;
				if (count($arMenu["aMenuLinks"]) <= $menuIndex)
					$arMenu["aMenuLinks"][] = $menuItem;
				else
					$arMenu["aMenuLinks"] = array_merge(array_slice($arMenu["aMenuLinks"], 0, $menuIndex), array($menuItem), array_slice($arMenu["aMenuLinks"], $menuIndex));

				CFileMan::SaveMenu(Array($Params['site'], $menuFile), $arMenu["aMenuLinks"], $arMenu["sMenuTemplate"]);
			}
		}

		// Restore UrlRewriter
		if (is_array($Params['SEF']) && count($Params['SEF']) > 0)
		{
			for ($i = 0, $l = count($Params['SEF']); $i < $l; $i++)
			{
				CUrlRewriter::Add(array(
					"SITE_ID" => $Params['site'],
					"CONDITION" => $Params['SEF'][$i]["CONDITION"],
					"ID" => $Params['SEF'][$i]["ID"],
					"PATH" => $Params['SEF'][$i]["PATH"],
					"RULE" => $Params['SEF'][$i]["RULE"]
				));
			}
		}

		$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
	}

	public static function UndoNewFile($Params, $type)
	{
		global $APPLICATION;

		$documentRoot = CSite::GetSiteDocRoot($Params['site']);

		$io = CBXVirtualIo::GetInstance();

		// Delete file
		$f = $io->GetFile($Params['absPath']);
		$f->MarkWritable();

		if (COption::GetOptionInt("main", "disk_space") > 0)
		{
			$file_size = $f->GetFileSize();
			$quota = new CDiskQuota();
			$quota->UpdateDiskQuota("file", $file_size, "delete");
		}

		$sucess = $io->Delete($Params['absPath']);

		if (!$sucess)
			return;

		$APPLICATION->RemoveFileAccessPermission(Array($Params['site'], $Params['path']));

		if (CModule::IncludeModule("search"))
			CSearch::DeleteIndex("main", $Params['site']."|".$Params['path']);

		// Find and clean file from menu
		if ($Params['menu'])
		{
			$arMenu = CFileman::GetMenuArray($documentRoot.$Params['menu']['menuFile']);
			if (!empty($arMenu["aMenuLinks"]))
			{
				$found = false;
				foreach ($arMenu["aMenuLinks"] as $menuIndex => $arItem)
				{
					if ($arItem[1] == $Params['menu']['menuPath'] && $arItem[0] == $Params['menu']['menuName'])
					{
						unset($arMenu["aMenuLinks"][$menuIndex]);
						$found = true;
						break;
					}
				}

				if ($found)
					CFileMan::SaveMenu(Array($Params['site'], $Params['menu']['menuFile']), $arMenu["aMenuLinks"], $arMenu["sMenuTemplate"]);
			}
		}

		if (isset($Params['public']) && $Params['public'] == 'Y')
		{
			?>
			<script type="text/javascript">
				window.location = '<?= CUtil::JSEscape(CHTTP::URN2URI(GetDirPath($Params['path'])))?>';
			</script>
		<?
		}

		$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
	}

	public static function UndoEditFile($Params, $type)
	{
		global $APPLICATION;

		// Restore file
		if (strlen($Params['absPath']) > 0)
			$APPLICATION->SaveFileContent($Params['absPath'], $Params['content']);

		$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
	}

	public static function UndoNewSection($Params, $type)
	{
		$io = CBXVirtualIo::GetInstance();

		if (strlen($Params['path']) > 0 && $Params['path'] != "/" && $io->DirectoryExists($Params['absPath']))
			CFileman::DeleteEx(Array($Params['site'], $Params['path']));

		$documentRoot = CSite::GetSiteDocRoot($Params['site']);
		// Find and clean file from menu
		if ($Params['menu'])
		{
			$arMenu = CFileman::GetMenuArray($documentRoot.$Params['menu']['menuFile']);
			if (!empty($arMenu["aMenuLinks"]))
			{
				$found = false;
				foreach ($arMenu["aMenuLinks"] as $menuIndex => $arItem)
				{
					if ($arItem[1] == $Params['menu']['menuPath'] && $arItem[0] == $Params['menu']['menuName'])
					{
						unset($arMenu["aMenuLinks"][$menuIndex]);
						$found = true;
						break;
					}
				}

				if ($found)
					CFileMan::SaveMenu(Array($Params['site'], $Params['menu']['menuFile']), $arMenu["aMenuLinks"], $arMenu["sMenuTemplate"]);
			}
		}
	}

	public static function FetchFileAccessPerm($path)
	{
		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);
		$result = false;

		if(($p = bxstrrpos($path, "/")) === false)
			return $result;

		$path_file = substr($path, $p + 1);
		$path_dir = substr($path, 0, $p);

		$io = CBXVirtualIo::GetInstance();
		if (!$io->FileExists($DOC_ROOT.$path_dir."/.access.php"))
			return $result;

		include($io->GetPhysicalName($DOC_ROOT.$path_dir."/.access.php"));

		$result = array();
		foreach($PERM as $file=>$arPerm)
		{
			if($file == $path_file)
			{
				foreach($arPerm as $group=>$perm)
					$result[] = array(
						'permFile' => $DOC_ROOT.$path_dir."/.access.php",
						'file' => $file,
						'group' => $group,
						'perm' => $perm
					);
			}
		}

		return $result;
	}

	public static function ShowTypeSelector($params)
	{
		global $USER;
		$useEditor3 = COption::GetOptionString('fileman', "use_editor_3", "Y") == "Y";
		$name = $params['name'];
		$key = isset($params['key']) ? $params['key'] : '';
		$showTextType = isset($params['strTextTypeFieldName']) && $params['strTextTypeFieldName'];
		$strTextTypeFieldName = $params['strTextTypeFieldName'];
		$textType = $params['strTextTypeValue'] == 'html' ? 'html' : 'text';
		$bxid = 'bxed_'.$name;

		$replaceNewLines = COption::GetOptionString('fileman', "replace_new_lines", "Y") == "Y";

		if ($textType == 'html')
		{
			$curType = CUserOptions::GetOption('html_editor', "type_selector_".$name.$key, false, $USER->GetId());
			$curType = $curType['type'];
			if ($curType && in_array($curType, array('html', 'editor')))
			{
				$textType = $curType;
			}
		}
		$ch = "checked=\"checked\"";
		?>
		<div class="bx-ed-type-selector">
			<?if ($showTextType):?>
				<span class="bx-ed-type-selector-item"><input <? if ($textType == 'text') {echo $ch;}?>  type="radio" name="<?= $strTextTypeFieldName?>" id="<?= $bxid?>_text" value="text" /><label for="<?= $bxid?>_text"><?= GetMessage('FILEMAN_FILEMAN_TYPE_TEXT')?></label></span>

				<span class="bx-ed-type-selector-item"><input <? if ($textType == 'html') {echo $ch;}?>  type="radio" name="<?= $strTextTypeFieldName?>" id="<?= $bxid?>_html" value="html" /><label for="<?= $bxid?>_html">HTML</label></span>

				<span class="bx-ed-type-selector-item"><input <? if ($textType == 'editor') {echo $ch;}?>  type="radio" name="<?= $strTextTypeFieldName?>" id="<?= $bxid?>_editor" value="html" /><label for="<?= $bxid?>_editor"><?= GetMessage('FILEMAN_FILEMAN_TYPE_HTML_EDITOR')?></label></span>
			<? else:?>
				<span class="bx-ed-type-selector-item"><input type="checkbox" id="<?= $bxid?>_editor" name="<?= $strTextTypeFieldName?>" value="Y" <? if ($textType == 'editor') {echo $ch;}?> /><label for="<?= $bxid?>_editor"><?= GetMessage("FILEMAN_FILEMAN_USE_HTML_EDITOR");?></span>
			<? endif;?>
		</div>
		<script>
			function onChangeInputType(editorName)
			{
				if (window['changeType_' + editorName] && typeof window['changeType_' + editorName] == 'function')
				{
					window['changeType_' + editorName]();
				}
				else
				{
					return setTimeout(function(){onChangeInputType(editorName);}, 100);
				}
			}

			BX.ready(function()
			{
				var
					pOptText = BX("<?= $bxid?>_text"),
					pOptHtml = BX("<?= $bxid?>_html"),
					pOptEditor = BX("<?= $bxid?>_editor");

				if (pOptText)
				{
					BX.bind(pOptText, 'click', function(){onChangeInputType('<?= $name?>');});
				}
				if (pOptHtml)
				{
					BX.bind(BX("<?= $bxid?>_html"), 'click', function(){onChangeInputType('<?= $name?>');});
				}
				if (pOptEditor)
				{
					BX.bind(BX("<?= $bxid?>_editor"), 'click', function(){onChangeInputType('<?= $name?>');});
				}
			});
		</script>

		<?if ($useEditor3):?>
		<script>
			BX.ready(function()
			{
				top.changeType_<?= $name?> = window.changeType_<?= $name?> = function(bSave)
				{
					var
						replaceNewLines = <?= $replaceNewLines ? 'true' : 'false'?>,
						pOptHtml = BX("<?= $bxid?>_html"),
						pOptEditor = BX("<?= $bxid?>_editor");

					var curType = pOptEditor.checked ? 'editor' : 'text';
					if (pOptHtml && pOptHtml.checked)
					{
						curType = 'html';
					}

					// Save choice
					<?if ($params['bSave']):?>
					if (bSave !== false)
					{
						BX.userOptions.save('html_editor', 'type_selector_<?= $name.$key?>', 'type', curType);
					}
					<?endif;?>

					<?if (isset($params['externalFuncName']) && $params['externalFuncName']):?>
					var func = window['<?= $params['externalFuncName']?>'];
					if (func && typeof func == 'function')
					{
						func(curType);
					}
					<?else:?>
					// Editor
					var
						editorName = '<?= $name?>',
						textarea = BX("bxed_<?= $name?>"),
						show = pOptEditor.checked, /*&& textarea.style.display != "none"*/
						editor = window.BXHtmlEditor.Get(editorName),
						textareaValue = textarea.value || '';

					replaceNewLines = replaceNewLines && window.BXHtmlEditor.ReplaceNewLines;

					if (replaceNewLines)
					{
						if (curType == 'html')
						{
							textareaValue = window.BXHtmlEditor.ReplaceNewLines(textareaValue);
							textarea.value = textareaValue;
						}
						else if (curType == 'editor')
						{
							textareaValue = window.BXHtmlEditor.ReplaceNewLines(textareaValue);
						}
						else
						{
							textareaValue = window.BXHtmlEditor.ReplaceNewLinesBack(textareaValue);
							textarea.value = textareaValue;
						}
					}

					function runEditor(editor, textareaValue)
					{
						textarea.style.display = "none";

						editor.Show();
						if (editor.sandbox.inited)
						{
							editor.SetContent(textareaValue, true);
						}
						else
						{
							BX.addCustomEvent(editor, "OnCreateIframeAfter", function()
							{
								editor.SetContent(textareaValue, true);
							});
						}
					}

					if (editor && editor.Check())
					{
						if(show)
						{
							runEditor(editor, textareaValue);
						}
						else
						{
							if (editor.IsShown())
								editor.SaveContent();
							editor.Hide();
							textarea.style.display = "";
							if (replaceNewLines && curType == 'text')
							{
								textareaValue = textarea.value = window.BXHtmlEditor.ReplaceNewLinesBack(textarea.value);
							}
						}
					}
					else if(show)
					{
						BX.addCustomEvent(window.BXHtmlEditor, "OnEditorCreated", function(editor)
						{
							if (editor.id == editorName)
							{
								runEditor(editor, textareaValue);
							}
						});
						window.BXHtmlEditor.Show(false, editorName);
						textarea.style.display = "none";
					}
					<?endif;?>
				}
			});
		</script>
	<?else: /* if ($useEditor3) */ ?>
		<script>
			BX.ready(
				function()
				{
					window.changeType_<?= $name?> = function(bSave)
					{
						var
							pOptHtml = BX("<?= $bxid?>_html"),
							pOptEditor = BX("<?= $bxid?>_editor");

						var curType = pOptEditor.checked ? 'editor' : 'text';
						if (pOptHtml && pOptHtml.checked)
						{
							curType = 'html';
						}

						<?if (isset($params['externalFuncName']) && $params['externalFuncName']):?>
						var func = window['<?= $params['externalFuncName']?>'];
						if (func && typeof func == 'function')
						{
							func(curType);
						}

						<?else:?>
						// Editor
						var el = BX("bxed_<?= $name?>");
						if(pOptEditor.checked && el.style.display != "none")
						{
							var onEditorInit = function(pMainObj)
							{
								pMainObj.SetContent(pMainObj.PreparseHeaders(el.value));
								pMainObj.Show(true);
								pMainObj.LoadContent();
							};

							el.style.display = "none";
							if(!el.pMainObj)
								el.pMainObj = new BXHTMLEditor("<?= $name?>", onEditorInit);
							else
								onEditorInit(el.pMainObj);
						}
						else if(!pOptEditor.checked && el.style.display == "none")
						{
							el.pMainObj.Show(false);
							el.pMainObj.SaveContent(true);
							el.style.display = "";
						}
						<?endif;?>

						// Save choice
						<?if ($params['bSave']):?>
						if (bSave !== false)
						{
							BX.ajax.get('/bitrix/admin/fileman_manage_settings.php?<?= bitrix_sessid_get()?>&target=text_type&edname=<?= $name?>&key=<?= $key?>&type=' + curType);
						}
						<?endif;?>
					};


					var pOptEditor = BX("<?= $bxid?>_editor");
					if (pOptEditor)
					{
						BX.addCustomEvent(pOptEditor.form, 'onAutoSaveRestore', function (ob, data)
						{
							var pOptEditor = BX("<?= $bxid?>_editor");

							setTimeout(function()
							{
								if (pOptEditor.checked)
								{
									var pMainObj = GLOBAL_pMainObj['<?= $name?>'];
									if (pMainObj && pMainObj.bShowed)
									{
										pMainObj.SetContent(data[pMainObj.name]);
										pMainObj.LoadContent();
									}
								}
							}, 100);
						});
					}
				}
			);
		</script>
	<?endif;/* if ($useEditor3) */
		return $textType;
	}

	public static function AddHTMLEditorFrame(
		$strTextFieldName,
		$strTextValue,
		$strTextTypeFieldName,
		$strTextTypeValue,
		$arSize = Array("height"=>350),
		$CONVERT_FOR_WORKFLOW="N",
		$WORKFLOW_DOCUMENT_ID=0,
		$NEW_DOCUMENT_PATH="",
		$textarea_field="",
		$site = false,
		$bWithoutPHP = true,
		$arTaskbars = false,
		$arAdditionalParams = Array()
	)
	{
		// We have to avoid of showing HTML-editor with probably unsecure content when loosing the session [mantis:#0007986]
		if ($_SERVER["REQUEST_METHOD"] == "POST" && !check_bitrix_sessid())
			return;

		global $htmled, $usehtmled;
		$strTextFieldName = preg_replace("/[^a-zA-Z0-9_:\.]/is", "", $strTextFieldName);

		if(is_array($arSize))
			$iHeight = $arSize["height"];
		else
			$iHeight = $arSize;

		$strTextValue = htmlspecialcharsback($strTextValue);
		$dontShowTA = isset($arAdditionalParams['dontshowta']) ? $arAdditionalParams['dontshowta'] : false;

		if ($arAdditionalParams['hideTypeSelector'])
		{
			$textType = $strTextTypeValue == 'html' ? 'editor' : 'text';
			?><input type="hidden" name="<?= $strTextTypeFieldName?>" value="<?= $strTextTypeValue?>"/><?
		}
		else
		{
			$textType = CFileMan::ShowTypeSelector(array(
				'name' => $strTextFieldName,
				'key' => $arAdditionalParams['saveEditorKey'],
				'strTextTypeFieldName' => $strTextTypeFieldName,
				'strTextTypeValue' => $strTextTypeValue,
				'bSave' => $arAdditionalParams['saveEditorState'] !== false
			));
		}

		$curHTMLEd = $textType == 'editor';
		setEditorEventHandlers($strTextFieldName);
		?>
		<textarea class="typearea" style="<? echo(($curHTMLEd || $dontShowTA) ? 'display:none;' : '');?>width:100%;height:<?=$iHeight?>px;" name="<?=$strTextFieldName?>" id="bxed_<?=$strTextFieldName?>" wrap="virtual" <?=$textarea_field?>><?= htmlspecialcharsbx($strTextValue)?></textarea>
		<?

		if ($bWithoutPHP)
			$arTaskbars = Array("BXPropertiesTaskbar", "BXSnippetsTaskbar");
		else if (!$arTaskbars)
			$arTaskbars = Array("BXPropertiesTaskbar", "BXSnippetsTaskbar", "BXComponents2Taskbar");

		$minHeight = $arAdditionalParams['minHeight'] ? intval($arAdditionalParams['minHeight']) : 450;
		$arParams = Array(
			"bUseOnlyDefinedStyles"=>COption::GetOptionString("fileman", "show_untitled_styles", "N")!="Y",
			"bFromTextarea" => true,
			"bDisplay" => $curHTMLEd,
			"bWithoutPHP" => $bWithoutPHP,
			"arTaskbars" => $arTaskbars,
			"height" => max($iHeight, $minHeight)
		);

		if (isset($arAdditionalParams['use_editor_3']))
			$arParams['use_editor_3'] = $arAdditionalParams['use_editor_3'];

		$arParams['site'] = (strlen($site)<=0?LANG:$site);
		if(isset($arSize["width"]))
			$arParams["width"] = $arSize["width"];

		if (isset($arAdditionalParams))
			$arParams["arAdditionalParams"] = $arAdditionalParams;

		if (isset($arAdditionalParams['limit_php_access']))
			$arParams['limit_php_access'] = $arAdditionalParams['limit_php_access'];

		if (isset($arAdditionalParams['toolbarConfig']))
			$arParams['toolbarConfig'] = $arAdditionalParams['toolbarConfig'];

		if (isset($arAdditionalParams['componentFilter']))
			$arParams['componentFilter'] = $arAdditionalParams['componentFilter'];

		$arParams['setFocusAfterShow'] = isset($arParams['setFocusAfterShow']) ? $arParams['setFocusAfterShow'] : false;

		CFileman::ShowHTMLEditControl($strTextFieldName, $strTextValue, $arParams);
	}

	public static function ShowHTMLEditControl($name, $content, $arParams = Array())
	{
		global $USER;
		// We have to avoid of showing HTML-editor with probably unsecure content when loosing the session [mantis:#0007986]
		if ($_SERVER["REQUEST_METHOD"] == "POST" && !check_bitrix_sessid())
		{
			return;
		}

		CUtil::InitJSCore(array('window', 'ajax'));
		$relPath = (isset($arParams["path"])) ? $arParams["path"] : "/";
		$site = (isset($arParams["site"])) ? $arParams["site"] : "";
		$__path = Rel2Abs("/", $relPath);
		$site = CFileMan::__CheckSite($site);
		$name = preg_replace("/[^a-zA-Z0-9_:\.]/is", "", $name);

		if (!isset($arParams["limit_php_access"]))
			$arParams["limit_php_access"] = false;

		$arParams["light_mode"] = (defined('BX_PUBLIC_MODE') && BX_PUBLIC_MODE == 1) || (isset($arParams["light_mode"]) && $arParams["light_mode"] == 'Y');

		$io = CBXVirtualIo::GetInstance();
		$direction_rtl = false;
		if($site)
		{
			$DOC_ROOT = CSite::GetSiteDocRoot($site);
			$abs_path = $DOC_ROOT.$__path;
			if ($io->FileExists($abs_path))
			{
				$relPath = substr($relPath,0,strrpos($relPath,"/"));
				if ($relPath=="")
					$relPath = "/";
			}
		}

		static $bFirstUsed;
		$template = $arParams["templateID"];

		if (!isset($template) && defined('SITE_TEMPLATE_ID'))
		{
			$template = SITE_TEMPLATE_ID;
		}

		if (!isset($template) && isset($_GET['siteTemplateId']))
		{
			$template = $_GET['siteTemplateId'];
		}

		if (!isset($template) && isset($site))
		{
			$dbSiteRes = CSite::GetTemplateList($site);
			$SITE_TEMPLATE = Array();
			while($arSiteRes = $dbSiteRes->Fetch())
			{
				if ($arSiteRes['CONDITION'] == "" || !isset($template))
					$template = $arSiteRes['TEMPLATE'];
			}
		}

		if (isset($arParams['use_editor_3']))
		{
			$useEditor3 = $arParams['use_editor_3'] == "Y";
		}
		else
		{
			$useEditor3 = COption::GetOptionString('fileman', "use_editor_3", "Y") == "Y";
		}

		if ($useEditor3)
		{
			$Editor = new CHTMLEditor;
			$Editor->Show(array(
				'name' => $name,
				'id' => $name,
				'siteId' => $arParams["site"],
				'width' => $arParams["width"],
				'height' => $arParams["height"],
				'content' => $content,
				'bAllowPhp' => !$arParams["bWithoutPHP"] && $USER->CanDoOperation('edit_php'),
				"limitPhpAccess" => $arParams["limit_php_access"],
				"display" => $arParams['bDisplay'],
				"componentFilter" => (isset($arParams['componentFilter']) ? $arParams['componentFilter'] : false),
				"setFocusAfterShow" => isset($arParams['setFocusAfterShow']) ? $arParams['setFocusAfterShow'] : true,
				"relPath" => $relPath,
				"templateId" => $template
			));
			return;
		}

		//Taskbars
		$arTaskbars = (isset($arParams["arTaskbars"])) ? $arParams["arTaskbars"] : Array();
		//Toolbars
		$arToolbars = (isset($arParams["arToolbars"])) ? $arParams["arToolbars"] : false;
		// Toolbar config
		$arParams["toolbarConfig"] = (is_array($arParams["toolbarConfig"])) ? $arParams["toolbarConfig"] : false;

		$arParams["use_advanced_php_parser"] = COption::GetOptionString("fileman", "use_advanced_php_parser", "Y");
		$arParams["ar_entities"] = COption::GetOptionString("fileman", "ar_entities", 'umlya,greek,other');
		if ($arParams["ar_entities"] == 'none')
			$arParams["ar_entities"] = '';

		if(!isset($arParams["usePspell"]))
			$arParams["usePspell"] = COption::GetOptionString("fileman", "use_pspell", "N");

		if(!isset($arParams["useCustomSpell"]))
			$arParams["useCustomSpell"] = COption::GetOptionString("fileman", "use_custom_spell", "Y");

		$arParams['allowRenderComp2'] = COption::GetOptionString('fileman', "allow_render_components", "N") == 'Y';
		$arParams['renderComponents'] = $arParams['allowRenderComp2']  && CUserOptions::GetOption('fileman', "render_components", "Y") == 'Y';

		$lca = COption::GetOptionString("fileman", "use_lca", "N");
		$styleList_render_style = (COption::GetOptionString("fileman", "render_styles_in_classlist", "Y") == 'Y') ? 'true' : 'false';
		$arAdditionalParams = (isset($arParams["arAdditionalParams"])) ? $arParams["arAdditionalParams"] : Array();

		$arResult = CFileman::GetAllTemplateParams($template, $site, ($arParams["bWithoutPHP"] != true),$arAdditionalParams);
		$arParams["TEMPLATE"] = $arResult;
		if($bUseOnlyDefinedStyles && !is_set($arResult, "STYLES_TITLE"))
			$bUseOnlyDefinedStyles = false;

		$arParams["body_class"] = COption::GetOptionString("fileman", "editor_body_class", "");
		$arParams["body_id"] = COption::GetOptionString("fileman", "editor_body_id", "");

		?>
		<script bxrunfirst="true">
			var relPath = "<?= CUtil::JSEscape($relPath);?>";
			var <? echo 'ar_'.$name.'_taskbars';?> = {};
			<?
			for ($k = 0, $l = count($arTaskbars); $k < $l; $k++)
				echo 'ar_'.$name.'_taskbars["'.$arTaskbars[$k].'"] = true;';
			if ($arToolbars !== false)
			{
				echo 'var  ar_'.$name.'_toolbars = {};';
				for ($k = 0, $l = count($arToolbars); $k < $l; $k++)
					echo 'ar_'.$name.'_toolbars["'.$arToolbars[$k].'"] = true;';
			}
			else
				echo 'var  ar_'.$name.'_toolbars = false;';
			?>

			window.ar_<?= $name?>_config = <?= CUtil::PhpToJSObject($arParams)?>; // editor-config
		</script>
		<?
		$str_taskbars = "";
		CFileman::GetHTMLEditorSettings($name, $arParams["light_mode"], $arTaskbars, $str_taskbars);
		$str_taskbars .= '_'.CFileman::GetVersion();

		if(!$bFirstUsed)
		{
			$arTemplates = Array(Array('value' => '.default', 'name' => GetMessage("FILEMAN_DEFTEMPL")));
			$db_site_templates = CSiteTemplate::GetList(array(), array(), array());
			while($ar_site_templates = $db_site_templates->Fetch())
				$arTemplates[] = Array('value'=>$ar_site_templates['ID'], 'name'=>$ar_site_templates['NAME']);

			?>
			<script bxrunfirst="true">
				var
					arBXTemplates = <?= CUtil::PhpToJSObject($arTemplates)?>,
					BXSite = "<?= CUtil::JSEscape($site)?>",
					BXLang = "<?= CUtil::JSEscape(LANGUAGE_ID)?>",
					styleList_render_style = <?=$styleList_render_style?>,
					limit_php_access = <?= $arParams["limit_php_access"] ? 'true' : 'false'?>,
					lca = <?= $lca == 'Y' ? 'true' : 'false'?>,
					lightMode = <?= $arParams["light_mode"] ? 'true' : 'false'?>,
					BX_PERSONAL_ROOT = "<?=BX_PERSONAL_ROOT?>";

				window.limit_php_access = top.limit_php_access = limit_php_access;
				window.lightMode = top.lightMode = lightMode;
				window.lca = top.lca = lca;
				window.BXLang = top.BXLang = BXLang;
				window.BXSite = top.BXSite = BXSite;
				window.BX_PERSONAL_ROOT = top.BX_PERSONAL_ROOT = BX_PERSONAL_ROOT;
			</script>
			<?

			$arJS = Array();
			$arCSS = Array();
			$events = GetModuleEvents("fileman", "OnBeforeHTMLEditorScriptsGet");
			while($arEvent = $events->Fetch())
			{
				$tmp = ExecuteModuleEventEx($arEvent, array($name, $arParams));
				if (!is_array($tmp))
					continue;

				if (is_array($tmp['JS']))
					$arJS = array_merge($arJS, $tmp['JS']);
				if (is_array($tmp['CSS']))
					$arCSS = array_merge($arCSS, $tmp['CSS']);
			}
			$arr = Array();
			// Additional JS files from event OnBeforeHtmlEditorScriptGet
			for($i = 0, $c = count($arJS); $i < $c; $i++)
			{
				$arJS[$i] = preg_replace("/[^a-zA-Z0-9_:\.]/is", "", $arJS[$i]);
				if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/htmleditor2/'.$arJS[$i]))
					$arr[] = $arJS[$i];
			}
			?>
			<script type="text/javascript" src="/bitrix/admin/fileman_js.php?lang=<?=LANGUAGE_ID?>&v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/fileman/lang/'.LANGUAGE_ID.'/admin/fileman_js.php')?>"></script>
			<script type="text/javascript" src="/bitrix/admin/fileman_common_js.php?s=<?=$str_taskbars?>"></script>
			<?
			for($i = 0, $l = count($arr); $i < $l; $i++)
			{
				$script_filename = $arr[$i];
				?><script type="text/javascript" src="/bitrix/admin/htmleditor2/<?=$script_filename?>?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/htmleditor2/'.$script_filename)?>"></script><?
			}
			?>
			<script type="text/javascript" src="/bitrix/js/main/popup_menu.js?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/main/popup_menu.js')?>"></script>
			<?
			for($i = 0, $l = count($arCSS); $i < $l; $i++) // Additional CSS files from event OnBeforeHtmlEditorScriptGet
			{
				$arCSS[$i] = preg_replace("/[^a-zA-Z0-9_:\.]/is", "", $arCSS[$i]);
				if(!file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/htmleditor2/'.$arCSS[$i]))
					continue;
				?><link rel="stylesheet" type="text/css" href="/bitrix/admin/htmleditor2/<?=$arCSS[$i]?>?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/htmleditor2/'.$arCSS[$i])?>"/><?
			}

			$db_events = GetModuleEvents("fileman", "OnIncludeHTMLEditorScript");
			while($arEvent = $db_events->Fetch())
			{
				ExecuteModuleEventEx($arEvent);
			}
			$bFirstUsed = true;
		}
		?>
		<div class="bxedmain-cont" id="<?= $name.'_object';?>"><table id="<?= $name?>_pFrame" class="bxedmainframe dim100x100" style="display:none;">
				<tr style="height: 1%;"><td id="<?= $name?>_toolBarSet0" colspan="2" style="width: 100%; display: none; border-bottom: 1px solid #808080 !important;"></td></tr>
				<tr>
					<td id="<?= $name?>_toolBarSet1" style="width:0%; display: none; border-right: 1px solid #808080 !important;"></td>
					<td vAlign="top" style="width: 4000px; padding: 0!important;">
						<table class="dim100x100">
							<tr>
								<td class="bx-ceditor" id="<?= $name?>_cEditor"></td>
								<td id="<?= $name?>_taskBarSet2" class="bxedtaskbarset" style="width:0%; display: none;">
									<table>
										<tr><td class="bx-move-col-v" rowSpan="3"><img src="/bitrix/images/1.gif" /></td><td style="height:26px;"></td></tr>
										<tr><td style="vertical-align: top;"></td></tr>
										<tr><td class="bx-taskbar-tabs"></td></tr>
									</table>
								</td>
							</tr>
							<tr style="height:0%; display: none;">
								<td id="<?= $name?>_taskBarSet3" colspan="2">
									<table>
										<tr><td class="bx-move-col-h"><img src="/bitrix/images/1.gif" /></td></tr>
										<tr><td style="height:26px;"></td></tr>
										<tr><td style="vertical-align: top; background: #F4F4F4 none !important;"></td></tr>
										<tr><td class="bx-taskbar-tabs"></td></tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr id="bx-css-tt"><td id="<?= $name?>_taskBarTabs" colspan="2" class="tasktabcell"></td></tr>
			</table>
		</div>
		<script>
			BX.loadCSS('/bitrix/admin/htmleditor2/editor.css');
			var bEd = BX("bxed_<?= $name?>_editor");
			if (bEd && !bEd.checked)
				BX("<?= $name?>_object").style.display = "none";
		</script>
		<?
		if(!$arParams["bFromTextarea"])
			echo '<input type="hidden" name="'.$name.'" id="bxed_'.$name.'" value="'.htmlspecialcharsbx($content).'">';

		if($arParams["bDisplay"] !== false)
		{
			setEditorEventHandlers($name);
			?>
			<script>
				BX.ready(function(){
					BX.showWait();
					BX("bxed_<?= $name;?>").pMainObj  = new BXHTMLEditor("<?= $name;?>");
				});
			</script>
		<?
		}
	}

	public static function GetFileTemplates($lang = LANG, $arTemplates=Array())
	{
		return GetFileTemplates($lang, $arTemplates);
	}

	public static function GetTemplateContent($filename, $lang=LANG, $arTemplates=Array())
	{
		return GetTemplateContent($filename, $lang, $arTemplates);
	}

	public static function GetScriptFileExt()
	{
		return GetScriptFileExt();
	}

	public static function ParseFileContent($filesrc, $bCheckProlog = false)
	{
		$res = ParseFileContent($filesrc);
		if ($bCheckProlog)
		{
			$prolog = trim(strtolower($res['PROLOG']));
			if (strlen($prolog) > 0 &&
				strpos($prolog, "prolog_before") === false &&
				strpos($prolog, 'bitrix/header.php') === false &&
				strpos($prolog, '$application->settitle') === false &&
				strpos($prolog, '$application->setpageproperty') === false)
			{
				$res['CONTENT'] = $res['PROLOG']."\n".$res['CONTENT'];
				$res['PROLOG'] = '';
			}

			$epilog = trim(strtolower($res['EPILOG']));
			if (strlen($epilog) > 0 &&
				strpos($epilog, 'bitrix/footer.php') === false &&
				strpos($epilog, 'epilog.php') === false)
			{
				$res['CONTENT'] = $res['CONTENT']."\n".$res['EPILOG'];
				$res['EPILOG'] = '';
			}
		}
		return $res;
	}

	public static function SetTitle($prolog, $title)
	{
		return SetPrologTitle($prolog, $title);
	}

	public static function SetProperty($prolog, $property_key, $property_val)
	{
		return SetPrologProperty($prolog, $property_key, $property_val);
	}

	public static function IsPHP($src)
	{
		return IsPHP($src);
	}

	public static function GetAllTemplateParams($templateID, $site, $findcomponent = true, $arAdditionalParams = Array())
	{
		global $APPLICATION;

		$db_templ = CSiteTemplate::GetByID($templateID);
		if(!($ar_templ = $db_templ->Fetch()))
		{
			$templateID = "";
			$db_site_templ = CSite::GetTemplateList($site);
			while($ar_site_templ = $db_site_templ->Fetch())
			{
				if(strlen($ar_site_templ["CONDITION"])<=0)
				{
					$templateID = $ar_site_templ["TEMPLATE"];
					break;
				}
			}

			if(strlen($templateID)>0)
			{
				$db_templ = CSiteTemplate::GetByID($templateID);
				$ar_templ = $db_templ->Fetch();
			}
		}

		if($ar_templ)
		{
			$arResult = Array(
				"ID" => $ar_templ["ID"],
				"NAME" => $ar_templ["NAME"]
			);

			if(is_set($ar_templ, "STYLES"))
			{
				// Fetch @import and include it to CSS - will include css from comments also :(.
				$pattern = '/^@import(.*)$/im';
				$matches = array();
				if (preg_match_all($pattern, $ar_templ["STYLES"], $matches))
				{
					for ($j = 0, $l = count($matches[0]); $j < $l; $j++)
					{
						$str = $matches[0][$j];
						$url = trim(trim($matches[1][$j]), '"\';');
						$css = "";
						if (substr($url, -5) != 'print')
						{
							$url = trim(trim($url), ' "\';');
							if (substr($url, 0, 4) == 'url(' && substr($url, -1) == ')')
								$url = trim(substr($url, 4, -1), ' "\'');
							$url = trim(trim($url), '\'";');
							if (substr($url, 0, 1) != '/' && file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$ar_templ["ID"]."/".$url))
								$css = "\n".$APPLICATION->GetFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$ar_templ["ID"]."/".$url)."\n";
							else if(file_exists($_SERVER["DOCUMENT_ROOT"].$url))
								$css = "\n".$APPLICATION->GetFileContent($_SERVER["DOCUMENT_ROOT"].$url)."\n";
						}
						$ar_templ["STYLES"] = str_replace($matches[0][$j], $css, $ar_templ["STYLES"]);
					}
				}

				$arResult["STYLES"] = $ar_templ["STYLES"];
				$arResult["STYLES_TITLE"] = $ar_templ["STYLES_TITLE"];
				$arResult["EDITOR_STYLES"] = $ar_templ["EDITOR_STYLES"];
			}
		}
		else
		{
			$arResult = Array("ID" => ".default", "NAME" => GetMessage("FILEMAN_DDEF_TEMPLATE"));
			$templateID = "";
		}

		if(!is_set($arResult, "STYLES") || $arResult["STYLES"]==false)
		{
			if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/".(strlen($site)<=0?LANGUAGE_ID:$site)."/styles.css"))
			{
				$arResult["STYLES"] = $APPLICATION->GetFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/".(strlen($site) <= 0?LANGUAGE_ID : $site)."/styles.css");
				$arResult["STYLES_TITLE"] = CSiteTemplate::__GetByStylesTitle($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/".(strlen($site)<=0?LANGUAGE_ID : $site)."/.styles.php");
			}
			elseif(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/styles.css"))
			{
				$arResult["STYLES"] = $APPLICATION->GetFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/styles.css");
				$arResult["STYLES_TITLE"] = CSiteTemplate::__GetByStylesTitle($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/.styles.php");
			}
			else
			{
				$arResult["STYLES"] = $APPLICATION->GetFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/.default/styles.css");
				$arResult["STYLES_TITLE"] = CSiteTemplate::__GetByStylesTitle($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/.default/.styles.php");
			}
		}

		if (isset($arAdditionalParams['additionalCSS']))
		{
			$additionalCSS = $arAdditionalParams['additionalCSS'];
			for ($i = 0, $l = count($additionalCSS); $i < $l; $i++)
			{
				$css_file_path = $additionalCSS[$i];
				$arResult["STYLES"] .= "\r\n".$APPLICATION->GetFileContent($css_file_path);
			}
		}

		if(strlen($templateID)>0 && file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$templateID."/editor.css"))
			$arResult["STYLES"] .= "\r\n".$APPLICATION->GetFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$templateID."/editor.css");
		elseif(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/.default/editor.css"))
			$arResult["STYLES"] .= "\r\n".$APPLICATION->GetFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/.default/editor.css");
		elseif(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/".$site."/editor.css"))
			$arResult["STYLES"] .= "\r\n".$APPLICATION->GetFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/".(strlen($site)<=0?LANGUAGE_ID:$site)."/editor.css");
		elseif(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/editor.css"))
			$arResult["STYLES"] .= "\r\n".$APPLICATION->GetFileContent($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/editor.css");

		$arResult["STYLES"] = preg_replace("/\r\n/", " ", $arResult["STYLES"]);
		$arResult["STYLES"] = preg_replace("/\n/", " ", $arResult["STYLES"]);

		$arResult["SITE_TEMPLATE_PATH"] =  getLocalPath('templates/'.$templateID, BX_PERSONAL_ROOT);

		return $arResult;
	}

	public static function __CheckOnAllowedComponents($str)
	{
		$allowed_components = trim(COption::GetOptionString('fileman', "~allowed_components", ''));
		if ($allowed_components == '')
			return true;

		$arAC = explode("\n",$allowed_components);
		$arAC = array_unique($arAC);
		$arAllowedComponents = Array();
		foreach ($arAC as $f)
		{
			if (trim($f) == '')
				continue;

			$f = preg_replace("/\s/is", "", $f);
			$f = preg_replace("/\./is", "\\.", $f);
			$f = preg_replace("/\*/is", ".*", $f);
			$arAllowedComponents[] = '/^'.$f.'$/';
		}
		if (count($arAllowedComponents) == 0)
			return true;
		$comp_RE = '/\$application->includecomponent\(\s*?(.*?),/i';
		preg_match_all($comp_RE, $str, $matches);

		for ($i = 0, $l = count($matches[1]); $i < $l; $i++)
		{
			$name = trim($matches[1][$i]);
			$er_name = $name;
			$name = substr($name, 1, -1);
			$bx = 'bitrix:';
			$bxlen = strlen($bx);
			if (substr($name, 0, $bxlen) != $bx)
				return $er_name;
			$name = substr($name, $bxlen);
			for ($j = 0, $c = count($arAllowedComponents); $j < $c; $j++)
				if (preg_match($arAllowedComponents[$j], $name))
					continue 2;

			return $er_name;
		}
		return true;
	}

	public static function CheckOnAllowedComponents($str)
	{
		if (($r = CFileMan::__CheckOnAllowedComponents($str)) !== true)
		{
			$GLOBALS['APPLICATION']->ThrowException(GetMessage("FILEMAN_UNALLOWED_COMPONENTS", Array("#BAD_COMPONENT#" => $r)), "UNALLOWED_COMPONENTS");
			return false;
		}
		return true;
	}

	public static function GetHTMLEditorSettings($edname, $lightMode, $arTaskbars, &$loadParams)
	{
		?>
		<script>
			//Array of settings
			if (!window.SETTINGS)
				SETTINGS = {};

			SETTINGS['<?= $edname?>'] = {};
			<?
					$loadParams = '';
					if (!$lightMode)
					{
						//Get toolbar settings
						$toolbar_settings = stripslashes(CUserOptions::GetOption("fileman", "toolbar_settings_".$edname));
						$rs_tlbrs = stripslashes(CUserOptions::GetOption("fileman", "rs_toolbar_".$edname, 'Y'));

						if ($toolbar_settings)
						{
							?>SETTINGS['<?= $edname?>'].arToolbarSettings = [];<?
				$res = explode("||", $toolbar_settings);
				for ($i = 0, $len = count($res); $i < $len; $i++)
				{
					$tmp = explode(":", $res[$i]);
					$tlbrname = $tmp[0];
					$tmp2 = explode(",", $tmp[1]);
					$show = $tmp2[0];
					$docked = $tmp2[1];
					$arPos = explode(";", substr($tmp2[2], 1, -1));
?>
			var _ar = [];
			_ar.show = <?echo($show == 'true' ? 'true' : 'false');?>;
			_ar.docked = <?echo($docked=='true' ? 'true' : 'false');?>;
			<?if ($docked=='true'):?>
			_ar.position = [<?echo$arPos[0];?>,<?echo$arPos[1];?>,<?echo$arPos[2];?>];
			<?else:?>
			_ar.position = {
				x : '<?echo(substr($arPos[0],-2)=="px" ? substr($arPos[0],0,-2) : $arPos[0]);?>',
				y : '<?echo(substr($arPos[1],-2)=="px" ? substr($arPos[1],0,-2) : $arPos[1]);?>'
			};
			<?endif;?>

			SETTINGS['<?= $edname?>'].arToolbarSettings["<?=$tlbrname?>"] = _ar;
			<?
							}
						}
						$loadParams = 'em'; // extended mode
					}

					//Get taskbar settings
					$taskbars = CUserOptions::GetOption("fileman", "taskbar_settings_".$edname, false);
					if ($taskbars !== false && CheckSerializedData($taskbars, 10))
						$taskbars = unserialize($taskbars);
					else
						$taskbars = false;

					if (is_array($taskbars))
					{
						?>SETTINGS['<?= $edname?>'].arTaskbarSettings = {};<?
			foreach($taskbars as $tname => $tskbr)
			{
				// Display settings
				?>SETTINGS['<?= $edname?>'].arTaskbarSettings["<?=$tname;?>"] = {show: <?= $tskbr['show'] ? 'true' : 'false'?>, set: <?= $tskbr['set'] == 2 ? 2 : 3?>, active: <?= $tskbr['active'] ? 'true' : 'false'?>};<?
				if ($tskbr['show'] && in_array($tname, $arTaskbars))
				{
					switch ($tname)
					{
						case 'BXSnippetsTaskbar':
							$loadParams .= 's';
							break;
						case 'BXComponents2Taskbar':
							$loadParams .= 'c2';
							break;
					}
				}
			}
		}
		else
		{
			if (in_array('BXSnippetsTaskbar', $arTaskbars))
				$loadParams .= 's';
			if (in_array('BXComponents2Taskbar', $arTaskbars))
				$loadParams .= 'c2';
		}

		//Get taskbarset settings
		$taskbarset = CUserOptions::GetOption("fileman", "taskbarset_settings_".$edname, false);

		if ($taskbarset !== false && CheckSerializedData($taskbarset, 10))
			$taskbarset = unserialize($taskbarset);
		else
			$taskbarset = false;

		if (is_array($taskbarset))
		{
			?>SETTINGS['<?= $edname?>'].arTBSetsSettings = [];<?
			foreach($taskbarset as $iNum => $tskbrset)
			{
				if ($iNum != 2)
					$iNum = 3;
				?>SETTINGS['<?= $edname?>'].arTBSetsSettings["<?= intVal($iNum)?>"] = {show: <?= $tskbrset['show'] ? 'true' : 'false'?>, size: <?= intVal($tskbrset['size'])?>};
			<?
		}
	}

	$show_tooltips = CUserOptions::GetOption("fileman", "show_tooltips".$edname, "Y");
	$visualEffects = CUserOptions::GetOption("fileman", "visual_effects".$edname, "Y");
	$arC2DS = CUtil::GetPopupSize("bx_edc2_".$edname, array("width" => 650, "height" => 450));
	?>

			SETTINGS['<?= $edname?>'].showTooltips4Components = <?echo $show_tooltips == "N" ? "false" : "true";?>;
			SETTINGS['<?= $edname?>'].visualEffects = <?echo $visualEffects == "N" ? "false" : "true";?>;

			window.comp2_dialog_size = {width: '<?= $arC2DS['width']?>', height: '<?= $arC2DS['height']?>'};
		</script>
		<?
		//return $str_res;
	}

	public static function CheckFileName($str)
	{
		$io = CBXVirtualIo::GetInstance();
		if (!$io->ValidateFilenameString($str))
			return GetMessage("FILEMAN_NAME_ERR");
		return true;
	}

	public static function GetPropstypes($site="")
	{
		$defRes = Array(
			'description' => GetMessage("FILEMAN_OPTION_PROPS_DESCR"),
			'keywords' => GetMessage("FILEMAN_OPTION_PROPS_KEYW")
		);
		$res = COption::GetOptionString('fileman', "propstypes", addslashes(serialize($defRes)), $site);
		if (CheckSerializedData($res))
			$res = unserialize(stripslashes($res));
		else
			$res = $defRes;
		return $res;
	}

	public static function SetPropstypes($arPT = Array(), $desc = false, $site = "")
	{
		$str = addslashes(serialize($arPT));
		if (strlen($str) > 2000)
			return false;
		return COption::SetOptionString('fileman', "propstypes", $str, $desc, $site);

	}

	public static function OnModuleUpdate($arParams)
	{
		if (isset($arParams['successModules']) && count($arParams['successModules']) > 0)
			CFileMan::ClearComponentsListCache();
	}

	public static function ClearComponentsListCache($id = '')
	{
		$GLOBALS["CACHE_MANAGER"]->CleanDir("fileman_component_tree_array");
	}

	public static function SecurePathVar($str)
	{
		$str = preg_replace("/\.\.+[\/\\\]+/i", "", $str);
		return $str;
	}

	public static function GetUnixFilePermissions($file)
	{
		$io = CBXVirtualIo::GetInstance();
		$f = $io->GetFile($file);
		$perms = $f->GetPermissions();

		if (($perms & 0xC000) == 0xC000)
			$info = 's';	// Socket
		elseif (($perms & 0xA000) == 0xA000)
			$info = 'l';	// Symbolic Link
		elseif (($perms & 0x8000) == 0x8000)
			$info = '-'; // Regular
		elseif (($perms & 0x6000) == 0x6000)
			$info = 'b'; // Block special
		elseif (($perms & 0x4000) == 0x4000)
			$info = 'd'; // Directory
		elseif (($perms & 0x2000) == 0x2000)
			$info = 'c';	// Character special
		elseif (($perms & 0x1000) == 0x1000)
			$info = 'p';	// FIFO pipe
		else
			$info = 'u';	// Unknown

		// Owner
		$info .= (($perms & 0x0100) ? 'r' : '-');
		$info .= (($perms & 0x0080) ? 'w' : '-');
		$info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));

		// Group
		$info .= (($perms & 0x0020) ? 'r' : '-');
		$info .= (($perms & 0x0010) ? 'w' : '-');
		$info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));

		// World
		$info .= (($perms & 0x0004) ? 'r' : '-');
		$info .= (($perms & 0x0002) ? 'w' : '-');
		$info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));

		return array(sprintf("%o", $perms & 0xfff), $info);
	}

	public static function IsWindows()
	{
		return PATH_SEPARATOR === ';';
	}

	public static function SaveLastPath($path)
	{
		$path = CFileMan::NormalizePath($path);
		if ($path == "" || $path == "/")
			return;

		$arPathes = CFileMan::GetLastPathes();
		$key = array_search($path, $arPathes);

		if ($key !== false)
			unset($arPathes[$key]);

		$arPathes = array_merge(array($path), $arPathes);

		CFileMan::SetLastPathes($arPathes);
	}

	public static function GetLastPathes()
	{
		$arPathes = CUserOptions::GetOption("fileman", "last_pathes", false);
		$arPathes = ($arPathes === false || !CheckSerializedData($arPathes)) ? CFileMan::GetLastPathesDefault() : unserialize($arPathes);
		$arPathes = array_slice($arPathes, 0, 10);

		return $arPathes;
	}

	public static function SetLastPathes($arPathes = array())
	{
		if (count($arPathes) == 0)
			$arPathes = CFileMan::GetLastPathesDefault();
		$arPathes = array_slice($arPathes, 0, 10);
		CUserOptions::SetOption("fileman", "last_pathes", serialize($arPathes));
	}

	public static function GetLastPathesDefault()
	{
		return array("/bitrix");
	}

	public static function GetEditorToolbarConfig($editorType)
	{
		$res = COption::GetOptionString('fileman', "toolbar_config_".$editorType, false);
		if ($res && CheckSerializedData($res))
		{
			$arConfig = unserialize($res);
			if (is_array($arConfig))
				return $arConfig;
		}
		return false;
	}
}

function is_array_assoc($arr)
{
	$i = 0;
	foreach($arr as $k=>$val)
	{
		if("".$k!="".$i)
			return true;
		$i++;
	}
	return false;
}

function setEditorEventHandlers($name)
{
	?>
	<script>
		function onContextMenu_<?= $name;?>(e){GLOBAL_pMainObj['<?= $name;?>'].OnContextMenu(e);}
		function onClick_<?= $name;?>(e){GLOBAL_pMainObj['<?= $name;?>'].OnClick(e);}
		function onDblClick_<?= $name;?>(e){GLOBAL_pMainObj['<?= $name;?>'].OnDblClick(e);}
		function onMouseUp_<?= $name;?>(e){GLOBAL_pMainObj['<?= $name;?>'].OnMouseUp(e);}
		function onDragDrop_<?= $name;?>(e){GLOBAL_pMainObj['<?= $name;?>'].OnDragDrop(e);}
		function onKeyPress_<?= $name;?>(e){GLOBAL_pMainObj['<?= $name;?>'].OnKeyPress(e);}
		function onKeyDown_<?= $name;?>(e){GLOBAL_pMainObj['<?= $name;?>'].OnKeyDown(e);}
		function onPaste_<?= $name;?>(e){GLOBAL_pMainObj['<?= $name;?>'].OnPaste(e);}

		function OnSubmit_<?= $name;?>(e){GLOBAL_pMainObj['<?= $name;?>'].onSubmit(e);}

		function OnDispatcherEvent_pDocument_<?= $name;?>(e){pBXEventDispatcher.OnEvent(GLOBAL_pMainObj['<?= $name;?>'].pDocument, e);}
		function OnDispatcherEvent_pEditorDocument_<?= $name;?>(e){pBXEventDispatcher.OnEvent(GLOBAL_pMainObj['<?= $name;?>'].pEditorDocument, e);}
	</script>
<?
}

function _replace_br_($str)
{
	return $str;
	$pos2 = strpos(strtolower($str), "\n");
	if ($pos2!==FALSE)
	{
		$str = str_replace("\r"," ",$str);
		$str = str_replace("\n"," ",$str);
		$str = str_replace("\\r"," ",$str);
		$str = str_replace("\\n"," ",$str);
	}
	return $str;
}
?>