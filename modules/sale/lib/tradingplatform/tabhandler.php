<?php

namespace Bitrix\Sale\TradingPlatform;

/**
 * Class TabHandler
 * The hairs of this class can penetrate to iblock module, section edit admin page, tab Trading platforms.
 * @package Bitrix\Sale\TradingPlatform
 */
abstract class TabHandler
{
	public $name = "";
	public $description = "";

	abstract public function action($arArgs);
	abstract public function check($arArgs);
	abstract public function showTabSection($divName, $arArgs, $bVarsFromForm);
} 