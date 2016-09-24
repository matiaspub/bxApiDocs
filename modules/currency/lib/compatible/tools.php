<?php
namespace Bitrix\Currency\Compatible;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Currency;

Loc::loadMessages(__FILE__);

/**
 * Class Tools
 * Provides various useful methods for old api.
 *
 * @package Bitrix\Currency\Compatible
 */
class Tools
{
	protected static $datetimeTemplate = null;

	/**
	 * Return datetime template for old api emulation.
	 *
	 * @internal
	 *
	 * @return string
	 */
	public static function getDatetimeExpressionTemplate()
	{
		if (self::$datetimeTemplate === null)
		{
			$helper = Main\Application::getConnection()->getSqlHelper();
			$format = Main\Context::getCurrent()->getCulture()->getDateTimeFormat();
			$datetimeFieldName = '#FIELD#';
			$datetimeField = $datetimeFieldName;
			/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
			if (\CTimeZone::enabled())
			{
				/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
				$diff = \CTimeZone::getOffset();
				if ($diff <> 0)
					$datetimeField = $helper->addSecondsToDateTime($diff, $datetimeField);
				unset($diff);
			}
			self::$datetimeTemplate = str_replace(
				array('%', $datetimeFieldName),
				array('%%', '%1$s'),
				$helper->formatDate($format, $datetimeField)
			);
			unset($datetimeField, $datetimeFieldName, $format, $helper);
		}
		return self::$datetimeTemplate;
	}
}