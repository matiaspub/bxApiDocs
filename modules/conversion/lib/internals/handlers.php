<?php

namespace Bitrix\Conversion\Internals;

use Bitrix\Conversion\DayContext;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetLocation;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/** @internal */
final class Handlers
{
	static public function onGetCounterTypes()
	{
		return array(
			'conversion_visit_day' => array('MODULE' => 'conversion', 'GROUP' => 'day', 'NAME' => 'Day visits'),
		);
	}

	static public function onGetAttributeTypes()
	{
		$userAgent = $_SERVER['HTTP_USER_AGENT'];

		// http://stackoverflow.com/questions/18070154/get-operating-system-info-with-php
		$operatingSystemValues = array(
			'windows'    => array('NAME' => 'Windows'   , 'REGEX' => '/windows|win98|win95|win16/i'     ),
			'macos'      => array('NAME' => 'Mac OS'    , 'REGEX' => '/macintosh|mac os x|mac_powerpc/i'),
			'android'    => array('NAME' => 'Android'   , 'REGEX' => '/android/i'                       ),
			'linux'      => array('NAME' => 'Linux'     , 'REGEX' => '/linux|ubuntu/i'                  ),
			'ios'        => array('NAME' => 'iOS'       , 'REGEX' => '/iphone|ipod|ipad/i'              ),
			'blackberry' => array('NAME' => 'BlackBerry', 'REGEX' => '/blackberry/i'                    ),
			'webos'      => array('NAME' => 'Web OS'    , 'REGEX' => '/webos/i'                         ),
		);

		$browser = null; // TODO hack

		$browserValues = array(
			'ie'        => array('NAME' => 'Internet Explorer', 'REGEX' => '/msie/i'     ),
			'firefox'   => array('NAME' => 'Firefox',           'REGEX' => '/firefox/i'  ),
			'chrome'    => array('NAME' => 'Chrome',            'REGEX' => '/chrome/i'   ),
			'opera'     => array('NAME' => 'Opera',             'REGEX' => '/opera/i'    ),
			'safari'    => array('NAME' => 'Safari',            'REGEX' => '/safari/i'   ),
			'netscape'  => array('NAME' => 'Netscape',          'REGEX' => '/netscape/i' ),
			'maxthon'   => array('NAME' => 'Maxthon',           'REGEX' => '/maxthon/i'  ),
			'konqueror' => array('NAME' => 'Konqueror',         'REGEX' => '/konqueror/i'),
			'mobile'    => array('NAME' => 'Handheld',          'REGEX' => '/mobile/i'   ),
		);

		$detector = new MobileDetect;

		$searchEngineValues = array(
			'google'  => array('NAME' => 'Google' , 'REGEX' => '#^https?://www\.google\.[a-z]{2,3}/#'     ),
			'bing'    => array('NAME' => 'Bing'   , 'REGEX' => '#^https?://www\.bing\.[a-z]{2,3}/#'       ),
			'yahoo'   => array('NAME' => 'Yahoo'  , 'REGEX' => '#^https?://r\.search\.yahoo\.[a-z]{2,3}/#'),
			'ask'     => array('NAME' => 'Ask'    , 'REGEX' => '#^https?://www\.ask\.[a-z]{2,3}/#'        ),
			'yandex'  => array('NAME' => 'Yandex' , 'REGEX' => '#^https?://yandex\.[a-z]{2,3}/#'          ),
			'mail.ru' => array('NAME' => 'Mail.ru', 'REGEX' => '#^https?://go\.mail\.ru/#'                ),
			'rambler' => array('NAME' => 'Rambler', 'REGEX' => '#^https?://nova\.rambler\.ru/#'           ),
		);

		return array(

			// SITE

			'conversion_site' => array(
				'MODULE' => 'conversion',
				'NAME'   => Loc::getMessage('CONVERSION_ATTRIBUTE_SITE_NAME'),
				'SORT'   => 1100,
				'GET_VALUES' => function (array $ids)
				{
					$values = array();

					$result = SiteTable::getList(array(
						'select' => array('LID', 'NAME'),
						'filter' => array('LID' => $ids),
						'order'  => array('SORT' => 'ASC'),
					));

					while ($row = $result->fetch())
					{
						$values[$row['LID']] = array('NAME' => $row['NAME']);
					}

					return $values;
				},
				'SET_DAY_CONTEXT' => function (DayContext $dayContext)
				{
					if ($siteId = DayContext::getSiteId())
					{
						$dayContext->setAttribute('conversion_site', $siteId);
					}
				},
			),

			// OPERATING SYSTEM

			'conversion_operating_system' => array(
				'MODULE' => 'conversion',
				'NAME'   => Loc::getMessage('CONVERSION_ATTRIBUTE_OPERATING_SYSTEM_NAME'),
				'SORT'   => 2100,
				'GET_VALUES' => function (array $ids) use ($operatingSystemValues)
				{
					$values = array();

					foreach ($ids as $id)
					{
						if ($value = $operatingSystemValues[$id])
						{
							$values[$id] = $value;
						}
					}

					return $values;
				},
				'SET_DAY_CONTEXT' => function (DayContext $dayContext) use ($operatingSystemValues, $userAgent)
				{
					if ($userAgent)
					{
						foreach ($operatingSystemValues as $name => $type)
						{
							if (preg_match($type['REGEX'], $userAgent))
							{
								$dayContext->setAttribute('conversion_operating_system', $name);
								break;
							}
						}
					}
				},
			),

			// BROWSER

			'conversion_browser' => array(
				'MODULE' => 'conversion',
				'NAME'   => Loc::getMessage('CONVERSION_ATTRIBUTE_BROWSER_NAME'),
				'SORT'   => 3100, // must be before conversion_device_desktop!
				'GET_VALUES' => function (array $ids) use ($browserValues)
				{
					$values = array();

					foreach ($ids as $id)
					{
						if ($value = $browserValues[$id])
						{
							$values[$id] = $value;
						}
					}

					return $values;
				},
				'SET_DAY_CONTEXT' => function (DayContext $dayContext) use ($browserValues, $userAgent, & $browser)
				{
					if ($userAgent)
					{
						foreach ($browserValues as $name => $type)
						{
							if (preg_match($type['REGEX'], $userAgent))
							{
								$dayContext->setAttribute('conversion_browser', $name);
								$browser = $name;
								break;
							}
						}
					}
				},
			),

			// DEVICE

			'conversion_device_tablet' => array(
				'MODULE'   => 'conversion',
				'GROUP'    => 'device',
				'SORT'     => 4100, // must be before conversion_device_mobile!
				'NAME'     => Loc::getMessage('CONVERSION_ATTRIBUTE_DEVICE_TABLET_NAME'),
				'SPLIT_BY' => 'conversion_operating_system',
				'BG_COLOR' => '#be6ac4',
				'SET_DAY_CONTEXT' => function (DayContext $dayContext) use ($detector)
				{
					if ($detector->isTablet())
					{
						$dayContext->setAttribute('conversion_device_tablet');
					}
				},
			),

			'conversion_device_mobile' => array(
				'MODULE'   => 'conversion',
				'GROUP'    => 'device',
				'SORT'     => 4200, // must be after conversion_device_tablet!
				'NAME'     => Loc::getMessage('CONVERSION_ATTRIBUTE_DEVICE_MOBILE_NAME'),
				'SPLIT_BY' => 'conversion_operating_system',
				'BG_COLOR' => '#4bbedb',
				'SET_DAY_CONTEXT' => function (DayContext $dayContext) use ($detector)
				{
					if ($detector->isMobile())
					{
						$dayContext->setAttribute('conversion_device_mobile');
					}
				},
			),

			'conversion_device_desktop' => array(
				'MODULE'   => 'conversion',
				'GROUP'    => 'device',
				'SORT'     => 4500, // must be after conversion_browser!
				'NAME'     => Loc::getMessage('CONVERSION_ATTRIBUTE_DEVICE_DESKTOP_NAME'),
				'SPLIT_BY' => 'conversion_browser',
				'BG_COLOR' => '#cf4343',
				'SET_DAY_CONTEXT' => function (DayContext $dayContext) use (& $browser)
				{
					if ($browser)
					{
						$dayContext->setAttribute('conversion_device_desktop');
					}
				},
			),

			// SOURCE

			'conversion_search_engine' => array(
				'MODULE'   => 'conversion',
				'GROUP'    => 'source',
				'SORT'     => 5500, // must be after seo_yandex_direct_source!
				'NAME'     => Loc::getMessage('CONVERSION_ATTRIBUTE_SEARCH_ENGINE_NAME'),
				'SPLIT_BY' => 'conversion_search_engine',
				'BG_COLOR' => '#be6ac4',
				'GET_VALUES' => function (array $ids) use ($searchEngineValues)
				{
					$values = array();

					foreach ($ids as $id)
					{
						if ($value = $searchEngineValues[$id])
						{
							$values[$id] = $value;
						}
					}

					return $values;
				},
				'SET_DAY_CONTEXT' => function (DayContext $dayContext) use ($searchEngineValues)
				{
					if ($referer = $_SERVER['HTTP_REFERER'])
					{
						foreach ($searchEngineValues as $name => $type)
						{
							if (preg_match($type['REGEX'], $referer))
							{
								$dayContext->setAttribute('conversion_search_engine', $name);
								break;
							}
						}
					}
				},
			),

		);
	}

