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

	/**
	 * Creates new exception object.
	 *
	 * @param string $message
	 * @param string $locMessageKey
	 * @param int $code
	 * @param string $file
	 * @param int $line
	 * @param \Exception $previous
	 */
	public function __construct($message = '', $locMessageKey = '', $code = 0, $file = '', $line = 0, \Exception $previous = null)
	{
		$this->locMessage = $locMessageKey;
		parent::__construct($message, $code, $file, $line, $previous);
	}

	/**
	 * @return string
	 */
	public function getLocMessage()
	{
		if (!$this->locMessage)
			return '';

		return Loc::getMessage($this->locMessage);
	}
}