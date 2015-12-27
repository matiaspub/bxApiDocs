<?php
namespace Bitrix\Im\Replica;

class StatusHandler extends \Bitrix\Replica\Client\BaseHandler
{
	protected $tableName = "b_im_status";
	protected $moduleId = "im";
	protected $className = "\\Bitrix\\Im\\StatusTable";
	protected $primary = array(
		"USER_ID" => "integer",
	);
	protected $predicates = array(
		"USER_ID" => "b_im_status.USER_ID",
	);
	protected $translation = array(
		"USER_ID" => "b_im_status.USER_ID",
	);
	protected $fields = array(
		"IDLE" => "datetime",
		"DESKTOP_LAST_DATE" => "datetime",
		"MOBILE_LAST_DATE" => "datetime",
		"EVENT_UNTIL_DATE" => "datetime",
	);

	/**
	 * Method will be invoked after new database record inserted.
	 *
	 * @param array $newRecord All fields of inserted record.
	 *
	 * @return void
	 */
	static public function afterInsertTrigger(array $newRecord)
	{
		if (\CIMStatus::Enable())
		{
			\CPullStack::AddShared(Array(
				'module_id' => 'online',
				'command' => 'user_status',
				'expiry' => 120,
				'params' => array(
					"USER_ID" => $newRecord["USER_ID"],
					"STATUS" => $newRecord["STATUS"],
				), //TODO \CIMStatus::PrepereToPush(
			));
		}
	}

	/**
	 * Method will be invoked after an database record updated.
	 *
	 * @param array $oldRecord All fields before update.
	 * @param array $newRecord All fields after update.
	 *
	 * @return void
	 */
	static public function afterUpdateTrigger(array $oldRecord, array $newRecord)
	{
		if ($oldRecord["STATUS"] !== $newRecord["STATUS"])
		{
			if (\CIMStatus::Enable())
			{
				\CPullStack::AddShared(Array(
					'module_id' => 'online',
					'command' => 'user_status',
					'expiry' => 120,
					'params' => array(
						"USER_ID" => $newRecord["USER_ID"],
						"STATUS" => $newRecord["STATUS"],
					), //TODO \CIMStatus::PrepereToPush(
				));
			}
		}
	}

	static public function onUserSetLastActivityDate(\Bitrix\Main\Event $event)
	{
		$users = $event->getParameter(0);
		foreach ($users as $userId)
		{
			$cache = \Bitrix\Main\Data\Cache::createInstance();
			if ($cache->startDataCache(60, $userId, '/im/status'))
			{
				$mapper = \Bitrix\Replica\Mapper::getInstance();
				$map = $mapper->getByPrimaryValue("b_im_status.USER_ID", false, $userId);
				if ($map)
				{
					$guid = \Bitrix\Replica\Client\User::getLocalUserGuid($userId);
					if ($guid && $map[$guid])
					{
						$event = array(
								"operation" => "im_status_update",
								"guid" => $guid,
						);
						\Bitrix\Replica\Log\Client::getInstance()->write($map[$guid], $event);
					}
				}
				$cache->endDataCache(true);
			}
		}
	}

	static public function handleStatusUpdateOperation($event, $nodeFrom, $nodeTo)
	{
		global $USER;
		if (isset($event["guid"]))
		{
			$userId = \Bitrix\Replica\Client\User::getId($event["guid"]);
			if ($userId > 0)
			{
				$USER->setLastActivityDate($userId);
			}
		}
	}

	static public function onStartUserReplication(\Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();

		$userId = $parameters[0];
		$domain = $parameters[2];

		$domainId = getNameByDomain($domain);
		if (!$domainId)
		{
			return;
		}

		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$map = $mapper->getByPrimaryValue("b_user.ID", false, $userId);
		if (!$map)
		{
			return;
		}

		$guid = key($map);
		$event = array(
			"operation" => "im_status_bind",
			"guid" => $guid,
		);
		\Bitrix\Replica\Log\Client::getInstance()->write(array($domainId), $event);
		\Bitrix\Replica\Mapper::getInstance()->add("b_im_status.USER_ID", $userId, $domainId, $event["guid"]);
	}

	public function handleStatusBindOperation($event, $nodeFrom, $nodeTo)
	{
		if (isset($event["guid"]))
		{
			$userId = \Bitrix\Replica\Client\User::getId($event["guid"]);
			if ($userId > 0)
			{
				\Bitrix\Replica\Mapper::getInstance()->add("b_im_status.USER_ID", $userId, $nodeFrom, $event["guid"]);
				$res = \Bitrix\Im\StatusTable::getById($userId);
				if ($res->fetch())
				{
					//Insert operation
					\Bitrix\Replica\Db\Operation::writeInsert(
						"b_im_status",
						$this->getPrimary(),
						array("USER_ID" => $userId)
					);
				}
			}
		}
	}
}
