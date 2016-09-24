<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Data;

abstract class NosqlConnection extends Connection
{
	abstract public function get($key);
	abstract public function set($key, $value);
}
