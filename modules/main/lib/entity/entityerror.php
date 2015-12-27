<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2015 Bitrix
 */

namespace Bitrix\Main\Entity;

class EntityError extends \Bitrix\Main\Error
{
	static public function __construct($message, $code='BX_ERROR')
	{
		parent::__construct($message, $code);
	}
}
