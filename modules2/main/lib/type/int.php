<?php
namespace Bitrix\Main\Type;

class Int
{
	public static function isInteger($val)
	{
		return ($val."!" === intval($val)."!");
	}
}
