<?php
namespace Bitrix\Main\Diag;

interface IExceptionHandlerOutput
{
	public static function renderExceptionMessage(\Exception $exception, $debug = false);
}
