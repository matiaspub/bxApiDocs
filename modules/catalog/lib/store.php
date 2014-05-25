<?php

namespace Bitrix\Catalog;

use Bitrix\Main\Entity;

class StoreTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_catalog_store';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'TITLE' => array(
				'data_type' => 'string'
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'ADDRESS' => array(
				'data_type' => 'string'
			),
			'DESCRIPTION' => array(
				'data_type' => 'string'
			),
			'GPS_N' => array(
				'data_type' => 'string'
			),
			'GPS_S' => array(
				'data_type' => 'string'
			),
			'IMAGE_ID' => array(
				'data_type' => 'string'
			),
			'LOCATION_ID' => array(
				'data_type' => 'integer'
			),
			'DATE_MODIFY' => array(
				'data_type' => 'datetime'
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime'
			),
			'USER_ID' => array(
				'data_type' => 'integer'
			),
			'MODIFIED_BY' => array(
				'data_type' => 'integer'
			),
			'PHONE' => array(
				'data_type' => 'string'
			),
			'SCHEDULE' => array(
				'data_type' => 'string'
			),
			'XML_ID' => array(
				'data_type' => 'string'
			)
		);

		return $fieldsMap;
	}
}
