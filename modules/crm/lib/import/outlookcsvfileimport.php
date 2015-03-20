<?php
namespace Bitrix\Crm\Import;
use Bitrix\Main;
class OutlookCsvFileImport extends CsvFileImport
{
	protected $originalHeaderMap = null;
	protected $headerMap = null;
	protected $headerLanguage = '';
	protected $headerAliases = null;
	protected $enableCompatibilityMode = false;

	protected static $PHONE_TYPES = array('Business', 'Home');
	protected static $ADDRESS_TYPES = array('Business', 'Home', 'Other');
	protected static $FIELDS = null;
	protected static $FIELD_MATCH_CODES = null;

	public function __construct()
	{
	}
	public function getHeaderMap()
	{
		return $this->headerMap;
	}
	public function setHeaderMap(array $headerMap)
	{
		if($this->headerMap === $headerMap)
		{
			return;
		}

		if(!$this->enableCompatibilityMode)
		{
			$this->headerMap = $this->originalHeaderMap = $headerMap;
		}
		else
		{
			$this->originalHeaderMap = $headerMap;
			$this->headerMap = $this->prepareCompatibleHeaderMap($this->originalHeaderMap);
		}
	}
	protected function prepareCompatibleHeaderMap(array $headerMap)
	{
		$result = array();
		foreach($headerMap as $k => $v)
		{
			$code = strtoupper(str_replace(' ', '', $k));
			$result[$code] = $v;
		}
		return $result;
	}
	public function getHeaderLanguage()
	{
		return $this->headerLanguage;
	}
	public function setHeaderLanguage($langID)
	{
		$this->headerLanguage = strtolower($langID);
	}
	public function isCompatibilityModeEnabled()
	{
		return $this->enableCompatibilityMode;
	}
	public function enableCompatibilityMode($enable)
	{
		$enable = (bool)$enable;
		if($this->enableCompatibilityMode === $enable)
		{
			return;
		}

		$this->enableCompatibilityMode = $enable;
		if($enable)
		{
			$this->headerMap = is_array($this->originalHeaderMap)
				? $this->prepareCompatibleHeaderMap($this->originalHeaderMap) : null;
		}
		else
		{
			$this->headerMap = $this->originalHeaderMap;
		}
	}
	public function getDefaultEncoding()
		{
			return $this->headerLanguage === 'ru' ? 'Windows-1251' : 'Windows-1252';
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

		$hasName = isset($map[$this->getFiledAlias('FIRST_NAME')])
			|| isset($map[$this->getFiledAlias('LAST_NAME')]);

		if(!$hasName)
		{
			$messages[] = GetMessage(
				'CRM_IMPORT_OUTLOOK_ERROR_FIELDS_NOT_FOUND',
				array(
					'#FIELD_LIST#' => implode(', ',
						array(
							'\''.$this->getFiledAlias('FIRST_NAME').'\'',
							'\''.$this->getFiledAlias('LAST_NAME').'\''
						)
					)
				)
			);
		}

		$hasEmail = isset($map[$this->getFiledAlias('E_MAIL_ADDRESS')])
			|| isset($map[$this->getFiledAlias('E_MAIL_2_ADDRESS')]);

		$hasPhone = isset($map[$this->getFiledAlias('PRIMARY_PHONE')])
			|| isset($map[$this->getFiledAlias('COMPANY_MAIN_PHONE')])
			|| isset($map[$this->getFiledAlias('MOBILE_PHONE')])
			|| isset($map[$this->getFiledAlias('BUSINESS_PHONE')])
			|| isset($map[$this->getFiledAlias('BUSINESS_PHONE_2')])
			|| isset($map[$this->getFiledAlias('HOME_PHONE')])
			|| isset($map[$this->getFiledAlias('HOME_PHONE_2')])
			|| isset($map[$this->getFiledAlias('CAR_PHONE')])
			|| isset($map[$this->getFiledAlias('RADIO_PHONE')])
			|| isset($map[$this->getFiledAlias('OTHER_PHONE')]);

		if(!$hasName && !$hasEmail && !$hasPhone)
		{
			$messages[] = GetMessage(
				'CRM_IMPORT_OUTLOOK_REQUIREMENTS',
				array(
					'#FILE_ENCODING#' => $this->getDefaultEncoding(),
					'#FILE_LANG#' => $this->headerLanguage
				)
			);
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
		$this->tryToMapField($this->getFiledAlias('FIRST_NAME'), 'NAME', $data, $result, $map, true);
		$this->tryToMapField($this->getFiledAlias('MIDDLE_NAME'), 'SECOND_NAME', $data, $result, $map, true);
		$this->tryToMapField($this->getFiledAlias('LAST_NAME'), 'LAST_NAME', $data, $result, $map, true);
		$this->tryToMapField($this->getFiledAlias('JOB_TITLE'), 'POST', $data, $result, $map, true);
		$this->tryToMapField($this->getFiledAlias('COMPANY'), 'COMPANY_TITLE', $data, $result, $map, true);
		$this->tryToMapField($this->getFiledAlias('NOTES'), 'COMMENTS', $data, $result, $map, true);

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
			if($valueType === 'BUSINESS')
			{
				$valueType = 'WORK';
			}
			if($valueType !== 'MOBILE' && $valueType !== 'FAX'
				&& $valueType !== 'HOME' && $valueType !== 'HOME'
				&& $valueType !== 'PAGER' && $valueType !== 'OTHER')
			{
				$valueType = 'OTHER';
			}
			$this->addMultifieldValue('PHONE', $valueType, $phoneInfo['VALUE'], $result);
		}
		unset($phoneInfo);

		$webPageUrl = '';
		if($this->tryToGetValue($this->getFiledAlias('WEB_PAGE'), $data, $webPageUrl, $map, true) && $webPageUrl !== '')
		{
			$result['WEB_WORK'] = $webPageUrl;
		}

		$addressInfos = $this->getAddresses($data);
		if(isset($addressInfos['Business']))
		{
			$result['ADDRESS'] = $this->formatAddress($addressInfos['Business']);
		}
		elseif(isset($addressInfos['Home']))
		{
			$result['ADDRESS'] = $this->formatAddress($addressInfos['Home']);
		}
		elseif(isset($addressInfos['Other']))
		{
			$result['ADDRESS'] = $this->formatAddress($addressInfos['Other']);
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
		if($this->tryToGetValue($this->getFiledAlias('PRIMARY_PHONE'), $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'Business',
				'VALUE' => $value
			);
		}
		if($this->tryToGetValue($this->getFiledAlias('COMPANY_MAIN_PHONE'), $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'Business',
				'VALUE' => $value
			);
		}
		if($this->tryToGetValue($this->getFiledAlias('MOBILE_PHONE'), $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'Mobile',
				'VALUE' => $value
			);
		}
		if($this->tryToGetValue($this->getFiledAlias('RADIO_PHONE'), $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'Other',
				'VALUE' => $value
			);
		}
		if($this->tryToGetValue($this->getFiledAlias('CAR_PHONE'), $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'Other',
				'VALUE' => $value
			);
		}
		if($this->tryToGetValue($this->getFiledAlias('OTHER_PHONE'), $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'Other',
				'VALUE' => $value
			);
		}
		if($this->tryToGetValue($this->getFiledAlias('OTHER_FAX'), $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'Fax',
				'VALUE' => $value
			);
		}
		if($this->tryToGetValue($this->getFiledAlias('PAGER'), $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'Pager',
				'VALUE' => $value
			);
		}

		foreach(self::$PHONE_TYPES as $type)
		{
			$typeUC = strtoupper($type);

			$keys = array("{$typeUC}_PHONE", "{$typeUC}_PHONE_2");
			foreach($keys as $key)
			{
				if($this->tryToGetValue($this->getFiledAlias($key), $data, $value, $map, true) && $value !== '')
				{
					$result[] = array(
						'VALUE_TYPE' => $type,
						'VALUE' => $value
					);
				}
			}
			unset($keys);

			$key = "{$typeUC}_FAX";
			if($this->tryToGetValue($this->getFiledAlias($key), $data, $value, $map, true) && $value !== '')
			{
				$result[] = array(
					'VALUE_TYPE' => 'Fax',
					'VALUE' => $value
				);
			}
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
		if($this->tryToGetValue($this->getFiledAlias('E_MAIL_ADDRESS'), $data, $value, $map, true) && $value !== '')
		{
			$result[] = array(
				'VALUE_TYPE' => 'P',
				'VALUE' => $value
			);
		}

		$i = 2;
		$valueKey = "E_MAIL_{$i}_ADDRESS";
		while($this->tryToGetValue($this->getFiledAlias($valueKey), $data, $value, $map, true))
		{
			if($value !== '')
			{
				$result[] = array(
					'VALUE_TYPE' => 'A',
					'VALUE' => $value
				);
			}

			$i++;
			$valueKey = "E_MAIL_{$i}_ADDRESS";
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
			$typeUC = strtoupper($type);

			$info = $this->getAddress(
				$data,
				$map,
				array(
					'STREET' => $this->getFiledAlias("{$typeUC}_STREET"),
					'CITY' => $this->getFiledAlias("{$typeUC}_CITY"),
					'STATE' => $this->getFiledAlias("{$typeUC}_STATE"),
					'COUNTRY' => $this->getFiledAlias("{$typeUC}_COUNTRY"),
					'POSTAL_CODE' => $this->getFiledAlias("{$typeUC}_POSTAL_CODE"),
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
	protected function getFiledAlias($fieldID)
	{
		if($this->headerAliases === null)
		{
			$this->headerAliases = self::getHeaderAliases($this->headerLanguage, $this->enableCompatibilityMode);
		}

		if(isset($this->headerAliases[$fieldID]))
		{
			return $this->headerAliases[$fieldID];
		}

		return self::getFieldName($fieldID);
	}
	protected static function getFields()
	{
		if(self::$FIELDS === null)
		{
			self::$FIELDS = array(
				'FIRST_NAME' => array('NAME' => 'First Name'),
				'MIDDLE_NAME' => array('NAME' => 'Middle Name'),
				'LAST_NAME' => array('NAME' => 'Last Name'),
				'COMPANY' => array('NAME' => 'Company'),
				'JOB_TITLE' => array('NAME' => 'Job Title'),
				'BUSINESS_STREET' => array('NAME' => 'Business Street'),
				'BUSINESS_CITY' => array('NAME' => 'Business City'),
				'BUSINESS_STATE' => array('NAME' => 'Business State'),
				'BUSINESS_POSTAL_CODE' => array('NAME' => 'Business Postal Code'),
				'BUSINESS_POSTAL_COUNTRY' => array('NAME' => 'Business Country'),

				'HOME_STREET' => array('NAME' => 'Home Street'),
				'HOME_CITY' => array('NAME' => 'Home City'),
				'HOME_STATE' => array('NAME' => 'Home State'),
				'HOME_POSTAL_CODE' => array('NAME' => 'Home Postal Code'),
				'HOME_COUNTRY' => array('NAME' => 'Home Country'),

				'OTHER_STREET' => array('NAME' => 'Other Street'),
				'OTHER_CITY' => array('NAME' => 'Other City'),
				'OTHER_STATE' => array('NAME' => 'Other State'),
				'OTHER_POSTAL_CODE' => array('NAME' => 'Other Postal Code'),
				'OTHER_COUNTRY' => array('NAME' => 'Other Country'),

				'BUSINESS_FAX' => array('NAME' => 'Business Fax'),
				'BUSINESS_PHONE' => array('NAME' => 'Business Phone'),
				'BUSINESS_PHONE_2' => array('NAME' => 'Business Phone 2'),

				'HOME_FAX' => array('NAME' => 'Home Fax'),
				'HOME_PHONE' => array('NAME' => 'Home Phone'),
				'HOME_PHONE_2' => array('NAME' => 'Home Phone 2'),

				'PRIMARY_PHONE' => array('NAME' => 'Primary Phone'),
				'COMPANY_MAIN_PHONE' => array('NAME' => 'Company Main Phone'),
				'MOBILE_PHONE' => array('NAME' => 'Mobile Phone'),
				'RADIO_PHONE' => array('NAME' => 'Radio Phone'),
				'CAR_PHONE' => array('NAME' => 'Car Phone'),
				'OTHER_FAX' => array('NAME' => 'Other Fax'),
				'OTHER_PHONE' => array('NAME' => 'Other Phone'),
				'PAGER' => array('NAME' => 'Pager'),

				'BIRTHDAY' => array('NAME' => 'Birthday'),
				'E_MAIL_ADDRESS' => array('NAME' => 'E-mail Address'),
				'E_MAIL_2_ADDRESS' => array('NAME' => 'E-mail 2 Address'),
				'E_MAIL_3_ADDRESS' => array('NAME' => 'E-mail 3 Address'),
				'NOTES' => array('NAME' => 'Notes'),
				'WEB_PAGE' => array('NAME' => 'Web Page')
			);
		}
		return self::$FIELDS;
	}
	protected static function getFieldName($fieldID)
	{
		$fields = self::getFields();
		return isset($fields[$fieldID]) ? $fields[$fieldID]['NAME'] : $fieldID;
	}
	protected static function getFieldMatchCode($fieldID)
	{
		if(self::$FIELD_MATCH_CODES !== null && isset(self::$FIELD_MATCH_CODES[$fieldID]))
		{
			return self::$FIELD_MATCH_CODES[$fieldID];
		}

		$fields = self::getFields();
		$fieldName = isset($fields[$fieldID]) ? $fields[$fieldID]['NAME'] : $fieldID;

		if(self::$FIELD_MATCH_CODES === null)
		{
			self::$FIELD_MATCH_CODES = array();
		}

		return (self::$FIELD_MATCH_CODES[$fieldID] = strtoupper(str_replace(' ', '', $fieldName)));
	}
	protected static function getHeaderAliases($langID, $enableCompatibilityMode = false)
	{
		$result = array();
		$fields = self::getFields();

		if($langID === ''|| $langID === 'en')
		{
			foreach($fields as $fieldID => &$field)
			{
				$result[$fieldID] = $field['NAME'];
				if($enableCompatibilityMode)
				{
					$result[$fieldID] = strtoupper(str_replace(' ', '', $result[$fieldID]));
				}
			}
			unset($field);
		}
		else
		{
			$messages = IncludeModuleLangFile(__FILE__, $langID, true);
			if(!is_array($messages))
			{
				return array();
			}

			foreach($fields as $fieldID => &$field)
			{
				$key = "CRM_IMPORT_OUTLOOK_ALIAS_{$fieldID}";
				$result[$fieldID] = isset($messages[$key]) ? $messages[$key] : $field['NAME'];
				if($enableCompatibilityMode)
				{
					$result[$fieldID] = strtoupper(str_replace(' ', '', $result[$fieldID]));
				}
			}
			unset($field);
		}

		return $result;
	}
}