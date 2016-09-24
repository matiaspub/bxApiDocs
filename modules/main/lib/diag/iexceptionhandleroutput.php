<?php
namespace Bitrix\Main\Diag;

interface IExceptionHandlerOutput
{
	/**
	 * @param \Error|\Exception $exception
	 * @param bool $debug
	 */
	static public function renderExceptionMessage($exception, $debug = false);
}
