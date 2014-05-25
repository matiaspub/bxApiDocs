<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class IblockMessageTable
 *
 * Fields:
 * <ul>
 * <li> IBLOCK_ID int mandatory
 * <li> MESSAGE_ID string(50) mandatory
 * <li> MESSAGE_TEXT string(255) optional
 * <li> IBLOCK reference to {@link \Bitrix\Iblock\IblockTable}
 * </ul>
 *
 * @package Bitrix\Iblock
 */
class IblockMessageTable extends Entity\DataManager
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
		return 'b_iblock_messages';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'IBLOCK_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('IBLOCK_MESSAGE_ENTITY_IBLOCK_ID_FIELD'),
			),
			'MESSAGE_ID' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateMessageId'),
				'title' => Loc::getMessage('IBLOCK_MESSAGE_ENTITY_MESSAGE_ID_FIELD'),
			),
			'MESSAGE_TEXT' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateMessageText'),
				'title' => Loc::getMessage('IBLOCK_MESSAGE_ENTITY_MESSAGE_TEXT_FIELD'),
			),
			'IBLOCK' => array(
				'data_type' => 'Bitrix\Iblock\Iblock',
				'reference' => array('=this.IBLOCK_ID' => 'ref.ID')
			),
		);
	}

	/**
	 * Returns validators for MESSAGE_ID field.
	 *
	 * @return array
	 */
	public static function validateMessageId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Returns validators for MESSAGE_TEXT field.
	 *
	 * @return array
	 */
	public static function validateMessageText()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}
