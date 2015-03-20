<?php
class CCrmEntityHelper
{
	private static $ENTITY_KEY = '/^\s*(L|D|C|CO)_([0-9]+)\s*$/i';
	private static $ITEMS = array();
	private static $DEFAULT_FEALD_TYPES = array('EMAIL', 'WEB', 'PHONE', 'IM');

	public static function IsEntityKey($key)
	{
		return preg_match(self::$ENTITY_KEY, strval($key)) === 1;
	}
	public static function ParseEntityKey($key, &$entityInfo)
	{
		if(preg_match(self::$ENTITY_KEY, strval($key), $match) !== 1)
		{
			$entityInfo = array();
			return false;
		}

		$entityTypeAbbr = strtoupper($match[1]);
		$entityID = intval($match[2]);
		$entityTypeID = CCrmOwnerType::ResolveID($entityTypeAbbr);
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);

		$entityInfo = array(
			'ENTITY_TYPE_ABBR' => $entityTypeAbbr,
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_TYPE_NAME' => $entityTypeName,
			'ENTITY_ID' => $entityID
		);
		return true;
	}
	public static function GetCached($sCacheName, $sKey)
	{
		return isset(self::$ITEMS[$sCacheName]) && isset(self::$ITEMS[$sCacheName][$sKey])
			? self::$ITEMS[$sCacheName][$sKey] : false;
	}
	public static function SetCached($sCacheName, $sKey, $value)
	{
		if(!isset(self::$ITEMS[$sCacheName]))
		{
			self::$ITEMS[$sCacheName] = array();
		}
		self::$ITEMS[$sCacheName][$sKey] = $value;
	}
	public static function RemoveCached($sCacheName, $sKey)
	{
		if(isset(self::$ITEMS[$sCacheName]))
		{
			unset(self::$ITEMS[$sCacheName]);
		}
	}
	public static function NormalizeUserFields(&$arFields, $entityID, $manager = null, $arOptions = null)
	{
		$entityID = strval($entityID);

		if(!$manager)
		{
			$manager = $GLOBALS['USER_FIELD_MANAGER'];
		}

		$userType = new CCrmUserType($manager, $entityID);
		$userType->PrepareUpdate($arFields, $arOptions);
	}
	public static function PrepareMultiFieldFilter(&$arFilter, $arFieldTypes = array(), $comparisonType = '%')
	{
		if(!is_array($arFieldTypes))
		{
			$arFieldTypes = array(strval($arFieldTypes));
		}

		if(!is_array($arFieldTypes) || count($arFieldTypes) === 0)
		{
			// Default field types
			$arFieldTypes = self::$DEFAULT_FEALD_TYPES;
		}

		$comparisonType = strval($comparisonType);
		if($comparisonType === '')
		{
			$comparisonType = '%';
		}

		if(isset($arFilter['FM']))
		{
			unset($arFilter['FM']);
		}

		foreach($arFieldTypes as $fieldType)
		{
			if(!isset($arFilter[$fieldType]))
			{
				continue;
			}

			$fieldValue = $arFilter[$fieldType];
			if(is_array($fieldValue))
			{

				$fieldValue = count($fieldValue) ? $fieldValue[0] : '';
			}

			if(!is_string($fieldValue))
			{
				$fieldValue = strval($fieldValue);
			}

			if($fieldValue === '')
			{
				unset($arFilter[$fieldType]);
				continue;
			}

			if(!isset($arFilter['FM']))
			{
				$arFilter['FM'] = array();
			}

			$arFilter['FM'][] = array(
				'TYPE_ID' => $fieldType,
				"{$comparisonType}VALUE" => $fieldValue
			);

			unset($arFilter[$fieldType]);
		}
	}
}
