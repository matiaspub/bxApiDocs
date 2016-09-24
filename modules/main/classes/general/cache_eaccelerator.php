<?
class CPHPCacheEAccelerator implements ICacheBackend
{
	var $sid = "";
	//cache stats
	var $written = false;
	var $read = false;

	public function __construct()
	{
		$this->CPHPCacheEAccelerator();
	}

	public function CPHPCacheEAccelerator()
	{
		if(defined("BX_CACHE_SID"))
			$this->sid = BX_CACHE_SID;
		else
			$this->sid = "BX";
	}

	public static function IsAvailable()
	{
		return function_exists('eaccelerator_get');
	}

	public function clean($basedir, $initdir = false, $filename = false)
	{
		if(strlen($filename))
		{
			$basedir_version = eaccelerator_get($this->sid.$basedir);
			if($basedir_version === null)
				return true;

			if($initdir !== false)
			{
				$initdir_version = eaccelerator_get($basedir_version."|".$initdir);
				if($initdir_version === null)
					return true;
			}
			else
			{
				$initdir_version = "";
			}

			eaccelerator_rm($basedir_version."|".$initdir_version."|".$filename);
		}
		else
		{
			if(strlen($initdir))
			{
				$basedir_version = eaccelerator_get($this->sid.$basedir);
				if($basedir_version === null)
					return true;

				eaccelerator_rm($basedir_version."|".$initdir);
			}
			else
			{
				eaccelerator_rm($this->sid.$basedir);
			}
		}
		return true;
	}

	public function read(&$arAllVars, $basedir, $initdir, $filename, $TTL)
	{
		$basedir_version = eaccelerator_get($this->sid.$basedir);
		if($basedir_version === null)
			return false;

		if($initdir !== false)
		{
			$initdir_version = eaccelerator_get($basedir_version."|".$initdir);
			if($initdir_version === null)
				return false;
		}
		else
		{
			$initdir_version = "";
		}

		$arAllVars = eaccelerator_get($basedir_version."|".$initdir_version."|".$filename);

		if($arAllVars === null)
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
		$basedir_version = eaccelerator_get($this->sid.$basedir);
		if($basedir_version === null)
		{
			$basedir_version = md5(mt_rand());
			if(!eaccelerator_put($this->sid.$basedir, $basedir_version))
				return;
		}

		if($initdir !== false)
		{
			$initdir_version = eaccelerator_get($basedir_version."|".$initdir);
			if($initdir_version === null)
			{
				$initdir_version = md5(mt_rand());
				if(!eaccelerator_put($basedir_version."|".$initdir, $initdir_version))
					return;
			}
		}
		else
		{
			$initdir_version = "";
		}

		$arAllVars = serialize($arAllVars);
		$this->written = strlen($arAllVars);

		eaccelerator_put($basedir_version."|".$initdir_version."|".$filename, $arAllVars, intval($TTL));
	}

	public static function IsCacheExpired($path)
	{
		return false;
	}
}
?>