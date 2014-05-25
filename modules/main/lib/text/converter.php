<?php
namespace Bitrix\Main\Text;

abstract class Converter
{
	const TEXT = "text";
	const HTML = "html";

	private static $htmlConverter;
	private static $xmlConverter;
	private static $emptyConverter;

	public static function getHtmlConverter()
	{
		if (self::$htmlConverter == null)
			self::$htmlConverter = new HtmlConverter();
		return self::$htmlConverter;
	}

	public static function getXmlConverter()
	{
		if (self::$xmlConverter == null)
			self::$xmlConverter = new XmlConverter();
		return self::$xmlConverter;
	}

	public static function getEmptyConverter()
	{
		if (self::$emptyConverter == null)
			self::$emptyConverter = new EmptyConverter();
		return self::$emptyConverter;
	}

	public static function initTextType($textType)
	{
		$textType = strtolower($textType);
		if ($textType != self::TEXT && $textType != self::HTML)
			$textType = self::TEXT;
		return $textType;
	}

	abstract public function encode($text, $textType = "");
	abstract public function decode($text, $textType = "");
}
