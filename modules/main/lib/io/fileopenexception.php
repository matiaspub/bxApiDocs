<?php
namespace Bitrix\Main\IO;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class FileOpenException
	extends IoException
{
	static public function __construct($path, \Exception $previous = null)
	{
		$message = Loc::getMessage(
			"file_open_exception_message",
			array("#PATH#" => $path)
		);
		parent::__construct($message, $path, $previous);
	}
}
