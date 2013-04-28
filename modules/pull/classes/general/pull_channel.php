<?
IncludeModuleLangFile(__FILE__);

class CPullChannel
{
	// TODO
	public static function GetList()
	{
		//return Array(array('CHANNEL_ID' => '', 'USER_ID' => ''));
		return Array();
	}
	// Get channel identifier
	// If time is up - remove it, start a new
	// If not, create a new
	public static function Get($userId, $reOpen = false)
	{
		global $DB;
		$nginxStatus = CPullOptions::GetNginxStatus();

		$strSql = "
				SELECT CHANNEL_ID, LAST_ID, ".$DB->DateToCharFunction('DATE_CREATE')." DATE_CREATE
				FROM b_pull_channel
				WHERE USER_ID = ".intval($userId);
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			if (MakeTimeStamp($arRes['DATE_CREATE'])+43200 > time())
			{
				if ($reOpen)
				{
					$arData = Array(
						'module_id' => 'pull',
						'command' => 'reopen',
						'params' => Array(),
					);
					$channelId = $arRes['CHANNEL_ID'];
					$CHTTP = new CHTTP();
					$CHTTP->http_timeout = 10;
					$CHTTP->HTTPQuery('POST', CPullOptions::GetPublishUrl($channelId), CUtil::PhpToJsObject(Array('MESSAGE' => Array($arData), 'ERROR' => '')));

				}

				return Array(
					'CHANNEL_ID' => $arRes['CHANNEL_ID'],
					'LAST_ID' => intval($arRes['LAST_ID']),
					'PATH' => ($nginxStatus? CPullOptions::GetListenUrl($arRes['CHANNEL_ID']): '/bitrix/components/bitrix/pull.request/ajax.php'),
					'PATH_WS' => '',
					'METHOD' => ($nginxStatus? 'LONG': 'PULL'),
				);
			}
			else
			{
				self::Delete($arRes['CHANNEL_ID']);
			}
		}

		$channelId = self::Add($userId);
		return $channelId? Array(
			'CHANNEL_ID' => $channelId,
			'LAST_ID' => 0,
			'PATH' => ($nginxStatus? CPullOptions::GetListenUrl($channelId): '/bitrix/components/bitrix/pull.request/ajax.php'),
			'METHOD' => ($nginxStatus? 'LONG': 'PULL')
		): false;
	}

	// create a channel for the user
	public static function Add($userId)
	{
		global $DB, $APPLICATION;

		$channelId = md5(uniqid().$_SERVER["REMOTE_ADDR"].$_SERVER["SERVER_NAME"].(is_object($APPLICATION)? $APPLICATION->GetServerUniqID(): ''));

		$arParams = Array(
			'USER_ID' => intval($userId),
			'CHANNEL_ID' => $channelId,
			'LAST_ID' => 0,
			'~DATE_CREATE' => $DB->CurrentTimeFunction(),
		);
		$result = IntVal($DB->Add("b_pull_channel", $arParams, Array()));

		if (CPullOptions::GetNginxStatus())
		{
			$result = false;
			$arData = Array(
				'module_id' => 'pull',
				'command' => 'open',
				'params' => Array(),
			);
			$CHTTP = new CHTTP();
			$CHTTP->http_timeout = 10;
			if ($CHTTP->HTTPQuery('POST', CPullOptions::GetPublishUrl($channelId), CUtil::PhpToJsObject(Array('MESSAGE' => Array($arData), 'ERROR' => ''))))
				$result = $CHTTP->result;
		}

		return ($result? $channelId: false);
	}

	// remove channel by identifier
	// before removing need to send a message to change channel
	public static function Delete($channelId)
	{
		global $DB;
		$arMessage = Array(
			'module_id' => 'pull',
			'command' => 'channel_die',
			'params' => ''
		);
		CPullStack::AddByChannel($channelId, $arMessage);

		$strSql = "DELETE FROM b_pull_channel WHERE CHANNEL_ID = '".$DB->ForSQL($channelId)."'";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public static function UpdateLastId($channelId, $lastId)
	{
		global $DB;

		$strSql = "UPDATE b_pull_channel SET LAST_ID = ".intval($lastId)." WHERE CHANNEL_ID = '".$DB->ForSQL($channelId)."'";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

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
			$sqlDateFunction = "DATE_SUB(NOW(), INTERVAL 12 HOUR)";
		else if ($dbType == "mssql")
			$sqlDateFunction = "dateadd(HOUR, -12, getdate())";
		else if ($dbType == "oracle")
			$sqlDateFunction = "SYSDATE-1/12";

		if (!is_null($sqlDateFunction))
		{
			$strSql = "
					SELECT CHANNEL_ID
					FROM b_pull_channel
					WHERE DATE_CREATE < ".$sqlDateFunction;
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arRes = $dbRes->Fetch())
				self::Delete($arRes['CHANNEL_ID']);
		}

		return "CPullChannel::CheckExpireAgent();";
	}
}
?>