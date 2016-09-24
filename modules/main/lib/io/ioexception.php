<?php
namespace Bitrix\Main\IO;

/**
 * This exception is thrown when an I/O error occurs.
 */
class IoException extends \Bitrix\Main\SystemException
{
	protected $path;

	/**
	 * Creates new exception object.
	 *
	 * @param string $message Exception message
	 * @param string $path Path that generated exception.
	 * @param \Exception $previous
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия. Принимает на вход только сообщение и путь. Код ошибки передается 120.</p>
	*
	*
	* @param string $message = "" Сообщение исключения
	*
	* @param string $path = "" Путь, который сгенерировал исключение.
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/ioexception/__construct.php
	* @author Bitrix
	*/
	public function __construct($message = "", $path = "", \Exception $previous = null)
	{
		parent::__construct($message, 120, '', 0, $previous);
		$this->path = $path;
	}

	/**
	 * Path that generated exception.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает путь, который генерирует исключение.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/ioexception/getpath.php
	* @author Bitrix
	*/
	public function getPath()
	{
		return $this->path;
	}
}

class InvalidPathException extends IoException
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия.  В Конструктор передается только путь.</p>
	*
	*
	* @param mixed $path  Путь к файлу.
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/invalidpathexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($path, \Exception $previous = null)
	{
		$message = sprintf("Path '%s' is invalid.", $path);
		parent::__construct($message, $path, $previous);
	}
}

class FileNotFoundException extends IoException
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия. В Конструктор передается только путь.</p>
	*
	*
	* @param mixed $path  Путь к файлу.
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/filenotfoundexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($path, \Exception $previous = null)
	{
		$message = sprintf("Path '%s' is not found.", $path);
		parent::__construct($message, $path, $previous);
	}
}

class FileDeleteException extends IoException
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести какие-то действия, при создании объекта. В Конструктор передается только путь.</p>
	*
	*
	* @param mixed $path  Путь к файлу.
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/filedeleteexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($path, \Exception $previous = null)
	{
		$message = sprintf("Error occurred during deleting file '%s'.", $path);
		parent::__construct($message, $path, $previous);
	}
}

class FileOpenException extends IoException
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия. В Конструктор передается только путь.</p>
	*
	*
	* @param mixed $path  Путь к файлу.
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/fileopenexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($path, \Exception $previous = null)
	{
		$message = sprintf("Cannot open the file '%s'.", $path);
		parent::__construct($message, $path, $previous);
	}
}

class FileNotOpenedException extends IoException
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/filenotopenedexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($path, \Exception $previous = null)
	{
		$message = sprintf("The file '%s' is not opened.", $path);
		parent::__construct($message, $path, $previous);
	}
}
