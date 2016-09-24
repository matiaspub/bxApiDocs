<?php

interface ILearnPath
{
	/**
	 * @param integer/string/array (if string must be strictly castable to integer).
	 * allowed many params. First param - is the id of top, etc.
	 * if array => than it must be one argument and it will be parsed as path in format for self::SetPathFromArray()
	 */
	static public function __construct();

	/**
	 * @param integer/string (if string must be strictly castable to integer).
	 * allowed many params. First param - is the id of top, etc.
	 */
	static public function SetPath();

	/**
	 * @param array of lessons' ids
	 */
	static public function SetPathFromArray($arPath);

	/**
	 * @param string urlencoded path, which can produced by exportUrlencoded()
	 */
	static public function ImportUrlencoded($str);

	/**
	 * @return string urlencoded path, which can be used for importUrlencoded()
	 */
	static public function ExportUrlencoded();

	/**
	 * @return array of lessons' ids in current path (from top to bottom)
	 */
	static public function GetPathAsArray();

	/**
	 * @return integer/bool id of top (left) element in path. If path is empty - returns FALSE
	 */
	static public function GetTop();

	/**
	 * @return integer/bool id of bottom (right) element in path. If path is empty - returns FALSE
	 */
	static public function GetBottom();

	/**
	 * Removes last element (if exists) from path
	 * @return integer/bool id of bottom (right) element in path. If path is empty - returns FALSE
	 */
	static public function PopBottom();

	/**
	 * Removes first element (if exists) from path
	 * @return integer/bool id of first (left) element in path. If path is empty - returns FALSE
	 */
	static public function ShiftTop();


	/**
	 * @return integer count of elements in path
	 */
	static public function Count();


	/**
	 * @param string delimiter, by default is ' / '
	 * @param string pattern, how names will be renderred. 
	 * Default pattern is '#NAME#'
	 * Available fields: #NAME#, #LESSON_ID#
	 * 
	 * @return string such as "Course bla bla bla / chapter XXX / lesson XXX"
	 */
	static public function GetPathAsHumanReadableString($delimiter = ' / ', $pattern = '#NAME#');
}

class CLearnPath implements ILearnPath
{
	const DELIMITER = '.';
	protected $arPath = array();

	public function __construct ()
	{
		$this->_SetPath (func_get_args());
	}

	public function SetPath ()
	{
		$this->_SetPath (func_get_args());
	}

	protected function _SetPath ($args)
	{
		// If only one arguments and it's an array => set path from array.
		if ( (count($args) == 1) && (is_array($args[0])) )
		{
			$this->_SetPath ($args[0]);
			return;
		}

		$this->arPath = array();

		foreach ($args as $key => $lessonId)
		{
			if (
				( ! is_numeric($lessonId) )
				|| ( ! is_int($lessonId + 0) )
			)
			{
				$this->arPath = array();
				return;
			}

			$this->arPath[] = (int) ($lessonId);
		}
	}

	public function SetPathFromArray($arPath)
	{
		$this->_SetPath ($arPath);
	}

	// returns true if $str is path with two or more elements
	public static function IsUrlencodedPath ($str)
	{
		$tmp = urldecode($str);

		if (strpos($tmp, self::DELIMITER) !== false)
			return (true);
		else
			return (false);
	}


	public function ImportUrlencoded($str)
	{
		$this->arPath = array();

		$tmp = urldecode($str);
		if (strlen($tmp) == 0)
		{
			return;
		}

		$arPath = explode(self::DELIMITER, $tmp);
		if ( ! is_array($arPath) )
		{
			return;
		}

		$this->_SetPath($arPath);
	}

	public function GetPathAsArray()
	{
		return ($this->arPath);
	}

	public function ExportUrlencoded()
	{
		return (urlencode(implode(self::DELIMITER, $this->arPath)));
	}

	public function GetTop()
	{
		if ( ! isset($this->arPath[0]) )
			return (false);

		return ($this->arPath[0]);
	}

	public function GetBottom()
	{
		$count = count ($this->arPath);
		if ( ! isset($this->arPath[$count - 1]) )
			return (false);

		return ($this->arPath[$count - 1]);
	}


	public function PopBottom()
	{
		$popped = array_pop ($this->arPath);

		// If there is no elements was in path
		if ($popped === NULL)
			return (false);

		return ($popped);
	}


	public function ShiftTop()
	{
		$shifted = array_shift ($this->arPath);

		// If there is no elements was in path
		if ($shifted === NULL)
			return (false);

		return ($shifted);
	}


	public function GetPathAsHumanReadableString($delimiter = ' / ', $pattern = '#NAME#')
	{
		$arHumanReadablePath = array();
		foreach ($this->arPath as $lessonId)
		{
			$rc = CLearnLesson::GetByID($lessonId);
			$rc = $rc->Fetch();
			$id   = '???';
			$name = '???';
			if (isset($rc['LESSON_ID']))
				$id = $rc['LESSON_ID'];

			if (isset($rc['NAME']))
				$name = htmlspecialcharsbx($rc['NAME']);

			$txt = $pattern;
			$txt = str_replace('#LESSON_ID#', $id, $txt);
			$txt = str_replace('#NAME#', $name, $txt);

			$arHumanReadablePath[] = $txt;
		}

		return (implode($delimiter, $arHumanReadablePath));
	}


	public function Count()
	{
		return (count($this->arPath));
	}
}