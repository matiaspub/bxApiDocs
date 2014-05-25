<?php
/**
* Bitrix Framework
* @package bitrix
* @subpackage security
* @copyright 2001-2013 Bitrix
*/

use Bitrix\Main\Application;

/**
 * Class CSecurityJsonHelper
 * @since 14.0.4
 */
class CSecurityJsonHelper
{
	/**
	 * @param mixed $content
	 * @param int $options - default is JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	 * @return string
	 */
	public static function encode($content, $options = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		if (!Application::getInstance()->isUtfMode())
			$content = $APPLICATION->convertCharsetarray($content, SITE_CHARSET, 'UTF-8');

		if ($options === null)
			$options = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

		return json_encode($content, $options);
	}
}