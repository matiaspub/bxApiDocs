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
	 * Creates new exception object for signing purposes.
	 *
	 * @param string $message Message.
	 * @param \Exception $previous Previous exception.
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия.</p>
	*
	*
	* @param string $message = "" Сообщение.
	*
	* @param Exception $previous = null Предыдущее исключение.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/badsignatureexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($message = "", \Exception $previous = null)
	{
		parent::__construct($message, 140, '', 0, $previous);
	}
}