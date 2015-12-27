<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage bitrix24
 * @copyright 2001-2015 Bitrix
 */

namespace Bitrix\Socialservices;

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Event;

Loc::loadMessages(__FILE__);

/**
 * Integration with Bitrix24.Network
 * @package bitrix
 * @subpackage socialservices
 */
class Network
{
	const ERROR_SEARCH_STRING_TO_SHORT = 'ERROR_SEARCH_STRING_TO_SHORT';
	const ERROR_SEARCH_USER_NOT_FOUND = 'ERROR_SEARCH_USER_NOT_FOUND';
	const ERROR_REGISTER_USER = 'ERROR_REGISTER_USER';
	const ERROR_SOCSERV_TRANSPORT = 'ERROR_SOCSERV_TRANSPORT';
	const ERROR_NETWORK_IN_NOT_ENABLED = 'ERROR_NETWORK_IN_NOT_ENABLED';
	const ERROR_INCORRECT_PARAMS = 'ERROR_INCORRECT_PARAMS';

	const EXTERNAL_AUTH_ID = 'replica';

	/** @var  ErrorCollection */
	public $errorCollection = null;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection();
	}

	/**
	 * @return boolean
	 */
	static public function isEnabled()
	{
		return Option::get('socialservices', 'network_enable', 'N') == 'Y';
	}

	/**
	 * Enables network communication. Returns true on success.
	 *
	 * @param boolean $enable Pass true to enable and false to disable.
	 *
	 * @return boolean
	 */
	public function setEnable($enable = true)
	{
		if ($this->isEnabled() && $enable)
			return true;

		if (!$this->isEnabled() && !$enable)
			return true;

		$query = \CBitrix24NetPortalTransport::init();
		if (!$query)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('B24NET_SOCSERV_TRANSPORT_ERROR'), self::ERROR_SOCSERV_TRANSPORT);
			return false;
		}

		$queryResult = $query->call('feature.enable', array(
			'FEATURE' => 'replica',
			'STATUS' => (bool)$enable,
		));

		Option::set('socialservices', 'network_enable', $enable? 'Y': 'N');

		return true;
	}

	/**
	 * Searches the network for users by email or nickname.
	 * Returns array on success and null on failure.
	 * Check errorCollection public member for errors description.
	 *
	 * @param string $search Search query string.
	 * @return array|null
	 */
	public function searchUser($search)
	{
		if (!$this->isEnabled())
		{
			$this->errorCollection[] = new Error(Loc::getMessage('B24NET_NETWORK_IN_NOT_ENABLED'), self::ERROR_NETWORK_IN_NOT_ENABLED);
			return null;
		}

		$search = trim($search);
		if (strlen($search) < 3)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('B24NET_SEARCH_STRING_TO_SHORT'), self::ERROR_SEARCH_STRING_TO_SHORT);
			return null;
		}

		$query = \CBitrix24NetPortalTransport::init();
		if (!$query)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('B24NET_SOCSERV_TRANSPORT_ERROR'), self::ERROR_SOCSERV_TRANSPORT);
			return null;
		}

		$queryResult = $query->call('profile.search', array(
			'QUERY' => $search
		));

		$result = Array();
		foreach ($queryResult['result'] as $user)
		{
			if (!$user = self::formatUserParam($user))
			{
				continue;
			}
			$result[] = $user;
		}

		return $result;
	}

	/**
	 * @param integer $networkId
	 * @param string $lastSearch
	 *
	 * @return array|null
	 */
	public function getUser($networkId, $lastSearch = '')
	{
		$result = $this->getUsers(Array($networkId), $lastSearch);

		return $result && isset($result[$networkId])? $result[$networkId]: null;
	}

	/**
	 * @param array $networkIds
	 * @param string $lastSearch
	 *
	 * @return array|null
	 */
	public function getUsers($networkIds, $lastSearch = '')
	{
		if (!$this->isEnabled())
		{
			$this->errorCollection[] = new Error(Loc::getMessage('B24NET_NETWORK_IN_NOT_ENABLED'), self::ERROR_NETWORK_IN_NOT_ENABLED);
			return null;
		}

		$query = \CBitrix24NetPortalTransport::init();
		if (!$query)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('B24NET_SOCSERV_TRANSPORT_ERROR'), self::ERROR_SOCSERV_TRANSPORT);
			return null;
		}

		if (!is_array($networkIds) || empty($networkIds))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('B24NET_ERROR_INCORRECT_PARAMS'), self::ERROR_INCORRECT_PARAMS);
			return null;
		}

		$queryResult = $query->call('profile.search', array(
			'ID' => array_values($networkIds),
			'QUERY' => trim($lastSearch)
		));
		
		$result = null;
		foreach ($queryResult['result'] as $user)
		{
			if (!$user = self::formatUserParam($user))
			{
				continue;
			}
			$result[$user['NETWORK_ID']] = $user;
		}

		if (!$result)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('B24NET_SEARCH_USER_NOT_FOUND'), self::ERROR_SEARCH_USER_NOT_FOUND);
			return null;
		}

		return $result;
	}

	/**
	 * @param int $networkId
	 * @param string $lastSearch
	 *
	 * @return integer|false
	 */
	public function addUserById($networkId, $lastSearch = '')
	{
		$userId = $this->getUserId($networkId);
		if ($userId)
		{
			return $userId;
		}

		$user = $this->getUser($networkId, $lastSearch);
		if (!$user)
		{
			return false;
		}

		return $this->addUser($user);
	}

	/**
	 * @param array $networkIds
	 * @param string $lastSearch
	 *
	 * @return array|boolean
	 */
	public function addUsersById($networkIds, $lastSearch = '')
	{
		$result = Array();

		$users = $this->getUsersId($networkIds);
		if ($users)
		{
			foreach ($users as $networkId => $userId)
			{
				$result[$networkId] = $userId;
			}
			$networkIds = array_diff($networkIds, array_keys($users));
		}
		if (!empty($networkIds))
		{
			$users = $this->getUsers($networkIds, $lastSearch);
			if (!$users)
			{
				return false;
			}

			foreach ($users as $networkId => $userParams)
			{
				$userId = $this->addUser($userParams);
				if ($userId)
				{
					$result[$networkId] = $userId;
				}
			}
		}

		return $result;
	}

	/**
	 * Add new user to b_user table.
	 * Returns its identifier or false on failure.
	 *
	 * @param array $params
	 *
	 * @return integer|false
	 */
	public function addUser($params)
	{
		if (!$this->isEnabled())
		{
			$this->errorCollection[] = new Error(Loc::getMessage('B24NET_NETWORK_IN_NOT_ENABLED'), self::ERROR_NETWORK_IN_NOT_ENABLED);
			return false;
		}

		$password = md5($params['XML_ID'].'|'.$params['CLIENT_DOMAIN'].'|'.rand(1000,9999).'|'.time().'|'.uniqid());
		$photo = \CFile::MakeFileArray($params['PERSONAL_PHOTO_ORIGINAL']);
		$groups = Array();

		if(Loader::includeModule('extranet'))
		{
			$groups[] = \CExtranet::GetExtranetUserGroupID();
		}

		$addParams = Array(
			'LOGIN' => $params['NETWORK_USER_ID'].'@'.$params['CLIENT_DOMAIN'],
			'NAME' => $params['NAME'],
			'EMAIL' => $params['EMAIL'],
			'LAST_NAME' => $params['LAST_NAME'],
			'SECOND_NAME' => $params['SECOND_NAME'],
			'PERSONAL_GENDER' => $params['PERSONAL_GENDER'],
			'PERSONAL_PHOTO' => $photo,
			'WORK_POSITION' => $params['CLIENT_DOMAIN'],
			'XML_ID' => $params['XML_ID'],
			'EXTERNAL_AUTH_ID' => self::EXTERNAL_AUTH_ID,
			"ACTIVE" => "Y",
			"PASSWORD" => $password,
			"CONFIRM_PASSWORD" => $password,
			"GROUP_ID" => $groups
		);
		if (isset($params['EMAIL']))
		{
			$addParams['EMAIL'] = $params['EMAIL'];
		}

		$user = new \CUser;
		$userId = $user->Add($addParams);
		if (intval($userId) <= 0)
		{
			$this->errorCollection[] = new Error($user->LAST_ERROR, self::ERROR_REGISTER_USER);
			return false;
		}

		$event = new Event("socialservices", "OnAfterRegisterUserByNetwork", array($userId, $params['NETWORK_USER_ID'], $params['CLIENT_DOMAIN']));
		$event->send();

		return $userId;
	}

	/**
	 * @param integer $userId
	 *
	 * @return integer|null
	 */
	public static function getNetworkId($userId)
	{
		$result = \Bitrix\Main\UserTable::getById($userId);
		$user = $result->fetch();
		if (!$user || $user['EXTERNAL_AUTH_ID'] != self::EXTERNAL_AUTH_ID)
		{
			return null;
		}

		list($networkId, ) = explode('|', $user['XML_ID']);

		return $networkId;
	}

	/**
	 * @param string $networkId
	 *
	 * @return integer|null
	 */
	public static function getUserId($networkId)
	{
		$result = self::getUsersId(Array($networkId));

		return $result && isset($result[$networkId])? $result[$networkId]: null;
	}

	/**
	 * @param array $networkIds
	 *
	 * @return array|null
	 */
	public static function getUsersId($networkIds)
	{
		if (!is_array($networkIds))
			return null;

		$searchArray = Array();
		foreach ($networkIds as $networkId)
		{
			$searchArray[] = intval($networkId)."|%";
		}

		$result = \Bitrix\Main\UserTable::getList(Array(
			'select' => Array('ID', 'WORK_PHONE', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'UF_PHONE_INNER', 'XML_ID'),
			'filter' => Array('=%XML_ID' => $searchArray, '=EXTERNAL_AUTH_ID' => self::EXTERNAL_AUTH_ID),
			'order' => 'ID'
		));

		$users = Array();
		while($user = $result->fetch())
		{
			list($networkId, ) = explode("|", $user['XML_ID']);
			$users[$networkId] = $user['ID'];
		}

		if (empty($users))
		{
			$users = null;
		}

		return $users;
	}

	/**
	 * @param array $params
	 *
	 * @return array|false
	 */
	private static function formatUserParam($params)
	{
		if (empty($params['NAME']))
		{
			if (!empty($params['PUBLIC_NAME']))
			{
				$params['NAME'] = $params['PUBLIC_NAME'];
			}
			else if (!empty($params['EMAIL']))
			{
				$params['NAME'] = $params['EMAIL'];
			}
			else
			{
				return false;
			}
		}

		$result = Array(
			'LOGIN' => $params['LOGIN'],
			'EMAIL' => $params['EMAIL'],
			'NAME' => $params['NAME'],
			'LAST_NAME' => $params['LAST_NAME'],
			'SECOND_NAME' => $params['SECOND_NAME'],
			'PUBLIC_NAME' => $params['PUBLIC_NAME'],
			'PERSONAL_GENDER' => $params['PERSONAL_GENDER'],
			'PERSONAL_PHOTO' => $params['PERSONAL_PHOTO_RESIZE'],
			'PERSONAL_PHOTO_ORIGINAL' => $params['PERSONAL_PHOTO'],
			'XML_ID' => $params['ID'].'|'.$params['USER_ID'],
			'NETWORK_ID' => $params['ID'],
			'NETWORK_USER_ID' => $params['USER_ID'],
			'REMOTE_USER_ID' => $params['PROFILE_ID'],
			'CLIENT_DOMAIN' => $params['CLIENT_DOMAIN'],
		);

		return $result;
	}
}