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

	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести  при создании объекта какие-то действия.</p>
	*
	*
	* @param string $path  Полный путь к файлу
	*
	* @param string $siteId = null Идентификатор сайта
	*
	* @return resource 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* Создаем экземпляр класса:$file=new \Bitrix\Main\IO\File($path, $siteId = null);Теперь с файлом, путь до которого был передан в конструктор класса можно сделать следующее:Открыть файл. Параметром указывается тип доступа, который запрашивается у потока. Этот параметр аналогичен функции <b>php fopen</b>. $file-&gt;open($mode)Проверить его существование.$file-&gt;isExists()Записать данные в файл аналогично статическому методу  <a href="/api_d7/bitrix/main/io/file/putfilecontents.php">putFileContents</a>.$file-&gt;putContents($data, $flags=self::REWRITE)Получить размер файла.$file-&gt;getSize()Понять, доступен ли файл для записи.$file-&gt;isWritable()Понять, доступен ли файл для чтения.$file-&gt;isReadable()Прочесть файл и записать его в буфер вывода. Получить количество прочитанных из файла байт.$file-&gt;readFile()Получить дату создания файла.$file-&gt;getCreationTime()Получить время последнего доступа к файлу.$file-&gt;getLastAccessTime()Получить время последнего изменения файла.$file-&gt;getModificationTime()Установить на файл права на запись$file-&gt;markWritable()Узнать права доступа к файлу$file-&gt;getPermissions()Удалить файл.$file-&gt;delete()Получить тип контента файла.$file-&gt;getContentType()
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/file/__construct.php
	* @author Bitrix
	*/
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
	
	/**
	* <p>Нестатический метод открывает файл и возвращает указатель файла.</p>
	*
	*
	* @param string $mode  
	*
	* @return resource 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/file/open.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод закрывает файл.</p> <p>Без параметров</p>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/file/close.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает размер файла.</p> <p>Без параметров</p>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/file/getsize.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод ищет указатель файла от начала (только SEEK_SET).</p>
	*
	*
	* @param mixed $integer  
	*
	* @param float $position  
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/file/seek.php
	* @author Bitrix
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

	
	/**
	* <p>Статический метод определяет существует ли файл. </p>
	*
	*
	* @param string $path  Полный путь к файлу
	*
	* @return resource 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/file/isfileexists.php
	* @author Bitrix
	*/
	public static function isFileExists($path)
	{
		$f = new self($path);
		return $f->isExists();
	}

	
	/**
	* <p>Статический метод возвращает содержимое файла в виде одной строки.</p>
	*
	*
	* @param string $path  Полный путь к файлу
	*
	* @return resource 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/file/getfilecontents.php
	* @author Bitrix
	*/
	public static function getFileContents($path)
	{
		$f = new self($path);
		return $f->getContents();
	}

	
	/**
	* <p>Статический метод записывает данные в файл. Если флаг указан, то данные будут дописаны в конец. В противном случае файл будет перезаписан полностью. Если файла нет, то он будет создан. При создании не только создаёт сам файл, но и все директории на пути к нему.</p> <p>Аналог метода в старом ядре: <a href="http://dev.1c-bitrix.ru/api_help/main/functions/file/rewritefile.php" >RewriteFile</a>.</p>
	*
	*
	* @param string $path  Полный путь к файлу
	*
	* @param string $data  Данные для записи
	*
	* @param $dat $flags = self::REWRITE флаг
	*
	* @return resource 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/file/putfilecontents.php
	* @author Bitrix
	*/
	public static function putFileContents($path, $data, $flags=self::REWRITE)
	{
		$f = new self($path);
		return $f->putContents($data, $flags);
	}

	
	/**
	* <p>Статический метод удаляет файл. </p>
	*
	*
	* @param string $path  Полный путь к файлу.
	*
	* @return resource 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/file/deletefile.php
	* @author Bitrix
	*/
	public static function deleteFile($path)
	{
		$f = new self($path);
		return $f->delete();
	}
}
