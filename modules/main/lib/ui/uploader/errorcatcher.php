<?
namespace Bitrix\Main\UI\Uploader;

class ErrorCatcher
{
	static public function log($path, $errorText)
	{
		if (check_bitrix_sessid() &&
			is_string($path) &&
			is_string($errorText) &&
			\COption::GetOptionString("main", "uploaderLog", "N") == "Y")
		{
			trigger_error("Uploading error! Path: ".substr($path, 0, 100)."\n Text:".substr($errorText, 0, 500), E_USER_WARNING);
		}
	}
}