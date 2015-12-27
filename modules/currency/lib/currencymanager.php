<?php
namespace Bitrix\Currency;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\LanguageTable;

Loc::loadMessages(__FILE__);

/**
 * Class CurrencyTable
 *
 * @package Bitrix\Currency
 **/
class CurrencyManager
{
	const CACHE_BASE_CURRENCY_ID = 'currency_base_currency';
	const CACHE_CURRENCY_LIST_ID = 'currency_currency_list';
	const CACHE_CURRENCY_SHORT_LIST_ID = 'currency_short_list_';

	protected static $baseCurrency = '';
	protected static $datetimeTemplate = null;

	/**
	 * Check currency id.
	 *
	 * @param string $currency	Currency id.
	 * @return bool|string
	 */
	public static function checkCurrencyID($currency)
	{
		$currency = (string)$currency;
		return ($currency === '' || strlen($currency) > 3 ? false : $currency);
	}

	/**
	 * Check language id.
	 *
	 * @param string $language	Language.
	 * @return bool|string
	 */
	public static function checkLanguage($language)
	{
		$language = (string)$language;
		return ($language === '' || strlen($language) > 2 ? false : $language);
	}

	/**
	 * Return base currency.
	 *
	 * @return string
	 */
	public static function getBaseCurrency()
	{
		if (self::$baseCurrency === '')
		{
			$skipCache = (defined('CURRENCY_SKIP_CACHE') && CURRENCY_SKIP_CACHE);
			$tableName = CurrencyTable::getTableName();
			$currencyFound = false;
			$currencyFromCache = false;
			if (!$skipCache)
			{
				$cacheTime = (int)(defined('CURRENCY_CACHE_TIME') ? CURRENCY_CACHE_TIME : CURRENCY_CACHE_DEFAULT_TIME);
				$managedCache = Application::getInstance()->getManagedCache();
				$currencyFromCache = $managedCache->read($cacheTime, self::CACHE_BASE_CURRENCY_ID, $tableName);
				if ($currencyFromCache)
				{
					$currencyFound = true;
					self::$baseCurrency = (string)$managedCache->get(self::CACHE_BASE_CURRENCY_ID, $tableName);
				}
			}
			if ($skipCache || !$currencyFound)
			{
				$currencyIterator = CurrencyTable::getList(array(
					'select' => array('CURRENCY'),
					'filter' => array('=BASE' => 'Y', 'AMOUNT' => 1)
				));
				if ($currency = $currencyIterator->fetch())
				{
					$currencyFound = true;
					self::$baseCurrency = $currency['CURRENCY'];
				}
				unset($currency, $currencyIterator);
			}
			if (!$skipCache && $currencyFound && !$currencyFromCache)
			{
				$managedCache->set(self::CACHE_BASE_CURRENCY_ID, self::$baseCurrency, $tableName);
			}
		}
		return self::$baseCurrency;
	}

	/**
	 * Return currency short list.
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getCurrencyList()
	{
		$currencyTableName = CurrencyTable::getTableName();
		$managedCache = Application::getInstance()->getManagedCache();

		$cacheTime = (int)(defined('CURRENCY_CACHE_TIME') ? CURRENCY_CACHE_TIME : CURRENCY_CACHE_DEFAULT_TIME);
		$cacheId = self::CACHE_CURRENCY_SHORT_LIST_ID.LANGUAGE_ID;

		if ($managedCache->read($cacheTime, $cacheId, $currencyTableName))
		{
			$currencyList = $managedCache->get($cacheId);
		}
		else
		{
			$currencyList = array();
			$currencyIterator = CurrencyTable::getList(array(
				'select' => array('CURRENCY', 'FULL_NAME' => 'CURRENT_LANG_FORMAT.FULL_NAME'),
				'order' => array('SORT' => 'ASC', 'CURRENCY' => 'ASC')
			));
			while ($currency = $currencyIterator->fetch())
			{
				$currency['FULL_NAME'] = (string)$currency['FULL_NAME'];
				$currencyList[$currency['CURRENCY']] = $currency['CURRENCY'].($currency['FULL_NAME'] != '' ? ' ('.$currency['FULL_NAME'].')' : '');
			}
			unset($currency, $currencyIterator);
			$managedCache->set($cacheId, $currencyList);
		}
		return $currencyList;
	}

	/**
	 * Return currency list, create to install module.
	 *
	 * @return array
	 */
	public static function getInstalledCurrencies()
	{
		$installedCurrencies = (string)Option::get('currency', 'installed_currencies');
		if ($installedCurrencies === '')
		{
			$bitrix24 = Main\ModuleManager::isModuleInstalled('bitrix24');
			$currencyList = array();

			$languageID = '';
			$siteIterator = Main\SiteTable::getList(array(
				'select' => array('LID', 'LANGUAGE_ID'),
				'filter' => array('=DEF' => 'Y', '=ACTIVE' => 'Y')
			));
			if ($site = $siteIterator->fetch())
				$languageID = (string)$site['LANGUAGE_ID'];
			unset($site, $siteIterator);

			if ($languageID == '')
				$languageID = 'en';

			if (!$bitrix24 && $languageID == 'ru')
			{
				$searched = false;
				$languageIterator = LanguageTable::getList(array(
					'select' => array('ID'),
					'filter' => array('=ID' => 'kz')
				));
				if ($oneLanguage = $languageIterator->fetch())
				{
					$searched = true;
					$languageID = 'kz';
				}
				unset($oneLanguage, $languageIterator);
				if (!$searched)
				{
					$languageIterator = LanguageTable::getList(array(
						'select' => array('ID'),
						'filter' => array('=ID' => 'ua')
					));
					if ($oneLanguage = $languageIterator->fetch())
					{
						$languageID = 'ua';
					}
					unset($oneLanguage, $languageIterator);
				}
			}
			unset($bitrix24);

			switch ($languageID)
			{
				case 'ua':
					$currencyList = array('UAH', 'RUB', 'USD', 'EUR');
					break;
				case 'kz':
					$currencyList = array('KZT', 'RUB', 'USD', 'EUR');
					break;
				case 'ru':
					$currencyList = array('RUB', 'USD', 'EUR', 'UAH', 'BYR');
					break;
				case 'de':
				case 'en':
				case 'tc':
				case 'sc':
				case 'la':
				default:
					$currencyList = array('USD', 'EUR', 'CNY', 'BRL', 'INR');
					break;
			}

			Option::set('currency', 'installed_currencies', implode(',', $currencyList), '');
			return $currencyList;
		}
		else
		{
			return explode(',', $installedCurrencies);
		}
	}

