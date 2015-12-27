<?php
namespace Bitrix\Catalog;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class VatTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> ACTIVE bool optional default 'Y'
 * <li> SORT int optional default 100
 * <li> NAME string(50) mandatory
 * <li> RATE double mandatory default 0.00
 * </ul>
 *
 * @package Bitrix\Catalog
 **/

class VatTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_vat';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('VAT_ENTITY_ID_FIELD'),
			)),
			'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
				'required' => true,
				'title' => Loc::getMessage('VAT_ENTITY_TIMESTAMP_X_FIELD'),
			)),
			'ACTIVE' => new Main\Entity\BooleanField('ACTIVE', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('VAT_ENTITY_ACTIVE_FIELD'),
			)),
			'SORT' => new Main\Entity\IntegerField('SORT', array(
				'column_name' => 'C_SORT',
				'default_value' => 100,
				'title' => Loc::getMessage('VAT_ENTITY_SORT_FIELD'),
			)),
			'NAME' => new Main\Entity\StringField('NAME',  array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('VAT_ENTITY_NAME_FIELD'),
			)),
			'RATE' => new Main\Entity\FloatField('RATE', array(
				'required' => true,
				'title' => Loc::getMessage('VAT_ENTITY_RATE_FIELD'),
			)),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
}