<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
/*
\Bitrix\Main\Loader::includeModule('iblock');

$arFields = array(
	"IBLOCK_ID" => 422,
	"NAME" => "Product name",
	"CODE" => "main code",
	"IBLOCK_SECTION_ID" => 12744,
	"PROPERTY_VALUES" => array(
		"CML2_ARTICLE" => "XX-SM6-XXXXXXXXX",
	),
);
$element = new \Bitrix\Iblock\Template\Entity\Element(0);
$element->setFields($arFields);
echo "<pre>";//print_r($element);
$arSkuCollection = array();
for ($i = 0; $i < 5; $i++)
{
	$arSkuCollection[$i] = array(
		"IBLOCK_ID" => 423,
		"NAME" => null,
		"CODE" => "code($i)",
		"PROPERTY_VALUES" => array(
			"CML2_LINK" => $element,
			"1377" => "0x0555555".$i,
			"1875" => 1045,
		),
	);
	$sku = new \Bitrix\Iblock\Template\Entity\Element(0);
	$sku->setFields($arSkuCollection[$i]);//print_r($sku);
	$arSkuCollection[$i]["NAME"] = \Bitrix\Iblock\Template\Engine::process(
		$sku
		//,'{=this.property.cml2_link.name}:  {=this.property.cml2_link.property.CML2_ARTICUL}, {=this.property.cml2_bar_code}'
		//,'Q{=this.code}Q'
		//,'Q{=this.property}Q'
		,'Q{=this.property.1377}Q{=this.property.1875}Q'
		//,'Q{=this.property.CML2_LINK}Q'
		//,'Q{=this.property.CML2_LINK.name}Q'
		//,'Q{=this.property.CML2_LINK.code}Q'
		//,'Q{=this.property.CML2_LINK.property.CML2_ARTICLE}Q'
		//,'Q{=this.property.CML2_LINK.parent.property.str}Q'
		//,'Q{=this.catalog.price.base}Q'
		//,'Q{=this.catalog.weight}Q'
		//,'Q{=this.catalog.measure}Q'
		//,'Q{=this.catalog.store.1.name}Q'
		//,'Q{=catalog.store}Q'
	);
}
echo "<pre>",htmlspecialcharsEx(print_r($arSkuCollection,1)),"</pre>";
*/
namespace Bitrix\Iblock\Template;
/**
 * Class Engine
 * Provides interface for templates processing.
 * @package Bitrix\Iblock\Template
 */
class Engine
{
	/**
	 * Takes an entity (Element, Section or Iblock) and processes the template.
	 * <code>
	 * if (\Bitrix\Main\Loader::includeModule('iblock'))
	 * &#123;
	 * 	$e = new \Bitrix\Iblock\Template\Entity\Element(6369);
	 * 	echo "<pre>", print_r(\Bitrix\Iblock\Template\Engine::process($e, "Name: {=this.Name}. Code:{=this.code}"), 1), "</pre>";
	 * &#125;
	 * </code>
	 *
	 * @param Entity\Base $entity Context entity for template processing.
	 * @param string $template Template to make substitutions in.
	 *
	 * @return string
	 */
	public static function process(Entity\Base $entity, $template)
	{
		$rootNode = self::parseTemplateTree($template, new NodeRoot);
		return $rootNode->process($entity);
	}

	/**
	 * Splits template by tokens and builds execution tree.
	 *
	 * @param string $template Source expression.
	 * @param NodeRoot $parent Root node.
	 *
	 * @return NodeRoot
	 */
	protected static function parseTemplateTree($template, NodeRoot $parent)
	{
		list($template, $modifiers) = Helper::splitTemplate($template);
		if ($modifiers != "")
			$parent->setModifiers($modifiers);

		$parsedTemplate = preg_split('/({=|})/',  $template, -1, PREG_SPLIT_DELIM_CAPTURE);
		while (list(,$token) = each($parsedTemplate))
		{
			$node = null;

			if ($token === "{=")
			{
				$node = self::parseFormula($parsedTemplate);
			}
			elseif ($token !== "")
			{
				$node = new NodeText($token);
			}

			if ($node)
				$parent->addChild($node);
		}
		return $parent;
	}

