<?php
namespace Bitrix\Im\Replica;

class Bind
{
	/** @var \Bitrix\Im\Replica\StatusHandler */
	protected static $statusHandler = null;

	/**
	 * Initializes replication process on im side.
	 *
	 * @return void
	 */
	static public function start()
	{
		self::$statusHandler = new StatusHandler();
		\Bitrix\Replica\Client\HandlersManager::register(self::$statusHandler);
		\Bitrix\Replica\Client\HandlersManager::register(new ChatHandler());
		\Bitrix\Replica\Client\HandlersManager::register(new RelationHandler());
		\Bitrix\Replica\Client\HandlersManager::register(new MessageHandler());
		\Bitrix\Replica\Client\HandlersManager::register(new MessageParamHandler());
		\Bitrix\Replica\Client\HandlersManager::register(new StartWritingHandler());

		$eventManager = \Bitrix\Main\EventManager::getInstance();

		$eventManager->addEventHandler("main", "OnUserSetLastActivityDate", array(self::$statusHandler, "OnUserSetLastActivityDate"));
		\Bitrix\Replica\Server\Event::registerOperation("im_status_update", array(self::$statusHandler, "handleStatusUpdateOperation"));

		$eventManager->addEventHandler("socialservices", "OnAfterRegisterUserByNetwork", array(self::$statusHandler, "OnStartUserReplication"), false, 200);
		\Bitrix\Replica\Server\Event::registerOperation("im_status_bind", array(self::$statusHandler, "handleStatusBindOperation"));
	}

}
