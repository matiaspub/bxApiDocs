<?php
namespace Bitrix\Main\Security;

class SecurityException
	extends \Bitrix\Main\SystemException
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия. Его сообщение принимает на вход только сообщение и код.</p>
	*
	*
	* @param string $message = "" Сообщение исключения
	*
	* @param string $code = 0 Код, который сгенерировал исключение.
	*
	* @param Exception $previous = null 
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/securityexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($message = "", $code = 0, \Exception $previous = null)
	{
		parent::__construct($message, $code, '', '', $previous);
	}
}
