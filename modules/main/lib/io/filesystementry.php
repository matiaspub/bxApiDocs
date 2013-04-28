<?php
namespace Bitrix\Main\IO;

abstract class FileSystemEntry
{
	protected $path = null;
	protected $originalPath = null;
	protected $documentRoot = null;
	protected $pathPhysical = null;

	static public function __construct($path)
	{
		if (empty($path))
			throw new InvalidPathException($path);

		$this->originalPath = $path;
		$this->path = Path::normalize($path);
		$this->documentRoot = \Bitrix\Main\Application::getDocumentRoot();

		if (empty($this->path))
			throw new InvalidPathException($path);
	}

	static public function isSystem()
	{
		$isSystem = false;

		if (substr($this->path, 0, strlen($this->documentRoot)) === $this->documentRoot)
		{
			$relativePath = substr($this->path, strlen($this->documentRoot));
			$relativePath = ltrim($relativePath, "/");
			if (($pos = strpos($relativePath, "/")) !== false)
				$s = substr($relativePath, 0, $pos);
			else
				$s = $relativePath;
			$s = strtolower(rtrim($s, "."));

			$uploadDirName = \COption::getOptionString("main", "upload_dir", "upload");
			if (in_array($s, array("bitrix", $uploadDirName)))
				$isSystem = true;
		}

		return $isSystem;
	}

	static public function getName()
	{
		return Path::getName($this->path);
	}

	static public function getDirectoryName()
	{
		return Path::getDirectory($this->path);
	}

	static public function getPath()
	{
		return $this->path;
	}

	static public function getDirectory()
	{
		return new Directory($this->getDirectoryName());
	}

	static abstract public function getCreationTime();
	static abstract public function getLastAccessTime();
	static abstract public function getModificationTime();

	abstract public function isExists();

	static public abstract function isDirectory();
	public abstract function isFile();
	static public abstract function isLink();

	public abstract function markWritable();
	static public abstract function getPermissions();
	static public abstract function delete();

	protected function getPhysicalPath()
	{
		if (is_null($this->pathPhysical))
			$this->pathPhysical = Path::convertLogicalToPhysical($this->path);

		return $this->pathPhysical;
	}

	static public function rename($newPath)
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
}
