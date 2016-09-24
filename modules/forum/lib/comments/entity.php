<?php

namespace Bitrix\Forum\Comments;

class Entity
{
	/** @var array */
	protected $entity;
	/** @var \Bitrix\Forum\Comments\BaseObject */
	protected $caller;
	/** @var array */
	protected $forum;
	/** @var array  */
	static $permissions = array();
	/** @var string */
	private $permission = "A";
	/** @var bool */
	private $editOwn = false;
	/** @var array */
	private $rights = array();
	protected static $pathToUser  = '/company/personal/user/#user_id#/';
	protected static $pathToGroup = '/workgroups/group/#group_id#/';

	/**
	 * @param array $entity
	 * @param BaseObject $caller
	 */
	public function __construct(array $entity, BaseObject $caller)
	{
		$this->entity = array(
			"type" => $entity["type"],
			"id" => $entity["id"],
			"xml_id" => $entity["xml_id"]
		);
		$this->caller = $caller;
		$this->forum = $caller->getForum();
		$this->initPermission();
		$this->editOwn = (\COption::GetOptionString("forum", "USER_EDIT_OWN_POST", "Y") == "Y");
	}

	public function getId()
	{
		return $this->entity["id"];
	}

	public function getType()
	{
		return $this->entity["type"];
	}

	public function getXmlId()
	{
		if (!empty($this->entity["xml_id"]))
			return $this->entity["xml_id"];
		return strtoupper($this->entity["type"]."_".$this->entity["id"]);
	}

	/**
	 * @return array
	 */
	public function getFullId()
	{
		return $this->entity;
	}

	public static function className()
	{
		return get_called_class();
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public function canRead()
	{
		return ($this->permission >= "E");
	}
	/**
	 * @return bool
	 */
	public function canAdd()
	{
		if (!array_key_exists("add", $this->rights))
		{
			$this->rights["add"] = ($this->permission >= "I");
		}
		return $this->rights["add"];
	}

	/**
	 * @return bool
	 */
	public function canEdit()
	{
		if (!array_key_exists("edit", $this->rights))
		{
			$this->rights["edit"] = ($this->permission >= "U");
		}
		return $this->rights["edit"];
	}
	/**
	 * @return bool
	 */
	public function canEditOwn()
	{
		if (!array_key_exists("editOwn", $this->rights))
		{
			$this->rights["editOwn"] = ($this->canEdit() || $this->permission >= "I" && $this->editOwn);
		}
		return $this->rights["editOwn"];
	}
	/**
	 * @return bool
	 */
	public function canModerate()
	{
		if (!array_key_exists("moderate", $this->rights))
		{
			$this->rights["moderate"] = ($this->permission >= "Q");
		}
		return $this->rights["moderate"];
	}

	/**
	 * @param string $permission A < E < I < M < Q < U < Y
	// A - NO ACCESS		E - READ			I - ANSWER
	// M - NEW TOPIC		Q - MODERATE	U - EDIT			Y - FULL_ACCESS
	 * @return $this
	 */
	public function setPermission($permission)
	{
		if (is_string($permission))
		{
			$this->permission = strtoupper($permission);
			$this->rights = array();
		}
		return $this;
	}

	/**
	 * @param bool $permission
	 * @return $this
	 */
	public function setEditOwn($permission)
	{
		$this->editOwn = $permission;
		unset($this->rights["editOwn"]);
		return $this;
	}

	public function initPermission()
	{
		if (!array_key_exists($this->forum["ID"], self::$permissions))
		{
			if (\CForumUser::IsAdmin($this->getUser()->getGroups()))
				$result = "Y";
			else if ($this->forum["ACTIVE"] != "Y")
				$result = "A";
			else if (\CForumUser::IsLocked($this->getUser()->getId()))
				$result = \CForumNew::GetPermissionUserDefault($this->forum["ID"]);
			else
				$result = \CForumNew::GetUserPermission($this->forum["ID"], $GLOBALS["USER"]->GetUserGroupArray());

			self::$permissions [$this->forum["ID"]] = $result;
		}
		$this->permission = self::$permissions[$this->forum["ID"]];
		$this->rights = array();
		return $this;
	}

	public function getPermission()
	{
		return $this->permission;
	}

	public function getUser()
	{
		return $this->caller->getUser();
	}
}