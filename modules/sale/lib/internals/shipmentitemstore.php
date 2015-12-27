<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class ShipmentItemStoreTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> BASKET_ID int mandatory
 * <li> BARCODE string(100) optional
 * <li> STORE_ID int mandatory
 * <li> QUANTITY double mandatory
 * <li> DATE_CREATE datetime optional
 * <li> DATE_MODIFY datetime optional
 * <li> CREATED_BY int optional
 * <li> MODIFIED_BY int optional
 * </ul>
 *
 * @package Bitrix\Sale
 **/

class ShipmentItemStoreTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sale_store_barcode';
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
				'title' => Loc::getMessage('STORE_BARCODE_ENTITY_ID_FIELD'),
			),
			'ORDER_DELIVERY_BASKET_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('STORE_BARCODE_ENTITY_ORDER_DELIVERY_BASKET_ID_FIELD'),
			),
			'BASKET_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('STORE_BARCODE_ENTITY_BASKET_ID_FIELD'),
			),
			'BARCODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateBarcode'),
				'title' => Loc::getMessage('STORE_BARCODE_ENTITY_BARCODE_FIELD'),
			),
			'STORE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('STORE_BARCODE_ENTITY_STORE_ID_FIELD'),
			),
			'QUANTITY' => array(
				'data_type' => 'float',
				'required' => true,
				'title' => Loc::getMessage('STORE_BARCODE_ENTITY_QUANTITY_FIELD'),
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('STORE_BARCODE_ENTITY_DATE_CREATE_FIELD'),
			),
			'DATE_MODIFY' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('STORE_BARCODE_ENTITY_DATE_MODIFY_FIELD'),
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('STORE_BARCODE_ENTITY_CREATED_BY_FIELD'),
			),
			'MODIFIED_BY' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('STORE_BARCODE_ENTITY_MODIFIED_BY_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for BARCODE field.
	 *
	 * @return array
	 */
	public static function validateBarcode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 100),
		);
	}
}