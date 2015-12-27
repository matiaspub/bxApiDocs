<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/support/classes/general/support.php");


/**
 * <b>CTicket</b> - класс для работы с обращениями. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/support/classes/cticket/index.php
 * @author Bitrix
 */
class CTicket extends CAllTicket
{

	static function isnull( $field, $alternative )
	{
		return "ifnull(" . $field . "," . $alternative . ")";
	}

	public static function err_mess()
	{
		$module_id = "support";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." <br>Class: CTicket<br>File: ".__FILE__;
	}

	public static function AutoClose()
	{
		$err_mess = (CTicket::err_mess())."<br>Function: AutoClose<br>Line: ";
		global $DB;
		/*$strSql = "
			SELECT
				T.ID
			FROM
				b_ticket T
			WHERE
				T.AUTO_CLOSE_DAYS > 0
			and (T.DATE_CLOSE is null or length(T.DATE_CLOSE)<=0)
			and	(UNIX_TIMESTAMP(now())-UNIX_TIMESTAMP(T.TIMESTAMP_X))/86400 > T.AUTO_CLOSE_DAYS
			";*/

		$nowTime = $DB->CharToDateFunction(GetTime(time() + CTimeZone::GetOffset(),"FULL"));
		$strSql = "
			SELECT
				T.ID
			FROM
				b_ticket T
			WHERE
				T.AUTO_CLOSE_DAYS > 0
			and (T.DATE_CLOSE is null or length(T.DATE_CLOSE)<=0)
			and	(UNIX_TIMESTAMP($nowTime)-UNIX_TIMESTAMP(T.LAST_MESSAGE_DATE))/86400 > T.AUTO_CLOSE_DAYS
			and T.LAST_MESSAGE_BY_SUPPORT_TEAM = 'Y'
			";//now()

		$rsTickets = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($arTicket = $rsTickets->Fetch())
		{
			$arFields = array(
				"TIMESTAMP_X"			=> $DB->GetNowFunction(),
				"DATE_CLOSE"			=> $DB->GetNowFunction(),
				"MODIFIED_USER_ID"		=> "null",
				"MODIFIED_GUEST_ID"		=> "null",
				"MODIFIED_MODULE_NAME"	=> "'auto closing'",
				//"AUTO_CLOSE_DAYS"		=> "null",
				"AUTO_CLOSED"			=> "'Y'"
				);
			$DB->Update("b_ticket",$arFields,"WHERE ID='".$arTicket["ID"]."'",$err_mess.__LINE__);
		}
		return "CTicket::AutoClose();";
	}

	public static function CleanUpOnline()
	{
		$err_mess = (CTicket::err_mess())."<br>Function: CleanUpOnline<br>Line: ";
		global $DB;
		$onlineInterval = intval(COption::GetOptionString("support", "ONLINE_INTERVAL"));
		$strSql = "
			DELETE FROM b_ticket_online WHERE
				TIMESTAMP_X < DATE_ADD(now(), INTERVAL - $onlineInterval SECOND)
			";
		$DB->Query($strSql,false,$err_mess.__LINE__);
		return "CTicket::CleanUpOnline();";
	}

	public static function GetOnline($ticketID)
	{
		$err_mess = (CTicket::err_mess())."<br>Function: GetOnline<br>Line: ";
		global $DB;
		$ticketID = intval($ticketID);
		$onlineInterval = intval(COption::GetOptionString("support", "ONLINE_INTERVAL"));
		$strSql = "
			SELECT
				".$DB->DateToCharFunction("max(T.TIMESTAMP_X)")."		TIMESTAMP_X,
				T.USER_ID,
				T.CURRENT_MODE,
				U.EMAIL													USER_EMAIL,
				U.LOGIN													USER_LOGIN,
				concat(ifnull(U.NAME,''),' ',ifnull(U.LAST_NAME,''))	USER_NAME
			FROM
				b_ticket_online T,
				b_user U
			WHERE
				T.TICKET_ID = $ticketID
			and T.TIMESTAMP_X >= DATE_ADD(now(), INTERVAL - $onlineInterval SECOND)
			and U.ID = T.USER_ID
			GROUP BY
				T.USER_ID, U.EMAIL, U.LOGIN, U.NAME, U.LAST_NAME
			ORDER BY
				T.USER_ID
			";

		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $z;
	}

	
	/**
	* <p>Метод удаляет сообщение.</p>
	*
	*
	* @param int $MESSAGE_ID  ID сообщения.</bod
	*
	* @param char(1) $CHECK_RIGHTS = "Y" "Y" - необходимо проверить право на удаление у текущего
	* пользователя (по умолчанию); "N" - прав проверять не надо.
	* Необязательный параметр. С версии 12.0.0 изменен на <b>checkRights</b>.
	*
	* @return record 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/support/classes/cticket/deletemessage.php
	* @author Bitrix
	*/
	public static function DeleteMessage($ID, $checkRights="Y")
	{
		$err_mess = (CTicket::err_mess())."<br>Function: DeleteMessage<br>Line: ";
		global $DB;
		$ID = intval($ID);
		if ($ID<=0) return;

		$bAdmin = "N";
		if ($checkRights=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
		}
		else
		{
			$bAdmin = "Y";
		}

		if ($bAdmin=="Y")
		{
			$strSql = "
				SELECT
					F.ID FILE_ID,
					M.TICKET_ID
				FROM
					b_ticket_message M
				LEFT JOIN b_ticket_message_2_file MF ON (MF.MESSAGE_ID = M.ID)
				LEFT JOIN b_file F ON (F.ID = MF.FILE_ID)
				WHERE
					M.ID='$ID'
				";

			$z = $DB->Query($strSql, false, $err_mess.__LINE__);
			while ($zr = $z->Fetch())
			{
				$ticketID = $zr["TICKET_ID"];
				if (intval($zr["FILE_ID"])>0)
				{
					CFile::Delete($zr["FILE_ID"]);
				}
			}

			$z = $DB->Query("DELETE FROM b_ticket_message WHERE ID='$ID'", false, $err_mess.__LINE__);
			if (intval($z->AffectedRowsCount())>0)
			{
				//CTicket::UpdateLastParams($ticketID);
				//CTicket::UpdateLastParams2($ticketID, CTicket::DELETE);
				CTicket::UpdateLastParamsN($ticketID, array("EVENT"=>array(CTicket::DELETE)), true, true);

				if (CSupportSearch::isIndexExists())
				{
					CSupportSearch::reindexTicket($ticketID);
				}
			}
		}
	}

