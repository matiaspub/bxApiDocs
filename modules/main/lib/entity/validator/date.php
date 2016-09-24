<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2013 Bitrix
 */

namespace Bitrix\Main\Entity\Validator;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;

class Date extends Base
{
	public function validate($value, $primary, array $row, Entity\Field $field)
	{
		if (empty($value))
		{
			return true;
		}

		if ($value instanceof Type\DateTime)
		{
			// self-validating object
			return true;
		}

		if (\CheckDateTime($value, FORMAT_DATE))
		{
			return true;
		}

		return $this->getErrorMessage($value, $field);
	}
}
