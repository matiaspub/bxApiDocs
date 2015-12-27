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