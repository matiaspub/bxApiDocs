<?php
IncludeModuleLangFile(__FILE__);

class CAllWorkflow
{
	public static function err_mess()
	{
		return "<br>Module: workflow<br>Class: CAllWorkflow<br>File: ".__FILE__;
	}

	public static function OnPanelCreate()
	{
		global $APPLICATION, $USER;
		$cur_page_param = $APPLICATION->GetCurPageParam();
		$cur_page = $APPLICATION->GetCurPage(true);
		$cur_dir = $APPLICATION->GetCurDir();

		// New page
		$flow_link_new = CWorkFlow::GetEditLink(array(SITE_ID, rtrim(GetDirPath($cur_page), "/")."/untitled.php"), $status_id, $status_title, "standart.php", LANGUAGE_ID, $cur_page_param);
		$create_permission = $flow_link_new <> '' && $USER->CanDoFileOperation('fm_edit_in_workflow', array(SITE_ID, $cur_dir));
		// Document history
		$flow_link_hist = "/bitrix/admin/workflow_history_list.php?lang=".LANGUAGE_ID. "&find_filename=".urlencode($cur_page)."&find_filename_exact_match=Y&set_filter=Y";
		$history_permission = $USER->CanDoFileOperation('fm_edit_in_workflow', array(SITE_ID, $cur_page));
		// Current page
		$flow_link_edit = CWorkFlow::GetEditLink(array(SITE_ID, $cur_page), $status_id, $status_title, "", LANGUAGE_ID, $cur_page_param);
		$edit_permission = $flow_link_edit <> '' && $history_permission;

		//Big button
		if($edit_permission)
		{
			$public_edit = $APPLICATION->GetPopupLink(array(
				"URL" => $flow_link_edit."&bxpublic=Y&from_module=workflow",
				"PARAMS" => Array(
					"min_width"=>700,
					"min_height" => 400,
					'height' => 700,
					'width' => 400,
				)
			));

			$APPLICATION->AddPanelButton(array(
				"HREF" => "javascript:".$public_edit,
				"TYPE" => "BIG",
				"ID" => "edit",
				"ICON" => "bx-panel-edit-page-icon",
				"ALT" => GetMessage("top_panel_edit_title"),
				"TEXT" => GetMessage("top_panel_edit_new"),
				"MAIN_SORT" => "200",
				"SORT" => 10,
				"MENU" => array(),
				"HK_ID" => "top_panel_edit_new",
				"RESORT_MENU" => true,
				"HINT" => array(
					"TITLE" => GetMessage("top_panel_edit_new_tooltip_title"),
					"TEXT" => GetMessage("top_panel_edit_new_tooltip")
				),
			));
		}

		// New page
		if($create_permission)
		{
			$APPLICATION->AddPanelButtonMenu("create", array("SEPARATOR"=>true, "SORT"=>49));
			$APPLICATION->AddPanelButtonMenu("create", array(
				"SRC" => "/bitrix/images/workflow/new_page.gif",
				"TEXT" => GetMessage("FLOW_PANEL_CREATE_WITH_WF"),
				"TITLE" => GetMessage("FLOW_PANEL_CREATE_ALT"),
				"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape($flow_link_new)."')",
				"HK_ID" => "FLOW_PANEL_CREATE_WITH_WF",
				"SORT" => 50
			));
		}

		if($edit_permission || $history_permission)
			$APPLICATION->AddPanelButtonMenu("edit", array("SEPARATOR"=>true, "SORT"=>79));

		// Current page
		if($edit_permission)
		{
			$APPLICATION->AddPanelButtonMenu("edit", array(
				"SRC" => "/bitrix/images/workflow/edit_flow_public.gif",
				"TEXT" => GetMessage("FLOW_PANEL_EDIT_WITH_WF"),
				"TITLE" => (intval($status_id) > 0? GetMessage("FLOW_CURRENT_STATUS")." [$status_id] $status_title": GetMessage("FLOW_PANEL_EDIT_ALT")),
				"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape($flow_link_edit)."')",
				"HK_ID" => "FLOW_PANEL_EDIT_WITH_WF",
				"SORT" => 80
			));
		}

		// Document history
		if($history_permission)
		{
			$flow_link_hist = "/bitrix/admin/workflow_history_list.php?lang=".LANGUAGE_ID. "&find_filename=".urlencode($cur_page)."&find_filename_exact_match=Y&set_filter=Y";
			$APPLICATION->AddPanelButtonMenu("edit", array(
				"SRC" => "/bitrix/images/workflow/history.gif",
				"TEXT" => GetMessage("FLOW_PANEL_HISTORY"),
				"TITLE" => GetMessage("FLOW_PANEL_HISTORY_ALT"),
				"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape($flow_link_hist)."')",
				"HK_ID" => "FLOW_PANEL_HISTORY",
				"SORT" => 81
			));
		}
	}

