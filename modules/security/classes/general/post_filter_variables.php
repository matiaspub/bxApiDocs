<?php
/**
* Bitrix Framework
* @package bitrix
* @subpackage security
* @copyright 2001-2013 Bitrix
*/

class CSecurityXSSDetectVariables
{

	const PATTERN_DELIMITER = '~';

	private $searchPatternsWithQuotes = array();
	private $searchPatternsWithoutQuotes = array();
	private $originalValues = array();

	/**
	 * @param string $pName
	 * @param string $pValue
	 */
	public function addVariable($pName, $pValue)
	{
		$this->originalValues[$pName] = $pValue;
	}

	/**
	 * @param string $pKey
	 * @return array
	 */
	public function getQuoteSearchPattern($pKey = "")
	{
		$this->lazyParseVariables();
		if(is_string($pKey) && $pKey != "")
		{
			return $this->searchPatternsWithQuotes[$pKey];
		}
		else
		{
			return $this->searchPatternsWithQuotes;
		}
	}

	/**
	 * @param string $pKey
	 * @return array
	 */
	public function getSearchPattern($pKey = "")
	{
		$this->lazyParseVariables();
		if(is_string($pKey) && $pKey != "")
		{
			return $this->searchPatternsWithoutQuotes[$pKey];
		}
		else
		{
			return $this->searchPatternsWithoutQuotes;
		}
	}

	/**
	 * @param string $pKey
	 * @return array
	 */
	public function getOriginalValue($pKey = "")
	{
		if(is_string($pKey) && $pKey != "")
		{
			return $this->originalValues[$pKey];
		}
		else
		{
			return $this->originalValues;
		}
	}

	/**
	 * @return bool
	 */
	public function isEmpty()
	{
		return (empty($this->originalValues));
	}

	/**
	 * @param string $pString
	 * @return mixed
	 */
	protected static function removeComments($pString)
	{
		return preg_replace('/(
				(?s:\\/\\*.*?\\*\\/)                                     # multiline comments
				|
				\\/\\/.*?(?:\\n|$)                                       # singleline comments
			)/x', '', $pString);
	}

	/**
	 * @param string $pString
	 * @return string
	 */
	protected static function pregQuote($pString)
	{
		return preg_quote($pString, self::PATTERN_DELIMITER);
	}

	/**
	 * @param string|array $pNeedle
	 * @param bool $pIsQuotePattern
	 * @return string
	 */
	protected static function getPattern($pNeedle, $pIsQuotePattern = false)
	{
		if(!is_array($pNeedle))
		{
			$needles = array($pNeedle);
		}
		else
		{
			$needles = $pNeedle;
		}
		$needles = array_map(array(__CLASS__,"pregQuote"), $needles);
		$needles = array_filter($needles);

		$pattern = self::PATTERN_DELIMITER;
		if($pIsQuotePattern)
			$pattern .= "(?<!\\\)";

		$pattern .= "(".implode("|",$needles).")";
		$pattern .= self::PATTERN_DELIMITER."i";
		return $pattern;
	}

	/**
	 * @param string $pName
	 * @param string $pOriginalValue
	 * @param string $pSearchPattern
	 * @param bool $pIsEscapeVariable
	 * @return bool
	 */
	protected function pushVariable($pName, $pOriginalValue, $pSearchPattern, $pIsEscapeVariable = false)
	{
		if($pIsEscapeVariable)
			$this->searchPatternsWithQuotes[$pName] = array("variable_len" => strlen($pOriginalValue), "pattern" => $pSearchPattern);
		else
			$this->searchPatternsWithoutQuotes[$pName] = array("variable_len" => strlen($pOriginalValue), "pattern" => $pSearchPattern);
		return true;
	}

	/**
	 * @param string $pName
	 * @param string $pValue
	 * @return bool
	 */
	protected function parseVariable($pName, $pValue)
	{
		if(!$pValue)
			return true;

		if(preg_match("/[^\\\](((\\\)(\\\))*+')+/s"," ".$pValue))
		{
			return $this->pushVariable(
				$pName,
				$pValue,
				self::getPattern(array($pValue, htmlspecialcharsbx($pValue)), true),
				true
			);
		}
		elseif(preg_match("/[^\\\](((\\\)(\\\))*+\")+/s"," ".$pValue))
		{
			return $this->pushVariable(
				$pName,
				$pValue,
				self::getPattern($pValue, true),
				true
			);
		}
		else
		{
//			$value = self::removeComments($pValue);
			return $this->pushVariable(
				$pName,
				$pValue,
				self::getPattern(array($pValue, htmlspecialcharsbx($pValue)))
			);
		}
	}

	/**
	 *
	 */
	protected function lazyParseVariables()
	{
		$isVariablesNotParsed = empty($this->searchPatternsWithQuotes) && empty($this->searchPatternsWithoutQuotes);
		$isAnyVariableExist = !empty($this->originalValues);
		if($isVariablesNotParsed && $isAnyVariableExist)
		{
			foreach($this->originalValues as $name => $value)
			{
				$this->parseVariable($name, $value);
			}
		}
	}
}
