<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Main\Text;

class BinaryString
{
	/**
	 * Binary version of strlen.
	 * @param $str
	 * @return int
	 */
	
	/**
	* <p>Статический метод. Возвращает длину строки или <b>0</b>, если строка пуста. Выполняет безопасную с точки зрения многобайтных кодировок операцию. Аналог PHP функции <b>strlen</b>.</p>
	*
	*
	* @param mixed $str  Строка, для которой измеряется длина.
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/text/binarystring/getlength.php
	* @author Bitrix
	*/
	public static function getLength($str)
	{
		return function_exists('mb_strlen') ? mb_strlen($str, 'latin1') : strlen($str);
	}

	/**
	 * Binary version of substr.
	 * @param $str
	 * @param $start
	 * @return string
	 */
	
	/**
	* <p>Статический метод. Возвращает подстроку строки <code>str</code>, начинающейся с <code>start</code> символа по счету или <i>false</i> в случае возникновения ошибки или пустую строку <b>string</b>. Выполняет безопасную с точки зрения многобайтных кодировок операцию. Аналог PHP функции <b>substr</b>. </p>
	*
	*
	* @param mixed $str  Входная строка. Должна содержать хотя бы один символ.
	*
	* @param $st $start  Если значение неотрицательно, то возвращаемая подстрока
	* начинается с указанной позиции от начала строки, считая от нуля.
	* Например, в строке <code>abcdef</code>, в позиции 0 находится символ <b>a</b>, в
	* позиции 2 - символ <b>c</b>, и так далее. <br> Если значение
	* отрицательное, то возвращаемая подстрока начинается с позиции,
	* отстоящей на указанное число символов от конца строки <code>string</code>.
	* <br> Если <code>string</code> меньше либо содержит ровно <code>start</code> символов,
	* будет возвращено <i>false</i>.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/text/binarystring/getsubstring.php
	* @author Bitrix
	*/
	public static function getSubstring($str, $start)
	{
		if(function_exists('mb_substr'))
		{
			$length = (func_num_args() > 2? func_get_arg(2) : self::getLength($str));
			return mb_substr($str, $start, $length, 'latin1');
		}
		if(func_num_args() > 2)
		{
			return substr($str, $start, func_get_arg(2));
		}
		return substr($str, $start);
	}

	/**
	 * Binary version of strpos.
	 * @param $haystack
	 * @param $needle
	 * @param int $offset
	 * @return bool|int
	 */
	
	/**
	* <p>Статический метод. Возвращает позицию, в которой находится искомая строка, относительно начала строки <b>haystack</b>, независимо от смещения <b>offset</b>. Позиция строки отсчитывается от 0, а не от 1. Возвращает <i>false</i>, если искомая строка не найдена. Выполняет безопасную с точки зрения многобайтных кодировок операцию. Аналог PHP функции <b>strpos</b>. </p>
	*
	*
	* @param mixed $haystack  Строка, в которой производится поиск.
	*
	* @param $haystac $needle  Искомая подстрока. Если не является строкой, он приводится к
	* целому и трактуется как код символа.
	*
	* @param integer $offset  Смещение. Если этот параметр указан, то поиск будет начат с
	* указанного количества символов с начала строки. Не может быть
	* отрицательным.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/text/binarystring/getposition.php
	* @author Bitrix
	*/
	public static function getPosition($haystack, $needle, $offset = 0)
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
	 * Binary version of strrpos.
	 * @param $haystack
	 * @param $needle
	 * @param int $offset
	 * @return bool|int
	 */
	
	/**
	* <p>Статический метод ищет позицию последнего вхождения подстроки <b>needle</b> в строку <b>haystack</b>. Выполняет безопасную с точки зрения многобайтных кодировок операцию. Возвращает номер позиции последнего вхождения искомой подстроки относительно начала строки <b>haystack</b>, независимо от направления поиска и смещения <b>offset</b>. Позиция строки отсчитывается от 0, а не от 1. Возвращает <i>false</i>, если искомая строка не найдена.  Аналог PHP функции <b>strrpos</b>.</p>
	*
	*
	* @param mixed $haystack  Строка, в которой производится поиск.
	*
	* @param $haystac $needle  Искомая подстрока. Если не является строкой, то он приводится к
	* целому и трактуется как код символа.
	*
	* @param integer $offset  Если значение указано, то поиск начнется с данного количества
	* символов с начала строки. Если значение отрицательное, то поиск
	* начнется с указанного количества символов от конца строки, но по
	* прежнему будет производится поиск последнего вхождения.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/text/binarystring/getlastposition.php
	* @author Bitrix
	*/
	public static function getLastPosition($haystack, $needle, $offset = 0)
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
	 * Binary version of stripos.
	 * @param $haystack
	 * @param $needle
	 * @param int $offset
	 * @return int
	 */
	
	/**
	* <p>Статический метод. Возвращает позицию, в которой находится искомая строка, относительно начала строки <b>haystack</b>, независимо от смещения <b>offset</b>, без учета регистра. Позиция строки отсчитывается от 0, а не от 1. Возвращает <i>false</i>, если искомая строка не найдена. Выполняет безопасную с точки зрения многобайтных кодировок операцию. Аналог PHP функции <b>stripos</b>. </p>
	*
	*
	* @param mixed $haystack  Строка, в которой производится поиск.
	*
	* @param $haystac $needle  Искомая подстрока
	*
	* @param integer $offset  Если этот параметр указан, то поиск будет начат с указанного
	* количества символов с начала строки. Не может быть отрицательным.
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/text/binarystring/getpositionignorecase.php
	* @author Bitrix
	*/
	public static function getPositionIgnoreCase($haystack, $needle, $offset = 0)
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
	 * Binary version of strripos.
	 * @param $haystack
	 * @param $needle
	 * @param int $offset
	 * @return int
	 */
	
	/**
	* <p>Статический метод. Статический метод ищет позицию последнего вхождения подстроки <b>needle</b> в строку <b>haystack</b>. Возвращает номер позиции последнего вхождения искомой подстроки относительно начала строки <b>haystack</b>, независимо от направления поиска. Позиция строки отсчитывается от 0, а не от 1. Возвращает <i>false</i>, если искомая строка не найдена.</p>
	*
	*
	* @param mixed $haystack  Строка, в которой производится поиск.
	*
	* @param $haystac $needle  Искомая подстрока. Если параметр не является строкой, то он будет
	* преобразован к целому и обработан как код символа.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/text/utfsafestring/getlastposition.php
	* @author Bitrix
	*/
	public static function getLastPositionIgnoreCase($haystack, $needle, $offset = 0)
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
	 * Binary version of strtolower.
	 * @param $str
	 * @return string
	 */
	
	/**
	* <p>Статический метод. Преобразует строку в нижний регистр. Выполняет безопасную с точки зрения многобайтных кодировок операцию. Аналог PHP функции <b>strtolower</b>.</p>
	*
	*
	* @param mixed $str  Входная строка для преобразования.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/text/binarystring/changecasetolower.php
	* @author Bitrix
	*/
	public static function changeCaseToLower($str)
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