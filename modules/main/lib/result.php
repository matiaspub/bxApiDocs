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
	
	/**
	* <p>Нестатический метод возвращает статус результата.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/result/issuccess.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод добавляет ошибку.</p>
	*
	*
	* @param mixed $Bitrix  
	*
	* @param Bitri $Main  
	*
	* @param Error $error  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/result/adderror.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает массив объектов <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/error/index.php">\Main\Error</a>.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/result/geterrors.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает коллекцию ошибок.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\ErrorCollection 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/result/geterrorcollection.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает массив строк с сообщениями об ошибках.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/result/geterrormessages.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод добавляет массив объектов ошибок.</p>
	*
	*
	* @param array $arrayerrors  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/result/adderrors.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод устанавливает данные результата.</p>
	*
	*
	* @param array $data  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/result/setdata.php
	* @author Bitrix
	*/
	public function setData(array $data)
	{
		$this->data = $data;
	}

	/**
	 * Returns data array saved into the result.
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод возвращает массив данных, записанных в результат.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/result/getdata.php
	* @author Bitrix
	*/
	public function getData()
	{
		return $this->data;
	}
}
