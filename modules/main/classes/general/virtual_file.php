<?
abstract class CBXVirtualFileBase
	implements IBXGetErrors
{
	protected $path = null;

	public function __construct($path)
	{
		$io = CBXVirtualIo::GetInstance();
		$this->path = $io->CombinePath($path);
	}

	public function GetName()
	{
		$io = CBXVirtualIo::GetInstance();
		return $io->ExtractNameFromPath($this->path);
	}

	public function GetPath()
	{
		$io = CBXVirtualIo::GetInstance();
		return $io->ExtractPathFromPath($this->path);
	}

	public function GetPathWithName()
	{
		return $this->path;
	}

	public abstract function IsDirectory();
	public abstract function IsExists();
	public abstract function MarkWritable();
	public abstract function GetPermissions();
	public abstract function GetModificationTime();
	public abstract function GetLastAccessTime();
}


/**
 * <p><b>Примечание</b>:</p>   <p>Класс считается устаревшим. Рекомендуется использовать класс нового ядра D7, расположенный <code>/bitrix/modules/main/lib/io/file.php</code>.</p>
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualfile/index.php
 * @author Bitrix
 */
abstract class CBXVirtualFile
	extends CBXVirtualFileBase
{
	static public function IsDirectory()
	{
		return false;
	}

	public function GetType()
	{
		return GetFileType($this->path);
	}

	public function GetExtension()
	{
		return GetFileExtension($this->path);
	}

	public abstract function Open($mode);
	public abstract function GetContents();
	public abstract function PutContents($data);
	public abstract function GetFileSize();
	public abstract function IsWritable();
	public abstract function IsReadable();
	public abstract function ReadFile();
}


/**
 * <b>CBXVirtualDirectory</b> - класс папки.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualdirectory/index.php
 * @author Bitrix
 */
abstract class CBXVirtualDirectory
	extends CBXVirtualFileBase
{
	static public function IsDirectory()
	{
		return true;
	}

	/**
	 * @return CBXVirtualDirectoryFileSystem[] | CBXVirtualFileFileSystem[]
	 */
	
	/**
	* <p>Метод возвращает содержимое папки в виде массива. Элементами массива являются экземпляры классов <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualdirectory/index.php">CBXVirtualDirectory</a> и <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualfile/index.php">CBXVirtualFile</a>. Нестатический метод.</p>   <a name="examples"></a>
	*
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $dir = $io-&gt;GetDirectory($io-&gt;RelativeToAbsolutePath("/папка1/папка2"));
	* $arChildren = $dir-&gt;GetChildren();
	* foreach ($arChildren as $child)
	* {
	*  if (!$child-&gt;IsDirectory())
	*  {
	*   echo "В папке есть файл ".$child-&gt;GetName()." размером ".$child-&gt;GetFileSize()."<br>";
	*  }
	*  if ($child-&gt;IsDirectory())
	*  {
	*   echo "В папке есть подпапка ".$child-&gt;GetName()."<br>";
	*  }
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualdirectory/getchildren.php
	* @author Bitrix
	*/
	public abstract function GetChildren();
	public abstract function Create();
}
?>