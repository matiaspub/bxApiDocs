<?php
namespace Bitrix\Seo\Adv;

use Bitrix\Catalog\ProductTable;
use Bitrix\Seo\Engine\YandexDirect;
use Bitrix\Seo\Engine\YandexDirectException;

class Auto
{
	public static function checkQuantity($ID, $productFields)
	{
		$checkNeed = (
			isset($productFields['QUANTITY'])
			|| isset($productFields['QUANTITY_TRACE'])
			|| isset($productFields['CAN_BUY_ZERO'])
		);

		if (!$checkNeed)
		{
			return;
		}

		$productIterator = ProductTable::getList(array(
			'filter' => array('=ID' => $ID),
			'select' => array('QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO'),
		));
		$product = $productIterator->fetch();
		if($product)
		{
			if($product["QUANTITY_TRACE"] == "Y" && $product['CAN_BUY_ZERO'] == 'N')
			{
				$linkIterator = LinkTable::getList(array(
					"filter" => array(
						"=LINK_TYPE" => LinkTable::TYPE_IBLOCK_ELEMENT,
						"=LINK_ID" => $ID,
						array(
							array(
								"=BANNER.AUTO_QUANTITY_OFF" => YandexBannerTable::ACTIVE,
							),
							array(
								"=BANNER.AUTO_QUANTITY_ON" => YandexBannerTable::ACTIVE,
							),
							'LOGIC' => "OR",
						),
					),
					"select" => array(
						"BANNER_ID",
						"AUTO_QUANTITY_ON" => "BANNER.AUTO_QUANTITY_ON",
						"AUTO_QUANTITY_OFF" => "BANNER.AUTO_QUANTITY_OFF",
					),
				));

				$zeroQuantity = $product['QUANTITY'] <= 0;

				$linkIdMark = array();
				$linkIdUnMark = array();
				while($link = $linkIterator->fetch())
				{
					$linkIdMark[] = $link["BANNER_ID"];

					if(
						$zeroQuantity && $link["AUTO_QUANTITY_ON"] == YandexBannerTable::MARKED
						|| !$zeroQuantity && $link["AUTO_QUANTITY_OFF"] == YandexBannerTable::MARKED
					)
					{
						$linkIdUnMark[] = $link["BANNER_ID"];
					}
				}

				if(count($linkIdMark) > 0)
				{
					if($zeroQuantity)
					{
						YandexBannerTable::markStopped($linkIdMark);
					}
					else
					{
						YandexBannerTable::markResumed($linkIdMark);
					}

					if(count($linkIdUnMark) > 0)
					{
						if($zeroQuantity)
						{
							YandexBannerTable::unMarkResumed($linkIdUnMark);
						}
						else
						{
							YandexBannerTable::unMarkStopped($linkIdUnMark);
						}
					}
				}
			}
		}
	}

	public static function checkQuantityAgent()
	{
		if(!IsModuleInstalled("catalog"))
		{
			return __CLASS__."::checkQuantityAgent();";
		}

		$dbRes = YandexBannerTable::getList(array(
			'filter' => array(
				array(
					'=AUTO_QUANTITY_ON' => YandexBannerTable::MARKED,
				),
				array(
					'=AUTO_QUANTITY_OFF' => YandexBannerTable::MARKED,
				),
				'LOGIC' => "OR"
			),
			'select' => array(
				'ID', 'XML_ID', 'CAMPAIGN_ID', 'CAMPAIGN_XML_ID' => 'CAMPAIGN.XML_ID',
				'AUTO_QUANTITY_ON', 'AUTO_QUANTITY_OFF',
			),
		));

		$engine = new YandexDirect();

		$bannersListToStop = array();
		$bannersListToResume = array();
		$bannersListToUnMarkStopped = array();
		$bannersListToUnMarkResumed = array();

		$bannersLogData = array();
		while($banner = $dbRes->fetch())
		{
			if($banner["AUTO_QUANTITY_ON"] == YandexBannerTable::MARKED)
			{
				if(!isset($bannersListToResume[$banner["CAMPAIGN_XML_ID"]]))
				{
					$bannersListToResume[$banner["CAMPAIGN_XML_ID"]] = array();
				}

				$bannersListToResume[$banner["CAMPAIGN_XML_ID"]][$banner["ID"]] = $banner["XML_ID"];
				$causeCode = AutologTable::CODE_QUANTITY_ON;

				if($banner["AUTO_QUANTITY_OFF"] == YandexBannerTable::MARKED)
				{
					$bannersListToUnMarkStopped[] = $banner["ID"];
				}
			}
			else
			{
				if(!isset($bannersListToResume[$banner["CAMPAIGN_XML_ID"]]))
				{
					$bannersListToStop[$banner["CAMPAIGN_XML_ID"]] = array();
				}

				$bannersListToStop[$banner["CAMPAIGN_XML_ID"]][$banner["ID"]] = $banner["XML_ID"];
				$causeCode = AutologTable::CODE_QUANTITY_OFF;

				if($banner["AUTO_QUANTITY_ON"] == YandexBannerTable::MARKED)
				{
					$bannersListToUnMarkResumed[] = $banner["ID"];
				}
			}

			$bannersLogData[$banner["ID"]] = array(
				'CAMPAIGN_ID' => $banner['CAMPAIGN_ID'],
				'CAMPAIGN_XML_ID' => $banner['CAMPAIGN_XML_ID'],
				'BANNER_ID' => $banner['ID'],
				'BANNER_XML_ID' => $banner['XML_ID'],
				'CAUSE_CODE' => $causeCode,
			);
		}

		if(count($bannersLogData) > 0)
		{
			foreach($bannersListToResume as $campaignId => $bannersList)
			{
				if(count($bannersList) > 0)
				{
					$success = true;

					try
					{
						$engine->resumeBanners($campaignId, array_values($bannersList));
					}
					catch(YandexDirectException $e)
					{
						$success = false;
					}

					foreach($bannersList as $bannerId => $bannerXmlId)
					{
						$logEntry = $bannersLogData[$bannerId];
						$logEntry['ENGINE_ID'] = $engine->getId();
						$logEntry['SUCCESS'] = $success ? AutologTable::SUCCESS : AutologTable::FAILURE;

						AutologTable::add($logEntry);
					}

					$bannersListToUnMarkResumed = array_merge(
						$bannersListToUnMarkResumed,
						array_keys($bannersList)
					);
				}
			}

			foreach($bannersListToStop as $campaignId => $bannersList)
			{
				if(count($bannersList) > 0)
				{
					$success = true;
					try
					{
						$engine->stopBanners($campaignId, array_values($bannersList));
					}
					catch(YandexDirectException $e)
					{
						$success = false;
					}

					foreach($bannersList as $bannerId => $bannerXmlId)
					{
						$logEntry = $bannersLogData[$bannerId];
						$logEntry['ENGINE_ID'] = $engine->getId();
						$logEntry['SUCCESS'] = $success ? AutologTable::SUCCESS : AutologTable::FAILURE;

						AutologTable::add($logEntry);
					}

					$bannersListToUnMarkStopped = array_merge(
						$bannersListToUnMarkStopped,
						array_keys($bannersList)
					);
				}
			}

			if(count($bannersListToUnMarkStopped) > 0)
			{
				YandexBannerTable::unMarkStopped($bannersListToUnMarkStopped);
			}

			if(count($bannersListToUnMarkResumed) > 0)
			{
				YandexBannerTable::unMarkResumed($bannersListToUnMarkResumed);
			}
		}

		return __CLASS__."::checkQuantityAgent();";
	}
}

