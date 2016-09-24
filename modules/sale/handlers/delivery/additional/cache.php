<?
namespace Sale\Handlers\Delivery\Additional;

use \Bitrix\Main\Application;
use Bitrix\Main\ArgumentOutOfRangeException;

/**
 * Class Cache
 * @package Sale\Handlers\Delivery\Additional
 * define const SALE_HNDL_DLV_ADD_CACHE_DISABLE to avoid caching.
 */
class Cache
{
	protected $ttl = 0;
	protected $cacheIdBase = '';

	/** @var \Bitrix\Main\Data\ManagedCache */
	protected static $cacheManager = null;

	/**
	 * Cache constructor.
	 * @param string $type
	 * @param int $ttl
	 */
	public function __construct($type, $ttl)
	{
		if(static::$cacheManager === null)
			static::$cacheManager = Application::getInstance()->getManagedCache();

		$this->cacheIdBase = self::createCacheId($type);
		$this->ttl = $ttl;
	}

	/**
	 * @param string[] $ids
	 * @return mixed
	 */
	public function get(array $ids = array())
	{
		$result = false;
		$cacheId = $this->getCacheId($ids);

		if(static::$cacheManager->read($this->ttl, $cacheId))
			$result = static::$cacheManager->get($cacheId);

		return $result;
	}

	/**
	 * @param mixed $value
	 * @param string[] $ids
	 */
	public function set($value, array $ids = array())
	{
		$cacheId = $this->getCacheId($ids);
		static::$cacheManager->set($cacheId, $value);
	}

	/**
	 * @param array $ids
	 */
	public function clean(array $ids = array())
	{
		$cacheId = $this->getCacheId($ids);
		static::$cacheManager->clean($cacheId);
	}

	/**
	 * @param string[] $ids
	 * @return string
	 */
	protected function getCacheId(array $ids = array())
	{
		$result = $this->cacheIdBase;

		if(!empty($ids))
			$result .= '_'.implode('_',$ids);

		return $result;
	}

	/**
	 * @param string $type
	 * @return string
	 */
	protected static function createCacheId($type)
	{
		return 'SALE_HNDL_DELV_ADD_'.$type;
	}
}

/**
 * Class CacheSession
 * Use session for cache purposes
 * We suggest that smt. (tariffs) will be constant during user shopping.
 * We increase the load on usual cache.
 * @package Sale\Handlers\Delivery\Additional
 */
class CacheSession extends Cache
{
	public function __construct($type, $ttl)
	{
		$this->cacheIdBase = self::createCacheId($type);
	}

	/**
	 * @param mixed $value
	 * @param string[] $ids
	 */
	public function set($value, array $ids = array())
	{
		$cacheId = $this->getCacheId($ids);
		$_SESSION[$cacheId] = $value;
	}

	/**
	 * @param string[] $ids
	 * @return bool
	 */
	public function get(array $ids = array())
	{
		$result = false;
		$cacheId = $this->getCacheId($ids);

		if(isset($_SESSION[$cacheId]))
			$result = $_SESSION[$cacheId];

		return $result;
	}

	/**
	 * @param array $ids
	 */
	public function clean(array $ids = array())
	{
		$cacheId = $this->getCacheId($ids);
		unset($_SESSION[$cacheId]);
	}
}

/**
 * Class CacheManager
 * @package Sale\Handlers\Delivery\Additional
 * todo: to clean cache if something changed on server-side.
 */
class CacheManager
{
	protected static $items = array();

	const TYPE_PROFILES_LIST = 1;
	const TYPE_DELIVERY_FIELDS = 2;
	const TYPE_DELIVERY_PRICE = 3;
	const TYPE_PROFILE_FIELDS = 4;
	const TYPE_DELIVERY_LIST = 5;
	const TYPE_PROFILE_CONFIG = 6;
	const TYPE_EXTRA_SERVICES = 7;

	// types of cache
	const LOC_CACHE = 1;
	const LOC_SESSION = 2;

	//Possible cache types & some params
	protected static $types = array(
		self::TYPE_PROFILES_LIST => array('TTL' => 604800, 'LOC' => self::LOC_CACHE), // week cache
		self::TYPE_DELIVERY_FIELDS => array('TTL' => 604800, 'LOC' => self::LOC_CACHE), // week cache
		self::TYPE_DELIVERY_PRICE => array('TTL' => 0, 'LOC' => self::LOC_SESSION), // session
		self::TYPE_PROFILE_FIELDS => array('TTL' => 604800, 'LOC' => self::LOC_CACHE), // week cache
		self::TYPE_DELIVERY_LIST => array('TTL' => 2419200, 'LOC' => self::LOC_CACHE), // month cache
		self::TYPE_PROFILE_CONFIG => array('TTL' => 0, 'LOC' => self::LOC_SESSION), // session
		self::TYPE_EXTRA_SERVICES => array('TTL' => 604800, 'LOC' => self::LOC_CACHE), // week cache
	);

	/**
	 * @param string $type Cache type
	 * @return Cache
	 * @throws ArgumentOutOfRangeException
	 */
	public static function getItem($type)
	{
		if(empty(self::$types[$type]))
			return null;

		if(defined('SALE_HNDL_DLV_ADD_CACHE_DISABLE'))
			return null;

		if(empty(self::$items[$type]))
		{
			if(self::$types[$type]['LOC'] == self::LOC_CACHE)
				self::$items[$type] = new Cache($type, self::$types[$type]['TTL']);
			elseif(self::$types[$type]['LOC'] == self::LOC_SESSION)
				self::$items[$type] = new CacheSession($type, self::$types[$type]['TTL']);
		}

		return self::$items[$type];
	}
}