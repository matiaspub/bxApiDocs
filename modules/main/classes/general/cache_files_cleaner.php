<?
class CFileCacheCleaner
{
	private $_CacheType;
	private $_arPath;

	private $_CurrentBase;
	private $_CurrentPath;

	private $_obFileTree;

	public function __construct($CacheType)
	{
		global $DB;
		$this->_CacheType = $CacheType;

		switch($this->_CacheType)
		{
		case "menu":
			$this->_arPath = array(
				BX_PERSONAL_ROOT.'/managed_cache/'.$DB->type.'/menu/',
			);
			break;
		case "managed":
			$this->_arPath = array(
				BX_PERSONAL_ROOT.'/managed_cache/',
				BX_PERSONAL_ROOT.'/stack_cache/',
			);
			break;
		case "html":
			$this->_arPath = array(
				BX_PERSONAL_ROOT.'/html_pages/',
			);
			break;
		case "expired":
			$this->_arPath = array(
				BX_PERSONAL_ROOT.'/cache/',
				BX_PERSONAL_ROOT.'/managed_cache/',
				BX_PERSONAL_ROOT.'/stack_cache/',
			);
			break;
		default:
			$this->_arPath = array(
				BX_PERSONAL_ROOT.'/cache/',
				BX_PERSONAL_ROOT.'/managed_cache/',
				BX_PERSONAL_ROOT.'/stack_cache/',
				BX_PERSONAL_ROOT.'/html_pages/',
			);
			break;
		}
	}

	public function InitPath($PathToCheck)
	{
		if(strlen($PathToCheck) > 0)
		{
			$PathToCheck = preg_replace("#[\\\\\\/]+#", "/", "/".$PathToCheck);
			//Check if path does not contain any injection
			if(preg_match('#/\\.\\.#', $PathToCheck) || preg_match('#\\.\\./#', $PathToCheck))
				return false;

			$base = "";
			foreach($this->_arPath as $path)
			{
				if(preg_match('#^'.$path.'#', $PathToCheck))
				{
					$base = $path;
					break;
				}
			}

			if(strlen($base))
			{
				$this->_CurrentBase = $base;
				$this->_CurrentPath = substr($PathToCheck, strlen($base));
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			$this->_CurrentBase = $this->_arPath[0];
			$this->_CurrentPath = "";
			return true;
		}
	}

	public function Start()
	{
		if($this->_CurrentBase)
		{
			$this->_obFileTree = new _CFileTree($_SERVER["DOCUMENT_ROOT"].$this->_CurrentBase);
			$this->_obFileTree->Start($this->_CurrentPath);
		}
	}

	public function GetNextFile()
	{
		if(is_object($this->_obFileTree))
		{
			$file = $this->_obFileTree->GetNextFile();
			//Check if current cache subdirectory cleaned
			if($file === false)
			{
				//Skip all checked bases
				$arPath = $this->_arPath;
				while(count($arPath) > 0)
				{
					$CurBase = array_shift($arPath);
					if($CurBase == $this->_CurrentBase)
						break;
				}
				//There is at least one cache directory not checked yet
				//so try to find a file inside
				while(count($arPath) > 0)
				{
					$this->_CurrentBase = array_shift($arPath);
					$this->_CurrentPath = "";
					$this->_obFileTree = new _CFileTree($_SERVER["DOCUMENT_ROOT"].$this->_CurrentBase);
					$this->_obFileTree->Start($this->_CurrentPath);
					$file = $this->_obFileTree->GetNextFile();
					if($file !== false)
						return $file;
				}
				return false;
			}
			return $file;
		}
		else
		{
			return false;
		}
	}

	public static function GetFileExpiration($FileName)
	{
		if(preg_match('#^'.$_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT.'/html_pages/.*\\.html$#', $FileName))
		{
			return 1;//like an very old file
		}
		elseif(preg_match('#\\.~\\d+/#', $FileName)) //delayed delete files
		{
			return 1;//like an very old file
		}
		elseif(substr($FileName, -4) == ".php")
		{
			$fd = fopen($FileName, "rb");
			if($fd)
			{
				$header = fread($fd, 150);
				fclose($fd);
				if(preg_match("/dateexpire\s*=\s*'([\d]+)'/im", $header, $match))
					return doubleval($match[1]);
			}
		}
		elseif(substr($FileName, -5) == ".html")
		{
			$fd = fopen($FileName, "rb");
			if($fd)
			{
				$header = fread($fd, 26);
				fclose($fd);
				if(substr($header, 0, 2) == "BX")
					return doubleval(substr($header, 14, 12));
			}
		}
		return false;
	}
}

class _CFileTree
{
	var $_in_path = '/';
	var $_path = '';
	var $_dir = false;

	public function __construct($in_path="/")
	{
		$this->_in_path = preg_replace("#[\\\\\\/]+#", "/", $in_path);
	}

	public function Start($path="/")
	{
		$this->_path = preg_replace("#[\\\\\\/]+#", "/", $this->_in_path.trim($path, "/"));

		if(!$this->FileExists($this->_path) || is_file($this->_path))
		{
			$last = self::ExtractFileFromPath($this->_path);
			$this->_dir = $this->ReadDir($this->_path);
			if(is_array($this->_dir))
			{
				while(count($this->_dir))
				{
					if(strcmp($this->_dir[0], $last) > 0)
						break;
					array_shift($this->_dir);
				}
			}
		}
	}

	public static function FileExists($file)
	{
		if(function_exists('accelerator_reset'))
		{
			if(is_dir($file))
				return true;

			$fd = @fopen($file, "rb");
			if($fd)
			{
				fclose($fd);
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return file_exists($file);
		}
	}

	public function GetNextFile()
	{
		if(!is_array($this->_dir))
		{
			$this->_dir = $this->ReadDir($this->_path);
			if(!is_array($this->_dir))
				return false;
		}

		$next = each($this->_dir);

		if($next === false)
		{
			//try to go up dir tree
			if($this->GoUp())
				return $this->GetNextFile();
			else
				return false;
		}
		elseif(is_file($next["value"]))
		{
			//it's our target
			return $next["value"];
		}
		else
		{
			//it's dir or link try to go deeper
			$this->_path = $next["value"];
			$this->_dir = false;
			return true;
		}
	}

	public static function ExtractFileFromPath(&$path)
	{
		$arPath = explode("/", $path);
		$last = array_pop($arPath);
		$path = implode("/", $arPath);
		return $path."/".$last;
	}

	public function GoUp()
	{
		$last_dir = self::ExtractFileFromPath($this->_path);
		//We are not going to go up any more
		if(strlen($this->_path."/") < strlen($this->_in_path))
			return false;

		$this->_dir = $this->ReadDir($this->_path);
		//This should't be happen so try to goup one more level
		if(!is_array($this->_dir))
			return $this->GoUp();

		//Skip all dirs till current
		while(count($this->_dir))
		{
			if(strcmp($this->_dir[0], $last_dir) > 0)
				break;
			array_shift($this->_dir);
		}

		if(count($this->_dir))
			return true; //there is more work to do
		else
			return $this->GoUp(); // try to go upper
	}

	public static function ReadDir($dir)
	{
		$dir = rtrim($dir, "/");
		if(is_dir($dir))
		{
			$dh = opendir($dir);
			if($dh)
			{
				$result = array();
				while(($f = readdir($dh)) !== false)
				{
					if($f == "." || $f == "..")
						continue;
					$result[] = $dir."/".$f;
				}
				closedir($dh);
				sort($result);
				//try to delete an empty directory
				if(count($result) == 0)
					@rmdir($dir);

				return $result;
			}
		}
		return false;
	}
}
?>