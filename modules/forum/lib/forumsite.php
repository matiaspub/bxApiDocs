<?php
namespace Bitrix\Forum;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use \Bitrix\Forum\Internals\BaseTable;

Loc::loadMessages(__FILE__);

/**
 * Class ForumSiteTable
 *
 * Fields:
 * <ul>
 * <li> FORUM_ID int mandatory
 * <li> SITE_ID char(2) mandatory
 * <li> PATH2FORUM_MESSAGE string(250)
 * <li> FORUM reference to {@link \Bitrix\Forum\ForumTable}
 * <li> SITE reference to {@link \Bitrix\Main\SiteTable}
 * </ul>
 *
 * @package Bitrix\Forum
 */
class ForumSiteTable extends BaseTable
{
	/**
	 * Returns path to the file which contains definition of the class.
	 *
	 * @return string
	 */
	public static function getFilePath()
	{
		return __FILE__;
	}

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_forum2site';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'FORUM_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('FORUM_SITE_TABLE_FIELD_FORUM_ID'),
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateSiteId'),
				'title' => Loc::getMessage('FORUM_SITE_TABLE_FIELD_SITE_ID'),
			),
			'PATH2FORUM_MESSAGE' =>  array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePath'),
				'title' => Loc::getMessage('FORUM_SITE_TABLE_FIELD_SITE_ID'),
			),
			'FORUM' => array(
				'data_type' => 'Bitrix\Forum\Forum',
				'reference' => array('=this.FORUM_ID' => 'ref.ID')
			),
			'SITE' => array(
				'data_type' => 'Bitrix\Main\Site',
				'reference' => array('=this.SITE_ID' => 'ref.LID'),
			),
		);
	}
}
