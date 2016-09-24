<?php
namespace Bitrix\Main;

/**
 * Base class for fatal exceptions
 */
class SystemException extends \Exception
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

		if (!empty($file) && !empty($line))
		{
			$this->file = $file;
			$this->line = $line;
		}
	}
}

/**
 * Exception is thrown when function argument is not valid.
 */
class ArgumentException extends SystemException
{
	protected $parameter;

	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести какие-то действия, при создании объекта. Код ошибки конструктор задает как 100.</p>
	*
	*
	* @param mixed $message = "" Сообщение.
	*
	* @param mixed $parameter = "" Параметр. Должен быть не пустым.
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/argumentexception/__construct.php
	* @author Bitrix
	*/
	public function __construct($message = "", $parameter = "", \Exception $previous = null)
	{
		parent::__construct($message, 100, '', 0, $previous);
		$this->parameter = $parameter;
	}

	
	/**
	* <p>Нестатический метод возвращает переданный в конструктор параметр.</p>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/argumentexception/getparameter.php
	* @author Bitrix
	*/
	public function getParameter()
	{
		return $this->parameter;
	}
}


/**
 * Exception is thrown when "empty" value is passed to a function that does not accept it as a valid argument.
 */
class ArgumentNullException extends ArgumentException
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести какие-то действия, при создании объекта. Конструктор принимает параметр и автоматически формирует сообщение. </p>
	*
	*
	* @param mixed $parameter  Параметр. Должен быть не пустым.
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/argumentnullexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($parameter, \Exception $previous = null)
	{
		$message = sprintf("Argument '%s' is null or empty", $parameter);
		parent::__construct($message, $parameter, $previous);
	}
}


/**
 * Exception is thrown when the value of an argument is outside the allowable range of values.
 */
class ArgumentOutOfRangeException extends ArgumentException
{
	protected $lowerLimit;
	protected $upperLimit;

	/**
	 * Creates new exception object.
	 *
	 * @param string $parameter Argument that generates exception
	 * @param null $lowerLimit Either lower limit of the allowable range of values or an array of allowable values
	 * @param null $upperLimit Upper limit of the allowable values
	 * @param \Exception $previous
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести какие-то действия, при создании объекта.</p>
	*
	*
	* @param string $parameter  Аргумент, который создал исключение.
	*
	* @param null $lowerLimit = null Нижний предел возможных значений или массив возможных значений.
	*
	* @param null $upperLimit = null Верхний предел возможных значений.
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/argumentoutofrangeexception/__construct.php
	* @author Bitrix
	*/
	public function __construct($parameter, $lowerLimit = null, $upperLimit = null, \Exception $previous = null)
	{
		if (is_array($lowerLimit))
			$message = sprintf("The value of an argument '%s' is outside the allowable range of values: %s", $parameter, implode(", ", $lowerLimit));
		elseif (($lowerLimit !== null) && ($upperLimit !== null))
			$message = sprintf("The value of an argument '%s' is outside the allowable range of values: from %s to %s", $parameter, $lowerLimit, $upperLimit);
		elseif (($lowerLimit === null) && ($upperLimit !== null))
			$message = sprintf("The value of an argument '%s' is outside the allowable range of values: not greater than %s", $parameter, $upperLimit);
		elseif (($lowerLimit !== null) && ($upperLimit === null))
			$message = sprintf("The value of an argument '%s' is outside the allowable range of values: not less than %s", $parameter, $lowerLimit);
		else
			$message = sprintf("The value of an argument '%s' is outside the allowable range of values", $parameter);

		$this->lowerLimit = $lowerLimit;
		$this->upperLimit = $upperLimit;

		parent::__construct($message, $parameter, $previous);
	}

	public function getLowerLimitType()
	{
		return $this->lowerLimit;
	}

	
	/**
	* <p>Нестатический метод возвращает верхний предел.</p>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/argumentoutofrangeexception/getuppertype.php
	* @author Bitrix
	*/
	public function getUpperType()
	{
		return $this->upperLimit;
	}
}


/**
 * Exception is thrown when the type of an argument is not accepted by function.
 */
class ArgumentTypeException	extends ArgumentException
{
	protected $requiredType;

	/**
	 * Creates new exception object
	 *
	 * @param string $parameter Argument that generates exception
	 * @param string $requiredType Required type
	 * @param \Exception $previous
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести какие-то действия, при создании объекта.</p>
	*
	*
	* @param string $parameter  Аргумент, который создал исключение
	*
	* @param string $requiredType = "" Требуемый тип
	*
	* @param Exception $previous = null Предыдущее исключение. Используется для построения цепочки
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/argumenttypeexception/__construct.php
	* @author Bitrix
	*/
	public function __construct($parameter, $requiredType = "", \Exception $previous = null)
	{
		if (!empty($requiredType))
			$message = sprintf("The value of an argument '%s' must be of type %s", $parameter, $requiredType);
		else
			$message = sprintf("The value of an argument '%s' has an invalid type", $parameter);

		$this->requiredType = $requiredType;

		parent::__construct($message, $parameter, $previous);
	}

	
	/**
	* <p>Нестатический метод возвращает тип, который пришел в конструктор.</p>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/argumenttypeexception/getrequiredtype.php
	* @author Bitrix
	*/
	public function getRequiredType()
	{
		return $this->requiredType;
	}
}


/**
 * Exception is thrown when operation is not implemented but should be.
 */
class NotImplementedException extends SystemException
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия. Устанавливает код ошибки 140.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/notimplementedexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($message = "", \Exception $previous = null)
	{
		parent::__construct($message, 140, '', 0, $previous);
	}
}


/**
 * Exception is thrown when operation is not supported.
 */
class NotSupportedException extends SystemException
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия. Конструктор принимает только сообщение и устанавливает код ошибки 150.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/notsupportedexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($message = "", \Exception $previous = null)
	{
		parent::__construct($message, 150, '', 0, $previous);
	}
}

/**
 * Exception is thrown when a method call is invalid for current state of object.
 */
class InvalidOperationException extends SystemException
{
	static public function __construct($message = "", \Exception $previous = null)
	{
		parent::__construct($message, 160, '', 0, $previous);
	}
}

/**
 * Exception is thrown when object property is not valid.
 */
class ObjectPropertyException extends ArgumentException
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия./p&gt; </p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/objectpropertyexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($parameter = "", \Exception $previous = null)
	{
		parent::__construct("Object property \"".$parameter."\" not found.", $parameter, $previous);
	}
}

/**
 * Exception is thrown when the object can't be constructed.
 */
class ObjectException extends SystemException
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/objectexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($message = "", \Exception $previous = null)
	{
		parent::__construct($message, 500, '', 0, $previous);
	}
}

/**
 * Exception is thrown when an object is not present.
 */
class ObjectNotFoundException extends SystemException
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия.</p> <p> </p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/objectnotfoundexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($message = "", \Exception $previous = null)
	{
		parent::__construct($message, 510, '', 0, $previous);
	}
}
