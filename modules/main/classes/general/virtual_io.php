<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/virtual_file.php");

interface IBXVirtualIO
{
	function CombinePath();
	public static function RelativeToAbsolutePath($relativePath);
	public static function SiteRelativeToAbsolutePath($relativePath, $site = null);
	public static function GetPhysicalName($path);
	function GetLogicalName($path);
	public static function ExtractNameFromPath($path);
	function ExtractPathFromPath($path);
	static function ValidatePathString($path);
	public static function ValidateFilenameString($filename);
	function DirectoryExists($path);
	public static function FileExists($path);
	function GetDirectory($path);
	public static function GetFile($path);
	function OpenFile($path, $mode);
	public static function CreateDirectory($path);
	function Delete($path);
	public static function Copy($source, $target, $bRewrite = true);
	public static function Move($source, $target, $bRewrite = true);
	function Rename($source, $target);
	public static function ClearCache();
}

interface IBXGetErrors
{
	public static function GetErrors();
}

/**
 * Proxy class for file IO. Provides a set of methods to retrieve resources from a file system.
 */

/**
 * Поддержка <b>Bitrix Framework</b> русских (и прочих) символов в названиях публичных файлов накладывает определённые ограничения на работу: <br> недопустимы прямые вызовы
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/index.php
 * @author Bitrix
 */
