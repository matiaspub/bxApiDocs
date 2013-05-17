<?php
namespace Bitrix\Main\IO;

use \Bitrix\Main\Localization\Loc;

//Loc::loadMessages(__FILE__);

class FileDeleteException
	extends IoException
{
	static public function __construct($path, \Exception $previous = null)
	{
		/*$message = Loc::getMessage(
			"file_delete_exception_message",
			array("#PATH#" => $path)
		);*/
		$message = sprintf("Error occurred during deleting file '%s'", $path);
		parent::__construct($message, $path, $previous);
	}
}
