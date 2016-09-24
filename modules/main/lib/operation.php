<?php

namespace Bitrix\Main;

use Bitrix\Main\Entity;

class OperationTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_operation';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
			),
			'MODULE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateModuleId'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDescription'),
			),
			'BINDING' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateBinding'),
			),
		);
	}

	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	public static function validateModuleId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	public static function validateDescription()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	public static function validateBinding()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
}