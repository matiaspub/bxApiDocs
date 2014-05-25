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
	public abstract function Create();
}
?>