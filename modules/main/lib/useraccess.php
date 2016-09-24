<?php

namespace Bitrix\Main;

use Bitrix\Main\Entity;

class UserAccessTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_user_access';
	}

	public static function getMap()
	{
		return array(
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'PROVIDER_ID' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateProviderId'),
			),
			'ACCESS_CODE' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateAccessCode'),
			),
		);
	}

	public static function validateProviderId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	public static function validateAccessCode()
	{
		return array(
			new Entity\Validator\Length(null, 100),
		);
	}
}