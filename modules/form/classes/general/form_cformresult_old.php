<?
/*********************************************
	Устаревшие функции (для совместимости)
*********************************************/

class CFormResult_old
{
public static 	function GetDataByIDForWeb($RESULT_ID, $GET_ADDITIONAL="N")
	{ return CFormResult::GetDataByIDForHTML($RESULT_ID, $GET_ADDITIONAL); }

public static 	function GetMaxPermissions()
	{ return CFormStatus::GetMaxPermissions(); }

	/*
	выводит HTML формы редактирования результата с учетом прав пользователя

		RESULT_ID - ID результата
		arrVALUES - массив значений для полей ввода
		TEMPLATE - шаблон для редактирования результата
	*/
public static 	function Edit($RESULT_ID, $arrVALUES, $TEMPLATE="", $EDIT_ADDITIONAL="N", $EDIT_STATUS="N")
	{
		global $DB, $MESS, $APPLICATION, $USER, $HTTP_POST_VARS, $HTTP_GET_VARS, $arrFIELDS, $arrRESULT_PERMISSION;
		$err_mess = (CAllFormResult::err_mess())."<br>Function: Edit<br>Line: ";
		$z = CFormResult::GetByID($RESULT_ID);
		if ($zr=$z->Fetch())
		{
			$arrResult = $zr;
			$additional = ($EDIT_ADDITIONAL=="Y") ? "ALL" : "N";
			$WEB_FORM_ID = $FORM_ID = CForm::GetDataByID($arrResult["FORM_ID"], $arForm, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect, $additional);
			CForm::GetResultAnswerArray($WEB_FORM_ID, $arrResultColumns, $arrResultAnswers, $arrResultAnswersVarname, array("RESULT_ID" => $RESULT_ID));
			$arrResultAnswers = $arrResultAnswers[$RESULT_ID];
			// проверим общие права
			$F_RIGHT = intval(CForm::GetPermission($WEB_FORM_ID));
			if ($F_RIGHT>=20 || ($F_RIGHT>=15 && $arrResult["USER_ID"]==$USER->GetID()))
			{
				// проверим права в зависимости от статуса результата
				$arrRESULT_PERMISSION = CFormResult::GetPermissions($RESULT_ID, $v);
				if (in_array("EDIT",$arrRESULT_PERMISSION)) // имеем право на просмотр
				{
					if (strlen(trim($TEMPLATE))>0) $template = $TEMPLATE;
					else
					{
						if (strlen($arrResult["EDIT_RESULT_TEMPLATE"])<=0) $template = "default.php";
						else $template = $arrResult["EDIT_RESULT_TEMPLATE"];
					}
					require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/form/include.php");
					$path = COption::GetOptionString("form","EDIT_RESULT_TEMPLATE_PATH");
					IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/form/include.php");
					include(GetLangFileName($_SERVER["DOCUMENT_ROOT"].$path."lang/", "/".$template));
					if ($APPLICATION->GetShowIncludeAreas())
					{
						$arIcons = Array();
						if (CModule::IncludeModule("fileman"))
						{
							$arIcons[] =
									Array(						
										"URL" => "/bitrix/admin/fileman_file_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&full_src=Y&path=". urlencode($path.$template),
										"SRC" => "/bitrix/images/form/panel/edit_template.gif",
										"ALT" => GetMessage("FORM_PUBLIC_ICON_TEMPLATE")
									);
							$arrUrl = parse_url($_SERVER["REQUEST_URI"]);
							$arIcons[] =
									Array(						
										"URL" => "/bitrix/admin/fileman_file_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&full_src=Y&path=". urlencode($arrUrl["path"]),
										"SRC" => "/bitrix/images/form/panel/edit_file.gif",
										"ALT" => GetMessage("FORM_PUBLIC_ICON_HANDLER")
									);
						}
						$arIcons[] =
								Array(						
									"URL" => "/bitrix/admin/form_edit.php?lang=".LANGUAGE_ID."&ID=".$WEB_FORM_ID,
									"SRC" => "/bitrix/images/form/panel/edit_form.gif",
									"ALT" => GetMessage("FORM_PUBLIC_ICON_SETTINGS")
								);
						echo $APPLICATION->IncludeStringBefore($arIcons);
					}
					include($_SERVER["DOCUMENT_ROOT"].$path.$template);
					if ($APPLICATION->GetShowIncludeAreas())
					{
						echo $APPLICATION->IncludeStringAfter();
					}
				}
			}
		}
	}

