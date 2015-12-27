<?php

/**
 * yandex errors:
 * - no_auth (токен кривой, короткий)
 * - not_permitted (токен кривой, длинный или неправильный)
 * - occupied (логин занят)
 * - no_user (нет пользователя)
 * - no_login (не передан логин)
 * - not_found (нет пользователя)
 */


class CMailDomain
{

public static 	public function __construct()
	{
	}

public static 	public static function isUserExists($token, $domain, $login, &$error)
	{
		$result = CMailYandex::checkUser($token, $login, $error);

		switch ($result)
		{
			case 'exists':
				return true;
			case 'nouser':
			case 'no_user':
				return false;
			default:
				$error = self::getErrorCode($error);
				return null;
		}
	}

public static 	public static function addUser($token, $domain, $login, $password, &$error)
	{
		$result = CMailYandex::registerUserToken($token, $login, $password, $error);

		if ($result !== false)
		{
			return true;
		}
		else
		{
			$error = self::getErrorCode($error);
			return null;
		}
	}

public static 	public static function getRedirectUrl($locale, $token, $domain, $login, $errorUrl, &$error)
	{
		$result = CMailYandex::userOAuthToken($token, $domain, $login, $error);

		if ($result !== false)
		{
			return CMailYandex::passport($locale, $result, $errorUrl);
		}
		else
		{
			$error = self::getErrorCode($error);
			return null;
		}
	}

public static 	public static function getUnreadMessagesCount($token, $domain, $login, &$error)
	{
		$result = CMailYandex::getMailInfo($token, $login, $error);

		if ($result !== false)
		{
			return $result;
		}
		else
		{
			$error = self::getErrorCode($error);
			return null;
		}
	}

public static 	public static function changePassword($token, $domain, $login, $password, &$error)
	{
		$result = CMailYandex::editUser($token, $login, array('domain' => $domain, 'password' => $password), $error);

		if ($result !== false)
		{
			return true;
		}
		else
		{
			$error = self::getErrorCode($error);
			return null;
		}
	}

public static 	public static function getDomainStatus($token, $domain, &$error)
	{
		$result = CMailYandex::getDomainUsers($token, 1, 1, $error);

		if ($result !== false)
		{
			if (strtolower($result['name']) == $domain)
			{
				return array(
					'domain' => $result['name'],
					'stage'  => $result['status']
				);
			}
			else
			{
				$error = self::getErrorCode('not_permitted');
				return null;
			}
		}
		else
		{
			$error = self::getErrorCode($error);
			return null;
		}
	}

public static 	public static function getDomainUsers($token, $domain, &$error)
	{
		$users = array();

		$page = 0;
		do
		{
			$result = CMailYandex::getDomainUsers($token, $per_page = 30, ++$page, $error);

			if ($result === false)
				break;

			foreach ($result['emails'] as $email)
			{
				list($login, $emailDomain) = explode('@', $email['name'], 2);
				if ($emailDomain == $domain)
					$users[] = $login;
			}
		}
		while ($result['emails_total'] > $per_page*$page);

		if (empty($users) && $error)
		{
			$error = self::getErrorCode($error);
			return null;
		}
		else
		{
			sort($users);
			return $users;
		}
	}

public static 	public static function setDomainLogo($token, $domain, $logo, &$error)
	{
		$result = CMailYandex::addLogo($token, $domain, $logo, $error);

		if ($result !== false)
		{
			return $result;
		}
		else
		{
			$error = self::getErrorCode($error);
			return null;
		}
	}

public static 	public static function deleteUser($token, $domain, $login, &$error)
	{
		$result = CMailYandex::deleteUser($token, $login, $error);

		if ($result !== false)
		{
			return true;
		}
		else
		{
			$error = self::getErrorCode($error);
			return null;
		}
	}

public static 	private static function getErrorCode($error)
	{
		$errorsList = array(
			'no_auth'          => CMail::ERR_API_DENIED,
			'not_permitted'    => CMail::ERR_API_DENIED,
			'occupied'         => CMail::ERR_API_NAME_OCCUPIED,
			'no_user'          => CMail::ERR_API_USER_NOTFOUND,
			'not_found'        => CMail::ERR_API_USER_NOTFOUND,
			'no_login'         => CMail::ERR_API_EMPTY_NAME,
			'login-toolong'    => CMail::ERR_API_LONG_NAME,
			'badlogin'         => CMail::ERR_API_BAD_NAME,
			'passwd-empty'     => CMail::ERR_API_EMPTY_PASSWORD,
			'passwd-tooshort'  => CMail::ERR_API_SHORT_PASSWORD,
			'passwd-toolong'   => CMail::ERR_API_LONG_PASSWORD,
			'passwd-likelogin' => CMail::ERR_API_PASSWORD_LIKELOGIN,
			'badpasswd'        => CMail::ERR_API_BAD_PASSWORD
		);

		$error = explode(',', $error);
		$error = trim($error[count($error)-1]);

		return array_key_exists($error, $errorsList) ? $errorsList[$error] : CMail::ERR_API_DEFAULT;
	}

}
