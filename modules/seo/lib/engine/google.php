<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo\Engine;

use Bitrix\Seo\Engine;
use Bitrix\Seo\IEngine;
use Bitrix\Main\Text\Converter;
use Bitrix\Main\Web\Json;

class Google extends Engine implements IEngine
{
	const ENGINE_ID = 'google';
	const SCOPE_BASE = 'https://www.google.com/webmasters/tools/feeds/';
	const SCOPE_USER = 'https://www.googleapis.com/auth/userinfo.profile';
	const SCOPE_VERIFY = 'https://www.googleapis.com/auth/siteverification.verify_only';

	const SCOPE_FEED_SITES = 'sites/';
	const SCOPE_FEED_CRAWLISSUES = 'crawlissues/';
	const SCOPE_FEED_MESSAGES = 'messages/';

	const SCOPE_DOMAIN_PROTOCOL = 'http://';

	const QUERY_USER = 'https://www.googleapis.com/oauth2/v3/userinfo';
	const QUERY_VERIFY = 'https://www.googleapis.com/siteVerification/v1/webResource?verificationMethod=FILE';

	protected $engineId = 'google';
	protected $scope = null;

	public function getScope()
	{
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

		return $this->scope;
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

		return false;
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

		return false;
	}

	public function getCurrentUser()
	{
		global $APPLICATION;

		if(!isset($this->engineSettings['AUTH_USER']) || !is_array($this->engineSettings['AUTH_USER']))
		{
			$queryResult = $this->queryXml(self::QUERY_USER);
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

			throw new \Exception('Query error! '.$queryResult->status.': '.$queryResult->result);
		}
		else
		{
			return $this->engineSettings['AUTH_USER'];
		}
	}

	public function getFeeds()
	{
		$queryResult = $this->queryXml(self::SCOPE_BASE.self::SCOPE_FEED_SITES);
		if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
		{
			return $this->processResult($queryResult->result);
		}
		else
		{
			throw new \Exception('Query error! '.$queryResult->status.': '.$queryResult->result);
		}
	}

	public function getSitemapsFeed($domain, $dir = '/')
	{
		$url = $this->engineSettings['SITES'][$domain]['entryLink']['sitemaps'];
		if($url)
		{
			$queryResult = $this->queryXml($url);
			if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
			{
				$obXml = new \CDataXML();

				if($obXml->loadString($queryResult->result))
				{
					$arFeeds = $obXml->getTree()->elementsByName('feed');
					foreach($arFeeds as $feed)
					{
						$feedChildren = $feed->children();
					}

					return $feedData;
				}
				else
				{
					throw new \Exception('Unexpected query result! '.$queryResult->status.': '.$queryResult->result);
				}
			}
			else
			{
				throw new \Exception('Query error! '.$queryResult->status.': '.$queryResult->result);
			}
		}
	}

	public function getCrawlIssuesFeed($domain, $dir = '/')
	{
		$url = self::SCOPE_BASE.urlencode(self::SCOPE_DOMAIN_PROTOCOL.$domain.$dir).'/'.self::SCOPE_FEED_CRAWLISSUES;
		$queryResult = $this->queryXml($url);
		if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
		{
			$obXml = new \CDataXML();

			if($obXml->loadString($queryResult->result))
			{
				$arEntries = $obXml->getTree()->elementsByName('entry');
				$arEntriesData = array();
				foreach($arEntries as $entry)
				{
					$feedChildren = $entry->children();
					$entryData = array();

					foreach ($feedChildren as $child)
					{
						$tag = $child->name();

						switch($tag)
						{
							case 'link':
								if(!isset($entryData[$tag]))
									$entryData[$tag] = array();

								$entryData[$tag][$child->getAttribute('rel')] = $child->getAttribute('href');
							break;
							default: $entryData[$tag] = $child->textContent();
						}
					}
					$arEntriesData[] = $entryData;
				}

				return $arEntriesData;
			}
			else
			{
				throw new \Exception('Unexpected query result! '.$queryResult->status.': '.$queryResult->result);
			}
		}
		else
		{
			throw new \Exception('Query error! '.$queryResult->status.': '.$queryResult->result);
		}
	}

