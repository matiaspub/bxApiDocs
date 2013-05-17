<?php
namespace Bitrix\Main\IO;

//use \Bitrix\Main\Localization\Loc;

//Loc::loadMessages(__FILE__);

class InvalidPathException
	extends IoException
{
	static public function __construct($path, \Exception $previous = null)
	{
		/*$message = Loc::getMessage(
			"invalid_path_exception_message",
			array("#PATH#" => $path)
		);*/
		$message = sprintf("Path '%s' is invalid", $path);
		parent::__construct($message, $path, $previous);
	}
}
