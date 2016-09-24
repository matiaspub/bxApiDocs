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

Loc::loadMessages(__FILE__);

class PostSocnetRightsTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_blog_socnet_rights';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'POST_ID' => array(
				'data_type' => 'integer',
			),
			'POST' => array(
				'data_type' => '\Bitrix\Blog\Post',
				'reference' => array('=this.POST_ID' => 'ref.ID')
			),
			'ENTITY_TYPE' => array(
				'data_type' => 'string'
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
			),
			'ENTITY' => array(
				'data_type' => 'string'
			),
		);

		return $fieldsMap;
	}
}
