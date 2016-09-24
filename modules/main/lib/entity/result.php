<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

class Result extends \Bitrix\Main\Result
{
	/** @var bool  */
	protected $wereErrorsChecked = false;

	static public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Returns result status
	 * Within the core and events should be called with internalCall flag
	 *
	 * @param bool $internalCall
	 *
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод возвращает статус результата. В ядре и событиях должен вызываться с флагом <code>internalCall</code>.</p>
	*
	*
	* @param boolean $internalCall = false 
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/result/issuccess.php
	* @author Bitrix
	*/
	public function isSuccess($internalCall = false)
	{
		if (!$internalCall && !$this->wereErrorsChecked)
		{
			$this->wereErrorsChecked = true;
		}

		return parent::isSuccess();
	}

	/**
	 * Returns an array of Error objects
	 *
	 * @return EntityError[]|FieldError[]
	 */
	
	/**
	* <p>Нестатический метод возвращает массив объектов <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/error/index.php">\Main\Error</a>.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/result/geterrors.php
	* @author Bitrix
	*/
	public function getErrors()
	{
		$this->wereErrorsChecked = true;

		return parent::getErrors();
	}

	/**
	 * Returns array of strings with error messages
	 *
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод возвращает массив строк с сообщениями об ошибках.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/result/geterrormessages.php
	* @author Bitrix
	*/
	public function getErrorMessages()
	{
		$this->wereErrorsChecked = true;

		return parent::getErrorMessages();
	}

	public function __destruct()
	{
		if (!$this->isSuccess && !$this->wereErrorsChecked)
		{
			// nobody interested in my errors :(
			// make a warning (usually it should be written in log)
			trigger_error(join('; ', $this->getErrorMessages()), E_USER_WARNING);
		}
	}
}
