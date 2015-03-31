<?php
namespace Bitrix\Crm\Import;
use Bitrix\Main;
class GMailCsvFileImport extends CsvFileImport
{
	protected $headerMap = null;

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
		return 'UTF-16';
	}
	public function getDefaultSeparator()
	{
		return ',';
	}
	public function checkHeaders(array &$messages)
	{
		IncludeModuleLangFile(__FILE__);

		$map = $this->headerMap;
		if($map === null)
		{
			throw new Main\SystemException("Invalid operation. HeaderMap is not assigned.");
		}

		$hasName = isset($map['Given Name'])
			|| isset($map['Family Name']);

		if(!$hasName)
		{
			$messages[] = GetMessage(
				'CRM_IMPORT_GMAIL_ERROR_FIELDS_NOT_FOUND',
				array('#FIELD_LIST#' => "'Given Name', 'Family Name'")
			);
		}

		$hasEmail = isset($map['E-mail 1 - Value']);
		$hasPhone = isset($map['Phone 1 - Value']);

		if(!$hasName && !$hasEmail && !$hasPhone)
		{
			$messages[] = GetMessage('CRM_IMPORT_GMAIL_REQUIREMENTS');
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
		if(isset($map['Given Name']))
		{
			$k = $map['Given Name'];
			$result['NAME'] = isset($data[$k]) ? $data[$k] : '';
		}

		if(isset($map['Additional Name']))
		{
			$k = $map['Additional Name'];
			$result['SECOND_NAME'] = isset($data[$k]) ? $data[$k] : '';
		}

		if(isset($map['Family Name']))
		{
			$k = $map['Family Name'];
			$result['LAST_NAME'] = isset($data[$k]) ? $data[$k] : '';
		}

		if(isset($map['Notes']))
		{
			$k = $map['Notes'];
			$result['COMMENTS'] = isset($data[$k]) ? $data[$k] : '';
		}

		$emailInfos = $this->getMultipleField('E-mail', $data);
		foreach($emailInfos as &$emailInfo)
		{
			$valueType = strtoupper($emailInfo['VALUE_TYPE']);
			if($valueType !== 'WORK' && $valueType !== 'HOME')
			{
				$valueType = 'OTHER';
			}
			$this->addMultifieldValue('EMAIL', $valueType, $emailInfo['VALUE'], $result);
		}
		unset($emailInfo);

		$phoneInfos = $this->getMultipleField('Phone', $data);
		foreach($phoneInfos as &$phoneInfo)
		{
			$valueType = strtoupper($phoneInfo['VALUE_TYPE']);
			if($valueType !== 'WORK' && $valueType !== 'HOME' && $valueType !== 'MOBILE')
			{
				$valueType = 'OTHER';
			}
			$this->addMultifieldValue('PHONE', $valueType, $phoneInfo['VALUE'], $result);
		}
		unset($phoneInfo);

		$webInfos = $this->getMultipleField('Website', $data);
		foreach($webInfos as &$webInfo)
		{
			$valueType = strtoupper($webInfo['VALUE_TYPE']);
			if($valueType === 'HOME PAGE')
			{
				$valueType = 'HOME';
			}
			elseif($valueType !== 'WORK')
			{
				$valueType = 'OTHER';
			}
			$this->addMultifieldValue('WEB', $valueType, $webInfo['VALUE'], $result);
		}
		unset($webInfo);

		$imInfos = $this->getInstantMessengers($data);
		foreach($imInfos as &$imInfo)
		{
			$valueType = strtoupper($imInfo['VALUE_TYPE']);
			if($valueType !== 'SKYPE' && $valueType !== 'ICQ' && $valueType !== 'MSN' && $valueType !== 'JABBER')
			{
				$valueType = 'OTHER';
			}
			$this->addMultifieldValue('IM', $valueType, $imInfo['VALUE'], $result);
		}
		unset($imInfo);

		$addressInfos = $this->getAddresses($data);
		$firstAddress = '';
		$workAddress = '';
		$homeAddress = '';
		foreach($addressInfos as &$addressInfo)
		{
			$type = strtoupper($addressInfo['VALUE_TYPE']);
			if($workAddress === '' && $type === 'WORK')
			{
				$workAddress = $addressInfo['VALUE'];
			}
			elseif($homeAddress === '' && $type === 'HOME')
			{
				$homeAddress = $addressInfo['VALUE'];
			}

			if($firstAddress === '')
			{
				$firstAddress = $addressInfo['VALUE'];
			}

			if($workAddress !== '' && $homeAddress !== '')
			{
				break;
			}
		}
		unset($addressInfo);

		if($workAddress !== '')
		{
			$result['ADDRESS'] = $workAddress;
		}
		elseif($homeAddress !== '')
		{
			$result['ADDRESS'] = $homeAddress;
		}
		elseif($firstAddress !== '')
		{
			$result['ADDRESS'] = $firstAddress;
		}

		$companyInfos = $this->getOrganizations($data);
		if(!empty($companyInfos))
		{
			$companyInfo = $companyInfos[0];
			$result['COMPANY_TITLE'] = $companyInfo['NAME'];
			$result['POST'] = $companyInfo['TITLE'];
		}
		return $result;
	}
	public function getMultipleField($name, &$data)
		{
			$map = $this->headerMap;
			if($map === null)
			{
				throw new Main\SystemException("Invalid operation. HeaderMap is not assigned.");
			}

			$result = array();
			$i = 1;
			$valueKey = "{$name} {$i} - Value";
			$typeKey = "{$name} {$i} - Type";
			while(isset($map[$valueKey]))
			{
				$k = $map[$valueKey];
				$value = isset($data[$k]) ? $data[$k] : '';
				if($value !== '')
				{
					$k = isset($map[$typeKey]) ? $map[$typeKey] : '';
					$valueType = isset($data[$k]) ? trim($data[$k]) : '';

					if($valueType !== '')
					{
						$valueType = preg_replace('/^\*\s*/', '', $valueType);
					}

					$result[] = array(
						'VALUE_TYPE' => $valueType,
						'VALUE' => $value
					);
				}

				$i++;
				$valueKey = "{$name} {$i} - Value";
				$typeKey = "{$name} {$i} - Type";
			}

			return $result;
		}
	public function getOrganizations(&$data)
	{
		$map = $this->headerMap;
		if($map === null)
		{
			throw new Main\SystemException("Invalid operation. HeaderMap is not assigned.");
		}

		$result = array();
		$i = 1;
		$nameKey = "Organization {$i} - Name";
		$typeKey = "Organization {$i} - Type";
		$titleKey = "Organization {$i} - Title";
		while(isset($map[$nameKey]))
		{
			$k = $map[$nameKey];
			$name = isset($data[$k]) ? $data[$k] : '';
			if($name !== '')
			{
				$k = isset($map[$titleKey]) ? $map[$titleKey] : '';
				$title = $k !== '' && isset($data[$k]) ? $data[$k] : '';

				$k = isset($map[$typeKey]) ? $map[$typeKey] : '';
				$type = $k !== '' && isset($data[$k]) ? $data[$k] : '';

				$result[] = array(
					'NAME' => $name,
					'TITLE' => $title,
					'TYPE' => $type
				);
			}

			$i++;
			$nameKey = "Organization {$i} - Name";
			$typeKey = "Organization {$i} - Type";
			$titleKey = "Organization {$i} - Title";
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
		$i = 1;
		$typeKey = "Address {$i} - Type";
		$valueKey = "Address {$i} - Formatted";
		while(isset($map[$valueKey]))
		{
			$k = $map[$valueKey];
			$value = isset($data[$k]) ? $data[$k] : '';
			if($value !== '')
			{
				$k = isset($map[$typeKey]) ? $map[$typeKey] : '';
				$valueType = $k !== '' && isset($data[$k]) ? trim($data[$k]) : '';

				if($valueType !== '')
				{
					$valueType = preg_replace('/^\*\s*/', '', $valueType);
				}

				$result[] = array(
					'VALUE_TYPE' => $valueType,
					'VALUE' => $value
				);
			}

			$i++;
			$typeKey = "Address {$i} - Type";
			$valueKey = "Address {$i} - Formatted";
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
		$i = 1;
		$typeKey = "IM {$i} - Service";
		$valueKey = "IM {$i} - Value";

		$types = '';
		$values = '';
		while($this->tryToGetValue($typeKey, $data, $types, $map) && $types !== ''
			&& $this->tryToGetValue($valueKey, $data, $values, $map) && $values !== '')
		{
			$types = explode(':::', $types);
			$values = explode(':::', $values);

			foreach($types as $k => $type)
			{
				$type = trim($type);

				$value = isset($values[$k]) ? trim($values[$k]) : '';
				if($value === '')
				{
					continue;
				}

				$result[] = array(
					'VALUE_TYPE' => $type,
					'VALUE' => $value
				);
			}

			$i++;
			$typeKey = "IM {$i} - Service";
			$valueKey = "IM {$i} - Value";
		}
		return $result;
	}
}