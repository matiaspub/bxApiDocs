<?php
namespace Bitrix\Crm\Integration;
use Bitrix\Main;

class StorageManager
{
	public static function getDefaultTypeID()
	{
		return StorageType::getDefaultTypeID();
	}
	/**
	 * @param array $fileData
	 * @param string $siteID
	 * @return array|null
	 */
	public static function getFileInfo($fileID, $storageTypeID = 0, $checkPermissions = true)
	{
		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		if(!StorageType::isDefined($storageTypeID))
		{
			$storageTypeID = StorageType::getDefaultTypeID();
		}

		if($storageTypeID === StorageType::Disk)
		{
			return DiskManager::getFileInfo($fileID, $checkPermissions);
		}
		elseif($storageTypeID === StorageType::WebDav)
		{
			return \CCrmWebDavHelper::getElementInfo($fileID, $checkPermissions);
		}
		elseif($storageTypeID === StorageType::File)
		{
			$fileInfo = \CFile::GetFileArray($fileID);
			if(!is_array($fileInfo))
			{
				return null;
			}

			return array(
				'ID' => $fileID,
				'NAME' => isset($fileInfo['ORIGINAL_NAME']) ? $fileInfo['ORIGINAL_NAME'] : $fileID,
				'SIZE' => \CFile::FormatSize($fileInfo['FILE_SIZE'] ? $fileInfo['FILE_SIZE'] : 0),
				'VIEW_URL' => isset($fileInfo['SRC']) ? $fileInfo['SRC'] : ''
			);
		}

		throw new Main\NotSupportedException("Storage type: '{$storageTypeID}' is not supported in current context");
	}
	/**
	 * @param array $fileData
	 * @param string $siteID
	 * @return int|false
	 */
	public static function saveEmailAttachment(array $fileData, $storageTypeID = 0, $siteID = '')
	{
		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		if(!StorageType::isDefined($storageTypeID))
		{
			$storageTypeID = StorageType::getDefaultTypeID();
		}

		if($storageTypeID === StorageType::Disk)
		{
			return DiskManager::saveEmailAttachment($fileData, $siteID);
		}
		elseif($storageTypeID === StorageType::WebDav)
		{
			return \CCrmWebDavHelper::saveEmailAttachment($fileData, $siteID);
		}

		throw new Main\NotSupportedException("Storage type: '{$storageTypeID}' is not supported in current context");
	}
	/**
	 * @param array $fileData
	 * @param string $siteID
	 * @param array $params
	 * @return int|false
	 */
	public static function saveFile(array $fileData, $storageTypeID = 0, $siteID = '', $params = array())
	{
		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		if(!StorageType::isDefined($storageTypeID))
		{
			$storageTypeID = StorageType::getDefaultTypeID();
		}

		if($storageTypeID === StorageType::Disk)
		{
			return DiskManager::saveFile($fileData, $siteID, $params);
		}
		elseif($storageTypeID === StorageType::WebDav)
		{
			return \CCrmWebDavHelper::saveFile($fileData, $siteID, $params);
		}

		throw new Main\NotSupportedException("Storage type: '{$storageTypeID}' is not supported in current context");
	}
	/**
	 * @param int|array $fileID
	 * @param int $storageTypeID
	 * @return array|null
	 */
	public static function makeFileArray($fileID, $storageTypeID)
	{
		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		if($storageTypeID === StorageType::Disk)
		{
			if(!is_array($fileID))
			{
				return DiskManager::makeFileArray($fileID);
			}

			$result = array();
			foreach($fileID as $ID)
			{
				$ary = DiskManager::makeFileArray($ID);
				if(is_array($ary))
				{
					$result[] = $ary;
				}
			}
			return $result;
		}
		elseif($storageTypeID === StorageType::WebDav)
		{
			if(!is_array($fileID))
			{
				return \CCrmWebDavHelper::makeElementFileArray($fileID);
			}

			$result = array();
			foreach($fileID as $ID)
			{
				$ary = \CCrmWebDavHelper::makeElementFileArray($ID);
				if(is_array($ary))
				{
					$result[] = $ary;
				}
			}
			return $result;
		}
		elseif($storageTypeID === StorageType::File)
		{
			if(!is_array($fileID))
			{
				return \CFile::makeFileArray($fileID);
			}

			$result = array();
			foreach($fileID as $ID)
			{
				$ary = \CFile::makeFileArray($ID);
				if(is_array($ary))
				{
					$result[] = $ary;
				}
			}
			return $result;
		}

		throw new Main\NotSupportedException("Storage type: '{$storageTypeID}' is not supported in current context");
	}
	/**
	 * @param int $fileID
	 * @param int $storageTypeID
	 * @return string
	 */
	public static function getFileName($fileID, $storageTypeID)
	{
		if(!is_integer($fileID))
		{
			$storageTypeID = (int)$fileID;
		}

		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		if($storageTypeID === StorageType::Disk)
		{
			return DiskManager::getFileName($fileID);
		}
		elseif($storageTypeID === StorageType::WebDav)
		{
			$info = \CCrmWebDavHelper::GetElementInfo($fileID, false);
			return is_array($info) && isset($info['NAME']) ? $info['NAME'] : "[{$fileID}]";
		}
		elseif($storageTypeID === StorageType::File)
		{
			$info = \CFile::GetFileArray($fileID);
			return is_array($info) && isset($info['FILE_NAME']) ? $info['FILE_NAME'] : "[{$fileID}]";
		}

		throw new Main\NotSupportedException("Storage type: '{$storageTypeID}' is not supported in current context");
	}
	/**
	 * @param int $fileID
	 * @param int $storageTypeID
	 * @return boolean
	 */
	public static function checkFileReadPermission($fileID, $storageTypeID, $userID = 0)
	{
		if(!is_integer($fileID))
		{
			$storageTypeID = (int)$fileID;
		}

		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		if($storageTypeID === StorageType::WebDav)
		{
			return \CCrmWebDavHelper::CheckElementReadPermission($fileID, $userID);
		}
		elseif($storageTypeID === StorageType::Disk)
		{
			return DiskManager::checkFileReadPermission($fileID, $userID);
		}
		elseif($storageTypeID === StorageType::File)
		{
			return true;
		}

		throw new Main\NotSupportedException("Storage type: '{$storageTypeID}' is not supported in current context");
	}
	public static function filterFiles(array $fileIDs, $storageTypeID, $userID = 0)
	{
		if(!is_integer($storageTypeID))
		{
			$storageTypeID = (int)$storageTypeID;
		}

		$result = array();
		if($storageTypeID === StorageType::WebDav)
		{
			foreach($fileIDs as $fileID)
			{
				if(\CCrmWebDavHelper::CheckElementReadPermission($fileID, $userID))
				{
					$result[] = $fileID;
				}
			}
		}
		elseif($storageTypeID === StorageType::Disk)
		{
			foreach($fileIDs as $fielID)
			{
				if(DiskManager::checkFileReadPermission($fielID, $userID))
				{
					$result[] = $fielID;
				}
			}
		}
		elseif($storageTypeID === StorageType::File)
		{
			$result = $fileIDs;
		}

		return $result;
	}
}