<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Entity;

class SiteDomainTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_lang_domain';
	}

	public static function getMap()
	{
		return array(
			'LID' => array(
				'data_type' => 'string',
				'primary' => true,
			),
			'DOMAIN' => array(
				'data_type' => 'string'
			),
			'SITE' => array(
				'data_type' => 'Bitrix\Main\Site',
				'reference' => array('=this.LID' => 'ref.LID'),
			),
		);
	}
}
