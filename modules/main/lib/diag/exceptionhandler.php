<?php
namespace Bitrix\Main\Diag;

use Bitrix\Main;

class ExceptionHandler
{
	private $debug = false;

	private $handledErrorsTypes;
	private $exceptionErrorsTypes;

	private $catchOverflowMemory = false;
	private $memoryReserveLimit = 65536;
	private $memoryReserve;

	private $ignoreSilence = false;

	private $assertionThrowsException = true;
	private $assertionErrorType = E_USER_ERROR;

	/**
	 * @var ExceptionHandlerLog
	 */
	private $handlerLog = null;
	private $handlerLogCreator = null;

	/**
	 * @var IExceptionHandlerOutput
	 */
	private $handlerOutput = null;
	private $handlerOutputCreator = null;

	private $isInitialized = false;

	/**
	 * ExceptionHandler constructor.
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести какие-то действия, при создании объекта.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/__construct.php
	* @author Bitrix
	*/
	public function __construct()
	{
		$this->handledErrorsTypes = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
		$this->exceptionErrorsTypes = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
	}

	/**
	 * Sets debug mode.
	 * Should be used for development install.
	 *
	 * @param boolean $debug If true errors will be displayed in html output. If false most errors will be suppressed.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает режим отладки.</p> <p>Следует использовать при разработке.</p>
	*
	*
	* @param boolean $debug  Если <i>true</i>, то ошибка будет отображена пользователю. Если <i>false</i>
	* большинство ошибок не будет выводиться.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/setdebugmode.php
	* @author Bitrix
	*/
	public function setDebugMode($debug)
	{
		$this->debug = $debug;
	}

	/**
	 * Whenever to try catch and report memory overflows errors or not.
	 *
	 * @param boolean $catchOverflowMemory If true memory overflow errors will be handled.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает будут ли выводиться ошибки переполнения памяти.</p>
	*
	*
	* @param boolean $catchOverflowMemory  Если <i>true</i>, то будут выводиться ошибки переполнения памяти.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/setoverflowmemorycatching.php
	* @author Bitrix
	*/
	public function setOverflowMemoryCatching($catchOverflowMemory)
	{
		$this->catchOverflowMemory = $catchOverflowMemory;
	}

	/**
	 * Sets error types to be handled.
	 *
	 * @param integer $handledErrorsTypes Bitmask of error types.
	 *
	 * @return void
	 * @see http://php.net/manual/en/errorfunc.constants.php
	 */
	
	/**
	* <p>Нестатический метод устанавливает типы ошибок которые будут обработаны.</p>
	*
	*
	* @param integer $handledErrorsTypes  Битовая маска типов ошибок.
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://php.net/manual/en/errorfunc.constants.php" >errorfunc.constants</a></li> </ul><a
	* name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/sethandlederrorstypes.php
	* @author Bitrix
	*/
	public function setHandledErrorsTypes($handledErrorsTypes)
	{
		$this->handledErrorsTypes = $handledErrorsTypes;
	}

	/**
	 * Sets assertion types to be handled.
	 *
	 * @param integer $assertionErrorType Bitmask of assertion types.
	 *
	 * @return void
	 * @see http://php.net/manual/en/errorfunc.constants.php
	 */
	
	/**
	* <p>Нестатический метод устанавливает разрешённые типы ошибок для обработки.</p>
	*
	*
	* @param integer $assertionErrorType  Битовые маски разрешаемых типов.
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="http://php.net/manual/en/errorfunc.constants.php%22" >errorfunc.constants</a></li> </ul><a
	* name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/setassertionerrortype.php
	* @author Bitrix
	*/
	public function setAssertionErrorType($assertionErrorType)
	{
		$this->assertionErrorType = $assertionErrorType;
	}

	/**
	 * Whenever to throw an exception on assertion or not.
	 *
	 * @param boolean $assertionThrowsException If true an assertion will throw exception.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает будет ли выбрасываться исключение утверждением или нет.</p>
	*
	*
	* @param boolean $assertionThrowsException  Если <i>true</i> утверждение будет выбрасывать исключение.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/setassertionthrowsexception.php
	* @author Bitrix
	*/
	public function setAssertionThrowsException($assertionThrowsException)
	{
		$this->assertionThrowsException = $assertionThrowsException;
	}

