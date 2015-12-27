<?php
class CSocServOAuthTransport
{
	const SERVICE_ID = "generic";

	protected $appID;
	protected $appSecret;
	protected $code = false;
	protected $access_token = false;
	protected $accessTokenExpires = 0;
	protected $refresh_token = '';

	protected $scope = '';

	public function __construct($appID, $appSecret, $code = false)
	{
		$this->httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;
		$this->appID = $appID;
		$this->appSecret = $appSecret;
		$this->code = $code;
	}

	public function getAppID()
	{
		return $this->appID;
	}

	public function getAppSecret()
	{
		return $this->appSecret;
	}

	public function getAccessTokenExpires()
	{
		return $this->accessTokenExpires;
	}

	public function setAccessTokenExpires($accessTokenExpires)
	{
		$this->accessTokenExpires = $accessTokenExpires;
	}

	public function getToken()
	{
		return $this->access_token;
	}

	public function setToken($access_token)
	{
		$this->access_token = $access_token;
	}

	public function setRefreshToken($refresh_token)
	{
		$this->refresh_token = $refresh_token;
	}

	public function getRefreshToken()
	{
		return $this->refresh_token;
	}

	public function addScope($scope)
	{
		if(is_array($scope))
			$this->scope = array_merge($this->scope, $scope);
		else
			$this->scope[] = $scope;
		return $this;
	}

	public function setScope($scope)
	{
		$this->scope = $scope;
	}

	public function getScope()
	{
		return $this->scope;
	}

	public function setCode($code)
	{
		$this->code = $code;
	}

	protected function getStorageTokens()
	{
		global $USER;

		$accessToken = '';
		if(is_object($USER) && $USER->IsAuthorized())
		{
			$dbSocservUser = CSocServAuthDB::GetList(
				array(),
				array(
					'USER_ID' => $USER->GetID(),
					"EXTERNAL_AUTH_ID" => static::SERVICE_ID
				), false, false, array("USER_ID", "XML_ID", "OATOKEN", "OATOKEN_EXPIRES", "REFRESH_TOKEN")
			);

			$accessToken = $dbSocservUser->Fetch();
		}
		return $accessToken;
	}

	protected function deleteStorageTokens()
	{
		global $USER;

		if(is_object($USER) && $USER->IsAuthorized())
		{
			$dbSocservUser = CSocServAuthDB::GetList(
				array(),
				array(
					'USER_ID' => $USER->GetID(),
					"EXTERNAL_AUTH_ID" => static::SERVICE_ID
				), false, false, array("ID")
			);

			while($accessToken = $dbSocservUser->Fetch())
			{
				CSocServAuthDB::Delete($accessToken['ID']);
			}
		}
	}

	public function checkAccessToken()
	{
		return (($this->accessTokenExpires - 30) < time()) ? false : true;
	}
}