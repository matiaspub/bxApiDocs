<?php

namespace Bitrix\Catalog;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StoreTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_catalog_store';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
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

	/**
	 * Return uf identifier.
	 *
	 * @return string
	 */
	public static function getUfId()
	{
		return 'CAT_STORE';
	}
}