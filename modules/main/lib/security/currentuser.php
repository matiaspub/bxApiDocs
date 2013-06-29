<?php
namespace Bitrix\Main\Security;

use \Bitrix\Main;
use \Bitrix\Main\Type;
use \Bitrix\Main\Config;

class CurrentUser
{
	protected $isAuthenticated = false;
	protected $checked = false;

	protected $userId;

	protected $login;
	protected $email;
	protected $name;
	protected $firstName;
	protected $secondName;
	protected $lastName;
	protected $isAdmin = false;
	protected $isAutoTimezone = false;
	protected $timezone;

	protected $sessionHash;
	protected $storedAuthId;
	protected $authType;

	protected $policy;
	protected $userGroups;

	protected static $defaultGroupPolicy = array(
		"SESSION_TIMEOUT" => 0, //minutes
		"SESSION_IP_MASK" => "0.0.0.0",
		"MAX_STORE_NUM" => 10,
		"STORE_IP_MASK" => "0.0.0.0",
		"STORE_TIMEOUT" => 525600, //60*24*365 minutes
		"CHECKWORD_TIMEOUT" => 525600, //60*24*365 minutes
		"PASSWORD_LENGTH" => false,
		"PASSWORD_UPPERCASE" => "N",
		"PASSWORD_LOWERCASE" => "N",
		"PASSWORD_DIGITS" => "N",
		"PASSWORD_PUNCTUATION" => "N",
		"LOGIN_ATTEMPTS" => 0,
	);

	public function __construct($userId = null)
	{
		if (($userId !== null) && !Type\Int::isInteger($userId))
			throw new Main\ArgumentOutOfRangeException("userId");

		$this->userId = intval($userId);

		$this->isAuthenticated = ($this->userId != null) ? true : false;
		$this->isAdmin = ($this->userId === 1) ? true : false;
		$this->checked = false;

		if ($this->isAuthenticated)
		{
			$this->loadUser();
			$this->loadUserGroups();
			$this->loadUserSecurityPolicy();
		}
		else
		{
			$this->loadUserGroups();
		}
	}

	protected function loadUser()
	{
		if (!$this->isAuthenticated || !isset($this->userId))
			throw new Main\SystemException("Can not load non-authenticated user");

		$connection = Main\Application::getDbConnection();

		$sql =
			"SELECT U.LOGIN, U.EMAIL, U.NAME, U.SECOND_NAME, U.LAST_NAME, U.AUTO_TIME_ZONE, U.TIME_ZONE ".
			"FROM b_user U  ".
			"WHERE U.ID = '".intval($this->userId)."' ";
		$recordset = $connection->query($sql);

		$record = $recordset->fetch();
		if (!$record)
			throw new SecurityException(sprintf("User '%s' is not found", $this->userId));

		$this->login = $record["LOGIN"];
		$this->email = $record["EMAIL"];
		$this->firstName = $record["NAME"];
		$this->secondName = $record["SECOND_NAME"];
		$this->lastName = $record["LAST_NAME"];
		$this->isAutoTimezone = trim($record["AUTO_TIME_ZONE"]);
		$this->timezone = $record["TIME_ZONE"];

		$this->checked = true;
	}

	protected function loadUserGroups()
	{
		$connection = Main\Application::getDbConnection();
		$sqlHelper = $connection->getSqlHelper();

		$this->userGroups = array();

		$sql = "SELECT G.ID FROM b_group G WHERE G.ANONYMOUS = 'Y' AND G.ACTIVE = 'Y'";
		$recordset = $connection->query($sql);
		while ($record = $recordset->fetch())
			$this->userGroups[] = intval($record["ID"]);

		if (!in_array(2, $this->userGroups))
			$this->userGroups[] = 2;

		if ($this->isAuthenticated)
		{
			$sql =
				"SELECT G.ID ".
				"FROM b_user_group UG ".
				"   INNER JOIN b_group G ON (UG.GROUP_ID = G.ID) ".
				"WHERE UG.USER_ID = ".intval($this->userId)." ".
				"	AND G.ACTIVE = 'Y' ".
				"	AND ((UG.DATE_ACTIVE_FROM IS NULL) OR (UG.DATE_ACTIVE_FROM <= ".$sqlHelper->getCurrentDateTimeFunction().")) ".
				"	AND ((UG.DATE_ACTIVE_TO IS NULL) OR (UG.DATE_ACTIVE_TO >= ".$sqlHelper->getCurrentDateTimeFunction().")) ".
				"	AND (G.ANONYMOUS <> 'Y' OR G.ANONYMOUS IS NULL) ";
			$recordset = $connection->query($sql);
			while ($record = $recordset->fetch())
				$this->userGroups[] = intval($record["ID"]);

			$this->userGroups = array_unique($this->userGroups, SORT_NUMERIC);

			$this->isAdmin = in_array(1, $this->userGroups, true);
		}

		sort($this->userGroups, SORT_NUMERIC);
	}

