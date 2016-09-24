<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2013 Bitrix
 */

namespace Bitrix\Main\Entity\Validator;

use Bitrix\Main\Entity;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

abstract class Base implements Entity\IValidator
{
	/**
	 * @var string
	 */
	protected $errorPhraseCode = 'MAIN_ENTITY_VALIDATOR';
	protected $errorPhrase;

	/**
	 * @param null $errorPhrase
	 * @throws ArgumentTypeException
	 */
	public function __construct($errorPhrase = null)
	{
		if ($errorPhrase !== null && !is_string($errorPhrase))
		{
			throw new ArgumentTypeException('errorPhrase', 'string');
		}

		if ($errorPhrase !== null)
		{
			$this->errorPhrase = $errorPhrase;
		}
	}

	/**
	 * @param $value
	 * @param \Bitrix\Main\Entity\Field $field
	 * @param null|string $errorPhrase
	 * @param null|array $additionalTemplates
	 *
	 * @return string
	 */
	protected function getErrorMessage($value, Entity\Field $field, $errorPhrase = null, $additionalTemplates = null)
	{
		if ($errorPhrase === null)
		{
			$errorPhrase = ($this->errorPhrase !== null? $this->errorPhrase : Loc::getMessage($this->errorPhraseCode));
		}

		$langValues = array(
			'#VALUE#' => $value,
			'#FIELD_NAME#' => $field->getName(),
			'#FIELD_TITLE#' => $field->getTitle()
		);
		if (is_array($additionalTemplates))
		{
			$langValues += $additionalTemplates;
		}

		return str_replace(
			array_keys($langValues),
			array_values($langValues),
			$errorPhrase
		);
	}
}
