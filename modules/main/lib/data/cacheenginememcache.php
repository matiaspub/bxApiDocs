<?php
namespace Bitrix\Main\Data;

use Bitrix\Main\Config;

class CacheEngineMemcache
	implements ICacheEngine, ICacheEngineStat
{
	private static $obMemcache = null;
	private static $isConnected = false;

	private static $baseDirVersion = array();
	private $sid = "";
	//cache stats
	private $written = false;
	private $read = false;
	// unfortunately is not available for memcache...

	public function __construct()
	{
		if (self::$obMemcache == null)
		{
			self::$obMemcache = new \Memcache;

			$cacheConfig = Config\Configuration::getValue("cache");
			$v = (isset($cacheConfig["memcache"])) ? $cacheConfig["memcache"] : null;

			if ($v != null && isset($v["host"]) && $v["host"] != "")
			{
				if ($v != null && isset($v["port"]))
					$port = intval($v["port"]);
				else
					$port = 11211;

				if (self::$obMemcache->pconnect($v["host"], $port))
				{
					self::$isConnected = true;
				}
			}
		}

		$v = Config\Configuration::getValue("cache");
		if ($v != null && isset($v["sid"]) && ($v["sid"] != ""))
			$this->sid = $v["sid"];
		else
			$this->sid = "BX";
	}

	public static function close()
	{
		if(self::$obMemcache != null)
		{
			self::$obMemcache->close();
			self::$obMemcache = null;
		}
	}

	public function getReadBytes()
	{
		return $this->read;
	}

	public function getWrittenBytes()
	{
		return $this->written;
	}

	static public function getCachePath()
	{
		return "";
	}

	public static function isAvailable()
	{
		return self::$isConnected;
	}

	public function clean($baseDir, $initDir = false, $filename = false)
	{
		if(is_object(self::$obMemcache))
		{
			if(strlen($filename))
			{
				if(!isset(self::$baseDirVersion[$baseDir]))
					self::$baseDirVersion[$baseDir] = self::$obMemcache->get($this->sid.$baseDir);

				if(self::$baseDirVersion[$baseDir] === false || self::$baseDirVersion[$baseDir] === '')
					return true;

				if($initDir !== false)
				{
					$initDirVersion = self::$obMemcache->get(self::$baseDirVersion[$baseDir]."|".$initDir);
					if($initDirVersion === false || $initDirVersion === '')
						return true;
				}
				else
				{
					$initDirVersion = "";
				}

				self::$obMemcache->replace(self::$baseDirVersion[$baseDir]."|".$initDirVersion."|".$filename, "", 0, 1);
			}
			else
			{
				if(strlen($initDir))
				{
					if(!isset(self::$baseDirVersion[$baseDir]))
						self::$baseDirVersion[$baseDir] = self::$obMemcache->get($this->sid.$baseDir);

					if(self::$baseDirVersion[$baseDir] === false || self::$baseDirVersion[$baseDir] === '')
						return true;

					self::$obMemcache->replace(self::$baseDirVersion[$baseDir]."|".$initDir, "", 0, 1);
				}
				else
				{
					if(isset(self::$baseDirVersion[$baseDir]))
						unset(self::$baseDirVersion[$baseDir]);

					self::$obMemcache->replace($this->sid.$baseDir, "", 0, 1);
				}
			}
			return true;
		}

		return false;
	}

	public function read(&$arAllVars, $baseDir, $initDir, $filename, $TTL)
	{
		if(!isset(self::$baseDirVersion[$baseDir]))
			self::$baseDirVersion[$baseDir] = self::$obMemcache->get($this->sid.$baseDir);

		if(self::$baseDirVersion[$baseDir] === false || self::$baseDirVersion[$baseDir] === '')
			return false;

		if($initDir !== false)
		{
			$initDirVersion = self::$obMemcache->get(self::$baseDirVersion[$baseDir]."|".$initDir);
			if($initDirVersion === false || $initDirVersion === '')
				return false;
		}
		else
		{
			$initDirVersion = "";
		}

		$arAllVars = self::$obMemcache->get(self::$baseDirVersion[$baseDir]."|".$initDirVersion."|".$filename);

		if($arAllVars === false || $arAllVars === '')
			return false;

		return true;
	}

	public function write($arAllVars, $baseDir, $initDir, $filename, $TTL)
	{
		if(!isset(self::$baseDirVersion[$baseDir]))
			self::$baseDirVersion[$baseDir] = self::$obMemcache->get($this->sid.$baseDir);

		if(self::$baseDirVersion[$baseDir] === false || self::$baseDirVersion[$baseDir] === '')
		{
			self::$baseDirVersion[$baseDir] = $this->sid.md5(mt_rand());
			self::$obMemcache->set($this->sid.$baseDir, self::$baseDirVersion[$baseDir]);
		}

		if($initDir !== false)
		{
			$initDirVersion = self::$obMemcache->get(self::$baseDirVersion[$baseDir]."|".$initDir);
			if($initDirVersion === false || $initDirVersion === '')
			{
				$initDirVersion = $this->sid.md5(mt_rand());
				self::$obMemcache->set(self::$baseDirVersion[$baseDir]."|".$initDir, $initDirVersion);
			}
		}
		else
		{
			$initDirVersion = "";
		}

		self::$obMemcache->set(self::$baseDirVersion[$baseDir]."|".$initDirVersion."|".$filename, $arAllVars, 0, time()+intval($TTL));
	}

	public static function isCacheExpired($path)
	{
		return false;
	}
}
