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
	/** @var string */
	protected $code;

	/** @var string */
	protected $message;

	public function __construct($message, $code='BX_ERROR')
	{
		$this->message = $message;
		$this->code = $code;
	}

	public function getCode()
	{
		return $this->code;
	}

	public function getMessage()
	{
		return $this->message;
	}
}
