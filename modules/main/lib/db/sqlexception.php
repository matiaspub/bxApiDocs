<?php
namespace Bitrix\Main\DB;

/**
 * Exception is thrown when database returns a error.
 */
class SqlException
	extends Exception
{
	static public function __construct($message = "", $databaseMessage = "", \Exception $previous = null)
	{
		parent::__construct($message, $databaseMessage, $previous);
	}
}

class SqlQueryException
	extends SqlException
{
	protected $query = "";

	public function __construct($message = "", $databaseMessage = "", $query = "", \Exception $previous = null)
	{
		parent::__construct($message, $databaseMessage, $previous);
		$this->query = $query;
	}

	public function getQuery()
	{
		return $this->query;
	}
}
