<?php
namespace Bitrix\Main\Mail;

class StopException
	extends \Bitrix\Main\SystemException
{
	protected $isBufferCleaned = false;

	static public function __construct($message = "", $code = 0, \Exception $previous = null)
	{
		parent::__construct($message, $code, '', '', $previous);
	}
}