	public static function UpdateMessage($MESSAGE_ID, $arFields, $checkRights="Y")
	{
		$err_mess = (CTicket::err_mess())."<br>Function: UpdateMessage<br>Line: ";
		global $DB, $USER;

		$MESSAGE_ID = intval($MESSAGE_ID);
		$bAdmin = "N";
		$bSupportTeam = "N";
		if ($checkRights=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
			$bSupportTeam = (CTicket::IsSupportTeam()) ? "Y" : "N";
			$uid = $USER->GetID();
		}
		else
		{
			$bAdmin = "Y";
			$bSupportTeam = "Y";
			$uid = 0;
		}

		if ($bAdmin=="Y")
		{
			$ownerSid = $arFields["OWNER_SID"];
			$ownerUserID = $arFields["OWNER_USER_ID"];
			$arFields_u = array(
				"TIMESTAMP_X"		=> $DB->GetNowFunction(),
				"C_NUMBER"			=> intval($arFields["C_NUMBER"]),
				"MESSAGE"			=> "'".$DB->ForSql($arFields["MESSAGE"])."'",
				"MESSAGE_SEARCH"	=> "'".ToUpper($DB->ForSql($arFields["MESSAGE"]))."'",
				"SOURCE_ID"			=> (intval($arFields["SOURCE_ID"])>0 ? intval($arFields["SOURCE_ID"]) : "null"),
				"OWNER_SID"			=> "'".$DB->ForSql($ownerSid, 255)."'",
				"OWNER_USER_ID"		=> (intval($ownerUserID)>0 ? intval($ownerUserID) : "null"),
				"MODIFIED_USER_ID"	=> (intval($uid)>0 ? intval($uid) : "null"),
				"MODIFIED_GUEST_ID"	=> (intval($_SESSION["SESS_GUEST_ID"])>0 ? intval($_SESSION["SESS_GUEST_ID"]) : "null"),
				"EXTERNAL_ID"		=> (intval($arFields["EXTERNAL_ID"])>0 ? intval($arFields["EXTERNAL_ID"]) : "null"),
				"TASK_TIME"		=> (intval($arFields["TASK_TIME"])>0 ? intval($arFields["TASK_TIME"]) : "null"),
				"EXTERNAL_FIELD_1"	=> "'".$DB->ForSql($arFields["EXTERNAL_FIELD_1"])."'",
				"IS_SPAM"			=> (strlen($arFields["IS_SPAM"])>0 ? "'".$arFields["IS_SPAM"]."'" : "null"),
				"IS_HIDDEN"			=> ($arFields["IS_HIDDEN"]=="Y" ? "'Y'" : "'N'"),
				"IS_LOG"			=> ($arFields["IS_LOG"]=="Y" ? "'Y'" : "'N'"),
				"IS_OVERDUE"		=> ($arFields["IS_OVERDUE"]=="Y" ? "'Y'" : "'N'"),
				"NOT_CHANGE_STATUS" => ($arFields["NOT_CHANGE_STATUS"]=="Y" ? "'Y'" : "'N'")
				);

			$notChangeStatus = (
				is_set($arFields, "NOT_CHANGE_STATUS") && $arFields["NOT_CHANGE_STATUS"]=="Y"
				? "Y"
				: "N"
			);


			$rows = $DB->Update("b_ticket_message",$arFields_u,"WHERE ID='".$MESSAGE_ID."'",$err_mess.__LINE__);
			if (intval($rows)>0)
			{
				$rsMessage = CTicket::GetMessageByID($MESSAGE_ID, $checkRights);
				if ($arMessage = $rsMessage->Fetch())
				{
					$ticketID = $arMessage["TICKET_ID"];

					// обновим прикрепленные файлы
					$not_image_extension_suffix = COption::GetOptionString("support", "NOT_IMAGE_EXTENSION_SUFFIX");
					$not_image_upload_dir = COption::GetOptionString("support", "NOT_IMAGE_UPLOAD_DIR");
					$max_size = COption::GetOptionString("support", "SUPPORT_MAX_FILESIZE");

					$arrFiles = $arFields["FILES"];
					if (is_array($arrFiles) && count($arrFiles)>0)
					{
						foreach ($arrFiles as $arFile)
						{
							if (strlen($arFile["name"])>0 || $arFile["del"]=="Y")
							{
								if ($bSupportTeam!="Y" && $bAdmin!="Y") $max_file_size = intval($max_size)*1024;
								$fes = "";
								$upload_dir = "support";
								if (!CFile::IsImage($arFile["name"], $arFile["type"]))
								{
									$fes = $not_image_extension_suffix;
									$arFile["name"] .= $fes;
									$upload_dir = $not_image_upload_dir;
								}

								if (!array_key_exists("MODULE_ID", $arFile) || strlen($arFile["MODULE_ID"]) <= 0)
									$arFile["MODULE_ID"] = "support";

								$fid = intval(CFile::SaveFile($arFile, $upload_dir, $max_file_size));

								// если стоял флаг "Удалить" то
								if ($arFile["del"]=="Y")
								{
									// удалим связку
									$strSql = "
										DELETE FROM
											b_ticket_message_2_file
										WHERE
											FILE_ID=".intval($arFile["old_file"])."
										";
									$DB->Query($strSql, false, $err_mess.__LINE__);
								}

								// если успешно загрузили файл то
								if ($fid>0)
								{
									// если это был новый файл то
									if (intval($arFile["old_file"])<=0)
									{
										// добавим связку
										$md5 = md5(uniqid(mt_rand(), true).time());
										$arFields_fi = array(
											"HASH"				=> "'".$DB->ForSql($md5, 255)."'",
											"MESSAGE_ID"		=> $MESSAGE_ID,
											"FILE_ID"			=> $fid,
											"TICKET_ID"			=> $ticketID,
											"EXTENSION_SUFFIX"	=> (strlen($fes)>0) ? "'".$DB->ForSql($fes, 255)."'" : "null"
											);
										$DB->Insert("b_ticket_message_2_file",$arFields_fi, $err_mess.__LINE__);
									}
									else // иначе
									{
										// обновим связку
										$arFields_fu = array(
											"FILE_ID"			=> $fid,
											"EXTENSION_SUFFIX"	=> (strlen($fes)>0) ? "'".$DB->ForSql($fes, 255)."'" : "null"
											);
										$DB->Update("b_ticket_message_2_file", $arFields_fu, "WHERE FILE_ID = ".intval($arFile["old_file"]),$err_mess.__LINE__);
									}
								}
							}
						}
					}
					if ($arFields["IS_SPAM"]=="Y")
						CTicket::MarkMessageAsSpam($MESSAGE_ID,"Y",$checkRights);
					elseif ($arFields["IS_SPAM"]=="N")
						CTicket::MarkMessageAsSpam($MESSAGE_ID,"N",$checkRights);
					elseif ($arFields["IS_SPAM"]!="Y" && $arFields["IS_SPAM"]!="N")
						CTicket::UnMarkMessageAsSpam($MESSAGE_ID,$checkRights);

					//if ($notChangeStatus != "Y")
					//CTicket::UpdateLastParams($ticketID);
					//if ($notChangeStatus!="Y" && $hidden!="Y" && $log!="Y")
					//{
						//CTicketReminder::Update($ticketID);
					//}

					CSupportSearch::reindexTicket($ticketID);
				}
			}
		}
	}

public static 	function AddMessage($ticketID, $arFields, &$arrFILES, $checkRights="Y")
	{
		if (strlen($arFields["MESSAGE"])>0 || (is_array($arFields["FILES"]) && count($arFields["FILES"])>0))
		{
			$err_mess = (CTicket::err_mess())."<br>Function: AddMessage<br>Line: ";
			global $DB, $USER;

			$bAdmin = "N";
			$bSupportTeam = "N";
			$bSupportClient = "N";
			if ($checkRights=="Y")
			{
				$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
				$bSupportTeam = (CTicket::IsSupportTeam()) ? "Y" : "N";
				$bSupportClient = (CTicket::IsSupportClient()) ? "Y" : "N";
				$uid = intval($USER->GetID());
			}
			else
			{
				$bAdmin = "Y";
				$bSupportTeam = "Y";
				$bSupportClient = "Y";
				//if (is_object($USER)) $uid = intval($USER->GetID()); else $uid = -1;
				$uid = 0;
			}
			if ($bAdmin!="Y" && $bSupportTeam!="Y" && $bSupportClient!="Y")
			{
				return false;
			}

			$ticketID = intval($ticketID);
			if ($ticketID<=0)
			{
				return 0;
			}

			$strSql = "SELECT RESPONSIBLE_USER_ID, LAST_MESSAGE_USER_ID, REOPEN, SITE_ID, TITLE FROM b_ticket WHERE ID='$ticketID'";
			$rsTicket = $DB->Query($strSql, false, $err_mess.__LINE__);
			$arTicket = $rsTicket->Fetch();
			$currentResponsibleUserID = $arTicket["RESPONSIBLE_USER_ID"];
			$siteID = $arTicket["SITE_ID"];
			$tTitle = $arTicket["TITLE"];

			$strSql = "SELECT max(C_NUMBER) MAX_NUMBER FROM b_ticket_message WHERE TICKET_ID='$ticketID'";
			$z = $DB->Query($strSql, false, $err_mess.__LINE__);
			$zr = $z->Fetch();
			$maxNumber = intval($zr['MAX_NUMBER']);

			if ((strlen(trim($arFields["MESSAGE_AUTHOR_SID"]))>0 || intval($arFields["MESSAGE_AUTHOR_USER_ID"])>0 || intval($arFields["MESSAGE_CREATED_USER_ID"])>0) && ($bSupportTeam=="Y" || $bAdmin=="Y"))
			{
				$ownerUserID = intval($arFields["MESSAGE_AUTHOR_USER_ID"]);
				$ownerSid = "'".$DB->ForSql($arFields["MESSAGE_AUTHOR_SID"],2000)."'";
				$ownerGuestID = intval($arFields["MESSAGE_AUTHOR_GUEST_ID"])>0 ? intval($arFields["MESSAGE_AUTHOR_GUEST_ID"]) : "null";

				$createdUserID = intval($arFields["MESSAGE_CREATED_USER_ID"])>0 ? intval($arFields["MESSAGE_CREATED_USER_ID"]) : intval($uid);
				$createdGuestID = intval($arFields["MESSAGE_CREATED_GUEST_ID"])>0 ? intval($arFields["MESSAGE_CREATED_GUEST_ID"]) : intval($_SESSION["SESS_GUEST_ID"]);
			}
			else
			{
				$ownerUserID = intval($uid);
				$ownerSid = "null";
				$ownerGuestID = intval($_SESSION["SESS_GUEST_ID"]);

				$createdUserID = intval($uid);
				$createdGuestID = intval($_SESSION["SESS_GUEST_ID"]);
			}

			if (intval($ownerGuestID)<=0)
			{
				$ownerGuestID = "null";
			}

			$MessageBySupportTeam = "null";
			if ($ownerUserID<=0)
			{
				$ownerUserID = "null";
			}
			else
			{
				$MessageBySupportTeam = "'N'";
				if (CTicket::IsSupportTeam($ownerUserID) || CTicket::IsAdmin($ownerUserID))
				{
					$MessageBySupportTeam = "'Y'";
				}
			}

			if ($createdUserID<=0)
			{
				$createdUserID = "null";
			}
			if (intval($createdGuestID)<=0)
			{
				$createdGuestID = "null";
			}

			$createdModuleName = (strlen($arFields["MESSAGE_CREATED_MODULE_NAME"])>0) ? "'".$DB->ForSql($arFields["MESSAGE_CREATED_MODULE_NAME"],255)."'" : "'support'";

			$externalID = intval($arFields["EXTERNAL_ID"])>0 ? intval($arFields["EXTERNAL_ID"]) : "null";
			$externalField1 = $arFields["EXTERNAL_FIELD_1"];

			if (is_set($arFields, "HIDDEN"))
			{
				$hidden = ($arFields["HIDDEN"]=="Y") ? "Y" : "N";
			}
			elseif (is_set($arFields, "IS_HIDDEN"))
			{
				$hidden = ($arFields["IS_HIDDEN"]=="Y") ? "Y" : "N";
			}
			$hidden = ($hidden=="Y") ? "Y" : "N";

			$notChangeStatus = (
				is_set($arFields, "NOT_CHANGE_STATUS") && $arFields["NOT_CHANGE_STATUS"]=="Y"
				? "Y"
				: "N"
			);

			$changeLastMessageDate = true;
			if ($arTicket["LAST_MESSAGE_USER_ID"] == $uid && $arTicket["REOPEN"] != "Y")
			{
				$changeLastMessageDate = false;
			}

			$TASK_TIME = intval($arFields["TASK_TIME"])>0 ? intval($arFields["TASK_TIME"]) : "null";

			if (is_set($arFields, "LOG"))
			{
				$log = ($arFields["LOG"]=="Y") ? "Y" : "N";
			}
			elseif (is_set($arFields, "IS_LOG"))
			{
				$log = ($arFields["IS_LOG"]=="Y") ? "Y" : "N";
			}
			$log = ($log=="Y") ? "Y" : "N";

			if (is_set($arFields, "OVERDUE"))
			{
				$overdue = ($arFields["OVERDUE"]=="Y") ? "Y" : "N";
			}
			elseif (is_set($arFields, "IS_OVERDUE"))
			{
				$overdue = ($arFields["IS_OVERDUE"]=="Y") ? "Y" : "N";
			}
			$overdue = ($overdue=="Y") ? "Y" : "N";

			$arFieldsI = array(
				"TIMESTAMP_X"					=> $DB->GetNowFunction(),
				"DAY_CREATE"					=> $DB->CurrentDateFunction(),
				"C_NUMBER"						=> $maxNumber + 1,
				"TICKET_ID"						=> $ticketID,
				"IS_HIDDEN"						=> "'".$hidden."'",
				"IS_LOG"						=> "'".$log."'",
				"IS_OVERDUE"					=> "'".$overdue."'",
				"MESSAGE"						=> "'".$DB->ForSql($arFields["MESSAGE"])."'",
				"MESSAGE_SEARCH"				=> "'".$DB->ForSql(ToUpper($arFields["MESSAGE"]))."'",
				"EXTERNAL_ID"					=> $externalID,
				"EXTERNAL_FIELD_1"				=> (strlen($externalField1)>0 ? "'".$DB->ForSql($externalField1)."'" : "null"),
				"OWNER_USER_ID"					=> $ownerUserID,
				"OWNER_GUEST_ID"				=> $ownerGuestID,
				"OWNER_SID"						=> $ownerSid,
				"SOURCE_ID"						=> intval($arFields["MESSAGE_SOURCE_ID"]),
				"CREATED_USER_ID"				=> $createdUserID,
				"CREATED_GUEST_ID"				=> $createdGuestID,
				"CREATED_MODULE_NAME"			=> $createdModuleName,
				"MODIFIED_USER_ID"				=> $createdUserID,
				"MODIFIED_GUEST_ID"				=> $createdGuestID,
				"MESSAGE_BY_SUPPORT_TEAM"		=> $MessageBySupportTeam,
				"TASK_TIME" => $TASK_TIME,
				"NOT_CHANGE_STATUS" => "'".$notChangeStatus."'"
				);

			CTimeZone::Disable();
			$arFieldsI["DATE_CREATE"] = $DB->CharToDateFunction( GetTime( time() ,"FULL" ) );
			CTimeZone::Enable();

			/*if ($hidden!="Y" && $log!="Y" && $changeLastMessageDate == false)
			{
				if ($MessageBySupportTeam == "'Y'" || ($maxNumber <= 0 && array_key_exists('SOURCE_SID', $arFields) && $arFields['SOURCE_SID'] === 'email'))
				{
					$arFieldsI["NOT_CHANGE_STATUS"] = "'N'";
				}
				else
				{
					$arFieldsI["NOT_CHANGE_STATUS"] = "'Y'";
				}
			}*/

			if (intval($currentResponsibleUserID)>0) $arFieldsI["CURRENT_RESPONSIBLE_USER_ID"] = $currentResponsibleUserID;



			$mid = $DB->Insert("b_ticket_message",$arFieldsI, $err_mess.__LINE__);
			if (intval($mid)>0)
			{
				$not_image_extension_suffix = COption::GetOptionString("support", "NOT_IMAGE_EXTENSION_SUFFIX");
				$not_image_upload_dir = COption::GetOptionString("support", "NOT_IMAGE_UPLOAD_DIR");
				$max_size = COption::GetOptionString("support", "SUPPORT_MAX_FILESIZE");
				// сохраняем приаттаченные файлы
				$arFILES = $arFields["FILES"];
				if (is_array($arFILES) && count($arFILES)>0)
				{
					while (list($key, $arFILE) = each($arFILES))
					{
						if (strlen($arFILE["name"])>0)
						{
							if ($bSupportTeam!="Y" && $bAdmin!="Y")
							{
								$max_file_size = intval($max_size) * 1024;
							}
							$fes = "";
							$upload_dir = "support";
							if (!CFile::IsImage($arFILE["name"], $arFILE["type"]))
							{
								$fes = $not_image_extension_suffix;
								$arFILE["name"] .= $fes;
								$upload_dir = $not_image_upload_dir;
							}

							if (!array_key_exists("MODULE_ID", $arFILE) || strlen($arFILE["MODULE_ID"]) <= 0)
							{
								$arFILE["MODULE_ID"] = "support";
							}

							$fid = intval(CFile::SaveFile($arFILE, $upload_dir, $max_file_size));
							if ($fid>0)
							{
								$md5 = md5(uniqid(mt_rand(), true).time());
								$arFILE["HASH"] = $md5;
								$arFILE["FILE_ID"] = $fid;
								$arFILE["MESSAGE_ID"] = $mid;
								$arFILE["TICKET_ID"] = $ticketID;
								$arFILE["EXTENSION_SUFFIX"] = $fes;
								$arFields_fi = array(
									"HASH"				=> "'".$DB->ForSql($md5, 255)."'",
									"MESSAGE_ID"		=> $mid,
									"FILE_ID"			=> $fid,
									"TICKET_ID"			=> $ticketID,
									"EXTENSION_SUFFIX"	=> (strlen($fes)>0) ? "'".$DB->ForSql($fes, 255)."'" : "null"
									);
								$link_id = $DB->Insert("b_ticket_message_2_file",$arFields_fi, $err_mess.__LINE__);
								if (intval($link_id)>0)
								{
									$arFILE["LINK_ID"] = $link_id;
									$arrFILES[] = $arFILE;
								}
							}
						}
					}
				}

				/*
				// если это не было скрытым сообщением или сообщение лога, то
				if ($notChangeStatus!="Y" && $hidden!="Y" && $log!="Y")
				{
					// обновим ряд параметров обращения
					if (!isset($arFields["AUTO_CLOSE_DAYS"])) $RESET_AUTO_CLOSE = "Y";
					
					CTicket::UpdateLastParams($ticketID, $RESET_AUTO_CLOSE, $changeLastMessageDate, true);

					// при необходимости создадим или удалим агенты-напоминальщики
					//CTicketReminder::Update($ticketID);
				}*/

				if ( $log!="Y" && CSupportSearch::isIndexExists())
				{
					CSupportSearch::reindexTicket($ticketID);
				}

				//если была установлена галочка "не изменять статус обращени" - пересчитаем количество собщений
				if ($notChangeStatus == "Y" || $hidden == "Y")
					CTicket::UpdateMessages($ticketID);
			}
		}
		return $mid;
	}

