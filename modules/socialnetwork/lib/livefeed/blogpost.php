<?php
namespace Bitrix\Socialnetwork\Livefeed;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class BlogPost extends Provider
{
	const PROVIDER_ID = 'BLOG_POST';

	public static function getId()
	{
		return static::PROVIDER_ID;
	}

	protected function initSourceFields()
	{
		$postId = $this->entityId;

		if (
			$postId > 0
			&& Loader::includeModule('blog')
		)
		{
			if (
				($post = \CBlogPost::getById($postId))
				&& (self::canRead(array(
					'POST' => $post
				)))
			)
			{
				$this->setSourceDescription($post['DETAIL_TEXT']);
				$this->setSourceTitle($post['TITLE']);
				$this->setSourceAttachedDiskObjects($this->getAttachedDiskObjects($postId));
				$this->setSourceDiskObjects($this->getDiskObjects($postId, $this->cloneDiskObjects));
			}
		}
	}

	protected function getAttachedDiskObjects($clone = false)
	{
		global $USER_FIELD_MANAGER;
		static $cache = array();

		$postId = $this->entityId;

		$result = array();
		$cacheKey = $postId.$clone;

		if (isset($cache[$cacheKey]))
		{
			$result = $cache[$cacheKey];
		}
		else
		{
			$postUF = $USER_FIELD_MANAGER->getUserFields("BLOG_POST", $postId, LANGUAGE_ID);
			if (
				!empty($postUF['UF_BLOG_POST_FILE'])
				&& !empty($postUF['UF_BLOG_POST_FILE']['VALUE'])
				&& is_array($postUF['UF_BLOG_POST_FILE']['VALUE'])
			)
			{
				if ($clone)
				{
					$this->attachedDiskObjectsCloned = self::cloneUfValues($postUF['UF_BLOG_POST_FILE']['VALUE']);
					$result = $cache[$cacheKey] = array_values($this->attachedDiskObjectsCloned);
				}
				else
				{
					$result = $cache[$cacheKey] = $postUF['UF_BLOG_POST_FILE']['VALUE'];
				}
			}
		}

		return $result;
	}

	public static function canRead($params)
	{
		if (
			!is_array($params)
			&& intval($params) > 0
		)
		{
			$params = array(
				'POST' => \CBlogPost::getByID($params)
			);
		}

		$result = false;
		if (
			isset($params["POST"])
			&& is_array($params["POST"])
		)
		{
			$permissions = self::getPermissions($params["POST"]);
			$result = ($permissions > self::PERMISSION_DENY);
		}

		return $result;
	}

	protected function getPermissions($post)
	{
		global $USER;

		$result = self::PERMISSION_DENY;

		if (Loader::includeModule('blog'))
		{
			if($post["AUTHOR_ID"] == $USER->getId())
			{
				$result = self::PERMISSION_FULL;
			}
			else
			{
				$perms = \CBlogPost::getSocNetPostPerms(array(
					"POST_ID" => $post["ID"],
					"NEED_FULL" => true,
					"USER_ID" => false,
					"POST_AUTHOR_ID" => $post["AUTHOR_ID"],
					"PUBLIC" => 'N',
					"LOG_ID" => false
				));

				if ($perms >= BLOG_PERMS_FULL)
				{
					$result = self::PERMISSION_FULL;
				}
				elseif ($perms >= BLOG_PERMS_READ)
				{
					$result = self::PERMISSION_READ;
				}
			}
		}

		return $result;
	}
}