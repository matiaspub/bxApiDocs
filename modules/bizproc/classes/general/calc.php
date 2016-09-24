<?
class CBPCalc
{
	private $activity;
	private $arErrorsList = array();

	private static $weekHolidays;
	private static $yearHolidays;
	private static $startWorkDay;
	private static $endWorkDay;

	public function __construct($activity)
	{
		/** @var CBPActivity activity */
		$this->activity = $activity;
	}

	private function GetVariableValue($variable)
	{
		$variable = trim($variable);
		if (!preg_match(CBPActivity::ValuePattern, $variable))
			return null;

		return $this->activity->ParseValue($variable);
	}

	private function SetError($errnum, $errstr = '')
	{
		$this->arErrorsList[] = array($errnum, str_replace('#STR#', $errstr, $this->arAvailableErrors[$errnum]));
	}

	public function GetErrors()
	{
		return $this->arErrorsList;
	}

	/*
	Return array of polish notation
	array(
		key => array(value, self::Operation | self::Variable | self::Constant)
	)
	*/
	private function GetPolishNotation($text)
	{
		$text = trim($text);
		if (substr($text, 0, 1) === '=')
			$text = substr($text, 1);
		if (strpos($text, '{{=') === 0 && substr($text, -2) == '}}')
		{
			$text = substr($text, 3);
			$text = substr($text, 0, -2);
		}

		if (strlen($text) <= 0)
		{
			$this->SetError(1);
			return false;
		}

		$arPolishNotation = array();

		$arStack = array();
		$prev = '';
		$preg = '/
			\s*\(\s*                          |
			\s*\)\s*                          |
			\s*,\s*                           | # Combine ranges of variables
			\s*;\s*                           | # Combine ranges of variables
			\s*=\s*                           |
			\s*<=\s*                          |
			\s*>=\s*                          |
			\s*<>\s*                          |
			\s*<\s*                           |
			\s*>\s*                           |
			\s*&\s*                           | # String concatenation
			\s*\+\s*                          | # Addition or unary plus
			\s*-\s*                           |
			\s*\*\s*                          |
			\s*\/\s*                          |
			\s*\^\s*                          | # Exponentiation
			\s*%\s*                           | # Percent
			\s*[\d\.]+\s*                     | # Numbers
			\s*\'[^\']*\'\s*                  | # String constants in apostrophes
			\s*"[^"]*"\s*                     | # String constants in quotes
			(\s*\w+\s*\(\s*)                  | # Function names
			\s*'.CBPActivity::ValueInternalPattern.'\s*  | # Variables
			(?<error>.+)                                # Any erroneous substring
			/xi';
		while (preg_match($preg, $text, $match))
		{
			if (isset($match['error']))
			{
				$this->SetError(2, $match['error']);
				return false;
			}

			$str = trim($match[0]);
			if ($str === ",")
				$str = ";";

			if (isset($match[1]) && $match[1])
			{
				$str = strtolower($str);
				list($name, $left) = explode('(', $str);
				$name = trim($name);
				if (isset($this->arAvailableFunctions[$name]))
				{
					if (!$arStack)
					{
						array_unshift($arStack, array($name, $this->arPriority['f']));
					}
					else
					{
						while ($this->arPriority['f'] <= $arStack[0][1])
						{
							$op = array_shift($arStack);
							$arPolishNotation[] = array($op[0], self::Operation);
							if (!$arStack)
								break;
						}
						array_unshift($arStack, array($name, $this->arPriority['f']));
					}
				}
				else
				{
					$this->SetError(3, $name);
					return false;
				}
				$str = '(';
			}

			if ($str == '-' || $str == '+')
			{
				if ($prev == '' || in_array($prev, array('(', ';', '=', '<=', '>=', '<>', '<', '>', '&', '+', '-', '*', '/', '^')))
					$str .= 'm';
			}
			$prev = $str;

			switch ($str)
			{
				case '(':
					array_unshift($arStack, array('(', $this->arPriority['(']));
					break;
				case ')':
					while ($op = array_shift($arStack))
					{
						if ($op[0] == '(')
							break;
						$arPolishNotation[] = array($op[0], self::Operation);
					}
					if ($op == null)
					{
						$this->SetError(4);
						return false;
					}
					break;
				case ';' :      case '=' :      case '<=':      case '>=':
				case '<>':      case '<' :      case '>' :      case '&' :
				case '+' :      case '-' :      case '+m':      case '-m':
				case '*' :      case '/' :      case '^' :      case '%' :
					if (!$arStack)
					{
						array_unshift($arStack, array($str, $this->arPriority[$str]));
						break;
					}
					while ($this->arPriority[$str] <= $arStack[0][1])
					{
						$op = array_shift($arStack);
						$arPolishNotation[] = array($op[0], self::Operation);
						if (!$arStack)
							break;
					}
					array_unshift($arStack, array($str, $this->arPriority[$str]));
					break;
				default:
					if (substr($str, 0, 1) == '0' || (int) $str)
					{
						$arPolishNotation[] = array((float)$str, self::Constant);
						break;
					}
					if (substr($str, 0, 1) == '"' || substr($str, 0, 1) == "'")
					{
						$arPolishNotation[] = array(substr($str, 1, -1), self::Constant);
						break;
					}
					$arPolishNotation[] = array($str, self::Variable);
			}
			$text = substr($text, strlen($match[0]));
		}
		while ($op = array_shift($arStack))
		{
			if ($op[0] == '(')
			{
				$this->SetError(5);
				return false;
			}
			$arPolishNotation[] = array($op[0], self::Operation);
		}
		return $arPolishNotation;
	}

