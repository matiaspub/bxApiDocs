<?php
namespace Bitrix\Im\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class AliasTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ALIAS string(255) mandatory
 * <li> ENTITY_TYPE string(255) mandatory
 * <li> ENTITY_ID string(255) mandatory
 * </ul>
 *
 * @package Bitrix\Im
 **/

class AliasTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_im_alias';
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
				'title' => Loc::getMessage('ALIAS_ENTITY_ID_FIELD'),
			),
			'ALIAS' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateAlias'),
				'title' => Loc::getMessage('ALIAS_ENTITY_ALIAS_FIELD'),
			),
			'ENTITY_TYPE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateEntityType'),
				'title' => Loc::getMessage('ALIAS_ENTITY_ENTITY_TYPE_FIELD'),
			),
			'ENTITY_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateEntityId'),
				'title' => Loc::getMessage('ALIAS_ENTITY_ENTITY_ID_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for ALIAS field.
	 *
	 * @return array
	 */
	public static function validateAlias()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for ENTITY_TYPE field.
	 *
	 * @return array
	 */
	public static function validateEntityType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for ENTITY_ID field.
	 *
	 * @return array
	 */
	public static function validateEntityId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}