<?
class CPHPCacheMemcache implements ICacheBackend
{
	private static $obMemcache;
	private static $basedir_version = array();
	var $sid = "";
	//cache stats
	var $written = false;
	var $read = false;
	// unfortunately is not available for memcache...

	public function __construct()
	{
		$this->CPHPCacheMemcache();
	}

	public static function CPHPCacheMemcache()
	{
		if(!is_object(self::$obMemcache))
			self::$obMemcache = new Memcache;

		if(defined("BX_MEMCACHE_PORT"))
			$port = intval(BX_MEMCACHE_PORT);
		else
			$port = 11211;

		if(!defined("BX_MEMCACHE_CONNECTED"))
		{
			if(self::$obMemcache->connect(BX_MEMCACHE_HOST, $port))
			{
				// define("BX_MEMCACHE_CONNECTED", true);
				register_shutdown_function(array("CPHPCacheMemcache", "close"));
			}
		}

		if(defined("BX_CACHE_SID"))
			$this->sid = BX_CACHE_SID;
		else
			$this->sid = "BX";
	}

	public static function close()
	{
		if(defined("BX_MEMCACHE_CONNECTED") && is_object(self::$obMemcache))
			self::$obMemcache->close();
	}

	public static function IsAvailable()
	{
		return defined("BX_MEMCACHE_CONNECTED");
	}

	public function clean($basedir, $initdir = false, $filename = false)
	{
		if(is_object(self::$obMemcache))
		{
			if(strlen($filename))
			{
				if(!isset(self::$basedir_version[$basedir]))
					self::$basedir_version[$basedir] = self::$obMemcache->get($this->sid.$basedir);

				if(self::$basedir_version[$basedir] === false || self::$basedir_version[$basedir] === '')
					return true;

				if($initdir !== false)
				{
					$initdir_version = self::$obMemcache->get(self::$basedir_version[$basedir]."|".$initdir);
					if($initdir_version === false || $initdir_version === '')
						return true;
				}
				else
				{
					$initdir_version = "";
				}

				self::$obMemcache->replace(self::$basedir_version[$basedir]."|".$initdir_version."|".$filename, "", 0, 1);
			}
			else
			{
				if(strlen($initdir))
				{
					if(!isset(self::$basedir_version[$basedir]))
						self::$basedir_version[$basedir] = self::$obMemcache->get($this->sid.$basedir);

					if(self::$basedir_version[$basedir] === false || self::$basedir_version[$basedir] === '')
						return true;

					self::$obMemcache->replace(self::$basedir_version[$basedir]."|".$initdir, "", 0, 1);
				}
				else
				{
					if(isset(self::$basedir_version[$basedir]))
						unset(self::$basedir_version[$basedir]);

					self::$obMemcache->replace($this->sid.$basedir, "", 0, 1);
				}
			}
			return true;
		}

		return false;
	}

	public function read(&$arAllVars, $basedir, $initdir, $filename, $TTL)
	{
		if(!isset(self::$basedir_version[$basedir]))
			self::$basedir_version[$basedir] = self::$obMemcache->get($this->sid.$basedir);

		if(self::$basedir_version[$basedir] === false || self::$basedir_version[$basedir] === '')
			return false;

		if($initdir !== false)
		{
			$initdir_version = self::$obMemcache->get(self::$basedir_version[$basedir]."|".$initdir);
			if($initdir_version === false || $initdir_version === '')
				return false;
		}
		else
		{
			$initdir_version = "";
		}

		$arAllVars = self::$obMemcache->get(self::$basedir_version[$basedir]."|".$initdir_version."|".$filename);

		if($arAllVars === false || $arAllVars === '')
			return false;

		return true;
	}

	public function write($arAllVars, $basedir, $initdir, $filename, $TTL)
	{
		if(!isset(self::$basedir_version[$basedir]))
			self::$basedir_version[$basedir] = self::$obMemcache->get($this->sid.$basedir);

		if(self::$basedir_version[$basedir] === false || self::$basedir_version[$basedir] === '')
		{
			self::$basedir_version[$basedir] = $this->sid.md5(mt_rand());
			self::$obMemcache->set($this->sid.$basedir, self::$basedir_version[$basedir]);
		}

		if($initdir !== false)
		{
			$initdir_version = self::$obMemcache->get(self::$basedir_version[$basedir]."|".$initdir);
			if($initdir_version === false || $initdir_version === '')
			{
				$initdir_version = $this->sid.md5(mt_rand());
				self::$obMemcache->set(self::$basedir_version[$basedir]."|".$initdir, $initdir_version);
			}
		}
		else
		{
			$initdir_version = "";
		}

		self::$obMemcache->set(self::$basedir_version[$basedir]."|".$initdir_version."|".$filename, $arAllVars, 0, time()+intval($TTL));
	}

	public static function IsCacheExpired($path)
	{
		return false;
	}
}
?>