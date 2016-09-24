<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Sale;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class GoodsSectionTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_iblock_section_element';
	}

	public static function getMap()
	{
		return array(
			'IBLOCK_ELEMENT_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'PRODUCT' => array(
				'data_type' => 'Product',
				'reference' => array(
					'=this.IBLOCK_ELEMENT_ID' => 'ref.ID'
				)
			),
			'IBLOCK_SECTION_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'SECT' => array(
				'data_type' => 'Section',
				'reference' => array(
					'=this.IBLOCK_SECTION_ID' => 'ref.ID'
				)
			)
		);
	}
}
