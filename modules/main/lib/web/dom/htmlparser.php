<?php
namespace Bitrix\Main\Web\DOM;

use \Bitrix\Main\Text\HtmlFilter;

class HtmlParser extends Parser
{
	public $debugTime = 0;

	protected $tagsMustBeClosed = array('SCRIPT', 'STYLE');


	public $storePhpCode = true;

	protected static $objectCounter = 0;
	protected $currentObjectNumber;
	protected $storedPHP = array();

	public function __construct()
	{
		static::$objectCounter++;
		$this->currentObjectNumber = static::$objectCounter;

		$this->setConfig(new HtmlParserConfig);
	}

	/*
	 * @param Node $node
	 * @return string
	 */
	public function getSource(Node $node)
	{
		$source = '';
		switch($node->getNodeType())
		{
			case Node::ELEMENT_NODE:

				/*@var $node Element*/
				$source = $this->getSourceElement($node);
				break;

			case Node::ATTRIBUTE_NODE:

				/*@var $node Attr*/
				$source = $this->getSourceAttr($node);
				break;

			case Node::TEXT_NODE:

				/*@var Text $node*/
				if($node->getParentNode() && in_array($node->getParentNode()->getNodeName(), $this->tagsMustBeClosed))
				{
					$source = $node->getNodeValue();
				}
				else
				{
					$source = HtmlFilter::encode($node->getNodeValue());
				}

				break;

			case Node::COMMENT_NODE:

				/*@var Comment $node*/
				$source = '<!--' . $node->getNodeValue() . '-->';
				if($this->storePhpCode)
				{
					$source = $this->restorePHP($source);
				}
				break;

			case Node::DOCUMENT_TYPE_NODE:

				/*@var DocumentType $node*/
				$source = $this->getSourceDocType($node);
				break;
		}

		return $source;
	}

	protected function getSourceAttr(Attr $node)
	{
		return $node->getName() . '="' . HtmlFilter::encode($node->getValue()) . '"';
	}

	protected function getSourceElement(Element $node)
	{
		$nodeName = strtolower($node->getNodeName());
		$source = '<' . $nodeName;
		if($node->hasAttributes())
		{
			$attrList = $node->getAttributesArray();
			foreach($attrList as $attr)
			{
				$source .= ' ' . $this->getSource($attr);
			}
		}

		if($node->hasChildNodes())
		{
			$source .= '>';

			if(Node::$isNodeListAsArray)
			{
				$childNodes = $node->getChildNodesArray();
				foreach ($childNodes as $child)
				{
					$source .= $this->getSource($child);
				}
			}
			else
			{
				for($i = 0; $i < $node->getChildNodes()->getLength(); $i++)
				{
					$source .= $this->getSource($node->getChildNodes()->item($i));
				}
			}


			$source .= '</' . $nodeName . '>';
		}
		else
		{
			$source .= ' />';
		}

		return $source;
	}

	protected function getSourceDocType(DocumentType $node)
	{
		$source = '<!DOCTYPE html>';
		return $source;
	}

	/*
	 * @param string $text
	 * @param Node $node
	 * @return void
	 */
	public function parse($text = "", Node $node)
	{
		if($this->storePhpCode)
		{
			$text = $this->storePHP($text);
		}
		else
		{
			$text = $this->commentPHP($text);
		}

		$isCharOpen = true;
		$isCharClose = false;
		$buffer = '';
		$char = $charPrev = $charNext = '';

		$textLength = strlen($text);
		for($i = 0; $i < $textLength; $i++)
		{
			$char = substr($text, $i, 1);
			if($char === '<')
			{
				$node = $this->getNextNode($buffer, $node);

				$buffer = '';
				$buffer .= $char;

				$isCharOpen = true;
				$isCharClose = false;
			}
			elseif($char === '>')
			{
				$buffer .= $char;
				if($isCharOpen)
				{
					$node = $this->getNextNode($buffer, $node);
					$buffer = '';
				}

				$isCharClose = true;
				$isCharOpen = false;
			}
			else
			{
				$buffer .= $char;
			}

			if(!$node)
			{
				return null;
			}
		}

		if($buffer != '')
		{
			$node = $this->getNextNode($buffer, $node);
		}

		return $node;
	}