	public function Calculate($text)
	{
		if (!$arPolishNotation = $this->GetPolishNotation($text))
			return null;

		$stack = array();
		foreach ($arPolishNotation as $item)
		{
			switch ($item[1])
			{
				case self::Constant:
					array_unshift($stack, $item[0]);
					break;
				case self::Variable:
					array_unshift($stack, $this->GetVariableValue($item[0]));
					break;
				case self::Operation:
					switch ($item[0])
					{
						case ';':
							$arg2 = array_shift($stack);
							$arg1 = array_shift($stack);
							if (!is_array($arg1) || !isset($arg1[0]))
								$arg1 = array($arg1);
							$arg1[] = $arg2;
							array_unshift($stack, $arg1);
							break;
						case '=':
							$arg2 = array_shift($stack);
							$arg1 = array_shift($stack);
							array_unshift($stack, $arg1 == $arg2);
							break;
						case '<=':
							$arg2 = array_shift($stack);
							$arg1 = array_shift($stack);
							array_unshift($stack, $arg1 <= $arg2);
							break;
						case '>=':
							$arg2 = array_shift($stack);
							$arg1 = array_shift($stack);
							array_unshift($stack, $arg1 >= $arg2);
							break;
						case '<>':
							$arg2 = array_shift($stack);
							$arg1 = array_shift($stack);
							array_unshift($stack, $arg1 != $arg2);
							break;
						case '<':
							$arg2 = array_shift($stack);
							$arg1 = array_shift($stack);
							array_unshift($stack, $arg1 < $arg2);
							break;
						case '>':
							$arg2 = array_shift($stack);
							$arg1 = array_shift($stack);
							array_unshift($stack, $arg1 > $arg2);
							break;
						case '&':
							$arg2 = (string) array_shift($stack);
							$arg1 = (string) array_shift($stack);
							array_unshift($stack, $arg1 . $arg2);
							break;
						case '+':
							$arg2 = (float) array_shift($stack);
							$arg1 = (float) array_shift($stack);
							array_unshift($stack, $arg1 + $arg2);
							break;
						case '-':
							$arg2 = (float) array_shift($stack);
							$arg1 = (float) array_shift($stack);
							array_unshift($stack, $arg1 - $arg2);
							break;
						case '+m':
							$arg = (float) array_shift($stack);
							array_unshift($stack, $arg);
							break;
						case '-m':
							$arg = (float) array_shift($stack);
							array_unshift($stack, (-$arg));
							break;
						case '*':
							$arg2 = (float) array_shift($stack);
							$arg1 = (float) array_shift($stack);
							array_unshift($stack, $arg1 * $arg2);
							break;
						case '/':
							$arg2 = (float) array_shift($stack);
							$arg1 = (float) array_shift($stack);
							if (0 == $arg2)
							{
								$this->SetError(6);
								return null;
							}
							array_unshift($stack, $arg1 / $arg2);
							break;
						case '^':
							$arg2 = (float) array_shift($stack);
							$arg1 = (float) array_shift($stack);
							array_unshift($stack, pow($arg1, $arg2));
							break;
						case '%':
							$arg = (float) array_shift($stack);
							array_unshift($stack, $arg / 100);
							break;
						default:
							$func = $this->arAvailableFunctions[$item[0]]['func'];
							if ($this->arAvailableFunctions[$item[0]]['args'])
							{
								$arg = array_shift($stack);
								$val = $this->$func($arg);
							}
							else
							{
								$val = $this->$func();
							}
							$error = is_float($val) && (is_nan($val) || is_infinite($val));
							if ($error)
							{
								$this->SetError(8, $item[0]);
								return null;
							}
							array_unshift($stack, $val);
					}
			}
		}
		if (count($stack) > 1)
		{
			$this->SetError(7);
			return null;
		}
		return array_shift($stack);
	}

