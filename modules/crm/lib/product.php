<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main\Entity;

class ProductTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'IBLOCK_ELEMENT' => array(
				'data_type' => 'IBlockElementProxy',
				'reference' => array('=this.ID' => 'ref.ID')
			),
			'IBLOCK_ELEMENT_GRC' => array(
				'data_type' => 'IBlockElementGrcProxy',
				'reference' => array('=this.ID' => 'ref.ID')
			)
		);
	}
}
