<?php
namespace Bitrix\Currency\Helpers\Admin;

use Bitrix\Main\Localization\Loc,
	Bitrix\Currency;

Loc::loadMessages(__FILE__);

/**
 * Class Tools
 * Provides various useful methods for admin pages.
 *
 * @package Bitrix\Currency\Helpers\Admin
 */
class Tools
{
	/**
	 * Return array with edit url for all currencies.
	 *
	 * @return array
	 */
	public static function getCurrencyLinkList()
	{
		global $APPLICATION;

		$result = array();
		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		$currencyLinkTitle = htmlspecialcharsbx(
			($APPLICATION->getGroupRight('currency') < 'W')
			? Loc::getMessage('CURRENCY_HELPERS_ADMIN_TOOLS_MESS_CURRENCY_VIEW_TITLE')
			: Loc::getMessage('CURRENCY_HELPERS_ADMIN_TOOLS_MESS_CURRENCY_EDIT_TITLE')
		);

		$currencyList = Currency\CurrencyManager::getCurrencyList();
		foreach ($currencyList as $currency => $title)
		{
			$result[$currency] = '<a href="/bitrix/admin/currency_edit.php?ID='.urlencode($currency).'&lang='.LANGUAGE_ID.
				'" title="'.$currencyLinkTitle.'">'.htmlspecialcharsbx($title).'</a>';
		}
		unset($currency, $title, $currencyList);

		return $result;
	}
}