	private function ArrgsToArray($args)
	{
		if (!is_array($args))
			return array($args);

		$result = array();
		foreach ($args as $arg)
		{
			if (!is_array($arg))
			{
				$result[] = $arg;
			}
			else
			{
				foreach ($this->ArrgsToArray($arg) as $val)
					$result[] = $val;
			}
		}

		return $result;
	}

	private function FunctionAbs($num)
	{
		return abs((float) $num);
	}

	private function FunctionAnd($args)
	{
		if (!is_array($args))
			return (boolean) $args;

		$args = $this->ArrgsToArray($args);

		foreach ($args as $arg)
		{
			if (!$arg)
				return false;
		}
		return true;
	}

	private function FunctionDateAdd($args)
	{
		if (!is_array($args))
			$args = array($args);

		$ar = $this->ArrgsToArray($args);
		$date = array_shift($ar);
		$interval = array_shift($ar);

		if (($date = $this->makeTimestamp($date)) === false)
			return null;

		if (($interval == null) || (strlen($interval) <= 0))
			return $date;

		// 1Y2M3D4H5I6S, -4 days 5 hours, 1month, 5h

		$interval = trim($interval);
		$bMinus = false;
		if (substr($interval, 0, 1) === "-")
		{
			$interval = substr($interval, 1);
			$bMinus = true;
		}

		static $arMap = array("y" => "YYYY", "year" => "YYYY", "years" => "YYYY",
							"m" => "MM", "month" => "MM", "months" => "MM",
							"d" => "DD", "day" => "DD", "days" => "DD",
							"h" => "HH", "hour" => "HH", "hours" => "HH",
							"i" => "MI", "min" => "MI", "minute" => "MI", "minutes" => "MI",
							"s" => "SS", "sec" => "SS", "second" => "SS", "seconds" => "SS",
		);

		$arInterval = array();
		while (preg_match('/\s*([\d]+)\s*([a-z]+)\s*/i', $interval, $match))
		{
			$match2 = strtolower($match[2]);
			if (array_key_exists($match2, $arMap))
				$arInterval[$arMap[$match2]] = ($bMinus ? -intval($match[1]) : intval($match[1]));

			$p = strpos($interval, $match[0]);
			$interval = substr($interval, $p + strlen($match[0]));
		}

		$newDate = AddToTimeStamp($arInterval, $date);

		return ConvertTimeStamp($newDate, "FULL");
	}

