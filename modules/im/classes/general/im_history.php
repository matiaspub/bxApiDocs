<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Im as IM;

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
		if (isset($arParams['HIDE_LINK']) && $arParams['HIDE_LINK'] == 'Y')
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
		if (strlen($searchText) <= 3)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_HISTORY_SEARCH_EMPTY"), "ERROR_SEARCH_EMPTY");
			return false;
		}

		$chatId = 0;
		$arMessages = Array();
		$arMessageId = Array();
		$arUnreadMessage = Array();
		$arMessageFiles = Array();
		$arUsers = Array();

		$limitById = '';
		$arRelation = \CIMChat::GetPrivateRelation($fromUserId, $toUserId);
		if ($arRelation)
		{
			if ($arRelation['START_ID'] > 0)
			{
				$limitById = 'AND M.ID >= '.intval($arRelation['START_ID']);
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
					".$fromUserId." R1_USER_ID,
					".$toUserId." R2_USER_ID,
					M.NOTIFY_EVENT
				FROM b_im_message M
				WHERE
					M.CHAT_ID = '".$arRelation['CHAT_ID']."'
				AND M.MESSAGE like '%".$DB->ForSql($searchText)."%'
					".$limitById."
				ORDER BY DATE_CREATE DESC, ID DESC
			";
			if (!$bTimeZone)
				CTimeZone::Enable();
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);


			$CCTP = new CTextParser();
			$CCTP->MaxStringLen = 200;
			$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => $this->bHideLink? "N": "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
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
					'chatId' => $arRes['CHAT_ID'],
					'senderId' => $arRes['FROM_USER_ID'],
					'recipientId' => $arRes['TO_USER_ID'],
					'date' => $arRes['DATE_CREATE'],
					'system' => $arRes['NOTIFY_EVENT'] == 'private'? 'N': 'Y',
					'text' => $CCTP->convertText(htmlspecialcharsbx($arRes['MESSAGE']))
				);

				$arUsers[$convId][] = $arRes['ID'];
				$arMessageId[] = $arRes['ID'];
				$chatId = $arRes['CHAT_ID'];
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
			$arMessageFiles = CIMDisk::GetFiles($chatId, $arFiles);
		}

		return Array('chatId' => $chatId, 'message' => $arMessages, 'unreadMessage' => $arUnreadMessage, 'usersMessage' => $arUsers, 'files' => $arMessageFiles);
	}

	public function SearchDateMessage($searchDate, $toUserId, $fromUserId = false, $bTimeZone = true)
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

		$sqlHelper = Bitrix\Main\Application::getInstance()->getConnection()->getSqlHelper();

		try
		{
			$dateStart = \Bitrix\Main\Type\DateTime::createFromUserTime($searchDate);
			$sqlDateStart = $sqlHelper->getCharToDateFunction($dateStart->format("Y-m-d H:i:s"));

			$dateEnd = $dateStart->add('1 DAY');
			$sqlDateEnd = $sqlHelper->getCharToDateFunction($dateEnd->format("Y-m-d H:i:s"));
		}
		catch(\Bitrix\Main\ObjectException $e)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_HISTORY_SEARCH_DATE_EMPTY"), "ERROR_SEARCH_EMPTY");
			return false;
		}

		$chatId = 0;
		$arMessages = Array();
		$arMessageId = Array();
		$arUnreadMessage = Array();
		$arMessageFiles = Array();
		$arUsers = Array();

		$limitById = '';
		$arRelation = \CIMChat::GetPrivateRelation($fromUserId, $toUserId);
		if ($arRelation)
		{
			if ($arRelation['START_ID'] > 0)
			{
				$limitById = 'AND M.ID >= '.intval($arRelation['START_ID']);
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
					".$fromUserId." R1_USER_ID,
					".$toUserId." R2_USER_ID,
					M.NOTIFY_EVENT
				FROM b_im_message M
				WHERE
					M.CHAT_ID = ".$arRelation['CHAT_ID']."
				AND M.DATE_CREATE >= ".$sqlDateStart." AND M.DATE_CREATE <=  ".$sqlDateEnd."
					".$limitById."
				ORDER BY DATE_CREATE DESC, ID DESC
			";
			if (!$bTimeZone)
				CTimeZone::Enable();
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$CCTP = new CTextParser();
			$CCTP->MaxStringLen = 200;
			$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink ? "N" : "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => $this->bHideLink ? "N" : "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
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

				$arMessages[$arRes['ID']] = Array('id' => $arRes['ID'], 'chatId' => $arRes['CHAT_ID'], 'senderId' => $arRes['FROM_USER_ID'], 'recipientId' => $arRes['TO_USER_ID'], 'date' => $arRes['DATE_CREATE'], 'system' => $arRes['NOTIFY_EVENT'] == 'private' ? 'N' : 'Y', 'text' => $CCTP->convertText(htmlspecialcharsbx($arRes['MESSAGE'])));

				$arUsers[$convId][] = $arRes['ID'];
				$arMessageId[] = $arRes['ID'];
				$chatId = $arRes['CHAT_ID'];
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
			$arMessageFiles = CIMDisk::GetFiles($chatId, $arFiles);
		}

		return Array('chatId' => $chatId, 'message' => $arMessages, 'unreadMessage' => $arUnreadMessage, 'usersMessage' => $arUsers, 'files' => $arMessageFiles);
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

		$chatId = 0;
		$arMessages = Array();
		$arMessageId = Array();
		$arUnreadMessage = Array();
		$arMessageFiles = Array();
		$arUsers = Array();

		$limitById = '';
		$arRelation = \CIMChat::GetPrivateRelation($fromUserId, $toUserId);
		if ($arRelation)
		{
			if ($arRelation['START_ID'] > 0)
			{
				$limitById = 'AND M.ID >= '.intval($arRelation['START_ID']);
			}
			$sqlStr = "
				SELECT COUNT(M.ID) as CNT
				FROM b_im_message M
				WHERE M.CHAT_ID = ".$arRelation['CHAT_ID']."
				".$limitById."
			";
			$res_cnt = $DB->Query($sqlStr);
			$res_cnt = $res_cnt->Fetch();
			$cnt = $res_cnt["CNT"];

			if ($cnt > 0 && ceil($cnt/20) >= $iNumPage)
			{
				if (!$bTimeZone)
					CTimeZone::Disable();
				$strSql ="
					SELECT
						M.ID,
						M.CHAT_ID,
						M.MESSAGE,
						".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
						M.AUTHOR_ID,
						M.NOTIFY_EVENT,
						".$fromUserId." R1_USER_ID,
						".$toUserId." R2_USER_ID
					FROM b_im_message M
					WHERE
						M.CHAT_ID = ".$arRelation['CHAT_ID']."
						".$limitById."
					ORDER BY M.DATE_CREATE DESC, M.ID DESC
				";
				if (!$bTimeZone)
					CTimeZone::Enable();
				$dbRes = new CDBResult();
				$dbRes->NavQuery($strSql, $cnt, Array('iNumPage' => $iNumPage, 'nPageSize' => 20));

				$CCTP = new CTextParser();
				$CCTP->MaxStringLen = 200;
				$CCTP->allow = array("HTML" => "N", "ANCHOR" => $this->bHideLink? "N": "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => $this->bHideLink? "N": "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
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
						'chatId' => $arRes['CHAT_ID'],
						'senderId' => $arRes['FROM_USER_ID'],
						'recipientId' => $arRes['TO_USER_ID'],
						'date' => $arRes['DATE_CREATE'],
						'system' => $arRes['NOTIFY_EVENT'] == 'private'? 'N': 'Y',
						'text' => $CCTP->convertText(htmlspecialcharsbx($arRes['MESSAGE']))
					);
					$arUsers[$convId][] = $arRes['ID'];
					$arMessageId[] = $arRes['ID'];
					$chatId = $arRes['CHAT_ID'];
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
				$arMessageFiles = CIMDisk::GetFiles($chatId, $arFiles);
			}
		}


		return Array('chatId' => $chatId, 'message' => $arMessages, 'usersMessage' => $arUsers, 'files' => $arMessageFiles);
	}

	public static function RemoveMessage($messageId)
	{
		global $DB;

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
			GROUP BY M.CHAT_ID, R1.ID, R1.START_ID, R2.ID, R2.START_ID
		";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$strSql = "UPDATE b_im_relation SET START_ID = ".intval($arRes['MAX_ID']).", LAST_ID = ".(intval($arRes['MAX_ID'])-1)." WHERE ID = ".intval($arRes['R1_ID']);
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if ($arRes['MAX_ID'] >= $arRes['R2_START_ID'] && $arRes['R2_START_ID'] > 0)
			{
				$messages = IM\MessageTable::getList(array(
					'select' => array('ID'),
					'filter' => array(
						'<ID' => $arRes['R2_START_ID'],
						'=CHAT_ID' => $arRes['CHAT_ID'],
					),
				));
				while ($messageInfo = $messages->fetch())
				{
					IM\MessageParamTable::delete($messageInfo['ID']);
				}
			}
			$obCache = new CPHPCache();
			$obCache->CleanDir('/bx/imc/recent'.CIMMessenger::GetCachePath($this->user_id));
		}

		return true;
	}

	/* CHAT */
	public function HideAllChatMessage($chatId)
	{
		global $DB;
		$chatId = intval($chatId);

		$limitById = '';
		$ar = \CIMChat::GetRelationById($chatId, $this->user_id);
		if ($ar && $ar['START_ID'] > 0)
		{
			$limitById = 'AND M.ID >= '.intval($ar['START_ID']);
		}

		$strSql ="
			SELECT
				MAX(M.ID)+1 MAX_ID,
				R1.ID R1_ID
			FROM b_im_relation R1
			INNER JOIN b_im_message M ON M.CHAT_ID = R1.CHAT_ID
			WHERE
				R1.USER_ID = ".$this->user_id."
			AND R1.MESSAGE_TYPE <> '".IM_MESSAGE_PRIVATE."'
			AND R1.CHAT_ID = ".$chatId."
				".$limitById."
			GROUP BY M.CHAT_ID, R1.ID
		";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$strSql = "UPDATE b_im_relation SET START_ID = ".intval($arRes['MAX_ID']).", LAST_ID = ".(intval($arRes['MAX_ID'])-1)." WHERE ID = ".intval($arRes['R1_ID']);
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$obCache = new CPHPCache();
			$obCache->CleanDir('/bx/imc/recent'.CIMMessenger::GetCachePath($this->user_id));
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

		$limitById = '';
		$ar = \CIMChat::GetRelationById($chatId, $this->user_id);
		if ($ar && $ar['START_ID'] > 0)
		{
			$limitById = 'AND M.ID >= '.intval($ar['START_ID']);
		}

		if (!$bTimeZone)
			CTimeZone::Disable();
		$strSql ="
			SELECT
				M.ID,
				M.CHAT_ID,
				M.MESSAGE,
				".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
				M.AUTHOR_ID
			FROM b_im_relation R1
			INNER JOIN b_im_message M ON M.CHAT_ID = R1.CHAT_ID
			WHERE
				R1.USER_ID = ".$this->user_id."
			AND R1.CHAT_ID = ".$chatId."
			AND R1.MESSAGE_TYPE <> '".IM_MESSAGE_PRIVATE."'
			AND M.MESSAGE like '%".$DB->ForSql($searchText)."%'
				".$limitById."
			ORDER BY M.DATE_CREATE DESC, M.ID DESC
		";
		if (!$bTimeZone)
			CTimeZone::Enable();
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arMessages = Array();
		$arMessageId = Array();
		$arUnreadMessage = Array();
		$usersMessage = Array();

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

			$usersMessage[$arRes['CHAT_ID']][] = $arRes['ID'];
			$arMessageId[] = $arRes['ID'];
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
		$arMessageFiles = CIMDisk::GetFiles($chatId, $arFiles);

		return Array('chatId' => $chatId, 'message' => $arMessages, 'unreadMessage' => $arUnreadMessage, 'usersMessage' => $usersMessage, 'files' => $arMessageFiles);
	}

	public function SearchDateChatMessage($searchDate, $chatId, $bTimeZone = true)
	{
		global $DB;

		$chatId = IntVal($chatId);

		$sqlHelper = Bitrix\Main\Application::getInstance()->getConnection()->getSqlHelper();
		try
		{
			$dateStart = \Bitrix\Main\Type\DateTime::createFromUserTime($searchDate);
			$sqlDateStart = $sqlHelper->getCharToDateFunction($dateStart->format("Y-m-d H:i:s"));

			$dateEnd = $dateStart->add('1 DAY');
			$sqlDateEnd = $sqlHelper->getCharToDateFunction($dateEnd->format("Y-m-d H:i:s"));
		}
		catch(\Bitrix\Main\ObjectException $e)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_HISTORY_SEARCH_DATE_EMPTY"), "ERROR_SEARCH_EMPTY");
			return false;
		}

		$limitById = '';
		$ar = \CIMChat::GetRelationById($chatId, $this->user_id);
		if ($ar && $ar['START_ID'] > 0)
		{
			$limitById = 'AND M.ID >= '.intval($ar['START_ID']);
		}

		if (!$bTimeZone)
			CTimeZone::Disable();
		$strSql ="
			SELECT
				M.ID,
				M.CHAT_ID,
				M.MESSAGE,
				".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
				M.AUTHOR_ID
			FROM b_im_relation R1
			INNER JOIN b_im_message M ON M.CHAT_ID = R1.CHAT_ID
			WHERE
				R1.USER_ID = ".$this->user_id."
			AND R1.CHAT_ID = ".$chatId."
			AND R1.MESSAGE_TYPE <> '".IM_MESSAGE_PRIVATE."'
			AND M.DATE_CREATE >= ".$sqlDateStart." AND M.DATE_CREATE <=  ".$sqlDateEnd."
				".$limitById."
			ORDER BY M.DATE_CREATE DESC, M.ID DESC
		";
		if (!$bTimeZone)
			CTimeZone::Enable();
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arMessages = Array();
		$arMessageId = Array();
		$arUnreadMessage = Array();
		$usersMessage = Array();

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

			$usersMessage[$arRes['CHAT_ID']][] = $arRes['ID'];
			$arMessageId[] = $arRes['ID'];
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
		$arMessageFiles = CIMDisk::GetFiles($chatId, $arFiles);

		return Array('chatId' => $chatId, 'message' => $arMessages, 'unreadMessage' => $arUnreadMessage, 'usersMessage' => $usersMessage, 'files' => $arMessageFiles);
	}

	public function GetMoreChatMessage($pageId, $chatId, $bTimeZone = true)
	{
		global $DB;

		$iNumPage = 1;
		if (intval($pageId) > 0)
			$iNumPage = intval($pageId);

		$chatId = IntVal($chatId);

		$limitById = '';
		$ar = \CIMChat::GetRelationById($chatId, $this->user_id);
		if ($ar && $ar['START_ID'] > 0)
		{
			$limitById = 'AND M.ID >= '.intval($ar['START_ID']);
		}

		$strSql ="
			SELECT COUNT(M.ID) as CNT
			FROM b_im_message M
			INNER JOIN b_im_relation R1 ON M.CHAT_ID = R1.CHAT_ID
			WHERE R1.CHAT_ID = ".$chatId." AND R1.USER_ID = ".$this->user_id." ".$limitById."
		";
		$res_cnt = $DB->Query($strSql);
		$res_cnt = $res_cnt->Fetch();
		$cnt = $res_cnt["CNT"];

		$arMessages = Array();
		$arMessageFiles = Array();
		$arMessageId = Array();
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
					".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
					M.AUTHOR_ID
				FROM b_im_message M
				INNER JOIN b_im_relation R1 ON M.CHAT_ID = R1.CHAT_ID
				WHERE R1.CHAT_ID = ".$chatId." AND R1.USER_ID = ".$this->user_id." ".$limitById."
				ORDER BY M.DATE_CREATE DESC, M.ID DESC
			";
			if (!$bTimeZone)
				CTimeZone::Enable();
			$dbRes = new CDBResult();
			$dbRes->NavQuery($strSql, $cnt, Array('iNumPage' => $iNumPage, 'nPageSize' => 20));

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
					'system' => $arRes['AUTHOR_ID'] > 0? 'N': 'Y',
					'text' => $CCTP->convertText(htmlspecialcharsbx($arRes['MESSAGE']))
				);

				$usersMessage[$arRes['CHAT_ID']][] = $arRes['ID'];
				$arMessageId[] = $arRes['ID'];
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
			$arMessageFiles = CIMDisk::GetFiles($chatId, $arFiles);
		}

		return Array('chatId' => $chatId, 'message' => $arMessages, 'usersMessage' => $usersMessage, 'files' => $arMessageFiles);
	}
}
?>