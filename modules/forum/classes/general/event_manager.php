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

	public static function updateIBlockPropertyAfterAddingMessage($ID, $arFields, $arTopic = array())
	{
		if ($ID > 0 && $arFields["PARAM1"] != "IB" && $arFields["APPROVED"] == "Y")
		{
			self::updateIBlockProperty($ID, "SHOW", $arFields, $arTopic);
		}
	}

	public static function updateIBlockPropertyAfterDeletingMessage($ID, $arFields)
	{
		if ($ID > 0 && $arFields["PARAM1"] != "IB" && $arFields["APPROVED"] == "Y")
		{
			self::updateIBlockProperty($ID, "HIDE", $arFields);
		}
	}

	public static function updateIBlockProperty($ID, $TYPE, $arMessage, $arTopic = array())
	{
		if ($ID > 0 && $arMessage["PARAM1"] != "IB" && IsModuleInstalled("iblock"))
		{
			$arTopic = (empty($arTopic) ? CForumTopic::GetByID($arMessage["TOPIC_ID"]) : $arTopic);
			if (!empty($arTopic) && $arTopic["XML_ID"] == "IBLOCK_".$arMessage["PARAM2"] && CModule::IncludeModule("iblock"))
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
}
?>