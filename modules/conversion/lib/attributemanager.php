<?php

namespace Bitrix\Conversion;

final class AttributeManager extends Internals\TypeManager
{
	static protected $event = 'OnGetAttributeTypes';
	static protected $types = array();
	static protected $ready = false;
	static protected $checkModule = true;

	static public function getGroupedTypes()
	{
		static $groupedTypes = array();

		if (! $groupedTypes)
		{
			foreach (self::getTypes() as $name => $type)
			{
				$groupedTypes[$type['GROUP']][$name] = $type;
			}
		}

		return $groupedTypes;
	}
}
