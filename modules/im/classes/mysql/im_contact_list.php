<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/im/classes/general/im_contact_list.php");

use Bitrix\Im as IM;

class CIMContactList extends CAllIMContactList
{
	public static function SetRecent($arParams)
	{
		$itemId = intval($arParams['ENTITY_ID']);
		$messageId = intval($arParams['MESSAGE_ID']);
		if ($itemId <= 0)
			return false;

		$userId = intval($arParams['USER_ID']);
		if ($userId <= 0)
			$userId = (int)$GLOBALS['USER']->GetID();

		$chatType = IM_MESSAGE_PRIVATE;
		if (isset($arParams['CHAT_TYPE']) && in_array($arParams['CHAT_TYPE'], Array(IM_MESSAGE_OPEN, IM_MESSAGE_CHAT)))
		{
			$chatType = $arParams['CHAT_TYPE'];
		}
		else if (isset($arParams['CHAT_ID']))
		{
			$orm = IM\ChatTable::getById($arParams['CHAT_ID']);
			if ($chatData = $orm->fetch())
			{
				$chatType = $chatData['TYPE'];
			}
		}

		$isChat = in_array($chatType, Array(IM_MESSAGE_OPEN, IM_MESSAGE_CHAT));
		if (!$isChat && $userId == $itemId)
			return false;

		global $DB;

		$strSQL = "
			INSERT INTO b_im_recent (USER_ID, ITEM_TYPE, ITEM_ID, ITEM_MID)
			VALUES (".$userId.", '".$chatType."', ".$itemId.", ".$messageId.")
			ON DUPLICATE KEY UPDATE ITEM_MID = ".$messageId."
		";
		$DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$obCache = new CPHPCache();
		$obCache->CleanDir('/bx/imc/recent'.CIMMessenger::GetCachePath($userId));

		if ($isChat)
			CIMMessenger::SpeedFileDelete($userId, IM_SPEED_GROUP);
		else
			CIMMessenger::SpeedFileDelete($userId, IM_SPEED_MESSAGE);

		return true;
	}

	public static function GetOnline($ID = array())
	{
		global $DB;

		if (!is_array($ID))
			return false;

		$arUsers = array();
		$strSQL = "
			SELECT U.ID, S.STATUS
			FROM b_user U LEFT JOIN b_im_status S ON U.ID = S.USER_ID
			WHERE U.LAST_ACTIVITY_DATE > DATE_SUB(NOW(), INTERVAL 120 SECOND)
		";
		$dbUsers = $DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		while ($arUser = $dbUsers->Fetch())
		{
			if (!empty($ID) && !in_array($arUser["ID"], $ID))
				continue;

			$arUsers[$arUser["ID"]] = Array(
				'id' => $arUser["ID"],
				'status' => isset($arUser["STATUS"])? $arUser["STATUS"]: 'online',
			);
		}
		return $arUsers;
	}
}
?>