<?php

namespace Bitrix\Lists\Internals\Error;

class Error 
{
	/** @var int */
	protected $code;

	/** @var string */
	protected $message;

	/**
	 * Creates new Error.
	 * @param string $message Message of error.
	 * @param int|string $code Code of error.
	 */
	public function __construct($message, $code = 0)
	{
		$this->message = $message;
		$this->code = $code;
	}

	/**
	 * Returns code of error.
	 * @return int|string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * Returns message of error.
	 * @return string
	 */
	public function getMessage()
	{
		return (string)$this->message;
	}

	public function __toString()
	{
		return $this->getMessage();
	}
}
