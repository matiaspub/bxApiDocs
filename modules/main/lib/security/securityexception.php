<?php
namespace Bitrix\Main\Security;

class SecurityException
	extends \Bitrix\Main\SystemException
{
	static public function __construct($message = "", $code = 0, \Exception $previous = null)
	{
		parent::__construct($message, $code, '', '', $previous);
	}
}
