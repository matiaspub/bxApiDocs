<?php
namespace Bitrix\Crm\Import;
use Bitrix\Main;
class MailruCsvFileImport extends OutlookCsvFileImport
{
	public function __construct()
	{
		//We have to enable compatibility mode for fix mistakes of Mail.Ru implementation
		$this->enableCompatibilityMode = true;
	}
	public function getDefaultEncoding()
	{
		return 'Windows-1251';
	}
	public function getDefaultSeparator()
	{
		return ',';
	}
}