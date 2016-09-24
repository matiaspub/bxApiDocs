<?php
namespace Bitrix\Main\Data;

use Bitrix\Main;

/**
 * Class StaticHtmlCache
 * @package Bitrix\Main\Data
 */
class StaticHtmlCache
{
	/**
	 * @var StaticHtmlCache
	 */
	protected static $instance = null;
	/**
	 * @var string
	 */
	private $cacheKey = null;
	/**
	 * @var bool
	 */
	private $canCache = true;

	/**
	 * @var StaticHtmlStorage
	 */
	private $storage = null;

	/**
	 * @var StaticCacheProvider
	 */
	private $cacheProvider = null;

	private $debugEnabled = false;
	private $voting = true;
	/**
	 * Creates new cache manager instance.
	 * @param $requestUri
	 * @param $host
	 * @param null $privateKey
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести  при создании объекта какие-то действия.</p>
	*
	*
	* @param mixed $requestUri  
	*
	* @param $requestUr $host = null 
	*
	* @param null $privateKey = null 
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/__construct.php
	* @author Bitrix
	*/
	public function __construct($requestUri, $host = null, $privateKey = null)
	{
		$this->cacheKey = static::convertUriToPath($requestUri, $host, $privateKey);
		if ($this->cacheKey)
		{
			$this->storage = $this->getStaticHtmlStorage($this->cacheKey);
		}
	}

	/**
	 * Returns current instance of the StaticHtmlCache.
	 *
	 * @return StaticHtmlCache
	 */
	
	/**
	* <p>Статический метод возвращает текущий экземпляр класса <code>\StaticHtmlCache</code>.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Data\StaticHtmlCache 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/getinstance.php
	* @author Bitrix
	*/
	public static function getInstance()
	{
		if (!isset(static::$instance))
		{
			$cacheProvider = static::getCacheProvider();
			$privateKey = $cacheProvider !== null ? $cacheProvider->getCachePrivateKey() : null;

			static::$instance = new static(
				\CHTMLPagesCache::getRequestUri(),
				\CHTMLPagesCache::getHttpHost(),
				\CHTMLPagesCache::getRealPrivateKey($privateKey)
			);

			static::$instance->enableDebug();
			if ($cacheProvider !== null)
			{
				static::$instance->setCacheProvider($cacheProvider);
			}
		}

		return static::$instance;
	}

	public function setCacheProvider(StaticCacheProvider $provider)
	{
		$this->cacheProvider = $provider;
	}

	/**
	 * @return StaticCacheProvider|null
	 */
	private static function getCacheProvider()
	{
		foreach (GetModuleEvents("main", "OnGetStaticCacheProvider", true) as $arEvent)
		{
			$provider = ExecuteModuleEventEx($arEvent);
			if (is_object($provider) && $provider instanceof StaticCacheProvider)
			{
				return $provider;
			}
		}

		return null;
	}

	/*
	 * Returns private cache key
	 */
	public static function getPrivateKey()
	{
		$cacheProvider = static::getCacheProvider();
		return $cacheProvider !== null ? $cacheProvider->getCachePrivateKey() : null;
	}
	/**
	 * Converts request uri into path safe file with .html extention.
	 * Returns empty string if fails.
	 * @param string $uri Uri.
	 * @param string $host Host name.
	 * @param string $privateKey
	 * @return string
	 */
	
	/**
	* <p>Статический метод конвертирует URI запроса в путь сохранения файла с расширением <b>.html</b>.</p> <p>В случае неудачи возвращает пустую строку.</p>
	*
	*
	* @param string $uri  Uri
	*
	* @param string $host = null Имя хоста.
	*
	* @param string $privateKey = null 
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/converturitopath.php
	* @author Bitrix
	*/
	public static function convertUriToPath($uri, $host = null, $privateKey = null)
	{
		return \CHTMLPagesCache::convertUriToPath($uri, $host, $privateKey);
	}

	/**
	 * Returns cache key
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает ключ кеша.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/getcachekey.php
	* @author Bitrix
	*/
	public function getCacheKey()
	{
		return $this->cacheKey;
	}
	/**
	 * Writes the content to the storage
	 * @param string $content the string that is to be written
	 * @param string $md5 the content hash
	 *
	 * @return bool
	 */
	
