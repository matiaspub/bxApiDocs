<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage socialnetwork
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Socialnetwork;

use Bitrix\Main\Entity;
use Bitrix\Main\NotImplementedException;

class UserToGroupTable extends Entity\DataManager
{
	const ROLE_OWNER = SONET_ROLES_OWNER;
	const ROLE_MODERATOR = SONET_ROLES_MODERATOR;
	const ROLE_USER = SONET_ROLES_USER;
	const ROLE_BAN = SONET_ROLES_BAN;
	const ROLE_REQUEST = SONET_ROLES_REQUEST;

	const INITIATED_BY_USER = SONET_INITIATED_BY_USER;
	const INITIATED_BY_GROUP = SONET_INITIATED_BY_GROUP;

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sonet_user2group';
	}

	/**
	 * Returns entity map definition
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
				'data_type' => 'integer',
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
			'GROUP_ID' => array(
				'data_type' => 'integer',
			),
			'GROUP' => array(
				'data_type' => 'Bitrix\Socialnetwork\WorkgroupTable',
				'reference' => array('=this.GROUP_ID' => 'ref.ID'),
			),
			'ROLE' => array(
				'data_type' => 'enum',
				'values' => array(self::ROLE_OWNER, self::ROLE_MODERATOR, self::ROLE_USER, self::ROLE_BAN, self::ROLE_REQUEST),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime'
			),
			'DATE_UPDATE' => array(
				'data_type' => 'datetime'
			),
			'INITIATED_BY_TYPE' => array(
				'data_type' => 'enum',
				'values' => array(self::INITIATED_BY_USER, self::INITIATED_BY_GROUP),
			),
			'INITIATED_BY_USER_ID' => array(
				'data_type' => 'integer',
			),
			'INITIATED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.INITIATED_BY_USER_ID' => 'ref.ID'),
			),
			'MESSAGE' => array(
				'data_type' => 'text',
			)
		);
	}

	/**
	 * Adds row to entity table
	 *
	 * @param array $data
	 *
	 * @return Entity\AddResult Contains ID of inserted row
	 *
	 * @throws \Exception
	 */
	public static function add(array $data)
	{
		throw new NotImplementedException("Use CSocNetUserToGroup class.");
	}

	/**
	 * Updates row in entity table by primary key
	 *
	 * @param mixed $primary
	 * @param array $data
	 *
	 * @return Entity\UpdateResult
	 *
	 * @throws \Exception
	 */
	public static function update($primary, array $data)
	{
		throw new NotImplementedException("Use CSocNetUserToGroup class.");
	}

	/**
	 * Deletes row in entity table by primary key
	 *
	 * @param mixed $primary
	 *
	 * @return Entity\DeleteResult
	 *
	 * @throws \Exception
	 */
	public static function delete($primary)
	{
		throw new NotImplementedException("Use CSocNetUserToGroup class.");
	}
}
