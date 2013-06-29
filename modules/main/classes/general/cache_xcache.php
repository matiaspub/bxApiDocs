<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

class CPHPCacheXCache implements ICacheBackend
{
	var $sid = "";
	//cache stats
	var $written = false;
	var $read = false;

	public function __construct()
	{
		$this->CPHPCacheXCache();
	}

	public function CPHPCacheXCache()
	{
		if(defined("BX_CACHE_SID"))
			$this->sid = BX_CACHE_SID;
		else
			$this->sid = "BX";
	}

	public static function IsAvailable()
	{
		return function_exists('xcache_get');
	}

	public function clean($basedir, $initdir = false, $filename = false)
	{
		if(strlen($filename))
		{
			$basedir_version = xcache_get($this->sid.$basedir);
			if($basedir_version === null)
				return true;

			if($initdir !== false)
			{
				$initdir_version = xcache_get($basedir_version."|".$initdir);
				if($initdir_version === null)
					return true;
			}
			else
			{
				$initdir_version = "";
			}

			xcache_unset($basedir_version."|".$initdir_version."|".$filename);
		}
		else
		{
			if(strlen($initdir))
			{
				$basedir_version = xcache_get($this->sid.$basedir);
				if($basedir_version === null)
					return true;

				xcache_unset($basedir_version."|".$initdir);
			}
			else
			{
				xcache_unset($this->sid.$basedir);
			}
		}
		return true;
	}

	public function read(&$arAllVars, $basedir, $initdir, $filename, $TTL)
	{
		$basedir_version = xcache_get($this->sid.$basedir);
		if($basedir_version === null)
			return false;

		if($initdir !== false)
		{
			$initdir_version = xcache_get($basedir_version."|".$initdir);
			if($initdir_version === null)
				return false;
		}
		else
		{
			$initdir_version = "";
		}

		$arAllVars = xcache_get($basedir_version."|".$initdir_version."|".$filename);

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
		$basedir_version = xcache_get($this->sid.$basedir);
		if($basedir_version === null)
		{
			$basedir_version = md5(mt_rand());
			if(!xcache_set($this->sid.$basedir, $basedir_version))
				return;
		}

		if($initdir !== false)
		{
			$initdir_version = xcache_get($basedir_version."|".$initdir);
			if($initdir_version === null)
			{
				$initdir_version = md5(mt_rand());
				if(!xcache_set($basedir_version."|".$initdir, $initdir_version))
					return;
			}
		}
		else
		{
			$initdir_version = "";
		}

		$arAllVars = serialize($arAllVars);
		$this->written = strlen($arAllVars);

		xcache_set($basedir_version."|".$initdir_version."|".$filename, $arAllVars, intval($TTL));
	}

	public static function IsCacheExpired($path)
	{
		return false;
	}
}
