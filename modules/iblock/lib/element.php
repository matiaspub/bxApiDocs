<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class ElementTable extends Entity\DataManager
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
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_iblock_element';
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
				'title' => Loc::getMessage('IBLOCK_ELEMENT_ENTITY_ID_FIELD'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('IBLOCK_ELEMENT_ENTITY_NAME_FIELD'),
			),
			'CODE' => array(
				'data_type' => 'string',
			),
			'PREVIEW_PICTURE' => array(
				'data_type' => 'integer',
			),
			'DETAIL_PICTURE' => array(
				'data_type' => 'integer',
			),
			'PREVIEW_TEXT' => array(
				'data_type' => 'string',
			),
			'DETAIL_TEXT' => array(
				'data_type' => 'string',
			),
			'IBLOCK_ID' => array(
				'data_type' => 'integer',
			),
			'IBLOCK' => array(
				'data_type' => 'Iblock',
				'reference' => array('=this.IBLOCK_ID' => 'ref.ID'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N','Y'),
			),
			'IBLOCK_SECTION_ID' => array(
				'data_type' => 'integer',
			),
		);
	}
}
