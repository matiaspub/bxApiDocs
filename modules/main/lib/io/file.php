<?php
namespace Bitrix\Main\IO;

class File
	extends FileEntry
	implements IFileStream
{
	const REWRITE = 0;
	const APPEND = 1;

	/** @var resource */
	protected $filePointer;

	static public function __construct($path, $siteId = null)
	{
		parent::__construct($path, $siteId);
	}

	/**
	 * Opens the file and returns the file pointer.
	 *
	 * @param string $mode
	 * @return resource
	 * @throws FileOpenException
	 */
	public function open($mode)
	{
		$this->filePointer = fopen($this->getPhysicalPath(), $mode."b");
		if (!$this->filePointer)
		{
			throw new FileOpenException($this->originalPath);
		}
		return $this->filePointer;
	}

	/**
	 * Closes the file.
	 *
	 * @throws FileNotOpenedException
	 */
	public function close()
	{
		if(!$this->filePointer)
		{
			throw new FileNotOpenedException($this->originalPath);
		}
		fclose($this->filePointer);
		$this->filePointer = null;
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

	public function putContents($data, $flags = self::REWRITE)
	{
		$dir = $this->getDirectory();
		if (!$dir->isExists())
			$dir->create();

		if ($this->isExists() && !$this->isWritable())
			$this->markWritable();

		return $flags & self::APPEND
			? file_put_contents($this->getPhysicalPath(), $data, FILE_APPEND)
			: file_put_contents($this->getPhysicalPath(), $data);
	}

	/**
	 * Returns the file size.
	 *
	 * @return float|int
	 * @throws FileNotFoundException
	 * @throws FileOpenException
	 */
	public function getSize()
	{
		if (!$this->isExists())
		{
			throw new FileNotFoundException($this->originalPath);
		}

		static $supportLarge32 = null;
		if($supportLarge32 === null)
		{
			$supportLarge32 = (\Bitrix\Main\Config\Configuration::getValue("large_files_32bit_support") === true);
		}

		$size = 0;
		if(PHP_INT_SIZE < 8 && $supportLarge32)
		{
			// 32bit
			$this->open(FileStreamOpenMode::READ);

			if(fseek($this->filePointer, 0, SEEK_END) === 0)
			{
				$size = 0.0;
				$step = 0x7FFFFFFF;
				while($step > 0)
				{
					if (fseek($this->filePointer, -$step, SEEK_CUR) === 0)
					{
						$size += floatval($step);
					}
					else
					{
						$step >>= 1;
					}
				}
			}

			$this->close();
		}
		else
		{
			// 64bit
			$size = filesize($this->getPhysicalPath());
		}

	    return $size;
	}

	/**
	 * Seeks on the file pointer from the beginning (SEEK_SET only).
	 *
	 * @param int|float $position
	 * @return int
	 * @throws FileNotOpenedException
	 */
	public function seek($position)
	{
		if(!$this->filePointer)
		{
			throw new FileNotOpenedException($this->originalPath);
		}

		if($position <= PHP_INT_MAX)
		{
			return fseek($this->filePointer, $position, SEEK_SET);
		}
		else
		{
			$res = fseek($this->filePointer, 0, SEEK_SET);
			if($res === 0)
			{
				do
				{
					$offset = ($position < PHP_INT_MAX? $position : PHP_INT_MAX);
					$res = fseek($this->filePointer, $offset, SEEK_CUR);
					if($res !== 0)
					{
						break;
					}
					$position -= PHP_INT_MAX;
				}
				while($position > 0);
			}
			return $res;
		}
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

	public function getContentType()
	{
		if (!$this->isExists())
			throw new FileNotFoundException($this->originalPath);

		$finfo = \finfo_open(FILEINFO_MIME_TYPE);
		$contentType = \finfo_file($finfo, $this->getPath());
		\finfo_close($finfo);

		return $contentType;
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
		return $f->delete();
	}
}
