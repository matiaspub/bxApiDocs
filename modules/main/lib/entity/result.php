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
