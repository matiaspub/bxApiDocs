<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage socialnetwork
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Socialnetwork;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;

Loc::loadMessages(__FILE__);

class LogViewTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sonet_log_view';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'EVENT_ID' => array(
				'data_type' => 'string',
				'primary' => true
			),
			'TYPE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y')
			),
		);

		return $fieldsMap;
	}

	public static function getDefaultValue($eventId, $full = false)
	{
		$result = 'Y';

		$eventId = trim($eventId);
		if (strlen($eventId))
		{
			throw new Main\SystemException("Empty eventId.");
		}
		if (!$full)
		{
			$eventId = \CSocNetLogTools::findFullSetByEventID($eventId);
		}

		$res = self::getList(array(
			'order' => array(),
			'filter' => array(
				'=USER_ID' => 0,
				'=EVENT_ID' => \Bitrix\Main\Application::getConnection()->getSqlHelper()->forSql($eventId)
			),
			'select' => array('TYPE')
		));

		if ($row = $res->fetch())
		{
			$result = $row['TYPE'];
		}

		return $result;
	}

	public static function set($userId, $eventId, $type)
	{
		$userId = intval($userId);
		$type = ($type == "Y" ? "Y" : "N");
		$eventId = trim($eventId);
		if (strlen($eventId) <= 0)
		{
			throw new Main\SystemException("Empty eventId.");
		}
		$eventId = \CSocNetLogTools::findFullSetByEventID($eventId);

		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		foreach ($eventId as $val)
		{
			$insertFields = array(
				"USER_ID" => $userId,
				"TYPE" => $type,
				"EVENT_ID" => $helper->forSql($val),
			);

			$updateFields = array(
				"TYPE" => $type
			);

			$merge = $helper->prepareMerge(
				static::getTableName(),
				array("USER_ID", "EVENT_ID"),
				$insertFields,
				$updateFields
			);

			if ($merge[0] != "")
			{
				$connection->query($merge[0]);
			}
		}
	}

	public static function checkExpertModeAuto($userId, $tasksNum, $pageSize)
	{
		$result = false;

		$userId = intval($userId);
		$tasksNum = intval($tasksNum);
		$pageSize = intval($pageSize);

		if (
			$userId <= 0
			|| $pageSize <= 0
		)
		{
			return false;
		}

		if (
			$tasksNum >= 5
			&& ($tasksNum / $pageSize) >= 0.25
		)
		{
			$isAlreadyChecked = \CUserOptions::getOption("socialnetwork", "~log_expertmode_checked", "N", $userId);
			if ($isAlreadyChecked != 'Y')
			{
				self::set($userId, 'tasks', 'N');
				\CUserOptions::setOption("socialnetwork", "~log_expertmode_checked", "Y", false, $userId);
				$result = true;
			}
		}

		return $result;
	}

	public static function add(array $data)
	{
		throw new NotImplementedException("Use set() method of the class.");
	}

	public static function update($primary, array $data)
	{
		throw new NotImplementedException("Use set() method of the class.");
	}
}
