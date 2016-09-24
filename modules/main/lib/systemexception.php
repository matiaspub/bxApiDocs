<?php
namespace Bitrix\Main;

/**
 * Base class for fatal exceptions
 */
class SystemException
	extends \Exception
{
	/**
	 * Creates new exception object.
	 *
	 * @param string $message
	 * @param int $code
	 * @param string $file
	 * @param int $line
	 * @param \Exception $previous
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия.</p>
	*
	*
	* @param string $message = "" Сообщение исключения
	*
	* @param integer $code  Код, вызвавший исключение
	*
	* @param string $file = "" Файл вызвавший исключение
	*
	* @param integer $line  Строка в файле
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/systemexception/__construct.php
	* @author Bitrix
	*/
	public function __construct($message = "", $code = 0, $file = "", $line = 0, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);

		$this->file = $file;
		$this->line = intval($line);
	}
}