	protected function loadUserSecurityPolicy()
	{
		$this->policy = static::$defaultGroupPolicy;
		if ($this->policy["SESSION_TIMEOUT"] <= 0)
			$this->policy["SESSION_TIMEOUT"] = ini_get("session.gc_maxlifetime") / 60;

		$connection = Main\Application::getDbConnection();
		$sqlHelper = $connection->getSqlHelper();

		$sql =
			"SELECT G.SECURITY_POLICY ".
			"FROM b_group G ".
			"WHERE G.ID = 2 ";

		if ($this->isAuthenticated)
		{
			$sql .=
				"UNION ".
				"SELECT G.SECURITY_POLICY ".
				"FROM b_group G ".
				"   INNER JOIN b_user_group UG ON (G.ID = UG.GROUP_ID) ".
				"WHERE UG.USER_ID = ".intval($this->userId)." ".
				"	AND ((UG.DATE_ACTIVE_FROM IS NULL) OR (UG.DATE_ACTIVE_FROM <= ".$sqlHelper->getCurrentDateTimeFunction().")) ".
				"	AND ((UG.DATE_ACTIVE_TO IS NULL) OR (UG.DATE_ACTIVE_TO >= ".$sqlHelper->getCurrentDateTimeFunction().")) ";
		}

		$recordset = $connection->query($sql);
		while ($record = $recordset->fetch())
		{
			if (!empty($record["SECURITY_POLICY"]))
				$groupPolicy = unserialize($record["SECURITY_POLICY"]);
			else
				continue;

			if (!is_array($groupPolicy))
				continue;

			foreach ($groupPolicy as $key => $val)
			{
				switch ($key)
				{
					case "STORE_IP_MASK":
					case "SESSION_IP_MASK":
						if ($this->policy[$key] < $val)
							$this->policy[$key] = $val;
						break;
					case "SESSION_TIMEOUT":
						if ($this->policy[$key] <= 0 || $this->policy[$key] > $val)
							$this->policy[$key] = $val;
						break;
					case "PASSWORD_LENGTH":
						if ($this->policy[$key] <= 0 || $this->policy[$key] < $val)
							$this->policy[$key] = $val;
						break;
					case "PASSWORD_UPPERCASE":
					case "PASSWORD_LOWERCASE":
					case "PASSWORD_DIGITS":
					case "PASSWORD_PUNCTUATION":
						if ($val === "Y")
							$this->policy[$key] = "Y";
						break;
					case "LOGIN_ATTEMPTS":
						if ($val > 0 && ($this->policy[$key] <= 0 || $this->policy[$key] > $val))
							$this->policy[$key] = $val;
						break;
					default:
						if ($this->policy[$key] > $val)
							$this->policy[$key] = $val;
				}
			}
		}

		if ($this->policy["PASSWORD_LENGTH"] === false)
			$this->policy["PASSWORD_LENGTH"] = 6;
	}

	public function setAuthType($authType)
	{
		$this->authType = $authType;
	}

	public function getAuthType()
	{
		return $this->authType;
	}

	public function setSessionHash($sessionHash)
	{
		$this->sessionHash = $sessionHash;
	}

	public function getSessionHash()
	{
		if (!isset($this->sessionHash))
			$this->sessionHash = md5(uniqid(rand(), true));

		return $this->sessionHash;
	}

	public function setStoredAuthId($storedAuthId)
	{
		$this->storedAuthId = $storedAuthId;
	}

	public function getStoredAuthId()
	{
		return $this->storedAuthId;
	}

	public function isChecked()
	{
		return $this->checked;
	}

	public function getUserGroups()
	{
		if (!isset($this->userGroups))
			$this->loadUserGroups();

		return $this->userGroups;
	}

	public function getEmail()
	{
		if (!isset($this->email))
			$this->loadUser();

		return $this->email;
	}

	public function getFirstName()
	{
		if (!isset($this->firstName))
			$this->loadUser();

		return $this->firstName;
	}

