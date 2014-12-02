<?php
namespace Bitrix\Main\Data;

use Bitrix\Main;

/**
 * Class StaticHtmlMemcachedStorage
 * Storages html cache in a memcached
 * @package Bitrix\Main\Data
 */
final class StaticHtmlMemcachedStorage extends StaticHtmlStorage
{
	private $memcached = null;
	private $props = null;
	private $compression = true;

	public function __construct($cacheKey, array $configuration, array $htmlCacheOptions)
	{
		parent::__construct($cacheKey, $configuration, $htmlCacheOptions);
		$this->memcached = \StaticHtmlMemcachedResponse::getConnection($configuration, $htmlCacheOptions);
		$this->compression = !isset($htmlCacheOptions["MEMCACHE_COMPRESSION"]) || $htmlCacheOptions["MEMCACHE_COMPRESSION"] !== "N";
	}

	public function write($content, $md5)
	{
		if (!$this->memcached || !$this->memcached->set($this->cacheKey, $content, $this->compression ? MEMCACHE_COMPRESSED : 0, 0))
		{
			return false;
		}

		$this->props = new \stdClass();
		$this->props->mtime = time();
		$this->props->etag = md5($this->cacheKey.$this->props->size.$this->props->mtime);
		$this->props->type = "text/html; charset=".LANG_CHARSET;
		$this->props->md5 = $md5;
		if (function_exists("mb_strlen"))
		{
			$this->props->size = mb_strlen(gzcompress($content), "latin1");
			$this->props->size += mb_strlen(serialize($this->props), "latin1");
		}
		else
		{
			$this->props->size = strlen(gzcompress($content));
			$this->props->size += strlen(serialize($this->props));
		}

		$this->memcached->set("~".$this->cacheKey, $this->props, 0, 0);

		return $this->props->size;
	}

	public function read()
	{
		if ($this->memcached !== null)
		{
			return $this->memcached->get($this->cacheKey);
		}

		return false;
	}

	public function exists()
	{
		return $this->getProps() !== false;
	}

	public function delete()
	{
		if ($this->memcached && $this->memcached->delete($this->cacheKey))
		{
			$size = $this->getProp("size");
			$this->deleteProps();
    		return $size;
		}

		return false;
	}

	public function deleteAll()
	{
		if ($this->memcached)
		{
			return $this->memcached->flush();
		}

		return false;
	}

	/**
	 * Returns the md5 hash of the cache
	 * @return string|false
	 */
	public function getMd5()
	{
		return $this->getProp("md5");
	}

	/**
	 * Should we count a quota limit
	 * @return bool
	 */
	static public function shouldCountQuota()
	{
		return false;
	}

	/**
	 * Returns the size of the cache
	 *
	 * @return int|false
	 */
	protected function getSize()
	{
		return $this->getProp("size");
	}

	/**
	 * Returns an array of the cache properties
	 *
	 * @return \stdClass|false
	 */
	protected function getProps()
	{
		if ($this->props === null)
		{
			if ($this->memcached !== null)
			{
				$props = $this->memcached->get("~".$this->cacheKey);
				$this->props = is_object($props) ? $props : false;
			}
			else
			{
				$this->props = false;
			}
		}

		return $this->props;
	}

	/**
	 * Deletes the cache properties
	 *
	 * @return bool
	 */
	protected function deleteProps()
	{
		if ($this->memcached)
		{
			$this->props = false;
			return $this->memcached->delete("~".$this->cacheKey);
		}

		return false;
	}

	/**
	 * Returns the property value
	 * @param string $property the property name
	 *
	 * @return string|false
	 */
	protected function getProp($property)
	{
		$props = $this->getProps();
		if ($props !== false && isset($props->{$property}))
		{
			return $props->{$property};
		}

		return false;
	}
}
