<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/im/classes/general/im_contact_list.php");

class CIMContactList extends CAllIMContactList
{
	public static function SetRecent($entityId, $messageId, $isChat = false, $userId = false)
	{
		$entityId = intval($entityId);
		$messageId = intval($messageId);
		if ($entityId <= 0 || $messageId <= 0)
			return false;

		$userId = intval($userId);
		if ($userId <= 0)
			$userId = $GLOBALS['USER']->GetID();

		if (!$isChat && $userId == $entityId)
			return false;

		global $DB;

		$strSQL = "
			INSERT INTO b_im_recent (USER_ID, ITEM_TYPE, ITEM_ID, ITEM_MID)
			VALUES (".$userId.", '".($isChat? IM_MESSAGE_GROUP: IM_MESSAGE_PRIVATE)."', ".$entityId.", ".$messageId.")
			ON DUPLICATE KEY UPDATE ITEM_MID = ".$messageId;
		$DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$obCache = new CPHPCache();
		$obCache->CleanDir('/bx/im/rec'.CIMMessenger::GetCachePath($userId));

		if ($isChat)
			CIMMessenger::SpeedFileDelete($userId, IM_SPEED_GROUP);
		else
			CIMMessenger::SpeedFileDelete($userId, IM_SPEED_MESSAGE);

		return true;
	}
}
?>