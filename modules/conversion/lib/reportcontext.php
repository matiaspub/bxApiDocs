<?php

namespace Bitrix\Conversion;

use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentTypeException;

final class ReportContext extends Internals\BaseContext
{
	public function unsetAttribute($name, $value = null)
	{
		if (! is_string($name))
			throw new ArgumentTypeException('name', 'string');

		if ($this->id !== null)
			throw new SystemException('Cannot modify existent context!');

		unset($this->attributes[$name]);
	}

	/** @deprecated */
	private function getCountersDeprecated(array $filter = null, array $steps = null)
	{
		$query  = new Query(Internals\ContextCounterDayTable::getEntity());

		if ($filter)
		{
			$query->setFilter($filter);
		}

		$i = 0;

		foreach ($this->attributes as $name => $value)
		{
			self::setAttributeFilter($query, '_conversion_attribute_'.(++ $i).'_', $name, $value);
		}

		$query->registerRuntimeField(null, new ExpressionField('VALUE_SUM', 'SUM(%s)', array('VALUE')));

		$query->setSelect(array('NAME', 'VALUE_SUM'));

		$query->addGroup('NAME');

		if ($steps) // TODO
		{
			$query->addGroup('DAY');
			$query->addSelect('DAY');
		}

		$result = $query->exec();

		$counters = array();

		if ($steps)
		{
			$steps = array(); // TODO

			while ($row = $result->fetch())
			{
				$name = $row['NAME'];
				$value = $row['VALUE_SUM'];

				$counters[$name] += $value;

				$steps[$row['DAY']->format('Y-m-d')][$name] = $value;
			}

			$counters['STEPS'] = $steps;
		}
		else
		{
			while ($row = $result->fetch())
			{
				$counters[$row['NAME']] = $row['VALUE_SUM'];
			}
		}

		return $counters;
	}

	/** @deprecated */
	public function getRatesDeprecated(array $rateTypes, array $filter = null, array $steps = null)
	{
		if (! $filter)
		{
			$filter = array();
		}

		$filter['=NAME'] = RateManager::getRatesCounters($rateTypes);

		$counters = $this->getCountersDeprecated($filter, $steps);

		return self::getRatesCommonDeprecated($rateTypes, $counters, $steps);
	}

	static private function getRatesCommonDeprecated(array $rateTypes, array $counters, array $steps = null)
	{
		$rates = RateManager::getRatesCalculated($rateTypes, $counters);

		if ($steps)
		{
			foreach ($rates as & $rate)
			{
				$rate['STEPS'] = array();
			}
			unset ($rate);

			foreach ($counters['STEPS'] as $step => $stepCounters)
			{
				foreach (RateManager::getRatesCalculated($rateTypes, $stepCounters) as $name => $rate)
				{
					$rates[$name]['STEPS'][$step] = $rate['RATE'];
				}
			}
		}

		return $rates;
	}

	// splits

	private function getSplitCountersDeprecated(array $splits, array $filter = null, array $steps = null)
	{
		$result = array();

		$totalCounters = $this->getCountersDeprecated($filter, $steps);
		$otherCounters = $totalCounters;

		foreach ($splits as $splitKey => $attribute)
		{
			if ($attribute['NAME'])
			{
				$this->setAttribute($attribute['NAME'], $attribute['VALUE']);

				$counters = $this->getCountersDeprecated($filter, $steps);

				$result[$splitKey] = $counters;

				self::subtructCounters($otherCounters, $counters, $steps);

				$this->unsetAttribute($attribute['NAME'], $attribute['VALUE']);
			}
		}

		$result['other'] = $otherCounters > 0 ? $otherCounters : 0; // can be -0 or maybe -something
		$result['total'] = $totalCounters;

		return $result;
	}

	private static function subtructCounters(array & $one, array $two, array $steps = null)
	{
		if ($steps)
		{
			$oneSteps = $one['STEPS'];
			$twoSteps = $two['STEPS'];

			unset($one['STEPS']);

			if ($oneSteps && $twoSteps)
			{
				foreach ($oneSteps as $key => & $oneStep)
				{
					if ($twoStep = $twoSteps[$key])
					{
						self::subtructCountersOnce($oneStep, $twoStep);
					}
				}
			}

			self::subtructCountersOnce($one, $two);

			$one['STEPS'] = $oneSteps;
		}
		else
		{
			self::subtructCountersOnce($one, $two);
		}
	}

