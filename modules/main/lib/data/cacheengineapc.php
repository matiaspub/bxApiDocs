<?php
namespace Bitrix\Main\Data;

class CacheEngineApc
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
		return function_exists('apc_fetch');
	}

	public function clean($baseDir, $initDir = false, $filename = false)
	{
		if(strlen($filename))
		{
			$baseDirVersion = apc_fetch($this->sid.$baseDir);
			if($baseDirVersion === false)
				return true;

			if($initDir !== false)
			{
				$initDirVersion = apc_fetch($baseDirVersion."|".$initDir);
				if($initDirVersion === false)
					return true;
			}
			else
			{
				$initDirVersion = "";
			}

			apc_delete($baseDirVersion."|".$initDirVersion."|".$filename);
		}
		else
		{
			if(strlen($initDir))
			{
				$baseDirVersion = apc_fetch($this->sid.$baseDir);
				if($baseDirVersion === false)
					return true;

				apc_delete($baseDirVersion."|".$initDir);
			}
			else
			{
				apc_delete($this->sid.$baseDir);
			}
		}
		return true;
	}

	public function read(&$arAllVars, $baseDir, $initDir, $filename, $TTL)
	{
		$baseDirVersion = apc_fetch($this->sid.$baseDir);
		if($baseDirVersion === false)
			return false;

		if($initDir !== false)
		{
			$initDirVersion = apc_fetch($baseDirVersion."|".$initDir);
			if($initDirVersion === false)
				return false;
		}
		else
		{
			$initDirVersion = "";
		}

		$arAllVars = apc_fetch($baseDirVersion."|".$initDirVersion."|".$filename);

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

	public function write($arAllVars, $baseDir, $initDir, $filename, $TTL)
	{
		$baseDirVersion = apc_fetch($this->sid.$baseDir);
		if($baseDirVersion === false)
		{
			$baseDirVersion = md5(mt_rand());
			if(!apc_store($this->sid.$baseDir, $baseDirVersion))
				return;
		}

		if($initDir !== false)
		{
			$initDirVersion = apc_fetch($baseDirVersion."|".$initDir);
			if($initDirVersion === false)
			{
				$initDirVersion = md5(mt_rand());
				if(!apc_store($baseDirVersion."|".$initDir, $initDirVersion))
					return;
			}
		}
		else
		{
			$initDirVersion = "";
		}

		$arAllVars = serialize($arAllVars);
		$this->written = strlen($arAllVars);

		apc_store($baseDirVersion."|".$initDirVersion."|".$filename, $arAllVars, intval($TTL));
	}

	static public function isCacheExpired($path)
	{
		return false;
	}
}
