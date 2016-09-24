<?
class CAllPullStack
{
	// receive messages on stack
	// only works in PULL mode
	public static function Get($channelId, $lastId = 0)
	{
		global $DB;

		$newLastId = $lastId;
		$arMessage = Array();
		$strSql = "
				SELECT ps.ID, ps.MESSAGE
				FROM b_pull_stack ps ".($lastId > 0? '': 'LEFT JOIN b_pull_channel pc ON pc.CHANNEL_ID = ps.CHANNEL_ID')."
				WHERE ps.CHANNEL_ID = '".$DB->ForSQL($channelId)."'".($lastId > 0? " AND ps.ID > ".intval($lastId): " AND ps.ID > pc.LAST_ID" );
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
		{
			if ($newLastId < $arRes['ID'])
				$newLastId = $arRes['ID'];

			$data = unserialize($arRes['MESSAGE']);
			$data['id'] = $arRes['ID'];

			$arMessage[] = $data;
		}

		if ($lastId < $newLastId)
			CPullChannel::UpdateLastId($channelId, $newLastId);

		return $arMessage;
	}

	// add a message to stack
	public static function AddByChannel($channelId, $arParams = Array())
	{
		global $DB;

		if (!is_array($channelId))
			$channelId = Array($channelId);

		$result = false;
		if (strlen($arParams['module_id']) > 0 || strlen($arParams['command']) > 0)
		{
			$arData = Array(
				'module_id' => $arParams['module_id'],
				'command' => $arParams['command'],
				'params' => is_array($arParams['params'])? $arParams['params']: Array(),
			);
			if (CPullOptions::GetQueueServerStatus())
			{
				$command = Array('SERVER_TIME_WEB' => time(), 'SERVER_NAME' => COption::GetOptionString('main', 'server_name', $_SERVER['SERVER_NAME']), 'MESSAGE' => Array($arData), 'ERROR' => '');
				if (!is_array($channelId) && CPullOptions::GetQueueServerVersion() == 1)
					$command['CHANNEL_ID'] = $channelId;

				$message = CUtil::PhpToJsObject($command);
				if (!defined('BX_UTF') || !BX_UTF)
					$message = $GLOBALS['APPLICATION']->ConvertCharset($message, SITE_CHARSET,'utf-8');

				$options = isset($arParams['expiry']) ? array('expiry' => intval($arParams['expiry'])) : array();
				$res = CPullChannel::Send($channelId, str_replace("\n", " ", $message), $options);
				$result = $res? true: false;
			}
			else
			{
				foreach ($channelId as $channel)
				{
					$arParams = Array(
						'CHANNEL_ID' => $channel,
						'MESSAGE' => str_replace("\n", " ", serialize($arData)),
						'~DATE_CREATE' => $DB->CurrentTimeFunction(),
					);
					$res = IntVal($DB->Add("b_pull_stack", $arParams, Array("MESSAGE")));
					$result = $res? true: false;
				}
			}

			return $result;
		}

		return false;
	}

	public static function AddByUser($userId, $arMessage, $channelType = 'private')
	{
		$userId = intval($userId);
		if ($userId == 0)
			return false;

		return self::AddByUsers(Array($userId), $arMessage, $channelType);
	}

	public static function AddByUsers($userIds, $arMessage, $channelType = 'private')
	{
		if (!is_array($userIds))
			return false;

		$arPush = Array();
		if (isset($arMessage['push']))
		{
			$arPush = $arMessage['push'];
			unset($arMessage['push']);
		}

		$channels = Array();
		foreach ($userIds as $userId)
		{
			$userId = intval($userId);
			if ($userId != 0)
			{
				$arChannel = CPullChannel::GetChannel($userId, $channelType);
				$channels[$userId] = $arChannel['CHANNEL_ID'];
			}
		}

		if (empty($channels))
			return false;

		$result = self::AddByChannel($channels, $arMessage);

		if ($result && !empty($arPush) && (isset($arPush['advanced_params']) || isset($arPush['message']) && strlen($arPush['message']) > 0))
		{
			$CPushManager = new CPushManager();

			$pushUsers = Array();
			foreach ($channels as $userId => $channelId)
			{
				if (isset($arPush['skip_users']) && in_array($userId, $arPush['skip_users']))
					continue;

				$pushUsers[] = $userId;
			}
			$CPushManager->AddQueue(Array(
				'USER_ID' => $pushUsers,
				'MESSAGE' => str_replace("\n", " ", $arPush['message']),
				'PARAMS' => $arPush['params'],
				'ADVANCED_PARAMS' => isset($arPush['advanced_params'])? $arPush['advanced_params']: Array(),
				'BADGE' => isset($arPush['badge'])? intval($arPush['badge']): '',
				'SOUND' => isset($arPush['sound'])? $arPush['sound']: '',
				'TAG' => isset($arPush['tag'])? $arPush['tag']: '',
				'SUB_TAG' => isset($arPush['sub_tag'])? $arPush['sub_tag']: '',
				'APP_ID' => isset($arPush['app_id'])? $arPush['app_id']: '',
				'SEND_IMMEDIATELY' => isset($arPush['send_immediately']) && $arPush['send_immediately'] == 'Y'? 'Y': 'N',
			));
		}

		return $result;
	}

	public static function AddShared($arMessage, $channelType = 'shared')
	{
		if (!CPullOptions::GetQueueServerStatus())
			return false;

		$arChannel = CPullChannel::GetChannelShared($channelType);
		return self::AddByChannel($arChannel['CHANNEL_ID'], $arMessage);
	}

	public static function AddBroadcast($arMessage)
	{
		global $DB;

		$strSql = "SELECT CHANNEL_ID, USER_ID FROM b_pull_channel";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arChannels = Array();
		while ($arRes = $dbRes->Fetch())
		{
			if ($arRes['USER_ID'] != 0)
				continue;

			$arChannels[] = $arRes['CHANNEL_ID'];
		}
		if(!self::AddByChannel($arChannels, $arMessage))
			return false;

		return true;
	}
}
?>