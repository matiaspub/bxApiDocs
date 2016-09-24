<?php

namespace Bitrix\Im;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Bot
{
	const INSTALL_TYPE_SYSTEM = 'system';
	const INSTALL_TYPE_USER = 'user';
	const INSTALL_TYPE_SILENT = 'silent';

	const LOGIN_START = 'bot_';
	const EXTERNAL_AUTH_ID = 'bot';

	const LIST_ALL = 'all';
	const LIST_OPENLINE = 'openline';

	const TYPE_HUMAN = 'H';
	const TYPE_BOT = 'B';
	const TYPE_NETWORK = 'N';
	const TYPE_OPENLINE = 'O';

	const CACHE_TTL = 31536000;
	const CACHE_PATH = '/bx/im/bot/';

	public static function register(array $fields)
	{
		$code = isset($fields['CODE'])? $fields['CODE']: '';
		$type = in_array($fields['TYPE'], Array(self::TYPE_HUMAN, self::TYPE_BOT, self::TYPE_NETWORK, self::TYPE_OPENLINE))? $fields['TYPE']: self::TYPE_BOT;
		$moduleId = $fields['MODULE_ID'];
		$installType = in_array($fields['INSTALL_TYPE'], Array(self::INSTALL_TYPE_SYSTEM, self::INSTALL_TYPE_USER, self::INSTALL_TYPE_SILENT))? $fields['INSTALL_TYPE']: self::INSTALL_TYPE_SYSTEM;
		$botFields = $fields['PROPERTIES'];
		$language = isset($fields['LANG'])? $fields['LANG']: null;

		/* vars for module install */
		$class = isset($fields['CLASS'])? $fields['CLASS']: '';
		$methodBotDelete = isset($fields['METHOD_BOT_DELETE'])? $fields['METHOD_BOT_DELETE']: '';
		$methodMessageAdd = isset($fields['METHOD_MESSAGE_ADD'])? $fields['METHOD_MESSAGE_ADD']: '';
		$methodWelcomeMessage = isset($fields['METHOD_WELCOME_MESSAGE'])? $fields['METHOD_WELCOME_MESSAGE']: '';
		$textPrivateWelcomeMessage = isset($fields['TEXT_PRIVATE_WELCOME_MESSAGE'])? $fields['TEXT_PRIVATE_WELCOME_MESSAGE']: '';
		$textChatWelcomeMessage = isset($fields['TEXT_CHAT_WELCOME_MESSAGE'])? $fields['TEXT_CHAT_WELCOME_MESSAGE']: '';
		$openline = isset($fields['OPENLINE']) && $fields['OPENLINE'] == 'Y'? 'Y': 'N';

		/* rewrite vars for openline type */
		if ($type == self::TYPE_OPENLINE)
		{
			$openline = 'Y';
			$installType = self::INSTALL_TYPE_SILENT;
		}

		/* vars for rest install */
		$appId = isset($fields['APP_ID'])? $fields['APP_ID']: '';
		$verified = isset($fields['VERIFIED']) && $fields['VERIFIED'] == 'Y'? 'Y': 'N';

		if ($moduleId == 'rest')
		{
			if (empty($appId))
			{
				return false;
			}
		}
		else
		{
			if (empty($class) || empty($methodMessageAdd))
			{
				return false;
			}
			if (!(!empty($methodWelcomeMessage) || isset($fields['TEXT_PRIVATE_WELCOME_MESSAGE'])))
			{
				return false;
			}
		}

		$bots = self::getListCache();
		if ($moduleId && $code)
		{
			foreach ($bots as $bot)
			{
				if ($bot['MODULE_ID'] == $moduleId && $bot['CODE'] == $code)
				{
					return $bot['BOT_ID'];
				}
			}
		}

		$userCode = $code? $moduleId.'_'.$code: $moduleId;

		$color = null;
		if (isset($botFields['COLOR']))
		{
			$color = $botFields['COLOR'];
			unset($botFields['COLOR']);
		}

		$userId = 0;
		if ($installType == self::INSTALL_TYPE_USER)
		{
			if (isset($fields['USER_ID']) && intval($fields['USER_ID']) > 0)
			{
				$userId = intval($fields['USER_ID']);
			}
			else
			{
				global $USER;
				if (is_object($USER))
				{
					$userId = $USER->GetID() > 0? $USER->GetID(): 0;
				}
			}
			if ($userId <= 0)
			{
				$installType = self::INSTALL_TYPE_SYSTEM;
			}
		}

		if (strlen($moduleId) <= 0)
		{
			return false;
		}

		if (!(isset($botFields['NAME']) || isset($botFields['LAST_NAME'])))
		{
			return false;
		}

		$botFields['LOGIN'] = substr(self::LOGIN_START.$userCode.'_'.randString(5), 0, 50);
		$botFields['PASSWORD'] = md5($botFields['LOGIN'].'|'.rand(1000,9999).'|'.time());
		$botFields['CONFIRM_PASSWORD'] = $botFields['PASSWORD'];
		$botFields['EXTERNAL_AUTH_ID'] = self::EXTERNAL_AUTH_ID;

		unset($botFields['GROUP_ID']);

		$botFields['ACTIVE'] = 'Y';

		if (IsModuleInstalled('intranet'))
		{
			$botFields['UF_DEPARTMENT'] = Array(\Bitrix\Im\Bot\Department::getId());
		}
		else
		{
			unset($botFields['UF_DEPARTMENT']);
		}

		$botFields['WORK_POSITION'] = isset($botFields['WORK_POSITION'])? trim($botFields['WORK_POSITION']): '';
		if (empty($botFields['WORK_POSITION']))
		{
			$botFields['WORK_POSITION'] = Loc::getMessage('BOT_DEFAULT_WORK_POSITION');
		}

		$user = new \CUser;
		$botId = $user->Add($botFields);
		if (!$botId)
		{
			return false;
		}

		$result = \Bitrix\Im\Model\BotTable::add(Array(
			'BOT_ID' => $botId,
			'CODE' => $code? $code: $botId,
			'MODULE_ID' => $moduleId,
			'CLASS' => $class,
			'TYPE' => $type,
			'LANG' => $language? $language: '',
			'METHOD_BOT_DELETE' => $methodBotDelete,
			'METHOD_MESSAGE_ADD' => $methodMessageAdd,
			'METHOD_WELCOME_MESSAGE' => $methodWelcomeMessage,
			'TEXT_PRIVATE_WELCOME_MESSAGE' => $textPrivateWelcomeMessage,
			'TEXT_CHAT_WELCOME_MESSAGE' => $textChatWelcomeMessage,
			'APP_ID' => $appId,
			'VERIFIED' => $verified,
			'OPENLINE' => $openline
		));

		$cache = \Bitrix\Main\Data\Cache::createInstance();
		$cache->cleanDir(self::CACHE_PATH);

		if ($result->isSuccess())
		{
			if (\Bitrix\Main\Loader::includeModule('pull'))
			{
				$botForJs = self::getListForJs();

				if ($color)
				{
					\CIMStatus::SetColor($botId, $color);
				}

				$userData = \CIMContactList::GetUserData(array(
						'ID' => $botId,
						'DEPARTMENT' => 'Y',
						'USE_CACHE' => 'N',
						'SHOW_ONLINE' => 'N',
						'PHONES' => 'N'
					)
				);
				\CPullStack::AddShared(Array(
					'module_id' => 'im',
					'command' => 'addBot',
					'params' => Array(
						'users' => $userData['users'],
						'userInGroup' => $userData['userInGroup'],
						'woUserInGroup' => $userData['woUserInGroup'],
						'bot' => Array($botId => $botForJs[$botId])
					)
				));
				if ($installType != self::INSTALL_TYPE_SILENT)
				{
					$message = '';
					if ($installType == self::INSTALL_TYPE_USER && \Bitrix\Im\User::getInstance($userId)->isExists())
					{
						$userName = \Bitrix\Im\User::getInstance($userId)->getFullName();
						$userName = '[USER='.$userId.']'.$userName.'[/USER]';
						$userGender = \Bitrix\Im\User::getInstance($userId)->getGender();
						$message = Loc::getMessage('BOT_MESSAGE_INSTALL_USER'.($userGender == 'F'? '_F':''), Array('#USER_NAME#' => $userName));
					}
					if (empty($message))
					{
						$message = Loc::getMessage('BOT_MESSAGE_INSTALL_SYSTEM');
					}

					$attach = new \CIMMessageParamAttach(null, $color);
					$attach->AddBot(Array(
						"NAME" => \Bitrix\Im\User::getInstance($botId)->getFullName(),
						"AVATAR" => \Bitrix\Im\User::getInstance($botId)->getAvatar(),
						"BOT_ID" => $botId,
					));
					$attach->addMessage(\Bitrix\Im\User::getInstance($botId)->getWorkPosition());

					\CIMChat::AddGeneralMessage(Array(
						'MESSAGE' => $message,
						'ATTACH' => $attach
					));
				}
			}
		}
		else
		{
			$user->Delete($botId);
			$botId = 0;
		}

		return $botId;
	}

