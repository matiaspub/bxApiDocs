<?php
namespace Bitrix\Im\Replica;

class MessageParamHandler extends \Bitrix\Replica\Client\BaseHandler
{
	protected $tableName = "b_im_message_param";
	protected $moduleId = "im";
	protected $className = "\\Bitrix\\Im\\MessageParamTable";
	protected $primary = array(
		"ID" => "auto_increment",
	);
	protected $predicates = array(
		"MESSAGE_ID" => "b_im_message.ID",
	);

	public function __construct()
	{
		$this->translation = array(
			"MESSAGE_ID" => "b_im_message.ID",
			"PARAM_VALUE" => array($this, "paramValueTranslation"),
		);
	}

	/**
	 * Returns relation depending on record values.
	 *
	 * @param array $record Database record.
	 * @return string|false
	 */
	public static function paramValueTranslation($record)
	{
		if ($record["PARAM_NAME"] === "LIKE" && $record["PARAM_VALUE"])
		{
			return "b_user.ID";
		}
 		if ($record["PARAM_NAME"] === "FILE_ID" && $record["PARAM_VALUE"])
 		{
 			return "b_file.ID";
 		}
		return false;
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
		$id = intval($newRecord['MESSAGE_ID']);

		if (!\Bitrix\Main\Loader::includeModule('pull'))
			return;

		$message = \CIMMessenger::GetById($id, Array('WITH_FILES' => 'Y'));
		if (!$message)
			return;

		if ($newRecord['PARAM_NAME'] === 'LIKE' && $newRecord["PARAM_VALUE"])
		{
			$like = $message['PARAMS']['LIKE'];

			$result = \Bitrix\IM\ChatTable::getList(Array(
				'filter'=>Array(
					'=ID' => $message['CHAT_ID']
				)
			));
			$chat = $result->fetch();

			$relations = \CIMMessenger::GetRelationById($id);
			if (!isset($relations[$newRecord["PARAM_VALUE"]]))
				return;
			
			if ($message['AUTHOR_ID'] > 0 && $message['AUTHOR_ID'] != $newRecord["PARAM_VALUE"])
			{
				$CCTP = new \CTextParser();
				$CCTP->MaxStringLen = 200;
				$CCTP->allow = array("HTML" => "N", "ANCHOR" => "N", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");

				$message['MESSAGE'] = str_replace('<br />', ' ', $CCTP->convertText($message['MESSAGE']));
				$message['MESSAGE'] = preg_replace("/\[s\].*?\[\/s\]/i", "", $message['MESSAGE']);
				$message['MESSAGE'] = preg_replace("/\[[bui]\](.*?)\[\/[bui]\]/i", "$1", $message['MESSAGE']);
				$message['MESSAGE'] = preg_replace("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", "$2", $message['MESSAGE']);
				$message['MESSAGE'] = preg_replace("/------------------------------------------------------(.*)------------------------------------------------------/mi", " [".GetMessage('IM_QUOTE')."] ", str_replace(array("#BR#"), Array(" "), $message['MESSAGE']));

				if (count($message['FILES']) > 0 && strlen($message['MESSAGE']) < 200)
				{
					foreach ($message['FILES'] as $file)
					{
						$file = " [".GetMessage('IM_MESSAGE_FILE').": ".$file['name']."]";
						if (strlen($message['MESSAGE'].$file) > 200)
							break;

						$message['MESSAGE'] .= $file;
					}
					$message['MESSAGE'] = trim($message['MESSAGE']);
				}

				$isChat = $chat && strlen($chat['TITLE']) > 0;

				$dot = strlen($message['MESSAGE'])>=200? '...': '';
				$message['MESSAGE'] = substr($message['MESSAGE'], 0, 199).$dot;
				$message['MESSAGE'] = strlen($message['MESSAGE'])>0? $message['MESSAGE']: '-';

				$arMessageFields = array(
					"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
					"TO_USER_ID" => $message['AUTHOR_ID'],
					"FROM_USER_ID" => $newRecord["PARAM_VALUE"],
					"NOTIFY_TYPE" => IM_NOTIFY_FROM,
					"NOTIFY_MODULE" => "main",
					"NOTIFY_EVENT" => "rating_vote",
					"NOTIFY_TAG" => "RATING|IM|".($isChat? 'G':'P')."|".($isChat? $chat['ID']: $newRecord["PARAM_VALUE"])."|".$id,
					"NOTIFY_MESSAGE" => GetMessage($isChat? 'IM_MESSAGE_LIKE': 'IM_MESSAGE_LIKE_PRIVATE', Array(
						'#MESSAGE#' => $message['MESSAGE'],
						'#TITLE#' => $chat['TITLE']
					))
				);
				\CIMNotify::Add($arMessageFields);
			}

			$arPullMessage = Array(
				'id' => $id,
				'chatId' => $relations[$newRecord["PARAM_VALUE"]]['CHAT_ID'],
				'senderId' => $newRecord["PARAM_VALUE"],
				'users' => $like
			);

			foreach ($relations as $rel)
			{
				\CPullStack::AddByUser($rel['USER_ID'], Array(
					'module_id' => 'im',
					'command' => 'messageLike',
					'params' => $arPullMessage
				));
			}
		}
	}
}