	/**
	 * Parses "{=" part of the template. Moves internal pointer right behind balanced "}"
	 * after {= a field of the entity should follow
	 * or a function call.
	 *
	 * @param array[]string &$parsedTemplate Template tokens.
	 *
	 * @return NodeEntityField|NodeFunction|null
	 */
	protected static function parseFormula(array &$parsedTemplate)
	{
		$node = null;
		if (list(,$token) = each($parsedTemplate))
		{
			if (preg_match("/^([a-zA-Z0-9_]+\\.[a-zA-Z0-9_.]+)\\s*\$/", $token, $match))
			{
				$node = new NodeEntityField($match[1]);
			}
			elseif (preg_match("/^([a-zA-Z0-9_]+)(.*)\$/", $token, $match))
			{
				$node = new NodeFunction($match[1]);
				self::parseFunctionArguments($match[2], $parsedTemplate, $node);
			}
		}
		//Eat up to the formula end
		while (list(,$token) = each($parsedTemplate))
		{
			if ($token === "}")
				break;
		}
		return $node;
	}

	/**
	 * Adds function arguments to a $function.
	 * An formula may be evaluated as oa argument.
	 * An number or
	 * A string in double quotes.
	 *
	 * @param string $token Expression string.
	 * @param array[]string &$parsedTemplate Template tokens.
	 * @param NodeFunction $function Function object to which arguments will be added.
	 *
	 * @return void
	 */
	protected static function parseFunctionArguments($token, array &$parsedTemplate, NodeFunction $function)
	{
		$token = ltrim($token, " \t\n\r");

		if ($token !== "")
			self::explodeFunctionArgument($token, $function);

		while (list(,$token) = each($parsedTemplate))
		{
			if ($token === "}")
			{
				prev($parsedTemplate);
				break;
			}
			elseif ($token === "{=")
			{
				$node = self::parseFormula($parsedTemplate);
				if ($node)
					$function->addParameter($node);
			}
			elseif ($token !== "")
			{
				self::explodeFunctionArgument($token, $function);
			}
		}
	}

	/**
	 * Explodes a string into function arguments.
	 * Numbers or strings.
	 *
	 * @param string $token Expression string.
	 * @param NodeFunction $function Function object to which arguments will be added.
	 *
	 * @return void
	 */
	protected static function explodeFunctionArgument($token, NodeFunction $function)
	{
		if (preg_match_all("/
			(
				[a-zA-Z0-9_]+\\.[a-zA-Z0-9_.]+
				|[0-9]+
				|\"[^\"]*\"
			)
			/x", $token, $wordList)
		)
		{
			foreach ($wordList[0] as $word)
			{
				if ($word !== "")
				{
					if (preg_match("/^([a-zA-Z0-9_]+\\.[a-zA-Z0-9_.]+)\\s*\$/", $word, $match))
					{
						$node = new NodeEntityField($match[1]);
					}
					else
					{
						$node = new NodeText(trim($word, '"'));
					}
					$function->addParameter($node);
				}
			}
		}
	}
}

/**
 * Class NodeBase
 * Base class for template parsed tree nodes.
 * @package Bitrix\Iblock\Template
 */
abstract class NodeBase
{
	/**
	 * Converts internal contents of the node into external presentation.
	 * It's a string or an array of strings.
	 *
	 * @param Entity\Base $entity Sets the context of processing.
	 *
	 * @return string
	 */
	abstract public function process(Entity\Base $entity);
}

/**
 * Class NodeRoot
 * Present simple collection of child nodes
 *
 * @package Bitrix\Iblock\Template
 */
class NodeRoot extends NodeBase
{
	/** @var array[int]NodeBase */
	protected $children = array();
	protected $modifiers = array();

	/**
	 * Appends a child to the children collection.
	 *
	 * @param NodeBase $child Object to be added as a child.
	 *
	 * @return void
	 */
	public function addChild(NodeBase $child)
	{
		$this->children[] = $child;
	}

