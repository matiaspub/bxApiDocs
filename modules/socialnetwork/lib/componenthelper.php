<?php

namespace Bitrix\Socialnetwork;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;

Loc::loadMessages(__FILE__);

class ComponentHelper
{
	protected static $postsCache = array();
	protected static $commentsCache = array();
	protected static $commentListsCache = array();
	protected static $commentCountCache = array();
	protected static $authorsCache = array();
	protected static $destinationsCache = array();

	/**
	 * Returns data of a blog post
	 *
	 * @param int $postId Blog Post Id.
	 * @param string $languageId 2-char language Id.
	 * @return array|bool|false|mixed|null
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	*/
	public static function getBlogPostData($postId, $languageId)
	{
		global $USER_FIELD_MANAGER;

		if (isset(self::$postsCache[$postId]))
		{
			$result = self::$postsCache[$postId];
		}
		else
		{
			if (!Loader::includeModule('blog'))
			{
				throw new Main\SystemException("Could not load 'blog' module.");
			}

			$res = \CBlogPost::getList(
				array(),
				array(
					"ID" => $postId
				),
				false,
				false,
				array("ID", "BLOG_GROUP_ID", "BLOG_GROUP_SITE_ID", "BLOG_ID", "PUBLISH_STATUS", "TITLE", "AUTHOR_ID", "ENABLE_COMMENTS", "NUM_COMMENTS", "VIEWS", "CODE", "MICRO", "DETAIL_TEXT", "DATE_PUBLISH", "CATEGORY_ID", "HAS_SOCNET_ALL", "HAS_TAGS", "HAS_IMAGES", "HAS_PROPS", "HAS_COMMENT_IMAGES")
			);

			if ($result = $res->fetch())
			{
				$result["ATTACHMENTS"] = array();

				if($result["HAS_PROPS"] != "N")
				{
					$userFields = $USER_FIELD_MANAGER->getUserFields("BLOG_POST", $postId, $languageId);
					$postUf = array("UF_BLOG_POST_FILE");
					foreach ($userFields as $fieldName => $userField)
					{
						if (!in_array($fieldName, $postUf))
						{
							unset($userFields[$fieldName]);
						}
					}

					if (
						!empty($userFields["UF_BLOG_POST_FILE"])
						&& !empty($userFields["UF_BLOG_POST_FILE"]["VALUE"])
					)
					{
						$result["ATTACHMENTS"] = self::getAttachmentsData($userFields["UF_BLOG_POST_FILE"]["VALUE"], $result["BLOG_GROUP_SITE_ID"]);
					}
				}

				$result["DETAIL_TEXT"] = self::convertDiskFileBBCode(
					$result["DETAIL_TEXT"],
					'BLOG_POST',
					$postId,
					$result["AUTHOR_ID"],
					$result["ATTACHMENTS"]
				);

				$result["DETAIL_TEXT_FORMATTED"] = preg_replace(
					array(
						'|\[DISK\sFILE\sID=[n]*\d+\]|',
						'|\[DOCUMENT\sID=[n]*\d+\]|'
					),
					'',
					$result["DETAIL_TEXT"]
				);

				$result["DETAIL_TEXT_FORMATTED"] = preg_replace(
					"/\[USER\s*=\s*([^\]]*)\](.+?)\[\/USER\]/is".BX_UTF_PCRE_MODIFIER,
					"\\2",
					$result["DETAIL_TEXT_FORMATTED"]
				);

				$p = new \blogTextParser();
				$p->arUserfields = array();

				$images = array();
				$allow = array("IMAGE" => "Y");
				$parserParameters = array();

				$result["DETAIL_TEXT_FORMATTED"] = $p->convert($result["DETAIL_TEXT_FORMATTED"], false, $images, $allow, $parserParameters);

				$title = (
					$result["MICRO"] == "Y"
						? \blogTextParser::killAllTags($result["DETAIL_TEXT_FORMATTED"])
						: htmlspecialcharsEx($result["TITLE"])
				);

				$title = preg_replace(
					'|\[MAIL\sDISK\sFILE\sID=[n]*\d+\]|',
					'',
					$title
				);

				$title = str_replace(Array("\r\n", "\n", "\r"), " ", $title);
				$result["TITLE_FORMATTED"] = \TruncateText($title, 100);
				$result["DATE_PUBLISH_FORMATTED"] = self::formatDateTimeToGMT($result['DATE_PUBLISH'], $result['AUTHOR_ID']);
			}

			self::$postsCache[$postId] = $result;
		}

		return $result;
	}

	/**
	 * Returns data of blog post destinations
	 *
	 * @param int $postId Blog Post Id.
	 * @return array
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	*/
	public static function getBlogPostDestinations($postId)
	{
		if (isset(self::$destinationsCache[$postId]))
		{
			$result = self::$destinationsCache[$postId];
		}
		else
		{
			$result = array();

			if (!Loader::includeModule('blog'))
			{
				throw new Main\SystemException("Could not load 'blog' module.");
			}

			$sonetPermission = \CBlogPost::getSocnetPermsName($postId);
			if (!empty($sonetPermission))
			{
				foreach($sonetPermission as $typeCode => $type)
				{
					foreach($sonetPermission[$typeCode] as $userId => $destination)
					{
						$name = false;

						if ($typeCode == "SG")
						{
							if ($sonetGroup = \CSocNetGroup::getByID($destination["ENTITY_ID"]))
							{
								$name = $sonetGroup["NAME"];
							}
						}
						elseif ($typeCode == "U")
						{
							if(in_array("US".$destination["ENTITY_ID"], $destination["ENTITY"]))
							{
								$name = "#ALL#";
								Loader::includeModule('intranet');
							}
							else
							{
								$name = \CUser::formatName(
									\CSite::getNameFormat(false),
									array(
										"NAME" => $destination["~U_NAME"],
										"LAST_NAME" => $destination["~U_LAST_NAME"],
										"SECOND_NAME" => $destination["~U_SECOND_NAME"],
										"LOGIN" => $destination["~U_LOGIN"]
									),
									true
								);
							}
						}
						elseif ($typeCode == "DR")
						{
							$name = $destination["EL_NAME"];
						}

						if ($name)
						{
							$result[] = $name;
						}
					}
				}
			}

			self::$destinationsCache[$postId] = $result;
		}

		return $result;
	}

	/**
	 * Returns data of a blog post/comment author
	 *
	 * @param int $authorId User Id.
	 * @param array $params Format parameters (avatar size etc).
	 * @return array
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	*/
	public static function getBlogAuthorData($authorId, $params)
	{
		if (isset(self::$authorsCache[$authorId]))
		{
			$result = self::$authorsCache[$authorId];
		}
		else
		{
			if (!Loader::includeModule('blog'))
			{
				throw new Main\SystemException("Could not load 'blog' module.");
			}

			$result = \CBlogUser::getUserInfo(
				intval($authorId),
				'',
				array(
					"AVATAR_SIZE" => (
						isset($params["AVATAR_SIZE"])
						&& intval($params["AVATAR_SIZE"]) > 0
							? intval($params["AVATAR_SIZE"])
							: false
					),
					"AVATAR_SIZE_COMMENT" => (
						isset($params["AVATAR_SIZE_COMMENT"])
						&& intval($params["AVATAR_SIZE_COMMENT"]) > 0
							? intval($params["AVATAR_SIZE_COMMENT"])
							: false
					),
					"RESIZE_IMMEDIATE" => "Y"
				)
			);

			$result["NAME_FORMATTED"] = \CUser::formatName(
				\CSite::getNameFormat(false),
				array(
					"NAME" => $result["~NAME"],
					"LAST_NAME" => $result["~LAST_NAME"],
					"SECOND_NAME" => $result["~SECOND_NAME"],
					"LOGIN" => $result["~LOGIN"]
				),
				true
			);

			self::$authorsCache[$authorId] = $result;
		}

		return $result;
	}

	/**
	 * Returns full list of blog post comments
	 *
	 * @param int $postId Blog Post Id.
	 * @param array $params Additional paramaters.
	 * @param string $languageId Language Id (2-char).
	 * @param array &$authorIdList List of User Ids.
	 * @return array
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	*/
	public static function getBlogCommentListData($postId, $params, $languageId, &$authorIdList = array())
	{
		if (isset(self::$commentListsCache[$postId]))
		{
			$result = self::$commentListsCache[$postId];
		}
		else
		{
			$result = array();

			if (!Loader::includeModule('blog'))
			{
				throw new Main\SystemException("Could not load 'blog' module.");
			}

			$p = new \blogTextParser();

			$selectedFields = Array("ID", "BLOG_GROUP_ID", "BLOG_GROUP_SITE_ID", "BLOG_ID", "POST_ID", "AUTHOR_ID", "AUTHOR_NAME", "AUTHOR_EMAIL", "POST_TEXT", "DATE_CREATE", "PUBLISH_STATUS", "HAS_PROPS", "SHARE_DEST");

			$connection = \Bitrix\Main\Application::getConnection();
			if ($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection)
			{
				$selectedFields[] = "DATE_CREATE_TS";
			}

			$res = \CBlogComment::getList(
				array("ID" => "DESC"),
				array(
					"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
					"POST_ID" => $postId,
//					"SHARE_DEST" => false
				),
				false,
				array(
					"nTopCount" => $params["COMMENTS_COUNT"]
				),
				$selectedFields
			);

			while ($comment = $res->fetch())
			{
				self::processCommentData($comment, $languageId, $p);

				$result[] = $comment;

				if (!in_array($comment["AUTHOR_ID"], $authorIdList))
				{
					$authorIdList[] = $comment["AUTHOR_ID"];
				}
			}

			if (!empty($result))
			{
				$result = array_reverse($result);
			}

			self::$commentListsCache[$postId] = $result;
		}

		return $result;
	}

