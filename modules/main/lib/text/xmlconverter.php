<?php
namespace Bitrix\Main\Text;

class XmlConverter extends Converter
{
	static public function encode($text, $textType = "")
	{
		if (is_object($text))
			return $text;

		return HtmlFilter::encode($text);
	}

	static public function decode($text, $textType = "")
	{
		if (is_object($text))
			return $text;

		return htmlspecialchars_decode($text);
	}
}
