<?php

namespace Bitrix\Conversion;

use Bitrix\Main\EventManager;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Config\Option;

/** @internal */
final class GeneratorContext extends Internals\BaseContext
{
	private function setAttributes(array $attributes)
	{
		foreach ($attributes as $name => $value)
		{
			self::setAttribute($name, $value);
		}
	}

	private static function appendCounters(array & $one, array $two)
	{
		foreach ($two as $name => $value)
		{
			if ($counter =& $one[$name])
			{
				$counter += $value;
			}
			else
			{
				$counter = $value;
			}
		}
	}

	private static function appendDayCounters(array & $one, array $two)
	{
		foreach ($two as $day => $twoCounters)
		{
			if ($oneCounters =& $one[$day])
			{
				self::appendCounters($oneCounters, $twoCounters);
			}
			else
			{
				$oneCounters = $twoCounters;
			}
		}
	}

	static public function generateInitialData(Date $from)
	{
		if (   ($to = Option::get('conversion', 'START_DATE_TIME', 'undefined')) != 'undefined'
			&& DateTime::isCorrect($to, 'Y-m-d H:i:s')
			&& ($to = new DateTime($to, 'Y-m-d H:i:s'))
			&&  $to->format('Y-m-d H:i:s') > $from->format('Y-m-d H:i:s')
			&& Option::get('conversion', 'GENERATE_INITIAL_DATA', 'undefined') == 'undefined')
		{
			Option::set('conversion', 'GENERATE_INITIAL_DATA', 'generated');

			$context = new self;

			// generate data

			$data = array();

			foreach (EventManager::getInstance()->findEventHandlers('conversion', 'OnGenerateInitialData') as $handler)
			{
				$result = ExecuteModuleEventEx($handler, array($from, $to)); // TODO validate

				foreach ($result as $row)
				{
					$context->id = null;
					$context->attributes = array();
					$context->setAttributes($row['ATTRIBUTES']);
					$context->save();

					if ($dayCounters =& $data[$context->id])
					{
						self::appendDayCounters($dayCounters, $row['DAY_COUNTERS']);
					}
					else
					{
						$dayCounters = $row['DAY_COUNTERS'];
					}
				}
			}

			unset($dayCounters);

			// save data to database

			$numerators = CounterManager::getTypes(array('GROUP' => 'day'));
			unset($numerators['conversion_visit_day']);

			foreach ($data as $id => $dayCounters)
			{
				$context->id = $id;

				foreach ($dayCounters as $day => $counters)
				{
					$day = new Date($day, 'Y-m-d');

					$visitSum      = 0;
					$visitQuantity = 0;

					unset($counters['conversion_visit_day']);

					foreach ($counters as $name => $value)
					{
						$context->addCounter($day, $name, $value);

						if ($numerators[$name])
						{
							$visitSum      += $value;
							$visitQuantity += 1;
						}
					}

					$context->addCounter($day, 'conversion_visit_day', $visitQuantity ? round($visitSum / $visitQuantity * 100) + 1 : 1);
				}
			}
		}
	}
}