	/**
	 * Sets which errors will raise an exception.
	 *
	 * @param integer $errorTypesException Bitmask of error types.
	 *
	 * @return void
	 * @see http://php.net/manual/en/errorfunc.constants.php
	 */
	
	/**
	* <p>Нестатический метод устанавливает какие ошибки будут выброшены исключением.</p>
	*
	*
	* @param integer $errorTypesException  Битовая маска типов ошибок.
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a href="" >errorfunc.constants</a></li> </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/setexceptionerrorstypes.php
	* @author Bitrix
	*/
	public function setExceptionErrorsTypes($errorTypesException)
	{
		$this->exceptionErrorsTypes = $errorTypesException;
	}

	/**
	 * Whenever to ignore error_reporting() == 0 or not.
	 *
	 * @param boolean $ignoreSilence If true then error_reporting()==0 will be ignored.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает игнорировать ли ошибку когда <code>error_reporting() == 0</code>.</p>
	*
	*
	* @param boolean $ignoreSilence  Если <i>true</i>, то error_reporting()==0 будет проигнорирован.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/setignoresilence.php
	* @author Bitrix
	*/
	public function setIgnoreSilence($ignoreSilence)
	{
		$this->ignoreSilence = $ignoreSilence;
	}

	/**
	 * Sets logger object to use for log writing.
	 *
	 * @param \Bitrix\Main\Diag\ExceptionHandlerLog $handlerLog Logger object.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает какой  объект регистратора использовать для записи.</p>
	*
	*
	* @param mixed $Bitrix  Объект регистратора
	*
	* @param Bitri $Main  
	*
	* @param Mai $Diag  
	*
	* @param ExceptionHandlerLog $handlerLog = null 
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/sethandlerlog.php
	* @author Bitrix
	*/
	public function setHandlerLog(\Bitrix\Main\Diag\ExceptionHandlerLog $handlerLog = null)
	{
		$this->handlerLog = $handlerLog;
	}

	/**
	 * Sets an object used for error message display to user.
	 *
	 * @param \Bitrix\Main\Diag\IExceptionHandlerOutput $handlerOutput Object will display errors to user.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает объект используемый для показа сообщения об ошибке для пользователя.</p>
	*
	*
	* @param mixed $Bitrix  Объект, который будет выводить ошибки пользователю.
	*
	* @param Bitri $Main  
	*
	* @param Mai $Diag  
	*
	* @param IExceptionHandlerOutput $handlerOutput  
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/sethandleroutput.php
	* @author Bitrix
	*/
	public function setHandlerOutput(\Bitrix\Main\Diag\IExceptionHandlerOutput $handlerOutput)
	{
		$this->handlerOutput = $handlerOutput;
	}

	/**
	 * Adjusts PHP for error handling.
	 *
	 * @return void
	 */
	protected function initializeEnvironment()
	{
		if ($this->debug)
		{
			error_reporting($this->handledErrorsTypes);
			@ini_set('display_errors', 'On');
			@ini_set('display_startup_errors', 'On');
			@ini_set('report_memleaks', 'On');
		}
		else
		{
			error_reporting(E_ERROR | E_PARSE);
		}
	}

	/**
	 * Returns an object used for error message display to user.
	 *
	 * @return IExceptionHandlerOutput|null
	 */
	protected function getHandlerOutput()
	{
		if ($this->handlerOutput === null)
		{
			$h = $this->handlerOutputCreator;
			if (is_callable($h))
				$this->handlerOutput = call_user_func_array($h, array());
		}

		return $this->handlerOutput;
	}

	/**
	 * Returns an object for error message writing to log.
	 *
	 * @return ExceptionHandlerLog|null
	 */
	protected function getHandlerLog()
	{
		if ($this->handlerLog === null)
		{
			$h = $this->handlerLogCreator;
			if (is_callable($h))
				$this->handlerLog = call_user_func_array($h, array());
		}

		return $this->handlerLog;
	}

