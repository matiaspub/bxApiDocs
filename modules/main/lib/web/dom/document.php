<?php
namespace Bitrix\Main\Web\DOM;

class Document extends Node
{
	/** @var $parser Parser */
	protected $parser;

	/** @var $queryEngine QueryEngine */
	protected $queryEngine;

	/*
	 * @return void
	 */
	public function __construct()
	{
		$this->init();
		$this->nodeType = self::DOCUMENT_NODE;
		$this->nodeName = '#document';

		$this->ownerDocument = $this;

		$this->setParser(Parser::getHtmlParser());
	}

	/*
	 * @param string $source
	 * @return void
	 */
	public function loadHTML($source)
	{
		$this->parser->parse($source, $this);
	}

	/*
	 * @param null|Node $node
	 * @return string
	 */
	public function saveHTML(Node $node = null)
	{
		if($node === null)
		{
			$result = '';

			if(self::$isNodeListAsArray)
			{
				$childNodes = $this->getChildNodesArray();
				foreach ($childNodes as $child)
				{
					/** @var $child Node */
					$result .= $child->getOuterHTML();
				}
			}
			else
			{
				for($i = 0; $i < $this->getChildNodes()->getLength(); $i++)
				{
					$child = $this->getChildNodes()->item($i);
					/** @var $child Node */
					if($child)
					{
						$result .= $child->getOuterHTML();
					}
				}
			}

			return $result;
		}
		else
		{
			return $node->getOuterHTML();
		}
	}

	/*
	 * @return QueryEngine
	 */
	public function getQueryEngine()
	{
		if(!$this->queryEngine)
		{
			$this->queryEngine = QueryEngine::getQuerySelectorEngine();
		}

		return $this->queryEngine;
	}

	/*
	 * @param QueryEngine $engine
	 * @return void
	 */
	public function setQueryEngine(QueryEngine $engine)
	{
		$this->queryEngine = $engine;
	}

	/*
	 * @return Parser
	 */
	public function getParser()
	{
		return $this->parser;
	}

	/*
	 * @param Parser $parser
	 * @return void
	 */
	public function setParser(Parser $parser)
	{
		$this->parser = $parser;
	}

	/*
	 * Changes the ownerDocument of a node, its children, as well as the attached attribute nodes if there are any.
	 * If the node has a parent it is first removed from its parent child list.
	 * This effectively allows moving a subtree from one document to another.
	 */
	static public function adoptNode(Node $source)
	{
		if($source->getParentNode())
		{
			$source->getParentNode()->removeChild($source);
		}

		$source->setOwnerDocument($this);

		if($source->hasAttributes())
		{
			$attrList = $source->getAttributes()->get();
			foreach($attrList as $attr)
			{
				/** @var $attr Attr */
				$attr->setOwnerDocument($this);
			}
		}

		if($source->hasChildNodes())
		{
			foreach($source->getChildNodesArray() as $child)
			{
				/** @var $child Node */
				$child->setOwnerDocument($this);
			}
		}
	}

	/*
	 * @param string $tagName
	 * @return Element
	 */
	static public function createElement($tagName)
	{
		static $classByTag = array();

		$tagName = strtoupper($tagName);
		$elementClass = "Bitrix\\Main\\Web\\DOM\\Element\\" . $tagName;

		if(!isset($classByTag[$tagName]))
		{
			if(class_exists($elementClass))
			{
				$classByTag[$tagName] = $elementClass;
			}
			else
			{
				$classByTag[$tagName] = false;
			}
		}

		if($classByTag[$tagName])
		{
			$elementClass = $classByTag[$tagName];
			$node = new $elementClass($tagName);
		}
		else
		{
			$node = new Element($tagName);
		}

		$node->setOwnerDocument($this);
		return $node;
	}

	/*
	 * @param string $name
	 * @param string $value
	 * @return Attr
	 */
	static public function createAttribute($name, $value)
	{
		$node = new Attr($name, $value);
		$node->setOwnerDocument($this);
		return $node;
	}

