<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Entity;

class UtsUserTable extends Entity\DataManager
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

	public static function isUts()
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
			if ($v['MULTIPLE'] == 'Y')
			{
				unset($fieldsMap[$k]);
			}
		}

		$fieldsMap['VALUE_ID'] = array(
			'data_type' => 'integer',
			'primary' => true
		);

		$fieldsMap['SOURCE_OBJECT'] = array(
			'data_type' => 'User',
			'reference' => array('=this.VALUE_ID' => 'ref.ID')
		);

		return $fieldsMap;
	}
}
