<?php
namespace Bitrix\Main\Security\Sign;

use Bitrix\Main\SystemException;

/**
 * Class BadSignatureException
 * @since 14.0.7
 * @package Bitrix\Main\Security\Sign
 */
class BadSignatureException
	extends SystemException
{
	/**
	 * @param string $message
	 * @param \Exception $previous
	 */
	static public function __construct($message = "", \Exception $previous = null)
	{
		parent::__construct($message, 140, '', 0, $previous);
	}
}