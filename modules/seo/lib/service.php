<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Seo\Engine\Bitrix;

Loc::loadMessages(__FILE__);

if(!defined("BITRIX_CLOUD_ADV_URL"))
{
	// define("BITRIX_CLOUD_ADV_URL", 'https://cloud-adv.bitrix.info');
}

if(!defined("SEO_SERVICE_URL"))
{
	// define('SEO_SERVICE_URL', BITRIX_CLOUD_ADV_URL);
}

class Service
{
	const SERVICE_URL = SEO_SERVICE_URL;
	const REGISTER = "/oauth/register/";
	const AUTHORIZE = "/register/";
	const REDIRECT_URI = "/bitrix/tools/seo_client.php";

	protected static $engine = null;
	protected static $auth = null;

	public static function isRegistered()
	{
		return static::getEngine()->isRegistered();
	}

	public static function getAuth($engineCode)
	{
		global $CACHE_MANAGER;
		if(static::$auth === null)
		{
			$cache_id = 'seo|service_auth';

			if($CACHE_MANAGER->Read(86400, $cache_id))
			{
				static::$auth = $CACHE_MANAGER->Get($cache_id);
			}
			else
			{
				static::$auth = static::getEngine()->getInterface()->getClientInfo();
				$CACHE_MANAGER->Set($cache_id, static::$auth);
			}
		}

		if(static::$auth)
		{
			return static::$auth["engine"][$engineCode];
		}

		return false;
	}

	public static function clearLocalAuth()
	{
		global $CACHE_MANAGER;

		$cache_id = 'seo|service_auth';
		$CACHE_MANAGER->Clean($cache_id);

		static::$auth = null;
	}

	public static function clearAuth($engineCode, $localOnly = false)
	{
		static::clearLocalAuth();

		if(!$localOnly)
		{
			static::getEngine()->getInterface()->clearClientAuth($engineCode);
		}
	}

	protected static function setAccessSettings(array $accessParams)
	{
		if(static::isRegistered())
		{
			$id = static::getEngine()->getId();

			$result = SearchEngineTable::update($id, array(
				"CLIENT_ID" => $accessParams["client_id"],
				"CLIENT_SECRET" => $accessParams["client_secret"],
				"SETTINGS" => "",
			));
		}
		else
		{
			$result = SearchEngineTable::add(array(
				"CODE" => Bitrix::ENGINE_ID,
				"NAME" => "Bitrix",
				"ACTIVE" => SearchEngineTable::ACTIVE,
				"CLIENT_ID" => $accessParams["client_id"],
				"CLIENT_SECRET" => $accessParams["client_secret"],
				"REDIRECT_URI" => static::getRedirectUri(),
			));
		}

		if($result->isSuccess())
		{
			static::clearAuth(Bitrix::ENGINE_ID, true);
			static::$engine = null;
		}
	}

	/**
	 * @return \Bitrix\Seo\Engine\Bitrix
	 */
	public static function getEngine()
	{
		if(!static::$engine && Loader::includeModule("socialservices"))
		{
			static::$engine = new Bitrix();
		}

		return static::$engine;
	}

	public static function register()
	{
		static::clearLocalAuth();

		$httpClient = new HttpClient();

		$queryParams = array(
			"key" => static::getLicense(),
			"scope" => static::getEngine()->getInterface()->getScopeEncode(),
			"redirect_uri" => static::getRedirectUri(),
		);

		$result = $httpClient->post(static::SERVICE_URL.static::REGISTER, $queryParams);
		$result = Json::decode($result);

		if($result["error"])
		{
			throw new SystemException($result["error"]);
		}
		else
		{
			static::setAccessSettings($result);
		}
	}

	public static function unregister()
	{
		if(static::isRegistered())
		{
			$id = static::getEngine()->getId();
			SearchEngineTable::delete($id);
			static::clearLocalAuth();
		}
	}

	public static function getAuthorizeLink()
	{
		return static::SERVICE_URL.static::AUTHORIZE;
	}

	public static function getAuthorizeData($engine)
	{
		$checkKey = "";
		if(Loader::includeModule("socialservices"))
		{
			$checkKey = \CSocServAuthManager::GetUniqueKey();
		}

		return array(
			"action" => "authorize",
			"engine" => $engine,
			"client_id" => static::getEngine()->getClientId(),
			"client_secret" => static::getEngine()->getClientSecret(),
			"key" => static::getLicense(),
			"check_key" => urlencode($checkKey)
		);
	}

	protected static function getRedirectUri()
	{
		$request = Context::getCurrent()->getRequest();

		$host = $request->getHttpHost();
		$isHttps = $request->isHttps();

		return ($isHttps ? 'https' : 'http').'://'.$host.static::REDIRECT_URI;
	}

	protected static function getLicense()
	{
		return md5(LICENSE_KEY);
	}
}