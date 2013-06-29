<?
IncludeModuleLangFile(__FILE__);

class CIMHistory
{
	private $user_id = 0;
	private $bHideLink = false;

	public function __construct($user_id = false, $arParams = Array())
	{
		global $USER;
		$this->user_id = intval($user_id);
		if ($user_id == 0)
			$this->user_id = intval($USER->GetID());
		if (isset($arParams['hide_link']) && $arParams['hide_link'] == true)
			$this->bHideLink = true;
	}

	public function SearchMessage($searchText, $toUserId, $fromUserId = false, $bTimeZone = true)
	{
		global $DB;

		$fromUserId = IntVal($fromUserId);
		if ($fromUserId <= 0)
			$fromUserId = $this->user_id;

		$toUserId = IntVal($toUserId);
		if ($toUserId <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_HISTORY_ERROR_TO_USER_ID"), "ERROR_TO_USER_ID");
			return false;
		}

		$searchText = trim($searchText);
		if (strlen($searchText) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_HISTORY_SEARCH_EMPTY"), "ERROR_SEARCH_EMPTY");
			return false;
		}
		if (!$bTimeZone)
			CTimeZone::Disable();
		$strSql ="
			SELECT
				M.ID,
				M.CHAT_ID,
				M.MESSAGE,
				".$DB->DateToCharFunction('M.DATE_CREATE')." DATE_CREATE,
				M.AUTHOR_ID,
				R1.USER_ID R1_USER_ID,
				R2.USER_ID R2_USER_ID
			FROM b_im_relation R1
			INNER JOIN b_im_relation R2 on R2.CHAT_ID = R1.CHAT_ID
			INNER JOIN b_im_message M ON M.ID >= R1.START_ID AND M.CHAT_ID = R1.CHAT_ID
			WHERE
				R1.USER_ID = ".$fromUserId."
			AND R2.USER_ID = ".$toUserId."
			AND R1.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'
			AND M.MESSAGE like '%".$DB->ForSql($searchText)."%'
			ORDER BY M.ID DESC
		";
		if (!$bTimeZone)
			CTimeZone::Enable();
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arMessages = Array();
		$arUnreadMessage = Array();
		$arUsers = Array();
		$CCTP = new CTextParser();
		$CCTP->MaxStringLen = 200;
		$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
		while ($arRes = $dbRes->Fetch())
		{
			if ($fromUserId == $arRes['AUTHOR_ID'])
			{
				$arRes['TO_USER_ID'] = $arRes['R2_USER_ID'];
				$arRes['FROM_USER_ID'] = $arRes['R1_USER_ID'];
				$convId = $arRes['TO_USER_ID'];
			}
			else
			{
				$arRes['TO_USER_ID'] = $arRes['R1_USER_ID'];
				$arRes['FROM_USER_ID'] = $arRes['R2_USER_ID'];
				$convId = $arRes['FROM_USER_ID'];
			}

			$arMessages[$arRes['ID']] = Array(
				'id' => $arRes['ID'],
				'senderId' => $arRes['FROM_USER_ID'],
				'recipientId' => $arRes['TO_USER_ID'],
				'date' => MakeTimeStamp($arRes['DATE_CREATE']),
				'text' => $CCTP->convertText(htmlspecialcharsbx($arRes['MESSAGE']))
			);

			$arUsers[$convId][] = $arRes['ID'];
		}
		return Array('message' => $arMessages, 'unreadMessage' => $arUnreadMessage, 'usersMessage' => $arUsers);
	}

