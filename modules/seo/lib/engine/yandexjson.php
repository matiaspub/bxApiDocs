<?php
namespace Bitrix\Seo\Engine;

use Bitrix\Main\Web\Json;

class YandexJson extends Json
{
	public static function encode($data)
	{
		// php 5.4.0+
		if(defined('JSON_UNESCAPED_UNICODE'))
		{
			return parent::encode($data, JSON_UNESCAPED_UNICODE);
		}
		else
		{
			return static::_encode(static::convertData($data));
		}
	}

	protected static function _encode($data)
	{
		$str = '';
		if(is_array($data))
		{
			$assoc = array_diff_key($data,array_keys(array_keys($data)));

			$str .= $assoc ? '{' : '[';

			$first = true;
			foreach($data as $key => $value)
			{
				$str .= $first ? '' : ',';
				$str .= $assoc ? static::_quote($key).':' : '';
				$str .= static::_encode($value);
				$first = false;
			}

			$str .= $assoc ? '}' : ']';
		}
		elseif(is_int($data))
		{
			$str = $data;
		}
		elseif($data === null)
		{
			$str = "null";
		}
		else
		{
			$str = static::_quote($data);
		}

		return $str;
	}

	protected static function _quote($s)
	{
		return '"'.str_replace(array('\\', '"'), array('\\\\', '\"'), $s).'"';
	}
}
