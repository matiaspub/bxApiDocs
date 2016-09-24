<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2015 Bitrix
 */

namespace Bitrix\Main;

use Bitrix\Main\Type\Dictionary;

class ErrorCollection extends Dictionary
{
	/**
	 * Constructor ErrorCollection.
	 * @param Error[] $values Initial errors in the collection.
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести  при создании объекта какие-то действия.</p>
	*
	*
	* @param array $arrayvalues = null Коллекция первоначальных ошибок.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/errorcollection/__construct.php
	* @author Bitrix
	*/
	public function __construct(array $values = null)
	{
		if($values)
		{
			$this->add($values);
		}
	}

	/**
	 * Adds an array of errors to the collection.
	 * @param Error[] $errors
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод добавляет массив ошибок в коллекцию.</p>
	*
	*
	* @param array $arrayerrors  
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/errorcollection/add.php
	* @author Bitrix
	*/
	public function add(array $errors)
	{
		foreach($errors as $error)
		{
			$this->setError($error);
		}
	}

	/**
	 * Returns an error with the necessary code.
	 * @param string|int $code The code of the error.
	 * @return Error|null
	 */
	
	/**
	* <p>Нестатический метод возвращает ошибку по полученному коду.</p>
	*
	*
	* @param mixed $string  Код ошибки.
	*
	* @param integer $code  
	*
	* @return \Bitrix\Main\Error|null 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/errorcollection/geterrorbycode.php
	* @author Bitrix
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

		return null;
	}

	/**
	 * Adds an error to the collection.
	 * @param Error $error An error object.
	 * @param $offset Offset in the array.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод добавляет ошибку в коллекцию.</p>
	*
	*
	* @param mixed $Bitrix  Объект ошибки
	*
	* @param Bitri $Main  Смещение в массиве.
	*
	* @param Error $error  
	*
	* @param Error $offset = null 
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/errorcollection/seterror.php
	* @author Bitrix
	*/
	static public function setError(Error $error, $offset = null)
	{
		parent::offsetSet($offset, $error);
	}

	/**
	 * \ArrayAccess thing.
	 * @param mixed $offset
	 * @param mixed $value
	 */
	
	/**
	* <p>Нестатический метод. Реализация интерфейса <code>\ArrayAccess</code>.</p>
	*
	*
	* @param mixed $offset  
	*
	* @param mixed $value  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/errorcollection/offsetset.php
	* @author Bitrix
	*/
	public function offsetSet($offset, $value)
	{
		$this->setError($value, $offset);
	}
}
