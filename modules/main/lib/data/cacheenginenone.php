<?php
namespace Bitrix\Main\Data;

class CacheEngineNone
	implements ICacheEngine
{
	static public function isAvailable()
	{
		return true;
	}

	static public function clean($baseDir, $initDir = false, $filename = false)
	{
		return true;
	}

	static public function read(&$arAllVars, $baseDir, $initDir, $filename, $TTL)
	{
		return false;
	}

	static public function write($arAllVars, $baseDir, $initDir, $filename, $TTL)
	{
	}

	static public function isCacheExpired($path)
	{
		return true;
	}
}