<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/catalog_sku.php");


/**
 * Это вспомогательный класс для получения информации об инфоблоках, свойствах и элементах инфоблоков, относящихся к SKU.</body> </html>
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/catalogsku/index.php
 * @author Bitrix
 */
class CCatalogSKU extends CAllCatalogSKU
{
	static public function GetInfoByIBlock($intIBlockID)
	{
		global $DB;

		$intIBlockID = (int)$intIBlockID;
		if ($intIBlockID <= 0)
			return false;

		if (!isset(self::$arIBlockCache[$intIBlockID]))
		{
			$arResult = false;
			$arIBlock = array();
			$arProductIBlock = array();
			$boolExists = false;
			$boolIBlock = false;
			$boolProductIBlock = false;
			$strSql = "select IBLOCK_ID, PRODUCT_IBLOCK_ID, SKU_PROPERTY_ID, VAT_ID, YANDEX_EXPORT, SUBSCRIPTION
					from b_catalog_iblock
					where IBLOCK_ID = ".$intIBlockID." or PRODUCT_IBLOCK_ID = ".$intIBlockID;
			$rsCatalogs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arCatalog = $rsCatalogs->Fetch())
			{
				$arCatalog['IBLOCK_ID'] = (int)$arCatalog['IBLOCK_ID'];
				$arCatalog['PRODUCT_IBLOCK_ID'] = (int)$arCatalog['PRODUCT_IBLOCK_ID'];
				$arCatalog['SKU_PROPERTY_ID'] = (int)$arCatalog['SKU_PROPERTY_ID'];
				$arCatalog['VAT_ID'] = (int)$arCatalog['VAT_ID'];
				$boolExists = true;
				if ($arCatalog['IBLOCK_ID'] == $intIBlockID)
				{
					$boolIBlock = true;
					$arIBlock = $arCatalog;
				}
				elseif ($arCatalog['PRODUCT_IBLOCK_ID'] == $intIBlockID)
				{
					$boolProductIBlock = true;
					$arProductIBlock = $arCatalog;
				}
			}
			if ($boolExists)
			{
				if ($boolProductIBlock && $boolIBlock)
				{
					$arResult = $arProductIBlock;
					$arResult['VAT_ID'] = $arIBlock['VAT_ID'];
					$arResult['YANDEX_EXPORT'] = $arIBlock['YANDEX_EXPORT'];
					$arResult['SUBSCRIPTION'] = $arIBlock['SUBSCRIPTION'];
					$arResult['CATALOG_TYPE'] = self::TYPE_FULL;
				}
				elseif ($boolIBlock)
				{
					$arResult = $arIBlock;
					$arResult['CATALOG_TYPE'] = (0 < $arResult['PRODUCT_IBLOCK_ID'] ? self::TYPE_OFFERS : self::TYPE_CATALOG);
				}
				else
				{
					$arResult = $arProductIBlock;
					unset($arResult['VAT_ID']);
					unset($arResult['YANDEX_EXPORT']);
					unset($arResult['SUBSCRIPTION']);
					$arResult['CATALOG_TYPE'] = self::TYPE_PRODUCT;
				}
				$arResult['CATALOG'] = ($boolIBlock ? 'Y' : 'N');
			}
			self::$arIBlockCache[$intIBlockID] = $arResult;
		}
		else
		{
			$arResult = self::$arIBlockCache[$intIBlockID];
		}
		return $arResult;
	}
}
?>