<?php
namespace Bitrix\Im;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

/**
 * Color schema for contacts and chats without avatars
 * @package bitrix
 * @subpackage im
 */
class Color
{
	private static $colors = Array(
		'RED' => '#df532d',
		'GREEN' => '#aac337',
		'MINT' => '#4ba984',
		'LIGHT_BLUE' => '#6fc8e5',
		'DARK_BLUE' => '#3e99ce',
		'PURPLE' => '#8474c8',
		'AQUA' => '#1eb4aa',
		'PINK' => '#e98fa6',
		'LIME' => '#85cb7b',
		'BROWN' => '#ab7761',
		'AZURE' => '#29619b',
		'KHAKI' => '#728f7a',
		'SAND' => '#ba9c7b',
		'ORANGE' => '#e8a441',
		'MARENGO' => '#556574',
		'GRAY' => '#77828e',
		'GRAPHITE' => '#3a403e',
	);

	private static $replaceColors = Array(
		'LIGHT_BLUE' => 'AZURE',
		'AQUA' => 'SAND',
		'PINK' => 'KHAKI',
	);

	private static $unsafeColors = Array('ORANGE' => '#e8a441');

	/**
	 * @return bool
	 */
	public static function isEnabled()
	{
		return (bool)Option::get('im', 'color_enable');
	}

	/**
	 * @return array
	 */
	public static function getColors()
	{
		return self::$colors;
	}

	/**
	 * @return array
	 */
	public static function getNames()
	{
		$result = Array();
		foreach (self::$colors as $code => $color)
		{
			$result[$code] = Loc::getMessage('IM_COLOR_'.$code);
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public static function getSafeColors()
	{
		return array_diff(self::$colors, self::$unsafeColors);
	}

	/**
	 * @return array
	 */
	public static function getSafeColorNames()
	{
		$result = Array();
		foreach (self::getSafeColors() as $code => $color)
		{
			$result[$code] = Loc::getMessage('IM_COLOR_'.$code);
		}

		return $result;
	}

	/**
	 * @param string $color
	 * @return string|null
	 */
	public static function getCode($color)
	{
		$colors = array_flip(self::$colors);
		return isset($colors[$color])? $colors[$color]: null;
	}

	/**
	 * @return array
	 */
	public static function getReplaceColors()
	{
		return self::$replaceColors;
	}

	/**
	 * @param string $code
	 * @return string|null
	 */
	public static function getColor($code)
	{
		if (!self::isEnabled())
		{
			$code = 'MARENGO';
		}

		return isset(self::$colors[$code])? self::$colors[$code]: null;
	}

	/**
	 * @param string $code
	 * @return string|null
	 */
	public static function getName($code)
	{
		return isset(self::$colors[$code])? Loc::getMessage('IM_COLOR_'.$code): null;
	}

	/**
	 * @param int $number
	 * @return string
	 */
	public static function getColorByNumber($number)
	{
		$code = self::getCodeByNumber($number);

		return self::getColor($code);
	}

	/**
	 * @param int $number
	 * @return string
	 */
	public static function getNameByNumber($number)
	{
		$code = self::getCodeByNumber($number);

		return self::getName($code);
	}

	/**
	 * @param int $number
	 * @return string
	 */
	public static function getCodeByNumber($number)
	{
		$colors = array_keys(self::getColors());

		$number = intval($number);
		if (!$number)
		{
			$code = $colors[mt_rand(0, 9)];
		}
		else
		{
			$number = intval(substr($number.'', -1));
			$number = $number == 0? 9: $number - 1;

			$code = $colors[$number];
		}

		return $code;
	}

	public static function isSafeColor($code)
	{
		$colors = self::getSafeColors();

		return isset($colors[$code]);
	}

	public static function getRandomColor()
	{
		$colors = array_values(self::getColors());

		return $colors[mt_rand(0, count($colors)-1)];
	}
}