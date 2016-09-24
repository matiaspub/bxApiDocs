<?php
namespace Bitrix\Crm\Import;
use Bitrix\Main;
class CsvFileImportType
{
	const UNDEFINED = 0;
	const GMAIL = 1;
	const LIVEMAIL = 2;
	const MAILRU = 3;
	const OUTLOOK = 4;
	const YANDEX = 5;
	const YAHOO = 6;

	const GMAIL_NAME = 'GMAIL';
	const LIVEMAIL_NAME = 'LIVEMAIL';
	const MAILRU_NAME = 'MAILRU';
	const OUTLOOK_NAME = 'OUTLOOK';
	const YAHOO_NAME = 'YAHOO';
	const YANDEX_NAME = 'YANDEX';

	public static function resolveID($name)
	{
		$name = strtoupper(strval($name));
		if($name === self::GMAIL_NAME)
		{
			return self::GMAIL;
		}
		if($name === self::LIVEMAIL_NAME)
		{
			return self::LIVEMAIL;
		}
		if($name === self::MAILRU_NAME)
		{
			return self::MAILRU;
		}
		elseif($name === self::OUTLOOK_NAME)
		{
			return self::OUTLOOK;
		}
		elseif($name === self::YANDEX_NAME)
		{
			return self::YANDEX;
		}
		elseif($name === self::YAHOO_NAME)
		{
			return self::YAHOO;
		}
		return self::UNDEFINED;
	}
}