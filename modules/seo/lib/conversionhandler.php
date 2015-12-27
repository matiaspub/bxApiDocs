<?php
namespace Bitrix\Seo;

use Bitrix\Conversion\DayContext;
use Bitrix\Main\Localization\Loc;
use Bitrix\Seo\Adv\YandexBannerTable;

Loc::loadMessages(__FILE__);

class ConversionHandler
{
	public static function onSetDayContextAttributes(DayContext $context)
	{
		if (AdvSession::isSession())
		{
			$context->setAttribute('seo_yandex_direct_source', static::getSeoId());
		}
	}

	protected static function getSeoId()
	{
		$session = AdvSession::getSession();
		return $session["BANNER_ID"];
	}

	public static function onGetAttributeTypes()
	{
		return array(
			'seo_yandex_direct_source' => array(
				'MODULE'   => 'seo',
				'GROUP'    => 'source',
				'SORT'     => 5200, // must be before conversion_search_engine!
				'NAME'     => Loc::getMessage('SEO_CONVERSION_ATTRIBUTE_YANDEX_DIRECT_NAME'),
				'BG_COLOR' => '#4bbedb',
				'GET_VALUES' => function (array $ids)
				{
					$dbRes = YandexBannerTable::getList(array(
						'filter' => array("=XML_ID" => $ids),
						'select' => array("XML_ID", "NAME"),
						'order' => array('NAME' => 'ASC'),
					));

					$bannerList = array();
					while($banner = $dbRes->fetch())
					{
						$bannerList[$banner['XML_ID']] = array("NAME" => array($banner["NAME"]));
					}

					return $bannerList;
				},
				'SPLIT_BY' => 'seo_yandex_direct_source',
			),
		);
	}
}
