<?php
namespace Bitrix\Crm\Import;
use Bitrix\Main;
class YahooCsvFileImport extends CsvFileImport
{
	protected $headerMap = null;
	protected static $PHONE_TYPES = array('Home', 'Work', 'Pager', 'Fax', 'Mobile', 'Other');
	protected static $WEB_SITE_TYPES = array('Personal', 'Business');
	protected static $ADDRESS_TYPES = array('Work', 'Home');
	protected static $IM_TYPES = array('Skype', 'ICQ', 'MSN', 'Jabber');

	public function __construct()
	{
	}
	public function getHeaderMap()
	{
		return $this->headerMap;
	}
	public function setHeaderMap(array $headerMap)
	{
		$this->headerMap = $headerMap;
	}
	public function getDefaultEncoding()
	{
		return 'UTF-8';
	}
	public function getDefaultSeparator()
	{
		return ',';
	}
	public function checkHeaders(array &$messages)
	{
		IncludeModuleLangFile(__FILE__);

		$map = $this->headerMap !== null ? $this->headerMap : array();
		if($map === null)
		{
			throw new Main\SystemException("Invalid operation. HeaderMap is not assigned.");
		}

		$hasName = isset($map['First'])
			|| isset($map['Last']);

		if(!$hasName)
		{
			$messages[] = GetMessage(
				'CRM_IMPORT_YAHOO_ERROR_FIELDS_NOT_FOUND',
				array('#FIELD_LIST#' => "'First', 'Last'")
			);
		}

		$hasEmail = isset($map['Email'])
			|| isset($map['Alternate Email 1']);

		$hasPhone = isset($map['Home'])
			|| isset($map['Work'])
			|| isset($map['Mobile'])
			|| isset($map['Other']);

		if(!$hasName && !$hasEmail && !$hasPhone)
		{
			$messages[] = GetMessage('CRM_IMPORT_YAHOO_REQUIREMENTS');
		}

		return $hasName || $hasEmail || $hasPhone;
	}
	public function prepareContact(&$data)
	{
		$map = $this->headerMap;
		if($map === null)
		{
			throw new Main\SystemException("Invalid operation. HeaderMap is not assigned.");
		}

		$result = array();

		$this->tryToMapField('First', 'NAME', $data, $result, $map, true);
		$this->tryToMapField('Middle', 'SECOND_NAME', $data, $result, $map, true);
		$this->tryToMapField('Last', 'LAST_NAME', $data, $result, $map, true);
		$this->tryToMapField('Title', 'POST', $data, $result, $map, true);
		$this->tryToMapField('Company', 'COMPANY_TITLE', $data, $result, $map, true);
		$this->tryToMapField('Comments', 'COMMENTS', $data, $result, $map, true);

		$emailInfos = $this->getEmails($data);
		foreach($emailInfos as &$emailInfo)
		{
			$valueType = $emailInfo['VALUE_TYPE'] === 'P' ? 'WORK' : 'OTHER';
			$this->addMultifieldValue('EMAIL', $valueType, $emailInfo['VALUE'], $result);
		}
		unset($emailInfo);

		$phoneInfos = $this->getPhones($data);
		foreach($phoneInfos as &$phoneInfo)
		{
			$valueType = strtoupper($phoneInfo['VALUE_TYPE']);
			$result["PHONE_{$valueType}"] = $phoneInfo['VALUE'];
		}
		unset($phoneInfo);

		$websiteInfos = $this->getWebsites($data);
		foreach($websiteInfos as &$websiteInfo)
		{
			$valueType = strtoupper($websiteInfo['VALUE_TYPE']);
			if($valueType === 'PERSONAL')
			{
				$valueType = 'HOME';
			}
			elseif($valueType === 'BUSINESS')
			{
				$valueType = 'WORK';
			}
			else
			{
				$valueType = 'WORK';
			}
			$this->addMultifieldValue('WEB', $valueType, $websiteInfo['VALUE'], $result);
		}
		unset($websiteInfo);

		$imInfos = $this->getInstantMessengers($data);
		foreach($imInfos as $imInfo)
		{
			$valueType = strtoupper($imInfo['VALUE_TYPE']);
			$this->addMultifieldValue('IM', $valueType, $imInfo['VALUE'], $result);
		}
		unset($imInfo);

		$addressInfos = $this->getAddresses($data);
		if(isset($addressInfos['Work']))
		{
			$result['ADDRESS'] = $this->formatAddress($addressInfos['Work']);
		}
		elseif(isset($addressInfos['Home']))
		{
			$result['ADDRESS'] = $this->formatAddress($addressInfos['Home']);
		}

		return $result;
	}
	public function getEmails(&$data)
	{
		$map = $this->headerMap;
		if($map === null)
		{
			throw new Main\SystemException("Invalid operation. HeaderMap is not assigned.");
		}

		$result = array();
		$value = '';
		if($this->tryToGetValue('Email', $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'P',
				'VALUE' => $value
			);
		}

		$i = 1;
		$valueKey = "Alternate Email {$i}";
		while($this->tryToGetValue($valueKey, $data, $value, $map, true))
		{
			if($value !== '')
			{
				$result[] = array(
					'VALUE_TYPE' => 'A',
					'VALUE' => $value
				);
			}

			$i++;
			$valueKey = "Alternate Email {$i}";
		}
		return $result;
	}
	public function getPhones(&$data)
	{
		$map = $this->headerMap;
		if($map === null)
		{
			throw new Main\SystemException("Invalid operation. HeaderMap is not assigned.");
		}

		$result = array();
		$value = '';
		foreach(self::$PHONE_TYPES as $type)
		{
			if($this->tryToGetValue($type, $data, $value, $map, true) && $value !== '')
			{
				$result[] = array(
					'VALUE_TYPE' => $type,
					'VALUE' => $value
				);
			}
		}
		return $result;
	}
	public function getWebsites(&$data)
	{
		$map = $this->headerMap;
		$result = array();

		$value = '';
		foreach(self::$WEB_SITE_TYPES as $type)
		{
			if($this->tryToGetValue("{$type} Website", $data, $value, $map, true) && $value !== '')
			{
				$result[] = array(
					'VALUE_TYPE' => $type,
					'VALUE' => $value
				);
			}
		}
		return $result;
	}
	public function getAddresses(&$data)
	{
		$map = $this->headerMap;
		if($map === null)
		{
			throw new Main\SystemException("Invalid operation. HeaderMap is not assigned.");
		}

		$result = array();
		foreach(self::$ADDRESS_TYPES as $type)
		{
			$info = $this->getAddress(
				$data,
				$map,
				array(
					'STREET' => "{$type} Address",
					'CITY' => "{$type} City",
					'STATE' => "{$type} State",
					'COUNTRY' => "{$type} Country",
					'POSTAL_CODE' => "{$type} ZIP"
				)
			);

			if(!empty($info))
			{
				$result[$type] = &$info;
			}
			unset($info);
		}
		return $result;
	}
	public function getInstantMessengers(&$data)
	{
		$map = $this->headerMap;
		if($map === null)
		{
			throw new Main\SystemException("Invalid operation. HeaderMap is not assigned.");
		}

		$result = array();
		$value = '';
		foreach(self::$IM_TYPES as $type)
		{
			if($this->tryToGetValue("{$type} ID", $data, $value, $map, true) && $value !== '')
			{
				$result[] = array(
					'VALUE_TYPE' => $type,
					'VALUE' => $value
				);
			}
		}
		return $result;
	}
}