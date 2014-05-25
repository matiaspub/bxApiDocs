<?php
namespace Bitrix\Main\IO;

use Bitrix\Main\Localization\Loc;

class FileNotFoundException
	extends IoException
{
	static public function __construct($path, \Exception $previous = null)
	{
		$message = sprintf("Path '%s' is not found", $path);
		parent::__construct($message, $path, $previous);
	}
}
