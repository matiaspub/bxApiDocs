<?php
namespace Bitrix\Main\Data;

use Bitrix\Main;

/**
 * Class StaticHtmlCache
 *
 * <code>
 * $staticHtmlCache = \Bitrix\Main\Data\StaticHtmlCache::getInstance();
 *
 * if ($staticHtmlCache->isExists())
 * &#123;
 * 	$staticHtmlCache->read();
 * 	die();
 * &#125;
 *
 * if ($staticHtmlCache->isCacheable())
 * &#123;
 * 	$staticHtmlCache->write($content);
 * &#125;
 * else
 * &#123;
 * 	$staticHtmlCache->delete();
 * &#125;
 *
 * if ($staticHtmlCache->isCacheable() && $staticHtmlCache->isExists())
 * &#123;
 * 	if (md5($content) !== $staticHtmlCache->getMd5())
 * 		$staticHtmlCache->write($content); //update cache
 * 	//send Json
 * &#125;
 * </code>
 *
 * @package Bitrix\Main\Data
 */
class StaticHtmlCache
{
	/**
	 * @var StaticHtmlCache
	 */
	protected static $instance = null;
	/**
	 * @var Main\IO\File
	 */
	private $cacheFile = null;
	/**
	 * @var Main\IO\File
	 */
	private $statFile = null;
	/**
	 * @var bool
	 */
	private $canCache = true;
	/**
	 * @var array
	 */
	private $options = null;

	/**
	 * Creates new cache manager instance.
	 */
	static public function __construct()
	{
	}

	/**
	 * Returns current instance of the StaticHtmlCache.
	 *
	 * @return StaticHtmlCache
	 */
	public static function getInstance()
	{
		if (!isset(static::$instance))
		{
			static::$instance = new static();
			static::$instance->init();
		}

		return static::$instance;
	}

	/**
	 * Initializes an instance.
	 *
	 * @return void
	 */
	public function init()
	{
		if ($this->getRequestMethod() === "GET")
			$PageFile = self::convertUriToPath($this->getRequestUri(), Main\Context::getCurrent()->getServer()->getHttpHost());
		else
			$PageFile = "";

		if ($PageFile)
		{
			$this->cacheFile = new Main\IO\File(Main\IO\Path::convertRelativeToAbsolute(
				Main\Application::getPersonalRoot()
				."/html_pages"
				.$PageFile
			));
			$this->statFile = new Main\IO\File(Main\IO\Path::convertRelativeToAbsolute(
				Main\Application::getPersonalRoot()
				."/html_pages/"
				.".enabled"
			));
		}
	}

	/**
	 * Returns request uri
	 *
	 * @return string
	 */
	static public function getRequestUri()
	{
		$uri = Main\Context::getCurrent()->getServer()->getRequestUri();
		return $uri;
	}

	/**
	 * Returns request method
	 *
	 * @return string
	 */
	static public function getRequestMethod()
	{
		return Main\Context::getCurrent()->getServer()->getRequestMethod();
	}

	/**
	 * Converts request uri into path safe file with .html extention.
	 * Returns empty string if fails.
	 *
	 * @param string $Uri
	 * @return string
	 */
	public static function convertUriToPath($Uri, $host = "")
	{
		$match = array();
		if (preg_match("#^(/.+?)\\.php\\?([^\\\\/]*)#", $Uri, $match) > 0)
		{
			$PageFile = $match[1]."@".$match[2];
		}
		elseif (preg_match("#^(/.+)\\.php\$#", $Uri, $match) > 0)
		{
			$PageFile = $match[1]."@";
		}
		elseif (preg_match("#^(/.+?|)/\\?([^\\\\/]*)#", $Uri, $match) > 0)
		{
			$PageFile = $match[1]."/index@".$match[2];
		}
		elseif(preg_match("#^(/.+|)/\$#", $Uri, $match) > 0)
		{
			$PageFile = $match[1]."/index@";
		}
		else
		{
			return "";
		}

		if (strlen($host) > 0)
		{
			$host = "/".$host;
			$host = preg_replace("/:(\\d+)\$/", "-\\1", $host);
		}

		$PageFile = $host.str_replace(".", "_", $PageFile).".html";

		if (!Main\IO\Path::validate($PageFile))
			return "";
		if (Main\IO\Path::normalize($PageFile) !== $PageFile)
			return "";

		return $PageFile;
	}

	/**
	 * Saves html content into file
	 * with predefined path (current request uri)
	 *
	 * @param string $content
	 * @return void
	 */
	public function write($content)
	{
		if ($this->cacheFile)
		{
			if (defined("BX_COMPOSITE_DEBUG"))
			{
				if ($this->cacheFile->isExists())
				{
					$backupName = $this->cacheFile->getPath().".write.".microtime(true);
					AddMessage2Log($backupName, "composite");
					if ($this->checkQuota())
					{
						$backupFile = new Main\IO\File($backupName);
						$backupFile->putContents($this->cacheFile->getContents());
						$this->writeStatistic(0, 0, 0, 0, $this->cacheFile->getFileSize());
					}
				}
			}
			$written = $this->cacheFile->putContents($content);
			//Update total files size
			$this->writeStatistic(
				0, //hit
				1, //miss
				0, //quota
				0, //posts
				$written //files
			);
		}
		
	}

