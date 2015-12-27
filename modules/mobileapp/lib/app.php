<?php

namespace Bitrix\MobileApp;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\Entity\FieldError;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

Loc::loadMessages(__FILE__);

/**
 * Class AppTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CODE string(255) optional
 * <li> PLATFORM string(255) optional
 * <li> SHORT_NAME string(20) optional
 * <li> NAME string(255) optional
 * <li> DESCRIPTION string(255) optional
 * <li> FOLDER string(255) optional
 * <li> DATE_CREATE datetime optional
 * </ul>
 *
 * @package Bitrix\Mobileapp
 **/
class AppTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_mobileapp_app';
	}

	public static function getMap()
	{
		return array(
			new Entity\StringField('CODE', array(
				'primary' => true,
				'validation' => array(__CLASS__, 'validateCode'),
				'title' => Loc::getMessage('APP_ENTITY_CODE_FIELD'),
			)),
			new Entity\StringField('SHORT_NAME', array(
				'validation' => array(__CLASS__, 'validateShortName'),
				'title' => Loc::getMessage('APP_ENTITY_SHORT_NAME_FIELD'),
				'default_value' => ""
			)),
			new Entity\StringField('NAME', array(
				'validation' => array(__CLASS__, 'validateName'),
				"require" => true,
				'title' => Loc::getMessage('APP_ENTITY_NAME_FIELD'),
			)),
			new Entity\TextField('DESCRIPTION', array(
				'default_value' => "",
				'title' => Loc::getMessage('APP_ENTITY_DESCRIPTION_FIELD'),
			)),
			new Entity\TextField('FILES', array(
				'serialized' => true,
				'default_value' => array(),
				'title' => Loc::getMessage('APP_ENTITY_FILES_FIELD'),
			)),
			new Entity\TextField('LAUNCH_ICONS', array(
				'serialized' => true,
				'default_value' => array(),
				'title' => Loc::getMessage('APP_ENTITY_LAUNCH_ICONS_FIELD'),
			)),
			new Entity\TextField('LAUNCH_SCREENS', array(
				'serialized' => true,
				'default_value' => array(),
				'title' => Loc::getMessage('APP_ENTITY_LAUNCH_SCREENS_FIELD'),
			)),
			new Entity\StringField('FOLDER', array(
				'validation' => array(__CLASS__, 'validateFolder'),
				'require' => true,
				'title' => Loc::getMessage('APP_ENTITY_FOLDER_FIELD'),
			)),
			new Entity\DatetimeField('DATE_CREATE', array(
				'default_value' => new \Bitrix\Main\Type\Date,
				'title' => Loc::getMessage('APP_ENTITY_DATE_CREATE_FIELD'),
			)),
			new Entity\ReferenceField(
				'CONFIG',
				'Bitrix\MobileApp\Designer\ConfigTable',
				array('=this.CODE' => 'ref.APP_CODE')
			)
		);
	}

	public static function validateCode()
	{
		return array(
			new Entity\Validator\Unique(null, 255),
		);
	}

	public static function validateShortName()
	{
		return array(
			new Entity\Validator\Length(null, 20),
		);
	}

	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	public static function validateFolder()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	public static function isAppExists($code)
	{
		$result = self::getById($code);

		return ($result->getSelectedRowsCount() ? true : false);
	}

	public static function onAfterDelete(Event $event)
	{
		$parameters = $event->getParameter("id");
		$code = $parameters["CODE"];
		Designer\ConfigTable::delete(array("APP_CODE" => $code));
		parent::onAfterDelete($event);
	}

	public static function checkFields(Result $result, $primary, array $data)
	{
		parent::checkFields($result, $primary, $data);

		if ($result instanceof Entity\AddResult)
		{
			$entity = self::getEntity();
			if (!$data["CODE"])
			{
				$result->addError(new Entity\FieldError($entity->getField("CODE"), "Can not be empty!", FieldError::EMPTY_REQUIRED));
			}
			elseif (!$data["FOLDER"])
			{
				$result->addError(new Entity\FieldError($entity->getField("FOLDER"), "Can not be empty!", FieldError::EMPTY_REQUIRED));
			}
			elseif (!$data["NAME"])
			{
				$result->addError(new Entity\FieldError($entity->getField("NAME"), "Can not be empty!", FieldError::EMPTY_REQUIRED));
			}

		}
	}

	public static function getSupportedPlatforms()
	{
		return array(
			"android",
			"ios",
		);
	}


}