	private function FunctionWorkDateAdd($args)
	{
		if (!is_array($args))
			$args = array($args);

		$ar = $this->ArrgsToArray($args);
		$date = array_shift($ar);
		$paramInterval = array_shift($ar);

		if (($date = $this->makeTimestamp($date)) === false)
			return null;

		if (($paramInterval == null) || (strlen($paramInterval) <= 0) || !CModule::IncludeModule('calendar'))
			return $date;

		$paramInterval = trim($paramInterval);
		$multiplier = 1;
		if (substr($paramInterval, 0, 1) === "-")
		{
			$paramInterval = substr($paramInterval, 1);
			$multiplier = -1;
		}

		$workDayInterval = $this->getWorkDayInterval();
		$intervalMap = array("d" => $workDayInterval, "day" => $workDayInterval, "days" => $workDayInterval,
							"h" => 3600, "hour" => 3600, "hours" => 3600,
							"i" => 60, "min" => 60, "minute" => 60, "minutes" => 60,
		);

		$interval = 0;
		while (preg_match('/\s*([\d]+)\s*([a-z]+)\s*/i', $paramInterval, $match))
		{
			$match2 = strtolower($match[2]);
			if (array_key_exists($match2, $intervalMap))
				$interval += intval($match[1]) * $intervalMap[$match2];

			$p = strpos($paramInterval, $match[0]);
			$paramInterval = substr($paramInterval, $p + strlen($match[0]));
		}

		$date = $this->getNearestWorkTime($date, $multiplier);
		if ($interval)
		{
			$days = (int) floor($interval / $workDayInterval);
			$hours = $interval % $workDayInterval;

			$remainTimestamp = $this->getWorkDayRemainTimestamp($date, $multiplier);

			if ($days)
				$date = $this->addWorkDay($date, $days * $multiplier);

			if ($hours > $remainTimestamp)
			{
				$date += $multiplier < 0 ? -$remainTimestamp -60 : $remainTimestamp + 60;
				$date = $this->getNearestWorkTime($date, $multiplier) + (($hours - $remainTimestamp) * $multiplier);
			}
			else
				$date += $multiplier * $hours;
		}

		return ConvertTimeStamp($date, "FULL");
	}

	private function makeTimestamp($date)
	{
		if (!$date)
			return false;
		if (intval($date)."!" === $date."!")
			return $date;

		if (($result = MakeTimeStamp($date, FORMAT_DATETIME)) === false)
		{
			if (($result = MakeTimeStamp($date, FORMAT_DATE)) === false)
			{
				if (($result = MakeTimeStamp($date, "YYYY-MM-DD HH:MI:SS")) === false)
				{
					$result = MakeTimeStamp($date, "YYYY-MM-DD");
				}
			}
		}
		return $result;
	}

	private function getWorkDayTimestamp($date)
	{
		return date('H', $date) * 3600 + date('i', $date) * 60;
	}

	private function getWorkDayRemainTimestamp($date, $multiplier = 1)
	{
		$dayTs = $this->getWorkDayTimestamp($date);
		list ($startSeconds, $endSeconds) = $this->getCalendarWorkTime();
		return $multiplier < 0 ? $dayTs - $startSeconds :$endSeconds - $dayTs;
	}

	private function getWorkDayInterval()
	{
		list ($startSeconds, $endSeconds) = $this->getCalendarWorkTime();
		return $endSeconds - $startSeconds;
	}

	private function isHoliday($date)
	{
		list($weekHolidays, $yearHolidays) = $this->getCalendarHolidays();

		$dayOfWeek = date('w', $date);
		if (in_array($dayOfWeek, $weekHolidays))
				return true;
		$dayOfYear = date('j.n', $date);
		if (in_array($dayOfYear, $yearHolidays))
			return true;

		return false;
	}

