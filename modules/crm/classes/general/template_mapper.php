<?php
abstract class CCrmTemplateMapperBase
{
	protected $contentType = CCrmContentType::PlainText;
	public function GetContentType()
	{
		return $this->contentType;
	}
	public function SetContentType($type)
	{
		$this->contentType = $type;
	}
	abstract public function MapPath($path);
}

class CCrmTemplateMapper extends CCrmTemplateMapperBase
{
	private static $LEAD_STATUSES = null;
	private static $SOURCES = null;
	private static $DEAL_TYPES = null;
	private static $DEAL_STAGES = null;
	private static $CONTACT_TYPES = null;
	private static $COMPANY_TYPES = null;
	private static $INDUSTRIES = null;
	private static $EMPLOYEES = null;

	private $context = null;
	function __construct($typeID, $ID)
	{
		$this->context = self::ResolveEntityInfo($typeID, $ID);
	}
	public function MapPath($path)
	{
		$path = strval($path);
		if($path === '')
		{
			return '';
		}

		if($this->context === null)
		{
			return $path;
		}

		$typeName = isset($this->context['TYPE_NAME']) ? $this->context['TYPE_NAME'] : '';
		$parts = explode('.', $path);
		if(count($parts) < 2 || $typeName === '' || $typeName !== $parts[0])
		{
			//Invalid path or invalid info is specified
			return $path;
		}

		// Take 3 (max depth) from 2 (fisrt is context entity type name)
		$parts = array_slice($parts, 1, 3);

		$result = '';
		$curEntityInfo = &$this->context;
		foreach($parts as &$part)
		{
			if(isset($curEntityInfo['ASSICIATIONS']) && isset($curEntityInfo['ASSICIATIONS'][$part]))
			{
				$curEntityInfo = &$curEntityInfo['ASSICIATIONS'][$part];
				continue;
			}

			$curResult = $this->MapField($curEntityInfo, $part);
			if(is_array($curResult))
			{
				if(!isset($curEntityInfo['ASSICIATIONS']))
				{
					$curEntityInfo['ASSICIATIONS'] = array();
				}
				$curEntityInfo['ASSICIATIONS'][$part] = &$curResult;
				$curEntityInfo = &$curResult;
				unset($curResult);
				continue;
			}

			$result = $curResult;
			break;
		}
		unset($part, $curEntityInfo);

		return $result !== '' ? $result : $path;
	}
	private static function ResolveEntityInfo($typeID, $ID)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		if(!(CCrmOwnerType::IsDefined($typeID) && $ID > 0))
		{
			return array(
				'TYPE_ID' => $typeID,
				'TYPE_NAME' => CCrmOwnerType::ResolveName($typeID),
				'ID' => $ID
			);
		}

		$fields = null;
		if($typeID === CCrmOwnerType::Lead)
		{
			$fields = CCrmLead::GetByID($ID, false);
		}
		elseif($typeID === CCrmOwnerType::Contact)
		{
			$fields = CCrmContact::GetByID($ID, false);
		}
		elseif($typeID === CCrmOwnerType::Company)
		{
			$fields = CCrmCompany::GetByID($ID, false);
		}
		elseif($typeID === CCrmOwnerType::Deal)
		{
			$fields = CCrmDeal::GetByID($ID, false);
		}