	public static function GetStatus($ticketID)
	{
		$err_mess = (CTicket::err_mess())."<br>Function: GetStatus<br>Line: ";
		global $DB, $USER;

		$ticketID = intval($ticketID);
		if ($ticketID<=0) return false;

		$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
		$bSupportTeam = (CTicket::IsSupportTeam()) ? "Y" : "N";
		$bSupportClient = (CTicket::IsSupportClient()) ? "Y" : "N";
		$bDemo = (CTicket::IsDemo()) ? "Y" : "N";
		$uid = intval($USER->GetID());

		if ($bSupportTeam=="Y" || $bAdmin=="Y" || $bDemo=="Y")
		{
			$lamp = "
				if(ifnull(T.DATE_CLOSE,'x')<>'x', 'grey',
					if(ifnull(T.LAST_MESSAGE_USER_ID,0)='$uid', 'green',
						if(ifnull(T.OWNER_USER_ID,0)='$uid', 'red',
							if(T.LAST_MESSAGE_BY_SUPPORT_TEAM='Y','green_s',
								if(ifnull(T.RESPONSIBLE_USER_ID,0)='$uid', 'red',
									'yellow')))))
				";
		}
		else
		{
			$lamp = "
				if(ifnull(T.DATE_CLOSE,'x')<>'x', 'grey',
					if(ifnull(T.LAST_MESSAGE_USER_ID,0)='$uid', 'green', 'red'))
				";
		}

		$strSql = "
			SELECT
				$lamp	LAMP
			FROM
				b_ticket T
			WHERE
				ID = $ticketID
			";
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($ar = $rs->Fetch()) return $ar["LAMP"];

		return false;
	}


