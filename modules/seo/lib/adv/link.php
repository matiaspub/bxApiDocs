<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Seo\Adv;

use Bitrix\Main\Entity;

/**
 * Class LinkTable
 *
 * Fields:
 * <ul>
 * <li> LINK_TYPE string(1) mandatory
 * <li> LINK_ID int mandatory
 * <li> BANNER_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Seo
 **/

class LinkTable extends Entity\DataManager
{
	const TYPE_IBLOCK_ELEMENT = 'I';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_seo_adv_link';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'LINK_TYPE' => array(
				'data_type' => 'enum',
				'primary' => true,
				'values' => array(static::TYPE_IBLOCK_ELEMENT),
			),
			'LINK_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'BANNER_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'BANNER' => array(
				'data_type' => 'Bitrix\Seo\Adv\YandexBannerTable',
				'reference' => array('=this.BANNER_ID' => 'ref.ID'),
			),
			'IBLOCK_ELEMENT' => array(
				'data_type' => 'Bitrix\Iblock\ElementTable',
				'reference' => array('=this.LINK_ID' => 'ref.ID'),
			),
		);
	}
}