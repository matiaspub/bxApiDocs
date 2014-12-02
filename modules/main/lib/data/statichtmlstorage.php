<?php
namespace Bitrix\Main\Data;

use Bitrix\Main;

/**
 * Class StaticHtmlStorage
 * Represents the interface for a cache storage
 * @package Bitrix\Main\Data
 */
abstract class StaticHtmlStorage
{
	protected $cacheKey = null;
	protected $configuration = array();
	protected $htmlCacheOptions = array();

	/**
	 * @param string $cacheKey unique cache identifier
	 * @param array $configuration storage configuration
	 * @param array $htmlCacheOptions html cache options
	 */
	public function __construct($cacheKey, array $configuration, array $htmlCacheOptions)
	{
		$this->cacheKey = $cacheKey;
		$this->configuration = $configuration;
		$this->htmlCacheOptions = $htmlCacheOptions;
	}

	/**
	 * Writes the content to the storage
	 * @param string $content the string that is to be written
	 * @param string $md5 the content hash
	 *
	 * @return bool
	 */
	abstract public function write($content, $md5);

	/**
	 * Returns the cache contents
	 * @return string|false
	 */
	abstract public function read();

	/**
	 * Returns true if the cache exists
	 * @return bool
	 */
	abstract public function exists();

	/**
	 * Deletes the cache
	 * Returns the number of deleted bytes
	 * @return int|false
	 */
	abstract public function delete();

	/**
	 * Deletes all cache data in the storage
	 * @return bool
	 */
	abstract public function deleteAll();

	/**
	 * Returns md5 hash of the cache
	 * @return string|false
	 */
	abstract public function getMd5();

	/**
	 * Should we count a quota limit
	 * @return bool
	 */
	abstract public function shouldCountQuota();
}