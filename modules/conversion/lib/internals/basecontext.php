<?php

namespace Bitrix\Conversion\Internals;

use Bitrix\Conversion\CounterManager;
use Bitrix\Conversion\AttributeManager;

use Bitrix\Main\DB;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Config\Option;
use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentTypeException;

/** @internal */
class BaseContext
{
	const EMPTY_CONTEXT_ID = 0; // Context with no attributes.

	protected $id = null;
	protected $attributes = array();

	/** Add value to counter. If counter not exists set counter to value. Save to database.
	 * @param Date      $day   - counter date
	 * @param string    $name  - counter name
	 * @param int|float $value - number to add
	 * @throws ArgumentTypeException
	 * @throws SystemException
	 */
	public function addCounter(Date $day, $name, $value)
	{
		if (! is_string($name))
			throw new ArgumentTypeException('name', 'string');

		if (! is_numeric($value))
			throw new ArgumentTypeException('value', 'numeric');

		if (($id = $this->id) === null)
			throw new SystemException('Cannot add counter without context!');

		static $types;
		if (! $types)
		{
			$types = CounterManager::getTypes();
		}

		if (! $type = $types[$name])
			throw new SystemException('Undefined counter type!');

		if (! $type['ACTIVE'])
			return;

		// save to database

		$primary = array(
			'DAY'        => $day,
			'CONTEXT_ID' => $id,
			'NAME'       => $name
		);

		$data = array('VALUE' => new DB\SqlExpression('?# + ?f', 'VALUE', $value));

		$result = ContextCounterDayTable::update($primary, $data);

		if ($result->getAffectedRowsCount() === 0)
		{
			try
			{
				$result = ContextCounterDayTable::add($primary + array('VALUE' => $value));
			}
			catch (DB\SqlQueryException $e)
			{
				$result = ContextCounterDayTable::update($primary, $data);
			}
		}

		$result->isSuccess(); // TODO isSuccess
	}

	/** Set attribute with value.
	 * @param string                $name  - attribute name
	 * @param string|int|float|null $value - attribute value
	 * @throws ArgumentTypeException
	 * @throws SystemException
	 */
	public function setAttribute($name, $value = null)
	{
		if (! is_string($name))
			throw new ArgumentTypeException('name', 'string');

		if (! (is_scalar($value) || is_null($value)))
			throw new ArgumentTypeException('name', 'scalar');

		if ($this->id !== null)
			throw new SystemException('Cannot set attribute for existent context!');

		// TODO uncomment after seo 15.5.2 released
//		static $types;
//		if (! $types)
//		{
//			$types = AttributeManager::getTypes();
//		}
//		if (! $type = $types[$name])
//			throw new SystemException('Undefined attribute: "'.$name.'"!');

		// set attribute

		$this->attributes[$name] = $value;
	}

	/** Save context & attributes to database */
	protected function save()
	{
		if (($id =& $this->id) !== null)
			throw new SystemException('Cannot save existent context!');

		$id = self::EMPTY_CONTEXT_ID;

		if ($attributes = $this->attributes)
		{
			// leave only one attribute in group

			static $groupedTypes;

			if (! $groupedTypes)
			{
				$groupedTypes = AttributeManager::getGroupedTypes();
				unset($groupedTypes[null]);
			}

			foreach ($groupedTypes as $types)
			{
				$intersection = array_intersect_key($types, $attributes);

				if (count($intersection) > 1)
				{
					array_shift($intersection);

					foreach ($intersection as $name => $type)
					{
						unset($attributes[$name]);
					}
				}
			}

			// save to database

			$snapshot = self::getSnapshot($attributes);

			$query = array(
				'limit'  => 1,
				'select' => array('ID'),
				'filter' => array('=SNAPSHOT' => $snapshot),
			);

			if ($row = ContextTable::getList($query)->fetch())
			{
				$id = (int) $row['ID'];
			}
			elseif (Option::get('conversion', 'CONTEXT_TABLE') != 'locked') // TODO remove if
			{
				try
				{
					$result = ContextTable::add(array('SNAPSHOT' => $snapshot));

					if ($result->isSuccess())
					{
						$id = $result->getId();

						foreach ($attributes as $name => $value)
						{
							// TODO resetContext if not success and return null!!!
							$result = ContextAttributeTable::add(array(
								'CONTEXT_ID' => $id,
								'NAME'       => $name,
								'VALUE'      => (string) $value, // can be null!
							));
						}
					}
					else
					{
						throw new DB\SqlQueryException();
					}
				}
				catch (DB\SqlQueryException $e)
				{
					if ($row = ContextTable::getList($query)->fetch())
					{
						$id = (int) $row['ID'];
					}
				}
			}
		}
	}

	static private function getSnapshot(array $attributes)
	{
		$keys = array();

		foreach ($attributes as $name => $value)
		{
			$keys []= $name.':'.$value;
		}

		sort($keys);

		$string = implode(';', $keys);

		return md5($string).md5(strrev($string));
	}
}
