<?php
namespace Bitrix\Main\Type;

use Bitrix\Main;
use Bitrix\Main\Context;

class DateTime extends Date
{
	/**
	 * @param string $time String representation of datetime
	 * @param string $format PHP datetime format. If not specified, the format is got from the current culture.
	 * @param \DateTimeZone $timezone
	 * @throws Main\ObjectException
	 */
	public function __construct($time = null, $format = null, \DateTimeZone $timezone = null)
	{
		if ($time === null || $time === "")
		{
			if ($timezone === null)
			{
				$this->value = new \DateTime();
			}
			else
			{
				$this->value = new \DateTime(null, $timezone);
			}
		}
		else
		{
			if ($format === null)
			{
				$format = static::getFormat();
			}

			if ($timezone === null)
			{
				$this->value = \DateTime::createFromFormat($format, $time);
			}
			else
			{
				$this->value = \DateTime::createFromFormat($format, $time, $timezone);
			}

			if (empty($this->value))
			{
				throw new Main\ObjectException("Incorrect date/time: ".$time);
			}
		}
	}

	/**
	 * Converts date to string, using Culture and global timezone settings
	 *
	 * @param Context\Culture $culture Culture contains datetime format
	 * @return string
	 */
	static public function toString(Context\Culture $culture = null)
	{
		if(\CTimeZone::Enabled())
		{
			$userTime = clone $this;
			$userTime->toUserTime();

			$format = static::getFormat($culture);
			return $userTime->format($format);
		}
		else
		{
			return parent::toString($culture);
		}
	}

	/**
	 * Returns timezone object
	 *
	 * @return \DateTimeZone
	 */
	public function getTimeZone()
	{
		return $this->value->getTimezone();
	}

	/**
	 * Sets timezone object
	 *
	 * @param \DateTimeZone $timezone
	 * @return DateTime
	 */
	public function setTimeZone(\DateTimeZone $timezone)
	{
		$this->value->setTimezone($timezone);
		return $this;
	}

	/**
	 * Sets default timezone
	 *
	 * @return DateTime
	 */
	public function setDefaultTimeZone()
	{
		$time = new \DateTime();
		$this->setTimezone($time->getTimezone());
		return $this;
	}

	/**
	 * @param int $hour
	 * @param int $minute
	 * @param int $second
	 * @return DateTime
	 */
	public function setTime($hour, $minute, $second = 0)
	{
		$this->value->setTime($hour, $minute, $second);
		return $this;
	}

	/**
	 * Changes time from server time to user time using global timezone settings
	 *
	 * @return DateTime
	 */
	public function toUserTime()
	{
		//first, move to server timezone
		$this->setDefaultTimeZone();

		//second, adjust time according global timezone offset
		static $diff = null;
		if($diff === null)
		{
			$diff = \CTimeZone::GetOffset();
		}
		if($diff <> 0)
		{
			$this->add(($diff < 0? "-":"")."PT".abs($diff)."S");
		}
		return $this;
	}

	/**
	 * Creates DateTime object from local user time using global timezone settings and default culture
	 *
	 * @param string $timeString
	 * @return DateTime
	 */
	public static function createFromUserTime($timeString)
	{
		/** @var DateTime $time */
		try
		{
			//try full datetime format
			$time = new static($timeString);
		}
		catch(Main\ObjectException $e)
		{
			//try short date format
			$time = new static($timeString, Date::getFormat());
			$time->setTime(0, 0, 0);
		}

		if(\CTimeZone::Enabled())
		{
			static $diff = null;
			if($diff === null)
			{
				$diff = \CTimeZone::GetOffset();
			}
			if($diff <> 0)
			{
				$time->add(($diff > 0? "-":"")."PT".abs($diff)."S");
			}
		}
		return $time;
	}

	protected static function getCultureFormat(Context\Culture $culture)
	{
		return $culture->getDateTimeFormat();
	}
}
