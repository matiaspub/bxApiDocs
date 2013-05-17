<?
// define("NO_KEEP_STATISTIC", true);
// define("NO_AGENT_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS", true);
// define("NO_AGENT_CHECK", true);
// define("DisableEventsCheck", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if($USER->IsAuthorized() && check_bitrix_sessid())
{
	CUtil::JSPostUnescape();

	$res = false;
	$uid = $USER->GetID();
	$now = $DB->GetNowFunction();
	global $adminMenu;

	switch ($_REQUEST["act"])
	{
		case  'add':

			$arFields = array(
							"MODIFIED_BY"	=>	$uid,
							"CREATED_BY"	=>	$uid,
							"USER_ID"	=>	$uid,
							"LANGUAGE_ID"	=> LANGUAGE_ID,
							"~TIMESTAMP_X"	=> $now,
							"COMMON"	=>	"N",
							"~DATE_CREATE"	=>	$now,
							);

			if(isset($_REQUEST["menu_id"]))
			{
				$arFields["MENU_ID"] = $_REQUEST["menu_id"];

				if (isset($_REQUEST['module_id']))
					$arFields["MODULE_ID"] = $_REQUEST["module_id"];

				$favMenu = new CBXFavAdmMenu;
				$menuItem = $favMenu->GetMenuItem($arFields["MENU_ID"], $adminMenu->aGlobalMenu);
				$arFields["NAME"] = $menuItem["text"] ? htmlspecialcharsback($menuItem["text"]) : $_REQUEST["name"];

				if(isset($_REQUEST["addurl"]) && !empty($_REQUEST["addurl"]))
					$arFields["URL"] = $_REQUEST["addurl"];
				elseif(isset($menuItem["url"]) && !empty($menuItem["url"]))
					$arFields["URL"] = htmlspecialcharsback($menuItem["url"]);
			}
			else
			{
				$arFields["NAME"] =	htmlspecialcharsback($_REQUEST["name"]);

				if(isset($_REQUEST["addurl"]) && !empty($_REQUEST["addurl"]))
					$arFields["URL"] =	$_REQUEST["addurl"];
			}

			$arFields["NAME"] = trim($arFields["NAME"]);

			$id = CFavorites::Add($arFields,true);

			if($id)
			{
				$favMenu = new CBXFavAdmMenu;
				$res = $favMenu->GenerateMenuHTML($id);
			}

			break;

		case 'delete':

			if(!isset($_REQUEST["id"]) || !$_REQUEST["id"])
				break;

			$dbFav = CFavorites::GetByID($_REQUEST["id"]);

			while ($arFav = $dbFav->GetNext())
				if($arFav["USER_ID"]==$uid)
					$res = CFavorites::Delete($_REQUEST["id"]);

			if($res)
			{
				$favMenu = new CBXFavAdmMenu;
				$res = $favMenu->GenerateMenuHTML();
			}


			break;

		case 'get_list':

			$dbFav = CFavorites::GetList();
			while ($arFav = $dbFav->GetNext())
				if($uid == $arFav["USER_ID"] || $arFav["COMMON"]=="Y")
					$res[] = array("NAME" => $arFav["NAME"], "URL" => $arFav["URL"], "LANGUAGE_ID" => $arFav["LANGUAGE_ID"]);

			if($res)
				$res = CUtil::PhpToJSObject($res);

			break;

		case 'get_menu_html':

			$favMenu = new CBXFavAdmMenu;
			$res = $favMenu->GenerateMenuHTML();

			break;

	}

	echo $res;
}

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_after.php");
?>
