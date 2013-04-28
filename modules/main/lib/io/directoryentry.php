<?php
namespace Bitrix\Main\IO;

abstract class DirectoryEntry
	extends FileSystemEntry
{
	static public function __construct($path)
	{
		parent::__construct($path);
	}

	static public function create()
	{
		if ($this->isExists())
			return;

		$arMissingDirs = array($this->getName());
		$dir = $this->getDirectory();
		while (!$dir->isExists())
		{
			$arMissingDirs[] = $dir->getName();
			$dir = $dir->getDirectory();
		}

		$arMissingDirs = array_reverse($arMissingDirs);
		foreach ($arMissingDirs as $dirName)
			$dir = $dir->createSubdirectory($dirName);
	}

	/**
	 * @return FileSystemEntry[]
	 */
	static abstract public function getChildren();

	/**
	 * @param string $path
	 * @return DirectoryEntry
	 */
	static abstract public function createSubdirectory($name);

	static public function isDirectory()
	{
		return true;
	}

	static public function isFile()
	{
		return false;
	}

	static public function isLink()
	{
		return false;
	}
}
