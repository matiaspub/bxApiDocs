<?php
namespace Bitrix\Main\Data;

class CacheEngineNone
	implements ICacheEngine, ICacheEngineStat
{
	static public function getReadBytes()
	{
		return 0;
	}

	static public function getWrittenBytes()
	{
		return 0;
	}

	static public function getCachePath()
	{
		return "";
	}

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