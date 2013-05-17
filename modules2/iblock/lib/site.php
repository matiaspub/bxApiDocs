<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Iblock;

use Bitrix\Main\Entity;

class SiteTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_iblock_site';
	}

	public static function getMap()
	{
		return array(
			'IBLOCK_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'IBLOCK' => array(
				'data_type' => 'Iblock',
				'reference' => array('=this.IBLOCK_ID' => 'ref.ID')
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'primary' => true
			)
		);
	}
}
