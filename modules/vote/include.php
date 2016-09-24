<?
global $DB, $MESS, $APPLICATION, $voteCache;

require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin_tools.php");
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/filter_tools.php");
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/vote_tools.php");
require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/vote/classes/".strtolower($DB->type)."/channel.php");
IncludeModuleLangFile(__FILE__);

if (!defined("VOTE_CACHE_TIME"))
	// define("VOTE_CACHE_TIME", 3600);

// define("VOTE_DEFAULT_DIAGRAM_TYPE", "histogram");

$GLOBALS["VOTE_CACHE"] = array(
	"CHANNEL" => array(),
	"VOTE" => array(),
	"QUESTION" => array());
$GLOBALS["VOTE_CACHE_VOTING"] = array();
$GLOBALS["aVotePermissions"] = array(
	"reference_id" => array(0, 1, 2, /*3, */4),
	"reference" => array(GetMessage("VOTE_DENIED"), GetMessage("VOTE_READ"), GetMessage("VOTE_WRITE"), /*GetMessage("VOTE_EDIT_MY_OWN"), */GetMessage("VOTE_EDIT")));
$_SESSION["VOTE"] = (is_array($_SESSION["VOTE"]) ? $_SESSION["VOTE"] : array());
$_SESSION["VOTE"]["VOTES"] = (is_array($_SESSION["VOTE"]["VOTES"]) ? $_SESSION["VOTE"]["VOTES"] : array());

CModule::AddAutoloadClasses("vote", array(
	"CVoteAnswer" => "classes/".strtolower($DB->type)."/answer.php",
	"CVoteEvent" => "classes/".strtolower($DB->type)."/event.php",
	"CVoteQuestion" => "classes/".strtolower($DB->type)."/question.php",
	"CVoteUser" => "classes/".strtolower($DB->type)."/user.php",
	"CVote" => "classes/".strtolower($DB->type)."/vote.php",
	"CVoteCacheManager" => "classes/general/functions.php",
	"CUserTypeVote" => "classes/general/usertypevote.php",
	"CVoteNotifySchema" => "classes/general/im.php"));

$voteCache = new CVoteCacheManager();