	public static function OnChangeFile($path, $site)
	{
		global $BX_WORKFLOW_PUBLISHED_PATH, $BX_WORKFLOW_PUBLISHED_SITE;
		if($BX_WORKFLOW_PUBLISHED_PATH == $path && $BX_WORKFLOW_PUBLISHED_SITE == $site)
			return ;

		global $DB, $USER, $APPLICATION;
		$HISTORY_SIMPLE_EDITING = COption::GetOptionString("workflow","HISTORY_SIMPLE_EDITING","N");
		if ($HISTORY_SIMPLE_EDITING=="Y")
		{
			$HISTORY_COPIES = intval(COption::GetOptionString("workflow","HISTORY_COPIES","10"));
			CWorkflow::CleanUpHistoryCopies_SE($path,$HISTORY_COPIES-1);
			if ($HISTORY_COPIES>0)
			{
				$DOC_ROOT = CSite::GetSiteDocRoot($site);
				$filesrc = $APPLICATION->GetFileContent($DOC_ROOT.$path);
				$arContent = ParseFileContent($filesrc);
				$TITLE = $arContent["TITLE"];
				$BODY = $arContent["CONTENT"];
				$arFields = array(
					"DOCUMENT_ID" => 0,
					"MODIFIED_BY" => $USER? $USER->GetID(): 1,
					"TITLE" => $TITLE,
					"FILENAME" => $path,
					"SITE_ID" => $site,
					"BODY" => $BODY,
					"BODY_TYPE" => "html",
					"STATUS_ID" => 1,
					"~TIMESTAMP_X" => $DB->CurrentTimeFunction(),
				);
				$DB->Add("b_workflow_log", $arFields, array("BODY"), "workflow");
			}
		}
	}

	public static function SetHistory($DOCUMENT_ID)
	{
		global $DB;

		$LOG_ID = false;
		$DOCUMENT_ID = intval($DOCUMENT_ID);
		$HISTORY_COPIES = intval(COption::GetOptionString("workflow","HISTORY_COPIES","10"));
		$z = CWorkflow::GetByID($DOCUMENT_ID);
		if ($zr=$z->Fetch())
		{
			CWorkflow::CleanUpHistoryCopies($DOCUMENT_ID,$HISTORY_COPIES-1);
			if ($HISTORY_COPIES>0)
			{
				$arFields = array(
					"DOCUMENT_ID"	=> $DOCUMENT_ID,
					"MODIFIED_BY"	=> $zr["MODIFIED_BY"],
					"TITLE"			=> $zr["TITLE"],
					"FILENAME"		=> $zr["FILENAME"],
					"SITE_ID"		=> $zr["SITE_ID"],
					"BODY"			=> $zr["BODY"],
					"BODY_TYPE"		=> $zr["BODY_TYPE"],
					"STATUS_ID"		=> $zr["STATUS_ID"],
					"COMMENTS"		=> $zr["COMMENTS"],
					"~TIMESTAMP_X" => $DB->CurrentTimeFunction(),
				);
				$LOG_ID = $DB->Add("b_workflow_log", $arFields, array("BODY"), "workflow");
			}
		}
		return $LOG_ID;
	}

	// Deletes old copies from document's history
	public static function CleanUpHistoryCopies($DOCUMENT_ID=false, $HISTORY_COPIES=false)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: CleanUpHistoryCopies<br>Line: ";
		global $DB;

		if($HISTORY_COPIES===false)
		{
			$HISTORY_COPIES = intval(COption::GetOptionString("workflow","HISTORY_COPIES","10"));
		}

		$DOCUMENT_ID = intval($DOCUMENT_ID);
		if ($DOCUMENT_ID > 0)
		{
			$strSqlSearch = " and ID = $DOCUMENT_ID ";
		}
		else
		{
			$strSqlSearch = "";
		}

		$strSql = "SELECT ID FROM b_workflow_document WHERE 1=1 ".$strSqlSearch;
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($zr=$z->Fetch())
		{
			$DID = $zr["ID"];
			$strSql = "
				SELECT
					ID
				FROM
					b_workflow_log
				WHERE
					DOCUMENT_ID = $DID
				ORDER BY
					ID desc
				";
			$t = $DB->Query($strSql, false, $err_mess.__LINE__);
			$i = 0;
			$str_id = "0";
			while ($tr = $t->Fetch())
			{
				$i++;
				if ($i > $HISTORY_COPIES)
				{
					$str_id .= ",".$tr["ID"];
				}
			}
			$strSql = "DELETE FROM b_workflow_log WHERE ID in ($str_id)";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
	}

