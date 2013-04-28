<?php
namespace Bitrix\Main\Data;

interface ICacheEngine
{
	public function isAvailable();
	static public function clean($baseDir, $initDir = false, $filename = false);
	static public function read(&$arAllVars, $baseDir, $initDir, $filename, $TTL);
	static public function write($arAllVars, $baseDir, $initDir, $filename, $TTL);
	static public function isCacheExpired($path);
}