	private function isWorkTime($date)
	{
		$dayTs = $this->getWorkDayTimestamp($date);
		list ($startSeconds, $endSeconds) = $this->getCalendarWorkTime();
		return ($dayTs >= $startSeconds && $dayTs <= $endSeconds);
	}

	private function getNearestWorkTime($date, $multiplier = 1)
	{
		$reverse = $multiplier < 0;
		list ($startSeconds, $endSeconds) = $this->getCalendarWorkTime();
		$dayTimeStamp = $this->getWorkDayTimestamp($date);

		if ($this->isHoliday($date))
		{
			$date -= $dayTimeStamp;
			$date += $reverse? -86400 + $endSeconds : $startSeconds;
			$dayTimeStamp = $reverse? $endSeconds : $startSeconds;
		}

		if (!$this->isWorkTime($date))
		{
			$date -= $dayTimeStamp;

			if ($dayTimeStamp < $startSeconds)
			{
				$date += $reverse? -86400 + $endSeconds : $startSeconds;
			}
			else
			{
				$date += $reverse? $endSeconds : 86400 + $startSeconds;
			}
		}

		if ($this->isHoliday($date))
			$date = $this->addWorkDay($date, $reverse? -1 : 1);

		return $date;
	}

	private function addWorkDay($date, $days)
	{
		$delta = 86400;
		if ($days < 0)
			$delta *= -1;

		$days = abs($days);

		while ($days > 0)
		{
			$date += $delta;

			if ($this->isHoliday($date))
				continue;
			--$days;
		}

		return $date;
	}

	private function getCalendarHolidays()
	{
		if (static::$yearHolidays === null)
		{
			$calendarSettings = CCalendar::GetSettings();
			$weekHolidays = array(0, 6);
			$yearHolidays = array();

			if (isset($calendarSettings['week_holidays']))
			{
				$weekDays = array('SU' => 0, 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6);
				$weekHolidays = array();
				foreach ($calendarSettings['week_holidays'] as $day)
					$weekHolidays[] = $weekDays[$day];
			}

			if (isset($calendarSettings['year_holidays']))
			{
				foreach (explode(',', $calendarSettings['year_holidays']) as $yearHoliday)
				{
					$ardate = explode('.', trim($yearHoliday));
					if (count($ardate) == 2 && $ardate[0] && $ardate[1])
						$yearHolidays[] = (int)$ardate[0].'.'.(int)$ardate[1];
				}
			}
			static::$weekHolidays = $weekHolidays;
			static::$yearHolidays = $yearHolidays;
		}

		return array(static::$weekHolidays, static::$yearHolidays);
	}

	private function getCalendarWorkTime()
	{
		if (static::$startWorkDay === null)
		{
			$startSeconds = 0;
			$endSeconds = 24 * 3600 - 1;

			$calendarSettings = CCalendar::GetSettings();
			if (!empty($calendarSettings['work_time_start']))
			{
				$time = explode('.', $calendarSettings['work_time_start']);
				$startSeconds = $time[0] * 3600;
				if (!empty($time[1]))
					$startSeconds += $time[1] * 60;
			}

			if (!empty($calendarSettings['work_time_end']))
			{
				$time = explode('.', $calendarSettings['work_time_end']);
				$endSeconds = $time[0] * 3600;
				if (!empty($time[1]))
					$endSeconds += $time[1] * 60;
			}
			static::$startWorkDay = $startSeconds;
			static::$endWorkDay = $endSeconds;
		}
		return array(static::$startWorkDay, static::$endWorkDay);
	}

	private function FunctionAddWorkDays($args)
	{
		if (!is_array($args))
			$args = array($args);

		$ar = $this->ArrgsToArray($args);
		$date = array_shift($ar);
		$days = (int) array_shift($ar);

		if (($date = $this->makeTimestamp($date)) === false)
			return null;

		if ($days === 0 || !CModule::IncludeModule('calendar'))
			return $date;

		$date = $this->addWorkDay($date, $days);

		return ConvertTimeStamp($date, "FULL");
	}

