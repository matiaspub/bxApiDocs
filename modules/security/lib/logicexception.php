<?php
/**
* Bitrix Framework
* @package bitrix
* @subpackage security
* @copyright 2001-2013 Bitrix
*/

namespace Bitrix\Security;


use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

/**
 * Class LogicException
 * @since 14.0.6
 * @package Bitrix\Security
 */
class LogicException
	extends Main\SystemException
{
	protected $locMessage = null;

	/**
	 * Creates new logic exception object.
	 *
	 * @param string $message The Exception message to throw.
	 * @param string $locMessageKey The Exception message localization key. Helpful for output message directly to user.
	 * @param int $code The Exception code.
	 * @param string $file The filename where the exception is thrown.
	 * @param int $line The line number where the exception is thrown.
	 * @param \Exception $previous The previous exception used for the exception chaining.
	 */
	
	/**
	* <p>Нестатический метод создает новое исключение.</p>
	*
	*
	* @param string $message = '' Отправляемое сообщение
	*
	* @param string $locMessageKey = '' Ключ локализации сообщения. Полезен для вывода сообщения
	* напрямую пользователю.
	*
	* @param integer $code  Код исключения.
	*
	* @param string $file = '' Имя файла для, которого создается исключение.
	*
	* @param integer $line  Имя строки, для которой создается исключение.
	*
	* @param Exception $previous = null Предыдущее исключение. Используется учета последовательности
	* исключений.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/logicexception/__construct.php
	* @author Bitrix
	*/
	public function __construct($message = '', $locMessageKey = '', $code = 0, $file = '', $line = 0, \Exception $previous = null)
	{
		$this->locMessage = $locMessageKey;
		parent::__construct($message, $code, $file, $line, $previous);
	}

	/**
	 * Returns localized message
	 *
	 * @return string
	 */
	public function getLocMessage()
	{
		if (!$this->locMessage)
			return '';

		return Loc::getMessage($this->locMessage);
	}
}
