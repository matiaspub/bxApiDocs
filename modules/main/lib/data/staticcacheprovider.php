<?
namespace Bitrix\Main\Data;

use Bitrix\Main;

abstract class StaticCacheProvider
{
	abstract public function isCacheable();
	abstract public function setUserPrivateKey();
	abstract public function getCachePrivateKey();
	abstract public function onBeforeEndBufferContent();
}