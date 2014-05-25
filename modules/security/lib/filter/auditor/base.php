<?php
/**
 * Bitrix Security Module
 * @package Bitrix
 * @subpackage Security
 * @copyright 2001-2013 Bitrix
 * @since File available since 14.0.0
 */
namespace Bitrix\Security\Filter\Auditor;

/**
 * Abstract class of all security auditors
 *
 * @package Bitrix\Security\Filter\Auditor
 * @since 14.0.0
 */
abstract class Base
{
	private $splittingChar = ' ';
	private $filteredValue = '';

	protected $filters = array();
	protected $name = '';


	public function __construct($splittingChar = '')
	{
		$this->setSplittingChar($splittingChar);
	}

	/**
	 * Process and save filtered value
	 * Return true if value triggered auditor filtration
	 * For get filtered value use Base::getFilteredValue
	 *
	 * Simple example:
	 * <code>
	 * $ob = new Base();
	 * if ($ob->process($someValue))
	 *     $someValue = $ob->getFilteredValue();
	 * </code>
	 * @param string $value
	 * @return bool
	 */
	public function process($value)
	{
		$this->initializeFilters();
		$this->setFilteredValue('');
		$found = false;

		$str2 = '';
		$strX = $value;
		while ($str2 != $strX)
		{
				$str2 = $strX;
				$strX = preg_replace($this->filters['search'], $this->filters['replace'], $str2);
		}

		if ($str2 != $value)
		{
			$this->setFilteredValue($str2);
			$found = true;
		} 
		return $found;
	}


	/**
	 * Return filtered value after last value processing
	 *
	 * @see Base::process
	 * @return string
	 */
	public function getFilteredValue()
	{
		return $this->filteredValue;
	}


	/**
	 * Return auditor name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @param string $string
	 */
	protected function setFilteredValue($string)
	{
			$this->filteredValue = $string;
	}


	/**
	 * @param string $char
	 */
	protected function setSplittingChar($char)
	{
		if (is_string($char) && $char != '')
		{
			$this->splittingChar = $char;
		}
	}

	/**
	 * @param string $customChar
	 * @return string
	 */
	protected function getSplittingChar($customChar = '')
	{
		if (is_string($customChar) && $customChar != '')
		{
			return $customChar;
		}
		elseif (is_string($this->splittingChar) && $this->splittingChar != '')
		{
			return $this->splittingChar;
		}
		else
		{
			return ' ';
		}
	}

	/**
	 * make string like '\\1 * \\2 * \\3 * \\4'
	 * @param int $splitItemsCount
	 * @param string $customSplitChar
	 * @return string
	 */
	protected function getSplittingString($splitItemsCount = 2, $customSplitChar = '')
	{
		$glue = self::getSplittingChar($customSplitChar).'\\';
		$result = '\\';
		$result .= join($glue, range(1, $splitItemsCount));
		return $result;
	}

	protected function initializeFilters()
	{
		if (!$this->filters)
		{
			$this->filters = $this->getFilters();
		}
	}

	/**
	 * @return array
	 */
	abstract protected function getFilters();

}
