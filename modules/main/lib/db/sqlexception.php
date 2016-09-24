<?php
namespace Bitrix\Main\DB;

/**
 * Exception is thrown when database returns a error.
 *
 * @see \Bitrix\Main\DB\SqlException::__construct
 * @package Bitrix\Main\DB
 */
class SqlException extends Exception
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
	* @param string $databaseMessage = "" Сообщение от базы данных.
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($message = "", $databaseMessage = "", \Exception $previous = null)
	{
		parent::__construct($message, $databaseMessage, $previous);
	}
}

/**
 * Exception is thrown when database returns a error on query execution.
 *
 * @see \Bitrix\Main\DB\SqlQueryException::__construct
 * @package Bitrix\Main\DB
 */
class SqlQueryException extends SqlException
{
	/** @var string */
	protected $query = "";

	/**
	 * @param string $message Application message.
	 * @param string $databaseMessage Database reason.
	 * @param string $query Sql query text.
	 * @param \Exception $previous The previous exception used for the exception chaining.
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести  при создании объекта какие-то действия.</p>
	*
	*
	* @param string $message = "" Сообщение исключения
	*
	* @param string $databaseMessage = "" Сообщение от базы данных.
	*
	* @param string $query = "" Текст sql запроса.
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlqueryexception/__construct.php
	* @author Bitrix
	*/
	public function __construct($message = "", $databaseMessage = "", $query = "", \Exception $previous = null)
	{
		parent::__construct($message, $databaseMessage, $previous);
		$this->query = $query;
	}

	/**
	 * Returns text of the sql query.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает текст sql запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/sqlqueryexception/getquery.php
	* @author Bitrix
	*/
	public function getQuery()
	{
		return $this->query;
	}
}
