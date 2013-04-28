<?php
namespace Bitrix\Main\Security;

use \Bitrix\Main;
use \Bitrix\Main\Config;

class Authentication
{
	protected static $lastError;

	const AUTHENTICATED_BY_SESSION = 1;
	const AUTHENTICATED_BY_HASH = 2;
	const AUTHENTICATED_BY_PASSWORD = 3;

	public static function checkSessionSecurity(CurrentUser $user)
	{
		$context = \Bitrix\Main\Application::getInstance()->getContext();
		if (!($context instanceof \Bitrix\Main\HttpContext))
			throw new \Bitrix\Main\NotSupportedException();

		$policy = $user->getPolicy();
		$currentTime = time();

		/** @var $request \Bitrix\Main\HttpRequest */
		$request = $context->getRequest();
		$remoteAddress = $request->getRemoteAddress();

		// IP address changed
		$destroySession = $_SESSION['SESS_IP']
			&& strlen($policy["SESSION_IP_MASK"]) > 0
			&& (
				(ip2long($policy["SESSION_IP_MASK"]) & ip2long($_SESSION['SESS_IP']))
				!=
				(ip2long($policy["SESSION_IP_MASK"]) & ip2long($remoteAddress))
			);

		// session timeout
		if (!$destroySession)
			$destroySession = $policy["SESSION_TIMEOUT"] > 0
				&& $_SESSION['SESS_TIME'] > 0
				&& $currentTime - $policy["SESSION_TIMEOUT"] * 60 > $_SESSION['SESS_TIME'];

		// session expander control
		if (!$destroySession)
			$destroySession = $_SESSION["BX_SESSION_TERMINATE_TIME"] > 0
				&& $currentTime > $_SESSION["BX_SESSION_TERMINATE_TIME"];

		if ($destroySession)
		{
			$_SESSION = array();
			@session_destroy();

			//session_destroy cleans user session handles in some PHP versions
			//see http://bugs.php.net/bug.php?id=32330 discussion
			if (
				Config\Option::get("security", "session", "N") === "Y"
				&& Main\Loader::includeModule("security"))
			{
				\CSecuritySession::init();
			}

			session_id(md5(uniqid(rand(), true)));
			session_start();
		}
		$_SESSION['SESS_IP'] = $remoteAddress;
		$_SESSION['SESS_TIME'] = time();

		//session control from security module
		if (
			(Config\Option::get("main", "use_session_id_ttl", "N") == "Y")
			&& (intval(Config\Option::get("main", "session_id_ttl", 0)) > 0)
			&& !defined("BX_SESSION_ID_CHANGE")
		)
		{
			if (!array_key_exists('SESS_ID_TIME', $_SESSION))
			{
				$_SESSION['SESS_ID_TIME'] = $_SESSION['SESS_TIME'];
			}
			elseif (($_SESSION['SESS_ID_TIME'] + intval(Config\Option::get("main", "session_id_ttl"))) < $_SESSION['SESS_TIME'])
			{
				if (Config\Option::get("security", "session", "N") === "Y"
					&& \Bitrix\Main\Loader::includeModule("security"))
				{
					\CSecuritySession::updateSessID();
				}
				else
				{
					session_regenerate_id();
				}
				$_SESSION['SESS_ID_TIME'] = $_SESSION['SESS_TIME'];
			}
		}

		return !$destroySession;
	}

	public static function getUserByCookie()
	{
		if (Config\Option::get("main", "store_password", "Y") != "Y")
			return null;

		$context = Main\Application::getInstance()->getContext();
		/** @var $request \Bitrix\Main\HttpRequest */
		$request = $context->getRequest();

		$cookieLogin = $request->getCookie('LOGIN');
		$cookieMd5Pass = $request->getCookie('UIDH');

		if (!empty($cookieLogin) && !empty($cookieMd5Pass)
			&& $_SESSION["SESS_PWD_HASH_TESTED"] != md5($cookieLogin."|".$cookieMd5Pass))
		{
			$user = static::getUserByHash($cookieLogin, $cookieMd5Pass);
			$_SESSION["SESS_PWD_HASH_TESTED"] = md5($cookieLogin."|".$cookieMd5Pass);

			return $user;
		}

		return null;
	}

