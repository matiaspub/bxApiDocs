<?
class CCrmExternalSaleProxy
{
	private $externalSaleId = 0;

	private $scheme = 'http';
	private $server = '';
	private $port = 80;
	private $userName = '';
	private $userPassword = '';

	private $enableProxy = false;

	private $proxyScheme = '';
	private $proxyServer = '';
	private $proxyPort = '';
	private $proxyUserName = '';
	private $proxyUserPassword = '';

	private $userAgent = 'BitrixCRM client';
	/** @var \Bitrix\Main\Web\HttpClient */
	private $client = null;
	private $responseData = null;
	private $cookies = array();
	private $errors = array();

	private $isInitialized = false;

	public function __construct($saleId)
	{
		$this->externalSaleId = intval($saleId);
		$dbResult = CCrmExternalSale::GetList(array(), array("ID" => $this->externalSaleId, "ACTIVE" => "Y"));
		if ($arResult = $dbResult->Fetch())
		{
			$scheme = isset($arResult['SCHEME']) ? strtolower($arResult['SCHEME']) : '';
			$this->scheme = $scheme === 'https' ? 'https' : 'http';
			$this->server = isset($arResult['SERVER']) ? $arResult['SERVER'] : '';
			$this->port = isset($arResult['PORT']) ? intval($arResult['PORT']) : 80;
			$this->userName = isset($arResult['LOGIN']) ? $arResult['LOGIN'] : '';
			$this->userPassword = isset($arResult['PASSWORD']) ? $arResult['PASSWORD'] : '';

			if (isset($arResult['COOKIE']) && !empty($arResult['COOKIE']))
			{
				$cookies = unserialize($arResult['COOKIE']);
				$this->cookies = is_array($cookies) ? $cookies : array();
			}
		}
		else
		{
			$this->AddError('PA1', 'External site is not found');
		}

		$proxySettings = CCrmExternalSale::GetProxySettings();
		if (is_array($proxySettings) && isset($proxySettings['PROXY_HOST']) && $proxySettings['PROXY_HOST'] !== '')
		{
			$this->proxyServer = $proxySettings['PROXY_HOST'];
			$scheme = isset($proxySettings['PROXY_SCHEME']) ? strtolower($proxySettings['PROXY_SCHEME']) : '';
			$this->proxyScheme = $scheme === 'https' ? 'https' : 'http';
			$this->proxyPort = isset($proxySettings['PROXY_PORT']) ? intval($proxySettings['PROXY_PORT']) : 80;
			$this->proxyUserName = isset($proxySettings['PROXY_USERNAME']) ? $proxySettings['PROXY_USERNAME'] : '';
			$this->proxyUserPassword = isset($proxySettings['PROXY_PASSWORD']) ? $proxySettings['PROXY_PASSWORD'] : '';
			$this->enableProxy = true;
		}

		$this->isInitialized = $this->server !== '';
	}
	public function IsInitialized()
	{
		return $this->isInitialized;
	}
	private function AddError($code, $message)
	{
		$this->errors[] = array($code, $message);
	}
	public function GetErrors()
	{
		return $this->errors;
	}
	public function GetUrl()
	{
		return $this->scheme.'://'.$this->server.(($this->port > 0 && $this->port !== 80) ? ':'.strval($this->port) : '');
	}
	public function Send(array $request)
	{
		$method = isset($request['METHOD']) ? strtoupper($request['METHOD']) : '';
		if($method !== \Bitrix\Main\Web\HttpClient::HTTP_GET && $method !== \Bitrix\Main\Web\HttpClient::HTTP_POST)
		{
			throw new Bitrix\Main\ArgumentException("Could not find 'METHOD'.", 'request');
		}

		$path = isset($request['PATH']) && is_string($request['PATH']) ? $request['PATH'] : '';
		if($path === '')
		{
			throw new Bitrix\Main\ArgumentException("Could not find 'PATH'.", 'request');
		}

		$postData = $method === \Bitrix\Main\Web\HttpClient::HTTP_POST
			&& isset($request['BODY'])
			? $request['BODY'] : null;

		if(!$this->client)
		{
			$this->client = new \Bitrix\Main\Web\HttpClient();
		}

		if($method === \Bitrix\Main\Web\HttpClient::HTTP_POST && is_array($postData))
		{
			//Force UTF encoding
			$this->client->setCharset('UTF-8');
			if ((!isset($request['UTF']) || !$request['UTF']) && !defined('BX_UTF'))
			{
				$postData = \Bitrix\Main\Text\Encoding::convertEncodingArray($postData, SITE_CHARSET, 'UTF-8');
			}
		}

		$headers = isset($request['HEADERS']) ? $request['HEADERS'] : null;
		if(is_array($headers))
		{
			foreach($headers as $k => $v)
			{
				$this->client->setHeader($k, $v, true);
			}
		}

		if(!empty($this->cookies))
		{
			$this->client->setCookies($this->cookies);
		}

		if($this->enableProxy)
		{
			$this->client->setProxy($this->proxyServer, $this->proxyPort, $this->proxyUserName, $this->proxyUserPassword);
		}

		if($this->userName !== '')
		{
			$this->client->setAuthorization($this->userName, $this->userPassword);
		}

		$this->client->setHeader('User-Agent', $this->userAgent, true);

		$absolutePath = $this->GetUrl().$path;
		if(!$this->client->query($method, $absolutePath, $postData))
		{
			$this->responseData = null;
			$this->errors = $this->client->getError();
		}
		else
		{
			/**@var \Bitrix\Main\Web\HttpHeaders*/
			$responseHeaders = $this->client->getHeaders();

			//STATUS.VERSION & STATUS.PHRASE are delcared for backward compatibility only.
			$this->responseData = array(
				'STATUS' => array(
					'VERSION' => '',
					'CODE' => $this->client->getStatus(),
					'PHRASE' => ''
				),
				'CONTENT' => array(
					'TYPE' => $this->client->getContentType(),
					'ENCODING' => $this->client->getCharset()
				),
				'HEADERS' => $responseHeaders,
				'BODY' => $this->client->getResult()
			);

			if($responseHeaders->get('Set-Cookie', false) !== null)
			{
				$this->cookies = array_merge($this->cookies, $this->client->getCookies()->toArray());
				CCrmExternalSale::Update($this->externalSaleId, array('COOKIE' => serialize($this->cookies)));
			}

			$this->errors = array();
		}
		return $this->responseData;
	}
	public function Connect()
	{
		//Stub. For backward compatibility only.
		return true;
	}
	public function Disconnect()
	{
		//Stub. For backward compatibility only.
		return true;
	}
	public function GetVersion()
	{
		return '2';
	}
}
