<?
/*********************************************
	Устаревшие функции (для совместимости)
*********************************************/

class CForm_old
{
public static 	function GetFileValue($RESULT_ID, $ANSWER_ID)
	{
		global $DB;
		$err_mess = (CAllForm::err_mess())."<br>Function: GetFileValue<br>Line: ";
		$strSql = "SELECT USER_FILE_ID FROM b_form_result_answer WHERE RESULT_ID='".intval($RESULT_ID)."' and ANSWER_ID='".intval($ANSWER_ID)."'";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		return $zr["USER_FILE_ID"];
	}

	/*
	выводит HTML формы с учетом прав посетителя

		WEB_FORM_VARNAME - идентификатор формы
		VARS - массив значений для полей ввода
		SHOW_TEMPLATE - имя шаблона формы
		PREVIEW - если "Y" тогда кнопка "Сохранить" в шаблоне недоступна
	*/
public static 	function Show($WEB_FORM_VARNAME, $arrVALUES=false, $SHOW_TEMPLATE=false, $PREVIEW="N")
	{
		global $DB, $MESS, $APPLICATION, $USER, $_REQUEST, $HTTP_POST_VARS, $HTTP_GET_VARS, $arrFIELDS;
		$err_mess = (CAllForm::err_mess())."<br>Function: Show<br>Line: ";
		if ($arrVALUES===false) $arrVALUES = $_REQUEST;

		$z = CForm::GetBySID($WEB_FORM_VARNAME);
		$zr = $z->Fetch();
		$WEB_FORM_ID = $FORM_ID = intval($zr["ID"]);
		$WEB_FORM_ID = CForm::GetDataByID($WEB_FORM_ID, $arForm, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect);
		if (intval($WEB_FORM_ID)>0)
		{
			$F_RIGHT = CForm::GetPermission($WEB_FORM_ID);
			if (intval($F_RIGHT)>=10)
			{
				if (strlen(trim($SHOW_TEMPLATE))>0) $template = $SHOW_TEMPLATE;
				else
				{
					if (strlen(trim($arForm["SHOW_TEMPLATE"]))<=0) $template = "default.php";
					else $template = $arForm["SHOW_TEMPLATE"];
				}
				$path = COption::GetOptionString("form","SHOW_TEMPLATE_PATH");
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

	public static function IsOldVersion()
	{
		return 'N';

		/*
		$res = "N";
		$arr = CForm::GetTemplateList("EDIT_RESULT");
		if (is_array($arr) && count($arr["reference"])>0) $res = "Y";
		else
		{
			$arr = CForm::GetTemplateList("SHOW_RESULT");
			if (is_array($arr) && count($arr["reference"])>0) $res = "Y";
			else
			{
				$arr = CForm::GetTemplateList("SHOW");
				if (is_array($arr) && count($arr["reference"])>0) $res = "Y";
				else
				{
					$arr = CForm::GetTemplateList("PRINT_RESULT");
					if (is_array($arr) && count($arr["reference"])>0) $res = "Y";
				}
			}
		}
		return $res;
		*/
	}

	// возвращает hidden поля для формирования массива
	// вопросов формы значения которых не нужно менять в момент редактирования результата
	public static function GetClosedFields($WEB_FORM_ID, $arrFields)
	{
		$err_mess = (CAllForm::err_mess())."<br>Function: GetClosedFields<br>Line: ";
		global $DB;
		$str = "";
		if (is_array($arrFields) && count($arrFields)>0)
		{
			$q = CFormField::GetList($WEB_FORM_ID, "N", $by, $order, array("VARNAME" => implode("|",$arrFields)), $is_filtered);
			while ($qr=$q->Fetch())
			{
				$str .= "<input type=\"hidden\" name=\"ARR_CLS[]\" value=\"".htmlspecialcharsbx($qr["ID"])."\">\n";
			}
		}
		return $str;
	}

	public static function GetByVarname($VARNAME)
	{ return CForm::GetByID($VARNAME, "Y"); }

public static 	function GetResultList($WEB_FORM_ID, &$by, &$order, $arFilter=Array(), &$is_filtered, $CHECK_RIGHTS="Y", $records_limit=false)
	{ return CFormResult::GetList($WEB_FORM_ID, $by, $order, $arFilter, $is_filtered, $CHECK_RIGHTS, $records_limit); }

public static 	function GetResultByID($RESULT_ID)
	{ return CFormResult::GetByID($RESULT_ID); }

	function GetResultFields($RESULT_ID, $arrFIELD_VARNAME, &$arrRES, &$arrANSWER)
	{ return CFormResult::GetDataByID($RESULT_ID, $arrFIELD_VARNAME, $arrRES, $arrANSWER); }

	function GetResultValuesFromDB($RESULT_ID, $GET_ADDITIONAL="N")
	{ return CFormResult::GetDataByIDForHTML($RESULT_ID, $GET_ADDITIONAL); }

public static 	function Add($WEB_FORM_ID, $arrVALUES=false, $CHECK_RIGHTS="Y", $USER_ID=false)
	{ return CFormResult::Add($WEB_FORM_ID, $arrVALUES, $CHECK_RIGHTS, $USER_ID); }

static 	function Update($RESULT_ID, $arrVALUES=false, $UPDATE_ADDITIONAL="N", $CHECK_RIGHTS="Y")
	{ return CFormResult::Update($RESULT_ID, $arrVALUES, $UPDATE_ADDITIONAL, $CHECK_RIGHTS); }

	function SetResultField($RESULT_ID, $FIELD_VARNAME, $VALUE)
	{ return CFormResult::SetField($RESULT_ID, $FIELD_VARNAME, $VALUE); }

public static 	function GetResultPermission($RESULT_ID, &$CURRENT_STATUS_ID)
	{ return CFormResult::GetPermissions($RESULT_ID, $CURRENT_STATUS_ID); }

public static 	function AddResultAnswer($arFields)
	{ return CFormResult::AddAnswer($arFields); }

	public static function UpdateResultField($arFields, $RESULT_ID, $FIELD_ID)
	{ return CFormResult::UpdateField($arFields, $RESULT_ID, $FIELD_ID); }

	function DeleteResult($ID, $CHECK_RIGHTS="Y")
	{ return CFormResult::Delete($ID, $CHECK_RIGHTS); }

public static 	function ResetResult($ID, $WEB_FORM_ID, $DELETE_IMAGES=true, $DELETE_ADDITIONAL="N", $arrException=array())
	{ return CFormResult::Reset($ID, $DELETE_IMAGES, $DELETE_ADDITIONAL, $arrException); }

public static 	function ShowResult($RESULT_ID, $TEMPLATE="", $TEMPLATE_TYPE="show", $SHOW_ADDITIONAL="N", $SHOW_ANSWER_VALUE="Y", $SHOW_STATUS="N")
	{ return CFormResult::Show($RESULT_ID, $TEMPLATE, $TEMPLATE_TYPE, $SHOW_ADDITIONAL, $SHOW_ANSWER_VALUE, $SHOW_STATUS); }

public static 	function EditResult($RESULT_ID, $arrVALUES, $TEMPLATE="", $EDIT_ADDITIONAL="N", $EDIT_STATUS="N")
	{ return CFormResult::Edit($RESULT_ID, $arrVALUES, $TEMPLATE, $EDIT_ADDITIONAL, $EDIT_STATUS); }

	function SetResultStatus($WEB_FORM_ID, $RESULT_ID, $NEW_STATUS_ID, $CHECK_RIGHTS="Y")
	{ return CFormResult::SetStatus($RESULT_ID, $NEW_STATUS_ID, $CHECK_RIGHTS); }

public static 	function Mail($RESULT_ID, $TEMPLATE_ID="")
	{ return CFormResult::Mail($RESULT_ID, $TEMPLATE_ID); }

public static 	function GetResultsCount($WEB_FORM_ID)
	{ return CFormResult::GetCount($WEB_FORM_ID); }

public static 	function PrepareResultFilter($WEB_FORM_ID, $arFilter)
	{ return CFormResult::PrepareFilter($WEB_FORM_ID, $arFilter); }

public static 	function SetEvent($RESULT_ID, $IN_EVENT1=false, $IN_EVENT2=false, $IN_EVENT3=false, $money="", $currency="", $goto="", $chargeback="N")
	{ return CFormResult::SetEvent($RESULT_ID, $IN_EVENT1, $IN_EVENT2, $IN_EVENT3, $money, $currency, $goto, $chargeback); }

	function GetFieldList($WEB_FORM_ID, $additional, &$by, &$order, $arFilter=Array(), &$is_filtered)
	{ return CFormField::GetList($WEB_FORM_ID, $additional, $by, $order, $arFilter, $is_filtered); }

public static 	function GetFieldByID($ID)
	{ return CFormField::GetByID($ID); }

public static 	function GetFieldByVarname($SID)
	{ return CFormField::GetBySID($SID); }

public static 	function DeleteField($WEB_FORM_ID, $ID, $CHECK_RIGHTS="Y")
	{ return CFormField::Delete($ID, $CHECK_RIGHTS); }

	function ResetField($WEB_FORM_ID, $ID, $CHECK_RIGHTS="Y")
	{ return CFormField::Reset($ID, $CHECK_RIGHTS); }

	function GetFilterTypeList(&$arrUSER, &$arrANSWER_TEXT, &$arrANSWER_VALUE, &$arrFIELD)
	{ return CFormField::GetFilterTypeList($arrUSER, $arrANSWER_TEXT, $arrANSWER_VALUE, $arrFIELD); }

public static 	function GetAdditionaFieldTypeList()
	{ return CFormField::GetTypeList(); }

public static 	function GetAnswerByID($ID)
	{ return CFormAnswer::GetByID($ID); }

public static 	function DeleteAnswer($ID)
	{ return CFormAnswer::Delete($ID); }

public static 	function GetAnswerList($FIELD_ID, &$by, &$order, $arFilter=Array(), &$is_filtered)
	{ return CFormAnswer::GetList($FIELD_ID, $by, $order, $arFilter, $is_filtered); }

	function GetAnswerTypeList()
	{ return CFormAnswer::GetTypeList(); }

	public static function GetFilterList($WEB_FORM_ID, $arFilter=Array())
	{ return CFormField::GetFilterList($WEB_FORM_ID, $arFilter); }

	function GetStatusPermission($STATUS_ID)
	{ return CFormStatus::GetPermissions($STATUS_ID); }

public static 	function GetNextStatusSort($WEB_FORM_ID)
	{ return CFormStatus::GetNextSort($WEB_FORM_ID); }

	function GetDefaultStatus($WEB_FORM_ID)
	{ return CFormStatus::GetDefault($WEB_FORM_ID); }

public static 	function GetStatusList($WEB_FORM_ID, &$by, &$order, $arFilter=array(), &$is_filtered)
	{ return CFormStatus::GetList($WEB_FORM_ID, $by, $order, $arFilter, $is_filtered); }

public static 	function GetStatusByID($ID)
	{ return CFormStatus::GetByID($ID); }

public static 	function GetStatusDropdown($WEB_FORM_ID, $PERMISSION="MOVE", $OWNER_ID=0)
	{ return CFormStatus::GetDropdown($WEB_FORM_ID, $PERMISSION, $OWNER_ID); }
}

?>