	/*
	выводит HTML показывающий результат с учетом прав посетителя

		RESULT_ID - ID результата
		TEMPLATE - имя шаблона для показа результат
		TEMPLATE_TYPE - 
			если "show" тогда берется шаблон для показа,
			если "print" тогда берется шаблон для печати
	*/
public static 	function Show($RESULT_ID, $TEMPLATE="", $TEMPLATE_TYPE="show", $SHOW_ADDITIONAL="N", $SHOW_ANSWER_VALUE="Y", $SHOW_STATUS="N")
	{
		global $DB, $MESS, $APPLICATION, $USER, $HTTP_POST_VARS, $HTTP_GET_VARS, $arrRESULT_PERMISSION, $arrFIELDS;
		$err_mess = (CAllFormResult::err_mess())."<br>Function: Show<br>Line: ";
		$z = CFormResult::GetByID($RESULT_ID);
		if ($zr=$z->Fetch())
		{
			$arrResult = $zr;
			InitBVar($SHOW_ADDITIONAL);
			$additional = ($SHOW_ADDITIONAL=="Y") ? "ALL" : "N";
			$WEB_FORM_ID = $FORM_ID = CForm::GetDataByID($arrResult["FORM_ID"], $arForm, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect, $additional);
			CForm::GetResultAnswerArray($WEB_FORM_ID, $arrResultColumns, $arrResultAnswers, $arrResultAnswersVarname, array("RESULT_ID" => $RESULT_ID));
			$arrResultAnswers = $arrResultAnswers[$RESULT_ID];
			// проверим общие права на результат
			$F_RIGHT = CForm::GetPermission($WEB_FORM_ID);
			if (intval($F_RIGHT)>=20 || ($F_RIGHT>=15 && $zr["USER_ID"]==$USER->GetID()))
			{
				// проверим права в зависимости от статуса результата
				$arrRESULT_PERMISSION = CFormResult::GetPermissions($RESULT_ID, $v);
				if (in_array("VIEW",$arrRESULT_PERMISSION)) // имеем право на просмотр
				{
					if (strlen(trim($TEMPLATE))>0) $template = $TEMPLATE;
					else
					{
						if ($TEMPLATE_TYPE=="show")
						{
							if (strlen($arrResult["SHOW_RESULT_TEMPLATE"])<=0) $template = "default.php";
							else $template = $arrResult["SHOW_RESULT_TEMPLATE"];
						}
						elseif ($TEMPLATE_TYPE=="print")
						{
							if (strlen($arrResult["PRINT_RESULT_TEMPLATE"])<=0) $template = "default.php";
							else $template = $arrResult["PRINT_RESULT_TEMPLATE"];
						}
					}
					require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/form/include.php");

					if ($TEMPLATE_TYPE=="show")
					{
						$path = COption::GetOptionString("form","SHOW_RESULT_TEMPLATE_PATH");
					}
					elseif ($TEMPLATE_TYPE=="print") 
					{
						$path = COption::GetOptionString("form","PRINT_RESULT_TEMPLATE_PATH");
					}
					IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/form/include.php");
					include(GetLangFileName($_SERVER["DOCUMENT_ROOT"].$path."lang/", "/".$template));
					if ($APPLICATION->GetShowIncludeAreas())
					{
						$arIcons = Array();
						if (CModule::IncludeModule("fileman"))
						{
							$arIcons[] =
									Array(						
										"URL" => "/bitrix/admin/fileman_file_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&full_src=Y&path=". urlencode($path.$template),
										"SRC" => "/bitrix/images/form/panel/edit_template.gif",
										"ALT" => GetMessage("FORM_PUBLIC_ICON_TEMPLATE")
									);
							$arrUrl = parse_url($_SERVER["REQUEST_URI"]);
							$arIcons[] =
									Array(						
										"URL" => "/bitrix/admin/fileman_file_edit.php?lang=".LANGUAGE_ID."&site=".SITE_ID."&full_src=Y&path=". urlencode($arrUrl["path"]),
										"SRC" => "/bitrix/images/form/panel/edit_file.gif",
										"ALT" => GetMessage("FORM_PUBLIC_ICON_HANDLER")
									);
						}
						$arIcons[] =
								Array(						
									"URL" => "/bitrix/admin/form_edit.php?lang=".LANGUAGE_ID."&ID=".$WEB_FORM_ID,
									"SRC" => "/bitrix/images/form/panel/edit_form.gif",
									"ALT" => GetMessage("FORM_PUBLIC_ICON_SETTINGS")
								);
						echo $APPLICATION->IncludeStringBefore($arIcons);
					}
					include($_SERVER["DOCUMENT_ROOT"].$path.$template);
					if ($APPLICATION->GetShowIncludeAreas())
					{
						echo $APPLICATION->IncludeStringAfter();
					}
				}
			}
		}
	}
}
?>