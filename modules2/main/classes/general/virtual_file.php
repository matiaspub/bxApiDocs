<?
abstract class CBXVirtualFileBase
	implements IBXGetErrors
{
	protected $path = null;

	static public function __construct($path)
	{
		$io = CBXVirtualIo::GetInstance();
		$this->path = $io->CombinePath($path);
	}

	static public function GetName()
	{
		$io = CBXVirtualIo::GetInstance();
		return $io->ExtractNameFromPath($this->path);
	}

	static public function GetPath()
	{
		$io = CBXVirtualIo::GetInstance();
		return $io->ExtractPathFromPath($this->path);
	}

	static public function GetPathWithName()
	{
		return $this->path;
	}

	public abstract function IsDirectory();
	static public abstract function IsExists();
	public abstract function MarkWritable();
	static public abstract function GetPermissions();
	static public abstract function GetModificationTime();
	static public abstract function GetLastAccessTime();
}

abstract class CBXVirtualFile
	extends CBXVirtualFileBase
{
	static public function IsDirectory()
	{
		return false;
	}

	static public function GetType()
	{
		return GetFileType($this->path);
	}

	static public function GetExtension()
	{
		return GetFileExtension($this->path);
	}

	static public abstract function Open($mode);
	public abstract function GetContents();
	static public abstract function PutContents($data);
	public abstract function GetFileSize();
	static public abstract function IsWritable();
	public abstract function IsReadable();
	static public abstract function ReadFile();
}

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
	public abstract function GetChildren();
	static public abstract function Create();
}
?>