	/**
	 * Initializes error handling.
	 * Must be called after the object creation.
	 *
	 * @param callable $exceptionHandlerOutputCreator Function to return an object for error message formatting.
	 * @param callable|null $exceptionHandlerLogCreator Function to return an object for log writing.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод инициализирует обработку ошибок.</p> <p>Должен быть вызван после создания объекта.</p>
	*
	*
	* @param callable $exceptionHandlerOutputCreator  Функция для возврата объекта для форматирования сообщения об
	* ошибке.
	*
	* @param callable $callable  Функция для возврата объекта для записи в лог.
	*
	* @param null $exceptionHandlerLogCreator = null 
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/initialize.php
	* @author Bitrix
	*/
	public function initialize($exceptionHandlerOutputCreator, $exceptionHandlerLogCreator = null)
	{
		if ($this->isInitialized)
			return;

		$this->initializeEnvironment();

		$this->handlerOutputCreator = $exceptionHandlerOutputCreator;
		$this->handlerLogCreator = $exceptionHandlerLogCreator;

		if ($this->catchOverflowMemory)
		{
			$this->memoryReserve = str_repeat('b', $this->memoryReserveLimit);
		}

		set_error_handler(array($this, "handleError"), $this->handledErrorsTypes);
		set_exception_handler(array($this, "handleException"));
		register_shutdown_function(array($this, "handleFatalError"));

		if ($this->debug)
		{
			assert_options(ASSERT_ACTIVE, 1);
			assert_options(ASSERT_WARNING, 0);
			assert_options(ASSERT_BAIL, 0);
			assert_options(ASSERT_QUIET_EVAL, 0);
			assert_options(ASSERT_CALLBACK, array($this, "handleAssertion"));
		}
		else
		{
			assert_options(ASSERT_ACTIVE, 0);
		}

		$this->isInitialized = true;
	}

	/**
	 * Writes exception information into log, displays it to user and terminates with die().
	 *
	 * @param \Exception|\Error $exception Exception object.
	 *
	 * @return void
	 * @see \Bitrix\Main\Diag\ExceptionHandler::writeToLog
	 * @see \Bitrix\Main\Diag\ExceptionHandler::initialize
	 */
	
	/**
	* <p>Нестатический метод записывает информацию об исключении в лог, отображает её пользователю и удаляет посредством <code>die()</code>.</p>
	*
	*
	* @param mixed $Exception  Объект исключения.
	*
	* @param Error $exception  
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/writetolog.php">\Bitrix\Main\Diag\ExceptionHandler::writeToLog</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/initialize.php">\Bitrix\Main\Diag\ExceptionHandler::initialize</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/handleexception.php
	* @author Bitrix
	*/
	public function handleException($exception)
	{
		$this->writeToLog($exception, ExceptionHandlerLog::UNCAUGHT_EXCEPTION);
		$out = $this->getHandlerOutput();
		$out->renderExceptionMessage($exception, $this->debug);
		die();
	}

	/**
	 * Creates and exception object from its arguments.
	 * Throws it if $code matches exception mask or writes it into log.
	 *
	 * @param integer $code Error code.
	 * @param string $message Error message.
	 * @param string $file File where error has occurred.
	 * @param integer $line File line number where error has occurred.
	 *
	 * @return true
	 * @throws \ErrorException
	 * @see \Bitrix\Main\Diag\ExceptionHandler::setExceptionErrorsTypes
	 */
	
