<?php
namespace Bitrix\Main\Web\DOM;

abstract class Node
{
	const ELEMENT_NODE = 1;
	const ATTRIBUTE_NODE = 2;
	const TEXT_NODE = 3;
	const CDATA_SECTION_NODE = 4;
	const ENTITY_REFERENCE_NODE = 5;
	const ENTITY_NODE = 6;
	const PROCESSING_INSTRUCTION_NODE = 7;
	const COMMENT_NODE = 8;
	const DOCUMENT_FRAGMENT_NODE = 11;
	const DOCUMENT_NODE = 9;
	const DOCUMENT_TYPE_NODE = 10;
	const NOTATION_NODE = 12;

	public static $isNodeListAsArray = true;

	/*@var Document $ownerDocument*/
	protected $ownerDocument = null;

	/*@var string $nodeType*/
	protected $nodeType = null;

	/*@var string $nodeName*/
	protected $nodeName = null;

	/*@var string $nodeValue*/
	protected $nodeValue = null;

	/*@var NodeList $childNodes*/
	protected $childNodes = null;

	/*@var Node $parentNode*/
	protected $parentNode = null;

	/*@var null|NamedNodeMap $attributes*/
	protected $attributes = null;

	/*@var string $textContent*/
	protected $textContent = null;

	/*@var bool $bxIsAlreadyClosed*/
	public $bxIsAlreadyClosed = false;
	/*@var bool $bxClosable*/
	public $bxClosable = true;

	public function __construct()
	{
		$this->init();
	}

	protected function init()
	{
		if(self::$isNodeListAsArray)
		{
			$this->childNodes = array();
		}
		else
		{
			$this->childNodes = new NodeList;
		}
	}

	public function getNodeType()
	{
		return $this->nodeType;
	}

	public function getParentNode()
	{
		return $this->parentNode;
	}

	public function setParentNode(Node $node = null)
	{
		$this->parentNode = $node;
	}

	public function hasAttributes()
	{
		if(!$this->attributes)
		{
			return false;
		}

		if(self::$isNodeListAsArray)
		{
			return count($this->attributes) > 0;
		}
		else
		{
			return $this->attributes->getLength() > 0;
		}
	}

	public function getAttributes()
	{
		return $this->attributes;
	}

	public function getNodeName()
	{
		return $this->nodeName;
	}

	public function getNodeValue()
	{
		return $this->nodeValue;
	}

	public function getTextContent()
	{
		return $this->nodeValue;
	}

	public function getOwnerDocument()
	{
		return $this->ownerDocument;
	}

	public function setOwnerDocument(Document $owner)
	{
		$this->ownerDocument = $owner;
	}

	public function getFirstChild()
	{
		$child = null;
		foreach($this->getChildNodesArray() as $child)
		{
			break;
		}

		return $child;
	}

	public function getLastChild()
	{
		$child = null;
		foreach($this->getChildNodesArray() as $child)
		{

		}

		return $child;
	}

	public function getPreviousSibling()
	{
		$searchableSibling = null;
		if($this->parentNode)
		{
			$previousSibling = null;
			foreach($this->parentNode->getChildNodesArray() as $sibling)
			{
				if($this->isEqual($sibling))
				{
					$searchableSibling = $previousSibling;
					break;
				}

				$previousSibling = $sibling;
			}
		}

		return $searchableSibling;
	}

	public function getNextSibling()
	{
		$searchableSibling = null;
		if($this->parentNode)
		{
			$previousSibling = null;
			foreach($this->parentNode->getChildNodesArray() as $sibling)
			{
				if($this->isEqual($previousSibling))
				{
					$searchableSibling = $sibling;
					break;
				}

				$previousSibling = $sibling;
			}
		}

		return $searchableSibling;
	}

	/*
	* @return NodeList|Node[]
	*/
	public function getChildNodes()
	{
		return $this->childNodes;
	}

	/*
	* @return Node[]
	*/
	public function getChildNodesArray()
	{
		if(self::$isNodeListAsArray)
		{
			return $this->childNodes;
		}
		else
		{
			return $this->childNodes->get();
		}
	}

	public function setChildNodesArray($childList)
	{
		if(self::$isNodeListAsArray)
		{
			$this->childNodes = $childList;
		}
		else
		{
			$this->childNodes->set($childList);
		}
	}

	public function hasChildNodes()
	{
		if(self::$isNodeListAsArray)
		{
			return (count($this->getChildNodes()) > 0);
		}
		else
		{
			return ($this->getChildNodes()->getLength() > 0);
		}
	}

