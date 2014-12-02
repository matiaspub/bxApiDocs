<?php
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class SectionElementTable extends Entity\DataManager
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
		return 'b_iblock_section_element';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'IBLOCK_SECTION_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('IBLOCK_SECTION_ELEMENT_ENTITY_IBLOCK_SECTION_ID_FIELD'),
			),
			'IBLOCK_SECTION' => array(
				'data_type' => 'Section',
				'reference' => array('=this.IBLOCK_SECTION_ID' => 'ref.ID'),
			),
			'IBLOCK_ELEMENT_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('IBLOCK_SECTION_ELEMENT_ENTITY_IBLOCK_ELEMENT_ID_FIELD'),
			),
			'IBLOCK_ELEMENT' => array(
				'data_type' => 'Element',
				'reference' => array('=this.IBLOCK_ELEMENT_ID' => 'ref.ID'),
				'title' => Loc::getMessage('IBLOCK_SECTION_ELEMENT_ENTITY_IBLOCK_ELEMENT_FIELD'),
			),
			'ADDITIONAL_PROPERTY_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('IBLOCK_SECTION_ELEMENT_ENTITY_ADDITIONAL_PROPERTY_ID_FIELD'),
			)
		);
	}
}
