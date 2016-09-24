<?php

namespace Bitrix\Lists\Internals\Error;

use Bitrix\Main\Entity\Result;
use Bitrix\Main;

final class ErrorCollection extends Main\ErrorCollection
{
	/**
	 * Adds one error to collection.
	 * @param Error $error Error object.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод добавляет ошибку в коллекцию.</p>
	*
	*
	* @param mixed $Bitrix  Объект ошибок.
	*
	* @param Bitri $Lists  
	*
	* @param List $Internals  
	*
	* @param Internal $Error  
	*
	* @param Error $error  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/lists/errorcollection/addone.php
	* @author Bitrix
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
	 * Returns array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	
	/**
	* <p>Нестатический метод получает массив ошибок с необходимым кодом.</p>
	*
	*
	* @param mixed $code  Код ошибки.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/lists/errorcollection/geterrorsbycode.php
	* @author Bitrix
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
}