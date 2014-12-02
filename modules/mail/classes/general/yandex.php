<?php

IncludeModuleLangFile(__FILE__);

class CMailYandex
{

	static public function __construct()
	{
	}

	public static function checkUser($token, $login, &$error)
	{
		$result = self::query('https://pddimp.yandex.ru/check_user.xml', array(
			'token' => $token,
			'login' => $login
		));

		if ($resultNode = $result->selectNodes('/page/result'))
			return $resultNode->textContent();

		self::setError($result, $error);
		return false;
	}

	public static function registerUserToken($token, $login, $password, &$error)
	{
		$result = self::query('https://pddimp.yandex.ru/reg_user_token.xml', array(
			'token'      => $token,
			'u_login'    => $login,
			'u_password' => $password
		));

		if ($okNode = $result->selectNodes('/page/ok'))
			return $okNode->getAttribute('uid');

		self::setError($result, $error);
		return false;
	}

	public static function userOAuthToken($token, $domain, $login, &$error)
	{
		$result = self::query('https://pddimp.yandex.ru/api/user_oauth_token.xml', array(
			'token'  => $token,
			'domain' => $domain,
			'login'  => $login
		));

		if ($oauthTokenNode = $result->selectNodes('/action/domains/domain/email/oauth-token'))
			return $oauthTokenNode->textContent();

		self::setError2($result, $error);
		return false;
	}

	public static function passport($country, $oauthToken, $errorUrl)
	{
		switch ($country)
		{
			case 'ru':
			case 'ua':
				$passportZone = 'ru'; break;
			case 'en':
			case 'de':
				$passportZone = 'com'; break;
			default:
				$passportZone = 'com';
		}

		return sprintf(
			'https://passport.yandex.%s/passport?mode=oauth&type=trusted-pdd-partner&error_retpath=%s&access_token=%s',
			$passportZone, urlencode($errorUrl), urlencode($oauthToken)
		);
	}

	public static function deleteUser($token, $login, &$error)
	{
		$result = self::query('https://pddimp.yandex.ru/delete_user.xml', array(
			'token' => $token,
			'login' => $login
		));

		if ($okNode = $result->selectNodes('/page/ok'))
			return true;

		self::setError($result, $error);
		return false;
	}

	public static function getMailInfo($token, $login, &$error)
	{
		$result = self::query('https://pddimp.yandex.ru/get_mail_info.xml', array(
			'token' => $token,
			'login' => $login
		));

		if ($okNode = $result->selectNodes('/page/ok'))
			return $okNode->getAttribute('new_messages');

		self::setError($result, $error);
		return false;
	}

	// post
	public static function getUserInfo($token, $login, &$error)
	{
		$result = self::query('https://pddimp.yandex.ru/get_user_info.xml', array(
			'token' => $token,
			'login' => $login
		));

		if ($userNode = $result->selectNodes('/page/domain/user'))
		{
			$userInfo = array();
			foreach ($userNode->children() as $userFieldNode)
				$userInfo[$userFieldNode->name()] = $userFieldNode->textContent();
			return $userInfo;
		}

		self::setError($result, $error);
		return false;
	}

	public static function editUser($token, $login, $data, &$error)
	{
		$postData = array(
			'token' => $token,
			'login' => $login
		);

		foreach ($data as $key => $value)
		{
			switch ($key)
			{
				case 'password':
				case 'hintq':
				case 'hinta':
					$postData[$key] = (string) $value;
					break;
				case 'domain':
					$postData['domain_name'] = (string) $value;
					break;
				case 'first_name':
					$postData['iname'] = (string) $value;
					break;
				case 'last_name':
					$postData['fname'] = (string) $value;
					break;
				case 'gender':
					$postData['sex'] = (string) $value;
					break;
			}
		}

		$result = self::query('https://pddimp.yandex.ru/edit_user.xml', $postData);

		if ($okNode = $result->selectNodes('/page/ok'))
			return $okNode->getAttribute('uid');

		self::setError($result, $error);
		return false;
	}

	public static function getDomainUsers($token, $per_page = 30, $page = 0, &$error)
	{
		$result = self::query('https://pddimp.yandex.ru/get_domain_users.xml', array(
			'token'   => $token,
			'on_page' => $per_page,
			'page'    => $page
		));

		if ($domainNode = $result->selectNodes('/page/domains/domain'))
		{
			$domainInfo = array();
			foreach ($domainNode->children() as $domainFieldNode)
			{
				if (in_array($domainFieldNode->name(), array('name', 'status')))
					$domainInfo[$domainFieldNode->name()] = $domainFieldNode->textContent();
				if (in_array($domainFieldNode->name(), array('emails-max-count')))
					$domainInfo[$domainFieldNode->name()] = intval($domainFieldNode->textContent());
				if ($domainFieldNode->name() == 'emails')
				{
					$domainInfo['emails'] = array();
					foreach ($domainFieldNode->children() as $domainEmailsNode)
					{
						if (in_array($domainEmailsNode->name(), array('found', 'total')))
							$domainInfo['emails_'.$domainEmailsNode->name()] = $domainEmailsNode->textContent();
						if ($domainEmailsNode->name() == 'email')
						{
							$key = count($domainInfo['emails']);
							foreach ($domainEmailsNode->children() as $emailNode)
								$domainInfo['emails'][$key][$emailNode->name()] = $emailNode->textContent();
						}
					}
				}
			}

			return $domainInfo;
		}

		self::setError($result, $error);
		return false;
	}

	public static function addLogo($token, $domain, $file, &$error)
	{
		$http = new \Bitrix\Main\Web\HttpClient();

		$boundary = 'CMY' . md5(rand().time());

		$data = '';

		$data .= '--' . $boundary . "\r\n";
		$data .= 'Content-Disposition: form-data; name="token"' . "\r\n\r\n";
		$data .= $token . "\r\n";

		$data .= '--' . $boundary . "\r\n";
		$data .= 'Content-Disposition: form-data; name="domain"' . "\r\n\r\n";
		$data .= $domain . "\r\n";

		$data .= '--' . $boundary . "\r\n";
		$data .= 'Content-Disposition: form-data; name="file"; filename="file"' . "\r\n";
		$data .= 'Content-Type: application/octet-stream' . "\r\n\r\n";
		$data .= file_get_contents($file) . "\r\n";

		$data .= '--' . $boundary . "--\r\n";

		$http->setHeader('Content-type', 'multipart/form-data; boundary='.$boundary);
		$http->setHeader('Content-length', CUtil::binStrlen($data));

		$response = $http->post('https://pddimp.yandex.ru/api/add_logo.xml', $data);

		$result = new CDataXML();
		$result->loadString($response);

		if ($logoUrlNode = $result->selectNodes('/action/domains/domain/logo/url'))
			return $logoUrlNode->textContent();

		self::setError2($result, $error);
		return false;
	}

	private static function query($query, $data)
	{
		$http = new \Bitrix\Main\Web\HttpClient();

		$response = $http->post($query, $data);

		$xml = new CDataXML();
		$xml->loadString($response);

		return $xml;
	}

	private static function setError($xml, &$error)
	{
		if ($errorNode = $xml->selectNodes('/page/error'))
			$error = $errorNode->getAttribute('reason');
	}

	private static function setError2($xml, &$error)
	{
		if ($errorNode = $xml->selectNodes('/action/status/error'))
			$error = $errorNode->textContent();
	}

}
