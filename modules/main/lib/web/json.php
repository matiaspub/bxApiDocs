<?php
namespace Bitrix\Main\Web;

use \Bitrix\Main\Application;
use \Bitrix\Main\Text\Encoding;
use \Bitrix\Main\ArgumentException;

class Json
{
	const JSON_ERROR_UNKNOWN = -1;

	static public function encode($data, $options = null)
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

		self::checkException();

		return $res;
	}

	static public function decode($data)
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

	protected static function convertData($data)
	{
		return Encoding::convertEncodingArray($data, SITE_CHARSET, 'UTF-8');
	}

	protected static function unConvertData($data)
	{
		return Encoding::convertEncodingArray($data, 'UTF-8', SITE_CHARSET);
	}

	protected static function checkException()
	{
		$e = json_last_error();
		if($e != JSON_ERROR_NONE)
		{
			self::throwException($e);
		}
	}

	protected static function throwException($e)
	{
		throw new ArgumentException('JSON error: '.$e, 'data');
	}
}