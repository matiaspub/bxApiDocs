<?php
class CCrmUtils
{
	private static $ENABLE_TRACING = false;
	public static function EnableTracing($enable)
	{
		self::$ENABLE_TRACING = $enable;
	}

	public static function Trace($id, $msg, $forced = false)
	{
		if(!$forced && !self::$ENABLE_TRACING)
		{
			return;
		}

		$file = fopen($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/log.txt', 'a');
		fwrite($file, "$id: $msg\n");
		fclose($file);
	}

	public static function Dump($obj)
	{
		echo '<pre>', mydump($obj), '</pre>';
	}
}