	protected function parseElement($text)
	{
		$result = array('NAME' => '', 'ATTRIBUTES' => array());

		if(preg_match('/[ \t\n]/S', $text, $matches, PREG_OFFSET_CAPTURE))
		{
			$delimiterPosition = $matches[0][1];
			$result['NAME'] = strtoupper(substr($text, 0, $delimiterPosition));
			$textAttr = substr($text, $delimiterPosition + 1);
			$result['ATTRIBUTES'] = $this->parseAttributes($textAttr);
		}
		else
		{
			$result['NAME'] = strtoupper($text);
		}

		return $result;
	}

	protected function parseDocType($text)
	{
		return array();
	}

	protected function parseAttributes($text)
	{
		static $search = array(
			"'&(quot|#34);'i",
			"'&(lt|#60);'i",
			"'&(gt|#62);'i",
			"'&(amp|#38);'i",
		);

		static $replace = array(
			"\"",
			"<",
			">",
			"&",
		);

		$attributes = array();
		if ($text !== "")
		{
			preg_match_all("/(?'name'[\w-_:]+)(?'eq'\s*=\s*)?(?(eq)([\"'])(?'val'.*?)\g{-2})/s", $text, $attrTmp);
			if(strpos($text, "&")===false)
			{
				foreach($attrTmp['name'] as $i => $attrName)
				{
					$attributes[$attrName] = $attrTmp['val'][$i];
				}
			}
			else
			{
				foreach($attrTmp['name'] as $i => $attrName)
				{
					$attributes[$attrName] = preg_replace($search, $replace, $attrTmp['val'][$i]);
				}
			}
		}

		return $attributes;
	}

	protected function parseAttributesOld($text)
	{
		preg_match_all("/\b([\w_-]+\s*=\s*([\"']*)[^\\2]+?\\2)/", $text, $pairs);
		$pairs = $pairs[0];

		$attributeList = Array();
		foreach($pairs as $pair)
		{
			$attr = array_map(
				function ($data){
					$data = preg_replace("/(^['\"]|['\"]$)/","",$data);
					return $data;
				},
				preg_split("/\s*=\s*/", $pair)
			);
			$name = $attr[0];
			$value = $attr[1];
			$attributeList[$name] = $value;
		}

		return $attributeList;
	}

