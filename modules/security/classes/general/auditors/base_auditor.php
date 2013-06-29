<?php

abstract class CSecurityFilterBaseAuditor
{
	private $splittingChar = " ";
	private $validString = "";

	protected $filters = array();
	protected $name = "";


	/**
	 * @param string $pString
	 * @return bool
	 */
	public function process($pString)
	{
		$this->lazyLoadFilters();
		$this->setValidString("");
		$found = false;

		$str2 = "";
		$strX = $pString;
		while($str2 <> $strX)
		{
				$str2 = $strX;
				$strX = preg_replace($this->filters["search"], $this->filters["replace"], $str2);
		}

		if($str2 <> $pString)
		{
			$this->setValidString($str2);
			$found = true;
		} 
		return $found;
	}


	/**
	 * @return string
	 */
	public function getValidString()
	{
		return $this->validString;
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @param string $pString
	 */
	protected function setValidString($pString)
	{
			$this->validString = $pString;
	}


	/**
	 * @param string $pChar
	 */
	protected function setSplittingChar($pChar)
	{
		if(is_string($pChar) && $pChar != "")
		{
			$this->splittingChar = $pChar;
		}
	}

	/**
	 * @param string $pCustomChar
	 * @return string
	 */
	protected function getSplittingChar($pCustomChar = "")
	{
		if(is_string($pCustomChar) && $pCustomChar != "")
		{
			return $pCustomChar;
		}
		elseif(is_string($this->splittingChar) && $this->splittingChar != "")
		{
			return $this->splittingChar;
		}
		else
		{
			return " ";
		}
	}

	/**
	 * make string like "\\1 * \\2 * \\3 * \\4"
	 * @param int $pSplitItemsCount
	 * @param string $pCustomSplitChar
	 * @return string
	 */
	protected function getSplittingString($pSplitItemsCount = 2, $pCustomSplitChar = "")
	{
		$glue = self::getSplittingChar($pCustomSplitChar)."\\";
		$result = "\\";
		$result .= join($glue, range(1, $pSplitItemsCount));
		return $result;
	}

	/**
	 *
	 */
	protected function lazyLoadFilters()
	{
		if(!$this->filters)
		{
			$this->filters = $this->getFilters();
		}
	}

	/**
	 * @return array
	 */
	abstract protected function getFilters();

}