function VoteVoteEditFromArray($CHANNEL_ID, $VOTE_ID = false, $arFields = array(), $params = array())
{
	$CHANNEL_ID = intVal($CHANNEL_ID);
	if ($CHANNEL_ID <= 0 || empty($arFields)):
		return false;
	elseif (CVote::UserGroupPermission($CHANNEL_ID) <= 0):
		return false;
	endif;
	$aMsg = array();
	$params = (is_array($params) ? $params : array());
	$params["UNIQUE_TYPE"] = (is_set($params, "UNIQUE_TYPE") ? intVal($params["UNIQUE_TYPE"]) : 20);
	$params["DELAY"] = (is_set($params, "DELAY") ? intVal($params["DELAY"]) : 10);
	$params["DELAY_TYPE"] = ((is_set($params, "DELAY_TYPE") && in_array($params['DELAY_TYPE'], array("S", "M", "H", "D")))? ($params["DELAY_TYPE"]) : "D");

	$arVote = array();
	$arQuestions = array();

	$arFieldsQuestions = array();
	$arFieldsVote = array(
		"CHANNEL_ID" => $CHANNEL_ID,
		"AUTHOR_ID" => $GLOBALS["USER"]->GetID(),
		"UNIQUE_TYPE" => $params["UNIQUE_TYPE"],
		"DELAY" => $params["DELAY"],
		"DESCRIPTION_TYPE" => $params["DELAY_TYPE"]);
	if (!empty($arFields["DATE_START"]))
		$arFieldsVote["DATE_START"] = $arFields["DATE_START"];
	if (!empty($arFields["DATE_END"]))
		$arFieldsVote["DATE_END"] = $arFields["DATE_END"];
	if (!empty($arFields["TITLE"]))
		$arFieldsVote["TITLE"] = $arFields["TITLE"];
	if (isset($arFields["ACTIVE"]))
		$arFieldsVote["ACTIVE"] = $arFields["ACTIVE"];
	if (isset($arFields["NOTIFY"]))
		$arFieldsVote["NOTIFY"] = $arFields["NOTIFY"];
	if (isset($arFields["URL"]))
		$arFieldsVote["URL"] = $arFields["URL"];
/************** Fatal errors ***************************************/
	if (!CVote::CheckFields("UPDATE", $arFieldsVote)):
		$e = $GLOBALS['APPLICATION']->GetException();
		$aMsg[] = array(
			"id" => "VOTE_ID",
			"text" => $e->GetString());
	elseif (intval($VOTE_ID) > 0):
		$db_res = CVote::GetByID($VOTE_ID);
		if (!($db_res && $res = $db_res->Fetch())):
			$aMsg[] = array(
				"id" => "VOTE_ID",
				"text" => GetMessage("VOTE_VOTE_NOT_FOUND", array("#ID#", $VOTE_ID)));
		elseif ($res["CHANNEL_ID"] != $CHANNEL_ID):
			$aMsg[] = array(
				"id" => "CHANNEL_ID",
				"text" => GetMessage("VOTE_CHANNEL_ID_ERR"));
		else:
			$arVote = $res;
			$db_res = CVoteQuestion::GetList($arVote["ID"], $by = "s_id", $order = "asc", array(), $is_filtered);
			if ($db_res && $res = $db_res->Fetch()):
				do { $arQuestions[$res["ID"]] = $res + array("ANSWERS" => array()); } while ($res = $db_res->Fetch());
			endif;
			$db_res = CVoteAnswer::GetListEx(array("ID" => "ASC"), array("VOTE_ID" => $arVote["ID"]));
			if ($db_res && $res = $db_res->Fetch()):
				do { $arQuestions[$res["QUESTION_ID"]]["ANSWERS"][$res["ID"]] = $res; } while ($res = $db_res->Fetch());
			endif;
		endif;
	endif;
	if (!empty($aMsg)):
		$e = new CAdminException(array_reverse($aMsg));
		$GLOBALS["APPLICATION"]->ThrowException($e);
		return false;
	endif;
/************** Fatal errors/***************************************/
	if (!empty($arFieldsVote["TITLE"]) && !empty($arVote["TITLE"]))
	{
		$q = reset($arQuestions);
		if ($arVote["TITLE"] == substr($q["QUESTION"], 0, strlen($arVote["TITLE"])))
			unset($arFieldsVote["TITLE"]);
	}
/************** Check Data *****************************************/
	// Questions
	$arFields["QUESTIONS"] = (is_array($arFields["QUESTIONS"]) ? $arFields["QUESTIONS"] : array());
	$iQuestions = 0;
	foreach ($arFields["QUESTIONS"] as $key => $arQuestion)
	{
		if ($arQuestion["DEL"] != "Y")
		{
			$arQuestion["ID"] = intval($arQuestion["ID"]);
			$arQuestion = array(
				"ID" => $arQuestion["ID"] > 0 && is_set($arQuestions, $arQuestion["ID"]) ? $arQuestion["ID"] : false,
				"QUESTION" => trim($arQuestion["QUESTION"]),
				"QUESTION_TYPE" => trim($arQuestion["QUESTION_TYPE"]),
				"ANSWERS" => (is_array($arQuestion["ANSWERS"]) ? $arQuestion["ANSWERS"] : array()));

			$arAnswers = ($arQuestion["ID"] > 0 ? $arQuestions[$arQuestion["ID"]]["ANSWERS"] : array());
			foreach ($arQuestion["ANSWERS"] as $keya => $arAnswer)
			{
				$arAnswer["ID"] = intVal($arAnswer["ID"]);
				$arAnswer["MESSAGE"] = trim($arAnswer["MESSAGE"]);
				if (!empty($arAnswer["MESSAGE"]) && $arAnswer["DEL"] != "Y")
				{
					$arQuestion["ANSWERS"][$keya] = array(
						"MESSAGE" => $arAnswer["MESSAGE"],
						"MESSAGE_TYPE" => $arAnswer["MESSAGE_TYPE"],
						"FIELD_TYPE" => $arAnswer["FIELD_TYPE"]);
					if ($arAnswer["ID"] > 0 && is_set($arAnswers, $arAnswer["ID"]))
					{
						$arQuestion["ANSWERS"][$keya]["ID"] = $arAnswer["ID"];
						unset($arAnswers[$arAnswer["ID"]]);
					}
				}
			}
		}

		if ($arQuestion["DEL"] == "Y" || empty($arQuestion["QUESTION"]) || empty($arQuestion["ANSWERS"]))
		{
			if ($arQuestion["DEL"] != "Y" && !(empty($arQuestion["QUESTION"]) && empty($arQuestion["ANSWERS"])))
			{
				$aMsg[] = array(
					"id" => "QUESTION_".$key,
					"text" => (empty($arQuestion["QUESTION"]) ?
						GetMessage("VOTE_QUESTION_EMPTY", array("#NUMBER#" => $key)) :
						GetMessage("VOTE_ANSWERS_EMPTY", array("#QUESTION#" => $arQuestion["QUESTION"]))));
			}
			continue;
		}
		if ($arQuestion["ID"] > 0)
		{
			unset($arQuestions[$arQuestion["ID"]]);
			foreach($arAnswers as $arAnswer)
			{
				$arQuestion["ANSWERS"][] = ($arAnswer + array("DEL" => "Y"));
			}
		}
		$iQuestions++;
		$arFieldsQuestions[$key] = $arQuestion;
	}
	foreach ($arQuestions as $arQuestion)
	{
		$arFieldsQuestions[] = ($arQuestion + array("DEL" => "Y"));
	}

	if (!empty($aMsg)):
		$e = new CAdminException(array_reverse($aMsg));
		$GLOBALS["APPLICATION"]->ThrowException($e);
		return false;
	elseif (empty($arFieldsQuestions) && $VOTE_ID <= 0):
			return true;
	elseif ($params["bOnlyCheck"] == "Y"):
		return true;
	endif;
/************** Check Data/*****************************************/
/************** Main actions with return ***************************/
	if (empty($arFieldsVote["TITLE"]))
	{
		$q = reset($arFieldsQuestions);
		$arFieldsVote["TITLE"] = null;
		do {
			if ($q["DEL"] != "Y")
			{
				$arFieldsVote["TITLE"] = $q["QUESTION"];
				break;
			}
		} while ($q = next($arFieldsQuestions));
		reset($arFieldsQuestions);
	}
	if (empty($arVote))
	{
		$arFieldsVote["UNIQUE_TYPE"] = $params["UNIQUE_TYPE"];
		$arFieldsVote["DELAY"] = $params["DELAY"];
		$arFieldsVote["DELAY_TYPE"] = $params["DELAY_TYPE"];

		$arVote["ID"] = intval(CVote::Add($arFieldsVote));
	}
	else
	{
		CVote::Update($VOTE_ID, $arFieldsVote);
	}

	if ($iQuestions > 0 && $arVote["ID"] > 0)
	{
		$iQuestions = 0;
		foreach ($arFieldsQuestions as $arQuestion)
		{
			if ($arQuestion["DEL"] == "Y"):
				CVoteQuestion::Delete($arQuestion["ID"]);
				continue;
			elseif ($arQuestion["ID"] > 0):
				$arQuestion["C_SORT"] = ($iQuestions + 1) * 10;
				CVoteQuestion::Update($arQuestion["ID"], $arQuestion);
			else:
				$arQuestion["C_SORT"] = ($iQuestions + 1) * 10;
				$arQuestion["VOTE_ID"] = $arVote["ID"];
				$arQuestion["ID"] = intVal(CVoteQuestion::Add($arQuestion));
				if ($arQuestion["ID"] <= 0):
					continue;
				endif;
			endif;
			$iQuestions++;
			$iAnswers = 0;
			foreach ($arQuestion["ANSWERS"] as $arAnswer)
			{
				if ($arAnswer["DEL"] == "Y"):
					CVoteAnswer::Delete($arAnswer["ID"]);
					continue;
				endif;

				if ($arAnswer["ID"] > 0):
					$arAnswer["C_SORT"] = ($iAnswers + 1)* 10;
					CVoteAnswer::Update($arAnswer["ID"], $arAnswer);
				else:
					$arAnswer["QUESTION_ID"] = $arQuestion["ID"];
					$arAnswer["C_SORT"] = ($iAnswers + 1)* 10;
					$arAnswer["ID"] = intVal(CVoteAnswer::Add($arAnswer));
					if ($arAnswer["ID"] <= 0):
						continue;
					endif;
				endif;

				$iAnswers++;
			}
			if ($iAnswers <= 0)
			{
				CVoteQuestion::Delete($arQuestion["ID"]);
				$iQuestions--;
			}
		}
	}

	if (intVal($arVote["ID"]) <= 0)
	{
		return false;
	}
	elseif ($iQuestions <= 0)
	{
		CVote::Delete($arVote["ID"]);
		return 0;
	}
	return $arVote["ID"];
/************** Actions/********************************************/
/*	$arFields = array(
		"ID" => 345,
		"TITLE" => "test",
		"...",
		"QUESTIONS" => array(
			array(
				"ID" => 348,
				"QUESTION" => "test",
				"ANSWERS" => array(
					array(
						"ID" => 340,
						"MESSAGE" => "test"),
					array(
						"ID" => 0,
						"MESSAGE" => "test"),
					array(
						"ID" => 350,
						"DEL" => "Y",
						"MESSAGE" => "test")
					)
				),
			array(
				"ID" => 351,
				"DEL" => "Y",
				"QUESTION" => "test",
				"ANSWERS" => array(
					array(
						"ID" => 0,
						"MESSAGE" => "test"),
					array(
						"ID" => 478,
						"DEL" => "Y",
						"MESSAGE" => "test")
					)
				),
			array(
				"ID" => 0,
				"QUESTION" => "test",
				"ANSWERS" => array(
					array(
						"ID" => 0,
						"MESSAGE" => "test"),
					)
				),
			)
		);
*/


}

