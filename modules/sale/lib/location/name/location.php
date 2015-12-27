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

class LocationTable extends NameEntity
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_loc_name';
	}

	public static function add($data = array())
	{
		if(strlen($data['NAME']))
			$data['NAME_UPPER'] = ToUpper($data['NAME']); // bitrix to upper

		return parent::add($data);
	}

	public static function update($primary, $data = array())
	{
		if(strlen($data['NAME']))
			$data['NAME_UPPER'] = ToUpper($data['NAME']); // bitrix to upper

		return parent::update($primary, $data);
	}

	static public function getReferenceFieldName()
	{
		return 'LOCATION_ID';
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
				'title' => Loc::getMessage('SALE_LOCATION_NAME_LOCATION_ENTITY_NAME_FIELD')
			),
			'NAME_UPPER' => array(
				'data_type' => 'string',
			),
			'SHORT_NAME' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('SALE_LOCATION_NAME_LOCATION_ENTITY_SHORT_NAME_FIELD')
			),
			'LANGUAGE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SALE_LOCATION_NAME_LOCATION_ENTITY_LANGUAGE_ID_FIELD')
			),

			'LOCATION_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('SALE_LOCATION_NAME_LOCATION_ENTITY_LOCATION_ID_FIELD')
			),
			'LOCATION' => array(
				'data_type' => 'Bitrix\Sale\Location\Location',
				'required' => true,
				'reference' => array(
					'=this.LOCATION_ID' => 'ref.ID'
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
