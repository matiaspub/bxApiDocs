<?
// define("NO_KEEP_STATISTIC", true);
// define("NO_AGENT_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$res = false;

if($USER->IsAuthorized() && check_bitrix_sessid())
{
	$uid = $USER->GetID();
	$isAdmin = $USER->CanDoOperation('edit_other_settings');

	switch ($_REQUEST["action"])
	{
		case "save_filter":

			CUtil::decodeURIComponent($_POST);

			$arFields = array(
					"USER_ID" => $uid,
					"FILTER_ID" => $_POST['filter_id'],
					"NAME" => $_POST["name"],
					"LANGUAGE_ID" => LANG
				);

			$arFields["FIELDS"] = $_POST['fields'];

			if(isset($_POST['common']))
				$arFields["COMMON"] = $isAdmin ? $_POST['common'] : "N";

			if(isset($_POST['preset_id']))
				$arFields["PRESET_ID"] = $_POST['preset_id'];

			if(isset($_POST['sort']))
				$arFields["SORT"] = $_POST['sort'];

			if(isset($_POST['sort_field']))
				$arFields["SORT_FIELD"] = $_POST['sort_field'];

			$id = false;

			if(isset($_POST['id']))
			{
				$dbRes = CAdminFilter::GetList( array(), array("ID" => $_POST['id']), false);

				if($dbRes && $arFilter = $dbRes->Fetch())
					if(($arFilter["USER_ID"] = $uid || $isAdmin) && $arFilter["PRESET"]!="Y")
						if(CAdminFilter::Update($_POST['id'], $arFields ))
							$id = $_POST['id'];
			}
			else
				$id = CAdminFilter::Add( $arFields );

			if($id)
				$res = $id;

			break;

		case "del_filter":

			$dbRes = CAdminFilter::GetList(array(),array("ID" => $_REQUEST["id"]),false);

			$arFlt = $dbRes->GetNext();

			if(($arFlt["USER_ID"] == $uid || $isAdmin) && $arFlt["PRESET"]!="Y")
				$res = CAdminFilter::Delete($_REQUEST["id"]) ? true : false;

			break;

		case "open_tab_save":

			if(isset($_REQUEST["id"]) && isset($_REQUEST["filter_id"]))
				$_SESSION[CAdminFilter::SESS_PARAMS_NAME][$_REQUEST["filter_id"]]["activeTabId"] = $_REQUEST["id"];

			$res = true;

			break;

		case "filtered_tab_save":

			if(isset($_REQUEST["id"]) && isset($_REQUEST["filter_id"]))
			{
				if($_REQUEST["id"] != "false")
					$_SESSION[CAdminFilter::SESS_PARAMS_NAME][$_REQUEST["filter_id"]]["filteredId"] = $_REQUEST["id"];
				else
					unset($_SESSION[CAdminFilter::SESS_PARAMS_NAME][$_REQUEST["filter_id"]]["filteredId"]);
			}

			$res = true;

			break;
	}
}

echo $res;
?>