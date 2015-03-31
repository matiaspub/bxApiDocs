<?php
namespace Bitrix\Crm\Format;
use Bitrix\Main;
class PersonNameFormatter
{
	const Undefined = 0;
	const Dflt = 1;
	const FirstLast = 2;
	const FirstSecondLast = 3;
	const LastFirst = 4;
	const LastFirstSecond = 5;
	//const Custom = 100;

	const FirstLastFormat = '#NAME# #LAST_NAME#';
	const FirstSecondLastFormat = '#NAME# #SECOND_NAME# #LAST_NAME#';
	const LastFirstFormat = '#LAST_NAME# #NAME#';
	const LastFirstSecondFormat = '#LAST_NAME# #NAME# #SECOND_NAME#';

	private static $FORMAT_ID = null;
	private static $FORMAT_STRING = null;
	private static $ALL_DESCRIPTIONS = null;

	public static function isDefined($formatID)
	{
		if(!is_int($formatID))
		{
			$formatID = intval($formatID);
		}
		return $formatID > self::Undefined && $formatID <= self::LastFirstSecond;
	}

	public static function getFormatID()
	{
		if(self::$FORMAT_ID !== null)
		{
			return self::$FORMAT_ID;
		}

		$formatID = intval(\COption::GetOptionString('crm', 'prsn_nm_frmt_id', 0));
		if(!self::isDefined($formatID))
		{
			$formatID = self::Dflt;
		}
		self::$FORMAT_ID = $formatID;
		return self::$FORMAT_ID;
	}
	public static function setFormatID($formatID)
	{
		if(!is_int($formatID))
		{
			throw new Main\ArgumentTypeException('formatID', 'integer');
		}

		if(!self::isDefined($formatID))
		{
			return false;
		}

		self::$FORMAT_ID = $formatID;
		self::$FORMAT_STRING = null;
		if($formatID !== self::Dflt)
		{
			return \COption::SetOptionString('crm', 'prsn_nm_frmt_id', $formatID);
		}
		// Do not store default format ID
		\COption::RemoveOption('crm', 'prsn_nm_frmt_id');
		return true;
	}
	public static function getAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			IncludeModuleLangFile(__FILE__);

			self::$ALL_DESCRIPTIONS = array(
				self::Dflt => GetMessage('CRM_PRSN_NM_FRMT_DEFAULT'),
				self::FirstLast => GetMessage('CRM_PRSN_NM_FRMT_FIRST_LAST'),
				self::FirstSecondLast => GetMessage('CRM_PRSN_NM_FRMT_FIRST_SECOND_LAST'),
				self::LastFirst => GetMessage('CRM_PRSN_NM_FRMT_LAST_FIRST'),
				self::LastFirstSecond => GetMessage('CRM_PRSN_NM_FRMT_LAST_FIRST_SECOND')
			);
		}
		return self::$ALL_DESCRIPTIONS;
	}
	public static function getFormatByID($formatID)
	{
		$formatID = intval($formatID);
		switch($formatID)
		{
			case self::FirstLast:
				return self::FirstLastFormat;
			case self::FirstSecondLast:
				return self::FirstSecondLastFormat;
			case self::LastFirst:
				return self::LastFirstFormat;
			case self::LastFirstSecond:
				return self::LastFirstSecondFormat;
		}
		return \CSite::GetNameFormat(false);
	}
	public static function getFormat()
	{
		if(self::$FORMAT_STRING !== null)
		{
			return self::$FORMAT_STRING;
		}

		$formatID = self::getFormatID();
		switch($formatID)
		{
			case self::FirstLast:
				self::$FORMAT_STRING = self::FirstLastFormat;
				break;
			case self::FirstSecondLast:
				self::$FORMAT_STRING = self::FirstSecondLastFormat;
				break;
			case self::LastFirst:
				self::$FORMAT_STRING = self::LastFirstFormat;
				break;
			case self::LastFirstSecond:
				self::$FORMAT_STRING = self::LastFirstSecondFormat;
				break;
			default:
				self::$FORMAT_STRING = \CSite::GetNameFormat(false);
		}
		return self::$FORMAT_STRING;
	}
	public static function tryParseName($name, $formatID, &$nameParts)
	{
		if(!is_string($name) || $name === '')
		{
			return false;
		}

		$formatID = intval($formatID);
		if(!self::isDefined($formatID))
		{
			throw new Main\NotSupportedException("Format: '{$formatID}' is not supported in current context");
		}

		if($formatID === self::FirstSecondLast || $formatID === self::LastFirstSecond)
		{
			if(preg_match('/^\s*(\S+)\s+(\S+)\s+(\S+)\s*$/', $name, $m) === 1)
			{
				if(!is_array($nameParts))
				{
					$nameParts = array();
				}

				if($formatID === self::FirstSecondLast)
				{
					$nameParts['NAME'] = $m[1];
					$nameParts['SECOND_NAME'] = $m[2];
					$nameParts['LAST_NAME'] = $m[3];
				}
				else //$formatID === self::LastFirstSecond
				{
					$nameParts['LAST_NAME'] = $m[1];
					$nameParts['NAME'] = $m[2];
					$nameParts['SECOND_NAME'] = $m[3];
				}

				return true;
			}
		}

		if(preg_match('/^\s*(\S+)\s+(\S+)\s*$/', $name, $m) === 1)
		{
			if(!is_array($nameParts))
			{
				$nameParts = array();
			}

			if($formatID === self::FirstLast || $formatID === self::FirstSecondLast)
			{
				$nameParts['NAME'] = $m[1];
				$nameParts['SECOND_NAME'] = '';
				$nameParts['LAST_NAME'] = $m[2];
			}
			else //$formatID === self::LastFirst || $formatID === self::LastFirstSecond
			{
				$nameParts['LAST_NAME'] = $m[1];
				$nameParts['NAME'] = $m[2];
				$nameParts['SECOND_NAME'] = '';
			}

			return true;
		}

		return false;
	}
}