<?php
namespace Bitrix\Im\Replica;

class StatusHandler extends \Bitrix\Replica\Client\BaseHandler
{
	public $insertIgnore = true;

	protected $tableName = "b_im_status";
	protected $moduleId = "im";
	protected $className = "\\Bitrix\\Im\\Model\\StatusTable";
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

	/**
	 * OnUserSetLastActivityDate event handler.
	 * Checks all users if they are marked for replication hence he is "remote".
	 * Then sends im_status_update operation to the database where this user is "local".
	 *
	 * @param \Bitrix\Main\Event $event Event object.
	 *
	 * @return void
	 * @see \Bitrix\Im\Replica\StatusHandler::handleStatusUpdateOperation
	 */
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
					$guid = \Bitrix\Replica\Client\User::getLocalUserGuid($userId).'@'.getNameByDomain();
					if ($guid && $map[$guid])
					{
						$event = array(
							"operation" => "im_status_update",
							"guid" => $guid,
							"nodes" => $map[$guid],
							"ts" => time(),
							"ip" => \Bitrix\Main\Application::getInstance()->getContext()->getServer()->get('REMOTE_ADDR'),
						);
						\Bitrix\Replica\Log\Client::getInstance()->write($map[$guid], $event);
					}
				}
				$cache->endDataCache(true);
			}
		}
	}

	/**
	 * Updates user last activity date.
	 *
	 * @param array $event Event made by onUserSetLastActivityDate method.
	 * @param string $nodeFrom Source database.
	 * @param string $nodeTo Target database.
	 *
	 * @return void
	 * @see \Bitrix\Im\Replica\StatusHandler::onUserSetLastActivityDate
	 */
	static public function handleStatusUpdateOperation($event, $nodeFrom, $nodeTo)
	{
		global $USER;
		if (!isset($event["guid"]))
		{
			return;
		}

		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$userId = $mapper->getByGuid("b_im_status.USER_ID", $event["guid"], $nodeFrom);
		if (!$userId)
		{
			return;
		}

		$USER->setLastActivityDate($userId);
	}

	/**
	 * OnAfterRegisterUserByNetwork event handler.
	 * Checks if user is marked for replication hence he is "remote".
	 * Then sends im_status_bind operation to the database where this user is "local".
	 *
	 * @param \Bitrix\Main\Event $event Event object.
	 *
	 * @return void
	 * @see \Bitrix\Im\Replica\StatusHandler::handleStatusBindOperation
	 */
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
			"guid" => $guid.'@'.$domainId,
			"nodes" => array($domainId),
			"ts" => time(),
			"ip" => \Bitrix\Main\Application::getInstance()->getContext()->getServer()->get('REMOTE_ADDR'),
		);
		\Bitrix\Replica\Log\Client::getInstance()->write(array($domainId), $event);
		\Bitrix\Replica\Mapper::getInstance()->add("b_im_status.USER_ID", $userId, $domainId, $event["guid"]);
	}

	/**
	 * Registers b_im_status record in the replication map
	 * and sends the record back as an insert operation.
	 *
	 * @param array $event Event made by onStartUserReplication method.
	 * @param string $nodeFrom Source database.
	 * @param string $nodeTo Target database.
	 *
	 * @return void
	 * @see \Bitrix\Im\Replica\StatusHandler::onStartUserReplication
	 */
	public function handleStatusBindOperation($event, $nodeFrom, $nodeTo)
	{
		if (!isset($event["guid"]))
		{
			return;
		}

		list ($userGuid,) = explode('@', $event["guid"]);
		if (!$userGuid)
		{
			return;
		}

		$userId = \Bitrix\Replica\Client\User::getLocalUserId($userGuid);
		if (!$userId)
		{
			return;
		}

		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$mapper->add("b_im_status.USER_ID", $userId, $nodeFrom, $event["guid"]);

		$res = \Bitrix\Im\Model\StatusTable::getById($userId);
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

	/**
	 * OnAfterRecentDelete event handler.
	 * Sends "unsubscribe" message from b_im_message changes to peer database.
	 *
	 * @param \Bitrix\Main\Event $event Event object.
	 *
	 * @return void
	 * @see \Bitrix\Im\Replica\StatusHandler::handleStatusUnbindOperation
	 */
	static public function OnAfterRecentDelete(\Bitrix\Main\Event $event)
	{
		$userId = $event->getParameter('user_id');
		if (!$userId)
		{
			return;
		}

		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$map = $mapper->getByPrimaryValue("b_im_status.USER_ID", false, $userId);
		if (!$map)
		{
			return;
		}

		$guid = key($map);
		list(, $targetNode) = explode("@", $guid, 2);

		$domainId = getNameByDomain();
		$event = array(
			"operation" => "im_status_unbind",
			"guid" => $guid,
			"nodes" => array($domainId),
			"ts" => time(),
			"ip" => \Bitrix\Main\Application::getInstance()->getContext()->getServer()->get('REMOTE_ADDR'),
		);
		\Bitrix\Replica\Log\Client::getInstance()->write(array($targetNode), $event);

	}

	/**
	 * Deletes b_im_status record from the replication map
	 *
	 * @param array $event Event made by OnAfterRecentDelete method.
	 * @param string $nodeFrom Source database.
	 * @param string $nodeTo Target database.
	 *
	 * @return void
	 * @see \Bitrix\Im\Replica\StatusHandler::OnAfterRecentDelete
	 */
	static public function handleStatusUnbindOperation($event, $nodeFrom, $nodeTo)
	{
		if (!isset($event["guid"]))
		{
			return;
		}

		list ($userGuid,) = explode('@', $event["guid"]);
		$userId = \Bitrix\Replica\Client\User::getLocalUserId($userGuid);
		if (!$userId > 0)
		{
			return;
		}

		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$mapper->deleteByGuid("b_im_status.USER_ID", $event["guid"], $nodeFrom);
	}

	/**
	 * OnAfterRecentAdd event handler.
	 * Sends "subscribe" message for b_im_message changes to peer database.
	 *
	 * @param \Bitrix\Main\Event $event Event object.
	 *
	 * @return void
	 * @see \Bitrix\Im\Replica\StatusHandler::handleStatusRebindOperation
	 */
	static public function OnAfterRecentAdd(\Bitrix\Main\Event $event)
	{
		$userId = $event->getParameter('user_id');
		if (!$userId)
		{
			return;
		}

		$userGuid = \Bitrix\Replica\Client\User::getRemoteUserGuid($userId);
		if (!$userGuid)
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
		$targetNode = current($map[$guid]);

		$domainId = getNameByDomain();
		$event = array(
			"operation" => "im_status_rebind",
			"guid" => $userGuid."@".$targetNode,
			"nodes" => array($domainId),
			"ts" => time(),
			"ip" => \Bitrix\Main\Application::getInstance()->getContext()->getServer()->get('REMOTE_ADDR'),
		);
		\Bitrix\Replica\Log\Client::getInstance()->write(array($targetNode), $event);
	}

	/**
	 * Registers b_im_status record in the replication map
	 * and sends the record back as an update operation.
	 *
	 * @param array $event Event made by OnAfterRecentAdd method.
	 * @param string $nodeFrom Source database.
	 * @param string $nodeTo Target database.
	 *
	 * @return void
	 * @see \Bitrix\Im\Replica\StatusHandler::OnAfterRecentAdd
	 */
	public function handleStatusRebindOperation($event, $nodeFrom, $nodeTo)
	{
		if (!isset($event["guid"]))
		{
			return;
		}

		list ($userGuid,) = explode('@', $event["guid"]);
		$userId = \Bitrix\Replica\Client\User::getLocalUserId($userGuid);
		if (!$userId)
		{
			return;
		}

		\Bitrix\Replica\Mapper::getInstance()->add("b_im_status.USER_ID", $userId, $nodeFrom, $event["guid"]);

		$res = \Bitrix\Im\Model\StatusTable::getById($userId);
		if ($res->fetch())
		{
			//Update operation
			\Bitrix\Replica\Db\Operation::writeUpdate(
				"b_im_status",
				$this->getPrimary(),
				array("USER_ID" => $userId)
			);
		}
	}
}