	/**
	* <p>Метод предназначен для получения списка обращений в техподдержку.</p>
	*
	*
	* @param varchar &$by  Идентификатор, позволяющий задать имя поля для сортировки.
	* Допустимы следующие значения: <ul> <li>s_id - по ID </li> <li>s_lid - по сайту,
	* для которого было создано обращение </li> <li>s_lamp - по индикатору </li>
	* <li>s_date_create - по дате создания </li> <li>s_timestamp - по дате изменения </li>
	* <li>s_date_close - по дате закрытия </li> <li>s_owner - по автору </li> <li>s_modified_by - по
	* ID пользователя - изменившего обращение </li> <li>s_title - по заголовку
	* </li> <li>s_responsible - по ID ответственного </li> <li>s_messages - по количеству
	* сообщений </li> <li>s_category - по ID категории </li> <li>s_criticality - по ID
	* критичности </li> <li>s_status - по ID статуса </li> <li>s_mark - по ID оценки </li>
	* <li>s_online - по количеству пользователей </li> </ul>
	*
	* @param varchar &$order  Порядок сортировки. Допустимы следующие значения: <ul> <li>desc - по
	* убыванию (значение по умолчанию) </li> <li>asc - по возрастанию </li> </ul>
	*
	* @param array $arFilter = array() Массив для фильтрации значений. Необязательный параметр. В
	* массиве допустимы следующие индексы: <ul> <li>ID - ID обращения
	* (допускается сложная логика) </li> <li>ID_EXACT_MATCH - "Y" - при фильтрации по
	* ID обращения будет искаться точное совпадение (по умолчанию); "N" - в
	* противном случае будет искаться вхождение </li> <li>LID - ID сайта </li>
	* <li>LID_EXACT_MATCH - "Y" - при фильтрации по ID языка будет искаться точное
	* совпадение (по умолчанию); "N" - в противном случае будет искаться
	* вхождение </li> <li>LAMP - массив индикаторов: "red", "yellow", "green", "green_s", "grey"
	* </li> <li>DATE_CREATE_1 - левая часть интервала для даты создания обращения
	* </li> <li>DATE_CREATE_2 - правая часть интервала для даты создания обращения
	* </li> <li>DATE_TIMESTAMP_1 - левая часть интервала для даты модификации
	* обращения </li> <li>DATE_TIMESTAMP_2 - правая часть интервала для даты
	* модификации обращения </li> <li>DATE_CLOSE_1 - левая часть интервала для
	* даты закрытия обращения </li> <li>DATE_CLOSE_2 - правая часть интервала для
	* даты закрытия обращения </li> <li>AUTO_CLOSE_DAYS1 - левая часть интервала
	* для количества дней по истечении которых обращение будет
	* автоматически закрыто при отсутствии ответа от автора </li>
	* <li>AUTO_CLOSE_DAYS2 - правая часть интервала для количества дней по
	* истечении которых обращение будет автоматически закрыто при
	* отсутствии ответа от автора </li> <li>TICKET_TIME_1 - левая часть интервала
	* для количества дней прошедших с даты создания до даты закрытия
	* обращения </li> <li>TICKET_TIME_2 - правая часть интервала для количества
	* дней прошедших с даты создания до даты закрытия обращения </li>
	* <li>TITLE - заголовок обращения (допускается сложная логика) </li>
	* <li>TITLE_EXACT_MATCH - "Y" - при фильтрации по заголовку обращения будет
	* искаться точное совпадение (по умолчанию); "N" - в противном случае
	* будет искаться вхождение </li> <li>MESSAGES1 - левая часть интервала для
	* количества сообщений в обращении </li> <li>MESSAGES2 - правая часть
	* интервала для количества сообщений в обращении </li> <li>OWNER - автор
	* обращения, поиск осуществляется по ID пользователя, логину, имени,
	* фамилии, символному коду (допускается сложная логика) </li>
	* <li>OWNER_EXACT_MATCH - "Y" - при фильтрации по автору обращения будет
	* искаться точное совпадение (по умолчанию); "N" - в противном случае
	* будет искаться вхождение </li> <li>CREATED_BY - кем было создано обращение,
	* поиск осуществляется по ID пользователя, логину, имени, фамилии,
	* названии модуля из которого было создано обращение (допускается
	* сложная логика) </li> <li>CREATED_BY_EXACT_MATCH - "Y" - при фильтрации по
	* создателю обращения будет искаться точное совпадение (по
	* умолчанию); "N" - в противном случае будет искаться вхождение </li>
	* <li>MODIFIED_BY - изменивший обращение, поиск осуществляется по ID
	* пользователя, логину, имени, фамилии, названии модуля из которого
	* было изменено обращение (допускается сложная логика) </li>
	* <li>MODIFIED_BY_EXACT_MATCH - "Y" - при фильтрации по символьному коду статуса
	* будет искаться точное совпадение (по умолчанию); "N" - в противном
	* случае будет искаться вхождение </li> <li>RESPONSIBLE - ответственный за
	* обращение, поиск осуществляется по ID пользователя, логину, имени,
	* фамилии (допускается сложная логика) </li> <li>RESPONSIBLE_EXACT_MATCH - "Y" - при
	* фильтрации по ответственному за обращение будет искаться точное
	* совпадение (по умолчанию); "N" - в противном случае будет искаться
	* вхождение </li> <li>RESPONSIBLE_ID - ID пользователя ответственного за
	* обращение, при задании "0" будут выбраны только те обращения у
	* которых нет ответственного </li> <li>CATEGORY - ID категории обращения (0 -
	* без категории) </li> <li>CATEGORY_SID - символьный код категории обращения
	* (допускается сложная логика) </li> <li>CATEGORY_SID_EXACT_MATCH - "Y" - при
	* фильтрации по символьному коду категории будет искаться точное
	* совпадение (по умолчанию); "N" - в противном случае будет искаться
	* вхождение </li> <li>CRITICALITY - ID критичности обращения (0 - без
	* критичности) </li> <li>CRITICALITY_SID - символьный код критичности
	* обращения (допускается сложная логика) </li> <li>CRITICALITY_SID_EXACT_MATCH - "Y" -
	* при фильтрации по символьному коду критичности будет искаться
	* точное совпадение (по умолчанию); "N" - в противном случае будет
	* искаться вхождение </li> <li>STATUS - ID статуса обращения (0 - без статуса)
	* </li> <li>STATUS_SID - символьный код статуса обращения (допускается
	* сложная логика) </li> <li>STATUS_SID_EXACT_MATCH - "Y" - при фильтрации по
	* символьному коду статуса будет искаться точное совпадение (по
	* умолчанию); "N" - в противном случае будет искаться вхождение </li>
	* <li>STATUS_SID_EXACT_MATCH - "Y" - при фильтрации по символьному коду оценки
	* ответа будет искаться точное совпадение (по умолчанию); "N" - в
	* противном случае будет искаться вхождение </li> <li>MARK - ID оценки
	* ответов (0 - без оценки) </li> <li>MARK_SID - символьный код оценки ответов
	* (допускается сложная логика) </li> <li>SOURCE - ID источника (0 - источник
	* "web") </li> <li>SOURCE_SID - символьный код источника (допускается сложная
	* логика) </li> <li>SOURCE_SID_EXACT_MATCH - "Y" - при фильтрации по символьному коду
	* источника будет искаться точное совпадение (по умолчанию); "N" - в
	* противном случае будет искаться вхождение </li> <li>MESSAGE - текст
	* сообщения (допускается сложная логика) </li> <li>MESSAGE_EXACT_MATCH - "Y" - при
	* фильтрации по сообщению будет искаться точное совпадение (по
	* умолчанию); "N" - в противном случае будет искаться вхождение </li>
	* <li>LAST_MESSAGE_USER_ID - ID пользователя написавшего последнее сообщение в
	* обращении (допускается сложная логика) </li> <li>LAST_MESSAGE_USER_ID_EXACT_MATCH - "Y"
	* - при фильтрации по ID пользователя написавшего последнее
	* сообщение в обращении будет искаться точное совпадение (по
	* умолчанию); "N" - в противном случае будет искаться вхождение </li>
	* <li>LAST_MESSAGE_SID - символьный идентификатор написавшего последнее
	* сообщение в обращении (если источник "email", то это может быть email,
	* если источник "телефон", то это может быть номер телефона)
	* (допускается сложная логика) </li> <li>LAST_MESSAGE_SID_EXACT_MATCH - "Y" - при
	* фильтрации по символьному идентификатору пользователя
	* написавшего последнее сообщение в обращении будет искаться
	* точное совпадение (по умолчанию); "N" - в противном случае будет
	* искаться вхождение </li> <li>SUPPORT_COMMENTS - комментарий техподдержки,
	* видимый в стандартных формах только для пользователей входящих в
	* группу техподдержки </li> <li>SUPPORT_COMMENTS_EXACT_MATCH - "Y" - при фильтрации по ID
	* пользователя написавшего последнее сообщение в обращении будет
	* искаться точное совпадение (по умолчанию); "N" - в противном случае
	* будет искаться вхождение </li> <li>SITE_ID - идентификатор сайта</li> <li>CLOSE
	* - Y\N - закрыт тикет, или нет</li> <li>S_SPAM - Y\N - флаг, является ли тикет
	* спамом</li> <li>IS_OVERDUE - Y\N - флаг, просрочен ли тикет.</li> <li>SLA_ID - по ID
	* уровня техподдержки (допускается сложная логика).</li>
	* <li>SUPPORTTEAM_GROUP_ID - по ID группы техподдержки (число или массив
	* чисел).</li> <li>CLIENT_GROUP_ID - Y\N - по ID группы клиентов (число или массив
	* чисел).</li> </ul>
	*
	* @param boolean &$is_filtered  Переменная возвращающая true в том случае если список результатов
	* отфильтрован по какому либо критерию; либо false в противном случае.
	* Изменен на <b>isFiltered</b> c 12.0.0
	*
	* @param char(1) $CHECK_RIGHTS = "Y" Необязательный параметр. "Y" - будут выбраны только те обращения
	* которые текущий пользователь может просматривать (по умолчанию);
	* "N" - выбирать все обращения независимо от прав текущего
	* пользователя. Изменен на <b>checkRights</b> c 12.0.0
	*
	* @param char(1) $get_user_name = "Y" Необязательный параметр. "Y" - при выборке обращений будут также
	* выбраны такие поля как OWNER_LOGIN, OWNER_NAME, RESPONSIBLE_LOGIN, RESPONSIBLE_NAME, MODIFIED_LOGIN,
	* MODIFIED_NAME, LAST_MESSAGE_LOGIN, LAST_MESSAGE_NAME, CREATED_LOGIN, CREATED_EMAIL, CREATED_NAME, описывающие
	* параметры пользователей имевших отношение к данному обращению
	* (по умолчанию); "N" - вышеперечисленные поля не будут выбраны, но
	* зато это ускорит работу метода. Изменен на <b>getUserName</b> c 12.0.0
	*
	* @param char(1) $get_dictionary_name = "Y" Необязательный параметр. "Y" - при выборке обращений будут также
	* выбраны такие поля как CATEGORY_NAME, CATEGORY_SID, CRITICALITY_NAME, CRITICALITY_SID, STATUS_NAME,
	* STATUS_SID, MARK_NAME, MARK_SID, SOURCE_NAME, SOURCE_SID, описывающие поля из справочника
	* техподдержки (по умолчанию); "N" - вышеперечисленные поля не будут
	* выбраны, но зато это ускорит работу метода. Удален с 4.0.6
	*
	* @param char(2) $lang = LANG Двухсимвольный код языка в формате которого необходимо выбрать
	* временные параметры обращения (время создания, изменения,
	* закрытия); необязательный параметр, по умолчанию - код текущего
	* сайта. Удален с 4.0.0
	*
	* @return record 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>$FilterArr = Array(<br>	"find_id",<br>	"find_lid",<br>	"find_lamp",<br>	"find_date_create1",<br>	"find_date_create2",<br>	"find_date_timestamp1",<br>	"find_date_timestamp2",<br>	"find_date_close1",<br>	"find_date_close2",<br>	"find_close",<br>	"find_ticket_time_1",<br>	"find_ticket_time_2",<br>	"find_title",<br>	"find_messages1",<br>	"find_messages2",<br>	"find_owner",<br>	"find_created_by",<br>	"find_responsible",<br>	"find_responsible_id",<br>	"find_category_id",<br>	"find_criticality_id",<br>	"find_status_id",<br>	"find_mark_id",<br>	"find_source_id",<br>	"find_modified_by",<br>	"find_message"<br>	);<br>if (strlen($set_filter)&gt;0) InitFilterEx($FilterArr,"TICKET_LIST","set",true); else InitFilterEx($FilterArr,"TICKET_LIST","get",true);<br>if (strlen($del_filter)&gt;0) DelFilterEx($FilterArr,"TICKET_LIST",true);<br>if (CheckFilter())<br>{<br>	$arFilter = Array(<br>		"ID"                =&gt; $find_id,<br>		"LID"               =&gt; $find_lid,<br>		"LAMP"              =&gt; $find_lamp,<br>		"DATE_CREATE_1"     =&gt; $find_date_create1,<br>		"DATE_CREATE_2"     =&gt; $find_date_create2,<br>		"DATE_TIMESTAMP_1"  =&gt; $find_date_timestamp1,<br>		"DATE_TIMESTAMP_2"  =&gt; $find_date_timestamp2,<br>		"DATE_CLOSE_1"      =&gt; $find_date_close1,<br>		"DATE_CLOSE_2"      =&gt; $find_date_close2,<br>		"CLOSE"             =&gt; $find_close,<br>		"TICKET_TIME_1"     =&gt; $find_ticket_time_1,<br>		"TICKET_TIME_2"     =&gt; $find_ticket_time_2,<br>		"TITLE"             =&gt; $find_title,<br>		"MESSAGES1"         =&gt; $find_messages1,<br>		"MESSAGES2"         =&gt; $find_messages2,<br>		"OWNER"             =&gt; $find_owner,<br>		"CREATED_BY"        =&gt; $find_created_by,<br>		"RESPONSIBLE"       =&gt; $find_responsible,<br>		"RESPONSIBLE_ID"    =&gt; $find_responsible_id,<br>		"CATEGORY"          =&gt; $find_category_id,<br>		"CRITICALITY"       =&gt; $find_criticality_id,<br>		"STATUS"            =&gt; $find_status_id,<br>		"MARK"              =&gt; $find_mark_id,<br>		"SOURCE"            =&gt; $find_source_id,<br>		"MODIFIED_BY"       =&gt; $find_modified_by,<br>		"MESSAGE"           =&gt; $find_message<br>		);<br>}<br>$tickets = <b>CTicket::GetList</b>($by, $order, $arFilter, $is_filtered);<br>?&gt;<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/support/classes/cticket/getlist.php
	* @author Bitrix
	*/
	public static 	function GetList(&$by, &$order, $arFilter=Array(), &$isFiltered, $checkRights="Y", $getUserName="Y", $getExtraNames="Y", $siteID=false, $arParams = Array() )
	{
		$err_mess = (CTicket::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB, $USER, $USER_FIELD_MANAGER;

		/** @var string $d_join Dictionary join */
		$d_join = "";

		$bAdmin = 'N';
		$bSupportTeam = 'N';
		$bSupportClient = 'N';
		$bDemo = 'N';

		/** @var string $messJoin Messages join */
		$messJoin = "";

		/** @var string $searchJoin Search table join */
		$searchJoin = '';

		$need_group = false;

		$arSqlHaving = array();

		if ($checkRights=='Y')
		{
			$bAdmin = (CTicket::IsAdmin()) ? 'Y' : 'N';
			$bSupportTeam = (CTicket::IsSupportTeam()) ? 'Y' : 'N';
			$bSupportClient = (CTicket::IsSupportClient()) ? 'Y' : 'N';
			$bDemo = (CTicket::IsDemo()) ? 'Y' : 'N';
			$uid = intval($USER->GetID());
		}
		else
		{
			$bAdmin = 'Y';
			$bSupportTeam = 'Y';
			$bSupportClient = 'Y';
			$bDemo = 'Y';
			if (is_object($USER)) $uid = intval($USER->GetID()); else $uid = -1;
		}
		if ($bAdmin!='Y' && $bSupportTeam!='Y' && $bSupportClient!='Y' && $bDemo!='Y') return false;

		if ($bSupportTeam=='Y' || $bAdmin=='Y' || $bDemo=='Y')
		{
			$lamp = "
				if(ifnull(T.DATE_CLOSE,'x')<>'x', 'grey',
					if(ifnull(T.LAST_MESSAGE_USER_ID,0)='$uid', 'green',
						if(ifnull(T.OWNER_USER_ID,0)='$uid', 'red',
							if(T.LAST_MESSAGE_BY_SUPPORT_TEAM='Y','green_s',
								if(ifnull(T.RESPONSIBLE_USER_ID,0)='$uid', 'red',
									'yellow')))))
				";
		}
		else
		{
			$lamp = "
				if(ifnull(T.DATE_CLOSE,'x')<>'x', 'grey',
					if(T.LAST_MESSAGE_BY_SUPPORT_TEAM='Y', 'red', 'green'))
				";
		}
		$bJoinSupportTeamTbl = $bJoinClientTbl = false;

		$arSqlSearch = Array();
		$strSqlSearch = "";

		if (is_array($arFilter))
		{
			$filterKeys = array_keys($arFilter);
			$filterKeysCount = count($filterKeys);
			for ($i=0; $i<$filterKeysCount; $i++)
			{
				$key = $filterKeys[$i];
				$val = $arFilter[$filterKeys[$i]];
				if ((is_array($val) && count($val)<=0) || (!is_array($val) && (strlen($val)<=0 || $val==='NOT_REF')))
					continue;
				$matchValueSet = (in_array($key."_EXACT_MATCH", $filterKeys)) ? true : false;
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("T.ID",$val,$match);
						break;
					case "HOLD_ON":
						$arSqlSearch[] = ($val=="Y") ? "T.HOLD_ON='Y'" : "T.HOLD_ON = 'N'";
						break;

					case "LID":
					case "SITE":
					case "SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("T.SITE_ID",$val,$match);
						break;
					case "LAMP":
						if (is_array($val))
						{
							if (count($val)>0)
							{
								$str = "";
								foreach ($val as $value)
								{
									$str .= ", '".$DB->ForSQL($value)."'";
								}
								$str = TrimEx($str, ",");
								$arSqlSearch[] = " ".$lamp." in (".$str.")";
							}
						}
						elseif (strlen($val)>0)
						{
							$arSqlSearch[] = " ".$lamp." = '".$DB->ForSQL($val)."'";
						}
						break;
					case "DATE_CREATE_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "T.DATE_CREATE>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_CREATE_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "T.DATE_CREATE<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "DATE_TIMESTAMP_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "T.TIMESTAMP_X>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_TIMESTAMP_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "T.TIMESTAMP_X<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "DATE_CLOSE_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "T.DATE_CLOSE>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_CLOSE_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "T.DATE_CLOSE<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "CLOSE":
						$arSqlSearch[] = ($val=="Y") ? "T.DATE_CLOSE is not null" : "T.DATE_CLOSE is null";
						break;
					case "AUTO_CLOSE_DAYS1":
						$arSqlSearch[] = "T.AUTO_CLOSE_DAYS>='".intval($val)."'";
						break;
					case "AUTO_CLOSE_DAYS2":
						$arSqlSearch[] = "T.AUTO_CLOSE_DAYS<='".intval($val)."'";
						break;
					case "TICKET_TIME_1":
						$arSqlSearch[] = "UNIX_TIMESTAMP(T.DATE_CLOSE) - UNIX_TIMESTAMP(T.DATE_CREATE)>='".(intval($val)*86400)."'";
						break;
					case "TICKET_TIME_2":
						$arSqlSearch[] = "UNIX_TIMESTAMP(T.DATE_CLOSE) - UNIX_TIMESTAMP(T.DATE_CREATE)<='".(intval($val)*86400)."'";
						break;
					case "TITLE":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $matchValueSet) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("T.TITLE", $val, $match);
						break;
					case "MESSAGES1":
						$arSqlSearch[] = "T.MESSAGES>='".intval($val)."'";
						break;
					case "MESSAGES2":
						$arSqlSearch[] = "T.MESSAGES<='".intval($val)."'";
						break;

					case "PROBLEM_TIME1":
						$arSqlSearch[] = "T.PROBLEM_TIME>='".intval($val)."'";
						break;
					case "PROBLEM_TIME2":
						$arSqlSearch[] = "T.PROBLEM_TIME<='".intval($val)."'";
						break;

					case "OVERDUE_MESSAGES1":
						$arSqlSearch[] = "T.OVERDUE_MESSAGES>='".intval($val)."'";
						break;
					case "OVERDUE_MESSAGES2":
						$arSqlSearch[] = "T.OVERDUE_MESSAGES<='".intval($val)."'";
						break;
					case "AUTO_CLOSE_DAYS_LEFT1":
						$arSqlSearch[] = "CASE WHEN (UNIX_TIMESTAMP(T.DATE_CLOSE) IS NULL OR UNIX_TIMESTAMP(T.DATE_CLOSE) = 0) AND T.LAST_MESSAGE_BY_SUPPORT_TEAM = 'Y' THEN
							TO_DAYS(ADDDATE(T.LAST_MESSAGE_DATE, INTERVAL T.AUTO_CLOSE_DAYS DAY)) - TO_DAYS(now()) ELSE -1 END >='".intval($val)."'";
						break;
					case "AUTO_CLOSE_DAYS_LEFT2":
						$arSqlSearch[] = "CASE WHEN (UNIX_TIMESTAMP(T.DATE_CLOSE) IS NULL OR UNIX_TIMESTAMP(T.DATE_CLOSE) = 0) AND T.LAST_MESSAGE_BY_SUPPORT_TEAM = 'Y' THEN
							TO_DAYS(ADDDATE(T.LAST_MESSAGE_DATE, INTERVAL T.AUTO_CLOSE_DAYS DAY))-TO_DAYS(now()) ELSE 999 END <='".intval($val)."'";
						break;
					case "OWNER":
						$getUserName = "Y";
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $matchValueSet) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("UO.ID, UO.LOGIN, UO.LAST_NAME, UO.NAME", $val, $match, array("@", ".")); //T.OWNER_USER_ID,
						break;
					case "OWNER_USER_ID":
					case "OWNER_SID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("T.".$key, $val, $match);
						break;
					case "SLA_ID":
					case "SLA":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("T.SLA_ID", $val, $match);
						break;
					case "CREATED_BY":
						$getUserName = "Y";
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $matchValueSet) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("T.CREATED_USER_ID, UC.LOGIN, UC.LAST_NAME, UC.NAME, T.CREATED_MODULE_NAME", $val, $match);
						break;
					case "RESPONSIBLE":
						$getUserName = "Y";
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $matchValueSet) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("T.RESPONSIBLE_USER_ID, UR.LOGIN, UR.LAST_NAME, UR.NAME", $val, $match);
						break;
					case "RESPONSIBLE_ID":
						if (intval($val)>0) $arSqlSearch[] = "T.RESPONSIBLE_USER_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.RESPONSIBLE_USER_ID is null or T.RESPONSIBLE_USER_ID=0)";
						break;
					case "CATEGORY_ID":
					case "CATEGORY":
						if (intval($val)>0) $arSqlSearch[] = "T.CATEGORY_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.CATEGORY_ID is null or T.CATEGORY_ID=0)";
						break;
					case "CATEGORY_SID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("DC.SID", $val, $match);
						$d_join = "
			LEFT JOIN b_ticket_dictionary DC ON (DC.ID = T.CATEGORY_ID and DC.C_TYPE = 'C')";
						break;
					case "CRITICALITY_ID":
					case "CRITICALITY":
						if (intval($val)>0) $arSqlSearch[] = "T.CRITICALITY_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.CRITICALITY_ID is null or T.CRITICALITY_ID=0)";
						break;
					case "CRITICALITY_SID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("DK.SID", $val, $match);
						break;
					case "STATUS_ID":
					case "STATUS":
						if (intval($val)>0) $arSqlSearch[] = "T.STATUS_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.STATUS_ID is null or T.STATUS_ID=0)";
						break;
					case "STATUS_SID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("DS.SID", $val, $match);
						break;
					case "MARK_ID":
					case "MARK":
						if (intval($val)>0) $arSqlSearch[] = "T.MARK_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.MARK_ID is null or T.MARK_ID=0)";
						break;
					case "MARK_SID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("DM.SID", $val, $match);
						break;
					case "SOURCE_ID":
					case "SOURCE":
						if (intval($val)>0) $arSqlSearch[] = "T.SOURCE_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.SOURCE_ID is null or T.SOURCE_ID=0)";
						break;
					case "SOURCE_SID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("DSR.SID", $val, $match);
						break;