	/*
	 * @param string $comment
	 * @return Comment
	 */
	static public function createComment($comment)
	{
		$node = new Comment($comment);
		$node->setOwnerDocument($this);
		return $node;
	}

	/*
	 * @param string $text
	 * @return Text
	 */
	static public function createTextNode($text)
	{
		$node = new Text($text);
		$node->setOwnerDocument($this);
		return $node;
	}

	/*
	 * @return null
	 */
	static public function createDocumentFragment()
	{
		throw new DomException('Not implemented');
	}

	/*
	 * @return null|Node
	 */
	public function getElementById($id)
	{
		$resultList = $this->getElementsByAttr('id', $id, 1);
		return (count($resultList) > 0) ? current($resultList) : null;
	}

	/*
	 * @return null|Node
	 */
	public function getElementByClassName($className)
	{
		$resultList = $this->getElementsByClassName($className, 1);
		return (count($resultList) > 0) ? current($resultList) : null;
	}

	/*
	 * @return array|NodeList
	 */
	public function getElementsByName($name)
	{
		return $this->getElementsByAttr('name', $name);
	}

	static public function getTextContent()
	{
		return null;
	}

	/*
	 * @return array|NodeList
	 */
	public function getElementsByAttr($attrName, $attrValue = null, $limit = 0)
	{
		$attrName = strtolower($attrName);
		$nodeList = $this->getQueryEngine()->walk(
			array(
				array(
					QueryEngine::FILTER_ATTR_VALUE => array(
						array(
							'name' => $attrName,
							'value' => $attrValue,
							'operation' => QueryEngine::FILTER_OPERATION_EQUAL,
						)
					)
				)
			),
			null, $this, $limit
		);

		if(Node::$isNodeListAsArray)
		{
			return $nodeList;
		}
		else
		{
			return new NodeList($nodeList);
		}
	}

	/*
	 * @return array|NodeList
	 */
	public function getElementsByTagName($tagName)
	{
		$tagName = strtoupper($tagName);
		$nodeList = $this->getQueryEngine()->walk(
			array(
				array(QueryEngine::FILTER_NODE_NAME => $tagName)
			),
			null, $this
		);

		if(Node::$isNodeListAsArray)
		{
			return $nodeList;
		}
		else
		{
			return new NodeList($nodeList);
		}
	}

	/*
	 * @return array|NodeList
	 */
	public function getElementsByClassName($className, $limit = 0)
	{
		$nodeList = $this->getQueryEngine()->walk(
			array(
				array(QueryEngine::FILTER_ATTR_CLASS_NAME => $className)
			),
			null, $this, $limit
		);

		if(Node::$isNodeListAsArray)
		{
			return $nodeList;
		}
		else
		{
			return new NodeList($nodeList);
		}
	}

	/*
	 * @return null|Element
	 */
	public function getDocumentElement()
	{
		foreach($this->getChildNodesArray() as $child)
		{
			/** @var $child Node */
			if($child->getNodeName() === 'HTML')
			{
				return $child;
			}
		}

		return null;
	}

	/*
	 * Get HEAD element
	 * @return null|Element
	 */
	public function getHead()
	{
		if(!$this->getDocumentElement())
		{
			return null;
		}

		foreach($this->getDocumentElement()->getChildNodesArray() as $child)
		{
			/** @var $child Node */
			if($child->getNodeName() === 'HEAD')
			{
				return $child;
			}
		}

		return null;
	}

	/*
	 * Get BODY element
	 * @return null|Element
	 */
	public function getBody()
	{
		if(!$this->getDocumentElement())
		{
			return null;
		}

		foreach($this->getDocumentElement()->getChildNodesArray() as $child)
		{
			/** @var $child Node */
			if($child->getNodeName() === 'BODY')
			{
				return $child;
			}
		}

		return null;
	}
}