<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
/**
 * Class TypeTable
 *
 * Fields:
 * <ul>
 * <li> ID string(50) mandatory
 * <li> SECTIONS bool optional default 'Y'
 * <li> EDIT_FILE_BEFORE string(255) optional
 * <li> EDIT_FILE_AFTER string(255) optional
 * <li> IN_RSS bool optional default 'N'
 * <li> SORT int optional default 500
 * <li> LANG_MESSAGE reference to {@link \Bitrix\Iblock\TypeLanguageTable}
 * </ul>
 *
 * @package Bitrix\Iblock
 */
class TypeTable extends Entity\DataManager
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
		return 'b_iblock_type';
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
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateId'),
				'title' => Loc::getMessage('IBLOCK_TYPE_ENTITY_ID_FIELD'),
			),
			'SECTIONS' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('IBLOCK_TYPE_ENTITY_SECTIONS_FIELD'),
			),
			'EDIT_FILE_BEFORE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateEditFileBefore'),
				'title' => Loc::getMessage('IBLOCK_TYPE_ENTITY_EDIT_FILE_BEFORE_FIELD'),
			),
			'EDIT_FILE_AFTER' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateEditFileAfter'),
				'title' => Loc::getMessage('IBLOCK_TYPE_ENTITY_EDIT_FILE_AFTER_FIELD'),
			),
			'IN_RSS' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
				'title' => Loc::getMessage('IBLOCK_TYPE_ENTITY_IN_RSS_FIELD'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('IBLOCK_TYPE_ENTITY_SORT_FIELD'),
			),
			'LANG_MESSAGE' => array(
				'data_type' => 'Bitrix\Iblock\TypeLanguage',
				'reference' => array('=this.ID' => 'ref.IBLOCK_TYPE_ID'),
			),
		);
	}

	/**
	 * Returns validators for ID field.
	 *
	 * @return array
	 */
	public static function validateId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Returns validators for EDIT_FILE_BEFORE field.
	 *
	 * @return array
	 */
	public static function validateEditFileBefore()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for EDIT_FILE_AFTER field.
	 *
	 * @return array
	 */
	public static function validateEditFileAfter()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Deletes information blocks of given type
	 * and language messages from TypeLanguageTable
	 *
	 * @param \Bitrix\Main\Entity\Event $event Contains information about iblock type being deleted.
	 *
	 * @return \Bitrix\Main\Entity\EventResult
	 */
	public static function onDelete(\Bitrix\Main\Entity\Event $event)
	{
		$id = $event->getParameter("id");

		//Delete information blocks
		$iblockList = IblockTable::getList(array(
			"select" => array("ID"),
			"filter" => array(
				"=IBLOCK_TYPE_ID" => $id["ID"],
			),
			"order" => array("ID" => "DESC")
		));
		while ($iblock = $iblockList->fetch())
		{
			$iblockDeleteResult = IblockTable::delete($iblock["ID"]);
			if (!$iblockDeleteResult->isSuccess())
			{
				return $iblockDeleteResult;
			}
		}

		//Delete language messages
		$result = TypeLanguageTable::deleteByIblockTypeId($id["ID"]);

		return $result;
	}
}
