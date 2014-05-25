<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Entity;

class UtmUserTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_utm_user';
	}

	public static function getUfId()
	{
		return 'USER';
	}

	public static function isUtm()
	{
		return true;
	}

	public static function getMap()
	{
		/** @global CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		// get ufields
		$fieldsMap = $USER_FIELD_MANAGER->getUserFields(static::getUfId());

		foreach ($fieldsMap as $k => $v)
		{
			if ($v['MULTIPLE'] == 'N')
			{
				unset($fieldsMap[$k]);
			}
		}

		return array_merge(array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'VALUE_ID' => array(
				'data_type' => 'integer'
			),
			'SOURCE_OBJECT' => array(
				'data_type' => 'User',
				'reference' => array('=this.VALUE_ID' => 'ref.ID')
			),
			'FIELD_ID' => array(
				'data_type' => 'integer'
			),
			'VALUE' => array(
				'data_type' => 'string'
			),
			'VALUE_INT' => array(
				'data_type' => 'integer'
			),
			'VALUE_DOUBLE' => array(
				'data_type' => 'float'
			),
			'VALUE_DATE' => array(
				'data_type' => 'datetime'
			)
		), $fieldsMap);
	}
}
