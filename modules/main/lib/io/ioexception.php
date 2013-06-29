<?php
namespace Bitrix\Main\IO;

/**
 * This exception is thrown when an I/O error occurs.
 */
class IoException
	extends \Bitrix\Main\SystemException
{
	protected $path;

	/**
	 * Creates new exception object.
	 *
	 * @param string $message Exception message
	 * @param string $path Path that generated exception.
	 * @param \Exception $previous
	 */
	public function __construct($message = "", $path = "", \Exception $previous = null)
	{
		parent::__construct($message, 120, '', '', $previous);
		$this->path = $path;
	}

	/**
	 * Path that generated exception.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}
}
