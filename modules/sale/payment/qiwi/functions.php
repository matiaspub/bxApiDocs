<?php

use \Bitrix\Main\Application;

const QIWI_WALLET_ERROR_CODE_NONE 		= 0;
const QIWI_WALLET_ERROR_CODE_BAD_REQUEST = 5;
const QIWI_WALLET_ERROR_CODE_BUSY		= 13;
const QIWI_WALLET_ERROR_CODE_AUTH		= 150;
const QIWI_WALLET_ERROR_CODE_NOT_FOUND	= 210;
const QIWI_WALLET_ERROR_CODE_EXISTS		= 215;
const QIWI_WALLET_ERROR_CODE_TOO_LOW	= 241;
const QIWI_WALLET_ERROR_CODE_TOO_HIGH	= 242;
const QIWI_WALLET_ERROR_CODE_NO_PURSE	= 298;
const QIWI_WALLET_ERROR_CODE_OTHER		= 300;


function qiwiLog($data)
{
	file_put_contents(__DIR__ . "/log.txt", $data, FILE_APPEND);
}

if(!function_exists("qiwiWalletGetAuthHeader"))
{
	function qiwiWalletGetAuthHeader()
	{
		$incomingToken = false;
		if(isset($_SERVER["REMOTE_USER"]) && strlen($_SERVER["REMOTE_USER"]) > 0)
			$incomingToken = $_SERVER["REMOTE_USER"];
		elseif(isset($_SERVER["REDIRECT_REMOTE_USER"]) && strlen($_SERVER["REDIRECT_REMOTE_USER"]) > 0)
			$incomingToken = $_SERVER["REDIRECT_REMOTE_USER"];
		elseif(isset($_SERVER["HTTP_AUTHORIZATION"]) && strlen($_SERVER["HTTP_AUTHORIZATION"]) > 0)
			$incomingToken = $_SERVER["HTTP_AUTHORIZATION"];
		elseif(function_exists("apache_request_headers"))
		{
			$headers = \apache_request_headers();

			if(array_key_exists("Authorization", $headers))
				$incomingToken = $headers["Authorization"];
		}
		return $incomingToken;
	}
}

if(!function_exists("qiwiWalletCheckAuth"))
{
	function qiwiWalletCheckAuth($login, $password)
	{
		$header = qiwiWalletGetAuthHeader();
		
		if(!$header)
			return false;

		$check = "Basic " . base64_encode("{$login}:{$password}");
		return $header == $check;
	}
}

if(!function_exists("qiwiWalletXmlResponse"))
{
	function qiwiWalletXmlResponse($code)
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();
		header("Content-Type: text/xml");
		header("Pragma: no-cache");
		$xml = '<?xml version="1.0" encoding="UTF-8"?><result><result_code>' . $code . '</result_code></result>';
		$siteCharset = Application::getInstance()->getContext()->getCulture()->getCharset();
		print \CharsetConverter::getInstance()->ConvertCharset($xml, $siteCharset, "utf-8");
		die();
	}
}