	public function GetMoreMessage($pageId, $toUserId, $fromUserId = false, $bTimeZone = true)
	{
		global $DB;

		$iNumPage = 1;
		if (intval($pageId) > 0)
			$iNumPage = intval($pageId);

		$fromUserId = IntVal($fromUserId);
		if ($fromUserId <= 0)
			$fromUserId = $this->user_id;

		$toUserId = IntVal($toUserId);
		if ($toUserId <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_HISTORY_ERROR_TO_USER_ID"), "ERROR_TO_USER_ID");
			return false;
		}

		$sqlStr = "
			SELECT COUNT(M.ID) as CNT
			FROM b_im_relation R1
			INNER JOIN b_im_relation R2 on R2.CHAT_ID = R1.CHAT_ID
			INNER JOIN b_im_message M ON M.ID >= R1.START_ID AND M.CHAT_ID = R2.CHAT_ID
			WHERE R1.USER_ID = ".$fromUserId."
			AND R2.USER_ID = ".$toUserId."
			AND R1.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'";
		$res_cnt = $DB->Query($sqlStr);
		$res_cnt = $res_cnt->Fetch();
		$cnt = $res_cnt["CNT"];

		$arMessages = Array();
		$arUnreadMessage = Array();
		$arUsers = Array();
		if ($cnt > 0 && ceil($cnt/20) >= $iNumPage)
		{
			if (!$bTimeZone)
				CTimeZone::Disable();
			$strSql ="
				SELECT
					M.ID,
					M.CHAT_ID,
					M.MESSAGE,
					".$DB->DateToCharFunction('M.DATE_CREATE')." DATE_CREATE,
					M.AUTHOR_ID,
					R1.USER_ID R1_USER_ID,
					R2.USER_ID R2_USER_ID
				FROM b_im_relation R1
				INNER JOIN b_im_relation R2 on R2.CHAT_ID = R1.CHAT_ID
				INNER JOIN b_im_message M ON M.ID >= R1.START_ID AND M.CHAT_ID = R2.CHAT_ID
				WHERE
					R1.USER_ID = ".$fromUserId."
				AND R2.USER_ID = ".$toUserId."
				AND R1.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'
				ORDER BY M.DATE_CREATE DESC, ID DESC
			";
			if (!$bTimeZone)
				CTimeZone::Enable();
			$dbRes = new CDBResult();
			$dbRes->NavQuery($strSql, $cnt, Array('iNumPage' => $iNumPage, 'nPageSize' => 20));

			$CCTP = new CTextParser();
			$CCTP->MaxStringLen = 200;
			$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
			while ($arRes = $dbRes->Fetch())
			{
				if ($fromUserId == $arRes['AUTHOR_ID'])
				{
					$arRes['TO_USER_ID'] = $arRes['R2_USER_ID'];
					$arRes['FROM_USER_ID'] = $arRes['R1_USER_ID'];
					$convId = $arRes['TO_USER_ID'];
				}
				else
				{
					$arRes['TO_USER_ID'] = $arRes['R1_USER_ID'];
					$arRes['FROM_USER_ID'] = $arRes['R2_USER_ID'];
					$convId = $arRes['FROM_USER_ID'];
				}
				$arMessages[$arRes['ID']] = Array(
					'id' => $arRes['ID'],
					'senderId' => $arRes['FROM_USER_ID'],
					'recipientId' => $arRes['TO_USER_ID'],
					'date' => MakeTimeStamp($arRes['DATE_CREATE']),
					'text' => $CCTP->convertText(htmlspecialcharsbx($arRes['MESSAGE']))
				);
				$arUsers[$convId][] = $arRes['ID'];
			}
		}

		return Array('message' => $arMessages, 'usersMessage' => $arUsers);
	}

	public static function RemoveMessage($messageId)
	{
		global $DB;

		// function for future
		return false;
	}

	public function RemoveAllMessage($userId)
	{
		global $DB;

		$userId = intval($userId);

		$strSql ="
			SELECT
				MAX(M.ID)+1 MAX_ID,
				M.CHAT_ID,
				R1.ID R1_ID,
				R1.START_ID R1_START_ID,
				R2.ID R2_ID,
				R2.START_ID R2_START_ID
			FROM b_im_relation R1
			INNER JOIN b_im_relation R2 on R2.CHAT_ID = R1.CHAT_ID
			INNER JOIN b_im_message M ON M.ID >= R1.START_ID AND M.CHAT_ID = R1.CHAT_ID
			WHERE
				R1.USER_ID = ".$this->user_id."
			AND R2.USER_ID = ".$userId."
			AND R1.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'
			GROUP BY M.CHAT_ID
		";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$strSql = "UPDATE b_im_relation SET START_ID = ".intval($arRes['MAX_ID']).", LAST_ID = ".(intval($arRes['MAX_ID'])-1)." WHERE ID = ".intval($arRes['R1_ID']);
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if ($arRes['MAX_ID'] >= $arRes['R2_START_ID'] && $arRes['R2_START_ID'] > 0)
			{
				$strSql = "DELETE FROM b_im_message WHERE ID < ".intval($arRes['R2_START_ID'])." AND CHAT_ID = ".intval($arRes['CHAT_ID']);
				$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			$obCache = new CPHPCache();
			$obCache->CleanDir('/bx/im/rec'.CIMMessenger::GetCachePath($this->user_id));
		}

		return true;
	}

	/* CHAT */
	public function HideAllChatMessage($chatId)
	{
		global $DB;
		$chatId = intval($chatId);

		$strSql ="
			SELECT
				MAX(M.ID)+1 MAX_ID,
				R1.ID R1_ID
			FROM b_im_relation R1
			INNER JOIN b_im_message M ON M.ID >= R1.START_ID AND M.CHAT_ID = R1.CHAT_ID
			WHERE
				R1.USER_ID = ".$this->user_id."
			AND R1.MESSAGE_TYPE = '".IM_MESSAGE_GROUP."'
			AND R1.CHAT_ID = ".$chatId."
			GROUP BY M.CHAT_ID
		";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$strSql = "UPDATE b_im_relation SET START_ID = ".intval($arRes['MAX_ID']).", LAST_ID = ".(intval($arRes['MAX_ID'])-1)." WHERE ID = ".intval($arRes['R1_ID']);
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$obCache = new CPHPCache();
			$obCache->CleanDir('/bx/im/rec'.CIMMessenger::GetCachePath($this->user_id));
		}