	protected function haveChild($checkingChild)
	{
		foreach($this->childNodes as $child)
		{
			if($child === $checkingChild)
			{
				return true;
			}
		}

		return false;
	}

	/*
	 * Adds the node newChild to the end of the list of children of this node.
	 * If the newChild is already in the tree, it is first removed.
	 * */
	public function appendChild(Node $newChild)
	{
		$this->insertBefore($newChild);
	}

	public function insertBefore(Node $newChild, Node $refChild = null)
	{
		if($newChild->getOwnerDocument() !== $this->getOwnerDocument())
		{
			throw new DomException('Node newChild was created from a different document than the one that created this node', DomException::WRONG_DOCUMENT_ERR);
		}

		if($refChild && !$this->haveChild($refChild))
		{
			throw new DomException('Node refChild not found in childList', DomException::NOT_FOUND_ERR);
		}

		if($this->haveChild($newChild))
		{
			$this->removeChild($newChild);
		}



		$childList = $this->getChildNodesArray();
		if($refChild)
		{
			$childListNew = array();
			foreach($childList as $child)
			{
				if($refChild === $child)
				{
					$childListNew[] = $newChild;
				}
				$childListNew[] = $child;
			}
			$childList = $childListNew;
		}
		else
		{
			$childList[] = $newChild;
		}

		$this->setChildNodesArray($childList);
		$newChild->setParentNode($this);

		return $newChild;
	}

	public function removeChild(Node $oldChild)
	{
		$childList = $this->getChildNodesArray();
		$childListNew = array();
		$isFound = false;
		foreach($childList as $child)
		{
			if($oldChild !== $child)
			{
				$childListNew[] = $child;
			}
			else
			{
				$isFound = true;
			}
		}

		if($isFound)
		{
			$this->setChildNodesArray($childListNew);
			$oldChild->setParentNode(null);
		}
		else
		{
			throw new DomException('Node not found in childList', DomException::NOT_FOUND_ERR);
		}

		return $oldChild;
	}

	static public function replaceChild(Node $newChild, Node $oldChild)
	{
		throw new DomException('Not implemented');
	}

	static public function isEqual(Node $node = null)
	{
		if($node && $node === $this)
		{
			return true;
		}

		return false;
	}

	public function getOuterHTML()
	{
		return $this->getOwnerDocument()->getParser()->getSource($this);
	}

	public function getInnerHTML()
	{
		$html = '';
		if(!$this->hasChildNodes())
		{
			return $html;
		}

		$parser = $this->getOwnerDocument()->getParser();
		foreach($this->getChildNodesArray() as $child)
		{
			$html .= $parser->getSource($child);
		}

		return $html;
	}

	public function setInnerHTML($html)
	{
		foreach($this->getChildNodesArray() as $child)
		{
			$this->removeChild($child);
		}
		$this->getOwnerDocument()->getParser()->parse($html, $this);
	}

	/*
	 * @param string $queryString
	 * @return Node[]
	 * */
	static public function querySelectorAll($queryString)
	{
		return QueryEngine::getQuerySelectorEngine()->query($queryString, $this);
	}

	/*
	 * @param string $queryString
	 * @return Node|null
	 * */
	static public function querySelector($queryString)
	{
		$list = QueryEngine::getQuerySelectorEngine()->query($queryString, $this, 1);
		return current($list);
	}

	public function closest($queryString)
	{
		$list = $this->getOwnerDocument()->getQueryEngine()->query($queryString, $this, 1, QueryEngine::DIR_UP);
		return current($list);
	}

	static public function toString()
	{
		throw new DomException('Not implemented');
	}

	public function toArray()
	{
		$childNodes = array();
		if($this->hasChildNodes())
		{
			foreach($this->getChildNodesArray() as $child)
			{
				/* @var $child Node*/
				$childNodes[] = $child->toArray();
			}
		}

		$attributes = array();
		if($this->hasAttributes())
		{
			foreach($this->getAttributes() as $attr)
			{
				/* @var $attr Attr*/
				$attributes = array_merge($attributes, $attr->toArray());
			}
		}

		return array(
			'nodeType' => $this->nodeType,
			'nodeName' => $this->nodeName,
			'textContent' => $this->getTextContent(),
			'attributesCount' => count($attributes),
			'attributes' => $attributes,
			'childNodesCount' => count($childNodes),
			'childNodes' => $childNodes,
		);
	}
}