<?php

namespace Bitrix\Lists\Internals\Error;

interface IErrorable 
{
	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	static public function getErrors();

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	static public function getErrorsByCode($code);

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	static public function getErrorByCode($code);
}