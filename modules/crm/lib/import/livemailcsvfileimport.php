<?php
namespace Bitrix\Crm\Import;
use Bitrix\Main;
class LiveMailCsvFileImport extends CsvFileImport
{
	protected $headerMap = null;
	protected static $WEB_SITE_TYPES = array('Personal', 'Business');
	protected static $ADDRESS_TYPES = array('Business', 'Home');

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
		return ';';
	}
	public function checkHeaders(array &$messages)
	{
		IncludeModuleLangFile(__FILE__);

		$map = $this->headerMap !== null ? $this->headerMap : array();
		if($map === null)
		{
			throw new Main\SystemException("Invalid operation. HeaderMap is not assigned.");
		}

		$hasName = isset($map['First Name'])
			|| isset($map['Last Name'])
			|| isset($map['Name']);

		if(!$hasName)
		{
			$messages[] = GetMessage(
				'CRM_IMPORT_LIVE_MAIL_ERROR_FIELDS_NOT_FOUND',
				array('#FIELD_LIST#' => "'First Name', 'Last Name', 'Name'")
			);
		}

		$hasEmail = isset($map['Email Address']);

		$hasPhone = isset($map['Home Phone'])
			|| isset($map['Mobile Phone'])
			|| isset($map['Business Phone']);

		if(!$hasName && !$hasEmail && !$hasPhone)
		{
			$messages[] = GetMessage('CRM_IMPORT_LIVE_MAIL_REQUIREMENTS');
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

		$this->tryToMapField('First Name', 'NAME', $data, $result, $map, true);
		$this->tryToMapField('Middle Name', 'SECOND_NAME', $data, $result, $map, true);
		$this->tryToMapField('Last Name', 'LAST_NAME', $data, $result, $map, true);
		$this->tryToMapField('Job Title', 'POST', $data, $result, $map, true);
		$this->tryToMapField('Company', 'COMPANY_TITLE', $data, $result, $map, true);
		$this->tryToMapField('Notes', 'COMMENTS', $data, $result, $map, true);

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
			$valueType = $phoneInfo['VALUE_TYPE'];
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
		if($this->tryToGetValue('Email Address', $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'P',
				'VALUE' => $value
			);
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
		if($this->tryToGetValue('Home Phone', $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'HOME',
				'VALUE' => $value
			);
		}
		if($this->tryToGetValue('Business Phone', $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'WORK',
				'VALUE' => $value
			);
		}
		if($this->tryToGetValue('Mobile Phone', $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'MOBILE',
				'VALUE' => $value
			);
		}
		if($this->tryToGetValue('Home Fax', $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'FAX',
				'VALUE' => $value
			);
		}
		if($this->tryToGetValue('Business Fax', $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'FAX',
				'VALUE' => $value
			);
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
			if($this->tryToGetValue("{$type} Web Page", $data, $value, $map, true) && $value !== '')
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
					'STREET' => "{$type} Street",
					'CITY' => "{$type} City",
					'STATE' => "{$type} State",
					'COUNTRY' => "{$type} Country/Region",
					'POSTAL_CODE' => "{$type} Postal Code"
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
}