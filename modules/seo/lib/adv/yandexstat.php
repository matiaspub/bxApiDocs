<?php
namespace Bitrix\Seo\Adv;

use Bitrix\Main\Entity;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Seo\Engine\YandexDirect;
use Bitrix\Seo\Engine\YandexDirectException;

/**
 * Class YandexStatTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CAMPAIGN_ID int mandatory
 * <li> BANNER_ID int mandatory
 * <li> DATE_DAY date mandatory
 * <li> SUM double optional
 * <li> SUM_SEARCH double optional
 * <li> SUM_CONTEXT double optional
 * <li> CLICKS int optional
 * <li> CLICKS_SEARCH int optional
 * <li> CLICKS_CONTEXT int optional
 * <li> SHOWS int optional
 * <li> SHOW_SEARCH int optional
 * <li> SHOW_CONTEXT int optional
 * </ul>
 *
 * @package Bitrix\Seo
 **/

class YandexStatTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_seo_yandex_direct_stat';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'CAMPAIGN_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'BANNER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'DATE_DAY' => array(
				'data_type' => 'date',
				'required' => true,
			),
			'CURRENCY' => array(
				'data_type' => 'string',
			),
			'SUM' => array(
				'data_type' => 'float',
			),
			'SUM_SEARCH' => array(
				'data_type' => 'float',
			),
			'SUM_CONTEXT' => array(
				'data_type' => 'float',
			),
			'CLICKS' => array(
				'data_type' => 'integer',
			),
			'CLICKS_SEARCH' => array(
				'data_type' => 'integer',
			),
			'CLICKS_CONTEXT' => array(
				'data_type' => 'integer',
			),
			'SHOWS' => array(
				'data_type' => 'integer',
			),
			'SHOWS_SEARCH' => array(
				'data_type' => 'integer',
			),
			'SHOWS_CONTEXT' => array(
				'data_type' => 'integer',
			),
			'CAMPAIGN' => array(
				'data_type' => 'Bitrix\Seo\Adv\YandexCampaignTable',
				'reference' => array('=this.CAMPAIGN_ID' => 'ref.ID'),
			),
			'BANNER' => array(
				'data_type' => 'Bitrix\Seo\Adv\YandexBannerTable',
				'reference' => array('=this.BANNER_ID' => 'ref.ID'),
			),
		);
	}

	public static function getBannerStat($bannerId, $dateStart, $dateFinish)
	{
		$result = array();

		$dbRes = static::getList(array(
			'order' => array(
				'BANNER_ID' => 'ASC',
				'DATE_DAY' => 'ASC',
			),
			'filter' => array(
				'=BANNER_ID' => $bannerId,
				'>=DATE_DAY' => $dateStart,
				'<=DATE_DAY' => $dateFinish,
			)
		));

		while($statEntry = $dbRes->fetch())
		{
			$result[$statEntry['DATE_DAY']->toString()] = $statEntry;
		}

		return $result;
	}

	public static function getCampaignStat($campaignId, $dateStart, $dateFinish)
	{
		$result = array();

		$dbRes = static::getList(array(
			'order' => array(
				'DATE_DAY' => 'ASC',
			),
			'group' => array('CAMPAIGN_ID', 'DATE_DAY', 'CURRENCY'),
			'filter' => array(
				'=CAMPAIGN_ID' => $campaignId,
				'>=DATE_DAY' => $dateStart,
				'<=DATE_DAY' => $dateFinish,
			),
			'select' => array(
				'CAMPAIGN_ID', 'DATE_DAY', 'CURRENCY',
				'CAMPAIGN_SUM', 'CAMPAIGN_SUM_SEARCH', 'CAMPAIGN_SUM_CONTEXT',
				'CAMPAIGN_SHOWS', 'CAMPAIGN_SHOWS_SEARCH', 'CAMPAIGN_SHOWS_CONTEXT',
				'CAMPAIGN_CLICKS', 'CAMPAIGN_CLICKS_SEARCH', 'CAMPAIGN_CLICKS_CONTEXT',
			),
			'runtime' => array(
				new Entity\ExpressionField('CAMPAIGN_SUM', 'SUM(SUM)'),
				new Entity\ExpressionField('CAMPAIGN_SUM_SEARCH', 'SUM(SUM_SEARCH)'),
				new Entity\ExpressionField('CAMPAIGN_SUM_CONTEXT', 'SUM(SUM_CONTEXT)'),
				new Entity\ExpressionField('CAMPAIGN_SHOWS', 'SUM(SHOWS)'),
				new Entity\ExpressionField('CAMPAIGN_SHOWS_SEARCH', 'SUM(SHOWS_SEARCH)'),
				new Entity\ExpressionField('CAMPAIGN_SHOWS_CONTEXT', 'SUM(SHOWS_CONTEXT)'),
				new Entity\ExpressionField('CAMPAIGN_CLICKS', 'SUM(CLICKS)'),
				new Entity\ExpressionField('CAMPAIGN_CLICKS_SEARCH', 'SUM(CLICKS_SEARCH)'),
				new Entity\ExpressionField('CAMPAIGN_CLICKS_CONTEXT', 'SUM(CLICKS_CONTEXT)'),
			),
		));

		while($statEntry = $dbRes->fetch())
		{
			$result[$statEntry['DATE_DAY']->toString()] = $statEntry;
		}

		return $result;
	}


	public static function loadBannerStat($bannerId, $dateStart, $dateFinish)
	{
		$liveEngine = new YandexDirect();

		$dbRes = YandexBannerTable::getList(array(
			'filter' => array(
				'=ID' => $bannerId,
				'=ENGINE_ID' => $liveEngine->getId()
			),
			'select' => array(
				'ID', 'CAMPAIGN_ID',
				'CAMPAIGN_XML_ID' => 'CAMPAIGN.XML_ID'
			)
		));

		$banner = $dbRes->fetch();
		if($banner)
		{
			$result = static::loadStat($liveEngine, $banner['CAMPAIGN_XML_ID'], $dateStart, $dateFinish);
			if($result['Stat'])
			{
				static::processStatsResult($banner['CAMPAIGN_ID'], $result, $liveEngine);
				return true;
			}
		}

		return false;
	}

	public static function loadCampaignStat($campaignId, $dateStart, $dateFinish)
	{
		$liveEngine = new YandexDirect();

		$dbRes = YandexCampaignTable::getList(array(
			'filter' => array(
				'=ID' => $campaignId,
				'=ENGINE_ID' => $liveEngine->getId()
			),
			'select' => array(
				'ID', 'XML_ID'
			)
		));

		$campaign = $dbRes->fetch();
		if($campaign)
		{
			$result = static::loadStat($liveEngine, $campaign['XML_ID'], $dateStart, $dateFinish);
			if($result['Stat'])
			{
				static::processStatsResult($campaignId, $result, $liveEngine);
				return true;
			}
		}

		return false;
	}

	protected function loadStat(YandexDirect $liveEngine, $campaignXmlId, $dateStart, $dateFinish, $skipCurrency = false)
	{
		$dateStart = new Date($dateStart);
		$dateFinish = new Date($dateFinish);

		$queryData = array(
			"CampaignID" => $campaignXmlId,
			"StartDate" => $dateStart->format("Y-m-d"),
			'EndDate' => $dateFinish->format("Y-m-d"),
			'GroupByColumns' => array(
				'clDate', 'clBanner'
			),
		);

		$currency = '';
		if(!$skipCurrency && Loader::includeModule('currency'))
		{
			$baseCurrency = \CCurrency::GetBaseCurrency();
			if($baseCurrency == 'RUR')
			{
				$baseCurrency = 'RUB';
			}

			if(in_array($baseCurrency, $liveEngine->allowedCurrency))
			{
				$currency = $baseCurrency;
			}
		}

		if($currency != '')
		{
			$queryData['Currency'] = $currency;
		}

		try
		{
			$result = $liveEngine->getBannerStats($queryData);
			$result['Currency'] = $currency;
		}
		catch(YandexDirectException $e)
		{
			if($currency != '' && $e->getCode() == YandexDirect::ERROR_WRONG_CURRENCY)
			{
				$result = static::loadStat($liveEngine, $campaignXmlId, $dateStart, $dateFinish, true);
			}
			else
			{
				throw $e;
			}
		}

		return $result;
	}

	protected function processStatsResult($campaignId, array $result, YandexDirect $liveEngine)
	{
		if($result['Stat'])
		{
			$bannerIds = array();
			foreach($result['Stat'] as $statEntry)
			{
				$bannerIds[] = $statEntry['BannerID'];
			}

			if(count($bannerIds) > 0)
			{
				$dbRes = YandexBannerTable::getList(array(
					'filter' => array(
						'=XML_ID' => array_values(array_unique($bannerIds)),
						'=ENGINE_ID' => $liveEngine->getId()
					),
					'select' => array(
						'ID', 'XML_ID'
					)
				));
				$bannerList = array();
				while($bannerData = $dbRes->fetch())
				{
					$bannerList[$bannerData['XML_ID']] = $bannerData['ID'];
				}

				if(count($bannerList) > 0)
				{
					foreach($result['Stat'] as $statEntry)
					{
						if(array_key_exists($statEntry['BannerID'], $bannerList))
						{
							$statFields = array(
								'CAMPAIGN_ID' => $campaignId,
								'BANNER_ID' => $bannerList[$statEntry['BannerID']],
								'DATE_DAY' => new Date($statEntry['StatDate'], 'Y-m-d'),
								'CURRENCY' => $result['Currency'],
								'SUM' => $statEntry['Sum'],
								'SUM_SEARCH' => $statEntry['SumSearch'],
								'SUM_CONTEXT' => $statEntry['SumContext'],
								'CLICKS' => $statEntry['Clicks'],
								'CLICKS_SEARCH' => $statEntry['ClicksSearch'],
								'CLICKS_CONTEXT' => $statEntry['ClicksContext'],
								'SHOWS' => $statEntry['Shows'],
								'SHOWS_SEARCH' => $statEntry['ShowsSearch'],
								'SHOWS_CONTEXT' => $statEntry['ShowsContext'],
							);

							$statCheckRes = static::getList(array(
								'filter' => array(
									'BANNER_ID' => $statFields['BANNER_ID'],
									'DATE_DAY' => $statFields['DATE_DAY'],
								),
								'select' => array('ID')
							));

							$statCheck = $statCheckRes->fetch();
							if(!$statCheck)
							{
								static::add($statFields);
							}
						}
					}
				}
			}
		}
	}

	public static function getMissedPeriods(array $stats, $dateStart, $dateFinish)
	{
		$missedPeriods = array();

		$datePrevoius = false;

		$checkDate = new Date($dateStart);
		$dateCurrent = new Date($dateStart);
		$dateFinish = new Date($dateFinish);

		while($dateCurrent->getTimestamp() <= $dateFinish->getTimestamp())
		{
			if(!array_key_exists($dateCurrent->toString(), $stats))
			{
				if(
					!$datePrevoius
					|| $dateCurrent->getTimestamp() >= $checkDate->getTimestamp()
				)
				{
					$missedPeriods[] = array(
						$dateCurrent->toString(),
						$dateCurrent->toString()
					);

					$checkDate = new Date($dateCurrent->toString());
					$checkDate->add("+".YandexDirect::MAX_STAT_DAYS_DELTA." days");

					$datePrevoius = true;
				}
				else
				{
					$missedPeriods[count($missedPeriods)-1][1] = $dateCurrent->toString();
				}
			}
			else
			{
				$datePrevoius = false;
			}

			$dateCurrent->add("+1 days");
		}

		return $missedPeriods;
	}
}