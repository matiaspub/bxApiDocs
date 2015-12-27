<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage socialnetwork
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Socialnetwork;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class LogPageTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sonet_log_page';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'primary' => true
			),
			'GROUP_CODE' => array(
				'data_type' => 'string',
				'primary' => true
			),
			'PAGE_SIZE' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'PAGE_NUM' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'PAGE_LAST_DATE' => array(
				'data_type' => 'datetime'
			),
			'TRAFFIC_AVG' => array(
				'data_type' => 'integer'
			),
			'TRAFFIC_CNT' => array(
				'data_type' => 'integer'
			),
			'TRAFFIC_LAST_DATE' => array(
				'data_type' => 'datetime'
			)
		);

		return $fieldsMap;
	}
}
