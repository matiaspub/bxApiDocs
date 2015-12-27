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

	public function __construct()
	{
		$this->handledErrorsTypes = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
		$this->exceptionErrorsTypes = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
	}

	public function setDebugMode($debug)
	{
		$this->debug = $debug;
	}

	public function setOverflowMemoryCatching($catchOverflowMemory)
	{
		$this->catchOverflowMemory = $catchOverflowMemory;
	}

	public function setHandledErrorsTypes($handledErrorsTypes)
	{
		$this->handledErrorsTypes = $handledErrorsTypes;
	}

	public function setAssertionErrorType($assertionErrorType)
	{
		$this->assertionErrorType = $assertionErrorType;
	}

	public function setAssertionThrowsException($assertionThrowsException)
	{
		$this->assertionThrowsException = $assertionThrowsException;
	}

	public function setExceptionErrorsTypes($errorTypesException)
	{
		$this->exceptionErrorsTypes = $errorTypesException;
	}

	public function setIgnoreSilence($ignoreSilence)
	{
		$this->ignoreSilence = $ignoreSilence;
	}

	/**
	 * @param \Bitrix\Main\Diag\ExceptionHandlerLog $handlerLog
	 */
	public function setHandlerLog(ExceptionHandlerLog $handlerLog = null)
	{
		$this->handlerLog = $handlerLog;
	}

	/**
	 * @param \Bitrix\Main\Diag\IExceptionHandlerOutput $handlerOutput
	 */
	public function setHandlerOutput(IExceptionHandlerOutput $handlerOutput)
	{
		$this->handlerOutput = $handlerOutput;
	}

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

	public function handleException(\Exception $exception)
	{
		$this->writeToLog($exception, ExceptionHandlerLog::UNCAUGHT_EXCEPTION);
		$out = $this->getHandlerOutput();
		$out->renderExceptionMessage($exception, $this->debug);
		die();
	}

	public function handleError($code, $message, $file, $line)
	{
		$exception = new \ErrorException($message, 0, $code, $file, $line);

		if ((error_reporting() === 0) && !$this->ignoreSilence)
		{
			//$this->writeToLog($exception, ExceptionHandlerLog::IGNORED_ERROR);
			return true;
		}
		elseif (!($code & $this->exceptionErrorsTypes))
		{
			$this->writeToLog($exception, ExceptionHandlerLog::LOW_PRIORITY_ERROR);
			return true;
		}

		throw $exception;
	}

	public function handleAssertion($file, $line, $message)
	{
		$exception = new \ErrorException($message, 0, $this->assertionErrorType, $file, $line);

		if (!$this->assertionThrowsException)
		{
			$this->writeToLog($exception, ExceptionHandlerLog::ASSERTION);
			return;
		}

		throw $exception;
	}

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

	protected function writeToLog($exception, $logType = null)
	{
		$log = $this->getHandlerLog();
		if ($log !== null)
			$log->write($exception, $logType);
	}
}
