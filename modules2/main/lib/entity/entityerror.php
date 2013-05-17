<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

class EntityError
{
	/** @var int */
	protected $code;

	/** @var string */
	protected $message;

	static public function __construct($message, $code=0)
	{
		$this->message = $message;
		$this->code = $code;
	}

	static public function getCode()
	{
		return $this->code;
	}

	static public function getMessage()
	{
		return $this->message;
	}
}
