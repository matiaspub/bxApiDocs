<?php
namespace Bitrix\Socialnetwork\Livefeed;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class BlogComment extends Provider
{
	const PROVIDER_ID = 'BLOG_COMMENT';

	public static function getId()
	{
		return static::PROVIDER_ID;
	}

	protected function initSourceFields()
	{
		$commentId = $this->entityId;

		if (
			$commentId > 0
			&& Loader::includeModule('blog')
		)
		{
			if (
				($comment = \CBlogComment::getById($commentId))
				&& ($post = \CBlogPost::getById($comment["POST_ID"]))
				&& (BlogPost::canRead(array(
					'POST' => $post
				)))
			)
			{
				$this->setSourceDescription($comment['POST_TEXT']);
				$this->setSourceTitle(TruncateText(preg_replace(array("/\n+/is".BX_UTF_PCRE_MODIFIER, "/\s+/is".BX_UTF_PCRE_MODIFIER), " ", \blogTextParser::killAllTags($comment['POST_TEXT'])), 100));
				$this->setSourceAttachedDiskObjects($this->getAttachedDiskObjects());
				$this->setSourceDiskObjects(self::getDiskObjects($commentId, $this->cloneDiskObjects));
			}
		}
	}

	protected function getAttachedDiskObjects($clone = false)
	{
		global $USER_FIELD_MANAGER;
		static $cache = array();

		$commentId = $this->entityId;

		$result = array();
		$cacheKey = $commentId.$clone;

		if (isset($cache[$cacheKey]))
		{
			$result = $cache[$cacheKey];
		}
		else
		{
			$commentUF = $USER_FIELD_MANAGER->getUserFields("BLOG_COMMENT", $commentId, LANGUAGE_ID);
			if (
				!empty($commentUF['UF_BLOG_COMMENT_FILE'])
				&& !empty($commentUF['UF_BLOG_COMMENT_FILE']['VALUE'])
				&& is_array($commentUF['UF_BLOG_COMMENT_FILE']['VALUE'])
			)
			{
				if ($clone)
				{
					$this->attachedDiskObjectsCloned = self::cloneUfValues($commentUF['UF_BLOG_COMMENT_FILE']['VALUE']);
					$result = $cache[$cacheKey] = array_values($this->attachedDiskObjectsCloned);
				}
				else
				{
					$result = $cache[$cacheKey] = $commentUF['UF_BLOG_COMMENT_FILE']['VALUE'];
				}
			}
		}

		return $result;
	}


}