	public static function unRegister(array $bot)
	{
		$botId = intval($bot['BOT_ID']);
		$moduleId = isset($bot['MODULE_ID'])? $bot['MODULE_ID']: '';
		$appId = isset($bot['APP_ID'])? $bot['APP_ID']: '';

		if (intval($botId) <= 0)
			return false;

		$bots = self::getListCache();
		if (!isset($bots[$botId]))
			return false;

		if (strlen($moduleId) > 0 && $bots[$botId]['MODULE_ID'] != $moduleId)
			return false;

		if (strlen($appId) > 0 && $bots[$botId]['APP_ID'] != $appId)
			return false;

		\Bitrix\Im\Model\BotTable::delete($botId);

		$orm = \Bitrix\Im\Model\BotChatTable::getList(Array(
			'filter' => Array('=BOT_ID' => $botId)
		));
		if ($row = $orm->fetch())
		{
			\Bitrix\Im\Model\BotChatTable::delete($row['ID']);
		}

		$cache = \Bitrix\Main\Data\Cache::createInstance();
		$cache->cleanDir(self::CACHE_PATH);

		$user = new \CUser;
		$user->Delete($botId);

		if (\Bitrix\Main\Loader::includeModule($bots[$botId]['MODULE_ID']) && $bots[$botId]["METHOD_BOT_DELETE"] && class_exists($bots[$botId]["CLASS"]) && method_exists($bots[$botId]["CLASS"], $bots[$botId]["METHOD_BOT_DELETE"]))
		{
			call_user_func_array(array($bots[$botId]["CLASS"], $bots[$botId]["METHOD_BOT_DELETE"]), Array($botId));
		}

		foreach(\Bitrix\Main\EventManager::getInstance()->findEventHandlers("im", "onImBotDelete") as $event)
		{
			ExecuteModuleEventEx($event, Array($bots[$botId], $botId));
		}

		$orm = \Bitrix\Im\Model\CommandTable::getList(Array(
			'filter' => Array('=BOT_ID' => $botId)
		));
		while ($row = $orm->fetch())
		{
			\Bitrix\Im\Command::unRegister(Array('COMMAND_ID' => $row['ID'], 'FORCE' => 'Y'));
		}

		if (\Bitrix\Main\Loader::includeModule('pull'))
		{
			\CPullStack::AddShared(Array(
				'module_id' => 'im',
				'command' => 'deleteBot',
				'params' => Array(
					'botId' => $botId
				)
			));
		}

		return true;
	}

