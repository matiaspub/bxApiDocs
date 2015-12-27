<?php

namespace Bitrix\Iblock\Component;
/**
 * Class Tools
 * Provides various useful methods.
 *
 * @package Bitrix\Iblock\Component
 */
class Tools
{
	/**
	 * Performs actions enabled by its parameters.
	 *
	 * @param string $message Message to show with bitrix:system.show_message component.
	 * @param bool $defineConstant If true then ERROR_404 constant defined.
	 * @param bool $setStatus If true sets http response status.
	 * @param bool $showPage If true then work area will be cleaned and /404.php will be included.
	 * @param string $pageFile Alternative file to /404.php.
	 *
	 * @return void
	 */
	public static function process404($message = "", $defineConstant = true, $setStatus = true, $showPage = false, $pageFile = "")
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		if($message <> "")
		{
			$APPLICATION->includeComponent(
				"bitrix:system.show_message",
				".default",
				array(
					"MESSAGE"=> $message,
					"STYLE" => "errortext",
				),
				null,
				array(
					"HIDE_ICONS" => "Y",
				)
			);
		}

		if ($defineConstant && !defined("ERROR_404"))
		{
			// define("ERROR_404", "Y");
		}

		if ($setStatus)
		{
			\CHTTP::setStatus("404 Not Found");
		}

		if ($showPage)
		{
			if ($APPLICATION->RestartWorkarea())
			{
				if ($pageFile)
					require(\Bitrix\Main\Application::getDocumentRoot().rel2abs("/", "/".$pageFile));
				else
					require(\Bitrix\Main\Application::getDocumentRoot()."/404.php");
				die();
			}
		}
	}
}
