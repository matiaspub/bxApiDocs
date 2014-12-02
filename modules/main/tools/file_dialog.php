<?
//**************************** FILE DIALOG ACTIONS *************************************
// File dialog PHP class - /bitrix/modules/main/classes/general/file_dialog.php
// JS  /bitrix/js/main/file_dialog.js, /bitrix/js/main/file_dialog_engine.js
// CSS  /bitrix/themes/.default/file_dialog.css

// define('PUBLIC_AJAX_MODE', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if(!$USER->CanDoOperation('fileman_view_file_structure'))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

IncludeModuleLangFile(__FILE__);

echo '<!--BX_FD_LOAD_OK-->';

if (!check_bitrix_sessid())
	die('<!--BX_FD_DUBLICATE_ACTION_REQUEST'.bitrix_sessid().'-->');

$action = isset($_GET['action']) ? $_GET['action'] : false;

if ($action == 'start')
{
	CAdminFileDialog::Start(array(
		'path' => isset($_GET['path']) ? $_GET['path'] : '',
		'site' => isset($_GET['site']) ? $_GET['site'] : false,
		'lang' => isset($_GET['lang']) ? $_GET['lang'] : 'en',
		'getFiles' => !isset($_GET['get_files']) || $_GET['get_files'],
		'bAddToMenu' => isset($_GET['add_to_menu']) && $_GET['add_to_menu'],
		'operation' => isset($_GET['operation']) ? $_GET['operation'] : '',
	));
}
elseif ($action == 'load')
{
	CAdminFileDialog::LoadItems(array(
		'path' => isset($_GET['path']) ? $_GET['path'] : '/',
		'site' => isset($_GET['site']) ? $_GET['site'] : false,
		'loadRecursively' => intval($_GET['rec']) > 0,
		'loadRoot' =>  intval($_GET['rec']) > 1,
		'getFiles' => !isset($_GET['get_files']) || $_GET['get_files'],
		'bAddToMenu' => isset($_GET['add_to_menu']) && $_GET['add_to_menu'],
		'operation' => isset($_GET['operation']) ? $_GET['operation'] : '',
	));
}
elseif ($action == 'new_dir')
{
	CAdminFileDialog::MakeNewDir(array(
		'path' => isset($_GET['path']) ? $_GET['path'] : '',
		'name' => isset($_GET['name']) ? $_GET['name'] : '',
		'site' => isset($_GET['site']) ? $_GET['site'] : false,
		'getFiles' => !isset($_GET['get_files']) || $_GET['get_files'],
		'bAddToMenu' => isset($_GET['add_to_menu']) && $_GET['add_to_menu'],
		'operation' => isset($_GET['operation']) ? $_GET['operation'] : '',
	));
}
elseif ($action == 'remove')
{
	CAdminFileDialog::Remove(array(
		'path' => isset($_GET['path']) ? $_GET['path'] : '',
		'site' => isset($_GET['site']) ? $_GET['site'] : false,
		'getFiles' => !isset($_GET['get_files']) || $_GET['get_files'],
		'bAddToMenu' => isset($_GET['add_to_menu']) && $_GET['add_to_menu'],
		'operation' => isset($_GET['operation']) ? $_GET['operation'] : '',
	));
}
elseif ($action == 'rename')
{
	CAdminFileDialog::Rename(array(
		'path' => isset($_GET['path']) ? $_GET['path'] : '',
		'old_name' => isset($_GET['old_name']) ? $_GET['old_name'] : '',
		'name' => isset($_GET['name']) ? $_GET['name'] : '',
		'site' => isset($_GET['site']) ? $_GET['site'] : false,
		'getFiles' => !isset($_GET['get_files']) || $_GET['get_files'],
		'bAddToMenu' => isset($_GET['add_to_menu']) && $_GET['add_to_menu'],
		'operation' => isset($_GET['operation']) ? $_GET['operation'] : '',
	));
}
elseif ($action == 'set_config')
{
	CAdminFileDialog::SetUserConfig(array(
		'path' => isset($_GET['path']) ? $_GET['path'] : '/',
		'site' => isset($_GET['site']) ? $_GET['site'] : false,
		'view' => isset($_GET['view']) ? $_GET['view'] : 'list',
		'sort' => isset($_GET['sort']) ? $_GET['sort'] : 'name',
		'sort_order' => isset($_GET['sort_order']) ? $_GET['sort_order'] : 'asc'
	));
}
elseif ($action == 'flash')
{
	CAdminFileDialog::PreviewFlash(array(
		'path' => isset($_GET['path']) ? $_GET['path'] : '/',
		'site' => isset($_GET['site']) ? $_GET['site'] : false,
		'width' => '86px',
		'height' => '86px',
	));
}
elseif ($action == 'uploader')
{
	if (isset($_REQUEST['cur_site']))
		$curSite = $_REQUEST['cur_site'];
	elseif (isset($_REQUEST['site']))
		$curSite = $_REQUEST['site'];
	else
		$curSite = false;

	CAdminFileDialog::ShowUploadForm(array(
		'lang' => isset($_REQUEST['lang']) ? $_REQUEST['lang'] : 'en',
		'site' => $curSite,
		'file' => isset($_FILES["load_file"]) ? $_FILES["load_file"] : false,
		'path' => isset($_POST["path"]) ? $_POST["path"] : '',
		'filename' => isset($_POST["filename"]) ? $_POST["filename"] : '',
		'upload_and_open' => isset($_POST["upload_and_open"]) ? $_POST["upload_and_open"] : 'N',
		'rewrite' => isset($_POST["rewrite"]) ? $_POST["rewrite"] : 'N'
	));
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>