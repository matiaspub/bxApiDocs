<?
IncludeModuleLangFile(__FILE__);

class CPullChannel
{
	public static function GetShared($cache = true, $reOpen = false)
	{
		return self::Get(0, $cache, $reOpen);
	}

	public static function GetNewChannelId()
	{
		global $APPLICATION;
		return md5(uniqid().$_SERVER["REMOTE_ADDR"].$_SERVER["SERVER_NAME"].(is_object($APPLICATION)? $APPLICATION->GetServerUniqID(): ''));
	}

	public static function Get($userId, $cache = true, $reOpen = false)
	{
		global $DB, $CACHE_MANAGER;

		$nginxStatus = CPullOptions::GetQueueServerStatus();

		$arResult = false;
		$userId = intval($userId);
		$cache_id="b_pchc_".$userId;

		if ($nginxStatus && $cache)
		{
			$res = $CACHE_MANAGER->Read(43200, $cache_id, "b_pull_channel");
			if ($res)
				$arResult = $CACHE_MANAGER->Get($cache_id);
		}
		if(!is_array($arResult) || !isset($arResult['CHANNEL_ID']))
		{
			$arResult = Array();
			CTimeZone::Disable();
			$strSql = "
					SELECT CHANNEL_ID, ".$DB->DatetimeToTimestampFunction('DATE_CREATE')." DATE_CREATE, LAST_ID
					FROM b_pull_channel
					WHERE USER_ID = ".$userId."
			";
			CTimeZone::Enable();
			$res = $DB->Query($strSql);
			if ($arRes = $res->Fetch())
				$arResult = $arRes;

			if ($nginxStatus)
				self::SaveToCache($cache_id, $arResult);
		}
		if (empty($arResult) || intval($arResult['DATE_CREATE'])+43200 < time())
		{
			$arChannel = Array(
				'CHANNEL_ID' => self::GetNewChannelId(),
				'DATE_CREATE' => time(),
				'LAST_ID' => 0,
			);
			self::SaveToCache($cache_id, $arChannel);

			if (isset($arResult['CHANNEL_ID']))
			{
				$strSql = "DELETE FROM b_pull_channel WHERE CHANNEL_ID = '".$arResult['CHANNEL_ID']."'";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}

			$channelId = self::Add($userId, $arChannel['CHANNEL_ID']);

			if (isset($arResult['CHANNEL_ID']) && $channelId != $arResult['CHANNEL_ID'])
			{
				$arMessage = Array(
					'module_id' => 'pull',
					'command' => 'channel_die',
					'params' => Array('from' => 'delete by channel')
				);
				CPullStack::AddByChannel($arResult['CHANNEL_ID'], $arMessage);
			}

			return $channelId? Array(
				'CHANNEL_ID' => $channelId,
				'CHANNEL_DT' => time(),
				'LAST_ID' => 0,
			): false;
		}
		else
		{
			if ($nginxStatus && $reOpen)
			{
				$arData = Array(
					'module_id' => 'pull',
					'command' => 'reopen',
					'params' => Array(),
				);
				self::Send($arResult['CHANNEL_ID'], CUtil::PhpToJsObject(Array('MESSAGE' => Array($arData), 'ERROR' => '')));
			}
			return Array(
				'CHANNEL_ID' => $arResult['CHANNEL_ID'],
				'CHANNEL_DT' => $arResult['DATE_CREATE'],
				'LAST_ID' => $arResult['LAST_ID'],
			);
		}
	}

