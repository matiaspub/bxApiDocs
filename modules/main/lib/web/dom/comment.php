<?php
namespace Bitrix\Main\Web\DOM;

class Comment extends Node
{
	public function __construct($comment)
	{
		$this->init();
		$this->nodeType = self::COMMENT_NODE;

		$this->nodeValue = $comment;
		$this->nodeName = '#comment';
	}

	public function setNodeValue($comment)
	{
		$this->nodeValue = $comment;
	}

	public function getTextContent()
	{
		return $this->nodeValue;
	}
}