	public static function update(array $bot, array $updateFields)
	{
		$botId = intval($bot['BOT_ID']);
		$moduleId = isset($bot['MODULE_ID'])? $bot['MODULE_ID']: '';
		$appId = isset($bot['APP_ID'])? $bot['APP_ID']: '';

		if ($botId <= 0)
			return false;

		if (!\Bitrix\Im\User::getInstance($botId)->isExists() || !\Bitrix\Im\User::getInstance($botId)->isBot())
			return false;

		$bots = self::getListCache();
		if (!isset($bots[$botId]))
			return false;

		if (strlen($moduleId) > 0 && $bots[$botId]['MODULE_ID'] != $moduleId)
			return false;

		if (strlen($appId) > 0 && $bots[$botId]['APP_ID'] != $appId)
			return false;

		if (isset($updateFields['PROPERTIES']))
		{
			$update = $updateFields['PROPERTIES'];
			// update user properties
			unset($update['ACTIVE']);
			unset($update['LOGIN']);
			unset($update['PASSWORD']);
			unset($update['CONFIRM_PASSWORD']);
			unset($update['EXTERNAL_AUTH_ID']);
			unset($update['GROUP_ID']);
			unset($update['UF_DEPARTMENT']);

			$user = new \CUser;
			$user->Update($botId, $update);
		}

		$update = Array();
		if (isset($updateFields['CLASS']) && !empty($updateFields['CLASS']))
		{
			$update['CLASS'] = $updateFields['CLASS'];
		}
		if (isset($updateFields['CODE']) && !empty($updateFields['CODE']))
		{
			$update['CODE'] = $updateFields['CODE'];
		}
		if (isset($updateFields['TYPE']))
		{
			$update['TYPE'] = in_array($updateFields['TYPE'], Array(self::TYPE_HUMAN, self::TYPE_BOT, self::TYPE_NETWORK, self::TYPE_OPENLINE))? $updateFields['TYPE']: self::TYPE_BOT;
		}
		if (isset($updateFields['LANG']))
		{
			$update['LANG'] = $updateFields['LANG']? $updateFields['LANG']: '';
		}
		if (isset($updateFields['METHOD_BOT_DELETE']))
		{
			$update['METHOD_BOT_DELETE'] = $updateFields['METHOD_BOT_DELETE'];
		}
		if (isset($updateFields['METHOD_MESSAGE_ADD']))
		{
			$update['METHOD_MESSAGE_ADD'] = $updateFields['METHOD_MESSAGE_ADD'];
		}
		if (isset($updateFields['METHOD_WELCOME_MESSAGE']))
		{
			$update['METHOD_WELCOME_MESSAGE'] = $updateFields['METHOD_WELCOME_MESSAGE'];
		}
		if (isset($updateFields['TEXT_PRIVATE_WELCOME_MESSAGE']))
		{
			$update['TEXT_PRIVATE_WELCOME_MESSAGE'] = $updateFields['TEXT_PRIVATE_WELCOME_MESSAGE'];
		}
		if (isset($updateFields['TEXT_CHAT_WELCOME_MESSAGE']))
		{
			$update['TEXT_CHAT_WELCOME_MESSAGE'] = $updateFields['TEXT_CHAT_WELCOME_MESSAGE'];
		}
		if (isset($updateFields['VERIFIED']))
		{
			$update['VERIFIED'] = $updateFields['VERIFIED'] == 'Y'? 'Y': 'N';
		}
		if (isset($updateFields['OPENLINE']))
		{
			$update['OPENLINE'] = $updateFields['OPENLINE'] == 'Y'? 'Y': 'N';
		}
		if (!empty($update))
		{
			\Bitrix\Im\Model\BotTable::update($botId, $update);

			$cache = \Bitrix\Main\Data\Cache::createInstance();
			$cache->cleanDir(self::CACHE_PATH);
		}

		if (\Bitrix\Main\Loader::includeModule('pull'))
		{
			$botForJs = self::getListForJs();

			$userData = \CIMContactList::GetUserData(array(
					'ID' => $botId,
					'DEPARTMENT' => 'Y',
					'USE_CACHE' => 'N',
					'SHOW_ONLINE' => 'N',
					'PHONES' => 'N'
				)
			);
			\CPullStack::AddShared(Array(
				'module_id' => 'im',
				'command' => 'updateBot',
				'params' => Array(
					'users' => $userData['users'],
					'userInGroup' => $userData['userInGroup'],
					'woUserInGroup' => $userData['woUserInGroup'],
					'bot' => Array($botId => $botForJs[$botId])
				)
			));
		}

		return true;
	}

