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
				SELECT ID, MESSAGE
				FROM b_pull_stack
				WHERE CHANNEL_ID = '".$DB->ForSQL($channelId)."' AND ID > ".intval($lastId);
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
	public static function AddByChannel($channelId, $arMessage)
	{
		global $DB;

		if (strlen($arMessage['module_id'])<=0 || strlen($arMessage['command'])<=0)
			return false;


		$arData = Array(
			'module_id' => $arMessage['module_id'],
			'command' => $arMessage['command'],
			'params' => is_array($arMessage['params'])?$arMessage['params']: Array(),
		);
		if (CPullOptions::GetNginxStatus())
		{
			$CHTTP = new CHTTP();
			$CHTTP->http_timeout = 10;
			if ($CHTTP->HTTPQuery('POST', CPullOptions::GetPublishUrl($channelId), str_replace("\n", " ", CUtil::PhpToJsObject(Array('CHANNEL_ID' => $channelId, 'MESSAGE' => Array($arData), 'ERROR' => '')))))
				$result = $CHTTP->result;
		}
		else
		{
			$arParams = Array(
				'CHANNEL_ID' => $channelId,
				'MESSAGE' => str_replace("\n", " ", serialize($arData)),
				'~DATE_CREATE' => $DB->CurrentTimeFunction(),
			);
			$id = IntVal($DB->Add("b_pull_stack", $arParams, Array("MESSAGE")));
			$result = $id? '{"channel": "'.$channelId.'", "id": "'.$id.'"}': false;
		}

		if (isset($arMessage['push_text']) && strlen($arMessage['push_text'])>0
		&& isset($arMessage['push_user']) && intval($arMessage['push_user'])>0)
		{
			$CPushManager = new CPushManager();
			$CPushManager->AddQueue(Array(
				'USER_ID' => $arMessage['push_user'],
				'MESSAGE' => str_replace("\n", " ", $arMessage['push_text']),
				'PARAMS' => $arMessage['push_params'],
				'TAG' => isset($arMessage['push_tag'])? $arMessage['push_tag']: '',
			));
		}

		return $result;
	}

	public static function AddByUser($userId, $arMessage)
	{
		if (intval($userId) <= 0)
			return false;

		$arChannel = CPullChannel::Get($userId);
		$arMessage['push_user'] = $userId;
		return self::AddByChannel($arChannel['CHANNEL_ID'], $arMessage);
	}

	public static function AddBroadcast($arMessage)
	{
		global $DB;

		$strSql = "SELECT CHANNEL_ID FROM b_pull_channel";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
		{
			if(!self::AddByChannel($arRes['CHANNEL_ID'], $arMessage))
				break;
		}

		return true;
	}
}
?>