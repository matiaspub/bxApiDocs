<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo\Engine;

use Bitrix\Seo\Engine;
use Bitrix\Seo\SearchEngineTable;
use Bitrix\Main\Text;
use Bitrix\Main\Text\Converter;

class Yandex extends Engine
{
	const ENGINE_ID = 'yandex';

	const SERVICE_URL = "https://webmaster.yandex.ru/api/v2";

	const HOSTS_SERVICE = "host-list";
	const HOST_VERIFY = "verify-host";
	const HOST_INFO = "host-information";
	const HOST_TOP_QUERIES = "top-queries";
	const HOST_ORIGINAL_TEXTS = "original-texts";
	const HOST_INDEXED = "indexed-urls";
	const HOST_EXCLUDED = "excluded-urls";

	const ORIGINAL_TEXT_MIN_LENGTH = 500;
	const ORIGINAL_TEXT_MAX_LENGTH = 32000;

	const QUERY_USER = 'https://login.yandex.ru/info';

	const VERIFIED_STATE_VERIFIED = "VERIFIED";
	const VERIFIED_STATE_WAITING = "WAITING";
	const VERIFIED_STATE_FAILED = "VERIFICATION_FAILED";
	const VERIFIED_STATE_NEVER_VERIFIED = "NEVER_VERIFIED";
	const VERIFIED_STATE_IN_PROGRESS = "IN_PROGRESS";

	protected $engineId = 'yandex';
	protected $arServiceList = array();

	public function getSettings()
	{
		return $this->engineSettings;
	}

	public function getAuthUrl()
	{
		return $this->getInterface()->getAuthUrl();
	}

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

	public function getAuthSettings()
	{
		return $this->engineSettings['AUTH'];
	}

	public function clearAuthSettings()
	{
		unset($this->engineSettings['AUTH']);
		$this->saveSettings();
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

		return false;
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

		return false;
	}

