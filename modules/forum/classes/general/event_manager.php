<?
#############################################
# Bitrix Site Manager Forum					#
# Copyright (c) 2002-2013 Bitrix			#
# http://www.bitrixsoft.com					#
# mailto:admin@bitrixsoft.com				#
#############################################
class ForumEventManager
{
	public static function ForumEventManager()
	{
		if (IsModuleInstalled("iblock")) {
			AddEventHandler("forum", "onAfterMessageAdd", array(&$this, "updateIBlockPropertyAfterAddingMessage"));
			AddEventHandler("forum", "onMessageModerate", array(&$this, "updateIBlockProperty"));
			AddEventHandler("forum", "onAfterMessageDelete", array(&$this, "updateIBlockPropertyAfterDeletingMessage"));
		}
	}

	public static function updateIBlockPropertyAfterAddingMessage($ID, $arFields)
	{
		if ($ID > 0 && $arFields["PARAM1"] != "IB" && $arFields["APPROVED"] == "Y" && !empty($arFields["PARAM2"]))
		{
			self::updateIBlockProperty($ID, "SHOW", $arFields);
		}
	}

	public static function updateIBlockPropertyAfterDeletingMessage($ID, $arFields)
	{
		if ($ID > 0 && $arFields["PARAM1"] != "IB" && $arFields["APPROVED"] == "Y" && !empty($arFields["PARAM2"]))
		{
			self::updateIBlockProperty($ID, "HIDE", $arFields);
		}
	}

	public static function updateIBlockProperty($ID, $TYPE, $arMessage)
	{
		if ($ID > 0 && $arMessage["PARAM1"] != "IB" && !empty($arMessage["PARAM2"]) && CModule::IncludeModule("iblock"))
		{
			CIBlockElement::SetPropertyValuesEx($arMessage["PARAM2"], 0, array(
				"FORUM_MESSAGE_CNT" => array(
					"VALUE" => CForumMessage::GetList(array(), array("TOPIC_ID" => $arMessage["TOPIC_ID"], "APPROVED" => "Y", "!PARAM1" => "IB"), true),
					"DESCRIPTION" => "",
				)
			));
		}
	}
}
?>