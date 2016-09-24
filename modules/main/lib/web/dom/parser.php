<?php
namespace Bitrix\Main\Web\DOM;

abstract class Parser
{
	/*@var $htmlParser HtmlParser */
	private static $htmlParser;

	/*@var $config ParserConfig */
	protected $config;

	public static function getHtmlParser()
	{
		if (self::$htmlParser == null)
		{
			self::$htmlParser = new HtmlParser();
		}

		return self::$htmlParser;
	}

	public function setConfig(ParserConfig $config)
	{
		$this->config = $config;
		return $this;
	}

	public function getConfig()
	{
		return $this->config;
	}

	abstract public function parse($text = "", Node $toNode);
	abstract public function getSource(Node $node);
}