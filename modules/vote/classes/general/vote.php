<?
##############################################
# Bitrix Site Manager Forum					 #
# Copyright (c) 2002-2009 Bitrix			 #
# http://www.bitrixsoft.com					 #
# mailto:admin@bitrixsoft.com				 #
##############################################
IncludeModuleLangFile(__FILE__);

class CAllVote
{
	public static function err_mess()
	{
		$module_id = "vote";
		return "<br>Module: ".$module_id."<br>Class: CAllVote<br>File: ".__FILE__;
	}

	public static function GetFilterOperation($key)
	{
		return CGroup::GetFilterOperation($key);
	}

	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		$aMsg = array();
		$ID = intVal($ID);
		$arVote = array();
		if ($ID > 0):
			$db_res = CVote::GetByID($ID);
			if ($db_res && $res = $db_res->Fetch()):
				$arVote = $res;
			endif;
		endif;

		unset($arFields["ID"]);
		if (is_set($arFields, "CHANNEL_ID") || $ACTION == "ADD")
		{
			$arFields["CHANNEL_ID"] = intVal($arFields["CHANNEL_ID"]);
			if ($arFields["CHANNEL_ID"] <= 0):
				$aMsg[] = array(
					"id" => "CHANNEL_ID",
					"text" => GetMessage("VOTE_EMPTY_CHANNEL_ID"));
			else:
				$rChannel = CVoteChannel::GetList($by, $order, arraY('ID' => intval($arFields['CHANNEL_ID'])), $filtered);
				if (! ($rChannel && $arChannel = $rChannel->Fetch()))
				{
					$aMsg[] = array(
						"id" => "CHANNEL_ID",
						"text" => GetMessage("VOTE_WRONG_CHANNEL_ID"));
				}
			endif;
		}

		if (is_set($arFields, "C_SORT")) $arFields["C_SORT"] = intval($arFields["C_SORT"]);
		if (is_set($arFields, "ACTIVE") || $ACTION == "ADD") $arFields["ACTIVE"] = ($arFields["ACTIVE"] == "N" ? "N" : "Y");

		unset($arFields["TIMESTAMP_X"]);
		$date_start = false;
		if (is_set($arFields, "DATE_START") || $ACTION == "ADD")
		{
			$arFields["DATE_START"] = trim($arFields["DATE_START"]);
			$date_start = MakeTimeStamp($arFields["DATE_START"]);
			if (!$date_start):
				$aMsg[] = array(
					"id" => "DATE_START",
					"text" => GetMessage("VOTE_WRONG_DATE_START"));
			endif;
		}

		if (is_set($arFields, "DATE_END") || $ACTION == "ADD")
		{
			$arFields["DATE_END"] = trim($arFields["DATE_END"]);
			if (strlen($arFields["DATE_END"]) <= 0):
				if ($date_start != false):
					$date_end = $date_start + 2592000;
					$arFields["DATE_END"] = GetTime($date_end, "FULL");
				else:
					$date_end = 1924984799; // '31.12.2030 23:59:59'
					$arFields["DATE_END"] = GetTime($date_end, "FULL");
				endif;
			else:
				$date_end = MakeTimeStamp($arFields["DATE_END"]);
			endif;
			if (!$date_end):
				$aMsg[] = array(
					"id" => "DATE_END",
					"text" => GetMessage("VOTE_WRONG_DATE_END"));
			elseif ($date_start >= $date_end && !empty($arFields["DATE_START"])):
				$aMsg[] = array(
					"id" => "DATE_END",
					"text" => GetMessage("VOTE_WRONG_DATE_TILL"));
			endif;
		}
		if (empty($aMsg) && (is_set($arFields, "DATE_START") || is_set($arFields, "DATE_END") || is_set($arFields, "CHANNEL_ID") || is_set($arFields, "ACTIVE")))
		{
			$vid = 0;
			if ($ACTION == "ADD" && $arFields["ACTIVE"] == "Y")
			{
				$vid = CVote::WrongDateInterval(0, $arFields["DATE_START"], $arFields["DATE_END"], $arFields["CHANNEL_ID"]);
			}
			elseif ($ACTION != "ADD" && !(is_set($arFields, "ACTIVE") && $arFields["ACTIVE"] != "Y"))
			{
				$res = array(
					"DATE_START" => (is_set($arFields, "DATE_START") ? $arFields["DATE_START"] : false),
					"DATE_END" => (is_set($arFields, "DATE_END") ? $arFields["DATE_END"] : false),
					"CHANNEL_ID" => (is_set($arFields, "CHANNEL_ID") ? $arFields["CHANNEL_ID"] : false));
				$vid = CVote::WrongDateInterval($ID, $res["DATE_START"], $res["DATE_END"], $res["CHANNEL_ID"]);
			}
			if (intVal($vid) > 0):
				$aMsg[] = array(
					"id" => "DATE_START",
					"text" => str_replace("#ID#", $vid, GetMessage("VOTE_WRONG_INTERVAL")));
			endif;
		}
		if (is_set($arFields, "IMAGE_ID") && strLen($arFields["IMAGE_ID"]["name"]) <= 0 && strLen($arFields["IMAGE_ID"]["del"]) <= 0)
		{
			unset($arFields["IMAGE_ID"]);
		}
		elseif (is_set($arFields, "IMAGE_ID"))
		{
			if ($str = CFile::CheckImageFile($arFields["IMAGE_ID"])):
				$aMsg[] = array(
					"id" => "IMAGE_ID",
					"text" => $str);
			else:
				$arFields["IMAGE_ID"]["MODULE_ID"] = "vote";
				if (!empty($arVote)):
					$arFields["IMAGE_ID"]["old_file"] = $arVote["IMAGE_ID"];
				endif;
			endif;
		}

		if (is_set($arFields, "COUNTER")) $arFields["COUNTER"] = intVal($arFields["COUNTER"]);
		if (is_set($arFields, "TITLE")) $arFields["TITLE"] = trim($arFields["TITLE"]);
		if (is_set($arFields, "DESCRIPTION")) $arFields["DESCRIPTION"] = trim($arFields["DESCRIPTION"]);
		if (is_set($arFields, "DESCRIPTION_TYPE") || $ACTION == "ADD") $arFields["DESCRIPTION_TYPE"] = ($arFields["DESCRIPTION_TYPE"] == "html" ? "html" : "text");

