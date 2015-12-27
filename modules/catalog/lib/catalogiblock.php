<?php
namespace Bitrix\Catalog;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class CatalogIblockTable
 *
 * Fields:
 * <ul>
 * <li> IBLOCK_ID int mandatory
 * <li> YANDEX_EXPORT bool optional default 'N'
 * <li> SUBSCRIPTION bool optional default 'N'
 * <li> VAT_ID int optional
 * <li> PRODUCT_IBLOCK_ID int mandatory
 * <li> SKU_PROPERTY_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Catalog
 **/

class CatalogIblockTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_iblock';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'IBLOCK_ID' => new Main\Entity\IntegerField('IBLOCK_ID', array(
				'primary' => true,
				'title' => Loc::getMessage('IBLOCK_ENTITY_IBLOCK_ID_FIELD')
			)),
			'YANDEX_EXPORT' => new Main\Entity\BooleanField('YANDEX_EXPORT', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N',
				'title' => Loc::getMessage('IBLOCK_ENTITY_YANDEX_EXPORT_FIELD')
			)),
			'SUBSCRIPTION' => new Main\Entity\BooleanField('SUBSCRIPTION', array(
				'values' => array('N', 'Y'),
				'default_value' => 'N',
				'title' => Loc::getMessage('IBLOCK_ENTITY_SUBSCRIPTION_FIELD')
			)),
			'VAT_ID' => new Main\Entity\IntegerField('VAT_ID', array(
				'default_value' => 0,
				'title' => Loc::getMessage('IBLOCK_ENTITY_VAT_ID_FIELD')
			)),
			'PRODUCT_IBLOCK_ID' => new Main\Entity\IntegerField('PRODUCT_IBLOCK_ID', array(
				'default_value' => 0,
				'title' => Loc::getMessage('IBLOCK_ENTITY_PRODUCT_IBLOCK_ID_FIELD'),
			)),
			'SKU_PROPERTY_ID' => new Main\Entity\IntegerField('SKU_PROPERTY_ID', array(
				'default_value' => 0,
				'title' => Loc::getMessage('IBLOCK_ENTITY_SKU_PROPERTY_ID_FIELD')
			)),
			'IBLOCK' => new Main\Entity\ReferenceField(
				'IBLOCK',
				'Bitrix\Iblock\Iblock',
				array('=this.IBLOCK_ID' => 'ref.ID'),
				array('join_type' => 'INNER')
			)
		);
	}
}