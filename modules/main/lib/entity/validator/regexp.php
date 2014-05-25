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

class RegExp extends Base
{
	/**
	 * @var string
	 */
	protected $pattern;

	/**
	 * @var string
	 */
	protected $errorPhraseCode = 'MAIN_ENTITY_VALIDATOR_REGEXP';

	/**
	 * @param string $pattern
	 * @param null   $errorPhrase
	 *
	 * @throws ArgumentTypeException
	 */
	public function __construct($pattern, $errorPhrase = null)
	{
		if (!is_string($pattern))
		{
			throw new ArgumentTypeException('pattern', 'string');
		}

		$this->pattern = $pattern;

		parent::__construct($errorPhrase);
	}


	public function validate($value, $primary, array $row, Entity\Field $field)
	{
		if (preg_match($this->pattern, $value))
		{
			return true;
		}

		return $this->getErrorMessage($value, $field);
	}
}