	public static function getUserBySession()
	{
		if (isset($_SESSION["SESS_AUTH"]) && is_array($_SESSION["SESS_AUTH"]))
		{
			if ($_SESSION["SESS_AUTH"]["AUTHORIZED"] === "Y")
			{
				$user = CurrentUser::createFromArray($_SESSION["SESS_AUTH"]);
				$user->setAuthType(static::AUTHENTICATED_BY_SESSION);
				return $user;
			}
		}

		return null;
	}

	public static function copyToSession(CurrentUser $user)
	{
		$_SESSION["SESS_AUTH"] = $user->exportToArray();
	}

	protected static function getStoredHashId(CurrentUser $user, $hash, $onlyTempHash = false)
	{
		if (!$user->isAuthenticated())
			throw new SecurityException();

		$context = Main\Application::getInstance()->getContext();
		if (!($context instanceof \Bitrix\Main\HttpContext))
			throw new Main\NotSupportedException();

		/** @var $request \Bitrix\Main\HttpRequest */
		$request = $context->getRequest();
		$remoteAddress = $request->getRemoteAddress();

		$policy = $user->getPolicy();

		$cnt = 0;
		$hashId = null;

		$connection = Main\Application::getDbConnection();

		$sql =
			"SELECT A.* ".
			"FROM b_user_stored_auth A ".
			"WHERE A.USER_ID = ".intval($user->getUserId())." ".
			"ORDER BY A.LAST_AUTH DESC";
		$recordset = $connection->query($sql);
		while ($record = $recordset->fetch())
		{
			if ($record["TEMP_HASH"] === "N")
				$cnt++;

			/** @var $lastAuth \Bitrix\Main\Type\DateTime */
			$lastAuth = $record["LAST_AUTH"];
			if ($policy["MAX_STORE_NUM"] < $cnt
				|| ($record["TEMP_HASH"] === "N" && mktime() - $policy["STORE_TIMEOUT"] * 60 > $lastAuth->getTimestamp())
				|| ($record["TEMP_HASH"] === "Y" && mktime() - $policy["SESSION_TIMEOUT"] * 60 > $lastAuth->getTimestamp())
			)
			{
				$connection->queryExecute("DELETE FROM b_user_stored_auth WHERE ID = ".$record["ID"]);
			}
			elseif (!$hashId)
			{
				//for domain spreaded external auth we should check only temporary hashes
				if ($onlyTempHash === false || $record["TEMP_HASH"] === "Y")
				{
					$remoteNet = ip2long($policy["STORE_IP_MASK"]) & ip2long($remoteAddress);
					$storedNet = ip2long($policy["STORE_IP_MASK"]) & (float)$record["IP_ADDR"];
					if ($hash === $record["STORED_HASH"] && $remoteNet === $storedNet)
						$hashId = $record["ID"];
				}
			}
		}
		return $hashId;
	}

	public static function getUserByHash($login, $hash)
	{
		if (empty($login))
			throw new Main\ArgumentNullException("login");
		if (empty($hash))
			throw new Main\ArgumentNullException("hash");

		$event = new Main\Event(
			"main",
			"OnBeforeUserLoginByHash",
			array("LOGIN" => $login, "HASH" => $hash)
		);
		$event->send();
		if (($eventResults = $event->getResults()) !== null)
		{
			foreach ($eventResults as $eventResult)
			{
				if ($eventResult->getResultType() === Main\EventResult::ERROR)
					return null;
			}
		}

		$connection = Main\Application::getDbConnection();
		$sqlHelper = $connection->getSqlHelper();

		$sql =
			"SELECT U.ID, U.EXTERNAL_AUTH_ID ".
			"FROM b_user U ".
			"WHERE U.LOGIN = '".$sqlHelper->forSql($login, 50)."' ".
			"   AND U.ACTIVE = 'Y' ";

		$isFound = false;
		$user = null;

		$recordset = $connection->query($sql);
		while ($record = $recordset->fetch())
		{
			$user = new CurrentUser($record["ID"]);
			$isExternal = ($record["EXTERNAL_AUTH_ID"] != '');

			$storedHashId = static::getStoredHashId($user, $hash, $isExternal);
			if ($storedHashId)
			{
				$isFound = true;
				$user->setSessionHash($hash);
				$user->setAuthType(static::AUTHENTICATED_BY_HASH);
				break;
			}

			unset($user);
		}

		$isFound = $isFound && isset($user) && ($user instanceof CurrentUser);

		$event = new \Bitrix\Main\Event(
			"main",
			"OnAfterUserLoginByHash",
			array(
				"LOGIN" => $login,
				"HASH" => $hash,
				"USER_ID" => ($isFound && isset($user) ? $user->getUserId() : null)
			)
		);
		$event->send();

		if (!$isFound && (Config\Option::get("main", "event_log_login_fail", "N") === "Y"))
			\CEventLog::log("SECURITY", "USER_LOGINBYHASH", "main", $login, "Unknown error");

		return $isFound && isset($user) ? $user : null;
	}

