<?
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCatalogCSVSettings
{
	const FIELDS_ELEMENT = 'ELEMENT';
	const FIELDS_CATALOG = 'CATALOG';
	const FIELDS_PRICE = 'PRICE';
	const FIELDS_PRICE_EXT = 'PRICE_EXT';
	const FIELDS_SECTION = 'SECTION';
	const FIELDS_CURRENCY = 'CURRENCY';

	public static function getSettingsFields($type, $extFormat = false)
	{
		$extFormat = ($extFormat === true);
		$result = array();
		$type = (string)$type;
		if ($type !== '')
		{
			switch ($type)
			{
			case self::FIELDS_ELEMENT:
				$result = array(
					'IE_XML_ID' => array(
						'value' => 'IE_XML_ID',
						'field' => 'XML_ID',
						'important' => 'Y',
						'name' => Loc::getMessage('CATI_FI_UNIXML_EXT').' (B_IBLOCK_ELEMENT.XML_ID)'
					),
					'IE_NAME' => array(
						'value' => 'IE_NAME',
						'field' => 'NAME',
						'important' => 'Y',
						'name' => Loc::getMessage('CATI_FI_NAME').' (B_IBLOCK_ELEMENT.NAME)'
					),
					'IE_ACTIVE' => array(
						'value' => 'IE_ACTIVE',
						'field' => 'ACTIVE',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_ACTIV').' (B_IBLOCK_ELEMENT.ACTIVE)'
					),
					'IE_ACTIVE_FROM' => array(
						'value' => 'IE_ACTIVE_FROM',
						'field' => 'ACTIVE_FROM',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_ACTIVFROM').' (B_IBLOCK_ELEMENT.ACTIVE_FROM)'
					),
					'IE_ACTIVE_TO' => array(
						'value' => 'IE_ACTIVE_TO',
						'field' => 'ACTIVE_TO',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_ACTIVTO').' (B_IBLOCK_ELEMENT.ACTIVE_TO)'
					),
					'IE_SORT' => array(
						'value' => 'IE_SORT',
						'field' => 'SORT',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_SORT_EXT').' (B_IBLOCK_ELEMENT.SORT)'
					),
					'IE_PREVIEW_PICTURE' => array(
						'value' => 'IE_PREVIEW_PICTURE',
						'field' => 'PREVIEW_PICTURE',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_CATIMG_EXT').' (B_IBLOCK_ELEMENT.PREVIEW_PICTURE)'
					),
					'IE_PREVIEW_TEXT' => array(
						'value' => 'IE_PREVIEW_TEXT',
						'field' => 'PREVIEW_TEXT',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_CATDESCR_EXT').' (B_IBLOCK_ELEMENT.PREVIEW_TEXT)'
					),
					'IE_PREVIEW_TEXT_TYPE' => array(
						'value' => 'IE_PREVIEW_TEXT_TYPE',
						'field' => 'PREVIEW_TEXT_TYPE',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_CATDESCRTYPE_EXT').' (B_IBLOCK_ELEMENT.PREVIEW_TEXT_TYPE)'
					),
					'IE_DETAIL_PICTURE' => array(
						'value' => 'IE_DETAIL_PICTURE',
						'field' => 'DETAIL_PICTURE',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_DETIMG_EXT').' (B_IBLOCK_ELEMENT.DETAIL_PICTURE)'
					),
					'IE_DETAIL_TEXT' => array(
						'value' => 'IE_DETAIL_TEXT',
						'field' => 'DETAIL_TEXT',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_DETDESCR_EXT').' (B_IBLOCK_ELEMENT.DETAIL_TEXT)'
					),
					'IE_DETAIL_TEXT_TYPE' => array(
						'value' => 'IE_DETAIL_TEXT_TYPE',
						'field' => 'DETAIL_TEXT_TYPE',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_DETDESCRTYPE_EXT').' (B_IBLOCK_ELEMENT.DETAIL_TEXT_TYPE)'
					),
					'IE_CODE' => array(
						'value' => 'IE_CODE',
						'field' => 'CODE',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_CODE_EXT').' (B_IBLOCK_ELEMENT.CODE)'
					),
					'IE_TAGS' => array(
						'value' => 'IE_TAGS',
						'field' => 'TAGS',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_TAGS').' (B_IBLOCK_ELEMENT.TAGS)'
					),
					'IE_ID' => array(
						'value' => 'IE_ID',
						'field' => 'ID',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_ID').' (B_IBLOCK_ELEMENT.ID)'
					)
				);
				break;
			case self::FIELDS_CATALOG:
				$result = array(
					'CP_QUANTITY' => array(
						'value' => 'CP_QUANTITY',
						'field' => 'QUANTITY',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_QUANT').' (B_CATALOG_PRODUCT.QUANTITY)'
					),
					'CP_QUANTITY_TRACE' => array(
						'value' => 'CP_QUANTITY_TRACE',
						'field' => 'QUANTITY_TRACE',
						'field_orig' => 'QUANTITY_TRACE_ORIG',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_QUANTITY_TRACE').' (B_CATALOG_PRODUCT.QUANTITY_TRACE)'
					),
					'CP_CAN_BUY_ZERO' => array(
						'value' => 'CP_CAN_BUY_ZERO',
						'field' => 'CAN_BUY_ZERO',
						'field_orig' => 'CAN_BUY_ZERO_ORIG',
						'important'=>'N',
						'name' => Loc::getMessage('CATI_FI_CAN_BUY_ZERO').' (B_CATALOG_PRODUCT.CAN_BUY_ZERO)'
					),
					'CP_NEGATIVE_AMOUNT_TRACE' => array(
						'value' => 'CP_NEGATIVE_AMOUNT_TRACE',
						'field' => 'NEGATIVE_AMOUNT_TRACE',
						'field_orig' => 'NEGATIVE_AMOUNT_ORIG',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_NEGATIVE_AMOUNT_TRACE').' (B_CATALOG_PRODUCT.NEGATIVE_AMOUNT_TRACE)'
					),
					'CP_WEIGHT' => array(
						'value' => 'CP_WEIGHT',
						'field' => 'WEIGHT',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_WEIGHT').' (B_CATALOG_PRODUCT.WEIGHT)'
					),
					'CP_WIDTH' => array(
						'value' => 'CP_WIDTH',
						'field' => 'WIDTH',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_WIDTH').' (B_CATALOG_PRODUCT.WIDTH)'
					),
					'CP_HEIGHT' => array(
						'value' => 'CP_HEIGHT',
						'field' => 'HEIGHT',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_HEIGHT').' (B_CATALOG_PRODUCT.HEIGHT)'
					),
					'CP_LENGTH' => array(
						'value' => 'CP_LENGTH',
						'field' => 'LENGTH',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_LENGTH').' (B_CATALOG_PRODUCT.LENGTH)'
					),
					'CP_PURCHASING_PRICE' => array(
						'value' => 'CP_PURCHASING_PRICE',
						'field' => 'PURCHASING_PRICE',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_PURCHASING_PRICE').' (B_CATALOG_PRODUCT.PURCHASING_PRICE)'
					),
					'CP_PURCHASING_CURRENCY' => array(
						'value' => 'CP_PURCHASING_CURRENCY',
						'field' => 'PURCHASING_CURRENCY',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_PURCHASING_CURRENCY').' (B_CATALOG_PRODUCT.PURCHASING_CURRENCY)'
					),
					'CP_PRICE_TYPE' => array(
						'value' => 'CP_PRICE_TYPE',
						'field' => 'PRICE_TYPE',
						'important' => 'N',
						'name' => Loc::getMessage('I_PAY_TYPE').' (B_CATALOG_PRODUCT.PRICE_TYPE)'
					),
					'CP_RECUR_SCHEME_LENGTH' => array(
						'value' => 'CP_RECUR_SCHEME_LENGTH',
						'field' => 'RECUR_SCHEME_LENGTH',
						'important' => 'N',
						'name' => Loc::getMessage('I_PAY_PERIOD_LENGTH').' (B_CATALOG_PRODUCT.RECUR_SCHEME_LENGTH)'
					),
					'CP_RECUR_SCHEME_TYPE' => array(
						'value' => 'CP_RECUR_SCHEME_TYPE',
						'field' => 'RECUR_SCHEME_TYPE',
						'important' => 'N',
						'name' => Loc::getMessage('I_PAY_PERIOD_TYPE').' (B_CATALOG_PRODUCT.RECUR_SCHEME_TYPE)'
					),
					'CP_TRIAL_PRICE_ID' => array(
						'value' => 'CP_TRIAL_PRICE_ID',
						'field' => 'TRIAL_PRICE_ID',
						'important' => 'N',
						'name' => Loc::getMessage('I_TRIAL_FOR').' (B_CATALOG_PRODUCT.TRIAL_PRICE_ID)'
					),
					'CP_WITHOUT_ORDER' => array(
						'value' => 'CP_WITHOUT_ORDER',
						'field' => 'WITHOUT_ORDER',
						'important' => 'N',
						'name' => Loc::getMessage('I_WITHOUT_ORDER').' (B_CATALOG_PRODUCT.WITHOUT_ORDER)'
					),
					'CP_VAT_ID' => array(
						'value' => 'CP_VAT_ID',
						'field' => 'VAT_ID',
						'important' => 'N',
						'name' => Loc::getMessage('I_VAT_ID').' (B_CATALOG_PRODUCT.VAT_ID)'
					),
					'CP_VAT_INCLUDED' => array(
						'value' => 'CP_VAT_INCLUDED',
						'field' => 'VAT_INCLUDED',
						'important' => 'N',
						'name' => Loc::getMessage('I_VAT_INCLUDED').' (B_CATALOG_PRODUCT.VAT_INCLUDED)'
					),
					'CP_MEASURE' => array(
						'value' => 'CP_MEASURE',
						'field' => 'MEASURE',
						'important' => 'N',
						'name' => Loc::getMessage('BX_CAT_CSV_SETTINGS_PRODUCT_FIELD_NAME_MEASURE_ID').' (B_CATALOG_PRODUCT.MEASURE)'
					),
				);
				break;
			case self::FIELDS_PRICE:
				$result = array(
					'CV_PRICE' => array(
						'value' => 'CV_PRICE',
						'value_size' => 8,
						'field' => 'PRICE',
						'important' => 'N',
						'name' => Loc::getMessage('I_NAME_PRICE').' (B_CATALOG_PRICE.PRICE)'
					),
					'CV_CURRENCY' => array(
						'value' => 'CV_CURRENCY',
						'value_size' => 11,
						'field' => 'CURRENCY',
						'important' => 'N',
						'name' => Loc::getMessage('I_NAME_CURRENCY').' (B_CATALOG_PRICE.CURRENCY)'
					),
					'CV_EXTRA_ID' => array(
						'value' => 'CV_EXTRA_ID',
						'value_size' => 11,
						'field' => 'EXTRA_ID',
						'important' => 'N',
						'name' => Loc::getMessage('I_NAME_EXTRA_ID').' (B_CATALOG_PRICE.EXTRA_ID)'
					)
				);
				break;
			case self::FIELDS_PRICE_EXT:
				$result = array(
					'CV_QUANTITY_FROM' => array(
						'value' => 'CV_QUANTITY_FROM',
						'field' => 'QUANTITY_FROM',
						'important' => 'N',
						'name' => Loc::getMessage('I_NAME_QUANTITY_FROM').' (B_CATALOG_PRICE.QUANTITY_FROM)'
					),
					'CV_QUANTITY_TO' => array(
						'value' => 'CV_QUANTITY_TO',
						'field' => 'QUANTITY_TO',
						'important' => 'N',
						'name' => Loc::getMessage('I_NAME_QUANTITY_TO').' (B_CATALOG_PRICE.QUANTITY_TO)'
					)
				);
				break;
			case self::FIELDS_SECTION:
				$result = array(
					'IC_ID' => array(
						'value' => 'IC_ID',
						'field' => 'ID',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FI_ID').' (B_IBLOCK_SECTION.ID)'
					),
					'IC_XML_ID' => array(
						'value' => 'IC_XML_ID',
						'field' => 'XML_ID',
						'important' => 'Y',
						'name' => Loc::getMessage('CATI_FG_UNIXML_EXT').' (B_IBLOCK_SECTION.XML_ID)'
					),
					'IC_GROUP' => array(
						'value' => 'IC_GROUP',
						'field' => 'NAME',
						'important' => 'Y',
						'name' => Loc::getMessage('CATI_FG_NAME').' (B_IBLOCK_SECTION.NAME)'
					),
					'IC_ACTIVE' => array(
						'value' => 'IC_ACTIVE',
						'field' => 'ACTIVE',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FG_ACTIV').' (B_IBLOCK_SECTION.ACTIVE)'
					),
					'IC_SORT' => array(
						'value' => 'IC_SORT',
						'field' => 'SORT',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FG_SORT_EXT').' (B_IBLOCK_SECTION.SORT)'
					),
					'IC_DESCRIPTION' => array(
						'value' => 'IC_DESCRIPTION',
						'field' => 'DESCRIPTION',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FG_DESCR').' (B_IBLOCK_SECTION.DESCRIPTION)'
					),
					'IC_DESCRIPTION_TYPE' => array(
						'value' => 'IC_DESCRIPTION_TYPE',
						'field' => 'DESCRIPTION_TYPE',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FG_DESCRTYPE').' (B_IBLOCK_SECTION.DESCRIPTION_TYPE)'
					),
					'IC_CODE' => array(
						'value' => 'IC_CODE',
						'field' => 'CODE',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FG_CODE_EXT2').' (B_IBLOCK_SECTION.CODE)'
					),
					'IC_PICTURE' => array(
						'value' => 'IC_PICTURE',
						'field' => 'PICTURE',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FG_PICTURE').' (B_IBLOCK_SECTION.PICTURE)'
					),
					'IC_DETAIL_PICTURE' => array(
						'value' => 'IC_DETAIL_PICTURE',
						'field' => 'DETAIL_PICTURE',
						'important' => 'N',
						'name' => Loc::getMessage('CATI_FG_DETAIL_PICTURE').' (B_IBLOCK_SECTION.DETAIL_PICTURE)'
					)
				);
				break;
			}
		}
		return ($extFormat ? $result : array_values($result));
	}

	public static function getDefaultSettings($type, $extFormat = false)
	{
		$extFormat = ($extFormat === true);
		$result = ($extFormat ? array() : '');
		$type = (string)$type;
		if ($type !== '')
		{
			switch ($type)
			{
			case self::FIELDS_ELEMENT:
				$result = (
					$extFormat
					? array('IE_XML_ID', 'IE_NAME', 'IE_PREVIEW_TEXT', 'IE_DETAIL_TEXT')
					: 'IE_XML_ID,IE_NAME,IE_PREVIEW_TEXT,IE_DETAIL_TEXT'
				);
				break;
			case self::FIELDS_CATALOG:
				$result = (
					$extFormat
					? array('CP_QUANTITY' ,'CP_WEIGHT', 'CP_WIDTH', 'CP_HEIGHT', 'CP_LENGTH')
					: 'CP_QUANTITY,CP_WEIGHT,CP_WIDTH,CP_HEIGHT,CP_LENGTH'
				);
				break;
			case self::FIELDS_PRICE:
				$result = (
					$extFormat
					? array('CV_PRICE', 'CV_CURRENCY')
					: 'CV_PRICE,CV_CURRENCY'
				);
				break;
			case self::FIELDS_PRICE_EXT:
				$result = (
					$extFormat
					? array('CV_QUANTITY_FROM', 'CV_QUANTITY_TO')
					: 'CV_QUANTITY_FROM,CV_QUANTITY_TO'
				);
				break;
			case self::FIELDS_SECTION:
				$result = (
					$extFormat
					? array('IC_GROUP')
					: 'IC_GROUP'
				);
				break;
			case self::FIELDS_CURRENCY:
				$result = (
					$extFormat
					? array('USD')
					: 'USD'
				);
				break;
			}
		}
		return $result;
	}
}