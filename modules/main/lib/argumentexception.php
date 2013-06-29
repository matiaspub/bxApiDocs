<?php
namespace Bitrix\Main;

/**
 * Exception is thrown when function argument is not valid.
 */
class ArgumentException
	extends SystemException
{
	protected $parameter;

	public function __construct($message = "", $parameter = "", \Exception $previous = null)
	{
		parent::__construct($message, 100, '', '', $previous);
		$this->parameter = $parameter;
	}

	public function getParameter()
	{
		return $this->parameter;
	}
}
