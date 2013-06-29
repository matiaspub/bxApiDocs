<?
class CBPCalc
{
	private $activity;
	private $arErrorsList = array();

	public function __construct($activity)
	{
		$this->activity = $activity;
	}

	private function GetVariableValue($variable)
	{
		$variable = trim($variable);
		if (!preg_match("#^\{=[a-z0-9_]+:[a-z0-9_]+\}$#i", $variable))
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
			\s*\{=[a-z0-9_]+:[a-z0-9_]+\}\s*  | # Variables
			(.+)                                # Any erroneous substring
			/xi';
		while (preg_match($preg, $text, $match))
		{
			if (isset($match[3]))
			{
				$this->SetError(2, $match[3]);
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

		if ($date == null)
			return null;

		if (intval($date)."!" != $date."!")
		{
			if (($dateTmp = MakeTimeStamp($date, FORMAT_DATETIME)) === false)
			{
				if (($dateTmp = MakeTimeStamp($date, FORMAT_DATE)) === false)
				{
					if (($dateTmp = MakeTimeStamp($date, "YYYY-MM-DD HH:MI:SS")) === false)
					{
						if (($dateTmp = MakeTimeStamp($date, "YYYY-MM-DD")) === false)
							return null;
					}
				}
			}

			$date = $dateTmp;
		}

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
		while (preg_match("/\s*([\d]+)\s*([a-z]+)\s*/i", $interval, $match))
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

	private function FunctionTrue()
	{
		return true;
	}

	// Operation priority
	private $arPriority = array(
		'(' => 0,       ')' => 1,       ';' => 2,       '=' => 3,       '<' => 3,       '>' => 3,
		'<=' => 3,      '>=' => 3,      '<>' => 3,      '&' => 4,       '+' => 5,       '-' => 5,
		'*' => 6,       '/' => 6,       '^' => 7,       '%' => 8,       '-m' => 9,      '+m' => 9,
		' ' => 10,      ':' => 11,      'f' => 12,
	);

	// Allowable functions
	private $arAvailableFunctions = array(
		'abs' => array('args' => true, 'func' => 'FunctionAbs'),
		'and' => array('args' => true, 'func' => 'FunctionAnd'),
		'dateadd'=> array('args' => true, 'func' => 'FunctionDateAdd'),
		'false' => array('args' => false, 'func' => 'FunctionFalse'),
		'if' => array('args' => true, 'func' => 'FunctionIf'),
		'intval' => array('args' => true, 'func' => 'FunctionIntval'),
		'min' => array('args' => true, 'func' => 'FunctionMin'),
		'not' => array('args' => true, 'func' => 'FunctionNot'),
		'or' => array('args' => true, 'func' => 'FunctionOr'),
		'substr' => array('args' => true, 'func' => 'FunctionSubstr'),
		'true' => array('args' => false, 'func' => 'FunctionTrue'),
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