	/**
	* <p>Нестатический записывает контент в кеш. Возвращает записанную строку, либо <i>false</i> в случае неудачной попытки записи.</p>
	*
	*
	* @param string $content  Строка, которая должна быть записана
	*
	* @param string $md5  хэш контента
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/write.php
	* @author Bitrix
	*/
	public function write($content, $md5)
	{
		if ($this->storage === null)
		{
			return false;
		}

		$this->writeDebug();

		$cacheSize = $this->storage->getSize();
		$written = $this->storage->write($content."<!--".$md5."-->", $md5);
		if ($written !== false && $this->storage->shouldCountQuota())
		{
			$delta = $cacheSize !== false ? $written - $cacheSize : $written;
			if ($delta !== 0)
			{
				\CHTMLPagesCache::writeStatistic(0, 1, 0, 0, $delta);
			}
		}

		return $written;
	}

	/**
	 * Returns html content from the cache
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает контент из кеша. Возвращает записанную строку, либо <i>false</i> в случае неудачной попытки чтения.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/read.php
	* @author Bitrix
	*/
	public function read()
	{
		if ($this->storage !== null)
		{
			return $this->storage->read();
		}

		return false;
	}

	/**
	 * Deletes the cache
	 *
	 * @return bool|int
	 */
	
	/**
	* <p>Нестатический метод удаляет кеш. Возвращает количество удалённых байтов.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/delete.php
	* @author Bitrix
	*/
	public function delete()
	{
		if ($this->storage === null)
		{
			return false;
		}

		$this->writeDebug();

		$deletedSize = $this->storage->delete();
		if ($deletedSize !== false && $this->storage->shouldCountQuota())
		{
			\CHTMLPagesCache::writeStatistic(0, 0, 0, 0, -$deletedSize);
		}

		return $deletedSize;
	}

	/**
	 * Deletes all cache data
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод удаляет весь кеш из хранилища.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/deleteall.php
	* @author Bitrix
	*/
	public function deleteAll()
	{
		if ($this->storage === null)
		{
			return false;
		}

		$this->storage->deleteAll();

		if ($this->storage->shouldCountQuota())
		{
			\CHTMLPagesCache::writeStatistic(0, 0, 0, 0, false);
		}

		return true;
	}

	/**
	 * Returns the time the cache was last modified
	 * @return int|false
	 */
	
	/**
	* <p>Нестатический метод возвращает время последней модификации кеша, либо <i>false</i> в противном случае.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/getlastmodified.php
	* @author Bitrix
	*/
	public function getLastModified()
	{
		if ($this->storage !== null)
		{
			return $this->storage->getLastModified();
		}

		return false;
	}

	/**
	 * Returns true if the cache exists
	 *
	 * @return boolean
	 */
	
	/**
	* <p>Нестатический метод возвращает <i>true</i> если кеш существует.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/exists.php
	* @author Bitrix
	*/
	public function exists()
	{
		if ($this->storage !== null)
		{
			return $this->storage->exists();
		}

		return false;
	}

	/**
	 * Returns hash of the cache
	 * @return string|false
	 */
	
	/**
	* <p>Нестатический абстрактный метод возвращает <i>md5</i> кеша. Возвращает записанную строку, либо <i>false</i> в случае неудачной попытки чтения.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/getmd5.php
	* @author Bitrix
	*/
	public function getMd5()
	{
		if ($this->storage !== null)
		{
			return $this->storage->getMd5();
		}

		return false;
	}

	/**
	 * Returns true if we can cache current request
	 *
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод возвращает <i>true</i> если текущий запрос может быть закеширован.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/iscacheable.php
	* @author Bitrix
	*/
	public function isCacheable()
	{
		if ($this->storage === null)
		{
			return false;
		}

		if ($this->cacheProvider !== null && $this->cacheProvider->isCacheable() === false)
		{
			return false;
		}

		if (isset($_SESSION["SESS_SHOW_TIME_EXEC"]) && ($_SESSION["SESS_SHOW_TIME_EXEC"] == 'Y'))
		{
			return false;
		}
		elseif (isset($_SESSION["SHOW_SQL_STAT"]) && ($_SESSION["SHOW_SQL_STAT"] == 'Y'))
		{
			return false;
		}
		elseif (isset($_SESSION["SHOW_CACHE_STAT"]) && ($_SESSION["SHOW_CACHE_STAT"] == 'Y'))
		{
			return false;
		}

		$httpStatus = intval(\CHTTP::GetLastStatus());
		if ($httpStatus == 200 || $httpStatus === 0)
		{
			return $this->canCache;
		}

		return false;
	}

