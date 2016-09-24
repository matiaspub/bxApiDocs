<?php
namespace Bitrix\Crm\Import;
use Bitrix\Main;
abstract class CsvFileImport
{
	protected static $ADDRESS_FIELD_NAMES = array(
		'STREET',
		'CITY',
		'STATE',
		'COUNTRY',
		'POSTAL_CODE'
	);

	abstract public function getDefaultEncoding();
	abstract public function getDefaultSeparator();

	abstract public function checkHeaders(array &$messages);
	abstract public function prepareContact(&$data);

	protected function tryToMapField($srcName, $dstName, &$srcData, &$dstData, &$map, $htmldecode = false)
	{
		if(!isset($map[$srcName]))
		{
			return false;
		}

		$k = $map[$srcName];
		$dstData[$dstName] = isset($srcData[$k]) ? $srcData[$k] : '';
		if($htmldecode)
		{
			$dstData[$dstName] = htmlspecialcharsback($dstData[$dstName]);
		}
		return true;
	}
	protected function tryToGetValue($srcName, &$srcData, &$value, &$map, $htmldecode = false)
	{
		if($value !== '')
		{
			$value = '';
		}

		if(!isset($map[$srcName]))
		{
			return false;
		}

		$k = $map[$srcName];
		$value = isset($srcData[$k]) ? $srcData[$k] : '';
		if($htmldecode)
		{
			$value = htmlspecialcharsback($value);
		}
		return true;
	}
	protected function addMultifieldValue($type, $valueType, $value, array &$dstData)
	{
		if(!(is_string($value) && $value !== ''))
		{
			return;
		}

		$key = "{$type}_{$valueType}";
		if(isset($dstData[$key]) && $dstData[$key] !== '')
		{
			$dstData[$key] .= ',';
			$dstData[$key] .= $value;
		}
		else
		{
			$dstData[$key] = $value;
		}
	}
	public function getAddress(array &$data, array &$headerMap, array $aliasMap)
	{
		$result = array();

		foreach(self::$ADDRESS_FIELD_NAMES as $name)
		{
			$this->tryToMapField((isset($aliasMap[$name]) ? $aliasMap[$name] : $name), $name, $data, $result, $headerMap, true);
		}

		return $result;
	}
	public function formatAddress(array &$fields)
	{
		$parts = array();

		if(isset($fields['STREET']) && $fields['STREET'] !== '')
		{
			$parts[] = $fields['STREET'];
		}

		if(isset($fields['CITY']) && $fields['CITY'] !== '')
		{
			$parts[] = $fields['CITY'];
		}

		if(isset($fields['STATE']) && $fields['STATE'] !== '')
		{
			$parts[] = $fields['STATE'];
		}

		if(isset($fields['COUNTRY']) && $fields['COUNTRY'] !== '')
		{
			$parts[] = $fields['COUNTRY'];
		}

		if(isset($fields['POSTAL_CODE']) && $fields['POSTAL_CODE'] !== '')
		{
			$parts[] = $fields['POSTAL_CODE'];
		}

		return !empty($parts) ? implode(', ', $parts) : '';
	}
	public function getHeaderLanguage()
	{
		return '';
	}
}