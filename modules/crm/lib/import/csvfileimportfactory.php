<?php
namespace Bitrix\Crm\Import;
use Bitrix\Main;
class CsvFileImportFactory
{
	public static function createByTypeName($typeName, $params = array())
	{
		$typeName = strval($typeName);
		$typeID = CsvFileImportType::resolveID($typeName);

		if(!is_array($params))
		{
			$params = array();
		}

		$item = null;
		if($typeID === CsvFileImportType::GMAIL)
		{
			$item = new GMailCsvFileImport();
			if(isset($params['MAP']))
			{
				$item->setHeaderMap($params['MAP']);
			}
		}
		elseif($typeID === CsvFileImportType::LIVEMAIL)
		{
			$item = new LiveMailCsvFileImport();
			if(isset($params['MAP']))
			{
				$item->setHeaderMap($params['MAP']);
			}
		}
		elseif($typeID === CsvFileImportType::MAILRU)
		{
			$item = new MailruCsvFileImport();
			if(isset($params['MAP']))
			{
				$item->setHeaderMap($params['MAP']);
			}
		}
		elseif($typeID === CsvFileImportType::YAHOO)
		{
			$item = new YahooCsvFileImport();
			if(isset($params['MAP']))
			{
				$item->setHeaderMap($params['MAP']);
			}
		}
		elseif($typeID === CsvFileImportType::YANDEX)
		{
			$item = new YandexCsvFileImport();
			if(isset($params['MAP']))
			{
				$item->setHeaderMap($params['MAP']);
			}
			if(isset($params['LANG_ID']) && $params['LANG_ID'] !== '')
			{
				$item->setHeaderLanguage($params['LANG_ID']);
			}
		}
		elseif($typeID === CsvFileImportType::OUTLOOK)
		{
			$item = new OutlookCsvFileImport();
			if(isset($params['MAP']))
			{
				$item->setHeaderMap($params['MAP']);
			}
			if(isset($params['LANG_ID']) && $params['LANG_ID'] !== '')
			{
				$item->setHeaderLanguage($params['LANG_ID']);
			}
		}
		elseif($typeID === CsvFileImportType::OUTLOOK)
		{
			$item = new OutlookCsvFileImport();
			if(isset($params['MAP']))
			{
				$item->setHeaderMap($params['MAP']);
			}
			if(isset($params['LANG_ID']) && $params['LANG_ID'] !== '')
			{
				$item->setHeaderLanguage($params['LANG_ID']);
			}
		}
		return $item;
	}
}