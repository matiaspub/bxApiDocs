<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;

class SectionElementTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_iblock_section_element';
	}

	public static function getMap()
	{
		return array(
			'IBLOCK_SECTION_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'IBLOCK_SECTION' => array(
				'data_type' => 'Section',
				'reference' => array(
					'=this.IBLOCK_SECTION_ID' => 'ref.ID'
				)
			),
			'IBLOCK_ELEMENT_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'IBLOCK_ELEMENT' => array(
				'data_type' => 'Element',
				'reference' => array(
					'=this.IBLOCK_ELEMENT_ID' => 'ref.ID'
				)
			),
			'ADDITIONAL_PROPERTY_ID' => array(
				'data_type' => 'integer'
			)
		);
	}
}
