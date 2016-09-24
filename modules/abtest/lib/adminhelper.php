<?php

namespace Bitrix\ABTest;

use Bitrix\Main\Loader;
use Bitrix\Main\IO;
use Bitrix\Main\Web;
use Bitrix\Main\Type;
use Bitrix\Conversion;

const MIN_EFFECT = 0.1;

class AdminHelper
{

	/**
	 * Returns script filename by URL
	 *
	 * @param string $site Site ID.
	 * @param string $url URL.
	 * @return string|null
	 */
	public static function getRealPath($site, $url)
	{
		$docRoot = rtrim(\Bitrix\Main\SiteTable::getDocumentRoot($site), '/');

		$url = str_replace('\\', '/', $url);
		$url = \CHTTP::urnEncode($url);
		$uri = new Web\Uri($url);

		$path = \CHTTP::urnDecode($uri->getPath());
		if (substr($path, -1, 1) == '/')
			$path .= 'index.php';

		$file = new IO\File($docRoot.$path);
		if ($file->isExists())
			return substr($file->getPath(), strlen($docRoot));

		if ($rewriteRules = AdminHelper::getRewriteRules($site))
		{
			$pathQuery = \CHTTP::urnDecode($uri->getPathQuery());

			foreach ($rewriteRules as &$item)
			{
				if (preg_match($item['CONDITION'], $pathQuery))
				{
					$url = empty($item['PATH']) && !empty($item['RULE'])
						? preg_replace($item['CONDITION'], $item['RULE'], $pathQuery)
						: $item['PATH'];

					$url = \CHTTP::urnEncode($url);
					$uri = new Web\Uri($url);

					$path = \CHTTP::urnDecode($uri->getPath());

					$file = new IO\File($docRoot.$path);
					if ($file->isExists())
					{
						$pathTmp  = str_replace('.', '', strtolower(ltrim($path, '/\\')));
						$pathTmp7 = substr($pathTmp, 0, 7);

						if ($pathTmp7 == 'upload/' || $pathTmp7 == 'bitrix/')
							continue;

						if ($file->getExtension() != 'php')
							continue;

						return substr($file->getPath(), strlen($docRoot));
					}
				}
			}
		}

		return null;
	}

	/**
	 * Returns urlrewrite array
	 *
	 * @param string $site Site ID.
	 * @return array
	 */
	private static function getRewriteRules($site)
	{
		$docRoot = rtrim(\Bitrix\Main\SiteTable::getDocumentRoot($site), '/');

		$rewriteRules = array();
		$arUrlRewrite =& $rewriteRules;

		$rewriteFile = new IO\File($docRoot.'/urlrewrite.php');
		if ($rewriteFile->isExists())
			include $rewriteFile->getPath();

		return $rewriteRules;
	}

	/**
	 * Returns site traffic capacity
	 *
	 * @param string $id Site ID.
	 * @return array
	 */
	public static function getSiteCapacity($id)
	{
		$cache = new \CPHPCache();

		if ($cache->initCache(time()-strtotime('today'), 'abtest_site_capacity', '/abtest'))
		{
			$capacity = $cache->getVars();
		}
		else if (Loader::includeModule('conversion'))
		{
			if ($conversionRates = Conversion\RateManager::getTypes(array('ACTIVE' => true)))
			{
				$baseRate = array_slice($conversionRates, 0, 1, true);

				$reportContext = new Conversion\ReportContext;

				$from = new \DateTime('first day of last month');
				$to   = new \DateTime('today');

				$capacity = array();

				$res = \Bitrix\Main\SiteTable::getList();
				while ($site = $res->fetch())
				{
					$lid = $site['LID'];

					$reportContext->setAttribute('conversion_site', $lid);

					$rateData = reset($reportContext->getRatesDeprecated(
						$baseRate, array(
							'>=DAY' => Type\Date::createFromPhp($from),
							'<=DAY' => Type\Date::createFromPhp($to)
						), null
					));

					$reportContext->unsetAttribute('conversion_site', $lid);

					$rate = $rateData['RATE'];
					$hits = $rateData['DENOMINATOR'];

					$daily = floor($hits / (date_diff($from, $to)->format('%a')+1));

					$min = $rate > 0 && $rate < 1 ? ceil(16 * (1 / $rate - 1) / pow(MIN_EFFECT, 2)) : 0;
					$est = $daily ? $min / ($daily / 2) : 0;

					$capacity[$lid] = array(
						'daily' => $daily,
						'min'   => $min,
						'est'   => $est
					);
				}

				$cache->startDataCache(strtotime('tomorrow')-time());
				$cache->endDataCache($capacity);
			}
		}

		$result = array();
		foreach ((array) $id as $lid)
			$result[$lid] = isset($capacity[$lid]) ? $capacity[$lid] : array('min' => 0, 'est' => 0);

		return is_array($id) ? $result : reset($result);
	}

	/**
	 * Returns A/B-test traffic amounts
	 *
	 * @param int $id A/B-test ID.
	 * @return array
	 */
	public static function getTestCapacity($id)
	{
		$cache = new \CPHPCache();

		if ($cache->initCache(time()-strtotime('today'), 'abtest_capacity_'.intval($id), '/abtest'))
		{
			$capacity = $cache->getVars();
		}
		else if (Loader::includeModule('conversion'))
		{
			if ($conversionRates = Conversion\RateManager::getTypes(array('ACTIVE' => true)))
			{
				if ($abtest = ABTestTable::getById($id)->fetch())
				{
					$lid = $abtest['SITE_ID'];

					$baseRate = array_slice($conversionRates, 0, 1, true);

					$reportContext = new Conversion\ReportContext;

					$reportContext->setAttribute('conversion_site', $lid);
					$reportContext->setAttribute('abtest', $id);

					$reportContext->setAttribute('abtest_section', 'A');
					$groupAData = reset($reportContext->getRatesDeprecated($baseRate, array(), null));

					$reportContext->unsetAttribute('abtest_section', 'A');
					$reportContext->setAttribute('abtest_section', 'B');
					$groupBData = reset($reportContext->getRatesDeprecated($baseRate, array(), null));

					$capacity = array(
						'A' => $groupAData['DENOMINATOR'],
						'B' => $groupBData['DENOMINATOR']
					);

					$cache->startDataCache(strtotime('tomorrow')-time());
					$cache->endDataCache($capacity);
				}
			}
		}

		return !empty($capacity) ? $capacity : array('A' => 0, 'B' => 0);
	}

}
