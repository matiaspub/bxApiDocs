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
	// define("BITRIX_CLOUD_ADV_URL", 'http://cloud-adv.bitrix.info');
}

if(!defined("SEO_SERVICE_URL"))
{
	// define('SEO_SERVICE_URL', BITRIX_CLOUD_ADV_URL);
}

class Service
{
	const OPT_ACCESS = "access_params";

	const SERVICE_URL = SEO_SERVICE_URL;
	const REGISTER = "/register/";
	const AUTHORIZE = "/oauth/authorize/";

	protected static $engine = null;
	protected static $auth = null;

	public static function isRegistered()
	{
		return static::getEngine()->isRegistered();
	}

	public static function isAuthorized($engineCode = null)
	{
		if($engineCode === null)
		{
			$authSettings = static::getEngine()->getAuthSettings();
			return is_array($authSettings) && $authSettings["access_token"];
		}

		return static::isRegistered() && static::getAuth($engineCode);
	}

	public static function getAuth($engineCode)
	{
		global $CACHE_MANAGER;

		if(static::$auth === null)
		{
			$cache_id = 'seo|service_auth';

			if ($CACHE_MANAGER->Read(86400, $cache_id))
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

	public static function clearAuth($engineCode, $localOnly = false)
	{
		global $CACHE_MANAGER;

		$cache_id = 'seo|service_auth';
		$CACHE_MANAGER->Clean($cache_id);

		static::$auth = null;

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
		$httpClient = new HttpClient();

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");

		$queryParams = array(
			"action" => "register",
			"key" => md5(\CUpdateClient::GetLicenseKey()),
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

	public static function authorize()
	{
		\CSocServAuthManager::SetUniqueKey();

		$redirectUri = static::getRedirectUri();

		$url = static::getEngine()->getInterface()->GetAuthUrl(
			$redirectUri,
			"backurl=".urlencode($redirectUri.'?check_key='.$_SESSION["UNIQUE_KEY"])
		);

		$httpClient = new HttpClient(array(
			"redirect" => false,
		));

		$result = $httpClient->get($url);

		if($httpClient->getStatus() == 302)
		{
			return array(
				"location" => $httpClient->getHeaders()->get("Location"),
			);
		}

		throw new SystemException("Wrong response: ".$result);
	}


	public static function getAuthorizeLink($engine)
	{
		$interface = static::getEngine()->getInterface();
		if(!$interface->checkAccessToken())
		{
			if($interface->getNewAccessToken())
			{
				static::getEngine()->setAuthSettings($interface->getResult());
			}
		}

		$checkKey = "";
		if(Loader::includeModule("socialservices"))
		{
			$checkKey = \CSocServAuthManager::GetUniqueKey();
		}

		return static::SERVICE_URL.static::REGISTER."?action=authorize&engine=".urlencode($engine)."&client_id=".urlencode(static::getEngine()->getClientId())."&auth=".urlencode($interface->getToken())."&check_key=".urlencode($checkKey);
	}

	protected static function getRedirectUri()
	{
		$request = Context::getCurrent()->getRequest();

		$host = $request->getHttpHost();
		$isHttps = $request->isHttps();

		return ($isHttps ? 'https' : 'http').'://'.$host."/bitrix/tools/seo_client.php";
	}
}