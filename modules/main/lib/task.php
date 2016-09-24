<?php

namespace Bitrix\Main;

use Bitrix\Main\Entity;

class TaskTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_task';
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
			'LETTER' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateLetter'),
			),
			'MODULE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateModuleId'),
			),
			'SYS' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateSys'),
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
			new Entity\Validator\Length(null, 100),
		);
	}

	public static function validateLetter()
	{
		return array(
			new Entity\Validator\Length(null, 1),
		);
	}

	public static function validateModuleId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	public static function validateSys()
	{
		return array(
			new Entity\Validator\Length(null, 1),
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