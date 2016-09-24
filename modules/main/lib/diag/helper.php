<?php
namespace Bitrix\Main\Diag;

class Helper
{
	/**
	 * Returns current Unix timestamp with microseconds.
	 *
	 * @return float
	 */
	
	/**
	* <p>Статический метод возвращает текущую метку времени Unix с микросекундами.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return float 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/helper/getcurrentmicrotime.php
	* @author Bitrix
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
	
	/**
	* <p>Статический метод возвращает трассировку массива.</p>
	*
	*
	* @param integer $limit  Максимальное стек элементов для возврата.
	*
	* @param integer $null  Передаваемые опции для функции debug_backtrace.
	*
	* @param integer $options = null Количество пропускаемых фреймов стека.
	*
	* @param integer $skip = 1 
	*
	* @return array 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://www.php.su/debug_backtrace" >debug_backtrace</a></li> </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/helper/getbacktrace.php
	* @author Bitrix
	*/
	public static function getBackTrace($limit = 0, $options = null, $skip = 1)
	{
		if(!defined("DEBUG_BACKTRACE_PROVIDE_OBJECT"))
		{
			// define("DEBUG_BACKTRACE_PROVIDE_OBJECT", 1);
		}

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