		return array(
			'TYPE_ID' => $typeID,
			'TYPE_NAME' => CCrmOwnerType::ResolveName($typeID),
			'ID' => $ID,
			'FIELDS' => $fields
		);
	}
	private function MapField(&$entityInfo, $fieldName)
	{
		$result = '';
		$typeID = isset($entityInfo['TYPE_ID']) ? intval($entityInfo['TYPE_ID']) : CCrmOwnerType::Undefined;
		$fields = isset($entityInfo['FIELDS']) ? $entityInfo['FIELDS'] : array();

		$isHtml = $this->contentType === CCrmContentType::Html;
		$isBBCode = $this->contentType === CCrmContentType::BBCode;
		$isPlainText = $this->contentType === CCrmContentType::PlainText;

		if($typeID === CCrmOwnerType::Lead)
		{
			switch($fieldName)
			{
				case 'ID':
					$result = isset($fields[$fieldName]) ? intval($fields[$fieldName]) : 0;
					break;
				case 'NAME':
				case 'SECOND_NAME':
				case 'LAST_NAME':
				case 'TITLE':
				case 'COMPANY_TITLE':
				case 'SOURCE_DESCRIPTION':
				case 'STATUS_DESCRIPTION':
				case 'POST':
				case 'ADDRESS':
						$result = self::MapFieldValue($fields, $fieldName, $isHtml);
					break;
				case 'COMMENTS':
				{
					if($isBBCode)
					{
						$result = self::MapHtmlFieldAsBbCode($fields, 'COMMENTS');
					}
					elseif($isPlainText)
					{
						$result = self::MapHtmlFieldAsPlainText($fields, 'COMMENTS');
					}
					else
					{
						$result = self::MapFieldValue($fields, $fieldName, false);
					}
					break;
				}
				case 'SOURCE':
					$result = self::MapReferenceValue(self::PrepareSources(), $fields, 'SOURCE_ID', $isHtml);
					break;
				case 'STATUS':
					$result = self::MapReferenceValue(self::PrepareLeadStatuses(), $fields, 'STATUS_ID', $isHtml);
					break;
				case 'FORMATTED_NAME':
					$result = CUser::FormatName(
						\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
						array(
							'LOGIN' => '',
							'NAME' => isset($fields['NAME']) ? $fields['NAME'] : '',
							'SECOND_NAME' => isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : '',
							'LAST_NAME' => isset($fields['LAST_NAME']) ? $fields['LAST_NAME'] : ''
						),
						false, $isHtml
					);
					break;
				case 'ASSIGNED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['ASSIGNED_BY_ID']) ? $fields['ASSIGNED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'CREATED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['CREATED_BY_ID']) ? $fields['CREATED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'MODIFY_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['MODIFY_BY_ID']) ? $fields['MODIFY_BY_ID'] : 0, '', $isHtml);
					break;
				case 'DATE_CREATE':
					$result = isset($fields['DATE_CREATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_CREATE'])) : '';
					break;
				case 'DATE_MODIFY':
					$result = isset($fields['DATE_MODIFY']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_MODIFY'])) : '';
					break;
				case 'CURRENCY':
					$result = isset($fields['CURRENCY_ID']) ? CCrmCurrency::GetCurrencyName($fields['CURRENCY_ID']) : '';
					break;
				case 'OPPORTUNITY':
					$result = isset($fields['OPPORTUNITY']) ? $fields['OPPORTUNITY'] : 0.00;
					break;
				case 'OPPORTUNITY_FORMATTED':
					$result = CCrmCurrency::MoneyToString(
						isset($fields['OPPORTUNITY']) ? $fields['OPPORTUNITY'] : 0.00,
						isset($fields['CURRENCY_ID']) ? $fields['CURRENCY_ID'] : ''
					);
					break;
			}
		}
		elseif($typeID === CCrmOwnerType::Deal)
		{
			switch($fieldName)
			{
				case 'ID':
					$result = isset($fields[$fieldName]) ? intval($fields[$fieldName]) : 0;
					break;
				case 'TITLE':
					$result = self::MapFieldValue($fields, 'TITLE', $isHtml);
					break;
				case 'COMMENTS':
				{
					if($isBBCode)
					{
						$result = self::MapHtmlFieldAsBbCode($fields, 'COMMENTS');
					}
					elseif($isPlainText)
					{
						$result = self::MapHtmlFieldAsPlainText($fields, 'COMMENTS');
					}
					else
					{
						$result = self::MapFieldValue($fields, $fieldName, false);
					}
					break;
				}
				case 'TYPE':
					$result = self::MapReferenceValue(self::PrepareDealTypes(), $fields, 'TYPE_ID', $isHtml);
					break;
				case 'STAGE':
					$result = self::MapReferenceValue(self::PrepareDealStages(), $fields, 'STAGE_ID', $isHtml);
					break;
				case 'PROBABILITY':
					$result = (isset($fields[$fieldName]) ? intval($fields[$fieldName]) : 0).' %';
					break;
				case 'BEGINDATE':
					$result = isset($fields['BEGINDATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['BEGINDATE'])) : '';
					break;
				case 'CLOSEDATE':
					$result = isset($fields['CLOSEDATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['CLOSEDATE'])) : '';
					break;
				case 'ASSIGNED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['ASSIGNED_BY_ID']) ? $fields['ASSIGNED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'CREATED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['CREATED_BY_ID']) ? $fields['CREATED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'MODIFY_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['MODIFY_BY_ID']) ? $fields['MODIFY_BY_ID'] : 0, '', $isHtml);
					break;
				case 'DATE_CREATE':
					$result = isset($fields['DATE_CREATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_CREATE'])) : '';
					break;
				case 'DATE_MODIFY':
					$result = isset($fields['DATE_MODIFY']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_MODIFY'])) : '';
					break;
				case 'CURRENCY':
					$result = isset($fields['CURRENCY_ID']) ? CCrmCurrency::GetCurrencyName($fields['CURRENCY_ID']) : '';
					break;
				case 'OPPORTUNITY':
					$result = isset($fields['OPPORTUNITY']) ? $fields['OPPORTUNITY'] : 0.00;
					break;
				case 'OPPORTUNITY_FORMATTED':
					$result = CCrmCurrency::MoneyToString(
						isset($fields['OPPORTUNITY']) ? $fields['OPPORTUNITY'] : 0.00,
						isset($fields['CURRENCY_ID']) ? $fields['CURRENCY_ID'] : ''
					);
					break;
				case 'COMPANY':
					$result = self::ResolveEntityInfo(
						CCrmOwnerType::Company,
						isset($fields['COMPANY_ID']) ? intval($fields['COMPANY_ID']) : 0
					);
					break;
				case 'CONTACT':
					$result = self::ResolveEntityInfo(
						CCrmOwnerType::Contact,
						isset($fields['CONTACT_ID']) ? intval($fields['CONTACT_ID']) : 0
					);
					break;
			}
		}
		elseif($typeID === CCrmOwnerType::Contact)
		{
			switch($fieldName)
			{
				case 'ID':
					$result = isset($fields[$fieldName]) ? intval($fields[$fieldName]) : 0;
					break;
				case 'NAME':
				case 'SECOND_NAME':
				case 'LAST_NAME':
				case 'POST':
				case 'ADDRESS':
				case 'SOURCE_DESCRIPTION':
					$result = self::MapFieldValue($fields, $fieldName, $isHtml);
					break;
				case 'COMMENTS':
				{
					if($isBBCode)
					{
						$result = self::MapHtmlFieldAsBbCode($fields, 'COMMENTS');
					}
					elseif($isPlainText)
					{
						$result = self::MapHtmlFieldAsPlainText($fields, 'COMMENTS');
					}
					else
					{
						$result = self::MapFieldValue($fields, $fieldName, false);
					}
					break;
				}
				case 'FORMATTED_NAME':
					$result = CUser::FormatName(
						\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
						array(
							'LOGIN' => '',
							'NAME' => isset($fields['NAME']) ? $fields['NAME'] : '',
							'SECOND_NAME' => isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : '',
							'LAST_NAME' => isset($fields['LAST_NAME']) ? $fields['LAST_NAME'] : ''
						),
						false, $isHtml
					);
					break;
				case 'SOURCE':
					$result = self::MapReferenceValue(self::PrepareSources(), $fields, 'SOURCE_ID', $isHtml);
					break;
				case 'TYPE':
					$result = self::MapReferenceValue(self::PrepareContactTypes(), $fields, 'TYPE_ID', $isHtml);
					break;
				case 'COMPANY':
					$result = self::ResolveEntityInfo(
						CCrmOwnerType::Company,
						isset($fields['COMPANY_ID']) ? intval($fields['COMPANY_ID']) : 0
					);
					break;
				case 'ASSIGNED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['ASSIGNED_BY_ID']) ? $fields['ASSIGNED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'CREATED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['CREATED_BY_ID']) ? $fields['CREATED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'MODIFY_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['MODIFY_BY_ID']) ? $fields['MODIFY_BY_ID'] : 0, '', $isHtml);
					break;
				case 'DATE_CREATE':
					$result = isset($fields['DATE_CREATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_CREATE'])) : '';
					break;
				case 'DATE_MODIFY':
					$result = isset($fields['DATE_MODIFY']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_MODIFY'])) : '';
					break;
			}
		}
		elseif($typeID === CCrmOwnerType::Company)
		{
			switch($fieldName)
			{
				case 'ID':
					$result = isset($fields[$fieldName]) ? intval($fields[$fieldName]) : 0;
					break;
				case 'TITLE':
				case 'COMPANY_TITLE':
				case 'SOURCE_DESCRIPTION':
				case 'ADDRESS':
				case 'ADDRESS_LEGAL':
				case 'BANKING_DETAILS':
					$result = self::MapFieldValue($fields, $fieldName, $isHtml);
					break;
				case 'COMMENTS':
				{
					if($isBBCode)
					{
						$result = self::MapHtmlFieldAsBbCode($fields, 'COMMENTS');
					}
					elseif($isPlainText)
					{
						$result = self::MapHtmlFieldAsPlainText($fields, 'COMMENTS');
					}
					else
					{
						$result = self::MapFieldValue($fields, $fieldName, false);
					}
					break;
				}
				case 'COMPANY_TYPE':
				case 'TYPE':
					$result = self::MapReferenceValue(self::PrepareCompanyTypes(), $fields, 'COMPANY_TYPE', $isHtml);
					break;
				case 'INDUSTRY':
					$result = self::MapReferenceValue(self::PrepareIndustries(), $fields, 'INDUSTRY', $isHtml);
					break;
				case 'EMPLOYEES':
					$result = self::MapReferenceValue(self::PrepareEmployees(), $fields, 'EMPLOYEES', $isHtml);
					break;
				case 'CURRENCY':
					$result = isset($fields['CURRENCY_ID']) ? CCrmCurrency::GetCurrencyName($fields['CURRENCY_ID']) : '';
					break;
				case 'REVENUE':
					$result = isset($fields['REVENUE']) ? $fields['REVENUE'] : 0.00;
					break;
				case 'REVENUE_FORMATTED':
					$result = CCrmCurrency::MoneyToString(
						isset($fields['REVENUE']) ? $fields['REVENUE'] : 0.00,
						isset($fields['CURRENCY_ID']) ? $fields['CURRENCY_ID'] : ''
					);
					break;
				case 'ASSIGNED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['ASSIGNED_BY_ID']) ? $fields['ASSIGNED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'CREATED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['CREATED_BY_ID']) ? $fields['CREATED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'MODIFY_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['MODIFY_BY_ID']) ? $fields['MODIFY_BY_ID'] : 0, '', $isHtml);
					break;
				case 'DATE_CREATE':
					$result = isset($fields['DATE_CREATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_CREATE'])) : '';
					break;
				case 'DATE_MODIFY':
					$result = isset($fields['DATE_MODIFY']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_MODIFY'])) : '';
					break;
			}
		}

		return $result;
	}
	private static function MapFieldValue(&$fields, $fieldID, $htmlEncode = false)
	{
		if(!isset($fields[$fieldID]))
		{
			return '';
		}

		$result = $fields[$fieldID];
		if($htmlEncode)
		{
			$result = htmlspecialcharsEx($result);
		}
		return $result;
	}
	private static function MapHtmlFieldAsBbCode(&$fields, $fieldID)
	{
		if(!isset($fields[$fieldID]))
		{
			return '';
		}

		return \Bitrix\Crm\Format\TextHelper::convertHtmlToBbCode($fields[$fieldID]);
	}
	private static function MapHtmlFieldAsPlainText(&$fields, $fieldID)
	{
		if(!isset($fields[$fieldID]))
		{
			return '';
		}

		$result = $fields[$fieldID];
		$result = preg_replace("/<br(\s*\/\s*)?>/i", PHP_EOL, $result);
		return strip_tags($result);
	}
	private static function MapReferenceValue(&$items, &$fields, $fieldID, $htmlEncode = false)
	{
		$ID = isset($fields[$fieldID]) ? $fields[$fieldID] : '';
		$result = isset($items[$ID]) ? $items[$ID] : $ID;
		if($htmlEncode)
		{
			$result = htmlspecialcharsEx($result);
		}
		return $result;
	}
	private static function PrepareLeadStatuses()
	{
		return self::$LEAD_STATUSES !== null
			? self::$LEAD_STATUSES
			: (self::$LEAD_STATUSES = CCrmStatus::GetStatusListEx('STATUS'));
	}
	private static function PrepareSources()
	{
		return self::$SOURCES !== null
			? self::$SOURCES
			: (self::$SOURCES = CCrmStatus::GetStatusListEx('SOURCE'));
	}
	private static function PrepareDealTypes()
	{
		return self::$DEAL_TYPES !== null
			? self::$DEAL_TYPES
			: (self::$DEAL_TYPES = CCrmStatus::GetStatusListEx('DEAL_TYPE'));
	}
	private static function PrepareDealStages()
	{
		return self::$DEAL_STAGES !== null
			? self::$DEAL_STAGES
			: (self::$DEAL_STAGES = CCrmStatus::GetStatusListEx('DEAL_STAGE'));
	}
	private static function PrepareContactTypes()
	{
		return self::$CONTACT_TYPES !== null
			? self::$CONTACT_TYPES
			: (self::$CONTACT_TYPES = CCrmStatus::GetStatusListEx('CONTACT_TYPE'));
	}
	private static function PrepareCompanyTypes()
	{
		return self::$COMPANY_TYPES !== null
			? self::$COMPANY_TYPES
			: (self::$COMPANY_TYPES = CCrmStatus::GetStatusListEx('COMPANY_TYPE'));
	}
	private static function PrepareIndustries()
	{
		return self::$INDUSTRIES !== null
			? self::$INDUSTRIES
			: (self::$INDUSTRIES = CCrmStatus::GetStatusListEx('INDUSTRY'));
	}
	private static function PrepareEmployees()
	{
		return self::$EMPLOYEES !== null
			? self::$EMPLOYEES
			: (self::$EMPLOYEES = CCrmStatus::GetStatusListEx('EMPLOYEES'));
	}
}