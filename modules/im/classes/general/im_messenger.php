<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Im as IM;

class CIMMessenger
{
	private $user_id = 0;

	public function __construct($user_id = false)
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
			$arFields['NOTIFY_TITLE'] = substr($arFields['TITLE'], 0, 255);

		if (isset($arFields['NOTIFY_MESSAGE']) && !isset($arFields['MESSAGE']))
			$arFields['MESSAGE'] = $arFields['NOTIFY_MESSAGE'];

		if (isset($arFields['NOTIFY_MESSAGE_OUT']) && !isset($arFields['MESSAGE_OUT']))
			$arFields['MESSAGE_OUT'] = $arFields['NOTIFY_MESSAGE_OUT'];

		if (isset($arFields['MESSAGE']))
		{
			$arFields['MESSAGE'] = trim(str_replace(Array('[BR]', '[br]'), "\n", $arFields['MESSAGE']));
		}

		$arFields['MESSAGE_OUT'] = isset($arFields['MESSAGE_OUT'])? trim($arFields['MESSAGE_OUT']): "";

		$arFields['URL_PREVIEW'] = isset($arFields['URL_PREVIEW']) && $arFields['URL_PREVIEW'] == 'N'? 'N': 'Y';

		$bConvert = false;
		if (isset($arFields['CONVERT']) && $arFields['CONVERT'] == 'Y')
			$bConvert = true;

		if (!isset($arFields['MESSAGE_TYPE']))
			$arFields['MESSAGE_TYPE'] = "";

		if (!isset($arFields['NOTIFY_MODULE']))
			$arFields['NOTIFY_MODULE'] = 'im';

		if (!isset($arFields['NOTIFY_EVENT']))
			$arFields['NOTIFY_EVENT'] = 'default';

		if (!isset($arFields['PARAMS']))
		{
			$arFields['PARAMS'] = Array();
		}
		if (isset($arFields['ATTACH']) || isset($arFields['PARAMS']['ATTACH']))
		{
			$attach = isset($arFields['ATTACH'])? $arFields['ATTACH']: $arFields['PARAMS']['ATTACH'];
			if (is_object($attach))
			{
				$arFields['PARAMS']['ATTACH'] = Array($attach);
			}
			else if (is_array($attach))
			{
				$arFields['PARAMS']['ATTACH'] = $attach;
			}
			else
			{
				$arFields['PARAMS']['ATTACH'] = Array();
			}
		}
		if (isset($arFields['FILES']))
		{
			if (is_array($arFields['FILES']))
			{
				$arFields['PARAMS']['FILE_ID'] = $arFields['FILE_ID'];
			}
			else
			{
				$arFields['PARAMS']['FILE_ID'] = Array();
			}
		}
		if (isset($arFields['KEYBOARD']) || isset($arFields['PARAMS']['KEYBOARD']))
		{
			$keyboard = isset($arFields['KEYBOARD'])? $arFields['KEYBOARD']: $arFields['PARAMS']['KEYBOARD'];
			if (is_object($keyboard))
			{
				$arFields['PARAMS']['KEYBOARD'] = $keyboard;
			}
			else
			{
				$arFields['PARAMS']['KEYBOARD'] = Array();
			}
		}

		if (isset($arFields['FOR_USER_ID'])) // TODO create this feature in future
		{
			$arFields['PARAMS']['FOR_USER_ID'] = $arFields['FOR_USER_ID'];
		}

		$arFields['SKIP_COMMAND'] = isset($arFields['SKIP_COMMAND']) && $arFields['SKIP_COMMAND'] == 'Y'? 'Y': 'N';
		$arFields['SKIP_CONNECTOR'] = isset($arFields['SKIP_CONNECTOR']) && $arFields['SKIP_CONNECTOR'] == 'Y'? 'Y': 'N';
		$arFields['IMPORTANT_CONNECTOR'] = isset($arFields['IMPORTANT_CONNECTOR']) && $arFields['IMPORTANT_CONNECTOR'] == 'Y'? 'Y': 'N';

