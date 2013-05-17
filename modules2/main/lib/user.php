<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Entity;

class UserTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

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
//		$connection = Application::getDbConnection();
//		$helper = $connection->getSqlHelper();
		global $DB;

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
//					$helper->getDatetimeToDateFunction('%s'), 'DATE_REGISTER'
					$DB->datetimeToDateFunction('%s'), 'DATE_REGISTER'
				)
			),
			'LAST_LOGIN' => array(
				'data_type' => 'datetime'
			),
			'LAST_LOGIN_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
//					$helper->getDatetimeToDateFunction('%s'), 'LAST_LOGIN'
					$DB->datetimeToDateFunction('%s'), 'LAST_LOGIN'
				)
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'PERSONAL_PHONE' => array(
				'data_type' => 'string'
			),
			'PERSONAL_MOBILE' => array(
				'data_type' => 'string'
			),
			'SECOND_NAME' => array(
				'data_type' => 'string'
			),
			'LAST_NAME' => array(
				'data_type' => 'string'
			),
			'LID' => array(
				'data_type' => 'string'
			),
			'WORK_POSITION' => array(
				'data_type' => 'string'
			),
			'PERSONAL_BIRTHDAY' => array(
				'data_type' => 'date'
			),
			'PERSONAL_GENDER' => array(
				'data_type' => 'string'
			),
			'SHORT_NAME' => array(
				'data_type' => 'string',
				'expression' => array(
//					$helper->getConcatFunction("%s","' '", "UPPER(".$helper->getSubstrFunction("%s", 1, 1).")", "'.'"),
					$DB->concat("%s","' '", "UPPER(".$DB->substr("%s", 1, 1).")", "'.'"),
					'LAST_NAME', 'NAME'
				)
			),
			'EXTERNAL_AUTH_ID' => array(
				'data_type' => 'string'
			),
			'UTS_OBJECT' => array(
				'data_type' => 'UtsUser',
				'reference' => array('=this.ID' => 'ref.VALUE_ID')
			)
		);
	}

	public static function getActiveUsersCount()
	{
		$sql = "SELECT COUNT(ID) ".
			"FROM b_user ".
			"WHERE ACTIVE = 'Y' ".
			"   AND LAST_LOGIN IS NOT NULL";

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

		$connection = Application::getDbConnection();
		return $connection->queryScalar($sql);
	}
}
