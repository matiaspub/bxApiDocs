<?php
class CCrmFileProxy
{
	public static function WriteFileToResponse($ownerTypeID, $ownerID, $fieldName, $fileID, &$errors, $options = array())
	{
		$ownerTypeID = intval($ownerTypeID);
		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);
		$ownerID = intval($ownerID);
		$fieldName = strval($fieldName);
		$fileID = intval($fileID);
		$options = is_array($options) ? $options : array();

		if(!CCrmOwnerType::IsDefined($ownerTypeID) || $ownerID <= 0 || $fieldName === '' || $fileID <= 0)
		{
			$errors[] = 'File not found';
			return false;
		}

		$authToken = isset($options['oauth_token']) ? strval($options['oauth_token']) : '';
		if($authToken !== '')
		{
			$authData = array();
			if(!(CModule::IncludeModule('rest')
				&& CRestUtil::checkAuth($authToken, CCrmRestService::SCOPE_NAME, $authData)
				&& CRestUtil::makeAuth($authData)))
			{
				$errors[] = 'Access denied.';
				return false;
			}
		}

		if(!CCrmPerms::IsAdmin())
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
			$attrs = $userPermissions->GetEntityAttr($ownerTypeName, $ownerID);
			if($userPermissions->HavePerm($ownerTypeName, BX_CRM_PERM_NONE, 'READ')
				|| !$userPermissions->CheckEnityAccess($ownerTypeName, 'READ', isset($attrs[$ownerID]) ? $attrs[$ownerID] : array()))
			{
				$errors[] = 'Access denied.';
				return false;
			}
		}

		$isDynamic = isset($options['is_dynamic']) ? (bool)$options['is_dynamic'] : true;
		if($isDynamic)
		{
			$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(
				CCrmOwnerType::ResolveUserFieldEntityID($ownerTypeID),
				$ownerID,
				LANGUAGE_ID
			);

			$field = is_array($userFields) && isset($userFields[$fieldName]) ? $userFields[$fieldName] : null;

			if(!(is_array($field) && $field['USER_TYPE_ID'] === 'file'))
			{
				$errors[] = 'File not found';
				return false;
			}

			$fileIDs = isset($field['VALUE'])
				? (is_array($field['VALUE'])
					? $field['VALUE']
					: array($field['VALUE']))
				: array();

			//The 'strict' flag must be 'false'. In MULTIPLE mode value is an array of integers. In SIGLE mode value is a string.
			if(!in_array($fileID, $fileIDs, false))
			{
				$errors[] = 'File not found';
				return false;
			}

			return self::InnerWriteFileToResponse($fileID, $errors, $options);
		}
		else
		{
			$fieldsInfo = isset($options['fields_info']) ? $options['fields_info'] : null;
			if(!is_array($fieldsInfo))
			{
				$fieldsInfo = CCrmOwnerType::GetFieldsInfo($ownerTypeID);
			}

			$fieldInfo = is_array($fieldsInfo) && isset($fieldsInfo[$fieldName]) ? $fieldsInfo[$fieldName] : array();
			$fieldInfoType = isset($fieldInfo['TYPE']) ? $fieldInfo['TYPE'] : '';

			if($fieldInfoType !== 'file')
			{
				$errors[] = 'File not found';
				return false;
			}

			if($fileID !== CCrmOwnerType::GetFieldIntValue($ownerTypeID, $ownerID, $fieldName))
			{
				$errors[] = 'File not found';
				return false;
			}

			return self::InnerWriteFileToResponse($fileID, $errors, $options);
		}
	}

	public static function WriteEventFileToResponse($eventID, $fileID, &$errors, $options = array())
	{
		$eventID = intval($eventID);
		$fileID = intval($fileID);

		if($eventID <= 0 || $fileID <= 0)
		{
			$errors[] = 'File not found';
			return false;
		}

		//Get event file IDs and check permissions
		$dbResult = CCrmEvent::GetListEx(
			array(),
			array(
				'=ID' => $eventID
				//'CHECK_PERMISSIONS' => 'Y' //by default
			),
			false,
			false,
			array('ID', 'FILES'),
			array()
		);

		$event = $dbResult ? $dbResult->Fetch() : null;

		if(!$event)
		{
			$errors[] = 'File not found';
			return false;
		}

		if(is_array($event['FILES']))
		{
			$eventFiles = $event['FILES'];
		}
		elseif(is_string($event['FILES']) && $event['FILES'] !== '')
		{
			$eventFiles = unserialize($event['FILES']);
		}
		else
		{
			$eventFiles = array();
		}

		if(
			empty($eventFiles)
			|| !is_array($eventFiles)
			|| !in_array($fileID, $eventFiles, true)
		)
		{
			$errors[] = 'File not found';
			return false;
		}

		return self::InnerWriteFileToResponse($fileID, $errors, $options);
	}

	private static function InnerWriteFileToResponse($fileID, &$errors, $options = array())
	{
		$fileInfo = CFile::GetFileArray($fileID);
		if(!is_array($fileInfo))
		{
			$errors[] = 'File not found';
			return false;
		}

		$options = is_array($options) ? $options : array();
		// Ñrutch for CFile::ViewByUser. Waiting for main 14.5.2
		$options['force_download'] = true;
		set_time_limit(0);
		CFile::ViewByUser($fileInfo, $options);

		return true;
	}

	public static function TryResolveFile(&$path, &$file, $arOptions = array())
	{
		$result = null;
		if(is_numeric($path))
		{
			if(is_array($arOptions) && isset($arOptions['ENABLE_ID']) && $arOptions['ENABLE_ID'])
			{
				$result = CFile::MakeFileArray($path);
			}
		}
		elseif(is_string($path))
		{
			$absPath = CCrmUrlUtil::ToAbsoluteUrl($path);
			//Parent directories and not secure URLs are not allowed.
			if($absPath !== '' && preg_match('/[\/,\\\\]\.\.[\/,\\\\]/', $absPath) !== 1 && CCrmUrlUtil::IsSecureUrl($absPath))
			{
				$result = CFile::MakeFileArray($absPath);
			}
		}

		if(is_array($result))
		{
			$result['MODULE_ID'] = 'crm';
			$file = $result;
			return true;
		}

		return false;
	}
}
?>
