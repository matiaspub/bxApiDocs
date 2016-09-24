<?php
namespace Bitrix\Crm\Integration;
use Bitrix\Main\Localization\Loc;
class StorageFileType
{
	const Undefined = 0;
	const EmailAttachment = 1;
	const CallRecord = 2;
	const Rest = 3;

	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = (int)$typeID;
		return $typeID >= self::EmailAttachment && $typeID <= self::Rest;
	}

	public static function getFolderName($typeID)
	{
		Loc::loadMessages(__FILE__);
		if($typeID === self::EmailAttachment)
		{
			return Loc::getMessage('CRM_STORAGE_EMAIL');
		}
		elseif($typeID === self::CallRecord)
		{
			return Loc::getMessage('CRM_STORAGE_CALL_RECORD');
		}
		elseif($typeID === self::Rest)
		{
			return Loc::getMessage('CRM_STORAGE_APPLICATION');
		}
		return '';
	}

	public static function getFolderXmlID($typeID)
	{
		if($typeID === self::EmailAttachment)
		{
			return 'CRM_EMAIL_ATTACHMENTS';
		}
		elseif($typeID === self::CallRecord)
		{
			return 'CRM_CALL_RECORDS';
		}
		elseif($typeID === self::Rest)
		{
			return 'CRM_REST';
		}
		return '';
	}
}