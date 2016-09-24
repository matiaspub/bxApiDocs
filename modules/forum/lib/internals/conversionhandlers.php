<?php

namespace Bitrix\Forum\Internals;

use Bitrix\Conversion\Config;
use Bitrix\Conversion\DayContext;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class ConversionHandlers
{

	/**
	 * Returns conversion counter types
	 *
	 * @return array
	 */
	public static function onGetCounterTypes()
	{
		return array(
			'forum_post_add_day'  => array('MODULE' => 'forum', 'GROUP' => 'day', 'NAME' => 'Post'),
			'forum_topic_add_day' => array('MODULE' => 'forum', 'GROUP' => 'day', 'NAME' => 'Topic'),
		);
	}

	/**
	 * Returns conversion rate types
	 *
	 * @return array
	 */
	public static function onGetRateTypes()
	{
		$scale = array(0.5, 1, 1.5, 2, 5);

		return array(
			'forum_topic' => array(
				'NAME'      => Loc::getMessage('FORUM_CONVERSION_RATE_TOPIC_TITLE'),
				'SCALE'     => $scale,
				'MODULE'    => 'forum',
				'SORT'      => 3000,
				'COUNTERS'  => array('conversion_visit_day', 'forum_topic_add_day'),
				'CALCULATE' => function(array $counters)
				{
					$denominator = $counters['conversion_visit_day'] ?: 0;
					$numerator   = $counters['forum_topic_add_day'] ?: 0;

					return array(
						'DENOMINATOR' => $denominator,
						'NUMERATOR'   => $numerator,
						'RATE'        => $denominator ? $numerator / $denominator : 0,
					);
				},
			),
			'forum_post' => array(
				'NAME'      => Loc::getMessage('FORUM_CONVERSION_RATE_POST_TITLE'),
				'SCALE'     => $scale,
				'MODULE'    => 'forum',
				'SORT'      => 3100,
				'COUNTERS'  => array('conversion_visit_day', 'forum_post_add_day'),
				'CALCULATE' => function(array $counters)
				{
					$denominator = $counters['conversion_visit_day'] ?: 0;
					$numerator   = $counters['forum_post_add_day'] ?: 0;

					return array(
						'DENOMINATOR' => $denominator,
						'NUMERATOR'   => $numerator,
						'RATE'        => $denominator ? $numerator / $denominator : 0,
					);
				},
			),
		);
	}

	/**
	 * Increments conversion topic day counter
	 *
	 * @param int $id Topic ID.
	 * @param array $fields Topic fields.
	 * @return void
	 */
	public static function onTopicAdd($id, $fields)
	{
		if (Loader::includeModule('conversion'))
		{
			$context = DayContext::getInstance();
			$context->addDayCounter('forum_topic_add_day', 1);
		}
	}

	/**
	 * Increments conversion post day counter
	 *
	 * @param int $id Topic ID.
	 * @param array $message Message fields.
	 * @param array $topicInfo Message TOPIC_INFO field.
	 * @param array $forumInfo Message FORUM_INFO field.
	 * @param array $fields Message fields used to insert.
	 * @return void
	 */
	public static function onMessageAdd($id, $message, $topicInfo, $forumInfo, $fields)
	{
		if (Loader::includeModule('conversion'))
		{
			$context = DayContext::getInstance();
			$context->addDayCounter('forum_post_add_day', 1);
		}
	}

}
