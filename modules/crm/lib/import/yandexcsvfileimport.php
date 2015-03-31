<?php
namespace Bitrix\Crm\Import;
use Bitrix\Main;
class YandexCsvFileImport extends OutlookCsvFileImport
{
	public function getDefaultEncoding()
	{
		return $this->headerLanguage === 'ru' ? 'Windows-1251' : 'UTF-8';
	}
	public function getDefaultSeparator()
	{
		return ',';
	}
}