class CBXVirtualIo
	implements IBXVirtualIO, IBXGetErrors
{
	private static $instance;
	private $io;

	public function __construct()
	{
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/virtual_io_filesystem.php");
		$this->io = new CBXVirtualIoFileSystem();
	}

	/**
	 * Returns proxy class instance (singleton pattern)
	 *
	 * @static
	 * @return CBXVirtualIo - Proxy class instance
	 */
	public static function GetInstance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	/**
	 * Combines a path parts
	 *
	 * Variable-length argument list
	 *
	 * @return string - Combined path
	 */
	
	/**
	* <p>Объединяет части пути в единый путь. Параметров может быть произвольное число. После объединения путь приводится к нормальной форме. Нестатический метод.</p>  <a name="examples"></a>
	*
	*
	* @param string $path1  
	*
	* @param string $path2  
	*
	* @param string $path3  
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* echo $io-&gt;CombinePath("/", "index.php");  // вернет /index.php
	* echo $io-&gt;CombinePath("/", "/path1/", "\\path2/", "path3\\. ./path4/");  // вернет /path1/path2/path4
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/combinepath.php
	* @author Bitrix
	*/
	public function CombinePath()
	{
		$numArgs = func_num_args();
		if ($numArgs <= 0)
			return "";

		$arParts = array();
		for ($i = 0; $i < $numArgs; $i++)
		{
			$arg = func_get_arg($i);
			if (empty($arg))
				continue;

			if (is_array($arg))
			{
				foreach ($arg as $v)
				{
					if (empty($v))
						continue;
					$arParts[] = $v;
				}
			}
			else
			{
				$arParts[] = $arg;
			}
		}

		return $this->io->CombinePath($arParts);
	}

	/**
	 * Converts a relative path to absolute one
	 *
	 * @param string $relativePath - Relative path
	 * @return string - Complete path
	 */
	
	/**
	* <p>Приводит путь относительно корня продукта к абсолютному пути. Путь приводится к нормальной форме. Нестатический метод.</p>
	*
	*
	* @param string $relativePath  Относительный путь
	*
	* @return result_type 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* echo $io-&gt;RelativeToAbsolutePath("/path1/index.php");  // вернет c:/Projects/site1/path1/index.php
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/relativetoabsolutepath.php
	* @author Bitrix
	*/
	public function RelativeToAbsolutePath($relativePath)
	{
		return $this->io->RelativeToAbsolutePath($relativePath);
	}

	
	/**
	* <p>Приводит путь относительно корня указанного сайта к абсолютному пути. Путь приводится к нормальной форме. Нестатический метод.</p>
	*
	*
	* @param string $relativePath  Относительный путь
	*
	* @param string $site = null домен сайта
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* echo $io-&gt;SiteRelativeToAbsolutePath("/path1/index.php", "s1");  // вернет c:/Projects/site1/s1/path1/index.php
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/siterelativetoabsolutepath.php
	* @author Bitrix
	*/
	public function SiteRelativeToAbsolutePath($relativePath, $site = null)
	{
		return $this->io->SiteRelativeToAbsolutePath($relativePath, $site);
	}

	/**
	 * Returns Physical path to file or directory
	 *
	 * @param string $path - Path
	 * @return string - Physical path
	 */
	public function GetPhysicalName($path)
	{
		return $this->io->GetPhysicalName($path);
	}

	public function GetLogicalName($path)
	{
		return $this->io->GetLogicalName($path);
	}

	/**
	 * Returns name of the file or directory
	 *
	 * @param string $path - Path
	 * @return string - File/directory name
	 */
	
	/**
	* <p>Возвращает имя файла или папки принимая на вход путь. Хорошо, если путь сначала приведен к нормальной форме. Нестатический метод.</p>
	*
	*
	* @param string $path  Путь
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* echo $io-&gt;ExtractNameFromPath("/path1/index.php");   // вернет index.php
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/extractnamefrompath.php
	* @author Bitrix
	*/
	public function ExtractNameFromPath($path)
	{
		return $this->io->ExtractNameFromPath($path);
	}

	/**
	 * Returns path to the file or directory (without file/directory name)
	 *
	 * @param string $path - Path
	 * @return string - Result
	 */
	
	/**
	* <p>Возвращает путь к файлу или папке принимая на вход путь. Хорошо, если путь сначала приведен к нормальной форме. Нестатический метод.</p>
	*
	*
	* @param string $path  Путь
	*
	* @return result_type 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* echo $io-&gt;ExtractPathFromPath("/path1/index.php");   // вернет /path1
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/extractpathfrompath.php
	* @author Bitrix
	*/
	public function ExtractPathFromPath($path)
	{
		return $this->io->ExtractPathFromPath($path);
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	
	/**
	* <p>Проверяет, является ли путь корректным. Нестатический метод.</p>
	*
	*
	* @param string $path  Путь
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* echo $io-&gt;ValidatePathString("/path1/путь2/файл.php");  // вернет 1
	* echo $io-&gt;ValidatePathString("/path1/пу*ть2/файл.php?"); // вернет 0
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/validatepathstring.php
	* @author Bitrix
	*/
	public function ValidatePathString($path)
	{
		return $this->io->ValidatePathString($path);
	}

	/**
	 * @param string $filename
	 * @return bool
	 */
	public function ValidateFilenameString($filename)
	{
		return $this->io->ValidateFilenameString($filename);
	}

	/**
	 * @param string $filename
	 * @return string
	 */
	public function RandomizeInvalidFilename($filename)
	{
		return $this->io->RandomizeInvalidFilename($filename);
	}

	/**
	 * Gets a value that indicates whether a directory exists in the file system
	 *
	 * @param string $path - Complete path to the directory
	 * @return bool - True if the directory exists, false - otherwise
	 */
	
	/**
	* <p>Проверяет, существует ли указанная папка. На вход принимает абсолютный путь.  Нестатический метод.</p>
	*
	*
	* @param string $path  Путь
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* echo $io-&gt;DirectoryExists($io-&gt;RelativeToAbsolutePath("/папка1/папка2"));
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/directoryexists.php
	* @author Bitrix
	*/
	public function DirectoryExists($path)
	{
		return $this->io->DirectoryExists($path);
	}

	/**
	 * Gets a value that indicates whether a file exists in the file system
	 *
	 * @param string $path - Complete path to the file
	 * @return bool - True if the file exists, false - otherwise
	 */
	
	/**
	* <p>Проверяет, существует ли указанный файл. На вход принимает абсолютный путь. Нестатический метод.</p>
	*
	*
	* @param string $path  Путь
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* echo $io-&gt;FileExists($io-&gt;RelativeToAbsolutePath("папка1/файл.php"));
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/fileexists.php
	* @author Bitrix
	*/
	public function FileExists($path)
	{
		return $this->io->FileExists($path);
	}

	/**
	 * Gets a directory from the file system
	 *
	 * @param string $path - Complete path to the directory
	 * @return CBXVirtualDirectoryFileSystem
	 */
	
	/**
	* <p>Возвращает объект класса папки для указанного пути. На вход принимает абсолютный путь. При этом существование папки не проверяется. Нестатический метод.</p>
	*
	*
	* @param string $path  Путь
	*
	* @return CBXVirtualDirectory 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $dir = $io-&gt;GetDirectory($io-&gt;RelativeToAbsolutePath("/папка1/папка2"));
	* $arChildren = $dir-&gt;GetChildren();
	* foreach ($arChildren as $child)
	* {
	*  if (!$child-&gt;IsDirectory() &amp;&amp; $child-&gt;GetName() != ".access.php")
	*   die("Error");
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/getdirectory.php
	* @author Bitrix
	*/
	public function GetDirectory($path)
	{
		return $this->io->GetDirectory($path);
	}

	/**
	 * Gets a virtual file from the file system
	 *
	 * @param string $path - Complete path to the file
	 * @return CBXVirtualFileFileSystem
	 */
	
	/**
	* <p>Возвращает объект класса файла для указанного пути. На вход принимает абсолютный путь. При этом существование файла не проверяется. Нестатический метод.</p>
	*
	*
	* @param string $path  Путь
	*
	* @return CBXVirtualFile 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $fp = $io-&gt;RelativeToAbsolutePath("/папка1/.access.php");
	* $f = $io-&gt;GetFile($fp);
	* $f-&gt;MarkWritable();
	* $io-&gt;Delete($fp);
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/getfile.php
	* @author Bitrix
	*/
	public function GetFile($path)
	{
		return $this->io->GetFile($path);
	}

	/**
	 * Returns a stream from a file
	 *
	 * @param string $path - Complete path to the file
	 * @param string $mode - The type of access to the file ('rb' - reading, 'wb' - writing, 'ab' - appending)
	 * @return resource
	 */
	public function OpenFile($path, $mode)
	{
		return $this->io->OpenFile($path, $mode);
	}

	/**
	 * Deletes a file or directory from the file system
	 *
	 * @param string $path - Complete path to the file or directory
	 * @return bool - Result
	 */
	
	/**
	* <p>Удаляет файл или папку.  Нестатический метод.</p>
	*
	*
	* @param string $path  Путь
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $path = $io-&gt;RelativeToAbsolutePath($path);
	* $flTmp = $io-&gt;GetFile($path);
	* $flSzTmp = $flTmp-&gt;GetFileSize();
	* if ($io-&gt;Delete($path))
	* {
	*  if (COption::GetOptionInt("main", "disk_space") &gt; 0)
	*   CDiskQuota::updateDiskQuota("file", $flSzTmp, "delete");
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/delete.php
	* @author Bitrix
	*/
	public function Delete($path)
	{
		return $this->io->Delete($path);
	}

	/**
	 * Copies a file or directory from source location to target
	 *
	 * @param string $source - Complete path of the source file or directory
	 * @param string $target - Complete path of the target file or directory
	 * @param bool $bRewrite - True to rewrite existing files, false - otherwise
	 * @return bool - Result
	 */
	
	/**
	* <p>Копирует файл или папку. Дополнительный параметр указывает, перетирать ли существующие файлы. На вход принимает абсолютный путь. Нестатический метод.</p>
	*
	*
	* @param string $source  Путь к источнику.
	*
	* @param string $target  Путь к целевой папке.
	*
	* @param bool $bRewrite = true] Если файл существует переписывать или нет? По умолчанию <i>true</i>.
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/copy.php
	* @author Bitrix
	*/
	public function Copy($source, $target, $bRewrite = true)
	{
		return $this->io->Copy($source, $target, $bRewrite);
	}

	/**
	 * Moves a file or directory from source location to target
	 *
	 * @param string $source - Complete path of the source file or directory
	 * @param string $target - Complete path of the target file or directory
	 * @param bool $bRewrite - True to rewrite existing files, false - otherwise
	 * @return bool - Result
	 */
	
	/**
	* <p>Перемещает файл или папку. Дополнительный параметр указывает, перетирать ли существующие файлы. На вход принимает абсолютный путь. Нестатический метод.</p>
	*
	*
	* @param string $source  Исходное местоположение.
	*
	* @param string $target  Конечное местоположение.
	*
	* @param bool $bRewrite = true] Если файл существует переписывать или нет? По умолчанию <i>true</i>.
	*
	* @return bool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/move.php
	* @author Bitrix
	*/
	public function Move($source, $target, $bRewrite = true)
	{
		return $this->io->Move($source, $target, $bRewrite);
	}

	public function Rename($source, $target)
	{
		return $this->io->Rename($source, $target);
	}

	/**
	 * Clear file system cache (if any)
	 *
	 * @return void
	 */
	public function ClearCache()
	{
		$this->io->ClearCache();
	}

	/**
	 * Creates a directory if is is not exist
	 *
	 * @param string $path - Complete path of the directory
	 * @return CBXVirtualDirectory|null
	 */
	
	/**
	* <p>Создает указанную папку, если ее нет. Возвращает объект созданной или существующей папки или null в случае ошибки. На вход принимает абсолютный путь.  Нестатический метод.</p>
	*
	*
	* @param string $path  Путь
	*
	* @return CBXVirtualDirectory 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* if (!$io-&gt;CreateDirectory($io-&gt;RelativeToAbsolutePath($path)))
	*  die();
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbxvirtualio/createdirectory.php
	* @author Bitrix
	*/
	public function CreateDirectory($path)
	{
		return $this->io->CreateDirectory($path);
	}

	/**
	 * Returns runtime errors
	 *
	 * @return array - Array of errors
	 */
	public function GetErrors()
	{
		return $this->io->GetErrors();
	}
}
?>