<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2015 Bitrix
 */

namespace Bitrix\Main;

class Error
{
	/** @var int|string */
	protected $code;

	/** @var string */
	protected $message;

	/**
	 * Creates a new Error.
	 * @param string $message Message of the error.
	 * @param int|string $code Code of the error.
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести  при создании объекта какие-то действия.</p>
	*
	*
	* @param string $message  Сообщение об ошибке.
	*
	* @param string $integer  Код ошибки.
	*
	* @param string $code  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/error/__construct.php
	* @author Bitrix
	*/
	public function __construct($message, $code = 0)
	{
		$this->message = $message;
		$this->code = $code;
	}

	/**
	 * Returns the code of the error.
	 * @return int|string
	 */
	
	/**
	* <p>Нестатический метод возвращает код ошибки.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/error/getcode.php
	* @author Bitrix
	*/
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * Returns the message of the error.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает сообщение об ошибке.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/error/getmessage.php
	* @author Bitrix
	*/
	public function getMessage()
	{
		return $this->message;
	}

	public function __toString()
	{
		return $this->getMessage();
	}
}