		return true;
	}

	public function SearchChatMessage($searchText, $chatId, $bTimeZone = true)
	{
		global $DB;

		$chatId = IntVal($chatId);
		$searchText = trim($searchText);

		if (strlen($searchText) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_HISTORY_SEARCH_EMPTY"), "ERROR_SEARCH_EMPTY");
			return false;
		}

		if (!$bTimeZone)
			CTimeZone::Disable();
		$strSql ="
			SELECT
				M.ID,
				M.CHAT_ID,
				M.MESSAGE,
				".$DB->DateToCharFunction('M.DATE_CREATE')." DATE_CREATE,
				M.AUTHOR_ID
			FROM b_im_relation R1
			INNER JOIN b_im_message M ON M.ID >= R1.START_ID AND M.CHAT_ID = R1.CHAT_ID
			WHERE
				R1.USER_ID = ".$this->user_id."
			AND R1.CHAT_ID = ".$chatId."
			AND R1.MESSAGE_TYPE = '".IM_MESSAGE_GROUP."'
			AND M.MESSAGE like '%".$DB->ForSql($searchText)."%'
			ORDER BY M.ID DESC
		";
		if (!$bTimeZone)
			CTimeZone::Enable();
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arMessages = Array();
		$arUnreadMessage = Array();
		$usersMessage = Array();
		$CCTP = new CTextParser();
		$CCTP->MaxStringLen = 200;
		$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
		while ($arRes = $dbRes->Fetch())
		{
			$arMessages[$arRes['ID']] = Array(
				'id' => $arRes['ID'],
				'senderId' => $arRes['AUTHOR_ID'],
				'recipientId' => $arRes['CHAT_ID'],
				'date' => MakeTimeStamp($arRes['DATE_CREATE']),
				'text' => $CCTP->convertText(htmlspecialcharsbx($arRes['MESSAGE']))
			);

			$usersMessage[$arRes['CHAT_ID']][] = $arRes['ID'];
		}
		return Array('message' => $arMessages, 'unreadMessage' => $arUnreadMessage, 'usersMessage' => $usersMessage);
	}

	public function GetMoreChatMessage($pageId, $chatId, $bTimeZone = true)
	{
		global $DB;

		$iNumPage = 1;
		if (intval($pageId) > 0)
			$iNumPage = intval($pageId);

		$chatId = IntVal($chatId);

		$strSql ="
			SELECT COUNT(M.ID) as CNT
			FROM b_im_message M
			INNER JOIN b_im_relation R1 ON M.ID >= R1.START_ID AND M.CHAT_ID = R1.CHAT_ID
			WHERE R1.CHAT_ID = ".$chatId." AND R1.USER_ID = ".$this->user_id."
		";
		$res_cnt = $DB->Query($strSql);
		$res_cnt = $res_cnt->Fetch();
		$cnt = $res_cnt["CNT"];

		$arMessages = Array();
		$usersMessage = Array();
		if ($cnt > 0 && ceil($cnt/20) >= $iNumPage)
		{
			if (!$bTimeZone)
				CTimeZone::Disable();
			$strSql ="
				SELECT
					M.ID,
					M.CHAT_ID,
					M.MESSAGE,
					".$DB->DateToCharFunction('M.DATE_CREATE')." DATE_CREATE,
					M.AUTHOR_ID
				FROM b_im_message M
				INNER JOIN b_im_relation R1 ON M.ID >= R1.START_ID AND M.CHAT_ID = R1.CHAT_ID
				WHERE R1.CHAT_ID = ".$chatId." AND R1.USER_ID = ".$this->user_id."
				ORDER BY M.DATE_CREATE DESC, ID DESC
			";
			if (!$bTimeZone)
				CTimeZone::Enable();
			$dbRes = new CDBResult();
			$dbRes->NavQuery($strSql, $cnt, Array('iNumPage' => $iNumPage, 'nPageSize' => 20));

			$CCTP = new CTextParser();
			$CCTP->MaxStringLen = 200;
			$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
			while ($arRes = $dbRes->Fetch())
			{
				$arMessages[$arRes['ID']] = Array(
					'id' => $arRes['ID'],
					'senderId' => $arRes['AUTHOR_ID'],
					'recipientId' => $arRes['CHAT_ID'],
					'date' => MakeTimeStamp($arRes['DATE_CREATE']),
					'text' => $CCTP->convertText(htmlspecialcharsbx($arRes['MESSAGE']))
				);

				$usersMessage[$arRes['CHAT_ID']][] = $arRes['ID'];
			}
		}

		return Array('message' => $arMessages, 'usersMessage' => $usersMessage);
	}
}
?>