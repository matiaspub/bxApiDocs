<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

class Result
{
	protected $isSuccess;

	/** @var EntityError[] */
	protected $errors;

	public function __construct()
	{
		$this->isSuccess = true;
		$this->errors = array();
	}

	/**
	 * Returns result status
	 *
	 * @return bool
	 */
	public function isSuccess()
	{
		return $this->isSuccess;
	}

	/**
	 * Adds error message for specified field
	 *
	 * @param EntityError $error
	 */
	public function addError(EntityError $error)
	{
		$this->isSuccess = false;
		$this->errors[] = $error;
	}

	/**
	 * Returns array of FieldError objects
	 *
	 * @return FieldError[]
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Returns array of strings with error messages
	 *
	 * @return array
	 */
	public function getErrorMessages()
	{
		$messages = array();

		foreach($this->errors as $error)
			$messages[] = $error->getMessage();

		return $messages;
	}

	/**
	 * Adds array of FieldError objects
	 *
	 * @param FieldError[] $errors
	 */
	public function addErrors(array $errors)
	{
		if(is_array($errors))
		{
			foreach($errors as $error)
				$this->addError($error);
		}
	}
}
