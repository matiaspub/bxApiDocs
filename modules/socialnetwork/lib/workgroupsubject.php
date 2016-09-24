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

class WorkgroupSubjectTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sonet_group_subject';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'primary' => true
			),
			'SITE' => array(
				'data_type' => '\Bitrix\Main\Site',
				'reference' => array('=this.SITE_ID' => 'ref.LID')
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			'SORT' => array(
				'data_type' => 'integer',
			)
		);

		return $fieldsMap;
	}
}
