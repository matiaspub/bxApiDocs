<?php
namespace Bitrix\Main;

/**
 * Base class for fatal exceptions
 */
class SystemException
	extends \Exception
{
	/**
	 * Creates new exception object.
	 *
	 * @param string $message
	 * @param int $code
	 * @param \Exception $previous
	 */
	public function __construct($message = "", $code = 0, $file = "", $line = "", \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);

		$this->file = $file;
		$this->line = $line;
	}
}
