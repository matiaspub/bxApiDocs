<?php
IncludeModuleLangFile(__FILE__);

use \Bitrix\Main\Web\HttpClient;

class CSocServGooglePlusOAuth extends CSocServGoogleOAuth
{
	const ID = "GooglePlusOAuth";
	const LOGIN_PREFIX = "GP_";

	public function getEntityOAuth($code = false)
	{
		if(!$this->entityOAuth)
		{
			$this->entityOAuth = new CGooglePlusOAuthInterface();
		}

		return parent::getEntityOAuth($code);
	}

	static public function GetSettings()
	{
		return array(
			array("note"=>GetMessage("socserv_googleplus_note")),
		);
	}

	public function getFriendsList($limit, &$next)
	{
		if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
		{
			$redirect_uri = static::CONTROLLER_URL."/redirect.php";
		}
		else
		{
			$redirect_uri = CSocServUtil::ServerName()."/bitrix/tools/oauth/google.php";
		}

		$ob = $this->getEntityOAuth();

		if($ob->GetAccessToken($redirect_uri) !== false)
		{
			$res = $ob->getCurrentUserFriends($limit, $next);

			foreach($res["items"] as $key => $contact)
			{
				$contact["uid"] = $contact["id"];

				if(array_key_exists("name", $contact))
				{
					$contact["first_name"] = $contact["name"]["givenName"];
					$contact["last_name"] = $contact["name"]["familyName"];
				}
				else
				{
					list($contact["first_name"], $contact["last_name"]) = explode(" ", $contact["displayName"], 2);
				}

				if(array_key_exists("image", $contact))
				{
					$contact["picture"] = preg_replace("/\?.*$/", "", $contact["image"]["url"]);
				}

				$res["items"][$key] = $contact;
			}

			return $res["items"];
		}

		return false;
	}

	static public function getProfileUrl($uid)
	{
		return "https://plus.google.com/".$uid;
	}
}

class CGooglePlusOAuthInterface extends CGoogleOAuthInterface
{
	const SERVICE_ID = "GooglePlusOAuth";

	const PROFILE_URL = 'https://www.googleapis.com/plus/v1/people/me';
	const FRIENDS_URL = 'https://www.googleapis.com/plus/v1/people/me/people/visible';

	const FRIENDS_FIELDS = 'items(displayName,emails,gender,id,image,name,nickname),nextPageToken,title,totalItems';

	protected $scope = array(
		'https://www.googleapis.com/auth/plus.login',
		'https://www.googleapis.com/auth/plus.me',
	);

	public function getCurrentUser()
	{
		return $this->query(static::PROFILE_URL);
	}

	public function getCurrentUserFriends($limit, &$next)
	{
		$url = static::FRIENDS_URL.'?';//'fields='.urlencode(static::FRIENDS_FIELDS);

		$limit = intval($limit);
		if($limit > 0)
		{
			$url .= '&maxResults='.$limit;
		}

		if($next)
		{
			$url .= '&pageToken='.$next;
		}

		$result = $this->query($url);

		$next = $result['nextPageToken'];
		if(!$next)
		{
			$next = '__finish__';
		}

		return $result;
	}

	protected function query($url)
	{
		if($this->access_token === false)
			return false;

		$http = new HttpClient();
		$http->setHeader("authorization", "Bearer ".$this->access_token);
		$result = $http->get($url);

		if(!defined("BX_UTF"))
		{
			$result = CharsetConverter::ConvertCharset($result, "utf-8", LANG_CHARSET);
		}

		$result = CUtil::JsObjectToPhp($result);

		return $result;
	}
}