function VoteIsUserVoteForVote($VOTE_ID, $USER_ID = 0)
{
	if (!empty($VOTE_ID))
	{
		$res = (is_array($_SESSION["VOTE_ARRAY"]) && in_array($VOTE_ID, $_SESSION["VOTE_ARRAY"]));
		if (!$res)
		{
			$_SESSION["VOTE"] = (is_array($_SESSION["VOTE"]) ? $_SESSION["VOTE"] : array());
			$_SESSION["VOTE"]["VOTES"] = (is_array($_SESSION["VOTE"]["VOTES"]) ? $_SESSION["VOTE"]["VOTES"] : array());

			if (!in_array($VOTE_ID, $_SESSION["VOTE"]["VOTES"]))
			{
				$_SESSION["VOTE"]["VOTES"][$VOTE_ID] = false;

				$USER_ID = intval($USER_ID);
				$USER_ID = ($USER_ID > 0 ? $USER_ID : $GLOBALS["USER"]->GetID());
				$arFilter = array();
				if ($USER_ID > 0)
					$arFilter["USER_ID"] = $USER_ID;
				else
				{
					$voteUserID = ($_SESSION["VOTE_USER_ID"] ? $_SESSION["VOTE_USER_ID"] : intval($GLOBALS["APPLICATION"]->get_cookie("VOTE_USER_ID")));
					if ($voteUserID > 0)
						$arFilter["VOTE_USER"] = ($_SESSION["VOTE_USER_ID"] ? $_SESSION["VOTE_USER_ID"] : $GLOBALS["APPLICATION"]->get_cookie("VOTE_USER_ID"));
				}
				if (!empty($arFilter))
				{
					$arFilter["VOTE_ID"] = $VOTE_ID;
					$db_res = CVoteEvent::GetList($by, $order, $arFilter, $is_filtered, "Y");
					if ($db_res && $res = $db_res->Fetch())
						$_SESSION["VOTE"]["VOTES"][$VOTE_ID] = $res["ID"];
				}
			}
			$res = $_SESSION["VOTE"]["VOTES"][$VOTE_ID];
		}
		return $res;
	}
	return false;
}
?>