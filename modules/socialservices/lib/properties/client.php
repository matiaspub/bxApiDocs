<?

namespace Bitrix\Socialservices\Properties;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Context;

Loc::loadMessages(__FILE__);

class Client
{
	const SERVICE_HOST = 'https://properties.bitrix.info';
	const REST_URI = '/rest/';
	const REGISTER_URI = '/oauth/register/';
	const SCOPE = 'ps';
	const SERVICE_ACCESS_OPTION = 'properties_service_access';
	const METHOD_COMMON_GET_BY_INN = 'ps.common.getByInn';
	const METHOD_COMMON_GET_BY_OGRN = 'ps.common.getByOgrn';
	const METHOD_ORGANIZATION_SEARCH_BY_NAME = 'ps.organization.searchByName';
	const METHOD_IP_SEARCH_BY_NAME = 'ps.ip.searchByName';
	const METHOD_IS_SERVICE_ONLINE = 'ps.common.isOnline';
	const ERROR_WRONG_INPUT = 1;
	const ERROR_WRONG_LICENSE = 2;
	const ERROR_SERVICE_UNAVAILABLE = 3;
	const ERROR_NOTHING_FOUND = 4;

	protected $httpTimeout = 5;
	protected $accessSettings = null;

	/** @var ErrorCollection */
	protected $errorCollection;

	/**
	 * Constructor of the client of the properties service.
	 */
	public function __construct()
	{
		$this->errorCollection = new ErrorCollection();
	}

	/**
	 * Returns properties of the organization or individual businessman by its OGRN code.
	 * @param string $ogrn OGRN code of the organization or individual businessman.
	 * @return array|false
	 */
	public function getByOgrn($ogrn)
	{
		return $this->call(static::METHOD_COMMON_GET_BY_OGRN, array('ogrn' => $ogrn));
	}

	/**
	 * Returns properties of the organization or individual businessman by its INN code.
	 * @param string $inn INN code of the organization or individual businessman.
	 * @return array|false
	 */
	public function getByInn($inn)
	{
		return $this->call(static::METHOD_COMMON_GET_BY_INN, array('inn' => $inn));
	}

	/**
	 * Performs search of organizations by name and returns array of found organizations.
	 * @param string $name Part of the organization's name.
	 * @param int $limit Maximum size of returning array.
	 * @param int $offset Offset of the returning array.
	 * @return array|false Array of found organizations or false in case of error.
	 */
	public function searchOrganizationByName($name, $limit, $offset = 0)
	{
		return $this->call(static::METHOD_ORGANIZATION_SEARCH_BY_NAME, array(
			'name' => $name,
			'limit' => $limit,
			'offset' => $offset
		));
	}

	/**
	 * Performs search of individual businessmen by name and returns array of found businessmen.
	 * @param string $name First name (full or partial).
	 * @param string $secondName Second name (full or partial).
	 * @param string $lastName Last name (full or partial).
	 * @param int $limit Maximum size of returning array.
	 * @param int $offset Offset of the returning array.
	 * @return array|false Array of found businessmen or false in case of error.
	 */
	public function searchIpByName($name, $secondName, $lastName, $limit, $offset = 0)
	{
		return $this->call(static::METHOD_IP_SEARCH_BY_NAME, array(
			'name' => $name,
			'second_name' => $secondName,
			'last_name' => $lastName,
			'limit' => $limit,
			'offset' => $offset
		));
	}

	/**
	 * Checks service's availability.
	 * @return bool Returns true if service is ready and false otherwise.
	 */
	public function isServiceOnline()
	{
		return $this->call(static::METHOD_IS_SERVICE_ONLINE);
	}

