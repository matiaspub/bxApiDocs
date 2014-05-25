<?php
namespace Bitrix\Main\Diag;

use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class ExceptionHandlerOutput
	implements IExceptionHandlerOutput
{
	public static function renderExceptionMessage(\Exception $exception, $debug = false)
	{
		if ($debug)
		{
			echo ExceptionHandlerFormatter::format($exception, false);
		}
		else
		{
			$context = Main\Application::getInstance();
			if ($context)
				echo Main\Localization\Loc::getMessage("eho_render_exception_message");
			else
				echo "A error occurred during execution of this script. You can turn on extended error reporting in .settings.php file.";
		}
	}
}
