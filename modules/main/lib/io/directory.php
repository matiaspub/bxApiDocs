<?php
namespace Bitrix\Main\IO;

class Directory
	extends DirectoryEntry
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести  при создании объекта какие-то действия.</p>
	*
	*
	* @param string $path  Полный путь к папке
	*
	* @param string $siteId = nul Идентификатор сайта
	*
	* @return public 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* Создав экземпляр класса можно работать с ним, так как далеко не все методы существуют в статическом исполнении. $directory=new \Bitrix\Main\IO\Directory($path, $siteId = null);Теперь с папкой, путь до которой был передан в конструктор класса можно сделать следующее:Проверить существует ли указанный путь:$directory-&gt;isExists()Удалить папку и все ее содержимое:$directory-&gt;delete()Вернуть массив объектов классов <b>Directory</b> и <b>File</b>, которые являются вложенными в текущую директорию. Без рекурсии.$directory-&gt;getChildren()Создать поддерикторию, с именем, переданым в качестве параметра. Возвращает объект созданной папки:$directory-&gt;createSubdirectory($name)Получить время создания папки. Здесь и далее методы возвращают время в формате <b>Unix timestamp</b>. Выводиться информация о директории, которая была указана при создании объекта:$directory-&gt;getCreationTime()Получить время последнего доступа к папке:$directory-&gt;getLastAccessTime()Получить время последнего изменения папки:$directory-&gt;getModificationTime()Установить на папку права на запись:$directory-&gt;markWritable()Вернуть права доступа к папке:$directory-&gt;getPermissions()
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/directory/__construct.php
	* @author Bitrix
	*/
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
	
	/**
	* <p>Статический метод создает директорию. По сути этот метод - обертка над стандартным повторяющимся кодом <code>mkdir($dir, BX_DIR_PERMISSIONS, true)</code>.</p> <p>Аналог функции <a href="http://dev.1c-bitrix.ru/api_help/main/functions/file/checkdirpath.php" >CheckDirPath</a> в старом ядре.</p>
	*
	*
	* @param string $path  Полный путь от корня сервера.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/directory/createdirectory.php
	* @author Bitrix
	*/
	public static function createDirectory($path)
	{
		$dir = new self($path);
		$dir->create();

		return $dir;
	}

	
	/**
	* <p>Статический метод рекурсивно удаляет директорию по указанному полному пути до папки (в отличие от <b>rmdir</b>, которая требует предварительной очистки директории). </p> <p>Аналог метода в старом ядре: <a href="http://dev.1c-bitrix.ru/api_help/main/functions/file/deletedirfilesex.php" >DeleteDirFilesEx</a>. <i>DeleteDirFilesEx</i> принимет путь от корня сайта, а текущий метод принимает абсолютный путь к файлу от корня сервера.</p>
	*
	*
	* @param string $path  Полный путь к папке.
	*
	* @return resource 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/directory/deletedirectory.php
	* @author Bitrix
	*/
	public static function deleteDirectory($path)
	{
		$dir = new self($path);
		$dir->delete();
	}

	
	/**
	* <p>Статический метод определяет существует ли папка. </p>
	*
	*
	* @param string $path  Полный путь к папке
	*
	* @return resource 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/io/directory/isdirectoryexists.php
	* @author Bitrix
	*/
	public static function isDirectoryExists($path)
	{
		$f = new self($path);
		return $f->isExists();
	}
}