	public static function loginByHttp()
	{

	}

	public static function getUserByPassword($login, $password, $passwordIsOriginal = true)
	{
		if (empty($login))
			throw new Main\ArgumentNullException("login");

		$event = new Main\Event(
			"main",
			"OnBeforeUserLogin",
			array(array("LOGIN" => $login, "PASSWORD" => $password, "PASSWORD_ORIGINAL" => $passwordIsOriginal))
		);
		$event->send();
		if (($eventResults = $event->getResults()) !== null)
		{
			foreach ($eventResults as $eventResult)
			{
				if ($eventResult->getResultType() === Main\EventResult::ERROR)
				{
					static::$lastError = $eventResult->getParameters();
					return null;
				}
				elseif ($eventResult->getResultType() === Main\EventResult::SUCCESS)
				{
					if (($resultParams = $eventResult->getParameters()) && is_array($resultParams))
					{
						if (isset($resultParams["LOGIN"]))
							$login = $resultParams["LOGIN"];
						if (isset($resultParams["PASSWORD"]))
							$password = $resultParams["PASSWORD"];
						if (isset($resultParams["PASSWORD_ORIGINAL"]))
							$passwordIsOriginal = $resultParams["PASSWORD_ORIGINAL"];
					}
				}
			}
		}

		$user = null;

		$event = new Main\Event(
			"main",
			"OnUserLoginExternal",
			array(array("LOGIN" => $login, "PASSWORD" => $password, "PASSWORD_ORIGINAL" => $passwordIsOriginal))
		);
		$event->send();
		if (($eventResults = $event->getResults()) !== null)
		{
			foreach ($eventResults as $eventResult)
			{
				if ($eventResult->getResultType() === Main\EventResult::SUCCESS)
				{
					$userId = $eventResult->getParameters();
					if (!Main\Type\Int::isInteger($userId))
						throw new SecurityException();

					$user = new CurrentUser($userId);
					break;
				}
			}
		}

		$connection = Main\Application::getDbConnection();
		$sqlHelper = $connection->getSqlHelper();

		if (is_null($user))
		{
			$sql =
				"SELECT U.ID, U.PASSWORD, U.LOGIN_ATTEMPTS ".
				"FROM b_user U  ".
				"WHERE U.LOGIN = '".$sqlHelper->forSql($login)."' ".
				"	AND (U.EXTERNAL_AUTH_ID IS NULL OR U.EXTERNAL_AUTH_ID = '') ".
				"   AND U.ACTIVE = 'Y' ";
			$userRecordset = $connection->query($sql);
			if ($userRecord = $userRecordset->fetch())
			{
				$userTmp = new CurrentUser($userRecord["ID"]);

				$salt = substr($userRecord["PASSWORD"], 0, -32);
				$passwordFromDb = substr($userRecord["PASSWORD"], -32);

				if ($passwordIsOriginal)
					$passwordFromUser = md5($salt.$password);
				else
					$passwordFromUser = (strlen($password) > 32) ? substr($password, -32) : $password;

				$policy = $userTmp->getPolicy();
				$policyLoginAttempts = intval($policy["LOGIN_ATTEMPTS"]);
				$userLoginAttempts = intval($userRecord["LOGIN_ATTEMPTS"]) + 1;
				if ($policyLoginAttempts > 0 && $userLoginAttempts > $policyLoginAttempts)
				{
//					$_SESSION["BX_LOGIN_NEED_CAPTCHA"] = true;
//					if (!$APPLICATION->captchaCheckCode($_REQUEST["captcha_word"], $_REQUEST["captcha_sid"]))
//					{
//						$passwordUser = false;
//					}
				}

				if ($passwordFromDb === $passwordFromUser)
				{
					$user = $userTmp;

					//update digest hash for http digest authorization
					if ($passwordIsOriginal && Main\Config\Option::get('main', 'use_digest_auth', 'N') == 'Y')
						static::updateDigest($user->getUserId(), $password);
				}
				else
				{
					$connection->query(
						"UPDATE b_user SET ".
						"   LOGIN_ATTEMPTS = ".$userLoginAttempts." ".
						"WHERE ID = ".intval($userRecord["ID"])
					);
				}
			}
		}

		if (is_null($user))
		{
			if ((Main\Config\Option::get("main", "event_log_login_fail", "N") === "Y"))
				\CEventLog::log("SECURITY", "USER_LOGIN", "main", $login, "LOGIN_FAILED");

			return null;
		}

		if ($user->getUserId() !== 1)
		{
			$limitUsersCount = intval(Main\Config\Option::get("main", "PARAM_MAX_USERS", 0));
			if ($limitUsersCount > 0)
			{
				$usersCount = Main\UserTable::getActiveUsersCount();
				if ($usersCount > $limitUsersCount)
				{
					$sql = "SELECT 'x' ".
						"FROM b_user ".
						"WHERE ACTIVE = 'Y' ".
						"   AND ID = ".intval($user->getUserId())." ".
						"   AND LAST_LOGIN IS NULL ";
					$recordset = $connection->query($sql);
					if ($recordset->fetch())
					{
						$user = null;
						static::$lastError = array(
							"CODE" => "LIMIT_USERS_COUNT",
							"MESSAGE" => Main\Localization\Loc::getMessage("LIMIT_USERS_COUNT"),
						);
					}
				}
			}
		}

		if (is_null($user))
		{
			if ((Main\Config\Option::get("main", "event_log_login_fail", "N") === "Y"))
				\CEventLog::log("SECURITY", "USER_LOGIN", "main", $login, "LIMIT_USERS_COUNT");

			return null;
		}

		$user->setAuthType(static::AUTHENTICATED_BY_PASSWORD);

		$event = new \Bitrix\Main\Event(
			"main",
			"OnAfterUserLogin",
			array(array(
				"LOGIN" => $login, "PASSWORD" => $password, "PASSWORD_ORIGINAL" => $passwordIsOriginal,
				"USER_ID" => $user->getUserId()
			))
		);
		$event->send();

		return $user;
	}

