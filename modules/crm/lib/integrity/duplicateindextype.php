<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

class DuplicateIndexType
{
	const UNDEFINED = 0;
	const PERSON = 1;
	const ORGANIZATION = 2;
	const COMMUNICATION_PHONE = 4;
	const COMMUNICATION_EMAIL = 8;

	const COMMUNICATION = 12; /*COMMUNICATION_PHONE|COMMUNICATION_EMAIL*/
	const DENOMINATION = 3; /*PERSON|ORGANIZATION*/
	const ALL = 15; /*PERSON|ORGANIZATION|COMMUNICATION_PHONE|COMMUNICATION_EMAIL*/

	const PERSON_NAME = 'PERSON';
	const ORGANIZATION_NAME = 'ORGANIZATION';
	const COMMUNICATION_PHONE_NAME = 'COMMUNICATION_PHONE';
	const COMMUNICATION_EMAIL_NAME = 'COMMUNICATION_EMAIL';

	private static $ALL_DESCRIPTIONS = array();

	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = intval($typeID);
		return $typeID === self::PERSON
			|| $typeID === self::ORGANIZATION
			|| $typeID === self::COMMUNICATION_EMAIL
			|| $typeID === self::COMMUNICATION_PHONE
			|| $typeID === self::DENOMINATION
			|| $typeID === self::COMMUNICATION
			|| $typeID === self::ALL;
	}
	public static function resolveName($typeID)
	{
		if(!is_numeric($typeID))
		{
			return '';
		}

		$typeID = intval($typeID);
		if($typeID <= 0)
		{
			return '';
		}

		$results = array();
		if(($typeID & self::PERSON) !== 0)
		{
			$results[] = self::PERSON_NAME;
		}
		if(($typeID & self::ORGANIZATION) !== 0)
		{
			$results[] = self::ORGANIZATION_NAME;
		}
		if(($typeID & self::COMMUNICATION_PHONE) !== 0)
		{
			$results[] = self::COMMUNICATION_PHONE_NAME;
		}
		if(($typeID & self::COMMUNICATION_EMAIL) !== 0)
		{
			$results[] = self::COMMUNICATION_EMAIL_NAME;
		}

		return implode('|', $results);
	}
	public static function resolveID($typeName)
	{
		$typeID = self::innerResolveID($typeName);
		if($typeID !== self::UNDEFINED)
		{
			return $typeID;
		}

		if(strpos($typeName, '|') >= 0)
		{
			$typeNames = explode('|', $typeName);
			foreach($typeNames as $name)
			{
				$typeID |= self::innerResolveID(trim($name));
			}
		}
		return $typeID;
	}
	private static function innerResolveID($typeName)
	{
		if(!is_string($typeName))
		{
			return self::UNDEFINED;
		}

		$typeName = strtoupper(trim($typeName));
		if($typeName === '')
		{
			return self::UNDEFINED;
		}

		if($typeName === self::PERSON_NAME)
		{
			return self::PERSON;
		}
		if($typeName === self::ORGANIZATION_NAME)
		{
			return self::ORGANIZATION;
		}
		if($typeName === self::COMMUNICATION_PHONE_NAME)
		{
			return self::COMMUNICATION_PHONE;
		}
		if($typeName ===  self::COMMUNICATION_EMAIL_NAME)
		{
			return self::COMMUNICATION_EMAIL;
		}

		return self::UNDEFINED;
	}
	public static function getAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS[LANGUAGE_ID])
		{
			Main\Localization\Loc::loadMessages(__FILE__);
			self::$ALL_DESCRIPTIONS[LANGUAGE_ID] = array(
				self::PERSON => GetMessage('CRM_DUP_INDEX_TYPE_PERSON'),
				self::ORGANIZATION => GetMessage('CRM_DUP_INDEX_TYPE_ORGANIZATION'),
				self::COMMUNICATION_PHONE => GetMessage('CRM_DUP_INDEX_TYPE_COMM_PHONE'),
				self::COMMUNICATION_EMAIL => GetMessage('CRM_DUP_INDEX_TYPE_COMM_EMAIL')
			);
		}

		return self::$ALL_DESCRIPTIONS[LANGUAGE_ID];
	}
	public static function isSigle($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = intval($typeID);
		return ($typeID === self::PERSON
			|| $typeID === self::ORGANIZATION
			|| $typeID === self::COMMUNICATION_EMAIL
			|| $typeID === self::COMMUNICATION_PHONE);
	}
	public static function joinType(array $typeIDs)
	{
		$result = 0;
		foreach($typeIDs as $typeID)
		{
			$result |= $typeID;
		}
		return $result;
	}
	public static function splitType($typeID)
	{
		$typeID = intval($typeID);

		$result = array();
		if(($typeID & self::PERSON) !== 0)
		{
			$result[] = self::PERSON;
		}
		if(($typeID & self::ORGANIZATION) !== 0)
		{
			$result[] = self::ORGANIZATION;
		}
		if(($typeID & self::COMMUNICATION_PHONE) !== 0)
		{
			$result[] = self::COMMUNICATION_PHONE;
		}
		if(($typeID & self::COMMUNICATION_EMAIL) !== 0)
		{
			$result[] = self::COMMUNICATION_EMAIL;
		}
		return $result;
	}
	public static function getSupportedTypes($entityTypeID)
	{
		$entityTypeID = intval($entityTypeID);

		if($entityTypeID !== \CCrmOwnerType::Lead
			&& $entityTypeID !== \CCrmOwnerType::Contact
			&& $entityTypeID !== \CCrmOwnerType::Company)
		{
			return array();
		}

		$result = array();
		if($entityTypeID === \CCrmOwnerType::Lead || $entityTypeID === \CCrmOwnerType::Contact)
		{
			$result[] = self::PERSON;
		}
		if($entityTypeID === \CCrmOwnerType::Lead || $entityTypeID === \CCrmOwnerType::Company)
		{
			$result[] = self::ORGANIZATION;
		}

		$result[] = self::COMMUNICATION_PHONE;
		$result[] = self::COMMUNICATION_EMAIL;

		return $result;
	}
}