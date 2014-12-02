<?php
namespace Bitrix\Main\DB;

/**
 * Class Exception is used for all exceptions thrown in database.
 *
 * @see \Bitrix\Main\DB\Exception::__construct
 * @package Bitrix\Main\DB
 */
class Exception extends \Bitrix\Main\SystemException
{
	/** @var string */
	protected $databaseMessage;

	/**
	 * @param string $message Application message.
	 * @param string $databaseMessage Database reason.
	 * @param \Exception $previous The previous exception used for the exception chaining.
	 */
	public function __construct($message = "", $databaseMessage = "", \Exception $previous = null)
	{
		if (($message != "") && ($databaseMessage != ""))
			$message .= ": ".$databaseMessage;
		elseif (($message == "") && ($databaseMessage != ""))
			$message = $databaseMessage;

		$this->databaseMessage = $databaseMessage;

		parent::__construct($message, 400, '', 0, $previous);
	}

	/**
	 * Returns database specific message provided to the constructor.
	 *
	 * @return string
	 */
	public function getDatabaseMessage()
	{
		return $this->databaseMessage;
	}
}
