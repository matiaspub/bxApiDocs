<?php
namespace Bitrix\Main\DB;

/**
 * Class ConnectionException used to indicate errors during database connection process.
 *
 * @see \Bitrix\Main\DB\ConnectionException::__construct
 * @package Bitrix\Main\DB
 */
class ConnectionException extends Exception
{
	/**
	 * @param string $message Application message.
	 * @param string $databaseMessage Database reason.
	 * @param \Exception $previous The previous exception used for the exception chaining.
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести  при создании объекта какие-то действия.</p>
	*
	*
	* @param string $message = "" Сообщение исключения
	*
	* @param string $databaseMessage = "" Сообщение базы данных
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/connectionexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($message = "", $databaseMessage = "", \Exception $previous = null)
	{
		parent::__construct($message, $databaseMessage, $previous);
	}
}