	/**
	 * Returns a number of blog post comments
	 *
	 * @param int $postId Blog Post Id.
	 * @return bool|int
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	*/
	public static function getBlogCommentListCount($postId)
	{
		if (isset(self::$commentCountCache[$postId]))
		{
			$result = self::$commentCountCache[$postId];
		}
		else
		{
			if (!Loader::includeModule('blog'))
			{
				throw new Main\SystemException("Could not load 'blog' module.");
			}

			$selectedFields = Array("ID");

			$result = \CBlogComment::getList(
				array("ID" => "DESC"),
				array(
					"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
					"POST_ID" => $postId,
//					"SHARE_DEST" => false
				),
				array(), // count only
				false,
				$selectedFields
			);

			self::$commentCountCache[$postId] = $result;
		}

		return $result;
	}


	/**
	 * Returns data of a blog comment
	 *
	 * @param int $commentId Comment Id.
	 * @param string $languageId Language id (2-chars).
	 * @return array|bool|false|mixed|null
	*/
	public static function getBlogCommentData($commentId, $languageId)
	{
		$result = array();

		if (isset(self::$commentsCache[$commentId]))
		{
			$result = self::$commentsCache[$commentId];
		}
		else
		{
			$selectedFields = Array("ID", "BLOG_GROUP_ID", "BLOG_GROUP_SITE_ID", "BLOG_ID", "POST_ID", "AUTHOR_ID", "AUTHOR_NAME", "AUTHOR_EMAIL", "POST_TEXT", "DATE_CREATE", "PUBLISH_STATUS", "HAS_PROPS", "SHARE_DEST");

			$connection = \Bitrix\Main\Application::getConnection();
			if ($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection)
			{
				$selectedFields[] = "DATE_CREATE_TS";
			}

			$res = \CBlogComment::getList(
				array(),
				array(
					"ID" => $commentId
				),
				false,
				false,
				$selectedFields
			);

			if ($comment = $res->fetch())
			{
				$p = new \blogTextParser();

				self::processCommentData($comment, $languageId, $p);

				$result = $comment;
			}

			self::$commentsCache[$commentId] = $result;
		}

		return $result;
	}

	/**
	 * Processes comment data, rendering formatted text and date
	 *
	 * @param array $comment Comment fields set.
	 * @param string $languageId Language Id (2-chars).
	 * @param \blogTextParser $p TextParser object.
	*/
	private static function processCommentData(&$comment, $languageId, $p)
	{
		global $USER_FIELD_MANAGER;

		$comment["ATTACHMENTS"] = $comment["PROPS"] = array();

		if($comment["HAS_PROPS"] != "N")
		{
			$userFields = $comment["PROPS"] = $USER_FIELD_MANAGER->getUserFields("BLOG_COMMENT", $comment["ID"], $languageId);
			$commentUf = array("UF_BLOG_COMMENT_FILE");
			foreach ($userFields as $fieldName => $userField)
			{
				if (!in_array($fieldName, $commentUf))
				{
					unset($userFields[$fieldName]);
				}
			}

			if (
				!empty($userFields["UF_BLOG_COMMENT_FILE"])
				&& !empty($userFields["UF_BLOG_COMMENT_FILE"]["VALUE"])
			)
			{
				$comment["ATTACHMENTS"] = self::getAttachmentsData($userFields["UF_BLOG_COMMENT_FILE"]["VALUE"], $comment["BLOG_GROUP_SITE_ID"]);
			}
		}

		$comment["POST_TEXT"] = self::convertDiskFileBBCode(
			$comment["POST_TEXT"],
			'BLOG_COMMENT',
			$comment["ID"],
			$comment["AUTHOR_ID"],
			$comment["ATTACHMENTS"]
		);

		$comment["POST_TEXT_FORMATTED"] = preg_replace(
			array(
				'|\[DISK\sFILE\sID=[n]*\d+\]|',
				'|\[DOCUMENT\sID=[n]*\d+\]|'
			),
			'',
			$comment["POST_TEXT"]
		);

		$comment["POST_TEXT_FORMATTED"] = preg_replace(
			"/\[USER\s*=\s*([^\]]*)\](.+?)\[\/USER\]/is".BX_UTF_PCRE_MODIFIER,
			"\\2",
			$comment["POST_TEXT_FORMATTED"]
		);

		if ($p)
		{
			$p->arUserfields = array();
		}
		$images = array();
		$allow = array("IMAGE" => "Y");
		$parserParameters = array();

		$comment["POST_TEXT_FORMATTED"] = $p->convert($comment["POST_TEXT_FORMATTED"], false, $images, $allow, $parserParameters);
		$comment["DATE_CREATE_FORMATTED"] = self::formatDateTimeToGMT($comment['DATE_CREATE'], $comment['AUTHOR_ID']);
	}

