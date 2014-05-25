<?
IncludeModuleLangFile(__FILE__);


class CAllTicketReminder
{
		
	public static function err_mess()
	{
		$module_id = "support";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/install/version.php");
		return "<br>Module: ".$module_id." <br>Class: CAllTicketReminder<br>File: ".__FILE__;
	}
	

	public static function ConvertResponseTimeUnit($rt, $rtu)
	{
		switch($rtu)
		{
			case 'day': return intval($rt) * 1440;
			case 'hour': return intval($rt) * 60;
			case 'minute': return intval($rt);
		}
		return 0;
	}

	public static function RecalculateLastMessageDeadline($RSD = true)
	{
		global $DB, $DBType;
		$err_mess = (self::err_mess())."<br>Function: RecalculateLastMessage<br>Line: ";

		$DB->StartUsingMasterOnly();

		$strSql = "SELECT count(*) C FROM b_ticket";
		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		$resC = $rs->Fetch();
		if(!is_array($resC))
		{
			return true;
		}

		if(!isset($resC["C"]) || $resC["C"] == 0)
		{
			return true;
		}

		$strUsers = implode(",", CTicket::GetSupportTeamAndAdminUsers());
		$strSql0 = "
				b_ticket
				INNER JOIN (
					SELECT
						T.ID TID,
						M.DATE_CREATE DATE_CREATE,
						M.ID ID,
						" . CTicket::isnull("Q.LMBS", "'Y'") . " LMBS
					FROM
						b_ticket as T
						LEFT JOIN (
							SELECT
								TM.TICKET_ID ID,
								MIN(TM.ID) M_ID,
								'N' LMBS
							FROM
								b_ticket_message TM
								INNER JOIN (
									SELECT
										T.ID ID,
										MAX(" . CTicket::isnull("TM.ID", "0") . ") M_ID
									FROM
										b_ticket T
										LEFT JOIN b_ticket_message TM
											ON T.ID = TM.TICKET_ID
												AND (NOT(TM.IS_LOG='Y'))
												AND (NOT(TM.IS_HIDDEN='Y'))
												AND (NOT(TM.NOT_CHANGE_STATUS='Y'))
												AND TM.OWNER_USER_ID IN ($strUsers)
									WHERE
										T.DATE_CLOSE IS NULL
									GROUP BY
										T.ID
								) AS Q
									ON TM.TICKET_ID = Q.ID
										AND TM.ID > Q.M_ID
										AND (NOT(TM.IS_LOG='Y'))
										AND (NOT(TM.IS_HIDDEN='Y'))
										AND (NOT(TM.NOT_CHANGE_STATUS='Y'))
							GROUP BY
								TM.TICKET_ID
						) AS Q
							ON T.ID = Q.ID
						LEFT JOIN b_ticket_message AS M
							ON Q.M_ID = M.ID
					WHERE
						T.DATE_CLOSE IS NULL
				) AS M
					ON b_ticket.ID = M.TID
		";

		$arS = array(
			"MySQL" =>	"
	UPDATE $strSql0
	SET
	b_ticket.D_1_USER_M_AFTER_SUP_M = M.DATE_CREATE,
	b_ticket.ID_1_USER_M_AFTER_SUP_M = M.ID,
	b_ticket.LAST_MESSAGE_BY_SUPPORT_TEAM = M.LMBS
					",

			"MSSQL" =>	"
	UPDATE b_ticket
	SET
	b_ticket.D_1_USER_M_AFTER_SUP_M = M.DATE_CREATE,
	b_ticket.ID_1_USER_M_AFTER_SUP_M = M.ID,
	b_ticket.LAST_MESSAGE_BY_SUPPORT_TEAM = M.LMBS
	FROM $strSql0
					",




			"Oracle" =>	"
	UPDATE b_ticket T0
	SET (D_1_USER_M_AFTER_SUP_M, ID_1_USER_M_AFTER_SUP_M, LAST_MESSAGE_BY_SUPPORT_TEAM) = (
		SELECT
			M.DATE_CREATE,
			M.ID,
			M.LMBS
		FROM ".str_replace(" AS ", " ", $strSql0)."
		WHERE b_ticket.ID = T0.ID
	)
					",
		);

		$res = $DB->Query($arS[$DBType], true);

		$res = $DB->Query("UPDATE b_ticket SET SUPPORT_DEADLINE = null, SUPPORT_DEADLINE_NOTIFY = null WHERE LAST_MESSAGE_BY_SUPPORT_TEAM = 'Y'", true);

		if($RSD)
		{
			self::RecalculateSupportDeadline();
		}

		$DB->StopUsingMasterOnly();

	}


