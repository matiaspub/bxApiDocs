<?php

namespace Bitrix\Lists\Internals\Error;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Type\Dictionary;

final class ErrorCollection extends Dictionary
{
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
	 * @param Error[] $errors
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
	 * Add one error to collection.
	 * @param Error $error Error object.
	 */
	static public function addOne(Error $error)
	{
		$this[] = $error;
	}

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

	static public function hasErrors()
	{
		return count($this);
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param $code
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
	 * @param $code
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

	public function offsetSet($offset, $value)
	{
		$this->checkType($value);
		parent::offsetSet($offset, $value);
	}

	/**
	 * @param $value
	 * @throws ArgumentTypeException
	 */
	protected function checkType($value)
	{
		if(!$value instanceof Error)
		{
			throw new ArgumentTypeException('Could not push in ErrorCollection non Error.');
		}
	}
}