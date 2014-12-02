<?php
namespace Bitrix\Main\Diag;

class Helper
{
	/**
	 * Returns current Unix timestamp with microseconds.
	 *
	 * @return float
	 */
	public static function getCurrentMicrotime()
	{
		return microtime(true);
	}

	/**
	 * Returns array backtrace.
	 *
	 * @param integer $limit Maximum stack elements to return.
	 * @param null|integer $options Passed to debug_backtrace options.
	 * @param integer $skip How many stack frames to skip.
	 *
	 * @return array
	 * @see debug_backtrace
	 */
	public static function getBackTrace($limit = 0, $options = null, $skip = 1)
	{
		if ($options === null)
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
			$trace = debug_backtrace($options, ($limit > 0? $limit + 1: 0));
		}

		if ($limit > 0)
		{
			return array_slice($trace, $skip, $limit);
		}

		return array_slice($trace, $skip);
	}
}
