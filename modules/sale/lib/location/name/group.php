<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Location\Name;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Sale\Location;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class GroupTable extends NameEntity
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_location_group_lang'; // "_lang", not "_name" because we use an old table
	}

	public static function getLanguageFieldName()
	{
		return 'LID';
	}

	static public function getReferenceFieldName()
	{
		return 'LOCATION_GROUP_ID';
	}

	public static function getMap()
	{
		return array(

			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SALE_LOCATION_NAME_GROUP_ENTITY_NAME_FIELD')
			),
			'LID' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SALE_LOCATION_NAME_GROUP_ENTITY_LANGUAGE_ID_FIELD')
			),
			// alias for LID
			'LANGUAGE_ID' => array(
				'data_type' => 'string',
				'expression' => array(
					'%s', 
					'LID'
				),
				'title' => Loc::getMessage('SALE_LOCATION_NAME_GROUP_ENTITY_LANGUAGE_ID_FIELD')
			),

			'LOCATION_GROUP_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('SALE_LOCATION_NAME_GROUP_ENTITY_LOCATION_GROUP_ID_FIELD')
			),
			// alias for LOCATION_GROUP_ID
			'GROUP_ID' => array(
				'data_type' => 'integer',
				'expression' => array(
					'%u', 
					'LOCATION_GROUP_ID'
				),
				'title' => Loc::getMessage('SALE_LOCATION_NAME_GROUP_ENTITY_LOCATION_GROUP_ID_FIELD')
			),

			// virtual
			'GROUP' => array(
				'data_type' => '\Bitrix\Sale\Location\Group',
				'required' => true,
				'reference' => array(
					'=this.LOCATION_GROUP_ID' => 'ref.ID'
				)
			),

			'CNT' => array(
				'data_type' => 'integer',
				'expression' => array(
					'count(*)'
				)
			),
		);
	}
}
