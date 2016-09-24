<?php

namespace Bitrix\Main\UrlPreview\Parser;

use Bitrix\Main\UrlPreview\HtmlDocument;
use Bitrix\Main\UrlPreview\Parser;

class SchemaOrg extends Parser
{
	/** @var  \DOMDocument */
	protected $dom;

	/** @var  array */
	protected $schemaMetadata = array();

	/**
	 * Parses HTML document's Schema.org metadata.
	 *
	 * @param HtmlDocument $document
	 */
	
	/**
	* <p>Нестатический метод парсит метаданные HTML документа по стандарту <b>Schema.org</b>.</p>
	*
	*
	* @param mixed $Bitrix  
	*
	* @param Bitri $Main  
	*
	* @param Mai $UrlPreview  
	*
	* @param HtmlDocument $document  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/parser/schemaorg/handle.php
	* @author Bitrix
	*/
	public function handle(HtmlDocument $document)
	{
		if(strpos($document->getHtml(), 'itemscope') === false)
			return null;

		if(!$this->initializeDom($document))
			return null;

		if(!$this->getSchemaMetadata())
			return null;

		if(strlen($document->getTitle()) == 0 && isset($this->schemaMetadata['name']))
		{
			$document->setTitle($this->schemaMetadata['name']);
		}

		if(strlen($document->getDescription()) == 0 && isset($this->schemaMetadata['description']))
		{
			$document->setDescription($this->schemaMetadata['description']);
		}

		if(strlen($document->getImage()) == 0 && isset($this->schemaMetadata['image']))
		{
			$document->setImage($this->schemaMetadata['image']);
		}
	}

	/**
	 * @return bool
	 */
	protected function getSchemaMetadata()
	{
		// Starting with first node with itemscope attribute, to prevent walking over full document.
		$xpath = new \DOMXPath($this->dom);
		$itemScopeNodes = $xpath->query('//*[@itemscope]');

		if(!is_a($itemScopeNodes, '\DOMNodeList') || $itemScopeNodes->length < 1)
			return false;

		$mainNode = $itemScopeNodes->item(0);
		if(!is_a($mainNode, '\DOMElement'))
			return false;

		$this->walkDomTree($mainNode);

		return true;
	}

	/**
	 * @param \DOMElement $currentNode
	 * @param int $currentDepth
	 */
	protected function walkDomTree(\DOMElement $currentNode, $currentDepth = 0)
	{
		$this->handleNode($currentNode);
		foreach($currentNode->childNodes as $childNode)
		{
			if(is_a($childNode, '\DOMElement') && !($currentDepth == 0 xor $currentNode->hasAttribute('itemscope')))
			{
				$this->walkDomTree($childNode, $currentDepth + 1);
			}
		}
	}

	/**
	 * @param \DOMElement $node
	 * @return null|string
	 */
	protected function getSchemaPropertyValue(\DOMElement $node)
	{
		$result = null;

		switch($node->tagName)
		{
			case 'img':
				$result =  $node->getAttribute('src');
				break;
			case 'meta':
				$result = $node->getAttribute('content');
				break;
			case 'a':
				$result = $node->getAttribute('href');
				break;
			case 'time':
				if($node->hasAttribute('datetime'))
					$result = $node->getAttribute('datetime');
				else
					$result = $node->textContent;
				break;
			case 'div':
				$result = $this->getNodeInnerHtml($node);
				break;
			case 'p':
			case 'span':
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				$result = $node->textContent;
				break;
		}

		$result = trim($result);
		return (strlen($result) > 0 ? $result : null);
	}

	/**
	 * @param \DOMElement $node
	 */
	protected function handleNode(\DOMElement $node)
	{
		if($node->hasAttribute('itemprop') && !$node->hasAttribute('itemscope'))
		{
			$propertyName = strtolower($node->getAttribute('itemprop'));
			$propertyValue = $this->getSchemaPropertyValue($node);
			$this->schemaMetadata[$propertyName] = $propertyValue;
		}
	}

	/**
	 * @param \DOMElement $element
	 * @return string
	 */
	protected function getNodeInnerHtml(\DOMElement $element)
	{
		$innerHTML = "";
		$children  = $element->childNodes;

		foreach ($children as $child)
		{
			$innerHTML .= $element->ownerDocument->saveHTML($child);
		}

		return $innerHTML;
	}

	/**
	 * @param HtmlDocument $document
	 * @return bool
	 */
	protected function initializeDom(HtmlDocument $document)
	{
		if(!class_exists('DOMDocument'))
		{
			return false;
		}

		$this->dom = new \DOMDocument();
		// Prevents parsing errors bubbling
		libxml_use_internal_errors(true);
		$result = $this->dom->loadHTML('<?xml encoding="'.$document->getEncoding().'">'.$document->getHtml());

		return $result;
	}
}
