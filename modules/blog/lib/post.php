<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage blog
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Blog;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;

Loc::loadMessages(__FILE__);

class PostTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_blog_post';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime'
			),
			'DATE_PUBLISH' => array(
				'data_type' => 'datetime'
			),
			'PUBLISH_STATUS' => array(
				'data_type' => 'string',
				'values' => array(BLOG_PUBLISH_STATUS_DRAFT, BLOG_PUBLISH_STATUS_READY, BLOG_PUBLISH_STATUS_PUBLISH)
			),
		);

		return $fieldsMap;
	}

	public static function add(array $data)
	{
		throw new NotImplementedException("Use CBlogPost class.");
	}

	public static function update($primary, array $data)
	{
		throw new NotImplementedException("Use CBlogPost class.");
	}

	public static function delete($primary)
	{
		throw new NotImplementedException("Use CBlogPost class.");
	}
}
