<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Seo;

interface IEngine
{
	public function getCode();

	static public function getInterface();

	public function getAuthSettings();

	static public function setAuthSettings($settings);
}