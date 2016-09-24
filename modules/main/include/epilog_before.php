<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

global $USER, $APPLICATION;

// define("START_EXEC_EPILOG_BEFORE_1", microtime());
$GLOBALS["BX_STATE"] = "EB";

/* Draw edit menu for whole content */
global $BX_GLOBAL_AREA_EDIT_ICON; //set in prolog_after.php
if($BX_GLOBAL_AREA_EDIT_ICON == true)
{
	IncludeModuleLangFile(__FILE__);

	if(isset($_SERVER["REAL_FILE_PATH"]) && $_SERVER["REAL_FILE_PATH"] != "")
		$currentFilePath = $_SERVER["REAL_FILE_PATH"];
	else
		$currentFilePath = $APPLICATION->GetCurPage(true);

	$encCurrentFilePath = urlencode($currentFilePath);
	$encUri = urlencode($_SERVER["REQUEST_URI"]);
	$encSiteTemplateId = urlencode(SITE_TEMPLATE_ID);

	$arIcons = array(
		array(
			"TITLE"=>GetMessage("main_epilog_before_menu_edit"),
			"ALT"=>GetMessage("main_epilog_before_menu_edit_title"),
			"ICON"=>"bx-context-toolbar-edit-icon",
			"URL"=> 'javascript:'.$APPLICATION->GetPopupLink(Array(
				"URL"=> "/bitrix/admin/public_file_edit.php?bxpublic=Y&lang=".LANGUAGE_ID."&path=".$encCurrentFilePath."&site=".SITE_ID."&back_url=".$encUri."&templateID=".$encSiteTemplateId,
				"PARAMS"=>array(
					"width" => 770,
					"height" => 470,
					"dialog_type" => 'EDITOR',
					"min_width" => 700,
					"min_height" => 400
				))
			),
			"DEFAULT"=>true,
			"MENU" => array
			(
				array(
					"TEXT"=>GetMessage("main_epilog_before_menu_edit_html"),
					"TITLE"=>GetMessage("main_epilog_before_menu_edit_html_title"),
					"ICON"=>"panel-edit-text",
					"ACTION"=>'javascript:'.$APPLICATION->GetPopupLink(Array(
						"URL"=>"/bitrix/admin/public_file_edit.php?bxpublic=Y&lang=".LANGUAGE_ID."&noeditor=Y&path=".$encCurrentFilePath."&site=".SITE_ID."&back_url=".$encUri,
						"PARAMS"=>array("width"=>770, "height"=>470))
					),
				),
				//array('SEPARATOR'=>true),
				array(
					"TEXT"=>GetMessage("main_epilog_before_menu_prop"),
					"TITLE"=>GetMessage("main_epilog_before_menu_prop_title"),
					"ICON"=>"panel-file-props",
					"ACTION"=> 'javascript:'.$APPLICATION->GetPopupLink(Array(
						"URL"=>"/bitrix/admin/public_file_property.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&path=".$encCurrentFilePath."&back_url=".$encUri,
						"PARAMS" => Array("min_width"=>450, "min_height" => 250))
					),
				),
				array('SEPARATOR' => true),
				array(
					"TEXT"=>GetMessage('main_epilog_before_remove_panel'),
					"TITLE"=>GetMessage('main_epilog_before_remove_panel_title'),
					"ACTION"=> 'javascript:if (confirm(\''.CUtil::JSEscape(GetMessage('main_epilog_before_remove_panel_confirm')).'\')) window.PAGE_EDIT_CONTROL.Remove()',
				),
			),
		),
	);

	echo $APPLICATION->IncludeStringAfter($arIcons, array('TOOLTIP'=>GetMessage("main_epilog_before_menu_title"), 'ICON'=>'edit-icon', "COMPONENT_ID" => "page_edit_control"));
}

\Bitrix\Main\Page\Asset::getInstance()->startTarget('TEMPLATE');
$sPreviewFile = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/tmp/templates/__bx_preview/footer.php";
if(defined("SITE_TEMPLATE_PREVIEW_MODE") && file_exists($sPreviewFile))
{
	include_once($sPreviewFile);
}
else
{
	include_once($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/footer.php");
}