	private static function subtructCountersOnce(array & $one, array $two)
	{
		foreach ($one as $key => & $value)
		{
			$value -= $two[$key];
		}
	}

	/** @deprecated */
	public function getSplitRatesDeprecated(array $splits, array $rateTypes, array $filter = null, array $steps = null)
	{
		if (! $filter)
		{
			$filter = array();
		}

		$filter['=NAME'] = RateManager::getRatesCounters($rateTypes);

		$splitCounters = $this->getSplitCountersDeprecated($splits, $filter, $steps);

		$result = array();

		foreach ($splitCounters as $split => $counters)
		{
			$result[$split] = $this->getRatesCommonDeprecated($rateTypes, $counters, $steps);
		}

		return $result;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	static private function setAttributeFilter(Query $query, $field, $name, $value = null)
	{
		$query->registerRuntimeField(null, new ReferenceField($field, Internals\ContextAttributeTable::getEntity(),
			array('=this.CONTEXT_ID' => 'ref.CONTEXT_ID'),
			array('join_type' => 'INNER')
		));

		$query->addFilter("=$field.NAME" , $name);

		if ($value !== null)
		{
			$query->addFilter("=$field.VALUE", $value);
		}
	}

	public function getCounters(array $parameters = array())
	{
		$query  = new Query(Internals\ContextCounterDayTable::getEntity());

		if ($filter = $parameters['filter'])
		{
			$query->setFilter($filter);
		}

		$i = 0;

		foreach ($this->attributes as $name => $value)
		{
			self::setAttributeFilter($query, '_conversion_attribute_'.(++ $i).'_', $name, $value);
		}

		$query->registerRuntimeField(null, new ExpressionField('VALUE_SUM', 'SUM(%s)', array('VALUE')));

		$splitNames = array();

		if ($split = $parameters['split'])
		{
			if (! is_array($split))
				throw new ArgumentTypeException('parameters[split]', 'array');

			foreach ($split as $name => $value)
			{
				switch ($name)
				{
					case 'ATTRIBUTE_NAME':

						if (! is_string($value))
							throw new ArgumentTypeException('parameters[split][ATTRIBUTE_NAME]', 'string');

						self::setAttributeFilter($query, 'split_attribute', $value);
						$query->addGroup('split_attribute.VALUE');
						$query->addSelect('split_attribute.VALUE', 'ATTRIBUTE_VALUE');
						$splitNames []= 'ATTRIBUTE_VALUE';

						break;

					default: throw new ArgumentTypeException('parameters[split]['.$name.']', 'not implemented');
				}
			}
		}

		$query->addGroup('NAME');
		$query->addSelect('NAME');
		$query->addSelect('VALUE_SUM');

		$result = $query->exec();

//		return $result->fetchAll();

		$counters = array();

		while ($row = $result->fetch())
		{
			$level =& $counters;

			foreach ($splitNames as $name)
			{
				if (! $level =& $level[$row[$name]])
				{
					$level = array();
				}
			}

			$level[$row['NAME']] = $row['VALUE_SUM'];
		}

		return $counters;
	}

	public function getRates(array $rateTypes, array $parameters = array())
	{
		if (! $filter =& $parameters['filter'])
		{
			$filter = array();
		}

		$filter['=NAME'] = RateManager::getRatesCounters($rateTypes);

		return self::getRatesRecursive($rateTypes, $this->getCounters($parameters), ($s = $parameters['split']) ? count($s) : 0);
	}

	static private function getRatesRecursive(array $rateTypes, array $counters, $level)
	{
		if ($level > 0)
		{
			$level--;

			$rates = array();

			foreach ($counters as $k => $v)
			{
				$rates[$k] = self::getRatesRecursive($rateTypes, $v, $level);
			}

			return $rates;
		}
		else
		{
			return RateManager::getRatesCalculated($rateTypes, $counters);
		}
	}
}