	protected function updateDigest($userId, $password)
	{
		if (empty($userId))
			throw new Main\ArgumentNullException("userId");
		if (!Main\Type\Int::isInteger($userId))
			throw new Main\ArgumentTypeException("userId");

		$userId = intval($userId);

		$connection = Main\Application::getDbConnection();
		$sqlHelper = $connection->getSqlHelper();

		$recordset = $connection->query(
			"SELECT U.LOGIN, UD.DIGEST_HA1 ".
			"FROM b_user U ".
			"   LEFT JOIN b_user_digest UD on U.ID = UD.USER_ID ".
			"WHERE U.ID = ".$userId
		);
		if ($record = $recordset->fetch())
		{
			$realm = Main\Config\Configuration::getValue("http_auth_realm");
			if (is_null($realm))
				$realm = "Bitrix Site Manager";

			$digest = md5($record["LOGIN"].':'.$realm.':'.$password);

			if ($record["DIGEST_HA1"] == '')
			{
				//new digest
				$connection->queryExecute(
					"INSERT INTO b_user_digest (USER_ID, DIGEST_HA1) ".
					"VALUES('".$userId."', '".$sqlHelper->forSql($digest)."')"
				);
			}
			else
			{
				//update digest (login, password or realm were changed)
				if ($record["DIGEST_HA1"] !== $digest)
				{
					$connection->queryExecute(
						"UPDATE b_user_digest SET ".
						"   DIGEST_HA1 = '".$sqlHelper->forSql($digest)."' ".
						"WHERE USER_ID = ".$userId
					);
				}
			}
		}
	}

