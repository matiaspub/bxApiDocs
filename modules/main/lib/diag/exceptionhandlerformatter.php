<?php
namespace Bitrix\Main\Diag;

use Bitrix\Main;

class ExceptionHandlerFormatter
{
	const MAX_CHARS = 30;

	const SHOW_PARAMETERS = 1;

	const DELIMITER = '----------';

	/**
	 * @param \Error|\Exception $exception
	 * @param bool $htmlMode
	 * @param int $level
	 * @return string
	 */
	public static function format($exception, $htmlMode = false, $level = 0)
	{
		$result = '['.get_class($exception).'] ';

		if ($exception instanceof \ErrorException)
			$result .= static::severityToString($exception->getSeverity());

		$result .= "\n".static::getMessage($exception)."\n";

		if ($exception instanceof Main\DB\SqlQueryException)
			$result .= $exception->getQuery()."\n";

		$fileLink = static::getFileLink($exception->getFile(), $exception->getLine());
		$result .= $fileLink.(empty($fileLink) ? "" : "\n");

		if ($htmlMode)
			$result = Main\Text\HtmlFilter::encode($result);

		$prevArg = null;
		$trace = static::getTrace($exception);
		foreach ($trace as $traceNum => $traceInfo)
		{
			$traceLine = '#'.$traceNum.': ';
			if (array_key_exists('class', $traceInfo))
				$traceLine .= $traceInfo['class'].$traceInfo['type'];

			if (array_key_exists('function', $traceInfo))
			{
				$traceLine .= $traceInfo['function'];
				if(isset($traceInfo['args']))
				{
					$traceLine .= static::getArguments($traceInfo['args'], $level);
				}
			}

			if ($htmlMode)
				$traceLine = Main\Text\HtmlFilter::encode($traceLine);

			if (array_key_exists('file', $traceInfo))
				$traceLine .= "\n\t".static::getFileLink($traceInfo['file'], $traceInfo['line']);
			else
				$traceLine .= "\n\t".static::getFileLink(null, null);

			$result .= $traceLine. "\n";
		}

		if ($htmlMode)
			$result = '<pre>'.$result.'</pre>';
		else
			$result .= static::DELIMITER;

		return $result;
	}

	/**
	 * @param \Error|\Exception $exception
	 * @return array
	 */
	protected static function getTrace($exception)
	{
		$backtrace = $exception->getTrace();

		$exceptionHandlerClass = "Bitrix\\Main\\Diag\\ExceptionHandler";

		$result = array();
		foreach ($backtrace as $item)
		{
			if (array_key_exists('class', $item) && ($item['class'] == $exceptionHandlerClass))
				continue;

			$result[] = $item;
		}

		return $result;
	}

	/**
	 * @param \Error|\Exception $exception
	 * @return string
	 */
	protected static function getMessage($exception)
	{
		return $exception->getMessage().' ('.$exception->getCode().')';
	}

	public static function severityToString($severity)
	{
		switch ($severity)
		{
			case 1:
				return 'E_ERROR';
				break;
			case 2:
				return 'E_WARNING';
				break;
			case 4:
				return 'E_PARSE';
				break;
			case 8:
				return 'E_NOTICE';
				break;
			case 16:
				return 'E_CORE_ERROR';
				break;
			case 32:
				return 'E_CORE_WARNING';
				break;
			case 64:
				return 'E_COMPILE_ERROR';
				break;
			case 128:
				return 'E_COMPILE_WARNING';
				break;
			case 256:
				return 'E_USER_ERROR';
				break;
			case 512:
				return 'E_USER_WARNING';
				break;
			case 1024:
				return 'E_USER_NOTICE';
				break;
			case 2048:
				return 'E_STRICT';
				break;
			case 4096:
				return 'E_RECOVERABLE_ERROR';
				break;
			case 8192:
				return 'E_DEPRECATED';
				break;
			case 16384:
				return 'E_USER_DEPRECATED';
				break;
			case 30719:
				return 'E_ALL';
				break;
			default:
				return 'UNKNOWN';
				break;
		}
	}

	protected static function getArguments($args, $level)
	{
		if (!is_null($args))
		{
			$argsTmp = array();
			foreach ($args as $arg)
				$argsTmp[] = static::convertArgumentToString($arg, $level);

			return '(' . implode(', ', $argsTmp) . ')';
		}
		return '()';
	}

	protected static function convertArgumentToString($arg, $level)
	{
		if (($level & static::SHOW_PARAMETERS) === 0)
		{
			return gettype($arg);
		}

		$result = null;
		switch (gettype($arg))
		{
			case 'boolean':
				$result = $arg ? 'true' : 'false';
				break;
			case 'NULL':
				$result = 'null';
				break;
			case 'integer':
			case 'double':
			case 'float':
				$result = (string) $arg;
				break;
			case 'string':
				if (is_callable($arg, false, $callableName))
				{
					$result = 'fs:'.$callableName;
				}
				elseif (class_exists($arg, false))
				{
					$result = 'c:'.$arg;
				}
				elseif (interface_exists($arg, false))
				{
					$result = 'i:'.$arg;
				}
				else
				{
					if (strlen($arg) > static::MAX_CHARS)
						$result = '"'.substr($arg, 0, static::MAX_CHARS / 2).'...'.substr($arg, -static::MAX_CHARS / 2).'" ('.strlen($arg).')';
					else
						$result = '"'.$arg.'"';
				}
				break;
			case 'array':
				if (is_callable($arg, false, $callableName))
					$result = 'fa:'.$callableName;
				else
					$result = 'array('.count($arg).')';
				break;
			case 'object':
				$result = '['.get_class($arg).']';
				break;
			case 'resource':
				$result = 'r:'.get_resource_type($arg);
				break;
			default:
				$result = 'unknown type';
				break;
		}

		return str_replace("\n", '\n', $result);
	}

	protected static function getFileLink($file, $line)
	{
		if (!is_null($file) && !empty($file))
			return $file.':'.$line;

		return "";
	}
}
