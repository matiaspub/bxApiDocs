<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo\Engine;

use Bitrix\Main\SystemException;
use Bitrix\Main\Text;
use Bitrix\Main\Web;
use Bitrix\Seo\Engine;

class YandexBase extends BitrixEngine
{
	const QUERY_USER = 'https://login.yandex.ru/info';

	protected $engineId = 'yandex_generic';

	/**
	 * Returns URL to authorize app
	 *
	 * @return string Url
	 */
	public function getAuthUrl()
	{
		return $this->getInterface()->getAuthUrl();
	}

	/**
	 * Creates OAuth interface object instance
	 *
	 * @return \CYandexOAuthInterface
	 */
	public function getInterface()
	{
		if($this->authInterface === null)
		{
			$this->authInterface = new \CYandexOAuthInterface($this->engine['CLIENT_ID'], $this->engine['CLIENT_SECRET']);

			if($this->engineSettings['AUTH'])
			{
				$this->authInterface->setToken($this->engineSettings['AUTH']['access_token']);
				$this->authInterface->setRefreshToken($this->engineSettings['AUTH']['refresh_token']);
				$this->authInterface->setAccessTokenExpires($this->engineSettings['AUTH']['expires_in']);
			}
		}

		return $this->authInterface;
	}

	public function clearSitesSettings()
	{
		unset($this->engineSettings['SITES']);
		$this->saveSettings();
	}

	public function setAuthSettings($settings = null)
	{
		if($settings === null)
		{
			$settings = $this->getInterface();
		}

		if($settings instanceof \CYandexOAuthInterface)
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

	public function checkAuthExpired()
	{
		$ob = $this->getInterface();
		return !$ob->checkAccessToken();
	}

	public function getAuth($code)
	{
		$ob = $this->getInterface();
		$ob->setCode($code);

		if($ob->getAccessToken())
		{
			unset($this->engineSettings['AUTH_USER']);

			$this->setAuthSettings();
			return true;
		}

		throw new \Exception($ob->getError());
	}

	/**
	 * Returns current Yandex user data
	 *
	 * @return array
	 *
	 * @throws SystemException
	 * @throws YandexException
	 */
	public function getCurrentUser()
	{
		if(
			!array_key_exists('AUTH_USER', $this->engineSettings)
			|| !is_array($this->engineSettings['AUTH_USER'])
		)
		{
			$queryResult = self::query(self::QUERY_USER);

			if($queryResult->getStatus() == self::HTTP_STATUS_OK && strlen($queryResult->getResult()) > 0)
			{
				$res = Web\Json::decode($queryResult->getResult());

				if(is_array($res))
				{
					$this->engineSettings['AUTH_USER'] = $res;
					$this->saveSettings();

					return $this->engineSettings['AUTH_USER'];
				}
			}

			throw new Engine\YandexException($queryResult);
		}
		else
		{
			return $this->engineSettings['AUTH_USER'];
		}
	}

	/**
	 * Returns HttpClient object with query result
	 *
	 * @param string $scope Url to call
	 * @param string $method HTTP method (GET/POST/PUT supported)
	 * @param array|null $data Post data
	 * @param bool $skipRefreshAuth Skip authorization refresh
	 *
	 * @returns \Bitrix\Main\Web\HttpClient
	 * @throws SystemException
	 */
	protected function query($scope, $method = "GET", $data = null, $skipRefreshAuth = false)
	{
		if($this->engineSettings['AUTH'])
		{
			$http = new Web\HttpClient();
			$http->setHeader('Authorization', 'OAuth '.$this->engineSettings['AUTH']['access_token']);
			$http->setRedirect(false);

			switch($method)
			{
				case 'GET':
					$http->get($scope);
				break;
				case 'POST':
					$http->post($scope, $data);
				break;
				case 'PUT':
					$http->query($method, $scope, $data);
				break;
				case 'DELETE':

				break;
			}

			if($http->getStatus() == 401 && !$skipRefreshAuth)
			{
				if($this->checkAuthExpired(false))
				{
					$this->query($scope, $method, $data, true);
				}
			}

			return $http;
		}
		else
		{
			throw new SystemException("No Yandex auth data");
		}
	}

	protected function prepareQueryResult(array $result)
	{
		return Text\Encoding::convertEncodingArray($result, 'utf-8', LANG_CHARSET);
	}
}
