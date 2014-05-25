<?
// define("NO_KEEP_STATISTIC", true);
// define("NO_AGENT_STATISTIC", true);
// define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$hkInstance = CHotKeys::GetInstance();
$uid=$USER->GetID();

if($USER->IsAuthorized() && check_bitrix_sessid())
{
	$res = false;

	switch ($_REQUEST["hkaction"])
	{
		case  'add':

				$arFields = array(
								"KEYS_STRING"=>rawurldecode($_REQUEST["KEYS_STRING"]),
								"CODE_ID"=>$_REQUEST["CODE_ID"],
								"USER_ID"=>$uid
								);

				$res = $hkInstance->Add($arFields);
				break;

		case  'update':

				if($hkInstance->GetUIDbyHID($_REQUEST["ID"])==$uid)
					$res = $hkInstance->Update($_REQUEST["ID"],array( "KEYS_STRING"=>rawurldecode($_REQUEST["KEYS_STRING"]) ));

				break;

		case  'delete':

				if($hkInstance->GetUIDbyHID($_REQUEST["ID"])==$uid)
					$res = $hkInstance->Delete($_REQUEST["ID"]);

				break;

		case  'delete_all':

				$res=0;
				$listRes=$hkInstance->GetList(array(),array( "USER_ID" => $uid ));
				while($arHK=$listRes->Fetch())
					$res += $hkInstance->Delete($arHK["ID"]);

				break;

		case  'set_default':

				$sdRes = $hkInstance->SetDefault($uid);
				if($sdRes)
				{
					$res="";
					$listRes=$hkInstance->GetList(array(),array( "USER_ID" => $uid ));
					while($arHK=$listRes->Fetch())
						$res.=$arHK["CODE_ID"]."::".$arHK["ID"]."::".$arHK["KEYS_STRING"].";;";
				}

				break;

		case  'export':

				$tmpExportFile = $hkInstance->Export();

				if($tmpExportFile)
					if(file_exists($tmpExportFile))
						if(filesize($tmpExportFile)>0)
						{
							header('Content-type: application/force-download');
							header('Content-Disposition: attachment; filename="'.CHotKeys::$ExpImpFileName.'"');
							$res = file_get_contents($tmpExportFile);
							break;
						}

				$res='
				<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.LANGUAGE_ID.'" lang="'.LANGUAGE_ID.'">
				<body>
				<script>alert("'.GetMessage("HK_EXP_FALSE").'");
				window.close();
				</script>
				</body></html>';
				break;

		case  'import':

				if(!$_FILES['bx_hk_filename']['name'] || !$_FILES['bx_hk_filename']['size'])
				{
					$res='<script type="text/javascript">window.parent.BXHotKeys.OnImportResponse(0);</script>';
					break;
				}

				$numImported = 0;

				$tmpDir = CTempFile::GetDirectoryName();
				CheckDirPath($tmpDir);

				$name = $tmpDir.basename($_FILES['bx_hk_filename']['name']);

				if(move_uploaded_file($_FILES['bx_hk_filename']['tmp_name'], $tmpDir.CHotKeys::$ExpImpFileName))
					$numImported = $hkInstance->Import($tmpDir.CHotKeys::$ExpImpFileName,$uid);

				$res='<script type="text/javascript">window.parent.BXHotKeys.OnImportResponse("'.$numImported.'");</script>';

				break;
	}

	echo $res;
}

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_after.php");
?>
