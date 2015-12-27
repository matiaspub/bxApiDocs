<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage bitrix24
 * @copyright 2001-2015 Bitrix
 */

namespace Bitrix\Im;

class User
{
	private static $instance = Array();
	private $userId = 0;
	private $userData = null;

	public function __construct($userId = null)
	{
		global $USER;

		$this->userId = (int)$userId;
		if ($this->userId <= 0 && is_object($USER) && $USER->GetID() > 0)
		{
			$this->userId = (int)$USER->GetID();
		}
	}

	/**
	 * @param null $userId
	 * @return User
	 */
	public static function getInstance($userId = null)
	{
		global $USER;

		$userId = (int)$userId;
		if ($userId <= 0 && is_object($USER) && $USER->GetID() > 0)
		{
			$userId = (int)$USER->GetID();
		}

		if (!isset(self::$instance[$userId]))
		{
			self::$instance[$userId] = new self($userId);
		}

		return self::$instance[$userId];
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->userId;
	}

	/**
	 * @return bool
	 */
	public function isExtranet()
	{
		$fields = $this->getFields();

		return $fields? (bool)$fields['extranet']: false;
	}

	/**
	 * @return bool
	 */
	public function isNetwork()
	{
		$fields = $this->getFields();

		return $fields? (bool)$fields['network']: false;
	}

	/**
	 * @return array|null
	 */
	public function getFields()
	{
		$params = $this->getParams();

		return $params? $params['user']: null;
	}

	/**
	 * @return array|null
	 */
	private function getParams()
	{
		if (is_null($this->userData))
		{
			$userData = \CIMContactList::GetUserData(Array(
				'ID' => self::getId(),
				'PHONES' => 'Y'
			));
			if (isset($userData['users'][self::getId()]))
			{
				$this->userData['user'] = $userData['users'][self::getId()];
			}
		}
		return $this->userData;
	}

	/**
	 * @return bool
	 */
	public function updateParams()
	{
		$this->userData = null;

		return true;
	}
}