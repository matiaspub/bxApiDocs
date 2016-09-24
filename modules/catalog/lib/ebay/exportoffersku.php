<?php
namespace Bitrix\Catalog\Ebay;

use Bitrix\Main\SystemException;

class ExportOfferSKU extends ExportOffer
{
	protected $arSKUExport = array();
	protected $arSelectOfferProps;
	protected $arProperties;
	protected $arOfferIBlock = array();
	protected $arOffers = array();
	protected $intOfferIBlockID =0;

	public function __construct($catalogType, $params)
	{
		parent::__construct($catalogType, $params);

		$this->arSKUExport = $this->getSKUExport();
		$this->arOffers = $this->getOffers();
	}

	protected function getOffers()
	{
		$arPropertyMap = array();
		$arSelectedPropTypes = array('S','N','L','E','G');
		$this->arSelectOfferProps = array();
		$arOffers = \CCatalogSku::GetInfoByProductIBlock($this->iBlockId);

		if (empty($arOffers['IBLOCK_ID']))
			return array();

		$this->intOfferIBlockID = $arOffers['IBLOCK_ID'];
		$rsOfferIBlocks = \CIBlock::GetByID($this->intOfferIBlockID);

		if (!$this->arOfferIBlock = $rsOfferIBlocks->Fetch())
			throw new SystemException("Bad offers iBlock ID  (".__CLASS__."::".__METHOD__.")");

		$rsProps = \CIBlockProperty::GetList(
			array('SORT' => 'ASC', 'NAME' => 'ASC'),
			array('IBLOCK_ID' => $this->intOfferIBlockID, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N')
		);

		while ($arProp = $rsProps->Fetch())
		{
			$arProp['ID'] = (int)$arProp['ID'];

			if ($arOffers['SKU_PROPERTY_ID'] != $arProp['ID'])
			{
				$arProp['USER_TYPE'] = (string)$arProp['USER_TYPE'];
				$arProp['CODE'] = (string)$arProp['CODE'];
				$this->arIblock['OFFERS_PROPERTY'][$arProp['ID']] = $arProp;
				$this->arProperties[$arProp['ID']] = $arProp;

				if (in_array($arProp['PROPERTY_TYPE'], $arSelectedPropTypes))
					$this->arSelectOfferProps[] = $arProp['ID'];

				if ($arProp['CODE'] !== '')
				{
					foreach ($this->arIblock['PROPERTY'] as &$arMainProp)
					{
						if ($arMainProp['CODE'] == $arProp['CODE'])
						{
							$arPropertyMap[$arProp['ID']] = $arMainProp['CODE'];
							break;
						}
					}

					if (isset($arMainProp))
						unset($arMainProp);
				}
			}
		}

		$this->arOfferIBlock['LID'] = $this->arIblock['LID'];

		$this->arOfferIBlock['PROPERTY'] = array();

		$rsProps = \CIBlockProperty::GetList(
			array('SORT' => 'ASC', 'NAME' => 'ASC'),
			array('IBLOCK_ID' => $this->intOfferIBlockID, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N')
		);

		while ($arProp = $rsProps->Fetch())
		{

			$arProp['ID'] = (int)$arProp['ID'];
			$arProp['USER_TYPE'] = (string)$arProp['USER_TYPE'];
			$arProp['CODE'] = (string)$arProp['CODE'];
			$this->arOfferIBlock['PROPERTY'][$arProp['ID']] = $arProp;
		}

		return $arOffers;
	}

	protected function getSKUExport()
	{
		$arOffersSelectKeys = array(
			YANDEX_SKU_EXPORT_ALL,
			YANDEX_SKU_EXPORT_MIN_PRICE,
			YANDEX_SKU_EXPORT_PROP,
		);

		$arCondSelectProp = array(
			'ZERO',
			'NONZERO',
			'EQUAL',
			'NONEQUAL',
		);

		$arSKUExport = array();

		if (is_array($this->arOfferIBlock) && !empty($this->arOfferIBlock))
		{
			if (empty($this->xmlData['SKU_EXPORT']))
				throw new SystemException("YANDEX_ERR_SKU_SETTINGS_ABSENT");

			$arSKUExport = $this->xmlData['SKU_EXPORT'];

			if (empty($arSKUExport['SKU_EXPORT_COND']) || !in_array($arSKUExport['SKU_EXPORT_COND'], $arOffersSelectKeys))
				throw new SystemException("YANDEX_SKU_EXPORT_ERR_CONDITION_ABSENT");

			if (YANDEX_SKU_EXPORT_PROP == $arSKUExport['SKU_EXPORT_COND'])
			{
				if (empty($arSKUExport['SKU_PROP_COND']) || !is_array($arSKUExport['SKU_PROP_COND']))
					throw new SystemException("YANDEX_SKU_EXPORT_ERR_PROPERTY_ABSENT");

				if (empty($arSKUExport['SKU_PROP_COND']['PROP_ID']) || !in_array($arSKUExport['SKU_PROP_COND']['PROP_ID'],$this->arSelectOfferProps))
					throw new SystemException("YANDEX_SKU_EXPORT_ERR_PROPERTY_ABSENT");

				if (empty($arSKUExport['SKU_PROP_COND']['COND']) || !in_array($arSKUExport['SKU_PROP_COND']['COND'],$arCondSelectProp))
					throw new SystemException("YANDEX_SKU_EXPORT_ERR_PROPERTY_COND_ABSENT");

				if ($arSKUExport['SKU_PROP_COND']['COND'] == 'EQUAL' || $arSKUExport['SKU_PROP_COND']['COND'] == 'NONEQUAL')
				{
					if (empty($arSKUExport['SKU_PROP_COND']['VALUES']))
						throw new SystemException("YANDEX_SKU_EXPORT_ERR_PROPERTY_VALUES_ABSENT");
				}
			}
		}

		return $arSKUExport;
	}

	protected function getOfferTemplateUrl()
	{
		$strOfferTemplateURL = '';

		if (!empty($this->arSKUExport['SKU_URL_TEMPLATE_TYPE']))
		{
			switch($this->arSKUExport['SKU_URL_TEMPLATE_TYPE'])
			{
				case YANDEX_SKU_TEMPLATE_PRODUCT:
					$strOfferTemplateURL = '#PRODUCT_URL#';
					break;
				case YANDEX_SKU_TEMPLATE_CUSTOM:
					if (!empty($this->arSKUExport['SKU_URL_TEMPLATE']))
						$strOfferTemplateURL = $this->arSKUExport['SKU_URL_TEMPLATE'];
					break;
				case YANDEX_SKU_TEMPLATE_OFFERS:
				default:
					$strOfferTemplateURL = '';
					break;
			}
		}

		return $strOfferTemplateURL;
	}

	protected function getOffersItemsDb($itemId)
	{
		$arOfferSelect = array("ID", "LID", "IBLOCK_ID", "NAME", "PREVIEW_PICTURE", "PREVIEW_TEXT",
			"PREVIEW_TEXT_TYPE", "DETAIL_PICTURE", "DETAIL_PAGE_URL", "DETAIL_TEXT");

		$arOfferFilter = array('IBLOCK_ID' => $this->intOfferIBlockID, 'PROPERTY_'.$this->arOffers['SKU_PROPERTY_ID'] => 0,
			"ACTIVE" => "Y", "ACTIVE_DATE" => "Y");

		if (YANDEX_SKU_EXPORT_PROP == $this->arSKUExport['SKU_EXPORT_COND'])
		{
			$strExportKey = '';
			$mxValues = false;

			if ($this->arSKUExport['SKU_PROP_COND']['COND'] == 'NONZERO' || $this->arSKUExport['SKU_PROP_COND']['COND'] == 'NONEQUAL')
				$strExportKey = '!';

			$strExportKey .= 'PROPERTY_'.$this->arSKUExport['SKU_PROP_COND']['PROP_ID'];

			if ($this->arSKUExport['SKU_PROP_COND']['COND'] == 'EQUAL' || $this->arSKUExport['SKU_PROP_COND']['COND'] == 'NONEQUAL')
				$mxValues = $this->arSKUExport['SKU_PROP_COND']['VALUES'];

			$arOfferFilter[$strExportKey] = $mxValues;
		}

		$arOfferFilter['PROPERTY_'.$this->arOffers['SKU_PROPERTY_ID']] = $itemId;
		return \CIBlockElement::GetList(array(),$arOfferFilter,false,false,$arOfferSelect);
	}

	protected function getProperties($arItem)
	{
		$arCross = array();

		if (!empty($arItem['PROPERTIES']))
		{
			foreach ($arItem['PROPERTIES'] as &$arProp)
				$arCross[$arProp['ID']] = $arProp;

			if (isset($arProp))
				unset($arProp);
		}

		return $arCross;
	}

	protected function getItemParams(array $itemOffer)
	{
		$itemOffer["QUANTITY"] = $this->getQuantity($itemOffer["ID"]);
		$itemOffer["CATEGORIES"] = $this->getCategories($itemOffer["ID"]);
		$itemOffer["DETAIL_PICTURE_URL"] = $this->getPictureUrl((int)$itemOffer["DETAIL_PICTURE"]);
		$itemOffer["PREVIEW_PICTURE_URL"] = $this->getPictureUrl((int)$itemOffer["PREVIEW_PICTURE"]);
		$itemOffer["PARAMS"] = $this->getParams($itemOffer, $this->arOfferIBlock);
		$itemOffer["DETAIL_PAGE_URL"] = $this->getDetailPageUrl($itemOffer["~DETAIL_PAGE_URL"]);

		return $itemOffer;
	}

	/**
	 * @param \_CIBElement $obOfferItem
	 * @param array $arItem
	 * @return array|mixed
	 */
	protected function getItemProps($obOfferItem, array $arItem)
	{
		$arCross = (!empty($arItem['PROPERTIES']) ? $arItem['PROPERTIES'] : array());
		$props = $obOfferItem->GetProperties();

		if (!empty($props))
			foreach ($props as $arProp)
				$arCross[$arProp['ID']] = $arProp;

		return $arCross;
	}

	/**
	 * @param \CIBlockResult $rsOfferItems
	 * @param array $arItem
	 * @return array
	 */
	protected function getMinPriceOffer($rsOfferItems, $arItem)
	{
		$dblAllMinPrice = 0;
		$boolFirst = true;

		while ($obOfferItem = $rsOfferItems->GetNextElement())
		{
			$arOfferItem = $obOfferItem->GetFields();
			$arOfferItem["PRICES"] = $this->getPrices($obOfferItem["ID"], $this->arOfferIBlock['LID']);

			if ($arOfferItem["PRICES"]["MIN"] <= 0)
				continue;

			if ($boolFirst)
			{
				$dblAllMinPrice = $arOfferItem["PRICES"]["MIN"];
				$boolFirst = false;
			}
			else
			{
				if ($dblAllMinPrice > $arOfferItem["PRICES"]["MIN_RUB"])
					$dblAllMinPrice = $arOfferItem["PRICES"]["MIN_RUB"];
				else
					continue;
			}
		}

		$arOfferItem['PROPERTIES'] = $this->getItemProps($obOfferItem, $arItem);
		$arCurrentOffer = $arOfferItem;

		if (!empty($arCurrentOffer) && $arCurrentOffer["PRICES"]["MIN"] > 0)
		{
			$arOfferItem = $arCurrentOffer;
			$arOfferItem = $this->getItemParams($arOfferItem);
		}
		else
		{
			$arOfferItem = array();
		}

		return $arOfferItem;
	}

	protected function nextItem()
	{
		/** @var \_CIBElement $obItem */
		if(!$obItem = $this->dbItems->GetNextElement())
			return false;
		$arItem = $obItem->GetFields();

		$arItem['PROPERTIES'] = $obItem->GetProperties($arItem);
		$arItem["CATEGORIES"] = $this->getCategories($arItem["ID"]);
		$arItem["DETAIL_PICTURE_URL"] = $this->getPictureUrl((int)$arItem["DETAIL_PICTURE"]);
		$arItem["PREVIEW_PICTURE_URL"] = $this->getPictureUrl((int)$arItem["PREVIEW_PICTURE"]);
		$arItem['OFFERS'] = array();

		$strOfferTemplateURL = $this->getOfferTemplateUrl();
		$rsOfferItems = $this->getOffersItemsDb($arItem["ID"]);

		if (!empty($strOfferTemplateURL))
			$rsOfferItems->SetUrlTemplates($strOfferTemplateURL);

		if (YANDEX_SKU_EXPORT_MIN_PRICE == $this->arSKUExport['SKU_EXPORT_COND'])
		{
			$arOfferItem = $this->getMinPriceOffer($rsOfferItems, $arItem);

			if(!empty($arOfferItem))
			{
				$arOfferItem = $this->getItemParams($arOfferItem);
				$arItem['OFFERS'][] = $arOfferItem;
			}
		}
		else
		{
			while ($obOfferItem = $rsOfferItems->GetNextElement())
			{
				$arOfferItem = $obOfferItem->GetFields();
				$arOfferItem["PRICES"] = $this->getPrices($arOfferItem["ID"], $this->arOfferIBlock['LID']);

				if ($arOfferItem["PRICES"]["MIN"] <= 0)
					continue;

				$arOfferItem['PROPERTIES'] = $this->getItemProps($obOfferItem, $arItem);
				$arOfferItem = $this->getItemParams($arOfferItem);
				$arItem['OFFERS'][] = $arOfferItem;
			}
		}

		if(empty($arItem['OFFERS']) && $this->catalogType == \CCatalogSku::TYPE_FULL)
		{
			$arItem["QUANTITY"] = $this->getQuantity($arItem["ID"]);
			$arItem["PRICES"] = $this->getPrices($arItem["ID"], $this->arIblock['LID']);
			$arItem["PARAMS"] = $this->getParams($arItem, $this->arIblock);
			$arItem["DETAIL_PAGE_URL"] = $this->getDetailPageUrl($arItem["~DETAIL_PAGE_URL"]);
		}

		return $arItem;
	}
}