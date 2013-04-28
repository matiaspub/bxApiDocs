<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

/**
 * Entity field class for string data type
 * @package bitrix
 * @subpackage main
 */
class StringField extends ScalarField
{
	/**
	 * Shortcut for Regexp validator
	 * @var null|string
	 */
	protected $format = null;

	function __construct($name, $dataType, Base $entity, $parameters = array())
	{
		parent::__construct($name, $dataType, $entity, $parameters);

		if (!empty($parameters['format']))
		{
			$this->format = $parameters['format'];
		}
	}

	/**
	 * Shortcut for Regexp validator
	 * @return null|string
	 */
	static public function getFormat()
	{
		return $this->format;
	}

	static public function getValidators()
	{
		$validators = parent::getValidators();

		if ($this->format !== null)
		{
			$validators[] = new Validator\RegExp($this->format);
		}

		return $validators;
	}
}