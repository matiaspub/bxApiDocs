<?php
namespace Bitrix\Main\Data;

use Bitrix\Main;
use Bitrix\Main\IO\File;

final class StaticHtmlFileStorage extends StaticHtmlStorage
{
	private $cacheFile = null;

	public function __construct($cacheKey, array $configuration, array $htmlCacheOptions)
	{
		parent::__construct($cacheKey, $configuration, $htmlCacheOptions);

		$this->cacheFile = new Main\IO\File(Main\IO\Path::convertRelativeToAbsolute(
			Main\Application::getPersonalRoot()
			."/html_pages"
			.$this->cacheKey
		));
	}

	public function write($content, $md5)
	{
		$written = false;
		
		if ($this->cacheFile)
		{
			$tempFile = new File($this->cacheFile->getPhysicalPath().".tmp");

			try
			{
				$written = $tempFile->putContents($content);
				$this->cacheFile->delete();
				if (!$tempFile->rename($this->cacheFile->getPhysicalPath()))
				{
					$written = false;
				}
			}
			catch (\Exception $exception)
			{
				$written = false;
				$this->cacheFile->delete();
				$tempFile->delete();
			}
		}

		return $written;
	}

	public function read()
	{
		if ($this->exists())
		{
			try
			{
				return $this->cacheFile->getContents();
			}
			catch (\Exception $exception)
			{

			}
		}

		return false;
	}

	public function exists()
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

	public function delete()
	{
		$fileSize = false;
		if ($this->cacheFile && $this->cacheFile->isExists())
		{
			try
			{
				$cacheDirectory = $this->cacheFile->getDirectory();
				$fileSize = $this->cacheFile->getSize();
				$this->cacheFile->delete();

				//Try to cleanup directory
				$children = $cacheDirectory->getChildren();
				if (empty($children))
				{
					$cacheDirectory->delete();
				}
			}
			catch (\Exception $exception)
			{

			}
		}

		return $fileSize;
	}

	static public function deleteAll()
	{
		return (bool)self::deleteRecursive("/");
	}

	public function getMd5()
	{
		if ($this->exists())
		{
			$content = $this->read();
			return $content !== false ? substr($content, -35, 32) : false;
		}

		return false;
	}

	/**
	 * Should we count a quota limit
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод устанавливает должен ли считаться лимит квот.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlfilestorage/shouldcountquota.php
	* @author Bitrix
	*/
	static public function shouldCountQuota()
	{
		return true;
	}

	public function getLastModified()
	{
		if ($this->exists())
		{
			try
			{
				return $this->cacheFile->getModificationTime();
			}
			catch (\Exception $exception)
			{

			}
		}

		return false;
	}

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlfilestorage/getsize.php
	* @author Bitrix
	*/
	public function getSize()
	{
		if ($this->cacheFile && $this->cacheFile->isExists())
		{
			try
			{
				return $this->cacheFile->getSize();
			}
			catch (\Exception $exception)
			{

			}
		}

		return false;
	}

	public function getCacheFile()
	{
		return $this->cacheFile;
	}

	/**
	 * Deletes all above html_pages
	 * @param string $relativePath [optional]
	 * @param int $validTime [optional] unix timestamp
	 * @return float
	 */
	
	/**
	* <p>Статический метод удаляет все html страницы созданные ранее указанной в параметрах даты.</p>
	*
	*
	* @param string $relativePath = "" [optional]
	*
	* @param integer $validTime  [optional] Метка времени в unix формате
	*
	* @return float 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/statichtmlfilestorage/deleterecursive.php
	* @author Bitrix
	*/
	public static function deleteRecursive($relativePath = "", $validTime = 0)
	{
		$bytes = 0.0;
		if (strpos($relativePath, "..") !== false)
		{
			return $bytes;
		}

		$relativePath = rtrim($relativePath, "/");
		$baseDir = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages";
		$absPath = $baseDir.$relativePath;

		if (is_file($absPath))
		{
			if (
				($validTime && filemtime($absPath) > $validTime) ||
				in_array($relativePath, array("/.enabled", "/.config.php", "/.htaccess", "/404.php")))
			{
				return $bytes;
			}

			$bytes = filesize($absPath);
			@unlink($absPath);
			return doubleval($bytes);
		}
		elseif (is_dir($absPath) && ($handle = opendir($absPath)) !== false)
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file === "." || $file === "..")
				{
					continue;
				}

				$bytes += self::deleteRecursive($relativePath."/".$file, $validTime);
			}
			closedir($handle);
			@rmdir($absPath);
		}

		return doubleval($bytes);
	}
}
