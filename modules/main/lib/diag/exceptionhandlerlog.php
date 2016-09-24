<?php
namespace Bitrix\Main\Diag;

use Bitrix\Main;

abstract class ExceptionHandlerLog
{
	const UNCAUGHT_EXCEPTION = 0;
	const CAUGHT_EXCEPTION = 1;
	const IGNORED_ERROR = 2;
	const LOW_PRIORITY_ERROR = 3;
	const ASSERTION = 4;
	const FATAL = 5;

	public static function logTypeToString($logType)
	{
		switch ($logType)
		{
			case 0:
				return 'UNCAUGHT_EXCEPTION';
				break;
			case 1:
				return 'CAUGHT_EXCEPTION';
				break;
			case 2:
				return 'IGNORED_ERROR';
				break;
			case 3:
				return 'LOW_PRIORITY_ERROR';
				break;
			case 4:
				return 'ASSERTION';
				break;
			case 5:
				return 'FATAL';
				break;
			default:
				return 'UNKNOWN';
				break;
		}
	}

	abstract public function write($exception, $logType);

	abstract public function initialize(array $options);
}
