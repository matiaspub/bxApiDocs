<?php
namespace Bitrix\Main\Analytics;

use Bitrix\Main\Context;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetLocation;
use Bitrix\Main\Text\JsExpression;

class Counter
{
	protected static $data = array();
	protected static $enabled = true;

	public static function enable()
	{
		static::$enabled = true;
	}

	public static function disable()
	{
		static::$enabled = false;
	}

	public static function getInjectedJs($stripTags = false)
	{
		$accountId = static::getAccountId();
		$params = static::injectDataParams();

		$host = Context::getCurrent()->getServer()->getHttpHost();
		$host = preg_replace("/:(80|443)$/", "", $host);
		$host = \CUtil::JSEscape($host);

		$js = <<<JS
			var _ba = _ba || []; _ba.push(["aid", "{$accountId}"]); _ba.push(["host", "{$host}"]); {$params}
			(function() {
				var ba = document.createElement("script"); ba.type = "text/javascript"; ba.async = true;
				ba.src = (document.location.protocol == "https:" ? "https://" : "http://") + "bitrix.info/ba.js";
				var s = document.getElementsByTagName("script")[0];
				s.parentNode.insertBefore(ba, s);
			})();
JS;

		$js = str_replace(array("\n", "\t"), "", $js);
		if ($stripTags === false)
		{
			return "<script type=\"text/javascript\">".$js."</script>";
		}
		else
		{
			return $js;
		}
	}

	public static function injectIntoPage()
	{
		Asset::getInstance()->addString(static::getInjectedJs(), false, AssetLocation::AFTER_JS);
	}

	public static function getAccountId()
	{
		if (defined("LICENSE_KEY"))
		{
			return md5("BITRIX".LICENSE_KEY."LICENCE");
		}
		else
		{
			return "";
		}
	}

	public static function getPrivateKey()
	{
		if (defined("LICENSE_KEY"))
		{
			return md5(LICENSE_KEY);
		}
		else
		{
			return "";
		}
	}

	public static function onBeforeEndBufferContent()
	{
		$server = Context::getCurrent()->getServer();
		$ajax = $server->get("HTTP_BX_AJAX");

		if (SiteSpeed::isOn() &&
			static::$enabled === true &&
			$ajax === null &&
			(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
		)
		{
			Counter::injectIntoPage();
		}
	}

	public static function onBeforeRestartBuffer()
	{
		static::disable();
	}

	public static function sendData($id, array $arParams)
	{
		static::$data[$id] = $arParams;
	}

	private static function injectDataParams()
	{
		$result = "";
		foreach (static::$data as $index => $arItem)
		{
			foreach ($arItem as $key => $value)
			{
				if (is_array($value))
				{
					$jsValue = '"'.\CUtil::PhpToJSObject($value).'"';
				}
				elseif ($value instanceof JsExpression)
				{
					$jsValue = $value;
				}
				else
				{
					$jsValue = '"'.\CUtil::JSEscape($value).'"';
				}

				$result .= '_ba.push(["ad['.$index.']['.\CUtil::JSEscape($key).']", '.$jsValue.']);';
			}
		}

		return $result;
	}
}