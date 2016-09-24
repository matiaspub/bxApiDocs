<?php
namespace Bitrix\Main\Web;

use \Bitrix\Main\Application;
use \Bitrix\Main\Text\Encoding;
use \Bitrix\Main\ArgumentException;

class Json
{
	const JSON_ERROR_UNKNOWN = -1;

	/**
	 * Returns a string containing the JSON representation of $data.
	 *
	 * @param mixed $data The value being encoded.
	 * @param null $options Bitmasked options. Default is JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT.
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @see json_encode
	 */
	
	/**
	* <p>Статический метод возвращает строку, содержащую JSON представление переменной <code>$data</code>.</p>
	*
	*
	* @param mixed $data  Данные для кодирования.
	*
	* @param null $options = null Параметры битовой маски.  По умолчанию:
	* JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT.
	*
	* @return mixed 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/json/encode.php">\Bitrix\Main\Web\Json::encode</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/json/encode.php
	* @author Bitrix
	*/
	public static function encode($data, $options = null)
	{
		if (!Application::getInstance()->isUtfMode())
		{
			$data = self::convertData($data);
		}

		if (is_null($options))
		{
			$options = JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT;
		}

		$res = json_encode($data, $options);

		self::checkException($options);

		return $res;
	}

	/**
	 * Takes a JSON encoded string and converts it into a PHP variable.
	 *
	 * @param string $data The json string being decoded.
	 *
	 * @return mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @see json_decode
	 */
	
	/**
	* <p>Статический метод выполняет декодирование строки JSON и конвертирует данные в переменные PHP.</p>
	*
	*
	* @param string $data  Json строка для декодирования.
	*
	* @return mixed 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/json/encode.php">\Bitrix\Main\Web\Json::encode</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/web/json/decode.php
	* @author Bitrix
	*/
	public static function decode($data)
	{
		$res = json_decode($data, true);

		self::checkException();

		// PHP<5.3.3 returns no error for JSON_ERROR_UTF8 and some other ones
		if($res === null && ToLower($data) != 'null')
		{
			self::throwException(self::JSON_ERROR_UNKNOWN);
		}

		if (!Application::getInstance()->isUtfMode())
		{
			$res = self::unConvertData($res);
		}

		return $res;
	}

	/**
	 * Converts $data to UTF-8 charset.
	 *
	 * @param mixed $data Input data.
	 * @return mixed
	 */
	protected static function convertData($data)
	{
		return Encoding::convertEncoding($data, SITE_CHARSET, 'UTF-8');
	}

	/**
	 * Converts $data from UTF-8 charset.
	 *
	 * @param mixed $data Input data.
	 * @return mixed
	 */
	protected static function unConvertData($data)
	{
		return Encoding::convertEncoding($data, 'UTF-8', SITE_CHARSET);
	}

	/**
	 * Checks global error flag and raises exception if needed.
	 *
	 * @param integer $options Bitmasked options. When JSON_PARTIAL_OUTPUT_ON_ERROR passed no exception is raised.
	 *
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected static function checkException($options = 0)
	{
		$e = json_last_error();

		if ($e == JSON_ERROR_NONE)
		{
			return;
		}

		if ($e == JSON_ERROR_UTF8 && ($options & JSON_PARTIAL_OUTPUT_ON_ERROR))
		{
			return;
		}

		if (function_exists('json_last_error_msg'))
		{
			// Must be available on PHP >= 5.5.0
			$message = sprintf('%s [%d]', json_last_error_msg(), $e);
		}
		else
		{
			$message = $e;
		}

		self::throwException($message);
	}

	/**
	 * Throws exception with message given.
	 *
	 * @param string $e Exception message.
	 *
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected static function throwException($e)
	{
		throw new ArgumentException('JSON error: '.$e, 'data');
	}
}