	// create a channel for the user
	public static function Add($userId, $channelId = null)
	{
		global $DB;

		$userId = intval($userId);
		$cache_id="b_pchc_".$userId;

		$channelId = is_null($channelId)? self::GetNewChannelId(): $channelId;
		$arParams = Array(
			'USER_ID' => intval($userId),
			'CHANNEL_ID' => $channelId,
			'LAST_ID' => 0,
			'~DATE_CREATE' => $DB->CurrentTimeFunction(),
		);
		$result = intval($DB->Add("b_pull_channel", $arParams, Array(), "", true));
		if ($result > 0)
		{
			$arChannel = Array(
				'CHANNEL_ID' => $channelId,
				'DATE_CREATE' => time(),
				'LAST_ID' => 0,
			);
			self::SaveToCache($cache_id, $arChannel);

			if (CPullOptions::GetQueueServerStatus())
			{
				$arData = Array(
					'module_id' => 'pull',
					'command' => 'open',
					'params' => Array(),
				);
				self::Send($channelId, CUtil::PhpToJsObject(Array('MESSAGE' => Array($arData), 'ERROR' => '')));
			}
		}
		else
		{
			CTimeZone::Disable();
			$strSql = "
					SELECT CHANNEL_ID, ".$DB->DatetimeToTimestampFunction('DATE_CREATE')." DATE_CREATE, LAST_ID
					FROM b_pull_channel
					WHERE USER_ID = ".$userId."
			";
			CTimeZone::Enable();
			$res = $DB->Query($strSql);
			$arChannel = $res->Fetch();
			$channelId = $arChannel['CHANNEL_ID'];
			self::SaveToCache($cache_id, $arChannel);

			if (CPullOptions::GetQueueServerStatus())
			{
				$arData = Array(
					'module_id' => 'pull',
					'command' => 'open_exists',
					'params' => Array(),
				);
				self::Send($channelId, CUtil::PhpToJsObject(Array('MESSAGE' => Array($arData), 'ERROR' => '')));
			}
		}

		return $channelId;
	}

	// remove channel by identifier
	// before removing need to send a message to change channel
	public static function Delete($channelId)
	{
		global $DB, $CACHE_MANAGER;

		$strSql = "SELECT USER_ID FROM b_pull_channel WHERE CHANNEL_ID = '".$DB->ForSQL($channelId)."'";
		$res = $DB->Query($strSql);
		if ($arRes = $res->Fetch())
		{
			$strSql = "DELETE FROM b_pull_channel WHERE USER_ID = ".$arRes['USER_ID'];
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$CACHE_MANAGER->Clean("b_pchc_".intval($arRes['USER_ID']), "b_pull_channel");

			$arMessage = Array(
				'module_id' => 'pull',
				'command' => 'channel_die',
				'params' => Array('from' => 'delete by channel')
			);
			CPullStack::AddByChannel($channelId, $arMessage);
		}

		return true;
	}

	public static function DeleteByUser($userId, $channelId = null)
	{
		global $DB, $CACHE_MANAGER;

		$userId = intval($userId);

		if (is_null($channelId))
		{
			$strSql = "SELECT CHANNEL_ID FROM b_pull_channel WHERE USER_ID = ".$userId;
			$res = $DB->Query($strSql);
			if ($arRes = $res->Fetch())
				$channelId = $arRes['CHANNEL_ID'];
		}

		$strSql = "DELETE FROM b_pull_channel WHERE USER_ID = ".$userId;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$CACHE_MANAGER->Clean("b_pchc_".$userId, "b_pull_channel");

		$arMessage = Array(
			'module_id' => 'pull',
			'command' => 'channel_die',
			'params' => Array('from' => 'delete by user')
		);
		CPullStack::AddByChannel($channelId, $arMessage);

		return true;
	}

