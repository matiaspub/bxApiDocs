<?php

namespace Bitrix\Conversion;

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;

final class Config
{
	static private $baseCurrency;

	static public function getBaseCurrency()
	{
		if (! $currency =& self::$baseCurrency)
		{
			$currency = Option::get('conversion', 'BASE_CURRENCY', 'RUB');
		}

		return $currency;
	}

	/** @internal
	 * @param string $currency - currency code
	 */
	static public function setBaseCurrency($currency)
	{
		if (! $currency)
		{
			$currency = 'RUB';
		}

		self::$baseCurrency = $currency;

		Option::set('conversion', 'BASE_CURRENCY', $currency);
	}



	/** @deprecated */
	static public function convertToBaseCurrency($value, $currency) // TODO remove from sale
	{
		return Utils::convertToBaseCurrency($value, $currency);
	}

	/** @deprecated */
	static public function formatToBaseCurrency($value, $format = null) // TODO remove from sale
	{
		return Utils::formatToBaseCurrency($value, $format);
	}

	/** @deprecated */
	static public function getBaseCurrencyUnit() // TODO remove from sale
	{
		return Utils::getBaseCurrencyUnit();
	}



	static private $modules = array();

	static public function getModules()
	{
		if (! $modules =& self::$modules)
		{
			$default = array('ACTIVE' => ! ModuleManager::isModuleInstalled('sale'));

			foreach (
				array(
					AttributeManager::getTypesInternal(),
					CounterManager::getTypesInternal(),
					RateManager::getTypesInternal(),
				) as $types)
			{
				foreach ($types as $type)
				{
					$modules[$type['MODULE']] = $default;
				}
			}

			if ($modules['sale'])
			{
				$modules['sale']['ACTIVE'] = true;
			}

			$modules = unserialize(Option::get('conversion', 'MODULES', 'a:0:{}')) + $modules;

			// TODO all modules with attributes must be active
			$modules['conversion'] = $modules['abtest'] = $modules['sender'] = $modules['seo'] = array('ACTIVE' => true);

			ksort($modules);
		}

		return $modules;
	}

	/** @internal */
	static public function setModules(array $modules)
	{
		self::$modules = $modules;
		Option::set('conversion', 'MODULES', serialize($modules));
	}

	static public function isModuleActive($name)
	{
		$modules = self::getModules();
		$module = $modules[$name];
		return $module && $module['ACTIVE'];
	}
}
