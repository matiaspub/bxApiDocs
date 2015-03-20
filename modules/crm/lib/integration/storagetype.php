<?php
namespace Bitrix\Crm\Integration;
class StorageType
{
	const Undefined = 0;
	const File = 1;
	const WebDav = 2;
	const Disk = 3;

	private static $DEFAULT_TYPE_ID = null;

	public static function isDefined($typeID)
	{
		$typeID = (int)$typeID;
		return $typeID > self::Undefined && $typeID <= self::Disk;
	}
	public static function getDefaultTypeID()
	{
		if(self::$DEFAULT_TYPE_ID === null)
		{
			if(IsModuleInstalled('disk') && \COption::GetOptionString('disk', 'successfully_converted', 'N') === 'Y')
			{
				self::$DEFAULT_TYPE_ID = self::Disk;
			}
			elseif(IsModuleInstalled('webdav'))
			{
				self::$DEFAULT_TYPE_ID = self::WebDav;
			}
			else
			{
				self::$DEFAULT_TYPE_ID = self::File;
			}
		}
		return self::$DEFAULT_TYPE_ID;
	}
}