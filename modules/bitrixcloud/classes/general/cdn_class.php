<?php
class CBitrixCloudCDNClass
{
	private $name = "";
	private $extensions = /*.(array[int]string).*/ array();
	/**
	 *
	 * @return string
	 *
	 */
	public function getName()
	{
		return $this->name;
	}
	/**
	 *
	 * @return array[int]string
	 *
	 */
	public function getExtensions()
	{
		return $this->extensions;
	}
	/**
	 *
	 * @param array[int]string $extensions
	 * @return CBitrixCloudCDNClass
	 *
	 */
	public function setExtensions($extensions)
	{
		$this->extensions = /*.(array[int]string).*/ array();
		if (is_array($extensions))
		{
			foreach ($extensions as $extension)
			{
				$extension = trim($extension, " \t\n\r");
				if ($extension != "")
					$this->extensions[] = $extension;
			}
		}
		return $this;
	}
	/**
	 *
	 * @param string $name
	 * @param array[int]string $extensions
	 * @return void
	 */
	public function __construct($name, $extensions)
	{
		$this->name = $name;
		$this->setExtensions($extensions);
	}
	/**
	 *
	 * @param CDataXMLNode $node
	 * @return CBitrixCloudCDNClass
	 *
	 */
	public static function fromXMLNode(CDataXMLNode $node)
	{
		$name = $node->getAttribute("name");
		$extensions = /*.(array[int]string).*/ array();
		$nodeExtensions = $node->elementsByName("extension");
		foreach ($nodeExtensions as $nodeExtension)
		{
			$extensions[] = $nodeExtension->textContent();
		}
		return new CBitrixCloudCDNClass($name, $extensions);
	}
}
class CBitrixCloudCDNClasses
{
	private $classes = /*.(array[string]CBitrixCloudCDNClass).*/ array();
	/**
	 *
	 * @param CBitrixCloudCDNClass $file_class
	 * @return CBitrixCloudCDNClasses
	 *
	 */
	public function addClass(CBitrixCloudCDNClass $file_class)
	{
		$this->classes[$file_class->getName()] = $file_class;
		return $this;
	}
	/**
	 *
	 * @param string $class_name
	 * @return CBitrixCloudCDNClass
	 *
	 */
	public function getClass($class_name)
	{
		return $this->classes[$class_name];
	}
	/**
	 *
	 * @param CDataXMLNode $node
	 * @return CBitrixCloudCDNClasses
	 *
	 */
	public static function fromXMLNode(CDataXMLNode $node)
	{
		$classes = new CBitrixCloudCDNClasses;
		foreach ($node->children() as $sub_node)
		{
			$classes->addClass(CBitrixCloudCDNClass::fromXMLNode($sub_node));
		}
		return $classes;
	}
	/**
	 *
	 * @param CBitrixCloudOption $option
	 * @return CBitrixCloudCDNClasses
	 *
	 */
	public static function fromOption(CBitrixCloudOption $option)
	{
		$classes = new CBitrixCloudCDNClasses;
		foreach ($option->getArrayValue() as $class_name => $extensions)
		{
			$classes->addClass(new CBitrixCloudCDNClass($class_name, explode(",", $extensions)));
		}
		return $classes;
	}
	/**
	 *
	 * @param CBitrixCloudOption $option
	 * @return CBitrixCloudCDNClasses
	 *
	 */
	public function saveOption(CBitrixCloudOption $option)
	{
		$classes = /*.(array[string]string).*/ array();
		foreach ($this->classes as $class_name => $file_class)
		{
			/* @var CBitrixCloudCDNClass $file_class */
			$classes[$class_name] = implode(",", $file_class->getExtensions());
		}
		$option->setArrayValue($classes);
		return $this;
	}
}
