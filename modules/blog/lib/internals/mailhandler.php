<?php

namespace Bitrix\Blog\Internals;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config;

Loc::loadMessages(__FILE__);

/**
 * Class for incoming mail event handlers
 *
 * Class MailHandler
 * @package Bitrix\Blog\Internals
 */
final class MailHandler
{
	/**
	 * Adds new comment from mail
	 *
	 * @param \Bitrix\Main\Event $event Event.
	 * @return int|false
	 */
	
	/**
	* <p>Добавляет новый комментарий из почты. Метод статический.</p>
	*
	*
	* @param mixed $Bitrix  Событие.
	*
	* @param Bitri $Main  
	*
	* @param Event $event  
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/blog/internals/mailhandler/handlereplyreceivedblogpost.php
	* @author Bitrix
	*/
	public static function handleReplyReceivedBlogPost(\Bitrix\Main\Event $event)
	{
		$postId = intval($event->getParameter('entity_id'));
		$userId = intval($event->getParameter('from'));
		$message = trim($event->getParameter('content'));
		$attachments = $event->getParameter('attachments');

		if (
			strlen($message) <= 0
			&& count($attachments) > 0
		)
		{
			$message = Loc::getMessage('BLOG_MAILHANDLER_ATTACHMENTS');
		}

		if (
			$postId <= 0
			|| $userId <= 0
			|| strlen($message) <= 0
		)
		{
			return false;
		}

		$res = \CBlogPost::getList(
			array(),
			array(
				"ID" => $postId
			),
			false,
			false,
			array("BLOG_ID", "AUTHOR_ID", "BLOG_OWNER_ID")
		);

		if (!($blogPost = $res->fetch()))
		{
			return false;
		}

		$perm = BLOG_PERMS_DENY;

		if ($blogPost["AUTHOR_ID"] == $userId)
		{
			$perm = BLOG_PERMS_FULL;
		}
		else
		{
			$postPerm = \CBlogPost::getSocNetPostPerms($postId, false, $userId, $blogPost["AUTHOR_ID"]);
			if ($postPerm > BLOG_PERMS_DENY)
			{
				$perm = \CBlogComment::getSocNetUserPerms($postId, $blogPost["AUTHOR_ID"], $userId);
			}
		}

		if ($perm == BLOG_PERMS_DENY)
		{
			return false;
		}

		$res = \CBlogComment::getList(
			array("ID" => "DESC"),
			array(
				"BLOG_ID" => $blogPost["BLOG_ID"],
				"POST_ID" => $postId,
				"AUTHOR_ID" => $userId
			),
			false,
			array("nTopCount" => 1),
			array("ID", "POST_ID", "BLOG_ID", "AUTHOR_ID", "POST_TEXT")
		);

		if (
			($duplicateComment = $res->fetch())
			&& md5($duplicateComment["POST_TEXT"]) == md5($message)
			&& strlen($message) > 10
		)
		{
			return false;
		}

		$fields = Array(
			"POST_ID" => $postId,
			"BLOG_ID" => $blogPost["BLOG_ID"],
			"TITLE" => '',
			"POST_TEXT" => $message,
			"AUTHOR_ID" => $userId,
			"DATE_CREATE" => ConvertTimeStamp(time() + \CTimeZone::getOffset(), "FULL")
		);

		if ($perm == BLOG_PERMS_PREMODERATE)
		{
			$fields["PUBLISH_STATUS"] = BLOG_PUBLISH_STATUS_READY;
		}

		$ufCode = (
			isModuleInstalled("webdav")
			|| isModuleInstalled("disk")
				? "UF_BLOG_COMMENT_FILE"
				: "UF_BLOG_COMMENT_DOC"
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

		$fields["POST_TEXT"] = preg_replace_callback(
			"/\[ATTACHMENT\s*=\s*([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER,
			function ($matches) use ($attachmentRelations)
			{
				if (isset($attachmentRelations[$matches[1]]))
				{
					return "[DISK FILE ID=".$attachmentRelations[$matches[1]]."]";
				}
			},
			$fields["POST_TEXT"]
		);

		$commentId = \CBlogComment::add($fields);

		if ($commentId)
		{
			if (!empty($fields[$ufCode]))
			{
				$tmp = array(
					"AUTHOR_ID" => $fields["AUTHOR_ID"],
					$ufCode => $fields[$ufCode]
				);

				\Bitrix\Disk\Uf\FileUserType::setValueForAllowEdit(array(
					"ENTITY_ID" => "BLOG_COMMENT",
					"VALUE_ID" => $commentId
				), true);

				\CBlogComment::update($commentId, $tmp);
			}

			\BXClearCache(true, "/blog/comment/".intval($postId / 100)."/".$postId."/");
			$connection = \Bitrix\Main\Application::getConnection();
			$helper = $connection->getSqlHelper();

			$connection->query("UPDATE b_blog_image SET COMMENT_ID=".intval($commentId)." WHERE BLOG_ID=".intval($blogPost["BLOG_ID"])." AND POST_ID=".$postId." AND IS_COMMENT = 'Y' AND (COMMENT_ID = 0 OR COMMENT_ID is null) AND USER_ID=".$userId);

			if (Loader::includeModule('socialnetwork'))
			{
				$res = \CSocNetLog::getList(
					array(),
					array(
						"EVENT_ID" => array("blog_post", "blog_post_important"),
						"SOURCE_ID" => $postId
					),
					false,
					false,
					array("ID")
				);

				if ($log = $res->fetch())
				{
					$extranetSiteId = false;
					if (Loader::includeModule('extranet'))
					{
						$extranetSiteId = \CExtranet::getExtranetSiteId();
					}

					$logSiteId = array();
					$res = \CSocNetLog::getSite($log["ID"]);
					while ($logSite = $res->fetch())
					{
						$logSiteId[] = $logSite["LID"];
					}

					$siteId = (
						$extranetSiteId
						&& count($logSiteId) == 1
						&& $logSiteId[0] == $extranetSiteId
							? $extranetSiteId
							: $logSiteId[0]
					);

					$postUrl = Config\Option::get("socialnetwork", "userblogpost_page", '/company/personal/users/'.$blogPost["BLOG_OWNER_ID"].'/blog/#post_id#/', $siteId);
					$postUrl = \CComponentEngine::makePathFromTemplate(
						$postUrl,
						array(
							"user_id" => $blogPost["AUTHOR_ID"],
							"post_id" => $postId
						)
					);

					$fieldsSocnet = array(
						"ENTITY_TYPE" => SONET_ENTITY_USER,
						"ENTITY_ID" => $blogPost["BLOG_OWNER_ID"],
						"EVENT_ID" => "blog_comment",
						"USER_ID" => $userId,
						"=LOG_DATE" => $helper->getCurrentDateTimeFunction(),
						"MESSAGE" => $message,
						"TEXT_MESSAGE" => $message,
						"URL" => $postUrl,
						"MODULE_ID" => false,
						"SOURCE_ID" => $commentId,
						"LOG_ID" => $log["ID"],
						"RATING_TYPE_ID" => "BLOG_COMMENT",
						"RATING_ENTITY_ID" => $commentId
					);

					$logCommentId = \CSocNetLogComments::add($fieldsSocnet, false, false);

					if ($logCommentId > 0)
					{
						\CSocNetLog::counterIncrement(
							$logCommentId,
							false,
							false,
							"LC",
							\CSocNetLogRights::checkForUserAll($log["ID"])
						);
					}

					$postSonetRights = \CBlogPost::getSocnetPerms($postId);
					$userCode = array();
					$mailUserId = array();
					if (!empty($postSonetRights["U"]))
					{
						$mailUserId = array_keys($postSonetRights["U"]);
						foreach($postSonetRights["U"] as $k => $v)
						{
							$userCode[] = "U".$k;
						}
					}

					$fieldsIM = Array(
						"TYPE" => "COMMENT",
						"TITLE" => htmlspecialcharsBack($blogPost["TITLE"]),
						"URL" => $postUrl,
						"ID" => $postId,
						"FROM_USER_ID" => $userId,
						"TO_USER_ID" => array($blogPost["AUTHOR_ID"]),
						"TO_SOCNET_RIGHTS" => $userCode,
						"TO_SOCNET_RIGHTS_OLD" => array(
							"U" => array(),
							"SG" => array()
						),
						"AUTHOR_ID" => $blogPost["AUTHOR_ID"],
						"BODY" => $message,
					);

					$fieldsIM["EXCLUDE_USERS"] = array();

					$res = \CSocNetLogFollow::getList(
						array(
							"CODE" => "L".$log["ID"],
							"TYPE" => "N"
						),
						array("USER_ID")
					);

					while ($unfollower = $res->fetch())
					{
						$fieldsIM["EXCLUDE_USERS"][$unfollower["USER_ID"]] = $unfollower["USER_ID"];
					}

					\CBlogPost::notifyIm($fieldsIM);

					if (!empty($mailUserId))
					{
						\CBlogPost::notifyMail(array(
							"type" => "COMMENT",
							"userId" => $mailUserId,
							"authorId" => $userId,
							"postId" => $postId,
							"commentId" => $commentId,
							"siteId" => $siteId,
							"postUrl" => \CComponentEngine::makePathFromTemplate(
								'/pub/post.php?post_id=#post_id#',
								array(
									"post_id"=> $postId
								)
							)
						));
					}

					$siteResult = \CSite::getByID($siteId);

					if ($site = $siteResult->fetch())
					{
						\CBlogComment::addLiveComment($commentId, array(
							"DATE_TIME_FORMAT" => $site["FORMAT_DATETIME"],
							"NAME_TEMPLATE" => \CSite::getNameFormat(null, $siteId),
							"SHOW_LOGIN" => "Y",
							"MODE" => "PULL_MESSAGE"
						));
					}
				}
			}
		}

		return $commentId;
	}
	/**
	 * Adds new post from mail
	 *
	 * @param \Bitrix\Main\Event $event Event.
	 * @return int|false
	 */
	
	/**
	* <p>Добавляет новый пост из почты. Метод статический.</p>
	*
	*
	* @param mixed $Bitrix  Событие.
	*
	* @param Bitri $Main  
	*
	* @param Event $event  
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/blog/internals/mailhandler/handleforwardreceivedblogpost.php
	* @author Bitrix
	*/
	public static function handleForwardReceivedBlogPost(\Bitrix\Main\Event $event)
	{
		$userId = intval($event->getParameter('from'));
		$message = trim($event->getParameter('content'));
		$subject = trim($event->getParameter('subject'));
		$attachments = $event->getParameter('attachments');
		$siteId = $event->getParameter('site_id');

		if (
			strlen($message) <= 0
			&& count($attachments) > 0
		)
		{
			$message = Loc::getMessage('BLOG_MAILHANDLER_ATTACHMENTS');
		}

		if (
			$userId <= 0
			|| strlen($message) <= 0
			|| strlen($siteId) <= 0
		)
		{
			return false;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			throw new Main\SystemException("Could not load 'socialnetwork' module.");
		}

		$pathToPost = Config\Option::get("socialnetwork", "userblogpost_page", '', $siteId);
		$blog = $postId = false;

		if ($blogGroupId = Config\Option::get("socialnetwork", "userbloggroup_id", false, $siteId))
		{
			$res = \CBlog::getList(array(), array(
				"ACTIVE" => "Y",
				"USE_SOCNET" => "Y",
				"GROUP_ID" => $blogGroupId,
				"GROUP_SITE_ID" => $siteId,
				"OWNER_ID" => $userId
			));
			$blog = $res->fetch();
		}

		if (
			!$blog
			&& isModuleInstalled("intranet")
		)
		{
			$ideaBlogGroupIdList = array();
			if (isModuleInstalled("idea"))
			{
				$res = \CSite::getList($by="sort", $order="desc", Array("ACTIVE" => "Y"));
				while ($site = $res->fetch())
				{
					$val = Config\Option::get("idea", "blog_group_id", false, $site["LID"]);
					if ($val)
					{
						$ideaBlogGroupIdList[] = $val;
					}
				}
			}

			if (empty($ideaBlogGroupIdList))
			{
				$blog = \CBlog::getByOwnerID($userId);
			}
			else
			{
				$blogGroupIdList = array();
				$res = \CBlogGroup::getList(array(), array(), false, false, array("ID"));
				while($blogGroup = $res->fetch())
				{
					if (!in_array($blogGroup["ID"], $ideaBlogGroupIdList))
					{
						$blogGroupIdList[] = $blogGroup["ID"];
					}
				}

				$blog = \CBlog::getByOwnerID($userId, $blogGroupIdList);
			}
		}

		if (
			!$blog
			&& intval($blogGroupId) > 0
		)
		{
			$blog = \Bitrix\Socialnetwork\ComponentHelper::createUserBlog(array(
				"BLOG_GROUP_ID" => intval($blogGroupId),
				"USER_ID" => $userId,
				"SITE_ID" => $siteId
			));
		}

		if ($blog)
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$helper = $connection->getSqlHelper();

			$fields = Array(
				"BLOG_ID" => $blog["ID"],
				"AUTHOR_ID" => $userId,
				"=DATE_CREATE" => $helper->getCurrentDateTimeFunction(),
				"=DATE_PUBLISH" => $helper->getCurrentDateTimeFunction(),
				"MICRO" => "N",
				"TITLE" => $subject,
				"DETAIL_TEXT" => $message,
				"DETAIL_TEXT_TYPE" => "text",
				"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
				"HAS_IMAGES" => "N",
				"HAS_TAGS" => "N",
				"HAS_SOCNET_ALL" => "N",
				"SOCNET_RIGHTS" => array("U".$userId)
			);

			if (strlen($fields["TITLE"]) <= 0)
			{
				$fields["MICRO"] = "Y";
				$fields["TITLE"] = TruncateText(trim(preg_replace(array("/\n+/is".BX_UTF_PCRE_MODIFIER, "/\s+/is".BX_UTF_PCRE_MODIFIER), " ", \blogTextParser::killAllTags($fields["DETAIL_TEXT"]))), 100);
				if(strlen($fields["TITLE"]) <= 0)
				{
					$fields["TITLE"] = Loc::getMessage("BLOG_MAILHANDLER_EMPTY_TITLE_PLACEHOLDER");
				}
			}

			$ufCode = (
				isModuleInstalled("webdav")
				|| isModuleInstalled("disk")
					? "UF_BLOG_POST_FILE"
					: "UF_BLOG_POST_DOC"
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

			$fields["HAS_PROPS"] = (!empty($attachmentRelations) ? "Y" :"N");

			$fields["DETAIL_TEXT"] = preg_replace_callback(
				"/\[ATTACHMENT\s*=\s*([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER,
				function ($matches) use ($attachmentRelations)
				{
					return (
						isset($attachmentRelations[$matches[1]])
							? "[DISK FILE ID=".$attachmentRelations[$matches[1]]."]"
							: ""
					);
				},
				$fields["DETAIL_TEXT"]
			);

			$postId = \CBlogPost::add($fields);

			if ($postId)
			{
				if (!empty($fields[$ufCode]))
				{
					$tmp = array(
						"AUTHOR_ID" => $fields["AUTHOR_ID"],
						$ufCode => $fields[$ufCode]
					);

					\Bitrix\Disk\Uf\FileUserType::setValueForAllowEdit(array(
						"ENTITY_ID" => "BLOG_POST",
						"VALUE_ID" => $postId
					), true);

					\CBlogPost::update($postId, $tmp);
				}

				BXClearCache(true, "/".$siteId."/blog/last_messages_list/");

				$fields["ID"] = $postId;
				$paramsNotify = array(
					"bSoNet" => true,
					"UserID" => $userId,
					"allowVideo" => "N",
					"PATH_TO_SMILE" => Config\Option::get("socialnetwork", "smile_page", '', $siteId),
					"PATH_TO_POST" => $pathToPost,
					"user_id" => $userId,
					"NAME_TEMPLATE" => \CSite::getNameFormat(null, $siteId),
					"SHOW_LOGIN" => "Y",
					"SEND_COUNTER_TO_AUTHOR" => "Y"
				);
				\CBlogPost::notify($fields, $blog, $paramsNotify);

				if (Loader::includeModule('im'))
				{
					$postUrl = \CComponentEngine::makePathFromTemplate($pathToPost, array(
						"post_id" => $postId,
						"user_id" => $userId
					));

					$processedPathData = \CSocNetLogTools::processPath(array("POST_URL" => $postUrl), $userId, $siteId);
					$serverName = $processedPathData["SERVER_NAME"];
					$postUrl = $processedPathData["URLS"]["POST_URL"];

					\CIMNotify::add(array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
						"NOTIFY_MODULE" => "blog",
						"NOTIFY_EVENT" => "post_mail",
						"NOTIFY_TAG" => "BLOG|POST|".$postId,
						"TO_USER_ID" => $userId,
						"NOTIFY_MESSAGE" => Loc::getMessage("BLOG_MAILHANDLER_NEW_POST", array(
							"#TITLE#" => "<a href=\"".$postUrl."\">".$fields["TITLE"]."</a>"
						)),
						"NOTIFY_MESSAGE_OUT" => Loc::getMessage("BLOG_MAILHANDLER_NEW_POST", array(
								"#TITLE#" => $fields["TITLE"]
							)).' '.$serverName.$postUrl
					));
				}
			}
		}

		return $postId;
	}
}