	private function FunctionIsWorkDay($args)
	{
		if (!CModule::IncludeModule('calendar'))
			return null;

		if (!is_array($args))
			$args = array($args);

		$ar = $this->ArrgsToArray($args);
		$date = array_shift($ar);

		if (($date = $this->makeTimestamp($date)) === false)
			return null;

		return !$this->isHoliday($date);
	}

	private function FunctionIsWorkTime($args)
	{
		if (!CModule::IncludeModule('calendar'))
			return null;

		if (!is_array($args))
			$args = array($args);

		$ar = $this->ArrgsToArray($args);
		$date = array_shift($ar);

		if (($date = $this->makeTimestamp($date)) === false)
			return null;

		return !$this->isHoliday($date) && $this->isWorkTime($date);
	}

	private function FunctionDateDiff($args)
	{
		if (!is_array($args))
			$args = array($args);

		$ar = $this->ArrgsToArray($args);
		$date1 = array_shift($ar);
		$date2 = array_shift($ar);
		$format = array_shift($ar);

		if ($date1 == null || $date2 == null)
			return null;

		$df = $GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME);
		$df2 = $GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE);
		$date1Formatted = \DateTime::createFromFormat($df, $date1);
		if ($date1Formatted === false)
			$date1Formatted = \DateTime::createFromFormat($df2, $date1);
		$date2Formatted = \DateTime::createFromFormat($df, $date2);
		if ($date2Formatted === false)
			$date2Formatted = \DateTime::createFromFormat($df2, $date2);
		if ($date1Formatted === false || $date2Formatted === false)
			return null;

		$interval = $date1Formatted->diff($date2Formatted);

		return $interval === false? null : $interval->format($format);
	}

	private function FunctionFalse()
	{
		return false;
	}

	private function FunctionIf($args)
	{
		if (!is_array($args))
			return null;

		$expression = (boolean) array_shift($args);
		$ifTrue = array_shift($args);
		$ifFalse = array_shift($args);
		return $expression ? $ifTrue : $ifFalse;
	}

	private function FunctionIntval($num)
	{
		return intval($num);
	}

	private function FunctionMin($args)
	{
		if (!is_array($args))
			return (float) $args;

		foreach ($args as &$arg)
			$arg = (float) $arg;

		$args = $this->ArrgsToArray($args);
		return min($args);
	}

	private function FunctionMax($args)
	{
		if (!is_array($args))
			return (float) $args;

		foreach ($args as &$arg)
			$arg = (float) $arg;

		$args = $this->ArrgsToArray($args);
		return max($args);
	}

	private function FunctionNot($arg)
	{
		return !((boolean) $arg);
	}

	private function FunctionOr($args)
	{
		if (!is_array($args))
			return (boolean) $args;

		$args = $this->ArrgsToArray($args);
		foreach ($args as $arg)
		{
			if ($arg)
				return true;
		}

		return false;
	}

	private function FunctionSubstr($args)
	{
		if (!is_array($args))
			$args = array($args);

		$ar = $this->ArrgsToArray($args);
		$str = array_shift($ar);
		$pos = array_shift($ar);
		$len = array_shift($ar);

		if (($str == null) || ($str === ""))
			return null;

		if ($pos == null)
			$pos = 0;

		if ($len != null)
			return substr($str, $pos, $len);

		return substr($str, $pos);
	}

	private function FunctionConvert($args)
	{
		if (!is_array($args))
			$args = array($args);

		$ar = $this->ArrgsToArray($args);
		$val = array_shift($ar);
		$type = array_shift($ar);
		$attr = array_shift($ar);

		$type = strtolower($type);
		if ($type === 'printableuserb24')
		{
			$result = array();

			$users = CBPHelper::StripUserPrefix($val);
			if (!is_array($users))
				$users = array($users);

			foreach ($users as $userId)
			{
				$db = CUser::GetByID($userId);
				if ($ar = $db->GetNext())
				{
					$ix = randString(5);
					$attr = (!empty($attr) ? 'href="'.$attr.'"' : 'href="#" onClick="return false;"');
					$result[] = '<a class="feed-post-user-name" id="bp_'.$userId.'_'.$ix.'" '.$attr.' bx-post-author-id="'.$userId.'">'.CUser::FormatName(CSite::GetNameFormat(false), $ar, false).'</a><script type="text/javascript">BX.tooltip(\''.$userId.'\', "bp_'.$userId.'_'.$ix.'", "");</script>';
				}
			}

			$result = implode(", ", $result);
		}
		elseif ($type == 'printableuser')
		{
			$result = array();

			$users = CBPHelper::StripUserPrefix($val);
			if (!is_array($users))
				$users = array($users);

			foreach ($users as $userId)
			{
				$db = CUser::GetByID($userId);
				if ($ar = $db->GetNext())
					$result[] = CUser::FormatName(CSite::GetNameFormat(false), $ar, false);
			}

			$result = implode(", ", $result);

		}
		else
		{
			$result = $val;
		}

		return $result;
	}

	private function FunctionTrue()
	{
		return true;
	}

	private function FunctionMerge($args)
	{
		if (!is_array($args))
			$args = array();

		foreach ($args as &$a)
		{
			$a = (array)$a;
		}
		return call_user_func_array('array_merge', $args);
	}

	// Operation priority
	private $arPriority = array(
		'('  => 0,   ')'  => 1,     ';'   => 2,   '=' => 3,     '<' => 3,   '>' => 3,
		'<=' => 3,   '>=' => 3,     '<>'  => 3,   '&' => 4,     '+' => 5,   '-' => 5,
		'*'  => 6,   '/'  => 6,     '^'   => 7,   '%' => 8,     '-m' => 9,  '+m' => 9,
		' '  => 10,  ':'  => 11,    'f'   => 12,
	);

	// Allowable functions
	private $arAvailableFunctions = array(
		'abs' => array('args' => true, 'func' => 'FunctionAbs'),
		'and' => array('args' => true, 'func' => 'FunctionAnd'),
		'dateadd' => array('args' => true, 'func' => 'FunctionDateAdd'),
		'datediff' => array('args' => true, 'func' => 'FunctionDateDiff'),
		'false' => array('args' => false, 'func' => 'FunctionFalse'),
		'if' => array('args' => true, 'func' => 'FunctionIf'),
		'intval' => array('args' => true, 'func' => 'FunctionIntval'),
		'min' => array('args' => true, 'func' => 'FunctionMin'),
		'max' => array('args' => true, 'func' => 'FunctionMax'),
		'not' => array('args' => true, 'func' => 'FunctionNot'),
		'or' => array('args' => true, 'func' => 'FunctionOr'),
		'substr' => array('args' => true, 'func' => 'FunctionSubstr'),
		'true' => array('args' => false, 'func' => 'FunctionTrue'),
		'convert' => array('args' => true, 'func' => 'FunctionConvert'),
		'merge' => array('args' => true, 'func' => 'FunctionMerge'),
		'addworkdays' => array('args' => true, 'func' => 'FunctionAddWorkDays'),
		'workdateadd' => array('args' => true, 'func' => 'FunctionWorkDateAdd'),
		'isworkday' => array('args' => true, 'func' => 'FunctionIsWorkDay'),
		'isworktime' => array('args' => true, 'func' => 'FunctionIsWorkTime'),
	);

	// Allowable errors
	private $arAvailableErrors = array(
		0 => 'Incorrect variable name - "#STR#"',
		1 => 'Empty',
		2 => 'Syntax error "#STR#"',
		3 => 'Unknown function "#STR#"',
		4 => 'Unmatched closing bracket ")"',
		5 => 'Unmatched opening bracket "("',
		6 => 'Division by zero',
		7 => 'Incorrect order of operands',
		8 => 'Incorrect arguments of function "#STR#"',
	);

	const Operation = 0;
	const Variable = 1;
	const Constant = 2;
}