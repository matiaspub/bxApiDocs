<?php
namespace Bitrix\Scale;

use \Bitrix\Main\SystemException;

/**
 * Exception is thrown when we can't comunicate with the slave server.
 */
class ServerBxInfoException extends SystemException
{
	protected $hostname;

	public function __construct($message = "", $hostname = "", \Exception $previous = null)
	{
		parent::__construct($message, 0, '', 0, $previous);
		$this->hostname= $hostname;
	}

	public function getHostname()
	{
		return $this->hostname;
	}
}

/**
 * Class NeedMoreUserInfoException
 * @package Bitrix\Scale
 * If we need more info from user to execute action
 */
class NeedMoreUserInfoException extends SystemException
{
	protected $actionParams;

	public function __construct($message = "", $actionParams = array(), \Exception $previous = null)
	{
		parent::__construct($message, 0, '', 0, $previous);
		$this->actionParams= $actionParams;
	}

	public function getActionParams()
	{
		return $this->actionParams;
	}
}
