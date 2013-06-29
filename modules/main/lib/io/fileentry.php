<?php
namespace Bitrix\Main\IO;

abstract class FileEntry
	extends FileSystemEntry
{
	static public function __construct($path)
	{
		parent::__construct($path);
	}

	public function getExtension()
	{
		return Path::getExtension($this->path);
	}

	public abstract function getContents();
	public abstract function putContents($data);
	public abstract function getFileSize();
	public abstract function isWritable();
	public abstract function isReadable();
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
