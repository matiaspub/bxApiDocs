<?php
namespace Bitrix\Main\Text;

class EmptyConverter
	extends Converter
{
	static public function encode($text, $textType = "")
	{
		return $text;
	}

	static public function decode($text, $textType = "")
	{
		return $text;
	}
}
