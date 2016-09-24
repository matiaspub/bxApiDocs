<?php
namespace Bitrix\Main\IO;

abstract class FileEntry
	extends FileSystemEntry
{
	static public function __construct($path, $siteId = null)
	{
		parent::__construct($path, $siteId);
	}

	public function getExtension()
	{
		return Path::getExtension($this->path);
	}

	public abstract function getContents();
	public abstract function putContents($data);
	public abstract function getSize();
	public abstract function isWritable();
	public abstract function isReadable();
	public abstract function readFile();

	/**
	 * @deprecated Use getSize() instead
	 * @return mixed
	 */
	public function getFileSize()
	{
		return $this->getSize();
	}

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
