<?php

namespace Bitrix\Conversion;

final class RateManager extends Internals\TypeManager
{
	static protected $event = 'OnGetRateTypes';
	static protected $types = array();
	static protected $ready = false;
	static protected $checkModule = true;

	/** @internal */
	static public function getRatesCounters(array $types)
	{
		$counters = array();

		foreach ($types as $type)
		{
			$counters = array_merge($counters, $type['COUNTERS']);
		}

		return array_unique($counters);
	}

	static public function getRatesCalculated(array $types, array $counters)
	{
		$rates = array();

		foreach ($types as $name => $type)
		{
			$rates[$name] = $type['CALCULATE']($counters);
		}

		return $rates;
	}
}
