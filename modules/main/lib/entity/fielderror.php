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
	const EMPTY_REQUIRED = 'BX_EMPTY_REQUIRED';
	const INVALID_VALUE = 'BX_INVALID_VALUE';

	/** @var Field */
	protected $field;

	public function __construct(Field $field, $message, $code='BX_ERROR')
	{
		parent::__construct($message, $code);
		$this->field = $field;
	}

	public function getField()
	{
		return $this->field;
	}
}
