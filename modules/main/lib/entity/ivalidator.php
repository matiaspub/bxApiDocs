<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2013 Bitrix
 */

namespace Bitrix\Main\Entity;

interface IValidator
{
	/**
	 * @param       $value
	 * @param       $primary
	 * @param array $row
	 * @param Field $field
	 *
	 * @return string|boolean|EntityError
	 */
	static public function validate($value, $primary, array $row, Field $field);
}
