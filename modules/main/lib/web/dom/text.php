<?php
namespace Bitrix\Main\Web\DOM;

class Text extends Node
{
	public function __construct($text)
	{
		$this->init();

		$this->nodeType = self::TEXT_NODE;
		$this->nodeValue = $text;
		$this->nodeName = '#text';
	}

	public function setNodeValue($text)
	{
		$this->nodeValue = $text;
	}
}