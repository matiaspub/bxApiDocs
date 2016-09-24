<?php
namespace Bitrix\Main\Type;

use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;

class DateTime extends Date
{
	/**
	 * @param string $time String representation of datetime.
	 * @param string $format PHP datetime format. If not specified, the format is got from the current culture.
	 * @param \DateTimeZone $timezone Optional timezone object.
	 *
	 * @throws Main\ObjectException
	 */
	public function __construct($time = null, $format = null, \DateTimeZone $timezone = null)
	{
		if ($timezone === null)
		{
			$this->value = new \DateTime();
		}
		else
		{
			$this->value = new \DateTime(null, $timezone);
		}

		if ($time !== null && $time !== "")
		{
			if ($format === null)
			{
				$format = static::getFormat();
			}

			$parsedValue = date_parse_from_format($format, $time);
			//Ignore errors when format is longer than date
			//or date string is longer than format
			if ($parsedValue['error_count'] > 1)
			{
				if (
					current($parsedValue['errors']) !== 'Trailing data'
					&& current($parsedValue['errors']) !== 'Data missing'
				)
				{
					throw new Main\ObjectException("Incorrect date/time: ".$time);
				}
			}

			$this->value->setDate($parsedValue['year'], $parsedValue['month'], $parsedValue['day']);
			$this->value->setTime($parsedValue['hour'], $parsedValue['minute'], $parsedValue['second']);

			if (
				isset($parsedValue["relative"])
				&& isset($parsedValue["relative"]["second"])
				&& $parsedValue["relative"]["second"] != 0
			)
			{
				$this->value->add(new \DateInterval("PT".$parsedValue["relative"]["second"]."S"));
			}
		}
	}

	/**
	 * Converts date to string, using Culture and global timezone settings.
	 *
	 * @param Context\Culture $culture Culture contains datetime format.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод конвертирует дату в строку с использованием <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=35&amp;LESSON_ID=2071#local_settings" >региональных настроек</a> и настроек глобальных временных зон.</p>
	*
	*
	* @param mixed $Bitrix  Формат даты и времени для региональных настроек.
	*
	* @param Bitri $Main  
	*
	* @param Mai $Context  
	*
	* @param Culture $culture = null 
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/datetime/tostring.php
	* @author Bitrix
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
	 * Returns timezone object.
	 *
	 * @return \DateTimeZone
	 */
	
	/**
	* <p>Нестатический метод возвращает объект временной зоны.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \DateTimeZone 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/datetime/gettimezone.php
	* @author Bitrix
	*/
	public function getTimeZone()
	{
		return $this->value->getTimezone();
	}

	/**
	 * Sets timezone object.
	 *
	 * @param \DateTimeZone $timezone Timezone object.
	 *
	 * @return DateTime
	 */
	
	/**
	* <p>Нестатический метод устанавливает объект временной зоны.</p>
	*
	*
	* @param DateTimeZone $timezone  Объект временной зоны.
	*
	* @return \Bitrix\Main\Type\DateTime 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/datetime/settimezone.php
	* @author Bitrix
	*/
	public function setTimeZone(\DateTimeZone $timezone)
	{
		$this->value->setTimezone($timezone);
		return $this;
	}

	/**
	 * Sets default timezone.
	 *
	 * @return DateTime
	 */
	
	/**
	* <p>Нестатический метод устанавливает временную зону по умолчанию.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Type\DateTime 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/datetime/setdefaulttimezone.php
	* @author Bitrix
	*/
	public function setDefaultTimeZone()
	{
		$time = new \DateTime();
		$this->setTimezone($time->getTimezone());
		return $this;
	}

	/**
	 * @param int $hour Hour value.
	 * @param int $minute Minute value.
	 * @param int $second Second value.
	 *
	 * @return DateTime
	 */
	public function setTime($hour, $minute, $second = 0)
	{
		$this->value->setTime($hour, $minute, $second);
		return $this;
	}

	/**
	 * Changes time from server time to user time using global timezone settings.
	 *
	 * @return DateTime
	 */
	
	/**
	* <p>Нестатический метод изменяет время с серверного на время пользователя с использованием настроек глабальных временных зон.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Type\DateTime 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/datetime/tousertime.php
	* @author Bitrix
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
	 * Creates DateTime object from local user time using global timezone settings and default culture.
	 *
	 * @param string $timeString Full or short formatted time.
	 *
	 * @return DateTime
	 */
	
	/**
	* <p>Статический метод создаёт объект класса <code>\DateTime</code> из локального времени пользователя с использованием глобальных настроек временной зоны и региональных настроек по умолчанию.</p>
	*
	*
	* @param string $timeString  Полное или краткое форматированное время.
	*
	* @return \Bitrix\Main\Type\DateTime 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* //время из формата текущего сайта, в случае отсутствия время подставляет 00:00:00
	* \Bitrix\Main\Type\DateTime::createFromUserTime("16.08.2014 15:30:10"); 
	* 
	* //время из формата текущего сайта 
	* $date = new \Bitrix\Main\Type\DateTime("16.08.2014 15:30:10");
	* 
	* $arFields = Array(
	*    'DATE_START' =&gt; \Bitrix\Main\Type\DateTime::createFromUserTime("16.08.2014 15:30:10"); 
	* );
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/datetime/createfromusertime.php
	* @author Bitrix
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

	/**
	 * Returns long (including time) date culture format.
	 *
	 * @param Context\Culture $culture Culture.
	 *
	 * @return string
	 */
	protected static function getCultureFormat(Context\Culture $culture)
	{
		return $culture->getDateTimeFormat();
	}

	/**
	 * Creates DateTime object from PHP \DateTime object.
	 *
	 * @param \DateTime $datetime Source object.
	 *
	 * @return static
	 */
	
	/**
	* <p>Статический метод создаёт объект класса <code>\DateTime</code> из объекта <a href="http://php.net/manual/ru/class.datetime.php" >\DateTime</a> PHP.</p>
	*
	*
	* @param DateTime $datetime  Объект источника.
	*
	* @return static 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/datetime/createfromphp.php
	* @author Bitrix
	*/
	public static function createFromPhp(\DateTime $datetime)
	{
		/** @var DateTime $d */
		$d = new static();
		$d->value = $datetime;
		return $d;
	}

	/**
	 * Creates DateTime object from Unix timestamp.
	 *
	 * @param int $timestamp Source timestamp.
	 *
	 * @return static
	 */
	
	/**
	* <p>Статический метод создаёт объект класса <code>\DateTime</code> из временной метки Unix.</p>
	*
	*
	* @param integer $timestamp  Временная метка источника.
	*
	* @return static 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* Создание объекта по timestamp\Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime("+5 days"));//<$date = Bitrix\Main\Type\DateTime Object 
	*                         ( [value:protected] =&gt; DateTime Object
	*                                 (
	*                                     [date] =&gt; 2014-09-02 12:00:00
	*                                     [timezone_type] =&gt; 3
	*                                     [timezone] =&gt; Europe/Minsk
	*                                 )
	* 
	*                         );>//
	* echo $date-&gt;toString(new \Bitrix\Main\Context\Culture(array("FORMAT_DATETIME" =&gt; "HH:MI"))); // на выходе 12:00
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/datetime/createfromtimestamp.php
	* @author Bitrix
	*/
	public static function createFromTimestamp($timestamp)
	{
		/** @var DateTime $d */
		$d = new static();
		$d->value->setTimestamp($timestamp);
		return $d;
	}
}
