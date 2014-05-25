<?
IncludeModuleLangFile(__FILE__);

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/classes/general/runtimeservice.php");

class CBPAllTaskService
	extends CBPRuntimeService
{
	static public function DeleteTask($id)
	{
		self::Delete($id);
	}

	static public function DeleteAllWorkflowTasks($workflowId)
	{
		self::DeleteByWorkflow($workflowId);
	}

	static public function MarkCompleted($id, $userId)
	{
		global $DB;

		$id = intval($id);
		if ($id <= 0)
			throw new Exception("id");
		$userId = intval($userId);
		if ($userId <= 0)
			throw new Exception("userId");

		$DB->Query("DELETE FROM b_bp_task_user WHERE TASK_ID = ".intval($id)." AND USER_ID = ".intval($userId)." ", true);

		$dbRes = $DB->Query("SELECT COUNT(ID) as CNT FROM b_bp_task_user WHERE TASK_ID = ".intval($id)." ");
		$arRes = $dbRes->Fetch();
		if (intval($arRes["CNT"]) <= 0)
			$DB->Query("DELETE FROM b_bp_task WHERE ID = ".intval($id)." ", true);

		CUserCounter::Decrement($userId, 'bp_tasks', '**');

		foreach (GetModuleEvents("bizproc", "OnTaskMarkCompleted", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($id, $userId));
	}

	public static function Delete($id)
	{
		global $DB;

		$id = intval($id);
		if ($id <= 0)
			throw new Exception("id");

		$dbRes = $DB->Query("SELECT USER_ID FROM b_bp_task_user WHERE TASK_ID = ".intval($id)." ");
		while ($arRes = $dbRes->Fetch())
			CUserCounter::Decrement($arRes["USER_ID"], 'bp_tasks', '**');

		$DB->Query("DELETE FROM b_bp_task_user WHERE TASK_ID = ".intval($id)." ", true);
		$DB->Query("DELETE FROM b_bp_task WHERE ID = ".intval($id)." ", true);

		foreach (GetModuleEvents("bizproc", "OnTaskDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($id));
	}

	public static function DeleteByWorkflow($workflowId)
	{
		global $DB;

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			throw new Exception("workflowId");

		$dbRes = $DB->Query(
			"SELECT ID ".
			"FROM b_bp_task ".
			"WHERE WORKFLOW_ID = '".$DB->ForSql($workflowId)."' "
		);
		while ($arRes = $dbRes->Fetch())
		{
			$taskId = intval($arRes["ID"]);
			$dbResUser = $DB->Query("SELECT USER_ID FROM b_bp_task_user WHERE TASK_ID = ".$taskId." ");
			while ($arResUser = $dbResUser->Fetch())
				CUserCounter::Decrement($arResUser["USER_ID"], 'bp_tasks', '**');

			$DB->Query("DELETE FROM b_bp_task_user WHERE TASK_ID = ".$taskId." ", true);

			foreach (GetModuleEvents("bizproc", "OnTaskDelete", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($taskId));
		}

		$DB->Query(
			"DELETE FROM b_bp_task ".
			"WHERE WORKFLOW_ID = '".$DB->ForSql($workflowId)."' ",
			true
		);
	}

	protected static function ParseFields(&$arFields, $id = 0)
	{
		global $DB;

		$id = intval($id);
		$updateMode = ($id > 0 ? true : false);
		$addMode = !$updateMode;

		if ($addMode && !is_set($arFields, "USERS"))
			throw new Exception("USERS");

		if (is_set($arFields, "USERS"))
		{
			$arUsers = $arFields["USERS"];
			if (!is_array($arUsers))
				$arUsers = array($arUsers);

			$arFields["USERS"] = array();
			foreach ($arUsers as $userId)
			{
				$userId = intval($userId);
				if ($userId > 0 && !in_array($userId, $arFields["USERS"]))
					$arFields["USERS"][] = $userId;
			}

			if (count($arFields["USERS"]) <= 0)
				throw new Exception("arUsers");
		}

		if (is_set($arFields, "WORKFLOW_ID") || $addMode)
		{
			$arFields["WORKFLOW_ID"] = trim($arFields["WORKFLOW_ID"]);
			if (strlen($arFields["WORKFLOW_ID"]) <= 0)
				throw new Exception("WORKFLOW_ID");
		}

		if (is_set($arFields, "ACTIVITY") || $addMode)
		{
			$arFields["ACTIVITY"] = trim($arFields["ACTIVITY"]);
			if (strlen($arFields["ACTIVITY"]) <= 0)
				throw new Exception("ACTIVITY");
		}

		if (is_set($arFields, "ACTIVITY_NAME") || $addMode)
		{
			$arFields["ACTIVITY_NAME"] = trim($arFields["ACTIVITY_NAME"]);
			if (strlen($arFields["ACTIVITY_NAME"]) <= 0)
				throw new Exception("ACTIVITY_NAME");
		}

		if (is_set($arFields, "NAME") || $addMode)
		{
			$arFields["NAME"] = trim($arFields["NAME"]);
			if (strlen($arFields["NAME"]) <= 0)
				throw new Exception("NAME");

			$arFields["NAME"] = htmlspecialcharsback($arFields["NAME"]);
		}

		if (is_set($arFields, "DESCRIPTION"))
			$arFields["DESCRIPTION"] = htmlspecialcharsback($arFields["DESCRIPTION"]);

		if (is_set($arFields, "PARAMETERS"))
		{
			if ($arFields["PARAMETERS"] == null)
			{
				$arFields["PARAMETERS"] = false;
			}
			else
			{
				$arParameters = $arFields["PARAMETERS"];
				if (!is_array($arParameters))
					$arParameters = array($arParameters);
				if (count($arParameters) > 0)
					$arFields["PARAMETERS"] = serialize($arParameters);
			}
		}

		if (is_set($arFields, "OVERDUE_DATE"))
		{
			if ($arFields["OVERDUE_DATE"] == null)
				$arFields["OVERDUE_DATE"] = false;
			elseif (!$DB->IsDate($arFields["OVERDUE_DATE"], false, LANG, "FULL"))
				throw new Exception("OVERDUE_DATE");
		}
	}

	public static function OnAdminInformerInsertItems()
	{
		global $USER;

		if(!defined("BX_AUTH_FORM"))
		{
			$tasksCount = CUserCounter::GetValue($USER->GetID(), 'bp_tasks');

			if($tasksCount > 0)
			{
				$bpAIParams = array(
					"TITLE" => GetMessage("BPTS_AI_BIZ_PROC"),
					"HTML" => '<span class="adm-informer-strong-text">'.GetMessage("BPTS_AI_EX_TASKS").'</span><br>'.GetMessage("BPTS_AI_TASKS_NUM").' '.$tasksCount,
					"FOOTER" => '<a href="/bitrix/admin/bizproc_task_list.php?lang='.LANGUAGE_ID.'">'.GetMessage("BPTS_AI_TASKS_PERF").'</a>',
					"COLOR" => "red",
					"ALERT" => true
				);

				CAdminInformer::AddItem($bpAIParams);
			}
		}
	}
}

class CBPTaskResult extends CDBResult
{
	static public function __construct($res)
	{
		parent::CDBResult($res);
	}

	public static function Fetch()
	{
		$res = parent::Fetch();

		if ($res)
		{
			if (strlen($res["PARAMETERS"]) > 0)
				$res["PARAMETERS"] = unserialize($res["PARAMETERS"]);
		}

		return $res;
	}

	public function GetNext()
	{
		$res = parent::GetNext();

		if ($res)
		{
			if (strlen($res["DESCRIPTION"]) > 0)
				$res["DESCRIPTION"] = $this->ConvertBBCode($res["DESCRIPTION"]);
		}

		return $res;
	}

	public function ConvertBBCode($text)
	{
		$text = preg_replace(
			"'(?<=^|[\s.,;:!?\#\-\*\|\[\(\)\{\}]|\s)((http|https|news|ftp|aim|mailto)://[\.\-\_\:a-z0-9\@]([^\"\s\'\[\]\{\}])*)'is",
			"[url]\\1[/url]",
			$text
		);

		$text = preg_replace("#\[img\](.+?)\[/img\]#ie", "\$this->ConvertBCodeImageTag('\\1')", $text);

		$text = preg_replace(
			array(
				"/\[url\]([^\]]+?)\[\/url\]/ie".BX_UTF_PCRE_MODIFIER,
				"/\[url\s*=\s*([^\]]+?)\s*\](.*?)\[\/url\]/ie".BX_UTF_PCRE_MODIFIER
			),
			array(
				"\$this->ConvertBCodeAnchorTag('\\1', '\\1')",
				"\$this->ConvertBCodeAnchorTag('\\1', '\\2')"
			),
			$text
		);

		$text = preg_replace(
			array(
				"/\[b\](.+?)\[\/b\]/is".BX_UTF_PCRE_MODIFIER,
				"/\[i\](.+?)\[\/i\]/is".BX_UTF_PCRE_MODIFIER,
				"/\[s\](.+?)\[\/s\]/is".BX_UTF_PCRE_MODIFIER,
				"/\[u\](.+?)\[\/u\]/is".BX_UTF_PCRE_MODIFIER
			),
			array(
				"<b>\\1</b>",
				"<i>\\1</i>",
				"<s>\\1</s>",
				"<u>\\1</u>"
			),
			$text
		);

		return $text;
	}

	public static function ConvertBCodeImageTag($url = "")
	{
		$url = trim($url);
		if (strlen($url) <= 0)
			return "";

		$extension = preg_replace("/^.*\.(\S+)$/".BX_UTF_PCRE_MODIFIER, "\\1", $url);
		$extension = strtolower($extension);
		$extension = preg_quote($extension, "/");

		$bErrorIMG = False;

		if (preg_match("/[?&;]/".BX_UTF_PCRE_MODIFIER, $url))
			$bErrorIMG = True;
		if (!$bErrorIMG && !preg_match("/$extension(\||\$)/".BX_UTF_PCRE_MODIFIER, "gif|jpg|jpeg|png"))
			$bErrorIMG = True;
		if (!$bErrorIMG && !preg_match("/^((http|https|ftp)\:\/\/[-_:.a-z0-9@]+)*(\/[-_+\/=:.a-z0-9@%]+)$/i".BX_UTF_PCRE_MODIFIER, $url))
			$bErrorIMG = True;

		if ($bErrorIMG)
			return "[img]".$url."[/img]";

		return '<img src="'.$url.'" border="0" />';
	}

	public static function ConvertBCodeAnchorTag($url, $text)
	{

		$result = "";

		if ($url === $text)
		{
			$arUrl = explode(", ", $url);
			$arText = $arUrl;
		}
		else
		{
			$arUrl = array($url);
			$arText = array($text);
		}

		for ($i = 0, $n = count($arUrl); $i < $n; $i++)
		{
			$url = $arUrl[$i];
			$text = $arText[$i];

			$text = str_replace("\\\"", "\"", $text);
			$end = "";

			if (preg_match("/([\.,\?]|&#33;)$/".BX_UTF_PCRE_MODIFIER, $url, $match))
			{
				$end = $match[1];
				$url = preg_replace("/([\.,\?]|&#33;)$/".BX_UTF_PCRE_MODIFIER, "", $url);
				$text = preg_replace("/([\.,\?]|&#33;)$/".BX_UTF_PCRE_MODIFIER, "", $text);
			}

			$url = preg_replace(
				array("/&amp;/".BX_UTF_PCRE_MODIFIER, "/javascript:/i".BX_UTF_PCRE_MODIFIER),
				array("&", "java script&#58; "),
				$url
			);
			if (substr($url, 0, 1) != "/" && !preg_match("/^(http|news|https|ftp|aim|mailto)\:\/\//i".BX_UTF_PCRE_MODIFIER, $url))
				$url = 'http://'.$url;
			if (!preg_match("/^((http|https|news|ftp|aim):\/\/[-_:.a-z0-9@]+)*([^\"\'])+$/i".BX_UTF_PCRE_MODIFIER, $url))
				return $text." (".$url.")".$end;

			$text = preg_replace(
				array("/&amp;/i".BX_UTF_PCRE_MODIFIER, "/javascript:/i".BX_UTF_PCRE_MODIFIER),
				array("&", "javascript&#58; "),
				$text
			);

			if ($result !== "")
				$result .= ", ";

			$result .= "<a href=\"".$url."\" target='_blank'>".$text."</a>".$end;
		}

		return $result;
	}

}
?>