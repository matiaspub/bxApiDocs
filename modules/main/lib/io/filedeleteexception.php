<?php
namespace Bitrix\Main\IO;

use Bitrix\Main\Localization\Loc;

class FileDeleteException
	extends IoException
{
	static public function __construct($path, \Exception $previous = null)
	{
		$message = sprintf("Error occurred during deleting file '%s'", $path);
		parent::__construct($message, $path, $previous);
	}
}
