<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class UserTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_user';
	}

	public static function getUfId()
	{
		return 'USER';
	}

	public static function getMap()
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'LOGIN' => array(
				'data_type' => 'string'
			),
			'PASSWORD' => array(
				'data_type' => 'string'
			),
			'EMAIL' => array(
				'data_type' => 'string'
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
			'DATE_REGISTER' => array(
				'data_type' => 'datetime'
			),
			'DATE_REG_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$helper->getDatetimeToDateFunction('%s'), 'DATE_REGISTER'
				)
			),
			'LAST_LOGIN' => array(
				'data_type' => 'datetime'
			),
			'LAST_LOGIN_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$helper->getDatetimeToDateFunction('%s'), 'LAST_LOGIN'
				)
			),
			'LAST_ACTIVITY_DATE' => array(
				'data_type' => 'datetime'
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'SECOND_NAME' => array(
				'data_type' => 'string'
			),
			'LAST_NAME' => array(
				'data_type' => 'string'
			),
			'TITLE' => array(
				'data_type' => 'string'
			),
			'EXTERNAL_AUTH_ID' => array(
				'data_type' => 'string'
			),
			'XML_ID' => array(
				'data_type' => 'string'
			),
			'BX_USER_ID' => array(
				'data_type' => 'string'
			),
			'CONFIRM_CODE' => array(
				'data_type' => 'string'
			),
			'LID' => array(
				'data_type' => 'string'
			),
			'TIME_ZONE_OFFSET' => array(
				'data_type' => 'integer'
			),
			'PERSONAL_PROFESSION' => array(
				'data_type' => 'string'
			),
			'PERSONAL_PHONE' => array(
				'data_type' => 'string'
			),
			'PERSONAL_MOBILE' => array(
				'data_type' => 'string'
			),
			'PERSONAL_WWW' => array(
				'data_type' => 'string'
			),
			'PERSONAL_ICQ' => array(
				'data_type' => 'string'
			),
			'PERSONAL_FAX' => array(
				'data_type' => 'string'
			),
			'PERSONAL_PAGER' => array(
				'data_type' => 'string'
			),
			'PERSONAL_STREET' => array(
				'data_type' => 'text'
			),
			'PERSONAL_MAILBOX' => array(
				'data_type' => 'string'
			),
			'PERSONAL_CITY' => array(
				'data_type' => 'string'
			),
			'PERSONAL_STATE' => array(
				'data_type' => 'string'
			),
			'PERSONAL_ZIP' => array(
				'data_type' => 'string'
			),
			'PERSONAL_COUNTRY' => array(
				'data_type' => 'string'
			),
			'PERSONAL_BIRTHDAY' => array(
				'data_type' => 'date'
			),
			'PERSONAL_GENDER' => array(
				'data_type' => 'string'
			),
			'PERSONAL_PHOTO' => array(
				'data_type' => 'integer'
			),
			'PERSONAL_NOTES' => array(
				'data_type' => 'text'
			),
			'WORK_COMPANY' => array(
				'data_type' => 'string'
			),
			'WORK_DEPARTMENT' => array(
				'data_type' => 'string'
			),
			'WORK_PHONE' => array(
				'data_type' => 'string'
			),
			'WORK_POSITION' => array(
				'data_type' => 'string'
			),
			'WORK_WWW' => array(
				'data_type' => 'string'
			),
			'WORK_FAX' => array(
				'data_type' => 'string'
			),
			'WORK_PAGER' => array(
				'data_type' => 'string'
			),
			'WORK_STREET' => array(
				'data_type' => 'text'
			),
			'WORK_MAILBOX' => array(
				'data_type' => 'string'
			),
			'WORK_CITY' => array(
				'data_type' => 'string'
			),
			'WORK_STATE' => array(
				'data_type' => 'string'
			),
			'WORK_ZIP' => array(
				'data_type' => 'string'
			),
			'WORK_COUNTRY' => array(
				'data_type' => 'string'
			),
			'WORK_PROFILE' => array(
				'data_type' => 'text'
			),
			'WORK_LOGO' => array(
				'data_type' => 'integer'
			),
			'WORK_NOTES' => array(
				'data_type' => 'text'
			),
			'SHORT_NAME' => array(
				'data_type' => 'string',
				'expression' => array(
					$helper->getConcatFunction("%s","' '", "UPPER(".$helper->getSubstrFunction("%s", 1, 1).")", "'.'"),
					'LAST_NAME', 'NAME'
				)
			),
			'IS_ONLINE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'expression' => array(
					'CASE WHEN %s > '.$helper->addSecondsToDateTime('(-120)').' THEN \'Y\' ELSE \'N\' END',
					'LAST_ACTIVITY_DATE',
				)
			),
		);
	}

	public static function getActiveUsersCount()
	{
		if (ModuleManager::isModuleInstalled("intranet"))
		{
			$sql = "SELECT COUNT(U.ID) ".
				"FROM b_user U ".
				"WHERE U.ACTIVE = 'Y' ".
				"   AND U.LAST_LOGIN IS NOT NULL ".
				"   AND EXISTS(".
				"       SELECT 'x' ".
				"       FROM b_utm_user UF, b_user_field F ".
				"       WHERE F.ENTITY_ID = 'USER' ".
				"           AND F.FIELD_NAME = 'UF_DEPARTMENT' ".
				"           AND UF.FIELD_ID = F.ID ".
				"           AND UF.VALUE_ID = U.ID ".
				"           AND UF.VALUE_INT IS NOT NULL ".
				"           AND UF.VALUE_INT <> 0".
				"   )";
		}
		else
		{
			$sql = "SELECT COUNT(ID) ".
				"FROM b_user ".
				"WHERE ACTIVE = 'Y' ".
				"   AND LAST_LOGIN IS NOT NULL";
		}

		$connection = Application::getConnection();
		return $connection->queryScalar($sql);
	}

	public static function getUserGroupIds($userId)
	{
		$groups = array();

		// anonymous groups
		$result = GroupTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=ANONYMOUS' => 'Y',
				'=ACTIVE' => 'Y'
			)
		));

		while ($row = $result->fetch())
		{
			$groups[] = $row['ID'];
		}

		if(!in_array(2, $groups))
			$groups[] = 2;

		// private groups
		$nowTimeExpression = new SqlExpression(
			static::getEntity()->getConnection()->getSqlHelper()->getCurrentDateTimeFunction()
		);

		$result = GroupTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=UserGroup:GROUP.USER_ID' => $userId,
				'=ACTIVE' => 'Y',
				array(
					'LOGIC' => 'OR',
					'=UserGroup:GROUP.DATE_ACTIVE_FROM' => null,
					'<=UserGroup:GROUP.DATE_ACTIVE_FROM' => $nowTimeExpression,
				),
				array(
					'LOGIC' => 'OR',
					'=UserGroup:GROUP.DATE_ACTIVE_TO' => null,
					'>=UserGroup:GROUP.DATE_ACTIVE_TO' => $nowTimeExpression,
				),
				array(
					'LOGIC' => 'OR',
					'!=ANONYMOUS' => 'Y',
					'=ANONYMOUS' => null
				)
			)
		));

		while ($row = $result->fetch())
		{
			$groups[] = $row['ID'];
		}

		sort($groups);

		return $groups;
	}

	public static function add(array $data)
	{
		throw new NotImplementedException("Use CUser class.");
	}

	public static function update($primary, array $data)
	{
		throw new NotImplementedException("Use CUser class.");
	}

	public static function delete($primary)
	{
		throw new NotImplementedException("Use CUser class.");
	}
}
