<?php
namespace Bitrix\Main\IO;

use \Bitrix\Main\Localization\Loc;

//Loc::loadMessages(__FILE__);

class FileNotFoundException
	extends IoException
{
	static public function __construct($path, \Exception $previous = null)
	{
		/*$message = Loc::getMessage(
			"file_not_found_exception_message",
			array("#PATH#" => $path)
		);*/
		$message = sprintf("Path '%s' is not found", $path);
		parent::__construct($message, $path, $previous);
	}
}