	/**
	 * Performs call to the REST method and returns decoded results of the call.
	 * @param string $methodName Name of the REST method.
	 * @param array $additionalParams Parameters, that should be passed to the method.
	 * @param bool $licenseCheck Should client send license key as a parameter of the http request.
	 * @param bool $clearAccessSettings Should client clear authorization before performing http request.
	 * @return array|false
	 */
	protected function call($methodName, $additionalParams = null, $licenseCheck = false, $clearAccessSettings = false)
	{
		$this->errorCollection->clear();

		if($clearAccessSettings)
			$this->clearAccessSettings();

		if(is_null($this->accessSettings))
			$this->accessSettings = $this->getAccessSettings();

		if($this->accessSettings === false)
			return false;

		if(!is_array($additionalParams))
		{
			$additionalParams = array();
		}
		else
		{
			$additionalParams = Encoding::convertEncodingArray($additionalParams, LANG_CHARSET, "utf-8");
		}

		$additionalParams['client_id'] = $this->accessSettings['client_id'];
		$additionalParams['client_secret'] = $this->accessSettings['client_secret'];
		if($licenseCheck)
			$additionalParams['key'] = static::getLicenseHash();

		$http = new HttpClient(array('socketTimeout' => $this->httpTimeout));
		$result = $http->post(
				static::SERVICE_HOST.static::REST_URI.$methodName,
				$additionalParams
		);

		$answer = $this->prepareAnswer($result);

		if(!is_array($answer) || count($answer) == 0)
		{
			$this->errorCollection->add(array(new Error('Malformed answer from service: '.$http->getStatus().' '.$result, static::ERROR_SERVICE_UNAVAILABLE)));
			return false;
		}

		if(array_key_exists('error', $answer))
		{
			if($answer['error'] === 'verification_needed' && !$licenseCheck)
			{
				return $this->call($methodName, $additionalParams, true);
			}
			else if(($answer['error'] === 'ACCESS_DENIED' || $answer['error'] === 'Invalid client')
					&& !$clearAccessSettings)
			{
				return $this->call($methodName, $additionalParams, true, true);
			}

			$this->errorCollection->add(array(new Error($answer['error'].". ".$answer['error_description'])));
			return false;
		}

		if($answer['result'] == false)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('SALE_PROPERTIES_ERROR_NOTHING_FOUND'), static::ERROR_NOTHING_FOUND)));
		}

		return $answer['result'];
	}

	/**
	 * Decodes answer of the method.
	 * @param string $result Json-encoded answer.
	 * @return array|bool|mixed|string Decoded answer.
	 */
	protected function prepareAnswer($result)
	{
		return Json::decode($result);
	}

	/**
	 * Registers client on the properties service.
	 * @return array|false Access credentials if registration was successful or false otherwise.
	 */
	protected function register()
	{
		$httpClient = new HttpClient();

		$queryParams = array(
				"key" => static::getLicenseHash(),
				"scope" => static::SCOPE,
				"redirect_uri" => static::getRedirectUri(),
		);

		$result = $httpClient->post(static::SERVICE_HOST.static::REGISTER_URI, $queryParams);

		if($result === false)
		{
			$this->errorCollection->add(array(new Error($result["error"], static::ERROR_SERVICE_UNAVAILABLE)));
			return false;
		}

		$result = Json::decode($result);
		if($result["error"])
		{
			$this->errorCollection->add(array(new Error($result["error"], static::ERROR_WRONG_LICENSE)));
			return false;
		}
		else
		{
			return $result;
		}
	}

	/**
	 * Stores access credentials.
	 * @param array $params Access credentials.
	 * @return void
	 */
	protected static function setAccessSettings(array $params)
	{
		Option::set('sale', static::SERVICE_ACCESS_OPTION, serialize($params));
	}

	/**
	 * Reads and returns access credentials.
	 * @return array|false Access credentials or false in case of errors.
	 */
	protected function getAccessSettings()
	{
		$accessSettings = Option::get('sale', static::SERVICE_ACCESS_OPTION);

		if($accessSettings != '')
		{
			return unserialize($accessSettings);
		}
		else
		{
			if($accessSettings = $this->register())
			{
				$this->setAccessSettings($accessSettings);
				return $accessSettings;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Drops current stored access credentials.
	 * @return void
	 */
	static public function clearAccessSettings()
	{
		Option::set('sale', static::SERVICE_ACCESS_OPTION, null);
	}

	/**
	 * Internal method for usage in registration process.
	 * @return string URL of the host.
	 */
	protected static function getRedirectUri()
	{
		$request = Context::getCurrent()->getRequest();

		$host = $request->getHttpHost();
		$isHttps = $request->isHttps();

		return ($isHttps ? 'https' : 'http').'://'.$host."/";
	}

	/**
	 * Returns array of errors.
	 * @return Error[] Errors.
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Returns md5 hash of the license key.
	 * @return string md5 hash of the license key.
	 */
	protected static function getLicenseHash()
	{
		return md5(LICENSE_KEY);
	}
}