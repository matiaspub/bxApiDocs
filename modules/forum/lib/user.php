<?php
namespace Bitrix\Forum;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\NotImplementedException;


Loc::loadMessages(__FILE__);

/**
 * Class UserTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int
 * <li> DESCRIPTION string(255) null,
 * <li> AVATAR int(10),
 * <li> POINTS int not null default 0,
 * <li> RANK_ID int null,
 * <li> NUM_POSTS int(10) default '0',
 * <li> INTERESTS text,
 * <li> LAST_POST int(10),
 * <li> SIGNATURE varchar(255) null,

 * <li> IP_ADDRESS string(128) null
 * <li> REAL_IP_ADDRESS varchar(128) null,
 * <li> DATE_REG date not null,
 * <li> LAST_VISIT datetime not null,

 * <li> ALLOW_POST char(1) not null default 'Y',
 * <li> SHOW_NAME char(1) not null default 'Y',
 * <li> HIDE_FROM_ONLINE char(1) not null default 'N',
 * <li> SUBSC_GROUP_MESSAGE char(1) NOT NULL default 'N',
 * <li> SUBSC_GET_MY_MESSAGE char(1) NOT NULL default 'Y',
 * </ul>
 *
 * @package Bitrix\Forum
 */
class UserTable extends Internals\BaseTable
{
	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_forum_user';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer'
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array(
					'=this.USER_ID' => 'ref.ID'
				)
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
			),
			'AVATAR' => array(
				'data_type' => 'integer'
			),
			'POINTS' => array(
				'data_type' => 'integer'
			),
			'RANK_ID' => array(
				'data_type' => 'integer'
			),
			'NUM_POSTS' => array(
				'data_type' => 'integer'
			),
			'INTERESTS' => array(
				'data_type' => 'text'
			),
			'LAST_POST' => array(
				'data_type' => 'integer'
			),
			'SIGNATURE' => array(
				'data_type' => 'string'
			),
			'IP_ADDRESS' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateAuthorIp'),
			),
			'REAL_IP_ADDRESS' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateAuthorRealIp'),
			),
			'DATE_REG' => array(
				'data_type' => 'datetime',
				'required' => true,
			),
			'LAST_VISIT' => array(
				'data_type' => 'datetime',
				'required' => true,
			),
			'ALLOW_POST' => array(
				'data_type' => "boolean",
				'values' => array("N", "Y"),
				'default_value' => "Y"
			),
			'SHOW_NAME' => array(
				'data_type' => "boolean",
				'values' => array("N", "Y"),
				'default_value' => "Y"
			),
			'HIDE_FROM_ONLINE' => array(
				'data_type' => "boolean",
				'values' => array("N", "Y"),
				'default_value' => "N"
			),
			'SUBSC_GROUP_MESSAGE' => array(
				'data_type' => "boolean",
				'values' => array("N", "Y"),
				'default_value' => "N"
			),
			'SUBSC_GET_MY_MESSAGE' => array(
				'data_type' => "boolean",
				'values' => array("N", "Y"),
				'default_value' => "Y"
			)
		);
	}
	public static function add(array $data)
	{
		throw new NotImplementedException("Use CForumUser class.");
	}

	public static function update($primary, array $data)
	{
		throw new NotImplementedException("Use CForumUser class.");
	}

	public static function delete($primary)
	{
		throw new NotImplementedException("Use CForumUser class.");
	}
}

class User {
	/** @var int */
	protected $id;
	/** @var int */
	protected $forumUserId = null;
	/** @var string */
	protected $login;
	/** @var string */
	protected $name;
	/** @var string */
	protected $secondName;
	/** @var string */
	protected $lastName;

	/** @var User[] */
	protected static $loadedUsers;

	public static function loadById($id)
	{
		if (!isset(self::$loadedUsers[$id]))
		{
			self::$loadedUsers[$id] = new User($id);
		}
		return self::$loadedUsers[$id];
	}

	protected function __construct($id)
	{
		$user = UserTable::getList(array(
			'select' => array('*'),
			'filter' => array('USER_ID' => (int)$id),
			'limit' => 1,
		))->fetch();
		if ($user)
		{
			$this->forumUserId = $user["ID"];
			$this->id = $user["USER_ID"];
		}
		else
		{
			$user = \Bitrix\Main\UserTable::getList(array(
				'select' => array('*'),
				'filter' => array('ID' => (int)$id),
				'limit' => 1,
			))->fetch();
			$this->id = $user["ID"];
		}
		$this->name = $user["NAME"];
		$this->secondName = $user["SECOND_NAME"];
		$this->lastName = $user["LAST_NAME"];
		$this->login = $user["LOGIN"];
	}
	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getLastName()
	{
		return $this->lastName;
	}


	/**
	 * @return string
	 */
	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	public function setLastVisit()
	{
		if ($this->getId() <= 0)
		{
			return;
		}

		static $connection = false;
		static $helper = false;
		if (!$connection)
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$helper = $connection->getSqlHelper();
		}

		$merge = $helper->prepareMerge(
			"b_forum_user",
			array("USER_ID"),
			array(
				"SHOW_NAME" => (\COption::GetOptionString("forum", "USER_SHOW_NAME", "Y") == "Y" ? "Y" : "N"),
				"ALLOW_POST" => "Y",
				"USER_ID" => $this->getId(),
				"DATE_REG" => new \Bitrix\Main\DB\SqlExpression($helper->getCurrentDateTimeFunction()),
				"LAST_VISIT" => new \Bitrix\Main\DB\SqlExpression($helper->getCurrentDateTimeFunction())
			),
			array(
				"LAST_VISIT" => new \Bitrix\Main\DB\SqlExpression($helper->getCurrentDateTimeFunction())
			)
		);
		if ($merge[0] != "")
		{
			$connection->query($merge[0]);
		}

		unset($GLOBALS["FORUM_CACHE"]["USER"]);
		unset($GLOBALS["FORUM_CACHE"]["USER_ID"]);
	}
}