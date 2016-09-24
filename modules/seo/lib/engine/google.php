<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo\Engine;

use Bitrix\Main\Web\HttpClient;
use Bitrix\Seo\Engine;
use Bitrix\Seo\IEngine;
use Bitrix\Main\Text\Converter;
use Bitrix\Main\Web\Json;

class Google extends Engine implements IEngine
{
	const ENGINE_ID = 'google';
	const SCOPE_BASE = 'https://www.googleapis.com/auth/webmasters';
	const SCOPE_USER = 'https://www.googleapis.com/auth/userinfo.profile';
	const SCOPE_VERIFY = 'https://www.googleapis.com/auth/siteverification.verify_only';

	const SCOPE_FEED_SITES = 'sites';
	const SCOPE_FEED_CRAWLISSUES = 'crawlissues/';
	const SCOPE_FEED_MESSAGES = 'messages/';

	const SCOPE_DOMAIN_PROTOCOL = 'http://';

	const QUERY_BASE = 'https://www.googleapis.com/webmasters/v3/';

	const QUERY_USER = 'https://www.googleapis.com/oauth2/v3/userinfo';
	const QUERY_VERIFY = 'https://www.googleapis.com/siteVerification/v1/webResource?verificationMethod=FILE';
	const QUERY_VERIFY_TOKEN = 'https://www.googleapis.com/siteVerification/v1/token';

	protected $engineId = 'google';
	protected $scope = null;

	public function getScope()
	{
/*
		if(!is_array($this->scope))
		{
			$arDomains = \CSeoUtils::getDomainsList();
			$this->scope = array(
				self::SCOPE_USER,
				self::SCOPE_BASE,
				self::SCOPE_VERIFY,
			);

			foreach ($arDomains as $arDomain)
			{
				$this->scope[] = $this->getSiteId($arDomain['DOMAIN'], $arDomain['SITE_ID']);
			}
		}
*/

		return array(
			self::SCOPE_USER,
			self::SCOPE_BASE,
			self::SCOPE_VERIFY,
		);
	}

	public function getAuthUrl()
	{
		return $this->getInterface()->getAuthUrl($this->engine['REDIRECT_URI']);
	}

	public function getInterface()
	{
		if($this->authInterface === null)
		{
			$this->authInterface = new \CGoogleOAuthInterface($this->engine['CLIENT_ID'], $this->engine['CLIENT_SECRET']);
			$this->authInterface->setScope($this->getScope());

			if($this->engineSettings['AUTH'])
			{
				$this->authInterface->setToken($this->engineSettings['AUTH']['access_token']);
				$this->authInterface->setRefreshToken($this->engineSettings['AUTH']['refresh_token']);
				$this->authInterface->setAccessTokenExpires($this->engineSettings['AUTH']['expires_in']);
			}
		}

		return $this->authInterface;
	}

	public function setAuthSettings($settings = null)
	{
		if($settings === null)
		{
			$settings = $this->getInterface();
		}

		if($settings instanceof \CGoogleOAuthInterface)
		{
			$settings = array(
				'access_token' => $settings->getToken(),
				'refresh_token' => $settings->getRefreshToken(),
				'expires_in' => $settings->getAccessTokenExpires()
			);
		}

		$this->engineSettings['AUTH'] = $settings;
		$this->saveSettings();
	}

	public function checkAuthExpired($bGetNew)
	{
		$ob = $this->getInterface();
		if(!$ob->checkAccessToken())
		{
			return $bGetNew ? $this->refreshAuth() : false;
		}
		return true;
	}

	public function refreshAuth()
	{
		$ob = $this->getInterface();
		if($ob->getNewAccessToken())
		{
			$this->setAuthSettings();
			return true;
		}

		throw new \Exception($ob->getError());
	}

	public function getAuth($code)
	{
		$ob = $this->getInterface();
		$ob->setCode($code);

		if($ob->getAccessToken($this->engine['REDIRECT_URI']))
		{
			unset($this->engineSettings['AUTH_USER']);

			$this->setAuthSettings();
			return true;
		}

		throw new \Exception($ob->getError());
	}

	public function getCurrentUser()
	{
		global $APPLICATION;

		if(!isset($this->engineSettings['AUTH_USER']) || !is_array($this->engineSettings['AUTH_USER']))
		{
			$queryResult = $this->queryJson(self::QUERY_USER);

			if(!$queryResult)
			{
				return false;
			}

			if($queryResult->getStatus() == self::HTTP_STATUS_OK && strlen($queryResult->getResult()) > 0)
			{
				$res = Json::decode($queryResult->getResult());
				if(is_array($res))
				{
					$this->engineSettings['AUTH_USER'] = $APPLICATION->convertCharsetArray($res, 'utf-8', LANG_CHARSET);
					$this->saveSettings();

					return $this->engineSettings['AUTH_USER'];
				}
			}

			throw new \Exception('Query error! '.$queryResult->getStatus().': '.$queryResult->getResult());
		}
		else
		{
			return $this->engineSettings['AUTH_USER'];
		}
	}