	public function isAdmin()
	{
		return $this->isAdmin;
	}

	public function isAuthenticated()
	{
		return $this->isAuthenticated;
	}

	public function isAutoTimezone()
	{
		if (!isset($this->isAutoTimezone))
			$this->loadUser();

		return $this->isAutoTimezone;
	}

	public function getLastName()
	{
		if (!isset($this->lastName))
			$this->loadUser();

		return $this->lastName;
	}

	public function getSecondName()
	{
		if (!isset($this->secondName))
			$this->loadUser();

		return $this->secondName;
	}

	public function getLogin()
	{
		if (!isset($this->login))
			$this->loadUser();

		return $this->login;
	}

	public function getName()
	{
		if (!isset($this->name))
		{
			$firstName = $this->getFirstName();
			$lastName = $this->getLastName();
			$this->name = $firstName.(strlen($firstName) <= 0 || strlen($lastName) <= 0 ? "" : " ").$lastName;
			if (strlen($this->name) <= 0)
				$this->name = $this->getLogin();
		}
		return $this->name;
	}

	public function setTimezone($timezone)
	{
		$this->timezone = $timezone;
	}

	public function getTimezone()
	{
		if (!isset($this->timezone))
			$this->loadUser();

		return $this->timezone;
	}

	public function getPolicy()
	{
		if (!isset($this->policy))
			$this->loadUserSecurityPolicy();

		return $this->policy;
	}

	public function getUserId()
	{
		return $this->userId;
	}

	public function isInGroup($groupId)
	{
		if (empty($groupId))
			throw new Main\ArgumentNullException("groupId");
		if (!Type\Int::isInteger($groupId))
			throw new Main\ArgumentTypeException("groupId", "int");

		$groupId = intval($groupId);
		if ($groupId == 2)
			return true;

		if (!isset($this->userGroups))
			$this->loadUserGroups();

		return in_array($groupId, $this->userGroups);
	}

	public static function createFromArray(array $data)
	{
		if (empty($data))
			throw new Main\ArgumentNullException("data");

		if (!isset($data["USER_ID"]) || !Main\Type\Int::isInteger($data["USER_ID"]))
			throw new Main\ArgumentOutOfRangeException("data");

		if (isset($data["AUTHORIZED"]) && ($data["AUTHORIZED"] != "Y"))
			throw new SecurityException();

		$user = new static($data["USER_ID"]);

		$ar = array("LOGIN" => "login", "EMAIL" => "email", "FIRST_NAME" => "firstName",
			"SECOND_NAME" => "secondName", "LAST_NAME" => "lastName", "ADMIN" => "isAdmin",
			"TIME_ZONE" => "timezone");
		foreach ($ar as $k => $v)
		{
			if (isset($data[$k]))
				$user->{$v} = $data[$k];
		}

		if (isset($data["AUTO_TIME_ZONE"]))
			$user->isAutoTimezone = ($data["AUTO_TIME_ZONE"] == "Y");

		if (isset($data["POLICY"]))
			$user->policy = $data["POLICY"];
		if (isset($data["GROUPS"]))
			$user->userGroups = $data["GROUPS"];

//		$_SESSION["SESS_AUTH"]["CONTROLLER_ADMIN"] = false;
//		$_SESSION["SESS_AUTH"]["STORED_AUTH_ID"] = $stored_id;

		return $user;
	}

	public function exportToArray()
	{
		$data = array(
			"AUTHORIZED" => $this->isAuthenticated ? "Y" : "N",
			"ADMIN" => $this->isAdmin,
		);

		if ($this->isAuthenticated)
		{
			$data["USER_ID"] = $this->userId;

			$ar = array("LOGIN" => "login", "EMAIL" => "email", "FIRST_NAME" => "firstName",
				"SECOND_NAME" => "secondName", "LAST_NAME" => "lastName", "AUTO_TIME_ZONE" => "isAutoTimezone",
				"TIME_ZONE" => "timezone");
			foreach ($ar as $k => $v)
			{
				if (isset($this->{$v}))
					$data[$k] = $this->{$v};
			}

			if (isset($data["AUTO_TIME_ZONE"]))
				$data["AUTO_TIME_ZONE"] = ($data["AUTO_TIME_ZONE"] ? "Y" : "N");
		}

		if (isset($this->policy))
			$data["POLICY"] = $this->policy;
		if (isset($this->userGroups))
			$data["GROUPS"] = $this->userGroups;

		return $data;
	}
}