	public function getKeywordsFeed($domain, $dir = '/')
	{
		$url = $this->engineSettings['SITES'][$domain]['entryLink']['keywords'];
		if($url)
		{
			$queryResult = $this->queryXml($url);
			if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
			{
				$obXml = new \CDataXML();

				if($obXml->loadString($queryResult->result))
				{
					$arFeeds = $obXml->getTree()->elementsByName('feed');
					foreach($arFeeds as $feed)
					{
						$feedChildren = $feed->children();
						$feedData = array(
							'etag' => $feed->getAttribute('etag'),
							'keyword' => array(
								'internal' => array(),
								'external' => array(),
							),
						);

						foreach ($feedChildren as $child)
						{
							$tag = $child->name();

							switch($tag)
							{
								case 'category': break;

								case 'link':
									if(!isset($feedData[$tag]))
										$feedData[$tag] = array();

									$feedData[$tag][$child->getAttribute('rel')] = $child->getAttribute('href');
								break;

								case 'keyword':
									$feedData['keyword'][$child->getAttribute('source')][] = $child->textContent();
								break;

								default: $feedData[$tag] = $child->textContent();
							}
						}
					}

					return $feedData;
				}
				else
				{
					throw new \Exception('Unexpected query result! '.$queryResult->status.': '.$queryResult->result);
				}
			}
			else
			{
				throw new \Exception('Query error! '.$queryResult->status.': '.$queryResult->result);
			}
		}
	}

	public function getSiteInfo($domain, $dir = '/')
	{
		$queryResult = $this->queryXml($this->getSiteId($domain, $dir));
		if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
		{
			$res = $this->processResult($queryResult->result);
			$res['_domain'] = $domain;
			$res['_path'] = $path;

			return $res;
		}
		else
		{
			throw new \Exception('Query error! '.$queryResult->status.': '.$queryResult->result);
		}
	}

	public function setSiteInfo($domain, $dir = '/', $arFields)
	{
		$str = <<<EOT
<atom:entry xmlns:atom='http://www.w3.org/2005/Atom' xmlns:wt="http://schemas.google.com/webmasters/tools/2007"
><atom:id>%s</atom:id>%s
</atom:entry>
EOT;

		if(count($arFields) > 0)
		{
			$str1 = '';
			foreach($arFields as $field=>$value)
			{
				$str1 .= '<wt:'.$field.'>'.Converter::getXmlConverter()->encode($value).'</wt:'.$field.'>';
			}

			$queryResult = $this->queryXml(
				$this->engineSettings['SITES'][$domain]['link']['edit'],
				"PUT",
				sprintf(
					$str,
					Converter::getXmlConverter()->encode(self::SCOPE_DOMAIN_PROTOCOL.$domain.$dir),
					$str1
				)
			);

			if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
			{
				return $this->processResult($queryResult->result);
			}
			else
			{
				throw new \Exception('Query error! '.$queryResult->status.': '.$queryResult->result);
			}
		}

	}

	public function addSite($domain, $dir = '/')
	{
		$str = <<<EOT
<atom:entry xmlns:atom='http://www.w3.org/2005/Atom'><atom:content src="%s" /></atom:entry>
EOT;

		$queryResult = $this->queryXml(
			self::SCOPE_BASE.self::SCOPE_FEED_SITES,
			"POST",
			sprintf($str, Converter::getXmlConverter()->encode(self::SCOPE_DOMAIN_PROTOCOL.$domain.$dir))
		);

		if($queryResult->status == self::HTTP_STATUS_CREATED && strlen($queryResult->result) > 0)
		{
			return $this->processResult($queryResult->result);
		}
		else
		{
			throw new \Exception('Query error! '.$queryResult->status.': '.$queryResult->result);
		}
	}

