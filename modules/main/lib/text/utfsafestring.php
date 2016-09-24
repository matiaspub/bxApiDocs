<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Main\Text;

class UtfSafeString
{
	public static function getLastPosition($haystack, $needle)
	{
		if (defined("BX_UTF"))
		{
			//mb_strrpos does not work on invalid UTF-8 strings
			$ln = strlen($needle);
			for ($i = strlen($haystack) - $ln; $i >= 0; $i--)
			{
				if (substr($haystack, $i, $ln) == $needle)
				{
					return $i;
				}
			}
			return false;
		}

		return strrpos($haystack, $needle);
	}
}