	static public function onSetDayContextAttributes(DayContext $dayContext)
	{
		foreach (self::onGetAttributeTypes() as $name => $type)
		{
			if ($setDayContext = $type['SET_DAY_CONTEXT'])
			{
				$setDayContext($dayContext);
			}
		}
	}

	public static function onGetAttributeGroupTypes()
	{
		return array(
			'source' => array(
				'NAME' => Loc::getMessage('CONVERSION_ATTRIBUTE_GROUP_SOURCE_NAME'),
				'SORT' => 100,
			),
			'device' => array(
				'NAME' => Loc::getMessage('CONVERSION_ATTRIBUTE_GROUP_DEVICE_NAME'),
				'SORT' => 200,
			),
		);
	}

	static public function onProlog()
	{
		static $done = false;
		if (! $done)
		{
			$done = true;

			DayContext::getInstance();

			// For composite site this script must not be changing often!!!
			Asset::getInstance()->addString(
				'<script type="text/javascript">
					(function () {
						"use strict";

						var counter = function ()
						{
							var cookie = (function (name) {
								var parts = ("; " + document.cookie).split("; " + name + "=");
								if (parts.length == 2) {
									try {return JSON.parse(decodeURIComponent(parts.pop().split(";").shift()));}
									catch (e) {}
								}
							})("'.DayContext::getVarName().'");

							if (! cookie || cookie.EXPIRE < BX.message("SERVER_TIME"))
							{
								var request = new XMLHttpRequest();
								request.open("POST", "/bitrix/tools/conversion/ajax_counter.php", true);
								request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
								request.send(
									"SITE_ID="      + encodeURIComponent(BX.message("SITE_ID")) + "&" +
									"sessid="       + encodeURIComponent(BX.bitrix_sessid())    + "&" +
									"HTTP_REFERER=" + encodeURIComponent(document.referrer)
								);
							}
						};

						if (window.frameRequestStart === true)
							BX.addCustomEvent("onFrameDataReceived", counter);
						else
							BX.ready(counter);
					})();
				</script>',
				false,
				AssetLocation::AFTER_JS_KERNEL
				// Do not use AssetMode unless you absolutely sure what you're doing!
				// Maybe default value or AssetMode::ALL is appropriate, all other modes do not work!
				// This script must be executed on every hit!!!
			);
		}
	}
}