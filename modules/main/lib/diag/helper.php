<?php
namespace Bitrix\Main\Diag;

class Helper
{
	public static function getCurrentMicrotime()
	{
		return microtime(true);
	}

	public static function getBackTrace($limit = 0, $options = null)
	{
		if($options === null)
		{
			$options = ~DEBUG_BACKTRACE_PROVIDE_OBJECT;
		}
		if (PHP_VERSION_ID < 50306)
		{
			$trace = debug_backtrace($options & DEBUG_BACKTRACE_PROVIDE_OBJECT);
		}
		elseif (PHP_VERSION_ID < 50400)
		{
			$trace = debug_backtrace($options);
		}
		else
		{
			$trace = debug_backtrace($options, ($limit > 0? $limit + 1 : 0));
		}
		if($limit > 0)
		{
			return array_slice($trace, 1, $limit);
		}
		return array_slice($trace, 1);
	}
}
