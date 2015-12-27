<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2015 Bitrix
 */

namespace Bitrix\Main;

class Result
{
	/** @var bool */
	protected $isSuccess = true;

	/** @var ErrorCollection */
	protected $errors;

	/** @var  array */
	protected $data = array();

	public function __construct()
	{
		$this->errors = new ErrorCollection();
	}

	/**
	 * Returns the result status.
	 *
	 * @return bool
	 */
	public function isSuccess()
	{
		return $this->isSuccess;
	}

	/**
	 * Adds the error.
	 *
	 * @param Error $error
	 */
	public function addError(Error $error)
	{
		$this->isSuccess = false;
		$this->errors[] = $error;
	}

	/**
	 * Returns an array of Error objects.
	 *
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errors->toArray();
	}

	/**
	 * Returns the error collection.
	 *
	 * @return ErrorCollection
	 */
	public function getErrorCollection()
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

		foreach($this->getErrors() as $error)
			$messages[] = $error->getMessage();

		return $messages;
	}

	/**
	 * Adds array of Error objects
	 *
	 * @param Error[] $errors
	 */
	public function addErrors(array $errors)
	{
		$this->isSuccess = false;
		$this->errors->add($errors);
	}

	/**
	 * Sets data of the result.
	 * @param array $data
	 */
	public function setData(array $data)
	{
		$this->data = $data;
	}

	/**
	 * Returns data array saved into the result.
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}
}