	/**
	 * Sets modifiers to be used at the last stage of processing.
	 *
	 * @param string $modifiers String which contains modifiers.
	 *
	 * @return void
	 */
	public function setModifiers($modifiers)
	{
		$this->modifiers = array();
		foreach(Helper::splitModifiers($modifiers) as $mod)
		{
			if ($mod == "l")
				$modifierFunction = Functions\Fabric::createInstance("lower");
			else
				$modifierFunction = Functions\Fabric::createInstance("translit", array(
					"replace_space" => substr($mod, 1),
				));
			$this->modifiers[] = $modifierFunction;
		}
	}

	/**
	 * Calls process for each of it's children.
	 * Returns concatenation of their results.
	 *
	 * @param Entity\Base $entity Sets the context of processing.
	 *
	 * @return string
	 */
	public function process(Entity\Base $entity)
	{
		$content = "";
		/** @var NodeBase $child*/
		foreach ($this->children as $child)
		{
			$childContent = $child->process($entity);
			if (is_array($childContent))
				$content .= implode(" ", $childContent);
			else
				$content .= $childContent;
		}
		/** @var Functions\FunctionBase $modifier*/
		foreach ($this->modifiers as $modifier)
		{
			$node = new NodeText($content);
			$arguments = $modifier->onPrepareParameters($entity, array($node));
			$content = $modifier->calculate($arguments);
		}
		return $content;
	}
}

/**
 * Class NodeText
 * Plain text node
 *
 * @package Bitrix\Iblock\Template
 */
class NodeText extends NodeBase
{
	protected $content = "";

	/**
	 * Sets text contents into $content.
	 *
	 * @param string $content A text to be saved.
	 */
	function __construct($content = "")
	{
		$this->content = $content;
	}

	/**
	 * Returns text contents.
	 *
	 * @param Entity\Base $entity Sets the context of processing.
	 *
	 * @return string
	 */
	public function process(Entity\Base $entity)
	{
		return $this->content;
	}
}

/**
 * Class NodeEntityField
 * Represents an entity field in the formula.
 * For example: this.name
 *
 * @package Bitrix\Iblock\Template
 */
class NodeEntityField extends NodeBase
{
	protected $entityField = "";

	/**
	 * Initializes the object.
	 * $entityName and $entityField is case insensitive.
	 *
	 * @param string $entityField Field of the Entity.
	 */
	function __construct($entityField = "")
	{
		$this->entityField = strtolower($entityField);
	}

	/**
	 * Calls "resolve" method of the $entity.
	 * On success returns field value of the resolved.
	 * Otherwise returns empty string.
	 *
	 * @param Entity\Base $entity Sets the context of processing.
	 *
	 * @return string
	 */
	public function process(Entity\Base $entity)
	{
		$entityObject = $entity;
		$pathToField = explode(".", $this->entityField);
		for ($i = 0, $c = count($pathToField)-1; $i < $c; $i++)
		{
			$entityObject = $entityObject->resolve($pathToField[$i]);
		}
		if ($entityObject)
			return $entityObject->getField($pathToField[$c]);
		else
			return "";
	}
}

/**
 * Class NodeFunction
 * Represents a function call in the formula.
 *
 * @package Bitrix\Iblock\Template
 */
class NodeFunction extends NodeBase
{
	protected $functionName = "";
	protected $parameters = array();

	/**
	 * Initialize function object.
	 *
	 * @param string $functionName Name of the function. Case insensitive.
	 */
	public function __construct($functionName = "")
	{
		$this->functionName = strtolower($functionName);
	}

	/**
	 * Adds new parameters to the function call.
	 *
	 * @param NodeBase $parameter A new parameter to be added.
	 *
	 * @return void
	 */
	public function addParameter(NodeBase $parameter)
	{
		$this->parameters[] = $parameter;
	}

	/**
	 * Uses Functions\Fabric to get an instance of the function object by it's name.
	 * On success calls onPrepareParameters, then calculate method.
	 * Otherwise returns an empty string.
	 *
	 * @param Entity\Base $entity Sets the context of processing.
	 *
	 * @return string
	 */
	public function process(Entity\Base $entity)
	{
		$functionObject = Functions\Fabric::createInstance($this->functionName);
		if ($functionObject instanceof Functions\FunctionBase)
		{
			$arguments = $functionObject->onPrepareParameters($entity, $this->parameters);
			return $functionObject->calculate($arguments);
		}
		else
		{
			return "";
		}
	}
}
