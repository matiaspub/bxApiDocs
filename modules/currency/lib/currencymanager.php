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

	const EVENT_ON_AFTER_UPDATE_BASE_RATE = 'onAfterUpdateCurrencyBaseRate';
	const EVENT_ON_UPDATE_BASE_CURRENCY = 'onUpdateBaseCurrency';
	const EVENT_ON_AFTER_UPDATE_BASE_CURRENCY = 'onAfterUpdateBaseCurrency';

	protected static $baseCurrency = '';

	/**
	 * Check currency id.
	 *
	 * @param string $currency	Currency id.
	 * @return bool|string
	 */
	
	/**
	* <p>Метод проверяет идентификатор валюты. Метод статический.</p>
	*
	*
	* @param string $currency  Идентификатор валюты.
	*
	* @return mixed <p>В случае успеха возвращает идентификатор валюты. В случае
	* ошибки - <i>false</i>. </p><a name="example"></a>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/currency/currencymanager/checkcurrencyid.php
	* @author Bitrix
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
	
	/**
	* <p>Метод проверяет идентификатор языка интерфейса. Метод статический.</p>
	*
	*
	* @param string $language  Идентификатор языка интерфейса.
	*
	* @return mixed <p>В случае успеха возвращает идентификатор языка. В случае ошибки
	* - <i>false</i>. </p><a name="example"></a>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/currency/currencymanager/checklanguage.php
	* @author Bitrix
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
	
	/**
	* <p>Метод возвращает код базовой валюты.</p> <p>Одна из валют сайта должна иметь курс, равный 1, флаг <code>BASE = 'Y'</code> и не иметь записей в таблице курсов. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/currency/currencymanager/getbasecurrency.php
	* @author Bitrix
	*/
	public static function getBaseCurrency()
	{
		if (self::$baseCurrency === '')
		{
			/** @var \Bitrix\Main\Data\ManagedCache $managedCache */
			$skipCache = (defined('CURRENCY_SKIP_CACHE') && CURRENCY_SKIP_CACHE);
			$currencyFound = false;
			$currencyFromCache = false;
			if (!$skipCache)
			{
				$cacheTime = (int)(defined('CURRENCY_CACHE_TIME') ? CURRENCY_CACHE_TIME : CURRENCY_CACHE_DEFAULT_TIME);
				$managedCache = Application::getInstance()->getManagedCache();
				$currencyFromCache = $managedCache->read($cacheTime, self::CACHE_BASE_CURRENCY_ID, CurrencyTable::getTableName());
				if ($currencyFromCache)
				{
					$currencyFound = true;
					self::$baseCurrency = (string)$managedCache->get(self::CACHE_BASE_CURRENCY_ID);
				}
			}
			if ($skipCache || !$currencyFound)
			{
				$currencyIterator = CurrencyTable::getList(array(
					'select' => array('CURRENCY'),
					'filter' => array('=BASE' => 'Y', '=AMOUNT' => 1)
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
				$managedCache->set(self::CACHE_BASE_CURRENCY_ID, self::$baseCurrency);
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
	
	/**
	* <p>Метод возвращает список всех валют в следующем формате: ключ - код валюты, значение - название валюты на текущем языке. Метод статический.</p> <p>Без параметров</p>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/currency/currencymanager/getcurrencylist.php
	* @author Bitrix
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
				'select' => array('CURRENCY', 'FULL_NAME' => 'CURRENT_LANG_FORMAT.FULL_NAME', 'SORT'),
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
	
	/**
	* <p>Метод возвращает список валют, созданных при установке модуля <b>Валюты</b>. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/currency/currencymanager/getinstalledcurrencies.php
	* @author Bitrix
	*/
	public static function getInstalledCurrencies()
	{
		$installedCurrencies = (string)Option::get('currency', 'installed_currencies');
		if ($installedCurrencies === '')
		{
			$bitrix24 = Main\ModuleManager::isModuleInstalled('bitrix24');

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
				$languageList = array();
				$languageIterator = LanguageTable::getList(array(
					'select' => array('ID'),
					'filter' => array('@ID' => array('kz', 'by', 'ua'), '=ACTIVE' => 'Y')
				));
				while ($language = $languageIterator->fetch())
					$languageList[$language['ID']] = $language['ID'];
				unset($language, $languageIterator);
				if (isset($languageList['kz']))
					$languageID = 'kz';
				elseif (isset($languageList['by']))
					$languageID = 'by';
				elseif (isset($languageList['ua']))
					$languageID = 'ua';
				unset($languageList);
			}
			unset($bitrix24);

			switch ($languageID)
			{
				case 'br':
					$currencyList = array('BYN', 'RUB', 'USD', 'EUR');
					break;
				case 'ua':
					$currencyList = array('UAH', 'RUB', 'USD', 'EUR');
					break;
				case 'kz':
					$currencyList = array('KZT', 'RUB', 'USD', 'EUR');
					break;
				case 'ru':
					$currencyList = array('RUB', 'USD', 'EUR', 'UAH', 'BYN');
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
	
	/**
	* <p>Метод сбрасывает кеш валют. Метод статический.</p>
	*
	*
	* @param string $language = '' Идентификатор языка интерфейса.
	*
	* @return void <p>Нет.</p><a name="example"></a>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/currency/currencymanager/clearcurrencycache.php
	* @author Bitrix
	*/
	public static function clearCurrencyCache($language = '')
	{
		$language = static::checkLanguage($language);
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

		/** @global \CStackCacheManager $stackCacheManager */
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
	
	/**
	* <p>Метод сбрасывает тегированный кеш валюты <code>currency</code>. Метод статический.</p>
	*
	*
	* @param string $currency  Идентификатор валюты.
	*
	* @return void <p>Нет.</p><a name="example"></a>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/currency/currencymanager/cleartagcache.php
	* @author Bitrix
	*/
	public static function clearTagCache($currency)
	{
		if (!defined('BX_COMP_MANAGED_CACHE'))
			return;
		$currency = static::checkCurrencyID($currency);
		if ($currency === false)
			return;
		Application::getInstance()->getTaggedCache()->clearByTag('currency_id_'.$currency);
	}

	/**
	 * Agent for update current currencies rates to base currency.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод для расчета и обновления текущей (актуальной) стоимости одной единицы валюты в базовой. Метод статический и необходим для корректной сортировки и фильтрации по ценам с учетом валют.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/currency/currencymanager/currencybaserateagent.php
	* @author Bitrix
	*/
	public static function currencyBaseRateAgent()
	{
		static::updateBaseRates();
		return '\Bitrix\Currency\CurrencyManager::currencyBaseRateAgent();';
	}

	/**
	 * Update current currencies rates to base currency.
	 *
	 * @param string $updateCurrency		Update currency id.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	
	/**
	* <p>Метод вычисляет и сохраняет в таблице валют актуальный курс валюты <code>updateCurrency</code> на текущую дату по отношению к базовой на основании курсов валют, уже занесенных в базу. Метод статический.</p>
	*
	*
	* @param string $updateCurrency = '' Идентификатор валюты.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/currency/currencymanager/updatebaserates.php
	* @author Bitrix
	*/
	public static function updateBaseRates($updateCurrency = '')
	{
		$currency = (string)static::getBaseCurrency();
		if ($currency === '')
			return;

		$currencyIterator = CurrencyTable::getList(array(
			'select' => array('CURRENCY', 'CURRENT_BASE_RATE'),
			'filter' => ($updateCurrency == '' ? array() : array('=CURRENCY' => $updateCurrency))
		));
		while ($existCurrency = $currencyIterator->fetch())
		{
			$baseRate = ($existCurrency['CURRENCY'] != $currency
				? \CCurrencyRates::getConvertFactorEx($existCurrency['CURRENCY'], $currency)
				: 1
			);
			$updateResult = CurrencyTable::update($existCurrency['CURRENCY'], array('CURRENT_BASE_RATE' => $baseRate));
			if ($updateResult->isSuccess())
			{
				$event = new Main\Event(
					'currency',
					self::EVENT_ON_AFTER_UPDATE_BASE_RATE,
					array(
						'OLD_BASE_RATE' => (float)$existCurrency['CURRENT_BASE_RATE'],
						'CURRENT_BASE_RATE' => $baseRate,
						'BASE_CURRENCY' => $currency,
						'CURRENCY' => $existCurrency['CURRENCY']
					)
				);
				$event->send();
			}
			unset($updateResult);
			unset($baseRate);
		}
		unset($existCurrency, $currencyIterator);
	}

	/**
	 * Update base currency.
	 *
	 * @param string $currency			Currency id.
	 * @return bool
	 */
	
	/**
	* <p>Метод валюту <code>currency</code> делает базовой. Метод статический.</p>
	*
	*
	* @param string $currency  Идентификатор валюты.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/currency/currencymanager/updatebasecurrency.php
	* @author Bitrix
	*/
	public static function updateBaseCurrency($currency)
	{
		/** @global \CUser $USER */
		global $USER;
		$currency = CurrencyManager::checkCurrencyID($currency);
		if ($currency === false)
			return false;

		$event = new Main\Event(
			'currency',
			self::EVENT_ON_UPDATE_BASE_CURRENCY,
			array(
				'NEW_BASE_CURRENCY' => $currency
			)
		);
		$event->send();
		unset($event);

		$conn = Main\Application::getConnection();
		$helper = $conn->getSqlHelper();

		$userID = (isset($USER) && $USER instanceof \CUser ? (int)$USER->getID() : 0);

		$tableName = $helper->quote(CurrencyTable::getTableName());
		$baseField = $helper->quote('BASE');
		$dateUpdateField = $helper->quote('DATE_UPDATE');
		$modifiedByField = $helper->quote('MODIFIED_BY');
		$amountField = $helper->quote('AMOUNT');
		$amountCntField = $helper->quote('AMOUNT_CNT');
		$currencyField = $helper->quote('CURRENCY');
		$query = 'update '.$tableName.' set '.$baseField.' = \'N\', '.
			$dateUpdateField.' = '.$helper->getCurrentDateTimeFunction().', '.
			$modifiedByField.' = '.($userID == 0 ? 'NULL' : $userID).
			' where '.$currencyField.' <> \''.$helper->forSql($currency).'\' and '.$baseField.' = \'Y\'';
		$conn->queryExecute($query);
		$query = 'update '.$tableName.' set '.$baseField.' = \'Y\', '.
			$dateUpdateField.' = '.$helper->getCurrentDateTimeFunction().', '.
			$modifiedByField.' = '.($userID == 0 ? 'NULL' : $userID).', '.
			$amountField.' = 1, '.$amountCntField.' = 1 where '.$currencyField.' = \''.$helper->forSql($currency).'\'';
		$conn->queryExecute($query);

		static::updateBaseRates();

		$event = new Main\Event(
			'currency',
			self::EVENT_ON_AFTER_UPDATE_BASE_CURRENCY,
			array(
				'NEW_BASE_CURRENCY' => $currency
			)
		);
		$event->send();
		unset($event);
		self::$baseCurrency = '';

		return true;
	}
}