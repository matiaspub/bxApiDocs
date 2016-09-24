<?php
namespace Bitrix\Perfmon\Php;

class Statement
{
	protected $bodyLines = array();
	public $conditions = array();

	/**
	 * Adds one more line to the body.
	 *
	 * @param string $line Line of code.
	 *
	 * @return Statement
	 */
	
	/**
	* <p>Нестатический метод добавляет строку в код.</p>
	*
	*
	* @param string $line  Строка кода.
	*
	* @return \Bitrix\Perfmon\Php\Statement 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/php/statement/addline.php
	* @author Bitrix
	*/
	public function addLine($line)
	{
		$this->bodyLines[] = (string)$line;
		return $this;
	}

	/**
	 * Adds condition on which statement have to be executed.
	 *
	 * @param string $predicate Condition predicate.
	 *
	 * @return Statement
	 */
	
	/**
	* <p>Нестатический метод добавляет условие для исполнения выражения.</p>
	*
	*
	* @param string $predicate  Предикат условия.
	*
	* @return \Bitrix\Perfmon\Php\Statement 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/php/statement/addcondition.php
	* @author Bitrix
	*/
	public function addCondition($predicate)
	{
		$this->conditions[] = new Condition($predicate);
		return $this;
	}

	/**
	 * Merges two statements.
	 *
	 * @param Statement $stmt Contains lines to be added.
	 *
	 * @return Statement
	 */
	
	/**
	* <p>Нестатический метод объединяет два выражения.</p>
	*
	*
	* @param mixed $Bitrix  Содержит добавляемые строки.
	*
	* @param Bitri $Perfmon  
	*
	* @param Perfmo $Php  
	*
	* @param Statement $stmt  
	*
	* @return \Bitrix\Perfmon\Php\Statement 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/php/statement/merge.php
	* @author Bitrix
	*/
	public function merge(Statement $stmt)
	{
		foreach ($stmt->bodyLines as $line)
		{
			$this->addLine($line);
		}
		return $this;
	}

	/**
	 * Return body aligned with tab characters.
	 *
	 * @param integer $level Code align level.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод добавляет выравнивание кода с помощью символов табуляции.</p>
	*
	*
	* @param integer $level  Уровень выравнивания кода.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/perfmon/php/statement/formatbodylines.php
	* @author Bitrix
	*/
	public function formatBodyLines($level = 0)
	{
		$body = '';
		foreach ($this->bodyLines as $line)
		{
			$body .= str_repeat("\t", $level).$line."\n";
		}
		return $body;
	}
}