	public static function onMessageAdd($messageId, $messageFields)
	{
		$bots = self::getListCache();
		if (empty($bots))
			return true;

		if (isset($bots[$messageFields['FROM_USER_ID']]))
			return false;

		$botExecModule = Array();
		if ($messageFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			if (isset($bots[$messageFields['TO_USER_ID']]))
			{
				$botData = self::findBots(Array(
					'BOT_ID' => $messageFields['TO_USER_ID'],
					'TYPE' => $messageFields['MESSAGE_TYPE'],
				));
				if (!empty($botData))
				{
					$botExecModule[$messageFields['TO_USER_ID']] = $botData;
				}
			}
		}
		else
		{
			$botFound = Array();
			if ($messageFields['CHAT_ENTITY_TYPE'] == 'LINES')
			{
				$botFound = $messageFields['BOT_IN_CHAT'];
			}
			else if (preg_match_all("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", $messageFields['MESSAGE'], $matches))
			{
				foreach($matches[1] as $userId)
				{
					if (isset($bots[$userId]))
					{
						$botFound[$userId] = $userId;
					}
				}
			}
			if (!empty($botFound))
			{
				foreach ($botFound as $botId)
				{
					$botData = self::findBots(Array(
						'BOT_ID' => $botId,
						'CHAT_ID' => $messageFields['TO_CHAT_ID'],
						'TYPE' => $messageFields['MESSAGE_TYPE'],
					));
					if ($messageFields['CHAT_ENTITY_TYPE'] == 'LINES' && $botData['OPENLINE'] == 'N')
					{
						continue;
					}
					if (!empty($botData))
					{
						$botExecModule[$botId] = $botData;
					}
				}
				$messageFields['MESSAGE_ORIGINAL'] = $messageFields['MESSAGE'];
				$messageFields['MESSAGE'] = trim(preg_replace('#\[(?P<tag>USER)=\d+\].+?\[/(?P=tag)\],?#', '', $messageFields['MESSAGE']));
			}
		}

		if (!empty($botExecModule))
		{
			$messageFields['DIALOG_ID'] = \Bitrix\Im\Bot::getDialogId($messageFields);
			unset($messageFields['MESSAGE_OUT']);
			unset($messageFields['NOTIFY_EVENT']);
			unset($messageFields['NOTIFY_MODULE']);
			unset($messageFields['URL_PREVIEW']);

			foreach ($botExecModule as $params)
			{
				if (!$params['MODULE_ID'] || !\Bitrix\Main\Loader::includeModule($params['MODULE_ID']))
					continue;

				$messageFields['BOT_ID'] = $params['BOT_ID'];

				if ($params["METHOD_MESSAGE_ADD"] && class_exists($params["CLASS"]) && method_exists($params["CLASS"], $params["METHOD_MESSAGE_ADD"]))
				{
					\Bitrix\Im\Model\BotTable::update($params['BOT_ID'], array(
						"COUNT_MESSAGE" => new \Bitrix\Main\DB\SqlExpression("?# + 1", "COUNT_MESSAGE")
					));

					call_user_func_array(array($params["CLASS"], $params["METHOD_MESSAGE_ADD"]), Array($messageId, $messageFields));
				}
			}
			unset($messageFields['BOT_ID']);

			foreach(\Bitrix\Main\EventManager::getInstance()->findEventHandlers("im", "onImBotMessageAdd") as $event)
			{
				ExecuteModuleEventEx($event, Array($botExecModule, $messageId, $messageFields));
			}

			if ($messageFields['CHAT_ENTITY_TYPE'] == 'LINES' && trim($messageFields['MESSAGE']) == '0' && \Bitrix\Main\Loader::includeModule('imopenlines'))
			{
				$chat = new \Bitrix\Imopenlines\Chat($messageFields['TO_CHAT_ID']);
				$chat->endBotSession();
			}
		}


		return true;
	}

