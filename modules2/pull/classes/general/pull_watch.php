<?
class CAllPullWatch
{
	public static function Add($userId, $tag)
	{
		global $DB;

		if (intval($userId) <= 0 && strlen($tag) <= 0)
			return false;

		$arChannel = CPullChannel::Get($userId);

		$strSql = "SELECT ID FROM b_pull_watch WHERE USER_ID = ".intval($userId)." AND TAG = '".$DB->ForSQL($tag)."'";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$DB->Query("UPDATE b_pull_watch SET DATE_CREATE = ".$DB->CurrentTimeFunction().", CHANNEL_ID = '".$DB->ForSQL($arChannel['CHANNEL_ID'])."' WHERE ID = ".$arRes['ID']);
			$ID = $arRes['ID'];
		}
		else
		{
			$arParams = Array(
				'USER_ID' => intval($userId),
				'CHANNEL_ID' => $arChannel['CHANNEL_ID'],
				'TAG' => trim($tag),
				'~DATE_CREATE' => $DB->CurrentTimeFunction(),
			);
			$ID = IntVal($DB->Add("b_pull_watch", $arParams, Array()));
		}
		return $ID;
	}

	public static function Extend($userId, $tag)
	{
		global $DB;

		if (intval($userId) <= 0 && strlen($tag) <= 0)
			return false;

		$strSql = "SELECT ID FROM b_pull_watch WHERE USER_ID = ".intval($userId)." AND TAG = '".$DB->ForSQL($tag)."'";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			$ID = $arRes['ID'];
			$arChannel = CPullChannel::Get($userId);
			$DB->Query("UPDATE b_pull_watch SET DATE_CREATE = ".$DB->CurrentTimeFunction().", CHANNEL_ID = '".$DB->ForSQL($arChannel['CHANNEL_ID'])."' WHERE ID = ".$ID);
		}
		return false;
	}

	public static function AddToStack($tag, $arMessage)
	{
		global $DB;

		$result = false;
		$strSql = "SELECT CHANNEL_ID FROM b_pull_watch WHERE TAG = '".$DB->ForSQL($tag)."'";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
		{
			$result = CPullStack::AddByChannel($arRes['CHANNEL_ID'], $arMessage);
			if (!$result)
				break;
		}
		return !$result? false: true;
	}

	public static function GetUserList($tag)
	{
		global $DB;

		$arUsers = Array();
		$strSql = "SELECT USER_ID FROM b_pull_watch WHERE TAG = '".$DB->ForSQL($tag)."'";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
			$arUsers[$arRes['USER_ID']] = $arRes['USER_ID'];

		return $arUsers;
	}
}
?>