	public static function Send($channelId, $message, $method = 'POST', $timeout = 5, $dont_wait_answer = true)
	{
		$result_start = '{"infos": ['; $result_end = ']}';
		if (is_array($channelId) && CPullOptions::GetQueueServerVersion() == 1)
		{
			$results = Array();
			foreach ($channelId as $channel)
			{
				$results[] = self::SendCommand($channel, $message, $method, $timeout, $dont_wait_answer);
			}
			$result = json_decode($result_start.implode(',', $results).$result_end);
		}
		else if (is_array($channelId))
		{
			$commandPerHit = CPullOptions::GetCommandPerHit();
			if (count($channelId) > $commandPerHit)
			{
				$arGroup = Array();
				$i = 0;
				foreach($channelId as $channel)
				{
					if (count($arGroup[$i]) == $commandPerHit)
						$i++;

					$arGroup[$i][] = $channel;
				}
				$results = Array();
				foreach($arGroup as $channels)
				{
					$result = self::SendCommand($channels, $message, $method, $timeout, $dont_wait_answer);
					$subresult = json_decode($result);
					$results = array_merge($results, $subresult->infos);
				}
				$result = json_decode('{"infos":'.json_encode($results).'}');
			}
			else
			{
				$result = self::SendCommand($channelId, $message, $method, $timeout, $dont_wait_answer);
				$result = json_decode($result);
			}
		}
		else
		{
			$result = self::SendCommand($channelId, $message, $method, $timeout, $dont_wait_answer);
			$result = json_decode($result_start.$result.$result_end);
		}

		return $result;
	}
	private static function SendCommand($channelId, $message, $method = 'POST', $timeout = 5, $dont_wait_answer = true)
	{
		if (!is_array($channelId))
			$channelId = Array($channelId);

		$channelId = implode('/', array_unique($channelId));

		if (strlen($channelId) <=0 || strlen($message) <= 0)
			return false;

		if (!in_array($method, Array('POST', 'GET')))
			return false;

		$nginx_error = COption::GetOptionString("pull", "nginx_error", "N");
		if ($nginx_error != "N")
		{
			$nginx_error = unserialize($nginx_error);
			if (intval($nginx_error['date'])+120 < time())
			{
				COption::SetOptionString("pull", "nginx_error", "N");
				CAdminNotify::DeleteByTag("PULL_ERROR_SEND");
				$nginx_error = "N";
			}
			else if ($nginx_error['count'] >= 10)
			{
				$ar = Array(
					"MESSAGE" => GetMessage('PULL_ERROR_SEND'),
					"TAG" => "PULL_ERROR_SEND",
					"MODULE_ID" => "pull",
				);
				CAdminNotify::Add($ar);
				return false;
			}
		}

		$postdata = CHTTP::PrepareData($message);

		$CHTTP = new CHTTP();
		$CHTTP->http_timeout = intval($timeout);
		$arUrl = $CHTTP->ParseURL(CPullOptions::GetPublishUrl($channelId), false);
		if ($CHTTP->Query($method, $arUrl['host'], $arUrl['port'], $arUrl['path_query'], $postdata, $arUrl['proto'], 'N', $dont_wait_answer))
		{
			$result = $dont_wait_answer? '{}': $CHTTP->result;
		}
		else
		{
			if ($nginx_error == "N")
			{
				$nginx_error = Array(
					'count' => 1,
					'date' => time(),
					'date_increment' => time(),
				);
			}
			else if (intval($nginx_error['date_increment'])+1 < time())
			{
				$nginx_error['count'] = intval($nginx_error['count'])+1;
				$nginx_error['date_increment'] = time();
			}
			COption::SetOptionString("pull", "nginx_error", serialize($nginx_error));
			$result = false;
		}

		return $result;
	}

	public static function SaveToCache($cacheId, $data)
	{
		global $CACHE_MANAGER;

		$CACHE_MANAGER->Clean($cacheId, "b_pull_channel");
		$CACHE_MANAGER->Read(43200, $cacheId, "b_pull_channel");
		$CACHE_MANAGER->SetImmediate($cacheId, $data);
	}
	public static function UpdateLastId($channelId, $lastId)
	{
		global $DB;

		$strSql = "UPDATE b_pull_channel SET LAST_ID = ".intval($lastId)." WHERE CHANNEL_ID = '".$DB->ForSQL($channelId)."'";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	// check channels that are older than 12 hours, remove them.
	public static function CheckExpireAgent()
	{
		global $DB;
		if (!CPullOptions::ModuleEnable())
			return false;

		$sqlDateFunction = null;
		$dbType = strtolower($DB->type);
		if ($dbType== "mysql")
			$sqlDateFunction = "DATE_SUB(NOW(), INTERVAL 13 HOUR)";
		else if ($dbType == "mssql")
			$sqlDateFunction = "dateadd(HOUR, -13, getdate())";
		else if ($dbType == "oracle")
			$sqlDateFunction = "SYSDATE-1/13";

		if (!is_null($sqlDateFunction))
		{
			$strSql = "
					SELECT USER_ID, CHANNEL_ID
					FROM b_pull_channel
					WHERE DATE_CREATE < ".$sqlDateFunction;
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arRes = $dbRes->Fetch())
				self::DeleteByUser($arRes['USER_ID'], $arRes['CHANNEL_ID']);
		}

		return "CPullChannel::CheckExpireAgent();";
	}

