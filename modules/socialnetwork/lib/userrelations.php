<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage socialnetwork
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Socialnetwork;

use Bitrix\Main\Entity;

class UserRelationsTable extends Entity\DataManager
{
	const RELATION_FRIEND = SONET_RELATIONS_FRIEND;
	const RELATION_REQUEST = SONET_RELATIONS_REQUEST;
	const RELATION_BAN = SONET_RELATIONS_BAN;

	const INITIATED_BY_FIRST = 'F';
	const INITIATED_BY_SECOND = 'S';

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sonet_user_relations';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'FIRST_USER_ID' => array(
				'data_type' => 'integer',
			),
			'SECOND_USER_ID' => array(
				'data_type' => 'integer',
			),
			'RELATION' => array(
				'data_type' => 'enum',
				'required' => true,
				'values' => array(self::RELATION_FRIEND, self::RELATION_REQUEST, self::RELATION_BAN),
			),
			'INITIATED_BY' => array(
				'data_type' => 'enum',
				'required' => true,
				'values' => array(self::INITIATED_BY_FIRST, self::INITIATED_BY_SECOND)
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
			),
			'DATE_UPDATE' => array(
				'data_type' => 'datetime',
			),
			'MESSAGE' => array(
				'data_type' => 'text',
			),
			'FIRST_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.FIRST_USER_ID' => 'ref.ID'),
			),
			'SECOND_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.SECOND_USER_ID' => 'ref.ID'),
			),
		);
	}

	public static function getUserFilter($operation, $field, $filter)
	{
		return array(
			'LOGIC' => 'OR',
			$operation.preg_replace('/^USER/', 'FIRST_USER', $field) => $filter,
			$operation.preg_replace('/^USER/', 'SECOND_USER', $field) => $filter,
		);
	}
}
