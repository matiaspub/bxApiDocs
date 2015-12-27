<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo\Engine;

use Bitrix\Main\Context;
use Bitrix\Seo\Engine;
use Bitrix\Seo\IEngine;
use Bitrix\Main\Text;

// to use Yandex.Direct Sandbox
define('YANDEX_DIRECT_LIVE_API_URL', "https://api-sandbox.direct.yandex.ru/live/v4/json/");

if(!defined('YANDEX_DIRECT_LIVE_API_URL'))
{
	// define('YANDEX_DIRECT_LIVE_API_URL', 'https://api.direct.yandex.ru/live/v4/json/');
}

/**
 * Class YandexDirectLive
 *
 * @deprecated
 */
class YandexDirectLive extends Engine\YandexDirect implements IEngine
{
	const API_URL = YANDEX_DIRECT_LIVE_API_URL;

	const METHOD_STAT_BANNER = 'GetBannersStat';

	const MAX_STAT_DAYS_DELTA = 7;

	const ERROR_WRONG_CURRENCY = 245;

	public $allowedCurrency = array('RUB', 'CHF', 'EUR', 'KZT', 'TRY', 'UAH', 'USD');

	/**
	 * Sends request for banner stats.
	 *
	 * @param array $params Stats query params.
	 *
	 * @return array
	 * @throws YandexDirectException
	 * @throws \Bitrix\Main\SystemException
	 *
	 * @see https://tech.yandex.ru/direct/doc/dg-v4/live/GetBannersStat-docpage/
	 */
	public function getBannerStats(array $params)
	{
		$result = $this->query(static::METHOD_STAT_BANNER, $params);
		$result = YandexJson::decode($result->getResult());

		if(!empty($result['error_code']))
		{
			throw new YandexDirectException($result);
		}

		return $result["data"];
	}
}