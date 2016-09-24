<?
if(!CModule::IncludeModule('rest'))
	return;

class CIMRestService extends IRestService
{
	public static function OnRestServiceBuildDescription()
	{
		return array(
			'im' => array(
				'im.chat.add' => array('CIMRestService', 'chatCreate'),
				'im.chat.setOwner' => array('CIMRestService', 'chatSetOwner'),
				'im.chat.updateColor' => array('CIMRestService', 'chatUpdateColor'),
				'im.chat.updateTitle' => array('CIMRestService', 'chatUpdateTitle'),
				'im.chat.updateAvatar' => array('CIMRestService', 'chatUpdateAvatar'),
				'im.chat.leave' => array('CIMRestService', 'chatUserDelete'),
				'im.chat.user.add' => array('CIMRestService', 'chatUserAdd'),
				'im.chat.user.delete' => array('CIMRestService', 'chatUserDelete'),
				'im.chat.user.list' => array('CIMRestService', 'chatUserList'),
				'im.chat.sendTyping' => array('CIMRestService', 'chatSendTyping'),

				'im.message.add' => array('CIMRestService', 'messageAdd'),
				'im.message.delete' => array('CIMRestService', 'messageDelete'),
				'im.message.update' => array('CIMRestService', 'messageUpdate'),
				'im.message.like' => array('CIMRestService', 'messageLike'),

				'im.notify' => array('CIMRestService', 'notifyAdd'),
				'im.notify.personal.add' => array('CIMRestService', 'notifyAdd'),
				'im.notify.system.add' => array('CIMRestService', 'notifyAdd'),
				'im.notify.delete' => array('CIMRestService', 'notifyDelete'),
			),
			'imbot' => Array(
				'imbot.register' => array('CIMRestService', 'botRegister'),
				'imbot.unregister' => array('CIMRestService', 'botUnRegister'),
				'imbot.update' => array('CIMRestService', 'botUpdate'),

				'imbot.chat.updateColor' => array('CIMRestService', 'chatUpdateColor'),
				'imbot.chat.updateTitle' => array('CIMRestService', 'chatUpdateTitle'),
				'imbot.chat.updateAvatar' => array('CIMRestService', 'chatUpdateAvatar'),
				'imbot.chat.leave' => array('CIMRestService', 'botLeave'),
				'imbot.chat.user.add' => array('CIMRestService', 'chatUserAdd'),
				'imbot.chat.user.delete' => array('CIMRestService', 'chatUserDelete'),
				'imbot.chat.user.list' => array('CIMRestService', 'chatUserList'),
				'imbot.chat.sendTyping' => array('CIMRestService', 'botSendTyping'),

				'imbot.bot.list' => array('CIMRestService', 'botList'),

				'imbot.message.add' => array('CIMRestService', 'botMessageAdd'),
				'imbot.message.delete' => array('CIMRestService', 'botMessageDelete'),
				'imbot.message.update' => array('CIMRestService', 'botMessageUpdate'),
				'imbot.message.like' => array('CIMRestService', 'botMessageLike'),

				'imbot.sendTyping' => array('CIMRestService', 'botSendTyping'),

				'imbot.command.register' => array('CIMRestService', 'commandRegister'),
				'imbot.command.unregister' => array('CIMRestService', 'commandUnRegister'),
				'imbot.command.update' => array('CIMRestService', 'commandUpdate'),
				'imbot.command.answer' => array('CIMRestService', 'commandAnswer'),

				CRestUtil::EVENTS => array(
					'OnImBotMessageAdd' => array('im', 'onImBotMessageAdd', array(__CLASS__, 'onBotMessageAdd'), array("category" => \Bitrix\Rest\Sqs::CATEGORY_BOT, "sendRefreshToken" => true)),
					'OnImBotJoinChat' => array('im', 'onImBotJoinChat', array(__CLASS__, 'onBotJoinChat'), array("category" => \Bitrix\Rest\Sqs::CATEGORY_BOT)),
					'OnImBotDelete' => array('im', 'onImBotDelete', array(__CLASS__, 'onBotDelete'), array("category" => \Bitrix\Rest\Sqs::CATEGORY_BOT)),
					'OnImCommandAdd' => array('im', 'onImCommandAdd', array(__CLASS__, 'onCommandAdd'), array("category" => \Bitrix\Rest\Sqs::CATEGORY_BOT, "sendRefreshToken" => true)),
				),
			)
		);
	}

	public static function OnRestAppDelete($arParams)
	{
		if(!\Bitrix\Main\Loader::includeModule('rest'))
		{
			return;
		}
		$result = \Bitrix\Rest\AppTable::getList(array('filter' =>array('=ID' => $arParams['APP_ID'])));
		if ($result = $result->fetch())
		{
			$bots = \Bitrix\Im\Bot::getListCache();
			foreach ($bots as $bot)
			{
				if ($bot['APP_ID'] == $result['CLIENT_ID'])
				{
					\Bitrix\Im\Bot::unRegister(Array('BOT_ID' => $bot['BOT_ID']));
				}
			}
		}
	}

	/* ChatAPI */

