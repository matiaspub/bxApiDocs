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
	
	/**
	* <p>Нестатический метод обрабатывает и сохраняет отфильтрованное значение. Возвращает <code>true</code>, если значение было отфильтровано аудитором. Для использования отфильтрованного значения используется <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/security/filter/auditor/base/getfilteredvalue.php">Base::getFilteredValue</a> (<code>\Bitrix\Security\Filter\Auditor\Base::getFilteredValue</code>).</p>
	*
	*
	* @param string $value  Обрабатываемое значение.
	*
	* @return boolean 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $ob = new Base();
	* if ($ob-&gt;process($someValue))
	*     $someValue = $ob-&gt;getFilteredValue();
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/filter/auditor/base/process.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает отфильтрованное значение после последней обработки значений.</p> <p>Без параметров</p>
	*
	*
	* @return string 
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/security/filter/auditor/base/process.php">Base::process</a>
	* (<code>\Bitrix\Security\Filter\Auditor\Base::process</code>)</li> </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/filter/auditor/base/getfilteredvalue.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает имя аудитора.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/filter/auditor/base/getname.php
	* @author Bitrix
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
