<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

IncludeModuleLangFile(__FILE__);

if (!function_exists('__format_user4search'))
{
	function __format_user4search($userID=null, $nameTemplate="")
	{
		global $USER;

		if ($userID == null)
			$userID = $USER->GetID();

		if (empty($nameTemplate))
			$nameTemplate = CSite::GetNameFormat(false);

		$rUser = CUser::GetByID($userID);
		if ($rUser && $arUser =$rUser->Fetch())
		{
			$userName = CUser::FormatName($nameTemplate.' [#ID#]', $arUser);
			if (!(strlen($arUser['NAME'])>0 || strlen($arUser['LAST_NAME'])>0))
			{
				$userName .= ' ['.$arUser['ID'].']';
			}
		}
		else
		{
			$userName = '';
		}
		return $userName;
	}
}

function CrmClearMenuCache()
{
	global $CACHE_MANAGER;
	$CACHE_MANAGER->CleanDir('menu');
	$CACHE_MANAGER->ClearByTag('crm_change_role');
}

function CrmCheckPath($path_name, $param_path, $def_path)
{
	if (strlen($param_path) <= 0 && strlen(COption::GetOptionString('crm', strtolower($path_name))) > 0)
		$path_value = htmlspecialcharsbx(COption::GetOptionString('crm', strtolower($path_name)));
	else if (strlen($param_path) <= 0)
		$path_value = htmlspecialcharsbx($def_path);
	else
		$path_value = $param_path;

	return $path_value;
}

function CrmCompareFieldsList($arFieldData, $fieldValue, $sEmptyString = null)
{
	$sEmptyString = !is_null($sEmptyString) ? $sEmptyString : GetMessage('CRM_FIELD_COMPARE_EMPTY');
	return isset($arFieldData[$fieldValue]) ? $arFieldData[$fieldValue] : (!empty($fieldValue) ? GetMessage('CRM_FIELD_COMPARE_DELETE') : $sEmptyString);
}

//function CrmCompareFieldCallback($callback, $fieldValue, $emptyString = null)
//{
//	$emptyString = !is_null($emptyString) ? $emptyString : GetMessage('CRM_FIELD_COMPARE_EMPTY');
//	$fieldDisplayInfo = call_user_func($callback, $fieldValue);
//	return isset($fieldDisplayInfo[0]) ? $fieldDisplayInfo : (!empty($fieldValue) ? GetMessage('CRM_FIELD_COMPARE_DELETE') : $emptyString);
//}
?>