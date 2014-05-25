<?php
namespace Bitrix\Main\IO;

class InvalidPathException
	extends IoException
{
	static public function __construct($path, \Exception $previous = null)
	{
		$message = sprintf("Path '%s' is invalid", $path);
		parent::__construct($message, $path, $previous);
	}
}
