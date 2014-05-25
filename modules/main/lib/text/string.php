<?php
namespace Bitrix\Main\Text;

class String
{
	public static function strrpos($haystack, $needle)
	{
		if (defined("BX_UTF"))
		{
			//mb_strrpos does not work on invalid UTF-8 strings
			$ln = strlen($needle);
			for ($i = strlen($haystack) - $ln; $i >= 0; $i--)
				if (substr($haystack, $i, $ln) == $needle)
					return $i;

			return false;
		}

		return strrpos($haystack, $needle);
	}

	public static function htmlEncode($string, $flags = ENT_COMPAT)
	{
		return htmlspecialchars($string, $flags, (defined("BX_UTF") ? "UTF-8" : "ISO-8859-1"));
	}

	public static function htmlDecode($string, $flags = ENT_COMPAT)
	{
		return htmlspecialchars_decode($string, $flags);
	}

	public static function getBinaryLength($str)
	{
		return function_exists('mb_strlen') ? mb_strlen($str, 'latin1') : strlen($str);
	}

	public static function getBinarySubstring($str, $start)
	{
		if(function_exists('mb_substr'))
		{
			$length = (func_num_args() > 2? func_get_arg(2) : self::getBinaryLength($str));
			return mb_substr($str, $start, $length, 'latin1');
		}
		if(func_num_args() > 2)
		{
			return substr($str, $start, func_get_arg(2));
		}
		return substr($str, $start);
	}
}
