<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2012 Bitrix
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
class RegExp extends Base
{
	/**
	 * @var string
	 */
	protected $pattern;

	/**
	 * @var string
	 */
	protected $errorPhrase = 'MAIN_ENTITY_VALIDATOR_REGEXP';

	/**
	 * @param string $pattern
	 * @param null   $errorPhrase
	 *
	 * @throws ConfigurationException
	 */
	public function __construct($pattern, $errorPhrase = null)
	{
		if (!is_string($pattern))
		{
			throw new ConfigurationException('Pattern should be a string');
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