	public static function RecalculateSupportDeadline($arFilter = array())
	{
		global $DB;
		$err_mess = (CAllTicketReminder::err_mess())."<br>Function: RecalculateSupportDeadline<br>Line: ";	
		
		$arSqlSearch = Array();
		if(!is_array($arFilter)) $arFilter = array();
		foreach($arFilter as $key => $val)
		{
			if((is_array($val) && count($val) <= 0) || (!is_array($val) && strlen($val) <= 0)) continue;
			$key = strtoupper($key);
			if (is_array($val)) $val = implode(" | ",$val);
			switch($key)
			{
				case "ID":
					$arSqlSearch[] = GetFilterQuery("T.ID", $val, "N");
					break;
				case "SLA_ID":
					$arSqlSearch[] = GetFilterQuery("T.SLA_ID", $val, "N");
					break;
			}
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		
		$strSql = "
			SELECT
				T.ID ID,
				T.SLA_ID,
				" . $DB->DateToCharFunction("T.DEADLINE_SOURCE_DATE", "FULL") . " DEADLINE_SOURCE_DATE,
				" . $DB->DateToCharFunction("T.D_1_USER_M_AFTER_SUP_M", "FULL") . " D_1_USER_M_AFTER_SUP_M,
				T.IS_OVERDUE,
				SLA.RESPONSE_TIME_UNIT,
				SLA.RESPONSE_TIME,
				SLA.NOTICE_TIME_UNIT,
				SLA.NOTICE_TIME
			FROM
				b_ticket T
				INNER JOIN b_ticket_sla SLA
					ON T.SLA_ID = SLA.ID
						AND T.LAST_MESSAGE_BY_SUPPORT_TEAM = 'N'
						AND (T.DATE_CLOSE IS NULL)
			WHERE
				$strSqlSearch
		";
		$rsTicket = $DB->Query($strSql, false, $err_mess . __LINE__);
		while($arTicket = $rsTicket->Fetch())
		{
			self::RecalculateSupportDeadlineForOneTicket($arTicket);
		}
	}
	
	/*$arTicket = ID,SLA_ID,RESPONSE_TIME, D_1_USER_M_AFTER_SUP_M, RESPONSE_TIME_UNIT, NOTICE_TIME, NOTICE_TIME_UNIT,DEADLINE_SOURCE_DATE
	$dateType = CTicket::ADD, CTicket::UPDATE CTicket::DELETE, CTicket::IGNORE, CTicket::REOPEN, CTicket::NEW_SLA*/
	public static function RecalculateSupportDeadlineForOneTicket($arTicket, $arFields = array(), $dateType = array("EVENT"=>array(CTicket::IGNORE)))
	{
		global $DB;
		$err_mess = (CAllTicketReminder::err_mess())."<br>Function: RecalculateSupportDeadlineForOneTicket<br>Line: ";	
		$currDateTS = time() + CTimeZone::GetOffset();
		$ts2010 = mktime(0, 0, 0, 1, 1, 2010);
		$supportDeadlineNotify = 0;
		$ticketID = intval($arTicket["ID"]);
		$slaID = intval($arTicket["SLA_ID"]);
		$periodMin = self::ConvertResponseTimeUnit($arTicket["RESPONSE_TIME"], $arTicket["RESPONSE_TIME_UNIT"]);
		$periodNMin = 0;
		if($ticketID <= 0 || $slaID <= 0 || $periodMin <= 0 || intval($arTicket["D_1_USER_M_AFTER_SUP_M"]) <= 0)
		{
			if($ticketID > 0 && count($arFields) > 0)
			{
				$DB->Update("b_ticket", $arFields, "WHERE ID='" . $ticketID . "'", $err_mess . __LINE__);
			}
			return;
		}
		
		$periodNMinMinus = self::ConvertResponseTimeUnit($arTicket["NOTICE_TIME"], $arTicket["NOTICE_TIME_UNIT"]);
		if($periodNMinMinus > 0 && $periodNMinMinus < $periodMin) 
		{
			$periodNMin = $periodMin - $periodNMinMinus;
		}
		
		$newDate1UserMessAfterSupMessTS = MakeTimeStamp($arTicket["D_1_USER_M_AFTER_SUP_M"]);
		$deadlineSourceDate = MakeTimeStamp($arTicket["DEADLINE_SOURCE_DATE"]);
		if($deadlineSourceDate <= $ts2010)
		{
			if($newDate1UserMessAfterSupMessTS <= $ts2010)
			{
				$deadlineSourceDate = $currDateTS;
			}
			else
			{
				$deadlineSourceDate = $newDate1UserMessAfterSupMessTS;
			}
			$arFields["DEADLINE_SOURCE_DATE"] = $DB->CharToDateFunction(GetTime($deadlineSourceDate,"FULL"));
		}
		$oldPeriodMin = null;
		if(isset($dateType["EVENT"]) && in_array(CTicket::UPDATE, $dateType["EVENT"]))
		{
			if(isset($dateType["OLD_SLA_RESPONSE_TIME"]) && isset($dateType["OLD_SLA_RESPONSE_TIME_UNIT"]))
			{
				$oldPeriodMin = self::ConvertResponseTimeUnit($dateType["OLD_SLA_RESPONSE_TIME"], $dateType["OLD_SLA_RESPONSE_TIME_UNIT"]);
			}

			if(
				in_array(CTicket::REOPEN, $dateType["EVENT"]) ||
				(
					in_array(CTicket::NEW_SLA, $dateType["EVENT"]) &&
					($arTicket["IS_OVERDUE"] != "Y") &&
					($oldPeriodMin != null) &&
					($oldPeriodMin > $periodMin)
				)
			)
			{
				$deadlineSourceDate = $currDateTS;
				$arFields["DEADLINE_SOURCE_DATE"] = $DB->CharToDateFunction(GetTime($deadlineSourceDate,"FULL"));
			}
			elseif(($arTicket["IS_OVERDUE"] != "Y") && ($newDate1UserMessAfterSupMessTS > $deadlineSourceDate))
			{
				$sla = CTicketSLA::getById($slaID)->Fetch();

				if (empty($sla['DEADLINE_SOURCE']))
				{
					// default deadline calculation
					// date of first client message after support message
					$deadlineSourceDate = $newDate1UserMessAfterSupMessTS;
					$arFields["DEADLINE_SOURCE_DATE"] = $arFields["D_1_USER_M_AFTER_SUP_M"];
				}
			}
		}

		$supportDeadlineTS = CSupportTimetableCache::getEndDate($slaID, $periodMin, GetTime($deadlineSourceDate,"FULL"));
		$arFields["SUPPORT_DEADLINE"] = $DB->CharToDateFunction(GetTime($supportDeadlineTS, "FULL"));
		$arFields["IS_OVERDUE"] = (($supportDeadlineTS <= $currDateTS) ? "'Y'" : "'N'");

		// exec event and confirm if overdue
		if ($arTicket['IS_OVERDUE'] == "N" && $arFields['IS_OVERDUE'] == "'Y'")
		{
			$rs = GetModuleEvents('support', 'OnBeforeTicketExpire');
			while ($arr = $rs->Fetch())
			{
				$arFields = ExecuteModuleEventEx($arr, array($ticketID, $arFields));

				if (!$arFields)
				{
					return false;
				}
			}
		}


		if($periodNMin > 0)
		{
			$supportDeadlineNotifyTS = CSupportTimetableCache::getEndDate($slaID, $periodNMin, GetTime($deadlineSourceDate,"FULL"));
			$arFields["SUPPORT_DEADLINE_NOTIFY"] = $DB->CharToDateFunction(GetTime($supportDeadlineNotifyTS, "FULL"));
			$arFields["IS_NOTIFIED"] = (($supportDeadlineNotifyTS <= $currDateTS) ? "'Y'" : "'N'");

			// exec event and confirm if set notified
			if ($arTicket['IS_NOTIFIED'] == "N" && $arFields['IS_NOTIFIED'] == "'Y'")
			{
				$rs = GetModuleEvents('support', 'OnBeforeTicketNotify');
				while ($arr = $rs->Fetch())
				{
					$arFields = ExecuteModuleEventEx($arr, array($ticketID, $arFields));

					if (!$arFields)
					{
						return false;
					}
				}
			}
		}

		$DB->Update("b_ticket", $arFields, "WHERE ID='" . $ticketID . "'", $err_mess . __LINE__);
	}


	public static function SupportDeadline($arrTicket)
	{

		global $MESS, $DB;

		$err_mess = (CAllTicketReminder::err_mess())."<br>Function: supportDeadline<br>Line: ";
		$rsSite = CSite::GetByID($arrTicket["SITE_ID"]);
		$arSite = $rsSite->Fetch();

		//$oldMess = $MESS;
		
		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/support/classes/general/messages.php", $arSite["LANGUAGE_ID"]);

		// update message params
		$arFields = array(
			"EXPIRE_AGENT_ID"		=> "null",
			"IS_OVERDUE"			=> "'Y'",
			"OVERDUE_MESSAGES"		=> "OVERDUE_MESSAGES + 1",
		);

		// execute event
		$rs = GetModuleEvents('support', 'OnBeforeTicketExpire');
		while ($arr = $rs->Fetch())
		{
			$arFields = ExecuteModuleEventEx($arr, array($arrTicket["ID"], $arFields));

			if (!$arFields)
			{
				return false;
			}
		}

		$DB->Update("b_ticket", $arFields, "WHERE ID='" . $arrTicket["ID"] . "'", $err_mess . __LINE__);

		// add message log
		$message = str_replace("#ID#", $arrTicket["TM_ID"], GetMessage("SUP_MESSAGE_OVERDUE_LOG"));
		$message = str_replace("#NUMBER#", $arrTicket["TM_C_NUMBER"], $message);
		$message .= "<br><li>" . htmlspecialcharsEx(str_replace("#VALUE#", $arrTicket["SLA_NAME"], GetMessage("SUP_SLA_LOG")));

		if(intval($arrTicket["RESPONSIBLE_USER_ID"]) > 0)
		{
			$rsUser = CUser::GetByID(intval($arrTicket["RESPONSIBLE_USER_ID"]));
			$arUser = $rsUser->Fetch();
			$responsibleText = "[" . $arUser["ID"] . "] (" . $arUser["LOGIN"] . ") " . $arUser["NAME"] . " " . $arUser["LAST_NAME"];
			$message .= "<li>".htmlspecialcharsEx(str_replace("#VALUE#", $responsibleText, GetMessage("SUP_RESPONSIBLE_LOG")));
		}

		$arFields = array(
			"IS_LOG"						=> "Y",
			"IS_OVERDUE"					=> "Y",
			"MESSAGE_CREATED_USER_ID"		=> "null",
			"MESSAGE_CREATED_MODULE_NAME"	=> "auto expiration",
			"MESSAGE_CREATED_GUEST_ID"		=> "null",
			"MESSAGE_SOURCE_ID"				=> "null",
			"MESSAGE"						=> $message
		);
		$v = null;
		$mid = CTicket::AddMessage($arrTicket['ID'], $arFields, $v, "N");

		//$MESS = $oldMess;

	}


	public static function SupportDeadlineNotify($arrTicket0)
	{
		//SUPPORT_DEADLINE_NOTIFY
		//SUPPORT_DEADLINE			= EXPIRATION_DATE
		//SUPPORT_DEADLINE_STMP		= EXPIRATION_DATE_STMP

		$err_mess = (CAllTicketReminder::err_mess())."<br>Function: SupportDeadlineNotify<br>Line: ";
		$rs = CTicket::GetByID($arrTicket0["ID"], false, "N");
		if(!($arTicket = $rs->Fetch())) return false;
		
		$rsMessage = CTicket::GetMessageByID(intval($arTicket["ID_1_USER_M_AFTER_SUP_M"]), "N", "N");
		if(!($arMessage = $rsMessage->Fetch()))
		{
			return false;
		}

		$arMessage["EXPIRATION_DATE"] = $arrTicket0["SUPPORT_DEADLINE"];
		$arMessage["EXPIRATION_DATE_STMP"] = MakeTimeStamp($arMessage["EXPIRATION_DATE"]);
		
		//$SUPPORT_DEADLINE_STMP = MakeTimeStamp($arrTicket0["SUPPORT_DEADLINE"]);
			
		$rsSite = CSite::GetByID($arTicket["SITE_ID"]);
		$arSite = $rsSite->Fetch();

		global $MESS, $DB;;
		//$oldMess = $MESS;
		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/support/classes/general/messages.php", $arSite["LANGUAGE_ID"]);

		$sourceName = strlen($arTicket["SOURCE_NAME"]) <= 0 ? "" : "[" . $arTicket["SOURCE_NAME"] . "] ";
		if(intval($arTicket["OWNER_USER_ID"]) > 0 || strlen(trim($arTicket["OWNER_LOGIN"])) > 0)
		{
			$ownerText = "[" . $arTicket["OWNER_USER_ID"] . "] (" . $arTicket["OWNER_LOGIN"] . ") " . $arTicket["OWNER_NAME"];
			//if(strlen(trim($OWNER_SID)) > 0 && $OWNER_SID != "null") $ownerText = " / " . $ownerText;
		}

		if(intval($arTicket["RESPONSIBLE_USER_ID"]) > 0)
		{
			$responsibleText = "[" . $arTicket["RESPONSIBLE_USER_ID"] . "] (" . $arTicket["RESPONSIBLE_LOGIN"] . ") " . $arTicket["RESPONSIBLE_NAME"];
			if(CTicket::IsSupportTeam($arTicket["RESPONSIBLE_USER_ID"]) || CTicket::IsAdmin($arTicket["RESPONSIBLE_USER_ID"]))
			{
				$responsibleText .= " " . GetMessage("SUP_TECHSUPPORT_HINT");
			}
		}

		$arAdminEMails = CTicket::GetAdminEmails();
		if(count($arAdminEMails) > 0) $support_admin_email = implode(",", $arAdminEMails);

		// prepare email to author
		$arrOwnerEMail = array($arTicket["OWNER_EMAIL"]);
		$arrEmails = explode(",", $arTicket["OWNER_SID"]);
		if(is_array($arrEmails) && count($arrEmails) > 0)
		{
			foreach($arrEmails as $email)
			{
				$email = trim($email);
				if(strlen($email) > 0)
				{
					preg_match_all("#[<\[\(](.*?)[>\]\)]#i" . BX_UTF_PCRE_MODIFIER, $email, $arr);
					if(is_array($arr[1]) && count($arr[1]) > 0)
					{
						foreach($arr[1] as $email)
						{
							$email = trim($email);
							if(strlen($email)>0 && !in_array($email, $arrOwnerEMail) && check_email($email))
							{
								$arrOwnerEMail[] = $email;
							}
						}
					}
					elseif(!in_array($email, $arrOwnerEMail) && check_email($email)) $arrOwnerEMail[] = $email;
				}
			}
		}
		TrimArr($arrOwnerEMail);
		$ownerEmail = implode(", ", $arrOwnerEMail);

		// prepare email to support
		$support_email = $arTicket["RESPONSIBLE_EMAIL"];
		if(strlen($support_email) <= 0)
			$support_email = $support_admin_email;
		if(strlen($support_email) <= 0)
			$support_email = COption::GetOptionString("main", "email_from","");

		$arr = explode(",", $support_email);
		$arr = array_unique($arr);
		$support_email = implode(",", $arr);
		if(is_array($arr) && count($arr)>0)
		{
			foreach($arr as $email) unset($arAdminEMails[$email]);
		}
		$support_admin_email = implode(",", $arAdminEMails);

		$createdModuleName = "";
		if($arTicket["CREATED_MODULE_NAME"] == "support" || !strlen($arTicket["CREATED_MODULE_NAME"]))
		{
			if(intval($arTicket["CREATED_USER_ID"]) > 0)
			{
				$createdText = "[" . $arTicket["CREATED_USER_ID"] . "] (" . $arTicket["CREATED_LOGIN"] . ") " . $arTicket["CREATED_NAME"];
				if(CTicket::IsSupportTeam($arTicket["CREATED_USER_ID"]) || CTicket::IsAdmin($arTicket["CREATED_USER_ID"]))
				{
					$createdText .= " " . GetMessage("SUP_TECHSUPPORT_HINT");
				}
			}
		}
		else $createdModuleName = "[".$arTicket["CREATED_MODULE_NAME"]."]";

		$MESSAGE = PrepareTxtForEmail($arMessage["MESSAGE"], $arSite["LANGUAGE_ID"], false, false);
		$remainedTime = $arMessage["EXPIRATION_DATE_STMP"] - time();
		if($remainedTime > 0)
		{
			$strRemainedTime = "";
			$hours = intval($remainedTime / 3600);
			if($hours > 0)
			{
				$strRemainedTime .= $hours . " " . GetMessage("SUP_HOUR") . " ";
				$remainedTime = $remainedTime - $hours*3600;
			}
			$strRemainedTime .= intval($remainedTime / 60) . " " . GetMessage("SUP_MIN") . " ";
			$strRemainedTime .= ($remainedTime % 60) . " " . GetMessage("SUP_SEC");
		}

		$arFields_notify = array(
			"ID"						=> $arTicket["ID"],
			"LANGUAGE_ID"				=> $arSite["LANGUAGE_ID"],
			"DATE_CREATE"				=> $arTicket["DATE_CREATE"],
			"TITLE"						=> $arTicket["TITLE"],
			"STATUS"					=> $arTicket["STATUS_NAME"],
			"CATEGORY"					=> $arTicket["CATEGORY_NAME"],
			"CRITICALITY"				=> $arTicket["CRITICALITY_NAME"],
			"DIFFICULTY"				=> $arTicket["DIFFICULTY_NAME"],
			"RATE"						=> $arTicket["MARK_NAME"],
			"SLA"						=> $arTicket["SLA_NAME"],
			"SOURCE"					=> $sourceName,
			"ADMIN_EDIT_URL"			=> "/bitrix/admin/ticket_edit.php",
			"EXPIRATION_DATE"			=> $arMessage["EXPIRATION_DATE"],
			"REMAINED_TIME"				=> $strRemainedTime,

			"OWNER_EMAIL"				=> TrimEx($ownerEmail,","),
			"OWNER_USER_ID"				=> $arTicket["OWNER_USER_ID"],
			"OWNER_USER_NAME"			=> $arTicket["OWNER_NAME"],
			"OWNER_USER_LOGIN"			=> $arTicket["OWNER_LOGIN"],
			"OWNER_USER_EMAIL"			=> $arTicket["OWNER_EMAIL"],
			"OWNER_TEXT"				=> $ownerText,
			"OWNER_SID"					=> $arTicket["OWNER_SID"],

			"SUPPORT_EMAIL"				=> TrimEx($support_email,","),
			"RESPONSIBLE_USER_ID"		=> $arTicket["RESPONSIBLE_USER_ID"],
			"RESPONSIBLE_USER_NAME"		=> $arTicket["RESPONSIBLE_NAME"],
			"RESPONSIBLE_USER_LOGIN"	=> $arTicket["RESPONSIBLE_LOGIN"],
			"RESPONSIBLE_USER_EMAIL"	=> $arTicket["RESPONSIBLE_EMAIL"],
			"RESPONSIBLE_TEXT"			=> $responsibleText,
			"SUPPORT_ADMIN_EMAIL"		=> TrimEx($support_admin_email,","),

			"CREATED_USER_ID"			=> $arTicket["CREATED_USER_ID"],
			"CREATED_USER_LOGIN"		=> $arTicket["CREATED_LOGIN"],
			"CREATED_USER_EMAIL"		=> $arTicket["CREATED_EMAIL"],
			"CREATED_USER_NAME"			=> $arTicket["CREATED_NAME"],
			"CREATED_MODULE_NAME"		=> $createdModuleName,
			"CREATED_TEXT"				=> $createdText,

			"MESSAGE_BODY"				=> $MESSAGE
		);

		//$MESS = $oldMess;

		$arFields = array("NOTIFY_AGENT_ID" => "null", "IS_NOTIFIED" => "'Y'");

		// execute event
		$rs = GetModuleEvents('support', 'OnBeforeTicketNotify');
		while ($arr = $rs->Fetch())
		{
			$arFields = ExecuteModuleEventEx($arr, array($arTicket["ID"], $arFields));

			if (!$arFields)
			{
				return false;
			}
		}

		// check value again and send notification
		if (isset($arFields['IS_NOTIFIED']) && $arFields['IS_NOTIFIED'] === "'Y'")
		{
			CEvent::Send("TICKET_OVERDUE_REMINDER", $arTicket["SITE_ID"], $arFields_notify);
		}

		// event for notification

		$DB->Update("b_ticket", $arFields, "WHERE ID='" . $arTicket["ID"] . "'", $err_mess . __LINE__);

		$arFields = array("NOTIFY_AGENT_DONE" => "'Y'");
		$DB->Update("b_ticket_message", $arFields, "WHERE ID='" . $arMessage["ID"] . "'", $err_mess . __LINE__);
	}

	public static function AgentFunction()
	{
		//IS_OVERDUE
		//IS_NOTIFIED
		//SUPPORT_DEADLINE
		//SUPPORT_DEADLINE_NOTIFY
		global $DB;
		$err_mess = (CAllTicketReminder::err_mess())."<br>Function: AgentFunction<br>Line: ";

		CTimeZone::Disable();
		$cyrrDateTime = $DB->CharToDateFunction(GetTime(time(), "FULL"));
		CTimeZone::Enable();
		
		$strSql = "
			SELECT
				T.ID ID,
				T.SITE_ID,
				" . $DB->DateToCharFunction("T.SUPPORT_DEADLINE_NOTIFY", "FULL") . " SUPPORT_DEADLINE_NOTIFY,
				" . $DB->DateToCharFunction("T.SUPPORT_DEADLINE", "FULL") . " SUPPORT_DEADLINE,
				T.ID_1_USER_M_AFTER_SUP_M	
			FROM
				b_ticket T					
			WHERE
				T.LAST_MESSAGE_BY_SUPPORT_TEAM = 'N'
				AND T.SUPPORT_DEADLINE_NOTIFY <= $cyrrDateTime
				AND T.SUPPORT_DEADLINE_NOTIFY IS NOT NULL
				AND T.ID_1_USER_M_AFTER_SUP_M > 0
				AND T.IS_OVERDUE = 'N'
				AND T.IS_NOTIFIED = 'N'
				AND T.DATE_CLOSE IS NULL
		";
		$rsTicket = $DB->Query($strSql, false, $err_mess . __LINE__);
		while($arrTicket = $rsTicket->Fetch())
		{
			self::SupportDeadlineNotify($arrTicket);
		}
		
		$strSql = "
			SELECT
				T.*,
				TM.ID TM_ID,
				TM.C_NUMBER TM_C_NUMBER,
				S.NAME SLA_NAME
			FROM
				b_ticket T
				LEFT JOIN b_ticket_sla S
					ON T.SLA_ID = S.ID
				LEFT JOIN b_ticket_message TM
					ON T.ID_1_USER_M_AFTER_SUP_M	= TM.ID	
			WHERE
				T.LAST_MESSAGE_BY_SUPPORT_TEAM = 'N'
				AND T.SUPPORT_DEADLINE <= $cyrrDateTime
				AND T.IS_OVERDUE = 'N'
				AND T.DATE_CLOSE IS NULL
		";
		$rsTicket = $DB->Query($strSql, false, $err_mess . __LINE__);
		
		while($arrTicket = $rsTicket->Fetch())
		{
			self::SupportDeadline($arrTicket);
		}
		return "CTicketReminder::AgentFunction();";
	}


	public static function StartAgent()
	{
		CAgent::RemoveModuleAgents("support");
		CAgent::AddAgent("CTicketReminder::AgentFunction();", "support", "N", 60);
		CAgent::AddAgent('CTicket::CleanUpOnline();', 'support', 'N');
		CAgent::AddAgent('CTicket::AutoClose();', 'support', 'N');
	}

}

?>