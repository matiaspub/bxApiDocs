<?php
namespace Bitrix\Main\DB;

/**
 * Class Exception is used for all exceptions thrown in database.
 *
 * @see \Bitrix\Main\DB\Exception::__construct
 * @package Bitrix\Main\DB
 */
class Exception extends \Bitrix\Main\SystemException
{
	/** @var string */
	protected $databaseMessage;

	/**
	 * @param string $message Application message.
	 * @param string $databaseMessage Database reason.
	 * @param \Exception $previous The previous exception used for the exception chaining.
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести  при создании объекта какие-то действия. Код ошибки устанавливается 400. </p>
	*
	*
	* @param string $message = "" Сообщение исключения
	*
	* @param string $databaseMessage = "" Сообщение от базы данных.
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/exception/__construct.php
	* @author Bitrix
	*/
	public function __construct($message = "", $databaseMessage = "", \Exception $previous = null)
	{
		if (($message != "") && ($databaseMessage != ""))
			$message .= ": ".$databaseMessage;
		elseif (($message == "") && ($databaseMessage != ""))
			$message = $databaseMessage;

		$this->databaseMessage = $databaseMessage;

		parent::__construct($message, 400, '', 0, $previous);
	}

	/**
	 * Returns database specific message provided to the constructor.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает  конкретное сообщение БД предоставленное конструктору.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/exception/getdatabasemessage.php
	* @author Bitrix
	*/
	public function getDatabaseMessage()
	{
		return $this->databaseMessage;
	}
}
