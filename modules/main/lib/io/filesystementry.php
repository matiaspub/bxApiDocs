<?php
namespace Bitrix\Main\IO;

use Bitrix\Main;

abstract class FileSystemEntry
{
	protected $path;
	protected $originalPath;
	protected $pathPhysical;
	protected $siteId;

	public function __construct($path, $siteId = null)
	{
		if ($path == '')
			throw new InvalidPathException($path);

		$this->originalPath = $path;
		$this->path = Path::normalize($path);
		$this->siteId = $siteId;

		if ($this->path == '')
			throw new InvalidPathException($path);
	}

	public function isSystem()
	{
		if (preg_match("#/\\.#", $this->path))
			return true;

		$documentRoot = static::getDocumentRoot($this->siteId);

		if (substr($this->path, 0, strlen($documentRoot)) === $documentRoot)
		{
			$relativePath = substr($this->path, strlen($documentRoot));
			$relativePath = ltrim($relativePath, "/");
			if (($pos = strpos($relativePath, "/")) !== false)
				$s = substr($relativePath, 0, $pos);
			else
				$s = $relativePath;
			$s = strtolower(rtrim($s, "."));

			$ar = array(
				"bitrix" => 1,
				Main\Config\Option::get("main", "upload_dir", "upload") => 1,
				"urlrewrite.php" => 1,
			);
			if (isset($ar[$s]))
				return true;
		}

		return false;
	}

	public function getName()
	{
		return Path::getName($this->path);
	}

	public function getDirectoryName()
	{
		return Path::getDirectory($this->path);
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getDirectory()
	{
		return new Directory($this->getDirectoryName());
	}

	abstract public function getCreationTime();
	abstract public function getLastAccessTime();
	abstract public function getModificationTime();

	abstract public function isExists();

	public abstract function isDirectory();
	public abstract function isFile();
	public abstract function isLink();

	public abstract function markWritable();
	public abstract function getPermissions();
	public abstract function delete();

	public function getPhysicalPath()
	{
		if (is_null($this->pathPhysical))
			$this->pathPhysical = Path::convertLogicalToPhysical($this->path);

		return $this->pathPhysical;
	}

	public function rename($newPath)
	{
		$newPathNormalized = Path::normalize($newPath);

		$success = true;
		if ($this->isExists())
			$success = rename($this->getPhysicalPath(), Path::convertLogicalToPhysical($newPathNormalized));

		if ($success)
		{
			$this->originalPath = $newPath;
			$this->path = $newPathNormalized;
			$this->pathPhysical = null;
		}

		return $success;
	}

	protected static function getDocumentRoot($siteId)
	{
		if($siteId === null)
		{
			$documentRoot = Main\Application::getDocumentRoot();
		}
		else
		{
			$documentRoot = Main\SiteTable::getDocumentRoot($siteId);
		}
		return $documentRoot;
	}
}
