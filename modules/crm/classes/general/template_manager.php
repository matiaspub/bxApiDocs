<?php
class CCrmTemplateManager
{
	private static $ADAPTERS = null;

	private static function PrepareAdapters()
	{
		if(self::$ADAPTERS !== null)
		{
			return self::$ADAPTERS;
		}

		self::$ADAPTERS = array(
			new CCrmTemplateAdapter()
		);

		return self::$ADAPTERS;
	}

	public static function GetAllMaps()
	{
		$result = array();
		$adapters = self::PrepareAdapters();
		foreach($adapters as $adapter)
		{
			$types = $adapter->GetSupportedTypes();
			foreach($types as $typeID)
			{
				$map = $adapter->GetTypeMap($typeID);
				if($map)
				{
					$result[] = &$map;
				}
				unset($map);
			}
		}
		return $result;
	}

	private static function ResolveMapper($entityTypeID, $entityID)
	{
		$adapters = self::PrepareAdapters();
		foreach($adapters as $adapter)
		{
			if($adapter->IsTypeSupported($entityTypeID))
			{
				return $adapter->CreateMapper($entityTypeID, $entityID);
			}
		}
		return null;
	}

	public static function PrepareTemplate($template, $entityTypeID, $entityID, $contentTypeID = 0)
	{
		$template = strval($template);
		if($template === '')
		{
			return '';
		}

		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		$entityID = intval($entityID);
		if($entityTypeName === '' || $entityID <= 0)
		{
			return $template;
		}

		$matches = null;
		$result = preg_match_all('/#'.$entityTypeName.'\.[^#]+#/i', $template, $matches, PREG_OFFSET_CAPTURE);
		if(!(is_int($result) && $result > 0))
		{
			return $template;
		}

		$mapper = self::ResolveMapper($entityTypeID, $entityID);
		if($mapper === null)
		{
			return $template;
		}

		$contentTypeID = intval($contentTypeID);
		if(!CCrmContentType::IsDefined($contentTypeID))
		{
			$contentTypeID = CCrmContentType::PlainText;
		}
		$mapper->SetContentType($contentTypeID);

		$replaceKeys = array();
		$replaceValues = array();

		foreach($matches[0] as &$match)
		{
			$key = $match[0];
			if(isset($replacements[$key]))
			{
				continue;
			}

			$path = substr($key, 1, strlen($key) - 2);
			$replaceKeys[] = $key;
			$replaceValues[] = $mapper->MapPath($path);
		}
		unset($match);

		return !empty($replaceKeys) ? str_replace($replaceKeys, $replaceValues, $template) : $template;
	}
}