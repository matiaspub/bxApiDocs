<?
namespace Bitrix\Sale\TradingPlatform;

class YandexMarket extends Platform
{
	const TRADING_PLATFORM_CODE = "ymarket";

	public static function getInstance()
	{
		return parent::getInstance(self::TRADING_PLATFORM_CODE);
	}
}
