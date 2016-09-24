<?php
namespace Bitrix\Main\Web\DOM;

class Attr extends Node
{
	/**
	 * @var string $name
	 */
	protected $name = null;

	public $value = null;

	public function __construct($name, $value)
	{
		parent::__construct();

		$this->nodeType = self::ATTRIBUTE_NODE;

		$this->name = strtolower($name);
		$this->value = $value;
	}

	public function toString()
	{
		return $this->name . '="' . $this->value . '"';
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setValue($value)
	{
		$this->value = $value;
	}

	public function toArray()
	{
		return array($this->name => $this->value);
	}
}