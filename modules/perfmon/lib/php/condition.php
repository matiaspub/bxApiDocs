<?php
namespace Bitrix\Perfmon\Php;

class Condition
{
	protected $predicate = '';

	/**
	 * @param string $predicate Php condition.
	 */
	public function __construct($predicate)
	{
		$this->predicate = (string)$predicate;
	}

	/**
	 * @return string
	 */
	public function getPredicate()
	{
		return $this->predicate;
	}
}