<?php
namespace Bitrix\Socialnetwork\Ui\Preview;

use Bitrix\Main\Loader;

class Post
{
	/**
	 * Returns HTML code for blog post preview.
	 * @param array $params Expected keys: postId, userId.
	 * @return string
	 */
	public static function buildPreview(array $params)
	{
		global $APPLICATION;
		if(!Loader::includeModule('blog'))
			return null;

		ob_start();
		$APPLICATION->includeComponent(
			'bitrix:socialnetwork.blog.post.preview',
			'',
			$params
		);
		return ob_get_clean();
	}

	/**
	 * Returns true if current user has read access to the blog post.
	 * @param array $params Allowed keys: postId, userId.
	 * @param int $userId Current user's id.
	 * @return bool
	 */
	public static function checkUserReadAccess(array $params, $userId)
	{
		if(!Loader::includeModule('blog'))
			return false;

		$permissions = \CBlogPost::GetSocNetPostPerms($params['postId'], true, $userId);
		return ($permissions >= BLOG_PERMS_READ);
	}

}