	public static function setAuthentication(CurrentUser $user, $isPersistent = false)
	{
		/** @var $context \Bitrix\Main\HttpContext */
		$context = \Bitrix\Main\Application::getInstance()->getContext();
		$context->setUser($user);

		static::copyToSession($user);

		/** @var $response \Bitrix\Main\HttpResponse */
		$response = $context->getResponse();

		if (!$user->isAuthenticated())
		{
			$cookie = new \Bitrix\Main\Web\Cookie("UIDH", "", time() - 3600);
			$response->addCookie($cookie);
			return;
		}

		$connection = \Bitrix\Main\Application::getDbConnection();
		$sqlHelper = $connection->getSqlHelper();

		$connection->queryExecute(
			"UPDATE b_user SET ".
			"   STORED_HASH = NULL, ".
			"   LAST_LOGIN = ".$sqlHelper->getCurrentDateTimeFunction().", ".
			"   TIMESTAMP_X = TIMESTAMP_X,  ".
			"   LOGIN_ATTEMPTS = 0, ".
			"   TIME_ZONE_OFFSET = ".\CTimeZone::getOffset()." ".
			"WHERE ID = ".$user->getUserId()." "
		);

		$cookie = new \Bitrix\Main\Web\Cookie("LOGIN", $user->getLogin(), time()+60*60*24*30*60);
		$cookie->setSpread((\Bitrix\Main\Config\Option::get("main", "auth_multisite", "N") == "Y") ? \Bitrix\Main\Web\Cookie::SPREAD_SITES : \Bitrix\Main\Web\Cookie::SPREAD_DOMAIN);
		$response->addCookie($cookie);

		if ($isPersistent || \Bitrix\Main\Config\Option::get("main", "auth_multisite", "N") == "Y")
		{
			$hash = $user->getSessionHash();

			/** @var $request \Bitrix\Main\HttpRequest */
			$request = $context->getRequest();

			if ($isPersistent)
				$cookie = new \Bitrix\Main\Web\Cookie("UIDH", $hash, time() + 60 * 60 * 24 * 30 * 60);
			else
				$cookie = new \Bitrix\Main\Web\Cookie("UIDH", $hash, 0);

			$cookie->setSecure(\Bitrix\Main\Config\Option::get("main", "use_secure_password_cookies", "N") == "Y" && $request->isHttps());
			$response->addCookie($cookie);

			$storedId = static::getStoredHashId($user, $hash);
			if ($storedId)
			{
				$connection->queryExecute(
					"UPDATE b_user_stored_auth SET ".
					"	LAST_AUTH = ".$sqlHelper->getCurrentDateTimeFunction().", ".
					"	".(($user->getAuthType() === static::AUTHENTICATED_BY_HASH) ? "" : "TEMP_HASH='".($isPersistent ? "N" : "Y")."', ")." ".
					"	IP_ADDR = '".sprintf("%u", ip2long($request->getRemoteAddress()))."' ".
					"WHERE ID = ".intval($storedId)
				);
			}
			else
			{
				$sqlTmp1 = "";
				$sqlTmp2 = "";
				if ($connection->getType() === "oracle")
				{
					$storedId = $connection->getIdentity("sq_b_user_stored_auth");
					$sqlTmp1 = "ID, ";
					$sqlTmp2 = intval($storedId).", ";
				}

				$sql =
					"INSERT INTO b_user_stored_auth (".$sqlTmp1."USER_ID, DATE_REG, LAST_AUTH, TEMP_HASH, ".
					"   IP_ADDR, STORED_HASH) ".
					"VALUES (".$sqlTmp2.intval($user->getUserId()).", ".$sqlHelper->getCurrentDateTimeFunction().", ".
					"   ".$sqlHelper->getCurrentDateTimeFunction().", '".($isPersistent ? "N" : "Y")."', ".
					"   '".$sqlHelper->forSql(sprintf("%u", ip2long($request->getRemoteAddress())))."', ".
					"   '".$sqlHelper->forSql($hash)."')";
				$connection->queryExecute($sql);

				if ($connection->getType() !== "oracle")
					$storedId = $connection->getIdentity();
			}

			$user->setStoredAuthId($storedId);
		}

		$event = new Main\Event(
			"main",
			"OnUserLogin",
			array("USER" => $user)
		);
		$event->send();

		if (\Bitrix\Main\Config\Option::get("main", "event_log_login_success", "N") === "Y")
			\CEventLog::log("SECURITY", "USER_AUTHORIZE", "main", $user->getUserId());
	}

	public static function getLastError()
	{
		return static::$lastError;
	}
}
