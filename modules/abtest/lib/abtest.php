<?php

namespace Bitrix\ABTest;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization;

Localization\Loc::loadMessages(__FILE__);

class ABTestTable extends Entity\DataManager
{

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_abtest';
	}

	/**
	 * Returns entity map definition
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'title'     => Localization\Loc::getMessage('abtest_entity_site_field'),
				'required'  => true
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'title'     => Localization\Loc::getMessage('abtest_entity_active_field'),
				'values'    => array('N', 'Y'),
				'required'  => true
			),
			'ENABLED' => array(
				'data_type' => 'enum',
				'title'     => Localization\Loc::getMessage('abtest_entity_enabled_field'),
				'values'    => array('N', 'T', 'Y'),
				'required'  => true
			),
			'NAME' => array(
				'data_type' => 'string',
				'title'     => Localization\Loc::getMessage('abtest_entity_name_field'),
			),
			'DESCR' => array(
				'data_type' => 'text',
				'title'     => Localization\Loc::getMessage('abtest_entity_descr_field'),
			),

			'TEST_DATA' => array(
				'data_type'  => 'text',
				'title'      => Localization\Loc::getMessage('abtest_entity_test_data_field'),
				'serialized' => true,
				'required'   => true
			),

			'START_DATE' => array(
				'data_type' => 'datetime',
				'title'     => Localization\Loc::getMessage('abtest_entity_start_date_field'),
			),
			'STOP_DATE' => array(
				'data_type' => 'datetime',
				'title'     => Localization\Loc::getMessage('abtest_entity_stop_date_field'),
			),
			'DURATION' => array(
				'data_type' => 'integer',
				'title'     => Localization\Loc::getMessage('abtest_entity_duration_field'),
				'required'  => true
			),
			'PORTION' => array(
				'data_type' => 'integer',
				'title'     => Localization\Loc::getMessage('abtest_entity_portion_field'),
				'required'  => true
			),

			'MIN_AMOUNT' => array(
				'data_type' => 'integer',
				'title'     => Localization\Loc::getMessage('abtest_entity_min_amount_field')
			),

			'USER_ID' => array(
				'data_type' => 'integer',
				'title'     => Localization\Loc::getMessage('abtest_entity_userid_field')
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),

			'SORT' => array(
				'data_type' => 'integer',
				'title'     => Localization\Loc::getMessage('abtest_entity_sort_field'),
			),
		);
	}

}