		if (is_set($arFields, "EVENT1")) $arFields["EVENT1"] = trim($arFields["EVENT1"]);
		if (is_set($arFields, "EVENT2")) $arFields["EVENT2"] = trim($arFields["EVENT2"]);
		if (is_set($arFields, "EVENT3")) $arFields["EVENT3"] = trim($arFields["EVENT3"]);
		if (is_set($arFields, "UNIQUE_TYPE")) $arFields["UNIQUE_TYPE"] = intVal($arFields["UNIQUE_TYPE"]);

		if (is_set($arFields, "DELAY_TYPE") || $ACTION == "ADD")
		{
			$arFields["DELAY_TYPE"] = trim($arFields["DELAY_TYPE"]);
			$arFields["DELAY_TYPE"] = (in_array($arFields["DELAY_TYPE"], array("S", "M", "H", "D")) ? $arFields["DELAY_TYPE"] : "D");
		}
		if (is_set($arFields, "DELAY") || $ACTION == "ADD") $arFields["DELAY"] = intVal($arFields["DELAY"]);

		unset($arFields["KEEP_IP_SEC"]);
		$sec = 1;
		switch ($arFields["DELAY_TYPE"])
		{
			case "S": $sec = 1; break;
			case "M": $sec = 60; break;
			case "H": $sec = 3600; break;
			case "D": $sec = 86400; break;
		}
		$arFields["KEEP_IP_SEC"] = intval($arFields["DELAY"]) * $sec;

		if ((is_set($arFields, "UNIQUE_TYPE")) && ($arFields['UNIQUE_TYPE'] < 5))
		{
			switch($arFields['UNIQUE_TYPE'])
			{
				case 0:$arFields['UNIQUE_TYPE']=5;
					break;
				case 1:$arFields['UNIQUE_TYPE']=6;
					break;
				case 2:$arFields['UNIQUE_TYPE']=8;
					break;
				case 3:$arFields['UNIQUE_TYPE']=12;
					break;
				case 4:$arFields['UNIQUE_TYPE']=20;
					break;
			}
		}

		if (CVote::IsOldVersion() != "Y")
		{
			unset($arFields["TEMPLATE"]);
			unset($arFields["RESULT_TEMPLATE"]);
		}

		if (is_set($arFields, "TEMPLATE")) $arFields["TEMPLATE"] = trim($arFields["TEMPLATE"]);
		if (is_set($arFields, "RESULT_TEMPLATE")) $arFields["RESULT_TEMPLATE"] = trim($arFields["RESULT_TEMPLATE"]);
		if (is_set($arFields, "NOTIFY")) $arFields["NOTIFY"] = (in_array($arFields["NOTIFY"], array("Y", "N", "I")) ? $arFields["NOTIFY"] : "N");
		if (is_set($arFields, "REQUIRED")) $arFields["REQUIRED"] = ($arFields["REQUIRED"] == "Y" ? "Y" : "N");
		if (is_set($arFields, "AUTHOR_ID")) $arFields["AUTHOR_ID"] = intval($arFields["AUTHOR_ID"]);

		if(!empty($aMsg))
		{
			global $APPLICATION;
			$e = new CAdminException(array_reverse($aMsg));
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;
	}