	public static function chatCreate($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		if (!is_array($arParams['USERS']) || empty($arParams['USERS']))
		{
			throw new Bitrix\Rest\RestException("Please select users before creating a new chat", "USERS_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (isset($arParams['AVATAR']))
		{
			$arParams['AVATAR'] = CRestUtil::saveFile($arParams['AVATAR']);
			if (!$arParams['AVATAR'] || strpos($arParams['AVATAR']['type'], "image/") !== 0)
			{
				$arParams['AVATAR'] = 0;
			}
			else
			{
				$arParams['AVATAR'] = CFile::saveFile($arParams['AVATAR'], 'im');
			}
		}
		else
		{
			$arParams['AVATAR'] = 0;
		}


		$add = Array(
			'TYPE' => $arParams['TYPE'] == 'OPEN'? IM_MESSAGE_OPEN: IM_MESSAGE_CHAT,
			'USERS' => $arParams['USERS'],
		);
		if ($arParams['AVATAR'] > 0)
		{
			$add['AVATAR_ID'] = $arParams['AVATAR'];
		}
		if (isset($arParams['COLOR']))
		{
			$add['COLOR'] = $arParams['COLOR'];
		}
		if (isset($arParams['MESSAGE']))
		{
			$add['MESSAGE'] = $arParams['MESSAGE'];
		}
		if (isset($arParams['TITLE']))
		{
			$add['TITLE'] = $arParams['TITLE'];
		}
		if (isset($arParams['DESCRIPTION']))
		{
			$add['DESCRIPTION'] = $arParams['DESCRIPTION'];
		}

		$CIMChat = new CIMChat();
		$chatId = $CIMChat->Add($add);
		if (!$chatId)
		{
			throw new Bitrix\Rest\RestException("Chat can't be created", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return $chatId;
	}

	public static function chatListChat($arParams, $n, CRestServer $server)
	{
		global $USER;

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		throw new Bitrix\Rest\RestException("Method isn't implemented yet", "NOT_IMPLEMENTED", CRestServer::STATUS_NOT_FOUND);
	}

	public static function chatSetOwner($arParams, $n, CRestServer $server)
	{
		global $USER;

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);
		$arParams['USER_ID'] = intval($arParams['USER_ID']);

		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		if ($arParams['USER_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("User ID can't be empty", "USER_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (CIMChat::GetGeneralChatId() == $arParams['CHAT_ID'])
		{
			throw new Bitrix\Rest\RestException("Action unavailable", "ACCESS_ERROR", CRestServer::STATUS_FORBIDDEN);
		}

		$chat = new CIMChat();
		$result = $chat->SetOwner($arParams['CHAT_ID'], $arParams['USER_ID']);
		if (!$result)
		{
			throw new Bitrix\Rest\RestException("Change owner can only owner or user isn't member in chat", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function chatUpdateColor($arParams, $n, CRestServer $server)
	{
		global $USER;

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);

		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		$userId = $USER->GetId();
		if (CIMChat::GetGeneralChatId() == $arParams['CHAT_ID'] && !CIMChat::CanSendMessageToGeneralChat($userId))
		{
			throw new Bitrix\Rest\RestException("Action unavailable", "ACCESS_ERROR", CRestServer::STATUS_FORBIDDEN);
		}

		if (!Bitrix\Im\Color::isSafeColor($arParams['COLOR']))
		{
			throw new Bitrix\Rest\RestException("This color currently unavailable", "WRONG_COLOR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$chat = new CIMChat();
		$result = $chat->SetColor($arParams['CHAT_ID'], $arParams['COLOR']);

		if (!$result)
		{
			throw new Bitrix\Rest\RestException("This color currently set or chat isn't exists", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function chatUpdateTitle($arParams, $n, CRestServer $server)
	{
		global $USER;

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);
		$arParams['TITLE'] = trim($arParams['TITLE']);

		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}
		if (empty($arParams['TITLE']))
		{
			throw new Bitrix\Rest\RestException("Title can't be empty", "TITLE_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		$userId = $USER->GetId();
		if (CIMChat::GetGeneralChatId() == $arParams['CHAT_ID'] && !CIMChat::CanSendMessageToGeneralChat($userId))
		{
			throw new Bitrix\Rest\RestException("Action unavailable", "ACCESS_ERROR", CRestServer::STATUS_FORBIDDEN);
		}


		$chat = new CIMChat();
		$result = $chat->Rename($arParams['CHAT_ID'], $arParams['TITLE']);

		if (!$result)
		{
			throw new Bitrix\Rest\RestException("This title currently set or chat isn't exists", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function chatUpdateAvatar($arParams, $n, CRestServer $server)
	{
		global $USER;

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);

		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		$userId = $USER->GetId();
		if (CIMChat::GetGeneralChatId() == $arParams['CHAT_ID'] && !CIMChat::CanSendMessageToGeneralChat($userId))
		{
			throw new Bitrix\Rest\RestException("Action unavailable", "ACCESS_ERROR", CRestServer::STATUS_FORBIDDEN);
		}

		$arParams['AVATAR'] = CRestUtil::saveFile($arParams['AVATAR']);
		if (!$arParams['AVATAR'] || strpos($arParams['AVATAR']['type'], "image/") !== 0)
		{
			throw new Bitrix\Rest\RestException("Avatar incorrect", "AVATAR_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$arParams['AVATAR'] = CFile::saveFile($arParams['AVATAR'], 'im');

		$result = CIMDisk::UpdateAvatarId($arParams['CHAT_ID'], $arParams['AVATAR']);
		if (!$result)
		{
			throw new Bitrix\Rest\RestException("Chat isn't exists", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function chatUserAdd($arParams, $n, CRestServer $server)
	{
		global $USER;

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);

		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (CIMChat::GetGeneralChatId() == $arParams['CHAT_ID'])
		{
			throw new Bitrix\Rest\RestException("Action unavailable", "ACCESS_ERROR", CRestServer::STATUS_FORBIDDEN);
		}

		$CIMChat = new CIMChat();
		$result = $CIMChat->AddUser($arParams['CHAT_ID'], $arParams['USERS'], $arParams['HIDE_HISTORY'] != "N");
		if (!$result)
		{
			throw new Bitrix\Rest\RestException("You don't have access or user already member in chat", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function chatUserDelete($arParams, $n, CRestServer $server)
	{
		global $USER;

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);
		$arParams['USER_ID'] = intval($arParams['USER_ID']);

		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (CIMChat::GetGeneralChatId() == $arParams['CHAT_ID'])
		{
			throw new Bitrix\Rest\RestException("Action unavailable", "ACCESS_ERROR", CRestServer::STATUS_FORBIDDEN);
		}

		if ($server->getMethod() == "im.chat.user.delete" && $arParams['USER_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("User ID can't be empty", "USER_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		$CIMChat = new CIMChat();
		$result = $CIMChat->DeleteUser($arParams['CHAT_ID'], $arParams['USER_ID'] > 0? $arParams['USER_ID']: $USER->GetID());
		if (!$result)
		{
			throw new Bitrix\Rest\RestException("You don't have access or user isn't member in chat", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function chatUserList($arParams, $n, CRestServer $server)
	{
		global $USER;

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);

		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (CIMChat::GetGeneralChatId() == $arParams['CHAT_ID'])
		{
			throw new Bitrix\Rest\RestException("Action unavailable", "ACCESS_ERROR", CRestServer::STATUS_FORBIDDEN);
		}

		$arChat = CIMChat::GetChatData(array(
			'ID' => $arParams['CHAT_ID'],
			'USE_CACHE' => 'Y',
			'USER_ID' => $USER->GetId()
		));

		return isset($arChat['userInChat'][$arParams['CHAT_ID']])? $arChat['userInChat'][$arParams['CHAT_ID']]: Array();
	}

	public static function chatSendTyping($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);

		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = CIMMessenger::StartWriting('chat'.$arParams['CHAT_ID']);
		if (!$result)
		{
			throw new Bitrix\Rest\RestException("Action unavailable", "ACCESS_ERROR", CRestServer::STATUS_FORBIDDEN);
		}

		return true;
	}

	public static function botLeave($arParams, $n, CRestServer $server)
	{
		global $USER;

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);

		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (CIMChat::GetGeneralChatId() == $arParams['CHAT_ID'])
		{
			throw new Bitrix\Rest\RestException("Action unavailable", "ACCESS_ERROR", CRestServer::STATUS_FORBIDDEN);
		}

		$bots = \Bitrix\Im\Bot::getListCache();
		$botFound = false;
		$botId = 0;
		foreach ($bots as $bot)
		{
			if ($bot['APP_ID'] == $server->getAppId())
			{
				$botFound = true;
				$botId = $bot['BOT_ID'];
				break;
			}
		}
		if (!$botFound)
		{
			throw new \Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$CIMChat = new CIMChat();
		$result = $CIMChat->DeleteUser($arParams['CHAT_ID'], $botId);
		if (!$result)
		{
			throw new Bitrix\Rest\RestException("You don't have access or user isn't member in chat", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function botList($arParams, $n, CRestServer $server)
	{
		$result = Array();
		$list = \Bitrix\Im\Bot::getListCache();
		foreach ($list as $botId => $botData)
		{
			if ($botData['TYPE'] == \Bitrix\Im\Bot::TYPE_NETWORK)
				continue;

			$result[$botId] = Array(
				'ID' => $botId,
				'NAME' => \Bitrix\Im\User::getInstance($botId)->getFullName(),
				'CODE' => $botData['CODE'],
				'OPENLINE' => $botData['OPENLINE'],
			);
		}

		return $result;
	}

	public static function messageAdd($arParams, $n, CRestServer $server)
	{
		global $USER;

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['MESSAGE'] = trim($arParams['MESSAGE']);
		if (strlen($arParams['MESSAGE']) <= 0)
		{
			throw new Bitrix\Rest\RestException("Message can't be empty", "MESSAGE_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		$arParams['FROM_USER_ID'] = $USER->GetId();
		if (isset($arParams['USER_ID']))
		{
			$arParams['USER_ID'] = intval($arParams['USER_ID']);
			if ($arParams['USER_ID'] <= 0)
			{
				throw new Bitrix\Rest\RestException("User ID can't be empty", "USER_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
			}

			if (!Bitrix\Im\User::getInstance($arParams['USER_ID'])->isExists())
			{
				throw new Bitrix\Rest\RestException("User not found", "USER_NOT_FOUND", CRestServer::STATUS_WRONG_REQUEST);
			}

			$arMessageFields = Array(
				"MESSAGE_TYPE" => IM_MESSAGE_PRIVATE,
				"FROM_USER_ID" => $arParams['FROM_USER_ID'],
				"TO_USER_ID" => $arParams['USER_ID'],
				"MESSAGE" 	 => $arParams['MESSAGE'],
			);
		}
		else if (isset($arParams['CHAT_ID']))
		{
			$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);
			if ($arParams['CHAT_ID'] <= 0)
			{
				throw new Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
			}
			if (CIMChat::GetGeneralChatId() == $arParams['CHAT_ID'] && !CIMChat::CanSendMessageToGeneralChat($arParams['FROM_USER_ID']))
			{
				throw new Bitrix\Rest\RestException("Action unavailable", "ACCESS_ERROR", CRestServer::STATUS_FORBIDDEN);
			}

			if (isset($arParams['SYSTEM']) && $arParams['SYSTEM'] == 'Y')
			{
				$result = \CBitrix24App::getList(array(), array('APP_ID' => $server->getAppId()));
				$result = $result->fetch();
				$moduleName = isset($result['APP_NAME'])? $result['APP_NAME']: $result['CODE'];

				$arParams['MESSAGE'] = "[b]".$moduleName."[/b]\n".$arParams['MESSAGE'];
			}
			else
			{
				$arRelation = CIMChat::GetRelationById($arParams['CHAT_ID']);
				if (!isset($arRelation[$arParams['FROM_USER_ID']]))
				{
					throw new Bitrix\Rest\RestException("You don't have access or user isn't member in chat", "ACCESS_ERROR", CRestServer::STATUS_WRONG_REQUEST);
				}
			}

			$arMessageFields = Array(
				"MESSAGE_TYPE" => IM_MESSAGE_CHAT,
				"FROM_USER_ID" => $arParams['FROM_USER_ID'],
				"TO_CHAT_ID" => $arParams['CHAT_ID'],
				"MESSAGE" 	 => $arParams['MESSAGE'],
			);
		}
		else
		{
			throw new Bitrix\Rest\RestException("Incorrect params", "PARAMS_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$attach = CIMMessageParamAttach::GetAttachByJson($arParams['ATTACH']);
		if ($attach)
		{
			if ($attach->IsAllowSize())
			{
				$arMessageFields['ATTACH'] = $attach;
			}
			else
			{
				throw new Bitrix\Rest\RestException("You have exceeded the maximum allowable size of attach", "ATTACH_OVERSIZE", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else if ($arParams['ATTACH'])
		{
			throw new Bitrix\Rest\RestException("Incorrect attach params", "ATTACH_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}
		if (isset($arParams['SYSTEM']) && $arParams['SYSTEM'] == 'Y')
		{
			$arMessageFields['SYSTEM'] = 'Y';
		}
		if (isset($arParams['URL_PREVIEW']) && $arParams['URL_PREVIEW'] == 'N')
		{
			$arMessageFields['URL_PREVIEW'] = 'N';
		}

		$id = CIMMessenger::Add($arMessageFields);
		if (!$id)
		{
			throw new Bitrix\Rest\RestException("Message isn't added", "PARAMS_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		return $id;

	}

	public static function messageDelete($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		if (isset($arParams['MESSAGE_ID']))
		{
			$arParams['ID'] = $arParams['MESSAGE_ID'];
		}

		$arParams['ID'] = intval($arParams['ID']);
		if ($arParams['ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Message ID can't be empty", "MESSAGE_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$res = CIMMessenger::Delete($arParams['ID']);
		if (!$res)
		{
			throw new Bitrix\Rest\RestException("Time has expired for modification or you don't have access", "CANT_EDIT_MESSAGE", CRestServer::STATUS_FORBIDDEN);
		}

		return true;
	}

	public static function messageUpdate($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		if (isset($arParams['MESSAGE_ID']))
		{
			$arParams['ID'] = $arParams['MESSAGE_ID'];
		}

		$arParams['ID'] = intval($arParams['ID']);
		if ($arParams['ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Message ID can't be empty", "MESSAGE_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}
		$arParams['IS_EDITED'] = $arParams['IS_EDITED'] == 'N'? 'N': 'Y';

		if (isset($arParams['ATTACH']))
		{
			$message = CIMMessenger::CheckPossibilityUpdateMessage($arParams['ID']);
			if (!$message)
			{
				throw new Bitrix\Rest\RestException("Time has expired for modification or you don't have access", "CANT_EDIT_MESSAGE", CRestServer::STATUS_FORBIDDEN);
			}

			if (empty($arParams['ATTACH']) || $arParams['ATTACH'] == 'N')
			{
				CIMMessageParam::Set($arParams['ID'], Array('IS_EDITED' => $arParams['IS_EDITED'], 'ATTACH' => Array()));
			}
			else
			{
				$attach = CIMMessageParamAttach::GetAttachByJson($arParams['ATTACH']);
				if ($attach)
				{
					if ($attach->IsAllowSize())
					{
						CIMMessageParam::Set($arParams['ID'], Array('IS_EDITED' => $arParams['IS_EDITED'], 'ATTACH' => $attach));
					}
					else
					{
						throw new Bitrix\Rest\RestException("You have exceeded the maximum allowable size of attach", "ATTACH_OVERSIZE", CRestServer::STATUS_WRONG_REQUEST);
					}
				}
				else if ($arParams['ATTACH'])
				{
					throw new Bitrix\Rest\RestException("Incorrect attach params", "ATTACH_ERROR", CRestServer::STATUS_WRONG_REQUEST);
				}
			}
		}

		if (isset($arParams['MESSAGE']))
		{
			$urlPreview = isset($arParams['URL_PREVIEW']) && $arParams['URL_PREVIEW'] == "N"? false: true;

			$res = CIMMessenger::Update($arParams['ID'], $arParams['MESSAGE'], $urlPreview);
			if (!$res)
			{
				throw new Bitrix\Rest\RestException("Time has expired for modification or you don't have access", "CANT_EDIT_MESSAGE", CRestServer::STATUS_FORBIDDEN);
			}
		}
		else
		{
			CIMMessageParam::SendPull($arParams['ID']);
		}

		return true;
	}

	public static function messageLike($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		if (isset($arParams['MESSAGE_ID']))
		{
			$arParams['ID'] = $arParams['MESSAGE_ID'];
		}

		$arParams['ID'] = intval($arParams['ID']);
		if ($arParams['ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Message ID can't be empty", "MESSAGE_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$arParams['ACTION'] = strtolower($arParams['ACTION']);
		if (!in_array($arParams['ACTION'], Array('auto', 'plus', 'minus')))
		{
			$arParams['ACTION'] = 'auto';
		}

		$result = CIMMessenger::Like($arParams['ID'], $arParams['ACTION']);
		if ($result === false)
		{
			throw new Bitrix\Rest\RestException("Action completed without changes", "WITHOUT_CHANGES", CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function notifyAdd($arParams, $n, CRestServer $server)
	{
		global $USER;

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		if (isset($arParams['TO']))
		{
			$arParams['USER_ID'] = $arParams['TO'];
		}
		$arParams['USER_ID'] = intval($arParams['USER_ID']);
		if ($arParams['USER_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("User ID can't be empty", "USER_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		if ($server->getMethod() == "im.notify.personal.add")
		{
			$arParams['TYPE'] = 'USER';
		}
		else if ($server->getMethod() == "im.notify.system.add")
		{
			$arParams['TYPE'] = 'SYSTEM';
		}
		else if (!isset($arParams['TYPE']) || !in_array($arParams['TYPE'], Array('USER', 'SYSTEM')))
		{
			$arParams['TYPE'] = 'USER';
		}

		$arParams['MESSAGE'] = trim($arParams['MESSAGE']);
		if (strlen($arParams['MESSAGE']) <= 0)
		{
			throw new Bitrix\Rest\RestException("Message can't be empty", "MESSAGE_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		$messageOut = "";
		$arParams['MESSAGE_OUT'] = trim($arParams['MESSAGE_OUT']);
		if ($arParams['TYPE'] == 'SYSTEM')
		{
			$result = \Bitrix\Rest\AppTable::getList(array('filter' => array('=CLIENT_ID' => $server->getAppId())));
			$result = $result->fetch();
			$moduleName = isset($result['APP_NAME'])? $result['APP_NAME']: $result['CODE'];

			$fromUserId = 0;
			$notifyType = IM_NOTIFY_SYSTEM;
			$message = $moduleName."#BR#".$arParams['MESSAGE'];
			if (!empty($arParams['MESSAGE_OUT']))
			{
				$messageOut = $moduleName."#BR#".$arParams['MESSAGE_OUT'];
			}
		}
		else
		{
			$fromUserId = $USER->GetID();
			$notifyType = IM_NOTIFY_FROM;
			$message = $arParams['MESSAGE'];
			if (!empty($arParams['MESSAGE_OUT']))
			{
				$messageOut = $arParams['MESSAGE_OUT'];
			}
		}

		$arMessageFields = array(
			"TO_USER_ID" => $arParams['USER_ID'],
			"FROM_USER_ID" => $fromUserId,
			"NOTIFY_TYPE" => $notifyType,
			"NOTIFY_MODULE" => "rest",
			"NOTIFY_EVENT" => "rest_notify",
			"NOTIFY_MESSAGE" => $message,
			"NOTIFY_MESSAGE_OUT" => $messageOut,
		);
		if (!empty($arParams['TAG']))
		{
			$appKey = substr(md5($server->getAppId()), 0, 5);
			$arMessageFields['NOTIFY_TAG'] = 'MP|'.$appKey.'|'.$arParams['TAG'];
		}
		if (!empty($arParams['SUB_TAG']))
		{
			$appKey = substr(md5($server->getAppId()), 0, 5);
			$arMessageFields['NOTIFY_SUB_TAG'] = 'MP|'.$appKey.'|'.$arParams['SUB_TAG'];
		}

		$attach = CIMMessageParamAttach::GetAttachByJson($arParams['ATTACH']);
		if ($attach)
		{
			if ($attach->IsAllowSize())
			{
				$arMessageFields['ATTACH'] = $attach;
			}
			else
			{
				throw new Bitrix\Rest\RestException("You have exceeded the maximum allowable size of attach", "ATTACH_OVERSIZE", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else if ($arParams['ATTACH'])
		{
			throw new Bitrix\Rest\RestException("Incorrect attach params", "ATTACH_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		return CIMNotify::Add($arMessageFields);
	}

	public static function notifyDelete($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		if (isset($arParams['ID']) && intval($arParams['ID']) > 0)
		{
			$CIMNotify = new CIMNotify();
			$result = $CIMNotify->DeleteWithCheck($arParams['ID']);
		}
		else if (!empty($arParams['TAG']))
		{
			$appKey = substr(md5($server->getAppId()), 0, 5);
			$result = CIMNotify::DeleteByTag('MP|'.$appKey.'|'.$arParams['TAG']);
		}
		else if (!empty($arParams['SUB_TAG']))
		{
			$appKey = substr(md5($server->getAppId()), 0, 5);
			$result = CIMNotify::DeleteBySubTag('MP|'.$appKey.'|'.$arParams['SUB_TAG']);
		}
		else
		{
			throw new Bitrix\Rest\RestException("Incorrect params", "PARAMS_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		return $result;
	}

	public static function notImplemented($arParams, $n, CRestServer $server)
	{
		throw new Bitrix\Rest\RestException("Method isn't implemented yet", "NOT_IMPLEMENTED", CRestServer::STATUS_NOT_FOUND);
	}

	/* BotAPI */

	public static function botRegister($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		if (!CModule::IncludeModule('rest'))
		{
			throw new Bitrix\Rest\RestException("Module \"REST\" isn't installed", "BITRIX24_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$dbRes = \Bitrix\Rest\AppTable::getList(array('filter' => array('=CLIENT_ID' => $server->getAppId())));
		$arApp = $dbRes->fetch();

		if (isset($arParams['EVENT_MESSAGE_ADD']) && !empty($arParams['EVENT_MESSAGE_ADD']))
		{
			try
			{
				Bitrix\Rest\EventTable::checkCallback($arParams['EVENT_MESSAGE_ADD'], $arApp);
			}
			catch(Exception $e)
			{
				throw new Bitrix\Rest\RestException($e->getMessage(), "EVENT_MESSAGE_ADD_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else
		{
			throw new Bitrix\Rest\RestException("Handler for \"Message add\" event isn't specified", "EVENT_MESSAGE_ADD_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (isset($arParams['EVENT_WELCOME_MESSAGE']) && !empty($arParams['EVENT_WELCOME_MESSAGE']))
		{
			try
			{
				Bitrix\Rest\EventTable::checkCallback($arParams['EVENT_WELCOME_MESSAGE'], $arApp);
			}
			catch(Exception $e)
			{
				throw new Bitrix\Rest\RestException($e->getMessage(), "EVENT_WELCOME_MESSAGE_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else
		{
			throw new Bitrix\Rest\RestException("Handler for \"Welcome message\" event isn't specified", "EVENT_WELCOME_MESSAGE_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (isset($arParams['EVENT_BOT_DELETE']) && !empty($arParams['EVENT_BOT_DELETE']))
		{
			try
			{
				Bitrix\Rest\EventTable::checkCallback($arParams['EVENT_BOT_DELETE'], $arApp);
			}
			catch(Exception $e)
			{
				throw new Bitrix\Rest\RestException($e->getMessage(), "EVENT_BOT_DELETE_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else
		{
			throw new Bitrix\Rest\RestException("Handler for \"Bot delete\" event isn't specified", "EVENT_BOT_DELETE_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!isset($arParams['CODE']) || empty($arParams['CODE']))
		{
			throw new Bitrix\Rest\RestException("Bot code isn't specified", "CODE_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$arParams['TYPE'] = in_array($arParams['TYPE'], Array('O', 'B', 'H'))? $arParams['TYPE']: '';
		$arParams['OPENLINE'] = $arParams['OPENLINE'] == 'Y'? 'Y': 'N';

		$properties = Array();
		if (isset($arParams['PROPERTIES']['NAME']))
		{
			$properties['NAME'] = $arParams['PROPERTIES']['NAME'];
		}
		if (isset($arParams['PROPERTIES']['LAST_NAME']))
		{
			$properties['LAST_NAME'] = $arParams['PROPERTIES']['LAST_NAME'];
		}
		if (!(isset($properties['NAME']) || isset($properties['LAST_NAME'])))
		{
			throw new Bitrix\Rest\RestException("Bot name isn't specified", "NAME_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (isset($arParams['PROPERTIES']['COLOR']))
		{
			$properties['COLOR'] = $arParams['PROPERTIES']['COLOR'];
		}
		if (isset($arParams['PROPERTIES']['EMAIL']))
		{
			$properties['EMAIL'] = $arParams['PROPERTIES']['EMAIL'];
		}
		if (isset($arParams['PROPERTIES']['PERSONAL_BIRTHDAY']))
		{
			$birthday = new \Bitrix\Main\Type\DateTime($arParams['PROPERTIES']['PERSONAL_BIRTHDAY'].' 19:45:00', 'Y-m-d H:i:s');
			$birthday = $birthday->format(\Bitrix\Main\Type\Date::convertFormatToPhp(\CSite::GetDateFormat('SHORT')));

			$properties['PERSONAL_BIRTHDAY'] = $birthday;
		}
		if (isset($arParams['PROPERTIES']['WORK_POSITION']))
		{
			$properties['WORK_POSITION'] = $arParams['PROPERTIES']['WORK_POSITION'];
		}
		if (isset($arParams['PROPERTIES']['PERSONAL_WWW']))
		{
			$properties['PERSONAL_WWW'] = $arParams['PROPERTIES']['PERSONAL_WWW'];
		}
		if (isset($arParams['PROPERTIES']['PERSONAL_GENDER']))
		{
			$properties['PERSONAL_GENDER'] = $arParams['PROPERTIES']['PERSONAL_GENDER'];
		}
		if (isset($arParams['PROPERTIES']['PERSONAL_PHOTO']))
		{
			$avatar = CRestUtil::saveFile($arParams['PROPERTIES']['PERSONAL_PHOTO'], $arParams['CODE'].'.png');
			if (isset($avatar) && strpos($avatar['type'], "image/") === 0)
			{
				$properties['PERSONAL_PHOTO'] = $avatar;
			}
		}

		$botId = \Bitrix\Im\Bot::register(Array(
			'APP_ID' => $server->getAppId(),
			'CODE' => $arParams['CODE'],
			'TYPE' => $arParams['TYPE'],
			'OPENLINE' => $arParams['OPENLINE'],
			'MODULE_ID' => 'rest',
			'PROPERTIES' => $properties
		));
		if ($botId)
		{
			self::unbindEvent($arApp['ID'], $arApp['CLIENT_ID'], 'im', 'onImBotMessageAdd', 'OnImBotMessageAdd', true);
			self::bindEvent($arApp['ID'], 'im', 'onImBotMessageAdd', 'OnImBotMessageAdd', $arParams['EVENT_MESSAGE_ADD']);

			self::unbindEvent($arApp['ID'], $arApp['CLIENT_ID'], 'im', 'onImBotJoinChat', 'OnImBotJoinChat', true);
			self::bindEvent($arApp['ID'], 'im', 'onImBotJoinChat', 'OnImBotJoinChat', $arParams['EVENT_WELCOME_MESSAGE']);

			self::unbindEvent($arApp['ID'], $arApp['CLIENT_ID'], 'im', 'onImBotDelete', 'OnImBotDelete', true);
			self::bindEvent($arApp['ID'], 'im', 'onImBotDelete', 'OnImBotDelete', $arParams['EVENT_BOT_DELETE']);
		}
		else
		{
			throw new Bitrix\Rest\RestException("Bot can't be created", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return $botId;
	}

	public static function botUnRegister($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$bots = \Bitrix\Im\Bot::getListCache();
		if (!isset($bots[$arParams['BOT_ID']]))
		{
			throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}
		if ($bots[$arParams['BOT_ID']]['APP_ID'] != $server->getAppId())
		{
			throw new Bitrix\Rest\RestException("Bot was installed by another application", "APP_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = \Bitrix\Im\Bot::unRegister(Array('BOT_ID' => $arParams['BOT_ID']));
		if (!$result)
		{
			throw new Bitrix\Rest\RestException("Bot can't be deleted", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function botUpdate($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$bots = \Bitrix\Im\Bot::getListCache();
		if (!isset($bots[$arParams['BOT_ID']]))
		{
			throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}
		if ($bots[$arParams['BOT_ID']]['APP_ID'] != $server->getAppId())
		{
			throw new Bitrix\Rest\RestException("Bot was installed by another application", "APP_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$dbRes = \Bitrix\Rest\AppTable::getList(array('filter' => array('=CLIENT_ID' => $server->getAppId())));
		$arApp = $dbRes->fetch();

		$updateEvents = Array();
		if (isset($arParams['FIELDS']['EVENT_MESSAGE_ADD']) && !empty($arParams['FIELDS']['EVENT_MESSAGE_ADD']))
		{
			$updateEvents['EVENT_MESSAGE_ADD'] = $arParams['FIELDS']['EVENT_MESSAGE_ADD'];
			try
			{
				Bitrix\Rest\EventTable::checkCallback($arParams['FIELDS']['EVENT_MESSAGE_ADD'], $arApp);
			}
			catch(Exception $e)
			{
				throw new Bitrix\Rest\RestException($e->getMessage(), "EVENT_MESSAGE_ADD_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		if (isset($arParams['FIELDS']['EVENT_WELCOME_MESSAGE']) && !empty($arParams['FIELDS']['EVENT_WELCOME_MESSAGE']))
		{
			$updateEvents['EVENT_WELCOME_MESSAGE'] = $arParams['FIELDS']['EVENT_WELCOME_MESSAGE'];
			try
			{
				Bitrix\Rest\EventTable::checkCallback($arParams['FIELDS']['EVENT_WELCOME_MESSAGE'], $arApp);
			}
			catch(Exception $e)
			{
				throw new Bitrix\Rest\RestException($e->getMessage(), "EVENT_WELCOME_MESSAGE_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		if (isset($arParams['FIELDS']['EVENT_BOT_DELETE']) && !empty($arParams['FIELDS']['EVENT_BOT_DELETE']))
		{
			$updateEvents['EVENT_BOT_DELETE'] = $arParams['FIELDS']['EVENT_BOT_DELETE'];
			try
			{
				Bitrix\Rest\EventTable::checkCallback($arParams['FIELDS']['EVENT_BOT_DELETE'], $arApp);
			}
			catch(Exception $e)
			{
				throw new Bitrix\Rest\RestException($e->getMessage(), "EVENT_BOT_DELETE_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		$updateFields = Array();

		if (isset($arParams['FIELDS']['CODE']) && !empty($arParams['FIELDS']['CODE']))
		{
			$updateFields['CODE'] = $arParams['FIELDS']['CODE'];
		}

		if (isset($arParams['FIELDS']['TYPE']) && !empty($arParams['FIELDS']['TYPE']) && in_array($arParams['TYPE'], Array('O', 'B', 'H')))
		{
			$updateFields['TYPE'] = $arParams['FIELDS']['TYPE'];
		}

		if (isset($arParams['FIELDS']['OPENLINE']) && !empty($arParams['FIELDS']['OPENLINE']))
		{
			$updateFields['OPENLINE'] = $arParams['FIELDS']['OPENLINE'];
		}

		$properties = Array();
		if (isset($arParams['FIELDS']['PROPERTIES']['NAME']))
		{
			$properties['NAME'] = $arParams['FIELDS']['PROPERTIES']['NAME'];
		}
		if (isset($arParams['FIELDS']['PROPERTIES']['LAST_NAME']))
		{
			$properties['LAST_NAME'] = $arParams['FIELDS']['PROPERTIES']['LAST_NAME'];
		}

		if (isset($properties['NAME']) && empty($properties['NAME']) && isset($properties['LAST_NAME']) && empty($properties['LAST_NAME']))
		{
			throw new Bitrix\Rest\RestException("Bot name isn't specified", "NAME_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (isset($arParams['FIELDS']['PROPERTIES']['COLOR']))
		{
			$properties['COLOR'] = $arParams['FIELDS']['PROPERTIES']['COLOR'];
		}
		if (isset($arParams['FIELDS']['PROPERTIES']['EMAIL']))
		{
			$properties['EMAIL'] = $arParams['FIELDS']['PROPERTIES']['EMAIL'];
		}
		if (isset($arParams['FIELDS']['PROPERTIES']['PERSONAL_BIRTHDAY']))
		{
			$birthday = new \Bitrix\Main\Type\DateTime($arParams['FIELDS']['PROPERTIES']['PERSONAL_BIRTHDAY'].' 19:45:00', 'Y-m-d H:i:s');
			$birthday = $birthday->format(\Bitrix\Main\Type\Date::convertFormatToPhp(\CSite::GetDateFormat('SHORT')));

			$properties['PERSONAL_BIRTHDAY'] = $birthday;
		}
		if (isset($arParams['FIELDS']['PROPERTIES']['WORK_POSITION']))
		{
			$properties['WORK_POSITION'] = $arParams['FIELDS']['PROPERTIES']['WORK_POSITION'];
		}
		if (isset($arParams['FIELDS']['PROPERTIES']['PERSONAL_WWW']))
		{
			$properties['PERSONAL_WWW'] = $arParams['FIELDS']['PROPERTIES']['PERSONAL_WWW'];
		}
		if (isset($arParams['FIELDS']['PROPERTIES']['PERSONAL_GENDER']))
		{
			$properties['PERSONAL_GENDER'] = $arParams['FIELDS']['PROPERTIES']['PERSONAL_GENDER'];
		}
		if (isset($arParams['FIELDS']['PROPERTIES']['PERSONAL_PHOTO']))
		{
			$avatar = CRestUtil::saveFile($arParams['FIELDS']['PROPERTIES']['PERSONAL_PHOTO'], $bots[$arParams['BOT_ID']]['CODE'].'.png');
			if ($avatar && strpos($avatar['type'], "image/") === 0)
			{
				$properties['PERSONAL_PHOTO'] = $avatar;
			}
		}

		if (!empty($properties))
		{
			$updateFields['PROPERTIES'] = $properties;
		}

		if (empty($updateFields))
		{
			if (empty($updateEvents))
			{
				throw new Bitrix\Rest\RestException("Update fields can't be empty", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else
		{
			$result = \Bitrix\Im\Bot::update(Array('BOT_ID' => $arParams['BOT_ID']), $updateFields);
			if (!$result)
			{
				throw new Bitrix\Rest\RestException("Bot can't be updated", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		if (isset($updateEvents['EVENT_MESSAGE_ADD']))
		{
			self::unbindEvent($arApp['ID'], $arApp['CLIENT_ID'], 'im', 'onImBotMessageAdd', 'OnImBotMessageAdd', true);
			self::bindEvent($arApp['ID'], 'im', 'onImBotMessageAdd', 'OnImBotMessageAdd', $updateEvents['EVENT_MESSAGE_ADD']);
		}
		if (isset($updateEvents['EVENT_WELCOME_MESSAGE']))
		{
			self::unbindEvent($arApp['ID'], $arApp['CLIENT_ID'], 'im', 'onImBotJoinChat', 'OnImBotJoinChat', true);
			self::bindEvent($arApp['ID'], 'im', 'onImBotJoinChat', 'OnImBotJoinChat', $updateEvents['EVENT_WELCOME_MESSAGE']);
		}
		if (isset($updateEvents['EVENT_BOT_DELETE']))
		{
			self::unbindEvent($arApp['ID'], $arApp['CLIENT_ID'], 'im', 'onImBotDelete', 'OnImBotDelete', true);
			self::bindEvent($arApp['ID'], 'im', 'onImBotDelete', 'OnImBotDelete', $updateEvents['EVENT_BOT_DELETE']);
		}

		return true;
	}

	public static function botMessageAdd($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$bots = \Bitrix\Im\Bot::getListCache();
		if (isset($arParams['BOT_ID']))
		{
			if (!isset($bots[$arParams['BOT_ID']]))
			{
				throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
			if ($bots[$arParams['BOT_ID']]['APP_ID'] != $server->getAppId())
			{
				throw new Bitrix\Rest\RestException("Bot was installed by another application", "APP_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else
		{
			$botFound = false;
			foreach ($bots as $bot)
			{
				if ($bot['APP_ID'] == $server->getAppId())
				{
					$botFound = true;
					$arParams['BOT_ID'] = $bot['BOT_ID'];
					break;
				}
			}
			if (!$botFound)
			{
				throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		$arMessageFields = Array();

		$arMessageFields['DIALOG_ID'] = $arParams['DIALOG_ID'];
		if (empty($arMessageFields['DIALOG_ID']))
		{
			throw new Bitrix\Rest\RestException("Dialog ID can't be empty", "DIALOG_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		$arMessageFields['MESSAGE'] = trim($arParams['MESSAGE']);
		if (strlen($arMessageFields['MESSAGE']) <= 0)
		{
			throw new Bitrix\Rest\RestException("Message can't be empty", "MESSAGE_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		$attach = CIMMessageParamAttach::GetAttachByJson($arParams['ATTACH']);
		if ($attach)
		{
			if ($attach->IsAllowSize())
			{
				$arMessageFields['ATTACH'] = $attach;
			}
			else
			{
				throw new Bitrix\Rest\RestException("You have exceeded the maximum allowable size of attach", "ATTACH_OVERSIZE", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else if ($arParams['ATTACH'])
		{
			throw new Bitrix\Rest\RestException("Incorrect attach params", "ATTACH_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (isset($arParams['KEYBOARD']))
		{
			$keyboard = Array();
			if (!isset($arParams['KEYBOARD']['BUTTONS']))
			{
				$keyboard['BUTTONS'] = $arParams['KEYBOARD'];
			}
			else
			{
				$keyboard = $arParams['KEYBOARD'];
			}
			$keyboard['BOT_ID'] = $arParams['BOT_ID'];

			$keyboard = \Bitrix\Im\Bot\Keyboard::getKeyboardByJson($keyboard);
			if ($keyboard)
			{
				$arMessageFields['KEYBOARD'] = $keyboard;
			}
			else
			{
				throw new Bitrix\Rest\RestException("Incorrect keyboard params", "KEYBOARD_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		if (isset($arParams['SYSTEM']) && $arParams['SYSTEM'] == 'Y')
		{
			$arMessageFields['SYSTEM'] = 'Y';
		}

		if (isset($arParams['URL_PREVIEW']) && $arParams['URL_PREVIEW'] == 'N')
		{
			$arMessageFields['URL_PREVIEW'] = 'N';
		}

		$id = \Bitrix\Im\Bot::addMessage(array('BOT_ID' => $arParams['BOT_ID']), $arMessageFields);
		if (!$id)
		{
			throw new Bitrix\Rest\RestException("Message isn't added", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return $id;

	}

	public static function botMessageUpdate($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$bots = \Bitrix\Im\Bot::getListCache();
		if (isset($arParams['BOT_ID']))
		{
			if (!isset($bots[$arParams['BOT_ID']]))
			{
				throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
			if ($bots[$arParams['BOT_ID']]['APP_ID'] != $server->getAppId())
			{
				throw new Bitrix\Rest\RestException("Bot was installed by another application", "APP_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else
		{
			$botFound = false;
			foreach ($bots as $bot)
			{
				if ($bot['APP_ID'] == $server->getAppId())
				{
					$botFound = true;
					$arParams['BOT_ID'] = $bot['BOT_ID'];
					break;
				}
			}
			if (!$botFound)
			{
				throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		$arParams['MESSAGE_ID'] = intval($arParams['MESSAGE_ID']);
		if ($arParams['MESSAGE_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Message ID can't be empty", "MESSAGE_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$message = null;
		if (isset($arParams['ATTACH']))
		{
			$message = CIMMessenger::CheckPossibilityUpdateMessage($arParams['MESSAGE_ID'], $arParams['BOT_ID']);
			if (!$message)
			{
				throw new Bitrix\Rest\RestException("Time has expired for modification or you don't have access", "CANT_EDIT_MESSAGE", CRestServer::STATUS_FORBIDDEN);
			}

			if (empty($arParams['ATTACH']) || $arParams['ATTACH'] == 'N')
			{
				CIMMessageParam::Set($arParams['MESSAGE_ID'], Array('ATTACH' => Array()));
			}
			else
			{
				$attach = CIMMessageParamAttach::GetAttachByJson($arParams['ATTACH']);
				if ($attach)
				{
					if ($attach->IsAllowSize())
					{
						CIMMessageParam::Set($arParams['MESSAGE_ID'], Array('ATTACH' => $attach));
					}
					else
					{
						throw new Bitrix\Rest\RestException("You have exceeded the maximum allowable size of attach", "ATTACH_OVERSIZE", CRestServer::STATUS_WRONG_REQUEST);
					}
				}
				else if ($arParams['ATTACH'])
				{
					throw new Bitrix\Rest\RestException("Incorrect attach params", "ATTACH_ERROR", CRestServer::STATUS_WRONG_REQUEST);
				}
			}
		}


		if (isset($arParams['KEYBOARD']))
		{
			if (is_null($message))
			{
				$message = CIMMessenger::CheckPossibilityUpdateMessage($arParams['MESSAGE_ID'], $arParams['BOT_ID']);
			}
			if (!$message)
			{
				throw new Bitrix\Rest\RestException("Time has expired for modification or you don't have access", "CANT_EDIT_MESSAGE", CRestServer::STATUS_FORBIDDEN);
			}

			if (empty($arParams['KEYBOARD']) || $arParams['KEYBOARD'] == 'N')
			{
				CIMMessageParam::Set($arParams['MESSAGE_ID'], Array('KEYBOARD' => 'N'));
			}
			else
			{
				$keyboard = Array();
				if (!isset($arParams['KEYBOARD']['BUTTONS']))
				{
					$keyboard['BUTTONS'] = $arParams['KEYBOARD'];
				}
				else
				{
					$keyboard = $arParams['KEYBOARD'];
				}
				$keyboard['BOT_ID'] = $arParams['BOT_ID'];

				$keyboard = \Bitrix\Im\Bot\Keyboard::getKeyboardByJson($keyboard);
				if ($keyboard)
				{
					if ($keyboard->isAllowSize())
					{
						CIMMessageParam::Set($arParams['MESSAGE_ID'], Array('KEYBOARD' => $keyboard));
					}
					else
					{
						throw new Bitrix\Rest\RestException("You have exceeded the maximum allowable size of keyboard", "KEYBOARD_OVERSIZE", CRestServer::STATUS_WRONG_REQUEST);
					}
				}
				else if ($arParams['KEYBOARD'])
				{
					throw new Bitrix\Rest\RestException("Incorrect keyboard params", "KEYBOARD_ERROR", CRestServer::STATUS_WRONG_REQUEST);
				}
			}
		}

		if (isset($arParams['MESSAGE']))
		{
			$urlPreview = isset($arParams['URL_PREVIEW']) && $arParams['URL_PREVIEW'] == "N"? false: true;

			$res = CIMMessenger::Update($arParams['MESSAGE_ID'], $arParams['MESSAGE'], $urlPreview, false, $arParams['BOT_ID']);
			if (!$res)
			{
				throw new Bitrix\Rest\RestException("Time has expired for modification or you don't have access", "CANT_EDIT_MESSAGE", CRestServer::STATUS_FORBIDDEN);
			}
		}
		else
		{
			CIMMessageParam::SendPull($arParams['MESSAGE_ID']);
		}

		return true;
	}

	public static function botMessageDelete($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$bots = \Bitrix\Im\Bot::getListCache();
		if (isset($arParams['BOT_ID']))
		{
			if (!isset($bots[$arParams['BOT_ID']]))
			{
				throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
			if ($bots[$arParams['BOT_ID']]['APP_ID'] != $server->getAppId())
			{
				throw new Bitrix\Rest\RestException("Bot was installed by another application", "APP_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else
		{
			$botFound = false;
			foreach ($bots as $bot)
			{
				if ($bot['APP_ID'] == $server->getAppId())
				{
					$botFound = true;
					$arParams['BOT_ID'] = $bot['BOT_ID'];
					break;
				}
			}
			if (!$botFound)
			{
				throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		$arParams['MESSAGE_ID'] = intval($arParams['MESSAGE_ID']);
		if ($arParams['MESSAGE_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Message ID can't be empty", "MESSAGE_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$res = CIMMessenger::Delete($arParams['MESSAGE_ID'], $arParams['BOT_ID']);
		if (!$res)
		{
			throw new Bitrix\Rest\RestException("Time has expired for modification or you don't have access", "CANT_EDIT_MESSAGE", CRestServer::STATUS_FORBIDDEN);
		}

		return true;
	}

	public static function botMessageLike($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$bots = \Bitrix\Im\Bot::getListCache();
		if (isset($arParams['BOT_ID']))
		{
			if (!isset($bots[$arParams['BOT_ID']]))
			{
				throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
			if ($bots[$arParams['BOT_ID']]['APP_ID'] != $server->getAppId())
			{
				throw new Bitrix\Rest\RestException("Bot was installed by another application", "APP_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else
		{
			$botFound = false;
			foreach ($bots as $bot)
			{
				if ($bot['APP_ID'] == $server->getAppId())
				{
					$botFound = true;
					$arParams['BOT_ID'] = $bot['BOT_ID'];
					break;
				}
			}
			if (!$botFound)
			{
				throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		$arParams['MESSAGE_ID'] = intval($arParams['MESSAGE_ID']);
		if ($arParams['MESSAGE_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Message ID can't be empty", "MESSAGE_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$arParams['ACTION'] = strtolower($arParams['ACTION']);
		if (!in_array($arParams['ACTION'], Array('auto', 'plus', 'minus')))
		{
			$arParams['ACTION'] = 'auto';
		}

		$result = CIMMessenger::Like($arParams['MESSAGE_ID'], $arParams['ACTION'], $arParams['BOT_ID']);
		if ($result === false)
		{
			throw new Bitrix\Rest\RestException("Action completed without changes", "WITHOUT_CHANGES", CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function botSendTyping($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$bots = \Bitrix\Im\Bot::getListCache();
		if (isset($arParams['BOT_ID']))
		{
			if (!isset($bots[$arParams['BOT_ID']]))
			{
				throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
			if ($bots[$arParams['BOT_ID']]['APP_ID'] != $server->getAppId())
			{
				throw new Bitrix\Rest\RestException("Bot was installed by another application", "APP_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else
		{
			$botFound = false;
			foreach ($bots as $bot)
			{
				if ($bot['APP_ID'] == $server->getAppId())
				{
					$botFound = true;
					$arParams['BOT_ID'] = $bot['BOT_ID'];
					break;
				}
			}
			if (!$botFound)
			{
				throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		
		if (empty($arParams['DIALOG_ID']))
		{
			throw new Bitrix\Rest\RestException("Dialog ID can't be empty", "DIALOG_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => $arParams['BOT_ID']), $arParams['DIALOG_ID']);

		return true;
	}

	public static function onCommandAdd($arParams, $arHandler)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$bot = \Bitrix\Im\Bot::getListCache();

		$commandId = Array();
		foreach ($arParams[0] as $commandData)
		{
			if ($commandData['APP_ID'] == $arHandler['APP_CODE'] && $commandData['BOT_ID'] > 0)
			{
				$sendBotData = self::getAccessToken($arHandler['APP_ID'], $commandData['BOT_ID']);
				$sendBotData['AUTH'] = $sendBotData;
				$sendBotData['BOT_ID'] = $commandData['BOT_ID'];
				$sendBotData['BOT_CODE'] = $bot[$commandData['BOT_ID']]['CODE'];
				$sendBotData['COMMAND'] = $commandData['COMMAND'];
				$sendBotData['COMMAND_ID'] = $commandData['ID'];
				$sendBotData['COMMAND_PARAMS'] = $commandData['EXEC_PARAMS'];
				$sendBotData['COMMAND_CONTEXT'] = $commandData['CONTEXT'];
				$sendBotData['MESSAGE_ID'] = $arParams[1];
				$commandId[$sendBotData['COMMAND_ID']] = $sendBotData;
				if ($commandData['CONTEXT'] != 'KEYBOARD')
				{
					if (
						$arParams[2]['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE ||
						$arParams[2]['FROM_USER_ID'] == $commandData['BOT_ID'] ||
						$arParams[2]['TO_USER_ID'] == $commandData['BOT_ID']
					)
					{
						\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => $commandData['BOT_ID']), $arParams[2]['DIALOG_ID']);
					}
				}
			}
		}
		if (empty($commandId))
		{
			throw new Exception('Event is intended for another application');
		}
		$arParams[2]['MESSAGE_ID'] = $arParams[1];
		$arParams[2]['CHAT_TYPE'] = $arParams[2]['MESSAGE_TYPE'];
		$arParams[2]['LANGUAGE'] = \Bitrix\Im\Bot::getDefaultLanguage();

		if ($arParams[2]['FROM_USER_ID'] > 0)
		{
			$fromUser = \Bitrix\Im\User::getInstance($arParams[2]['FROM_USER_ID'])->getFields();

			$user = Array(
				'ID' => $fromUser['id'],
				'NAME' => $fromUser['name'],
				'FIRST_NAME' => $fromUser['firstName'],
				'LAST_NAME' => $fromUser['lastName'],
				'WORK_POSITION' => $fromUser['workPosition'],
				'GENDER' => $fromUser['gender'],
			);
		}
		else
		{
			$user = Array();
		}

		return Array(
			'COMMAND' => $commandId,
			'PARAMS' => $arParams[2],
			'USER' => $user
		);
	}

	public static function onBotMessageAdd($arParams, $arHandler)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$bots = Array();
		foreach ($arParams[0] as $botData)
		{
			if ($botData['APP_ID'] == $arHandler['APP_CODE'])
			{
				$sendBotData = self::getAccessToken($arHandler['APP_ID'], $botData['BOT_ID']);
				$sendBotData['AUTH'] = $sendBotData;
				$sendBotData['BOT_ID'] = $botData['BOT_ID'];
				$sendBotData['BOT_CODE'] = $botData['CODE'];
				$bots[$botData['BOT_ID']] = $sendBotData;

				if ($arParams[2]['CHAT_ENTITY_TYPE'] != 'LINES')
				{
					\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => $botData['BOT_ID']), $arParams[2]['DIALOG_ID']);
				}
			}
		}

		if (empty($bots))
		{
			throw new Exception('Event is intended for another application');
		}

		$arParams[2]['MESSAGE_ID'] = $arParams[1];
		$arParams[2]['CHAT_TYPE'] = $arParams[2]['MESSAGE_TYPE'];
		$arParams[2]['LANGUAGE'] = \Bitrix\Im\Bot::getDefaultLanguage();

		if ($arParams[2]['FROM_USER_ID'] > 0)
		{
			$fromUser = \Bitrix\Im\User::getInstance($arParams[2]['FROM_USER_ID'])->getFields();

			$user = Array(
				'ID' => $fromUser['id'],
				'NAME' => $fromUser['name'],
				'FIRST_NAME' => $fromUser['firstName'],
				'LAST_NAME' => $fromUser['lastName'],
				'WORK_POSITION' => $fromUser['workPosition'],
				'GENDER' => $fromUser['gender'],
			);
		}
		else
		{
			$user = Array();
		}

		return Array(
			'BOT' => $bots,
			'PARAMS' => $arParams[2],
			'USER' => $user
		);
	}

	public static function onBotJoinChat($arParams, $arHandler)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$bots = Array();
		if ($arParams[0]['APP_ID'] == $arHandler['APP_CODE'])
		{
			$sendBotData = self::getAccessToken($arHandler['APP_ID'], $arParams[0]['BOT_ID']);
			$sendBotData['AUTH'] = $sendBotData;
			$sendBotData['BOT_ID'] = $arParams[0]['BOT_ID'];
			$sendBotData['BOT_CODE'] = $arParams[0]['CODE'];
			$bots[$arParams[0]['BOT_ID']] = $sendBotData;
		}

		if (empty($bots))
		{
			throw new Exception('Event is intended for another application');
		}

		$params = $arParams[2];
		$params['DIALOG_ID'] = $arParams[1];
		$params['LANGUAGE'] = \Bitrix\Im\Bot::getDefaultLanguage();

		if ($params['USER_ID'] > 0)
		{
			$fromUser = \Bitrix\Im\User::getInstance($params['USER_ID'])->getFields();

			$user = Array(
				'ID' => $fromUser['id'],
				'NAME' => $fromUser['name'],
				'FIRST_NAME' => $fromUser['firstName'],
				'LAST_NAME' => $fromUser['lastName'],
				'WORK_POSITION' => $fromUser['workPosition'],
				'GENDER' => $fromUser['gender'],
			);
		}
		else
		{
			$user = Array();
		}

		\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => $params['BOT_ID']), $params['DIALOG_ID']);

		return Array(
			'BOT' => $bots,
			'PARAMS' => $params,
			'USER' => $user
		);
	}

	public static function onBotDelete($arParams, $arHandler)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$botCode = "";
		if ($arParams[0]['APP_ID'] == $arHandler['APP_CODE'])
		{
			$botCode = $arParams[0]['CODE'];
		}

		if (!$botCode)
		{
			throw new Exception('Event is intended for another application');
		}

		$botId = $arParams[1];

		$result = self::unbindEvent($arHandler['APP_ID'], $arHandler['APP_CODE'], 'im', 'onImBotMessageAdd', 'OnImBotMessageAdd');
		if ($result)
		{
			self::unbindEvent($arHandler['APP_ID'], $arHandler['APP_CODE'], 'im', 'onImBotJoinChat', 'OnImBotJoinChat');
			self::unbindEvent($arHandler['APP_ID'], $arHandler['APP_CODE'], 'im', 'onImBotDelete', 'OnImBotDelete');
		}

		return Array(
			'BOT_ID' => $botId,
			'BOT_CODE' => $botCode
		);
	}

	public static function commandRegister($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		if (!CModule::IncludeModule('rest'))
		{
			throw new Bitrix\Rest\RestException("Module \"REST\" isn't installed", "BITRIX24_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$dbRes = \Bitrix\Rest\AppTable::getList(array('filter' => array('=CLIENT_ID' => $server->getAppId())));
		$arApp = $dbRes->fetch();

		if (isset($arParams['EVENT_COMMAND_ADD']) && !empty($arParams['EVENT_COMMAND_ADD']))
		{
			try
			{
				Bitrix\Rest\EventTable::checkCallback($arParams['EVENT_COMMAND_ADD'], $arApp);
			}
			catch(Exception $e)
			{
				throw new Bitrix\Rest\RestException($e->getMessage(), "EVENT_COMMAND_ADD_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else
		{
			throw new Bitrix\Rest\RestException("Handler for \"Command add\" event isn't specified", "EVENT_COMMAND_ADD_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!isset($arParams['COMMAND']) || empty($arParams['COMMAND']))
		{
			throw new Bitrix\Rest\RestException("Command isn't specified", "COMMAND_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$arParams['BOT_ID'] = intval($arParams['BOT_ID']);
		if ($arParams['BOT_ID'] > 0)
		{
			$bots = \Bitrix\Im\Bot::getListCache();
			if (!isset($bots[$arParams['BOT_ID']]))
			{
				throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
			if ($bots[$arParams['BOT_ID']]['APP_ID'] != $server->getAppId())
			{
				throw new Bitrix\Rest\RestException("Bot was installed by another application", "APP_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else
		{
			throw new Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}
		$arParams['COMMON'] = isset($arParams['COMMON']) && $arParams['COMMON'] == 'Y'? 'Y': 'N';
		$arParams['HIDDEN'] = isset($arParams['HIDDEN']) && $arParams['HIDDEN'] == 'Y'? 'Y': 'N';
		$arParams['EXTRANET_SUPPORT'] = isset($arParams['EXTRANET_SUPPORT']) && $arParams['EXTRANET_SUPPORT'] == 'Y'? 'Y': 'N';

		if (!isset($arParams['LANG']) && empty($arParams['LANG']) && $arParams['HIDDEN'] == 'N')
		{
			throw new Bitrix\Rest\RestException("Lang set can't be empty", "LANG_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$commandId = \Bitrix\Im\Command::register(Array(
			'APP_ID' => $server->getAppId(),
			'BOT_ID' => $arParams['BOT_ID'],
			'COMMAND' => $arParams['COMMAND'],
			'COMMON' => $arParams['COMMON'],
			'HIDDEN' => $arParams['HIDDEN'],
			'SONET_SUPPORT' => $arParams['SONET_SUPPORT'],
			'EXTRANET_SUPPORT' => $arParams['EXTRANET_SUPPORT'],
			'MODULE_ID' => 'rest',
			'LANG' => $arParams['LANG'],
		));
		if ($commandId)
		{
			self::unbindEvent($arApp['ID'], $arApp['CLIENT_ID'], 'im', 'onImCommandAdd', 'onImCommandAdd', true);
			self::bindEvent($arApp['ID'], 'im', 'onImCommandAdd', 'onImCommandAdd', $arParams['EVENT_COMMAND_ADD']);
		}
		else
		{
			throw new Bitrix\Rest\RestException("Command can't be created", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return $commandId;
	}

	public static function commandUnRegister($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$commands = \Bitrix\Im\Command::getListCache();
		if (!isset($commands[$arParams['COMMAND_ID']]))
		{
			throw new Bitrix\Rest\RestException("Command not found", "COMMAND_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}
		if ($commands[$arParams['COMMAND_ID']]['APP_ID'] != $server->getAppId())
		{
			throw new Bitrix\Rest\RestException("Command was installed by another application", "APP_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = \Bitrix\Im\Command::unRegister(Array('COMMAND_ID' => $arParams['COMMAND_ID']));
		if (!$result)
		{
			throw new Bitrix\Rest\RestException("Command can't be deleted", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function commandUpdate($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$bots = \Bitrix\Im\Command::getListCache();
		if (!isset($bots[$arParams['COMMAND_ID']]))
		{
			throw new Bitrix\Rest\RestException("Command not found", "COMMAND_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}
		if ($bots[$arParams['COMMAND_ID']]['APP_ID'] != $server->getAppId())
		{
			throw new Bitrix\Rest\RestException("Command was installed by another application", "APP_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		$dbRes = \Bitrix\Rest\AppTable::getList(array('filter' => array('=CLIENT_ID' => $server->getAppId())));
		$arApp = $dbRes->fetch();

		$updateEvents = Array();
		if (isset($arParams['FIELDS']['EVENT_COMMAND_ADD']) && !empty($arParams['FIELDS']['EVENT_COMMAND_ADD']))
		{
			$updateEvents['EVENT_COMMAND_ADD'] = $arParams['FIELDS']['EVENT_COMMAND_ADD'];
			try
			{
				Bitrix\Rest\EventTable::checkCallback($arParams['FIELDS']['EVENT_COMMAND_ADD'], $arApp);
			}
			catch(Exception $e)
			{
				throw new Bitrix\Rest\RestException($e->getMessage(), "EVENT_COMMAND_ADD_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		$updateFields = Array();

		if (isset($arParams['FIELDS']['COMMAND']) && !empty($arParams['FIELDS']['COMMAND']))
		{
			$updateFields['COMMAND'] = $arParams['FIELDS']['COMMAND'];
		}

		if (isset($arParams['FIELDS']['HIDDEN']) && !empty($arParams['FIELDS']['HIDDEN']))
		{
			$updateFields['HIDDEN'] = $arParams['FIELDS']['HIDDEN'] == 'Y'? 'Y': 'N';
		}

		if (isset($arParams['FIELDS']['EXTRANET_SUPPORT']) && !empty($arParams['FIELDS']['EXTRANET_SUPPORT']))
		{
			$updateFields['EXTRANET_SUPPORT'] = $arParams['FIELDS']['EXTRANET_SUPPORT'] == 'Y'? 'Y': 'N';
		}

		if (isset($arParams['FIELDS']['LANG']) && !empty($arParams['FIELDS']['LANG']))
		{
			$updateFields['LANG'] = $arParams['FIELDS']['LANG'];
		}

		if (empty($updateFields))
		{
			if (empty($updateEvents))
			{
				throw new Bitrix\Rest\RestException("Update fields can't be empty", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else
		{
			$result = \Bitrix\Im\Command::update(Array('COMMAND_ID' => $arParams['COMMAND_ID']), $updateFields);
			if (!$result)
			{
				throw new Bitrix\Rest\RestException("Command can't be updated", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		if (isset($updateEvents['EVENT_COMMAND_ADD']))
		{
			self::unbindEvent($arApp['ID'], $arApp['CLIENT_ID'], 'im', 'onImCommandAdd', 'onImCommandAdd', true);
			self::bindEvent($arApp['ID'], 'im', 'onImCommandAdd', 'onImCommandAdd', $updateEvents['EVENT_COMMAND_ADD']);
		}

		return true;
	}

	public static function commandAnswer($arParams, $n, CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);
		$commands = \Bitrix\Im\Command::getListCache();
		if (isset($arParams['COMMAND_ID']))
		{
			if (!isset($commands[$arParams['COMMAND_ID']]))
			{
				throw new Bitrix\Rest\RestException("Command not found", "COMMAND_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
			if ($commands[$arParams['COMMAND_ID']]['APP_ID'] != $server->getAppId())
			{
				throw new Bitrix\Rest\RestException("Command was installed by another application", "APP_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else if (isset($arParams['COMMAND']))
		{
			$commandFound = false;
			foreach ($commands as $command)
			{
				if ($command['APP_ID'] == $server->getAppId() && $command['COMMAND'] == $arParams['COMMAND'])
				{
					$commandFound = true;
					$arParams['COMMAND_ID'] = $command['COMMAND_ID'];
					break;
				}
			}
			if (!$commandFound)
			{
				throw new Bitrix\Rest\RestException("Command not found", "COMMAND_ID_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		$botId = intval($commands[$arParams['COMMAND_ID']]['BOT_ID']);

		$arMessageFields = Array();

		$arParams['MESSAGE_ID'] = intval($arParams['MESSAGE_ID']);
		if ($arParams['MESSAGE_ID'] <= 0)
		{
			throw new Bitrix\Rest\RestException("Message ID can't be empty", "MESSAGE_ID_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		$arMessageFields['MESSAGE'] = trim($arParams['MESSAGE']);
		if (strlen($arMessageFields['MESSAGE']) <= 0)
		{
			throw new Bitrix\Rest\RestException("Message can't be empty", "MESSAGE_EMPTY", CRestServer::STATUS_WRONG_REQUEST);
		}

		$attach = CIMMessageParamAttach::GetAttachByJson($arParams['ATTACH']);
		if ($attach)
		{
			if ($attach->IsAllowSize())
			{
				$arMessageFields['ATTACH'] = $attach;
			}
			else
			{
				throw new Bitrix\Rest\RestException("You have exceeded the maximum allowable size of attach", "ATTACH_OVERSIZE", CRestServer::STATUS_WRONG_REQUEST);
			}
		}
		else if ($arParams['ATTACH'])
		{
			throw new Bitrix\Rest\RestException("Incorrect attach params", "ATTACH_ERROR", CRestServer::STATUS_WRONG_REQUEST);
		}

		if (isset($arParams['KEYBOARD']) && $botId > 0)
		{
			$keyboard = Array();
			if (!isset($arParams['KEYBOARD']['BUTTONS']))
			{
				$keyboard['BUTTONS'] = $arParams['KEYBOARD'];
			}
			else
			{
				$keyboard = $arParams['KEYBOARD'];
			}
			$keyboard['BOT_ID'] = $botId;

			$keyboard = \Bitrix\Im\Bot\Keyboard::getKeyboardByJson($keyboard);
			if ($keyboard)
			{
				if ($keyboard->isAllowSize())
				{
					$arMessageFields['KEYBOARD'] = $keyboard;
				}
				else
				{
					throw new Bitrix\Rest\RestException("You have exceeded the maximum allowable size of keyboard", "KEYBOARD_OVERSIZE", CRestServer::STATUS_WRONG_REQUEST);
				}
			}
			else
			{
				throw new Bitrix\Rest\RestException("Incorrect keyboard params", "KEYBOARD_ERROR", CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		if (isset($arParams['SYSTEM']) && $arParams['SYSTEM'] == 'Y')
		{
			$arMessageFields['SYSTEM'] = 'Y';
		}

		if (isset($arParams['URL_PREVIEW']) && $arParams['URL_PREVIEW'] == 'N')
		{
			$arMessageFields['URL_PREVIEW'] = 'N';
		}

		$id = \Bitrix\Im\Command::addMessage(Array(
			'MESSAGE_ID' => $arParams['MESSAGE_ID'],
			'COMMAND_ID' => $arParams['COMMAND_ID']
		), $arMessageFields);
		if (!$id)
		{
			throw new Bitrix\Rest\RestException("Message isn't added", "WRONG_REQUEST", CRestServer::STATUS_WRONG_REQUEST);
		}

		return $id;
	}

	private static function getAccessToken($appId, $userId)
	{
		$session = \Bitrix\Rest\Event\Session::get();
		if(!$session)
		{
			return Array();
		}
		$auth = \Bitrix\Rest\Event\Sender::getAuth(
			$appId,
			$userId,
			array('EVENT_SESSION' => $session)
		);
		return $auth? $auth: Array();
	}

	private static function bindEvent($appId, $bitrixEventModule, $bitrixEventName, $restEventName, $restEventHandler)
	{
		$res = \Bitrix\Rest\EventTable::getList(array(
			'filter' => array(
				'=EVENT_NAME' => toUpper($restEventName),
				'=APP_ID' => $appId,
			),
			'select' => array('ID')
		));
		if ($handler = $res->fetch())
		{
			return true;
		}

		$result = \Bitrix\Rest\EventTable::add(array(
			"APP_ID" => $appId,
			"EVENT_NAME" => toUpper($restEventName),
			"EVENT_HANDLER" => $restEventHandler,
			"USER_ID" => 0,
		));
		if($result->isSuccess())
		{
			\Bitrix\Rest\Event\Sender::bind($bitrixEventModule, $bitrixEventName);
		}

		return true;
	}

	private static function unbindEvent($appId, $appCode, $bitrixEventModule, $bitrixEventName, $restEventName, $skipCheck = false)
	{
		if (!$skipCheck)
		{
			$res = \Bitrix\Im\Model\BotTable::getList(array(
				'filter' => array(
					'=APP_ID' => $appCode,
				),
				'select' => array('BOT_ID')
			));
			if ($handler = $res->fetch())
			{
				return false;
			}
		}

		$res = \Bitrix\Rest\EventTable::getList(array(
			'filter' => array(
				'=EVENT_NAME' => toUpper($restEventName),
				'=APP_ID' => $appId,
			),
			'select' => array('ID')
		));
		$eventFound = false;
		while($handler = $res->fetch())
		{
			$eventFound = true;
			\Bitrix\Rest\EventTable::delete($handler['ID']);
		}
		if ($eventFound)
		{
			\Bitrix\Rest\Event\Sender::unbind($bitrixEventModule, $bitrixEventName);
		}
		
		return true;
	}
}
?>