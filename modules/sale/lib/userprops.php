<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Sale\Internals\OrderUserPropertiesTable;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OrderUserProperties
{
	private $profiles = array();
	private static $instance;

	public static function __construct()
	{

	}

	/**
	 * @return OrderUserProperties
	 */
	public static function getInstance()
	{

		if(!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param array $parameters
	 * @return \Bitrix\Main\DB\Result
	 */
	public static function getList(array $parameters)
	{
		return OrderUserPropertiesTable::getList($parameters);
	}

	/**
	 * @param $parameters
	 */
	public static function loadFromDB($parameters)
	{
		static::getInstance()->profiles = static::getList($parameters)->fetchAll();
	}

	/**
	 * @param $personTypeId
	 * @param $userId
	 * @return bool
	 */
	public static function getFirstId($personTypeId, $userId)
	{
		if (empty(static::getInstance()->profiles))
		{
			static::loadFromDB(array(
				'order' => array("DATE_UPDATE" => "DESC"),
				'filter' => array(
								"PERSON_TYPE_ID" => $personTypeId,
								"USER_ID" => $userId
								),
			));
		}

		if (!empty(static::getInstance()->profiles) && is_array(static::getInstance()->profiles))
		{
			$profile = reset(static::getInstance()->profiles);
			return $profile['ID'];
		}

		return false;
	}

	/**
	 * @param $profileId
	 * @param $personTypeId
	 * @param $userId
	 * @return bool
	 */
	public static function checkCorrect($profileId, $personTypeId, $userId)
	{
		if (static::getList(array(
			'filter' => array(
				"ID" => $profileId,
				"PERSON_TYPE_ID" => $personTypeId,
				"USER_ID" => $userId
			)))->fetch())
		{
			return true;
		}
		else
		{
			return false;
		}
	}



}