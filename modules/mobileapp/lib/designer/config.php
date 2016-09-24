<?php
namespace Bitrix\MobileApp\Designer;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\Entity\FieldError;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Entity\ScalarField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

Loc::loadMessages(__FILE__);

/**
 * Class ConfigTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> APP_CODE string(255) optional
 * <li> PLATFORM string(255) optional
 * <li> PARAMS string optional
 * <li> DATE_CREATE datetime optional
 * </ul>
 *
 * @package Bitrix\Mobileapp
 **/
class ConfigTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_mobileapp_config';
	}

	public static function getMap()
	{
		return array(
			new Entity\StringField('APP_CODE',array(
				'primary' => true,
				'validation' => array(__CLASS__, 'validateAppCode'),
				'title' => Loc::getMessage('CONFIG_ENTITY_APP_CODE_FIELD'),
			)),
			new Entity\StringField('PLATFORM',array(
				'primary' => true,
				'validation' => array(__CLASS__, 'validatePlatform'),
				'title' => Loc::getMessage('CONFIG_ENTITY_PLATFORM_FIELD'),
			)),
			new Entity\TextField('PARAMS', array(
				'serialized' => true,
				'title' => Loc::getMessage('CONFIG_ENTITY_PARAMS_FIELD'),
			)),

			new Entity\DatetimeField('DATE_CREATE',array(
				'default_value' => new \Bitrix\Main\Type\DateTime,
				'title' => Loc::getMessage('CONFIG_ENTITY_DATE_CREATE_FIELD'),
			)),
			new Entity\ReferenceField(
				'APP',
				'Bitrix\MobileApp\AppTable',
				array('=this.APP_CODE' => 'ref.CODE')
			)
		);
	}

	public static function validateAppCode()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}


	public static function validatePlatform()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}


	public static function getSupportedPlatforms()
	{
		$platforms = AppTable::getSupportedPlatforms();
		$platforms[] = "global";
		return $platforms;
	}

	public static function checkFields(Result $result, $primary, array $data)
	{
		parent::checkFields($result, $primary, $data);
		$availablePlatforms = self::getSupportedPlatforms();

 		if( $result instanceof Entity\AddResult)
		{
			$entity = self::getEntity();
			if(!$data["APP_CODE"])
			{
				$result->addError(new Entity\FieldError($entity->getField("APP_CODE"),"Can not be empty!", 1));
			}
			else if (!$data["PLATFORM"])
			{
				$result->addError(new Entity\FieldError($entity->getField("PLATFORM"), "Can not be empty!", 1));
			}
			elseif(!in_array($data["PLATFORM"], $availablePlatforms))
			{
				$result->addError(new Entity\FieldError($entity->getField("PLATFORM"), "The passed value in not available!", 1));
			}

			$selectResult = self::getList(array(
				"filter"=>array(
					"APP_CODE"=>$data["APP_CODE"],
					"PLATFORM"=>$data["PLATFORM"]
				)
			));

			if($selectResult->getSelectedRowsCount() > 0)
			{
				$result->addError(new Entity\EntityError("Such configuration is already exists!", 1000));
			}
		}
	}

	public static function isExists($appCode, $platform)
	{
		//this is not such heavy operation as it might be expected
		$config = self::getRowById(Array("APP_CODE" => $appCode, "PLATFORM" => $platform));
		return (is_array($config) && count($config) > 0);
	}
}

