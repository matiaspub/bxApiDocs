<?php
namespace Bitrix\Main\IO;

abstract class FileSystemEntry
{
	protected $path = null;
	protected $originalPath = null;
	protected $documentRoot = null;
	protected $pathPhysical = null;

	public function __construct($path)
	{
		if (empty($path))
			throw new InvalidPathException($path);

		$this->originalPath = $path;
		$this->path = Path::normalize($path);
		$this->documentRoot = \Bitrix\Main\Application::getDocumentRoot();

		if (empty($this->path))
			throw new InvalidPathException($path);
	}

	public function isSystem()
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

	protected function getPhysicalPath()
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
}
