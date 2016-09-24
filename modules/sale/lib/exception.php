<?php
namespace Bitrix\Sale;
use Bitrix\Main\SystemException;

class UserMessageException extends SystemException
{
	static public function __construct($message = "", \Exception $previous = null)
	{
		parent::__construct($message, 0, '', 0, $previous);
	}
}