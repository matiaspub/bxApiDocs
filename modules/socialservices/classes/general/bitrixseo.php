<?
use \Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Seo\Service;

Loc::loadMessages(__FILE__);

if(!defined("BITRIX_CLOUD_ADV_URL"))
{
	// define("BITRIX_CLOUD_ADV_URL", 'http://cloud-adv.bitrix.info');
}

if(!defined('BITRIXSEO_URL'))
{
	// define('BITRIXSEO_URL', BITRIX_CLOUD_ADV_URL);
}

class CBitrixSeoOAuthInterface extends CSocServOAuthTransport
{
	const SERVICE_ID = "bitrixseo";

	const URL = BITRIXSEO_URL;

	const AUTH_URL = "/oauth/authorize/";
	const TOKEN_URL = "/oauth/token/";

	protected $scope = array(
		'seo',
	);

	protected $authResult = array();

	static public function __construct($appID = false, $appSecret = false, $code = false)
	{
		if($appID === false)
		{
			$appID = trim(CSocServAuth::GetOption("bitrixseo_id"));
		}

		if($appSecret === false)
		{
			$appSecret = trim(CSocServAuth::GetOption("bitrixseo_secret"));
		}

		parent::__construct($appID, $appSecret, $code);
	}


	public function getScopeEncode()
	{
		return implode(',', array_map('urlencode', array_unique($this->getScope())));
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

	public function GetAuthUrl($redirect_uri, $state = '')
	{
		return self::URL.self::AUTH_URL.
			"?user_lang=".LANGUAGE_ID.
			"&client_id=".urlencode($this->appID).
			"&redirect_uri=".urlencode($redirect_uri).
			"&scope=".$this->getScopeEncode().
			"&response_type=code".
			($state <> '' ? '&state='.urlencode($state) : '');
	}

	public function GetAccessToken($redirect_uri = '')
	{
		if($this->access_token && !$this->checkAccessToken())
		{
			Service::getEngine()->clearAuthSettings();
		}

		if($this->code === false)
		{
			return false;
		}

		$http = new HttpClient(array('socketTimeout' => $this->httpTimeout));

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");

		$result = $http->get(self::URL.self::TOKEN_URL.'?'.http_build_query(
			array(
				'code' => $this->code,
				'client_id' => $this->appID,
				'client_secret' => $this->appSecret,
				'redirect_uri' => $redirect_uri,
				'scope' => implode(',',$this->getScope()),
				'grant_type' => 'authorization_code',
				'key' => md5(\CUpdateClient::GetLicenseKey()),
			)
		));

		if($result)
		{
			try
			{
				$this->authResult = Json::decode($result);
			}
			catch(\Bitrix\Main\ArgumentException $e)
			{
				$result = "";
			}
		}

		if($result)
		{
			if(isset($this->authResult["access_token"]) && $this->authResult["access_token"] <> '')
			{
				if(isset($this->authResult["refresh_token"]) && $this->authResult["refresh_token"] <> '')
				{
					$this->refresh_token = $this->authResult["refresh_token"];
				}

				$this->access_token = $this->authResult["access_token"];
				$this->accessTokenExpires = time() + $this->authResult["expires_in"];

				return true;
			}
		}

		return false;
	}

	public function getNewAccessToken($refreshToken = false, $userId = 0, $save = false, $scope = array())
	{
		if($this->appID == false || $this->appSecret == false)
			return false;

		if($refreshToken == false)
			$refreshToken = $this->refresh_token;

		if($scope != null)
			$this->addScope($scope);

		$http = new HttpClient(array('socketTimeout' => $this->httpTimeout));

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");

		$url = self::URL.self::TOKEN_URL.'?'.http_build_query(
			array(
				'client_id' => $this->appID,
				'client_secret' => $this->appSecret,
				'refresh_token' => $refreshToken,
				'scope' => implode(',',$this->getScope()),
				'grant_type' => 'refresh_token',
				'key' => md5(\CUpdateClient::GetLicenseKey()),
			)
		);

		$result = $http->get($url);

		if($result)
		{
			try
			{
				$this->authResult = Json::decode($result);
			}
			catch(\Bitrix\Main\ArgumentException $e)
			{
				$result = "";
			}
		}

		if($result)
		{
			if(isset($this->authResult["access_token"]) && $this->authResult["access_token"] <> '')
			{
				$this->access_token = $this->authResult["access_token"];
				$this->accessTokenExpires = time() + $this->authResult["expires_in"];
				$this->refresh_token = $this->authResult["refresh_token"];

				return true;
			}
		}

		Service::getEngine()->clearAuthSettings();

		return false;
	}

	public function getClientInfo()
	{
		if($this->access_token)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->getClientInfo();

			if(!isset($res['error']))
			{
				return $res['result'];
			}
		}

		return false;
	}

	public function clearClientAuth($engine)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->clearClientAuth($engine);