	public static function CheckOnlineChannel()
	{
		if (!CPullOptions::GetQueueServerStatus())
			return false;

		global $DB;
		$arUser = Array();

		$sqlDateFunction = null;
		$dbType = strtolower($DB->type);
		if ($dbType == "mysql")
			$sqlDateFunction = "DATE_SUB(NOW(), INTERVAL 13 HOUR)";
		else if ($dbType == "mssql")
			$sqlDateFunction = "dateadd(HOUR, -13, getdate())";
		else if ($dbType == "oracle")
			$sqlDateFunction = "SYSDATE-1/13";

		if (!is_null($sqlDateFunction))
		{
			$strSql = "
					SELECT USER_ID, CHANNEL_ID
					FROM b_pull_channel
					WHERE DATE_CREATE >= ".$sqlDateFunction;
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arRes = $dbRes->Fetch())
				$arUser[$arRes['CHANNEL_ID']] = $arRes['USER_ID'];
		}
		if (count($arUser) > 0)
		{
			$arOnline = Array();
			$arOffline = Array();

			global $USER;
			$agentUserId = 0;
			if (is_object($USER) && $USER->GetId() > 0)
			{
				$agentUserId = $USER->GetId();
				$arOnline[$agentUserId] = $agentUserId;
			}

			$arOnline = Array();
			$result = CPullChannel::Send(array_keys($arUser), 'ping', 'GET', 5, false);
			if (is_object($result) && isset($result->infos))
			{
				foreach ($result->infos as $info)
				{
					$userId = $arUser[$info->channel];
					if ($userId <= 0 || $agentUserId == $userId)
						continue;
					if ($info->subscribers > 0)
						$arOnline[$userId] = $userId;
					else
						$arOffline[$userId] = $userId;
				}
			}

			if (count($arOnline) > 0)
			{
				ksort($arOnline);
				CUser::SetLastActivityDateByArray($arOnline);
			}
		}

		$arSend = Array();
		$dbUsers = CUser::GetList(($sort_by = 'ID'), ($sort_dir = 'asc'), array('LAST_ACTIVITY' => '180'), array('FIELDS' => array("ID")));
		while ($arUser = $dbUsers->Fetch())
		{
			$arSend[$arUser["ID"]] = Array(
				'id' => $arUser["ID"],
				'status' => 'online',
			);
		}

		CPullStack::AddShared(Array(
			'module_id' => 'main',
			'command' => 'online_list',
			'params' => Array(
				'USERS' => $arSend
			),
		));

		return "CPullChannel::CheckOnlineChannel();";
	}

	public static function OnAfterUserAuthorize($arParams)
	{
		$arAuth = CHTTP::ParseAuthRequest();
		if(isset($arAuth["basic"]) && $arAuth["basic"]["username"] <> '' && $arAuth["basic"]["password"] <> ''
			&& strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'bitrix') === false)
		{
			return false;
		}

		if (isset($arParams['update']) && $arParams['update'] === false)
			return false;

		if ($arParams['user_fields']['ID'] <= 0)
			return false;

		$arParams['user_fields']['ID'] = intval($arParams['user_fields']['ID']);

		if (isset($_SESSION['USER_LAST_AUTH_'.$arParams['user_fields']['ID']])
			&& intval($_SESSION['USER_LAST_AUTH_'.$arParams['user_fields']['ID']])+100 > time())
			return false;

		$_SESSION['USER_LAST_AUTH_'.$arParams['user_fields']['ID']] = time();
		unset($_SESSION['USER_LAST_LOGOUT_'.$arParams['user_fields']['ID']]);

		CPullStack::AddShared(Array(
			'module_id' => 'main',
			'command' => 'user_authorize',
			'params' => Array(
				'USER_ID' => $arParams['user_fields']['ID']
			),
		));
	}

	public static function OnAfterUserLogout($arParams)
	{
		if ($arParams['USER_ID'] <= 0)
			return false;

		$arParams['USER_ID'] = intval($arParams['USER_ID']);

		if (isset($_SESSION['USER_LAST_LOGOUT_'.$arParams['USER_ID']])
			&& intval($_SESSION['USER_LAST_LOGOUT_'.$arParams['USER_ID']])+100 > time())
			return false;

		$_SESSION['USER_LAST_LOGOUT_'.$arParams['USER_ID']] = time();
		unset($_SESSION['USER_LAST_AUTH_'.$arParams['USER_ID']]);

		CPullStack::AddShared(Array(
			'module_id' => 'main',
			'command' => 'user_logout',
			'params' => Array(
				'USER_ID' => $arParams['USER_ID']
			),
		));
	}
}
?>