	public function verifySite($domain, $dir)
	{
		$data = array("site" => array("identifier" => self::SCOPE_DOMAIN_PROTOCOL.$domain.$dir, "type" => "SITE"));
		$queryResult = $this->queryJson(
			self::QUERY_VERIFY,
			"POST",
			Json::encode($data)
		);

		if($queryResult->status == self::HTTP_STATUS_OK && strlen($queryResult->result) > 0)
		{
			return true;
		}
		else
		{
			throw new \Exception('Query error! '.$queryResult->status.': '.$queryResult->result);
		}
	}

	protected function processResult($res)
	{
		$obXml = new \CDataXML();
		if($obXml->loadString($res))
		{
			$arEntries = $obXml->getTree()->elementsByName('entry');

			$arDomains = array();
			foreach($arEntries as $entry)
			{
				$entryChildren = $entry->children();
				$entryData = array(
					'etag' => $entry->getAttribute('etag')
				);

				foreach ($entryChildren as $child)
				{
					$tag = $child->name();
					switch($tag)
					{
						case 'category': break;
						case 'content':
							$entryData[$tag] = $child->getAttribute('src');
						break;
						case 'link':
							if(!isset($entryData[$tag]))
								$entryData[$tag] = array();

							$entryData[$tag][$child->getAttribute('rel')] = $child->getAttribute('href');
						break;
						case 'entryLink':
							if(!isset($entryData[$tag]))
								$entryData[$tag] = array();

							$rel = preg_replace("/^[^#]+#/", "", $child->getAttribute('rel'));
							$entryData[$tag][$rel] = $child->getAttribute('href');
						break;
						case 'verification-method':
							if($child->getAttribute('type') == 'htmlpage')
							{
								$entryData[$tag] = array(
									'in-use' => $child->getAttribute('in-use'),
									'file-name' => $child->textContent(),
									'file-content' => $child->getAttribute('file-content')
								);
							}
						break;
						default: $entryData[$tag] = $child->textContent();
					}
				}

				$url = $entryData['content'];
				if(strlen($url) > 0)
				{
					$urlData = parse_url($url);
					if(isset($urlData['port']) && strlen($urlData['port']) > 0)
						$urlData['host'] .= ':'.$urlData['port'];

					if(!isset($arDomains[$urlData['host']]))
					{
						$arDomains[$urlData['host']] = $entryData;
					}
				}
			}

			$arExistedDomains = \CSeoUtils::getDomainsList();
			foreach($arExistedDomains as $domain)
			{
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


	protected function queryJson($scope, $method = "GET", $data = null, $bSkipRefreshAuth = false)
	{
		return $this->query($scope, $method, $data, $bSkipRefreshAuth, 'application/json');
	}

	protected function queryXml($scope, $method = "GET", $data = null, $bSkipRefreshAuth = false)
	{
		return $this->query($scope, $method, $data, $bSkipRefreshAuth, 'application/atom+xml');
	}


	protected function query($scope, $method = "GET", $data = null, $bSkipRefreshAuth = false, $contentType = 'application/atom+xml')
	{
		if($this->engineSettings['AUTH'])
		{
			$http = new \CHTTP();
			$http->setAdditionalHeaders(
				array(
					'Authorization' => 'Bearer '.$this->engineSettings['AUTH']['access_token'],
					'GData-Version' => '2'
				)
			);

			switch($method)
			{
				case 'GET':
					$result = $http->get($scope);
				break;
				case 'POST':
				case 'PUT':
					$arUrl = $http->parseURL($scope);
					$result = $http->query($method, $arUrl['host'], $arUrl['port'], $arUrl['path_query'], $data, $arUrl['proto'], $contentType);
				break;
				case 'DELETE':

				break;
			}

			if($http->status == 401 && !$bSkipRefreshAuth)
			{
				if($this->checkAuthExpired(true))
				{
					return $this->query($scope, $method, $data, true, $contentType);
				}
			}

			return $http;
		}
	}

	protected function getSiteId($domain, $dir = '/')
	{
		return isset($this->engineSettings['SITES'][$domain])
			? $this->engineSettings['SITES'][$domain]['id']
			: self::SCOPE_BASE.self::SCOPE_FEED_SITES.urlencode(self::SCOPE_DOMAIN_PROTOCOL.$domain.$dir);
	}
}
?>