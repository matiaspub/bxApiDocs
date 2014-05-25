<?php
namespace Bitrix\Main\Type;

use Bitrix\Main;
use Bitrix\Main\Context;

class Date
{
	/** @var \DateTime */
	protected $value;

	/**
	 * @param string $date String representation of date
	 * @param string $format PHP date format. If not specified, the format is got from the current culture.
	 * @throws Main\ObjectException
	 */
	public function __construct($date = null, $format = null)
	{
		if ($date === null || $date === "")
		{
			$this->value = new \DateTime();
		}
		else
		{
			if ($format === null)
			{
				$format = static::getFormat();
			}

			$this->value = \DateTime::createFromFormat($format, $date);

			if (empty($this->value))
			{
				throw new Main\ObjectException("Incorrect date: ".$date);
			}
		}
		$this->value->setTime(0, 0, 0);
	}

	/**
	 * Formats date value to string
	 *
	 * @param string $format PHP date format
	 * @return string
	 */
	public function format($format)
	{
		return $this->value->format($format);
	}

	public function __clone()
	{
		$this->value = clone $this->value;
	}

	/**
	 * Performs dates arithmetic
	 *
	 * @param string $interval
	 *    Each duration period is represented by an integer value followed by a period
	 *    designator. If the duration contains time elements, that portion of the
	 *    specification is preceded by the letter T.
	 *    Period Designators: Y - years, M - months, D - days, W - weeks, H - hours,
	 *    M - minutes, S - seconds.
	 *    Examples: two days - 2D, two seconds - T2S, six years and five minutes - 6YT5M.
	 *    The unit types must be entered from the largest scale unit on the left to the
	 *    smallest scale unit on the right.
	 *    Use first "-" char for negative periods.
	 *    OR
	 *    Relative period.
	 *    Examples: "+5 weeks", "12 day", "-7 weekdays", '3 months - 5 days'
	 *
	 * @return Date
	 */
	public function add($interval)
	{
		$i = null;
		try
		{
			$intervalTmp = strtoupper($interval);
			$isNegative = false;
			$firstChar = substr($intervalTmp, 0, 1);
			if ($firstChar === "-")
			{
				$isNegative = true;
				$intervalTmp = substr($intervalTmp, 1);
				$firstChar = substr($intervalTmp, 0, 1);
			}

			if ($firstChar !== "P")
			{
				$intervalTmp = "P".$intervalTmp;
			}
			$i = new \DateInterval($intervalTmp);
			if ($isNegative)
			{
				$i->invert = 1;
			}
		}
		catch (\Exception $e)
		{
		}

		if ($i == null)
		{
			$i = \DateInterval::createFromDateString($interval);
		}

		$this->value->add($i);

		return $this;
	}

	/**
	 * Returns Unix timestamp from date
	 *
	 * @return int
	 */
	public function getTimestamp()
	{
		return $this->value->getTimestamp();
	}

	/**
	 * Converts date to string
	 *
	 * @param Context\Culture $culture Culture contains date format
	 * @return string
	 */
	public function toString(Context\Culture $culture = null)
	{
		$format = static::getFormat($culture);
		return $this->format($format);
	}

	public function __toString()
	{
		return $this->toString();
	}

	protected static function getFormat(Context\Culture $culture = null)
	{
		static $defaultCulture = null;

		if($culture === null)
		{
			if($defaultCulture === null)
			{
				$context = Context::getCurrent();
				$defaultCulture = $context->getCulture();
			}
			$culture = $defaultCulture;
		}

		$format = static::getCultureFormat($culture);

		return static::convertFormatToPhp($format);
	}

	protected static function getCultureFormat(Context\Culture $culture)
	{
		return $culture->getDateFormat();
	}

	/**
	 * Converts date format from culture to php format
	 *
	 * @param $format
	 * @return mixed
	 */
	public static function convertFormatToPhp($format)
	{
		static $from = array(
			"YYYY", // 1999
			"MMMM", // January - December
			"MM", // 01 - 12
			"DD", // 01 - 31
			"TT", // AM - PM
			"T", // am - pm
			"MI", // 00 - 59
			"SS", // 00 - 59
		);
		static $to = array(
			"Y", // 1999
			"F", // January - December
			"m", // 01 - 12
			"d", // 01 - 31
			"A", // AM - PM
			"a", // am - pm
			"i", // 00 - 59
			"s", // 00 - 59
		);

		$format = str_replace($from, $to, $format);

		$tempFormat = $format;
		$format = str_replace("HH", "H", $format); // 00 - 24
		if ($tempFormat === $format)
		{
			$format = str_replace("H", "h", $format); // 01 - 12
		}

		$tempFormat = $format;
		$format = str_replace("GG", "G", $format); // 0 - 24
		if ($tempFormat === $format)
		{
			$format = str_replace("G", "g", $format); // 1 - 12
		}

		return $format;
	}

	/**
	 * Checks the string for correct date (by trying to create Date object)
	 *
	 * @param string $time String representation of date
	 * @param string $format PHP date format. If not specified, the format is got from the current culture.
	 * @return bool
	 */
	public static function isCorrect($time, $format = null)
	{
		if (empty($time))
		{
			return false;
		}

		$result = true;

		try
		{
			new static($time, $format);
		}
		catch (Main\ObjectException $ex)
		{
			$result = false;
		}

		return $result;
	}

	/**
	 * Creates Date object from PHP \DateTime object
	 *
	 * @param \DateTime $datetime
	 * @return static
	 */
	public static function createFromPhp(\DateTime $datetime)
 	{
		/** @var Date $d */
		$d = new static();
		$d->value = $datetime;
		return $d;
	}

	/**
	 * Creates Date object from Unix timestamp
	 *
	 * @param int $timestamp
	 * @return static
	 */
	public static function createFromTimestamp($timestamp)
 	{
		/** @var Date $d */
		$d = new static();
		$d->value->setTimestamp($timestamp);
		return $d;
	}
}
