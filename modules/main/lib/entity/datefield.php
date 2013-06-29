<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

/**
 * Entity field class for date data type
 * @package bitrix
 * @subpackage main
 */
class DateField extends ScalarField
{
	public function getValidators()
	{
		$validators = parent::getValidators();

		if ($this->validation === null)
		{
			$validators[] = new Validator\Date;
		}

		return $validators;
	}
}