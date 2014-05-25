<?php
namespace Bitrix\Main\Data;


class CacheEngineXCache
	implements ICacheEngine, ICacheEngineStat
{
	private $sid = "";
	//cache stats
	private $written = false;
	private $read = false;

	public function __construct()
	{
		$v = \Bitrix\Main\Config\Configuration::getValue("cache");
		if ($v != null && isset($v["sid"]) && ($v["sid"] != ""))
			$this->sid = $v["sid"];
		else
			$this->sid = "BX";
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

	static public function isAvailable()
	{
		return function_exists('xcache_get');
	}

	public function clean($baseDir, $initDir = false, $filename = false)
	{
		if(strlen($filename))
		{
			$baseDirVersion = xcache_get($this->sid.$baseDir);
			if($baseDirVersion === null)
				return true;

			if($initDir !== false)
			{
				$initDirVersion = xcache_get($baseDirVersion."|".$initDir);
				if($initDirVersion === null)
					return true;
			}
			else
			{
				$initDirVersion = "";
			}

			xcache_unset($baseDirVersion."|".$initDirVersion."|".$filename);
		}
		else
		{
			if(strlen($initDir))
			{
				$baseDirVersion = xcache_get($this->sid.$baseDir);
				if($baseDirVersion === null)
					return true;

				xcache_unset($baseDirVersion."|".$initDir);
			}
			else
			{
				xcache_unset($this->sid.$baseDir);
			}
		}
		return true;
	}

	public function read(&$arAllVars, $baseDir, $initDir, $filename, $TTL)
	{
		$baseDirVersion = xcache_get($this->sid.$baseDir);
		if($baseDirVersion === null)
			return false;

		if($initDir !== false)
		{
			$initDirVersion = xcache_get($baseDirVersion."|".$initDir);
			if($initDirVersion === null)
				return false;
		}
		else
		{
			$initDirVersion = "";
		}

		$arAllVars = xcache_get($baseDirVersion."|".$initDirVersion."|".$filename);

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

	public function write($arAllVars, $baseDir, $initDir, $filename, $TTL)
	{
		$baseDirVersion = xcache_get($this->sid.$baseDir);
		if($baseDirVersion === null)
		{
			$baseDirVersion = md5(mt_rand());
			if(!xcache_set($this->sid.$baseDir, $baseDirVersion))
				return;
		}

		if($initDir !== false)
		{
			$initDirVersion = xcache_get($baseDirVersion."|".$initDir);
			if($initDirVersion === null)
			{
				$initDirVersion = md5(mt_rand());
				if(!xcache_set($baseDirVersion."|".$initDir, $initDirVersion))
					return;
			}
		}
		else
		{
			$initDirVersion = "";
		}

		$arAllVars = serialize($arAllVars);
		$this->written = strlen($arAllVars);

		xcache_set($baseDirVersion."|".$initDirVersion."|".$filename, $arAllVars, intval($TTL));
	}

	static public function isCacheExpired($path)
	{
		return false;
	}
} 