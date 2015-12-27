<?
IncludeModuleLangFile(__FILE__);

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/classes/general/runtimeservice.php");

class CBPAllTaskService
	extends CBPRuntimeService
{
	const COUNTERS_CACHE_TAG_PREFIX = 'b_bp_tasks_cnt_';

	static public function DeleteTask($id)
	{
		self::Delete($id);
	}

	static public function DeleteAllWorkflowTasks($workflowId)
	{
		self::DeleteByWorkflow($workflowId);
	}

	static public function MarkCompleted($taskId, $userId, $status = CBPTaskUserStatus::Ok)
	{
		global $DB;

		$taskId = (int)$taskId;
		if ($taskId <= 0)
			throw new Exception("id");
		$userId = (int)$userId;
		if ($userId <= 0)
			throw new Exception("userId");
		$status = (int)$status;

		$DB->Query("UPDATE b_bp_task_user SET STATUS = ".$status.", DATE_UPDATE = ".$DB->CurrentTimeFunction()." WHERE TASK_ID = ".$taskId." AND USER_ID = ".$userId, true);

		CUserCounter::Decrement($userId, 'bp_tasks', '**');

		self::onTaskChange($taskId, array(
			'USERS_STATUSES' => array($userId => $status)
		), CBPTaskChangedStatus::Update);
		foreach (GetModuleEvents("bizproc", "OnTaskMarkCompleted", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($taskId, $userId));
	}

	public static function getTaskUsers($taskId)
	{
		global $DB;

		$taskId = (array)$taskId;
		$taskId = array_map('intval', $taskId);
		$taskId = array_filter($taskId);
		if (sizeof($taskId) < 1)
			throw new Exception("taskId");

		$where = '';
		foreach ($taskId as $id)
		{
			if ($where)
				$where .= ' OR ';
			$where .= ' TASK_ID = '.$id;
		}

		$users = array();
		$iterator = $DB->Query('SELECT TU.*, U.PERSONAL_PHOTO, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.LOGIN, U.TITLE'
			.' FROM b_bp_task_user TU'
			.' INNER JOIN b_user U ON (U.ID = TU.USER_ID)'
			.' WHERE '.$where
			.' ORDER BY TU.DATE_UPDATE DESC'
		);
		while ($user = $iterator->fetch())
		{
			$users[$user['TASK_ID']][] = $user;
		}
		return $users;
	}

	/**
	 * @param string $workflowId - Internal workflow id.
	 * @param null|int $userStatus - Filter participants by status.
	 * @return array - User ids array (ex. array(1, 2, 3)).
	 * @throws Exception
	 */
	public static function getWorkflowParticipants($workflowId, $userStatus = null)
	{
		global $DB;

		if (strlen($workflowId) <= 0)
			throw new Exception('workflowId');

		$users = array();
		$iterator = $DB->Query('SELECT DISTINCT TU.USER_ID'
			.' FROM b_bp_task_user TU'
			.' INNER JOIN b_bp_task T ON (T.ID = TU.TASK_ID)'
			.' WHERE T.WORKFLOW_ID = \''.$DB->ForSql($workflowId).'\''
			.($userStatus !== null ? ' AND TU.STATUS = '.(int)$userStatus : '')
		);
		while ($user = $iterator->fetch())
		{
			$users[] = (int)$user['USER_ID'];
		}
		return $users;
	}

	public static function delegateTask($taskId, $fromUserId, $toUserId)
	{
		global $DB;
		$taskId = (int)$taskId;
		$fromUserId = (int)$fromUserId;
		$toUserId = (int)$toUserId;

		if (!$taskId || !$fromUserId || !$toUserId)
			return false;

		$originalUserId = 0;

		//check ORIGINAL_USER_ID
		$iterator = $DB->Query('SELECT ORIGINAL_USER_ID'
			.' FROM b_bp_task_user'
			.' WHERE TASK_ID = '.$taskId.' AND USER_ID = '.$fromUserId
		);
		$row = $iterator->fetch();
		if (!empty($row['ORIGINAL_USER_ID']))
			$originalUserId = $row['ORIGINAL_USER_ID'];

		// check USER_ID (USER_ID must be unique for task)
		$iterator = $DB->Query('SELECT USER_ID'
			.' FROM b_bp_task_user'
			.' WHERE TASK_ID = '.$taskId.' AND USER_ID = '.$toUserId
		);
		$row = $iterator->fetch();
		if (!empty($row['USER_ID']))
			return false;

		$DB->Query("UPDATE b_bp_task_user SET USER_ID = "
			.$toUserId
			.(!$originalUserId? ', ORIGINAL_USER_ID = '.$fromUserId : '')
			." WHERE TASK_ID = ".$taskId." AND USER_ID = ".$fromUserId, true);
		CUserCounter::Decrement($fromUserId, 'bp_tasks', '**');
		CUserCounter::Increment($toUserId, 'bp_tasks', '**');
		self::onTaskChange($taskId, array(
			'USERS' => array($toUserId),
			'USERS_REMOVED' => array($fromUserId)
		), CBPTaskChangedStatus::Delegate);
		return true;
	}

	public static function getOriginalTaskUserId($taskId, $realUserId)
	{
		global $DB;
		$taskId = (int)$taskId;
		$realUserId = (int)$realUserId;

		$iterator = $DB->Query('SELECT ORIGINAL_USER_ID'
			.' FROM b_bp_task_user'
			.' WHERE TASK_ID = '.$taskId.' AND USER_ID = '.$realUserId
		);
		if ($row = $iterator->fetch())
		{
			return $row['ORIGINAL_USER_ID'] > 0 ? $row['ORIGINAL_USER_ID'] : $realUserId;
		}
		return false;
	}

	public static function Delete($id)
	{
		global $DB;

		$id = intval($id);
		if ($id <= 0)
			throw new Exception("id");

		$removedUsers = array();
		$dbRes = $DB->Query("SELECT USER_ID, STATUS FROM b_bp_task_user WHERE TASK_ID = ".intval($id)." ");
		while ($arRes = $dbRes->Fetch())
		{
			if ($arRes['STATUS'] == CBPTaskUserStatus::Waiting)
				CUserCounter::Decrement($arRes["USER_ID"], 'bp_tasks', '**');
			$removedUsers[] = $arRes["USER_ID"];
		}
		$DB->Query("DELETE FROM b_bp_task_user WHERE TASK_ID = ".intval($id)." ", true);
		$DB->Query("DELETE FROM b_bp_task WHERE ID = ".intval($id)." ", true);

		self::onTaskChange($id, array('USERS_REMOVED' => $removedUsers), CBPTaskChangedStatus::Delete);
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
			$removedUsers = array();
			$dbResUser = $DB->Query("SELECT USER_ID, STATUS FROM b_bp_task_user WHERE TASK_ID = ".$taskId." ");
			while ($arResUser = $dbResUser->Fetch())
			{
				if ($arResUser['STATUS'] == CBPTaskUserStatus::Waiting)
					CUserCounter::Decrement($arResUser["USER_ID"], 'bp_tasks', '**');
				$removedUsers[] = $arResUser['USER_ID'];
			}
			$DB->Query("DELETE FROM b_bp_task_user WHERE TASK_ID = ".$taskId." ", true);

			self::onTaskChange($taskId, array('USERS_REMOVED' => $removedUsers), CBPTaskChangedStatus::Delete);
			foreach (GetModuleEvents("bizproc", "OnTaskDelete", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($taskId));
		}

		$DB->Query(
			"DELETE FROM b_bp_task ".
			"WHERE WORKFLOW_ID = '".$DB->ForSql($workflowId)."' ",
			true
		);
	}

	public static function getCounters($userId)
	{
		global $DB;

		$counters = array('*' => 0);
		$cache = \Bitrix\Main\Application::getInstance()->getManagedCache();
		$cacheTag = self::COUNTERS_CACHE_TAG_PREFIX.$userId;
		if ($cache->read(3600*24*7, $cacheTag))
		{
			$counters = (array) $cache->get($cacheTag);
		}
		else
		{
			$query =
				"SELECT WS.MODULE_ID AS MODULE_ID, WS.ENTITY AS ENTITY, COUNT('x') AS CNT ".
				'FROM b_bp_task T '.
				'	INNER JOIN b_bp_task_user TU ON (T.ID = TU.TASK_ID) '.
				'	INNER JOIN b_bp_workflow_state WS ON (T.WORKFLOW_ID = WS.ID) '.
				'WHERE TU.STATUS = '.(int)CBPTaskUserStatus::Waiting.' '.
				'	AND TU.USER_ID = '.(int)$userId.' '.
				'GROUP BY MODULE_ID, ENTITY';

			$iterator = $DB->Query($query, true);
			if ($iterator)
			{
				while ($row = $iterator->fetch())
				{
					$cnt = (int)$row['CNT'];
					$counters[$row['MODULE_ID']][$row['ENTITY']] = $cnt;
					if (!isset($counters[$row['MODULE_ID']]['*']))
						$counters[$row['MODULE_ID']]['*'] = 0;
					$counters[$row['MODULE_ID']]['*'] += $cnt;
					$counters['*'] += $cnt;
				}
				$cache->set($cacheTag, $counters);
			}
		}
		return $counters;
	}

	protected static function onTaskChange($taskId, $taskData, $status)
	{
		$workflowId = isset($taskData['WORKFLOW_ID']) ? $taskData['WORKFLOW_ID'] : null;
		if (!$workflowId)
		{
			$iterator = CBPTaskService::GetList(array('ID'=>'DESC'), array('ID' => $taskId), false, false, array('WORKFLOW_ID'));
			$row = $iterator->fetch();
			if (!$row)
				return false;
			$workflowId = $row['WORKFLOW_ID'];
			$taskData['WORKFLOW_ID'] = $workflowId;
		}

		//clean counters cache
		$users = array();
		if (!empty($taskData['USERS']))
			$users = $taskData['USERS'];
		if (!empty($taskData['USERS_REMOVED']))
			$users = array_merge($users, $taskData['USERS_REMOVED']);
		if (!empty($taskData['USERS_STATUSES']))
			$users = array_merge($users, array_keys($taskData['USERS_STATUSES']));
		self::cleanCountersCache($users);

		//ping document
		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();
		$documentId = CBPStateService::GetStateDocumentId($workflowId);
		if ($documentId)
		{
			$documentService = $runtime->GetService('DocumentService');
			try
			{
				$documentService->onTaskChange($documentId, $taskId, $taskData, $status);
			}
			catch (Exception $e)
			{

			}
		}
		return true;
	}

	protected static function cleanCountersCache($users)
	{
		$users = (array) $users;
		$users = array_unique($users);
		$cache = \Bitrix\Main\Application::getInstance()->getManagedCache();
		foreach ($users as $userId)
		{
			$cache->clean(self::COUNTERS_CACHE_TAG_PREFIX.$userId);
		}
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

	public static function ConvertBBCode($text)
	{
		$text = preg_replace(
			"'(?<=^|[\s.,;:!?\#\-\*\|\[\(\)\{\}]|\s)((http|https|news|ftp|aim|mailto)://[\.\-\_\:a-z0-9\@]([^\"\s\'\[\]\{\}])*)'is",
			"[url]\\1[/url]",
			$text
		);

		$text = preg_replace_callback("#\[img\](.+?)\[/img\]#i", array($this, "ConvertBCodeImageTag"), $text);

		$text = preg_replace_callback(
			"/\[url\]([^\]]+?)\[\/url\]/i".BX_UTF_PCRE_MODIFIER,
			array($this, "ConvertBCodeAnchorTag"),
			$text
		);
		$text = preg_replace_callback(
			"/\[url\s*=\s*([^\]]+?)\s*\](.*?)\[\/url\]/i".BX_UTF_PCRE_MODIFIER,
			array($this, "ConvertBCodeAnchorTag"),
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
		if (is_array($url))
			$url = $url[1];
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

	public static function ConvertBCodeAnchorTag($url, $text = '')
	{
		if (is_array($url))
		{
			$text = isset($url[2]) ? $url[2] : $url[1];
			$url = $url[1];
		}

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