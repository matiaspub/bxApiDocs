<?php

namespace Bitrix\Blog\Internals;

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
			'blog_post_add_day' => array('MODULE' => 'blog', 'GROUP' => 'day', 'NAME' => 'Post'),
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
			'blog_post' => array(
				'NAME'      => Loc::getMessage('BLOG_CONVERSION_RATE_POST_TITLE'),
				'SCALE'     => $scale,
				'MODULE'    => 'blog',
				'SORT'      => 4000,
				'COUNTERS'  => array('conversion_visit_day', 'blog_post_add_day'),
				'CALCULATE' => function(array $counters)
				{
					$denominator = $counters['conversion_visit_day'] ?: 0;
					$numerator   = $counters['blog_post_add_day'] ?: 0;

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
	 * Increments conversion post day counter
	 *
	 * @param int $id Post ID.
	 * @param array $fields Post fields.
	 * @return void
	 */
	public static function onPostAdd($id, $fields)
	{
		if (Loader::includeModule('conversion'))
		{
			$context = DayContext::getInstance();
			$context->addDayCounter('blog_post_add_day', 1);
		}
	}

}
