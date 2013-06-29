<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

class FieldError extends EntityError
{
	const EMPTY_REQUIRED = 1;
	const INVALID_VALUE = 2;

	/** @var Field */
	protected $field;

	public function __construct(Field $field, $message, $code=0)
	{
		parent::__construct($message, $code);
		$this->field = $field;
	}

	public function getField()
	{
		return $this->field;
	}
}
