<?
namespace Sale\Handlers\Delivery\Spsr;

use \Bitrix\Main\Application;

/**
 * Working with cache for faster work.
 * Class Cache
 * @package Sale\Handlers\Delivery\Spsr
 * todo: multiple SPSR handlers.
 */
class Cache
{
	protected static function getCache($ttl, $cacheId)
	{
		$result = false;
		$cacheManager = Application::getInstance()->getManagedCache();

		if($cacheManager->read($ttl, $cacheId))
			$result = $cacheManager->get($cacheId);

		return $result;
	}
	protected static function setCache($cacheId, $value)
	{
		$cacheManager = Application::getInstance()->getManagedCache();
		$cacheManager->set($cacheId, $value);
	}
	protected static function cleanCache($cacheId)
	{
		$cacheManager = Application::getInstance()->getManagedCache();
		$cacheManager->clean($cacheId);
	}

	public static function cleanAll()
	{
		self::cleanSid();
		self::cleanServiceTypes();
		self::cleanCalcRes();
	}

	public static function getSidResult()
	{
		// day
		return self::getCache(86400, 'SALE_DELIVERY_HANDL_SPSR_SESSID');
	}
	public static function setSid($sid)
	{
		self::setCache('SALE_DELIVERY_HANDL_SPSR_SESSID', $sid);
	}
	public static function cleanSid()
	{
		self::cleanCache('SALE_DELIVERY_HANDL_SPSR_SESSID');
	}


	public static function getServiceTypes()
	{
		// month
		return self::getCache(2592000, 'SALE_DELIVERY_HANDL_SPSR_ST');
	}
	public static function setServiceTypes(array $serviceTypes)
	{
		self::setCache('SALE_DELIVERY_HANDL_SPSR_ST', $serviceTypes);
	}
	public static function cleanServiceTypes()
	{
		self::cleanCache('SALE_DELIVERY_HANDL_SPSR_ST');
	}


	public static function getCalcRes($request)
	{
		// week
		return self::getCache(604800, 'SALE_DELIVERY_HANDL_SPSR_CR_'.md5($request));
	}
	public static function setCalcRes(array $calcRes, $request)
	{
		self::setCache('SALE_DELIVERY_HANDL_SPSR_CR_'.md5($request), $calcRes);
	}
	public static function cleanCalcRes()
	{
		self::cleanCache('SALE_DELIVERY_HANDL_SPSR_ST');
	}
}