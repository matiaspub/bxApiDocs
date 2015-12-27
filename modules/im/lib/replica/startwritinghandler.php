<?php
namespace Bitrix\Im\Replica;

class StartWritingHandler extends \Bitrix\Replica\Client\BaseHandler
{
	protected $moduleId = "im";

	static public function initDataManagerEvents()
	{
		\Bitrix\Main\EventManager::getInstance()->addEventHandler(
			"im",
			"OnStartWriting",
			array($this, "OnStartWriting")
		);
		\Bitrix\Main\EventManager::getInstance()->addEventHandler(
			"replica",
			"OnExecuteStartWriting",
			array($this, "OnExecuteStartWriting")
		);
	}

	public static function onStartWriting($userId, $dialogId)
	{
		$operation = new \Bitrix\Replica\Db\Execute();
		if (substr($dialogId, 0, 4) === "chat")
		{
			$chatId = substr($dialogId, 4);
			$operation->writeToLog(
				"StartWriting",
				array(
					array(
						"relation" => "b_user.ID",
						"value" => $userId,
					),
					array(
						"value" => "chat",
					),
					array(
						"relation" => "b_im_chat.ID",
						"value" => $chatId,
					),
				)
			);
		}
		else
		{
			$operation->writeToLog(
				"StartWriting",
				array(
					array(
						"relation" => "b_user.ID",
						"value" => $userId,
					),
					array(
						"value" => "",
					),
					array(
						"relation" => "b_user.ID",
						"value" => $dialogId,
					),
				)
			);
		}
	}

	public static function onExecuteStartWriting(\Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();
		$userId = $parameters[0];
		$dialogId = $parameters[1].$parameters[2];

		if ($userId > 0)
		{
			if (!\Bitrix\Main\Loader::includeModule('pull'))
				return;

			\CPushManager::DeleteFromQueueBySubTag($userId, 'IM_MESS');

			if (intval($dialogId) > 0)
			{
				\CPullStack::AddByUser($dialogId, Array(
					'module_id' => 'im',
					'command' => 'startWriting',
					'expiry' => 60,
					'params' => Array(
						'senderId' => $userId,
						'dialogId' => $dialogId
					),
				));
			}
			elseif (substr($dialogId, 0, 4) == 'chat')
			{
				$arRelation = \CIMChat::GetRelationById(substr($dialogId, 4));
				foreach ($arRelation as $rel)
				{
					if ($rel['USER_ID'] == $userId)
						continue;

					\CPullStack::AddByUser($rel['USER_ID'], Array(
						'module_id' => 'im',
						'command' => 'startWriting',
						'expiry' => 60,
						'params' => Array(
							'senderId' => $userId,
							'dialogId' => $dialogId
						),
					));
				}
			}
		}
	}
}
