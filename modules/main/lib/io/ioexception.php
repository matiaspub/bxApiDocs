<?php
namespace Bitrix\Main\IO;

/**
 * This exception is thrown when an I/O error occurs.
 */
class IoException extends \Bitrix\Main\SystemException
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
		parent::__construct($message, 120, '', 0, $previous);
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

class InvalidPathException extends IoException
{
	static public function __construct($path, \Exception $previous = null)
	{
		$message = sprintf("Path '%s' is invalid.", $path);
		parent::__construct($message, $path, $previous);
	}
}

class FileNotFoundException extends IoException
{
	static public function __construct($path, \Exception $previous = null)
	{
		$message = sprintf("Path '%s' is not found.", $path);
		parent::__construct($message, $path, $previous);
	}
}

class FileDeleteException extends IoException
{
	static public function __construct($path, \Exception $previous = null)
	{
		$message = sprintf("Error occurred during deleting file '%s'.", $path);
		parent::__construct($message, $path, $previous);
	}
}

class FileOpenException extends IoException
{
	static public function __construct($path, \Exception $previous = null)
	{
		$message = sprintf("Cannot open the file '%s'.", $path);
		parent::__construct($message, $path, $previous);
	}
}

class FileNotOpenedException extends IoException
{
	static public function __construct($path, \Exception $previous = null)
	{
		$message = sprintf("The file '%s' is not opened.", $path);
		parent::__construct($message, $path, $previous);
	}
}
