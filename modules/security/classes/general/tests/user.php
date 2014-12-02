<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage security
 * @copyright 2001-2013 Bitrix
 */

/**
 * Class CSecurityUserTest
 * @since 14.0.3
 */
class CSecurityUserTest
	extends CSecurityBaseTest
{
	protected $internalName = 'UsersTest';
	/** @var CSecurityTemporaryStorage */
	protected $sessionData = null;
	protected $maximumExecutionTime = 0.0;
	protected $savedMaxExecutionTime = 0.0;

	public function __construct()
	{
		IncludeModuleLangFile(__FILE__);
		$this->savedMaxExecutionTime = ini_get("max_execution_time");
		if($this->savedMaxExecutionTime <= 0)
			$phpMaxExecutionTime = 30;
		else
			$phpMaxExecutionTime = $this->savedMaxExecutionTime - 2;
		$this->maximumExecutionTime = time() + $phpMaxExecutionTime;
		set_time_limit(0);
	}

	public function __destruct()
	{
		set_time_limit($this->savedMaxExecutionTime);
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function check($params = array())
	{
		$this->initializeParams($params);
		$testID = $this->getParam('TEST_ID', $this->internalName);
		$sessionData = new CSecurityTemporaryStorage($testID);

		if (!$sessionData->isExists('current_user'))
		{
			$userId = static::getNextUserId(0);
			$passwordId = 0;
		}
		else
		{
			$userId = $sessionData->getInt('current_user');
			$passwordId = $sessionData->getInt('current_password');
		}

		if ($userId > 0)
		{
			$userChecked = true;
			$passwordDictionary = static::getPasswordDictionary();
			list($salt, $password) = $this->getUserPassword($userId);
			for ($i = $passwordId, $max = count($passwordDictionary); $i < $max; $i++)
			{
				if ($this->isTimeOut())
				{
					$sessionData->setData('current_password', $i);
					$userChecked = false;
					break;
				}
				if (static::isUserPassword($salt, $password, $passwordDictionary[$i]))
				{
					$sessionData->pushToArray('weak_users', $userId);
					break;
				}
			}

			if ($userChecked)
				$sessionData->setData('current_user', static::getNextUserId($userId));
			else
				$sessionData->setData('current_user', $userId);

			$result = array(
				'name' => $this->getName(),
				'timeout' => 1,
				'in_progress' => true
			);
		}
		else
		{
			$weakUsers = $sessionData->getArray('weak_users');
			$sessionData->flushData();
			$result = array(
				'name' => $this->getName(),
				'problem_count' => !empty($weakUsers)? 1: 0,
				'errors' => array(
					array(
						'title' => GetMessage('SECURITY_SITE_CHECKER_ADMIN_WEAK_PASSWORD'),
						'critical' => CSecurityCriticalLevel::HIGHT,
						'detail' => GetMessage('SECURITY_SITE_CHECKER_ADMIN_WEAK_PASSWORD_DETAIL'),
						'recommendation' => $result = GetMessage('SECURITY_SITE_CHECKER_ADMIN_WEAK_PASSWORD_RECOMMENDATIONS'),
						'additional_info' => static::formatRecommendation($weakUsers)
					)
				),
				'status' => empty($weakUsers)
			);
		}

		return $result;
	}

	protected function checkOtp()
	{
		if (IsModuleInstalled('intranet')) //OTP not used in Bitrix Intranet Portal
			return;

		if (CSecurityUser::isActive())
		{
			$dbUser = $this->getAdminUserList();
			while ($user = $dbUser->fetch())
			{
				$userInfo = CSecurityUser::getSecurityUserInfo($user['ID']);
				if (!$userInfo)
				{
					$this->addUnformattedDetailError('SECURITY_SITE_CHECKER_ADMIN_OTP_NOT_USED', CSecurityCriticalLevel::MIDDLE);
				}
			}
		}
		else
		{
			$this->addUnformattedDetailError('SECURITY_SITE_CHECKER_OTP_NOT_USED', CSecurityCriticalLevel::MIDDLE);
		}
	}

	/**
	 * @param array $weakUsers
	 * @return string
	 */
	protected static function formatRecommendation(array $weakUsers)
	{
		$result = getMessage('SECURITY_SITE_CHECKER_ADMIN_WEAK_PASSWORD_USER_LIST');
		foreach (static::getUsersLogins($weakUsers) as $id => $login)
		{
			$result .= sprintf(
				'<br><a href="/bitrix/admin/user_edit.php?ID=%d" target="_blank">%s<a/>',
				$id, $login
			);
		}

		return $result;
	}

	/**
	 * @param int $id
	 * @return array
	 */
	protected static function getUserPassword($id)
	{
		$dbUser = CUser::GetList(
			$by = 'ID',
			$order = 'ASC',
			array(
				'ID' => $id,
				'ACTIVE' => 'Y'
			),
			array(
				'FIELDS' => 'PASSWORD'
			)
		);

		$salt = '';
		$password = '';
		if ($dbUser)
		{
			$user = $dbUser->fetch();
			$password = $user['PASSWORD'];
			$salt = '';
			if (strlen($password) > 32)
			{
				$salt = substr($password, 0, strlen($password) - 32);
				$password = substr($password, -32);
			}
		}

		return array($salt, $password);
	}

	/**
	 * @param int $id
	 * @return int
	 */
	protected static function getNextUserId($id)
	{
		$result = 0;
		$users = static::getAdminUserList(1, $id);
		if ($user = $users->fetch())
			$result = $user['ID'];

		return $result;
	}

	/**
	 * @param int[] $ids
	 * @return array
	 */
	protected static function getUsersLogins(array $ids)
	{
		$dbUser = CUser::GetList(
			$by = 'ID',
			$order = 'ASC',
			array(
				'ID' => implode('|', $ids),
				'ACTIVE' => 'Y'
			),
			array(
				'FIELDS' => 'LOGIN'
			)
		);

		$result = array();
		if ($dbUser)
		{
			while ($user = $dbUser->fetch())
			{
				$result[$user['ID']] = $user['LOGIN'];
			}
		}

		return $result;
	}

	/**
	 * @param string $salt
	 * @param string $hash
	 * @param string $password
	 * @return bool
	 */
	protected static function isUserPassword($salt, $hash, $password)
	{
		return ($hash === md5($salt.$password));
	}

	protected static function getPasswordDictionary()
	{
		static $passwords = null;

		if (is_null($passwords))
			$passwords = file($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/security/data/passwordlist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		return $passwords;
	}

	/**
	 * @param int $limit
	 * @param int $minId
	 * @return CDBResult
	 */
	protected static function getAdminUserList($limit = 0, $minId = 0)
	{
		$dbUser = CUser::GetList(
			$by = 'ID',
			$order = 'ASC',
			array(
				'GROUPS_ID' => 1,
				'>ID' => $minId,
				'ACTIVE' => 'Y'
			),
			array(
				'FIELDS' => 'ID',
				'NAV_PARAMS' => array(
					'nTopCount' => $limit
				)
			)
		);

		if ($dbUser)
			return $dbUser;
		else
			return new CDBResult(array());
	}

	/**
	 * @return bool
	 */
	protected function isTimeOut()
	{
		return (time() >= $this->maximumExecutionTime);
	}
}