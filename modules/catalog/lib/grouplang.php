<?php
namespace Bitrix\Catalog;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class GroupLangTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CATALOG_GROUP_ID int mandatory
 * <li> LANG string(2) mandatory
 * <li> NAME string(100) optional
 * </ul>
 *
 * @package Bitrix\Catalog
 **/

class GroupLangTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_group_lang';
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
				'title' => Loc::getMessage('GROUP_LANG_ENTITY_ID_FIELD')
			)),
			'CATALOG_GROUP_ID' => new Main\Entity\IntegerField('CATALOG_GROUP_ID', array(
				'title' => Loc::getMessage('GROUP_LANG_ENTITY_CATALOG_GROUP_ID_FIELD')
			)),
			'LANG' => new Main\Entity\StringField('LANG', array(
				'validation' => array(__CLASS__, 'validateLang'),
				'title' => Loc::getMessage('GROUP_LANG_ENTITY_LANG_FIELD')
			)),
			'NAME' => new Main\Entity\StringField('NAME', array(
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('GROUP_LANG_ENTITY_NAME_FIELD')
			)),
		);
	}
	/**
	 * Returns validators for LID field.
	 *
	 * @return array
	 */
	public static function validateLang()
	{
		return array(
			new Main\Entity\Validator\Length(2, 2),
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
			new Main\Entity\Validator\Length(null, 100),
		);
	}
}