	public function getFeeds()
	{
		$queryResult = $this->queryJson(self::QUERY_BASE.self::SCOPE_FEED_SITES);
		if($queryResult->getStatus() == self::HTTP_STATUS_OK && strlen($queryResult->getResult()) > 0)
		{
			$result = Json::decode($queryResult->getResult());
			$response = array();
			if(is_array($result))
			{
				foreach($result['siteEntry'] as $key => $siteInfo)
				{
					$siteUrlInfo = parse_url($siteInfo['siteUrl']);
					if($siteUrlInfo)
					{
						$errors = array();
						$hostKey = \CBXPunycode::toASCII($siteUrlInfo["host"], $errors);
						if(count($errors) > 0)
						{
							$hostKey = $siteUrlInfo["host"];
						}

						$response[$hostKey] = array(
							'binded' => $siteInfo["permissionLevel"] !== "siteRestrictedUser",
							'verified' => $siteInfo["permissionLevel"] !== "siteRestrictedUser"
								&& $siteInfo["permissionLevel"] !== "siteUnverifiedUser",
						);
					}
				}
			}

			return $response;
		}
		else
		{
			throw new \Exception('Query error! '.$queryResult->getStatus().': '.$queryResult->getResult());
		}
	}

	public function addSite($domain, $dir = '/')
	{
		$queryResult = $this->queryJson(self::QUERY_BASE.self::SCOPE_FEED_SITES."/".$domain, "PUT");

		if(!$queryResult)
		{
			return false;
		}

		if($queryResult->getStatus() == self::HTTP_STATUS_NO_CONTENT)
		{
			return $this->getFeeds();
		}
		else
		{
			throw new \Exception('Query error! '.$queryResult->getStatus().': '.$queryResult->getResult());
		}
	}

	public function verifyGetToken($domain, $dir)
	{
		$data = array(
			"verificationMethod" => "FILE",
			"site" => array(
				"identifier" => self::SCOPE_DOMAIN_PROTOCOL.$domain.$dir,
				"type" => "SITE"
			)
		);

		$queryResult = $this->queryJson(
			static::QUERY_VERIFY_TOKEN,
			"POST",
			Json::encode($data)
		);

		if(!$queryResult)
		{
			return false;
		}

		if($queryResult->getStatus() == self::HTTP_STATUS_OK && strlen($queryResult->getResult()) > 0)
		{
			$result = Json::decode($queryResult->getResult());
			return $result["token"];
		}
		else
		{
			throw new \Exception('Query error! '.$queryResult->getStatus().': '.$queryResult->getResult());
		}
	}

	public function verifySite($domain, $dir)
	{
		$data = array(
			"site" => array(
				"identifier" => self::SCOPE_DOMAIN_PROTOCOL.$domain.$dir,
				"type" => "SITE"
			)
		);

		$queryResult = $this->queryJson(
			self::QUERY_VERIFY,
			"POST",
			Json::encode($data)
		);

		if(!$queryResult)
		{
			return false;
		}

		if($queryResult->getStatus() == self::HTTP_STATUS_OK && strlen($queryResult->getResult()) > 0)
		{
			return true;
		}
		else
		{
			throw new \Exception('Query error! '.$queryResult->getStatus().': '.$queryResult->getResult());
		}
	}


	protected function queryJson($scope, $method = "GET", $data = null, $bSkipRefreshAuth = false)
	{
		return $this->query($scope, $method, $data, $bSkipRefreshAuth, 'application/json');
	}

	protected function query($scope, $method = "GET", $data = null, $bSkipRefreshAuth = false, $contentType = 'application/json')
	{
		if($this->engineSettings['AUTH'])
		{
			$http = new HttpClient();
			$http->setHeader("Authorization", 'Bearer '.$this->engineSettings['AUTH']['access_token']);

/*
			$http->setAdditionalHeaders(
				array(
					'Authorization' => ,
					'GData-Version' => '2'
				)
			);
*/

			switch($method)
			{
				case 'GET':
					$result = $http->get($scope);
				break;
				case 'POST':
				case 'PUT':
					$http->setHeader("Content-Type", $contentType);

					if(!$data)
					{
						$http->setHeader("Content-Length", 0);
					}

					$result = $http->query($method, $scope, $data);

				break;
				case 'DELETE':

				break;
			}

			if($http->getStatus() == 401 && !$bSkipRefreshAuth)
			{
				if($this->checkAuthExpired(true))
				{
					return $this->query($scope, $method, $data, true, $contentType);
				}
			}

			return $http;
		}
	}
}
?>