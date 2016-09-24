<?php

namespace Bitrix\Advertising\Internals;

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
			'advertising_banner_click_day' => array('MODULE' => 'advertising', 'GROUP' => 'day', 'NAME' => 'Banner click'),
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
			'advertising_banner_click' => array(
				'NAME'      => Loc::getMessage('ADVERTISING_CONVERSION_RATE_BANNER_CLICK_TITLE'),
				'SCALE'     => $scale,
				'MODULE'    => 'advertising',
				'SORT'      => 5000,
				'COUNTERS'  => array('conversion_visit_day', 'advertising_banner_click_day'),
				'CALCULATE' => function(array $counters)
				{
					$denominator = $counters['conversion_visit_day'] ?: 0;
					$numerator   = $counters['advertising_banner_click_day'] ?: 0;

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
	 * Increments conversion banner click day counter
	 *
	 * @param int $id Banner ID.
	 * @param array $fields Banner fields.
	 * @return void
	 */
	public static function onBannerClick($id, $fields)
	{
		if (Loader::includeModule('conversion'))
		{
			$context = DayContext::getInstance();
			$context->addDayCounter('advertising_banner_click_day', 1);
		}
	}

}
