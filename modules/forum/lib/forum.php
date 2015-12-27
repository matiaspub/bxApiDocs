<?php
namespace Bitrix\Forum;

use Bitrix\Main\Entity;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Forum\Internals\BaseTable;
Loc::loadMessages(__FILE__);

/**
 * Class ForumTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> FORUM_GROUP_ID int
 * <li> NAME string(255) mandatory
 * <li> DESCRIPTION text optional
 * <li> SORT int mandatory default '150'
 * <li> ACTIVE bool mandatory default 'Y'

 * <li> ALLOW_HTML bool mandatory default 'N'
 * <li> ALLOW_ANCHOR bool mandatory default 'Y'
 * <li> ALLOW_BIU bool mandatory default 'Y'
 * <li> ALLOW_IMG bool mandatory default 'Y'
 * <li> ALLOW_VIDEO bool mandatory default 'Y'
 * <li> ALLOW_LIST bool mandatory default 'Y'
 * <li> ALLOW_QUOTE bool mandatory default 'Y'
 * <li> ALLOW_CODE bool mandatory default 'Y'
 * <li> ALLOW_FONT bool mandatory default 'Y'
 * <li> ALLOW_SMILES bool mandatory default 'Y'
 * <li> ALLOW_UPLOAD bool mandatory default 'N'
 * <li> ALLOW_TABLE bool mandatory default 'N'
 * <li> ALLOW_ALIGN bool mandatory default 'Y'
 * <li> ALLOW_UPLOAD_EXT string(255) null
 * <li> ALLOW_MOVE_TOPIC bool mandatory default 'Y'
 * <li> ALLOW_TOPIC_TITLED bool mandatory default 'N'
 * <li> ALLOW_NL2BR bool mandatory default 'N'
 * <li> ALLOW_SIGNATURE bool mandatory default 'Y'
 * <li> ASK_GUEST_EMAIL bool mandatory default 'N'
 * <li> USE_CAPTCHA bool mandatory default 'N'
 * <li> INDEXATION bool mandatory default 'Y'
 * <li> DEDUPLICATION bool mandatory default 'Y'
 * <li> MODERATION bool mandatory default 'N'
 * <li> ORDER_BY enum('P', 'T', 'N', 'V', 'D', 'A', '') mandatory default 'P'
 * <li> ORDER_DIRECTION enum('DESC', 'ASC') mandatory default 'DESC'

 * <li> TOPICS int
 * <li> POSTS int
 * <li> LAST_POSTER_ID int
 * <li> LAST_POSTER_NAME string(255)
 * <li> LAST_POST_DATE datetime
 * <li> LAST_MESSAGE_ID int
 * <li> POSTS_UNAPPROVED int
 * <li> ABS_LAST_POSTER_ID int
 * <li> ABS_LAST_POSTER_NAME string(255)
 * <li> ABS_LAST_POST_DATE datetime
 * <li> ABS_LAST_MESSAGE_ID int

 * <li> EVENT1 string(255) default 'forum'
 * <li> EVENT2 string(255) default 'message'
 * <li> EVENT3 string(255)

 * <li> XML_ID varchar(255)
 * </ul>
 *
 * @package Bitrix\Forum
 */
class ForumTable extends BaseTable
{
	private static $topicSort = array(
		"P" => "LAST_POST_DATE",
		"T" => "TITLE",
		"N" => "POSTS",
		"V" => "VIEWS",
		"D" => "START_DATE",
		"A" => "USER_START_NAME"
	);

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_forum';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ID'),
			),
			'FORUM_GROUP_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_FORUM_GROUP_ID'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_NAME'),
				'validation' => array(__CLASS__, 'validateName'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_DESCRIPTION'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_SORT'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ACTIVE'),
			),
			'ALLOW_HTML' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_HTML')
			),
			'ALLOW_ANCHOR' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_ANCHOR')
			),
			'ALLOW_BIU' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_BIU')
			),
			'ALLOW_IMG' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_IMG')
			),
			'ALLOW_VIDEO' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_VIDEO')
			),
			'ALLOW_LIST' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_LIST')
			),
			'ALLOW_QUOTE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_QUOTE')
			),
			'ALLOW_CODE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_CODE')
			),
			'ALLOW_FONT' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_FONT')
			),
			'ALLOW_SMILES' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_SMILES')
			),
			'ALLOW_TABLE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_TABLE')
			),
			'ALLOW_ALIGN' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_ALIGN')
			),
			'ALLOW_UPLOAD' => array(
				'data_type' => 'boolean',
				'values' => array('Y', 'F', 'A'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_UPLOAD')
			),
			'ALLOW_UPLOAD_EXT' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_UPLOAD'),
				'validation' => array(__CLASS__, 'validateFileExt')
			),
			'ALLOW_MOVE_TOPIC' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_MOVE_TOPIC')
			),
			'ALLOW_TOPIC_TITLED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_TOPIC_TITLED')
			),
			'ALLOW_NL' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_NL')
			),
			'ALLOW_SIGNATURE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ALLOW_SIGNATURE')
			),
			'ASK_GUEST_EMAIL' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ASK_GUEST_EMAIL')
			),
			'USE_CAPTCHA' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_USE_CAPTCHA')
			),
			'INDEXATION' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_INDEXATION')
			),
			'DEDUPLICATION' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_DEDUPLICATION')
			),
			'MODERATION' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_MODERATION')
			),
			'ORDER_BY' =>  array(
				'data_type' => 'enum',
				'values' => self::$topicSort,
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ORDER_BY')
			),
			'ORDER_DIRECTION' =>  array(
				'data_type' => 'enum',
				'values' => array('ASC', 'DESC'),
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ORDER_BY')
			),
			'TOPICS' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_TOPICS'),
			),
			'POSTS' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_POSTS'),
			),
			'LAST_POSTER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_'),
			),
			'LAST_POSTER_NAME' => array(
				'required' => true,
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_LAST_POSTER_NAME'),
			),
			'LAST_POST_DATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_LAST_POST_DATE'),
			),
			'LAST_MESSAGE_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_'),
			),
			'POSTS_UNAPPROVED' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_'),
			),
			'ABS_LAST_POSTER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_'),
			),
			'ABS_LAST_POSTER_NAME' => array(
				'required' => true,
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ABS_LAST_POSTER_NAME'),
			),
			'ABS_LAST_POST_DATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_ABS_LAST_POST_DATE'),
			),
			'ABS_LAST_MESSAGE_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_'),
			),
			'EVENT1' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_EVENT1'),
			),
			'EVENT2' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_EVENT2'),
			),
			'EVENT3' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_EVENT3'),
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('FORUM_TABLE_FIELD_EVENT3'),
				'validation' => array(__CLASS__, 'validateXmlId')
			),
		);
	}

	/**
	 * Returns validators for ALLOW_UPLOAD_EXT field.
	 *
	 * @return array
	 */
	public static function validateFileExt()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}
