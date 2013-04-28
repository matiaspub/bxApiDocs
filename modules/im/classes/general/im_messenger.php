<?
IncludeModuleLangFile(__FILE__);

class CIMMessenger
{
	private $user_id = 0;

	function __construct($user_id = false)
	{
		global $USER;
		$this->user_id = intval($user_id);
		if ($this->user_id == 0)
			$this->user_id = IntVal($USER->GetID());
	}

	/*
		$arParams keys:
		---------------
		MESSAGE_TYPE: P - private chat, G - group chat, S - notification
		TO_USER_ID
		FROM_USER_ID
		MESSAGE
		AUTHOR_ID
		EMAIL_TEMPLATE
		NOTIFY_TYPE: 1 - confirm, 2 - notify single from, 4 - notify single
		NOTIFY_MODULE: module id sender (ex: xmpp, main, etc)
		NOTIFY_EVENT: module event id for search (ex, IM_GROUP_INVITE)
		NOTIFY_TITLE: notify title to send email
		NOTIFY_BUTTONS: array of buttons - available with NOTIFY_TYPE = 1
			Array(
				Array('TITLE' => 'OK', 'VALUE' => 'Y', 'TYPE' => 'accept', 'URL' => '/test.php?CONFIRM=Y'),
				Array('TITLE' => 'Cancel', 'VALUE' => 'N', 'TYPE' => 'cancel', 'URL' => '/test.php?CONFIRM=N'),
			)
		NOTIFY_TAG: field for group in JS notification and search in table
		NOTIFY_SUB_TAG: second TAG for search in table
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (isset($arFields['TITLE']) && !isset($arFields['NOTIFY_TITLE']))
			$arFields['NOTIFY_TITLE'] = $arFields['TITLE'];

		if (isset($arFields['NOTIFY_MESSAGE']) && !isset($arFields['MESSAGE']))
			$arFields['MESSAGE'] = $arFields['NOTIFY_MESSAGE'];

		if (isset($arFields['NOTIFY_MESSAGE_OUT']) && !isset($arFields['MESSAGE_OUT']))
			$arFields['MESSAGE_OUT'] = $arFields['NOTIFY_MESSAGE_OUT'];

		$bConvert = false;
		if (isset($arFields['CONVERT']) && $arFields['CONVERT'] == 'Y')
			$bConvert = true;

		if (!isset($arFields['MESSAGE_OUT']))
			$arFields['MESSAGE_OUT'] = "";

		if (!isset($arFields['MESSAGE_TYPE']))
			$arFields['MESSAGE_TYPE'] = "";

		if (!isset($arFields['NOTIFY_MODULE']))
			$arFields['NOTIFY_MODULE'] = 'im';

		if (!isset($arFields['NOTIFY_EVENT']))
			$arFields['NOTIFY_EVENT'] = 'default';

		if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_SYSTEM)
		{
			if (!isset($arFields['NOTIFY_TYPE']) && intval($arFields['FROM_USER_ID']) > 0)
				$arFields['NOTIFY_TYPE'] = IM_NOTIFY_FROM;
			else if (!isset($arFields['NOTIFY_TYPE']))
				$arFields['NOTIFY_TYPE'] = IM_NOTIFY_SYSTEM;
		}

		if (isset($arFields['NOTIFY_EMAIL_TEMPLATE']) && !isset($arFields['EMAIL_TEMPLATE']))
			$arFields['EMAIL_TEMPLATE'] = $arFields['NOTIFY_EMAIL_TEMPLATE'];

		if (isset($arFields['EMAIL_TEMPLATE']) && strlen(trim($arFields['EMAIL_TEMPLATE']))>0)
			$arParams['EMAIL_TEMPLATE'] = trim($arFields['EMAIL_TEMPLATE']);

		if (!isset($arFields['AUTHOR_ID']))
			$arFields['AUTHOR_ID'] = intval($arFields['FROM_USER_ID']);

		foreach(GetModuleEvents("im", "OnBeforeMessageNotifyAdd", true) as $arEvent)
		{
			$result = ExecuteModuleEventEx($arEvent, array(&$arFields));
			if($result===false || isset($result['result']) && $result['result'] === false)
			{
				if (isset($result['reason']))
				{
					$CBXSanitizer = new CBXSanitizer;
					$CBXSanitizer->AddTags(array(
						'a' => array('href','style', 'target'),
						'b' => array(), 'u' => array(),
						'i' => array(), 'br' => array(),
						'span' => array('style'),
					));
					$reason = $CBXSanitizer->SanitizeHtml($result['reason']);
				}
				else
				{
					$reason = $arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE? GetMessage("IM_ERROR_MESSAGE_CANCELED"): ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_GROUP? GetMessage("IM_ERROR_GROUP_CANCELED"): GetMessage("IM_ERROR_NOTIFY_CANCELED"));
				}

				$GLOBALS["APPLICATION"]->ThrowException($reason, "ERROR_FROM_OTHER_MODULE");

				return false;
			}
		}

		if (!self::CheckFields($arFields))
			return false;

		if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$arFields['FROM_USER_ID'] = intval($arFields['FROM_USER_ID']);
			$arFields['TO_USER_ID'] = intval($arFields['TO_USER_ID']);

			$chatId = 0;
			$strSql = "
				SELECT RF.CHAT_ID
				FROM
					b_im_relation RF
					INNER JOIN b_im_relation RT on RF.CHAT_ID = RT.CHAT_ID
				WHERE
					RF.USER_ID = ".$arFields['FROM_USER_ID']."
				and RT.USER_ID = ".$arFields['TO_USER_ID']."
				and RF.MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."'
			";
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				$chatId = intval($arRes['CHAT_ID']);
			else
			{
				$chatId = IntVal($DB->Add("b_im_chat", Array('AUTHOR_ID' => $arFields['AUTHOR_ID']), Array()));
				if ($chatId <= 0)
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_CREATE"), "CHAT_ID");
					return false;
				}

				$strSql = "INSERT INTO b_im_relation (CHAT_ID, MESSAGE_TYPE, USER_ID, STATUS) VALUES (".$chatId.",'".IM_MESSAGE_PRIVATE."',".$arFields['FROM_USER_ID'].", ".($bConvert? 2: 0).")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$strSql = "INSERT INTO b_im_relation (CHAT_ID, MESSAGE_TYPE, USER_ID, STATUS) VALUES (".$chatId.",'".IM_MESSAGE_PRIVATE."',".$arFields['TO_USER_ID'].", ".($bConvert? 2: 0).")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			if ($chatId > 0)
			{
				$arParams = Array();
				$arParams['CHAT_ID'] = $chatId;
				$arParams['AUTHOR_ID'] = intval($arFields['AUTHOR_ID']);
				$arParams['MESSAGE'] = trim($arFields['MESSAGE']);
				$arParams['MESSAGE_OUT'] = trim($arFields['MESSAGE_OUT']);
				$arParams['NOTIFY_MODULE'] = $arFields['NOTIFY_MODULE'];
				$arParams['NOTIFY_EVENT'] = 'private';

				if (isset($arFields['IMPORT_ID']))
					$arParams['IMPORT_ID'] = intval($arFields['IMPORT_ID']);

				if (isset($arFields['MESSAGE_DATE']))
					$arParams['DATE_CREATE'] = $arFields['MESSAGE_DATE'];
				else
					$arParams['~DATE_CREATE'] = $DB->CurrentTimeFunction();

				$messageID = IntVal($DB->Add("b_im_message", $arParams, Array('MESSAGE','MESSAGE_OUT')));

				CIMContactList::SetRecent($arFields['TO_USER_ID'], $messageID, false, $arFields['FROM_USER_ID']);
				CIMContactList::UpdateRecent($arFields['FROM_USER_ID'], $messageID, false, $arFields['TO_USER_ID']);

				if (!$bConvert)
				{
					if (CModule::IncludeModule("pull"))
					{
						$arParams['FROM_USER_ID'] = $arFields['FROM_USER_ID'];
						$arParams['TO_USER_ID'] = $arFields['TO_USER_ID'];

						$pushText = '';
						if (CPullOptions::GetPushStatus())
						{
							$arSelect = Array("ID", "LAST_NAME", "NAME", "LOGIN", "SECOND_NAME");
							$dbUsers = CUser::GetList(($sort_by = false), ($dummy=''), array('ID' => $arParams['FROM_USER_ID']), array('FIELDS' => $arSelect));
							if ($arUser = $dbUsers->GetNext(true, false))
							{
								$sName = CUser::FormatName(CSite::GetNameFormat(false), $arUser, true, false);
								$pushText = $sName.': '.$arParams['MESSAGE'];
							}
						}

						$arPullTo = Array(
							'module_id' => 'im',
							'command' => 'message',
							'params' => CIMMessage::GetFormatMessage(Array(
								'ID' => $messageID,
								'TO_USER_ID' => $arParams['TO_USER_ID'],
								'FROM_USER_ID' => $arParams['FROM_USER_ID'],
								'MESSAGE' => $arParams['MESSAGE'],
								'DATE_CREATE' => time(),
							)),
							'push_params' => 'IM_MESS_'.$arParams['FROM_USER_ID'],
							'push_tag' => 'IM_MESS_'.$arParams['FROM_USER_ID'],
							'push_text' => $pushText
						);
						$arPullFrom = $arPullTo;
						unset($arPullFrom['push_text']);

						CPullStack::AddByUser($arParams['TO_USER_ID'], $arPullTo);
						CPullStack::AddByUser($arParams['FROM_USER_ID'], $arPullFrom);
					}

					$strSql = "
						UPDATE b_im_relation SET STATUS = '".IM_STATUS_UNREAD."'
						WHERE USER_ID = ".$arFields['TO_USER_ID']." AND MESSAGE_TYPE = '".IM_MESSAGE_PRIVATE."' AND CHAT_ID = ".$chatId;
					$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					foreach(GetModuleEvents("im", "OnAfterMessagesAdd", true) as $arEvent)
						ExecuteModuleEventEx($arEvent, array(intval($messageID), $arFields));
				}

				return $messageID;
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_CREATE"), "CHAT_ID");
				return false;
			}
		}
		else if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_GROUP)
		{
			$chatId = 0;
			$systemMessage = false;
			if (isset($arFields['SYSTEM']) && $arFields['SYSTEM'] == 'Y' && intval($arFields['FROM_USER_ID']) <= 0)
			{
				$strSql = "
					SELECT C.ID CHAT_ID, C.TITLE CHAT_TITLE, C.AUTHOR_ID CHAT_AUTHOR_ID
					FROM b_im_chat C
					WHERE C.ID = ".intval($arFields['TO_CHAT_ID'])."
				";
				$systemMessage = true;
			}
			else
			{
				$strSql = "
					SELECT R.CHAT_ID, C.TITLE CHAT_TITLE, C.AUTHOR_ID CHAT_AUTHOR_ID
					FROM b_im_relation R LEFT JOIN b_im_chat C ON R.CHAT_ID = C.ID
					WHERE R.USER_ID = ".intval($arFields['FROM_USER_ID'])." AND R.MESSAGE_TYPE = '".IM_MESSAGE_GROUP."' AND R.CHAT_ID = ".intval($arFields['TO_CHAT_ID'])."
				";
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
			{
				$chatId = intval($arRes['CHAT_ID']);
				$chatTitle = htmlspecialcharsbx($arRes['CHAT_TITLE']);
				$chatAuthorId = intval($arRes['CHAT_AUTHOR_ID']);
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_GROUP_CANCELED"), "CANCELED");
				return false;
			}

			if ($chatId > 0)
			{
				$arParams = Array();
				$arParams['CHAT_ID'] = $chatId;
				$arParams['AUTHOR_ID'] = intval($arFields['AUTHOR_ID']);
				$arParams['MESSAGE'] = trim($arFields['MESSAGE']);
				$arParams['MESSAGE_OUT'] = trim($arFields['MESSAGE_OUT']);
				$arParams['NOTIFY_MODULE'] = 'im';
				$arParams['NOTIFY_EVENT'] = 'group';

				if (isset($arFields['MESSAGE_DATE']))
					$arParams['DATE_CREATE'] = $arFields['MESSAGE_DATE'];
				else
					$arParams['~DATE_CREATE'] = $DB->CurrentTimeFunction();

				$messageID = IntVal($DB->Add("b_im_message", $arParams, Array('MESSAGE','MESSAGE_OUT')));

				//if(!$systemMessage)
				CIMContactList::SetRecent($chatId, $messageID, true, $arFields['FROM_USER_ID']);
				CIMContactList::UpdateRecent($chatId, $messageID, true);

				if (CModule::IncludeModule("pull"))
				{
					$arParams['FROM_USER_ID'] = $arFields['FROM_USER_ID'];
					$arParams['TO_CHAT_ID'] = $arFields['TO_CHAT_ID'];

					$arPullTo = Array(
						'module_id' => 'im',
						'command' => 'messageChat',
						'params' => CIMMessage::GetFormatMessage(Array(
							'ID' => $messageID,
							'TO_CHAT_ID' => $arParams['TO_CHAT_ID'],
							'FROM_USER_ID' => $arParams['FROM_USER_ID'],
							'MESSAGE' => $arParams['MESSAGE'],
							'DATE_CREATE' => time(),
						)),
					);
					$arPullFrom = $arPullTo;
					unset($arPullFrom['push_text']);

					CPullStack::AddByUser($arParams['FROM_USER_ID'], $arPullFrom);

					$arRel = CIMChat::GetRelationById($chatId);
					foreach ($arRel as $rel)
					{
						if ($rel['USER_ID'] != $arParams['FROM_USER_ID'])
							CPullStack::AddByUser($rel['USER_ID'], $arPullTo);
					}
				}
				if (!$systemMessage)
				{
					$strSql = "
						UPDATE b_im_relation SET STATUS = '".IM_STATUS_UNREAD."'
						WHERE USER_ID <> ".$arFields['FROM_USER_ID']." AND MESSAGE_TYPE = '".IM_MESSAGE_GROUP."' AND CHAT_ID = ".$chatId;
					$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}
				foreach(GetModuleEvents("im", "OnAfterMessagesAdd", true) as $arEvent)
					ExecuteModuleEventEx($arEvent, array(intval($messageID), $arFields));

				return $messageID;
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_CREATE"), "CHAT_ID");
				return false;
			}

		}
		else if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_SYSTEM)
		{
			$chatId = 0;
			$strSql = "
				SELECT CHAT_ID
				FROM b_im_relation
				WHERE USER_ID = ".intval($arFields['TO_USER_ID'])." AND MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."'";
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				$chatId = intval($arRes['CHAT_ID']);
			else
			{
				$chatId = IntVal($DB->Add("b_im_chat", Array('AUTHOR_ID' => intval($arFields['TO_USER_ID'])), Array()));
				if ($chatId <= 0)
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_CREATE"), "CHAT_ID");
					return false;
				}

				$strSql = "INSERT INTO b_im_relation (CHAT_ID, MESSAGE_TYPE, USER_ID, STATUS) VALUES (".$chatId.",'".IM_MESSAGE_SYSTEM."',".intval($arFields['TO_USER_ID']).", ".($bConvert? 2: 0).")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}

			if ($chatId > 0)
			{
				$arParams = Array();
				$arParams['CHAT_ID'] = $chatId;
				$arParams['AUTHOR_ID'] = intval($arFields['AUTHOR_ID']);
				$arParams['MESSAGE'] = trim($arFields['MESSAGE']);
				$arParams['MESSAGE_OUT'] = trim($arFields['MESSAGE_OUT']);
				$arParams['NOTIFY_TYPE'] = intval($arFields['NOTIFY_TYPE']);
				$arParams['NOTIFY_MODULE'] = $arFields['NOTIFY_MODULE'];
				$arParams['NOTIFY_EVENT'] = $arFields['NOTIFY_EVENT'];

				if (isset($arFields['IMPORT_ID']))
					$arParams['IMPORT_ID'] = intval($arFields['IMPORT_ID']);

				if (isset($arFields['MESSAGE_DATE']))
					$arParams['DATE_CREATE'] = $arFields['MESSAGE_DATE'];
				else
					$arParams['~DATE_CREATE'] = $DB->CurrentTimeFunction();

				if (isset($arFields['EMAIL_TEMPLATE']) && strlen(trim($arFields['EMAIL_TEMPLATE']))>0)
					$arParams['EMAIL_TEMPLATE'] = trim($arFields['EMAIL_TEMPLATE']);

				if (isset($arFields['NOTIFY_TAG']))
					$arParams['NOTIFY_TAG'] = $arFields['NOTIFY_TAG'];

				if (isset($arFields['NOTIFY_SUB_TAG']))
					$arParams['NOTIFY_SUB_TAG'] = $arFields['NOTIFY_SUB_TAG'];

				if (isset($arFields['NOTIFY_TITLE']) && strlen(trim($arFields['NOTIFY_TITLE']))>0)
					$arParams['NOTIFY_TITLE'] = trim($arFields['NOTIFY_TITLE']);

				if ($arParams['NOTIFY_TYPE'] == IM_NOTIFY_CONFIRM)
				{
					if (isset($arFields['NOTIFY_BUTTONS']))
					{
						foreach ($arFields['NOTIFY_BUTTONS'] as $key => $arButtons)
						{
							if (is_array($arButtons))
							{
								if (isset($arButtons['TITLE']) && strlen($arButtons['TITLE']) > 0
								&& isset($arButtons['VALUE']) && strlen($arButtons['VALUE']) > 0
								&& isset($arButtons['TYPE']) && strlen($arButtons['TYPE']) > 0)
								{
									$arButtons['TITLE'] = htmlspecialcharsbx($arButtons['TITLE']);
									$arButtons['VALUE'] = htmlspecialcharsbx($arButtons['VALUE']);
									$arButtons['TYPE'] = htmlspecialcharsbx($arButtons['TYPE']);
									$arFields['NOTIFY_BUTTONS'][$key] = $arButtons;
								}
								else
									unset($arFields['NOTIFY_BUTTONS'][$key]);
							}
							else
								unset($arFields['NOTIFY_BUTTONS'][$key]);
						}
					}
					else
					{
						$arFields['NOTIFY_BUTTONS'] = Array(
							Array('TITLE' => GetMessage('IM_ERROR_BUTTON_ACCEPT'), 'VALUE' => 'Y', 'TYPE' => 'accept'),
							Array('TITLE' => GetMessage('IM_ERROR_BUTTON_CANCEL'), 'VALUE' => 'N', 'TYPE' => 'cancel'),
						);
					}
					$arParams['NOTIFY_BUTTONS'] = serialize($arFields["NOTIFY_BUTTONS"]);

					if (isset($arParams['NOTIFY_TAG']) && strlen($arParams['NOTIFY_TAG'])>0)
						CIMNotify::DeleteByTag($arParams['NOTIFY_TAG']);
				}
				$messageID = IntVal($DB->Add("b_im_message", $arParams, Array('MESSAGE','MESSAGE_OUT', 'NOTIFY_BUTTONS')));
				CIMMessenger::SpeedFileDelete(intval($arFields['TO_USER_ID']), IM_SPEED_NOTIFY);

				if (!$bConvert)
				{
					if (CModule::IncludeModule("pull"))
					{
						CPullStack::AddByUser(intval($arFields['TO_USER_ID']), Array(
							'module_id' => 'im',
							'command' => 'notify',
							'params' => CIMNotify::GetFormatNotify(Array(
								'ID' => $messageID,
								'TIMESTAMP' => time(),
								'FROM_USER_ID' => intval($arFields['FROM_USER_ID']),
								'MESSAGE' => $arParams['MESSAGE'],
								'NOTIFY_TAG' => $arParams['NOTIFY_TAG'],
								'NOTIFY_TYPE' => $arParams['NOTIFY_TYPE'],
								'NOTIFY_BUTTONS' => isset($arParams['NOTIFY_BUTTONS'])? $arParams['NOTIFY_BUTTONS']: serialize(Array()),
								'NOTIFY_TITLE' => isset($arParams['NOTIFY_TITLE'])? $arParams['NOTIFY_TITLE']: '',
							)),
						));
					}

					$strSql = "
						UPDATE b_im_relation SET STATUS = '".IM_STATUS_UNREAD."'
						WHERE USER_ID = ".intval($arFields['TO_USER_ID'])." AND MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."' AND CHAT_ID = ".$chatId;
					$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					foreach(GetModuleEvents("im", "OnAfterNotifyAdd", true) as $arEvent)
						ExecuteModuleEventEx($arEvent, array(intval($messageID), $arFields));
				}

				return $messageID;
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_CREATE"), "CHAT_ID");
				return false;
			}
		}
		else
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_TYPE"), "MESSAGE_TYPE");
			return false;
		}

		return false;
	}

	private static function CheckFields($arFields)
	{
		$aMsg = array();
		if(!is_set($arFields, "MESSAGE_TYPE") || !in_array($arFields["MESSAGE_TYPE"], Array(IM_MESSAGE_PRIVATE, IM_MESSAGE_GROUP, IM_MESSAGE_SYSTEM)))
		{
			$aMsg[] = array("id"=>"MESSAGE_TYPE", "text"=> GetMessage("IM_ERROR_MESSAGE_TYPE"));
		}
		else
		{
			if($arFields["MESSAGE_TYPE"] == IM_MESSAGE_GROUP && !isset($arFields['SYSTEM']) && (intval($arFields["TO_CHAT_ID"]) <= 0 && intval($arFields["FROM_USER_ID"]) <= 0))
				$aMsg[] = array("id"=>"TO_CHAT_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_TO_FROM"));

			if($arFields["MESSAGE_TYPE"] == IM_MESSAGE_PRIVATE && !(intval($arFields["TO_USER_ID"]) > 0 && intval($arFields["FROM_USER_ID"]) > 0))
				$aMsg[] = array("id"=>"FROM_USER_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_TO_FROM"));

			if (is_set($arFields, "MESSAGE_DATE") && (!$GLOBALS['DB']->IsDate($arFields["MESSAGE_DATE"], false, LANG, "FULL")))
				$aMsg[] = array("id"=>"MESSAGE_DATE", "text"=> GetMessage("IM_ERROR_MESSAGE_DATE"));

			if(in_array($arFields["MESSAGE_TYPE"], Array(IM_MESSAGE_PRIVATE, IM_MESSAGE_SYSTEM)) && intval($arFields["TO_USER_ID"]) <= 0)
				$aMsg[] = array("id"=>"TO_USER_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_TO"));

			if(!is_set($arFields, "MESSAGE") || strlen(trim($arFields["MESSAGE"])) <= 0)
				$aMsg[] = array("id"=>"MESSAGE", "text"=> GetMessage("IM_ERROR_MESSAGE_TEXT"));

			if($arFields["MESSAGE_TYPE"] == IM_MESSAGE_PRIVATE && is_set($arFields, "AUTHOR_ID") && intval($arFields["AUTHOR_ID"]) <= 0)
				$aMsg[] = array("id"=>"AUTHOR_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_AUTHOR"));

			if(is_set($arFields, "IMPORT_ID") && intval($arFields["IMPORT_ID"]) <= 0)
				$aMsg[] = array("id"=>"IMPORT_ID", "text"=> GetMessage("IM_ERROR_IMPORT_ID"));

			if ($arFields["MESSAGE_TYPE"] == IM_MESSAGE_SYSTEM)
			{
				if(is_set($arFields, "NOTIFY_MODULE") && strlen(trim($arFields["NOTIFY_MODULE"])) <= 0)
					$aMsg[] = array("id"=>"NOTIFY_MODULE", "text"=> GetMessage("IM_ERROR_NOTIFY_MODULE"));

				if(is_set($arFields, "NOTIFY_EVENT") && strlen(trim($arFields["NOTIFY_EVENT"])) <= 0)
					$aMsg[] = array("id"=>"NOTIFY_EVENT", "text"=> GetMessage("IM_ERROR_NOTIFY_EVENT"));

				if(is_set($arFields, "NOTIFY_TYPE") && !in_array($arFields["NOTIFY_TYPE"], Array(IM_NOTIFY_CONFIRM, IM_NOTIFY_SYSTEM, IM_NOTIFY_FROM)))
					$aMsg[] = array("id"=>"NOTIFY_TYPE", "text"=> GetMessage("IM_ERROR_NOTIFY_TYPE"));

				if(is_set($arFields, "NOTIFY_TYPE") && $arFields["NOTIFY_TYPE"] == IM_NOTIFY_CONFIRM)
				{
					if(is_set($arFields, "NOTIFY_BUTTONS") && !is_array($arFields["NOTIFY_BUTTONS"]))
						$aMsg[] = array("id"=>"NOTIFY_BUTTONS", "text"=> GetMessage("IM_ERROR_NOTIFY_BUTTON"));
				}
				else if(is_set($arFields, "NOTIFY_TYPE") && $arFields["NOTIFY_TYPE"] == IM_NOTIFY_FROM)
				{
					if(!is_set($arFields, "FROM_USER_ID") || intval($arFields["FROM_USER_ID"]) <= 0)
						$aMsg[] = array("id"=>"FROM_USER_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_FROM"));
				}
			}
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}

	public static function GetById($ID)
	{
		global $DB;

		$ID = intval($ID);

		$strSql = "SELECT DISTINCT M.*, R.MESSAGE_TYPE FROM b_im_message M LEFT JOIN b_im_relation R ON M.CHAT_ID = R.CHAT_ID WHERE M.ID = ".$ID;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
			return $arRes;

		return false;
	}

	public static function GetRelationById($ID)
	{
		global $DB;

		$ID = intval($ID);
		$arResult = Array();

		$strSql = "SELECT R.*, (case when M.AUTHOR_ID = R.USER_ID then 'Y' else 'N' end) as IS_AUTHOR
			FROM b_im_message M LEFT JOIN b_im_relation R ON M.CHAT_ID = R.CHAT_ID WHERE M.ID = ".$ID;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
			$arResult[] = $arRes;

		return $arResult;
	}

	public static function CheckXmppStatusOnline()
	{
		If (IsModuleInstalled('xmpp'))
		{
			$LastActivityDate = CUserOptions::GetOption('xmpp', 'LastActivityDate');
			if (intval($LastActivityDate)+60 > time())
				return true;
		}
		return false;
	}

	public static function CheckDesktopStatusOnline()
	{
		$LastActivityDate = CUserOptions::GetOption('im', 'DesktopLastActivityDate');
		if (intval($LastActivityDate)+240 > time())
			return true;
		else
			return false;
	}

	public static function SetDesktopStatusOnline($userId = null)
	{
		global $USER;

		if (is_null($userId))
			$userId = $USER->GetId();

		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		$time = time();
		CUserOptions::SetOption('im', 'DesktopLastActivityDate', $time, false, $userId);

		return $time;
	}

	public static function RemoveDesktopStatusOnline($userId = null)
	{
		global $USER;

		if (is_null($userId))
			$userId = $USER->GetId();

		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		CUserOptions::SetOption('im', 'DesktopLastActivityDate', 0, false, $userId);

		return true;
	}

	public static function GetSettings()
	{
		return CUserOptions::GetOption('IM', 'settings');
	}

	public static function GetOpenTabs()
	{
		$arOpenTab = CUserOptions::GetOption('IM', 'openTab', Array());
		return is_array($arOpenTab)? $arOpenTab: Array();
	}

	public static function GetCurrentTab()
	{
		return intval(CUserOptions::GetOption('IM', 'currentTab'));
	}

	public static function SetCurrentTab($userId)
	{
		$userId = intval($userId);
		if (intval($userId)<=0)
			$userId = null;

		if ($userId == self::GetCurrentTab())
			return false;

		CUserOptions::SetOption('IM', 'currentTab', $userId);

		return true;
	}

	public static function GetMessageCounter($userId, $arMessages = Array())
	{
		$count = 0;

		if (isset($arMessages['unreadMessage']))
		{
			foreach ($arMessages['unreadMessage'] as $key => $value)
				$count += count($value);
		}
		else
		{
			$count = CIMMessenger::SpeedFileGet($userId, IM_SPEED_MESSAGE);
		}

		return intval($count);
	}

	public static function SpeedFileCreate($userID, $value, $type = IM_SPEED_MESSAGE)
	{
		global $CACHE_MANAGER;

		$userID = IntVal($userID);
		if ($userID <= 0 || !in_array($type, Array(IM_SPEED_NOTIFY, IM_SPEED_MESSAGE, IM_SPEED_GROUP)))
			return;

		$CACHE_MANAGER->Clean("im_csf_".$type.'_'.$userID);
		$CACHE_MANAGER->Read(86400*30, "im_csf_".$type.'_'.$userID);
		$CACHE_MANAGER->Set("im_csf_".$type.'_'.$userID, $value);
	}

	public static function SpeedFileDelete($userID, $type = IM_SPEED_MESSAGE)
	{
		global $CACHE_MANAGER;

		$userID = IntVal($userID);
		if ($userID <= 0 || !in_array($type, Array(IM_SPEED_NOTIFY, IM_SPEED_MESSAGE, IM_SPEED_GROUP)))
			return;

		$CACHE_MANAGER->Clean("im_csf_".$type.'_'.$userID);
	}

	public static function SpeedFileExists($userID, $type = IM_SPEED_MESSAGE)
	{
		global $CACHE_MANAGER;

		$userID = IntVal($userID);
		if ($userID <= 0 || !in_array($type, Array(IM_SPEED_NOTIFY, IM_SPEED_MESSAGE, IM_SPEED_GROUP)))
			return false;

		$result = $CACHE_MANAGER->Read(86400*30, "im_csf_".$type.'_'.$userID);
		if ($result)
			$result = $CACHE_MANAGER->Get("im_csf_".$type.'_'.$userID) === false? false: true;

		return $result;
	}

	public static function SpeedFileGet($userID, $type = IM_SPEED_MESSAGE)
	{
		global $CACHE_MANAGER;

		$userID = IntVal($userID);
		if ($userID <= 0 || !in_array($type, Array(IM_SPEED_NOTIFY, IM_SPEED_MESSAGE, IM_SPEED_GROUP)))
			return false;

		$CACHE_MANAGER->Read(86400*30, "im_csf_".$type.'_'.$userID);
		return intval($CACHE_MANAGER->Get("im_csf_".$type.'_'.$userID));
	}

	public static function GetTemplateJS($arParams, $arTemplate)
	{
		global $USER;

		$ppStatus = 'false';
		$ppServerStatus = 'false';
		if (CModule::IncludeModule("pull"))
		{
			$ppStatus = CPullOptions::ModuleEnable()? 'true': 'false';
			$ppServerStatus = CPullOptions::GetNginxStatus()? 'true': 'false';
		}

		if ($arTemplate['INIT'] == 'Y')
		{
			$sJS = "
				BX.ready(function() {
					BXIM = new BX.IM(BX('bx-notifier-panel'), {
						'panelPosition': {'horizontal' : '".$arTemplate['PANEL_POSTION_HORIZONTAL']."', 'vertical' : '".$arTemplate['PANEL_POSTION_VERTICAL']."'},
						'userStatus': '".$arTemplate['STATUS']."',
						'mailCount': ".$arTemplate["MAIL_COUNTER"].",
						'notifyCount': ".$arTemplate["NOTIFY_COUNTER"].",
						'messageCount': ".$arTemplate["MESSAGE_COUNTER"].",
						'counters': ".(empty($arTemplate['COUNTERS'])? '{}': CUtil::PhpToJSObject($arTemplate['COUNTERS'])).",
						'ppStatus': ".$ppStatus.",
						'ppServerStatus': ".$ppServerStatus.",
						'xmppStatus': ".(CIMMessenger::CheckXmppStatusOnline()? 'true': 'false').",
						'bitrix24Status': ".(IsModuleInstalled('bitrix24')? 'true': 'false').",
						'bitrixIntranet': ".(IsModuleInstalled('intranet')? 'true': 'false').",
						'desktop': ".$arTemplate["DESKTOP"].",
						'desktopStatus': ".(CIMMessenger::CheckDesktopStatusOnline()? 'true': 'false').",
						'desktopLinkOpen': ".$arTemplate["DESKTOP_LINK_OPEN"].",

						'notify': ".(empty($arTemplate['NOTIFY']['notify'])? '{}': CUtil::PhpToJSObject($arTemplate['NOTIFY']['notify'])).",
						'unreadNotify' : ".(empty($arTemplate['NOTIFY']['unreadNotify'])? '{}': CUtil::PhpToJSObject($arTemplate['NOTIFY']['unreadNotify'])).",
						'flashNotify' : ".(empty($arTemplate['NOTIFY']['flashNotify'])? '{}': CUtil::PhpToJSObject($arTemplate['NOTIFY']['flashNotify'])).",
						'countNotify' : ".intval($arTemplate['NOTIFY']['countNotify']).",
						'loadNotify' : ".($arTemplate['NOTIFY']['loadNotify']? 'true': 'false').",

						'recent': ".CUtil::PhpToJSObject($arTemplate['RECENT']).",
						'users': ".(empty($arTemplate['CONTACT_LIST']['users'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['users'])).",
						'groups': ".(empty($arTemplate['CONTACT_LIST']['groups'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['groups'])).",
						'userInGroup': ".(empty($arTemplate['CONTACT_LIST']['userInGroup'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['userInGroup'])).",
						'woGroups': ".(empty($arTemplate['CONTACT_LIST']['woGroups'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['woGroups'])).",
						'woUserInGroup': ".(empty($arTemplate['CONTACT_LIST']['woUserInGroup'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['woUserInGroup'])).",
						'chat': ".(empty($arTemplate['CHAT']['chat'])? '{}': CUtil::PhpToJSObject($arTemplate['CHAT']['chat'])).",
						'userInChat': ".(empty($arTemplate['CHAT']['userInChat'])? '{}': CUtil::PhpToJSObject($arTemplate['CHAT']['userInChat'])).",
						'message' : ".(empty($arTemplate['MESSAGE']['message'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['message'])).",
						'showMessage' : ".(empty($arTemplate['MESSAGE']['usersMessage'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['usersMessage'])).",
						'unreadMessage' : ".(empty($arTemplate['MESSAGE']['unreadMessage'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['unreadMessage'])).",
						'flashMessage' : ".(empty($arTemplate['MESSAGE']['flashMessage'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['flashMessage'])).",
						'history' : {},
						'openMessenger' : ".(isset($_GET['IM_DIALOG'])? "'".htmlspecialcharsbx($_GET['IM_DIALOG'])."'": 'false').",
						'openHistory' : ".(isset($_GET['IM_HISTORY'])? "'".htmlspecialcharsbx($_GET['IM_HISTORY'])."'": 'false').",
						'viewOffline': ".$arTemplate['VIEW_OFFLINE'].",
						'viewGroup': ".$arTemplate['VIEW_GROUP'].",
						'enableSound': ".$arTemplate['ENABLE_SOUND'].",
						'sendByEnter': ".$arTemplate['SEND_BY_ENTER'].",
						'currentTab' : ".($arTemplate['CURRENT_TAB']>0? $arTemplate['CURRENT_TAB']: '0').",
						'userId': ".$USER->GetID().",
						'path' : {'profile' : '".$arTemplate['PATH_TO_USER_PROFILE']."', 'profileTemplate' : '".$arTemplate['PATH_TO_USER_PROFILE_TEMPLATE']."', 'mail' : '".$arTemplate['PATH_TO_USER_MAIL']."'}
					});
				});
			";
		}
		else
		{
			$sJS = "
				BX.ready(function() {
					BXIM = new BX.IM(BX('bx-notifier-panel'), {
						'init': false,

						'desktop': ".$arTemplate["DESKTOP"].",
						'desktopStatus': ".(CIMMessenger::CheckDesktopStatusOnline()? 'true': 'false').",
						'desktopLinkOpen': ".$arTemplate["DESKTOP_LINK_OPEN"].",

						'userStatus': '".$arTemplate['STATUS']."',
						'ppStatus': ".$ppStatus.",
						'ppServerStatus': ".$ppServerStatus.",
						'xmppStatus': ".(CIMMessenger::CheckXmppStatusOnline()? 'true': 'false').",
						'bitrix24Status': ".(IsModuleInstalled('bitrix24')? 'true': 'false').",
						'bitrixIntranet': ".(IsModuleInstalled('intranet')? 'true': 'false').",
						'enableSound': ".$arTemplate['ENABLE_SOUND'].",
						'notify' : {},
						'users' : {},
						'userId': ".$USER->GetID().",

						'openMessenger' : ".(isset($_GET['IM_DIALOG'])? intval($_GET['IM_DIALOG']): 'false').",
						'openHistory' : ".(isset($_GET['IM_HISTORY'])? intval($_GET['IM_HISTORY']): 'false').",

						'path' : {'profile' : '".$arTemplate['PATH_TO_USER_PROFILE']."', 'profileTemplate' : '".$arTemplate['PATH_TO_USER_PROFILE_TEMPLATE']."', 'mail' : '".$arTemplate['PATH_TO_USER_MAIL']."'}
					});
				});
			";
		}

		return $sJS;
	}

	public static function CallCommand($recipientId, $command, $params = Array())
	{
		global $USER;

		if ($USER->GetID() <= 0 || intval($recipientId) <= 0)
			return false;

		if (!CModule::IncludeModule("pull"))
			return false;

		$arParams = Array(
			'command' => $command,
			'senderId' => $USER->GetID()
		);

		if ($command == 'invite')
		{
			$arParams['video'] = isset($params['video']) && $params['video'] == 'Y'? true: false;
		}
		else if ($command == 'signaling')
		{
			$arParams['peer'] = $params['peer'];
		}
		else if ($command == 'wait' || $command == 'busy' || $command == 'cancel' || $command == 'decline' || $command == 'accept')
		{
		}
		else
			return false;

		CPullStack::AddByUser(intval($recipientId), Array(
			'module_id' => 'im',
			'command' => 'call',
			'params' => $arParams,
		));

		return true;
	}

	public static function StartWriting($recipientId)
	{
		global $USER;

		if ($USER->GetID() > 0 && intval($recipientId) > 0 && CModule::IncludeModule("pull"))
		{
			CPullStack::AddByUser($recipientId, Array(
				'module_id' => 'im',
				'command' => 'startWriting',
				'params' => Array(
					'senderId' => $USER->GetID(),
					'recipientId' => $recipientId
				),
			));
			return true;
		}
		return false;
	}

	/* TMP FUNCTION */
	public static function GetCachePath($id)
	{
		$str = md5($id);
		return '/'.substr($str,2,2).'/'.intval($id);
	}

	public static function GetSonetCode($user_id, $site_id = SITE_ID)
	{
		global $DB;
		$result = array();
		$user_id = intval($user_id);

		if($user_id > 0 && IsModuleInstalled('socialnetwork'))
		{
			$strSQL = "
				SELECT CODE, SUM(CNT) CNT
				FROM b_sonet_log_counter
				WHERE USER_ID = ".$user_id."
				AND (SITE_ID = '".$site_id."' OR SITE_ID = '**')
				GROUP BY CODE
			";
			$dbRes = $DB->Query($strSQL, true, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arRes = $dbRes->Fetch())
				$result[$arRes["CODE"]] = $arRes["CNT"];
		}

		return $result;
	}
}
?>