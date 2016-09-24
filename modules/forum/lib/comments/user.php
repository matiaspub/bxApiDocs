<?php

namespace Bitrix\Forum\Comments;

class User
{
	protected $id = 0;
	protected $groups = array(2);

	public function __construct($id)
	{
		global $USER;
		if (is_object($USER) && $id == $USER->getId())
		{
			$this->id = $USER->getId();
			$this->groups = $USER->GetUserGroupArray();
		}
		else if ($id > 0)
		{
			$this->id = $id;
			$this->groups = \Bitrix\Main\UserTable::getUserGroupIds($id);
		}
	}

	public function getId()
	{
		return $this->id;
	}

	public function getGroups()
	{
		return implode(",", $this->groups);
	}

	public function getUserGroupArray()
	{
		return $this->groups;
	}

	static public function isAuthorized()
	{
		return true;
	}

	static public function getParam()
	{
		return '';
	}
	static public function isAdmin()
	{
		return false;
	}
	public function getUserGroup()
	{
		return $this->groups;
	}
	static public function getFirstName()
	{
		return '';
	}
	static public function getLastName()
	{
		return '';
	}
	static public function getSecondName()
	{
		return '';
	}
	static public function getLogin()
	{
		return '';
	}
	static public function getFullName()
	{
		return '';
	}
}