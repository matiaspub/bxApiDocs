<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2013 Bitrix
 */

namespace Bitrix\Main\Entity\Validator;

use Bitrix\Main\Entity;
use \Bitrix\Main\Config\ConfigurationException;

IncludeModuleLangFile(__FILE__);

/**
 * Class description
 * @package    bitrix
 * @subpackage main
 */
abstract class Base implements Entity\IValidator
{
	/**
	 * @var string
	 */
	protected $errorPhrase = 'MAIN_ENTITY_VALIDATOR';

	/**
	 * @param null $errorPhrase
	 * @throws ConfigurationException
	 */
	public function __construct($errorPhrase = null)
	{
		if ($errorPhrase !== null && !is_string($errorPhrase))
		{
			throw new ConfigurationException('Error phrase should be a string');
		}

		if ($errorPhrase !== null)
		{
			$this->errorPhrase = $errorPhrase;
		}
	}

	/**
	 * @param                           $value
	 * @param \Bitrix\Main\Entity\Field $field
	 * @param null                      $errorPhrase
	 *
	 * @return mixed
	 */
	protected function getErrorMessage($value, Entity\Field $field, $errorPhrase = null)
	{
		if ($errorPhrase === null)
		{
			$errorPhrase = $this->errorPhrase;
		}

		$langValues = array(
			'#VALUE#' => $value,
			'#FIELD_NAME#' => $field->getName(),
			'#FIELD_TITLE#' => $field->getTitle()
		);

		if (HasMessage($errorPhrase))
		{
			return GetMessage($errorPhrase, $langValues);
		}
		else
		{
			return str_replace(
				array_keys($langValues),
				array_values($langValues),
				$errorPhrase
			);
		}
	}
}
