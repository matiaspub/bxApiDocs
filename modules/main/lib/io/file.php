<?php
namespace Bitrix\Main\IO;

class File
	extends FileEntry
	implements IFileStream
{
	const REWRITE = 0;
	const APPEND = 1;

	static public function __construct($path, $siteId = null)
	{
		parent::__construct($path, $siteId);
	}

	public function open($mode)
	{
		$fd = fopen($this->getPhysicalPath(), $mode."b");
		if (!$fd)
			throw new FileOpenException($this->originalPath);

		return $fd;
	}

	public function isExists()
	{
		$p = $this->getPhysicalPath();
		return file_exists($p) && (is_file($p) || is_link($p));
	}

	public function getContents()
	{
		if (!$this->isExists())
			throw new FileNotFoundException($this->originalPath);

		return file_get_contents($this->getPhysicalPath());
	}

	public function putContents($data, $flags=self::REWRITE)
	{
		$dir = $this->getDirectory();
		if (!$dir->isExists())
			$dir->create();

		if ($this->isExists() && !$this->isWritable())
			$this->markWritable();

		return $flags&self::APPEND
			? file_put_contents($this->getPhysicalPath(), $data, FILE_APPEND)
			: file_put_contents($this->getPhysicalPath(), $data);
	}

	public function getFileSize()
	{
		if (!$this->isExists())
			throw new FileNotFoundException($this->originalPath);

		return intval(filesize($this->getPhysicalPath()));
	}

	public function isWritable()
	{
		if (!$this->isExists())
			throw new FileNotFoundException($this->originalPath);

		return is_writable($this->getPhysicalPath());
	}

	public function isReadable()
	{
		if (!$this->isExists())
			throw new FileNotFoundException($this->originalPath);

		return is_readable($this->getPhysicalPath());
	}

	public function readFile()
	{
		if (!$this->isExists())
			throw new FileNotFoundException($this->originalPath);

		return readfile($this->getPhysicalPath());
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

		@chmod($this->getPhysicalPath(), BX_FILE_PERMISSIONS);
	}

	public function getPermissions()
	{
		if (!$this->isExists())
			throw new FileNotFoundException($this->originalPath);

		return fileperms($this->getPhysicalPath());
	}

	public function delete()
	{
		if ($this->isExists())
			return unlink($this->getPhysicalPath());

		return true;
	}

	public static function isFileExists($path)
	{
		$f = new self($path);
		return $f->isExists();
	}

	public static function getFileContents($path)
	{
		$f = new self($path);
		return $f->getContents();
	}

	public static function putFileContents($path, $data, $flags=self::REWRITE)
	{
		$f = new self($path);
		return $f->putContents($data, $flags);
	}

	public static function deleteFile($path)
	{
		$f = new self($path);
		$f->delete();
	}
}