		$arFields['URL_ATTACH'] = Array();
		if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_SYSTEM)
		{
			if (!isset($arFields['NOTIFY_TYPE']) && intval($arFields['FROM_USER_ID']) > 0)
				$arFields['NOTIFY_TYPE'] = IM_NOTIFY_FROM;
			else if (!isset($arFields['NOTIFY_TYPE']))
				$arFields['NOTIFY_TYPE'] = IM_NOTIFY_SYSTEM;

			if (isset($arFields['NOTIFY_ANSWER']) && $arFields['NOTIFY_ANSWER'] == 'Y')
				$arFields['PARAMS']['CAN_ANSWER'] = 'Y';

			/*
			$urlPrepare = self::PrepareUrl($arFields['MESSAGE']);
			if ($urlPrepare['RESULT'])
			{
				if (empty($arFields['MESSAGE_OUT']))
				{
					$arFields['MESSAGE_OUT'] = $arFields['MESSAGE'];
				}
				$arFields['MESSAGE'] = $urlPrepare['MESSAGE'];
				$arFields['PARAMS']['ATTACH'] = array_merge($arFields['PARAMS']['ATTACH'], $urlPrepare['ATTACH']);
			}
			*/
		}
		else if ($arFields['URL_PREVIEW'] == 'Y')
		{
			$link = new CIMMessageLink();
			$urlPrepare = $link->prepareInsert($arFields['MESSAGE']);
			if ($urlPrepare['RESULT'])
			{
				if (empty($arFields['MESSAGE_OUT']))
				{
					$arFields['MESSAGE_OUT'] = $arFields['MESSAGE'];
				}
				$arFields['MESSAGE'] = $urlPrepare['MESSAGE'];

				if (isset($arFields['PARAMS']['URL_ID']))
				{
					$arFields['PARAMS']['URL_ID'] = array_merge($arFields['PARAMS']['URL_ID'], $urlPrepare['URL_ID']);
				}
				else
				{
					$arFields['PARAMS']['URL_ID'] = $urlPrepare['URL_ID'];
				}
				$arFields['URL_ATTACH'] = $urlPrepare['ATTACH'];
			}
		}

		if (isset($arFields['NOTIFY_EMAIL_TEMPLATE']) && !isset($arFields['EMAIL_TEMPLATE']))
			$arFields['EMAIL_TEMPLATE'] = $arFields['NOTIFY_EMAIL_TEMPLATE'];

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
					if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
					{
						$reason = GetMessage("IM_ERROR_MESSAGE_CANCELED");
					}
					else if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_SYSTEM)
					{
						$reason = GetMessage("IM_ERROR_NOTIFY_CANCELED");
					}
					else
					{
						$reason = GetMessage("IM_ERROR_GROUP_CANCELED");
					}
				}

				$GLOBALS["APPLICATION"]->ThrowException($reason, "ERROR_FROM_OTHER_MODULE");

				return false;
			}
		}
		if (!self::CheckFields($arFields))
			return false;

		if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			if (isset($arFields['TO_CHAT_ID']))
			{
				$chatId = $arFields['TO_CHAT_ID'];
				$arRel = CIMChat::GetRelationById($chatId);
				foreach ($arRel as $rel)
				{
					if ($rel['USER_ID'] == $arFields['FROM_USER_ID'])
						continue;

					$arFields['TO_USER_ID'] = $rel['USER_ID'];
				}

				if (!IsModuleInstalled('intranet'))
				{
					if (CIMSettings::GetPrivacy(CIMSettings::PRIVACY_MESSAGE) == CIMSettings::PRIVACY_RESULT_CONTACT && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed() && !CSocNetUserRelations::IsFriends($arFields['FROM_USER_ID'], $arFields['TO_USER_ID']))
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage('IM_ERROR_MESSAGE_PRIVACY_SELF'), "ERROR_FROM_PRIVACY_SELF");
						return false;
					}
					else if (CIMSettings::GetPrivacy(CIMSettings::PRIVACY_MESSAGE, $arFields['TO_USER_ID']) == CIMSettings::PRIVACY_RESULT_CONTACT && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed() && !CSocNetUserRelations::IsFriends($arFields['FROM_USER_ID'], $arFields['TO_USER_ID']))
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage('IM_ERROR_MESSAGE_PRIVACY'), "ERROR_FROM_PRIVACY");
						return false;
					}
				}
			}
			else
			{
				$arFields['FROM_USER_ID'] = intval($arFields['FROM_USER_ID']);
				$arFields['TO_USER_ID'] = intval($arFields['TO_USER_ID']);

				if (!IsModuleInstalled('intranet'))
				{
					if (CIMSettings::GetPrivacy(CIMSettings::PRIVACY_MESSAGE) == CIMSettings::PRIVACY_RESULT_CONTACT && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed() && !CSocNetUserRelations::IsFriends($arFields['FROM_USER_ID'], $arFields['TO_USER_ID']))
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage('IM_ERROR_MESSAGE_PRIVACY_SELF'), "ERROR_FROM_PRIVACY_SELF");
						return false;
					}
					else if (CIMSettings::GetPrivacy(CIMSettings::PRIVACY_MESSAGE, $arFields['TO_USER_ID']) == CIMSettings::PRIVACY_RESULT_CONTACT && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed() && !CSocNetUserRelations::IsFriends($arFields['FROM_USER_ID'], $arFields['TO_USER_ID']))
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage('IM_ERROR_MESSAGE_PRIVACY'), "ERROR_FROM_PRIVACY");
						return false;
					}
				}
				$chatId = CIMMessage::GetChatId($arFields['FROM_USER_ID'], $arFields['TO_USER_ID']);
			}

			if ($chatId > 0)
			{
				$arParams = Array();
				$arParams['CHAT_ID'] = $chatId;
				$arParams['AUTHOR_ID'] = intval($arFields['AUTHOR_ID']);
				$arParams['MESSAGE'] = $arFields['MESSAGE'];
				$arParams['MESSAGE_OUT'] = $arFields['MESSAGE_OUT'];
				$arParams['NOTIFY_MODULE'] = $arFields['NOTIFY_MODULE'];
				$arParams['NOTIFY_EVENT'] = $arFields['SYSTEM'] == 'Y'? 'private_system': 'private';

				if (isset($arFields['IMPORT_ID']))
					$arParams['IMPORT_ID'] = intval($arFields['IMPORT_ID']);

				if (isset($arFields['MESSAGE_DATE']))
				{
					$arParams['DATE_CREATE'] = new Bitrix\Main\Type\DateTime($arFields['MESSAGE_DATE']);
				}
				$arFiles = Array();
				$arFields['FILES'] = Array();
				if (isset($arFields['PARAMS']['FILE_ID']))
				{
					foreach ($arFields['PARAMS']['FILE_ID'] as $fileId)
					{
						$arFiles[$fileId] = $fileId;
					}
				}
				$arFields['FILES'] = CIMDisk::GetFiles($chatId, $arFiles, false);

				$messageFiles = self::GetFormatFilesMessageOut($arFields['FILES']);
				if (strlen($messageFiles) > 0)
				{
					$arParams['MESSAGE_OUT'] = strlen($arParams['MESSAGE_OUT'])>0? $arParams['MESSAGE_OUT']."\n".$messageFiles: $messageFiles;
					$arFields['MESSAGE_OUT'] = $arParams['MESSAGE_OUT'];
				}

				$result = IM\Model\MessageTable::add($arParams);
				$messageID = IntVal($result->getId());
				if ($messageID <= 0)
					return false;

				IM\Model\ChatTable::update($chatId, Array('LAST_MESSAGE_ID' => $messageID));

				if (!empty($arFields['PARAMS']))
					CIMMessageParam::Set($messageID, $arFields['PARAMS']);

				if (!empty($arFields['URL_ATTACH']))
				{
					if (isset($arFields['PARAMS']['ATTACH']))
					{
						$arFields['PARAMS']['ATTACH'] = array_merge($arFields['PARAMS']['ATTACH'], $arFields['URL_ATTACH']);
					}
					else
					{
						$arFields['PARAMS']['ATTACH'] = $arFields['URL_ATTACH'];
					}
				}

				//CUserCounter::Increment($arFields['TO_USER_ID'], 'im_message_v2', '**', false);
				CIMContactList::SetRecent(Array(
					'ENTITY_ID' => $arFields['TO_USER_ID'],
					'MESSAGE_ID' => $messageID,
					'CHAT_TYPE' => IM_MESSAGE_PRIVATE,
					'USER_ID' => $arFields['FROM_USER_ID']
				));

				if (!IM\User::getInstance($arFields['TO_USER_ID'])->isBot())
				{
					CIMContactList::SetRecent(Array(
						'ENTITY_ID' => $arFields['FROM_USER_ID'],
						'MESSAGE_ID' => $messageID,
						'CHAT_TYPE' => IM_MESSAGE_PRIVATE,
						'USER_ID' => $arFields['TO_USER_ID']
					));
				}

				CIMStatus::SetIdle($arFields['FROM_USER_ID'], false);

				if (!$bConvert)
				{
					$arRel = CIMChat::GetRelationById($chatId);
					foreach ($arRel as $relation)
					{
						if (IM\User::getInstance($relation["USER_ID"])->isBot())
						{
							continue;
						}
						if ($relation["USER_ID"] == $arFields["TO_USER_ID"])
						{
							if ($relation['STATUS'] != IM_STATUS_UNREAD)
							{
								IM\Model\RelationTable::update($relation["ID"], array(
									"STATUS" => IM_STATUS_UNREAD,
								));
							}
						}
						else
						{
							IM\Model\RelationTable::update($relation["ID"], array(
								"STATUS" => IM_STATUS_READ,
								"LAST_ID" => $messageID,
								"LAST_SEND_ID" => $messageID,
								"LAST_READ" => new Bitrix\Main\Type\DateTime(),
							));
						}
					}

					if (CModule::IncludeModule("pull"))
					{
						$arParams['FROM_USER_ID'] = $arFields['FROM_USER_ID'];
						$arParams['TO_USER_ID'] = $arFields['TO_USER_ID'];

						$pullMessage = Array(
							'module_id' => 'im',
							'command' => 'message',
							'params' => CIMMessage::GetFormatMessage(Array(
								'ID' => $messageID,
								'CHAT_ID' => $chatId,
								'TO_USER_ID' => $arParams['TO_USER_ID'],
								'FROM_USER_ID' => $arParams['FROM_USER_ID'],
								'SYSTEM' => $arFields['SYSTEM'] == 'Y'? 'Y': 'N',
								'MESSAGE' => $arParams['MESSAGE'],
								'DATE_CREATE' => time(),
								'PARAMS' => self::PrepareParamsForPull($arFields['PARAMS']),
								'FILES' => $arFields['FILES'],
							)),
						);

						$pullMessageTo = $pullMessage;
						if (CPullOptions::GetPushStatus() && (!isset($arFields['PUSH']) || $arFields['PUSH'] == 'Y'))
						{
							if (CIMSettings::GetNotifyAccess($arParams["TO_USER_ID"], 'im', 'message', CIMSettings::CLIENT_PUSH))
							{
								$pushParams = self::PreparePushForPrivate(Array(
									'FROM_USER_ID' => $arParams['FROM_USER_ID'],
									'MESSAGE' => $arParams['MESSAGE'],
									'SYSTEM' => $arFields['SYSTEM'],
									'FILES' => $arFields['FILES'],
									'ATTACH' => isset($arFields['PARAMS']['ATTACH'])? true: false
								));
								if ($pushParams)
								{
									$pullMessageTo = array_merge($pullMessage, $pushParams);
								}
							}
						}

						CPullStack::AddByUser($arParams['TO_USER_ID'], $pullMessageTo);
						CPullStack::AddByUser($arParams['FROM_USER_ID'], $pullMessage);

						CPushManager::DeleteFromQueueBySubTag($arParams['FROM_USER_ID'], 'IM_MESS');

						//self::SendBadges($arParams['TO_USER_ID']);
					}

					foreach(GetModuleEvents("im", "OnAfterMessagesAdd", true) as $arEvent)
						ExecuteModuleEventEx($arEvent, array(intval($messageID), $arFields));

					$arFields['COMMAND_CONTEXT'] = 'TEXTAREA';
					$result = \Bitrix\Im\Command::onCommandAdd(intval($messageID), $arFields);
					if (!$result)
					{
						\Bitrix\Im\Bot::onMessageAdd(intval($messageID), $arFields);
					}
				}

				return $messageID;
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_CREATE"), "CHAT_ID");
				return false;
			}
		}
		else if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_CHAT || $arFields['MESSAGE_TYPE'] == IM_MESSAGE_OPEN)
		{
			$arFields['SKIP_USER_CHECK'] = isset($arFields['SKIP_USER_CHECK']) && $arFields['SKIP_USER_CHECK'] == 'Y'? 'Y': 'N';
			$arFields['FROM_USER_ID'] = intval($arFields['FROM_USER_ID']);
			$chatId = 0;
			$systemMessage = false;
			if (isset($arFields['SYSTEM']) && $arFields['SYSTEM'] == 'Y')
			{
				$strSql = "
					SELECT
						C.ID CHAT_ID,
						C.TITLE CHAT_TITLE,
						C.AUTHOR_ID CHAT_AUTHOR_ID,
						C.TYPE CHAT_TYPE,
						C.ENTITY_TYPE CHAT_ENTITY_TYPE,
						C.ENTITY_ID CHAT_ENTITY_ID,
						C.ENTITY_DATA_1 CHAT_ENTITY_DATA_1,
						C.ENTITY_DATA_2 CHAT_ENTITY_DATA_2,
						C.ENTITY_DATA_3 CHAT_ENTITY_DATA_3,
						'1' RID
					FROM b_im_chat C
					WHERE C.ID = ".intval($arFields['TO_CHAT_ID'])."
				";
				$systemMessage = true;
			}
			else
			{
				$strSql = "
					SELECT
						C.ID CHAT_ID,
						C.TITLE CHAT_TITLE,
						C.AUTHOR_ID CHAT_AUTHOR_ID,
						C.TYPE CHAT_TYPE,
						C.ENTITY_TYPE CHAT_ENTITY_TYPE,
						C.ENTITY_ID CHAT_ENTITY_ID,
						C.ENTITY_DATA_1 CHAT_ENTITY_DATA_1,
						C.ENTITY_DATA_2 CHAT_ENTITY_DATA_2,
						C.ENTITY_DATA_3 CHAT_ENTITY_DATA_3,
						R.USER_ID RID
					FROM b_im_chat C
					LEFT JOIN b_im_relation R ON R.CHAT_ID = C.ID AND R.USER_ID = ".$arFields['FROM_USER_ID']."
					WHERE C.ID = ".intval($arFields['TO_CHAT_ID'])."
				";
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
			{
				$chatId = intval($arRes['CHAT_ID']);
				$chatTitle = htmlspecialcharsbx($arRes['CHAT_TITLE']);
				$chatAuthorId = intval($arRes['CHAT_AUTHOR_ID']);
				$arRes['CHAT_TYPE'] = trim($arRes['CHAT_TYPE']);
				$arFields['MESSAGE_TYPE'] = $arRes['CHAT_TYPE'];

				if ($arFields['SKIP_USER_CHECK'] == 'N')
				{
					if ($arRes['CHAT_TYPE'] == IM_MESSAGE_OPEN)
					{
						if (!CIMMessenger::CheckEnableOpenChat())
						{
							$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_GROUP_CANCELED"), "CANCELED");
							return false;
						}
						else if (intval($arRes['RID']) <= 0)
						{
							if (IM\User::getInstance($arFields['FROM_USER_ID'])->isExtranet())
							{
								$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_GROUP_CANCELED"), "CANCELED");
								return false;
							}
							else
							{
								$chat = new CIMChat(0);
								$chat->AddUser($chatId, $arFields['FROM_USER_ID']);
							}
						}
					}
					else if (intval($arRes['RID']) <= 0)
					{
						$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_GROUP_CANCELED"), "CANCELED");
						return false;
					}
				}
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
				$arParams['AUTHOR_ID'] = $systemMessage? 0: intval($arFields['AUTHOR_ID']);
				$arParams['MESSAGE'] = $arFields['MESSAGE'];
				$arParams['MESSAGE_OUT'] = $arFields['MESSAGE_OUT'];
				$arParams['NOTIFY_MODULE'] = 'im';
				$arParams['NOTIFY_EVENT'] = 'group';

				if (isset($arFields['MESSAGE_DATE']))
				{
					$arParams['DATE_CREATE'] = new Bitrix\Main\Type\DateTime($arFields['MESSAGE_DATE']);
				}

				$arFiles = Array();
				$arFields['FILES'] = Array();

				if (isset($arFields['PARAMS']['FILE_ID']))
				{
					foreach ($arFields['PARAMS']['FILE_ID'] as $fileId)
					{
						$arFiles[$fileId] = $fileId;
					}
				}
				$arFields['FILES'] = CIMDisk::GetFiles($chatId, $arFiles, false);
				$messageFiles = self::GetFormatFilesMessageOut($arFields['FILES']);
				if (strlen($messageFiles) > 0)
				{
					$arParams['MESSAGE_OUT'] = strlen($arParams['MESSAGE_OUT'])>0? $arParams['MESSAGE_OUT']."\n".$messageFiles: $messageFiles;
					$arFields['MESSAGE_OUT'] = $arParams['MESSAGE_OUT'];
				}

				$result = IM\Model\MessageTable::add($arParams);
				$messageID = IntVal($result->getId());
				if ($messageID <= 0)
					return false;

				IM\Model\ChatTable::update($chatId, Array('LAST_MESSAGE_ID' => $messageID));

				if (!empty($arFields['PARAMS']))
					CIMMessageParam::Set($messageID, $arFields['PARAMS']);

				if (!empty($arFields['URL_ATTACH']))
				{
					if (isset($arFields['PARAMS']['ATTACH']))
					{
						$arFields['PARAMS']['ATTACH'] = array_merge($arFields['PARAMS']['ATTACH'], $arFields['URL_ATTACH']);
					}
					else
					{
						$arFields['PARAMS']['ATTACH'] = $arFields['URL_ATTACH'];
					}
				}

				//$sqlCounter = "SELECT USER_ID as ID, 1 as CNT, '**' as SITE_ID, 'im_chat_v2' as CODE, 1 as SENT
				//				FROM b_im_relation R1
				//				WHERE CHAT_ID = ".$chatId." AND USER_ID <> ".$arFields['FROM_USER_ID'];
				//CUserCounter::IncrementWithSelect($sqlCounter, false);

				$arBotInChat = Array();
				$arRel = CIMChat::GetRelationById($chatId);
				foreach ($arRel as $relation)
				{
					if ($relation["EXTERNAL_AUTH_ID"] == \Bitrix\Im\Bot::EXTERNAL_AUTH_ID)
					{
						$arBotInChat[$relation["USER_ID"]] = $relation["USER_ID"];
						continue;
					}
					if ($arRes['CHAT_ENTITY_TYPE'] == "LINES" && $relation["EXTERNAL_AUTH_ID"] == 'imconnector')
					{
						continue;
					}
					CIMContactList::SetRecent(Array(
						'ENTITY_ID' => $chatId,
						'MESSAGE_ID' => $messageID,
						'CHAT_TYPE' => $arFields['MESSAGE_TYPE'],
						'USER_ID' => $relation['USER_ID']
					));

					if ($relation["USER_ID"] == $arFields["FROM_USER_ID"])
					{
						IM\Model\RelationTable::update($relation["ID"], array(
							"STATUS" => IM_STATUS_READ,
							"LAST_ID" => $messageID,
							"LAST_SEND_ID" => $messageID,
							"LAST_READ" => new Bitrix\Main\Type\DateTime(),
						));
					}
					else
					{
						if ($relation['STATUS'] != IM_STATUS_UNREAD)
						{
							IM\Model\RelationTable::update($relation["ID"], array(
								"STATUS" => IM_STATUS_UNREAD,
							));
						}
					}
				}

				CIMStatus::SetIdle($arFields['FROM_USER_ID'], false);

				if (CModule::IncludeModule("pull"))
				{
					$arParams['FROM_USER_ID'] = $arFields['FROM_USER_ID'];
					$arParams['TO_CHAT_ID'] = $arFields['TO_CHAT_ID'];

					$pullMessage = Array(
						'module_id' => 'im',
						'command' => 'messageChat',
						'params' => CIMMessage::GetFormatMessage(Array(
							'ID' => $messageID,
							'CHAT_ID' => $chatId,
							'TO_CHAT_ID' => $arParams['TO_CHAT_ID'],
							'FROM_USER_ID' => $arParams['FROM_USER_ID'],
							'MESSAGE' => $arParams['MESSAGE'],
							'SYSTEM' => $arFields['SYSTEM'] == 'Y'? 'Y': 'N',
							'DATE_CREATE' => time(),
							'PARAMS' => self::PrepareParamsForPull($arFields['PARAMS']),
							'FILES' => $arFields['FILES'],
						)),
					);

					if (CPullOptions::GetPushStatus() && (!isset($arFields['PUSH']) || $arFields['PUSH'] == 'Y'))
					{
						$pushParams = self::PreparePushForChat(Array(
							'CHAT_ID' => $chatId,
							'CHAT_TITLE' => $chatTitle,
							'FROM_USER_ID' => $arParams['FROM_USER_ID'],
							'MESSAGE' => $arParams['MESSAGE'],
							'SYSTEM' => $arFields['SYSTEM'],
							'FILES' => $arFields['FILES'],
							'ATTACH' => isset($arFields['PARAMS']['ATTACH'])? true: false
						));
						if ($pushParams)
						{
							$pullMessage = array_merge($pullMessage, $pushParams);
						}
					}

					$pullUsers = Array();
					$pullUsersSkip = Array();
					foreach ($arRel as $rel)
					{
						if ($arRes['CHAT_ENTITY_TYPE'] == "LINES" && $rel["EXTERNAL_AUTH_ID"] == 'imconnector')
						{
						}
						else if ($rel['USER_ID'] == $arParams['FROM_USER_ID'])
						{
							$pullUsers[] = $rel['USER_ID'];
							$pullUsersSkip[] = $rel['USER_ID'];
							CPushManager::DeleteFromQueueBySubTag($arParams['FROM_USER_ID'], 'IM_MESS');
						}
						else
						{
							$pullUsers[] = $rel['USER_ID'];
							if ($rel['NOTIFY_BLOCK'] == 'Y' || !CIMSettings::GetNotifyAccess($rel['USER_ID'], 'im', ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_OPEN? 'openChat': 'chat'), CIMSettings::CLIENT_PUSH))
							{
								$pullUsersSkip[] = $rel['USER_ID'];
							}
						}
					}
					$pullMessage['push']['skip_users'] = $pullUsersSkip;

					CPullStack::AddByUsers($pullUsers, $pullMessage);

					if ($arRes['CHAT_TYPE'] == IM_MESSAGE_OPEN)
					{
						$pullMessageToWatch = $pullMessage;
						unset($pullMessageToWatch['push']);
						CPullWatch::AddToStack('IM_PUBLIC_'.$chatId, $pullMessageToWatch);
					}

					self::SendMention(Array(
						'CHAT_ID' => $chatId,
						'CHAT_TITLE' => $chatTitle,
						'CHAT_RELATION' => $arRel,
						'CHAT_TYPE' => $arFields['MESSAGE_TYPE'],
						'MESSAGE' => $arParams['MESSAGE'],
						'FILES' => $arFields['FILES'],
						'FROM_USER_ID' => $arParams['FROM_USER_ID'],
					));
					//self::SendBadges($usersForBadges);
				}

				$arFields['CHAT_AUTHOR_ID'] = $chatAuthorId;
				$arFields['CHAT_ENTITY_TYPE'] = $arRes['CHAT_ENTITY_TYPE'];
				$arFields['CHAT_ENTITY_ID'] = $arRes['CHAT_ENTITY_ID'];
				$arFields['CHAT_ENTITY_DATA_1'] = $arRes['CHAT_ENTITY_DATA_1'];
				$arFields['CHAT_ENTITY_DATA_2'] = $arRes['CHAT_ENTITY_DATA_2'];
				$arFields['CHAT_ENTITY_DATA_3'] = $arRes['CHAT_ENTITY_DATA_3'];
				$arFields['BOT_IN_CHAT'] = $arBotInChat;

				foreach(GetModuleEvents("im", "OnAfterMessagesAdd", true) as $arEvent)
					ExecuteModuleEventEx($arEvent, array(intval($messageID), $arFields));

				$arFields['COMMAND_CONTEXT'] = 'TEXTAREA';
				$result = \Bitrix\Im\Command::onCommandAdd(intval($messageID), $arFields);
				if (!$result)
				{
					\Bitrix\Im\Bot::onMessageAdd(intval($messageID), $arFields);
				}

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
			$arFields['TO_USER_ID'] = intval($arFields['TO_USER_ID']);

			$orm = \Bitrix\Main\UserTable::getById($arFields['TO_USER_ID']);
			$userData = $orm->fetch();
			if (!$userData || $userData['ACTIVE'] == 'N' || $userData['EXTERNAL_AUTH_ID'] == 'email' || $userData['EXTERNAL_AUTH_ID'] == 'bot' || $userData['EXTERNAL_AUTH_ID'] == 'imconnector')
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_CREATE"), "TO_USER_ID");
				return false;
			}

			$chatId = 0;
			$strSql = "
				SELECT ID CHAT_ID
				FROM b_im_chat
				WHERE AUTHOR_ID = ".$arFields['TO_USER_ID']." AND TYPE = '".IM_MESSAGE_SYSTEM."'
				ORDER BY ID ASC
			";
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
			{
				$chatId = intval($arRes['CHAT_ID']);
			}
			else
			{
				$result = IM\Model\ChatTable::add(Array('TYPE' => IM_MESSAGE_SYSTEM, 'AUTHOR_ID' => $arFields['TO_USER_ID']));
				$chatId = $result->getId();
				if ($chatId <= 0)
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_ERROR_MESSAGE_CREATE"), "CHAT_ID");
					return false;
				}

				IM\Model\RelationTable::add(array(
					"CHAT_ID" => $chatId,
					"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
					"USER_ID" => intval($arFields['TO_USER_ID']),
					"STATUS" => ($bConvert? 2: 0),
				));
			}

			if ($chatId > 0)
			{
				$arParams = Array();
				$arParams['CHAT_ID'] = $chatId;
				$arParams['AUTHOR_ID'] = intval($arFields['AUTHOR_ID']);
				$arParams['MESSAGE'] = $arFields['MESSAGE'];
				$arParams['MESSAGE_OUT'] = $arFields['MESSAGE_OUT'];
				$arParams['NOTIFY_TYPE'] = intval($arFields['NOTIFY_TYPE']);
				$arParams['NOTIFY_MODULE'] = $arFields['NOTIFY_MODULE'];
				$arParams['NOTIFY_EVENT'] = $arFields['NOTIFY_EVENT'];

				//if (strlen($arParams['MESSAGE']) <= 0 && strlen($arParams['MESSAGE_OUT']) <= 0)
				//	return false;

				$sendToSite = true;
				if ($arParams['NOTIFY_TYPE'] != IM_NOTIFY_CONFIRM)
					$sendToSite = CIMSettings::GetNotifyAccess($arFields["TO_USER_ID"], $arFields["NOTIFY_MODULE"], $arFields["NOTIFY_EVENT"], CIMSettings::CLIENT_SITE);

				if (!$sendToSite)
					$arParams['NOTIFY_READ'] = 'Y';

				if (isset($arFields['IMPORT_ID']))
					$arParams['IMPORT_ID'] = intval($arFields['IMPORT_ID']);

				if (isset($arFields['MESSAGE_DATE']))
				{
					$arParams['DATE_CREATE'] = new Bitrix\Main\Type\DateTime($arFields['MESSAGE_DATE']);
				}

				if (isset($arFields['EMAIL_TEMPLATE']) && strlen(trim($arFields['EMAIL_TEMPLATE']))>0)
					$arParams['EMAIL_TEMPLATE'] = trim($arFields['EMAIL_TEMPLATE']);

				$arParams['NOTIFY_TAG'] = isset($arFields['NOTIFY_TAG'])? $arFields['NOTIFY_TAG']: '';
				$arParams['NOTIFY_SUB_TAG'] = isset($arFields['NOTIFY_SUB_TAG'])? $arFields['NOTIFY_SUB_TAG']: '';

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

				if ($sendToSite)
				{
					$result = IM\Model\MessageTable::add($arParams);
					$messageID = IntVal($result->getId());
					if ($messageID <= 0)
						return false;
				}
				else
				{
					$messageID = time();
				}

				if (!$bConvert)
				{
					if (CModule::IncludeModule('pull'))
					{
						$CPushManager = new CPushManager();
						if (isset($arFields['PUSH_MESSAGE']) && CIMSettings::GetNotifyAccess($arFields["TO_USER_ID"], $arFields['NOTIFY_MODULE'], $arFields['NOTIFY_EVENT'], CIMSettings::CLIENT_PUSH) && CModule::IncludeModule('pull'))
						{
							$CPushManager->AddQueue(Array(
								'USER_ID' => $arFields['TO_USER_ID'],
								'MESSAGE' => str_replace("\n", " ", trim($arFields['PUSH_MESSAGE'])),
								'PARAMS' => isset($arFields['PUSH_PARAMS'])? $arFields['PUSH_PARAMS']: '',
								'TAG' => $arParams['NOTIFY_TAG'],
								'SUB_TAG' => $arParams['NOTIFY_SUB_TAG'],
								'APP_ID' => isset($arParams['PUSH_APP_ID'])? $arParams['PUSH_APP_ID']: '',
							));
						}
						else
						{
							$CPushManager->AddQueue(Array(
								'USER_ID' => $arFields['TO_USER_ID'],
								'APP_ID' => isset($arParams['PUSH_APP_ID'])? $arParams['PUSH_APP_ID']: ''
							));
						}
					}
					foreach(GetModuleEvents("im", "OnAfterNotifyAdd", true) as $arEvent)
						ExecuteModuleEventEx($arEvent, array(intval($messageID), $arFields));
				}
				if (!$sendToSite)
				{
					return false;
				}

				if (!empty($arFields['PARAMS']))
					CIMMessageParam::Set($messageID, $arFields['PARAMS']);

				IM\Model\ChatTable::update($chatId, Array('LAST_MESSAGE_ID' => $messageID));

				CIMMessenger::SpeedFileDelete($arFields['TO_USER_ID'], IM_SPEED_NOTIFY);

				if (!$bConvert)
				{
					//CUserCounter::Increment($arFields['TO_USER_ID'], 'im_notify_v2', '**', false);
					$strSql = "UPDATE b_im_relation SET STATUS = '".IM_STATUS_UNREAD."' WHERE USER_ID = ".intval($arFields['TO_USER_ID'])." AND MESSAGE_TYPE = '".IM_MESSAGE_SYSTEM."' AND CHAT_ID = ".$chatId;
					$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

					if (CModule::IncludeModule("pull"))
					{
						CPullStack::AddByUser(intval($arFields['TO_USER_ID']), Array(
							'module_id' => 'im',
							'command' => 'notify',
							'params' => CIMNotify::GetFormatNotify(Array(
								'ID' => $messageID,
								'DATE_CREATE' => time(),
								'FROM_USER_ID' => intval($arFields['FROM_USER_ID']),
								'MESSAGE' => $arParams['MESSAGE'],
								'PARAMS' => self::PrepareParamsForPull($arFields['PARAMS']),
								'NOTIFY_MODULE' => $arParams['NOTIFY_MODULE'],
								'NOTIFY_EVENT' => $arParams['NOTIFY_EVENT'],
								'NOTIFY_TAG' => $arParams['NOTIFY_TAG'],
								'NOTIFY_TYPE' => $arParams['NOTIFY_TYPE'],
								'NOTIFY_BUTTONS' => isset($arParams['NOTIFY_BUTTONS'])? $arParams['NOTIFY_BUTTONS']: serialize(Array()),
								'NOTIFY_TITLE' => isset($arParams['NOTIFY_TITLE'])? $arParams['NOTIFY_TITLE']: '',
							)),
						));
						self::SendBadges($arFields['TO_USER_ID']);
					}
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
	}

	public static function CheckPossibilityUpdateMessage($id, $userId = null)
	{
		global $USER;
		$userId = is_null($userId)? $USER->GetId(): intval($userId);
		if ($userId <= 0)
			return false;

		$result = false;
		if (
			CModule::IncludeModule('pull') && CPullOptions::GetNginxStatus() &&
			intval($id) > 0 && $userId > 0
		)
		{
			$message = self::GetById($id);
			if (
				$message !== false &&
				$message['AUTHOR_ID'] == $userId &&
				$message['DATE_CREATE']+259200 > time() &&
				$message['MESSAGE_TYPE'] != IM_MESSAGE_SYSTEM &&
				$message['PARAMS']['IS_DELETED'] == 'N'
			)
			{
				$result = $message;
			}
		}

		return $result;
	}

	public static function Update($id, $text, $urlPreview = true, $editFlag = true, $userId = null, $robot = false)
	{
		$text = trim(str_replace(Array('[BR]', '[br]'), "\n", $text));
		if (strlen($text) <= 0)
		{
			return self::Delete($id, $userId, $robot);
		}

		$message = self::CheckPossibilityUpdateMessage($id, $userId);
		if (!$message)
			return false;

		$arUpdate = Array('MESSAGE' => $text, 'MESSAGE_OUT' => '');
		$urlId = Array();
		if ($urlPreview)
		{
			$link = new CIMMessageLink();
			$urlPrepare = $link->prepareInsert($text);
			if ($urlPrepare['RESULT'])
			{
				$arUpdate['MESSAGE_OUT'] = $text;
				$arUpdate['MESSAGE'] = $urlPrepare['MESSAGE'];
				$urlId = $urlPrepare['URL_ID'];
			}
		}

		IM\Model\MessageTable::update($message['ID'], $arUpdate);

		CIMMessageParam::Set($message['ID'], Array('IS_EDITED' => $editFlag?'Y':'N', 'URL_ID' => $urlId));

		$arFields = $message;
		$arFields['MESSAGE'] = $text;
		$arFields['DATE_MODIFY'] = time()+CTimeZone::GetOffset();

		$CCTP = new CTextParser();
		$CCTP->MaxStringLen = 200;
		$CCTP->allow = array("HTML" => "N", "USER" => "N",  "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
		$pullMessage = $CCTP->convertText(htmlspecialcharsbx($arFields['MESSAGE']));

		$relations = CIMMessenger::GetRelationById($message['ID']);

		$arPullMessage = Array(
			'id' => $arFields['ID'],
			'type' => $arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE? 'private': 'chat',
			'text' => $pullMessage,
			'date' => $arFields['DATE_MODIFY'],
		);
		if ($message['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$arFields['FROM_USER_ID'] = $arFields['AUTHOR_ID'];
			foreach ($relations as $rel)
			{
				if ($rel['USER_ID'] != $arFields['AUTHOR_ID'])
					$arFields['TO_USER_ID'] = $rel['USER_ID'];
			}

			$arPullMessage['fromUserId'] = $arFields['FROM_USER_ID'];
			$arPullMessage['toUserId'] = $arFields['TO_USER_ID'];
		}
		else
		{
			$arPullMessage['chatId'] = $arFields['CHAT_ID'];
			$arPullMessage['senderId'] = $arFields['AUTHOR_ID'];

			if ($message['CHAT_ENTITY_TYPE'] == 'LINES')
			{
				foreach ($relations as $rel)
				{
					if ($rel["EXTERNAL_AUTH_ID"] == 'imconnector')
					{
						unset($relations[$rel["USER_ID"]]);
					}
				}
			}
		}

		$arMessages[$message['ID']] = Array();

		$params = CIMMessageParam::Get(Array($message['ID']));
		foreach ($params as $messageId => $param)
		{
			$arMessages[$messageId]['params'] = $param;
			if (isset($arMessages[$messageId]['params']['URL_ID']))
				unset($arMessages[$messageId]['params']['URL_ID']);
		}
		$arMessages = CIMMessageLink::prepareShow($arMessages, $params);

		$arPullMessage['params'] = CIMMessenger::PrepareParamsForPull($arMessages[$message['ID']]['params']);

		CPullStack::AddByUsers(array_keys($relations), Array(
			'module_id' => 'im',
			'command' => 'messageUpdate',
			'params' => $arPullMessage,
		));
		foreach ($relations as $rel)
		{
			$obCache = new CPHPCache();
			$obCache->CleanDir('/bx/imc/recent'.self::GetCachePath($rel['USER_ID']));
		}

		if ($message['MESSAGE_TYPE'] == IM_MESSAGE_OPEN)
		{
			CPullWatch::AddToStack('IM_PUBLIC_'.$message['CHAT_ID'], Array(
				'module_id' => 'im',
				'command' => 'messageUpdate',
				'params' => $arPullMessage,
			));
		}

		if (!$robot && $message['CHAT_ENTITY_TYPE'] == 'LINES')
		{
			list($connectorType, $lineId, $chatId) = explode("|", $message['CHAT_ENTITY_ID']);
			if ($connectorType == "livechat")
			{
				foreach($params[$message['ID']]['CONNECTOR_MID'] as $mid)
				{
					self::Update($mid, $text, $urlPreview, $editFlag, $userId, true);
				}
			}
			else
			{
				return false;
			}
		}
		else if (!$robot && $message['CHAT_ENTITY_TYPE'] == 'LIVECHAT')
		{
			foreach($params[$message['ID']]['CONNECTOR_MID'] as $mid)
			{
				self::Update($mid, $text, $urlPreview, $editFlag, $userId, true);
			}
		}

		foreach(GetModuleEvents("im", "OnAfterMessagesUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(intval($id), $arFields));

		return true;
	}

	public static function Delete($id, $userId = null, $robot = false)
	{
		$message = self::CheckPossibilityUpdateMessage($id, $userId);
		if (!$message)
			return false;

		$date = FormatDate("FULL", $message['DATE_CREATE']+CTimeZone::GetOffset());

		IM\Model\MessageTable::update($message['ID'], array(
			"MESSAGE" => GetMessage('IM_MESSAGE_DELETED'),
			"MESSAGE_OUT" => GetMessage('IM_MESSAGE_DELETED_OUT', Array('#DATE#' => $date)),
		));

		$params = CIMMessageParam::Get($message['ID']);
		if (!empty($params['FILE_ID']))
		{
			foreach ($params['FILE_ID'] as $fileId)
			{
				CIMDisk::DeleteFile($message['CHAT_ID'], $fileId);
			}
		}

		CIMMessageParam::Set($message['ID'], Array('IS_DELETED' => 'Y', 'URL_ID' => Array(), 'FILE_ID' => Array(), 'KEYBOARD' => 'N', 'ATTACH' => Array()));

		$arFields = $message;
		$arFields['MESSAGE'] = GetMessage('IM_MESSAGE_DELETED_OUT', Array('#DATE#' => $date));
		$arFields['DATE_MODIFY'] = time()+CTimeZone::GetOffset();

		$relations = CIMMessenger::GetRelationById($message['ID']);
		$arPullMessage = Array(
			'id' => $arFields['ID'],
			'type' => $arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE? 'private': 'chat',
			'date' => $arFields['DATE_MODIFY'],
			'text' => GetMessage('IM_MESSAGE_DELETED'),
		);
		if ($message['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$arFields['FROM_USER_ID'] = $arFields['AUTHOR_ID'];
			foreach ($relations as $rel)
			{
				if ($rel['USER_ID'] != $arFields['AUTHOR_ID'])
					$arFields['TO_USER_ID'] = $rel['USER_ID'];
			}

			$arPullMessage['fromUserId'] = $arFields['FROM_USER_ID'];
			$arPullMessage['toUserId'] = $arFields['TO_USER_ID'];
		}
		else
		{
			$arPullMessage['chatId'] = $arFields['CHAT_ID'];
			$arPullMessage['senderId'] = $arFields['AUTHOR_ID'];

			if ($message['CHAT_ENTITY_TYPE'] == 'LINES')
			{
				foreach ($relations as $rel)
				{
					if ($rel["EXTERNAL_AUTH_ID"] == 'imconnector')
					{
						unset($relations[$rel["USER_ID"]]);
					}
				}
			}
		}

		CPullStack::AddByUsers(array_keys($relations), Array(
			'module_id' => 'im',
			'command' => 'messageDelete',
			'params' => $arPullMessage
		));
		foreach ($relations as $rel)
		{
			$obCache = new CPHPCache();
			$obCache->CleanDir('/bx/imc/recent'.self::GetCachePath($rel['USER_ID']));
		}
		if ($message['MESSAGE_TYPE'] == IM_MESSAGE_OPEN)
		{
			CPullWatch::AddToStack('IM_PUBLIC_'.$message['CHAT_ID'], Array(
				'module_id' => 'im',
				'command' => 'messageUpdate',
				'params' => $arPullMessage,
			));
		}

		if (!$robot && $message['CHAT_ENTITY_TYPE'] == 'LINES')
		{
			list($connectorType, $lineId, $chatId) = explode("|", $message['CHAT_ENTITY_ID']);
			if ($connectorType == "livechat")
			{
				foreach($params['CONNECTOR_MID'] as $mid)
				{
					self::Delete($mid, $userId, true);
				}
			}
			else
			{
				return false;
			}
		}
		else if (!$robot && $message['CHAT_ENTITY_TYPE'] == 'LIVECHAT')
		{
			foreach($params['CONNECTOR_MID'] as $mid)
			{
				self::Delete($mid, $userId, true);
			}
		}

		foreach(GetModuleEvents("im", "OnAfterMessagesDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(intval($id), $arFields));

		return true;
	}

	public static function Like($id, $action = 'auto', $userId = null, $robot = false)
	{
		if (!CModule::IncludeModule('pull'))
			return false;

		global $USER;
		$userId = is_null($userId)? $USER->GetId(): intval($userId);
		if ($userId <= 0)
			return false;

		$action = in_array($action, Array('plus', 'minus'))? $action: 'auto';

		$message = self::GetById($id);
		if (!$message)
			return false;

		$relations = CIMMessenger::GetRelationById($id);

		$result = IM\Model\ChatTable::getList(Array(
			'filter'=>Array(
				'=ID' => $message['CHAT_ID']
			)
		));
		$chat = $result->fetch();
		if ($chat['ENTITY_TYPE'] != 'LIVECHAT')
		{
			if (!isset($relations[$userId]))
				return false;
		}

		if (!$robot && $chat['ENTITY_TYPE'] == 'LINES')
		{
			list($connectorType, $lineId, $chatId) = explode("|", $chat['ENTITY_ID']);
			if ($connectorType == "livechat")
			{
				foreach($message['PARAMS']['CONNECTOR_MID'] as $mid)
				{
					self::Like($mid, $action, $userId, true);
				}
			}
			else
			{
				return false;
			}
		}
		else if (!$robot && $chat['ENTITY_TYPE'] == 'LIVECHAT')
		{
			foreach($message['PARAMS']['CONNECTOR_MID'] as $mid)
			{
				self::Like($mid, $action, $userId, true);
			}
		}

		$isLike = false;
		if (isset($message['PARAMS']['LIKE']))
		{
			$isLike = in_array($userId, $message['PARAMS']['LIKE']);
		}

		if ($isLike && $action == 'plus')
		{
			return false;
		}
		else if (!$isLike && $action == 'minus')
		{
			return false;
		}

		$isLike = true;
		if (isset($message['PARAMS']['LIKE']))
		{
			$like = $message['PARAMS']['LIKE'];
			$selfLike = array_search($userId, $like);
			if ($selfLike !== false)
			{
				$isLike = false;
				unset($like[$selfLike]);
			}
			else
			{
				$like[] = $userId;
			}
		}
		else
		{
			$like = Array($userId);
		}

		sort($like);
		CIMMessageParam::Set($id, Array('LIKE' => $like));

		if ($message['AUTHOR_ID'] > 0 && $message['AUTHOR_ID'] != $userId && $isLike && $chat['ENTITY_TYPE'] != 'LIVECHAT')
		{
			$CCTP = new CTextParser();
			$CCTP->MaxStringLen = 200;
			$CCTP->allow = array("HTML" => "N", "USER" => "N",  "ANCHOR" => "N", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");

			$message['MESSAGE'] = self::PrepareMessageForPush($message);

			$isChat = $chat && strlen($chat['TITLE']) > 0;

			$dot = strlen($message['MESSAGE'])>=200? '...': '';
			$message['MESSAGE'] = substr($message['MESSAGE'], 0, 199).$dot;
			$message['MESSAGE'] = strlen($message['MESSAGE'])>0? $message['MESSAGE']: '-';

			$arMessageFields = array(
				"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
				"TO_USER_ID" => $message['AUTHOR_ID'],
				"FROM_USER_ID" => $userId,
				"NOTIFY_TYPE" => IM_NOTIFY_FROM,
				"NOTIFY_MODULE" => "im",
				"NOTIFY_EVENT" => "like",
				"NOTIFY_TAG" => "RATING|IM|".($isChat? 'G':'P')."|".($isChat? $chat['ID']: $userId)."|".$id,
				"NOTIFY_MESSAGE" => GetMessage($isChat? 'IM_MESSAGE_LIKE': 'IM_MESSAGE_LIKE_PRIVATE', Array(
					'#MESSAGE#' => $message['MESSAGE'],
					'#TITLE#' => $isChat? '[CHAT='.$chat['ID'].']'.$chat['TITLE'].'[/CHAT]': $chat['TITLE']
				)),
				"NOTIFY_MESSAGE_OUT" => GetMessage($isChat? 'IM_MESSAGE_LIKE': 'IM_MESSAGE_LIKE_PRIVATE', Array(
					'#MESSAGE#' => $message['MESSAGE'],
					'#TITLE#' => $chat['TITLE']
				)),
			);
			CIMNotify::Add($arMessageFields);
		}

		$pushUsers = $like;
		$pushUsers[] = $message['AUTHOR_ID'];
		$arPullMessage = Array(
			'id' => $id,
			'chatId' => $chat['ID'],
			'senderId' => $userId,
			'users' => $like
		);

		if ($chat['ENTITY_TYPE'] == 'LINES')
		{
			foreach ($relations as $rel)
			{
				if ($rel["EXTERNAL_AUTH_ID"] == 'imconnector')
				{
					unset($relations[$rel["USER_ID"]]);
				}
			}
		}

		CPullStack::AddByUsers(array_keys($relations), Array(
			'module_id' => 'im',
			'command' => 'messageLike',
			'params' => $arPullMessage
		));

		if ($chat['TYPE'] == IM_MESSAGE_OPEN)
		{
			CPullWatch::AddToStack('IM_PUBLIC_'.$chat['ID'], Array(
				'module_id' => 'im',
				'command' => 'messageLike',
				'params' => $arPullMessage
			));
		}

		return $like;
	}

	private static function CheckFields($arFields)
	{
		$aMsg = array();
		if(!is_set($arFields, "MESSAGE_TYPE") || !in_array($arFields["MESSAGE_TYPE"], Array(IM_MESSAGE_PRIVATE, IM_MESSAGE_CHAT, IM_MESSAGE_OPEN, IM_MESSAGE_SYSTEM)))
		{
			$aMsg[] = array("id"=>"MESSAGE_TYPE", "text"=> GetMessage("IM_ERROR_MESSAGE_TYPE"));
		}
		else
		{
			if(in_array($arFields["MESSAGE_TYPE"], Array(IM_MESSAGE_CHAT, IM_MESSAGE_OPEN)) && !isset($arFields['SYSTEM']) && (intval($arFields["TO_CHAT_ID"]) <= 0 && intval($arFields["FROM_USER_ID"]) <= 0))
				$aMsg[] = array("id"=>"TO_CHAT_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_TO_FROM"));

			if($arFields["MESSAGE_TYPE"] == IM_MESSAGE_PRIVATE && !((intval($arFields["TO_USER_ID"]) > 0 || intval($arFields["TO_CHAT_ID"]) > 0) && intval($arFields["FROM_USER_ID"]) > 0))
				$aMsg[] = array("id"=>"FROM_USER_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_TO_FROM"));

			if (is_set($arFields, "MESSAGE_DATE") && (!$GLOBALS['DB']->IsDate($arFields["MESSAGE_DATE"], false, LANG, "FULL")))
				$aMsg[] = array("id"=>"MESSAGE_DATE", "text"=> GetMessage("IM_ERROR_MESSAGE_DATE"));

			if(in_array($arFields["MESSAGE_TYPE"], Array(IM_MESSAGE_PRIVATE, IM_MESSAGE_SYSTEM)) && !(intval($arFields["TO_USER_ID"]) > 0 || intval($arFields["TO_CHAT_ID"]) > 0))
				$aMsg[] = array("id"=>"TO_USER_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_TO"));

			if(is_set($arFields, "MESSAGE") && strlen(trim($arFields["MESSAGE"])) <= 0)
				$aMsg[] = array("id"=>"MESSAGE", "text"=> GetMessage("IM_ERROR_MESSAGE_TEXT"));

			if($arFields["MESSAGE_TYPE"] == IM_MESSAGE_PRIVATE && is_set($arFields, "AUTHOR_ID") && intval($arFields["AUTHOR_ID"]) <= 0)
				$aMsg[] = array("id"=>"AUTHOR_ID", "text"=> GetMessage("IM_ERROR_MESSAGE_AUTHOR"));

			if(is_set($arFields, "IMPORT_ID") && intval($arFields["IMPORT_ID"]) <= 0)
				$aMsg[] = array("id"=>"IMPORT_ID", "text"=> GetMessage("IM_ERROR_IMPORT_ID"));

			if ($arFields["MESSAGE_TYPE"] == IM_MESSAGE_SYSTEM)
			{
				if(!$arFields['MESSAGE'] || strlen(trim($arFields["MESSAGE"])) <= 0)
					$aMsg[] = array("id"=>"MESSAGE", "text"=> GetMessage("IM_ERROR_MESSAGE_TEXT"));

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

	public static function GetById($ID, $params = Array())
	{
		global $DB;

		$ID = intval($ID);

		$strSql = "
			SELECT
				DISTINCT M.*,
				".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." DATE_CREATE,
				C.TYPE MESSAGE_TYPE,
				C.ENTITY_TYPE CHAT_ENTITY_TYPE,
				C.ENTITY_ID CHAT_ENTITY_ID
			FROM b_im_message M
			LEFT JOIN b_im_chat C ON M.CHAT_ID = C.ID
			WHERE M.ID = ".$ID;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$param = CIMMessageParam::Get($arRes['ID']);
			$arRes['PARAMS'] = $param? $param: Array();
		}
		if ($arRes && $params['WITH_FILES'] == 'Y')
		{
			$arFiles = Array();
			foreach ($arRes['PARAMS']['FILE_ID'] as $fileId)
			{
				$arFiles[$fileId] = $fileId;
			}
			$arRes['FILES'] = CIMDisk::GetFiles($arRes['CHAT_ID'], $arFiles, false);
		}

		return $arRes;
	}

	public static function GetRelationById($ID)
	{
		global $DB;

		$ID = intval($ID);
		$arResult = Array();

		$strSql = "
			SELECT
				R.USER_ID, U.EXTERNAL_AUTH_ID
			FROM b_im_message M
			LEFT JOIN b_im_relation R ON M.CHAT_ID = R.CHAT_ID
			LEFT JOIN b_user U ON U.ID = R.USER_ID
			WHERE M.ID = ".$ID;
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
			$arResult[$arRes['USER_ID']] = $arRes;

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

	public static function CheckEnableOpenChat()
	{
		return COption::GetOptionString('im', 'open_chat_enable');
	}

	public static function CheckNetwork()
	{
		return COption::GetOptionString('bitrix24', 'network', 'N') == 'Y';
	}

	public static function CheckNetwork2()
	{
		if (!CModule::IncludeModule('socialservices'))
			return false;

		$network = new \Bitrix\Socialservices\Network();
		return $network->isEnabled();
	}

	public static function CheckInstallDesktop()
	{
		$LastActivityDate = CUserOptions::GetOption('im', 'DesktopLastActivityDate', -1);
		if (intval($LastActivityDate) >= 0)
			return true;
		else
			return false;
	}

	public static function EnableInVersion($version)
	{
		$version = intval($version);
		$currentVersion = intval(CUserOptions::GetOption('im', 'DesktopVersionApi', 0));

		return $currentVersion >= $version;
	}

	public static function SetDesktopVersion($version)
	{
		global $USER;

		$version = intval($version);
		$userId = intval($USER->GetId());
		if ($userId <= 0)
			return false;

		CUserOptions::SetOption('im', 'DesktopVersionApi', $version, false, $userId);

		return $version;
	}

	public static function GetDesktopVersion()
	{
		$version = CUserOptions::GetOption('im', 'DesktopVersionApi', 0);

		return $version;
	}

	public static function CheckPhoneStatus()
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant') || !\Bitrix\Main\Loader::includeModule('pull'))
			return false;

		$userPermissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		if(!$userPermissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL, \Bitrix\Voximplant\Security\Permissions::ACTION_PERFORM))
			return false;

		return CPullOptions::GetNginxStatus() && (!IsModuleInstalled('extranet') || CModule::IncludeModule('extranet') && CExtranet::IsIntranetUser());
	}

	public static function CanUserCallCrmNumber()
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return false;

		$userPermissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		return $userPermissions->canPerform(
			\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL,
			\Bitrix\Voximplant\Security\Permissions::ACTION_PERFORM,
			\Bitrix\Voximplant\Security\Permissions::PERMISSION_CALL_CRM
		);
	}

	public static function CanUserCallUserNumber()
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return false;

		$userPermissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		return $userPermissions->canPerform(
			\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL,
			\Bitrix\Voximplant\Security\Permissions::ACTION_PERFORM,
			\Bitrix\Voximplant\Security\Permissions::PERMISSION_CALL_USERS
		);
	}

	public static function CanUserCallAnyNumber()
	{
		if(!\Bitrix\Main\Loader::includeModule('voximplant'))
			return false;

		$userPermissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		return $userPermissions->canPerform(
			\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL,
			\Bitrix\Voximplant\Security\Permissions::ACTION_PERFORM,
			\Bitrix\Voximplant\Security\Permissions::PERMISSION_ANY
		);
	}

	public static function CheckDesktopStatusOnline($userId = null)
	{
		global $USER;

		if (is_null($userId))
			$userId = $USER->GetId();

		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		$maxDate = 120;
		if (CModule::IncludeModule('pull') && CPullOptions::GetNginxStatus())
			$maxDate = self::GetSessionLifeTime();

		$LastActivityDate = CUserOptions::GetOption('im', 'DesktopLastActivityDate', 0, $userId);
		if (intval($LastActivityDate)+($maxDate*2)+60 > time())
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

		if (CModule::IncludeModule("pull"))
		{
			CPullStack::AddByUser($userId, Array(
				'module_id' => 'im',
				'command' => 'desktopOnline',
				'params' => Array(),
			));
		}

		return $time;
	}

	public static function SetDesktopStatusOffline($userId = null)
	{
		global $USER;

		if (is_null($userId))
			$userId = $USER->GetId();

		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		CUserOptions::SetOption('im', 'DesktopLastActivityDate', 0, false, $userId);

		if (CModule::IncludeModule("pull"))
		{
			CPullStack::AddByUser($userId, Array(
				'module_id' => 'im',
				'command' => 'desktopOffline',
				'params' => Array(),
			));
		}

		return true;
	}

	public static function GetSettings($userId = false)
	{
		$arSettings = CIMSettings::Get($userId);
		return $arSettings['settings'];
	}

	public static function GetCurrentTab()
	{
		return htmlspecialcharsbx(CUserOptions::GetOption('IM', 'currentTab', 0));
	}

	public static function SetCurrentTab($tab = 0)
	{
		if ($tab == self::GetCurrentTab())
			return false;

		CUserOptions::SetOption('im', 'currentTab', $tab);

		return true;
	}

	public static function GetFormatFilesMessageOut($files)
	{
		if (!is_array($files) || count($files) <= 0)
			return false;

		$messageFiles = '';
		$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));
		foreach ($files as $fileId => $fileData)
		{
			if ($fileData['status'] == 'done')
			{
				$fileElement = $fileData['name'].' ('.CFile::FormatSize($fileData['size']).")\n".
								GetMessage('IM_MESSAGE_FILE_DOWN').' '.$serverName.$fileData['urlDownload']['default']."\n";
				$messageFiles = strlen($messageFiles)>0? $messageFiles."\n".$fileElement: $fileElement;
			}
		}

		return $messageFiles;
	}

	public static function GetSessionLifeTime()
	{
		global $USER;

		$sessTimeout = ini_get("session.gc_maxlifetime");
		if (is_object($USER))
		{
			$arPolicy = $USER->GetSecurityPolicy();
			if($arPolicy["SESSION_TIMEOUT"] > 0)
				$sessTimeout = min($arPolicy["SESSION_TIMEOUT"]*60, $sessTimeout);
		}
		return intval($sessTimeout);
	}

	public static function GetUnreadCounter($userId)
	{
		$count = 0;
		$userId = intval($userId);
		if ($userId <= 0)
			return $count;

		global $DB;

		$strSql ="
			SELECT M.ID, M.NOTIFY_TYPE, M.NOTIFY_TAG
			FROM b_im_message M
			INNER JOIN b_im_relation R1 ON M.ID > R1.LAST_ID AND M.CHAT_ID = R1.CHAT_ID AND R1.USER_ID != M.AUTHOR_ID
			WHERE R1.USER_ID = ".$userId."  AND R1.STATUS < ".IM_STATUS_READ."
		";

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arGroupNotify = Array();
		while ($arRes = $dbRes->Fetch())
		{
			if ($arRes['NOTIFY_TYPE'] == 2 && $arRes['NOTIFY_TAG'] != '')
			{
				if (!isset($arGroupNotify[$arRes['NOTIFY_TAG']]))
				{
					$arGroupNotify[$arRes['NOTIFY_TAG']] = true;
					$count++;
				}
			}
			else
				$count++;
		}

		return $count;
	}

	public static function GetMessageCounter($userId, $arMessages = Array())
	{
		$count = 0;
		if (isset($arMessages['message']))
		{
			foreach ($arMessages['message'] as $value)
				$count += isset($value['counter'])? $value['counter']: 1;
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
			return false;

		$CACHE_MANAGER->Clean("im_csf_".$type.'_'.$userID);
		$CACHE_MANAGER->Read(86400*30, "im_csf_".$type.'_'.$userID);
		$CACHE_MANAGER->Set("im_csf_".$type.'_'.$userID, $value);
	}

	public static function SpeedFileDelete($userID, $type = IM_SPEED_MESSAGE)
	{
		global $CACHE_MANAGER;

		$userID = IntVal($userID);
		if ($userID <= 0 || !in_array($type, Array(IM_SPEED_NOTIFY, IM_SPEED_MESSAGE, IM_SPEED_GROUP)))
			return false;

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
		return $CACHE_MANAGER->Get("im_csf_".$type.'_'.$userID);
	}

	public static function GetTemplateJS($arParams, $arTemplate)
	{
		global $USER;

		$ppStatus = 'false';
		$ppServerStatus = 'false';
		$updateStateInterval = 'auto';
		if (CModule::IncludeModule("pull"))
		{
			$ppStatus = CPullOptions::ModuleEnable()? 'true': 'false';
			$ppServerStatus = CPullOptions::GetNginxStatus()? 'true': 'false';
			$updateStateInterval = CPullOptions::GetNginxStatus()? self::GetSessionLifeTime(): 80;
			if ($updateStateInterval > 100)
			{
				if ($updateStateInterval > 3600)
					$updateStateInterval = 3600;

				if ($arTemplate['DESKTOP'] == 'true')
					$updateStateInterval = intval($updateStateInterval/2)-10;
				else
					$updateStateInterval = $updateStateInterval-60;
			}
		}

		$diskStatus = CIMDisk::Enabled();

		$phoneSipAvailable = false;
		$phoneDeviceActive = false;
		$phoneCanCallUserNumber = false;
		$phoneEnabled = false;

		if ($arTemplate['INIT'] == 'Y')
		{
			$phoneEnabled = self::CheckPhoneStatus();
			if ($phoneEnabled && CModule::IncludeModule('voximplant'))
			{
				$phoneSipAvailable = CVoxImplantConfig::GetModeStatus(CVoxImplantConfig::MODE_SIP);
				$phoneDeviceActive = CVoxImplantUser::GetPhoneActive($USER->GetId());
				$phoneCanCallUserNumber = self::CanUserCallUserNumber();
			}
		}

		$crmPath = Array();
		$businessUsers = false;
		if (CModule::IncludeModule('imopenlines'))
		{
			if (CModule::IncludeModule('crm'))
			{
				$crmPath['LEAD'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_lead_show'),
					array('lead_id' => '#ID#')
				);

				$crmPath['CONTACT'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_contact_show'),
					array('contact_id' => '#ID#')
				);

				$crmPath['COMPANY'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_company_show'),
					array('company_id' => '#ID#')
				);

				$crmPath['DEAL'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_deal_show'),
					array('deal_id' => '#ID#')
				);
			}
			$businessUsers = \Bitrix\ImOpenLines\Common::getLicenseUsersLimit();
		}

		$pathToIm = isset($arTemplate['PATH_TO_IM']) ? $arTemplate['PATH_TO_IM'] : '';
		$pathToCall = isset($arTemplate['PATH_TO_CALL']) ? $arTemplate['PATH_TO_CALL'] : '';
		$pathToFile = isset($arTemplate['PATH_TO_FILE']) ? $arTemplate['PATH_TO_FILE'] : '';

		$userColor = isset($arTemplate['CONTACT_LIST']['users'][$USER->GetID()]['color']) ? $arTemplate['CONTACT_LIST']['users'][$USER->GetID()]['color']: '';

		$sJS = "
			BX.ready(function() {
				BXIM = new BX.IM(BX('bx-notifier-panel'), {
					'init': ".($arTemplate['INIT'] == 'Y'? 'true': 'false').",

					'context': '".$arTemplate["CONTEXT"]."',
					'design': '".$arTemplate["DESIGN"]."',
					'colors': ".(IM\Color::isEnabled()? CUtil::PhpToJSObject(IM\Color::getSafeColorNames()): 'false').",
					'mailCount': ".$arTemplate["MAIL_COUNTER"].",
					'notifyCount': ".$arTemplate["NOTIFY_COUNTER"].",
					'messageCount': ".$arTemplate["MESSAGE_COUNTER"].",
					'counters': ".(empty($arTemplate['COUNTERS'])? '{}': CUtil::PhpToJSObject($arTemplate['COUNTERS'])).",
					'ppStatus': ".$ppStatus.",
					'ppServerStatus': ".$ppServerStatus.",
					'updateStateInterval': '".$updateStateInterval."',
					'openChatEnable': ".(CIMMessenger::CheckEnableOpenChat()? 'true': 'false').",
					'xmppStatus': ".(CIMMessenger::CheckXmppStatusOnline()? 'true': 'false').",
					'bitrixNetwork': ".(CIMMessenger::CheckNetwork()? 'true': 'false').",
					'bitrixNetwork2': ".(CIMMessenger::CheckNetwork2()? 'true': 'false').",
					'bitrix24': ".(IsModuleInstalled('bitrix24')? 'true': 'false').",
					'bitrix24Admin': ".(CModule::IncludeModule('bitrix24') && CBitrix24::IsPortalAdmin($USER->GetId())? 'true': 'false').",
					'bitrix24net': ".(IsModuleInstalled('b24network')? 'true': 'false').",
					'bitrixIntranet': ".(IsModuleInstalled('intranet')? 'true': 'false').",
					'bitrixXmpp': ".(IsModuleInstalled('xmpp')? 'true': 'false').",
					'bitrixMobile': ".(IsModuleInstalled('mobile')? 'true': 'false').",
					'bitrixOpenLines': ".(IsModuleInstalled('imopenlines')? 'true': 'false').",
					'desktop': ".$arTemplate["DESKTOP"].",
					'desktopStatus': ".(CIMMessenger::CheckDesktopStatusOnline()? 'true': 'false').",
					'desktopVersion': ".CIMMessenger::GetDesktopVersion().",
					'desktopLinkOpen': ".$arTemplate["DESKTOP_LINK_OPEN"].",
					'language': '".LANGUAGE_ID."',

					'bot': ".(empty($arTemplate['BOT'])? '{}': CUtil::PhpToJSObject($arTemplate["BOT"])).",
					'command': ".(empty($arTemplate['COMMAND'])? '[]': CUtil::PhpToJSObject($arTemplate["COMMAND"])).",

					'smile': ".CUtil::PhpToJSObject($arTemplate["SMILE"]).",
					'smileSet': ".CUtil::PhpToJSObject($arTemplate["SMILE_SET"]).",
					'settings': ".CUtil::PhpToJSObject($arTemplate['SETTINGS']).",
					'settingsNotifyBlocked': ".(empty($arTemplate['SETTINGS_NOTIFY_BLOCKED'])? '{}': CUtil::PhpToJSObject($arTemplate['SETTINGS_NOTIFY_BLOCKED'])).",

					'notify': ".(empty($arTemplate['NOTIFY']['notify'])? '{}': CUtil::PhpToJSObject($arTemplate['NOTIFY']['notify'])).",
					'unreadNotify' : ".(empty($arTemplate['NOTIFY']['unreadNotify'])? '{}': CUtil::PhpToJSObject($arTemplate['NOTIFY']['unreadNotify'])).",
					'flashNotify' : ".(empty($arTemplate['NOTIFY']['flashNotify'])? '{}': CUtil::PhpToJSObject($arTemplate['NOTIFY']['flashNotify'])).",
					'countNotify' : ".intval($arTemplate['NOTIFY']['countNotify']).",
					'loadNotify' : ".($arTemplate['NOTIFY']['loadNotify']? 'true': 'false').",

					'recent': ".(empty($arTemplate['RECENT']) && $arTemplate['RECENT'] !== false? '[]': CUtil::PhpToJSObject($arTemplate['RECENT'])).",
					'users': ".(empty($arTemplate['CONTACT_LIST']['users'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['users'])).",
					'businessUsers': ".($businessUsers === false? false: empty($businessUsers)? '{}': CUtil::PhpToJSObject($businessUsers)).",
					'groups': ".(empty($arTemplate['CONTACT_LIST']['groups'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['groups'])).",
					'userInGroup': ".(empty($arTemplate['CONTACT_LIST']['userInGroup'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['userInGroup'])).",
					'woGroups': ".(empty($arTemplate['CONTACT_LIST']['woGroups'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['woGroups'])).",
					'woUserInGroup': ".(empty($arTemplate['CONTACT_LIST']['woUserInGroup'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['woUserInGroup'])).",
					'chat': ".(empty($arTemplate['CHAT']['chat'])? '{}': CUtil::PhpToJSObject($arTemplate['CHAT']['chat'])).",
					'userInChat': ".(empty($arTemplate['CHAT']['userInChat'])? '{}': CUtil::PhpToJSObject($arTemplate['CHAT']['userInChat'])).",
					'userChatBlockStatus': ".(empty($arTemplate['CHAT']['userChatBlockStatus'])? '{}': CUtil::PhpToJSObject($arTemplate['CHAT']['userChatBlockStatus'])).",
					'message' : ".(empty($arTemplate['MESSAGE']['message'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['message'])).",
					'files' : ".(empty($arTemplate['MESSAGE']['files'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['files'])).",
					'showMessage' : ".(empty($arTemplate['MESSAGE']['usersMessage'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['usersMessage'])).",
					'unreadMessage' : ".(empty($arTemplate['MESSAGE']['unreadMessage'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['unreadMessage'])).",
					'flashMessage' : ".(empty($arTemplate['MESSAGE']['flashMessage'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['flashMessage'])).",
					'history' : {},
					'openMessenger' : ".(isset($_GET['IM_DIALOG'])? "'".CUtil::JSEscape(htmlspecialcharsbx($_GET['IM_DIALOG']))."'": 'false').",
					'openHistory' : ".(isset($_GET['IM_HISTORY'])? "'".CUtil::JSEscape(htmlspecialcharsbx($_GET['IM_HISTORY']))."'": 'false').",
					'openNotify' : ".(isset($_GET['IM_NOTIFY']) && $_GET['IM_NOTIFY'] == 'Y'? 'true': 'false').",
					'openSettings' : ".(isset($_GET['IM_SETTINGS'])? $_GET['IM_SETTINGS'] == 'Y'? "'true'": "'".CUtil::JSEscape(htmlspecialcharsbx($_GET['IM_SETTINGS']))."'": 'false').",
					'externalRecentList' : '".(isset($arTemplate['EXTERNAL_RECENT_LIST'])?$arTemplate['EXTERNAL_RECENT_LIST']: '')."',

					'currentTab' : '".CUtil::JSEscape($arTemplate['CURRENT_TAB'])."',
					'generalChatId': ".CIMChat::GetGeneralChatId().",
					'canSendMessageGeneralChat': ".(CIMChat::CanSendMessageToGeneralChat($USER->GetID())? 'true': 'false').",
					'userId': ".$USER->GetID().",
					'userEmail': '".CUtil::JSEscape($USER->GetEmail())."',
					'userColor': '".IM\Color::getCode($userColor)."',
					'userGender': '".IM\User::getInstance()->getGender()."',
					'userExtranet': ".(IM\User::getInstance()->isExtranet()? 'true': 'false').",
					'webrtc': {'turnServer' : '".CUtil::JSEscape($arTemplate['TURN_SERVER'])."', 'turnServerFirefox' : '".CUtil::JSEscape($arTemplate['TURN_SERVER_FIREFOX'])."', 'turnServerLogin' : '".CUtil::JSEscape($arTemplate['TURN_SERVER_LOGIN'])."', 'turnServerPassword' : '".CUtil::JSEscape($arTemplate['TURN_SERVER_PASSWORD'])."', 'mobileSupport': false, 'phoneEnabled': ".($phoneEnabled? 'true': 'false').", 'phoneSipAvailable': ".($phoneSipAvailable? 'true': 'false').", 'phoneDeviceActive': '".($phoneDeviceActive? 'Y': 'N')."', 'phoneCanCallUserNumber': '".($phoneCanCallUserNumber? 'Y': 'N')."'},
					'disk': {'enable' : ".($diskStatus? 'true': 'false')."},
					'path' : {'profile' : '".CUtil::JSEscape($arTemplate['PATH_TO_USER_PROFILE'])."', 'profileTemplate' : '".CUtil::JSEscape($arTemplate['PATH_TO_USER_PROFILE_TEMPLATE'])."', 'mail' : '".CUtil::JSEscape($arTemplate['PATH_TO_USER_MAIL'])."', 'im': '".CUtil::JSEscape($pathToIm)."', 'call': '".CUtil::JSEscape($pathToCall)."', 'file': '".CUtil::JSEscape($pathToFile)."', 'crm' : ".CUtil::PhpToJSObject($crmPath)."}
				});
			});
		";

		return $sJS;
	}

	public static function GetMobileTemplateJS($arParams, $arTemplate)
	{
		global $USER;

		$ppStatus = 'false';
		$ppServerStatus = 'false';
		$updateStateInterval = 'auto';
		if (CModule::IncludeModule("pull"))
		{
			$ppStatus = CPullOptions::ModuleEnable()? 'true': 'false';
			$ppServerStatus = CPullOptions::GetNginxStatus()? 'true': 'false';
			$updateStateInterval = CPullOptions::GetNginxStatus()? self::GetSessionLifeTime(): 80;
			if ($updateStateInterval > 100)
			{
				if ($updateStateInterval > 3600)
					$updateStateInterval = 3600;

				$updateStateInterval = $updateStateInterval-60;
			}
		}

		$diskStatus = CIMDisk::Enabled();

		$phoneSipAvailable = false;
		$phoneDeviceActive = false;

		$phoneEnabled = self::CheckPhoneStatus() && CModule::IncludeModule('mobileapp') && \Bitrix\MobileApp\Mobile::getInstance()->isWebRtcSupported();
		if ($phoneEnabled && CModule::IncludeModule('voximplant'))
		{
			$phoneSipAvailable = CVoxImplantConfig::GetModeStatus(CVoxImplantConfig::MODE_SIP);
			$phoneDeviceActive = CVoxImplantUser::GetPhoneActive($USER->GetId());
		}

		$crmPath = Array();
		$businessUsers = false;
		if (CModule::IncludeModule('imopenlines'))
		{
			if (CModule::IncludeModule('crm'))
			{
				$crmPath['LEAD'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_lead_show'),
					array('lead_id' => '#ID#')
				);

				$crmPath['CONTACT'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_contact_show'),
					array('contact_id' => '#ID#')
				);

				$crmPath['COMPANY'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_company_show'),
					array('company_id' => '#ID#')
				);

				$crmPath['DEAL'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_deal_show'),
					array('deal_id' => '#ID#')
				);
			}
			$businessUsers = \Bitrix\ImOpenLines\Common::getLicenseUsersLimit();
		}

		$mobileAction = isset($arTemplate["ACTION"])? $arTemplate["ACTION"]: 'none';
		$mobileCallMethod = isset($arTemplate["CALL_METHOD"])? $arTemplate["CALL_METHOD"]: 'device';

		$userColor = isset($arTemplate['CONTACT_LIST']['users'][$USER->GetID()]['color']) ? $arTemplate['CONTACT_LIST']['users'][$USER->GetID()]['color']: '';

		$sJS = "
			BX.ready(function() {
				BXIM = new BX.ImMobile({
					'mobileAction': '".$mobileAction."',
					'mobileCallMethod': '".$mobileCallMethod."',

					'colors': ".(IM\Color::isEnabled()? CUtil::PhpToJSObject(IM\Color::getSafeColorNames()): 'false').",
					'mailCount': ".intval($arTemplate["MAIL_COUNTER"]).",
					'notifyCount': ".intval($arTemplate["NOTIFY_COUNTER"]).",
					'messageCount': ".intval($arTemplate["MESSAGE_COUNTER"]).",
					'counters': ".(empty($arTemplate['COUNTERS'])? '{}': CUtil::PhpToJSObject($arTemplate['COUNTERS'])).",
					'ppStatus': ".$ppStatus.",
					'ppServerStatus': ".$ppServerStatus.",
					'updateStateInterval': '".$updateStateInterval."',
					'openChatEnable': ".(CIMMessenger::CheckEnableOpenChat()? 'true': 'false').",
					'xmppStatus': ".(CIMMessenger::CheckXmppStatusOnline()? 'true': 'false').",
					'bitrixNetwork': ".(CIMMessenger::CheckNetwork()? 'true': 'false').",
					'bitrixNetwork2': ".(CIMMessenger::CheckNetwork2()? 'true': 'false').",
					'bitrix24': ".(IsModuleInstalled('bitrix24')? 'true': 'false').",
					'bitrix24Admin': ".(CModule::IncludeModule('bitrix24') && CBitrix24::IsPortalAdmin($USER->GetId())? 'true': 'false').",
					'bitrix24net': ".(IsModuleInstalled('b24network')? 'true': 'false').",
					'bitrixIntranet': ".(IsModuleInstalled('intranet')? 'true': 'false').",
					'bitrixXmpp': ".(IsModuleInstalled('xmpp')? 'true': 'false').",
					'bitrixMobile': ".(IsModuleInstalled('mobile')? 'true': 'false').",
					'bitrixOpenLines': ".(IsModuleInstalled('imopenlines')? 'true': 'false').",
					'desktopStatus': ".(CIMMessenger::CheckDesktopStatusOnline()? 'true': 'false').",
					'desktopVersion': ".CIMMessenger::GetDesktopVersion().",
					'language': '".LANGUAGE_ID."',

					'bot': ".(empty($arTemplate['BOT'])? '{}': CUtil::PhpToJSObject($arTemplate["BOT"])).",
					'smile': ".(empty($arTemplate['SMILE'])? '{}': CUtil::PhpToJSObject($arTemplate["SMILE"])).",
					'smileSet': ".(empty($arTemplate['SMILE_SET'])? '{}': CUtil::PhpToJSObject($arTemplate["SMILE_SET"])).",
					'settings': ".(empty($arTemplate['SETTINGS'])? '{}': CUtil::PhpToJSObject($arTemplate['SETTINGS'])).",
					'settingsNotifyBlocked': ".(empty($arTemplate['SETTINGS_NOTIFY_BLOCKED'])? '{}': CUtil::PhpToJSObject($arTemplate['SETTINGS_NOTIFY_BLOCKED'])).",

					'notify': ".(empty($arTemplate['NOTIFY']['notify'])? '{}': CUtil::PhpToJSObject($arTemplate['NOTIFY']['notify'])).",
					'unreadNotify' : ".(empty($arTemplate['NOTIFY']['unreadNotify'])? '{}': CUtil::PhpToJSObject($arTemplate['NOTIFY']['unreadNotify'])).",
					'flashNotify' : ".(empty($arTemplate['NOTIFY']['flashNotify'])? '{}': CUtil::PhpToJSObject($arTemplate['NOTIFY']['flashNotify'])).",
					'countNotify' : ".intval($arTemplate['NOTIFY']['countNotify']).",
					'loadNotify' : ".($arTemplate['NOTIFY']['loadNotify']? 'true': 'false').",

					'recent': ".(empty($arTemplate['RECENT']) && $arTemplate['RECENT'] !== false? '[]': CUtil::PhpToJSObject($arTemplate['RECENT'])).",
					'users': ".(empty($arTemplate['CONTACT_LIST']['users'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['users'])).",
					'businessUsers': ".($businessUsers === false? false: empty($businessUsers)? '{}': CUtil::PhpToJSObject($businessUsers)).",
					'groups': ".(empty($arTemplate['CONTACT_LIST']['groups'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['groups'])).",
					'userInGroup': ".(empty($arTemplate['CONTACT_LIST']['userInGroup'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['userInGroup'])).",
					'woGroups': ".(empty($arTemplate['CONTACT_LIST']['woGroups'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['woGroups'])).",
					'woUserInGroup': ".(empty($arTemplate['CONTACT_LIST']['woUserInGroup'])? '{}': CUtil::PhpToJSObject($arTemplate['CONTACT_LIST']['woUserInGroup'])).",
					'chat': ".(empty($arTemplate['CHAT']['chat'])? '{}': CUtil::PhpToJSObject($arTemplate['CHAT']['chat'])).",
					'userInChat': ".(empty($arTemplate['CHAT']['userInChat'])? '{}': CUtil::PhpToJSObject($arTemplate['CHAT']['userInChat'])).",
					'userChatBlockStatus': ".(empty($arTemplate['CHAT']['userChatBlockStatus'])? '{}': CUtil::PhpToJSObject($arTemplate['CHAT']['userChatBlockStatus'])).",
					'message' : ".(empty($arTemplate['MESSAGE']['message'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['message'])).",
					'files' : ".(empty($arTemplate['MESSAGE']['files'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['files'])).",
					'showMessage' : ".(empty($arTemplate['MESSAGE']['usersMessage'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['usersMessage'])).",
					'unreadMessage' : ".(empty($arTemplate['MESSAGE']['unreadMessage'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['unreadMessage'])).",
					'flashMessage' : ".(empty($arTemplate['MESSAGE']['flashMessage'])? '{}': CUtil::PhpToJSObject($arTemplate['MESSAGE']['flashMessage'])).",
					'history' : {},
					'openMessenger' : ".(isset($_GET['IM_DIALOG'])? "'".CUtil::JSEscape(htmlspecialcharsbx($_GET['IM_DIALOG']))."'": 'false').",
					'openHistory' : ".(isset($_GET['IM_HISTORY'])? "'".CUtil::JSEscape(htmlspecialcharsbx($_GET['IM_HISTORY']))."'": 'false').",
					'openNotify' : ".(isset($_GET['IM_NOTIFY']) && $_GET['IM_NOTIFY'] == 'Y'? 'true': 'false').",
					'openSettings' : ".(isset($_GET['IM_SETTINGS'])? $_GET['IM_SETTINGS'] == 'Y'? "'true'": "'".CUtil::JSEscape(htmlspecialcharsbx($_GET['IM_SETTINGS']))."'": 'false').",

					'currentTab' : '".($arTemplate['CURRENT_TAB']? CUtil::JSEscape($arTemplate['CURRENT_TAB']): 0)."',
					'generalChatId': ".CIMChat::GetGeneralChatId().",
					'canSendMessageGeneralChat': ".(CIMChat::CanSendMessageToGeneralChat($USER->GetID())? 'true': 'false').",
					'userId': ".$USER->GetID().",
					'userEmail': '".CUtil::JSEscape($USER->GetEmail())."',
					'userColor': '".IM\Color::getCode($userColor)."',
					'userGender': '".IM\User::getInstance()->getGender()."',
					'userExtranet': ".(IM\User::getInstance()->isExtranet()? 'true': 'false').",
					'webrtc': {'turnServer' : '".(empty($arTemplate['TURN_SERVER'])? '': CUtil::JSEscape($arTemplate['TURN_SERVER']))."', 'turnServerLogin' : '".(empty($arTemplate['TURN_SERVER_LOGIN'])? '': CUtil::JSEscape($arTemplate['TURN_SERVER_LOGIN']))."', 'turnServerPassword' : '".(empty($arTemplate['TURN_SERVER_PASSWORD'])? '': CUtil::JSEscape($arTemplate['TURN_SERVER_PASSWORD']))."', 'mobileSupport': ".($arTemplate['WEBRTC_MOBILE_SUPPORT']? 'true': 'false').", 'phoneEnabled': ".($phoneEnabled? 'true': 'false').", 'phoneSipAvailable': ".($phoneSipAvailable? 'true': 'false')."},
					'disk': {'enable' : ".($diskStatus? 'true': 'false')."},
					'path' : {'profile' : '".(empty($arTemplate['PATH_TO_USER_PROFILE'])? '': CUtil::JSEscape($arTemplate['PATH_TO_USER_PROFILE']))."', 'profileTemplate' : '".(empty($arTemplate['PATH_TO_USER_PROFILE_TEMPLATE'])? '': CUtil::JSEscape($arTemplate['PATH_TO_USER_PROFILE_TEMPLATE']))."', 'mail' : '".(empty($arTemplate['PATH_TO_USER_MAIL'])? '': CUtil::JSEscape($arTemplate['PATH_TO_USER_MAIL']))."', 'crm' : ".CUtil::PhpToJSObject($crmPath)."}
				});
			});
		";

		return $sJS;
	}

	public static function StartWriting($dialogId, $userId = false)
	{
		$userId = intval($userId);
		if ($userId <= 0)
		{
			global $USER;
			$userId = $USER->GetID();
		}

		if (substr($dialogId, 0, 4) == 'chat')
		{
			$arRelation = CIMChat::GetRelationById(substr($dialogId, 4));
			if (!isset($arRelation[$userId]))
			{
				return false;
			}
		}
		
		foreach(\Bitrix\Main\EventManager::getInstance()->findEventHandlers("im", "OnStartWriting") as $event)
		{
			$result = ExecuteModuleEventEx($event, array($userId, $dialogId));
		}

		if ($userId > 0 && strlen($dialogId) > 0 && CModule::IncludeModule("pull"))
		{
			CPushManager::DeleteFromQueueBySubTag($userId, 'IM_MESS');

			if (intval($dialogId) > 0)
			{
				CPullStack::AddByUser($dialogId, Array(
					'module_id' => 'im',
					'command' => 'startWriting',
					'expiry' => 60,
					'params' => Array(
						'senderId' => $userId,
						'dialogId' => $dialogId
					),
				));
			}
			elseif (substr($dialogId, 0, 4) == 'chat')
			{
				unset($arRelation[$userId]);

				$pullMessage = Array(
					'module_id' => 'im',
					'command' => 'startWriting',
					'expiry' => 60,
					'params' => Array(
						'senderId' => $userId,
						'dialogId' => $dialogId
					),
				);

				$orm = \Bitrix\Im\Model\ChatTable::getById(substr($dialogId, 4));
				$chat = $orm->fetch();

				if ($chat['ENTITY_TYPE'] == 'LINES')
				{
					foreach ($arRelation as $rel)
					{
						if ($rel["EXTERNAL_AUTH_ID"] == 'imconnector')
						{
							unset($arRelation[$rel["USER_ID"]]);
						}
					}
				}
				CPullStack::AddByUsers(array_keys($arRelation), $pullMessage);

				if ($chat['TYPE'] == IM_MESSAGE_OPEN)
				{
					CPullWatch::AddToStack('IM_PUBLIC_'.$chat['ID'], $pullMessage);
				}
			}
			return true;
		}
		return false;
	}

	public static function PrepareSmiles()
	{
		return CSmileGallery::getSmilesWithSets();
	}

	public static function SendMention($params)
	{
		$params['CHAT_ID'] = intval($params['CHAT_ID']);
		if (!isset($params['MESSAGE']) || $params['CHAT_ID'] <= 0)
		{
			return false;
		}

		if (!isset($params['CHAT_TITLE']) || !isset($params['CHAT_TYPE']))
		{
			$orm = \Bitrix\Im\Model\ChatTable::getById($params['CHAT_ID']);
			$chat = $orm->fetch();
			if (!$chat)
			{
				return false;
			}

			$params['CHAT_TITLE'] = $chat['TITLE'];
			$params['CHAT_TYPE'] = trim($chat['TYPE']);
		}

		if (!in_array($params['CHAT_TYPE'], Array(IM_MESSAGE_OPEN, IM_MESSAGE_CHAT)))
		{
			return false;
		}

		if (!isset($params['CHAT_RELATION']))
		{
			$params['CHAT_RELATION'] = CIMChat::GetRelationById($params['CHAT_ID']);
		}

		$forUsers = Array();
		if (preg_match_all("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", $params['MESSAGE'], $matches))
		{
			if ($params['CHAT_TYPE'] == IM_MESSAGE_OPEN)
			{
				foreach($matches[1] as $userId)
				{
					if (!isset($params['CHAT_RELATION'][$userId]))
					{
						$forUsers[$userId] = $userId;
					}
					else if (
						$params['CHAT_RELATION'][$userId]['NOTIFY_BLOCK'] == 'Y' ||
						!CIMSettings::GetNotifyAccess($params['CHAT_RELATION'][$userId]['USER_ID'], 'im', 'openChat', CIMSettings::CLIENT_PUSH)
					)
					{
						$forUsers[$userId] = $userId;
					}
				}
			}
			else
			{
				foreach($matches[1] as $userId)
				{
					if (
						isset($params['CHAT_RELATION'][$userId]) &&
						($params['CHAT_RELATION'][$userId]['NOTIFY_BLOCK'] == 'Y' || !CIMSettings::GetNotifyAccess($params['CHAT_RELATION'][$userId]['USER_ID'], 'im', 'chat', CIMSettings::CLIENT_PUSH))
					)
					{
						$forUsers[$userId] = $userId;
					}
				}
			}
		}

		$userName = \Bitrix\Im\User::getInstance($params['FROM_USER_ID'])->getFullName();
		$userGender = \Bitrix\Im\User::getInstance($params['FROM_USER_ID'])->getGender();
		if ($userName)
		{
			$chatTitle = substr(htmlspecialcharsback($params['CHAT_TITLE']), 0, 32);
			$notifyMail = GetMessage('IM_MESSAGE_MENTION_'.($userGender=='F'?'F':'M'), Array('#TITLE#' => $chatTitle));
			$notifyText = GetMessage('IM_MESSAGE_MENTION_'.($userGender=='F'?'F':'M'), Array('#TITLE#' => '[CHAT='.$params['CHAT_ID'].']'.$chatTitle.'[/CHAT]'));
			$pushText = GetMessage('IM_MESSAGE_MENTION_PUSH_'.($userGender=='F'?'F':'M'), Array('#USER#' => $userName, '#TITLE#' => $chatTitle)).': '.self::PrepareMessageForPush(Array('MESSAGE' => $params['MESSAGE'], 'FILES' => $params['FILES']));
		}
		if (strlen($notifyText) > 0)
		{
			foreach ($forUsers as $userId)
			{
				$arMessageFields = array(
					"TO_USER_ID" => $userId,
					"FROM_USER_ID" => $params['FROM_USER_ID'],
					"NOTIFY_TYPE" => IM_NOTIFY_FROM,
					"NOTIFY_MODULE" => "im",
					"NOTIFY_EVENT" => "mention",
					"NOTIFY_TAG" => 'IM|MENTION|'.$params['CHAT_ID'],
					"NOTIFY_SUB_TAG" => "IM_MESS",
					"NOTIFY_MESSAGE" => $notifyText,
					"NOTIFY_MESSAGE_OUT" => $notifyMail,
					"PUSH_MESSAGE" => $pushText,
					"PUSH_PARAMS" => Array(
						'TAG' => 'IM_CHAT_'.$params['CHAT_ID'],
						'CATEGORY' => 'ANSWER',
						'URL' => SITE_DIR.'mobile/ajax.php?mobile_action=im_answer',
						'PARAMS' => Array(
							'RECIPIENT_ID' => 'chat'.$params['CHAT_ID']
						)
					),
					"PUSH_APP_ID" => 'Bitrix24'
				);
				CIMNotify::Add($arMessageFields);
			}
		}

		return true;
	}

	public static function PrepareParamsForPull($params)
	{
		foreach ($params as $key => $value)
		{
			if ($key == 'ATTACH')
			{
				if (is_object($value) && $value instanceof CIMMessageParamAttach)
				{
					$params[$key] = CIMMessageParamAttach::PrepareAttach($value->GetArray());
				}
				else
				{
					foreach ($value as $key2 => $value2)
					{
						if (is_object($value2) && $value2 instanceof CIMMessageParamAttach)
						{
							$params[$key][$key2] = CIMMessageParamAttach::PrepareAttach($value2->GetArray());
						}
					}
				}
			}
			elseif ($key == 'KEYBOARD')
			{
				if (is_object($value) && $value instanceof \Bitrix\Im\Bot\Keyboard)
				{
					$params[$key] = $value->getArray();
				}
			}
			elseif ($key == 'AVATAR')
			{
				$arFileTmp = \CFile::ResizeImageGet(
					$value,
					array('width' => 58, 'height' => 58),
					BX_RESIZE_IMAGE_EXACT,
					false,
					false,
					true
				);
				$params[$key] = empty($arFileTmp['src'])? '': $arFileTmp['src'];
			}
		}
		return $params;
	}

	public static function PreparePushForChat($params)
	{
		$params['CHAT_ID'] = intval($params['CHAT_ID']);
		if ($params['CHAT_ID'] <= 0)
		{
			return false;
		}

		if (!isset($params['CHAT_TITLE']))
		{
			$orm = \Bitrix\Im\Model\ChatTable::getById($params['CHAT_ID']);
			$chat = $orm->fetch();
			if (!$chat)
			{
				return false;
			}
			$params['CHAT_TITLE'] = $chat['TITLE'];
		}

		$params['CHAT_TITLE'] = substr(htmlspecialcharsback($params['CHAT_TITLE']), 0, 32);

		if ($params['SYSTEM'] == 'Y')
		{
			$pushText = $params['CHAT_TITLE'].': '.$params['MESSAGE'];
		}
		else
		{
			$userName = \Bitrix\Im\User::getInstance($params['FROM_USER_ID'])->getFullName();
			if (!$userName)
				return false;

			$pushText = GetMessage('IM_PUSH_GROUP_TITLE', Array('#USER#' => $userName, '#GROUP#' => $params['CHAT_TITLE'])).': '.$params['MESSAGE'];
		}

		$pushText = self::PrepareMessageForPush(Array(
			'MESSAGE' => $pushText,
			'FILES' => $params['FILES'],
			'ATTACH' => empty($params['MESSAGE']) && $params['ATTACH'],
		));

		if (strlen($pushText) <= 0)
			return false;

		$result = Array();
		$result['push']['params'] = Array(
			'TAG' => 'IM_CHAT_'.$params['CHAT_ID'],
			'CATEGORY' => 'ANSWER',
			'URL' => SITE_DIR.'mobile/ajax.php?mobile_action=im_answer',
			'PARAMS' => Array(
				'RECIPIENT_ID' => 'chat'.$params['CHAT_ID']
			)
		);
		$result['push']['tag'] = 'IM_CHAT_'.$params['CHAT_ID'];
		$result['push']['sub_tag'] = 'IM_MESS';
		$result['push']['app_id'] = 'Bitrix24';
		$result['push']['message'] = $pushText;

		return $result;
	}

	public static function PreparePushForPrivate($params)
	{
		if ($params['SYSTEM'] == 'Y')
		{
			$pushText = $params['MESSAGE'];
		}
		else
		{
			$userName = \Bitrix\Im\User::getInstance($params['FROM_USER_ID'])->getFullName();
			if (!$userName)
				return false;

			$pushText = $userName.': '.$params['MESSAGE'];
		}

		$pushText = self::PrepareMessageForPush(Array(
			'MESSAGE' => $pushText,
			'FILES' => $params['FILES'],
			'ATTACH' => empty($params['MESSAGE']) && $params['ATTACH'],
		));

		if (!$pushText)
			return false;

		$result = Array();
		$result['push']['params'] = Array(
			'TAG' => 'IM_MESS_'.$params['FROM_USER_ID'],
			'CATEGORY' => 'ANSWER',
			'URL' => SITE_DIR.'mobile/ajax.php?mobile_action=im_answer',
			'PARAMS' => Array(
				'RECIPIENT_ID' => $params['FROM_USER_ID']
			)
		);
		$result['push']['tag'] = 'IM_MESS_'.$params['FROM_USER_ID'];
		$result['push']['sub_tag'] = 'IM_MESS';
		$result['push']['app_id'] = 'Bitrix24';
		$result['push']['message'] = $pushText;

		return $result;
	}

	public static function PrepareMessageForPush($params)
	{
		if (!isset($params['MESSAGE']))
		{
			$params['MESSAGE'] = '';
		}
		$params['MESSAGE'] = trim($params['MESSAGE']);

		$pushFiles = '';
		if (isset($params['FILES']) && count($params['FILES']) > 0)
		{
			foreach ($params['FILES'] as $file)
			{
				$pushFiles .= " [".GetMessage('IM_MESSAGE_FILE').": ".$file['name']."]";
			}
			$params['MESSAGE'] .= $pushFiles;
		}

		$params['MESSAGE'] = preg_replace("/\[s\].*?\[\/s\]/i", "-", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[[bui]\](.*?)\[\/[bui]\]/i", "$1", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\\[url\\](.*?)\\[\\/url\\]/i".BX_UTF_PCRE_MODIFIER, "$1", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\\[url\\s*=\\s*((?:[^\\[\\]]++|\\[ (?: (?>[^\\[\\]]+) | (?:\\1) )* \\])+)\\s*\\](.*?)\\[\\/url\\]/ixs".BX_UTF_PCRE_MODIFIER, "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[CHAT=([0-9]{1,})\](.*?)\[\/CHAT\]/i", "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[SEND(?:=(.+?))?\](.+?)?\[\/SEND\]/i", "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[PUT(?:=(.+?))?\](.+?)?\[\/PUT\]/i", "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[CALL(?:=(.+?))?\](.+?)?\[\/CALL\]/i", "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/i", "$2", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace("/\[ATTACH=([0-9]{1,})\]/i", " [".GetMessage('IM_MESSAGE_ATTACH')."] ", $params['MESSAGE']);
		$params['MESSAGE'] = preg_replace('#\-{54}.+?\-{54}#s', " [".GetMessage('IM_QUOTE')."] ", str_replace(array("#BR#"), Array(" "), $params['MESSAGE']));

		if (!$pushFiles && $params['ATTACH'])
		{
			$params['MESSAGE'] .= " [".GetMessage('IM_MESSAGE_ATTACH')."]";
		}

		return $params['MESSAGE'];
	}

	public static function GetBadge($userId)
	{
		return 0;
		$count = 0;
		$count += CUserCounter::GetValue($userId, 'im_notify_v2', '**');
		$count += CUserCounter::GetValue($userId, 'im_chat_v2', '**');
		$count += CUserCounter::GetValue($userId, 'im_message_v2', '**');

		return $count;
	}

	public static function SendBadges($userID)
	{
		return false;

		if (!(CModule::IncludeModule('pull') && CPullOptions::GetPushStatus()))
			return false;

		$arPush = Array();
		if (!is_array($userID))
			$userID = Array(intval($userID));

		global $DB;
		if (empty($userID))
			return false;

		foreach ($userID as $key => $userId)
			$userID[$key] = intval($userId);

		$CPushManager = new CPushManager();

		$sql = "SELECT SUM(CNT) CNT, USER_ID
				FROM b_user_counter
				WHERE USER_ID IN (".implode(',', $userID).") and SITE_ID = '**' and CODE IN ('im_notify_v2', 'im_message_v2', 'im_chat_v2')
				GROUP BY USER_ID";
		$res = $DB->Query($sql);
		while($row = $res->Fetch())
		{
			$CPushManager->AddQueue(Array('USER_ID' => $row['USER_ID'], 'BADGE' => $row['CNT'], 'SEND_IMMEDIATELY' => 'Y'));
		}

		return true;
	}

	public static function GetSpeedCounters()
	{
		$CIMNotify = new CIMNotify();
		$arNotify = $CIMNotify->GetUnreadNotify(Array('USE_TIME_ZONE' => 'N'));
		$arResult['NOTIFY']  = count($arNotify['notify']);

		$CIMMessage = new CIMMessage();
		$arMessage = $CIMMessage->GetUnreadMessage(Array(
			'LOAD_DEPARTMENT' => 'N',
			'FILE_LOAD' => 'N',
			'ORDER' => 'ASC',
		));
		$arResult['MESSAGE'] = count($arMessage['message']);

		$CIMChat = new CIMChat();
		$arMessage = $CIMChat->GetUnreadMessage(Array(
			'LOAD_DEPARTMENT' => 'N',
			'ORDER' => 'ASC',
			'FILE_LOAD' => 'N',
			'USER_LOAD' => 'N',
		));
		$arResult['MESSAGE'] += count($arMessage['message']);

		return $arResult;
	}

	public static function InitCounters($userId, $check = true)
	{
		return false;

		if (intval($userId) <= 0)
			return false;

		$send = false;
		if (!$check || !CUserOptions::GetOption('im', 'initCounterNotify2', false, $userId))
		{
			CIMNotify::SetUnreadCounter($userId);
			CUserOptions::SetOption('im', 'initCounterNotify2', true, $userId);
			$send = true;
		}
		if (!$check || !CUserOptions::GetOption('im', 'initCounterChat2', false, $userId))
		{
			CIMChat::SetUnreadCounter($userId);
			CUserOptions::SetOption('im', 'initCounterChat2', true, $userId);
			$send = true;
		}
		if (!$check || !CUserOptions::GetOption('im', 'initCounterMessage2', false, $userId))
		{
			CIMMessage::SetUnreadCounter($userId);
			CUserOptions::SetOption('im', 'initCounterMessage2', true, $userId);
			$send = true;
		}
		if ($send)
			CIMMessenger::SendBadges($userId);

		return true;
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