	/**
	* <p>Нестатический метод создаёт и исключает объект по его аргументам.</p> <p>Исключение выбрасывается если <code>$code</code> совпадает с маской исключения, или же записывается в лог.</p>
	*
	*
	* @param integer $code  Код ошибки
	*
	* @param string $message  Сообщение об ошибке
	*
	* @param string $file  Файл где обнаружилась ошибка.
	*
	* @param integer $line  НОмер строки в файле, где расположена ошибка.
	*
	* @return true 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/setexceptionerrorstypes.php">\Bitrix\Main\Diag\ExceptionHandler::setExceptionErrorsTypes</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/handleerror.php
	* @author Bitrix
	*/
	public function handleError($code, $message, $file, $line)
	{
		$exception = new \ErrorException($message, 0, $code, $file, $line);

		if ((error_reporting() === 0) && !$this->ignoreSilence)
		{
			return true;
		}

		if ($code & $this->exceptionErrorsTypes)
		{
			throw $exception;
		}
		else
		{
			$this->writeToLog($exception, ExceptionHandlerLog::LOW_PRIORITY_ERROR);
			return true;
		}
	}

	/**
	 * Creates and exception object from its arguments.
	 * Throws it if assertion set to raise exception (which is by default) or writes it to log.
	 *
	 * @param string $file File where error has occurred.
	 * @param integer $line File line number where error has occurred.
	 * @param string $message Error message.
	 *
	 * @return void
	 * @throws \ErrorException
	 * @see \Bitrix\Main\Diag\ExceptionHandler::setAssertionThrowsException
	 */
	
	/**
	* <p>Нестатический метод создаёт и исключает объект по его аргументам.</p> <p>Выбрасывает исключение если утверждение установлено по умолчанию, или же производит запись в лог.</p>
	*
	*
	* @param string $file  Файл, где обнаружена ошибка.
	*
	* @param integer $line  Номер строки файла, где обнаружена ошибка.
	*
	* @param string $message  Сообщение об ошибке.
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/setassertionthrowsexception.php">\Bitrix\Main\Diag\ExceptionHandler::setAssertionThrowsException</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/handleassertion.php
	* @author Bitrix
	*/
	public function handleAssertion($file, $line, $message)
	{
		$exception = new \ErrorException($message, 0, $this->assertionErrorType, $file, $line);

		if ($this->assertionThrowsException)
		{
			throw $exception;
		}
		else
		{
			$this->writeToLog($exception, ExceptionHandlerLog::ASSERTION);
			return;
		}
	}

	/**
	 * Gets error information from error_get_last() function.
	 * Checks if type for certain error types and writes it to log.
	 *
	 * @return void
	 * @see error_get_last
	 * @see \Bitrix\Main\Diag\ExceptionHandler::setHandledErrorsTypes
	 */
	
	/**
	* <p>Нестатический метод выводит информацию об ошибке из функции <code>error_get_last()</code>.</p> <p>Проверяет и заносит в лог тип ошибок.</p> <p>Без параметров</p>
	*
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><code>\Bitrix\Main\Diag\error_get_last</code></li> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/sethandlederrorstypes.php">\Bitrix\Main\Diag\ExceptionHandler::setHandledErrorsTypes</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/handlefatalerror.php
	* @author Bitrix
	*/
	public function handleFatalError()
	{
		unset($this->memoryReserve);
		if ($error = error_get_last())
		{
			if (($error['type'] & (E_ERROR | E_USER_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR)))
			{
				if(($error['type'] & $this->handledErrorsTypes))
				{
					$exception = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
					$this->writeToLog($exception, ExceptionHandlerLog::FATAL);
				}
			}
		}
	}

	/**
	 * Writes an exception information to log.
	 *
	 * @param \Exception $exception Exception object.
	 * @param integer|null $logType See ExceptionHandlerLog class constants.
	 *
	 * @return void
	 * @see \Bitrix\Main\Diag\ExceptionHandler::initialize
	 */
	
	/**
	* <p>Нестатический метод записывает информацию об исключении в лог файл.</p>
	*
	*
	* @param Exception $exception  Объект исключения.
	*
	* @param Exception $integer  Просмотреть константы классы ExceptionHandlerLog.
	*
	* @param null $logType = null 
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/initialize.php">\Bitrix\Main\Diag\ExceptionHandler::initialize</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/exceptionhandler/writetolog.php
	* @author Bitrix
	*/
	public function writeToLog($exception, $logType = null)
	{
		$log = $this->getHandlerLog();
		if ($log !== null)
			$log->write($exception, $logType);
	}
}