	protected function getNextNode($tag, Node $parentNode)
	{
		$node = null;
		$isSingleTag = true;

		static $tagsWithoutClose = array('INPUT'=>1, 'IMG'=>1, 'BR'=>1, 'HR'=>1, 'META'=>1, 'AREA'=>1, 'BASE'=>1, 'COL'=>1, 'EMBED'=>1, 'KEYGEN'=>1, 'LINK'=>1, 'PARAM'=>1, 'SOURCE'=>1, 'TRACK'=>1, 'WBR'=>1);
		$tagsCantHaveNestedTags = array();

		$document = $parentNode->getOwnerDocument();

		if($parentNode->getNodeType() === Node::COMMENT_NODE)
		{
			$commentClosePosition = strpos($tag, '-->');
			if($commentClosePosition !== false)
			{
				$clean = substr($tag, 0, $commentClosePosition);
				$parentNode->setNodeValue($parentNode->getNodeValue() . $clean);
				$parentNode->bxNodeFoundCloseTag = true;

				$tag = substr($tag, $commentClosePosition + 3);
				if(!$tag)
				{
					return $parentNode->getParentNode();
				}
				else
				{
					$parentNode = $parentNode->getParentNode();
				}
			}
			else
			{
				$parentNode->setNodeValue($parentNode->getNodeValue() . $tag);
				return $parentNode;
			}
		}
		elseif(in_array($parentNode->getNodeName(), $this->tagsMustBeClosed))
		{
			if(strtoupper(substr($tag, -9)) == '</' . $parentNode->getNodeName() . '>')
			{
				$parentNode->bxNodeFoundCloseTag = true;
				$parentNode = $parentNode->getParentNode();
			}
			else
			{
				$firstChild = $parentNode->getFirstChild();
				if(!$firstChild)
				{
					$parentNode->appendChild($document->createTextNode($tag));
				}
				else
				{
					$firstChild->setNodeValue($firstChild->getNodeValue() . $tag);
				}

				$parentNode->bxNodeFoundCloseTag = false;
				return $parentNode;
			}
		}

		if(substr($tag, 0, 2) === '</')
		{
			// closed tag
			//TODO: find closest opened parent with same nodeName and return it
			$cleaned = strtoupper(substr($tag, 2, -strlen('>') ));
			$searchableNode = $parentNode;
			$isSearchableNodeFound = false;

			$unclosedNodes = array();
			do
			{
				if(!$searchableNode->bxNodeFoundCloseTag)
				{
					$unclosedNodes[] = $searchableNode;
				}

				if($searchableNode->getNodeName() === $cleaned)
				{
					$isSearchableNodeFound = true;
					break;
				}
			}while($searchableNode = $searchableNode->getParentNode());

			if($isSearchableNodeFound)
			{
				foreach($unclosedNodes as $unclosedNode)
				{
					/* @var $unclosedNode Node */
					if(in_array($unclosedNode->getNodeName(), $tagsCantHaveNestedTags))
					{
						if($unclosedNode->hasChildNodes())
						{
							foreach ($unclosedNode->getChildNodesArray() as $childNode)
							{
								$unclosedNode->getParentNode()->appendChild($unclosedNode->removeChild($childNode));
							}
						}
					}

					$unclosedNode->bxNodeFoundCloseTag = true;
				}

				return $searchableNode->getParentNode();
			}
			else
			{
				if(false)
				{
					throw new DomException('Parser error. Find close tag, but can not find open tag ' . $cleaned);
				}
				else
				{
					$parentNode->getParentNode()->bxNodeFoundCloseTag = true;
					return $parentNode;
				}
			}
		}
		elseif(substr($tag, 0, 4) === '<!--')
		{
			// Comment
			$cleaned = substr($tag, 4);
			if(substr($tag, -3) == '-->')
			{
				$cleaned = substr($cleaned, 0, -3);
				$parentNode->bxNodeFoundCloseTag = true;
			}
			else
			{
				$isSingleTag = false;
			}


			//$parentNode->bxNodeFoundCloseTag = false;
			$node = $document->createComment($cleaned);
		}
		elseif(substr($tag, 0, 1) === '<')
		{

			// Element
			if(substr($tag, -2) === '/>')
			{
				// empty tag
				$cleaned = substr($tag, 1, -2);
				$bxNodeWithCloseTag = false;
				$isSingleTag = true;
			}
			else
			{
				$cleaned = substr($tag, 1, -1);
				$isSingleTag = false;
				$bxNodeWithCloseTag = true;
			}

			$list = $this->parseElement($cleaned);

			$isDocType = substr($list['NAME'], 0, strlen('!DOCTYPE')) === '!DOCTYPE';

			if(isset($tagsWithoutClose[$list['NAME']]) || $isDocType)
			{
				$bxNodeWithCloseTag = false;
				$isSingleTag = true;
			}

			if($isDocType)
			{
				$list = $this->parseDocType($cleaned);
				//TODO: set doctype fields
			}
			else
			{
				$node = $document->createElement($list['NAME']);
				foreach($list['ATTRIBUTES'] as $attrName => $attrValue)
				{
					$nodeAttr = $document->createAttribute($attrName, $attrValue);
					$node->setAttributeNode($nodeAttr);
				}
				$node->bxNodeWithCloseTag = $bxNodeWithCloseTag;
			}
		}
		else
		{
			// Text
			$cleaned = html_entity_decode($tag, ENT_COMPAT, (defined("BX_UTF") ? "UTF-8" : "ISO-8859-1"));
			$node = $document->createTextNode($cleaned);
		}

		if($node && $parentNode)
		{
			$parentNode->appendChild($node);
			if(!$isSingleTag)
			{
				return $node;
			}
		}

		return $parentNode;
	}

	/*
	* @param string $html
	* @return string
	*/
	static public function commentPHP($html)
	{
		$html = str_replace(array('<?', '?>'), array('<!--', '-->'),	$html);
		return $html;
	}

	/*
	 * @param string $html
	 * @return string
	*/
	public function storePHP($html)
	{
		if(preg_match_all('/(<\?[\W\w\n]*?\?>)/i', $html, $matches, PREG_SET_ORDER) && is_array($matches))
		{
			$prefix = 'BX_DOM_DOCUMENT_PHP_SLICE_PLACEHOLDER_' . $this->currentObjectNumber . '_';
			foreach($matches as $key => $value)
			{
				$this->storedPHP['<!--' . $prefix . (string) $key . '-->'] = $value[0];
			}

			$replaceFrom = array_values($this->storedPHP);
			$replaceTo = array_keys($this->storedPHP);

			$html = str_replace($replaceFrom, $replaceTo,	$html);
		}

		return $html;
	}

	/*
	 * @param string $html
	 * @return string
	*/
	public function restorePHP($html)
	{
		$html = str_replace(
			array_keys($this->storedPHP),
			array_values($this->storedPHP),
			$html
		);

		return $html;
	}
}