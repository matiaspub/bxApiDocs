<?php
namespace Bitrix\Main\DB;

/**
 * Exception is thrown when database returns a error.
 */
class SqlException
	extends DbException
{
	static public function __construct($message = "", $databaseMessage = "", \Exception $previous = null)
	{
		parent::__construct($message, $databaseMessage, $previous);
	}
}