	public function getCurrentUser()
	{
		global $APPLICATION;

		if(!isset($this->engineSettings['AUTH_USER']) || !is_array($this->engineSettings['AUTH_USER']))
		{
			$queryResult = $this->query(self::QUERY_USER);
			if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
			{
				$res = json_decode($queryResult->result, true);
				if(is_array($res))
				{
					$this->engineSettings['AUTH_USER'] = $APPLICATION->convertCharsetArray($res, 'utf-8', LANG_CHARSET);
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

	public function getFeeds()
	{
		if(!isset($this->arServiceList[self::HOSTS_SERVICE]))
		{
			$this->getServiceDocument();
		}

		if(isset($this->arServiceList[self::HOSTS_SERVICE]))
		{
			$queryResult = $this->query($this->arServiceList[self::HOSTS_SERVICE]);

			if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
			{
				return $this->processResult($queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
	}

	public function getSiteFeeds($domain)
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]))
		{
			$this->getFeeds();
		}

		if(isset($this->engineSettings['SITES'][$domain]))
		{
			$queryResult = $this->query($this->engineSettings['SITES'][$domain]['href']);

			if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
			{
				return $this->processSiteResult($queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
		else
		{
			throw new \Exception('Site not binded! '.$domain);
		}
	}

	public function getSiteInfo($domain)
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$queryResult = $this->query($this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_INFO]);
			if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
			{
				return $this->processResult($queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
		else
		{
			throw new \Exception('Site not binded! '.$domain);
		}
	}

	public function getQueriesFeed($domain)
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$queryResult = $this->query($this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_TOP_QUERIES]);
			if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
			{
				$obXml = new \CDataXML();
				if($obXml->loadString($queryResult->result))
				{
					$root = $obXml->getTree()->elementsByName('top-queries');
					if(count($root) > 0)
					{
						$root = $root[0];

						$arQueriesData = array(
							'top-shows' => array(),
							'top-clicks' => array()
						);

						foreach ($root->children as $child)
						{
							switch($child->name())
							{
								case 'top-shows':
								case 'top-clicks':
									$arQueries = $child->elementsByName('top-info');
									foreach($arQueries as $query)
									{
										$arData = array();
										foreach($query->children() as $subChild)
										{
											$arData[$subChild->name()] = $subChild->textContent();
										}
										$arQueriesData[$child->name()][] = $arData;
									}

								break;
								default:
									$arQueriesData[$child->name()] = $child->textContent();
								break;
							}
						}

						return $arQueriesData;
					}

				}
				throw new \Exception('Unexpected query result! '.$queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
		else
		{
			throw new \Exception('Site not binded! '.$domain);
		}
	}

	public function getOriginalTexts($domain, $dir = "/")
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$queryResult = $this->query($this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_ORIGINAL_TEXTS]);
			if($queryResult->status == self::HTTP_STATUS_OK)
			{
				$obXml = new \CDataXML();
				if($obXml->loadString($queryResult->result))
				{
					$arOriginalTexts = array();
					$arEntries = $obXml->getTree()->elementsByName('original-text');
					foreach($arEntries as $entry)
					{
						$arText = array();
						$arChildren = $entry->children();
						foreach($arChildren as $child)
						{
							$arText[$child->name] = $child->textContent();
						}
						$arOriginalTexts[] = $arText;
					}

					return array(
						"total" => $obXml->getTree()->root[0]->getAttribute("total"),
						"can-add" => $obXml->getTree()->root[0]->getAttribute("can-add"),
						"text" => $arOriginalTexts,
					);
				}

				throw new \Exception('Unexpected query result! '.$queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
	}

	public function addOriginalText($text, $domain, $dir = '/')
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
$str = <<<EOT
<original-text><content>%s</content></original-text>
EOT;

			$queryResult = $this->query(
				$this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_ORIGINAL_TEXTS],
				'POST',
				urlencode(sprintf(
					$str,
					Converter::getXmlConverter()->encode(
						Text\Encoding::convertEncoding(
							$text,
							LANG_CHARSET,
							'utf-8'
						)
					)
				))
			);

			if($queryResult->status == self::HTTP_STATUS_OK || $queryResult->status == self::HTTP_STATUS_CREATED)
			{

				return true;
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
	}

	public function getIndexed($domain, $dir = "/")
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$queryResult = $this->query($this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_INDEXED]);
			if($queryResult->status == self::HTTP_STATUS_OK)
			{
				$obXml = new \CDataXML();
				if($obXml->loadString($queryResult->result))
				{
					$arIndexed = array(
						'last-week-index-urls' => array(),
					);
					$root = $obXml->getTree()->root[0];
					foreach($root->children as $tag)
					{
						switch($tag->name())
						{
							case 'last-week-index-urls':
								if(count($tag->children()) > 0)
								{
									foreach($tag->children() as $child)
									{
										$arIndexed[$tag->name()][] = $child->textContent();
									}
								}
							break;
							default:
								$arIndexed[$tag->name()] = $tag->textContent();
							break;
						}
					}

					return $arIndexed;
				}

				throw new \Exception('Unexpected query result! '.$queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
	}

	public function getExcluded($domain, $dir = "/")
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$queryResult = $this->query($this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_EXCLUDED]);
			if($queryResult->status == self::HTTP_STATUS_OK)
			{
				$obXml = new \CDataXML();
				if($obXml->loadString($queryResult->result))
				{
					$root = $obXml->getTree()->root[0]->children();
					$arExcluded = array(
						"count" => $root[0]->getAttribute('count'),
						"errors" => array(),
					);
					$arEntries = $obXml->getTree()->elementsByName('url-errors-with-code');
					foreach($arEntries as $entry)
					{
						$error = array(
							"code" => $entry->getAttribute('code')
						);
						foreach($entry->children() as $child)
						{
							$error[$child->name()] = $child->textContent();
						}
						$arExcluded['errors'][] = $error;
					}

					return $arExcluded;
				}

				throw new \Exception('Unexpected query result! '.$queryResult->result);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
	}

	public function addSite($domain, $dir = '/')
	{
		$domain = ToLower($domain);

		if(!isset($this->arServiceList[self::HOSTS_SERVICE]))
		{
			$this->getServiceDocument();
		}

		if(isset($this->arServiceList[self::HOSTS_SERVICE]))
		{
			$str = <<<EOT
<host><name>%s</name></host>
EOT;
			$queryResult = $this->query(
				$this->arServiceList[self::HOSTS_SERVICE],
				"POST",
				sprintf($str, Converter::getXmlConverter()->encode($domain))
			);

			if($queryResult->status == self::HTTP_STATUS_CREATED && strlen($queryResult->result) > 0)
			{
				return array($domain => true);
			}
			else
			{
				throw new Engine\YandexException($queryResult);
			}
		}
	}

	public function verifySite($domain, $bCheck)
	{
		$domain = ToLower($domain);

		if(!isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			$this->getSiteFeeds($domain);
		}

		if(isset($this->engineSettings['SITES'][$domain]['SERVICES']))
		{
			if(!$bCheck)
			{
				$queryResult = $this->query($this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_VERIFY]);
				if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
				{
					$obXml = new \CDataXML();
					if($obXml->loadString($queryResult->result))
					{
						$ver = $obXml->getTree()->elementsByName('verification');
						$ver = $ver[0];

						$state = $ver->getAttribute('state');
						if($state != 'VERIFIED')
						{
							return $ver->children[0]->textContent();
						}
						else
						{
							return false;
						}
					}
				}
				else
				{
					throw new Engine\YandexException($queryResult);
				}
			}
			else
			{
				$queryResult = $this->query(
					$this->engineSettings['SITES'][$domain]['SERVICES'][self::HOST_VERIFY],
					"PUT",
					"<host><type>TXT_FILE</type></host>"
				);

				if($queryResult->status == self::HTTP_STATUS_OK || $queryResult->status == self::HTTP_STATUS_NO_CONTENT)
				{
					return array($domain => array('verification' => 'VERIFIED'));
				}
				else
				{
					throw new Engine\YandexException($queryResult);
				}
			}
		}

		return;
	}

	protected function getServiceDocument()
	{
		$queryResult = $this->query(self::SERVICE_URL);
		if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
		{
			return $this->processServiceDocument($queryResult->result);
		}
		else
		{
			throw new Engine\YandexException($queryResult);
		}
	}

	protected function processServiceDocument($res)
	{
		$obXml = new \CDataXML();

		if($obXml->loadString($res))
		{
			$arEntries = $obXml->getTree()->elementsByName('link');
			foreach($arEntries as $entry)
			{
				$this->arServiceList[$entry->getAttribute('rel')] = $entry->getAttribute('href');
			}
		}
	}

	protected function processResult($res)
	{
		$obXml = new \CDataXML();

		if($obXml->loadString($res))
		{
			$arEntries = $obXml->getTree()->elementsByName('host');

			$arDomains = array();
			foreach($arEntries as $entry)
			{
				$entryChildren = $entry->children();
				$entryData = array();

				foreach ($entryChildren as $child)
				{
					$tag = $child->name();

					switch($tag)
					{
						case 'name':
							$entryData[$tag] = \CBXPunycode::toASCII(ToLower($child->textContent()), $e = null);
						break;

						case 'verification':
						case 'crawling':
							$entryData[$tag] = $child->getAttribute('state');
							$details = $child->children();
							if($details)
							{
								$entryData[$tag.'-details'] = $details[0]->textContent();
							}
						break;

						case 'virused':
							$entryData[$tag] = $child->textContent() == 'true';
						break;

						default: $entryData[$tag] = $child->textContent();
					}
				}

				// HOST_INFO query returns only host id instead of direct url so we should take it from the previous data
				$hostHref = $entry->getAttribute('href');
				if(!$hostHref)
				{
					$hostHref = $this->engineSettings['SITES'][$entryData['name']]['href'];
				}
				$entryData['href'] = $hostHref;

				$arDomains[$entryData['name']] = $entryData;
			}

			$arExistedDomains = \CSeoUtils::getDomainsList();
			foreach($arExistedDomains as $domain)
			{
				$domain['DOMAIN'] = ToLower($domain['DOMAIN']);

				if(isset($arDomains[$domain['DOMAIN']]))
				{
					if(!is_array($this->engineSettings['SITES']))
						$this->engineSettings['SITES'] = array();

					$this->engineSettings['SITES'][$domain['DOMAIN']] = $arDomains[$domain['DOMAIN']];
				}
			}

			$this->saveSettings();

			return $arDomains;
		}

		throw new \Exception('Unexpected query result! '.$res);
		return false;
	}

	protected function processSiteResult($res)
	{
		$obXml = new \CDataXML();

		if($obXml->loadString($res))
		{
			$hostName = $obXml->getTree()->elementsByName('name');
			$hostName = \CBXPunycode::toASCII(ToLower($hostName[0]->textContent()), $e = null);

			$this->engineSettings['SITES'][$hostName]['SERVICES'] = array();

			$arLinks = $obXml->getTree()->elementsByName('link');
			foreach ($arLinks as $link)
			{
				$this->engineSettings['SITES'][$hostName]['SERVICES'][$link->getAttribute('rel')] = $link->getAttribute('href');
			}

			$this->saveSettings();

			return true;
		}

		return false;
	}

	protected function query($scope, $method = "GET", $data = null, $bSkipRefreshAuth = false)
	{
		if($this->engineSettings['AUTH'])
		{
			$http = new \CHTTP();
			$http->setAdditionalHeaders(
				array(
					'Authorization' => 'OAuth '.$this->engineSettings['AUTH']['access_token']
				)
			);
			$http->setFollowRedirect(false);

			switch($method)
			{
				case 'GET':
					$result = $http->get($scope);
				break;
				case 'POST':
					$result = $http->post($scope, $data);
				break;
				case 'PUT':
					$result = $http->httpQuery($method, $scope, $http->prepareData($data));
				break;
				case 'DELETE':

				break;
			}

			if($http->status == 401 && !$bSkipRefreshAuth)
			{
				if($this->checkAuthExpired(false))
				{
					$this->query($scope, $method, $data, true);
				}
			}

			$http->result = Text\Encoding::convertEncoding($http->result, 'utf-8', LANG_CHARSET);

			return $http;
		}
	}

	protected function saveSettings()
	{
		SearchEngineTable::update($this->engine['ID'], array(
			'SETTINGS' => serialize($this->engineSettings)
		));
	}
}
?>