	/**
	 * Marks current page as non cacheable.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод отмечает текущую страницу как не кешируемую.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/marknoncacheable.php
	* @author Bitrix
	*/
	public function markNonCacheable()
	{
		$this->canCache = false;
	}

	public function setUserPrivateKey()
	{
		if ($this->cacheProvider !== null)
		{
			$this->cacheProvider->setUserPrivateKey();
		}
	}

	public function onBeforeEndBufferContent()
	{
		if ($this->cacheProvider !== null)
		{
			$this->cacheProvider->onBeforeEndBufferContent();
		}
	}

	/**
	 * Returns the instance of the StaticHtmlStorage
	 * @param string $cacheKey unique cache identifier
	 *
	 * @return StaticHtmlStorage|null
	 */
	
	/**
	* <p>Статический метод возвращает экземпляр <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlstorage/index.php">\Bitrix\Main\Data\StaticHtmlStorage</a>.</p>
	*
	*
	* @param string $cacheKey  Уникальный идентификатор кэша.
	*
	* @return \Bitrix\Main\Data\StaticHtmlStorage|null 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/getstatichtmlstorage.php
	* @author Bitrix
	*/
	public static function getStaticHtmlStorage($cacheKey)
	{
		$configuration = array();
		$htmlCacheOptions = \CHTMLPagesCache::getOptions();
		$storage = isset($htmlCacheOptions["STORAGE"]) ? $htmlCacheOptions["STORAGE"] : false;

		if (in_array($storage, array("memcached", "memcached_cluster")))
		{
			if (extension_loaded("memcache"))
			{
				return new StaticHtmlMemcachedStorage($cacheKey, $configuration, $htmlCacheOptions);
			}
			else
			{
				return null;
			}
		}
		else
		{
			return new StaticHtmlFileStorage($cacheKey, $configuration, $htmlCacheOptions);
		}
	}

	public function enableDebug()
	{
		$this->debugEnabled = true;
	}

	public function disableDebug()
	{
		$this->debugEnabled = false;
	}

	public function enableVoting()
	{
		$this->voting = true;
	}

	public function disableVoting()
	{
		$this->voting = false;
	}

	public function isVotingEnabled()
	{
		return $this->voting;
	}

	/**
	 * Writes a debug information in a log file
	 */
	private function writeDebug()
	{
		if (!$this->debugEnabled || !defined("BX_COMPOSITE_DEBUG") || BX_COMPOSITE_DEBUG !== true || !$this->storage->exists())
		{
			return;
		}

		if (!$this->storage->shouldCountQuota() || \CHTMLPagesCache::checkQuota())
		{
			//temporary check
			if ($this->storage instanceof StaticHtmlFileStorage)
			{
				$cacheFile = $this->storage->getCacheFile();
				$backupName = $cacheFile->getPath().".delete.".microtime(true);
				AddMessage2Log($backupName, "composite");
				$backupFile = new Main\IO\File($backupName);
				$backupFile->putContents($cacheFile->getContents());
				\CHTMLPagesCache::writeStatistic(0, 0, 0, 0, $cacheFile->getSize());
			}
			else
			{
				AddMessage2Log($this->cacheKey." was deleted", "composite");
			}
		}
		else
		{
			AddMessage2Log($this->cacheKey."(quota exceeded)", "composite");
		}
	}

	/**
	 * Checks component frame mode
	 * @param string $context
	 */
	
	/**
	* <p>Статический метод проверяет использует ли компонент режим  Checks component frame mode</p>
	*
	*
	* @param string $context = "" 
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlcache/applycomponentframemode.php
	* @author Bitrix
	*/
	public static function applyComponentFrameMode($context = "")
	{
		if (
			defined("USE_HTML_STATIC_CACHE")
			&& USE_HTML_STATIC_CACHE === true
			&& \Bitrix\Main\Page\Frame::getInstance()->getCurrentDynamicId() === false
		)
		{
			$staticHtmlCache = static::getInstance();
			if (!$staticHtmlCache->isVotingEnabled())
			{
				return;
			}

			$staticHtmlCache->markNonCacheable();

			if (defined("BX_COMPOSITE_DEBUG") && BX_COMPOSITE_DEBUG === true)
			{
				AddMessage2Log(
					"Reason: ".$context."\n".
					"Request URI: ".$_SERVER["REQUEST_URI"]."\n".
					"Script: ".(isset($_SERVER["REAL_FILE_PATH"]) ? $_SERVER["REAL_FILE_PATH"] : $_SERVER["SCRIPT_NAME"]),
					"Composite was rejected"
				);
			}
		}

	}
}
