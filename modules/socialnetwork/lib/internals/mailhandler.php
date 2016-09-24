<?php

namespace Bitrix\Socialnetwork\Internals;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config;
use Bitrix\Main\EventManager;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

/**
 * Class for incoming mail event handlers
 *
 * Class MailHandler
 * @package Bitrix\Socialnetwork\Internals
 */
final class MailHandler
{
	/**
	 * Adds new comment from mail
	 *
	 * @param \Bitrix\Main\Event $event Event.
	 * @return int|false
	 */
	public static function handleReplyReceivedLogEntry(\Bitrix\Main\Event $event)
	{
		global $USER, $DB;

		$logEntryId = intval($event->getParameter('entity_id'));
		$userId = intval($event->getParameter('from'));
		$message = trim($event->getParameter('content'));
		$attachments = $event->getParameter('attachments');

		$commentId = false;

		if (
			strlen($message) <= 0
			&& count($attachments) > 0
		)
		{
			$message = Loc::getMessage('SONET_MAILHANDLER_ATTACHMENTS');
		}

		if (
			$logEntryId <= 0
			|| $userId <= 0
			|| strlen($message) <= 0
		)
		{
			return false;
		}

		$res = \CSocNetLog::getList(
			array(),
			array(
				"ID" => $logEntryId
			),
			false,
			false,
			array("ID", "ENTITY_TYPE", "ENTITY_ID", "EVENT_ID", "USER_ID", "SOURCE_ID")
		);

		if (!($logEntry = $res->fetch()))
		{
			return false;
		}

		$siteId = \Bitrix\Socialnetwork\Util::getSiteIdByLogId($logEntry["ID"]);
		$res = \CSite::GetByID($siteId);
		$site = $res->fetch();

		$pathToUser = Config\Option::get("main", "TOOLTIP_PATH_TO_USER", false, $siteId);
		$pathToSmile = Config\Option::get("socialnetwork", "smile_page", false, $siteId);

		$commentEvent = \CSocNetLogTools::findLogCommentEventByLogEventID($logEntry["EVENT_ID"]);
		if (is_array($commentEvent))
		{
			if (\Bitrix\Socialnetwork\ComponentHelper::canAddComment($logEntry, $commentEvent))
			{
				$fields = array(
					"ENTITY_TYPE" => $logEntry["ENTITY_TYPE"],
					"ENTITY_ID" => $logEntry["ENTITY_ID"],
					"EVENT_ID" => $commentEvent["EVENT_ID"],
					"=LOG_DATE" => $DB->currentTimeFunction(),
					"MESSAGE" => $message,
					"TEXT_MESSAGE" => $message,
					"MODULE_ID" => false,
					"LOG_ID" => $logEntry["ID"],
					"USER_ID" => $userId
				);

				$ufCode = (
					ModuleManager::isModuleInstalled("webdav")
					|| ModuleManager::isModuleInstalled("disk")
						? "UF_SONET_COM_DOC"
						: "UF_SONET_COM_FILE"
				);
				$fields[$ufCode] = array();

				$type = false;
				$attachmentRelations = array();

				foreach ($attachments as $key => $attachedFile)
				{
					$resultId = \CSocNetLogComponent::saveFileToUF($attachedFile, $type, $userId);
					if ($resultId)
					{
						$fields[$ufCode][] = $resultId;
						$attachmentRelations[$key] = $resultId;
					}
				}

				$fields["MESSAGE"] = preg_replace_callback(
					"/\[ATTACHMENT\s*=\s*([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER,
					function ($matches) use ($attachmentRelations)
					{
						if (isset($attachmentRelations[$matches[1]]))
						{
							return "[DISK FILE ID=".$attachmentRelations[$matches[1]]."]";
						}
					},
					$fields["MESSAGE"]
				);

				$commentId = \CSocNetLogComments::add($fields, true, false);

				if ($commentId)
				{
					foreach (EventManager::getInstance()->findEventHandlers('socialnetwork', 'OnAfterSocNetLogEntryCommentAdd') as $handler)
					{
						ExecuteModuleEventEx($handler, array($logEntry, array(
							"SITE_ID" => $siteId,
							"COMMENT_ID" => $commentId
						)));
					}

					$skipCounterIncrement = false;
					foreach (EventManager::getInstance()->findEventHandlers('socialnetwork', 'OnBeforeSocNetLogCommentCounterIncrement') as $handler)
					{
						if (ExecuteModuleEventEx($handler, array($logEntry)) === false)
						{
							$skipCounterIncrement = true;
							break;
						}
					}

					if (!$skipCounterIncrement)
					{
						\CSocNetLog::counterIncrement(
							$commentId,
							false,
							false,
							"LC",
							\CSocNetLogRights::checkForUserAll($logEntry["ID"])
						);
					}

					if ($comment = \CSocNetLogComments::getByID($commentId))
					{
						\Bitrix\Socialnetwork\ComponentHelper::addLiveComment(
							$comment,
							$logEntry,
							$commentEvent,
							array(
								"ACTION" => 'ADD',
								"SOURCE_ID" => 0,
								"TIME_FORMAT" => \CSite::getTimeFormat(),
								"PATH_TO_USER" => $pathToUser,
								"NAME_TEMPLATE" => \CSite::getNameFormat(null, $site["ID"]),
								"SHOW_LOGIN" => "N",
								"AVATAR_SIZE" => 39,
								"PATH_TO_SMILE" => $pathToSmile,
								"LANGUAGE_ID" => $site["LANGUAGE_ID"],
								"SITE_ID" => $site["ID"],
								"PULL" => "Y"
							)
						);
					}
				}
			}
		}

		return $commentId;
	}
}
