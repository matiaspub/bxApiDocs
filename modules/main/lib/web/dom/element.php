<?php
namespace Bitrix\Main\Web\DOM;

class Element extends Node
{
	/**
	 * @var Element $parentElement
	 */
	protected $parentElement;
	/**
	 * @var NamedNodeMap $attributes
	 */
	protected $attributes;
	/**
	 * @var string $tagName
	 */
	protected $tagName;
	/**
	 * @var array $classList
	 */
	protected $classList;
	/**
	 * @var string $id
	 */
	protected $id;
	/**
	 * @var string $name
	 */
	protected $name;
	/**
	 * @var string $className
	 */
	protected $className;
	/**
	 * @var string $style
	 */
	protected $style;


	public function __construct($name)
	{
		$this->init();

		$this->nodeType = self::ELEMENT_NODE;
		$this->nodeName = strtoupper($name);
		$this->tagName = $this->nodeName;

		if(self::$isNodeListAsArray)
		{
			$this->attributes = array();
		}
		else
		{
			$this->attributes = new NamedNodeMap;
		}
	}

	public function getTagName()
	{
		return $this->tagName;
	}

	public function getId()
	{
		return $this->getAttribute('id');
	}

	public function setId($id)
	{
		$this->setAttribute('id', $id);
	}

	public function getName()
	{
		return $this->getAttribute('name');
	}

	public function setName($id)
	{
		$this->setAttribute('name', $id);
	}

	public function getStyle()
	{
		return $this->getAttribute('style');
	}

	public function setStyle($style)
	{
		$this->setAttribute('style', $style);
	}

	public function getClassName()
	{
		return $this->getAttribute('class');
	}

	public function setClassName($className)
	{
		$this->setAttribute('class', $className);
	}

	public function getClassList()
	{
		return explode(' ', $this->getClassName());
	}

	static public function setClassList(array $classList)
	{
		return implode(' ', $classList);
	}

	public function getParentElement()
	{
		return $this->parentElement;
	}

	public function getTextContent()
	{
		$content = '';
		foreach($this->getChildNodesArray() as $child)
		{
			if($child->getNodeType() === self::COMMENT_NODE || $child->getNodeType() === self::PROCESSING_INSTRUCTION_NODE )
			{
				continue;
			}

			$content .= $child->getTextContent();
		}

		return $content;
	}

	protected function setParentElement(Element $parentElement)
	{
		$this->parentElement = $parentElement;
	}

	public function getAttributesArray()
	{
		if(self::$isNodeListAsArray)
		{
			return $this->attributes;
		}
		else
		{
			return $this->attributes->get();
		}
	}

	public function setAttributeNode(Attr $attr)
	{
		if(self::$isNodeListAsArray)
		{
			$name = $attr->getName();
			$this->attributes[$name] = $attr;
		}
		else
		{
			$this->getAttributes()->setNamedItem($attr);
		}

		$attr->setParentNode($this);
	}

	public function getAttributeNode($name)
	{
		$result = null;
		if($this->hasAttributes())
		{
			if(self::$isNodeListAsArray)
			{
				$result = $this->attributes[$name];
			}
			else
			{
				$result = $this->attributes->getNamedItem($name);
			}
		}

		return $result;
	}

	public function removeAttributeNode(Attr $oldNode)
	{
		if($this->attributes)
		{
			if(self::$isNodeListAsArray)
			{
				$name = $oldNode->getName();
				unset($this->attributes[$name]);
			}
			else
			{
				$this->attributes->removeNamedItem($oldNode->getName());
			}

			$oldNode->setParentNode(null);
		}
	}

	public function setAttribute($name, $value)
	{
		$attr = $this->getAttributeNode($name);
		if(!$attr)
		{
			$attr = $this->ownerDocument->createAttribute($name, $value);
		}
		else
		{
			$attr->setValue($value);
		}

		$this->setAttributeNode($attr);
	}

	/**
	 * @param string $attributeName
	 * @return bool
	 */
	public function hasAttribute($attributeName)
	{
		$result = false;
		if($this->hasAttributes())
		{
			if(self::$isNodeListAsArray)
			{
				$result = array_key_exists($attributeName, $this->attributes);
			}
			else
			{
				$result = !is_null($this->attributes->getNamedItem($attributeName));
			}
		}
		return $result;
	}

	public function getAttribute($name)
	{
		$attr = $this->getAttributeNode($name);
		if($attr)
		{
			return $attr->getValue();
		}

		return null;
	}

	public function removeAttribute($name)
	{
		$attr = $this->getAttributeNode($name);
		if($attr)
		{
			$this->removeAttributeNode($attr);
		}
	}
}