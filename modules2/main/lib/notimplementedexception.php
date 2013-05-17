<?php
namespace Bitrix\Main;

/**
 * Exception is thrown when operation is not implemented but should be.
 */
class NotImplementedException
	extends SystemException
{
	static public function __construct($message = "", \Exception $previous = null)
	{
		parent::__construct($message, 140, '', '', $previous);
	}
}
