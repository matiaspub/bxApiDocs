<?php
namespace Bitrix\Main\IO;

abstract class FileEntry
	extends FileSystemEntry
{
	static public function __construct($path)
	{
		parent::__construct($path);
	}

	static public function getExtension()
	{
		return Path::getExtension($this->path);
	}

	public abstract function getContents();
	static public abstract function putContents($data);
	static public abstract function getFileSize();
	public abstract function isWritable();
	static public abstract function isReadable();
	public abstract function readFile();

	static public function isDirectory()
	{
		return false;
	}

	static public function isFile()
	{
		return true;
	}

	static public function isLink()
	{
		return false;
	}
}
