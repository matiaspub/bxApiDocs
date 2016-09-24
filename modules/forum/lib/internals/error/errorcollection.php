<?php

namespace Bitrix\Forum\Internals\Error;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Type\Dictionary;

final class ErrorCollection extends Dictionary
{
	/**
	 * Constructor ErrorCollection.
	 * @param array $values Errors which need to add in collection.
	 * @throws ArgumentTypeException
	 */
	public function __construct(array $values = null)
	{
		if($values)
		{
			foreach($values as $value)
			{
				$this->checkType($value);
			}
		}
		unset($value);

		parent::__construct($values);
	}

	/**
	 * Adds array of errors to collection.
	 * @param Error[] $errors Array of errors.
	 * @return void
	 */
	static public function add(array $errors)
	{
		foreach ($errors as $error)
		{
			$this[] = $error;
		}
		unset($error);
	}

	/**
	 * Adds one error to collection.
	 * @param Error $error Error object.
	 * @return void
	 */
	static public function addOne(Error $error)
	{
		$this[] = $error;
	}

	/**
	 * Adds errors from Main\Entity\Result.
	 * @param Result $result Result after action in Entity.
	 * @return void
	 */
	public function addFromResult(Result $result)
	{
		$errors = array();
		foreach ($result->getErrorMessages() as $message)
		{
			$errors[] = new Error($message);
		}
		unset($message);

		$this->add($errors);
	}

	/**
	 * Returns true if collection has errors.
	 * @return bool
	 */
	static public function hasErrors()
	{
		return (bool)count($this);
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		$needle = array();
		foreach($this->values as $error)
		{
			/** @var Error $error */
			if($error->getCode() == $code)
			{
				$needle[] = $error;
			}
		}
		unset($error);

		return $needle;
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorByCode($code)
	{
		foreach($this->values as $error)
		{
			/** @var Error $error */
			if($error->getCode() == $code)
			{
				return $error;
			}
		}
		unset($error);

		return null;
	}

	/**
	 * Offset to set error.
	 * @param Error $offset Offset.
	 * @param string|int $value Error.
	 * @throws ArgumentTypeException
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->checkType($value);
		parent::offsetSet($offset, $value);
	}

	/**
	 * @param $value
	 * @throws ArgumentTypeException
	 */
	private function checkType($value)
	{
		if(!$value instanceof Error)
		{
			throw new ArgumentTypeException('Could not push in ErrorCollection non Error.');
		}
	}
}