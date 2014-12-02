<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Im as IM;

class CIMChat
{
	private $user_id = 0;
	private $bHideLink = false;
	public $lastAvatarId = 0;

	public function __construct($user_id = false, $arParams = Array())
	{
		global $USER;
		$this->user_id = intval($user_id);
		if ($this->user_id == 0)
			$this->user_id = IntVal($USER->GetID());
		if (isset($arParams['hide_link']) && $arParams['hide_link'] == true)
			$this->bHideLink = true;
	}

	public function GetMessage($ID)
	{
		global $DB;

		$ID = intval($ID);

		$strSql = "SELECT M.* FROM b_im_relation R, b_im_message M WHERE M.ID = ".$ID." AND R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE = '".IM_MESSAGE_GROUP."' AND R.CHAT_ID = M.CHAT_ID";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
			return $arRes;

		return false;
	}

	public function GetLastMessage($toChatId, $fromUserId = false, $loadExtraData = false, $bTimeZone = true, $limit = true)
	{
		global $DB;

		$fromUserId = IntVal($fromUserId);
		if ($fromUserId <= 0)
			$fromUserId = $this->user_id;

		$toChatId = IntVal($toChatId);
		if ($toChatId <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_EMPTY_CHAT_ID"), "ERROR_TO_CHAT_ID");
			return false;
		}

		if ($limit)
		{
			$dbType = strtolower($DB->type);
			if ($dbType== "mysql")
				$sqlLimit = " AND M.DATE_CREATE > DATE_SUB(NOW(), INTERVAL 30 DAY)";
			else if ($dbType == "mssql")
				$sqlLimit = " AND M.DATE_CREATE > dateadd(day, -30, getdate())";
			else if ($dbType == "oracle")
				$sqlLimit = " AND M.DATE_CREATE > SYSDATE-30";
		}

		if (!$bTimeZone)
			CTimeZone::Disable();
		$strSql = "
			SELECT
				M.ID,
				M.CHAT_ID,
				M.MESSAGE,
				".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
				M.AUTHOR_ID
			FROM b_im_message M
			INNER JOIN b_im_relation R1 ON R1.START_ID <= M.ID AND M.CHAT_ID = R1.CHAT_ID
			WHERE R1.CHAT_ID = ".$toChatId." AND R1.USER_ID = ".$fromUserId." #LIMIT#
			ORDER BY M.DATE_CREATE DESC
		";
		$strSql = $DB->TopSql($strSql, 20);
		if (!$bTimeZone)
			CTimeZone::Enable();

		if ($limit)
		{
			$dbRes = $DB->Query(str_replace("#LIMIT#", $sqlLimit, $strSql), false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if (!$dbRes->SelectedRowsCount())
				$dbRes = $DB->Query(str_replace("#LIMIT#", "", $strSql), false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		else
		{
			$dbRes = $DB->Query(str_replace("#LIMIT#", "", $strSql), false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		CIMStatus::Set($fromUserId, Array('IDLE' => null));

		$arMessages = Array();
		$arMessageId = Array();
		$arUsersMessage = Array();
		$CCTP = new CTextParser();
		$CCTP->MaxStringLen = 200;
		$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => $this->bHideLink? "N": "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
		while ($arRes = $dbRes->Fetch())
		{
			$arMessages[$arRes['ID']] = Array(
				'id' => $arRes['ID'],
				'chatId' => $arRes['CHAT_ID'],
				'senderId' => $arRes['AUTHOR_ID'],
				'recipientId' => $arRes['CHAT_ID'],
				'date' => $arRes['DATE_CREATE'],
				'text' => $CCTP->convertText(htmlspecialcharsbx($arRes['MESSAGE']))
			);

			$arMessageId[] = $arRes['ID'];
			$arUsersMessage[$arRes['CHAT_ID']][] = $arRes['ID'];
		}
		$params = CIMMessageParam::Get($arMessageId);

		$arFiles = Array();
		foreach ($params as $messageId => $param)
		{
			$arMessages[$messageId]['params'] = $param;
			if (isset($param['FILE_ID']))
			{
				foreach ($param['FILE_ID'] as $fileId)
				{
					$arFiles[$fileId] = $fileId;
				}
			}
		}
		$arChatFiles = CIMDisk::GetFiles($toChatId, $arFiles);

		$arResult = Array(
			'chatId' => $toChatId,
			'message' => $arMessages,
			'usersMessage' => $arUsersMessage,
			'users' => Array(),
			'userInGroup' => Array(),
			'woUserInGroup' => Array(),
			'files' => $arChatFiles
		);

		if (is_array($loadExtraData) || is_bool($loadExtraData) && $loadExtraData == true)
		{
			$bDepartment = true;
			if (is_array($loadExtraData) && $loadExtraData['DEPARTMENT'] == 'N')
				$bDepartment = false;

			$arChat = self::GetChatData(array(
				'ID' => $toChatId,
				'USE_CACHE' => 'N'
			));
			if (in_array($fromUserId, $arChat['userInChat'][$toChatId]))
			{
				$arResult['chat'] = $arChat['chat'];
				$arResult['userInChat']  = $arChat['userInChat'];
				$arResult['userChatBlockStatus'] = $arChat['userChatBlockStatus'];

				$ar = CIMContactList::GetUserData(array(
						'ID' => $arChat['userInChat'][$toChatId],
						'DEPARTMENT' => ($bDepartment? 'Y': 'N'),
						'USE_CACHE' => 'N'
					)
				);
				$arResult['users'] = $ar['users'];
				$arResult['userInGroup']  = $ar['userInGroup'];
				$arResult['woUserInGroup']  = $ar['woUserInGroup'];
			}
		}

		return $arResult;
	}

	public function GetLastSendMessage($arParams)
	{
		global $DB;

		if (!isset($arParams['CHAT_ID']))
			return false;

		$chatId = $arParams['CHAT_ID'];
		$fromUserId = isset($arParams['FROM_USER_ID']) && IntVal($arParams['FROM_USER_ID'])>0? IntVal($arParams['FROM_USER_ID']): $this->user_id;
		$limit = isset($arParams['LIMIT']) && IntVal($arParams['LIMIT'])>0? IntVal($arParams['LIMIT']): false;
		$bLoadChatInfo = isset($arParams['LOAD_CHAT_INFO']) && $arParams['LOAD_CHAT_INFO'] == 'Y'? true: false;
		$bTimeZone = isset($arParams['USE_TIME_ZONE']) && $arParams['USE_TIME_ZONE'] == 'N'? false: true;

		$arChatId = Array();
		if (is_array($chatId))
		{
			foreach ($chatId as $val)
				$arChatId[] = intval($val);
		}
		else
		{
			$arChatId[] = intval($chatId);
		}
		if (empty($arChatId))
			return Array();

		$sqlLimit = '';
		if ($limit)
		{
			$dbType = strtolower($DB->type);
			if ($dbType== "mysql")
				$sqlLimit = " AND M.DATE_CREATE > DATE_SUB(NOW(), INTERVAL ".$limit." DAY)";
			else if ($dbType == "mssql")
				$sqlLimit = " AND M.DATE_CREATE > dateadd(day, -".$limit.", getdate())";
			else if ($dbType == "oracle")
				$sqlLimit = " AND M.DATE_CREATE > SYSDATE-".$limit;
		}
		if (!$bTimeZone)
			CTimeZone::Disable();
		$strSql = "
			SELECT
				M1.ID,
				M1.CHAT_ID,
				M1.MESSAGE,
				".$DB->DatetimeToTimestampFunction('M1.DATE_CREATE')." DATE_CREATE,
				M1.AUTHOR_ID
				".($bLoadChatInfo? ", C.TITLE CHAT_TITLE": "")."
			FROM b_im_message M1
			INNER JOIN (
				SELECT MAX(M.ID) MAX_ID
				FROM b_im_relation R1
				INNER JOIN b_im_message M ON M.ID >= R1.START_ID AND M.ID >= R1.LAST_ID AND M.CHAT_ID = R1.CHAT_ID
				WHERE R1.CHAT_ID IN (".implode(",",$arChatId).") AND R1.USER_ID = ".$fromUserId." ".$sqlLimit."
				GROUP BY M.CHAT_ID
			) MM ON M1.ID = MM.MAX_ID
			".($bLoadChatInfo? " LEFT JOIN b_im_chat C ON M1.CHAT_ID = C.ID": "");
		if (!$bTimeZone)
			CTimeZone::Enable();

		$arMessages = Array();
		$CCTP = new CTextParser();
		$CCTP->MaxStringLen = 200;
		$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => $this->bHideLink? "N": "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
		{
			$arMessages[$arRes['CHAT_ID']] = Array(
				'id' => $arRes['ID'],
				'senderId' => $arRes['AUTHOR_ID'],
				'recipientId' => $arRes['CHAT_ID'],
				'date' => $arRes['DATE_CREATE'],
				'text' => $CCTP->convertText(htmlspecialcharsbx($arRes['MESSAGE']))
			);
			if ($bLoadChatInfo)
				$arMessages[$arRes['CHAT_ID']]['chatTitle'] = $arRes['CHAT_TITLE'];
		}

		return $arMessages;
	}

	public static function GetRelationById($ID, $userId = false)
	{
		global $DB;

		$ID = intval($ID);
		$userId = intval($userId);
		$arResult = Array();

		$strSql = "
			SELECT R.*
			FROM b_im_relation R
			WHERE R.CHAT_ID = ".$ID." ".($userId>0? "AND R.USER_ID = ".$userId: "");
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
			$arResult[$arRes['USER_ID']] = $arRes;

		if ($userId > 0)
			$arResult = isset($arResult[$userId])? $arResult[$userId]: false;

		return $arResult;
	}

	static function GetChatData($arParams = Array())
	{
		global $DB;

		$arFilter = Array();
		if (isset($arParams['ID']) && is_array($arParams['ID']))
		{
			foreach ($arParams['ID'] as $key => $value)
				$arFilter['ID'][$key] = intval($value);
		}
		else if (isset($arParams['ID']) && intval($arParams['ID']) > 0)
		{
			$arFilter['ID'][] = intval($arParams['ID']);
		}

		if (empty($arFilter['ID']))
			return false;

		$innerJoin = $whereUser = "";
		if (isset($arParams['USER_ID']))
		{
			$innerJoin = "INNER JOIN b_im_relation R2 ON R1.CHAT_ID = R2.CHAT_ID";
			$whereUser = "and R2.USER_ID = ".intval($arParams['USER_ID']);
		}

		$strSql = "
			SELECT
				C.ID CHAT_ID,
				C.TITLE CHAT_TITLE,
				C.CALL_TYPE CHAT_CALL_TYPE,
				C.AUTHOR_ID CHAT_OWNER_ID,
				C.CALL_NUMBER CHAT_CALL_NUMBER,
				R1.NOTIFY_BLOCK RELATION_BLOCK_NOTIFY,
				R1.USER_ID RELATION_USER_ID,
				R1.CALL_STATUS,
				R1.MESSAGE_TYPE CHAT_TYPE,
				C.AVATAR,
				C.ENTITY_TYPE,
				C.ENTITY_ID
			FROM b_im_relation R1 LEFT JOIN b_im_chat C ON R1.CHAT_ID = C.ID
			".$innerJoin."
			WHERE R1.CHAT_ID IN (".implode(',', $arFilter['ID']).") ".$whereUser."
		";

		$arChat = Array();
		$arUserInChat = Array();
		$arUserCallStatus = Array();
		$arUserChatBlockStatus = Array();
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->GetNext(true, false))
		{
			if (!isset($arChat[$arRes["CHAT_ID"]]))
			{
				$avatar = '/bitrix/js/im/images/blank.gif';
				if (intval($arRes["AVATAR"]) > 0)
				{
					$avatar = self::GetAvatarImage($arRes["AVATAR"]);
				}
				$arChat[$arRes["CHAT_ID"]] = Array(
					'id' => $arRes["CHAT_ID"],
					'name' => $arRes["CHAT_TITLE"],
					'owner' => $arRes["CHAT_OWNER_ID"],
					'avatar' => $avatar,
					'call' => trim($arRes["CHAT_CALL_TYPE"]),
					'call_number' => trim($arRes["CHAT_CALL_NUMBER"]),
					'call_entity_type' => trim($arRes["ENTITY_TYPE"]),
					'call_entity_id' => trim($arRes["ENTITY_ID"]),
					'type' => trim($arRes["CHAT_TYPE"]),
					'style' => $arRes["ENTITY_TYPE"] == 'CALL'? 'call': (trim($arRes["CHAT_TYPE"]) == IM_MESSAGE_PRIVATE? 'private': 'group'),
				);
			}
			$arUserInChat[$arRes["CHAT_ID"]][] = $arRes["RELATION_USER_ID"];
			$arUserCallStatus[$arRes["CHAT_ID"]][$arRes["RELATION_USER_ID"]] = trim($arRes["CALL_STATUS"]);
			if ($arRes["RELATION_BLOCK_NOTIFY"] != 'N')
				$arUserChatBlockStatus[$arRes["CHAT_ID"]][$arRes["RELATION_USER_ID"]] = $arRes["RELATION_BLOCK_NOTIFY"];
		}

		$result = array('chat' => $arChat, 'userInChat' => $arUserInChat, 'userCallStatus' => $arUserCallStatus, 'userChatBlockStatus' => $arUserChatBlockStatus);

		return $result;
	}

	public function SetReadMessage($chatId, $lastId = null)
	{
		global $DB;

		$chatId = intval($chatId);
		if ($chatId <= 0)
			return false;

		$sqlLastId = '';
		if (intval($lastId) > 0)
			$sqlLastId = "AND M.ID <= ".intval($lastId);

		$strSql = "
			SELECT COUNT(M.ID) CNT, MAX(M.ID) ID, M.CHAT_ID
			FROM b_im_message M
			INNER JOIN b_im_relation R1 ON M.ID > R1.LAST_ID ".$sqlLastId." AND M.CHAT_ID = R1.CHAT_ID
			WHERE R1.CHAT_ID = ".$chatId." AND R1.USER_ID = ".$this->user_id."
			GROUP BY M.CHAT_ID
		";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$bReadMessage = CIMMessage::SetLastId($chatId, $this->user_id, $arRes['ID']);
			if ($bReadMessage)
			{
				//CUserCounter::Decrement($this->user_id, 'im_chat', '**', false, $arRes['CNT']);
				CIMMessenger::SpeedFileDelete($this->user_id, IM_SPEED_GROUP);
				if (CModule::IncludeModule("pull"))
				{
					CPushManager::DeleteFromQueueBySubTag($this->user_id, 'IM_GROUP');
					CPullStack::AddByUser($this->user_id, Array(
						'module_id' => 'im',
						'command' => 'readMessageChat',
						'params' => Array(
							'chatId' => $chatId,
							'lastId' => $arRes['ID'],
							'count' => $arRes['CNT']
						),
					));
					//CIMMessenger::SendBadges($this->user_id);
				}
				return true;
			}
		}

		return false;
	}

	public function GetUnreadMessage($arParams = Array())
	{
		global $DB;

		$bSpeedCheck = isset($arParams['SPEED_CHECK']) && $arParams['SPEED_CHECK'] == 'N'? false: true;
		$lastId = !isset($arParams['LAST_ID']) || $arParams['LAST_ID'] == null? null: IntVal($arParams['LAST_ID']);
		$order = isset($arParams['ORDER']) && $arParams['ORDER'] == 'ASC'? 'ASC': 'DESC';
		$loadDepartment = isset($arParams['LOAD_DEPARTMENT']) && $arParams['LOAD_DEPARTMENT'] == 'N'? false: true;
		$bTimeZone = isset($arParams['USE_TIME_ZONE']) && $arParams['USE_TIME_ZONE'] == 'N'? false: true;
		$bGroupByChat = isset($arParams['GROUP_BY_CHAT']) && $arParams['GROUP_BY_CHAT'] == 'Y'? true: false;
		$bUserLoad = isset($arParams['USER_LOAD']) && $arParams['USER_LOAD'] == 'N'? false: true;
		$bFileLoad = isset($arParams['FILE_LOAD']) && $arParams['FILE_LOAD'] == 'N'? false: true;
		$arExistUserData = isset($arParams['EXIST_USER_DATA']) && is_array($arParams['EXIST_USER_DATA'])? $arParams['EXIST_USER_DATA']: Array();

		$arMessages = Array();
		$arUnreadMessage = Array();
		$arUsersMessage = Array();

		$arResult = Array(
			'message' => Array(),
			'unreadMessage' => Array(),
			'usersMessage' => Array(),
			'users' => Array(),
			'userInGroup' => Array(),
			'woUserInGroup' => Array(),
			'files' => Array(),
			'countMessage' => 0,
			'chat' => Array(),
			'userChatBlockStatus' => Array(),
			'userInChat' => Array(),
			'result' => false
		);
		$bLoadMessage = $bSpeedCheck? CIMMessenger::SpeedFileExists($this->user_id, IM_SPEED_GROUP): false;
		$count = CIMMessenger::SpeedFileGet($this->user_id, IM_SPEED_GROUP);
		if (!$bLoadMessage || ($bLoadMessage && intval($count) > 0))
		{

			$ssqlLastId = "R1.LAST_ID";
			$ssqlStatus = " AND R1.STATUS < ".IM_STATUS_READ;
			if (!is_null($lastId) && intval($lastId) > 0 && !CIMMessenger::CheckXmppStatusOnline())
			{
				$ssqlLastId = intval($lastId);
				$ssqlStatus = "";
			}

			if (!$bTimeZone)
				CTimeZone::Disable();
			$strSql ="
				SELECT
					M.ID,
					M.CHAT_ID,
					M.MESSAGE,
					".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
					M.AUTHOR_ID,
					R1.STATUS R1_STATUS
				FROM b_im_message M
				INNER JOIN b_im_relation R1 ON M.ID > ".$ssqlLastId." AND M.CHAT_ID = R1.CHAT_ID AND R1.USER_ID != M.AUTHOR_ID
				WHERE R1.USER_ID = ".$this->user_id." AND R1.MESSAGE_TYPE = '".IM_MESSAGE_GROUP."' ".$ssqlStatus."
			";
			if (!$bTimeZone)
				CTimeZone::Enable();
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$arMessageId = Array();
			$arMessageChatId = Array();
			$arLastMessage = Array();
			$arMark = Array();
			$arChat = Array();
			$CCTP = new CTextParser();
			$CCTP->MaxStringLen = 200;
			$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => $this->bHideLink? "N": "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");

			while ($arRes = $dbRes->Fetch())
			{
				$arUsers[] = $arRes['AUTHOR_ID'];

				$arMessages[$arRes['ID']] = Array(
					'id' => $arRes['ID'],
					'chatId' => $arRes['CHAT_ID'],
					'senderId' => $arRes['AUTHOR_ID'],
					'recipientId' => $arRes['CHAT_ID'],
					'date' => $arRes['DATE_CREATE'],
					'text' => $arRes['MESSAGE'],
				);
				if ($bGroupByChat)
				{
					$arMessages[$arRes['ID']]['conversation'] = $arRes['CHAT_ID'];
					$arMessages[$arRes['ID']]['unread'] = $this->user_id != $arRes['AUTHOR_ID']? 'Y': 'N';
				}
				else
				{
					$arUsersMessage[$arRes['CHAT_ID']][] = $arRes['ID'];
					if ($this->user_id != $arRes['AUTHOR_ID'])
						$arUnreadMessage[$arRes['CHAT_ID']][] = $arRes['ID'];
				}

				if ($arRes['R1_STATUS'] == IM_STATUS_UNREAD && (!isset($arMark[$arRes["CHAT_ID"]]) || $arMark[$arRes["CHAT_ID"]] < $arRes["ID"]))
					$arMark[$arRes["CHAT_ID"]] = $arRes["ID"];

				if (!isset($arLastMessage[$arRes["CHAT_ID"]]) || $arLastMessage[$arRes["CHAT_ID"]] < $arRes["ID"])
					$arLastMessage[$arRes["CHAT_ID"]] = $arRes["ID"];

				$arChat[$arRes["CHAT_ID"]] = $arRes["CHAT_ID"];
				$arMessageId[] = $arRes['ID'];
				$arMessageChatId[$arRes['CHAT_ID']][$arRes["ID"]] = $arRes["ID"];
			}
			$params = CIMMessageParam::Get($arMessageId);

			if ($bFileLoad)
			{
				foreach ($arMessageChatId as $chatId => $messages)
				{
					$files = Array();
					foreach ($messages as $messageId)
					{
						$arMessages[$messageId]['params'] = $params[$messageId];

						if (isset($params[$messageId]['FILE_ID']))
						{
							foreach ($params[$messageId]['FILE_ID'] as $fileId)
							{
								$files[$fileId] = $fileId;
							}
						}
					}

					$arMessageFiles = CIMDisk::GetFiles($chatId, $files);
					foreach ($arMessageFiles as $key => $value)
					{
						$arResult['files'][$chatId][$key] = $value;
					}
				}
			}
			else
			{
				foreach ($params as $messageId => $param)
				{
					$arMessages[$messageId]['params'] = $param;
				}
			}

			foreach ($arMark as $chatId => $lastSendId)
				CIMMessage::SetLastSendId($chatId, $this->user_id, $lastSendId);

			if ($bGroupByChat)
			{
				foreach ($arMessages as $key => $value)
				{
					$arMessages[$arLastMessage[$value['conversation']]]['counter']++;
					if ($arLastMessage[$value['conversation']] != $value['id'])
					{
						unset($arMessages[$key]);
					}
					else
					{
						$arMessages[$key]['text'] = $CCTP->convertText(htmlspecialcharsbx($value['text']));
						$arMessages[$key]['text_mobile'] = strip_tags(preg_replace("/<img.*?data-code=\"([^\"]*)\".*?>/i", "$1", $CCTP->convertText(htmlspecialcharsbx(preg_replace("/\[s\].*?\[\/s\]/i", "", $value['text'])))) , '<br>');

						$arUsersMessage[$value['conversation']][] = $value['id'];

						if ($value['unread'] == 'Y')
							$arUnreadMessage[$value['conversation']][] = $value['id'];

						unset($arMessages[$key]['conversation']);
						unset($arMessages[$key]['unread']);
					}
				}
			}
			else
			{
				foreach ($arMessages as $key => $value)
				{
					$arMessages[$key]['text'] = $CCTP->convertText(htmlspecialcharsbx($value['text']));
					$arMessages[$key]['text_mobile'] = strip_tags(preg_replace("/<img.*?data-code=\"([^\"]*)\".*?>/i", "$1", $CCTP->convertText(htmlspecialcharsbx(preg_replace("/\[s\].*?\[\/s\]/i", "", $value['text'])))) , '<br>');
				}
			}

			$arResult['message'] = $arMessages;
			$arResult['unreadMessage'] = $arUnreadMessage;
			$arResult['usersMessage'] = $arUsersMessage;

			$arChat = self::GetChatData(array(
				'ID' => $arChat,
				'USE_CACHE' => 'N'
			));
			if (!empty($arChat))
			{
				$arResult['chat'] = $arChat['chat'];
				$arResult['userChatBlockStatus'] = $arChat['userChatBlockStatus'];
				$arResult['userInChat']  = $arChat['userInChat'];

				foreach ($arChat['userInChat'] as $value)
					$arUsers[] = $value;
			}

			if ($bUserLoad && !empty($arUsers))
			{
				$arUserData = CIMContactList::GetUserData(Array('ID' => array_diff(array_unique($arUsers), $arExistUserData), 'DEPARTMENT' => ($loadDepartment? 'Y': 'N')));
				$arResult['users'] = $arUserData['users'];
				$arResult['userInGroup'] = $arUserData['userInGroup'];
				$arResult['woUserInGroup'] = $arUserData['woUserInGroup'];
			}
			else
			{
				$arResult['users'] = Array();
				$arResult['userInGroup'] = Array();
				$arResult['userInGroup'] = Array();
			}

			$arResult['countMessage'] = CIMMessenger::GetMessageCounter($this->user_id, $arResult);
			if (!$bGroupByChat)
				CIMMessenger::SpeedFileCreate($this->user_id, $arResult['countMessage'], IM_SPEED_GROUP);
			$arResult['result'] = true;
		}
		else
		{
			$arResult['countMessage'] = CIMMessenger::GetMessageCounter($this->user_id, $arResult);
		}

		return $arResult;
	}

	public function Rename($chatId, $title)
	{
		global $DB;
		$chatId = intval($chatId);
		$title = trim($title);
		if ($chatId <= 0 || strlen($title) <= 0)
			return false;

		$strSql = "
			SELECT R.CHAT_ID, C.TITLE CHAT_TITLE, C.AUTHOR_ID CHAT_AUTHOR_ID
			FROM b_im_relation R LEFT JOIN b_im_chat C ON R.CHAT_ID = C.ID
			WHERE R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE = '".IM_MESSAGE_GROUP."' AND R.CHAT_ID = ".$chatId;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			if ($arRes['CHAT_TITLE'] == $title)
				return false;

			$strSql = "UPDATE b_im_chat SET TITLE = '".$DB->ForSQL($title)."' WHERE ID = ".$chatId;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			CIMChat::AddSystemMessage(Array(
				'CHAT_ID' => $chatId,
				'USER_ID' => $this->user_id,
				'MESSAGE_CODE' => 'IM_CHAT_CHANGE_TITLE_',
				'MESSAGE_REPLACE' => Array('#CHAT_TITLE#' => $title)
			));

			if (CModule::IncludeModule("pull"))
			{
				$ar = CIMChat::GetRelationById($chatId);
				foreach ($ar as $rel)
				{
					CPullStack::AddByUser($rel['USER_ID'], Array(
						'module_id' => 'im',
						'command' => 'chatRename',
						'params' => Array(
							'chatId' => $chatId,
							'chatTitle' => htmlspecialcharsbx($title),
						),
					));
				}
			}

			return true;
		}
		return false;
	}

	public function Add($arParams)
	{
		global $DB;

		$chatTitle = '';
		if (isset($arParams['TITLE']))
			$chatTitle = trim($arParams['TITLE']);

		$userId = Array();
		if (isset($arParams['USERS']))
			$userId = $arParams['USERS'];

		$callNumber = '';
		if (isset($arParams['CALL_NUMBER']))
			$callNumber = $arParams['CALL_NUMBER'];

		$entityType = '';
		if (isset($arParams['ENTITY_TYPE']))
			$entityType = $arParams['ENTITY_TYPE'];

		$entityId = '';
		if (isset($arParams['ENTITY_ID']))
			$entityId = $arParams['ENTITY_ID'];

		$skipUserAdd = false;
		if ($userId === false)
		{
			$skipUserAdd = true;
		}

		$arUserId = Array();
		$arUserId[$this->user_id] = $this->user_id;
		if (is_array($userId))
		{
			foreach ($userId as $value)
				$arUserId[intval($value)] = intval($value);
		}
		else if (intval($userId) > 0)
		{
			$arUserId[intval($userId)] = intval($userId);
		}

		if (!$skipUserAdd)
		{
			if (count($arUserId) <= 2)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MIN_USER"), "MIN_USER");
				return false;
			}

			if (count($arUserId) > 100)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MAX_USER", Array('#COUNT#' => 100)), "MAX_USER");
				return false;
			}

			if (!IsModuleInstalled('intranet') && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed())
			{
				global $USER;

				$arFriendUsers = Array();
				$dbFriends = CSocNetUserRelations::GetList(array(),array("USER_ID" => $USER->GetID(), "RELATION" => SONET_RELATIONS_FRIEND), false, false, array("ID", "FIRST_USER_ID", "SECOND_USER_ID", "DATE_CREATE", "DATE_UPDATE", "INITIATED_BY"));
				while ($arFriends = $dbFriends->Fetch())
				{
					$friendId = $USER->GetID() == $arFriends["FIRST_USER_ID"]? $arFriends["SECOND_USER_ID"]: $arFriends["FIRST_USER_ID"];
					$arFriendUsers[$friendId] = $friendId;
				}
				foreach ($arUserId as $id => $userId)
				{
					if ($userId == $USER->GetID())
						continue;

					if (!isset($arFriendUsers[$userId]) && CIMSettings::GetPrivacy(CIMSettings::PRIVACY_CHAT, $userId) == CIMSettings::PRIVACY_RESULT_CONTACT)
						unset($arUserId[$id]);
				}

				if (count($arUserId) <= 2)
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MIN_USER_BY_PRIVACY"), "MIN_USER_BY_PRIVACY");
					return false;
				}
			}
		}

		$arUsers = CIMContactList::GetUserData(array(
			'ID' => array_values($arUserId),
			'DEPARTMENT' => 'N',
			'USE_CACHE' => 'N'
		));
		$arUsers = $arUsers['users'];

		$arUsersName = Array();
		if ($chatTitle == "")
		{
			foreach ($arUserId as $userId)
				$arUsersName[$userId] = htmlspecialcharsback($arUsers[$userId]['name']);

			$chatTitle = implode(', ', $arUsersName);
		}

		$result = IM\ChatTable::add(Array(
			"TITLE"	=> substr($chatTitle, 0, 255),
			"AUTHOR_ID"	=> $this->user_id,
			"ENTITY_TYPE" => $entityType,
			"ENTITY_ID" => $entityId,
			"CALL_NUMBER" => $callNumber,
		));
		$chatId = $result->getId();
		if ($chatId > 0)
		{
			$params = $result->getData();
			if (intval($params['AVATAR']) > 0)
				$this->lastAvatarId = $params['AVATAR'];

			$arUsersName = Array();
			foreach ($arUserId as $userId)
			{
				if ($userId != $this->user_id)
					$arUsersName[$userId] = htmlspecialcharsback($arUsers[$userId]['name']);

				CIMContactList::SetRecent($chatId, 0, true, $userId);
				$strSql = "INSERT INTO b_im_relation (CHAT_ID, MESSAGE_TYPE, USER_ID) VALUES (".$chatId.",'".IM_MESSAGE_GROUP."',".$userId.")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			if (!$skipUserAdd)
			{
				$message = GetMessage("IM_CHAT_JOIN_".$arUsers[$this->user_id]['gender'], Array('#USER_1_NAME#' => htmlspecialcharsback($arUsers[$this->user_id]['name']), '#USER_2_NAME#' => implode(', ', $arUsersName)));

				self::AddMessage(Array(
					"TO_CHAT_ID" => $chatId,
					"FROM_USER_ID" => $this->user_id,
					"MESSAGE" 	 => $message,
					"SYSTEM"	 => 'Y',
				));
			}
		}
		else
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_IM_ERROR_EMPTY_USER_OR_CHAT"), "ERROR_OF_CREATE_CHAT");
			return false;
		}
		return $chatId;
	}

	public static function AddMessage($arFields)
	{
		$arFields['MESSAGE_TYPE'] = IM_MESSAGE_GROUP;

		return CIMMessenger::Add($arFields);
	}

	public function AddUser($chatId, $userId)
	{
		global $DB;

		$chatId = intval($chatId);
		if ($chatId <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_EMPTY_CHAT_ID"), "EMPTY_CHAT_ID");
			return false;
		}

		$arUserId = Array();
		if (is_array($userId))
		{
			foreach ($userId as $value)
				$arUserId[] = intval($value);
		}
		else
		{
			$arUserId[] = intval($userId);
		}
		if (count($arUserId) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_EMPTY_USER_ID"), "EMPTY_USER_ID");
			return false;
		}

		if (!IsModuleInstalled('intranet') && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed())
		{
			global $USER;

			$arFriendUsers = Array();
			$dbFriends = CSocNetUserRelations::GetList(array(),array("USER_ID" => $USER->GetID(), "RELATION" => SONET_RELATIONS_FRIEND), false, false, array("ID", "FIRST_USER_ID", "SECOND_USER_ID", "DATE_CREATE", "DATE_UPDATE", "INITIATED_BY"));
			while ($arFriends = $dbFriends->Fetch())
			{
				$friendId = $USER->GetID() == $arFriends["FIRST_USER_ID"]? $arFriends["SECOND_USER_ID"]: $arFriends["FIRST_USER_ID"];
				$arFriendUsers[$friendId] = $friendId;
			}
			foreach ($arUserId as $id => $userId)
			{
				if ($userId == $USER->GetID())
					continue;

				if (!isset($arFriendUsers[$userId]) && CIMSettings::GetPrivacy(CIMSettings::PRIVACY_CHAT, $userId) == CIMSettings::PRIVACY_RESULT_CONTACT)
					unset($arUserId[$id]);
			}

			if (count($arUserId) <= 0)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_EMPTY_USER_ID_BY_PRIVACY"), "EMPTY_USER_ID_BY_PRIVACY");
				return false;
			}
		}

		$strSql = "
			SELECT R.CHAT_ID, C.TITLE CHAT_TITLE, C.AUTHOR_ID CHAT_AUTHOR_ID
			FROM b_im_relation R LEFT JOIN b_im_chat C ON R.CHAT_ID = C.ID
			WHERE R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE = '".IM_MESSAGE_GROUP."' AND R.CHAT_ID = ".$chatId;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$chatTitle = $arRes['CHAT_TITLE'];
			$chatAuthorId = intval($arRes['CHAT_AUTHOR_ID']);
			$arRelation = self::GetRelationById($chatId);
			$arExistUser = Array();
			foreach ($arRelation as $relation)
				$arExistUser[] = $relation['USER_ID'];

			if (count($arRelation)+count($arUserId) > 100)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MAX_USER", Array('#COUNT#' => 100)), "MAX_USER");
				return false;
			}

			$arUserId = array_diff($arUserId, $arExistUser);
			if (empty($arUserId))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_NOTHING_TO_ADD"), "NOTHING_TO_ADD");
				return false;
			}

			$arUserSelect = $arUserId;
			$arUserSelect[] = $this->user_id;

			$arUsers = CIMContactList::GetUserData(array(
				'ID' => array_values($arUserSelect),
				'DEPARTMENT' => 'N',
				'USE_CACHE' => 'N'
			));
			$arUsers = $arUsers['users'];

			$maxId = 0;
			$strSql = "SELECT MAX(ID) ID FROM b_im_message WHERE CHAT_ID = ".$chatId." GROUP BY CHAT_ID";
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				$maxId = $arRes['ID'];

			$fileMaxId = CIMDisk::GetMaxFileId($chatId);

			$arUsersName = Array();
			foreach ($arUserId as $userId)
			{
				$arUsersName[] = htmlspecialcharsback($arUsers[$userId]['name']);
				CIMContactList::SetRecent($chatId, $maxId, true, $userId);
				$strSql = "INSERT INTO b_im_relation (CHAT_ID, MESSAGE_TYPE, USER_ID, START_ID, LAST_ID, LAST_SEND_ID, LAST_FILE_ID) VALUES (".$chatId.",'".IM_MESSAGE_GROUP."',".$userId.",".($maxId+1).",".$maxId.",".$maxId.", ".$fileMaxId.")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}

			CIMDisk::ChangeFolderMembers($chatId, $arUserId);

			$message = GetMessage("IM_CHAT_JOIN_".$arUsers[$this->user_id]['gender'], Array('#USER_1_NAME#' => htmlspecialcharsback($arUsers[$this->user_id]['name']), '#USER_2_NAME#' => implode(', ', $arUsersName)));

			if (CModule::IncludeModule("pull"))
			{
				foreach ($arRelation as $ar)
				{
					CPullStack::AddByUser($ar['USER_ID'], Array(
						'module_id' => 'im',
						'command' => 'chatUserAdd',
						'params' => Array(
							'chatId' => $chatId,
							'chatTitle' => $chatTitle,
							'chatOwner' => $chatAuthorId,
							'users' => $arUsers,
							'newUsers' => $arUserId
						),
					));
				}
			}
			self::AddMessage(Array(
				"TO_CHAT_ID" => $chatId,
				"MESSAGE" 	 => $message,
				"FROM_USER_ID" => $this->user_id,
				"SYSTEM"	 => 'Y',
			));

			return true;
		}
		$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_AUTHORIZE_ERROR"), "AUTHORIZE_ERROR");
		return false;
	}

	public function MuteNotify($chatId, $mute = true)
	{
		global $DB;

		$strSql = "UPDATE b_im_relation SET NOTIFY_BLOCK = '".($mute? 'Y': 'N')."' WHERE CHAT_ID = ".$chatId." AND USER_ID = ".$this->user_id;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return false;
	}

	public function DeleteUser($chatId, $userId, $checkPermission = true)
	{
		global $DB;
		$chatId = intval($chatId);
		$userId = intval($userId);
		if ($chatId <= 0 || $userId <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_EMPTY_USER_OR_CHAT"), "EMPTY_USER_OR_CHAT");
			return false;
		}

		$strSql = "
			SELECT R.CHAT_ID, C.TITLE CHAT_TITLE, C.AUTHOR_ID CHAT_AUTHOR_ID
			FROM b_im_relation R LEFT JOIN b_im_chat C ON R.CHAT_ID = C.ID
			WHERE R.USER_ID = ".$userId." AND R.MESSAGE_TYPE = '".IM_MESSAGE_GROUP."' AND R.CHAT_ID = ".$chatId;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$chatTitle = $arRes['CHAT_TITLE'];
			$chatAuthorId = intval($arRes['CHAT_AUTHOR_ID']);
			if ($chatAuthorId == $userId)
			{
				$strSql = "
					SELECT R.USER_ID
					FROM b_im_relation R
					WHERE R.CHAT_ID = ".$chatId." AND R.USER_ID <> ".$chatAuthorId;
				$strSql = $DB->TopSql($strSql, 1);
				$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if ($arRes = $dbRes->Fetch())
				{
					$strSql = "UPDATE b_im_chat SET AUTHOR_ID = ".$arRes['USER_ID']." WHERE ID = ".$chatId;
					$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}
			}

			$bSelf = true;
			$arUsers = Array($userId);
			if(is_object($GLOBALS["USER"]) && $GLOBALS["USER"]->GetId() != $userId)
			{
				if ($checkPermission && $chatAuthorId != $GLOBALS["USER"]->GetId())
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_KICK"), "IM_ERROR_KICK");
					return false;
				}

				$bSelf = false;
				$arUsers[] = $GLOBALS["USER"]->GetId();
			}

			$arSelect = Array("ID", "LAST_NAME", "NAME", "LOGIN", "SECOND_NAME", "PERSONAL_GENDER");
			$dbUsers = CUser::GetList(($sort_by = false), ($dummy=''), array('ID' => implode('|', $arUsers)), array('FIELDS' => $arSelect));
			$arUsers = Array();
			while ($arUser = $dbUsers->Fetch())
			{
				$arUsers[$arUser['ID']]['NAME'] = CUser::FormatName(CSite::GetNameFormat(false), $arUser, true, false);
				$arUsers[$arUser['ID']]['GENDER'] = $arUser["PERSONAL_GENDER"] == 'F'? 'F': 'M';
			}

			if ($bSelf)
				$message = GetMessage("IM_CHAT_LEAVE_".$arUsers[$userId]['GENDER'], Array('#USER_NAME#' => $arUsers[$userId]['NAME']));
			else
				$message = GetMessage("IM_CHAT_KICK_".$arUsers[$GLOBALS["USER"]->GetId()]['GENDER'], Array('#USER_1_NAME#' => $arUsers[$GLOBALS["USER"]->GetId()]['NAME'], '#USER_2_NAME#' => $arUsers[$userId]['NAME']));

			$arOldRelation = Array();
			if (CModule::IncludeModule("pull"))
				$arOldRelation = CIMChat::GetRelationById($chatId);

			$CIMChat = new CIMChat($userId);
			$CIMChat->SetReadMessage($chatId);

			$strSql = "DELETE FROM b_im_relation WHERE CHAT_ID = ".$chatId." AND USER_ID = ".$userId;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			CIMDisk::ChangeFolderMembers($chatId, $userId, false);

			self::AddMessage(Array(
				"TO_CHAT_ID" => $chatId,
				"MESSAGE" 	 => $message,
				"FROM_USER_ID" => $this->user_id,
				"SYSTEM"	 => 'Y',
			));

			foreach ($arOldRelation as $rel)
			{
				CPullStack::AddByUser($rel['USER_ID'], Array(
					'module_id' => 'im',
					'command' => 'chatUserLeave',
					'params' => Array(
						'chatId' => $chatId,
						'chatTitle' => $chatTitle,
						'userId' => $userId,
						'message' => $bSelf? '': htmlspecialcharsbx($message),
					),
				));
			}



			CIMContactList::DeleteRecent($chatId, true, $userId);

			return true;
		}

		$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_USER_NOT_FOUND"), "USER_NOT_FOUND");
		return false;
	}

	public static function GetAvatarImage($id)
	{
		$url = '/bitrix/js/im/images/blank.gif';

		$id = intval($id);
		if ($id > 0)
		{
			$arFileTmp = CFile::ResizeImageGet(
				$id,
				array('width' => 58, 'height' => 58),
				BX_RESIZE_IMAGE_EXACT,
				false
			);
			if (!empty($arFileTmp['src']))
			{
				$url = $arFileTmp['src'];
			}
		}
		return $url;
	}

	public static function AddSystemMessage($params)
	{
		$chatId = intval($params['CHAT_ID']);
		if ($chatId <= 0)
			return false;

		$arUser = false;
		$userId = intval($params['USER_ID']);
		if ($userId > 0)
		{
			$arSelect = Array("ID", "LAST_NAME", "NAME", "LOGIN", "SECOND_NAME", "PERSONAL_GENDER");
			$dbUsers = CUser::GetList(($sort_by = false), ($dummy=''), array('ID' => $userId), array('FIELDS' => $arSelect));
			if ($arUser = $dbUsers->Fetch())
			{
				$arUser['NAME'] = CUser::FormatName(CSite::GetNameFormat(false), $arUser, true, false);
				$arUser['PERSONAL_GENDER'] = $arUser["PERSONAL_GENDER"] == 'F'? 'F': 'M';
			}
		}

		if (isset($params['MESSAGE_CODE']))
		{
			$messageReplace = is_array($params['MESSAGE_REPLACE'])? $params['MESSAGE_REPLACE']: Array();
			if ($arUser)
			{
				$messageReplace['#USER_NAME#'] = $arUser['NAME'];
				$message = GetMessage($params['MESSAGE_CODE'].$arUser['PERSONAL_GENDER'], $messageReplace);
			}
			else
			{
				$message = GetMessage($params['MESSAGE_CODE'], $messageReplace);
			}
		}
		else
		{
			$messageReplace = is_array($params['MESSAGE_REPLACE'])? $params['MESSAGE_REPLACE']: Array();
			$message = trim($params['MESSAGE']);
			if (strlen($message) > 0 && !empty($messageReplace))
			{
				$message = str_replace(array_keys($messageReplace), array_values($messageReplace), $message);
			}
		}
		if (strlen($message) <= 0)
			return false;

		return self::AddMessage(Array(
			"TO_CHAT_ID" => $chatId,
			"FROM_USER_ID" => $userId,
			"MESSAGE" => $message,
			"SYSTEM" => 'Y',
		));
	}

	public static function SetUnreadCounter($userId)
	{
		return false;

		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		global $DB;

		$sqlCounter = "SELECT COUNT(M.ID) as CNT
						FROM b_im_message M
						INNER JOIN b_im_relation R1 ON M.ID > R1.LAST_ID AND M.CHAT_ID = R1.CHAT_ID AND R1.MESSAGE_TYPE = '".IM_MESSAGE_GROUP."' AND R1.STATUS < ".IM_STATUS_READ."
						WHERE R1.USER_ID = ".$userId;
		$dbRes = $DB->Query($sqlCounter, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($row = $dbRes->Fetch())
			CUserCounter::Set($userId, 'im_chat', $row['CNT'], '**', false);
		else
			CUserCounter::Set($userId, 'im_chat', 0, '**', false);

		return true;
	}
}
?>