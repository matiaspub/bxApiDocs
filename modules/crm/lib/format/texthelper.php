<?php
namespace Bitrix\Crm\Format;
use Bitrix\Main;
class TextHelper
{
	public static function convertHtmlToBbCode($html)
	{
		$html = strval($html);
		if($html === '')
		{
			return '';
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventKey = $eventManager->addEventHandlerCompatible("main", "TextParserBeforeTags", array("\Bitrix\Crm\Format\TextHelper", "onTextParserBeforeTags"));

		$textParser = new \CTextParser();
		$textParser->allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "Y", "VIDEO" => "Y", "TABLE" => "Y", "CUT_ANCHOR" => "Y", "ALIGN" => "Y");
		$result = $textParser->convertText($html);
		$result = htmlspecialcharsback($result);
		$result = preg_replace("/\<br\s*\/*\>/is".BX_UTF_PCRE_MODIFIER,"\n", $result);
		$result = preg_replace("/&nbsp;/is".BX_UTF_PCRE_MODIFIER,"", $result);
		$result = preg_replace("/\<([^>]*?)>/is".BX_UTF_PCRE_MODIFIER,"", $result);
		$result = htmlspecialcharsbx($result);

		$eventManager->removeEventHandler("main", "TextParserBeforeTags", $eventKey);
		return $result;
	}
	public static function onTextParserBeforeTags(&$text, &$textParser)
	{
		$text = preg_replace(array("/\&lt;/is".BX_UTF_PCRE_MODIFIER, "/\&gt;/is".BX_UTF_PCRE_MODIFIER),array('<', '>'),$text);
		$text = preg_replace("/\<br\s*\/*\>/is".BX_UTF_PCRE_MODIFIER,"", $text);
		$text = preg_replace("/\<(\w+)[^>]*\>(.+?)\<\/\\1[^>]*\>/is".BX_UTF_PCRE_MODIFIER,"\\2",$text);
		$text = preg_replace("/\<*\/li\>/is".BX_UTF_PCRE_MODIFIER,"", $text);
		$text = str_replace(array("<", ">"),array("&lt;", "&gt;"),$text);
		$textParser->allow = array();
		return true;
	}
}