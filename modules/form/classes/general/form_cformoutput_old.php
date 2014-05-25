<?
/*********************************************
	Устаревшие функции (для совместимости)
*********************************************/

class CFormOutput_old
{
	/**
	 * Form initializing and checking. If form's wrong, returns false
	 * Use ShowErrorMsg() to output error code
	 *
	 * @param array $arParams
	 * @return bool
	 */
public 	function Init($arParams, $admin = false)
	{
		global $APPLICATION, $USER;

		$this->bSimple = (COption::GetOptionString("form", "SIMPLE", "Y") == "Y") ? true : false;
		$this->comp2 = !empty($arParams["COMPONENT"]);
		$this->SHOW_INCLUDE_AREAS = $APPLICATION->GetShowIncludeAreas();

		if ($admin)
		{
			$FORM_RIGHT = $APPLICATION->GetGroupRight("form");
			if($FORM_RIGHT<="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

			$this->__admin = true;
		}

		$this->arParams = $arParams;

		$this->RESULT_ID = intval($arParams["RESULT_ID"]);
		if (intval($this->RESULT_ID)<=0) $this->RESULT_ID = intval($_REQUEST["RESULT_ID"]);

		// if there's result ID try to get form ID
		if (intval($this->RESULT_ID) > 0)
		{
			$DBRes = CFormResult::GetByID($this->RESULT_ID);

			if ($arrResult = $DBRes->Fetch())
			{
				$this->WEB_FORM_ID = intval($arrResult["FORM_ID"]);
			}
		}

		if (intval($this->WEB_FORM_ID) <= 0)
			$this->WEB_FORM_ID = intval($arParams["WEB_FORM_ID"]);

		// if there's no WEB_FORM_ID, try to get it from $_REQUEST;
		if (intval($this->WEB_FORM_ID) <= 0)
			$this->WEB_FORM_ID = intval($_REQUEST["WEB_FORM_ID"]);

		// check WEB_FORM_ID and get web form data
		$this->WEB_FORM_ID = CForm::GetDataByID($this->WEB_FORM_ID, $this->arForm, $this->arQuestions, $this->arAnswers, $this->arDropDown, $this->arMultiSelect, $this->__admin || $this->arParams["SHOW_ADDITIONAL"] == "Y" || $this->arParams["EDIT_ADDITIONAL"] == "Y" ? "ALL" : "N", $this->__admin ? 'Y' : 'N');

		$this->WEB_FORM_NAME = $this->arForm["SID"];

		// if wrong WEB_FORM_ID return error;
		if ($this->WEB_FORM_ID > 0)
		{
			//  insert chain item
			if (strlen($this->arParams["CHAIN_ITEM_TEXT"]) > 0)
			{
				$APPLICATION->AddChainItem($this->arParams["CHAIN_ITEM_TEXT"], $this->arParams["CHAIN_ITEM_LINK"]);
			}

			// check web form rights;
			$this->F_RIGHT = intval(CForm::GetPermission($this->WEB_FORM_ID));

			// in no form access - return error
			if ($this->isAccessForm())
			{
				if (!empty($_REQUEST["strFormNote"])) $this->strFormNote = $_REQUEST["strFormNote"];

				if (!$this->comp2 || $this->arParams["COMPONENT"]["componentName"] != "bitrix:form.result.list" || $this->isAccessFormResultList())
				{
					if ($this->RESULT_ID)
					{
						if ($this->isAccessFormResult($arrResult))
						{
							$this->arrRESULT_PERMISSION = CFormResult::GetPermissions($this->RESULT_ID, $v);

							// check result rights
							if (
								!$this->comp2 && !$this->isAccessFormResultEdit() // for components1 - check only editing right
								||
								$this->comp2 && // for components2 - check whether there's editing or viewing and check rights for it
									(
										$this->arParams["COMPONENT"]["componentName"] == "bitrix:form.result.edit" && !$this->isAccessFormResultEdit()
										||
										$this->arParams["COMPONENT"]["componentName"] == "bitrix:form.result.view" && !$this->isAccessFormResultView()
									)
							)
							{
								$this->__error_msg = "FORM_RESULT_ACCESS_DENIED";
							}
							else
							{
								if (!$arrResult)
								{
									$z = CFormResult::GetByID($this->RESULT_ID);
									$this->arResult = $z->Fetch();
								}
								else
								{
									$this->arResult = $arrResult;
								}

								if ($this->arResult)
								{
									if ($this->comp2 && $this->arParams["COMPONENT"]["componentName"] == "bitrix:form.result.view")
									{
										CForm::GetResultAnswerArray($this->WEB_FORM_ID, $this->arrResultColumns, $this->arrVALUES, $this->arrResultAnswersSID, array("RESULT_ID" => $this->RESULT_ID));
										$this->arrVALUES = $this->arrVALUES[$this->RESULT_ID];
									}
									else
									{
										$this->arrVALUES = CFormResult::GetDataByIDForHTML($this->RESULT_ID, $this->arParams["EDIT_ADDITIONAL"]);
									}
								}
								else
								{
									$this->__error_msg = "FORM_RECORD_NOT_FOUND";
								}
							}
						}
						else
						{
							$this->__error_msg = "FORM_ACCESS_DENIED";
						}

						$this->arForm["USE_CAPTCHA"] = "N";
					}
					else
					{
						// if form uses CAPCHA initialize it
						if ($this->arForm["USE_CAPTCHA"] == "Y") $this->CaptchaInitialize();
					}
				}
				else
				{
					$this->__error_msg = "FORM_ACCESS_DENIED";
				}
			}
			else
			{
				$this->__error_msg = "FORM_ACCESS_DENIED";
			} // endif ($F_RIGHT>=10);
		}
		else
		{
			$this->__error_msg = "FORM_NOT_FOUND";

		} // endif ($WEB_FORM_ID>0);

		return empty($this->__error_msg);
	}

/*****************************************/
	/*              Filter methods. Move to component     */
	/*****************************************/
public static 	function __checkFilter(&$str_error) // check of filter values
	{
		global $strError, $MESS, $HTTP_GET_VARS, $arrFORM_FILTER;
		global $find_date_create_1, $find_date_create_2;
		$str = "";

		CheckFilterDates($find_date_create_1, $find_date_create_2, $date1_wrong, $date2_wrong, $date2_less);
		if ($date1_wrong=="Y") $str.= GetMessage("FORM_WRONG_DATE_CREATE_FROM")."<br>";
		if ($date2_wrong=="Y") $str.= GetMessage("FORM_WRONG_DATE_CREATE_TO")."<br>";
		if ($date2_less=="Y") $str.= GetMessage("FORM_FROM_TILL_DATE_CREATE")."<br>";

		if (is_array($arrFORM_FILTER))
		{
			reset($arrFORM_FILTER);
			foreach ($arrFORM_FILTER as $arrF)
			{
				if (is_array($arrF))
				{
					foreach ($arrF as $arr)
					{
						$title = ($arr["TITLE_TYPE"]=="html") ? strip_tags(htmlspecialcharsback($arr["TITLE"])) : $arr["TITLE"];
						if ($arr["FILTER_TYPE"]=="date")
						{
							$date1 = $HTTP_GET_VARS["find_".$arr["FID"]."_1"];
							$date2 = $HTTP_GET_VARS["find_".$arr["FID"]."_2"];
							CheckFilterDates($date1, $date2, $date1_wrong, $date2_wrong, $date2_less);
							if ($date1_wrong=="Y")
								$str .= str_replace("#TITLE#", $title, GetMessage("FORM_WRONG_DATE1"))."<br>";
							if ($date2_wrong=="Y")
								$str .= str_replace("#TITLE#", $title, GetMessage("FORM_WRONG_DATE2"))."<br>";
							if ($date2_less=="Y")
								$str .= str_replace("#TITLE#", $title, GetMessage("FORM_DATE2_LESS"))."<br>";
						}
						if ($arr["FILTER_TYPE"]=="integer")
						{
							$int1 = intval($HTTP_GET_VARS["find_".$arr["FID"]."_1"]);
							$int2 = intval($HTTP_GET_VARS["find_".$arr["FID"]."_2"]);
							if ($int1>0 && $int2>0 && $int2<$int1)
							{
								$str .= str_replace("#TITLE#", $title, GetMessage("FORM_INT2_LESS"))."<br>";
							}
						}
					}
				}
			}
		}
		$strError .= $str;
		$str_error .= $str;
		if (strlen($str)>0) return false; else return true;
	}

public 	function __prepareFilter()
	{
		$FilterArr = Array(
			"find_id",
			"find_id_exact_match",
			"find_status",
			"find_status_id",
			"find_status_id_exact_match",
			"find_timestamp_1",
			"find_timestamp_2",
			"find_date_create_2",
			"find_date_create_1",
			"find_date_create_2",
			"find_registered",
			"find_user_auth",
			"find_user_id",
			"find_user_id_exact_match",
			"find_guest_id",
			"find_guest_id_exact_match",
			"find_session_id",
			"find_session_id_exact_match"
			);

		$z = CFormField::GetFilterList($this->WEB_FORM_ID, array("ACTIVE" => "Y"));
		while ($zr=$z->Fetch())
		{
			$FID = $this->WEB_FORM_NAME."_".$zr["SID"]."_".$zr["PARAMETER_NAME"]."_".$zr["FILTER_TYPE"];
			$zr["FID"] = $FID;
			$arrFORM_FILTER[$zr["SID"]][] = $zr;
			$fname = "find_".$FID;
			if ($zr["FILTER_TYPE"]=="date" || $zr["FILTER_TYPE"]=="integer")
			{
				$FilterArr[] = $fname."_1";
				$FilterArr[] = $fname."_2";
				$FilterArr[] = $fname."_0";
			}
			elseif ($zr["FILTER_TYPE"]=="text")
			{
				$FilterArr[] = $fname;
				$FilterArr[] = $fname."_exact_match";
			}
			else $FilterArr[] = $fname;
		}
		$sess_filter = "FORM_RESULT_LIST_".$this->WEB_FORM_NAME;
		if (strlen($_REQUEST["set_filter"])>0)
			InitFilterEx($FilterArr,$sess_filter,"set");
		else
			InitFilterEx($FilterArr,$sess_filter,"get");
		if (strlen($_REQUEST["del_filter"])>0)
		{
			DelFilterEx($FilterArr,$sess_filter);
		}
		else
		{
			InitBVar($find_id_exact_match);
			InitBVar($find_status_id_exact_match);
			InitBVar($find_user_id_exact_match);
			InitBVar($find_guest_id_exact_match);
			InitBVar($find_session_id_exact_match);
			$str_error = "";
			if ($this->__checkFilter($str_error))
			{
				$arFilter = Array(
					"ID"						=> $find_id,
					"ID_EXACT_MATCH"			=> $find_id_exact_match,
					"STATUS"					=> $find_status,
					"STATUS_ID"					=> $find_status_id,
					"STATUS_ID_EXACT_MATCH"		=> $find_status_id_exact_match,
					"TIMESTAMP_1"				=> $find_timestamp_1,
					"TIMESTAMP_2"				=> $find_timestamp_2,
					"DATE_CREATE_1"				=> $find_date_create_1,
					"DATE_CREATE_2"				=> $find_date_create_2,
					"REGISTERED"				=> $find_registered,
					"USER_AUTH"					=> $find_user_auth,
					"USER_ID"					=> $find_user_id,
					"USER_ID_EXACT_MATCH"		=> $find_user_id_exact_match,
					"GUEST_ID"					=> $find_guest_id,
					"GUEST_ID_EXACT_MATCH"		=> $find_guest_id_exact_match,
					"SESSION_ID"				=> $find_session_id,
					"SESSION_ID_EXACT_MATCH"	=> $find_session_id_exact_match
					);

				if (is_array($arrFORM_FILTER))
				{
					foreach ($arrFORM_FILTER as $arrF)
					{
						foreach ($arrF as $arr)
						{
							if ($arr["FILTER_TYPE"]=="date" || $arr["FILTER_TYPE"]=="integer")
							{
								$arFilter[$arr["FID"]."_1"] = ${"find_".$arr["FID"]."_1"};
								$arFilter[$arr["FID"]."_2"] = ${"find_".$arr["FID"]."_2"};
								$arFilter[$arr["FID"]."_0"] = ${"find_".$arr["FID"]."_0"};
							}
							elseif ($arr["FILTER_TYPE"]=="text")
							{
								$arFilter[$arr["FID"]] = ${"find_".$arr["FID"]};
								$exact_match = (${"find_".$arr["FID"]."_exact_match"}=="Y") ? "Y" : "N";
								$arFilter[$arr["FID"]."_exact_match"] = $exact_match;
							}
							else $arFilter[$arr["FID"]] = ${"find_".$arr["FID"]};
						}
					}
				}
			}
		}
		return $arFilter;
	}

	/**
	 * Public output method
	 * Use: $FORM->Out();
	 *
	 */
public 	function Out()
	{
		global $APPLICATION, $USER;

		$this->arParams['USE_EXTENDED_ERRORS'] = 'N';

		if (strlen($_REQUEST["web_form_submit"])>0 || strlen($_REQUEST["web_form_apply"])>0)
		{
			$this->arrVALUES = $_REQUEST;

			if ($this->RESULT_ID)
			{
				$this->__form_validate_errors = CForm::Check($this->WEB_FORM_ID, $this->arrVALUES, $this->RESULT_ID);
			}
			else
			{
				$this->__form_validate_errors = CForm::Check($this->WEB_FORM_ID, $this->arrVALUES);
			}

			if (!$this->isFormErrors())
			{
				if (check_bitrix_sessid())
				{
					$return = false;
					if ($this->RESULT_ID)
					{
						CFormResult::Update($this->RESULT_ID, $this->arrVALUES, $this->arParams["EDIT_ADDITIONAL"]);

						$this->strFormNote = GetMessage("FORM_DATA_SAVED");

						if (strlen($_REQUEST["web_form_submit"])>0 && !(defined("ADMIN_SECTION") && ADMIN_SECTION===true))
						{
							LocalRedirect($this->arParams["LIST_URL"].(strpos($this->arParams["LIST_URL"], "?") === false ? "?" : "&")."WEB_FORM_ID=".$this->WEB_FORM_ID."&strFormNote=".urlencode($this->strFormNote));
						}

						if (defined("ADMIN_SECTION") && ADMIN_SECTION === true)
						{
							if (strlen($_REQUEST["web_form_submit"])>0)
								LocalRedirect(BX_ROOT."/admin/form_result_list.php?lang=".LANG."&WEB_FORM_ID=".$this->WEB_FORM_ID."&strFormNote=".urlencode($this->strFormNote));
							elseif (strlen($_REQUEST["web_form_apply"])>0)
								LocalRedirect(BX_ROOT."/admin/form_result_edit.php?lang=".LANG."&WEB_FORM_ID=".$this->WEB_FORM_ID."&RESULT_ID=".$this->RESULT_ID."&strFormNote=".urlencode($this->strFormNote));
							die();
						}
						/*
						else
						{
							$DBRes = CFormResult::GetByID($this->RESULT_ID);
							$arrResult = $DBRes->Fetch();
						}
						*/
					}
					else
					{
						if($this->RESULT_ID = CFormResult::Add($this->WEB_FORM_ID, $this->arrVALUES))
						{
							$this->strFormNote = GetMessage("FORM_DATA_SAVED1").$this->RESULT_ID.GetMessage("FORM_DATA_SAVED2");
							CFormResult::SetEvent($this->RESULT_ID);
							CFormResult::Mail($this->RESULT_ID);

							if ($this->F_RIGHT >= 15)
							{
								if (strlen($_REQUEST["web_form_submit"])>0 && strlen($this->arParams["LIST_URL"])>0)
								{
									LocalRedirect($this->arParams["LIST_URL"].(strpos($this->arParams["LIST_URL"], "?") === false ? "?" : "&")."lang=".LANGUAGE_ID."&WEB_FORM_ID=".$this->WEB_FORM_ID."&RESULT_ID=".$this->RESULT_ID."&strFormNote=".urlencode($this->strFormNote));
								}
								elseif (strlen($_REQUEST["web_form_apply"])>0 && strlen($this->arParams["EDIT_URL"])>0)
								{
									LocalRedirect($this->arParams["EDIT_URL"].(strpos($this->arParams["EDIT_URL"], "?") === false ? "?" : "&")."RESULT_ID=".$this->RESULT_ID."&strFormNote=".urlencode($this->strFormNote));
								}

								$return = true;
							}
							else
							{
								LocalRedirect($APPLICATION->GetCurPage()."?lang=".LANGUAGE_ID."&WEB_FORM_ID=".$this->WEB_FORM_ID."&strFormNote=".urlencode($this->strFormNote));
							}
						}
						else
						{
							$this->__form_validate_errors = $GLOBALS["strError"];
						}
					}
				}
			}
		}

		$strReturn = $this->IncludeFormCustomTemplate();
		if (strlen($strReturn) <= 0)
		{
			ob_start();
			$GLOBALS["FORM"] =& $this; // create interface for template
			$APPLICATION->IncludeFile("form/".(empty($this->RESULT_ID) || $return ? "result_new" : "result_edit")."/form.php", $this->arParams, array("SHOW_BORDER" => false));
			$strReturn = ob_get_contents();
			ob_end_clean();
		}

		$back_url = $_SERVER['REQUEST_URI'];

		$editor = "/bitrix/admin/fileman_file_edit.php?full_src=Y&site=".SITE_ID."&";
		$rel_path = "form/".(empty($this->RESULT_ID) ? "result_new" : "result_edit")."/form.php";
		$path = BX_PRESONAL_ROOT."/templates/".SITE_TEMPLATE_ID."/".$rel_path;
		$href = "javascript:window.location='".$editor."path=".urlencode($path)."&lang=".LANGUAGE_ID."&back_url=".urlencode($back_url)."'";

		if(!file_exists($_SERVER["DOCUMENT_ROOT"].$path))
		{
			$path = BX_PRESONAL_ROOT."/templates/.default/".$rel_path;
			$href = "javascript:window.location='".$editor."path=".urlencode($path)."&lang=".LANGUAGE_ID."&back_url=".urlencode($back_url)."'";
			if(!file_exists($_SERVER["DOCUMENT_ROOT"].$path))
			{
				$path = "/bitrix/modules/form/install/templates/".$rel_path;
				$href = "javascript:if(confirm('".GetMessage("MAIN_INC_BLOCK_COMMON")."')) window.location='".$editor."path=".urlencode(BX_PERSONAL_ROOT.'/templates/'.SITE_TEMPLATE_ID.'/'.$rel_path)."&template=".urlencode($path)."&lang=".LANGUAGE_ID."&back_url=".urlencode($back_url)."'";
			}
		}

		if ($USER->IsAdmin())
		{
			$APPLICATION->AddPanelButton(array(
				"SORT" => 100,
				"MAIN_SORT" => 1000,
				"HREF" => "/bitrix/admin/form_edit.php?lang=".LANGUAGE_ID."&amp;ID=".$this->WEB_FORM_ID. "&amp;tabControl_active_tab=edit5&back_url=".urlencode($back_url),
				"SRC"  => "/bitrix/images/form/edit_templ.gif",
				"ALT" => GetMessage("FORM_PUBLIC_ICON_EDIT_TPL")
			));

			$APPLICATION->AddPanelButton(array(
				"SORT" => 200,
				"MAIN_SORT" => 1000,
				"HREF" => $href,
				"SRC"  => "/bitrix/images/form/edit_default_templ.gif",
				"ALT" => GetMessage("FORM_PUBLIC_ICON_EDIT_DEFAULT_TPL"),
			));

			if($APPLICATION->GetShowIncludeAreas())
			{
				if ($this->arForm["USE_DEFAULT_TEMPLATE"] == "N")
				{
					$arIcons = Array();
					$arIcons[] =
							Array(
								"URL" => "/bitrix/admin/form_edit.php?lang=".LANGUAGE_ID."&amp;ID=".$this->WEB_FORM_ID. "&amp;tabControl_active_tab=edit5&back_url=".urlencode($back_url),
								"ICON" => 'form-edit-tpl',
								"ALT" => GetMessage("FORM_PUBLIC_ICON_EDIT_TPL")
								);
					$strReturn = $APPLICATION->IncludeString($strReturn, $arIcons);
				}
				else
				{
					$arIcons = Array();
					$arIcons[] =
							Array(
								"URL" => $href,
								"SRC" => "/bitrix/images/form/edit_default_templ.gif",
								"ICON" => 'form-edit-default-tpl',
								"ALT" => GetMessage("FORM_PUBLIC_ICON_EDIT_DEFAULT_TPL"),
								);
					$strReturn = $APPLICATION->IncludeString($strReturn, $arIcons);
				}
			}

		}

		echo $strReturn;
	}

	fpublic unction getData(&$arResult)
	{
		global $APPLICATION, $USER;

		//$arResult = $this->__prepareDataForTpl();
		$arResult["WEB_FORM_ID"] = $this->WEB_FORM_ID;
		$arResult["WEB_FORM_NAME"] = $this->WEB_FORM_NAME;
		if ($this->RESULT_ID > 0) $arResult["RESULT_ID"] = $this->RESULT_ID;
		$arResult["F_RIGHT"] = $this->F_RIGHT;

		if (strlen($_REQUEST["web_form_submit"])>0 || strlen($_REQUEST["web_form_apply"])>0)
		{
			$this->arrVALUES = $_REQUEST;

			if ($this->RESULT_ID)
			{
				$this->__form_validate_errors = CForm::Check($this->WEB_FORM_ID, $this->arrVALUES, $this->RESULT_ID);
			}
			else
			{
				$this->__form_validate_errors = CForm::Check($this->WEB_FORM_ID, $this->arrVALUES);
			}

			if (!$this->isFormErrors())
			{
				if (check_bitrix_sessid())
				{
					$return = false;

					if ($this->RESULT_ID)
					{
						CFormResult::Update($this->RESULT_ID, $this->arrVALUES, $this->arParams["EDIT_ADDITIONAL"]);

						$this->strFormNote = GetMessage("FORM_DATA_SAVED");

						if (strlen($_REQUEST["web_form_submit"])>0 && !(defined("ADMIN_SECTION") && ADMIN_SECTION===true))
						{
							if ($this->arParams["SEF_MODE"] == "Y")
								LocalRedirect($this->arParams["LIST_URL"]."?strFormNote=".urlencode($this->strFormNote));
							else
								LocalRedirect($this->arParams["LIST_URL"].(strpos($this->arParams["LIST_URL"], "?") === false ? "?" : "&")."WEB_FORM_ID=".$this->WEB_FORM_ID."&strFormNote=".urlencode($this->strFormNote));

							die();
						}

						if (strlen($_REQUEST["web_form_apply"])>0 && !(defined("ADMIN_SECTION") && ADMIN_SECTION===true) && $this->arParams["SEF_MODE"] == "Y")
						{
							// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
							LocalRedirect($this->arParams["EDIT_URL"].(strpos($this->arParams["EDIT_URL"], "?") === false ? "?" : "&")."strFormNote=".urlencode($this->strFormNote));
							die();
						}

						if (defined("ADMIN_SECTION") && ADMIN_SECTION === true)
						{
							if (strlen($_REQUEST["web_form_submit"])>0)
							{
								LocalRedirect(BX_ROOT."/admin/form_result_list.php?lang=".LANG."&WEB_FORM_ID=".$this->WEB_FORM_ID."&strFormNote=".urlencode($this->strFormNote));
							}
							elseif (strlen($_REQUEST["web_form_apply"])>0)
							{
								LocalRedirect(BX_ROOT."/admin/form_result_edit.php?lang=".LANG."&WEB_FORM_ID=".$this->WEB_FORM_ID."&RESULT_ID=".$this->RESULT_ID."&strFormNote=".urlencode($this->strFormNote));
							}
							die();
						}

					}
					else
					{
						if($this->RESULT_ID = CFormResult::Add($this->WEB_FORM_ID, $this->arrVALUES))
						{
							$this->strFormNote = GetMessage("FORM_DATA_SAVED1").$this->RESULT_ID.GetMessage("FORM_DATA_SAVED2");

							CFormResult::SetEvent($this->RESULT_ID);
							CFormResult::Mail($this->RESULT_ID);

							if ($this->F_RIGHT >= 15)
							{
								if (strlen($_REQUEST["web_form_submit"])>0 && strlen($this->arParams["LIST_URL"])>0)
								{
									if ($this->arParams["SEF_MODE"] == "Y")
										LocalRedirect($this->arParams["LIST_URL"]."?strFormNote=".urlencode($this->strFormNote));
									else
										LocalRedirect($this->arParams["LIST_URL"].(strpos($this->arParams["LIST_URL"], "?") === false ? "?" : "&")."WEB_FORM_ID=".$this->WEB_FORM_ID."&RESULT_ID=".$this->RESULT_ID."&strFormNote=".urlencode($this->strFormNote));
									die();
								}
								elseif (strlen($_REQUEST["web_form_apply"])>0 && strlen($this->arParams["EDIT_URL"])>0)
								{
									if ($this->arParams["SEF_MODE"] == "Y")
										LocalRedirect(str_replace("#RESULT_ID#", $this->RESULT_ID. $this->arParams["EDIT_URL"])."?strFormNote=".urlencode($this->strFormNote));
									else
										LocalRedirect($this->arParams["EDIT_URL"].(strpos($this->arParams["EDIT_URL"], "?") === false ? "?" : "&")."RESULT_ID=".$this->RESULT_ID."&strFormNote=".urlencode($this->strFormNote));
									die();
								}

								$arResult["return"] = true;
							}
							else
							{
								LocalRedirect($APPLICATION->GetCurPage()."?WEB_FORM_ID=".$this->WEB_FORM_ID."&strFormNote=".urlencode($this->strFormNote));
								die();
							}
						}
						else
						{
							$this->__form_validate_errors = $GLOBALS["strError"];
						}
					}
				}
			}
		}

		return $arResult;
	}

public 	function getListData()
	{
		$arFilter = $this->__prepareFilter();
		$arResult = $this->__prepareDataForTpl();
		$arResult["arFilter"] = $arFilter;
		return $arResult;
	}

public 	function __prepareDataForTpl()
	{
		global $APPLICATION;

		$arResult = array();

		if ($this->arResult)
		{
			if (intval($this->arResult["USER_ID"])>0)
			{
				$rsUser = CUser::GetByID($this->arResult["USER_ID"]);
				$arUser = $rsUser->Fetch();
				$this->arResult["LOGIN"] = htmlspecialcharsbx($arUser["LOGIN"]);
				$this->arResult["EMAIL"] = $arUser["USER_EMAIL"];
				$this->arResult["FIRST_NAME"] = htmlspecialcharsbx($arUser["NAME"]);
				$this->arResult["LAST_NAME"] = htmlspecialcharsbx($arUser["LAST_NAME"]);
			}
		}

		$arResult["FORM"] =& $this;
		return $arResult;
	}

	/**
	 * Initialize Captcha
	 *
	 */
	fpublic unction CaptchaInitialize()
	{
		$this->CAPTCHACode = $GLOBALS["APPLICATION"]->CaptchaGetCode();
		//echo $this->CAPTCHACode;
	}

	public function ShowAnswer($FIELD_SID)
	{
		global $USER;

		$out = "";

		$arQuestion = $this->arQuestions[$FIELD_SID];
		$arrResultAnswer = $this->arrVALUES[$arQuestion["ID"]];

		if (is_array($arrResultAnswer))
		{
			reset($arrResultAnswer);
			$count = count($arrResultAnswer);
			$i=0;
			foreach ($arrResultAnswer as $key => $arrA)
			{
				$i++;

				if (strlen(trim($arrA["USER_TEXT"]))>0)
				{
					if (intval($arrA["USER_FILE_ID"])>0)
					{
						if ($arrA["USER_FILE_IS_IMAGE"]=="Y" && $USER->IsAdmin())
							$out .= htmlspecialcharsbx($arrA["USER_TEXT"])."<br />";
					}
					else $out .= TxtToHTML($arrA["USER_TEXT"],true,50)."<br />";
				}

				if (strlen(trim($arrA["ANSWER_TEXT"]))>0)
				{
					$answer = "[<span class='form-anstext'>".TxtToHTML($arrA["ANSWER_TEXT"],true,50)."</span>]";
					if (strlen(trim($arrA["ANSWER_VALUE"]))>0) $answer .= "&nbsp;"; else $answer .= "<br />";
					$out .= $answer;
				}

				if ($this->arParams["SHOW_ANSWER_VALUE"]=="Y")
				{
					if (strlen(trim($arrA["ANSWER_VALUE"]))>0)
						$out .= "(<span class='form-ansvalue'>".TxtToHTML($arrA["ANSWER_VALUE"],true,50)."</span>)<br />";
				}

				if (intval($arrA["USER_FILE_ID"])>0)
				{
					if ($arrA["USER_FILE_IS_IMAGE"]=="Y")
					{
						$out .= CFile::ShowImage($arrA["USER_FILE_ID"], 0, 0, "border=0", "", true);
					}
					else
					{
						$file_link = "/bitrix/tools/form_show_file.php?rid=".$this->RESULT_ID."&hash=".$arrA["USER_FILE_HASH"]."&lang=".LANGUAGE_ID;

						$out .= "<a title=\"".GetMessage("FORM_VIEW_FILE")."\" target=\"_blank\" href=\"".$file_link."\">".htmlspecialcharsbx($arrA["USER_FILE_NAME"])."</a><br />(";

						$out .= CFile::FormatSize($arrA["USER_FILE_SIZE"]);

						$out .= ")<br />[&nbsp;<a title=\"".str_replace("#FILE_NAME#", $arrA["USER_FILE_NAME"], GetMessage("FORM_DOWNLOAD_FILE"))."\" href=\"".$file_link."&action=download\">".GetMessage("FORM_DOWNLOAD")."</a>&nbsp;]";
					} //endif;
				} //endif;
			} //endforeach;
		} //endif;

		return $out;
	}
}
?>