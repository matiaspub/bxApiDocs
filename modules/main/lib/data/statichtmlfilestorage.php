<?php
namespace Bitrix\Main\Data;

use Bitrix\Main;

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
		$success = false;
		if ($this->cacheFile)
		{
			try
			{
				$success = $this->cacheFile->putContents($content);
			}
			catch(\Exception $exception)
			{
				$this->cacheFile->delete();
			}
		}

		return $success;
	}

	public function read()
	{
		if ($this->exists())
		{
			return $this->cacheFile->getContents();
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
		if ($this->cacheFile && $this->cacheFile->isExists())
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

			return $fileSize;
		}

		return false;
	}

	static public function deleteAll()
	{
		return (bool)self::deleteRecursive("/");
	}

	public function getMd5()
	{
		if ($this->exists())
		{
			return substr($this->read(), -35, 32);
		}

		return false;
	}

	/**
	 * Should we count a quota limit
	 * @return bool
	 */
	static public function shouldCountQuota()
	{
		return true;
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
