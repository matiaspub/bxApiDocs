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
class Date extends Base
{
	public function validate($value, $primary, array $row, Entity\Field $field)
	{
		if (CheckDateTime($value, FORMAT_DATE))
		{
			return true;
		}

		return $this->getErrorMessage($value, $field);
	}
}
