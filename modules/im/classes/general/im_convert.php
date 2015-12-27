<?
class CIMConvert
{
	public static $convertPerStep = 0;
	public static $nextConvertPerStep = 0;
	public static $converted = 0;

	static public function __construct()
	{
	}

	public static function RecentList()
	{
		global $DB, $USER;

		$arRecent = CUserOptions::GetOption('IM', 'recentList2', null);
		if (is_null($arRecent))
		{
			$arResult = CUserOptions::GetOption('IM', 'recentList', null);
			if (!is_null($arResult))
			{
				unset($arResult[$GLOBALS['USER']->GetID()]);
				foreach ($arResult as $key => $value)
					$arRecent[IM_MESSAGE_PRIVATE][$key] = $value;
			}
			else
			{
				$arRecent = Array();
			}
		}
		if (!empty($arRecent))
		{
			if (isset($arRecent[IM_MESSAGE_PRIVATE]) && !empty($arRecent[IM_MESSAGE_PRIVATE]))
			{
				$arUsers = Array();
				$arInsert = Array();
				$CIMMessage = new CIMMessage(false, array(
					'HIDE_LINK' => 'Y'
				));
				$arMessages = $CIMMessage->GetLastSendMessage(Array(
					'TO_USER_ID' => array_keys($arRecent[IM_MESSAGE_PRIVATE]),
					'ORDER' => 'ASC',
					'LIMIT' => 30,
					'USE_TIME_ZONE' => 'N'
				));
				foreach ($arMessages as $userId => $arMessage)
				{
					$arUsers[] = $userId;
					$arInsert[$userId] = Array(
						'USER_ID' => $USER->GetId(),
						'ITEM_TYPE' => IM_MESSAGE_PRIVATE,
						'ITEM_ID' => $userId,
						'ITEM_MID' => $arMessage['id'],
					);
				}
				if (!empty($arUsers))
				{
					$strSql = "SELECT ITEM_ID FROM b_im_recent WHERE USER_ID = ".$USER->GetId()." AND ITEM_TYPE = '".IM_MESSAGE_PRIVATE."' AND ITEM_ID IN (".implode(',', $arUsers).")";
					$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					while ($arRes = $dbRes->Fetch())
						unset($arInsert[$arRes['ITEM_ID']]);
				}

				foreach ($arInsert as $arAdd)
					$DB->Add('b_im_recent', $arAdd);
			}
			if (isset($arRecent[IM_MESSAGE_CHAT]) && !empty($arRecent[IM_MESSAGE_CHAT]))
			{
				$arChats = Array();
				$arInsert = Array();

				$CIMChat = new CIMChat(false, array(
					'HIDE_LINK' => 'Y'
				));
				$arMessagesGroup = $CIMChat->GetLastSendMessage(Array(
					'ID' => array_keys($arRecent[IM_MESSAGE_CHAT]),
					'ORDER' => 'ASC',
					'LIMIT' => 30,
					'USE_TIME_ZONE' => 'N'
				));
				foreach ($arMessagesGroup as $chatId => $arMessage)
				{
					$arChats[] = $chatId;
					$arInsert[$chatId] = Array(
						'USER_ID' => $USER->GetId(),
						'ITEM_TYPE' => IM_MESSAGE_CHAT,
						'ITEM_ID' => $chatId,
						'ITEM_MID' => $arMessage['id'],
					);
				}

				if (!empty($arChats))
				{
					$strSql = "
						SELECT
							ITEM_ID
						FROM
							b_im_recent
						WHERE
							USER_ID = ".$USER->GetId()."
							AND ITEM_TYPE = '".IM_MESSAGE_CHAT."'
							AND ITEM_ID IN (".implode(',', $arChats).")
					";
					$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					while ($arRes = $dbRes->Fetch())
						unset($arInsert[$arRes['ITEM_ID']]);
				}

				foreach ($arInsert as $arAdd)
					$DB->Add('b_im_recent', $arAdd);
			}
			CUserOptions::SetOption('IM', 'recentList2', Array());
		}
		else
		{
			CUserOptions::SetOption('IM', 'recentList2', Array());
		}

		return true;
	}

	public static function DeliveredMessage($step = 100, $maxExecutionTime = 10)
	{
		if (!CModule::IncludeModule("socialnetwork"))
			return false;

		$step = intval($step)>0? intval($step): 100;
		$startConvertTime = getmicrotime();

		$step = intval($step);
		$dbMessage = CSocNetMessages::GetList(
			array("ID" => "ID"),
			array(
				"!DATE_VIEW" => "",
				"TO_DELETED" => "N",
				"MESSAGE_TYPE" => "P"
			),
			false,
			array("nTopCount" => $step),
			array("ID", "FROM_USER_ID", "TO_USER_ID", "MESSAGE", "DATE_CREATE")
		);

		while($arMessage = $dbMessage->Fetch())
		{
			$ar = Array(
				"FROM_USER_ID" => intval($arMessage["FROM_USER_ID"]),
				"TO_USER_ID" => intval($arMessage["TO_USER_ID"]),
				"MESSAGE" 	 => $arMessage["MESSAGE"],
				"MESSAGE_DATE" => $arMessage["DATE_CREATE"],
				"IMPORT_ID" => $arMessage["ID"],
				"CONVERT" => 'Y',
			);
			CIMMessage::Add($ar);
			CSocNetMessages::Update($arMessage["ID"], array("TO_DELETED" => "Y", "FROM_DELETED" => "Y"));

			self::$converted++;
			self::$convertPerStep++;

			if($maxExecutionTime > 0 && (getmicrotime() - $startConvertTime > $maxExecutionTime))
				break;
		}
		if ($maxExecutionTime > (2*(getmicrotime() - $startConvertTime)))
			self::$nextConvertPerStep = $step*2;
		else
			self::$nextConvertPerStep = $step;
	}


	public static function UndeliveredMessageAgent()
	{
		if (!CModule::IncludeModule("socialnetwork"))
			return false;

		$activateNewAgent = false;

		$dbMessage = CSocNetMessages::GetList(
			array("ID" => "ASC"),
			array(
				"DATE_VIEW" => "",
				"TO_DELETED" => "N",
				"MESSAGE_TYPE" => "P"
			),
			false,
			array("nTopCount" => '500'),
			array("ID", "FROM_USER_ID", "TO_USER_ID", "MESSAGE", "DATE_CREATE")
		);
		while ($arMessage = $dbMessage->Fetch())
		{
			$ar = Array(
				"FROM_USER_ID" => intval($arMessage["FROM_USER_ID"]),
				"TO_USER_ID" => intval($arMessage["TO_USER_ID"]),
				"MESSAGE" 	 => $arMessage["MESSAGE"],
				"MESSAGE_DATE" => $arMessage["DATE_CREATE"],
			);
			CIMMessage::Add($ar);
			CSocNetMessages::Update($arMessage["ID"], array("TO_DELETED" => "Y", "FROM_DELETED" => "Y"));
			$activateNewAgent = true;
		}

		return $activateNewAgent? "CIMConvert::UndeliveredMessageAgent();": "";
	}

	public static function ConvertCount()
	{
		global $DB;

		$strSql = "SELECT COUNT(*) CNT FROM b_sonet_messages WHERE DATE_VIEW IS NOT NULL AND TO_DELETED = 'N' AND MESSAGE_TYPE = 'P'";
		$res = $DB->Query($strSql, true);
		if (!$res)
			return 0;

		if ($row = $res->Fetch())
			return intval($row['CNT']);
	}
}

?>