	public static function Add($arFields, $strUploadDir = false)
	{
		global $DB;
		$arBinds = array();
		$strUploadDir = ($strUploadDir === false ? "vote" : $strUploadDir);

		if (!CVote::CheckFields("ADD", $arFields))
			return false;
/***************** Event onBeforeVoteAdd ***************************/
		foreach (GetModuleEvents("vote", "onBeforeVoteAdd", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;

		if (
			array_key_exists("IMAGE_ID", $arFields)
			&& is_array($arFields["IMAGE_ID"])
			&& (
				!array_key_exists("MODULE_ID", $arFields["IMAGE_ID"])
				|| strlen($arFields["IMAGE_ID"]["MODULE_ID"]) <= 0
			)
		)
			$arFields["IMAGE_ID"]["MODULE_ID"] = "vote";

		CFile::SaveForDB($arFields, "IMAGE_ID", $strUploadDir);

		$arFields["~TIMESTAMP_X"] = $DB->GetNowFunction();
		if (is_set($arFields, "DESCRIPTION"))
			$arBinds["DESCRIPTION"] = $arFields["DESCRIPTION"];

		$ID = $DB->Add("b_vote", $arFields, $arBinds);

/***************** Event onAfterVoteAdd ****************************/
		foreach (GetModuleEvents("vote", "onAfterVoteAdd", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
/***************** /Event ******************************************/
		return $ID;
	}

	public static function Update($ID, $arFields, $strUploadDir = false)
	{
		global $DB;
		$arBinds = array();
		$strUploadDir = ($strUploadDir === false ? "vote" : $strUploadDir);
		$ID = intVal($ID);

		if ($ID <= 0 || !CVote::CheckFields("UPDATE", $arFields, $ID))
			return false;

/***************** Event onBeforeVoteUpdate ************************/
		foreach (GetModuleEvents("vote", "onBeforeVoteUpdate", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array(&$ID, &$arFields)) === false)
				return false;
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;

		$arFields["~TIMESTAMP_X"] = $DB->GetNowFunction();
		if (is_set($arFields, "DESCRIPTION"))
			$arBinds["DESCRIPTION"] = $arFields["DESCRIPTION"];

		if (
			array_key_exists("IMAGE_ID", $arFields)
			&& is_array($arFields["IMAGE_ID"])
			&& (
				!array_key_exists("MODULE_ID", $arFields["IMAGE_ID"])
				|| strlen($arFields["IMAGE_ID"]["MODULE_ID"]) <= 0
			)
		)
			$arFields["IMAGE_ID"]["MODULE_ID"] = "vote";

		CFile::SaveForDB($arFields, "IMAGE_ID", $strUploadDir);

		$strUpdate = $DB->PrepareUpdateBind("b_vote", $arFields, $strUploadDir, false, $arBinds);

		if (!empty($strUpdate)):
			$strSql = "UPDATE b_vote SET ".$strUpdate." WHERE ID=".$ID;
			$DB->QueryBind($strSql, $arBinds);
		endif;
/***************** Event onAfterVoteUpdate *************************/
		foreach (GetModuleEvents("vote", "onAfterVoteUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
/***************** /Event ******************************************/
		return $ID;
	}

	public static function Delete($ID)
	{
		global $DB;
		$err_mess = (CVote::err_mess())."<br>Function: Delete<br>Line: ";
		$ID = intval($ID);
		if ($ID <= 0):
			return false;
		endif;

		/***************** Event onBeforeVoteDelete *************************/
		foreach (GetModuleEvents("vote", "onBeforeVoteDelete", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array(&$ID)) === false)
				return false;
		/***************** /Event ******************************************/

		@set_time_limit(1000);
		$DB->StartTransaction();

		// delete questions
		CVoteQuestion::Delete(false, $ID);
		// delete vote images
		$strSql = "SELECT IMAGE_ID FROM b_vote WHERE ID = ".$ID." AND IMAGE_ID > 0";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		while ($zr = $z->Fetch()) CFile::Delete($zr["IMAGE_ID"]);

		// delete vote events
		$DB->Query("DELETE FROM b_vote_event WHERE VOTE_ID='$ID'", false, $err_mess.__LINE__);
		// delete vote
		$res = $DB->Query("DELETE FROM b_vote WHERE ID='$ID'", false, $err_mess.__LINE__);
		$DB->Commit();
		/***************** Event onAfterVoteDelete *************************/
		foreach (GetModuleEvents("vote", "onAfterVoteDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));
		/***************** /Event ******************************************/
		return $res;
	}

	public static function Reset($ID)
	{
		global $DB;
		$err_mess = (CVote::err_mess())."<br>Function: Reset<br>Line: ";
		$ID = intval($ID);
		if ($ID <= 0):
			return false;
		endif;
		// zeroize questions
		CVoteQuestion::Reset(false, $ID);
		// zeroize events
		$DB->Query("DELETE FROM b_vote_event WHERE VOTE_ID='$ID'", false, $err_mess.__LINE__);
		// zeroize vote counter
		unset($GLOBALS["VOTE_CACHE_VOTING"][$ID]);
		$DB->Update("b_vote", array("COUNTER"=>"0"), "WHERE ID=".$ID, $err_mess.__LINE__);
		/***************** Event OnVoteReset *******************************/
		foreach (GetModuleEvents("vote", "onVoteReset", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));
		/***************** /Event ******************************************/
		return true;
	}

	public static function Copy($ID)
	{
		global $DB;
		$err_mess = (CVote::err_mess())."<br>Function: Copy<br>Line: ";
		$ID = intval($ID);
		if ($ID <= 0):
			return false;
		endif;
		$rCurrentVote = CVote::GetByID($ID);
		if (!$arCurrentVote = $rCurrentVote->Fetch())
			return false;
		unset($arCurrentVote["ID"]);
		$arCurrentVote['ACTIVE'] = "N";

		$newImageId = false;
		if (intval($arCurrentVote['IMAGE_ID'] > 0))
		{
			$imageId = $arCurrentVote['IMAGE_ID'];
			$newImageId = CFile::CopyFile($imageId);
			$arCurrentVote["IMAGE_ID"] = NULL;
		}
		$newID = CVote::Add($arCurrentVote);
		if ($newID === false)
			return false;
		$DB->Update("b_vote", array("COUNTER"=>"0"), "WHERE ID=".$newID, $err_mess.__LINE__);
		if ($newImageId)
		{
			$DB->Update("b_vote", array("IMAGE_ID"=>$newImageId), "WHERE ID=".$newID, $err_mess.__LINE__);
		}

		$state = true;
		$rQuestions = CVoteQuestion::GetList($ID, $by, $order, array(), $is_filtered);
		while ($arQuestion = $rQuestions->Fetch())
		{
			$state = $state && ( CVoteQuestion::Copy($arQuestion['ID'], $newID) !== false);
		}

		if ($state == true)
			return $newID;
		else return $state;
	}

	public static function IsOldVersion()
	{
		$res = "N";
		$arr = GetTemplateList("RV");
		if (is_array($arr) && count($arr["reference"])>0) $res = "Y";
		else
		{
			$arr = GetTemplateList("SV");
			if (is_array($arr) && count($arr["reference"])>0) $res = "Y";
			else
			{
				$arr = GetTemplateList("RQ");
				if (is_array($arr) && count($arr["reference"])>0) $res = "Y";
			}
		}
		return $res;
	}

	public static function GetByID($ID)
	{
		$ID = intval($ID);
		return CVote::GetList($by="s_id", $order="desc", array("ID" => $ID), $is_filtered = false);
	}

	public static function GetByIDEx($ID)
	{
		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		if (!isset($GLOBALS["VOTE_CACHE"]["VOTE"][$ID]))
		{
			global $CACHE_MANAGER;
			if (!!VOTE_CACHE_TIME && $CACHE_MANAGER->Read(VOTE_CACHE_TIME, $ID, "b_vote"))
			{
				$GLOBALS["VOTE_CACHE"]["VOTE"][$ID] = $CACHE_MANAGER->Get($ID);
			}
			else
			{
				$db_res = CVote::GetListEx(array("ID" => "ASC"),  array("ID" => $ID));
				if ($db_res && ($res = $db_res->Fetch()))
				{
					$GLOBALS["VOTE_CACHE"]["VOTE"][$ID] = $res;
					if (!!VOTE_CACHE_TIME)
						$CACHE_MANAGER->Set($ID, $res);
				}
			}
		}
		$db_res = new CDBResult();
		$db_res->InitFromArray(array($GLOBALS["VOTE_CACHE"]["VOTE"][$ID]));
		return $db_res;
	}

	public static function UserAlreadyVote($VOTE_ID, $VOTE_USER_ID, $UNIQUE_TYPE, $KEEP_IP_SEC, $USER_ID = false)
	{
		global $DB, $USER;
		$err_mess = (CAllVote::err_mess())."<br>Function: UserAlreadyVote<br>Line: ";
		$VOTE_ID = intval($VOTE_ID);
		$UNIQUE_TYPE = intval($UNIQUE_TYPE);
		$VOTE_USER_ID = intval($VOTE_USER_ID);
		$USER_ID = intval($USER_ID);

		if ($VOTE_ID <= 0)
			return false;

		if ($UNIQUE_TYPE <= 0)
			return false;

		if ($UNIQUE_TYPE > 4)
			$UNIQUE_TYPE -= 5;
		//No restrictions
		if ($UNIQUE_TYPE <= 0)
			return false;

		//One session
		if (($UNIQUE_TYPE & 1) && IsModuleInstalled('statistic') && is_array($_SESSION["VOTE_ARRAY"]) && in_array($VOTE_ID, $_SESSION["VOTE_ARRAY"]))
			return 1;

		$arSqlSearch = array();
		$arSqlSelect = array("VE.ID");

		//Same cookie
		if ($UNIQUE_TYPE & 2)
		{
			if (($VOTE_USER_ID > 0) && ($UNIQUE_TYPE != 6))
			{
				$arSqlSelect[] = "VE.VOTE_USER_ID";
				$arSqlSearch[] = "VE.VOTE_USER_ID='".$VOTE_USER_ID."'";
			}
		}

		// Same IP
		if ($UNIQUE_TYPE & 4)
		{
			$tmp = CVote::CheckVotingIP($VOTE_ID, $_SERVER["REMOTE_ADDR"], $KEEP_IP_SEC, array("RETURN_SEARCH_ARRAY" => "Y"));
			if (is_array($tmp))
			{
				$arSqlSelect[] = $tmp["select"];
				$arSqlSearch[] = $tmp["search"];
			}
			else
				return 4;
		}

		// Same ID
		if ($UNIQUE_TYPE & 8)
		{
			if ($USER_ID <= 0 || $USER_ID == $USER->GetID() && !!$_SESSION["VOTE"]["VOTES"][$VOTE_ID])
			{
				return 8;
			}
			else
			{
				if ($UNIQUE_TYPE & 16) // Register date
				{
					$rUser = CUser::GetByID($USER_ID);
					if ($rUser && $arUser = $rUser->Fetch())
					{
						$userRegister = MakeTimeStamp($arUser['DATE_REGISTER']);
						$rVote = CVote::GetByID($VOTE_ID);
						if ($rVote && $arVote = $rVote->Fetch())
						{
							$voteStart = MakeTimeStamp($arVote['DATE_START']);
							if ($userRegister > $voteStart)
							{
								return 16;
							}
						}
					}
				}
				$arSqlSelect[] = "VU.AUTH_USER_ID";
				$arSqlSearch[] = "VU.AUTH_USER_ID=".$USER_ID;
			}
		}

		if (!empty($arSqlSearch))
		{
			$strSql = "SELECT ".implode(",", $arSqlSelect)."
				FROM b_vote_event VE
				LEFT JOIN b_vote_user VU ON (VE.VOTE_USER_ID = VU.ID)
				WHERE VE.VOTE_ID=".$VOTE_ID." AND ((".implode(") OR (", $arSqlSearch)."))";
			$db_res = $DB->Query($strSql, false, $err_mess.__LINE__);
			if ($db_res && $res = $db_res->Fetch())
			{
				$return = 16; $event_id = 0;
				do {
					if (($UNIQUE_TYPE & 2) && $res["VOTE_USER_ID"] == $VOTE_USER_ID)
					{
						$return = min($return, 2);
						break;
					}
					elseif (($UNIQUE_TYPE & 4) && $res["IP"] == $_SERVER["REMOTE_ADDR"] && (
						$KEEP_IP_SEC <= 0 || $KEEP_IP_SEC > $res["KEEP_IP_SEC"]))
					{
						$return = min($return, 4);
					}
					elseif (($UNIQUE_TYPE & 8) && $res["AUTH_USER_ID"] == $USER_ID)
					{
						$return = min($return, 8);
						$event_id = ($event_id > 0  && $USER_ID == $USER->GetID() ? $event_id : intval($res["ID"]));
					}
				} while ($res = $db_res->Fetch());
				if ($event_id > 0)
					$_SESSION["VOTE"]["VOTES"][$VOTE_ID] = $event_id;
				return ($return != 16 ? $return : true);
			}
		}

		return false;
	}

	public static function UserGroupPermission($CHANNEL_ID)
	{
		global $USER;
		return CVoteChannel::GetGroupPermission($CHANNEL_ID, $USER->GetUserGroupArray());
	}

	public static function SetVoteUserID()
	{
		global $DB, $USER, $APPLICATION;
		$err_mess = (CAllVote::err_mess())."<br>Function: SetVoteUserID<br>Line: ";
		$COOKIE_VOTE_USER_ID = intval($APPLICATION->get_cookie("VOTE_USER_ID"));
		$_SESSION["VOTE_USER_ID"] = $COOKIE_VOTE_USER_ID;
		$arFields = array(
			"LAST_IP"		=> "'".$DB->ForSql($_SERVER["REMOTE_ADDR"],15)."'",
			"DATE_LAST"		=> $DB->GetNowFunction(),
			"STAT_GUEST_ID"	=> "'".intval($_SESSION["SESS_GUEST_ID"])."'",
			"AUTH_USER_ID"	=> "'".intval($USER->GetID())."'"
			);
		$rows = $DB->Update("b_vote_user", $arFields, "WHERE (ID='".$COOKIE_VOTE_USER_ID."') AND (AUTH_USER_ID='".intval($USER->GetID())."')", $err_mess.__LINE__);
		// insert user if not exists
		if (intval($rows)<=0)
		{
			$arFields["DATE_LAST"] = $DB->GetNowFunction();
			$arFields["DATE_FIRST"] = $DB->GetNowFunction();
			$_SESSION["VOTE_USER_ID"] = $DB->Insert("b_vote_user",$arFields, $err_mess.__LINE__);
			$_SESSION["VOTE_USER_ID"] = intval($_SESSION["VOTE_USER_ID"]);
			$APPLICATION->set_cookie("VOTE_USER_ID", $_SESSION["VOTE_USER_ID"]);
		}
		return $_SESSION["VOTE_USER_ID"];
	}

	public static function UpdateVoteUserID($VOTE_USER_ID)
	{
		global $DB;
		$err_mess = (CAllVote::err_mess())."<br>Function: UpdateVoteUserID<br>Line: ";

		$VOTE_USER_ID = intval($VOTE_USER_ID);
		$arFields = array(
			"DATE_LAST"		=> $DB->GetNowFunction(),
			"COUNTER"		=> "COUNTER+1"
			);
		return $DB->Update("b_vote_user", $arFields, "WHERE ID='".$VOTE_USER_ID."'", $err_mess.__LINE__);
	}

	public static function KeepVoting()
	{
		global $DB, $VOTING_LAMP, $USER_ALREADY_VOTE, $USER_GROUP_PERMISSION, $USER;
		$err_mess = (CAllVote::err_mess())."<br>Function: KeepVoting<br>Line: ";
		$VOTING_LAMP = "green";
		$USER_ALREADY_VOTE = "N";
		$PUBLIC_VOTE_ID = intval($_REQUEST["PUBLIC_VOTE_ID"]);
		$aMsg = array();
		$VOTE_ID = 0;
		$arVote = array();
		$arQuestions = array();

		if (!(!empty($_REQUEST["vote"]) && $PUBLIC_VOTE_ID > 0 && check_bitrix_sessid()))
		{
			$aMsg[] = array(
				"id" => "bad_params",
				"text" => GetMessage("VOTE_NOT_FOUND"));
		}
		elseif (($VOTE_ID = intVal(GetVoteDataByID($PUBLIC_VOTE_ID, $arChannel, $arVote, $arQuestions, $arAnswers, $arDropDown, $arMultiSelect, $arGroupAnswers, "N")))
			&& ($VOTE_ID <= 0 || $arVote["LAMP"] != "green"))
		{
			$VOTING_LAMP = "red";
			if ($VOTE_ID <= 0)
				$aMsg[] = array(
					"id" => "VOTE_ID",
					"text" => GetMessage("VOTE_NOT_FOUND"));
			else
				$aMsg[] = array(
					"id" => "LAMP",
					"text" => GetMessage("VOTE_RED_LAMP"));
		}
		elseif ($arChannel["USE_CAPTCHA"] == "Y" && !$USER->IsAuthorized())
		{
			include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/captcha.php");
			$cpt = new CCaptcha();
			if (!empty($_REQUEST["captcha_word"]))
			{
				$captchaPass = COption::GetOptionString("main", "captcha_password", "");
				if (!$cpt->CheckCodeCrypt($_REQUEST["captcha_word"], $_REQUEST["captcha_code"], $captchaPass))
				{
					$GLOBALS["BAD_CAPTCHA"] = "Y";
					$aMsg[] = array(
						"id" => "CAPTCHA",
						"text" => GetMessage("VOTE_BAD_CAPTCHA"));
				}
			}
			else
			{
				if (!$cpt->CheckCode($_REQUEST["captcha_word"], 0))
				{
					$GLOBALS["NO_CAPTCHA"] = "Y";
					$aMsg[] = array(
						"id" => "CAPTCHA",
						"text" => GetMessage("VOTE_BAD_CAPTCHA"));
				}
			}
		}

		if (empty($aMsg))
		{
			// get user id
			$_SESSION["VOTE_USER_ID"] = CVote::SetVoteUserID();
			$GLOBALS["VOTING_ID"] = $VOTE_ID;
			// check: can user vote
			$UNIQUE_TYPE = $arVote["UNIQUE_TYPE"];
			$KEEP_IP_SEC = $arVote["KEEP_IP_SEC"];
			$CHANNEL_ID = $arVote["CHANNEL_ID"];

			$StatusVote = CVote::UserAlreadyVote($VOTE_ID, $_SESSION["VOTE_USER_ID"], $UNIQUE_TYPE, $KEEP_IP_SEC, $USER->GetID());
			$USER_ALREADY_VOTE = ($StatusVote != false ? "Y" : "N");

			$USER_GROUP_PERMISSION = CVote::UserGroupPermission($CHANNEL_ID);

			// if user can vote that
			if (($USER_ALREADY_VOTE == "N" || ($StatusVote == 8 && $_REQUEST["REVOTE_ID"] == $VOTE_ID)) && $USER_GROUP_PERMISSION >= 2)
			{
				$arSqlAnswers = array();
				// check answers
				foreach ($arQuestions as $qID => $arQuestion)
				{
					$arSqlAnswers[$arQuestion["ID"]] = array();
					$bIndicators = array();
					foreach ($arQuestion["ANSWERS"] as $arAnswer)
					{
						switch ($arAnswer["FIELD_TYPE"]):
							case 0: // radio
							case 2: // dropdown list
								$fieldName = ($arAnswer["FIELD_TYPE"] == 0 ? "vote_radio_" : "vote_dropdown_").$qID;
								$aID = intval($GLOBALS[$fieldName]);
								if (!isset($bIndicators[$fieldName]) && array_key_exists($aID, $arQuestion["ANSWERS"]))
								{
									if (!empty($arAnswer['MESSAGE']))
										$arSqlAnswers[$qID][$aID] = array("ANSWER_ID" => $aID);
									$bIndicators[$fieldName] = "Y";
								}
								break;
							case 1: // checkbox
							case 3: // multiselect list
								$fieldName = ($arAnswer["FIELD_TYPE"] == 1 ? "vote_checkbox_" : "vote_multiselect_").$qID;
								$res = $GLOBALS[$fieldName];
								if (!isset($bIndicators[$fieldName]) && is_array($res) && !empty($res))
								{
									reset($res);
									foreach($res as $aID):
										if (array_key_exists($aID, $arQuestion["ANSWERS"]))
											$arSqlAnswers[$qID][$aID] = array("ANSWER_ID" => $aID);
									endforeach;
									$bIndicators[$fieldName] = "Y";
								}
								break;
							case 4: // field
							case 5: // text
								$aID = $arAnswer["ID"];
								$fieldName = ($arAnswer["FIELD_TYPE"] == 4 ? "vote_field_" : "vote_memo_").$aID;
								$MESSAGE = trim($GLOBALS[$fieldName]);
								if ($MESSAGE != "")
								{
									$arSqlAnswers[$qID][$aID] = array(
										"ANSWER_ID" => $aID,
										"MESSAGE" => "'".$DB->ForSql(trim($MESSAGE), 2000)."'");
								}
								break;
						endswitch;
					}
					if (empty($arSqlAnswers[$qID]))
					{
						unset($arSqlAnswers[$qID]);
						if ($arQuestion['REQUIRED'] == 'Y')
						{
							$aMsg[] = array(
								"id" => "QUESTION_".$qID,
								"text" => GetMessage("VOTE_REQUIRED_MISSING"));
							$GLOBALS["VOTE_REQUIRED_MISSING"] = "Y";
							break;
						}
					}
				}
				if (!empty($aMsg))
				{
					/* do nothing; */
				}
				elseif (!empty($arSqlAnswers))
				{
					// vote event
					$arFields = array(
						"VOTE_ID"			=> $VOTE_ID,
						"VOTE_USER_ID"		=> intval($_SESSION["VOTE_USER_ID"]),
						"DATE_VOTE"			=> $DB->GetNowFunction(),
						"STAT_SESSION_ID"	=> intval($_SESSION["SESS_SESSION_ID"]),
						"IP"				=> "'".$DB->ForSql($_SERVER["REMOTE_ADDR"],15)."'",
						"VALID"				=> "'Y'");

					/***************** Event onBeforeVoting ****************************/
					foreach (GetModuleEvents("vote", "onBeforeVoting", true) as $arEvent)
					{
						if (ExecuteModuleEventEx($arEvent, array(&$arFields, &$arSqlAnswers)) === false)
							return false;
					}
					/***************** /Event ******************************************/
					if ($StatusVote == 8 && $_REQUEST["REVOTE_ID"] == $VOTE_ID)
					{
						$strSql = "SELECT VE.ID, VEQ.QUESTION_ID, VEA.ANSWER_ID".
							" FROM b_vote_event VE ".
							"	LEFT JOIN b_vote_event_question VEQ ON (VEQ.EVENT_ID=VE.ID)".
							"	LEFT JOIN b_vote_event_answer VEA ON (VEA.EVENT_QUESTION_ID=VEQ.ID)".
							"	LEFT JOIN b_vote_user VU ON (VE.VOTE_USER_ID = VU.ID)".
							" WHERE VE.VOTE_ID=".$VOTE_ID." AND VU.AUTH_USER_ID=".intval($USER->GetID()).
							" ORDER BY VE.ID ASC, VEQ.QUESTION_ID ASC";
						$db_res = $DB->Query($strSql, false, $err_mess.__LINE__);
						if ($db_res && $res = $db_res->Fetch())
						{
							if ($USER->GetID() > 0 && CModule::IncludeModule("im"))
								CIMNotify::DeleteByTag("VOTING|".$VOTE_ID, $USER->GetID());
							$del = false; $delQ = false;
							do {
								if ($delQ !== $res["QUESTION_ID"])
								{
									if ($del !== $res["ID"])
									{
										CVoteEvent::Delete($res["ID"]);
										$del = $res["ID"];
										$arVote["COUNTER"] = intval($arVote["COUNTER"]) - 1;
									}
									$delQ = $res["QUESTION_ID"];
									$arQuestions[$res["QUESTION_ID"]]["COUNTER"] =
										intval($arQuestions[$res["QUESTION_ID"]]["COUNTER"]) - 1;
								}
								$arQuestions[$res["QUESTION_ID"]]["ANSWERS"][$res["ANSWER_ID"]]["COUNTER"] =
									intval($arQuestions[$res["QUESTION_ID"]]["ANSWERS"][$res["ANSWER_ID"]]["COUNTER"]) - 1;
							} while($res = $db_res->Fetch());
						}
						$USER_ALREADY_VOTE = "N";
					}

					unset($GLOBALS["VOTE_CACHE_VOTING"][$VOTE_ID]);
					unset($GLOBALS["VOTE_CACHE"]["VOTE"][$VOTE_ID]);
					$EVENT_ID = intval($DB->Insert("b_vote_event", $arFields, $err_mess.__LINE__));
					if ($EVENT_ID > 0)
					{
						$arSqlQuestionsID = array();
						$arSqlAnswersID = array();

						foreach ($arSqlAnswers as $qID => $arSqlAnswer):
							$arFields = array("EVENT_ID" => $EVENT_ID, "QUESTION_ID" => $qID);
							$EVENT_QUESTION_ID = intval($DB->Insert("b_vote_event_question", $arFields, $err_mess.__LINE__));
							if ($EVENT_QUESTION_ID > 0):
								$arSqlQuestionsID[] = $qID;
								$arQuestions[$qID]["COUNTER"] = intval($arQuestions[$qID]["COUNTER"]) + 1;
								foreach ($arSqlAnswer as $aID => $res):
									$res["EVENT_QUESTION_ID"] = $EVENT_QUESTION_ID;
									if ($DB->Insert("b_vote_event_answer", $res, $err_mess.__LINE__))
									{
										$arSqlAnswersID[$aID] = $qID;
										$arQuestions[$qID]["ANSWERS"][$aID]["COUNTER"] =
											intval($arQuestions[$qID]["ANSWERS"][$aID]["COUNTER"]) + 1;
									}
								endforeach;
							endif;
						endforeach;

						if (empty($arSqlQuestionsID) || empty($arSqlAnswersID)):
							$DB->Query("DELETE FROM b_vote_event WHERE ID=".$EVENT_ID, $arFields, $err_mess.__LINE__);
						else:
							$arFields = array("COUNTER" => "COUNTER+1");
							$DB->Update("b_vote", $arFields, "WHERE ID='".$VOTE_ID."'", $err_mess.__LINE__);
							$arVote["COUNTER"] = intval($arVote["COUNTER"]) + 1;


							$DB->Update("b_vote_question", $arFields, "WHERE ID in (".implode(", ", $arSqlQuestionsID).")",$err_mess.__LINE__);
							$DB->Update("b_vote_answer", $arFields, "WHERE ID in (".implode(", ", array_keys($arSqlAnswersID)).")", $err_mess.__LINE__);

							// increment user counter
							CVote::UpdateVoteUserID($_SESSION["VOTE_USER_ID"]);
							$GLOBALS["VOTING_OK"] = "Y";
							$_SESSION["VOTE_ARRAY"][] = $VOTE_ID;
							if ($UNIQUE_TYPE & 8)
								$_SESSION["VOTE"]["VOTES"][$VOTE_ID] = $EVENT_ID;
							// statistic module
							if (CModule::IncludeModule("statistic"))
							{
								$event3 = $arVote["EVENT3"];
								if (!empty($event3)):
									$event3 = "http://".$_SERVER["HTTP_HOST"]."/bitrix/admin/vote_user_results.php?EVENT_ID=". $EVENT_ID."&lang=".LANGUAGE_ID;
								endif;
								CStatEvent::AddCurrent($arVote["EVENT1"], $arVote["EVENT2"], $event3);
							}
							// notification
							if (!!$arVote["AUTHOR_ID"] && $arVote["AUTHOR_ID"] != $USER->GetID())
							{
								if (empty($arVote["TITLE"]))
								{
									$arQuestion = reset($arQuestions);
									$arVote["TITLE"] = $arQuestion["QUESTION"];
								}
								if ($arVote["NOTIFY"] == "I" && CModule::IncludeModule("im"))
								{
									$arVote["TOTAL_URL"] = "";
									if (!empty($arVote["URL"]))
									{
										if (defined('SITE_SERVER_NAME'))
											$arVote["TOTAL_URL"] = SITE_SERVER_NAME;
										$arVote["TOTAL_URL"] = (!empty($arVote["TOTAL_URL"]) ? $arVote["TOTAL_URL"] : COption::GetOptionString("main", "server_name", $GLOBALS["SERVER_NAME"]));
										if (!empty($arVote["TOTAL_URL"]))
											$arVote["TOTAL_URL"] = (CMain::IsHTTPS() ? "https" : "http")."://".$arVote["TOTAL_URL"].$arVote["URL"];
									}


									// send notification
									$gender = ($USER->getParam("PERSONAL_GENDER") == "F" ? "_F" : "");
									$arMessageFields = array(
										"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
										"TO_USER_ID" => $arVote["AUTHOR_ID"],
										"FROM_USER_ID" => $USER->GetID(),
										"NOTIFY_TYPE" => IM_NOTIFY_FROM,
										"NOTIFY_MODULE" => "vote",
										"NOTIFY_EVENT" => "voting",
										"NOTIFY_TAG" => "VOTING|".$VOTE_ID,
										"NOTIFY_MESSAGE" => (!empty($arVote["URL"]) ?
											GetMessage("V_NOTIFY_MESSAGE_HREF".$gender, array("#VOTE_TITLE#" => $arVote["TITLE"], "#VOTE_URL#" => $arVote["URL"])) :
											GetMessage("V_NOTIFY_MESSAGE".$gender, array("#VOTE_TITLE#" => $arVote["TITLE"]))),
										"NOTIFY_MESSAGE_OUT" => (!empty($arVote["TOTAL_URL"]) ?
											GetMessage("V_NOTIFY_MESSAGE_OUT_HREF".$gender, array("#VOTE_TITLE#" => $arVote["TITLE"], "#VOTE_URL#" => $arVote["TOTAL_URL"])) :
											GetMessage("V_NOTIFY_MESSAGE".$gender, array("#VOTE_TITLE#" => $arVote["TITLE"])))
									);

									CIMNotify::Add($arMessageFields);
								}
								else if ($arVote["NOTIFY"] == "Y")
								{
									// send e-mail
									$db_user = CUser::GetById($arVote["AUTHOR_ID"]);
									if ($db_user && ($arUser = $db_user->Fetch()) && !empty($arUser["EMAIL"]))
									{
										$arEventFields = array(
											"EMAIL_TO"			=> $arUser["EMAIL"],
											"VOTE_STATISTIC"	=> "",
											"ID"				=> $EVENT_ID,
											"TIME"				=> GetTime(time(),"FULL"),
											"VOTE_TITLE"		=> $arVote["TITLE"],
											"VOTE_DESCRIPTION"	=> $arVote["DESCRIPTION"],
											"VOTE_ID"			=> $arVote["ID"],
											"VOTE_COUNTER"		=> $arVote["COUNTER"],
											"URL"				=> $arVote["URL"],
											"CHANNEL"			=> $arChannel["TITLE"],
											"CHANNEL_ID"		=> $arChannel["ID"],
											"VOTER_ID"			=> $_SESSION["VOTE_USER_ID"],
											"USER_NAME"			=> $USER->GetFullName(),
											"LOGIN"				=> $USER->GetLogin(),
											"USER_ID"			=> $USER->GetID(),
											"STAT_GUEST_ID"		=> intval($_SESSION["SESS_GUEST_ID"]),
											"SESSION_ID"		=> intval($_SESSION["SESS_SESSION_ID"]),
											"IP"				=> $_SERVER["REMOTE_ADDR"]);
										$arEventFields["USER_NAME"] = (!!$arEventFields["USER_NAME"] ? $arEventFields["USER_NAME"] : $arEventFields["LOGIN"]);
										// VOTE_STATISTIC
										$text = array();
										foreach ($arSqlAnswersID as $aID => $qID)
										{
											$text[$qID] = (is_array($text[$qID]) ? $text[$qID] : array());
											if ($arQuestions[$qID]["ANSWERS"][$aID]["FIELD_TYPE"] == 4 ||
												$arQuestions[$qID]["ANSWERS"][$aID]["FIELD_TYPE"] == 5)
											{
												if (!empty($arSqlAnswers[$qID][$aID]["MESSAGE"]))
													$text[$qID][] = $arSqlAnswers[$qID][$aID]["MESSAGE"];
											}
											else
											{
												$text[$qID][] = $arQuestions[$qID]["ANSWERS"][$aID]["MESSAGE"];
											}
										}
										foreach ($text as $qID => $txt)
										{
											$text[$qID] = " - ".$arQuestions[$qID]["QUESTION"]."\n - ".implode(", ", $text[$qID]);
										}
										$arEventFields["VOTE_STATISTIC"] = "\n".implode("\n\n", $text);
										$arrSites = CVoteChannel::GetSiteArray($arChannel["ID"]);
										CEvent::Send("VOTE_FOR", $arrSites, $arEventFields, "N");
									}
								}
							}
						endif;
						/***************** Event onAfterVoting *****************************/
						foreach (GetModuleEvents("vote", "onAfterVoting", true) as $arEvent)
							ExecuteModuleEventEx($arEvent, array($VOTE_ID, $EVENT_ID));
						/***************** /Event ******************************************/
					}
				}
				else
				{
					$GLOBALS["USER_VOTE_EMPTY"] = "Y";
					$aMsg[] = array(
						"id" => "VOTE_ID",
						"text" => GetMessage("USER_VOTE_EMPTY"));
				}
			}
			else
			{
				$aMsg[] = array(
					"id" => "VOTE_ID",
					"text" => ($USER_GROUP_PERMISSION >= 2 ? GetMessage("VOTE_ALREADY_VOTE") : GetMessage("VOTE_ACCESS_DENIED")));
			}
		}
		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg, "CVote::KeepVoting");
			$GLOBALS["APPLICATION"]->ThrowException($e);
			$GLOBALS["VOTING_OK"] = "N";
			return false;
		}

		return true;
	}

	public static function GetNextSort($CHANNEL_ID)
	{
		global $DB;
		$err_mess = (CAllVote::err_mess())."<br>Function: GetNextSort<br>Line: ";
		$CHANNEL_ID = intval($CHANNEL_ID);
		$strSql = "SELECT max(C_SORT) MAX_SORT FROM b_vote WHERE CHANNEL_ID='$CHANNEL_ID'";
		$z = $DB->Query($strSql, false, $err_mess.__LINE__);
		$zr = $z->Fetch();
		return intval($zr["MAX_SORT"])+100;
	}

	public static function WrongDateInterval($CURRENT_VOTE_ID, $DATE_START, $DATE_END, $CHANNEL_ID)
	{
		global $DB;
		$err_mess = (CAllVote::err_mess())."<br>Function: WrongDateInterval<br>Line: ";
		$CURRENT_VOTE_ID = intval($CURRENT_VOTE_ID);
		$CURRENT_VOTE_ID = ($CURRENT_VOTE_ID > 0 ? $CURRENT_VOTE_ID : false);
		$CHANNEL_ID = intval($CHANNEL_ID);
		$CHANNEL_ID = ($CHANNEL_ID > 0 ? $CHANNEL_ID : false);
		$DATE_START = ($DATE_START == false ? false : (trim($DATE_START) == '' ? false : trim($DATE_START)));
		$DATE_END = ($DATE_END == false ? false : (trim($DATE_END) == '' ? false : trim($DATE_END)));
		
		if($CURRENT_VOTE_ID == false && $CHANNEL_ID == false)
		{
			return 0;
		}
		elseif($CHANNEL_ID > 0)
		{
			$db_res = CVoteChannel::GetByID($CHANNEL_ID);
			if($db_res && $res = $db_res->Fetch())
				if($res["VOTE_SINGLE"] != "Y")
					return 0;
		}
		
		$st = ($DATE_START == false ? "VV.DATE_START" : $DB->CharToDateFunction($DATE_START, "FULL"));
		$en = ($DATE_END == false ? "VV.DATE_END" : $DB->CharToDateFunction($DATE_END, "FULL"));
		if($CURRENT_VOTE_ID <= 0)
		{
			if($DATE_START == false)
				$st = $DB->CurrentTimeFunction();
			if($DATE_END == false)
				$en = $DB->CharToDateFunction(ConvertTimeStamp(1924984799, "FULL"), "FULL"); // '31.12.2030 23:59:59'
		}

		$strSql = "
			SELECT V.ID
			FROM b_vote V 
			".($CURRENT_VOTE_ID > 0 ? 
			"LEFT JOIN b_vote VV ON (VV.ID = ".$CURRENT_VOTE_ID.") " : "")."
			INNER JOIN b_vote_channel VC ON (V.CHANNEL_ID = VC.ID AND VC.VOTE_SINGLE = 'Y')
			WHERE
				V.CHANNEL_ID=".($CHANNEL_ID == false ? "VV.CHANNEL_ID" : $CHANNEL_ID)." AND 
				V.ACTIVE='Y' AND 
				".($CURRENT_VOTE_ID > 0 ? 
				"V.ID<>'".$CURRENT_VOTE_ID."' AND " : "")."
				(
					(".$st." between V.DATE_START and V.DATE_END) OR
					(".$en." between V.DATE_START and V.DATE_END) OR
					(V.DATE_START between ".$st." and ".$en.") OR
					(V.DATE_END between ".$st." and ".$en.")
				)";
		$db_res = $DB->Query($strSql, false, $err_mess.__LINE__);
		if($db_res && $res = $db_res->Fetch())
			return intval($res["ID"]);

		return 0;
	}
}

class _CVoteDBResult extends CDBResult
{
	public static function _CVoteDBResult($res, $params = array())
	{
		parent::CDBResult($res);
	}
	public static function Fetch()
	{
		if($res = parent::Fetch())
		{
			if ($res["LAMP"] == "yellow" && !empty($res["CHANNEL_ID"]))
			{
				$res["LAMP"] = ($res["ID"] == CVote::GetActiveVoteId($res["CHANNEL_ID"]) ? "green" : "red");
			}
		}
		return $res;
	}
}
?>