	public static function onJoinChat($dialogId, $joinFields)
	{
		$bots = self::getListCache();
		if (empty($bots))
			return true;

		if (!isset($joinFields['BOT_ID']) || !$bots[$joinFields['BOT_ID']])
			return false;

		$bot = $bots[$joinFields['BOT_ID']];

		if (!\Bitrix\Main\Loader::includeModule($bot['MODULE_ID']))
			return false;

		if ($joinFields['CHAT_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$updateCounter = array("COUNT_USER" => new \Bitrix\Main\DB\SqlExpression("?# + 1", "COUNT_USER"));
		}
		else
		{
			$updateCounter = array("COUNT_CHAT" => new \Bitrix\Main\DB\SqlExpression("?# + 1", "COUNT_CHAT"));
		}
		\Bitrix\Im\Model\BotTable::update($joinFields['BOT_ID'], $updateCounter);

		if ($bot["METHOD_WELCOME_MESSAGE"] && class_exists($bot["CLASS"]) && method_exists($bot["CLASS"], $bot["METHOD_WELCOME_MESSAGE"]))
		{
			call_user_func_array(array($bot["CLASS"], $bot["METHOD_WELCOME_MESSAGE"]), Array($dialogId, $joinFields));
		}
		else if (strlen($bot["TEXT_PRIVATE_WELCOME_MESSAGE"]) > 0 && $joinFields['CHAT_TYPE'] == IM_MESSAGE_PRIVATE && $joinFields['FROM_USER_ID'] != $joinFields['BOT_ID'])
		{
			if ($bot['TYPE'] == self::TYPE_HUMAN)
			{
				\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => $joinFields['BOT_ID']), $dialogId);
			}

			$userName = \Bitrix\Im\User::getInstance($joinFields['USER_ID'])->getName();
			\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => $joinFields['BOT_ID']), Array(
				'DIALOG_ID' => $dialogId,
				'MESSAGE' => str_replace(Array('#USER_NAME#'), Array($userName), $bot["TEXT_PRIVATE_WELCOME_MESSAGE"]),
			));
		}
		else if (strlen($bot["TEXT_CHAT_WELCOME_MESSAGE"]) > 0 && $joinFields['CHAT_TYPE'] == IM_MESSAGE_CHAT && $joinFields['FROM_USER_ID'] != $joinFields['BOT_ID'])
		{
			if ($bot['TYPE'] == self::TYPE_HUMAN)
			{
				\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => $joinFields['BOT_ID']), $dialogId);
			}
			$userName = \Bitrix\Im\User::getInstance($joinFields['USER_ID'])->getName();
			\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => $joinFields['BOT_ID']), Array(
				'DIALOG_ID' => $dialogId,
				'MESSAGE' => str_replace(Array('#USER_NAME#'), Array($userName), $bot["TEXT_CHAT_WELCOME_MESSAGE"]),
			));
		}

		foreach(\Bitrix\Main\EventManager::getInstance()->findEventHandlers("im", "onImBotJoinChat") as $event)
		{
			ExecuteModuleEventEx($event, Array($bot, $dialogId, $joinFields));
		}

		return true;
	}

	public static function startWriting(array $bot, $dialogId)
	{
		$botId = $bot['BOT_ID'];
		$moduleId = isset($bot['MODULE_ID'])? $bot['MODULE_ID']: '';
		$appId = isset($bot['APP_ID'])? $bot['APP_ID']: '';

		if (intval($botId) <= 0)
			return false;

		if (!\Bitrix\Im\User::getInstance($botId)->isExists() || !\Bitrix\Im\User::getInstance($botId)->isBot())
			return false;

		$bots = self::getListCache();
		if (!isset($bots[$botId]))
			return false;

		if (strlen($moduleId) > 0 && $bots[$botId]['MODULE_ID'] != $moduleId)
			return false;

		if (strlen($appId) > 0 && $bots[$botId]['APP_ID'] != $appId)
			return false;

		\CIMMessenger::StartWriting($dialogId, $botId);

		return true;
	}

	public static function addMessage(array $bot, array $messageFields)
	{
		$botId = $bot['BOT_ID'];
		$moduleId = isset($bot['MODULE_ID'])? $bot['MODULE_ID']: '';
		$appId = isset($bot['APP_ID'])? $bot['APP_ID']: '';

		if (intval($botId) <= 0)
			return false;

		if (!\Bitrix\Im\User::getInstance($botId)->isExists() || !\Bitrix\Im\User::getInstance($botId)->isBot())
			return false;

		$bots = self::getListCache();
		if (!isset($bots[$botId]))
			return false;

		if (strlen($moduleId) > 0 && $bots[$botId]['MODULE_ID'] != $moduleId)
			return false;

		if (strlen($appId) > 0 && $bots[$botId]['APP_ID'] != $appId)
			return false;

		if (empty($messageFields['DIALOG_ID']))
			return false;

		$messageFields['ATTACH'] = $messageFields['ATTACH']? $messageFields['ATTACH']: null;
		$messageFields['KEYBOARD'] = $messageFields['KEYBOARD']? $messageFields['KEYBOARD']: null;
		$messageFields['PARAMS'] = $messageFields['PARAMS']? $messageFields['PARAMS']: Array();

		if (self::isChat($messageFields['DIALOG_ID']))
		{
			$chatId = intval(substr($messageFields['DIALOG_ID'], 4));
			if ($chatId <= 0)
				return false;

			if (\CIMChat::GetGeneralChatId() == $chatId && !\CIMChat::CanSendMessageToGeneralChat($botId))
			{
				return false;
			}
			else
			{
				$ar = Array(
					"FROM_USER_ID" => $botId,
					"TO_CHAT_ID" => $chatId,
					"ATTACH" => $messageFields['ATTACH'],
					"KEYBOARD" => $messageFields['KEYBOARD'],
					"PARAMS" => $messageFields['PARAMS'],
				);
				if (isset($messageFields['MESSAGE']) && (!empty($messageFields['MESSAGE']) || $messageFields['MESSAGE'] === "0"))
				{
					$ar['MESSAGE'] = $messageFields['MESSAGE'];
				}
				if (isset($messageFields['SYSTEM']) && $messageFields['SYSTEM'] == 'Y')
				{
					$ar['SYSTEM'] = 'Y';
					$ar['MESSAGE'] = "[USER=".$botId."]".\Bitrix\Im\User::getInstance($botId)->getFullName()."[/USER]\n".$ar['MESSAGE'];
				}
				if (isset($messageFields['URL_PREVIEW']) && $messageFields['URL_PREVIEW'] == 'N')
				{
					$ar['URL_PREVIEW'] = 'N';
				}
				$ar['SKIP_COMMAND'] = 'Y';
				$id = \CIMChat::AddMessage($ar);
			}
		}
		else
		{
			$userId = intval($messageFields['DIALOG_ID']);
			$ar = Array(
				"FROM_USER_ID" => $botId,
				"TO_USER_ID" => $userId,
				"ATTACH" => $messageFields['ATTACH'],
				"KEYBOARD" => $messageFields['KEYBOARD'],
				"PARAMS" => $messageFields['PARAMS'],
			);
			if (isset($messageFields['MESSAGE']) && (!empty($messageFields['MESSAGE']) || $messageFields['MESSAGE'] === "0"))
			{
				$ar['MESSAGE'] = $messageFields['MESSAGE'];
			}
			if (isset($messageFields['SYSTEM']) && $messageFields['SYSTEM'] == 'Y')
			{
				$ar['SYSTEM'] = 'Y';
			}
			if (isset($messageFields['URL_PREVIEW']) && $messageFields['URL_PREVIEW'] == 'N')
			{
				$ar['URL_PREVIEW'] = 'N';
			}
			$ar['SKIP_COMMAND'] = 'Y';
			$id = \CIMMessage::Add($ar);
		}

		return $id;
	}
	
	public static function updateMessage(array $bot, array $messageFields)
	{
		$botId = $bot['BOT_ID'];
		$moduleId = isset($bot['MODULE_ID'])? $bot['MODULE_ID']: '';
		$appId = isset($bot['APP_ID'])? $bot['APP_ID']: '';

		if (intval($botId) <= 0)
			return false;

		if (!\Bitrix\Im\User::getInstance($botId)->isExists() || !\Bitrix\Im\User::getInstance($botId)->isBot())
			return false;

		$bots = self::getListCache();
		if (!isset($bots[$botId]))
			return false;

		if (strlen($moduleId) > 0 && $bots[$botId]['MODULE_ID'] != $moduleId)
			return false;

		if (strlen($appId) > 0 && $bots[$botId]['APP_ID'] != $appId)
			return false;

		$messageId = intval($messageFields['MESSAGE_ID']);
		if ($messageId <= 0)
			return false;

		$message = \CIMMessenger::CheckPossibilityUpdateMessage($messageId, $botId);
		if (!$message)
			return false;

		if (isset($messageFields['ATTACH']))
		{
			if (empty($messageFields['ATTACH']) || $messageFields['ATTACH'] == 'N')
			{
				\CIMMessageParam::Set($messageId, Array('ATTACH' => Array()));
			}
			else if ($messageFields['ATTACH'] instanceof \CIMMessageParamAttach)
			{
				if ($messageFields['ATTACH']->IsAllowSize())
				{
					\CIMMessageParam::Set($messageId, Array('ATTACH' => $messageFields['ATTACH']));
				}
			}
		}

		if (isset($messageFields['KEYBOARD']))
		{
			if (empty($messageFields['KEYBOARD']) || $messageFields['KEYBOARD'] == 'N')
			{
				\CIMMessageParam::Set($messageId, Array('KEYBOARD' => 'N'));
			}
			else if ($messageFields['KEYBOARD'] instanceof \Bitrix\Im\Bot\Keyboard)
			{
				if ($messageFields['KEYBOARD']->isAllowSize())
				{
					\CIMMessageParam::Set($messageId, Array('KEYBOARD' => $messageFields['KEYBOARD']));
				}
			}
		}

		if (isset($messageFields['MESSAGE']))
		{
			$urlPreview = isset($messageFields['URL_PREVIEW']) && $messageFields['URL_PREVIEW'] == "N"? false: true;

			$res = \CIMMessenger::Update($messageId, $messageFields['MESSAGE'], $urlPreview, false, $botId);
			if (!$res)
			{
				return false;
			}
		}
		else
		{
			\CIMMessageParam::SendPull($messageId);
		}

		return true;
	}

	public static function deleteMessage(array $bot, $messageId)
	{
		$botId = $bot['BOT_ID'];
		$moduleId = isset($bot['MODULE_ID'])? $bot['MODULE_ID']: '';
		$appId = isset($bot['APP_ID'])? $bot['APP_ID']: '';

		$messageId = intval($messageId);
		if ($messageId <= 0)
			return false;

		if (intval($botId) <= 0)
			return false;

		if (!\Bitrix\Im\User::getInstance($botId)->isExists() || !\Bitrix\Im\User::getInstance($botId)->isBot())
			return false;

		$bots = self::getListCache();
		if (!isset($bots[$botId]))
			return false;

		if (strlen($moduleId) > 0 && $bots[$botId]['MODULE_ID'] != $moduleId)
			return false;

		if (strlen($appId) > 0 && $bots[$botId]['APP_ID'] != $appId)
			return false;

		return \CIMMessenger::Delete($messageId, $botId);
	}

	public static function likeMessage(array $bot, $messageId, $action = 'AUTO')
	{
		$botId = $bot['BOT_ID'];
		$moduleId = isset($bot['MODULE_ID'])? $bot['MODULE_ID']: '';
		$appId = isset($bot['APP_ID'])? $bot['APP_ID']: '';

		$messageId = intval($messageId);
		if ($messageId <= 0)
			return false;

		if (intval($botId) <= 0)
			return false;

		if (!\Bitrix\Im\User::getInstance($botId)->isExists() || !\Bitrix\Im\User::getInstance($botId)->isBot())
			return false;

		$bots = self::getListCache();
		if (!isset($bots[$botId]))
			return false;

		if (strlen($moduleId) > 0 && $bots[$botId]['MODULE_ID'] != $moduleId)
			return false;

		if (strlen($appId) > 0 && $bots[$botId]['APP_ID'] != $appId)
			return false;
		
		return \CIMMessenger::Like($messageId, $action, $botId);
	}

	public static function getDialogId($messageFields)
	{
		if ($messageFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$dialogId = $messageFields['FROM_USER_ID'];
		}
		else
		{
			$dialogId = 'chat'.$messageFields['TO_CHAT_ID'];
		}

		return $dialogId;
	}

	public static function isChat($dialogId)
	{
		$isChat = false;
		if (is_string($dialogId) && substr($dialogId, 0, 4) == 'chat')
		{
			$isChat = true;
		}

		return $isChat;
	}

	private static function findBots($fields)
	{
		$result = Array();
		if (intval($fields['BOT_ID']) <= 0)
			return $result;

		$bots = self::getListCache();
		if ($fields['TYPE'] == IM_MESSAGE_PRIVATE)
		{
			if (isset($bots[$fields['BOT_ID']]))
			{
				$result = $bots[$fields['BOT_ID']];
			}
		}
		else
		{
			if (isset($bots[$fields['BOT_ID']]))
			{
				$chats = self::getChatListCache($fields['BOT_ID']);
				if (isset($chats[$fields['CHAT_ID']]))
				{
					$result = $bots[$fields['BOT_ID']];
				}
			}
		}

		return $result;
	}

	public static function getCache($botId)
	{
		$botList = self::getListCache();
		return isset($botList[$botId])? $botList[$botId]: false;
	}

	public static function getListCache($type = self::LIST_ALL)
	{
		$cache = \Bitrix\Main\Data\Cache::createInstance();
		if($cache->initCache(self::CACHE_TTL, 'list_r4', self::CACHE_PATH))
		{
			$result = $cache->getVars();
		}
		else
		{
			$result = Array();
			$orm = \Bitrix\Im\Model\BotTable::getList();
			while ($row = $orm->fetch())
			{
				$row['LANG'] = $row['LANG']? $row['LANG']: null;
				$result[$row['BOT_ID']] = $row;
			}

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		if ($type == self::LIST_OPENLINE)
		{
			foreach ($result as $botId => $botData)
			{
				if ($botData['OPENLINE'] != 'Y' || $botData['CODE'] == 'marta')
				{
					unset($result[$botId]);
				}
			}
		}

		return $result;
	}

	public static function getListForJs()
	{
		$result = Array();
		$bots = self::getListCache();
		foreach ($bots as $bot)
		{
			$type = 'bot';
			if ($bot['TYPE'] == self::TYPE_HUMAN)
			{
				$type = 'human';
			}
			else if ($bot['TYPE'] == self::TYPE_NETWORK)
			{
				$type = 'network';
			}
			else if ($bot['TYPE'] == self::TYPE_OPENLINE)
			{
				$type = 'openline';
			}

			$result[$bot['BOT_ID']] = Array(
				'id' => $bot['BOT_ID'],
				'code' => $bot['CODE'],
				'type' => $type,
				'openline' => $bot['OPENLINE'] == 'Y',
			);
		}

		return $result;
	}

	private static function getChatListCache($botId)
	{
		$botId = intval($botId);
		if ($botId <= 0)
			return Array();

		$cache = \Bitrix\Main\Data\Cache::createInstance();
		if($cache->initCache(self::CACHE_TTL, 'chat'.$botId, self::CACHE_PATH))
		{
			$result = $cache->getVars();
		}
		else
		{
			$result = Array();
			$orm = \Bitrix\Im\Model\BotChatTable::getList(Array(
				'filter' => Array('=BOT_ID' => $botId)
			));
			while ($row = $orm->fetch())
			{
				$result[$row['CHAT_ID']] = $row;
			}

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		return $result;
	}

	public static function changeChatMembers($chatId, $botId, $append = true)
	{
		$chatId = intval($chatId);
		$botId = intval($botId);

		if ($chatId <= 0 || $botId <= 0)
			return false;

		$chats = self::getChatListCache($botId);

		if ($append)
		{
			if (isset($chats[$chatId]))
			{
				return true;
			}
			\Bitrix\Im\Model\BotChatTable::add(Array(
				'BOT_ID' => $botId,
				'CHAT_ID' => $chatId
			));
		}
		else
		{
			if (!isset($chats[$chatId]))
			{
				return true;
			}

			$orm = \Bitrix\Im\Model\BotChatTable::getList(Array(
				'filter' => Array('=BOT_ID' => $botId, '=CHAT_ID' => $chatId)
			));
			if ($row = $orm->fetch())
			{
				\Bitrix\Im\Model\BotChatTable::delete($row['ID']);
			}
		}

		$cache = \Bitrix\Main\Data\Cache::createInstance();
		$cache->clean('chat'.$botId, self::CACHE_PATH);

		return true;
	}

	public static function getDefaultLanguage()
	{
		$cache = \Bitrix\Main\Data\Cache::createInstance();
		if($cache->initCache(self::CACHE_TTL, 'language_v2', '/bx/im/'))
		{
			$languageId = $cache->getVars();
		}
		else
		{
			$languageId = '';

			$siteIterator = \Bitrix\Main\SiteTable::getList(array(
				'select' => array('LANGUAGE_ID'),
				'filter' => array('=DEF' => 'Y', '=ACTIVE' => 'Y')
			));
			if ($site = $siteIterator->fetch())
				$languageId = (string)$site['LANGUAGE_ID'];

			if ($languageId == '')
			{
				if (\Bitrix\Main\Loader::includeModule('bitrix24'))
				{
					$languageId = \CBitrix24::getLicensePrefix();
				}
				else
				{
					$languageId = LANGUAGE_ID;
				}
			}
			if ($languageId == '')
			{
				$languageId = 'en';
			}

			$languageId = strtolower($languageId);

			$cache->startDataCache();
			$cache->endDataCache($languageId);
		}

		return $languageId;
	}

	public static function deleteExpiredTokenAgent()
	{
		$orm = \Bitrix\Im\Model\BotTokenTable::getList(Array(
			'filter' => array(
				'<DATE_EXPIRE' => new \Bitrix\Main\Type\DateTime(),
			),
			'select' => array('ID'),
			'limit' => 1
		));
		if ($token = $orm->fetch())
		{
			$application = \Bitrix\Main\Application::getInstance();
			$connection = $application->getConnection();
			$sqlHelper = $connection->getSqlHelper();
			$connection->query("
				DELETE FROM b_im_bot_token
				WHERE DATE_EXPIRE < ".$sqlHelper->getCurrentDateTimeFunction()."
			");
		}

		return "\\Bitrix\\Im\\Bot::deleteExpiredTokenAgent();";
	}
}