<?php
namespace Bitrix\Main\Text;

/**
 * @deprecated PHP7 reserved word
 */
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

	/**
	 * Binary version of strlen
	 * @param $str
	 * @return int
	 */
	public static function getBinaryLength($str)
	{
		return function_exists('mb_strlen') ? mb_strlen($str, 'latin1') : strlen($str);
	}

	/**
	 *
	 * Binary version of substr
	 * @param $str
	 * @param $start
	 * @return string
	 */
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

	/**
	 *
	 * Binary version of strpos
	 * @param $haystack
	 * @param $needle
	 * @param int $offset
	 * @return bool|int
	 */
	public static function getBinaryStrpos($haystack, $needle, $offset = 0)
	{
		if (defined("BX_UTF"))
		{
			if (function_exists("mb_orig_strpos"))
			{
				return mb_orig_strpos($haystack, $needle, $offset);
			}

			return mb_strpos($haystack, $needle, $offset, "latin1");
		}

		return strpos($haystack, $needle, $offset);
	}

	/**
	 *
	 * Binary version of strrpos
	 * @param $haystack
	 * @param $needle
	 * @param int $offset
	 * @return bool|int
	 */
	public static function getBinaryStrrpos($haystack, $needle, $offset = 0)
	{
		if (defined("BX_UTF"))
		{
			if (function_exists("mb_orig_strrpos"))
			{
				return mb_orig_strrpos($haystack, $needle, $offset);
			}

			return mb_strrpos($haystack, $needle, $offset, "latin1");
		}

		return strrpos($haystack, $needle, $offset);
	}

	/**
	 *
	 * Binary version of stripos
	 * @param $haystack
	 * @param $needle
	 * @param int $offset
	 * @return int
	 */
	public static function getBinaryStripos($haystack, $needle, $offset = 0)
	{
		if (defined("BX_UTF"))
		{
			if (function_exists("mb_orig_stripos"))
			{
				return mb_orig_stripos($haystack, $needle, $offset);
			}

			return mb_stripos($haystack, $needle, $offset, "latin1");
		}

		return stripos($haystack, $needle, $offset);
	}

	/**
	 *
	 * Binary version of strripos
	 * @param $haystack
	 * @param $needle
	 * @param int $offset
	 * @return int
	 */
	public static function getBinaryStrripos($haystack, $needle, $offset = 0)
	{
		if (defined("BX_UTF"))
		{
			if (function_exists("mb_orig_strripos"))
			{
				return mb_orig_strripos($haystack, $needle, $offset);
			}

			return mb_strripos($haystack, $needle, $offset, "latin1");
		}

		return strripos($haystack, $needle, $offset);
	}

	/**
	 *
	 * Binary version of strtolower
	 * @param $str
	 * @return string
	 */
	public static function getBinaryStrtolower($str)
	{
		if (defined("BX_UTF"))
		{
			if (function_exists("mb_orig_strtolower"))
			{
				return mb_orig_strtolower($str);
			}

			return mb_strtolower($str, "latin1");
		}

		return strtolower($str);
	}
}
