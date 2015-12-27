<?php
namespace Bitrix\Im\Replica;

class MessageHandler extends \Bitrix\Replica\Client\BaseHandler
{
	protected $tableName = "b_im_message";
	protected $moduleId = "im";
	protected $className = "\\Bitrix\\Im\\MessageTable";
	protected $primary = array(
		"ID" => "auto_increment",
	);
	protected $predicates = array(
		"AUTHOR_ID" => "b_user.ID",
		"CHAT_ID" => "b_im_chat.ID",
	);
	protected $translation = array(
		"ID" => "b_im_message.ID",
		"CHAT_ID" => "b_im_chat.ID",
		"AUTHOR_ID" => "b_user.ID",
	);
	protected $fields = array(
		"DATE_CREATE" => "datetime",
		"MESSAGE" => "text",
		"MESSAGE_OUT" => "text",
	);

	/**
	 * Called before log write. You may return false and not log write will take place.
	 *
	 * @param array $record Database record.
	 * @return boolean
	 */
	static public function beforeLogInsert(array $record)
	{
		if (
			$record["NOTIFY_TYPE"] <= 0
			|| preg_match("/^RATING\\|IM/", $record["NOTIFY_TAG"])
		)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method will be invoked before new database record inserted.
	 *
	 * @param array &$newRecord All fields of inserted record.
	 *
	 * @return void
	 */
	public function beforeInsertTrigger(array &$newRecord)
	{
		$newRecord["MESSAGE"] = $this->fixMessage($newRecord["MESSAGE"]);
	}

	/**
	 * Method will be invoked before an database record updated.
	 *
	 * @param array $oldRecord All fields before update.
	 * @param array &$newRecord All fields after update.
	 *
	 * @return void
	 */
	public function beforeUpdateTrigger(array $oldRecord, array &$newRecord)
	{
		if (array_key_exists("MESSAGE", $newRecord))
		{
			$newRecord["MESSAGE"] = $this->fixMessage($newRecord["MESSAGE"]);
		}
	}

	/**
	 * Replaces some BB codes on receiver to display them properly.
	 *
	 * @param string $message A message.
	 *
	 * @return string
	 */
	protected function fixMessage($message)
	{
		$fixed = preg_replace("/\\[CHAT=[0-9]\\](.*?)\\[\\/CHAT\\]/", "\\1", $message);
		if ($fixed == null)
		{
			return $message;
		}

		$fixed = preg_replace("/\\[USER=[0-9]\\](.*?)\\[\\/USER\\]/", "\\1", $fixed);
		if ($fixed == null)
		{
			return $message;
		}

		return $fixed;
	}

	/**
	 * Method will be invoked after new database record inserted.
	 *
	 * @param array $newRecord All fields of inserted record.
	 *
	 * @return void
	 */
	static public function afterInsertTrigger(array $newRecord)
	{
		$arParams = array();

		$chatId = $newRecord['CHAT_ID'];
		$arRel = \CIMChat::GetRelationById($chatId);
		//AddMessage2Log($newRecord);
		//AddMessage2Log($arRel);
		$arFields['MESSAGE_TYPE'] = '';
		foreach ($arRel as $rel)
		{
			$arFields['MESSAGE_TYPE'] = $rel["MESSAGE_TYPE"];
			break;
		}
		//AddMessage2Log($arParams);
		//CUserCounter::Increment($arFields['TO_USER_ID'], 'im_message_v2', '**', false);

		if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			foreach ($arRel as $rel)
			{
				if ($rel['USER_ID'] == $newRecord['AUTHOR_ID'])
					$arFields['FROM_USER_ID'] = $rel['USER_ID'];
				else
					$arFields['TO_USER_ID'] = $rel['USER_ID'];
			}

			\CIMContactList::SetRecent(Array(
				'ENTITY_ID' => $arFields['TO_USER_ID'],
				'MESSAGE_ID' => $newRecord['ID'],
				'CHAT_TYPE' => IM_MESSAGE_PRIVATE,
				'USER_ID' => $arFields['FROM_USER_ID']
			));

			\CIMContactList::SetRecent(Array(
				'ENTITY_ID' => $arFields['FROM_USER_ID'],
				'MESSAGE_ID' => $newRecord['ID'],
				'CHAT_TYPE' => IM_MESSAGE_PRIVATE,
				'USER_ID' => $arFields['TO_USER_ID']
			));

			if (\CModule::IncludeModule('pull'))
			{
				$arPullTo = Array(
					'module_id' => 'im',
					'command' => 'message',
					'params' => \CIMMessage::GetFormatMessage(Array(
						'ID' => $newRecord['ID'],
						'CHAT_ID' => $chatId,
						'TO_USER_ID' => $arFields['TO_USER_ID'],
						'FROM_USER_ID' => $arFields['FROM_USER_ID'],
						'SYSTEM' => $newRecord['NOTIFY_EVENT'] == 'private_system'? 'Y': 'N',
						'MESSAGE' => $newRecord['MESSAGE'],
						'DATE_CREATE' => time(),
						//'PARAMS' => $arFields['PARAMS'],
						//'FILES' => $arFields['FILES'],
					)),
				);
				$arPullFrom = $arPullTo;

				\CPullStack::AddByUser($arFields['TO_USER_ID'], $arPullTo);
				\CPullStack::AddByUser($arFields['FROM_USER_ID'], $arPullFrom);

				\CPushManager::DeleteFromQueueBySubTag($arParams['FROM_USER_ID'], 'IM_MESS');
				//self::SendBadges($arParams['TO_USER_ID']);
			}
		}
		else if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_CHAT || $arFields['MESSAGE_TYPE'] == IM_MESSAGE_OPEN)
		{
			foreach ($arRel as $relation)
			{
				\CIMContactList::SetRecent(Array(
					'ENTITY_ID' => $relation['CHAT_ID'],
					'MESSAGE_ID' => $newRecord['ID'],
					'CHAT_TYPE' => $relation['MESSAGE_TYPE'],
					'USER_ID' => $relation['USER_ID']
				));
			}

			if (\CModule::IncludeModule('pull'))
			{
				$arPullTo = Array(
					'module_id' => 'im',
					'command' => 'messageChat',
					'params' => \CIMMessage::GetFormatMessage(Array(
						'ID' => $newRecord['ID'],
						'CHAT_ID' => $chatId,
						'TO_CHAT_ID' => $chatId,
						'FROM_USER_ID' => $newRecord['AUTHOR_ID'],
						'MESSAGE' => $newRecord['MESSAGE'],
						'SYSTEM' => $newRecord['AUTHOR_ID'] > 0? 'N': 'Y',
						'DATE_CREATE' => time(),
						//'PARAMS' => $arFields['PARAMS'],
						//'FILES' => $arFields['FILES'],
					)),
				);

				$arPullFrom = $arPullTo;

				foreach ($arRel as $rel)
				{
					if ($rel['USER_ID'] == $arParams['FROM_USER_ID'])
					{
						\CPullStack::AddByUser($arParams['FROM_USER_ID'], $arPullFrom);
						\CPushManager::DeleteFromQueueBySubTag($arParams['FROM_USER_ID'], 'IM_MESS');
					}
					else
					{
						\CPullStack::AddByUser($rel['USER_ID'], $arPullTo);
						//$usersForBadges[] = $rel['USER_ID'];
					}
				}
			}
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
		if (!\Bitrix\Main\Loader::includeModule('pull'))
			return;
//AddMessage2Log(array("OnAfterMessageUpdate", $newRecordBefore, $newRecord));
		$arFields = \CIMMessenger::GetById($newRecord['ID'], Array('WITH_FILES' => 'Y'));
		if (!$arFields)
			return;

		$arFields['DATE_MODIFY'] = time()+\CTimeZone::GetOffset();

		$CCTP = new \CTextParser();
		$CCTP->MaxStringLen = 200;
		$CCTP->allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "Y", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
		$pullMessage = $CCTP->convertText(htmlspecialcharsbx($arFields['MESSAGE']));

		$relations = \CIMChat::GetRelationById($arFields['CHAT_ID']);

		$arPullMessage = Array(
			'id' => $arFields['ID'],
			'type' => $arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE? 'private': 'chat',
			'text' => $pullMessage,
			'date' => $arFields['DATE_MODIFY'],
		);
		if ($arFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$arFields['FROM_USER_ID'] = $arFields['AUTHOR_ID'];
			foreach ($relations as $rel)
			{
				if ($rel['USER_ID'] != $arFields['AUTHOR_ID'])
					$arFields['TO_USER_ID'] = $rel['USER_ID'];
			}

			$arPullMessage['fromUserId'] = $arFields['FROM_USER_ID'];
			$arPullMessage['toUserId'] = $arFields['TO_USER_ID'];
		}
		else
		{
			$arPullMessage['chatId'] = $arFields['CHAT_ID'];
			$arPullMessage['senderId'] = $arFields['AUTHOR_ID'];
		}

		foreach ($relations as $rel)
		{
			\CPullStack::AddByUser($rel['USER_ID'], $p=Array(
				'module_id' => 'im',
				'command' => $arFields['PARAMS']['IS_DELETED']==='Y'? 'messageDelete': 'messageUpdate',
				'params' => $arPullMessage,
			));
			$obCache = new \CPHPCache();
			$obCache->CleanDir('/bx/imc/recent'.\CIMMessenger::GetCachePath($rel['USER_ID']));
		}
	}
}
