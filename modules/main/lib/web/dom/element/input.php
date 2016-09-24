<?php
namespace Bitrix\Main\Web\DOM\Element;

use Bitrix\Main\Web\DOM\Element;

class Input extends Element
{
	/**
	 * @var string $name
	 */
	public $name = null;

	public $value = null;

	public function __construct($name, $value = null)
	{
		parent::__construct($name);

		$this->name = strtolower($name);
		if($value !== null)
		{
			$this->value = $value;
		}
	}
}