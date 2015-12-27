<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Im as IM;

class CIMChat
{
	const CHAT_ALL = 'all';

	private $user_id = 0;
	private $bHideLink = false;
	public $lastAvatarId = 0;

	public function __construct($user_id = null, $arParams = Array())
	{
		if (is_null($user_id))
		{
			global $USER;
			$this->user_id = IntVal($USER->GetID());
		}
		else
		{
			$this->user_id = intval($user_id);
		}

		if (isset($arParams['HIDE_LINK']) && $arParams['HIDE_LINK'] == 'Y')
		{
			$this->bHideLink = true;
		}
	}

	public function GetMessage($ID)
	{
		global $DB;

		$strSql = "
			SELECT
				M.*, C.TYPE CHAT_TYPE, R.USER_ID RID
			FROM
				b_im_message M
				INNER JOIN b_im_chat C ON C.ID = M.CHAT_ID
				LEFT JOIN b_im_relation R ON R.CHAT_ID = M.CHAT_ID AND R.USER_ID = ".$this->user_id."
			WHERE
				M.ID = ".intval($ID)."
		";

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			if ($arRes['CHAT_TYPE'] == IM_MESSAGE_OPEN)
			{
				if (intval($arRes['RID']) <= 0 && IM\User::getInstance($this->userId)->isExtranet())
				{
					return false;
				}
			}
			else if (intval($arRes['RID']) <= 0)
			{
				return false;
			}
			unset($arRes['CHAT_TYPE']);
			unset($arRes['RID']);

			return $arRes;
		}

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

		$orm = IM\ChatTable::getById($toChatId);
		if (!($chatData = $orm->fetch()))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_CHAT_NOT_EXISTS"), "ERROR_CHAT_NOT_EXISTS");
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

		$limitById = '';
		$ar = \CIMChat::GetRelationById($toChatId, $fromUserId);
		if ($ar && $ar['START_ID'] > 0)
		{
			$limitById = 'AND M.ID >= '.intval($ar['START_ID']);
		}

		if (!$bTimeZone)
			CTimeZone::Disable();

		if ($chatData['TYPE'] == IM_MESSAGE_OPEN)
		{
			$strSql = "
				SELECT
					M.ID,
					M.CHAT_ID,
					M.MESSAGE,
					".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
					M.AUTHOR_ID,
					C.TYPE CHAT_TYPE,
					R.USER_ID RID
				FROM b_im_message M
				INNER JOIN b_im_chat C ON C.ID = M.CHAT_ID AND C.TYPE = '".IM_MESSAGE_OPEN."'
				LEFT JOIN b_im_relation R ON R.CHAT_ID = M.CHAT_ID AND R.USER_ID = ".$fromUserId."
				WHERE
					M.CHAT_ID = ".$toChatId."
					".$limitById."
					#LIMIT#
				ORDER BY M.DATE_CREATE DESC
			";
		}
		else
		{
			$strSql = "
				SELECT
					M.ID,
					M.CHAT_ID,
					M.MESSAGE,
					".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
					M.AUTHOR_ID,
					C.TYPE CHAT_TYPE,
					R1.USER_ID RID
				FROM b_im_message M
				INNER JOIN b_im_relation R1 ON M.CHAT_ID = R1.CHAT_ID
				INNER JOIN b_im_chat C ON C.ID = M.CHAT_ID AND C.TYPE = '".IM_MESSAGE_CHAT."'
				WHERE
					R1.CHAT_ID = ".$toChatId." AND R1.USER_ID = ".$fromUserId."
					".$limitById."
					#LIMIT#
				ORDER BY M.DATE_CREATE DESC
			";
		}
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

		$chatType = IM_MESSAGE_CHAT;
		$chatRelationUserId = 0;

		$arMessages = Array();
		$arMessageId = Array();
		$arUsersMessage = Array();
		$CCTP = new CTextParser();
		$CCTP->MaxStringLen = 200;
		$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => $this->bHideLink? "N": "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
		while ($arRes = $dbRes->Fetch())
		{
			$chatType = $arRes['CHAT_TYPE'];
			$chatRelationUserId = intval($arRes['RID']);

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

		if ($chatType == IM_MESSAGE_OPEN && $chatRelationUserId <= 0)
		{
			if (IM\User::getInstance($fromUserId)->isExtranet())
			{
				$arMessages = Array();
				$arMessageId = Array();
				$arUsersMessage = Array();
				$loadExtraData = false;
			}
			else if (CModule::IncludeModule('pull'))
			{
				CPullWatch::Add($fromUserId, 'IM_PUBLIC_'.$toChatId, true);
			}
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

			if ($arChat['chat'][$toChatId]['messageType'] == IM_MESSAGE_OPEN || in_array($fromUserId, $arChat['userInChat'][$toChatId]))
			{
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

				if ($arChat['chat'][$toChatId]['extranet'] === "")
				{
					$isExtranet = false;
					foreach ($ar['users'] as $userData)
					{
						if ($userData['extranet'])
						{
							$isExtranet = true;
							break;
						}
					}
					IM\ChatTable::update($toChatId, Array('EXTRANET' => $isExtranet? "Y":"N"));

					$arChat['chat'][$toChatId]['extranet'] = $isExtranet;
				}
				$arResult['chat'] = $arChat['chat'];
			}
		}

		return $arResult;
	}

