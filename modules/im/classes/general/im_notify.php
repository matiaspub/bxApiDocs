<?
use Bitrix\Im as IM;

class CIMNotify
{
	private $user_id = 0;
	private $bHideLink = false;

	public function __construct($user_id = false, $arParams = Array())
	{
		global $USER;
		$this->user_id = intval($user_id);
		if ($this->user_id == 0)
			$this->user_id = IntVal($USER->GetID());
		if (isset($arParams['HIDE_LINK']) && $arParams['HIDE_LINK'] == 'Y')
			$this->bHideLink = true;
	}

	public static function Add($arFields)
	{
		$arFields['MESSAGE_TYPE'] = IM_MESSAGE_SYSTEM;

		return CIMMessenger::Add($arFields);
	}

	public function GetNotifyList($arParams = array())
	{
		global $DB;

		$iNumPage = 1;
		if (isset($arParams['PAGE']) && intval($arParams['PAGE']) >= 0)
			$iNumPage = intval($arParams['PAGE']);

		$bTimeZone = isset($arParams['USE_TIME_ZONE']) && $arParams['USE_TIME_ZONE'] == 'N'? false: true;

		$sqlStr = "
			SELECT COUNT(M.ID) as CNT, M.CHAT_ID
			FROM b_im_relation R
			INNER JOIN b_im_message M ON M.CHAT_ID = R.CHAT_ID
			WHERE R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."'
			GROUP BY M.CHAT_ID
		";
		$res_cnt = $DB->Query($sqlStr);
		$res_cnt = $res_cnt->Fetch();
		$cnt = $res_cnt["CNT"];
		$chatId = $res_cnt["CHAT_ID"];

		$arNotify = Array();
		if ($cnt > 0)
		{
			if (!$bTimeZone)
				CTimeZone::Disable();

			$strSql ="
				SELECT
					M.ID,
					M.CHAT_ID,
					M.MESSAGE,
					M.MESSAGE_OUT,
					".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
					M.NOTIFY_TYPE,
					M.NOTIFY_MODULE,
					M.NOTIFY_EVENT,
					M.NOTIFY_TITLE,
					M.NOTIFY_BUTTONS,
					M.NOTIFY_TAG,
					M.NOTIFY_SUB_TAG,
					M.NOTIFY_READ,
					$this->user_id TO_USER_ID,
					M.AUTHOR_ID FROM_USER_ID
				FROM b_im_message M
				WHERE M.CHAT_ID = ".$chatId." #LIMIT#
				ORDER BY M.DATE_CREATE DESC, ID DESC
			";
			if (!$bTimeZone)
				CTimeZone::Enable();

			if ($iNumPage == 0)
			{
				$dbType = strtolower($DB->type);
				if ($dbType== "mysql")
					$sqlLimit = " AND M.DATE_CREATE > DATE_SUB(NOW(), INTERVAL 30 DAY)";
				else if ($dbType == "mssql")
					$sqlLimit = " AND M.DATE_CREATE > dateadd(day, -30, getdate())";
				else if ($dbType == "oracle")
					$sqlLimit = " AND M.DATE_CREATE > SYSDATE-30";

				$strSql = $DB->TopSql($strSql, 20);
				$dbRes = $DB->Query(str_replace("#LIMIT#", $sqlLimit, $strSql), false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			else
			{
				$dbRes = new CDBResult();
				$dbRes->NavQuery(str_replace("#LIMIT#", "", $strSql), $cnt, Array('iNumPage' => $iNumPage, 'nPageSize' => 20));
			}

			$arGetUsers = Array();
			while ($arRes = $dbRes->Fetch())
			{
				if ($this->bHideLink)
					$arRes['HIDE_LINK'] = 'Y';

				$arNotify[$arRes['ID']] = $arRes;
				$arGetUsers[] = $arRes['FROM_USER_ID'];
			}
			if (empty($arNotify))
				return $arNotify;

			$arUsers = CIMContactList::GetUserData(Array('ID' => $arGetUsers, 'DEPARTMENT' => 'N', 'USE_CACHE' => 'Y', 'CACHE_TTL' => 86400));
			$arGetUsers = $arUsers['users'];

			foreach ($arNotify as $key => $value)
			{
				$value['FROM_USER_DATA'] = $arGetUsers;
				$arNotify[$key] = self::GetFormatNotify($value);
			}
		}
		return $arNotify;
	}

	public function GetUnreadNotify($arParams = Array())
	{
		global $DB;

		$order = isset($arParams['ORDER']) && $arParams['ORDER'] == 'ASC'? 'ASC': 'DESC';
		$bSpeedCheck = isset($arParams['SPEED_CHECK']) && $arParams['SPEED_CHECK'] == 'N'? false: true;
		$bTimeZone = isset($arParams['USE_TIME_ZONE']) && $arParams['USE_TIME_ZONE'] == 'N'? false: true;
		$bGetOnlyFlash = isset($arParams['GET_ONLY_FLASH']) && $arParams['GET_ONLY_FLASH'] == 'Y'? true: false;

		$arNotify['result'] = false;
		$arNotify['notify'] = Array();
		$arNotify['unreadNotify'] = Array();
		$arNotify['loadNotify'] = false;
		$arNotify['countNotify'] = 0;
		$arNotify['maxNotify'] = 0;

		$bLoadNotify = $bSpeedCheck? !CIMMessenger::SpeedFileExists($this->user_id, IM_SPEED_NOTIFY): true;
		if ($bLoadNotify)
		{
			$strSql = "SELECT CHAT_ID, STATUS FROM b_im_relation WHERE USER_ID = ".$this->user_id." AND MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."'";
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
			{
				$chatId = intval($arRes['CHAT_ID']);
				$chatStatus = $arRes['STATUS'];
			}
			else
				return $arNotify;

			if (!$bTimeZone)
				CTimeZone::Disable();
			$strSql ="
				SELECT
					M.ID,
					M.CHAT_ID,
					M.MESSAGE,
					M.MESSAGE_OUT,
					".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
					M.NOTIFY_TYPE,
					M.NOTIFY_MODULE,
					M.NOTIFY_EVENT,
					M.NOTIFY_TITLE,
					M.NOTIFY_BUTTONS,
					M.NOTIFY_TAG,
					M.NOTIFY_SUB_TAG,
					M.NOTIFY_READ,
					$this->user_id TO_USER_ID,
					M.AUTHOR_ID FROM_USER_ID
				FROM b_im_message M
				WHERE M.CHAT_ID = ".$chatId." AND M.NOTIFY_READ = 'N'
			";
			if (!$bTimeZone)
				CTimeZone::Enable();
			$strSql = $DB->TopSql($strSql, 500);
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$arMark = Array();
			$arGetUsers = Array();
			while ($arRes = $dbRes->Fetch())
			{
				if ($this->bHideLink)
					$arRes['HIDE_LINK'] = 'Y';

				$arNotify['original_notify'][$arRes['ID']] = $arRes;
				$arNotify['notify'][$arRes['ID']] = $arRes;
				$arNotify['unreadNotify'][$arRes['ID']] = $arRes['ID'];

				if ($chatStatus == IM_STATUS_UNREAD && (!isset($arMark[$arRes["CHAT_ID"]]) || $arMark[$arRes["CHAT_ID"]] < $arRes["ID"]))
					$arMark[$arRes["CHAT_ID"]] = $arRes["ID"];

				if ($arNotify['maxNotify'] < $arRes['ID'])
					$arNotify['maxNotify'] = $arRes['ID'];

				$arGetUsers[] = $arRes['FROM_USER_ID'];
			}
			foreach ($arMark as $chatId => $lastSendId)
				CIMNotify::SetLastSendId($chatId, $lastSendId);

			$arNotify['countNotify'] = $this->GetNotifyCounter($arNotify);
			CIMMessenger::SpeedFileCreate($this->user_id, $arNotify['countNotify'], IM_SPEED_NOTIFY);

			$arUsers = CIMContactList::GetUserData(Array('ID' => $arGetUsers, 'DEPARTMENT' => 'N', 'USE_CACHE' => 'Y', 'CACHE_TTL' => 86400));
			$arGetUsers = $arUsers['users'];

			if ($bGetOnlyFlash)
			{
				foreach ($arNotify['notify'] as $key => $value)
				{
					if (isset($_SESSION['IM_FLASHED_NOTIFY'][$key]))
					{
						unset($arNotify['notify'][$key]);
						unset($arNotify['original_notify'][$key]);
						$arNotify['loadNotify'] = true;
					}
					else
					{
						$value['FROM_USER_DATA'] = $arGetUsers;
						$arNotify['notify'][$key] = self::GetFormatNotify($value);
					}
				}
			}
			else
			{
				foreach ($arNotify['notify'] as $key => $value)
				{
					$value['FROM_USER_DATA'] = $arGetUsers;
					$arNotify['notify'][$key] = self::GetFormatNotify($value);
				}
			}

			$arNotify['result'] = true;
		}
		else
		{
			$arNotify['countNotify'] = $this->GetNotifyCounter();
			if ($arNotify['countNotify'] > 0)
				$arNotify['loadNotify'] = true;
		}

		return $arNotify;
	}

	public static function GetUnsendNotify()
	{
		global $DB;

		$strSqlRelation ="
			SELECT
				R.CHAT_ID,
				R.LAST_SEND_ID,
				R.USER_ID TO_USER_ID,
				U1.LOGIN TO_USER_LOGIN,
				U1.NAME TO_USER_NAME,
				U1.LAST_NAME TO_USER_LAST_NAME,
				U1.SECOND_NAME TO_USER_SECOND_NAME,
				U1.EMAIL TO_USER_EMAIL,
				U1.ACTIVE TO_USER_ACTIVE,
				U1.LID TO_USER_LID,
				U1.AUTO_TIME_ZONE AUTO_TIME_ZONE,
				U1.TIME_ZONE TIME_ZONE,
				U1.TIME_ZONE_OFFSET TIME_ZONE_OFFSET
			FROM b_im_relation R
			LEFT JOIN b_user U1 ON U1.ID = R.USER_ID
			WHERE R.MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."' AND R.STATUS < ".IM_STATUS_NOTIFY."
		";
		$dbResRelation = $DB->Query($strSqlRelation, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arNotify = Array();

		CTimeZone::Disable();
		while ($arResRelation = $dbResRelation->Fetch())
		{
			$strSql ="
				SELECT
					M.ID,
					M.CHAT_ID,
					M.MESSAGE,
					M.MESSAGE_OUT,
					".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')."+".CIMMail::GetUserOffset($arResRelation)." DATE_CREATE,
					M.NOTIFY_TYPE,
					M.NOTIFY_MODULE,
					M.NOTIFY_EVENT,
					M.NOTIFY_TITLE,
					M.NOTIFY_BUTTONS,
					M.NOTIFY_TAG,
					M.NOTIFY_SUB_TAG,
					M.EMAIL_TEMPLATE,
					M.AUTHOR_ID FROM_USER_ID,
					U2.LOGIN FROM_USER_LOGIN,
					U2.NAME FROM_USER_NAME,
					U2.LAST_NAME FROM_USER_LAST_NAME,
					U2.SECOND_NAME FROM_USER_SECOND_NAME
				FROM b_im_message M
				LEFT JOIN b_user U2 ON U2.ID = M.AUTHOR_ID
				WHERE M.ID > ".intval($arResRelation['LAST_SEND_ID'])." AND M.CHAT_ID = ".intval($arResRelation['CHAT_ID'])."
				ORDER BY ID DESC
			";
			$strSql = $DB->TopSql($strSql, 200);
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			while ($arRes = $dbRes->Fetch())
			{
				$arRes = array_merge($arRes, $arResRelation);
				$arNotify[$arRes['ID']] = $arRes;
			}
			if (count($arNotify) > 5000)
			{
				break;
			}
		}
		CTimeZone::Enable();

		return $arNotify;
	}

	public static function GetFlashNotify($arUnreadNotify)
	{
		$arFlashNotify = Array();
		if (isset($_SESSION['IM_FLASHED_NOTIFY']))
		{
			foreach ($arUnreadNotify as $value)
			{
				if (!isset($_SESSION['IM_FLASHED_NOTIFY'][$value]))
				{
					$_SESSION['IM_FLASHED_NOTIFY'][$value] = $value;
					$arFlashNotify[$value] = true;
				}
				else
					$arFlashNotify[$value] = false;
			}
		}
		else
		{
			$_SESSION['IM_FLASHED_NOTIFY'] = Array();
			foreach ($arUnreadNotify as $value)
			{
				$_SESSION['IM_FLASHED_NOTIFY'][$value] = $value;
				$arFlashNotify[$value] = true;
			}
		}

		return $arFlashNotify;
	}

	public function GetNotify($ID)
	{
		global $DB;

		$ID = intval($ID);

		$strSql = "SELECT M.* FROM b_im_relation R, b_im_message M WHERE M.ID = ".$ID." AND R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."' AND R.CHAT_ID = M.CHAT_ID";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
			return $arRes;

		return false;
	}

	public static function GetFormatNotify($arFields)
	{
		$CCTP = new CTextParser();
		$CCTP->allow["SMILES"] = "N";
		$CCTP->allow["VIDEO"] = "N";

		if (isset($arFields['HIDE_LINK']) && $arFields['HIDE_LINK'] == 'Y')
			$CCTP->allow["ANCHOR"] = "N";

		$CCTP->link_target = "_self";
		$arNotify = Array(
			'id' => $arFields['ID'],
			'type' => $arFields['NOTIFY_TYPE'],
			'date' => $arFields['DATE_CREATE'],
			'silent' => $arFields['NOTIFY_SILENT']? 'Y': 'N',
			'text' => str_replace('#BR#', '<br>', $CCTP->convertText($arFields['MESSAGE'])),
			'tag' => strlen($arFields['NOTIFY_TAG'])>0? md5($arFields['NOTIFY_TAG']): '',
			'original_tag' => $arFields['NOTIFY_TAG'],
			'read' => $arFields['NOTIFY_READ'],
			'settingName' => $arFields['NOTIFY_MODULE'].'|'.$arFields['NOTIFY_EVENT']
		);
		if (!isset($arFields["FROM_USER_DATA"]))
		{
			$arUsers = CIMContactList::GetUserData(Array('ID' => $arFields['FROM_USER_ID'], 'DEPARTMENT' => 'N', 'USE_CACHE' => 'Y', 'CACHE_TTL' => 86400));
			$arFields["FROM_USER_DATA"] = $arUsers['users'];
		}

		$arNotify['userId'] = $arFields["FROM_USER_ID"];
		$arNotify['userName'] = $arFields["FROM_USER_DATA"][$arFields["FROM_USER_ID"]]['name'];
		$arNotify['userColor'] = $arFields["FROM_USER_DATA"][$arFields["FROM_USER_ID"]]['color'];
		$arNotify['userAvatar'] = $arFields["FROM_USER_DATA"][$arFields["FROM_USER_ID"]]['avatar'];
		$arNotify['userLink'] = $arFields["FROM_USER_DATA"][$arFields["FROM_USER_ID"]]['profile'];

		if ($arFields['NOTIFY_TYPE'] == IM_NOTIFY_CONFIRM)
		{
			$arNotify['buttons'] = unserialize($arFields['NOTIFY_BUTTONS']);
		}
		else
		{
			$arNotify['title'] = htmlspecialcharsbx($arFields['NOTIFY_TITLE']);
		}

		return $arNotify;
	}

	public function MarkNotifyRead($id = 0, $checkAll = false)
	{
		global $DB;

		$id = intval($id);
		$chatId = 0;

		if ($id > 0)
		{
			$strSql ="
				SELECT M.ID, M.CHAT_ID FROM b_im_relation R
				INNER JOIN b_im_message M ON M.ID = ".$id." AND M.CHAT_ID = R.CHAT_ID AND M.NOTIFY_READ = 'N'
				WHERE R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."'
			";
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
			{
				if ($checkAll)
				{
					$id = $arRes['ID'];
					$chatId = intval($arRes['CHAT_ID']);
					$strSql ="UPDATE b_im_message SET NOTIFY_READ = 'Y' WHERE CHAT_ID = ".$chatId." AND NOTIFY_READ='N' AND NOTIFY_TYPE > '".IM_NOTIFY_CONFIRM."' AND ID <= ".$id."";
					$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					self::SetLastId($chatId, $id);
				}
				else
				{
					$id = $arRes['ID'];
					$chatId = intval($arRes['CHAT_ID']);
					$strSql ="UPDATE b_im_message SET NOTIFY_READ = 'Y' WHERE CHAT_ID = ".$chatId." AND NOTIFY_READ='N' AND NOTIFY_TYPE > '".IM_NOTIFY_CONFIRM."' AND ID = ".$id." ";
					$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					self::SetLastId($chatId);
				}
				//CUserCounter::Set($this->user_id, 'im_notify_v2', 0, '**', false);
			}
			if ($chatId > 0 && CModule::IncludeModule("pull"))
			{
				if ($checkAll)
				{
					CPullStack::AddByUser($this->user_id, Array(
						'module_id' => 'im',
						'command' => 'readNotify',
						'params' => Array(
							'chatId' => $chatId,
							'lastId' => $id
						),
					));
				}
				else
				{
					CPullStack::AddByUser($this->user_id, Array(
						'module_id' => 'im',
						'command' => 'readNotifyOne',
						'params' => Array(
							'chatId' => $chatId,
							'id' => $id
						),
					));
				}
				CIMMessenger::SendBadges($this->user_id);
			}
			CIMMessenger::SpeedFileDelete($this->user_id, IM_SPEED_NOTIFY);
		}

		return true;
	}

	public static function SetLastId($chatId, $lastId = null)
	{
		global $DB;

		if (intval($chatId) <= 0)
			return false;

		$ssqlLastId = "";
		if (!is_null($lastId))
		{
			$ssqlLastId = "LAST_ID = (case when LAST_ID < ".intval($lastId)." then ".intval($lastId)." else LAST_ID end),";
			$ssqlLastId .= "LAST_SEND_ID = (case when LAST_SEND_ID < ".intval($lastId)." then ".intval($lastId)." else LAST_SEND_ID end),";
		}

		$strSql = "UPDATE b_im_relation SET ".$ssqlLastId." STATUS = ".IM_STATUS_READ." WHERE CHAT_ID = ".intval($chatId);
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public static function SetLastSendId($chatId, $lastSendId)
	{
		global $DB;

		if (intval($chatId) <= 0 || intval($lastSendId) <= 0)
			return false;

		$strSql = "
		UPDATE b_im_relation SET
			LAST_SEND_ID = (case when LAST_SEND_ID < ".intval($lastSendId)." then ".intval($lastSendId)." else LAST_SEND_ID end),
			STATUS = ".IM_STATUS_NOTIFY."
		WHERE CHAT_ID = ".intval($chatId);
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public function Confirm($ID, $VALUE)
	{
		global $DB;

		$ID = intval($ID);

		$strSql = "SELECT M.* FROM b_im_relation R, b_im_message M WHERE M.ID = ".$ID." AND R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."' AND R.CHAT_ID = M.CHAT_ID AND M.NOTIFY_TYPE = ".IM_NOTIFY_CONFIRM;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$arRes['RELATION_USER_ID'] = $this->user_id;

			if (strlen($arRes['NOTIFY_TAG'])>0)
			{
				foreach(GetModuleEvents("im", "OnBeforeConfirmNotify", true) as $arEvent)
					if (ExecuteModuleEventEx($arEvent, Array($arRes['NOTIFY_MODULE'], $arRes['NOTIFY_TAG'], $VALUE, $arRes))===false)
						return false;
			}

			IM\MessageTable::delete($ID);
			//CUserCounter::Decrement($this->user_id, 'im_notify_v2', '**', false);

			if (strlen($arRes['NOTIFY_TAG'])>0)
			{
				foreach(GetModuleEvents("im", "OnAfterConfirmNotify", true) as $arEvent)
					ExecuteModuleEventEx($arEvent, array($arRes['NOTIFY_MODULE'], $arRes['NOTIFY_TAG'], $VALUE, $arRes));
			}

			if (CModule::IncludeModule("pull"))
			{
				CPullStack::AddByUser($this->user_id, Array(
					'module_id' => 'im',
					'command' => 'confirmNotify',
					'params' => Array(
						'chatId' => intval($arRes['CHAT_ID']),
						'id' => $ID
					),
				));
				CIMMessenger::SendBadges($this->user_id);
			}

			CIMMessenger::SpeedFileDelete($this->user_id, IM_SPEED_NOTIFY);
			return true;
		}

		return false;
	}

	public static function Delete($ID)
	{
		$ID = intval($ID);

		IM\MessageTable::delete($ID);

		foreach(GetModuleEvents("im", "OnAfterDeleteNotify", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));

		return true;
	}

	public function DeleteWithCheck($ID)
	{
		global $DB;

		$ID = intval($ID);

		$strSql = "SELECT M.* FROM b_im_relation R, b_im_message M WHERE M.ID = ".$ID." AND R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."' AND R.CHAT_ID = M.CHAT_ID";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			IM\MessageTable::delete($ID);

			$arRes['RELATION_USER_ID'] = $this->user_id;
			foreach(GetModuleEvents("im", "OnAfterDeleteNotify", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID, $arRes));

			CIMMessenger::SpeedFileDelete($this->user_id, IM_SPEED_NOTIFY);

			return true;
		}

		return false;
	}

	public static function DeleteByTag($notifyTag, $authorId = false)
	{
		global $DB;
		if (strlen($notifyTag) <= 0)
			return false;

		$sqlUser = "";
		$sqlUser2 = "";
		if ($authorId !== false)
		{
			$sqlUser = " AND M.AUTHOR_ID = ".intval($authorId);
			$sqlUser2 = " AND AUTHOR_ID = ".intval($authorId);
		}

		$dbRes = $DB->Query("SELECT R.USER_ID, R.STATUS FROM b_im_relation R, b_im_message M WHERE M.CHAT_ID = R.CHAT_ID AND M.NOTIFY_TAG = '".$DB->ForSQL($notifyTag)."'".$sqlUser, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$arUsers = Array();
		while ($row = $dbRes->Fetch())
		{
			$count = $row['STATUS'] < IM_STATUS_READ? 1: 0;
			if (isset($arUsers[$row['USER_ID']]))
				$arUsers[$row['USER_ID']] += $count;
			else
				$arUsers[$row['USER_ID']] = $count;
		}

		$pullActive = false;
		if (CModule::IncludeModule("pull"))
			$pullActive = true;

		$arUsersSend = Array();
		foreach ($arUsers as $userId => $count)
		{
			CIMMessenger::SpeedFileDelete($userId, IM_SPEED_NOTIFY);
			if ($count > 0)
			{
				//CUserCounter::Decrement($userId, 'im_notify_v2', '**', false);
				$arUsersSend[] = $userId;
			}
			if ($pullActive)
			{
				CPushManager::DeleteFromQueueBySubTag($userId, $notifyTag);
			}
		}

		$strSql = "DELETE FROM b_im_message WHERE NOTIFY_TAG = '".$DB->ForSQL($notifyTag)."'".$sqlUser2;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		CIMMessenger::SendBadges($arUsersSend);


		return true;
	}

	public static function DeleteBySubTag($notifySubTag, $authorId = false)
	{
		global $DB;
		if (strlen($notifySubTag) <= 0)
			return false;

		$sqlUser = "";
		$sqlUser2 = "";
		if ($authorId !== false)
		{
			$sqlUser = " AND M.AUTHOR_ID = ".intval($authorId);
			$sqlUser2 = " AND AUTHOR_ID = ".intval($authorId);
		}

		$dbRes = $DB->Query("SELECT R.USER_ID, R.STATUS FROM b_im_relation R, b_im_message M WHERE M.CHAT_ID = R.CHAT_ID AND M.NOTIFY_SUB_TAG = '".$DB->ForSQL($notifySubTag)."'".$sqlUser, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$arUsers = Array();
		while ($row = $dbRes->Fetch())
		{
			$count = $row['STATUS'] < IM_STATUS_READ? 1: 0;
			if (isset($arUsers[$row['USER_ID']]))
				$arUsers[$row['USER_ID']] += $count;
			else
				$arUsers[$row['USER_ID']] = $count;
		}

		$pullActive = false;
		if (CModule::IncludeModule("pull"))
			$pullActive = true;

		$arUsersSend = Array();
		foreach ($arUsers as $userId => $count)
		{
			CIMMessenger::SpeedFileDelete($userId, IM_SPEED_NOTIFY);
			if ($count > 0)
			{
				$arUsersSend[] = $userId;
				//CUserCounter::Decrement($userId, 'im_notify_v2', '**', false);
			}
			if ($pullActive)
			{
				CPushManager::DeleteFromQueueBySubTag($userId, $notifySubTag);
			}
		}

		$strSql = "DELETE FROM b_im_message WHERE NOTIFY_SUB_TAG = '".$DB->ForSQL($notifySubTag)."'".$sqlUser2;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		CIMMessenger::SendBadges($arUsersSend);

		return true;
	}

	public static function DeleteByModule($moduleId, $moduleEvent = '')
	{
		global $DB;
		if (strlen($moduleId) <= 0)
			return false;

		$sqlEvent = '';
		if (strlen($moduleEvent) > 0)
			$sqlEvent = " AND NOTIFY_EVENT = '".$DB->ForSQL($moduleEvent)."'";

		$strSql = "DELETE FROM b_im_message WHERE NOTIFY_MODULE = '".$DB->ForSQL($moduleId)."'".$sqlEvent;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public function GetNotifyCounter($arNotify = Array())
	{
		$count = 0;
		if (isset($arNotify['unreadNotify']) && !empty($arNotify['unreadNotify']) && isset($arNotify['notify']))
		{
			foreach ($arNotify['unreadNotify'] as $key => $value)
			{
				if (!isset($arNotify['notify'][$key]))
					continue;

				$count++;
			}
		}
		else
		{
			$count = CIMMessenger::SpeedFileGet($this->user_id, IM_SPEED_NOTIFY);
		}
		return intval($count);
	}

	public static function SetUnreadCounter($userId)
	{
		return false;
		global $DB;

		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		$strSql ="
			SELECT COUNT(M.ID) as CNT
			FROM b_im_message M
			INNER JOIN b_im_relation R1 ON M.ID > R1.LAST_ID AND M.CHAT_ID = R1.CHAT_ID
			WHERE R1.USER_ID = ".$userId." AND R1.MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."' AND R1.STATUS < ".IM_STATUS_READ;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($row = $dbRes->Fetch())
			CUserCounter::Set($userId, 'im_notify_v2', $row['CNT'], '**', false);
		else
			CUserCounter::Set($userId, 'im_notify_v2', 0, '**', false);

		return true;
	}
}
?>