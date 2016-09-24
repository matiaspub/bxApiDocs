<?php
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

class CBitrixServiceOAuthInterface extends CSocServOAuthTransport
{
	const SERVICE_ID = "bitrixgeneric";

	const AUTH_URL = "/oauth/authorize/";
	const TOKEN_URL = "/oauth/token/";

	const URL = '';

	protected $scope = array();

	protected $authResult = array();

	static public function __construct($appID = false, $appSecret = false, $code = false)
	{
		parent::__construct($appID, $appSecret, $code);
	}

	public function getResult()
	{
		return $this->authResult;
	}

	public function getError()
	{
		return is_array($this->authResult) && isset($this->authResult['error'])
			? $this->authResult
			: '';
	}
}

class CBitrixServiceTransport
{
	const SERVICE_URL = "/rest/";

	const METHOD_METHODS = 'methods';
	const METHOD_BATCH = 'batch';

	protected $clientId = '';
	protected $clientSecret = '';
	protected $httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;

	protected $serviceHost = '';

	public function __construct($clientId, $clientSecret)
	{
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
	}

	protected function setSeviceHost($host)
	{
		$this->serviceHost = $host;
	}

	protected function prepareAnswer($result)
	{
		return Json::decode($result);
	}

	public function call($methodName, $additionalParams = null, $licenseCheck = false)
	{
		global $APPLICATION;

		if($this->clientId && $this->clientSecret)
		{
			if(!is_array($additionalParams))
			{
				$additionalParams = array();
			}
			else
			{
				$additionalParams = $APPLICATION->ConvertCharsetArray($additionalParams, LANG_CHARSET, "utf-8");
			}

			$additionalParams['client_id'] = $this->clientId;
			$additionalParams['client_secret'] = $this->clientSecret;

			if($licenseCheck)
			{
				$additionalParams['key'] = static::getLicense();
			}

			$http = new HttpClient(array('socketTimeout' => $this->httpTimeout));
			$result = $http->post(
				$this->serviceHost.static::SERVICE_URL.$methodName,
				$additionalParams
			);

			$res = false;

			try
			{
				$res = $this->prepareAnswer($result);
			}
			catch(\Bitrix\Main\ArgumentException $e)
			{

			}

			if($res)
			{
				if(!$licenseCheck && is_array($res) && isset($res['error']) && $res['error'] === 'verification_needed')
				{
					return $this->call($methodName, $additionalParams, true);
				}
			}
			else
			{
				AddMessage2Log('Strange answer from Bitrix Service! '.$this->serviceHost.static::SERVICE_URL.$methodName.": ".$http->getStatus().' '.$result);
			}

			return $res;
		}
		else
		{
			throw new \Bitrix\Main\SystemException("No client credentials");
		}
	}

	public function batch($actions)
	{
		$batch = array();

		if(is_array($actions))
		{
			foreach($actions as $query_key => $arCmd)
			{
				list($cmd, $arParams) = array_values($arCmd);
				$batch['cmd'][$query_key] = $cmd.(is_array($arParams) ? '?'.http_build_query($arParams) : '');
			}
		}

		return $this->call(static::METHOD_BATCH, $batch);
	}

	public function getMethods()
	{
		return $this->call(self::METHOD_METHODS);
	}

	protected static function getLicense()
	{
		return md5(LICENSE_KEY);
	}
}