					case "DIFFICULTY_ID":
					case "DIFFICULTY":
						if (intval($val)>0) $arSqlSearch[] = "T.DIFFICULTY_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.DIFFICULTY_ID is null or T.DIFFICULTY_ID=0)";
						break;
					case "DIFFICULTY_SID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("DD.SID", $val, $match);
						break;



					case "MODIFIED_BY":
						$getUserName = "Y";
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $matchValueSet) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("T.MODIFIED_USER_ID, T.MODIFIED_MODULE_NAME, UM.LOGIN, UM.LAST_NAME, UM.NAME", $val, $match);
						break;
					case "MESSAGE":
						global $strError;
						if( strlen( $val ) <= 0 ) break;

						if(CSupportSearch::CheckModule() && CSupportSearch::isIndexExists())
						{
							// new indexed search
							$searchSqlParams = CSupportSearch::getSql($val);
							$searchOn = $searchSqlParams['WHERE'];
							$searchHaving = $searchSqlParams['HAVING'];

							if ($searchOn)
							{
								$searchJoin = 'INNER JOIN b_ticket_search TS ON TS.TICKET_ID = T.ID AND '.$searchOn;

								if (!empty($searchHaving))
								{
									// 2 or more search words
									$arSqlHaving[] = $searchHaving;
									$need_group = true;
								}
							}

						}
						else
						{
							if ($bSupportTeam=="Y" || $bAdmin=="Y" || $bDemo=="Y")
							{
								$messJoin = "INNER JOIN b_ticket_message M ON (M.TICKET_ID=T.ID)";
							}
							else
							{
								$messJoin = "INNER JOIN b_ticket_message M ON (M.TICKET_ID=T.ID and M.IS_HIDDEN='N' and M.IS_LOG='N')";
							}

							$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $matchValueSet) ? "N" : "Y";
							$f = new CFilterQuery("OR", "yes", $match, array(), "N", "Y", "N");
							$query = $f->GetQueryString( "T.TITLE,M.MESSAGE_SEARCH", $val );
							$error = $f->error;
							if (strlen(trim($error))>0)
							{
								$strError .= $error."<br>";
								$query = "0";
							}
							else $arSqlSearch[] = $query;
						}
						break;
					case "LAST_MESSAGE_USER_ID":
					case "LAST_MESSAGE_SID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("T.".$key, $val, $match);
						break;
					case "LAST_MESSAGE_BY_SUPPORT_TEAM":
						$arSqlSearch[] = "T.LAST_MESSAGE_BY_SUPPORT_TEAM= '".($val == 'Y' ? 'Y' : 'N')."'";
						break;
					case "SUPPORT_COMMENTS":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $matchValueSet) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("T.SUPPORT_COMMENTS", $val, $match);
						break;
					case "IS_SPAM":
						$arSqlSearch[] = ($val=="Y") ? "T.IS_SPAM ='Y'" : "(T.IS_SPAM = 'N' or T.IS_SPAM is null)";
						break;
					case "IS_OVERDUE":
						$arSqlSearch[] = ($val=="Y") ? "T.IS_OVERDUE ='Y'" : "(T.IS_OVERDUE = 'N' or T.IS_OVERDUE is null)";
						break;
					case "IS_SPAM_MAYBE":
						$arSqlSearch[] = ($val=="Y") ? "T.IS_SPAM='N'" : "(T.IS_SPAM='Y' or T.IS_SPAM is null)";
						break;

					case 'SUPPORTTEAM_GROUP_ID':
					case 'CLIENT_GROUP_ID':
						if ($key == 'SUPPORTTEAM_GROUP_ID')
						{
							$table = 'UGS';
							$bJoinSupportTeamTbl = true;
						}
						else
						{
							$table = 'UGC';
							$bJoinClientTbl = true;
						}
						if (is_array($val))
						{
							$val = array_map('intval', $val);
							$val = array_unique($val);
							$val = array_filter($val);
							if (count($val) > 0)
							{
								$arSqlSearch[] = '('.$table.'.GROUP_ID IS NOT NULL AND '.$table.'.GROUP_ID IN ('.implode(',', $val).'))';
							}
						}
						else
						{
							$val = intval($val);
							if ($val > 0)
							{
								$arSqlSearch[] = '('.$table.'.GROUP_ID IS NOT NULL AND '.$table.'.GROUP_ID=\''.$val.'\')';
							}
						}
						break;
					case 'COUPON':
						$match = ($matchValueSet && $arFilter[$key."_EXACT_MATCH"]!="Y") ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("T.".$key, $val, $match);
						break;
				}
			}
		}

		$obUserFieldsSql = new CUserTypeSQL;
		$obUserFieldsSql->SetEntity("SUPPORT", "T.ID");
		$obUserFieldsSql->SetSelect( $arParams["SELECT"] );
		$obUserFieldsSql->SetFilter( $arFilter );
		$obUserFieldsSql->SetOrder( array( $by => $order) );

		if ($by == "s_id")
		{
			$strSqlOrder = "ORDER BY T.ID";
		}
		elseif ($by == "s_last_message_date")
		{
			$strSqlOrder = "ORDER BY T.LAST_MESSAGE_DATE";
		}
		elseif ($by == "s_site_id" || $by == "s_lid")
		{
			$strSqlOrder = "ORDER BY T.SITE_ID";
		}
		elseif ($by == "s_lamp")
		{
			$strSqlOrder = "ORDER BY LAMP";
		}
		elseif ($by == "s_is_overdue")
		{
			$strSqlOrder = "ORDER BY T.IS_OVERDUE";
		}
		elseif ($by == "s_is_notified")
		{
			$strSqlOrder = "ORDER BY T.IS_NOTIFIED";
		}
		elseif ($by == "s_date_create")
		{
			$strSqlOrder = "ORDER BY T.DATE_CREATE";
		}
		elseif ($by == "s_timestamp" || $by == "s_timestamp_x")
		{
			$strSqlOrder = "ORDER BY T.TIMESTAMP_X";
		}
		elseif ($by == "s_date_close")
		{
			$strSqlOrder = "ORDER BY T.DATE_CLOSE";
		}
		elseif ($by == "s_owner")
		{
			$strSqlOrder = "ORDER BY T.OWNER_USER_ID";
		}
		elseif ($by == "s_modified_by")
		{
			$strSqlOrder = "ORDER BY T.MODIFIED_USER_ID";
		}
		elseif ($by == "s_title")
		{
			$strSqlOrder = "ORDER BY T.TITLE ";
		}
		elseif ($by == "s_responsible")
		{
			$strSqlOrder = "ORDER BY T.RESPONSIBLE_USER_ID";
		}
		elseif ($by == "s_messages")
		{
			$strSqlOrder = "ORDER BY T.MESSAGES";
		}
		elseif ($by == "s_category")
		{
			$strSqlOrder = "ORDER BY T.CATEGORY_ID";
		}
		elseif ($by == "s_criticality")
		{
			$strSqlOrder = "ORDER BY T.CRITICALITY_ID";
		}
		elseif ($by == "s_sla")
		{
			$strSqlOrder = "ORDER BY T.SLA_ID";
		}
		elseif ($by == "s_status")
		{
			$strSqlOrder = "ORDER BY T.STATUS_ID";
		}
		elseif ($by == "s_difficulty")
		{
			$strSqlOrder = "ORDER BY T.DIFFICULTY_ID";
		}
		elseif ($by == "s_problem_time")
		{
			$strSqlOrder = "ORDER BY T.PROBLEM_TIME";
		}
		elseif ($by == "s_mark")
		{
			$strSqlOrder = "ORDER BY T.MARK_ID";
		}
		elseif ($by == "s_online")
		{
			$strSqlOrder = "ORDER BY USERS_ONLINE";
		}
		elseif ($by == "s_support_comments")
		{
			$strSqlOrder = "ORDER BY T.SUPPORT_COMMENTS";
		}
		elseif ($by == "s_auto_close_days_left")
		{
			$strSqlOrder = "ORDER BY AUTO_CLOSE_DAYS_LEFT";
		}
		elseif ($by == 's_coupon')
		{
			$strSqlOrder = 'ORDER BY T.COUPON';
		}
		elseif ($by == 's_deadline')
		{
			$strSqlOrder = 'ORDER BY T.SUPPORT_DEADLINE';
		}
		elseif( $s = $obUserFieldsSql->GetOrder($by) )
		{
			$strSqlOrder = "ORDER BY ".strtoupper($s);
		}
		else
		{
			$by = "s_default";
			$strSqlOrder = "ORDER BY IS_SUPER_TICKET DESC, T.IS_OVERDUE DESC, T.IS_NOTIFIED DESC, T.LAST_MESSAGE_DATE";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$arSqlSearch[] = $obUserFieldsSql->GetFilter();

		if ($getUserName=="Y")
		{
			$u_select = "
				,
				UO.LOGIN													OWNER_LOGIN,
				UO.EMAIL													OWNER_EMAIL,
				concat(ifnull(UO.NAME,''),' ',ifnull(UO.LAST_NAME,''))		OWNER_NAME,
				UR.LOGIN													RESPONSIBLE_LOGIN,
				UR.EMAIL													RESPONSIBLE_EMAIL,
				concat(ifnull(UR.NAME,''),' ',ifnull(UR.LAST_NAME,''))		RESPONSIBLE_NAME,
				UM.LOGIN													MODIFIED_BY_LOGIN,
				UM.EMAIL													MODIFIED_BY_EMAIL,
				concat(ifnull(UM.NAME,''),' ',ifnull(UM.LAST_NAME,''))		MODIFIED_BY_NAME,
				UM.LOGIN													MODIFIED_LOGIN,
				UM.EMAIL													MODIFIED_EMAIL,
				concat(ifnull(UM.NAME,''),' ',ifnull(UM.LAST_NAME,''))		MODIFIED_NAME,
				UL.LOGIN													LAST_MESSAGE_LOGIN,
				UL.EMAIL													LAST_MESSAGE_EMAIL,
				concat(ifnull(UL.NAME,''),' ',ifnull(UL.LAST_NAME,''))		LAST_MESSAGE_NAME,
				UC.LOGIN													CREATED_LOGIN,
				UC.EMAIL													CREATED_EMAIL,
				concat(ifnull(UC.NAME,''),' ',ifnull(UC.LAST_NAME,''))		CREATED_NAME
			";
			$u_join = "
			LEFT JOIN b_user UO ON (UO.ID = T.OWNER_USER_ID)
			LEFT JOIN b_user UR ON (UR.ID = T.RESPONSIBLE_USER_ID)
			LEFT JOIN b_user UM ON (UM.ID = T.MODIFIED_USER_ID)
			LEFT JOIN b_user UL ON (UL.ID = T.LAST_MESSAGE_USER_ID)
			LEFT JOIN b_user UC ON (UC.ID = T.CREATED_USER_ID)
			";
		}
		if ($getExtraNames=="Y")
		{
			$d_select = "
				,
				DC.NAME														CATEGORY_NAME,
				DC.DESCR													CATEGORY_DESC,
				DC.SID														CATEGORY_SID,
				DK.NAME														CRITICALITY_NAME,
				DK.DESCR													CRITICALITY_DESC,
				DK.SID														CRITICALITY_SID,
				DS.NAME														STATUS_NAME,
				DS.DESCR													STATUS_DESC,
				DS.SID														STATUS_SID,
				DM.NAME													MARK_NAME,
				DM.DESCR													MARK_DESC,
				DM.SID														MARK_SID,
				DSR.NAME													SOURCE_NAME,
				DSR.DESCR													SOURCE_DESC,
				DSR.SID														SOURCE_SID,
				DD.NAME													DIFFICULTY_NAME,
				DD.DESCR													DIFFICULTY_DESC,
				DD.SID														DIFFICULTY_SID,
				SLA.NAME													SLA_NAME
			";
			$d_join = "
			LEFT JOIN b_ticket_dictionary DC ON (DC.ID = T.CATEGORY_ID and DC.C_TYPE = 'C')
			LEFT JOIN b_ticket_dictionary DK ON (DK.ID = T.CRITICALITY_ID and DK.C_TYPE = 'K')
			LEFT JOIN b_ticket_dictionary DS ON (DS.ID = T.STATUS_ID and DS.C_TYPE = 'S')
			LEFT JOIN b_ticket_dictionary DM ON (DM.ID = T.MARK_ID and DM.C_TYPE = 'M')
			LEFT JOIN b_ticket_dictionary DSR ON (DSR.ID = T.SOURCE_ID and DSR.C_TYPE = 'SR')
			LEFT JOIN b_ticket_dictionary DD ON (DD.ID = T.DIFFICULTY_ID and DD.C_TYPE = 'D')
			LEFT JOIN b_ticket_sla SLA ON (SLA.ID = T.SLA_ID)
			";
		}
		if (strlen($siteID)>0)
		{
			$dates_select = "
				".$DB->DateToCharFunction("T.DATE_CREATE","FULL",$siteID,true)."	DATE_CREATE,
				".$DB->DateToCharFunction("T.TIMESTAMP_X","FULL",$siteID,true)."	TIMESTAMP_X,
				".$DB->DateToCharFunction("T.LAST_MESSAGE_DATE","FULL",$siteID,true)."	LAST_MESSAGE_DATE,
				".$DB->DateToCharFunction("T.DATE_CLOSE","FULL",$siteID,true)."	DATE_CLOSE,
				".$DB->DateToCharFunction("T.DATE_CREATE","SHORT",$siteID,true)."	DATE_CREATE_SHORT,
				".$DB->DateToCharFunction("T.TIMESTAMP_X","SHORT",$siteID,true)."	TIMESTAMP_X_SHORT,
				".$DB->DateToCharFunction("T.DATE_CLOSE","SHORT",$siteID,true)."	DATE_CLOSE_SHORT,
				".$DB->DateToCharFunction("T.SUPPORT_DEADLINE","FULL",$siteID,true)."	SUPPORT_DEADLINE,
				CASE WHEN (UNIX_TIMESTAMP(T.DATE_CLOSE) IS NULL OR UNIX_TIMESTAMP(T.DATE_CLOSE) = 0) AND T.LAST_MESSAGE_BY_SUPPORT_TEAM = 'Y' THEN "
					.$DB->DateToCharFunction("ADDDATE(T.LAST_MESSAGE_DATE, INTERVAL T.AUTO_CLOSE_DAYS DAY)","FULL",$siteID,true)
				." ELSE NULL END AUTO_CLOSE_DATE
			";
		}
		else
		{
			$dates_select = "
				".$DB->DateToCharFunction("T.DATE_CREATE","FULL")."		DATE_CREATE,
				".$DB->DateToCharFunction("T.TIMESTAMP_X","FULL")."		TIMESTAMP_X,
				".$DB->DateToCharFunction("T.LAST_MESSAGE_DATE","FULL")."	LAST_MESSAGE_DATE,
				".$DB->DateToCharFunction("T.DATE_CLOSE","FULL")."		DATE_CLOSE,
				".$DB->DateToCharFunction("T.DATE_CREATE","SHORT")."	DATE_CREATE_SHORT,
				".$DB->DateToCharFunction("T.TIMESTAMP_X","SHORT")."	TIMESTAMP_X_SHORT,
				".$DB->DateToCharFunction("T.DATE_CLOSE","SHORT")."		DATE_CLOSE_SHORT,
				".$DB->DateToCharFunction("T.SUPPORT_DEADLINE","FULL")."	SUPPORT_DEADLINE,
				CASE WHEN (UNIX_TIMESTAMP(T.DATE_CLOSE) IS NULL OR UNIX_TIMESTAMP(T.DATE_CLOSE) = 0) AND T.LAST_MESSAGE_BY_SUPPORT_TEAM = 'Y' THEN "
					.$DB->DateToCharFunction("ADDDATE(T.LAST_MESSAGE_DATE, INTERVAL T.AUTO_CLOSE_DAYS DAY)","FULL")
				." ELSE NULL END AUTO_CLOSE_DATE
			";
		}

		$ugroupJoin = '';

		if ($bJoinSupportTeamTbl)
		{
			$ugroupJoin .= "
			LEFT JOIN b_ticket_user_ugroup UGS ON (UGS.USER_ID = T.RESPONSIBLE_USER_ID) ";
			$need_group = true;
		}

		if ($bJoinClientTbl)
		{
			$ugroupJoin .= "
			LEFT JOIN b_ticket_user_ugroup UGC ON (UGC.USER_ID = T.OWNER_USER_ID) ";
			$need_group = true;
		}

		// add permissions check
		if (!($bAdmin == 'Y' || $bDemo == 'Y'))
		{
			// a list of users who own or are responsible for tickets, which we can show to our current user
			$ticketUsers = array($uid);

			// check if user has groups
			$result = $DB->Query('SELECT GROUP_ID FROM b_ticket_user_ugroup WHERE USER_ID = '.$uid.' AND CAN_VIEW_GROUP_MESSAGES = \'Y\'');
			if ($result)
			{
				// collect members of these groups
				$uGroups = array();

				while ($row = $result->Fetch())
				{
					$uGroups[] = $row['GROUP_ID'];
				}

				if (!empty($uGroups))
				{
					$result = $DB->Query('SELECT USER_ID FROM b_ticket_user_ugroup WHERE GROUP_ID IN ('.join(',', $uGroups).')');
					if ($result)
					{
						while ($row = $result->Fetch())
						{
							$ticketUsers[] = $row['USER_ID'];
						}
					}
				}
			}

			// build sql
			$strSqlSearchUser = "";

			if($bSupportTeam == 'Y')
			{
				$strSqlSearchUser = 'T.RESPONSIBLE_USER_ID IN ('.join(',', $ticketUsers).')';
			}
			elseif ($bSupportClient == 'Y')
			{
				$strSqlSearchUser = 'T.OWNER_USER_ID IN ('.join(',', $ticketUsers).')';
			}

			$arSqlSearch[] = $strSqlSearchUser;
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$onlineInterval = intval(COption::GetOptionString("support", "ONLINE_INTERVAL"));

		$strSqlSelect = "
			SELECT
				T.*,
				T.SITE_ID,
				T.SITE_ID																			LID,
				$dates_select,
				UNIX_TIMESTAMP(T.DATE_CLOSE)-UNIX_TIMESTAMP(T.DATE_CREATE)							TICKET_TIME,
				CASE WHEN (UNIX_TIMESTAMP(T.DATE_CLOSE) IS NULL OR UNIX_TIMESTAMP(T.DATE_CLOSE) = 0) AND T.LAST_MESSAGE_BY_SUPPORT_TEAM = 'Y' THEN
					TO_DAYS(
						ADDDATE(
							T.LAST_MESSAGE_DATE, INTERVAL T.AUTO_CLOSE_DAYS DAY
						)
					) - TO_DAYS(now())
				ELSE -1 END AUTO_CLOSE_DAYS_LEFT,
				(SELECT COUNT(DISTINCT USER_ID) FROM b_ticket_online WHERE TICKET_ID = T.ID AND TIMESTAMP_X >= DATE_ADD(now(), INTERVAL - ".$onlineInterval." SECOND)) USERS_ONLINE,
				if(T.COUPON IS NOT NULL, 1, 0)														IS_SUPER_TICKET,
				$lamp																				LAMP
				$d_select
				$u_select
				" . $obUserFieldsSql->GetSelect();

		$strSqlFrom = "
			FROM
				b_ticket T
			$u_join
			$d_join
			$messJoin
			$searchJoin
			$ugroupJoin
				" . $obUserFieldsSql->GetJoin("T.ID");

		$strSqlWhere = "
			WHERE
			$strSqlSearch
		";

		$strSqlGroup = $need_group ? ' GROUP BY T.ID  ' : '';
		$strSqlHaving = $arSqlHaving ? ' HAVING ' . join(' AND ', $arSqlHaving) . ' ' : '';

		$strSql = $strSqlSelect . $strSqlFrom . $strSqlWhere . $strSqlGroup . $strSqlHaving . $strSqlOrder;

		if (is_array($arParams) && isset($arParams["NAV_PARAMS"]) && is_array($arParams["NAV_PARAMS"]))
		{
			$nTopCount = isset($arParams['NAV_PARAMS']['nTopCount']) ? intval($arParams['NAV_PARAMS']['nTopCount']) : 0;

			if($nTopCount > 0)
			{
				$strSql = $DB->TopSql($strSql, $nTopCount);
				$res = $DB->Query($strSql, false, $err_mess.__LINE__);
				$res->SetUserFields( $USER_FIELD_MANAGER->GetUserFields("SUPPORT") );
			}
			else
			{
				$cntSql = "SELECT COUNT(T.ID) as C " . $strSqlFrom . $strSqlWhere . $strSqlGroup . $strSqlHaving;

				if (!empty($strSqlGroup))
				{
					$cntSql = 'SELECT COUNT(1) AS C FROM ('.$cntSql.') tt';
				}

				$res_cnt = $DB->Query($cntSql);
				$res_cnt = $res_cnt->Fetch();
				$res = new CDBResult();
				$res->SetUserFields( $USER_FIELD_MANAGER->GetUserFields("SUPPORT") );
				$res->NavQuery($strSql, $res_cnt["C"], $arParams["NAV_PARAMS"]);
			}
		}
		else
		{
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			$res->SetUserFields( $USER_FIELD_MANAGER->GetUserFields("SUPPORT") );
		}

		$isFiltered = (IsFiltered($strSqlSearch));
		return $res;
	}

public static 	function GetSupportTeamList()
	{
		$err_mess = (CTicket::err_mess())."<br>Function: GetSupportTeamList<br>Line: ";
		global $DB;
		$arrGid = CTicket::GetSupportTeamGroups();
		$arrAid = CTicket::GetAdminGroups();
		if (count($arrGid)>0)
		{
			$gid = implode(",",$arrGid);
		}
		else
		{
			$gid = 0;
		}
		if (count($arrAid)>0)
		{
			$aid = implode(",",$arrAid);
		}
		else
		{
			$aid = 0;
		}
		$strSql = "
			SELECT DISTINCT
				U.ID as REFERENCE_ID,
				concat('[',U.ID,'] ',' (',U.LOGIN,') ',ifnull(U.NAME,''),' ',ifnull(U.LAST_NAME,'')) as REFERENCE,
				U.ACTIVE
			FROM
				b_user U,
				b_user_group G
			WHERE
				U.ID = G.USER_ID
			and G.GROUP_ID in ($gid, $aid)
			ORDER BY
				U.ID
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	/*function GetResponsibleList($user_id)
	{
		$err_mess = (CTicket::err_mess())."<br>Function: GetSupportTeamMailList<br>Line: ";
		global $DB;

		$strSql = "
			SELECT DISTINCT
				U.ID as ID,
				U.LOGIN as LOGIN,
				concat('[',U.ID,'] ',' (',U.LOGIN,') ',ifnull(U.NAME,''),' ',ifnull(U.LAST_NAME,'')) as NAME,
				U.EMAIL as EMAIL
			FROM
				b_user U,
				b_ticket_user_ugroup TUG,
				b_ticket_user_ugroup TUG2
			WHERE
				TUG.USER_ID = '".intval($user_id)."'
			and TUG2.GROUP_ID = TUG.GROUP_ID
			and U.ID = TUG2.USER_ID
			and TUG2.CAN_MAIL_GROUP_MESSAGES = 'Y'
			ORDER BY
				U.ID
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}*/


	/**
	* <p>Метод предназначен для получения списка сообщений в одном обращении.</p>
	*
	*
	* @param varchar &$by  
	*
	* @param varchar &$order  Идентификатор позволяющий задать имя поля для сортировки.
	* Допустимы следующие значения: <ul> <li>s_id - по ID </li> <li>s_number - по
	* порядковому номеру </li> </ul>
	*
	* @param array $arFilter = array() Порядок сортировки. Допустимы следующие значения: <ul> <li>desc - по
	* убыванию (значение по умолчанию) </li> <li>asc - по возрастанию </li> </ul>
	*
	* @param boolean &$is_filtered  Массив для фильтрации значений. В массиве допустимы следующие
	* индексы: <ul> <li>ID - ID сообщения (допускается сложная логика); </li>
	* <li>TICKET_ID - ID обращения; </li> <li>TICKET - (допускается сложная логика); </li>
	* <li>IS_MESSAGE - Позволяет получить список именно сообщений. В противном
	* случае в списке сообщений будет много мусора, например, сообщения
	* об изменении статуса. </li> <li>IS_HIDDEN - "Y" - будут выбраны только
	* скрытые сообщения; "N" - будут выбраны все не скрытые сообщения; </li>
	* <li>IS_LOG - "Y" - будут выбраны только лог-записи (записи изменений); "N" -
	* будут выбраны не лог-записи; </li> <li>IS_OVERDUE - "Y" - будут выбраны только
	* просроченные сообщения; "N" - будут выбраны не просроченные
	* сообщения; </li> <li>NOT_CHANGE_STATUS - "Y" - будут выбраны только неизменённые
	* сообщения; "N" - будут выбраны изменённые сообщения; </li>
	* <li>MESSAGE_BY_SUPPORT_TEAM - "Y" - будут выбраны сообщения от сотрудников
	* техподдержки; "N" - будут выбраны все сообщения; </li> <li>EXTERNAL_FIELD_1 -
	* (допускается сложная логика) </li> </ul> Необязательный параметр.
	*
	* @param char(1) $CHECK_RIGHTS = "Y" Переменная возвращающая true в том случае если список результатов
	* отфильтрован по какому либо критерию; либо false в противном случае.
	* Изменен на <b>isFiltered</b> с версии 12.0.0
	*
	* @param char(1) $get_user_name = "Y" Флаг необходимости проверки прав текущего пользователя: <ul> <li>Y -
	* будут выбраны только те сообщения которые данные пользователь
	* может просматривать (по умолчанию) </li> <li>N - выбирать все сообщения
	* независимо от прав текущего пользователя </li> </ul> Необязательный
	* параметр. Изменен на <b>checkRights</b> с версии 12.0.0
	*
	* @return record 
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $mess = <b>CTicket::GetMessageList</b>($a, $b, array("TICKET_ID" =&gt; $ID, "TICKET_ID_EXACT_MATCH" =&gt; "Y"), $c, $CHECK_RIGHTS);
	* $mess-&gt;NavStart(50);
	* $messages = $mess-&gt;SelectedRowsCount();
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/support/classes/cticket/getmessagelist.php
	* @author Bitrix
	*/
	public static 	function GetMessageList(&$by, &$order, $arFilter=Array(), &$isFiltered, $checkRights="Y", $getUserName="Y")
	{
		$err_mess = (CTicket::err_mess())."<br>Function: GetMessageList<br>Line: ";
		global $DB, $USER, $APPLICATION;

		$bAdmin = "N";
		$bSupportTeam = "N";
		$bSupportClient = "N";
		$bDemo = "N";
		if ($checkRights=="Y")
		{
			$bAdmin = (CTicket::IsAdmin()) ? "Y" : "N";
			$bSupportTeam = (CTicket::IsSupportTeam()) ? "Y" : "N";
			$bSupportClient = (CTicket::IsSupportClient()) ? "Y" : "N";
			$bDemo = (CTicket::IsDemo()) ? "Y" : "N";
			$uid = intval($USER->GetID());
		}
		else
		{
			$bAdmin = "Y";
			$bSupportTeam = "Y";
			$bSupportClient = "Y";
			$bDemo = "Y";
			$uid = 0;
		}
		if ($bAdmin!="Y" && $bSupportTeam!="Y" && $bSupportClient!="Y" && $bDemo!="Y") return false;

		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			$filterKeys = array_keys($arFilter);
			$filterKeysCount = count($filterKeys);
			for ($i=0; $i<$filterKeysCount; $i++)
			{
				$key = $filterKeys[$i];
				$val = $arFilter[$filterKeys[$i]];
				if ((is_array($val) && count($val)<=0) || (!is_array($val) && (strlen($val)<=0 || $val==='NOT_REF')))
					continue;
				$matchValueSet = (in_array($key."_EXACT_MATCH", $filterKeys)) ? true : false;
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("M.ID",$val,$match);
						break;
					case "TICKET_ID":
						$arSqlSearch[] = "M.TICKET_ID = ".intval($val);
						break;
					case "TICKET":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("M.TICKET_ID",$val,$match);
						break;
					case "IS_MESSAGE":
						$arSqlSearch[] = ($val=="Y") ? "(M.IS_HIDDEN = 'N' and M.IS_LOG='N' and M.IS_OVERDUE='N')" : "(M.IS_HIDDEN = 'Y' or M.IS_LOG='Y' or M.IS_OVERDUE='Y')";
						break;
					case "IS_HIDDEN":
					case "IS_LOG":
					case "IS_OVERDUE":
					case "NOT_CHANGE_STATUS":
					case "MESSAGE_BY_SUPPORT_TEAM":
						$arSqlSearch[] = ($val=="Y") ? "M.".$key."='Y'" : "M.".$key."='N'";
						break;
					case "EXTERNAL_FIELD_1":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $matchValueSet) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("M.EXTERNAL_FIELD_1", $val, $match);
						break;
				}
			}
		}
		if ($getUserName=="Y")
		{
			$u_select = "
				,
				UO.EMAIL												OWNER_EMAIL,
				UO.LOGIN												OWNER_LOGIN,
				concat(ifnull(UO.NAME,''),' ',ifnull(UO.LAST_NAME,''))	OWNER_NAME,
				UO.LOGIN												LOGIN,
				concat(ifnull(UO.NAME,''),' ',ifnull(UO.LAST_NAME,''))	NAME,
				UC.EMAIL												CREATED_EMAIL,
				UC.LOGIN												CREATED_LOGIN,
				concat(ifnull(UC.NAME,''),' ',ifnull(UC.LAST_NAME,''))	CREATED_NAME,
				UM.EMAIL												MODIFIED_EMAIL,
				UM.LOGIN												MODIFIED_LOGIN,
				concat(ifnull(UM.NAME,''),' ',ifnull(UM.LAST_NAME,''))	MODIFIED_NAME
				";
			$u_join = "
			LEFT JOIN b_user UO ON (UO.ID = M.OWNER_USER_ID)
			LEFT JOIN b_user UC ON (UC.ID = M.CREATED_USER_ID)
			LEFT JOIN b_user UM ON (UM.ID = M.MODIFIED_USER_ID)
			";
		}

		if ($bSupportTeam!="Y" && $bAdmin!="Y")
		{
			$arSqlSearch[] = "M.IS_HIDDEN='N'";
			$arSqlSearch[] = "M.IS_LOG='N'";
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

		if ($by == "s_id")			$strSqlOrder = "ORDER BY M.ID";
		elseif ($by == "s_number")	$strSqlOrder = "ORDER BY M.C_NUMBER";
		else
		{
			$by = "s_number";
			$strSqlOrder = "ORDER BY M.C_NUMBER";
		}
		if ($order=="desc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		else
		{
			$strSqlOrder .= " asc ";
			$order="asc";
		}

		$strSql = "
			SELECT
				M.*,
				T.SLA_ID,
				".$DB->DateToCharFunction("M.DATE_CREATE")."			DATE_CREATE,
				".$DB->DateToCharFunction("M.TIMESTAMP_X")."			TIMESTAMP_X,
				DS.NAME													SOURCE_NAME
				$u_select
			FROM
				b_ticket_message M
			INNER JOIN b_ticket T ON (T.ID = M.TICKET_ID)
			LEFT JOIN b_ticket_dictionary DS ON (DS.ID = M.SOURCE_ID)
			$u_join
			WHERE
				$strSqlSearch
			$strSqlOrder
			";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

public static 	function GetDynamicList(&$by, &$order, $arFilter=Array())
	{
		$err_mess = (CTicket::err_mess())."<br>Function: GetDynamicList<br>Line: ";
		global $DB;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			$filterKeys = array_keys($arFilter);
			$filterKeysCount = count($filterKeys);
			for ($i=0; $i<$filterKeysCount; $i++)
			{
				$key = $filterKeys[$i];
				$val = $arFilter[$filterKeys[$i]];
				if ((is_array($val) && count($val)<=0) || (!is_array($val) && (strlen($val)<=0 || $val==='NOT_REF')))
					continue;
				$matchValueSet = (in_array($key."_EXACT_MATCH", $filterKeys)) ? true : false;
				$key = strtoupper($key);
				switch($key)
				{
					case "DATE_CREATE_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "T.DATE_CREATE>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_CREATE_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "T.DATE_CREATE<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "RESPONSIBLE":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $matchValueSet) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("T.RESPONSIBLE_USER_ID, UR.LOGIN, UR.LAST_NAME, UR.NAME", $val, $match);
						break;
					case "RESPONSIBLE_ID":
						if (intval($val)>0) $arSqlSearch[] = "T.RESPONSIBLE_USER_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.RESPONSIBLE_USER_ID is null or T.RESPONSIBLE_USER_ID=0)";
						break;
					case "SLA_ID":
					case "SLA":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("T.SLA_ID", $val, $match);
						break;
					case "CATEGORY_ID":
					case "CATEGORY":
						if (intval($val)>0) $arSqlSearch[] = "T.CATEGORY_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.CATEGORY_ID is null or T.CATEGORY_ID=0)";
						break;
					case "CRITICALITY_ID":
					case "CRITICALITY":
						if (intval($val)>0) $arSqlSearch[] = "T.CRITICALITY_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.CRITICALITY_ID is null or T.CRITICALITY_ID=0)";
						break;
					case "STATUS_ID":
					case "STATUS":
						if (intval($val)>0) $arSqlSearch[] = "T.STATUS_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.STATUS_ID is null or T.STATUS_ID=0)";
						break;
					case "MARK_ID":
					case "MARK":
						if (intval($val)>0) $arSqlSearch[] = "T.MARK_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.MARK_ID is null or T.MARK_ID=0)";
						break;
					case "SOURCE_ID":
					case "SOURCE":
						if (intval($val)>0) $arSqlSearch[] = "T.SOURCE_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.SOURCE_ID is null or T.SOURCE_ID=0)";
						break;
					case "DIFFICULTY_ID":
					case "DIFFICULTY":
						if (intval($val)>0) $arSqlSearch[] = "T.DIFFICULTY_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.DIFFICULTY_ID is null or T.DIFFICULTY_ID=0)";
						break;
				}
			}
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		if ($by == "s_date_create") $strSqlOrder = "ORDER BY T.DATE_CREATE";
		else
		{
			$by = "s_date_create";
			$strSqlOrder = "ORDER BY T.DATE_CREATE";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$strSql = "
			SELECT
				count(T.ID)							ALL_TICKETS,
				sum(if(T.DATE_CLOSE is null,1,0))	OPEN_TICKETS,
				sum(if(T.DATE_CLOSE is null,0,1))	CLOSE_TICKETS,
				DAYOFMONTH(T.DAY_CREATE)			CREATE_DAY,
				MONTH(T.DAY_CREATE)					CREATE_MONTH,
				YEAR(T.DAY_CREATE)					CREATE_YEAR
			FROM
				b_ticket T
			LEFT JOIN b_user UR ON (T.RESPONSIBLE_USER_ID = UR.ID)
			WHERE
			$strSqlSearch
			and	T.DAY_CREATE is not null
			GROUP BY
				TO_DAYS(T.DAY_CREATE)
			$strSqlOrder
			";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

public static 	function GetMessageDynamicList(&$by, &$order, $arFilter=Array())
	{
		$err_mess = (CTicket::err_mess())."<br>Function: GetMessageDynamicList<br>Line: ";
		global $DB;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			$filterKeys = array_keys($arFilter);
			$filterKeysCount = count($filterKeys);
			for ($i=0; $i<$filterKeysCount; $i++)
			{
				$key = $filterKeys[$i];
				$val = $arFilter[$filterKeys[$i]];
				if ((is_array($val) && count($val)<=0) || (!is_array($val) && (strlen($val)<=0 || $val==='NOT_REF')))
					continue;
				$matchValueSet = (in_array($key."_EXACT_MATCH", $filterKeys)) ? true : false;
				$key = strtoupper($key);
				switch($key)
				{
					case "SITE":
					case "SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("T.SITE_ID",$val,$match);
						break;
					case "DATE_CREATE_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "M.DATE_CREATE>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_CREATE_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "M.DATE_CREATE<".$DB->CharToDateFunction($val, "SHORT")." + INTERVAL 1 DAY";
						break;
					case "OWNER":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $matchValueSet) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("M.OWNER_USER_ID, U.LOGIN, U.LAST_NAME, U.NAME", $val, $match);
						break;
					case "OWNER_ID":
						if (intval($val)>0) $arSqlSearch[] = "M.OWNER_USER_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(M.OWNER_USER_ID is null or M.OWNER_USER_ID=0)";
						break;
					case "IS_HIDDEN":
					case "IS_LOG":
					case "IS_OVERDUE":
						$arSqlSearch[] = ($val=="Y") ? "M.".$key."='Y'" : "M.".$key."='N'";
						break;
					case "SLA_ID":
					case "SLA":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $matchValueSet) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("T.SLA_ID", $val, $match);
						break;
					case "CATEGORY_ID":
					case "CATEGORY":
						if (intval($val)>0) $arSqlSearch[] = "T.CATEGORY_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.CATEGORY_ID is null or T.CATEGORY_ID=0)";
						break;
					case "CRITICALITY_ID":
					case "CRITICALITY":
						if (intval($val)>0) $arSqlSearch[] = "T.CRITICALITY_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.CRITICALITY_ID is null or T.CRITICALITY_ID=0)";
						break;
					case "STATUS_ID":
					case "STATUS":
						if (intval($val)>0) $arSqlSearch[] = "T.STATUS_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.STATUS_ID is null or T.STATUS_ID=0)";
						break;
					case "MARK_ID":
					case "MARK":
						if (intval($val)>0) $arSqlSearch[] = "T.MARK_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.MARK_ID is null or T.MARK_ID=0)";
						break;
					case "SOURCE_ID":
					case "SOURCE":
						if (intval($val)>0) $arSqlSearch[] = "T.SOURCE_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.SOURCE_ID is null or T.SOURCE_ID=0)";
						break;
					case "DIFFICULTY_ID":
					case "DIFFICULTY":
						if (intval($val)>0) $arSqlSearch[] = "T.DIFFICULTY_ID = '".intval($val)."'";
						elseif ($val==0) $arSqlSearch[] = "(T.DIFFICULTY_ID is null or T.DIFFICULTY_ID=0)";
						break;
				}
			}
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		if ($by == "s_date_create") $strSqlOrder = "ORDER BY M.DATE_CREATE";
		else
		{
			$by = "s_date_create";
			$strSqlOrder = "ORDER BY M.DATE_CREATE";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$strSql = "
			SELECT
				count(M.ID)								COUNTER,
				sum(if(M.EXPIRE_AGENT_DONE='Y', 1, 0))	COUNTER_OVERDUE,
				DAYOFMONTH(M.DAY_CREATE)				CREATE_DAY,
				MONTH(M.DAY_CREATE)						CREATE_MONTH,
				YEAR(M.DAY_CREATE)						CREATE_YEAR
			FROM
				b_ticket_message M
			INNER JOIN b_ticket T ON (T.ID = M.TICKET_ID)
			LEFT JOIN b_user U ON (M.OWNER_USER_ID = U.ID)
			WHERE
			$strSqlSearch
			GROUP BY
				TO_DAYS(M.DAY_CREATE)
			$strSqlOrder
			";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}
}

?>