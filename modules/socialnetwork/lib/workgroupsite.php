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

class WorkgroupSiteTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_sonet_group_site';
	}

	public static function getMap()
	{
		return array(
			'GROUP_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'GROUP' => array(
				'data_type' => '\Bitrix\Socialnetwork\Workgroup',
				'reference' => array('=this.GROUP_ID' => 'ref.ID')
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'primary' => true
			),
			'SITE' => array(
				'data_type' => '\Bitrix\Main\Site',
				'reference' => array('=this.SITE_ID' => 'ref.LID')
			),
		);
	}
}
