<?

class CSecurityCloudMonitorRequest
{
	const BITRIX_CHECKER_URL_PATH = "/bitrix/site_checker.php";
	const REMOTE_STATUS_OK = "ok";
	const REMOTE_STATUS_ERROR = "error";
	const REMOTE_STATUS_FATAL_ERROR = "fatal_error";
	const CONNECTION_TIMEOUT = 10;

	private static $mValidActions = array("check", "get_results");
	protected $response = array();
	protected $checkingToken = "";

	public function __construct($pAction, $pToken = "")
	{
		if(!in_array($pAction, self::$mValidActions))
			return null;
		$this->checkingToken = $pToken;
		$this->response = $this->receiveData($pAction);
	}

	/**
	 * @param string $pCheckingToken
	 */
	public function setCheckingToken($pCheckingToken)
	{
		$this->checkingToken = $pCheckingToken;
	}

	/**
	 * @return string
	 */
	public function getCheckingToken()
	{
		return $this->checkingToken;
	}

	/**
	 * Make a request to the Bitrix server and returns the result
	 * @param array $pAction
	 * @return array|bool
	 */
	public function receiveData($pAction)
	{
		$payload = $this->getPayload($pAction, false);
		if(!$payload)
			return false;

		$response = self::sendRequest($payload);
		if($response)
		{
			if(isset($response["new_spd"]))
			{
				CUpdateClient::setSpd($response["new_spd"]);
			}
		}
		else
		{
			$response = array();
		}

		if(!isset($response["status"]))
		{
			$response["status"] = self::REMOTE_STATUS_FATAL_ERROR;
			$response["error_text"] = GetMessage("SECURITY_SITE_CHECKER_CONNECTION_ERROR");
		}

		return $response;
	}

	/**
	 * @return bool
	 */
	public function isOk()
	{
		return 	$this->checkStatus(self::REMOTE_STATUS_OK);
	}

	/**
	 * @return bool
	 */
	public function isFatalError()
	{
		return 	$this->checkStatus(self::REMOTE_STATUS_FATAL_ERROR);
	}

	/**
	 * @return bool
	 */
	public function isError()
	{
		return 	$this->checkStatus(self::REMOTE_STATUS_ERROR);
	}

	/**
	 * @return bool
	 */
	public function isSuccess()
	{
		return 	(isset($this->response["status"]));
	}

	/**
	 * @param string $pkey
	 * @return string
	 */
	public function getValue($pkey)
	{
		if(isset($this->response[$pkey]))
		{
			return $this->response[$pkey];
		}
		else
		{
			return "";
		}
	}

	/**
	 * @param string $pStatus
	 * @return bool
	 */
	protected function checkStatus($pStatus)
	{
		return 	(isset($this->response["status"]) && $this->response["status"] === $pStatus);
	}

	/**
	 * Generate payload for request to Bitrix
	 * @param string $pAction - "check" or "receive_results"
	 * @param bool $pCollectSystemInformation
	 * @return string
	 */
	protected function getPayload($pAction = "check", $pCollectSystemInformation = true)
	{
		if(!in_array($pAction, self::$mValidActions))
			return false;

		$payload = array(
				"action" => $pAction,
				"host"   => self::getHostName(),
				"lang"   => LANGUAGE_ID,
				"license_key" => self::getLicenseKey(),
				"spd" => CUpdateClient::getSpd(),
				"testing_token" => $this->checkingToken
			);
		if($pCollectSystemInformation || $pAction === "check")
			$payload["system_information"] = base64_encode(serialize(self::getSystemInformation()));
		return $payload;
	}


	/**
	 * @param string $pResponse
	 * @return array
	 */
	protected static function decodeResponse($pResponse)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		$result = json_decode($pResponse, true);
		if(!defined("BX_UTF"))
			$result = $APPLICATION->ConvertCharsetArray($result, "UTF-8", LANG_CHARSET);
		return $result;
	}

	/**
	 * Return Bitrix WebService Url for Cloud Security Monitor
	 * @return string
	 */
	protected static function getCheckerUrl()
	{
		$result = "http://";
		$result .= COption::GetOptionString("main", "update_site", "www.bitrixsoft.com");
		$result .= self::BITRIX_CHECKER_URL_PATH;
		return $result;
	}

	/**
	 * Send request to Bitrix (check o receive)
	 * @param array $pPayload
	 * @return array|bool
	 */
	protected static function sendRequest(array $pPayload)
	{
		$request = new CHTTP();
		$request->http_timeout = self::CONNECTION_TIMEOUT;
		$request->setFollowRedirect(true);
		@$request->Post(self::getCheckerUrl(), $pPayload);
		if($request->status === 200 && $request->result)
		{
			return self::decodeResponse($request->result);	
		}
		else
		{
			return false;
		}
	}

	/**
	 * Return License key, your Captain Obvious
	 * @return string
	 */
	protected static function getLicenseKey()
	{
		if (defined("LICENSE_KEY"))
		{
			$licenseKey = LICENSE_KEY;
		}
		else
		{
			$licenseKey = "DEMO";
		}
		return md5($licenseKey);
	}

	/**
	 * Return system information, such as php version
	 * @return array
	 */
	protected static function getSystemInformation()
	{
		return CSecuritySystemInformation::getSystemInformation();
	}

	/**
	 * Return host name for site checking
	 * @return string
	 */
	protected function getHostName()
	{
		$sheme = (CMain::IsHTTPS() ? "https" : "http")."://";
		$serverPort = self::getServerPort();
		$url = self::getDomainName();
		$url .= ($serverPort && strpos($url, ":") === false) ? ":".$serverPort : "";
		return $sheme.$url;
	}

	/**
	 * Return current server port, except 80 and 443
	 * @return int|bool
	 */
	protected static function getServerPort()
	{
		if($_SERVER["SERVER_PORT"] && !in_array($_SERVER["SERVER_PORT"], array(80, 443)))
			return $_SERVER["SERVER_PORT"];
		else
			return false;
	}

	/**
	 * Return current domain name (in puny code for cyrillic domain)
	 * @return string
	 */
	protected static function getDomainName()
	{
		return CSecuritySystemInformation::getCurrentHost();
	}
}