	// Deletes old copies from document's history (simple edit - SE)
	public static function CleanUpHistoryCopies_SE($FILENAME, $HISTORY_COPIES=false)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: CleanUpHistoryCopies_SE<br>Line: ";
		global $DB;
		if ($HISTORY_COPIES===false)
		{
			$HISTORY_COPIES = intval(COption::GetOptionString("workflow","HISTORY_COPIES","10"));
		}
		$strSql = "
			SELECT
				ID
			FROM
				b_workflow_log
			WHERE
				FILENAME = '".$DB->ForSql($FILENAME,255)."'
			and DOCUMENT_ID = 0
			ORDER BY
				ID desc
			";
		$t = $DB->Query($strSql, false, $err_mess.__LINE__);
		$i = 0;
		$str_id = "0";
		while ($tr = $t->Fetch())
		{
			$i++;
			if ($i > $HISTORY_COPIES)
			{
				$str_id .= ",".$tr["ID"];
			}
		}
		$strSql = "DELETE FROM b_workflow_log WHERE ID in ($str_id)";
		$DB->Query($strSql, false, $err_mess.__LINE__);
	}

	// saves changes history and send e-mails on status change
	public static function SetMove($DOCUMENT_ID, $STATUS_ID, $OLD_STATUS_ID, $LOG_ID)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: SetMove<br>Line: ";
		global $DB, $USER, $APPLICATION;

		$DOCUMENT_ID = intval($DOCUMENT_ID);
		$STATUS_ID = intval($STATUS_ID);
		$OLD_STATUS_ID = intval($OLD_STATUS_ID);
		$LOG_ID = intval($LOG_ID);

		$arFields = array(
			"TIMESTAMP_X" => $DB->GetNowFunction(),
			"DOCUMENT_ID" => $DOCUMENT_ID,
			"OLD_STATUS_ID" => $OLD_STATUS_ID,
			"STATUS_ID" => $STATUS_ID,
			"LOG_ID" => $LOG_ID,
			"USER_ID" => intval($USER->GetID()),
		);
		$DB->Insert("b_workflow_move",$arFields, $err_mess.__LINE__);

		if($STATUS_ID != $OLD_STATUS_ID)
		{
			CTimeZone::Disable();
			$d = CWorkflow::GetByID($DOCUMENT_ID);
			CTimeZone::Enable();

			if ($dr = $d->Fetch())
			{
				$STATUS_ID = $dr["STATUS_ID"];

				// document creator
				$ENTERED_BY_USER_ID = $dr["ENTERED_BY"];

				// gather email of the workflow admins
				$WORKFLOW_ADMIN_GROUP_ID = COption::GetOptionInt("workflow", "WORKFLOW_ADMIN_GROUP_ID", 0);
				$strSql = "
					SELECT
						U.ID,
						U.EMAIL
					FROM
						b_user U,
						b_user_group UG
					WHERE
						UG.GROUP_ID = $WORKFLOW_ADMIN_GROUP_ID
						and U.ID = UG.USER_ID
						and U.ACTIVE = 'Y'
				";
				$a = $DB->Query($strSql, false, $err_mess.__LINE__);
				$arAdmin = Array();
				while ($ar=$a->Fetch())
				{
					$arAdmin[$ar["ID"]] = $ar["EMAIL"];
				}

				// gather email for BCC
				$arBCC = array();

				// gather all who changed doc in its current status
				$strSql = "
					SELECT
						USER_ID
					FROM
						b_workflow_move
					WHERE
						DOCUMENT_ID = $DOCUMENT_ID
						and OLD_STATUS_ID = $STATUS_ID
				";
				$z = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($zr = $z->Fetch())
					$arBCC[$zr["EMAIL"]] = $zr["EMAIL"];

				// gather all editors
				// in case status have notifier flag
				$strSql = "
					SELECT DISTINCT
						UG.USER_ID
						,U.EMAIL
					FROM
						b_workflow_status S,
						b_workflow_status2group SG,
						b_user U,
						b_user_group UG
					WHERE
						S.ID = $STATUS_ID
						and S.NOTIFY = 'Y'
						and SG.STATUS_ID = S.ID
						and SG.PERMISSION_TYPE = '2'
						and UG.GROUP_ID = SG.GROUP_ID
						and U.ID = UG.USER_ID
						and U.ACTIVE = 'Y'
				";
				$z = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($zr = $z->Fetch())
				{
					if(!array_key_exists($zr["EMAIL"], $arBCC))
					{
						$grp = array();
						$rs = $USER->GetUserGroupList($zr["USER_ID"]);
						while($ar = $rs->Fetch())
							$grp[] = $ar["GROUP_ID"];

						$arTasks = $APPLICATION->GetFileAccessPermission($dr["FILENAME"], $grp, true);
						foreach($arTasks as $task_id)
						{
							$arOps = CTask::GetOperations($task_id, true);
							if(in_array("fm_edit_in_workflow", $arOps))
							{
								$arBCC[$zr["EMAIL"]] = $zr["EMAIL"];
								break;
							}
						}
					}
				}

				unset($arBCC[$dr["EUSER_EMAIL"]]);

				if(array_key_exists($dr["ENTERED_BY"], $arAdmin))
					$dr["EUSER_NAME"] .= " (Admin)";

				// it is not new doc
				if($OLD_STATUS_ID > 0)
				{
					if(array_key_exists($dr["MODIFIED_BY"],$arAdmin))
						$dr["MUSER_NAME"] .= " (Admin)";
					$q = CWorkflowStatus::GetByID($OLD_STATUS_ID);
					$qr = $q->Fetch();
					// send change notification
					$arEventFields = array(
						"ID"			=> $dr["ID"],
						"ADMIN_EMAIL"		=> implode(",", $arAdmin),
						"BCC"			=> implode(",", $arBCC),
						"PREV_STATUS_ID"	=> $OLD_STATUS_ID,
						"PREV_STATUS_TITLE"	=> $qr["TITLE"],
						"STATUS_ID"		=> $dr["STATUS_ID"],
						"STATUS_TITLE"		=> $dr["STATUS_TITLE"],
						"DATE_ENTER"		=> $dr["DATE_ENTER"],
						"ENTERED_BY_ID"		=> $dr["ENTERED_BY"],
						"ENTERED_BY_NAME"	=> $dr["EUSER_NAME"],
						"ENTERED_BY_EMAIL"	=> $dr["EUSER_EMAIL"],
						"DATE_MODIFY"		=> $dr["DATE_MODIFY"],
						"MODIFIED_BY_ID"	=> $dr["MODIFIED_BY"],
						"MODIFIED_BY_NAME"	=> $dr["MUSER_NAME"],
						"FILENAME"		=> $dr["FILENAME"],
						"SITE_ID"		=> $dr["SITE_ID"],
						"TITLE"			=> $dr["TITLE"],
						"BODY_HTML"		=> ($dr["BODY_TYPE"]=="html"?$dr["BODY"]:TxtToHtml($dr["BODY"])),
						"BODY_TEXT"		=> ($dr["BODY_TYPE"]=="text"?$dr["BODY"]:HtmlToTxt($dr["BODY"])),
						"BODY"			=> $dr["BODY"],
						"BODY_TYPE"		=> $dr["BODY_TYPE"],
						"COMMENTS"		=> $dr["COMMENTS"],
					);
					CEvent::Send("WF_STATUS_CHANGE", $dr["SITE_ID"], $arEventFields);
				}
				else // otherwise
				{
					// it was new one
					$arEventFields = array(
						"ID"			=> $dr["ID"],
						"ADMIN_EMAIL"		=> implode(",", $arAdmin),
						"BCC"			=> implode(",", $arBCC),
						"STATUS_ID"		=> $dr["STATUS_ID"],
						"STATUS_TITLE"		=> $dr["STATUS_TITLE"],
						"DATE_ENTER"		=> $dr["DATE_ENTER"],
						"ENTERED_BY_ID"		=> $dr["ENTERED_BY"],
						"ENTERED_BY_NAME"	=> $dr["EUSER_NAME"],
						"ENTERED_BY_EMAIL"	=> $dr["EUSER_EMAIL"],
						"FILENAME"		=> $dr["FILENAME"],
						"SITE_ID"		=> $dr["SITE_ID"],
						"TITLE"			=> $dr["TITLE"],
						"BODY_HTML"		=> ($dr["BODY_TYPE"]=="html"?$dr["BODY"]:TxtToHtml($dr["BODY"])),
						"BODY_TEXT"		=> ($dr["BODY_TYPE"]=="text"?$dr["BODY"]:HtmlToTxt($dr["BODY"])),
						"BODY"			=> $dr["BODY"],
						"BODY_TYPE"		=> $dr["BODY_TYPE"],
						"COMMENTS"		=> $dr["COMMENTS"],
					);
					CEvent::Send("WF_NEW_DOCUMENT", $dr["SITE_ID"], $arEventFields);
				}
			}

		}
	}

	public static function Delete($DOCUMENT_ID)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: Delete<br>Line: ";
		global $DB;
		CWorkflow::CleanUpFiles($DOCUMENT_ID);
		CWorkflow::CleanUpPreview($DOCUMENT_ID);
		$DB->Query("DELETE FROM b_workflow_move WHERE DOCUMENT_ID=".intval($DOCUMENT_ID), false, $err_mess.__LINE__);
		$DB->Query("DELETE FROM b_workflow_document WHERE ID=".intval($DOCUMENT_ID), false, $err_mess.__LINE__);
	}

	public static function IsAdmin()
	{
		global $USER;

		if ($USER->IsAdmin())
		{
			return true;
		}
		else
		{
			$WORKFLOW_ADMIN_GROUP_ID = COption::GetOptionString("workflow", "WORKFLOW_ADMIN_GROUP_ID");
			if (in_array($WORKFLOW_ADMIN_GROUP_ID, $USER->GetUserGroupArray()))
			{
				return true;
			}
		}
		return false;
	}

	// check edit rights for the document
	// depending on it's status and lock
	public static function IsAllowEdit($DOCUMENT_ID, &$locked_by, &$date_lock, $CHECK_RIGHTS="Y")
	{

		$DOCUMENT_ID = intval($DOCUMENT_ID);
		$LOCK_STATUS = CWorkflow::GetLockStatus($DOCUMENT_ID, $locked_by, $date_lock);
		if ($LOCK_STATUS=="red")
		{
			return false;
		}
		elseif ($LOCK_STATUS=="yellow")
		{
			return true;
		}
		elseif ($LOCK_STATUS=="green")
		{
			if ($CHECK_RIGHTS=="Y")
			{
				return CWorkflow::IsHaveEditRights($DOCUMENT_ID);
			}
			else
			{
				return true;
			}
		}
		return false;
	}

	public static function GetStatus($DOCUMENT_ID)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: GetStatus<br>Line: ";
		global $DB;
		$DOCUMENT_ID = intval($DOCUMENT_ID);
		$strSql = "
			SELECT
				S.*
			FROM
				b_workflow_document D,
				b_workflow_status S
			WHERE
				D.ID='$DOCUMENT_ID'
			and	S.ID = D.STATUS_ID
			";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $z;
	}

	// check edit rights for the document
	// check is based only on status no lock
	public static function IsHaveEditRights($DOCUMENT_ID)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: IsHaveEditRights<br>Line: ";
		global $DB, $USER;

		if(CWorkflow::IsAdmin())
			return true;

		$arGroups = $USER->GetUserGroupArray();
		if(!is_array($arGroups) || count($arGroups) <= 0)
			$arGroups = array(2);

		$strSql = "
			SELECT
				G.ID
			FROM
				b_workflow_document D,
				b_workflow_status2group G
			WHERE
				D.ID = ".intval($DOCUMENT_ID)."
				and G.STATUS_ID = D.STATUS_ID
				and G.PERMISSION_TYPE >= '2'
				and G.GROUP_ID in (".implode(",",$arGroups).")
		";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);

		if($zr = $z->Fetch())
			return true;
		else
			return false;
	}

	public static function UnLock($DOCUMENT_ID)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: UnLock<br>Line: ";
		global $DB, $USER;
		$DOCUMENT_ID = intval($DOCUMENT_ID);
		$z = CWorkflow::GetByID($DOCUMENT_ID);
		$zr = $z->Fetch();
		if (CWorkflow::IsAdmin() || $zr["LOCKED_BY"]==$USER->GetID())
		{
			$arFields = array(
				"DATE_LOCK"	=> "null",
				"LOCKED_BY"	=> "null"
				);
			$rows = $DB->Update("b_workflow_document",$arFields,"WHERE ID='".$DOCUMENT_ID."'",$err_mess.__LINE__);
			return intval($rows);
		}
		return false;
	}

	public static function Lock($DOCUMENT_ID)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: Lock<br>Line: ";
		global $DB, $USER;
		$DOCUMENT_ID = intval($DOCUMENT_ID);
		$z = CWorkflow::GetByID($DOCUMENT_ID);
		if ($zr=$z->Fetch())
		{
			if ($zr["STATUS_ID"]!=1)
			{
				$arFields = array(
					"DATE_LOCK"		=> $DB->GetNowFunction(),
					"LOCKED_BY"		=> $USER->GetID()
					);
				$DB->Update("b_workflow_document",$arFields,"WHERE ID='".$DOCUMENT_ID."'",$err_mess.__LINE__);
			}
		}
	}

	// return edit link depending on rights and status
	public static function GetEditLink($FILENAME, &$status_id, &$status_title, $template="", $lang=LANGUAGE_ID, $return_url="")
	{
		global $USER;

		$link = '';
		CMain::InitPathVars($SITE_ID, $FILENAME);

		if($USER->CanDoFileOperation('fm_edit_in_workflow', array($SITE_ID, $FILENAME)))
		{
			//Check if user have access at least to one status
			if(!CWorkflow::IsAdmin())
			{
				$arGroups = $USER->GetUserGroupArray();
				if(!is_array($arGroups))
					$arGroups = array(2);
				$arFilter = array(
					"GROUP_ID" => $arGroups,
					"PERMISSION_TYPE_1" => 1,
				);
				$rsStatuses = CWorkflowStatus::GetList($by = "s_c_sort", $strOrder, $arFilter, $is_filtered, array("ID"));
				if(!$rsStatuses->Fetch())
					return "";
			}

			$link = "/bitrix/admin/workflow_edit.php?lang=".$lang."&site=".$SITE_ID."&fname=".$FILENAME;
			if (strlen($template)>0) $link .= "&template=".urlencode($template);
			if (strlen($return_url)>0) $link .= "&return_url=".urlencode($return_url);
			$z = CWorkflow::GetByFilename($FILENAME, $SITE_ID);
			if ($zr = $z->Fetch())
			{
				$status_id = $zr["STATUS_ID"];
				$status_title = $zr["STATUS_TITLE"];
				if ($status_id!=1)
				{
					$DOCUMENT_ID = $zr["ID"];
					if (CWorkflow::IsHaveEditRights($DOCUMENT_ID)) $link .= "&ID=".$DOCUMENT_ID;
					else return "";
				}
			}
		}
		return $link;
	}

	public static function DeleteHistory($ID)
	{
		global $DB;
		$DB->Query("
			DELETE FROM b_workflow_log
			WHERE ID = ".intval($ID)."
		", false, CAllWorkflow::err_mess()."<br>Function: DeleteHistory<br>Line: ".__LINE__);
	}

	public static function CleanUp()
	{
		CWorkflow::CleanUpPublished();
		CWorkflow::CleanUpHistory();
		CWorkflow::CleanUpFiles();
		CWorkflow::CleanUpPreview();
		return "CWorkflow::CleanUp();";
	}

	public static function CleanUpFiles($DOCUMENT_ID=false, $FILE_ID=false)
	{
		$err_mess = (CWorkflow::err_mess())."<br>Function: CleanUpFiles<br>Line: ";
		global $DB;
		if ($DOCUMENT_ID===false)
		{
			$strSql = "SELECT TEMP_FILENAME FROM b_workflow_file WHERE DOCUMENT_ID is null";
		}
		else
		{
			$DOCUMENT_ID = intval($DOCUMENT_ID);
			$strSql = "SELECT TEMP_FILENAME FROM b_workflow_file WHERE DOCUMENT_ID = ".$DOCUMENT_ID;
		}
		if ($FILE_ID!==false)
		{
			$FILE_ID = intval($FILE_ID);
			$strSql .= " and ID = ".$FILE_ID;
		}
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		while($zr=$z->Fetch())
			CWorkflow::DeleteFile($zr["TEMP_FILENAME"]);
	}

	public static function CleanUpPreview($DOCUMENT_ID=false)
	{
		$err_mess = (CWorkflow::err_mess())."<br>Function:  CleanUpPreview<br>Line: ";
		global $DB;
		if ($DOCUMENT_ID===false)
		{
			$strSql = "
				SELECT
					P.FILENAME, D.SITE_ID
				FROM
					b_workflow_document D,
					b_workflow_preview P
				WHERE
					D.STATUS_ID = 1
					and P.DOCUMENT_ID = D.ID
				";
		}
		else
		{
			$DOCUMENT_ID = intval($DOCUMENT_ID);
			$strSql = "
				SELECT
					FILENAME
				FROM
					b_workflow_preview
				WHERE
					DOCUMENT_ID = ".$DOCUMENT_ID."
				";
		}
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		while($zr=$z->Fetch())
			CWorkflow::DeletePreview($zr["FILENAME"], $zr["SITE"]);
	}

	public static function DeletePreview($FILENAME, $site = false)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: DeletePreview<br>Line: ";
		global $DB, $APPLICATION;
		$strSql = "DELETE FROM b_workflow_preview WHERE FILENAME='".$DB->ForSql($FILENAME,255)."'";
		$DB->Query($strSql, false, $err_mess.__LINE__);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);
		$path = $DOC_ROOT.$FILENAME;
		if (file_exists($path)) unlink($path);
	}

	public static function DeleteFile($FILENAME)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: DeleteFile<br>Line: ";
		global $DB;
		$strSql = "DELETE FROM b_workflow_file WHERE TEMP_FILENAME='".$DB->ForSql($FILENAME,255)."'";
		$DB->Query($strSql, false, $err_mess.__LINE__);
		$temp_path = CWorkflow::GetTempDir().$FILENAME;
		if (file_exists($temp_path)) unlink($temp_path);
	}

	public static function IsFilenameExists($FILENAME)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: IsFilenameExists<br>Line: ";
		global $DB;
		$strSql = "SELECT ID FROM b_workflow_file WHERE TEMP_FILENAME='".$DB->ForSql($FILENAME,255)."'";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		return intval($zr["ID"]);
	}

	public static function GetUniqueFilename($filename)
	{
		$ext = GetFileExtension($filename);
		$temp_file = md5($filename.uniqid(rand())).".".$ext;
		while (CWorkflow::IsFilenameExists($temp_file))
			$temp_file = md5($filename.uniqid(rand())).".".$ext;
		return $temp_file;
	}

	public static function IsPreviewExists($FILENAME)
	{
		global $DB;

		$z = $DB->Query("
			SELECT ID
			FROM b_workflow_preview
			WHERE FILENAME='".$DB->ForSql($FILENAME,255)."'
		", false, CAllWorkflow::err_mess()."<br>Function: IsPreviewExists<br>Line: ".__LINE__);

		$zr = $z->Fetch();
		return intval($zr["ID"]);
	}

	public static function GetUniquePreview($DOCUMENT_ID)
	{
		global $DB;

		$z = $DB->Query("
			SELECT FILENAME
			FROM b_workflow_document
			WHERE ID = ".intval($DOCUMENT_ID)."
		", false, CAllWorkflow::err_mess()."<br>Function: GetUniquePreview<br>Line: ".__LINE__);

		$zr = $z->Fetch();
		if($zr)
		{
			$DOCUMENT_PATH = GetDirPath($zr["FILENAME"]);
			do
			{
				$temp_file = $DOCUMENT_PATH.md5(uniqid(rand())).".php";
			}
			while(CWorkflow::IsPreviewExists($temp_file));
		}
		return $temp_file;
	}

	public static function SetStatus($DOCUMENT_ID, $STATUS_ID, $OLD_STATUS_ID, $history=true)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: SetStatus<br>Line: ";
		global $DB, $APPLICATION, $USER, $strError;
		//$arMsg = Array();
		$DOCUMENT_ID = intval($DOCUMENT_ID);
		$STATUS_ID = intval($STATUS_ID);
		$OLD_STATUS_ID = intval($OLD_STATUS_ID);
		if ($STATUS_ID==1) // if "[1] Published"
		{
			// get all files associated with the document
			$files = CWorkflow::GetFileList($DOCUMENT_ID);
			while ($file=$files->Fetch())
			{
				$path = $file["FILENAME"];
				$DOC_ROOT = CSite::GetSiteDocRoot($file["SITE_ID"]);
				$pathto = $DOC_ROOT.$path;
				$pathfrom = CWorkflow::GetTempDir().$file["TEMP_FILENAME"];
				if(
					$USER->CanDoFileOperation('fm_edit_in_workflow', Array($file["SITE_ID"], $path))
					&& $USER->CanDoFileOperation('fm_edit_existent_file', Array($file["SITE_ID"], $path))
					&& $USER->CanDoFileOperation('fm_create_new_file', Array($file["SITE_ID"], $path))
				)
				{
					if(!copy($pathfrom,$pathto))
					{
						$str = GetMessage("FLOW_CAN_NOT_WRITE_FILE", array("#FILENAME#" => $path));
						$strError .= $str."<br>";
					}
				}
				else
				{
					$str = GetMessage("FLOW_ACCESS_DENIED_FOR_FILE_WRITE", array("#FILENAME#" => $path));
					$strError .= $str."<br>";
				}
			}

			// still good
			if (strlen($strError)<=0)
			{
				// publish the document
				$y = CWorkflow::GetByID($DOCUMENT_ID);
				$yr = $y->Fetch();
				if(
					$USER->CanDoFileOperation('fm_edit_in_workflow', Array($yr["SITE_ID"], $yr["FILENAME"]))
					&& $USER->CanDoFileOperation('fm_edit_existent_file', Array($yr["SITE_ID"], $yr["FILENAME"]))
					&& $USER->CanDoFileOperation('fm_create_new_file', Array($yr["SITE_ID"], $yr["FILENAME"]))
				)
				{
					// save file
					$prolog = $yr["PROLOG"];
					if (strlen($prolog)>0)
					{
						$title = $yr["TITLE"];
						$prolog = SetPrologTitle($prolog, $title);
					}
					$content = ($yr["BODY_TYPE"]=="text") ? TxtToHTML($yr["BODY"]) : $yr["BODY"];
					$content = WFToPath($content);
					$epilog = $yr["EPILOG"];
					$filesrc = $prolog.$content.$epilog;
					global $BX_WORKFLOW_PUBLISHED_PATH, $BX_WORKFLOW_PUBLISHED_SITE;
					$BX_WORKFLOW_PUBLISHED_PATH = $yr["FILENAME"];
					$BX_WORKFLOW_PUBLISHED_SITE = $yr["SITE_ID"];
					$DOC_ROOT = CSite::GetSiteDocRoot($yr["SITE_ID"]);
					$APPLICATION->SaveFileContent($DOC_ROOT.$yr["FILENAME"], $filesrc);
					$BX_WORKFLOW_PUBLISHED_PATH = "";
					$BX_WORKFLOW_PUBLISHED_SITE = "";
				}
				else // otherwise
				{
					// throw error
					$str = GetMessage("FLOW_ACCESS_DENIED_FOLDER", array("#FILENAME#" => $yr["FILENAME"]));
					$strError .= GetMessage("FLOW_ERROR").htmlspecialcharsbx($str)."<br>";
				}
			}
		}

		if (strlen($strError)<=0)
		{
			// update db
			$arFields = array(
				"DATE_MODIFY"	=> $DB->GetNowFunction(),
				"MODIFIED_BY"	=> $USER->GetID(),
				"STATUS_ID"		=> intval($STATUS_ID)
				);
			$DB->Update("b_workflow_document",$arFields,"WHERE ID='".$DOCUMENT_ID."'",$err_mess.__LINE__);
			if ($history===true)
			{
				$LOG_ID = CWorkflow::SetHistory($DOCUMENT_ID);
				CWorkflow::SetMove($DOCUMENT_ID, $STATUS_ID, $OLD_STATUS_ID, $LOG_ID);
			}
		}
		else
		{
			$strError = GetMessage("FLOW_DOCUMENT_NOT_PUBLISHED")."<br>".$strError;
		}
		CWorkflow::CleanUpPublished();
	}

	public static function LinkFiles2Document($arUploadedFiles,$DOCUMENT_ID)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: SetStatus<br>Line: ";
		global $DB;
		$DOCUMENT_ID = intval($DOCUMENT_ID);
		if (is_array($arUploadedFiles) && count($arUploadedFiles)>0)
		{
			foreach ($arUploadedFiles as $FILE_ID)
			{
				$FILE_ID = intval($FILE_ID);
				$strSql = "UPDATE b_workflow_file SET DOCUMENT_ID=$DOCUMENT_ID WHERE ID=$FILE_ID";
				$DB->Query($strSql, false, $err_mess.__LINE__);
			}
		}
		CWorkflow::CleanUpFiles();
	}

	public static function GetFileByID($DOCUMENT_ID, $FILENAME)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: GetFileByID<br>Line: ";
		global $DB;
		$DOCUMENT_ID = intval($DOCUMENT_ID);
		$strSql = "
			SELECT
				F.*
			FROM
				b_workflow_file F
			WHERE
				F.DOCUMENT_ID = $DOCUMENT_ID
			and F.FILENAME = '".$DB->ForSql($FILENAME,255)."'
			";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $z;
	}

	public static function GetTempDir()
	{
		$upload_dir = COption::GetOptionString("","upload_dir","/upload/");
		$dir = $_SERVER["DOCUMENT_ROOT"]."/".$upload_dir."/workflow/";
		$dir = str_replace("//","/",$dir);
		return $dir;
	}

	public static function GetFileContent($did, $fname, $wf_path="", $site=false)
	{
		$err_mess = (CAllWorkflow::err_mess())."<br>Function: GetFileContent<br>Line: ";
		global $DB, $APPLICATION, $USER;
		$did = intval($did);
		// check if executable
		if (
			$USER->IsAdmin()
			|| (
				CBXVirtualIoFileSystem::ValidatePathString($fname)
				&& !HasScriptExtension($fname)
			)
		)
		{
			if ($did>0)
			{
				// check if it is associated wtih document
				$z = CWorkflow::GetFileByID($did, $fname);
				// found one
				if ($zr = $z->Fetch())
				{
					// get it's contents
					$path = CWorkflow::GetTempDir().$zr["TEMP_FILENAME"];
					if (file_exists($path))
					{
						return $APPLICATION->GetFileContent($path);
					}
				}
				else
				{
					// lookup in database
					$strSql = "SELECT FILENAME, SITE_ID FROM b_workflow_document WHERE ID='$did'";
					$y = $DB->Query($strSql, false, $err_mess.__LINE__);
					// found
					if ($yr=$y->Fetch())
					{
						// get it's directory
						$path = GetDirPath($yr["FILENAME"]);
						// absolute path
						$pathto = Rel2Abs($path, $fname);
						$DOC_ROOT = CSite::GetSiteDocRoot($yr["SITE_ID"]);
						$path = $DOC_ROOT.$pathto;
						// give it another try
						$u = CWorkflow::GetFileByID($did, $pathto);
						// found
						if ($ur = $u->Fetch())
						{
							// get it's contents
							$path = CWorkflow::GetTempDir().$ur["TEMP_FILENAME"];
							if (file_exists($path)) return $APPLICATION->GetFileContent($path);
						}
						elseif (file_exists($path)) // it is already on disk
						{
							// get it's contents
							if($USER->CanDoFileOperation('fm_view_file', Array($yr["SITE_ID"], $pathto)))
								return $APPLICATION->GetFileContent($path);
						}
					}
				}
			}
			$DOC_ROOT = CSite::GetSiteDocRoot($site);
			// new one
			if (strlen($wf_path)>0)
			{
				$pathto = Rel2Abs($wf_path, $fname);
				$path = $DOC_ROOT.$pathto;
				if (file_exists($path)) // it is already on disk
				{
					// get it's contents
					if($USER->CanDoFileOperation('fm_view_file', Array($site, $pathto)))
					{
						$src = $APPLICATION->GetFileContent($path);
						return $src;
					}
				}
			}

			// still failed to find
			// get path
			$path = $DOC_ROOT.$fname;
			if (file_exists($path))
			{
				// get it's contents
				if($USER->CanDoFileOperation('fm_view_file', Array($site, $fname)))
					return $APPLICATION->GetFileContent($path);
			}
		} // it is executable
		else
		{
			return GetMessage("FLOW_ACCESS_DENIED_PHP_VIEW");
		}
	}

	public static function __CheckSite($site)
	{
		if($site!==false)
		{
			if(strlen($site)>0)
			{
				$res = CSite::GetByID($site);
				if(!($arSite = $res->Fetch()))
					$site = false;
			}
			else
				$site = false;
		}

		return $site;
	}
}
