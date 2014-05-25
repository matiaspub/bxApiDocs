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
	/** @var bool */
	protected $isSuccess;
	/** @var  array */
	protected $data;

	/** @var bool  */
	protected $wereErrorsChecked = false;

	/** @var EntityError[] */
	protected $errors;

	public function __construct()
	{
		$this->isSuccess = true;
		$this->errors = array();
	}

	/**
	 * Returns result status
	 * Within the core and events should be called with internalCall flag
	 *
	 * @param bool $internalCall
	 *
	 * @return bool
	 */
	public function isSuccess($internalCall = false)
	{
		$this->wereErrorsChecked = !$internalCall;

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
		$this->wereErrorsChecked = true;

		return $this->errors;
	}

	/**
	 * Returns array of strings with error messages
	 *
	 * @return array
	 */
	public function getErrorMessages()
	{
		$this->wereErrorsChecked = true;

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

	public function __destruct()
	{
		if (!$this->isSuccess && !$this->wereErrorsChecked)
		{
			// nobody interested in my errors :(
			// make a warning (usually it should be written in log)
			trigger_error(join('; ', $this->getErrorMessages()), E_USER_WARNING);
		}
	}

	public function setData(array $data)
	{
		$this->data = $data;
	}

	/**
	 * Returns data array saved into the record
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}
}
