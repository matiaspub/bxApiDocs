<?php
namespace Bitrix\Main\IO;

class Directory
	extends DirectoryEntry
{
	static public function __construct($path, $siteId = null)
	{
		parent::__construct($path, $siteId);
	}

	public function isExists()
	{
		$p = $this->getPhysicalPath();
		return file_exists($p) && is_dir($p);
	}

	public function delete()
	{
		return self::deleteInternal($this->getPhysicalPath());
	}

	private static function deleteInternal($path)
	{
		if (is_file($path) || is_link($path))
		{
			if (!@unlink($path))
				throw new FileDeleteException($path);
		}
		elseif (is_dir($path))
		{
			if ($handle = opendir($path))
			{
				while (($file = readdir($handle)) !== false)
				{
					if ($file == "." || $file == "..")
						continue;

					self::deleteInternal(Path::combine($path, $file));
				}
				closedir($handle);
			}
			if (!@rmdir($path))
				throw new FileDeleteException($path);
		}

		return true;
	}

	/**
	 * @return array|FileSystemEntry[]
	 * @throws FileNotFoundException
	 */
	public function getChildren()
	{
		if (!$this->isExists())
			throw new FileNotFoundException($this->originalPath);

		$arResult = array();

		if ($handle = opendir($this->getPhysicalPath()))
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file == "." || $file == "..")
					continue;

				$pathLogical = Path::combine($this->path, Path::convertPhysicalToLogical($file));
				$pathPhysical = Path::combine($this->getPhysicalPath(), $file);
				if (is_dir($pathPhysical))
					$arResult[] = new Directory($pathLogical);
				else
					$arResult[] = new File($pathLogical);
			}
			closedir($handle);
		}

		return $arResult;
	}

	/**
	 * @param $name
	 * @return Directory|DirectoryEntry
	 */
	public function createSubdirectory($name)
	{
		$dir = new Directory(Path::combine($this->path, $name));
		if (!$dir->isExists())
			mkdir($dir->getPhysicalPath(), BX_DIR_PERMISSIONS, true);
		return $dir;
	}

	public function getCreationTime()
	{
		if (!$this->isExists())
			throw new FileNotFoundException($this->originalPath);

		return filectime($this->getPhysicalPath());
	}

	public function getLastAccessTime()
	{
		if (!$this->isExists())
			throw new FileNotFoundException($this->originalPath);

		return fileatime($this->getPhysicalPath());
	}

	public function getModificationTime()
	{
		if (!$this->isExists())
			throw new FileNotFoundException($this->originalPath);

		return filemtime($this->getPhysicalPath());
	}

	public function markWritable()
	{
		if (!$this->isExists())
			throw new FileNotFoundException($this->originalPath);

		@chmod($this->getPhysicalPath(), BX_DIR_PERMISSIONS);
	}

	public function getPermissions()
	{
		return fileperms($this->getPhysicalPath());
	}

	/**
	 * @param $path
	 *
	 * @return Directory
	 */
	public static function createDirectory($path)
	{
		$dir = new self($path);
		$dir->create();

		return $dir;
	}

	public static function deleteDirectory($path)
	{
		$dir = new self($path);
		$dir->delete();
	}

	public static function isDirectoryExists($path)
	{
		$f = new self($path);
		return $f->isExists();
	}
}
