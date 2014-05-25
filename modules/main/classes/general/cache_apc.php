<?
class CPHPCacheAPC implements ICacheBackend
{
	var $sid = "";
	//cache stats
	var $written = false;
	var $read = false;

	public function __construct()
	{
		$this->CPHPCacheAPC();
	}

	public function CPHPCacheAPC()
	{
		if(defined("BX_CACHE_SID"))
			$this->sid = BX_CACHE_SID;
		else
			$this->sid = "BX";
	}

	public static function IsAvailable()
	{
		return function_exists('apc_fetch');
	}

	public function clean($basedir, $initdir = false, $filename = false)
	{
		if(strlen($filename))
		{
			$basedir_version = apc_fetch($this->sid.$basedir);
			if($basedir_version === false)
				return true;

			if($initdir !== false)
			{
				$initdir_version = apc_fetch($basedir_version."|".$initdir);
				if($initdir_version === false)
					return true;
			}
			else
			{
				$initdir_version = "";
			}

			apc_delete($basedir_version."|".$initdir_version."|".$filename);
		}
		else
		{
			if(strlen($initdir))
			{
				$basedir_version = apc_fetch($this->sid.$basedir);
				if($basedir_version === false)
					return true;

				apc_delete($basedir_version."|".$initdir);
			}
			else
			{
				apc_delete($this->sid.$basedir);
			}
		}
		return true;
	}

	public function read(&$arAllVars, $basedir, $initdir, $filename, $TTL)
	{
		$basedir_version = apc_fetch($this->sid.$basedir);
		if($basedir_version === false)
			return false;

		if($initdir !== false)
		{
			$initdir_version = apc_fetch($basedir_version."|".$initdir);
			if($initdir_version === false)
				return false;
		}
		else
		{
			$initdir_version = "";
		}

		$arAllVars = apc_fetch($basedir_version."|".$initdir_version."|".$filename);

		if($arAllVars === false)
		{
			return false;
		}
		else
		{
			$this->read = strlen($arAllVars);
			$arAllVars = unserialize($arAllVars);
		}

		return true;
	}

	public function write($arAllVars, $basedir, $initdir, $filename, $TTL)
	{
		$basedir_version = apc_fetch($this->sid.$basedir);
		if($basedir_version === false)
		{
			$basedir_version = md5(mt_rand());
			if(!apc_store($this->sid.$basedir, $basedir_version))
				return;
		}

		if($initdir !== false)
		{
			$initdir_version = apc_fetch($basedir_version."|".$initdir);
			if($initdir_version === false)
			{
				$initdir_version = md5(mt_rand());
				if(!apc_store($basedir_version."|".$initdir, $initdir_version))
					return;
			}
		}
		else
		{
			$initdir_version = "";
		}

		$arAllVars = serialize($arAllVars);
		$this->written = strlen($arAllVars);

		apc_store($basedir_version."|".$initdir_version."|".$filename, $arAllVars, intval($TTL));
	}

	public static function IsCacheExpired($path)
	{
		return false;
	}
}
?>