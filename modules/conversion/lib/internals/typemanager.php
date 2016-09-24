<?php

namespace Bitrix\Conversion\Internals;

use Bitrix\Conversion\Config;
use Bitrix\Main\EventManager;
use Bitrix\Main\SystemException;

/** @internal */
abstract class TypeManager
{
	/** @internal */
	static public function getTypesInternal()
	{
		if (! $types =& static::$types)
		{
			$event       = static::$event;
			$checkModule = static::$checkModule;

			foreach (EventManager::getInstance()->findEventHandlers('conversion', $event) as $handler)
			{
				$result = ExecuteModuleEventEx($handler);

				if (! is_array($result))
					throw new SystemException('Not array returned from: '.print_r($handler, true));

				foreach ($result as $name => $type)
				{
					if (! is_array($type))
						throw new SystemException('Not array in: '.$event.'()['.$name.'] => '.print_r($handler, true));

					if ($checkModule)
					{
						if (! $type['MODULE'])
							throw new SystemException('No [MODULE] in: '.$event.'()['.$name.'] => '.print_r($handler, true));
					}

					if ($types[$name])
						throw new SystemException('Duplicate in: '.$event.'()['.$name.'] => '.print_r($handler, true));

					$types[$name] = $type;
				}
			}
		}

		return $types;
	}

	static public function getTypes(array $filter = null)
	{
		if (! $types =& static::$types)
		{
			static::getTypesInternal();
		}

		if (! static::$ready)
		{
			static::$ready = true;

			uasort($types, function ($a, $b)
			{
				$a = $a['SORT'];
				$b = $b['SORT'];

				return $a < $b ? -1 : ($a > $b ? 1 : 0);
			});

			if (static::$checkModule)
			{
				$modules = Config::getModules();
				foreach ($types as & $type)
				{
					$module = $modules[$type['MODULE']];
					$type['ACTIVE'] = $module && $module['ACTIVE'];
				}
				unset($type);
			}
		}

		if ($filter)
		{
			$count = count($filter);

			return array_filter($types, function (array $type) use ($count, $filter)
			{
				return $count == count(array_intersect_assoc($filter, $type));
			});
		}
		else
		{
			return $types;
		}
	}

	/** @deprecated */
	static public function isTypeActive($name)
	{
		$types = static::getTypes();
		$type = $types[$name];
		return $type && $type['ACTIVE'];
	}
}