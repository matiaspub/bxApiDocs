<?php
namespace Bitrix\Main\DB;

/**
 * The base class for all exceptions thrown in database.
 */
class DbException
	extends \Bitrix\Main\SystemException
{
	protected $databaseMessage;

	public function __construct($message = "", $databaseMessage = "", \Exception $previous = null)
	{
		if (($message != "") && ($databaseMessage != ""))
			$message .= ": ".$databaseMessage;
		elseif (($message == "") && ($databaseMessage != ""))
			$message = $databaseMessage;

		$this->databaseMessage = $databaseMessage;

		parent::__construct($message, 400, '', '', $previous);
	}

	public function getDatabaseMessage()
	{
		return $this->databaseMessage;
	}
}
