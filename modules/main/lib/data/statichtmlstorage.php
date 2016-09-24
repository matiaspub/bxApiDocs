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
	
	/**
	* <p>Нестатический абстрактный метод записывает контент в кеш. Возвращает записанную строку, либо <i>false</i> в случае неудачной попытки записи.</p>
	*
	*
	* @param string $content  Строка, которая должна быть записана
	*
	* @param string $md5  хэш контента
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlstorage/write.php
	* @author Bitrix
	*/
	abstract public function write($content, $md5);

	/**
	 * Returns the cache contents
	 * @return string|false
	 */
	
	/**
	* <p>Нестатический абстрактный метод возвращает контент из кеша. Возвращает записанную строку, либо <i>false</i> в случае неудачной попытки чтения.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlstorage/read.php
	* @author Bitrix
	*/
	abstract public function read();

	/**
	 * Returns true if the cache exists
	 * @return bool
	 */
	
	/**
	* <p>Нестатический абстрактный метод возвращает <i>true</i> если кеш существует.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlstorage/exists.php
	* @author Bitrix
	*/
	abstract public function exists();

	/**
	 * Deletes the cache
	 * Returns the number of deleted bytes
	 * @return int|false
	 */
	
	/**
	* <p>Нестатический абстрактный метод удаляет кеш. Возвращает количество удалённых байтов.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlstorage/delete.php
	* @author Bitrix
	*/
	abstract public function delete();

	/**
	 * Deletes all cache data in the storage
	 * @return bool
	 */
	
	/**
	* <p>Нестатический абстрактный метод удаляет весь кеш из хранилища.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlstorage/deleteall.php
	* @author Bitrix
	*/
	abstract public function deleteAll();

	/**
	 * Returns md5 hash of the cache
	 * @return string|false
	 */
	
	/**
	* <p>Нестатический абстрактный метод возвращает md5 кеша. Возвращает записанную строку, либо <i>false</i> в случае неудачной попытки чтения.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlstorage/getmd5.php
	* @author Bitrix
	*/
	abstract public function getMd5();

	/**
	 * Should we count a quota limit
	 * @return bool
	 */
	
	/**
	* <p>Нестатический абстрактный метод устанавливает должен ли считаться лимит квот.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlstorage/shouldcountquota.php
	* @author Bitrix
	*/
	abstract public function shouldCountQuota();

	/**
	 * Returns the time the cache was last modified
	 * @return int|false
	 */
	
	/**
	* <p>Нестатический метод возвращает время последней модификации кеша.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlstorage/getlastmodified.php
	* @author Bitrix
	*/
	abstract public function getLastModified();

	/**
	 * Returns cache size
	 * @return int|false
	 */
	
	/**
	* <p>Нестатический метод возвращает размер кеша.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlstorage/getsize.php
	* @author Bitrix
	*/
	abstract public function getSize();
}