	public function GetLastSendMessage($arParams)
	{
		global $DB;

		if (!isset($arParams['ID']))
			return false;

		$chatId = $arParams['ID'];

		$fromUserId = isset($arParams['FROM_USER_ID']) && IntVal($arParams['FROM_USER_ID'])>0? IntVal($arParams['FROM_USER_ID']): $this->user_id;
		$limit = isset($arParams['LIMIT']) && IntVal($arParams['LIMIT'])>0? IntVal($arParams['LIMIT']): false;
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
				M.ID,
				M.CHAT_ID,
				M.MESSAGE,
				".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
				M.AUTHOR_ID,
				C.TITLE CHAT_TITLE,
				C.COLOR CHAT_COLOR,
				C.ENTITY_TYPE CHAT_ENTITY_TYPE,
				C.TYPE CHAT_TYPE,
				R.ID RID
			FROM b_im_message M
			INNER JOIN b_im_chat C ON C.ID = M.CHAT_ID AND C.LAST_MESSAGE_ID = M.ID
			LEFT JOIN b_im_relation R ON R.CHAT_ID = M.CHAT_ID AND R.USER_ID = ".$fromUserId."
			WHERE
				M.ID = C.LAST_MESSAGE_ID
				AND M.CHAT_ID IN (".implode(",",$arChatId).")
				".$sqlLimit."
		";
		if (!$bTimeZone)
			CTimeZone::Enable();

		$arMessages = Array();
		$CCTP = new CTextParser();
		$CCTP->MaxStringLen = 200;
		$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => $this->bHideLink? "N": "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
		{
			if ($arRes['CHAT_TYPE'] == IM_MESSAGE_OPEN)
			{
				if (intval($arRes['RID']) <= 0 && IM\User::getInstance($this->userId)->isExtranet())
				{
					continue;
				}
			}
			else if (intval($arRes['RID']) <= 0)
			{
				continue;
			}

			if ($arRes["CHAT_TYPE"] == IM_MESSAGE_PRIVATE)
			{
				$chatType = 'private';
			}
			else if ($arRes["CHAT_ENTITY_TYPE"] == 'CALL')
			{
				$chatType = 'call';
			}
			else
			{
				$chatType = $arRes["CHAT_TYPE"] == IM_MESSAGE_OPEN? 'open': 'chat';
			}


			$arMessages[$arRes['CHAT_ID']] = Array(
				'id' => $arRes['ID'],
				'senderId' => $arRes['AUTHOR_ID'],
				'recipientId' => $arRes['CHAT_ID'],
				'chatTitle' => $arRes['CHAT_TITLE'],
				'date' => $arRes['DATE_CREATE'],
				'color' => $arRes["CHAT_COLOR"] == ""? IM\Color::getColorByNumber($arRes['CHAT_ID']): IM\Color::getColor($arRes['CHAT_COLOR']),
				'type' => $chatType,
				'messageType' => $arRes["CHAT_TYPE"],
				'text' => $CCTP->convertText(htmlspecialcharsbx($arRes['MESSAGE']))
			);
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

	public static function GetPrivateRelation($fromUserId, $toUserId)
	{
		global $DB;

		$fromUserId = intval($fromUserId);
		$toUserId = intval($toUserId);

		$arResult = Array();
		$strSql = "
			SELECT
				RF.*
			FROM
				b_im_relation RF
				INNER JOIN b_im_relation RT on RF.CHAT_ID = RT.CHAT_ID
			WHERE
				RF.USER_ID = ".$fromUserId."
			and RT.USER_ID = ".$toUserId."
			and RF.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'
		";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
			$arResult = $arRes;

		return $arResult;
	}

	public static function GetChatData($arParams = Array())
	{
		global $DB;

		$arParams['PHOTO_SIZE'] = isset($arParams['PHOTO_SIZE'])? intval($arParams['PHOTO_SIZE']): 58;

		$from = "
			FROM b_im_relation R1
			INNER JOIN b_im_chat C ON C.ID = R1.CHAT_ID
		";

		if (isset($arParams['SKIP_PRIVATE']) && $arParams['SKIP_PRIVATE'] == 'Y')
		{
			$from .= " AND C.TYPE IN ('".IM_MESSAGE_OPEN."','".IM_MESSAGE_CHAT."')";
		}

		$innerJoin = $whereUser = "";
		if (isset($arParams['GET_LIST']) && $arParams['GET_LIST'] == 'Y')
		{
			if (!isset($arParams['USER_ID']))
				return false;

			$innerJoin = "INNER JOIN b_im_relation R2 ON R2.CHAT_ID = C.ID";
			$whereGeneral = "WHERE R2.USER_ID = ".intval($arParams['USER_ID']);
		}
		else
		{
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
			{
				return false;
			}

			if (isset($arParams['USER_ID']))
			{
				$innerJoin = "LEFT JOIN b_im_relation R2 ON R2.CHAT_ID = C.ID AND R2.USER_ID = ".intval($arParams['USER_ID']);
			}
			$whereGeneral = "WHERE R1.CHAT_ID IN (".implode(',', $arFilter['ID']).") ";
		}

		$strSql = "
			SELECT
				C.ID CHAT_ID,
				C.TITLE CHAT_TITLE,
				C.CALL_TYPE CHAT_CALL_TYPE,
				C.AUTHOR_ID CHAT_OWNER_ID,
				C.CALL_NUMBER CHAT_CALL_NUMBER,
				C.EXTRANET CHAT_EXTRANET,
				C.COLOR CHAT_COLOR,
				C.TYPE CHAT_TYPE,
				C.AVATAR,
				C.ENTITY_TYPE,
				C.ENTITY_ID,
				R1.NOTIFY_BLOCK RELATION_BLOCK_NOTIFY,
				R1.USER_ID RELATION_USER_ID,
				R1.CALL_STATUS
				".(isset($arParams['USER_ID'])? ", R2.ID RID": "")."
			".$from."
			".$innerJoin."
			".$whereGeneral."
		";

		$arChat = Array();
		$arUserInChat = Array();
		$arUserCallStatus = Array();
		$arUserChatBlockStatus = Array();
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->GetNext(true, false))
		{
			if (isset($arParams['USER_ID']))
			{
				if ($arRes['CHAT_TYPE'] == IM_MESSAGE_OPEN)
				{
					if (intval($arRes['RID']) <= 0 && IM\User::getInstance($arParams['USER_ID'])->isExtranet())
					{
						continue;
					}
				}
				else if (intval($arRes['RID']) <= 0)
				{
					continue;
				}
			}

			if (!isset($arChat[$arRes["CHAT_ID"]]))
			{
				$avatar = '/bitrix/js/im/images/blank.gif';
				if (intval($arRes["AVATAR"]) > 0)
				{
					$avatar = self::GetAvatarImage($arRes["AVATAR"], $arParams['PHOTO_SIZE']);
				}
				if ($arRes["CHAT_TYPE"] == IM_MESSAGE_PRIVATE)
				{
					$chatType = 'private';
				}
				else if ($arRes["ENTITY_TYPE"] == 'CALL')
				{
					$chatType = 'call';
				}
				else
				{
					$chatType = $arRes["CHAT_TYPE"] == IM_MESSAGE_OPEN? 'open': 'chat';
				}

				$arChat[$arRes["CHAT_ID"]] = Array(
					'id' => $arRes["CHAT_ID"],
					'name' => $arRes["CHAT_TITLE"],
					'owner' => $arRes["CHAT_OWNER_ID"],
					'color' => $arRes["CHAT_COLOR"] == ""? IM\Color::getColorByNumber($arRes['CHAT_ID']): IM\Color::getColor($arRes['CHAT_COLOR']),
					'extranet' => $arRes["CHAT_EXTRANET"] == ""? "": ($arRes["CHAT_EXTRANET"] == "Y"? true: false),
					'avatar' => $avatar,
					'call' => trim($arRes["CHAT_CALL_TYPE"]),
					'call_number' => trim($arRes["CHAT_CALL_NUMBER"]),
					'call_entity_type' => trim($arRes["ENTITY_TYPE"]),
					'call_entity_id' => trim($arRes["ENTITY_ID"]),
					'type' => $chatType,
					'messageType' => $arRes["CHAT_TYPE"],
				);
			}
			$arUserInChat[$arRes["CHAT_ID"]][] = $arRes["RELATION_USER_ID"];
			$arUserCallStatus[$arRes["CHAT_ID"]][$arRes["RELATION_USER_ID"]] = trim($arRes["CALL_STATUS"]);
			if ($arRes["RELATION_BLOCK_NOTIFY"] != 'N')
				$arUserChatBlockStatus[$arRes["CHAT_ID"]][$arRes["RELATION_USER_ID"]] = $arRes["RELATION_BLOCK_NOTIFY"];
		}

		$result = array(
			'chat' => $arChat,
			'userInChat' => $arUserInChat,
			'userCallStatus' => $arUserCallStatus,
			'userChatBlockStatus' => $arUserChatBlockStatus
		);

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
				//CUserCounter::Decrement($this->user_id, 'im_chat_v2', '**', false, $arRes['CNT']);
				CIMMessenger::SpeedFileDelete($this->user_id, IM_SPEED_GROUP);
				if (CModule::IncludeModule("pull"))
				{
					CPushManager::DeleteFromQueueBySubTag($this->user_id, 'IM_MESS');
					CPullStack::AddByUser($this->user_id, Array(
						'module_id' => 'im',
						'command' => 'readMessageChat',
						'params' => Array(
							'chatId' => $chatId,
							'lastId' => $arRes['ID'],
							'count' => $arRes['CNT']
						),
					));
					CIMMessenger::SendBadges($this->user_id);
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
		$messageType = isset($arParams['MESSAGE_TYPE']) && in_array($arParams['MESSAGE_TYPE'], Array(IM_MESSAGE_OPEN, IM_MESSAGE_CHAT))? $arParams['MESSAGE_TYPE']: 'ALL';

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

			$arRelations = Array();
			if (strlen($ssqlStatus) > 0)
			{
				$strSql ="
					SELECT
						R1.USER_ID,
						R1.CHAT_ID,
						R1.LAST_ID
					FROM
						b_im_relation R1
					WHERE
						R1.USER_ID = ".$this->user_id."
						".($messageType == 'ALL'? "AND R1.MESSAGE_TYPE IN ('".IM_MESSAGE_OPEN."','".IM_MESSAGE_CHAT."')": "AND R1.MESSAGE_TYPE = '".$messageType."'")."
						".$ssqlStatus."
				";
				$dbSubRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				while ($arRes = $dbSubRes->Fetch())
				{
					$arRelations[] = $arRes;
				}
			}

			$arMessageId = Array();
			$arMessageChatId = Array();
			$arLastMessage = Array();
			$arMark = Array();
			$arChat = Array();

			$arPrepareResult = Array();
			$arFilteredResult = Array();

			if (!empty($arRelations))
			{
				if (!$bTimeZone)
					CTimeZone::Disable();
				$strSql = "
					SELECT
						M.ID,
						M.CHAT_ID,
						M.MESSAGE,
						".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
						M.AUTHOR_ID,
						R1.STATUS R1_STATUS,
						R1.MESSAGE_TYPE MESSAGE_TYPE
					FROM b_im_message M
					INNER JOIN b_im_relation R1 ON M.ID > ".$ssqlLastId." AND M.CHAT_ID = R1.CHAT_ID AND R1.USER_ID != M.AUTHOR_ID
					WHERE
						R1.USER_ID = ".$this->user_id."
						".($messageType == 'ALL'? "AND R1.MESSAGE_TYPE IN ('".IM_MESSAGE_OPEN."','".IM_MESSAGE_CHAT."')": "AND R1.MESSAGE_TYPE = '".$messageType."'")."
						".$ssqlStatus."
				";
				if (!$bTimeZone)
					CTimeZone::Enable();
				$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

				$CCTP = new CTextParser();
				$CCTP->MaxStringLen = 200;
				$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink ? "N" : "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => $this->bHideLink ? "N" : "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");

				while ($arRes = $dbRes->Fetch())
				{
					$arPrepareResult[$arRes['CHAT_ID']][$arRes['ID']] = $arRes;
				}
				foreach ($arPrepareResult as $chatId => $arRes)
				{
					if (count($arPrepareResult[$chatId]) > 100)
					{
						$arPrepareResult[$chatId] = array_slice($arRes, -100, 100);
					}
					$arFilteredResult = array_merge($arFilteredResult, $arPrepareResult[$chatId]);
				}
				unset($arPrepareResult);
			}

			foreach ($arFilteredResult as $arRes)
			{
				$arUsers[] = $arRes['AUTHOR_ID'];

				$arMessages[$arRes['ID']] = Array(
					'id' => $arRes['ID'],
					'chatId' => $arRes['CHAT_ID'],
					'senderId' => $arRes['AUTHOR_ID'],
					'recipientId' => $arRes['CHAT_ID'],
					'date' => $arRes['DATE_CREATE'],
					'text' => $arRes['MESSAGE'],
					'messageType' => $arRes['MESSAGE_TYPE'],
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

			if (!empty($arMessages))
			{
				foreach ($arMark as $chatId => $lastSendId)
					CIMMessage::SetLastSendId($chatId, $this->user_id, $lastSendId);
			}
			else
			{
				foreach ($arRelations as $relation)
					CIMMessage::SetLastId($relation['CHAT_ID'], $relation['USER_ID']);
			}

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

	public function SetColor($chatId, $color)
	{
		global $DB;
		$chatId = intval($chatId);
		if ($chatId <= 0 || !IM\Color::isSafeColor($color))
			return false;

		$strSql = "
			SELECT R.CHAT_ID, C.COLOR CHAT_COLOR, C.AUTHOR_ID CHAT_AUTHOR_ID
			FROM b_im_relation R LEFT JOIN b_im_chat C ON R.CHAT_ID = C.ID
			WHERE R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE IN ('".IM_MESSAGE_OPEN."','".IM_MESSAGE_CHAT."') AND R.CHAT_ID = ".$chatId;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			if ($arRes['CHAT_COLOR'] == $color)
				return false;

			IM\ChatTable::update($chatId, array('COLOR' => $color));

			CIMChat::AddSystemMessage(Array(
				'CHAT_ID' => $chatId,
				'USER_ID' => $this->user_id,
				'MESSAGE_CODE' => 'IM_CHAT_CHANGE_COLOR_',
				'MESSAGE_REPLACE' => Array('#CHAT_COLOR#' => IM\Color::getName($color))
			));

			if (CModule::IncludeModule("pull"))
			{
				$ar = CIMChat::GetRelationById($chatId);
				foreach ($ar as $rel)
				{
					CIMContactList::CleanChatCache($rel['USER_ID']);
					CPullStack::AddByUser($rel['USER_ID'], Array(
						'module_id' => 'im',
						'command' => 'chatChangeColor',
						'params' => Array(
							'chatId' => $chatId,
							'chatColor' => IM\Color::getColor($color),
						),
					));
				}
			}

			return true;
		}
		return false;
	}

	public function Rename($chatId, $title)
	{
		global $DB;
		$chatId = intval($chatId);
		$title = substr(trim($title), 0, 255);

		if ($chatId <= 0 || strlen($title) <= 0)
			return false;

		$strSql = "
			SELECT R.CHAT_ID, C.TITLE CHAT_TITLE, C.AUTHOR_ID CHAT_AUTHOR_ID
			FROM b_im_relation R LEFT JOIN b_im_chat C ON R.CHAT_ID = C.ID
			WHERE R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE IN ('".IM_MESSAGE_OPEN."','".IM_MESSAGE_CHAT."') AND R.CHAT_ID = ".$chatId;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			if ($arRes['CHAT_TITLE'] == $title)
				return false;

			IM\ChatTable::update($chatId, array('TITLE' => $title));

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
					CIMContactList::CleanChatCache($rel['USER_ID']);
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

		$type = IM_MESSAGE_CHAT;
		if (isset($arParams['TYPE']) && in_array($arParams['TYPE'], Array(IM_MESSAGE_OPEN, IM_MESSAGE_CHAT)))
			$type = $arParams['TYPE'];

		$skipUserAdd = false;
		if ($userId === false)
		{
			$skipUserAdd = true;
		}

		$arUserId = Array();
		if (is_array($userId))
		{
			$arUserId = \CIMContactList::PrepareUserIds($userId);
		}
		else if (intval($userId) > 0)
		{
			$arUserId[intval($userId)] = intval($userId);
		}
		$arUserId[$this->user_id] = $this->user_id;

		if (!$skipUserAdd)
		{
			if (count($arUserId) <= 2)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MIN_USER"), "MIN_USER");
				return false;
			}

			if (count($arUserId) > 300)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MAX_USER", Array('#COUNT#' => 300)), "MAX_USER");
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
			if (IM\Color::isEnabled())
			{
				CGlobalCounter::Increment('im_chat_color_id', CGlobalCounter::ALL_SITES, false);
				$chatColorId = CGlobalCounter::GetValue('im_chat_color_id', CGlobalCounter::ALL_SITES);
				$chatColorCode = \Bitrix\Im\Color::getCodeByNumber($chatColorId);

				CGlobalCounter::Increment('im_chat_color_'.$chatColorCode, CGlobalCounter::ALL_SITES, false);
				$chatColorCodeCount = CGlobalCounter::GetValue('im_chat_color_'.$chatColorCode, CGlobalCounter::ALL_SITES);
				if ($chatColorCodeCount == 100)
				{
					CGlobalCounter::Set('im_chat_color_'.$chatColorCode, 1, CGlobalCounter::ALL_SITES, '', false);
					$chatColorId = 1;
				}

				$chatTitle = GetMessage('IM_CHAT_NAME_FORMAT', Array(
					'#COLOR#' => \Bitrix\Im\Color::getName($chatColorCode),
					'#NUMBER#' => $chatColorCodeCount,
				));
			}
			else
			{
				foreach ($arUserId as $userId)
				{
					$arUsersName[$userId] = htmlspecialcharsback($arUsers[$userId]['name']);
				}

				$chatTitle = implode(', ', $arUsersName);
			}
		}

		$isExtranet = false;
		foreach ($arUsers as $userData)
		{
			if ($userData['extranet'])
			{
				$isExtranet = true;
				break;
			}
		}

		$result = IM\ChatTable::add(Array(
			"TITLE"	=> substr($chatTitle, 0, 255),
			"TYPE"	=> $type,
			"COLOR"	=> $chatColorCode,
			"AUTHOR_ID"	=> $this->user_id,
			"ENTITY_TYPE" => $entityType,
			"ENTITY_ID" => $entityId,
			"EXTRANET" => $isExtranet? 'Y': 'N',
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

				CIMContactList::SetRecent(Array(
					'ENTITY_ID' => $chatId,
					'MESSAGE_ID' => 0,
					'CHAT_TYPE' => $params['TYPE'],
					'USER_ID' => $userId
				));
				IM\RelationTable::add(array(
					"CHAT_ID" => $chatId,
					"MESSAGE_TYPE" => $params['TYPE'],
					"USER_ID" => $userId,
					"STATUS" => IM_STATUS_READ,
				));

				CIMContactList::CleanChatCache($userId);
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
		$arFields['MESSAGE_TYPE'] = isset($arParams['MESSAGE_TYPE']) && in_array($arParams['MESSAGE_TYPE'], Array(IM_MESSAGE_OPEN, IM_MESSAGE_CHAT))? $arParams['MESSAGE_TYPE']: IM_MESSAGE_CHAT;

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
			$arUserId = \CIMContactList::PrepareUserIds($userId);
		}
		else if (intval($userId) > 0)
		{
			$arUserId[intval($userId)] = intval($userId);
		}
		if ($this->user_id > 0)
		{
			$arUserId[$this->user_id] = $this->user_id;
		}

		if (count($arUserId) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_EMPTY_USER_ID"), "EMPTY_USER_ID");
			return false;
		}
		
		if ($this->user_id > 0 && !IsModuleInstalled('intranet') && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed())
		{
			$arFriendUsers = Array();
			$dbFriends = CSocNetUserRelations::GetList(array(),array("USER_ID" => $this->user_id, "RELATION" => SONET_RELATIONS_FRIEND), false, false, array("ID", "FIRST_USER_ID", "SECOND_USER_ID", "DATE_CREATE", "DATE_UPDATE", "INITIATED_BY"));
			while ($arFriends = $dbFriends->Fetch())
			{
				$friendId = $this->user_id == $arFriends["FIRST_USER_ID"]? $arFriends["SECOND_USER_ID"]: $arFriends["FIRST_USER_ID"];
				$arFriendUsers[$friendId] = $friendId;
			}
			foreach ($arUserId as $id => $userId)
			{
				if ($userId == $this->user_id)
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
			SELECT
				R.CHAT_ID, C.TITLE CHAT_TITLE, C.AUTHOR_ID CHAT_AUTHOR_ID, C.EXTRANET CHAT_EXTRANET, C.TYPE CHAT_TYPE
			FROM b_im_relation R
			LEFT JOIN b_im_chat C ON R.CHAT_ID = C.ID
			WHERE
				".($this->user_id > 0? "R.USER_ID = ".$this->user_id." AND ": "")."
				R.MESSAGE_TYPE IN ('".IM_MESSAGE_OPEN."','".IM_MESSAGE_CHAT."')
				AND R.CHAT_ID = ".$chatId."
		";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$extranetFlag = $arRes["CHAT_EXTRANET"] == ""? "": ($arRes["CHAT_EXTRANET"] == "Y"? true: false);
			$chatTitle = $arRes['CHAT_TITLE'];
			$chatAuthorId = intval($arRes['CHAT_AUTHOR_ID']);
			$chatType = intval($arRes['CHAT_TYPE']);
			$arRelation = self::GetRelationById($chatId);
			$arExistUser = Array();
			foreach ($arRelation as $relation)
				$arExistUser[] = $relation['USER_ID'];

			if (count($arRelation)+count($arUserId) > 500)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MAX_USER", Array('#COUNT#' => 500)), "MAX_USER");
				return false;
			}

			$arUserId = array_diff($arUserId, $arExistUser);
			if (empty($arUserId))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_NOTHING_TO_ADD"), "NOTHING_TO_ADD");
				return false;
			}

			$arUserSelect = $arUserId;
			if ($this->user_id > 0)
			{
				$arUserSelect[] = $this->user_id;
			}

			$arUsers = CIMContactList::GetUserData(array(
				'ID' => array_values($arUserSelect),
				'DEPARTMENT' => 'N',
				'USE_CACHE' => 'N'
			));
			$arUsers = $arUsers['users'];

			if ($extranetFlag !== true)
			{
				$isExtranet = false;
				foreach ($arUsers as $userData)
				{
					if ($userData['extranet'])
					{
						$isExtranet = true;
						break;
					}
				}
				if ($isExtranet || $extranetFlag === "")
				{
					IM\ChatTable::update($chatId, Array('EXTRANET' => $isExtranet? "Y":"N"));
				}
				$extranetFlag = $isExtranet;
			}

			$arUsersName = Array();
			foreach ($arUserId as $userId)
			{
				$arUsersName[] = htmlspecialcharsback($arUsers[$userId]['name']);
			}

			if ($this->user_id > 0)
			{
				$message = GetMessage("IM_CHAT_JOIN_".$arUsers[$this->user_id]['gender'], Array('#USER_1_NAME#' => htmlspecialcharsback($arUsers[$this->user_id]['name']), '#USER_2_NAME#' => implode(', ', $arUsersName)));
			}
			else
			{
				if (count($arUsersName) > 1)
				{
					$message = GetMessage("IM_CHAT_SELF_JOIN", Array('#USERS_NAME#' => implode(', ', $arUsersName)));
				}
				else
				{
					$arUserList = array_values($arUserId);
					$message = GetMessage("IM_CHAT_SELF_JOIN_".$arUsers[$arUserList[0]]['gender'], Array('#USER_NAME#' => implode(', ', $arUsersName)));
				}
			}

			$fileMaxId = CIMDisk::GetMaxFileId($chatId);

			$maxId = 0;
			$strSql = "SELECT MAX(ID) ID FROM b_im_message WHERE CHAT_ID = ".$chatId." GROUP BY CHAT_ID";
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arMax = $dbRes->Fetch())
				$maxId = $arMax['ID'];

			$update = Array();

			$publicPullWatch = false;
			if ($chatType == IM_MESSAGE_OPEN && CModule::IncludeModule("pull"))
			{
				$publicPullWatch = true;
			}

			foreach ($arUserId as $userId)
			{
				if ($publicPullWatch)
				{
					CPullWatch::Delete($userId, 'IM_PUBLIC_'.$chatId);
				}
				CIMContactList::SetRecent(Array(
					'ENTITY_ID' => $chatId,
					'MESSAGE_ID' => $maxId,
					'CHAT_TYPE' => $arRes['CHAT_TYPE'],
					'USER_ID' => $userId
				));

				$hideHistory = CIMSettings::GetStartChatMessage() == CIMSettings::START_MESSAGE_LAST && $arRes['CHAT_TYPE'] == IM_MESSAGE_CHAT;
				if ($arRes['CHAT_TYPE'] != IM_MESSAGE_PRIVATE && $arUsers[$userId]['extranet'])
				{
					$hideHistory = true;
				}
				$orm = IM\RelationTable::add(array(
					"CHAT_ID" => $chatId,
					"MESSAGE_TYPE" => $arRes['CHAT_TYPE'],
					"USER_ID" => $userId,
					"START_ID" => $hideHistory? $maxId+1: 0,
					"LAST_ID" => $maxId,
					"LAST_SEND_ID" => $maxId,
					"LAST_FILE_ID" => $hideHistory? $fileMaxId: 0,
				));
				$update[] = $orm->getId();

				CIMContactList::CleanChatCache($userId);
			}

			CIMDisk::ChangeFolderMembers($chatId, $arUserId);

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
							'chatExtranet' => $extranetFlag,
							'users' => $arUsers,
							'newUsers' => $arUserId
						),
					));
				}
			}

			$lastId = self::AddMessage(Array(
				"TO_CHAT_ID" => $chatId,
				"MESSAGE" => $message,
				"FROM_USER_ID" => $this->user_id,
				"SYSTEM" => 'Y',
			));

			if (IsModuleInstalled('replica'))
			{
				if ($lastId && CIMSettings::GetStartChatMessage() == CIMSettings::START_MESSAGE_LAST && $arRes['CHAT_TYPE'] == IM_MESSAGE_CHAT)
				{
					foreach ($update as $relId)
					{
						IM\RelationTable::update($relId, Array('START_ID' => $lastId));
					}
				}
			}

			return true;
		}
		$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_AUTHORIZE_ERROR"), "AUTHORIZE_ERROR");
		return false;
	}

	public function MuteNotify($chatId, $mute = true)
	{
		global $DB;

		$strSql = "UPDATE b_im_relation SET NOTIFY_BLOCK = '".($mute? 'Y': 'N')."' WHERE CHAT_ID = ".intval($chatId)." AND USER_ID = ".$this->user_id;
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
			SELECT R.CHAT_ID, C.TITLE CHAT_TITLE, C.AUTHOR_ID CHAT_AUTHOR_ID, C.EXTRANET CHAT_EXTRANET, C.TYPE CHAT_TYPE
			FROM b_im_relation R LEFT JOIN b_im_chat C ON R.CHAT_ID = C.ID
			WHERE R.USER_ID = ".$userId." AND R.MESSAGE_TYPE IN ('".IM_MESSAGE_OPEN."','".IM_MESSAGE_CHAT."') AND R.CHAT_ID = ".$chatId;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$extranetFlag = $arRes["CHAT_EXTRANET"] == ""? "": ($arRes["CHAT_EXTRANET"] == "Y"? true: false);
			$chatTitle = $arRes['CHAT_TITLE'];
			$chatType = $arRes['CHAT_TYPE'];
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

			$arOldRelation = CIMChat::GetRelationById($chatId);

			$arUsers = CIMContactList::GetUserData(array(
				'ID' => array_keys($arOldRelation),
				'DEPARTMENT' => 'N',
				'USE_CACHE' => 'N'
			));
			$arUsers = $arUsers['users'];

			if ($bSelf)
				$message = GetMessage("IM_CHAT_LEAVE_".$arUsers[$userId]['gender'], Array('#USER_NAME#' => htmlspecialcharsback($arUsers[$userId]['name'])));
			else
				$message = GetMessage("IM_CHAT_KICK_".$arUsers[$GLOBALS["USER"]->GetId()]['gender'], Array('#USER_1_NAME#' => htmlspecialcharsback($arUsers[$GLOBALS["USER"]->GetId()]['name']), '#USER_2_NAME#' => htmlspecialcharsback($arUsers[$userId]['name'])));

			$CIMChat = new CIMChat($userId);
			$CIMChat->SetReadMessage($chatId);

			CIMContactList::CleanChatCache($userId);

			$publicPullWatch = false;
			if ($chatType == IM_MESSAGE_OPEN && CModule::IncludeModule("pull"))
			{
				$publicPullWatch = true;
			}

			$relationList = IM\RelationTable::getList(array(
				"select" => array("ID", "USER_ID"),
				"filter" => array(
					"=CHAT_ID" => $chatId,
					"=USER_ID" => $userId,
				),
			));
			while ($relation = $relationList->fetch())
			{
				if ($publicPullWatch && !$arUsers[$relation["USER_ID"]]['extranet'])
				{
					CPullWatch::Add($relation["USER_ID"], 'IM_PUBLIC_'.$chatId, true);
				}
				Im\RelationTable::delete($relation["ID"]);

				CIMContactList::DeleteRecent($chatId, true, $relation["USER_ID"]);

				if ($extranetFlag !== false)
				{
					$isExtranet = false;
					foreach ($arUsers as $userData)
					{
						if ($userData['id'] == $userId)
							continue;

						if ($userData['extranet'])
						{
							$isExtranet = true;
							break;
						}
					}
					if (!$isExtranet || $extranetFlag === "")
					{
						IM\ChatTable::update($chatId, Array('EXTRANET' => $isExtranet? "Y":"N"));
					}
					$extranetFlag = $isExtranet;
				}
			}

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

			return true;
		}

		$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_USER_NOT_FOUND"), "USER_NOT_FOUND");
		return false;
	}

	public static function GetAvatarImage($id, $size = 58)
	{
		$url = '/bitrix/js/im/images/blank.gif';

		$id = intval($id);
		if ($id > 0 && $size > 0)
		{
			$arFileTmp = CFile::ResizeImageGet(
				$id,
				array('width' => $size, 'height' => $size),
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
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
						INNER JOIN b_im_relation R1 ON M.ID > R1.LAST_ID AND M.CHAT_ID = R1.CHAT_ID AND R1.MESSAGE_TYPE IN ('".IM_MESSAGE_OPEN."','".IM_MESSAGE_CHAT."') AND R1.STATUS < ".IM_STATUS_READ."
						WHERE R1.USER_ID = ".$userId;
		$dbRes = $DB->Query($sqlCounter, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($row = $dbRes->Fetch())
			CUserCounter::Set($userId, 'im_chat_v2', $row['CNT'], '**', false);
		else
			CUserCounter::Set($userId, 'im_chat_v2', 0, '**', false);

		return true;
	}
}
?>
