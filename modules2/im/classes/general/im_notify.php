<?


class CIMNotify
{
	private $user_id = 0;
	private $bHideLink = false;

	function __construct($user_id = false, $arParams = Array())
	{
		global $USER;
		$this->user_id = intval($user_id);
		if ($this->user_id == 0)
			$this->user_id = IntVal($USER->GetID());
		if (isset($arParams['hide_link']) && $arParams['hide_link'] == true)
			$this->bHideLink = true;
	}

	public static function Add($arFields)
	{
		$arFields['MESSAGE_TYPE'] = IM_MESSAGE_SYSTEM;

		return CIMMessenger::Add($arFields);
	}

	static public function GetNotifyList($arParams)
	{
		global $DB;

		$iNumPage = 1;
		if (isset($arParams['PAGE']) && intval($arParams['PAGE']) > 0)
			$iNumPage = intval($arParams['PAGE']);

		$bTimeZone = isset($arParams['USE_TIME_ZONE']) && $arParams['USE_TIME_ZONE'] == 'N'? false: true;


		$sqlStr = "
			SELECT COUNT(M.ID) as CNT
			FROM b_im_relation R
			INNER JOIN b_im_message M ON M.NOTIFY_READ = 'Y' AND M.CHAT_ID = R.CHAT_ID
			WHERE R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."'
		";
		$res_cnt = $DB->Query($sqlStr);
		$res_cnt = $res_cnt->Fetch();
		$cnt = $res_cnt["CNT"];

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
					".$DB->DateToCharFunction('M.DATE_CREATE')." DATE_CREATE,
					M.NOTIFY_TYPE,
					M.NOTIFY_MODULE,
					M.NOTIFY_TITLE,
					M.NOTIFY_BUTTONS,
					M.NOTIFY_TAG,
					M.NOTIFY_SUB_TAG,
					M.NOTIFY_READ,
					R.LAST_ID,
					R.USER_ID TO_USER_ID,
					M.AUTHOR_ID FROM_USER_ID
				FROM b_im_relation R
				INNER JOIN b_im_message M ON M.NOTIFY_READ = 'Y' AND M.CHAT_ID = R.CHAT_ID
				WHERE R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."'
				ORDER BY M.DATE_CREATE DESC, ID DESC
			";
			if (!$bTimeZone)
				CTimeZone::Enable();
			$dbRes = new CDBResult();
			$dbRes->NavQuery($strSql, $cnt, Array('iNumPage' => $iNumPage, 'nPageSize' => 20));

			while ($arRes = $dbRes->Fetch())
			{
				if ($this->bHideLink)
					$arRes['HIDE_LINK'] = 'Y';

				$arNotify[$arRes['ID']] = self::GetFormatNotify($arRes);
			}
		}
		return $arNotify;
	}

	static public function GetUnreadNotify($arParams = Array())
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
		$arNotify['maxNotify'] = 0;

		$bLoadNotify = $bSpeedCheck? !CIMMessenger::SpeedFileExists($this->user_id, IM_SPEED_NOTIFY): true;
		if ($bLoadNotify)
		{
			if (!$bTimeZone)
				CTimeZone::Disable();
			$strSql ="
				SELECT
					M.ID,
					M.CHAT_ID,
					M.MESSAGE,
					M.MESSAGE_OUT,
					".$DB->DateToCharFunction('M.DATE_CREATE')." DATE_CREATE,
					M.NOTIFY_TYPE,
					M.NOTIFY_MODULE,
					M.NOTIFY_EVENT,
					M.NOTIFY_TITLE,
					M.NOTIFY_BUTTONS,
					M.NOTIFY_TAG,
					M.NOTIFY_SUB_TAG,
					R.LAST_ID,
					R.STATUS,
					R.USER_ID TO_USER_ID,
					M.AUTHOR_ID FROM_USER_ID
				FROM b_im_relation R
				INNER JOIN b_im_message M ON M.NOTIFY_READ = 'N' AND M.CHAT_ID = R.CHAT_ID
				WHERE R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."'
				ORDER BY DATE_CREATE ".($order == "DESC"? "DESC": "ASC").", ID ".($order == "DESC"? "DESC": "ASC")."
			";
			if (!$bTimeZone)
				CTimeZone::Enable();
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$arMark = Array();
			while ($arRes = $dbRes->Fetch())
			{
				if ($this->bHideLink)
					$arRes['HIDE_LINK'] = 'Y';

				$arNotify['original_notify'][$arRes['ID']] = $arRes;
				$arNotify['notify'][$arRes['ID']] = $bGetOnlyFlash? $arRes: self::GetFormatNotify($arRes);
				$arNotify['unreadNotify'][$arRes['ID']] = $arRes['ID'];

				if ($arRes['STATUS'] == IM_STATUS_UNREAD && (!isset($arMark[$arRes["CHAT_ID"]]) || $arMark[$arRes["CHAT_ID"]] < $arRes["ID"]))
					$arMark[$arRes["CHAT_ID"]] = $arRes["ID"];

				if ($arNotify['maxNotify'] < $arRes['ID'])
					$arNotify['maxNotify'] = $arRes['ID'];
			}
			foreach ($arMark as $chatId => $lastSendId)
				CIMNotify::SetLastSendId($chatId, $lastSendId);

			$arNotify['countNotify'] = $this->GetNotifyCounter($arNotify);
			CIMMessenger::SpeedFileCreate($this->user_id, $arNotify['countNotify'], IM_SPEED_NOTIFY);

			if ($bGetOnlyFlash)
			{
				foreach ($arNotify['notify'] as $key => $value)
				{
					if (isset($_SESSION['IM_FLASHED_NOTIFY']) && in_array($key, $_SESSION['IM_FLASHED_NOTIFY']))
					{
						unset($arNotify['notify'][$key]);
						unset($arNotify['original_notify'][$key]);
						$arNotify['loadNotify'] = true;
					}
					else
					{
						$arNotify['notify'][$key] = self::GetFormatNotify($value);
					}
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

	public static function GetUnsendNotify($order = "DESC")
	{
		global $DB;

		$strSql ="
			SELECT
				M.ID,
				M.CHAT_ID,
				M.MESSAGE,
				M.MESSAGE_OUT,
				".$DB->DateToCharFunction('M.DATE_CREATE')." DATE_CREATE,
				M.NOTIFY_TYPE,
				M.NOTIFY_MODULE,
				M.NOTIFY_EVENT,
				M.NOTIFY_TITLE,
				M.NOTIFY_BUTTONS,
				M.NOTIFY_TAG,
				M.NOTIFY_SUB_TAG,
				M.EMAIL_TEMPLATE,
				R.LAST_SEND_ID,
				R.USER_ID TO_USER_ID,
				U1.LOGIN TO_USER_LOGIN,
				U1.NAME TO_USER_NAME,
				U1.LAST_NAME TO_USER_LAST_NAME,
				U1.SECOND_NAME TO_USER_SECOND_NAME,
				U1.EMAIL TO_USER_EMAIL,
				U1.ACTIVE TO_USER_ACTIVE,
				U1.LID TO_USER_LID,
				M.AUTHOR_ID FROM_USER_ID,
				U2.LOGIN FROM_USER_LOGIN,
				U2.NAME FROM_USER_NAME,
				U2.LAST_NAME FROM_USER_LAST_NAME,
				U2.SECOND_NAME FROM_USER_SECOND_NAME
			FROM b_im_relation R
			INNER JOIN b_im_message M ON M.NOTIFY_READ = 'N' AND M.ID > R.LAST_SEND_ID AND M.CHAT_ID = R.CHAT_ID
			LEFT JOIN b_user U1 ON U1.ID = R.USER_ID
			LEFT JOIN b_user U2 ON U2.ID = M.AUTHOR_ID
			WHERE R.MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."' AND R.STATUS < ".IM_STATUS_NOTIFY."
			ORDER BY DATE_CREATE ".($order == "DESC"? "DESC": "ASC").", ID ".($order == "DESC"? "DESC": "ASC")."
		";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arNotify = Array();
		while ($arRes = $dbRes->Fetch())
			$arNotify[$arRes['ID']] = $arRes;

		return $arNotify;
	}

	public static function GetFlashNotify($arUnreadNotify)
	{
		$arResult = Array();

		if (!empty($arUnreadNotify))
		{
			if (isset($_SESSION['IM_FLASHED_NOTIFY']))
			{
				$arFlashMessage = array_diff($arUnreadNotify, $_SESSION['IM_FLASHED_NOTIFY']);
				$_SESSION['IM_FLASHED_NOTIFY'] = array_merge($_SESSION['IM_FLASHED_NOTIFY'], $arFlashMessage);

				foreach ($arUnreadNotify as $key => $value)
				{
					if (isset($arFlashMessage[$key]))
						$arResult[$value] = true;
					else
						$arResult[$value] = false;
				}
			}
			else
			{
				$_SESSION['IM_FLASHED_NOTIFY'] = $arUnreadNotify;

				foreach ($arUnreadNotify as $value)
					$arResult[$value] = true;
			}
		}

		return $arResult;
	}

	static public function GetNotify($ID)
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

		if (isset($arFields['HIDE_LINK']) && $arFields['HIDE_LINK'] == 'Y')
			$CCTP->allow["ANCHOR"] = "N";

		$CCTP->link_target = "_self";
		$arNotify = Array(
			'id' => $arFields['ID'],
			'type' => $arFields['NOTIFY_TYPE'],
			'date' => isset($arFields['TIMESTAMP'])? intval($arFields['TIMESTAMP']): MakeTimeStamp($arFields['DATE_CREATE']),
			'text' => str_replace('#BR#', '<br>', $CCTP->convertText($arFields['MESSAGE'])),
			'tag' => strlen($arFields['NOTIFY_TAG'])>0? md5($arFields['NOTIFY_TAG']): '',
			'original_tag' => $arFields['NOTIFY_TAG']
		);

		$arUsers = CIMContactList::GetUserData(Array('ID' => $arFields['FROM_USER_ID'], 'DEPARTMENT' => 'N', 'USE_CACHE' => 'Y', 'CACHE_TTL' => 86400));
		$arNotify['userId'] = $arFields["FROM_USER_ID"];
		$arNotify['userName'] = $arUsers['users'][$arFields["FROM_USER_ID"]]['name'];
		$arNotify['userAvatar'] = $arUsers['users'][$arFields["FROM_USER_ID"]]['avatar'];
		$arNotify['userLink'] = $arUsers['users'][$arFields["FROM_USER_ID"]]['profile'];

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

	static public function MarkNotifyRead($id = 0, $checkAll = false)
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
					$strSql ="UPDATE b_im_message SET NOTIFY_READ = 'Y' WHERE CHAT_ID = ".$chatId." AND NOTIFY_TYPE > '".IM_NOTIFY_CONFIRM."' AND ID <= ".$id."";
					$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					self::SetLastId($chatId, $id);
				}
				else
				{
					$id = $arRes['ID'];
					$chatId = intval($arRes['CHAT_ID']);
					$strSql ="UPDATE b_im_message SET NOTIFY_READ = 'Y' WHERE ID = ".$id." AND NOTIFY_TYPE > '".IM_NOTIFY_CONFIRM."'";
					$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					self::SetLastId($chatId);
				}
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
			$ssqlLastId = "LAST_ID = ".intval($lastId).", LAST_SEND_ID = ".intval($lastId).", ";

		$strSql = "UPDATE b_im_relation SET ".$ssqlLastId." STATUS = ".IM_STATUS_READ." WHERE CHAT_ID = ".intval($chatId);
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public static function SetLastSendId($chatId, $lastSendId)
	{
		global $DB;

		if (intval($chatId) <= 0 || intval($lastSendId) <= 0)
			return false;

		$strSql = "UPDATE b_im_relation SET LAST_SEND_ID = ".intval($lastSendId).", STATUS = ".IM_STATUS_NOTIFY." WHERE CHAT_ID = ".intval($chatId);
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	static public function Confirm($ID, $VALUE)
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

			$strSql = "DELETE FROM b_im_message WHERE ID = ".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

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
			}

			CIMMessenger::SpeedFileDelete($this->user_id, IM_SPEED_NOTIFY);
			return true;
		}

		return false;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);

		$strSql = "DELETE FROM b_im_message WHERE ID = ".$ID;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		foreach(GetModuleEvents("im", "OnAfterDeleteNotify", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID));

		return true;
	}

	static public function DeleteWithCheck($ID)
	{
		global $DB;

		$ID = intval($ID);

		$strSql = "SELECT M.* FROM b_im_relation R, b_im_message M WHERE M.ID = ".$ID." AND R.USER_ID = ".$this->user_id." AND R.MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."' AND R.CHAT_ID = M.CHAT_ID";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$strSql = "DELETE FROM b_im_message WHERE ID = ".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

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

		$dbRes = $DB->Query("SELECT DISTINCT USER_ID FROM b_im_relation R, b_im_message M WHERE M.CHAT_ID = R.CHAT_ID AND M.NOTIFY_TAG = '".$DB->ForSQL($notifyTag)."'".$sqlUser, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
			CIMMessenger::SpeedFileDelete($arRes['USER_ID'], IM_SPEED_NOTIFY);

		$strSql = "DELETE FROM b_im_message WHERE NOTIFY_TAG = '".$DB->ForSQL($notifyTag)."'".$sqlUser2;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

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

		$dbRes = $DB->Query("SELECT DISTINCT USER_ID FROM b_im_relation R, b_im_message M WHERE M.CHAT_ID = R.CHAT_ID AND M.NOTIFY_SUB_TAG = '".$DB->ForSQL($notifySubTag)."'".$sqlUser, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
			CIMMessenger::SpeedFileDelete($arRes['USER_ID'], IM_SPEED_NOTIFY);

		$strSql = "DELETE FROM b_im_message WHERE NOTIFY_SUB_TAG = '".$DB->ForSQL($notifySubTag)."'".$sqlUser2;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	static public function GetNotifyCounter($arNotify = Array())
	{
		$count = 0;
		if (isset($arNotify['unreadNotify']) && !empty($arNotify['unreadNotify']) && isset($arNotify['notify']))
		{
			$arGroupNotify = Array();
			foreach ($arNotify['unreadNotify'] as $key => $value)
			{
				if (!isset($arNotify['notify'][$key]))
					continue;

				$notify = $arNotify['notify'][$key];
				if ($notify['type'] == 2 && $notify['tag'] != '')
				{
					if (!isset($arGroupNotify[$notify['tag']]))
					{
						$arGroupNotify[$notify['tag']] = true;
						$count++;
					}
				}
				else
					$count++;
			}
		}
		else
		{
			$count = CIMMessenger::SpeedFileGet($this->user_id, IM_SPEED_NOTIFY);
		}
		return intval($count);
	}
}
?>