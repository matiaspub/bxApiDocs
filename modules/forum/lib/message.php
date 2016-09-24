<?php
namespace Bitrix\Forum;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\NotImplementedException;

/**
 * Class MessageTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> FORUM_ID int mandatory
 * <li> TOPIC_ID int mandatory
 * <li> USE_SMILES bool optional default 'Y'
 * <li> NEW_TOPIC bool optional default 'N'
 * <li> APPROVED bool optional default 'Y'
 * <li> SOURCE_ID string(255) mandatory default 'WEB'
 * <li> POST_DATE datetime mandatory
 * <li> POST_MESSAGE string optional
 * <li> POST_MESSAGE_HTML string optional
 * <li> POST_MESSAGE_FILTER string optional
 * <li> POST_MESSAGE_CHECK string(32) optional
 * <li> ATTACH_IMG int optional
 * <li> PARAM1 string(2) optional
 * <li> PARAM2 int optional
 * <li> AUTHOR_ID int optional
 * <li> AUTHOR_NAME string(255) optional
 * <li> AUTHOR_EMAIL string(255) optional
 * <li> AUTHOR_IP string(255) optional
 * <li> AUTHOR_REAL_IP string(128) optional
 * <li> GUEST_ID int optional
 * <li> EDITOR_ID int optional
 * <li> EDITOR_NAME string(255) optional
 * <li> EDITOR_EMAIL string(255) optional
 * <li> EDIT_REASON string optional
 * <li> EDIT_DATE datetime optional
 * <li> XML_ID string(255) optional
 * <li> HTML string optional
 * <li> MAIL_HEADER string optional
 * </ul>
 *
 * @package Bitrix\Forum
 **/
class MessageTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_forum_message';
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
			),
			'FORUM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TOPIC_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'USE_SMILES' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'NEW_TOPIC' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'APPROVED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'SOURCE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateSourceId'),
			),
			'POST_DATE' => array(
				'data_type' => 'datetime',
				'required' => true,
			),
			'POST_MESSAGE' => array(
				'data_type' => 'text',
			),
			'POST_MESSAGE_HTML' => array(
				'data_type' => 'text',
			),
			'POST_MESSAGE_FILTER' => array(
				'data_type' => 'text',
			),
			'POST_MESSAGE_CHECK' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validatePostMessageCheck'),
			),
			'ATTACH_IMG' => array(
				'data_type' => 'integer',
			),
			'PARAM1' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateParam1'),
			),
			'PARAM2' => array(
				'data_type' => 'integer',
			),
			'AUTHOR_ID' => array(
				'data_type' => 'integer',
			),
			'AUTHOR_NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateAuthorName'),
			),
			'AUTHOR_EMAIL' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateAuthorEmail'),
			),
			'AUTHOR_IP' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateAuthorIp'),
			),
			'AUTHOR_REAL_IP' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateAuthorRealIp'),
			),
			'GUEST_ID' => array(
				'data_type' => 'integer',
			),
			'EDITOR_ID' => array(
				'data_type' => 'integer',
			),
			'EDITOR_NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateEditorName'),
			),
			'EDITOR_EMAIL' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateEditorEmail'),
			),
			'EDIT_REASON' => array(
				'data_type' => 'text',
			),
			'EDIT_DATE' => array(
				'data_type' => 'datetime',
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateXmlId'),
			),
			'HTML' => array(
				'data_type' => 'text',
			),
			'MAIL_HEADER' => array(
				'data_type' => 'text',
			),
		);
	}

	/**
	 * Returns validators for SOURCE_ID field.
	 *
	 * @return array
	 */
	public static function validateSourceId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for POST_MESSAGE_CHECK field.
	 *
	 * @return array
	 */
	public static function validatePostMessageCheck()
	{
		return array(
			new Main\Entity\Validator\Length(null, 32),
		);
	}

	/**
	 * Returns validators for PARAM1 field.
	 *
	 * @return array
	 */
	public static function validateParam1()
	{
		return array(
			new Main\Entity\Validator\Length(null, 2),
		);
	}

	/**
	 * Returns validators for AUTHOR_NAME field.
	 *
	 * @return array
	 */
	public static function validateAuthorName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for AUTHOR_EMAIL field.
	 *
	 * @return array
	 */
	public static function validateAuthorEmail()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for AUTHOR_IP field.
	 *
	 * @return array
	 */
	public static function validateAuthorIp()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for AUTHOR_REAL_IP field.
	 *
	 * @return array
	 */
	public static function validateAuthorRealIp()
	{
		return array(
			new Main\Entity\Validator\Length(null, 128),
		);
	}

	/**
	 * Returns validators for EDITOR_NAME field.
	 *
	 * @return array
	 */
	public static function validateEditorName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for EDITOR_EMAIL field.
	 *
	 * @return array
	 */
	public static function validateEditorEmail()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for XML_ID field.
	 *
	 * @return array
	 */
	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}


	/**
	 * Adds row to entity table
	 *
	 * @param array $data
	 *
	 * @return Entity\AddResult Contains ID of inserted row
	 *
	 * @throws \Exception
	 */
	public static function add(array $data)
	{
		throw new NotImplementedException;
	}

	/**
	 * Updates row in entity table by primary key
	 *
	 * @param mixed $primary
	 * @param array $data
	 *
	 * @return Entity\UpdateResult
	 *
	 * @throws \Exception
	 */
	public static function update($primary, array $data)
	{
		throw new NotImplementedException;
	}

	/**
	 * Deletes row in entity table by primary key
	 *
	 * @param mixed $primary
	 *
	 * @return Entity\DeleteResult
	 *
	 * @throws \Exception
	 */
	public static function delete($primary)
	{
		throw new NotImplementedException;
	}
}