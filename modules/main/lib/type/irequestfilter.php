<?php
namespace Bitrix\Main\Type;

use Bitrix\Main;

interface IRequestFilter
{
	/**
	 * @param array $values
	 * @return array
	 */
	public static function filter(array $values);
}
