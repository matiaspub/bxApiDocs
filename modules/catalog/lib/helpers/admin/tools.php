<?php
namespace Bitrix\Catalog\Helpers\Admin;

use Bitrix\Main\Localization\Loc,
	Bitrix\Catalog;

Loc::loadMessages(__FILE__);

/**
 * Class Tools
 * Provides various useful methods for admin pages.
 *
 * @package Bitrix\Catalog\Helpers\Admin
 */
class Tools
{
	/**
	 * Return array with edit url for all price types.
	 *
	 * @return array
	 */
	public static function getPriceTypeLinkList()
	{
		global $USER;

		$result = array();
		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		if (!($USER->canDoOperation('catalog_read') || $USER->canDoOperation('catalog_group')))
			return $result;
		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		$priceTypeLinkTitle = htmlspecialcharsbx(
			$USER->canDoOperation('catalog_group')
			? Loc::getMessage('CATALOG_HELPERS_ADMIN_TOOLS_MESS_PRICE_TYPE_EDIT_TITLE')
			: Loc::getMessage('CATALOG_HELPERS_ADMIN_TOOLS_MESS_PRICE_TYPE_VIEW_TITLE')
		);

		//TODO: use d7 managed cache for price type list
		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		$priceTypeList = \CCatalogGroup::getListArray();
		if (empty($priceTypeList))
			return $result;
		foreach ($priceTypeList as $priceType)
		{
			$id = (int)$priceType['ID'];
			$title = (string)$priceType['NAME_LANG'];
			$fullTitle = '['.$id.'] ['.$priceType['NAME'].']'.($title != '' ? ' '.$title : '');
			$result[$id] = '<a href="/bitrix/admin/cat_group_edit.php?ID='.$id.'&lang='.LANGUAGE_ID.
				'" title="'.$priceTypeLinkTitle.'">'.htmlspecialcharsbx($fullTitle).'</a>';

			unset($fullTitle, $title, $id);
		}
		unset($priceType, $priceTypeList, $priceTypeLinkTitle);

		return $result;
	}

	/**
	 * Return price type list for dropdown selectors.
	 *
	 * @param bool $codeIndex	Use price code for result index.
	 * @return array
	 */
	public static function getPriceTypeList($codeIndex = false)
	{
		$result = array();
		$codeIndex = ($codeIndex === true);
		//TODO: use d7 managed cache for price type list
		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		foreach (\CCatalogGroup::getListArray() as $priceType)
		{
			$id = (int)$priceType['ID'];
			$title = (string)$priceType['NAME_LANG'];
			$index = ($codeIndex ? $priceType['NAME'] : $id);
			$result[$index] = '['.$id.'] ['.$priceType['NAME'].']'.($title != '' ? ' '.$title : '');
			unset($index, $title, $id);
		}
		unset($priceType);

		return $result;
	}
}