	/**
	 * Returns mail-hash url
	 *
	 * @param string $url Entity Link.
	 * @param int $userId User Id.
	 * @param string $entityType Entity Type.
	 * @param int $entityId Entity Id.
	 * @param string $siteId Site id (2-char).
	 * @return bool|string
	 * @throws Main\LoaderException
	*/
	public static function getReplyToUrl($url, $userId, $entityType, $entityId, $siteId, $backUrl = null)
	{
		$result = false;

		if (
			strlen($url) > 0
			&& intval($userId) > 0
			&& strlen($entityType) > 0
			&& intval($entityId) > 0
			&& strlen($siteId) > 0
			&& Loader::includeModule('mail')
		)
		{
			$urlRes = \Bitrix\Mail\User::getReplyTo(
				$siteId,
				intval($userId),
				$entityType,
				$entityId,
				$url,
				$backUrl
			);
			if (is_array($urlRes))
			{
				list($replyTo, $backUrl) = $urlRes;

				if ($backUrl)
				{
					$result = $backUrl;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns data of attached files
	 *
	 * @param array $valueList Attachments List.
	 * @param string|bool|false $siteId Site Id (2-chars).
	 * @return array
	 * @throws Main\LoaderException
     */
	public static function getAttachmentsData($valueList, $siteId = false)
	{
		$result = array();

		if (!Loader::includeModule('disk'))
		{
			return $result;
		}

		if (
			!$siteId
			|| strlen($siteId) <= 0
		)
		{
			$siteId = SITE_ID;
		}

		$driver = \Bitrix\Disk\Driver::getInstance();
		$urlManager = $driver->getUrlManager();

		foreach ($valueList as $key => $value)
		{
			$attachedObject = \Bitrix\Disk\AttachedObject::loadById($value, array('OBJECT'));
			if(
				!$attachedObject
				|| !$attachedObject->getFile()
			)
			{
				continue;
			}

			$attachedObjectUrl = $urlManager->getUrlUfController('show', array('attachedId' => $value));

			$result[$value] = array(
				"ID" => $value,
				"OBJECT_ID" => $attachedObject->getFile()->getId(),
				"NAME" => $attachedObject->getFile()->getName(),
				"SIZE" => \CFile::formatSize($attachedObject->getFile()->getSize()),
				"URL" => $attachedObjectUrl,
				"IS_IMAGE" => \Bitrix\Disk\TypeFile::isImage($attachedObject->getFile())
			);
		}

		return $result;
	}

	/**
	 * Processes disk objects list and generates external links (for inline images) if needed
	 *
	 * @param array $valueList
	 * @param string $entityType Entity Type.
	 * @param int $entityId Entity Id.
	 * @param int $authorId User Id.
	 * @param array $attachmentList Attachments List.
	 * @return array
	 * @throws Main\LoaderException
	*/
	public static function getAttachmentUrlList($valueList = array(), $entityType = '', $entityId = 0, $authorId = 0, $attachmentList = array())
	{
		$result = array();

		if (
			empty($valueList)
			|| empty($attachmentList)
			|| intval($authorId) <= 0
			|| intval($entityId) <= 0
			|| !Loader::includeModule('disk')
		)
		{
			return $result;
		}

		$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType($entityType);

		foreach($valueList as $value)
		{
			$attachedFileId = false;
			$attachedObject = false;

			list($type, $realValue) = \Bitrix\Disk\Uf\FileUserType::detectType($value);
			if ($type == \Bitrix\Disk\Uf\FileUserType::TYPE_NEW_OBJECT)
			{
				$attachedObject = \Bitrix\Disk\AttachedObject::load(array(
					'=ENTITY_TYPE' => $connectorClass,
					'ENTITY_ID' => $entityId,
					'=MODULE_ID' => $moduleId,
					'OBJECT_ID'=> $realValue
				), array('OBJECT'));

				if($attachedObject)
				{
					$attachedFileId = $attachedObject->getId();
				}
			}
			else
			{
				$attachedFileId = $realValue;
			}

			if (
				intval($attachedFileId) > 0
				&& !empty($attachmentList[$attachedFileId])
			)
			{
				if (!$attachmentList[$attachedFileId]["IS_IMAGE"])
				{
					$result[$value] = array(
						'TYPE' => 'file',
						'URL' => $attachmentList[$attachedFileId]["URL"]
					);
				}
				else
				{
					if (!$attachedObject)
					{
						$attachedObject = \Bitrix\Disk\AttachedObject::loadById($attachedFileId, array('OBJECT'));
					}

					if ($attachedObject)
					{
						$file = $attachedObject->getFile();

						$extLinks = $file->getExternalLinks(array(
							'filter' => array(
								'OBJECT_ID' => $file->getId(),
								'CREATED_BY' => $authorId,
								'TYPE' => \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_MANUAL,
								'IS_EXPIRED' => false,
							),
							'limit' => 1,
						));

						if (empty($extLinks))
						{
							$externalLink = $file->addExternalLink(array(
								'CREATED_BY' => $authorId,
								'TYPE' => \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_MANUAL,
							));
						}
						else
						{
							/** @var \Bitrix\Disk\ExternalLink $externalLink */
							$externalLink = reset($extLinks);
						}

						if ($externalLink)
						{
							$originalFile = $file->getFile();

							$result[$value] = array(
								'TYPE' => 'image',
								'URL' => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlExternalLink(
									array(
										'hash' => $externalLink->getHash(),
										'action' => 'showFile'
									),
									true
								),
								'WIDTH' => intval($originalFile["WIDTH"]),
								'HEIGHT' => intval($originalFile["HEIGHT"])
							);
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Converts formatted text replacing pseudo-BB code MAIL DISK, using calculated URLs
	 *
	 * @param string $text Text to convert.
	 * @param array $attachmentList Attachments List.
	 * @return mixed|string
	*/
	public static function convertMailDiskFileBBCode($text = '', $attachmentList = array())
	{
		if (preg_match_all('|\[MAIL\sDISK\sFILE\sID=([n]*\d+)\]|', $text, $matches))
		{
			foreach($matches[1] as $inlineFileId)
			{
				$attachmentId = false;
				if (strpos($inlineFileId, 'n') === 0)
				{
					$found = false;
					foreach($attachmentList as $attachmentId => $attachment)
					{
						if (
							isset($attachment["OBJECT_ID"])
							&& intval($attachment["OBJECT_ID"]) == intval(substr($inlineFileId, 1))
						)
						{
							$found = true;
							break;
						}
					}
					if (!$found)
					{
						$attachmentId = false;
					}
				}
				else
				{
					$attachmentId = $inlineFileId;
				}

				if (intval($attachmentId) > 0)
				{
					$text = preg_replace(
						'|\[MAIL\sDISK\sFILE\sID='.$inlineFileId.'\]|',
						'[URL='.$attachmentList[$attachmentId]["URL"].']['.$attachmentList[$attachmentId]["NAME"].'][/URL]',
						$text
					);
				}
			}

			$p = new \blogTextParser();
			$p->arUserfields = array();

			$text = $p->convert($text, false, array(), array("HTML" => "Y", "ANCHOR" => "Y"), array());
		}

		return $text;
	}

	/**
	 * Converts DISK FILE BB-code to the pseudo-BB code MAIL DISK FILE or IMG BB-code
	 *
	 * @param string $text Text to convert.
	 * @param string $entityType Entity Type.
	 * @param int $entityId Entity Type.
	 * @param int $authorId User id.
	 * @param array $attachmentList Attachments List.
	 * @return mixed
	*/
	public static function convertDiskFileBBCode($text, $entityType, $entityId, $authorId, $attachmentList = array())
	{
		if (
			strlen(trim($text)) <= 0
			|| empty($attachmentList)
			|| intval($authorId) <= 0
			|| strlen($entityType) <= 0
			|| intval($entityId) <= 0
		)
		{
			return $text;
		}

		if (preg_match_all('|\[DISK\sFILE\sID=([n]*\d+)\]|', $text, $matches))
		{
			$attachmentUrlList = self::getAttachmentUrlList(
				$matches[1],
				$entityType,
				$entityId,
				$authorId,
				$attachmentList
			);

			foreach($matches[1] as $inlineFileId)
			{
				if (!empty($attachmentUrlList[$inlineFileId]))
				{
					$needCreatePicture = false;
					$sizeSource = $sizeDestination = array();
					\CFile::scaleImage(
						$attachmentUrlList[$inlineFileId]['WIDTH'], $attachmentUrlList[$inlineFileId]['HEIGHT'],
						array('width' => 400, 'height' => 1000), BX_RESIZE_IMAGE_PROPORTIONAL,
						$needCreatePicture, $sizeSource, $sizeDestination
					);

					$replacement = (
						$attachmentUrlList[$inlineFileId]["TYPE"] == 'image'
							? '[IMG WIDTH='.intval($sizeDestination['width']).' HEIGHT='.intval($sizeDestination['height']).']'.\htmlspecialcharsBack($attachmentUrlList[$inlineFileId]["URL"]).'[/IMG]'
							: '[MAIL DISK FILE ID='.$inlineFileId.']'
					);
					$text = preg_replace(
						'|\[DISK\sFILE\sID='.$inlineFileId.'\]|',
						$replacement,
						$text
					);
				}
			}
		}

		return $text;
	}

	/**
	 * Formsts date time to the value of author + GMT offset
	 *
	 * @param string $dateTimeSource Date/Time in site format.
	 * @param int $authorId User Id.
	 * @return string
	*/
	public static function formatDateTimeToGMT($dateTimeSource, $authorId)
	{
		$result = '';

		if (!empty($dateTimeSource))
		{
			$serverTs = \MakeTimeStamp($dateTimeSource) - \CTimeZone::getOffset();
			$serverGMTOffset = date('Z');

			$authorOffset = \CTimeZone::getOffset($authorId);
			$authorGMTOffset = $serverGMTOffset + $authorOffset;
			$authorGMTOffsetFormatted = 'GMT';
			if ($authorGMTOffset != 0)
			{
				$authorGMTOffsetFormatted .= ($authorGMTOffset >= 0 ? '+' : '-').sprintf('%02d', floor($authorGMTOffset / 3600)).':'.sprintf('%02u', ($authorGMTOffset % 3600) / 60);
			}

			$result = \FormatDate(
				preg_replace('/[\/.,\s:][s]/', '', \Bitrix\Main\Type\Date::convertFormatToPhp(FORMAT_DATETIME)),
				($serverTs + $authorOffset)
			).' ('.$authorGMTOffsetFormatted.')';
		}

		return $result;
	}

	/**
	 * Creates a user blog (when it is the first post of the user)
	 *
	 * @param array $params Parameters.
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	*/
	public static function createUserBlog($params)
	{
		$result = false;

		if (!Loader::includeModule('blog'))
		{
			throw new Main\SystemException("Could not load 'blog' module.");
		}

		if (
			!isset($params["BLOG_GROUP_ID"])
			|| intval($params["BLOG_GROUP_ID"]) <= 0
			|| !isset($params["USER_ID"])
			|| intval($params["USER_ID"]) <= 0
			|| !isset($params["SITE_ID"])
			|| strlen($params["SITE_ID"]) <= 0
		)
		{
			return false;
		}

		if (
			!isset($params["PATH_TO_BLOG"])
			|| strlen($params["PATH_TO_BLOG"]) <= 0
		)
		{
			$params["PATH_TO_BLOG"] = "";
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$fields = array(
			"=DATE_UPDATE" => $helper->getCurrentDateTimeFunction(),
			"=DATE_CREATE" => $helper->getCurrentDateTimeFunction(),
			"GROUP_ID" => intval($params["BLOG_GROUP_ID"]),
			"ACTIVE" => "Y",
			"OWNER_ID" => intval($params["USER_ID"]),
			"ENABLE_COMMENTS" => "Y",
			"ENABLE_IMG_VERIF" => "Y",
			"EMAIL_NOTIFY" => "Y",
			"ENABLE_RSS" => "Y",
			"ALLOW_HTML" => "N",
			"ENABLE_TRACKBACK" => "N",
			"SEARCH_INDEX" => "Y",
			"USE_SOCNET" => "Y",
			"PERMS_POST" => Array(
				1 => "I",
				2 => "I"
			),
			"PERMS_COMMENT" => Array(
				1 => "P",
				2 => "P"
			)
		);

		$res = \Bitrix\Main\UserTable::getList(array(
			'order' => array(),
			'filter' => array(
				"ID" => $params["USER_ID"]
			),
			'select' => array("NAME", "LAST_NAME", "LOGIN")
		));

		if ($user = $res->fetch())
		{
			$fields["NAME"] = Loc::getMessage("BLG_NAME")." ".(
				strlen($user["NAME"]."".$user["LAST_NAME"]) <= 0
					? $user["LOGIN"]
					: $user["NAME"]." ".$user["LAST_NAME"]
			);

			$fields["URL"] = str_replace(" ", "_", $user["LOGIN"])."-blog-".$params["SITE_ID"];
			$urlCheck = preg_replace("/[^a-zA-Z0-9_-]/is", "", $fields["URL"]);
			if ($urlCheck != $fields["URL"])
			{
				$fields["URL"] = "u".$params["USER_ID"]."-blog-".$params["SITE_ID"];
			}

			if(\CBlog::getByUrl($fields["URL"]))
			{
				$uind = 0;
				do
				{
					$uind++;
					$fields["URL"] = $fields["URL"].$uind;
				}
				while (\CBlog::getByUrl($fields["URL"]));
			}

			$fields["PATH"] = \CComponentEngine::makePathFromTemplate(
				$params["PATH_TO_BLOG"],
				array(
					"blog" => $fields["URL"],
					"user_id" => $fields["OWNER_ID"]
				)
			);

			if ($blogID = \CBlog::add($fields))
			{
				BXClearCache(true, "/blog/form/blog/");

				$rightsFound = false;

				$featureOperationPerms = \CSocNetFeaturesPerms::getOperationPerm(
					SONET_ENTITY_USER,
					$fields["OWNER_ID"],
					"blog",
					"view_post"
				);

				if ($featureOperationPerms == SONET_RELATIONS_TYPE_ALL)
				{
					$rightsFound = true;
				}

				if ($rightsFound)
				{
					\CBlog::addSocnetRead($blogID);
				}

				$result = \CBlog::getByID($blogID);
			}
		}

		return $result;
	}

	/**
	 * get urlPreview property value from text with links
	 *
	 * @param $text string
	 * @param bool|true $html
	 * @return bool|string
	 * @throws Main\ArgumentTypeException
	*/
	public static function getUrlPreviewValue($text, $html = true)
	{
		static $parser = false;
		$value = false;

		if (empty($text))
		{
			return $value;
		}

		if (!$parser)
		{
			$parser = new \CTextParser();
		}

		if ($html)
		{
			$text = $parser->convertHtmlToBB($text);
		}

		preg_match_all("/\[url\s*=\s*([^\]]*)\](.+?)\[\/url\]/ies".BX_UTF_PCRE_MODIFIER, $text, $res);

		if (
			!empty($res)
			&& !empty($res[1])
		)
		{
			$url = (
				!\Bitrix\Main\Application::isUtfMode()
					? \Bitrix\Main\Text\Encoding::convertEncoding($res[1][0], 'UTF-8', \Bitrix\Main\Context::getCurrent()->getCulture()->getCharset())
					: $res[1][0]
			);

			$metaData = \Bitrix\Main\UrlPreview\UrlPreview::getMetadataAndHtmlByUrl($url);
			if (
				!empty($metaData)
				&& !empty($metaData["ID"])
				&& intval($metaData["ID"]) > 0
			)
			{
				$signer = new \Bitrix\Main\Security\Sign\Signer();
				$value = $signer->sign($metaData["ID"].'', \Bitrix\Main\UrlPreview\UrlPreview::SIGN_SALT);
			}
		}

		return $value;
	}

	/**
	 * Returns rendered url preview block
	 *
	 * @param array $uf
	 * @param array $params
	 * @return string|boolean
	*/
	public static function getUrlPreviewContent($uf, $params = array())
	{
		global $APPLICATION;
		$res = false;

		if ($uf["USER_TYPE"]["USER_TYPE_ID"] != 'url_preview')
		{
			return $res;
		}

		ob_start();

		$APPLICATION->includeComponent(
			"bitrix:system.field.view",
			$uf["USER_TYPE"]["USER_TYPE_ID"],
			array(
				"LAZYLOAD" => (isset($params["LAZYLOAD"]) && $params["LAZYLOAD"] == "Y" ? "Y" : "N"),
				"MOBILE" => (isset($params["MOBILE"]) && $params["MOBILE"] == "Y" ? "Y" : "N"),
				"arUserField" => $uf,
				"arAddField" => array(
					"NAME_TEMPLATE" => (isset($params["NAME_TEMPLATE"]) ? $params["NAME_TEMPLATE"] : false),
					"PATH_TO_USER" => (isset($params["PATH_TO_USER"]) ? $params["PATH_TO_USER"] : '')
				)
			), null, array("HIDE_ICONS"=>"Y")
		);

		$res = ob_get_clean();

		return $res;
	}

	public static function getExtranetUserIdList()
	{
		static $result = false;
		global $CACHE_MANAGER;

		if ($result === false)
		{
			$result = array();

			$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
			$cacheId = 'sonet_ex_userid';
			$cache = new \CPHPCache;
			$cacheDir = '/bitrix/sonet/user_ex';

			if($cache->initCache($ttl, $cacheId, $cacheDir))
			{
				$tmpVal = $cache->getVars();
				$result = $tmpVal['EX_USER_ID'];
				unset($tmpVal);
			}
			elseif (ModuleManager::isModuleInstalled('extranet'))
			{
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->startTagCache($cacheDir);
				}

				$filter = array(
					'UF_DEPARTMENT' => false
				);

				$externalAuthIdList = self::checkPredefinedAuthIdList(array('replica', 'bot', 'email', 'imconnector'));
				if (!empty($externalAuthIdList))
				{
					$filter['!=EXTERNAL_AUTH_ID'] = $externalAuthIdList;
				}

				$res = \Bitrix\Main\UserTable::getList(array(
					'order' => array(),
					'filter' => $filter,
					'select' => array('ID')
				));

				while($user = $res->fetch())
				{
					$result[] = $user["ID"];
				}

				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->registerTag('sonet_user2group');
					$CACHE_MANAGER->endTagCache();
				}

				if($cache->startDataCache())
				{
					$cache->endDataCache(array(
						'EX_USER_ID' => $result
					));
				}
			}
		}

		return $result;
	}

	public static function getEmailUserIdList()
	{
		global $CACHE_MANAGER;

		$result = array();

		$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
		$cacheId = 'sonet_email_userid';
		$cache = new \CPHPCache;
		$cacheDir = '/bitrix/sonet/user_email';

		if($cache->initCache($ttl, $cacheId, $cacheDir))
		{
			$tmpVal = $cache->getVars();
			$result = $tmpVal['EMAIL_USER_ID'];
			unset($tmpVal);
		}
		elseif (ModuleManager::isModuleInstalled('mail'))
		{
			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->startTagCache($cacheDir);
			}

			$res = \Bitrix\Main\UserTable::getList(array(
				'order' => array(),
				'filter' => array(
					'=EXTERNAL_AUTH_ID' => 'email'
				),
				'select' => array('ID')
			));

			while($user = $res->fetch())
			{
				$result[] = $user["ID"];
			}

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->registerTag('USER_CARD');
				$CACHE_MANAGER->endTagCache();
			}

			if($cache->startDataCache())
			{
				$cache->endDataCache(array(
					'EMAIL_USER_ID' => $result
				));
			}
		}

		return $result;
	}

	public static function getExtranetSonetGroupIdList()
	{
		$result = array();

		$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
		$cacheId = 'sonet_ex_groupid';
		$cache = new \CPHPCache;
		$cacheDir = '/bitrix/sonet/group_ex';

		if($cache->initCache($ttl, $cacheId, $cacheDir))
		{
			$tmpVal = $cache->getVars();
			$result = $tmpVal['EX_GROUP_ID'];
			unset($tmpVal);
		}
		elseif (Loader::includeModule('extranet'))
		{
			global $CACHE_MANAGER;
			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->startTagCache($cacheDir);
			}

			$res = WorkgroupTable::getList(array(
				'order' => array(),
				'filter' => array(
					"=WorkgroupSite:GROUP.SITE_ID" => \CExtranet::getExtranetSiteID()
				),
				'select' => array('ID')
			));

			while($sonetGroup = $res->fetch())
			{
				$result[] = $sonetGroup["ID"];
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->registerTag('sonet_group_'.$sonetGroup["ID"]);
				}
			}

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->registerTag('sonet_group');
				$CACHE_MANAGER->endTagCache();
			}

			if($cache->startDataCache())
			{
				$cache->endDataCache(array(
					'EX_GROUP_ID' => $result
				));
			}
		}

		return $result;
	}

	public static function hasCommentSource($params)
	{
		$res = false;

		if (empty($params["LOG_EVENT_ID"]))
		{
			return $res;
		}

		$commentEvent = \CSocNetLogTools::findLogCommentEventByLogEventID($params["LOG_EVENT_ID"]);

		if (
			isset($commentEvent["DELETE_CALLBACK"])
			&& $commentEvent["DELETE_CALLBACK"] != "NO_SOURCE"
		)
		{
			if (
				$commentEvent["EVENT_ID"] == "crm_activity_add_comment"
				&& isset($params["LOG_ENTITY_ID"])
				&& intval($params["LOG_ENTITY_ID"]) > 0
				&& Loader::includeModule('crm')
			)
			{
				$result = \CCrmActivity::getList(
					array(),
					array(
						'ID' => intval($params["LOG_ENTITY_ID"]),
						'CHECK_PERMISSIONS' => 'N'
					)
				);

				if ($activity = $result->fetch())
				{
					$res = ($activity['TYPE_ID'] == \CCrmActivityType::Task);
				}
			}
			else
			{
				$res = true;
			}
		}

		return $res;
	}

	// only by current userid
	public static function processBlogPostShare($fields, $params)
	{
		$postId = intval($fields["POST_ID"]);
		$blogId = intval($fields["BLOG_ID"]);
		$siteId = $fields["SITE_ID"];
		$sonetRights = $fields["SONET_RIGHTS"];
		$newRights = $fields["NEW_RIGHTS"];
		$userId = $fields["USER_ID"];

		$commentId = false;
		$logId = false;

		if (Loader::includeModule('blog'))
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$helper = $connection->getSqlHelper();

			if(\CBlogPost::update($postId, array("SOCNET_RIGHTS" => $sonetRights, "HAS_SOCNET_ALL" => "N")))
			{
				BXClearCache(true, "/blog/socnet_post/".intval($postId / 100)."/".$postId."/");
				BXClearCache(true, "/blog/socnet_post/gen/".intval($postId / 100)."/".$postId."/");
				BXClearCache(True, "/".$siteId."/blog/popular_posts/");

				$logSiteListNew = array();
				$newRightsNameList = array();
				$user2NotifyList = array();
				$sonetPermissionList = \CBlogPost::getSocnetPermsName($postId);
				$extranet = Loader::includeModule("extranet");
				$currentExtranetSite = ($extranet && \CExtranet::isExtranetSite());
				$extranetSite = ($extranet ? \CExtranet::getExtranetSiteID() : false);
				$tzOffset = \CTimeZone::getOffset();

				$res = \CBlogPost::getList(
					array(),
					array("ID" => $postId),
					false,
					false,
					array("ID", "BLOG_ID", "PUBLISH_STATUS", "TITLE", "AUTHOR_ID", "ENABLE_COMMENTS", "NUM_COMMENTS", "VIEWS", "CODE", "MICRO", "DETAIL_TEXT", "DATE_PUBLISH", "CATEGORY_ID", "HAS_SOCNET_ALL", "HAS_TAGS", "HAS_IMAGES", "HAS_PROPS", "HAS_COMMENT_IMAGES")
				);
				$post = $res->fetch();
				if (!$post)
				{
					return false;
				}

				$intranetUserIdList = ($extranet ? \CExtranet::getIntranetUsers() : false);

				foreach($sonetPermissionList as $type => $v)
				{
					foreach($v as $vv)
					{
						$name = "";
						$link = "";
						$id = "";
						if (
							$type == "SG"
							&& in_array($type.$vv["ENTITY_ID"], $newRights)
						)
						{
							if($sonetGroup = \CSocNetGroup::getByID($vv["ENTITY_ID"]))
							{
								$name = $sonetGroup["NAME"];
								$link = \CComponentEngine::makePathFromTemplate(
									$params["PATH_TO_GROUP"],
									array(
										"group_id" => $vv["ENTITY_ID"]
									)
								);

								$groupSiteID = false;

								$res = \CSocNetGroup::getSite($vv["ENTITY_ID"]);
								while ($groupSiteList = $res->fetch())
								{
									$logSiteListNew[] = $groupSiteList["LID"];
									if (
										!$groupSiteID
										&& (
											!$extranet
											|| $groupSiteList["LID"] != $extranetSite
										)
									)
									{
										$groupSiteID = $groupSiteList["LID"];
									}
								}

								if ($groupSiteID)
								{
									$tmp = \CSocNetLogTools::processPath(array("GROUP_URL" => $link), $userId, $groupSiteID); // user_id is not important parameter
									$link = (strlen($tmp["URLS"]["GROUP_URL"]) > 0 ? $tmp["SERVER_NAME"].$tmp["URLS"]["GROUP_URL"] : $link);
								}
							}
						}
						elseif ($type == "U")
						{
							if (
								in_array("US".$vv["ENTITY_ID"], $vv["ENTITY"])
								&& in_array("UA", $newRights)
							)
							{
								$name = (
									ModuleManager::isModuleInstalled("intranet")
										? Loc::getMessage("BLG_SHARE_ALL")
										: Loc::getMessage("BLG_SHARE_ALL_BUS")
								);

								if (
									!$currentExtranetSite
									&& defined("BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL")
								)
								{
									$link = BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL;
								}
							}
							elseif(in_array($type.$vv["ENTITY_ID"], $newRights))
							{
								$tmpUser = array(
									"NAME" => $vv["~U_NAME"],
									"LAST_NAME" => $vv["~U_LAST_NAME"],
									"SECOND_NAME" => $vv["~U_SECOND_NAME"],
									"LOGIN" => $vv["~U_LOGIN"],
									"NAME_LIST_FORMATTED" => "",
								);
								$name = \CUser::formatName($params["NAME_TEMPLATE"], $tmpUser, ($params["SHOW_LOGIN"] != "N" ? true : false));
								$id = $vv["ENTITY_ID"];
								$link = \CComponentEngine::makePathFromTemplate(
									$params["PATH_TO_USER"],
									array(
										"user_id" => $vv["ENTITY_ID"]
									)
								);
								$user2NotifyList[] = $vv["ENTITY_ID"];

								if (
									$extranet
									&& is_array($intranetUserIdList)
									&& !in_array($vv["ENTITY_ID"], $intranetUserIdList)
								)
								{
									$logSiteListNew[] = $extranetSite;
								}
							}
						}
						elseif($type == "DR" && in_array($type.$vv["ENTITY_ID"], $newRights))
						{
							$name = $vv["EL_NAME"];
							$id = $vv["ENTITY_ID"];
							$link = \CComponentEngine::makePathFromTemplate(
								$params["PATH_TO_CONPANY_DEPARTMENT"],
								array(
									"ID" => $vv["ENTITY_ID"]
								)
							);
						}

						if(strlen($name) > 0)
						{
							if(strlen($link) > 0)
							{
								$newRightsNameList[] = (
									$type == "U"
									&& intval($id) > 0
										? "[user=".$id."]".htmlspecialcharsback($name)."[/user]"
										: "[url=".$link."]".htmlspecialcharsback($name)."[/url]"
								);
							}
							else
							{
								$newRightsNameList[] = htmlspecialcharsback($name);
							}
						}
					}
				}

				$userIP = \CBlogUser::getUserIP();
				$commentFields = Array(
					"POST_ID" => $postId,
					"BLOG_ID" => $blogId,
					"POST_TEXT" => (
						count($newRightsNameList) > 1
							? Loc::getMessage("BLG_SHARE")
							: Loc::getMessage("BLG_SHARE_1")
						).implode(", ", $newRightsNameList),
					"DATE_CREATE" => convertTimeStamp(time() + $tzOffset, "FULL"),
					"AUTHOR_IP" => $userIP[0],
					"AUTHOR_IP1" => $userIP[1],
					"PARENT_ID" => false,
					"AUTHOR_ID" => $userId,
					"SHARE_DEST" => implode(",", $newRights),
				);

				$userIdSent = array();

				if($commentId = \CBlogComment::add($commentFields))
				{
					BXClearCache(true, "/blog/comment/".intval($postId / 100)."/".$postId."/");

					if($post["AUTHOR_ID"] != $userId)
					{
						$fieldsIM = array(
							"TYPE" => "SHARE",
							"TITLE" => htmlspecialcharsback($post["TITLE"]),
							"URL" => \CComponentEngine::makePathFromTemplate(
								htmlspecialcharsBack($params["PATH_TO_POST"]),
								array(
									"post_id" => $postId,
									"user_id" => $post["AUTHOR_ID"]
								)
							),
							"ID" => $postId,
							"FROM_USER_ID" => $userId,
							"TO_USER_ID" => array($post["AUTHOR_ID"]),
						);
						\CBlogPost::notifyIm($fieldsIM);
						$userIdSent[] = array_merge($userIdSent, $fieldsIM["TO_USER_ID"]);
					}

					if(!empty($user2NotifyList))
					{
						$fieldsIM = array(
							"TYPE" => "SHARE2USERS",
							"TITLE" => htmlspecialcharsback($post["TITLE"]),
							"URL" => \CComponentEngine::makePathFromTemplate(
								htmlspecialcharsBack($params["PATH_TO_POST"]),
								array(
									"post_id" => $postId,
									"user_id" => $post["AUTHOR_ID"]
								)),
							"ID" => $postId,
							"FROM_USER_ID" => $userId,
							"TO_USER_ID" => $user2NotifyList,
						);
						\CBlogPost::notifyIm($fieldsIM);
						$userIdSent[] = array_merge($userIdSent, $fieldsIM["TO_USER_ID"]);

						\CBlogPost::notifyMail(array(
							"type" => "POST_SHARE",
							"siteId" => $siteId,
							"userId" => $user2NotifyList,
							"authorId" => $userId,
							"postId" => $post["ID"],
							"postUrl" => \CComponentEngine::makePathFromTemplate(
								'/pub/post.php?post_id=#post_id#',
								array(
									"post_id"=> $post["ID"]
								)
							)
						));
					}
				}

				/* update socnet log rights*/
				$res = \CSocNetLog::getList(
					array("ID" => "DESC"),
					array(
						"EVENT_ID" => array("blog_post", "blog_post_important"),
						"SOURCE_ID" => $postId
					),
					false,
					false,
					array("ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "EVENT_ID")
				);
				if ($logEntry = $res->fetch())
				{
					$logId = $logEntry["ID"];
					$logSiteList = array();
					$res = \CSocNetLog::getSite($logId);
					while ($logSite = $res->fetch())
					{
						$logSiteList[] = $logSite["LID"];
					}
					$logSiteListNew = array_merge($logSiteListNew, $logSiteList);

					$socnetPerms = \CBlogPost::getSocNetPermsCode($postId);
					if(!in_array("U".$post["AUTHOR_ID"], $socnetPerms))
					{
						$socnetPerms[] = "U".$post["AUTHOR_ID"];
					}
					$socnetPerms[] = "SA"; // socnet admin

					if (
						in_array("AU", $socnetPerms)
						|| in_array("G2", $socnetPerms)
					)
					{
						$socnetPermsAdd = array();

						foreach($socnetPerms as $perm)
						{
							if (preg_match('/^SG(\d+)$/', $perm, $matches))
							{
								if (
									!in_array("SG".$matches[1]."_".SONET_ROLES_USER, $socnetPerms)
									&& !in_array("SG".$matches[1]."_".SONET_ROLES_MODERATOR, $socnetPerms)
									&& !in_array("SG".$matches[1]."_".SONET_ROLES_OWNER, $socnetPerms)
								)
								{
									$socnetPermsAdd[] = "SG".$matches[1]."_".SONET_ROLES_USER;
								}
							}
						}
						if (count($socnetPermsAdd) > 0)
						{
							$socnetPerms = array_merge($socnetPerms, $socnetPermsAdd);
						}
					}

					\CSocNetLogRights::deleteByLogID($logId);
					\CSocNetLogRights::add($logId, $socnetPerms, true);

					if (count(array_diff($logSiteListNew, $logSiteList)) > 0)
					{
						\CSocNetLog::update($logId, array(
							"ENTITY_TYPE" => $logEntry["ENTITY_TYPE"], // to use any real field
							"SITE_ID" => $logSiteListNew,
							"=LOG_UPDATE" => $helper->getCurrentDateTimeFunction(),
						));
					}
					else
					{
						\CSocNetLog::update($logId, array(
							"=LOG_UPDATE" => $helper->getCurrentDateTimeFunction(),
						));
					}

					\CSocNetLogFollow::deleteByLogID($logId, "Y", true);

					/* subscribe share author */
					\CSocNetLogFollow::set(
						$userId,
						"L".$logId,
						"Y",
						convertTimeStamp(time() + $tzOffset, "FULL")
					);
				}

				/* update socnet groupd activity*/
				foreach($newRights as $v)
				{
					if(substr($v, 0, 2) == "SG")
					{
						$groupId = intval(substr($v, 2));
						if($groupId > 0)
						{
							\CSocNetGroup::setLastActivity($groupId);
						}
					}
				}

				\Bitrix\Blog\Broadcast::send(array(
					"EMAIL_FROM" => \COption::getOptionString("main","email_from", "nobody@nobody.com"),
					"SOCNET_RIGHTS" => $newRights,
					"ENTITY_TYPE" => "POST",
					"ENTITY_ID" => $post["ID"],
					"AUTHOR_ID" => $post["AUTHOR_ID"],
					"URL" => \CComponentEngine::makePathFromTemplate(
						htmlspecialcharsBack($params["PATH_TO_POST"]),
						array(
							"post_id" => $post["ID"],
							"user_id" => $post["AUTHOR_ID"]
						)
					),
					"EXCLUDE_USERS" => $userIdSent
				));

				\Bitrix\Main\FinderDestTable::merge(array(
					"CONTEXT" => "blog_post",
					"CODE" => \Bitrix\Main\FinderDestTable::convertRights($newRights)
				));

				if (\Bitrix\Main\Loader::includeModule('crm'))
				{
					\CCrmLiveFeedComponent::processCrmBlogPostRights($logId, $logEntry, $post, 'share');
				}

				if (
					intval($commentId) > 0
					&& (
						!isset($params["LIVE"])
						|| $params["LIVE"] != "N"
					)
				)
				{
					\CBlogComment::addLiveComment($commentId, array(
						"PATH_TO_USER" => $params["PATH_TO_USER"],
						"LOG_ID" => ($logId ? intval($logId) : 0)
					));
				}
			}
		}

		return $commentId;
	}

	public static function processBlogCreateTask($params)
	{
		global $USER;

		$taskId = (isset($params['TASK_ID']) ? intval($params['TASK_ID']) : 0);
		$sourceEntityType = (isset($params['SOURCE_ENTITY_TYPE']) && in_array($params['SOURCE_ENTITY_TYPE'], array('BLOG_POST', 'BLOG_COMMENT')) ? $params['SOURCE_ENTITY_TYPE'] : false);
		$sourceEntityId = (isset($params['SOURCE_ENTITY_ID']) ? intval($params['SOURCE_ENTITY_ID']) : 0);
		$commentId = $postId = $blogId = $logId = 0;

		if (
			empty($sourceEntityType)
			|| $sourceEntityId <= 0
			|| $taskId <= 0
			|| !Loader::includeModule('tasks')
			|| !Loader::includeModule('blog')
		)
		{
			return false;
		}

		if ($task = \Bitrix\Tasks\Manager\Task::get($USER->getId(), $taskId))
		{
			$task = $task['DATA'];
		}

		if (!$task)
		{
			return false;
		}

		if ($sourceEntityType == 'BLOG_COMMENT')
		{
			$commentId = $sourceEntityId;
			if ($comment = \CBlogComment::getByID($sourceEntityId))
			{
				$postId = $comment['POST_ID'];
			}
		}
		else
		{
			$postId = $sourceEntityId;
		}

		if (
			$postId <= 0
			|| !($post = \CBlogPost::getByID($postId))
			|| !\Bitrix\Socialnetwork\Livefeed\BlogPost::canRead(array(
				'POST' => $post
			))
		)
		{
			return false;
		}

		$blogId = intval($post['BLOG_ID']);

		if ($blogId <= 0)
		{
			return false;
		}

		$commentPath = false;
		$userPage = Option::get('socialnetwork', 'user_page', SITE_DIR.'company/personal/');
		$userPath = $userPage.'user/#user_id#/';

		if ($sourceEntityType == 'BLOG_COMMENT')
		{
			$commentPath = $userPage.'user/'.$post['AUTHOR_ID'].'/blog/view/'.$post['ID'].'/?commentId='.$commentId.'#com'.$commentId;
		}

		$commentText = Loc::getMessage(($sourceEntityType == 'BLOG_COMMENT' ? 'BLG_TASK_CREATED_COMMENT' : 'BLG_TASK_CREATED_POST'), array(
			'#TASK_NAME#' => $task['TITLE'],
			'#LINK#' => ($commentPath ? '[URL='.$commentPath.']'.Loc::getMessage('BLG_TASK_CREATED_COMMENT_LINK').'[/URL]' : '')
		));

		$userIP = \CBlogUser::getUserIP();

		if(!($newCommentId = \CBlogComment::add(array(
			"POST_ID" => $postId,
			"BLOG_ID" => $blogId,
			"POST_TEXT" => $commentText,
			"DATE_CREATE" => convertTimeStamp(time() + \CTimeZone::getOffset(), "FULL"),
			"AUTHOR_IP" => $userIP[0],
			"AUTHOR_IP1" => $userIP[1],
			"PARENT_ID" => false,
			"AUTHOR_ID" => $task['CREATED_BY']
		))))
		{
			return false;
		}

		BXClearCache(true, "/blog/comment/".intval($postId / 100)."/".$postId."/");

		$res = \CSocNetLog::getList(
			array(),
			array(
				'EVENT_ID' => array('blog_post', 'blog_post_important'),
				'SOURCE_ID' => $postId
			),
			false,
			array('nTopCount' => 1),
			array('ID')
		);
		if ($log = $res->fetch())
		{
			$logId = intval($log['ID']);
		}

		if ($logId > 0)
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$helper = $connection->getSqlHelper();

			\CSocNetLog::update($logId, array(
				"=LOG_UPDATE" => $helper->getCurrentDateTimeFunction(),
			));

			if (
				isset($params["LIVE"])
				&& $params["LIVE"] == "Y"
			)
			{
				\CBlogComment::addLiveComment($newCommentId, array(
					"PATH_TO_USER" => $userPath,
					"LOG_ID" => $logId,
					'MODE' => 'PULL_MESSAGE'
				));
			}
		}

		return true;
	}

	public static function getUrlContext()
	{
		$result = array();

		if (
			isset($_GET["entityType"])
			&& strlen($_GET["entityType"]) > 0
		)
		{
			$result["ENTITY_TYPE"] = $_GET["entityType"];
		}

		if (
			isset($_GET["entityId"])
			&& intval($_GET["entityId"]) > 0
		)
		{
			$result["ENTITY_ID"] = intval($_GET["entityId"]);
		}

		return $result;
	}

	public static function addContextToUrl($url, $context)
	{
		$result = $url;

		if (
			!empty($context)
			&& !empty($context["ENTITY_TYPE"])
			&& !empty($context["ENTITY_ID"])
		)
		{
			$result = $url.(strpos($url, '?') === false ? '?' : '&').'entityType='.$context["ENTITY_TYPE"].'&entityId='.$context["ENTITY_ID"];
		}

		return $result;
	}

	public static function checkPredefinedAuthIdList($authIdList = array())
	{
		if (!is_array($authIdList))
		{
			$authIdList = array($authIdList);
		}

		foreach($authIdList as $key => $authId)
		{
			if (
				$authId == 'replica'
				&& !ModuleManager::isModuleInstalled("replica")
			)
			{
				unset($authIdList[$key]);
			}

			if (
				$authId == 'imconnector'
				&& !ModuleManager::isModuleInstalled("imconnector")
			)
			{
				unset($authIdList[$key]);
			}

			if (
				$authId == 'bot'
				&& !ModuleManager::isModuleInstalled("im")
			)
			{
				unset($authIdList[$key]);
			}

			if (
				$authId == 'email'
				&& !ModuleManager::isModuleInstalled("mail")
			)
			{
				unset($authIdList[$key]);
			}
		}

		return $authIdList;
	}

	public static function setComponentOption($list, $params = array())
	{
		if (!is_array($list))
		{
			return false;
		}

		$siteId = (!empty($params["SITE_ID"]) ? $params["SITE_ID"] : SITE_ID);
		$sefFolder = (!empty($params["SEF_FOLDER"]) ? $params["SEF_FOLDER"] : false);

		foreach($list as $key => $value)
		{
			if (
				empty($value["OPTION"])
				|| empty($value["OPTION"]["MODULE_ID"])
				|| empty($value["OPTION"]["NAME"])
				|| empty($value["VALUE"])
			)
			{
				return false;
			}

			$optionValue = Option::get($value["OPTION"]["MODULE_ID"], $value["OPTION"]["NAME"], false, $siteId);

			if (
				!$optionValue
				|| (
					!!$value["CHECK_SEF_FOLDER"]
					&& $sefFolder
					&& substr($optionValue, 0, strlen($sefFolder)) !== $sefFolder
				)
			)
			{
				Option::set($value["OPTION"]["MODULE_ID"], $value["OPTION"]["NAME"], $value["VALUE"], $siteId);
			}
		}

		return true;
	}

	public static function getSonetGroupAvailable()
	{
		global $USER;

		$currentUserId = $USER->getId();

		$currentCache = \Bitrix\Main\Data\Cache::createInstance();

		$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 3153600 : 3600*4;
		$cacheId = 'dest_group_'.SITE_ID.'_'.$USER->getID();
		$cacheDir = '/sonet/dest_sonet_groups/'.SITE_ID.'/'.$USER->getID();

		if($currentCache->startDataCache($cacheTtl, $cacheId, $cacheDir))
		{
			global $CACHE_MANAGER;

			$groupList = \CSocNetLogDestination::getSocnetGroup(array(
				'features' => array("blog", array("premoderate_post", "moderate_post", "write_post", "full_post"))
			));

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->StartTagCache($cacheDir);
				foreach($groupList as $group)
				{
					$CACHE_MANAGER->RegisterTag("sonet_features_G_".$group["entityId"]);
					$CACHE_MANAGER->RegisterTag("sonet_group_".$group["entityId"]);
				}
				$CACHE_MANAGER->RegisterTag("sonet_user2group_U".$currentUserId);
				$CACHE_MANAGER->EndTagCache();
			}
			$currentCache->EndDataCache($groupList);

		}
		else
		{
			$groupList = $currentCache->GetVars();
		}

		return $groupList;
	}

	public static function canAddComment($logEntry = array(), $commentEvent = array())
	{
		$canAddComments = false;

		global $USER;

		if (
			!is_array($logEntry)
			&& intval($logEntry) > 0
		)
		{
			$res = \CSocNetLog::getList(
				array(),
				array(
					"ID" => intval($logEntry)
				),
				false,
				false,
				array("ID", "ENTITY_TYPE", "ENTITY_ID", "EVENT_ID", "USER_ID")
			);

			if (!($logEntry = $res->fetch()))
			{
				return $canAddComments;
			}
		}

		if (
			!is_array($logEntry)
			|| empty($logEntry)
			|| empty($logEntry["EVENT_ID"])
		)
		{
			return $canAddComments;
		}

		if (
			!is_array($commentEvent)
			|| empty($commentEvent)
		)
		{
			$commentEvent = \CSocNetLogTools::findLogCommentEventByLogEventID($logEntry["EVENT_ID"]);
		}

		if (is_array($commentEvent))
		{
			$feature = \CSocNetLogTools::findFeatureByEventID($commentEvent["EVENT_ID"]);

			if (
				array_key_exists("OPERATION_ADD", $commentEvent)
				&& $commentEvent["OPERATION_ADD"] == "log_rights"
			)
			{
				$canAddComments = \CSocNetLogRights::checkForUser($logEntry["ID"], $USER->getID());
			}
			elseif (
				$feature
				&& array_key_exists("OPERATION_ADD", $commentEvent)
				&& strlen($commentEvent["OPERATION_ADD"]) > 0
			)
			{
				$canAddComments = \CSocNetFeaturesPerms::canPerformOperation(
					$USER->getID(),
					$logEntry["ENTITY_TYPE"],
					$logEntry["ENTITY_ID"],
					($feature == "microblog" ? "blog" : $feature),
					$commentEvent["OPERATION_ADD"],
					\CSocNetUser::isCurrentUserModuleAdmin()
				);
			}
			else
			{
				$canAddComments = true;
			}
		}

		return $canAddComments;
	}

	public static function addLiveComment($comment = array(), $logEntry, $commentEvent = array(), $params = array())
	{
		global $USER_FIELD_MANAGER, $DB, $APPLICATION;

		$result = array();

		if (
			!is_array($comment)
			|| empty($comment)
			|| !is_array($logEntry)
			|| empty($logEntry)
			|| !is_array($commentEvent)
			|| empty($commentEvent)
		)
		{
			return $result;
		}

		if (
			!isset($params["ACTION"])
			|| !in_array($params["ACTION"], array("ADD", "UPDATE"))
		)
		{
			$params["ACTION"] = "ADD";
		}

		if (
			!isset($params["LANGUAGE_ID"])
			|| empty($params["LANGUAGE_ID"])
		)
		{
			$params["LANGUAGE_ID"] = LANGUAGE_ID;
		}

		if (
			!isset($params["SITE_ID"])
			|| empty($params["SITE_ID"])
		)
		{
			$params["SITE_ID"] = SITE_ID;
		}

		if ($params["ACTION"] == "ADD")
		{
			if (
				!empty($commentEvent)
				&& !empty($commentEvent["METHOD_CANEDIT"])
				&& !empty($comment["SOURCE_ID"])
				&& intval($comment["SOURCE_ID"]) > 0
				&& !empty($logEntry["SOURCE_ID"])
				&& intval($logEntry["SOURCE_ID"]) > 0
			)
			{
				$canEdit = call_user_func($commentEvent["METHOD_CANEDIT"], array(
					"LOG_SOURCE_ID" => $logEntry["SOURCE_ID"],
					"COMMENT_SOURCE_ID" => $comment["SOURCE_ID"]
				));
			}
			else
			{
				$canEdit = true;
			}
		}

		$result["hasEditCallback"] = (
			$canEdit
			&& is_array($commentEvent)
			&& isset($commentEvent["UPDATE_CALLBACK"])
			&& (
				$commentEvent["UPDATE_CALLBACK"] == "NO_SOURCE"
				|| is_callable($commentEvent["UPDATE_CALLBACK"])
			)
				? "Y"
				: "N"
		);

		$result["hasDeleteCallback"] = (
			$canEdit
			&& is_array($commentEvent)
			&& isset($commentEvent["DELETE_CALLBACK"])
			&& (
				$commentEvent["DELETE_CALLBACK"] == "NO_SOURCE"
				|| is_callable($commentEvent["DELETE_CALLBACK"])
			)
				? "Y"
				: "N"
		);

		if (
			!isset($params["SOURCE_ID"])
			|| intval($params["SOURCE_ID"]) <= 0
		)
		{
			foreach (EventManager::getInstance()->findEventHandlers('socialnetwork', 'OnAfterSonetLogEntryAddComment') as $handler)  // send notification
			{
				ExecuteModuleEventEx($handler, array($comment));
			}
		}

		$result["arComment"] = $comment;
		foreach($result["arComment"] as $key => $value)
		{
			if (strpos($key, "~") === 0)
			{
				unset($result["arComment"][$key]);
			}
		}

		$result["arComment"]["RATING_USER_HAS_VOTED"] = "N";

		$result["sourceID"] = $comment["SOURCE_ID"];
		$result["timestamp"] = makeTimeStamp(
			array_key_exists("LOG_DATE_FORMAT", $comment)
				? $comment["LOG_DATE_FORMAT"]
				: $comment["LOG_DATE"]
		);

		$comment["UF"] = $USER_FIELD_MANAGER->getUserFields("SONET_COMMENT", $comment["ID"], LANGUAGE_ID);

		if (
			array_key_exists("UF_SONET_COM_DOC", $comment["UF"])
			&& array_key_exists("VALUE", $comment["UF"]["UF_SONET_COM_DOC"])
			&& is_array($comment["UF"]["UF_SONET_COM_DOC"]["VALUE"])
			&& count($comment["UF"]["UF_SONET_COM_DOC"]["VALUE"]) > 0
			&& $commentEvent["EVENT_ID"] != "tasks_comment"
		)
		{
			$rights = array();
			$res = \CSocNetLogRights::getList(array(), array("LOG_ID" => $logEntry["ID"]));
			while ($right = $res->fetch())
			{
				$rights[] = $right["GROUP_CODE"];
			}

			\CSocNetLogTools::SetUFRights($comment["UF"]["UF_SONET_COM_DOC"]["VALUE"], $rights);
		}

		$dateFormated = FormatDate(
			$DB->dateFormatToPHP(FORMAT_DATE),
			$result["timestamp"]
		);

		$timeFormat = (
			!empty($params["TIME_FORMAT"])
				? $params["TIME_FORMAT"]
				: \CSite::getTimeFormat()
		);

		$result["timeFormatted"] = formatDateFromDB(
			(
				array_key_exists("LOG_DATE_FORMAT", $comment)
					? $comment["LOG_DATE_FORMAT"]
					: $comment["LOG_DATE"]
			),
			(
				stripos($timeFormat, 'a')
				|| (
					$timeFormat == 'FULL'
					&& (strpos(FORMAT_DATETIME, 'T')!==false || strpos(FORMAT_DATETIME, 'TT')!==false)
				) !== false
					? (strpos(FORMAT_DATETIME, 'TT')!==false ? 'H:MI TT' : 'H:MI T')
					: 'HH:MI'
			)
		);

		if (intval($comment["USER_ID"]) > 0)
		{
			$user = array(
				"ID" => $comment["USER_ID"],
				"NAME" => $comment["~CREATED_BY_NAME"],
				"LAST_NAME" => $comment["~CREATED_BY_LAST_NAME"],
				"SECOND_NAME" => $comment["~CREATED_BY_SECOND_NAME"],
				"LOGIN" => $comment["~CREATED_BY_LOGIN"],
				"PERSONAL_PHOTO" => $comment["~CREATED_BY_PERSONAL_PHOTO"],
				"PERSONAL_GENDER" => $comment["~CREATED_BY_PERSONAL_GENDER"],
			);
			$createdBy = array(
				"FORMATTED" => \CUser::formatName($params["NAME_TEMPLATE"], $user, ($params["SHOW_LOGIN"] != "N")),
				"URL" => \CComponentEngine::makePathFromTemplate(
					$params["PATH_TO_USER"],
					array(
						"user_id" => $comment["USER_ID"],
						"id" => $comment["USER_ID"]
					)
				)
			);
		}
		else
		{
			$user = array();
			$createdBy = array(
				"FORMATTED" => Loc::getMessage("SONET_HELPER_CREATED_BY_ANONYMOUS", false, $params["LANGUAGE_ID"])
			);
		}

		$commentFormatted = array(
			"LOG_DATE" => $comment["LOG_DATE"],
			"LOG_DATE_FORMAT" => $comment["LOG_DATE_FORMAT"],
			"LOG_DATE_DAY" => ConvertTimeStamp(MakeTimeStamp($comment["LOG_DATE"]), "SHORT"),
			"LOG_TIME_FORMAT" => $result["timeFormatted"],
			"MESSAGE" => $comment["MESSAGE"],
			"MESSAGE_FORMAT" => $comment["~MESSAGE"],
			"CREATED_BY" => $createdBy,
			"AVATAR_SRC" => \CSocNetLogTools::formatEvent_CreateAvatar($user, $params, ""),
			"USER_ID" => $comment["USER_ID"]
		);

		if (
			array_key_exists("CLASS_FORMAT", $commentEvent)
			&& array_key_exists("METHOD_FORMAT", $commentEvent)
		)
		{
			$fieldsFormatted = call_user_func(
				array($commentEvent["CLASS_FORMAT"], $commentEvent["METHOD_FORMAT"]),
				$comment,
				$params
			);
			$commentFormatted["MESSAGE_FORMAT"] = htmlspecialcharsback($fieldsFormatted["EVENT_FORMATTED"]["MESSAGE"]);
		}
		else
		{
			$commentFormatted["MESSAGE_FORMAT"] = $comment["MESSAGE"];
		}

		if (
			array_key_exists("CLASS_FORMAT", $commentEvent)
			&& array_key_exists("METHOD_FORMAT", $commentEvent)
		)
		{
			$fieldsFormatted = call_user_func(
				array($commentEvent["CLASS_FORMAT"], $commentEvent["METHOD_FORMAT"]),
				$comment,
				array_merge(
					$params,
					array(
						"MOBILE" => "Y"
					)
				)
			);
			$messageMobile = htmlspecialcharsback($fieldsFormatted["EVENT_FORMATTED"]["MESSAGE"]);
		}
		else
		{
			$messageMobile = $comment["MESSAGE"];
		}

		$result["arCommentFormatted"] = $commentFormatted;

		if (
			isset($params["PULL"])
			&& $params["PULL"] == "Y"
			&& Loader::includeModule("pull")
			&& \CPullOptions::GetNginxStatus()
		)
		{
			$forumMetaData = \CSocNetLogTools::getForumCommentMetaData($logEntry["EVENT_ID"]);

			if (
				$logEntry["ENTITY_TYPE"] == "CRMACTIVITY"
				&& Loader::includeModule("crm")
				&& ($activity = \CCrmActivity::getByID($logEntry["ENTITY_ID"], false))
				&& ($activity["TYPE_ID"] == \CCrmActivityType::Task)
			)
			{
				$entityXMLId = "TASK_".$activity["ASSOCIATED_ENTITY_ID"];
			}
			elseif (
				$logEntry["ENTITY_TYPE"] == "WF"
				&& $logEntry["SOURCE_ID"] > 0
				&& Loader::includeModule("bizproc")
				&& ($workflowId = \CBPStateService::getWorkflowByIntegerId($logEntry["SOURCE_ID"]))
			)
			{
				$entityXMLId = "WF_".$workflowId;
			}
			elseif (
				$forumMetaData
				&& $logEntry["SOURCE_ID"] > 0
			)
			{
				$entityXMLId = $forumMetaData[0]."_".$logEntry["SOURCE_ID"];
			}
			else
			{
				$entityXMLId = strtoupper($logEntry["EVENT_ID"])."_".$logEntry["ID"];
			}

			$listCommentId = (
				!!$comment["SOURCE_ID"]
					? $comment["SOURCE_ID"]
					: $comment["ID"]
			);

			$eventHandlerID = EventManager::getInstance()->addEventHandlerCompatible("main", "system.field.view.file", array("CSocNetLogTools", "logUFfileShow"));
			$rights = \CSocNetLogComponent::getCommentRights(array(
				"EVENT_ID" => $logEntry["EVENT_ID"],
				"SOURCE_ID" => $logEntry["SOURCE_ID"]
			));
			$res = $APPLICATION->IncludeComponent(
				"bitrix:main.post.list",
				"",
				array(
					"TEMPLATE_ID" => '',
					"RATING_TYPE_ID" => $comment["RATING_TYPE_ID"],
					"ENTITY_XML_ID" => $entityXMLId,
					"RECORDS" => array(
						$listCommentId => array(
							"ID" => $listCommentId,
							"NEW" => "Y",
							"APPROVED" => "Y",
							"POST_TIMESTAMP" => $result["timestamp"],
							"AUTHOR" => array(
								"ID" => $user["ID"],
								"NAME" => $user["NAME"],
								"LAST_NAME" => $user["LAST_NAME"],
								"SECOND_NAME" => $user["SECOND_NAME"],
								"AVATAR" => $commentFormatted["AVATAR_SRC"]
							),
							"FILES" => false,
							"UF" => $comment["UF"],
							"~POST_MESSAGE_TEXT" => $comment["~MESSAGE"],
							"WEB" => array(
								"CLASSNAME" => "",
								"POST_MESSAGE_TEXT" => $commentFormatted["MESSAGE_FORMAT"],
								"AFTER" => $commentFormatted["UF"]
							),
							"MOBILE" => array(
								"CLASSNAME" => "",
								"POST_MESSAGE_TEXT" => $messageMobile
							)
						)
					),
					"NAV_STRING" => "",
					"NAV_RESULT" => "",
					"PREORDER" => "N",
					"RIGHTS" => array(
						"MODERATE" => "N",
						"EDIT" => $rights["COMMENT_RIGHTS_EDIT"],
						"DELETE" => $rights["COMMENT_RIGHTS_DELETE"]
					),
					"VISIBLE_RECORDS_COUNT" => 1,

					"ERROR_MESSAGE" => "",
					"OK_MESSAGE" => "",
					"RESULT" => $listCommentId,
					"PUSH&PULL" => array(
						"ACTION" => "REPLY",
						"ID" => $listCommentId
					),
					"MODE" => "PULL_MESSAGE",
					"VIEW_URL" => (
						isset($comment["EVENT"]["URL"])
						&& strlen($comment["EVENT"]["URL"]) > 0
							? $comment["EVENT"]["URL"]
							: (
								isset($params["PATH_TO_LOG_ENTRY"])
								&& strlen($params["PATH_TO_LOG_ENTRY"]) > 0
									? \CComponentEngine::makePathFromTemplate(
										$params["PATH_TO_LOG_ENTRY"],
										array(
											"log_id" => $logEntry["ID"]
										)
									)."?commentId=#ID#"
									: ""
							)
					),
					"EDIT_URL" => "__logEditComment('".$entityXMLId."', '#ID#', '".$logEntry["ID"]."');",
					"MODERATE_URL" => "",
					"DELETE_URL" => '/bitrix/components/bitrix/socialnetwork.log.entry/ajax.php?lang='.$params["LANGUAGE_ID"].'&action=delete_comment&delete_comment_id=#ID#&post_id='.$logEntry["ID"].'&site='.$params["SITE_ID"],
					"AUTHOR_URL" => '',

					"AVATAR_SIZE" => $params["AVATAR_SIZE_COMMENT"],
					"NAME_TEMPLATE" => $params["NAME_TEMPLATE"],
					"SHOW_LOGIN" => $params["SHOW_LOGIN"],

					"DATE_TIME_FORMAT" => $params["DATE_TIME_FORMAT"],
					"LAZYLOAD" => "Y",

					"NOTIFY_TAG" => "",
					"NOTIFY_TEXT" => "",
					"SHOW_MINIMIZED" => "Y",
					"SHOW_POST_FORM" => "Y",

					"IMAGE_SIZE" => "",
					"mfi" => ""
				),
				array(),
				null
			);

			if ($eventHandlerID > 0)
			{
				EventManager::getInstance()->removeEventHandler('main', 'system.field.view.file', $eventHandlerID);
			}

			$result['return_data'] = $res['JSON'];
		}

		return $result;
	}

	public static function processBlogPostNewCrmContact(&$HTTPPost, &$componentResult)
	{
		$USent = (
			isset($HTTPPost["SPERM"]["U"])
			&& is_array($HTTPPost["SPERM"]["U"])
			&& !empty($HTTPPost["SPERM"]["U"])
		);

		$UESent = (
			$componentResult["ALLOW_EMAIL_INVITATION"]
			&& isset($HTTPPost["SPERM"]["UE"])
			&& is_array($HTTPPost["SPERM"]["UE"])
			&& !empty($HTTPPost["SPERM"]["UE"])
		);

		if (
			($USent || $UESent)
			&& Loader::includeModule('crm')
			&& Loader::includeModule('mail')
		)
		{
			if ($USent) // process mail users/contacts
			{
				$userIdList = array();
				foreach ($HTTPPost["SPERM"]["U"] as $code)
				{
					if 	(preg_match('/^U(\d+)$/i', $code, $matches))
					{
						$userIdList[] = intval($matches[1]);
					}
				}

				if (!empty($userIdList))
				{
					$res = Main\UserTable::getList(array(
						'filter' => array(
							'ID' => $userIdList,
							'!=UF_USER_CRM_ENTITY' => false
						),
						'select' => array('ID', 'UF_USER_CRM_ENTITY')
					));
					while ($user = $res->fetch())
					{
						$livefeedCrmEntity = \CCrmLiveFeedComponent::resolveLFEntutyFromUF($user['UF_USER_CRM_ENTITY']);

						if (!empty($livefeedCrmEntity))
						{
							list($k, $v) = $livefeedCrmEntity;
							if ($k && $v)
							{
								if (!isset($HTTPPost["SPERM"][$k]))
								{
									$HTTPPost["SPERM"][$k] = array();
								}
								$HTTPPost["SPERM"][$k][] = $k.$v;
							}
						}
					}
				}
			}

			if ($UESent) // process emails
			{
				foreach ($HTTPPost["SPERM"]["UE"] as $key => $userEmail)
				{
					if (!check_email($userEmail))
					{
						continue;
					}

					$found = false;

					$res = \CUser::getList(
						$o = "ID",
						$b = "ASC",
						array("=EMAIL" => $userEmail),
						array("FIELDS" => array("ID", "EXTERNAL_AUTH_ID", "ACTIVE"))
					);

					while ($emailUser = $res->fetch())
					{
						if (
							intval($emailUser["ID"]) > 0
							&& (
								$emailUser["ACTIVE"] == "Y"
								|| $emailUser["EXTERNAL_AUTH_ID"] == "email"
							)
						)
						{
							if ($emailUser["ACTIVE"] == "N") // email only
							{
								$user = new \CUser;
								$user->update($emailUser["ID"], array(
									"ACTIVE" => "Y"
								));
							}

							$HTTPPost["SPERM"]["U"][] = "U".$emailUser["ID"];
							$found = true;
						}
					}

					if ($found)
					{
						continue;
					}

					$userFields = array(
						'EMAIL' => $userEmail,
						'NAME' => (
							isset($HTTPPost["INVITED_USER_NAME"])
							&& isset($HTTPPost["INVITED_USER_NAME"][$userEmail])
								? $HTTPPost["INVITED_USER_NAME"][$userEmail]
								: ''
						),
						'LAST_NAME' => (
							isset($HTTPPost["INVITED_USER_LAST_NAME"])
							&& isset($HTTPPost["INVITED_USER_LAST_NAME"][$userEmail])
								? $HTTPPost["INVITED_USER_LAST_NAME"][$userEmail]
								: ''
						)
					);

					if (!empty($HTTPPost["INVITED_USER_CRM_ENTITY"][$userEmail]))
					{
						$userFields['UF'] = array(
							'UF_USER_CRM_ENTITY' => $HTTPPost["INVITED_USER_CRM_ENTITY"][$userEmail]
						);
						$res = \CCrmLiveFeedComponent::resolveLFEntutyFromUF($HTTPPost["INVITED_USER_CRM_ENTITY"][$userEmail]);
						if (!empty($res))
						{
							list($k, $v) = $res;
							if ($k && $v)
							{
								if (!isset($HTTPPost["SPERM"][$k]))
								{
									$HTTPPost["SPERM"][$k] = array();
								}
								$HTTPPost["SPERM"][$k][] = $k.$v;

								if (
									$k == \CCrmLiveFeedEntity::Contact
									&& ($contact = \CCrmContact::GetByID($v))
									&& intval($contact['PHOTO']) > 0
								)
								{
									$userFields['PERSONAL_PHOTO_ID'] = intval($contact['PHOTO']);
								}
							}
						}
					}
					elseif (
						$HTTPPost["INVITED_USER_CREATE_CRM_CONTACT"][$userEmail] == 'Y'
						&& ($contactId = \CCrmLiveFeedComponent::createContact($userFields))
					)
					{
						$userFields['UF'] = array(
							'UF_USER_CRM_ENTITY' => 'C_'.$contactId
						);
						if (!isset($HTTPPost["SPERM"]["CRMCONTACT"]))
						{
							$HTTPPost["SPERM"]["CRMCONTACT"] = array();
						}
						$HTTPPost["SPERM"]["CRMCONTACT"][] = "CRMCONTACT".$contactId;
					}

					// invite extranet user by email
					$invitedUserId = \Bitrix\Mail\User::create($userFields);

					$errorMessage = false;

					if (
						intval($invitedUserId) <= 0
						&& $invitedUserId->LAST_ERROR <> ''
					)
					{
						$errorMessage = $invitedUserId->LAST_ERROR;
					}

					if (
						!$errorMessage
						&& intval($invitedUserId) > 0
					)
					{
						if (!isset($HTTPPost["SPERM"]["U"]))
						{
							$HTTPPost["SPERM"]["U"] = array();
						}
						$HTTPPost["SPERM"]["U"][] = "U".$invitedUserId;
					}
					else
					{
						$componentResult["ERROR_MESSAGE"] .= $errorMessage;
					}
				}
				unset($HTTPPost["SPERM"]["UE"]);
			}
		}
	}

}