			if(!isset($res['error']))
			{
				return $res['result'];
			}
		}

		return false;
	}

	public function addCampaign($engine, array $campaignParams)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(
				CBitrixSeoTransport::METHOD_CAMPAIGN_ADD,
				array(
					"engine" => $engine,
					"campaign" => $campaignParams
				)
			);

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function updateCampaign($engine, array $campaignParams)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(
				CBitrixSeoTransport::METHOD_CAMPAIGN_UPDATE,
				array(
					"engine" => $engine,
					"campaign" => $campaignParams
				)
			);

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function getCampaign($engine, array $campaignParams)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(
				CBitrixSeoTransport::METHOD_CAMPAIGN_GET,
				array(
					"engine" => $engine,
					"campaign" => $campaignParams
				)
			);

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function getCampaignList($engine)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_CAMPAIGN_LIST, array(
				'engine' => $engine,
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function archiveCampaign($engine, $campaignId)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_CAMPAIGN_ARCHIVE, array(
				"engine" => $engine,
				"campaign" => $campaignId
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function unArchiveCampaign($engine, $campaignId)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_CAMPAIGN_UNARCHIVE, array(
				"engine" => $engine,
				"campaign" => $campaignId
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function resumeCampaign($engine, $campaignId)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_CAMPAIGN_RESUME, array(
				"engine" => $engine,
				"campaign" => $campaignId
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function stopCampaign($engine, $campaignId)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_CAMPAIGN_STOP, array(
				"engine" => $engine,
				"campaign" => $campaignId
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function deleteCampaign($engine, $campaignId)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_CAMPAIGN_DELETE, array(
				"engine" => $engine,
				"campaign" => $campaignId
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function addBanner($engine, array $bannerParam)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(
				CBitrixSeoTransport::METHOD_BANNER_ADD,
				array(
					"engine" => $engine,
					"banner" => $bannerParam
				)
			);

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function updateBanner($engine, array $bannerParam)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(
				CBitrixSeoTransport::METHOD_BANNER_UPDATE,
				array(
					"engine" => $engine,
					"banner" => $bannerParam
				)
			);

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function getBannerList($engine, $filter)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_BANNER_LIST, array(
				'engine' => $engine,
				'filter' => $filter
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function moderateBanners($engine, $filter)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_BANNER_MODERATE, array(
				'engine' => $engine,
				'filter' => $filter
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function stopBanners($engine, $filter)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_BANNER_STOP, array(
				'engine' => $engine,
				'filter' => $filter
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function resumeBanners($engine, $filter)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_BANNER_RESUME, array(
				'engine' => $engine,
				'filter' => $filter
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function archiveBanners($engine, $filter)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_BANNER_ARCHIVE, array(
				'engine' => $engine,
				'filter' => $filter
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function unArchiveBanners($engine, $filter)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_BANNER_UNARCHIVE, array(
				'engine' => $engine,
				'filter' => $filter
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function deleteBanners($engine, $filter)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_BANNER_DELETE, array(
				'engine' => $engine,
				'filter' => $filter
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function getRegions($engine)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_REGION_GET, array(
				'engine' => $engine
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function createWordstatReport($engine, $queryData)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_REPORT_WORDSTAT_CREATE, array(
				'engine' => $engine,
				'query' => $queryData,
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function deleteWordstatReport($engine, $reportId)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_REPORT_WORDSTAT_DELETE, array(
				'engine' => $engine,
				'reportId' => $reportId,
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function getWordstatReport($engine, $reportId)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_REPORT_WORDSTAT_GET, array(
				'engine' => $engine,
				'reportId' => $reportId,
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function getWordstatReportList($engine)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_REPORT_WORDSTAT_LIST, array(
				'engine' => $engine,
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}
	public function createForecastReport($engine, $queryData)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_REPORT_FORECAST_CREATE, array(
				'engine' => $engine,
				'query' => $queryData,
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function deleteForecastReport($engine, $reportId)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_REPORT_FORECAST_DELETE, array(
				'engine' => $engine,
				'reportId' => $reportId,
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function getForecastReport($engine, $reportId)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_REPORT_FORECAST_GET, array(
				'engine' => $engine,
				'reportId' => $reportId,
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function getForecastReportList($engine)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_REPORT_FORECAST_LIST, array(
				'engine' => $engine,
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}

	public function getBannerStats($engine, array $params)
	{
		if($this->access_token && $engine)
		{
			$ob = new CBitrixSeoTransport($this->access_token);
			$res = $ob->call(CBitrixSeoTransport::METHOD_STAT_GET, array(
				'engine' => $engine,
				'params' => $params,
			));

			if(!isset($res['error']))
			{
				return $res['result'];
			}
			else
			{
				return $res;
			}
		}

		return false;
	}
}

class CBitrixSeoTransport
{
	const SERVICE_URL = "/rest/";

	const METHOD_METHODS = 'methods';
	const METHOD_BATCH = 'batch';
	const METHOD_CLIENT_INFO = 'seo.client.info';
	const METHOD_CLIENT_AUTH_CLEAR = 'seo.client.auth.clear';

	const METHOD_CAMPAIGN_ADD = 'seo.campaign.add';
	const METHOD_CAMPAIGN_UPDATE = 'seo.campaign.update';
	const METHOD_CAMPAIGN_GET = 'seo.campaign.get';
	const METHOD_CAMPAIGN_LIST = 'seo.campaign.list';
	const METHOD_CAMPAIGN_ARCHIVE = 'seo.campaign.archive';
	const METHOD_CAMPAIGN_UNARCHIVE = 'seo.campaign.unarchive';
	const METHOD_CAMPAIGN_RESUME = 'seo.campaign.resume';
	const METHOD_CAMPAIGN_STOP = 'seo.campaign.stop';
	const METHOD_CAMPAIGN_DELETE = 'seo.campaign.delete';

	const METHOD_BANNER_ADD = 'seo.banner.add';
	const METHOD_BANNER_UPDATE = 'seo.banner.update';
	const METHOD_BANNER_LIST = 'seo.banner.list';
	const METHOD_BANNER_MODERATE = 'seo.banner.moderate';
	const METHOD_BANNER_ARCHIVE = 'seo.banner.archive';
	const METHOD_BANNER_UNARCHIVE = 'seo.banner.unarchive';
	const METHOD_BANNER_RESUME = 'seo.banner.resume';
	const METHOD_BANNER_STOP = 'seo.banner.stop';
	const METHOD_BANNER_DELETE = 'seo.banner.delete';

	const METHOD_REGION_GET = 'seo.region.get';

	const METHOD_REPORT_WORDSTAT_CREATE = 'seo.report.wordstat.create';
	const METHOD_REPORT_WORDSTAT_DELETE = 'seo.report.wordstat.delete';
	const METHOD_REPORT_WORDSTAT_GET = 'seo.report.wordstat.get';
	const METHOD_REPORT_WORDSTAT_LIST = 'seo.report.wordstat.list';

	const METHOD_REPORT_FORECAST_CREATE = 'seo.report.forecast.create';
	const METHOD_REPORT_FORECAST_DELETE = 'seo.report.forecast.delete';
	const METHOD_REPORT_FORECAST_GET = 'seo.report.forecast.get';
	const METHOD_REPORT_FORECAST_LIST = 'seo.report.forecast.list';

	const METHOD_STAT_GET = 'seo.stat.get';

	protected $access_token = '';
	protected $httpTimeout = SOCSERV_DEFAULT_HTTP_TIMEOUT;

	protected function prepareAnswer($result)
	{
		return Json::decode($result);
	}

	public function call($methodName, $additionalParams = null)
	{
		global $APPLICATION;

		if(!$this->access_token)
		{
			$interface = Service::getEngine()->getInterface();

			if(!$interface->checkAccessToken())
			{
				if($interface->getNewAccessToken())
				{
					Service::getEngine()->setAuthSettings($interface->getResult());
				}
				else
				{
					return $interface->getResult();
				}
			}

			$this->access_token = $interface->getToken();
		}

		if($this->access_token)
		{
			if(!is_array($additionalParams))
			{
				$additionalParams = array();
			}
			else
			{
				$additionalParams = $APPLICATION->ConvertCharsetArray($additionalParams, LANG_CHARSET, "utf-8");
			}

			$additionalParams['auth'] = $this->access_token;

			$http = new HttpClient(array('socketTimeout' => $this->httpTimeout));
			$result = $http->post(
				CBitrixSeoOAuthInterface::URL.self::SERVICE_URL.$methodName,
				$additionalParams
			);

/*			AddMessage2Log(array(
				CBitrixSeoOAuthInterface::URL.self::SERVICE_URL.$methodName,
				$additionalParams,
				$http->getStatus(),
				$result,
			));*/

			$res = $this->prepareAnswer($result);
			if(!$res)
			{
				AddMessage2Log('Strange answer from Seo! '.$http->getStatus().' '.$result);
			}

			return $res;
		}
		else
		{
			throw new SystemException("No access token");
		}
	}

	public function batch($actions)
	{
		$arBatch = array();

		if(is_array($actions))
		{
			foreach($actions as $query_key => $arCmd)
			{
				list($cmd, $arParams) = array_values($arCmd);
				$arBatch['cmd'][$query_key] = $cmd.(is_array($arParams) ? '?'.http_build_query($arParams) : '');
			}
		}

		return $this->call(self::METHOD_BATCH, $arBatch);
	}

	public function getMethods()
	{
		return $this->call(self::METHOD_METHODS);
	}

	public function getClientInfo()
	{
		return $this->call(self::METHOD_CLIENT_INFO);
	}

	public function clearClientAuth($engine)
	{
		return $this->call(self::METHOD_CLIENT_AUTH_CLEAR, array("engine" => $engine));
	}
}