	/**
	 * Clear currency cache.
	 *
	 * @param string $language		Language id.
	 * @return void
	 */
	public static function clearCurrencyCache($language = '')
	{
		$language = self::checkLanguage($language);
		$currencyTableName = CurrencyTable::getTableName();

		$managedCache = Application::getInstance()->getManagedCache();
		$managedCache->clean(self::CACHE_CURRENCY_LIST_ID, $currencyTableName);
		if (empty($language))
		{
			$languageIterator = LanguageTable::getList(array(
				'select' => array('ID')
			));
			while ($oneLanguage = $languageIterator->fetch())
			{
				$managedCache->clean(self::CACHE_CURRENCY_LIST_ID.'_'.$oneLanguage['ID'], $currencyTableName);
				$managedCache->clean(self::CACHE_CURRENCY_SHORT_LIST_ID.$oneLanguage['ID'], $currencyTableName);
			}
			unset($oneLanguage, $languageIterator);
		}
		else
		{
			$managedCache->clean(self::CACHE_CURRENCY_LIST_ID.'_'.$language, $currencyTableName);
			$managedCache->clean(self::CACHE_CURRENCY_SHORT_LIST_ID.$language, $currencyTableName);
		}
		$managedCache->clean(self::CACHE_BASE_CURRENCY_ID, $currencyTableName);

		global $stackCacheManager;
		$stackCacheManager->clear('currency_rate');
		$stackCacheManager->clear('currency_currency_lang');
	}

	/**
	 * Clear tag currency cache.
	 *
	 * @param string $currency	Currency id.
	 * @return void
	 */
	public static function clearTagCache($currency)
	{
		if (defined('BX_COMP_MANAGED_CACHE'))
		{
			$currency = (string)$currency;
			if ($currency !== '')
			{
				$taggedCache = Application::getInstance()->getTaggedCache();
				$taggedCache->clearByTag('currency_id_'.$currency);
			}
		}
	}

	/**
	 * Return datetime template for old api emulation.
	 *
	 * @return string
	 */
	public static function getDatetimeExpressionTemplate()
	{
		if (self::$datetimeTemplate === null)
		{
			$helper = Application::getConnection()->getSqlHelper();
			$format = Context::getCurrent()->getCulture()->getDateTimeFormat();
			$datetimeFieldName = '#FIELD#';
			$datetimeField = $datetimeFieldName;
			if (\CTimeZone::enabled())
			{
				$diff = \CTimeZone::getOffset();
				if ($diff <> 0)
					$datetimeField = $helper->addSecondsToDateTime($diff, $datetimeField);
				unset($diff);
			}
			self::$datetimeTemplate = str_replace(
				array('%', $datetimeFieldName),
				array('%%', '%1$s'),
				$helper->formatDate($format, $datetimeField)
			);
			unset($datetimeField, $datetimeFieldName, $format, $helper);
		}
		return self::$datetimeTemplate;
	}
}