	/**
	 * Returns html content from the file
	 * with predefined path (current request uri)
	 * Returns empty string when there is no file.
	 *
	 * @return string
	 */
	public function read()
	{
		if ($this->cacheFile && $this->cacheFile->isExists())
			return $this->cacheFile->getContents();
		else
			return "";
	}

	/**
	 * Deletes the file
	 * with predefined path (current request uri)
	 *
	 * @return void
	 */
	public function delete()
	{
		if ($this->cacheFile && $this->cacheFile->isExists())
		{
			$cacheDirectory = $this->cacheFile->getDirectory();
			$fileSize = $this->cacheFile->getFileSize();
			if (defined("BX_COMPOSITE_DEBUG"))
			{
				$backupName = $this->cacheFile->getPath().".delete.".microtime(true);
				if ($this->checkQuota())
				{
					AddMessage2Log($backupName, "composite");
					$backupFile = new Main\IO\File($backupName);
					$backupFile->putContents($this->cacheFile->getContents());
					$this->writeStatistic(0, 0, 0, 0, $fileSize);
				}
				else
				{
					AddMessage2Log($backupName."(quota exceeded)", "composite");
				}
			}
			$this->cacheFile->delete();
			//Try to cleanup directory
			$children = $cacheDirectory->getChildren();
			if (empty($children))
				$cacheDirectory->delete();
			//Update total files size
			$this->writeStatistic(0, 0, 0, 0, -$fileSize);
		}
	}

	/**
	 * Returns true if file exists
	 * with predefined path (current request uri)
	 *
	 * @return bool
	 */
	public function isExists()
	{
		if ($this->cacheFile)
		{
			return $this->cacheFile->isExists();
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns true if file exists
	 * with predefined path (current request uri)
	 *
	 * @return bool
	 */
	public function isCacheable()
	{
		if ($this->cacheFile)
		{
			if (isset($_SESSION["SESS_SHOW_TIME_EXEC"]) && ($_SESSION["SESS_SHOW_TIME_EXEC"] == 'Y'))
				return false;
			elseif (isset($_SESSION["SHOW_SQL_STAT"]) && ($_SESSION["SHOW_SQL_STAT"] == 'Y'))
				return false;
			elseif (isset($_SESSION["SHOW_CACHE_STAT"]) && ($_SESSION["SHOW_CACHE_STAT"] == 'Y'))
				return false;

			$httpStatus = \CHTTP::GetLastStatus();
			if ($httpStatus == 200 || $httpStatus === "")
				return $this->canCache;
			else
				return false;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Marks current page as non cacheable.
	 *
	 * @return void
	 */
	public function markNonCacheable()
	{
		$this->canCache = false;
	}

	/**
	 * Returns substring from the file.
	 *
	 * @param int $offset
	 * @param int $length
	 * @return string
	 */
	public function getSubstring($offset, $length)
	{
		if ($this->isExists())
		{
			return substr($this->read(), $offset, $length);
		}
		return "";
	}

	/**
	 * Returns array with cache statistics data.
	 * Returns an empty array in case of disabled html cache.
	 *
	 * @return array
	 */
	public function readStatistic()
	{
		$result = array();
		if(
			$this->statFile
			&& $this->statFile->isExists()
		)
		{
			$fileValues = explode(",", $this->statFile->getContents());
			$result = array(
				"HITS" => intval($fileValues[0]),
				"MISSES" => intval($fileValues[1]),
				"QUOTA" => intval($fileValues[2]),
				"POSTS" => intval($fileValues[3]),
				"FILE_SIZE" => doubleval($fileValues[4]),
			);
		}
		return $result;
	}

	/**
	 * Updates cache usage statistics.
	 * Each of parameters is added to appropriate existing stats.
	 *
	 * @param int $hit
	 * @param int $miss
	 * @param int $quota
	 * @param int $posts
	 * @param float $files
	 * @return void
	 */
	public function writeStatistic($hit = 0, $miss = 0, $quota = 0, $posts = 0, $files = 0.0)
	{
		$fileValues = $this->readStatistic();
		if($fileValues)
		{
			$newValues = array(
				intval($fileValues["HITS"]) + $hit,
				intval($fileValues["MISSES"]) + $miss,
				intval($fileValues["QUOTA"]) + $quota,
				intval($fileValues["POSTS"]) + $posts,
				$files === false? 0: doubleval($fileValues["FILE_SIZE"]) + doubleval($files),
			);
			$this->statFile->putContents(implode(",", $newValues));
		}
	}

	/**
	 * Reads the configuration.
	 *
	 * @return array
	 */
	public function includeConfiguration()
	{
		if (!isset($this->options))
		{
			$arHTMLPagesOptions = array();
			$configurationPath = Main\IO\Path::convertRelativeToAbsolute(
				Main\Application::getPersonalRoot()."/html_pages/.config.php"
			);
			if (file_exists($configurationPath))
				include($configurationPath);
			$this->options = $arHTMLPagesOptions;
		}
		return $this->options;
	}

	/**
	 * Checks disk quota.
	 * Returns true if quota is not exceeded.
	 *
	 * @return bool
	 */
	public function checkQuota()
	{
		$arHTMLPagesOptions = $this->includeConfiguration();
		$cache_quota = doubleval($arHTMLPagesOptions["~FILE_QUOTA"]);
		$statistic = $this->readStatistic();
		if($statistic)
			$cached_size = $statistic["FILE_SIZE"];
		else
			$cached_size = 0.0;
		return ($cached_size < $cache_quota);
	}
}
