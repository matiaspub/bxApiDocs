<?php
namespace Bitrix\Main\Web\DOM;

class DocumentType extends Node
{
	public function __construct()
	{
		parent::__construct();
		$this->nodeType = self::DOCUMENT_TYPE_NODE;
		$this->nodeName = '#document';
	}

	static public function getTextContent()
	{
		return null;
	}
}